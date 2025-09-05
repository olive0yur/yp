<?php

namespace app\common\repositories\turntable;

use app\common\dao\pool\PoolSaleDao;
use app\common\dao\turntable\TurntableGoodsDao;
use app\common\repositories\agent\AgentRepository;
use app\common\repositories\BaseRepository;
use app\common\repositories\box\BoxSaleGoodsListRepository;
use app\common\repositories\mine\MineRepository;
use app\common\repositories\mine\MineUserRepository;
use app\common\repositories\givLog\GivLogRepository;
use app\common\repositories\pool\PoolOrderNoRepository;
use app\common\repositories\pool\PoolSaleRepository;
use app\common\repositories\pool\PoolTransferLogRepository;
use app\common\repositories\snapshot\SnapshotRepository;
use app\common\repositories\system\upload\UploadFileRepository;
use app\common\repositories\users\UsersAddressRepository;
use app\common\repositories\users\UsersFoodLogRepository;
use app\common\repositories\users\UsersMarkRepository;
use app\common\repositories\users\UsersPoolRepository;
use app\common\repositories\users\UsersRepository;
use app\controller\company\box\Brand;
use app\helper\SnowFlake;
use app\jobs\PoolUserApiBuyJob;
use think\exception\ValidateException;
use think\facade\Cache;
use think\facade\Db;
use app\common\repositories\pool\PoolOrderLockRepository;

/**
 * Class PoolSaleRepository
 * @package app\common\repositories\pool
 * @mixin PoolSaleDao
 */
class TurntableGoodsRepository extends BaseRepository
{

    public $pooolOrder;


    public function __construct(TurntableGoodsDao $dao)
    {
        $this->dao = $dao;
    }


    public function getList(array $where, $page, $limit, $companyId = null)
    {
        $query = $this->dao->search($where, $companyId);
        $count = $query->count();
        $list = $query->page($page, $limit)
            ->append(['goods'])
            ->hidden(['site', 'file'])->order('id desc')
            ->select();
        return compact('count', 'list');
    }

    public function addInfo($companyId,$data)
    {
        return Db::transaction(function () use ($data,$companyId) {
            $data['company_id'] = $companyId;
            $data['create_at'] = date('Y-m-d H:i:s');
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

    public function getEnd($data,$userInfo,$companyId){

            $web = web_config($companyId,'turn');
            if(!$web) throw new ValidateException('转盘配置未完善!');

        /** @var UsersTurnRepository $usersTurnRepository */
        $usersTurnRepository = app()->make(UsersTurnRepository::class);
        /** @var UsersRepository $usersRepository */
        $usersRepository = app()->make(UsersRepository::class);
        switch ($data['types']){
            case 2:
                $delChange =  $web['tenPrice'];
                if($userInfo['jade'] < $delChange) throw new ValidateException('余额不足!');
                $usersRepository->jadeChange($userInfo['id'],3,(-1)*$delChange,['remark'=>'开转盘x'.$data['num']],4,$companyId);
                Cache::store('redis')->inc('pool',$delChange);
                break;
        }



        /** @var TurntableGoodsRepository $TurntableGoodsRepository */
            $TurntableGoodsRepository = app()->make(TurntableGoodsRepository::class);
            $list = $TurntableGoodsRepository->search([],$companyId)->where('lv','>=',0)->select();
            $item = [];
            foreach ($list as $k => $v) {
                $item[$v['id']] = $v['lv'];
            }


                /** @var UsersRepository $usersRepository */
                $usersRepository = app()->make(UsersRepository::class);
                for ($i = 1; $i <= $data['num']; $i++) {
                    $ids[] = SignOpenBox($item);
                }

                /** @var UsersTurnRepository $usersTurnRepository */
                $usersTurnRepository = app()->make(UsersTurnRepository::class);
                switch ($data['types']){
                    case 1:  //广告
                        throw new ValidateException('暂未对接广告!');
                        if($data['num'] != 1) throw new ValidateException('禁止乱传数字');
                        $list = $TurntableGoodsRepository->search([],$companyId)
                            ->whereIn('id',$ids)->select();
                        $idCounts = array_count_values($ids);
                        foreach ($list as &$item) {
                            $id = $item['id'];
                            if($item['type'] == 1){
                                $pool = Cache::store('redis')->get('pool');
                                $change =  $pool * $item['per'];
                                $usersRepository->foodChange($userInfo['id'],6,$change,['remark'=>'看广告开转盘'],4,$companyId);
                                Cache::store('redis')->dec('pool',$change);
                            }
                            $item['count'] = $idCounts[$id] ?? 0;
                            $item['food'] = $change ?? 0;

                            $add['uuid'] = $userInfo['id'];
                            $add['turn_id'] = $item['id'];
                            $add['num'] = $change;
                            $add['types'] = 1;
                            $usersTurnRepository->addInfo($companyId,$add);


                        }
                        break;
                    case 2:  //余额
                        $list = $TurntableGoodsRepository->search([],$companyId)
                            ->whereIn('id',$ids)->select();
                        $idCounts = array_count_values($ids);
                        foreach ($list as &$item) {
                            $id = $item['id'];
                            if($item['type'] == 1){
                                $pool = Cache::store('redis')->get('pool');
                                if(!$pool) throw new ValidateException('奖池为空!');
                                $change =  $pool * $item['per'];
                                $usersRepository->foodChange($userInfo['id'],4,$change,['remark'=>'开转盘x'.$data['num']],4,$companyId);
                                Cache::store('redis')->dec('pool',$change);
                            }
                            if($item['type'] == 4){
                                Cache::store('redis')->dec('pool',$change);

                                $poolSaleRepository = app()->make(PoolSaleRepository::class);
                                $poolInfo = $poolSaleRepository->get($item['pool_id']);
                                if ($poolInfo['stock'] < 1) {
                                    throw new ValidateException('库存不足!');
                                }
                                $no = app()->make(PoolOrderNoRepository::class);
                                $userPool = app()->make(UsersPoolRepository::class);

                                $add['uuid'] = $userInfo['id'];
                                $add['add_time'] = date('Y-m-d H:i:s');
                                $add['pool_id'] = $data['pool_id'];
                                $add['no'] = $no->getNo($item['pool_id'],$userInfo['id']);
                                $add['price'] = 0.00;
                                $add['type'] = 2;
                                $add['status'] = 1;
                                $add['is_dis'] = 1;
                                $result = $userPool->addInfo($companyId, $add);
                                $log = [];
                                $log['pool_id'] = $item['pool_id'];
                                $log['no'] = $add['no'];
                                $log['uuid'] = $userInfo['id'];
                                $log['type'] = 2;
                                app()->make(PoolTransferLogRepository::class)->addInfo($companyId,$log);
                                $poolSaleRepository->search([], $companyId)
                                    ->where(['id' => $data['pool_id']])->dec('stock', 1)->update();
                                /** @var MineUserRepository $mineUserRepository */
                                $mineUserRepository = app()->make(MineUserRepository::class);
                                /** @var MineRepository $mineRepository */
                                $mineRepository = app()->make(MineRepository::class);
                                $mine = $mineRepository->search(['level'=>1,'status'=>1],$companyId)->find();
                                if($mine) {
                                    //发放start
                                    $re = $mineUserRepository->search(['uuid' => $userInfo['id'], 'level' => 1, 'status' => 1], $companyId)->find();
                                    if ($re) $mineUserRepository->incField($re['id'], 'dispatch_count', $data['num']);
                                    if (!$re) {
                                        event('user.mine', $userInfo);
                                        sleep(2);
                                        $re = $mineUserRepository->search(['uuid' => $userInfo['id'], 'level' => 1, 'status' => 1], $companyId)->find();
                                        if ($re) {
                                                    $mineUserRepository->incField($re['id'], 'dispatch_count', $data['num']);
                                            }
                                        }
                                    }
                                    //发放end
                                $change  = 1;
                                }
                            if($item['type'] == 5){
                                $pool = Cache::store('redis')->get('pool');
                                if(!$pool) throw new ValidateException('奖池为空!');
                                $change =  $pool * $item['per'];
                                $usersRepository->jadeChange($userInfo['id'],4,$change,['remark'=>'开转盘x'.$data['num']],4,$companyId);
                                Cache::store('redis')->dec('pool',$change);
                            }

                            $item['count'] = $idCounts[$id] ?? 0;
                            $item['food'] = $change ?? 0;
                            $add['uuid'] = $userInfo['id'];
                            $add['turn_id'] = $item['id'];
                            $add['num'] = $change;
                            $add['types'] = 2;
                            $usersTurnRepository->addInfo($companyId,$add);
                        }
                        break;
                }
                return $list;
      }

    public function getApiList($companyId = null)
    {
        $query = $this->dao->search([], $companyId);
        $count = $query->count();
        $list = $query->field('id')->order('id desc')
            ->select();
        return compact('count', 'list');
    }

}