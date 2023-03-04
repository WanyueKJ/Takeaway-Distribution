<?php

return [
    'secretId'                 => [// 在后台插件配置表单中的键名 ,会是config[text]
        'title'   => '腾讯云API密钥SecretId', // 表单的label标题
        'type'    => 'text',// 表单的类型：text,password,textarea,checkbox,radio,select等
        'value'   => '',// 表单的默认值
        "rule"    => [
            "require" => true
        ],
        "message" => [
            "require" => '腾讯云API密钥SecretId不能为空'
        ],
        'tip'     => '' //表单的帮助提示
    ],
    'secretKey'                 => [// 在后台插件配置表单中的键名 ,会是config[password]
        'title'   => '腾讯云API密钥SecretKey',
        'type'    => 'text',
        'value'   => '',
        "rule"    => [
            "require" => true
        ],
        "message" => [
            "require" => '腾讯云API密钥SecretKey不能为空'
        ],
        'tip'     => ''
    ],
    'protocol'                  => [// 在后台插件配置表单中的键名 ,会是config[select]
        'title'   => '域名协议',
        'type'    => 'select',
        'options' => [//select 和radio,checkbox的子选项
            'http'  => 'http',// 值=>显示
            'https' => 'https',
        ],
        'value'   => 'http',
        "rule"    => [
            "require" => true
        ],
        "message" => [
            "require" => '域名协议不能为空'
        ],
        'tip'     => ''
    ],
    'domain'                    => [
        'title'   => '空间域名',
        'type'    => 'text',
        'value'   => '',
        "rule"    => [
            "require" => true
        ],
        "message" => [
            "require" => '空间域名不能为空'
        ],
        'tip'     => ''
    ],
    'region'                  => [// 在后台插件配置表单中的键名 ,会是config[select]
        'title'   => '地域简称',
        'type'    => 'text',
        'value'   => '',
        "rule"    => [
            "require" => true
        ],
        "message" => [
            "require" => '地域简称不能为空'
        ],
        'tip'     => ''
    ],
    'bucket'                    => [
        'title'   => '存储桶',
        'type'    => 'text',
        'value'   => '',
        "rule"    => [
            "require" => true
        ],
        "message" => [
            "require" => '存储桶不能为空'
        ],
        'tip'     => ''
    ],
];
					