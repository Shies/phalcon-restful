<?php

if (!function_exists('curl_init'))
{
    die('Curl module not installed!' . PHP_EOL);
}

error_reporting(E_ALL | E_STRICT);
define('ROOT_PATH', str_replace('\\', '/', dirname(__FILE__)));
require_once ROOT_PATH . '/app/functions/core.php';

$route = '/pingok';
// $route = '/test/4';
// $route = '/doesntexist';
// $route = '/skip/auth';

if (isset($_GET['_url'])) {
    $host = 'http://l.restful.cn/api' . $route;
} else {
    $host = "http://l.restful.cn/api" . $route;
}

$host1 = "http://l.restful.cn/api/article/1";
$privateKey = '593fe6ed77014f9507761028801aa376f141916bd26b1b3f0271b5ec3135b989';

$time = time();
$id = 1;

$data = ['name' => 'bob'];
$message = buildMessage($time, $id, $data);

$hash = hash_hmac('sha256', $message, $privateKey);
$headers = ['API_ID: ' . $id, 'API_TIME: ' . $time, 'API_HASH: ' . $hash];

$result = curl_post($host, $headers, $data);
$result1 = curl_get($host1, $headers);

if ($result === FALSE) {
    echo "Curl Error: " . curl_error($ch);
} else {
//	echo "Request: <br>" . PHP_EOL;
//	echo curl_getinfo($ch, CURLINFO_HEADER_OUT)."<br>";

    echo "Response: <br>" . PHP_EOL;
    echo $result . "<br>";
    echo $result1 . "<br>";
    echo PHP_EOL;
}

function buildMessage($time, $id, $data)
{
    return $time . $id . http_build_query($data, '', '&');
}

?>
