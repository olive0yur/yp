<?php

namespace app\controller\company\users;

use app\common\repositories\agent\AgentRepository;
use app\common\repositories\game\KillRepository;
use app\common\repositories\guild\GuildMemberRepository;
use app\common\repositories\guild\GuildRepository;
use app\common\repositories\guild\GuildWareHouseRepository;
use app\common\repositories\guild\GuildWareLogRepository;
use app\common\repositories\mine\MineDispatchRepository;
use app\common\repositories\mine\MineUserDispatchRepository;
use app\common\repositories\mine\MineUserRepository;
use app\common\repositories\pool\PoolShopOrder;
use app\common\repositories\rabbit\ToysBackpackRepository;
use app\common\repositories\users\UsersCertRepository;
use app\common\repositories\users\UsersGroupRepository;
use app\common\repositories\users\UsersLabelRepository;
use app\common\repositories\users\UsersPoolRepository;
use app\common\repositories\users\UsersPushRepository;
use think\App;
use app\controller\company\Base;
use app\common\repositories\users\UsersRepository;
use app\validate\users\UsersCertValidate;
use app\validate\users\UsersValidate;
use think\exception\ValidateException;
use think\facade\Db;

class Users extends Base
{
    protected $repository;

    public function __construct(App $app, UsersRepository $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
        $this->assign([
            'addAuth' => company_auth('companyUsersAdd'),
            'editAuth' => company_auth('companyUsersEdit'),
            'delAuth' => company_auth('companyUsersDelete'),
            'editPasswordAuth' => company_auth('companyUsersEditPassword'),
            'gashaponListAuth' => company_auth('companyGashaponList'),
            'editTjrAuth' => company_auth('companyUsersEditTjr'),
            'setUserStatusAuth' => company_auth('companyUsersStatus'),
            'setUserCertAuth' => company_auth('companySetUsersCert'),
            'userDetailAuth' => company_auth('companyUsersDetail'),
            'setBalanceAuth' => company_auth('companyUsersSetBalance'),
            'batchSetBalanceAuth' => company_auth('companyUsersBatchSetBalance'),
            'givePoolAuth' => company_auth('companyGiveUserPoolSale'),
            'giveBoxAuth' => company_auth('companyGiveUserBoxSale'),
            'giveGashaponAuth' => company_auth('companyGashaponList'),
            'companyUsersBlack' => company_auth('companyUsersBlack'),
            'companyUsersPushGame' => company_auth('companyUsersPushGame'),
        ]);
    }


    public function list()
    {
        if ($this->request->isAjax()) {
            $where = $this->request->param([
                'keywords' => '',
                'group_id' => '',
                'reg_time' => '',
                'is_open' => '',
                'is_cert' => '',
                'user_code' => '',
            ]);
            [$page, $limit] = $this->getPage();
            $data = $this->repository->getList($where, $page, $limit, $this->request->companyId);
            return json()->data(['code' => 0, 'data' => $data['list'], 'count' => $data['count']]);
        } else {

            /**
             * @var UsersLabelRepository $usersLabelRepository
             */
            $tokens = web_config($this->request->companyId, 'site')['tokens'];
            return $this->fetch('users/users/list', [
                'tokens' => $tokens ?: '',
                'companyy_id' => $this->request->companyId,
            ]);
        }
    }

    public function add()
    {
        if ($this->request->isPost()) {
            $param = $this->request->param([
                'nickname' => '',
                'mobile' => '',
                'password' => '',
                'status' => '',
                'tjr_account' => ''
            ]);
            try {
                validate(UsersValidate::class)->scene('add')->check($param);
            } catch (ValidateException $e) {
                return json()->data($e->getError());
            }
            if ($this->repository->companyFieldExists($this->request->companyId, 'mobile', $param['mobile'])) {
                return $this->error($param['mobile'] . '手机号已存在');
            }

            if ($param['tjr_account']) {
                if (!$this->repository->fieldExists('mobile', $param['tjr_account'])) {
                    return $this->error('推荐人手机号不存在');
                }
            }
            if ($param['mobile'] == $param['tjr_account']) {
                return $this->error('不能设置自己为推荐人');
            }
            try {
                $res = $this->repository->addInfo($this->request->companyId, $param);
                if ($res) {
                    return $this->success('添加成功');
                } else {
                    return $this->error('添加失败');
                }
            } catch (\Exception $e) {
                return $this->error($e->getMessage());
            }
        } else {
            return $this->fetch('users/users/add');
        }
    }


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
                'type' => '',
                'mobile' => '',
                'nickname' => '',
                'birthday' => '',
                'status' => '',
                'user_sex' => '',
                'avatar' => '',
                'head_file_id' => '',
                'is_top' => '',
                'user_code' => '',
                'is_create_guild' => ''
            ]);
            validate(UsersValidate::class)->scene('edit')->check($param);
            try {
                $res = $this->repository->editInfo($info, $param);
                if ($res) {
                    company_user_log(3, '编辑用户', $param);
                    return $this->success('修改成功');
                } else {
                    return $this->error('修改失败');
                }
            } catch (\Exception $e) {
                exception_log('修改失败', $e);
                return $this->error($e->getMessage());
            }
        } else {
            return $this->fetch('users/users/edit', [
                'info' => $info,
            ]);
        }
    }

    /**
     * 删除用户
     */
    public function delete()
    {
        $ids = $this->request->param('ids');
        $res = $this->repository->delUser($ids);
        if ($res) {
            app()->make(MineDispatchRepository::class)->search([])->whereIn('uuid', $ids)->delete();
            app()->make(MineUserRepository::class)->search([])->whereIn('uuid', $ids)->delete();
            app()->make(MineUserDispatchRepository::class)->search([])->whereIn('uuid', $ids)->delete();
            $guild = app()->make(GuildRepository::class)->search([])->whereIn('uuid', $ids)->select();
            foreach ($guild as $value) {
                app()->make(GuildMemberRepository::class)->search(['guild_id' => $value['id']])->delete();
                app()->make(GuildWareHouseRepository::class)->search(['guild_id' => $value['id']])->delete();
                app()->make(GuildWareLogRepository::class)->search(['guild_id' => $value['id']])->delete();
                app()->make(GuildRepository::class)->search([])->where('id', $value['id'])->delete();
            }
            app()->make(GuildMemberRepository::class)->search([])->whereIn('uuid', $ids)->delete();
            app()->make(UsersPoolRepository::class)->search([])->whereIn('uuid', $ids)->delete();
            app()->make(UsersPushRepository::class)->search([])->whereIn('user_id', $ids)->delete();
            app()->make(UsersPushRepository::class)->search([])->whereIn('parent_id', $ids)->delete();
            app()->make(UsersCertRepository::class)->search([])->whereIn('user_id', $ids)->delete();
            app()->make(PoolShopOrder::class)->search([])->whereIn('uuid', $ids)->delete();
            app()->make(AgentRepository::class)->search([])->whereIn('uuid', $ids)->delete();
            company_user_log(4, '删除用户:' . implode(',', $ids));
            return $this->success('删除成功');
        } else {
            return $this->error('删除失败');
        }
    }

    /**
     * 修改密码
     * @return string|\think\response\Json|\think\response\View
     * @throws \Exception
     */
    public function editPassword()
    {
        $id = (int)$this->request->param('id');
        if (!$id) {
            return $this->error('参数错误');
        }
        $info = $this->repository->getDetail($id);
        if (!$info) return $this->error('数据不存在');

        if ($this->request->isPost()) {
            $param = $this->request->param([
                'password' => '',
                'type' => ''
            ]);
            if ((int)$param['type'] <= 0) {
                return $this->error('请选择修改类型');
            }
            try {
                $this->repository->editInfo($info, $param);
                company_user_log(3, '修改用户密码 id:' . $id, $param);
                return $this->success('修改成功');
            } catch (\Exception $e) {
                return $this->error('网络错误');
            }
        } else {
            return $this->fetch('users/users/edit_password');
        }
    }

    /**
     * 修改推荐人
     * @return string|\think\response\Json|\think\response\View
     * @throws \Exception
     */
    public function editTjr()
    {
        $id = (int)$this->request->param('id');
        if (!$id) {
            return $this->error('参数错误');
        }
        if (!$this->repository->exists($id)) {
            return $this->error('数据不存在');
        }
        $info = $this->repository->getDetail($id);
        if (!$info) {
            return $this->error('信息错误');
        }
        if ($this->request->isPost()) {
            $param = $this->request->param([
                'tjr_account' => ''
            ]);
            if ($param['tjr_account'] === '') {
                return $this->error('请输入推荐人手机号');
            }
            if (!$this->repository->fieldExists('mobile', $param['tjr_account'])) {
                return $this->error('推荐人手机号不存在');
            }
            $tjrInfo = $this->repository->getUserByMobile($param['tjr_account'], $this->request->companyId);

            if ($tjrInfo['id'] == $info['id']) {
                return $this->error('不能设置自己为推荐人');
            }
            /**
             * @var UsersPushRepository $usersPushRepository
             */
            $usersPushRepository = app()->make(UsersPushRepository::class);
            $parent = $usersPushRepository->getUserId($id);
            if (in_array($tjrInfo['id'], $parent)) {
                return $this->error('推荐人不能是自己的下级');
            }

            try {
                $usersPushRepository->batchTjr($info, $tjrInfo['id'], $this->request->companyId);

                company_user_log(3, '调整用户推荐人 id:' . $info['id'], $param);
                return $this->success('修改成功');
            } catch (\Exception $e) {
                return $this->error('网络错误');
            }
        } else {
            return $this->fetch('users/users/edit_tjr', [
                'info' => $info
            ]);
        }
    }

    /**
     * 设置用户登录状态
     */
    public function setUserStatus()
    {
        $id = (int)$this->request->param('id');
        if ($this->request->isPost()) {
            $param = $this->request->param([
                'id' => '',
                'status' => '',
                'is_giv' => '',
            ]);
            foreach ($param as $key => $vo) if ($vo === '') unset($param[$key]);
            if (!$this->repository->exists($id)) {
                return $this->error('数据不存在');
            }
            try {
                $res = $this->repository->update($id, $param);
                company_user_log(3, '修改登录状态 id:' . $id, $param);
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

    /**
     * 个人认证
     */
    public function setUserCert()
    {
        $id = $this->request->param('id');
        if (!$id) {
            return $this->error('参数错误');
        }
        if (!$this->repository->exists($id)) {
            return $this->error('数据不存在');
        }
        $info = $this->repository->getDetail($id);
        if (!$info) {
            return $this->error('信息错误');
        }
        if ($this->request->isPost()) {
            $param = $this->request->param([
                'username' => '',
                'number' => '',
                'cert_status' => '',
                'remark' => '',
                'idcard_front_photo' => '',
                'idcard_back_photo' => '',
            ]);
            $param['user_id'] = $info['id'];
            try {
                validate(UsersCertValidate::class)->scene('add')->check($param);
            } catch (ValidateException $e) {
                return json()->data($e->getError());
            }
            try {
                /**
                 * @var UsersCertRepository $usersCertRepository
                 */
                $usersCertRepository = app()->make(UsersCertRepository::class);
                $res = $usersCertRepository->addCert($param, $info, $this->request->companyId);
                company_user_log(2, '添加个人认证', $param);
                if ($res) {
                    return $this->success('添加成功');
                } else {
                    return $this->error('添加失败');
                }
            } catch (\Exception $e) {
                return $this->error('网络错误');
            }
        }
        return $this->fetch('users/users/set_user_cert', [
            'info' => $info
        ]);
    }

    /**
     * 用户详情
     */
    public function userDetail()
    {
        $id = $this->request->param('id');

        if (!$id) {
            return $this->error('参数错误');
        }
        if (!$this->repository->exists($id)) {
            return $this->error('数据不存在');
        }
        $info = $this->repository->getDetail($id);
        if (!$info) {
            return $this->error('信息错误');
        }
        $sitename = web_config($this->request->companyId, 'site')['sitename'];
        return $this->fetch('users/users/details', [
            'info' => $info,
            'sitename' => $sitename
        ]);
    }

    /**
     * 设置用户余额
     * @return string|\think\response\Json|\think\response\View
     * @throws \Exception
     */
    public function setBalance()
    {
        $id = (int)$this->request->param('id');
        if (!$id) {
            return $this->error('参数错误');
        }
        if (!$this->repository->exists($id)) {
            return $this->error('数据不存在');
        }
        $info = $this->repository->get($id);
        if (!$info) {
            return $this->error('信息错误');
        }
        if ($this->request->isPost()) {
            $param = $this->request->param([
                'type' => '',
                'amount' => '',
                'remark' => ''
            ]);
            if ($param['amount'] <= 0) {
                return $this->error('金额必须大于0');
            }
            try {
                $this->repository->balanceChange($id, 1, ($param['type'] == 2 ? '-' : '') . $param['amount'], [
                    'remark' => $param['remark']
                ], 1);
                company_user_log(3, '调整用户余额 id:' . $info['id'], $param);
                return $this->success('设置成功');
            } catch (\Exception $e) {
                return $this->error('网络错误');
            }
        } else {
            return $this->fetch('users/users/set_balance');
        }
    }


    /**
     * 批量设置余额
     */
    public function batchSetBalance()
    {
        $id = (array)$this->request->param('id');
        if ($this->request->isPost()) {
            $param = $this->request->param([
                'balance_type' => '',
                'type' => '',
                'amount' => '',
                'remark' => ''
            ]);
            if ($param['amount'] <= 0) {
                return $this->error('金额必须大于0');
            }
            try {
                if ($param['balance_type'] == 1) {
                    $this->repository->batchFoodChange($id, 1, ($param['type'] == 2 ? '-' : '') . $param['amount'], [
                        'remark' => $param['remark']
                    ], 1);
                } elseif ($param['balance_type'] == 2) {
                    $this->repository->batchGoldChange($id, 1, ($param['type'] == 2 ? '-' : '') . $param['amount'], [
                        'remark' => $param['remark']
                    ], 1);
                } elseif ($param['balance_type'] == 3) {
                    $this->repository->batchScoreChange($id, 1, ($param['type'] == 2 ? '-' : '') . $param['amount'], [
                        'remark' => $param['remark']
                    ], 1);
                }
                company_user_log(3, '批量调整用户余额 id:' . implode(',', $id), $param);
                return $this->success('设置成功');
            } catch (\Exception $e) {
                return $this->error('网络错误');
            }
        } else {
            return $this->fetch('users/users/set_balance');
        }
    }


    public function pushCountList(UsersPushRepository $repository)
    {
        if ($this->request->isAjax()) {
            $where = $this->request->param([
                'parent_id' => '',
                'levels' => ''
            ]);
            [$page, $limit] = $this->getPage();
            $data = $repository->getList($where, $page, $limit, $this->request->companyId);
            return json()->data(['code' => 0, 'data' => $data['list'], 'count' => $data['count']]);
        }
    }

    public function gearUser(ToysBackpackRepository $repository)
    {
        if ($this->request->isAjax()) {
            $where = $this->request->param([
                'keywords' => '',
                'uuid' => '',
            ]);
            [$page, $limit] = $this->getPage();
            $data = $repository->getList($where, $page, $limit, $this->request->companyId);
            return json()->data(['code' => 0, 'data' => $data['list'], 'count' => $data['count']]);
        }
        return $this->fetch('users/users/gear', ['uuid' => $_GET['uuid']]);
    }
    /**
     * 下级游戏
     * @return string|\think\response\Json|\think\response\View
     * @throws \Exception
     */
    public function pushListGame()
    {
        $id = $this->request->param('uuid');
        if (!$id) {
            return $this->error('参数错误');
        }
        if (!$this->repository->exists($id)) {
            return $this->error('数据不存在');
        }
        $killRepository = app()->make(KillRepository::class);
        if ($this->request->isAjax()) {
            $where = $this->request->param([
                'reg_time' => '',
                'uuid'     => '',
            ]);
            [$page, $limit] = $this->getPage();
            $data = $killRepository->getList($where, $page, $limit, $this->request->companyId);
            return json()->data(['code' => 0, 'data' => $data['list'], 'count' => $data['count']]);
        } else {
            $sitename = web_config($this->request->companyId, 'site')['sitename'];
            return $this->fetch('users/users/pushListGame', [
                'uuid' => $id,
                'sitename' => $sitename
            ]);
        }
    }
}
