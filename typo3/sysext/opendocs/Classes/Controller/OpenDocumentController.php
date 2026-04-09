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

namespace TYPO3\CMS\Opendocs\Controller;

use Doctrine\DBAL\Exception\TableNotFoundException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Attribute\AsController;
use TYPO3\CMS\Backend\Breadcrumb\BreadcrumbFactory;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\Components\Breadcrumb;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Opendocs\Domain\Model\OpenDocument;
use TYPO3\CMS\Opendocs\Domain\Repository\OpenDocumentRepository;

/**
 * Controller for documents processing.
 * Contains AJAX endpoints for the open docs toolbar item.
 *
 * @internal This class is a specific Backend controller implementation and is not part of the TYPO3's Core API.
 */
#[AsController]
readonly class OpenDocumentController
{
    public function __construct(
        protected OpenDocumentRepository $openDocumentRepository,
        protected IconFactory $iconFactory,
        protected UriBuilder $uriBuilder,
        protected BreadcrumbFactory $breadcrumbFactory,
        protected Breadcrumb $breadcrumb,
        protected ResourceFactory $resourceFactory,
    ) {}

    /**
     * Returns all recent documents as JSON.
     */
    public function list(ServerRequestInterface $request): ResponseInterface
    {
        $backendUser = $this->getBackendUser();

        return new JsonResponse([
            'success' => true,
            'recentDocuments' => $this->serializeDocuments(
                $this->openDocumentRepository->findForUser($backendUser)
            ),
        ]);
    }

    /**
     * Serialize documents to JSON-friendly format.
     *
     * @param array<string, OpenDocument> $documents
     * @return array<int, array<string, mixed>>
     */
    protected function serializeDocuments(array $documents): array
    {
        $result = [];
        foreach ($documents as $document) {
            $serialized = $this->serializeDocument($document);
            if ($serialized !== null) {
                $result[] = $serialized;
            }
        }
        return $result;
    }

    /**
     * Serialize a single document to JSON-friendly format.
     *
     * @return array<string, mixed>|null Returns null if document is invalid or deleted
     */
    protected function serializeDocument(OpenDocument $document): ?array
    {
        $table = $document->table;
        $uid = $document->uid;

        try {
            $record = BackendUtility::getRecordWSOL($table, $uid);
        } catch (TableNotFoundException) {
            // Table might have been removed (e.g., extension uninstalled)
            return null;
        }

        if (!is_array($record)) {
            // Record seems to be deleted
            return null;
        }

        // Use the UID from the fetched record (workspace overlay if applicable)
        $recordUid = (int)$record['uid'];

        // Derive display data from the record
        $label = BackendUtility::getRecordTitle($table, $record);
        $uri = $this->uriBuilder->buildUriFromRoute('record_edit', ['edit' => [$table => [$recordUid => 'edit']]]);

        // Get icon identifiers from Icon object
        $icon = $this->iconFactory->getIconForRecord($table, $record, IconSize::SMALL);
        $iconIdentifier = $icon->getIdentifier();
        $iconOverlayIdentifier = $icon->getOverlayIcon()?->getIdentifier() ?? '';

        return [
            'identifier' => $document->getIdentifier(),
            'title' => strip_tags($label),
            'uri' => (string)$uri,
            'iconIdentifier' => $iconIdentifier,
            'iconOverlayIdentifier' => $iconOverlayIdentifier,
            'breadcrumb' => $this->getBreadcrumb($table, $record),
            'updatedAt' => $document->updatedAt->format(\DateTimeInterface::ATOM),
        ];
    }

    /**
     * Get breadcrumb nodes for a record using TYPO3's breadcrumb infrastructure.
     *
     * @return array<int, array<string, mixed>> Array of serialized BreadcrumbNode objects
     */
    protected function getBreadcrumb(string $table, array $record): array
    {
        try {
            // Handle file/resource records
            if (in_array($table, ['sys_file', 'sys_file_metadata'], true)) {
                return $this->getFileBreadcrumb($table, $record);
            }

            $uid = (int)($record['uid'] ?? 0);
            if ($uid <= 0) {
                return [];
            }

            $context = $this->breadcrumbFactory->forEditAction($table, $uid);
            $nodes = $this->breadcrumb->getBreadcrumb(null, $context);

            return array_map(fn($node) => $node->jsonSerialize(), $nodes);
        } catch (\Exception) {
            return [];
        }
    }

    /**
     * Get breadcrumb nodes for file/resource records.
     *
     * @return array<int, array<string, mixed>> Array of serialized BreadcrumbNode objects
     */
    protected function getFileBreadcrumb(string $table, array $record): array
    {
        try {
            $fileUid = $table === 'sys_file' ? (int)($record['uid'] ?? 0) : (int)($record['file'] ?? 0);
            if ($fileUid === 0) {
                return [];
            }

            $file = $this->resourceFactory->getFileObject($fileUid);
            $context = $this->breadcrumbFactory->forResource($file);
            $nodes = $this->breadcrumb->getBreadcrumb(null, $context);

            return array_map(fn($node) => $node->jsonSerialize(), $nodes);
        } catch (\Exception) {
            return [];
        }
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
