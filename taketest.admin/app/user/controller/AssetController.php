<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2019 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: kane <chengjin005@163.com>
// +----------------------------------------------------------------------
namespace app\user\controller;

use cmf\controller\AdminBaseController;
use cmf\lib\Upload;
use think\Db;
use think\facade\View;

/**
 * 附件上传控制器
 * Class Asset
 * @package app\asset\controller
 */
class AssetController extends AdminBaseController
{
    public function initialize()
    {
        $adminId = cmf_get_current_admin_id();
        $userId  = cmf_get_current_user_id();
        $tId  = get_current_teacher_id();
        if (empty($adminId) && empty($userId) && empty($tId)) {
            $this->error("非法上传！");
        }
    }

    /**
     * webuploader 上传
     */
    public function webuploader()
    {
        if ($this->request->isPost()) {

            $uploader = new Upload();

            $result = $uploader->upload();

            if ($result === false) {
                $this->error($uploader->getError());
            } else {
                $this->success("上传成功!", '', $result);
            }

        } else {
            $uploadSetting = cmf_get_upload_setting();

            $arrFileTypes = [
                'image' => ['title' => 'Image files', 'extensions' => $uploadSetting['file_types']['image']['extensions']],
                'video' => ['title' => 'Video files', 'extensions' => $uploadSetting['file_types']['video']['extensions']],
                'audio' => ['title' => 'Audio files', 'extensions' => $uploadSetting['file_types']['audio']['extensions']],
                'file'  => ['title' => 'Custom files', 'extensions' => $uploadSetting['file_types']['file']['extensions']]
            ];

            $arrData = $this->request->param();
            if (empty($arrData["filetype"])) {
                $arrData["filetype"] = "image";
            }

            $fileType = $arrData["filetype"];

            if (array_key_exists($arrData["filetype"], $arrFileTypes)) {
                $extensions                = $uploadSetting['file_types'][$arrData["filetype"]]['extensions'];
                $fileTypeUploadMaxFileSize = $uploadSetting['file_types'][$fileType]['upload_max_filesize'];
            } else {
                $this->error('上传文件类型配置错误！');
            }


            View::share('filetype', $arrData["filetype"]);
            View::share('extensions', $extensions);
            View::share('upload_max_filesize', $fileTypeUploadMaxFileSize * 1024);
            View::share('upload_max_filesize_mb', intval($fileTypeUploadMaxFileSize / 1024));
            $maxFiles  = intval($uploadSetting['max_files']);
            $maxFiles  = empty($maxFiles) ? 20 : $maxFiles;
            $chunkSize = intval($uploadSetting['chunk_size']);
            $chunkSize = empty($chunkSize) ? 512 : $chunkSize;
            View::share('max_files', $arrData["multi"] ? $maxFiles : 1);
            View::share('chunk_size', $chunkSize); //// 单位KB
            View::share('multi', $arrData["multi"]);
            View::share('app', $arrData["app"]);
            if(!$arrData["multi"]){
                $storageSetting = cmf_get_option('storage');
                $type=$storageSetting['type'];
                $config=[];
                if($type!='Local'){
                    $config = Db::name('plugin')->where('name', $type)->value('config');
                    $config=json_decode($config,true);
                }

                if($type=='Cos'){
                    $sts=$this->getTxSts($config);
                    $config['sts']=$sts;
                    $this->assign('upconfig', $config);
                    return $this->fetch(":cosuploader");
                }
            }

            //$content = hook_one('fetch_upload_view');
            $content='';
            $tabs = ['local', 'url', 'cloud'];

            $tab = !empty($arrData['tab']) && in_array($arrData['tab'], $tabs) ? $arrData['tab'] : 'local';

            if (!empty($content)) {
                $this->assign('has_cloud_storage', true);
            }

            if (!empty($content) && $tab == 'cloud') {
                return $content;
            }

            $tab = $tab == 'cloud' ? 'local' : $tab;

            $this->assign('tab', $tab);
            return $this->fetch(":webuploader");

        }
    }

    /**
     * 腾讯云上传sts信息
     * @desc 用于获取阿里云上传sts信息
     */
    public function getTxSts($config){

        require_once CMF_ROOT.'sdk/tencentcloud/sts.php';

        $sts = new \STS();
        $config = array(
            'url' => 'https://sts.tencentcloudapi.com/',
            'domain' => 'sts.tencentcloudapi.com',
            'proxy' => '',
            'secretId' => $config['secretId'], // 腾讯云存储secretid密钥
            'secretKey' => $config['secretKey'], // 腾讯云存储secretkey
            'bucket' => $config['bucket'], // bucket-appid
            'region' => $config['region'], // 换成 bucket 所在地区 如ap-shanghai
            'durationSeconds' => 1800, // 密钥有效期
            'allowPrefix' => '*', // 这里改成允许的路径前缀，可以根据自己网站的用户登录状态判断允许上传的具体路径，例子： a.jpg 或者 a/* 或者 * (使用通配符*存在重大安全风险, 请谨慎评估使用)
            // 密钥的权限列表。简单上传和分片需要以下的权限，其他权限列表请看 https://cloud.tencent.com/document/product/436/31923
            'allowActions' => array (
                // 简单上传
                'name/cos:PutObject',
                'name/cos:PostObject',
                // 分片上传
                'name/cos:InitiateMultipartUpload',
                'name/cos:ListMultipartUploads',
                'name/cos:ListParts',
                'name/cos:UploadPart',
                'name/cos:CompleteMultipartUpload'
            )
        );

        // 获取临时密钥，计算签名
        $tempKeys = $sts->getTempKeys($config);

        $info['sessionToken']=$tempKeys['credentials']['sessionToken'] ?? '';
        $info['tmpSecretId']=$tempKeys['credentials']['tmpSecretId'] ?? '';
        $info['tmpSecretKey']=$tempKeys['credentials']['tmpSecretKey'] ?? '';
        $info['requestId']=$tempKeys['requestId'] ?? '';
        $info['expiredTime']=(string)$tempKeys['expiredTime'] ?? '';
        $info['startTime']=(string)$tempKeys['startTime'] ?? '';

        return $info;

    }

}
