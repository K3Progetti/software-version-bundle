<?php

namespace K3Progetti\SoftwareVersionBundle\Command;

use K3Progetti\SoftwareVersionBundle\Service\ComposerInfoService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Manda al server delle versioni cosa gira qui. Da lanciare all'avvio del container
 * e una volta al giorno: non deve mai bloccare un deploy, quindi gli errori li scrive e basta
 */
class SendInfoCommand extends Command
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
        parent::__construct();
    }

    protected function configure(): void
    {
        // setName e non #[AsCommand] o $defaultName: così va da Symfony 5.4 a 8
        $this->setName('software-version:send-info');
        $this->setDescription('Manda al server delle versioni il PHP e il Symfony che girano qui');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $key = $this->key ?: $this->envKey();
        if (empty($key)) {
            $io->warning('SOFTWARE_VERSION_KEY non impostata: non mando niente');
            return 0;
        }

        $payload = $this->composerInfoService->getInfo();
        $payload['key'] = $key;

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->endpoint,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 20,
        ]);
        $response = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            $io->error('Invio non riuscito: ' . $error);
            return 1;
        }
        if ($httpCode < 200 || $httpCode >= 300) {
            $body = json_decode((string)$response, true);
            $io->error(sprintf('Invio rifiutato (%d): %s', $httpCode, isset($body['message']) ? $body['message'] : $response));
            return 1;
        }

        $io->success(sprintf('Inviato: %s, PHP %s, Symfony %s', $payload['name'], $payload['phpVersion'], $payload['symfonyVersion'] ?: '?'));

        return 0;
    }

    /**
     * Dove la mette Symfony a seconda della versione e di come è caricato il .env
     */
    private function envKey(): ?string
    {
        foreach ([$_SERVER, $_ENV] as $vars) {
            if (!empty($vars['SOFTWARE_VERSION_KEY'])) {
                return (string)$vars['SOFTWARE_VERSION_KEY'];
            }
        }
        $value = getenv('SOFTWARE_VERSION_KEY');

        return $value !== false && $value !== '' ? $value : null;
    }
}
