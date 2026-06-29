<?php

namespace Evervault\Tests\EndToEnd;

use PHPUnit\Framework\TestCase;
use Evervault\Evervault;

class EndToEndTestCase extends TestCase {
    protected static $evervaultClient;
    
    public static function setUpBeforeClass(): void
    {
        $appId = getenv('TEST_EV_APP_ID');
        $apiKey = getenv('TEST_EV_API_KEY');
        self::$evervaultClient = new Evervault($appId, $apiKey);
    }
}