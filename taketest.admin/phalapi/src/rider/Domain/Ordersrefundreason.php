<?php
namespace Rider\Domain;

use Rider\Model\Ordersrefundreason as Model_Ordersrefundreason;

class Ordersrefundreason {

    public function getList(){

        $key='orders_refundreason';
        if(isset($GLOBALS[$key])){
            return $GLOBALS[$key];
        }
        $list=\App\getcaches($key);
        if(!$list){
            $model = new Model_Ordersrefundreason();
            $list=$model->getList();
            \App\setcaches($key,$list);
        }


        foreach($list as $k=>$v){
            unset($v['list_order']);
            $list[$k]=$v;
        }
        $GLOBALS[$key]=$list;
        return $list;
    }

    public function getInfo($id){

        $info=[];
        $list=self::getList();

        foreach($list as $k=>$v){
            if($id==$v['id']){
                $info=$v;
                break;
            }
        }

        return $info;
    }

}
