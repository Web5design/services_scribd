<?php

require_once 'CommonTest.php';

class Services_Scribd_UserTest extends Services_Scribd_CommonTest
{
    public function testGetAvailableEndpoints()
    {
        $endpoints = $this->scribd->getAvailableEndpoints();

        $this->assertInternalType('array', $endpoints);
        $this->assertArrayHasKey(0, $endpoints);
        $this->assertArrayHasKey(1, $endpoints);
        $this->assertArrayHasKey(2, $endpoints);
        $this->assertEquals($endpoints[0], 'getAutoSigninUrl');
        $this->assertEquals($endpoints[1], 'login');
        $this->assertEquals($endpoints[2], 'signup');
    }

    public function testGetAutoSigninUrl()
    {
        $expectedResponse = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<rsp stat="ok">
  <url>
    <![CDATA[http://www.scribd.com/login/auto_login/999?hash=1a1d&next_url=%2F]]>
  </url>
</rsp>
XML;

        $this->setHTTPResponse($expectedResponse);
        $response = $this->scribd->getAutoSigninUrl();

        $this->assertInternalType('string', $response);
        $this->assertEquals('http://www.scribd.com/login/auto_login/999?hash=1a1d&next_url=%2F', $response);
    }

    public function testLogin()
    {
        $expectedResponse = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<rsp stat="ok">
  <session_key>sess1</session_key>
  <user_id>9184</user_id>
  <username>richid-test</username>
  <name></name>
</rsp>
XML;

        $this->setHTTPResponse($expectedResponse);
        $response = $this->scribd->login('richid-test', 'pass');

        $this->assertInstanceOf('SimpleXMLElement', $response);
        $this->assertEquals('sess1', (string) $response->session_key);
        $this->assertEquals('9184', (string) $response->user_id);
        $this->assertEquals('richid-test', (string) $response->username);
        $this->assertEquals('', (string) $response->name);
    }

    public function testSignup()
    {
        $expectedResponse = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<rsp stat="ok">
  <session_key>sess1</session_key>
  <user_id>1014</user_id>
  <username>richid-test2</username>
  <name></name>
</rsp>
XML;

        $this->setHTTPResponse($expectedResponse);
        $response = $this->scribd->signup('richid-test2', 'pass', 'rich@email.com');

        $this->assertInstanceOf('SimpleXMLElement', $response);
        $this->assertEquals('sess1', (string) $response->session_key);
        $this->assertEquals('1014', (string) $response->user_id);
        $this->assertEquals('richid-test2', (string) $response->username);
        $this->assertEquals('', (string) $response->name);
    }
}

?>
