<?php

namespace app\common\repositories\users;

use app\common\dao\users\UsersDao;
use app\common\model\game\KillRebateModel;
use app\common\model\mine\MineUserModel;
use app\common\repositories\agent\AgentRepository;
use app\common\repositories\BaseRepository;
use app\common\repositories\game\GameJadeLogLogRepository;
use app\common\repositories\game\KillRepository;
use app\common\repositories\game\LevelTeamRepository;
use app\common\repositories\givLog\MineGivLogRepository;
use app\common\repositories\mine\MineDispatchRepository;
use app\common\repositories\mine\MineUserRepository;
use app\common\repositories\system\upload\UploadFileRepository;
use app\common\repositories\top\UsersTopRepository;
use app\common\repositories\users\user\AgentUserRepository;
use app\common\services\JwtService;
use app\helper\SnowFlake;
use think\exception\ValidateException;
use think\facade\Cache;
use think\facade\Db;

/**
 * Class UsersRepository
 *
 * @mixin UsersDao
 */
class UsersRepository extends BaseRepository
{
    public function __construct(UsersDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 密码验证
     *
     * @param $password
     * @param string $userPassword 用户密码
     * @return string
     */
    public function passwordVerify($password, $userPassword)
    {
        return password_verify($password, $userPassword);
    }


    public function getList(array $where, $page, $limit, int $companyId = null)
    {
        $query = $this->dao->search($where, $companyId);
        $count = $query->count();
        $integralLogRepository = app()->make(UsersIntegralLogRepository::class);
        $list = $query->page($page, $limit)
            ->with([
                'tjrInfo' => function ($query) {
                    $query->field('company_id,user_id,parent_id,levels')->with([
                        'tjrOne' => function ($query) {
                            $query->bind(['user_code','mobile', 'nickname']);
                        }
                    ]);
                },
                'cert' => function ($query) {
                    $query->field('id,cert_status,remark,username');
                },
            ])->withCount(['pool' => function ($query) {
                $query->where(['status' => 1]);
            }])
            ->append(['zt_num'])
            ->order('id', 'desc')
            ->select();
        foreach ($list as &$item) {
            $item['team_vip_name'] = '普通用户';
            if ($item['team_vip'] == 0) {
                $item['team_vip_name'] = '有效用户';
            }
            if ($item['team_vip'] > 0) {
                $item['team_vip_name'] = app()->make(LevelTeamRepository::class)->search(['level' => $item['team_vip']])->value('name');
            }
        }

        return compact('list', 'count');
    }


    public function getAgentList(array $where, $page, $limit, int $companyId = null)
    {

        /** @var AgentUserRepository $agentUserRepository */
        $agentUserRepository = app()->make(AgentUserRepository::class);
        $userInfo = $agentUserRepository->getLoginUserInfo();

        /** @var UsersPushRepository $usersPushRepository */
        $usersPushRepository = app()->make(UsersPushRepository::class);

        $ids = $usersPushRepository->search(['parent_id' => $userInfo['id']])->whereIn('levels', [1, 2, 3])->column('user_id');


        $query = $this->dao->search($where, $companyId)->whereIn('id', $ids);
        $count = $query->count();
        $integralLogRepository = app()->make(UsersIntegralLogRepository::class);
        $list = $query->page($page, $limit)
            ->with([
                'tjrInfo' => function ($query) {
                    $query->field('company_id,user_id,parent_id,levels')->with([
                        'tjrOne' => function ($query) {
                            $query->bind(['mobile', 'nickname']);
                        }
                    ]);
                },
                'cert' => function ($query) {
                    $query->field('id,cert_status,remark,username');
                },
            ])->withCount(['pool' => function ($query) {
                $query->where(['status' => 1]);
            }])
            ->append(['zt_num'])
            ->order('id', 'desc')
            ->select();

        return compact('list', 'count');
    }


    public function getHoldList(array $where, $page, $limit, int $companyId = null)
    {

        $ids = app()->make(UsersPoolRepository::class)->search(['pool_id' => $where['pool_id']], $companyId)
            ->whereIn('status', [1, 2])
            ->group('uuid')->column('uuid');

        $query = $this->dao->search($where, $companyId)->whereIn('id', $ids)->field('id,company_id,mobile');
        $count = $query->count();
        $list = $query->page($page, $limit)
            ->withCount(['pool' => function ($query) use ($where) {
                $query->where(['pool_id' => $where['pool_id']])->whereIn('status', [1, 2]);
            }])
            ->order('id', 'desc')
            ->select();

        return compact('list', 'count');
    }

    public function editInfo($info, $data)
    {

        if (isset($data['type'])) {
            switch ((int)$data['type']) {
                case '1':
                    if (isset($data['password']) && $data['password'] !== '') {
                        $data['password'] = $this->passwordEncrypt($data['password']);
                    }
                    break;
                case '2':
                    if (isset($data['password']) && $data['password'] !== '') {
                        $data['pay_password'] = $this->passwordEncrypt($data['password']);
                        unset($data['password']);
                    }
                    break;
            }
        }
        if (isset($data['avatar']) && $data['avatar'] !== '') {
            /** @var UploadFileRepository $uploadFileRepository */
            $uploadFileRepository = app()->make(UploadFileRepository::class);
            $fileInfo = $uploadFileRepository->getFileData($data['avatar'], 2, $info['company_id']);
            if ($fileInfo['id'] != $info['head_file_id']) {
                $data['head_file_id'] = $fileInfo['id'];
            }
        }
        unset($data['type']);
        unset($data['avatar']);
        return $this->dao->update($info['id'], $data);
    }

    /**
     * 密码加密
     *
     * @param $password
     * @return string
     */
    public function passwordEncrypt($password)
    {
        return password_hash($password, PASSWORD_BCRYPT);
    }

    public function editPasswordInfo($info, $data)
    {
        if (isset($data['password']) && $data['password'] !== '') {
            $data['password'] = $this->passwordEncrypt($data['password']);
        }
        return $this->dao->update($info['id'], $data);
    }

    public function batchSetUser(array $userId, array $data)
    {
        $list = $this->dao->selectWhere([
            ['id', 'in', $userId]
        ]);
        if ($list) {
            foreach ($list as $k => $v) {
                $this->dao->update($v['id'], $data);
            }
            return $list;
        }
        return [];
    }

    public function getDetail(int $id)
    {
        $with = [
            'tjrInfo' => function ($query) {
                $query->with([
                    'tjrOne' => function ($query) {
                        $query->bind(['mobile', 'nickname']);
                    }
                ]);
            },
            'cert' => function ($query) {
                $query->field('id,front_file_id,back_file_id,username,number,cert_status,remark')->with([
                    'frontFile' => function ($query) {
                        $query->bind(['idcard_front_photo' => 'show_src']);
                    },
                    'backFile' => function ($query) {
                        $query->bind(['idcard_back_photo' => 'show_src']);
                    }
                ]);
            },
            'avatars' => function ($query) {
                $query->field('id,show_src');
                $query->bind(['avatar' => 'show_src']);
            }
        ];
        $data = $this->dao->search([])
            ->with($with)
            ->where('id', $id)
            ->hidden(['avatars'])
            ->find();
        event('user.mine', $data);
        return $data;
    }

    /**
     * 产出币种变动
     * @param $userId
     * @param $type
     * @param $amount
     * @param array $data
     * @param $trackPort
     * @return \app\common\dao\BaseDao|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function foodChange($userId, $type, $amount, $data = [], $trackPort = 4, $companyId = null)
    {
        return Db::transaction(function () use ($userId, $type, $amount, $data, $trackPort, $companyId) {
            $userInfo = $this->dao->search([], $companyId)->where('id', $userId)->lock(true)->find();
            if ($userInfo) {
                $beforeChange = $userInfo->food;
                $userInfo->food = bcadd($userInfo->food, $amount, 2);
                $userInfo->save();
                $afterChange = $userInfo->food;
                /**
                 * @var UsersBalanceLogRepository $balanceLogRepository
                 */
                $balanceLogRepository = app()->make(UsersBalanceLogRepository::class);
                return $balanceLogRepository->addLog($userInfo['id'], $amount, $type, array_merge($data, [
                    'before_change' => $beforeChange,
                    'after_change' => $afterChange,
                    'track_port' => $trackPort,
                ]));
            }
        });
        return false;
    }

    /**
     * 批量设置代币变动
     *
     * @param array $userId 用户ID
     * @param int $type 变动类型
     * @param float $amount 变动金额
     * @param array $data 其他数据 note:备注 link_id:关联ID
     * @return \app\common\dao\BaseDao|\think\Model|void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function batchFoodChange($userId, $type, $amount, $data = [], $trackPort = 1)
    {
        $userInfo = $this->dao->selectWhere([
            ['id', 'in', $userId]
        ]);
        if ($userInfo) {
            foreach ($userInfo as $k => $v) {
                $beforeChange[] = $v->food ?: 0.00;
                $v->food = round($v->food + $amount, 7);
                $v->save();
                $afterChange[] = $v->food;
            }
            /**
             * @var UsersFoodLogRepository $usersFoodLogRepository
             */
            $usersFoodLogRepository = app()->make(UsersFoodLogRepository::class);
            return $usersFoodLogRepository->batchAddLog($userInfo, $amount, $type, $data, $beforeChange, $afterChange, $trackPort);
        }
    }

    /**
     * 批量设置代币变动
     *
     * @param array $userId 用户ID
     * @param int $type 变动类型
     * @param float $amount 变动金额
     * @param array $data 其他数据 note:备注 link_id:关联ID
     * @return \app\common\dao\BaseDao|\think\Model|void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function batchGoldChange($userId, $type, $amount, $data = [], $trackPort = 1)
    {
        $userInfo = $this->dao->selectWhere([
            ['id', 'in', $userId]
        ]);
        if ($userInfo) {
            foreach ($userInfo as $k => $v) {
                $beforeChange[] = $v->gold ?: 0.00;
                $v->gold = round($v->gold + $amount, 7);
                $v->save();
                $afterChange[] = $v->gold;
            }
            /**
             * @var UsersGoldLogRepository $usersFoodLogRepository
             */
            $usersFoodLogRepository = app()->make(UsersGoldLogRepository::class);
            return $usersFoodLogRepository->batchAddLog($userInfo, $amount, $type, $data, $beforeChange, $afterChange, $trackPort);
        }
    }

    /**
     * 批量设置代币变动
     *
     * @param array $userId 用户ID
     * @param int $type 变动类型
     * @param float $amount 变动金额
     * @param array $data 其他数据 note:备注 link_id:关联ID
     * @return \app\common\dao\BaseDao|\think\Model|void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function batchScoreChange($userId, $type, $amount, $data = [], $trackPort = 1)
    {
        $userInfo = $this->dao->selectWhere([
            ['id', 'in', $userId]
        ]);
        if ($userInfo) {
            foreach ($userInfo as $k => $v) {
                $beforeChange[] = $v->score ?: 0.00;
                $v->score = round($v->score + $amount, 7);
                $v->save();
                $afterChange[] = $v->score;
            }
            /**
             * @var UsersScoreLogRepository $usersScoreLogRepository
             */
            $usersScoreLogRepository = app()->make(UsersScoreLogRepository::class);
            return $usersScoreLogRepository->batchAddLog($userInfo, $amount, $type, $data, $beforeChange, $afterChange, $trackPort);
        }
    }


    public function batchFood($userId, $type, $amount, $data = [], $trackPort = 1)
    {
        $userInfo = $this->dao->selectWhere([
            ['id', 'in', $userId]
        ]);
        if ($userInfo) {
            /** @var UsersFoodTimeRepository $usersFoodTimeRepository */
            $usersFoodTimeRepository = app()->make(UsersFoodTimeRepository::class);
            foreach ($userInfo as $k => $v) {
                if ($this->incField($v->id, 'food', $amount)) {
                    $afterChange[] = $v->food;
                    $arr['uuid'] = $v['id'];
                    $arr['price'] = $amount;
                    $arr['status'] = 2;
                    $arr['type'] = $data['type'];
                    $arr['dis_id'] = $data['dis_id'];
                    $usersFoodTimeRepository->addInfo($v['company_id'], $arr);
                }
            }
            return true;
        }
    }


    /**
     * 余额变动
     *
     * @param int $userId 用户ID
     * @param int $type 变动类型
     * @param float $amount 变动金额
     * @param array $data 其他数据 note:备注 link_id:关联ID
     * @return \app\common\dao\BaseDao|\think\Model|void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function balanceChange($userId, $type, $amount, $data = [], $trackPort = 4)
    {
        $userInfo = $this->dao->get($userId);
        if ($userInfo) {
            $beforeChange = $userInfo->balance;
            $userInfo->balance = bcadd($userInfo->balance, $amount, 2);
            $userInfo->save();
            $afterChange = $userInfo->balance;
            /**
             * @var UsersBalanceLogRepository $balanceLogRepository
             */
            $balanceLogRepository = app()->make(UsersBalanceLogRepository::class);
            return $balanceLogRepository->addLog($userInfo['id'], $amount, $type, array_merge($data, [
                'before_change' => $beforeChange,
                'after_change' => $afterChange,
                'track_port' => $trackPort,
            ]));
        }
    }

    /**
     * 批量设置余额变动
     *
     * @param array $userId 用户ID
     * @param int $type 变动类型
     * @param float $amount 变动金额
     * @param array $data 其他数据 note:备注 link_id:关联ID
     * @return \app\common\dao\BaseDao|\think\Model|void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function batchBalanceChange($userId, $type, $amount, $data = [], $trackPort = 1)
    {
        $userInfo = $this->dao->selectWhere([
            ['id', 'in', $userId]
        ]);
        if ($userInfo) {
            foreach ($userInfo as $k => $v) {
                $beforeChange[] = $v->balance;
                $v->balance = bcadd($v->balance, $amount, 2);
                $v->save();
                $afterChange[] = $v->balance;
            }

            /**
             * @var UsersBalanceLogRepository $balanceLogRepository
             */
            $balanceLogRepository = app()->make(UsersBalanceLogRepository::class);
            return $balanceLogRepository->batchAddLog($userInfo, $amount, $type, $data, $beforeChange, $afterChange, $trackPort);
        }
    }

    /**
     * 221仙玉
     * @param $userId
     * @param $type
     * @param $amount
     * @param array $data
     * @param $trackPort
     * @return \app\common\dao\BaseDao|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function jadeChange($userId, $type, $amount, $data = [], $trackPort = 4, $companyId = null)
    {
        return Db::transaction(function () use ($userId, $type, $amount, $data, $trackPort, $companyId) {
            $userInfo = $this->dao->search([], $companyId)->where('id', $userId)->lock(true)->find();
            if ($userInfo) {
                $beforeChange = $userInfo->jade;
                $userInfo->jade = bcadd($userInfo->jade, $amount, 7);
                $userInfo->save();
                $afterChange = $userInfo->jade;
                /**
                 * @var GameJadeLogLogRepository $gameJadeLogLogRepository
                 */
                $gameJadeLogLogRepository = app()->make(GameJadeLogLogRepository::class);
                return $gameJadeLogLogRepository->addLog($userInfo['id'], $amount, $type, array_merge($data, [
                    'before_change' => $beforeChange,
                    'after_change' => $afterChange,
                    'track_port' => $trackPort,
                    'company_id' => $companyId
                ]));
            }
        });
        return false;
    }


    /**
     * 注册账号
     *
     * @param int $companyId 企业ID
     * @param array $data 用户数据
     * @return \app\common\dao\BaseDao|\think\Model
     */
    public function register(array $data, int $companyId)
    {
        if (empty($data['nickname']) && isset($data['mobile'])) {
            $data['nickname'] = substr_replace($data['mobile'], '****', 3, 4);
        }

        if (empty($data['password'])) $data['password'] = 123456;
        if (empty($data['pay_password'])) $data['pay_password'] = 123456;

        $tjRegCodeType = (int)web_config($companyId, 'reg.tj_reg_code_type');
        if ($tjRegCodeType == 1 && !$data['user_code']) {
            throw new ValidateException('邀请码不能为空!');
        }
        if (isset($data['user_code']) && $data['user_code']) {
            $tjr_account = $this->dao->getWhere(['user_code' => $data['user_code']], 'id');
            if (!$tjr_account) throw new ValidateException('邀请码不存在!');
            $data['tjr_account'] = $tjr_account['id'];
            $tjr_account_id = $this->dao->getWhere(['user_code' => $data['user_code']], 'id');
            $parent_agent = app()->make(AgentRepository::class)->search(['uuid' => $tjr_account_id['id']], $companyId)->find();
            if ($companyId == 35 && !$parent_agent) throw new ValidateException('邀请码无效!');
        }
        $user = $this->addInfo($companyId, $data);
        if (isset($tjr_account_id)) {
            $key = 'invitation_rewards:' . $tjr_account_id;
            Cache::store('redis')->sadd($key, $user['id']);
        }
        // TODO END
        return $user;
    }

    public function addInfo(int $companyId = null, array $data = [])
    {

        return Db::transaction(function () use ($data, $companyId) {
            if ($companyId) $data['company_id'] = $companyId;
            if (isset($data['password']) && $data['password'] !== '') {
                $data['password'] = $this->passwordEncrypt($data['password']);
            }
            if (isset($data['pay_password']) && $data['pay_password'] !== '') {
                $data['pay_password'] = $this->passwordEncrypt($data['pay_password']);
            }
            $data['regist_ip'] = Request()->ip();
            $data['unquied'] = $companyId . rand(1, 9999999999);

            $data['is_mine'] = 2;

            $userInfo = $this->create($data, $companyId);
            if (isset($data['tjr_account']) && $data['tjr_account']) {
                $tjrInfo = $this->dao->getWhere(['id' => $data['tjr_account']], 'id');
                /**
                 * @var UsersPushRepository $usersPushRepository
                 */
                $usersPushRepository = app()->make(UsersPushRepository::class);
                $usersPushRepository->batchTjr($userInfo, $tjrInfo['id'], $companyId);
            }
            event('user.mine', $userInfo);
            return $userInfo;
        });
    }

    public function create(array $data, $companyId)
    {
        if (!isset($data['unquied']) || !$data['unquied']) {
            $unquied = time() . rand(1000, 9999) . rand(1000, 9999);
            $unquied = str_split($unquied);
            shuffle($unquied);
            $unquied = implode('', $unquied);
            $data['unquied'] = $unquied;
        }
        if (!isset($data['user_code']) || !$data['user_code']) {
            $userCode = $this->makeInviterCode($companyId);
            $data['user_code'] = $userCode;
        }
        return $this->dao->create($data);
    }

    public function makeInviterCode($companyId, $start = 0, $end = 9, $length = 8)
    {
        if ($companyId == 50) $length = 7;
        //初始化变量为0
        $connt = 0;
        //建一个新数组
        $temp = array();
        while ($connt < $length) {
            //在一定范围内随机生成一个数放入数组中
            $temp[] = mt_rand($start, $end);
            //$data = array_unique($temp);
            //去除数组中的重复值用了“翻翻法”，就是用array_flip()把数组的key和value交换两次。这种做法比用 array_unique() 快得多。
            $data = array_flip(array_flip($temp));
            //将数组的数量存入变量count中
            $connt = count($data);
        }
        //为数组赋予新的键名
        shuffle($data);
        //数组转字符串
        $str = implode(",", $data);
        //替换掉逗号
        $number = str_replace(',', '', $str);
        $code = $this->dao->search(['user_code' => $number], $companyId)->find();
        if ($code) return $this->makeInviterCode($companyId);
        return $number;
    }
    
      public function getUserPush($userInfo,$companyId,$user_code){
        $usersPushRepository = app()->make(UsersPushRepository::class);
        $tjRegCodeType = (int)web_config($companyId, 'reg.tj_reg_code_type');
        $ids = $usersPushRepository->search(['user_id' => $userInfo['id']])->column('user_id');
        if ($tjRegCodeType == 1 && !$ids) {
            if(!empty($user_code)){
                $tjrInfo = $this->dao->getWhere(['user_code' => $user_code], 'id');
                if(!$tjrInfo){
                    throw new ValidateException('推荐人不存在!');
                }
                $usersPushRepository->batchTjr($userInfo, $tjrInfo['id'], $companyId);
            }else{
                throw new ValidateException('邀请码不能为空!');
            }
        }
    }

    /**
     * 创建token
     *
     * @param User $userInfo 用户信息
     * @return string
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function createToken($userInfo)
    {
        $service = new JwtService();
        $res = $service->createToken($userInfo['id'], 'user', strtotime('+365 day'));
        $token = sha1($res['token']);

        Cache::store('redis')->set('redis_' . $token, $res['token']);
        Cache::store('redis')->set('token_' . $userInfo['id'], $token);
        return ['token' => $token, 'java_token' => $res['token']];
    }

    /**
     * 更新token
     *
     * @param string $token
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function updateToken(string $token)
    {
        Cache::store('redis')->set('redis_' . $token, $this->getToken(), 86400);
    }

    /**
     * 生成token
     *
     * @param string $token token
     * @return string
     */
    public function getToken($token = '')
    {
        if ($token) {
            $token = 'redis_' . $token;
        } else {
            $token = 'redis_' . app('request')->header('Token');
        }
        return Cache::store('redis')->get($token);
    }

    /**
     * 清除token
     *
     */
    public function clearToken()
    {
        Cache::store('redis')->delete('redis_' . app('request')->header('Token'));
    }

    /**
     * 过滤显示的用户信息
     *
     * @param array userInfo 用户信息
     * @return array
     */
    public function showApiFilter($userInfo)
    {
        $data = $userInfo
            ->append(['personal_auth', 'queue'])
            // ->append(['labels'])
            ->hidden([
                'password',
                'session_id',
                'pay_password'
            ]);
        //        $data = $data->toArray();
        $pushInfos = app()->make(UsersPushRepository::class)->search(['parent_id' => $userInfo['id']], $userInfo['company_id'])
            ->where('levels', '<', 3)->column('user_id');
        $data['invitedCount'] = count($pushInfos);
        $data['mine_dispatch_num'] = app()->make(MineUserRepository::class)->search([], $userInfo['company_id'])
            ->whereIn('uuid', $pushInfos)->where('dispatch_count', '>', 0)->count();

        $tokens_rate = web_config($data['company_id'], 'program');
        if (isset($tokens_rate['mine']['tokens']['rate']) && $tokens_rate['mine']['tokens']['rate']) {
            $data['tokens_rate'] = $tokens_rate['mine']['tokens']['rate'] / 100;
            // $data['gold_rate'] = $tokens_rate['mine']['tokens']['rate_gold'] / 100;

            if ($data['company_id'] == 57) {
                //如果有有效的矿工卡，则减免手续费
                $mineDispatchCount = app()->make(MineUserRepository::class)->search(['uuid' => $userInfo['id']], $userInfo['company_id'])
                    ->where('dispatch_count', '>', 0)->count();
                if ($mineDispatchCount > 0 && ($data['tokens_rate'] - 0.1) > 0) {
                    $data['tokens_rate'] = $data['tokens_rate'] - 0.1;
                }
            }
        }
        if ($data['is_top']) {
            $data['tokens_rate'] = 0;
        }

        /** @var AgentRepository $agentRepository */
        $agentRepository = app()->make(AgentRepository::class);
        $agent = $agentRepository->search(['uuid' => $data['id']])->find();
        $data['is_agent'] = !$agent ? 0 : 1;
        $data['agent_level'] = $agent ? $agent['level'] : -1;
        /** @var UsersPoolRepository $usersPoolRepository */
        $usersPoolRepository = app()->make(UsersPoolRepository::class);
        $data['countPool'] = $usersPoolRepository->search(['uuid' => $userInfo['id'], 'status' => 1])->count('id');
        $data['expiryPool'] = $usersPoolRepository->search(['uuid' => $userInfo['id']])->where('status', '>', 1)->count('id');

        $total = 0;
        $product = (new MineUserModel())->alias('mu')
            ->where(['mu.status' => 1, 'mu.uuid' => $data['id']])
            ->where('mu.level', '>', 1)
            ->join('mine m', 'mu.mine_id = m.id')
            ->whereExp('mu.dispatch_count', '> 0')
            ->sum('m.day_output');
        if ($product) $total += $product;
        $chuji = (new MineUserModel())->alias('mu')
            ->where(['mu.status' => 1, 'mu.level' => 1, 'mu.uuid' => $data['id']])
            ->join('mine m', 'mu.mine_id = m.id')
            ->whereExp('mu.dispatch_count', '> 0')
            ->find();
        if ($chuji) {
            $tatal2 = get_rate($chuji['dispatch_count'], $userInfo['company_id']);
            if ($tatal2) $total += $tatal2['total'];
        }

        /** @var MineDispatchRepository $mineDispatchRepository */
        $mineDispatchRepository = app()->make(MineDispatchRepository::class);
        $dis = $mineDispatchRepository->search(['uuid' => $data['id']])
            ->whereExp('dispatch_count', '> 0')
            ->find();
        if ($dis) {
            $tatal1 = get_rate1($dis['dispatch_count'], $userInfo['company_id'], $data['id']);
            if ($tatal1) $total += $tatal1['total'];
        }
        $where[] = [
            ['levels', '<=', 3]
        ];
        if ($userInfo['company_id'] == 74) {
            $where[] = [
                ['levels', '<=', 2]
            ];
        }
        //各级人数、奖励提升
        $invite_total = app()->make(UsersPushRepository::class)->search(['parent_id' => $userInfo['id']], $userInfo['company_id'])
            ->where($where)
            ->group('levels')
            ->field('levels,count(user_id) invite_count')
            ->select()->toArray();
        $data['invite_total'] = [
            'levels_1_count' => 0,
            'levels_2_count' => 0,
            'levels_3_count' => 0,
            'levels_1_rate' => bcmul(web_config($data['company_id'], 'program.node.one.rate'), '100'),
            'levels_2_rate' => bcmul(web_config($data['company_id'], 'program.node.two.rate'), '100'),
            'levels_3_rate' => bcmul(web_config($data['company_id'], 'program.node.three.rate'), '100'),
        ];
        foreach ($invite_total as $invite) {
            $key = 'levels_' . $invite['levels'] . '_count';
            if (isset($data['invite_total'][$key])) {
                $temp = $data['invite_total'];
                $temp[$key] = $invite['invite_count'];
                $data['invite_total'] = $temp;
            }
        }

        /** @var MineUserRepository $mineUserRepository */
        $mineUserRepository = app()->make(MineUserRepository::class);
        $childDayRate = $mineUserRepository->getChildDay($data, $data['company_id']);
        $data['product'] = round(($total + $childDayRate), 7);
        $data['team_total'] = app()->make(UsersPushRepository::class)->search(['parent_id' => $userInfo['id']], $userInfo['company_id'])->count();
        /** @var UsersFoodLogRepository $usersFoodLogRepository */
        $usersFoodLogRepository = app()->make(UsersFoodLogRepository::class);
        $data['income'] = $usersFoodLogRepository->search(['user_id' => $data['id']], $data['company_id'])->where(['user_id' => $userInfo['id']])->where('amount', '>', 0)->sum('amount');
        $data['expenditure'] = $usersFoodLogRepository->search(['user_id' => $data['id']], $data['company_id'])->where(['user_id' => $userInfo['id']])->where('amount', '<', 0)->sum('amount');
        $data['giv_pwd'] = web_config($data['company_id'], 'program.giv_pwd');
        $data['parent_user_code'] = $data['user_code'];
        if ($data['is_agent'] == 0) {
            $parent = app()->make(UsersPushRepository::class)->search(['user_id' => $userInfo['id'], 'levels' => 1], $userInfo['company_id'])->find();
            if ($parent) {
                $data['parent_user_code'] = $this->dao->search([])->where('id', $parent['parent_id'])->value('user_code');
            }
        }
        /** @var UsersFoodLogRepository $usersFoodLogRepository */
        $usersFoodLogRepository = app()->make(UsersFoodLogRepository::class);
        $data['totayFood'] = $usersFoodLogRepository->search(['uuid' => $data['id']], $data['company_id'])->where('amount', '>', 0)->sum('amount');
        /** @var MineGivLogRepository $mineGivLogRepository */
        $mineGivLogRepository = app()->make(MineGivLogRepository::class);
        $data['givNum'] = $mineGivLogRepository->search(['uuid' => $data['id'], 'type' => 2])->sum('num');

        //        /** @var GameJadeLogLogRepository $gameJadeLogLogRepository */
        //        $gameJadeLogLogRepository = app()->make(GameJadeLogLogRepository::class);
        //        $todayRole = $gameJadeLogLogRepository->search(['uuid'=>$data['id']],$data['company_id'])->whereTime('add_time','today')->count('id');
        //        $data['todayRole'] = $todayRole;
        $data['banks'] = Db::table('banks')->where('uuid', $userInfo['id'])->find();
        $data['egg_start'] = Db::table('toys_egg')->where('uuid', $userInfo['id'])->order('add_time desc')->value('add_time');
        $data['egg_end'] = date("Y-m-d 00:00:00", strtotime("+1 day"));
        $data['egg_num'] = Db::table('toys_egg')->where('uuid', $userInfo['id'])->where('status', 2)->count('id');
        $data['egg_use_num'] = Db::table('toys_egg')->where('uuid', $userInfo['id'])->where('status', 1)->count('id');
        //        $data['team_dividend'] = app()->make(LevelTeamRepository::class)->search(['level' => $data['team_level']], $data['company_id'])->value('dividend');
        if ($data['team_level'] == -1 && $data['countPool'] > 0) {
            $this->dao->update($data['id'], ['team_vip' => 0]);
            $data['team_vip'] = 0;
        }
        if ($data['team_level'] == 0 && $data['countPool'] <= 0) {
            $this->dao->update($data['id'], ['team_vip' => -1]);
            $data['team_vip'] = -1;
        }

        // 用户vip等级
        $vipLevel = $this->getUserVipLevel($data['vip_level'], $data['id'], $data['company_id']);
        if ($vipLevel > $data['vip_level']) {
            $this->editInfo($data, ['vip_level' => $vipLevel]);
        }
        $data['watch_num'] = Db::name('users_adver')
            ->where(['company_id' => $data['company_id'], 'uuid' => $data['id']])
            ->whereTime('add_time', 'today')->count();
        return $data;
    }

    public function giv($data, $userInfo, $companyId)
    {
        $rate = web_config($companyId, 'program.mine.tokens.rate');
        if ($companyId != 35) {
            if (!$rate) throw new ValidateException('请先设置手续费比例');
        }
        if ($data['num'] <= 10) throw new ValidateException('转赠数量不能小于10');

        /** @var UsersCertRepository $usersCertRepository */
        $usersCertRepository = app()->make(UsersCertRepository::class);
        $cert = $usersCertRepository->search(['status' => 2], $companyId)->where(['id' => $userInfo['cert_id']])->find();
        if (!$cert) throw new ValidateException('你的账户还未实名！');

        $res1 = Cache::store('redis')->incr('giv_' . $userInfo['id']);
        if ($res1 > 1) {
            throw new ValidateException('操作太快,请稍后再试');
        }
        Cache::store('redis')->expire('giv_' . $userInfo['id'], 3);

        $is_giv = $userInfo['is_giv'];
        if ($is_giv != 1) throw new ValidateException('转增暂未开启!');

        $change = $data['num'] * ($rate / 100);
        if ($userInfo['is_top']) {
            $change = 0;
        }


        $giv_pwd = web_config($companyId, 'program.giv_pwd');
        if ($giv_pwd != 2) {
            if (!$data['pay_password']) throw new ValidateException('请输入转赠密码!');
            /** @var UsersRepository $usersRepository */
            $usersRepository = app()->make(UsersRepository::class);
            $verfiy = $usersRepository->passwordVerify($data['pay_password'], $userInfo['pay_password']);
            if (!$verfiy) throw new ValidateException('交易密码错误!');
        }


        if (($data['num'] + $change) > $userInfo['food']) throw new ValidateException('余额不足!');


        $givUser = $this->search([], $companyId)->where(['user_code' => $data['user_code']])->find();
        if (!$givUser) throw new ValidateException('转赠对象不存在!');
        if ($givUser['id'] == $userInfo['id']) throw new ValidateException('不能给自己转赠!');
        return Db::transaction(function () use ($data, $userInfo, $companyId, $givUser, $change) {
            $this->batchFoodChange($userInfo['id'], 5, '-' . $data['num'], [
                'remark' => '转赠'
            ], 4);
            #单独设置
            $type = 5;
            if ($companyId == 74) {
                $type = 6;
            }
            $this->batchFoodChange($userInfo['id'], $type, '-' . $change, [
                'remark' => '转赠手续费'
            ], 4);
            $order_no = SnowFlake::createOnlyId();
            #加入手续费
            $arr = [
                ['company_id' => $companyId, 'uuid' => $userInfo['id'], 'num' => $data['num'], 'create_at' => date('Y-m-d H:i:s'), 'get_uuid' => $givUser['id'], 'type' => 1, 'order_sn' => $order_no, 'change_num' => $change],
                ['company_id' => $companyId, 'uuid' => $givUser['id'], 'num' => $data['num'], 'create_at' => date('Y-m-d H:i:s'), 'get_uuid' => $userInfo['id'], 'type' => 2, 'order_sn' => $order_no, 'change_num' => $change],
            ];
            /** @var MineGivLogRepository $mineGivLogRepository */
            $mineGivLogRepository = app()->make(MineGivLogRepository::class);
            $mineGivLogRepository->insertAll($arr);
            $this->batchFoodChange($givUser['id'], 4, $data['num'], ['remark' => '用户' . substr_replace($userInfo['mobile'], '****', 3, 4) . '转赠']);
            //如果是代理
            if ($givUser['is_top']) {
                $topRate =  web_config($companyId, 'program.mine.tokens.top_rate');
                $topChange = bcmul($data['num'], ($topRate / 100) . '', 7);
                if ($topChange > 0) {
                    $this->batchFoodChange($givUser['id'], 4, $topChange, ['remark' => '用户' . substr_replace($userInfo['mobile'], '****', 3, 4) . '转赠手续费']);
                }
            }
            return true;
        });
        throw new ValidateException('网络错误，转赠失败!');
    }

    public function exchange($data, $userInfo, $companyId)
    {
        if (!$data['pay_password']) throw new ValidateException('请输入交易密码!');
        /** @var UsersRepository $usersRepository */
        $usersRepository = app()->make(UsersRepository::class);
        $verfiy = $usersRepository->passwordVerify($data['pay_password'], $userInfo['pay_password']);
        if (!$verfiy) throw new ValidateException('交易密码错误!');
        if ($data['num'] <= 0) throw new ValidateException('请输入正确的数量!');
        //最低一个起兑换
        if ($data['num'] < 1 || !(filter_var($data['num'], FILTER_VALIDATE_INT) !== false)) {
            throw new ValidateException('兑换数量必须为整数!');
        }
        switch ($data['type']) {
            case 1:  //金币兑换水晶
                $exchange = web_config($companyId, 'program.mine.tokens.exchange_food');
                if (!$exchange) throw new ValidateException('兑换比例设置错误!');
                $change = bcmul($data['num'], $exchange, 7);
                if ($userInfo['gold'] < $data['num']) throw new ValidateException('金币不足,无法兑换');
                /** @var UsersRepository $usersRepository */
                $usersRepository = app()->make(UsersRepository::class);
                $usersRepository->batchGoldChange($userInfo['id'], 3, (-1) * $data['num'], ['remark' => '兑换水晶'], 4);
                return $usersRepository->batchFoodChange($userInfo['id'], 4, $change, ['remark' => '金币兑换'], 4);
            case 2: // 金币兑换银币
                $exchange = web_config($companyId, 'program.mine.tokens.exchange_score');
                $change = bcmul($data['num'], $exchange, 7);
                if ($userInfo['gold'] < $data['num']) throw new ValidateException('金币不足,无法兑换');
                /** @var UsersRepository $usersRepository */
                $usersRepository = app()->make(UsersRepository::class);
                $usersRepository->batchGoldChange($userInfo['id'], 3, (-1) * $data['num'], ['remark' => '兑换银币'], 4);
                return $usersRepository->batchScoreChange($userInfo['id'], 4, $change, ['remark' => '金币兑换'], 4);
            case 3: // 水晶兑换金币
                $exchange = web_config($companyId, 'program.mine.tokens.exchange_food');
                $change = bcdiv($data['num'], $exchange, 7);
                if ($userInfo['food'] < $data['num']) throw new ValidateException('水晶不足,无法兑换');
                /** @var UsersRepository $usersRepository */
                $usersRepository = app()->make(UsersRepository::class);
                $usersRepository->batchFoodChange($userInfo['id'], 3, (-1) * $data['num'], ['remark' => '兑换金币'], 4);
                return $usersRepository->batchGoldChange($userInfo['id'], 4, $change, ['remark' => '水晶兑换'], 4);
            case 4: // 银币兑换金币
                $exchange = web_config($companyId, 'program.mine.tokens.exchange_score');
                $change = bcdiv($data['num'], $exchange, 7);
                if ($userInfo['score'] < $data['num']) throw new ValidateException('银币不足,无法兑换');
                /** @var UsersRepository $usersRepository */
                $usersRepository = app()->make(UsersRepository::class);
                $usersRepository->batchScoreChange($userInfo['id'], 3, (-1) * $data['num'], ['remark' => '转换金币'], 4);
                return $usersRepository->batchGoldChange($userInfo['id'], 4, $change, ['remark' => '银币兑换'], 4);
        }
    }

    public function watchAdver($user, $companyId)
    {
        $configTime = web_config($companyId, 'program.mine.adver_time');
        $award = web_config($companyId, 'program.mine.adver_award');
        $todayWatchCount = Db::name('users_adver')
            ->where(['company_id' => $companyId, 'uuid' => $user['id']])
            ->whereTime('add_time', 'today')->count();
        if ($todayWatchCount >= $configTime) throw new ValidateException('今日已观看' . $configTime . '个广告,请勿重复观看');
        Db::name('users_adver')->insert([
            'company_id' => $companyId,
            'uuid' => $user['id'],
            'add_time' => date('Y-m-d H:i:s'),
        ]);
        if ($todayWatchCount + 1 >= $configTime) {
            $this->batchGoldChange($user['id'], 4, $award, ['remark' => '每日广告奖励'], 4);
        }
        return ['now' => $todayWatchCount + 1, 'total' => $configTime, 'value' => $award];
    }

    public function givTokens($data, $userInfo, $companyId)
    {
        $rate = web_config($companyId, 'program.mine.tokens.rate');
        if (!$rate) throw new ValidateException('请先设置手续费比例');
        $rate = $rate / 100;
        if ($companyId == 57) {
            //如果有有效的矿工卡，则减免手续费
            $mineDispatchCount = app()->make(MineUserRepository::class)->search(['uuid' => $userInfo['id']], $userInfo['company_id'])
                ->where('dispatch_count', '>', 0)->count();
            if ($mineDispatchCount > 0 && ($rate - 0.1) > 0) {
                $rate = $rate - 0.1;
            }
        }

        $res1 = Cache::store('redis')->incr('giv_' . $userInfo['id']);
        if ($res1 > 1) {
            throw new ValidateException('操作太快,请稍后再试');
        }
        Cache::store('redis')->expire('giv_' . $userInfo['id'], 3);

        $is_giv = $userInfo['is_giv'];
        if ($is_giv != 1) throw new ValidateException('转增暂未开启!');

        //转赠数量必须为5的倍数
        if ($data['num'] % 5 != 0) {
            throw new ValidateException('转赠数量必须为5的倍数!');
        }
        $change = $data['num'] * $rate;


        $giv_pwd = web_config($companyId, 'program.giv_pwd');
        if ($giv_pwd != 2) {
            if (!$data['pay_password']) throw new ValidateException('请输入转赠密码!');
            /** @var UsersRepository $usersRepository */
            $usersRepository = app()->make(UsersRepository::class);
            $verfiy = $usersRepository->passwordVerify($data['pay_password'], $userInfo['pay_password']);
            if (!$verfiy) throw new ValidateException('交易密码错误!');
        }

        $givUser = $this->search([], $companyId)->where(['user_code' => $data['user_code']])->find();
        if ($givUser['id'] == $userInfo['id']) throw new ValidateException('不可给自己转赠!');
        if (!$givUser) throw new ValidateException('转赠对象不存在!');
        /** @var UsersTopRepository $usersTopRepository */
        $usersTopRepository = app()->make(UsersTopRepository::class);
        $isElder = $usersTopRepository->search(['uuid' => $userInfo['id']], $companyId)->find();

        $isGetElder = $usersTopRepository->search(['uuid' => $givUser['id']], $companyId)->find();
        if ($userInfo['is_top'] || $isElder) {
            if (($data['num']) > $userInfo['food']) throw new ValidateException('余额不足!');
        } else {
            if (($data['num'] + $change) > $userInfo['food']) throw new ValidateException('余额不足!');
        }

        return Db::transaction(function () use ($data, $userInfo, $companyId, $givUser, $change, $isElder, $isGetElder) {
            $top = web_config($companyId, 'program.top', '');
            if (!$top) throw new ValidateException('参数设置未完成!');
            //********************转出方处理*****************************//
            $out = 0;
            $order_no = SnowFlake::createOnlyId();
            /** @var UsersFoodLogRepository $balanceLogRepository */
            $balanceLogRepository = app()->make(UsersFoodLogRepository::class);
            if (($userInfo['is_top'] == 1 || $isElder)) {  //顶商


                $rt = $this->dao->search([], $companyId)->where('id', $userInfo['id'])->field('id,food')->lock(true)->find();
                if ($rt) {
                    $this->dao->search([], $companyId)->where('id', $rt['id'])->dec('food', $data['num'])->update();
                    $balanceLogRepository->addLog($rt['id'], $data['num'], 3, [
                        'before_change' => $rt['food'],
                        'after_change' => $rt['food'] - $data['num'],
                        'track_port' => 4,
                        'remark' => '转赠',
                        'company_id' => $companyId
                    ]);
                }


                $out = 1;
                $arr[0] = [
                    'company_id' => $companyId,
                    'uuid' => $userInfo['id'],
                    'num' => $data['num'],
                    'create_at' => date('Y-m-d H:i:s'),
                    'get_uuid' => $givUser['id'],
                    'type' => 1,
                    'order_sn' => $order_no
                ];
            } else {  //普通用户

                if (($data['num'] + $change) > $userInfo['food']) throw new ValidateException('用户余额不足!');


                $rs = $this->dao->search([], $companyId)->where('id', $userInfo['id'])->field('id,food')->lock(true)->find();
                if ($rs) {
                    $dec = $data['num'] + $change;
                    $this->dao->search([], $companyId)->where('id', $rs['id'])->where('food', '>=', $dec)->dec('food', $dec)->update();
                    /** @var UsersFoodLogRepository $balanceLogRepository */
                    $balanceLogRepository = app()->make(UsersFoodLogRepository::class);
                    $balanceLogRepository->addLog($rs['id'], $data['num'], 5, [
                        'before_change' => $rs['food'],
                        'after_change' => $rs['food'] - $dec,
                        'track_port' => 4,
                        'remark' => '转赠:' . $data['num'] . ',手续费:' . $change,
                        'company_id' => $companyId
                    ]);
                }
                $arr[0] = [
                    'company_id' => $companyId,
                    'uuid' => $userInfo['id'],
                    'num' => $data['num'] + $change,
                    'create_at' => date('Y-m-d H:i:s'),
                    'get_uuid' => $givUser['id'],
                    'type' => 1,
                    'order_sn' => $order_no
                ];
            }
            //********************转出方处理*****************************//

            //********************接手方处理*****************************//
            if ($givUser['is_top'] == 1) {   // 接收方是顶商
                if ($out == 1) { //转出也是顶商，不收任何费用

                    $rtop = $this->dao->search([], $companyId)->where('id', $givUser['id'])->field('id,food')->lock(true)->find();
                    if ($rtop) {
                        $this->dao->search([], $companyId)->where('id', $rtop['id'])->inc('food', $data['num'])->update();

                        $balanceLogRepository->addLog($rtop['id'], $data['num'], 4, [
                            'before_change' => $rtop['food'],
                            'after_change' => $rtop['food'] + $data['num'],
                            'track_port' => 4,
                            'remark' => '用户' . substr_replace($userInfo['mobile'], '****', 3, 4) . '转赠',
                            'company_id' => $companyId
                        ]);
                    }
                    $arr[1] = [
                        'company_id' => $companyId,
                        'uuid' => $givUser['id'],
                        'num' => $data['num'],
                        'create_at' => date('Y-m-d H:i:s'),
                        'get_uuid' => $userInfo['id'],
                        'type' => 2,
                        'order_sn' => $order_no
                    ];
                } else {
                    $lv = $data['num'] * web_config($companyId, 'program.top.merchant_lv');
                    if (!$lv) throw new ValidateException('顶商手续费比例未设置!');
                    $add = round($data['num'] + $lv, 7);

                    $rzl = $this->dao->search([], $companyId)->where('id', $givUser['id'])->field('id,food')->lock(true)->find();
                    if ($rzl) {
                        $this->dao->search([], $companyId)->where('id', $rzl['id'])->inc('food', $add)->update();
                        $balanceLogRepository->addLog($rzl['id'], $add, 4, [
                            'before_change' => $rzl['food'],
                            'after_change' => $rzl['food'] + $add,
                            'track_port' => 4,
                            'remark' => '用户' . substr_replace($userInfo['mobile'], '****', 3, 4) . '转赠',
                            'company_id' => $companyId
                        ]);
                    }


                    $arr[1] = [
                        'company_id' => $companyId,
                        'uuid' => $givUser['id'],
                        'num' => $add,
                        'create_at' => date('Y-m-d H:i:s'),
                        'get_uuid' => $userInfo['id'],
                        'type' => 2,
                        'order_sn' => $order_no
                    ];
                }
            } elseif ($isGetElder) {   // 接收方是长老


                if ($out == 1) { //转出也是顶商，不收任何费用
                    $rZzl = $this->dao->search([], $companyId)->where('id', $givUser['id'])->field('id,food')->lock(true)->find();
                    if ($rZzl) {
                        $this->dao->search([], $companyId)->where('id', $rZzl['id'])->inc('food', $data['num'])->update();
                        $balanceLogRepository->addLog($rZzl['id'], $data['num'], 4, [
                            'before_change' => $rZzl['food'],
                            'after_change' => $rZzl['food'] + $data['num'],
                            'track_port' => 4,
                            'remark' => '用户' . substr_replace($userInfo['mobile'], '****', 3, 4) . '转赠',
                            'company_id' => $companyId
                        ]);
                    }

                    $arr[1] = [
                        'company_id' => $companyId,
                        'uuid' => $givUser['id'],
                        'num' => $data['num'],
                        'create_at' => date('Y-m-d H:i:s'),
                        'get_uuid' => $userInfo['id'],
                        'type' => 2,
                        'order_sn' => $order_no
                    ];
                } else {
                    if (!$top['merchant_lv']) throw new ValidateException('顶商手续费比例未设置!');
                    if (!$top['elder_lv']) throw new ValidateException('长老手续费比例未设置!');
                    $lv = $data['num'] * $top['elder_lv'];
                    $add = round($data['num'] + $lv, 7);

                    $wo = $this->dao->search([], $companyId)->where('id', $givUser['id'])->field('id,food')->lock(true)->find();
                    if ($wo) {
                        $this->dao->search([], $companyId)->where('id', $wo['id'])->inc('food', $add)->update();
                        $balanceLogRepository->addLog($wo['id'], $add, 4, [
                            'before_change' => $wo['food'],
                            'after_change' => $wo['food'] + $add,
                            'track_port' => 4,
                            'remark' => '用户' . substr_replace($userInfo['mobile'], '****', 3, 4) . '转赠',
                            'company_id' => $companyId
                        ]);
                    }
                    $parent_lv = round($data['num'] * ($top['merchant_lv'] - $top['elder_lv']), 7);
                    $parent = $this->dao->search([], $companyId)->where('id', $isGetElder['top_id'])->field('id,food')->lock(true)->find();
                    if ($parent && $parent_lv > 0) {
                        $this->dao->search([], $companyId)->where('id', $parent['id'])->inc('food', $parent_lv)->update();
                        $balanceLogRepository->addLog($parent['id'], $parent_lv, 4, [
                            'before_change' => $parent['food'],
                            'after_change' => $parent['food'] + $parent_lv,
                            'track_port' => 4,
                            'remark' => '用户' . substr_replace($userInfo['mobile'], '****', 3, 4) . '转赠',
                            'company_id' => $companyId
                        ]);
                    }


                    $arr[1] = [
                        'company_id' => $companyId,
                        'uuid' => $givUser['id'],
                        'num' => $add,
                        'create_at' => date('Y-m-d H:i:s'),
                        'get_uuid' => $userInfo['id'],
                        'type' => 2,
                        'order_sn' => $order_no
                    ];
                    $arr[2] = [
                        'company_id' => $companyId,
                        'uuid' => $isGetElder['top_id'],
                        'num' => round($data['num'] * ($top['merchant_lv'] - $top['elder_lv'])),
                        'create_at' => date('Y-m-d H:i:s'),
                        'get_uuid' => $userInfo['id'],
                        'type' => 2,
                        'order_sn' => $order_no
                    ];
                }
            } else {   // 接收方是普通用户


                $pu = $this->dao->search([], $companyId)->where('id', $givUser['id'])->field('id,food')->lock(true)->find();
                if ($pu) {
                    $this->dao->search([], $companyId)->where('id', $pu['id'])->inc('food', $data['num'])->update();
                    $balanceLogRepository->addLog($pu['id'], $data['num'], 4, [
                        'before_change' => $pu['food'],
                        'after_change' => $pu['food'] + $data['num'],
                        'track_port' => 4,
                        'remark' => '用户' . substr_replace($userInfo['mobile'], '****', 3, 4) . '转赠',
                        'company_id' => $companyId
                    ]);
                }


                $order_no = SnowFlake::createOnlyId();
                $arr[1] = ['company_id' => $companyId, 'uuid' => $givUser['id'], 'num' => $data['num'], 'create_at' => date('Y-m-d H:i:s'), 'get_uuid' => $userInfo['id'], 'type' => 2, 'order_sn' => $order_no];
            }
            //********************接手方处理*****************************//
            /** @var MineGivLogRepository $mineGivLogRepository */
            $mineGivLogRepository = app()->make(MineGivLogRepository::class);
            $mineGivLogRepository->insertAll($arr);
            return true;
        });
        throw new ValidateException('网络错误，转赠失败!');
    }


    /**
     * 删除
     * @param array $ids 用户ID
     */
    public function delUser($ids)
    {
        /** @var UsersPushRepository $usersPushRepository */
        $usersPushRepository = app()->make(UsersPushRepository::class);

        $parent = [];
        foreach ($ids as $k => $v) {
            $parent[] = $usersPushRepository->getUserId($v);
        }

        foreach ($parent as $k => $v) {
            if (!empty($v)) {
                throw new ValidateException('请先处理下级推荐人');
            }
        }

        foreach ($ids as $k => $v) {
            $usersPushRepository->whereDelete(['user_id' => $v]);

            $res = $this->dao->delete($v);
        }
        return $res;
    }

    /******************大逃杀**************************/
    public function getGameDetail($userInfo)
    {
        $data['userId'] = $userInfo['id'];
        $data['avatar'] = $userInfo['avatar'];
        $data['nickName'] = $userInfo['nickname'];
        $data['status'] = $userInfo['status'];;
        $data['isIdentity'] = $userInfo['cert_id'] > 0 ? 1 : 0;
        $data['isBlack'] = 0;
        $data['isCancellation'] = 1;
        return $data;
    }

    public function getGameBalance($param)
    {
        $userInfo = app()->make(UsersRepository::class)->search([])->where(['id' => $param['uuid']])->find();
        if (!$userInfo) throw new ValidateException('账号不存在');
        $data['id'] = $userInfo['id'];
        $data['userId'] = $userInfo['id'];
        $data['balanceDiamond'] = $userInfo['food'];
        $data['nickname'] = $userInfo['nickname'];
        return $data;
    }

    public function decbalance($data)
    {
        return Db::transaction(function () use ($data) {
            $userInfo = app()->make(UsersRepository::class)->search([])->where(['id' => $data['userId']])->lock(true)->find();
            if ($userInfo['food'] < $data['changePoints']) throw new ValidateException('账户余额不足');
            $affectedRows = app()->make(UsersRepository::class)->search([])->where(['id' => $data['userId']])
                ->where('food', '>=', $data['changePoints'])
                ->update(['food' => $userInfo['food'] - $data['changePoints']]);
            if (!$affectedRows) {
                throw new ValidateException('账户余额不足');
            }
            /** @var UsersFoodLogRepository $balanceLogRepository */
            $balanceLogRepository = app()->make(UsersFoodLogRepository::class);
            $balanceLogRepository->addLog($userInfo['id'], $data['changePoints'], 3, array_merge($data, [
                'before_change' => $userInfo['food'],
                'after_change' => $userInfo['food'] - $data['changePoints'],
                'track_port' => 1,
                'remark' => '大逃杀投入',
                'company_id' => $userInfo['company_id']
            ]));
            $log['uuid'] = $userInfo['id'];
            $log['requestNo'] = $data['requestNo'];
            $log['coinType'] = $data['coinType'];
            $log['gameName'] = $data['gameName'];
            $log['changePoints'] = $data['changePoints'];
            $log['gameDate'] = $data['gameDate'];
            $log['batch_no'] = $data['batch_no'];
            $log['type'] = 1;
            app()->make(KillRepository::class)->addInfo($userInfo['company_id'], $log);
            return [];
        });
        throw new ValidateException('投入失败');
    }

    public function incbalance($data)
    {
        return Db::transaction(function () use ($data) {
            /** @var KillRepository $KillRepository */
            $KillRepository = app()->make(KillRepository::class);
            /** @var  UsersRepository $usersRepository */
            $usersRepository = app()->make(UsersRepository::class);

            foreach ($data['inComeDetails'] as $key => $value) {
                //                   $log['requestNo'] = $data['requestNo'];
                $log['coinType'] = $data['coinType'];
                $log['gameName'] = $data['gameName'];
                $log['changePoints'] = $data['changePoints'];
                $log['gameDate'] = date("Y-m-d H:i:s");
                $log['detail'] = json_encode($data['inComeDetails']);
                $log['type'] = 2;
                $userInfo = $usersRepository->search([])->where(['id' => $value['userId']])->find();
                if ($userInfo) {
                    $log['uuid'] = $userInfo['id'];
                    $re = $KillRepository->addInfo($userInfo['company_id'], $log);
                    $kill = $KillRepository->search(['uuid' => $value['userId'], 'type' => 1, 'batch_no' => $data['batch_no']], $userInfo['company_id'])->find();
                    if ($kill) {
                        $this->batchFoodChange($value['userId'], 4, $value['changePoints'], ['remark' => '成功躲避杀手']);
                        $param_rate = web_config($userInfo['company_id'], 'program.mine.tokens.parm_rate');
                        if ($param_rate) {
                            $number = $KillRepository->search([])->where(['batch_no' => $data['batch_no'], 'uuid' => $value['userId'], 'type' => 1])->sum('changePoints');
                            $newNumber = $value['changePoints'] - $number;
                            if (($newNumber * $param_rate) > 0) {
                                $parent = app()->make(UsersPushRepository::class)->search(['uuid' => $value['userId'], 'levels' => 1], $userInfo['company_id'])->find();
                                if ($parent) {
                                    $this->batchFoodChange($parent['parent_id'], 4, $newNumber * $param_rate, ['remark' => '下级成功躲避杀手']);
                                }
                            }
                        }
                    }
                }
            }
        });
        throw new ValidateException('投入失败');
    }
    /******************大逃杀**************************/

    /******************赛跑**************************/
    public function getGameInfo($userInfo)
    {
        $data['userId'] = $userInfo['id'];
        $data['avatar'] = $userInfo['avatar'];
        $data['nickName'] = $userInfo['nickname'];
        $data['food'] = $userInfo['score'];
        $data['gold'] = $userInfo['food'];
        return $data;
    }

    public function decRace($data, $user)
    {
        if (!$data['gameName']) throw new ValidateException('游戏名称错误!');
        return Db::transaction(function () use ($data, $user) {
            $userInfo = app()->make(UsersRepository::class)->search([])->where(['id' => $user['id']])->lock(true)->find();
            if ($userInfo['score'] < $data['changePoints']) throw new ValidateException('账户余额不足');
            // $affectedRows = app()->make(UsersRepository::class)->search([])->where(['id'=>$user['id']])
            //     ->where('food','>=',$data['changePoints'])
            //     ->update(['food' => $userInfo['food'] - $data['changePoints']]);
            // if (!$affectedRows) {
            //     throw new ValidateException('账户余额不足');
            // }
            // /** @var UsersFoodLogRepository $balanceLogRepository */
            // $balanceLogRepository = app()->make(UsersFoodLogRepository::class);
            // $balanceLogRepository->addLog($userInfo['id'], $data['changePoints'], 3, array_merge($data, [
            //     'before_change' => $userInfo['food'],
            //     'after_change' => $userInfo['food'] - $data['changePoints'],
            //     'track_port' => 1,
            // ]));
            $remark = '大逃杀投入';
            if ($data['gameName'] == '赛跑') {
                $remark = '运动会投入';
            }

            $this->batchScoreChange($userInfo['id'], 3, -$data['changePoints'], ['remark' => $remark]);

            $log['uuid'] = $userInfo['id'];
            $log['coinType'] = $data['coinType'];
            $log['gameName'] = $data['gameName'];
            $log['changePoints'] = $data['changePoints'];
            $log['gameDate'] = date('Y-m-d H:i:s');
            $log['batch_no'] = $data['batch_no'];
            $log['type'] = 1;
            app()->make(KillRepository::class)->addInfo($userInfo['company_id'], $log);

            $userInfo = app()->make(UsersRepository::class)->search([])->where(['id' => $userInfo['id']])->field('id,nickname,food,score')->find();
            $data['userId'] = $userInfo['id'];
            $data['avatar'] = $userInfo['avatar'] ?? '';
            $data['nickName'] = $userInfo['nickname'];
            $data['food'] = $userInfo['score'];
            return $data;
        });
        throw new ValidateException('投入失败');
    }

    public function incRace($data, $userInfo)
    {
        return Db::transaction(function () use ($data, $userInfo) {
            $returnUser = [];
            /** @var KillRepository $KillRepository */
            $KillRepository = app()->make(KillRepository::class);
            /** @var  UsersRepository $usersRepository */
            $usersRepository = app()->make(UsersRepository::class);
            $arr = [];
            $remark = '大逃杀胜利';
            if ($data['gameName'] == '赛跑') {
                $remark = '运动会胜利';
            }
            foreach ($data['list'] as $key => $value) {
                $returnUser[] = $value['userId'];
                $log['coinType'] = $data['coinType'];
                $log['gameName'] = $data['gameName'];
                $log['changePoints'] = $data['changePoints'];
                $log['gameDate'] = date('Y-m-d H:i:s');
                $log['detail'] = json_encode($data['list']);
                $log['type'] = 2;
                $userInfo = $usersRepository->search([])->where(['id' => $value['userId']])->find();
                if ($userInfo) {
                    $log['uuid'] = $userInfo['id'];
                    $KillRepository->addInfo($userInfo['company_id'], $log);
                    $kill = $KillRepository->search(['uuid' => $value['userId'], 'type' => 1, 'batch_no' => $data['batch_no']], $userInfo['company_id'])->find();
                    if ($kill) {
                        $number = $value['changePoints'];
                        $this->batchScoreChange($value['userId'], 4, $number, ['remark' => $remark]);
                    }
                }
            }

            return $this->search([])->whereIn('id', $returnUser)->column('id,score food');
        });
        throw new ValidateException('盈利失败');
    }

    public function incCord($data, $userInfo)
    {
        return Db::transaction(function () use ($data, $userInfo) {
            /** @var KillRepository $KillRepository */
            $KillRepository = app()->make(KillRepository::class);
            /** @var  UsersRepository $usersRepository */
            $usersRepository = app()->make(UsersRepository::class);

            $log['coinType'] = $data['coinType'];
            $log['gameName'] = $data['gameName'];
            $log['changePoints'] = $data['changePoints'];
            $log['gameDate'] = date('Y-m-d H:i:s');
            $log['batch_no'] = $data['batch_no'];
            $log['type'] = 2;
            $userInfo = $usersRepository->search([])->where(['id' => $userInfo['id']])->find();
            if ($userInfo) {
                $log['uuid'] = $userInfo['id'];
                $KillRepository->addInfo($userInfo['company_id'], $log);
                $kill = $KillRepository->search(['uuid' => $userInfo['id'], 'type' => 1, 'batch_no' => $data['batch_no']], $userInfo['company_id'])->find();
                if ($kill) {
                    $number = $data['changePoints'];
                    $this->batchFoodChange($userInfo['id'], 4, $number, ['remark' => '成功套中']);
                }
            }
        });
        throw new ValidateException('盈利失败');
    }

    public function circulate($winUser, $game_no)
    {
        $KillRepository = app()->make(KillRepository::class);
        $agentRepository = app()->make(AgentRepository::class);
        $usersPushRepository = app()->make(UsersPushRepository::class);
        $list = $KillRepository->search(['type' => 1, 'batch_no' => $game_no])->whereNotIn('uuid', $winUser)->group('uuid')->select();
        foreach ($list as $value) {
            $agent = $agentRepository->search(['uuid' => $value['uuid']])->find();
            $number = $KillRepository->search([])->where(['batch_no' => $value['batch_no'], 'uuid' => $value['uuid'], 'type' => 1])->sum('changePoints');
            $child1 = 0;
            $child2 = 0;
            $child3 = 0;
            $parent = null;
            if ($agent) {
                $userId = $value['uuid'];
                $child1 = $agent['lv'];
                $parent = $usersPushRepository->search(['user_id' => $value['uuid']])->where('levels', 1)->find();
            } else {
                $user = $usersPushRepository->search(['user_id' => $value['uuid']])->where('levels', 1)->find();
                if ($user) {
                    $userId = $user['parent_id'];
                    $user_agent = $agentRepository->search(['uuid' => $user['parent_id']])->find();
                    if ($user_agent) {
                        $child1 = $user_agent['lv'];
                    }
                    $parent = $usersPushRepository->search(['user_id' => $user['parent_id']])->where('levels', 1)->find();
                }
            }

            if ($parent) {
                $parent_agent = $agentRepository->search(['uuid' => $parent['parent_id']])->find();
                if ($parent_agent) {
                    $child2 = $parent_agent['lv'] - $child1;
                }
                $higher = $usersPushRepository->search(['user_id' => $parent['parent_id']])->where('levels', 1)->find();
                if ($higher) {
                    $higher_agent = $agentRepository->search(['uuid' => $higher['parent_id']])->find();
                    if ($higher_agent) {
                        $child3 = $higher_agent['lv'] - $parent_agent['lv'];
                    }
                }
            }

            $agent_rebate = web_config($value['company_id'], 'program.agent_rebate');
            $ken = round($number * $agent_rebate, 7);
            if ($ken > 0) {
                $child1_num = $child1 > 0 ? round($ken * $child1, 7) : 0;
                $child2_num = $child2 > 0 ? round($ken * $child2, 7) : 0;
                $child3_num = $child3 > 0 ? round($ken * $child3, 7) : 0;
                if ($child1_num > 0) {
                    (new KillRebateModel())->insert([
                        'company_id' => $value['company_id'],
                        'uuid' => $userId,
                        'batch_no' => $value['batch_no'],
                        'price' => $ken,
                        'rebate' => $child1_num,
                        'type' => 21,
                        'add_time' => date('Y-m-d H:i:s')
                    ]);
                }
                if ($child2_num > 0) {
                    (new KillRebateModel())->insert([
                        'company_id' => $value['company_id'],
                        'uuid' => $parent['parent_id'],
                        'batch_no' => $value['batch_no'],
                        'price' => $ken,
                        'rebate' => $child2_num,
                        'type' => 22,
                        'add_time' => date('Y-m-d H:i:s')
                    ]);
                }
                if ($child3_num > 0) {
                    (new KillRebateModel())->insert([
                        'company_id' => $value['company_id'],
                        'uuid' => $higher['parent_id'],
                        'batch_no' => $value['batch_no'],
                        'price' => $ken,
                        'rebate' => $child3_num,
                        'type' => 23,
                        'add_time' => date('Y-m-d H:i:s')
                    ]);
                }
            }


            $to_rebate = web_config($value['company_id'], 'program.to_rebate');
            if ($to_rebate && $to_rebate > 0) {
                $ken = round($number * $to_rebate, 7);
                (new KillRebateModel())->insert([
                    'company_id' => $value['company_id'],
                    'uuid' => $value['uuid'],
                    'batch_no' => $value['batch_no'],
                    'price' => $number,
                    'rebate' => $ken,
                    'type' => 1,
                    'add_time' => date('Y-m-d H:i:s')
                ]);
            }
        }
    }

    /******************赛跑**************************/


    public function getFood($user, $companyId)
    {

        // if ($companyId == 49 && $user['is_run'] == 1) {
        /** @var MineUserRepository $mineUserRepository */
        $mineUserRepository = app()->make(MineUserRepository::class);
        $mineUserRepository->getMyWite($user, $user['company_id']);
        // }
        return $this->dao->search([])->where(['id' => $user['id']])->value('gold');
    }

    public function bindUser($data, $userInfo, $companyId)
    {
        if (isset($data['user_code']) && $data['user_code']) {
            $parent = $this->dao->search(['user_code' => $data['user_code']], $companyId)->find();
            if (!$parent) throw new ValidateException('上级用户存在!');
            /**
             * @var UsersPushRepository $usersPushRepository
             */
            $usersPushRepository = app()->make(UsersPushRepository::class);
            return $usersPushRepository->batchTjr($userInfo, $parent['id'], $companyId);
        }
    }

    public function setRebate($userInfo)
    {
        $num = (new KillRebateModel())->where('status', 0)->where('uuid', $userInfo['id'])->sum('rebate');
        if ($num <= 0) throw new ValidateException('暂无可领取');

        $uuid = $userInfo['id'];
        $res2 = Cache::store('redis')->setnx('set_rebate' . $uuid, $uuid);
        Cache::store('redis')->expire('set_rebate' . $uuid, 1);
        if (!$res2) throw new ValidateException('操作频繁');

        return Db::transaction(function () use ($uuid) {
            $list = (new KillRebateModel())->where('status', 0)->where('uuid', $uuid)->select();
            $num = 0;
            foreach ($list as $value) {
                $re = (new KillRebateModel())->where(['id' => $value['id'], 'uuid' => $uuid])->update(['status' => 1]);
                if ($re) $num += $value['rebate'];
            }
            $this->batchFoodChange($uuid, 4, $num, ['remark' => '领取返利']);
            return true;
        });
    }


    public function run($uuid, $companyId)
    {
        $info = $this->get($uuid);
        /** @var UsersFoodLogRepository $usersFoodLogRepository */
        $usersFoodLogRepository = app()->make(UsersFoodLogRepository::class);
        $count = $usersFoodLogRepository->search(['user_id' => $uuid, 'remark' => '挖矿收入'])->whereTime('add_time', 'today')->count('id');
        if ($count > 0) throw new ValidateException('禁止重复开启!');
        return $this->editInfo($info, ['is_run' => 1]);
    }

    public function getUserVipLevel($level, $id, $companyId)
    {
        //10个开了1矿的下级
        $userIds = app()->make(UsersPushRepository::class)->search([], $companyId)->where(['parent_id' => $id])->column('user_id');

        if ($level < 1) {
            if ($userIds) {
                $countMine = app()->make(MineUserRepository::class)->search([], $companyId)->whereIn('uuid', $userIds)->whereIn('level', [2])->count();

                if ($countMine >= 10) {
                    return 1;
                }
            }
        } else {
            //判断下级等级数量
            if ($level == 4 && $this->dao->search([], $companyId)->whereIn('vip_level', [4])->whereIn('id', $userIds)->count() > 5) {
                return 5;
            } else if ($level == 3 && $this->dao->search([], $companyId)->whereIn('vip_level', [3])->whereIn('id', $userIds)->count() > 5) {
                return 4;
            } else if ($level == 2 && $this->dao->search([], $companyId)->whereIn('vip_level', [2])->whereIn('id', $userIds)->count() > 50) {
                return 3;
            } else if ($level == 1 && $this->dao->search([], $companyId)->whereIn('vip_level', [1])->whereIn('id', $userIds)->count() > 10) {
                return 2;
            }
        }
    }

    /******************顶商**************************/
    public function obstacles($top_name, $userInfo, $companyId)
    {
        if ($userInfo['is_top'] == 1) throw new ValidateException('禁止重复申请');
        $top = web_config($companyId, 'program.top', '');
        if (!$top) throw new ValidateException('参数配置未完善!');

        //大玩家质押逻辑
        $top['pledge'] = $top['pledge'] ?? 0;
        if ($companyId == 55) {
            if ($userInfo['food'] < bcadd($top['unset'], $top['pledge'], 2)) throw new ValidateException('余额不足');
        }

        if ($userInfo['food'] < $top['unset']) throw new ValidateException('余额不足');
        /** @var UsersTopRepository $usersTopRepository */
        $usersTopRepository = app()->make(UsersTopRepository::class);
        $is = $usersTopRepository->search(['uuid' => $userInfo['id']], $companyId)->find();
        if ($is) throw new ValidateException('请先退出联盟，才能申请成为大玩家!');
        try {
            return Db::transaction(function () use ($userInfo, $top, $companyId, $top_name) {
                $user = $this->dao->search([])->where(['id' => $userInfo['id']])->lock(true)->find();
                $re = $this->dao->search([], $companyId)->where('id', $user['id'])->where('food', '>=', $top['unset'])->dec('food', bcadd($top['unset'], $top['pledge']))->update();
                if ($re) {
                    $this->editInfo($user, ['is_top' => 1, 'top_name' => $top_name, 'top_num' => $top['unset'], 'top_pledge' => $top['pledge'], 'top_time' => date('Y-m-d H:i:s')]);
                    /** @var UsersFoodLogRepository $usersFoodLogRepository */
                    $usersFoodLogRepository = app()->make(UsersFoodLogRepository::class);
                    $data['user_id'] = $userInfo['id'];
                    $data['amount'] = bcadd($top['unset'], $top['pledge']);
                    $data['before_change'] = $userInfo['food'];
                    $data['after_change'] = $userInfo['food'] - bcadd($top['unset'], $top['pledge']);
                    $data['log_type'] = 3;
                    $data['remark'] = '开通顶商';
                    $data['track_port'] = 4;
                    return $usersFoodLogRepository->addInfo($companyId, $data);
                }
            });
        } catch (\Exception $exception) {
            throw new ValidateException($exception->getMessage());
        }
    }

    public function getTopList($page, $limit, int $companyId = null)
    {
        $where['is_top'] = 1;
        $query = $this->dao->search($where, $companyId)->field('id,head_file_id,nickname,top_name');
        $count = $query->count();
        $list = $query->with(['avatars'])->page($page, $limit)
            ->order('top_time', 'asc')
            ->select();
        return compact('list', 'count');
    }
    /******************顶商**************************/
}
