<?php

namespace Evervault;

class EvervaultHttp {
    private $apiKey;
    private $appUuid;
    private $apiBaseUrl;
    private $functionRunBaseUrl;

    private $curl;

    private $appKeyPath = '/cages/key';
    private $relayConfigPath = '/v2/relay-outbound';
    private $decryptPath = '/decrypt';
    private $createTokenPath = '/client-side-tokens';
    
    private $appKey;

    function __construct($apiKey, $appUuid, $apiBaseUrl, $functionRunBaseUrl) {
        $this->apiKey = $apiKey;
        $this->appUuid = $appUuid;
        $this->apiBaseUrl = $apiBaseUrl;
        $this->functionRunBaseUrl = $functionRunBaseUrl;

        $this->curl = curl_init();
    }

    private function _getDefaultHeaders($basicAuth = false) {
        $defaultHeaders = [
            'content-type: application/json',
            'accept: application/json',
            'user-agent: evervault-php/'.Evervault::VERSION
        ];

        if ($basicAuth) {
            $defaultHeaders[] = 'authorization: Basic ' . base64_encode($this->appUuid . ':' . $this->apiKey);
        } else {
            $defaultHeaders[] = 'api-key: '.$this->apiKey;
        }

        return $defaultHeaders;
    }

    private function _buildApiUrl($path) {
        return $this->apiBaseUrl . $path;
    }

    private function _buildFunctionUrl($functionName) {
        return $this->functionRunBaseUrl . '/' . $functionName;
    }

    private function _decryptUrl() {
        return $this->apiBaseUrl . '/decrypt';
    }

    private function _createTokenUrl() {
        return $this->apiBaseUrl . '/client-side-tokens';
    }

    public function getAppEcdhKey() {
        $appKeys = $this->_makeApiRequest(
            'GET',
            $this->appKeyPath
        );

        return (object) [
            "appEcdhP256Key" => $appKeys->ecdhP256Key,
            "appId" => isset($appKeys->appUuid) ? $appKeys->appUuid : $appKeys->teamUuid
        ];
    }

    public function getAppRelayConfiguration() {
        $relayConfig = $this->_makeApiRequest(
            'GET',
            $this->relayConfigPath
        );
        return array_keys((array) $relayConfig->outboundDestinations);
    }

    public function createRunToken($functionName, $payload = []) {
       return $this->_makeApiRequest(
            'POST',
            '/v2/functions/'.$functionName.'/run-token',
            $payload
        );
    }

    private function _handleApiResponse($curl, $response, $headers = [], $associativeArray = false) {
        $responseCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $requestedUrl = curl_getinfo($curl, CURLINFO_EFFECTIVE_URL);

        if ($responseCode === 401 and strncmp($requestedUrl, 'https://run.evervault.com/', strlen('https://run.evervault.com')) === 0) {
            throw new EvervaultError('Your function could not be found. Please ensure you have deployed a function with the name you provided.');
        } else if ($responseCode === 401) {
            throw new EvervaultError('Your API key was invalid. Please verify it matches your API key in the Evervault Dashboard.');
        } else if ($responseCode === 403) {
            $json_response = json_decode($response);
            if ($json_response->code === 'decrypt/forbidden') {
                throw new EvervaultError($json_response->detail);
            } else {
                throw new EvervaultError('Your API key does not have the required permissions to perform this action. You can update your API key permissions in the Evervault Dashboard.');
            }
        } else if ($responseCode >= 400) {
            throw new EvervaultError('There was an error initializing the Evervault SDK. Please try again or contact support@evervault.com for help.');
        } else {
            return json_decode($response, $associativeArray);
        }
    }

    private function _makeApiRequest($method, $path, $body = [], $headers = [], $basicAuth = false, $associativeArrayResponse = false) {
        curl_setopt(
            $this->curl, 
            CURLOPT_URL, 
            $this->_buildApiUrl($path)
        );

        curl_setopt(
            $this->curl,
            CURLOPT_HTTPHEADER,
            array_merge(
                $this->_getDefaultHeaders($basicAuth), 
                $headers
            )
        );

        curl_setopt(
            $this->curl,
            CURLOPT_HTTPGET,
            true
        );
        
        if (strtolower($method) === 'post') {
            curl_setopt(
                $this->curl,
                CURLOPT_POSTFIELDS,
                json_encode($body, JSON_FORCE_OBJECT)
            );
        }

        curl_setopt(
            $this->curl,
            CURLOPT_RETURNTRANSFER,
            true
        );

        $response = curl_exec($this->curl);
        return $this->_handleApiResponse($this->curl, $response, $headers, $associativeArrayResponse);
    }

    private function _makefunctionRunRequest($functionName, $body = [], $headers = []) {
        $url = $this->_buildfunctionUrl($functionName);

        curl_setopt(
            $this->curl, 
            CURLOPT_URL, 
            $url
        );

        curl_setopt(
            $this->curl,
            CURLOPT_HTTPHEADER,
            array_merge(
                $this->_getDefaultHeaders(), 
                $headers
            )
        );

        curl_setopt(
            $this->curl,
            CURLOPT_POSTFIELDS,
            json_encode($body, JSON_FORCE_OBJECT)
        );

        curl_setopt(
            $this->curl,
            CURLOPT_RETURNTRANSFER,
            true
        );

        $response = curl_exec($this->curl);

        return $this->_handleApiResponse($this->curl, $response, $headers);
    }

    public function runFunction($functionName, $functionData, $additionalHeaders) {
        $response = $this->_makefunctionRunRequest($functionName, $functionData, $additionalHeaders);

        if (in_array('x-async: true', $additionalHeaders)) {
            return $response;
        } else {
            return $response->result;
        }
    }

    public function decrypt($data) {
        $response = $this->_makeApiRequest('POST', $this->decryptPath, [
            'data' => $data
        ], [], true, true);
        return $response['data'];
    }

    public function createToken($action, $data, $expiry) {
        $payload = array(
            'action' => $action,
            'payload' => $data,
            'expiry' => $expiry
        );

        $response = $this->_makeApiRequest('POST', $this->createTokenPath, $payload, [], true);

        return $response;
    }
}