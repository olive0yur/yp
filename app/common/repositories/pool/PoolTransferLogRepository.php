<?php

namespace app\common\repositories\pool;

use app\common\dao\pool\PoolBrandDao;
use app\common\dao\pool\PoolTransferLogDao;
use app\common\repositories\BaseRepository;

/**
 * Class PoolBrandRepository
 * @package app\common\repositories\pool
 * @mixin PoolTransferLogDao
 */
class PoolTransferLogRepository extends BaseRepository
{

    public function __construct(PoolTransferLogDao $dao)
    {
        $this->dao = $dao;
    }

    public function getList(array $where, $page, $limit, $companyId = null)
    {
        $query = $this->dao->search($where, $companyId);
        $count = $query->count();
        $list = $query->page($page, $limit)->order('id desc')
            ->with(['pool'=>function($query){
                $query->bind(['title'=>'title']);
            }])
            ->select();

        return compact('count', 'list');
    }


    public function addInfo($companyId,$data)
    {
        $data['company_id'] = $companyId;
        return $this->dao->create($data);
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

    public function getApiList(array $where,int $page,int $limit,int $companyId = null){

        $query = $this->dao->search($where, $companyId);
        $count = $query->count();
        $list = $query->page($page, $limit)->order('id desc')
            ->with(['user'=>function($query){
                $query->field('id,mobile,nickname')->withAttr('mobile', function ($v, $data) {
                    return mb_substr_replace($v, '****', 3, 4);
                })->withAttr('nickname', function ($v, $data) {
                    return mb_substr_replace($v, '****', 0, -2);
                });
                $query->bind(['mobile' => 'mobile', 'nickname' => 'nickname']);
            }])
            ->append(['typeName'])
            ->select();
        return compact('count', 'list');
    }
}