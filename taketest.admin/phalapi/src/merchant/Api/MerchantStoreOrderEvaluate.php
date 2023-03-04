<?php

namespace Merchant\Api;

use App\ApiException;
use Merchant\Domain\MerchantStoreOrderEvaluate as MerchantStoreOrderEvaluateDomain;
use PhalApi\Api;

/**
 * (新-1)订单评论
 */
class MerchantStoreOrderEvaluate extends Api
{
    public function getRules()
    {
        return array(
            'replySave' => array(
                'id' => array('name' => 'id', 'type' => 'string', 'desc' => '评论id'),
                'merchant_reply_content' => array('name' => 'merchant_reply_content', 'type' => 'string', 'desc' => '回复内容'),
            ),
            'index' => array(
                'reply' => array('name' => 'reply', 'type' => 'string', 'desc' => '回复状态(0:全部 1:已回复 2:未回复)'),
                'evaluate' => array('name' => 'evaluate', 'type' => 'string', 'desc' => '是否好评(0:全部 1:好评 2:差评)'),
                'content' => array('name' => 'content', 'type' => 'string', 'desc' => '是否有内容(0:全部 1:有内容 2:无内容)'),
                'month' => array('name' => 'month', 'type' => 'string', 'desc' => '时间(1:一个月 2:二个月 3:三个月)'),
                'p' => array('name' => 'p', 'type' => 'string', 'desc' => '页码'),
            ),
            'detail' => array(
                'id' => array('name' => 'id', 'type' => 'string', 'desc' => '评论id'),
            ),

        );
    }


    /**
     * 店铺订单评价详情
     * @desc 店铺订单评价详情
     * @return int code 操作码，0表示成功
     * @return array info.user[] 评论用户信息
     * @return array info.order[] 订单信息
     * @return array info.order.order_id 订单号
     * @return array info.order.total_num 订单商品数量
     * @return array info.order.content 订单评论
     * @return array info.order.is_reply 是否回复 0:否 1:是
     * @return array info.order.cart_info[] 订单购物车信息
     * @return array info.order.cart_info.cart_num 商品购买数量
     * @return array info.order.cart_info.product 订单购物车商品信息
     * @return array info.order.merchant_reply_content 商家回复内容
     * @return array info.order.merchant_reply_time 商家回复时间
     * @return array info.order.like 评论点赞数
     * @return array info.id
     * @return array info[1][].evaluate[] 评分总体统计
     * @return array info[1][].evaluate.stars (美食-总体),(服务-总体),(超市-总体)
     * @return array info[1][].evaluate.taste_star (美食-口味),(服务-态度),(超市-质量)
     * @return array info[1][].evaluate.packaging_star (美食-包装),(服务-质量),(超市-包装)
     * @return array info[1][].evaluate.distribution_star 配送满意度
     * @return array info[1][].product_reply 赞 踩的商品
     * @return array info[1][].product_reply[-1] 踩,差评的商品
     * @return array info[1][].product_reply[0] 中等商品
     * @return array info[1][].product_reply[-] 好评的商品
     * @return array info.order.id 订单id
     * @return array info.order.order_id 订单号
     * @return array info.order.total_num 商品数量
     * @return array info.rider_evaluate[] 骑手信息
     * @return array info.rider_evaluate.end_time 订单配送时间
     * @return array info.product[] 订单商品信息
     * @return string msg 提示信息
     */
    public function detail()
    {
        $uid = \App\checkNull($this->uid);
        $token = \App\checkNull($this->token);

        $this->checkLogin($uid, $token);
        $id = \App\checkNull($this->id);
        if (!$id) throw new ApiException(\PhalApi\T('参数错误'));
        $MerchantStoreOrderEvaluateDomain = new MerchantStoreOrderEvaluateDomain();
        $res = $MerchantStoreOrderEvaluateDomain->getDetail($uid, $id);

        return $res;
    }


    /**
     * 商家回复
     * @desc 商家回复
     * @return int code 操作码，0表示成功
     * @return array info
     * @return string msg 提示信息
     */
    public function replySave()
    {
        $uid = \App\checkNull($this->uid);
        $token = \App\checkNull($this->token);

        $this->checkLogin($uid, $token);

        $id = \App\checkNull($this->id);
        $merchant_reply_content = \App\checkNull($this->merchant_reply_content);

        $MerchantStoreOrderEvaluateDomain = new MerchantStoreOrderEvaluateDomain();
        $res = $MerchantStoreOrderEvaluateDomain->businessReply($uid, $id, $merchant_reply_content);
        return $res;
    }


    /**
     * 店铺订单评价列表
     * @desc 店铺订单评价列表
     * @return int code 操作码，0表示成功
     * @return array info.user[] 评论用户信息
     * @return array info.order[] 订单信息
     * @return array info.order.order_id 订单号
     * @return array info.order.total_num 订单商品数量
     * @return array info.order.content 订单评论
     * @return array info.order.is_reply 是否回复 0:否 1:是
     * @return array info.order.cart_info[] 订单商品,规格,数量信息
     * @return array info.order.merchant_reply_content 商家回复内容
     * @return array info.order.like 评论点赞数
     * @return array info.id 商家回复是传此id
     * @return array info.overall_star (美食-总体),(服务-总体),(超市-总体)
     * @return array info.taste_star (美食-口味),(服务-态度),(超市-质量)
     * @return array info.packaging_star (美食-包装),(服务-质量),(超市-包装)
     * @return array info.distribution_star 配送满意度评分
     * @return array info.pics 图片
     * @return array info.is_product_reply (服务.超市类 是否展示有商品被单独评价 1是 0不是)
     * @return array info.video 视频
     * @return array info.tags  点赞,评价,满意度
     * @return array info[1][].count 数量
     * @return array info[1][].evaluate[] 评分总体统计
     * @return array info[1][].evaluate.stars (美食-总体),(服务-总体),(超市-总体)
     * @return array info[1][].evaluate.taste_star (美食-口味),(服务-态度),(超市-质量)
     * @return array info[1][].evaluate.packaging_star (美食-包装),(服务-质量),(超市-包装)
     * @return array info[1][].evaluate.distribution_star 配送满意度
     * @return array info[1][].product_reply 赞 踩的商品
     * @return array info[1][].product_reply[-1] 踩,差评的商品
     * @return array info[1][].product_reply[0] 中等商品
     * @return array info[1][].product_reply[-] 好评的商品
     * @return string msg 提示信息
     */
    public function index()
    {

        $uid = \App\checkNull($this->uid);
        $token = \App\checkNull($this->token);

        $this->checkLogin($uid, $token);
        $reply = \App\checkNull($this->reply);
        $evaluate = \App\checkNull($this->evaluate);
        $content = \App\checkNull($this->content);
        $month = \App\checkNull($this->month);
        $p = \App\checkNull($this->p);

        $MerchantStoreOrderEvaluateDomain = new MerchantStoreOrderEvaluateDomain();
        $res = $MerchantStoreOrderEvaluateDomain->getEvaluateList($uid, $reply, $evaluate,$content,$month, $p);
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