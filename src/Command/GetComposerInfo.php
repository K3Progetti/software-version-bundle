<?php

namespace K3Progetti\SoftwareVersionBundle\Command;

use JsonException;
use K3Progetti\SoftwareVersionBundle\Service\ComposerInfoService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'software-version:get-composer-info', description: 'Restituisce le informazioni del composer')]
class GetComposerInfo extends Command
{

    protected static $defaultName = 'fai-filtri:create-user';

    public function __construct(
        private readonly ComposerInfoService $composerInfoService,
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
        $io->title('Recupera le informazioni del composer');

        $data = $this->composerInfoService->getInfo();

        print_r($data);

        return Command::SUCCESS;
    }


}
