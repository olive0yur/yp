<?php

namespace app\common\repositories\active\syn;

use app\common\dao\active\syn\ActiveSynInfoDao;
use app\common\repositories\BaseRepository;
use app\common\repositories\box\BoxSaleRepository;
use app\common\repositories\pool\PoolSaleRepository;
use app\common\repositories\fraud\FraudRepository;
use app\common\repositories\system\upload\UploadFileRepository;
use app\common\repositories\users\UsersBoxRepository;
use app\common\repositories\users\UsersPoolRepository;
use Mpdf\Tag\P;
use think\facade\Db;

/**
 * Class ActiveSynInfoRepository
 * @package app\common\repositories\active
 * @mixin ActiveSynInfoDao
 */
class ActiveSynInfoRepository extends BaseRepository
{

    public function __construct(ActiveSynInfoDao $dao)
    {
        $this->dao = $dao;
    }


    public function getList(array $where, $page, $limit, $companyId = null)
    {
        $query = $this->dao->search($where, $companyId);
        $count = $query->count();
        $query->append(['goods']);
        $list = $query->page($page, $limit)->order('id desc')
            ->select();
        return compact('count', 'list');
    }


    public function addInfo($companyId,$data)
    {

        return Db::transaction(function () use ($data,$companyId) {
            if(isset($data['img']) && $data['img']){
                /** @var UploadFileRepository $uploadFileRepository */
                $uploadFileRepository = app()->make(UploadFileRepository::class);
                $fileInfo = $uploadFileRepository->getFileData($data['img'], 1,0);
                if($fileInfo){
                    $data['file_id'] = $fileInfo['id'];
                }
            }
            $data['company_id'] = $companyId;
            $data['add_time'] = date('Y-m-d H:i:s');
            return $this->dao->create($data);
        });



    }

    public function editInfo($info, $data)
    {
        if(isset($data['img']) && $data['img']){
            /** @var UploadFileRepository $uploadFileRepository */
            $uploadFileRepository = app()->make(UploadFileRepository::class);
            $fileInfo = $uploadFileRepository->getFileData($data['img'], 1,0);
            if($fileInfo['id'] != $info['id']){
                $data['file_id'] = $fileInfo['id'];
            }
        }
        unset($data['img']);
        return $this->dao->update($info['id'], $data);
    }

    public function getDetail(int $id)
    {
        $data = $this->dao->search([])
         ->append(['goods'])
            ->with(['cover'=>function($query){
                $query->bind(['picture'=>'show_src']);
            }])
        ->where('id', $id)
            ->find();
        return $data;
    }

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

    public function getApiDetail(array $where,$uuid,int $companyId = null)
    {
        ## 合成目标
        $target = $this->dao->search($where, $companyId)
            ->where(['is_target'=>1])->whereIn('target_type',[1,2])->field('id,target_type,file_id,num,syn_type,goods_id,probability')
            ->append(['goods'])
            ->with(['cover'=>function($query){
                $query->field('id,show_src,width,height');
            }])
            ->select();
        ## 所需材料
        $material = $this->dao->search($where, $companyId)
            ->field('id,target_type,syn_type,goods_id,num,is_must')
            ->where(['is_target'=>2])
            ->withAttr('ownNum',function ($V,$data) use($companyId,$uuid){
                if($data['target_type'] == 1){
                    return app()->make(UsersPoolRepository::class)
                        ->search(['uuid'=>$uuid,'pool_id'=>$data['goods_id'],'status'=>1],$companyId)->count('id');
                }elseif($data['target_type'] == 2){
                    return app()->make(UsersBoxRepository::class)
                        ->search(['uuid'=>$uuid,'box_id'=>$data['goods_id'],'status'=>1],$companyId)->count('id');
                }

            })
            ->append(['goods','ownNum'])
            ->select();
        $usersPoolRepository = app()->make(UsersPoolRepository::class);
        $usersBoxRepository = app()->make(UsersBoxRepository::class);
        $data['uuid'] = $uuid;
        $data['status'] = 1;
        foreach ($material as $key => $value){
            switch ($value['target_type']){
                case 1:
                    $data['pool_id'] = $value['goods_id']; ## 计算材料是不是 达标
                    $count = $usersPoolRepository->search($data,$companyId)->count('id');
                    break;
                case 2:
                    $data['box_id'] = $value['goods_id'];## 计算材料是不是 达标
                    $count = $usersBoxRepository->search($data,$companyId)->count('id');
                    break;
            }
            $material[$key]['is_can'] = 0;##  合成不了
            if($count >= $value['num']) $material[$key]['is_can'] = 1; ##   可以合成
        }
        return compact('target', 'material');
    }


    public function getApiDetail_v1(array $where,$uuid,int $companyId = null)
    {
        ## 合成目标
        $target = $this->dao->search($where, $companyId)
            ->where(['is_target'=>1])->whereIn('target_type',[1,2])->field('id,target_type,file_id,num,syn_type,goods_id,probability')
            ->append(['goods'])
            ->with(['cover'=>function($query){
                $query->field('id,show_src,width,height');
            }])
            ->select();
        ## 所需材料
        $material = app()->make(ActiveSynKeyRepository::class)->search($where, $companyId)
            ->with(['info'=>function($query){
                $query->with(['pool'=>function($query){
                    $query->field('id,title,file_id');
                    $query->with(['cover'=>function($query){
                        $query->field('id,show_src,width,height');
                    }]);
                }]);
            }])
            ->select();
        $usersPoolRepository = app()->make(UsersPoolRepository::class);
        $usersBoxRepository = app()->make(UsersBoxRepository::class);
        $data['uuid'] = $uuid;
        $data['status'] = 1;
        foreach ($material as $k => $v){

            foreach ($v['info'] as $key => $value){
                $data['pool_id'] = $value['pool_id']; ## 计算材料是不是 达标
                $count = $usersPoolRepository->search($data,$companyId)->count('id');
                $material[$k]['info'][$key]['is_can'] = 0;##  合成不了
                if($count >= $v['num']) $material[$k]['info'][$key]['is_can'] = 1; ##   可以合成
            }
        }
        return compact('target', 'material');
    }


    /**
     * 获取几率随机目标
     *
     * @param int $synId
     */
    public function getProbabilityRandomTarget(int $synId)
    {
        $fraudRepository = app()->make(FraudRepository::class);
        $targetList = $this->dao->search(['syn_id' => $synId, 'is_target' => 1])
            ->where('probability', '>', 0)
            ->orderRand()
            ->withAttr('num', function ($v, $data) use ($fraudRepository) {
                return $data['num'] - $fraudRepository->search(['fraud_type' => 3, 'pid' => $data['id']])->sum('num');
            })
            ->select()->toArray();
        if (!$targetList) {
            return null;
        }
        foreach ($targetList as $k => $v) {
            if ($v['num'] <= 0) {
                unset($targetList[$k]);
            }
        }
        if (!$targetList) {
            return null;
        }

        $probabilitys = [];
        foreach ($targetList as $v) {
            $probabilitys[] = $v['probability'] * 100;
        }

        // 减少生成数据 等量减少最少得几率数量
        $min = min($probabilitys) - 1;

        $ids = get_arr_column($targetList, 'id');
        foreach ($targetList as $v) {
            $probability = ($v['probability'] * 100) - $min;
            for ($i = 0; $i < $probability; $i++) {
                $ids[] = $v['id'];
            }
        }

        $id = $ids[array_rand($ids)];

        return $this->dao->getSearch([])
            ->where('id', $id)
            ->with(['cover' => function ($query) {
                $query->bind(['picture' => 'show_src']);
            }])
            ->find();
    }
}