<?php

namespace App\Domain;

use App\Domain\MerchantStore as MerchantStoreDomain;
use App\Domain\MerchantType as MerchantTypeDomain;
use App\Model\MerchantStoreProductReply as MerchantStoreProductReplyModel;

/**
 * @method array selectList(array $where, string$field , string $order , int $p , int $limit)
 * @method array getOne(array $where, string$field , string $order)
 */
class MerchantStoreProductReply
{


    /**
     * 获取点赞最多的评论
     * @param $where
     * @return mixed
     */
    public function getThumbUpMost($where)
    {

        $info = $this->getOne($where, 'id,`comment`,`like`', '`like` DESC');

        return $info['comment'] ?? '';
    }


    /**
     * 新增评价
     * @param $data
     * @return int
     */
    public function replySave($store_id, $data)
    {
        $rs = ['code' => 0, 'msg' => '', 'info' => []];

        $MerchantStoreDomain = new MerchantStoreDomain();
        $store = $MerchantStoreDomain->getOne(['id' => $store_id], 'type_id');
        if (!$store) {
            $rs['code'] = 995;
            $rs['msg'] = \PhalApi\T('店铺信息错误');
            return $rs;
        }

        $MerchantTypeDomain = new MerchantTypeDomain();
        $typeList = $MerchantTypeDomain->getSelfAndChildren(4);
        $idArr = array_column($typeList, 'id');

        if (!in_array($store['type_id'], $idArr)) {
            $rs['code'] = 995;
            $rs['msg'] = \PhalApi\T('店铺类型错误');
            return $rs;
        }
        $data['addtime'] = time();
        $MerchantStoreProductReplyModel = new MerchantStoreProductReplyModel();
        $save = $MerchantStoreProductReplyModel->saveOne($data);
        if(!$save){
            $rs['code'] = 995;
            $rs['msg'] = \PhalApi\T('评价失败');
            return $rs;
        }
        $rs['msg'] = \PhalApi\T('评价成功');
        return $rs;
    }


    /**
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {

        $MerchantStoreProductReplyModel = new MerchantStoreProductReplyModel();
        // TODO: Implement __call() method.
        return call_user_func_array([$MerchantStoreProductReplyModel, $name], $arguments);
    }
}