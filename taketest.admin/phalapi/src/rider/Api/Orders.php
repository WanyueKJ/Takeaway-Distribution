<?php

namespace Rider\Api;

use App\ApiException;
use PhalApi\Api;
use Rider\Domain\Orders as Domain_Orders;
use Rider\Domain\Orderscount as Domain_Orderscount;

/**
 * 订单
 */
class Orders extends Api
{

    public function getRules()
    {
        return array(
            'getList' => array(
                'cityid' => array('name' => 'cityid', 'type' => 'int', 'desc' => '城市ID'),
                'type' => array('name' => 'type', 'type' => 'int', 'desc' => '类型 1待接单  2 待取货 3待完成'),
                'p' => array('name' => 'p', 'type' => 'int', 'desc' => '页码'),
            ),

            'getDetail' => array(
                'oid' => array('name' => 'oid', 'type' => 'int', 'desc' => '订单ID'),
            ),

            'refuse' => array(
                'cityid' => array('name' => 'cityid', 'type' => 'int', 'desc' => '城市ID'),
                'oid' => array('name' => 'oid', 'type' => 'int', 'desc' => '订单ID'),
            ),

            'grap' => array(
                'cityid' => array('name' => 'cityid', 'type' => 'int', 'desc' => '城市ID'),
                'oid' => array('name' => 'oid', 'type' => 'int', 'desc' => '订单ID'),
            ),

            'start' => array(
                'oid' => array('name' => 'oid', 'type' => 'int', 'desc' => '订单ID'),
                'thumbs' => array('name' => 'thumbs', 'type' => 'string', 'desc' => '照片 数组json'),
            ),

            'complete' => array(
                'oid' => array('name' => 'oid', 'type' => 'int', 'desc' => '订单ID'),
                'code' => array('name' => 'code', 'type' => 'string', 'desc' => '完成码'),
            ),

    

            'getCountList' => array(
                'type' => array('name' => 'type', 'type' => 'int', 'desc' => '类型 0全部,1已完成,2已转单'),
                'p' => array('name' => 'p', 'type' => 'int', 'desc' => '页码'),
            ),

            'getMonthCount' => array(
                'year' => array('name' => 'year', 'type' => 'int', 'desc' => '年份 0为本年'),
            ),
            'getSubmitOrder' => array(
                'users_id' => array('name' => 'users_id', 'type' => 'int', 'desc' => '用户id'),
            ),

        );
    }

    /**
     * 获取骑手,用户订单关系
     * @desc 获取骑手,用户订单关系
     * @return int code 操作码，0表示成功
     * @return array info
     * @return string info[].order_id  订单ID
     */
    public function getSubmitOrder()
    {

        $rs = array('code' => 0, 'msg' => '', 'info' => array());
        $uid = \App\checkNull($this->uid);
        $token = \App\checkNull($this->token);

        $users_id = \App\checkNull($this->users_id);
        if(!$users_id){
            throw new ApiException(\PhalApi\T('用户id不能为空'), 600);
        }

        $checkToken = \App\checkRiderToken($uid, $token);
        if ($checkToken == 700) {
            $rs['code'] = $checkToken;
            $rs['msg'] = \PhalApi\T('您的登陆状态失效，请重新登陆！');
            return $rs;
        }

        $Domain_Orders = new Domain_Orders();
        $res = $Domain_Orders->getSubmitOrder($uid, $users_id);
        return $res;
    }


    /**
     * 订单列表
     * @desc 用于获取订单列表
     * @return int code 操作码，0表示成功
     * @return array info
     * @return string info[].id  订单ID
     * @return string info[].type  订单类型 1帮送 2帮取 3帮买 4 帮排队 5帮办 6店铺外卖
     * @return string info[].orderno  订单号
     * @return string info[].f_name  起始点
     * @return string info[].f_addr  起始点-门牌号
     * @return string info[].f_lng  起始点-经度
     * @return string info[].f_lat  起始点-纬度
     * @return string info[].t_name  目的地
     * @return string info[].t_addr  目的地-门牌号
     * @return string info[].t_lng  目的地-经度
     * @return string info[].t_lat  目的地-纬度
     * @return string info[].pick_name  取件人姓名
     * @return string info[].pick_phone  取件人电话
     * @return string info[].recip_name  收件人姓名
     * @return string info[].recip_phone  收件人电话
     * @return string info[].status  支付状态， 1待支付2已支付 3已接单 4已取件 5已送达 6已完成 7退款中 8退款完成 9已取消
     * @return string info[].income  收益
     * @return string info[].tips  提示标签
     * @return string info[].ispre  是否显示预
     * @return string info[].service_time  格式化服务时间
     * @return string info[].add_time  下单时间
     * @return string info[].grap_time  接单时间
     * @return string info[].pick_time  开始服务时间
     * @return string info[].complete_time  完成时间
     * @return string info[].istrans  是否转单 0否1是
     * @return string info[].trans_time  转单时间
     * @return object info[].extra  完成时间
     * @return object info[].users_im  下单用户IM信息
     * @return object info[].store_im  商家IM信息
     * @return object info[].extra  完成时间
     * @return object info[].reminder_count  催单次数 大于0 表示催单
     * @return object info[].reminder_content  催单内容
     * @return string msg 提示信息
     */
    public function getList()
    {
        $rs = array('code' => 0, 'msg' => '', 'info' => array());
        $uid = \App\checkNull($this->uid);
        $token = \App\checkNull($this->token);
        $cityid = \App\checkNull($this->cityid);
        $type = \App\checkNull($this->type);
        $p = \App\checkNull($this->p);

        $checkToken = \App\checkRiderToken($uid, $token);
        if ($checkToken == 700) {
            $rs['code'] = $checkToken;
            $rs['msg'] = \PhalApi\T('您的登陆状态失效，请重新登陆！');
            return $rs;
        }
        $cityid = 1;
        $domain = new Domain_Orders();
        $list = $domain->getList($cityid, $uid, $type, $p);

        $rs['info'] = $list;

        return $rs;
    }

    /**
     * 订单详情
     * @desc 订单详情
     * @return int code 操作码，0表示成功
     * @return array info
     * @return string info[].id  订单ID
     * @return string info[].expect_time  期望时间
     * @return string info[].addtime  下单时间
     * @return string info[].graptime  接单时间
     * @return string info[].picktime  取件时间
     * @return string info[].sendtime  送达时间
     * @return string info[].rider_basic  配送费
     * @return string info[].rider_weight  超重费 0不显示
     * @return string info[].rider_timemoney  时段附加费 0不显示
     * @return string info[].rider_prepaid  预付费 0不显示
     * @return string info[].rider_fee  小费 0不显示
     * @return string info[].income  总收入
     * @return object info[].reminder_count  催单次数 大于0 表示催单
     * @return object info[].reminder_content  催单内容
     * @return string msg 提示信息
     */
    public function getDetail()
    {
        $rs = array('code' => 0, 'msg' => '', 'info' => array());

        $uid = \App\checkNull($this->uid);
        $token = \App\checkNull($this->token);
        $oid = \App\checkNull($this->oid);

        $checkToken = \App\checkRiderToken($uid, $token);
        if ($checkToken == 700) {
            $rs['code'] = $checkToken;
            $rs['msg'] = \PhalApi\T('您的登陆状态失效，请重新登陆！');
            return $rs;
        }

        $domain = new Domain_Orders();
        return $domain->getDetail($uid, $oid);

    }

    /**
     * 拒接
     * @desc 用于拒接订单
     * @return int code 操作码，0表示成功
     * @return array info
     * @return string msg 提示信息
     */
    public function refuse()
    {
        $rs = array('code' => 0, 'msg' => '', 'info' => array());

        $uid = \App\checkNull($this->uid);
        $token = \App\checkNull($this->token);
        $cityid = \App\checkNull($this->cityid);
        $oid = \App\checkNull($this->oid);

        $checkToken = \App\checkRiderToken($uid, $token);
        if ($checkToken == 700) {
            $rs['code'] = $checkToken;
            $rs['msg'] = \PhalApi\T('您的登陆状态失效，请重新登陆！');
            return $rs;
        }
        $cityid = 1;
        $domain = new Domain_Orders();
        $res = $domain->refuse($cityid, $uid, $oid);

        return $res;
    }

    /**
     * 抢单
     * @desc 用于抢单
     * @return int code 操作码，0表示成功
     * @return array info
     * @return string msg 提示信息
     */
    public function grap()
    {
        $rs = array('code' => 0, 'msg' => '', 'info' => array());

        $uid = \App\checkNull($this->uid);
        $token = \App\checkNull($this->token);
        $cityid = \App\checkNull($this->cityid);
        $oid = \App\checkNull($this->oid);

        $checkToken = \App\checkRiderToken($uid, $token);
        if ($checkToken == 700) {
            $rs['code'] = $checkToken;
            $rs['msg'] = \PhalApi\T('您的登陆状态失效，请重新登陆！');
            return $rs;
        }
        $cityid = 1;
        $domain = new Domain_Orders();

        $res = $domain->grap($cityid, $uid, $oid);

        return $res;
    }

    /**
     * 开始服务
     * @desc 用于订单开始订单服务（取件、已买、开始排队、开始办事）
     * @return int code 操作码，0表示成功
     * @return array info
     * @return string msg 提示信息
     */
    public function start()
    {
        $rs = array('code' => 0, 'msg' => '', 'info' => array());

        $uid = \App\checkNull($this->uid);
        $token = \App\checkNull($this->token);
        $oid = \App\checkNull($this->oid);
        $thumbs = \App\checkNull($this->thumbs);

        $checkToken = \App\checkRiderToken($uid, $token);
        if ($checkToken == 700) {
            $rs['code'] = $checkToken;
            $rs['msg'] = \PhalApi\T('您的登陆状态失效，请重新登陆！');
            return $rs;
        }

        $domain = new Domain_Orders();
        $res = $domain->start($uid, $oid, $thumbs);

        return $res;
    }

    /**
     * 完成订单
     * @desc 用于完成订单
     * @return int code 操作码，0表示成功
     * @return array info
     * @return string msg 提示信息
     */
    public function complete()
    {
        $rs = array('code' => 0, 'msg' => '', 'info' => array());
        
        $uid = \App\checkNull($this->uid);
        $token = \App\checkNull($this->token);
        $oid = \App\checkNull($this->oid);
        $code = \App\checkNull($this->code);
      
        $checkToken = \App\checkRiderToken($uid, $token);
        if ($checkToken == 700) {
            $rs['code'] = $checkToken;
            $rs['msg'] = \PhalApi\T('您的登陆状态失效，请重新登陆！');
            return $rs;
        }

        $domain = new Domain_Orders();
        $res = $domain->complete($uid, $oid, $code);

        return $res;
    }



    /**
     * 今日订单统计
     * @desc 用于获取今日订单统计
     * @return int code 操作码，0表示成功
     * @return array info
     * @return string info[0].orders 完成订单数
     * @return string info[0].trans 转单数
     * @return string info[0].graps 抢单数
     * @return string info[0].distance 配送距离
     * @return string msg 提示信息
     */
    public function getCount()
    {
        $rs = array('code' => 0, 'msg' => '', 'info' => array());

        $uid = \App\checkNull($this->uid);
        $token = \App\checkNull($this->token);

        $checkToken = \App\checkRiderToken($uid, $token);
        if ($checkToken == 700) {
            $rs['code'] = $checkToken;
            $rs['msg'] = \PhalApi\T('您的登陆状态失效，请重新登陆！');
            return $rs;
        }

        $domain = new Domain_Orders();
        $info = $domain->getCount($uid);

        $rs['info'][0] = $info;

        return $rs;
    }

    /**
     * 订单明细列表
     * @desc 用于获取订单明细列表
     * @return int code 操作码，0表示成功
     * @return array info
     * @return string msg 提示信息
     */
    public function getCountList()
    {
        $rs = array('code' => 0, 'msg' => '', 'info' => array());

        $uid = \App\checkNull($this->uid);
        $token = \App\checkNull($this->token);
        $type = \App\checkNull($this->type);
        $p = \App\checkNull($this->p);

        $checkToken = \App\checkRiderToken($uid, $token);
        if ($checkToken == 700) {
            $rs['code'] = $checkToken;
            $rs['msg'] = \PhalApi\T('您的登陆状态失效，请重新登陆！');
            return $rs;
        }

        $domain = new Domain_Orders();
        $list = $domain->getCountList($uid, $type, $p);

        $rs['info'] = $list;

        return $rs;
    }

    /**
     * 月订单统计
     * @desc 用于获取月订单统计
     * @return int code 操作码，0表示成功
     * @return array info
     * @return string info[].title 月份
     * @return string info[].des 日期
     * @return string info[].orders 完成订单数
     * @return string info[].transfers 转单数
     * @return string info[].distance 配送距离（km）
     * @return string msg 提示信息
     */
    public function getMonthCount()
    {
        $rs = array('code' => 0, 'msg' => '', 'info' => array());

        $uid = \App\checkNull($this->uid);
        $token = \App\checkNull($this->token);
        $year = \App\checkNull($this->year);

        $checkToken = \App\checkRiderToken($uid, $token);
        if ($checkToken == 700) {
            $rs['code'] = $checkToken;
            $rs['msg'] = \PhalApi\T('您的登陆状态失效，请重新登陆！');
            return $rs;
        }

        $Domain_Orderscount = new Domain_Orderscount();
        $list = $Domain_Orderscount->getMonthCount($uid, $year);

        $rs['info'] = $list;

        return $rs;
    }


}
