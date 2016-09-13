<?php
/**
 * 支付入口 v3-b12
 *
 *
 **by 好商城V3 www.haoid.cn 运营版*/


defined('InShopNC') or exit('Access Invalid!');

class paymentControl extends BaseHomeControl{

    public function __construct() {
        //向前兼容
        $_GET['extra_common_param'] = str_replace(array('predeposit','product_buy'),array('pd_order','real_order'),$_GET['extra_common_param']);
        $_POST['extra_common_param'] = str_replace(array('predeposit','product_buy'),array('pd_order','real_order'),$_POST['extra_common_param']);
    }

	/**
	 * 实物商品订单
	 */
	public function real_orderOp(){
        Log::record("实物订单下单逻辑");
	    $pay_sn = $_POST['pay_sn'];
		$payment_code = $_POST['payment_code'];
        $url = 'index.php?act=member_order';

        if(!preg_match('/^\d{18}$/',$pay_sn)){
            showMessage('参数错误','','html','error');
        }

        //获取支付方式参数
        $logic_payment = Logic('payment');
        $result = $logic_payment->getPaymentInfo($payment_code);
        if(!$result['state']) {
            showMessage($result['msg'], $url, 'html', 'error');
        }
        $payment_info = $result['data'];

        //计算所需支付金额等支付单信息
        $result = $logic_payment->getRealOrderInfo($pay_sn, $_SESSION['member_id']);
        if(!$result['state']) {
            showMessage($result['msg'], $url, 'html', 'error');
        }

        if ($result['data']['api_pay_state'] || empty($result['data']['api_pay_amount'])) {
            showMessage('该订单不需要支付', $url, 'html', 'error');
        }

        //转到第三方API支付
        $this->_api_pay($result['data'], $payment_info);
	}

	/**
	 * 虚拟商品购买
	 */
	public function vr_orderOp(){
	    $order_sn = $_POST['order_sn'];
	    $payment_code = $_POST['payment_code'];
	    $url = 'index.php?act=member_vr_order';

	    if(!preg_match('/^\d{18}$/',$order_sn)){
            showMessage('参数错误','','html','error');
        }

        $logic_payment = Logic('payment');
        $result = $logic_payment->getPaymentInfo($payment_code);
        if(!$result['state']) {
            showMessage($result['msg'], $url, 'html', 'error');
        }
        $payment_info = $result['data'];

        //计算所需支付金额等支付单信息
        $result = $logic_payment->getVrOrderInfo($order_sn, $_SESSION['member_id']);
        if(!$result['state']) {
            showMessage($result['msg'], $url, 'html', 'error');
        }

        if ($result['data']['order_state'] != ORDER_STATE_NEW || empty($result['data']['api_pay_amount'])) {
            showMessage('该订单不需要支付', $url, 'html', 'error');
        }

        //转到第三方API支付
        $this->_api_pay($result['data'], $payment_info);
	}

	/**
	 * 预存款充值
	 */
	public function pd_orderOp(){
	    $pdr_sn = $_POST['pdr_sn'];
	    $payment_code = $_POST['payment_code'];
	    $url = 'index.php?act=predeposit';

	    if(!preg_match('/^\d{18}$/',$pdr_sn)){
	        showMessage('参数错误',$url,'html','error');
	    }

	    $logic_payment = Logic('payment');
	    $result = $logic_payment->getPaymentInfo($payment_code);
	    if(!$result['state']) {
	        showMessage($result['msg'], $url, 'html', 'error');
	    }
	    $payment_info = $result['data'];

        $result = $logic_payment->getPdOrderInfo($pdr_sn,$_SESSION['member_id']);
        if(!$result['state']) {
            showMessage($result['msg'], $url, 'html', 'error');
        }
        if ($result['data']['pdr_payment_state'] || empty($result['data']['api_pay_amount'])) {
            showMessage('该充值单不需要支付', $url, 'html', 'error');
        }

	    //转到第三方API支付
	    $this->_api_pay($result['data'], $payment_info);
	}

	/**
	 * 第三方在线支付接口 v3-b12
	 *
	 */
	private function _api_pay($order_info, $payment_info) {
        Log::record("调用第三方支付API进行支付");
    	$payment_api = new $payment_info['payment_code']($payment_info,$order_info);
    	if($payment_info['payment_code'] == 'chinabank') {
    		$payment_api->submit();
        } elseif ($payment_info['payment_code'] == 'wxpay') {
            if (!extension_loaded('curl')) {
                showMessage('系统curl扩展未加载，请检查系统配置', '', 'html', 'error');
            }
            Tpl::setDir('buy');
            Tpl::setLayout('buy_layout');
            if (array_key_exists('order_list', $order_info)) {
                Tpl::output('order_list',$order_info['order_list']);
                Tpl::output('args','buyer_id='.$_SESSION['member_id'].'&pay_id='.$order_info['pay_id']);
            } else {
                Tpl::output('order_list',array($order_info));
                Tpl::output('args','buyer_id='.$_SESSION['member_id'].'&order_id='.$order_info['order_id']);
            }
            Tpl::output('api_pay_amount',$order_info['api_pay_amount']);
            Tpl::output('pay_url',base64_encode(encrypt($payment_api->get_payurl(),MD5_KEY)));
            Tpl::output('nav_list', rkcache('nav',true));
            Tpl::showpage('payment.wxpay');
    	} else {
            $redirect_url = $payment_api->get_payurl();
            Log::record("除了微信和网银的支付方式都是直接跳转页面，跳转链接:{$redirect_url}");
    		@header("Location: ".$redirect_url);
    	}
    	exit();
	}

	/**
	 * 通知处理(支付宝异步通知和网银在线自动对账)
	 *
	 */
	public function notifyOp(){
        Log::record('alipay notify start');
        switch ($_GET['payment_code']) {
            case 'alipay':
                $success = 'success'; $fail = 'fail'; break;
            case 'chinabank':
                $success = 'ok'; $fail = 'error'; break;
            default:
                exit();
        }
        $order_type = $_GET['extra_common_param']; //订单类型
        $out_trade_no = $_POST['out_trade_no'];        //系统订单号
        $trade_no = $_POST['trade_no'];                //支付宝订单号
        Log::record("订单参数order_type:{$order_type},out_trade_no:{$out_trade_no},trade_no:{$trade_no}");

		//参数判断
		if(!preg_match('/^\d{18}$/',$out_trade_no)) exit($fail);

		$model_pd = Model('predeposit');
		$logic_payment = Logic('payment');

		if ($order_type == 'real_order') {

		    $result = $logic_payment->getRealOrderInfo($out_trade_no);
		    if (intval($result['data']['api_pay_state'])) {
		        exit($success);
		    }
		    $order_list = $result['data']['order_list'];

	    } elseif ($order_type == 'vr_order'){

	        $result = $logic_payment->getVrOrderInfo($out_trade_no);
	        if ($result['data']['order_state'] != ORDER_STATE_NEW) {
	            exit($success);
	        }

		} elseif ($order_type == 'pd_order') {

		    $result = $logic_payment->getPdOrderInfo($out_trade_no);
		    if ($result['data']['pdr_payment_state'] == 1) {
		        exit($success);
		    }

		} else {
		    exit();
		}
		$order_pay_info = $result['data'];

		//取得支付方式
		$result = $logic_payment->getPaymentInfo($_GET['payment_code']);
		if (!$result['state']) {
            Log::record("订单状态{$result['state']}为错误状态，返回异步通知失败信息");
		    exit($fail);
		}
		$payment_info = $result['data'];

		//创建支付接口对象
		$payment_api	= new $payment_info['payment_code']($payment_info,$order_pay_info);

		//对进入的参数进行远程数据判断
		$verify = $payment_api->notify_verify();
		if (!$verify) {
            Log::record("异步通知验证失败，返回支付方失败信息，一般支付方会再次通过异步通知来确认支付状态");
		    exit($fail);
		}

        //购买商品
		if ($order_type == 'real_order') {
            $result = $logic_payment->updateRealOrder($out_trade_no, $payment_info['payment_code'], $order_list, $trade_no);
		} elseif($order_type == 'vr_order'){
		    $result = $logic_payment->updateVrOrder($out_trade_no, $payment_info['payment_code'], $order_pay_info, $trade_no);
		} elseif ($order_type == 'pd_order') {
		    $result = $logic_payment->updatePdOrder($out_trade_no,$trade_no,$payment_info,$order_pay_info);
		}

		exit($result['state'] ? $success : $fail);
	}

	/**
	 * 支付接口返回
	 *
	 */
	public function returnOp(){
        Log::record("开始支付返回逻辑");
	    $order_type = $_GET['extra_common_param'];
		if ($order_type == 'real_order') {
		    $act = 'member_order';
		} elseif($order_type == 'vr_order') {
			$act = 'member_vr_order';
		} elseif($order_type == 'pd_order') {
		    $act = 'predeposit';
		} else {
		    exit();
		}

		$out_trade_no = $_GET['out_trade_no'];
		$trade_no = $_GET['trade_no'];
		$url = SHOP_SITE_URL.'/index.php?act='.$act;
        Log::record("同步返回参数: 支付宝订单号:{$trade_no}, 订单号:{$out_trade_no}, 订单类型:{$order_type}, url:{$url}");

		//对外部交易编号进行非空判断
		if(!preg_match('/^\d{18}$/',$out_trade_no)) {
		    showMessage('参数错误',$url,'','html','error');
		}

		$logic_payment = Logic('payment');

		if ($order_type == 'real_order') {

		    $result = $logic_payment->getRealOrderInfo($out_trade_no);
            //检查订单状态，是否存在
            Log::record("订单状态:{$result['state']}");
		    if(!$result['state']) {
		        showMessage($result['msg'], $url, 'html', 'error');
		    }
            //检查订单第三方支付状态，是否已经支付成功
            Log::record("订单支付状态:{$result['data']['api_pay_state']}");
		    if ($result['data']['api_pay_state']) {
		        $payment_state = 'success';
		    }
		    $order_list = $result['data']['order_list'];

	    }elseif ($order_type == 'vr_order') {

	        $result = $logic_payment->getVrOrderInfo($out_trade_no);
	        if(!$result['state']) {
	            showMessage($result['msg'], $url, 'html', 'error');
	        }
	        if ($result['data']['order_state'] != ORDER_STATE_NEW) {
	            $payment_state = 'success';
	        }

		} elseif ($order_type == 'pd_order') {

		    $result = $logic_payment->getPdOrderInfo($out_trade_no);
		    if(!$result['state']) {
		        showMessage($result['msg'], $url, 'html', 'error');
		    }
		    if ($result['data']['pdr_payment_state'] == 1) {
		        $payment_state = 'success';
		    }
		}
		$order_pay_info = $result['data'];
		$api_pay_amount = $result['data']['api_pay_amount'];

		if ($payment_state != 'success') {
            Log::record("同步返回时订单支付状态还不是已支付状态");
		    //取得支付方式
		    $result = $logic_payment->getPaymentInfo($_GET['payment_code']);
		    if (!$result['state']) {
		        showMessage($result['msg'],$url,'html','error');
		    }
		    $payment_info = $result['data'];

		    //创建支付接口对象
		    $payment_api	= new $payment_info['payment_code']($payment_info,$order_pay_info);

		    //返回参数判断
		    $verify = $payment_api->return_verify();
		    if(!$verify) {
                Log::record("订单同步返回验证失败");
		        showMessage('支付数据验证失败',$url,'html','error');
		    }

		    //取得支付结果
		    $pay_result	= $payment_api->getPayResult($_GET);
		    if (!$pay_result) {
                Log::record("订单支付状态不是支付成功状态，表示支付失败");
		        showMessage('非常抱歉，您的订单支付没有成功，请您后尝试',$url,'html','error');
		    }

            //更改订单支付状态
		    if ($order_type == 'real_order') {
		        $result = $logic_payment->updateRealOrder($out_trade_no, $payment_info['payment_code'], $order_list, $trade_no);
		    } else if($order_type == 'vr_order') {
		        $result = $logic_payment->updateVrOrder($out_trade_no, $payment_info['payment_code'], $order_pay_info, $trade_no);
		    } else if ($order_type == 'pd_order') {
		        $result = $logic_payment->updatePdOrder($out_trade_no, $trade_no, $payment_info, $order_pay_info);
		    }
		    if (!$result['state']) {
		        showMessage('支付状态更新失败',$url,'html','error');
		    }
		}

		//支付成功后跳转
		if ($order_type == 'real_order') {
		    $pay_ok_url = SHOP_SITE_URL.'/index.php?act=buy&op=pay_ok&pay_sn='.$out_trade_no.'&pay_amount='.ncPriceFormat($api_pay_amount);
		} elseif ($order_type == 'vr_order') {
		    $pay_ok_url = SHOP_SITE_URL.'/index.php?act=buy_virtual&op=pay_ok&order_sn='.$out_trade_no.'&order_id='.$order_pay_info['order_id'].'&order_amount='.ncPriceFormat($api_pay_amount);
		} elseif ($order_type == 'pd_order') {
		    $pay_ok_url = SHOP_SITE_URL.'/index.php?act=predeposit';
		}
        Log::record("支付成功后同步页面跳转链接: {$pay_ok_url}");
        if ($payment_info['payment_code'] == 'tenpay') {
            showMessage('',$pay_ok_url,'tenpay');
        } else {
            redirect($pay_ok_url);
        }
    }

    /**
     * 二维码显示(微信扫码支付) v3-b12
     */
    public function qrcodeOp() {
        $data = base64_decode($_GET['data']);
        $data = decrypt($data,MD5_KEY,30);
        require_once BASE_RESOURCE_PATH.'/phpqrcode/phpqrcode.php';
        QRcode::png($data);
    }

    /**
     * 接收微信请求，接收productid和用户的openid等参数，执行（【统一下单API】返回prepay_id交易会话标识
     */
    public function wxpay_returnOp() {
        $result = Logic('payment')->getPaymentInfo('wxpay');
        if (!$result['state']) {
            Log::record('wxpay not found','RUN');
			
        }
        new wxpay($result['data'],array());
        require_once BASE_PATH.'/api/payment/wxpay/native_notify.php';
    }

    /**
     * 支付成功，更新订单状态
     */
    public function wxpay_notifyOp() {
		
        $result = Logic('payment')->getPaymentInfo('wxpay');
        if (!$result['state']) {
            Log::record('wxpay not found','RUN');
        }
		
        new wxpay($result['data'],array());
        require_once BASE_PATH.'/api/payment/wxpay/notify.php';
    }

    public function query_stateOp() {
		
        if ($_GET['pay_id'] && intval($_GET['pay_id']) > 0) {
            $info = Model('order')->getOrderPayInfo(array('pay_id'=>intval($_GET['pay_id']),'buyer_id'=>intval($_GET['buyer_id'])));
            exit(json_encode(array('state'=>($info['api_pay_state'] == '1'),'pay_sn'=>$info['pay_sn'],'type'=>'r')));
        } elseif (intval($_GET['order_id']) > 0) {
            $info = Model('vr_order')->getOrderInfo(array('order_id'=>intval($_GET['order_id']),'buyer_id'=>intval($_GET['buyer_id'])));
            exit(json_encode(array('state'=>($info['order_state'] == '20'),'pay_sn'=>$info['order_sn'],'type'=>'v')));
        }
    }
}