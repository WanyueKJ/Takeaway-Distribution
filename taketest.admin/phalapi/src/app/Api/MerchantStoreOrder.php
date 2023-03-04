<?php

namespace App\Api;

use App\ApiException;
use App\Domain\MerchantStoreOrder as MerchantStoreOrderDomain;
use PhalApi\Api;

/**
 * (新-1)店铺订单
 */
class MerchantStoreOrder extends Api
{
    public function getRules()
    {
        return array(
            'save' => array(
                'order_key' => array('name' => 'order_key', 'type' => 'string', 'desc' => '订单key'),
                'pay_type' => array('name' => 'pay_type', 'type' => 'string', 'desc' => '支付方式 1支付宝2微信APP 3微信小程序支付 4 微信h5支付  5微信公众号支付 6苹果支付'),
                'openid' => array('name' => 'openid', 'type' => 'string', 'desc' => 'openid'),
            ),
            'search' => array(
                'keywords' => array('name' => 'keywords', 'type' => 'string', 'desc' => '店铺/商品关键词'),
                'top_type_id' => array('name' => 'top_type_id', 'type' => 'string', 'desc' => '订单类型 0:全部 1:美食 2:闪送 3:服务 4:找店 5:超市 6:生鲜 7:送药 8:家政'),
                'p' => array('name' => 'p', 'type' => 'string', 'desc' => '页码'),
            ),
            'again' => array(
                'id' => array('name' => 'id', 'type' => 'string', 'desc' => '订单id'),
            ),
            'read' => array(
                'id' => array('name' => 'id', 'type' => 'string', 'desc' => '订单id'),
            ),
            'reminder' => array(
                'id' => array('name' => 'id', 'type' => 'string', 'desc' => '订单id'),
                'content' => array('name' => 'content', 'type' => 'string', 'desc' => '催单内容'),
            ),
            'getToEvaluate' => array(
                'p' => array('name' => 'p', 'type' => 'string', 'desc' => '页码'),
            ),
            'submitOrder' => array(
                'rider_id' => array('name' => 'rider_id', 'type' => 'string', 'desc' => '骑手id'),
                'orderid' => array('name' => 'orderid', 'type' => 'string', 'desc' => '单号'),
                'order_type' => array('name' => 'order_type', 'type' => 'string', 'desc' => '订单类型(0:跑腿订单 1:店铺订单)'),
            ),
            'cancelToBeEvaluated' => array(
            ),

            'aliAppOrder' => array(
                'data' => array('name' => 'data', 'type' => 'string', 'desc' => ''),
            ),
            'wechatAppOrder' => array(
                'data' => array('name' => 'data', 'type' => 'string', 'desc' => ''),
            ),
            'wechatSmallOrder' => array(
                'data' => array('name' => 'data', 'type' => 'string', 'desc' => ''),
            ),
            'pay' => array(
                'id' => array('name' => 'id', 'type' => 'string', 'desc' => ''),
                'pay_type' => array('name' => 'pay_type', 'type' => 'string', 'desc' => '支付方式 1支付宝 2微信APP 3微信小程序支付 4 微信h5支付  5微信公众号支付 6苹果支付'),
                'openid' => array('name' => 'openid', 'type' => 'string', 'desc' => ''),
            ),
            'cancel' => array(
                'id' => array('name' => 'id', 'type' => 'string', 'desc' => '')
            ),

        );
    }


    /**
     * 取消待支付订单
     * @desc 取消待支付订单
     * @return array
     * @throws ApiException
     */
    public function cancel(){
        $uid = \App\checkNull($this->uid);
        $token = \App\checkNull($this->token);
        $this->checkLogin($uid, $token);
        $id = \App\checkNull($this->id);
        if (!$id) throw new ApiException(\PhalApi\T('参数错误'));
        $MerchantStoreOrderDomain = new MerchantStoreOrderDomain();
        $res = $MerchantStoreOrderDomain->cancelOrder($uid, $id);
        return $res;
    }

    /**
     * 待支付订单支付
     * @desc 待支付订单支付
     * @return array
     * @throws ApiException
     */
    public function pay(){

        $uid = \App\checkNull($this->uid);
        $token = \App\checkNull($this->token);
        $id = \App\checkNull($this->id);
        $pay_type = \App\checkNull($this->pay_type);
        $openid = \App\checkNull($this->openid);

        $this->checkLogin($uid, $token);

        if($pay_type > 0 && !in_array($pay_type,[1,2,3])){
            throw new ApiException(\PhalApi\T('支付方式错误'));
        }
        if(!$id){
            throw new ApiException(\PhalApi\T('订单号错误'));
        }
        if(($pay_type == 3) && !$openid){
            throw new ApiException(\PhalApi\T('参数有误:openid'));
        }

        $MerchantStoreOrderDomain = new MerchantStoreOrderDomain();
        $res = $MerchantStoreOrderDomain->orderPay($uid, $id,$pay_type,$openid);
        return $res;
    }

    /**
     * 清空待评价订单
     * @desc 清空待评价订单
     * @return array
     * @throws ApiException
     */
    public function cancelToBeEvaluated(){
        $rs = array('code' => 0, 'msg' => \PhalApi\T('操作成功'), 'info' => array());

        $uid = \App\checkNull($this->uid);
        $token = \App\checkNull($this->token);
        $this->checkLogin($uid, $token);

        $MerchantStoreOrderDomain = new MerchantStoreOrderDomain();
        $MerchantStoreOrderDomain->cancelToBeEvaluated($uid);
        return $rs;
    }



    /**
     * 保存订单骑手信息
     * @desc 保存订单骑手信息
     * @return int code 操作码，0表示成功
     * @return string msg 提示信息
     */
    public function submitOrder()
    {
        $uid = \App\checkNull($this->uid);
        $token = \App\checkNull($this->token);

        $this->checkLogin($uid, $token);
        $rider_id = \App\checkNull($this->rider_id);
        if(!$rider_id) throw new ApiException(\PhalApi\T('骑手id不能为空'),600);
        $orderid = \App\checkNull($this->orderid);
        if(!$orderid) throw new ApiException(\PhalApi\T('订单号不能为空'));

        //(0:跑腿订单 1:店铺订单)
        $order_type = \App\checkNull($this->order_type);
        if(!in_array($order_type,[0,1])) throw new ApiException(\PhalApi\T('订单类型错误'),600);

        $MerchantStoreOrderDomain = new MerchantStoreOrderDomain();
        $res = $MerchantStoreOrderDomain->submitOrder($uid,$rider_id,$orderid,$order_type);
        return $res;
    }

    /**
     * 待评价订单
     * @desc 待评价订单
     * @return array info.order_type 订单类型(0:跑腿订单 1:店铺订单)
     * @return array info.title_txt 左上角文字
     * @return array info.status_txt 右上角文字
     * @return array info.total_num 订单商品数量(order_type为1时存在)
     * @return array info.pay_price 订单支付价格
     * @return array info.addtime 订单下单时间
     * @return array info.image 图标(order_type为1时为店铺图标 其余为空)
     * @return array info.t_name 收货地址
     * @return array info.top_type_id 店铺类型 店铺总类型 1:美食 2:闪送 3:服务 4:找店 5:超市 6:生鲜 7:送药 8:家政
     * @return int code 操作码，0表示成功
     * @return string msg 提示信息
     */
    public function getToEvaluate()
    {
        $uid = \App\checkNull($this->uid);
        $token = \App\checkNull($this->token);

        $this->checkLogin($uid, $token);
        $p = \App\checkNull($this->p);

        $MerchantStoreOrderDomain = new MerchantStoreOrderDomain();
        $res = $MerchantStoreOrderDomain->getUnionOrderList($uid, $p);
        return $res;
    }

    protected function checkLogin($uid, $token)
    {
        $checkToken = \App\checkToken($uid, $token);
        if ($checkToken == 700) {
            throw new ApiException(\PhalApi\T('您的登陆状态失效，请重新登陆！'), 700);
        }
    }

    /**
     * 订单催单
     * @desc 订单催单
     * @return int code 操作码，0表示成功
     * @return string msg 提示信息
     */
    public function reminder()
    {
        $rs = array('code' => 0, 'msg' => '', 'info' => array());

        $uid = \App\checkNull($this->uid);
        $token = \App\checkNull($this->token);

        $checkToken = \App\checkToken($uid, $token);
        if ($checkToken == 700) {
            $rs['code'] = $checkToken;
            $rs['msg'] = \PhalApi\T('您的登陆状态失效，请重新登陆！');
            return $rs;
        }
        $id = \App\checkNull($this->id);
        $content = \App\checkNull($this->content);
        $MerchantStoreOrderDomain = new MerchantStoreOrderDomain();

        $res = $MerchantStoreOrderDomain->setReminder($uid, $id, $content);
        return $res;

    }




    /**
     * 订单详情
     * @desc 订单详情
     * @return int code 操作码，0表示成功
     * @return int is_evaluate 订单是否评价 0:否 1:是
     * @return array info.id 订单id
     * @return array info.order_id 订单号
     * @return array info.add_time 订单下单时间时间
     * @return array info.pay_type_txt 支付方式
     * @return array info.show_verify 是否展示核销码
     * @return array info.show_reminder 是否展示催单
     * @return array info.shipping_type 1:外卖配送 ，2:门店自提 3上门服务
     * @return array info.verify_code 核销码
     * @return array info.code 完成码
     * @return array info.end_time 结束时间(送达,提货,结束时间)
     * @return array info.count_down 待付款倒计时(距离过期还多少秒)
     * @return array info.shipping_type_txt 配送服务
     * @return array info.pay_price 订单支付金额
     * @return array info.freight_price 配送费用
     * @return array info.service_time 服务(送达时间)
     * @return array info.address[] 送货地址
     * @return array info.des 订单订单说明
     * @return array info.des 订单订单说明
     * @return array info.im[]  店铺客服信息
     * @return array info.rider_im[]  店铺骑手IM信息
     * @return array info.rider[]  店铺骑手信息
     * @return array info.thumbs  取件照
     * @return array info.pickup[]  自提提货地点信息
     * @return array info.pickup.pickup_date  自提时间
     * @return array info.pickup.pickup_phone  自提联系电话
     * @return array info.pickup.name  自提点名字
     * @return array info.pickup.adress  自提点地址
     * @return array info.pickup.doorplate  自提点门牌号
     * @return array info.store[]  店铺信息
     * @return array info.store['top_type_id'] 店铺类型
     * @return array info.store.phone  店铺电话
     * @return array info.store.use_name  店铺电话
     * @return array info.store.banner  店铺轮播图
     * @return array info.store.cart_info[]  订单商品
     * @return array info.store.cart_info.product[]  订单商品信息
     * @return array info.store.cart_info.attr[]  订单商品规格信息
     * @return array info.store.cart_info.use_price  商品价格
     * @return array info.store.cart_info.more_product_attr  美食多规格
     * @return string msg 提示信息
     */
    public function read()
    {
        $rs = array('code' => 0, 'msg' => '', 'info' => array());

        $uid = \App\checkNull($this->uid);
        $token = \App\checkNull($this->token);

        $checkToken = \App\checkToken($uid, $token);
        if ($checkToken == 700) {
            $rs['code'] = $checkToken;
            $rs['msg'] = \PhalApi\T('您的登陆状态失效，请重新登陆！');
            return $rs;
        }

        $id = \App\checkNull($this->id);

//        $action = 'App.MerchantStoreOrder.read';
//        $date = date('Y-m-d H:i:s');
//        file_put_contents('./log.txt', var_export(compact('action', 'date', 'id'), true) . PHP_EOL, FILE_APPEND);

        $MerchantStoreOrderDomain = new MerchantStoreOrderDomain();
        $res = $MerchantStoreOrderDomain->getDetail($uid, $id);
        return $res;
    }


    /**
     * 再来一单
     * @desc 再来一单(把当前订单的商品加入到购物车中)
     * @return int code 操作码，0表示成功
     * @return string msg 提示信息
     */
    public function again()
    {
        $rs = array('code' => 0, 'msg' => '', 'info' => array());

        $uid = \App\checkNull($this->uid);
        $token = \App\checkNull($this->token);

        $checkToken = \App\checkToken($uid, $token);
        if ($checkToken == 700) {
            $rs['code'] = $checkToken;
            $rs['msg'] = \PhalApi\T('您的登陆状态失效，请重新登陆！');
            return $rs;
        }
        $id = \App\checkNull($this->id);
        $MerchantStoreOrderDomain = new MerchantStoreOrderDomain();
        $res = $MerchantStoreOrderDomain->againOrder($uid, $id);
        return $res;
    }

    /**
     * 店铺订单搜索
     * @desc 店铺订单搜索
     * @return int code 操作码，0表示成功
     * @return int.id 订单id
     * @return int.pay_price 订单支付金额
     * @return int.status_txt 订单状态 (0 待付款 1:已付款(待接单)  2:待配送  3:配送中 4:已完成 5:退款 6:已备货 7.已取消)
     * @return int.is_evaluate 订单是否评价 0:否 1:是
     * @return int.des 订单详情
     * @return int.total_num 订单商品数量
     * @return int.store[] 店铺信息
     * @return int.store.use_name 店铺名
     * @return int.store.thumb 休息状态(营业状态 0打样 1营业)
     * @return int.store.operating_state 店铺图标
     * @return int.store.product[] 订单商品信息
     * @return int.store.product.use_name 订单商品名
     * @return int.store.product.image 订单商品图
     * @return string msg 提示信息
     */
    public function search()
    {
        $rs = array('code' => 0, 'msg' => '', 'info' => array());

        $uid = \App\checkNull($this->uid);
        $token = \App\checkNull($this->token);

        $checkToken = \App\checkToken($uid, $token);
        if ($checkToken == 700) {
            $rs['code'] = $checkToken;
            $rs['msg'] = \PhalApi\T('您的登陆状态失效，请重新登陆！');
            return $rs;
        }
        $keywords = \App\checkNull($this->keywords);
        $type_id = \App\checkNull($this->top_type_id);
        $p = \App\checkNull($this->p);

        $MerchantStoreOrderDomain = new MerchantStoreOrderDomain();
        $res = $MerchantStoreOrderDomain->searchOrder($uid, $keywords, $type_id, $p);
        return $res;
    }

    /**
     * 店铺下单
     * @desc 店铺下单
     * @return int code 操作码，0表示成功
     * @return string msg 提示信息
     */
    public function save()
    {
        $rs = array('code' => 0, 'msg' => '', 'info' => array());

        $uid = \App\checkNull($this->uid);
        $token = \App\checkNull($this->token);
        $pay_type = \App\checkNull($this->pay_type);
        $openid = \App\checkNull($this->openid);

        if(!in_array($pay_type,[1,2])){
            throw new ApiException(\PhalApi\T('支付方式错误'));
        }

        $checkToken = \App\checkToken($uid, $token);
        if ($checkToken == 700) {
            $rs['code'] = $checkToken;
            $rs['msg'] = \PhalApi\T('您的登陆状态失效，请重新登陆！');
            return $rs;
        }
        $order_key = \App\checkNull($this->order_key);
        $source = \App\checkNull($this->source);
        $MerchantStoreOrderDomain = new MerchantStoreOrderDomain();
        $res = $MerchantStoreOrderDomain->createOrder($uid, $order_key, $source,$pay_type,$openid);
        return $res;
    }

    public function aliAppOrder(){
        $data = \App\checkNull($this->data);
        $dataArr = json_decode($data,true);

        $MerchantStoreOrderDomain = new MerchantStoreOrderDomain();

        $MerchantStoreOrderDomain->AliAppStorePaySuccess($dataArr);
    }


    public function wechatAppOrder()
    {
        $data = \App\checkNull($this->data);

        $dataArr = json_decode($data, true);
        $MerchantStoreOrderDomain = new MerchantStoreOrderDomain();
        $MerchantStoreOrderDomain->wechatAppStorePaySuccess($dataArr);
    }

    public function wechatSmallOrder()
    {
        $data = \App\checkNull($this->data);
        $dataArr = json_decode($data, true);
        $MerchantStoreOrderDomain = new MerchantStoreOrderDomain();
        $MerchantStoreOrderDomain->wechatSmallStorePaySuccess($dataArr);
    }

}
