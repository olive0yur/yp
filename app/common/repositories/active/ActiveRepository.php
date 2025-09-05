<?php

namespace app\common\repositories\active;

use app\common\dao\active\ActiveDao;
use app\common\repositories\BaseRepository;
use app\common\repositories\forum\VoteLogRepository;
use app\common\repositories\system\upload\UploadFileRepository;
use think\exception\ValidateException;
use think\facade\Db;

/**
 * Class PoolSaleRepository
 * @package app\common\repositories\active
 * @mixin ActiveDao
 */
class ActiveRepository extends BaseRepository
{

    public function __construct(ActiveDao $dao)
    {
        $this->dao = $dao;
    }


    public function getList(array $where, $page, $limit, $companyId = null)
    {
        $query = $this->dao->search($where, $companyId);
        $count = $query->count();
        $list = $query->page($page, $limit)
            ->with(['cover'=>function($query){
                $query->bind(['picture' => 'show_src']);
            }])
            ->hidden(['file'])->order('id desc')
            ->select();

        return compact('count', 'list');
    }


    public function addInfo($companyId,$data)
    {
        return Db::transaction(function () use ($data,$companyId) {
            if($data['cover']){
                /** @var UploadFileRepository $uploadFileRepository */
                $uploadFileRepository = app()->make(UploadFileRepository::class);
                $fileInfo = $uploadFileRepository->getFileData($data['cover'], 1,0);
                if($fileInfo){
                    if($fileInfo['id'] > 0){
                        $data['file_id'] = $fileInfo['id'];
                    }
                }
            }
            unset($data['cover']);
            $data['company_id'] = $companyId;
            $data['add_time'] = date('Y-m-d H:i:s');
            return $this->dao->create($data);
        });



    }

    public function editInfo($info, $data)
    {
        /** @var UploadFileRepository $uploadFileRepository */
        $uploadFileRepository = app()->make(UploadFileRepository::class);
        $fileInfo = $uploadFileRepository->getFileData($data['cover'], 1,0);
        unset($data['cover']);
        if($fileInfo){
            if($fileInfo['id'] != $info['id']){
                $data['file_id'] = $fileInfo['id'];
            }
        }
        return $this->dao->update($info['id'], $data);
    }

    public function getDetail(int $id)
    {
        $with = [
            'cover' => function ($query) {
                $query->field('id,show_src');
                $query->bind(['picture' => 'show_src']);
            },
        ];
        $data = $this->dao->search([])
            ->with($with)
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
        $query = $this->dao->search($where, $companyId)
            ->field('id,title,active_type,with_id,file_id,start_time,end_time');
        $count = $query->count();
        $query->page($page, $limit)
            ->with(['cover'=>function($query){
                $query->field('id,show_src,width,height');
            }])
            ->withAttr('status',function ($v,$data){
                  return authSyn($data); //合成时间判断
            })->append(['status'])

        ;
        if($where['sort'] == 1) $query->order('id asc');
        if($where['sort'] == 2) $query->order('id desc');
        $list =$query->hidden(['file'])->order('id desc')
            ->select();
        return compact('count', 'list');
    }

    public function getApiDetail(int $id,int $uuid)
    {
        $voteLogRepository = app()->make(VoteLogRepository::class);
        $with = [
            'cover' => function ($query) {
                $query->field('id,show_src,width,height');
            },
            'vote'=>function($query) use($voteLogRepository,$uuid){
                $query->where(['vote_type'=>2])->with(['cover'=>function($query){
                    $query->field('id,width,height,show_src');
                }])->withAttr('is_vote',function ($v,$data)use($voteLogRepository,$uuid){
                    return $voteLogRepository->search(['uuid'=>$uuid,'vote_type'=>2,'vote_id'=>$data['id']])->count('id');
                })->append(['is_vote']);
                $query->bind(['is_vote']);
            }
        ];
        $data = $this->dao->search([])
            ->with($with)
            ->where('id', $id)
            ->find();
        if(!$data){
            throw new ValidateException('未配置参数!');
        }
        if(!in_array($data['active_type'],[4,5])) throw new ValidateException('此接口只支持普通活动详情');
        return $data;
    }



}