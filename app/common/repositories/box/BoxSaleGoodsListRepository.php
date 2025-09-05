<?php

namespace app\common\repositories\box;

use app\common\dao\box\BoxSaleGoodsListDao;
use app\common\repositories\BaseRepository;
use app\common\repositories\fraud\FraudRepository;
use app\common\repositories\pool\PoolSaleRepository;
use app\common\repositories\pool\PoolShopOrder;
use app\common\repositories\system\upload\UploadFileRepository;
use app\common\repositories\users\UsersBoxRepository;
use app\common\repositories\users\UsersRepository;
use think\exception\ValidateException;
use think\facade\Cache;
use think\facade\Db;

/**
 * Class PoolSaleRepository
 * @package app\common\repositories\pool
 * @mixin BoxSaleGoodsListDao
 */
class BoxSaleGoodsListRepository extends BaseRepository
{



    public function __construct(BoxSaleGoodsListDao $dao)
    {
        $this->dao = $dao;
    }


    public function getList(array $where, $page, $limit, $companyId = null)
    {
        $query = $this->dao->search($where, $companyId);
        $count = $query->count();
        $list = $query->page($page, $limit)
                 ->append(['goods'])
                 ->order('id desc')
            ->select();

        return compact('count', 'list');
    }


    public function addInfo($companyId,$data)
    {

        /** @var UploadFileRepository $uploadFileRepository */
        $uploadFileRepository = app()->make(UploadFileRepository::class);
        if ($data['cover']){
            $fileInfo = $uploadFileRepository->getFileData($data['cover'], 1, 0);
            if ($fileInfo){
                if ($fileInfo['id'] > 0){
                    $data['file_id'] = $fileInfo['id'];
                }
            }
        }
        unset($data['cover']);

        return Db::transaction(function () use ($data,$companyId) {
            $data['company_id'] = $companyId;
            return $this->dao->create($data);
        });



    }

    public function editInfo($info, $data)
    {
        /** @var UploadFileRepository $uploadFileRepository */
        $uploadFileRepository = app()->make(UploadFileRepository::class);
        if ($data['cover']){
            $fileInfo = $uploadFileRepository->getFileData($data['cover'], 1, 0);
            if ($fileInfo){
                if ($fileInfo['id'] > 0){
                    $data['file_id'] = $fileInfo['id'];
                }
            }
        }
        unset($data['cover']);
        
        return $this->dao->update($info['id'], $data);
    }


    public function getDetail(int $id)
    {

        $data = $this->dao->search([])
            ->where('id', $id)
            ->append(['goods'])
            ->find();
        return $data;
    }

    public function batchDelete(array $ids)
    {
        $list = $this->dao->selectWhere([
            ['id', 'in', $ids]
        ]);
        $repository = app()->make(PoolSaleRepository::class);
        if ($list) {
            foreach ($list as $k => $v) {
                switch ($v['goods_type']){
                    case 1:
                        //
//                        $repository->search([],$v['company_id'])->where('id',$v['goods_id'])->update(['is_box'=>2]);
                        Cache::store('redis')->delete('goods_'.$v['goods_id']);
                        break;
                }
                $this->dao->delete($v['id']);
            }
            return $list;
        }
        return [];
    }
}