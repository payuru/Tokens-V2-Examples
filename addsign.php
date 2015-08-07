<?

    // расчет подписи для всех методов

    function addSign($data){

        ksort($data);

        $signString = "";
        foreach($data as $one){
            $signString .=  $one;    
        }

        $data['timestamp'] = time();

	$secret = 'secret_key';
	
        $data['signature'] =  hash_hmac("sha256", $signString . $data['timestamp'], $secret);

        return $data; 

    }
