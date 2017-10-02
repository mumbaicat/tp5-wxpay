Thinkphp5 命名空间版微信支付  
==========  
作者: [Dust](http://pixelgm.com)  

## 对官方PHPSDK进行改进和封装:  
  * 修复了postxml中SSL版本问题
  * 官方SDK改为namespace版(如要其他框架使用请修改WxPayConfig.php里的config函数)
  * 修改SDK中的大小写错误
  * 修复php5.3之后默认禁用always_populate_raw_post_data(php7中直接去除了此特性)导致的xml获取失败的问题
  * 配置信息昂放在应用目录下的config.php里方便修改
  * 简单的文件结构
  * 一行代码更直接的使用
  * 去掉了内置的LOG
  * JSAPI、扫码支付、微信小程序支付

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
