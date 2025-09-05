<?php

namespace app\common\services;

use api\upload\Upload;

class UploadService
{
    /**
     * 初始化
     *
     * @param string $dir 上传目录
     * @param string $driver 上传驱动
     * @return Upload
     */
    public static function init(int $companyId = 0, string $dir = '', string $driver = ''): Upload
    {
        $config = web_config($companyId, 'upload');
        $upload = app()->make(Upload::class, [$dir, $driver]);
        $upload->setConfig($config);
        $upload->setDriver($driver ?: ($config['upload_type'] ?? 'local'));

        return $upload;
    }
}