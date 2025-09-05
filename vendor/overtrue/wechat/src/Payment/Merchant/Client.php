<?php

/*
 * This file is part of the overtrue/wechat.
 *
 * (c) overtrue <i@overtrue.me>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace EasyWeChat\Payment\company;

use EasyWeChat\Payment\Kernel\BaseClient;

/**
 * Class Client.
 *
 * @author mingyoung <mingyoungcheung@gmail.com>
 */
class Client extends BaseClient
{
    /**
     * Add sub-company.
     *
     * @param array $params
     *
     * @return \Psr\Http\Message\ResponseInterface|\EasyWeChat\Kernel\Support\Collection|array|object|string
     *
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     */
    public function addSubcompany(array $params)
    {
        return $this->manage($params, ['action' => 'add']);
    }

    /**
     * Query sub-company by company id.
     *
     * @param string $id
     *
     * @return \Psr\Http\Message\ResponseInterface|\EasyWeChat\Kernel\Support\Collection|array|object|string
     *
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     */
    public function querySubcompanyBycompanyId(string $id)
    {
        $params = [
            'micro_mch_id' => $id,
        ];

        return $this->manage($params, ['action' => 'query']);
    }

    /**
     * Query sub-company by wechat id.
     *
     * @param string $id
     *
     * @return \Psr\Http\Message\ResponseInterface|\EasyWeChat\Kernel\Support\Collection|array|object|string
     *
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     */
    public function querySubcompanyByWeChatId(string $id)
    {
        $params = [
            'recipient_wechatid' => $id,
        ];

        return $this->manage($params, ['action' => 'query']);
    }

    /**
     * @param array $params
     * @param array $query
     *
     * @return \Psr\Http\Message\ResponseInterface|\EasyWeChat\Kernel\Support\Collection|array|object|string
     *
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     */
    protected function manage(array $params, array $query)
    {
        $params = array_merge($params, [
            'appid' => $this->app['config']->app_id,
            'nonce_str' => '',
            'sub_mch_id' => '',
            'sub_appid' => '',
        ]);

        return $this->safeRequest('secapi/mch/submchmanage', $params, 'post', compact('query'));
    }
}
