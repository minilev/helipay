<?php

/**
* 	配置账号信息
*/
class HlPayConfig
{
	//=======【基本信息设置】=====================================
	//
	/**
	 * @var string
	 */
	// 把常量改成静态变量
	public static $mchid = 'C1800000002';	// 商户编号
	public static $user_solt = '95555';	// 用户id盐值
	
	//  public static $url    			= 'http://test.trx.helipay.com/trx/quickPayApi/interface.action';//测试环境
	//  public static $pfx_cert_path	= '../cert/C1802.pfx';//商户证书.pfx文件路径
	//  public static $pfx_cert_pwd    = '123456';//商户证书.pfx文件密码
	 public static $pub_cert_path	= '../cert/helipay.cer';//平台公钥证书.cer文件路径
	//  public static $order_async_url	= 'http://bfdl.weiyinstudio.com/api/helipay/orderAsync';//支付请求的回调地址 测试
	 public static $url 			= 'http://quickpay.trx.helipay.com/trx/quickPayApi/interface.action';//生产环境
	 public static $url_app 			= 'http://pay.trx.helipay.com/trx/app/interface.action';//生产环境
	 public static $pfx_cert_path	= '../cert/C1802.pfx';//商户证书.pfx文件路径 生产
	 public static $pfx_cert_pwd    = '12345';//商户证书.pfx文件密码 生产
	 public static $order_async_url	= '';//支付请求的回调地址 生产
	
	
	
	public static function getBankInfo(){
		$data = [
			'ICBC' => ['bankId'=> 'ICBC','bankName'=>'工商银行','supportType'=>['DEBIT']],
			'ABC' => ['bankId'=> 'ABC','bankName'=>'农业银行','supportType'=>['DEBIT']],
			'BOC' => ['bankId'=> 'BOC','bankName'=>'中国银行','supportType'=>['DEBIT']],
			'CCB' => ['bankId'=> 'CCB','bankName'=>'建设银行','supportType'=>['DEBIT']],
			'CMBCHINA' => ['bankId'=> 'CMBCHINA','bankName'=>'招商银行','supportType'=>['DEBIT']],
			'POST' => ['bankId'=> 'POST','bankName'=>'邮政储蓄','supportType'=>['DEBIT']],
			'ECITIC' => ['bankId'=> 'ECITIC','bankName'=>'中信银行','supportType'=>['DEBIT']],
			'CEB' => ['bankId'=> 'CEB','bankName'=>'光大银行','supportType'=>['DEBIT']],
			'BOCO' => ['bankId'=> 'BOCO','bankName'=>'交通银行','supportType'=>['DEBIT']],
			'CIB' => ['bankId'=> 'CIB','bankName'=>'兴业银行','supportType'=>['DEBIT']],
			'CMBC' => ['bankId'=> 'CMBC','bankName'=>'民生银行','supportType'=>['DEBIT']],
			'PINGAN' => ['bankId'=> 'PINGAN','bankName'=>'平安银行','supportType'=>['DEBIT']],
			'CGB' => ['bankId'=> 'CGB','bankName'=>'广发银行','supportType'=>['DEBIT']],
			'BCCB' => ['bankId'=> 'BCCB','bankName'=>'北京银行','supportType'=>['DEBIT']],
			'HXB' => ['bankId'=> 'HXB','bankName'=>'华夏银行','supportType'=>['DEBIT']],
			'SPDB' => ['bankId'=> 'SPDB','bankName'=>'浦发银行','supportType'=>['DEBIT']],
			'SHB' => ['bankId'=> 'SHB','bankName'=>'上海银行','supportType'=>['DEBIT']],
		];
		return $data;
	}

}