<?php
/**
 * API framework front controller.
 *
 * @package api-framework
 * @author  Angelos Hadjiphilippou <angelos@exevior.com>
 */
define('_SECURED', 1);

include('library/classes/Beep.php');
$beep = new Beep();

if (!$beep->database_connected()) {
    $beep->connect_to_database();
}

$currentapp = explode('|', $_SERVER['HTTP_USER_AGENT']);

/**
 * Generic class autoloader.
 *
 * @param string $class_name
 */
function autoload_class($class_name) {
    $directories = array(
        'controllers/',
        'models/'
    );
    foreach ($directories as $directory) {
        $filename = $directory . $class_name . '.php';
        if (is_file($filename)) {
            require($filename);
            break;
        }
    }
}

spl_autoload_register('autoload_class');

/**
 * Parse the incoming request.
 */
$request = new Request();
$request->method = strtoupper($_SERVER['REQUEST_METHOD']);
$request->url_elements = isset($_SERVER['QUERY_STRING'])
    ? explode('/', trim($_SERVER['QUERY_STRING'], '/'))
    : array();

switch ($request->method) {
    case 'POST':
        $request->parameters = $_POST;
        break;
    case 'PUT':
    case 'DELETE':
        parse_str(file_get_contents('php://input'), $request->parameters);
        break;
    default:
        $request->parameters = $_GET;
        break;
}

if (!empty($request->url_elements[0])) {
    $controller_name = ucfirst($request->url_elements[0]) . 'Controller';

    if (class_exists($controller_name)) {
        $controller = new $controller_name;
        $action_name = strtolower($request->method);
        $controller->authorize($currentapp, $beep);
        $response_str = call_user_func_array(array($controller, $action_name), array($request));
    } else {
        header('HTTP/1.1 404 Not Found');
        $response_str = array('error' => 'Unknown request');
    }
} else {
    $response_str = array('error' => 'Unknown request');
}

/**
 * Send the response to the client.
 */
$accept = isset($_SERVER['HTTP_ACCEPT']) ? $_SERVER['HTTP_ACCEPT'] : 'application/json';

$response_obj = Response::create($response_str, $accept);
echo $response_obj->render();

$beep->database_close();
