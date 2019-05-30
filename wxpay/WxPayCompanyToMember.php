<?php
namespace wxpay;

use wxpay\WxCompany;

class WxPayCompanyToMember extends WxCompany
{
    /**
     * @param $openid
     * @param $amount
     * @param $desc
     * @return array
     * 付款到零钱
     */
    public function wxPayToPocket($openid,$order_sn, $amount, $desc)
    {
        $this->payurl = 'https://api.mch.weixin.qq.com/mmpaymkttransfers/promotion/transfers';
        $result       = $this->companyToPocket($openid, $amount,$order_sn, $desc);
        return $this->returnResult($result);

    }

    /**
     * @param $enc_bank_no
     * @param $enc_true_name
     * @param $bank_code
     * @param $amount
     * @param $desc
     * @return array
     * 付款到银行卡
     */
    public function wxPayToBank($enc_bank_no, $enc_true_name, $amount, $desc)
    {
        $this->payurl = 'https://api.mch.weixin.qq.com/mmpaymkttransfers/promotion/transfers';
        $this->setBankData($enc_bank_no, $enc_true_name);
        $result = $this->companyPayToBank($amount, $desc);
        return $this->returnResult($result);

    }

    /**
     * @param $result
     * @return array
     * 反參
     */
    private function returnResult($result)
    {
        if(empty($result['return_code'])){
            $result['is'] = false;
        }else{
            $result['is'] = true;
        }
        return $result;
    }

}