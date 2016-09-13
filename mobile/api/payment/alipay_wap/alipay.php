<?php
/**
 * 支付接口
 * 好商城V3-B10 haoid.cn
 *
 */
defined('InShopNC') or exit('Access Invalid!');


require_once __DIR__ . "/lib/alipay_submit.class.php";

class alipay {

    public function submit($params)
    {
        require_once __DIR__ . "/alipay.config.php";
        // dd($params, $alipay_config);
        /**************************请求参数**************************/
        //商户订单号，商户网站订单系统中唯一订单号，必填
        $out_trade_no = $param['order_sn'].'-'.$param['order_type'];

        //订单名称，必填
        $subject = $param['order_sn'];

        //付款外币币种，必填
        $currency = isset($alipay_config['currency']) && $alipay_config['currency'] ? $alipay_config['currency'] : 'HKD';

        //付款外币金额，必填
        $total_fee = $param['order_amount'];

        //商品描述，可空
        $body = '';

        //补充aplipay_config
        if(!$alipay_config['partner'] && $param['alipay_partner']) {
            $alipay_config['partner'] = $param['alipay_partner'];
        }
        if(!$alipay_config['key'] && $param['alipay_key']) {
            $alipay_config['key'] = $param['alipay_key'];
        }

        /************************************************************/

        //构造要请求的参数数组，无需改动
        $parameter = array(
                "service"       => $alipay_config['service'],
                "partner"       => $alipay_config['partner'],
                "notify_url"    => $alipay_config['notify_url'],
                "return_url"    => $alipay_config['return_url'],
                "out_trade_no"  => $out_trade_no,
                "subject"   => $subject,
                // "total_fee" => $total_fee,
                "rmb_fee" => $total_fee,
                "body"  => $body,
                "currency" => $currency,
                "_input_charset"    => trim(strtolower($alipay_config['input_charset']))
        );

        //建立请求
        $alipaySubmit = new AlipaySubmit($alipay_config);
        $html_text = $alipaySubmit->buildRequestForm($parameter,"get", "确认");
        // dd($html_text);
        return $html_text;
    }
}
