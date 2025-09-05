<?php

namespace app\common\repositories\pool;

use app\common\dao\pool\PoolVolumnDao;
use app\common\repositories\BaseRepository;

/**
 * Class PoolVolumnRepository
 * @package app\common\repositories\pool
 * @mixin PoolVolumnDao
 */
class PoolVolumnRepository extends BaseRepository
{

    public function __construct(PoolVolumnDao $dao)
    {
        $this->dao = $dao;
    }

    public function getList(array $where, $page, $limit, $companyId = null)
    {
        $query = $this->dao->search($where, $companyId);
        $count = $query->count();
        $query->with(['goods'=>function($query){
            $query->field('id,title');
            $query->bind(['title']);
        },'user'=>function($query){
            $query->field('id,mobile');
            $query->bind(['mobile']);
        }]);
        $list = $query->page($page, $limit)
            ->select();
        return compact('count', 'list');
    }



    public function addInfo($companyId,$data)
    {
        $data['company_id'] = $companyId;
        $data['add_time'] = date('Y-m-d H:i:s');
        return $this->dao->create($data);
    }

    public function editInfo($info, $data)
    {
        return $this->dao->update($info['id'], $data);
    }

    public function getDetail(int $id)
    {
        $data = $this->dao->search([])
            ->with([
                'goods' => function ($query) {
                   $query->field('id,title');
                }
            ])
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

}