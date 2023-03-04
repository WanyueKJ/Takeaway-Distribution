<?php

namespace Merchant\Api;

use App\ApiException;
use PhalApi\Api;
use Merchant\Domain\MerchantStoreOrder as MerchantStoreOrderDomain;

/**
 * (新-1)店铺订单
 */
class MerchantOrder extends Api
{
    public function getRules()
    {
        return array(
            'index' => array(
                'type' => array('name' => 'type', 'type' => 'string', 'desc' => '类型 (0 待付款 1:待接单 2:待配送 3:配送中 4:已完成 5:退款 6:已备货(自提单)'),
                'p' => array('name' => 'p', 'type' => 'string', 'desc' => '页码'),
            ),
            'getNumber' => array(),
            'detail' => array(
                'id' => array('name' => 'id', 'type' => 'string', 'desc' => '订单id'),
            ),

            'refundOrder' => array(
                'id' => array('name' => 'id', 'type' => 'string', 'desc' => '订单退款'),
            ),
            'takeOrders'=>array(
                'id' => array('name' => 'id', 'type' => 'string', 'desc' => '订单id'),
            ),
            'cancelOrder'=>array(
                'id' => array('name' => 'id', 'type' => 'string', 'desc' => '订单id'),
            ),

            'getuserOrder' => array(
                'users_id' => array('name' => 'users_id', 'type' => 'string', 'desc' => '用户id'),
            ),
        );
    }


    /**
     * 获取该用户在自己店铺中最新的一条订单号
     * @desc 店铺订单打印数据
     * @return int code 操作码，0表示成功
     */
    public function getUserOrder(){
        $uid = \App\checkNull($this->uid);
        $token = \App\checkNull($this->token);
        $this->checkLogin($uid, $token);
        $users_id = \App\checkNull($this->users_id);
        $MerchantStoreOrderDomain = new MerchantStoreOrderDomain();
        $res = $MerchantStoreOrderDomain->getUserOrder($uid,$users_id);
        return $res;
    }



    /**
     * 订单接单
     * @return void
     */
    public function takeOrders(){
        $uid = \App\checkNull($this->uid);
        $token = \App\checkNull($this->token);

        $this->checkLogin($uid, $token);
        $id = \App\checkNull($this->id);
        if (!$id) throw new ApiException(\PhalApi\T('参数错误'));

        $MerchantStoreOrderDomain = new MerchantStoreOrderDomain();
        $res = $MerchantStoreOrderDomain->takeOrders($uid, $id);
        return $res;
    }

    /**
     * 订单取消
     * @return void
     */
    public function cancelOrder(){
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
     * 订单退款
     * @return void
     */
    public function refundOrder()
    {
        $uid = \App\checkNull($this->uid);
        $token = \App\checkNull($this->token);

        $this->checkLogin($uid, $token);
        $id = \App\checkNull($this->id);
        if (!$id) throw new ApiException(\PhalApi\T('参数错误'));
        $MerchantStoreOrderDomain = new MerchantStoreOrderDomain();
        $res = $MerchantStoreOrderDomain->refundOrder($uid, $id);
        return $res;
    }


    /**
     * 店铺订单数详情
     * @desc 店铺订单数详情
     * @return int code 操作码，0表示成功
     * @return array info
     * @return array info.users[] 顾客信息
     * @return array info.address[] 顾客地址信息
     * @return array info.users_im[] 顾客IM信息
     * @return array info.product[] 订单商品信息
     * @return array info.product['more_product_attr'] 订单美食多规格信息
     * @return array info.pay_price 订单支付信息
     * @return array info.freight_price 订单配送费
     * @return array info.add_time 下单时间
     * @return array info.order_id 订单编号
     * @return array info.shipping_type 订单类型 1=外卖配送 ，2=门店自提 3上门服务
     * @return array info.shipping_type_txt 订单配送说明
     * @return array info.status_txt 订单状态说明
     * @return array info.pay_type_txt 订单支付方式说明
     * @return array info.verify_code 自提商品核销码
     * @return array info.reminder_content 催单内容
     * @return array info.rider 骑手信息
     * @return array info.rider.statue_txt 骑手配送状态
     * @return array info.order_evaluate[] 订单的评价信息
     * @return string msg 提示信息
     */
    public function detail()
    {
        $uid = \App\checkNull($this->uid);
        $token = \App\checkNull($this->token);
        $this->checkLogin($uid, $token);
        $id = \App\checkNull($this->id);
        if (!$id) throw new ApiException(\PhalApi\T('参数错误'));
        $MerchantStoreOrderDomain = new MerchantStoreOrderDomain();
        $res = $MerchantStoreOrderDomain->getDetail($uid, $id);
        return $res;
    }


    /**
     * 店铺订单数统计
     * @desc 用于获取订单-(营业额_订单数)
     * @return int code 操作码，0表示成功
     * @return array info
     * @return array info.type1 订单数 待接单
     * @return array info.type2 订单数 待配送
     * @return array info.type3 订单数 配送中
     * @return array info.type4 订单数 已完成
     * @return array info.type5 订单数 退款
     * @return array info.type6 订单数 自提单
     * @return string msg 提示信息
     */
    public function getNumber()
    {
        $uid = \App\checkNull($this->uid);
        $token = \App\checkNull($this->token);
        $this->checkLogin($uid, $token);
        $MerchantStoreOrderDomain = new MerchantStoreOrderDomain();
        $res = $MerchantStoreOrderDomain->getStoreNumber($uid);
        return $res;
    }


    /**
     * 外卖订单
     * @desc 外卖订单列表
     * @return int code 操作码，0表示成功
     * @return array info
     * @return array info.total_num 订单数数量
     * @return array info.pay_price 订单支付价格
     * @return array info.status_txt 订单状态(右上角)
     * @return array info.delivery_time 送达时间(左上角)
     * @return array info.users[] 下单用户信息
     * @return array info.users_im[] 下单用户IM信息
     * @return array info.address[] 下单用户地址信息
     * @return array info.rider[] 订单骑手信息(可能为空)
     * @return array info.reminder_count 是否催单 0:未催单 大于1:已催单
     * @return array info.show_receie 是否展示待接单按钮
     * @return array info.show_refund 是否展示退款按钮
     * @return array info.show_cancel 是否展示取消按钮
     * @return array info.show_evaluate 是否展示待评价按钮
     * @return array info.order_evaluate[] 订单的评价信息
     * @return string msg 提示信息
     */
    public function index()
    {
        $uid = \App\checkNull($this->uid);
        $token = \App\checkNull($this->token);
        $this->checkLogin($uid, $token);
        $type = \App\checkNull($this->type);
        $p = \App\checkNull($this->p);
        if (!in_array($type, [1, 2, 3, 4, 5, 6])) {
            throw new ApiException(\PhalApi\T('类型错误'));
        }
        $MerchantStoreOrderDomain = new MerchantStoreOrderDomain();
        $res = $MerchantStoreOrderDomain->getOrderList($uid, $type, $p);
        return $res;
    }

    /**
     * 检测登录状态
     * @param $uid
     * @param $token
     * @return void
     * @throws ApiException
     */
    protected function checkLogin($uid, $token)
    {
        $checkToken = \App\checkMerchantToken($uid, $token);
        if ($checkToken == 700) {
            throw new ApiException(\PhalApi\T('您的登陆状态失效，请重新登陆！'), 700);
        }
    }
}
