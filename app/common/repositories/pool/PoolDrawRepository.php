<?php

namespace app\common\repositories\pool;

use app\common\dao\pool\PoolBrandDao;
use app\common\dao\pool\PoolDrawDao;
use app\common\repositories\BaseRepository;
use app\common\repositories\system\upload\UploadFileRepository;

/**
 * Class PoolBrandRepository
 * @package app\common\repositories\pool
 * @mixin PoolBrandDao
 */
class PoolDrawRepository extends BaseRepository
{

    public function __construct(PoolDrawDao $dao)
    {
        $this->dao = $dao;
    }

    public function getList(array $where, $page, $limit, $companyId = null)
    {


        $query = $this->dao->search($where, $companyId);
        $count = $query->count();

        $query->with(['user'=>function($query){
            $query->bind(['mobile','nickname']);
        }]);

        $list = $query->page($page, $limit)->order('id desc')
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
}