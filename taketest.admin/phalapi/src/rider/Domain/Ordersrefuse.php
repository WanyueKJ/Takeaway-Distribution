<?php
namespace Rider\Domain;

use Rider\Model\Ordersrefuse as Model_Ordersrefuse;

class Ordersrefuse {


    public function getAll($where,$field='*'){
        $model = new Model_Ordersrefuse();

        $list=$model->getAll($where,$field);

        return $list;
    }

    public function add($data){
        $model = new Model_Ordersrefuse();

        $list=$model->add($data);

        return $list;
    }

    public function getRefuseids($riderid){
        $model = new Model_Ordersrefuse();

        $where=[
            'riderid'=>$riderid
        ];
        $list=$model->getAll($where,'oid');
        if(!$list){
            return [];
        }

        $orderids=array_column($list,'oid');

        $orderids=array_unique($orderids);

        return $orderids;
    }

    public function delorder($oid){
        $model = new Model_Ordersrefuse();

        $where=[
            'oid'=>$oid
        ];
        return $model->del($where);

    }


}
