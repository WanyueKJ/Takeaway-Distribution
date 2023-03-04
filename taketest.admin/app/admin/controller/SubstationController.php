<?php
/* 分站管理员 */

namespace app\admin\controller;

use app\models\CityModel;
use app\models\RiderModel;
use app\models\SubstationModel;
use cmf\controller\AdminBaseController;
use think\Db;

class SubstationController extends AdminBaseController
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

        $nums = SubstationModel::where($map)->count();

        $list = SubstationModel::where($map)
            ->order("id desc")
            ->paginate(20);

        $list->each(function ($v, $k) {
            $v['user_login'] = m_s($v['user_login']);
            $v['mobile'] = m_s($v['mobile']);
            $v['avatar'] = get_upload_path($v['avatar']);
            $v['rnums'] = RiderModel::where([['cityid', '=', $v['cityid']]])->count();
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

    function del()
    {

        $id = $this->request->param('id', 0, 'intval');

        $info = SubstationModel::where(["id" => $id])->find();
        $rs = SubstationModel::where(["id" => $id])->delete();
        if ($rs === false) {
            $this->error("删除失败！");
        }
        $cityid = handelSetToArr($info['cityid']);
        CityModel::setStatus($cityid, 0);

        $this->success("删除成功！");

    }

    public function listOrder()
    {
        $model = DB::name('substation');
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
            $result = SubstationModel::where(["id" => $id])->setField('user_status', 0);
            if ($result) {
                $this->success("禁用成功！");
            } else {
                $this->error('禁用失败,用户不存在');
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
            SubstationModel::where(["id" => $id])->setField('user_status', 1);
            $this->success("启用成功！");
        } else {
            $this->error('数据传入失败！');
        }
    }

    function add()
    {

//        $city = CityModel::getNoOpen();
//        $city = SubstationModel::handleCity($city);
        $city[] = [
            'id' => 1,
            'name2' => '全球'
        ];
        $this->assign('city', $city);

        return $this->fetch();
    }

    function addPost()
    {
        if ($this->request->isPost()) {

            $data = $this->request->param();

            $cityid = $data['cityid'];
            if ($cityid <= 0) {
                $this->error("请选择管理城市");
            }

            $isexist = SubstationModel::where(['cityid' => $cityid])->value('id');
            if ($isexist) {
                $this->error("该城市下已有管理员");
            }

            $user_login = $data['user_login'];

            if ($user_login == "") {
                $this->error("请填写账号");
            }

            $isexist = SubstationModel::where(['user_login' => $user_login])->value('id');
            if ($isexist) {
                $this->error("账号已存在，请更换");
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

            $avatar = $data['avatar'] ?? '';

            if ($avatar == '') {
                $data['avatar'] = '/default.png';
            }

            $data['create_time'] = time();

            $id = SubstationModel::insertGetId($data);
            if (!$id) {
                $this->error("添加失败！");
            }
            $this->success("添加成功！");

        }
    }

    function edit()
    {

        $id = $this->request->param('id', 0, 'intval');

        $data = SubstationModel::where("id={$id}")
            ->find();
        if (!$data) {
            $this->error("信息错误");
        }

        //$data['mobile']=m_s($data['mobile']);
        $this->assign('data', $data);

        $cityid = $data['cityid'] ?? 0;

        $city = CityModel::getInfo($cityid);
        $this->assign('city', $city);

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

            /*$avatar=$data['avatar'] ?? '';

            if($avatar=='' ){
                $data['avatar']='/default.png';
            }*/

            $rs = SubstationModel::update($data);
            if ($rs === false) {
                $this->error("修改失败！");
            }

            $this->success("修改成功！");
        }
    }
}
