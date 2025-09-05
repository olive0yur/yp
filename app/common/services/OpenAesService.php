<?php
namespace app\common\services;


class OpenAesService
{
    /**
     * var string $method 加解密方法，可通过openssl_get_cipher_methods()获得
     */
    protected $method;

    /**
     * var string $secret_key 加解密的密钥
     */
    protected $secret_key;

    /**
     * var string $iv 加解密的向量，有些方法需要设置比如CBC
     */
    protected $iv;

    /**
     * var string $options
     */
    protected $options;

    /**
     * OpenAes constructor.
     * @param $secret_key
     * @param string $method
     * @param int $options
     */
    public function __construct($secret_key,$method = 'aes-128-cbc', $options = 0)
    {
        // key是必须要设置的
        $this->secret_key = $secret_key;
        $this->method = $method;
        $this->options = $options;
        $this->iv= 'ABCDEF1234123412';
    }

    /**
     * 加密
     * @param $data
     * @return string
     */
    public function encryptData($data)
    {
        //AES, 128 模式加密数据 CBC
//        $screct_key = base64_decode($this->secret_key);
        $screct_key = $this->secret_key;
        $str = trim(json_encode($data));
        $str = $this->addPKCS7Padding($str);
        $encrypt_str = openssl_encrypt($str, 'aes-128-cbc', $screct_key, OPENSSL_NO_PADDING, $this->iv);
        return base64_encode($encrypt_str);
    }

    /**
     * 解密
     * @param $message_string
     * @param null $appId
     * @return bool|mixed
     */
    public function decryptData($message_string)
    {
        //AES, 128 模式加密数据 CBC
        $str = base64_decode($message_string);

        $screct_key = base64_decode($this->secret_key);
        $screct_key = $this->secret_key;
        //设置全0的IV

        $decrypt_str = openssl_decrypt($str, 'aes-128-cbc', $screct_key, OPENSSL_NO_PADDING, $this->iv);
        $decrypt_str = $this->stripPKSC7Padding($decrypt_str);
        return $decrypt_str;
    }

    /**
     * 移去填充算法
     * @param string $source
     * @return string
     */
    public function stripPKSC7Padding($source)
    {
        $char = substr($source, -1);
        $num = ord($char);
        if ($num == 62) return $source;
        $source = substr($source, 0, -$num);
        return $source;
    }

    /**
     * 填充算法
     * @param string $source
     * @return string
     */
    public function addPKCS7Padding($source)
    {
        $source = trim($source);
        $block = 16;

        $pad = $block - (strlen($source) % $block);
        if ($pad <= $block) {
            $char = chr($pad);
            $source .= str_repeat($char, $pad);
        }
        return $source;
    }
}
