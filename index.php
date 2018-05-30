<?php
/**
 * API framework front controller.
 * 
 * @package api-framework
 * @author  Angelos Hadjiphilippou <angelos@exevior.com>
 */
/**
 * As always
 * Security first 
 */
define('_SECURED', 1);


/**
 * Load the Beep Library
 */
include('library/classes/Beep.php');
$beep= new beep();

if (!$beep->database_connected()) {
    $beep->connect_to_database();
}

$currentapp = explode('|',$_SERVER['HTTP_USER_AGENT']);
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

/**
 * Register autoloader functions.
 */
spl_autoload_register('autoload_class');
//echo '<pre>';print_r($_SERVER);die;
/**
 * Parse the incoming request.
 */
$request = new Request();

$flag = false;

$request->method = strtoupper($_SERVER['REQUEST_METHOD']);

if (isset($_SERVER['QUERY_STRING'])) {
    $request->url_elements = explode('/', trim($_SERVER['QUERY_STRING'], '/'));
}
$request->method = strtoupper($_SERVER['REQUEST_METHOD']);
switch ($request->method) {
    case 'POST2':
        $request->parameters = $_GET;//POST;
        $data=$_POST;
        if(!empty($request->parameters)){
            $request->url_elements = explode('/', trim(urldecode($_SERVER['REQUEST_URI']), '/'));
            $flag = true; 
//print_r($request);die;
        }
//print_r($request);die;
    break;
    case 'PUT2':
        //$request->parameters = $_POST;
        parse_str(file_get_contents('php://input'), $request->parameters);
        if(!empty($request->parameters)){
            $request->url_elements = explode('/', trim(urldecode($_SERVER['REQUEST_URI']), '/'));
            $flag = true; 
        }
    break;
    case 'DELETE2':
        parse_str(file_get_contents('php://input'), $request->parameters);
        if(!empty($request->parameters)){
            $request->url_elements = explode('/', trim(urldecode($_SERVER['REQUEST_URI']), '/'));
            $flag = true; 
        }
    break;
    default:
        parse_str(file_get_contents('php://input'), $request->parameters);
        $request->parameters = $_GET;
        if(!empty($request->parameters)){
            $request->url_elements = explode('/', trim(urldecode($_SERVER['REQUEST_URI']), '/'));
            $flag = true; 
        }
    break;
}
if ($flag) {
    $controller_name = ucfirst($request->url_elements[0]) . 'Controller';
   

    
    if (class_exists($controller_name)) {
        $controller = new $controller_name;
        $action_name = strtolower($request->method);
        //echo $action_name; exit(1);
        $response_str = call_user_func_array(array($controller, $action_name), array($request));
        $controller->authorize($currentapp,$beep);

    }
    else {
        header('HTTP/1.1 404 Not Found');
       // $response_str = 'Unknown request: ' . $request->url_elements[0];
        $response_str = array('error' => 'Unknown request');
    } 
    
    
}
else {
    $response_str = array('error' => 'Unknown request');
}


/**
 * Send the response to the client.
 */

if(!isset($_SERVER['HTTP_ACCEPT'])){
    $accept = "text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8";
} else {
    $accept = $_SERVER['HTTP_ACCEPT'];
}

$response_obj = Response::create($response_str, $accept);
$response = $response_obj->render();
echo $response;

/**
 * Close the database connection
 */
$beep->database_close();