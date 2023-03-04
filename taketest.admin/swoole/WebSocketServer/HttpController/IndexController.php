<?php

namespace ImiApp\WebSocketServer\HttpController;

use Imi\Db\Db;
use Imi\Server\Http\Controller\HttpController;
use Imi\Server\Http\Route\Annotation\Action;
use Imi\Server\Http\Route\Annotation\Controller;
use Imi\Server\Http\Route\Annotation\Route;


/**
 * @Controller("/")
 */
class IndexController extends HttpController
{
    /**
     * @Action
     * @Route("/")
     *
     * @return array
     */
    public function index()
    {
        return [
            'data'  =>  'index',
        ];
    }

    /**
     * @Action
     *
     * @return array
     */
    public function api()
    {
        return [
            'data'  =>  'api',
        ];
    }
}
