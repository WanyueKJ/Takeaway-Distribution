<?php
namespace App\Model;

use PhalApi\Model\NotORMModel as NotORM;

class Addr extends NotORM {
	
	public function getList($where) {
		
		$list=\PhalApi\DI()->notorm->addr
                ->select('*')
				->where($where)
                ->order('id desc')
				->fetchAll();

		return $list;
	}

    public function getDistanceList($where, $lng,  $lat) {

        $field = "*";

		$list=\PhalApi\DI()->notorm->addr
                ->select("{$field},
                (
                    6378.138 * 2 * ASIN(
                        SQRT(
                            POW( SIN( ( {$lat} * PI( ) / 180 - lat * PI( ) / 180 ) / 2 ), 2 ) + COS( {$lat} * PI( ) / 180 ) * COS( lat * PI( ) / 180 ) * POW( SIN( ( {$lng}* PI( ) / 180 - lng * PI( ) / 180 ) / 2 ), 2 ) 
                        ) 
                    ) 
                ) AS distance")
				->where($where)
                ->order('distance asc')
				->fetchAll();

		return $list;
	}
    
    public function getInfo($where,$field='*') {
		
		$info=\PhalApi\DI()->notorm->addr
                ->select($field)
				->where($where)
				->fetchOne();

		return $info;
	}
    
    public function set($data) {
		
		$rs=\PhalApi\DI()->notorm->addr
				->insert($data);
        $id = \PhalApi\DI()->notorm->addr->insert_id();

		return $id;
	}
    
    public function up($where,$data) {
		
		$rs=\PhalApi\DI()->notorm->addr
                ->where($where)
				->update($data);

		return $rs;
	}
    
    public function del($where) {
		
		$rs=\PhalApi\DI()->notorm->addr
                ->where($where)
				->delete();

		return $rs;
	}
	
	
}
