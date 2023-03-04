<?php

namespace app\admin\model\merchant;

use app\models\OrdersModel;
use phpDocumentor\Reflection\Types\Void_;
use think\Model;


/**
 * 找店类型自定义的评价选项
 */
class TypeEvaluate extends Model
{
    protected $pk = 'id';
    protected $name = 'merchant_type_evaluate';

    public static $redis_key = 'merchant_type_evaluate';

    /**
     * 获取店铺对应的评价信息
     * @param $stroeId
     * @return array
     */
    public function getTypeEvaluate($stroeId)
    {
        $storeInfo = StoreModel::where('id', $stroeId)->field('type_id')->find();
        if (!$storeInfo) return [];
        $evaluate = $this->where('type_id', $storeInfo['type_id'])->select()->toArray();

        return $evaluate ?: [];
    }

    /**
     * 新增分类默认评分
     * @param $typeId
     * @return void
     */
    public function addDefault($typeId)
    {

        $isExist = $this->where('type_id', $typeId)->find();
        if ($isExist) return;

        $data = [
            [
                'name' => "总体",
                'type_id' => $typeId
            ],
            [
                'name' => "口味",
                'type_id' => $typeId
            ],
            [
                'name' => "服务",
                'type_id' => $typeId
            ],
        ];
        $this->saveAll($data);
    }

    /**
     * 删除分类默认评分
     * @param $typeId
     * @return void
     */
    public function deleteDefault($typeId)
    {

        $this->where('type_id', $typeId)->delete();
    }
}