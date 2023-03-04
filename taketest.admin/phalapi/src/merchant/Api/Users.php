<?php

namespace Merchant\Api;

use App\ApiException;
use Merchant\Domain\MerchantStore as MerchantStoreDomain;
use Merchant\Domain\MerchantType as MerchantTypeDomain;
use Merchant\Domain\Users as UsersDomain;
use PhalApi\Api;

/**
 * (新-1)个人中心
 */
class Users extends Api
{
    public function getRules()
    {
        return array(
            'home' => array(),
            'update' => array(
                'thumb' => array('name' => 'thumb', 'type' => 'string', 'desc' => '基本资料-店铺Logo'),
                'banner' => array('name' => 'banner', 'type' => 'string', 'desc' => '基本资料-店铺Banner'),
                'name' => array('name' => 'name', 'type' => 'string', 'desc' => '基本资料-店铺名称'),
                
                'phone' => array('name' => 'phone', 'type' => 'string', 'desc' => '基本资料-门店电话'),
                'type_id' => array('name' => 'type_id', 'type' => 'string', 'desc' => '基本资料-店铺类型'),
                'address' => array('name' => 'address', 'type' => 'string', 'desc' => '基本资料-店铺地址'),
                'lng' => array('name' => 'lng', 'type' => 'string', 'desc' => '基本资料-店铺地址经度'),
                'lat' => array('name' => 'lat', 'type' => 'string', 'desc' => '基本资料-店铺地址维度'),
                'operating_state' => array('name' => 'operating_state', 'type' => 'string', 'desc' => '营业状态: 1营业 0打样'),
                'automatic_order' => array('name' => 'automatic_order', 'type' => 'string', 'desc' => '自动接单开关: 1开启 0关闭'),
                'auto_print' => array('name' => 'auto_print', 'type' => 'string', 'desc' => '易联云接单自动打印: 1开启 0关闭'),
                'sm_auto_print' => array('name' => 'sm_auto_print', 'type' => 'string', 'desc' => '商米接单自动打印: 1开启 0关闭'),
                'tohes' => array('name' => 'tohes', 'type' => 'string', 'desc' => '通知与消息音开关: 1开启 0关闭'),
            ),
            'getMerchantTypeList' => array(
                'id' => array('name' => 'id', 'type' => 'string', 'desc' => '分类id 默认0:表示全部分类 大于0:获取当前id下的分类'),
                'level' => array('name' => 'level', 'type' => 'string', 'desc' => '分类层级 默认1:表示获取一级'),
                'is_tree' => array('name' => 'is_tree', 'type' => 'string', 'desc' => '默认:0 是否已tree方式返回数据'),
            ),
      
            'logOut' =>array(),
        );

    }

    /**
     * 账号退出
     * @desc 账号退出
     * @return array
     * @throws ApiException
     */
    public function logOut(){
        $rs = array('code' => 0, 'msg' => '', 'info' => array());

        $uid = \App\checkNull($this->uid);
        $token = \App\checkNull($this->token);
        $this->checkLogin($uid,$token);
        $UsersDomain = new UsersDomain();
        $UsersDomain->logOut($uid);
        return $rs;
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
     * 商户个人中心
     * @desc 用于获取商户个人中心数据
     * @return int code 操作码，0表示成功
     * @return array info
     * @return array info.name 店铺名
     * @return array info.address 店铺地址
     * @return array info.thumb 店铺图标
     * @return array info.phone 店铺电话
     * @return array info.type_name 店铺类型
     * @return array info.type_id 店铺类型id
     * @return array info.auto_print 易联云小票自动打印开关(0关闭 1开启)
     * @return array info.sm_auto_print 商米小票自动打印开关(0关闭 1开启)
     * @return array info.automatic_order 自动接单开关
     * @return array info.operating_state 营业状态
     * @return array info.tohes 通知消息与提示音设置开关
     * @return string msg 提示信息
     */
    public function home()
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
        $store = $MerchantStoreDomain->getStore($uid, 'id,name,thumb,address,phone,type_id,operating_state,auto_print,automatic_order,tohes,lng,lat,sm_auto_print');
        return $store;
    }

    /**
     * 获取店铺类型列表
     * @desc 用于获取店铺类型列表
     * @return int code 操作码，0表示成功
     * @return array info
     * @return array info.name 店铺类型名[中文]

     * @return array info.use_name 当前语言包下应该使用的名字
     * @return array info.pid 父节点id
     * @return array info.level 层级等级 最低为1 从一开始
     * @return string msg 提示信息
     */
    public function getMerchantTypeList()
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



        $MerchantTypeDomain = new MerchantTypeDomain();
        $list = $MerchantTypeDomain->getSelectList($uid, $id, $is_tree, $level);
        $rs['info'][] =$list;
        return $rs;
    }

    /**
     * 商户资料-修改
     * @desc 用于修改店铺信息
     * @return int code 操作码，0表示成功
     * @return array info
     * @return array info.auto_print 易联云小票自动打印开关(0关闭 1开启)
     * @return array info.sm_auto_print 商米小票自动打印开关(0关闭 1开启)
     * @return array info.automatic_order 自动接单开关
     * @return array info.operating_state 营业状态
     * @return array info.tohes 通知消息与提示音设置开关
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

        $thumb = \App\checkNull($this->thumb);
        $name = \App\checkNull($this->name);
  
        $phone = \App\checkNull($this->phone);
        $operating_state = \App\checkNull($this->operating_state);
        $automatic_order = \App\checkNull($this->automatic_order);
        $auto_print = \App\checkNull($this->auto_print);
        $tohes = \App\checkNull($this->tohes);
        $type_id = \App\checkNull($this->type_id);
        $address = \App\checkNull($this->address);
        $lng = \App\checkNull($this->lng);
        $lat = \App\checkNull($this->lat);
        $banner = \App\checkNull($this->banner);
        $sm_auto_print = \App\checkNull($this->sm_auto_print);


//        $action = 'App.Users.update';
//        $date = date('Y-m-d H:i:s');
//        file_put_contents('./log.txt', var_export(compact('action','date','uid','token', 'banner','automatic_order'), true) . PHP_EOL, FILE_APPEND);

        $MerchantStoreDomain = new MerchantStoreDomain();

        $update = $MerchantStoreDomain->updateStore($uid, $thumb, $name, $address, $phone, $operating_state, $automatic_order, $auto_print, $tohes, $type_id,'','','','',$lng,$lat,$banner,$sm_auto_print);
        return $update;
    }


}