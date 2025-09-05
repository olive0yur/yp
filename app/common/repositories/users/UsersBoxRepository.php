<?php

namespace app\common\repositories\users;

use app\common\repositories\box\BoxSaleGoodsListRepository;
use app\common\repositories\box\BoxSaleRepository;
use app\common\repositories\givLog\GivLogRepository;
use app\common\repositories\mine\MineRepository;
use app\common\repositories\mine\MineUserRepository;
use app\common\repositories\pool\PoolFollowRepository;
use app\common\repositories\pool\PoolOrderNoRepository;
use app\common\repositories\pool\PoolSaleRepository;
use app\common\repositories\pool\PoolShopOrder;
use app\common\repositories\pool\PoolTransferLogRepository;
use app\common\repositories\users\UsersRepository;
use app\helper\SnowFlake;
use app\jobs\BoxUserOpenAll;
use think\exception\ValidateException;
use think\facade\Cache;
use think\facade\Db;
use app\common\dao\users\UsersBoxDao;
use app\common\repositories\BaseRepository;
use think\facade\Log;

class UsersBoxRepository extends BaseRepository
{

    public function __construct(UsersBoxDao $dao)
    {
        $this->dao = $dao;
    }

    public function getList(array $where, $page, $limit, int $companyId = null)
    {

        $query = $this->dao->search($where, $companyId);
        $count = $query->count();
        $list = $query->page($page, $limit)
            ->with(['box' => function ($query) {
                $query->field('id,title,file_id')->with(['cover' => function ($query) {
                    $query->bind(['picture' => 'show_src']);
                }]);
            }, 'user' => function ($query) {
                $query->field('id,mobile,nickname');
                $query->bind(['mobile', 'nickname']);
            }])
            ->append(['goods'])
            ->order('id', 'desc')
            ->select();
        return compact('list', 'count');
    }

    public function send(array $data, $user, int $companyId = null)
    {
        $res2 = Cache::store('redis')->setnx('giv_box_' . $user['id'], $user['id']);
        Cache::store('redis')->expire('giv_box_' . $user['id'], 1);
        if (!$res2) throw new ValidateException('禁止同时转增!');
        /** @var UsersRepository $usersRepository */
        $usersRepository = app()->make(UsersRepository::class);
        $uuid = $user['id'];
        $user = $usersRepository->search([], $companyId)
            ->where('id', $uuid)
            ->field('pay_password,cert_id')
            ->find();
        $verfiy = $usersRepository->passwordVerify($data['pay_password'], $user['pay_password']);
        if (!$verfiy) throw new ValidateException('交易密码错误!');

        if ($user['cert_id'] <= 0) throw new ValidateException('请先实名认证!');
        $getUser = $usersRepository->search(['mobile' => $data['phone']], $companyId)->field('id,mobile,cert_id')->find();
        if (!$getUser) throw new ValidateException('接收方账号不存在！');
        if ($getUser['cert_id'] == 0) throw new ValidateException('接收方未实名认证!');
        $userBox = $this->getDetail($data['id'], $companyId);
        if (!$userBox) throw new ValidateException('会员卡牌不存在!');
        $boxInfo = app()->make(BoxSaleRepository::class)->search([], $companyId)->where('id', $userBox['box_id'])->field('id,title,is_give')->find();
        if (!$boxInfo) {
            throw new ValidateException('未配置参数!');
        }
        if ($boxInfo['is_give'] != 1) throw new ValidateException('暂未开始转赠！');
        return Db::transaction(function () use ($data, $userBox, $getUser, $uuid, $companyId, $boxInfo) {
            $arr['uuid'] = $getUser['id'];
            $arr['box_id'] = $userBox['box_id'];
            $arr['add_time'] = date('Y-m-d H:i:s');
            $arr['status'] = 1;
            $arr['type'] = 3;
            $re = $this->addinfo($companyId, $arr);
            if ($re) {
                $givLogRepository = app()->make(GivLogRepository::class);
                $giv['uuid'] = $uuid;
                $giv['to_uuid'] = $getUser['id'];
                $giv['goods_id'] = $userBox['box_id'];
                $giv['buy_type'] = 2;
                $giv['sell_id'] = $userBox['id'];
                $givLogRepository->addInfo($companyId, $giv);
                $this->editInfo($userBox, ['status' => 4]);
            }
            api_user_log($uuid, 4, $companyId, '转赠盲盒:' . $boxInfo['title']);
            return true;
        });
    }

    public function getDetail(int $id, $companyId = null)
    {
        $with = [
            'box'
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
            if ($companyId) $data['company_id'] = $companyId;
            $userInfo = $this->dao->create($data);
            return $userInfo;
        });
    }

    public function editInfo($info, $data)
    {
        return $this->dao->update($info['id'], $data);
    }

    public function getApiMyList(array $where, $page, $limit, int $companyId = null, int $uuid)
    {
        $where['uuid'] = $uuid;
        $query = $this->dao->search($where, $companyId)->whereIn('status', [1, 2]);
        $list = $query->page($page, $limit)
            ->with(['box' => function ($query) {
                $query->field('id,title,file_id')->with(['cover' => function ($query) {
                    $query->field('id,show_src,width,height');
                }]);
            }
            ])
            ->order('id', 'desc')->group('box_id')
            ->withAttr('think_count', function ($v, $data) use ($uuid) {
                return $this->dao->search(['uuid' => $uuid, 'box_id' => $data['box_id']])->whereIn('status', [1, 2])->count('id');
            })->append(['mark_count', 'think_count'])
            ->select();
        $count = $query->count();
        return compact('list', 'count');
    }

    public function getCount($where, int $companyId = null)
    {
        return $this->dao->search($where, $companyId)->count('id');
    }

    public function getApiMyListInfo(array $where, $page, $limit, int $companyId = null, int $uuid)
    {
        $where['uuid'] = $uuid;
        $query = $this->dao->search($where, $companyId)->whereIn('status', [1, 2]);
        $count = $query->count();
        $list = $query->page($page, $limit)
            ->with(['box' => function ($query) {
                $query->field('id,title,file_id')->with(['cover' => function ($query) {
                    $query->field('id,show_src,width,height');
                }]);
            }
            ])
            ->order('id', 'desc')
            ->select();
        return compact('list', 'count');
    }


    public function getMyInfo(array $where, int $companyId = null, int $uuid)
    {
        $where['uuid'] = $uuid;
        $query = $this->dao->search($where, $companyId)
            ->with(['box' => function ($query) {
                $query->field('id,title,file_id,num,content')
                    ->with(
                        ['cover' => function ($query) {
                            $query->field('id,show_src,width,height');
                        }]
                    );
            }
            ])
            ->find();
        return $query;
    }

    public function apiBuy($data, $userInfo, int $companyId = null)
    {
        $res1 = Cache::store('redis')->setnx('develop_' . $userInfo['id'], $userInfo['id']);
        Cache::store('redis')->expire('develop_' . $userInfo['id'], 1);
        if (!$res1) throw new ValidateException('操作频繁!!');

        $boxSaleRepository = app()->make(BoxSaleRepository::class);
        $boxInfo = $boxSaleRepository->search([])->where(['id'=>$data['box_id']])->find();
        if(!$boxInfo) throw new ValidateException('网络错误!');

        if($userInfo['cert_id'] <= 0 ) throw new ValidateException('请先实名认证!');
        if ($userInfo['food'] < 0 || !$userInfo['food'] || $userInfo['food'] < ($boxInfo['price'] * $data['num'] * $data['multiple'])){
            $tokens = web_config($companyId, 'site')['tokens'];
            throw new ValidateException('您的'.$tokens.'余额不足');
        }

        $goodsRepository = app()->make(BoxSaleGoodsListRepository::class);
        $list = $goodsRepository->search(['box_id' => $data['box_id']], $companyId)->select();
        $item = [];
        foreach ($list as $key => $value){
            if ($value['num'] > 0){
                $item[$value['id']] = $value['probability'];
            }
        }
        if (!$item) throw new ValidateException('当前开盒人数过多，请稍后重试');

        $arr = [];
        $arr['uuid'] = $userInfo['id'];
        $arr['company_id'] = $companyId;
        $arr['box_id'] = $data['box_id'];
        $arr['num'] = $data['num'];
        $arr['multiple'] = $data['multiple'];
        $arr['open_no'] = SnowFlake::createOnlyId('open');
         (new \app\jobs\BoxOpenAll())->openBox($arr);
//        $rt = \think\facade\Queue::push(\app\jobs\BoxOpenAll::class, $arr, 'BoxBuyOpen');
        return ['open_no'=>$arr['open_no']];

    }

    public function open($data, $user, int $companyId = null)
    {
        if(!$data['id']) {
            $list = $this->dao->search(['uuid' => $user['id'],'status'=>1], $companyId)->select();
        }else{
            $list = $this->dao->search(['uuid' => $user['id'],'status'=>1], $companyId)->where('id',$data['id'])->select();
        }
        if(count($list) == 0) throw new ValidateException('您暂无可激活礼包');
        foreach ($list as $value){
            $arr = [];
            $arr['uuid'] = $user['id'];
            $arr['company_id'] = $companyId;
            $arr['box_id'] = $value['box_id'];
            $arr['num'] = 1;
            $arr['open_no'] = SnowFlake::createOnlyId('open');
//            (new \app\jobs\BoxUserOpenAll())->openBox($arr);
            $rt = \think\facade\Queue::push(\app\jobs\BoxUserOpenAll::class, $arr, 'BoxOpen');
        }
        return true;
    }

    public function openAll($data, $user, int $companyId = null)
    {
        if(!$data['box_id']) throw new ValidateException('请选择要开启的盲盒');
        if(!$data['num']) throw new ValidateException('请选择开始数量');

        $list = $this->dao->search(['box_id'=>$data['box_id'],'uuid' => $user['id'],'status'=>1], $companyId)
            ->limit($data['num'])->select();

        if (count($list) < $data['num']) throw new ValidateException('您拥有的盲盒不足！');
        $key = $data['box_id'].'_'.$user['id'];
        $res2 = Cache::store('redis')->setnx('open_' .$key,$user['id']);
        Cache::store('redis')->expire('open_' .$key, 3);
        if (!$res2) throw new ValidateException('操作频繁');

        $goodsRepository = app()->make(BoxSaleGoodsListRepository::class);
        $list = $goodsRepository->search(['box_id' => $data['box_id']], $companyId)->select();
        $total = array_sum(array_column(json_decode($list,true),'num'));
        if($total < $data['num']) throw new ValidateException('库存不足!');

        $arr['uuid'] = $user['id'];
        $arr['company_id'] = $companyId;
        $arr['box_id'] = $data['box_id'];
        $arr['num'] = $data['num'];
        $arr['open_no'] = SnowFlake::createOnlyId("open");
        Cache::store('redis')->lPush('openBox_'.$user['id'],json_encode($arr));
        return ['order_id'=>$arr['open_no']];
    }

    public function openAllLog($data,$user,$companyId = null){

        $arr = Cache::store('redis')->rPop('openBox_'.$user['id']);
        if($arr) $pop = json_decode($arr,true);
        if(isset($pop) && $pop){
            (new BoxUserOpenAll())->openBox($pop);
        }


        $list = $this->dao->search(['open_no'=>$data['open_no'],'uuid'=>$user['id']],$companyId)
            ->append(['goods'])
            ->field('id,company_id,add_time,open_no,open_type,goods_id,open_time,no')
            ->select();
        return compact('list');

    }

    public function getApiOpenList(array $data, int $page, int $limit, int $uuid, int $companyId)
    {
//        if (!$data['open_type']) {
//            $data['uuid'] = $uuid;
//        }
        $query = $this->dao->search($data, $companyId)
//            ->group('open_no')
            ->field('id,company_id,uuid,box_id,open_type,open_time,goods_id,no,open_no,order_id');
        $count = $query->count();
        $query->with(['box' => function ($query) {
            $query->field('id,title,file_id')->with(['cover' => function ($query) {
                $query->field('id,show_src,width,height');
            }]);
        },'user'=>function($query){
            $query->field('id,user_code,nickname,head_file_id')->with([
                'avatars' => function ($query) {
                    $query->bind(['avatar' => 'show_src']);
                }
            ]);
        }]);
        $query->withAttr('num',function ($v,$data){
            if($data['open_type'] == 1){
                return app()->make(UsersPoolRepository::class)->search([])->where('order_id',$data['order_id'])->count('id');
            }elseif($data['open_type'] == 2){
                return app()->make(PoolShopOrder::class)->search([])->where('order_id',$data['order_id'])->count('id');
            }
        });
        $query->append(['goods','num']);
        $list = $query->page($page, $limit)
            ->order('id', 'desc')
            ->select();
        return compact('list', 'count');

    }


    public function batchGiveUserBox(array $userId, array $data)
    {

        $usersRepository = app()->make(UsersRepository::class);
        $list = $usersRepository->selectWhere([
            ['id', 'in', $userId]
        ]);
        if ($list) {
            foreach ($list as $k => $v) {
                $this->giveUserBoxInfo($v, $data);
            }
            return $list;
        }
        return [];
    }


    public function giveUserBoxInfo($userInfo, $data)
    {
        $boxSaleRepository = app()->make(BoxSaleRepository::class);
        $boxInfo = $boxSaleRepository->get($data['box_id']);

        if ($boxInfo) {
            Db::transaction(function () use ($userInfo, $data, $boxInfo, $boxSaleRepository) {
                for ($i = 1; $i <= $data['num']; $i++) {
                    $givBoxData['type'] = 5;
                    $givBoxData['status'] = 1;
                    $givBoxData['uuid'] = $userInfo['id'];
                    $givBoxData['box_id'] = $data['box_id'];
                    $this->addInfo($userInfo['company_id'], $givBoxData);
                }
                if ($data['num'] > 0) {
                    $boxSaleRepository->update($data['box_id'], ['stock'=>$boxInfo['stock'] - $data['num']]);
                }
            });
        }
    }

    public function Apirecovery($data, $userId, $companyId = null)
    {
        $list = $this->dao->search(['uuid'=>$userId,'status'=>6],$companyId)
            ->whereIn('id',$data['id'])
            ->append(['goods'])
            ->field('id,company_id,add_time,open_no,open_type,goods_id,open_time,no,order_id')
            ->select();
        if(count($list) == 0) throw new ValidateException('暂无可回收!');
        return Db::transaction(function () use ($list, $data, $userId)
        {
            foreach ($list as $value){
               $goods = $value['goods'];
               $res = $this->dao->update($value['id'], ['status'=>66]);
               if($res){
                   app()->make(UsersRepository::class)->batchFoodChange($userId, 4, $goods['price_tag'], ['remark' => '盲盒回收']);
                   app()->make(PoolShopOrder::class)->search([])->where('order_id',$value['order_id'])->update(['status'=>4]);
               }
            }
        });
    }

}