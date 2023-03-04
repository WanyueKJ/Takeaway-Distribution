<?php

namespace App\Api;

use App\ApiException;
use App\Domain\MerchantStore as MerchantStoreDomain;
use PhalApi\Api;
use App\Domain\MerchantStoreProduct as MerchantStoreProductDomain;

/**
 * (改-1)首页
 */
class Home extends Api
{

    public function getRules()
    {
        return array(
            'getConfig' => array(),
            'searchStore' => array(
                'type_id' => array('name' => 'type_id', 'type' => 'string', 'desc' => '店铺类型id 0:全部 1:美食 3:服务 5:超市'),
                'lng' => array('name' => 'lng', 'type' => 'string', 'desc' => '当前用户经度'),
                'lat' => array('name' => 'lat', 'type' => 'string', 'desc' => '当前用户纬度'),
                'p' => array('name' => 'p', 'type' => 'string', 'desc' => '页码'),
            ),
            'search' => array(
                'lng' => array('name' => 'lng', 'type' => 'string', 'require' => true, 'desc' => '当前用户经度'),
                'lat' => array('name' => 'lat', 'type' => 'string', 'require' => true, 'desc' => '当前用户纬度'),
                'overall' => array('name' => 'overall', 'type' => 'string', 'default' => '', 'desc' => '综合排序:按当天店铺浏览量倒叙'),
                'price' => array('name' => 'price', 'type' => 'string', 'default' => '', 'desc' => '价格排序:ASC正序(默认),DESC倒叙'),
                'distanc' => array('name' => 'distanc', 'type' => 'string', 'default' => '', 'desc' => '距离排序:ASC正序(默认)'),
                'evaluate' => array('name' => 'evaluate', 'type' => 'string', 'default' => '', 'desc' => '评价排序:ASC倒叙(默认)'),
                'keywords' => array('name' => 'keywords', 'type' => 'string', 'default' => '', 'desc' => '商品关键字'),
                'p' => array('name' => 'p', 'type' => 'string', 'default' => 1, 'desc' => '页码'),
            ),
        );
    }



    protected function checkLogin($uid, $token)
    {
        $checkToken = \App\checkToken($uid, $token);
        if ($checkToken == 700) {
            throw new ApiException(\PhalApi\T('您的登陆状态失效，请重新登陆！'), 700);
        }
    }

    /**
     * (新-1)搜索所有店铺(不包括找店分类下)的商品 返回格式与美食搜索一致
     * @desc 搜索所有店铺(不包括找店分类下)的商品
     * @return int code 操作码，0表示成功
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
     * @return array info.store.top_type_id 店铺总类型 1:美食 2:闪送 3:服务 4:找店 5:超市 6:生鲜 7:送药 8:家政
     * @return string msg 提示信息
     */
    public function search()
    {
        $rs = array('code' => 0, 'msg' => '', 'info' => array());

        $uid = \App\checkNull($this->uid);
        $token = \App\checkNull($this->token);
        $checkToken = \App\checkToken($uid, $token);

        $overall = \App\checkNull($this->overall);
        $price = \App\checkNull($this->price);
        $distanc = \App\checkNull($this->distanc);
        $evaluate = \App\checkNull($this->evaluate);
        $keywords = \App\checkNull($this->keywords);
        $page = \App\checkNull($this->p);
        $lng = \App\checkNull($this->lng);
        $lat = \App\checkNull($this->lat);

        if (!$lng || $lng == '') $lng = 0;
        if (!$lat || $lat == '') $lat = 0;

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
            $list = $MerchantStoreProductDomain->getHomeSearch($lng, $lat, $overall, $price, $distanc, $evaluate, $keywords, $page);
        }
        $rs['info'][] = $list;
        return $rs;
    }


    /**
     * (新-1)首页下部店铺;列表
     * @desc 首页下部店铺
     * @return int code 操作码，0表示成功
     * @return array info
     * @return string info.use_name 店铺名
     * @return string info.stars 评分
     * @return string info.thumb 店铺图标
     * @return string info.month_sales 月销量
     * @return string info.distance 距离
     * @return string info.comment 评论
     * @return string msg 提示信息
     */
    public function searchStore()
    {
        $rs = array('code' => 0, 'msg' => '', 'info' => array());


        $type_id = \App\checkNull($this->type_id);
        $lng = \App\checkNull($this->lng);
        $lat = \App\checkNull($this->lat);
        $p = \App\checkNull($this->p);
        if (!in_array($type_id, [0, 1, 3, 5])) {
            throw new ApiException('类型错误');
        }

        $MerchantStoreDomain = new MerchantStoreDomain();//店铺

        $distance_store = [];
        if ($lng && $lat) {
            $distance_store = $MerchantStoreDomain->getRecommendStoreList($lng, $lat, $type_id, $p, 20);
        }

        $rs['info'][] = $distance_store;
        return $rs;
    }





    /**
     * 网站信息
     * @desc 用于获取网站基本信息
     * @return int code 操作码，0表示成功
     * @return array info
     * @return string info[0].site_name 网站名称
     * @return string info[0].name_coin 消费币名称
     * @return string info[0].apk_ver APK版本号
     * @return string info[0].apk_des APK更新说明
     * @return string info[0].apk_url APK下载链接
     * @return string info[0].ipa_ver IPA版本号
     * @return string info[0].ios_shelves IPA上架版本号
     * @return string info[0].ipa_des IPA更新说明
     * @return string info[0].ipa_url IPA下载链接
     * @return string info[0].service_url 53客服链接  需解密
     * @return string info[0].chatserver websocket地址  需解密
     * @return array info[0].share_type 分享方式
     * @return string msg 提示信息
     */
    public function getConfig()
    {
        $rs = array('code' => 0, 'msg' => '', 'info' => array());
        $info = [];
        $info_pub = \App\getConfigPub();
        $info['site_name'] = $info_pub['site_name'];
        $info['apk_ver'] = $info_pub['apk_ver'];
        $info['apk_url'] = $info_pub['apk_url'];
        $info['apk_des'] = $info_pub['apk_des'];
        $info['ipa_ver'] = $info_pub['ipa_ver'];
        $info['ios_shelves'] = $info_pub['ios_shelves'];
        $info['ipa_url'] = $info_pub['ipa_url'];
        $info['ipa_des'] = $info_pub['ipa_des'];
        $info['small_shelves'] = $info_pub['small_shelves'];
        $info['share_title'] = $info_pub['share_title'];
        $info['share_des'] = $info_pub['share_des'];
        $info['share_img'] = \App\get_upload_path($info_pub['share_img']);

        $info_pri = \App\getConfigPri();

        $info['share_type'] = [];
        $info['service_url'] = \App\string_encryption($info_pri['service_url']);

        $info['chatserver'] = \App\string_encryption($info_pri['chatserver']);

        $rs['info'][0] = $info;

        return $rs;
    }

}
