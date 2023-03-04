<?php
namespace App\Domain;

use App\ApiException;
use App\Model\Addr as Model_Addr;

class Addr {

    public function checkExceedAddress($uid, $lng, $lat,$store_lng,$store_lat){
        $rs = ['code' => 0, 'msg' => '', 'info' => []];

        $noriko_sakai = \App\getConfigPri()['noriko_sakai'] ?? 50;
        $distance = \App\getDistance($lng,$lat,$store_lng,$store_lat);
        if($distance > $noriko_sakai){
            throw new ApiException(\PhalApi\T('您的地址超出商家配送范围了'));
        }
        return $rs;
    }


    /* 列表 */
	public function getList($where=[]) {


        $model = new Model_Addr();
        $list= $model->getList($where);

        foreach ($list as $key => &$value){
            $value['exceed'] = 0;
        }
		return $list;
	}

    /* 列表 */
	public function getDistanceList($where=[], $lng,  $lat) {


        $model = new Model_Addr();
        $list= $model->getDistanceList($where, $lng ?? 0,  $lat ?? 0);

        $noriko_sakai = \App\getConfigPri()['noriko_sakai'] ?? 50;
        foreach ($list as $key => &$value){
            $value['exceed'] = 0;
            $value['distance'] = round($value['distance'],2);
            if($value['distance'] > $noriko_sakai){
                $value['exceed'] = 1;
            }

        }

        return $list;
	}
    
    /* 某个信息 */
	public function getInfo($where=[],$field='*') {

        $model = new Model_Addr();
        $info= $model->getInfo($where,$field);

		return $info;
	}

	public function checkInfo($place,$lng,$lat,$addr,$name,$mobile){
        $rs = ['code' => 0, 'msg' => '', 'info' => []];
        if($place==''){
            $rs['code'] = 1001;
            $rs['msg'] = \PhalApi\T('请选择位置');
            return $rs;
        }

        if($lng==''){
            $rs['code'] = 1002;
            $rs['msg'] = \PhalApi\T('请选择位置');
            return $rs;
        }

        if($lat==''){
            $rs['code'] = 1003;
            $rs['msg'] = \PhalApi\T('请选择位置');
            return $rs;
        }

        if($addr==''){
            $rs['code'] = 1001;
            $rs['msg'] = \PhalApi\T('请填写门牌号');
            return $rs;
        }

        if($name==''){
            $rs['code'] = 1001;
            $rs['msg'] = \PhalApi\T('请填写联系人');
            return $rs;
        }

        if($mobile==''){
            $rs['code'] = 1001;
            $rs['msg'] = \PhalApi\T('请填写联系人手机号');
            return $rs;
        }

        return $rs;
    }
    
    /* 新增 */
	public function set($uid,$place,$lng,$lat,$addr,$name,$mobile) {
        $rs = array('code' => 0, 'msg' => '', 'info' => array());

	    $res=$this->checkInfo($place,$lng,$lat,$addr,$name,$mobile);
	    if($res['code']!=0){
	        return $res;
        }
        
        $rs = ['code' => 0, 'msg' => \PhalApi\T('新增成功'), 'info' => []];

        $data=[
            'uid'=>$uid,
            'name'=>$name,
            'mobile'=>$mobile,
            'place'=>$place,
            'lng'=>$lng,
            'lat'=>$lat,
            'addr'=>$addr,
            'addtime'=>time(),
        ];

        $model = new Model_Addr();
        $res= $model->set($data);
        if($res==false){
            $rs['code'] = 1002;
			$rs['msg'] = \PhalApi\T('新增失败，请重试');
			return $rs;
        }
        $addr = $model->getInfo(['id = ?'=>$res]);
        $rs['info'][] = $addr;
		return $rs;
	}
    
    /* 编辑 */
	public function up($uid,$addrid,$place,$lng,$lat,$addr,$name,$mobile) {

        $res=$this->checkInfo($place,$lng,$lat,$addr,$name,$mobile);
        if($res['code']!=0){
            return $res;
        }

        $rs = ['code' => 0, 'msg' => \PhalApi\T('编辑成功'), 'info' => []];

        $model = new Model_Addr();
        
        $where=[
            'id'=>$addrid,
        ];
        
        $info= $model->getInfo($where);
        
        if(!$info){
            $rs['code'] = 1002;
			$rs['msg'] = \PhalApi\T('信息错误');
			return $rs;
        }
        
        if($info['uid']!=$uid){
            $rs['code'] = 1003;
			$rs['msg'] = \PhalApi\T('信息错误');
			return $rs;
        }

        $data=[
            'name'=>$name,
            'mobile'=>$mobile,
            'place'=>$place,
            'lng'=>$lng,
            'lat'=>$lat,
            'addr'=>$addr,
        ];
        
        $res= $model->up($where,$data);
        if($res===false){
            $rs['code'] = 1004;
			$rs['msg'] = \PhalApi\T('编辑失败，请重试');
			return $rs;
        }

		return $rs;
	}

    /* 删除 */
	public function del($where) {

        $model = new Model_Addr();

        return $model->del($where);

	}
	
	
}
