<?php
/**
 * 支付宝通知地址
 *
 * 
 * by 33hao 好商城V3  www.haoid.cn 开发
 */
$_GET['act']	= 'payment';
$_GET['op']		= 'notify';
$_GET['payment_code'] = 'alipay';
$_GET['extra_common_param'] = 'pd_order';
require_once(dirname(__FILE__).'/../../../index.php');
?>