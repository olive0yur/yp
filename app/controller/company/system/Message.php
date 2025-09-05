<?php

namespace app\controller\company\system;

use think\App;
use app\controller\company\Base;
use app\common\repositories\user\UserRepository;
use app\common\repositories\user\UserMessageRepository;
use app\common\repositories\system\message\SystemMessageRepository;

class Message extends Base
{
    protected $repository;

    public function __construct(App $app, SystemMessageRepository $repository, UserMessageRepository $userRepository)
    {
        parent::__construct($app);
        $this->repository = $repository;
        $this->userRepository = $userRepository;
    }

    /**
     * 系统消息列表
     */
    public function list()
    {
        if ($this->request->isAjax()) {
            $where = $this->request->param([
                'keywords' => ''
            ]);
            [$page, $limit] = $this->getPage();
            $res = $this->repository->getList($this->request->companyId, $where, $page, $limit);

            return json()->data([
                'code' => 0,
                'data' => $res['list'],
                'count' => $res['count']
            ]);
        }
        return $this->fetch('system/message/list', [
            'addAuth' => company_auth('companyMessageAdd'),
            'delAuth' => company_auth('companyMessagedDel')
        ]);
    }

    /**
     * 发送系统通知
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $param = $this->request->param([
                'title' => '',
                'content' => '',
                'user_id' => '',
                'user_type' => ''
            ]);
            if ($param['user_type'] == 1) {
                $param['user_id'] = 0;
            } else {
                /** @var UserRepository $userRepository */
                $userRepository = app()->make(UserRepository::class);
                if (!$userRepository->companyExists($this->request->companyId, $param['user_id'])) {
                    return $this->error('用户不存在');
                }
            }
            try {
                $param['company_id'] = $this->request->companyId;
                $res = $this->repository->create($param);
                company_user_log(5, '发送系统通知：', $param);
                if ($res) {
                    return $this->success('发送成功');
                } else {
                    return $this->error('发送失败');
                }
            } catch (\Exception $e) {
                return $this->error('网络错误');
            }
        }
        return $this->fetch('system/message/add');
    }

    /**
     * 删除
     */
    public function del()
    {
        $id = (int)$this->request->param('id');
        if (!$id) {
            return $this->error('参数错误');
        }
        if (!$this->repository->companyExists($this->request->companyId, $id)) {
            return $this->error('数据不存在');
        }
        try {
            $res = $this->repository->delete($id);
            if ($res) {
                company_user_log(5, '删除系统通知 id:' . $id);
                return $this->success('删除成功');
            } else {
                return $this->error('删除失败');
            }
        } catch (\Exception $e) {
            return $this->error('网络失败');
        }
    }
	
	
	/**
	 * 账单通知列表
	 */
	public function billlist()
	{
	    if ($this->request->isAjax()) {
	        $where = $this->request->param([
	            'keywords' => '',
	            'message_type' => 3
	        ]);
	        [$page, $limit] = $this->getPage();
	        $res = $this->userRepository->getCompanyList($this->request->companyId, $where, $page, $limit);
	        return json()->data(['code' => 0,'data' => $res['list'],'count' => $res['count'] ]);
	    }
	    return $this->fetch('system/message/bill_list', [
	        'addAuth' => company_auth('companyBillMessageAdd'),
	        'delAuth' => company_auth('companyBillMessageDel')
	    ]);
	}


    /**
     * 发送账单消息
     */
    public function billAdd()
    {
        if ($this->request->isPost()) {
            $param = $this->request->param([
                'title' => '',
                'content' => '',
                'mobile' => '',
                'message_type' => 3
            ]);
            /** @var UserRepository $userRepository */
            $userRepository = app()->make(UserRepository::class);
            $userInfo = $userRepository->getUserByMobile( $param['mobile'],$this->request->companyId);
            if ($userInfo['id'] <= 0) {
                return $this->error($param['mobile'].' 用户不存在');
            }
            try {
                $param['user_id'] = $userInfo['id'];
                $param['company_id'] = $this->request->companyId;
                $res = $this->userRepository->create($param);
                company_user_log(5, '发送账单消息：', $param);
                if ($res) {
                    return $this->success('发送成功');
                } else {
                    return $this->error('发送失败');
                }
            } catch (\Exception $e) {
                return $this->error('网络错误');
            }
        }
        return $this->fetch('system/message/add_bill');
    }

    /**
     * 删除账单消息
     */
    public function billDel()
    {
        $id = (int)$this->request->param('id');
        if (!$id) {
            return $this->error('参数错误');
        }
        if (!$this->userRepository->companyExists($this->request->companyId, $id)) {
            return $this->error('数据不存在');
        }
        try {
            $res = $this->userRepository->delete($id);
            if ($res) {
                company_user_log(5, '删除系统通知 id:' . $id);
                return $this->success('删除成功');
            } else {
                return $this->error('删除失败');
            }
        } catch (\Exception $e) {
            return $this->error('网络失败');
        }
    }



	/**
	 * 合同通知列表
	 */
	public function contractlist()
	{
	    if ($this->request->isAjax()) {
	        $where = $this->request->param([
	            'keywords' => '',
	            'message_type' => 2
	        ]);
	        [$page, $limit] = $this->getPage();
	        $res = $this->userRepository->getCompanyList($this->request->companyId, $where, $page, $limit);
	        return json()->data([
	            'code' => 0,
	            'data' => $res['list'],
	            'count' => $res['count']
	        ]);
	    }
	    return $this->fetch('system/message/contract_list', [
	        'addAuth' => company_auth('companyContractMessageAdd'),
	        'delAuth' => company_auth('companyContractMessageDel')
	    ]);
	}


    /**
     * 发送合同消息
     */
    public function contractAdd()
    {
        if ($this->request->isPost()) {
            $param = $this->request->param([
                'title' => '',
                'content' => '',
                'mobile' => '',
                'message_type' => 2
            ]);
            /** @var UserRepository $userRepository */
            $userRepository = app()->make(UserRepository::class);
            $userInfo = $userRepository->getUserByMobile( $param['mobile'],$this->request->companyId);
            if ($userInfo['id'] <= 0) {
                return $this->error($param['mobile'].' 用户不存在');
            }
            try {
                $param['user_id'] = $userInfo['id'];
                $param['company_id'] = $this->request->companyId;
                $res = $this->userRepository->create($param);
                company_user_log(5, '发送合同消息', $param);
                if ($res) {
                    return $this->success('发送成功');
                } else {
                    return $this->error('发送失败');
                }
            } catch (\Exception $e) {
                return $this->error('网络错误');
            }
        }
        return $this->fetch('system/message/add_contract');
    }

    /**
     * 删除合同消息
     */
    public function contractDel()
    {
        $id = (int)$this->request->param('id');
        if (!$id) {
            return $this->error('参数错误');
        }
        if (!$this->userRepository->companyExists($this->request->companyId, $id)) {
            return $this->error('数据不存在');
        }
        try {
            $res = $this->userRepository->delete($id);
            if ($res) {
                company_user_log(5, '删除系统通知 id:' . $id);
                return $this->success('删除成功');
            } else {
                return $this->error('删除失败');
            }
        } catch (\Exception $e) {
            return $this->error('网络失败');
        }
    }

}