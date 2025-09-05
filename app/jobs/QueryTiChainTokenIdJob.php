<?php

namespace app\jobs;

use app\common\repositories\pool\PoolOrderNoRepository;
use app\common\services\TichainService;
use think\queue\Job;

class QueryTiChainTokenIdJob
{
    public function fire(Job $job, $data)
    {
        try {
            // 企业ID
            $companyId = $data['company_id'];
            // 交易hash
            $transactionHash = $data['transactionHash'];


            $res = TichainService::init($companyId)
                ->Nfr()
                ->queryTransferStatus($transactionHash)
                ->execute();
            if ($res && $res['code'] == 0) {
                if ($res['data']['status'] == '0x0') {
                    /** @var PoolOrderNoRepository $poolOrderNoRepository */
                    $poolOrderNoRepository = app()->make(PoolOrderNoRepository::class);
                    $poolOrderNoRepository->getSearch([])
                        ->where('hash', $transactionHash)
                        ->where('token', '-1')
                        ->update([
                            'no' => $res['data']['tokenId'],
                            'hash' => $res['data']['transactionHash']
                        ]);
                }
            }

        } catch (\Exception $e) {
            exception_log('查询tokenID任务处理失败', $e);
        }
        $job->delete();
    }


    public function failed($data)
    {
        // ...任务达到最大重试次数后，失败了
    }

}