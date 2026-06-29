<?php

namespace Evervault\Tests\EndToEnd;

use Evervault\Tests\EndToEnd\EndToEndTestCase;

class FunctionTest extends EndToEndTestCase {

    private const TEST_FUNCTION_NAME = 'node-function-synthetic';

    public function testFunctionRun() {
        $array = [
            "string" => "apple",
            "integer" => 12345,
            "float" => 123.45,
            "true" => true,
            "false" => false
        ];
        $encrypted = self::$evervaultClient->encrypt($array);
        $functionResponse = self::$evervaultClient->run(self::TEST_FUNCTION_NAME, $encrypted);
        $this->assertResult($functionResponse);
    }

    public function testCreateFunctionRunToken() {
        $array = [
            "string" => "apple",
            "integer" => 12345,
            "float" => 123.45,
            "true" => true,
            "false" => false
        ];
        $encrypted = self::$evervaultClient->encrypt($array);
        $response = self::$evervaultClient->createRunToken(self::TEST_FUNCTION_NAME, $encrypted);
        $this->assertNotEmpty($response->token);

        $functionResponse = $this->runFunctionWithToken($response->token, self::TEST_FUNCTION_NAME, $encrypted);
        $this->assertResult($functionResponse);
    }

    private function assertResult($result) {
        foreach ($result as $property => $value) {
            $this->assertNotEquals("encrypted_string", $value);
        }
    }

    private function runFunctionWithToken($token, $functionName, $payload) {
        $url = "https://run.evervault.com/$functionName";
        $ch = curl_init($url);
        $headers = [
            "authorization: Bearer $token",
            'content-type: application/json'
        ];
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);
        return json_decode($response)->result;
    }
}