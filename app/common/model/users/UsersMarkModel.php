<?php

namespace app\common\model\users;

use app\common\model\BaseModel;
use app\common\model\box\BoxSaleModel;
use app\common\model\pool\PoolFollowModel;
use app\common\model\pool\PoolOrderNoModel;
use app\common\model\pool\PoolSaleModel;
use app\common\repositories\box\BoxSaleRepository;
use app\common\repositories\pool\PoolSaleRepository;
use app\common\repositories\users\UsersBoxRepository;
use app\common\repositories\users\UsersPoolRepository;

class UsersMarkModel extends BaseModel
{

    public static function tablePk(): string
    {
        return 'id';
    }

    public static function tableName(): string
    {
        return 'user_mark_sell';
    }

    public function getGoodsAttr($v, $data)
    {
        switch ($data['buy_type']) {
            case 1:  //卡牌
                /** @var PoolSaleRepository $goods */
                $goods = app()->make(PoolSaleRepository::class);
                return $goods->search(['id' => $data['goods_id']])->field('id,title,file_id,num,is_give,ww as circulate')
                    ->with(['cover' => function ($query) {
                        $query->field('id,show_src,width,height');
                    }])->find();
                break;
            case 2: //盲盒
                /** @var BoxSaleRepository $goods */
                $goods = app()->make(BoxSaleRepository::class);
                return $goods->getWhere(['id' => $data['goods_id']], 'id,title,file_id,num,is_give,circulate_true as circulate', ['cover' => function ($query) {
                    $query->field('id,show_src,width,height');
                }]);
                break;
        }
    }

    public function getPoolAttr($v, $data)
    {
        switch ($data['buy_type']) {
            case 1:  //卡牌
                /** @var PoolSaleRepository $goods */
                $goods = app()->make(PoolSaleRepository::class);
                return $goods->search(['id' => $data['goods_id']])->field('id,title,file_id,num,is_give,circulate_true as circulate')
                    ->with(['cover' => function ($query) {
                        $query->field('id,show_src,width,height');
                    }])->find();
                break;
            case 2: //盲盒
                /** @var BoxSaleRepository $goods */
                $goods = app()->make(BoxSaleRepository::class);
                return $goods->getWhere(['id' => $data['goods_id']], 'id,title,file_id,num,is_give,circulate_true as circulate', ['cover' => function ($query) {
                    $query->field('id,show_src,width,height');
                }]);
                break;
        }
    }


    public function getGoodsInfoAttr($v, $data)
    {

        switch ($data['buy_type']) {
            case 1:  //卡牌
                /** @var PoolSaleRepository $goods */
                $goods = app()->make(PoolSaleRepository::class);

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
                            },
                        ]);
                        $query->bind(['img', 'back', 'tableImg', 'mode_type']);
                    },
                    'album' => function ($query) {
                        $query->field('id,name,file_id')->with(['file' => function ($query) {
                            $query->field('id,width,height,show_src');
                        }]);
                    },
                    'author' => function ($query) {
                        $query->field('id,nickname,head_file_id')->with(['avatars' => function ($query) {
                            $query->field('id,width,height,show_src');
                        }]);
                    }, 'brand' => function ($query) {
                        $query->field('id,name,file_id,head_file_id')->with([
                            'file' => function ($query) {
                                $query->field('id,width,height,show_src');
                            },
                            'headInfo' => function ($query) {
                                $query->field('id,width,height,show_src');
                            }
                        ]);
                    }];
                return $goods->search(['id' => $data['goods_id']])
                    ->field('id,title,file_id,ablum_id,brand_id,author_id,num,circulate,content,start_time,end_time')
                    ->with($with)
                    ->find();
                break;
            case 2: //盲盒
                /** @var BoxSaleRepository $goods */
                $goods = app()->make(BoxSaleRepository::class);
                return $goods->getWhere(['id' => $data['goods_id']], 'id,title,file_id,num,circulate_true as circulate,content', ['cover' => function ($query) {
                    $query->field('id,show_src,width,height');
                }]);
                break;
        }

    }

    public function getNftIdAttr()
    {
        if ($this->buy_type == 1) {
            return PoolOrderNoModel::where('pool_id', $this->goods_id)
                ->where(['no' => $this->no])
                ->value('nft_id');
        }
        return '';
    }

    public function isFollow()
    {
        return $this->hasMany(PoolFollowModel::class, 'goods_id', 'goods_id');
    }

    public function pool()
    {
        return $this->hasOne(PoolSaleModel::class, 'id', 'goods_id');
    }

    public function box()
    {
        return $this->hasOne(BoxSaleModel::class, 'id', 'goods_id');
    }

    public function userInfo()
    {
        return $this->hasOne(UsersModel::class, 'id', 'uuid');
    }

    public function userPool()
    {
        return $this->hasOne(UsersPoolModel::class, 'id', 'sell_id');
    }

    public function userBox()
    {
        return $this->hasOne(UsersBoxModel::class, 'id', 'sell_id');
    }

    public function open()
    {
        return $this->hasMany(UsersBoxModel::class, 'box_id', 'goods_id')->where(['status' => 6]);
    }
}
