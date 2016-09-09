<?php

// Define constant APPLICATION_ENV
if (!defined('APPLICATION_ENV') && array_key_exists('HTTP_APPLICATION_ENV', $_SERVER)) {
    define('APPLICATION_ENV', $_SERVER['HTTP_APPLICATION_ENV']);
} elseif (!defined('APPLICATION_ENV') && array_key_exists('APPLICATION_ENV', $_SERVER)) {
    define('APPLICATION_ENV', $_SERVER['APPLICATION_ENV']);
} elseif (!defined('APPLICATION_ENV')) {
    define('APPLICATION_ENV', 'production');
}

ini_set('log_errors', 1);
ini_set('error_log', dirname(__FILE__) . '/log/payment.log');

/**
 * Значение слова-пароля выдаваемое модератором PeliPay
 * учавствует в формировании подписи запросов
 * @var string
 */
class PaySystem {

    private $_apiUrl = 'http://api.pelipay.com/';

    /**
     * значение host сайта-КЛИЕНТА
     * @var string
     */
    private $_myHost = 'mysite.com';
    private $_apiSekret = 'SOME_SECRET_KEY';
    private $_request = array();

    public function __construct($apiHost = '', $apiSekret = '', $newApiFileName = null) {

        if (!is_null($newApiFileName)) {
            $this->_apiUrl = $this->_apiUrl . $newApiFileName;
        }

        if ($apiHost) {
            $this->_myHost = $apiHost;
        }
        if ($apiSekret) {
            $this->_apiSekret = $apiSekret;
        }

        $this->setHTTPRequest();
    }

    function setHTTPRequest() {
        $body = file_get_contents('php://input');

        if (is_object(json_decode($body))) {
            $this->_request = json_decode($body, true);
        } elseif (is_string($body) && !empty($body)) {
            $this->_request['ppBody'] = base64_encode($body);
        }

        if ($_REQUEST) {
            $this->_request = array_merge($this->_request, $_REQUEST);
        }
    }

    public function getAvailablePaysystems() {
        return $this->sendRequest('getAvailablePaysystems');
    }

    public function getPayForm($request) {
        return $this->sendRequest('getPayForm', $request);
    }

    public function checkSuccessUrl() {

        error_log('============= successUrl REQUEST START');
        error_log('RequestData => ' . print_r($this->_request, true));
        error_log('============= successUrl REQUEST END');

        $this->_request['url'] = 'successUrl';
        $resp = $this->sendRequest('checkReturnUrl', $this->_request);
        if ($resp['success'] && isset($resp['data']['redirectUrl'])) {
            header('Location: ' . $resp['data']['redirectUrl']);
            exit;
        }
    }

    public function checkFailUrl() {

        error_log('============= failUrl REQUEST START');
        error_log('RequestData => ' . print_r($this->_request, true));
        error_log('============= failUrl REQUEST END');

        $this->_request['url'] = 'failUrl';
        $resp = $this->sendRequest('checkReturnUrl', $this->_request);
        if ($resp['success'] && isset($resp['data']['redirectUrl'])) {
            header('Location: ' . $resp['data']['redirectUrl']);
            exit;
        }
    }

    public function statusUrl($ololo = array()) {

        error_log('============= statusUrl REQUEST START');
        error_log('RequestData => ' . print_r($this->_request, true));
        error_log('============= statusUrl REQUEST END');

        return $this->sendRequest('statusUrl', $ololo ? $ololo : $this->_request);
    }

    public function getIframeForm() {
        error_log('============= iframeUrl REQUEST START');
        error_log('RequestData => ' . print_r($this->_request, true));
        error_log('============= iframeUrl REQUEST END');

        $ret = '';
        $resp = $this->sendRequest('getPayForm', $this->_request);
        if ($resp['success'] && isset($resp['data']['payForm'])) {
            $ret = $resp['data']['payForm'];
        }
        return $ret;
    }

    /**
     * Отправка запроса на API PeliPay
     * @param string $action - метод API который будем дергать. Пример: getPayForm
     * @param array $request - массив параметров передаваемый в метод
     * @return array - ответ от сервера
     */
    public function sendRequest($action, $request = array()) {

        // подготовка данных к отправке на PeliPay
        $request = $this->_prepareRequest($action, $request);
        // инициализируем сеанс
        $curl = curl_init();
        // уcтанавливаем урл, к которому обратимся
        curl_setopt($curl, CURLOPT_URL, $this->_apiUrl);
        // максимальное время выполнения скрипта
        curl_setopt($curl, CURLOPT_TIMEOUT, 20);
        // передаем данные по методу post
        curl_setopt($curl, CURLOPT_POST, 1);
        // теперь curl вернет нам ответ, а не выведет
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        //curl_setopt($curl, CURLOPT_NOBODY, false);
        // переменные, которые будут переданные по методу post
        curl_setopt($curl, CURLOPT_POSTFIELDS, $request);
        // отправка запроса

        $result = curl_exec($curl);
        //d_var($result);
        // закрываем соединение
        curl_close($curl);

        if ($result) {
            $result = json_decode($result, true);
        } else {
            $result = array("statuscode" => 503, "error" => "Server is not responding");
        }

        return $result;
    }

    /**
     * Подготовка данных перед отправкой
     * @param string $action - вызываемый метод PeliPay
     * @param array $request - данные передаваемые в метод
     * @return array - массив подготовленых данных
     */
    private function _prepareRequest($action, $request) {
        //  Структурирование данных
        $requestArr = array(
            'action' => $action,
            'host' => $this->_myHost,
            'request' => $request,
        );

        // Получение JSON представления данных
        $requestJson = json_encode($requestArr);
        // Кодирование данных
        $data = base64_encode($requestJson);

        // Генерация подписи
        $sign = base64_encode(md5($this->_apiSekret . $requestJson . $this->_apiSekret));

        // Возврат данных подготовленных данных
        return array(
            'data' => $data,
            'sign' => $sign
        );
    }

}
