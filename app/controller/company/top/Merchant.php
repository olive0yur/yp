<?php

namespace app\controller\company\top;

use app\common\repositories\agent\AgentRepository;
use app\common\repositories\goods\GoodsRepository;
use app\common\repositories\guild\GuildMemberRepository;
use app\common\repositories\guild\GuildRepository;
use app\common\repositories\guild\GuildWareHouseRepository;
use app\common\repositories\guild\GuildWareLogRepository;
use app\common\repositories\identity\IdentityRepository;
use app\common\repositories\mine\MineDispatchRepository;
use app\common\repositories\mine\MineUserDispatchRepository;
use app\common\repositories\mine\MineUserRepository;
use app\common\repositories\pool\PoolShopOrder;
use app\common\repositories\top\UsersTopRepository;
use app\common\repositories\users\UsersCertRepository;
use app\common\repositories\users\UsersGoodsRepository;
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

class Merchant extends Base
{
    protected $repository;

    public function __construct(App $app, UsersRepository $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
        $this->assign([
            'addAuth' => company_auth('companyMerchantAdd'),
            'delAuth' => company_auth('companyMerchantDel'),
            'infoAuth' => company_auth('companyMerchantInfo'),
            'giveAuth' => company_auth('companyMerchantGive'),
        ]);
    }


    public function list()
    {
        if ($this->request->isAjax()) {
            $where = $this->request->param([
                'keywords' => '',
                'is_top' => 1,
            ]);
            [$page, $limit] = $this->getPage();
            $data = $this->repository->getList($where, $page, $limit, $this->request->companyId);
            return json()->data(['code' => 0, 'data' => $data['list'], 'count' => $data['count']]);
        } else {
            return $this->fetch('top/merchant/list');
        }
    }


    public function add()
    {
        if ($this->request->isPost()) {
            $param = $this->request->param([
                'uuid' => '',
            ]);


            $z = $this->repository->search(['uuid' => $param['uuid']])->find();
            if ($z) return $this->error('您所选用户已加入公会，请先退出！');
            /** @var  UsersRepository $usersRepository */
            $usersRepository = $this->app->make(UsersRepository::class);
            $user = $usersRepository->search([], $this->request->companyId)->where('id', $param['uuid'])->find();
            if ($user['is_top'] == 1) return $this->error('您所选用户已经是会长！');

            try {
                $res = $usersRepository->editInfo($user, ['is_top' => 1, 'top_time' => date('Y-m-d H:i:s')]);
                if ($res) {
                    app()->make(IdentityRepository::class)->addInfo($this->request->companyId, [
                        'uuid' => $user['id'], 'levels' => 1, 'hidden' => 1
                    ]);
                    return $this->success('添加成功');
                } else {
                    return $this->error('添加失败');
                }
            } catch (\Exception $e) {
                return $this->error($e->getMessage());
            }
        } else {
            $users = $this->app->make(UsersRepository::class)->search([], $this->request->companyId)->where('is_top', '<>', 1)->field('id,mobile,user_code')->select();
            $this->assign('users', $users);
            return $this->fetch('top/merchant/add');
        }
    }

    public function del()
    {
        $ids = (array)$this->request->param('ids');
        $ids = $ids[0] ?? 0;

        if ($ids) {
            $repository = app()->make(UsersTopRepository::class);
            $topUids = $repository->search([], $this->request->companyId)->where('top_id', $ids)->column('uuid');
            $topUids[] = $ids;
            $this->repository->search([], $this->request->companyId)->where('id', $ids)
                ->update(['top_time' => null, 'is_top' => 0, 'top_num' => 0, 'top_pledge' => 0]);
            app()->make(IdentityRepository::class)->search([], $this->request->companyId)->where(['hidden' => 1
            ])->whereIn('uuid', $topUids)->delete();
            $repository->search([], $this->request->companyId)->whereIn('uuid', $topUids)->delete();
            return $this->success('删除成功');
        }
        return $this->error('删除失败');
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
                'type' => '',
                'amount' => '',
                'remark' => ''
            ]);
            if ($param['amount'] <= 0) {
                return $this->error('金额必须大于0');
            }
            try {
                $this->repository->batchFoodChange($id, 1, ($param['type'] == 2 ? '-' : '') . $param['amount'], [
                    'remark' => $param['remark']
                ], 1);
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
            ]);
            $where['levels'] = 1;
            [$page, $limit] = $this->getPage();
            $data = $repository->getList($where, $page, $limit, $this->request->companyId);
            return json()->data(['code' => 0, 'data' => $data['list'], 'count' => $data['count']]);
        }
    }

    public function givUser()
    {
        $id = (array)$this->request->param('id');
        if ($this->request->isPost()) {
            $param = $this->request->param([
                'goods_id' => '',
                'num' => '',
            ]);
            if ($param['goods_id'] <= 0) {
                return $this->error('请选择商品');
            }
            try {
                $userGoodsRepository = app()->make(UsersGoodsRepository::class);
                $companyId = $this->request->companyId;
                foreach ($id as $value) {

                    $user = app()->make(UsersRepository::class)->getDetail($value);
                    if (!$user) {
                        return $this->error('用户不存在');
                    }
                }
                $goods = app()->make(GoodsRepository::class)->search([], $companyId)->where('id', $param['goods_id'])->find();
                if (!$goods) return $this->error('商品不存在');
                foreach ($id as $value) {
                    $uuid = $value;
                    for ($i = 0; $i < $param['num']; $i++) {
                        $userGoodsRepository->addInfo($companyId, [
                            'uuid' => $uuid,
                            'goods_id' => $param['goods_id'],
                            'type' => 1,
                            'status' => 1,
                            'goods_code' => $this->generateUniqueTimestampMixedCode(12),
                        ]);
                    }
                }
                company_user_log(3, '空投商品 用户id:' . implode(',', $id), $param);
                return $this->success('设置成功');
            } catch (\Exception $e) {
                return $this->error('网络错误');
            }
        } else {
            $goods = app()->make(GoodsRepository::class)->search([], $this->request->companyId)->select();
            return $this->fetch('users/users/set_goods', ['goods' => $goods]);
        }
    }


    /**
     * 生成基于时间戳和随机数的唯一12位字母数字字符串
     *
     * @return string 12位的字母数字混合唯一字符串
     */
    public function generateUniqueTimestampMixedCode()
    {
        // 获取当前时间戳（秒级别），并将其转换为16进制形式以增加字符串多样性
        $hexTimestamp = dechex(time());
        // 生成一个随机数（确保在一定范围内以控制字符串长度）
        $randomNum = mt_rand(100, 999); // 三位随机数，确保数字部分有足够的变化
        // 将时间戳和随机数合并
        $combinedStr = $hexTimestamp . $randomNum;
        // 将合并后的字符串转换为字符和数字的混合形式
        // 首先确保字符串长度，如果不足12位则补全，超过则取前12位
        if (strlen($combinedStr) < 12) {
            // 计算需要补充的长度
            $paddingLength = 12 - strlen($combinedStr);
            // 生成随机字符串以补充，包括大小写字母和数字
            $paddingStr = substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, $paddingLength);
            $combinedStr .= $paddingStr;
        } else {
            $combinedStr = substr($combinedStr, 0, 12);
        }

        return $combinedStr;
    }

}