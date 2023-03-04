<?php

declare(strict_types=1);

namespace Imi\Server\View\Annotation;

/**
 * HTML 视图配置注解.
 *
 * @Annotation
 * @Target({"CLASS", "METHOD"})
 *
 * @property string|null $baseDir  模版基础路径；abc-配置中设定的路径/abc/；/abc/-绝对路径
 * @property string|null $template 模版路径
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
class HtmlView extends BaseViewOption
{
    /**
     * {@inheritDoc}
     */
    protected ?string $defaultFieldName = 'template';

    public function __construct(?array $__data = null, ?string $baseDir = null, ?string $template = null)
    {
        parent::__construct(...\func_get_args());
    }
}
