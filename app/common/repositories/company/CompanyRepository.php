<?php

namespace app\common\repositories\company;

use think\facade\Db;
use app\common\dao\company\CompanyDao;
use app\common\repositories\BaseRepository;
use app\common\repositories\company\user\CompanyUserRepository;

class CompanyRepository extends BaseRepository
{
    // session名称
    const SESSION_NAME = 'proerty_info';

    public function __construct(CompanyDao $dao)
    {
        $this->dao = $dao;
    }
    /**
     * @param array $where
     * @param $page
     * @param $limit
     * @return array
     */
    public function getAdminList(array $where, $page, $limit)
    {
        $query = $this->dao->search($where);
        $count = $query->count();
        $list = $query->page($page, $limit)
            ->field('id,name,mobile,add_time,address,username,picture,expire_time,status,key_code')
            ->append(['main_account'])
            ->order('id desc')
            ->select();
        return compact('list', 'count');
    }

    /**
     * 获取登录用户信息
     */
    public function getLoginUserInfo()
    {
        return Session::get(self::SESSION_NAME);
    }

    /**
     * 检测权限
     */
    public function chenckAuth($url)
    {
        $url = strtolower($url);
        $adminInfo = $this->getLoginUserInfo();
        if (empty($adminInfo)) {
            return false;
        }
        $rules = app('cache')->remember('company_user_rules_' . $adminInfo['id'], function () use ($adminInfo) {
            $repository = app()->make(CompanyAuthRuleRepository::class);
            $rules = $repository->getRules(explode(',', $adminInfo['rules']));
            foreach ($rules as $k => $v) {
                $rules[$k] = strtolower($v);
            }
            return $rules;
        });
        if (in_array($url, $rules)) {
            return true;
        }
        return false;
    }

    public function editInfo($info, $data)
    {
        return Db::transaction(function () use ($info,$data) {
            if (isset($data['password'])) {
                unset($data['password']);
            }
            if (isset($data['key_code'])) {
                unset($data['key_code']);
            }
            $this->dao->update($info['id'], $data);

            /** @var CompanyUserRepository $companyUserRepository */
            $companyUserRepository = app()->make(CompanyUserRepository::class);
            $arr = [
                'account' => $data['mobile'],
                'mobile' => $data['mobile'],
                'status' => $data['status'],
                'password' => $data['password']
            ];
            $companyUserRepository->updateWhere($info['id'],$arr);
            return true;
        });
    }

    /**
     * 删除企业
     */
    public function delCompanyRepository(array $ids)
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



    /**
     * 获取所有组
     *
     * @param int|null $companyId
     * @param $status
     * @return array|\think\Collection|\think\db\BaseQuery[]
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getAll($status = '')
    {
        $query = $this->dao->search([
            'status' => $status
        ]);
        $list = $query->select();
        return $list;
    }

    public function getCompanyData($status = '')
    {
        $list = $this->getAll($status);
        $list = convert_arr_key($list, 'id');
        return formatCascaderData($list, 'name');
    }
}
