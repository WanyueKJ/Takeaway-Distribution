<?php

namespace Merchant\Api;

use Merchant\Domain\MerchantStoreProductAttr as MerchantStoreProductAttrDomain;
use PhalApi\Api;

/**
 * (新-1)商品规格
 */
class MerchantStoreProductAttr extends Api
{
    public function getRules()
    {
        return array(
            'save' => array(
                'product_id' => array('name' => 'product_id', 'type' => 'string', 'desc' => '商品id'),
                'attr' => array('name' => 'attr', 'type' => 'string', 'default' => '', 'desc' => '规格:[{"is_main":"是否主规格 0否 1是","attr_name":"规格1(有子规格)","th_attr_name":"规格1( )","price":"价格","children":[{"is_main":"是否主规格 0否 1是","attr_name":"子规格1","th_attr_name":"子规格1( )","price":"子规格1价格"},{"attr_name":"子规格2","th_attr_name":"子规格2( )","price":"子规格2价格"}]},{"attr_name":"规格2(无子规格)","th_attr_name":"规格1( )","price":"价格","children":[]}]'),
            ),

            'update' => array(
                'product_id' => array('name' => 'product_id', 'type' => 'string', 'desc' => '商品id'),
                'attr' => array('name' => 'attr', 'type' => 'string', 'default' => '', 'desc' => '规格:{"id":"1","attr_name":"规格1(有子规格111)","th_attr_name":"规格12( )","price":"0.01"}'),
            ),

            'read' => array(
                'product_id' => array('name' => 'product_id', 'type' => 'string', 'desc' => '商品ID'),
            ),
            'delete' => array(
                'product_attr_id' => array('name' => 'product_attr_id', 'type' => 'string', 'desc' => '商品规格ID'),
            ),

            'readAttr' => array(
                'id' => array('name' => 'id', 'type' => 'string', 'desc' => '规格id'),
            ),

        );
    }

    /**
     * 删除规格信息
     * @desc 用于删除规格信息
     * @return int code 操作码，0表示成功
     * @return string msg 提示信息
     */
    public function delete(){
        $rs = array('code' => 0, 'msg' => '', 'info' => array());

        $uid = \App\checkNull($this->uid);
        $token = \App\checkNull($this->token);

        $checkToken = \App\checkMerchantToken($uid, $token);
        if ($checkToken == 700) {
            $rs['code'] = $checkToken;
            $rs['msg'] = \PhalApi\T('您的登陆状态失效，请重新登陆！');
            return $rs;
        }

        $product_attr_id = \App\checkNull($this->product_attr_id);
        $MerchantStoreProductAttrDomain = new MerchantStoreProductAttrDomain();

        $res = $MerchantStoreProductAttrDomain->deleteAttr($uid, $product_attr_id);
        return $res;
    }


    /**
     * 获取单一规格信息
     * @desc 用于获取单一规格信息
     * @return int code 操作码，0表示成功
     * @return array info
     * @return array info.use_attr_name 规格名
     * @return array info.use_price 规格价格
     * @return string msg 提示信息
     */
    public function readAttr()
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

        $MerchantStoreProductAttrDomain = new MerchantStoreProductAttrDomain();

        $productInfoAttr = $MerchantStoreProductAttrDomain->getAttr($uid, $id);
        return $productInfoAttr;
    }

    /**
     * 修改商品规格信息
     * @desc 用于修改商品规格信息
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

        $product_id = \App\checkNull($this->product_id);
        $attr = \App\checkNull($this->attr);
        if (!$product_id) {
            $rs['code'] = 1000;
            $rs['msg'] = \PhalApi\T('信息错误');
            return $rs;
        }
        $action = 'merchant.MerchantStoreProductAttr.update';
        $date = date('Y-m-d H:i:s');
        file_put_contents('./log.txt', var_export(compact('action','date','product_id','attr'), true) . PHP_EOL, FILE_APPEND);

        $MerchantStoreProductDomain = new MerchantStoreProductAttrDomain();
        $rs = $MerchantStoreProductDomain->updateProductAttr($uid, $product_id, $attr);
        return $rs;
    }




    /**
     * 获取商品所有规格信息
     * @desc 用于获取商品所有规格信息
     * @return int code 操作码，0表示成功
     * @return array info
     * @return array info.use_attr_name 规格名
     * @return array info.use_price 规格价格
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

        $product_id = \App\checkNull($this->product_id);
        if (!$product_id) {
            $rs['code'] = 1000;
            $rs['msg'] = \PhalApi\T('信息错误');
            return $rs;
        }

        $MerchantStoreProductAttrDomain = new MerchantStoreProductAttrDomain();
        $productInfoAttr = $MerchantStoreProductAttrDomain->getProductAttr($uid, $product_id);
        return $productInfoAttr;
    }

    /**
     * 店铺添加商品规格
     * @desc 用于店铺添加商品规格
     * @return int code 操作码，0表示成功
     * @return array info
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
        $product_id = \App\checkNull($this->product_id);
        $attr = \App\checkNull($this->attr);

//        $action = 'merchant.MerchantStoreProductAttr.update';
//        $date = date('Y-m-d H:i:s');
//        file_put_contents('./log.txt', var_export(compact('action','date','product_id','attr'), true) . PHP_EOL, FILE_APPEND);

        
        $MerchantStoreProductAttrDomain = new MerchantStoreProductAttrDomain();
        $rs = $MerchantStoreProductAttrDomain->addProductAttr($uid, $product_id, $attr);
        return $rs;

    }


}