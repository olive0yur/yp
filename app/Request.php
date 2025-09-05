<?php

namespace app;

// 应用请求对象类
use app\traits\Macro;

class Request extends \think\Request
{
    use Macro;

    public function ip(): string
    {
        return $this->header('remote-host') ?? parent::ip();
    }

}
