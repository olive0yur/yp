<?php

namespace app\validate\order;

use think\Validate;

class OrderRefundValidate extends Validate
{
    protected $rule = [
        'type|退款类型' => 'require|in:1,2',
        'refund_type|退款方式' => 'require|in:1,2',
        'refund_num|商品件数' => 'requireIf:type,1|integer|>:0',
        'ids|退款商品' => 'require',
        'refund_message|退款原因' => 'require|max:128',
        'mark|备注' => 'max:128',
        'refund_price|退款金额' => 'require|float',
        'pics|凭证' => 'array|max:9',
    ];

    public function sceneAudit()
    {
        return $this->only(['status', 'fail_message'])
            ->append([
                'status' => 'require|in:1,-1',
                'fail_message' => 'requireIf:status,-1'
            ])->message([
                'status' => '审核类型错误',
                'fail_message.requireIf' => '拒绝原因不能为空'
            ]);
    }
}