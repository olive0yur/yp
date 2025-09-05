<?php

namespace app\common\services;

class RealNameCertService
{

    protected $config = [];

    public function __construct(int $companyId)
    {
        $this->config = web_config($companyId, 'program.cert');
    }

    public static function init(int $companyId)
    {
        return new self($companyId);
    }

    public function twoInfoCert(string $name, string $idCard)
    {

        try {
            $client = new \GuzzleHttp\Client([
                'base_uri' => 'https://idcardcert.market.alicloudapi.com',
                'verify' => false
            ]);

            $res = $client->get('/idCardCert', [
                'query' => [
                    'idCard' => $idCard,
                    'name' => $name
                ],
                'headers' => [
                    'Authorization' => 'APPCODE ' . $this->config['aliyun_two_appcode']
                ]
            ]);
            $returnData = json_decode($res->getBody(), true);

            if ($returnData['status'] == '01') {
                $returnData['status'] = 1;
            }
            return $returnData;
        } catch (\Exception $e) {
            exception_log('实名认证接口请求失败', $e);
            return [
                'status' => 400,
                'msg' => '实名认证失败'
            ];
        }
    }
}