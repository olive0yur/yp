<?php

namespace app\common\repositories\users;

use app\common\dao\users\FastBuyDao;
use app\common\dao\users\UsersMarkDao;
use app\common\repositories\box\BoxSaleRepository;
use app\common\repositories\pool\PoolFollowRepository;
use app\common\repositories\pool\PoolOrderNoRepository;
use app\common\repositories\pool\PoolSaleRepository;
use app\common\repositories\pool\PoolShopOrder;
use app\common\repositories\pool\ShopOrderListRepository;
use app\helper\SnowFlake;
use think\exception\ValidateException;
use think\facade\Cache;
use think\facade\Db;
use app\common\repositories\BaseRepository;

class FastBuyRepository extends BaseRepository
{
    public function __construct(FastBuyDao $dao)
    {

        $this->dao = $dao;
    }
    public function getList(array $where, $page, $limit, int $companyId = null)
    {

        $query = $this->dao->search($where,$companyId);
        $count = $query->count();
        $list = $query->page($page, $limit)
            ->order('id', 'desc')
            ->select();
        return compact('list', 'count');
    }



    public function addInfo(int $companyId = null,array $data = [])
    {
        return Db::transaction(function () use ($data,$companyId) {
            if($companyId) $data['company_id'] = $companyId;
            $data['add_time'] = date('Y-m-d H:i:s');
            $userInfo = $this->dao->create($data);
            return $userInfo;
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
}