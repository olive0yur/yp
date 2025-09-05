<?php

namespace app\controller\api\union;

use think\App;
use think\facade\Cache;
use app\controller\api\Base;
use app\common\repositories\box\BoxSaleRepository;
use app\common\repositories\pool\PoolSaleRepository;
use app\common\repositories\union\UnionBrandRepository;

class UnionBrand extends Base
{
    public $repository;
    public function __construct(App $app,UnionBrandRepository $repository)
    {
        $this->repository=$repository;
        parent::__construct($app);
    }

    public function brandList(){
        $where = $this->request->param([
            'is_type' => 1,
        ]);
        [$page, $limit] = $this->getPage();
        return $this->success($this->repository->getApiList($where, $page, $limit, $this->request->companyId));
    }

    public function brandDetails(){
        $data = $this->request->param(['brand_id'=>'']);
        if(!$data['brand_id']) return $this->error('ID不能为空');
        return $this->success($this->repository->getApiDetail($data['brand_id']));
    }

    public function brandPoolList(PoolSaleRepository $repository){
        $where = $this->request->param([
            'brand_id' => '',
        ]);
        if(!$where['brand_id']) return $this->error('品牌ID不能为空');
        [$page, $limit] = $this->getPage();
        return $this->success($repository->getBrandPoolList($where, $page, $limit,$this->request->companyId));
    }

    public function brandBoxList(BoxSaleRepository $repository){
        $where = $this->request->param([
            'brand_id' => '',
        ]);
        if(!$where['brand_id']) return $this->error('品牌ID不能为空');
        [$page, $limit] = $this->getPage();
        return $this->success($repository->getApiBrandBoxList($where, $page, $limit,$this->request->companyId));
    }
}