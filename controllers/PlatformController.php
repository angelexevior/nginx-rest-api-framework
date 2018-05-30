<?php

class PlatformController extends AbstractController {

    private $BeepCountry = null;
    private $BeepProduct = null;

    public function __construct() {
        parent::__construct();

        global $beep;

        if (!class_exists("Country"))
            require_once('library/classes/Country.php');
        $this->BeepCountry = new Country($beep);

    }

    /**
     * GET method.
     * 
     * @param  Request $request
     * @return string
     */
    public function get($request) {
        $data = $this->beginRequest($request);

        return $data;
    }

    /**
     * POST action.
     *
     * @param  $request
     * @return null
     */
    public function post($request) {
        //to develop
        return 'Post method not allowed';
    }

    public function put($request) {

        return 'Put method not allowed';
    }

    public function delete($request) {

        return 'Delete method not allowed';
    }

    /**
     * Get User details.
     *
     * @return array
     */
    protected function beginRequest($request) {
        
        switch (count($request->url_elements)) {
            case 1:
                $data = $this->error('Please specify the model you wish to retrieve data from', 1);
                break;
            case 2:
                $model = $this->getModel($request);
                switch ($model) {
                    case 'countries':
                        $data = $this->getCountries();
                        break;
                    case 'categories':
                        $data = $this->getCategories();
                        break;
                    case 'currencies';
                        $data = $this->getCurrencies();
                        break;
                    case 'rates';
                        $data = $this->getRates();
                        break;
                    case 'specialoffers':
                        $data = $this->getSpecialOffers($request);
                        break;
                    default:
                        //error
                        $data = $this->error('no model found for this request', 6);
                        break;
                }
                break;
            case 3:
                $model = $this->getModel($request);
                switch ($model) {
                    case 'countries':
                        $data = $this->getCountries($request->url_elements[2]);
                        break;
                    case 'categories':
                        $data = $this->getCategories($request->url_elements[2]);
                        break;
                    case 'category':
                        $data = $this->getCategory($request->url_elements[2]);
                        break;
                    case 'currencies';
                        $data = $this->getCurrencies($request->url_elements[2]);
                        break;
                    case 'rates';
                        $data = $this->getRates($request->url_elements[2]);
                        break;
                    case 'specialoffers':
                        $data = $this->getSpecialOffers($request);
                        break;
                    case 'nearestoutlets':
                        $data = $this->getNearestOutlets($request);
                        break;
                    case 'nearestoffers':
                        $data = $this->getNearestOffers($request);
                        break;
                    case 'outlet':
                        $array = array("id" => $request->url_elements[2]);
                        $data = $this->BeepOutlet->getApiOutlet($array);
                        break;
                    default:
                        //error
                        $data = $this->error('no model found for this request', 6);
                        break;
                }
                
                break;
            case 4:
                $model = $this->getModel($request);
                
                switch ($model) {
                    
                    default:
                        $data = $this->error('no model found for this request', 6);
                        break;
                }
                break;
            default :
                $data_id = $request->url_elements[2];
                $data = NULL;
                break;
        }
        return $data;
    }

    protected function getModel($request) {
        $model = $request->url_elements[1];
        return $model;
    }

    protected function getCountries($id = NULL) {
        $countries = $this->BeepCountry->getCountries($id);
        return $countries;
    }

    protected function getCurrencies($id = NULL) {
        $currencies = $this->BeepCountry->getCurrencies($id);

        return $currencies;
    }

    protected function getCategories($id = NULL) {
        $categories = $this->BeepProduct->getCategories($id);
        return $categories;
    }

    protected function getCategory($id = NULL) {
        $category = $this->BeepProduct->getCategory($id);
        return $category;
    }
    
    protected function getSpecialOffers($request) {
        
        $endingdate1 = date("m/d/Y", strtotime("+7 day"));
        //PRODUCE NEWSLINE FOR EACH COUNTRY
        $end_date = date("Y-m-d H:i:s", strtotime('next wednesday') + 12 * 60 * 60 - 1);
        $start_date = date("Y-m-d H:i:s", strtotime('previous wednesday') + 12 * 60 * 60);
        // if it's a wednesday before lunch still show previous offers
        if (date('l') == "Wednesday" && date('A') == "AM") {
            $start_date = date("Y-m-d H:i:s", strtotime('-7 day') + 12 * 60 * 60); // starts midday today
            $end_date = date("Y-m-d H:i:s", strtotime('today') + 12 * 60 * 60 - 1); // starts midday today
            // if it's a wednesday after lunch show new offers
        } else if (date('l') == "Wednesday" && date('A') == "PM") {
            $start_date = date("Y-m-d H:i:s", strtotime('today') + 12 * 60 * 60); // starts midday today
            $end_date = date("Y-m-d H:i:s", strtotime($endingdate1) + 12 * 60 * 60 - 1); // starts midday today
        }
        
        //GET OFFERS 
        $sql = "SELECT q.* FROM (
               SELECT oo.*, CONCAT('https://www.beepstores.com/img/uploads/offers/',oo.logo) as logo_path, c.currency, 
               ou.id as outletid, ou.name as outlet_name, ou.alias as outlet_alias, 
               st.id as storeid, st.name as store_name, st.alias as store_alias,
               c.name as country_name, c.flag_name as country_alias
                   FROM `outlet_offers` oo 
                   LEFT JOIN outlets ou ON ou.id=oo.outlet_id
                   LEFT JOIN stores st ON ou.store_id=st.id
                   LEFT JOIN countries c ON ou.country=c.id
                   WHERE oo.deleted=0 AND oo.start_date <= '{$start_date}' AND oo.end_date >= '{$end_date}'";

        //offers of specific country
                   if(count($request->url_elements) >= 3){
                        $country = $request->url_elements[2];
                        if(!is_numeric($country)){
                            return $this->error('Invalid Country specified. Should be numeric', 420);
                        }
                   } else {
                        $country = 0;
                   }
        if (($country != 0)) {
            $sql.=" AND c.id={$country}";
        }
        $sql.=" ORDER BY oo.id DESC) q
                 GROUP BY q.outlet_id
               ";
        //echo $sql;die;
        $sort = "beep_discount";
        //OFFERS SORT BY 
        switch ($sort) {
            case "end_date":
                $sql.=" ORDER BY q.end_date ";
                break;
            case "beep_discount":
                $sql.=" ORDER BY q.beep_discount ";
                break;
            case "price":
                $sql.=" ORDER BY q.price ";
                break;
            case "country":
                $sql.=" ORDER BY country_name ";
                break;
        }
        $order = "ASC";
        $sql.= $order;
        
        $result = $this->beep->db->query($sql);
        $numoffers = $result->num_rows;
        $offers = $this->beep->db->fetch_assoc($result);
        
        //echo '<pre>';print_r($offers);die;
        return $offers;
    }
    
    private function getNearestOutlets($request){
        //Get the lng-lat
        $location = explode(',',$request->url_elements[2]);
        //verify lng and lat exist and are decimals
        if(count($location) >= 2){
            $lat = $location[0];
            $lng = $location[1];
        } else {
            return $this->error("Invalid coordinates specified", 430);
        }
        
        if(count($location) >= 3){
            $distance = $location[2];
        } else {
            $distance = 200;
        }
        
        if(count($location) == 4){
            $limit = $location[3];
        } else {
            $limit = 100;
        }
        
        $outlets = $this->BeepOutlet->getApiClosestOutlets($lng, $lat, $distance, $limit);
        return $outlets;
    }
    
    function getNearestOffers($request){
        //Get the lng-lat
        $location = explode(',',$request->url_elements[2]);
        //verify lng and lat exist and are decimals
        if(count($location) >= 2){
            $lat = $location[0];
            $lng = $location[1];
        } else {
            return $this->error("Invalid coordinates specified", 430);
        }
        
        if(count($location) >= 3){
            $distance = $location[2];
        } else {
            $distance = 200;
        }
        
        if(count($location) == 4){
            $limit = $location[3];
        } else {
            $limit = 100;
        }
        
        
        $endingdate1=date("m/d/Y", strtotime("+7 day"));
        //PRODUCE NEWSLINE FOR EACH COUNTRY
        $end_date = date("Y-m-d H:i:s", strtotime('next wednesday') + 12*60*60 -1 );
        $start_date = date("Y-m-d H:i:s", strtotime('previous wednesday') + 12*60*60  );
        // if it's a wednesday before lunch still show previous offers
        if(date('l') == "Wednesday" && date('A')== "AM") {
            $start_date = date("Y-m-d H:i:s", strtotime('today') + 12*60*60 -1  ); // starts midday today
            $end_date = date("Y-m-d H:i:s", strtotime($endingdate1) + 12*60*60 -1); // starts midday today
        // if it's a wednesday after lunch show new offers
        }else if(date('l') == "Wednesday" && date('A')== "PM") {
            $start_date = date("Y-m-d H:i:s", strtotime('today') + 12*60*60  ); // starts midday today
            $end_date = date("Y-m-d H:i:s", strtotime($endingdate1) + 12*60*60 -1); // starts midday today
        }  
        
        
        $sql="SELECT d.id as offer_id, a.outlet_id, ou.name outlet_name, d.title as offer_title, CONCAT('https://www.beepstores.com/img/uploads/offers/', d.logo) as offer_logo, d.description as offer_description, c.currency, d.price as offer_price, d.beep_discount as offer_discount, count(d.id) as available_offers, c.name country_name, a.street, a.street_no, a.city, a.region, a.postcode,REPLACE( CONCAT(  'https://www.beepstores.com/img/uploads/', REPLACE( SUBSTRING( ou.logo, 1, 5 ) ,  'outle',  'outlet' ) ,  's/', ou.logo ) ,  'https://www.beepstores.com/img/uploads/s/', 'https://www.beepstores.com/img/logo/no-logo.jpg' ) AS logo_path, a.lat as lat, a.lng as lng,  ( 3959 * acos( cos( radians($lat) ) * cos( radians( lat ) ) * cos( radians( lng ) - radians($lng) ) + sin( radians($lat) ) * sin( radians( lat ) ) ) ) AS distance, ou.discount "
                . "FROM outlets_address a "
                . "LEFT JOIN outlets ou ON a.outlet_id=ou.id "
                . "LEFT JOIN countries c ON ou.country=c.id "
                . "LEFT JOIN outlet_offers as d ON a.outlet_id = d.outlet_id "
                . "WHERE ou.integrated=1 AND d.active = 1 AND d.start_date <= '{$start_date}' AND d.end_date >= '{$end_date}' "
                . "GROUP BY ou.id "
                . "HAVING distance < $distance "
                . "ORDER BY distance LIMIT 0 , $limit";
        //echo $sql;die;
        $outlets = $this->beep->getdata($sql);
        
        if(empty($outlets)){
            $outlets["error"]="No available outlets on that range!";
        }  
        return $outlets; 
    }

}
