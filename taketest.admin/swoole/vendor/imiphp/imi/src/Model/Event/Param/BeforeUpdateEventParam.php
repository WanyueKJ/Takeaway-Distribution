<?php

declare(strict_types=1);

namespace Imi\Model\Event\Param;

use Imi\Event\EventParam;

class BeforeUpdateEventParam extends EventParam
{
    /**
     * 模型.
     *
     * @var \Imi\Model\BaseModel
     */
    public $model;

    /**
     * 初始化数据.
     *
     * @var \Imi\Util\LazyArrayObject
     */
    public $data;

    /**
     * 查询器.
     *
     * @var \Imi\Db\Query\Interfaces\IQuery
     */
    public $query;
}
