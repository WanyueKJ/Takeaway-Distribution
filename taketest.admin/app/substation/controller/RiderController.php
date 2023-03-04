<?php
/* 骑手管理 */
namespace app\substation\controller;

use app\models\OrdersModel;
use app\models\RiderlocalModel;
use app\models\RiderModel;
use cmf\controller\SubstationBaseController;

class RiderController extends SubstationBaseController
{

    public function index()
    {

        $data = $this->request->param();
        $map=[];

        $cityid= session("cityid");

        $map[]=['cityid','=',$cityid];

        $start_time= $data['start_time'] ?? '';
        $end_time= $data['end_time'] ?? '';

        if($start_time!=""){
           $map[]=['create_time','>=',strtotime($start_time)];
        }

        if($end_time!=""){
           $map[]=['create_time','<=',strtotime($end_time) + 60*60*24];
        }


        $isban= $data['isban'] ?? '';
        if($isban!=''){
            $map[]=['user_status','=',$isban];
        }

        $keyword= $data['keyword'] ?? '';
        if($keyword!=''){
            $map[]=['user_login|user_nickname|mobile','like','%'.$keyword.'%'];
        }

        $uid= $data['uid'] ?? '';
        if($uid!=''){
			$map[]=['id','=',$uid];
        }

        $map[]=['type','=',2];

        $nums=RiderModel::where($map)->count();

        $list = RiderModel::where($map)
			->order("id desc")
			->paginate(20);

        $list->each(function($v,$k){
            $v['user_login']=m_s($v['user_login']);
            $v['mobile']=m_s($v['mobile']);
            $v['avatar']=get_upload_path($v['avatar']);
            $v['status_txt']=RiderModel::getStatus($v['user_status']);
            $v['type_txt']=RiderModel::getTypes($v['type']);
            return $v;
        });

        $list->appends($data);
        // 获取分页显示
        $page = $list->render();
        $this->assign('list', $list);
        $this->assign('page', $page);
        $this->assign('cityid', $cityid);

        $this->assign('nums', $nums);
        $this->assign('status', RiderModel::getStatus());
        // 渲染模板输出
        return $this->fetch('index');
    }

    function del(){

        $id = $this->request->param('id', 0, 'intval');

        $rs = RiderModel::where(["id"=>$id])->delete();
        if($rs===false){
            $this->error("删除失败！");
        }

        RiderModel::del($id);

        $this->success("删除成功！");

	}

    public function setstatus()
    {
        $id = input('param.id', 0, 'intval');
        $status = input('param.status', 0, 'intval');
        if (!$id) {
            $this->error('数据传入失败！');
        }

        $result = RiderModel::where(["id" => $id])->setField('user_status', $status);
        if ($result===false) {
            $this->error('会操作失败');
        }

        RiderModel::clearInfo($id,true);
        $this->success("操作成功！");

    }

    public function settype()
    {
        $id = input('param.id', 0, 'intval');
        $type = input('param.type', 0, 'intval');
        if (!$id) {
            $this->error('数据传入失败！');
        }

        $up=[
            'type'=>$type,
        ];
        if($type==1){
            $up['balance']=0;
        }
        if($type==2){
            $info=RiderModel::where(["id" => $id])->field('balance')->find();
            if($info['balance']>0){
                $this->error('变更为全职前要保证余额为0！');
            }
        }


        $result = RiderModel::where(["id" => $id])->update($up);
        if ($result===false) {
            $this->error('会操作失败');
        }

        $this->success("操作成功！");

    }

    function add(){
		return $this->fetch();
	}
	function addPost(){
		if ($this->request->isPost()) {

            $data      = $this->request->param();

			$mobile=$data['mobile'];

			if($mobile==""){
				$this->error("请填写手机号");
			}

            if(!checkMobile($mobile)){
                $this->error("请填写正确手机号");
            }

            $isexist=RiderModel::where(['mobile'=>$mobile])->value('id');
            if($isexist){
                $this->error("该手机号已使用，请更换");
            }

			$user_pass=$data['user_pass'];
			if($user_pass==""){
				$this->error("请填写密码");
			}

            if(!checkPass($user_pass)){
                $this->error("密码为6-20位字母数字组合");
            }

            $data['user_pass']=cmf_password($user_pass);

			$user_nickname=$data['user_nickname'];
			if($user_nickname==""){
				$this->error("请填写昵称");
			}

            /*$isexist=RiderModel::where([ ['user_nickname','=',$user_nickname] ])->find();
            if($isexist){
                $this->error("该昵称已存在，请更换");
            }*/

            $avatar=$data['avatar'];
            $avatar_thumb=$data['avatar_thumb'];
            if( ($avatar=="" || $avatar_thumb=='' ) && ($avatar!="" || $avatar_thumb!='' )){
                $this->error("请同时上传头像 和 头像小图  或 都不上传");
            }

            if($avatar=='' && $avatar_thumb==''){
                $data['avatar']='/qishou_avatar.png';
                $data['avatar_thumb']='/qishou_avatar.png';
            }

            $data['create_time']=time();
            $data['mobile']=$mobile;
            $user_login='phone_'.time().rand(100,999);
            $data['user_login']=$user_login;
            $data['type']=2;

            $cityid= session("cityid");

            $data['cityid']=$cityid;

			$id = RiderModel::insertGetId($data);
            if(!$id){
                $this->error("添加失败！");
            }
            $this->success("添加成功！");

		}
	}
	function edit(){

        $id   = $this->request->param('id', 0, 'intval');

        $data=RiderModel::where("id={$id}")
            ->find();
        if(!$data){
            $this->error("信息错误");
        }

        //$data['mobile']=m_s($data['mobile']);
        $this->assign('data', $data);
        return $this->fetch();
	}

	function editPost(){
		if ($this->request->isPost()) {

            $data      = $this->request->param();

            $id=$data['id'];
			$user_pass=$data['user_pass'];
			if($user_pass!=""){
				if(!checkPass($user_pass)){
                    $this->error("密码为6-20位字母数字组合");
                }

                $data['user_pass']=cmf_password($user_pass);
			}else{
                unset($data['user_pass']);
            }

			$user_nickname=$data['user_nickname'];
			if($user_nickname==""){
				$this->error("请填写昵称");
			}

            /*$isexist=RiderModel::where([ ['user_nickname','=',$user_nickname],['id','<>',$id] ])->find();
            if($isexist){
                $this->error("该昵称已存在，请更换");
            }*/

            $mobile=$data['mobile'];
            $isexist=RiderModel::where([ ['user_login|mobile','=',$mobile],['id','<>',$id] ])->find();
            if($isexist){
                $this->error("该手机号已使用，请更换");
            }

            $avatar=$data['avatar'];
            $avatar_thumb=$data['avatar_thumb'];
            if( ($avatar=="" || $avatar_thumb=='' ) && ($avatar!="" || $avatar_thumb!='' )){
                $this->error("请同时上传头像 和 头像小图  或 都不上传");
            }

            if($avatar=='' && $avatar_thumb==''){
                $data['avatar']='/default.png';
                $data['avatar_thumb']='/default_thumb.png';
            }

			$rs = RiderModel::update($data);
            if($rs===false){
                $this->error("修改失败！");
            }
            RiderModel::clearInfo($data['id']);
            $this->success("修改成功！");
		}
	}

	function getList(){
        $data = $this->request->param();
        $map=[];

        $cityid= session("cityid");

        $map[]=['cityid','=',$cityid];

        $keyword=$data['keyword'] ?? '';
        if($keyword!=''){
            $map[]=['user_nickname|mobile','like',"%{$keyword}%"];
        }

        $list=RiderModel::where($map)->field('id,user_nickname,mobile')->select();

        $list->each(function ($v,$k){
            $where=[
                ['riderid','=',$v['id']],
                ['status','<',6],
            ];
            $orders=OrdersModel::where($where)->count();

            $v['orders']=$orders;

            $lng='';
            $lat='';
            $isexist=RiderlocalModel::getLocal($v['id']);
            if($isexist){
                $lng=$isexist['lng'];
                $lat=$isexist['lat'];
            }

            $v['lng']=$lng;
            $v['lat']=$lat;

            return $v;
        });

        $this->success('','',$list);
    }
}
