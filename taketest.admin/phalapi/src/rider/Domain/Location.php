<?php
namespace Rider\Domain;

use Rider\Model\Location as Model_Location;
use Rider\Model\User as Model_User;
use Rider\Domain\City as Domain_City;


class Location {

    public function getLocation($where){
        $location=[
            'lng'=>'',
            'lat'=>'',
        ];
        $info= self::getInfo($where);
        if(!$info){
            return $location;
        }

        unset($info['uid']);
        unset($info['addtime']);

        return $info;
    }

    public function getInfo($where,$field='*'){
        $model = new Model_Location();

        return $model->getInfo($where,$field);

    }


    public function up($where,$data){
        $model = new Model_Location();

        return $model->up($where,$data);

    }

    public function del($data){
        $model = new Model_Location();

        return $model->del($data);

    }

    public function setLocation($uid,$lng,$lat){

        $rs = ['code' => 0, 'msg' => '', 'info' => []];
        if($lng=='' || $lat==''){
            $rs['code'] = 1001;
            $rs['msg'] = \PhalApi\T('位置信息错误');
            return $rs;
        }

        $model = new Model_Location();
        $where=[
            'uid'=>$uid
        ];
        $up=[
            'lng'=>$lng,
            'lat'=>$lat,
            'addtime'=>time(),
        ];

        $isexist=$model->getInfo($where);
        if(!$isexist){
            $up['uid']=$uid;
            $model->add($up);
        }else{
            $model->up($where,$up);
        }

        return $rs;
    }

    public function getNearby($cityid,$lng,$lat){
        $list=[];
        if($cityid<1 || $lng=='' || $lat==''){
            return $list;
        }

        $Domain_City=new Domain_City();
        $config=$Domain_City->getConfig($cityid);
        if(!$config){
            return $list;
        }

        $Model_User=new Model_User();

        $riders=$Model_User->getAll(['cityid'=>$cityid,'isrest'=>0],'id');
        if(!$riders){
            return $list;
        }

        $riderids=array_column($riders,'id');

        $model = new Model_Location();
        $where=[
            'uid'=>$riderids
        ];
        $list=$model->getAll($where);

        $limit=$config['rider_distance'];

        foreach ($list as $k=>$v){
            $distance=\App\getDistance($lat,$lng,$v['lat'],$v['lng']);
            if($distance>$limit){
                unset($list[$k]);
                continue;
            }

            $v['distance']=$distance;
            unset($v['uid']);
            unset($v['addtime']);
            $list[$k]=$v;
        }

        $list=array_values($list);
        return $list;
    }

}
