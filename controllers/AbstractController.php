<?php

/**
 * @package api-framework
 */
abstract class AbstractController {

    public $beep;
    public $request;

    function __construct() {
        global $beep, $request;
        $this->beep = $beep;
        $this->request = $request;
    }

    public function error($error, $id = null) {
        if ($id) {
            return array('0' => array('error' => $error, 'errorid' => $id, 'errormsg' => $error));
        }
        return array('0' => array('error' => $error));
    }

    public function response($data) {
        return array('0' => array('response' => $data));
    }

    public function success($data) {
        return array('0' => array('success' => $data));
    }

    /**
     * Hook for request authorization. Override in a controller/app layer
     * to validate API keys, tokens, etc. Allows all requests by default.
     */
    public function authorize($app, $beep) {
        return true;
    }

    protected function getModel($request) {
        return $request->url_elements[1];
    }

    protected function buildOrderQuery($order) {
        switch ($order[1]) {
            case 'default':
                return "ORDER BY id {$order[2]}";
            default:
                return "ORDER BY {$order[1]} {$order[2]}";
        }
    }

    protected function buildLimitQuery($limit) {
        if (is_numeric($limit[1]) && is_numeric($limit[2])) {
            return "LIMIT {$limit[1]},{$limit[2]}";
        }
        return '';
    }
}
