<?php

namespace app\appapi\controller;

use cmf\controller\HomeBaseController;
use app\models\MerstoreStoreOrdersModel;
use app\models\OrdersModel;

/**
 * 订单退款
 */
class OrderRefundController extends HomeBaseController
{

    /**
     * 微信APP退款结果通知
     * @return void
     */
    public function wechatAppRefund()
    {
        $xmlInfo = file_get_contents("php://input");
//        $xmlInfo = "<xml><return_code>SUCCESS</return_code><appid><![CDATA[wxcaca1fd4f5dea470]]></appid><mch_id><![CDATA[1596653071]]></mch_id><nonce_str><![CDATA[55e98b19550d853e020ffe51a2bbc11d]]></nonce_str><req_info><![CDATA[p5T0NHTgyg8I2owAewGALeYEoL+HEMpqb+/Agn4dTgwW2y7qrHF23sobmQyFKnpg+jJDA+Ak/X2h/3XNrQb+R/XTS4XsDlmTkG/yoqCWiAn5GbnOPr841B3CO6ypDBQNucfFxtZSqmW8XqsNgAB/3EpehwAvTRrsQe3rt98JlKD7M4Z/W7HK9cweYnGOSPCVfViNp3HVRZKZzNVdGZ3SNHP48+3t3VprtGlFdkqGr6PFO9EWwBlRDeBJaPBuGqPzT3lsewFkL2tNXPq13vCIPEEcjULmJcYqbQE/3cdT+4H28hgFU84YSsDKy+n94xHkuIdALjErDBKK5O1tg3xjOYK1jCEuvXyxYzhaRMqUmcL67Jg8QG03sSoY8p4aF1JtCmlvZF8hfASpZb1YRuoaZn7YpuzEHTDNRXo5nJHXQOAY4itmn0YEsPWgF0OTtmUOHyChtU9oVp0CnkuNgDXqibZBNel4Eg5Dn86Q8Wg0EPRiGlYkz6ctA1d5ruBivhWdAjRklrB4vK0Ip9Cz6TG658W1lCOauKjngstOKKLzHNnVWvNKKABRdaNyetP4So4XPbEM7w+2C1xAgaDXbtHjUi9rwsGXs4XAESXLIKBsMDMUZ01HnalkFUTtoxrNGeg1rO5u7/OhuEzFraybl+op0Sb17kJr1KathBayFy1zognDcOsgbjug9j9yTOKP5v/vrDyKQokWs2gLBc1L+FJDYdYjvkxqxv4XC3hqLt8cV8xS8ViDcE8op9+Ej0QurWpIZsiG6ps/v5EnZj7uaSYMxV1XOyLZZKItCGzmpibUy3cL85ly02m4DmMvzRNAzTla4y3aaxFR+gNFqF5keqWxRpGvJq+igTS/aGhrRqVY3I1plXzc2Jlt7IhQj/QvizUyt9r0HS3YAIe1PiPfMWoraSppFeiam3YjG7rIj8wxgTboeyrVNAU14jNWjBoI0oE7a1IAvvD6YSkTB0L6P46NR7XVJs2VBB9OVjv6kov4Rihnv9Czu1V2atkPo29wgL5HWOM8ND6SdBlN5ezwZ1eIhEyblvLWc3/BslMMRwOW17a7GxbSlcIK9jR2US9OKMI0BYKI8ymgn7MwVy2Xz+qZ8Q/mAnlUOqEpGeZKNqk1CCFauwR1CHwiFt6MJGQ/bDVV]]></req_info></xml>";
        $xmlArr = $this->xmlToArray($xmlInfo);
        $this->log(json_encode($xmlArr));
        $xmlArr = json_decode('{"return_code":"SUCCESS","appid":"wx74f73771f5c66d60","mch_id":"1596653071","nonce_str":"66fe7d744802894b576ac08bd6c007cd","req_info":"p5T0NHTgyg8I2owAewGALeYEoL+HEMpqb+\/Agn4dTgxR8Y2S8WjFFT\/YkiJWOgM0+jJDA+Ak\/X2h\/3XNrQb+R\/XTS4XsDlmTkG\/yoqCWiAlMCYkN5o6JEnl7zMEkJ2b6fpe2is41HsKR57Dqr4nQbwJTe3Fpx2qOC7Mx\/4lsSTfIiO7Ft6yAlgIxlJAe43Khr6IH22Tb3Uy+AN6\/vZ\/CVR7HYzDrscSndANVxtku6NrtXzSnsPNdWQoJ8GoEoKV02TKhFceQzAY4yBLIrcGNeSp7hLpEM7ho\/q8obDesL3WcO97J9S3K+NuBEZbgTZRHzbPf80bmMKTzN2Hz5yXVMOYEoL+HEMpqb+\/Agn4dTgx7rvr0AMKDo9vxBu7zgrm0vqTN+Fsv97yudtp4UEyi8E0VbpNLbw+5vUPBGg4dAzL7smMPgWt7KsVDFXnd4SdRu4i\/65rkGoXI71gpUOOWKtXc1UAwa0GX9kJPQcZ+sc8nInwEr0lAhxFKYqqjRl4ObZPa3i7L9xDhZY7de5rrBGf+3XYOUTWH59OcqYZH6+YzbdaiEJF5dgnZIiua57lc3v0uvD3314lSPjY+XUUQL1JGJFaKJSfkgRb0CViLdnjzPYK1zOMjEIgb6Gumcm20OaGKBY32E4iGLbZZysmT\/ZYaheKW0VCXzj9v92M\/s4vzaDuBmiSszcdZorrOT9Giy6w01g8s4IJcmLxSF2rXgYH5SXaSeuQd8vbWaEm\/GNfHr8wnu5iVeh3ggRAKZMLFD0can3WXv3SLrq5+zeVmvlQRKzDOZ55m797LPiwqvYog1gtx28U47ZN9PDgMxfjBN0hNip5CeIJKKx9ixkE6M+u34FRtrmjUxKk3op5bkB\/YjWgtDuTc0ssV8IMnl3QM9SPpxuvqMcbG2ubdgbOVn0GcVVUZrf5ebN0mGfILXX964JO3e\/dqEloCFSVHNtH1tMXQc\/J3\/Iig6AV+Ul3OFNov0PeGTUkKeXtvg5e+wTRxjotIUQmrgEFuQqiCxzKNeid0I2sMaPw0mMQHWbek7gpeBsxJRdQ7yYpnCerDD2k0fi67Q8Cc\/JwZYUQVWbbAn8etb09NuQjZamrHT85ygTh9Pff4NZPGGfZNLBssLN8="}', true);
        $this->decryption($xmlArr);
        echo $this->returnInfo("SUCCESS", "OK");
    }

    /**
     * 微信小程序退款结果通知
     * @return void
     */
    public function wechatSmallRefund()
    {
        $xmlInfo = file_get_contents("php://input");
//        $xmlInfo = "<xml><return_code>SUCCESS</return_code><appid><![CDATA[wxcaca1fd4f5dea470]]></appid><mch_id><![CDATA[1596653071]]></mch_id><nonce_str><![CDATA[55e98b19550d853e020ffe51a2bbc11d]]></nonce_str><req_info><![CDATA[p5T0NHTgyg8I2owAewGALeYEoL+HEMpqb+/Agn4dTgwW2y7qrHF23sobmQyFKnpg+jJDA+Ak/X2h/3XNrQb+R/XTS4XsDlmTkG/yoqCWiAn5GbnOPr841B3CO6ypDBQNucfFxtZSqmW8XqsNgAB/3EpehwAvTRrsQe3rt98JlKD7M4Z/W7HK9cweYnGOSPCVfViNp3HVRZKZzNVdGZ3SNHP48+3t3VprtGlFdkqGr6PFO9EWwBlRDeBJaPBuGqPzT3lsewFkL2tNXPq13vCIPEEcjULmJcYqbQE/3cdT+4H28hgFU84YSsDKy+n94xHkuIdALjErDBKK5O1tg3xjOYK1jCEuvXyxYzhaRMqUmcL67Jg8QG03sSoY8p4aF1JtCmlvZF8hfASpZb1YRuoaZn7YpuzEHTDNRXo5nJHXQOAY4itmn0YEsPWgF0OTtmUOHyChtU9oVp0CnkuNgDXqibZBNel4Eg5Dn86Q8Wg0EPRiGlYkz6ctA1d5ruBivhWdAjRklrB4vK0Ip9Cz6TG658W1lCOauKjngstOKKLzHNnVWvNKKABRdaNyetP4So4XPbEM7w+2C1xAgaDXbtHjUi9rwsGXs4XAESXLIKBsMDMUZ01HnalkFUTtoxrNGeg1rO5u7/OhuEzFraybl+op0Sb17kJr1KathBayFy1zognDcOsgbjug9j9yTOKP5v/vrDyKQokWs2gLBc1L+FJDYdYjvkxqxv4XC3hqLt8cV8xS8ViDcE8op9+Ej0QurWpIZsiG6ps/v5EnZj7uaSYMxV1XOyLZZKItCGzmpibUy3cL85ly02m4DmMvzRNAzTla4y3aaxFR+gNFqF5keqWxRpGvJq+igTS/aGhrRqVY3I1plXzc2Jlt7IhQj/QvizUyt9r0HS3YAIe1PiPfMWoraSppFeiam3YjG7rIj8wxgTboeyrVNAU14jNWjBoI0oE7a1IAvvD6YSkTB0L6P46NR7XVJs2VBB9OVjv6kov4Rihnv9Czu1V2atkPo29wgL5HWOM8ND6SdBlN5ezwZ1eIhEyblvLWc3/BslMMRwOW17a7GxbSlcIK9jR2US9OKMI0BYKI8ymgn7MwVy2Xz+qZ8Q/mAnlUOqEpGeZKNqk1CCFauwR1CHwiFt6MJGQ/bDVV]]></req_info></xml>";
        $xmlArr = $this->xmlToArray($xmlInfo);
        $this->log(json_encode($xmlArr));
        $this->decryption($xmlArr);
        echo $this->returnInfo("SUCCESS", "OK");
    }

    protected function log($data, $fileName = 'wechatAppOrderRefund')
    {
        file_put_contents(CMF_ROOT . "data/log/{$fileName}" . '_' . date('Y-m-d') . '.txt', date('Y-m-d H:i:s') . ':' . $data . PHP_EOL, FILE_APPEND);
    }

    protected function xmlToArray($xmlStr)
    {
        $msg = array();
        $postStr = $xmlStr;
        $msg = (array)simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
        return $msg;
    }

    protected function returnInfo($type, $msg)
    {
        return "<xml><return_code><![CDATA[{$type}]]></return_code><return_msg><![CDATA[{$msg}]]></return_msg></xml>";
    }

    /**
     * 微信退款回调解密
     * @param $notifyArray
     * @return void
     */
    protected function decryption(array $notifyArray)
    {
        $req_info = $notifyArray['req_info'];
        $strB = base64_decode($req_info, true);
        $wx_key = $this->getMchKey($notifyArray['appid'], $notifyArray['mch_id']);
        $md5Key = strtolower(md5($wx_key));
        $decodeXml = openssl_decrypt($strB, 'aes-256-ecb', $md5Key, OPENSSL_RAW_DATA);
        $decodeArray = $this->xmlToArray($decodeXml);
        $this->log(json_encode($decodeArray));
        if (is_array($decodeArray)) {
            $this->ordeHandle($notifyArray, $decodeArray);
        }
    }


    /**
     *
     * @param $notifyArray 通知数据 json (未解密)
     * @param $decodeArray 已解密
     * @return void
     */
    public function ordeHandle($notifyArray, $decodeArray)
    {
        $orderno_pay = $decodeArray['out_trade_no'];//系统订单号
        $refund_order_id = $decodeArray['out_refund_no'];//退款单号

        $orderPrefix = explode('_', $orderno_pay)[0] ?? '';
        if ($orderPrefix == "store") {
            //店铺订单
            $MerstoreStoreOrdersModel = app()->make(MerstoreStoreOrdersModel::class);
            $MerstoreStoreOrdersModel::updateInfo([
                ['orderno_pay', '=', $orderno_pay],
                ['refund_order_id', '=', $refund_order_id],
            ], [
                'refund_result' => $notifyArray['return_code']
            ]);
        } else {
            //跑腿订单
            $OrdersModel = app()->make(OrdersModel::class);
            $OrdersModel::where([
                ['orderno', '=', $orderno_pay],
                ['refund_order_id', '=', $refund_order_id],
            ])->update([
                'refund_result' => $notifyArray['return_code']
            ]);
        }
    }

    /**
     * 获取对应的商户KEY
     * @param $appId
     * @param $mchId
     * @return void
     */
    protected function getMchKey($appId, $mchId = "")
    {
        $configpri = getConfigPri();

        $appidName = '';
        foreach ($configpri as $key => $value) {
            if ($appId == $value) {
                $appidName = $key;
                break;
            }
        }
        if (!$appidName) return "";
        $endNum = strrpos($appidName, '_');
        $cutOut = substr($appidName, 0, $endNum);
        $mchKey = $cutOut . '_key';
        return $configpri[$mchKey] ?? '';
    }
}