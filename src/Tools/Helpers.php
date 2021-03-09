<?php
//----------------------------------------------------------------
// 通用助手函数
//----------------------------------------------------------------

if(!function_exists('user_md5')){
    /**
     * 用户密码加密方法.
     *
     * @param string $str      加密的字符串
     * @param [type] $auth_key 加密符
     *
     * @return string 加密后长度为32的字符串
     */
    function user_md5($str, $salt = '')
    {
        return '' === $str ? '' : md5(sha1($str).$salt);
    }
}


if(!function_exists('str2Arr')){
    /**
     * 将字符串类型参数转换为数组.
     *
     * @param [type] $str
     */
    function str2Arr($str)
    {
        if (is_array($str)) {
            return $str;
        }

        $res1 = json_decode($str, true);

        if (is_array($res1)) {
            return $res1;
        }

        if(strpos($str,',') !== false){
            $res2 = explode(',', $str);
            if (is_array($res2)) {
                return $res2;
            }
        }

        return $str;
    }
}


if(!function_exists('humpToLine')){
    /**
     * 驼峰转下划线
     *
     * @param string $str 需要转换的字符
     */
    function humpToLine($str)
    {
        $str = preg_replace_callback('/([A-Z]{1})/', function ($matches) {
            return '_'.strtolower($matches[0]);
        }, $str);

        return $str;
    }
}


if(!function_exists('convertUnderline')){
    /**
     * 下划线转驼峰.
     *
     * @param [type] $str
     */
    function convertUnderline($str)
    {
        $str = preg_replace_callback('/([-_]+([a-z]{1}))/i', function ($matches) {
            return strtoupper($matches[2]);
        }, $str);

        return $str;
    }
}


if(!function_exists('in_string')){
    /**
     * 判断一个字符串是否包含另一个字符串.
     */
    function in_string($needle, $string)
    {
        return strpos($string, $needle) !== false;
    }
}


if(!function_exists('now')){
    /**
     * 格式化的当前时间.
     */
    function now()
    {
        // 获取userId
        if (isset($GLOBALS['_now'])) {
            return $GLOBALS['_now'];
        }

        $now = date('Y-m-d H:i:s');

        // 存储UserId
        $GLOBALS['_now'] = $now;

        return $now;
    }
}


if(!function_exists('encrypt')){
    /**
     * 字符加密，一次一密,可定时解密有效
     *
     * @param string $string 原文
     * @param string $key 密钥
     * @param int $expiry 密文有效期,单位s,0 为永久有效
     * @return string 加密后的内容
     */
    function encrypt($string,$key = 'kls8in1e', $expiry = 0){
        $string = serialize($string);
        $ckeyLength = 4;
        $keya = md5(substr($key, 0, 16));         //做数据完整性验证
        $keyb = md5(substr($key, 16, 16));         //用于变化生成的密文 (初始化向量IV)
        $keyc = substr(md5(microtime()), - $ckeyLength);
        $cryptkey = $keya . md5($keya . $keyc);
        $keyLength = strlen($cryptkey);
        $string = sprintf('%010d', $expiry ? $expiry + time() : 0).substr(md5($string . $keyb), 0, 16) . $string;
        $stringLength = strlen($string);
        $rndkey = array();
        for($i = 0; $i <= 255; $i++) {
            $rndkey[$i] = ord($cryptkey[$i % $keyLength]);
        }
        $box = range(0, 255);
        // 打乱密匙簿，增加随机性
        for($j = $i = 0; $i < 256; $i++) {
            $j = ($j + $box[$i] + $rndkey[$i]) % 256;
            $tmp = $box[$i];
            $box[$i] = $box[$j];
            $box[$j] = $tmp;
        }
        // 加解密，从密匙簿得出密匙进行异或，再转成字符
        $result = '';
        for($a = $j = $i = 0; $i < $stringLength; $i++) {
            $a = ($a + 1) % 256;
            $j = ($j + $box[$a]) % 256;
            $tmp = $box[$a];
            $box[$a] = $box[$j];
            $box[$j] = $tmp;
            $result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
        }
        $result = $keyc . str_replace('=', '', base64_encode($result));
        $result = str_replace(array('+', '/', '='),array('-', '_', '.'), $result);
        return $result;
    }
}


if(!function_exists('decrypt')){
    /**
     * 字符解密，一次一密,可定时解密有效
     *
     * @param string $string 密文
     * @param string $key 解密密钥
     * @return string 解密后的内容
     */
    function decrypt($string,$key = 'kls8in1e')
    {
        $string = str_replace(array('-', '_', '.'),array('+', '/', '='), $string);
        $ckeyLength = 4;
        $keya = md5(substr($key, 0, 16));         //做数据完整性验证
        $keyb = md5(substr($key, 16, 16));         //用于变化生成的密文 (初始化向量IV)
        $keyc = substr($string, 0, $ckeyLength);
        $cryptkey = $keya . md5($keya . $keyc);
        $keyLength = strlen($cryptkey);
        $string = base64_decode(substr($string, $ckeyLength));
        $stringLength = strlen($string);
        $rndkey = array();
        for($i = 0; $i <= 255; $i++) {
            $rndkey[$i] = ord($cryptkey[$i % $keyLength]);
        }
        $box = range(0, 255);
        // 打乱密匙簿，增加随机性
        for($j = $i = 0; $i < 256; $i++) {
            $j = ($j + $box[$i] + $rndkey[$i]) % 256;
            $tmp = $box[$i];
            $box[$i] = $box[$j];
            $box[$j] = $tmp;
        }
        // 加解密，从密匙簿得出密匙进行异或，再转成字符
        $result = '';
        for($a = $j = $i = 0; $i < $stringLength; $i++) {
            $a = ($a + 1) % 256;
            $j = ($j + $box[$a]) % 256;
            $tmp = $box[$a];
            $box[$a] = $box[$j];
            $box[$j] = $tmp;
            $result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
        }
        if ((substr($result, 0, 10) == 0 || substr($result, 0, 10) - time() > 0)
        && substr($result, 10, 16) == substr(md5(substr($result, 26) . $keyb), 0, 16)
        ) {
            return unserialize(substr($result, 26));
        } else {
            return '';
        }
    }
}
