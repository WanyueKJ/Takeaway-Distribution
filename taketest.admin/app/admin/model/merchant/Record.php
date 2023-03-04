<?php

namespace app\admin\model\merchant;
use think\Model;
use app\admin\model\merchant\StoreModel;


class Record extends Model
{
    protected $pk = 'id';
    protected $name = 'merchant_record';

    public static $redis_key = 'merchant_record';


    public function getStatusAttr($value){
        $status=array(
            '0'=>'待审核',
            '1'=>'已通过',
            '2'=>'已拒绝',
        );
        if(!array_key_exists($value,$status)){
            return '--';
        }

        return $status[$value] ?? '';
    }


    public static function getStatus($k=''){
        $status=array(
            '0'=>'待审核',
            '1'=>'已通过',
            '2'=>'已拒绝',
        );
        if($k===''){
            return $status;
        }

        return $status[$k] ?? '';
    }

    public function storeinfo (){
        return $this->belongsTo(StoreModel::class, 'store_id', 'id');
    }
}