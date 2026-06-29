<?php

namespace Evervault;

class Evervault {
    const VERSION = '0.0.5';
    
    private $cryptoClient;
    private $httpClient;
    private $configClient;
    private $outboundRelayCaFile;
    private $outboundRelayCaPath;
    private $relayAuthString;
    private $appKeys;
    private $apiKey;
    private $appUuid;

    private $outboundRelayUrl;
    private $outboundRelayCaUrl;
    private $outboundRelayDestinations;

    private $caFilename = '/evervault-ca.pem';

    function __construct($appId, $apiKey, $options = []) {
        $this->appUuid = $appId;
        $this->apiKey = $apiKey;

        EvervaultUtils::validateAppUuidAndApiKey($this->appUuid, $this->apiKey);

        $this->outboundRelayUrl = getenv('EV_TUNNEL_HOSTNAME') ? getenv('EV_TUNNEL_HOSTNAME') : 'https://relay.evervault.com:443';
        $this->outboundRelayCaUrl = getenv('EV_CERT_HOSTNAME') ? getenv('EV_CERT_HOSTNAME') : 'https://ca.evervault.com';
        $this->configClient = new EvervaultConfig();
        $this->httpClient = new EvervaultHttp(
            $apiKey,
            $appId,
            $this->configClient->getApiBaseUrl(), 
            $this->configClient->getFunctionRunBaseUrl()
        );

        // If CA doesn't exist, or if CA is >1 minute old then refresh
        if (!file_exists(sys_get_temp_dir() . $this->caFilename) 
        || (file_exists(sys_get_temp_dir() . $this->caFilename) 
            && (time() - filemtime(sys_get_temp_dir() . $this->caFilename)) > (1 * 60))
            ) {
            $outboundRelayCaFile = fopen(sys_get_temp_dir() . $this->caFilename, "w");
            fwrite($outboundRelayCaFile, file_get_contents($this->outboundRelayCaUrl));
        }

        $this->outboundRelayCaPath = sys_get_temp_dir() . $this->caFilename;
    }

    private function _createCryptoClientIfNotExists() {
        if (!$this->cryptoClient) {
            if (!$this->appKeys) {
                $this->appKeys = $this->httpClient->getAppEcdhKey();
            }
            $this->cryptoClient = new EvervaultCrypto(
                $this->appKeys->appEcdhP256Key
            );
        }
    }

    public function encrypt($data, $role = null) {
        $this->_createCryptoClientIfNotExists();

        if (!isset($data) || $data === "") {
            throw new EvervaultError('Please provide some data to encrypt.');
        }

        if (!(is_bool($data) || is_string($data) || is_array($data) || is_numeric($data))) {
            throw new EvervaultError('The data to encrypt must be a string, number, boolean or array.');
        }

        return $this->cryptoClient->encryptData($data, $role);
    }

    public function decrypt($data) {
        if (!$data || (!is_string($data) && !is_array($data))) {
            throw new EvervaultError('`decrypt()` must be called with a string or an array.');
        }
        return $this->httpClient->decrypt($data);
    }

    public function createClientSideDecryptToken($data, $expiry = null) {
        if (!$data) {
            throw new EvervaultError('The `$data` parameter is required and ensures the issued token can only be used to decrypt that specific payload.');
        }

        if ($expiry) {
            $expiry = $expiry * 1000;
        }
        
        return $this->httpClient->createToken("api:decrypt", $data, $expiry);
    }

    public function run($functionName, $functionData, $options = ['version' => null, 'async' => false]) {
        $additionalHeaders = [];

        if (isset($options['version'])) {
            if (!is_numeric($options['version'])) {
                throw new EvervaultError('Function version must be a number.');
            } else {
                $additionalHeaders[] = 'x-version-id: ' . $options['version'];
            }
        }

        if ($options['async']) {
            $additionalHeaders[] = 'x-async: true';
        }

        return $this->httpClient->runFunction($functionName, $functionData, $additionalHeaders);
    }

    public function enableOutboundRelay($curlHandler) {
        $this->_createCryptoClientIfNotExists();

        if (!$this->relayAuthString) {
            $this->relayAuthString = $this->appKeys->appId . ':' . $this->apiKey;
        }

        if (!$this->outboundRelayDestinations) {
            $this->outboundRelayDestinations = $this->httpClient->getAppRelayConfiguration();
        }

        $requestUrl = curl_getinfo($curlHandler, CURLINFO_EFFECTIVE_URL);

        if (EvervaultUtils::isDecryptionDomain($requestUrl, $this->outboundRelayDestinations)) {
            curl_setopt($curlHandler, CURLOPT_PROXY, $this->outboundRelayUrl);
            curl_setopt($curlHandler, CURLOPT_PROXYUSERPWD, $this->relayAuthString);
            curl_setopt($curlHandler, CURLOPT_CAINFO, $this->outboundRelayCaPath);
        }
    }

    public function createRunToken($functionName, $payload = []) {
        return $this->httpClient->createRunToken($functionName, $payload);
    }
}
