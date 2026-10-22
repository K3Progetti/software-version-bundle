<?php

namespace K3Progetti\SoftwareVersionBundle\Command;

use K3Progetti\SoftwareVersionBundle\Service\InfoSender;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Manda subito al server delle versioni cosa gira qui. Di solito non serve, ci pensa l'invio
 * automatico dopo le richieste: è per provare e per i progetti senza richieste web
 */
class SendInfoCommand extends Command
{
    /** @var InfoSender */
    private $sender;

    public function __construct(InfoSender $sender)
    {
        $this->sender = $sender;
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

        if ($this->sender->getKey() === null) {
            $io->warning('SOFTWARE_VERSION_KEY non impostata: non mando niente');
            return 0;
        }

        $result = $this->sender->send();
        if (!$result['ok']) {
            $io->error($result['message']);
            return 1;
        }

        $payload = $result['payload'];
        $io->success(sprintf('Inviato: %s, PHP %s, Symfony %s', $payload['name'], $payload['phpVersion'], $payload['symfonyVersion'] ?: '?'));

        return 0;
    }
}
