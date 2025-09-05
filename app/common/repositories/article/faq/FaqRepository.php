<?php

namespace app\common\repositories\article\faq;

use think\facade\Db;
use app\common\repositories\BaseRepository;
use app\common\dao\article\faq\FaqDao;
use app\common\repositories\system\upload\UploadFileRepository;

/**
 * Class FaqRepository
 * @package app\common\repositories\article\news
 * @mixin FaqDao
 */
class FaqRepository extends BaseRepository
{

    public function __construct(FaqDao $dao)
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
            ->field('id,cate_id,title,picture_id,content,is_show,sort,is_top,click,sort,add_time')
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

    public function editInfo($id, array $data,$info)
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

    public function getApiList(array $where, $page, $limit, $companyId = null)
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
            ->field('id,cate_id,title,picture_id,content,is_show,is_top,click,add_time')
            ->order('id', 'desc')
            ->select();
        return compact('count', 'list');
    }


}