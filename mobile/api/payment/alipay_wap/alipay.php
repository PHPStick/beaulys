<?php
/**
 * 支付接口
 * 好商城V3-B10 haoid.cn
 *
 */
defined('InShopNC') or exit('Access Invalid!');


require_once __DIR__ . "/lib/alipay_submit.class.php";

class alipay {

    public function submit($param)
    {
        require_once __DIR__ . "/alipay.config.php";
        /**************************请求参数**************************/
        //商户订单号，商户网站订单系统中唯一订单号，必填
        $out_trade_no = $param['order_sn'].'-'.$param['order_type'];

        //订单名称，必填
        $subject = $param['order_sn'];

        //付款外币币种，必填
        $currency = $alipay_config['currency'];

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
                "subject"       => $subject,
                // "total_fee"  => $total_fee,
                "rmb_fee"       => $total_fee,
                "body"          => $body,
                "currency"      => $currency,
                "_input_charset"    => trim(strtolower($alipay_config['input_charset']))
        );
        Log::record("请求参数:" . json_encode($parameter) . ", 支付宝配置信息:" . json_encode($alipay_config));

        //建立请求
        $alipaySubmit = new AlipaySubmit($alipay_config);
        $html_text = $alipaySubmit->buildRequestForm($parameter,"get", "确认");
        // Log::record("创建的支付表单:{$html_text}");
        return $html_text;
    }

    /**
     * 获取return信息
     */
    public function getReturnInfo($payment_config)
    {
        require_once __DIR__ . "/alipay.config.php";
        require_once(BASE_PATH.DS.'api/payment/alipay_wap/lib/alipay_notify.class.php');
        //获取配置
        $payment_config['sign_type'] = $alipay_config['sign_type']; //签名方式
        //计算得出通知验证结果
        $alipayNotify = new AlipayNotify($payment_config);
        $verify_result = $alipayNotify->verifyReturn();
        Log::record("return结果:{$verify_result}");
        if($verify_result) {
            //获取支付宝的通知返回参数，可参考技术文档中页面跳转同步通知参数列表

            //商户订单号

            $out_trade_no = $_GET['out_trade_no'];

            //支付宝交易号

            $trade_no     = $_GET['trade_no'];

            //交易状态
            $trade_status = $_GET['trade_status'];

            Log::record("订单号:{$out_trade_no}, 支付宝交易号:{$trade_no}, 交易状态:{$trade_status}");

            if($trade_status == 'TRADE_FINISHED' || $trade_status == 'TRADE_SUCCESS') {
                return array(
                    //商户订单号
                    'out_trade_no' => $out_trade_no,
                    //支付宝交易号
                    'trade_no'     => $trade_no,
                );
            }
        }

        return false;
    }

    /**
     * 获取notify信息
     */
    public function getNotifyInfo($payment_config)
    {
        require_once(BASE_PATH.DS.'api/payment/alipay_wap/lib/alipay_notify.class.php');
        //计算得出通知验证结果
        $alipayNotify = new AlipayNotify($payment_config);
        $verify_result = $alipayNotify->verifyNotify();
        Log::record("获取notify信息结果:{$verify_result}");

        if($verify_result) {
            //获取支付宝的通知返回参数，可参考技术文档中服务器异步通知参数列表

            //商户订单号

            $out_trade_no = $_POST['out_trade_no'];

            //支付宝交易号

            $trade_no     = $_POST['trade_no'];

            //交易状态
            $trade_status = $_POST['trade_status'];

            Log::record("订单号:{$out_trade_no}, 支付宝交易号:{$trade_no}, 交易状态:{$trade_status}");

            //判断交易状态
            if($trade_status == 'TRADE_FINISHED' || $trade_status == 'TRADE_SUCCESS') {
                return array(
                    //商户订单号
                    'out_trade_no' => $out_trade_no,
                    //支付宝交易号
                    'trade_no'     => $trade_no,
                );
            }
        }

        return false;
    }
}
