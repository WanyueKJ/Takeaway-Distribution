<?php

namespace Merchant\Domain;

use App\ApiException;
use Merchant\Model\MerchantStore as MerchantStoreModel;
use Merchant\Model\MerchantStoreBank;
use Merchant\Model\MerchantStoreProduct as MerchantStoreProductModel;
use Merchant\Model\MerchantStoreServe;
use Merchant\Model\MerchantStoreServe as MerchantStoreServeModel;
use Merchant\Model\MerchantStoreBank as MerchantStoreBankModel;
use Merchant\Model\MerchantStoreIndustry as MerchantStoreIndustryModel;
use Merchant\Model\MerchantType as MerchantTypeModel;
use App\Model\MerchantType as AppMerchantTypeModel;
use Merchant\Model\Users as UsersModel;
use Merchant\Model\MerchantStorePickup as MerchantStorePickupModel;

/**
 * 店铺
 */
class MerchantStore
{
      /**
     * 新增订单服务信息
     * @param ...$param
     * @return array
     */
    public function addStoreServe(...$param)
    {

        $rs = array('code' => 0, 'msg' => '', 'info' => array());
        [$uid, $name] = $param;
        $store = $this->checkStoreIdentity($uid);
        $store_id = $store['id'] ?? 0;
        $MerchantStoreServeModel = new MerchantStoreServeModel();
        $insertData = [
            'name' => $name,
            'addtime' => time(),
            'store_id' => $store_id,
        ];

        $res = $MerchantStoreServeModel->saveOne($insertData);
        $rs['info'][] = $res;
        $rs['msg'] = \PhalApi\T('添加成功');
        return $rs;
    }


    /**
     * 修改店铺上架商品数量
     * @param $storeId
     * @return void
     */
    public function updatePutaway($storeId)
    {
        $MerchantStoreProductModel = new MerchantStoreProductModel();
        $productCount = $MerchantStoreProductModel->getCount(['is_show = ?' => 1, 'is_del = ?' => 0, 'store_id = ?' => $storeId]);
        $this->updateOne(['id = ?' => $storeId], ['putaway' => $productCount]);
    }

    /**
     * 检测用户身份
     * @param $uid
     * @return array
     * @throws ApiException
     */
    public function checkStoreIdentity($uid)
    {
        $loginInfo = \App\getcaches("merchant_token_{$uid}");
        $store_id = $loginInfo['store']['id'] ?? 0;
        if (!$store_id) {
            throw new ApiException(\PhalApi\T('店铺信息错误!'), 995);
        }
        return $loginInfo['store'] ?? [];
    }

    /**
     * 订单服务说明列表
     * @param ...$param
     * @return array
     */
    public function getStoreIndexServe(...$param)
    {
        $rs = array('code' => 0, 'msg' => '', 'info' => array());
        [$uid] = $param;
        $store = $this->checkStoreIdentity($uid);
        $store_id = $store['id'] ?? 0;
        $MerchantStoreServeModel = new MerchantStoreServeModel();

        $res = $MerchantStoreServeModel->selectList(['store_id=?' => $store_id]);
        $rs['info'][] = $res;
        $rs['msg'] = \PhalApi\T('');
        return $rs;
    }


    /**
     * 更新订单服务说明
     * @param ...$param
     * @return array
     */
    public function updateStoreServe(...$param)
    {
        $rs = array('code' => 0, 'msg' => '', 'info' => array());
        [$uid, $id, $name] = $param;
        $store = $this->checkStoreIdentity($uid);
        $store_id = $store['id'] ?? 0;
        $MerchantStoreServeModel = new MerchantStoreServeModel();
        $exist = $MerchantStoreServeModel->getOne(['store_id = ?' => $store_id, 'id = ?' => $id]);
        if (!$exist) {
            $rs['code'] = 995;
            $rs['msg'] = \PhalApi\T('您还没有添加!');
            return $rs;
        }
        $updateData = [
            'name' => $name,
        ];

        $res = $MerchantStoreServeModel->updateOne(['id=?' => $id, 'store_id=?' => $store_id], $updateData);
        $rs['info'][] = $res;
        $rs['msg'] = \PhalApi\T('修改成功');
        return $rs;
    }

    /**
     * 获取订单服务说明
     * @param ...$param
     * @return array
     */
    public function getStoreServe(...$param)
    {

        $rs = array('code' => 0, 'msg' => '', 'info' => array());
        [$uid, $id] = $param;
        $store = $this->checkStoreIdentity($uid);
        $store_id = $store['id'] ?? 0;
        $MerchantStoreServe = new MerchantStoreServe();
        $exist = $MerchantStoreServe->getOne(['store_id = ?' => $store_id, 'id = ?' => $id]);

        $rs['info'][] = $exist ? $exist : [];
        return $rs;
    }







    /**
     * 店铺银行列表信息
     * @param ...$param
     * @return array
     */
    public function getStoreBankList($uid)
    {
        $rs = array('code' => 0, 'msg' => '', 'info' => array());

        $store = $this->checkStoreIdentity($uid);
        $store_id = $store['id'] ?? 0;
        $MerchantStoreBankModel = new MerchantStoreBankModel();
        $list = $MerchantStoreBankModel->selectList(['store_id = ?' => $store_id]);
        $rs['info'][] = $list;
        return $rs;
    }


    /**
     * 更新店铺银行信息
     * @param ...$param
     * @return array
     */
    public function updateStoreBank(...$param)
    {

        $rs = array('code' => 0, 'msg' => '', 'info' => array());
        [$uid, $id, $name, $bank_number, $bank_name] = $param;
        $store = $this->checkStoreIdentity($uid);
        $store_id = $store['id'] ?? 0;

        $MerchantStoreBankModel = new MerchantStoreBankModel();
        $exist = $MerchantStoreBankModel->getOne(['store_id = ?' => $store_id, 'id = ?' => $id]);
        if (!$exist) {
            $rs['code'] = 995;
            $rs['msg'] = \PhalApi\T('您还没有添加!');
            return $rs;
        }
        $updateData = [
            'name' => $name,
            'bank_number' => $bank_number,
            'bank_name' => $bank_name,
        ];

        $res = $MerchantStoreBankModel->updateOne(['id=?' => $id, 'store_id=?' => $store_id], $updateData);
        $rs['info'][] = $res;
        $rs['msg'] = \PhalApi\T('修改成功');
        return $rs;
    }




    /**
     * 获取店铺银行信息
     * @param ...$param
     * @return array
     */
    public function getStoreBank(...$param)
    {
        $rs = array('code' => 0, 'msg' => '', 'info' => array());
        [$uid, $id] = $param;
        $store = $this->checkStoreIdentity($uid);
        $store_id = $store['id'] ?? 0;
        $MerchantStoreBankModel = new MerchantStoreBankModel();
        $exist = $MerchantStoreBankModel->getOne(['store_id = ?' => $store_id, 'id = ?' => $id]);

        $rs['info'][] = $exist ? $exist : [];
        return $rs;
    }

    /**
     * 新增店铺银行信息
     * @param ...$param
     * @return array
     */
    public function addStoreBank(...$param)
    {

        $rs = array('code' => 0, 'msg' => '', 'info' => array());
        [$uid, $name, $bank_number, $bank_name] = $param;
        $store = $this->checkStoreIdentity($uid);
        $store_id = $store['id'] ?? 0;

        $MerchantStoreBankModel = new MerchantStoreBankModel();

        $insertData = [
            'name' => $name,
            'bank_number' => $bank_number,
            'bank_name' => $bank_name,
            'store_id' => $store_id,
        ];

        $res = $MerchantStoreBankModel->saveOne($insertData);
        $rs['info'][] = $res;
        $rs['msg'] = \PhalApi\T('添加成功');
        return $rs;
    }

    /**
     * 新增店铺工商信息
     * @param ...$param
     * @return void
     */
    public function updateStoreIndustry(...$param)
    {
        $rs = array('code' => 0, 'msg' => '', 'info' => array());
        [$uid, $id, $name, $id_card, $id_card_image, $registr_id, $business_image, $license_number, $license_image] = $param;
        $store = $this->checkStoreIdentity($uid);
        $store_id = $store['id'] ?? 0;
        $MerchantStoreIndustryModel = new MerchantStoreIndustryModel();

        $exist = $MerchantStoreIndustryModel->getOne(['store_id = ?' => $store_id, 'id = ?' => $id]);
        if (!$exist) {
            $rs['code'] = 995;
            $rs['msg'] = \PhalApi\T('您还没有添加!');
            return $rs;
        }
        if (!json_decode((string)$id_card_image, true)) {
            $rs['code'] = 995;
            $rs['msg'] = \PhalApi\T('参数错误');
            return $rs;
        }
        if (!json_decode((string)$business_image, true)) {
            $rs['code'] = 995;
            $rs['msg'] = \PhalApi\T('参数错误');
            return $rs;
        }
        if (!json_decode((string)$license_image, true)) {
            $rs['code'] = 995;
            $rs['msg'] = \PhalApi\T('参数错误');
            return $rs;
        }
        $update = [
            'name' => $name,
            'id_card' => $id_card,
            'id_card_image' => $id_card_image,
            'registr_id' => $registr_id,
            'business_image' => $business_image,
            'license_number' => $license_number,
            'license_image' => $license_image,
        ];
        $MerchantStoreIndustryModel->updateOne(['id = ?' => $id, 'store_id = ?' => $store_id], $update);
        $rs['msg'] = \PhalApi\T('修改成功');
        return $rs;

    }

    /**
     * 获取店铺工商信息
     * @param ...$param
     * @return void
     */
    public function getStoreIndustry(...$param)
    {

        $rs = array('code' => 0, 'msg' => '', 'info' => array());
        [$uid] = $param;
        $store = $this->checkStoreIdentity($uid);
        $store_id = $store['id'] ?? 0;
        $MerchantStoreIndustryModel = new MerchantStoreIndustryModel();
        $exist = $MerchantStoreIndustryModel->getOne(['store_id = ?' => $store_id], 'id,name,id_card,id_card_image,registr_id,business_image,license_number,license_image');
        if ($exist) {
            $id_card_image = json_decode($exist['id_card_image'], true);
            foreach ($id_card_image as &$value) {
                $value = \App\get_upload_path($value);
            }

            $business_image = json_decode($exist['business_image'], true);
            foreach ($business_image as &$value) {
                $value = \App\get_upload_path($value);
            }

            $license_image = json_decode($exist['license_image'], true);
            foreach ($license_image as &$value2) {
                $value2 = \App\get_upload_path($value2);
            }
            $exist['business_image'] = $business_image;
            $exist['license_image'] = $license_image;
            $exist['id_card_image'] = $id_card_image;
        }

        $rs['info'][] = $exist ?: [];
        return $rs;
    }

    /**
     * 新增店铺工商信息
     * @param ...$param
     * @return void
     */
    public function addStoreIndustry(...$param)
    {
        $rs = array('code' => 0, 'msg' => '', 'info' => array());
        [$uid, $name, $id_card, $id_card_image, $registr_id, $business_image, $license_number, $license_image] = $param;
        $store = $this->checkStoreIdentity($uid);
        $store_id = $store['id'] ?? 0;
        $MerchantStoreIndustryModel = new MerchantStoreIndustryModel();
        $exist = $MerchantStoreIndustryModel->getOne(['store_id = ?' => $store_id]);
        if ($exist) {
            $rs['code'] = 995;
            $rs['msg'] = \PhalApi\T('您已经添加过了!');
            return $rs;
        }
        if (!json_decode((string)$id_card_image, true)) {
            $rs['code'] = 995;
            $rs['msg'] = \PhalApi\T('参数错误');
            return $rs;
        }
        if (!json_decode((string)$business_image, true)) {
            $rs['code'] = 995;
            $rs['msg'] = \PhalApi\T('参数错误');
            return $rs;
        }
        if (!json_decode((string)$license_image, true)) {
            $rs['code'] = 995;
            $rs['msg'] = \PhalApi\T('参数错误');
            return $rs;
        }
        $update = [
            'name' => $name,
            'id_card' => $id_card,
            'id_card_image' => $id_card_image,
            'registr_id' => $registr_id,
            'business_image' => $business_image,
            'license_number' => $license_number,
            'license_image' => $license_image,
            'store_id' => $store_id,
            'addtime' => time(),
        ];
        $MerchantStoreIndustryModel->saveOne($update);
        $rs['msg'] = \PhalApi\T('添加成功');
        return $rs;

    }

    public function updateStore(...$param)
    {
        $rs = array('code' => 0, 'msg' => '', 'info' => array());

        list($uid, $thumb, $name,$address,$phone, $operating_state, $automatic_order, $auto_print, $tohes, $type_id, $environment, $open_date, $open_time, $about, $lng, $lat,$banner,$sm_auto_print) = $param;
        $loginInfo = \App\getcaches("merchant_token_{$uid}");

        if (!$loginInfo) {
            $rs['code'] = 995;
            $rs['msg'] = \PhalApi\T('您的登陆状态失效，请重新登陆！');
            return $rs;
        }
        $store_id = $loginInfo['store']['id'] ?? 0;
        if (!$store_id) {
            $rs['code'] = 995;
            $rs['msg'] = \PhalApi\T('店铺信息错误!');
            return $rs;
        }

        $updateData = [];
        if ($thumb) {
            $updateData['thumb'] = $thumb;
        }

        if ($environment && is_array(json_decode($environment, true))) {
            $updateData['environment'] = $environment;
        }
        if ($banner && json_decode($banner, true)) {
            $updateData['banner'] = $banner;
        }
        if ($address) {
            $updateData['address'] = $address;
        }
        if ($lng) {
            $updateData['lng'] = $lng;
        }
        if ($lat) {
            $updateData['lat'] = $lat;
        }
        if ($name) {
            $updateData['name'] = $name;
        }

        if ($phone) {
            $updateData['phone'] = $phone;
        }
        if ($open_time && json_decode($open_time, true)) {
            $updateData['open_time'] = $open_time;
        }
        if ($open_date && json_decode($open_date, true)) {
            $updateData['open_date'] = $open_date;
        }
        if ($about) {
            $updateData['about'] = $about;
        }

        if (($operating_state != '') && in_array((int)$operating_state, [0, 1], true)) {
            $updateData['operating_state'] = (int)$operating_state;
        }
        if (($sm_auto_print != '') && in_array((int)$sm_auto_print, [0, 1], true)) {
            $updateData['sm_auto_print'] = (int)$sm_auto_print;
        }
        if (($automatic_order != '') && in_array((int)$automatic_order, [0, 1], true)) {
            $updateData['automatic_order'] = (int)$automatic_order;
        }
        if (($auto_print != '') && in_array((int)$auto_print, [0, 1], true)) {
            $updateData['auto_print'] = (int)$auto_print;
        }
        if (($tohes != '') && in_array((int)$tohes, [0, 1, true])) {
            $updateData['tohes'] = (int)$tohes;
        }
        $MerchantTypeModel = new MerchantTypeModel();
        $MerTypelist = $MerchantTypeModel->selectList([], 'id');
        if ($type_id && in_array($type_id, array_column($MerTypelist, 'id'))) {
            $updateData['type_id'] = $type_id;
        }

        $MercahntStore = new MerchantStoreModel();
        $updateRes = $MercahntStore->updateOne(['id = ?' => $store_id], $updateData);
        $rs['info'][] = $updateRes;
        $rs['msg'] = \PhalApi\T('修改成功!');

        return $rs;
    }

    public function getStore($uid, $field = '*')
    {
        $rs = array('code' => 0, 'msg' => '', 'info' => array());

        $UsersModel = new UsersModel();
        $storeAccount = $UsersModel->getOne(['id = ?' => $uid, 'type = ?' => 1]);
        if (!$storeAccount) {
            $rs['code'] = 995;
            $rs['msg'] = \PhalApi\T('账号不存在');
            return $rs;
        }
        $MerchantStoreModel = new MerchantStoreModel();
        $store = $MerchantStoreModel->getOne(['id = ?' => $storeAccount['store_id']], $field);
        if (!$store) {
            $rs['code'] = 995;
            $rs['msg'] = \PhalApi\T('店铺不存在');
            return $rs;
        }

        $store['thumb'] = \App\get_upload_path($store['thumb']);

        $MerchantTypeModel = new MerchantTypeModel();
        $MerchantTypeInfo = $MerchantTypeModel->getOne(['id = ? ' => $store['type_id']]);
        $store['thumb'] = \App\get_upload_path($store['thumb']);
        $AppMerchantTypeModel = new AppMerchantTypeModel();
        $store['top_type_id'] = $AppMerchantTypeModel->getTopInfo($store['type_id'])['id'] ?? 0;
        $store['type_name'] = $MerchantTypeInfo['use_name'] ?? '--';
        if (isset($store['open_date'])) {
            $store['open_date'] = json_decode($store['open_date'], true);
        }
        if (isset($store['environment'])) {
            $store['environment'] = json_decode($store['environment'], true) ?? [];
            foreach ($store['environment'] as &$v){
                $v = \App\get_upload_path($v);
            }
        }
        if (isset($store['open_time'])) {
            $store['open_time'] = json_decode($store['open_time'], true);
        }
        $re['info'][] = $store;
        return $re;
    }

    /**
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {

        $MerchantStoreModel = new MerchantStoreModel();
        // TODO: Implement __call() method.
        return call_user_func_array([$MerchantStoreModel, $name], $arguments);
    }
}
