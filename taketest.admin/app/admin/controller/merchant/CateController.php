<?php

namespace app\admin\controller\merchant;

use app\admin\model\merchant\StoreProductAttr;
use app\admin\model\merchant\StoreType;
use cmf\controller\AdminBaseController;
use app\admin\model\merchant\StoreCircle;
use app\admin\model\merchant\ViewStoreOrderProductAttr;
use app\admin\model\merchant\StoreModel;
use app\admin\model\merchant\StoreProductReply;
use app\admin\model\merchant\StoreProduct;
use app\admin\model\merchant\StoreOrderCartInfo;
use app\admin\model\merchant\StoreOrderEvaluate;
use app\admin\model\merchant\Evaluate;
use app\admin\model\merchant\TypeModel;
use app\admin\model\merchant\Order;
use app\admin\model\merchant\StoreIndustry;
use app\models\EvaluategentModel;
use think\Db;

use function App\get_tree_children;

/**
 * 美食店铺
 */
class CateController extends AdminBaseController
{
    public function index()
    {
        $data = $this->request->param();

        $where = [
            ['top_type_id', '=', 1]
        ];
        if (isset($data['circle_id']) && ($data['circle_id'] > 0)) {
            $where[] = ['circle_id', '=', $data['circle_id']];
        }
        if (isset($data['type_id']) && ($data['type_id'] > 0)) {
            $where[] = ['type_id', '=', $data['type_id']];
        }
        if (isset($data['keywords'])) {
            $where[] = ['name', 'LIKE', "%" . $data['keywords'] . '%'];
        }

        $list = StoreModel::with(['account', 'storeCircle'])
            ->order('list_order ASC,id DESC')
            ->where($where)
            ->paginate(20);

        /** @var TypeModel $TypeModel */
        $TypeModel = app()->make(TypeModel::class);
        $type = $TypeModel->find(1)->toArray();
        $typeList = $TypeModel->getTree(1);
        array_unshift($typeList, $type);

        /** @var StoreCircle $StoreCircle */
        $StoreCircle = app()->make(StoreCircle::class);


        /** @var ViewStoreOrderProductAttr $ViewStoreOrderProductAttr */
        $ViewStoreOrderProductAttr = app()->make(ViewStoreOrderProductAttr::class);

        /** @var Evaluate $Evaluate */
        $Evaluate = app()->make(Evaluate::class);

        /** @var StoreOrderEvaluate $StoreOrderEvaluate */
        $StoreOrderEvaluate = app()->make(StoreOrderEvaluate::class);

        $list->each(function ($value) use ($ViewStoreOrderProductAttr, $Evaluate, $StoreOrderEvaluate,$TypeModel) {
            $value['or_top_type_id'] = $value->getData('top_type_id');
            $value['circle_name'] = $value->storeCircle['name'] ?? '--';
            $value['product_reply_count'] = StoreProductReply::where('store_id', $value['id'])->count();

            $value['product_count'] = StoreProduct::where('store_id', $value['id'])->where('is_del', 0)->count();
            $value['putaway'] = StoreProduct::where('store_id', $value['id'])->where('is_del', 0)->where('is_show', 1)->count();
            $value['all_sales'] = $ViewStoreOrderProductAttr->getSales([
                ['store_id', '=', $value['id']],
                ['status', '=', 4]
            ]);
            $value['monthly_sales'] = $ViewStoreOrderProductAttr->getMonthlySales([
                ['store_id', '=', $value['id']],
                ['status', '=', 4]
            ]);
            //配送平均分
            $distributionStar = $Evaluate->getAverage([
                ['store_id', '=', $value['id']],
            ]);
            $value['distribution_star'] = sprintf("%01.1f", $distributionStar['average']);

            //订单评价
            $orderEvaluate = $StoreOrderEvaluate->getAverage([
                ['store_id', '=', $value['id']]
            ]);
            $value['taste_star'] = sprintf("%01.1f", $orderEvaluate['taste_star']);
            $value['packaging_star'] = sprintf("%01.1f", $orderEvaluate['packaging_star']);

            $tree_list = $TypeModel->getTopTreeList($value['type_id']);
            $value['tree_name'] = implode('->', array_column($tree_list, 'name'));

        });
        $this->assign([
            'type_list' => $typeList,
            'list' => $list,
            'page' => $list->render()
        ]);

        return $this->fetch();
    }

    /**
     * 当前订单|过往订单
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function read()
    {
        $param = $this->request->param();
        $id = $this->request->param('id');
        //类型:
        $type = $this->request->param('type', 'now');
        $this->readPageHeader();

        /** @var Order $StoreOrder */
        $StoreOrder = app()->make(Order::class);
        $orderWhere[] = ['store_id', '=', $id];
        if ($type == 'now') {
            $orderWhere[] = ['status', '<>', 4];
        } else {
            $orderWhere[] = ['status', '=', 4];
        }
        if (isset($param['start_time']) && $param['start_time'] != '') {
            $orderWhere[] = ['add_time', '>=', strtotime($param['start_time'])];
        }
        if (isset($param['end_time']) && $param['end_time'] != '') {
            $orderWhere[] = ['add_time', '<=', strtotime($param['end_time'])];
        }
        if (isset($param['keywords']) && ($param['keywords'] > 0)) {
            $orderWhere[] = ['uid', 'LIKE', "%" . ($param['keywords']) . '%'];
        }

        $orderList = $StoreOrder
            ->with(['userinfo','evaluate'])
            ->field('id,order_id,status,uid,total_num,pay_type,pay_price,total_price,coupon_price,freight_price,add_time,end_time,free_shipping')
            ->where($orderWhere)
            ->paginate(20);

        $orderCount = $StoreOrder->where([
            ['store_id', '=', $id]
        ])->count();
        $orderPrice = $StoreOrder->field('sum(pay_price) as pay_price')->where([
            ['store_id', '=', $id],
            ['status', '=', 4]
        ])->find()['pay_price'] ?? 0;
        $this->assign([
            'type' => $type,
            'order_list' => $orderList,
            'page' => $orderList->render(),
            'orderCount' => $orderCount,
            'orderPrice' => $orderPrice,
        ]);
        return $this->fetch();
    }

    /**
     * 页面公共头部数据
     * @return void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    protected function readPageHeader()
    {
        $id = $this->request->param('id');

        $where[] = ['id', '=', $id];
        $data = StoreModel::with(['storeCircle', 'storeType'])
            ->field('id,name,thumb,type_id,top_type_id,remark,operating_state,open_date,open_time,circle_id,address')
            ->where($where)
            ->find();
        if (!$data) $this->error('店铺不存在');
        $data['o_top_type_id'] = $data->getData('top_type_id');
        /** @var Evaluate $Evaluate */
        $Evaluate = app()->make(Evaluate::class);

        /** @var StoreOrderEvaluate $StoreOrderEvaluate */
        $StoreOrderEvaluate = app()->make(StoreOrderEvaluate::class);
        //配送平均分
        $distributionStar = $Evaluate->getAverage([
            ['store_id', '=', $data['id']]
        ]);
        $distributionStar = sprintf("%01.1f", $distributionStar['average']);

        //订单评价
        $orderEvaluate = $StoreOrderEvaluate->getAverage([
            ['store_id', '=', $data['id']]
        ]);

        $tasteStar = sprintf("%01.1f", $orderEvaluate['taste_star']);
        $packagingStar = sprintf("%01.1f", $orderEvaluate['packaging_star']);
        $overallStar = sprintf("%01.1f", $orderEvaluate['overall_star']);

        $openDate = json_decode($data['open_date'], true) ?? [];
        $openTime = json_decode($data['open_time'], true) ?? [];
        $businessHours = '--';
        if ($openDate && $openTime) {
            $businessHours = '每周' . implode(',', $openDate) . ' 的 ' . implode('~', $openTime) . '点';
        }


        $this->assign([
            'data' => $data,
            'distribution_star' => $distributionStar,
            'taste_star' => $tasteStar,
            'packaging_star' => $packagingStar,
            'overall_star' => $overallStar,
            'businessHours' => $businessHours,
        ]);
    }

    public function evaluateIndex()
    {
        $param = $this->request->param();
        $id = $this->request->param('id');
        $showImg = $param['show_img'] ?? 0;
        $showVideo = $param['show_video'] ?? 0;

        $this->readPageHeader();
        /** @var StoreOrderEvaluate $StoreOrderEvaluate */
        $StoreOrderEvaluate = app()->make(StoreOrderEvaluate::class);
        /** @var StoreProductReply $StoreProductReply */
        $StoreProductReply = app()->make(StoreProductReply::class);
        $where = [
            ['store_id', '=', $id]
        ];
        if (isset($param['show_img'])) {
            if ($param['show_img'] == 1) $where[] = ['pics', '<>', '[]'];
            if ($param['show_img'] == 2) $where[] = ['pics', '=', '[]'];
        }
        if (isset($param['show_video'])) {
            if ($param['show_video'] == 1) $where[] = ['video', '<>', '[]'];
            if ($param['show_video'] == 2) $where[] = ['video', '=', '[]'];
        }
        if (isset($param['uid']) && $param['uid'] !== '') {
            $where[] = ['uid', 'LIKE', $param['uid']];
        }
        $list = $StoreOrderEvaluate->with(['evaluate', 'storeOrder', 'userinfo'])->where($where)->paginate(20);

        $list->each(function ($value) use ($StoreProductReply) {
            $value['tag_praise'] = $StoreProductReply->where([
                ['store_id', '=', $value['store_id']],
                ['tags', '=', 1]
            ])->count();
            $value['tag_tread'] = $StoreProductReply->where([
                ['store_id', '=', $value['store_id']],
                ['tags', '=', -1]
            ])->count();
            $value['o_is_show'] = $value->getData('is_show');
            $value['evaluate']=EvaluategentModel::field('star')->where('store_oid',$value['oid'])->find();
        });

        $allCount = $StoreOrderEvaluate->where([
            ['store_id', '=', $id]
        ])->count();
        $hasImg = $StoreOrderEvaluate->where([
            ['store_id', '=', $id],
            ['pics|video', '<>', '[]'],
        ])->count();
        $good = $StoreOrderEvaluate->where([
            ['store_id', '=', $id],
            ['overall_star', '=', 5]
        ])->count();
        $bad = $StoreOrderEvaluate->where([
            ['store_id', '=', $id],
            ['overall_star', '=', 1]
        ])->count();

        $this->assign([
            'list' => $list,
            'page' => $list->render(),
            'all_count' => $allCount,
            'has_img' => $hasImg,
            'good' => $good,
            'bad' => $bad,
            'show_img' => $showImg,
            'show_video' => $showVideo,
        ]);
        return $this->fetch();
    }

    /**
     * 店铺资质
     * @return void
     */
    public function readIndustry()
    {
        $id = $this->request->param('id');

        /** @var StoreIndustry $StoreIndustry */
        $StoreIndustry = app()->make(StoreIndustry::class);

        $data = $StoreIndustry->where('store_id', $id)->find();
        $this->assign([
            'industry' => $data
        ]);
        return $this->fetch();
    }

    /**
     * 店铺资质修改
     * @return void
     */
    public function saveIndustry()
    {
        $param = $this->request->param();
        $id = $param['id'] ?? 0;

        /** @var StoreIndustry $StoreIndustry */
        $StoreIndustry = app()->make(StoreIndustry::class);
        $industryIsExist = $StoreIndustry->where('store_id', $id)->find();

        $industryData = [
            'name' => $param['person_name'],
            'id_card' => $param['id_card'],
            'id_card_image' => json_encode([$param['id_card_image_0'], $param['id_card_image_1']]),
            'registr_id' => $param['registr_id'],
            'business_image' => json_encode([$param['business_image_0']]),
            'license_number' => $param['license_number'],
            'license_image' => json_encode([$param['license_image_0']]),
        ];
        if ($industryIsExist) {
            $StoreIndustry->where('store_id', $id)->update($industryData);
        } else {
            $industryData['store_id'] = $id;
            $StoreIndustry->insert($industryData);
        }
        $this->success('操作成功');
    }


    /**
     * 赞踩商品详情
     * @return void
     */
    public function productReply()
    {
        $id = $this->request->param('store_oid');
        $store_id = $this->request->param('store_id');
        if (!$id) $this->error('参数不正确');

        /** @var StoreModel StoreModel */
        $StoreModel = app()->make(StoreModel::class);
        $store = $StoreModel->find($store_id);
        if (!$store) $this->error('店铺不存在');
        $store['o_top_type_id'] = $store->getData('top_type_id');
        /** @var StoreProductReply $StoreProductReply */
        $StoreProductReply = app()->make(StoreProductReply::class);
        $list = $StoreProductReply
            ->with(['product','storeOrder'])
            ->where([
                ['oid', '=', $id]
            ])->select();
        $this->assign([
            'store' => $store,
            'list' => $list
        ]);
        return $this->fetch();
    }

    /**
     * 赞踩商品详情
     * @return void
     */
    public function productReplyDetail()
    {
        $id = $this->request->param('id');
        if (!$id) $this->error('参数不正确');

        $StoreProductReply = app()->make(StoreProductReply::class);
        $list = $StoreProductReply
            ->with(['product','storeOrder'])
            ->where([
                ['product_id', '=', $id]
            ])->select();
        $this->assign([
            'list' => $list
        ]);
        return $this->fetch();
    }

    /**
     * 赞踩商品详情
     * @return void
     */
    public function productAttr()
    {
        $id = $this->request->param('id');
        if (!$id) $this->error('参数不正确');

        /** @var StoreProduct $StoreProduct */
        $StoreProduct = app()->make(StoreProduct::class);
        $product = $StoreProduct->find($id);
        if (!$product) $this->error('商品不存在');
        /** @var StoreProductAttr $StoreProductAttr */
        $StoreProductAttr = app()->make(StoreProductAttr::class);
        $list = $StoreProductAttr
            ->where([
                ['product_id', '=', $id]
            ])->select();
        $treeList = get_tree_childrens($list->toArray());
        $this->assign([
            'list' => $treeList
        ]);
        return $this->fetch();
    }

    /**
     * 商品详情
     * @return void
     */
    public function readProduct()
    {
        $id = $this->request->param('id');
        if (!$id) $this->error('参数不正确');

        /** @var StoreOrderCartInfo $StoreOrderCartInfo */
        $StoreOrderCartInfo = app()->make(StoreOrderCartInfo::class);
        $list = $StoreOrderCartInfo
            ->where([
                ['oid', '=', $id]
            ])->select();
        $list->each(function ($value) {
            $value['cart_info'] = json_decode($value['cart_info'], true) ?? [];
            $value['product_image'] = json_decode($value['cart_info']['product']['image'] ?? '[]', true) ?? [];
            $value['product'] = $value['cart_info']['product'] ?? [];
            $value['product_attr'] = $value['cart_info']['product_attr']['attr'] ?? [] ? $value['cart_info']['product_attr'] : $value['cart_info']['more_product_attr'];
            unset($value['cart_info']);
        });

        $this->assign([
            'list' => $list
        ]);
        return $this->fetch();
    }

    /**
     * 订单评价删除
     * @return void
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function delete()
    {
        $oid = $this->request->param('oid');
        if (!$oid) $this->error('参数不正确');

        /** @var StoreOrderEvaluate $StoreOrderEvaluate */
        $StoreOrderEvaluate = app()->make(StoreOrderEvaluate::class);

        /** @var StoreProductReply $StoreProductReply */
        $StoreProductReply = app()->make(StoreProductReply::class);

        /** @var Evaluate $Evaluate */
        $Evaluate = app()->make(Evaluate::class);

        $StoreOrderEvaluate->where([
            ['oid', '=', $oid]
        ])->delete();

        $StoreProductReply->where([
            ['oid', '=', $oid]
        ])->delete();

        $Evaluate->where([
            ['store_oid', '=', $oid]
        ])->delete();
        $this->success('操作成功');
    }

    /**
     * 订单评论展示/隐藏
     * @return void
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function setShow()
    {
        $param = $this->request->param();
        $oid = $param['oid'] ?? 0;
        $is_show = $param['is_show'] ?? 0;
        if (!$oid) $this->error('参数不正确');
        if (!in_array($is_show, [0, 1])) $this->error('参数不正确');


        /** @var StoreOrderEvaluate $StoreOrderEvaluate */
        $StoreOrderEvaluate = app()->make(StoreOrderEvaluate::class);
        /** @var StoreProductReply $StoreProductReply */
        $StoreProductReply = app()->make(StoreProductReply::class);

        $Evaluate = $StoreOrderEvaluate->where('oid',$oid)->find();
        if (!$Evaluate) $this->error('信息不存在');

        $StoreOrderEvaluate->where([
            ['oid', '=', $oid]
        ])->update([
            'is_show' => $is_show
        ]);
        $StoreProductReply->where([
            ['oid', '=', $oid]
        ])->update([
            'is_show' => $is_show
        ]);

        /** @var StoreModel $StoreModel */
        $StoreModel = app()->make(StoreModel::class);
        $StoreModel->updateRemark($Evaluate['store_id']);
        $this->success('操作成功');

    }

    public function product()
    {
        $param = $this->request->param();

        $id = $param['id'] ?? 0;
        $store_type_id = $param['store_type_id'] ?? 0;
        $show = $param['show'] ?? 0;
        if (!$id) $this->error('参数不正确');

        /** @var StoreProduct $StoreProduct */
        $StoreProduct = app()->make(StoreProduct::class);
        /** @var ViewStoreOrderProductAttr $ViewStoreOrderProductAttr */
        $ViewStoreOrderProductAttr = app()->make(ViewStoreOrderProductAttr::class);
        /** @var StoreProductReply $StoreProductReply */
        $StoreProductReply = app()->make(StoreProductReply::class);
        /** @var StoreType $StoreType */
        $StoreType = app()->make(StoreType::class);
        $where = [];

        if (isset($param['store_type_id']) && ($param['store_type_id'] > 0)) {
            $where[] = ['store_type_id', '=', $param['store_type_id']];
        }
        if (isset($param['product_id']) && ($param['product_id'] > 0)) {
            $where[] = ['id', 'LIKE', "%" . $param['product_id'] . '%'];
        }
        if (isset($param['price']) && ($param['price'] > 0)) {
            $where[] = ['price', '<=', $param['price']];
        }
        if (isset($param['show']) && $param['show'] > 1) {
            $where[] = ['is_show', '=', $param['show']];
        }
        $where[] = ['store_id', '=', $id];
        $list = $StoreProduct
            ->with(['typeInfo', 'storeTypeInfo'])
            ->where($where)
            ->where('is_del', '=', 0)
            ->paginate(20);
        $this->readPageHeader();

        $list->each(function ($value) use ($ViewStoreOrderProductAttr, $StoreProductReply) {
            $value['monthly_sales'] = $ViewStoreOrderProductAttr->getMonthlySales([
                ['product_id', '=', $value['id']],
                ['status', '=', 4]
            ]);

            $value['all_sales'] = $ViewStoreOrderProductAttr->getSales([
                ['product_id', '=', $value['id']],
                ['status', '=', 4]
            ]);
            $value['tag_praise'] = $StoreProductReply->where([
                ['store_id', '=', $value['store_id']],
                ['tags', '=', 1]
            ])->count();
            $value['tag_tread'] = $StoreProductReply->where([
                ['store_id', '=', $value['store_id']],
                ['tags', '=', -1]
            ])->count();
            $value['o_is_show'] = $value->getData('is_show');
        });
        $this->assign([
            'store_type_list' => $StoreType->field('id,name')->where('store_id',$id)->select(),
            'list' => $list,
            'page' => $list->render(),
            'store_type_id' => $store_type_id,
            'show' => $show,
        ]);
        return $this->fetch();
    }

    /**
     * 商品上下架
     * @return void
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function productSetShow()
    {
        $param = $this->request->param();
        $id = $param['id'] ?? 0;
        $is_show = $param['is_show'] ?? 0;
        if (!$id) $this->error('参数不正确');
        if (!in_array($is_show, [0, 1])) $this->error('参数不正确');

        /** @var StoreProduct $StoreProduct */
        $StoreProduct = app()->make(StoreProduct::class);

        $StoreProduct->where([
            ['id', '=', $id]
        ])->update([
            'is_show' => $is_show
        ]);
        $this->success('操作成功');
    }


    public function storeType()
    {
        $param = $this->request->param();
        $id = $param['id'] ?? 0;
        if (!$id) $this->error('参数不正确');

        /** @var StoreType $StoreType */
        $StoreType = new StoreType();
        $this->readPageHeader();

        $where = [];
        $where[] = ['store_id', '=', $id];
        $list = $StoreType
            ->where($where)
            ->paginate(20);

        $this->assign([
            'list' => $list,
            'page' => $list->render(),
        ]);
        return $this->fetch();
    }

    public function storeTypeDelete()
    {
        $id = $param['id'] ?? 0;
        if (!$id) $this->error('参数不正确');
        /** @var StoreProduct $StoreProduct */
        $StoreProduct = app()->make(StoreProduct::class);
        $isExist = $StoreProduct->where('store_type_id', '=', $id)->find();
        if ($isExist) $this->error('请先删除此分类下的商品');

        /** @var StoreType $StoreType */
        $StoreType = new StoreType();
        $StoreType->where('id', $id)->delete();
        $this->success('操作成功');

    }


    public function replyVideo(){
        $param = $this->request->param();
        $id = $param['id'] ?? 0;
        if (!$id) $this->error('参数不正确');
        /** @var StoreOrderEvaluate $StoreOrderEvaluate */
        $StoreOrderEvaluate = app()->make(StoreOrderEvaluate::class);
        $data = $StoreOrderEvaluate->find($id);
        if (!$data) $this->error('信息不存在');
        $this->assign([
            'data' => $data,
        ]);
        return $this->fetch();
    }
}
