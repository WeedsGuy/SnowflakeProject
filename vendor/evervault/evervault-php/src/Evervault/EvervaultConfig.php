<?php

namespace Evervault;

class EvervaultConfig {
    private const DEFAULT_API_URL = 'https://api.evervault.com';
    private const DEFAULT_RUN_URL = 'https://run.evervault.com';

    private $apiBaseUrl;
    private $functionRunBaseUrl;

    public function __construct() {
        $this->apiBaseUrl = getenv('EV_API_URL') ?: self::DEFAULT_API_URL;
        $this->functionRunBaseUrl = getenv('EV_CAGE_RUN_URL') ?: self::DEFAULT_RUN_URL;
    }

    public function getApiBaseUrl() {
        return $this->apiBaseUrl;
    }
    
    public function getFunctionRunBaseUrl() {
        return $this->functionRunBaseUrl;
    }
}