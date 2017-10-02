<?php 
namespace wxpay;

use wxpay\lib\Config\WxPayConfig;
use wxpay\lib\Api\WxPayApi;
use wxpay\realize\NativePay;
use wxpay\lib\Data\WxPayUnifiedOrder;

class Wxpay{

	public function __construct(){
		ini_set('date.timezone','Asia/Shanghai');
		WxPayConfig::init();
	}
    
    public function qrcode($text){
        error_reporting(E_ERROR);
        require_once 'phpqrcode.php';
        \QRcode::png($text);
        exit();
    }
    
	public function qrcodePay($title = '订单名称', $money = 1, $notifyUrl, $returnBase = true, $orderID = false, $time = 300, $tag = 'www.pixelgm.com') {
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
}