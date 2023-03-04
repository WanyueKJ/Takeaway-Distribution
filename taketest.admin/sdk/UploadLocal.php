<?php
// 根据框架上传 改为只 本地上传
namespace cmf\lib;

use think\facade\Env;
use think\File;
use app\user\model\AssetModel;
use think\Db;

/**
 * ThinkCMF上传类,分块上传
 */
class UploadLocal
{
    private $request;
    private $error = false;
    private $fileType;
    private $formName = 'file';

    public function __construct()
    {
        $this->request = request();
    }

    public function getError()
    {
        return $this->error;
    }

    public function setFileType($fileType)
    {
        $this->fileType = $fileType;
    }

    public function setFormName($name)
    {
        $this->formName = $name;
    }

    public function upload()
    {
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

        $fileType = $this->fileType;
        if (empty($this->fileType)) {
            $fileType = $arrData["filetype"];
        }

        if (array_key_exists($arrData["filetype"], $arrFileTypes)) {
            $extensions                = $uploadSetting['file_types'][$arrData["filetype"]]['extensions'];
            $fileTypeUploadMaxFileSize = $uploadSetting['file_types'][$fileType]['upload_max_filesize'];
        } else {
            $this->error = '上传文件类型配置错误！';
            return false;
        }

        @set_time_limit(24 * 60 * 60);
        $cleanupTargetDir = false; // Remove old files
        $maxFileAge       = 5 * 3600; // Temp file age in seconds

        /**
         * 断点续传 end
         */

        $app = $this->request->param('app');
        if (empty($app) || !file_exists(APP_PATH . $app)) {
            $app = 'default';
        }

        $fileImage    = $this->request->file($this->formName);
        $originalName = $fileImage->getInfo('name');

        $arrAllowedExtensions = explode(',', $arrFileTypes[$fileType]['extensions']);

        $strFileExtension = strtolower(cmf_get_file_extension($originalName));

        if (!in_array($strFileExtension, $arrAllowedExtensions) || $strFileExtension == 'php') {
            $this->error = "非法文件类型！";
            return false;
        }

        $fileUploadMaxFileSize = $uploadSetting['upload_max_filesize'][$strFileExtension];
        $fileUploadMaxFileSize = empty($fileUploadMaxFileSize) ? 2097152 : $fileUploadMaxFileSize;//默认2M

        $strWebPath = "";//"upload" . DS;
        $strId      = $this->request->param("id");
        $strDate    = date('Ymd');

        $adminId = cmf_get_current_admin_id();
        $userId  = cmf_get_current_user_id();
        /* LV 讲师ID'*/
        $teacherid  = get_current_teacher_id();
        /* LV */
        $userId  = empty($adminId) ? $userId : $adminId;
        /* LV */
        $userId  = empty($userId) ? $teacherid : $userId;
        /* LV */
        if (empty($userId)) {
            $userId = Db::name('user_token')->where('token', $this->request->header('XX-Token'))->field('user_id,token')->value('user_id');
        }
        $targetDir = Env::get('runtime_path') . "upload" . DIRECTORY_SEPARATOR . $userId . DIRECTORY_SEPARATOR; // 断点续传 need
        if (!file_exists($targetDir)) {
            mkdir($targetDir, 0777, true);
        }


        /**
         * 断点续传 need
         */
        $strFilePath = md5($originalName);
        $chunk       = $this->request->param("chunk", 0, "intval");// isset($_REQUEST["chunk"]) ? intval($_REQUEST["chunk"]) : 0;
        $chunks      = $this->request->param("chunks", 1, "intval");//isset($_REQUEST["chunks"]) ? intval($_REQUEST["chunks"]) : 1;

        if (!$fileImage->isValid()) {
            $this->error = "非法文件！";
            return false;
        }

        if ($cleanupTargetDir) {
            if (!is_dir($targetDir) || !$dir = opendir($targetDir)) {
                $this->error = "Failed to open temp directory！";
                return false;
            }

            while (($file = readdir($dir)) !== false) {
                $tmpFilePath = $targetDir . $file;
                if ($tmpFilePath == "{$strFilePath}_{$chunk}.part" || $tmpFilePath == "{$strFilePath}_{$chunk}.parttmp") {
                    continue;
                }
                if (preg_match('/\.(part|parttmp)$/', $file) && (@filemtime($tmpFilePath) < time() - $maxFileAge)) {
                    @unlink($tmpFilePath);
                }
            }
            closedir($dir);
        }

        // Open temp file
        if (!$out = @fopen($targetDir . "{$strFilePath}_{$chunk}.parttmp", "wb")) {
            $this->error = "上传文件临时目录不可写" . $targetDir;
            return false;
        }
        // Read binary input stream and append it to temp file
        if (!$in = @fopen($fileImage->getInfo("tmp_name"), "rb")) {
            $this->error = "Failed to open input stream！";
            return false;
        }

        while ($buff = fread($in, 4096)) {
            fwrite($out, $buff);
        }

        @fclose($out);
        @fclose($in);

        rename($targetDir . "{$strFilePath}_{$chunk}.parttmp", $targetDir . "{$strFilePath}_{$chunk}.part");

        $uploadPath = WEB_ROOT . 'upload/';

        $fileSaveName    = (empty($app) ? '' : $app . '/') . $strDate . '/' . md5(uniqid()) . "." . $strFileExtension;
        $strSaveFilePath = $uploadPath . $fileSaveName; //TODO 测试 windows 下
        $strSaveFileDir  = dirname($strSaveFilePath);
        if (!file_exists($strSaveFileDir)) {
            mkdir($strSaveFileDir, 0777, true);
        }

        // 合并临时文件
        if (!$out = @fopen($strSaveFilePath, "wb")) {
            $this->error = "上传目录不可写";
            return false;
        }

        if (flock($out, LOCK_EX)) {
            for ($index = 0; $index < $chunks; $index++) {
                if (!$in = @fopen($targetDir . "{$strFilePath}_{$index}.part", "rb")) {
                    break;
                }

                while ($buff = fread($in, 4096)) {
                    fwrite($out, $buff);
                }

                fclose($in);
                unlink("{$targetDir}{$strFilePath}_{$index}.part");
            }
            flock($out, LOCK_UN);
        }
        @fclose($out);

        $fileImage = new File($strSaveFilePath, 'r');
        $arrInfo   = [
            "name"     => $originalName,
            "type"     => $fileImage->getMime(),
            "tmp_name" => $strSaveFilePath,
            "error"    => 0,
            "size"     => $fileImage->getSize(),
        ];

        $fileImage->setSaveName($fileSaveName);
        $fileImage->setUploadInfo($arrInfo);

        /**
         * 断点续传 end
         */

        if (!$fileImage->validate(['size' => $fileUploadMaxFileSize])->check()) {
            $error = $fileImage->getError();
            unset($fileImage);
            unlink($strSaveFilePath);
            $this->error = $error;
            return false;
        }

        $arrInfo = [];

        if (empty($fileImage)) {
            $this->error = $fileImage->getError();
            return false;
        } else {
            $arrInfo["user_id"]     = $userId;
            $arrInfo["file_size"]   = $fileImage->getSize();
            $arrInfo["create_time"] = time();
            $arrInfo["file_md5"]    = md5_file($strSaveFilePath);
            $arrInfo["file_sha1"]   = sha1_file($strSaveFilePath);
            $arrInfo["file_key"]    = $arrInfo["file_md5"] . md5($arrInfo["file_sha1"]);
            $arrInfo["filename"]    = $fileImage->getInfo("name");
            $arrInfo["file_path"]   = $strWebPath . $fileSaveName;
            $arrInfo["suffix"]      = $fileImage->getExtension();
        }

        //关闭文件对象
        $fileImage = null;

        //删除临时文件
//        for ($index = 0; $index < $chunks; $index++) {
//            // echo $targetDir . "{$strFilePath}_{$index}.part";
//            @unlink($targetDir . "{$strFilePath}_{$index}.part");
//        }
        @rmdir($targetDir);

        return [
            'filepath'    => $arrInfo["file_path"],
            "name"        => $arrInfo["filename"],
            'id'          => $strId,
            'preview_url' => cmf_get_root() . '/upload/' . $arrInfo["file_path"],
            'url'         => cmf_get_root() . '/upload/' . $arrInfo["file_path"],
        ];
    }

}
