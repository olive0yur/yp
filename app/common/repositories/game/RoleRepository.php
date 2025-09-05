<?php

namespace app\common\repositories\game;

use app\common\dao\game\RoleDao;
use app\common\repositories\BaseRepository;
use app\common\repositories\users\UsersRepository;
use think\exception\ValidateException;
use think\validate\ValidateRule;

/**
 * Class RoleRepository
 * @package app\common\repositories\RoleRepository
 * @mixin RoleDao
 */
class RoleRepository extends BaseRepository
{

    public function __construct(RoleDao $dao)
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



    public function addInfo($companyId,$data)
    {
        $data['company_id'] = $companyId;
        $data['create_at'] = date('Y-m-d H:i:s');
        return $this->dao->create($data);
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

    public function getFalling($userInfo,$companyId){
        $data['stone_lv'] = web_config($companyId,'game.stone_lv');
        $data['exchange'] = web_config($companyId,'game.exchange');
        return compact('data');
    }

    public function exchange($data,$userInfo,$companyId){
        $exchange = web_config($companyId,'game.exchange','');
        if(!$exchange) throw new ValidateException('兑换比例设置错误!');
        switch ($data['type']){
            case 1:  //仙玉兑换灵石
                $change =round($data['num'] / $exchange,7);
                if($userInfo['food'] < $change) throw new ValidateException('余额不足,无法兑换');
                if(!$data['pay_password']) throw new ValidateException('请输入交易密码!');
                /** @var UsersRepository $usersRepository */
                $usersRepository = app()->make(UsersRepository::class);
                $verfiy = $usersRepository->passwordVerify($data['pay_password'], $userInfo['pay_password']);
                if (!$verfiy) throw new ValidateException('交易密码错误!');

                /** @var UsersRepository $usersRepository */
                $usersRepository = app()->make(UsersRepository::class);
                $usersRepository->foodChange($userInfo['id'],3,(-1)*$change,['remark'=>'兑换灵石'],4,$companyId);
                return $usersRepository->jadeChange($userInfo['id'],4,$data['num'],['remark'=>'仙玉兑换'],4,$companyId);
            case 2: // 灵石兑换仙玉
                $change = round($data['num'] * $exchange,7);
                if($userInfo['jade'] < $change) throw new ValidateException('灵石不足,无法兑换');
                if(!$data['pay_password']) throw new ValidateException('请输入交易密码!');
                /** @var UsersRepository $usersRepository */
                $usersRepository = app()->make(UsersRepository::class);
                $verfiy = $usersRepository->passwordVerify($data['pay_password'], $userInfo['pay_password']);
                if (!$verfiy) throw new ValidateException('交易密码错误!');
                /** @var UsersRepository $usersRepository */
                $usersRepository = app()->make(UsersRepository::class);
                $usersRepository->jadeChange($userInfo['id'],3,(-1)*$change,['remark'=>'转换仙玉'],4,$companyId);
                return $usersRepository->foodChange($userInfo['id'],4,$data['num'],['remark'=>'灵石兑换'],4,$companyId);
        }
    }
}