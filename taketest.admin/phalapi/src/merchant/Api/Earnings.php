<?php

namespace Merchant\Api;

use App\ApiException;
use PhalApi\Api;
use Merchant\Domain\MerchantStoreOrder as MerchantStoreOrderDomain;
use Merchant\Domain\MerchantRecord as MerchantRecordDomain;

/**
 * (新-1)收益管理(对账列表)
 */
class Earnings extends Api
{
    public function getRules()
    {
        return array(
            'incomeStatistics' => array(),
            'save' => array(
                'bink_id' => array('name' => 'bink_id', 'type' => 'string', 'desc' => '店铺银行id(Merchant.MerchantStore.Index)'),
                'money' => array('name' => 'money', 'type' => 'string', 'desc' => '体现金额'),
            ),
            'reconciliation' => array(
                'start_time' => array('name' => 'start_time', 'type' => 'string', 'desc' => '开始时间:2022-09-07'),
                'p' => array('name' => 'p', 'type' => 'string', 'desc' => '页码'),
            )
        );
    }

    /**
     * 对账订单
     * @desc 对账订单
     * @return int code 操作码，0表示成功
     * @return array info.addtime 下单时间
     * @return array info.pay_money 下单金额
     * @return array info.total_num 商品数量
     * @return array info.status 状态
     * @return array info.status_txt 状态描述
     * @return array info.
     * @return string msg 提示信息
     */
    public function reconciliation()
    {
        $uid = \App\checkNull($this->uid);
        $token = \App\checkNull($this->token);

        $this->checkLogin($uid, $token);

        $start_time = \App\checkNull($this->start_time);
        $p = \App\checkNull($this->p);

        $MerchantStoreOrderDomain = new MerchantStoreOrderDomain();


        $res = $MerchantStoreOrderDomain->reconciliationList($uid, $start_time, $p);
        return $res;
    }




    /**
     * 提现申请
     * @return void
     * @throws ApiException
     */
    public function save()
    {
        $uid = \App\checkNull($this->uid);
        // $token = \App\checkNull($this->token);
        // $this->checkLogin($uid, $token);

        $MerchantRecordDomain = new MerchantRecordDomain();
        
        $bink_id = \App\checkNull($this->bink_id);
        $money = \App\checkNull($this->money);
        if (!$bink_id) throw new ApiException(\PhalApi\T('银行信息错误'));
        if ((float)$money <= 0) throw new ApiException(\PhalApi\T('体现参数错误'));
        $res = $MerchantRecordDomain->applyFor($uid, $bink_id, $money);
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
     * 收益统计
     * @desc 收益统计
     * @return int code 操作码，0表示成功
     * @return array info.all_price 总收入
     * @return array info.for_withdrawal 可提现
     * @return array info.freight_free 运费减免
     * @return array info.unread_price 待提现
     * @return string msg 提示信息
     */
    public function incomeStatistics()
    {
        $uid = \App\checkNull($this->uid);
        $token = \App\checkNull($this->token);
        $this->checkLogin($uid, $token);
        $MerchantStoreOrderDomain = new MerchantStoreOrderDomain();
        $res = $MerchantStoreOrderDomain->incomeStatistics($uid);
        return $res;
    }
}