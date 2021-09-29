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

namespace TYPO3\CMS\Backend\Controller\ContentElement;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Backend\Tree\View\ContentMovingPagePositionMap;
use TYPO3\CMS\Backend\Tree\View\PageMovingPagePositionMap;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * Script Class for rendering the move-element wizard display
 * @internal This class is a specific Backend controller implementation and is not considered part of the Public TYPO3 API.
 */
class MoveElementController
{
    protected int $sys_language = 0;
    protected int $page_id = 0;
    protected string $table = '';
    protected string $R_URI = '';
    protected int $moveUid = 0;
    protected int $makeCopy = 0;
    protected string $perms_clause = '';

    protected ?ModuleTemplate $moduleTemplate = null;

    protected IconFactory $iconFactory;
    protected PageRenderer $pageRenderer;
    protected ModuleTemplateFactory $moduleTemplateFactory;

    public function __construct(
        IconFactory $iconFactory,
        PageRenderer $pageRenderer,
        ModuleTemplateFactory $moduleTemplateFactory
    ) {
        $this->iconFactory = $iconFactory;
        $this->pageRenderer = $pageRenderer;
        $this->moduleTemplateFactory = $moduleTemplateFactory;
    }

    public function mainAction(ServerRequestInterface $request): ResponseInterface
    {
        $this->moduleTemplate = $this->moduleTemplateFactory->create($request);
        $parsedBody = $request->getParsedBody();
        $queryParams = $request->getQueryParams();

        $this->sys_language = (int)($parsedBody['sys_language'] ?? $queryParams['sys_language'] ?? 0);
        $this->page_id = (int)($parsedBody['uid'] ?? $queryParams['uid'] ?? 0);
        $this->table = (string)($parsedBody['table'] ?? $queryParams['table'] ?? '');
        $this->R_URI = GeneralUtility::sanitizeLocalUrl($parsedBody['returnUrl'] ?? $queryParams['returnUrl'] ?? '');
        $this->moveUid = (int)(($parsedBody['moveUid'] ?? $queryParams['moveUid'] ?? false) ?: $this->page_id);
        $this->makeCopy = (int)($parsedBody['makeCopy'] ?? $queryParams['makeCopy'] ?? 0);
        // Select-pages where clause for read-access
        $this->perms_clause = $this->getBackendUser()->getPagePermsClause(Permission::PAGE_SHOW);

        // Setting up the buttons and markers for docheader
        $this->getButtons();
        // Build the <body> for the module
        $this->moduleTemplate->setTitle($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_misc.xlf:movingElement'));
        $this->moduleTemplate->setContent($this->renderContent());
        return new HtmlResponse($this->moduleTemplate->renderContent());
    }

    /**
     * Creating the module output.
     */
    protected function renderContent(): string
    {
        if (!$this->page_id) {
            return '';
        }
        $assigns = [];
        $backendUser = $this->getBackendUser();
        $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/Tooltip');
        // Get record for element:
        $elRow = BackendUtility::getRecordWSOL($this->table, $this->moveUid);
        // Headerline: Icon, record title:
        $assigns['table'] = $this->table;
        $assigns['elRow'] = $elRow;
        $assigns['recordTooltip'] = BackendUtility::getRecordToolTip($elRow, $this->table);
        $assigns['recordTitle'] = BackendUtility::getRecordTitle($this->table, $elRow, true);
        // Make-copy checkbox (clicking this will reload the page with the GET var makeCopy set differently):
        $assigns['makeCopyChecked'] = (bool)$this->makeCopy;
        $assigns['makeCopyUrl'] = GeneralUtility::linkThisScript(['makeCopy' => !$this->makeCopy]);
        // Get page record (if accessible):
        if ($this->table !== 'pages' && $this->moveUid === $this->page_id) {
            $this->page_id = (int)$elRow['pid'];
        }
        $pageInfo = BackendUtility::readPageAccess($this->page_id, $this->perms_clause);
        $assigns['pageInfo'] = $pageInfo;
        if (is_array($pageInfo) && $backendUser->isInWebMount($pageInfo['pid'], $this->perms_clause)) {
            // Initialize the page position map:
            $pagePositionMap = GeneralUtility::makeInstance(PageMovingPagePositionMap::class);
            $pagePositionMap->moveOrCopy = $this->makeCopy ? 'copy' : 'move';
            $pagePositionMap->moveUid = $this->moveUid;
            switch ($this->table) {
                case 'pages':
                    // Print a "go-up" link IF there is a real parent page (and if the user has read-access to that page).
                    if ($pageInfo['pid']) {
                        $pidPageInfo = BackendUtility::readPageAccess($pageInfo['pid'], $this->perms_clause);
                        if (is_array($pidPageInfo)) {
                            if ($backendUser->isInWebMount($pidPageInfo['pid'], $this->perms_clause)) {
                                $assigns['goUpUrl'] = GeneralUtility::linkThisScript([
                                    'uid' => (int)$pageInfo['pid'],
                                    'moveUid' => $this->moveUid,
                                ]);
                            } else {
                                $assigns['pidPageInfo'] = $pidPageInfo;
                            }
                            $assigns['pidRecordTitle'] = BackendUtility::getRecordTitle('pages', $pidPageInfo, true);
                        }
                    }
                    // Create the position tree:
                    $assigns['positionTree'] = $pagePositionMap->positionTree($this->page_id, $pageInfo, $this->perms_clause, $this->R_URI);
                    break;
                case 'tt_content':
                    // Initialize the content position map:
                    $contentPositionMap = GeneralUtility::makeInstance(ContentMovingPagePositionMap::class);
                    $contentPositionMap->copyMode = $this->makeCopy ? 'copy' : 'move';
                    $contentPositionMap->moveUid = $this->moveUid;
                    $contentPositionMap->cur_sys_language = $this->sys_language;
                    $contentPositionMap->R_URI = $this->R_URI;
                    // Headerline for the parent page: Icon, record title:
                    $assigns['ttContent']['recordTooltip'] = BackendUtility::getRecordToolTip($pageInfo);
                    $assigns['ttContent']['recordTitle'] = BackendUtility::getRecordTitle('pages', $pageInfo, true);
                    // Adding parent page-header and the content element columns from position-map:
                    $assigns['contentElementColumns'] = $contentPositionMap->printContentElementColumns($this->page_id);
                    // Print a "go-up" link IF there is a real parent page (and if the user has read-access to that page).
                    if ($pageInfo['pid'] > 0) {
                        $pidPageInfo = BackendUtility::readPageAccess($pageInfo['pid'], $this->perms_clause);
                        if (is_array($pidPageInfo)) {
                            if ($backendUser->isInWebMount($pidPageInfo['pid'], $this->perms_clause)) {
                                $assigns['goUpUrl'] = GeneralUtility::linkThisScript([
                                    'uid' => (int)$pageInfo['pid'],
                                    'moveUid' => $this->moveUid,
                                ]);
                            } else {
                                $assigns['pidPageInfo'] = $pidPageInfo;
                            }
                            $assigns['pidRecordTitle'] = BackendUtility::getRecordTitle('pages', $pidPageInfo, true);
                        }
                    }
                    // Create the position tree (for pages) without insert lines:
                    $pagePositionMap->dontPrintPageInsertIcons = 1;
                    $assigns['positionTree'] = $pagePositionMap->positionTree($this->page_id, $pageInfo, $this->perms_clause, $this->R_URI);
                }
        }

        // Rendering of the output via fluid
        $view = $this->initializeView();
        $view->assignMultiple($assigns);
        return $view->render();
    }

    protected function initializeView(): StandaloneView
    {
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setTemplateRootPaths(['EXT:backend/Resources/Private/Templates']);
        $view->setPartialRootPaths(['EXT:backend/Resources/Private/Partials']);
        $view->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName(
            'EXT:backend/Resources/Private/Templates/ContentElement/MoveElement.html'
        ));
        return $view;
    }

    /**
     * Create the panel of buttons for submitting the form or otherwise perform operations.
     */
    protected function getButtons()
    {
        $buttonBar = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar();
        if ($this->page_id) {
            if ($this->table === 'pages') {
                $cshButton = $buttonBar->makeHelpButton()
                    ->setModuleName('xMOD_csh_corebe')
                    ->setFieldName('move_el_pages');
                $buttonBar->addButton($cshButton);
            } elseif ($this->table === 'tt_content') {
                $cshButton = $buttonBar->makeHelpButton()
                    ->setModuleName('xMOD_csh_corebe')
                    ->setFieldName('move_el_cs');
                $buttonBar->addButton($cshButton);
            }

            if ($this->R_URI) {
                $backButton = $buttonBar->makeLinkButton()
                    ->setHref($this->R_URI)
                    ->setShowLabelText(true)
                    ->setTitle($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_misc.xlf:goBack'))
                    ->setIcon($this->iconFactory->getIcon('actions-view-go-back', Icon::SIZE_SMALL));
                $buttonBar->addButton($backButton);
            }
        }
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
