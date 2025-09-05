<?php

namespace app\controller\company\pool;

use app\common\repositories\cochain\CaoTianUserRepository;
use app\common\repositories\tichain\TichainPoolRepository;
use app\common\repositories\users\UsersPoolRepository;
use app\common\repositories\users\UsersPushRepository;
use app\common\services\AvataService;
use app\common\services\CaoTianService;
use think\App;
use think\exception\ValidateException;
use think\facade\Cache;
use app\controller\company\Base;
use app\validate\pool\SaleValidate;
use app\common\repositories\users\UsersRepository;
use app\common\repositories\pool\PoolModeRepository;
use app\common\repositories\pool\PoolSaleRepository;
use app\common\repositories\cochain\EbaoPoolRepository;
use app\common\repositories\union\UnionAlbumRepository;
use app\common\repositories\union\UnionBrandRepository;

class Sale extends Base
{
    protected $repository;

    public function __construct(App $app, PoolSaleRepository $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
        $this->assign(['VirtualAuth' => company_auth('companyPoolSaleVirtual')]);
    }

    protected function commonParams()
    {
        $brandData = $this->app->make(UnionBrandRepository::class)->getBrandData(1, $this->request->companyId);
        $this->assign([
            'branCase' => $brandData,
        ]);
    }



    public function list()
    {
        if ($this->request->isAjax()) {
            $where = $this->request->param([
                'keywords' => '',
            ]);
            [$page, $limit] = $this->getPage();
            $data = $this->repository->getList($where, $page, $limit, $this->request->companyId);
            return json()->data(['code' => 0, 'data' => $data['list'], 'count' => $data['count']]);
        }
        return $this->fetch('pool/sale/list', [
            'addAuth' => company_auth('companyPoolSaleAdd'),
            'editAuth' => company_auth('companyPoolSaleEdit'),
            'delAuth' => company_auth('companyPoolSaleDel'),
            'switchStatusAuth' => company_auth('companyPoolSaleSwitch'),##上架/下架
            'orderNoAuth' => company_auth('companyPoolSaleOrderNo'),##编号
            'marketAuth' => company_auth('companyPoolSaleMarketSwitch'),##市场开关
            'giveAuth' => company_auth('companyPoolSaleGiveSwitch'),##转赠开关
            'hotAuth' => company_auth('companyPoolSaleHotSwitch'),##热门
            'checkAuth' => company_auth('companyPoolSaleCheckPrice'),##限价
            'drawAuth' => company_auth('companyPoolDraw'),##
            'drawEditAuth' => company_auth('companyPoolDrawEdit'),##
            'createAuth' => company_auth('companyPoolSaleCreateNft'),##
            'cochain' => config('cochain.default'),
            'hold' => company_auth('companyPoolHold'),
            'integral' => env('integral.i_default'),
            'companyId'=>$this->request->companyId,
            'poolBonus' => company_auth('companyUserPoolBonus'),
        ]);
    }

    /**
     *
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $param = $this->request->param([
                'title' => '',
                'num' => '',
                'limit_num' => '',
                'month_limit'=>'',
                'brand_id'=>'',
                'cover' => '',
                'img' => '',
                'back_img' => '',
                'table_img' => '',
                'mode_type' => '',
                'content' => '',
                'get_type'=>'',
                'price' => '',
                'circulate' => '',
                'integral'  => '',
                'sort'=>'',
                'virtual'=>'',
                'type'=>'',
                'card_type'=>'',
                'remark'=>'',
                'ageing'=>'',
                'is_phone'=>'',
                'is_address'=>'',
                'price_tag'=>''
            ]);
            if($param['num'] > 30000) return $this->error('单次最多发行三万份!');
            if(!$param['virtual']) $param['virtual'] = 2;
            validate(SaleValidate::class)->scene('add')->check($param);
            if ($param['mode_type'] == 2 && !$param['table_img']) return $this->error('请上传台子图');
            try {
                $res = $this->repository->addInfo($this->request->companyId, $param);
                if ($res) {
                    admin_log(3, '添加卡牌', $param);
                    return $this->success('添加成功');
                } else {
                    return $this->error('添加失败');
                }
            } catch (\Exception $e) {
                return $this->error($e->getMessage());
            }
        } else {

            $this->commonParams();
            $pool = $this->app->make(PoolModeRepository::class)->recently($this->request->companyId);
            return $this->fetch('pool/sale/add', [
                'pool' => $pool,
                'integral' => env('integral.i_default'),
                'companyId'=>$this->request->companyId,
                'tokens'=>web_config($this->request->companyId,'site')['tokens']
            ]);
        }
    }

    /**
     * 编辑
     */
    public function edit()
    {
        $id = $this->request->param('id');

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
                'title' => '',
                'num' => '',
                'brand_id'=>'',
                'limit_num' => '',
                'month_limit'=>'',
                'cover' => '',
                'img' => '',
                'back_img' => '',
                'get_type'=>'',
                'table_img' => '',
                'content' => '',
                'mode_type' => '',
                'price' => '',
                'circulate' => '',
                'virtual'=>'',
                'type'=>'',
                'card_type'=>'',
                'remark'=>'',
                'ageing'=>'',
                'is_phone'=>'',
                'is_address'=>'',
                'price_tag'=>''
            ]);
            if(!$param['virtual']) $param['virtual'] = 2;
            validate(SaleValidate::class)->scene('edit')->check($param);
            try {
                $res = $this->repository->editInfo($info, $param);
                if ($res) {
                    Cache::store('redis')->delete('goods_' . $param['id']);
                    return $this->success('修改成功');
                } else {
                    return $this->error('修改失败');
                }
            } catch (\Exception $e) {
                return $this->error($e->getMessage());
            }
        } else {
            $this->commonParams();
            return $this->fetch('pool/sale/edit', [
                'info' => $info,
                'integral' => env('integral.I_DEFAULT'),
                'companyId'=>$this->request->companyId,
                'tokens'=>web_config($this->request->companyId,'site')['tokens']
            ]);
        }
    }


    /**
     * 设置卡牌状态
     */
    public function status()
    {
        $id = (int)$this->request->param('id');
        if ($this->request->isPost()) {
            $param = $this->request->param([
                'id' => '',
                'status' => '',
            ]);

            if (!$this->repository->exists($id)) {
                return $this->error('数据不存在');
            }
            try {
                $res = $this->repository->update($id, $param);
                admin_log(3, '修改卡牌状态 id:' . $id, $param);
                if ($res) {
                    Cache::store('redis')->delete('goods_' . $id);
                    return $this->success('修改成功');
                } else {
                    return $this->error('修改失败');
                }
            } catch (\Exception $e) {
                return $this->error('网络错误');
            }
        }
    }


    public function market()
    {
        $id = (int)$this->request->param('id');
        if ($this->request->isPost()) {
            $param = $this->request->param([
                'id' => '',
                'is_mark' => '',
            ]);

            if (!$this->repository->exists($id)) {
                return $this->error('数据不存在');
            }
            try {
                $res = $this->repository->update($id, $param);
                if ($param['is_mark'] == 2) {
                    $push['id'] = $param['id'];
                    $push['company_id'] = $this->request->companyId;
                    ##后台在关闭某个卡牌的市场寄售时， 场中的寄售挂单需要自动取消寄售，自动下架。
                    $isPushed = \think\facade\Queue::push(\app\jobs\EndPoolMarkJob::class, $push);
                }
                admin_log(3, '修改卡牌市场开关状态 id:' . $id, $param);
                if ($res) {
                    Cache::store('redis')->delete('goods_' . $id);
                    return $this->success('修改成功');
                } else {
                    return $this->error('修改失败');
                }
            } catch (\Exception $e) {
                return $this->error('网络错误');
            }
        }
    }


    public function give()
    {
        $id = (int)$this->request->param('id');
        if ($this->request->isPost()) {
            $param = $this->request->param([
                'id' => '',
                'is_give' => '',
            ]);

            if (!$this->repository->exists($id)) {
                return $this->error('数据不存在');
            }
            try {
                $res = $this->repository->update($id, $param);
                admin_log(3, '修改卡牌转赠开关状态 id:' . $id, $param);
                if ($res) {
                    Cache::store('redis')->delete('goods_' . $id);
                    return $this->success('修改成功');
                } else {
                    return $this->error('修改失败');
                }
            } catch (\Exception $e) {
                return $this->error('网络错误');
            }
        }
    }


    public function hot()
    {
        $id = (int)$this->request->param('id');
        if ($this->request->isPost()) {
            $param = $this->request->param([
                'id' => '',
                'is_hot' => '',
            ]);

            if (!$this->repository->exists($id)) {
                return $this->error('数据不存在');
            }
            try {
                $res = $this->repository->update($id, $param);
                if ($res) {
                    Cache::store('redis')->delete('goods_' . $id);
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
     * 删除
     */
    public function del()
    {
        $ids = (array)$this->request->param('ids');
        try {
            $data = $this->repository->batchDelete($ids);
            if ($data) {
                admin_log(4, '删除卡牌 ids:' . implode(',', $ids), $data);
                return $this->success('删除成功');
            } else {
                return $this->error('删除失败');
            }
        } catch (\Exception $e) {
            return $this->error('网络失败');
        }
    }

}