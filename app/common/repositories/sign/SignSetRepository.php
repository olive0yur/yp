<?php

namespace app\common\repositories\sign;

use app\common\dao\sign\SignSetDao;
use app\common\repositories\BaseRepository;
use think\exception\ValidateException;

/**
 * Class SignSetRepository
 * @package app\common\repositories\sign
 * @mixin SignSetDao
 */
class SignSetRepository extends BaseRepository
{

    public function __construct(SignSetDao $dao)
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



    public function editInfo($info, $data)
    {

        return $this->dao->update($info['id'],$data);
    }

    public function addInfo($companyId,$data)
    {

        $data['company_id'] = $companyId;
        $data['create_at'] = date('Y-m-d H:i:s');
        return $this->dao->create($data);
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


    public function getCong($userInfo,$companyId){
       $list = $this->dao->search([],$companyId)->select();

        /** @var SignRepository $signRepository */
        $signRepository = app()->make(SignRepository::class);
        $day = $signRepository->search(['uuid'=>$userInfo['id']],$companyId)->order('create_at desc')->value('day');
        $total = 10;
        /** @var SignAbcRepository $signAbcRepository */
        $signAbcRepository = app()->make(SignAbcRepository::class);
        $abcDay =$signAbcRepository->search(['uuid'=>$userInfo['id']],$companyId)->whereDay('create_at')->count('id');
        return compact('list','day','total','abcDay');
    }

}