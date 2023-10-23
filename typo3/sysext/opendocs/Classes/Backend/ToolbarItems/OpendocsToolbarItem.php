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

namespace TYPO3\CMS\Opendocs\Backend\ToolbarItems;

use Doctrine\DBAL\Exception\TableNotFoundException;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Domain\Model\Element\ImmediateActionElement;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Toolbar\RequestAwareToolbarItemInterface;
use TYPO3\CMS\Backend\Toolbar\ToolbarItemInterface;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\View\BackendViewFactory;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Opendocs\Service\OpenDocumentService;

/**
 * Main functionality to render a list of all open documents in the top bar of the Backend.
 *
 * @internal This class is a specific hook implementation and is not part of the TYPO3's Core API.
 */
class OpendocsToolbarItem implements ToolbarItemInterface, RequestAwareToolbarItemInterface
{
    private ServerRequestInterface $request;

    public function __construct(
        private readonly OpenDocumentService $documentService,
        private readonly UriBuilder $uriBuilder,
        private readonly BackendViewFactory $backendViewFactory,
    ) {}

    public function setRequest(ServerRequestInterface $request): void
    {
        $this->request = $request;
    }

    /**
     * Checks whether the user has access to this toolbar item.
     */
    public function checkAccess(): bool
    {
        return !(bool)($this->getBackendUser()->getTSConfig()['backendToolbarItem.']['tx_opendocs.']['disabled'] ?? false);
    }

    /**
     * Render toolbar icon via Fluid
     */
    public function getItem(): string
    {
        $view = $this->backendViewFactory->create($this->request, ['typo3/cms-opendocs']);
        return $view->render('ToolbarItems/ToolbarItem');
    }

    /**
     * This item has a drop-down.
     */
    public function hasDropDown(): bool
    {
        return true;
    }

    /**
     * Render drop-down.
     */
    public function getDropDown(): string
    {
        $view = $this->backendViewFactory->create($this->request, ['typo3/cms-opendocs']);
        $view->assignMultiple([
            'openDocuments' => $this->getMenuEntries($this->documentService->getOpenDocuments()),
            // If there are "recent documents" in the list, add them
            'recentDocuments' => $this->getMenuEntries($this->documentService->getRecentDocuments()),
        ]);
        return $view->render('ToolbarItems/DropDown');
    }

    /**
     * No additional attributes
     */
    public function getAdditionalAttributes(): array
    {
        return [];
    }

    /**
     * Position relative to others
     */
    public function getIndex(): int
    {
        return 30;
    }

    /**
     * Called as a hook in \TYPO3\CMS\Backend\Utility\BackendUtility::getUpdateSignalCode, calls a JS function
     * to change the number of opened documents.
     */
    public function updateNumberOfOpenDocsHook(array &$params): void
    {
        $params['html'] = ImmediateActionElement::dispatchCustomEvent(
            'typo3:opendocs:updateRequested',
            null,
            true
        );
    }

    /**
     * Get menu entries for all eligible records.
     */
    protected function getMenuEntries(array $documents): array
    {
        $entries = [];
        foreach ($documents as $identifier => $document) {
            $menuEntry = $this->getMenuEntry($document, $identifier);
            if (!empty($menuEntry)) {
                $entries[] = $menuEntry;
            }
        }
        return $entries;
    }

    /**
     * Returns the data for a recent or open document
     *
     * @return array The data of a recent or closed document, or empty array if no record was found (e.g. deleted)
     */
    protected function getMenuEntry(array $document, string $identifier): array
    {
        $table = $document[3]['table'] ?? '';
        $uid = $document[3]['uid'] ?? 0;

        try {
            $record = BackendUtility::getRecordWSOL($table, $uid);
        } catch (TableNotFoundException) {
            // This exception is caught in cases, when you have an recently opened document
            // from an extension record (let's say a sys_note record) and then uninstall
            // the extension and drop the DB table. After then, the DB table could
            // not be found anymore and will throw an exception making the
            // whole backend unusable.
            $record = null;
        }

        if (!is_array($record)) {
            // Record seems to be deleted
            return [];
        }

        $result = [];
        $result['table'] = $table;
        $result['record'] = $record;
        $result['label'] = strip_tags(htmlspecialchars_decode($document[0]));
        $uri = $this->uriBuilder->buildUriFromRoute('record_edit', ['returnUrl' => $document[4] ?? null]) . '&' . $document[2];
        $pid = (int)$document[3]['pid'];

        if ($document[3]['table'] === 'pages') {
            $pid = (int)$document[3]['uid'];
        }

        $result['pid'] = $pid;
        $result['uri'] = $uri;
        $result['md5sum'] = $identifier;

        return $result;
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
