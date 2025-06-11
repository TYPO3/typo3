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

namespace TYPO3\CMS\Workspaces\Backend\LiveSearch;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Search\LiveSearch\ResultItem;
use TYPO3\CMS\Backend\Search\LiveSearch\ResultItemAction;
use TYPO3\CMS\Backend\Search\LiveSearch\SearchDemand\SearchDemand;
use TYPO3\CMS\Backend\Search\LiveSearch\SearchProviderInterface;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Workspaces\Service\WorkspaceService;

/**
 * Search provider to query workspaces from database
 *
 * @internal
 */
final readonly class WorkspaceProvider implements SearchProviderInterface
{
    private LanguageService $languageService;

    public function __construct(
        #[Autowire(service: 'cache.runtime')]
        private FrontendInterface $runtimeCache,
        private WorkspaceService $workspaceService,
        private UriBuilder $uriBuilder,
        private IconFactory $iconFactory,
        private LanguageServiceFactory $languageServiceFactory,
        private TcaSchemaFactory $tcaSchemaFactory,
    ) {
        $this->languageService = $this->languageServiceFactory->createFromUserPreferences($this->getBackendUser());
    }

    public function count(SearchDemand $searchDemand): int
    {
        return count($this->getFilteredWorkspaces($searchDemand));
    }

    public function find(SearchDemand $searchDemand): array
    {
        $icon = $this->iconFactory->getIcon('mimetypes-x-sys_workspace', IconSize::SMALL);
        $schema = $this->tcaSchemaFactory->get('sys_workspace');
        $typeLabel = $schema->getTitle($this->languageService->sL(...));
        $workspaces = $this->getFilteredWorkspaces($searchDemand);
        $items = [];

        foreach ($workspaces as $workspaceId => $workspaceLabel) {
            $actions = [];
            $actions[] = (new ResultItemAction('open_workspace'))
                ->setLabel($this->languageService->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang.xlf:action.switchToWorkspace'))
                ->setUrl((string)$this->uriBuilder->buildUriFromRoute('workspaces_admin', [
                    'workspace' => $workspaceId,
                    'id' => $searchDemand->getPageId(),
                ]))
            ;

            if ($workspaceId > 0 && $this->getBackendUser()->isAdmin()) {
                $editWorkspaceRecordUrl = (string)$this->uriBuilder->buildUriFromRoute('record_edit', [
                    'edit' => [
                        'sys_workspace' => [
                            $workspaceId => 'edit',
                        ],
                    ],
                    'returnUrl' => (string)$this->uriBuilder->buildUriFromRoute('workspaces_admin', ['id' => $searchDemand->getPageId()]),
                ]);

                $actions[] = (new ResultItemAction('configure_workspace'))
                    ->setLabel($this->languageService->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang.xlf:button.editWorkspaceSettings'))
                    ->setIcon($this->iconFactory->getIcon('actions-cog-alt', IconSize::SMALL))
                    ->setUrl($editWorkspaceRecordUrl);
            }

            $items[] = (new ResultItem(self::class))
                ->setItemTitle($workspaceLabel)
                ->setTypeLabel($typeLabel)
                ->setIcon($icon)
                ->setActions(...$actions);
        }

        return $items;
    }

    public function getFilterLabel(): string
    {
        $schema = $this->tcaSchemaFactory->get('sys_workspace');
        return $schema->getTitle($this->languageService->sL(...));
    }

    private function getAvailableWorkspaces(): array
    {
        $cacheId = 'available-workspaces-' . hash('xxh3', $this->getBackendUser()->name);
        $availableWorkspaces = $this->runtimeCache->get($cacheId);
        if ($availableWorkspaces === false) {
            $availableWorkspaces = $this->workspaceService->getAvailableWorkspaces();
            $this->runtimeCache->set($cacheId, $availableWorkspaces);
        }

        return $availableWorkspaces;
    }

    private function getFilteredWorkspaces(SearchDemand $searchDemand): array
    {
        // @todo: This isn't nice. The interface should rather have an `canAccess()` method to check whether the current
        //        backend user is permitted to use a provider at all.
        if (count($this->getAvailableWorkspaces()) <= 1) {
            return [];
        }

        $normalizedQuery = mb_strtolower($searchDemand->getQuery());
        $filteredWorkspaces = array_filter(
            $this->getAvailableWorkspaces(),
            static fn(string $workspaceName) => str_contains(mb_strtolower($workspaceName), $normalizedQuery)
        );

        $firstResult = $searchDemand->getOffset();
        $remainingItems = $searchDemand->getLimit();

        return array_slice($filteredWorkspaces, $firstResult, $remainingItems, true);
    }

    private function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
