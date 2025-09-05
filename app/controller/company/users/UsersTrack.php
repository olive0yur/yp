<?php

namespace app\controller\company\users;

use think\App;
use app\controller\company\Base;
use app\common\repositories\users\UsersTrackRepository;

class UsersTrack extends Base
{

    protected $repository;

    public function __construct(App $app, UsersTrackRepository $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
        $this->assign([
            'addAuth' => company_auth('companyUsersTrackAdd'),
            'editAuth' => company_auth('companyUsersTrackEdit'),
            'delAuth' => company_auth('companyUsersTrackDel'),
        ]);
    }

    public function list()
    {
        if ($this->request->isAjax()) {
            $where = $this->request->param([
                'user_id' => '',
                'mobile' => '',
                'keyword' => ''
            ]);
            [$page, $limit] = $this->getPage();
            $res = $this->repository->getList($where, $page, $limit,$this->request->companyId);
            return json()->data(['code' => 0,'data' => $res['list'],'count' => $res['count']]);
        }
        return $this->fetch('users/track/list');
    }

    public function add()
    {

    }


    public function edit()
    {

    }

    public function del()
    {
        $ids = (array)$this->request->param('ids');
        try {
            $data = $this->repository->batchDelete($ids);
            if ($data) {
                return $this->success('删除成功');
            } else {
                return $this->error('删除失败');
            }
        } catch (\Exception $e) {
            return $this->error('网络失败');
        }
    }

}