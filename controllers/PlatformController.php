<?php

/**
 * Sample controller exposing the countries/currencies reference data
 * from countries.sql at /platform/countries and /platform/currencies.
 */
class PlatformController extends AbstractController {

    private $BeepCountry = null;

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
     * @return array
     */
    public function get($request) {
        return $this->beginRequest($request);
    }

    public function post($request) {
        return $this->error('Post method not allowed');
    }

    public function put($request) {
        return $this->error('Put method not allowed');
    }

    public function delete($request) {
        return $this->error('Delete method not allowed');
    }

    protected function beginRequest($request) {
        if (count($request->url_elements) < 2) {
            return $this->error('Please specify the model you wish to retrieve data from', 1);
        }

        $model = $this->getModel($request);
        $id = isset($request->url_elements[2]) ? $request->url_elements[2] : NULL;

        switch ($model) {
            case 'countries':
                return $this->getCountries($id);
            case 'currencies':
                return $this->getCurrencies($id);
            default:
                return $this->error('no model found for this request', 6);
        }
    }

    protected function getCountries($id = NULL) {
        return $this->BeepCountry->getCountries($id);
    }

    protected function getCurrencies($id = NULL) {
        return $this->BeepCountry->getCurrencies($id);
    }
}
