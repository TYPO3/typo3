<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace TYPO3\CMS\Webhooks\Listener;

use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Webhooks\Message\PageModificationMessage;

/**
 * Creates a message everytime something changed within a page.
 *
 * This example does not use PSR-14 events, but creates a message manually,
 * which is then dispatched.
 *
 * @internal not part of TYPO3 Core API
 */
class PageModificationListener
{
    public function __construct(
        protected readonly MessageBusInterface $bus,
        protected readonly LoggerInterface $logger,
        protected readonly SiteFinder $siteFinder,
    ) {}

    public function processDatamap_afterDatabaseOperations($status, $table, $id, $fieldArray, DataHandler $dataHandler)
    {
        if ($table !== 'pages') {
            return;
        }
        if (!MathUtility::canBeInterpretedAsInteger($id)) {
            $id = $dataHandler->substNEWwithIDs[$id];
        }
        try {
            $id = (int)$id;
            $site = $this->siteFinder->getSiteByPageId($id);
        } catch (SiteNotFoundException) {
            // The ID did not have a proper connection to a site, so this is skipped (e.g. when creating a fully new page tree)
            return;
        }
        if ($status === 'new') {
            $message = new PageModificationMessage(
                'new',
                $id,
                $fieldArray,
                (string)$site->getRouter()->generateUri($id),
                $site->getIdentifier(),
                $dataHandler->BE_USER,
            );
        } else {
            if (isset($fieldArray['hidden'])) {
                $action = $fieldArray['hidden'] ? 'unpublished' : 'published';
            } else {
                $action = 'modified';
            }
            $message = new PageModificationMessage(
                $action,
                $id,
                BackendUtility::getRecord('pages', $id),
                (string)$site->getRouter()->generateUri($id),
                $site->getIdentifier(),
                $dataHandler->BE_USER,
                $fieldArray
            );
        }
        $this->dispatchMessage($message);
    }

    protected function dispatchMessage(PageModificationMessage $message): void
    {
        try {
            $this->bus->dispatch($message);
        } catch (\Throwable $e) {
            // At the moment we ignore every exception here, but we log them.
            // An exception here means that an error happens while sending the webhook,
            // and we should not block the execution of other configured webhooks.
            // This can happen if no transport is configured, and the message is handled directly.
            $this->logger->error(get_class($message) . ': ' . $e->getMessage());
        }
    }
}
