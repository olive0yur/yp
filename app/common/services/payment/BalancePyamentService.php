<?php

namespace app\common\services\payment;

use app\common\repositories\pool\PoolSaleRepository;
use app\common\repositories\pool\PoolShopOrder;
use app\common\repositories\users\UsersBoxRepository;
use app\common\repositories\users\UsersRepository;
use app\common\services\PaymentService;
use think\exception\ValidateException;

/**
 * 账号余额支付参数处理
 * Class BalancePyamentService
 * @package app\data\service\payment
 */
class BalancePyamentService extends PaymentService
{
    /**
     * 订单信息查询
     * @param string $orderNo
     * @return array
     */
    public function query(string $orderNo): array
    {
        return [];
    }

    /**
     * 支付通知处理
     * @return string
     */
    public function notify(): string
    {
        return 'SUCCESS';
    }

    /**
     * 创建订单支付参数
     * @param string $openid 用户OPENID
     * @param string $orderNo 交易订单单号
     * @param string $payAmount 交易订单金额（元）
     * @param string $payTitle 交易订单名称
     * @param string $payRemark 订单订单描述
     * @param string $payReturn 完成回跳地址
     * @param string $payImage 支付凭证图片
     * @return array
     */
    public function create(string $openid, string $orderNo, string $payAmount, string $payTitle, string $payRemark, string $payReturn = '', string $payImage = '', int $companyId = null): array
    {

        /** @var PoolShopOrder $shopOrder */
        $shopOrder = $this->app->make(PoolShopOrder::class);
        $order = $shopOrder->search(['order_id' => $orderNo])->find();
        if ($order['status'] !== 1) throw new ValidateException("不可发起支付");

        // 检查能否支付
        /** @var UsersRepository $userRepository */
        $userRepository = $this->app->make(UsersRepository::class);
        $old = $userRepository->getDetail($order['uuid'])['food'];
        if ($payAmount > $old) throw new ValidateException("可抵扣余额不足");
        try {
            // 扣减用户余额
            $this->app->db->transaction(function () use ($order, $payAmount, $shopOrder, $userRepository, $companyId, $payTitle) {
                // 判断扣除类型
                $poolInfo = app()->make(PoolSaleRepository::class)->search([], $companyId)->where('id', $order['goods_id'])->find();
                if (!$poolInfo) {
                    throw new ValidateException("商品不存在");
                }
                if ($poolInfo['type'] == 1) {
                    // 扣除余额金额
                    $userRepository->batchFoodChange($order['uuid'], 3, '-' . $payAmount, ['remark' => $payTitle, 'company_id' => $companyId], 4, $companyId);
                } else {
                    // 扣除余额金额
                    $userRepository->batchGoldChange($order['uuid'], 3, '-' . $payAmount, ['remark' => $payTitle, 'company_id' => $companyId], 4, $companyId);
                }
                // 更新订单余额
                switch ($order['buy_type']) {
                    case 1:
                        $shopOrder->editInfo($order, ['pay_type' => 'food', 'status' => 2, 'pay_time' => date('Y-m-d H:i:s')]);
                        //如果有奖励的能量值，则发放
                        if ($poolInfo && $poolInfo['price_tag'] > 0) {
                            // 发放代币奖励
                            $userRepository->batchFoodChange($order['uuid'], 4, $poolInfo['price_tag'], ['remark' => '购买商城商品', 'company_id' => $companyId], 4, $companyId);
                        }
                        break;
                    case 2:
                        $shopOrder->editInfo($order, ['pay_type' => 'food', 'status' => 5, 'pay_time' => date('Y-m-d H:i:s')]);
                        break;
                }
                if ($order['buy_type'] == 2) {
                    $data = [];
                    $data['uuid'] = $order['uuid'];
                    $data['add_time'] = date('Y-m-d H:i:s');
                    $data['order_id'] = $order['order_id'];
                    $data['box_id'] = $order['goods_id'];
                    $data['price'] = $order['price'];
                    $data['type'] = 1;
                    $userBox = app()->make(UsersBoxRepository::class);
                    $count = $userBox->getCount(['order_id' => $data['order_id']], $companyId);
                    if ($count >= $order['num']) {
                        return ['code' => 1, 'info' => '支付完成'];
                    } else {
                        for ($i = 1; $i <=  $order['num']; $i++) {
                            $userBox->addInfo($companyId, $data);
                        }
                    }
                }
            });
            return ['code' => 1, 'info' => '支付完成'];
        } catch (\Exception $exception) {
            exception_log('支付处理失败', $exception);
            return ['code' => 0, 'info' => $exception->getMessage()];
        }
    }
}
