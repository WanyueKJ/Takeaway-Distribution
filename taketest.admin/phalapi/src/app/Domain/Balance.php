<?php
namespace App\Domain;

use App\Model\Balance as Model_Balance;
use App\Domain\User as Domain_User;

class Balance {

    public function getActions($k=''){
        $actions=[
            '1'=>'余额充值',
            '2'=>'支付订单',
            '3'=>'订单退款',
            '4'=>'加小费',
            '5'=>'邀请奖励',
            '6'=>'管理员充值',
            '7'=>'管理员扣除',
        ];
        if($k==''){
            return $actions;
        }

        return $actions[$k] ?? '';
    }

    public function handleInfo($v){
        $v['action_txt']=self::getActions($v['action']);
        $v['add_time']=date('m月d日 H:i',$v['addtime']);
        return $v;
    }
    /* 列表 */
	public function getList($where=[],$p=1,$nums=20) {

        $model = new Model_Balance();
        $list= $model->getList($where,$p,$nums);

        foreach ($list as $k=>$v){
            $v=self::handleInfo($v);
            $list[$k]=$v;
        }
		return $list;
	}
    
    /* 某个信息 */
	public function getInfo($where=[],$field='*') {

        $model = new Model_Balance();
        $info= $model->getInfo($where,$field);

		return $info;
	}

	public function getRecord($uid,$type,$time,$p){
        $where=[
            'uid'=>$uid,
        ];
        if($type==1){
            $where['action']=$type;
        }
        if($type==2){
            $where['action']=[2,4];
        }
        if($type==3){
            $where['action']=$type;
        }
        $nowtime=time();
        if($time==''){
            $time=date('Y-m',$nowtime);
        }

        $time=date('Y-m',strtotime($time)).'-01';

        $m_start=strtotime($time);
        $m_end=strtotime("{$time} + 1 month");

        $where['addtime >= ?']=$m_start;
        $where['addtime < ?']=$m_end;

	    $list=self::getList($where,$p);

	    return $list;
    }

    /* 新增
    * $type 收支 1收入 2支出
    * $action 行为，1充值  2消费  3退款
    * $actionid  行为对应ID
    * $nums  数量
    * $total 总价
    */
	public function add($uid,$type,$action,$actionid,$orderno,$nums,$total) {


	    $Domain_User=new Domain_User();

	    $uinfo=$Domain_User->getInfo(['id'=>$uid],'balance');

        $balance=$uinfo['balance'];

        $data=[
            'type'=>$type,
            'action'=>$action,
            'uid'=>$uid,
            'actionid'=>$actionid,
            'nums'=>$nums,
            'total'=>$total,
            'balance'=>$balance,
            'orderno'=>$orderno,
            'addtime'=>time(),
        ];

        $model = new Model_Balance();
        $res= $model->add($data);

		return $res;
	}

    /* 删除 */
	public function del($where) {

        $model = new Model_Balance();

        return $model->del($where);

	}
	
	
}
