<?php
// 应用公共文件


use app\common\repositories\pool\PoolOrderNoRepository;
use app\common\repositories\pool\PoolSaleRepository;
use app\common\repositories\snapshot\SnapshotRepository;
use app\common\repositories\users\UsersCertRepository;
use app\common\repositories\users\UsersPoolRepository;
use think\facade\Cache;
use think\facade\Db;
use think\facade\Log;

if (!function_exists('admin_log')) {
    /**
     * 管理员日志
     *
     * @param int $logType 日志类型
     * @param string $note 日志信息
     * @param array $otherData 日志操作数据
     * @param int $adminId 管理员id
     * @param string $token 操作token
     */
    function admin_log($logType, $note, $otherData = [], $adminId = 0, $token = '')
    {
        app()->make(\app\common\repositories\system\admin\AdminLogRepository::class)->addLog($logType, $note, $otherData, $adminId, $token);
    }
}

if (!function_exists('J2F')) {
    function J2F($str)
    {

        if (trim($str) == '') {
            return '';
        }
        $fstr = '';
        include 'J2FData.php';//此数据文件下面有下载地址
        $count = mb_strlen($str, 'utf-8');
        for ($i = 0; $i <= $count; $i++) {
            $jchar = mb_substr($str, $i, 1, 'utf-8');
            //这里用到的$fantiData数组是在J2FData.php文件中定义的
            $fchar = isset($fantiData[$jchar]) ? $fantiData[$jchar] : $jchar;
            $fstr .= $fchar;
        }
        return $fstr;
    }

    function getData($data)
    {
        if (!is_array($data) && $data != '{-null-}') {
            $data = $data->toArray();
        }
        if ($data != '{-null-}') {
            foreach ($data as $key => $v) {
                if (gettype($v) == 'object') {
                    foreach ($v->toArray() as $k => $a) {
                        if (is_array($a)) {
                            foreach ($a as $ak => $av) {
                                $data[$key][$k][$ak] = J2F($av);
                            }
                        } else {
                            $data[$key][$k] = J2F($a);
                        }
                    }
                } else {
                    if (!empty($v)) {
                        if (is_array($v)) {
                            foreach ($v as $k => $a) {
                                if (!empty($a)) {
                                    if (is_array($a)) {
                                        foreach ($a as $ak => $av) {
                                            if (is_array($av)) {
                                                foreach ($av as $vk => $vv) {
                                                    $data[$key][$k][$ak][$vk] = J2F($vv);
                                                }
                                            } else {
                                                $data[$key][$k][$ak] = J2F($av);
                                            }
                                        }
                                    } else {
                                        $data[$key][$k] = J2F($a);
                                    }
                                }
                            }
                        } else {
                            $data[$key] = J2F($v);
                        }
                    }
                }
            }
        }
        return $data;
    }

    function arrayLevel($arr)
    {
        $al = array(0);
        aL($arr, $al);
        return max($al);
    }

    function aL($arr, &$al, $level = 0)
    {
        if (is_array($arr)) {
            $level++;
            $al[] = $level;
            foreach ($arr as $v) {
                aL($v, $al, $level);
            }
        }
    }
}


if (!function_exists('company_user_log')) {
    /**
     * 管理员日志
     *
     * @param int $logType 日志类型
     * @param string $note 日志信息
     * @param array $otherData 日志操作数据
     * @param int $adminId 管理员id
     * @param int $companyId 企业Id
     * @param string $token 操作token
     */
    function company_user_log($logType, $note, $otherData = [], $adminId = 0, $companyId = 0, $token = '')
    {
        app()->make(\app\common\repositories\company\user\CompanyUserLogRepository::class)->addLog($logType, $note, $otherData, $adminId, $companyId, $token);
    }
}

if (!function_exists('get_rate')) {
    /**
     * 获取日产和总产
     */
    function get_rate($num, $companyId)
    {
        if (!isset(web_config($companyId, 'program')['output'])) return false;
        $config = web_config($companyId, 'program')['output'];

        $HOURS_PER_DAY = 24;
        $MINUTES_PER_HOUR = 60;
        $SECONDS_PER_MINUTE = 60;

        if (isset($config['thrrate']) && $num > 0 && $num < 3) {
            $rate = bcdiv($config['thrrate'], $HOURS_PER_DAY * $MINUTES_PER_HOUR * $SECONDS_PER_MINUTE, 7);
            return ['rate' => $rate, 'total' => $config['thrrate']];
        }
        if (isset($config['fivrate']) && $num >= 3 && $num < 5) {
            $rate = bcdiv($config['fivrate'], $HOURS_PER_DAY * $MINUTES_PER_HOUR * $SECONDS_PER_MINUTE, 7);
            return ['rate' => $rate, 'total' => $config['fivrate']];
        }

        if (isset($config['qirate']) && $num >= 5 && $num < 7) {
            $rate = bcdiv($config['qirate'], $HOURS_PER_DAY * $MINUTES_PER_HOUR * $SECONDS_PER_MINUTE, 7);
            return ['rate' => $rate, 'total' => $config['qirate']];
        }
        if (isset($config['shirate']) && $num >= 7 && $num < 10) {
            $rate = bcdiv($config['shirate'], $HOURS_PER_DAY * $MINUTES_PER_HOUR * $SECONDS_PER_MINUTE, 7);
            return ['rate' => $rate, 'total' => $config['shirate']];
        }
        if (isset($config['ershirate']) && $num >= 10 && $num < 20) {
            $rate = bcdiv($config['ershirate'], $HOURS_PER_DAY * $MINUTES_PER_HOUR * $SECONDS_PER_MINUTE, 7);
            return ['rate' => $rate, 'total' => $config['ershirate']];
        }
        if (isset($config['wushirate']) && $num >= 20 && $num < 50) {
            $rate = bcdiv($config['wushirate'], $HOURS_PER_DAY * $MINUTES_PER_HOUR * $SECONDS_PER_MINUTE, 7);
            return ['rate' => $rate, 'total' => $config['wushirate']];
        }
        if (isset($config['yibairate']) && $num >= 50 && $num < 100) {
            $rate = bcdiv($config['yibairate'], $HOURS_PER_DAY * $MINUTES_PER_HOUR * $SECONDS_PER_MINUTE, 7);
            return ['rate' => $rate, 'total' => $config['yibairate']];
        }
        if (isset($config['maxrate']) && $num >= 100) {
            $rate = bcdiv($config['maxrate'], $HOURS_PER_DAY * $MINUTES_PER_HOUR * $SECONDS_PER_MINUTE, 7);
            return ['rate' => $rate, 'total' => $config['maxrate']];
        }
        return ['rate' => 0.0000000, 'total' => 0.0000000];
    }
}

if (!function_exists('get_rate1')) {
    /**
     * 获取日产和总产
     */
    function get_rate1($num, $companyId, $uuid)
    {

        $rate = 0;
        $total = 0;
        $node = web_config($companyId, 'program.node.one.rate', 0);
        if (!$node) {
            $usersPushRepository = app()->make(\app\common\repositories\users\UsersPushRepository::class);
            $rate = 0;
            $total = 0;
            $list = $usersPushRepository->search([])
                ->where(['parent_id' => $uuid, 'levels' => 1])
                ->withCount(['pool'])
                ->select();
            foreach ($list as $key => $value) {
                if ($value['pool_count'] >= 7) {
                    $rate += (0.3 / 24 / 60 / 6);
                    $total += 0.3;
                }
                if ($value['pool_count'] < 7) {
                    $rate += (0.03 / 24 / 60 / 6);
                    $total += 0.03;
                }
            }
        } else {
            $usersPushRepository = app()->make(\app\common\repositories\users\UsersPushRepository::class);
            $rate = 0;
            $total = 0;
            $list = $usersPushRepository->search([])
                ->where(['parent_id' => $uuid])->whereIn('levels', [1, 2, 3])
                ->withCount(['pool'])
                ->select();
            foreach ($list as $key => $value) {
                if ($value['pool_count'] > 0) {
                    $count = app()->make(\app\common\repositories\mine\MineUserRepository::class)->search(['uuid' => $value['user_id'], 'level' => 1], $companyId)->value('dispatch_count');
                    $product = get_rate($count, $companyId);
                    switch ($value['levels']) {
                        case 1:
                            $lv = web_config($companyId, 'program.node.one.rate', 0);
                            break;
                        case 2:
                            $lv = web_config($companyId, 'program.node.two.rate', 0);
                            break;
                        case 3:
                            $lv = web_config($companyId, 'program.node.three.rate', 0);
                            break;
                    }
                    $rate += $product['rate'] * $lv;
                    $total += $product['total'] * $lv;
                }
            }
        }
        return ['rate' => $rate, 'total' => $total];
    }
}


if (!function_exists('api_user_log')) {

    function api_user_log($uuid, $track_type, $companyId = null, $remark = '', $track_port = 4)
    {
        return app()->make(\app\common\repositories\users\UsersTrackRepository::class)->addLog($uuid, $track_type, $companyId, $remark, $track_port);
    }
}
if (!function_exists('getLocaltion')) {
    function getLocation($ip)
    {
        $key = '56548aebf70650ffdc0ac54cb98e5c3c';    //为自己申请的key值
        $url = "https://restapi.amap.com/v3/ip?ip=$ip&output=xml&key=$key";
        $data = xmltoarray(file_get_contents($url));   //返回值是xml的格式，进行了解析
        $adcode = 0;
        if ($data['status'] == 1) {
            if ($data['city'] == false) {
                $adcode = '';
            } else {
                $adcode = $data['city'];
            }
        }
        return $adcode;
    }

    function xmltoarray($xml)
    {
        //禁止引用外部xml实体
        libxml_disable_entity_loader(true);
        $xmlstring = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
        $val = json_decode(json_encode($xmlstring), true);
        return $val;
    }
}
if (!function_exists('admin_auth')) {
    /**
     * 后台权限检测
     *
     * @param string $url 地址
     * @return bool
     */
    function admin_auth($url)
    {
        return app()->make(\app\common\repositories\system\admin\AdminUserRepository::class)->checkAuth($url);
    }
}

if (!function_exists('company_auth')) {
    /**
     * 后台权限检测
     *
     * @param string $url 地址
     * @return bool
     */
    function company_auth($url)
    {
        return app()->make(\app\common\repositories\company\user\CompanyUserRepository::class)->checkAuth($url);
    }
}
if (!function_exists('exception_log')) {
    /**
     * 异常日志写入
     *
     * @param string $msg 错误信息
     * @param Exception $e 异常类
     * @param bool $isDetail 是否记录详细信息
     * @author gkdos
     * 2019-10-29 10:54:24
     */
    function exception_log($msg, $e, $isDetail = true)
    {
        if ($isDetail) {
            \app\helper\Log::exceptionWrite($msg, $e);
        } else {
            \think\facade\Log::write($msg . ', error_msg:' . $e->getMessage(), 'error');
        }
    }
}

if (!function_exists('arr_specify_get')) {
    /**
     * 数组指定获取
     *
     * @param array $arr 数组
     * @param array|string $keys 要获取的键名
     * @param bool $multilevel 是否是多级数组
     * @return array
     */
    function arr_specify_get($arr, $keys, $multilevel = false)
    {
        if (!is_array($keys)) {
            $keys = explode(',', $keys);
        }
        if ($multilevel) {
            $tempData = [];
            foreach ($arr as $k => $v) {
                if (is_array($v) && !empty($v)) {
                    $arr = arr_specify_get($v, $keys, true);
                    $tempData[$k] = $arr;
                } else {
                    if (in_array($k, $keys)) {
                        $tempData[$k] = $v;
                    }
                }
            }
            return $tempData;
        } else {
            $tempData = [];
            foreach ($arr as $k => $v) {
                if (in_array($k, $keys)) {
                    $tempData[$k] = $v;
                }
            }

            return $tempData;
        }
    }
}
if (!function_exists('web_config')) {

    /**
     * 查询网站配置
     *
     * @param string $key 键
     * @param mixed $default 默认值
     * @return mixed
     */
    function web_config($companyId = 0, $key = null, $default = null)
    {
        return app()->make(\app\common\repositories\system\ConfigRepository::class)->getConfig($companyId, $key, $default);
    }
}

if (!file_exists('arr_level_sort')) {
    /**
     * 数组按层级排序
     *
     * @param array $array 数组
     * @param int $type 1一级 2多级
     * @param int $fid 上级id
     * @param int $level 层数·
     * @return array
     */
    function arr_level_sort($array, $type = 1, $fid = 0, $level = 0)
    {
        $column = [];
        if ($type == 2) {
            foreach ($array as $key => $vo) {
                if ($vo['pid'] == $fid) {
                    $vo['level'] = $level;
                    $column[$key] = $vo;
                    $column [$key][$vo['id']] = arr_level_sort($array, $type, $vo['id'], $level + 1);
                }
            }
        } else {
            foreach ($array as $key => $vo) {
                if ($vo['pid'] == $fid) {
                    $vo['level'] = $level;
                    $column[] = $vo;
                    $column = array_merge($column, arr_level_sort($array, $type, $vo['id'], $level + 1));
                }
            }
        }

        return $column;
    }
}
if (!function_exists('get_arr_column')) {

    /**
     * 获取数组中的某一列
     *
     * @param array $arr 数组
     * @param string $keyName 列名
     * @return array 返回那一列的数组
     */
    function get_arr_column($arr, $keyName)
    {
        $returnArr = array();
        if (!empty($arr)) {
            foreach ($arr as $k => $v) {
                $returnArr[] = $v[$keyName];
            }
        }
        return $returnArr;
    }

}
if (!function_exists('convert_arr_key')) {

    /**
     * 将数组中的某个键值作为键
     *
     * @param array $arr 数组
     * @param string $keyName 键名
     * @param bool $multilevel 是否保留多级
     * @return array
     */
    function convert_arr_key($arr, $keyName, $multilevel = false)
    {
        $arr2 = array();
        if ($multilevel) {
            foreach ($arr as $key => $val) {
                if (isset($arr2[$val[$keyName]])) {
                    $arr2[$val[$keyName]][] = $val;
                } else {
                    $arr2[$val[$keyName]] = [$val];
                }
            }
        } else {
            foreach ($arr as $key => $val) {
                $arr2[$val[$keyName]] = $val;
            }
        }
        return $arr2;
    }

}
if (!function_exists('sms_verify')) {
    /**
     * 短信验证码验证
     *
     * @param int $companyId 企业ID
     * @param string $mobile 手机号
     * @param int $smsCode 验证码
     * @param int $smsType 验证码类型
     */
    function sms_verify(int $companyId, $mobile, $smsCode, $smsType)
    {
        \app\common\services\SmsService::init($companyId)->checkVerifyCode($mobile, $smsCode, $smsType);
    }
}
if (!function_exists('format_bytes')) {
    /**
     * 文件大小单位转换
     *
     * @param int $size 大小
     * @return array
     */
    function format_bytes($size)
    {
        $units = array('B', 'K', 'M', 'G', 'T');
        $i = 0;
        for (; $size >= 1024 && $i < count($units); $i++) {
            $size /= 1024;
        }
        return round($size, 2) . $units[$i];

    }

}
if (!function_exists('handle_address')) {
    /**
     * 处理地址获取省市区信息
     *
     * @param string $address 地址
     * @return array|string[]
     */
    function handle_address($address)
    {
        preg_match('/(.*?(省|自治区|特别行政区|北京市|天津市|上海市|重庆市))/', $address, $matches);
        $province = '';
        if (count($matches) > 1) {
            $province = $matches[count($matches) - 2];
            $address = str_replace($province, '', $address);
        }
        if (in_array($province, ['北京市', '天津市', '上海市', '重庆市', '香港特别行政区', '澳门特别行政区'])) {
            $city = $province;
        } else {
            preg_match('/(.*?(市|自治州|地区|区划|县|特别行政区))/', $address, $matches);
            if (count($matches) > 1) {
                $city = $matches[count($matches) - 2];
                $address = str_replace($city, '', $address);
            }
        }
        preg_match('/(.*?(区|县|镇|乡|街道))/', $address, $matches);
        if (count($matches) > 1) {
            $area = $matches[count($matches) - 2];
            $address = str_replace($area, '', $address);
        }
        return [
            'province' => isset($province) ? $province : '',
            'city' => isset($city) ? $city : '',
            'area' => isset($area) ? $area : '',
        ];
    }
}
if (!function_exists('goods_attr_format')) {
    /**
     * 获取商品属性信息
     *
     * @param array $attr 商品属性
     * @return array
     */
    function goods_attr_format($attr)
    {
        $data = [];
        foreach ($attr[0]['detail'] as $k => $v) {
            $data[] = [
                $attr[0]['value'] => $v
            ];
        }
        foreach ($attr as $k => $v) {
            if ($k > 0) {
                $data = goods_attr_combine($data, $v);
            }
        }
        return $data;
    }

}
if (!function_exists('generate_pdf_file')) {
    /**
     * 生成pdf文件
     *
     * @param string|array $html html内容
     * @param string $fileName 文件名称 不设置直接输出pdf内容
     * @return mixed|string 文件地址
     * @throws \Mpdf\MpdfException
     */
    function generate_pdf_file($html, $fileName = '')
    {
        $dir = explode('/', $fileName);
        if (count($dir) > 1) {
            unset($dir[count($dir) - 1]);
            $dir = implode('/', $dir);
            if (!is_dir($dir)) {
                mkdir($dir, 0777, true);
            }
        }
        $mpdf = new \Mpdf\Mpdf(['tempDir' => app()->getRootPath() . 'runtime/pdf_tmp']);

        $mpdf->autoLangToFont = true;
        $mpdf->autoScriptToLang = true;

        if (is_array($html)) {
            foreach ($html as $k => $v) {
                $mpdf->AddPageByArray([
                    'margin-left' => 10,
                    'margin-right' => 10,
                    'margin-top' => 12,
                    'margin-bottom' => 12
                ]);
                $mpdf->WriteHTML($v);
            }
        } else {
            $mpdf->AddPageByArray([
                'margin-left' => 10,
                'margin-right' => 10,
                'margin-top' => 12,
                'margin-bottom' => 12
            ]);
            $mpdf->WriteHTML($html);
        }

        $mpdf->Output($fileName);

        return $fileName;
    }
}
if (!function_exists('uuid')) {
    /**
     * 生成uuid
     *
     * @return string
     */
    function uuid()
    {
        $chars = md5(uniqid(mt_rand(), true));
        $uuid = substr($chars, 0, 8) . '-'
            . substr($chars, 8, 4) . '-'
            . substr($chars, 12, 4) . '-'
            . substr($chars, 16, 4) . '-'
            . substr($chars, 20, 12);
        return $uuid;
    }
}
if (!function_exists('authcode')) {
    /**
     *
     * 非常给力的authcode加密函数,Discuz!经典代码(带详解)
     * 函数authcode($string, $operation, $key, $expiry)中的$string：字符串，明文或密文；$operation：DECODE表示解密，其它表示加密；$key：密匙；$expiry：密文有效期。
     * @return false|string
     */
    function authcode($string, $operation = 'DECODE', $key = '', $expiry = 0)
    {
        // 动态密匙长度，相同的明文会生成不同密文就是依靠动态密匙
        $ckey_length = 4;

        // 密匙
        $key = md5($key ? $key : '');

        // 密匙a会参与加解密
        $keya = md5(substr($key, 0, 16));
        // 密匙b会用来做数据完整性验证
        $keyb = md5(substr($key, 16, 16));
        // 密匙c用于变化生成的密文
        $keyc = $ckey_length ? ($operation == 'DECODE' ? substr($string, 0, $ckey_length) : substr(md5(microtime()), -$ckey_length)) : '';
        // 参与运算的密匙
        $cryptkey = $keya . md5($keya . $keyc);
        $key_length = strlen($cryptkey);
        // 明文，前10位用来保存时间戳，解密时验证数据有效性，10到26位用来保存$keyb(密匙b)，
        //解密时会通过这个密匙验证数据完整性
        // 如果是解码的话，会从第$ckey_length位开始，因为密文前$ckey_length位保存 动态密匙，以保证解密正确
        $string = $operation == 'DECODE' ? base64_decode(substr($string, $ckey_length)) : sprintf('%010d', $expiry ? $expiry + time() : 0) . substr(md5($string . $keyb), 0, 16) . $string;
        $string_length = strlen($string);
        $result = '';
        $box = range(0, 255);
        $rndkey = array();
        // 产生密匙簿
        for ($i = 0; $i <= 255; $i++) {
            $rndkey[$i] = ord($cryptkey[$i % $key_length]);
        }
        // 用固定的算法，打乱密匙簿，增加随机性，好像很复杂，实际上对并不会增加密文的强度
        for ($j = $i = 0; $i < 256; $i++) {
            $j = ($j + $box[$i] + $rndkey[$i]) % 256;
            $tmp = $box[$i];
            $box[$i] = $box[$j];
            $box[$j] = $tmp;
        }
        // 核心加解密部分
        for ($a = $j = $i = 0; $i < $string_length; $i++) {
            $a = ($a + 1) % 256;
            $j = ($j + $box[$a]) % 256;
            $tmp = $box[$a];
            $box[$a] = $box[$j];
            $box[$j] = $tmp;
            // 从密匙簿得出密匙进行异或，再转成字符
            $result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
        }
        if ($operation == 'DECODE') {
            // 验证数据有效性，请看未加密明文的格式
            if ((substr($result, 0, 10) == 0 || substr($result, 0, 10) - time() > 0) && substr($result, 10, 16) == substr(md5(substr($result, 26) . $keyb), 0, 16)) {
                return substr($result, 26);
            } else {
                return '';
            }
        } else {
            // 把动态密匙保存在密文里，这也是为什么同样的明文，生产不同密文后能解密的原因
            // 因为加密后的密文可能是一些特殊字符，复制过程可能会丢失，所以用base64编码
            return $keyc . str_replace('=', '', base64_encode($result));
        }
    }
}
if (!function_exists('formatCascaderData')) {
    function formatCascaderData(&$options, $name = '', $baseLevel = 0, $pidName = 'pid', $pid = 0, $level = 0, $data = []): array
    {
        if ($level === 0 && !is_array(current($options))) {
            $temp = [];
            foreach ($options as $k => $v) {
                $temp[] = [
                    'value' => $k,
                    'label' => $v
                ];
            }
            return $temp;
        }
        $_options = $options;
        foreach ($_options as $k => $option) {
            if ($option[$pidName] == $pid) {
                $value = ['value' => $k, 'label' => $option[$name]];
                unset($options[$k]);
                $value['children'] = formatCascaderData($options, $name, $baseLevel, $pidName, $k, $level + 1);
                if (!count($value['children'])) unset($value['children']);
                $data[] = $value;
            }
        }
        return $data;
    }
}
if (!function_exists('bubbleSort')) {
    /**
     * 冒泡排序 从小到大
     *
     * @param array $arr 数组
     * @param string|null $key 键名
     * @return array
     */
    function bubbleSort(array $arr, $key = null)
    {
        if ($key) {
            for ($i = 0; $i < count($arr); $i++) {
                //设置一个空变量
                $data = '';
                for ($j = $i; $j < count($arr) - 1; $j++) {
                    if ($arr[$i][$key] > $arr[$j + 1][$key]) {
                        $data = $arr[$i];
                        $arr[$i] = $arr[$j + 1];
                        $arr[$j + 1] = $data;
                    }
                }
            }
        } else {
            for ($i = 0; $i < count($arr); $i++) {
                //设置一个空变量
                $data = '';
                for ($j = $i; $j < count($arr) - 1; $j++) {
                    if ($arr[$i] > $arr[$j + 1]) {
                        $data = $arr[$i];
                        $arr[$i] = $arr[$j + 1];
                        $arr[$j + 1] = $data;
                    }
                }
            }
        }
        return $arr;
    }
}
if (!function_exists('SignOpenBox')) {
    /**
     * 随机开奖
     * @return string
     */
    function SignOpenBox($item)
    {
        $num = array_sum($item);//计算出分母200
        $result = [];
        foreach ($item as $k => $v) {
            $rand = rand(0.00005, $num);//概率区间(整数) 包括1和200
            if ($rand <= $v) {
                //循环遍历,当下标$k = 1的时候，只有$rand = 1 才能中奖
                $result = $k;
//            echo $rand.'--'.$v;
                break;
            } else {
                //当下标$k=6的时候，如果$rand>100 必须$rand < = 100 才能中奖 ，那么前面5次循环之后$rand的概率区间= 200-1-5-10-24-60 （1,100） 必中1块钱
                $num -= $v;
//            echo '*'.$rand.'*'." "." "." ";
            }
        }
        return $result;
    }
}

if (!function_exists('getRand')) {
    /**
     * 随机开奖
     * @return string
     */
    function getRand($item)
    {
        $num = array_sum($item);//计算出分母200
        foreach ($item as $k => $v) {
            $rand = mt_rand(0.01, $num);//概率区间(整数) 包括1和200
            if ($rand <= $v) {
                //循环遍历,当下标$k = 1的时候，只有$rand = 1 才能中奖
                $result = $k;
//            echo $rand.'--'.$v;
                break;
            } else {
                //当下标$k=6的时候，如果$rand>100 必须$rand < = 100 才能中奖 ，那么前面5次循环之后$rand的概率区间= 200-1-5-10-24-60 （1,100） 必中1块钱
                $num -= $v;
//            echo '*'.$rand.'*'." "." "." ";
            }
        }
        return $result;
    }
}
if (!function_exists('check_active')) {
    /**
     * 随机开奖
     * @return string
     */
    function check_active($id, $companyId, $type)
    {
        $list = app()->make(app\common\repositories\active\ActiveRepository::class)->search(['status' => 1], $companyId)->whereIn('active_type', [1, 2, 3])->whereBetweenTimeField('start_time', 'end_time')
            ->field('id,active_type,with_id')
            ->select();
        foreach ($list as $k => $v) {
            switch ($v['active_type']) {
                case 1:
                    switch ($type) {
                        case 1:
                            return app()->make(\app\common\repositories\active\syn\ActiveSynInfoRepository::class)
                                ->search(['syn_id' => $v['with_id'], 'goods_id' => $id, 'target_type' => 1], $companyId)->count('id');
                        case 2:
                            return app()->make(\app\common\repositories\active\syn\ActiveSynInfoRepository::class)
                                ->search(['syn_id' => $v['with_id'], 'goods_id' => $id, 'target_type' => 2], $companyId)->count('id');
                    }

                    break;
                case 2:
                    switch ($type) {
                        case 1:
                            return app()->make(\app\common\repositories\active\draw\ActiveDrawInfoRepository::class)
                                ->search(['draw_id' => $v['with_id'], 'goods_id' => $id, 'goods_type' => 1], $companyId)->count('id');
                            break;
                        case 2:
                            return app()->make(\app\common\repositories\active\draw\ActiveDrawInfoRepository::class)
                                ->search(['draw_id' => $v['with_id'], 'goods_id' => $id, 'goods_type' => 2], $companyId)->count('id');
                            break;
                    }
                    break;
                case 3:
            }
        }
    }
}

if (!function_exists('authSyn')) {
    ## 1 正常  2 没开始 3 结束
    function authSyn($data)
    {
        $date = date('Y-m-d H:i:s');
        if ($data['start_time'] > $date) return 2;
        if ($data['end_time'] < $date) return 3;
        $syn = app()->make(\app\common\repositories\active\syn\ActiveSynInfoRepository::class);
        $num = $syn->search(['is_target' => 1, 'syn_id' => $data['with_id']])->sum('num');
        return ($num > 0) ? 1 : 3;
    }
}


if (!function_exists('time_diff')) {
    /**
     * 计算时间差
     * @param int $timestamp1 时间戳开始
     * @param int $timestamp2 时间戳结束
     * @return array
     */
    function time_diff($timestamp1, $timestamp2, $type = 1)
    {
        if ($timestamp2 <= $timestamp1) {
            return ['hours' => 0, 'minutes' => 0, 'seconds' => 0];
        }
        $timediff = $timestamp2 - $timestamp1;
        switch ($type) {
            case 1:
                // 时
                $remain = $timediff % 86400;
                $hours = intval($remain / 3600);
                return $hours;
            case 2:
                // 分
                $remain = $timediff % 3600;
                $mins = intval($remain / 60);
                return $mins;
        }
    }
}


if (!function_exists('calculateTimeDifference')) {
    /**
     * 计算时间差
     * @param int $timestamp1 时间戳开始
     * @param int $timestamp2 时间戳结束
     * @param int $type days  hours
     * @return array
     */
    function calculateTimeDifference($time1, $time2, $type = 'days')
    {
        // 如果是时间戳格式，直接转换为 DateTime 对象
        if (is_numeric($time1)) {
            $datetime1 = (new DateTime())->setTimestamp($time1);
        } else {
            $datetime1 = new DateTime($time1);
        }

        if (is_numeric($time2)) {
            $datetime2 = (new DateTime())->setTimestamp($time2);
        } else {
            $datetime2 = new DateTime($time2);
        }

        // 计算时间差
        $interval = $datetime1->diff($datetime2);

        // 根据类型参数返回相差的天数或小时数
        if ($type === 'days') {
            return $interval->days;
        } elseif ($type === 'hours') {
            return ($interval->days * 24) + $interval->h + ($interval->i / 60) + ($interval->s / 3600);
        } else {
            return null; // 如果类型参数无效，返回 null
        }
    }
}

if (!function_exists('gashaponu')) {
    function gashaponu($uuid, $type, $companyId = null)
    {
        $list = app()->make(\app\common\repositories\gashapon\GashaponToRepository::class)->serach(['type' => $type])->select();
        foreach ($list as $value) {
            if ($type == 2) {
                $userCount = app()->make(\app\common\repositories\users\UsersPushRepository::class)->search(['parent_id' => $uuid, 'levels' => 1])->count('id');
                $is = 3;
            } elseif ($type == 3) {
                $userCount = app()->make(UsersPoolRepository::class)->search(['uuid' => $uuid, 'type' => 1])->count('id');
                $is = 4;
            }
            if ($userCount >= $value['number']) {
                $count = Db::table('gashapon_log')->where(['type' => $type, 'uuid' => $uuid['id'], 'num' => $value['number']])->find();
                if (!$count) {
                    for ($i = 0; $i < $value['count']; $i++) {
                        $givBoxData = [];
                        $givBoxData['type'] = $is;
                        $givBoxData['status'] = 1;
                        $givBoxData['uuid'] = $uuid;
                        app()->make(\app\common\repositories\gashapon\GashaponUserRepository::class)->addInfo($companyId, $givBoxData);
                    }
                }
            }
        }
    }
}

if (!function_exists('task')) {

    function task($uuid, $type = 1, $companyId = null, $goods_id = 0, $source = null)
    {
        if ($source === 'invite') {
            $status = false;
            $key = 'invitation_rewards:' . $uuid;
            $invitation_rewards_keys = Cache::store('redis')->smembers($key);
            if (isset($invitation_rewards_keys)) {
                if (count($invitation_rewards_keys) === 3) {
                    foreach ($invitation_rewards_keys as $v) {
                        $usersCertRepository = app()->make(UsersCertRepository::class);
                        $certInfo = $usersCertRepository->getSearch(['user_id' => $v])->find();
                        if (!$certInfo) continue;
                        if (((int)$certInfo['cert_status']) != 2) continue;
                    }
                    $status = true;
                }
            }
            if ($status === false) return;
            Cache::store('redis')->delete($key);
        }


        $TaskRepository = app()->make(\app\common\repositories\active\task\TaskRepository::class);
        $list = $TaskRepository->search(['type' => $type], $companyId)->whereBetweenTimeField('start_time', 'end_time')->select();
        $no = app()->make(PoolOrderNoRepository::class);
        $usersRepository = app()->make(\app\common\repositories\users\UsersRepository::class);
        $usersPoolRepository = app()->make(UsersPoolRepository::class);
        $poolSaleRepository = app()->make(PoolSaleRepository::class);
        $usersPushRepository = app()->make(\app\common\repositories\users\UsersPushRepository::class);
        $poolDrawRepository = app()->make(\app\common\repositories\pool\PoolDrawRepository::class);
        $taskLogRepository = app()->make(\app\common\repositories\active\task\TaskLogRepository::class);
        $userPool = [];
        foreach ($list as $key => $value) {
            if (in_array($type, [4, 5])) {
                $uid = $usersPushRepository->search(['user_id' => $uuid], $companyId)->where('levels', 1)->value('parent_id');
                if ($type == 5 && $uid && $value['goods_id'] == $goods_id) {
                    $userPool = app()->make(UsersPoolRepository::class)->search([], $companyId)->where(['uuid' => $uuid, 'type' => 1, 'pool_id' => $value['goods_id']])->find();
                    if (!$userPool) break;
                }
                $arr['child_id'] = $uuid;
                $uid = app()->make(\app\common\repositories\users\UsersPushRepository::class)
                    ->search(['user_id' => $uuid, 'levels' => 1])->value('parent_id');
            } else {
                $uid = $uuid;
            }

            if ($value['task_type'] == 1) {
                $count = $taskLogRepository->search(['uuid' => $uid], $companyId)->count('id');
                if ($value['task_num'] <= $count) continue;
            }

            if ($value['task_type'] == 2) {
                $count = $taskLogRepository->search(['uuid' => $uid], $companyId)->whereTime('add_time', 'today')->count('id');
                if ($value['task_num'] <= $count) continue;
            }
            switch ($value['send_type']) {
                case 1:

                    if ($value['num'] > 0) {
                        $usersRepository->integralChange($uid, 4, $value['num'], ['remark' => '任务奖励', 'company_id' => $companyId], 4);
                    }
                    break;
                case 2:
                    if ($value['send_pool_id'] > 0 && $uid > 0) {
                        $pool = $poolSaleRepository->search(['id' => $value['send_pool_id']], $companyId)->find();
                        if (!$pool) break;
                        $data['uuid'] = $uid;
                        $data['add_time'] = date('Y-m-d H:i:s');
                        $data['pool_id'] = $value['send_pool_id'];
                        $data['no'] = $no->getNo($value['send_pool_id'], $uid);
                        if (!$data['no']) break;
                        $data['price'] = 0.00;
                        $data['type'] = 9;
                        $re = $usersPoolRepository->addInfo($companyId, $data);
                        if ($re) {
                            $poolSaleRepository->update($value['send_pool_id'], ['stock' => ($pool['stock'] - 1)]);
                        }
                    }
                    break;
                case 3:
                    if ($value['send_pool_id'] > 0 && $uid > 0) {
                        $arr['pool_id'] = $value['send_pool_id'];
                        $arr['uuid'] = $uid;
                        $arr['order_no'] = $value['send_pool_id'] . $uuid . rand(9999, 99999);
                        $poolDrawRepository->addInfo($companyId, $arr);
                    }
                    break;

            }
            if ($uid) {
                $arr['task_id'] = $value['id'];
                $arr['uuid'] = $uid;
                $taskLogRepository->addInfo($companyId, $arr);
            }

        }
    }

}

if (!function_exists('post')) {
    function post($url, $data = [])
    {
        if (empty($header)) {
            $header = array('Content-Type:application/json;charset=utf-8', 'Accept:application/json');
        }
        $curl = curl_init(); //初始化curl
        curl_setopt($curl, CURLOPT_URL, $url); //抓取指定网页
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        curl_setopt($curl, CURLOPT_HEADER, false); //设置header
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); //要求结果为字符串且输出到屏幕上
        curl_setopt($curl, CURLOPT_POST, 1); //post提交方式?
        if ($data) {
            $data = json_encode($data);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        $res = curl_exec($curl);
        curl_close($curl);
        return json_decode($res, true);
    }
}

if (!function_exists('dows_file')) {

    function dows_file($url, $filePath = './', $type = 1)
    {
        if (empty($url)) {
            return false;
        }
        //创建保存目录
        if (!file_exists($filePath) && !mkdir($filePath, 0777, true)) {
            return false;
        }
        if ($type == 1) {
            //打开文件
            $fp = @fopen($filePath . iconv("UTF-8", "GB2312", urldecode(basename($url))), 'w');
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_FILE, $fp);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_exec($ch);
            //关闭URL请求
            curl_close($ch);
            //关闭文件
            fclose($fp);
        } else {
            $newfname = $filePath . iconv("UTF-8", "GB2312", urldecode(basename($url)));
            $file = @fopen($url, "r+");
            if ($file) {
                $newf = @fopen($newfname, "w+");
                if ($newf) {
                    while (!feof($file)) {
                        fwrite($newf, fread($file, 1024 * 5), 1024 * 5);
                    }
                }
            }
            if ($file) {
                fclose($file);
            }
            if ($newf) {
                fclose($newf);
            }
        }
        return true;
    }

}

if (function_exists('mb_substr_replace') === false) {
    function mb_substr_replace($string, $replacement, $start, $length = null, $encoding = null)
    {
        if (extension_loaded('mbstring') === true) {
            $string_length = (is_null($encoding) === true) ? mb_strlen($string) : mb_strlen($string, $encoding);

            if ($start < 0) {
                $start = max(0, $string_length + $start);
            } else if ($start > $string_length) {
                $start = $string_length;
            }

            if ($length < 0) {
                $length = max(0, $string_length - $start + $length);
            } else if ((is_null($length) === true) || ($length > $string_length)) {
                $length = $string_length;
            }

            if (($start + $length) > $string_length) {
                $length = $string_length - $start;
            }

            if (is_null($encoding) === true) {
                return mb_substr($string, 0, $start) . $replacement . mb_substr($string, $start + $length, $string_length - $start - $length);
            }

            return mb_substr($string, 0, $start, $encoding) . $replacement . mb_substr($string, $start + $length, $string_length - $start - $length, $encoding);
        }

        return (is_null($length) === true) ? substr_replace($string, $replacement, $start) : substr_replace($string, $replacement, $start, $length);
    }
}


if (!function_exists('send')) {
    function send($post_data)
    {
        // 建立socket连接到内部推送端口
        $client = stream_socket_client('tcp://127.0.0.1:9508', $errno, $errmsg, 1);
        // 推送的数据，包含uid字段，表示是给这个uid推送
        // 发送数据，注意5678端口是Text协议的端口，Text协议需要在数据末尾加上换行符
        fwrite($client, json_encode($post_data) . "\n");
        // 读取推送结果
        // echo fread($client, 8192);
    }
}
