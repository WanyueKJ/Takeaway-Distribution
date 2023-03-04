<?php

namespace app\admin\controller\merchant;

use app\admin\model\merchant\StoreModel;
use app\admin\model\merchant\StoreType;
use cmf\controller\AdminBaseController;
use app\admin\model\merchant\StoreProduct;
use app\admin\model\merchant\TypeModel;

class ProductController extends AdminBaseController
{
    public function index()
    {
        $param = $this->request->param();

        $where = [];

        if (isset($param['type_id']) && $param['type_id'] > 0) {
            $where[] = ['type_id', '=', $param['type_id']];
        }
        if (isset($param['store_type_id']) && $param['store_type_id'] > 0) {
            $where[] = ['store_type_id', '=', $param['store_type_id']];
        }
        if (isset($param['keywords']) && $param['keywords'] > 0) {
            $where[] = ['name|id', 'LIKE', "%" . $param['keywords'] . '%'];
        }
        if (isset($param['store_id']) && $param['store_id'] > 0) {
            $where[] = ['store_id', '=', $param['store_id']];
        }

        /** @var StoreProduct $StoreProduct */
        $StoreProduct = app()->make(StoreProduct::class);
        $list = $StoreProduct
            ->with(['storeTypeInfo', 'TypeInfo', 'store'])
            ->field('id,name,th_name,image,price,is_show,store_id,store_type_id,type_id,list_order,sales,is_show,recommend')
            ->where('is_del', 0)
            ->where($where)
            ->order('id', "DESC")
            ->paginate(20);

        /** @var TypeModel $TypeModel */
        $TypeModel = app()->make(TypeModel::class);

        /** @var StoreType $StoreType */
        $StoreType = app()->make(StoreType::class);
        $list->each(function ($value, $key) use ($TypeModel, $StoreType) {
            $value['is_show_o'] = $value->getData('is_show');
            $value['recommend_o'] = $value->getData('recommend');

            $tree_list = $TypeModel->getTopTreeList($value['type_id']);
            $value['tree_name'] = implode('->', array_column($tree_list, 'name'));

            $Store_tree_list = $StoreType->getTopTreeList($value['store_type_id']);
            $value['store_tree_name'] = implode('->', array_column($Store_tree_list, 'name'));

        });
        $typeList = $TypeModel->where('top_id', 'NOT IN', [2])->select();

        /** @var StoreType $StoreType */
        $StoreType = app()->make(StoreType::class);
        $storeTypeList = $StoreType->select();

        /** @var StoreModel $StoreModel */
        $StoreModel = app()->make(StoreModel::class);
        $storeList = $StoreModel->select();

        $this->assign([
            'list' => $list,
            'type_list' => sort_list_tier($typeList),
            'storeList' => ($storeList),
            'store_type_list' => sort_list_tier($storeTypeList),
            'page' => $list->render()
        ]);
        return $this->fetch();
    }
}