<?php

namespace app\controller\company\guild;

use app\common\repositories\agent\AgentRepository;
use app\common\repositories\guild\GuildConfigRepository;
use app\common\repositories\guild\GuildRepository;
use app\common\repositories\users\UsersRepository;
use think\App;
use app\controller\company\Base;
use think\exception\ValidateException;


class Guild extends Base
{
    protected $repository;

    public function __construct(App $app, GuildRepository $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
        $this->assign([
            'addAuth' => company_auth('companyGuildAdd'),##
            'editAuth' => company_auth('companyGuildEdit'),##
            'delAuth' => company_auth('companyGuildDel'),##
            'childAuth' => company_auth('companyGuildChild'),##
        ]);
    }

    protected function commonParams()
    {
        /**
         * @var UsersRepository $usersRepository
         */
        $usersRepository = app()->make(UsersRepository::class);
        $userList = $usersRepository->search([],$this->request->companyId)
            ->field('id,mobile,nickname')->select();
        $config = app()->make(GuildConfigRepository::class)->search([],$this->request->companyId)
            ->field('id,level,title')->select();
        $this->assign([
            'userList' => $userList,
            'configList' => $config
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
        return $this->fetch('guild/guild/list');
    }

    /**
     * 添加常见问题
     */
    public function add()
    {
        if($this->request->isPost()){
            $param = $this->request->param([
                'uuid' => '',
                'guild_name' => '',
                'level' => '',
                'guild_mark' => '',
            ]);

            try {
                $param['update_time'] = date('Y-m-d H:i:s');
                $res = $this->repository->addInfo($this->request->companyId,$param);
                company_user_log(4, '添加公会', $param);
                if($res) {
                    return $this->success('添加成功');
                } else{
                    return $this->error('添加失败');
                }
            } catch (\Exception $e) {
                return $this->error('网络失败');
            }
        } else {
            $this->commonParams();
            return $this->fetch('guild/guild/add');
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
                'uuid' => '',
                'guild_name' => '',
                'level' => '',
                'guild_mark' => '',
            ]);

//            try {
                $res = $this->repository->editInfo($info, $param);
                if ($res) {
                    company_user_log(4, '编辑公会 id:' . $info['id'], $param);
                    return $this->success('修改成功');
                } else {
                    return $this->error('修改失败');
                }
//            } catch (\Exception $e) {
//                return $this->error('网络错误');
//            }
        } else {
            $this->commonParams();
            return $this->fetch('guild/guild/edit', [
                'info' => $info,
            ]);
        }
    }

    /**
     * 删除常见问题
     */
    public function del()
    {
        $ids = (array)$this->request->param('ids');
        try {
            $data = $this->repository->batchDelete($ids);
            if ($data) {
                company_user_log(4, '删除公会 ids:' . implode(',', $ids), $data);
                return $this->success('删除成功');
            } else {
                return $this->error('删除失败');
            }
        } catch (\Exception $e) {
            return $this->error('网络失败');
        }
    }

    /**
     * 开关
     */
    public function switchStatus()
    {
        $id = $this->request->param('id');
        $status = $this->request->param('status', 2) == 1 ? 1 : 2;
        $res = $this->repository->switchStatus($id, $status);
        if ($res) {
            company_user_log(4, '修改常见问题状态 id:' . $id, [
                'status' => $status
            ]);
            return $this->success('修改成功');
        } else {
            return $this->error('修改失败');
        }
    }

    /**
     * 常见问题显示置顶开关
     */
    public function placedTop()
    {
        $id = $this->request->param('id');
        $placedTop = $this->request->param('statusTop', 2) == 1 ? 1 : 2;
        $res = $this->repository->placedTop($id, $placedTop);
        if ($res) {
            company_user_log(4, '修改常见问题置顶 id:' . $id, [
                'statusTop' => $placedTop
            ]);
            return $this->success('修改成功');
        } else {
            return $this->error('修改失败');
        }
    }

}