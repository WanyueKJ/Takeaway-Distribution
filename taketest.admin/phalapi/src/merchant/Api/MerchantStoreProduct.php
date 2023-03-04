<?php

namespace Merchant\Api;

use App\ApiException;
use Merchant\Domain\MerchantStoreProduct as MerchantStoreProductDomain;
use Merchant\Domain\MerchantStoreType as MerchantStoreTypeDomain;
use PhalApi\Api;

/**
 * (新-1)商品管理
 */
class MerchantStoreProduct extends Api
{
    public function getRules()
    {
        return array(
            'save' => array(
                'image' => array('name' => 'image', 'type' => 'string', 'default' => '', 'desc' => '商品图片格式:["admin/2022719/13a0f1d66a1fc7210b77.jpg","admin/2022719/13a0f1d66a1fc7210b77.jpg"]'),
                'name' => array('name' => 'name', 'type' => 'string', 'desc' => '商品名[中文]'),
                
                'price' => array('name' => 'price', 'type' => 'string', 'desc' => '价格[中文]'),
                'type_id' => array('name' => 'type_id', 'type' => 'string', 'desc' => '店铺类型(平台店铺分类)'),
                'store_type_id' => array('name' => 'store_type_id', 'type' => 'string', 'desc' => '店铺分类(店铺分类)'),
                'recommend' => array('name' => 'recommend', 'type' => 'string', 'desc' => '是否推荐商品 0不是(默认) 1是'),
                'repertory' => array('name' => 'repertory', 'type' => 'string', 'desc' => '超市-商品库存'),
                'max_repertory' => array('name' => 'max_repertory', 'type' => 'string', 'desc' => '超市-商品最大库存'),
                'day_repertory' => array('name' => 'day_repertory', 'type' => 'string', 'desc' => '超市-商品库存次日置满开关 0关 1开启'),
                'des' => array('name' => 'des', 'type' => 'string', 'desc' => '商品说明'),
                'attr' => array('name' => 'attr', 'type' => 'string', 'default' => '', 'desc' => '规格:[{"attr_name":"规格1(有子规格)","th_attr_name":"规格1(泰语)","price":"价格","children":[{"attr_name":"子规格1","th_attr_name":"子规格1(泰语)","price":"子规格1价格"},{"attr_name":"子规格2","th_attr_name":"子规格2(泰语)","price":"子规格2价格"}]},{"attr_name":"规格2(无子规格)","th_attr_name":"规格1(泰语)","price":"价格","children":[]}]'),
            ),

            'update' => array(
                'id' => array('name' => 'id', 'type' => 'string', 'default' => '', 'desc' => '商品ID'),
                'image' => array('name' => 'image', 'type' => 'string', 'default' => '', 'desc' => '商品图片格式:["admin/2022719/13a0f1d66a1fc7210b77.jpg","admin/2022719/13a0f1d66a1fc7210b77.jpg"]'),
                'name' => array('name' => 'name', 'type' => 'string', 'desc' => '商品名[中文]'),
              
                'price' => array('name' => 'price', 'type' => 'string', 'desc' => '价格[中文]'),
                'type_id' => array('name' => 'type_id', 'type' => 'string', 'desc' => '店铺类型(平台店铺分类)'),
                'store_type_id' => array('name' => 'store_type_id', 'type' => 'string', 'desc' => '店铺分类(店铺分类)'),
                'recommend' => array('name' => 'recommend', 'type' => 'string', 'desc' => '是否推荐商品 0不是(默认) 1是'),
                'repertory' => array('name' => 'repertory', 'type' => 'string', 'desc' => '超市-商品库存'),
                'des' => array('name' => 'des', 'type' => 'string', 'desc' => '商品说明'),
                'max_repertory' => array('name' => 'max_repertory', 'type' => 'string', 'desc' => '超市-商品最大库存'),
                'day_repertory' => array('name' => 'day_repertory', 'type' => 'string', 'desc' => '超市-商品库存次日置满开关 0关 1开启'),
                'has_attr' => array('name' => 'has_attr', 'type' => 'string', 'desc' => '是否有规格 '),
            ),
            'read' => array(
                'id' => array('name' => 'id', 'type' => 'string', 'desc' => '商品ID'),
            ),
            'status' => array(
                'id' => array('name' => 'id', 'type' => 'string', 'desc' => '商品ID'),
                'is_del' => array('name' => 'is_del', 'type' => 'string', 'desc' => '传1 删除商品'),
                'is_show' => array('name' => 'is_show', 'type' => 'string', 'desc' => '传1 上架 0下架'),
            ),
            'index' => array(
                'store_type_id' => array('name' => 'store_type_id', 'type' => 'string', 'desc' => '店铺分类id(0:推荐商品(没有二级分类))'),
                'p' => array('name' => 'p', 'type' => 'string', 'desc' => '页码'),
            ),
            'search' => array(
                'keywords' => array('name' => 'keywords', 'type' => 'string', 'desc' => '商品关键字'),
                'p' => array('name' => 'p', 'type' => 'string', 'desc' => '页码'),
            )
        );
    }


    /**
     * 商品搜索
     * @desc 商品搜索
     * @return int code 操作码，0表示成功
     * @return array info
     * @return string msg 提示信息
     */
    public function search()
    {
        $uid = \App\checkNull($this->uid);
        $token = \App\checkNull($this->token);
        $this->checkLogin($uid, $token);
        $keywords = \App\checkNull($this->keywords);
        $p = \App\checkNull($this->p);

        $MerchantStoreProductDomain = new MerchantStoreProductDomain();
        $res = $MerchantStoreProductDomain->getSearchList($uid, $keywords, $p);
        return $res;
    }

    /**
     * 商品列表
     * @desc 商品列表
     * @return int code 操作码，0表示成功
     * @return array info.use_name 商品名
     * @return array info.image 商品图
     * @return array info.repertory 商品库存
     * @return array info.use_price 商品价格
     * @return array info.is_show 商品是否商家 0:否 1:是
     * @return array info
     * @return string msg 提示信息
     */
    public function index()
    {
        $uid = \App\checkNull($this->uid);
        $token = \App\checkNull($this->token);
        $this->checkLogin($uid, $token);
        $store_type_id = \App\checkNull($this->store_type_id);
        $p = \App\checkNull($this->p);


//        $action = 'App.MerchantStoreProduct.index';
//        $date = date('Y-m-d H:i:s');
//        file_put_contents('./log.txt', var_export(compact('action','date','uid','token', 'store_type_id','p'), true) . PHP_EOL, FILE_APPEND);

        $MerchantStoreProductDomain = new MerchantStoreProductDomain();
        $res = $MerchantStoreProductDomain->getList($uid, $store_type_id, $p);
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

    /**
     * 修改提交商品信息
     * @desc 用于修改提交商品信息
     * @return int code 操作码，0表示成功
     * @return array info
     * @return string msg 提示信息
     */
    public function update()
    {
        $rs = array('code' => 0, 'msg' => '', 'info' => array());

        $uid = \App\checkNull($this->uid);
        $token = \App\checkNull($this->token);

        $checkToken = \App\checkMerchantToken($uid, $token);
        if ($checkToken == 700) {
            $rs['code'] = $checkToken;
            $rs['msg'] = \PhalApi\T('您的登陆状态失效，请重新登陆！');
            return $rs;
        }

        $id = \App\checkNull($this->id);
        $image = \App\checkNull($this->image);
        $name = \App\checkNull($this->name);
     
        $price = \App\checkNull($this->price);
        $type_id = \App\checkNull($this->type_id);
        $store_type_id = \App\checkNull($this->store_type_id);
        $recommend = \App\checkNull($this->recommend) ?? 0;
        $repertory = \App\checkNull($this->repertory);
        $des = \App\checkNull($this->des);
        $max_repertory = \App\checkNull($this->max_repertory);
        $day_repertory = \App\checkNull($this->day_repertory);
        $has_attr = \App\checkNull($this->has_attr);
        
//        $action = 'merchant.MerchantStoreProduct.update';
//        $date = date('Y-m-d H:i:s');
//        file_put_contents('./log.txt', var_export(compact('action', 'date', 'uid', 'token', 'id', 'image', 'name', 'th_name', 'price', 'type_id', 'store_type_id', 'recommend', 'repertory', 'des', 'max_repertory', 'day_repertory'), true) . PHP_EOL, FILE_APPEND);

        if (!$id) {
            $rs['code'] = 1000;
            $rs['msg'] = \PhalApi\T('信息错误');
            return $rs;
        }

        $MerchantStoreProductDomain = new MerchantStoreProductDomain();
        $rs = $MerchantStoreProductDomain->updateProduct($uid, $id, $image, $name, $price, $type_id, $store_type_id, $recommend, $repertory, $des, $max_repertory, $day_repertory,$has_attr);
        return $rs;
    }

    /**
     * 修改商品状态
     * @desc 用于修改删除,上下架商品
     * @return int code 操作码，0表示成功
     * @return array info
     * @return string msg 提示信息
     */
    public function status()
    {
        $rs = array('code' => 0, 'msg' => '', 'info' => array());

        $uid = \App\checkNull($this->uid);
        $token = \App\checkNull($this->token);

        $checkToken = \App\checkMerchantToken($uid, $token);
        if ($checkToken == 700) {
            $rs['code'] = $checkToken;
            $rs['msg'] = \PhalApi\T('您的登陆状态失效，请重新登陆！');
            return $rs;
        }
        $id = \App\checkNull($this->id);
        $is_del = \App\checkNull($this->is_del);
        $is_show = \App\checkNull($this->is_show);

        $MerchantStoreProductDomain = new MerchantStoreProductDomain();
        $res = $MerchantStoreProductDomain->changeStatus($uid, $id, $is_del, $is_show);
        return $res;
    }

    /**
     * 获取商品信息
     * @desc 用于获取商品信息
     * @return int code 操作码，0表示成功
     * @return array info
     * @return array info.use_name 商品名
     * @return array info.use_price 商品价格
     * @return array info.image[] 商品图片
     * @return array info.repertory 库存
     * @return array info.sales 销售
     * @return array info.top_type_name 店铺类型(平台分类)
     * @return array info.top_store_type_name 店铺分类(店铺分类)
     * @return string msg 提示信息
     */
    public function read()
    {
        $rs = array('code' => 0, 'msg' => '', 'info' => array());

        $uid = \App\checkNull($this->uid);
        $token = \App\checkNull($this->token);

        $checkToken = \App\checkMerchantToken($uid, $token);
        if ($checkToken == 700) {
            $rs['code'] = $checkToken;
            $rs['msg'] = \PhalApi\T('您的登陆状态失效，请重新登陆！');
            return $rs;
        }

        $id = \App\checkNull($this->id);
        if (!$id) {
            $rs['code'] = 1000;
            $rs['msg'] = \PhalApi\T('信息错误');
            return $rs;
        }

        $MerchantStoreProductDomain = new MerchantStoreProductDomain();

        $productInfo = $MerchantStoreProductDomain->getProduct($uid, $id);
        return $productInfo;
    }

    /**
     * 店铺保存商品
     * @desc 用于店铺提交保存商品
     * @return int code 操作码，0表示成功
     * @return array info
     * @return array info.name 店铺类型名[中文]

     * @return array info.use_name 当前语言包下应该使用的名字
     * @return string msg 提示信息
     */
    public function save()
    {
        $rs = array('code' => 0, 'msg' => '', 'info' => array());

        $uid = \App\checkNull($this->uid);
        $token = \App\checkNull($this->token);

        $checkToken = \App\checkMerchantToken($uid, $token);
        if ($checkToken == 700) {
            $rs['code'] = $checkToken;
            $rs['msg'] = \PhalApi\T('您的登陆状态失效，请重新登陆！');
            return $rs;
        }
        $image = \App\checkNull($this->image);
        $name = \App\checkNull($this->name);
      
        $price = \App\checkNull($this->price);
        $type_id = \App\checkNull($this->type_id);
        $store_type_id = \App\checkNull($this->store_type_id);
        $recommend = \App\checkNull($this->recommend) ?? 0;
        $repertory = \App\checkNull($this->repertory);
        $max_repertory = \App\checkNull($this->max_repertory);
        $day_repertory = \App\checkNull($this->day_repertory);
        $des = \App\checkNull($this->des);
        $attr = \App\checkNull($this->attr);

//        $LANG = LANG;
//        $action = 'MerchantStoreProduct.save';
//        file_put_contents('./log.txt', var_export(compact('action', 'uid', 'token', 'image', 'name', 'th_name', 'price', 'type_id', 'store_type_id', 'recommend', 'repertory', 'des', 'attr', "LANG"), true) . PHP_EOL, FILE_APPEND);

        $MerchantStoreProductDomain = new MerchantStoreProductDomain();
        $rs = $MerchantStoreProductDomain->addProduct($uid, $image, $name,  $price, $type_id, $store_type_id, $recommend, $repertory, $max_repertory, $day_repertory, $attr, $des);
        return $rs;

    }


}