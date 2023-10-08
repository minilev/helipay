<?php
require_once "Heli.Config.php";
require_once "HlpUtils.php";

class HlpWeixin
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
     * 微信小程序支付预下单
     * 
     */
    public function preOrder($post) {
        $post['P1_bizType'] = 'AppPayApplet';
		$post['P3_customerNumber'] = HlPayConfig::$mchid;
        $post['P4_payType'] = 'APPLET';
        $post['P5_appid'] = HlPayConfig::$appid;
        $post['P8_openid'] = '';

    	if (!isset($post['P1_bizType'])) {
    		$this->retJson('缺少交易类型', -1001);
    	}

    	if (!isset($post['P2_orderId'])) {
    		$this->retJson('缺少订单编号', -1002);
    	}

        if (!isset($post['P3_customerNumber'])) {
    		$this->retJson('缺少商户编号', -1003);
    	}

    	if (!isset($post['P4_payType'])) {
    		$this->retJson('缺少支付类型', -1004);
    	}

    	if (!isset($post['P5_appid'])) {
    		$this->retJson('缺少appid', -1005);
    	}

    	$post['P6_timestamp'] = date('YmdHis',time());

    	if (!isset($post['P8_openid'])) {
    		$this->retJson('缺少openid', -1007);
    	}

    	if (!isset($post['P9_orderAmount'])) {
    		$this->retJson('缺少交易金额', -1008);
    	}

    	if (!isset($post['P15_goodsName'])) {
    		$this->retJson('缺少商品名称', -1009);
    	}


    	// if (!isset($post['P16_serverCallbackUrl']) || empty($post['P16_serverCallbackUrl'])) {
    	// 	$post['P16_serverCallbackUrl'] = config('helipay_config.order_async_url');
    	// }

		$params = [
			'P1_bizType'	=> $post['P1_bizType'],
			'P2_orderId'	=> $post['P2_orderId'],
			'P3_customerNumber'	=> $post['P3_customerNumber'],
            'P4_payType'    => $post['P4_payType'],
            'P5_appid'      => $post['P5_appid'],
            'P8_openid'     => $post['P8_openid'],
            'P9_orderAmount'    => $post['P9_orderAmount'],
            'P10_currency'  => 'CNY',
            'P11_appType'   => 'WXPAY',
            'P12_notifyUrl' => '',
            'P14_orderIp'   => self::getUserIP(),
            'P15_goodsName' => $post['P15_goodsName'],
            'P18_desc'      => '',  //商户备注,原样返回
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

    	// $post_arr['dealSceneParams'] = '';
    	$post_arr['signatureType']   = 'MD5WITHRSA';
    	$post_arr['encryption_key']   = $encryption_key;
    	$post_arr['sign']			 = $sign;

    	$result = HlpUtils::post( HlPayConfig::$url, $post_arr );

    	return $this->retJson($result);
    }
}