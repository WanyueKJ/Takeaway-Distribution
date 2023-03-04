<?php

namespace app\admin\controller\merchant;

use cmf\controller\AdminBaseController;
use app\admin\model\merchant\Record;
use app\admin\model\merchant\StoreModel;

/**
 * 商家提现
 */
class RecordController extends AdminBaseController
{
    public function index()
    {

        $data = $this->request->param();
        $map=[];


        $start_time=isset($data['start_time']) ? $data['start_time']: '';
        $end_time=isset($data['end_time']) ? $data['end_time']: '';

        if($start_time!=""){
            $map[]=['addtime','>=',strtotime($start_time)];
        }

        if($end_time!=""){
            $map[]=['addtime','<=',strtotime($end_time) + 60*60*24];
        }

        $status=isset($data['status']) ? $data['status']: '';
        if($status!=''){
            $map[]=['status','=',$status];
        }

        $store_id=isset($data['uid']) ? $data['uid']: '';
        if($store_id!=''){
            $map[]=['store_id','=',$store_id];
        }

        $keyword=isset($data['keyword']) ? $data['keyword']: '';
        if($keyword!=''){
            $map[]=['orderno|trade_no','like',"%".$keyword."%"];
        }
        $list = Record
            ::order('id DESC')
            ->where($map)
            ->paginate(20);

        $list->each(function ($value, $index) {
            $value['add_t'] = date('Y-m-d H:i:s', $value['addtime']);
            $value['up_t'] = $value['uptime'] > 0 ? date('Y-m-d H:i:s', $value['uptime']) : '--';
        });

        $status_a = Record::getStatus();


        $this->assign([
            'list' => $list,
            'page' => $list->render(),
            'status' => $status_a
        ]);
        return $this->fetch();
    }


    public function edit()
    {
        $id   = $this->request->param('id', 0, 'intval');

        $data=Record::where("id",$id)
            ->find();
        if(!$data){
            $this->error("信息错误");
        }

        $storeinfo=StoreModel::find($data['store_id']);


        $this->assign('data', $data);
        $this->assign('storeinfo', $storeinfo);
        $status_a=Record::getStatus();
        $this->assign('status', $status_a);

        return $this->fetch();
    }

    public function editPost()
    {
        if ($this->request->isPost()) {
            $data      = $this->request->param();

            $id=$data['id'] ?? 0;
            $status=$data['status'] ?? 0;

            $info=Record::where('id',$id)->find();
            if(!$info){
                $this->error("信息错误！");
            }

            if($info['status']!=0){
                $this->error("不能多次处理！");
            }

            $nowtime=time();
            $data['uptime']=$nowtime;

            $rs = Record::update($data);

            if($rs === false){
                $this->error("保存失败！");
            }

            $this->success("保存成功！");
        }
    }

}