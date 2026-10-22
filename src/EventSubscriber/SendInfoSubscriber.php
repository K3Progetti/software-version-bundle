<?php

namespace K3Progetti\SoftwareVersionBundle\EventSubscriber;

use K3Progetti\SoftwareVersionBundle\Service\InfoSender;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Throwable;

/**
 * L'invio automatico, così non serve un cron: dopo una richiesta, quando la risposta è già
 * partita, al massimo una volta ogni intervallo. Il segno sta nella cache di Symfony: col deploy
 * la cache riparte vuota e la prima richiesta manda subito, così la data di rilascio è giusta
 */
final class SendInfoSubscriber implements EventSubscriberInterface
{
    /** @var InfoSender */
    private $sender;

    /** @var string */
    private $cacheDir;

    /** @var bool */
    private $enabled;

    /** @var int */
    private $interval;

    public function __construct(InfoSender $sender, string $cacheDir, bool $enabled, int $interval)
    {
        $this->sender = $sender;
        $this->cacheDir = $cacheDir;
        $this->enabled = $enabled;
        $this->interval = $interval;
    }

    public static function getSubscribedEvents(): array
    {
        // Priorità bassa: prima finiscono gli altri listener di terminate
        return [KernelEvents::TERMINATE => ['onTerminate', -1024]];
    }

    public function onTerminate(): void
    {
        if (!$this->enabled || $this->sender->getKey() === null) {
            return;
        }

        $mark = $this->cacheDir . '/software_version_sent';
        $last = @filemtime($mark);
        if ($last !== false && time() - $last < $this->interval) {
            return;
        }
        // Il segno lo metto prima di mandare: con tante richieste insieme ne parte una sola,
        // e se il server delle versioni è giù riprovo all'intervallo dopo, non a ogni richiesta
        if (@touch($mark) === false) {
            return;
        }

        try {
            $this->sender->send(5);
        } catch (Throwable $e) {
            // Il progetto non si deve mai accorgere di questo invio
        }
    }
}
