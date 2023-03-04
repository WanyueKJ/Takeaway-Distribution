<?php

namespace Merchant\Api;

use Merchant\Domain\MerchantStoreProduct as MerchantStoreProductDomain;
use Merchant\Domain\MerchantStoreType as MerchantStoreTypeDomain;
use PhalApi\Api;

/**
 * (新-1)店铺分类
 */
class MerchantStoreType extends Api
{
    public function getRules()
    {
        return array(
            'save' => array(
                'pid' => array('name' => 'pid', 'type' => 'string', 'desc' => '父分类ID 默认:0'),
                'name' => array('name' => 'name', 'type' => 'string', 'desc' => '商品名[中文]'),
            
                'thumb' => array('name' => 'thumb', 'type' => 'string', 'desc' => '分类图标'),
            ),
            'update' => array(
                'id' => array('name' => 'id', 'type' => 'string', 'desc' => 'id'),
                'name' => array('name' => 'name', 'type' => 'string', 'desc' => '商品名[中文]'),
              
            ),
            'read' => array(
                'id' => array('name' => 'id', 'type' => 'string', 'desc' => 'id'),
            ),
            'delete' => array(
                'id' => array('name' => 'id', 'type' => 'string', 'desc' => 'id'),
            ),
            'index' => array(
                'id' => array('name' => 'id', 'type' => 'string', 'desc' => '分类id 默认0:表示全部分类 大于0:获取当前id下的分类'),
                'level' => array('name' => 'level', 'type' => 'string', 'desc' => '分类层级 默认1:表示获取一级 一共为2级'),
                'is_tree' => array('name' => 'is_tree', 'type' => 'string', 'desc' => '默认:0 是否已tree方式返回数据'),
            )
        );
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
        $name = \App\checkNull($this->name);
    

        if (!$id) {
            $rs['code'] = 1000;
            $rs['msg'] = \PhalApi\T('信息错误');
            return $rs;
        }

        $MerchantStoreProductDomain = new MerchantStoreTypeDomain();
        $update = [
            'name' => $name,
          
        ];
        $res = $MerchantStoreProductDomain->updateOne(['id = ?' => $id],$update);
        $rs['msg'] = \PhalApi\T('修改成功');
        return $rs;
    }

    /**
     * 获取分类列表信息
     * @desc 获取分类列表信息
     * @return int code 操作码，0表示成功
     * @return array info
     * @return string msg 提示信息
     */
    public function index()
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
        $level = \App\checkNull($this->level);
        $is_tree = \App\checkNull($this->is_tree);

        $MerchantStoreTypeDomain = new MerchantStoreTypeDomain();

        $list = $MerchantStoreTypeDomain->getList($uid, $id, $is_tree, $level);

        $rs['info'][] = $list;
        return $rs;
    }

    /**
     * 删除分类信息
     * @desc 删除分类信息
     * @return int code 操作码，0表示成功
     * @return array info
     * @return string msg 提示信息
     */
    public function delete()
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

        $MerchantStoreTypeDomain = new MerchantStoreTypeDomain();

        $res = $MerchantStoreTypeDomain->deleteOne(['id = ? ' => $id]);

        $rs['msg'] = '删除成功';
        $rs['info'][] = $res;
        return $rs;
    }

    /**
     * 获取分类信息
     * @desc 用于获取分类信息
     * @return int code 操作码，0表示成功
     * @return array info
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

        $MerchantStoreTypeDomain = new MerchantStoreTypeDomain();

        $typeInfo = $MerchantStoreTypeDomain->getOne(['id = ?' => $id]);
        $typeInfo['thumb'] = \App\get_upload_path($typeInfo['thumb']);
        $rs['info'][] = $typeInfo;
        return $rs;
    }

    /**
     * 店铺保存分类
     * @desc 用于店铺提交保存分类
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
        $name = \App\checkNull($this->name);
      
        if (!$name) {
            $rs['code'] = $checkToken;
            $rs['msg'] = \PhalApi\T('请填写分类名');
            return $rs;
        }
        $MerchantStoreTypeDomain = new MerchantStoreTypeDomain();

        $pid = \App\checkNull($this->pid);
        $thumb = \App\checkNull($this->thumb);

        $loginInfo = \App\getcaches("merchant_token_{$uid}");
        $store_id = $loginInfo['store']['id'] ?? 0;
        if (!$store_id) {
            $rs['code'] = 995;
            $rs['msg'] = \PhalApi\T('店铺信息错误');
            return $rs;
        }
        $level = 1;
        if ($pid > 0) {
            $pidInfo = $MerchantStoreTypeDomain->getOne(['id = ?' => $pid]);
            if (!$pidInfo) {
                $rs['code'] = 995;
                $rs['msg'] = \PhalApi\T('父级信息错误');
                return $rs;
            }
            if ($pidInfo['store_id'] != $store_id) {
                $rs['code'] = 995;
                $rs['msg'] = \PhalApi\T('父级信息错误');
                return $rs;
            }
            $level = $pidInfo['level'] + 1;
        }
        $add = $MerchantStoreTypeDomain->saveOne(['name' => $name, 'level' => $level, 'thumb' => $thumb, 'pid' => $pid,  'store_id' => $store_id]);
        $rs['msg'] = \PhalApi\T('添加成功');
        return $rs;
    }


}