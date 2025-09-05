<?php

namespace app\controller\company\pool;

use app\common\model\users\UsersPoolModel;
use app\common\repositories\mine\MineRepository;
use app\common\repositories\pool\PoolOrderNoRepository;
use app\common\repositories\pool\PoolSaleRepository;
use app\common\repositories\users\UsersPoolRepository;
use app\common\repositories\users\UsersRepository;
use app\controller\company\Base;
use app\validate\pool\SaleValidate;
use think\App;
use think\exception\ValidateException;
use think\facade\Cache;
use think\facade\Db;
use think\facade\Log;
use think\validate\ValidateRule;

class UserPool extends Base
{
    protected $repository;

    public function __construct(App $app, UsersPoolRepository $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
    }
    public function list()
    {
        if ($this->request->isAjax()) {
            $where = $this->request->param([
                'keywords' => '',
                'mobile'=>'',
                'title'=>'',
                'no'=>'',
                'status'=>'',
                'type'=>'',
            ]);
            [$page, $limit] = $this->getPage();
            $data = $this->repository->getList($where,$page, $limit,$this->request->companyId);
            return json()->data(['code' => 0, 'data' => $data['list'],'count' => $data['count'] ]);
        }
        return $this->fetch('pool/user/list', [
            'saleAuth' => company_auth('companyUserPoolSale'),##
            'importFileAuth' => company_auth('companyGiveUserPoolSaleBatch'),##
            'importBlackFileAuth' => company_auth('companyGiveBlackUserPoolSaleBatch'),##
            'importBlackFileDestroyAuth' => company_auth('companyGiveBlackUserPoolSaleDestroy'),##
            'autoMarkAddAuth' => company_auth('companyUserPoolSaleAutoMarkAddBatch'),##
            'autoMarkEndAuth' => company_auth('companyUserPoolSaleAutoMarkEndBatch'),##
        ]);
    }

    public function sale()
    {
        $ids = (array)$this->request->param('ids');
        $is_sale = $this->request->param('is_sale');
        try {
            $data = $this->repository->updates($ids,['is_sale'=>$is_sale]);
            if ($data) {
                admin_log(4, '禁止会员卡牌寄售 ids:' . implode(',', $ids), $data);
                return $this->success('操作成功');
            } else {
                return $this->error('操作失败');
            }
        } catch (\Exception $e) {
            return $this->error('网络失败');
        }
    }


    /**
     * 空投用户卡牌
     * @return string|\think\response\Json|\think\response\View
     * @throws \Exception
     */
    public function giveUserPool()
    {
        $id = (array)$this->request->param('id');
        if ($this->request->isPost()) {
            $param = $this->request->param([
                'pool_id' => '',
                'num' => '',
                'remark' => ''
            ]);
            if ($param['num'] <= 0) {
                return $this->error('空投数量必须大于0');
            }
            if($param['num'] > 500) return $this->error('单次空投数量为500');
            /** @var MineRepository $mineRepository */
            $mineRepository = app()->make(MineRepository::class);
            $mine = $mineRepository->search(['level'=>1,'status'=>1],$this->request->companyId)->find();
            if(!$mine || !isset(web_config($this->request->companyId, 'program')['output'])) throw new ValidateException('矿产参数错误');

            $poolSaleRepository = app()->make(PoolSaleRepository::class);
            $poolInfo = $poolSaleRepository->get($param['pool_id']);
            $totalNum = count($id) * $param['num'];
            if($poolInfo['stock'] < $totalNum) return $this->error('库存不足');
            $data = $this->repository->batchGiveUserPool($id,$param,$this->request->companyId);
            company_user_log(3, '批量空投闪卡 id:' . implode(',', $id), $param);
            return $this->success('投送成功');
        } else {
            /**
             * @var PoolSaleRepository $poolSaleRepository
             */
            $poolSaleRepository = app()->make(PoolSaleRepository::class);
            $poolData = $poolSaleRepository->getCascaderData($this->request->companyId);
            return $this->fetch('pool/user/give', [
                'poolData' => $poolData,
            ]);
        }
    }

}