<?php

namespace app\admin\controller\merchant;

use app\admin\model\merchant\StoreModel;
use cmf\controller\AdminBaseController;
use app\admin\model\merchant\StoreProduct;
use app\admin\model\merchant\StoreProductAttr;

/**
 * 店铺商品
 */
class StoreProductController extends AdminBaseController
{
    public function index()
    {

        $storeId = $this->request->param('id', 0, 'intval');
        if (!$storeId) $this->error('参数错误');
        $StoreProduct = app()->make(StoreProduct::class);
        $list = $StoreProduct
            ->field('id,name,th_name,image,price,is_show,list_order,sales,is_show,recommend')
            ->where('store_id', $storeId)
            ->where('is_del', 0)
            ->order('id', "DESC")
            ->paginate(20);

        $list->each(function ($value, $key) {
            $value['is_show_o'] = $value->getData('is_show');
            $value['recommend_o'] = $value->getData('recommend');
        });

        $this->assign([
            'list' => $list,
            'page' => $list->render()
        ]);
        return $this->fetch();
    }

    public function listOrder()
    {
        $model = app()->make(StoreProduct::class);
        parent::listOrders($model);
        $this->success("排序更新成功！");
    }

    public function isshow()
    {
        $id = $this->request->param('id', 0, 'intval');
        $is_show = $this->request->param('is_show', 0, 'intval');
        if (!in_array($is_show, [0, 1])) {
            $this->error('参数错误');
        }

        $StoreProduct = app()->make(StoreProduct::class);
        $update = [
            'is_show' => $is_show
        ];
        $StoreProduct->where('id', $id)->update($update);
        $this->success("操作成功！");
    }

    public function recommend()
    {
        $id = $this->request->param('id', 0, 'intval');
        $recommend = $this->request->param('recommend', 0, 'intval');
        if (!in_array($recommend, [0, 1])) {
            $this->error('参数错误');
        }

        $StoreProduct = app()->make(StoreProduct::class);
        $update = [
            'recommend' => $recommend
        ];
        $StoreProduct->where('id', $id)->update($update);
        $this->success("操作成功！");
    }

    public function delete()
    {
        $id = $this->request->param('id', 0, 'intval');

        /** @var StoreProduct $StoreProduct */
        $StoreProduct = app()->make(StoreProduct::class);
        /** @var StoreModel $StoreModel */
        $StoreModel = app()->make(StoreModel::class);
        $update = [
            'is_del' => 1
        ];
        $product = $StoreProduct->where('id', $id)->find();
        if (!$product) {
            $this->error('商品不存在');
        }
        $StoreProduct->where('id', $id)->update($update);

        $StoreProductAttr = app()->make(StoreProductAttr::class);
        $StoreProductAttr->where('product_id', $id)->update($update);
        $StoreModel->updatePutaway($product['store_id']);

        $this->success("操作成功！");
    }

}