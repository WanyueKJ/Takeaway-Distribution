<?php

namespace app\admin\controller\merchant;

use app\admin\model\merchant\StoreOrderEvaluate;
use app\admin\model\merchant\StoreProductReply;
use cmf\controller\AdminBaseController;

/**
 * 评价管理
 */
class OrderEvaluateController extends AdminBaseController
{
    public function index()
    {
        $param = $this->request->param();
        $id = $this->request->param('id');
        $showImg = $param['show_img'] ?? 0;
        $showVideo = $param['show_video'] ?? 0;

        /** @var StoreOrderEvaluate $StoreOrderEvaluate */
        $StoreOrderEvaluate = app()->make(StoreOrderEvaluate::class);
        $where = [];

        $list = $StoreOrderEvaluate->with(['storeOrder', 'userinfo', 'store'])->where($where)->paginate(20);

        $list->each(function ($value) {
            $value['o_is_show'] = $value->getData('is_show');
            $value['o_top_type_id'] = $value->store->getData('top_type_id') ?? '';
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
}