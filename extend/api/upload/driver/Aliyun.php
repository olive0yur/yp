<?php

namespace api\upload\driver;

use api\upload\exception\UploadException;
use OSS\OssClient;
use think\facade\Db;

class Aliyun
{
    public $uploadDir = '';
    public $ossClient;
    private $config;

    /**
     * @param array $uploadConfig 上传配置
     * @param string $dir 上传目录
     * @throws UploadException
     * @throws \OSS\Core\OssException
     */
    public function __construct($uploadConfig, $dir = '')
    {
        $this->uploadDir = $dir;
        $this->config = $uploadConfig['aliyun'] ?? [];
        if (empty($this->config)) {
            throw new UploadException('参数未配置');
        }
        if (!empty($this->config['access_key_id']) && !empty($this->config['access_key_secret']) && !empty($this->config['endpoint'])) {
            $this->ossClient = new OssCLient($this->config['access_key_id'], $this->config['access_key_secret'], $this->config['endpoint']);
        }
    }

    /**
     * 生成文件名称
     *
     * @return void
     */
    public function generateFileName($file)
    {
        return uuid() . '.' . pathinfo($file->getOriginalName(), PATHINFO_EXTENSION);
    }

    /**
     * 获取域名
     *
     * @return void
     */
    public function getDomain()
    {
        $domain = $this->config['domain'];
        if (!strstr($domain, 'http')) {
            $domain = 'http://' . $domain;
        }


        $domain = rtrim($domain, '/') . '/';

        return $domain;
    }

    /**
     * 上传图片文件
     *
     * @param object $uploadFileInfo 文件信息
     * @return void
     */
    public function uploadImageFile($uploadFileInfo)
    {

        $fileName = $this->uploadDir . $this->generateFileName($uploadFileInfo);

        $res = $this->uploadToOss($fileName, $uploadFileInfo->getRealPath(), $uploadFileInfo->getMime());

        return [
            'uploadPath' => $res,
            'showPath' => $this->getDomain() . $res
        ];
    }

    /**
     * 上传文件
     *
     * @param object $uploadFileInfo 文件信息
     * @return void
     */
    public function uploadFile($uploadFileInfo)
    {

        $fileName = $this->uploadDir . $this->generateFileName($uploadFileInfo);

        $res = $this->uploadToOss($fileName, $uploadFileInfo->getRealPath(), $uploadFileInfo->getMime());

        return [
            'uploadPath' => $res,
            'showPath' => $this->getDomain() . $res
        ];
    }

    /**
     * 上传本地文件
     *
     * @param string $localFilePath 本地文件地址
     * @return array
     * @throws \Exception
     */
    public function uploadLocalFile($localFilePath)
    {

        $fileName = $this->uploadDir . $this->generateFileName([
                'name' => basename($localFilePath)
            ]);

        $res = $this->uploadToOss($fileName, $localFilePath);

        return [
            'uploadPath' => $res,
            'showPath' => $this->getDomain() . $res
        ];
    }

    /**
     * 上传至oss
     *
     * @param string $fileName oss文件名
     * @param string $localFileSrc 本地文件路径
     * @param string $fileType 文件类型
     * @return string oss路径
     * @throws \Exception
     */
    public function uploadToOss($fileName, $localFileSrc, $fileType = '')
    {
        try {
            $this->publicUploadToPublic($fileName, $localFileSrc, [
//                'ContentType' => $fileType ? $fileType : mime_content_type($localFileSrc)
                'ContentType' => $fileType ? $fileType : (new \finfo(FILEINFO_MIME_TYPE))->file($localFileSrc)
            ]);
            return $fileName;
        } catch (\Exception $e) {
            exception_log('阿里云oss上传失败', $e);
            throw new UploadException('上传失败');
        }
    }

    /**
     * 上传至公共oss
     *
     * @param string $fileName 文件名称
     * @param string $filePath 本地文件路径
     * @param array $options
     * @return null
     * @throws \OSS\Core\OssException
     */
    public function publicUploadToPublic($fileName, $filePath, $options = [])
    {
        $bucket = $this->config['bucket'];
        $resource = fopen($filePath, 'r+');
        if (!is_resource($resource)) {
            throw new UploadException('文件打开失败');
        }
        $i = 0;
        $bufferSize = 1000000; // 1M
        while (!feof($resource)) {
            if (false === $buffer = fread($resource, $block = $bufferSize)) {
                return false;
            }
            $position = $i * $bufferSize;
            $size = $this->ossClient->appendObject($bucket, $fileName, $buffer, $position, $options);
            $i++;
        }
        fclose($resource);
//        return $oss->ossClient->uploadFile($bucket, $fileName, $filePath, $options);
    }

    /**
     * 删除文件
     *
     * @param string $filePath 文件地址
     * @return void
     */
    public function deleteFile($filePath)
    {
        try {
            $bucket = $this->config['bucket'];
            $this->ossClient->deleteObject($bucket, $filePath);

            return true;
        } catch (\Exception $e) {
            exception_log('阿里云oss删除文件失败', $e);
            return false;
        }
    }
}
