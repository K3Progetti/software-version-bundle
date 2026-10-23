<?php

namespace K3Progetti\SoftwareVersionBundle\Composer;

/**
 * La chiave dell'azienda cifrata con una password: il repo è pubblico, quindi in chiaro non ce la metto.
 * AES-256-GCM, così se la password è sbagliata la decifratura fallisce invece di dare spazzatura
 */
final class KeyCrypto
{
    public const FILE = __DIR__ . '/../../resources/software_version_key.json';

    private const CIPHER = 'aes-256-gcm';
    // Tante iterazioni apposta: il file è pubblico e chiunque può provare password offline
    private const ITERATIONS = 600000;

    /**
     * @return array<string, mixed>|null
     */
    public static function load(): ?array
    {
        if (!is_file(self::FILE)) {
            return null;
        }
        $data = json_decode((string)file_get_contents(self::FILE), true);

        return is_array($data) ? $data : null;
    }

    /**
     * @return array<string, mixed>
     */
    public static function encrypt(string $plain, string $password): array
    {
        $salt = random_bytes(16);
        $iv = random_bytes(12);
        $tag = '';
        $data = openssl_encrypt($plain, self::CIPHER, self::derive($password, $salt, self::ITERATIONS), OPENSSL_RAW_DATA, $iv, $tag);

        return [
            'iterations' => self::ITERATIONS,
            'salt' => base64_encode($salt),
            'iv' => base64_encode($iv),
            'tag' => base64_encode($tag),
            'data' => base64_encode((string)$data),
        ];
    }

    /**
     * @param array<string, mixed> $encrypted
     * @return string|null null se la password è sbagliata
     */
    public static function decrypt(array $encrypted, string $password): ?string
    {
        foreach (['iterations', 'salt', 'iv', 'tag', 'data'] as $field) {
            if (!isset($encrypted[$field])) {
                return null;
            }
        }
        $key = self::derive($password, (string)base64_decode($encrypted['salt']), (int)$encrypted['iterations']);
        $plain = openssl_decrypt(
            (string)base64_decode($encrypted['data']),
            self::CIPHER,
            $key,
            OPENSSL_RAW_DATA,
            (string)base64_decode($encrypted['iv']),
            (string)base64_decode($encrypted['tag'])
        );

        return $plain !== false ? $plain : null;
    }

    private static function derive(string $password, string $salt, int $iterations): string
    {
        return openssl_pbkdf2($password, $salt, 32, $iterations, 'sha256');
    }
}
