<?php
/**
 * 支付宝接口类
 *
 * 
 * by 33hao 好商城V3  www.haoid.cn 开发
 */
defined('InShopNC') or exit('Access Invalid!');

class alipay{
	/**
	 *支付宝网关地址（新）
	 */
	private $alipay_gateway_new = 'https://mapi.alipay.com/gateway.do?';     //正式地址
    // private $alipay_gateway_new = 'https://openapi.alipaydev.com/gateway.do?';  //测试地址
	/**
	 * 消息验证地址
	 *
	 * @var string
	 */
	private $alipay_verify_url = 'https://mapi.alipay.com/gateway.do?service=notify_verify&';
	/**
	 * 支付接口标识
	 *
	 * @var string
	 */
    private $code      = 'alipay';
    /**
	 * 支付接口配置信息
	 *
	 * @var array
	 */
    private $payment;
     /**
	 * 订单信息
	 *
	 * @var array
	 */
    private $order;
    /**
	 * 发送至支付宝的参数
	 *
	 * @var array
	 */
    private $parameter;
    /**
     * 订单类型
     * @var unknown
     */
    private $order_type;

    /**
     * 支付接口类型，forex:海外接口/domestic:国内支付宝接口
     * @var [string]
     */
    private $payment_api_type;

    const ALIPAY_FOREX = 'forex';         //海外支付类型
    const ALIPAY_DOMESTIC = 'domestic';   //国内支付类型

    const DEFAULT_FOREX_CURRENCY = 'HKD'; //默认海外支付货币

    public function __construct($payment_info = array(),$order_info = array()){
    	if (!extension_loaded('openssl')) $this->alipay_verify_url = 'http://notify.alipay.com/trade/notify_query.do?';
    	if(!empty($payment_info) and !empty($order_info)){
    		$this->payment	= $payment_info;
    		$this->order	= $order_info;
            $this->payment_api_type = (isset($payment_info['payment_api_type']) && !empty($payment_info['payment_api_type'])) ? $payment_info['payment_api_type'] : self::ALIPAY_FOREX;
    	}
    }

    /**
     * 获取支付接口的请求地址
     *
     * @return string
     */
    public function get_payurl(){
        Log::record("获取支付宝支付跳转链接");
        switch ($this->payment_api_type) {
            case self::ALIPAY_FOREX:
            case self::ALIPAY_DOMESTIC:
                $this->parameter = call_user_func([$this, "get_{$this->payment_api_type}_pay_params"]);
                break;
            default:
                die("支付宝暂不支持其余支付接口");
                break;
        }
        $this->parameter['sign']	= $this->sign($this->parameter);
        return $this->create_url();
    }

    /**
     * 获取国内支付接口的请求地址
     *
     * @return string
     */
    public function get_domestic_pay_params()
    {
        return array(
            'service'           => 'create_direct_pay_by_user', //服务名
            'partner'           => $this->payment['payment_config']['alipay_partner'],  //合作伙伴ID
            'key'               => $this->payment['payment_config']['alipay_key'],
            '_input_charset'    => CHARSET,                 //网站编码
            'notify_url'        => SHOP_SITE_URL."/api/payment/alipay/notify_url.php",  //通知URL
            'sign_type'         => 'MD5',               //签名方式
            'return_url'        => SHOP_SITE_URL."/api/payment/alipay/return_url.php",  //返回URL
            'extra_common_param'=> $this->order['order_type'],
            'subject'           => $this->order['subject'], //商品名称
            'body'              => $this->order['pay_sn'],  //商品描述
            'out_trade_no'      => $this->order['pay_sn'],      //外部交易编号
            'payment_type'      => 1,                           //支付类型
            'logistics_type'    => 'EXPRESS',                   //物流配送方式：POST(平邮)、EMS(EMS)、EXPRESS(其他快递)
            'logistics_payment' => 'BUYER_PAY',                  //物流费用付款方式：SELLER_PAY(卖家支付)、BUYER_PAY(买家支付)、BUYER_PAY_AFTER_RECEIVE(货到付款)
            'receive_name'      => $_SESSION['member_name'],//收货人姓名
            'receive_address'   => 'N', //收货人地址
            'receive_zip'       => 'N', //收货人邮编
            'receive_phone'     => 'N',//收货人电话
            'receive_mobile'    => 'N',//收货人手机
            'seller_email'      => $this->payment['payment_config']['alipay_account'],  //卖家邮箱
            'price'             => $this->order['api_pay_amount'],//订单总价
            'quantity'          => 1,//商品数量
            'total_fee'         => 0,//物流配送费用
            'extend_param'      => "isv^sh32",
        );
    }

    /**
     * 获取海外支付宝支付接口
     * @desc sing和sing_type不参与要生成签名的参数组中，参数key是需要在生成sign是参与md5的
     * @param service 网关服务入口
     * @param partner 支付宝pid
     * @param key 支付宝Key
     * @param sign_type 加密类型
     * @param notify_url 支付宝交易完成后异步回调的接口
     * @param return_url 支付宝交易完成后前端网页跳转的页面链接，给客户看的
     * @param out_trade_no 外部系统交易订单ID（区别于支付宝自己的订单号）
     * @param subject 商品名称，考虑多个商品的情况
     * @param rmb_fee total_fee的替代参数，订单金额，表示订单交易使用人民币支付，与total_fee参数互斥出现
     * @param currency 外汇币种，表示支付宝会将受到的金额转化为此种外汇打到商户账上
     * @param _input_charset 字符集，只支持gbk和utf-8
     * @return [array]
     */
    public function get_forex_pay_params()
    {
        Log::record("支付宝海外支付接口，组装海外接口参数");
        //根据订单类型返回不同的return_url & notify_url
        $return_url = SHOP_SITE_URL."/api/payment/alipay/";
        $notify_url = SHOP_SITE_URL."/api/payment/alipay/";
        switch ($this->order['order_type']) {
            //实物订单
            case 'real_order':
                $notify_url_suffix = 'notify_url_real_order.php';
                $return_url_suffix = 'return_url_real_order.php';
                break;
            //预付款订单
            case 'pd_order':
                $notify_url_suffix = 'notify_url_pd_order.php';
                $return_url_suffix = 'return_url_pd_order.php';
                break;
            //虚拟订单
            case 'vr_order':
                $notify_url_suffix = 'notify_url_vr_order.php';
                $return_url_suffix = 'return_url_vr_order.php';
                break;
            default:
                $notify_url_suffix = 'notify_url.php';
                $return_url_suffix = 'return_url.php';
                break;
        }
        $notify_url = $notify_url . $notify_url_suffix;
        $return_url = $return_url . $return_url_suffix;
        Log::record("根据订单类型同步通知和异步通知链接也不同,return_url: {$return_url}, notify_url:{$notify_url}");
        return array(
            "service"        => 'create_forex_trade', //海外交易服务网关
            "partner"        => $this->payment['payment_config']['alipay_partner'],
            // 'partner'        => '2088101122136241',
            'key'            => $this->payment['payment_config']['alipay_key'],
            // 'key'            => '760bdzec6y9goq7ctyx96ezkz78287de',
            'sign_type'      => 'MD5',
            "notify_url"     => $notify_url,
            "return_url"     => $return_url,
            "out_trade_no"   => $this->order['pay_sn'],
            "subject"        => $this->order['subject'], //商品名称,
            "rmb_fee"        => $this->order['api_pay_amount'], //订单总价
            "body"           => $this->order['subject'],
            "currency"       => self::DEFAULT_FOREX_CURRENCY,
            "_input_charset" => CHARSET,
        );
    }

	/**
	 * 通知地址验证
	 *
	 * @return bool
	 */
	public function notify_verify() {
		$param	= $_POST;
		$param['key']	= $this->payment['payment_config']['alipay_key'];
        Log::record("notify_verify的原始参数:" . json_encode($param));
		$veryfy_url = $this->alipay_verify_url. "partner=" .$this->payment['payment_config']['alipay_partner']. "&notify_id=".$param["notify_id"];
		$veryfy_result  = $this->getHttpResponse($veryfy_url);
        Log::record("发起notify请求，url:{$veryfy_url}, 请求结果:{$veryfy_result}");
		$mysign = $this->sign($param);
        Log::record("检查参数校验 生成签名参数:" . json_encode($param) . ", mysign:{$mysign}, 参数中的sign:{$param['sign']}");
		if (preg_match("/true$/i",$veryfy_result) && $mysign == $param["sign"])  {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * 返回地址验证
	 *
	 * @return bool
	 */
	public function return_verify() {
        Log::record("开始同步返回地址验证");
		$param	= $_GET;
		//将系统的控制参数置空，防止因为加密验证出错
		$param['act']	= '';
		$param['op']	= '';
		$param['payment_code'] = '';
        $param['extra_common_param'] = '';
		$param['key']	= $this->payment['payment_config']['alipay_key'];
        Log::record("return_verify的原始参数:" . json_encode($param));
		$veryfy_url = $this->alipay_verify_url. "partner=" .$this->payment['payment_config']['alipay_partner']. "&notify_id=".$param["notify_id"];
		$veryfy_result  = $this->getHttpResponse($veryfy_url);
        Log::record("发起同步返回验证请求，请求地址: {$veryfy_url}， 请求结果:{$veryfy_result}");
		$mysign = $this->sign($param);
        Log::record("检查参数校验 生成签名参数:" . json_encode($param) . ",mysign:{$mysign}, 参数中的sign:{$param['sign']}");
		if (preg_match("/true$/i",$veryfy_result) && $mysign == $param["sign"])  {
            return true;
		} else {
			return false;
		}
	}

	/**
	 * 
	 * 取得订单支付状态，成功或失败
	 * @param array $param
	 * @return array
	 */
	public function getPayResult($param){
		return $param['trade_status'] == 'TRADE_SUCCESS';
	}

	/**
	 * 
	 *
	 * @param string $name
	 * @return 
	 */
	public function __get($name){
	    return $this->$name;
	}

	/**
	 * 远程获取数据
	 * $url 指定URL完整路径地址
	 * @param $time_out 超时时间。默认值：60
	 * return 远程输出的数据
	 */
	private function getHttpResponse($url,$time_out = "60") {
		$urlarr     = parse_url($url);
		$errno      = "";
		$errstr     = "";
		$transports = "";
		$responseText = "";
		if($urlarr["scheme"] == "https") {
			$transports = "ssl://";
			$urlarr["port"] = "443";
		} else {
			$transports = "tcp://";
			$urlarr["port"] = "80";
		}
		$fp=@fsockopen($transports . $urlarr['host'],$urlarr['port'],$errno,$errstr,$time_out);
		if(!$fp) {
			die("ERROR: $errno - $errstr<br />\n");
		} else {
			if (trim(CHARSET) == '') {
				fputs($fp, "POST ".$urlarr["path"]." HTTP/1.1\r\n");
			} else {
				fputs($fp, "POST ".$urlarr["path"].'?_input_charset='.CHARSET." HTTP/1.1\r\n");
			}
			fputs($fp, "Host: ".$urlarr["host"]."\r\n");
			fputs($fp, "Content-type: application/x-www-form-urlencoded\r\n");
			fputs($fp, "Content-length: ".strlen($urlarr["query"])."\r\n");
			fputs($fp, "Connection: close\r\n\r\n");
			fputs($fp, $urlarr["query"] . "\r\n\r\n");
			while(!feof($fp)) {
				$responseText .= @fgets($fp, 1024);
			}
			fclose($fp);
			$responseText = trim(stristr($responseText,"\r\n\r\n"),"\r\n");
			return $responseText;
		}
	}

    /**
     * 制作支付接口的请求地址
     *
     * @return string
     */
    private function create_url() {
		$url        = $this->alipay_gateway_new;
		$filtered_array	= $this->para_filter($this->parameter);
		$sort_array = $this->arg_sort($filtered_array);
		$arg        = "";
		while (list ($key, $val) = each ($sort_array)) {
			$arg.=$key."=".urlencode($val)."&";
		}
		$url.= $arg."sign=" .$this->parameter['sign'] ."&sign_type=".$this->parameter['sign_type'];
		return $url;
	}

	/**
	 * 取得支付宝签名
	 *
	 * @return string
	 */
	private function sign($parameter) {
		$mysign = "";
        Log::record("生成sign传入参数:" . json_encode($parameter));
		$filtered_array	= $this->para_filter($parameter);
        Log::record("取得支付宝签名-参数过滤结果:" . json_encode($filtered_array));
		$sort_array = $this->arg_sort($filtered_array);
        Log::record("取得支付宝签名-参数排序结果:" . json_encode($sort_array));
		$arg = "";
        while (list ($key, $val) = each ($sort_array)) {
			$arg	.= $key."=".$this->charset_encode($val,(empty($parameter['_input_charset'])?"UTF-8":$parameter['_input_charset']),(empty($parameter['_input_charset'])?"UTF-8":$parameter['_input_charset']))."&";
		}
		$prestr = substr($arg,0,-1);  //去掉最后一个&号
		$prestr	.= $parameter['key'];
        Log::record("取得支付宝签名-加密前的参数:{$prestr}");
        if($parameter['sign_type'] == 'MD5') {
			$mysign = md5($prestr);
            Log::record("md5加密方式后的结果:{$mysign}");
		}elseif($parameter['sign_type'] =='DSA') {
			//DSA 签名方法待后续开发
			die("DSA 签名方法待后续开发，请先使用MD5签名方式");
		}else {
			die("支付宝暂不支持".$parameter['sign_type']."类型的签名方式");
		}
		return $mysign;

	}

	/**
	 * 除去数组中的空值和签名模式
	 *
	 * @param array $parameter
	 * @return array
	 */
	private function para_filter($parameter) {
        Log::record("para_filter before:" . json_encode($parameter));
		$para = array();
        foreach ($parameter as $key => $val) {
            if ($key == "sign" || $key == "sign_type" || $key == "key" || $val == "") {
                continue;
            }
            else {
                $para[$key] = $parameter[$key];
            }
        }
        /*
		while (list ($key, $val) = each ($parameter)) {
			if($key == "sign" || $key == "sign_type" || $key == "key" || $val == "")continue;
			else	$para[$key] = $parameter[$key];
		}
        */
        Log::record("para_filter after:" . json_encode($para));
		return $para;
	}

	/**
	 * 重新排序参数数组
	 *
	 * @param array $array
	 * @return array
	 */
	private function arg_sort($array) {
		ksort($array);
		reset($array);
		return $array;

	}

	/**
	 * 实现多种字符编码方式
	 */
	private function charset_encode($input,$_output_charset,$_input_charset="UTF-8") {
		$output = "";
		if(!isset($_output_charset))$_output_charset  = $this->parameter['_input_charset'];
		if($_input_charset == $_output_charset || $input == null) {
			$output = $input;
		} elseif (function_exists("mb_convert_encoding")){
			$output = mb_convert_encoding($input,$_output_charset,$_input_charset);
		} elseif(function_exists("iconv")) {
			$output = iconv($_input_charset,$_output_charset,$input);
		} else die("sorry, you have no libs support for charset change.");
		return $output;
	}
}