<?php

use PHPUnit\Framework\TestCase;

class ResponseJsonTest extends TestCase {

    protected function setUp(): void {
        global $request;
        $request = new Request();
        $request->method = 'GET';
        $request->url_elements = array('platform', 'countries');
        $request->parameters = array();
    }

    public function testSuccessEnvelope() {
        $response = new ResponseJson(array(array('id' => 1, 'name' => 'Cyprus')));
        $decoded = json_decode($response->render(), true);

        $this->assertTrue($decoded['success']);
        $this->assertSame(0, $decoded['error']['errorid']);
        $this->assertSame('Cyprus', $decoded['data']['name']);
    }

    public function testErrorEnvelope() {
        $response = new ResponseJson(array('error' => 'no model found for this request', 'errorid' => 6));
        $decoded = json_decode($response->render(), true);

        $this->assertFalse($decoded['success']);
        $this->assertSame(6, $decoded['error']['errorid']);
        $this->assertSame('no model found for this request', $decoded['error']['message']);
    }

    public function testUnwrapsSingleItemArray() {
        $response = new ResponseJson(array(0 => array('id' => 5)));
        $decoded = json_decode($response->render(), true);

        $this->assertSame(5, $decoded['data']['id']);
    }

    public function testKeepsMultiItemArrays() {
        $response = new ResponseJson(array(
            array('id' => 1),
            array('id' => 2),
        ));
        $decoded = json_decode($response->render(), true);

        $this->assertCount(2, $decoded['data']);
    }

    public function testFactoryReturnsJsonResponder() {
        $obj = Response::create(array('ok' => true), 'application/json');
        $this->assertInstanceOf(ResponseJson::class, $obj);
    }
}
