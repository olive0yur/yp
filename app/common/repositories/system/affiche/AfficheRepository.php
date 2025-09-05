<?php

namespace app\common\repositories\system\affiche;

use app\common\dao\system\affiche\AfficheDao;
use app\common\repositories\BaseRepository;
use app\common\repositories\system\upload\UploadFileRepository;
use think\facade\Cache;
use think\facade\Db;

class AfficheRepository extends BaseRepository
{
    public function __construct(AfficheDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 获取列表
     */
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
            ->field('id,cate_id,title,content,is_show,add_time,start_time,is_top,sort,file_id')
            ->order('sort desc')
            ->select();
        return compact('list', 'count');
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


    public function addInfo($companyId,$data)
    {
        if($data['picture']){
            /** @var UploadFileRepository $uploadFileRepository */
            $uploadFileRepository = app()->make(UploadFileRepository::class);
            $fileInfo = $uploadFileRepository->getFileData($data['picture']);
            if($fileInfo){
                $data['file_id'] = $fileInfo['id'];
            }
        }
        unset($data['picture']);
        $data['company_id'] = $companyId;
        $data['edit_time'] = date('Y-m-d H:i:s');
        return $this->dao->create($data);
    }


    public function editInfo($id,array $data,$info)
    {
        if($data['picture']){
            /** @var UploadFileRepository $uploadFileRepository */
            $uploadFileRepository = app()->make(UploadFileRepository::class);
            $fileInfo = $uploadFileRepository->getFileData($data['picture']);
            if($fileInfo){
                if($fileInfo['id'] != $info['id']){
                    $data['file_id'] = $fileInfo['id'];
                }
            }
        }
        unset($data['picture']);
        $data['edit_time'] = date('Y-m-d H:i:s');
        return $this->dao->update($id,$data);
    }


    /**
     * 删除
     */
    public function delete(array $ids)
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
        $query = $this->dao->search($where, $companyId)
            ->whereTime('start_time','<=',date('Y-m-d H:i:s'));
        $count = $query->count();
        $list = $query->page($page, $limit)
        ->with(
            [
                'file' => function ($query) {
                    $query->field('id,show_src,width,height');
                },
                'cateInfo' => function ($query) {
                    $query->field('id,name');
            }
            ])
            ->field('id,title,is_top,file_id,cate_id,add_time,start_time,sort')
            ->order('is_top','asc')
            ->order('sort','desc')
            ->select();
        return compact('list', 'count');
    }

    public function getTopList(array $where, $companyId = null)
    {
        $data = $this->dao->search($where, $companyId)
            ->with([
                'cateInfo' => function ($query) {
                    $query->field(['id, name']);
                },
                'file' => function ($query) {
                    $query->bind(['picture' => 'show_src']);
                }
            ])
            ->field('id,cate_id,title,content,add_time,start_time,is_top,file_id')
            ->order('sort desc')
            ->select();

        return $data;
    }

    public function getApiDetail(int $id,int $uuid,int $companyId=null)
    {
        $data = $this->dao->search(['is_show'=>1])->field('id,title,content,start_time,add_time')
               ->withCount(['is_follow'=>function($query) use($uuid){
                $query->where(['uuid'=>$uuid]);
            }])
            ->where('id', $id)
            ->find();

        api_user_log($uuid,3,$companyId,'查看公告');
        return $data;
    }


    public function getApiNew(array $where,$companyId = null){
        $query = $this->dao->search($where,$companyId)->order('id desc')
            ->with([
                'file' => function ($query) {
                    $query->bind(['picture' => 'show_src']);
                }
            ])
            ->hidden(['file'])
            ->find();
        return $query;
    }

}