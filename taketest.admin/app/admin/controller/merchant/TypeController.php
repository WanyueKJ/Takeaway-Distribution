<?php

namespace app\admin\controller\merchant;

use app\admin\model\merchant\TypeModel;
use app\admin\model\merchant\StoreModel;
use app\admin\model\merchant\StoreProduct;
use app\admin\model\merchant\TypeEvaluate;
use cmf\controller\AdminBaseController;
use tree\Tree;

/**
 * 店铺管理-店铺类型
 */
class TypeController extends AdminBaseController
{
    public function index()
    {
        $result = TypeModel::order('pid asc,list_order asc')
            ->where('top_id', 'NOT IN', [2, 4])->select()
            ->toArray();
        $tree = new Tree();
        $tree->icon = ['&nbsp;&nbsp;&nbsp;│ ', '&nbsp;&nbsp;&nbsp;├─', '&nbsp;&nbsp;&nbsp;└─ '];
        $tree->nbsp = '&nbsp;&nbsp;&nbsp;';

        foreach ($result as $k => $m) {
            $m['parent_id'] = $m['pid'];
            $thumb_bd = '';
            $thumb = get_upload_path($m['thumb']);
            if ($thumb != '') {
                $thumb_bd = "<img src='{$thumb}'>";
            }

            $m['thumb'] = $thumb_bd;

            $background_bd = '';
            $background = get_upload_path($m['background']);
            if ($thumb != '') {
                $background_bd = "<img src='{$background}'>";
            }

            $m['background'] = $background_bd;
            $m['font_color'] = '<input style="width:50px;height:20px;background:' . $m['font_color'] . ';border:none;" disabled="">';
            $m['count'] = $this->getCount($m['id']);

            $result[$k] = $m;
        }
        foreach ($result as $key => $value) {
            $result[$key]['parent_id_node'] = ($value['parent_id']) ? ' class="child-of-node-' . $value['parent_id'] . '"' : '';
            $result[$key]['style'] = empty($value['parent_id']) ? '' : 'display:none;';

            $result[$key]['str_manage'] = '<a class="layui-bo layui-bo-small layui-bo-checked"  href="' . url("merchant.type/edit", array("id" => $value["id"])) . '"">' . lang('EDIT') . '</a>';
            if ($value['id'] > 8) {
                $result[$key]['str_manage'] .= ' <a class="layui-bo layui-bo-small layui-bo-close js-ajax-delete" href="' . url('merchant.type/del', array('id' => $value['id'])) . '">' . lang('DELETE') . '</a>';
            }

        }
        $tree->init($result);
        $str = "<tr id='node-\$id' \$parent_id_node style='\$style'>
                        <td style='padding-left:20px;'><input name='list_orders[\$id]' type='text' size='3' value='\$list_order' class='input input-order'></td>
                        <td>\$id</td>
                        <td>\$spacer\$name</td>
                       
                        <td>\$count</td>
                        <td>\$thumb</td>
                        <td>\$str_manage</td>
                    </tr>";
        $category = $tree->getTree(0, $str);
        $this->assign("category", $category);

        return $this->fetch();
    }

    public function getCount($topTypeId)
    {
        $TypeModel = new TypeModel();
        $StoreModel = new StoreModel();
        $children = $TypeModel->getTree($topTypeId);
        $childrenId = array_column($children, 'id');
        array_unshift($childrenId, $topTypeId);

        $count = $StoreModel->where([
            ['type_id', 'IN', $childrenId]
        ])->count();
        return $count;

    }


    public function add()
    {
        $data = $this->request->param();

        $pid = $data['pid'] ?? 0;
        $this->assign('pid', $pid);

        $list = TypeModel::order('pid asc,list_order asc')
            ->where('top_id', 'NOT IN', [2, 4])
            ->where(function ($query) {
                $query->where(function ($query) {
                    $query->where('top_id', '=', 1)->where('level', '<=', 2);
                })->whereOr(function ($query) {
                    $query->where('top_id', 'IN', [5, 6, 7, 8])->where('level', '<=', 1);
                });
            })
            ->select()
            ->toArray();
        $list = sort_list_tier($list);
        $this->assign('list', $list);

        return $this->fetch();
    }

    public function addPost()
    {
        if ($this->request->isPost()) {
            $data = $this->request->param();

            $name = $data['name'];
            $pid = $data['pid'];

            if ($name == '') {
                $this->error('请填写名称');
            }

            $thumb = $data['thumb'] ?? '';
            if ($thumb == '') {
                $this->error('请上传图标');
            }

            $data['font_color'] = $data['font_color'] ?? '#000000';
            $topInfo = TypeModel::getTopInfo($pid);
            if ($pid > 0 && !$topInfo) {
                $this->error('数据错误');
            }
            if ($pid > 0) {
                $data['top_id'] = $topInfo['id'];
                $data['level'] = $topInfo['level'] + 1;
            }

            $id = TypeModel::insertGetId($data);
            if (!$id) {
                $this->error("添加失败！");
            }
            TypeModel::resetcache();

            if ($topInfo['id'] == 4) {
//                找店分类
                /** @var TypeEvaluate $TypeEvaluate */
                $TypeEvaluate = app()->make(TypeEvaluate::class);
                $TypeEvaluate->addDefault($id);
            }

            $this->success("添加成功！");
        }
    }

    public function edit()
    {
        $id = $this->request->param('id', 0, 'intval');

        /** @var TypeModel $TypeModel */
        $TypeModel = app()->make(TypeModel::class);

        $data = TypeModel::where("id={$id}")->find();
        if (!$data) {
            $this->error("信息错误");
        }
        $data['top_type_id'] = $TypeModel->getTopInfo($id)['id'] ?? 0;
        $this->assign('data', $data);

        $list = TypeModel::getLevelOne();
        $this->assign('list', $list);

        return $this->fetch();
    }

    public function editPost()
    {
        if ($this->request->isPost()) {
            $data = $this->request->param();

            $id = $data['id'];
            $name = $data['name'];

            if ($name == '') {
                $this->error('请填写名称');
            }

            $thumb = $data['thumb'];
            if ($thumb == '') {
                $this->error('请上传图标');
            }

            $data['font_color'] = $data['font_color'] ?? '#000000';
            $rs = TypeModel::update($data);

            if ($rs === false) {
                $this->error("保存失败！");
            }
            TypeModel::resetcache();
            $this->success("保存成功！");
        }
    }

    public function listOrder()
    {
        $model = new TypeModel;
        parent::listOrders($model);
        TypeModel::resetcache();
        $this->success("排序更新成功！");
    }

    public function del()
    {
        $id = $this->request->param('id', 0, 'intval');

        if ((int)$id < 8) {
            $this->error("禁止删除！");
        }
        $child = TypeModel::where('pid', $id)->find();
        if ($child) $this->error("有子级分类,无法删除！");

        $product = StoreProduct::where('type_id', $id)->where('is_del', 0)->find();
        if ($product) $this->error("有商品正在使用中,无法删除！");

        $store = StoreModel::where('top_type_id', $id)->find();
        if ($store) $this->error("有店铺正在使用中,无法删除！");
        $topInfo = TypeModel::getTopInfo($id);
        if (!$topInfo) {
            $this->error('数据错误');
        }

        $rs = TypeModel::where('id', $id)->delete();
        if (!$rs) {
            $this->error("删除失败！");
        }
        if ($topInfo['id'] == 4) {
            /** @var TypeEvaluate $TypeEvaluate */
            $TypeEvaluate = app()->make(TypeEvaluate::class);
            $TypeEvaluate->deleteDefault($id);
        }
        TypeModel::resetcache();
        $this->success("删除成功！");
    }
}