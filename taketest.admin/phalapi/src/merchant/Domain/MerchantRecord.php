<?php

namespace Merchant\Domain;

use App\ApiException;
use Merchant\Model\MerchantStoreBank as MerchantStoreBankModel;
use Merchant\Model\MerchantRecord as MerchantRecordModel;

/**
 * 店铺提现信息
 */
class MerchantRecord
{


    public function getList($uid, $start_time, $p)
    {
        $rs = array('code' => 0, 'msg' => \PhalApi\T(''), 'info' => array());
        $store = $this->checkStoreIdentity($uid);
        $storeId = $store['id'] ?? 0;
        $where = [];
        if ($start_time) {
            $where['addtime >= ?'] = strtotime($start_time);
            $where['store_id >= ?'] = $storeId;
        }
        $statusTxt = [
            \PhalApi\T('审核中'),
            \PhalApi\T('审核通过'),
            \PhalApi\T('审核失败'),
        ];

        $list = $this->selectList($where, 'id,addtime,account_bank,account,money,status,des', 'id ASC', $p, 20);
        foreach ($list as &$value) {
            $value['addtime'] = date('Y/m/d', $value['addtime']);
            $value['status_txt'] = in_array($statusTxt[$value['status']], [0, 1, 2]) ? $statusTxt[$value['status']] : '';
            $value['account'] = $value['account_bank'] . substr($value['account'], -4);
            unset($value['account_bank']);
        }
        $rs['info'][] = $list;
        return $rs;
    }

    /**
     * 商户体现申请
     * @param $uid
     * @param $bink_id
     * @param $money
     * @return void
     */
    public function applyFor($uid, $bink_id, $money)
    {
        $rs = array('code' => 0, 'msg' => \PhalApi\T('提现成功,等待审核'), 'info' => array());
       
        $store = $this->checkStoreIdentity($uid);
        $storeId = $store['id'] ?? 0;
        
        $MerchantStoreBankModel = new MerchantStoreBankModel();
        $bankInfo = $MerchantStoreBankModel->getOne(['id = ?' => $bink_id, 'store_id = ?' => $storeId]);
        if (!$bankInfo) throw new ApiException(\PhalApi\T('银行信息不存在'));

        $MerchantStoreOrder = new MerchantStoreOrder();
        $price = $MerchantStoreOrder->incomeStatistics($uid);
        
        if ($money <= 0) {
            if (!$bankInfo) throw new ApiException(\PhalApi\T('提现金额不符合要求'));
        }
        $mon = $money > $price['info']['for_withdrawal'];
        if ($mon) {
            throw new ApiException(\PhalApi\T('提现金额不符合要求'));
        }
        $installData = [
            'store_id' => $storeId,
            'addtime' => time(),
            'money' => $money,
            'account_bank' => $bankInfo['bank_name'],
            'name' => $bankInfo['name'],
            'account' => $bankInfo['bank_number'],
        ];
        $add = $this->saveOne($installData);
        return $rs;
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
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {

        $MerchantRecordModel = new MerchantRecordModel();
        // TODO: Implement __call() method.
        return call_user_func_array([$MerchantRecordModel, $name], $arguments);
    }
}