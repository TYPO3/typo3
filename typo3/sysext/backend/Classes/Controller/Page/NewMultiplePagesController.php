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
use TYPO3\CMS\Backend\Attribute\Controller;
use TYPO3\CMS\Backend\Routing\PreviewUriBuilder;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\DataHandling\PageDoktypeRegistry;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * "Create multiple pages" controller
 *
 * @internal This class is a specific Backend controller implementation and is not considered part of the Public TYPO3 API.
 */
#[Controller]
class NewMultiplePagesController
{
    public function __construct(
        protected readonly IconFactory $iconFactory,
        protected readonly ModuleTemplateFactory $moduleTemplateFactory,
    ) {
    }

    /**
     * Main function Handling input variables and rendering main view.
     */
    public function mainAction(ServerRequestInterface $request): ResponseInterface
    {
        $view = $this->moduleTemplateFactory->create($request);
        $backendUser = $this->getBackendUser();
        $pageUid = (int)$request->getQueryParams()['id'];

        // Show only if there is a valid page and if this page may be viewed by the user
        $pageRecord = BackendUtility::readPageAccess($pageUid, $backendUser->getPagePermsClause(Permission::PAGE_SHOW));
        if (!is_array($pageRecord)) {
            // User has no permission on parent page, should not happen, just render an empty page
            return $view->renderResponse('Dummy/Index');
        }

        // Doc header handling
        $view->getDocHeaderComponent()->setMetaInformation($pageRecord);
        $buttonBar = $view->getDocHeaderComponent()->getButtonBar();
        $previewDataAttributes = PreviewUriBuilder::create($pageUid)
            ->withRootLine(BackendUtility::BEgetRootLine($pageUid))
            ->buildDispatcherDataAttributes();
        $viewButton = $buttonBar->makeLinkButton()
            ->setHref('#')
            ->setDataAttributes($previewDataAttributes ?? [])
            ->setDisabled(!$previewDataAttributes)
            ->setTitle($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.showPage'))
            ->setIcon($this->iconFactory->getIcon('actions-view-page', Icon::SIZE_SMALL))
            ->setShowLabelText(true);
        $buttonBar->addButton($viewButton);

        $calculatedPermissions = new Permission($backendUser->calcPerms($pageRecord));
        $canCreateNew = $backendUser->isAdmin() || $calculatedPermissions->createPagePermissionIsGranted();

        $view->assignMultiple([
            'canCreateNew' => $canCreateNew,
            'maxTitleLength' => $backendUser->uc['titleLen'] ?? 20,
            'pageUid' => $pageUid,
        ]);

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

        return $view->renderResponse('Page/NewPages');
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

        $dataMap = [];
        $firstRecord = true;
        $previousIdentifier = '';
        foreach ($newPagesData as $identifier => $data) {
            if (!trim($data['title'])) {
                continue;
            }
            $dataMap['pages'][$identifier]['hidden'] = (int)$hidePages;
            $dataMap['pages'][$identifier]['nav_hide'] = (int)$hidePagesInMenu;
            $dataMap['pages'][$identifier]['title'] = $data['title'];
            $dataMap['pages'][$identifier]['doktype'] = $data['doktype'];
            if ($firstRecord) {
                $firstRecord = false;
                $dataMap['pages'][$identifier]['pid'] = $firstPid;
            } else {
                $dataMap['pages'][$identifier]['pid'] = '-' . $previousIdentifier;
            }
            $previousIdentifier = $identifier;
        }

        if (!empty($dataMap)) {
            $pagesCreated = true;
            $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
            $dataHandler->start($dataMap, []);
            $dataHandler->process_datamap();
            BackendUtility::setUpdateSignal('updatePageTree');
        }

        return $pagesCreated;
    }

    /**
     * Page selector type data
     */
    protected function getTypeSelectData(int $pageUid): array
    {
        $tsConfig = BackendUtility::getPagesTSconfig($pageUid);
        $pagesTsConfig = $tsConfig['TCEFORM.']['pages.'] ?? [];

        // Find all available doktypes for the current user
        $types = GeneralUtility::makeInstance(PageDoktypeRegistry::class)->getRegisteredDoktypes();
        $types[] = PageRepository::DOKTYPE_DEFAULT;
        $types[] = PageRepository::DOKTYPE_LINK;
        $types[] = PageRepository::DOKTYPE_SHORTCUT;
        $types[] = PageRepository::DOKTYPE_MOUNTPOINT;
        $types[] = PageRepository::DOKTYPE_SPACER;

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
            if ($doktypeData['value'] === '--div--') {
                $groupLabel = $doktypeData['label'];
            } else {
                if (in_array($doktypeData['value'], $allowedDoktypes)) {
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
                    $queryBuilder->createNamedParameter($pageUid, Connection::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    $GLOBALS['TCA']['pages']['ctrl']['languageField'],
                    $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)
                )
            )
            ->orderBy('sorting')
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
