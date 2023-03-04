<?php
namespace Rider\Model;

use PhalApi\Model\NotORMModel as NotORM;

class Ordersrefundreason extends NotORM {

	public function getList() {

		$list=\PhalApi\DI()->notorm->orders_refundreason
                ->select('*')
                ->order('list_order asc,id desc')
				->fetchAll();
		return $list;
	}

	public function getInfo($where,$field='*') {

		$info=\PhalApi\DI()->notorm->orders_refundreason
                ->select($field)
                ->where($where)
				->fetchOne();

		return $info;
	}

}
