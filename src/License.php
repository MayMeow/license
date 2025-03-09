<?php

namespace MayMeow\License;

class License
{

    public function __construct(
        protected string $publicKey_path = 'public_key_for-your-app.txt',
        protected string $privateKey_path = 'private_key_keep-safe.txt',
        protected ?string $licenseData = null
    )
    {
    }

    public static function new(string $name, string $app, array $features, int $additional_days = 365): array
    {
        // timestamp for 1 year
        $validUntil = time() + ($additional_days * 24 * 60 * 60);

        //generate license id from name and app and validUntil
        $id = sha1($name . $app . $validUntil);

        return [
            'id' => $id,
            'name' => $name,
            'app' => $app,
            'features' => $features,
            'valid_until' => $validUntil,
        ];
    }

    /**
     * Sign the license
     * @param array $data
     * @return void
     */
    public function sign(array $data): void
    {
        $keys = $this->getKeys();
        $privateKey = openssl_pkey_get_private(base64_decode($keys['private_key']));
        $stringData = json_encode($data);

        openssl_sign($stringData, $signature, $privateKey);

        $data['signature'] = base64_encode($signature);


        file_put_contents('license.txt', base64_encode(json_encode($data)));
    }

    /**
     * Check if the license is valid
     * @return bool
     */
    public function isValid(): bool
    {
        return $this->verify() === 1 && !$this->isExpired();
    }

    /**
     * Get the license
     * @return array
     */ 
    protected function getLicense(): array
    {
        if ($this->licenseData) {
            return json_decode(base64_decode($this->licenseData), true);
        } else {
            return json_decode(base64_decode(file_get_contents('license.txt')), true);
        }
    }

    /**
     * Get the license id
     * @return string
     */
    public function getId(): string
    {
        return $this->getLicense()['id'];
    }

    /**
     * Get the licensee
     * @return string
     */
    public function getLicensee(): string
    {
        return $this->getLicense()['name'];
    }

    public function hasFeature(string $geature): bool
    {
        return in_array($geature, $this->getLicense()['features']);
    }

    /**
     * Check if the license is expired
     * @return bool
     */
    protected function isExpired(): bool
    {
        return $this->getLicense()['valid_until'] < time();
    }

    /**
     * Verify the license
     * @return int
     */
    protected function verify(): int
    {
        $license = $this->getLicense();

        $keys = file_get_contents($this->publicKey_path);

        $publicKey = openssl_pkey_get_public(base64_decode($keys));

        $signature = base64_decode($license['signature']);

        // remove signature from data
        unset($license['signature']);

        $stringData = json_encode($license);

        return openssl_verify($stringData, $signature, $publicKey);
    }

    /**
     * Get keys for the license
     * @return array
     */
    protected function getKeys(): array
    {
        if (file_exists('keys.json')) {
            return json_decode(file_get_contents('keys.json'), true);
        } else {
            self::generateKeys();

            return $this->getKeys();
        }
    }

    /**
     * Generate keys for the license
     * @return void
     * @throws \Exception
     */
    protected static function generateKeys(): void
    {
        if (!extension_loaded('openssl')) {
            throw new \Exception('OpenSSL extension is not enabled');
        } else {
            $version = OPENSSL_VERSION_TEXT;

            echo "Using $version \n";
        }

        $config = [
            'private_key_type' => OPENSSL_KEYTYPE_EC,
            'curve_name' => 'prime256v1',
        ];

        $res = openssl_pkey_new($config);

        if (!$res) {
            throw new \Exception('Failed to generate private key: ' . openssl_error_string());
        } else {
            openssl_pkey_export($res, $privKey);

            $keys = [
                'private_key' => base64_encode($privKey),
                'public_key' => base64_encode(openssl_pkey_get_details($res)['key'])
            ];

            file_put_contents('keys.json', json_encode($keys));

            file_put_contents('private_key_keep-safe.txt', $keys['private_key']);
            file_put_contents('public_key_for-your-app.txt', $keys['public_key']);
        }
    }
}