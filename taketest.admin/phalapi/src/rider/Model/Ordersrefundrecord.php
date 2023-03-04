<?php
namespace Rider\Model;

use PhalApi\Model\NotORMModel as NotORM;

class Ordersrefundrecord extends NotORM {

	public function getAll($where,$field='*') {

		$list=\PhalApi\DI()->notorm->orders_refundrecord
                ->select($field)
                ->where($where)
                ->order('id desc')
				->fetchAll();
		return $list;
	}

	public function getInfo($where,$field='*') {

		$info=\PhalApi\DI()->notorm->orders_refundrecord
                ->select($field)
                ->where($where)
				->fetchOne();

		return $info;
	}

	public function add($data) {
		$info=\PhalApi\DI()->notorm->orders_refundrecord
				->insert($data);

		return $info;
	}

	public function up($where,$data) {

		$info=\PhalApi\DI()->notorm->orders_refundrecord
                ->where($where)
				->update($data);

		return $info;
	}

	public function del($where) {

		$info=\PhalApi\DI()->notorm->orders_refundrecord
                ->where($where)
				->delete();

		return $info;
	}

}
