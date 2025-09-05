<?php

namespace app\common\repositories\pool;

use app\common\dao\pool\PoolSaleDao;
use app\common\repositories\BaseRepository;
use app\common\repositories\box\BoxSaleGoodsListRepository;
use app\common\repositories\mine\MineUserRepository;
use app\common\repositories\givLog\GivLogRepository;
use app\common\repositories\snapshot\SnapshotRepository;
use app\common\repositories\system\upload\UploadFileRepository;
use app\common\repositories\users\UsersAddressRepository;
use app\common\repositories\users\UsersFoodLogRepository;
use app\common\repositories\users\UsersMarkRepository;
use app\common\repositories\users\UsersPoolRepository;
use app\common\repositories\users\UsersRepository;
use app\helper\SnowFlake;
use app\jobs\PoolUserApiBuyJob;
use think\exception\ValidateException;
use think\facade\Cache;
use think\facade\Db;
use app\common\repositories\pool\PoolOrderLockRepository;

/**
 * Class PoolSaleRepository
 * @package app\common\repositories\pool
 * @mixin PoolSaleDao
 */
class PoolSaleRepository extends BaseRepository
{

    public $pooolOrder;
    public $usersRepository;
    public $usersPoolRepository;


    public function __construct(PoolSaleDao $dao, PoolShopOrder $pooolOrder)
    {
        $this->dao = $dao;
        $this->pooolOrder = $pooolOrder;
        /** @var UsersRepository  usersRepository */
        $this->usersRepository = app()->make(UsersRepository::class);
        $this->usersPoolRepository = app()->make(UsersPoolRepository::class);
    }

    public function getAuthor(array $where, int $companyId = null)
    {
        $where['company_id'] = $companyId;
        $data = $this->dao->search($where, $companyId)->select();
        return $data;
    }

    public function getList(array $where, $page, $limit, $companyId = null)
    {
        $query = $this->dao->search($where, $companyId);
        $count = $query->count();
        $list = $query->page($page, $limit)
            ->with(['cover' => function ($query) {
                $query->bind(['picture' => 'show_src']);
            }])
            ->hidden(['site', 'file'])->order('id desc')
            ->select();

        return compact('count', 'list');
    }

    public function getListSnap(array $where, $page, $limit, $companyId = null)
    {
        $query = $this->dao->search($where, $companyId)->field('id,title');
        $count = $query->count();
        $list = $query->page($page, $limit)
            ->with(['cover' => function ($query) {
                $query->bind(['picture' => 'show_src']);
            }])
            ->hidden(['site', 'file'])->order('id desc')
            ->select();

        return compact('count', 'list');
    }

    public function addInfo($companyId, $data)
    {

        return Db::transaction(function () use ($data, $companyId) {
            /** @var UploadFileRepository $uploadFileRepository */
            $uploadFileRepository = app()->make(UploadFileRepository::class);
            $fileInfo = $uploadFileRepository->getFileData($data['cover'], 1, 0);
            if ($fileInfo['id'] > 0) {
                $data['file_id'] = $fileInfo['id'];
            }
            unset($data['cover']);
            $data['company_id'] = $companyId;
            $data['stock'] = $data['num'];
            $data['add_time'] = date('Y-m-d H:i:s');

            $mode['img'] = $data['img'];
            unset($data['img']);
            $mode['back_img'] = $data['back_img'];
            unset($data['back_img']);
            $mode['table_img'] = $data['table_img'];
            unset($data['table_img']);
            $mode['mode_type'] = $data['mode_type'];
            unset($data['mode_type']);
            $pool = $this->dao->create($data);
            $mode['pool_id'] = $pool['id'];
            $poolModeRepository = app()->make(PoolModeRepository::class);
            return $poolModeRepository->addInfo(0, $mode);
        });
    }

    public function editInfo($info, $data)
    {
        if (isset($data['cover'])) {
            /** @var UploadFileRepository $uploadFileRepository */
            $uploadFileRepository = app()->make(UploadFileRepository::class);
            $fileInfo = $uploadFileRepository->getFileData($data['cover'], 1, 0);
            unset($data['cover']);
            if ($fileInfo['id'] != $info['id']) {
                $data['file_id'] = $fileInfo['id'];
            }
            $mode['img'] = $data['img'];
            unset($data['img']);
            $mode['back_img'] = $data['back_img'];
            unset($data['back_img']);
            $mode['table_img'] = $data['table_img'];
            unset($data['table_img']);
            $mode['mode_type'] = $data['mode_type'];
            unset($data['mode_type']);
            /** @var PoolModeRepository $poolModeRepository */
            $poolModeRepository = app()->make(PoolModeRepository::class);
            $modeInfo = $poolModeRepository->getDetail(['pool_id' => $info['id']]);
            $poolModeRepository->editInfo($modeInfo, $mode);
        }

        return $this->dao->update($info['id'], $data);
    }


    public function getDetail(int $id)
    {
        $with = [
            'cover' => function ($query) {
                $query->field('id,show_src');
                $query->bind(['picture' => 'show_src']);
            },
            'PoolMode' => function ($query) {
                $query->with([
                    'img' => function ($query) {
                        $query->bind(['img' => 'show_src']);
                    },
                    'back' => function ($query) {
                        $query->bind(['back_img' => 'show_src']);
                    },
                    'tableImg' => function ($query) {
                        $query->bind(['table_img' => 'show_src']);
                    }
                ]);
                $query->bind(['img', 'back_img', 'table_img', 'mode_type']);
            }
        ];
        $data = $this->dao->search([])
            ->with($with)
            ->where('id', $id)
            ->find();
        return $data;
    }

    /**
     * 删除
     */
    public function batchDelete(array $ids)
    {
        $list = $this->dao->selectWhere([
            ['id', 'in', $ids]
        ]);

        if ($list) {
            foreach ($list as $k => $v) {
                $this->dao->delete($v['id']);
            }
            return $list;
        }
        return [];
    }

    public function getApiList(array $where, $page, $limit, $companyId = null)
    {

        $where['status'] = 1;
        // switch ($where['type']) {
        //     case 2:
        //         $where['virtual'] = 1;
        //         break;
        //     case 1:
        //         $where['virtual'] = 2;
        //         $where['is_number'] = 1;
        //         break;
        // }

        $query = $this->dao->search($where, $companyId);
        $count = $query->count();
        $list = $query->page($page, $limit)
            ->field('id,title,file_id,type,price,remark,virtual,get_type,month_limit,price_tag,num,stock')
            ->withAttr('price', function ($v, $data) {
                if ($data['virtual'] == 2) {
                    return $data['price'];
                } else {
                    return (int)$data['price'];
                }
            })
            ->with([
                'cover' => function ($query) {
                    $query->field('id,show_src,width,height');
                },
            ])
            ->append(['SellNum'])
            ->order('id desc')
            ->select();
        return compact('count', 'list');
    }

    public function getApiDetail(int $id, int $uuid, int $companyId = null)
    {
        $with = [
            'PoolMode' => function ($query) {
                $query->with([
                    'img' => function ($query) {
                        $query->field('id,show_src,width,height');
                    },
                    'back' => function ($query) {
                        $query->field('id,show_src,width,height');
                    },
                    'tableImg' => function ($query) {
                        $query->field('id,show_src,width,height');
                    }
                ]);
                $query->bind(['img', 'back', 'tableImg', 'mode_type']);
            },
        ];
        $info = $this->dao->search([], $companyId)
            ->field('id,title,type,get_type,file_id,limit_num,month_limit,price,is_number,status,content,virtual,remark,is_phone,is_address,price_tag')
            ->with($with)
            ->where('id', $id)
            ->find();


        api_user_log($uuid, 3, $companyId, '查看首发卡牌:' . $info['title']);
        return $info;
    }

    public function apiBuy($data, $user, $companyId = null)
    {
        $uuid = $user['id'];
        $num = (int)$data['num'];
        $id = (int)$data['id'];
        $address_id = $data['address_id'] ? $data['address_id'] : 0;
        $res1 = Cache::store('redis')->setnx('apiBuy_' . $user['id'], $user['id']);
        Cache::store('redis')->expire('apiBuy_' . $user['id'], 3);
        if (!$res1) throw new ValidateException('操作频繁!');

        if ($user['cert_id'] <= 0) throw new ValidateException('请先实名认证!');
        $poolInfo = $this->dao->get($id);
        if (!$poolInfo) throw new ValidateException('非法操作！');
        $mobile = $user['mobile'];
        if ($poolInfo['is_phone'] == 1) {
            if (isset($data['phone']) && !empty($data['phone'])) {
                $mobile = $data['phone'];
            }
        }

        if ($poolInfo['is_address'] == 1) {
            $usersAddressRepository = app()->make(UsersAddressRepository::class);
            $address = $usersAddressRepository->search(['uuid' => $user['id']], $companyId)->where(['id' => $address_id])->find();
            if (!$address) throw new ValidateException('地址错误!');
        }

        $info = $this->getCache($id, $user['id'], $companyId);
        if (!$info) throw new ValidateException('太火爆了！');
        if ($info['status'] != 1) throw new ValidateException('商品未上架');
        if ($info['virtual'] != 1) throw new ValidateException('只能购买周边商品!');

        $count = app()->make(PoolShopOrder::class)->search(['uuid' => $uuid, 'is_mark' => 1])
            ->whereTime('add_time', 'today')
            ->whereIn('status', [1, 2, 3, 5])->count('id');
        if ($poolInfo['limit_num'] > 0 && $count >= $poolInfo['limit_num']) throw new ValidateException('今日已购买!');

        $monthCount = app()->make(PoolShopOrder::class)->search(['uuid' => $uuid, 'is_mark' => 1])
            ->whereMonth('add_time')
            ->whereIn('status', [1, 2, 3, 5])->count('id');
        if ($poolInfo['month_limit'] > 0 && $monthCount >= $poolInfo['month_limit']) throw new ValidateException('本月购买次数已用尽!');

        return Db::transaction(function () use ($user, $uuid, $companyId, $poolInfo, $address_id, $num, $id, $mobile) {
            $status = 1;
            if ($poolInfo['get_type'] == 2) {
                $userPool = app()->make(UsersPoolRepository::class)
                    ->search(['uuid' => $uuid])
                    ->where(['status' => 1])
                    ->limit($num * (int)$poolInfo['price'])
                    ->order('add_time asc')
                    ->count('id');
                if ($userPool <= 0) throw new ValidateException('卡牌不足!');
                if ($userPool < $num * (int)$poolInfo['price']) throw new ValidateException('卡牌不足!');
                $res = app()->make(UsersPoolRepository::class)
                    ->search(['uuid' => $uuid])
                    ->where(['status' => 1])
                    ->limit($num * (int)$poolInfo['price'])
                    ->order('add_time asc')->update(['status' => 88]);
                if (!$res) throw new ValidateException('网络错误!');
                $status = 2;
            }
            //减少库存
            $this->editInfo($poolInfo, ['stock' => $poolInfo['stock'] - $num]);
            $event['uuid'] = $uuid;
            $event['order_id'] = SnowFlake::createOnlyId();
            $event['buy_type'] = 1;
            $event['is_mark'] = 1;
            $event['goods_id'] = $id;
            $event['num'] = $num;
            $event['status'] = $status;
            $event['price'] = $poolInfo['price'];
            $event['money'] = $poolInfo['price'] * $num;
            $event['address_id'] = $address_id;
            $event['mobile'] = $mobile;
            api_user_log($uuid, 4, $companyId, '购买卡牌:' . $poolInfo['title'] . '单成功，订单生成');
            /** @var PoolShopOrder $poolShopOrder */
            $poolShopOrder = app()->make(PoolShopOrder::class);
            return $poolShopOrder->addInfo($companyId, $event);
        });
    }


    public function getApiSearch(array $data, int $companyId = null)
    {
        $data['is_number'] = 1;
        $data['status'] = 1;
        return $this->dao->search($data, $companyId)
            ->field('id,author_id,brand_id,title,file_id,price,num,stock,start_time,status,end_time,content,circulate_true as circulate')
            ->with(['cover' => function ($query) {
                $query->field('id,show_src,width,height');
            }, 'author' => function ($query) {
                $query->field('id,nickname');
            }])
            ->withAttr('price', function ($v, $data) {
                $price = app()->make(UsersMarkRepository::class)->search(['goods_id' => $data['id'], 'buy_type' => 1])
                    ->whereIn('status', [1, 3])->value('price');
                if ($data['stock'] <= 0 && $price) {
                    return $price;
                }
                if ($data['end_time'] <= date('Y-m-d H:i:s') && $price) {
                    return $price;
                }
                return $data['price'];
            })
            ->select();
    }

    public function getCache($id, $uuid, $companyId = null)
    {
        $where['is_number'] = 1;
        $where['status'] = 1;
        $with = [
            'PoolMode' => function ($query) {
                $query->with([
                    'img' => function ($query) {
                        $query->field('id,show_src,width,height');
                    },
                    'back' => function ($query) {
                        $query->field('id,show_src,width,height');
                    },
                    'tableImg' => function ($query) {
                        $query->field('id,show_src,width,height');
                    }
                ]);
                $query->bind(['img', 'back', 'tableImg', 'mode_type']);
            },
        ];

        $info = $this->dao->search([], $companyId)
            ->field('id,stock,title,get_type,file_id,limit_num,month_limit,price,num,is_number,status,content,virtual')
            ->with($with)
            ->where('id', $id)
            ->find();
        if (!$info) {
            return [];
        }
        return $info;
    }

    public function getAll(int $company_id = null)
    {
        return $this->dao->selectWhere(['company_id' => $company_id], 'id,title');
    }

    public function getCascaderData($companyId = 0, $status = '')
    {
        $list = $this->getAll($companyId, $status);
        $list = convert_arr_key($list, 'id');
        return formatCascaderData($list, 'title', 0, 'pid', 0, 1);
    }

    public function getBrandPoolList($data, $page, $limit, $companyId = null)
    {
        $query = $this->dao->search(['brand_id' => $data['brand_id']], $companyId);
        $count = $query->count();
        $list = $query->page($page, $limit)
            ->field('id,title,file_id,author_id,price,num,circulate_true as circulate')
            ->with(['cover' => function ($query) {
                $query->field('id,show_src,width,height');
            }])
            ->select();
        return compact('count', 'list');
    }

    public function getAlbumPoolList($data, $page, $limit, $companyId = null)
    {
        $query = $this->dao->search(['ablum_id' => $data['ablum_id']], $companyId);
        $count = $query->count();
        $list = $query->page($page, $limit)
            ->field('id,title,file_id,author_id,price,num,circulate_true as circulate')
            ->with(['cover' => function ($query) {
                $query->field('id,show_src,width,height');
            }])
            ->select();
        return compact('count', 'list');
    }
}
