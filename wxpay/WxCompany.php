<?php
namespace wxpay;

class WxCompany
{
    protected $payurl;
    protected $mch_appid;
    protected $mchid;
    protected $api_cert;
    protected $api_key;
    protected $api_p12;
    protected $nonce_str        = '';
    protected $sign             = '';
    protected $partner_trade_no = '';
    protected $check_name       = 'NO_CHECK';
    protected $amount           = 0;
    protected $desc             = '';
    protected $spbill_create_ip = '';
    protected $signKey          = '';
    protected $config           = '';
    protected $bandData         = '';

 /**
  * mch_appid：绑定支付的APPID（必须配置，开户邮件中可查看）
  * 
  * mchid：商户号（必须配置，开户邮件中可查看）
  * 
  * signKey：商户支付密钥，参考开户邮件设置（必须配置，登录商户平台自行设置）
  * 设置地址：https://pay.weixin.qq.com/index.php/account/api_cert
  * 
 */
    public function __construct()
    {
      
        $this->mch_appid        = "wx2182c35260d41ea5";//公众号appid
        $this->mchid            = "1507253621"; //商户号
        $this->signKey          = 'xiaochengxucqyunwencom45123ksds9';
        //=======【证书路径设置】=====================================
        /**
         * TODO：设置商户证书路径
         * 证书路径,注意应该填写绝对路径（仅退款、撤销订单时需要，可登录商户平台下载，
         * API证书下载地址：https://pay.weixin.qq.com/index.php/account/api_cert，下载之前需要安装商户操作证书）
         * @var path
         */
        $this->api_cert         = __DIR__'/cert/apiclient_cert.pem';
        $this->api_key          = __DIR__'/cert/apiclient_key.pem';
        $this->api_rootca       = __DIR__'/cert/rootca.pem';
        
        $this->nonce_str        = md5(time());
        $this->spbill_create_ip = $this->get_client_ip();
        $this->config           = array(
            "mch_appid" => $this->mch_appid,
            'mchid'     => $this->mchid,
            'api_cert'  => $this->api_cert,
            'api_key'   => $this->api_key,
            'api_p12'   => $this->api_p12,
        );
    }

    /**
     * @param $openid 微信用户openid
     * @param $amount 钱
     * @param $desc 描述
     * @param $ip IP地址
     * @return array|mixed
     */
    protected function companyToPocket($openid, $amount, $order_sn, $desc, $ip=null)
    {
        $this->config    = array(
            "mch_appid" => $this->mch_appid,
            'mchid'     => $this->mchid,
            'api_cert'  => $this->api_cert,
            'api_key'   => $this->api_key,
            'rootca'    => $this->api_rootca,
        );
        $webData         = array(
            "mch_appid"        => $this->mch_appid,
            'mchid'            => $this->mchid,
            'nonce_str'        => $this->nonce_str,
            'partner_trade_no' => empty($order_sn) ? $this->getOrderSn('R') : $order_sn,
            'openid'           => $openid,
            'amount'           => $amount,
            'desc'             => $desc,
            'check_name'       => $this->check_name,
            'spbill_create_ip' => empty($ip) ? $this->spbill_create_ip : $ip,
        );
        $webData['sign'] = $this->createSine($webData);
        return $this->goWxServer($webData);

    }

    /**
     * @param $bankData array('enc_bank_no'=>'','enc_true_name'=>'','bank_code'=>'')
     * @param $enc_bank_no_name 银行卡号 或实名
     * @param $enc_true_name_code 实名或银行编号
     */
    protected function setBankData($bankData, $enc_bank_no_name)
    {
        if (!is_array($bankData) && $enc_bank_no_name == '') {
            return array('state' => 0, 'msg' => "参数错误");
        }
        if (is_array($bankData)) {
            $this->bandData = $bankData;
        }
        if (is_string($bankData) && is_int($bankData) && !is_array($bankData) && $enc_bank_no_name != '') {
            $bankData              = array(
                'enc_bank_no'   => $bankData,
                'enc_true_name' => $enc_bank_no_name,
            );
            $bankData['bank_code'] = $this->getBankCode($bankData['enc_true_name']);
            $this->bandData        = $bankData;
        }

    }

    /**
     * @param $amount
     * @param $desc
     * @return array|mixed
     */
    protected function companyPayToBank($amount, $desc)
    {

        $webData         = array(
            "mch_appid"        => $this->mch_appid,
            'mchid'            => $this->mchid,
            'nonce_str'        => $this->nonce_str,
            'partner_trade_no' => $this->getOrderSn('R'),
            'amount'           => $amount,
            'desc'             => $desc,
        );
        $webData         = array_merge($webData, $this->bandData);
        $webData['sign'] = $this->createSine($webData);
        return $this->goWxServer($webData);
    }

    /**
     *
     *
     * 1056
     */
    private function getBankCode($bank_name)
    {
        switch ($bank_name) {
            case  "工商银行":
                return 1002;
                break;
            case  "农业银行":
                return 1005;
                break;
            case  "中国银行":
                return 1026;
                break;
            case  "建设银行":
                return 1003;
                break;
            case  "招商银行":
                return 1001;
                break;
            case  "邮储银行":
                return 1066;
                break;
            case  "交通银行":
                return 1020;
                break;
            case  "浦发银行":
                return 1004;
                break;
            case  "民生银行":
                return 1006;
                break;
            case  "兴业银行":
                return 1009;
                break;
            case  "平安银行":
                return 1010;
                break;
            case  "中信银行":
                return 1021;
                break;
            case  "华夏银行":
                return 1025;
                break;
            case  "广发银行":
                return 1027;
                break;
            case  "光大银行":
                return 1022;
                break;
            case  "北京银行":
                return 1032;
                break;
            case  "宁波银行":
                return 1056;
                break;
        }
    }

    public function goWxServer($webData)
    {
        $this->config = array(
            "mch_appid" => $this->mch_appid,
            'mchid'     => $this->mchid,
            'api_cert'  => $this->api_cert,
            'api_key'   => $this->api_key,
            'rootca'    => $this->api_rootca,
        );
        $wGet         = $this->array2xml($webData);
        $res          = $this->http_post($this->payurl, $wGet, $this->config);
        if (!$res) {
            return array('status' => 0, 'msg' => "Can't connect the server");
        }
        libxml_disable_entity_loader(true);
        $content = json_decode(json_encode(simplexml_load_string($res, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        if ($content['return_code'] == 'FAIL') {
            return array('status' => 0, 'msg' => $content['return_msg']);
        }
        if ($content['result_code'] == 'FAIL') {
            return array('status' => 0, 'msg' => $content['err_code'] . ':' . $content['err_code_des']);
        }
        return $content;
    }

    public function createSine($data)
    {
        $tArr = '';
        foreach ($data as $k => $v) {
            $tArr[] = $k . "=" . $v;
        }
        sort($tArr);
        $sign = implode($tArr, "&");
        $sign .= "&key=" . $this->signKey;
        return strtoupper(md5($sign));
    }

    private function array2xml($arr, $level = 1)
    {
        $s = $level == 1 ? "<xml>" : '';
        foreach ($arr as $tagname => $value) {
            if (is_numeric($tagname)) {
                $tagname = $value['TagName'];
                unset($value['TagName']);
            }
            if (!is_array($value)) {
                $s .= "<{$tagname}>" . (!is_numeric($value) ? '<![CDATA[' : '') . $value . (!is_numeric($value) ? ']]>' : '') . "</{$tagname}>";
            } else {
                $s .= "<{$tagname}>" . $this->array2xml($value, $level + 1) . "</{$tagname}>";
            }
        }
        $s = preg_replace("/([\x01-\x08\x0b-\x0c\x0e-\x1f])+/", ' ', $s);
        return $level == 1 ? $s . "</xml>" : $s;

    }

    /**
     * 获取客户端IP地址
     * @param integer $type 返回类型 0 返回IP地址 1 返回IPV4地址数字
     * @return mixed
     */
    private function get_client_ip($type = 0)
    {
        $type = $type ? 1 : 0;
        static $ip = NULL;
        if ($ip !== NULL) return $ip[$type];
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $arr = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $pos = array_search('unknown', $arr);
            if (false !== $pos) unset($arr[$pos]);
            $ip = trim($arr[0]);
        } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        // IP地址合法验证
        $long = sprintf("%u", ip2long($ip));
        $ip   = $long ? array($ip, $long) : array('0.0.0.0', 0);
        return $ip[$type];
    }

    /**
     * @param string $str
     * @return string
     * 唯一码
     */
    private function getOrderSn($str = '')
    {
        $order_id_main = date('YmdHis') . rand(100, 999);
        $order_id_len  = strlen($order_id_main);
        $order_id_sum  = 0;
        for ($i = 0; $i < $order_id_len; $i++) {
            $order_id_sum += (int)(substr($order_id_main, $i, 1));
        }
        $order_sn = $order_id_main . str_pad((100 - $order_id_sum % 100) % 100, 2, '0', STR_PAD_LEFT);
        return $str . $order_sn;
    }

    /**
     * @param $url
     * @param $param
     * @param $config
     * @return bool|mixed
     * post 请求；
     */
    private function http_post($url, $param, $config)
    {
        $oCurl = curl_init();
        if (stripos($url, "https://") !== FALSE) {
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYHOST, FALSE);
        }
        if (is_string($param)) {
            $strPOST = $param;
        } else {
            $aPOST = array();
            foreach ($param as $key => $val) {
                $aPOST[] = $key . "=" . urlencode($val);
            }
            $strPOST = join("&", $aPOST);
        }
        curl_setopt($oCurl, CURLOPT_URL, $url);
        curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($oCurl, CURLOPT_POST, true);
        curl_setopt($oCurl, CURLOPT_POSTFIELDS, $strPOST);
        if ($config) {
            curl_setopt($oCurl, CURLOPT_SSLCERT, $config['api_cert']);
            curl_setopt($oCurl, CURLOPT_SSLKEY, $config['api_key']);
            curl_setopt($oCurl, CURLOPT_CAINFO, $config['rootca']);
        }
        $sContent = curl_exec($oCurl);
        $aStatus  = curl_getinfo($oCurl);
        curl_close($oCurl);
        if (intval($aStatus["http_code"]) == 200) {
            return $sContent;
        } else {
            return false;
        }
    }

}