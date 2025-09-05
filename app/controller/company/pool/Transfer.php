<?php

namespace app\controller\company\pool;

use app\common\repositories\pool\PoolTransferLogRepository;
use app\common\repositories\tichain\TichainPoolRepository;
use think\App;
use think\exception\ValidateException;
use think\facade\Cache;
use app\controller\company\Base;
use app\validate\pool\SaleValidate;
use app\common\repositories\users\UsersRepository;
use app\common\repositories\pool\PoolModeRepository;
use app\common\repositories\cochain\EbaoPoolRepository;
use app\common\repositories\union\UnionAlbumRepository;
use app\common\repositories\union\UnionBrandRepository;

class Transfer extends Base
{
    protected $repository;

    public function __construct(App $app, PoolTransferLogRepository $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
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
        return $this->fetch('pool/transfer/list', [
            'delAuth' => company_auth('companyPoolTransferDel'),
        ]);
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
            return $this->error($e->getMessage());
        }
    }

}