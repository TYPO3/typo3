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
use TYPO3\CMS\Backend\Attribute\Controller;
use TYPO3\CMS\Backend\Module\ModuleInterface;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\WorkspaceRestriction;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Page TSconfig > Page TSconfig Configuration
 *
 * @internal This class is a specific Backend controller implementation and is not part of the TYPO3's Core API.
 */
#[Controller]
final class PageTsConfigRecordsOverviewController
{
    public function __construct(
        private readonly IconFactory $iconFactory,
        private readonly UriBuilder $uriBuilder,
        private readonly ModuleTemplateFactory $moduleTemplateFactory,
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
            $view->getDocHeaderComponent()->setMetaInformation($pageRecord);
        }

        $accessContent = false;
        if (($pageId && $pageRecord !== []) || ($backendUser->isAdmin() && !$pageId)) {
            $accessContent = true;
            if (!$pageId && $backendUser->isAdmin()) {
                $pageRecord = ['title' => '[root-level]', 'uid' => 0, 'pid' => 0];
            }
            $view->assign('id', $pageId);
            // Setting up the buttons and the module menu for the doc header
            $this->getButtons($view, $currentModule, $pageId, $pageRecord);
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
            $this->setInPageArray($pageArray, BackendUtility::BEgetRootLine($row['uid'], 'AND 1=1'), $row);
        }
        return $this->getList($currentModule, $pageArray);
    }

    /**
     * Builds a multidimensional array that reflects the page hierarchy.
     */
    private function setInPageArray(array &$hierarchicArray, array $rootlineArray, array $row): void
    {
        ksort($rootlineArray);
        if (!($rootlineArray[0]['uid'] ?? false)) {
            array_shift($rootlineArray);
        }
        $currentElement = current($rootlineArray);
        $hierarchicArray[$currentElement['uid']] = htmlspecialchars($currentElement['title']);
        array_shift($rootlineArray);
        if (!empty($rootlineArray)) {
            if (!is_array($hierarchicArray[$currentElement['uid'] . '.'] ?? null)) {
                $hierarchicArray[$currentElement['uid'] . '.'] = [];
            }
            $this->setInPageArray($hierarchicArray[$currentElement['uid'] . '.'], $rootlineArray, $row);
        } else {
            $hierarchicArray[$currentElement['uid'] . '_'] = $this->extractLinesFromTSConfig($row);
        }
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

        foreach ($pageArray as $identifier => $title) {
            if (!MathUtility::canBeInterpretedAsInteger($identifier)) {
                continue;
            }
            $line = [];
            $line['padding'] = ($pageDepth * 20);
            $line['title'] = $identifier;
            if (isset($pageArray[$identifier . '_'])) {
                $line['link'] = $this->uriBuilder->buildUriFromRoute($currentModule->getIdentifier(), ['id' => $identifier]);
                $line['icon'] = $this->iconFactory->getIconForRecord('pages', BackendUtility::getRecordWSOL('pages', $identifier), Icon::SIZE_SMALL)->render();
                $line['pageTitle'] = GeneralUtility::fixed_lgd_cs($title, 30);
                $line['lines'] = ($pageArray[$identifier . '_']['writtenLines'] === 0 ? '' : $pageArray[$identifier . '_']['writtenLines']);
            } else {
                $line['link'] = '';
                $line['icon'] = $this->iconFactory->getIconForRecord('pages', BackendUtility::getRecordWSOL('pages', $identifier), Icon::SIZE_SMALL)->render();
                $line['pageTitle'] = GeneralUtility::fixed_lgd_cs($title, 30);
                $line['lines'] = '';
            }
            $lines[] = $line;
            $lines = $this->getList($currentModule, $pageArray[$identifier . '.'] ?? [], $lines, $pageDepth + 1);
        }
        return $lines;
    }

    private function getButtons(ModuleTemplate $view, ModuleInterface $currentModule, ?int $pageId, ?array $pageRecord): void
    {
        $buttonBar = $view->getDocHeaderComponent()->getButtonBar();
        $shortcutButton = $buttonBar->makeShortcutButton()
            ->setRouteIdentifier($currentModule->getIdentifier())
            ->setDisplayName($currentModule->getTitle())
            ->setArguments(['id' => $pageId]);
        $buttonBar->addButton($shortcutButton, ButtonBar::BUTTON_POSITION_RIGHT);
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
