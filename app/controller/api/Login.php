<?php

namespace app\controller\api;

use app\common\repositories\system\upload\UploadFileRepository;
use app\common\repositories\users\UsersRepository;
use app\common\repositories\users\UsersTrackRepository;
use app\validate\users\LoginValidate;
use think\captcha\Captcha;
use think\facade\Cache;
use think\facade\Db;

class Login extends Base
{
    /**
     * 密码登录
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function passwordLogin(UsersRepository $repository)
    {
        $param = $this->request->param([
            'mobile' => '',
            'password' => '',
            'user_code' => '',
        ]);
        validate(LoginValidate::class)->scene('captchaLogin')->check($param);
        $userInfo = $repository->getUserByMobile($param['mobile'], $this->request->companyId);

        if (!$userInfo) {
            return app('api_return')->error('账号或密码错误');
        }

        //密码错误次数限制
        $getNums = Cache::store('redis')->get('password_login_error_' . $userInfo['id']);
        if ($getNums && $getNums > 3) {
            return app('api_return')->error('账号或密码错误次数过多，请过一会儿再试');
        }

        if (!$repository->passwordVerify($param['password'], $userInfo['password'])) {
            if (!$getNums) Cache::store('redis')->set('password_login_error_' . $userInfo['id'], 1, 60 * 3);
            Cache::store('redis')->inc('password_login_error_' . $userInfo['id']);
            return app('api_return')->error('账号或密码错误');
        }
        if ($userInfo['status'] != 1) {
            return app('api_return')->error('此账号已冻结');
        }

        $repository->getUserPush($userInfo,$this->request->companyId,$param['user_code']);
        $res = $repository->createToken($userInfo);
        $userInfo->session_id = $res['token'];
        // 用户登陆事件
        event('user.login', $userInfo);
        $data = [
            'token' => $res['token'],
            'java_token' => $res['java_token'],
            'userInfo' => $repository->showApiFilter($userInfo)
        ];

        api_user_log($userInfo['id'], 1, $this->request->companyId, '账号密码登录');
        return app('api_return')->success($data);
    }

    /**
     * 短信登录
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function smsLogin(UsersRepository $repository)
    {
        validate(LoginValidate::class)->scene('smsLogin')->check($this->request->param());
        $mobile = $this->request->param('mobile');
        $smsCode = $this->request->param('sms_code');
        $userCode = $this->request->param('user_code');
        $userInfo = $repository->getUserByMobile($mobile, $this->request->companyId);

        // 短信验证
        sms_verify($this->request->companyId, $mobile, $smsCode, config('sms.sms_type.LOGIN_VERIFY_CODE'));

        if ($userInfo) {
            if ($userInfo['status'] != 1) {
                return app('api_return')->error('账号已被锁定');
            }
        } else {
            if (!$this->request->companyId == 74) return app('api_return')->error('此账号不存在');
            $userInfo = $repository->register(['mobile' => $mobile], $this->request->companyId);
            $userInfo = $repository->get($userInfo['id']);
        }
        $repository->getUserPush($userInfo,$this->request->companyId,$userCode);
        $res = $repository->createToken($userInfo);
        $userInfo->session_id = $res['token'];
        $userInfo->java_token = $res['java_token'];
        // 用户登陆事件
        event('user.login', $userInfo);

        $data = [
            'token' => $res['token'],
            'java_token' => $res['java_token'],
            'userInfo' => $repository->showApiFilter($userInfo)
        ];
        api_user_log($userInfo['id'], 1, $this->request->companyId, '短信验证码登录');
        return app('api_return')->success($data);
    }

    public function register(UsersRepository $repository)
    {
        if ($this->request->companyId == 74) {
            validate(LoginValidate::class)->scene('register')->check($this->request->param());
        } else {
            validate(LoginValidate::class)->scene('captchaRegister')->check($this->request->param());
        }

        $mobile = $this->request->param('mobile');
        $smsCode = $this->request->param('sms_code');
        $param = $this->request->param();
        if ($this->request->companyId == 74) {
            $param['nickname'] = $mobile;
            $users = $repository->getSearch(['nickname' => $mobile, 'company_id' => $this->request->companyId])->find();
            if ($users) {
                return app('api_return')->error("该账号已被注册");
            }
        } else {
            $users = $repository->search(['mobile' => $mobile], $this->request->companyId)->find();
            if ($users) {//123
                return app('api_return')->error("该账号已被注册");
            }
        }

        // 短信验证
        $certType = (int)web_config($this->request->companyId, 'site.env_type', 1);
        if ($certType == 2) {
            sms_verify($this->request->companyId, $mobile, $smsCode, config('sms.sms_type.REGISTER_VERIFY_CODE'));
        }

        $userInfo = $repository->register($param, $this->request->companyId);
        $userInfo = $repository->get($userInfo['id']);
        $res = $repository->createToken($userInfo);//
        $userInfo->session_id = $res['token'];
        $userInfo->java_token = $res['java_token'];

        $data = [
            'token' => $res['token'],
            'java_token' => $res['java_token'],
            'userInfo' => $repository->showApiFilter($userInfo)
        ];
        api_user_log($userInfo['id'], 2, $this->request->companyId, '用户注册');
        task($userInfo['id'], 1, $this->request->companyId);
        task($userInfo['id'], 1, $this->request->companyId, '', 'invite');
        return app('api_return')->success($data);
    }

    /**
     * 忘记密码
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function forgertPassword(UsersRepository $repository)
    {
        $param = $this->request->param([
            'mobile' => '',
            'password' => '',
            'sms_code' => ''
        ]);
        $data['type'] = 1;
        $userInfo = $repository->getUserByMobile($param['mobile'], $this->request->companyId);
        if (!$userInfo) {
            return app('api_return')->error('账号不存在');
        }
        sms_verify($this->request->companyId, $param['mobile'], $param['sms_code'], config('sms.sms_type.MODIFY_PASSWORD'));

        if ($userInfo['status'] != 1) {
            return app('api_return')->error('此账号已冻结');
        }
        unset($param['sms_code']);
        unset($param['mobile']);
        $repository->editPasswordInfo($userInfo, $param);
        $res = $repository->createToken($userInfo);
        $userInfo->session_id = $res['token'];
        $userInfo->java_token = $res['java_token'];
        // 用户登陆事件
        event('user.login', $userInfo);
        $data = [
            'token' => $res['token'],
            'java_token' => $res['java_token'],
            'userInfo' => $repository->showApiFilter($userInfo)
        ];
        return app('api_return')->success($data);
    }

    /**
     * 用户忘记密码 判断验证码是否正确
     *
     * @param UsersRepository $repository
     * @return mixed
     */
    public function verifiCodeTrue(UsersRepository $repository)
    {
        $param = $this->request->param([
            'mobile' => '',
            'sms_code' => ''
        ]);
        $data['type'] = 1;
        $userInfo = $repository->getUserByMobile($param['mobile'], $this->request->companyId);
        if (!$userInfo) {
            return app('api_return')->error('账号不存在');
        }
        sms_verify($this->request->companyId, $param['mobile'], $param['sms_code'], config('sms.sms_type.MODIFY_PASSWORD'));
    }


    /**
     *  TODO:
     *       应客户要求  在用户未注册的情况下  输入账号和密码直接注册并且登陆成功！
     * @param UsersRepository $repository
     * @return mixed
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function MobileLoginNew(UsersRepository $repository)
    {
        $param = $this->request->param([
            'mobile' => '',
        ]);
        $userInfo = $repository->getUserByMobile($param['mobile'], $this->request->companyId);
        if (!$userInfo) {
            /***************************2023.06.19  begin***********************************************/
            $userInfo = $repository->register($this->request->param(), $this->request->companyId);
            $userInfo = $repository->get($userInfo['id']);
            $res = $repository->createToken($userInfo);
            $userInfo->session_id = $res;
            $data = [
                'token' => $res,
                'userInfo' => $repository->showApiFilter($userInfo)
            ];
            api_user_log($userInfo['id'], 2, $this->request->companyId, '用户注册');
            task($userInfo['id'], 1, $this->request->companyId);
            return app('api_return')->success($data);
            /***************************2023.06.19  end***********************************************/
            //return app('api_return')->error('账号或密码错误');
        }
        if ($userInfo['status'] != 1) {
            return app('api_return')->error('此账号已冻结');
        }
        $res = $repository->createToken($userInfo);
        $userInfo->session_id = $res['token'];
        $userInfo->java_token = $res['java_token'];
        // 用户登陆事件
        event('user.login', $userInfo);
        $data = [
            'token' => $res,
            'java_token' => $res['java_token'],
            'userInfo' => $repository->showApiFilter($userInfo)
        ];
        api_user_log($userInfo['id'], 1, $this->request->companyId, '手机号一键登录');
        return app('api_return')->success($data);
    }

    public function queryUserCode(UsersRepository $repository)
    {
        $param = $this->request->param([
            'user_code' => '',
        ]);
        if (!isset($param['user_code'])) return app('api_return')->error('请输入邀请码');
        $userInfo = $repository->search([])->where(['user_code' => $param['user_code']])
            ->with(['avatars' => function ($query) {
                $query->bind(['avatar' => 'show_src']);
            }])
            ->withAttr('mobile', function ($v, $data) {
                return substr_replace($data['mobile'], '****', 3, 4);
            })
            ->field('id,nickname,head_file_id,user_code,mobile')->find();
        return app('api_return')->success($userInfo);
    }

    /**
     * 微信登录
     *
     * @param UsersRepository $repository
     * @return mixed
     */
    public function wechatLogin(UsersRepository $repository)
    {
        $data = $this->request->param();
        $code = $data['code'];
        $userCode = $data['user_code'];
        $res = $this->getWechatInfoByAPP($code);
        if ($res['code'] != 200) {
            $res = $res['msg'];
            return $this->error($res);
        }
        $data['openid'] = $res['data']['openid'];
        $data['nickname'] = $res['data']['nickname'];
        $data['unionid'] = $res['data']['unionid'];
        if (!$data['openid']) return $this->error('openid错误');
        //判断是登录还是注册
        $UploadFileRepository = app()->make(UploadFileRepository::class);
        $is_user = app()->make(UsersRepository::class)->getWhere(['openid' => $data['openid']], 'id');
        if (!$is_user) {
            //注册
            $userInfo = $repository->register($data, $this->request->companyId);
            $userInfo = $repository->get($userInfo['id']);
        } else {
            $userInfo = $repository->get($is_user['id']);
//            $head_img_arr = $UploadFileRepository->search(['id' => $userInfo['head_file_id']])->find();
//            $userInfo->avatar = $head_img_arr['show_src'];
        }
        $repository->getUserPush($userInfo,$this->request->companyId,$userCode);
        $res = $repository->createToken($userInfo);
        $userInfo->session_id = $res['token'];
        $userInfo->java_token = $res['java_token'];
        $data = [
            'token' => $res['token'],
            'java_token' => $res['java_token'],
            'userInfo' => $repository->showApiFilter($userInfo)
        ];
        api_user_log($userInfo['id'], 2, $this->request->companyId, '用户注册');
        return app('api_return')->success($data);
    }

    /**
     * 获取微信用户信息
     */
    protected function getWechatInfoByAPP($code)
    {
        if (!$code) $this->error('请填写正确的code');

        $app_id = web_config($this->request->companyId, 'program.wechat.appid', ''); // 开放平台APP的id
        $app_secret = web_config($this->request->companyId, 'program.wechat.secret', '');
        if (!$app_id) return ['code' => "502", 'msg' => "微信参数未配置"];
        if (!$app_secret) return ['code' => "502", 'msg' => "微信参数未配置"];
        $url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid={$app_id}&secret={$app_secret}&code={$code}&grant_type=authorization_code";
        $data = $this->curl_get($url);

        if ($data['code'] != 200 || !isset($data['data'])) {
            return ['code' => "500", 'msg' => "登录错误" . $data['errmsg']];
        }
        $data = $data['data'];
        if (isset($data['errcode']) && $data['errcode']) {
            return ['code' => "502", 'msg' => "code错误," . $data['errmsg']];
        }
        // 请求用户信息
        $info_url = "https://api.weixin.qq.com/sns/userinfo?access_token={$data['access_token']}&openid={$data['openid']}";
        $user_info = $this->curl_get($info_url);
        file_put_contents('wechat_login.txt', var_export('user_info', true), FILE_APPEND);
        file_put_contents('wechat_login.txt', var_export($user_info, true), FILE_APPEND);

        if ($user_info['code'] != 200 || !isset($user_info['data'])) {
            return ['code' => "500", 'msg' => "登录错误" . $user_info['errmsg']];
        }
        $data = $user_info['data'];
        if (!isset($data['openid']) || !isset($data['nickname']) || !isset($data['headimgurl'])) {
            return ['code' => "500", 'msg' => "APP登录失败,网络繁忙"];
        }
        return ['code' => 200, 'data' => $data];
    }

// curl get请求
    protected function curl_get($url)
    {
        $header = [
            'Accept: application/json',
        ];
        $curl = curl_init();
        // 设置抓取的url
        curl_setopt($curl, CURLOPT_URL, $url);
        // 设置头文件的信息作为数据流输出
        curl_setopt($curl, CURLOPT_HEADER, false);
        // 超时设置,以秒为单位
        curl_setopt($curl, CURLOPT_TIMEOUT, 1);

        // 超时设置，以毫秒为单位
        // curl_setopt($curl, CURLOPT_TIMEOUT_MS, 500);

        // 设置请求头
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        // 设置获取的信息以文件流的形式返回，而不是直接输出。
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        // 执行命令
        $data = curl_exec($curl);
        $error = curl_error($curl);
        curl_close($curl);

        // 显示错误信息
        if ($error) {
            return ['code' => 500, 'msg' => $error];
        } else {
            return ['code' => 200, 'msg' => 'success', 'data' => json_decode($data, true)];
        }
    }


}