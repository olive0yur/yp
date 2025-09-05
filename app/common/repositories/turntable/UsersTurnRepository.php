<?php

namespace app\common\repositories\turntable;

use app\common\dao\pool\PoolSaleDao;
use app\common\dao\turntable\TurntableGoodsDao;
use app\common\dao\turntable\UsersTurnDao;
use app\common\repositories\BaseRepository;
use think\facade\Db;

/**
 * Class PoolSaleRepository
 * @package app\common\repositories\pool
 * @mixin PoolSaleDao
 */
class UsersTurnRepository extends BaseRepository
{

    public $pooolOrder;


    public function __construct(UsersTurnDao $dao)
    {
        $this->dao = $dao;
    }


    public function getList(array $where, $page, $limit, $companyId = null)
    {
        $query = $this->dao->search($where, $companyId);
        $count = $query->count();
        $list = $query->page($page, $limit)
            ->order('id desc')
            ->select();
        return compact('count', 'list');
    }

    public function addInfo($companyId,$data)
    {
            $data['company_id'] = $companyId;
            $data['create_at'] = date('Y-m-d H:i:s');
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
    public function getApiList($page, $limit,$userInfo, $companyId = null)
    {
        $query = $this->dao->search(['uuid'=>$userInfo['id']], $companyId);
        $count = $query->count();
        $list = $query->with(['goods'])->page($page, $limit)
            ->order('id desc')
            ->select();
        return compact('count', 'list');
    }

}