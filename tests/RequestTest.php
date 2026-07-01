<?php

use PHPUnit\Framework\TestCase;

class RequestTest extends TestCase {

    public function testDefaults() {
        $request = new Request();

        $this->assertSame(array(), $request->url_elements);
        $this->assertNull($request->method);
        $this->assertNull($request->parameters);
    }

    public function testFieldsAreWritable() {
        $request = new Request();
        $request->method = 'GET';
        $request->url_elements = array('platform', 'countries', '1');
        $request->parameters = array('foo' => 'bar');

        $this->assertSame('GET', $request->method);
        $this->assertSame('1', $request->url_elements[2]);
        $this->assertSame('bar', $request->parameters['foo']);
    }
}
