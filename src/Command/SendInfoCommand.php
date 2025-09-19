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
        $info = $this->composerInfoService->getInfo();

        $payload = [
            'project' => [
                'name' => $info['name'],
                'description' => $info['description'],
                'php_installed' => $info['php_installed_version'],
                'symfony_installed' => $info['symfony_installed_version'],
            ],
        ];

        $response = $this->httpClient->request('POST', $endpoint, [
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'json' => $payload,
            'timeout' => 15,
        ]);

        $status = $response->getStatusCode();
        $content = $response->getContent(false); // non solleva eccezione su errori

        if ($status >= 200 && $status < 300) {
            $io->success('Chiamata API riuscita (' . $status . ')');
            $resultCommand = Command::SUCCESS;
        } else {
            $io->error('Chiamata API fallita (' . $status . '): ' . $content);
            $resultCommand = Command::FAILURE;
        }


        return $resultCommand;
    }


}
