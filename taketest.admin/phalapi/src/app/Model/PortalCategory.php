<?php

namespace App\Model;

/**
 * 文章分类
 */
class PortalCategory
{
    public function selectList(array $where, $field = '*', $order = 'id ASC', $p = 0, $limit = 0)
    {
        if ($p < 1) {
            $p = 1;
        }

        $start = ($p - 1) * $limit;
        $list = \PhalApi\DI()->notorm->portal_category
            ->select($field)
            ->order($order)
            ->where($where);
        if ($limit > 0) {
            $list->limit($start, $limit);
        }
        return $list->fetchAll();
    }

    /**
     * 分类下文章列表
     * @return array
     */
    public function selectPostList($categoryId, array $where, $field = '*', $order = 'id ASC', $p = 0, $limit = 0)
    {
        if ($p < 1) {
            $p = 1;
        }

        $start = ($p - 1) * $limit;
        $list = \PhalApi\DI()->notorm->portal_category_post
            ->alias('c') //
            ->select($field) // 获取字段
            ->leftJoin('portal_post', 'p', 'c.post_id=p.id')
            ->where($where)
            ->where('c.status', 1)
            ->where('c.category_id', $categoryId)
            ->where('p.post_status', 1)
            ->order($order);
        if ($limit > 0) {
            $list->limit($start, $limit);
        }
        return $list->fetchAll();
    }
}