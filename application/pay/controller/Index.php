<?php
namespace app\pay\controller;

use think\Controller;
use wxpay\Wxpay;

class Index extends Controller {

	// 扫码支付 直接显示二维码图片
	public function index() {
		$pay = new Wxpay();
		$res = $pay->qrcodePay('商品名称', 1, url('notify', '', false, true), false); // 第四个参数如果填写为true 则只返回支付链接,否则返回订单全部信息,默认true
		// dump($res);
		$pay->qrcode($res['code_url']);
	}

	// 扫码支付 通过模板显示 需要使用下面的操作方法
	public function pay() {
		$pay = new Wxpay();
		$url = $pay->qrcodePay('商品名称', 1, url('notify', '', false, true));
		$url = base64_encode($url); // base64方便通过URL传输
		$this->assign('url', $url);
		return $this->fetch('pay');
	}

	// 生成二维码  对应上面的操作方法
	public function makeqr() {
		$text = input('text');
		$text = base64_decode($text); //base64
		$pay = new Wxpay();
		$pay->qrcode($text);
	}

	// JSAPI模式
	public function jsapi() {
		$pay = new Wxpay();
		$res = $pay->jsPay('商品名称', 1, url('notify', '', false, true));
		$this->assign('jsApiParameters', $res['jsApiParameters']);
		$this->assign('editAddress', $res['editAddress']);
		return $this->fetch('js');
	}

	// 小程序获取openid
	public function getid() {
		$code = input('post.code');
		if (empty($code)) {
			return '未填写code';
		}
		$pay = new Wxpay();
		$info = $pay->getOpenId($code);
		return $info;
	}

	// 小程序aes解密
	public function aes() {
		$sessionKey = input('post.sessionKey');
		$encryptedData = input('post.encryptedData');
		$iv = input('post.iv');
		if (empty($sessionKey) or empty($encryptedData) or empty($iv)) {
			return '数据不完整';
		}
		$pay = new Wxpay();
		$data = $pay->aes($sessionKey, $encryptedData, $iv);
		return $data;
	}

	// 小程序支付
	public function miniPay() {
		$openid = input('post.openid');
		$pay = new Wxpay();
		$order = $pay->miniPay('一分钱的商品', 1, $openid, url('notify', '', false, true));
		return json_encode($order);
	}

	// 回调处理
	public function notify() {
		$pay = new Wxpay();
		if ($pay->notify()) {
			$fileName = 'wxpay' . date('His', time()) . '.txt';
			$fileData = '支付完成:' . date('Y-m-d H:i:s', time());
			file_put_contents($fileName, $fileData);
		}
	}
}