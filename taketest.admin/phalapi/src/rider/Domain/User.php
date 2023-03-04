<?php
namespace Rider\Domain;

use Rider\Model\User as Model_User;
use Rider\Domain\Login as Domain_Login;

class User {

    /**
     * 退出登录
     * @param $uid
     * @return void
     */
    public function logOut($uid)
    {

    }

    /* 用户基本信息 */
    public function getBaseInfo($uid) {

        $model = new Model_User();
        $field='id,user_nickname,avatar,avatar_thumb,sex,signature,balance,balancetotal,birthday,mobile,star,stars,evaluates,good,average,bad,isrest';
        $where=['id'=>$uid];
        $info = $model->getInfo($where,$field);

        if($info){
            //$birthday=$info['birthday'];
            $info=\App\handleUser($info);
            //$info['birthday']=date('Y-m-d',$birthday);

            unset($info['birthday']);
        }

        return $info;
    }

    /* 更新基本信息 */
    public function upUserInfo($uid,$fields=[]) {

        $rs = array('code' => 0, 'msg' => \PhalApi\T('操作成功'), 'info' => array());

        $model = new Model_User();
        $data=[];
        $info=[];
        /* 头像 */
        if( isset($fields['avatar']) && $fields['avatar']!=''  ){
            $avatar_q=$fields['avatar'];

            $avatar=$avatar_q;
            $avatar_thumb=$avatar_q;

            $data['avatar']=$avatar;
            $data['avatar_thumb']=$avatar_thumb;

            $info['avatar']=\App\get_upload_path($avatar);
            $info['avatar_thumb']=\App\get_upload_path($avatar_thumb);

        }


        /* 昵称 */
        if( isset($fields['user_nickname']) && $fields['user_nickname']!=''  ){
            $name=$fields['user_nickname'];
            $count=mb_strlen($name);
            if($count>10){
                $rs['code'] = 1002;
                $rs['msg'] = \PhalApi\T('昵称最多10个字');
                return $rs;
            }

            $isexist = $this->checkNickname($uid,$name);
            if($isexist){
                $rs['code'] = 1003;
                $rs['msg'] = \PhalApi\T('昵称已存在');
                return $rs;
            }

            $data['user_nickname']=$name;
            $info['user_nickname']=$name;
        }

        /* 生日 年龄 */
        if( isset($fields['birthday']) && $fields['birthday']!=''  ){
            $birthday=strtotime($fields['birthday']);
            $age=\App\getAge($birthday);

            $data['birthday']=$birthday;

            $info['birthday']=$birthday;
            $info['age']=$age;
        }

        /* 性别 */
        if( isset($fields['sex']) && $fields['sex']!=''  ){
            $sex=$fields['sex'];
            $data['sex']=$sex;
            $info['sex']=$sex;
        }

        /* 签名 */
        if( isset($fields['signature']) && $fields['signature']!=''  ){
            $signature=$fields['signature'];

            $data['signature']=$signature;
            $info['signature']=$signature;
        }


        if(!$data){
            $rs['code'] = 1003;
            $rs['msg'] = \PhalApi\T('信息错误');
            return $rs;
        }
        $where=['id'=>$uid];
        $result = $model->up($where,$data);

        \App\delcache("userinfo_".$uid);

        $rs['info'][0]=$info;
        return $rs;
    }

    public function checkNickname($uid,$name){

        $model = new Model_User();
        $where=[
            'id!=?'=>$uid,
            'user_nickname'=>$name,
        ];

        $isexist=$model->getInfo($where,'id');

        if($isexist ){
            return 1;
        }

        return 0;
    }

    /* 根据条件获取用户ID */
    public function getAll($where,$field='*'){

        $model = new Model_User();
        $list = $model->getAll($where,$field);

        return $list;
    }

    public function getInfo($where,$field='*'){

        $model = new Model_User();
        $info = $model->getInfo($where,$field);

        return $info;
    }

    public function up($where,$data){

        $model = new Model_User();
        $info = $model->up($where,$data);

        return $info;
    }

    public function upField($where,$field,$nums){

        $model = new Model_User();
        $info = $model->upField($where,$field,$nums);

        return $info;
    }

    public function addBalance($where,$nums){

        $model = new Model_User();
        $info = $model->addBalance($where,$nums);

        return $info;
    }

    public function upStar($where,$stars,$evaluates){

        $model = new Model_User();
        $info = $model->upStar($where,$stars,$evaluates);

        return $info;
    }


    public function del($uid){

        $model = new Model_User();
        $model->del(['id'=>$uid]);

        \App\delcache("rider_userinfo_".$uid);
        \App\delcache("rider_token_".$uid);

        return 1;
    }

    public function upPass($uid,$code,$newpass){

        $rs = ['code' => 0, 'msg' => '', 'info' => []];

        $model=new Model_User();
        $uinfo=$model->getInfo(['id'=>$uid],'mobile');
        $Domain_Login=new Domain_Login();

        $res=$Domain_Login->checkCode(5,$uinfo['mobile'],$code);
        if($res['code']!=0){
            return $res;
        }

        $pass=\App\setPass($newpass);

        $up=[
            'user_pass'=>$pass,
        ];

        $model->up(['id'=>$uid],$up);

        return $rs;

    }

    public function upMobile($uid,$mobile,$code){

        $rs = ['code' => 0, 'msg' => \PhalApi\T('更换成功'), 'info' => []];

        $model=new Model_User();
        $Domain_Login=new Domain_Login();

        $res=$Domain_Login->checkCode(4,$mobile,$code);
        if($res['code']!=0){
            return $res;
        }

        $where=[
            'mobile'=>$mobile,
        ];
        $isexist=$model->getInfo($where,'id');
        if($isexist){
            $rs['code'] = 1001;
            $rs['msg'] = \PhalApi\T('该手机号已绑定账号，请更换');
            return $rs;
        }

        $up=[
            'mobile'=>$mobile,
        ];

        $model->up(['id'=>$uid],$up);

        return $rs;

    }

    public function getCityLevel($uid){
        $key='city_level_'.$uid;

        if(isset($GLOBALS[$key])){
            return $GLOBALS[$key];
        }

        $info=self::getInfo(['id'=>$uid],'cityid,levelid');

        $GLOBALS[$key]=$info;

        return $info;
    }
}
