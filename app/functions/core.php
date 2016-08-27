<?php
use \Phalcon\DI;

/**
 * 公共方法
 */

/**
 * 加载配置文件数据
 *
 *     config('database')
 *     config('database.default.adapter')
 *
 * @param  string $name
 * @return mixed
 */
function config($name)
{
    static $cached = array();

    // 移除多余的分隔符
    $name = trim($name, '.');

    if (!isset($cached[$name])) {
        $filename = $name;

        // 获取配置名及路径
        if (strpos($name, '.') !== false) {
            $paths = explode('.', $name);
            $filename = array_shift($paths);
        }

        // 查找配置文件
        $file = APP_PATH . '/config/' . $filename . '.php';
        if (!is_file($file)) {
            return null;
        }

        // 从文件中加载配置数据
        $data = include_once $file;

        if (is_array($data)) {
            $data = new Phalcon\Config($data);
        }
        // 缓存文件数据
        $cached[$filename] = $data;

        // 支持路径方式获取配置，例如：config('file.key.subkey')
        if (isset($paths)) {
            foreach ($paths as $key) {
                if (is_array($data) && isset($data[$key])) {
                    $data = $data[$key];
                } elseif (is_object($data) && isset($data->{$key})) {
                    $data = $data->{$key};
                } else {
                    $data = null;
                }
            }
        }

        // 缓存数据
        $cached[$name] = $data;
    }

    return $cached[$name];
}

/**
 * 简化 Phalcon\Di::getDefault()->getShared($S)
 *
 *     S('url')
 *     S('db')
 *     ...
 *
 * @see    http://docs.phalconphp.com/en/latest/api/Phalcon_DI.html
 * @param  string $service
 * @return object
 */
function S($service)
{
    return Phalcon\DI::getDefault()->getShared($service);
}

/**
 * 获取完整的 url 地址
 *
 * @see    http://docs.phalconphp.com/en/latest/api/Phalcon_Mvc_Url.html
 * @param  string $uri
 * @return string
 */
function url($uri = null)
{
    return S('url')->get($uri);
}

/**
 * 获取静态资源地址
 *
 * @see    http://docs.phalconphp.com/en/latest/api/Phalcon_Mvc_Url.html
 * @param  string $uri
 * @return string
 */
function static_url($uri = null)
{
    return S('url')->getStatic($uri);
}

/**
 * 获取包含域名在内的 url
 *
 * @param  string $uri
 * @param  string $base
 * @return string
 */
function baseurl($uri = null, $base = HTTP_BASE)
{
    return HTTP_BASE . ltrim($uri, '/');
}

/**
 * 根据 query string 参数生成 url
 *
 *     url_param('item/list', array('page' => 1)) // item/list?page=1
 *     url_param('item/list?page=1', array('limit' => 10)) // item/list?page=1&limit=10
 *
 * @param  string $uri
 * @param  array $params
 * @return string
 */
function url_param($uri, array $params)
{
    return $uri . (strpos($uri, '?') ? '&' : '?') . http_build_query(array_unique($params));
}

/**
 * 获取视图内容
 *
 * @see    http://docs.phalconphp.com/en/latest/api/Phalcon_Mvc_View.html
 * @return string
 */
function get_content()
{
    return S('view')->getContent();
}

/**
 * 判断视图是否存在
 *
 * @param  string $viewFile
 * @param  string|array $suffixes
 * @return boolean
 */
function has_view($viewFile, $suffixes = null)
{
    $file = S('view')->getViewsDir() . $viewFile;

    if ($suffixes === null) {
        $suffixes = array('phtml', 'volt');
    } elseif (!is_array($suffixes)) {
        $suffixes = array($suffixes);
    }

    foreach ($suffixes as $suffix) {
        if (is_file($file . '.' . $suffix)) {
            return true;
        }
    }

    return false;
}

/**
 * 加载局部视图
 *
 * @see    http://docs.phalconphp.com/en/latest/api/Phalcon_Mvc_View.html
 * @param  string $partialPath
 * @param  array $params
 * @return string
 */
function partial_view($partialPath, array $params = null)
{
    return S('view')->partial($partialPath, $params);
}

/**
 * 选择不同的视图来渲染，并做为最后的 controller/action 输出
 *
 * @see    http://docs.phalconphp.com/en/latest/api/Phalcon_Mvc_View.html
 * @param  string $renderView
 * @return string
 */
function pick_view($renderView)
{
    return S('view')->pick($renderView);
}


/**
 * 简化三元表达式
 *
 * @param $boolValue
 * @param  mixed $trueValue
 * @param  mixed $falseValue
 * @return mixed
 * @internal param $boolean $boolValue
 */
function on($boolValue, $trueValue, $falseValue = null)
{
    return $boolValue ? $trueValue : $falseValue;
}

/**
 * 返回格式化的 json 数据
 *
 * @param  array $array
 * @param  boolean $pretty 美化 json 数据
 * @param  boolean $unescaped 关闭 Unicode 编码
 * @return string
 */
function json_it(array $array, $pretty = true, $unescaped = true)
{
    // php 5.4+
    if (defined('JSON_PRETTY_PRINT') && defined('JSON_UNESCAPED_UNICODE')) {
        if ($pretty && $unescaped)
            $options = JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE;
        elseif ($pretty)
            $options = JSON_PRETTY_PRINT;
        elseif ($unescaped)
            $options = JSON_UNESCAPED_UNICODE;
        else
            $options = null;

        return json_encode($array, $options);
    }

    if ($unescaped) {
        // convmap since 0x80 char codes so it takes all multibyte codes (above ASCII 127).
        // So such characters are being "hidden" from normal json_encoding
        $tmp = array();
        array_walk_recursive($array, function (&$item, $key) {
            if (is_string($item)) {
                $item = mb_encode_numericentity($item, array(0x80, 0xffff, 0, 0xffff), 'UTF-8');
            }
        });
        $json = mb_decode_numericentity(json_encode($array), array(0x80, 0xffff, 0, 0xffff), 'UTF-8');
    } else {
        $json = json_encode($array);
    }

    if ($pretty) {
        $result = '';
        $pos = 0;
        $strLen = strlen($json);
        $indentStr = "\t";
        $newLine = "\n";
        $prevChar = '';
        $outOfQuotes = true;

        for ($i = 0; $i <= $strLen; $i++) {

            // Grab the next character in the string.
            $char = substr($json, $i, 1);

            // Are we inside a quoted string
            if ($char == '"' AND $prevChar != '\\') {
                $outOfQuotes = !$outOfQuotes;

                // If this character is the end of an element,
                // output a new line and indent the next line.
            } elseif (($char == '}' OR $char == ']') AND $outOfQuotes) {
                $result .= $newLine;
                $pos--;
                for ($j = 0; $j < $pos; $j++) {
                    $result .= $indentStr;
                }
            }

            // Add the character to the result string.
            $result .= $char;

            // If the last character was the beginning of an element,
            // output a new line and indent the next line.
            if (($char == ',' OR $char == '{' OR $char == '[') AND $outOfQuotes) {
                $result .= $newLine;
                if ($char == '{' OR $char == '[') {
                    $pos++;
                }

                for ($j = 0; $j < $pos; $j++) {
                    $result .= $indentStr;
                }
            }

            $prevChar = $char;
        }

        $json = $result;
    }

    return $json;
}

/**
 * 简化日志写入方法
 *
 * @see    http://docs.phalconphp.com/en/latest/api/Phalcon_Logger.html
 * @see    http://docs.phalconphp.com/en/latest/api/Phalcon_Logger_Adapter_File.html
 * @param  string $name 日志名称
 * @param  string $message 日志内容
 * @param  string $type 日志类型
 * @param  boolean $addUrl 记录当前 url
 * @return Phalcon\Logger\Adapter\File
 */
function write_log($name, $message, $type = null, $addUrl = false)
{
    static $logger, $formatter;

    if (!isset($logger[$name])) {
        $logfile = DATA_PATH . '/logs/' . date('/Ym/') . $name . '_' . date('Ymd') . '.log';
        if (!is_dir(dirname($logfile))) {
            mkdir(dirname($logfile), 0755, true);
        }

        $logger[$name] = new Phalcon\Logger\Adapter\File($logfile);

        // Set the logger format
        if ($formatter === null) {
            $formatter = new Phalcon\Logger\Formatter\Line();
            $formatter->setDateFormat('Y-m-d H:i:s O');
        }

        $logger[$name]->setFormatter($formatter);
    }

    if ($type === null) {
        $type = Phalcon\Logger::INFO;
    }

    if ($addUrl) {
        $logger[$name]->log('URL: ' . HTTP_URL, Phalcon\Logger::INFO);
    }

    $logger[$name]->log($message, $type);

    return $logger[$name];
}

/**
 * Email格式检查 (支持验证host有效性)
 *
 * @param  string $email
 * @param  boolean $testMX
 * @return boolean
 */
function is_email($email, $testMX = false)
{
    if (preg_match('/^([_a-z0-9+-]+)(\.[_a-z0-9-]+)*@([a-z0-9-]+)(\.[a-z0-9-]+)*(\.[a-z]{2,4})$/i', $email)) {
        if ($testMX) {
            list(, $domain) = explode("@", $email);

            return getmxrr($domain, $mxrecords);
        }

        return true;
    }

    return false;
}

/**
 * 检查是否效的 url
 *
 * @param  string $url
 * @return boolean
 */
function is_url($url)
{
    return preg_match('/^https?:\/\/([a-z0-9\-]+\.)+[a-z]{2,3}([a-z0-9_~#%&\/\'\+\=\:\?\.\-])*$/i', $url);
}

/**
 * CURL POST 请求
 *
 * @param  string $url
 * @param array $headers
 * @param  array $postdata
 * @param  array $curl_opts
 * @param string $cookie
 * @return string
 */
function curl_post($url, array $headers = null, array $postdata = null, array $curl_opts = null, $cookie = '')
{
    $ch = curl_init();

    if ($postdata !== null) {
        $postdata = http_build_query($postdata);
    }

    curl_setopt_array($ch, array(
        CURLOPT_TIMEOUT => 10,
        CURLOPT_URL => $url,
        CURLOPT_SSL_VERIFYPEER => FALSE,
        CURLOPT_SSL_VERIFYHOST => FALSE,
        CURLOPT_POST => TRUE,
        CURLOPT_HEADER => TRUE,
        CURLOPT_POSTFIELDS => $postdata,
        CURLOPT_COOKIE => $cookie,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_USERAGENT => 'WuJie API PHP Web Client/1.0.0',
        CURLOPT_RETURNTRANSFER => TRUE,
    ));

    if ($curl_opts !== null) {
        curl_setopt_array($ch, $curl_opts);
    }

    $result = curl_exec($ch);
    curl_close($ch);

    return $result;
}

function httpPost($url, $data, $cookie = '')
{
    if ($url == '' || !is_array($data)) {
        throw new Exception(sprintf("parms error ."));
    }
    $ch = @curl_init();
    if (!$ch) {
        throw new Exception(sprintf("curl_init error for url %s.", $url));
    }
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    //curl_setopt($ch, CURLOPT_HEADER, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    //设置curl默认访问为IPv4
    if (defined('CURLOPT_IPRESOLVE') && defined('CURL_IPRESOLVE_V4')) {
        curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
    }
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSLVERSION, 1);
    curl_setopt($ch, CURLOPT_COOKIE, $cookie);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_USERAGENT, 'HS SHOP API PHP Web Client/1.0.0 (xiongyan)');
    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
}

/**
 * CURL GET 请求
 *
 * @param  string $url
 * @param array $headers
 * @param  array $curl_opts
 * @return string
 */
function curl_get($url, array $headers = null, array $curl_opts = null)
{
    $ch = curl_init();

    curl_setopt_array($ch, array(
        CURLOPT_TIMEOUT => 10,
        CURLOPT_URL => $url,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_USERAGENT => 'WuJie API PHP Web Client/1.0.0',
        CURLOPT_RETURNTRANSFER => 1,
    ));

    if ($curl_opts !== null) {
        curl_setopt_array($ch, $curl_opts);
    }

    $result = curl_exec($ch);
    curl_close($ch);

    return $result;
}

/**
 * 设置 cookie 值
 *
 * @param string $name
 * @param mixed $value
 * @param integer $lifetime
 */
function cookie_set($name, $value, $lifetime = null)
{
    return S('cookies')->set($name, $value, $lifetime);
}

/**
 * 获取 cookie 值
 *
 * @param  string $name
 * @param  mixed $default
 * @return mixed
 */
function cookie_get($name, $default = null)
{
    return S('cookies')->get($name, $default);
}

/**
 * 删除 cookie
 *
 * @param  string $name
 * @return boolean
 */
function cookie_delete($name)
{
    return S('cookies')->delete($name);
}

/**
 * 设置 session 值
 *
 * @see   http://docs.phalconphp.com/en/latest/reference/session.html
 * @see   http://docs.phalconphp.com/en/latest/api/Phalcon_Session_AdapterInterface.html
 * @param string $name
 * @param mixed $value
 */
function session_set($name, $value)
{
    return S('session')->set($name, $value);
}

/**
 * 获取 session 值
 *
 * @param  string $name
 * @param  mixed $default
 * @return mixed
 */
function session_get($name, $default = null)
{
    return S('session')->get($name, $default);
}

/**
 * 删除 session
 *
 * @param  string $name
 * @return boolean
 */
function session_delete($name)
{
    return S('session')->remove($name);
}

/**
 * 按指定的长度切割字符串
 *
 * @param  string $string 需要切割的字符串
 * @param  integer $length 长度
 * @param  string $suffix 切割后补充的字符串
 * @return string
 */
function str_break($string, $length, $suffix = '')
{
    if (strlen($string) <= $length + strlen($suffix)) {
        return $string;
    }

    $n = $tn = $noc = 0;
    while ($n < strlen($string)) {
        $t = ord($string[$n]);
        if ($t == 9 || $t == 10 || (32 <= $t && $t <= 126)) {
            $tn = 1;
            $n++;
            $noc++;
        } elseif (194 <= $t && $t <= 223) {
            $tn = 2;
            $n += 2;
            $noc += 2;
        } elseif (224 <= $t && $t < 239) {
            $tn = 3;
            $n += 3;
            $noc += 2;
        } elseif (240 <= $t && $t <= 247) {
            $tn = 4;
            $n += 4;
            $noc += 2;
        } elseif (248 <= $t && $t <= 251) {
            $tn = 5;
            $n += 5;
            $noc += 2;
        } elseif ($t == 252 || $t == 253) {
            $tn = 6;
            $n += 6;
            $noc += 2;
        } else {
            $n++;
        }
        if ($noc >= $length) {
            break;
        }
    }
    $noc > $length && $n -= $tn;
    $strcut = substr($string, 0, $n);
    if (strlen($strcut) < strlen($string)) {
        $strcut .= $suffix;
    }

    return $strcut;
}

/**
 * 字符串高亮
 *
 * @param  string $string 需要的高亮的字符串
 * @param  mixed $keyword 关键字，可以是一个数组
 * @return string
 */
function highlight_keyword($string, $keyword)
{
    $string = (string)$string;

    if ($string && $keyword) {
        if (!is_array($keyword)) {
            $keyword = array($keyword);
        }

        $pattern = array();
        foreach ($keyword as $word) {
            if (!empty($word)) {
                $pattern[] = '(' . str_replace('/', '\/', preg_quote($word)) . ')';
            }
        }

        if (!empty($pattern)) {
            $string = preg_replace(
                '/(' . implode('|', $pattern) . ')/is',
                '<span style="background:#FF0;color:#E00;">\\1</span>',
                $string
            );
        }
    }

    return $string;
}

/**
 * 将 HTML 转换为文本
 *
 * @param  string $html
 * @return string
 */
function html2txt($html)
{
    $html = trim($html);
    if (empty($html))
        return $html;
    $search = array("'<script[^>]*?>.*?</script>'si",
        "'<style[^>]*?>.*?</style>'si",
        "'<[\/\!]*?[^<>]*?>'si",
        "'([\r\n])[\s]+'",
        "'&(quot|#34);'i",
        "'&(amp|#38);'i",
        "'&(lt|#60);'i",
        "'&(gt|#62);'i",
        "'&(nbsp|#160)[;]*'i",
        "'&(iexcl|#161);'i",
        "'&(cent|#162);'i",
        "'&(pound|#163);'i",
        "'&(copy|#169);'i",
        "'&#(\d+);'e"
    );
    $replace = array("", "", "", "\\1", "\"", "&", "<", ">", " ",
        chr(161), chr(162), chr(163), chr(169), "chr(\\1)");

    return preg_replace($search, $replace, $html);
}

/**
 * 递归地合并一个或多个数组(不同于 array_merge_recursive)
 *
 * @return array
 */
function array_merge_deep()
{
    $a = func_get_args();
    for ($i = 1; $i < count($a); $i++) {
        foreach ($a[$i] as $k => $v) {
            if (isset($a[0][$k])) {
                if (is_array($v)) {
                    if (is_array($a[0][$k])) {
                        $a[0][$k] = array_merge_deep($a[0][$k], $v);
                    } else {
                        $v[] = $a[0][$k];
                        $a[0][$k] = $v;
                    }
                } else {
                    $a[0][$k] = is_array($a[0][$k]) ? array_merge($a[0][$k], array($v)) : $v;
                }
            } else {
                $a[0][$k] = $v;
            }
        }
    }

    return $a[0];
}

/**
 * Lowercase the first character of each word in a string
 *
 * @param  string $string
 * @return string
 */
function lcwords($string)
{
    $tokens = explode(' ', $string);
    if (!is_array($tokens) || count($tokens) <= 1) {
        return lcfirst($string);
    }

    $result = array();
    foreach ($tokens as $token) {
        $result[] = lcfirst($token);
    }

    return implode(' ', $result);
}

/**
 *  打印
 * @param null $var
 * @param bool $vardump
 */
function pr($var = null, $vardump = false)
{
    echo '<pre>';
    if ($vardump) {
        var_dump($var);
    } else {
        print_r($var);
    }
}

function pe($var = null, $vardump = false)
{
    echo '<pre>';
    if ($vardump) {
        exit(var_dump($var));
    } else {
        exit(print_r($var));
    }
}

/**
 * 不显示信息直接跳转
 *
 * @param string $url
 */
function redirect($url = '')
{
    if (empty($url)) {
        if (!empty($_REQUEST['ref_url'])) {
            $url = $_REQUEST['ref_url'];
        } else {
            $url = getReferer();
        }
    }
    header('Location: ' . $url);
    exit();
}

/**
 * 取上一步来源地址
 *
 * @param
 * @return string 字符串类型的返回结果
 */
function getReferer()
{
    return empty($_SERVER['HTTP_REFERER']) ? '' : $_SERVER['HTTP_REFERER'];
}

/**
 * 取得IP
 *
 *
 * @return string 字符串类型的返回结果
 */
function getIp()
{
    if (getenv('HTTP_CLIENT_IP') && strcasecmp(getenv('HTTP_CLIENT_IP'), 'unknown')) {
        $ip = getenv('HTTP_CLIENT_IP');
    } elseif (getenv('HTTP_X_FORWARDED_FOR') && strcasecmp(getenv('HTTP_X_FORWARDED_FOR'), 'unknown')) {
        $ip = getenv('HTTP_X_FORWARDED_FOR');
    } elseif (getenv('REMOTE_ADDR') && strcasecmp(getenv('REMOTE_ADDR'), 'unknown')) {
        $ip = getenv('REMOTE_ADDR');
    } elseif (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], 'unknown')) {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    return preg_match('/[\d\.]{7,15}/', $ip, $matches) ? $matches [0] : '';
}


/**
 * 读取目录列表
 * 不包括 . .. 文件 三部分
 *
 * @param string $path 路径
 * @return array 数组格式的返回结果
 */
function readDirList($path)
{
    if (is_dir($path)) {
        $handle = @opendir($path);
        $dir_list = array();
        if ($handle) {
            while (false !== ($dir = readdir($handle))) {
                if ($dir != '.' && $dir != '..' && is_dir($path . DS . $dir)) {
                    $dir_list[] = $dir;
                }
            }
            return $dir_list;
        } else {
            return false;
        }
    } else {
        return false;
    }
}

/**
 * 获取目录大小
 *
 * @param string $path 目录
 * @param int $size 目录大小
 * @return int 整型类型的返回结果
 */
function getDirSize($path, $size = 0)
{
    $dir = @dir($path);
    if (!empty($dir->path) && !empty($dir->handle)) {
        while ($filename = $dir->read()) {
            if ($filename != '.' && $filename != '..') {
                if (is_dir($path . DS . $filename)) {
                    $size += getDirSize($path . DS . $filename);
                } else {
                    $size += filesize($path . DS . $filename);
                }
            }
        }
    }
    return $size ? $size : 0;
}

/**
 * 删除缓存目录下的文件或子目录文件
 *
 * @param string $dir 目录名或文件名
 * @return boolean
 */
function delCacheFile($dir)
{
    //防止删除cache以外的文件
    if (strpos($dir, '..') !== false) return false;
    $path = BASE_DATA_PATH . DS . 'cache' . DS . $dir;
    if (is_dir($path)) {
        $file_list = array();
        readFileList($path, $file_list);
        if (!empty($file_list)) {
            foreach ($file_list as $v) {
                if (basename($v) != 'index.html') @unlink($v);
            }
        }
    } else {
        if (basename($path) != 'index.html') @unlink($path);
    }
    return true;
}

/**
 * 获取文件列表(所有子目录文件)
 *
 * @param string $path 目录
 * @param array $file_list 存放所有子文件的数组
 * @param array $ignore_dir 需要忽略的目录或文件
 * @return array 数据格式的返回结果
 */
function readFileList($path, &$file_list, $ignore_dir = array())
{
    $path = rtrim($path, '/');
    if (is_dir($path)) {
        $handle = @opendir($path);
        if ($handle) {
            while (false !== ($dir = readdir($handle))) {
                if ($dir != '.' && $dir != '..') {
                    if (!in_array($dir, $ignore_dir)) {
                        if (is_file($path . DS . $dir)) {
                            $file_list[] = $path . DS . $dir;
                        } elseif (is_dir($path . DS . $dir)) {
                            readFileList($path . DS . $dir, $file_list, $ignore_dir);
                        }
                    }
                }
            }
            @closedir($handle);
//			return $file_list;
        } else {
            return false;
        }
    } else {
        return false;
    }
}

/**
 * 字符串切割函数，一个字母算一个位置,一个字算2个位置
 *
 * @param string $string 待切割的字符串
 * @param int $length 切割长度
 * @param string $dot 尾缀
 * @return string
 */
function str_cut($string, $length, $dot = '')
{
    $string = str_replace(array('&nbsp;', '&amp;', '&quot;', '&#039;', '&ldquo;', '&rdquo;', '&mdash;', '&lt;', '&gt;', '&middot;', '&hellip;'), array(' ', '&', '"', "'", '“', '”', '—', '<', '>', '·', '…'), $string);
    $strlen = strlen($string);
    if ($strlen <= $length) return $string;
    $maxi = $length - strlen($dot);
    $strcut = '';
    if (strtolower(CHARSET) == 'utf-8') {
        $n = $tn = $noc = 0;
        while ($n < $strlen) {
            $t = ord($string[$n]);
            if ($t == 9 || $t == 10 || (32 <= $t && $t <= 126)) {
                $tn = 1;
                $n++;
                $noc++;
            } elseif (194 <= $t && $t <= 223) {
                $tn = 2;
                $n += 2;
                $noc += 2;
            } elseif (224 <= $t && $t < 239) {
                $tn = 3;
                $n += 3;
                $noc += 2;
            } elseif (240 <= $t && $t <= 247) {
                $tn = 4;
                $n += 4;
                $noc += 2;
            } elseif (248 <= $t && $t <= 251) {
                $tn = 5;
                $n += 5;
                $noc += 2;
            } elseif ($t == 252 || $t == 253) {
                $tn = 6;
                $n += 6;
                $noc += 2;
            } else {
                $n++;
            }
            if ($noc >= $maxi) break;
        }
        if ($noc > $maxi) $n -= $tn;
        $strcut = substr($string, 0, $n);
    } else {
        $dotlen = strlen($dot);
        $maxi = $length - $dotlen;
        for ($i = 0; $i < $maxi; $i++) {
            $strcut .= ord($string[$i]) > 127 ? $string[$i] . $string[++$i] : $string[$i];
        }
    }
    $strcut = str_replace(array('&', '"', "'", '<', '>'), array('&amp;', '&quot;', '&#039;', '&lt;', '&gt;'), $strcut);
    return $strcut . $dot;
}

/**
 * unicode转为utf8
 * @param string $str 待转的字符串
 * @return string
 */
function unicodeToUtf8($str, $order = "little")
{
    $utf8string = "";
    $n = strlen($str);
    for ($i = 0; $i < $n; $i++) {
        if ($order == "little") {
            $val = str_pad(dechex(ord($str[$i + 1])), 2, 0, STR_PAD_LEFT) .
                str_pad(dechex(ord($str[$i])), 2, 0, STR_PAD_LEFT);
        } else {
            $val = str_pad(dechex(ord($str[$i])), 2, 0, STR_PAD_LEFT) .
                str_pad(dechex(ord($str[$i + 1])), 2, 0, STR_PAD_LEFT);
        }
        $val = intval($val, 16); // 由于上次的.连接，导致$val变为字符串，这里得转回来。
        $i++; // 两个字节表示一个unicode字符。
        $c = "";
        if ($val < 0x7F) { // 0000-007F
            $c .= chr($val);
        } elseif ($val < 0x800) { // 0080-07F0
            $c .= chr(0xC0 | ($val / 64));
            $c .= chr(0x80 | ($val % 64));
        } else { // 0800-FFFF
            $c .= chr(0xE0 | (($val / 64) / 64));
            $c .= chr(0x80 | (($val / 64) % 64));
            $c .= chr(0x80 | ($val % 64));
        }
        $utf8string .= $c;
    }
    /* 去除bom标记 才能使内置的iconv函数正确转换 */
    if (ord(substr($utf8string, 0, 1)) == 0xEF && ord(substr($utf8string, 1, 2)) == 0xBB && ord(substr($utf8string, 2, 1)) == 0xBF) {
        $utf8string = substr($utf8string, 3);
    }
    return $utf8string;
}

/*
* 重写$_SERVER['REQUREST_URI']
*/
function request_uri()
{
    if (isset($_SERVER['REQUEST_URI'])) {
        $uri = $_SERVER['REQUEST_URI'];
    } else {
        if (isset($_SERVER['argv'])) {
            $uri = $_SERVER['PHP_SELF'] . '?' . $_SERVER['argv'][0];
        } else {
            $uri = $_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING'];
        }
    }
    return $uri;
}


// 记录和统计时间（微秒）
function addUpTime($start, $end = '', $dec = 3)
{
    static $_info = array();
    if (!empty($end)) { // 统计时间
        if (!isset($_info[$end])) {
            $_info[$end] = microtime(TRUE);
        }
        return number_format(($_info[$end] - $_info[$start]), $dec);
    } else { // 记录时间
        $_info[$start] = microtime(TRUE);
    }
}

/**
 * 取得系统配置信息
 *
 * @param string $key 取得下标值
 * @return mixed
 */
function C($key)
{
    if (strpos($key, '.')) {
        $key = explode('.', $key);
        $value = $GLOBALS['setting_config'][$key[0]];
        if (isset($key[2])) {
            return $value[$key[1]][$key[2]];
        } else {
            return $value[$key[1]];
        }
    } else {
        return $GLOBALS['setting_config'][$key];
    }
}


/**
 * 加载文件
 *
 * 使用require_once函数，只适用于加载框架内类库文件
 * 如果文件名中包含"_"使用"#"代替
 *
 * @example import('cache'); //require_once(BASE_PATH.'/framework/libraries/cache.php');
 * @example import('libraries.cache');    //require_once(BASE_PATH.'/framework/libraries/cache.php');
 * @example import('function.core');    //require_once(BASE_PATH.'/framework/function/core.php');
 * @example import('.control.adv')    //require_once(BASE_PATH.'/control/adv.php');
 *
 * @param 要加载的文件 $libname
 * @param string|文件扩展名 $file_ext
 */
function import($libname, $file_ext = '.php')
{
    //替换为目录符号/
    if (strstr($libname, '.')) {
        $path = str_replace('.', '/', $libname);
    } else {
        $path = 'library/' . $libname;
    }
    // 基准目录，如果是顶级目录
    if (substr($libname, 0, 1) == '.') {
        $base_dir = APP_PATH . '/';
        $path = ltrim(str_replace('library/', '', $path), '/');
    } else {
        $base_dir = APP_PATH . '/functions/';
    }
    //如果文件名中含有.使用#代替
    if (strstr($path, '#')) {
        $path = str_replace('#', '.', $path);
    }
    //返回安全路径
    if (preg_match('/^[\w\d\/_.]+$/i', $path)) {
        $file = realpath($base_dir . $path . $file_ext);
    } else {
        $file = false;
    }
    echo $file;
    if (!$file) {
        exit($path . $file_ext . ' isn\'t exists!');
    } else {
        require_once($file);
    }

}

/**
 * 取得随机数
 *
 * @param int $length 生成随机数的长度
 * @param int $numeric 是否只产生数字随机数 1是0否
 * @return string
 */
function random($length, $numeric = 0)
{
    $seed = base_convert(md5(microtime() . $_SERVER['DOCUMENT_ROOT']), 16, $numeric ? 10 : 35);
    $seed = $numeric ? (str_replace('0', '', $seed) . '012340567890') : ($seed . 'zZ' . strtoupper($seed));
    $hash = '';
    $max = strlen($seed) - 1;
    for ($i = 0; $i < $length; $i++) {
        $hash .= $seed{mt_rand(0, $max)};
    }
    return $hash;
}


/**
 * 文件数据读取和保存 字符串、数组
 *
 * @param string $name 文件名称（不含扩展名）
 * @param mixed $value 待写入文件的内容
 * @param string $path 写入cache的目录
 * @param string $ext 文件扩展名
 * @return mixed
 */
function F($name, $value = null, $path = 'cache', $ext = '.php')
{
    if (strtolower(substr($path, 0, 5)) == 'cache') {
        $path = 'data/' . $path;
    }
    static $_cache = array();
    if (isset($_cache[$name . $path])) return $_cache[$name . $path];
    $filename = BASE_ROOT_PATH . '/' . $path . '/' . $name . $ext;
    if (!is_null($value)) {
        $dir = dirname($filename);
        if (!is_dir($dir)) mkdir($dir);
        return write_file($filename, $value);
    }

    if (is_file($filename)) {
        $_cache[$name . $path] = $value = include $filename;
    } else {
        $value = false;
    }
    return $value;
}

/**
 * 内容写入文件
 *
 * @param string $filepath 待写入内容的文件路径
 * @param string /array $data 待写入的内容
 * @param  string $mode 写入模式，如果是追加，可传入“append”
 * @return bool
 */
function write_file($filepath, $data, $mode = null)
{
    if (!is_array($data) && !is_scalar($data)) {
        return false;
    }

    $data = var_export($data, true);

    $data = "<?php defined('IN_ROOT') or exit('Access Invalid!'); return " . $data . ";";
    $mode = $mode == 'append' ? FILE_APPEND : null;
    if (false === file_put_contents($filepath, ($data), $mode)) {
        return false;
    } else {
        return true;
    }
}

/**
 * 循环创建目录
 *
 * @param string $dir 待创建的目录
 * @param string|权限 $mode 权限
 * @return bool
 */
function mk_dir($dir, $mode = '0777')
{
    if (is_dir($dir) || @mkdir($dir, $mode))
        return true;
    if (!mk_dir(dirname($dir), $mode))
        return false;
    return @mkdir($dir, $mode);
}

/**
 * 实例化一个没有模型文件的Model
 * @param string $name Model名称 支持指定基础模型 例如 MongoModel:User
 * @param string $tablePrefix 表前缀
 * @param mixed $connection 数据库连接信息
 * @return Model
 */
function M($name = '', $tablePrefix = '', $connection = '')
{
    static $_model = array();
    if (strpos($name, ':')) {
        list($class, $name) = explode(':', $name);
    } else {
        $class = 'Engine\\AbstractModel';
    }

    $guid = (is_array($connection) ? implode('', $connection) : $connection) . $tablePrefix . $name . '_' . $class;
    if (!isset($_model[$guid]))
        $_model[$guid] = new $class();
    return $_model[$guid];
}

function K($params)
{
    $comparison = array('eq' => '=', 'neq' => '<>', 'gt' => '>', 'egt' => '>=', 'lt' => '<', 'elt' => '<=', 'notlike' => 'NOT LIKE', 'like' => 'LIKE');

    if (is_array($params)) {
        $condition = '';
        $t = '=';
        foreach ($params as $key => $var) {
            if (is_string($var[0])) {
                if (preg_match('/^(EQ|NEQ|GT|EGT|LT|ELT|NOTLIKE|LIKE)$/i', $var[0])) { // 比较运算
                    $t = $comparison[strtolower($var[0])];
                }

            }
            $condition .= $key . ' ' . $t . ' :' . $key . ': AND ';
        }
        return substr($condition, 0, strlen($condition) - 4);
    } else {
        return $params;
    }
}

function V($params)
{
    $comparison = array('eq' => '=', 'neq' => '<>', 'gt' => '>', 'egt' => '>=', 'lt' => '<', 'elt' => '<=', 'notlike' => 'NOT LIKE', 'like' => 'LIKE');

    if (is_array($params)) {
        $arr = array();
        foreach ($params as $key => $var) {
            if (is_string($var[0])) {
                if (preg_match('/^(EQ|NEQ|GT|EGT|LT|ELT|NOTLIKE|LIKE)$/i', $var[0])) { // 比较运算
                    $arr[$key] = $var[1];
                } else $arr[$key] = $var;
            } else  $arr[$key] = $var;
        }
        return $arr;
    } else {
        return $params;
    }
}

/**
 * 通知邮件/通知消息 内容转换函数
 *
 * @param string $message 内容模板
 * @param array $param 内容参数数组
 * @return string 通知内容
 */
function ncReplaceText($message, $param)
{
    if (!is_array($param)) return false;
    foreach ($param as $k => $v) {
        $message = str_replace('{$' . $k . '}', $v, $message);
    }
    return $message;
}

/**
 * 抛出异常
 *
 * @param string $error 异常信息
 */
function throw_exception($error)
{
    if (!defined('IGNORE_EXCEPTION')) {
        showMessage($error, '', 'exception');
    } else {
        exit();
    }
}

/**
 * 输出错误信息
 *
 * @param string $error 错误信息
 */
function halt($error)
{
    showMessage($error, '', 'exception');
}

/**
 * 读/写 缓存方法
 *
 * H('key') 取得缓存
 * H('setting',true) 生成缓存并返回缓存结果
 * H('key',null) 清空缓存
 * H('setting',true,'file') 生成商城配置信息的文件缓存
 * H('setting',true,'memcache') 生成商城配置信息到memcache
 * @param string $key 缓存名称
 * @param string $value 缓存内容
 * @param string $type 缓存类型，允许值为 file,memcache,xcache,apc,eaccelerator，可以为空，默认为file缓存
 * @param int /null $expire 缓存周期
 * @param mixed $args 扩展参数
 * @return mixed
 */
function H($key, $value = '', $cache_type = '', $expire = null, $args = null)
{
    static $cache = array();
    $cache_type = $cache_type ? $cache_type : 'file';
    $obj_cache = Cache::getInstance($cache_type, $args);
    if ($value !== '') {
        if (is_null($value)) { // 删除缓存
            $result = $obj_cache->rm($key);
            if ($result)
                unset($cache[$cache_type . '_' . $key]);
            return $result;
        } else { // 缓存数据
            if ($value === true) $obj_cache->rm($key);
            $list = Model('cache')->call($key);
            $obj_cache->set($key, $list, null, $expire);
            $cache[$cache_type . '_' . $key] = $list;
        }
        return $value === true ? $list : true;
    }
    if (isset($cache[$cache_type . '_' . $key]))
        return $cache[$cache_type . '_' . $key];

    $value = $obj_cache->get($key);    // 取得缓存
    $cache[$cache_type . '_' . $key] = $value;
    return $value;
}

/**
 * 去除代码中的空白和注释
 *
 * @param string $content 待压缩的内容
 * @return string
 */
function compress_code($content)
{
    $stripStr = '';
    //分析php源码
    $tokens = token_get_all($content);
    $last_space = false;
    for ($i = 0, $j = count($tokens); $i < $j; $i++) {
        if (is_string($tokens[$i])) {
            $last_space = false;
            $stripStr .= $tokens[$i];
        } else {
            switch ($tokens[$i][0]) {
                case T_COMMENT:    //过滤各种PHP注释
                case T_DOC_COMMENT:
                    break;
                case T_WHITESPACE:    //过滤空格
                    if (!$last_space) {
                        $stripStr .= ' ';
                        $last_space = true;
                    }
                    break;
                default:
                    $last_space = false;
                    $stripStr .= $tokens[$i][1];
            }
        }
    }
    return $stripStr;
}

/**
 * 取得对象实例
 *
 * @param object $class
 * @param string $method
 * @param array $args
 * @return object
 */
function get_obj_instance($class, $method = '', $args = array())
{
    static $_cache = array();
    $key = $class . $method . (empty($args) ? null : md5(serialize($args)));
    if (isset($_cache[$key])) {
        return $_cache[$key];
    } else {
        if (class_exists($class)) {
            $obj = new $class;
            if (method_exists($obj, $method)) {
                if (empty($args)) {
                    $_cache[$key] = $obj->$method();
                } else {
                    $_cache[$key] = call_user_func_array(array(&$obj, $method), $args);
                }
            } else {
                $_cache[$key] = $obj;
            }
            return $_cache[$key];
        } else {
            throw_exception('Class ' . $class . ' isn\'t exists!');
        }
    }
}

/**
 * 返回以原数组某个值为下标的新数据
 *
 * @param array $array
 * @param string $key
 * @param int $type 1一维数组2二维数组
 * @return array
 */
function array_under_reset($array, $key, $type = 1)
{
    if (is_array($array)) {
        $tmp = array();
        foreach ($array as $v) {
            if ($type === 1) {
                $tmp[$v[$key]] = $v;
            } elseif ($type === 2) {
                $tmp[$v[$key]][] = $v;
            }
        }
        return $tmp;
    } else {
        return $array;
    }
}

/**
 * KV缓存 读
 *
 * @param string $key 缓存名称
 * @param bool $callback 传递非boolean值时 通过is_callable进行判断 失败抛出异常 成功则将$key作为参数进行回调
 * @return mixed
 * @throws Exception
 */
function rkcache($key, $callback = false)
{
    $cache = Di::getDefault()->get('cacheData');
    if (empty($cache)) {
        throw new Exception('Cannot fetch cache object!');
    }
    $value = $cache->get($key);
    if (empty($value) && $callback !== false) {
        if ($callback === true) {
            $callback = array(Model('Shop\Models\Cache'), 'call');
        }

        if (!is_callable($callback)) {
            throw new Exception('Invalid rkcache callback!');
        }

        $value = call_user_func($callback, $key);
        wkcache($key, $value);
    }

    return $value;
}

/**
 * KV缓存 写
 *
 * @param string $key 缓存名称
 * @param mixed $value 缓存数据 若设为否 则下次读取该缓存时会触发回调（如果有）
 * @param int $expire 缓存时间 单位秒 null代表不过期
 * @return bool
 * @throws Exception
 */
function wkcache($key, $value, $expire = null)
{
    $cache = Di::getDefault()->get('cacheData');

    if (empty($cache)) {
        throw new Exception('Cannot fetch cache object!');
    }
    return $cache->save($key, $value, null, $expire);
}

/**
 * KV缓存 删
 *
 * @param string $key 缓存名称
 * @return bool
 * @throws Exception
 */
function dkcache($key)
{
    $cache = Di::getDefault()->get('cacheData');
    if (empty($cache)) {
        throw new Exception('Cannot fetch cache object!');
    }
    return $cache->delete($key);
}


/**
 * 读取缓存信息
 *
 * @param string $key 要取得缓存键
 * @param string $prefix 键值前缀
 * @param string $fields 所需要的字段
 * @return array/bool
 */
function rcache($key = null, $prefix = '', $fields = '*')
{
    if ($key === null) return array();
    $cache = Di::getDefault()->get('cacheData');
    $cache_info = $cache->hget($key, $prefix, $fields);
    if ($cache_info === false) {
        //取单个字段且未被缓存
        $data = array();
    } elseif (is_array($cache_info)) {
        //如果有一个键值为false(即未缓存)，则整个函数返回空，让系统重新生成全部缓存
        $data = $cache_info;
        foreach ($cache_info as $k => $v) {
            if ($v === false) {
                $data = array();
                break;
            }
        }
    } else {
        //string 取单个字段且被缓存
        $data = array($fields => $cache_info);
    }
    // 验证缓存是否过期
    if (isset($data['cache_expiration_time']) && $data['cache_expiration_time'] < TIMESTAMP) {
        $data = array();
    }
    return $data;
}

/**
 * 写入缓存
 *
 * @param string $key 缓存键值
 * @param array $data 缓存数据
 * @param string $prefix 键值前缀
 * @param int $period 缓存周期  单位分，0为永久缓存
 * @return bool 返回值
 */
function wcache($key = null, $data = array(), $prefix, $period = 0)
{
    if ($key === null || !is_array($data)) return;
    $period = intval($period);
    if ($period != 0) {
        $data['cache_expiration_time'] = TIMESTAMP + $period * 60;
    }
    $cache = Di::getDefault()->get('cacheData');
    $cache->hset($key, $prefix, $data);
    $cache_info = $cache->hget($key, $prefix);
    return true;
}

/**
 * 删除缓存
 * @param string $key 缓存键值
 * @param string $prefix 键值前缀
 * @return boolean
 */
function dcache($key = null, $prefix = '')
{
    if ($key === null) return true;
    $cache = Di::getDefault()->get('cacheData');
    return $cache->hdel($key, $prefix);
}

/**
 * @param $data
 * @return array
 */
function string2array($data)
{
    if ($data == '') return array();
    @eval("\$array = $data;");
    return $array;
}

/**
 * 快速调用语言包
 *
 * @param string $key
 * @return string
 */
function L($key = '')
{
    if (class_exists('Language')) {
        if (strpos($key, ',') !== false) {
            $tmp = explode(',', $key);
            $str = Language::get($tmp[0]) . Language::get($tmp[1]);
            return isset($tmp[2]) ? $str . Language::get($tmp[2]) : $str;
        } else {
            return Language::get($key);
        }
    } else {
        return null;
    }
}


/**
 * 将字符部分加密并输出
 * @param unknown $str
 * @param unknown $start 从第几个位置开始加密(从1开始)
 * @param unknown $length 连续加密多少位
 * @return string
 */
function encryptShow($str, $start, $length)
{
    $end = $start - 1 + $length;
    $array = str_split($str);
    foreach ($array as $k => $v) {
        if ($k >= $start - 1 && $k < $end) {
            $array[$k] = '*';
        }
    }
    return implode('', $array);
}

/**
 * @param $document — 是指需要过滤的一段字符串，比如div、p、em、img等html标签。
 * @param $tags — 是指想要移除指定的html标签，比如a、img、p等。
 * @return mixed
 */
function strip_only_tags($document, $tags)
{
    $search = array(
        "/onload\=([\"|\'|\\\"|\\\']*)([^\"\']+)([\"|\'|\\\"|\\\']*)/si",
        "/(\<a[^\<\>]*href\=[\"|\'|\\\"|\\\']*)([^\"\'\s\>\\]+)([\"|\'|\\\"|\\\']*)([^\<\>]*\>)(.*?)\<\/a\>/si",//a tag
        "/(\<img[^\<\>]*)(src)(\=[\"|\'|\\\"|\\\']*)([^\"\'\s\>\\]+)([\"|\'|\\\"|\\\']*)([^\<\>]*)(\>)/si",//img tag
        "'<!-- Feedsky ad -->[^>]*?>.*?/Feedsky flare -->'si", //Feedsky ad
        "'<br([^>]*)>'si",
        "'<p([^>]*)>'si",
        "'</p>'si",
        "[page]",
        "'<script[^>]*?>.*?</script>'si",    // strip out javascript
        "'<[\/\!]*?[^<>]*?>'si",            // strip out html tags
        "'([\r\n])[\s]+'",                    // strip out white space
        "'&(quot|#34|#034|#x22);'i",        // replace html entities
        "'&(amp|#38|#038|#x26);'i",            // added hexadecimal values
        "'&(lt|#60|#060|#x3c);'i",
        "'&(gt|#62|#062|#x3e);'i",
        "'&(nbsp|#160|#xa0);'i",
        "'&(iexcl|#161);'i",
        "'&(cent|#162);'i",
        "'&(pound|#163);'i",
        "'&(copy|#169);'i",
        "'&(reg|#174);'i",
        "'&(deg|#176);'i",
        "'&(#39|#039|#x27);'",
        "'&(euro|#8364);'i",                // europe
        "'&a(uml|UML);'",                    // german
        "'&o(uml|UML);'",
        "'&u(uml|UML);'",
        "'&A(uml|UML);'",
        "'&O(uml|UML);'",
        "'&U(uml|UML);'",
        "'&szlig;'i",
        "'@@@'i",
        "'###'i",
        "/'/i",
    );
    $replace = array(
        "",
        '@@@a href="\\2" rel="external"###\\4@@@/a###',
        '@@@img src="\\4" alt="photo"###',
        "",
        '@@@br /###',
        '@@@p###',
        '@@@/p###',
        "",
        "",
        "",
        "<br />",
        "\"",
        "&",
        "<",
        ">",
        " ",
        chr(161),
        chr(162),
        chr(163),
        chr(169),
        chr(174),
        chr(176),
        chr(39),
        chr(128),
        "",
        "",
        "",
        "",
        "",
        "",
        "",
        "<",
        ">",
        "",
    );
    $document = strip_tags($document, $tags);
    return preg_replace($search, $replace, $document);
}


/**
 * 规范数据返回函数
 * @param bool|unknown $state
 * @param string $code
 * @param string|unknown $msg
 * @param array|unknown $data
 * @return multitype :unknown
 */
function callback($state = true, $msg = '', $code = '', $data = array())
{
    echo json_encode(['state' => $state, 'msg' => $msg, 'code' => $code, 'data' => $data], JSON_UNESCAPED_UNICODE);
    die;
}
/**
 * 对用户的密码进行加密
 * @param $password
 * @param $encrypt //传入加密串，在修改密码时做认证
 * @return array/password
 */
function password($password, $encrypt='') {
    $pwd = array();
    $pwd['encrypt'] =  $encrypt ? $encrypt : create_randomstr();
    $pwd['password'] = md5(md5(trim($password)).$pwd['encrypt']);
    return $encrypt ? $pwd['password'] : $pwd;
}
/**
 * 生成随机字符串
 * @param string $lenth 长度
 * @return string 字符串
 */
function create_randomstr($lenth = 6)
{
    return random($lenth, '123456789abcdefghijklmnpqrstuvwxyzABCDEFGHIJKLMNPQRSTUVWXYZ');
}

/**
 * 检查用户名是否符合规定
 *
 * @param STRING $username 要检查的用户名
 * @return    TRUE or FALSE
 */
function is_username($username)
{
    $strlen = strlen($username);
    if (is_badword($username) || !preg_match("/^[a-zA-Z0-9_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]+$/", $username)) {
        return false;
    } elseif (20 < $strlen || $strlen < 2) {
        return false;
    }
    return true;
}

/**
 * 检查密码长度是否符合规定
 *
 * @param STRING $password
 * @return    TRUE or FALSE
 */
function is_password($password)
{
    $strlen = strlen($password);
    if ($strlen >= 6 && $strlen <= 20) return true;
    return false;
}
/**
 * 检测输入中是否含有错误字符
 *
 * @param char $string 要检查的字符串名称
 * @return TRUE or FALSE
 */
function is_badword($string) {
    $badwords = array("\\",'&',' ',"'",'"','/','*',',','<','>',"\r","\t","\n","#");
    foreach($badwords as $value){
        if(strpos($string, $value) !== FALSE) {
            return TRUE;
        }
    }
    return FALSE;
}
function is_mobile($mobile)
{
    if (preg_match("/(13[0-9]|14[0-9]|15[0-9]|17[0-9]|18[0-9])\d{8}$/", trim($mobile))) {
        return true;
    }
    return false;
}
