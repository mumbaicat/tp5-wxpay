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

	// 扫码支付 生成二维码  对应上面的操作方法
	public function makeqr() {
		$text = input('text');
		$text = base64_decode($text); //base64
		$pay = new Wxpay();
		$pay->qrcode($text);
	}

	// JSAPI模式，需要设置公众号授权域名、授权回调目录和API目录
	public function jsapi() {
		$pay = new Wxpay();
        $money = 1;
		$res = $pay->jsPay('商品名称', $money * 100, url('notify', '', false, true));
        $this->assign('wxpay',$res);
        $this->assign('money',$money);
		return $this->fetch('js');
	}

	// 小程序 获取openid
	public function getid() {
		$code = input('post.code');
		if (empty($code)) {
			return '未填写code';
		}
		$pay = new Wxpay();
		$info = $pay->getOpenId($code);
		return $info;
	}

	// 小程序 aes解密（获取手机号等）
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

	// 小程序 通过微信获取手机号登陆 例子
    // 比 aes 上多个几个字段
    // openid
    // avator
    // nickname
	// sex
	// 小程序代码参考：
	/*
	// <button open-type="getPhoneNumber" bindgetphonenumber="getPhoneNumber">一键登录</button>
	// 获取用户手机号
	getPhoneNumber: function (e) {
		console.log(e.detail.errMsg)
		console.log(e.detail.iv)
		console.log(e.detail.encryptedData)
		if (e.detail.errMsg == 'getPhoneNumber:fail user deny') {
		wx.showModal({
			title: '提示',
			showCancel: false,
			content: '未授权',
			success: function (res) { }
		})
		} else {
		wx.getUserInfo({
			success: function (res) {
			console.log('获取到的用户数据' + JSON.stringify(res));
			var userInfo = res.userInfo
			var nickName = userInfo.nickName
			var avatarUrl = userInfo.avatarUrl
			var gender = userInfo.gender //性别 0：未知、1：男、2：女
			wx.request({
				url: 'https://xiaochengxu.cqyunwen.com/api/user/wxlogin',
				data: {
				'sessionKey': wx.getStorageSync('session_key'),
				'encryptedData': e.detail.encryptedData,
				'iv': e.detail.iv,
				'openid': wx.getStorageSync('openid'),
				'avator': avatarUrl,
				'nickname': nickName,
				'sex': gender
				},
				method: 'POST',
				success: function (x) {
				console.log(x.data);
				wx.setStorageSync('token', x.data.data.token);
				wx.navigateBack({
					delta: 2
				})
				}
			})
			}
		});
		}
	},
	*/
    public function wxlogin(){
        $jsonData = $this->aes();
        $openid = input('post.openid');
        $avator = input('post.avator');
        $nickName = input('post.nickname');
        $sex = input('post.sex');
        $sessionKey = input('post.sessionKey');
        if(empty($openid) or empty($avator) or empty($nickName) or empty($sex)){
            return make_return_json(500,'提交数据不完整');
        }
        $data = json_decode($jsonData,true);
        if(empty($data['purePhoneNumber'])){
            return make_return_json(500,'登陆失败');
        }
        $phone = $data['purePhoneNumber'];
        $model = UserModel::get(function($query) use($phone){
            $query->where('phone',$phone);
        });
        if(!$model){
            // 用户不存在，注册
        	$model = $this->reg($nickName.'_'.$phone,$phone,str_random(16));
		}
		// 存在可以更新下微信信息
        $avatorPath  = $this->saveImage($avator,$model->id);
        $model->weixin = [
            'openid' => $openid,
            'session_key' => $sessionKey
        ];
        $model->avator = $avatorPath;
        $model->save();
        return make_return_json(200,'success',['token'=>$model->token,'name'=>$model->name]);
    }

	// 小程序 支付
	public function miniPay() {
		// 比如说一个订单表，用其他的 Action 去写插入订单表的，获取订单号，然后带着订单号调用本 Action 去做微信支付
		$openid = input('post.openid');
		$pay = new Wxpay();
		// 参数3回调地址，URL中可以放传递参数，/notify/oid/1
		$order = $pay->miniPay('一分钱的商品', 1, $openid, url('notify', '', false, true));
		if (empty($order['need_nonceStr'])) {
			exit('获取失败,估计配置信息没填写');
		}
		// $orderData->weixin = $order; // 可以把下单信息存进去，$order['prepay_id'] 是微信下单号
		return json_encode($order);
	}

	// 回调处理，微信服务器发来的是POST方式，URL中可以放传递参数，/notify/oid/1
	public function notify() {
		// $pay = new Wxpay();
		// if ($pay->notify()) {
			$fileName = 'wxpay' . date('His', time()) . '.txt';
			$fileData = '支付完成_' . date('Y_m_d_H_i_s', time());
			file_put_contents($fileName, $fileData);
		// }
	}

	// 保存微信头像
    protected function saveImage($url,$uid){
        $ext = strrchr($url.'.jpg','.');
        $data = file_get_contents($url);
        file_put_contents('./uploads/avator/'.$uid.$ext,$data);
        return 'avator/'.$uid.$ext;
    }
}