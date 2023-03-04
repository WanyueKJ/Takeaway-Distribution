<?php
namespace Rider\Model;

use PhalApi\Model\NotORMModel as NotORM;

class Ordersrefuse extends NotORM {

	public function getAll($where,$field='*') {

		$list=\PhalApi\DI()->notorm->orders_refuse
                ->select($field)
                ->where($where)
                ->order('id asc')
				->fetchAll();
		return $list;
	}

	public function getInfo($where,$field='*') {

		$info=\PhalApi\DI()->notorm->orders_refuse
                ->select($field)
                ->where($where)
				->fetchOne();

		return $info;
	}

	public function add($data) {
		$info=\PhalApi\DI()->notorm->orders_refuse
				->insert($data);

		return $info;
	}

	public function up($where,$data) {

		$info=\PhalApi\DI()->notorm->orders_refuse
                ->where($where)
				->update($data);

		return $info;
	}

	public function del($where) {

		$info=\PhalApi\DI()->notorm->orders_refuse
                ->where($where)
				->delete();

		return $info;
	}

}
