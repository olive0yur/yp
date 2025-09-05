<?php

namespace app\controller\admin\system\company;

use app\common\repositories\company\user\CompanyUserRepository;
use app\common\repositories\company\CompanyAuthRuleRepository;
use app\common\repositories\company\CompanyRepository;
use app\common\model\company\user\CompanyUser;
use app\validate\admin\CompanyManageValidate;
use think\exception\ValidateException;
use app\controller\admin\Base;
use Exception;
use think\App;

class CompanyManage extends Base
{
    protected $repository;
    protected $repositorys;

    public function __construct(App $app, CompanyRepository $repository, CompanyUserRepository $repositorys)
    {
        parent::__construct($app);
        $this->repository = $repository;
        $this->repositorys = $repositorys;
        $this->assign([
            'editAuth' => admin_auth('adminCompanyEdit'),
            'addAuth' => admin_auth('adminCompanyAdd'),
            'delAuth' => admin_auth('adminCompanyDel'),
            'setAuth' => admin_auth('adminCompanySetAuth'),
        ]);
    }


    /**
     * 企业列表
     * @return string|\think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function list()
    {
        if ($this->request->isAjax()) {
            $where = $this->request->param([
                'name' => '',
                'keyword' => '',
                'address' => '',
            ]);
            [$page, $limit] = $this->getPage();
            $res = $this->repository->getAdminList($where, $page, $limit);
            return json()->data(['code' => 0, 'data' => $res['list'], 'count' => $res['count']]);
        } else {
            return $this->fetch('company/company/list');
        }
    }

    /**
     * 添加企业
     *
     * @return string|\think\response\Json|\think\response\View
     * @throws \Exception
     * CompanyUserValidate
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $param = $this->request->param([
                'username' => '',
                'name' => '',
                'mobile' => '',
                'address' => '',
                'key_code' => '',
                'desc' => '',
                'password' => '',
                'account' => '',
            ]);
            try {
                validate(CompanyManageValidate::class)->scene('add')->check($param);
            } catch (ValidateException $e) {
                return json()->data($e->getError());
            }

            if ($this->repository->fieldExists('name', $param['name'])) {
                return $this->error( $param['name'] .'公司名称已存在');
            }
            if ($this->repository->fieldExists('mobile', $param['mobile'])) {
                return $this->error( $param['mobile']. '手机号已存在');
            }
            try {
                $res = $this->repository->create($param);
                $arr = [
                    'account' => $res->account,
                    'password' => $res->password,
                    'mobile' => $res->mobile,
                    'company_id' => $res->id,
                    'is_main' => 1
                ];
                $res = $this->repositorys->create($arr);
                admin_log(4, '添加企业信息', $param);
                if ($res) {
                    return $this->success('添加成功');
                } else {
                    return $this->error('添加失败');
                }
            } catch (\Exception $e) {
                return $this->error($e->getMessage());
            }
        } else {
            return $this->fetch('company/company/add');
        }
    }

    /**
     * 编辑企业
     */
    public function edit()
    {
        $id = (int)$this->request->param('id');
        if (!$id) {
            return $this->error('参数错误');
        }
        $info = $this->repository->get($id);
        if ($this->request->isPost()) {
            $param = $this->request->param([
                'id' => '',
                'username' => '',
                'status' => '',
                'name' => '',
                'mobile' => '',
                'address' => '',
                'key_code' => '',
                'desc' => '',
                'password' => ''
            ]);
            try {
                validate(CompanyManageValidate::class)->scene('edit')->check($param);
            } catch (ValidateException $e) {
                return json()->data($e->getError());
            }
            try {
                $arr = [
                    'account' => $param['username'],
                    'mobile' => $param['mobile'],
                    'status' => $param['status'],
                    'password' => $param['password']
                ];
                if (isset($param['password'])) {
                    unset($param['password']);
                }
                $res = $this->repository->update($id, $param);
                $res = $this->repositorys->updateWhere($id, $arr);
                $old = arr_specify_get($info->toArray(), 'username,mobile,name,status,address,desc');
                $old = arr_specify_get($info->toArray(), 'account,mobile,status');
                admin_log(4, '修改企业信息 id:' . $info['id'], ['old' => $old, 'new' => $param, 'new' => $arr]);
                if ($res !== false) {
                    return $this->success('修改成功');
                } else {
                    return $this->error('修改失败');
                }
            } catch (\Exception $e) {
                return $this->error('网络错误');
            }
        } else {
            return $this->fetch('company/company/edit', [
                'info' => $info
            ]);
        }
    }

    /**
     * 修改状态
     */
    public function status()
    {
        $id = (int)$this->request->param('id');
        if (!$id) {
            return $this->error('参数错误');
        }
        $info = $this->repository->get($id);
        if ($this->request->isPost()) {
            $param = $this->request->param([
                'id' => '',
                'status' => '',
            ]);
            try {
                $arr = [
                    'status' => $param['status']
                ];
                $res = $this->repository->update($id, $param);
                $res = $this->repositorys->updateWhere($id, $arr);
                $old = arr_specify_get($info->toArray(), 'status');
                admin_log(4, '修改状态 id:' . $info['id'], ['old' => $old, 'new' => $param]);
                if ($res) {
                    return $this->success('修改成功');
                } else {
                    return $this->error('修改失败');
                }
            } catch (\Exception $e) {
                return $this->error('网络错误');
            }
        } else {
            return $this->fetch('company/company/index', [
                'info' => $info
            ]);
        }
    }

    /**
     * 设置权限
     */
    public function setAuth()
    {
        $id = (int)$this->request->param('id');
        if (!$id) {
            return $this->error('参数错误');
        }
        $info = $this->repositorys->getCmopanyMainUserInfo($id);
        if (!$info) {
            return $this->error('信息错误');
        }
        if ($this->request->isPost()) {
            try {
                $old = $info['rules'];
                $rule = implode(',', $this->request->post());
                $info->rules = $rule;
                $res = $info->save();
                admin_log(4, '设置权限 id:' . $info['id'], [
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
            /**
             * @var CompanyAuthRuleRepository $CompanyAuthRuleRepository
             */
            $CompanyAuthRuleRepository = app()->make(CompanyAuthRuleRepository::class);
            $res = arr_level_sort($CompanyAuthRuleRepository->getMenuList([])->toArray(), 2);
            $menus = $this->generateSelectAuth($res, $info);
            return $this->fetch('company/company/set_auth', [
                'info' => $info,
                'authData' => $menus
            ]);
        }
    }

    protected function generateSelectAuth($rules, $ppUserInfo)
    {
        $userRules = explode(',', $ppUserInfo['rules']);
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
     * 删除企业
     * (array $ids)
     * @param array $ids — ID
     */
    public function del()
    {
        try {
            validate(companyManageValidate::class)->scene('del')->check($this->request->param());
        } catch (ValidateException $e) {
            return json()->data($e->getError());
        }

        $ids = (array)$this->request->param('ids');
        try {
            $data = $this->repository->delCompanyRepository($ids);
            $data = $this->repositorys->delCompanyUserId($ids);
            if ($data) {
                admin_log(4, '删除企业 ids:' . implode(',', $ids), $data);
                return $this->success("删除成功");
            } else {
                return $this->error("删除失败");
            }
        } catch (\Exception $e) {
            return $this->error("网络错误");
        }
    }

    protected function getCompanyUser(): string
    {
        return CompanyUser::class;
    }
}