<?php

namespace api\upload;

use api\upload\exception\UploadException;
use Exception;
use think\exception\ValidateException;
use think\facade\Request;

class Upload
{
    public $uploadDir = '';
    private $driver;
    private $drivers;
    //上传大小限制 单位(字节)
    public $uploadSize = [
        'image' => 314572800000000, // 图片
        //'image' => 2048, // 图片
        'video' => 524288000000000 // 视频
    ];

    public $uploadConfig;

    /**
     * Upload constructor.
     *
     * @param string $dir 目录
     */
    public function __construct($dir = 'home', $driver = '')
    {
        $this->init($driver, $dir);
    }

    public function setConfig($config)
    {
        $this->uploadConfig = $config;
    }

    /**
     * 初始化上传
     *
     * @param string $driver 驱动名称
     * @param string $dir 上传目录
     */
    public function init($driver = '', $dir = '')
    {
        $this->uploadDir = $dir . '/' . date('Ymd') . '/';

        $this->setDriver($driver);
    }

    /**
     * 设置驱动
     *
     * @param string $driver 驱动名称
     * @return void
     */
    public function setDriver($driver)
    {
        if (!isset($this->drivers[$driver])) {
            if ($driver == '') {
                $defaultDriver = $this->uploadConfig['upload_type'] ?? 'local';
            } else {
                $defaultDriver = $driver;
            }
            $defaultDriver = ucfirst($defaultDriver);

            $class = '\\api\\upload\\driver\\' . $defaultDriver;
            $this->drivers[$driver] = new $class($this->uploadConfig, $this->uploadDir);
        }
        $this->driver = $this->drivers[$driver];
    }

    /**
     * 根据文件上传文件
     *
     * @param string $field 上传的图片字段
     * @return array
     * @throws Exception
     */
    public function uploadImageFile($field)
    {
        $error = error_get_last();
        if (!empty($error)) {
            throw new UploadException($error['message']);
        }
        $files = Request::file();
        if (empty($files)) {
            throw new UploadException('请上传图片');
        }

        try {
            validate([
                $field => 'fileSize:' . $this->uploadSize['image'] . '|fileExt:' . ($this->uploadConfig['upload_allow_ext'] ?? '')
            ], [
                $field . '.fileSize' => '图片过大',
                $field . '.fileExt' => '图片后缀不允许'
            ])->check($files);
        } catch (ValidateException $e) {
            throw new UploadException($e->getError());
        }
        $uploadFileInfo = $files[$field] ?? [];
        if (empty($uploadFileInfo)) {
            throw new UploadException('请选择要上传的文件');
        }
        $fileMd5 = md5_file($uploadFileInfo->getRealPath());
        $fileSize = filesize($uploadFileInfo->getRealPath());
        if ($this->checkHex($uploadFileInfo->getRealPath()) === false) {
            throw new UploadException('上传非法图片');
        }

        $res = $this->driver->uploadImageFile($uploadFileInfo);
     
        return [
            'code' => 1,
            'msg' => '上传成功',
            'data' => [
                'tempPath' => $uploadFileInfo->getRealPath(),
                'src' =>$res['showPath'],
                'fileInfo' => [
                    'file_size' => $fileSize,
                    'file_md5' => $fileMd5,
                    'storage_type' => $this->uploadConfig['upload_type'] ?? 'local',
                    'show_src' => $res['showPath'],
                    'upload_path' =>$res['uploadPath'],
                    'file_type' => $uploadFileInfo->getMime(),
                    'file_name' => $uploadFileInfo->getOriginalName()
                ]
            ]
        ];
    }

    /**
     * 根据文件上传文件
     *
     * @param string $field 上传的文件字段
     * @return array
     * @throws Exception
     */
    public function uploadFile($field)
    {
        $error = error_get_last();
        if (!empty($error)) {
            throw new UploadException($error['message']);
        }
        $files = Request::file();
        if (empty($files)) {
            throw new UploadException('请上传图片');
        }

        try {
            validate([
                $field => 'fileSize:10240000|fileExt:pdf,doc,xls,xlsx'
            ], [
                $field . '.fileSize' => '文件过大',
                $field . '.fileExt' => '文件后缀不允许'
            ])->check($files);
        } catch (ValidateException $e) {
            throw new UploadException($e->getError());
        }
        $uploadFileInfo = $files[$field] ?? [];
        if (empty($uploadFileInfo)) {
            throw new UploadException('请选择要上传的文件');
        }
        $fileMd5 = md5_file($uploadFileInfo->getRealPath());
        $fileSize = filesize($uploadFileInfo->getRealPath());

        $res = $this->driver->uploadFile($uploadFileInfo);
        return [
            'code' => 1,
            'msg' => '上传成功',
            'data' => [
                'src' => $res['showPath'],
                'fileInfo' => [
                    'file_size' => $fileSize,
                    'file_md5' => $fileMd5,
                    'storage_type' => $this->uploadConfig['upload_type'] ?? 'local',
                    'show_src' => $res['showPath'],
                    'upload_path' => $res['uploadPath'],
                    'file_type' => $uploadFileInfo->getMime(),
                    'file_name' => $uploadFileInfo->getOriginalName()
                ]
            ]
        ];
    }

    /**
     * 上传文件至本地
     *
     * @return array
     * @throws Exception
     */
    public function uploadFielToLocal()
    {
        $error = error_get_last();
        if (!empty($error)) {
            throw new UploadException($error['message']);
        }
        $files = Request::file();
        $filePaths = [];
        if ($files) {
            $dir = config('filesystem.disks.local.root');
            foreach ($files as $k => $file) {
                $filePaths[$k] = $dir . '/' . \think\facade\Filesystem::disk('local')->putFileAs('alipay_cert', $file, $file->getOriginalName());
            }
        }

        return $filePaths;
    }

    /**
     * 上传本地文件
     *
     * @param string $localFilePath 本地文件地址
     * @return array
     * @throws Exception
     */
    public function uploadLocalFile($localFilePath)
    {
        if (empty($localFilePath)) {
            throw new UploadException('文件不存在');
        }
        if (!file_exists($localFilePath)) {
            throw new UploadException('文件不存在');
        }

        $res = $this->driver->uploadLocalFile($localFilePath);
        return [
            'code' => 1,
            'msg' => '上传成功',
            'data' => [
                'src' => $res['showPath']
            ]
        ];
    }

    /**
     * 16进制检测
     *
     * @param string $fileSrc 文件地址
     * @return bool
     */
    private function checkHex($fileSrc)
    {
        $resource = fopen($fileSrc, 'rb');
        $fileSize = filesize($fileSrc);
        fseek($resource, 0);
        //把文件指针移到文件的开头
        if ($fileSize > 512) { // 若文件大于521B文件取头和尾
            $hexCode = bin2hex(fread($resource, 512));
            fseek($resource, $fileSize - 512);
            //把文件指针移到文件尾部
            $hexCode .= bin2hex(fread($resource, 512));
        } else { // 取全部
            $hexCode = bin2hex(fread($resource, $fileSize));
        }
        fclose($resource);
        /* 匹配16进制中的 <% ( ) %> */
        /* 匹配16进制中的 <? ( ) ?> */
        /* 匹配16进制中的 <script | /script> 大小写亦可 */
        /* eval|exec|write|put|system|passthru|fputs|fwrite */

        /* 核心  整个类检测木马脚本的核心在这里  通过匹配十六进制代码检测是否存在木马脚本 */

        if (preg_match("/(3c25.*?28.*?29.*?253e)|(3c3f.*?28.*?29.*?3f3e)|(3C534352495054)|(2F5343524950543E)|(3C736372697074)|(2F7363726970743E)|(6576616c)|(65786563)|(7772697465)|(707574)|(73797374656d)|(061737374687275)|(6670757473)|(667772697465)/is", $hexCode))
            return false;
        return true;
    }

    /**
     * 删除文件
     *
     * @param string $filePath 文件地址
     * @return void
     */
    public function deleteFile($filePath)
    {
        return $this->driver->deleteFile($filePath);
    }
}
