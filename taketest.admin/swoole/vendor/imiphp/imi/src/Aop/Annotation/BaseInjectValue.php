<?php

declare(strict_types=1);

namespace Imi\Aop\Annotation;

use Imi\Bean\Annotation\Base;
use Imi\Bean\Annotation\Parser;

/**
 * 注入值注解基类.
 *
 * @Parser("\Imi\Bean\Parser\BeanParser")
 */
abstract class BaseInjectValue extends Base
{
    /**
     * 获取注入值的真实值
     *
     * @return mixed
     */
    abstract public function getRealValue();
}
