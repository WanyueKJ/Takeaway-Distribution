<?php
namespace Rider\Model;

use PhalApi\Model\NotORMModel as NotORM;

class Orders extends NotORM {
	/* 订单列表 */
	public function getNums($where) {

		$list=\PhalApi\DI()->notorm->orders
                ->where($where)
				->count();
		return $list;
	}

    /* 订单列表 */
	public function inStatusGetCount($status,$where) {

		$list=\PhalApi\DI()->notorm->orders
                ->where('status',$status)
                ->where($where)
				->count();
		return $list;
	}

	/* 订单列表 */
	public function getAll($where,$field='*') {

		$list=\PhalApi\DI()->notorm->orders
                ->select($field)
                ->where($where)
                ->order('id asc')
				->fetchAll();
		return $list;
	}

	/* 订单列表 */
	public function getList($where,$p) {

        if($p<1){
            $p=1;
        }

        $nums=20;

        $start=($p-1) * $nums;
		$list=\PhalApi\DI()->notorm->orders
                ->select('*')
                ->where($where)
                ->order('id desc')
                ->limit($start,$nums)
				->fetchAll();

		return $list;
	}

	/* 订单信息 */
	public function getInfo($where,$field='*') {
	
		$info=\PhalApi\DI()->notorm->orders
                ->select($field)
                ->where($where)
				->fetchOne();

		return $info;
	}

	/* 添加信息 */
	public function add($data) {
		$info=\PhalApi\DI()->notorm->orders
				->insert($data);

		return \PhalApi\DI()->notorm->orders->insert_id();
	}

	/* 更新信息 */
	public function up($where,$data) {
		$info=\PhalApi\DI()->notorm->orders
                ->where($where)
				->update($data);

		return $info;
	}

	public function addFee($orderid,$fee) {

        $up=[
            'fee' => new \NotORM_Literal("fee + {$fee}"),
            'money' => new \NotORM_Literal("money + {$fee}"),
            'money_total' => new \NotORM_Literal("money_total + {$fee}"),
        ];
		$info=\PhalApi\DI()->notorm->orders
                ->where(['id'=>$orderid])
				->update($up);

		return $info;
	}

}
