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

namespace TYPO3\CMS\Backend\Controller\Page;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Routing\PreviewUriBuilder;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\BackendTemplateView;

/**
 * "Sort sub pages" controller - reachable from context menu "more" on page records
 * @internal This class is a specific Backend controller implementation and is not considered part of the Public TYPO3 API.
 */
class SortSubPagesController
{
    public function __construct(
        protected IconFactory $iconFactory,
        protected ModuleTemplateFactory $moduleTemplateFactory,
    ) {
    }

    /**
     * Main function Handling input variables and rendering main view.
     */
    public function mainAction(ServerRequestInterface $request): ResponseInterface
    {
        $moduleTemplate = $this->moduleTemplateFactory->create($request);
        $backendUser = $this->getBackendUser();
        $parentPageUid = (int)$request->getQueryParams()['id'];

        // Show only if there is a valid page and if this page may be viewed by the user
        $pageInformation = BackendUtility::readPageAccess($parentPageUid, $backendUser->getPagePermsClause(Permission::PAGE_SHOW));
        if (!is_array($pageInformation)) {
            // User has no permission on parent page, should not happen, just render an empty page
            $moduleTemplate->setContent('');
            return new HtmlResponse($moduleTemplate->renderContent());
        }

        // Doc header handling
        $moduleTemplate->getDocHeaderComponent()->setMetaInformation($pageInformation);
        $buttonBar = $moduleTemplate->getDocHeaderComponent()->getButtonBar();
        $cshButton = $buttonBar->makeHelpButton()
            ->setModuleName('pages_sort')
            ->setFieldName('pages_sort');
        $previewDataAttributes = PreviewUriBuilder::create($parentPageUid)
            ->withRootLine(BackendUtility::BEgetRootLine($parentPageUid))
            ->buildDispatcherDataAttributes();
        $viewButton = $buttonBar->makeLinkButton()
            ->setDataAttributes($previewDataAttributes ?? [])
            ->setTitle($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.showPage'))
            ->setIcon($this->iconFactory->getIcon('actions-view-page', Icon::SIZE_SMALL))
            ->setHref('#');
        $buttonBar->addButton($cshButton)->addButton($viewButton);

        // Main view setup
        $view = GeneralUtility::makeInstance(BackendTemplateView::class);
        $view->setTemplateRootPaths(['EXT:backend/Resources/Private/Templates']);

        $isInWorkspace = $backendUser->workspace !== 0;
        $view->assign('isInWorkspace', $isInWorkspace);
        $view->assign('maxTitleLength', $backendUser->uc['titleLen'] ?? 20);
        $view->assign('parentPageUid', $parentPageUid);
        $view->assign('dateFormat', $GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy']);
        $view->assign('timeFormat', $GLOBALS['TYPO3_CONF_VARS']['SYS']['hhmm']);

        if (!$isInWorkspace) {
            // Apply new sorting if given
            $newSortBy = $request->getQueryParams()['newSortBy'] ?? null;
            if ($newSortBy && in_array($newSortBy, ['title', 'subtitle', 'nav_title', 'crdate', 'tstamp'], true)) {
                $this->sortSubPagesByField($parentPageUid, (string)$newSortBy);
            } elseif ($newSortBy && $newSortBy === 'reverseCurrentSorting') {
                $this->reverseSortingOfPages($parentPageUid);
            }

            // Get sub pages, loop through them and add page/user specific permission details
            $pageRecords = $this->getSubPagesOfPage($parentPageUid);
            $hasInvisiblePage = false;
            $subPages = [];
            foreach ($pageRecords as $page) {
                $pageWithPermissions = [];
                $pageWithPermissions['record'] = $page;
                $calculatedPermissions = new Permission($backendUser->calcPerms($page));
                $pageWithPermissions['canEdit'] = $backendUser->isAdmin() || $calculatedPermissions->editPagePermissionIsGranted();
                $canSeePage = $backendUser->isAdmin() || $calculatedPermissions->showPagePermissionIsGranted();
                if ($canSeePage) {
                    $subPages[] = $pageWithPermissions;
                } else {
                    $hasInvisiblePage = true;
                }
            }
            $view->assign('subPages', $subPages);
            $view->assign('hasInvisiblePage', $hasInvisiblePage);
        }

        $moduleTemplate->setContent($view->render('Page/SortSubPages'));
        return new HtmlResponse($moduleTemplate->renderContent());
    }

    /**
     * Sort sub pages of given uid by field name alphabetically
     *
     * @param int $parentPageUid Parent page uid
     * @param string $newSortBy Field name to sort by
     * @throws \RuntimeException If $newSortBy does not validate
     */
    protected function sortSubPagesByField(int $parentPageUid, string $newSortBy)
    {
        if (!in_array($newSortBy, ['title', 'subtitle', 'nav_title', 'crdate', 'tstamp'], true)) {
            throw new \RuntimeException(
                'New sort by must be one of "title", "subtitle", "nav_title", "crdate" or tstamp',
                1498924810
            );
        }
        $subPages = $this->getSubPagesOfPage($parentPageUid, $newSortBy);
        if (!empty($subPages)) {
            $subPages = array_reverse($subPages);
            $this->persistNewSubPageOrder($parentPageUid, $subPages);
        }
    }

    /**
     * Reverse current sorting of sub pages
     *
     * @param int $parentPageUid Parent page uid
     */
    protected function reverseSortingOfPages(int $parentPageUid)
    {
        $subPages = $this->getSubPagesOfPage($parentPageUid);
        if (!empty($subPages)) {
            $this->persistNewSubPageOrder($parentPageUid, $subPages);
        }
    }

    /**
     * Store new sub page order
     *
     * @param int $parentPageUid Parent page uid
     * @param array $subPages List of sub pages in new order
     */
    protected function persistNewSubPageOrder(int $parentPageUid, array $subPages)
    {
        $commandArray = [];
        foreach ($subPages as $subPage) {
            $commandArray['pages'][$subPage['uid']]['move'] = $parentPageUid;
        }
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([], $commandArray);
        $dataHandler->process_cmdmap();
        BackendUtility::setUpdateSignal('updatePageTree');
    }

    /**
     * Get a list of sub pages with some all fields from given page.
     * Fetch all data fields for full page icon display
     *
     * @param int $parentPageUid Get sub pages from this pages
     * @param string $orderBy Order pages by this field
     * @return array
     */
    protected function getSubPagesOfPage(int $parentPageUid, string $orderBy = 'sorting'): array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        return $queryBuilder->select('*')
            ->from('pages')
            ->where(
                $queryBuilder->expr()->eq('sys_language_uid', 0),
                $queryBuilder->expr()->eq(
                    'pid',
                    $queryBuilder->createNamedParameter($parentPageUid, \PDO::PARAM_INT)
                )
            )
            ->orderBy($orderBy)
            ->executeQuery()
            ->fetchAllAssociative();
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
