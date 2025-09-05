<?php

namespace app\common\repositories\system\upload;

use app\common\dao\system\upload\UploadFileDao;
use app\common\repositories\BaseRepository;
use app\common\services\UploadService;

/**
 * Class UploadFileRepository
 *
 * @package app\common\repositories\system\upload
 * @mixin UploadFileDao
 */
class UploadFileRepository extends BaseRepository
{
    // 存储类型
    const STORAGE_TYPE = [
        'local' => '本地',
        'aliyun' => '阿里云OSS',
        'qiniu' => '七牛云存储',
        'qcloud' => '腾讯云COS'
    ];

    public function __construct(UploadFileDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * @param array $where
     * @param $page
     * @param $limit
     * @return array
     */
    public function getList(array $where = [], $filed = '*', $page = 1, $limit = 15, $companyId = 0)
    {
        $query = $this->dao->search($where,$companyId);
        $count = $query->count();
        $list = $query->page($page, $limit)
            ->field($filed)
            ->order('id', 'desc')
            ->select();
        return compact('list', 'count');
    }

    public function getFileData($file,$trackType = 1, int $id = 0)
    {
        switch ($trackType){
            case '1':
                $fileInfo = $this->dao->getSearch(['show_src'=>$file])->find();
                break;
            case '2':
                $fileInfo = $this->dao->getSearch(['show_src'=>$file,'company_id'=>$id])->find();
                break;
            case '4':
                $fileInfo = $this->dao->getSearch(['show_src'=>$file,'uuid'=>$id])->find();
                break;
        }
        return $fileInfo;

    }
}