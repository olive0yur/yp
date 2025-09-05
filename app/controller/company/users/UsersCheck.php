<?php

namespace app\controller\company\users;

use app\common\repositories\users\CheckIn;
use think\App;
use app\controller\company\Base;
use think\exception\ValidateException;
use app\common\repositories\users\UsersLabelRepository;
use app\validate\users\UsersLabelValidate;

class UsersCheck extends Base
{

    protected $repository;

    public function __construct(App $app, CheckIn $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
    }

    public function list()
    {
        if ($this->request->isAjax()) {
            $where = $this->request->param([
                'keywords' => '',
                'status'=>''
            ]);
            [$page, $limit] = $this->getPage();
            $res = $this->repository->getList($where, $page, $limit,$this->request->companyId);
            return json()->data([ 'code' => 0, 'data' => $res['list'], 'count' => $res['count'] ]);
        }
        return $this->fetch('users/check/index', [
            'switchStatusAuth' => company_auth('companyUsershCheckRule')
        ]);
    }


    public function add()
    {
        if ($this->request->isPost()) {
            $param = $this->request->param([
                'name' => '',
                'sort' => '',
                'is_show' => '',
            ]);
            try {
                validate(UsersLabelValidate::class)->scene('add')->check($param);
            } catch (ValidateException $e) {
                return json()->data($e->getError());
            }
            if ($this->repository->fieldExists('name', $param['name'])) {
                return $this->error($param['name'] . '已存在');
            }
            try {
                $res = $this->repository->addInfo($this->request->companyId,$param);
                company_user_log(2, '添加用户标签', $param);
                if ($res) {
                    return $this->success('添加成功');
                } else {
                    return $this->error('添加失败');
                }
            } catch (\Exception $e) {
                return $this->error('网络失败');
            }
        } else {
            return $this->fetch('users/label/add');
        }
    }


    public function edit()
    {
        $id = (int)$this->request->param('id');
        if (!$id) {
            return $this->error('参数错误');
        }
        $info = $this->repository->get($id);
        if (!$info) {
            return $this->error('信息错误');
        }
        if ($this->request->isPost()) {
            $param = $this->request->param([
                'id' => '',
                'name' => '',
                'is_show' => '',
                'sort' => ''
            ]);
            try {
                validate(UsersLabelValidate::class)->scene('edit')->check($param);
            } catch (ValidateException $e) {
                return json()->data($e->getError());
            }
            if ($this->repository->companyFieldExists($this->request->companyId,'name', $param['name'], $id)) {
                return $this->error($param['name'] . '已存在');
            }
            try {
                $res = $this->repository->editInfo($info, $param);
                if ($res) {
                    company_user_log(3, '修改标签信息 id:' . $info['id'], $param);
                    return $this->success('修改成功');
                } else {
                    return $this->error('修改失败');
                }
            } catch (\Exception $e) {
                return $this->error('网络错误');
            }
        } else {
            return $this->fetch('users/label/edit', [
                'info' => $info
            ]);
        }
    }

    public function save()
    {
        $id = (int)$this->request->param('ids');
        if ($this->request->isPost()) {
            $param = $this->request->param([
                'status' => '',
            ]);
            if (!$this->repository->exists($id)) {
                return $this->error('数据不存在');
            }
            try {
                $res = $this->repository->update($id, $param);
                if ($res) {
                    return $this->success('修改成功');
                } else {
                    return $this->error('修改失败');
                }
            } catch (\Exception $e) {
                return $this->error('网络错误');
            }
        }
    }

    public function del()
    {
        $ids = (array)$this->request->param('ids');
        try {
            $data = $this->repository->delete($ids);
            if ($data) {
                company_user_log(5, '删除标签 ids:' . implode(',', $ids), $data);
                return $this->success('删除成功');
            } else {
                return $this->error('删除失败');
            }
        } catch (\Exception $e) {
            return $this->error('网络失败');
        }
    }
}