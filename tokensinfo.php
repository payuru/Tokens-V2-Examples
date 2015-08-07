<?

    function sendTokensRequest($data) {

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


    $merchant = 'merchant';
    $tokens = array( 0 => '1c82fc76364cb1eafa04f7225b16b1ae',
		     1 => '2d82fc76364cb1eafa04f7225b16b1bf'
		);


    $data = array();

    foreach($tokens as $k => $v) $data = array('tokens['.$k.']' => $v) + $data;

    $data = $data + array('merchant' => $merchant);

    $data = addSign($data);

    $response = json_decode(sendTokensRequest($data), 1);

    if ($response['meta']['response']['httpCode'] !== 200) die($response['meta']['response']['httpMessage']);

    print_r($response);



