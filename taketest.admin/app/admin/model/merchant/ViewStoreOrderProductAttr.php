<?php

namespace app\admin\model\merchant;

use think\Model;

/**
 * 视图
 */
class ViewStoreOrderProductAttr extends Model
{
    protected $pk = 'id';
    protected $name = 'view_store_order_product_attr';

    public static $redis_key = 'view_store_order_product_attr';

    /**
     * 月销量
     * @param $where
     * @return int
     */
    public function getMonthlySales($where)
    {
        return $this
            ->field('sum(total_num) as total_num')
            ->where($where)
            ->where('add_time', '>=', strtotime('-30day'))
            ->find()['total_num'] ?? 0;
    }

    /**
     * 月销量
     * @param $where
     * @return int
     */
    public function getSales($where)
    {
        return $this
            ->field('sum(total_num) as total_num')
            ->where($where)
            ->find()['total_num'] ?? 0;
    }
}