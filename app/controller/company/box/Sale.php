<?php

namespace app\controller\company\box;

use think\App;
use think\facade\Cache;
use app\controller\company\Base;
use app\validate\box\SaleValidate;
use app\common\repositories\box\BoxSaleRepository;
use app\common\repositories\union\UnionAlbumRepository;
use app\common\repositories\union\UnionBrandRepository;

class Sale extends Base
{
    protected $repository;

    public function __construct(App $app, BoxSaleRepository $repository)
    {
        parent::__construct($app);
        $this->repository = $repository;
    }
    protected function commonParams()
    {
    }

    public function list()
    {
        if ($this->request->isAjax()) {
            $where = $this->request->param([
                'keywords' => '',
            ]);
            [$page, $limit] = $this->getPage();
            $data = $this->repository->getList($where,$page, $limit,$this->request->companyId);
            return json()->data(['code' => 0, 'data' => $data['list'],'count' => $data['count'] ]);
        }

        return $this->fetch('box/sale/list', [
            'addAuth' => company_auth('companyBlindBoxSaleAdd'),
            'editAuth' => company_auth('companyBlindBoxSaleEdit'),
            'delAuth' => company_auth('companyBlindBoxSaleDel'),
            'switchStatusAuth' => company_auth('companyBlindBoxSaleSwitch'),##上架/下架
            'marketAuth' => company_auth('companyBlindBoxSaleMarketSwitch'),##市场开关
            'giveAuth' => company_auth('companyBlindBoxSaleGiveSwitch'),##转赠开关
            'checkAuth' => company_auth('companyBindBoxSaleCheckPrice'),##限价
            'goodsList' => company_auth('companyBlindBoxSaleGoodsList'),##
        ]);
    }

    /**
     * 添加广告
     */
    public function add()
    {
        $this->commonParams();
        if ($this->request->isPost()) {
            $param = $this->request->param([
                'title' => '',
                'num' => '',
                'get_type' => '',
                'limit_num' => '',
                'cover' => '',
                'content' => '',
                'price' => '',
                'status'=>'',
                'is_to'=>'',
                'to_time'=>null,
                'equity' => '',
            ]);
            validate(SaleValidate::class)->scene('add')->check($param);
            try {
                $res = $this->repository->addInfo($this->request->companyId,$param);
                if ($res) {
                    company_user_log(3, '添加盲盒', $param);
                    return $this->success('添加成功');
                } else {
                    return $this->error('添加失败');
                }
            } catch (\Exception $e) {
                return $this->error('网络错误');
            }
        } else {
            return $this->fetch('box/sale/add');
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
        $this->commonParams();
        $info = $this->repository->getDetail($id);
        if (!$info) {
            return $this->error('信息错误');
        }
        if ($this->request->isPost()) {
            $param = $this->request->param([
                'id' => '',
                'title' => '',
                'num' => '',
                'limit_num' => '',
                'cover' => '',
                'get_type' => '',
                'content' => '',
                'price' => '',
                'is_to'=>'',
                'to_time'=> '',
                'equity' => '',
            ]);
            validate(SaleValidate::class)->scene('edit')->check($param);
            try {

                $res = $this->repository->editInfo($info, $param);
                if ($res !== false) {
                    company_user_log(3, '编辑盲盒', $param);
                    return $this->success('修改成功');
                } else {
                    return $this->error('修改失败');
                }
            } catch (\Exception $e) {
                return $this->error($e->getMessage());
            }
        } else {
            return $this->fetch('box/sale/edit', [
                'info' => $info,
            ]);
        }
    }

    /**
     * 设置藏品状态
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
                company_user_log(3, '修改盲盒状态 id:' . $id, $param);
                if ($res) {
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
                company_user_log(3, '修改盲盒市场开关状态 id:' . $id, $param);
                if ($res) {
                    return $this->success('修改成功');
                } else {
                    return $this->error('修改失败');
                }
            } catch (\Exception $e) {
                return $this->error('网络错误');
            }
        }
    }


    public function checkPrice()
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
                'min_price' => '',
                'max_price' => '',
            ]);
            try {
                $res = $this->repository->checkPrice($info, $param);
                if ($res !== false) {
                    company_user_log(3, '编辑藏品限价', $param);
                    return $this->success('修改成功');
                } else {
                    return $this->error('修改失败');
                }
            } catch (\Exception $e) {
                return $this->error($e->getMessage());
            }
        } else {
            return $this->fetch('pool/sale/checkPrice', [
                'info' => $info,
            ]);
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
                company_user_log(3, '修改盲盒转赠开关状态 id:' . $id, $param);
                if ($res) {
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
                company_user_log(4, '删除盲盒 ids:' . implode(',', $ids), $data);
                return $this->success('删除成功');
            } else {
                return $this->error('删除失败');
            }
        } catch (\Exception $e) {
            return $this->error('网络失败');
        }
    }

}