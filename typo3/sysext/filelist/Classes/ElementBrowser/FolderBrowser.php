<?php

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

namespace TYPO3\CMS\Filelist\ElementBrowser;

use TYPO3\CMS\Backend\ElementBrowser\AbstractElementBrowser;
use TYPO3\CMS\Backend\ElementBrowser\ElementBrowserInterface;
use TYPO3\CMS\Backend\Routing\Route;
use TYPO3\CMS\Backend\Template\Components\Buttons\DropDown\DropDownItemInterface;
use TYPO3\CMS\Backend\Template\Components\Buttons\DropDown\DropDownRadio;
use TYPO3\CMS\Backend\Template\Components\Buttons\DropDownButton;
use TYPO3\CMS\Backend\Tree\View\LinkParameterProviderInterface;
use TYPO3\CMS\Backend\View\FolderUtilityRenderer;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\HttpUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Filelist\FileList;
use TYPO3\CMS\Filelist\Matcher\Matcher;
use TYPO3\CMS\Filelist\Matcher\ResourceFolderTypeMatcher;
use TYPO3\CMS\Filelist\Type\Mode;
use TYPO3\CMS\Filelist\Type\ViewMode;

/**
 * Browser for folders. This is used with type=folder to select folders.
 *
 * @internal This class is a specific LinkBrowser implementation and is not part of the TYPO3's Core API.
 */
class FolderBrowser extends AbstractElementBrowser implements ElementBrowserInterface, LinkParameterProviderInterface
{
    protected string $identifier = 'folder';
    protected ?string $expandFolder = null;
    protected int $currentPage = 1;
    protected string $moduleStorageIdentifier = 'file_list';

    protected ?FileList $filelist = null;
    protected ?string $viewMode = null;
    protected ?string $displayThumbs = null;

    protected ?Folder $selectedFolder = null;

    protected ?Matcher $resourceDisplayMatcher = null;
    protected ?Matcher $resourceSelectableMatcher = null;

    /**
     * Adds additional JavaScript modules
     */
    protected function initialize()
    {
        parent::initialize();
        $this->view = $this->backendViewFactory->create($this->getRequest(), ['typo3/cms-filelist']);
        $this->pageRenderer->loadJavaScriptModule('@typo3/filelist/file-list-actions.js');
        $this->pageRenderer->loadJavaScriptModule('@typo3/filelist/browse-folders.js');
        $this->pageRenderer->loadJavaScriptModule('@typo3/backend/tree/file-storage-browser.js');
        $this->pageRenderer->loadJavaScriptModule('@typo3/backend/multi-record-selection.js');
        $this->pageRenderer->loadJavaScriptModule('@typo3/backend/global-event-handler.js');
    }

    /**
     * Checks for an additional request parameter
     */
    protected function initVariables()
    {
        parent::initVariables();
        $request = $this->getRequest();

        $this->currentPage = (int)($request->getParsedBody()['currentPage'] ?? $request->getQueryParams()['currentPage'] ?? 1);
        $this->expandFolder = $request->getParsedBody()['expandFolder'] ?? $request->getQueryParams()['expandFolder'] ?? null;

        $this->viewMode = $request->getParsedBody()['viewMode'] ?? $request->getQueryParams()['viewMode'] ?? null;
        if ($this->viewMode !== null) {
            $this->getBackendUser()->pushModuleData(
                $this->moduleStorageIdentifier,
                array_merge($this->getBackendUser()->getModuleData($this->moduleStorageIdentifier) ?? [], ['viewMode' => $this->viewMode])
            );
        } else {
            $this->viewMode = $this->getBackendUser()->getModuleData($this->moduleStorageIdentifier)['viewMode'] ?? ViewMode::TILES->value;
        }

        $this->displayThumbs = $request->getParsedBody()['displayThumbs'] ?? $request->getParsedBody()['displayThumbs'] ?? null;
        if ($this->displayThumbs !== null) {
            $this->getBackendUser()->pushModuleData(
                $this->moduleStorageIdentifier,
                array_merge($this->getBackendUser()->getModuleData($this->moduleStorageIdentifier) ?? [], ['displayThumbs' => $this->displayThumbs])
            );
        } else {
            $this->displayThumbs = $this->getBackendUser()->getModuleData($this->moduleStorageIdentifier)['displayThumbs'] ?? true;
        }

        $this->filelist = GeneralUtility::makeInstance(FileList::class, $this->getRequest());
        $this->filelist->viewMode = ViewMode::tryFrom($this->viewMode) ?? ViewMode::TILES;
        $this->filelist->thumbs = ($GLOBALS['TYPO3_CONF_VARS']['GFX']['thumbnails'] ?? false) && $this->displayThumbs;

        $this->resourceDisplayMatcher = GeneralUtility::makeInstance(Matcher::class);
        $this->resourceDisplayMatcher->addMatcher(GeneralUtility::makeInstance(ResourceFolderTypeMatcher::class));
        $this->resourceSelectableMatcher = GeneralUtility::makeInstance(Matcher::class);
        $this->resourceSelectableMatcher->addMatcher(GeneralUtility::makeInstance(ResourceFolderTypeMatcher::class));
    }

    /**
     * @return string HTML content
     */
    public function render()
    {
        $selectedFolderIcon = '';
        $contentHtml = '';
        $resourceFactory = GeneralUtility::makeInstance(ResourceFactory::class);

        // Select folder
        if ($this->expandFolder) {
            $this->selectedFolder = $resourceFactory->getFolderObjectFromCombinedIdentifier($this->expandFolder);
        }

        if (!$this->selectedFolder) {
            $this->selectedFolder = $resourceFactory->getDefaultStorage()?->getRootLevelFolder() ?? null;
        }

        if ($this->selectedFolder instanceof Folder) {
            $markup = [];

            // Select and multiselect
            $markup[] = '<div id="filelist">';
            $markup[] = '  <div class="row row-cols-auto justify-content-between gx-0 list-header multi-record-selection-actions-wrapper">';
            $markup[] = '      <div class="col-auto">';
            $markup[] = '          <div class="row row-cols-auto align-items-center g-2 t3js-multi-record-selection-actions hidden">';
            $markup[] = '              <div class="col">';
            $markup[] = '                  <strong>' . htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.selection')) . '</strong>';
            $markup[] = '              </div>';
            $markup[] = '              <div class="col">';
            $markup[] = '                  <button type="button" class="btn btn-default btn-sm" data-multi-record-selection-action="import" title="' . htmlspecialchars($this->getLanguageService()->getLL('importSelection')) . '">';
            $markup[] = '                      ' . $this->iconFactory->getIcon('actions-document-import-t3d', Icon::SIZE_SMALL);
            $markup[] = '                      ' . htmlspecialchars($this->getLanguageService()->getLL('importSelection'));
            $markup[] = '                  </button>';
            $markup[] = '              </div>';
            $markup[] = '          </div>';
            $markup[] = '      </div>';
            $markup[] = '      <div class="col-auto">';
            $markup[] = '        ' . $this->getViewModeSelector();
            $markup[] = '      </div>';
            $markup[] = '   </div>';
            $markup[] = '</div>';

            // Create the filelist
            $this->filelist->start($this->selectedFolder, MathUtility::forceIntegerInRange($this->currentPage, 1, 100000), 'asc', false, Mode::BROWSE);
            $this->filelist->setResourceDisplayMatcher($this->resourceDisplayMatcher);
            $this->filelist->setResourceSelectableMatcher($this->resourceSelectableMatcher);
            $markup[] = $this->filelist->render(null, $this->view);

            // Build the folder creation form
            $folderUtilityRenderer = GeneralUtility::makeInstance(FolderUtilityRenderer::class, $this);
            $markup[] = $folderUtilityRenderer->createFolder($this->selectedFolder);

            $selectedFolderIcon = $this->iconFactory->getIconForResource($this->selectedFolder, Icon::SIZE_SMALL);
            $contentHtml = implode('', $markup);
        }

        $contentOnly = (bool)($this->getRequest()->getQueryParams()['contentOnly'] ?? false);
        $this->pageRenderer->setTitle($this->getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang_browse_links.xlf:folderSelector'));
        $this->view->assignMultiple([
            'selectedFolder' => $this->selectedFolder,
            'selectedFolderIcon' => $selectedFolderIcon,
            'treeEnabled' => true,
            'initialNavigationWidth' => $this->getBackendUser()->uc['selector']['navigation']['width'] ?? 250,
            'content' => $contentHtml,
            'contentOnly' => $contentOnly,
        ]);

        $content = $this->view->render('ElementBrowser/Folder');
        if ($contentOnly) {
            return $content;
        }
        $this->pageRenderer->setBodyContent('<body ' . $this->getBodyTagParameters() . '>' . $content);
        return $this->pageRenderer->render();
    }

    protected function getViewModeSelector(): string
    {
        $viewModeItems = [];
        $viewModeItems[] = GeneralUtility::makeInstance(DropDownRadio::class)
            ->setActive($this->viewMode === ViewMode::TILES->value)
            ->setHref($this->createUri(['viewMode' => ViewMode::TILES->value]))
            ->setLabel($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.view.tiles'))
            ->setIcon($this->iconFactory->getIcon('actions-viewmode-tiles'));
        $viewModeItems[] = GeneralUtility::makeInstance(DropDownRadio::class)
            ->setActive($this->viewMode === ViewMode::LIST->value)
            ->setHref($this->createUri(['viewMode' => ViewMode::LIST->value]))
            ->setLabel($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.view.list'))
            ->setIcon($this->iconFactory->getIcon('actions-viewmode-list'));
        $viewModeButton = GeneralUtility::makeInstance(DropDownButton::class)
            ->setLabel($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.view'));
        foreach ($viewModeItems as $viewModeItem) {
            /** @var DropDownItemInterface $viewModeItem */
            $viewModeButton->addItem($viewModeItem);
        }

        return (string)$viewModeButton;
    }

    protected function createUri(array $parameters = []): string
    {
        $parameters = $this->getUrlParameters($parameters);
        if (($route = $this->getRequest()->getAttribute('route')) instanceof Route) {
            $scriptUrl = (string)$this->uriBuilder->buildUriFromRoute($route->getOption('_identifier'), $parameters);
        } else {
            $scriptUrl = ($this->thisScript ?: PathUtility::basename(Environment::getCurrentScript())) . HttpUtility::buildQueryString($parameters, '&');
        }

        return $scriptUrl;
    }

    /**
     * @param array $parameters Array of values to include into the parameters
     * @return string[] Array of parameters which have to be added to URLs
     */
    public function getUrlParameters(array $parameters): array
    {
        $parameters = array_replace_recursive([
            'mode' => 'folder',
            'expandFolder' => $parameters['identifier'] ?? $this->expandFolder,
            'bparams' => $this->bparams,
        ], $parameters);

        $parameters = array_filter($parameters, static function ($value) {
            return $value !== null && trim($value) !== '';
        });

        return $parameters;
    }

    /**
     * Session data for this class can be set from outside with this method.
     *
     * @param mixed[] $data Session data array
     * @return array<int, array|bool> Session data and boolean which indicates that data needs to be stored in session because it's changed
     */
    public function processSessionData($data)
    {
        if ($this->expandFolder !== null) {
            $data['expandFolder'] = $this->expandFolder;
            $store = true;
        } else {
            $this->expandFolder = $data['expandFolder'] ?? null;
            $store = false;
        }
        return [$data, $store];
    }

    /**
     * @param array $values Values to be checked
     * @return bool Returns TRUE if the given values match the currently selected item
     */
    public function isCurrentlySelectedItem(array $values)
    {
        return false;
    }

    /**
     * Returns the URL of the current script
     *
     * @return string
     */
    public function getScriptUrl()
    {
        return $this->thisScript;
    }
}
