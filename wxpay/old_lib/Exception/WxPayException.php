<?php
/**
 * 微信支付类
 * @author Dust <1272294450@qq.com>
 */

namespace wxpay\lib\Exception;

/**
 * 
 * 微信支付API异常类
 * @author widyhu
 *
 */
class WxPayException extends \Exception {
	public function errorMessage()
	{
		return $this->getMessage();
	}
}
