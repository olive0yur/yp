<?php

namespace app\controller\company\system;

use think\App;
use app\controller\company\Base;
use think\exception\ValidateException;
use app\validate\system\SystemPactValidate;
use app\common\repositories\system\SystemPactRepository;
use think\facade\Db;

class SystemPact extends Base
{
    protected $repository;

    public function __construct(App $app, SystemPactRepository $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
    }

    /**
     * 平台协议列表
     */
    public function list()
    {
        if($this->request->isAjax()) {
            $where = $this->request ->param([
                'keywords' => ''
            ]);
            [$page, $limit] = $this->getPage();
            $data = $this->repository->getList($where,$page,$limit,$this->request->companyId);
            return json()->data(['code' => 0,'data' => $data['list'],'count' => $data['count']]);
        }
        return $this->fetch('system/pact/list', [
            'addAuth' => company_auth('companySystemPactAdd'),
            'editAuth' => company_auth('companySystemPactEdit'),
            'delAuth' => company_auth('companySystemPactDel')
        ]);
    }

    /**
     * 添加协议
     */
    public function add()
    {
        if($this->request->isPost()) {
            $param = $this->request->param([
                'pact_type' => '',
                'content' => '',
            ]);
            try {
                validate(SystemPactValidate::class)->scene('add')->check($param);
            } catch (ValidateException $e) {
                return json()->data($e->getError());
            }
            try {
                if ($this->repository->companyFieldExists($this->request->companyId, 'pact_type', $param['pact_type'])) {
                    return $this->error('该类型协议已存在');
                }
                $param['content'] = htmlspecialchars($param['content']);
                $param['company_id'] = $this->request->companyId;
                $res = $this->repository->addInfo($param);
                company_user_log(2, '添加协议', $param);
                if($res) {
                    return $this->success('添加成功');
                } else {
                    return $this->error('添加失败');
                }
            } catch(\Exception $e) {
                return $this->error('网络错误'.$e->getMessage());
            }
        } else {
            return $this->fetch('system/pact/add',[
                'pactType' => $this->repository::PACT_TYPE
            ]);
        }
    }

    /**
     * 编辑协议内容
     */
    public function edit()
    {
        $id = $this->request->param('id');
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
                'content' => '',
            ]);
            try {
                validate(SystemPactValidate::class)->scene('edit')->check($param);
            } catch (ValidateException $e) {
                return json()->data($e->getError());
            }
            try {
                $param['content'] = htmlspecialchars($param['content']);
                $res = $this->repository->editInfo($id, $param);
                if ($res) {
                    company_user_log(2, '编辑协议 id:' . $info['id'], $param);
                    return $this->success('修改成功');
                } else {
                    return $this->error('修改失败');
                }
            } catch (\Exception $e) {
                return $this->error('网络失败');
            }
        }
        return $this->fetch('system/pact/edit',[
            'info' => $info
        ]);
    }

    /**
     * 查看协议内容 
     */

    public function details()
    {
        $id = $this->request->param('id');
        $getContent = $this->repository->getContent($id, $this->request->companyId);
        return $this->fetch('system/pact/details', [
            'getContent' => $getContent
        ]);
    }

    /**
     * 删除协议
     */
    public function del()
    {
        $id = (int)$this->request->param('id');
        $res = $this->repository->delete($id);
        if($res) {
            return $this->success('删除成功');
        } else {
            return $this->error('删除失败');
        }
    }
}