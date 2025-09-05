<?php

namespace app\controller\admin\system\admin;

use app\common\repositories\system\admin\AdminLogRepository;
use app\common\repositories\system\admin\AdminUserRepository;
use app\common\repositories\system\admin\AdminAuthRuleRepository;
use app\controller\admin\Base;
use app\validate\admin\AdminUserValidate;
use think\App;
use think\exception\ValidateException;

class AdminUser extends Base
{
    protected $repository;

    public function __construct(App $app, AdminUserRepository $repository)
    {
        parent::__construct($app);

        $this->repository = $repository;
    }

    /**
     * 修改自己的信息
     *
     * @return string|\think\response\Json|\think\response\View
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function editInfo()
    {
        $adminUserInfo = $this->repository->getLoginUserInfo();
        if ($this->request->isPost()) {
            $param = $this->request->param([
                'username' => '',
                'email' => '',
                'mobile' => ''
            ]);
            try {
                validate(AdminUserValidate::class)->scene('selfEdit')->check($param);
            } catch (ValidateException $e) {
                return json()->data($e->getError());
            }
            $info = $this->repository->get($adminUserInfo['id']);
            try {
                $old = arr_specify_get($info->toArray(), 'id,username,mobile,email');
                $res = $info->allowField(['username', 'mobile', 'email'])->save($param);
                $this->repository->setSessionInfo($info);
                admin_log(2, '修改自己的信息', ['old' => $old, 'new' => $param]);
                if ($res) {
                    return $this->success('修改成功');
                } else {
                    return $this->error('修改失败');
                }
            } catch (\Exception $e) {
                return $this->error('网络错误');
            }
        } else {
            return $this->fetch('system/admin/user/edit_info', [
                'adminUserInfo' => $adminUserInfo
            ]);
        }
    }

    /**
     * 修改密码
     *
     * @return string|\think\response\Json|\think\response\View
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function editPassword()
    {
        if ($this->request->isPost()) {
            $param = $this->request->param([
                'old_password' => '',
                'password' => '',
                're_password' => ''
            ]);
            try {
                validate(AdminUserValidate::class)->scene('editPassword')->check($param);
            } catch (ValidateException $e) {
                return json()->data($e->getError());
            }
            $info = $this->repository->getLoginUserInfo();
            if (!$this->repository->passwordVerify($param['old_password'], $info['password'])) {
                return $this->error('原密码错误');
            }
            try {
                $res = $this->repository->update($info['id'], [
                    'password' => $param['password']
                ]);
                admin_log(2, '修改登录密码');
                if ($res) {
                    $this->repository->clearSessionInfo();
                    return $this->success('修改成功，请重新登录');
                } else {
                    return $this->error('修改失败');
                }
            } catch (\Exception $e) {
                return $this->error('网络错误');
            }
        } else {
            return $this->fetch('system/admin/user/edit_password');
        }
    }

    /**
     * 管理员列表
     *
     * @return string|\think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function list()
    {
        if ($this->request->isAjax()) {
            $where = $this->request->param([
                'keywords' => '',
                'username' => '',
            ]);
            $where['add_admin_id'] = $this->request->adminId;
            [$page, $limit] = $this->getPage();
            $res = $this->repository->getList($where, $page, $limit);
            return json()->data([ 'code' => 0, 'data' => $res['list'], 'count' => $res['count']]);
        } else {
            return $this->fetch('system/admin/user/list', [
                'editAuth' => admin_auth('adminEditAdminUser'),
                'addAuth' => admin_auth('adminAddAdminUser'),
                'delAuth' => admin_auth('adminDelAdminUser'),
                'setAuth' => admin_auth('adminSetAdminUserAuth')
            ]);
        }
    }

    /**
     * 添加管理员
     *
     * @return string|\think\response\Json|\think\response\View
     * @throws \Exception
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $param = $this->request->param([
                'username' => '',
                'email' => '',
                'mobile' => '',
                'account' => '',
                'password' => '',
            ]);
            try {
                validate(AdminUserValidate::class)->scene('add')->check($param);
            } catch (ValidateException $e) {
                return json()->data($e->getError());
            }
            if ($this->repository->fieldExists('account', $param['account'])) {
                return $this->error('此账号已存在');
            }
            try {
                $param['add_admin_id'] = $this->request->adminId;
                $res = $this->repository->create($param);
                admin_log(3, '添加管理员', $param);
                if ($res) {
                    return $this->success('添加成功');
                } else {
                    return $this->error('添加失败');
                }
            } catch (\Exception $e) {
                return $this->error('网络错误');
            }
        } else {
            return $this->fetch('system/admin/user/add');
        }
    }

    /**
     * 编辑管理员
     *
     * @return string|\think\response\Json|\think\response\View
     * @throws \Exception
     */
    public function edit()
    {
        $id = (int)$this->request->param('id');
        if (!$id) {
            return $this->error('参数错误');
        }
        $info = $this->repository->get($id);
        if (!$info || $info['add_admin_id'] != $this->request->adminId) {
            return $this->error('信息错误');
        }
        if ($this->request->isPost()) {
            $param = $this->request->param([
                'id' => '',
                'username' => '',
                'status' => '',
                'email' => '',
                'mobile' => '',
                'account' => '',
                'password' => '',
            ]);
            try {
                validate(AdminUserValidate::class)->scene('edit')->check($param);
            } catch (ValidateException $e) {
                return json()->data($e->getError());
            }
            try {
                $res = $this->repository->update($id, $param);
                $old = arr_specify_get($info->toArray(), 'account,mobile,email,status,username');
                admin_log(3, '修改管理员信息 id:' . $info['id'], ['old' => $old, 'new' => $param]);
                if ($res) {
                    return $this->success('修改成功');
                } else {
                    return $this->error('修改失败');
                }
            } catch (\Exception $e) {
                return $this->error('网络错误');
            }
        } else {
            return $this->fetch('system/admin/user/edit', [
                'info' => $info
            ]);
        }
    }

    /**
     * 删除管理员
     *
     * @return \think\response\Json|\think\response\View
     */
    public function del()
    {
        try {
            validate(AdminUserValidate::class)->scene('del')->check($this->request->param());
        } catch (ValidateException $e) {
            return json()->data($e->getError());
        }
        $ids = (array)$this->request->param('ids');
        try {
            $data = $this->repository->delAdminUser($ids);
            if ($data) {
                admin_log(3, '删除管理员 ids:' . implode(',', $ids), $data);
                return $this->success('删除成功');
            } else {
                return $this->error('删除失败');
            }
        } catch (\Exception $e) {
            return $this->error('网络错误');
        }
    }

    /**
     * 设置管理员权限
     *
     * @return string|\think\response\Json|\think\response\View
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function setAuth()
    {
        $id = (int)$this->request->param('id');
        if (!$id) {
            return $this->error('参数错误');
        }
        $info = $this->repository->get($id);
        if (!$info || $info['add_admin_id'] != $this->request->adminId) {
            return $this->error('信息错误');
        }
        if ($this->request->isPost()) {
            try {
                $old = $info['rules'];
                $rule = implode(',', $this->request->post());
                $info->rules = $rule;
                $res = $info->save();
                admin_log(3, '设置管理员权限 id:' . $info['id'], [
                    'old' => $old,
                    'new' => $rule
                ]);
                if ($res) {
                    return $this->success('设置成功');
                } else {
                    return $this->error('设置失败');
                }
            } catch (\Exception $e) {
                return $this->error('网络错误');
            }
        } else {
            /** @var AdminAuthRuleRepository $authRuleRepository */
            $authRuleRepository = app()->make(AdminAuthRuleRepository::class);
            $res = arr_level_sort($authRuleRepository->getUserMenus($this->request->adminId, false)->toArray(), 2);
            $menus = $this->generateSelectAuth($res, $info);
            return $this->fetch('system/admin/user/set_auth', [
                'info' => $info,
                'authData' => $menus
            ]);
        }
    }

    protected function generateSelectAuth($rules, $ppUserInfo)
    {
        $userRules = explode(',', $ppUserInfo['rules']);
        $arr = [];
        foreach ($rules as $k => $v) {
            $arr2 = [
                'title' => $v['name'],
                'id' => $v['id'],
                'checked' => in_array($v['id'], $userRules)
            ];
            if (isset($v[$v['id']]) && $v[$v['id']]) {
                $arr2['children'] = $this->generateSelectAuth($v[$v['id']], $ppUserInfo);
                $num2 = 0;
                foreach ($arr2['children'] as $k2 => $v2) {
                    if ($v2['checked']) {
                        $num2++;
                    }
                }
                if ($num2 == count($v[$v['id']])) {
                    $arr2['checked'] = true;
                } else {
                    $arr2['checked'] = false;
                }
            }
            $arr[] = $arr2;
        }
        return $arr;
    }

    /**
     * 管理员日志列表
     *
     * @return string|\think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function adminLogList()
    {
        if ($this->request->isAjax()) {
            $where = $this->request->param([
                'account' => '',
                'username' => '',
                'keyword' => ''
            ]);
            [$page, $limit] = $this->getPage();
            /**
             * @var AdminLogRepository $adminLogRepository
             */
            $adminLogRepository = app()->make(AdminLogRepository::class);
            $res = $adminLogRepository->getList($where, $page, $limit);
            return json()->data(['code' => 0, 'data' => $res['list'], 'count' => $res['count']]);
        } else {
            return $this->fetch('system/admin/user/log_list');
        }
    }
}