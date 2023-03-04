<?php

declare(strict_types=1);

namespace Imi\Bean\Listener;

use Imi\Bean\Annotation;
use Imi\Bean\Annotation\AnnotationManager;
use Imi\Bean\BeanManager;
use Imi\Bean\PartialManager;
use Imi\Config;
use Imi\Event\EventParam;
use Imi\Event\IEventListener;

class BuildRuntimeListener implements IEventListener
{
    /**
     * {@inheritDoc}
     */
    public function handle(EventParam $e): void
    {
        if (!Config::get('@app.imi.runtime.bean', true))
        {
            return;
        }
        $eventData = $e->getData();
        $data = [];
        $parser = Annotation::getInstance()->getParser();
        if (Config::get('@app.imi.runtime.annotation_parser_data', true))
        {
            $data['annotationParserData'] = $parser->getStoreData();
        }
        if (Config::get('@app.imi.runtime.annotation_parser_parsers', true))
        {
            $data['annotationParserParsers'] = $parser->getParsers();
        }

        if (Config::get('@app.imi.runtime.annotation_manager_annotations', true))
        {
            $data['annotationManagerAnnotations'] = AnnotationManager::getAnnotations();
        }
        elseif (Config::get('@app.imi.runtime.annotation_manager_annotations_cache', true))
        {
            $data['annotationManagerAnnotationsCache'] = AnnotationManager::generateAnnotationsCache();
        }

        if (Config::get('@app.imi.runtime.annotation_manager_annotation_relation', true))
        {
            $data['annotationManagerAnnotationRelation'] = AnnotationManager::getAnnotationRelation();
        }
        elseif (Config::get('@app.imi.runtime.annotation_manager_annotation_relation_cache', true))
        {
            $data['annotationManagerAnnotationRelationCache'] = AnnotationManager::getAnnotationRelation()->generateCache();
        }

        if (Config::get('@app.imi.runtime.partial', true))
        {
            $data['partial'] = PartialManager::getMap();
        }
        $data['bean'] = BeanManager::getMap();
        $eventData['data']['bean'] = $data;
    }
}
