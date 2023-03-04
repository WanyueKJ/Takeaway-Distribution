<?php
namespace Rider\Model;

use PhalApi\Model\NotORMModel as NotORM;

class User extends NotORM {

    public function getInfo($where,$field='*'){

        $info=\PhalApi\DI()->notorm->rider
            ->select($field)
            ->where($where)
            ->fetchOne();

        return $info;
    }

	public function add($data){
        $rs=\PhalApi\DI()->notorm->rider
            ->insert($data);

        return $rs;
	}


	public function up($where,$data){
        $rs=\PhalApi\DI()->notorm->rider
            ->where($where)
            ->update($data);
        return $rs;
	}

	public function upField($where,$field,$nums){
        $rs=\PhalApi\DI()->notorm->rider
            ->where($where)
            ->update(["{$field}" => new \NotORM_Literal("{$field} + {$nums}")]);
        return $rs;
	}

	public function addBalance($where,$nums){
        $up=[
            "balance" => new \NotORM_Literal("balance + {$nums}"),
            "balancetotal" => new \NotORM_Literal("balancetotal + {$nums}"),
        ];
        $rs=\PhalApi\DI()->notorm->rider
            ->where($where)
            ->update($up);
        return $rs;
	}

	public function del($where){
        $rs=\PhalApi\DI()->notorm->rider
            ->where($where)
            ->delete();

        return $rs;
	}


    public function getAll($where,$field='*'){
        
        $list=\PhalApi\DI()->notorm->rider
				->select($field)
                ->where($where)
				->fetchAll();
        
        return $list;
    }

    public function upStar($where,$stars=1,$evaluates=1) {
        $good=0;
        $average=0;
        $bad=0;

        if($stars>=4){
            $good++;
        }
        if($stars>2 && $stars<4){
            $average++;
        }
        if($stars<=2){
            $bad++;
        }

        $up=[
            'stars' => new \NotORM_Literal("stars + {$stars}"),
            'evaluates' => new \NotORM_Literal("evaluates + {$evaluates}"),
            'good' => new \NotORM_Literal("good + {$good}"),
            'average' => new \NotORM_Literal("average + {$average}"),
            'bad' => new \NotORM_Literal("bad + {$bad}"),
        ];
        $rs=\PhalApi\DI()->notorm->rider
            ->where($where)
            ->update($up);
        if($rs){
            $info=\PhalApi\DI()->notorm->rider
                ->select('stars,evaluates')
                ->where($where)
                ->fetchOne();

            $star=\App\handleStar($info['stars'],$info['evaluates']);

            \PhalApi\DI()->notorm->rider
                ->where($where)
                ->update(['star' => $star]);
        }


        return $rs;
    }

}
