<?php

namespace App\Domain;

use App\Model\MerchantEnter as MerchantenterModel;

/**
 * 店铺管理-店铺申请
 */
class MerchantEnter
{

    /**
     * 新增一条店铺申请记录
     * @param ...$param
     * @return void
     */
    public function applyFor(...$param)
    {
        $rs = ['code' => 0, 'msg' => '', 'info' => []];

        [$uid, $name, $phone, $code, $type] = $param;

        $data = [
            'uid' => $uid,
            'name' => $name,
            'phone' => $phone,
            'type' => $type,
            'addtime' => time(),
        ];
        $model = new MerchantenterModel();
//        $where['uid = ?'] = $uid;
//        $phoneExist = $model->getOne($where);

//        if ($phoneExist) {//已申请过
//            $update = [
//                'updatetime' => time(),
//                'status' => 0,
//                'phone' => $phone,
//                'name' => $name,
//            ];
//            $where2['id = ?'] = $phoneExist['id'];
//            $install = $model->updateOne($where2, $update);
//            $rs['msg'] = \PhalApi\T('您已申请过');
//        } else {
            $install = $model->saveOne($data);
            $rs['msg'] = \PhalApi\T('提交成功');
//        }
        return $rs;
    }

    /**
     * 获取审核状态
     * @param $uid
     * @return int|mixed
     */
    public function getStatus($uid, $type)
    {

        $where['uid = ?'] = $uid;
        $where['type = ?'] = $type;

        $model = new MerchantenterModel();
        $info = $model->getOne($where, 'status');
        if (!$info) {
            $resData = ['status' => -2];
        } else {
            $resData = $info;
        }
        return $resData;

    }
}