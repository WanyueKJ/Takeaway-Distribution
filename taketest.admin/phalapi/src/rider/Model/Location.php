<?php
namespace Rider\Model;

use PhalApi\Model\NotORMModel as NotORM;

class Location extends NotORM {

	public function getAll($where,$field='*') {

		$info=\PhalApi\DI()->notorm->rider_location
                ->select($field)
                ->where($where)
				->fetchAll();

		return $info;
	}

	public function getInfo($where,$field='*') {

		$info=\PhalApi\DI()->notorm->rider_location
                ->select($field)
                ->where($where)
				->fetchOne();

		return $info;
	}

	public function add($data) {
		$info=\PhalApi\DI()->notorm->rider_location
				->insert($data);

		return $info;
	}

	public function up($where,$data) {

		$info=\PhalApi\DI()->notorm->rider_location
                ->where($where)
				->update($data);

		return $info;
	}

	public function del($where) {

		$info=\PhalApi\DI()->notorm->rider_location
                ->where($where)
				->delete();

		return $info;
	}

}
