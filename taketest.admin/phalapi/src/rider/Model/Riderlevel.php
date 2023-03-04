<?php
namespace Rider\Model;

use PhalApi\Model\NotORMModel as NotORM;

class Riderlevel extends NotORM {

	public function getList() {

		$list=\PhalApi\DI()->notorm->rider_level
                ->select('*')
                ->order('levelid asc')
				->fetchAll();
		return $list;
	}

	public function getInfo($where,$field='*') {

		$info=\PhalApi\DI()->notorm->rider_level
                ->select($field)
                ->where($where)
				->fetchOne();

		return $info;
	}

}
