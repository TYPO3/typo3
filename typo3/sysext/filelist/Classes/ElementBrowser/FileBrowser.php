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
use TYPO3\CMS\Backend\Template\Components\Buttons\DropDown\DropDownDivider;
use TYPO3\CMS\Backend\Template\Components\Buttons\DropDown\DropDownItemInterface;
use TYPO3\CMS\Backend\Template\Components\Buttons\DropDown\DropDownRadio;
use TYPO3\CMS\Backend\Template\Components\Buttons\DropDown\DropDownToggle;
use TYPO3\CMS\Backend\Template\Components\Buttons\DropDownButton;
use TYPO3\CMS\Backend\Tree\View\LinkParameterProviderInterface;
use TYPO3\CMS\Backend\View\FolderUtilityRenderer;
use TYPO3\CMS\Backend\View\RecordSearchBoxComponent;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\Search\FileSearchDemand;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\HttpUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Filelist\FileList;
use TYPO3\CMS\Filelist\Matcher\Matcher;
use TYPO3\CMS\Filelist\Matcher\ResourceFileExtensionMatcher;
use TYPO3\CMS\Filelist\Matcher\ResourceFolderTypeMatcher;
use TYPO3\CMS\Filelist\Type\Mode;
use TYPO3\CMS\Filelist\Type\ViewMode;

/**
 * Browser for files. This is used when adding a FAL inline image with the 'add image' button in FormEngine.
 *
 * @internal This class is a specific LinkBrowser implementation and is not part of the TYPO3's Core API.
 */
class FileBrowser extends AbstractElementBrowser implements ElementBrowserInterface, LinkParameterProviderInterface
{
    protected string $identifier = 'file';
    protected ?string $expandFolder = null;
    protected int $currentPage = 1;
    protected string $moduleStorageIdentifier = 'file_list';

    protected ?FileList $filelist = null;
    protected ?string $viewMode = null;
    protected ?string $displayThumbs = null;

    protected ?Folder $selectedFolder = null;
    protected ?string $searchWord = null;
    protected array $allowedFileExtensions = ['*'];
    protected ?FileSearchDemand $searchDemand = null;

    protected ?Matcher $resourceDisplayMatcher = null;
    protected ?Matcher $resourceSelectMatcher = null;

    /**
     * Loads additional JavaScript
     */
    protected function initialize()
    {
        parent::initialize();
        $this->view = $this->backendViewFactory->create($this->getRequest(), ['typo3/cms-filelist']);
        $this->pageRenderer->loadJavaScriptModule('@typo3/filelist/file-list-actions.js');
        $this->pageRenderer->loadJavaScriptModule('@typo3/filelist/browse-files.js');
        $this->pageRenderer->loadJavaScriptModule('@typo3/backend/tree/file-storage-browser.js');
        $this->pageRenderer->loadJavaScriptModule('@typo3/backend/multi-record-selection.js');
        $this->pageRenderer->loadJavaScriptModule('@typo3/backend/global-event-handler.js');
    }

    /**
     * Checks additional GET/POST requests
     */
    protected function initVariables()
    {
        parent::initVariables();
        $request = $this->getRequest();

        $this->currentPage = (int)($request->getParsedBody()['currentPage'] ?? $request->getQueryParams()['currentPage'] ?? 1);
        $this->expandFolder = $request->getParsedBody()['expandFolder'] ?? $request->getQueryParams()['expandFolder'] ?? null;
        $this->searchWord = (string)trim($request->getParsedBody()['searchTerm'] ?? $request->getQueryParams()['searchTerm'] ?? '');

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

        // The key number 3 of the bparams contains the "allowed" string. Disallowed is not passed to
        // the element browser at all but only filtered out in DataHandler afterwards
        $allowedFileExtensions = GeneralUtility::trimExplode(',', explode('|', $this->bparams)[3], true);
        if (!empty($allowedFileExtensions) && !in_array(['sys_file', '*'], $allowedFileExtensions)) {
            $this->allowedFileExtensions = $allowedFileExtensions;
        }

        $this->resourceDisplayMatcher = GeneralUtility::makeInstance(Matcher::class);
        $this->resourceDisplayMatcher->addMatcher(GeneralUtility::makeInstance(ResourceFolderTypeMatcher::class));
        $this->resourceDisplayMatcher->addMatcher(GeneralUtility::makeInstance(ResourceFileExtensionMatcher::class)->setExtensions($this->allowedFileExtensions));
        $this->resourceSelectMatcher = GeneralUtility::makeInstance(Matcher::class);
        $this->resourceSelectMatcher->addMatcher(GeneralUtility::makeInstance(ResourceFileExtensionMatcher::class)->setExtensions($this->allowedFileExtensions));
    }

    /**
     * @return string HTML content
     */
    public function render()
    {
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

            // Prepare search box, since the component should always be displayed, even if no files are available
            $markup[] = '<div class="mb-4">';
            $markup[] = GeneralUtility::makeInstance(RecordSearchBoxComponent::class)
                ->setSearchWord($this->searchWord ?? '')
                ->render($this->getRequest(), $this->createUri([]));
            $markup[] = '</div>';

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
            $this->filelist->setResourceSelectMatcher($this->resourceSelectMatcher);
            $searchDemand = $this->searchWord !== ''
                ? FileSearchDemand::createForSearchTerm($this->searchWord)->withFolder($this->selectedFolder)->withRecursive()
                : null;
            $markup[] = $this->filelist->render($searchDemand, $this->view);

            // Build the file upload and folder creation form
            $folderUtilityRenderer = GeneralUtility::makeInstance(FolderUtilityRenderer::class, $this);
            $markup[] = $folderUtilityRenderer->uploadForm($this->selectedFolder, $this->allowedFileExtensions);
            $markup[] = $folderUtilityRenderer->createFolder($this->selectedFolder);

            $selectedFolderIcon = $this->iconFactory->getIconForResource($this->selectedFolder, Icon::SIZE_SMALL);
            $contentHtml = implode('', $markup);
        }

        $contentOnly = (bool)($this->getRequest()->getQueryParams()['contentOnly'] ?? false);
        $this->pageRenderer->setTitle($this->getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang_browse_links.xlf:fileSelector'));
        $this->view->assignMultiple([
            'selectedFolder' => $this->selectedFolder,
            'selectedFolderIcon' => $selectedFolderIcon ?? '',
            'treeEnabled' => true,
            'initialNavigationWidth' => $this->getBackendUser()->uc['selector']['navigation']['width'] ?? 250,
            'content' => $contentHtml,
            'contentOnly' => $contentOnly,
        ]);

        $content = $this->view->render('ElementBrowser/Files');
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
        if (!($this->getBackendUser()->getTSConfig()['options.']['noThumbsInEB'] ?? false)) {
            $viewModeItems[] = GeneralUtility::makeInstance(DropdownDivider::class);
            $viewModeItems[] = GeneralUtility::makeInstance(DropDownToggle::class)
                ->setActive((bool)$this->displayThumbs)
                ->setHref($this->createUri(['displayThumbs' => $this->displayThumbs ? 0 : 1]))
                ->setLabel($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.view.showThumbnails'))
                ->setIcon($this->iconFactory->getIcon('actions-image'));
        }

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
            'mode' => 'file',
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
