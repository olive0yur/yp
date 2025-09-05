<?php

namespace app\common\repositories\article\news;

use app\common\repositories\system\upload\UploadFileRepository;
use think\facade\Db;
use app\common\repositories\BaseRepository;
use app\common\dao\article\news\NewsDao;

/**
 * Class NewsRepository
 * @package app\common\repositories\article\news
 * @mixin NewsDao
 */
class NewsRepository extends BaseRepository
{

    public function __construct(NewsDao $dao)
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
            ->field('id,cate_id,title,keywords,picture_id,desc,content,is_show,is_top,sort,click,add_time')
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
                if($fileInfo){
                    $data['picture_id'] = $fileInfo['id'];
                }
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

}