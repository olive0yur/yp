<?php

namespace app\common\services\payment;


use app\common\repositories\pool\PoolShopOrder;
use app\common\services\PaymentService;
use think\exception\ValidateException;

/**
 * 空支付支付
 * Class EmptyPaymentService
 * @package app\data\service\payment
 */
class EmptyPaymentService extends PaymentService
{

    /**
     * 订单主动查询
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
        return '';
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
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function create(string $openid, string $orderNo, string $payAmount, string $payTitle, string $payRemark, string $payReturn = '', string $payImage = ''): array
    {
        /** @var PoolShopOrder $poolShopOrder */
        $poolShopOrder =  $this->app->make(PoolShopOrder::class);
        $order = $poolShopOrder->search([])->where(['order_no' => $orderNo])->find();
        if (empty($order)) throw new ValidateException("订单不存在");
        if ($order['status'] !== 1) throw new ValidateException("不可发起支付");
        throw new ValidateException('订单价格错误!');
    }
}