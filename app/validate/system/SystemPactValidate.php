<?php

namespace app\validate\system;

use think\Validate;

class SystemPactValidate extends Validate
{

    protected $rule = [
        'pact_type|协议类型' => ['require', 'number'],
        'content|协议内容' => 'require',
    ];

    public function sceneEdit()
    {
        return $this->remove('pact_type', 'require');
    }
}