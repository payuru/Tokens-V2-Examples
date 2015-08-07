<?

// обработка IPN

    require 'addsign.php';

    function createToken($tokenData){

        $res = false;

	// проверка наличия всех полей
        foreach(array('IPN_CC_TOKEN', 'CARD_HOLDER_NAME', 'CARD_MASK', 'IPN_INFO', 'IPN_CC_EXP_DATE') as $one) {
            if(!array_key_exists($one, $tokenData)) die("missing value: " . $one);
        }

	$merchant = 'merchant';
	$token = $_POST['IPN_CC_TOKEN'];

        $data = addSign(array('merchant' => $merchant, 'refNo' => $token));
        $tokenInfo = json_decode(sendRequest($data), 1);

        if(isset($tokenInfo['meta']['status']['message']) && $tokenInfo['meta']['status']['message'] == 'success' && isset($tokenInfo['token'])){
		$token = $data['token'];
		// сохранить токен в БД
        }   

        return $res;

    }

    function sendRequest($data) {
        
        $url = 'https://secure.payu.ru/order/token/v2/merchantToken';
        
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);

        $curlerrcode = curl_errno($ch);
        $curlerr = curl_error($ch);

        if($curlerrcode || $curlerr) {
		// обработать ошибки CURL & die
	}
  
        return curl_exec($ch);
     
    }




    $option  = array(	'merchant' => 'merchant', 
			'secretkey' => 'secret_key',
			'debug' => 0, 
            		'luUrl' => "https://secure.payu.ru/order/lu.php", 
			'button' => ""
		);

    require 'PayU.php';	// PayU класс

    $payanswer = PayU::getInst()->setOptions( $option )->IPN();


    if(isset($_POST['IPN_CC_TOKEN'])){
	$res = createToken($_POST);
    }

    // реакция на статус IPN
    if ($_POST['ORDERSTATUS'] == "PAYMENT_AUTHORIZED")
    {
	// платеж по карте авторизирован (для оплаты не через Visa/MasterCard/Maestro такой статус означает списание денежных средств)
    }
    elseif ($_POST['ORDERSTATUS'] == "COMPLETE" )
    {
	// заказ выполнен (авторизован/поставлен), деньги списаны со счёта клиента - отметить в базе данных
    }
    elseif ($_POST['ORDERSTATUS'] == "TEST" )
    {
	// тестовый
    }
    elseif( $_POST['ORDERSTATUS'] == "REFUND" )
    {
	// обработка refund
    }



    // показать ответ системе PayU
    echo $payanswer;



