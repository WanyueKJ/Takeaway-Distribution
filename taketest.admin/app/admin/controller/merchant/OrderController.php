<?php

namespace app\admin\controller\merchant;

use cmf\controller\AdminBaseController;
use app\admin\model\merchant\Order;
use think\Db;

/**
 * 店铺订单
 */
class OrderController extends AdminBaseController
{
    public function index()
    {
        $data = $this->request->param();
        $map=[];
        $start_time=isset($data['start_time']) ? $data['start_time']: '';
        $end_time=isset($data['end_time']) ? $data['end_time']: '';

        if($start_time!=""){
            $map[]=['add_time','>=',strtotime($start_time)];
        }

        if($end_time!=""){
            $map[]=['add_time','<=',strtotime($end_time) + 60*60*24];
        }

        $type=isset($data['type']) ? $data['type']: '';
        if($type!=''){
            $map[]=['shipping_type','=',$type];
        }

        $paytype=isset($data['paytype']) ? $data['paytype']: '';
  
        if($paytype!=''){
            $map[]=['pay_type','=',$paytype];
        }

        $uid=isset($data['uid']) ? $data['uid']: '';
        if($uid!=''){
            $map[]=['uid','=',$uid];
        }

        $keyword=isset($data['keyword']) ? $data['keyword']: '';
        if($keyword!=''){
            $map[]=['order_id|trade_no','=',$keyword];
        }
        $list = Order::with(['orders','userinfo'])
            ->order('id DESC')
            ->where($map)
            ->paginate(20);
        $list->each(function ($value) {
            $value['delivery'] = $value['freight_price'] . ($value['free_shipping'] == 1 ? '(免运费)' : '');
            $storeInfo = Db::name('merchant_store')->field('id,name,top_type_id')->where([
                ["id", "=", $value['store_id']],
            ])->find();
            $value['store_name'] = $storeInfo['name'] ?? "";

            $typeInfo = Db::name('merchant_type')->where([
                ["id", "=", $storeInfo['top_type_id'] ?? 0],
            ])->find();
            $value['top_type_name'] = $typeInfo['name'] ?? "";

        });
      
        $this->assign('type', Order::getTypes());
        $this->assign('paytype', Order::getPayTypes());
        $this->assign('status', Order::getStatus());
        $this->assign([
            'lists' => $list,
            'page' => $list->render()
        ]);
     
        return $this->fetch();
    }


    public function viewDetails(){

    }
}