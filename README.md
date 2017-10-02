Thinkphp5 命名空间版微信支付  
==========  
作者: [Dust](http://pixelgm.com)  

## 对官方PHPSDK进行改进和封装:  
  * 修复了官方SDK中postxml中SSL版本问题
  * 官方SDK改为namespace版(如要其他框架使用请修改WxPayConfig.php里的config函数)
  * 修改SDK中的大小写错误
  * 修复php5.3之后默认禁用always_populate_raw_post_data(php7中直接去除了此特性)导致的xml获取失败的问题
  * 配置信息昂放在应用目录下的config.php里方便修改
  * 简单的文件结构
  * 一行代码更直接的使用
  * 去掉了内置的LOG
  * JSAPI支付模式
  * 扫码支付模式
  * 小程序支付模式

## 目录结构  
  * wxpay   扩展文件,把这个文件夹放在extends文件夹里
  * application  示例Demo
  * WxpayAPI_php_v3.0.1   微信支付官方的PHPSDK

## 配置
请在配置文件添加配置信息，结构如下（mini_开头为小程序,不需要则不填写）：
 <pre>
'wxpay'=>[
    'appid'=>'your appid',
    'mchid'=>'your mchid',
    'key'=>'your key',
    'appsecret'=>'your appsecret',
    'mini_appid' => 'your mini appid',
    'mini_appsecret'=>'your mini appsecret',
],
</pre>  

## 使用方法
把wxpay文件夹放在extend扩展文件夹里,然后引入.  
<pre>
use wxpay\Wxpay;
</pre>

扫码支付  
<pre>
$pay = new Wxpay();
$url = $pay->qcodePay('商品名称',1,url('notify','',false,true));
$pay->qrcode($url);
</pre>

JSAPI模式支付
<pre>
$pay = new Wxpay();
$res = $pay->jsPay('商品名称',1,url('notify','',false,true));
// res的操作请参考示例赋值到模板
</pre>

小程序支付
<pre>
$openid = input('post.openid');
$pay = new Wxpay();
$res = $pay->miniPay('商品名称',1,url('notify','',false,true),$openid);
return json_encode($res);
// 小程序发起wx.request把openid传过来,经过PHP统一下单,把订单信息返回给小程序wx.requestPayment. 参数都在$res里
</pre>

回调处理  
<pre>
$pay = new Wxpay();
if($pay->notify()){
	// 支付成功处理
}
</pre>


## 其他说明  
微信小程序支付时微信小程序需要的步骤  :  

app.js里onLaunch 通过wx.login凭着code发送wx.request请求到服务器,得到openid和session_key.用setStorageSync保存在本地.  
<pre>
//app.js
App({
    onLaunch: function () {
        // 登录
        wx.login({
            success: res => {
                // 发送 res.code 到后台换取 openId, sessionKey, unionId
                wx.request({
                    url: 'https://xxxxx/index.php/pay/index/getid',
                    data: {
                        'code': res.code,
                    },
                    method: 'POST',
                    success: function (x) {
                        console.log(x.data)
                        wx.setStorageSync('openid', x.data.openid);
                        wx.setStorageSync('session_key', x.data.session_key);
                    }
                })
            }
        })
....
</pre>
然后支付时通过wx.request请求把openid传到服务器里微信小程序支付(miniPay)进行下单,得到订单的信息,然后把部分订单的信息作为参数填写在wx.requestPayment里.
<pre>
wx.request({
          url: 'https://xxx/index.php/pay/index/minipay',
          method:'POST',
          data:{
              'openid': wx.getStorageSync('openid')
          },
          success:function(x){
             wx.requestPayment({
                 timeStamp: x.data.need_timeStamp,
                 nonceStr: x.data.need_nonceStr,
                 package: x.data.need_package,
                 signType: x.data.need_signType,
                 paySign: x.data.need_paySign,
                 'success': function () {
                     wx.showToast({
                         title: '支付OK',
                     })
                 },
                 'fail':function(){
                     wx.showToast({
                         title: '失败',
                     })
                 }
             })
          }
      });
</pre>

## 写在最后
后续会加上退款什么的.  
有什么BUG地方希望能反馈给我.
欢迎start和fork啊 :)