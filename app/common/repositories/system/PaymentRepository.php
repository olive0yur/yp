<?php

namespace app\common\repositories\system;

use app\common\dao\system\PaymentDao;
use app\common\repositories\BaseRepository;
use app\common\repositories\box\BoxSaleRepository;
use app\common\repositories\pool\PoolSaleRepository;
use app\common\repositories\pool\PoolShopOrder;
use app\common\repositories\system\upload\UploadFileRepository;
use app\common\repositories\users\UsersBoxRepository;
use app\common\repositories\users\UsersPoolRepository;
use app\common\repositories\users\UsersRepository;
use app\common\services\PaymentService;
use think\exception\ValidateException;
use think\facade\Cache;

/**
 *
 * @package app\common\repositories\system
 * @mixin PaymentDao
 */
class PaymentRepository extends BaseRepository {
    public $usersRepository;

    public function __construct(PaymentDao $dao, UsersRepository $usersRepository) {
        $this->dao = $dao;
        $this->usersRepository = $usersRepository;
    }

    public function getList(array $where, $page, $limit, $companyId = null) {
        $query = $this->dao->search($where, $companyId);
        $count = $query->count();
        $query->with(['cover' => function ($query) {
            $query->bind(['cover' => 'show_src']);
        }]);
        $list = $query->page($page, $limit)
            ->select();
        return compact('list', 'count');
    }


    /**
     * 添加
     */
    public function addInfo($companyId, $data) {
        if (isset($data['content']) && is_array($data['content'])) {
            $data['content'] = json_encode($data['content'], JSON_UNESCAPED_UNICODE);
        }
        if ($data['cover']) {
            /** @var UploadFileRepository $uploadFileRepository */
            $uploadFileRepository = app()->make(UploadFileRepository::class);
            $fileInfo = $uploadFileRepository->getFileData($data['cover'], 1, 0);
            if ($fileInfo['id'] > 0) {
                $data['file_id'] = $fileInfo['id'];
            }
        }
        unset($data['cover']);
        $data['company_id'] = $companyId;
        return $this->dao->create($data);
    }

    /**
     * 编辑
     */
    public function editInfo($info, $data) {
        if (isset($data['content']) && is_array($data['content'])) {
            $data['content'] = json_encode($data['content'], JSON_UNESCAPED_UNICODE);
        }
        if ($data['cover']) {
            /** @var UploadFileRepository $uploadFileRepository */
            $uploadFileRepository = app()->make(UploadFileRepository::class);
            $fileInfo = $uploadFileRepository->getFileData($data['cover'], 1, 0);
            if ($fileInfo['id'] != $info['id']) {
                $data['file_id'] = $fileInfo['id'];
            }
        }
        unset($data['cover']);
        return $this->dao->update($info['id'], $data);
    }


    public function getDetail(int $id) {
        $with = [
            'cover' => function ($query) {
                $query->field('id,show_src');
                $query->bind(['cover' => 'show_src']);
            },
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
    public function batchDelete(array $ids) {
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


    public function getApiList(array $where = [], $companyId = null) {
        $query = $this->dao->search($where, $companyId);
        $count = $query->count();
        $query->field('id,type,name,file_id')->order('sort desc');
        $query->with(['cover' => function ($query) {
            $query->field('id,show_src,width,height');
        }]);
        $list = $query->select();
        return compact('list', 'count');
    }

    public function payment($data, $user, $companyId = null) {

        $orderRepostory = app()->make(PoolShopOrder::class);
        $data['uuid'] = $user['id'];
        $data['status'] = 1;

        $order = $orderRepostory->search(['order_id'=>$data['order_id'],'uuid'=>$user['id'],'status'=>1], $companyId)->with(['goods'=>function($query){
            $query->field('id,title');
        }])->find();
        if (!$order) throw new ValidateException('暂无待支付订单');
        if ($data['type'] == 'balance') {
            $auth = $this->usersRepository->passwordVerify($data['password'], $user['pay_password']);
            if (!$auth) throw new ValidateException('交易密码错误');
        }

        $payReturn = '';
        if ($data['type'] == 'alipay_wap') {
            if (!$data['payReturn']) throw new ValidateException('支付回跳地址不能为空');
            $payReturn = $data['payReturn'];
        }

        $productName = '购买';
        $productName .= ($order['goods']['title']) ?? '';
        $param = PaymentService::instance($data['type'], $companyId)->create('', $order['order_id'], $order['money'], $productName, '', $payReturn, '', $companyId);
        return $param;
    }


}