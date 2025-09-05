<?php

namespace app\common\repositories\article\guide;

use think\facade\Db;
use app\common\repositories\BaseRepository;
use app\common\dao\article\guide\OperateDao;
use app\common\repositories\system\upload\UploadFileRepository;

/**
 * Class OperateRepository
 * @package app\common\repositories\article\news
 * @mixin OperateDao
 */
class OperateRepository extends BaseRepository
{

    public function __construct(OperateDao $dao)
    {
        $this->dao = $dao;
    }

    public function getList(array $where, $page, $limit, $companyId = null)
    {
        $query = $this->dao->search($where, $companyId);
        $count = $query->count();
        $list = $query->page($page, $limit)
            ->with([
                'cateInfo' => function ($query) {
                    $query->bind(['name']);
                },
                'file' => function ($query) {
                    $query->bind(['picture' => 'show_src']);
                }
            ])
            ->order('sort asc')
            ->select();
        return compact('count', 'list');
    }

    public function getSellList(array $where, $page, $limit, $companyId = null)
    {
        $cate = app()->make(OperateCateRepository::class);
        $care_id = $cate->search([])->where(['is_tips'=>1])->value('id');
        $query = $this->dao->search($where, $companyId);
        $count = $query->count();
        $list = $query->page($page, $limit)
            ->where('cate_id',$care_id)
            ->with([
                'cateInfo' => function ($query) {
                    $query->bind(['name']);
                },
                'file' => function ($query) {
                    $query->bind(['picture' => 'show_src']);
                }
            ])
            ->append(['content'])
            ->order('sort asc')
            ->select();
        return compact('count', 'list');
    }

    public function addInfo($companyId, $data)
    {
        if($data['picture']){
            /** @var UploadFileRepository $uploadFileRepository */
            $uploadFileRepository = app()->make(UploadFileRepository::class);
            $fileInfo = $uploadFileRepository->getFileData($data['picture']);
            if($fileInfo){
                $data['picture_id'] = $fileInfo['id'];
            }
        }

        unset($data['picture']);
        $data['company_id'] = $companyId;
        return $this->dao->create($data);
    }

    public function editInfo($id, array $data, $info)
    {
        if($data['picture']){
            /** @var UploadFileRepository $uploadFileRepository */
            $uploadFileRepository = app()->make(UploadFileRepository::class);
            $fileInfo = $uploadFileRepository->getFileData($data['picture']);
            if($fileInfo){
                if($fileInfo['id'] != $info['picture_id']){
                    $data['picture_id'] = $fileInfo['id'];
                }
            }
        }
        unset($data['picture']);
        return $this->dao->update($id,$data);
    }


    public function getDetail(int $id)
    {
        $data = $this->dao->search([])
            ->with([
                'file' => function ($query) {
                    $query->bind(['picture' => 'show_src']);
                }
            ])
            ->hidden(['file'])
            ->where('id', $id)
            ->find();
        return $data;
    }

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

}