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

namespace TYPO3\CMS\Filelist\ElementBrowser;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\ElementBrowser\AbstractElementBrowser;
use TYPO3\CMS\Backend\ElementBrowser\ElementBrowserInterface;
use TYPO3\CMS\Backend\Template\Components\Buttons\ButtonInterface;
use TYPO3\CMS\Backend\Template\Components\Buttons\DropDown\DropDownDivider;
use TYPO3\CMS\Backend\Template\Components\Buttons\DropDown\DropDownItemInterface;
use TYPO3\CMS\Backend\Template\Components\Buttons\DropDown\DropDownRadio;
use TYPO3\CMS\Backend\Template\Components\Buttons\DropDown\DropDownToggle;
use TYPO3\CMS\Backend\Template\Components\Buttons\DropDownButton;
use TYPO3\CMS\Backend\Tree\View\LinkParameterProviderInterface;
use TYPO3\CMS\Core\Resource\Exception\FolderDoesNotExistException;
use TYPO3\CMS\Core\Resource\Exception\InsufficientFolderAccessPermissionsException;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Filelist\FileList;
use TYPO3\CMS\Filelist\Matcher\Matcher;
use TYPO3\CMS\Filelist\Type\Mode;
use TYPO3\CMS\Filelist\Type\SortDirection;
use TYPO3\CMS\Filelist\Type\ViewMode;

/**
 * @internal
 */
abstract class AbstractResourceBrowser extends AbstractElementBrowser implements ElementBrowserInterface, LinkParameterProviderInterface
{
    protected ?string $expandFolder = null;
    protected int $currentPage = 1;
    protected string $moduleStorageIdentifier = 'media_management';

    protected ?FileList $filelist = null;
    protected string $sortField = 'name';
    protected ?SortDirection $sortDirection = null;
    protected ?ViewMode $viewMode = null;
    protected bool $displayThumbs = true;

    protected ?Folder $selectedFolder = null;
    protected ?Matcher $resourceDisplayMatcher = null;
    protected ?Matcher $resourceSelectableMatcher = null;

    /**
     * Loads additional JavaScript
     */
    protected function initialize(ServerRequestInterface $request): void
    {
        parent::initialize($request);
        $this->view = $this->backendViewFactory->create($this->getRequest(), ['typo3/cms-filelist']);
        $this->view->assign('initialNavigationWidth', $this->getBackendUser()->uc['selector']['navigation']['width'] ?? 250);

        $this->pageRenderer->loadJavaScriptModule('@typo3/backend/tree/file-storage-browser.js');
        $this->pageRenderer->loadJavaScriptModule('@typo3/filelist/file-list-actions.js');
        $this->pageRenderer->loadJavaScriptModule('@typo3/backend/multi-record-selection.js');
        $this->pageRenderer->loadJavaScriptModule('@typo3/backend/global-event-handler.js');
    }

    protected function initVariables(ServerRequestInterface $request): void
    {
        parent::initVariables($request);

        $this->currentPage = (int)($request->getParsedBody()['currentPage'] ?? $request->getQueryParams()['currentPage'] ?? 1);
        $this->expandFolder = $request->getParsedBody()['expandFolder'] ?? $request->getQueryParams()['expandFolder'] ?? null;
        $this->sortField = ($request->getParsedBody()['sortField'] ?? $request->getQueryParams()['sortField'] ?? 'name');
        $this->sortDirection = SortDirection::tryFrom($request->getParsedBody()['sortDirection'] ?? $request->getQueryParams()['sortDirection'] ?? '') ?? SortDirection::ASCENDING;

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

        $this->filelist = GeneralUtility::makeInstance(FileList::class, $this->getRequest());
        $this->filelist->viewMode = $this->viewMode;
        $this->filelist->thumbs = ($GLOBALS['TYPO3_CONF_VARS']['GFX']['thumbnails'] ?? false) && $this->displayThumbs;
    }

    /**
     * Last selected folder is stored in user module session. Sanitize it
     * to set $this->selectedFolder or keep it null.
     */
    protected function initSelectedFolder(): void
    {
        $resourceFactory = GeneralUtility::makeInstance(ResourceFactory::class);
        if ($this->expandFolder) {
            try {
                $this->selectedFolder = $resourceFactory->getFolderObjectFromCombinedIdentifier($this->expandFolder);
            } catch (FolderDoesNotExistException|InsufficientFolderAccessPermissionsException) {
                // Outdated module session data: Last used folder has been removed meanwhile, or
                // access to last used folder has been removed. Do not set a preselected folder.
            }
        }
    }

    protected function getSortingModeButtons(Mode $mode): ButtonInterface
    {
        $sortingButton = GeneralUtility::makeInstance(DropDownButton::class)
            ->setLabel($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.sorting'))
            ->setIcon($this->iconFactory->getIcon($this->sortDirection->getIconIdentifier()));

        $sortingModeButtons = [];
        $sortableFields = $this->filelist->getSortableFields();
        if (count($sortableFields) > 1) {
            foreach ($sortableFields as $field) {
                $label = $this->filelist->getFieldLabel($field);

                $sortingModeButtons[] = GeneralUtility::makeInstance(DropDownRadio::class)
                    ->setActive($this->sortField === $field)
                    ->setHref($this->createUri([
                        'sortField' => $field,
                        'sortDirection' => SortDirection::ASCENDING->value,
                        'currentPage' => 1,
                    ]))
                    ->setLabel($label);
            }

            $sortingModeButtons[] = GeneralUtility::makeInstance(DropDownDivider::class);
        }

        $defaultSortingDirectionParams = ['sortField' => $this->sortField, 'currentPage' => 1];
        $sortingModeButtons[] = GeneralUtility::makeInstance(DropDownRadio::class)
            ->setActive($this->sortDirection === SortDirection::ASCENDING)
            ->setHref($this->createUri(array_merge($defaultSortingDirectionParams, ['sortDirection' => SortDirection::ASCENDING->value])))
            ->setLabel($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.sorting.asc'));
        $sortingModeButtons[] = GeneralUtility::makeInstance(DropDownRadio::class)
            ->setActive($this->sortDirection === SortDirection::DESCENDING)
            ->setHref($this->createUri(array_merge($defaultSortingDirectionParams, ['sortDirection' => SortDirection::DESCENDING->value])))
            ->setLabel($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.sorting.desc'));

        foreach ($sortingModeButtons as $sortingModeButton) {
            $sortingButton->addItem($sortingModeButton);
        }

        return $sortingButton;
    }

    protected function getViewModeButton(): ButtonInterface
    {
        $viewModeItems = [];
        $viewModeItems[] = GeneralUtility::makeInstance(DropDownRadio::class)
            ->setActive($this->viewMode === ViewMode::TILES)
            ->setHref($this->createUri(['viewMode' => ViewMode::TILES->value]))
            ->setLabel($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.view.tiles'))
            ->setIcon($this->iconFactory->getIcon('actions-viewmode-tiles'));
        $viewModeItems[] = GeneralUtility::makeInstance(DropDownRadio::class)
            ->setActive($this->viewMode === ViewMode::LIST)
            ->setHref($this->createUri(['viewMode' => ViewMode::LIST->value]))
            ->setLabel($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.view.list'))
            ->setIcon($this->iconFactory->getIcon('actions-viewmode-list'));
        if (!($this->getBackendUser()->getTSConfig()['options.']['noThumbsInEB'] ?? false)) {
            $viewModeItems[] = GeneralUtility::makeInstance(DropDownDivider::class);
            $viewModeItems[] = GeneralUtility::makeInstance(DropDownToggle::class)
                ->setActive($this->displayThumbs)
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

        return $viewModeButton;
    }

    /**
     * @param array $values Array of values to include into the parameters
     * @return string[] Array of parameters which have to be added to URLs
     */
    public function getUrlParameters(array $values): array
    {
        $values = array_replace_recursive([
            'mode' => $this->identifier,
            'expandFolder' => $values['identifier'] ?? $this->expandFolder,
            'bparams' => $this->bparams,
        ], $values);

        return array_filter($values, static function ($value) {
            return $value !== null && trim((string)$value) !== '';
        });
    }

    protected function createUri(array $parameters = []): string
    {
        $parameters = $this->getUrlParameters($parameters);
        return (string)$this->uriBuilder->buildUriFromRequest($this->getRequest(), $parameters);
    }

    /**
     * Session data for this class can be set from outside with this method.
     *
     * @param mixed[] $data Session data array
     * @return array<int, array|bool> Session data and boolean which indicates that data needs to be stored in session because it's changed
     */
    public function processSessionData($data): array
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
}
