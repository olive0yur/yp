<?php

namespace app\controller\company\guild;

use app\common\repositories\guild\GuildConfigRepository;
use think\App;
use app\controller\company\Base;


class Config extends Base
{
    protected $repository;

    public function __construct(App $app, GuildConfigRepository $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
        $this->assign([
            'addAuth' => company_auth('companyGuildConfigAdd'),##
            'editAuth' => company_auth('companyGuildConfigEdit'),##
            'delAuth' => company_auth('companyGuildConfigDel'),##
            'statusAuth' => company_auth('companyGuildConfigStatus'),##
        ]);
    }

    public function list()
    {


        if($this->request->isAjax()) {
            $where = $this->request ->param([
                'keywords' => ''
            ]);
            [$page,$limit] = $this->getPage();
            $data = $this->repository->getList($where, $page, $limit, $this->request->companyId);
            return json()->data(['code' => 0,'data' => $data['list'],'count' => $data['count']]);
        }
        return $this->fetch('guild/config/list');
    }

    /**
     * 添加
     */
    public function add()
    {
        if($this->request->isPost()){
            $param = $this->request->param([
                'title' => '',
                'level' => '',
                'gold' => '',
                'people' => '',
            ]);

            try {
                $res = $this->repository->addInfo($this->request->companyId,$param);
                company_user_log(4, '添加店长', $param);
                if($res) {
                    return $this->success('添加成功');
                } else{
                    return $this->error('添加失败');
                }
            } catch (\Exception $e) {
                return $this->error('网络失败');
            }
        } else {
            return $this->fetch('guild/config/add');
        }
    }

    /**
     * 编辑
     */

    public function edit()
    {
        $id = (int)$this->request->param('id');
        if (!$id) {
            return $this->error('参数错误');
        }
        $info = $this->repository->getDetail($id);
        if (!$info) {
            return $this->error('信息错误');
        }
        if ($this->request->isPost()) {
            $param = $this->request->param([
                'id' => '',
                'title' => '',
                'level' => '',
                'gold' => '',
                'people' => '',
                'rate' => '',
            ]);

            try {
                $res = $this->repository->editInfo($info, $param);
                if ($res) {
                    company_user_log(4, '编辑代理 id:' . $info['id'], $param);
                    return $this->success('修改成功');
                } else {
                    return $this->error('修改失败');
                }
            } catch (\Exception $e) {
                return $this->error('网络错误');
            }
        } else {
            return $this->fetch('guild/config/edit', [
                'info' => $info,
            ]);
        }
    }

    /**
     * 删除
     */
    public function del()
    {
        $ids = (array)$this->request->param('ids');
        try {
            $data = $this->repository->batchDelete($ids);
            if ($data) {
                company_user_log(4, '删除公会配置 ids:' . implode(',', $ids), $data);
                return $this->success('删除成功');
            } else {
                return $this->error('删除失败');
            }
        } catch (\Exception $e) {
            return $this->error('网络失败');
        }
    }

    /**
     * 状态修改
     */
    public function switchStatus()
    {
        $id = (int)$this->request->param('id');
        if ($this->request->isPost()) {
            $param = $this->request->param([
                'id' => '',
                'status' => '',
            ]);

            if (!$this->repository->exists($id)) {
                return $this->error('数据不存在');
            }
            try {
                $res = $this->repository->update($id, $param);
                company_user_log(3, '修改公会配置 id:' . $id, $param);
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




}