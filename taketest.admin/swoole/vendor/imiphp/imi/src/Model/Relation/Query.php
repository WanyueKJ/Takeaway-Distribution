<?php

declare(strict_types=1);

namespace Imi\Model\Relation;

use Imi\Bean\Annotation\AnnotationManager;
use Imi\Bean\BeanFactory;
use Imi\Db\Query\Field;
use Imi\Event\Event;
use Imi\Model\Annotation\Relation\AutoSelect;
use Imi\Model\BaseModel;
use Imi\Model\Contract\IModelQuery;
use Imi\Model\Model;
use Imi\Model\Relation\Struct\ManyToMany;
use Imi\Model\Relation\Struct\OneToMany;
use Imi\Model\Relation\Struct\OneToOne;
use Imi\Model\Relation\Struct\PolymorphicManyToMany;
use Imi\Model\Relation\Struct\PolymorphicOneToMany;
use Imi\Model\Relation\Struct\PolymorphicOneToOne;
use Imi\Util\ArrayList;
use Imi\Util\Imi;

class Query
{
    private function __construct()
    {
    }

    /**
     * 初始化.
     *
     * @param \Imi\Bean\Annotation\Base[] $annotations
     * @param bool                        $forceInit   是否强制更新
     */
    public static function init(Model $model, string $propertyName, array $annotations, bool $forceInit = false, ?array &$refData = null): void
    {
        $className = BeanFactory::getObjectClass($model);

        if (!$forceInit)
        {
            /** @var AutoSelect|null $autoSelect */
            $autoSelect = AnnotationManager::getPropertyAnnotations($className, $propertyName, AutoSelect::class)[0] ?? null;
            if ($autoSelect && !$autoSelect->status)
            {
                return;
            }
        }

        $firstAnnotation = reset($annotations);

        // @phpstan-ignore-next-line
        if ($firstAnnotation instanceof \Imi\Model\Annotation\Relation\PolymorphicToOne)
        {
            // @phpstan-ignore-next-line
            static::initByPolymorphicToOne($model, $propertyName, $annotations, $refData);
        }
        // @phpstan-ignore-next-line
        elseif ($firstAnnotation instanceof \Imi\Model\Annotation\Relation\PolymorphicOneToOne)
        {
            // @phpstan-ignore-next-line
            static::initByPolymorphicOneToOne($model, $propertyName, $firstAnnotation, $refData);
        }
        // @phpstan-ignore-next-line
        elseif ($firstAnnotation instanceof \Imi\Model\Annotation\Relation\PolymorphicOneToMany)
        {
            // @phpstan-ignore-next-line
            static::initByPolymorphicOneToMany($model, $propertyName, $firstAnnotation, $refData);
        }
        // @phpstan-ignore-next-line
        elseif ($firstAnnotation instanceof \Imi\Model\Annotation\Relation\PolymorphicToMany)
        {
            // @phpstan-ignore-next-line
            static::initByPolymorphicToMany($model, $propertyName, $annotations, $refData);
        }
        // @phpstan-ignore-next-line
        elseif ($firstAnnotation instanceof \Imi\Model\Annotation\Relation\PolymorphicManyToMany)
        {
            // @phpstan-ignore-next-line
            static::initByPolymorphicManyToMany($model, $propertyName, $firstAnnotation, $refData);
        }
        elseif ($firstAnnotation instanceof \Imi\Model\Annotation\Relation\OneToOne)
        {
            static::initByOneToOne($model, $propertyName, $firstAnnotation, $refData);
        }
        elseif ($firstAnnotation instanceof \Imi\Model\Annotation\Relation\OneToMany)
        {
            static::initByOneToMany($model, $propertyName, $firstAnnotation, $refData);
        }
        elseif ($firstAnnotation instanceof \Imi\Model\Annotation\Relation\ManyToMany)
        {
            static::initByManyToMany($model, $propertyName, $firstAnnotation, $refData);
        }
    }

    /**
     * 初始化一对一关系.
     */
    public static function initByOneToOne(Model $model, string $propertyName, \Imi\Model\Annotation\Relation\OneToOne $annotation, ?array &$refData = null): void
    {
        $className = BeanFactory::getObjectClass($model);

        if (class_exists($annotation->model))
        {
            $modelClass = $annotation->model;
        }
        else
        {
            $modelClass = Imi::getClassNamespace($className) . '\\' . $annotation->model;
        }

        $struct = new OneToOne($className, $propertyName, $annotation);
        $leftField = $struct->getLeftField();
        $rightField = $struct->getRightField();
        $eventName = 'IMI.MODEL.RELATION.QUERY.' . $className . '.' . $propertyName;

        $leftValue = $model[$leftField];
        if (null === $leftValue)
        {
            $rightModel = $modelClass::newInstance();
        }
        elseif (null === $refData)
        {
            /** @var IModelQuery $query */
            $query = $modelClass::query()->where($rightField, '=', $leftValue);
            if ($annotation->fields)
            {
                $query->field(...$annotation->fields);
            }
            if ($annotation->withFields)
            {
                $query->withField(...$annotation->withFields);
            }
            Event::trigger($eventName . '.BEFORE', [
                'model'        => $model,
                'propertyName' => $propertyName,
                'annotation'   => $annotation,
                'struct'       => $struct,
                'query'        => $query,
            ]);
            $rightModel = $query->limit(1)->select()->get();
        }
        else
        {
            if (isset($refData[$propertyName]))
            {
                $item = &$refData[$propertyName];
                $item['ids'][] = $leftValue;
                $item['models'][$leftValue][] = $model;
            }
            else
            {
                $refData[$propertyName] = [
                    'annotation' => $annotation,
                    'modelClass' => $modelClass,
                    'leftField'  => $leftField,
                    'rightField' => $rightField,
                    'ids'        => [$leftValue],
                    'models'     => [
                        $leftValue => [$model],
                    ],
                ];
            }

            return;
        }

        $model[$propertyName] = $rightModel;
        Event::trigger($eventName . '.AFTER', [
            'model'        => $model,
            'propertyName' => $propertyName,
            'annotation'   => $annotation,
            'struct'       => $struct,
        ]);
    }

    /**
     * 初始化一对多关系.
     */
    public static function initByOneToMany(Model $model, string $propertyName, \Imi\Model\Annotation\Relation\OneToMany $annotation, ?array &$refData = null): void
    {
        $className = BeanFactory::getObjectClass($model);

        if (class_exists($annotation->model))
        {
            $modelClass = $annotation->model;
        }
        else
        {
            $modelClass = Imi::getClassNamespace($className) . '\\' . $annotation->model;
        }

        $struct = new OneToMany($className, $propertyName, $annotation);
        $leftField = $struct->getLeftField();
        $rightField = $struct->getRightField();
        $eventName = 'IMI.MODEL.RELATION.QUERY.' . $className . '.' . $propertyName;

        $model[$propertyName] = new ArrayList($modelClass);
        $leftValue = $model[$leftField];
        if (null !== $leftValue)
        {
            if (null === $refData)
            {
                /** @var IModelQuery $query */
                $query = $modelClass::query()->where($rightField, '=', $leftValue);
                if ($annotation->fields)
                {
                    $query->field(...$annotation->fields);
                }
                if ($annotation->withFields)
                {
                    $query->withField(...$annotation->withFields);
                }
                if ($annotation->order)
                {
                    $query->orderRaw($annotation->order);
                }
                if (null !== $annotation->limit)
                {
                    $query->limit($annotation->limit);
                }
                Event::trigger($eventName . '.BEFORE', [
                    'model'        => $model,
                    'propertyName' => $propertyName,
                    'annotation'   => $annotation,
                    'struct'       => $struct,
                    'query'        => $query,
                ]);
                $list = $query->select()->getArray();
                if ($list)
                {
                    $model[$propertyName]->append(...$list);
                }
            }
            else
            {
                if (isset($refData[$propertyName]))
                {
                    $item = &$refData[$propertyName];
                    $item['ids'][] = $leftValue;
                    $item['models'][$leftValue][] = $model;
                }
                else
                {
                    $refData[$propertyName] = [
                        'annotation' => $annotation,
                        'modelClass' => $modelClass,
                        'leftField'  => $leftField,
                        'rightField' => $rightField,
                        'ids'        => [$leftValue],
                        'models'     => [
                            $leftValue => [$model],
                        ],
                    ];
                }

                return;
            }
        }
        Event::trigger($eventName . '.AFTER', [
            'model'        => $model,
            'propertyName' => $propertyName,
            'annotation'   => $annotation,
            'struct'       => $struct,
        ]);
    }

    /**
     * 初始化多对多关系.
     */
    public static function initByManyToMany(Model $model, string $propertyName, \Imi\Model\Annotation\Relation\ManyToMany $annotation, ?array &$refData = null): void
    {
        $className = BeanFactory::getObjectClass($model);

        $struct = new ManyToMany($className, $propertyName, $annotation);
        $leftField = $struct->getLeftField();
        $rightField = $struct->getRightField();
        $middleModel = $struct->getMiddleModel();
        $middleTable = $middleModel::__getMeta()->getFullTableName();
        $rightModel = $struct->getRightModel();
        $rightTable = $rightModel::__getMeta()->getFullTableName();

        if ($annotation->fields)
        {
            $fields = $annotation->fields;
        }
        else
        {
            $fields = self::parseManyToManyQueryFields($middleModel, $rightModel);
        }

        $model[$propertyName] = new ArrayList($middleModel);
        $model[$annotation->rightMany] = new ArrayList($rightModel);
        $eventName = 'IMI.MODEL.RELATION.QUERY.' . $className . '.' . $propertyName;

        $leftValue = $model[$leftField];
        if (null !== $leftValue)
        {
            if (null === $refData)
            {
                $query = $rightModel::query($className::__getMeta()->getDbPoolName())
                            ->field(...$fields)
                            ->join($middleTable, $middleTable . '.' . $struct->getMiddleRightField(), '=', $rightTable . '.' . $rightField)
                            ->where($middleTable . '.' . $struct->getMiddleLeftField(), '=', $leftValue);
                if ($annotation->withFields)
                {
                    $query->withField(...$annotation->withFields);
                }
                if ($annotation->order)
                {
                    $query->orderRaw($annotation->order);
                }
                if (null !== $annotation->limit)
                {
                    $query->limit($annotation->limit);
                }
                Event::trigger($eventName . '.BEFORE', [
                    'model'        => $model,
                    'propertyName' => $propertyName,
                    'annotation'   => $annotation,
                    'struct'       => $struct,
                    'query'        => $query,
                ]);
                $list = $query->select()
                            ->getArray();
                if ($list)
                {
                    // 关联数据
                    static::appendMany($model[$propertyName], $list, $middleTable, $middleModel);

                    // 右侧表数据
                    $model[$annotation->rightMany]->append(...$list);
                }
            }
            else
            {
                if (isset($refData[$propertyName]))
                {
                    $item = &$refData[$propertyName];
                    $item['ids'][] = $leftValue;
                    $item['models'][$leftValue][] = $model;
                }
                else
                {
                    $refData[$propertyName] = [
                        'annotation'       => $annotation,
                        'fields'           => $fields,
                        'middleTable'      => $middleTable,
                        'rightTable'       => $rightTable,
                        'middleLeftField'  => $struct->getMiddleLeftField(),
                        'middleRightField' => $struct->getMiddleRightField(),
                        'leftField'        => $leftField,
                        'rightField'       => $rightField,
                        'middleModel'      => $middleModel,
                        'rightModel'       => $rightModel,
                        'ids'              => [$leftValue],
                        'models'           => [
                            $leftValue => [$model],
                        ],
                    ];
                }

                return;
            }
        }
        Event::trigger($eventName . '.AFTER', [
            'model'        => $model,
            'propertyName' => $propertyName,
            'annotation'   => $annotation,
            'struct'       => $struct,
        ]);
    }

    /**
     * 初始化多态一对一关系.
     */
    public static function initByPolymorphicOneToOne(Model $model, string $propertyName, \Imi\Model\Annotation\Relation\PolymorphicOneToOne $annotation, ?array &$refData = null): void
    {
        $className = BeanFactory::getObjectClass($model);
        $eventName = 'IMI.MODEL.RELATION.QUERY.' . $className . '.' . $propertyName;

        if (class_exists($annotation->model))
        {
            $modelClass = $annotation->model;
        }
        else
        {
            $modelClass = Imi::getClassNamespace($className) . '\\' . $annotation->model;
        }
        $struct = new PolymorphicOneToOne($className, $propertyName, $annotation);
        $leftField = $struct->getLeftField();
        $rightField = $struct->getRightField();

        $leftValue = $model[$leftField];
        if (null === $leftValue)
        {
            $rightModel = $modelClass::newInstance();
        }
        else
        {
            if (null === $refData)
            {
                /** @var IModelQuery $query */
                $query = $modelClass::query()->where($annotation->type, '=', $annotation->typeValue)->where($rightField, '=', $leftValue);
                if ($annotation->fields)
                {
                    $query->field(...$annotation->fields);
                }
                if ($annotation->withFields)
                {
                    $query->withField(...$annotation->withFields);
                }
                Event::trigger($eventName . '.BEFORE', [
                    'model'        => $model,
                    'propertyName' => $propertyName,
                    'annotation'   => $annotation,
                    'struct'       => $struct,
                    'query'        => $query,
                ]);
                $rightModel = $query->limit(1)->select()->get();
            }
            else
            {
                if (isset($refData[$propertyName]))
                {
                    $item = &$refData[$propertyName];
                    $item['ids'][] = $leftValue;
                    $item['models'][$leftValue][] = $model;
                }
                else
                {
                    $refData[$propertyName] = [
                        'annotation' => $annotation,
                        'modelClass' => $modelClass,
                        'leftField'  => $leftField,
                        'rightField' => $rightField,
                        'ids'        => [$leftValue],
                        'models'     => [
                            $leftValue => [$model],
                        ],
                    ];
                }

                return;
            }
        }

        $model[$propertyName] = $rightModel;
        Event::trigger($eventName . '.AFTER', [
            'model'        => $model,
            'propertyName' => $propertyName,
            'annotation'   => $annotation,
            'struct'       => $struct,
        ]);
    }

    /**
     * 初始化多态一对多关系.
     */
    public static function initByPolymorphicOneToMany(Model $model, string $propertyName, \Imi\Model\Annotation\Relation\PolymorphicOneToMany $annotation, ?array &$refData = null): void
    {
        $className = BeanFactory::getObjectClass($model);
        $eventName = 'IMI.MODEL.RELATION.QUERY.' . $className . '.' . $propertyName;

        if (class_exists($annotation->model))
        {
            $modelClass = $annotation->model;
        }
        else
        {
            $modelClass = Imi::getClassNamespace($className) . '\\' . $annotation->model;
        }

        $struct = new PolymorphicOneToMany($className, $propertyName, $annotation);
        $leftField = $struct->getLeftField();
        $rightField = $struct->getRightField();

        $model[$propertyName] = $modelPropery = new ArrayList($modelClass);
        $leftValue = $model[$leftField];
        if (null !== $leftValue)
        {
            if (null === $refData)
            {
                /** @var IModelQuery $query */
                $query = $modelClass::query()->where($annotation->type, '=', $annotation->typeValue)->where($rightField, '=', $leftValue);
                if ($annotation->fields)
                {
                    $query->field(...$annotation->fields);
                }
                if ($annotation->withFields)
                {
                    $query->withField(...$annotation->withFields);
                }
                if ($annotation->order)
                {
                    $query->orderRaw($annotation->order);
                }
                if (null !== $annotation->limit)
                {
                    $query->limit($annotation->limit);
                }
                Event::trigger($eventName . '.BEFORE', [
                    'model'        => $model,
                    'propertyName' => $propertyName,
                    'annotation'   => $annotation,
                    'struct'       => $struct,
                    'query'        => $query,
                ]);
                $list = $query->select()->getArray();
                if ($list)
                {
                    $modelPropery->append(...$list);
                }
            }
            else
            {
                if (isset($refData[$propertyName]))
                {
                    $item = &$refData[$propertyName];
                    $item['ids'][] = $leftValue;
                    $item['models'][$leftValue][] = $model;
                }
                else
                {
                    $refData[$propertyName] = [
                        'annotation' => $annotation,
                        'modelClass' => $modelClass,
                        'leftField'  => $leftField,
                        'rightField' => $rightField,
                        'ids'        => [$leftValue],
                        'models'     => [
                            $leftValue => [$model],
                        ],
                    ];
                }

                return;
            }
        }
        Event::trigger($eventName . '.AFTER', [
            'model'        => $model,
            'propertyName' => $propertyName,
            'annotation'   => $annotation,
            'struct'       => $struct,
        ]);
    }

    /**
     * 初始化多态，对应的实体模型.
     *
     * @param \Imi\Model\Annotation\Relation\PolymorphicToOne[] $annotations
     */
    public static function initByPolymorphicToOne(Model $model, string $propertyName, array $annotations, ?array &$refData = null): void
    {
        $className = BeanFactory::getObjectClass($model);
        $eventName = 'IMI.MODEL.RELATION.QUERY.' . $className . '.' . $propertyName;
        foreach ($annotations as $annotationItem)
        {
            $typeValue = $annotationItem->typeValue;
            if ($model[$annotationItem->type] == $typeValue)
            {
                $leftField = $annotationItem->modelField;
                $rightField = $annotationItem->field;
                if (class_exists($annotationItem->model))
                {
                    $modelClass = $annotationItem->model;
                }
                else
                {
                    $modelClass = Imi::getClassNamespace($className) . '\\' . $annotationItem->model;
                }
                $rightValue = $model[$rightField];
                if (null === $rightValue)
                {
                    $leftModel = $modelClass::newInstance();
                }
                else
                {
                    if (null === $refData)
                    {
                        /** @var IModelQuery $query */
                        $query = $modelClass::query()->where($leftField, '=', $rightValue);
                        if ($annotationItem->fields)
                        {
                            $query->field(...$annotationItem->fields);
                        }
                        if ($annotationItem->withFields)
                        {
                            $query->withField(...$annotationItem->withFields);
                        }
                        Event::trigger($eventName . '.BEFORE', [
                            'model'        => $model,
                            'propertyName' => $propertyName,
                            'annotation'   => $annotationItem,
                            'query'        => $query,
                        ]);
                        $leftModel = $query->limit(1)->select()->get();
                    }
                    else
                    {
                        if (!isset($refData[$propertyName]))
                        {
                            $refData[$propertyName]['annotation'] = $annotationItem;
                        }
                        if (isset($refData[$propertyName]['list'][$typeValue]))
                        {
                            $item = &$refData[$propertyName]['list'][$typeValue];
                            $item['ids'][] = $rightValue;
                            $item['models'][$rightValue][] = $model;
                        }
                        else
                        {
                            $refData[$propertyName]['list'][$typeValue] = [
                                'annotation' => $annotationItem,
                                'modelClass' => $modelClass,
                                'leftField'  => $leftField,
                                'rightField' => $rightField,
                                'ids'        => [
                                    $rightValue,
                                ],
                                'models'     => [
                                    $rightValue => [$model],
                                ],
                            ];
                        }

                        return;
                    }
                }
                $model[$propertyName] = $leftModel;
                break;
            }
        }
        Event::trigger($eventName . '.AFTER', [
            'model'        => $model,
            'propertyName' => $propertyName,
            'annotation'   => isset($leftModel) ? ($annotationItem ?? null) : null,
        ]);
    }

    /**
     * 初始化多态，对应的实体模型列表.
     *
     * @param \Imi\Model\Annotation\Relation\PolymorphicToMany[] $annotations
     */
    public static function initByPolymorphicToMany(Model $model, string $propertyName, array $annotations, ?array &$refData = null): void
    {
        $className = BeanFactory::getObjectClass($model);
        $eventName = 'IMI.MODEL.RELATION.QUERY.' . $className . '.' . $propertyName;

        foreach ($annotations as $annotationItem)
        {
            $typeValue = $annotationItem->typeValue;
            if ($model[$annotationItem->type] == $typeValue)
            {
                $struct = new PolymorphicManyToMany($className, $propertyName, $annotationItem);
                $leftField = $struct->getLeftField();
                $rightField = $struct->getRightField();
                $rightModel = $struct->getRightModel();
                $middleModel = $struct->getMiddleModel();
                $middleTable = $middleModel::__getMeta()->getFullTableName();
                $rightTable = $rightModel::__getMeta()->getFullTableName();
                $middleLeftField = $struct->getMiddleLeftField();
                $middleRightField = $struct->getMiddleRightField();

                if ($annotationItem->fields)
                {
                    $fields = $annotationItem->fields;
                }
                else
                {
                    $fields = self::parseManyToManyQueryFields($middleModel, $rightModel);
                }

                $model[$propertyName] = new ArrayList($struct->getRightModel());

                $leftValue = $model[$leftField];
                if (null !== $leftValue)
                {
                    if (null === $refData)
                    {
                        $query = $rightModel::query($className::__getMeta()->getDbPoolName())
                                    ->field(...$fields)
                                    ->join($middleTable, $middleTable . '.' . $middleLeftField, '=', $rightTable . '.' . $rightField)
                                    ->where($middleTable . '.' . $annotationItem->type, '=', $typeValue)
                                    ->where($middleTable . '.' . $middleRightField, '=', $leftValue);
                        if ($annotationItem->withFields)
                        {
                            $query->withField(...$annotationItem->withFields);
                        }
                        if ($annotationItem->order)
                        {
                            $query->orderRaw($annotationItem->order);
                        }
                        if (null !== $annotationItem->limit)
                        {
                            $query->limit($annotationItem->limit);
                        }
                        Event::trigger($eventName . '.BEFORE', [
                            'model'        => $model,
                            'propertyName' => $propertyName,
                            'annotation'   => $annotationItem,
                            'struct'       => $struct,
                            'query'        => $query,
                        ]);
                        $list = $query->select()
                                    ->getArray();
                        if ($list)
                        {
                            // 关联数据
                            $model[$propertyName]->append(...$list);
                        }
                    }
                    else
                    {
                        if (!isset($refData[$propertyName]))
                        {
                            $refData[$propertyName]['annotation'] = $annotationItem;
                        }
                        if (isset($refData[$propertyName]['list'][$typeValue]))
                        {
                            $item = &$refData[$propertyName]['list'][$typeValue];
                            $item['ids'][] = $leftValue;
                            $item['models'][$leftValue][] = $model;
                        }
                        else
                        {
                            $refData[$propertyName]['list'][$typeValue] = [
                                'annotation'       => $annotationItem,
                                'fields'           => $fields,
                                'middleTable'      => $middleTable,
                                'rightTable'       => $rightTable,
                                'middleLeftField'  => $middleLeftField,
                                'middleRightField' => $middleRightField,
                                'leftField'        => $leftField,
                                'rightField'       => $rightField,
                                'middleModel'      => $middleModel,
                                'rightModel'       => $rightModel,
                                'ids'              => [
                                    $leftValue,
                                ],
                                'models'     => [
                                    $leftValue => [$model],
                                ],
                            ];
                        }

                        return;
                    }
                }
                break;
            }
        }
        Event::trigger($eventName . '.AFTER', [
            'model'        => $model,
            'propertyName' => $propertyName,
            'annotation'   => isset($struct) ? ($annotationItem ?? null) : null,
            'struct'       => $struct ?? null,
        ]);
    }

    /**
     * 初始化多态多对多关系.
     */
    public static function initByPolymorphicManyToMany(Model $model, string $propertyName, \Imi\Model\Annotation\Relation\PolymorphicManyToMany $annotation, ?array &$refData = null): void
    {
        $className = BeanFactory::getObjectClass($model);
        $eventName = 'IMI.MODEL.RELATION.QUERY.' . $className . '.' . $propertyName;

        $struct = new PolymorphicManyToMany($className, $propertyName, $annotation);
        $leftField = $struct->getLeftField();
        $rightField = $struct->getRightField();
        $middleTable = $struct->getMiddleModel()::__getMeta()->getFullTableName();
        $rightTable = $struct->getRightModel()::__getMeta()->getFullTableName();

        if ($annotation->fields)
        {
            $fields = $annotation->fields;
        }
        else
        {
            $fields = self::parseManyToManyQueryFields($struct->getMiddleModel(), $struct->getRightModel());
        }

        $model[$propertyName] = new ArrayList($struct->getMiddleModel());
        $model[$annotation->rightMany] = new ArrayList($struct->getRightModel());
        $middleModel = $struct->getMiddleModel();
        $rightModel = $struct->getRightModel();

        $leftValue = $model[$leftField];
        if (null !== $leftValue)
        {
            if (null === $refData)
            {
                $query = $rightModel::query($className::__getMeta()->getDbPoolName())
                            ->field(...$fields)
                            ->join($middleTable, $middleTable . '.' . $struct->getMiddleRightField(), '=', $rightTable . '.' . $rightField)
                            ->where($middleTable . '.' . $annotation->type, '=', $annotation->typeValue)
                            ->where($middleTable . '.' . $struct->getMiddleLeftField(), '=', $leftValue);
                if ($annotation->withFields)
                {
                    $query->withField(...$annotation->withFields);
                }
                if ($annotation->order)
                {
                    $query->orderRaw($annotation->order);
                }
                if (null !== $annotation->limit)
                {
                    $query->limit($annotation->limit);
                }
                Event::trigger($eventName . '.BEFORE', [
                    'model'        => $model,
                    'propertyName' => $propertyName,
                    'annotation'   => $annotation,
                    'struct'       => $struct,
                    'query'        => $query,
                ]);
                $list = $query->select()
                              ->getArray();
                if ($list)
                {
                    // 关联数据
                    static::appendMany($model[$propertyName], $list, $middleTable, $middleModel);

                    // 右侧表数据
                    $model[$annotation->rightMany]->append(...$list);
                }
            }
            else
            {
                if (isset($refData[$propertyName]))
                {
                    $item = &$refData[$propertyName];
                    $item['ids'][] = $leftValue;
                    $item['models'][$leftValue][] = $model;
                }
                else
                {
                    $refData[$propertyName] = [
                        'annotation'       => $annotation,
                        'fields'           => $fields,
                        'middleTable'      => $middleTable,
                        'rightTable'       => $rightTable,
                        'middleLeftField'  => $struct->getMiddleLeftField(),
                        'middleRightField' => $struct->getMiddleRightField(),
                        'leftField'        => $leftField,
                        'rightField'       => $rightField,
                        'middleModel'      => $middleModel,
                        'rightModel'       => $rightModel,
                        'ids'              => [$leftValue],
                        'models'           => [
                            $leftValue => [$model],
                        ],
                    ];
                }

                return;
            }
        }
        Event::trigger($eventName . '.AFTER', [
            'model'        => $model,
            'propertyName' => $propertyName,
            'annotation'   => $annotation,
            'struct'       => $struct,
        ]);
    }

    /**
     * 处理多对多查询用的字段，需要是"表名.字段名"，防止冲突
     */
    private static function parseManyToManyQueryFields(string $middleModel, string $rightModel): array
    {
        $fields = [];

        /** @var \Imi\Model\Meta $middleModelMeta */
        $middleModelMeta = $middleModel::__getMeta();
        $middleTable = $middleModelMeta->getFullTableName();
        /** @var \Imi\Model\Meta $rightModelMeta */
        $rightModelMeta = $rightModel::__getMeta();
        $rightTable = $rightModelMeta->getFullTableName();

        foreach ($middleModelMeta->getDbFields() as $name => $_)
        {
            $fields[] = $field = new Field();
            $field->setTable($middleTable);
            $field->setField($name);
            $field->setAlias($middleTable . '_' . $name);
        }
        foreach ($middleModelMeta->getSqlColumns() as $name => $sqlAnnotations)
        {
            /** @var \Imi\Model\Annotation\Sql $sqlAnnotation */
            $sqlAnnotation = $sqlAnnotations[0];
            $fields[] = $field = new Field();
            $field->useRaw();
            $field->setRawSQL($sqlAnnotation->sql);
            $field->setAlias($middleTable . '_' . $name);
        }

        foreach ($rightModelMeta->getDbFields() as $name => $_)
        {
            $fields[] = $field = new Field();
            $field->setTable($rightTable);
            $field->setField($name);
        }
        foreach ($rightModelMeta->getSqlColumns() as $name => $sqlAnnotations)
        {
            /** @var \Imi\Model\Annotation\Sql $sqlAnnotation */
            $sqlAnnotation = $sqlAnnotations[0];
            $fields[] = $field = new Field();
            $field->useRaw();
            $field->setRawSQL($sqlAnnotation->sql);
        }

        return $fields;
    }

    /**
     * 追加到Many列表.
     */
    public static function appendMany(ArrayList $manyList, array $dataList, string $table, string $modelClass): void
    {
        $tableLength = \strlen($table);
        $keysMap = [];
        foreach ($dataList as $row)
        {
            $tmpRow = [];
            if ($row instanceof BaseModel)
            {
                $row = $row->__getOriginData();
            }
            foreach ($row as $key => $value)
            {
                if (isset($keysMap[$key]))
                {
                    if (false !== $keysMap[$key])
                    {
                        $tmpRow[$keysMap[$key]] = $value;
                    }
                }
                elseif (str_starts_with($key, $table))
                {
                    $keysMap[$key] = $realKey = substr($key, $tableLength);
                    $tmpRow[$realKey] = $value;
                }
                else
                {
                    $keysMap[$key] = false;
                }
            }
            $manyList->append($modelClass::createFromRecord($tmpRow));
        }
    }
}
