<?php

use Imi\App;

$mode = App::isInited() ? App::getApp()->getType() : null;

return [
    // 项目根命名空间
    'namespace'    =>    'ImiApp',

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
        // 'type'      =>  Imi\Swoole\Server\Type::WEBSOCKET,
        'type'      => \Imi\WorkermanGateway\Swoole\Server\Type::BUSINESS_WEBSOCKET, // workerman gateway 模式 Worker
        // 'host'      =>  '0.0.0.0',
        // 'port'      =>  8081,
        'mode'      =>  SWOOLE_BASE,
        'configs'   =>    [
            // 'worker_num'        =>  8,
            // 'task_worker_num'   =>  16,
        ],
        'beans' => [
            // 'ServerUtil' => \Imi\Swoole\Server\Util\LocalServerUtil::class,
            // 'ServerUtil' => \Imi\Swoole\Server\Util\LocalServerUtil::class,
            // 'ServerUtil' => 'RedisServerUtil',
            'ServerUtil' => 'SwooleGatewayServerUtil',
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
                // 'ServerUtil' => \Imi\Workerman\Server\Util\LocalServerUtil::class,
                // 'ServerUtil' => 'ChannelServerUtil',
                'ServerUtil' => 'WorkermanGatewayServerUtil',
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
        // 以下是 Workerman Gateway 模式需要
        'register' => [
            'namespace'   => 'Imi\WorkermanGateway\Test\AppServer\Register',
            'type'        => Imi\WorkermanGateway\Workerman\Server\Type::REGISTER,
            'host'        => '127.0.0.1',
            'port'        => 13004,
            'configs'     => [
            ],
        ],
        'gateway' => [
            'namespace'   => 'Imi\WorkermanGateway\Test\AppServer\Gateway',
            'type'        => Imi\WorkermanGateway\Workerman\Server\Type::GATEWAY,
            'socketName'  => 'websocket://0.0.0.0:8081', // 网关监听的地址
            'configs'     => [
                'lanIp'           => '127.0.0.1',
                'startPort'       => 12900,
                'registerAddress' => '127.0.0.1:13004',
            ],
        ],
        // workerman gateway 模式 Worker
        'websocketWorker' => [
            'namespace'   => 'ImiApp\WebSocketServer',
            'type'        => Imi\WorkermanGateway\Workerman\Server\Type::BUSINESS_WEBSOCKET,
            'shareWorker' => '\\' === \DIRECTORY_SEPARATOR ? 'http' : null,
            'configs'     => [
                'registerAddress' => '127.0.0.1:13004',
                'count'           => 2,
            ],
            'beans' => [
                // 'ServerUtil' => \Imi\Workerman\Server\Util\LocalServerUtil::class,
                // 'ServerUtil' => 'ChannelServerUtil',
                'ServerUtil' => 'WorkermanGatewayServerUtil',
            ],
        ],
    ] : [],

    'workerman' => [
        // 多进程通讯组件配置
        // 'channel' => [
        //     'host' => '127.0.0.1',
        //     'port' => 13005,
        // ],
    ],

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
                'username'    => 'n',
                'password'    => '',
                'database'    => '',
                'charset'     => 'utf8mb4',
            ],
        ],
        'redis'    => [
            'pool'    => [
                'class'        => \Imi\Swoole\Redis\Pool\CoroutineRedisPool::class,
                'config'       => [
                    'maxResources'    => 10,
                    'minResources'    => 0,
                ],
            ],
            'resource'    => [
                'host'      => '127.0.0.1',
                'port'      => 6379,
                'password'  => '',
            ],
        ],
    ],

    // 数据库配置
    'db'    =>    [
        // 数默认连接池名
        'defaultPool'    =>    'maindb',
        // FPM、Workerman 下用
        'connections'   => [
            'maindb' => [
                'host' => '127.0.0.1',
                'port'        => 3306,
                'username' => '',
                'password' => '',
                'database' => '',
                'charset'  => 'utf8mb4',
                // 'port'    => '3306',
                // 'timeout' => '建立连接超时时间',
                // 'charset' => '',
                // 使用 hook pdo 驱动（缺省默认）
                // 'dbClass' => \Imi\Db\Drivers\PdoMysql\Driver::class,
                // 使用 hook mysqli 驱动
                // 'dbClass' => \Imi\Db\Drivers\Mysqli\Driver::class,
                // 使用 Swoole MySQL 驱动
                // 'dbClass' => \Imi\Swoole\Db\Drivers\Swoole\Driver::class,
                // 数据库连接后，执行初始化的 SQL
                // 'sqls' => [
                //     'select 1',
                //     'select 2',
                // ],
            ],
        ],
    ],

    // redis 配置
    'redis' =>  [
        // 数默认连接池名
        'defaultPool'   =>  'redis',
        // FPM、Workerman 下用
        'connections'   => [
            'redis' => [
                'host'	=>	'127.0.0.1',
                'port'	=>	6379,
                // 是否自动序列化变量
                'serialize'	=>	true,
                // 密码
                'password'	=>	''
                // 第几个库
                'db'	=>	0,
            ],
        ],
    ],

    // 日志配置
    'logger' => [
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
                    [
                        'class'     => \Monolog\Handler\RotatingFileHandler::class,
                        'construct' => [
                            'filename' => dirname(__DIR__).'/.runtime/logs/log.log',
                        ],
                        'formatter' => [
                            'class'     => \Monolog\Formatter\LineFormatter::class,
                            'construct' => [
                                'dateFormat'                 => 'Y-m-d H:i:s',
                                'allowInlineLineBreaks'      => true,
                                'ignoreEmptyContextAndExtra' => true,
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
];
