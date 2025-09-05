<?php

namespace app\common\repositories\pool;

use app\common\dao\pool\PoolModeDao;
use app\common\repositories\BaseRepository;
use app\common\repositories\system\upload\UploadFileRepository;

/**
 * Class PoolModeRepository
 * @package app\common\repositories\pool
 * @mixin PoolModeDao
 */
class PoolModeRepository extends BaseRepository
{

    public function __construct(PoolModeDao $dao)
    {
        $this->dao = $dao;
    }

    public function getDetail(array $where)
    {
        $data = $this->dao->search($where)->find();
        return $data;
    }

    public function recently($companyId = null){
       return  $this->dao->search([],$companyId)->whereNotNull('back_id')->whereNotNull('table_id')
           ->order('id desc')->with(['back'=>function($query){
               $query->bind(['picture'=>'show_src']);
           },'tableImg'=>function($query){
               $query->bind(['picture1'=>'show_src']);
           }])
           ->find();
    }


    public function addInfo($companyId,$data)
    {
        /** @var UploadFileRepository $uploadFileRepository */
        $uploadFileRepository = app()->make(UploadFileRepository::class);
        if($data['img']){
            $data['file_id'] = $uploadFileRepository->getFileData($data['img'], 1,0)['id'];
        }
        if($data['back_img']){
            $data['back_id'] = $uploadFileRepository->getFileData($data['back_img'], 1,0)['id'];
        }
        if($data['table_img']){
            $data['table_id'] = $uploadFileRepository->getFileData($data['table_img'], 1,0)['id'];
        }
        return $this->dao->create($data);
    }

    public function editInfo($info, $data)
    {
        /** @var UploadFileRepository $uploadFileRepository */
        $uploadFileRepository = app()->make(UploadFileRepository::class);
        if($data['img']){
            $data['file_id'] = $uploadFileRepository->getFileData($data['img'], 1,0)['id'];
        }
        if($data['back_img']){
            $data['back_id'] = $uploadFileRepository->getFileData($data['back_img'], 1,0)['id'];
        }

        if($data['table_img']){
            $data['table_id'] = $uploadFileRepository->getFileData($data['table_img'], 1,0)['id'];
        }
        unset($data['img']);
        unset($data['back_img']);
        unset($data['table_img']);
        $this->dao->update($info['id'], $data);
        return true;
    }



}