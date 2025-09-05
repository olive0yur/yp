<?php

namespace app\controller\admin;

use app\BaseController;
use think\facade\View;

class Base extends BaseController
{

    public function __call($name, $arguments)
    {
        return $this->fetch('error/404');
    }

    /**
     * 错误提示
     *
     * @param string $msg 提示信息
     * @param int $code 状态码
     * @return \think\response\Json|\think\response\View
     */
    public function error($msg, $code = -1)
    {
        if ($this->request->isAjax()) {
            return json()->data(['code' => $code, 'msg' => $msg]);
        } else {
            View::assign('msg', $msg);
            return $this->fetch('error/error_tips');
        }
    }

    /**
     * 成功提示
     *
     * @param string $msg 提示信息
     * @param int $code 状态码
     * @return \think\response\Json|\think\response\View
     */
    public function success($msg, $code = 0)
    {
        if ($this->request->isAjax()) {
            return json()->data(['code' => $code, 'msg' => $msg]);
        } else {
            View::assign('msg', $msg);
            return $this->fetch('error/success_tips');
        }
    }

    /**
     * 解析和获取模板内容 用于输出
     *
     * @param string $template 模板文件名或者内容
     * @param array $vars 模板变量
     * @return string
     * @throws \Exception
     */
    protected function fetch(string $template = '', array $vars = []): string
    {
        return View::fetch('admin/' . $template, $vars);
    }

    /**
     * 模板变量赋值
     *
     * @param string|array $name 模板变量
     * @param mixed $value 变量值
     * @return View
     */
    protected function assign($name, $value = null)
    {
        return View::assign($name, $value);
    }

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