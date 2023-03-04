<?php

namespace App\Api;

use PhalApi\Api;
use Rider\Domain\Slide as SlideDomain;
use App\Domain\MerchantStore as MerchantStoreDomain;
use App\Domain\MerchantStoreType as MerchantStoreTypeDomain;
use App\Domain\MerchantStoreProduct as MerchantStoreProductDomain;
use App\Domain\MerchantType as MerchantTypeDomain;

/**
 * (新-1)美食
 */
class FineFood extends Api
{
    public function getRules()
    {
        return array(
            'foodType' => array(
                'type_id' => array('name' => 'type_id', 'type' => 'string', 'desc' => '店铺类型id'),
                'level' => array('name' => 'level', 'type' => 'string', 'default' => 1, 'desc' => '如果当前分类下有子分类 表示向下查询多少级'),
            ),
            'home' => array(
                'lng' => array('name' => 'lng', 'type' => 'string', 'desc' => '当前用户经度'),
                'lat' => array('name' => 'lat', 'type' => 'string', 'desc' => '当前用户纬度'),
            ),
            'search' => array(
                'lng' => array('name' => 'lng', 'type' => 'string', 'desc' => '当前用户经度'),
                'lat' => array('name' => 'lat', 'type' => 'string', 'desc' => '当前用户纬度'),
                'overall' => array('name' => 'overall', 'type' => 'string', 'default' => '', 'desc' => '综合排序:按当天店铺浏览量倒叙'),
                'price' => array('name' => 'price', 'type' => 'string', 'default' => '', 'desc' => '价格排序:ASC正序(默认),DESC倒叙'),
                'distanc' => array('name' => 'distanc', 'type' => 'string', 'default' => '', 'desc' => '距离排序:ASC正序(默认)'),
                'evaluate' => array('name' => 'evaluate', 'type' => 'string', 'default' => '', 'desc' => '评价排序:ASC倒叙(默认)'),
                'keywords' => array('name' => 'keywords', 'type' => 'string', 'default' => '', 'desc' => '商品关键字'),
                'type_id' => array('name' => 'type_id', 'type' => 'string', 'default' => '1', 'desc' => '店铺类型'),
                'p' => array('name' => 'p', 'type' => 'string', 'default' => 1, 'desc' => '页码'),
            ),
            'foodTypeSearch' => array(
                'lng' => array('name' => 'lng', 'type' => 'string', 'desc' => '当前用户经度'),
                'lat' => array('name' => 'lat', 'type' => 'string', 'desc' => '当前用户纬度'),
                'type_id' => array('name' => 'type_id', 'type' => 'string', 'default' => '', 'desc' => '店铺分类'),
                'p' => array('name' => 'p', 'type' => 'string', 'default' => 1, 'desc' => '页码'),
                'keywords' => array('name' => 'keywords', 'type' => 'string', 'default' => '', 'desc' => '店铺关键字'),
                'recommend' => array('name' => 'recommend', 'type' => 'string', 'default' => '', 'desc' => '是否是推荐店铺 0否 1是 默认0'),
            ),
            'getStoreDetail' => array(
                'id' => array('name' => 'id', 'type' => 'string', 'require' => true, 'desc' => '美食店铺id'),
                'lng' => array('name' => 'lng', 'type' => 'string', 'desc' => '当前用户经度'),
                'lat' => array('name' => 'lat', 'type' => 'string', 'desc' => '当前用户纬度'),
            ),
            'getStoreType' => array(
                'id' => array('name' => 'id', 'type' => 'string', 'desc' => '店铺id'),
            ),
            'getClassOfFood' => array(
                'id' => array('name' => 'id', 'type' => 'string', 'desc' => '店铺id'),
            )
        );
    }





    /**
     * 获取美食店铺所有分类及分类下的美食
     * @desc 获取美食店铺美食列表
     * @return int code 操作码，0表示成功
     * @return array info
     * @return array info.use_name 商品名
     * @return array info.use_price 商品名价格
     * @return array info.evaluate 点赞最高的评价
     * @return array info.month_sales 月销量
     * @return array info.starts_rate 好评率
     * @return array info.image 商品图
     * @return array info.attr[] 商品规格信息
     * @return array info.attr.use_price 总价: 规格价格+商品价格
     * @return array info.cart_num 已加入购物车的数量
     * @return string msg 提示信息
     */
    public function getClassOfFood()
    {
        $rs = array('code' => 0, 'msg' => '', 'info' => array());

        $uid = \App\checkNull($this->uid);
        $token = \App\checkNull($this->token);
        $checkToken = \App\checkToken($uid, $token);
//        if ($checkToken == 700) {
//            $rs['code'] = $checkToken;
//            $rs['msg'] = \PhalApi\T('您的登陆状态失效，请重新登陆！');
//            return $rs;
//        }
        $id = \App\checkNull($this->id);

        $MerchantStoreProductDomain = new MerchantStoreProductDomain();

        $list = $MerchantStoreProductDomain->getClassOfFood($uid, $id);

        $rs['info'][] = $list;
        return $rs;
    }



    /**
     * 获取店铺分类
     * @desc 用于获取美食店铺店铺分类
     * @return int code 操作码，0表示成功
     * @return array info
     *
     * @return string msg 提示信息
     */
    public function getStoreType()
    {
        $rs = array('code' => 0, 'msg' => '', 'info' => array());

        $uid = \App\checkNull($this->uid);
        $token = \App\checkNull($this->token);
        $checkToken = \App\checkToken($uid, $token);

//        if ($checkToken == 700) {
//            $rs['code'] = $checkToken;
//            $rs['msg'] = \PhalApi\T('您的登陆状态失效，请重新登陆！');
//            return $rs;
//        }

        $id = \App\checkNull($this->id);
        $MerchantStoreTypeDomain = new MerchantStoreTypeDomain();
        $list = $MerchantStoreTypeDomain->getStoreTypeList($uid, $id);
        $rs['info'][] = $list;
        return $rs;
    }




    /**
     * 美食店铺详情
     * @desc 美食店铺详情
     * @return int code 操作码，0表示成功
     * @return array info
     * @return array info.name 店铺名
     * @return array info.banner 轮播图
     * @return array info.stars 星级评分
     * @return array info.month_sales 月销量
     * @return array info.time 配送时间
     * @return array info.is_like 是否喜欢(0否 >0时 是(删除时传此id))
     * @return array info.thumb 头像
     * @return array info.background 背景图
     * @return array info.operating_state 营业状态(0关闭 1开启)
     * @return array info.evaluate_count 评价数
     * @return array info.address 店铺地址
     * @return array info.phone 手机号
     * @return array info.open_date[] 营业日期
     * @return array info.open_time[ 营业时间
     * @return array info.coupon[] 优惠卷列表
     * @return array info.coupon.name 优惠卷名
     * @return array info.industry[] 商家资质
     * @return array info.industry.business_image 营业执照图片
     * @return array info.industry.license_image 许可证图片
     * @return array info.cart 购物车
     * @return array info.cart.count 购物车商品数量
     * @return array info.cart.price 购物车商品价格
     * @return array info.evaluate[] 评价统计
     * @return array info.evaluate.stars (美食-总体)
     * @return array info.evaluate.taste_star (美食-口味)
     * @return array info.evaluate.packaging_star (美食-包装)
     * @return array info.evaluate.distribution_star 配送满意度
     * @return array info.service[] 商家客服
     * @return array info.service.im_uid 商家客服im uid
     *
     * @return string msg 提示信息
     */
    public function getStoreDetail()
    {
        $rs = array('code' => 0, 'msg' => '', 'info' => array());

        $uid = \App\checkNull($this->uid);
        $token = \App\checkNull($this->token);

        $id = \App\checkNull($this->id);
        $lng = \App\checkNull($this->lng);
        $lat = \App\checkNull($this->lat);
        if(!$lng) $lng = 0;
        if(!$lat) $lat = 0;
        $MerchantStoreDomain = new MerchantStoreDomain();
        $storeDetail = $MerchantStoreDomain->getStoreDetail($uid, $id, $lng, $lat);
        return $storeDetail;
    }


    /**
     * 美食分类-美食店铺列表搜索
     * @desc 美食店铺列表搜索
     * @return int code 操作码，0表示成功
     * @return array info
     * @return array info.name 店铺名
     * @return array info.evaluate 商品评价
     * @return array info.sales 商品销量
     * @return array info.name 店铺名
     * @return array info.stars 店铺星级评分
     * @return array info.distance 距离
     * @return array info.type_name 店铺类型
     *
     * @return string msg 提示信息
     */
    public function foodTypeSearch()
    {
        $rs = array('code' => 0, 'msg' => '', 'info' => array());

        $uid = \App\checkNull($this->uid);
        $token = \App\checkNull($this->token);

        $lng = \App\checkNull($this->lng);
        $lat = \App\checkNull($this->lat);
        $type_id = \App\checkNull($this->type_id);
        $p = \App\checkNull($this->p);
        $keywords = \App\checkNull($this->keywords);
        $recommend = \App\checkNull($this->recommend);

        $MerchantStoreDomain = new MerchantStoreDomain();

        $list = [];
        if($lng && $lat){
            if($recommend){
                $list = $MerchantStoreDomain->getRecommend($lng, $lat,$type_id, 0, $p,20);
            }else{
                $list = $MerchantStoreDomain->getFoodTypeSearchList($uid, $lng, $lat, $type_id, $keywords, $p);
            }
        }
        $rs['info'][] = $list;
        return $rs;
    }


    /**
     * 美食分类-分类列表(平台分类)
     * @desc 获取店铺类型下的子类型
     * @return int code 操作码，0表示成功
     * @return array info
     * @return array info.use_name 店铺类型名
     * @return array info.use_thumb 店铺类型图标
     * @return string msg 提示信息
     */
    public function foodType()
    {
        $rs = array('code' => 0, 'msg' => '', 'info' => array());


        $type_id = \App\checkNull($this->type_id);
        $level = \App\checkNull($this->level);

        if ((int)$type_id <= 0) {
            $rs['code'] = 400;
            $rs['msg'] = \PhalApi\T('参数错误！');
            return $rs;
        }
        if ((int)$level <= 0) {
            $level = 2;
        }

        $MerchantStoreTypeDomain = new MerchantTypeDomain();//美食下的分类
        $list = $MerchantStoreTypeDomain->getOwnChildren($type_id, $level);
        $rs['info'][] = $list;
        return $rs;
    }

    /**
     * 美食首页数据
     * @desc 用于获取美食 首页数据
     * @return int code 操作码，0表示成功
     * @return array info
     * @return array info.banner 轮播图
     *
     * @return array info.distance_store[] 附近美食
     * @return array info.distance_store.distance 距离(千米)
     * @return array info.distance_store.stars 星级评分
     * @return array info.distance_store.type_name 店铺分类名称
     * @return array info.distance_store.thumb 缩略图
     * @return array info.distance_store.comment 评论
     * @return array info.distance_store.comment_count 评论数量
     *
     * @return array info.recommend[] 推荐店铺
     * @return array info.middle_banner[] 中部轮播图
     * @return array info.middle_banner[title] 标题
     * @return array info.middle_banner[description] 描述
     *
     * @return array info.store_type 美食下店铺类型
     * @return array info.store_type.font_color 字体颜色
     * @return array info.store_type.use_name 根据语言包返回的对应的语言名字
     * @return array info.store_type.thumb 图标
     * @return array info.store_type.background 图标背景
     *
     * @return string msg 提示信息
     */
    public function home()
    {
        $rs = array('code' => 0, 'msg' => '', 'info' => array());

        $lng = \App\checkNull($this->lng);
        $lat = \App\checkNull($this->lat);


        $SlideDomain = new SlideDomain();
        $banner = $SlideDomain->getSlide(1);//轮播图

        $middle_banner = $SlideDomain->getSlide(14);//中部轮播图
        $middle_banner = array_chunk($middle_banner,2)[0] ?? [];

        $MerchantStoreDomain = new MerchantStoreDomain();

        $MerchantStoreTypeDomain = new MerchantTypeDomain();//美食下的分类
        $store_type = $MerchantStoreTypeDomain->getChildren(1, 2);

        if ($lng && $lat) {
            $distance_store = $MerchantStoreDomain->getSelectListByDistance($lng, $lat, 1, 10);
        }
        $rs['info'][] = compact('banner','middle_banner', 'store_type', 'distance_store');
        return $rs;
    }

    /**
     * 美食商品搜索
     * @desc 用于获取搜索美食商品搜索
     * @return int code 操作码，0表示成功
     * @return array info.use_name 商品名
     * @return array info.starts 总评价数
     * @return array info.good_starts 总好评价数
     * @return array info.use_price 价格
     * @return array info.monthly_sales 月销量
     *
     * @return array info.store 商品所属店铺
     * @return array info.store.use_name 店铺名
     * @return array info.store.stars 评分
     * @return array info.store.up_to_send 起送价格
     * @return array info.store.time 预计送达时间
     * @return array info.store.distance 距离
     *
     * @return string msg 提示信息
     */
    public function search()
    {
        $rs = array('code' => 0, 'msg' => '', 'info' => array());

        $lng = \App\checkNull($this->lng);
        $lat = \App\checkNull($this->lat);
        $type_id = 1;

        $overall = \App\checkNull($this->overall);
        $price = \App\checkNull($this->price);
        $distanc = \App\checkNull($this->distanc);
        $evaluate = \App\checkNull($this->evaluate);
        $keywords = \App\checkNull($this->keywords);
        $page = \App\checkNull($this->p);


        if ($type_id <= 0) {
            $rs['code'] = 1001;
            $rs['msg'] = \PhalApi\T('无效参数');
            return $rs;
        }
        if ($overall && !in_array(strtolower($overall), ['asc', 'desc'])) {
            $rs['code'] = 1001;
            $rs['msg'] = \PhalApi\T('无效参数');
            return $rs;
        }
        if ($price && !in_array(strtolower($price), ['asc', 'desc'])) {
            $rs['code'] = 1001;
            $rs['msg'] = \PhalApi\T('无效参数');
            return $rs;
        }
        if ($distanc && !in_array(strtolower($distanc), ['asc', 'desc'])) {
            $rs['code'] = 1001;
            $rs['msg'] = \PhalApi\T('无效参数');
            return $rs;
        }
        if ($evaluate && !in_array(strtolower($evaluate), ['asc', 'desc'])) {
            $rs['code'] = 1001;
            $rs['msg'] = \PhalApi\T('无效参数');
            return $rs;
        }
        $list = [];
        if ($lng && $lat) {
            $MerchantStoreProductDomain = new MerchantStoreProductDomain();
            $list = $MerchantStoreProductDomain->getCateSearchList($lng, $lat, $type_id ?? 1, $overall, $price, $distanc, $evaluate, $keywords, $page);
        }
        $rs['info'][] = $list;
        return $rs;
    }
}
