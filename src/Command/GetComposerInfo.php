<?php

namespace K3Progetti\SoftwareVersionBundle\Command;

use K3Progetti\SoftwareVersionBundle\Service\ComposerInfoService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Fa vedere cosa manderebbe send-info, senza mandarlo
 */
class GetComposerInfo extends Command
{
    /** @var ComposerInfoService */
    private $composerInfoService;

    public function __construct(ComposerInfoService $composerInfoService)
    {
        $this->composerInfoService = $composerInfoService;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('software-version:get-composer-info');
        $this->setDescription('Mostra cosa verrebbe mandato al server delle versioni');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $info = $this->composerInfoService->getInfo();

        $rows = [];
        foreach ($info as $key => $value) {
            $rows[] = [$key, $value === null ? '-' : $value];
        }
        $io->table(['Campo', 'Valore'], $rows);

        return 0;
    }
}
