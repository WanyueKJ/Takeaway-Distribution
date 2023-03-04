<?php

namespace app\admin\controller\merchant;

use cmf\controller\AdminBaseController;
use app\admin\model\merchant\Enter;

/**
 * 商家管理-商家入驻
 */
class EnterController extends AdminBaseController
{
    public function index()
    {
        $data = $this->request->param();

        $where = [];
        if (isset($data['status']) && in_array($data['status'], ['-1', '0', '1'])) {
            $where[] = ['status', '=', $data['status']];
        }
        if (isset($data['type']) && in_array($data['type'], ['1', '2', '3'])) {
            $where[] = ['type', '=', $data['type']];
        }
        if (isset($data['start_time']) && $data['start_time']) {
            $where[] = ['addtime', '>=', strtotime($data['start_time'])];
        }
        if (isset($data['end_time']) && $data['end_time']) {
            $where[] = ['addtime', '<=', strtotime($data['end_time'])];
        }
        $list = Enter::order('id asc')->where($where)->paginate(20);

        $this->assign("status", $this->getStatus());
        $this->assign("type", $this->getTypes());
        $this->assign("list", $list);
        $this->assign("page", $list->render());

        return $this->fetch();
    }

    public function getStatus()
    {
        return [
            0 => '未处理',
//            -1 => '已处理',
            1 => '已处理'
        ];
    }

    public function getTypes()
    {
        return [
            '1' => '商家入驻',
            '2' => '骑手入驻',
            '3' => '商务合作',
        ];
    }


    /**
     * 修改状态
     * @return void
     */
    public function updateStatus()
    {
        $data = $this->request->param();
        $id = $data['id'];
        $status = $data['status'];
        if (!$id) {
            $this->error("参数错误！");
        }

        if (!in_array($status, [-1, 0, 1])) {
            $this->error("状态参数错误！");
        }

        $update = [
            'status' => $status
        ];

        $EnterModel = app()->make(Enter::class);
        $EnterModel->where('id', $id)->update($update);
        $this->success("修改成功！");

    }
}