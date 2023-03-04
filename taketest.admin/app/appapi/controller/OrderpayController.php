<?php
/**
 * 订单支付回调
 */

namespace app\appapi\controller;
use app\models\OrdersModel;
use app\models\MerstoreStoreOrdersModel;
use cmf\controller\HomeBaseController;
use think\Db;

class OrderpayController extends HomebaseController {
	//支付宝 回调
	public function notify_ali() {
        $configpri=getConfigPri();
        $alipay_config = array (
            //应用ID,您的APPID。
            'app_id' => $configpri['aliapp_appid'],
            //商户私钥
            'merchant_private_key' => $configpri['aliapp_key'],
            //编码格式
            'charset' => "UTF-8",
            //签名方式
            'sign_type'=>"RSA2",
            //支付宝网关
            'gatewayUrl' => "https://openapi.alipay.com/gateway.do",
            //支付宝公钥,查看地址：https://openhome.alipay.com/platform/keyManage.htm 对应APPID下的支付宝公钥。
            'alipay_public_key' => $configpri['aliapp_publickey'],
        );
        require_once(CMF_ROOT."sdk/alipay/pagepay/service/AlipayTradeService.php");

        //{"gmt_create":"2022-05-13 17:50:55","charset":"utf-8","seller_email":"3627367577@qq.com","subject":"1","sign":"sLfVZHi5nDq2jlz0+O6lccbyKgYEEKRW3fNPsGS6Vx9gFC3OKwiAAPx0j7PlA5MnRJcozHPubBkcnk5KraSBUELnt9LGJk3vCGtyxbNbw1U3rNSmVU1NebI+dp0v\/KcPyEEFZeSnkVREDk797vbKIIJ3SQsPjsMdESmRPHiOD3t9ZHhMHR6FT\/0TAGAuPV5xOenggrQLSDL2Rq2syGmxO1VT9+Bor\/+0c48F9IFT3vrhOAMuiWZrVL\/vU1A4k3NK2Q40\/y8HxNkqAm6uviohPqpawkHXwNQ74z5JQulVFc5dmy0A9Uld6Dh6UVnTD8cgfsvcVrNb5frsokYYZoYi\/A==","body":"\u5546\u54c1","buyer_id":"2088712341260433","invoice_amount":"0.10","notify_id":"2022051300222175056060431445571869","fund_bill_list":"[{\"amount\":\"0.10\",\"fundChannel\":\"ALIPAYACCOUNT\"}]","notify_type":"trade_status_sync","trade_status":"TRADE_SUCCESS","receipt_amount":"0.10","app_id":"2021002163620252","buyer_pay_amount":"0.10","sign_type":"RSA2","seller_id":"2088831280904168","gmt_payment":"2022-05-13 17:50:56","notify_time":"2022-05-13 17:50:56","version":"1.0","out_trade_no":"2084_220513175050345","total_amount":"0.10","trade_no":"2022051322001460431456080585","auth_app_id":"2021002163620252","buyer_logon_id":"178****0887","point_amount":"0.00"}
//        $testData = '{"gmt_create":"2022-05-13 17:50:55","charset":"utf-8","seller_email":"3627367577@qq.com","subject":"1","sign":"sLfVZHi5nDq2jlz0+O6lccbyKgYEEKRW3fNPsGS6Vx9gFC3OKwiAAPx0j7PlA5MnRJcozHPubBkcnk5KraSBUELnt9LGJk3vCGtyxbNbw1U3rNSmVU1NebI+dp0v\/KcPyEEFZeSnkVREDk797vbKIIJ3SQsPjsMdESmRPHiOD3t9ZHhMHR6FT\/0TAGAuPV5xOenggrQLSDL2Rq2syGmxO1VT9+Bor\/+0c48F9IFT3vrhOAMuiWZrVL\/vU1A4k3NK2Q40\/y8HxNkqAm6uviohPqpawkHXwNQ74z5JQulVFc5dmy0A9Uld6Dh6UVnTD8cgfsvcVrNb5frsokYYZoYi\/A==","body":"\u5546\u54c1","buyer_id":"2088712341260433","invoice_amount":"0.10","notify_id":"2022051300222175056060431445571869","fund_bill_list":"[{\"amount\":\"0.10\",\"fundChannel\":\"ALIPAYACCOUNT\"}]","notify_type":"trade_status_sync","trade_status":"TRADE_SUCCESS","receipt_amount":"0.10","app_id":"2021002163620252","buyer_pay_amount":"0.10","sign_type":"RSA2","seller_id":"2088831280904168","gmt_payment":"2022-05-13 17:50:56","notify_time":"2022-05-13 17:50:56","version":"1.0","out_trade_no":"2084_220513175050345","total_amount":"0.10","trade_no":"2022051322001460431456080585","auth_app_id":"2021002163620252","buyer_logon_id":"178****0887","point_amount":"0.00"}';
//        $_POST = json_decode($testData,true);

        $alipaySevice = new \AlipayTradeService($alipay_config);
        $result = $alipaySevice->check($_POST);

		$this->logali("ali_data:".json_encode($_POST));

		if($result) {//验证成功
			//商户订单号
			$out_trade_no = $_POST['out_trade_no'];
            $prefix = explode('_',$out_trade_no)[0] ?? '';

			//支付宝交易号
			$trade_no = $_POST['trade_no'];
			//交易状态
			$trade_status = $_POST['trade_status'];
			
			//交易金额
			$total_fee = $_POST['total_amount'];
			
			if($_POST['trade_status'] == 'TRADE_FINISHED') {
				//判断该笔订单是否在商户网站中已经做过处理
				//如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
				//如果有做过处理，不执行商户的业务程序
					
				//注意：
				//退款日期超过可退款期限后（如三个月可退款），支付宝系统发送该交易状态通知
				//请务必判断请求时的total_fee、seller_id与通知时获取的total_fee、seller_id为一致的

				//调试用，写文本函数记录程序运行情况是否正常
				//logResult("这里写入想要调试的代码变量值，或其他运行的结果记录");
		
			}else if ($_POST['trade_status'] == 'TRADE_SUCCESS') {
				//判断该笔订单是否在商户网站中已经做过处理
				//如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
				//如果有做过处理，不执行商户的业务程序
					
				//注意：
				//付款完成后，支付宝系统发送该交易状态通知
				//请务必判断请求时的total_fee、seller_id与通知时获取的total_fee、seller_id为一致的

				//调试用，写文本函数记录程序运行情况是否正常
				//logResult("这里写入想要调试的代码变量值，或其他运行的结果记录");
                if($prefix == 'store'){
                    //店铺订单
                    $ConfigPub = getConfigPub();
                    $data = ['data'=>json_encode($_POST)];
                    $site_url = $ConfigPub['site_url'];
                    $Apiurl = $site_url.'/api/?s=App.MerchantStoreOrder.aliAppOrder&'.http_build_query($data);
                    $res = curl_get($Apiurl);
                }else{
                    $where['orderno|orderno_pay']=$out_trade_no;
                    $where['paytype']=1;

                    $data=[
                        'trade_no'=>$trade_no
                    ];

                    $this->logali("where:".json_encode($where));
                    $res=OrdersModel::handelPay($where,$data);
                    if($res==0){
                        $this->logali("orderno:".$out_trade_no.' 订单信息不存在');
                        echo "fail";
                        exit;
                    }
                }


                $this->logali("成功");
                echo "success";		//请不要修改或删除
                exit;										
			}
			//——请根据您的业务逻辑来编写程序（以上代码仅作参考）——

			echo "fail";		//请不要修改或删除
			/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
		}else {
			$this->logali("验证失败");		
			//验证失败
			echo "fail";
			//调试用，写文本函数记录程序运行情况是否正常
			//logResult("这里写入想要调试的代码变量值，或其他运行的结果记录");
		}			
		
    }

	/* 微信支付 */
    private $wxDate = null;
	public function notify_wx(){
		$configpri=getConfigPri();
		//$xmlInfo = $GLOBALS['HTTP_RAW_POST_DATA'];
		$xmlInfo=file_get_contents("php://input"); 

		//解析xml
		$arrayInfo = $this -> xmlToArray($xmlInfo);

//        $testData ='{"appid":"wx88ce0419d182313c","bank_type":"OTHERS","cash_fee":"2","fee_type":"CNY","is_subscribe":"N","mch_id":"1596653071","nonce_str":"8224488e70a197703c42b9ccb1e70ea6","openid":"oZMs35Ce3UAt1_LkVAkAmBGr1xos","out_trade_no":"store_1668656481272443835","result_code":"SUCCESS","return_code":"SUCCESS","sign":"F556E4913F8525B4142208E119F83FEF","time_end":"20221117114128","total_fee":"2","trade_type":"JSAPI","transaction_id":"4200001602202211175120994966"}';
//		$arrayInfo = json_decode($testData,true);

		$this -> wxDate = $arrayInfo;
		$this -> logwx("wx_data:".json_encode($arrayInfo));//log打印保存

		if($arrayInfo['return_code'] == "SUCCESS"){
			if(isset($arrayInfo['return_msg']) && $arrayInfo['return_msg'] != null){
				echo $this -> returnInfo("FAIL","签名失败");
				$this -> logwx("签名失败:");//log打印保存
				exit;
			}else{

                $out_trade_no = $this -> wxDate['out_trade_no'];
                $prefix = explode('_',$out_trade_no)[0] ?? '';

                if($prefix == 'store'){

                    //店铺订单
                    $wxSign = $arrayInfo['sign'];
                    unset($arrayInfo['sign']);

                    $arrayInfo['appid']  =  $configpri['wx_appid'];
                    $arrayInfo['mch_id'] =  $configpri['wx_mchid'];
                    $key =  $configpri['wx_key'];

                    ksort($arrayInfo);//按照字典排序参数数组
                    $sign = $this -> sign($arrayInfo,$key);//生成签名
                    $this -> logwx("数据打印测试签名signmy:".$sign.":::微信sign:".$wxSign);//log打印保存
                    if($this -> checkSign($wxSign,$sign)){
                        echo $this -> returnInfo("SUCCESS","OK");
                        $this -> logwx("签名验证结果成功:".$sign);//log打印保存

                        $ConfigPub = getConfigPub();
                        $arrayInfo['sign'] = $wxSign;
                        $data = ['data'=>json_encode($arrayInfo)];
                        $site_url = $ConfigPub['site_url'];
                        $Apiurl = $site_url.'/api/?s=App.MerchantStoreOrder.wechatAppOrder&'.http_build_query($data);
                        $res = curl_get($Apiurl);
                        exit;
                    }else{
                        echo $this -> returnInfo("FAIL","签名失败");
                        $this -> logwx("签名验证结果失败:本地加密：".$sign.'：：：：：三方加密'.$wxSign);//log打印保存
                        exit;
                    }

                }else{
                    $wxSign = $arrayInfo['sign'];
                    unset($arrayInfo['sign']);

                    $where['orderno|orderno_pay']=$out_trade_no;

                    $storeOrder = OrdersModel::getInfo($where);
                    if(!$storeOrder){
                        $this -> logwx("跑腿订单不存在：".json_encode($arrayInfo));//log打印保存
                        echo $this -> returnInfo("SUCCESS","OK");
                        die();
                    }

                    if($storeOrder['is_mer']){
                        //商家下单
                        $arrayInfo['appid']  =  $configpri['mer_wx_appid'];
                    }else{
                        $arrayInfo['appid']  =  $configpri['wx_appid'];
                    }
                    $arrayInfo['mch_id'] =  $configpri['wx_mchid'];
                    $key =  $configpri['wx_key'];


                    ksort($arrayInfo);//按照字典排序参数数组
                    $sign = $this -> sign($arrayInfo,$key);//生成签名
                    $this -> logwx("数据打印测试签名signmy:".$sign.":::微信sign:".$wxSign);//log打印保存
                    if($this -> checkSign($wxSign,$sign)){
                        echo $this -> returnInfo("SUCCESS","OK");
                        $this -> logwx("签名验证结果成功:".$sign);//log打印保存
                        $this -> orderServer(2);//订单处理业务逻辑
                        exit;
                    }else{
                        echo $this -> returnInfo("FAIL","签名失败");
                        $this -> logwx("签名验证结果失败:本地加密：".$sign.'：：：：：三方加密'.$wxSign);//log打印保存
                        exit;
                    }
                }

			}
		}else{
			echo $this -> returnInfo("FAIL","签名失败");
			$this -> logwx($arrayInfo['return_code']);//log打印保存
			exit;
		}			
	}

    /* UNIAPP端小程序支付 */
    public function notify_small(){
        $configpri=getConfigPri();
        //$xmlInfo = $GLOBALS['HTTP_RAW_POST_DATA'];
        $xmlInfo=file_get_contents("php://input");
        //{"appid":"wx88ce0419d182313c","bank_type":"OTHERS","cash_fee":"103","fee_type":"CNY","is_subscribe":"N","mch_id":"1596653071","nonce_str":"d3e368d0950789ba708ab86fd4f825ed","openid":"oZMs35MUQxZpxd-WgRqgyBzLe1WE","out_trade_no":"store_166720815276631048","result_code":"SUCCESS","return_code":"SUCCESS","sign":"938D789600C851103931CEB8266D4436","time_end":"20221031172238","total_fee":"103","trade_type":"JSAPI","transaction_id":"4200001616202210311369861836"}
        //解析xml
        $arrayInfo = $this -> xmlToArray($xmlInfo);

//        $testData  = '{"appid":"wx88ce0419d182313c","bank_type":"OTHERS","cash_fee":"4","fee_type":"CNY","is_subscribe":"N","mch_id":"1596653071","nonce_str":"a5a09842269846403926f2a3cfa864d2","openid":"oZMs35Ce3UAt1_LkVAkAmBGr1xos","out_trade_no":"store_1668665906802912309","result_code":"SUCCESS","return_code":"SUCCESS","sign":"B5EC99A9F80632C1BB73A09932231050","time_end":"20221117141834","total_fee":"4","trade_type":"JSAPI","transaction_id":"4200001613202211179321181174"}';
//        $arrayInfo = json_decode($testData,true);

        $this -> wxDate = $arrayInfo;
        $this -> logwx("small_data:".json_encode($arrayInfo));//log打印保存
        if($arrayInfo['return_code'] == "SUCCESS"){
            if(isset($arrayInfo['return_msg']) && $arrayInfo['return_msg'] != null){
                echo $this -> returnInfo("FAIL","签名失败");
                $this -> logwx("签名失败:");//log打印保存
                exit;
            }else{

                $out_trade_no = $this -> wxDate['out_trade_no'];
                $prefix = explode('_',$out_trade_no)[0] ?? '';

                if($prefix == 'store'){
                    //店铺订单
                    //店铺订单
                    $wxSign = $arrayInfo['sign'];
                    unset($arrayInfo['sign']);

                    $arrayInfo['appid']  =  $configpri['small_appid'];
                    $arrayInfo['mch_id'] =  $configpri['wx_mchid'];
                    $key =  $configpri['wx_key'];

                    ksort($arrayInfo);//按照字典排序参数数组
                    $sign = $this -> sign($arrayInfo,$key);//生成签名
                    $this -> logwx("数据打印测试签名signmy:".$sign.":::微信sign:".$wxSign);//log打印保存
                    if($this -> checkSign($wxSign,$sign)){
                        echo $this -> returnInfo("SUCCESS","OK");
                        $this -> logwx("签名验证结果成功:".$sign);//log打印保存

                        $ConfigPub = getConfigPub();
                        $arrayInfo['sign'] = $wxSign;
                        $data = ['data'=>json_encode($arrayInfo)];
                        $site_url = $ConfigPub['site_url'];
                        $Apiurl = $site_url.'/api/?s=App.MerchantStoreOrder.wechatSmallOrder&'.http_build_query($data);
                        $res = curl_get($Apiurl);
                        exit;
                    }else{
                        echo $this -> returnInfo("FAIL","签名失败");
                        $this -> logwx("签名验证结果失败:本地加密：".$sign.'：：：：：三方加密'.$wxSign);//log打印保存
                        exit;
                    }

                }else{
                    $where['orderno|orderno_pay']=$out_trade_no;
                    $storeOrder = OrdersModel::getInfo($where);
                    if(!$storeOrder){
                        $this -> logwx("跑腿订单不存在：".json_encode($arrayInfo));//log打印保存
                        echo $this -> returnInfo("SUCCESS","OK");
                        die();
                    }


                    $smallSign = $arrayInfo['sign'];
                    unset($arrayInfo['sign']);
                    if($storeOrder['is_mer']) {
                        //商家下单
                        $arrayInfo['appid']  =  $configpri['mer_small_appid'];
                    }else{
                        $arrayInfo['appid']  =  $configpri['small_appid'];
                    }
                    $key =  $configpri['wx_key'];
                    $arrayInfo['mch_id'] =  $configpri['wx_mchid'];

                    ksort($arrayInfo);//按照字典排序参数数组
                    $sign = $this -> sign($arrayInfo,$key);//生成签名
                    $this -> logwx("数据打印测试签名signmy:".$sign.":::小程序sign:".$smallSign);//log打印保存
                    if($this -> checkSign($smallSign,$sign)){
                        echo $this -> returnInfo("SUCCESS","OK");
                        $this -> logwx("签名验证结果成功:".$sign);//log打印保存
                        $this -> orderServer(3);//订单处理业务逻辑
                        exit;
                    }else{
                        echo $this -> returnInfo("FAIL","签名失败");
                        $this -> logwx("签名验证结果失败:本地加密：".$sign.'：：：：：三方加密'.$smallSign);//log打印保存
                        exit;
                    }
                }

            }
        }else{
            echo $this -> returnInfo("FAIL","签名失败");
            $this -> logwx($arrayInfo['return_code']);//log打印保存
            exit;
        }
    }

    /* UNIAPP端h5支付 */
    public function notify_hfive(){
        $configpri=getConfigPri();
        //$xmlInfo = $GLOBALS['HTTP_RAW_POST_DATA'];
        $xmlInfo=file_get_contents("php://input");

        //解析xml
        $arrayInfo = $this -> xmlToArray($xmlInfo);
        $this -> wxDate = $arrayInfo;
        $this -> logwx("h5_data:".json_encode($arrayInfo));//log打印保存
        if($arrayInfo['return_code'] == "SUCCESS"){
            if(isset($arrayInfo['return_msg']) && $arrayInfo['return_msg'] != null){
                echo $this -> returnInfo("FAIL","签名失败");
                $this -> logwx("签名失败:");//log打印保存
                exit;
            }else{
                $hfiveSign = $arrayInfo['sign'];
                unset($arrayInfo['sign']);

                $arrayInfo['appid']  =  $configpri['pc_wx_appid'];
                $arrayInfo['mch_id'] =  $configpri['pc_wx_mchid'];
                $key =  $configpri['wx_key'];
                ksort($arrayInfo);//按照字典排序参数数组
                $sign = $this -> sign($arrayInfo,$key);//生成签名
                $this -> logwx("数据打印测试签名signmy:".$sign.":::h5sign:".$hfiveSign);//log打印保存
                if($this -> checkSign($hfiveSign,$sign)){
                    echo $this -> returnInfo("SUCCESS","OK");
                    $this -> logwx("签名验证结果成功:".$sign);//log打印保存
                    $this -> orderServer(4);//订单处理业务逻辑
                    exit;
                }else{
                    echo $this -> returnInfo("FAIL","签名失败");
                    $this -> logwx("签名验证结果失败:本地加密：".$sign.'：：：：：三方加密'.$hfiveSign);//log打印保存
                    exit;
                }
            }
        }else{
            echo $this -> returnInfo("FAIL","签名失败");
            $this -> logwx($arrayInfo['return_code']);//log打印保存
            exit;
        }
    }

    /*微信内H5支付 */
    public function notify_mp(){
        $configpri=getConfigPri();
        //$xmlInfo = $GLOBALS['HTTP_RAW_POST_DATA'];
        $xmlInfo=file_get_contents("php://input");

        //解析xml
        $arrayInfo = $this -> xmlToArray($xmlInfo);
        $this -> wxDate = $arrayInfo;
        $this -> logwx("mp_data:".json_encode($arrayInfo));//log打印保存
        if($arrayInfo['return_code'] == "SUCCESS"){
            if(isset($arrayInfo['return_msg']) && $arrayInfo['return_msg'] != null){
                echo $this -> returnInfo("FAIL","签名失败");
                $this -> logwx("签名失败:");//log打印保存
                exit;
            }else{
                $smallSign = $arrayInfo['sign'];
                unset($arrayInfo['sign']);
                $arrayInfo['appid']  =  $configpri['mp_appid'] ?? '';
                $arrayInfo['mch_id'] =  $configpri['wx_mchid'] ?? '';
                $key =  $configpri['wx_key'] ?? '';
                ksort($arrayInfo);//按照字典排序参数数组
                $sign = $this -> sign($arrayInfo,$key);//生成签名
                $this -> logwx("数据打印测试签名signmy:".$sign.":::小程序sign:".$smallSign);//log打印保存
                if($this -> checkSign($smallSign,$sign)){
                    echo $this -> returnInfo("SUCCESS","OK");
                    $this -> logwx("签名验证结果成功:".$sign);//log打印保存
                    $this -> orderServer(5);//订单处理业务逻辑
                    exit;
                }else{
                    echo $this -> returnInfo("FAIL","签名失败");
                    $this -> logwx("签名验证结果失败:本地加密：".$sign.'：：：：：三方加密'.$smallSign);//log打印保存
                    exit;
                }
            }
        }else{
            echo $this -> returnInfo("FAIL","签名失败");
            $this -> logwx($arrayInfo['return_code']);//log打印保存
            exit;
        }
    }

	private function returnInfo($type,$msg){
		if($type == "SUCCESS"){
			return $returnXml = "<xml><return_code><![CDATA[{$type}]]></return_code></xml>";
		}else{
			return $returnXml = "<xml><return_code><![CDATA[{$type}]]></return_code><return_msg><![CDATA[{$msg}]]></return_msg></xml>";
		}
	}		
	
	//签名验证
	private function checkSign($sign1,$sign2){
		return trim($sign1) == trim($sign2);
	}
	/* 订单查询加值业务处理
	 * @param orderNum 订单号	   
	 */
	private function orderServer($paytype = ''){

        $info = $this -> wxDate;
        $this->logwx("info:".json_encode($info));
        $where['paytype']=$paytype;

        $where['orderno|orderno_pay']=$info['out_trade_no'];
        
        $trade_no=$info['transaction_id'];
        
        $data=[
            'trade_no'=>$trade_no
        ];
        
        $this->logwx("where:".json_encode($where));	
        $res=OrdersModel::handelPay($where,$data);
        if($res==0){
            $this->logwx("orderno:".' 订单信息不存在');
            return false;
        }

        $this->logwx("成功");
        return true;

	}		
	/**
	* sign拼装获取
	*/
	private function sign($param,$key){
		
		$sign = "";
		foreach($param as $k => $v){
			$sign .= $k."=".$v."&";
		}
	
		$sign .= "key=".$key;
		$sign = strtoupper(md5($sign));
		return $sign;
	
	}
	/**
	* xml转为数组
	*/
	private function xmlToArray($xmlStr){
		$msg = array(); 
		$postStr = $xmlStr; 
		$msg = (array)simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA); 
		return $msg;
	}
	
	/* 微信支付 */
    
     /*public function test(){
        $where=[
            'orderno_pay'=>'9_220113153114797',
            'type'=>'3',
        ];
        
        $data=['trade_no'=>'4200001391202201132260635760'];
        
        OrdersModel::handelPay($where,$data);
    }*/
    

    
    /* 打印log */
	protected function logali($msg){
		file_put_contents(CMF_ROOT.'data/log/orderpay_logali_'.date('Y-m-d').'.txt',date('Y-m-d H:i:s').'  msg:'.$msg."\r\n",FILE_APPEND);
	}
    
	/* 打印log */
	protected function logwx($msg){
		file_put_contents(CMF_ROOT.'data/log/orderpay_logwx_'.date('Y-m-d').'.txt',date('Y-m-d H:i:s').'  msg:'.$msg."\r\n",FILE_APPEND);
	}
}


