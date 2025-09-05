<?php

namespace api\upload\driver;

use api\upload\exception\UploadException;
use think\facade\Db;
use think\File;

class Local
{
    public $uploadDir = '';
    public $uploadConfig;

    /**
     * @param array $uploadConfig 上传配置
     * @param string $dir 保存目录
     */
    public function __construct($uploadConfig, $dir = '')
    {
        $this->uploadConfig = $uploadConfig;
        $this->uploadDir = $dir;
    }


    /**
     * 获取域名
     *
     * @return void
     */
    public function getDomain()
    {
        return config('filesystem.disks.public.url');
    }

    /**
     * 上传图片文件
     *
     * @param object $file 文件信息
     * @return void
     */
    public function uploadImageFile($file)
    {
        try {
            $fileName = \think\facade\Filesystem::disk('public')->putFile(rtrim($this->uploadDir, '/'), $file, 'sha1');
            $saveName = str_replace('\\', '/', $fileName);
            return [
                'uploadPath' => ltrim('/'.$this->getDomain() . '/' . $saveName, '/'),
                'showPath' => env('local_url').$this->getDomain() . '/' . $saveName
            ];
        } catch (\think\exception\ValidateException $e) {
            throw new UploadException($e->getError());
        }
    }

    /**
     * 上传文件
     *
     * @param object $file 文件信息
     * @return void
     */
    public function uploadFile($file)
    {
        try {
            $fileName = \think\facade\Filesystem::disk('public')->putFile(rtrim($this->uploadDir, '/'), $file, 'sha1');
            $saveName = str_replace('\\', '/', $fileName);

            return [
                'uploadPath' => ltrim($this->getDomain() . '/' . $saveName, '/'),
                'showPath' => $this->getDomain() . '/' . $saveName
            ];
        } catch (\think\exception\ValidateException $e) {
            throw new UploadException($e->getError());
        }
    }

    /**
     * 上传本地文件
     *
     * @param string $localFilePath 本地文件地址
     * @return array
     * @throws UploadException
     */
    public function uploadLocalFile($localFilePath)
    {
        try {
            $file = new File($localFilePath);
            $fileName = \think\facade\Filesystem::disk('public')->putFile(rtrim($this->uploadDir, '/'), $file, 'sha1');

            $saveName = str_replace('\\', '/', $fileName);

            return [
                'uploadPath' => ltrim($this->getDomain() . '/' . $saveName, '/'),
                'showPath' => $this->getDomain() . '/' . $saveName
            ];
        } catch (\think\exception\ValidateException $e) {
            throw new UploadException($e->getError());
        }
    }

    /**
     * 删除文件
     *
     * @param string $filePath 文件地址
     * @return bool
     */
    public function deleteFile($filePath)
    {
        if (!file_exists($filePath)) {
            return true;
        }
        try {
            return unlink($filePath);
        } catch (\Exception $e) {
            exception_log('本地上传文件删除失败', $e, false);
            return false;
        }
    }
}
