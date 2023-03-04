<?php
/**
 * 请在下面放置任何您需要的应用配置
 *
 * @license     http://www.phalapi.net/license GPL 协议
 * @link        http://www.phalapi.net/
 * @author dogstar <chanzonghuang@gmail.com> 2017-07-13
 */

return array(

    /**
     * 应用接口层的统一参数
     */
    'apiCommonRules' => array(
        'uid' => array('name' => 'uid', 'type' => 'int', 'default'=>'0','desc' => '用户ID'),
        'token' => array('name' => 'token', 'type' => 'string', 'default'=>'','desc' => '用户Toekn'),
        'pack' => array('name' => 'pack', 'type' => 'string', 'default'=>'','desc' => '包名'),
        'model' => array('name' => 'model', 'type' => 'string', 'default'=>'','desc' => '设备'),
        'system' => array('name' => 'system', 'type' => 'string', 'default'=>'','desc' => '系统版本'),
        'version' => array('name' => 'version', 'type' => 'string', 'default'=>'','desc' => '应用版本'),
        'source' => array('name' => 'source', 'type' => 'int', 'default'=>'','desc' => '来源,0web，1android，2ios，'),
        'lang' => array('name' => 'lang', 'type' => 'string', 'default'=>'','desc' => '语言包'),
    ),

    /* redis信息 */
    'REDIS_HOST' => "127.0.0.1",
    'REDIS_AUTH' => "",
    'REDIS_PORT' => "6379",
    'REDIS_SELECT' => 0,
    
    /* 接口签名key */
    'sign_key' => '400d069a791d51ada8af3e6c2979bcd7',
    
	/* 密码加密key */
	"authcode" => 'uV1sDvmUeV9Lcva00i',

    /* 存储方式 0本地 1七牛  2阿里云 3腾讯云*/
    'uptype' => "3",

    /**
     * 七牛相关配置
     */
    'Qiniu' => [
        //ak
        'access_key' => '',
        //sk
        'secret_key' => '',
        //存储空间
        'space_bucket' => '',
        //cdn加速域名 格式：http(s)://a.com
        'space_host' => '',
        // 上传文件名前缀
        'preffix' => '',
        // 存储区域 Region ID
        'region' => 'z0',
        //区域上传域名(服务端)  https://developer.qiniu.com/kodo/manual/1671/region-endpoint  非华东需配置
        //'upload_url' => '',
    ],
    'Aliyun'=>[
        'accessKeyId'=>'',
        'accessKeySecret'=>'',
        //域名 格式：http(s)://a.com
        'endpoint'=>'',
        'regionId'=>'',
        'bucket'=>'',
        'roleArn'=>'',
        //域名 格式：http(s)://a.com
        'host'=>'',
    ],
    'Txcos'=>[
        'appid'=>'',
        'secretId'=>'',
        'secretKey'=>'',
        'region'=>'',
        'bucket'=>'',
        //域名 格式：http(s)://a.com
        'host'=>'',
    ],
    
    /**
     * 接口服务白名单，格式：接口服务类名.接口服务方法名
     *
     * 示例：
     * - *.*         通配，全部接口服务，慎用！
     * - Site.*      Api_Default接口类的全部方法
     * - *.Index     全部接口类的Index方法
     * - Site.Index  指定某个接口服务，即Api_Default::Index()
     */
    'service_whitelist' => array(
        'Site.Index',
    ),
);
