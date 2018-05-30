<?php

defined('_SECURED') or die('Restricted access');
class Beep {
    public $db;
    public $beepdb;
    public $config;
    public $menu;
    //public $memcache;
    public $translator;
    public $defaulttranslator;
    public $lang;
    public $textRTL;
    public $deviceType;

    function beep() {
        //If the file is called from the index.php of the global site
        if (file_exists('beepconfig.php')) {
            include_once('beepconfig.php');
        } else {
            //If it is called from a subdirectory. For different solution change it...
            include_once('../beepconfig.php');
        }

        $this->config = new beepconfig();
        //$this->memcache = new memcache;
        $this->security = 1;
        if (isset($_GET["lang"])) {
            $this->lang = $_GET["lang"];
        } else {
            $this->lang = "en";
        }
       // $_SESSION["language_code"]=$this->lang;
    }

    ////////////////////////DATABASE FUNCTIONS///////////////////
    //connect to the database
    function connect_to_database() {
        //If the file is called from the index.php of the global site
        if (file_exists('library/databasegeneral.php')) {
             include_once('library/databasegeneral.php');
        } else {
            //If it is called from a subdirectory. For different solution change it...
            include_once('../library/databasegeneral.php');
        }
       
        $this->db = new mysqlDatabase();
        $this->db->open_connection();
        return $this->db;
    }
    
    //check if database connected
    function database_connected() {
        if (isset($this->db)) {
            return true;
        } else {
            return false;
        }
    }

    //close database
    function database_close() {
        $this->db->close_connection();
    }

    ///////////////////END OF DATABASE FUNCTIONS///////////////////
    /////////////////// SPHINX FUNCTIONS //////////////////////////
    function connect_to_sphinx() {
        $this->sphinx = mysqli_connect("sphinxdb", "", "", "uc1i_index", 9306) or die("Error " . mysqli_error($this->sphinx));
        return $this->sphinx;
    }

    //check if database connected
    function sphinx_connected() {
        if (isset($this->sphinx)) {
            return true;
        } else {
            return false;
        }
    }

    //close database
    function sphinx_close() {
        mysqli_close($this->sphinx);
    }

    ////////////////// END SPHINX FUNCTIONS ///////////////////////
    //////////////////MEMCACHE////////////////////////////////////
    function memcache_connect() {
        $this->memcache->connect('localhost', 11211) or die("Could not connect");
    }

    function memcache_get($key) {
        $get_result = $this->memcache->get($key);
        return $get_result;
    }

    function memcache_set($key, $data, $timeout) {
        $this->memcache->set($key, $data, false, $timeout) or die("Failed to save data at the server");
    }

    function memcache_close() {
        $this->memcache->close();
    }

    function memcache_delete($key) {
        $this->memcache->delete($key);
    }

    /////////////// END OF MEMCACHE /////////////////////////////
    //////////////////GET DATA////////////////////////////////////
    function getdata($query) {
        $result = $this->db->query($query);
        return $this->db->fetch_assoc($result);
    }
    
    function getdataajax($url, $elementid) {
        ?>
        <script> get('<?php echo $url; ?>', '<?php echo $elementid; ?>')

            function get(url, elementid) {
                var xmlhttp;
                if (window.XMLHttpRequest) {
                    xmlhttp = new XMLHttpRequest(); // code for IE7+, Firefox, Chrome, Opera, Safari
                } else {
                    xmlhttp = new ActiveXObject("Microsoft.XMLHTTP"); // code for IE6, IE5
                }
                xmlhttp.onreadystatechange = function () {
                    if (xmlhttp.readyState == 1) {
                        document.getElementById(elementid).innerHTML = "<img src='images/loader.gif'/>";
                        document.getElementById(elementid).className = "loading";
                    }
                    if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
                        document.getElementById(elementid).innerHTML = xmlhttp.responseText;
                        document.getElementById(elementid).className = "";
                    }
                }
                xmlhttp.open("GET", url, true);
                xmlhttp.send();
            }
        </script>
        <?php
    }

    /////////////// END OF GET DATA /////////////////////////////
    //////////////////// APPEND SCRIPTS //////////////////////
    function appendscript($appendscript, $script = NULL) {
        //echo 'test'.$appendscript;
        //If no script, set as null
        if ($script == NULL) {
            $appendscript = '';
        } else {
            $appendscript .= $script;
        }
        return $appendscript;
    }

    function appendscriptfile($appendfile, $file = NULL) {
        //echo 'test'.$appendscript;
        //If no script, set as null
        if ($file == NULL) {
            $appendfile = '';
        } else {
            $appendfile .= '<script type="text/javascript" src="' . $file . '"></script>';
        }
        return $appendfile;
    }

    ///////////////// END OF APPEND SCRIPTS ///////////////////
    ///////////SEND EMAIL TO USER//////////////////////////////
    function sendmail($from=NULL, $user_email, $subject, $content) {
        if (!class_exists("SendGrid")) {
             if (file_exists('library/sendgrid-php/sendgrid-php.php')) {
                include_once('library/sendgrid-php/sendgrid-php.php');
            } else {
                //If it is called from a subdirectory. For different solution change it...
                include_once('../library/sendgrid-php/sendgrid-php.php');
            }
        }
        $username = $this->config->sendgrid_username;
        $password = $this->config->sendgrid_password;
        $sendgrid = new SendGrid($username, $password, array("turn_off_ssl_verification" => true));
        if($from==NULL){
            $from = "donotreply@beepxtra.com";
        }  
        $receiver_email = array($user_email);
        $email = new SendGrid\Email();
        $email->setTos($receiver_email);
        $email->setFrom($from);
        $email->setFromName('Donotreply');
        //$email->setReplyTo($from);
        $email->setSubject($subject);
        $email->setHtml($content);
        $sendgrid->send($email);
    }

    //function sendmailattachment($from = NULL, $to, $subject, $content, $files) {
    function sendmailattachment($from = NULL, $from_name, $to, $subject, $content, $files) {
        if (!class_exists("SendGrid")) {
             if (file_exists('library/sendgrid-php/sendgrid-php.php')) {
                include_once('library/sendgrid-php/sendgrid-php.php');
            } else {
                //If it is called from a subdirectory. For different solution change it...
                include_once('../library/sendgrid-php/sendgrid-php.php'); 
            }
        }
        $username = $this->config->sendgrid_username;
        $password = $this->config->sendgrid_password;
        $sendgrid = new SendGrid($username, $password, array("turn_off_ssl_verification" => true));
        if($from==NULL){
            $from = "donotreply@email.com";
        }  
        $receiver_email = array($to);
        $email = new SendGrid\Email();
        $email->setTos($receiver_email);
        $email->setFrom($from);
        $email->setFromName($from_name);
        //$email->setReplyTo($from);
        $email->setSubject($subject);
        $email->setHtml($content);
        if(!empty($files)){
            foreach ($files as $file) {
                $email->addAttachment($file["name"]); 
            }
        }
        $result=$sendgrid->send($email); 
        sleep(1);
        if(!empty($files)){
            foreach ($files as $file) {
                unlink($file["name"]);
            } 
        } 
    }

    function createSEFurl($menu) {
        switch ($menu->type) {
            case 'page':
                $link = "/" . $this->lang . "/" . $menu->path;
                break;
            case 'external':
                $link = $menu->link;
                break;
            case 'media':
                $link = '#';
                break;
            case 'news':
                $link = '#';
                break;
            case 'controller':
                $link = "/" . $this->lang . "/" . $menu->path;
                break;
            default:
                $link = '#';
        }
        return $link;
    }

    function translator($lang) {
        include_once('library/translation.php');
        $this->translator = new Translator($lang);
        $this->defaulttranslator = new Translator("en");
    }

    function changelanguage($lang) {
        $this->lang = $lang;
    }

    function getTextDirection($lang) {
        $data = $this->getData("SELECT rtl FROM languages where code='$lang'");
        $this->textRTL = $data[0]["rtl"];
        if ($this->textRTL) {
            return "rtl";
        } else {
            return "ltr";
        }
    }
    
    //URL SLUG
    function friendly_url_creator($str, $options = array()) {
	// Make sure string is in UTF-8 and strip invalid UTF-8 characters
	$str = mb_convert_encoding((string)$str, 'UTF-8', mb_list_encodings());
	
	$defaults = array(
		'delimiter' => '-',
		'limit' => null,
		'lowercase' => true,
		'replacements' => array('/&/i' => ' and '),
		'transliterate' => false,
	);
	
	// Merge options
	$options = array_merge($defaults, $options);
	
	$char_map = array(
		// Latin
		'À' => 'A', '�?' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A', 'Å' => 'A', 'Æ' => 'AE', 'Ç' => 'C', 
		'È' => 'E', 'É' => 'E', 'Ê' => 'E', 'Ë' => 'E', 'Ì' => 'I', '�?' => 'I', 'Î' => 'I', '�?' => 'I', 
		'�?' => 'D', 'Ñ' => 'N', 'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'O', '�?' => 'O', 
		'Ø' => 'O', 'Ù' => 'U', 'Ú' => 'U', 'Û' => 'U', 'Ü' => 'U', 'Ű' => 'U', '�?' => 'Y', 'Þ' => 'TH', 
		'ß' => 'ss', 
		'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a', 'æ' => 'ae', 'ç' => 'c', 
		'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e', 'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i', 
		'ð' => 'd', 'ñ' => 'n', 'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o', 'ő' => 'o', 
		'ø' => 'o', 'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'u', 'ű' => 'u', 'ý' => 'y', 'þ' => 'th', 
		'ÿ' => 'y',

		// Latin symbols
		'©' => '(c)',

		// Greek
		'Α' => 'A', 'Β' => 'B', 'Γ' => 'G', 'Δ' => 'D', 'Ε' => 'E', 'Ζ' => 'Z', 'Η' => 'H', 'Θ' => '8',
		'Ι' => 'I', 'Κ' => 'K', 'Λ' => 'L', 'Μ' => 'M', '�?' => 'N', 'Ξ' => '3', 'Ο' => 'O', 'Π' => 'P',
		'Ρ' => 'R', 'Σ' => 'S', 'Τ' => 'T', 'Υ' => 'Y', 'Φ' => 'F', 'Χ' => 'X', 'Ψ' => 'PS', 'Ω' => 'W',
		'Ά' => 'A', 'Έ' => 'E', 'Ί' => 'I', 'Ό' => 'O', 'Ύ' => 'Y', 'Ή' => 'H', '�?' => 'W', 'Ϊ' => 'I',
		'Ϋ' => 'Y',
		'α' => 'a', 'β' => 'b', 'γ' => 'g', 'δ' => 'd', 'ε' => 'e', 'ζ' => 'z', 'η' => 'h', 'θ' => '8',
		'ι' => 'i', 'κ' => 'k', 'λ' => 'l', 'μ' => 'm', 'ν' => 'n', 'ξ' => '3', 'ο' => 'o', 'π' => 'p',
		'�?' => 'r', 'σ' => 's', 'τ' => 't', 'υ' => 'y', 'φ' => 'f', 'χ' => 'x', 'ψ' => 'ps', 'ω' => 'w',
		'ά' => 'a', 'έ' => 'e', 'ί' => 'i', 'ό' => 'o', '�?' => 'y', 'ή' => 'h', 'ώ' => 'w', 'ς' => 's',
		'ϊ' => 'i', 'ΰ' => 'y', 'ϋ' => 'y', '�?' => 'i',

		// Turkish
		'Ş' => 'S', 'İ' => 'I', 'Ç' => 'C', 'Ü' => 'U', 'Ö' => 'O', 'Ğ' => 'G',
		'ş' => 's', 'ı' => 'i', 'ç' => 'c', 'ü' => 'u', 'ö' => 'o', 'ğ' => 'g', 

		// Russian
		'�?' => 'A', 'Б' => 'B', 'В' => 'V', 'Г' => 'G', 'Д' => 'D', 'Е' => 'E', '�?' => 'Yo', 'Ж' => 'Zh',
		'З' => 'Z', 'И' => 'I', 'Й' => 'J', 'К' => 'K', 'Л' => 'L', 'М' => 'M', '�?' => 'N', 'О' => 'O',
		'П' => 'P', 'Р' => 'R', 'С' => 'S', 'Т' => 'T', 'У' => 'U', 'Ф' => 'F', 'Х' => 'H', 'Ц' => 'C',
		'Ч' => 'Ch', 'Ш' => 'Sh', 'Щ' => 'Sh', 'Ъ' => '', 'Ы' => 'Y', 'Ь' => '', 'Э' => 'E', 'Ю' => 'Yu',
		'Я' => 'Ya',
		'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 'е' => 'e', 'ё' => 'yo', 'ж' => 'zh',
		'з' => 'z', 'и' => 'i', 'й' => 'j', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n', 'о' => 'o',
		'п' => 'p', 'р' => 'r', '�?' => 's', 'т' => 't', 'у' => 'u', 'ф' => 'f', 'х' => 'h', 'ц' => 'c',
		'ч' => 'ch', 'ш' => 'sh', 'щ' => 'sh', 'ъ' => '', 'ы' => 'y', 'ь' => '', '�?' => 'e', 'ю' => 'yu',
		'�?' => 'ya',

		// Ukrainian
		'Є' => 'Ye', 'І' => 'I', 'Ї' => 'Yi', '�?' => 'G',
		'є' => 'ye', 'і' => 'i', 'ї' => 'yi', 'ґ' => 'g',

		// Czech
		'Č' => 'C', 'Ď' => 'D', 'Ě' => 'E', 'Ň' => 'N', 'Ř' => 'R', 'Š' => 'S', 'Ť' => 'T', 'Ů' => 'U', 
		'Ž' => 'Z', 
		'�?' => 'c', '�?' => 'd', 'ě' => 'e', 'ň' => 'n', 'ř' => 'r', 'š' => 's', 'ť' => 't', 'ů' => 'u',
		'ž' => 'z', 

		// Polish
		'Ą' => 'A', 'Ć' => 'C', 'Ę' => 'e', '�?' => 'L', 'Ń' => 'N', 'Ó' => 'o', 'Ś' => 'S', 'Ź' => 'Z', 
		'Ż' => 'Z', 
		'ą' => 'a', 'ć' => 'c', 'ę' => 'e', 'ł' => 'l', 'ń' => 'n', 'ó' => 'o', 'ś' => 's', 'ź' => 'z',
		'ż' => 'z',

		// Latvian
		'Ā' => 'A', 'Č' => 'C', 'Ē' => 'E', 'Ģ' => 'G', 'Ī' => 'i', 'Ķ' => 'k', 'Ļ' => 'L', 'Ņ' => 'N', 
		'Š' => 'S', 'Ū' => 'u', 'Ž' => 'Z',
		'�?' => 'a', '�?' => 'c', 'ē' => 'e', 'ģ' => 'g', 'ī' => 'i', 'ķ' => 'k', 'ļ' => 'l', 'ņ' => 'n',
		'š' => 's', 'ū' => 'u', 'ž' => 'z'
	);
	
	// Make custom replacements
	$str = preg_replace(array_keys($options['replacements']), $options['replacements'], $str);
	
	// Transliterate characters to ASCII
	if ($options['transliterate']) {
		$str = str_replace(array_keys($char_map), $char_map, $str);
	}
	
	// Replace non-alphanumeric characters with our delimiter
	$str = preg_replace('/[^\p{L}\p{Nd}]+/u', $options['delimiter'], $str);
	
	// Remove duplicate delimiters
	$str = preg_replace('/(' . preg_quote($options['delimiter'], '/') . '){2,}/', '$1', $str);
	
	// Truncate slug to max. characters
	$str = mb_substr($str, 0, ($options['limit'] ? $options['limit'] : mb_strlen($str, 'UTF-8')), 'UTF-8');
	
	// Remove delimiter from ends
	$str = trim($str, $options['delimiter']);
	
	return $options['lowercase'] ? mb_strtolower($str, 'UTF-8') : $str;
}
}
?>