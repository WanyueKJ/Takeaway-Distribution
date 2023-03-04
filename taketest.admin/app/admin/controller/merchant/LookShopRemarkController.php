<?php

namespace app\admin\controller\merchant;

use app\admin\model\merchant\TypeModel;
use cmf\controller\AdminBaseController;
use app\admin\model\merchant\TypeEvaluate;

/**
 * 找店点评
 */
class LookShopRemarkController extends AdminBaseController
{

    public function addEvaluatePost()
    {
        $type_id = $this->request->param('type_id', 0);
        $id = $this->request->param('id', 0);
        $name = $this->request->param('name');

        /** @var TypeEvaluate $TypeEvaluate */
        $TypeEvaluate = app()->make(TypeEvaluate::class);

        if ($id > 0) {
            $TypeEvaluate->where('id', $id)->update(['name' => $name]);
        } else {
            $count = $TypeEvaluate->where('type_id', $type_id)->count();
            if ($count >= 3) $this->error('最多加三个');
            $TypeEvaluate->save(['name' => $name, 'type_id' => $type_id]);
        }

        $this->success('操作成功');
    }

    public function addEvaluate()
    {
        $type_id = $this->request->param('type_id', 0);
        $this->assign(['type_id' => $type_id]);

        return $this->fetch();
    }

    public function editEvaluate()
    {
        $id = $this->request->param('id', 0);

        /** @var TypeEvaluate $TypeEvaluate */
        $TypeEvaluate = app()->make(TypeEvaluate::class);

        $data = $TypeEvaluate->find($id);
        if (!$data) $this->error('信息不存在');

        $this->assign([
            'data' => $data,
            'id' => $id,
        ]);

        return $this->fetch();
    }

    public function evaluate()
    {
        $id = $this->request->param('id', 0);
        if (!$id) {
            $this->error('参数错误');
        }
        /** @var TypeEvaluate $TypeEvaluate */
        $TypeEvaluate = app()->make(TypeEvaluate::class);
        $list = $TypeEvaluate->where('type_id', $id)->select();

        $this->assign(['list' => $list]);
        $this->assign(['type_id' => $id]);
        return $this->fetch();
    }

    public function delete()
    {
        $id = $this->request->param('id', 0);
        if (!$id) {
            $this->error('参数错误');
        }
        $TypeEvaluate = app()->make(TypeEvaluate::class);
        $TypeEvaluate->where('id', $id)->delete();

        $this->success('操作成功');

    }
}