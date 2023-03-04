<?php

namespace app\substation\service\pay;


class Alipay
{

    /* APP支付
    *  orderid  订单号
    *  money    CNY（元）
    *  url      回调URL（全链接）
    *  body     提示标题
    */
    public function apppay($orderid, $money, $url, $body = '充值虚拟币')
    {

        $rs = ['code' => 0, 'msg' => '', 'info' => []];

        $configpri = getConfigPri();
        $appid = $configpri['aliapp_appid'] ?? '';
        $key = $configpri['aliapp_key'] ?? '';
        $publickey = $configpri['aliapp_publickey'] ?? '';
        //配置参数检测
        if ($appid == "" || $key == "" || $publickey == "") {
            throw new \Exception('支付宝未配置');
        }

        $noceStr = md5(rand(100, 1000) . time());//获取随机字符串
        $time = time();

        require_once CMF_ROOT.'sdk/alipay/aop/AopClient.php';
        require_once CMF_ROOT.'sdk/alipay/aop/request/AlipayTradeAppPayRequest.php';

        $aop = new \AopClient ();
        $aop->gatewayUrl = 'https://openapi.alipay.com/gateway.do';
        $aop->appId = $appid;
        $aop->rsaPrivateKey = $key;
        $aop->alipayrsaPublicKey = $publickey;
        $aop->apiVersion = '1.0';
        $aop->signType = 'RSA2';

        $bizcontent = [
            "subject" => $body,
            "out_trade_no" => $orderid,
            "total_amount" => $money,
        ];

        $json = json_encode($bizcontent);
        $request = new \AlipayTradeAppPayRequest();
        $request->setNotifyUrl($url);
        $request->setBizContent($json);

        $response = $aop->sdkExecute($request);

        $info = [
            'orderinfo' => $response,
        ];
        $rs['info'] = $info;
        return $rs;
    }

    /**
     * 支付宝APP退款
     * @param $outTradeNo 商户订单号
     * @param $refundAmount 退款金额
     * @param $outRequestNo 退款单号
     * @param $refundReason 退款原因
     * @return void
     */
    public function aliAppRefund($outTradeNo, $refundAmount, $outRequestNo, $refundReason)
    {
        $rs = ['code' => 0, 'msg' => '操作成功', 'info' => []];

        $configpri = getConfigPri();
        $appid = $configpri['aliapp_appid'] ?? '';
        $key = $configpri['aliapp_key'] ?? '';
        $publickey = $configpri['aliapp_publickey'] ?? '';
        //配置参数检测
        if ($appid == "" || $key == "" || $publickey == "") {
            throw new \Exception('支付宝未配置');
        }

        $noceStr = md5(rand(100, 1000) . time());//获取随机字符串
        $time = time();

        require_once CMF_ROOT.'sdk/alipay/aop/SignData.php';
        require_once CMF_ROOT.'sdk/alipay/aop/AopClient.php';
        require_once CMF_ROOT.'sdk/alipay/aop/request/AlipayTradeRefundRequest.php';

        $aop = new \AopClient ();
        $aop->gatewayUrl = 'https://openapi.alipay.com/gateway.do';
        $aop->appId = $appid;
        $aop->rsaPrivateKey = $key;
        $aop->alipayrsaPublicKey = $publickey;
        $aop->apiVersion = '1.0';
        $aop->signType = 'RSA2';

        $bizcontent = [
            "out_trade_no" => $outTradeNo,
            "refund_amount" => round($refundAmount, 2),
            "out_request_no" => $outRequestNo,
            "refund_reason" => $refundReason,
        ];
        $json = json_encode($bizcontent);
        $request = new \AlipayTradeRefundRequest();
        $request->setBizContent($json);
        $response = $aop->execute($request);

        $responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
        $resultCode = $response->$responseNode->code;
        if (!empty($resultCode) && $resultCode != 10000) {
            throw new \Exception($response->$responseNode->sub_msg);
        }

        return $rs;
    }

}
