<?php
/**
 * Country Class
 * Country, currency and location info
 * @author marios
 */
 class Country {
    private $beep;
    private $SocketBeep;
    private $db;
    
    function __construct($beep1, $environment="local") {
        $this->beep=$beep1;
        
    }
    
     //from this select statement we don't have where publish=1 because we want india to be on the map
     public function getAllCountries($beep){
        $sql="SELECT * FROM $this->db.countries";
        $result=$beep->db->query($sql);
        $countries=$beep->db->fetch_assoc($result);
        return $countries;
    }
    
     function getCountry($params){
         //get condition parameters
        $fields= array();
        $i=0;
        foreach($params as $field=>$value){
            $fields[$i++]= $field ."='". urldecode($value) ."'";
        } 
        $conditions=implode(" AND ", $fields);
        $query = "Select * from $this->db.countries where " . $conditions;       
        $countries=$this->beep->getdata($query);
       if(empty($countries)){
            $countries["error"]="Country(ies) is not available";
        }
        return $countries;
    }
    
    //UPDATE COUNTRY ON CORE DATABASE AND SPHINX DATABASE
    
     function updateCountry($userid, $country){
        $request_uri = "userid=$userid&countryid=$country&action=usercountryupdate";
        $result=$this->SocketBeep->opensocket($request_uri);
        if(isset($result["success"])){
            return $result;
        }else if(isset($result["error"])){
            $result["error"]="The country update has failed! Please try again";
            return $result;
      }
    }
    
     public function getCountrySelect($val, $selectedvalue=0, $text="form_please_select"){
        $sql="SELECT id, name FROM $this->db.countries where publish=1 ORDER BY name ASC";
        $countries=$this->beep->getdata($sql);
        echo "<select name='country' id='country'";
        if($val=="on"){
            echo " onchange=\"validation('country')\">";
        }else{
            echo ">";
        }
        echo "<option id='0' value='0'>".$this->beep->translator->__($text)."</option>";
        foreach($countries as $country){
           echo "<option id='{$country['id']}' value='{$country['id']}";
           if($selectedvalue==$country['id']){
               echo " selected";
           }
           echo "'>";    
           echo $country['name'];
           echo "</option>";
        }
        echo "</select>";
    }
    
   
   public function getCountryCodesSelect(){
        $sql="SELECT id, name, country_code FROM $this->db.countries where publish=1 ORDER BY name ASC";
        $countries=$this->beep->getdata($sql);
        
        print_r("<select name='telephone-code' id='telephone-code' onchange='validation(\"telephone\")'>");
        print_r ("<option value='0'>".$this->beep->translator->__("form_please_select")."</option>");
        foreach($countries as $country){
            print_r("<option value='{$country['country_code']}'>");
            print_r($country['name']." (".$country['country_code'].")");
            print_r("</option>");
        }
        print_r("</select>");
    }
      
    public function getCountryName($countryid){
        $conditions = array("id" => $countryid);
        $country=$this->getCountry($conditions);
        if(isset($country["error"])){
            return NULL;
        }else{
            return $country[0]["name"];
        }
    }
    
     public function getCountryMapPosition($countryname){
        $sql="SELECT map_x, map_y FROM $this->db.countries where name like '%$countryname%' AND publish=1";
        $result=$this->beep->db->query($sql);
        $country=$this->beep->db->fetch_assoc($result);
        if(empty($country)){
            return NULL;
        }else{
            return $country[0];
        }
    }
    
    ///////////////////////////////////API CLASSES /////////////////////////////////////////////
    
    
    /** 
    * Get countries
    *
    * @param    int     $id (optional) 
    * @return   array   country details
    */
    function getCountries($id = NULL) {
        if ($id == NULL) {
            $query = "SELECT id,name,flag_name,country_code,continent,currency,map_x,map_y FROM $this->db.countries WHERE publish = 1 ORDER BY name";
            $result = $this->beep->db->query($query);
            $result = $this->beep->db->fetch_assoc($result);
        } else {
            if(is_numeric($id)){
                $query = "SELECT id,name,flag_name,country_code,continent,currency,map_x,map_y FROM $this->db.countries WHERE id = {$id}";
                $result = $this->beep->db->query($query);
                $result = $this->beep->db->fetch_assoc($result);
            }else {
                switch ($id){
                    case 'mastercard':
                        $query = "SELECT id,name,flag_name,country_code,continent,currency,map_x,map_y FROM $this->db.countries WHERE mastercard = 1";
                        $result = $this->beep->db->query($query);
                        $result = $this->beep->db->fetch_assoc($result);
                    break;
                    default:
                        $query = "SELECT id,name,flag_name,country_code,continent,currency,map_x,map_y FROM $this->db.countries WHERE continent LIKE '{$id}' ORDER BY name";
                        $result = $this->beep->db->query($query);
                        $result = $this->beep->db->fetch_assoc($result);
                        break;
                }
            }
        }
        if (!$result) {
            if(is_numeric($id)){
                $result = array('error'=>'There is no active country with id ' . $id . '');
            }else{
                $result = array('error'=>'There is no continent called ' . $id . '');
            }
        }
        return $result;
    }
    
    /** 
    * Get currencies
    *
    * @param    int     $id (optional) 
    * @return   array   currency details
    */
    function getCurrencies($id = NULL) {
        if ($id == NULL) {
            $query = "SELECT name,currency FROM $this->db.countries WHERE publish = 1 ORDER BY name ASC";
            $result = $this->beep->db->query($query);
            $result = $this->beep->db->fetch_assoc($result);
        } else {
                $query = "SELECT name,currency FROM $this->db.countries WHERE id = '{$id}'";
                $result = $this->beep->db->query($query);
                $result = $this->beep->db->fetch_assoc($result);
        }
        if (!$result) {
               $result = array('error'=>'There is no active country with id ' . $id . '');
        }
        return $result;
    }
       
}
