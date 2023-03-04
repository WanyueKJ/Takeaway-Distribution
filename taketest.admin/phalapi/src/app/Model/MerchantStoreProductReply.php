<?php

namespace App\Model;

/**
 * 商品评价
 */
class MerchantStoreProductReply
{


    public function getOne($where,$field,$order= "id DESC"){
        $rs = \PhalApi\DI()->notorm->merchant_store_product_reply
            ->select($field)
            ->where($where)
            ->where('is_show',1)
            ->order($order)
            ->fetchOne();
        return $rs;
    }


    /**
     * 更新数据
     * @param $where
     * @param $field
     * @return mixed
     */
    public function updateOne($where, $update = [])
    {
        $rs = \PhalApi\DI()->notorm->merchant_store_product_reply
            ->where($where)
            ->update($update);
        return $rs;
    }


    public function getReplyLikeOne($where, $field = '*')
    {
        $rs = \PhalApi\DI()->notorm->merchant_store_product_reply_like
            ->select($field)
            ->where($where)
            ->fetchOne();
        return $rs;
    }

    public function saveReplyLikeOne($data)
    {
        $rs = \PhalApi\DI()->notorm->merchant_store_product_reply_like
            ->insert($data);
        return $rs;
    }

    public function deleteReplyLikeOne($where)
    {
        $rs = \PhalApi\DI()->notorm->merchant_store_product_reply_like
            ->where($where)
            ->delete();
        return $rs;
    }

    public function saveOne($data)
    {
        $rs = \PhalApi\DI()->notorm->merchant_store_product_reply
            ->insert($data);
        return $rs;
    }


    /**
     * 查询数量
     * @return array
     */
    public function getCount($where)
    {
        $rs = \PhalApi\DI()->notorm->merchant_store_product_reply
            ->where($where)
            ->where('is_show',1)
            ->count();
        return $rs;
    }


    public function selectList(array $where, $field = '*', $order = 'id ASC', $p = 0, $limit = 0)
    {
        if ($p < 1) {
            $p = 1;
        }

        $start = ($p - 1) * $limit;
        $list = \PhalApi\DI()->notorm->merchant_store_product_reply
            ->select($field)
            ->order($order)
            ->where('is_show',1)
            ->where($where);
        if ($limit > 0) {
            $list->limit($start, $limit);
        }

        $list = $list->fetchAll();
        return $list;
    }
}