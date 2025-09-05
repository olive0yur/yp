<?php

namespace app\common\repositories\union;

use app\common\dao\union\UnionConvertDao;
use app\common\repositories\BaseRepository;
use app\common\repositories\pool\PoolFlowRepository;
use app\common\repositories\pool\PoolOrderNoRepository;
use app\common\repositories\system\upload\UploadFileRepository;
use app\common\repositories\users\UsersRepository;
use app\common\repositories\users\UsersPoolRepository;
use \app\common\repositories\pool\PoolSaleRepository;
use think\exception\ValidateException;
use think\facade\Cache;
use think\facade\Db;

/**
 * Class UnionConvertRepository
 * @package app\common\repositories\pool
 * @mixin UnionConvertDao
 */
class UnionConvertRepository extends BaseRepository
{

    public function __construct(UnionConvertDao $dao)
    {
        $this->dao = $dao;
    }

    public function getList(array $where, $page, $limit, $companyId = null)
    {
        $where['is_show'] = 1;
        $query = $this->dao->search($where, $companyId);
        $count = $query->count();
        $list = $query->page($page, $limit)
            ->append(['sourceAddInfo','sourceOutInfo','cover'])
            ->order('id desc')
            ->select();
        return compact('count', 'list');
    }


    public function addInfo($companyId,$data)
    {
        $uploadFileRepository = app()->make(UploadFileRepository::class);
        $fileInfo = $uploadFileRepository->getFileData($data['cover'], 1,0);
        if($fileInfo['id'] > 0){
            $data['file_id'] = $fileInfo['id'];
        }
        unset($data['cover']);
        $data['company_id'] = $companyId;
        return $this->dao->create($data);
    }

    public function editInfo($info, $data)
    {
        return $this->dao->update($info['id'], $data);
    }

    public function getDetail(int $id)
    {
        $data = $this->dao->getSearch(['id'=>$id])
            ->append(['cover'])
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


    public function getApiPoolConvertInfo(array $data,int $companyId = null){

//        switch ($data['buy_type']){
//            case '1':
//                if((int)$data['pool_id'] <= 0 ) throw new ValidateException('请选择卡牌!');
//                $where['out_id'] = $data['pool_id'];
//                break;
//            case '2':
//                if((int)$data['box_id'] <= 0 ) throw new ValidateException('请选择肓盒!');
//                $where['out_id'] = $data['box_id'];
//                break;
//        }
        $where['is_show'] = 1;
        $query = $this->dao->search($where, $companyId)->where(['id'=>$data['id']]);
        $configInfo = $query->append(['sourceOutInfo','sourceAddInfo','cover'])
            ->field('id,buy_type,out_id,add_id,add_per,title,file_id,start_time,end_time')
            ->find();
        return $configInfo;
    }


    public function getApiUserConvertPoolAdd(array $data,$userInfo = [],int $companyId = null){
        $uuid = $userInfo['id'];
        if($userInfo['cert_id'] <= 0 ) throw new ValidateException('请先实名认证!');

        $usersRepository = app()->make(UsersRepository::class);
        $poolSaleRepository = app()->make(PoolSaleRepository::class);
        $usersPoolRepository = app()->make(UsersPoolRepository::class);
        $user = $usersRepository->search(['id'=>$uuid],$companyId)->field('pay_password,cert_id')->find();
        if($user['cert_id'] == 0) throw new ValidateException('请先实名认证!');
//        $verfiy = $usersRepository->passwordVerify($data['pay_password'],$userInfo['pay_password']);
//        if(!$verfiy) throw new ValidateException('交易密码错误!');



        $configInfo = $this->dao->getSearch(['id'=>$data['id'],'is_show'=>1])
            ->field('id,buy_type,out_id,add_id,add_per')
            ->find();
        if(!$configInfo) throw new ValidateException('暂未开启兑换!');

        $res1 = Cache::store('redis')->setnx('Convert_' . $uuid, $uuid);
        Cache::store('redis')->expire('Convert_' . $uuid, 1);
        if (!$res1) throw new ValidateException('排队中!');
        $usersPoolInfo = $usersPoolRepository->getSearch(['pool_id'=>$configInfo['out_id'],'status'=>1,'uuid'=>$uuid])->orderRand()->limit(1)->find();
        if(!$usersPoolInfo) throw new ValidateException('持有卡牌不存在!');

        $poolInfo = $poolSaleRepository->getSearch(['id'=>$configInfo['add_id']])
            ->field('id,title,stock,price')
            ->find();
        if(!$poolInfo) throw new ValidateException('卡牌信息不存在!');
        $reserveNum = (int)$poolInfo['stock'];
        if($reserveNum <= 0 || $configInfo['add_per'] > $reserveNum){
            throw new ValidateException('卡牌' .$poolInfo['title'].' 库存不足!');
        }

        $no = app()->make(PoolOrderNoRepository::class);;
        $poolFlowRepository = app()->make(PoolFlowRepository::class);

        return Db::transaction(function () use ($configInfo, $poolInfo, $usersPoolInfo, $no,$uuid, $companyId, $usersPoolRepository, $poolFlowRepository, $poolSaleRepository) {
            for ($i=1;$i<=$configInfo['add_per'];$i++){
                $usersPoolData['pool_id'] = $poolInfo['id'];
                $usersPoolData['no'] = $no->getNo($poolInfo['id'],$uuid);
                $usersPoolData['price'] = $poolInfo['price'];
                $usersPoolData['type'] = 8;
                $usersPoolData['status'] = 1;
                $usersPoolData['uuid'] = $uuid;
                $usersPoolRepository->addInfo($companyId,$usersPoolData);

                $poolFlowData['no'] = $usersPoolData['no'];
                $poolFlowData['uuid'] = $uuid;
                $poolFlowData['price'] = $poolInfo['price'];
                $poolFlowData['pool_id'] = $poolInfo['id'];
                $poolFlowRepository->addInfo($poolFlowData);
            }
            $poolSaleRepository->update($poolInfo['id'],['stock'=> ($poolInfo['stock']-$configInfo['add_per'])]);
            $resInfo = $usersPoolRepository->update($usersPoolInfo['id'],['status'=>8]);
            $no->getSearch(['pool_id'=>$poolInfo['id'],'no'=>$usersPoolInfo['no']])->update(['status'=>9,'destroy_time'=>date('Y_m-d H:i:s')]);
            return $resInfo;
        });
    }

    public function getUserConvertPoolList(array $where,$userInfo, $page, $limit, $companyId = null)
    {
        $where['uuid'] = $userInfo['id'];
        $where['type'] = 8;
        $usersPoolRepository = app()->make(UsersPoolRepository::class);
        $query = $usersPoolRepository->search($where, $companyId);
        $count = $query->count();
        $list = $query->page($page, $limit)
            ->with(['pool'=>function($query){
                $query->field('id,title,file_id')->with(['cover'=>function($query){
                    $query->field('id,show_src,width,height');
                }]);
            }])
            ->order('id desc')
            ->select();
        return compact('count', 'list');
    }


}