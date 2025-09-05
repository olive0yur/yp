<?php

namespace app\common\repositories\active\syn;

use app\common\dao\active\syn\ActiveSynLogDao;
use app\common\repositories\BaseRepository;
use app\common\repositories\pool\PoolOrderNoRepository;
use app\common\repositories\system\upload\UploadFileRepository;
use app\common\repositories\users\UsersPoolRepository;
use app\helper\SnowFlake;
use think\exception\ValidateException;
use think\facade\Db;

/**
 * Class ActiveSynLogRepository
 * @package app\common\repositories\active
 * @mixin ActiveSynLogDao
 */
class ActiveSynLogRepository extends BaseRepository
{

    public $usersPoolRepository;
    public $activeSynInfoRepository;

    public function __construct(ActiveSynLogDao $dao)
    {
        $this->dao = $dao;
    }


    public function getList(array $where, $page, $limit, $companyId = null)
    {

        $with = [
            'synInfo' => function ($query) {
                $query->field('id,goods_id,target_type')
                    ->with([
                        'goods' => function ($query) {
                            $query->field('id,title,file_id');
                        }
                    ]);
            },
            'user' => function ($query) {
                $query->bind(['mobile', 'nickname']);
            }
        ];
        $query = $this->dao->search($where, $companyId)->with($with);
        $count = $query->count();
        $list = $query->page($page, $limit)->append(['goodsName'])
            ->order('id desc')
            ->select();

        return compact('count', 'list');
    }


    public function addInfo($companyId, $data)
    {
        return Db::transaction(function () use ($data, $companyId) {
            $data['company_id'] = $companyId;
            $data['add_time'] = date('Y-m-d H:i:s');
            return $this->dao->create($data);
        });


    }

    public function editInfo($info, $data)
    {
        return $this->dao->update($info['id'], $data);
    }

    public function getDetail(int $id)
    {
        $data = $this->dao->search([])
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
        $with = [
            'synInfo' => function ($query) {
                $query->field('id,goods_id,file_id,target_type')
                    ->with(['goods' => function ($query) {
                    $query->field('id,title,file_id')->with(['cover' => function ($query) {
                        $query->field('id,show_src,width,height');
                    }
                    ]);
                },'cover'=>function($query){
                        $query->field('id,show_src,width,height');
                    }]);
            },
            'no'=>function($query){
                $query->field('id,no');
            }
        ];
        $query = $this->dao->search($where, $companyId);
        $count = $query->count();
        $query->with($with)->append(['goods']);
        $list = $query->page($page, $limit)
            ->order('id desc')
            ->select();
        return compact('count', 'list');
    }


    public function getApiDetail(array $where, $companyId = null)
    {
        $with = [
            'synInfo' => function ($query) {
                $query->field('id,goods_id,syn_id')->with(['goods' => function ($query) {
                    $query->field('id,title,file_id,num')->with(['cover' => function ($query) {
                        $query->field('id,show_src,width,height');
                    }
                    ]);
                }, 'active'=>function($query){
                        $query->field('id,title,file_id,with_id')->where(['active_type'=>1])->with(['cover'=>function($query){
                            $query->field('id,width,height,show_src');
                        }]);
                    }
                    ]);
            },
            'no'=>function($query){
                $query->field('id,no');
            }
        ];
        $query = $this->dao->search($where, $companyId)->where('id',$where['id']);
        $count = $query->count();
        $query->with($with)->append(['goods']);
        $list = $query->order('id desc')
            ->find();
        return $list;
    }

    public function synGet($data,$uuid,$companyId = null){

        $with = [
            'synInfo' => function ($query) {
                $query->field('id,goods_id,syn_id')->with(['goods' => function ($query) {
                    $query->field('id,title,file_id,num')->with(['cover' => function ($query) {
                        $query->field('id,show_src,width,height');
                    }
                    ]);
                }, 'active'=>function($query){
                    $query->field('id,title,file_id,with_id')->where(['active_type'=>1])->with(['cover'=>function($query){
                        $query->field('id,width,height,show_src');
                    }]);
                }
                ]);
            },
            'no'=>function($query){
                $query->field('id,no');
            }
        ];
        $query = $this->dao->search(['uuid'=>$uuid,'userNo'=>$data['no']], $companyId);
        $query->with($with)->append(['goods']);
        $list = $query->order('id desc')
            ->find();
        return $list;
    }



}