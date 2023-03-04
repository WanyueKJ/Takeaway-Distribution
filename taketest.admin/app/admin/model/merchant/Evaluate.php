<?php

namespace app\admin\model\merchant;

use think\Model;

/**
 * 骑手评价
 */
class Evaluate extends Model
{
    protected $pk = 'id';
    protected $name = 'evaluate';

    public static $redis_key = 'evaluate';

    /**
     * 获取平均分
     * @param $where
     * @return float|string
     */
    public function getAverage($where, $field = "(sum(star)/count(id)) as average")
    {
        $info = $this
            ->field($field)
            ->where($where)
            ->find();
        return $info;
    }
}