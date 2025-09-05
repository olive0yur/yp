<?php

namespace app\controller\company\pool;

use app\common\repositories\pool\PoolOrderNoRepository;
use app\common\repositories\pool\PoolSaleRepository;
use app\controller\company\Base;
use think\App;
use think\facade\Cache;

class OrderNo extends Base
{
    protected $repository;

    public function __construct(App $app, PoolOrderNoRepository $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
    }


    public function list()
    {
        $param = $this->request->param('id');
        if($param && $this->request->isGet()){
            /** @var PoolSaleRepository $PoolSaleRepository **/
            $PoolSaleRepository = app()->make(PoolSaleRepository::class);
            $info = $PoolSaleRepository->getDetail($param);
            if($info['is_number'] == 2){
                $this->repository->addInfo($this->request->companyId,$info);
            }
        }

        if ($this->request->isAjax()) {
            $where = $this->request->param([
                'pool_id'=>'',
                'status'=>'',
                'no' => ''
            ]);
            [$page, $limit] = $this->getPage();
            $data = $this->repository->getList($where,$page, $limit,$this->request->companyId);
            return json()->data(['code' => 0, 'data' => $data['list'],'count' => $data['count'] ]);
        }
        return $this->fetch('pool/orderNo/list',[
            'id'=>$param,
            'destroyAuth' => company_auth('companyPoolSaleNoDestroy'),
            'recoveryyAuth' => company_auth('companyPoolSaleNorecovery'),
        ]);
    }


    public function destroy()
    {
        $ids = (array)$this->request->param('ids');
        try {
            $data = $this->repository->batchDestroy($ids);
            if ($data) {
                company_user_log(4, '销毁卡牌 ids:' . implode(',', $ids), $data);
                return $this->success('销毁成功');
            } else {
                return $this->error('销毁失败');
            }
        } catch (\Exception $e) {
            return $this->error('网络失败');
        }
    }


    public function recovery()
    {
        $ids = (array)$this->request->param('ids');
//        try {
            $data = $this->repository->ballRrecovery($ids);

            if ($data === true) {
                company_user_log(4, '回收卡牌 ids:' . implode(',', $ids), $data);
                return $this->success('回收成功');
            } else {
                return $this->error($data);
            }
//        } catch (\Exception $e) {
//            return $this->error('网络失败');
//        }
    }
}