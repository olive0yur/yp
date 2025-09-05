<?php

namespace app\controller\company\users;

use app\common\repositories\users\UsersRepository;
use think\App;
use app\controller\company\Base;
use think\exception\ValidateException;
use app\validate\users\UsersCertValidate;
use app\common\repositories\users\UsersCertRepository;

class UsersCert extends Base
{

    protected $repository;

    public function __construct(App $app, UsersCertRepository $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
    }

    public function list()
    {
        if ($this->request->isAjax()) {
            $where = $this->request->param([
                'cert_status' => '',
                'mobile' => '',
                'keywords' => ''
            ]);
            [$page, $limit] = $this->getPage();
            $res = $this->repository->getList($where, $page, $limit, $this->request->companyId);
            return json()->data(['code' => 0, 'data' => $res['list'], 'count' => $res['count'], 'statusCount' => $res['statusCount']]);
        }
        return $this->fetch('users/cert/index', [
            'delAuth' => company_auth('companyUsersCertDel'),
            'editAuth' => company_auth('companyUsersCertDel'),
            'examineAuth' => company_auth('companyUsersCertExamine')
        ]);
    }

    /**
     * 编辑个人认证
     */
    public function edit()
    {
        $id = (int)$this->request->param('id');
        if (!$id) {
            return $this->error('参数错误');
        }
        $info = $this->repository->userCertInfo($id);
        if (!$info) {
            return $this->error('信息错误');
        }
        if ($this->request->isPost()) {
            $param = $this->request->param([
                'username' => '',
                'number' => '',
                'idcard_front_photo' => '',
                'idcard_back_photo' => '',
                'cert_status' => 1,
                'remark' => '',
            ]);
            try {
                validate(UsersCertValidate::class)->scene('edit')->check($param);
            } catch (ValidateException $e) {
                return json()->data($e->getError());
            }
            if ($this->repository->fieldExists('number', $param['number'], $info['id'])) {
                return $this->error($param['number'] . '已存在');
            }
            try {
                $res = $this->repository->editInfo($info['id'], $param);
                if ($res) {
                    company_user_log(3, '修改个人认证 id:' . $info['id'], $param);
                    return $this->success('修改成功');
                } else {
                    return $this->error('修改失败');
                }
            } catch (\Exception $e) {
                return $this->error('网络错误' . $e->getMessage());
            }
        } else {
            return $this->fetch('users/cert/edit', [
                'info' => $info
            ]);
        }
    }


    /**
     * 删除个人认证
     */
    public function certDel()
    {
        $id = $this->request->param('id');
        if (!$id) {
            return $this->error('参数错误');
        }
        $info = $this->repository->get($id);
        if (!$info) {
            return $this->error('信息错误');
        }
        try {
            $res = $this->repository->delete($id, $info);
            app()->make(UsersRepository::class)->search([], $this->request->companyId)
                ->where(['cert_id' => $info['id']])
                ->update(['cert_id' => 0]);
            if ($res) {
                company_user_log(4, '删除个人认证 id:' . $id, $res);
                return $this->success('删除成功');
            } else {
                return $this->error('删除失败');
            }
        } catch (\Exception $e) {
            exception_log('删除个人认证信息', $e);
            return $this->error('网络错误');
        }

    }

    /**
     * 审核个人认证
     */
    public function examineCert()
    {

        $id = $this->request->param('id');
        if (!$id) {
            return $this->error('参数错误');
        }
        $info = $this->repository->userCertInfo($id);
        if (!$info) {
            return $this->error('信息错误');
        }
        if ($this->request->isPost()) {
            $param = $this->request->param([
                'cert_status' => '',
                'remark' => '',
            ]);
            $param['review_status'] = 2;
            $param['review_time'] = date('Y-m-d H:i:s');

            try {
//                if ($info['mobile']) {
//                    app()->make(UsersRepository::class)->update($info['user_id'], [
//                        'mobile' => $info['mobile'],
//                        'unquied' => $this->request->companyId . $info['mobile']
//                    ]);
//                }
                if ($param['cert_status'] == 3) {
                    app()->make(UsersRepository::class)->search([], $this->request->companyId)
                        ->where(['cert_id' => $info['id']])
                        ->update(['cert_id' => 0]);
                }
                validate(UsersCertValidate::class)->scene('examine')->check($param);
                $res = $this->repository->update($info['id'], $param);
                if ($res) {
                    company_user_log(4, '个人认证审核: id' . $id, $param);
                    return $this->success('操作成功');
                } else {
                    return $this->error('操作失败');
                }
            } catch (ValidateException $e) {
                return $this->error($e->getMessage());
            }
        }
        return $this->fetch('users/cert/examine_cert', [
            'info' => $info
        ]);
    }

}