<?php

namespace Evervault;

class EvervaultUtils {
    public static function uuidv4() {
        $data = openssl_random_pseudo_bytes(16);  
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    public static function base64url_encode($data) {
        $base64 = base64_encode($data);

        return preg_replace('/={1,2}$/', '', $base64);
    }

    public static function isDecryptionDomain($domain, $decryptionDomains) {
        $domain = parse_url($domain)['host'];

        foreach ($decryptionDomains as $decryptionDomain) {
            if ($decryptionDomain === $domain) {
                return true;
            } else if (substr($decryptionDomain, 0, 1) === '*' && str_ends_with($domain, substr($decryptionDomain, 1))) {
                return true;
            }
        }

        return false;
    }

    public static function validateAppUuidAndApiKey($appUuid, $apiKey) {
        if (!$appUuid) {
            throw new EvervaultError('No App ID provided. The App ID can be retrieved in the Evervault dashboard (App Settings).');
        }

        if (strpos($appUuid, "app_") !== 0) {
            throw new EvervaultError('The provided App ID is invalid. The App ID can be retrieved in the Evervault dashboard (App Settings).');
        }

        if (!$apiKey) {
            throw new EvervaultError('No API key provided. An API Key can be created in the Evervault dashboard.');
        }

        if (substr($apiKey, 0, 3) == 'ev:') {
            $appUuidHash = substr(base64_encode(hash('sha512', $appUuid, true)), 0, 6);
            $appUuidHashFromApiKey = explode(':', $apiKey)[4];
            if ($appUuidHash !== $appUuidHashFromApiKey) {
                throw new EvervaultError('The provided API key does not belong to the App '. $appUuid . '.');
            }
        }
    }
}