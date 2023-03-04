<?php

namespace App\Api;

use PhalApi\Api;
use App\Domain\Orders as Domain_Orders;
use App\Domain\User as Domain_User;
use Rider\Domain\Ordersrefundreason as Domain_Ordersrefundreason;

/**
 * (改-1)订单(订单)
 */
class Orders extends Api
{

    public function getRules()
    {
        return array(
            'getList' => array(
                'type' => array('name' => 'type', 'type' => 'int', 'desc' => '类型 0全部 1待支付 2待接单 3进行中 4已完成 5取消/退款'),
                'keyword' => array('name' => 'keyword', 'type' => 'string', 'desc' => '关键词'),
                'p' => array('name' => 'p', 'type' => 'int', 'desc' => '页码'),
            ),

            'getDetail' => array(
                'orderid' => array('name' => 'orderid', 'type' => 'int', 'desc' => '订单ID'),
            ),

            'getLocation' => array(
                'orderid' => array('name' => 'orderid', 'type' => 'int', 'desc' => '订单ID'),
                'type' => array('name' => 'type', 'type' => 'int', 'desc' => '订单类型 0跑腿订单(默认) 1外卖订单'),
            ),

            'getPayList' => array(
                'type' => array('name' => 'type', 'type' => 'int', 'desc' => '类型 1充值 2下单 3店铺下单'),
            ),

            'repay' => array(
                'payid' => array('name' => 'payid', 'type' => 'int', 'desc' => '支付方式ID'),
                'orderid' => array('name' => 'orderid', 'type' => 'int', 'desc' => '订单ID'),
                'openid' => array('name' => 'openid', 'type' => 'string', 'desc' => '微信openid'),
                'is_mer' => array('name' => 'is_mer', 'type' => 'string', 'desc' => '是否是商家下单 1是 默认0不是'),
            ),


            'cancel' => array(
                'orderid' => array('name' => 'orderid', 'type' => 'int', 'desc' => '订单ID'),
            ),

            'refund' => array(
                'orderid' => array('name' => 'orderid', 'type' => 'int', 'desc' => '订单ID'),
                'reason' => array('name' => 'reason', 'type' => 'string', 'desc' => '原因'),
            ),

            'cancelrefund' => array(
                'orderid' => array('name' => 'orderid', 'type' => 'int', 'desc' => '订单ID'),
            ),

            'getRefund' => array(
                'orderid' => array('name' => 'orderid', 'type' => 'int', 'desc' => '订单ID'),
            ),

            'track' => array(
                'orderid' => array('name' => 'orderid', 'type' => 'int', 'desc' => '订单ID'),
            ),

            'evaluate' => array(
                'orderid' => array('name' => 'orderid', 'type' => 'int', 'desc' => '订单ID'),
                'star' => array('name' => 'star', 'type' => 'int', 'desc' => '星级'),
                'content' => array('name' => 'content', 'type' => 'string', 'desc' => '评价内容'),
            ),

            'del' => array(
                'orderid' => array('name' => 'orderid', 'type' => 'int', 'desc' => '订单ID'),
            ),

            'reminder' => array(
                'id' => array('name' => 'id', 'type' => 'string', 'desc' => '订单id'),
                'content' => array('name' => 'content', 'type' => 'string', 'desc' => '催单内容'),
            ),
        );
    }



    /**
     * 我的订单(订单列表)
     * @desc 用于获取我的订单
     * @return int code 操作码，0表示成功
     * @return array info
     * @return string info[].id  订单ID
     * @return string info[].type  订单类型 1帮送 2帮取 3帮买 4 帮排队 5帮办
     * @return string info[].money  支付金额
     * @return string info[].money_total  总金额
     * @return string info[].discount_money  优惠券折扣金额
     * @return string info[].paytime  剩余支付时间（秒）
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
     * @return string info[].status  支付状态， 1待支付2已支付 3已接单 4已取件 5已送达 6已完成 7退款中 8退款完成 9退款拒绝 10已取消
     * @return string info[].income  收益
     * @return string info[].tips  提示标签
     * @return string info[].ispre  是否显示预
     * @return string info[].service_time  格式化服务时间
     * @return string info[].add_time  下单时间
     * @return string info[].grap_time  接单时间
     * @return string info[].pick_time  开始服务时间
     * @return string info[].complete_time  完成时间
     * @return string info[].isevaluate  是否评价 0否1是
     * @return object info[].extra  完成时间
     * @return object info[].status_des  订单状态描述
     * @return string msg 提示信息
     */
    public function getList()
    {
        $rs = array('code' => 0, 'msg' => '', 'info' => array());

        $uid = \App\checkNull($this->uid);
        $token = \App\checkNull($this->token);
        $type = \App\checkNull($this->type);
        $keyword = \App\checkNull($this->keyword);
        $p = \App\checkNull($this->p);

        $checkToken = \App\checkToken($uid, $token);
        if ($checkToken == 700) {
            $rs['code'] = $checkToken;
            $rs['msg'] = \PhalApi\T('您的登陆状态失效，请重新登陆！');
            return $rs;
        }

        $domain = new Domain_Orders();
        $list = $domain->getList($uid, $type, $keyword, $p);

        $rs['info'] = $list;

        return $rs;
    }

    /**
     * (改-1)订单详情
     * @desc 用于获取订单详情
     * @return int code 操作码，0表示成功
     * @return array info
     * @return string info[].id  订单ID
     * @return object info[].rinfo  骑手信息
     * @return string info[].prepaytime  支付超时剩余时间
     * @return string info[].forecast_time  预计送达、到达时间
     * @return string info[].is_reminder  能否催单
     * @return string info[].service_time  取件时间
     * @return string info[].way  配送方式
     * @return string msg 提示信息
     */
    public function getDetail()
    {
        $rs = array('code' => 0, 'msg' => '', 'info' => array());

        $uid = \App\checkNull($this->uid);
        $token = \App\checkNull($this->token);
        $orderid = \App\checkNull($this->orderid);

        $checkToken = \App\checkToken($uid, $token);
        if ($checkToken == 700) {
            $rs['code'] = $checkToken;
            $rs['msg'] = \PhalApi\T('您的登陆状态失效，请重新登陆！');
            return $rs;
        }

        $domain = new Domain_Orders();
        return $domain->getDetail($uid, $orderid);

    }

    /**
     * (改-1)配送员位置
     * @desc
     * @return int code 操作码，0表示成功
     * @return array info
     * @return string info[0].lng
     * @return string info[0].lat
     * @return string msg 提示信息
     */
    public function getLocation()
    {
        $rs = array('code' => 0, 'msg' => '', 'info' => array());

        $uid = \App\checkNull($this->uid);
        $token = \App\checkNull($this->token);
        $orderid = \App\checkNull($this->orderid);
        $type = (int)\App\checkNull($this->type);
        $checkToken = \App\checkToken($uid, $token);
        if ($checkToken == 700) {
            $rs['code'] = $checkToken;
            $rs['msg'] = \PhalApi\T('您的登陆状态失效，请重新登陆！');
            return $rs;
        }
//        $action = 'App.Orders.repay';
//        $date = date('Y-m-d H:i:s');
//        file_put_contents('./log.txt', var_export(compact('action','date','uid','token', 'orderid'), true) . PHP_EOL, FILE_APPEND);

        $domain = new Domain_Orders();
        return $domain->getLocation($uid, $orderid,$type);

    }

    /**
     * 支付方式
     * @desc 用于获取支付方式列表
     * @return int code 操作码，0表示成功
     * @return array info 支付方式
     * @return string info[].id 1支付宝2微信
     * @return string info[].name 名称
     * @return string info[].thumb 图标
     * @return string msg 提示信息
     */
    public function getPayList()
    {
        $rs = array('code' => 0, 'msg' => '', 'info' => array());

        $uid = \App\checkNull($this->uid);
        $token = \App\checkNull($this->token);
        $source = \App\checkNull($this->source);
        $type = \App\checkNull($this->type);

        $checkToken = \App\checkToken($uid, $token);
        if ($checkToken == 700) {
            $rs['code'] = $checkToken;
            $rs['msg'] = \PhalApi\T('您的登陆状态失效，请重新登陆！');
            return $rs;
        }

        $configpri = \App\getConfigPri();

        $paylist = [];

        $ali_switch = 0;
        $wx_switch = 0;

        if ($source != 0) {
            $aliapp_switch = $configpri['aliapp_switch'];
            $wxapp_switch = $configpri['wxapp_switch'];

            if ($aliapp_switch == 1) {
                $ali_switch = 1;
            }

            if ($wxapp_switch == 1) {
                $wx_switch = 1;
            }
        }

        if ($ali_switch && $source != 3 && $source != 4) {
            $paylist[] = [
                'id' => '1',
                'name' => \PhalApi\T('支付宝支付'),
                'thumb' => \App\get_upload_path("/static/app/pay/ali.png"),
            ];
        }
        if ($wx_switch) {
            $paylist[] = [
                'id' => '2',
                'name' => \PhalApi\T('微信支付'),
                'thumb' => \App\get_upload_path("/static/app/pay/wx.png"),
            ];
        }

        if ($type == 2) {
            $Domain_User = new Domain_User();
            $user = $Domain_User->getInfo(['id' => $uid], 'balance');

//            $paylist[] = [
//                'id' => '0',
//                'name' => \PhalApi\T('余额支付'),
//                'thumb' => \App\get_upload_path("/static/app/pay/balance.png?t=1615277928"),
//                'balance' => $user['balance'],
//            ];

//            $paylist[] = [];
        }

        $rs['info'] = $paylist;

        return $rs;
    }

    /**
     * 订单支付
     * @desc 用于订单支付
     * @return int code 操作码，0表示成功
     * @return array info
     * @return string info[0].orderid 订单号
     * @return string msg 提示信息
     */
    public function repay()
    {
        $rs = array('code' => 0, 'msg' => '', 'info' => array());

        $uid = \App\checkNull($this->uid);
        $token = \App\checkNull($this->token);
        $payid = \App\checkNull($this->payid);
        $orderid = \App\checkNull($this->orderid);
        $openid = \App\checkNull($this->openid);
        $is_mer = \App\checkNull($this->is_mer);
        if (!$is_mer || $is_mer == '') $is_mer = 0;

        $checkToken = \App\checkToken($uid, $token);
        if ($checkToken != 0) {
            $rs['code'] = $checkToken;
            $rs['msg'] = \PhalApi\T('您的登陆状态失效，请重新登陆！');
            return $rs;
        }

//        $action = 'App.Orders.repay';
//        $date = date('Y-m-d H:i:s');
//        file_put_contents('./log.txt', var_export(compact('action','date','uid','token', 'payid','orderid','openid'), true) . PHP_EOL, FILE_APPEND);

        $domain = new Domain_Orders();
        $res = $domain->repay($uid, $orderid, $payid, $openid,$is_mer);


        return $res;
    }



    /**
     * 订单取消
     * @desc 用于订单取消
     * @return int code 操作码，0表示成功
     * @return array info
     * @return string msg 提示信息
     */
    public function cancel()
    {
        $rs = array('code' => 0, 'msg' => '', 'info' => array());

        $uid = \App\checkNull($this->uid);
        $token = \App\checkNull($this->token);
        $orderid = \App\checkNull($this->orderid);

        $checkToken = \App\checkToken($uid, $token);
        if ($checkToken != 0) {
            $rs['code'] = $checkToken;
            $rs['msg'] = \PhalApi\T('您的登陆状态失效，请重新登陆！');
            return $rs;
        }

        $domain = new Domain_Orders();
        $res = $domain->cancel($uid, $orderid);

        return $res;
    }

    /**
     * 退款原因
     * @desc 用于获取订单申请退款原因
     * @return int code 操作码，0表示成功
     * @return array info
     * @return string msg 提示信息
     */
    public function getRefundReason()
    {
        $rs = array('code' => 0, 'msg' => '', 'info' => array());

        $Domain_Ordersrefundreason = new Domain_Ordersrefundreason();
        $list = $Domain_Ordersrefundreason->getList();

        $rs['info'] = $list;

        return $rs;
    }

    /**
     * 申请退款
     * @desc 用于订单申请退款
     * @return int code 操作码，0表示成功
     * @return array info
     * @return string msg 提示信息
     */
    public function refund()
    {
        $rs = array('code' => 0, 'msg' => '', 'info' => array());

        $uid = \App\checkNull($this->uid);
        $token = \App\checkNull($this->token);
        $orderid = \App\checkNull($this->orderid);
        $reason = \App\checkNull($this->reason);

        $checkToken = \App\checkToken($uid, $token);
        if ($checkToken != 0) {
            $rs['code'] = $checkToken;
            $rs['msg'] = \PhalApi\T('您的登陆状态失效，请重新登陆！');
            return $rs;
        }


        $domain = new Domain_Orders();
        $res = $domain->refund($uid, $orderid, $reason);

        return $res;
    }

    /**
     * 取消申请退款
     * @desc 用于订单取消申请退款
     * @return int code 操作码，0表示成功
     * @return array info
     * @return string msg 提示信息
     */
    public function cancelrefund()
    {
        $rs = array('code' => 0, 'msg' => '', 'info' => array());

        $uid = \App\checkNull($this->uid);
        $token = \App\checkNull($this->token);
        $orderid = \App\checkNull($this->orderid);

        $checkToken = \App\checkToken($uid, $token);
        if ($checkToken != 0) {
            $rs['code'] = $checkToken;
            $rs['msg'] = \PhalApi\T('您的登陆状态失效，请重新登陆！');
            return $rs;
        }


        $domain = new Domain_Orders();
        $res = $domain->cancelrefund($uid, $orderid);

        return $res;
    }

    /**
     * 退款详情
     * @desc 用于获取订单退款详情
     * @return int code 操作码，0表示成功
     * @return array info
     * @return string info[0].status 状态
     * @return string info[0].money 金额
     * @return string info[0].orderno 订单号
     * @return string info[0].reason 原因
     * @return array  info[0].list 退款流程
     * @return string msg 提示信息
     */
    public function getRefund()
    {
        $rs = array('code' => 0, 'msg' => '', 'info' => array());

        $uid = \App\checkNull($this->uid);
        $token = \App\checkNull($this->token);
        $orderid = \App\checkNull($this->orderid);

        $checkToken = \App\checkToken($uid, $token);
        if ($checkToken != 0) {
            $rs['code'] = $checkToken;
            $rs['msg'] = \PhalApi\T('您的登陆状态失效，请重新登陆！');
            return $rs;
        }


        $domain = new Domain_Orders();
        $res = $domain->getRefund($uid, $orderid);

        return $res;
    }

    /**
     * 订单追踪
     * @desc 用于获取订单追踪信息
     * @return int code 操作码，0表示成功
     * @return array info
     * @return string msg 提示信息
     */
    public function track()
    {
        $rs = array('code' => 0, 'msg' => '', 'info' => array());

        $uid = \App\checkNull($this->uid);
        $token = \App\checkNull($this->token);
        $orderid = \App\checkNull($this->orderid);

        $checkToken = \App\checkToken($uid, $token);
        if ($checkToken != 0) {
            $rs['code'] = $checkToken;
            $rs['msg'] = \PhalApi\T('您的登陆状态失效，请重新登陆！');
            return $rs;
        }


        $domain = new Domain_Orders();
        $res = $domain->track($uid, $orderid);

        return $res;
    }

    /**
     * 订单评价
     * @desc 用于订单评价
     * @return int code 操作码，0表示成功
     * @return array info
     * @return string msg 提示信息
     */
    public function evaluate()
    {
        $rs = array('code' => 0, 'msg' => '', 'info' => array());

        $uid = \App\checkNull($this->uid);
        $token = \App\checkNull($this->token);
        $orderid = \App\checkNull($this->orderid);
        $star = \App\checkNull($this->star);
        $content = \App\checkNull($this->content);

        $checkToken = \App\checkToken($uid, $token);
        if ($checkToken != 0) {
            $rs['code'] = $checkToken;
            $rs['msg'] = \PhalApi\T('您的登陆状态失效，请重新登陆！');
            return $rs;
        }


        $domain = new Domain_Orders();
        $res = $domain->evaluate($uid, $orderid, $star, $content);

        return $res;
    }

    /**
     * 删除订单
     * @desc 用于删除订单
     * @return int code 操作码，0表示成功
     * @return array info
     * @return string msg 提示信息
     */
    public function del()
    {
        $rs = array('code' => 0, 'msg' => '', 'info' => array());

        $uid = \App\checkNull($this->uid);
        $token = \App\checkNull($this->token);
        $orderid = \App\checkNull($this->orderid);

        $checkToken = \App\checkToken($uid, $token);
        if ($checkToken != 0) {
            $rs['code'] = $checkToken;
            $rs['msg'] = \PhalApi\T('您的登陆状态失效，请重新登陆！');
            return $rs;
        }


        $domain = new Domain_Orders();
        $res = $domain->del($uid, $orderid);

        return $res;
    }
}
