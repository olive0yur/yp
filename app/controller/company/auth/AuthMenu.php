<?php

namespace app\controller\company\auth;

use app\common\repositories\company\CompanyAuthRuleRepository;
use app\controller\company\Base;
use think\App;

class AuthMenu extends Base
{
    protected $repository;

    public function __construct(App $app, CompanyAuthRuleRepository $repository)
    {
        parent::__construct($app);

        $this->repository = $repository;
    }

    /**
     * 菜单目录列表
     *
     * @return array|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function index()
    {
        if ($this->request->isAjax()) {
            $data = $this->repository->getUserMenus($this->request->adminId);
            foreach ($data as $k => &$v) {
                $v['pid'] = $v['pid'] === 0 ? -1 : $v['pid'];
            }

            return [
                'code' => 0,
                'count' => 7,
                'msg' => '获取成功',
                'data' => $data
            ];
        }
        return $this->fetch('auth/menu/index', [
            'editAuth' => company_auth('companyEditAuthMenu')
        ]);
    }

    /**
     * 编辑菜单
     *
     * @return array|string|\think\response\View
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function edit()
    {
        $id = (int)$this->request->param('id');
        if (!$id) {
            return $this->error('参数错误');
        }
        $info = $this->repository->get($id);
        if (!$info) {
            return $this->error('信息获取失败');
        }
        if ($this->request->isPost()) {
            $param = $this->request->param([
                'icon' => '',
                'name' => '',
                'sort' => ''
            ]);
            try {
                $old = $info->toArray();
                $res = $this->repository->update($id, $param);
                company_user_log(3, '修改目录信息 id:' . $info['id'], ['old' => $old, 'new' => $param]);
                if ($res) {
                    return $this->success('修改成功');
                } else {
                    return $this->success('没有信息发生改变');
                }
            } catch (\Exception $e) {
                return $this->success('修改失败');
            }
        } else {
            return $this->fetch('auth/menu/edit', [
                'info' => $info
            ]);
        }
    }

}