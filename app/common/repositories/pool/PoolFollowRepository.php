<?php

namespace app\common\repositories\pool;

use app\common\dao\pool\PoolFollowDao;
use app\common\dao\pool\PoolSaleDao;
use app\common\repositories\BaseRepository;
use app\common\repositories\system\upload\UploadFileRepository;
use think\exception\ValidateException;
use think\facade\Cache;
use think\facade\Db;

/**
 * Class PoolSaleRepository
 * @package app\common\repositories\pool
 * @mixin PoolSaleDao
 */
class PoolFollowRepository extends BaseRepository
{

    public function __construct(PoolFollowDao $dao)
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
            ->hidden(['site', 'file'])
            ->select();

        return compact('count', 'list');
    }

    public function addInfo($companyId,$data)
    {
        $data['company_id']  =$companyId;
        return $this->dao->create($data);
    }

    public function follow(array $data,int $uuid,int $companyId){
        $info = $this->dao->search(['goods_id'=>$data['goods_id'],'buy_type'=>$data['buy_type'],'uuid'=>$uuid],$companyId)->find();
        if($info){
            $info->delete();
        }else{
            $arr['goods_id'] = $data['goods_id'];
            $arr['uuid'] = $uuid;
            $arr['buy_type'] = $data['buy_type'];
            return $this->addInfo($companyId,$arr);
        }
    }

}