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

use TYPO3\CMS\Backend\Domain\Model\Element\ImmediateActionElement;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Toolbar\ToolbarItemInterface;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Opendocs\Service\OpenDocumentService;

/**
 * Main functionality to render a list of all open documents in the top bar of the TYPO3 Backend
 * @internal This class is a specific hook implementation and is not part of the TYPO3's Core API.
 */
class OpendocsToolbarItem implements ToolbarItemInterface
{
    /**
     * @var OpenDocumentService
     */
    protected $documentService;

    /**
     * Set up dependencies
     *
     * @param OpenDocumentService|null $documentService
     */
    public function __construct(OpenDocumentService $documentService = null)
    {
        $this->documentService = $documentService ?: GeneralUtility::makeInstance(OpenDocumentService::class);
    }

    /**
     * Checks whether the user has access to this toolbar item
     *
     * @return bool TRUE if user has access, FALSE if not
     */
    public function checkAccess(): bool
    {
        return !(bool)($this->getBackendUser()->getTSConfig()['backendToolbarItem.']['tx_opendocs.']['disabled'] ?? false);
    }

    /**
     * Render toolbar icon via Fluid
     *
     * @return string HTML
     */
    public function getItem()
    {
        $view = $this->getFluidTemplateObject('ToolbarItem.html');

        return $view->render();
    }

    /**
     * This item has a drop down
     *
     * @return bool
     */
    public function hasDropDown()
    {
        return true;
    }

    /**
     * Render drop down via Fluid
     *
     * @return string HTML
     */
    public function getDropDown()
    {
        $view = $this->getFluidTemplateObject('DropDown.html');
        $view->assignMultiple([
            'openDocuments' => $this->getMenuEntries($this->documentService->getOpenDocuments()),
            // If there are "recent documents" in the list, add them
            'recentDocuments' => $this->getMenuEntries($this->documentService->getRecentDocuments()),
        ]);

        return $view->render();
    }

    /**
     * No additional attributes
     *
     * @return array List of attributes
     */
    public function getAdditionalAttributes()
    {
        return [];
    }

    /**
     * Position relative to others
     *
     * @return int
     */
    public function getIndex()
    {
        return 30;
    }

    /**
     * Called as a hook in \TYPO3\CMS\Backend\Utility\BackendUtility::getUpdateSignalCode, calls a JS function to change
     * the number of opened documents
     *
     * @param array $params
     */
    public function updateNumberOfOpenDocsHook(array &$params)
    {
        $params['html'] = ImmediateActionElement::dispatchCustomEvent(
            'typo3:opendocs:updateRequested',
            null,
            true
        );
    }

    /**
     * Get menu entries for all eligible records
     *
     * @param array $documents
     * @return array
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
     * @param array $document
     * @param string $identifier
     * @return array The data of a recent or closed document, or empty array if no record was found (e.g. deleted)
     */
    protected function getMenuEntry(array $document, string $identifier): array
    {
        $table = $document[3]['table'] ?? '';
        $uid = $document[3]['uid'] ?? 0;
        $record = BackendUtility::getRecordWSOL($table, $uid);

        if (!is_array($record)) {
            // Record seems to be deleted
            return [];
        }

        $result = [];
        $result['table'] = $table;
        $result['record'] = $record;
        $result['label'] = strip_tags(htmlspecialchars_decode($document[0]));
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        $uri = (string)$uriBuilder->buildUriFromRoute('record_edit') . '&' . $document[2];
        $pid = (int)$document[3]['pid'];

        if ($document[3]['table'] === 'pages') {
            $pid = (int)$document[3]['uid'];
        }

        $result['pid'] = $pid;
        $result['uri'] = $uri;
        $result['md5sum'] = $identifier;

        return $result;
    }

    /**
     * Returns a new standalone view, shorthand function
     *
     * @param string $filename Which templateFile should be used.
     * @return StandaloneView
     */
    protected function getFluidTemplateObject(string $filename): StandaloneView
    {
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setLayoutRootPaths(['EXT:opendocs/Resources/Private/Layouts']);
        $view->setPartialRootPaths([
            'EXT:backend/Resources/Private/Partials/ToolbarItems',
            'EXT:opendocs/Resources/Private/Partials/ToolbarItems',
        ]);
        $view->setTemplateRootPaths(['EXT:opendocs/Resources/Private/Templates/ToolbarItems']);
        $view->setTemplate($filename);
        $view->getRequest()->setControllerExtensionName('Opendocs');

        return $view;
    }

    /**
     * @return BackendUserAuthentication
     */
    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
