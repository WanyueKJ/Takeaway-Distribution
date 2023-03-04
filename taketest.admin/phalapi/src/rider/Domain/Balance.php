<?php
namespace Rider\Domain;

use Rider\Model\Balance as Model_Balance;
use Rider\Domain\User as Domain_User;

class Balance {

    public function getActions($k=''){
        $actions=[
            '1'=>'配送收入',
            '2'=>'提现支出',
            '3'=>'提现回退',
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

	public function getToday($uid){

        $nowtime=time();

        $today=date("Ymd",$nowtime);
        $today_start=strtotime($today);
        //当天 23:59:59
        $today_end=strtotime("{$today} + 1 day");

        $model = new Model_Balance();
        $where=[
            'type'=>1,
            'action'=>1,
            'uid'=>$uid,
            'addtime >=?'=>$today_start,
            'addtime <?'=>$today_end,
        ];
        $today_income=$model->getSum($where,'total');
        return $today_income;
    }

	public function getRecord($uid,$time,$p){
        $where=[
            'uid'=>$uid,
        ];

        //$nowtime=time();
        if($time!=''){
            $m_start=strtotime($time);
            $m_end=strtotime("{$time} + 1 day");

            $where['addtime >= ?']=$m_start;
            $where['addtime < ?']=$m_end;
        }


	    $list=self::getList($where,$p);

	    return $list;
    }

    /* 新增
    * $type 收支 1收入 2支出
    * $action 行为，1配送  2提现支出  3提现回退
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
