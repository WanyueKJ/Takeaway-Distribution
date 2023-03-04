<?php

namespace App\Api;

use App\ApiException;
use PhalApi\Api;
use App\Domain\MerchantStoreOrderEvaluate as MerchantStoreOrderEvaluateDomain;

/**
 * (新-1)店铺订单评价
 */
class MerchantStoreOrderEvaluate extends Api
{
    public function getRules()
    {
        return array(
            'getEvaluate' => array(
                'id' => array('name' => 'id', 'type' => 'string', 'desc' => '订单id')
            ),
            'save' => array(
                'id' => array('name' => 'id', 'type' => 'string', 'require' => true, 'desc' => '订单id'),

                'rider_id' => array('name' => 'rider_id', 'type' => 'string', 'desc' => '骑手id'),
                'rider_star' => array('name' => 'rider_star', 'type' => 'string', 'desc' => '骑手评分 1-5'),
                'rider_comment' => array('name' => 'rider_comment', 'type' => 'string', 'desc' => '订单'),
                'rider_anonymous' => array('name' => 'rider_anonymous', 'type' => 'string', 'desc' => '是否匿名 0否 1是'),

                'order_id' => array('name' => 'order_id', 'type' => 'string', 'desc' => '评价订单id'),
                'order_pics' => array('name' => 'order_pics', 'type' => 'string', 'desc' => '评论图 Json: ["admin/2022719/13a0f1d66a1fc7210b77.jpg","admin/2022719/13a0f1d66a1fc7210b77.jpg"]'),
                'order_video' => array('name' => 'order_video', 'type' => 'string', 'desc' => '评论视频 Json: {"url":""}'),
                'order_comment' => array('name' => 'comment', 'type' => 'string', 'desc' => '订单评论内容'),
                'order_overall_star' => array('name' => 'order_overall_star', 'type' => 'string', 'desc' => '星级(美食-总体),(服务-总体),(超市-总体)'),
                'order_taste_star' => array('name' => 'order_taste_star', 'type' => 'string', 'desc' => '星级(美食-口味),(服务-态度),(超市-质量)'),
                'order_packaging_star' => array('name' => 'order_packaging_star', 'type' => 'string', 'desc' => '星级(美食-包装),(服务-质量),(超市-包装)'),
                'order_anonymous' => array('name' => 'order_anonymous', 'type' => 'string', 'desc' => '是否匿名 0否 1是'),

                'product_json' => array('name' => 'product_json', 'type' => 'string', 'desc' => 'JSON: [{"id":"商品id","product_attr_id":"商品规格id","comment":"商品评价","tags":"赞：（1：赞，-1：踩，0：默认），满意：（1：满意，-1：不满意 ，0：中等），评价：（1：好评，0：中评，差评：-1）","pics":["admin\/2022719\/13a0f1d66a1fc7210b77.jpg"],"video":{"url":""}}]'),
            ),
            'getNumber' => array(
                'store_id' => array('name' => 'store_id', 'type' => 'string', 'require' => true, 'desc' => '店铺id'),
            ),
            'orderIndex' => array(
                'store_id' => array('name' => 'store_id', 'type' => 'string', 'require' => true, 'desc' => '店铺id'),
                'type' => array('name' => 'type', 'type' => 'string', 'desc' => '评价类型 0:全部(默认) 1:最新 2:有图 3:好评 4:差评 5:中评'),
                'p' => array('name' => 'p', 'type' => 'string', 'desc' => '页码'),
            ),
            'setOrderLike' => array(
                'id' => array('name' => 'id', 'type' => 'string', 'desc' => '评论id'),
                'status' => array('name' => 'status', 'type' => 'string', 'desc' => '是否喜欢 0:不喜欢 1:喜欢'),
            ),

            /**
             * 商品
             */
            'index' => array(
                'store_id' => array('name' => 'store_id', 'type' => 'string', 'desc' => '店铺id'),
                'product_id' => array('name' => 'product_id', 'type' => 'string', 'desc' => '商品id'),
                'type' => array('name' => 'type', 'type' => 'string', 'desc' => '评价类型 默认全部 (赞：(1：赞， -1：踩，)，满意：(1：满意, 0：中等, -1：不满意),评价：(1：好评,0：中评,差评：-1))  100:最新 101:有图'),
                'p' => array('name' => 'p', 'type' => 'string', 'desc' => '页码'),
            ),
            'setLike' => array(
                'id' => array('name' => 'id', 'type' => 'string', 'desc' => '评论id'),
                'status' => array('name' => 'status', 'type' => 'string', 'desc' => '是否喜欢 0:不喜欢 1:喜欢'),
            ),
            'getProductNumber' => array(
                'store_id' => array('name' => 'store_id', 'type' => 'string', 'require' => true, 'desc' => '店铺id'),
                'product_id' => array('name' => 'product_id', 'type' => 'string', 'require' => true, 'desc' => '商品id'),
            ),
        );
    }


    /**
     * [商品]评价数量统计
     * @desc 获取全部,好评,差评等的数量
     * @return int info.all 总的
     * @return int info.new 最新
     * @return int info.figure 有图
     * @return int info.good 好评 赞 满意
     * @return int info.medium 中评 '' 中等
     * @return int info.negative 差评 踩  不满意
     * @return int|null
     * @throws ApiException
     */
    public function getProductNumber(){
        $uid = \App\checkNull($this->uid);
        $token = \App\checkNull($this->token);

        $store_id = \App\checkNull($this->store_id);
        $product_id = \App\checkNull($this->product_id);

        if ($store_id <= 0) throw new ApiException(\PhalApi\T('参数错误！'), 400);

        $MerchantStoreOrderEvaluateDomain = new MerchantStoreOrderEvaluateDomain();
        $res = $MerchantStoreOrderEvaluateDomain
            ->getProductNumber($uid, $store_id,$product_id);
        return $res;

    }



    /**
     * [订单]评价点赞
     * @desc 订单评价点赞
     * @return array info.status 是否点赞 0:是 1否
     * @return array info.count 点赞总数数
     * @throws ApiException
     */
    public function setOrderLike(){

        $uid = \App\checkNull($this->uid);
        $token = \App\checkNull($this->token);
        $this->checkLogin($uid, $token);

        $id = \App\checkNull($this->id);
        $status = \App\checkNull($this->status);

//        $action = 'App.MerchantStoreOrderEvaluate.setOrderLike';
//        file_put_contents('./log.txt', var_export(compact('action','uid',$id, $status), true) . PHP_EOL, FILE_APPEND);

        if (!in_array($status, [0, 1])) throw new ApiException('参数错误', 400);
        $MerchantStoreOrderEvaluateDomain = new MerchantStoreOrderEvaluateDomain();
        $res = $MerchantStoreOrderEvaluateDomain->setOrderReplyLike($uid, $id, $status);
        return $res;
    }


    /**
     * [订单]订单评价列表
     * @desc 订单评价列表
     * @return array info.comment 评价内容
     * @return array info.addtime 评价时间
     * @return array info.is_anonymous 是否匿名 0:是 1否
     * @return array info.is_reply 是否回复 0:是 1否
     * @return array info.like 点赞数
     * @return array info.is_like 是否点赞
     * @return array info.overall_star 评分
     * @return array info.video 视频
     * @return array info.pics 图片
     * @return array info.overall_star_txt 文字评分
     * @return array info.user[] 评分用户(匿名时 空数据)
     * @return array info.reply[] 商家回复(无回复时时 空数据)
     * @throws ApiException
     */
    public function orderIndex()
    {

        $uid = \App\checkNull($this->uid);
        $token = \App\checkNull($this->token);
//        $this->checkLogin($uid, $token);

        $store_id = \App\checkNull($this->store_id);
        $type = \App\checkNull($this->type);
        $p = \App\checkNull($this->p);

        if (!$store_id) throw new ApiException(\PhalApi\T('参数错误！'), 400);
        if (!in_array($type, [0, 1, 2, 3, 4,5])) throw new ApiException(\PhalApi\T('参数错误！'), 400);

        $MerchantStoreOrderEvaluateDomain = new MerchantStoreOrderEvaluateDomain();
        $res = $MerchantStoreOrderEvaluateDomain->getOrderReplyList($uid, $store_id, $type, $p);
        return $res;
    }
    /**
     * [商品]评价点赞
     * @desc 商品评价点赞
     * @return array info.status 是否点赞 0:是 1否
     * @return array info.count 点赞总数数
     * @throws ApiException
     */
    public function setLike()
    {
        $uid = \App\checkNull($this->uid);
        $token = \App\checkNull($this->token);
        $this->checkLogin($uid, $token);

        $id = \App\checkNull($this->id);
        $status = \App\checkNull($this->status);

//        $action = 'App.MerchantStoreOrderEvaluate.setLike';
//        file_put_contents('./log.txt', var_export(compact('action','uid',$id, $status), true) . PHP_EOL, FILE_APPEND);

        if (!in_array($status, [0, 1])) throw new ApiException('参数错误', 400);
        $MerchantStoreOrderEvaluateDomain = new MerchantStoreOrderEvaluateDomain();
        $res = $MerchantStoreOrderEvaluateDomain->setReplyLike($uid, $id, $status);
        return $res;
    }


    /**
     * [商品]商品评价列表
     * @desc 商品评价列表
     * @return array info.comment 评价内容
     * @return array info.addtime 评价时间
     * @return array info.is_anonymous 是否匿名 0:是 1否
     * @return array info.is_reply 是否回复 0:是 1否
     * @return array info.like 点赞数
     * @return array info.tags 赞,满意度,评价 (赞：（1：赞，-1：踩，0：默认），满意：（1：满意，-1：不满意 ，0：中等），评价：（1：好评，0：中评，差评：-1）)
     * @return array info.is_like 是否点赞
     * @return array info.overall_star 评分
     * @return array info.overall_star_txt 文字评分
     * @return array info.user[] 评分用户(匿名时 空数据)
     * @return array info.reply[] 商家回复(无回复时时 空数据)
     * @throws ApiException
     */
    public function index()
    {
        $uid = \App\checkNull($this->uid);
        $token = \App\checkNull($this->token);
        $MerchantStoreOrderEvaluateDomain = new MerchantStoreOrderEvaluateDomain();

        $product_id = \App\checkNull($this->product_id);
        $type = \App\checkNull($this->type);
        $store_id = \App\checkNull($this->store_id);
        $p = \App\checkNull($this->p);


//        $action = 'App.MerchantStoreOrderEvaluate.index';
//        file_put_contents('./log.txt', var_export(compact('action','uid','product_id', 'type','store_id'), true) . PHP_EOL, FILE_APPEND);

        if (!$store_id) throw new ApiException(\PhalApi\T('参数错误！'), 400);
//        if (!in_array($type, [-1,0, 1, 100, 101])) throw new ApiException(\PhalApi\T('参数错误！'), 400);
        $res = $MerchantStoreOrderEvaluateDomain->getProductReplyList($uid, $store_id, $product_id, $type, $p);
        return $res;
    }


    /**
     * [订单]评价统计
     * @desc 获取全部,好评,差评等的数量
     * @return array info.all 总的
     * @return array info.new 最新
     * @return array info.figure 有图
     * @return array info.good 好评(满意)
     * @return int info.medium 中评 '' 中等
     * @return array info.poor 差评(不满意)
     * @return array|null
     * @throws ApiException
     */
    public function getNumber()
    {
        $uid = \App\checkNull($this->uid);
        $token = \App\checkNull($this->token);

        $store_id = \App\checkNull($this->store_id);
        if ($store_id <= 0) {
            throw new ApiException(\PhalApi\T('参数错误！'), 400);
        }
        $MerchantStoreOrderEvaluateDomain = new MerchantStoreOrderEvaluateDomain();
        $res = $MerchantStoreOrderEvaluateDomain
            ->getNumber($uid, $store_id);
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
     * [订单]提交评价订单
     * @desc 提交评价订单
     * @return array|null
     * @throws \App\ApiException
     */
    public function save()
    {

        $uid = \App\checkNull($this->uid);
        $token = \App\checkNull($this->token);

        $this->checkLogin($uid, $token);

        $id = \App\checkNull($this->id);

        $rider_id = \App\checkNull($this->rider_id);
        $rider_star = \App\checkNull($this->rider_star);
        $rider_comment = \App\checkNull($this->rider_comment);
        $rider_anonymous = \App\checkNull($this->rider_anonymous);

        $order_id = \App\checkNull($this->order_id);
        $order_pics = \App\checkNull($this->order_pics);
        $order_video = \App\checkNull($this->order_video);
        $order_comment = \App\checkNull($this->order_comment);
        $order_overall_star = \App\checkNull($this->order_overall_star);
        $order_taste_star = \App\checkNull($this->order_taste_star);
        $order_packaging_star = \App\checkNull($this->order_packaging_star);
        $order_anonymous = \App\checkNull($this->order_anonymous);

        $product_json = \App\checkNull($this->product_json);
        $MerchantStoreOrderEvaluateDomain = new MerchantStoreOrderEvaluateDomain();

//        $action = 'App.MerchantStoreOrderEvaluate.save';
//        file_put_contents('./log.txt', var_export(compact('action','uid','id','rider_id', 'rider_star','rider_comment','rider_anonymous','order_id','order_pics','order_video','order_comment','order_overall_star','order_taste_star','order_packaging_star','order_anonymous','product_json'), true) . PHP_EOL, FILE_APPEND);

        $res = $MerchantStoreOrderEvaluateDomain
            ->addEvaluate($uid, $id, $rider_id, $rider_star, $rider_comment, $rider_anonymous, $order_id, $order_pics,$order_video, $order_comment, $order_overall_star, $order_taste_star, $order_packaging_star, $order_anonymous, $product_json);
        return $res;
    }

    /**
     * [订单]订单评价展示的信息
     * @desc 获取订单评价时展示的信息
     * @return int code 操作码，0表示成功
     * @return array info.rider[] 配送货骑手信息(有数据就展示)
     * @return array info.rider.id 骑手id
     * @return array info.rider.avatar 骑手头像
     * @return array info.rider.end_time 送达时间
     * @return array info.rider.shipping_type_txt 配送方式
     * @return array info.rider.user_nickname 骑手昵称
     * @return array info.order[] 订单信息
     * @return array info.order.id 订单id
     * @return array info.order.top_type_id 订单中店铺类型
     * @return array info.store[] 店铺信息
     * @return array info.store.id 店铺id
     * @return array info.store.thumb 店铺图标
     * @return array info.product_cart[] 订单购买商品信息
     * @return array info.product_cart[0].use_name 商品名
     * @return array info.product_cart[0].use_thumb 商品图
     * @return string msg 提示信息
     */
    public function getEvaluate()
    {

        $uid = \App\checkNull($this->uid);
        $token = \App\checkNull($this->token);

        $this->checkLogin($uid, $token);

        $id = \App\checkNull($this->id);
        $MerchantStoreOrderEvaluateDomain = new MerchantStoreOrderEvaluateDomain();
        $res = $MerchantStoreOrderEvaluateDomain->getEvaluate($uid, $id);

        return $res;
    }

}