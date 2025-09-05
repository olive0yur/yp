<?php

namespace app\common\repositories\pool;

use app\common\dao\pool\PoolOrderNoDao;
use app\common\dao\pool\ShopOrderListDao;
use app\common\repositories\BaseRepository;
use app\common\repositories\system\upload\UploadFileRepository;
use think\exception\ValidateException;
use think\facade\Cache;
use think\facade\Db;

/**
 * Class PoolOrderNoRepository
 * @package app\common\repositories\pool
 * @mixin PoolOrderNoDao
 */
class ShopOrderListRepository extends BaseRepository
{

    public function __construct(ShopOrderListDao $dao)
    {
        $this->dao = $dao;
    }

    public function getList(array $where, $page, $limit, $companyId = null)
    {
        $query = $this->dao->search($where, $companyId);
        $count = $query->count();
        $list = $query->page($page, $limit)
            ->select();

        return compact('count', 'list');
    }

    public function getDetail($where,$companyId = null)
    {
        $data = $this->dao->search($where,$companyId)->find();
        return $data;
    }

    public function addAll($data){
        return $this->dao->insertAll($data);
    }

    public function editInfo($info, $data)
    {
        return $this->dao->update($info['id'], $data);
    }
}