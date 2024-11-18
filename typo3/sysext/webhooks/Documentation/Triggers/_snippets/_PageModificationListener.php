<?php

declare(strict_types=1);

namespace MyVendor\MyExtension\Webhooks\Hook;

use Symfony\Component\Messenger\MessageBusInterface;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Webhooks\Message\PageModificationMessage;

final class PageModificationListener
{
    public function __construct(
        private readonly MessageBusInterface $bus,
        private readonly SiteFinder $siteFinder,
    ) {}

    public function processDatamap_afterDatabaseOperations($status, $table, $id, $fieldArray, DataHandler $dataHandler)
    {
        if ($table !== 'pages') {
            return;
        }
        // ...
        $site = $this->siteFinder->getSiteByPageId($id);
        $message = new PageModificationMessage(
            'new',
            $id,
            $fieldArray,
            $site->getIdentifier(),
            (string)$site->getRouter()->generateUri($id),
            $dataHandler->BE_USER,
        );
        // ...
        $this->bus->dispatch($message);
    }
}
