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

namespace TYPO3\CMS\Backend\Controller\PageTsConfig;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Attribute\AsController;
use TYPO3\CMS\Backend\Module\ModuleInterface;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\WorkspaceRestriction;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Page TSconfig > Page TSconfig Configuration
 *
 * @internal This class is a specific Backend controller implementation and is not part of the TYPO3's Core API.
 */
#[AsController]
final readonly class PageTsConfigRecordsOverviewController
{
    public function __construct(
        private IconFactory $iconFactory,
        private UriBuilder $uriBuilder,
        private ModuleTemplateFactory $moduleTemplateFactory,
    ) {}

    public function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
        $backendUser = $this->getBackendUser();
        $currentModule = $request->getAttribute('module');
        $currentModuleIdentifier = $currentModule->getIdentifier();
        $pageId = (int)($request->getQueryParams()['id'] ?? 0);
        $pageRecord = BackendUtility::readPageAccess($pageId, $backendUser->getPagePermsClause(Permission::PAGE_SHOW)) ?: [];

        $moduleData = $request->getAttribute('moduleData');
        if ($moduleData->cleanUp([])) {
            $backendUser->pushModuleData($currentModuleIdentifier, $moduleData->toArray());
        }

        $view = $this->moduleTemplateFactory->create($request);

        $view->setTitle(
            $this->getLanguageService()->sL($currentModule->getTitle()),
            $pageId !== 0 && isset($pageRecord['title']) ? $pageRecord['title'] : ''
        );

        // The page will show only if there is a valid page and if this page may be viewed by the user.
        if ($pageRecord !== []) {
            $view->getDocHeaderComponent()->setPageBreadcrumb($pageRecord);
        }

        $accessContent = false;
        if (($pageId && $pageRecord !== []) || ($backendUser->isAdmin() && !$pageId)) {
            $accessContent = true;
            if (!$pageId && $backendUser->isAdmin()) {
                $pageRecord = ['title' => '[root-level]', 'uid' => 0, 'pid' => 0];
            }
            $view->assign('id', $pageId);
            // Setting up the buttons and the module menu for the doc header
            $view->getDocHeaderComponent()->setShortcutContext(
                $currentModule->getIdentifier(),
                $this->getLanguageService()->sL($currentModule->getTitle()),
                ['id' => $pageId]
            );
        }
        $view->assign('accessContent', $accessContent);
        $pagesUsingTSConfig = $this->getOverviewOfPagesUsingTSConfig($currentModule);
        if (count($pagesUsingTSConfig) > 0) {
            $view->assign('overviewOfPagesUsingTSConfig', $pagesUsingTSConfig);
        }

        $view->makeDocHeaderModuleMenu(['id' => $pageId]);
        return $view->renderResponse('PageTsConfig/RecordsOverview');
    }

    /**
     * Renders table rows of all pages containing TSConfig together with its rootline
     */
    private function getOverviewOfPagesUsingTSConfig(ModuleInterface $currentModule): array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
            ->add(GeneralUtility::makeInstance(WorkspaceRestriction::class, 0));

        $res = $queryBuilder
            ->select('uid', 'TSconfig')
            ->from('pages')
            ->where(
                $queryBuilder->expr()->neq(
                    'TSconfig',
                    $queryBuilder->createNamedParameter('')
                ),
                $queryBuilder->expr()->eq(
                    'sys_language_uid',
                    $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)
                )
            )
            ->executeQuery();

        $pageArray = [];

        while ($row = $res->fetchAssociative()) {
            $pageArray = $this->setInPageArray($pageArray, BackendUtility::BEgetRootLine($row['uid'], 'AND 1=1', false, ['sorting']), $row);
        }
        return $this->getList($currentModule, $pageArray);
    }

    /**
     * Builds a multidimensional array that reflects the page hierarchy, sorted like the page tree.
     */
    private function setInPageArray(array $pages, array $rootlineArray, array $row): array
    {
        ksort($rootlineArray);
        if (!($rootlineArray[0]['uid'] ?? false)) {
            array_shift($rootlineArray);
        }
        $currentElement = current($rootlineArray);
        $pages[$currentElement['uid']] ??= [
            'title' => htmlspecialchars($currentElement['title']),
            'sorting' => (int)$currentElement['sorting'],
        ];
        array_shift($rootlineArray);
        if (!empty($rootlineArray)) {
            $pages[$currentElement['uid']]['_nodes'] = $this->setInPageArray($pages[$currentElement['uid']]['_nodes'] ?? [], $rootlineArray, $row);
        } else {
            $pages[$currentElement['uid']]['_tsconfig'] = $this->extractLinesFromTSConfig($row);
        }
        uasort($pages, static fn(array $a, array $b): int => $a['sorting'] <=> $b['sorting']);
        return $pages;
    }

    /**
     * Extract the lines of TSConfig from a given pages row.
     */
    private function extractLinesFromTSConfig(array $row): array
    {
        $out = [];
        $out['uid'] = $row['uid'];
        $lines = GeneralUtility::trimExplode("\r\n", $row['TSconfig']);
        $out['writtenLines'] = count($lines);
        return $out;
    }

    /**
     * Recursive method to get the list of pages to show.
     */
    private function getList(ModuleInterface $currentModule, array $pageArray, array $lines = [], int $pageDepth = 0): array
    {
        if ($pageArray === []) {
            return $lines;
        }

        foreach ($pageArray as $identifier => $page) {
            $line = [];
            $line['padding'] = ($pageDepth * 20);
            $line['title'] = $identifier;
            if (isset($page['_tsconfig'])) {
                $line['link'] = $this->uriBuilder->buildUriFromRoute($currentModule->getIdentifier(), ['id' => $identifier]);
                $line['icon'] = $this->iconFactory->getIconForRecord('pages', BackendUtility::getRecordWSOL('pages', $identifier), IconSize::SMALL)->render();
                $line['pageTitle'] = GeneralUtility::fixed_lgd_cs($page['title'], 30);
                $line['lines'] = ($page['_tsconfig']['writtenLines'] === 0 ? '' : $page['_tsconfig']['writtenLines']);
            } else {
                $line['link'] = '';
                $line['icon'] = $this->iconFactory->getIconForRecord('pages', BackendUtility::getRecordWSOL('pages', $identifier), IconSize::SMALL)->render();
                $line['pageTitle'] = GeneralUtility::fixed_lgd_cs($page['title'], 30);
                $line['lines'] = '';
            }
            $lines[] = $line;
            $lines = $this->getList($currentModule, $page['_nodes'] ?? [], $lines, $pageDepth + 1);
        }
        return $lines;
    }

    private function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }

    private function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
