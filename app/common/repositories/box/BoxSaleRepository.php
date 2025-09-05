<?php

namespace app\common\repositories\box;

use app\common\dao\box\BoxSaleDao;
use app\common\dao\pool\PoolSaleDao;
use app\common\repositories\active\ActiveRepository;
use app\common\repositories\BaseRepository;
use app\common\repositories\pool\PoolShopOrder;
use app\common\repositories\system\upload\UploadFileRepository;
use app\common\repositories\users\UsersBoxRepository;
use app\common\repositories\users\UsersPoolRepository;
use app\common\repositories\users\UsersRepository;
use app\helper\SnowFlake;
use think\exception\ValidateException;
use think\facade\Cache;
use think\facade\Db;
use app\common\repositories\pool\PoolOrderLockRepository;

/**
 * Class PoolSaleRepository
 * @package app\common\repositories\pool
 * @mixin PoolSaleDao
 */
class BoxSaleRepository extends BaseRepository
{

    public $usersBoxRepository;
    public $usersRepository;


    public function __construct(BoxSaleDao $dao)
    {
        $this->dao = $dao;
        $this->usersRepository = app()->make(UsersRepository::class);
        $this->usersBoxRepository = app()->make(UsersBoxRepository::class);
    }

    public function getList(array $where, $page, $limit, $companyId = null)
    {
        $query = $this->dao->search($where, $companyId);
        $count = $query->count();
        $list = $query->page($page, $limit)
            ->with(['cover' => function ($query)
            {
                $query->bind(['picture' => 'show_src']);
            }])
            ->hidden(['file'])->order('id desc')
            ->select();
        return compact('count', 'list');
    }


    public function addInfo($companyId, $data)
    {
        return Db::transaction(function () use ($data, $companyId)
        {
            /** @var UploadFileRepository $uploadFileRepository */
            $uploadFileRepository = app()->make(UploadFileRepository::class);
            if ($data['cover'])
            {
                $fileInfo = $uploadFileRepository->getFileData($data['cover'], 1, 0);
                if ($fileInfo)
                {
                    if ($fileInfo['id'] > 0)
                    {
                        $data['file_id'] = $fileInfo['id'];
                    }
                }
            }
            unset($data['cover']);
//            if ($data['num'] < $data['reserve_num']) throw new ValidateException('预留数量不能大于发行数量！');
            $data['company_id'] = $companyId;
            $data['stock'] = $data['num'];
            $data['add_time'] = date('Y-m-d H:i:s');

            return $this->dao->create($data);
        });


    }

    public function editInfo($info, $data)
    {
        /** @var UploadFileRepository $uploadFileRepository */
        $uploadFileRepository = app()->make(UploadFileRepository::class);
        if ($data['cover'])
        {
            $fileInfo = $uploadFileRepository->getFileData($data['cover'], 1, 0);
            if ($fileInfo)
            {
                if ($fileInfo['id'] != $info['id'])
                {
                    $data['file_id'] = $fileInfo['id'];
                }
            }
        }
        unset($data['cover']);
        return $this->dao->update($info['id'], $data);
    }

    public function checkPrice($info, $data)
    {
        $this->dao->update($info['id'], $data);
    }

    public function getDetail(int $id)
    {
        $with = [
            'cover' => function ($query)
            {
                $query->field('id,show_src');
                $query->bind(['picture' => 'show_src']);
            },
        ];
        $data = $this->dao->search([])
            ->with($with)
            ->where('id', $id)
            ->find();
        return $data;
    }

    public function batchDelete(array $ids)
    {
        $list = $this->dao->selectWhere([
            ['id', 'in', $ids]
        ]);

        if ($list)
        {
            foreach ($list as $k => $v)
            {
                $this->dao->delete($v['id']);
            }
            return $list;
        }
        return [];
    }


    public function getApiList(array $where, $page, $limit, $companyId = null)
    {
        $boxGoodsListRepository = app()->make(BoxSaleGoodsListRepository::class);
        $where['status'] = 1;
        $where['is_number'] = 1;
        $query = $this->dao->search($where, $companyId);
        $count = $query->count();
        $list = $query->page($page, $limit)
            ->field('id,title,file_id,price,num,stock')
            ->with(['cover' => function ($query){
                $query->field('id,show_src,width,height');
            },
            ])
            ->withAttr('goods', function ($v, $data) use ($boxGoodsListRepository){
                return $boxGoodsListRepository->search(['box_id' => $data['id']])
                    ->append(['goods'])->hidden(['num'])->select();
            })
            ->append(['goods'])
            ->order('id desc')
            ->select();

        return compact('count', 'list');
    }

    public function getApiDetail(int $id, int $uuid, int $companyId = null)
    {
        $with = [

        ];
        $data = $this->getCache($id, $uuid, $companyId);
        api_user_log($uuid, 3, $companyId, '查看首发盲盒:' . $data['title']);
        return $data;
    }

    public function getApiGoods(int $id, int $companyId = null)
    {
        $boxGoodsListRepository = app()->make(BoxSaleGoodsListRepository::class);
        $list = $boxGoodsListRepository->search(['box_id' => $id])
            ->append(['goods'])
            ->hidden(['num'])
            ->select();
        return $list;
    }

    public function getApiReceiveList($companyId = null, $userInfo = [])
    {
        $where['status'] = 1;
        $where['get_type'] = 4;
        $query = $this->dao->search($where, $companyId);
        $list = $query
            ->field('id,title,file_id,price,num,stock,add_time')
            ->with(['cover' => function ($query){
                $query->field('id,show_src,width,height');
            }])->order('id desc')
            ->select();
        return compact('list');
    }

    /**
     * 下单购买
     * @param int $id
     * @param $userInfo
     * @param int $num
     * @param $companyId
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function apiBuy(int $id, $userInfo, int $num, $companyId = null)
    {
        if ($userInfo['cert_id'] <= 0) throw new ValidateException('请先实名认证!');

        $poolShopOrder = app()->make(PoolShopOrder::class);
        $count = $poolShopOrder->search(['uuid' => $userInfo['id'], 'status' => 1], $companyId)->count('id');
        if ($count > 0) throw new ValidateException('请先处理待支付订单！');


        $info = $this->dao->search(['status' => 1])->where('id', $id)->find();
        if (!$info) throw new ValidateException('盲盒不存在');
        if ($info['status'] != 1) throw new ValidateException('盲盒未上架');

        $stockNum = $info['stock'];
        if ($stockNum <= 0) throw new ValidateException('库存不足！');
        if ($stockNum < $num) throw new ValidateException('库存不足！');
        return Db::transaction(function () use ($poolShopOrder, $companyId, $userInfo, $info, $num, $id)
        {
            $orderData = [];
            $orderData['num'] = $num;
            $orderData['uuid'] = $userInfo['id'];
            $orderData['price'] = $info['price'];
            $orderData['money'] = bcmul($num, $info['price'], 2);
            $orderData['buy_type'] = 2;
            $orderData['is_mark'] = 1;
            $orderData['goods_id'] = $info['id'];
            $orderData['order_id'] = SnowFlake::createOnlyId('No');

            $res = $poolShopOrder->addInfo($companyId, $orderData);
            $this->dao->decField($info['id'], 'stock', $num);

            if ($info['is_to'] == 2 && $res){
                $userPool = app()->make(UsersPoolRepository::class)
                    ->search(['uuid'=>$userInfo['id']])
                    ->where(['status'=>1])
                    ->limit($num * (int)$info['price'])
                    ->order('add_time asc')
                    ->count('id');
                if($userPool <= 0) throw new ValidateException('卡牌不足!');
                if($userPool < $num * (int)$info['price']) throw new ValidateException('卡牌不足!');
                $res = app()->make(UsersPoolRepository::class)
                    ->search(['uuid'=>$userInfo['id']])
                    ->where(['status'=>1])
                    ->limit($num * (int)$info['price'])
                    ->order('add_time asc')->update(['status'=>88]);
                if(!$res) throw new ValidateException('网络错误!');
                $poolShopOrder->search([])->where('id',$res['id'])->update(['status'=>2]);

                $data = [];
                $data['uuid'] = $userInfo['id'];
                $data['add_time'] = date('Y-m-d H:i:s');
                $data['order_id'] = $orderData['order_id'];
                $data['box_id'] = $info['id'];
                $data['price'] = $orderData['price'];
                $data['type'] = 1;
                $userBox = app()->make(UsersBoxRepository::class);
                $count = $userBox->getCount(['order_id' => $data['order_id']], $companyId);
                if ($count >= $num){
                    return true;
                } else{
                    for ($i = 1; $i <= $num; $i++){
                        $userBox->addInfo($companyId, $data);
                    }
                }
            }
        });
    }

    /**
     * 免费领取
     * @param int $id
     * @param $userInfo
     * @param int $num
     * @param $companyId
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public
    function apiReceive(int $id, $userInfo, $companyId = null)
    {
        if ($userInfo['cert_id'] <= 0) throw new ValidateException('请先实名认证!');

        $boxiInfo = $this->dao->getSearch(['status' => 1, 'get_type' => 4])->where('id', $id)->find();
        if (!$boxiInfo) throw new ValidateException('盲盒不存在');
        if ($boxiInfo['stock'] <= 0)
        {
            throw new ValidateException('盲盒库存不足');
        }

        $tjrRegNum = intval(web_config($companyId, 'reg.tj_reg_num'), 0);## 用户邀请人数
        $givBoxId = intval(web_config($companyId, 'reg.giv_box_id'), 0); ## 赠送的肓盒
        $givDayNum = intval(web_config($companyId, 'reg.giv_day_num'), 0);## 两次之间的领取间隔天数
        if ($tjrRegNum > 0 && $givBoxId > 0 && $givDayNum > 0)
        {
            /** @var UsersRepository $usersRepository */
            $usersRepository = app()->make(UsersRepository::class);
            $ztNum = $usersRepository->getUserZtValidnNum($userInfo['id']);

            if ($ztNum < $tjrRegNum)
            {
                throw new ValidateException('邀请人数不足');
            }
            /** @var UsersBoxRepository $usersBoxRepository */
            $usersBoxRepository = app()->make(UsersBoxRepository::class);
            $lastInfo = $usersBoxRepository->getSearch(['type' => 7])->oeder('id', 'desc')->find();
            $date = date_create($lastInfo['add_time']);
            date_add($date, date_interval_create_from_date_string("$givDayNum days"));
            $nextTime = date_format($date, "Y-m-d H:i:s");
            if (date('Y-m-d H:i:s') < $nextTime)
            {
                throw new ValidateException($nextTime . '后可领取');
            }
            $event['uuid'] = $userInfo['id'];
            $event['num'] = 1;
            $event['buy_type'] = 2;
            $event['is_mark'] = 7;
            $event['goods_id'] = $boxiInfo['id'];
            $event['company_id'] = $companyId;
            $event['price'] = 0;
            $event['type'] = 7;
            $isPushed = \think\facade\Queue::push(\app\jobs\BoxUserReceiveJob::class, $event);
            return true;
        } else
        {
            throw new ValidateException('领取失败');
        }
    }


    public
    function getCache($id, $uuid, $companyId = null)
    {
        $info = Cache::store('redis')->get('box_' . $id);
//        if(!$info){
        $info = $this->dao->search(['status' => 1])
            ->field('id,title,status,file_id,price,num,stock,content,equity')
            ->with(['cover' => function ($query)
            {
                $query->field('id,show_src,width,height');
            }
            ])
            ->withCount(['is_follow' => function ($query) use ($uuid)
            {
                $query->where(['uuid' => $uuid]);
            }])
            ->where('id', $id)
            ->find();
        $len = Cache::store('redis')->lLen('box_num_' . $id);
        if (!$len || $len <= 0)
        {
            for ($i = 1; $i <= $info['stock']; $i++)
            {
                Cache::store('redis')->lpush('box_num_' . $info['id'], 1);
            }
        }
        Cache::store('redis')->set('box_' . $id, $info, 3);
//        }
        return $info;
    }

    public
    function getAll(int $company_id = null)
    {
        return $this->dao->selectWhere(['company_id' => $company_id], 'id,title');
    }

    public
    function getCascaderData($companyId = 0, $status = '')
    {
        $list = $this->getAll($companyId, $status);
        $list = convert_arr_key($list, 'id');
        return formatCascaderData($list, 'title', 0, 'pid', 0, 1);
    }


    public
    function getApiBrandBoxList($data, $page, $limit, $companyId = null)
    {
        $query = $this->dao->search(['brand_id' => $data['brand_id']], $companyId);
        $count = $query->count();
        $list = $query->page($page, $limit)
            ->field('id,title,file_id,num,price,is_give,is_mark,content,limit_num,stock,min_price,max_price')
            ->with(['cover' => function ($query)
            {
                $query->field('id,show_src,width,height');
            }])
            ->select();
        return compact('count', 'list');
    }

    public
    function getApiAlbumBoxList($data, $page, $limit, $companyId = null)
    {
        $query = $this->dao->search(['ablum_id' => $data['ablum_id']], $companyId);
        $count = $query->count();
        $list = $query->page($page, $limit)
            ->field('id,title,file_id,num,price,is_give,is_mark,content,limit_num,stock,min_price,max_price')
            ->with(['cover' => function ($query)
            {
                $query->field('id,show_src,width,height');
            }])
            ->select();
        return compact('count', 'list');
    }
}