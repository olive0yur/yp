<?php

namespace app\common\repositories\union;

use app\common\dao\union\UnionAlbumDao;
use app\common\repositories\BaseRepository;
use app\common\repositories\system\upload\UploadFileRepository;

/**
 * Class UnionAlbumRepository
 * @package app\common\repositories\pool
 * @mixin UnionAlbumDao
 */
class UnionAlbumRepository extends BaseRepository
{

    public function __construct(UnionAlbumDao $dao)
    {
        $this->dao = $dao;
    }

    public function getList(array $where, $page, $limit, $companyId = null)
    {
        $query = $this->dao->search($where, $companyId);
        $count = $query->count();
        $list = $query->page($page, $limit)
            ->with([
                'file' => function ($query) {
                    $query->bind(['picture' => 'show_src']);
                },
                'headInfo' => function ($query) {
                    $query->bind(['head_img' => 'show_src']);
                }
            ])
            ->order('sort asc')
            ->select();
        return compact('count', 'list');
    }




    public function getApiList(array $where, $page, $limit, $companyId = null)
    {
        $query = $this->dao->search($where, $companyId);
        $count = $query->count();
        $list = $query->page($page, $limit)
            ->with([
                'file' => function ($query) {
                    $query->bind(['picture' => 'show_src']);
                },
                'headInfo' => function ($query) {
                    $query->bind(['head_img' => 'show_src']);
                }
            ])
            ->field('id,name,file_id,content,head_file_id')
            ->hidden(['file_id'])
            ->order('sort asc')
            ->select();

        return compact('count', 'list');
    }

    public function getApiDetail(int $id)
    {
        $data = $this->dao->search([])
            ->with([
                'file' => function ($query) {
                    $query->bind(['cover' => 'show_src']);
                },
                'headInfo' => function ($query) {
                    $query->bind(['head_img' => 'show_src']);
                }
            ])
            ->where('id', $id)
            ->field('id,name,file_id,content,head_file_id')
            ->find();

        return $data;
    }



    public function addInfo($companyId,$data)
    {
        if($data['cover']){
            /** @var UploadFileRepository $uploadFileRepository */
            $uploadFileRepository = app()->make(UploadFileRepository::class);
            $fileInfo = $uploadFileRepository->getFileData($data['cover'], 1,0);
            if($fileInfo){
                $data['file_id'] = $fileInfo['id'];
            }
        }
        if($data['head_img']){
            /** @var UploadFileRepository $uploadFileRepository */
            $uploadFileRepository = app()->make(UploadFileRepository::class);
            $fileHeadInfo = $uploadFileRepository->getFileData($data['head_img'], 1,0);
            if($fileHeadInfo){
                $data['head_file_id'] = $fileHeadInfo['id'];
            }
        }
        unset($data['head_img']);
        unset($data['cover']);
        $data['company_id'] = $companyId;
        return $this->dao->create($data);
    }

    public function editInfo($info, $data)
    {
        if($data['cover']){
            /** @var UploadFileRepository $uploadFileRepository */
            $uploadFileRepository = app()->make(UploadFileRepository::class);
            $fileInfo = $uploadFileRepository->getFileData($data['cover'], 1,0);
            if($fileInfo){
                if($fileInfo['id'] != $info['file_id']){
                    $data['file_id'] = $fileInfo['id'];
                }
            }
        }
        if($data['head_img']){
            /** @var UploadFileRepository $uploadFileRepository */
            $uploadFileRepository = app()->make(UploadFileRepository::class);
            $fileHeadInfo = $uploadFileRepository->getFileData($data['head_img'], 1,0);
            if($fileHeadInfo){
                if($fileHeadInfo['id'] != $info['head_file_id']){
                    $data['head_file_id'] = $fileHeadInfo['id'];
                }
            }
        }
        unset($data['cover']);
        unset($data['head_img']);
        return $this->dao->update($info['id'], $data);
    }

    public function getDetail(int $id)
    {
        $data = $this->dao->search([])
            ->with([
                'file' => function ($query) {
                    $query->bind(['cover' => 'show_src']);
                },
                'headInfo' => function ($query) {
                    $query->bind(['head_img' => 'show_src']);
                }

            ])
            ->hidden(['file'])
            ->where('id', $id)
            ->find();

        return $data;
    }

    /**
     * 删除
     */
    public function batchDelete(array $ids)
    {
        $list = $this->dao->selectWhere([
            ['id', 'in', $ids]
        ]);

        if ($list) {
            foreach ($list as $k => $v) {
                $this->dao->delete($v['id']);
            }
            return $list;
        }
        return [];
    }
    public function getAlbumData($isType=1,$companyId = null){
        $list = $this->dao->search(['is_type'=>$isType],$companyId)->column('name','id');
        return formatCascaderData($list,'name');
    }



}