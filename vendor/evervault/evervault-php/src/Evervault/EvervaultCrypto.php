<?php

namespace Evervault;

use Mdanter\Ecc\EccFactory;
use Mdanter\Ecc\Serializer\Point\CompressedPointSerializer;
use Mdanter\Ecc\Serializer\Point\UncompressedPointSerializer;
use Mdanter\Ecc\Crypto\Key\PublicKey;
use Mdanter\Ecc\Primitives\GeneratorPoint;
use Mdanter\Ecc\Util\NumberSize;

class EvervaultCrypto {
    public $appEcdhP256Key;
    public $decodedAppEcdhP256Key;

    public $cipher = 'aes-256-gcm';

    public $keyLength = 32;
    public $authTagLength = 16;
    public $ivLength = 12;

    public $header = [
        'iss' => 'evervault',
        'version' => 1
    ];

    public $P256ASN1Prefix = '3082014b3082010306072a8648ce3d02013081f7020101302c06072a8648ce3d0101022100ffffffff00000001000000000000000000000000ffffffffffffffffffffffff305b0420ffffffff00000001000000000000000000000000fffffffffffffffffffffffc04205ac635d8aa3a93e7b3ebbd55769886bc651d06b0cc53b0f63bce3c3e27d2604b031500c49d360886e704936a6678e1139d26b7819f7e900441046b17d1f2e12c4247f8bce6e563a440f277037d812deb33a0f4a13945d898c2964fe342e2fe1a7f9b8ee7eb4a7c0f9e162bce33576b315ececbb6406837bf51f5022100ffffffff00000000ffffffffffffffffbce6faada7179e84f3b9cac2fc632551020101034200';

    function __construct($appEcdhP256Key) {
        $this->appEcdhP256Key = $appEcdhP256Key;
        $this->decodedAppEcdhP256Key = base64_decode($appEcdhP256Key);
    }

    private function _generateBytes($length = 16) {
        return openssl_random_pseudo_bytes($length);
    }

    private function _format($datatype, $ephemeralEcdhPublicKey, $keyIv, $encryptedData) {
        return sprintf(
            "ev:QkTC:%s%s:%s:%s:$", 
            $datatype, 
            EvervaultUtils::base64url_encode($keyIv), 
            EvervaultUtils::base64url_encode($ephemeralEcdhPublicKey), 
            EvervaultUtils::base64url_encode($encryptedData)
        );
    }

    private function _generateMetadata($role) {
        $buffer = '';
        $buffer .= pack('C', 0x80 | (empty($role) ? 2 : 3)); // Binary representation of a fixed map with 2 or 3 items, followed by the key-value pairs

        if(!empty($role)) {
            // `dr` (data role) => role_name
            $buffer .= pack('C', 0xA2) . 'dr'; // Binary representation for a fixed string of length 2, followed by `dr`
            $buffer .= pack('C', 0xA0 | strlen($role)) . $role; // Binary representation for a fixed string of role name length, followed by the role name itself
        }

        // "eo" (encryption origin) => 10 (PHP SDK)
        $buffer .= pack('C', 0xA2) . 'eo'; // Binary representation for a fixed string of length 2, followed by `eo`
        $buffer .= pack('C', 10); // Binary representation for the integer 10

        // "et" (encryption timestamp) => current time
        $buffer .= pack('C', 0xA2) . 'et'; // Binary representation for a fixed string of length 2, followed by `et`
        $buffer .= pack('C', 0xCE) . pack('N', time()); // Binary representation for a  4-byte unsigned integer (uint 32), followed by the epoch time

        return $buffer;
    }

    private function _deriveSharedSecret() {
        $adapter = EccFactory::getAdapter();
        $generator = EccFactory::getNistCurves()->generator256();
        $private = $generator->createPrivateKey();
        $public = $private->getPublicKey();

        $compressingSerializer = new CompressedPointSerializer($adapter);
        $uncompressingSerializer = new UncompressedPointSerializer($adapter);
        $serialized = $compressingSerializer->serialize($public->getPoint());
        $uncompressedSerialized = $uncompressingSerializer->serialize($public->getPoint());
        $encodedUncompressed = hex2bin($this->P256ASN1Prefix . $uncompressedSerialized);

        $appPub = $compressingSerializer->unserialize(
            EccFactory::getNistCurves()->curve256(), 
            bin2hex($this->decodedAppEcdhP256Key)
        );
        $appPubUncompressed = $uncompressingSerializer->serialize($appPub);
        $appPubInstance = new PublicKey($adapter, $generator, $appPub);

        $exchange = $private->createExchange($appPubInstance);
        $shared = $exchange->calculateSharedKey();

        $kdf = function (GeneratorPoint $G, \GMP $sharedSecret, $encodedUncompressed) {
            $adapter = $G->getAdapter();
            $binary = $adapter->intToFixedSizeString(
                $sharedSecret,
                NumberSize::bnNumBytes($adapter, $G->getOrder())
            );

            $toHash = $binary . hex2bin('00000001') . $encodedUncompressed;

            $hash = hash('sha256', $toHash, true);
            return $hash;
        };

        $key = $kdf($generator, $shared, $encodedUncompressed);

        return (object) [
            'aesKey' => $key,
            'ephemeralEcdhPublicKey' => hex2bin($serialized)
        ];
    }

    private function _encryptArray($array, $role) {
        array_walk_recursive($array, function (&$value) use($role) {
            $value = $this->_encryptValue($value, $role);
        });

        return $array;
    }

    private function _createV2Aad($dataType, $ephemeralPublicKeyBytes, $appPublicKeyBytes) {
        $dataTypeNumber = 0; // Default to String

        if ($dataType === 'number:') {
            $dataTypeNumber = 1;
        } elseif ($dataType === 'boolean:') {
            $dataTypeNumber = 2;
        }

        $versionNumber = 1; // PHP only supports QkTC

        if ($versionNumber === null) {
            throw new Exception('This encryption version does not have a version number for AAD');
        }

        $configByteSize = 1;
        $totalSize = $configByteSize + strlen($ephemeralPublicKeyBytes) + strlen($appPublicKeyBytes);
        $aad = str_repeat("\0", $totalSize); // Create a binary string initialized with null bytes

        // Set the configuration byte
        $b = 0x00 | ($dataTypeNumber << 4) | $versionNumber; // PHP doesn't support debug strings, so always start with 0x00
        $aad[0] = chr($b); // Convert the number to a character and set it to the first position

        // Copy ephemeral public key bytes into aad
        $aad = substr_replace($aad, $ephemeralPublicKeyBytes, $configByteSize, strlen($ephemeralPublicKeyBytes));

        // Copy application public key bytes into aad
        $aad = substr_replace($aad, $appPublicKeyBytes, $configByteSize + strlen($ephemeralPublicKeyBytes), strlen($appPublicKeyBytes));

        return $aad;
    }

    private function _encryptValue($value, $role) {
        if (in_array($this->cipher, openssl_get_cipher_methods())) {
            $sharedSecret = $this->_deriveSharedSecret();

            $iv = $this->_generateBytes(12);
            $tag = '';

            $stringifiedValue = $value;

            if (is_numeric($value)) {
                $datatype = 'number:';
            } else if (is_bool($value)) {
                $datatype = 'boolean:';
                $stringifiedValue = $value ? 'true' : 'false';
            } else {
                $datatype = '';
            }

            $aad = $this->_createV2Aad($datatype, $sharedSecret->ephemeralEcdhPublicKey, $this->decodedAppEcdhP256Key);

            $metadata = $this->_generateMetadata($role);
            $metadataOffset = pack('v', strlen($metadata)); // 'v' specifies 16-bit unsigned little-endian
            $payload = $metadataOffset . $metadata . $stringifiedValue;

            $enc = openssl_encrypt(
                $payload,
                'aes-256-gcm',
                $sharedSecret->aesKey,
                OPENSSL_RAW_DATA,
                $iv,
                $tag,
                $aad,
                16
            );
        
            return $this->_format($datatype, $sharedSecret->ephemeralEcdhPublicKey, $iv, $enc . $tag);
        } else {
            throw new EvervaultError('AES-256-GCM is not supported. Please upgrade to PHP >7.1.');
        }
    }

    public function encryptData($data, $role = null) {
        if ($role !== null && !preg_match('#^[a-z0-9-]{1,20}$#', $role)) {
            throw new EvervaultError('The provided Data Role slug is invalid. The slug can be retrieved in the Evervault dashboard (Data Roles section).');
        }

        if (is_array($data)) {
            return $this->_encryptArray($data, $role);
        }

        if (is_string($data) || is_numeric($data) || is_bool($data)) {
            return $this->_encryptValue($data, $role);
        }

        throw new EvervaultError('The provided data to be encrypted is invalid. Please ensure the data is either a string, number, boolean, or array.');
    }
}
