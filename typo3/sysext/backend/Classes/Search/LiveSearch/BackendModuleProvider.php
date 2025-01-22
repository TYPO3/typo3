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

namespace TYPO3\CMS\Backend\Search\LiveSearch;

use TYPO3\CMS\Backend\Module\ModuleInterface;
use TYPO3\CMS\Backend\Module\ModuleProvider;
use TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Search\LiveSearch\SearchDemand\SearchDemand;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;

class BackendModuleProvider implements SearchProviderInterface
{
    private LanguageService $languageService;

    public function __construct(
        private readonly LanguageServiceFactory $languageServiceFactory,
        private readonly UriBuilder $uriBuilder,
        private readonly IconFactory $iconFactory,
        private readonly ModuleProvider $moduleProvider
    ) {
        $this->languageService = $this->languageServiceFactory->createFromUserPreferences($this->getBackendUser());
    }

    public function getFilterLabel(): string
    {
        return $this->languageService->sL('LLL:EXT:backend/Resources/Private/Language/locallang.xlf:liveSearch.backendModuleProvider.filterLabel');
    }

    public function find(SearchDemand $searchDemand): array
    {
        $items = [];

        foreach ($this->getFilteredModules($searchDemand) as $module) {
            // we can't generate accessible URLs for all modules by their identifier
            // if URL generation fails, we don't create an action to open a module
            // and if no actions exist, we skip result item creation altogether
            try {
                $moduleUrl = (string)$this->uriBuilder->buildUriFromRoute($module->getIdentifier());
            } catch (RouteNotFoundException) {
                continue;
            }

            $action = (new ResultItemAction('open_module'))
                ->setLabel($this->languageService->sL('LLL:EXT:backend/Resources/Private/Language/locallang.xlf:resultItem.backendModuleProvider.openModule'))
                ->setUrl($moduleUrl);

            $iconIdentifier = $module->getIconIdentifier();
            if ($iconIdentifier === '' && $module->hasParentModule()) {
                $iconIdentifier = $module->getParentModule()->getIconIdentifier();
            }

            $items[] = (new ResultItem(self::class))
                ->setItemTitle($this->languageService->sL($module->getTitle()))
                ->setTypeLabel($this->languageService->sL('LLL:EXT:backend/Resources/Private/Language/locallang.xlf:liveSearch.backendModuleProvider.typeLabel'))
                ->setIcon($this->iconFactory->getIcon($iconIdentifier, IconSize::SMALL))
                ->setActions($action)
                ->setExtraData([
                    'moduleIdentifier' => $module->getIdentifier(),
                ]);
        }

        return $items;
    }

    public function count(SearchDemand $searchDemand): int
    {
        return count($this->getFilteredModules($searchDemand));
    }

    /**
     * @return list<ModuleInterface>
     */
    private function getFilteredModules(SearchDemand $searchDemand): array
    {
        $normalizedQuery = mb_strtolower($searchDemand->getQuery());
        $filteredModules = array_filter(
            $this->moduleProvider->getModules($this->getBackendUser(), true, false),
            fn(ModuleInterface $module) => str_contains(mb_strtolower($this->languageService->sL($module->getTitle())), $normalizedQuery)
        );

        $firstResult = $searchDemand->getOffset();
        $remainingItems = $searchDemand->getLimit();

        return array_slice($filteredModules, $firstResult, $remainingItems, true);
    }

    private function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
