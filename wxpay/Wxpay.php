<?php
namespace wxpay;

use wxpay\lib\Api\WxPayApi;
use wxpay\lib\Config\WxPayConfig;
use wxpay\lib\Data\WxPayUnifiedOrder;


use wxpay\realize\PayNotifyCallBack;
use wxpay\realize\NativePay;
use wxpay\realize\JsApiPay;

class Wxpay {

	/**
	 * 构造方法 初始化时区和配置等
	 */
	public function __construct() {
		ini_set('date.timezone', 'Asia/Shanghai');
		error_reporting(E_ERROR);
		WxPayConfig::init();
	}

	/**
	 * 生成二维码
	 * @param  string $text 二维码字符串
	 * @return void
	 */
	public function qrcode($text) {
		error_reporting(E_ERROR);
		require_once 'phpqrcode.php';
		\QRcode::png($text);
		exit();
	}

	/**
	 * 扫码支付
	 * @param  string  $title      订单名称
	 * @param  integer $money      金额,单位分
	 * @param  string  $notifyUrl  回调地址 url('','',是否伪静态,是否带域名) 函数生成
	 * @param  boolean $returnBase 是否直接返回支付链接字符串用于二维码生成  默认是
	 * @param  boolean $orderID    商户订单ID,默认自动生成
	 * @param  integer $time       有效时间,单位秒,默认五分钟
	 * @param  string  $tag        备注标签
	 * @return mixed
	 */
	public function qrcodePay($title, $money , $notifyUrl, $returnBase = true, $orderID = false, $time = 300, $tag = 'www.pixelgm.com') {
		$notify = new NativePay();
		$input = new WxPayUnifiedOrder();
		$input->SetBody($title);
		$input->SetAttach($title);
		if ($orderID == false) {
			$input->SetOut_trade_no(WxPayConfig::$appid . time() . mt_rand(1000, 9999));
		} else {
			$input->SetOut_trade_no($orderID);
		}
		$input->SetTotal_fee($money);
		$input->SetTime_start(date("YmdHis"));
		$input->SetTime_expire(date("YmdHis", time() + $time)); //有效期最少5分钟
		$input->SetGoods_tag($tag);
		$url = $notifyUrl;
		$input->SetNotify_url($url);
		$input->SetTrade_type("NATIVE");
		$input->SetProduct_id("100");
		$result = $notify->GetPayUrl($input);
		if ($returnBase == true) {
			$url2 = $result["code_url"];
			return $url2;
		} else {
			return $result;
		}
	}
	/**
	 * 小程序支付
	 * @param  string  $title      商品标题
	 * @param  integer $money      金额,单位分
	 * @param  integer $openId   用户的openID
	 * @param  string  $notifyUrl  回调地址
	 * @param  boolean $orderID    商户订单ID
	 * @param  string  $tag        标签,没卵用
	 * @return array              小程序订单信息
	 */
	public function miniPay($title = '小程序支付', $money = 1, $openId, $notifyUrl, $orderID = false, $tag = 'www.pixelgm.com') {
		WxPayConfig::mini_init();
		$input = new WxPayUnifiedOrder();
		$input->SetBody($title);
		$input->SetAttach($title);
		if ($orderID == false) {
			$input->SetOut_trade_no(WxPayConfig::$appid . time() . mt_rand(1000, 9999));
		} else {
			$input->SetOut_trade_no($orderID);
		}
		$input->SetTotal_fee($money);
		$input->SetTime_start(date("YmdHis"));
		$input->SetTime_expire(date("YmdHis", time() + $time)); //有效期最少5分钟
		$input->SetGoods_tag($tag);
		$url = $notifyUrl;
		$input->SetNotify_url($url);
		$input->SetTrade_type('JSAPI');
		$input->SetOpenid($openId);
		$order = WxPayApi::unifiedOrder($input);
		if ($order['result_code'] == 'SUCCESS' && $order['return_code'] == 'SUCCESS') {
			$order['timeStamp'] = (string) $_SERVER['REQUEST_TIME'];
			$order['package'] = 'prepay_id=' . $order['prepay_id'];
			$order['paySign'] = md5('appId=' . config('wxpay.appid') . '&nonceStr=' . $order['nonce_str'] . '&package=' . $order['package'] . '&signType=MD5&timeStamp=' . $order['timeStamp'] . '&key=' . config('wxpay.key'));
			$order['signType'] = 'MD5';
		}
		$order['need_timeStamp']=$order['timeStamp'];
		$order['need_nonceStr']=$order['nonce_str'];
		$order['need_package']=$order['package'];
		$order['need_signType']=$order['signType'];
		$order['need_paySign']=$order['paySign'];
		return $order;
	}

	/**
	 * JS模式 (需要在后台设置白名单)
	 * @param  string  $title     商品标题
	 * @param  integer $money     商品金额,单位分
	 * @param  string  $notifyUrl 回调地址
	 * @param  string  $orderID  商户单号  默认false自动生成
	 * @param  integer $time      有效期,单位秒
	 * @param  string  $tag       标签,没卵用
	 * @return array             所有信息,最后两个用于赋值给模板
	 */
	public function jsPay($title = '订单名称', $money = 1, $notifyUrl, $orderID = false, $time = 300, $tag = 'www.pixelgm.com') {
		$tools = new JsApiPay();
		$openId = $tools->GetOpenid();
		$input = new WxPayUnifiedOrder();
		$input->SetBody($title);
		$input->SetAttach($title);
		if ($orderID == false) {
			$input->SetOut_trade_no(WxPayConfig::$appid . time() . mt_rand(1000, 9999));
		} else {
			$input->SetOut_trade_no($orderID);
		}
		$input->SetTotal_fee($money);
		$input->SetTime_start(date("YmdHis"));
		$input->SetTime_expire(date("YmdHis", time() + $time)); //有效期最少5分钟
		$input->SetGoods_tag($tag);
		$url = $notifyUrl;
		$input->SetNotify_url($url);
		$input->SetTrade_type("JSAPI");
		$input->SetOpenid($openId);
		$order = WxPayApi::unifiedOrder($input);

		$return = $order;
		$return['jsApiParameters'] = $tools->GetJsApiParameters($order);
		$return['editAddress'] = $tools->GetEditAddressParameters();
		return $return;
	}

	/**
	 * 回调处理 检测是否支付成功
	 * @return bool 支付成功返回真
	 * $result => {"return_code":"SUCCESS","return_msg":"OK"}
	 */
	public function notify() {
		// error_reporting(E_ERROR);
		$notify = new PayNotifyCallBack();
		$notify->Handle(false);
		$result = $notify->GetValues();
		if ($result['return_code'] == 'SUCCESS') {
			return true;
		}
		return false;
	}

	/**
	 * 微信小程序获取用户的OPENID
	 * @param  integer $code 临时code
	 * @return json       微信服务返回来的东西
	 */
	public function getOpenId($code) {
		// $code = input('post.code');
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, 'https://api.weixin.qq.com/sns/jscode2session?appid='.config('wxpay.mini_appid').'&secret='.config('wxpay.mini_appsecret').'&js_code=' . $code . '&grant_type=authorization_code');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		$output = curl_exec($ch);
		curl_close($ch);
		return $output;
	}
}