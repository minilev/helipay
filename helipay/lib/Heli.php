<?php
require_once "Heli.Config.php";
require_once "HlpUtils.php";

class Heli
{
	private static $secret_arr = [
		'P8_idCardNo', 
		'P9_cardNo', 
		'P10_year', 
		'P11_month', 
		'P12_cvv2', 
		'P13_phone',
		'P5_validateCode',
	];

    /**
     * 对接客户端请求的json格式数据返回
     * code=1时，成功获取数据；
     * code!=1，返回错误信息，此时data为string
     *
     * @param mixed $retData 返回的数据，array, string
     * @param int $retCode  1 成功，否则 错误码
     * @return false|string
     */
    public static function retJson($retData,$retCode = 1)
    {
        $arrRet['code']   = $retCode;
        $arrRet['time']   = time();
        $arrRet['data']   = $retData;
		return $arrRet;
        die(json_encode($arrRet, JSON_UNESCAPED_UNICODE));
    }


    /**
     * 用户真实IP地址
     *
     * @return string
     */
    public static function getUserIP()
    {

        if (!empty($_SERVER['HTTP_CDN_SRC_IP'])) {
            return trim($_SERVER['HTTP_CDN_SRC_IP']);
        }

        if (!empty($_SERVER['HTTP_WL_PROXY_CLIENT_IP'])) {
            return trim($_SERVER['HTTP_WL_PROXY_CLIENT_IP']);
        }

        $x_for_arr = @explode(',', trim($_SERVER['HTTP_X_FORWARDED_FOR']));
        $x_for     = trim($x_for_arr[0]);
        if (!empty($x_for)) {
            return $x_for;
        }

        $client_ip = isset($_SERVER['HTTP_X_REAL_IP']) ? $_SERVER['HTTP_X_REAL_IP'] : '';
        if (!empty($client_ip)) {
            return $client_ip;
        }
        return trim($_SERVER['REMOTE_ADDR']);
    }

	/**
	 * 获取设备
	 */
	public function getterminalId() {
		dump($_SERVER['HTTP_USER_AGENT']);die;
		if(strpos($_SERVER['HTTP_USER_AGENT'], 'iPhone')||strpos($_SERVER['HTTP_USER_AGENT'], 'iPad')){
			echo 'systerm is IOS';
		   }else if(strpos($_SERVER['HTTP_USER_AGENT'], 'Android')){
			echo 'systerm is Android';
		   }else{
			echo 'systerm is other';
		   }
	}

	/**
	 * 拼接请求的参数
	 */
    private function dealPostParams($pre_post_params = [], $aes_key = '')
    {
    	if (empty($pre_post_params) || !is_array($pre_post_params) || empty($aes_key)) {
    		return false;
    	}

    	$ret_post_str = '';
		foreach ($pre_post_params as $key => $value) {
			$ret_post_str .=  '&' . $value;
			if ($value == '') {
				unset($pre_post_params[$key]);
			}

			if (in_array($key, self::$secret_arr) && $value != '') {
				// $pre_post_params[$key] = HlpUtils::aesEncrypt( $value, $aes_key );
			}

		}

		$pre_post_params['ret_post_str'] = $ret_post_str;
		return $pre_post_params;
    }

    /**
     * 获取用户绑定的银行卡列表
     */
    public function bankCardBindList($post)
    {
		$post['P1_bizType'] = 'BankCardbindList';
		$post['P2_customerNumber'] = HlPayConfig::$mchid;
    	if (!isset($post['P1_bizType'])) {
    		$this->retJson('缺少交易类型', -1001);
    	}

    	if (!isset($post['P2_customerNumber'])) {
    		$this->retJson('缺少商户编号', -1002);
    	}

    	if (!isset($post['P3_userId'])) {
    		$this->retJson('缺少用户id', -1003);
    	}

    	/*$post = [
    		'P1_bizType' => 'BankCardbindList',
    		'P2_customerNumber' => 'C1800000002',
    		'P3_userId' => '1000000001',
    		'P4_bindId' => '',
    		//'P5_timestamp' => date('YmdHis',time()),
    		//'P13_phone' => '18145823456',

    	];*/
    	// $post['P5_timestamp'] = date('YmdHis',time());
		$params = [
			'P1_bizType'	=> $post['P1_bizType'],
			'P2_customerNumber'	=> $post['P2_customerNumber'],
			'P3_userId'	=> HlPayConfig::$user_solt.$post['P3_userId'],
			'P4_bindId'	=> $post['P4_bindId'],
			'P5_timestamp'	=> date('YmdHis',time())
		];

    	//获取加密密钥
		$aes_key = HlpUtils::genKey(128);

    	//使用合利宝公钥进行aesKey的加密
		$encryption_key = HlpUtils::rsaEncrypt( $aes_key );
		// dump($encryption_key);
		$post_arr = $this->dealPostParams($params, $aes_key);

    	//MD5withRSA签名，该方法读取商户证书
    	if (isset($post_arr['ret_post_str'])) {
    		$sign = HlpUtils::sign( $post_arr['ret_post_str'] );
    		unset($post_arr['ret_post_str']);
    	}

    	$post_arr['signatureType'] = 'MD5WITHRSA';
		$post_arr['sign']          = $sign;
		// var_dump($post_arr);exit;

		$result = HlpUtils::post( HlPayConfig::$url, $post_arr );
		// return $result;
    	return $this->retJson($result);
    }

    /**
     * 绑卡预下单
     * 用户需要绑卡时上送银行卡等信息进行预下单，以便后续发送绑卡验证码和绑卡。
     */
    public function bindCardPreOrder($post)
    {
		$post['P1_bizType'] = 'QuickPayBindCardPreOrder';
		$post['P2_customerNumber'] = HlPayConfig::$mchid;
    	if (!isset($post['P1_bizType'])) {
    		$this->retJson('缺少交易类型', -1001);
    	}

    	if (!isset($post['P2_customerNumber'])) {
    		$this->retJson('缺少商户编号', -1002);
    	}

    	if (!isset($post['P3_userId'])) {
    		$this->retJson('缺少用户id', -1003);
    	}

    	if (!isset($post['P4_orderId'])) {
    		$this->retJson('缺少商户订单号', -1004);
    	}

    	$post['P5_timestamp'] = date('YmdHis',time());

    	if (!isset($post['P6_payerName'])) {
    		$this->retJson('缺少姓名', -1006);
    	}

    	if (!isset($post['P7_idCardType'])) {
    		$this->retJson('缺少证件类型', -1007);
    	}

    	if (!isset($post['P8_idCardNo'])) {
    		$this->retJson('缺少证件号码', -1008);
    	}

    	if (!isset($post['P9_cardNo'])) {
    		$this->retJson('缺少银行卡号', -1009);
    	}

    	// if (!isset($post['P10_year']) || empty($post['P10_year'])) {
    	// 	$post['P10_year'] = '';
    	// }

    	// if (!isset($post['P11_month']) || empty($post['P11_month'])) {
    	// 	$post['P11_month'] = '';
    	// }

    	// if (!isset($post['P12_cvv2']) || empty($post['P12_cvv2'])) {
    	// 	$post['P12_cvv2'] = '';
    	// }

    	if (!isset($post['P13_phone'])) {
    		$this->retJson('缺少手机号', -1010);
    	}

    	// $post['sendValidateCode'] = true;
    	// $post['protocolType'] = 'protocol';
		$params = [
			'P1_bizType'	=> $post['P1_bizType'],
			'P2_customerNumber'	=> $post['P2_customerNumber'],
			'P3_userId'	=> HlPayConfig::$user_solt.$post['P3_userId'],
			'P4_orderId'	=> $post['P4_orderId'],
			'P5_timestamp'	=> date('YmdHis',time()),
			'P6_payerName'	=> $post['P6_payerName'],
			'P7_idCardType'	=> $post['P7_idCardType'],
			'P8_idCardNo'	=> $post['P8_idCardNo'],
			'P9_cardNo'	=> $post['P9_cardNo'],
			'P10_year'	=> $post['P10_year'] ? $post['P10_year'] : '',
			'P11_month'	=> $post['P11_month'] ? $post['P11_month'] : '',
			'P12_cvv2'	=> $post['P12_cvv2'] ? $post['P12_cvv2'] : '',
			'P13_phone'	=> $post['P13_phone'],
		];

		// dump($params);
    	//获取加密密钥
		$aes_key = HlpUtils::genKey(128);
		
    	//使用合利宝公钥进行aesKey的加密
		$encryption_key = HlpUtils::rsaEncrypt( $aes_key );

		$post_arr = $this->dealPostParams($params, $aes_key);
		// dump($post_arr);
    	//MD5withRSA签名，该方法读取商户证书
    	if (isset($post_arr['ret_post_str'])) {
    		$sign = HlpUtils::sign( $post_arr['ret_post_str'] );
    		unset($post_arr['ret_post_str']);
    	}

		// $post_arr['sendValidateCode'] = TRUE;
    	// $post_arr['protocolType'] = 'protocol';
    	$post_arr['signatureType'] = 'MD5WITHRSA';
    	$post_arr['encryption_key'] = $encryption_key;
		$post_arr['sign']          = $sign;
		// dump($post_arr);
		$result = HlpUtils::post( HlPayConfig::$url, $post_arr );

    	return $this->retJson($result);

    }


    /**
     * 首次支付
     * 用户在商户平台使用银行卡进行首次支付时，商户平台调用合利宝首次支付接口把订单、银行卡信息提交到合利宝，合利宝生成商户订单记录。
     * 
     */
    public function firstPay()
    {
    	$post = Request::post();

    	if (!isset($post['P1_bizType'])) {
    		$this->retJson('缺少交易类型', -1001);
    	}

    	if (!isset($post['P2_customerNumber'])) {
    		$this->retJson('缺少商户编号', -1002);
    	}

    	if (!isset($post['P3_userId'])) {
    		$this->retJson('缺少用户id', -1003);
    	}

    	if (!isset($post['P4_orderId'])) {
    		$this->retJson('缺少商户订单号', -1004);
    	}

    	$post['P5_timestamp'] = date('YmdHis',time());

    	if (!isset($post['P6_payerName'])) {
    		$this->retJson('缺少姓名', -1006);
    	}

    	if (!isset($post['P7_idCardType'])) {
    		$this->retJson('缺少证件类型', -1007);
    	}

    	if (!isset($post['P8_idCardNo'])) {
    		$this->retJson('缺少证件号码', -1008);
    	}

    	if (!isset($post['P9_cardNo'])) {
    		$this->retJson('缺少银行卡号', -1009);
    	}

    	if (!isset($post['P10_year']) || empty($post['P10_year'])) {
    		$post['P10_year'] = '';
    	}

    	if (!isset($post['P11_month']) || empty($post['P11_month'])) {
    		$post['P11_month'] = '';
    	}

    	if (!isset($post['P12_cvv2']) || empty($post['P12_cvv2'])) {
    		$post['P12_cvv2'] = '';
    	}

    	if (!isset($post['P13_phone'])) {
    		$this->retJson('缺少手机号', -1013);
    	}
    	$post['P14_currency'] = 'CNY';


    	if (!isset($post['P15_orderAmount'])) {
    		$this->retJson('缺少交易金额', -1015);
    	}

    	if (!isset($post['P16_goodsName'])) {
    		$this->retJson('缺少商品名称', -1016);
    	}

    	if (!isset($post['P17_goodsDesc'])) {
    		$this->retJson('缺少商品描述', -1017);
    	}

    	if (!isset($post['P18_terminalType'])) {
    		$this->retJson('缺少终端类型', -1018);
    	}

    	if (!isset($post['P19_terminalId'])) {
    		$this->retJson('缺少终端标识', -1019);
    	}

    	$post['P20_orderIp'] = self::getUserIP();

    	if (!isset($post['P21_period']) || empty($post['P21_period'])) {
    		$post['P21_period'] = '';
    	}
    	if (!isset($post['P22_periodUnit']) || empty($post['P22_periodUnit'])) {
    		$post['P22_periodUnit'] = '';
    	}
    	if (!isset($post['P23_serverCallbackUrl']) || empty($post['P23_serverCallbackUrl'])) {
    		$post['P23_serverCallbackUrl'] = config('helipay_config.order_async_url');
    	}

    	//获取加密密钥
		$aes_key = HlpUtils::genKey(128);

    	//使用合利宝公钥进行aesKey的加密
		$encryption_key = HlpUtils::rsaEncrypt( $aes_key );

		$post_arr = $this->dealPostParams($post, $aes_key);

    	//MD5withRSA签名，该方法读取商户证书
    	if (isset($post_arr['ret_post_str'])) {
    		$sign = HlpUtils::sign( $post_arr['ret_post_str'] );
    		unset($post_arr['ret_post_str']);
    	}

    	
    	////////////////////////////////分账规则,json字符串。待定
    	$post_arr['ruleJson'] = '';


    	if (!isset($post['splitBillType']) || empty($post['splitBillType'])) {
    		$post_arr['splitBillType'] = '';
    	}

    	$post_arr['sendValidateCode'] = true;


    	if (!isset($post['goodsQuantity'])) {
    		$this->retJson('缺少商品数量', -1020);
    	}
    	$post_arr['goodsQuantity'] = $post['goodsQuantity'];

    	if (!isset($post['userAccount'])) {
    		$this->retJson('缺少用户注册账号', -1021);
    	}
    	$post_arr['userAccount'] = $post['userAccount'];

    	$post_arr['enrollTime'] = '';
    	$post_arr['lbs'] = '';

    	if (!isset($post['appType'])) {
    		$this->retJson('缺少应用类型', -1022);
    	}
    	$post_arr['appType'] = $post['appType'];

    	if (!isset($post['appName'])) {
    		$this->retJson('缺少应用名', -1023);
    	}
    	$post_arr['appName'] = $post['appName'];

    	if (!isset($post['dealSceneType'])) {
    		$this->retJson('缺少业务场景', -1024);
    	}
    	$post_arr['dealSceneType'] = $post['dealSceneType'];

    	$post_arr['dealSceneParams'] = '';
    	$post_arr['signatureType']   = 'MD5WITHRSA';
    	$post_arr['encryption_key']   = $encryption_key;
    	$post_arr['sign']			 = $sign;


    	$result = HlpUtils::post( HlPayConfig::$url, $post_arr );

    	$this->retJson($result);
    	
    }

    /**
     * 首次支付短信
     * 用户点击获取短信验证码，商户平台把用户首次支付订单号、手机号码提交到合利宝，合利宝下发首次支付短信验证码到用户手机号码。
     * 
     */
    public function firstSend()
    {
    	$post = Request::post();

    	if (!isset($post['P1_bizType'])) {
    		$this->retJson('缺少交易类型', -1001);
    	}

    	if (!isset($post['P2_customerNumber'])) {
    		$this->retJson('缺少商户编号', -1002);
    	}

    	if (!isset($post['P3_userId'])) {
    		$this->retJson('缺少用户id', -1003);
    	}

    	$post['P5_timestamp'] = date('YmdHis',time());

    	//获取加密密钥
		$aes_key = HlpUtils::genKey(128);

    	//使用合利宝公钥进行aesKey的加密
		$encryption_key = HlpUtils::rsaEncrypt( $aes_key );

		$post_arr = $this->dealPostParams($post, $aes_key);

    	//MD5withRSA签名，该方法读取商户证书
    	if (isset($post_arr['ret_post_str'])) {
    		$sign = HlpUtils::sign( $post_arr['ret_post_str'] );
    		unset($post_arr['ret_post_str']);
    	}

    	$post_arr['signatureType'] = 'MD5WITHRSA';
    	$post_arr['encryption_key'] = $encryption_key;
		$post_arr['sign']          = $sign;
		//var_dump($post_arr);exit;

		$result = HlpUtils::post( HlPayConfig::$url, $post_arr );

    }

    /**
     * 确认支付
     * 用户在商户平台输入首次支付短信验证码，确认支付；合利宝验证通过，完成支付；商户平台接收合利宝返回的绑定号，维护用户银行卡与合利宝的绑定关系。
     */
    public function confirmPay()
    {
    	$post = Request::post();

    	if (!isset($post['P1_bizType'])) {
    		$this->retJson('缺少交易类型', -1001);
    	}

    	if (!isset($post['P2_customerNumber'])) {
    		$this->retJson('缺少商户编号', -1002);
    	}

    	if (!isset($post['P3_userId'])) {
    		$this->retJson('缺少用户id', -1003);
    	}

    	$post['P5_timestamp'] = date('YmdHis',time());


    	if (!isset($post['P5_validateCode'])) {
    		$this->retJson('缺少验证码', -1004);
    	}

    	//获取加密密钥
		$aes_key = HlpUtils::genKey(128);

    	//使用合利宝公钥进行aesKey的加密
		$encryption_key = HlpUtils::rsaEncrypt( $aes_key );

		$post_arr = $this->dealPostParams($post, $aes_key);

    	//MD5withRSA签名，该方法读取商户证书
    	if (isset($post_arr['ret_post_str'])) {
    		$sign = HlpUtils::sign( $post_arr['ret_post_str'] );
    		unset($post_arr['ret_post_str']);
    	}

    	$post_arr['signatureType'] = 'MD5WITHRSA';
    	$post_arr['encryption_key'] = $encryption_key;
		$post_arr['sign']          = $sign;
		//var_dump($post_arr);exit;

		$result = HlpUtils::post( HlPayConfig::$url, $post_arr );

    }


    /**
     * 鉴权绑卡短信
     * 进行对用户银行卡绑定，后续支付时直接使用选卡而不需要再输入银行卡进行支付。
     * 上送鉴权绑卡预下单信息和短信验证码进行鉴权绑卡
     */
    public function bindCardMessage($post)
    {
    	$post['P1_bizType'] = 'BindCardSendValidateCode';
		$post['P2_customerNumber'] = HlPayConfig::$mchid;

    	if (!isset($post['P1_bizType'])) {
    		$this->retJson('缺少交易类型', -1001);
    	}

    	if (!isset($post['P2_customerNumber'])) {
    		$this->retJson('缺少商户编号', -1002);
    	}

    	if (!isset($post['P3_orderId'])) {
    		$this->retJson('缺少商户订单号', -1003);
    	}

    	// $post['P4_timestamp'] = date('YmdHis',time());
		$params = [
			'P1_bizType'	=> $post['P1_bizType'],
			'P2_customerNumber'	=> $post['P2_customerNumber'],
			'P3_orderId'	=> $post['P3_orderId'],
			'P4_timestamp'	=> date('YmdHis',time()),
		];
		
    	//获取加密密钥
		$aes_key = HlpUtils::genKey(128);

    	//使用合利宝公钥进行aesKey的加密
		$encryption_key = HlpUtils::rsaEncrypt( $aes_key );

		$post_arr = $this->dealPostParams($params, $aes_key);

    	//MD5withRSA签名，该方法读取商户证书
    	if (isset($post_arr['ret_post_str'])) {
    		$sign = HlpUtils::sign( $post_arr['ret_post_str'] );
    		unset($post_arr['ret_post_str']);
    	}

    	$post_arr['signatureType'] = 'MD5WITHRSA';
    	$post_arr['encryption_key'] = $encryption_key;
		$post_arr['sign']          = $sign;

		$result = HlpUtils::post( HlPayConfig::$url, $post_arr );

    	return $this->retJson($result);

    }


    /**
     * 鉴权绑卡
     * 进行对用户银行卡绑定，后续支付时直接使用选卡而不需要再输入银行卡进行支付。
     * 上送鉴权绑卡预下单信息和短信验证码进行鉴权绑卡
     */
    public function bindCard($post)
    {
    	$post['P1_bizType'] = 'ConfirmBindCard';
		$post['P2_customerNumber'] = HlPayConfig::$mchid;

    	if (!isset($post['P1_bizType'])) {
    		$this->retJson('缺少交易类型', -1001);
    	}

    	if (!isset($post['P2_customerNumber'])) {
    		$this->retJson('缺少商户编号', -1002);
    	}


    	if (!isset($post['P3_orderId'])) {
    		$this->retJson('缺少商户订单号', -1003);
    	}

    	$post['P4_timestamp'] = date('YmdHis',time());

    	if (!isset($post['P5_validateCode'])) {
    		$this->retJson('缺少短信验证码', -1004);
    	}
		$params = [
			'P1_bizType'	=> $post['P1_bizType'],
			'P2_customerNumber'	=> $post['P2_customerNumber'],
			'P3_orderId'	=> $post['P3_orderId'],
			'P4_timestamp'	=> date('YmdHis',time()),
			'P5_validateCode'	=> $post['P5_validateCode']
		];

    	//获取加密密钥
		$aes_key = HlpUtils::genKey(128);

    	//使用合利宝公钥进行aesKey的加密
		$encryption_key = HlpUtils::rsaEncrypt( $aes_key );

		$post_arr = $this->dealPostParams($params, $aes_key);

    	//MD5withRSA签名，该方法读取商户证书
    	if (isset($post_arr['ret_post_str'])) {
    		$sign = HlpUtils::sign( $post_arr['ret_post_str'] );
    		unset($post_arr['ret_post_str']);
    	}

    	$post_arr['signatureType'] = 'MD5WITHRSA';
    	$post_arr['encryption_key'] = $encryption_key;
		$post_arr['sign']          = $sign;

		$result = HlpUtils::post( HlPayConfig::$url, $post_arr );

    	return $this->retJson($result);

    }

	/**
	 * 解绑银行卡
	 */
	public function bankCardUnbind($post) {
		$post['P1_bizType'] = 'BankCardUnbind';
		$post['P2_customerNumber'] = HlPayConfig::$mchid;

    	if (!isset($post['P1_bizType'])) {
    		$this->retJson('缺少交易类型', -1001);
    	}

    	if (!isset($post['P2_customerNumber'])) {
    		$this->retJson('缺少商户编号', -1002);
    	}


    	if (!isset($post['P3_userId'])) {
    		$this->retJson('缺少用户ID', -1003);
    	}

		if (!isset($post['P4_bindId'])) {
    		$this->retJson('缺少绑定ID', -1003);
    	}
		if (!isset($post['P5_orderId'])) {
    		$this->retJson('缺少订单号', -1003);
    	}

    	// $post['P6_timestamp'] = date('YmdHis',time());

    	if (!isset($post['P5_validateCode'])) {
    		$this->retJson('缺少短信验证码', -1004);
    	}
		$params = [
			'P1_bizType'	=> $post['P1_bizType'],
			'P2_customerNumber'	=> $post['P2_customerNumber'],
			'P3_userId'	=> HlPayConfig::$user_solt.$post['P3_userId'],
			'P4_bindId'	=> $post['P4_bindId'],
			'P5_orderId'	=> $post['P5_orderId'],
			'P6_timestamp'	=> date('YmdHis',time()),
		];

    	//获取加密密钥
		$aes_key = HlpUtils::genKey(128);

    	//使用合利宝公钥进行aesKey的加密
		$encryption_key = HlpUtils::rsaEncrypt( $aes_key );

		$post_arr = $this->dealPostParams($params, $aes_key);

    	//MD5withRSA签名，该方法读取商户证书
    	if (isset($post_arr['ret_post_str'])) {
    		$sign = HlpUtils::sign( $post_arr['ret_post_str'] );
    		unset($post_arr['ret_post_str']);
    	}

    	$post_arr['signatureType'] = 'MD5WITHRSA';
    	// $post_arr['encryption_key'] = $encryption_key;
		$post_arr['sign']          = $sign;

		$result = HlpUtils::post( HlPayConfig::$url, $post_arr );

    	return $this->retJson($result);
	}

    /**
     * 绑卡支付预下单
     * 用户选卡支付时，需预先进行预下单。上送绑定ID和订单信息进行预下单。/////
     */
    public function cardPreOrder($post)
    {
    	$post['P1_bizType'] = 'QuickPayBindPayPreOrderSP';
		$post['P2_customerNumber'] = HlPayConfig::$mchid;

    	if (!isset($post['P1_bizType'])) {
    		$this->retJson('缺少交易类型', -1001);
    	}

    	if (!isset($post['P2_customerNumber'])) {
    		$this->retJson('缺少商户编号', -1002);
    	}

    	//绑卡ID，合利宝生成的唯一绑卡ID
    	if (!isset($post['P3_bindId'])) {
    		$this->retJson('缺少绑卡ID', -1003);
    	}

    	if (!isset($post['P4_userId'])) {
    		$this->retJson('缺少用户id', -1004);
    	}

    	if (!isset($post['P5_orderId'])) {
    		$this->retJson('缺少商户订单号', -1005);
    	}

    	$post['P6_timestamp'] = date('YmdHis',time());

    	// if (!isset($post['P7_currency'])) {
    	// 	$this->retJson('缺少交易币种', -1007);
    	// }

    	if (!isset($post['P8_orderAmount'])) {
    		$this->retJson('缺少交易金额', -1008);
    	}

    	if (!isset($post['P9_goodsName'])) {
    		$this->retJson('缺少商品名称', -1009);
    	}

    	if (!isset($post['P10_goodsDesc'])) {
    		$this->retJson('缺少商品描述', -1010);
    	}


    	if (!isset($post['P11_terminalType'])) {
    		$this->retJson('缺少终端类型', -1011);
    	}

    	if (!isset($post['P12_terminalId'])) {
    		$this->retJson('缺少终端标识', -1012);
    	}

    	$post['P13_orderIp'] = self::getUserIP();

    	if (!isset($post['P14_period']) || empty($post['P14_period'])) {
    		$post['P14_period'] = '';
    	}

    	if (!isset($post['P15_periodUnit']) || empty($post['P15_periodUnit'])) {
    		$post['P15_periodUnit'] = '';
    	}


    	// if (!isset($post['P16_serverCallbackUrl']) || empty($post['P16_serverCallbackUrl'])) {
    	// 	$post['P16_serverCallbackUrl'] = config('helipay_config.order_async_url');
    	// }

		$params = [
			'P1_bizType'	=> $post['P1_bizType'],
			'P2_customerNumber'	=> $post['P2_customerNumber'],
			'P3_bindId'	=> $post['P3_bindId'],
			'P4_userId'	=> HlPayConfig::$user_solt.$post['P4_userId'],
			'P5_orderId'	=> $post['P5_orderId'],
			'P6_timestamp'	=> date('YmdHis',time()),
			'P7_currency'	=> 'CNY',
			// 'P8_orderAmount'	=> $post['P8_orderAmount'],
			'P8_orderAmount'	=> 0.2,
			'P9_goodsName'	=> $post['P9_goodsName'],
			'P10_goodsDesc'	=> '',
			'P11_terminalType'	=> 'IMEI',
			'P12_terminalId'	=> '12345DABF',
			'P13_orderIp'	=> self::getUserIP(),
			'P14_period'	=> '',
			'P15_periodUnit'	=> '',
			'P16_serverCallbackUrl'	=> HlPayConfig::$order_async_url
		];

    	//获取加密密钥
		$aes_key = HlpUtils::genKey(128);

    	//使用合利宝公钥进行aesKey的加密
		$encryption_key = HlpUtils::rsaEncrypt( $aes_key );

		$post_arr = $this->dealPostParams($params, $aes_key);

    	//MD5withRSA签名，该方法读取商户证书
    	if (isset($post_arr['ret_post_str'])) {
    		$sign = HlpUtils::sign( $post_arr['ret_post_str'] );
    		unset($post_arr['ret_post_str']);
    	}

    	////////////////////////////////分账规则,json字符串。待定
    	$post_arr['ruleJson'] = '';


    	// if (!isset($post['splitBillType']) || empty($post['splitBillType'])) {
    	// 	$post_arr['splitBillType'] = '';
    	// }

    	// $post_arr['sendValidateCode'] = true;


    	if (!isset($post['goodsQuantity'])) {
    		$this->retJson('缺少商品数量', -1020);
    	}
    	$post_arr['goodsQuantity'] = $post['goodsQuantity'];

    	// if (!isset($post['userAccount'])) {
    	// 	$this->retJson('缺少用户注册账号', -1021);
    	// }
    	$post_arr['userAccount'] = '';

    	// $post_arr['enrollTime'] = '';
    	// $post_arr['lbs'] = '';

    	// if (!isset($post['appType'])) {
    	// 	$this->retJson('缺少应用类型', -1022);
    	// }
    	$post_arr['appType'] = 'H5';

    	// if (!isset($post['appName'])) {
    	// 	$this->retJson('缺少应用名', -1023);
    	// }
    	$post_arr['appName'] = '安永广州';

    	// if (!isset($post['dealSceneType'])) {
    	// 	$this->retJson('缺少业务场景', -1024);
    	// }
    	$post_arr['dealSceneType'] = 'QUICKPAY';

    	// $post_arr['dealSceneParams'] = '';
    	$post_arr['signatureType']   = 'MD5WITHRSA';
    	$post_arr['encryption_key']   = $encryption_key;
    	$post_arr['sign']			 = $sign;

    	$result = HlpUtils::post( HlPayConfig::$url, $post_arr );

    	return $this->retJson($result);

    }


    /**
     * 绑卡支付短信
     * 用户选卡支付时，需用户短信确认支付。
     * 上送绑卡支付预下单信息进行发送绑卡支付短信
     */
    public function cardMessage($post)
    {
    	$post['P1_bizType'] = 'BindPaySendValidateCode';
		$post['P2_customerNumber'] = HlPayConfig::$mchid;

    	if (!isset($post['P1_bizType'])) {
    		$this->retJson('缺少交易类型', -1001);
    	}

    	if (!isset($post['P2_customerNumber'])) {
    		$this->retJson('缺少商户编号', -1002);
    	}

    	if (!isset($post['P3_orderId'])) {
    		$this->retJson('缺少商户订单号', -1003);
    	}

    	// $post['P4_timestamp'] = date('YmdHis',time());
		$params = [
			'P1_bizType'	=> $post['P1_bizType'],
			'P2_customerNumber'	=> $post['P2_customerNumber'],
			'P3_orderId'	=> $post['P3_orderId'],
			'P4_timestamp'	=> date('YmdHis',time()),
		];

    	//获取加密密钥
		$aes_key = HlpUtils::genKey(128);

    	//使用合利宝公钥进行aesKey的加密
		$encryption_key = HlpUtils::rsaEncrypt( $aes_key );

		$post_arr = $this->dealPostParams($params, $aes_key);

    	//MD5withRSA签名，该方法读取商户证书
    	if (isset($post_arr['ret_post_str'])) {
    		$sign = HlpUtils::sign( $post_arr['ret_post_str'] );
    		unset($post_arr['ret_post_str']);
    	}

    	$post_arr['signatureType'] = 'MD5WITHRSA';
    	$post_arr['encryption_key'] = $encryption_key;
		$post_arr['sign']          = $sign;

		$result = HlpUtils::post( HlPayConfig::$url, $post_arr );

    	return $this->retJson($result);

    }




    /**
     * 绑卡支付
     * 用户选卡支付。上送绑卡支付预下单信息和验证码，进行快速提交支付。
     */
    public function cardConfirmPay($post)
    {
    	$post['P1_bizType'] = 'ConfirmBindPay';
		$post['P2_customerNumber'] = HlPayConfig::$mchid;

    	if (!isset($post['P1_bizType'])) {
    		$this->retJson('缺少交易类型', -1001);
    	}

    	if (!isset($post['P2_customerNumber'])) {
    		$this->retJson('缺少商户编号', -1002);
    	}

    	if (!isset($post['P3_orderId'])) {
    		$this->retJson('缺少商户订单号', -1003);
    	}

    	$post['P4_timestamp'] = date('YmdHis',time());


    	if (!isset($post['P5_validateCode'])) {
    		$this->retJson('缺少验证码', -1004);
    	}
		$params = [
			'P1_bizType'	=> $post['P1_bizType'],
			'P2_customerNumber'	=> $post['P2_customerNumber'],
			'P3_orderId'	=> $post['P3_orderId'],
			'P4_timestamp'	=> date('YmdHis',time()),
			'P5_validateCode'	=> $post['P5_validateCode']
		];

    	//获取加密密钥
		$aes_key = HlpUtils::genKey(128);

    	//使用合利宝公钥进行aesKey的加密
		$encryption_key = HlpUtils::rsaEncrypt( $aes_key );

		$post_arr = $this->dealPostParams($params, $aes_key);

    	//MD5withRSA签名，该方法读取商户证书
    	if (isset($post_arr['ret_post_str'])) {
    		$sign = HlpUtils::sign( $post_arr['ret_post_str'] );
    		unset($post_arr['ret_post_str']);
    	}

    	$post_arr['signatureType'] = 'MD5WITHRSA';
    	$post_arr['encryption_key'] = $encryption_key;
		$post_arr['sign']          = $sign;
		//var_dump($post_arr);exit;

		$result = HlpUtils::post( HlPayConfig::$url, $post_arr );
		return $this->retJson($result);
    }

	/**
	 * 订单查询
	 */
	public function orderQuery($order_sn) {
		
		$params = [
			'P1_bizType'	=> 'QuickPayQuery',
			'P2_orderId'	=> $order_sn,
			'P3_customerNumber'	=> HlPayConfig::$mchid,
		];

    	//获取加密密钥
		$aes_key = HlpUtils::genKey(128);

    	//使用合利宝公钥进行aesKey的加密
		$encryption_key = HlpUtils::rsaEncrypt( $aes_key );

		$post_arr = $this->dealPostParams($params, $aes_key);

    	//MD5withRSA签名，该方法读取商户证书
    	if (isset($post_arr['ret_post_str'])) {
    		$sign = HlpUtils::sign( $post_arr['ret_post_str'] );
    		unset($post_arr['ret_post_str']);
    	}

    	$post_arr['signatureType'] = 'MD5WITHRSA';
    	$post_arr['encryption_key'] = $encryption_key;
		$post_arr['sign']          = $sign;
		//var_dump($post_arr);exit;

		$result = HlpUtils::post( HlPayConfig::$url, $post_arr );
		return $this->retJson($result);
	}


    /**
     * 异步回调地址
     * 将订单支付成功之后，合利宝返回的信息进行入库
     */
    public function orderAsync()
    {
    	$post = Request::post();

    	//判断各项参数有无缺失
    	if (!isset($post['rt1_bizType'])) {
    		$this->retJson('缺少交易类型', -1001);
    	}

    	if (!isset($post['rt2_retCode'])) {
    		$this->retJson('缺少返回码', -1002);
    	}
    	//异步回调失败时写入日志
    	if ($post['rt2_retCode'] != '0000') {
    		Log::record( '异步回调失败>>' . json_encode($post) . PHP_EOL);
    		$this->retJson('异步回调失败', -1002);
    	}

    	if (!isset($post['rt3_retMsg'])) {
    		$this->retJson('缺少返回信息', -1003);
    	}

    	if (!isset($post['rt4_customerNumber'])) {
    		$this->retJson('缺少商户编号', -1004);
    	}

    	if (!isset($post['rt5_orderId'])) {
    		$this->retJson('缺少商户订单号', -1005);
    	}
    	if (!isset($post['rt6_serialNumber'])) {
    		$this->retJson('缺少合利宝交易流水号', -1006);
    	}

    	if (!isset($post['rt8_orderAmount'])) {
    		$this->retJson('缺少订单金额', -1008);
    	}
    	//入库时需要乘以100
    	$post['rt8_orderAmount'] = $post['rt8_orderAmount'] * 100;


    	if (!isset($post['rt9_orderStatus'])) {
    		$this->retJson('缺少订单状态', -1009);
    	}
    	if (!isset($post['rt10_bindId'])) {
    		$this->retJson('缺少合利宝绑定号', -1010);
    	}
    	if (!isset($post['rt11_bankId'])) {
    		$this->retJson('缺少银行编码', -1011);
    	}
    	if (!isset($post['rt12_onlineCardType'])) {
    		$this->retJson('缺少银行卡类型', -1012);
    	}
    	if (!isset($post['rt13_cardAfterFour'])) {
    		$this->retJson('缺少银行卡后四位', -1013);
    	}

    	if (!isset($post['rt14_userId'])) {
    		$this->retJson('缺少用户标识', -1014);
    	}
    	if (!isset($post['sign'])) {
    		$this->retJson('缺少签名', -1015);
    	}
    	$sign = $post['sign'];
    	unset($post['sign']);

    	//验证签名
    	$link_str = HlpUtils::forSign($post);
    	$is_valid = HlpUtils::verify($link_str, $sign);

    	if (!$is_valid) {
    		//$this->retJson('签名错误', -1015);
    	}

    	//先判断有没有入库
    	$UnionPayModel = new UnionPayAsync;
    	$result = $UnionPayModel->getUnionPayInfo($post['rt4_customerNumber'], $post['rt5_orderId']);

    	if (!empty($result)) {
    		$this->retJson('支付信息已入库', -1016);
    	}

    	$ret_bool = $UnionPayModel->insertUnionPay($post);

    	//需要向合利宝响应success
    	if (!$ret_bool) {
    		$this->retJson('异步回调响应失败', -1017);
    	}

    	return json_encode('success');

    }

	/**
	 * 微信小程序支付预下单
	 *
	 * @return void
	 */
	public function wxApiPay($post)
	{
    	$post['P1_bizType'] = 'AppPayApplet';
		$post['P3_customerNumber'] = HlPayConfig::$mchid;

    	if (!isset($post['P1_bizType'])) {
    		$this->retJson('缺少交易类型', -1001);
    	}
		if (!isset($post['P2_orderId'])) {
    		$this->retJson('缺少订单号', -1002);
    	}

    	if (!isset($post['P3_customerNumber'])) {
    		$this->retJson('缺少商户编号', -1002);
    	}
		if (!isset($post['P8_openid'])) {
    		$this->retJson('缺少openid', -1002);
    	}

    	if (!isset($post['P9_orderAmount'])) {
    		$this->retJson('缺少交易金额', -1008);
    	}

    	if (!isset($post['P15_goodsName'])) {
    		$this->retJson('缺少商品名称', -1009);
    	}
		$params = [
			'P1_bizType'	=> $post['P1_bizType'],
			'P2_orderId'	=> $post['P2_orderId'],
			'P3_customerNumber'	=> $post['P3_customerNumber'],
			'P4_payType'	=> 'APPLET',
			'P5_appid'		=> 'wx1d6e18e2ffb93550',
			'P6_deviceInfo'	=> 'WEB',
			'P7_isRaw'		=> '1',
			'P8_openid'		=> $post['P8_openid'],
			// 'P9_orderAmount'	=> $post['P9_orderAmount'],
			'P9_orderAmount'	=> '0.1',
			'P10_currency'		=> 'CNY',
			'P11_appType'		=> 'WXPAY',
			'P12_notifyUrl'		=> '',
			'P13_successToUrl'	=> '',
			'P14_orderIp'		=> self::getUserIP(),
			'P15_goodsName'		=> $post['P15_goodsName'],
			'P16_goodsDetail'	=> '',
			'P17_limitCreditPay'	=> '',
			'P18_desc'		=> '',	// 备注 原样返回
		];
		// dump($params);die;
		

    	//获取加密密钥
		$aes_key = HlpUtils::genKey(128);

    	//使用合利宝公钥进行aesKey的加密
		$encryption_key = HlpUtils::rsaEncrypt( $aes_key );

		$post_arr = $this->dealPostParams($params, $aes_key);

    	//MD5withRSA签名，该方法读取商户证书
    	if (isset($post_arr['ret_post_str'])) {
			// dump($post_arr);die;
    		$sign = HlpUtils::sign( $post_arr['ret_post_str'] );
    		unset($post_arr['ret_post_str']);
    	}
    	$post_arr['signatureType']   = 'MD5WITHRSA';
    	// $post_arr['encryption_key']   = $encryption_key;
    	$post_arr['sign']			 = $sign;
    	$result = HlpUtils::post( HlPayConfig::$url_app, $post_arr );
    	return $this->retJson($result);
    }


}