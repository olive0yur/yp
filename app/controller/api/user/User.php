<?php

namespace app\controller\api\user;

use app\common\model\BaseModel;
use app\common\model\game\KillRebateModel;
use app\common\model\users\CheckIn;
use app\common\repositories\forum\ForumRepository;
use app\common\repositories\forum\ForumZanRepository;
use app\common\repositories\givLog\GivLogRepository;
use app\common\repositories\givLog\MineGivLogRepository;
use app\common\repositories\mine\MineUserDispatchRepository;
use app\common\repositories\system\upload\UploadFileRepository;
use app\common\repositories\users\UsersBalanceLogRepository;
use app\common\repositories\users\UsersFoodLogRepository;
use app\common\repositories\users\UsersGoldLogRepository;
use app\common\repositories\users\UsersIntegralLogRepository;
use app\common\repositories\users\UsersPushRepository;
use app\common\repositories\users\UsersRepository;
use app\common\repositories\users\UsersScoreLogRepository;
use app\controller\api\Base;
use app\validate\users\LoginValidate;
use app\validate\users\UsersValidate;
use think\App;
use think\exception\ValidateException;
use think\facade\Cache;
use think\facade\Db;

class User extends Base
{
    protected $repository;

    public function __construct(App $app, UsersRepository $repository)
    {
        parent::__construct($app);

        $this->repository = $repository;
    }

    /**
     * 获取用户信息
     *
     * @return mixed
     */
    public function logout()
    {
        $this->repository->clearToken();
        return app('api_return')->success([], '退出成功');
    }

    /**
     * 获取用户信息
     *
     * @return mixed
     */
    public function getUserInfo()
    {
        return app('api_return')->success($this->repository->showApiFilter($this->request->userInfo()));
    }

    /**
     * 修改头像
     */
    public function modifyAvatar(UploadFileRepository $uploadFileRepository)
    {
        $userInfo = $this->request->userInfo();
        $avatar = $this->request->param('avatar');

        $uploadFileRepository->whereDelete([
            'id' => $userInfo['head_file_id']
        ]);
        $avatar = $uploadFileRepository->getFileData($avatar, 4, $this->request->userId());
        $this->repository->update($userInfo['id'], [
            'head_file_id' => $avatar['id']
        ]);
        return app('api_return')->success('修改成功');
    }

    /**
     * 修改信息
     */
    public function modifyInfo()
    {
        $params = $this->request->param([
            'nickname' => '',
            'qq' => '',
            'wechat' => ''
        ]);
        foreach ($params as $key => $vo) if ($vo === '') unset($params[$key]);
        if (empty($params)) return $this->error('没有修改的数据！');
        $this->repository->update($this->request->userId(), $params);
        return app('api_return')->success('修改成功');
    }

    /**
     * 修改手机号
     */
    public function modifyMobile()
    {
        $mobile = $this->request->param('mobile');
        $smsCode = $this->request->param('sms_code');

        $info = $this->request->userInfo();
        try {
            validate(UsersValidate::class)->scene('modifyPhone')->check($this->request->param());
        } catch (ValidateException $e) {
            return app('api_return')->error($e->getError());
        }
        if ($this->repository->fieldExists('mobile', $mobile)) {
            return app('api_return')->error('手机号已存在');
        }
        // 短信验证
        sms_verify($this->request->companyId, $mobile, $smsCode, config('sms.sms_type.MODIFY_MOBILE_VERIFY_CODE'));

        $res = $this->repository->update($info['id'], [
            'mobile' => $mobile
        ]);
        if ($res) {
            return app('api_return')->success('修改成功');
        } else {
            return app('api_return')->error('修改失败');
        }
    }

    public function frendsRawd(UsersFoodLogRepository $repository)
    {

        return $this->success($repository->frendsRawd($this->request->userId(), $this->request->companyId));
    }

    /**
     * 修改登录密码
     */
    public function modifyLoginPassword()
    {
        $smsCode = $this->request->param('sms_code');

        $info = $this->request->userInfo();
        if (!$info['mobile']) {
            return $this->error('请先绑定手机号');
        }

        $param = $this->request->param([
            // 确认密码
            'password' => '',
            //确认密码
            'repassword' => '',
            'sms_code' => ''
        ]);

        try {
            validate(UsersValidate::class)->scene('editPassword')->check($this->request->param());
        } catch (ValidateException $e) {
            return app('api_return')->error($e->getError());
        }

        // 短信验证
        sms_verify($this->request->companyId, $info['mobile'], $smsCode, config('sms.sms_type.MODIFY_PASSWORD'));
        $res = $this->repository->update($info['id'], [
            'password' => $this->repository->passwordEncrypt($param['password'])
        ]);

        if ($res) {
            return app('api_return')->success('修改成功');
        } else {
            return app('api_return')->error('修改失败');
        }
    }
    
    public function addUserPush(UsersPushRepository $repository){
        $info = $this->request->userInfo();
        $param = $this->request->param([
            //邀请码
            'user_code' => '',
        ]);
        $user = $this->repository->getWhere(['user_code' => $param['user_code']], 'id');
        if (isset($user['id'])) {
            $repository->batchTjr($info, $user['id'], $this->request->companyId);
            return app('api_return')->success('保存成功!');
        } else {
            return app('api_return')->error('推荐人不存在!');
        }
    }

    public function modifyLoginPayPassword()
    {
        // $smsCode = $this->request->param('sms_code');

        $info = $this->request->userInfo();

        $param = $this->request->param([
            // 确认密码
            'pay_password' => '',
            //确认密码
            'repassword' => '',
            // 'sms_code' => ''
        ]);

        try {
            if (!$param['pay_password']) return $this->error('交易密码不能为空');
            if (!$param['repassword']) return $this->error('确认密码不能为空');
            if ($param['pay_password'] != $param['repassword']) return $this->error('两次密码不一样!');
        } catch (ValidateException $e) {
            return app('api_return')->error($e->getError());
        }

        // 短信验证
        // sms_verify($this->request->companyId, $info['mobile'], $smsCode, config('sms.sms_type.MODIFY_PASSWORD'));
        $res = $this->repository->update($info['id'], [
            'pay_password' => $this->repository->passwordEncrypt($param['pay_password'])
        ]);

        if ($res) {
            return app('api_return')->success('修改成功');
        } else {
            return app('api_return')->error('修改失败');
        }
    }

    public function getFood(UsersRepository $repository)
    {
        return $this->success($repository->getFood($this->request->userInfo(), $this->request->companyId));
    }

    public function givTokens(UsersRepository $repository)
    {
        $data = $this->request->param(['user_code' => '', 'num' => '', 'pay_password' => '','type' => 1]);
        if (!$data['user_code']) return $this->error('请输入接收人!');
        if (!$data['num']) return $this->error('请输入数量!');
        return $this->success($repository->giv($data, $this->request->userInfo(), $this->request->companyId));

    }

    public function exchange(UsersRepository $repository){
        $data = $this->request->param(['num'=>'','pay_password'=>'','type'=>'']);
        return $this->success($repository->exchange($data,$this->request->userInfo(),$this->request->companyId),'兑换成功');
    }

    public function watchAdver(UsersRepository $repository){
        return $this->success($repository->watchAdver($this->request->userInfo(),$this->request->companyId),'签到成功');
    }

    public function getFrom(UsersPushRepository $repository)
    {
        $data = $this->request->param(['limit' => '', 'page' => '', 'parent_id' => $this->request->userId(), 'levels' => 1, 'level_code' => '']);
        return $this->success($repository->getFrom($data, $data['page'], $data['limit'], $this->request->userInfo()['id'], $this->request->companyId));

    }

    /**
     * 获取宝石日志
     */
    public function foodLogList(UsersFoodLogRepository $repository)
    {
        [$page, $limit] = $this->getPage();
        $data = $this->request->param(['type' => '']);
        return app('api_return')->success($repository->foodLogList($data['type'], $page, $limit, $this->request->userId(), $this->request->companyId));
    }

    /**
     * 获取宝石日志
     */
    public function goldLogList(UsersGoldLogRepository $repository)
    {
        [$page, $limit] = $this->getPage();
        $data = $this->request->param(['type' => '']);
        return app('api_return')->success($repository->goldLogList($data['type'], $page, $limit, $this->request->userId(), $this->request->companyId));
    }

    /**
     * 获取宝石日志
     */
    public function scoreLogList(UsersScoreLogRepository $repository)
    {
        [$page, $limit] = $this->getPage();
        $data = $this->request->param(['type' => '']);
        return app('api_return')->success($repository->scoreLogList($data['type'], $page, $limit, $this->request->userId(), $this->request->companyId));
    }


    public function balanceLogList(UsersBalanceLogRepository $repository)
    {
        [$page, $limit] = $this->getPage();
        $data = $this->request->param(['type' => 0]);
        return app('api_return')->success($repository->balanceLogList($data, $page, $limit, $this->request->userId()));
    }

    public function queryUser()
    {
        validate(LoginValidate::class)->scene('transfer')->check($this->request->param());
        $data = $this->request->param(['mobile' => '', 'hash' => '']);
        if (!isset($data['mobile']) && !isset($data['hash'])) $this->error('请填写转赠用户');
        return app('api_return')->success($this->repository->queryUser($data, $this->request->userId(), $this->request->companyId));
    }

    public function getGivLog(GivLogRepository $givLogRepository)
    {
        $data = $this->request->param(['limit' => '', 'page' => '', 'buy_type' => '']);
        $type = $this->request->param(['type' => '']);
        return $this->success($givLogRepository->getApiList($data, $type['type'], $data['page'], $data['limit'], $this->request->companyId, $this->request->userId()));
    }

    public function mineGivLog(MineGivLogRepository $repository)
    {
        $data = $this->request->param(['type' => '','balance_type' => '1']);
        [$page, $limit] = $this->getPage();
        return $this->success($repository->getApiList($data, $page, $limit, $this->request->userId(), $this->request->companyId));
    }


    public function getShareRanking(UsersPushRepository $usersPushRepository)
    {
        return $this->success($usersPushRepository->getApiRank($this->request->companyId));
    }

    public function bingUser(UsersRepository $repository)
    {
        $data = $this->request->param(['user_code' => '']);
        if (!$data['user_code']) return $this->error('请输入邀请码');
        return $this->success($repository->bindUser($data, $this->request->userInfo(), $this->request->companyId));
    }

    public function getRebate()
    {
        return $this->success((new KillRebateModel())->where('status', 0)->where('uuid', $this->request->userId())->sum('rebate'));
    }

    public function setRebate(UsersRepository $repository)
    {
        return $this->success($repository->setRebate($this->request->userInfo()));
    }

    /**
     * 绑定微信
     */
    public function bindWechat(\app\controller\api\Login $login, UsersRepository $repository)
    {
        $info = $this->request->userInfo();
        $code = $this->request->param('code');
        if (!$code) return $this->error('code不能为空');
        $res = $login->getWechatInfoByAPP($code);
        if (!isset($res['code']) || $res['code'] != 200) return $this->error(is_array($res)?($res['msg']??'绑定失败'):'绑定失败');
        $wx = $res['data'];
        // 判断 openid 是否已被绑定
        $exists = $repository->getWhere(['openid' => $wx['openid']], 'id');
        if ($exists && $exists['id'] != $info['id']) return $this->error('该微信已绑定其他账号');
        $repository->update($info['id'], [
            'openid' => $wx['openid'] ?? '',
            'unionid' => $wx['unionid'] ?? ''
        ]);
        return $this->success('绑定成功');
    }

    /**
     * 解绑微信
     */
    public function unbindWechat(UsersRepository $repository)
    {
        $info = $this->request->userInfo();
        // 确保解绑后仍可登录：至少保留手机号或本地密码
        if (!$info['mobile'] && !$info['password']) return $this->error('解绑前请先设置手机号或密码');
        $repository->update($info['id'], [
            'openid' => '',
            'unionid' => ''
        ]);
        return $this->success('解绑成功');
    }

    public function modifyBanks()
    {
        $param = $this->request->param([
            'qrcode' => '',
            'card_number' => '',
            'cardholder_name' => '',
            'bank_name' => '',
            'branch_name' => '',
        ]);
        foreach ($param as $key => $vo) if ($vo === '') unset($param[$key]);
        $info = Db::table('banks')->where([
            'uuid' => $this->request->userId(),
            'company_id' => $this->request->companyId,
        ])->find();
        if ($info) {
            $res = Db::table('banks')->where([
                'uuid' => $this->request->userId(),
                'company_id' => $this->request->companyId,
            ])->update($param);
        } else {
            $param['uuid'] = $this->request->userId();
            $param['company_id'] = $this->request->companyId;
            $res = Db::table('banks')->insert($param);
        }

        if ($res) {
            return app('api_return')->success('提交成功');
        } else {
            return app('api_return')->error('绑定失败');
        }
    }

}