<?


function createPaymentALUV3($new_token = false) {

	$token = '100000200300';				// если токен существует, берется из бады данных
        
        $arParams = array(

                  "MERCHANT" => 'merchant',
                  "ORDER_REF" => '100_200',			// номер заказа или любая информация, для идентификации заказа в IPN и КП PayU
                  "ORDER_DATE" => date('Y-m-d H:i:s'),
                   
                  'ORDER_PNAME[0]' => 'Пополнение счета',	// описание
                  'ORDER_PCODE[0]' => 'Код транзакции',		// информация о транзакции (внутренний код)
                  'ORDER_PINFO[0]' => 'Информация о платеже',	// любая дополнительная информация
                  'ORDER_PRICE[0]' => 1.01,			// сумма
                  'ORDER_QTY[0]' => 1,				// количество
                   
                  'ORDER_VAT[0]' => 0,				// ставка НДС: 0, 19
                  'ORDER_SHIPPING' => 0,			// стоимость доставки, если есть
                  
                  "PRICES_CURRENCY" => "RUB",			// валюта
                  "PAY_METHOD" => 'CCVISAMC',			// способ оплаты
                  
                  "CC_CVV" => '123',				// CVV
                   
                  "BACK_REF" =>  'http://',			// URL возврата после платежа

                  "CLIENT_IP" =>  $_SERVER['REMOTE_ADDR'], 

                  "BILL_LNAME" => 'Иван', 			// На чей счет выстален счет (поля автозаполнения в формах PayU), UTF-8
                  "BILL_FNAME" => 'Петров',
                  "BILL_EMAIL" => 'shopper@payu.ru',
                  "BILL_PHONE" => '79160000000',
                  
                  "BILL_COUNTRYCODE" => "RU",
                   
                  "DELIVERY_FNAME" => 'Иван',			// На чье имя доставляется товар (необязательно для заполнения)
                  "DELIVERY_LNAME" => 'Иванов',
                  "DELIVERY_PHONE" => '79160000000',
                  "DELIVERY_ADDRESS" => 'ул. Перева',
                  "DELIVERY_ZIPCODE" => '117321',
                  "DELIVERY_CITY" => 'Москва',
                  "DELIVERY_STATE" => 'Москва',

                  "DELIVERY_COUNTRYCODE" => "RU",
	);

	
	// платеж по уже существующему токену ?
        if(isset($token)) {

		$arParams["CC_TOKEN"] = $token;

	} else {

		$arParams["CC_NUMBER"] = '4506000000000000';	// номер карты
		$arParams["EXP_MONTH"] = '12';			// месяц окончания действия карты
		$arParams["EXP_YEAR"]  = '2017';		// год окончания действия карты
		$arParams["CC_OWNER"]  = 'Ivan Petrov';		// владелец карты

		// создавать токен ?	    
		if($new_token) {
			$arParams["LU_ENABLE_TOKEN"]= 1;
			$arParams["LU_TOKEN_TYPE"]  = "PAY_BY_CLICK";
		} 
	}
        
	// вычисление HASH
        ksort($arParams);
        $hashString = "";
                         
	foreach ($arParams as $key=>$val) {
		$hashString .= strlen($val) . $val;
	}
                 
	$arParams["ORDER_HASH"] = hash_hmac("md5", $hashString, 'secret_key');


	// отправка запроса
	$ch = curl_init();

	curl_setopt($ch, CURLOPT_URL, 'https://secure.payu.ru/order/alu/v3');
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_TIMEOUT, 60);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($arParams));

	$response = curl_exec($ch);
                 
	$curlerrcode = curl_errno($ch);
	$curlerr = curl_error($ch);
                 
	if (empty($curlerr) && empty($curlerrcode)) {

		$parsedXML = @simplexml_load_string($response);

		if ($parsedXML !== FALSE) {
                        // Номер операции в системе PayU
                        // Сохранить в базе данных, привязанным к номеру заказа, для поиска заказа в случае 3DSecure карт
                        // Может быть пустым, в случае ошибок в параметрах
                        $payuTranReference = $parsedXML->REFNO;
                 
                        if ($parsedXML->STATUS == "SUCCESS") {
                 
			    // В случае 3DS карт, PayU выдает уникальный для каждой транзакции URL в теге URL_3DS
			    // Пользователь должен быть перенаправлен на этот URL для завершения транзакции. Затем он будет перенаправлен на BACK_REF
                            if (($parsedXML->RETURN_CODE == "3DS_ENROLLED") && (!empty($parsedXML->URL_3DS))) {

                                header("Location:" . $parsedXML->URL_3DS);
                                die;

                            }

			    // обработка успешной операции
                            
                        } else {
                            
				$error = ALUerrorByCode((string)$parsedXML->RETURN_CODE);
                            
				// обработка ошибки 

				if (!empty($payuTranReference)) {
	                                // транзакция зарегистрирована в системе PayU, но возникла ошибка в процессе авторизации операции банком
        	                        // посмотреть $parsedXML->RETURN_MESSAGE и $parsedXML->RETURN_CODE для получения информации
				}

                            	// вывод сообщения об ошибке
                        }

                } else {

			// обработка ошибки CURL & die

                }
	}

}


function ALUerrorByCode($code) {

	$arr = array(

		'AUTHORIZED' => "Прошло успешно",
		'3DS_ENROLLED' => "3DS (see response sample)",
		'ALREADY_AUTHORIZED' => "Такая транзакция уже была произведена. Пересоздайте платеж заново.",
		'AUTHORIZATION_FAILED' => "Платеж неуспешный.",
		'INVALID_CUSTOMER_INFO' => "Неправильная информация плательщика.",
		'INVALID_PAYMENT_INFO' => "Неправильные данные карты",
		'INVALID_ACCOUNT' => "Неправильный идентификатор мерчанта",
		'INVALID_PAYMENT_METHOD_CODE' => "Неправильный метод оплаты.",
		'INVALID_CURRENCY' => "Неправильная валюта.",
		'REQUEST_EXPIRED' => "Запрос устарел. Повторите попытку.",
		'HASH_MISMATCH' => "Неправильные данные.",
		'WRONG_VERSION' => "Неправильная версия протокола.",

	);

	return  (isset($arr[$code])) ? $arr[$code] : "Произошла ошибка. Повторите оплату позже." ;
}


createPaymentALUV3(true);

