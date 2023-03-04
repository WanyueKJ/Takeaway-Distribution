<?php

namespace app\models;

use think\Model;

class CityModel extends Model
{
    protected $pk = 'id';
    protected $name = 'city';
    public static $redis_key = 'citylist';

    protected function setAreaCodeAttr($value)
    {
        return substr(str_pad($value,8,0,STR_PAD_RIGHT),0,8);
    }

    public static function resetcache(){
        $key=self::$redis_key;

        //$list=self::where(['status'=>1])->order("pid asc, list_order asc")->select();
        $list=self::order("pid asc, list_order asc")->select();
        if($list){
            setcaches($key,$list);
        }else{
            delcache($key);
        }

        return $list;
    }
    /* 列表 */
    public static function getList(){
        $key=self::$redis_key;

        if(isset($GLOBALS[$key])){
            return $GLOBALS[$key];
        }
        $list=getcaches($key);
        if(!$list){
            $list=self::resetcache();
        }

        foreach($list as $k=>$v){
            unset($v['list_order']);
            $list[$k]=$v;
        }

        $GLOBALS[$key]=$list;
        return $list;

    }

    public static function getStatus($k=''){
        $status=[
            '0'=>'未开通',
            '1'=>'已开通',
        ];

        if($k===''){
            return $status;
        }
        return  $status[$k] ?? '' ;
    }

    /* 某信息 */
    public static function getInfo($id){

        $info=[];

        if($id<1){
            return $info;
        }
        $list=self::getList();

        foreach($list as $k=>$v){
            if($v['id']==$id){
                $info=$v;
                break;
            }
        }

        return $info;
    }

    /* 一级分类 */
    public static function getLevelOne(){
        return self::where(['pid'=>0])->order("list_order asc")->column('*','id');
    }

    public static function getTimePeriod(){
        $h=[];
        for($n=0;$n<24;$n++){
            $h[]=str_pad($n,2,0,STR_PAD_LEFT);
        }
        $i=['00','30'];

        return [
            'h'=>$h,
            'i'=>$i,
        ];
    }

    public static function getLevelList(){

        $list2=[];
        $list=self::getList();
        $list=handelList($list);

        foreach ($list as $k=>$v){
            foreach ($v['list'] as $k2=>$v2){

                $v2['name2']=$v['name'].'-'.$v2['name'];

                $list2[]=$v2;
            }
        }


        return $list2;
    }

    public static function getNoOpen(){
        $list=self::getLevelList();
        foreach ($list as $k=>$v){
            if($v['status']==0){
                continue;
            }
            //unset($list[$k]);
        }

        return array_values($list);
    }

    public static function setStatus($cityid,$status){
        if($cityid<1){
            return 0;
        }

        self::where(['cityid'=>$cityid])->update(['status'=>$status]);
        self::resetcache();

        return 1;
    }

    public static function getTwoCityName($id){

        $name='';

        if($id<1){
            return $name;
        }
        $list=self::getLevelList();

        foreach($list as $k=>$v){
            if($v['id']==$id){
                $name=$v['name2'];
                break;
            }
        }

        return $name;

    }

}