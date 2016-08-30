<?php
namespace TYPO3\CMS\Backend\Controller;

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

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Template\DocumentTemplate;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\View\PageTreeView;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Workspaces\Service\WorkspaceService;

/**
 * Main script class for the page tree navigation frame
 * This is the class for rendering the "page tree" navigation frame without ExtJS, used prior to TYPO3 CMS 4.5.
 * This functionality is deprecated since TYPO3 CMS 7, and will be removed with TYPO3 CMS 8
 */
class PageTreeNavigationController
{
    /**
     * @var string
     */
    public $content;

    /**
     * @var \TYPO3\CMS\Backend\View\PageTreeView
     */
    public $pagetree;

    /**
     * document template object
     *
     * @var \TYPO3\CMS\Backend\Template\DocumentTemplate
     */
    public $doc;

    /**
     * Temporary mount point (record), if any
     *
     * @var int
     */
    public $active_tempMountPoint = 0;

    /**
     * @var string
     */
    public $currentSubScript;

    /**
     * @var bool
     */
    public $cMR;

    /**
     * If not '' (blank) then it will clear (0) or set (>0) Temporary DB mount.
     *
     * @var string
     */
    public $setTempDBmount;

    /**
     * @var string
     * @deprecated since TYPO3 CMS 7, will be removed in TYPO3 CMS 8
     */
    public $template;

    /**
     * @var IconFactory
     */
    protected $iconFactory;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        GeneralUtility::deprecationLog('PageTreeNavigationController is deprecated in favor of new pagetrees');
        $GLOBALS['SOBE'] = $this;
        $this->init();
    }

    /**
     * Initialization of the class
     *
     * @return void
     */
    protected function init()
    {
        // Setting GPvars:
        $this->cMR = (bool)GeneralUtility::_GP('cMR');
        $this->currentSubScript = GeneralUtility::_GP('currentSubScript');
        $this->setTempDBmount = GeneralUtility::_GP('setTempDBmount');
        // Create page tree object:
        $beUser = $this->getBackendUser();
        $this->pagetree = GeneralUtility::makeInstance(PageTreeView::class);
        $this->pagetree->ext_IconMode = $beUser->getTSConfigVal('options.pageTree.disableIconLinkToContextmenu');
        $this->pagetree->ext_showPageId = (bool)$beUser->getTSConfigVal('options.pageTree.showPageIdWithTitle');
        $this->pagetree->ext_showNavTitle = (bool)$beUser->getTSConfigVal('options.pageTree.showNavTitle');
        $this->pagetree->ext_separateNotinmenuPages = $beUser->getTSConfigVal('options.pageTree.separateNotinmenuPages');
        $this->pagetree->ext_alphasortNotinmenuPages = $beUser->getTSConfigVal('options.pageTree.alphasortNotinmenuPages');
        $this->pagetree->thisScript = 'alt_db_navframe.php';
        $this->pagetree->addField('alias');
        $this->pagetree->addField('shortcut');
        $this->pagetree->addField('shortcut_mode');
        $this->pagetree->addField('mount_pid');
        $this->pagetree->addField('mount_pid_ol');
        $this->pagetree->addField('url');
        // Temporary DB mounts:
        $this->initializeTemporaryDBmount();
    }

    /**
     * Initialization for the visual parts of the class
     * Use template rendering only if this is a non-AJAX call
     *
     * @return void
     */
    public function initPage()
    {
        // Setting highlight mode:
        $doHighlight = !$this->getBackendUser()->getTSConfigVal('options.pageTree.disableTitleHighlight');
        // Create template object:
        $this->doc = GeneralUtility::makeInstance(DocumentTemplate::class);
        $this->doc->setModuleTemplate('EXT:backend/Resources/Private/Templates/alt_db_navframe.html');
        $this->doc->showFlashMessages = false;
        // Get HTML-Template

        // Adding javascript for drag & drop activation and highlighting
        $dragDropCode = 'Tree.registerDragDropHandlers();';

        // If highlighting is active, define the CSS class for the active item depending on the workspace
        if ($doHighlight) {
            $hlClass = $this->getBackendUser()->workspace === 0 ? 'active' : 'active active-ws wsver' . $this->getBackendUser()->workspace;
            $dragDropCode .= '
				Tree.highlightClass = "' . $hlClass . '";
				Tree.highlightActiveItem("",top.fsMod.navFrameHighlightedID["web"]);';
        }
        // Adding javascript code for drag&drop and the pagetree as well as the click menu code
        $this->doc->getDragDropCode('pages', $dragDropCode);
        $this->doc->getContextMenuCode();
        /** @var $pageRenderer PageRenderer */
        $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
        $pageRenderer->loadExtJS();
        $this->doc->JScode .= $this->doc->wrapScriptTags(($this->currentSubScript ? 'top.currentSubScript=unescape("' . rawurlencode($this->currentSubScript) . '");' : '') . '
		// Function, loading the list frame from navigation tree:
		function jumpTo(id, linkObj, highlightID, bank) { //
			var theUrl = top.currentSubScript ;
			if (theUrl.indexOf("?") != -1) {
				theUrl += "&id=" + id
			} else {
				theUrl += "?id=" + id
			}
			top.fsMod.currentBank = bank;
			top.TYPO3.Backend.ContentContainer.setUrl(theUrl);

			' . ($doHighlight ? 'Tree.highlightActiveItem("web", highlightID + "_" + bank);' : '') . '
			if (linkObj) { linkObj.blur(); }
			return false;
		}
		' . ($this->cMR ? 'jumpTo(top.fsMod.recentIds[\'web\'],\'\');' : '') . '

		');
        $this->doc->bodyTagId = 'typo3-pagetree';
    }

    /**
     * Main function, rendering the browsable page tree
     *
     * @return void
     */
    public function main()
    {
        // Produce browse-tree:
        $tree = $this->pagetree->getBrowsableTree();
        // Outputting Temporary DB mount notice:
        if ($this->active_tempMountPoint) {
            $flashText = '
				<a href="' . htmlspecialchars(GeneralUtility::linkThisScript(['setTempDBmount' => 0])) . '">' . $this->getLanguageService()->sl('LLL:EXT:lang/locallang_core.xlf:labels.temporaryDBmount', true) . '</a>		<br />' . $this->getLanguageService()->sl('LLL:EXT:lang/locallang_core.xlf:labels.path', true) . ': <span title="' . htmlspecialchars($this->active_tempMountPoint['_thePathFull']) . '">' . htmlspecialchars(GeneralUtility::fixed_lgd_cs($this->active_tempMountPoint['_thePath'], -50)) . '</span>
			';
            $flashMessage = GeneralUtility::makeInstance(FlashMessage::class, $flashText, '', FlashMessage::INFO);
            $this->content .= $flashMessage->render();
        }
        // Outputting page tree:
        $this->content .= '<div id="PageTreeDiv">' . $tree . '</div>';
        // Setting up the buttons and markers for docheader
        $docHeaderButtons = $this->getButtons();
        $markers = [
            'WORKSPACEINFO' => $this->getWorkspaceInfo(),
            'CONTENT' => $this->content
        ];
        // Build the <body> for the module
        $this->content = $this->doc->startPage('TYPO3 Page Tree');
        $this->content .= $this->doc->moduleBody([], $docHeaderButtons, $markers);
        $this->content .= $this->doc->endPage();
        $this->content = $this->doc->insertStylesAndJS($this->content);
    }

    /**
     * Outputting the accumulated content to screen
     *
     * @return void
     */
    public function printContent()
    {
        echo $this->content;
    }

    /**
     * Create the panel of buttons for submitting the form or otherwise perform operations.
     *
     * @return array All available buttons as an assoc. array
     */
    protected function getButtons()
    {
        $buttons = [
            'csh' => '',
            'new_page' => '',
            'refresh' => ''
        ];
        // New Page
        $onclickNewPageWizard = 'top.content.list_frame.location.href=' . GeneralUtility::quoteJSvalue(BackendUtility::getModuleUrl('db_new', ['pagesOnly' => 1, 'id' => ''])) . '+Tree.pageID;';
        $buttons['new_page'] = '<a href="#" onclick="' . $onclickNewPageWizard . '" title="' . $this->getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang_layout.xlf:newPage', true) . '">'
            . $this->iconFactory->getIcon('actions-page-new', Icon::SIZE_SMALL)->render()
            . '</a>';
        // Refresh
        $buttons['refresh'] = '<a href="' . htmlspecialchars(GeneralUtility::getIndpEnv('REQUEST_URI')) . '" title="' . $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:labels.refresh', true) . '">' . $this->iconFactory->getIcon('actions-refresh', Icon::SIZE_SMALL)->render() . '</a>';
        // CSH
        $buttons['csh'] = str_replace('typo3-csh-inline', 'typo3-csh-inline show-right', BackendUtility::cshItem('xMOD_csh_corebe', 'pagetree'));
        return $buttons;
    }

    /**
     * Create the workspace information
     *
     * @return string HTML containing workspace info
     */
    protected function getWorkspaceInfo()
    {
        if (ExtensionManagementUtility::isLoaded('workspaces') && ($this->getBackendUser()->workspace !== 0 || $this->getBackendUser()->getTSConfigVal('options.pageTree.onlineWorkspaceInfo'))) {
            $wsTitle = htmlspecialchars(WorkspaceService::getWorkspaceTitle($this->getBackendUser()->workspace));

            $workspaceInfo = '<div class="bgColor4 workspace-info"><span title="' . $wsTitle . '" onclick="top.goToModule(\'web_WorkspacesWorkspaces\');" style="cursor:pointer;">'
                    . $this->iconFactory->getIcon('apps-toolbar-menu-workspace', Icon::SIZE_SMALL)->render() . '</span>'
                    . $wsTitle . '</div>';
        } else {
            $workspaceInfo = '';
        }
        return $workspaceInfo;
    }

    /**********************************
     *
     * Temporary DB mounts
     *
     **********************************/
    /**
     * Getting temporary DB mount
     *
     * @return void
     */
    public function initializeTemporaryDBmount()
    {
        $beUser = $this->getBackendUser();
        // Set/Cancel Temporary DB Mount:
        if ((string)$this->setTempDBmount !== '') {
            $set = MathUtility::forceIntegerInRange($this->setTempDBmount, 0);
            if ($set > 0 && $beUser->isInWebMount($set)) {
                // Setting...:
                $this->settingTemporaryMountPoint($set);
            } else {
                // Clear:
                $this->settingTemporaryMountPoint(0);
            }
        }
        // Getting temporary mount point ID:
        $temporaryMountPoint = (int)$beUser->getSessionData('pageTree_temporaryMountPoint');
        // If mount point ID existed and is within users real mount points, then set it temporarily:
        if ($temporaryMountPoint > 0 && $beUser->isInWebMount($temporaryMountPoint)) {
            if ($this->active_tempMountPoint = BackendUtility::readPageAccess($temporaryMountPoint, $beUser->getPagePermsClause(1))) {
                $this->pagetree->MOUNTS = [$temporaryMountPoint];
            } else {
                // Clear temporary mount point as we have no access to it any longer
                $this->settingTemporaryMountPoint(0);
            }
        }
    }

    /**
     * Setting temporary page id as DB mount
     *
     * @param int $pageId The page id to set as DB mount
     * @return void
     */
    public function settingTemporaryMountPoint($pageId)
    {
        $this->getBackendUser()->setAndSaveSessionData('pageTree_temporaryMountPoint', (int)$pageId);
    }

    /**********************************
     *
     * AJAX Calls
     *
     **********************************/
    /**
     * Makes the AJAX call to expand or collapse the pagetree.
     * Called by an AJAX Route, see AjaxRequestHandler
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function ajaxExpandCollapse(ServerRequestInterface $request, ResponseInterface $response)
    {
        $this->init();
        $tree = $this->pagetree->getBrowsableTree();
        if (!$this->pagetree->ajaxStatus) {
            $response = $response->withStatus(500);
        } else {
            $response->getBody()->write(json_encode($tree));
        }

        return $response;
    }

    /**
     * Returns LanguageService
     *
     * @return \TYPO3\CMS\Lang\LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }

    /**
     * Returns the current BE user.
     *
     * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
     */
    protected function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }
}
