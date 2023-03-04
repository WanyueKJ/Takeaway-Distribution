<?php
namespace Rider\Model;

use PhalApi\Model\NotORMModel as NotORM;

class City extends NotORM {
	
	public function getList() {
		
		$list=\PhalApi\DI()->notorm->city
                ->select('*')
                //->where(['status'=>1])
                ->order('pid asc, list_order asc')
				->fetchAll();

		return $list;
	}
}
