<?php

/**
 * Значение слова-пароля выдаваемое модератором Rivalpay
 * учавствует в формировании подписи запросов
 * @var string 
 */
define('RIVALPAY_SECRET_KEY', 'das4kh0jZVJLQFJ6ogUS8QKylVPHthwl');
define('RIVALPAY_API_URL', 'http://api.rivalpay.com');

class RivalPayApi {

    /**
     * значение host сайта-КЛИЕНТА
     * @var string
     */
    private $_secret_key = '';
    private $_myHost = '';
    private $_request = array();

    public function __construct($secret_key='')
    {
		if($secret_key)
			$this->_secret_key = $secret_key;
		else
			$this->_secret_key = RIVALPAY_SECRET_KEY;
			
        $this->_myHost = $_SERVER['HTTP_HOST'];
        $this->setHTTPRequest();
    }

    function setHTTPRequest() {
        if (empty($_REQUEST)) {
            $this->_request = file_get_contents('php://input');

            if (is_object(json_decode($this->_request))) {
                $this->_request = json_decode($this->_request, true);
            } elseif (is_string($_REQUEST)) {
                parse_str($_REQUEST, $this->_request);
            }
        } else {
            $this->_request = $_REQUEST;
        }
    }
    
    public function getAvailablePaysystems(){
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

    public function statusUrl() {

        error_log('============= statusUrl REQUEST START');
        error_log('RequestData => ' . print_r($this->_request, true));
        error_log('============= statusUrl REQUEST END');

        return $this->sendRequest('statusUrl', $this->_request);
    }

    /**
     * Отправка запроса на API Rivalpay
     * @param string $action - метод API который будем дергать. Пример: getPayForm
     * @param array $request - массив параметров передаваемый в метод
     * @return array - ответ от сервера
     */
    public function sendRequest($action, $request = array()) {

        // подготовка данных к отправке на Rivalpay
        $request = $this->_prepareRequest($action, $request);

        // инициализируем сеанс
        $curl = curl_init();
        // уcтанавливаем урл, к которому обратимся
        curl_setopt($curl, CURLOPT_URL, RIVALPAY_API_URL);
        // максимальное время выполнения скрипта
        curl_setopt($curl, CURLOPT_TIMEOUT, 20);
        // передаем данные по методу post
        curl_setopt($curl, CURLOPT_POST, 1);
        // теперь curl вернет нам ответ, а не выведет
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        // переменные, которые будут переданные по методу post
        curl_setopt($curl, CURLOPT_POSTFIELDS, $request);
        // отправка запроса
        $result = curl_exec($curl);
        
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
     * @param string $action - вызываемый метод Rivalpay
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
        $sign = base64_encode(md5($this->_secret_key . $requestJson . $this->_secret_key));

        // Возврат данных подготовленных данных
        return array(
            'data' => $data,
            'sign' => $sign
        );
    }
    
    public function getIframeForm(){
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

    public function sendMoney()
    {
        return $this->sendRequest('sendmoney', $request);
	}

    public function sendMoneyFields()
    {
		return $this->sendRequest('sendmoneyfields', $request);
	}

}

/* End of file Someclass.php */
