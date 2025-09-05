<?php

namespace app\controller\api;

use app\BaseController;
use app\common\services\OpenAesService;

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

    /**
     * 成功返回
     *
     * @param $data
     * @param string $message
     * @return mixed
     */
    protected function success($data = null, string $message = 'ok')
    {
        $language =  $this->request->header('language');
        if($language == 2){
//            $message = J2F($message);
        }
        return app('api_return')->success($data, $message);
    }

    /**
     * 成功文字返回
     *
     * @param string $message
     * @return mixed
     */
    protected function successText(string $message = 'ok')
    {
        return app('api_return')->success([], $message);
    }

    /**
     * 错误返回
     *
     * @param string $message
     * @param int $code
     * @return mixed
     */
    protected function error(string $message = '', int $code = 500)
    {
        return app('api_return')->error($message, $code);
    }
}