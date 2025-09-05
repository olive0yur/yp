<?php

namespace app\common\repositories\sign;

use app\common\dao\sign\SignDao;
use app\common\repositories\BaseRepository;
use app\common\repositories\users\UsersRepository;
use think\exception\ValidateException;
use think\facade\Db;

/**
 * Class SignRepository
 * @package app\common\repositories\sign
 * @mixin SignDao
 */
class SignRepository extends BaseRepository
{

    public function __construct(SignDao $dao)
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

    public function sign($userInfo,$companyId){

        $sign = $this->dao->search(['uuid'=>$userInfo['id']],$companyId)->whereDay('create_at')->find();
        if($sign) throw new ValidateException('今日已签到');

        $yest = $this->dao->search(['uuid'=>$userInfo['id']],$companyId)->whereDay('create_at','yesterday')->find();
        $day = 1;
        if($yest){
            $day = $day + $yest['day'];
        }

        /** @var SignSetRepository $signSetRepository */
        $signSetRepository = app()->make(SignSetRepository::class);

        $max = $signSetRepository->search([],$companyId)->where('day','>',$day)->find();
        if($max) $day = 1;

        $set = $signSetRepository->search(['day'=>$day],$companyId)->find();
        if($set){
            /** @var UsersRepository $usersRepository */
            $usersRepository = app()->make(UsersRepository::class);
            $usersRepository->batchFoodChange($userInfo['id'],4,$set['num'],['remark'=>'签到'],4);
        }
        $arr['uuid'] = $userInfo['id'];
        $arr['day'] = $day;
        return $this->addInfo($companyId,$arr);

    }





}