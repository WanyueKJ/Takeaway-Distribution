<?php

use Imi\App;

$mode = App::isInited() ? App::getApp()->getType() : null;

return [
    // 项目根命名空间
    'namespace'    =>    'ImiApp',

    'debug' => false,

    // 配置文件
    'configs'    =>    [
        'beans'        =>    __DIR__.'/beans.php',
    ],

    // 'ignoreNamespace'   => [
    //     'ImiApp\vendor\*',
    // ],

    // 组件命名空间
    'components'    =>  [
    ],

    // 主服务器配置
    'mainServer'    =>    'swoole' === $mode ? [
        'namespace' =>  'ImiApp\WebSocketServer',
        'type'      =>  Imi\Swoole\Server\Type::WEBSOCKET,
        // 'type'      => \Imi\WorkermanGateway\Swoole\Server\Type::BUSINESS_WEBSOCKET, // workerman gateway 模式 Worker
        'host'      =>  '0.0.0.0',
        'port'      =>  8085,
        'mode'      =>  SWOOLE_BASE,
        'sockType'  =>  SWOOLE_SOCK_TCP | SWOOLE_SSL, // SSL 需要设置一下 sockType
        'configs'   =>    [
            'worker_num'        =>  1,
            'task_worker_num'   =>  1,
            // 配置证书
            'ssl_cert_file'     =>  '/www/server/panel/vhost/cert/taketest2.sdwanyue.com/fullchain.pem',
            'ssl_key_file'      =>  '/www/server/panel/vhost/cert/taketest2.sdwanyue.com/privkey.pem',
        ],
        'beans' => [
            'ServerUtil' => \Imi\Swoole\Server\Util\LocalServerUtil::class,
//            'ServerUtil' => 'RedisServerUtil',
            // 'ServerUtil' => 'SwooleGatewayServerUtil',
        ],
        // workerman gateway 模式
        'workermanGateway' => [
            'registerAddress'      => '127.0.0.1:13004',
            'worker_coroutine_num' => swoole_cpu_num(),
            'channel'              => [
                'size' => 1024,
            ],
        ],
    ] : [],

    // 子服务器（端口监听）配置
    'subServers'        =>    'swoole' === $mode ? [
        // 'SubServerName'   =>  [
        //     'namespace'    =>    'ImiApp\XXXServer',
        //     'type'        =>    Imi\Server\Type::HTTP,
        //     'host'        =>    '0.0.0.0',
        //     'port'        =>    13005,
        // ]
    ] : [],

    // Workerman 服务器配置
    'workermanServer' => 'workerman' === $mode ? [
        'http' => [
            'namespace' => 'ImiApp\WebSocketServer',
            'type'      => Imi\Workerman\Server\Type::HTTP,
            'host'      => '0.0.0.0',
            'port'      => 8080,
            'configs'   => [
                'count' => 1,
            ],
            'beans' => [
                'ServerUtil' => \Imi\Workerman\Server\Util\LocalServerUtil::class,
                // 'ServerUtil' => 'ChannelServerUtil',
                // 'ServerUtil' => 'WorkermanGatewayServerUtil',
            ],
        ],
        // // Workerman Gateway 模式请注释 websocket
        'websocket' => [
            'namespace'   => 'ImiApp\WebSocketServer',
            'type'        => Imi\Workerman\Server\Type::WEBSOCKET,
            'host'        => '0.0.0.0',
            'port'        => 8081,
            'shareWorker' => 'http',
            'beans' => [
                'ServerUtil' => \Imi\Workerman\Server\Util\LocalServerUtil::class,
                // 'ServerUtil' => 'ChannelServerUtil',
                // 'ServerUtil' => 'WorkermanGatewayServerUtil',
            ],
            // 数据处理器
            'dataParser'    =>    Imi\Server\DataParser\JsonObjectParser::class,
        ],
        // 'channel' => [
        //     'namespace'   => '',
        //     'type'        => Imi\Workerman\Server\Type::CHANNEL,
        //     'host'        => '127.0.0.1',
        //     'port'        => 13005,
        //     'configs'     => [
        //     ],
        // ],
    ] : [],



    // 连接池配置
    'pools'    =>    [
        // 主数据库
        'maindb'    => [
            'pool'    => [
                'class'        => \Imi\Swoole\Db\Pool\CoroutineDbPool::class,
                'config'       => [
                    'maxResources'    => 10,
                    'minResources'    => 0,
                ],
            ],
            'resource'    => [
                'host'        => '127.0.0.1',
                'port'        => 3306,
                'username'    => '',
                'password'    => '',
                'database'    => '',
                'charset'     => 'utf8mb4',
                'prefix'      => 'cmf_', // 表前缀
            ],
        ],
        'redis'    => [
            'pool'    => [
                'class'        => \Imi\Swoole\Redis\Pool\CoroutineRedisPool::class,
                'config'       => [
                    'maxResources'    => 20,
                    'minResources'    => 0,
                ],
            ],
            'resource'    => [
                'host'      => '127.0.0.1',
                'port'      => 6379,
                'password'  => '',
                'db'        => 30,
                'options'=>[
                    \Redis::OPT_READ_TIMEOUT => -1,
                ]
            ],
        ],
    ],

    // 数据库配置
    'db'    =>    [
        // 数默认连接池名
        'defaultPool'    =>    'maindb',
    ],

    // redis 配置
    'redis' =>  [
        // 数默认连接池名
        'defaultPool'   =>  'redis',
    ],

    // 日志配置
    'logger' => [
        'async' => true, // 是否启用异步日志，仅 Swoole 模式有效，可以有效提升大量日志记录时的接口响应速度
        'channels' => [
            'imi' => [
                'handlers' => [
                    [
                        'class'     => \Imi\Log\Handler\ConsoleHandler::class,
                        'formatter' => [
                            'class'     => \Imi\Log\Formatter\ConsoleLineFormatter::class,
                            'construct' => [
                                'format'                     => null,
                                'dateFormat'                 => 'Y-m-d H:i:s',
                                'allowInlineLineBreaks'      => true,
                                'ignoreEmptyContextAndExtra' => true,
                            ],
                        ],
                    ],
                    /*[
                        'class'     => \Monolog\Handler\RotatingFileHandler::class,
                        'construct' => [
                            'filename' => dirname(__DIR__).'/.runtime/logs/log.log',
                            'level'  => \Imi\Log\MonoLogger::DEBUG, // 开发调试环境
                            // 'level'  => \Imi\Log\MonoLogger::INFO,  // 生产环境
                            'bubble'   => false,
                        ],
                        'formatter' => [
                            'class'     => \Monolog\Formatter\LineFormatter::class,
                            'construct' => [
                                'dateFormat'                 => 'Y-m-d H:i:s',
                                'allowInlineLineBreaks'      => true,
                                'ignoreEmptyContextAndExtra' => true,
                            ],
                        ],
                    ],*/
                ],
            ],
        ],
    ],
];
