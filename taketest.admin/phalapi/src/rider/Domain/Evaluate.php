<?php
namespace Rider\Domain;

use Rider\Model\Evaluate as Model_Evaluate;
use Rider\Model\User as Model_User;

/**
 * @method float getAverage(array $where) 获取店铺订单对骑手的平均分
 */
class Evaluate {

    public function handleInfo($v){
        $v['add_time']=date('m月d日 H:i',$v['addtime']);
        $v['uinfo']=\App\getUserInfo($v['uid']);
        return $v;
    }
    /* 列表 */
	public function getList($where=[],$p=1,$nums=20) {

        $model = new Model_Evaluate();
        $list= $model->getList($where,$p,$nums);

        foreach ($list as $k=>$v){
            $v=self::handleInfo($v);
            $list[$k]=$v;
        }
		return $list;
	}

	public function getRecord($uid,$type,$p){
        $where=[
            'rid'=>$uid,
        ];

        if($type==1){
            $where['star >= ?']=4;
        }
        if($type==2){
            $where['star > 2 and star < ?']=4;
        }
        if($type==3){
            $where['star <= ?']=2;
        }

	    $list=self::getList($where,$p);

	    return $list;
    }

	public function getInfo($where,$field='*') {

        $model = new Model_Evaluate();

        return $model->getInfo($where,$field);

	}

	public function isEvaluate($uid,$oid){
        $model = new Model_Evaluate();
        $where=[
            'uid'=>$uid,
            'oid'=>$oid,
        ];
        $isexist=$model->getInfo($where,'id');
        if($isexist){
            return '1';
        }

        return '0';
    }

	public function del($where) {

        $model = new Model_Evaluate();

        return $model->del($where);

	}

	public function set($uid,$oid,$rid,$content,$star,$cityid){

        $rs = ['code' => 0, 'msg' => \PhalApi\T('评价成功'), 'info' => []];

        if($star<=0 || $star>5){
            $rs['code'] = 1001;
            $rs['msg'] = \PhalApi\T('请选择配送服务星级');
            return $rs;
        }
        $model = new Model_Evaluate();
        $where=[
            'uid'=>$uid,
            'oid'=>$oid,
        ];
        $isexist=$model->getInfo($where,'id');
        if($isexist){
            $rs['code'] = 1002;
            $rs['msg'] = \PhalApi\T('订单已评价，无法再次评价');
            return $rs;
        }

	    $data=[
	        'uid'=>$uid,
	        'oid'=>$oid,
	        'rid'=>$rid,
	        'content'=>$content,
	        'star'=>$star,
	        'cityid'=>$cityid,
	        'addtime'=>time(),
        ];

        $res=$model->add($data);
        if(!$res){
            $rs['code'] = 1003;
            $rs['msg'] = \PhalApi\T('评价失败，请重试');
            return $rs;
        }

        $Model_User=new Model_User();
        $where=[
            'id'=>$rid
        ];
        $Model_User->upStar($where,$star,1);

        return $rs;

    }

    public function getMonth($rid){
        $nowtime=time();
        $day=date('Y-m-01',$nowtime);
        $month_start=strtotime($day);
        $month_end=strtotime("{$day} + 1 month");

        $where=[
            'rid'=>$rid,
            'star >= ?'=>4,
            'addtime >= ?'=>$month_start,
            'addtime < ?'=>$month_end,
        ];
        $model = new Model_Evaluate();
        $nums=$model->getNums($where);

        return $nums;
    }


    /**
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {

        $Model_Evaluate = new Model_Evaluate();
        // TODO: Implement __call() method.
        return call_user_func_array([$Model_Evaluate, $name], $arguments);
    }
}
