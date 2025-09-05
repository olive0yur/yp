<?php

namespace app\helper;

class Num
{
    /**
     * 人民币小写转大写
     *
     * @param float $money 数值
     * @param string $int_unit 币种单位，默认"元"，有的需求可能为"圆"
     * @param bool $is_round 是否对小数进行四舍五入
     * @param bool $is_extra_zero 是否对整数部分以0结尾，小数存在的数字附加0,比如1960.30
     * @return string
     */
    public static function rmb_format($money = 0, $int_unit = '元', $is_round = true, $is_extra_zero = false)
    {
        // 将数字切分成两段
        $parts = explode('.', $money, 2);
        $int = isset ($parts [0]) ? strval($parts [0]) : '0';
        $dec = isset ($parts [1]) ? strval($parts [1]) : '';

        // 如果小数点后多于2位，不四舍五入就直接截，否则就处理
        $dec_len = strlen($dec);
        if (isset ($parts [1]) && $dec_len > 2) {
            $dec = $is_round ? substr(strrchr(strval(round(floatval("0." . $dec), 2)), '.'), 1) : substr($parts [1], 0, 2);
        }

        // 当number为0.001时，小数点后的金额为0元
        if (empty ($int) && empty ($dec)) {
            return '零';
        }

        // 定义
        $chs = array('-' => '负', 0 => '0', 1 => '壹', 2 => '贰', 3 => '叁', 4 => '肆', 5 => '伍', 6 => '陆', 7 => '柒', 8 => '捌', 9 => '玖');
        $uni = array('', '拾', '佰', '仟');
        $dec_uni = array('角', '分');
        $exp = array('', '万');
        $res = '';

        // 整数部分从右向左找
        for ($i = strlen($int) - 1, $k = 0; $i >= 0; $k++) {
            $str = '';
            // 按照中文读写习惯，每4个字为一段进行转化，i一直在减
            for ($j = 0; $j < 4 && $i >= 0; $j++, $i--) {
                $u = $int{$i} > 0 ? $uni [$j] : ''; // 非0的数字后面添加单位
                $str = $chs [$int{$i}] . $u . $str;
            }
            $str = rtrim($str, '0'); // 去掉末尾的0
            $str = preg_replace("/0+/", "零", $str); // 替换多个连续的0
            if (!isset ($exp [$k])) {
                $exp [$k] = $exp [$k - 2] . '亿'; // 构建单位
            }
            $u2 = $str != '' ? $exp [$k] : '';
            $res = $str . $u2 . $res;
        }

        // 如果小数部分处理完之后是00，需要处理下
        $dec = rtrim($dec, '0');
        // 小数部分从左向右找
        if (!empty ($dec)) {
            $res .= $int_unit;

            // 是否要在整数部分以0结尾的数字后附加0，有的系统有这要求
            if ($is_extra_zero) {
                if (substr($int, -1) === '0') {
                    $res .= '零';
                }
            }

            for ($i = 0, $cnt = strlen($dec); $i < $cnt; $i++) {
                $u = $dec{$i} > 0 ? $dec_uni [$i] : ''; // 非0的数字后面添加单位
                $res .= $chs [$dec{$i}] . $u;
                if ($cnt == 1)
                    $res .= '整';
            }

            $res = rtrim($res, '0'); // 去掉末尾的0
            $res = preg_replace("/0+/", "零", $res); // 替换多个连续的0
        } else {
            $res .= $int_unit . '整';
        }
        return $res;
    }

    /**
     * 生成一个随机数字
     *
     * @param int $endNum 数字长度
     * @return string
     */
    public static function getRandNumber(int $endNum = 0)
    {
        $endNum = ($endNum <= 0 ? rand(4, 10) : $endNum);
        $str = rand(11111111, 99999999);
        $length = strlen($str) - 1;
        $substr = substr($str, rand(0, $length), rand(0, $length));
        $str .= $substr;
        $endStr = $str . rand(111111, 999999);
        $str = '';
        for ($i = 0; $i < $endNum; $i++) {
            $str .= $endStr[mt_rand(0, strlen($endStr) - 1)];
        }
        $str = strtolower($str);
        if ($str <= 0) {
            self::getRandNumber($endNum);
        }
        return $str;
    }
}