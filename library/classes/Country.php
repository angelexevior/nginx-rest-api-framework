<?php
/**
 * Country class - country and currency lookups.
 */
class Country {

    private $beep;

    function __construct($beep) {
        $this->beep = $beep;
    }

    /**
     * Get countries
     *
     * @param  int|string|null $id
     * @return array
     */
    function getCountries($id = NULL) {
        $columns = "id,name,flag_name,country_code,continent,currency,map_x,map_y";
        if ($id === NULL) {
            $query = "SELECT {$columns} FROM countries WHERE publish = 1 ORDER BY name";
        } elseif (is_numeric($id)) {
            $query = "SELECT {$columns} FROM countries WHERE id = " . (int) $id;
        } elseif ($id === 'mastercard') {
            $query = "SELECT {$columns} FROM countries WHERE mastercard = 1";
        } else {
            $continent = $this->beep->db->escape_value($id);
            $query = "SELECT {$columns} FROM countries WHERE continent LIKE '{$continent}' ORDER BY name";
        }

        $result = $this->beep->db->query($query);
        $result = $this->beep->db->fetch_assoc($result);

        if (!$result) {
            $result = array('error' => 'There is no active country matching ' . $id);
        }
        return $result;
    }

    /**
     * Get currencies
     *
     * @param  int|null $id
     * @return array
     */
    function getCurrencies($id = NULL) {
        if ($id === NULL) {
            $query = "SELECT name,currency FROM countries WHERE publish = 1 ORDER BY name ASC";
        } else {
            $query = "SELECT name,currency FROM countries WHERE id = " . (int) $id;
        }

        $result = $this->beep->db->query($query);
        $result = $this->beep->db->fetch_assoc($result);

        if (!$result) {
            $result = array('error' => 'There is no active country with id ' . $id);
        }
        return $result;
    }
}
