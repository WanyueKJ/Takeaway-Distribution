<?php

namespace Merchant\Api;

use App\ApiException;
use Merchant\Domain\MerchantStore as MerchantStoreDomain;
use Merchant\Domain\MerchantStoreOrder as MerchantStoreOrderDomain;
use PhalApi\Api;

/**
 * (新-1)店铺管理
 */
class MerchantStore extends Api
{
    public function getRules()
    {
        return array(
            'update' => array(
                'environment' => array('name' => 'environment', 'type' => 'string', 'default' => '', 'desc' => '店铺环境 多图:["admin/2022719/13a0f1d66a1fc7210b77.jpg","admin/2022719/13a0f1d66a1fc7210b77.jpg"]'),
                'open_date' => array('name' => 'open_date', 'type' => 'string', 'default' => '', 'desc' => '营业日期 1-7 示例表示周一,周二:["1","2"]'),
                'open_time' => array('name' => 'open_time', 'type' => 'string', 'default' => '', 'desc' => '营业时间:["1:00","2:00"]'),
                'about' => array('name' => 'about', 'type' => 'string', 'default' => '关于'),
            ),

            'saveIndustry' => array(
                'name' => array('name' => 'name', 'type' => 'string', 'desc' => '法人姓名'),
                'id_card' => array('name' => 'id_card', 'type' => 'string', 'desc' => '身份证号'),
                'id_card_image' => array('name' => 'id_card_image', 'type' => 'string', 'desc' => '身份证图片 多图 Json'),
                'registr_id' => array('name' => 'registr_id', 'type' => 'string', 'desc' => '注册号'),
                'business_image' => array('name' => 'business_image', 'type' => 'string', 'desc' => '营业执照图片 多图 Json'),
                'license_number' => array('name' => 'license_number', 'type' => 'string', 'desc' => '许可证编号'),
                'license_image' => array('name' => 'license_image', 'type' => 'string', 'desc' => '许可证图片 多图 Json'),
            ),
            'readIndustry' => array(),
            'updateIndustry' => array(
                'id' => array('name' => 'id', 'type' => 'string', 'desc' => 'id'),
                'name' => array('name' => 'name', 'type' => 'string', 'desc' => '法人姓名'),
                'id_card' => array('name' => 'id_card', 'type' => 'string', 'desc' => '身份证号'),
                'id_card_image' => array('name' => 'id_card_image', 'type' => 'string', 'desc' => '身份证图片 多图 Json'),
                'registr_id' => array('name' => 'registr_id', 'type' => 'string', 'desc' => '注册号'),
                'business_image' => array('name' => 'business_image', 'type' => 'string', 'desc' => '营业执照图片 多图 Json'),
                'license_number' => array('name' => 'license_number', 'type' => 'string', 'desc' => '许可证编号'),
                'license_image' => array('name' => 'license_image', 'type' => 'string', 'desc' => '许可证图片 多图 Json'),
            ),

            'saveBank' => array(
                'name' => array('name' => 'name', 'type' => 'string', 'desc' => '姓名'),
                'bank_number' => array('name' => 'bank_number', 'type' => 'string', 'desc' => '银行卡号'),
                'bank_name' => array('name' => 'bank_name', 'type' => 'string', 'desc' => '开户行'),
            ),
            'updateBank' => array(
                'id' => array('name' => 'id', 'type' => 'string', 'desc' => 'id'),
                'name' => array('name' => 'name', 'type' => 'string', 'desc' => '姓名'),
                'bank_number' => array('name' => 'bank_number', 'type' => 'string', 'desc' => '银行卡号'),
                'bank_name' => array('name' => 'bank_name', 'type' => 'string', 'desc' => '开户行'),
            ),
            'readBank' => array(
                'id' => array('name' => 'id', 'type' => 'string', 'desc' => 'id'),
            ),
            'deleteBank' => array(
                'id' => array('name' => 'id', 'type' => 'string', 'desc' => 'id'),
            ),
            'indexBank' => array(),

            'savePickup' => array(
                'name' => array('name' => 'name', 'type' => 'string', 'desc' => '名字'),
                'address' => array('name' => 'address', 'type' => 'string', 'desc' => '地址'),
                'doorplate' => array('name' => 'doorplate', 'type' => 'string', 'desc' => '门牌号'),
            ),
            'updatePickup' => array(
                'id' => array('name' => 'id', 'type' => 'string', 'desc' => 'id'),
                'name' => array('name' => 'name', 'type' => 'string', 'desc' => '名字'),
                'address' => array('name' => 'address', 'type' => 'string', 'desc' => '地址'),
                'doorplate' => array('name' => 'doorplate', 'type' => 'string', 'desc' => '门牌号'),
            ),
            'readPickup' => array(),
   

            'saveServe' => array(
                'name' => array('name' => 'name', 'type' => 'string', 'desc' => '服务名'),
            ),
            'updateServe' => array(
                'id' => array('name' => 'id', 'type' => 'string', 'desc' => 'id'),
                'name' => array('name' => 'name', 'type' => 'string', 'desc' => '服务名'),
            ),
            'readServe' => array(
                'id' => array('name' => 'id', 'type' => 'string', 'desc' => '服务id'),
            ),
            'indexServe' => array(),

            'orderStatistics' => array(),
        );
    }


    /**
     * 订单-(营业额_订单数)
     * @desc 用于获取订单-(营业额_订单数)
     * @return int code 操作码，0表示成功
     * @return array info
     * @return array info.count 订单数
     * @return array info.price 营业额
     * @return string msg 提示信息
     */

    public function orderStatistics(){
        $uid = \App\checkNull($this->uid);
        $token = \App\checkNull($this->token);
        $this->checkLogin($uid,$token);

        $MerchantStoreOrderDomain = new MerchantStoreOrderDomain();
        $res = $MerchantStoreOrderDomain->getStatistical($uid);
        return $res;

    }

    /**
     * 订单服务说明-列表
     * @desc 用于获取订单服务说明列表
     * @return int code 操作码，0表示成功
     * @return array info
     * @return string msg 提示信息
     */

    public function indexServe()
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

        $MerchantStoreDomain = new MerchantStoreDomain();
        $res = $MerchantStoreDomain->getStoreIndexServe($uid);
        return $res;
    }


    /**
     * 订单服务说明-获取
     * @desc 用于新增订单服务说明信息
     * @return int code 操作码，0表示成功
     * @return array info
     * @return string msg 提示信息
     */

    public function readServe()
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

        $MerchantStoreDomain = new MerchantStoreDomain();
        $res = $MerchantStoreDomain->getStoreServe($uid, $id);
        return $res;
    }

    /**
     * 订单服务说明-更新
     * @desc 用于更新订单服务说明
     * @return int code 操作码，0表示成功
     * @return array info
     * @return string msg 提示信息
     */

    public function updateServe()
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


        $MerchantStoreDomain = new MerchantStoreDomain();
        $res = $MerchantStoreDomain->updateStoreServe($uid, $id, $name);
        return $res;
    }

    /**
     * 订单服务说明-新增
     * @desc 用于新增订单服务说明
     * @return int code 操作码，0表示成功
     * @return array info
     * @return string msg 提示信息
     */

    public function saveServe()
    {
        $rs = array('code' => 0, 'msg' => '', 'info' => array());

        $uid = \App\checkNull($this->uid);
        $token = \App\checkNull($this->token);

        $checkToken = \App\checkMerchantToken($uid, $token);
        // if ($checkToken == 700) {
        //     $rs['code'] = $checkToken;
        //     $rs['msg'] = \PhalApi\T('您的登陆状态失效，请重新登陆！');
        //     return $rs;
        // }
        $name = \App\checkNull($this->name);
        
        $MerchantStoreDomain = new MerchantStoreDomain();
        $res = $MerchantStoreDomain->addStoreServe($uid, $name);
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
     * 银行信息-删除
     * @desc 用于获取店铺银行列表信息
     * @return int code 操作码，0表示成功
     * @return array info
     * @return string msg 提示信息
     */
    public function deleteBank()
    {

        $uid = \App\checkNull($this->uid);
        $token = \App\checkNull($this->token);
        $this->checkLogin($uid, $token);
        $id = \App\checkNull($this->id);

        $MerchantStoreDomain = new MerchantStoreDomain();
        $res = $MerchantStoreDomain->deleteStoreBank($uid, $id);
        return $res;
    }


    /**
     * 银行信息-列表
     * @desc 用于获取店铺银行列表信息
     * @return int code 操作码，0表示成功
     * @return array info
     * @return string msg 提示信息
     */
    public function indexBank()
    {

        $uid = \App\checkNull($this->uid);
        $token = \App\checkNull($this->token);
        $this->checkLogin($uid, $token);

        $MerchantStoreDomain = new MerchantStoreDomain();
        $res = $MerchantStoreDomain->getStoreBankList($uid);
        return $res;
    }

    /**
     * 银行信息-获取
     * @desc 用于获取店铺银行信息
     * @return int code 操作码，0表示成功
     * @return array info
     * @return string msg 提示信息
     */

    public function readBank()
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

        $MerchantStoreDomain = new MerchantStoreDomain();
        $res = $MerchantStoreDomain->getStoreBank($uid, $id);
        return $res;
    }

    /**
     * 银行信息-更新
     * @desc 用于更新店铺银行信息
     * @return int code 操作码，0表示成功
     * @return array info
     * @return string msg 提示信息
     */

    public function updateBank()
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
        $bank_number = \App\checkNull($this->bank_number);
        $bank_name = \App\checkNull($this->bank_name);


        $MerchantStoreDomain = new MerchantStoreDomain();
        $res = $MerchantStoreDomain->updateStoreBank($uid, $id, $name, $bank_number, $bank_name);
        return $res;
    }

    /**
     * 银行信息-新增
     * @desc 用于新增店铺银行信息
     * @return int code 操作码，0表示成功
     * @return array info
     * @return string msg 提示信息
     */

    public function saveBank()
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
        $bank_number = \App\checkNull($this->bank_number);
        $bank_name = \App\checkNull($this->bank_name);

        $MerchantStoreDomain = new MerchantStoreDomain();
        $res = $MerchantStoreDomain->addStoreBank($uid, $name, $bank_number, $bank_name);
        return $res;
    }

    /**
     * 工商信息-更新
     * @desc 用于更新店铺工商信息
     * @return int code 操作码，0表示成功
     * @return array info
     * @return string msg 提示信息
     */
    public function updateIndustry()
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
        $id_card = \App\checkNull($this->id_card);
        $id_card_image = \App\checkNull($this->id_card_image);
        $registr_id = \App\checkNull($this->registr_id);
        $business_image = \App\checkNull($this->business_image);
        $license_number = \App\checkNull($this->license_number);
        $license_image = \App\checkNull($this->license_image);


        $MerchantStoreDomain = new MerchantStoreDomain();
        $res = $MerchantStoreDomain->updateStoreIndustry($uid, $id, $name, $id_card, $id_card_image, $registr_id, $business_image, $license_number, $license_image);
        return $res;
    }

    /**
     * 工商信息-读取
     * @desc 用于获取店铺工商信息
     * @return int code 操作码，0表示成功
     * @return array info.name 法人姓名
     * @return array info.id_card 身份证号
     * @return array info.registr_id 注册号
     * @return array info.id_card_image[] 身份图片
     * @return array info.business_image[] 营业执照片
     * @return array info.license_number 许可证编号
     * @return array info.license_image[] 许可照片
     * @return string msg 提示信息
     */
    public function readIndustry()
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

        $MerchantStoreDomain = new MerchantStoreDomain();
        $res = $MerchantStoreDomain->getStoreIndustry($uid);
        return $res;
    }

    /**
     * 工商信息-新增
     * @desc 用于新增店铺工商信息
     * @return int code 操作码，0表示成功
     * @return array info
     * @return string msg 提示信息
     */
    public function saveIndustry()
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
        $id_card = \App\checkNull($this->id_card);
        $id_card_image = \App\checkNull($this->id_card_image);
        $registr_id = \App\checkNull($this->registr_id);
        $business_image = \App\checkNull($this->business_image);
        $license_number = \App\checkNull($this->license_number);
        $license_image = \App\checkNull($this->license_image);

        $MerchantStoreDomain = new MerchantStoreDomain();
        $res = $MerchantStoreDomain->addStoreIndustry($uid, $name, $id_card, $id_card_image, $registr_id, $business_image, $license_number, $license_image);
        return $res;
    }

    /**
     * 修改提交店铺信息
     * @desc 用于修改提交店铺信息
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

        $environment = \App\checkNull($this->environment);
        $open_date = \App\checkNull($this->open_date);
        $open_time = \App\checkNull($this->open_time);
        $about = \App\checkNull($this->about);

//        $action = 'App.MerchantStore.update';
//        $date = date('Y-m-d H:i:s');
//        file_put_contents('./log.txt', var_export(compact('action','date','uid','token', 'environment'), true) . PHP_EOL, FILE_APPEND);
        
        $MerchantStoreDomain = new MerchantStoreDomain();
        $update = $MerchantStoreDomain->updateStore($uid, '', '', '', '','', '', '', '', '', $environment, $open_date, $open_time, $about,'','');

        return $update;
    }

    /**
     * 获取店铺信息
     * @desc 用于获取店铺信息
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

        $MerchantStoreDomain = new MerchantStoreDomain();
        $store = $MerchantStoreDomain->getStore($uid, 'id,name,thumb,address,phone,type_id,operating_state,auto_print,environment,automatic_order,tohes,open_date,open_time');

        return $store;
    }


}