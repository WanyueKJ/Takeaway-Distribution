<?php
namespace Rider\Model;

use PhalApi\Model\NotORMModel as NotORM;

class Token extends NotORM {


	public function getInfo($where,$field='*') {
		$info=\PhalApi\DI()->notorm->rider_token
				->select($field)
				->where($where)
				->fetchOne();
		return $info;
	}


	public function add($data){

        $rs=\PhalApi\DI()->notorm->rider_token
                ->insert($data);

        return $rs;
	}

    public function up($where,$data){

        $rs=\PhalApi\DI()->notorm->rider_token
            ->where($where)
            ->update($data);

        return $rs;
    }

    public function del($where){

        $rs=\PhalApi\DI()->notorm->rider_token
            ->where($where)
            ->delete();

        return $rs;
    }




}
