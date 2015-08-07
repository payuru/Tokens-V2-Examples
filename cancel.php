<?

    require 'addsign.php';

    function sendCancelRequest($token, $data) {
        
        $url = 'https://secure.payu.ru/order/token/v2/merchantToken/' . $token;
        
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


    $merchant = 'merchant';
    $token = '1c82fc76364cb1eafa04f7225b16b1ae';

    $data = addSign(array('merchant' => $merchant));
    $data['cancelReason'] = 'Client requested token cancelation';

    $result = json_decode(sendCancelRequest($token, $data), 1);

    if ($result['meta']['response']['httpCode']) die($result['meta']['response']['httpMessage']);
