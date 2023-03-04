<?php
namespace Rider\Model;

use PhalApi\Model\NotORMModel as NotORM;

class Evaluate extends NotORM {


    public function getAverage($where){
        $info=\PhalApi\DI()->notorm->evaluate
            ->select("(sum(star)/count(id)) as average")
            ->where($where)
            ->fetchOne();
        return $info['average'] ? round($info['average'],1): '0.0';
    }

	public function getList($where,$p=0,$nums=0) {
	    $start=0;
		if($p>0){
		    if($nums==0){
		        $nums=20;
            }
            $start=($p-1)*$nums;
        }
		$list=\PhalApi\DI()->notorm->evaluate
                ->select('*')
				->where($where)
                ->order('id desc');

		if($nums>0){
            $list->limit($start,$nums);
        }

		return $list->fetchAll();
	}

    public function getInfo($where,$field='*') {

		$info=\PhalApi\DI()->notorm->evaluate
                ->select($field)
				->where($where)
				->fetchOne();

		return $info;
	}

	public function getNums($where) {

		$info=\PhalApi\DI()->notorm->evaluate
				->where($where)
				->count();

		return $info;
	}

    public function add($data) {

		$rs=\PhalApi\DI()->notorm->evaluate
				->insert($data);

		return $rs;
	}

    public function up($where,$data) {

		$rs=\PhalApi\DI()->notorm->evaluate
                ->where($where)
				->update($data);

		return $rs;
	}

    public function del($where) {

		$rs=\PhalApi\DI()->notorm->evaluate
                ->where($where)
				->delete();

		return $rs;
	}


}
