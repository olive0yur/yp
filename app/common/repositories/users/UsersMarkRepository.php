<?php

namespace app\common\repositories\users;

use app\common\dao\users\UsersMarkDao;
use app\common\repositories\box\BoxSaleRepository;
use app\common\repositories\pool\PoolFollowRepository;
use app\common\repositories\pool\PoolOrderNoRepository;
use app\common\repositories\pool\PoolSaleRepository;
use app\common\repositories\pool\PoolShopOrder;
use app\common\repositories\pool\PoolTransferLogRepository;
use app\common\repositories\pool\ShopOrderListRepository;
use app\helper\SnowFlake;
use think\exception\ValidateException;
use think\facade\Cache;
use think\facade\Db;
use app\common\repositories\BaseRepository;
use app\common\repositories\mark\UsersBuyRepository;

/**
 * Class UsersMarkRepository
 *
 * @mixin UsersMarkDao
 */
class UsersMarkRepository extends BaseRepository
{

    public $poolSaleRepository;
    public $boxSaleRepository;
    public $usersPoolRepository;
    public $usersBoxRepository;
    public $userRepository;
    public $poolShopOrder;

    public function __construct(UsersMarkDao $dao)
    {
        $this->poolSaleRepository = app()->make(PoolSaleRepository::class);
        $this->boxSaleRepository = app()->make(BoxSaleRepository::class);
        /** @var UsersPoolRepository usersPoolRepository */
        $this->usersPoolRepository = app()->make(UsersPoolRepository::class);
        /** @var UsersBoxRepository usersBoxRepository */
        $this->usersBoxRepository = app()->make(UsersBoxRepository::class);
        $this->userRepository = app()->make(UsersRepository::class);
        $poolShopOrder = app()->make(PoolShopOrder::class);
        $this->poolShopOrder = $poolShopOrder;
        $this->dao = $dao;
    }

    public function getList(array $where, $page, $limit, int $companyId = null)
    {

        $query = $this->dao->search($where, $companyId);
        $count = $query->count();
        $list = $query->page($page, $limit)
            ->with(['pool'])
            ->order('id', 'desc')
            ->select();
        return compact('list', 'count');
    }

    public function getApiListPool(array $where, $page, $limit, int $companyId = null)
    {
        $where['is_number'] = 1;
        $where['is_mark'] = 1;

        $usersMarkRepository = app()->make(UsersMarkRepository::class);

        $query = app()->make(PoolSaleRepository::class)->search($where, $companyId);

        if ($where['is_follow'] == 1) {
            $ids = app()->make(PoolFollowRepository::class)->search(['uuid' => $where['uuid'], 'buy_type' => 1], $companyId)->column('goods_id');
            $query->whereIn('id', $ids);
        }
        if ($where['is_hold'] == 1) {
            $ids = app()->make(UsersPoolRepository::class)->search(['uuid' => $where['uuid'], 'status' => 1], $companyId)->column('pool_id');
            $query->whereIn('id', $ids);
        }
        $query->field('id,title,file_id,num,circulate,destroy,max_price,add_time');
        $query->with([
            'cover' => function ($query) {
                $query->field('id,show_src,width,height');
            },
            'mark' => function ($query) {
                $query->whereIn('status', [1, 3])->order('price asc');
            }
        ])->withAttr('price', function ($v, $data) use ($usersMarkRepository, $companyId) {
            $price = $usersMarkRepository->search(['buy_type' => 1, 'goods_id' => $data['id']], $companyId)
                ->whereIn('status', [1, 3])->min('price');
            if (!$price) $price = $data['max_price'];
            return $price;
        })->withAttr('sort_time', function ($v, $data) use ($usersMarkRepository, $companyId) {
            $add_time = $usersMarkRepository->search(['buy_type' => 1, 'goods_id' => $data['id']], $companyId)
                ->whereIn('status', [1, 3])->order('add_time desc')->value('add_time');
            if (!$add_time) $add_time = $data['add_time'];
            return $add_time;
        })->append(['price', 'sort_time', 'sell_num']);
        $count = $query->count('id');
        $query->order('id desc');
        $list = $query->select();
        if (count($list) > 0) {
            $list = json_decode($list, true);
            $price = array_column($list, 'price');
            $addTime = array_column($list, 'sort_time');
            switch ($where['sort']) {
                  case 1: //降
                  array_multisort($addTime, SORT_DESC, $list);
                   break;
                 case 2://升
                  array_multisort($addTime, SORT_ASC, $list);
                  break;
                case 3: //降
                    array_multisort($price, SORT_DESC, $list);
                    break;
                case 4://升
                    array_multisort($price, SORT_ASC, $list);
                    break;
            }
        }
        /*foreach ($list as &$item)
        {
            $item['is_follow_count'] = app()->make(PoolFollowRepository::class)->search(['uuid' => $where['uuid']], $companyId)->where('goods_id', $item['id'])->count('id');
        }*/
        return compact('list', 'count');
    }

    public function getApiListBox(array $where, $page, $limit, int $companyId = null)
    {
        $query = $this->dao->search($where, $companyId)->whereIn('status', [1, 3]);
        if (isset($where['is_follow']) && $where['is_follow'] == 1) {
            $ids = app()->make(PoolFollowRepository::class)->search(['uuid' => $where['uuid'], 'buy_type' => 2], $companyId)->column('goods_id');
            $query->whereIn('goods_id', $ids);
        }
        if (isset($where['is_hold']) && $where['is_hold'] == 1) {
            $ids = app()->make(UsersBoxRepository::class)->search(['uuid' => $where['uuid'], 'status' => 1], $companyId)->column('box_id');
            $query->whereIn('goods_id', $ids);
        }
        if (isset($where['brand_id'])) {
            $ids = app()->make(BoxSaleRepository::class)->search(['brand_id' => $where['brand_id']], $companyId)->column('id');
            $query->whereIn('goods_id', $ids);
        }

        $box_open_type = intval(web_config($companyId, 'program.market.box_open_type'));

        if($box_open_type == 2){
            $query->field('id,goods_id,sell_id,status,price,no,buy_type');
        }else{
            $query->field('id,goods_id,sell_id,status,price,no,buy_type')->group('goods_id');
        }
        $count = $query->count('id');
        $query->withAttr('goods')->append(['goods']);
        $query->page($page, $limit);
        $list = $query->select();
        if (count($list) > 0) {
            $list = json_decode($list, true);
            $price = array_column($list, 'price');
            $addTime = array_column($list, 'sort_time');
            switch ($where['sort']) {
                  case 1://降
                  array_multisort($addTime, SORT_DESC, $list);
                   break;
                 case 2://升
                  array_multisort($addTime, SORT_ASC, $list);
                  break;
                case 3: //降
                    array_multisort($price, SORT_DESC, $list);
                    break;
                case 4://升
                    array_multisort($price, SORT_ASC, $list);
                    break;
            }
        }
        foreach ($list as &$item)
        {
            $item['is_follow_count'] = app()->make(PoolFollowRepository::class)->search(['uuid' => $where['uuid']], $companyId)->where('goods_id', $item['id'])->count('id');
        }
        return compact('list', 'count','box_open_type');
    }

    public function getCount($data, int $conpanyId = null)
    {
        return ['count' => $this->dao->search($data, $conpanyId)->where('price', '<=', $data['price'])->count('id')];
    }

    public function getApiDetail(array $where, int $uuid, int $companyId = null)
    {
        $query = $this->dao->search($where, $companyId)
            ->whereIn('status', [1, 3])
            ->with([
                'userInfo' => function ($query) {
                    $query->field('id,nickname');
                    $query->bind(['nickname']);
                }
            ])
            ->field('id,goods_id,uuid,sell_id,status,price,no,buy_type');
        $query->append(['goodsInfo','nft_id'])
            ->withCount([
                'is_follow' => function ($query) use ($where, $uuid) {
                    $query->where(['uuid' => $uuid, 'buy_type' => $where['buy_type']]);
                }, 'open']);
        $info = $query->find();
        if (!$info) {
            throw new ValidateException('数据不存在');
        }
        if ($info['open_count'] > 0) {
            $info['unopen'] = $info['goodsInfo']['num'] - $info['open_count'];
        }
        if ($info['buy_type'] == 1) {
            $info['hash'] = app()->make(PoolOrderNoRepository::class)->search(['no' => $info['no'], 'pool_id' => $info['goods_id']], $companyId)->value('hash');
        }
        return $info;
    }

    public function apiSale($data, $user, $companyId = null)
    {
        if ($user['cert_id'] <= 0) throw new ValidateException('请先实名认证!');
        $auth = $this->userRepository->passwordVerify($data['pay_password'], $user['pay_password']);
        if (!$auth) throw new ValidateException('交易密码错误');
        $res2 = Cache::store('redis')->setnx('apiSale_mark'.$data['sell_id'], $user['sell_id']);
        if(!$res2) throw new ValidateException('单个卡牌请勿多次寄售!');
        Cache::store('redis')->expire('apiSale_mark'.$data['sell_id'],1);
        $certType = (int)web_config($companyId, 'site.env_type', 1);
/*        if ($certType == 2) {
            if ($user['is_open'] != 1) throw new ValidateException('请先开户!');
        }*/

        $arr['uuid'] = $user['id'];
        $arr['buy_type'] = $data['buy_type'];
        $arr['sell_id'] = $data['sell_id'];
        $arr['price'] = $data['price'];
        switch ($data['buy_type']) {
            case 1:
                $userPool = $this->usersPoolRepository->getDetail($data['sell_id']);
                if (!$userPool) throw new ValidateException('卡牌不存在');
                if ($userPool['status'] != 1) throw new ValidateException('当前卡牌无法寄售');
                if ($userPool['is_sale'] != 1) throw new ValidateException('当前卡牌禁止寄售');
                $pool = $this->poolSaleRepository->getDetail($userPool['pool_id']);
                if (!$pool) throw new ValidateException('卡牌不存在');
                if ($pool['is_mark'] != 1) throw new ValidateException('当前卡牌未开始市场');

                if ($pool['max_price'] > 0 && $data['price'] > $pool['max_price']) throw new ValidateException('限价上限为' . $pool['max_price']);
                if ($pool['min_price'] > 0 && $data['price'] < $pool['min_price']) throw new ValidateException('限价下限为' . $pool['min_price']);
                $arr['goods_id'] = $userPool['pool_id'];
                $arr['buy_type'] = 1;
                $arr['no'] = $userPool['no'];

                return Db::transaction(function () use ($arr, $userPool, $data, $companyId) {
                    $this->addInfo($companyId, $arr);
                    $log['pool_id'] = $arr['goods_id'];
                    $log['no'] = $userPool['no'];
                    $log['uuid'] = $arr['uuid'];
                    $log['type'] = 4;
                    $log['price'] = $arr['price'];
                    app()->make(PoolTransferLogRepository::class)->addInfo($companyId,$log);
                    return $this->usersPoolRepository->editInfo($userPool, ['status' => 2]);
                });
                break;
            case 2:
                $userBox = $this->usersBoxRepository->getDetail($data['sell_id']);
                if (!$userBox) throw new ValidateException('盲盒不存在');
                if ($userBox['status'] != 1) throw new ValidateException('当前盲盒无法寄售');
                $box = $this->boxSaleRepository->getDetail($userBox['box_id']);
                if (!$box) throw new ValidateException('盲盒不存在');
                if ($box['is_mark'] != 1) throw new ValidateException('当前盲盒未开始市场');
                if ($box['max_price'] > 0 && $data['price'] > $box['max_price']) throw new ValidateException('限价上限为' . $box['max_price']);
                if ($box['min_price'] > 0 && $data['price'] < $box['min_price']) throw new ValidateException('限价下限为' . $box['min_price']);
                $arr['goods_id'] = $userBox['box_id'];
                $arr['buy_type'] = 2;
                return Db::transaction(function () use ($arr, $userBox, $companyId) {
                    $this->usersBoxRepository->editInfo($userBox, ['status' => 2]);
                    return $this->addInfo($companyId, $arr);
                });
                break;
        }
        return true;
    }

    public function getDetail(int $id)
    {
        $data = $this->dao->search([])
            ->where('id', $id)
            ->find();
        return $data;
    }

    ## 市场详情

    public function addInfo(int $companyId = null, array $data = [])
    {
        return Db::transaction(function () use ($data, $companyId) {
            if ($companyId) $data['company_id'] = $companyId;
            $data['add_time'] = date('Y-m-d H:i:s');
            $userInfo = $this->dao->create($data);
            return $userInfo;
        });
    }

    public function editInfo($info, $data)
    {
        return $this->dao->update($info['id'], $data);
    }

    public function getApiOrderAll($data, $uuid, $companyId = null)
    {

        $rt = $this->poolShopOrder->search(['pool_id' => $data['goods_id'], 'buy_type' => $data['buy_type'], 'is_mark' => 3, 'uuid' => $uuid, 'is_main' => 1, 'status' => 1], $companyId)->find();
        if ($rt) {
            $addList = $this->poolShopOrder->search(['is_main' => 2], $companyId)
                ->where(['main_order_id' => $rt['id']])
                ->with(['getHash' => function ($query) use ($data) {
                    $query->where(['pool_id' => $data['goods_id']]);
                    $query->bind(['hash']);
                }])
                ->select();
            $rt['orderList'] = $addList;
            return $rt;
        }
        throw new ValidateException('');
    }


    public function cannel($where, $uuid, $companyId)
    {
        $where['uuid'] = $uuid;
        $info = $this->dao->search($where, $companyId)->find();
        if (!$info) throw new ValidateException('寄售信息不存在！');
        if ($info['status'] != 1) throw new ValidateException('当前寄售状态不支持撤销');
        $re = $this->editInfo($info, ['status' => 4]);
        if (!$re) throw new ValidateException('网络错误！');
        switch ($info['buy_type']) {
            case 1:
                $count = $this->usersPoolRepository->search(['pool_id'=>$info['goods_id'],'no'=>$info['no']])
                    ->whereIn('status',[1,2])
                    ->where('uuid','<>',$uuid)->count('id');
                if($count > 0) throw new ValidateException('当前卡牌不可撤销!');
                $pool = $this->usersPoolRepository->getDetail($info['sell_id'], $companyId);
                return $this->usersPoolRepository->editInfo($pool, ['status' => 1]);
            case 2:
                $box = $this->usersBoxRepository->getDetail($info['sell_id'], $companyId);
                return $this->usersBoxRepository->editInfo($box, ['status' => 1]);
        }
        throw new ValidateException('网络错误！');
    }


    public function getNoList(array $where, int $page, int $limit, int $uuid, int $companyId = null)
    {
        $where['buy_type'] = 1;
        $query = $this->dao->search($where, $companyId)->whereIn('status', [1, 3])->field('id,goods_id,sell_id,status,price,no,buy_type');
        $count = $query->count();

        $with = [
            'pool' => function ($query) use ($uuid) {
                $query->field('id,title,file_id,num, circulate')->with(['cover' => function ($query) {
                    $query->field('id,show_src,width,height');
                }]);
            },
        ];
        $query->with($with);
        switch ($where['sort']) {
            case 1:
                $query->order('price +0 asc,id');
                break;
            case 2:
                $query->order('no + 0 desc,id');
                break;
        }


        $list = $query->page($page, $limit)
            ->append(['nft_id'])
            ->select();
        $brand = app()->make(PoolSaleRepository::class)->search([],$companyId)->where(['id'=>$where['goods_id']])
            ->with(['brand'=>function($query){
                $query->field('id,name,file_id')->with(['file'=>function($query){
                    $query->field('id,show_src,width,height');
                }]);
            }])->field('id,brand_id')->find();
        $is_follow_count = app()->make(PoolFollowRepository::class)->search(['uuid' => $uuid], $companyId)->where('goods_id', $where['goods_id'])->count('id');
        return compact('list', 'count', 'is_follow_count','brand');
    }

    public function getNoBoxList(array $where, int $page, int $limit, int $uuid, int $companyId = null)
    {
        $where['buy_type'] = 2;
        $query = $this->dao->search($where, $companyId)->whereIn('status', [1, 3])->field('id,goods_id,sell_id,status,price,no');
        $count = $query->count();

        $with = ['box' => function ($query) use ($uuid) {
            $query->field('id,title,file_id,num')->with(['cover' => function ($query) {
                $query->field('id,show_src,width,height');
            }]);
        },
        ];
        $query->with($with);
        switch ($where['sort']) {
            case 1:
                $query->order('price', 'asc');
                break;
            case 2:
                $query->order('no', 'asc');
                break;
        }

        $list = $query->page($page, $limit)
            ->select();
        $is_follow_count = app()->make(PoolFollowRepository::class)->search(['uuid' => $uuid], $companyId)->where('goods_id', $where['goods_id'])->count('id');
        return compact('list', 'count', 'is_follow_count');
    }


    ## 寄售记录
    public function getLog(array $where, int $page, int $limit, int $uuid, int $companyId = null)
    {
        $where['uuid'] = $uuid;
        $where['is_type'] = 1;
        $query = $this->dao->search($where, $companyId);
        $count = $query->count();
//        $with = ['pool' => function ($query) {
//            $query->field('id,title,file_id,num, circulate')->with(['cover' => function ($query) {
//                $query->field('id,show_src,width,height');
//            }]);
//        },
//        ];
        $query->append(['pool']);
//        $query->with($with);
        $list = $query->page($page, $limit)
            ->order('id', 'desc')
            ->select();
        return compact('list', 'count');
    }

}