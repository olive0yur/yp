<?php

namespace app\common\repositories\refund;

use app\common\dao\refund\RefundOrderDao;
use app\common\repositories\BaseRepository;
use app\common\repositories\merchant\MerchantRepository;
use app\common\repositories\order\OrderRepository;
use app\common\repositories\product\ProductRepository;
use app\common\repositories\system\FinancialRecordRepository;
use app\common\repositories\system\PayLogRepository;
use app\common\repositories\traits\MerCommonRepository;
use think\exception\ValidateException;
use think\facade\Db;

class RefundOrderRepository extends BaseRepository
{
    use MerCommonRepository;

    public function __construct(RefundOrderDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 商家订单退款列表
     */
    public function getList(int $companyId = null, int $merId = null, array $where, $page, $limit)
    {
        $status = $where['status'];
        unset($where['status']);
        $where['mer_id'] = $merId;
        $query = $this->dao->search($where, $companyId)->where($this->getOrderType($status))
            ->with([
                'orderInfo' => function ($query) {
                    $query->field('id, order_sn, username');
                    $query->bind(['order_sn', 'username']);
                }
            ]);

        $count = $query->count();
        $list = $query->page($page, $limit)
            ->hidden(['orderInfo'])
            ->field('id,add_time,status,delivery_id,user_id,refund_type,order_id,refund_price,phone,refund_order_sn,is_del')
            ->select();

        return compact('count', 'list');
    }

    /**
     * @param $status
     * @return mixed
     */
    public function getOrderType($status)
    {
        $param['is_del'] = 0;
        switch ($status) {
            case 1:
                $param['status'] = 0;
                break;    // 待审核
            case 2:
                $param['status'] = -1;
                break;  // 审核未通过
            case 3:
                $param['status'] = 3;
                break;  // 已退款
            case 4:
                $param['status'] = 2;
                break;  // 待收货
            case 5:
                $param['status'] = 1;
                break;  // 待收货
            case 6:
                $param['is_del'] = 1;
                break;  // 已删除
            default:
                unset($param['is_del']);
                break;  //全部
        }
        return $param;
    }

    /**
     * 状态统计信息
     *
     * @param int $companyId
     * @param int|null $merId
     * @return int[]
     */
    public function getStatusCount(int $companyId, ?int $merId = null, ?array $where = [])
    {
        $sysDel = $merId ? 0 : null;
        if ($merId) {
            $where['mer_id'] = $merId;
        }
        $statusCount = [0, 1, 2, 3, 4, 5, 6];
        foreach ($statusCount as $k => $v) {
            $statusCount[$k] = $this->dao->search($where, $companyId, $sysDel)->where($this->getOrderType($v))->count();
        }
        return $statusCount;
    }

    public function userDelExists(int $id, ?int $merId)
    {
        $where = ['id' => $id, 'is_del' => 1];
        if ($merId) $where['mer_id'] = $merId;
        return $this->dao->getWhereCount($where) > 0;
    }

    /**
     * 商家删除退款订单
     *
     * @param $id
     */
    public function merDelete($id)
    {
        Db::transaction(function () use ($id) {
            $data['is_system_del'] = 1;
            $this->dao->update($id, $data);
        });
    }

    /**
     * TODO 退款单同意退款退货
     * @param $id
     * @param $admin
     */
    public function adminRefund($id, $admin)
    {
        return Db::transaction(function () use ($admin, $id) {
            $data['status'] = 3;
            $data['status_time'] = date('Y-m-d H:i:s');
            /** @var RefundStatusRepository $refundStatusRepository */
            $refundStatusRepository = app()->make(RefundStatusRepository::class);
            $refundStatusRepository->addLog($id, 'refund_price', '退款成功' . ($admin ? '' : '[自动]'));

            $this->dao->update($id, $data);
            $res = $this->dao->getWhere(['id' => $id], '*', ['refundProduct.product']);
            $this->getProductRefundNumber($res, 1, true);
            $refund = $this->doRefundPrice($id, 0);
            if ($refund) $this->refundAfter($refund);

            return true;
        });
    }

    /**
     * 退款订单详情
     * 
     */
    public function getRefundDetail($id)
    {
        return $this->dao->search([])->where('id', $id)
            ->with([
                'refundStatus',
                // 'user' => function ($query) {
                //     $query->field('id,real_name,nickname');
                // },
                'orderInfo' => function ($query) {
                    $query->with(['orderProduct' => function ($query) {
                        $query->field('id,order_id, product_name');
                        $query->bind(['product_name']);
                    }]);
                },
                'refundProduct' => function ($query) {
                    $query->with(['product' => function ($query) {
                        $query->field('id,order_id, product_name');
                    }]);
                }
            ])->find();
    }

    public function audit($id, $data)
    {
        return Db::transaction(function () use ($id, $data) {
            $info = $this->dao->get($id);
            if ($data['status'] == 1) { // 同意退款
                $this->getProductRefundNumber($info, 1);
                if ($info['refund_type'] == 2) { // 退货
                    $this->dao->update($id, [
                        'status' => 1,
                        'mer_delivery_address' => $data['mer_delivery_address'],
                        'mer_delivery_user' => $data['mer_delivery_user'],
                        'delivery_phone' => $data['delivery_phone'],
                        'status_time' => date('Y-m-d H:i:s')
                    ]);

                    /** @var RefundStatusRepository $refundStatusRepository */
                    $refundStatusRepository = app()->make(RefundStatusRepository::class);
                    return $refundStatusRepository->addLog($id, 'refund_agree', '退款申请已通过，请将商品寄回');

                } elseif ($info['refund_type'] == 1) {
                    //已退款金额
                    $_refund_price = $this->checkRefundPrice($id);
                    $refund = $this->doRefundPrice($id, $_refund_price);

                    $this->dao->update($id, [
                        'status' => 3,
                        'status_time' => date('Y-m-d H:i:s')
                    ]);
                    $this->refundAfter($refund);

                    /** @var RefundStatusRepository $refundStatusRepository */
                    $refundStatusRepository = app()->make(RefundStatusRepository::class);
                    return $refundStatusRepository->addLog($id, 'refund_price', '退款成功');
                }
            } else { // 拒绝退款
                $this->dao->update($id, [
                    'fail_message' => $data['fail_message'],
                    'status' => $data['status'],
                    'status_time' => date('Y-m-d H:i:s')
                ]);
                $this->getProductRefundNumber($info, -1);

                /** @var RefundStatusRepository $refundStatusRepository */
                $refundStatusRepository = app()->make(RefundStatusRepository::class);
                return $refundStatusRepository->addLog($id, 'refund_refuse', '订单退款已拒绝');
            }
        });
    }

    /**
     * 退款后操作
     *
     * @param $refundOrder
     */
    public function refundAfter($refundOrder)
    {
        //返还库存
        $refundOrder->append(['refundProduct.product']);
        /** @var ProductRepository $productRepository */
        $productRepository = app()->make(ProductRepository::class);
        if ($refundOrder['refund_type'] == 2 || $refundOrder->orderInfo->status == 0 || $refundOrder->orderInfo->status == 9) {
            foreach ($refundOrder->refundProduct as $item) {
                $productRepository->orderProductIncStock($refundOrder->orderInfo, $item->product, $item->refund_num);
            }
        }
        /** @var OrderRepository $orderRepository */
        $orderRepository = app()->make(OrderRepository::class);
        $refundAll = $orderRepository->checkRefundStatusById($refundOrder['order_id'], $refundOrder['id']);

        /** @var FinancialRecordRepository $financialRecordRepository */
        $financialRecordRepository = app()->make(FinancialRecordRepository::class);

        $finance[] = [
            'company_id' => $refundOrder->company_id,
            'order_id' => $refundOrder->id,
            'order_sn' => $refundOrder->refund_order_sn,
            'user_info' => $refundOrder->user->nickname ?? '',
            'user_id' => $refundOrder->user_id,
            'financial_type' => 'refund_order',
            'financial_pm' => 0,
            'type' => 1,
            'amount' => $refundOrder->refund_price,
            'mer_id' => $refundOrder->mer_id,
            'financial_record_sn' => $financialRecordRepository->getSn()
        ];
        $financialRecordRepository->insertAll($finance);
    }

    public function getProductRefundNumber($res, $status, $after = false)
    {
        /**
         * 1.同意退款
         *   1.1 仅退款
         *      1.1.1 是 , 如果退款数量 等于 购买数量 is_refund = 3 全退退款 不等于 is_refund = 2 部分退款
         *      1.1.2 否, is_refund = 1 退款中
         *   1.2 退款退货 is_refund = 1
         *
         * 2. 拒绝退款
         *   2.1 如果退款数量 等于 购买数量 返还可退款数 is_refund = 0
         *   2.2 商品总数小于可退数量 返还可退数 以商品数为准
         *   2.3 是否存在其他图款单,是 ,退款中 ,否, 部分退款
         */
        $refundId = $this->getRefundCount($res->order_id, $res['refund_order_id']);
        /** @var RefundProductRepository $make */
        $make = app()->make(RefundProductRepository::class);
        foreach ($res['refundProduct'] as $item) {
            $is_refund = $item->product->is_refund;
            if ($status == 1) { //同意
                if ($after) {
                    $is_refund = ($item->refund_num == $item->product->product_num) ? 3 : 2;
                } else {
                    if ($res['refund_type'] == 1) {
                        $is_refund = ($item->refund_num == $item->product->product_num) ? 3 : 2;
                    }
                }
            } else {  //拒绝
                $refund_num = $item->refund_num + $item->product->refund_num; //返还可退款数
                if ($item->product->product_num == $refund_num) $is_refund = 0;
                if ($item->product->product_num < $refund_num) $refund_num = $item->product->product_num;
                $item->product->refund_num = $refund_num;
            }
            if (!empty($refundId)) {
                $has = $make->getWhereCount([['refund_order_id', 'in', $refundId], ['order_product_id', '=', $item->product->order_product_id]]);
                if ($has) $is_refund = 1;
            }
            $item->product->is_refund = $is_refund;
            $item->product->save();
        }
        return true;
    }

    /**
     * 获取订单存在的未处理完成的退款单
     * @param int $orderId
     * @param int|null $refundOrderId
     * @return array
     */
    public function getRefundCount(int $orderId, ?int $refundOrderId)
    {
        $where = [
            'type' => 1,
            'order_id' => $orderId,
        ];
        return $this->dao->search($where)->when($refundOrderId, function ($query) use ($refundOrderId) {
            $query->where('id', '<>', $refundOrderId);
        })->column('id');
    }

    /**
     *  退款金额是否超过可退金额
     * @param int $refundId
     * @return bool
     */
    public function checkRefundPrice(int $refundId)
    {
        $refund = $this->get($refundId);
        $order = app()->make(OrderRepository::class)->get($refund['order_id']);
        $pay_price = $order['pay_price'];

        //已退金额
        $refund_price = $this->dao->refundPirceByOrder([$refund['order_id']]);

        if (bccomp(bcsub($pay_price, $refund_price, 2), $refund['refund_price'], 2) == -1)
            throw new ValidateException('退款金额超出订单可退金额');

        return $refund_price;
    }

    /**
     * 退款操作
     *
     * @param $id
     * @param $refundPrice
     * @return array|\think\Model|void|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function doRefundPrice($id, $refundPrice)
    {
        $res = $this->dao->getWhere(['id' => $id], "*", ['orderInfo']);
        if (!$res->orderInfo) {
            $res->fail_message = '订单信息不全';
            $res->sataus = -1;
            $res->save();
            return;
        }
        if ($res->refund_price <= 0) return $res;

        $data[] = [
            'pay_type' => $res->orderInfo->pay_type,
            'id' => $res->orderInfo->id,
            'sn' => $res->orderInfo->groupOrder->group_order_sn,
            'data' => [
                'refund_id' => $res->refund_order_sn,
                'pay_price' => $res->orderInfo->groupOrder->pay_price,
                'refund_price' => $res->refund_price
            ]
        ];
        $refundPriceAll = 0;
        $refundRate = 0;
        $totalExtension = bcadd($res['extension_one'], $res['extension_two'], 2);
        $_extension = 0;
        $i = count($data);
        foreach ($data as $datum => $item) {
            if ($item['data']['pay_price'] > 0 && $item['data']['refund_price'] > 0) {
                //0余额 1微信 2小程序
                $refundPrice = $this->getRefundMerPrice($res, $item['data']['refund_price']);

                if ($res->orderInfo->commission_rate > 0) {
                    $commission_rate = ($res->orderInfo->commission_rate / 100);

                    if ($datum == ($i - 1)) {
                        $extension = bcsub($totalExtension, $_extension, 2);
                    } else {
                        $extension = bcmul(bcdiv($item['data']['refund_price'], $res->refund_price, 2), $totalExtension, 2);
                        $_extension = bcadd($_extension, $extension, 2);
                    }
                    $_refundRate = bcmul($commission_rate, bcsub($item['data']['refund_price'], $extension, 2), 2);
                    $refundRate = bcadd($refundRate, $_refundRate, 2);
                }
                $refundPriceAll = bcadd($refundPriceAll, $refundPrice, 2);

                try {
                    if ($item['pay_type'] == 'balance') {
                        true;
                        // /** @var UserRepository $userRepository */
                        // $userRepository = app()->make(UserRepository::class);
                        // $userRepository->balanceChange($res->user_id, 101, $item['data']['refund_price'], [
                        //     'note' => '订单退款',
                        //     'link_id' => $id
                        // ]);
                    } else {
                        if ($res->orderInfo->pay_log_id > 0) {
                            /** @var PayLogRepository $payLogRepository */
                            $payLogRepository = app()->make(PayLogRepository::class);
                            $payLogRepository->refundById($res->orderInfo->pay_log_id, '订单退款', $item['data']['refund_price']);
                        }
                    }

                    // 确认收货和已完成订单退款扣除商家余额
                    if (in_array($res->orderInfo->status, [3, 4])) {
                        /** @var MerchantRepository $merchantRepository */
                        $merchantRepository = app()->make(MerchantRepository::class);
                        $merchantRepository->balanceChange($res->mer_id, 2, $refundPrice, [
                            'note' => '订单退款',
                            'link_id' => $id
                        ]);
                    }
                } catch (\Exception $e) {
                    throw new ValidateException($e->getMessage());
                }
            }
        }

        /** @var FinancialRecordRepository $financialRecordRepository */
        $financialRecordRepository = app()->make(FinancialRecordRepository::class);

        $finance[] = [
            'company_id' => $res->company_id,
            'order_id' => $res->id,
            'order_sn' => $res->refund_order_sn,
            'user_info' => $res->user->nickname ?? '',
            'user_id' => $res->user_id,
            'financial_type' => 'refund_true',
            'financial_pm' => 1,
            'type' => 1,
            'amount' => $refundPriceAll,
            'mer_id' => $res->mer_id,
            'financial_record_sn' => $financialRecordRepository->getSn()
        ];
        $finance[] = [
            'company_id' => $res->company_id,
            'order_id' => $res->id,
            'order_sn' => $res->refund_order_sn,
            'user_info' => $res->user->nickname ?? '',
            'user_id' => $res->user_id,
            'financial_type' => 'refund_charge',
            'financial_pm' => 1,
            'type' => 1,
            'amount' => $refundRate,
            'mer_id' => $res->mer_id,
            'financial_record_sn' => $financialRecordRepository->getSn()
        ];
        $financialRecordRepository->insertAll($finance);
        return $res;
    }

    public function getRefundMerPrice($refundOrder, $refundPrice = null)
    {
        if ($refundPrice === null) {
            $refundPrice = $refundOrder->refund_price;
            $extension_one = $refundOrder['extension_one'];
            $extension_two = $refundOrder['extension_two'];
        } else {
            $rate = bcdiv($refundPrice, $refundOrder->refund_price, 3);
            $extension_one = $refundOrder['extension_one'] > 0 ? bcmul($rate, $refundOrder['extension_one'], 2) : 0;
            $extension_two = $refundOrder['extension_two'] > 0 ? bcmul($rate, $refundOrder['extension_two'], 2) : 0;
        }
        $extension = bcadd($extension_one, $extension_two, 3);
        $commission_rate = ($refundOrder->orderInfo->commission_rate / 100);
        $_refundRate = 0;
        if ($refundOrder->orderInfo->commission_rate > 0) {
            $_refundRate = bcmul($commission_rate, bcsub($refundPrice, $extension, 2), 2);
        }
        return bcsub(bcsub($refundPrice, $extension, 2), $_refundRate, 2);
    }

}