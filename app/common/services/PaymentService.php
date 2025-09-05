<?php

namespace app\common\services;

use app\common\repositories\pool\PoolOrderNoRepository;
use app\common\repositories\pool\PoolSaleRepository;
use app\common\repositories\pool\PoolShopOrder;
use app\common\repositories\system\PaymentRepository;
use app\common\repositories\users\UsersBoxRepository;
use app\common\repositories\users\UsersPoolRepository;
use app\common\services\payment\BalancePyamentService;
use app\common\services\payment\EmptyPaymentService;
use Mpdf\Tag\P;
use think\App;
use think\Exception;
use think\exception\ValidateException;
use think\facade\Cache;
use function JmesPath\search;

/**
 * 支付基础服务
 * Class PaymentService
 * @package app\common\service
 */
abstract class PaymentService
{

    // 用户余额支付
    const PAYMENT_EMPTY = 'empty';
    const PAYMENT_BALANCE = 'balance';
    const PAYMENT_VOUCHER = 'voucher';




    // 支付通道配置，不需要的可以注释
    const TYPES = [
//        // 空支付，金额为零时自动完成支付
//        self::PAYMENT_EMPTY       => [
//            'type' => 'EMPTY',
//            'name' => '订单无需支付',
//            'bind' => [],
//        ],
        // 余额支付，使用账号余额完成支付
        self::PAYMENT_BALANCE => [
            'type' => 'BALANCE',
            'name' => '账号余额支付',
            'bind' => [

            ],
        ],
    ];
    /**
     * 支付服务对象
     * @var array
     */
    protected static $driver = [];
    public $poolShopOrderRepository;
    /**
     * 当前应用
     * @var App
     */
    protected $app;
    /**
     * 支付参数编号
     * @var string
     */
    protected $code;
    /**
     * 默认支付类型
     * @var string
     */
    protected $type;
    /**
     * 当前支付参数
     * @var array
     */
    protected $params;

    /**
     * PaymentService constructor.
     * @param App $app 当前应用对象
     * @param string $code 支付参数编号
     * @param string $type 支付类型代码
     * @param array $params 支付参数配置
     */
    public function __construct(App $app, string $code, string $type, array $params, PoolShopOrder $poolShopOrder)
    {
        [$this->app, $this->code, $this->type, $this->params] = [$app, $code, $type, $params];
        $this->poolShopOrderRepository = $poolShopOrder;
        if (method_exists($this, 'initialize')) $this->initialize();
    }

    /**
     * 根据配置实例支付服务
     * @param string $code 支付配置编号
     * @return PaymentService
     */
    public static function instance(string $code, int $companyId = null): PaymentService
    {
        if ($code === 'empty') {
            $vars = ['code' => 'empty', 'type' => 'empty', 'params' => []];
            return static::$driver[$code] = app()->make(EmptyPaymentService::class, $vars);
        }
        [, $type, $params] = self::config($code, $companyId);
        if (isset(static::$driver[$code])) return static::$driver[$code];
        $vars = ['code' => $code, 'type' => $type, 'params' => $params];
        // 实例化具体支付参数类型

        if (stripos($type, 'balance') === 0) {
            return static::$driver[$code] = app()->make(BalancePyamentService::class, $vars);
        }  else {
            throw new ValidateException(sprintf('支付驱动[%s]未定义', $type));
        }
    }

    /**
     * 获取支付配置参数
     * @param string $code
     * @param array $payment
     * @return array [code, type, params]
     * @throws Exception
     */
    public static function config(string $code, int $companyId = null, array $payment = []): array
    {
        try {
            if (empty($payment)) {
                /** @var PaymentRepository $repository */
                $repository = app()->make(PaymentRepository::class);
                $payment = $repository->search(['type' => $code], $companyId)->find();
            }
            if (empty($payment)) {
                throw new Exception("支付参数[#{$code}]禁用关闭");
            }
            $params = $payment['content'];

            if (empty($params)) {
                throw new Exception("支付参数[#{$code}]配置无效");
            }
            if (empty(static::TYPES[$payment['type']])) {
                throw new Exception("支付参数[@{$payment['type']}]匹配失败");
            }
            return [$payment['code'], $payment['type'], $params];
        } catch (\Exception $exception) {
            throw new ValidateException($exception->getMessage(), $exception->getCode());
        }
    }

    /**
     * 获取支付支付名称
     * @param string $type
     * @return string
     */
    public static function name(string $type): string
    {
        return static::TYPES[$type]['name'] ?? $type;
    }


    /**
     * 筛选可用的支付类型
     * @param string $api 指定接口类型
     * @param array $types 默认返回支付
     * @return array
     */
    public static function getTypeApi(string $api = '', array $types = []): array
    {
        foreach (self::TYPES as $type => $attr) {
            if (in_array($api, $attr['bind'])) $types[] = $type;
        }
        return array_unique($types);
    }

}