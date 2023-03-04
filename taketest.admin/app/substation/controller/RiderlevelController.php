<?php
/* 骑手等级 */
namespace app\substation\controller;

use app\models\CityModel;
use app\models\RiderlevelModel;
use cmf\controller\SubstationBaseController;

class RiderlevelController extends SubstationBaseController
{

    public function index()
    {

        $data = $this->request->param();
        $map=[];

        $cityid=session("cityid");
        $map[]=['cityid','=',$cityid];


        $list = RiderlevelModel::where($map)
			->order("levelid asc")
			->paginate(20);
        $list->each(function ($v,$k){
            $v['config']=json_decode($v['config'],true);
            return $v;
        });
        $list->appends($data);
        // 获取分页显示
        $page = $list->render();
        $this->assign('list', $list);
        $this->assign('page', $page);
        $this->assign('cityid', $cityid);

        // 渲染模板输出
        return $this->fetch('index');
    }

    function del(){
        
        $id = $this->request->param('id', 0, 'intval');

        $rs = RiderlevelModel::where(["id"=>$id])->delete();
        if($rs===false){
            $this->error("删除失败！");
        }

        $this->success("删除成功！");
            
	}


    function add(){
        $data = $this->request->param();
        $cityid= $data['cityid'] ?? 0;
        $this->assign('cityid', $cityid);

		return $this->fetch();
	}
	function addPost(){
		if ($this->request->isPost()) {

            $cityid   = $this->request->param('cityid', 0, 'intval');

            $data=$this->check();

			$id = RiderlevelModel::insertGetId($data);
            if(!$id){
                $this->error("添加失败！");
            }

            CityModel::setStatus($cityid,1);

            RiderlevelModel::resetcache();
            $this->success("添加成功！");
            
		}
	}
	function edit(){
        
        $id   = $this->request->param('id', 0, 'intval');
        $cityid=session("cityid");

        $data=RiderlevelModel::where(['id'=>$id,'cityid'=>$cityid])
            ->find();
        if(!$data){
            $this->error("信息错误");
        }

        $data['config']=json_decode($data['config'],true);
        //$data['mobile']=m_s($data['mobile']);
        $this->assign('data', $data);
        return $this->fetch();
	}
	
	function editPost(){
		if ($this->request->isPost()) {

            $data=$this->check();
            
			$rs = RiderlevelModel::update($data);
            if($rs===false){
                $this->error("修改失败！");
            }
            RiderlevelModel::resetcache();
            $this->success("修改成功！");
		}
	}

    public function check(){

        $data      = $this->request->param();


        $id=$data['id'] ?? 0;
        $cityid=session("cityid");

        $name=$data['name'];
        if($name == ''){
            $this->error('请填写名称');
        }

        $map[]=['cityid','=',$cityid];
        $map[]=['name','=',$name];
        if($id>0){
            $map[]=['id','<>',$id];
        }
        $isexist = RiderlevelModel::field('id')->where($map)->find();
        if($isexist){
            $this->error('名称已存在');
        }

        $levelid=$data['levelid'];
        if($levelid == ''){
            $this->error('请填写等级');
        }
        $map2[]=['cityid','=',$cityid];
        $map2[]=['levelid','=',$levelid];
        if($id>0){
            $map2[]=['id','<>',$id];
        }
        $isexist = RiderlevelModel::field('id')->where($map2)->find();
        if($isexist){
            $this->error('等级已存在');
        }

        $config=$data['config'] ?? [
                'trans_nums'=>0,
                'run_fee_mode'=>1,
                'run_fix_money'=>0,
                'run_rate'=>0,
                'distance_basic'=>0,
                'distance_basic_money'=>0,
                'distance_more_money'=>0,
                'distance_max_money'=>0,
                'distance_type'=>1,
                'work_fee_mode'=>1,
                'work_fix_money'=>0,
                'work_rate'=>0,
            ];
        unset($data['config']);


        if($config['trans_nums']==''){
            $this->error("请添加转单次数");
        }

        if($config['run_mode']==1){
            if($config['run_fix']<0){
                $this->error("请设置正确的跑腿类收入-每单固定收入");
            }
        }
        if($config['run_mode']==2){
            if($config['run_rate']<0 || $config['run_rate']>100){
                $this->error("请设置正确的跑腿类收入-配送费比例");
            }
        }
        if($config['run_mode']==3){
            if($config['distance_basic']<=0){
                $this->error("请设置正确的跑腿类收入-起始距离");
            }

            if($config['distance_basic_money']<0){
                $this->error("请设置正确的跑腿类收入-基础配送费");
            }

            if($config['distance_more_money']<0){
                $this->error("请设置正确的跑腿类收入-增加配送费");
            }

            if($config['distance_max_money']<0){
                $this->error("请设置正确的跑腿类收入-最高配送费");
            }
        }


        if($config['work_mode']==1){
            if($config['work_fix']<=0){
                $this->error("请设置正确的办事类收入-每单固定收入");
            }
        }
        if($config['work_mode']==2){
            if($config['work_rate']<0){
                $this->error("请设置正确的办事类收入-配送费比例");
            }
        }

        $data['config']=json_encode($config);

        $nums=RiderlevelModel::where(['cityid'=>$cityid])->count();
        if($nums==0){
            $data['isdefault']=1;
        }

        return $data;
    }
}
