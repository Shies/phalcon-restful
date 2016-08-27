<?php
define('ROOT_PATH', str_replace('\\', '/', dirname(__FILE__)));

require_once ROOT_PATH . '/app/functions/core.php';
require_once ROOT_PATH . '/app/config/defined.php';

require_once APP_PATH . '/library/interfaces/IRun.php';
require_once APP_PATH . '/engine/PhpError.php';
require_once APP_PATH . '/engine/ApplicationMicro.php';

// Capture runtime errors
register_shutdown_function(['engine\PhpError', 'runtimeShutdown']);

header('Access-Control-Allow-Origin:*');
header('X-WuJie-Media-Type: WuJie.v2.0');
use Engine\Micro;
use Models\Api;
$app = new Micro();
try {
    // Record any php warnings/errors
    set_error_handler(['engine\PhpError', 'errorHandler']);
    // Get Authentication Headers
//    $clientId = $app->request->getHeader('API_ID');
//    $time = $app->request->getHeader('API_TIME');
//    $hash = $app->request->getHeader('API_HASH');
//    $privateKey = Api::findFirst($clientId)->private_key;
//
//    switch ($_SERVER['REQUEST_METHOD']) {
//
//        case 'GET':
//            $data = $_GET;
//            unset($data['_url']); // clean for hashes comparison
//            break;
//
//        case 'POST':
//            $data = $_POST;
//            break;
//
//        default: // PUT AND DELETE
//            $data = file_get_contents('php://input');
//            break;
//    }
//    $message = new \Micro\Messages\Auth($clientId, $time, $hash, $data);
//    $app->setEvents(new \Events\Api\HmacAuthenticate($message, $privateKey));
    $app->run();
} catch (Exception $e) {
    $app->response->setStatusCode(500, "Server Error");
    $app->response->setContent($e->getMessage());
    $app->response->send();
}
