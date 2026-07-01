<?php
/**
 * JSON response class.
 *
 * @package api-framework
 */
class ResponseJson {

    /**
     * Response data.
     *
     * @var mixed
     */
    protected $data;

    /**
     * Constructor.
     *
     * @param mixed $data
     */
    public function __construct($data) {
        $this->data = $data;
    }

    /**
     * Render the response as JSON.
     *
     * @return string
     */
    public function render() {
        global $request;
        header('Content-Type: application/json');

        // Unwrap single-item result arrays, e.g. [0 => [...]] becomes [...]
        if (isset($this->data[0]) && count($this->data) === 1) {
            $data = $this->data[0];
        } else {
            $data = $this->data;
        }

        return json_encode($this->buildFinalData($data, $request));
    }

    protected function buildFinalData($data, $request) {
        $error = $this->errorHandler($data);

        return array(
            'success' => $error['errorid'] === 0,
            'request' => $this->buildControllerData($request),
            'error' => $error,
            'data' => $data,
        );
    }

    protected function buildControllerData($request) {
        return array(
            'method' => $request->method,
            'controller' => $request->url_elements[0],
            'resource' => isset($request->url_elements[1]) ? $request->url_elements[1] : null,
            'parameters' => $request->parameters,
            'url_elements' => $request->url_elements,
        );
    }

    protected function errorHandler($data) {
        if (isset($data['error'])) {
            return array(
                'errorid' => isset($data['errorid']) ? $data['errorid'] : 1000,
                'message' => isset($data['errormsg']) ? $data['errormsg'] : $data['error'],
            );
        }
        return array('errorid' => 0, 'message' => null);
    }
}
