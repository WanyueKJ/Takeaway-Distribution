<?php
/* 用户管理 */

namespace app\admin\controller;

use app\models\BalanceModel;
use app\models\ChargeadminModel;
use app\models\UsersModel;
use cmf\controller\AdminBaseController;
use think\Db;

class UsersController extends AdminBaseController
{

    public function index()
    {
        $data = $this->request->param();
        $map = [];


        $start_time = isset($data['start_time']) ? $data['start_time'] : '';
        $end_time = isset($data['end_time']) ? $data['end_time'] : '';

        if ($start_time != "") {
            $map[] = ['create_time', '>=', strtotime($start_time)];
        }

        if ($end_time != "") {
            $map[] = ['create_time', '<=', strtotime($end_time) + 60 * 60 * 24];
        }


        $isban = isset($data['isban']) ? $data['isban'] : '';
        if ($isban != '') {
            if ($isban == 1) {
                $map[] = ['user_status', '=', 0];
            } else {
                $map[] = ['user_status', '<>', 0];
            }

        }


        $keyword = isset($data['keyword']) ? $data['keyword'] : '';
        if ($keyword != '') {
            $map[] = ['user_login|user_nickname|mobile', 'like', '%' . $keyword . '%'];
        }

        $uid = isset($data['uid']) ? $data['uid'] : '';
        if ($uid != '') {
            $map[] = ['id', '=', $uid];
        }

        $nums = UsersModel::where($map)->count();

        $list = UsersModel::where($map)
            ->order("id desc")
            ->paginate(20);

        $list->each(function ($v, $k) {
            $v['user_login'] = m_s($v['user_login']);
            $v['mobile'] = m_s($v['mobile']);
            $v['avatar'] = get_upload_path($v['avatar']);
            $v['order_count'] = ((int)$this->getStoreOrderCount($v['id']) + (int)$this->getOrderCount($v['id']));
            $v['addr_count'] = $this->getAddrCount($v['id']);
            $v['avatar'] = get_upload_path($v['avatar']);
            return $v;
        });

        $list->appends($data);
        // 获取分页显示
        $page = $list->render();
        $this->assign('list', $list);
        $this->assign('page', $page);

        $this->assign('nums', $nums);
        // 渲染模板输出
        return $this->fetch('index');
    }


    /**
     * 用户地址信息
     * @return void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function addr()
    {
        $data = $this->request->param();
        $uid = $data['uid'] ?? 0;
        if (!$uid) $this->error("参数有误！");
        $list = Db::name('addr')->where([
            ["uid", "=", $uid],
        ])->order('addtime DESC')->paginate(20);

        $users = Db::name('users')->where([
            ["id", "=", $uid],
        ])->find();
        // 获取分页显示
        $page = $list->render();
        $this->assign('users', $users);
        $this->assign('list', $list);
        $this->assign('page', $page);
        return $this->fetch();

    }



    /**
     * 获取用户店铺订单下单数量
     * @param $userId
     * @return float|int|string
     */
    public function getStoreOrderCount($userId)
    {
        return Db::name('merchant_store_order')->where([
            ["status", "IN", [1, 2, 3, 4, 5, 6, 7]],
            ["uid", "=", $userId],
        ])->count();
    }

    /**
     * 获取用户跑腿订单下单数量
     * @param $userId
     * @return float|int|string
     */
    public function getOrderCount($userId)
    {
        return Db::name('orders')->where([
            ["status", "IN", [2, 3, 4, 5, 6, 7, 8, 9, 10]],
            ["uid", "=", $userId],
        ])->count();
    }

    /**
     * 获取用户跑腿订单下单数量
     * @param $userId
     * @return float|int|string
     */
    public function getAddrCount($userId)
    {
        return Db::name('addr')->where([
            ["uid", "=", $userId],
        ])->count();
    }


    function del()
    {

        $id = $this->request->param('id', 0, 'intval');

        $rs = UsersModel::where(["id" => $id])->delete();
        if ($rs === false) {
            $this->error("删除失败！");
        }
        $rs = Db::name('users_third')->where(["uid" => $id])->delete();

        UsersModel::del($id);

        $this->success("删除成功！");

    }


    public function listOrder()
    {
        $model = DB::name('users');
        parent::listOrders($model);
        $this->success("排序更新成功！");
    }

    /**
     * 本站用户拉黑
     */
    public function ban()
    {
        $id = input('param.id', 0, 'intval');
        if ($id) {
            $result = UsersModel::where(["id" => $id])->setField('user_status', 0);
            if ($result) {
                UsersModel::clearInfo($id, true);
                $this->success("会员拉黑成功！");
            } else {
                $this->error('会员拉黑失败,会员不存在');
            }
        } else {
            $this->error('数据传入失败！');
        }
    }

    /**
     * 本站用户启用
     */
    public function cancelBan()
    {
        $id = input('param.id', 0, 'intval');
        if ($id) {
            UsersModel::where(["id" => $id])->setField('user_status', 1);
            $this->success("会员启用成功！");
        } else {
            $this->error('数据传入失败！');
        }
    }

    function add()
    {
        return $this->fetch();
    }

    function addPost()
    {
        if ($this->request->isPost()) {

            $data = $this->request->param();
            if (!isset($data['type'])) {
                $data['type'] = 0;
            }
            if (!in_array($data['type'], [0, 1])) {
                $data['type'] = 0;
            }
            $mobile = $data['mobile'];

            if ($mobile == "") {
                $this->error("请填写手机号");
            }

            if (!checkMobile($mobile)) {
                $this->error("请填写正确手机号");
            }

            $isexist = UsersModel::where(['mobile' => $mobile])->value('id');
            if ($isexist) {
                $this->error("该手机号已使用，请更换");
            }

            $user_pass = $data['user_pass'];
            if ($user_pass == "") {
                $this->error("请填写密码");
            }

            if (!checkPass($user_pass)) {
                $this->error("密码为6-20位字母数字组合");
            }

            $data['user_pass'] = cmf_password($user_pass);

            $user_nickname = $data['user_nickname'];
            if ($user_nickname == "") {
                $this->error("请填写昵称");
            }

            /*$isexist=UsersModel::where([ ['user_nickname','=',$user_nickname] ])->find();
            if($isexist){
                $this->error("该昵称已存在，请更换");
            }*/

            $avatar = $data['avatar'];
            $avatar_thumb = $data['avatar_thumb'];
            if (($avatar == "" || $avatar_thumb == '') && ($avatar != "" || $avatar_thumb != '')) {
                $this->error("请同时上传头像 和 头像小图  或 都不上传");
            }

            if ($avatar == '' && $avatar_thumb == '') {
                $data['avatar'] = '/default.png';
                $data['avatar_thumb'] = '/default_thumb.png';
            }

            $data['create_time'] = time();
            $data['mobile'] = $mobile;
            $user_login = 'phone_' . time() . rand(100, 999);
            $data['user_login'] = $user_login;

            $id = UsersModel::insertGetId($data);
            if (!$id) {
                $this->error("添加失败！");
            }
            $this->success("添加成功！");

        }
    }

    function addMerchantPost()
    {
        if ($this->request->isPost()) {

            $data = $this->request->param();
            $id = $data['id'];


            if ($data['type'] != 1) {
                $this->error("类型错误");
            }
            if (!$data['store_id']) {
                $this->error("店铺信息错误");
            }
            $data['user_pass'] = '123456';
            $mobile = $data['mobile'];

            if ($mobile == "") {
                $this->error("请填写手机号");
            }

            if (!checkMobile($mobile)) {
                $this->error("请填写正确手机号");
            }

            $user_pass = $data['user_pass'];
            if ($user_pass == "") {
                $this->error("请填写密码");
            }

            $data['user_pass'] = cmf_password($user_pass);

            $user_nickname = $data['user_nickname'];
            if ($user_nickname == "") {
                $this->error("请填写昵称");
            }

   
            $avatar = $data['avatar'];
            $avatar_thumb = $data['avatar_thumb'];
            if (($avatar == "" || $avatar_thumb == '') && ($avatar != "" || $avatar_thumb != '')) {
                $this->error("请同时上传头像 和 头像小图  或 都不上传");
            }

            if ($avatar == '' && $avatar_thumb == '') {
                $data['avatar'] = '/default.png';
                $data['avatar_thumb'] = '/default_thumb.png';
            }

            $data['create_time'] = time();
            $data['mobile'] = $mobile;
            $user_login = 'phone_' . time() . rand(100, 999);
            $data['user_login'] = $user_login;

            if ($id) {
                $up = UsersModel::where('id', $id)->update($data);
                $this->success("修改成功！");
            }

            $isexist = UsersModel::where(['mobile' => $mobile])->value('id');
            if ($isexist) {
                $this->error("该手机号已使用，请更换");
            }

            $isexist2 = UsersModel::where(['store_id' => $data['store_id']])->value('id');
            if ($isexist2) {
                $this->error("每个店铺只能添加一个账号");
            }
            $id = UsersModel::insertGetId($data);
            if (!$id) {
                $this->error("添加失败！");
            }
            $this->success("添加成功！");

        }
    }


    function edit()
    {

        $id = $this->request->param('id', 0, 'intval');

        $data = UsersModel::where("id={$id}")
            ->find();
        if (!$data) {
            $this->error("信息错误");
        }

        //$data['mobile']=m_s($data['mobile']);
        $this->assign('data', $data);
        return $this->fetch();
    }

    function editPost()
    {
        if ($this->request->isPost()) {

            $data = $this->request->param();

            $id = $data['id'];
            $user_pass = $data['user_pass'];
            if ($user_pass != "") {
                if (!checkPass($user_pass)) {
                    $this->error("密码为6-20位字母数字组合");
                }

                $data['user_pass'] = cmf_password($user_pass);
            } else {
                unset($data['user_pass']);
            }

            $user_nickname = $data['user_nickname'];
            if ($user_nickname == "") {
                $this->error("请填写昵称");
            }

            /*$isexist=UsersModel::where([ ['user_nickname','=',$user_nickname],['id','<>',$id] ])->find();
            if($isexist){
                $this->error("该昵称已存在，请更换");
            }*/

            $mobile = $data['mobile'];
            $isexist = UsersModel::where([['user_login|mobile', '=', $mobile], ['id', '<>', $id]])->find();
            if ($isexist) {
                $this->error("该手机号已使用，请更换");
            }

            $avatar = $data['avatar'];
            $avatar_thumb = $data['avatar_thumb'];
            if (($avatar == "" || $avatar_thumb == '') && ($avatar != "" || $avatar_thumb != '')) {
                $this->error("请同时上传头像 和 头像小图  或 都不上传");
            }

            if ($avatar == '' && $avatar_thumb == '') {
                $data['avatar'] = '/default.png';
                $data['avatar_thumb'] = '/default_thumb.png';
            }

            $rs = UsersModel::update($data);
            if ($rs === false) {
                $this->error("修改失败！");
            }
            UsersModel::clearInfo($data['id']);
            $this->success("修改成功！");
        }
    }

    function balance()
    {

        $id = $this->request->param('id', 0, 'intval');

        $data = UsersModel::where("id={$id}")
            ->find();
        if (!$data) {
            $this->error("信息错误");
        }

        $data['user_login'] = m_s($data['user_login']);
        $data['mobile'] = m_s($data['mobile']);

        $this->assign('data', $data);

        return $this->fetch();
    }

    function setBalance()
    {
        if ($this->request->isPost()) {

            $data = $this->request->param();

            $id = $data['id'];

            $coin = floatval($data['coin']);
            if ($coin <= 0) {
                $this->error("请填写正确的金额");
            }

            $coin2 = $coin;
            $where = [
                ['id', '=', $id]
            ];
            $type = $data['type'] ?? 1;

            $action = 6;
            if ($type == 2) {
                $where[] = ['balance', '>=', $coin];
                $coin2 = 0 - $coin;
                $action = 7;
            }

            $rs = UsersModel::where($where)->inc('balance', $coin2)->update();
            if ($rs === false) {
                $this->error("操作失败！");
            }


            $resid = ChargeadminModel::add($id, $type, $coin);

            BalanceModel::add($id, $type, $action, $resid, '', 1, $coin);

            $this->success("操作成功！");
        }
    }

}
