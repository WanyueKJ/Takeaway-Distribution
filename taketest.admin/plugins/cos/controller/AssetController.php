<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2018 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: Dean <zxxjjforever@163.com>
// +----------------------------------------------------------------------
namespace plugins\aliyun\controller; //Demo插件英文名，改成你的插件英文就行了
use cmf\controller\PluginBaseController;
use plugins\aliyun\lib\Aliyun;
use think\Validate;
use think\Db;

use OSS\OssClient;
use OSS\Core\OssException;

class AssetController extends PluginBaseController
{

    function getUrl()
    {

        $oss = new OSS([]);

        $file     = $this->request->param('file');
        $fileType = $this->request->param('filetype');

        $previewUrl = $fileType == 'image' ? $oss->getPreviewUrl($file) : $oss->getFileDownloadUrl($file);
        $url        = $fileType == 'image' ? $oss->getImageUrl($file, 'watermark') : $oss->getFileDownloadUrl($file);

        return $this->success('success', null, ['url' => $url, 'preview_url' => $previewUrl]);
    }

    public function saveFile()
    {
        $userId = cmf_get_current_admin_id();
        $userId = $userId ? $userId : cmf_get_current_user_id();

        if (empty($userId)) {
            $this->error('error');
        }
        $validate = new Validate([
            'filename' => 'require',
            'file_key' => 'require',
        ]);

        $data = $this->request->param();

        $result = $validate->check($data);

        if ($result !== true) {
            $this->error($validate);
        }

        $suffix = cmf_get_file_extension($data['filename']);

        $config = $this->getPlugin()->getConfig();

        $accessKey = $config['accessKey'];
        $secretKey = $config['secretKey'];


        $fileInfo = '';

        $findAsset = Db::name('asset')->where('file_key', $fileInfo['hash'])->find();


        if (empty($findAsset)) {

            Db::name('asset')->insert([
                'user_id'     => $userId,
                'file_size'   => $fileInfo['fsize'],
                'filename'    => $data['filename'],
                'create_time' => time(),
                'file_key'    => $fileInfo['hash'],
                'file_path'   => $fileInfo['hash'],
                'suffix'      => $suffix
            ]);
        }

        $this->success('success');

    }

}
