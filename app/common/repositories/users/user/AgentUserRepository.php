<?php

namespace app\common\repositories\users\user;

use app\common\dao\users\user\AgentUserDao;
use app\common\repositories\agent\AgentRepository;
use app\common\repositories\BaseRepository;
use app\common\services\CacheService;
use think\exception\ValidateException;
use think\facade\Cache;
use think\facade\Session;

class AgentUserRepository extends BaseRepository
{
    // session名称
    const SESSION_NAME = 'agent_user_info';

    public function __construct(AgentUserDao $dao)
    {
        $this->dao = $dao;
    }



    /**
     * 密码验证
     *
     * @param $password
     * @param string $userPassword 用户密码
     * @return string
     */
    public function passwordVerify($password, $userPassword)
    {
        return password_verify($password, $userPassword);
    }

    /**
     * 设置session信息
     *
     * @param array $info 管理员信息
     */
    public function setSessionInfo($info)
    {
        CacheService::delete('agent_user_menus_' . $info['id']);
        CacheService::delete('agent_user_rules_' . $info['id']);
        Session::set(self::SESSION_NAME, $info);
    }

    /**
     * 获取session信息
     *
     * @return mixed
     */
    public function getSessionInfo()
    {
        return Session::get(self::SESSION_NAME);
    }

    /**
     * 清除session信息
     *
     */
    public function clearSessionInfo()
    {
        CacheService::delete('agent_user_menus_' . self::getLoginAdminId());
        CacheService::delete('agent_user_rules_' . self::getLoginAdminId());

        Session::delete(self::SESSION_NAME);
    }

    /**
     * 是否登陆
     *
     * @return bool
     */
    public function isLogin()
    {
        return !empty(Session::get(self::SESSION_NAME));
    }

    /**
     * 管理员登录
     *
     * @param string $account 账号
     * @param string $password 密码
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function login($account, $password,$companyId)
    {
        $adminInfo = $this->dao->getInfoByAccount($account,$companyId);
        if (empty($adminInfo)) {
            throw new ValidateException('账号不存在或密码错误');
        }

        if (!$this->passwordVerify($password, $adminInfo['password'])) {
            throw new ValidateException('账号不存在或密码错误');
        }
        /** @var AgentRepository $agentRepository */
        $agentRepository = app()->make(AgentRepository::class);
        $agent = $agentRepository->search([])->where(['uuid'=>$adminInfo['id']])->find();
        if(!$agent) throw new ValidateException('您无此权限!');
        if ($adminInfo['status'] != 1) {
            throw new ValidateException('账号已被禁止登陆');
        }
        event('agent.login', $adminInfo);

        return $adminInfo;
    }

    /**
     * 获取登陆用户信息
     *
     * @return mixed
     */
    public function getLoginUserInfo()
    {
        return Session::get(self::SESSION_NAME);
    }

    /**
     * 获取登录管理员id
     *
     * @return int|mixed
     */
    public function getLoginAdminId()
    {
        return $this->getLoginUserInfo()['id'] ?? 0;
    }

    /**
     * 获取登录管理员企业id
     *
     * @return int|mixed
     */
    public function getLoginCompanyId()
    {
        return $this->getLoginUserInfo()['company_id'] ?? 0;
    }

    /**
     * 获取登录管理员token
     *
     * @return int|mixed
     */
    public function getLoginAdminToken()
    {
        return $this->getLoginUserInfo()['session_id'] ?? '';
    }

    /**
     * 获取菜单
     *
     * @throws \throwable
     */
    public function getMenus()
    {
        $userInfo = $this->getLoginUserInfo();


        $data = CacheService::init($userInfo['company_id'])->remember('agent_user_menus_' . $userInfo['id'], function () use ($userInfo) {
            /**
             * @var AgentAuthRuleRepository $repository
             */
            $repository = app()->make(AgentAuthRuleRepository::class);
            $res = $repository->getUserMenus($userInfo['id']);
            if ($res) {
                $res = $res->toArray();
            }

            $res = arr_level_sort($res, 2);
            $menus = $this->generateLayuiAdminMenu($res);

            $homeInfo = [
                'title' => '首页',
                'href' => (string)url('agentIndexWelcome')
            ];
            return [
                'homeInfo' => $homeInfo,
                'menuInfo' => $menus
            ];
        });

        return $data;
    }

    /**
     * 生成layuiadmin菜单
     *
     * @param array $rules 菜单
     * @return array
     */
    public function generateLayuiAdminMenu($rules)
    {
        $arr = [];
        foreach ($rules as $k => $v) {
            $arr2 = [
                'title' => $v['name'],
                'icon' => $v['icon'] ?? 'layui-icon-more-vertical',
                'target' => '_self'
            ];
            $arr2['href'] = $v['rule'] ? ltrim((string)url($v['rule']), '/') : '';
            if (isset($v[$v['id']]) && $v[$v['id']]) {
                $arr2['child'] = $this->generateLayuiAdminMenu($v[$v['id']]);
            }
            $arr[] = $arr2;
        }
        return $arr;
    }

    /**
     * 检测管理员权限
     *
     * @param string $url 操作地址
     * @return bool
     */
    public function checkAuth($url)
    {
        $url = strtolower($url);
        $adminInfo = $this->getLoginUserInfo();
        if (empty($adminInfo)) {
            return false;
        }

        $rules = CacheService::init($adminInfo['company_id'])->remember('agent_user_rules_' . $adminInfo['id'], function () use ($adminInfo) {
            /**
             * @var AgentAuthRuleRepository $repository
             */
            $repository = app()->make(AgentAuthRuleRepository::class);
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

    /**
     * @param array $where
     * @param $page
     * @param $limit
     * @return array
     */
    public function getList(array $where, $page, $limit)
    {
        $query = $this->dao->search($where);
        $count = $query->count();
        $list = $query->page($page, $limit)
            ->field('id,account,add_time,email,last_login_ip,last_login_time,mobile,username,status')
            ->select();

        foreach ($list as $k => $v) {
            $list[$k]['last_login_time'] = (int)$v['last_login_time'] > 0 ? date('Y-m-d H:i:s', $v['last_login_time']) : '暂未登陆';
        }
        return compact('list', 'count');
    }

    /**
     * 更新
     * @param int $id id
     * @param array $data 数组
     * @return int
     */
    public function update(int $id, array $data)
    {
        if (isset($data['password'])) {
            if ($data['password'] !== '') {
                $data['password'] = $this->passwordEncrypt($data['password']);
            } else {
                unset($data['password']);
            }
        }
        return $this->dao->update($id, $data);
    }

    /**
     * 修改
     */
    public function updateWhere(int $id, array $data)
    {
        if (isset($data['password'])) {
            if ($data['password'] !== '') {
                $data['password'] = $this->passwordEncrypt($data['password']);
            } else {
                unset($data['password']);
            }
        }
        return $this->dao->whereUpdate(['company_id'=>$id], $data);
    }

    /**
     * 删除管理员
     * @param array $ids ID
     * @return array
     */
    public function delCompanyUser(array $ids)
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
     * 删除管理员
     * @param array $ids ID
     * @return array
     */
    public function delCompanyUserId(array $ids)
    {
        $list = $this->dao->selectWhere([
            ['company_id', 'in', $ids]
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
     * 获取企业主账号
     */
    public function getCmopanyMainUserInfo($companyId)
    {
        return $this->dao->getWhere([
            'company_id' => $companyId,
            'is_main' => 1
        ]);
    }
}