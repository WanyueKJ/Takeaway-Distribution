<?php

use Imi\App;
use Imi\Log\LogLevel;

$mode = App::getApp()->getType();

return [
    'configs'    =>    [
    ],
    // bean扫描目录
    'beanScan'    =>    [
        'ImiApp\WebSocketServer\Controller',
        'ImiApp\WebSocketServer\HttpController',
    ],
    'beans'    =>    [
        'SessionManager'    =>    [
            'handlerClass'    =>    \Imi\Server\Session\Handler\File::class,
        ],
        'SessionFile'    =>    [
            'savePath'    =>    dirname(__DIR__, 2).'/.runtime/.session/',
        ],
        'SessionConfig'    =>    [

        ],
        'SessionCookie'    =>    [
            'lifetime'    =>    86400 * 30,
        ],
        'HttpDispatcher'    =>    [
            'middlewares'    =>    [
                \ImiApp\WebSocketServer\Middleware\PoweredBy::class,
                \Imi\Server\Session\Middleware\HttpSessionMiddleware::class,
                'swoole' === $mode ? \Imi\Swoole\Server\WebSocket\Middleware\HandShakeMiddleware::class : \Imi\Workerman\Server\WebSocket\Middleware\HandShakeMiddleware::class,
                \Imi\Server\Http\Middleware\RouteMiddleware::class,
            ],
        ],
        'HtmlView'    =>    [
            'templatePath'    =>    dirname(__DIR__).'/template/',
            // 支持的模版文件扩展名，优先级按先后顺序
            'fileSuffixs'        =>    [
                'tpl',
                'html',
                'php',
            ],
        ],
        'WebSocketDispatcher'    =>    [
            'middlewares'    =>    [
                \Imi\Server\WebSocket\Middleware\RouteMiddleware::class,
            ],
        ],
        // 本地模式
        // 'ConnectionContextStore'   =>  [
        //     'handlerClass'  =>  \Imi\Server\ConnectionContext\StoreHandler\Local::class,
        // ],
        // 'ConnectionContextLocal'    =>    [
        //     'lockId'    =>  null, // 非必设，可以用锁来防止数据错乱问题
        // ],
        // 'ServerGroup' => [
        //     'groupHandler' => 'GroupLocal',
        // ],

        // // Redis 模式
        // 'ConnectionContextStore'   =>  [
        //     'handlerClass'  =>  \Imi\Server\ConnectionContext\StoreHandler\Redis::class,
        // ],
        // 'ConnectionContextRedis'    =>    [
        //     'redisPool'    => 'redis', // Redis 连接池名称
        //     'redisDb'      => 0, // redis中第几个库
        //     'key'          => 'imi:connect_context', // 键
        //     'heartbeatTimespan' => 5, // 心跳时间，单位：秒
        //     'heartbeatTtl' => 8, // 心跳数据过期时间，单位：秒
        //     'dataEncode'=>  'serialize', // 数据写入前编码回调
        //     'dataDecode'=>  'unserialize', // 数据读出后处理回调
        //     'lockId'    =>  null, // 非必设，可以用锁来防止数据错乱问题
        // ],
        // 'ServerGroup' => [
        //     'groupHandler' => 'GroupRedis',
        // ],

        // 网关模式
        'ConnectionContextStore'   => [
            'handlerClass'  => 'ConnectionContextGateway',
        ],
        'ServerGroup' => [
            'groupHandler' => 'GroupGateway',
        ],
    ],
];
