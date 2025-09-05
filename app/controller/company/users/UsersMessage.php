<?php

namespace app\controller\company\users;

use app\common\repositories\users\UsersMessageRepository;
use think\App;
use app\controller\company\Base;
use think\exception\ValidateException;
use app\validate\users\UsersCertValidate;
use app\common\repositories\users\UsersCertRepository;

class UsersMessage extends Base
{

    protected $repository;

    public function __construct(App $app, UsersMessageRepository $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
    }

    public function list()
    {
        if ($this->request->isAjax()) {
            $where = $this->request->param([
                'cert_status' => ''
            ]);
            [$page, $limit] = $this->getPage();
            $res = $this->repository->getList($where, $page, $limit,$this->request->companyId);
            return json()->data(['code' => 0,'data' => $res['list'],'count' => $res['count']]);
        }
        return $this->fetch('users/message/list', [
            'reply' => company_auth('companyUserMessageReply'),
        ]);
    }
    public function reply()
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
                'replay_text' => '',
            ]);
            if(!$param['replay_text']) return $this->error('回复内容不能为空！');
            try {
                $res = $this->repository->editInfo($info, $param);
                if ($res) {
                    company_user_log(3, '回复用户反馈 id:' . $info['id'], $param);
                    return $this->success('修改成功');
                } else {
                    return $this->error('修改失败');
                }
            } catch (\Exception $e) {
                return $this->error('网络错误'.$e->getMessage());
            }
        } else {
            return $this->fetch('users/message/edit', [
                'info' => $info
            ]);
        }
    }

}