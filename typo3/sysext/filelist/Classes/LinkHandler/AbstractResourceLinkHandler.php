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

namespace TYPO3\CMS\Filelist\LinkHandler;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Controller\AbstractLinkBrowserController;
use TYPO3\CMS\Backend\LinkHandler\LinkHandlerInterface;
use TYPO3\CMS\Backend\LinkHandler\LinkHandlerVariableProviderInterface;
use TYPO3\CMS\Backend\LinkHandler\LinkHandlerViewProviderInterface;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\Components\Buttons\ButtonInterface;
use TYPO3\CMS\Backend\Template\Components\Buttons\DropDown\DropDownDivider;
use TYPO3\CMS\Backend\Template\Components\Buttons\DropDown\DropDownItemInterface;
use TYPO3\CMS\Backend\Template\Components\Buttons\DropDown\DropDownRadio;
use TYPO3\CMS\Backend\Template\Components\Buttons\DropDown\DropDownToggle;
use TYPO3\CMS\Backend\Template\Components\Buttons\DropDownButton;
use TYPO3\CMS\Backend\Tree\View\LinkParameterProviderInterface;
use TYPO3\CMS\Backend\View\BackendViewFactory;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\LinkHandling\LinkService;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Resource\Exception\FolderDoesNotExistException;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\View\ViewInterface;
use TYPO3\CMS\Filelist\FileList;
use TYPO3\CMS\Filelist\Matcher\Matcher;
use TYPO3\CMS\Filelist\Type\LinkType;
use TYPO3\CMS\Filelist\Type\ViewMode;

/**
 * @internal
 */
abstract class AbstractResourceLinkHandler implements LinkHandlerInterface, LinkHandlerVariableProviderInterface, LinkHandlerViewProviderInterface, LinkParameterProviderInterface
{
    protected ?string $expandFolder = null;
    protected int $currentPage = 1;
    protected string $moduleStorageIdentifier = 'media_management';

    protected ?FileList $filelist = null;
    protected ?ViewMode $viewMode = null;
    protected bool $displayThumbs = true;

    protected ?Folder $selectedFolder = null;
    protected ?Matcher $resourceDisplayMatcher = null;
    protected ?Matcher $resourceSelectableMatcher = null;

    protected LinkType $type;
    protected array $linkParts = [];

    protected ViewInterface $view;
    protected LanguageService $languageService;
    protected AbstractLinkBrowserController $linkBrowser;

    public function __construct(
        protected readonly IconFactory $iconFactory,
        protected readonly ResourceFactory $resourceFactory,
        protected readonly PageRenderer $pageRenderer,
        protected readonly UriBuilder $uriBuilder,
        protected readonly LanguageServiceFactory $languageServiceFactory
    ) {
        $this->languageService = $this->languageServiceFactory->createFromUserPreferences($this->getBackendUser());
    }

    public function canHandleLink(array $linkParts): bool
    {
        if (!$linkParts['url']) {
            return false;
        }
        if (isset($linkParts['url'][$this->type->value]) && $linkParts['url'][$this->type->value] instanceof ($this->type->getResourceType())) {
            $this->linkParts = $linkParts;
            return true;
        }
        return false;
    }

    public function formatCurrentUrl(): string
    {
        $resource = $this->linkParts['url'][$this->type->value];
        if (!$resource->checkActionPermission('read')) {
            return '';
        }
        if ($resource->getStorage()->isFallbackStorage()) {
            return '';
        }
        return $this->linkParts['url'][$this->type->value]->getName();
    }

    public function createView(BackendViewFactory $backendViewFactory, ServerRequestInterface $request): ViewInterface
    {
        return $backendViewFactory->create($request, ['typo3/cms-filelist']);
    }

    public function setView(ViewInterface $view): self
    {
        $this->view = $view;
        return $this;
    }

    public function getView(): ViewInterface
    {
        return $this->view;
    }

    public function getLinkAttributes(): array
    {
        return ['target', 'title', 'class', 'params', 'rel'];
    }

    public function initialize(AbstractLinkBrowserController $linkBrowser, $identifier, array $configuration)
    {
        $this->linkBrowser = $linkBrowser;
    }

    public function initializeVariables(ServerRequestInterface $request): void
    {
        $this->pageRenderer->loadJavaScriptModule('@typo3/backend/viewport/resizable-navigation.js');
        $this->pageRenderer->loadJavaScriptModule('@typo3/backend/tree/file-storage-browser.js');
        $this->pageRenderer->loadJavaScriptModule('@typo3/filelist/file-list-actions.js');

        $this->currentPage = (int)($request->getParsedBody()['currentPage'] ?? $request->getQueryParams()['currentPage'] ?? 1);

        $this->viewMode = ViewMode::tryFrom($request->getParsedBody()['viewMode'] ?? $request->getQueryParams()['viewMode'] ?? '');
        if ($this->viewMode !== null) {
            $this->getBackendUser()->pushModuleData(
                $this->moduleStorageIdentifier,
                array_merge($this->getBackendUser()->getModuleData($this->moduleStorageIdentifier) ?? [], ['viewMode' => $this->viewMode->value])
            );
        } else {
            $this->viewMode = ViewMode::tryFrom($this->getBackendUser()->getModuleData($this->moduleStorageIdentifier)['viewMode'] ?? '')
                ?? ViewMode::tryFrom($this->getBackendUser()->getTSConfig()['options.']['defaultResourcesViewMode'] ?? '')
                ?? ViewMode::TILES;
        }

        $displayThumbs = $request->getParsedBody()['displayThumbs'] ?? $request->getQueryParams()['displayThumbs'] ?? null;
        if ($displayThumbs !== null) {
            $this->displayThumbs = (bool)$displayThumbs;
            $this->getBackendUser()->pushModuleData(
                $this->moduleStorageIdentifier,
                array_merge($this->getBackendUser()->getModuleData($this->moduleStorageIdentifier) ?? [], ['displayThumbs' => $this->displayThumbs])
            );
        } else {
            $this->displayThumbs = (bool)($this->getBackendUser()->getModuleData($this->moduleStorageIdentifier)['displayThumbs'] ?? true);
        }

        // Selected Folder folder
        $this->expandFolder = $request->getParsedBody()['expandFolder'] ?? $request->getQueryParams()['expandFolder'] ?? null;
        if ($this->expandFolder === null) {
            if (!empty($this->linkParts)) {
                $resource = $this->linkParts['url'][$this->type->value];
                if ($resource instanceof File) {
                    $resource = $resource->getParentFolder();
                }
                if ($resource instanceof Folder) {
                    $this->expandFolder = $resource->getCombinedIdentifier();
                    if ($this->type === LinkType::FOLDER) {
                        // Select the parent folder of selected folder as entry point.
                        $parentFolder = $resource->getParentFolder();
                        if ($parentFolder instanceof Folder) {
                            $this->expandFolder = $parentFolder->getCombinedIdentifier();
                        }
                    }
                }
            } else {
                // Look up in the user's session which folder was opened the last time
                $moduleSessionData = $this->getBackendUser()->getModuleData('browse_links.php', 'ses');
                $this->expandFolder = $moduleSessionData['expandFolder'] ?? null;
            }
        }
        if ($this->expandFolder) {
            try {
                $this->selectedFolder = $this->resourceFactory->getFolderObjectFromCombinedIdentifier($this->expandFolder);
            } catch (FolderDoesNotExistException $e) {
            }
        }
        if ($this->selectedFolder?->checkActionPermission('read') === false) {
            $this->selectedFolder = null;
        }
        if ($this->selectedFolder?->getStorage()?->isFallbackStorage()) {
            $this->selectedFolder = null;
        }
        if (!$this->selectedFolder) {
            $this->selectedFolder = $this->resourceFactory->getDefaultStorage()?->getRootLevelFolder() ?? null;
        }

        $this->filelist = GeneralUtility::makeInstance(FileList::class, $request);
        $this->filelist->viewMode = $this->viewMode;
        $this->filelist->thumbs = ($GLOBALS['TYPO3_CONF_VARS']['GFX']['thumbnails'] ?? false) && $this->displayThumbs;
    }

    public function modifyLinkAttributes(array $fieldDefinitions): array
    {
        return $fieldDefinitions;
    }

    public function isUpdateSupported(): bool
    {
        $resource = $this->linkParts['url'][$this->type->value];
        if (!$resource->checkActionPermission('read')) {
            return false;
        }
        if ($resource->getStorage()->isFallbackStorage()) {
            return false;
        }
        return true;
    }

    /**
     * @return string[] Array of body-tag attributes
     */
    public function getBodyTagAttributes(): array
    {
        $resource = $this->linkParts['url'][$this->type->value] ?? null;
        if (!$resource instanceof ($this->type->getResourceType())) {
            return [];
        }
        if (!$resource->checkActionPermission('read')) {
            return [];
        }
        if ($resource->getStorage()->isFallbackStorage()) {
            return [];
        }
        return [
            'data-linkbrowser-current-link' => GeneralUtility::makeInstance(LinkService::class)->asString([
                'type' => $this->type->getLinkServiceType(),
                $this->type->value => $resource,
            ]),
        ];
    }

    protected function createUri(ServerRequestInterface $request, array $parameters = []): string
    {
        return (string)$this->uriBuilder->buildUriFromRequest($request, $this->getUrlParameters($parameters));
    }

    protected function getViewModeButton(ServerRequestInterface $request): ButtonInterface
    {
        $viewModeItems = [];
        $viewModeItems[] = GeneralUtility::makeInstance(DropDownRadio::class)
            ->setActive($this->viewMode === ViewMode::TILES)
            ->setHref($this->createUri($request, ['viewMode' => ViewMode::TILES->value]))
            ->setLabel($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.view.tiles'))
            ->setIcon($this->iconFactory->getIcon('actions-viewmode-tiles'));
        $viewModeItems[] = GeneralUtility::makeInstance(DropDownRadio::class)
            ->setActive($this->viewMode === ViewMode::LIST)
            ->setHref($this->createUri($request, ['viewMode' => ViewMode::LIST->value]))
            ->setLabel($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.view.list'))
            ->setIcon($this->iconFactory->getIcon('actions-viewmode-list'));
        if (!($this->getBackendUser()->getTSConfig()['options.']['noThumbsInEB'] ?? false)) {
            $viewModeItems[] = GeneralUtility::makeInstance(DropdownDivider::class);
            $viewModeItems[] = GeneralUtility::makeInstance(DropDownToggle::class)
                ->setActive($this->displayThumbs)
                ->setHref($this->createUri($request, ['displayThumbs' => $this->displayThumbs ? 0 : 1]))
                ->setLabel($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.view.showThumbnails'))
                ->setIcon($this->iconFactory->getIcon('actions-image'));
        }

        $viewModeButton = GeneralUtility::makeInstance(DropDownButton::class)
            ->setLabel($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.view'));
        foreach ($viewModeItems as $viewModeItem) {
            /** @var DropDownItemInterface $viewModeItem */
            $viewModeButton->addItem($viewModeItem);
        }

        return $viewModeButton;
    }

    public function getUrlParameters(array $values): array
    {
        $values = array_replace_recursive([
            'expandFolder' => $values['identifier'] ?? $this->expandFolder,
        ], $values);

        return array_merge($this->linkBrowser->getUrlParameters($values), $values);
    }

    protected function getLanguageService(): LanguageService
    {
        return $this->languageService;
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
