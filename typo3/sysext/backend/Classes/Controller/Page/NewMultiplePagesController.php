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
use TYPO3\CMS\Backend\Template\ModuleTemplate;
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
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * "Create multiple pages" controller
 *
 * @internal This class is a specific Backend controller implementation and is not considered part of the Public TYPO3 API.
 */
class NewMultiplePagesController
{
    /**
     * ModuleTemplate object
     *
     * @var ModuleTemplate
     */
    protected $moduleTemplate;

    protected IconFactory $iconFactory;
    protected ModuleTemplateFactory $moduleTemplateFactory;

    public function __construct(IconFactory $iconFactory, ModuleTemplateFactory $moduleTemplateFactory)
    {
        $this->iconFactory = $iconFactory;
        $this->moduleTemplateFactory = $moduleTemplateFactory;
    }

    /**
     * Main function Handling input variables and rendering main view
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface Response
     */
    public function mainAction(ServerRequestInterface $request): ResponseInterface
    {
        $this->moduleTemplate = $this->moduleTemplateFactory->create($request);
        $backendUser = $this->getBackendUser();
        $pageUid = (int)$request->getQueryParams()['id'];

        // Show only if there is a valid page and if this page may be viewed by the user
        $pageRecord = BackendUtility::readPageAccess($pageUid, $backendUser->getPagePermsClause(Permission::PAGE_SHOW));
        if (!is_array($pageRecord)) {
            // User has no permission on parent page, should not happen, just render an empty page
            $this->moduleTemplate->setContent('');
            return new HtmlResponse($this->moduleTemplate->renderContent());
        }

        // Doc header handling
        $this->moduleTemplate->getDocHeaderComponent()->setMetaInformation($pageRecord);
        $buttonBar = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar();
        $cshButton = $buttonBar->makeHelpButton()
            ->setModuleName('pages_new')
            ->setFieldName('pages_new');
        $previewDataAttributes = PreviewUriBuilder::create($pageUid)
            ->withRootLine(BackendUtility::BEgetRootLine($pageUid))
            ->buildDispatcherDataAttributes();
        $viewButton = $buttonBar->makeLinkButton()
            ->setDataAttributes($previewDataAttributes ?? [])
            ->setTitle($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.showPage'))
            ->setIcon($this->iconFactory->getIcon('actions-view-page', Icon::SIZE_SMALL))
            ->setHref('#');
        $buttonBar->addButton($cshButton)->addButton($viewButton);

        // Main view setup
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName(
            'EXT:backend/Resources/Private/Templates/Page/NewPages.html'
        ));

        $calculatedPermissions = new Permission($backendUser->calcPerms($pageRecord));
        $canCreateNew = $backendUser->isAdmin() || $calculatedPermissions->createPagePermissionIsGranted();

        $view->assign('canCreateNew', $canCreateNew);
        $view->assign('maxTitleLength', $backendUser->uc['titleLen'] ?? 20);
        $view->assign('pageUid', $pageUid);

        if ($canCreateNew) {
            $newPagesData = (array)($request->getParsedBody()['pages'] ?? []);
            if (!empty($newPagesData)) {
                $hasNewPagesData = true;
                $afterExisting = isset($request->getParsedBody()['createInListEnd']);
                $hidePages = isset($request->getParsedBody()['hidePages']);
                $hidePagesInMenu = isset($request->getParsedBody()['hidePagesInMenus']);
                $pagesCreated = $this->createPages($newPagesData, $pageUid, $afterExisting, $hidePages, $hidePagesInMenu);
                $view->assign('pagesCreated', $pagesCreated);
                $subPages = $this->getSubPagesOfPage($pageUid);
                $visiblePages = [];
                foreach ($subPages as $page) {
                    $calculatedPermissions = new Permission($backendUser->calcPerms($page));
                    if ($backendUser->isAdmin() || $calculatedPermissions->showPagePermissionIsGranted()) {
                        $visiblePages[] = $page;
                    }
                }
                $view->assign('visiblePages', $visiblePages);
            } else {
                $hasNewPagesData = false;
                $view->assign('pageTypes', $this->getTypeSelectData($pageUid));
            }
            $view->assign('hasNewPagesData', $hasNewPagesData);
        }

        $this->moduleTemplate->setContent($view->render());
        return new HtmlResponse($this->moduleTemplate->renderContent());
    }

    /**
     * Persist new pages in DB
     *
     * @param array $newPagesData Data array with title and page type
     * @param int $pageUid Uid of page new pages should be added in
     * @param bool $afterExisting True if new pages should be created after existing pages
     * @param bool $hidePages True if new pages should be set to hidden
     * @param bool $hidePagesInMenu True if new pages should be set to hidden in menu
     * @return bool TRUE if at least on pages has been added
     */
    protected function createPages(array $newPagesData, int $pageUid, bool $afterExisting, bool $hidePages, bool $hidePagesInMenu): bool
    {
        $pagesCreated = false;

        // Set first pid to "-1 * uid of last existing sub page" if pages should be created at end
        $firstPid = $pageUid;
        if ($afterExisting) {
            $subPages = $this->getSubPagesOfPage($pageUid);
            $lastPage = end($subPages);
            if (isset($lastPage['uid']) && MathUtility::canBeInterpretedAsInteger($lastPage['uid'])) {
                $firstPid = -(int)$lastPage['uid'];
            }
        }

        $commandArray = [];
        $firstRecord = true;
        $previousIdentifier = '';
        foreach ($newPagesData as $identifier => $data) {
            if (!trim($data['title'])) {
                continue;
            }
            $commandArray['pages'][$identifier]['hidden'] = (int)$hidePages;
            $commandArray['pages'][$identifier]['nav_hide'] = (int)$hidePagesInMenu;
            $commandArray['pages'][$identifier]['title'] = $data['title'];
            $commandArray['pages'][$identifier]['doktype'] = $data['doktype'];
            if ($firstRecord) {
                $firstRecord = false;
                $commandArray['pages'][$identifier]['pid'] = $firstPid;
            } else {
                $commandArray['pages'][$identifier]['pid'] = '-' . $previousIdentifier;
            }
            $previousIdentifier = $identifier;
        }

        if (!empty($commandArray)) {
            $pagesCreated = true;
            $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
            $dataHandler->start($commandArray, []);
            $dataHandler->process_datamap();
            BackendUtility::setUpdateSignal('updatePageTree');
        }

        return $pagesCreated;
    }

    /**
     * Page selector type data
     *
     * @param int $pageUid Page Uid
     * @return array
     */
    protected function getTypeSelectData(int $pageUid)
    {
        $tsConfig = BackendUtility::getPagesTSconfig($pageUid);
        $pagesTsConfig = $tsConfig['TCEFORM.']['pages.'] ?? [];

        // Find all available doktypes for the current user
        $types = $GLOBALS['PAGES_TYPES'];
        unset($types['default']);
        $types = array_keys($types);
        $types[] = 1; // default
        $types[] = 3; // link
        $types[] = 4; // shortcut
        $types[] = 7; // mount point
        $types[] = 199; // spacer

        if (!$this->getBackendUser()->isAdmin() && isset($this->getBackendUser()->groupData['pagetypes_select'])) {
            $types = GeneralUtility::trimExplode(',', $this->getBackendUser()->groupData['pagetypes_select'], true);
        }
        $removeItems = isset($pagesTsConfig['doktype.']['removeItems']) ? GeneralUtility::trimExplode(',', $pagesTsConfig['doktype.']['removeItems'], true) : [];
        $allowedDoktypes = array_diff($types, $removeItems);

        // All doktypes in the TCA
        $availableDoktypes = $GLOBALS['TCA']['pages']['columns']['doktype']['config']['items'];

        // Sort by group and allowedDoktypes
        $groupedData = [];
        $groupLabel = '';
        foreach ($availableDoktypes as $doktypeData) {
            // If it is a group, save the group label for the children underneath
            if ($doktypeData[1] === '--div--') {
                $groupLabel = $doktypeData[0];
            } else {
                if (in_array($doktypeData[1], $allowedDoktypes)) {
                    $groupedData[$groupLabel][] = $doktypeData;
                }
            }
        }

        return $groupedData;
    }

    /**
     * Get a list of sub pages with some all fields from given page.
     * Fetch all data fields for full page icon display
     *
     * @param int $pageUid Get sub pages from this pages
     * @return array
     */
    protected function getSubPagesOfPage(int $pageUid): array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        return $queryBuilder->select('*')
            ->from('pages')
            ->where(
                $queryBuilder->expr()->eq(
                    'pid',
                    $queryBuilder->createNamedParameter($pageUid, \PDO::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    $GLOBALS['TCA']['pages']['ctrl']['languageField'],
                    $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)
                )
            )
            ->orderBy('sorting')
            ->executeQuery()
            ->fetchAllAssociative();
    }

    /**
     * Returns LanguageService
     *
     * @return LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }

    /**
     * Returns current BE user
     *
     * @return BackendUserAuthentication
     */
    protected function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }
}
