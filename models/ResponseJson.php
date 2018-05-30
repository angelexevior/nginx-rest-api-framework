<?php
class ResponseJson
{
    /**
     * Response data.
     *
     * @var string
     */
    protected $data;
    
    
    /**
     * Constructor.
     *
     * @param string $data
     */
    public function __construct($data)
    {
        global $request;
        $this->data = $data;
        if($request->url_elements[0] != "platform"){
                    if(isset($this->data[0])){
            if(count($this->data) == 1){
                $this->data = $this->data[0];
            } else {
                $this->data = $this->data;
                
            }
        } 
        }

        
        //$this->data->data = $data;
        
        return $this;
    }
    
    /**
     * Render the response as JSON.
     * 
     * @return string
     */
    public function render()
    {
        global $request;
        header('Content-Type: application/json');
        //Last check to remove single array being child to [0]
        
        //print_r($this->data);
        if(isset($this->data[0])){
            if(count($this->data) == 1){
                $data = $this->data[0];
            } else {
                $data = $this->data;
                
            }
        } else {
            $data = $this->data;
            
        }
        //print_r($data);
        $finaldata = $this->buildFinalData($data);
        
        //print_r($finaldata);
        $response = json_encode($finaldata);

        return $response;
    }
    
    public function buildFinalData($data){
        global $request;
        
        $controller = $this->buildControllerData($request);
        $error = $this->errorHandler($this->data);
        $success = $this->successHandler($error);
    
        $finaldata = array();
        //Kept for backwards compatibility
        //$finaldata = $data;
        //Added for generic responses V2.0
        $finaldata['success'] = $success;
        $finaldata['request'] = $controller;
        $finaldata['error'] = $error;
        $finaldata['data'] = $this->data;
        //print_r($finaldata);
        return $finaldata;
    }
    
    public function buildControllerData($request){
        $controller = array();
        $controller['method'] = $request->method;
        $controller['controller'] = $request->url_elements[0];
        if(isset($request->url_elements[1])){
            $controller['resource'] = $request->url_elements[1];
        } else {
            $controller['resource'] = null;
        }
        $controller['parameters'] = key($request->parameters);
        $controller['url_elements'] = $request->url_elements;
        
        //$controller['request'] = $request;
        //print_r($request);die;
        return $controller;
    }
    
    public function errorHandler($data){
        //print_r($data);die;
        $error = array();
        if(isset($data['error'])){
            //Backwards compatible error reporting
            if(isset($data['errorid'])){
                $error['message'] = $data['errormsg'];
                $error['errorid'] = $data['errorid'];
            } else {
                $error['errorid'] = 1000;
                $error['message'] = $data['error'];
            }
            
        } else {
            $error['errorid'] = 0;
            $error['message'] = 0;
        }
        //print_r($error);
        
        return $error;
    }
    
    public function successHandler($error){
        //print_r($error);die;
        if($error['errorid']){
            $success = false;
        } else {
            $success = true;
        }
        return $success;
    }
}