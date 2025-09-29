<?php

namespace K3Progetti\SoftwareVersionBundle\Command;


use JsonException;
use K3Progetti\SoftwareVersionBundle\Service\ComposerInfoService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * @property $httpClient
 */
#[AsCommand(name: 'software-version:send-info', description: 'Invia le informazioni del progetto')]
class SendInfoCommand extends Command
{


    public function __construct(
        private readonly ParameterBagInterface $parameterBag,
        private readonly ComposerInfoService   $composerInfoService
    )
    {
        parent::__construct();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws JsonException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Invio le informazioni del progetto');

        $endpoint = $this->parameterBag->get('software_version.endpoint');

        // Recupero le informazioni
        $payload = $this->composerInfoService->getInfo();

        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL => $endpoint,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => json_encode($payload),
            // Robustezza TLS/SNI
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_SSL_VERIFYPEER => true,
            // Se il tuo ambiente ha reverse/proxy “capricciosi”, prova a forzare TLS1.2:
            // CURLOPT_SSLVERSION     => CURL_SSLVERSION_TLSv1_2,

            // Evita negoziazioni HTTP/2 strane con cert/proxy vecchi
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,

            // Timeout
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT        => 20,

            // Debug utile
            CURLINFO_HEADER_OUT    => true,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($response === false) {
            $io->error('Errore curl: ' . curl_error($ch));
            $resultCommand = Command::FAILURE;
        } elseif ($httpCode >= 200 && $httpCode < 300) {
            $io->success('Chiamata API riuscita (' . $httpCode . ')');
            $resultCommand = Command::SUCCESS;
        } else {
            $io->error('Chiamata API fallita (' . $httpCode . '): ' . $response);
            $resultCommand = Command::FAILURE;
        }

        curl_close($ch);



        return $resultCommand;
    }


}
