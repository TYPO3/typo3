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

namespace TYPO3\CMS\Opendocs\EventListener;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Controller\Event\AfterRecordOpenedEvent;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Opendocs\Domain\Repository\OpenDocumentRepository;

/**
 * Event listener that tracks recent documents for the backend user.
 *
 * Listens to the record lifecycle events:
 * - Open: Track document as recent
 * - Update: Refresh UI to reflect changes
 * - Delete: Remove from recent list
 *
 * @internal
 */
final readonly class TrackOpenDocumentsEventListener
{
    public function __construct(
        private OpenDocumentRepository $openDocumentRepository,
    ) {}

    /**
     * Track documents when records are opened for editing.
     */
    #[AsEventListener(identifier: 'opendocs/track-opened-documents')]
    public function onRecordOpened(AfterRecordOpenedEvent $event): void
    {
        if (!$this->isBackendContext() || !$this->canHandle($event->table, $event->uid)) {
            return;
        }

        $uid = $this->resolveLiveUid($event->table, (int)$event->uid);

        $this->openDocumentRepository->add($event->table, $uid, $this->getBackendUser());
        $this->requestUpdate();
    }

    /**
     * DataHandler hook: Called after database operations (insert/update).
     * Refreshes UI if the record is in the recent documents list.
     */
    public function processDatamap_afterDatabaseOperations(string $status, string $table, string|int $id, array $fieldArray, DataHandler $dataHandler): void
    {
        if (!$this->isBackendContext() || !$this->canHandle($table, $id)) {
            return;
        }

        $uid = $this->resolveLiveUid($table, (int)$id);
        $identifier = $table . ':' . $uid;

        $backendUser = $this->getBackendUser();
        $recentDocuments = $this->openDocumentRepository->findForUser($backendUser);

        if (isset($recentDocuments[$identifier])) {
            $this->requestUpdate();
        }
    }

    /**
     * DataHandler hook: Called when records are deleted.
     * Removes the document from the recent list.
     */
    public function processCmdmap_deleteAction(string $table, int $id, array $record, bool &$recordWasDeleted, DataHandler $dataHandler): void
    {
        if (!$this->isBackendContext() || !$this->canHandle($table, $id)) {
            return;
        }

        $uid = $this->resolveLiveUid($table, $id);

        $this->openDocumentRepository->remove($table . ':' . $uid, $this->getBackendUser());
        $this->requestUpdate();
    }

    /**
     * Returns true when running inside a backend web request.
     * Returns false in CLI context or frontend requests.
     */
    private function isBackendContext(): bool
    {
        // The CLI request is flagged as a backend request, but the "_cli_" backend
        // user has no user session, so reading module data from it raises an error.
        // There is no recent documents list to keep up to date on CLI anyway.
        if (Environment::isCli()) {
            return false;
        }
        return ($GLOBALS['TYPO3_REQUEST'] ?? null) instanceof ServerRequestInterface
            && ApplicationType::fromRequest($GLOBALS['TYPO3_REQUEST'])->isBackend();
    }

    /**
     * Check if a record should be tracked as a recent document.
     * Returns false for records that haven't been persisted to the database yet
     * or if the table name is empty.
     *
     * @param string $table The database table name
     * @param mixed $uid The record UID (may be integer or string like 'NEW123')
     * @return bool True if the record can be tracked, false otherwise
     */
    private function canHandle(string $table, mixed $uid): bool
    {
        // Only track records with valid table names and persisted integer IDs
        // Skip empty tables, transient records (e.g., 'NEW123'), and 'new' actions
        return $table !== '' && is_numeric($uid);
    }

    /**
     * Resolve a possibly workspace-versioned UID to its live record UID.
     * Returns the input UID unchanged if it is already the live record.
     */
    private function resolveLiveUid(string $table, int $uid): int
    {
        return BackendUtility::getLiveVersionIdOfRecord($table, $uid) ?? $uid;
    }

    private function requestUpdate(): void
    {
        BackendUtility::setUpdateSignal('opendocs:updateRequested');
    }

    private function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
