<?php


namespace CrazyGoat\Forex\Service;


class RC4Crypt
{
    private string $key;

    public function __construct(string $key)
    {
        $this->key = $key;
    }

    function encrypt($data)
    {
        $res = $this->rc4($data, $this->key);

        return 'CB01' . $this->toHex($res);
    }

    /**
     * [rc4 description]
     *
     * @param [type] $data [data to be encrypted]
     * @param [type] $pwd [key]
     *
     * @return [type] [Encrypted binary, need to be converted to hexadecimal]
     */
    private function rc4($data, $pwd)
    {
        $key[] = "";
        $box[] = "";
        $pwd_length = strlen($pwd);
        $data_length = strlen($data);
        $cipher = '';
        for ($i = 0; $i < 256; $i++) {
            $key[$i] = ord($pwd[$i % $pwd_length]);
            $box[$i] = $i;
        }
        for ($j = $i = 0; $i < 256; $i++) {
            $j = ($j + $box[$i] + $key[$i]) % 256;
            $tmp = $box[$i];
            $box[$i] = $box[$j];
            $box[$j] = $tmp;
        }
        for ($a = $j = $i = 0; $i < $data_length; $i++) {
            $a = ($a + 1) % 256;
            $j = ($j + $box[$a]) % 256;
            $tmp = $box[$a];
            $box[$a] = $box[$j];
            $box[$j] = $tmp;
            $k = $box[(($box[$a] + $box[$j]) % 256)];
            $cipher .= chr(ord($data[$i]) ^ $k);
        }

        return $cipher;
    }

    /**
     * Convert data to hexadecimal
     *
     * @param [type] $sa [data to be converted]
     * @param integer $len [data length]
     */
    private function toHex($sa, $len = 0)
    {
        $buf = "";
        if ($len == 0) {
            $len = strlen($sa);
        }
        for ($i = 0; $i < $len; $i++) {
            $val = dechex(ord($sa{$i}));
            if (strlen($val) < 2) {
                $val = "0" . $val;
            }
            $buf .= $val;
        }

        return $buf;
    }

    public function decrypt($data)
    {
        $str = $this->fromHex($data);

        return $this->rc4($str, $this->key);
    }

    /**
     * Convert hex to string
     *
     * @param [type] $sa [Hexadecimal data]
     */
    private function fromHex($sa)
    {
        $buf = "";
        $len = strlen($sa);
        for ($i = 0; $i < $len; $i += 2) {
            $val = chr(hexdec(substr($sa, $i, 2)));
            $buf .= $val;
        }

        return $buf;
    }
}