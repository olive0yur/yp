<?php

namespace app\common\repositories\users;

use app\common\dao\users\UsersMarkDao;
use app\common\repositories\agent\AgentRepository;
use app\common\repositories\givLog\GivLogRepository;
use app\common\repositories\mine\MineRepository;
use app\common\repositories\mine\MineUserRepository;
use app\common\repositories\pool\PoolOrderNoRepository;
use app\common\repositories\pool\PoolSaleRepository;
use app\common\repositories\pool\PoolTransferLogRepository;
use app\common\services\SmsService;
use app\helper\SnowFlake;
use think\exception\ValidateException;
use think\facade\Cache;
use think\facade\Db;
use app\common\dao\users\UsersPoolDao;
use app\common\repositories\BaseRepository;
use think\facade\Log;

/**
 * Class UsersPoolRepository
 *
 * @mixin UsersPoolDao
 */
class UsersPoolRepository extends BaseRepository
{

    public $usersMarkRepository;

    public function __construct(UsersPoolDao $dao)
    {
        $this->dao = $dao;
        /** @var $UsersMarkRepository usersMarkRepository */
        $this->usersMarkRepository = app()->make(UsersMarkDao::class);
    }

    public function getList(array $where, $page, $limit, int $companyId = null)
    {
        if ((int)$where['status'] <= 0) {
            $query = $this->dao->search($where, $companyId)->whereIn('status', [1, 2]);
        } else {
            $query = $this->dao->search($where, $companyId);
        }
        $count = $query->count();
        $list = $query->page($page, $limit)
            ->with(['user' => function ($query) {
                $query->field('id,mobile,nickname');
                $query->bind(['mobile', 'nickname']);
            }, 'pool' => function ($query) {
                $query->field('id,file_id,title')->with(['cover' => function ($query) {
                    $query->bind(['picture' => 'show_src']);
                }]);
            }, 'onInfo' => function ($query) {
                $query->field('id,no,pool_id,status');
                $query->bind(['no_status' => 'status']);
            }
            ])
            ->order('id', 'desc')
            ->select();
        return compact('list', 'count');
    }

    public function updateAll($ids, $companyId = null)
    {
        $re = $this->dao->updates($ids, ['status' => 5]);
        return $re;
    }

    public function send(array $data, $user, int $companyId = null)
    {
        $res1 = Cache::store('redis')->setnx('giv_pool_' . $user['id'], $user['id']);
        Cache::store('redis')->expire('giv_pool_' . $user['id'], 1);
        if (!$res1) throw new ValidateException('禁止同时转增!!');
        $uuid = $user['id'];
        if($companyId != 35){
            if ($user['cert_id'] <= 0) throw new ValidateException('请先实名认证!');
        }
        $agent = app()->make(AgentRepository::class)->search(['uuid'=>$user['id']])->find();
        if($companyId != 19){
            if(!$agent) throw new ValidateException('只有代理可以转赠！');
        }


        /** @var UsersRepository $usersRepository */
        $usersRepository = app()->make(UsersRepository::class);
        $getUser = $usersRepository->search(['user_code' => $data['user_code']], $companyId)->field('id,mobile,cert_id')->find();
        if (!$getUser) throw new ValidateException('接收方账号不存在！');
        if($companyId != 35){
            if ($getUser['cert_id'] == 0) throw new ValidateException('接收方未实名认证!');
        }

        if($companyId == 35){
            if($data['num'] != 5) throw new ValidateException('单次转赠数量为5张');
            /** @var MineUserRepository $mineUserRepository */
            $mineUserRepository = app()->make(MineUserRepository::class);
            $mine = $mineUserRepository->search(['uuid'=>$getUser['id'],'level'=>1],$companyId)->find();
            if($mine['ing'] == 1) throw new ValidateException('挖矿进行中!');
            $getCount = $this->dao->search(['uuid'=>$getUser['id'],'status'=>1])->where(['is_dis'=>1])->count('id');
            if($getCount >= 5) throw new ValidateException('当前已有矿工!');
        }


        $user = $usersRepository->search([], $companyId)->where(['id' => $uuid])->field('id,pay_password,cert_id')->find();
        if (!$agent){
            if ($getUser['id'] == $uuid) throw new ValidateException('禁止转给自己!');
        }

        /** @var UsersPoolRepository $usersPoolRepository */
        $usersPoolRepository = app()->make(UsersPoolRepository::class);
        // $count = $usersPoolRepository->search(['uuid' => $getUser['id'], 'status' => 1], $companyId)->count('id');
        // if ($count + $data['num'] >= web_config($companyId, 'program.output.maxNum')) throw new ValidateException('接收方最多可转赠' . web_config($companyId, 'program.output.maxNum') . '张!');

        $giv_pwd = web_config($companyId,'program.giv_pwd');
        if($giv_pwd != 2){
            if(!$data['pay_password']) throw new ValidateException('请输入交易密码!');
            /** @var UsersRepository $usersRepository */
            $usersRepository = app()->make(UsersRepository::class);
            $verfiy = $usersRepository->passwordVerify($data['pay_password'], $user['pay_password']);
            if (!$verfiy) throw new ValidateException('交易密码错误!');
        }

        $list = $this->dao->search(['pool_id' => $data['pool_id'], 'uuid' => $user['id'], 'status' => 1, 'is_dis' => 2], $companyId)
            ->limit($data['num'])
            ->select();
        if (count($list) < $data['num']) throw new ValidateException('会员卡牌不足!');

        //转增前查询 end
        $order_no = SnowFlake::createOnlyId();

        return Db::transaction(function () use ($uuid, $getUser, $usersPoolRepository, $list, $companyId, $order_no, $data) {
            $is_dis = 1;
            $getAgent = app()->make(AgentRepository::class)->search(['uuid' => $getUser['id']])->find();
            if ($getAgent && $getUser['id'] != $uuid) $is_dis = 2;
            $add[] = [];
            foreach ($list as $key => $v) {
                $add[$key]['company_id'] = $companyId;
                $add[$key]['uuid'] = $getUser['id'];
                $add[$key]['pool_id'] = $v['pool_id'];
                $add[$key]['no'] = $v['no'];
                $add[$key]['add_time'] = date('Y-m-d H:i:s');
                $add[$key]['status'] = 1;
                $add[$key]['type'] = 1;
                $add[$key]['order_id'] = $v['order_id'];
                $add[$key]['price'] = $v['price'];
                $add[$key]['is_dis'] = $is_dis;
                // $add[$key]['start_at'] = date('Y-m-d H:i:s');
                $add[$key]['giv_id'] = $v['id'];
                $usersPoolRepository->editInfo($v, ['status' => 11]);
            }
            $usersPoolRepository->addAll($add);

            /** @var GivLogRepository $givLogRepository */
            $givLogRepository = app()->make(GivLogRepository::class);
            $giv['uuid'] = $uuid;
            $giv['to_uuid'] = $getUser['id'];
            $giv['goods_id'] = $data['pool_id'];
            $giv['buy_type'] = 1;
            $giv['order_no'] = $order_no;
            if($companyId == 74){
                $giv['dragon_num'] = $data['num'];
            }
            $givLogRepository->addInfo($companyId, $giv);


            return ['order_no' => $order_no];
        });
    }


    public  function httpsPost($url, $data)
    {
        $header = ['Content-Type:application/json'];
        $curl = curl_init(); //初始化curl
        curl_setopt($curl, CURLOPT_URL, $url); //抓取指定网页
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        curl_setopt($curl, CURLOPT_HEADER, 0); //设置header
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); //要求结果为字符串且输出到屏幕上
        curl_setopt($curl, CURLOPT_POST, 1); //post提交方式?
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        $data = curl_exec($curl);
        curl_close($curl);
        return json_decode($data, true);
    }

    public function getDetail(int $id, $companyId = null)
    {
        $with = [
            'pool'
        ];
        $data = $this->dao->search([], $companyId)
            ->with($with)
            ->where('id', $id)
            ->find();
        return $data;
    }

    public function addInfo(int $companyId = null, array $data = [])
    {

        return Db::transaction(function () use ($data, $companyId) {
            $data['company_id'] = $companyId;
            return $this->dao->create($data);
        });
    }

    public function addAll($add)
    {
        return $this->dao->insertAll($add);
    }

    public function editInfo($info, $data)
    {
        return $this->dao->update($info['id'], $data);
    }

    public function getApiMyList(array $where, $page, $limit, int $companyId = null, $userInfo)
    {
        $where['uuid'] = $userInfo['id'];
        $query = $this->dao->search($where, $companyId)->whereIn('status', [1, 2])->field('*')->group('pool_id');
        $count = $query->count();
        $list = $query->page($page, $limit)
            ->with(['pool' => function ($query) {
                $query->field('id,title,file_id,num,price')->with(['cover' => function ($query) {
                    $query->field('id,show_src,width,height');
                }]);
            }])
            ->withAttr('is_dis_count',function ($v,$data) use($where,$companyId){
                return $this->dao->search($where, $companyId)->whereIn('status', [1, 2])
                        ->where(['is_dis'=>1,'pool_id'=>$data['pool_id']])->count('id');
            })
            ->append(['is_dis_count'])
            ->order('id', 'desc')
            ->select();
        $num = $this->dao->search(['uuid'=>$userInfo['id']],$companyId)->whereIn('status',[1,2])->count('id');

        $dragon = [];
        $data = web_config($companyId,'program.colors','');
        if($data){
            $dragon = [
                'name' => $data['name'],
                'img' => $data['img'],
                'num' => $userInfo['dragon'],
            ];
        }
        return compact('list', 'count','num','dragon');
    }

    public function getApiMySingleList(array $where, $page, $limit, int $companyId = null, $userInfo)
    {
        $where['uuid'] = $userInfo['id'];
        $query = $this->dao->search($where, $companyId)->whereIn('status', [1, 2])->field('*');
        $count = $query->count();
        $list = $query->page($page, $limit)
            ->with(['pool' => function ($query) {
                $query->field('id,title,file_id,num,price,ageing')->with(['cover' => function ($query) {
                    $query->field('id,show_src,width,height');
                }]);
            }])
            ->order('id', 'desc')
            ->select();
        $num = $this->dao->search(['uuid'=>$userInfo['id']],$companyId)->whereIn('status',[1,2])->count('id');

        $dragon = [];
        $data = web_config($companyId,'program.colors','');
        if($data){
            $dragon = [
                'name' => $data['name'],
                'img' => $data['img'],
                'num' => $userInfo['dragon'],
            ];
        }
        foreach ($list as $key => $item) {
            if ($item['pool']['ageing']) {
                $list[$key]['end_time'] = calculateTimeDifference(strtotime('+'.$item['pool']['ageing'].' day',strtotime($item['add_time'])),time()) . '天';
            } else {
                $list[$key]['end_time'] = '永久';
            }
        }
        return compact('list', 'count','num','dragon');
    }

    ## 我的卡牌

    public function getCount($where, int $companyId = null)
    {
        return $this->dao->search($where, $companyId)->count('id');
    }

    ## 我的空投卡牌

    public function getApiMyAirdropList(array $where, $page, $limit, int $companyId = null, int $uuid)
    {
        $where['uuid'] = $uuid;
//        $query = $this->dao->search($where,$companyId)->field('*')->group('pool_id');
        $query = $this->dao->search($where, $companyId);
        $count = $query->count();
        $list = $query->page($page, $limit)
            ->with(['pool' => function ($query) {
                $query->field('id,title,file_id,author_id,num')->with(['cover' => function ($query) {
                    $query->field('id,show_src,width,height');
                }, 'author' => function ($query) {
                    $query->field('id,nickname,head_file_id')->with(['avatars' => function ($query) {
                        $query->field('id,show_src,width,height');
                    }]);
                }]);
            }])->withAttr('mark_count', function ($v, $data) {
                $where['uuid'] = $data['uuid'];
                $where['pool_id'] = $data['pool_id'];
                $where['status'] = 2;
                return $this->getCount($where, $data['company_id']) ?? 0;
            })
            ->withAttr('days',function($v,$data){
                return intval((time() - strtotime($data['add_time']))/86400) ;
            })
            ->append(['mark_count','days'])
            ->order('id', 'desc')
            ->select();
        return compact('list', 'count');
    }


    public function getApiAuthorPoolList(array $where, $page, $limit, int $uuid, int $companyId = null)
    {
        $where['uuid'] = $uuid;
        $is_possess = app()->make(UsersRepository::class)->search([], $companyId)->where('id', $uuid)->value('is_possess');
        if ($is_possess == 2) {
            return [];
        }
        $query = $this->dao->search($where, $companyId)->whereIn('status', [1, 2])->field('*')->group('pool_id');
        $count = $query->count();
        $list = $query->page($page, $limit)
            ->with(['pool' => function ($query) {
                $query->field('id,title,file_id,author_id,num')->with(['cover' => function ($query) {
                    $query->field('id,show_src,width,height');
                }]);
            }])->withAttr('mark_count', function ($v, $data) {
                $where['uuid'] = $data['uuid'];
                $where['pool_id'] = $data['pool_id'];
                $where['status'] = 2;
                return $this->getCount($where, $data['company_id']) ?? 0;
            })->append(['mark_count'])
            ->order('id', 'desc')
            ->select();
        return compact('list', 'count');
    }


    public function getApiMyListInfo(array $where, $page, $limit, int $companyId = null)
    {
        $query = $this->dao->search($where, $companyId)->whereIn('status', [1, 2])->field('id,no,pool_id,status,price,company_id');
        $count = $query->count();
        $list = $query->page($page, $limit)
            ->with(['pool' => function ($query) {
                $query->field('id,title,num');
            },

            ])
            ->order('id', 'desc')
            ->select();
        return compact('list', 'count');
    }


    public function getApiMyInfo(array $where, int $companyId = null, int $uuid)
    {

        $with = [
            'PoolMode' => function ($query) {
                $query->with([
                    'img' => function ($query) {
                        $query->field('id,show_src,width,height');
                    }
                    , 'back' => function ($query) {
                        $query->field('id,show_src,width,height');
                    }
                    , 'tableImg' => function ($query) {
                        $query->field('id,show_src,width,height');

                    }
                ]);
                $query->bind(['img', 'back', 'tableImg', 'mode_type']);
            },

        ];
        $where['uuid'] = $uuid;
        $query = $this->dao->search($where, $companyId)->field('id,no,pool_id,status,price');
        $query->append(['nft_id']);
        $list = $query->with([
            'pool' => function ($query) use ($with) {
                $query->field('id,title,num,content')
                    ->with($with);
            }])
            ->find();
        $list['hash'] = app()->make(PoolOrderNoRepository::class)->search(['no' => $list['no'], 'pool_id' => $list['pool_id']], $companyId)->value('hash');
        return $list;
    }


    public function batchGiveUserPool(array $userId, array $data, int $companyId = null)
    {
        $usersRepository = app()->make(UsersRepository::class);
        $list = $usersRepository->selectWhere([
            ['id', 'in', $userId]
        ]);
        if ($list) {
            foreach ($list as $k => $v) {
                $this->giveUserPoolInfo($v, $data, $companyId);
            }
            return $list;
        }
        return [];
    }

    public function giveUserPoolInfo($userInfo, $data, $companyId = null)
    {
        $poolSaleRepository = app()->make(PoolSaleRepository::class);
        $poolInfo = $poolSaleRepository->get($data['pool_id']);
        if ($poolInfo['stock'] < $data['num']) {
            throw new ValidateException('库存不足!');
        }
        $no = app()->make(PoolOrderNoRepository::class);
        $userPool = app()->make(UsersPoolRepository::class);
        $is_agent = app()->make(AgentRepository::class)->search(['uuid'=>$userInfo['id']])->find();
        for ($i = 1; $i <= $data['num']; $i++) {
            $add = [];
            $add['uuid'] = $userInfo['id'];
            $add['add_time'] = date('Y-m-d H:i:s');
            $add['pool_id'] = $data['pool_id'];
            $add['no'] = $no->getNo($data['pool_id'],$userInfo['id']);
            $add['price'] = 0.00;
            $add['type'] = 2;
            $add['status'] = 1;
            if($companyId == 21){
                $add['is_dis'] = 2;
            }else{
                $add['is_dis'] = $is_agent ? 2 : 1;
            }
            $result = $userPool->addInfo($companyId, $add);
            $log = [];
            $log['pool_id'] = $data['pool_id'];
            $log['no'] = $add['no'];
            $log['uuid'] = $userInfo['id'];
            $log['type'] = 2;
            app()->make(PoolTransferLogRepository::class)->addInfo($companyId,$log);
        }
        $poolSaleRepository->search([], $companyId)
            ->where(['id' => $data['pool_id']])->dec('stock', $data['num'])->update();
        /** @var MineUserRepository $mineUserRepository */
        $mineUserRepository = app()->make(MineUserRepository::class);
        /** @var MineRepository $mineRepository */
        $mineRepository = app()->make(MineRepository::class);
        $mine = $mineRepository->search(['level'=>1,'status'=>1],$companyId)->find();
        if($mine) {
            //发放start
            $re = $mineUserRepository->search(['uuid' => $userInfo['id'], 'level' => 1, 'status' => 1], $companyId)->find();
            if ($re) {
                if($companyId != 21){
                    if(!$is_agent){
                        $mineUserRepository->incField($re['id'], 'dispatch_count', $data['num']);
                    }
                }
            }
            if (!$re) {
                event('user.mine', $userInfo);
                sleep(2);
                $re = $mineUserRepository->search(['uuid' => $userInfo['id'], 'level' => 1, 'status' => 1], $companyId)->find();
                if ($re) {
                    if($companyId != 21){
                        if(!$is_agent) {
                            $mineUserRepository->incField($re['id'], 'dispatch_count', $data['num']);
                        }
                    }
                }
            }
            //发放end
        }
//        $event['uuid'] = $userInfo['id'];
//        $event['num'] = $data['num'];
//        $event['type'] = 2;
//        $event['companyId'] = $companyId;
//        event('mine.dispatch',$event);
        return true;
    }



}