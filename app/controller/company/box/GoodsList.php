<?php

namespace app\controller\company\box;

use app\common\repositories\box\BoxSaleGoodsListRepository;
use app\common\repositories\pool\PoolSaleRepository;
use app\common\repositories\product\ProductRepository;
use app\controller\company\Base;
use app\validate\pool\SaleValidate;
use think\App;
use think\facade\Cache;

class GoodsList extends Base
{
    protected $repository;

    public function __construct(App $app, BoxSaleGoodsListRepository $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
    }

    public function list()
    {
        if ($this->request->isAjax()) {
            $where = $this->request->param([
                'keywords' => '',
                'box_id'=>''
            ]);
            [$page, $limit] = $this->getPage();
            $data = $this->repository->getList($where,$page, $limit,$this->request->companyId);
            return json()->data(['code' => 0, 'data' => $data['list'],'count' => $data['count'] ]);
        }

        return $this->fetch('box/goodsList/list', [
            'box_id' => $this->request->get('box_id'),
            'addAuth' => company_auth('companyBlindBoxSaleGoodsListAdd'),
            'editAuth' => company_auth('companyBlindBoxSaleGoodsListEdit'),
            'delAuth' => company_auth('companyBlindBoxSaleGoodsListDel'),
            'people' => company_auth('companyFraudList'),
        ]);
    }

    public function commonParams(){
        $box_id = $this->request->get('box_id');
        $poolList =  $this->app->make(PoolSaleRepository::class)->search(['is_number'=>1,'virtual'=>2],$this->request->companyId)->field('id,title')->select();
        $productList =   $this->app->make(PoolSaleRepository::class)->search(['virtual'=>1],$this->request->companyId)->field('id,title')->select();
        $this->assign(['poolList'=>$poolList,'productList'=>$productList,'box_id'=>$box_id]);
    }

    /**
     * 添加广告
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $param = $this->request->param([
                'goods_type' => '',
                'goods_id' => '',
                'probability' => '',
                'num' => '',
                'box_id' => '',
                'version'=> '',
                'cover'=>'',
            ]);
            if(!$param['goods_type']) return $this->error('请选择商品类型!');
            if(!$param['goods_id']) return $this->error('请选择商品!');
            if(!$param['probability']) return $this->error('请输入中奖几率');
            if(!$param['num']) return $this->error('请输入数量');
            switch ($param['goods_type']){
                case 1:
                    $repository = $this->app->make(PoolSaleRepository::class);
                    $stock = $repository->search([],$this->request->companyId)->where('id',$param['goods_id'])->value('stock');
                    if($stock < $param['num']) return  $this->error('库存不足!');
                    break;
                case 2:
                    $repository = $this->app->make(PoolSaleRepository::class);
                    $stock = $repository->search([],$this->request->companyId)->where(['id'=>$param['goods_id']])->value('stock');
                    if($stock < $param['num']) return $this->error('库存不足!');
                    break;
            }
            try {
                $res = $this->repository->addInfo($this->request->companyId,$param);
                if ($res) {
                    admin_log(3, '添加盲盒商品', $param);
                    return $this->success('添加成功');
                } else {
                    return $this->error('添加失败');
                }
            } catch (\Exception $e) {
                return $this->error($e->getMessage());
            }
        } else {
            $this->commonParams();
            return $this->fetch('box/goodsList/add',[
                'tokens' =>  web_config($this->request->companyId, 'site')['tokens']
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
                'goods_type' => '',
                'goods_id' => '',
                'probability' => '',
                'num' => '',
                'box_id' => '',
                'version'=> '',
                'cover'=>'',
            ]);
            if(!$param['goods_type']) return $this->error('请选择商品类型!');
            if(!$param['goods_id']) return $this->error('请选择商品!');
            if(!$param['probability']) return $this->error('请输入中奖几率');
            if(!$param['num']) return $this->error('请输入数量');
            switch ($param['goods_type']){
                case 1:
                    $repository = $this->app->make(PoolSaleRepository::class);
                    $stock = $repository->search([],$this->request->companyId)->where('id',$param['goods_id'])->value('stock');
                    if($stock < $param['num']) return  $this->error('库存不足!');
                    break;
                case 2:
                    $repository = $this->app->make(PoolSaleRepository::class);
                    $stock = $repository->search([],$this->request->companyId)->where(['id'=>$param['goods_id']])->value('stock');
                    if($stock < $param['num']) return $this->error('库存不足!');
                    break;
            }
            try {
                $res = $this->repository->editInfo($info, $param);
                if ($res !== false) {
                    admin_log(3, '编辑盲盒商品', $param);
                    Cache::store('redis')->delete('goods_'.$param['id']);
                    return $this->success('修改成功');
                } else {
                    return $this->error('修改失败');
                }
            } catch (\Exception $e) {
                return $this->error($e->getMessage());
            }
        } else {
            $this->commonParams();
            return $this->fetch('box/goodsList/add', [
                'info' => $info,
                'tokens' =>  web_config($this->request->companyId, 'site')['tokens']
            ]);
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
                admin_log(4, '删除盲盒商品 ids:' . implode(',', $ids), $data);
                return $this->success('删除成功');
            } else {
                return $this->error('删除失败');
            }
        } catch (\Exception $e) {
            return $this->error('网络失败');
        }
    }

}