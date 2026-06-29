<?php

namespace Evervault\Tests\EndToEnd;

use Evervault\Tests\EndToEnd\EndToEndTestCase;

class OutboundRelayTest extends EndToEndTestCase {

    private const OR_ENABLED_ENDPOINT_URL = 'https://o54dbmzbcj.execute-api.us-east-2.amazonaws.com/production/outbound?mode=outbound';

    public function testEnableOutboundRelay() 
    {
        $data = [
            "string" => "apple",
            "number" => 12345,
            "double" => 123.45,
            "true" => true,
            "false" => false
        ];
        $encrypted = self::$evervaultClient->encrypt($data, "permit-all");

        // Request outside Outbound Destination
        $response = $this->makeRequest(self::OR_ENABLED_ENDPOINT_URL . "&uuid=php-sdk-test", $encrypted, false);

        $this->assertEquals($response['request']['string'], true);
        $this->assertEquals($response['request']['number'], true);
        $this->assertEquals($response['request']['double'], true);
        $this->assertEquals($response['request']['true'], true);
        $this->assertEquals($response['request']['false'], true);

        // Request to Outbound Destination
        $response = $this->makeRequest(self::OR_ENABLED_ENDPOINT_URL . "&uuid=php-sdk-test", $encrypted, true);

        $this->assertEquals($response['request']['string'], false);
        $this->assertEquals($response['request']['number'], false);
        $this->assertEquals($response['request']['double'], false);
        $this->assertEquals($response['request']['true'], false);
        $this->assertEquals($response['request']['false'], false);        
    }

    private function makeRequest($url, $payload, $enableOutboundRelay)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_VERBOSE, false);
        if ($enableOutboundRelay) {
          self::$evervaultClient->enableOutboundRelay($ch);
        }
        $response = json_decode(curl_exec($ch), true);
        curl_close($ch);
        return $response;
    }
}
