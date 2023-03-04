<?php

namespace App\Api;

use PhalApi\Api;
use App\Domain\Addr as Domain_Addr;

/**
 * 地址
 */
class Addr extends Api
{

    public function getRules()
    {
        return array(
            'set' => array(
                'place' => array('name' => 'place', 'type' => 'string', 'desc' => '地址'),
                'lng' => array('name' => 'lng', 'type' => 'string', 'desc' => '经度'),
                'lat' => array('name' => 'lat', 'type' => 'string', 'desc' => '纬度'),
                'addr' => array('name' => 'addr', 'type' => 'string', 'desc' => '详细地址'),
                'name' => array('name' => 'name', 'type' => 'string', 'desc' => '收货人'),
                'mobile' => array('name' => 'mobile', 'type' => 'string', 'desc' => '电话'),
            ),

            'up' => array(
                'addrid' => array('name' => 'addrid', 'type' => 'int', 'desc' => '地址ID'),
                'place' => array('name' => 'place', 'type' => 'string', 'desc' => '地址'),
                'lng' => array('name' => 'lng', 'type' => 'string', 'desc' => '经度'),
                'lat' => array('name' => 'lat', 'type' => 'string', 'desc' => '纬度'),
                'addr' => array('name' => 'addr', 'type' => 'string', 'desc' => '详细地址'),
                'name' => array('name' => 'name', 'type' => 'string', 'desc' => '收货人'),
                'mobile' => array('name' => 'mobile', 'type' => 'string', 'desc' => '电话'),
            ),

            'del' => array(
                'addrid' => array('name' => 'addrid', 'type' => 'int', 'desc' => '地址ID'),
            ),
            'getList' => array(
                'store_lng' => array('name' => 'store_lng', 'type' => 'string', 'desc' => '店铺经度'),
                'store_lat' => array('name' => 'store_lat', 'type' => 'string', 'desc' => '店铺维度'),
            ),
            'checkNewAddress' => array(
                'lng' => array('name' => 'lng', 'type' => 'string', 'desc' => '经度'),
                'lat' => array('name' => 'lat', 'type' => 'string', 'desc' => '纬度'),
                'place' => array('name' => 'place', 'type' => 'string', 'desc' => '地址'),
            ),
            'checkExceedAddress' => array(
                'store_lng' => array('name' => 'store_lng', 'type' => 'string', 'desc' => '店铺经度'),
                'store_lat' => array('name' => 'store_lat', 'type' => 'string', 'desc' => '店铺维度'),

                'lng' => array('name' => 'lng', 'type' => 'string', 'desc' => '地址经度'),
                'lat' => array('name' => 'lat', 'type' => 'string', 'desc' => '地址纬度'),
            )
        );
    }


    /**
     * 检测新增的地址是否超出店铺配送范围
     * @return array
     * @throws \App\ApiException
     */
    public function checkExceedAddress(){
        $rs = array('code' => 0, 'msg' => '', 'info' => array());

        $uid = \App\checkNull($this->uid);
        $token = \App\checkNull($this->token);

        $checkToken = \App\checkToken($uid, $token);
        if ($checkToken == 700) {
            $rs['code'] = $checkToken;
            $rs['msg'] = \PhalApi\T('您的登陆状态失效，请重新登陆！');
            return $rs;
        }

        $store_lng = \App\checkNull($this->store_lng);
        $store_lat = \App\checkNull($this->store_lat);

        $lng = \App\checkNull($this->lng);
        $lat = \App\checkNull($this->lat);


        $domain = new Domain_Addr();
        $res = $domain->checkExceedAddress($uid, $lng, $lat,$store_lng,$store_lat);
        return $res;
    }


    /**
     * (新-1)检测当前地址是否已经添加过
     * @desc 检测当前地址是否已经添加过
     * @return int code 操作码，0表示成功
     * @return array info
     * @return string info[].addr 0:否 地址信息
     * @return string msg 提示信息
     */
    public function checkNewAddress()
    {
        $rs = array('code' => 0, 'msg' => '', 'info' => array());

        $uid = \App\checkNull($this->uid);
        $token = \App\checkNull($this->token);

        $checkToken = \App\checkToken($uid, $token);
        if ($checkToken == 700) {
            $rs['code'] = $checkToken;
            $rs['msg'] = \PhalApi\T('您的登陆状态失效，请重新登陆！');
            return $rs;
        }
        $place = \App\checkNull($this->place);
        $lng = \App\checkNull($this->lng);
        $lat = \App\checkNull($this->lat);

        $domain = new Domain_Addr();
        $isset = $domain->getInfo([
            'lng = ?' => $lng,
            'lat = ?' => $lat,
            'place LIKE ?' => "%{$place}%",
            'uid = ?' => $uid,
        ],'id,uid,place,lng,lat,addr,name,mobile');
        $tmp['addr'] = $isset ? $isset : 0;
        $rs['info'][] = $tmp;
        return $rs;

    }


    /**
     * 地址列表
     * @desc 用于获取地址列表
     * @return int code 操作码，0表示成功
     * @return array info
     * @return string info[].id
     * @return string info[].place 位置名称
     * @return string info[].lng 经度
     * @return string info[].lat 纬度
     * @return string info[].addr 门牌号
     * @return string info[].name 姓名
     * @return string info[].mobile 手机号
     * @return string info[].distance 距离 (km)
     * @return string info[].exceed 是否超配送范围 0否 1是
     * @return string msg 提示信息
     */
    public function getList()
    {
        $rs = array('code' => 0, 'msg' => '', 'info' => array());

        $uid = \App\checkNull($this->uid);
        $token = \App\checkNull($this->token);

        $checkToken = \App\checkToken($uid, $token);
        if ($checkToken == 700) {
            $rs['code'] = $checkToken;
            $rs['msg'] = \PhalApi\T('您的登陆状态失效，请重新登陆！');
            return $rs;
        }

        $lng = \App\checkNull($this->store_lng);
        $lat = \App\checkNull($this->store_lat);

        $where = [
            'uid' => $uid
        ];

        if($lng == "") $lng = 0;
        if($lat == "") $lat = 0;
        $domain = new Domain_Addr();
        if($lng>0 && $lat >0){
            $list = $domain->getDistanceList($where,$lng,$lat);
        }else{
            $list = $domain->getList($where);
        }

        $rs['info'] = $list;

        return $rs;
    }

    /**
     * 新增地址
     * @desc 用于新增地址
     * @return int code 操作码，0表示成功
     * @return array info
     * @return string msg 提示信息
     */
    public function set()
    {
        $rs = array('code' => 0, 'msg' => '', 'info' => array());

        $uid = \App\checkNull($this->uid);
        $token = \App\checkNull($this->token);

        $checkToken = \App\checkToken($uid, $token);
        if ($checkToken == 700) {
            $rs['code'] = $checkToken;
            $rs['msg'] = \PhalApi\T('您的登陆状态失效，请重新登陆！');
            return $rs;
        }

        $place = \App\checkNull($this->place);
        $lng = \App\checkNull($this->lng);
        $lat = \App\checkNull($this->lat);
        $addr = \App\checkNull($this->addr);
        $name = \App\checkNull($this->name);
        $mobile = \App\checkNull($this->mobile);


        $domain = new Domain_Addr();
        $res = $domain->set($uid, $place, $lng, $lat, $addr, $name, $mobile);

        return $res;
    }

    /**
     * 编辑地址
     * @desc 用于编辑地址
     * @return int code 操作码，0表示成功
     * @return array info
     * @return string msg 提示信息
     */
    public function up()
    {
        $rs = array('code' => 0, 'msg' => '', 'info' => array());

        $uid = \App\checkNull($this->uid);
        $token = \App\checkNull($this->token);

        $checkToken = \App\checkToken($uid, $token);
        if ($checkToken == 700) {
            $rs['code'] = $checkToken;
            $rs['msg'] = \PhalApi\T('您的登陆状态失效，请重新登陆！');
            return $rs;
        }

        $addrid = \App\checkNull($this->addrid);


        $place = \App\checkNull($this->place);
        $lng = \App\checkNull($this->lng);
        $lat = \App\checkNull($this->lat);
        $addr = \App\checkNull($this->addr);
        $name = \App\checkNull($this->name);
        $mobile = \App\checkNull($this->mobile);


        $domain = new Domain_Addr();
        $res = $domain->up($uid, $addrid, $place, $lng, $lat, $addr, $name, $mobile);

        return $res;
    }

    /**
     * 删除地址
     * @desc 用于删除地址
     * @return int code 操作码，0表示成功
     * @return array info
     * @return string msg 提示信息
     */
    public function del()
    {
        $rs = array('code' => 0, 'msg' => \PhalApi\T('删除成功'), 'info' => array());

        $uid = \App\checkNull($this->uid);
        $token = \App\checkNull($this->token);
        $addrid = \App\checkNull($this->addrid);

        $checkToken = \App\checkToken($uid, $token);
        if ($checkToken == 700) {
            $rs['code'] = $checkToken;
            $rs['msg'] = \PhalApi\T('您的登陆状态失效，请重新登陆！');
            return $rs;
        }

        if ($addrid < 0) {
            $rs['code'] = 1001;
            $rs['msg'] = \PhalApi\T('信息错误');
            return $rs;
        }

        $where = [
            'uid' => $uid,
            'id' => $addrid,
        ];


        $domain = new Domain_Addr();
        $res = $domain->del($where);

        return $rs;
    }


}
