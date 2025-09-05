<?php

namespace app\controller;

use app\BaseController;

class Base extends BaseController
{

    /**
     * 获取分页参数
     *
     * @return array
     */
    protected function getPage($defaultPage = 1, $defaultLimit = 10)
    {
        $page = $this->request->get('page', $defaultPage, 'intval');
        $pageSize = $this->request->get('limit', $defaultLimit, 'intval');

        return [$page, $pageSize];
    }
}