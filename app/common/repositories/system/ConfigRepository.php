<?php

namespace app\common\repositories\system;

use app\common\dao\system\ConfigDao;
use app\common\repositories\BaseRepository;
use app\common\repositories\mine\MineConfRepository;
use app\common\services\CacheService;
use think\facade\Cache;

class ConfigRepository extends BaseRepository
{

    public function __construct(ConfigDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 获取所有配置
     * @return array
     */
    private function getAllConfig($companyId = 0)
    {
        $config = CacheService::init($companyId)->remember('get_all_web_config_' . $companyId, function () use ($companyId) {
            $data = $this->dao->selectWhere(['company_id' => $companyId], 'value,key,config_type');
            $config = [];
            foreach ($data as $k => $v) {
                $val = unserialize($v['value']);
                if ($val == 'true') {
                    $val = true;
                } elseif ($val == 'false') {
                    $val = false;
                }
                $config[$v['config_type']][$v['key']] = $val;
            }

            return $config;
        });
        return $config;
    }

    /**
     * 获取配置
     *
     * @param string $name 配置名
     * @param mixed $default 默认值
     * @return array|mixed|null
     */
    public function getConfig($companyId = 0,$name = null, $default = null)
    {
        if (empty($name)) {
            return $this->getAllConfig($companyId);
        }
        $name = explode('.', $name);
        $name[0] = strtolower($name[0]);

        $config = $this->getAllConfig($companyId);
        foreach ($name as $k => $v) {
            if ($v !== '') {
                if (isset($config[$v])) {
                    $config = $config[$v];
                } else {
                    return $default;
                }
            }
        }

        return $config;
    }

    /**
     * 修改配置
     *
     * @param string $type 类型
     * @param array $params 配置
     * @param int $companyId 企业ID
     * @return void
     */
    public function modifyConfig($type, $params, $companyId = 0)
    {
        $this->dao->whereDelete([
            'company_id' => $companyId,
            'config_type' => $type
        ]);
        $data = [];
        foreach ($params as $k => $v) {
            if($k == 'output'){
                Cache::store('redis')->set('thrrate_'.$companyId,$v['thrrate']);
                Cache::store('redis')->set('fivrate_'.$companyId,$v['fivrate']);
                Cache::store('redis')->set('qirate_'.$companyId,$v['qirate']);
                Cache::store('redis')->set('shirate_'.$companyId,$v['shirate']);
                Cache::store('redis')->set('ershirate_'.$companyId,$v['ershirate']);
                Cache::store('redis')->set('wushirate_'.$companyId,$v['wushirate']);
                Cache::store('redis')->set('yibairate_'.$companyId,$v['yibairate']);
                Cache::store('redis')->set('maxrate_'.$companyId,$v['maxrate']);
                if(isset($v['hours'])) {
                    Cache::store('redis')->set('output_hours'.$companyId,$v['hours']);
                    $arr['hours']  =$v['hours'];
                }
                $arr['thrrate']  =$v['thrrate'];
                $arr['fivrate']  =$v['fivrate'];
                $arr['qirate']  =$v['qirate'];
                $arr['shirate']  =$v['shirate'];
                $arr['ershirate']  =$v['ershirate'];
                $arr['wushirate']  =$v['wushirate'];
                $arr['yibairate']  =$v['yibairate'];
                $arr['maxrate']  =$v['maxrate'];
                /** @var MineConfRepository $mineConfRepository */
                $mineConfRepository = app()->make(MineConfRepository::class);
                $conf = $mineConfRepository->search([],$companyId)->find();
                if($conf){
                    $mineConfRepository->editInfo($conf,$arr);
                }else{
                    $mineConfRepository->addInfo($companyId,$arr);
                }
            }
            if ($k == 'node'){
                $level1 = $v['one']['rate'];
                $level2 = $v['two']['rate'];
                $level3 = $v['three']['rate'] ?? 0;
                Cache::store('redis')->set('level_rate_1'.$companyId,$level1);
                Cache::store('redis')->set('level_rate_2'.$companyId,$level2);
                Cache::store('redis')->set('level_rate_3'.$companyId,$level3);
                $arr1['level_1'] = $level1;
                $arr1['level_2'] = $level2;
                $arr1['level_3'] = $level3;
                /** @var MineConfRepository $mineConfRepository */
                $mineConfRepository = app()->make(MineConfRepository::class);
                $conf = $mineConfRepository->search([],$companyId)->find();
                if($conf){
                    $mineConfRepository->editInfo($conf,$arr1);
                }else{
                    $mineConfRepository->addInfo($companyId,$arr1);
                }
            }
            if($k == 'colors'){
                Cache::store('redis')->set('dragon_'.$companyId,$v['product']);
                $arr2['product']  =$v['product'];
                /** @var MineConfRepository $mineConfRepository */
                $mineConfRepository = app()->make(MineConfRepository::class);
                $conf = $mineConfRepository->search([],$companyId)->find();
                if($conf){
                    $mineConfRepository->editInfo($conf,$arr2);
                }else{
                    $mineConfRepository->addInfo($companyId,$arr2);
                }
            }
            $data[] = [
                'key' => $k,
                'value' => serialize($v),
                'config_type' => $type,
                'company_id' => $companyId
            ];
        }

        $res = $this->dao->insertAll($data);
        if($companyId > 0){
            $this->clearCache($companyId);
        }else{
            $this->clearCache();
        }
        return $res;
    }


    /**
     * 清除缓存
     *
     * @return void
     */
    private function clearCache($companyId = 0)
    {
        CacheService::delete('get_all_web_config_' . $companyId);
    }
}