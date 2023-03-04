<?php
namespace Rider\Model;

use PhalApi\Model\NotORMModel as NotORM;

class Orderscount extends NotORM {

	public function getNums($where) {

		$list=\PhalApi\DI()->notorm->rider_order_count
                ->where($where)
				->count();
		return $list;
	}


	public function getAll($where,$field='*') {

		$list=\PhalApi\DI()->notorm->rider_order_count
                ->select($field)
                ->where($where)
                ->order('id asc')
				->fetchAll();
		return $list;
	}


	public function getList($where,$p) {

        if($p<1){
            $p=1;
        }

        $nums=20;

        $start=($p-1) * $nums;

		$list=\PhalApi\DI()->notorm->rider_order_count
                ->select('*')
                ->where($where)
                ->order('id desc')
                ->limit($start,$nums)
				->fetchAll();

		return $list;
	}


	public function getInfo($where,$field='*') {

		$info=\PhalApi\DI()->notorm->rider_order_count
                ->select($field)
                ->where($where)
				->fetchOne();

		return $info;
	}


	public function add($data) {
		$info=\PhalApi\DI()->notorm->rider_order_count
				->insert($data);

		return $info;
	}


	public function up($where,$data) {

		$info=\PhalApi\DI()->notorm->rider_order_count
                ->where($where)
				->update($data);

		return $info;
	}

	public function upNums($where,$orders,$transfers,$distance) {

        $up=[
            'orders' => new \NotORM_Literal("orders + {$orders}"),
            'transfers' => new \NotORM_Literal("transfers + {$transfers}"),
            'distance' => new \NotORM_Literal("distance + {$distance}"),
        ];
		$info=\PhalApi\DI()->notorm->rider_order_count
                ->where($where)
				->update($up);

		return $info;
	}

}
