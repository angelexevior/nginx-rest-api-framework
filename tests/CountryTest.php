<?php

use PHPUnit\Framework\TestCase;

/**
 * Minimal stand-in for mysqlDatabase so Country can be tested
 * without a real database connection.
 */
class FakeDb {
    public $queries = array();
    public $rows = array(array('id' => 1, 'name' => 'Cyprus'));

    public function query($sql) {
        $this->queries[] = $sql;
        return $sql;
    }

    public function fetch_assoc($result) {
        return $this->rows;
    }

    public function escape_value($value) {
        return addslashes($value);
    }
}

class FakeBeep {
    public $db;

    public function __construct(FakeDb $db) {
        $this->db = $db;
    }
}

class CountryTest extends TestCase {

    public function testGetCountriesWithNumericIdIsCastToInt() {
        $db = new FakeDb();
        $country = new Country(new FakeBeep($db));

        $country->getCountries('5');

        $this->assertStringContainsString('WHERE id = 5', $db->queries[0]);
    }

    public function testGetCountriesEscapesContinentLookup() {
        $db = new FakeDb();
        $country = new Country(new FakeBeep($db));

        // A non-numeric id is treated as a continent filter and must be escaped
        $country->getCountries("Europe' OR '1'='1");

        $this->assertStringNotContainsString("' OR '1'='1", $db->queries[0]);
    }

    public function testGetCountriesReturnsErrorWhenNoRowsFound() {
        $db = new FakeDb();
        $db->rows = array();
        $country = new Country(new FakeBeep($db));

        $result = $country->getCountries(999);

        $this->assertArrayHasKey('error', $result);
    }

    public function testGetCurrenciesDefaultsToPublishedList() {
        $db = new FakeDb();
        $country = new Country(new FakeBeep($db));

        $country->getCurrencies();

        $this->assertStringContainsString('WHERE publish = 1', $db->queries[0]);
    }
}
