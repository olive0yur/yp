<?php

namespace app\helper;

class Regular
{
    /**
     * 字母加数字验证
     *
     * @param string $str 要验证的字符
     * @param int $min 最小位数
     * @param int $max 最大位数
     * @return bool
     */
    public static function alphanumericVerify(string $str, int $min = 1, int $max = 0): bool
    {
        $pattern = '/^[a-zA-Z0-9]{' . $min . ',' . ($max > 0 ? $max : '') . '}$/';

        if (preg_match($pattern, $str)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 手机号验证
     *
     * @param string $mobile 手机号
     * @return bool
     */
    public static function mobileVerify(string $mobile): bool
    {
        $pattern = '/^1[3456789]\d{9}$/';

        if (preg_match($pattern, $mobile)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 身份证号验证
     *
     * @param string $idcard 身份证号
     * @return bool
     */
    public static function idcardVerify(string $idcard): bool
    {
        $vCity = array(
            '11', '12', '13', '14', '15', '21', '22',
            '23', '31', '32', '33', '34', '35', '36',
            '37', '41', '42', '43', '44', '45', '46',
            '50', '51', '52', '53', '54', '61', '62',
            '63', '64', '65', '71', '81', '82', '91'
        );
        if (!preg_match('/^([\d]{17}[xX\d]|[\d]{15})$/', $idcard)) return false;
        if (!in_array(substr($idcard, 0, 2), $vCity)) return false;
        $idcard = preg_replace('/[xX]$/i', 'a', $idcard);
        $vLength = strlen($idcard);
        if ($vLength == 18) {
            $vBirthday = substr($idcard, 6, 4) . '-' . substr($idcard, 10, 2) . '-' . substr($idcard, 12, 2);
        } else {
            $vBirthday = '19' . substr($idcard, 6, 2) . '-' . substr($idcard, 8, 2) . '-' . substr($idcard, 10, 2);
        }
        if (date('Y-m-d', strtotime($vBirthday)) != $vBirthday) return false;
        if ($vLength == 18) {
            $vSum = 0;
            for ($i = 17; $i >= 0; $i--) {
                $vSubStr = substr($idcard, 17 - $i, 1);
                $vSum += (pow(2, $i) % 11) * (($vSubStr == 'a') ? 10 : intval($vSubStr, 11));
            }
            if ($vSum % 11 != 1) return false;
        }
        return true;
    }

    /**
     * 邮箱验证
     *
     * @param string $email 邮箱
     * @return bool
     */
    public static function emailVerify(string $email): bool
    {
        $pattern = '/^\w{3,}@([a-z]{2,7}|[0-9]{3})\.(com|cn)$/';

        if (preg_match($pattern, $email)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 中文姓名验证
     *
     * @param string $name 姓名
     * @return bool
     */
    public static function chineseNameVerify(string $name): bool
    {
        $pattern = '/^([\xe4-\xe9][\x80-\xbf]{2}){2,4}$/';

        if (preg_match($pattern, $name)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 支付宝账号
     *
     * @param string $account 支付宝账号
     * @return bool
     */
    public static function alipayAccountVerify(string $account): bool
    {
        $pattern = '/^(?:1[3-9]\d{9}|[a-zA-Z\d._-]*\@[a-zA-Z\d.-]{1,10}\.[a-zA-Z\d]{1,20})$/';

        if (preg_match($pattern, $account)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 银行卡号
     *
     * @param string $bankno 银行卡号
     * @return bool
     */
    public static function banknoVerify(string $bankno): bool
    {
        if (!preg_match('/^[0-9]*$/', $bankno)) {
            return false;
        }
        $arr_no = str_split($bankno);
        $last_n = $arr_no[count($arr_no) - 1];
        krsort($arr_no);
        $i = 1;
        $total = 0;
        foreach ($arr_no as $n) {
            if ($i % 2 == 0) {
                $ix = $n * 2;
                if ($ix >= 10) {
                    $nx = 1 + ($ix % 10);
                    $total += $nx;
                } else {
                    $total += $ix;
                }
            } else {
                $total += $n;
            }
            $i++;
        }
        $total -= $last_n;
        $total *= 9;
        if ($last_n == ($total % 10)) {
            return true;
        }
        return false;
    }

    /**
     * 来往港澳通行证:
     * 1.W，C+8位数字
     * 2.7位数字
     *
     * @param string $content
     * @return bool
     */
    public static function gapassportVerify(string $content): bool
    {
        $pattern = "/^\d{7}$|^[W|C]\d{8}$/";
        if (!preg_match($pattern, $content)) {
            return false;
        }
        return true;
    }

    /**
     * 国内护照
     * 1.G|E+8位数字：如：G12345678
     * 2.D|S|P+7位数字：如：D1234567
     *
     * @param string $content
     * @return bool
     */
    public static function passportVerify(string $content): bool
    {
        $pattern = "/^[\w]{5,17}$/";
        if (!preg_match($pattern, $content)) {
            return false;
        }
        return true;
    }

    /**
     * 台胞证：
     * 1、8位数字，如：12345678
     * 2、10位数字+(1位英文字母)，如：1234567890(T)
     *
     * @param string $content
     * @return bool
     */
    public static function taibaoVerify(string $content): bool
    {
        $pattern_one = "/^[\d]{8}$/";
        $pattern_two = "/^[\d]{10}[(|（][a-zA-z][)|）]$/iu";
        if (preg_match($pattern_one, $content) || preg_match($pattern_two, $content)) {
            return true;
        }
        return false;
    }


    /**
     * 赴台证:
     * 1.T+8位数字
     * 2.25+7位数字
     *
     * @param string $content
     * @return bool
     */
    public static function twpassportVerify(string $content): bool
    {
        $pattern = "/^25\d{7}$|^T\d{8}$/";
        if (!preg_match($pattern, $content)) {
            return false;
        }
        return true;
    }


    /**
     * 军官证
     * X字+8位，如：南字12345678
     *
     * @param string $content
     * @return bool
     */
    public static function junguanVerify(string $content): bool
    {
        if (mb_strlen($content) != 10) {
            return false;
        }
        $check = preg_match("/^[\x{4e00}-\x{9fa5}]{1}\x{5b57}$/u", mb_substr($content, 0, 2));
        if ($check && preg_match("/^\d{8}$/u", mb_substr($content, 2, 8))) {
            return true;
        }
        return false;
    }
}