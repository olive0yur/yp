<?php

namespace app\common\repositories\pool;

use app\common\dao\pool\PoolOrderNoDao;
use app\common\repositories\BaseRepository;
use app\common\repositories\system\upload\UploadFileRepository;
use app\common\repositories\users\UsersPoolRepository;
use think\exception\ValidateException;
use think\facade\Cache;
use think\facade\Db;

/**
 * Class PoolOrderNoRepository
 * @package app\common\repositories\pool
 * @mixin PoolOrderNoDao
 */
class PoolOrderNoRepository extends BaseRepository
{

    public function __construct(PoolOrderNoDao $dao)
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

    public function getDetail($where, $companyId = null)
    {
        $data = $this->dao->search($where, $companyId)->find();
        return $data;
    }

    public function addInfo($companyId, $data)
    {
        return Db::transaction(function () use ($data, $companyId) {

            $no = [];
            $transfer = [];
            for ($i = 1; $i <= $data['num']; $i++) {
                $arr['pool_id'] = $data['id'];
                $arr['no'] = $i;
                $no[] = $arr;
                $log['pool_id'] = $data['id'];
                $log['no'] = $i;
                $log['type'] = 1;
                $log['company_id'] = $companyId;
                 $transfer[] = $log;
            }
            $re = $this->dao->insertAll($no);
            if ($re) {
                /** @var PoolSaleRepository $PoolSaleRepository */
                $PoolSaleRepository = app()->make(PoolSaleRepository::class);
                $PoolSaleRepository->update($data['id'], ['is_number' => 1]);
                app()->make(PoolTransferLogRepository::class)->insertAll($transfer);
            }
            return true;
        });
    }

    public function editInfo($info, $data)
    {
        return $this->dao->update($info['id'], $data);
    }

    public function all($data)
    {
        $this->dao->insertAll($data);
    }

    public function getNo($pool_id,$uuid = null, $level = 1)
    {
        if ($level >= 3) throw new ValidateException('卡牌预热中，请稍后!');
        $no = Cache::store('redis')->rpop('goods_no_' . $pool_id);
        if ($no) {
            $noInfo = $this->dao->getSearch(['pool_id' => $pool_id, 'no' => $no])->find();
            if($noInfo && $noInfo['status'] == 2){
                $this->dao->getSearch(['pool_id' => $pool_id, 'no' => $no])->update(['status' => 1,'uuid'=>$uuid]);
                return $no;
            }
            return $this->getNo($pool_id, $uuid,$level);
        }
        $list = $this->dao->getSearch(['pool_id' => $pool_id, 'status' => 2])
            ->field('no')
            ->orderRand()
            ->limit(500)
            ->select();
        if (!$list) {
            throw new ValidateException('当前藏己发售完毕!');
        }
        foreach ($list as $key => $value) {
            Cache::store('redis')->lpush('goods_no_' . $pool_id, $value['no']);
        }
        $level += 1;
        return $this->getNo($pool_id,$uuid, $level);

    }


    /**
     * 随机查询 未销售出去的卡牌
     * @param $pool_id
     * @param $level
     * @return mixed
     * @throws \think\db\exception\DbException
     */
    public function getRandNo($pool_id)
    {
        $noInfo = $this->getSearch(['pool_id' => $pool_id, 'status' => 2])->orderRand()->find();
        if ($noInfo) {
            $this->dao->getSearch(['id' => $noInfo['id']])->update(['status' => 1]);
            return $noInfo['no'];
        } else {
            throw new ValidateException('当前藏己发售完毕!');
        }


    }

    /**
     * 销毁卡牌
     */
    public function batchDestroy(array $ids)
    {
        $list = $this->dao->selectWhere([
            ['id', 'in', $ids]
        ]);
        if ($list) {
            foreach ($list as $k => $v) {
                $this->destroyPoll($v);
            }
            return $list;
        }
        return [];
    }


    ## 销毁卡牌
    public function destroyPoll($poolInfo)
    {
        $usersPoolRepository = app()->make(UsersPoolRepository::class);
        $usersPoolInfo = $usersPoolRepository->search([],$poolInfo['company_id'])
            ->where('pool_id',$poolInfo['pool_id'])
            ->where('no',$poolInfo['no'])
            ->whereIn('status',[1,2])
            ->find();
         Db::transaction(function () use ($usersPoolInfo,$usersPoolRepository,$poolInfo) {
            if($usersPoolInfo['id'] > 0){
                $usersPoolRepository->update($usersPoolInfo['id'], [
                    'status' => 6,
                ]);
            }
            $this->dao->update($poolInfo['id'], [
                'status' => 9,
                'destroy_time' => date('Y_m-d H:i:s'),
            ]);
        });
    }

    public function  ballRrecovery(array $ids)
    {
        $list = $this->dao->selectWhere([
            ['id', 'in', $ids]
        ]);
        $usersPoolRepository = app()->make(UsersPoolRepository::class);
        foreach ($list as $v){
            if($v['status'] == 2 ) throw new ValidateException($v['no'].'编号未下发!');
            $usersPoolInfo = $usersPoolRepository->search([],$v['company_id'])
                ->where('pool_id',$v['pool_id'])
                ->where('no',$v['no'])
                ->whereIn('status',[1,2])
                ->find();
            Db::transaction(function () use ($usersPoolInfo,$usersPoolRepository,$v) {
                if($usersPoolInfo['id'] > 0){
                    $usersPoolRepository->update($usersPoolInfo['id'], [
                        'status' => 11,
                    ]);
                }
                $this->dao->update($v['id'], [
                    'status' => 2,
                    'is_success' => 1,
                    'chain_status' => 1,
                ]);
            });
        }
        return true;
    }
}