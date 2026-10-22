<?php

namespace K3Progetti\SoftwareVersionBundle\Service;

/**
 * L'invio al server delle versioni, usato sia dal comando che dall'invio automatico
 */
final class InfoSender
{
    /** @var ComposerInfoService */
    private $composerInfoService;

    /** @var string */
    private $endpoint;

    /** @var string|null */
    private $key;

    public function __construct(ComposerInfoService $composerInfoService, string $endpoint, ?string $key)
    {
        $this->composerInfoService = $composerInfoService;
        $this->endpoint = $endpoint;
        $this->key = $key;
    }

    /**
     * La chiave da config o, di solito, da SOFTWARE_VERSION_KEY
     */
    public function getKey(): ?string
    {
        if (!empty($this->key)) {
            return $this->key;
        }
        // Dove la mette Symfony a seconda della versione e di come è caricato il .env
        foreach ([$_SERVER, $_ENV] as $vars) {
            if (!empty($vars['SOFTWARE_VERSION_KEY'])) {
                return (string)$vars['SOFTWARE_VERSION_KEY'];
            }
        }
        $value = getenv('SOFTWARE_VERSION_KEY');

        return $value !== false && $value !== '' ? $value : null;
    }

    /**
     * @param int $timeout secondi, corto quando lo faccio in automatico dopo una richiesta
     * @return array{ok: bool, message: string, payload: array}
     */
    public function send(int $timeout = 20): array
    {
        $payload = $this->composerInfoService->getInfo();
        $key = $this->getKey();
        if (empty($key)) {
            return ['ok' => false, 'message' => 'SOFTWARE_VERSION_KEY non impostata', 'payload' => $payload];
        }

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->endpoint,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => json_encode($payload + ['key' => $key]),
            CURLOPT_CONNECTTIMEOUT => min(10, $timeout),
            CURLOPT_TIMEOUT => $timeout,
        ]);
        $response = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            return ['ok' => false, 'message' => 'Invio non riuscito: ' . $error, 'payload' => $payload];
        }
        if ($httpCode < 200 || $httpCode >= 300) {
            $body = json_decode((string)$response, true);
            $message = isset($body['message']) ? $body['message'] : (string)$response;

            return ['ok' => false, 'message' => sprintf('Invio rifiutato (%d): %s', $httpCode, $message), 'payload' => $payload];
        }

        return ['ok' => true, 'message' => 'Inviato', 'payload' => $payload];
    }
}
