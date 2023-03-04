<?php

namespace ImiApp\WebSocketServer\HttpController;

use Imi\Server\Http\Controller\HttpController;
use Imi\Server\Http\Route\Annotation\Action;
use Imi\Server\Http\Route\Annotation\Controller;
use Imi\Server\Http\Route\Annotation\Route;
use Imi\Server\View\Annotation\View;
use Imi\Server\WebSocket\Route\Annotation\WSConfig;

/**
 * 测试.
 * @Controller
 * @View(renderType="html")
 */
class HandShakeController extends HttpController
{
    /**
     * @Action
     * @Route("/ws")
     * @WSConfig(parserClass=\Imi\Server\DataParser\JsonObjectParser::class)
     * @return void
     */
    public function ws()
    {
        // 握手处理，什么都不做，框架会帮你做好
    }
}
