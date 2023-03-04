<?php

namespace App\Model;

use App\Model\MerchantTypeEvaluate as MerchantTypeEvaluateModel;
use PhalApi\Model\NotORMModel as NotORM;

/**
 * 找店评级类型选项
 */
class MerchantTypeEvaluate extends NotORM
{
    public function selectList(array $where, $field = '*', $order = 'id ASC', $p = 0, $limit = 0)
    {
        if ($p < 1) {
            $p = 1;
        }

        $start = ($p - 1) * $limit;
        $list = \PhalApi\DI()->notorm->merchant_type_evaluate
            ->select($field)
            ->order($order)
            ->where($where);
        if ($limit > 0) {
            $list->limit($start, $limit);
        }
        $list = $list->fetchAll();
        foreach ($list as $key => &$value) {
            $value['use_name'] = '';

            if (LANG == 'zh-cn') {
                $value['use_name'] = $value['name'] ?? '';
            } else if (LANG == 'th') {
                $value['use_name'] = $value['th_name'] ?? '';
            }
        }
        return $list;
    }

}