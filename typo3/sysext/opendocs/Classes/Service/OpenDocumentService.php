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

namespace TYPO3\CMS\Opendocs\Service;

use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;

/**
 * Access to open and recent documents
 * @internal helper class
 */
class OpenDocumentService
{
    /**
     * Get the list of open documents for the current user
     */
    public function getOpenDocuments(): array
    {
        $openDocuments = [];
        $sessionOpenDocuments = $this->getBackendUser()->getModuleData('FormEngine', 'ses');

        if ($sessionOpenDocuments !== null) {
            $openDocuments = $sessionOpenDocuments[0];
        }

        return $openDocuments;
    }

    /**
     * Get the list of recent documents for the current user
     */
    public function getRecentDocuments(): array
    {
        return $this->getBackendUser()->getModuleData('opendocs::recent') ?: [];
    }

    /**
     * Close a document and add it to the list of recent documents
     *
     * @param string $identifier a document identifier (MD5 hash)
     */
    public function closeDocument(string $identifier): void
    {
        $openDocuments = $this->getOpenDocuments();

        if (!isset($openDocuments[$identifier])) {
            return;
        }

        $document = $openDocuments[$identifier];
        unset($openDocuments[$identifier]);

        $this->storeOpenDocuments($openDocuments);
        $this->addToRecentDocuments($identifier, $document);
    }

    /**
     * Closes all open documents
     */
    public function closeAllDocuments(): void
    {
        $openDocuments = $this->getOpenDocuments();
        $this->storeOpenDocuments([]);
        foreach ($openDocuments as $identifier => $document) {
            $this->addToRecentDocuments($identifier, $document);
        }
    }

    /**
     * Store a list of open documents
     */
    protected function storeOpenDocuments(array $openDocuments): void
    {
        [, $lastOpenDocumentIdentifier] = $this->getBackendUser()->getModuleData('FormEngine', 'ses');
        $this->getBackendUser()->pushModuleData('FormEngine', [$openDocuments, $lastOpenDocumentIdentifier]);
    }

    /**
     * Add a document to the list of recent documents
     *
     * @param string $identifier identifier of the document
     * @param array $document document data
     */
    protected function addToRecentDocuments(string $identifier, array $document): void
    {
        $recentDocuments = $this->getRecentDocuments();
        $recentDocuments = array_merge(
            [$identifier => $document],
            $recentDocuments
        );

        // Allow a maximum of 8 recent documents
        if (count($recentDocuments) > 8) {
            $recentDocuments = array_slice($recentDocuments, 0, 8);
        }

        $this->getBackendUser()->pushModuleData('opendocs::recent', $recentDocuments);
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
