<?php
require_once "Heli.Config.php";
use think\Env;
use think\Log;

class HlpUtils {

	/**
     *
     * @param string $key_length 所生成密钥的长度
     * @return string
     */
	public static function genKey($key_length) {
		$str = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
		$len = strlen($str)-1;
		$key = '';
		for ($i=0; $i<$key_length; $i++){
			$num = mt_rand(0, $len);
			$key .= $str[$num];
		}
		return $key;
	}



	//构造待签名串
	public static function forSign($ready) {
		$string = "&";
		foreach ($ready as $value) {
		$string.=$value."&"; 
		}
		$string = substr($string,0,-1);
		return $string;
	}


	public static function desEncrypt($data, $deskey){
        if (!empty($data)){
            return base64_encode(openssl_encrypt($data, 'des-ede3', $deskey, OPENSSL_RAW_DATA, $iv = ""));
        }
       return $data;
    }
 
    /**
     * 解密
     * @return string 加密的字符串不是完整的会返回空字符串值
     */
    public static function desDecrypt($data, $deskey){
        if (!empty($data)){
            return openssl_decrypt(base64_decode($data), 'des-ede3', $deskey, OPENSSL_RAW_DATA, $iv = "");
        }
        return $data;
    }


	/**
     *
     * @param string $string 需要加解密的字符串
     * @param string $key 密钥
     * @return string
     */
    public static function aesEncrypt( $string, $key ) {
    	//base64_decode($key);
        $encrypted = openssl_encrypt( $string, 'AES-128-ECB', $key );
        //$encrypted = base64_encode($data);
        return $encrypted;   
          
    }


    public static function aesDecrypt( $string, $key ) {
    	
        $decrypted = openssl_decrypt( $string, 'AES-128-ECB', $key);	    
        return $decrypted;
        
    }


    /**
	 * 加密数据
	 * @param string $data数据
	 * @param string $cert_path 证书配置路径
	 * @return unknown
	 */
	public static function rsaEncrypt( $data ) {

		if( $data != null ) {
			$cert_path = HlPayConfig::$pub_cert_path;

		}
		$publicCertData = file_get_contents ( $cert_path );
		$public_key = openssl_get_publickey($publicCertData);
		openssl_public_encrypt (str_pad($data, 256, "\0", STR_PAD_LEFT), $encrypted, $public_key, OPENSSL_NO_PADDING); //描述不完全准确，但可以大致理解下，就是：PHP与Java的无填充模式加密没有统一的标准，在Java中RSA加密选择NoPadding模式如果数据不足相应的字节长度（和密钥长度有关），会自动填充"\0"将数据字节长度补足，而openssl_public_encrypt并不会帮我们自动做这样的事，所以在这里使用函数str_pad()来应对处理。
		openssl_free_key( $public_key );
		return base64_encode ( $encrypted );
	}


	public static function rsaDecrypt( $data ) {
    	if( $data != null ) {

			$cert_path = HlPayConfig::$pfx_cert_path;
			$cert_pwd  = HlPayConfig::$pfx_cert_pwd;
		}
	    $privateCertData = file_get_contents ( $cert_path );
	        if($privateCertData === false ){

	          return;
	        }
	    openssl_pkcs12_read ( $privateCertData, $certs, $cert_pwd );
	    $private_key = openssl_get_privatekey($certs ['pkey']);
	    openssl_private_decrypt( base64_decode($data), $decrypted, $private_key, OPENSSL_NO_PADDING);
	    openssl_free_key($private_key);
	    //对应加密方法rsaEncrypt()中的说明，需要对解密后的数据做切割，剔除"\0"后返回有效可用的字符串
	    if(strstr($decrypted, "\0") !== false){
	    	$ltrimDecrypted = ltrim($decrypted, "\0");
	    	return $ltrimDecrypted;
	    }
	    else{
	    	return $decrypted;	
	    }    
  	}


  	public static function signWithSha256($signString) {
		      
			  //生成前后包含"sha256Key"的签名字串
			  //$signString = $signKey.",".$data.$signKey;
		      //$signString = (string)$src_sign;
		      $re = hash('sha256', $signString, true);
		      return bin2hex($re);
	} 


	public static function verifyWithSha256($signString, $oriSign) {
		      
		      $re = hash('sha256', $signString, true);
		      $verSign = bin2hex($re);
		      if($verSign == $oriSign) {
		      	return true;
		      }
		      else{
		      	return false;
		      }
		      
	}


  	public static function sign( $signString ) {

		if( $signString != null ) {

			$cert_path = HlPayConfig::$pfx_cert_path;
			$cert_pwd  = HlPayConfig::$pfx_cert_pwd;
		}

	    $privateCertData = file_get_contents ( $cert_path );
	        if($privateCertData === false ){
	          //$logger->LogInfo($certPath . "file_get_contents fail。");
	          return;
	        }
		// dump($privateCertData);
		// dump($cert_pwd);
	    openssl_pkcs12_read ( $privateCertData, $certs, $cert_pwd );
		// dump($certs);
	    $private_key = openssl_get_privatekey($certs['pkey']);
		
	    Log::record( '=====签名报文开始======' . PHP_EOL);

		$result = openssl_sign ( $signString, $signature, $private_key, OPENSSL_ALGO_MD5);

		//$ret_sign = md5($signString . "&" . $private_key);

		openssl_free_key( $private_key );

		if ($result) {
						$signature_base64 = base64_encode ( $signature );
						Log::record( "签名为>>" . $signature_base64 . PHP_EOL);

						} else {

						Log::record( '>>>>>签名失败<<<<<<<' . PHP_EOL);
					}
					Log::record( '=====签名报文结束======' . PHP_EOL);

			return $signature_base64;
			//return $signature;
	}


	public static function verify( $signString, $signature ) {

		if( $signature != null ) {

			$cert_path = HlPayConfig::$pub_cert_path;

		}
		$publicCertData = file_get_contents ( $cert_path );
		$public_key = openssl_get_publickey($publicCertData);
		$isValid = openssl_verify( $signString, base64_decode ($signature), $public_key, OPENSSL_ALGO_MD5 );
		openssl_free_key( $public_key );
		if($isValid = 'true') {
			Log::record( '返回参数验签成功' . PHP_EOL);
			return "true";			
		}else {
			Log::record( '返回参数验签失败' . PHP_EOL);
        	return "false";      
		} 
	}


	public static function json_validate( $string ) {
		if(is_string($string)){
			$json = json_decode($string);
	        if (json_last_error() === JSON_ERROR_NONE) { 
	            return $json;
	            //return (json_last_error() === JSON_ERROR_NONE);
	        }
    	}
        return false;
    }


    public static function createLinkString($para, $sort, $encode) {
		if($para == NULL || !is_array($para))
			return "";
		
		$linkString = "";
		if ($sort) {
			$para = argSort ( $para );
		}
		while ( list ( $key, $value ) = each ( $para ) ) {
			if ($encode) {
				$value = urldecode ( $value );
			}
			$linkString .= $key . "=" . $value . "&";
		}
		// 去掉最后一个&字符
		$linkString = substr ( $linkString, 0, -1 );
		
		return $linkString;
	}

	public static function enCodeFileContent4Zhrz($path){
		$file_content_base64 = '';
		if(!file_exists($path)){
			echo '文件没找到';
			return false;
		}

		$file_content = file_get_contents ( $path );
		//UTF8 去掉文本中的 bom头
		$BOM = chr(239).chr(187).chr(191);
		$file_content = str_replace($BOM,'',$file_content);
		$file_content_base64 = base64_encode ( $file_content );
		return $file_content_base64;
	}



    /**
	 * 后台交易 HttpClient通信
	 *
	 * @param unknown_type $params
	 * @param unknown_type $url
	 * @return mixed
	 */
	static function post( $url, $params) {

		$opts = json_encode( $params, JSON_UNESCAPED_SLASHES );
		$post_data = http_build_query($params);

		Log::record( "后台请求地址为>" . $url );
		Log::record( "后台请求报文为>" . $post_data );

		$ch = curl_init ();
		curl_setopt ( $ch, CURLOPT_URL, $url );
		curl_setopt ( $ch, CURLOPT_POST, 1 );
		curl_setopt ( $ch, CURLOPT_SSL_VERIFYPEER, false ); // 不验证证书
		curl_setopt ( $ch, CURLOPT_SSL_VERIFYHOST, false ); // 不验证HOST
		curl_setopt ( $ch, CURLOPT_SSLVERSION, 1 ); // http://php.net/manual/en/function.curl-setopt.php页面搜CURL_SSLVERSION_TLSv1
		curl_setopt ( $ch, CURLOPT_HTTPHEADER, array (
				'Content-type:application/x-www-form-urlencoded' 
				) );
				curl_setopt ( $ch, CURLOPT_POSTFIELDS, $post_data );
				curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, true );
				$html = curl_exec ( $ch );//var_dump($html);exit;

				Log::record( "后台返回结果为>" . $html );

				if(curl_errno($ch)){
					$errmsg = curl_error($ch);
					curl_close ( $ch );

					Log::record( "请求失败，报错信息>" . $errmsg );
					return null;
				}
				$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

				Log::record( "返回码>>>" . $code );

				if( curl_getinfo($ch, CURLINFO_HTTP_CODE) != "200"){
					$errmsg = "http状态=" . curl_getinfo($ch, CURLINFO_HTTP_CODE);
					curl_close ( $ch );

					Log::record( "请求失败，报错信息>" . $errmsg );
					return null;
				}
				curl_close ( $ch );
				return $html;
	}

}



?>