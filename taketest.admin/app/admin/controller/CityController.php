<?php

/* 城市管理 */
namespace app\admin\controller;

use app\models\CityModel;
use cmf\controller\AdminBaseController;
use think\Db;

class CityController extends AdminBaseController
{

    public function index()
    {
        $data = $this->request->param();
        $map=[];

        $pid=$data['pid'] ?? 0;
        $data['pid']=$pid;
        $map[]=['pid','=',$pid];

        $keyword=$data['keyword'] ?? '';
        if($keyword!=''){
            $map[]=['name','like',"%{$keyword}%"];
        }

        $list = CityModel::where($map)
            ->order("pid asc,list_order asc")
            ->paginate(20);

        $list->each(function($v,$k){
            $status_t='';
            if($v['pid']!=0){
                $status_t=CityModel::getStatus($v['status']);
            }
            $v['status_t']=$status_t;
            return $v;
        });

        $list->appends($data);
        // 获取分页显示
        $page = $list->render();
        $this->assign('list', $list);
        $this->assign('page', $page);
        $this->assign('pid', $pid);

        // 渲染模板输出
        return $this->fetch('index');
    }

    public function add()
    {
        $data      = $this->request->param();
        $pid=$data['pid'] ?? 0;
        $this->assign('pid', $pid);

        $this->assign('list', CityModel::getLevelOne());
        $this->assign('time', CityModel::getTimePeriod());

        return $this->fetch();
    }

    public function addPost()
    {
        if ($this->request->isPost()) {

            $data=$this->check();

            $model=new CityModel;

            $data['config']='{"type":[],"type1":{"fee_mode":"1","fix_money":"0","distance_switch":"0","distance_basic":"0","distance_basic_money":"0","distance_more_money":"0","distance_mode":"1","distance_type":"1","weight_switch":"0","weight_basic":"0","weight_basic_money":"0","weight_more_money":"0","weight_type":"1","h":"00","i":"00","times":[]},"type2":{"fee_mode":"1","fix_money":"0","distance_switch":"0","distance_basic":"0","distance_basic_money":"0","distance_more_money":"0","distance_mode":"1","distance_type":"1","weight_switch":"0","weight_basic":"0","weight_basic_money":"0","weight_more_money":"0","weight_type":"1","h":"00","i":"00","times":[]},"type3":{"fee_mode":"1","fix_money":"0","distance_basic":"0","distance_basic_money":"0","distance_more_money":"0","distance_mode":"1","distance_type":"1","h":"00","i":"00","times":[]},"type4":{"fee_mode":"1","fix_money":"0","time_basic":"0","time_basic_money":"0","time_more":"0","time_more_money":"0","time_type":"1","h":"00","i":"00","times":[]},"type5":{"fix_money":"0","h":"00","i":"00","times":[]},"distance_basic":"0","distance_basic_time":"0","distance_more_time":"0"}';

            $id = $model->save($data);
            if(!$id){
                $this->error("添加失败！");
            }

            CityModel::resetcache();

            $this->success("添加成功！");
        }
    }

    public function edit()
    {
        $id   = $this->request->param('id', 0, 'intval');
        
        $data=CityModel::where("id={$id}")
            ->find();
        if(!$data){
            $this->error("信息错误");
        }

        //$config=json_decode($data['config'],true);
        $this->assign('data', $data);
        /*$this->assign('type', $config['type']);
        $this->assign('type1', $config['type1']);

        $this->assign('type3', $config['type3']);
        $this->assign('type4', $config['type4']);
        $this->assign('type5', $config['type5']);
        $this->assign('config', $config);*/

        $this->assign('list', CityModel::getLevelOne());
        //$this->assign('time', CityModel::getTimePeriod());

        return $this->fetch();
    }

    public function editPost()
    {
        if ($this->request->isPost()) {

            $data=$this->check();

            $rs = CityModel::update($data);

            CityModel::resetcache();
            
            $this->success("保存成功！");
        }
    }

    public function check(){

        $data      = $this->request->param();

        $id=$data['id'] ?? 0;
        $pid=$data['pid'];
        $name=$data['name'];
        if($name == ''){
            $this->error('请填写名称');
        }

        $area_code=$data['area_code'];
        if($area_code == ''){
            $this->error('请填写地区编号');
        }


        $map[]=['pid','=',$pid];
        $map[]=['area_code','=',$area_code];
        if($id>0){
            $map[]=['id','<>',$id];
        }
        $isexist = CityModel::field('id')->where($map)->find();
        if($isexist){
            $this->error('地区编号已存在');
        }

        $pid=$data['pid'];

        if($pid>0){
            $rate=$data['rate'];
            if($rate == ''){
                $this->error('请填写抽成比例');
            }
        }


        return $data;
    }

    public function listOrder()
    {
        $model = new CityModel;
        parent::listOrders($model);
        CityModel::resetcache();
        $this->success("排序更新成功！");
    }

    public function del()
    {
        $id = $this->request->param('id', 0, 'intval');

   

        $rs = CityModel::where('id',$id)->delete();
        if(!$rs){
            $this->error("删除失败！");
        }

        $this->success("删除成功！");
    }
    

}