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
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Tree\View\ElementBrowserFolderTreeView;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Filelist\FileListFolderTree;
use TYPO3\CMS\Recordlist\Tree\View\DummyLinkParameterProvider;

/**
 * Main script class for rendering of the folder tree
 */
class FileSystemNavigationFrameController
{
    /**
     * Content accumulates in this variable.
     *
     * @var string
     */
    public $content;

    /**
     * @var \TYPO3\CMS\Backend\Tree\View\FolderTreeView
     */
    public $foldertree;

    /**
     * @var string
     */
    public $currentSubScript;

    /**
     * @var bool
     */
    public $cMR;

    /**
     * @var array
     */
    protected $scopeData;

    /**
     * ModuleTemplate Container
     *
     * @var ModuleTemplate
     */
    protected $moduleTemplate;

    /**
     * Constructor
     */
    public function __construct()
    {
        $GLOBALS['SOBE'] = $this;
        $this->init();
    }

    /**
     * @param ServerRequestInterface $request the current request
     * @param ResponseInterface $response
     * @return ResponseInterface the response with the content
     */
    public function mainAction(ServerRequestInterface $request, ResponseInterface $response)
    {
        $this->initPage();
        $this->main();

        $response->getBody()->write($this->content);
        return $response;
    }

    /**
     * Initialiation of the script class
     */
    protected function init()
    {
        $this->moduleTemplate = GeneralUtility::makeInstance(ModuleTemplate::class);

        // Setting GPvars:
        $this->currentSubScript = GeneralUtility::_GP('currentSubScript');
        $this->cMR = GeneralUtility::_GP('cMR');

        $scopeData = (string)GeneralUtility::_GP('scopeData');
        $scopeHash = (string)GeneralUtility::_GP('scopeHash');

        if (!empty($scopeData) && hash_equals(GeneralUtility::hmac($scopeData), $scopeHash)) {
            $this->scopeData = unserialize($scopeData);
        }

        // Create folder tree object:
        if (!empty($this->scopeData)) {
            $this->foldertree = GeneralUtility::makeInstance($this->scopeData['class']);
            $this->foldertree->thisScript = $this->scopeData['script'];
            $this->foldertree->ext_noTempRecyclerDirs = $this->scopeData['ext_noTempRecyclerDirs'];
            if ($this->foldertree instanceof ElementBrowserFolderTreeView) {
                // create a fake provider to pass link data along properly
                $linkParamProvider = GeneralUtility::makeInstance(
                    DummyLinkParameterProvider::class,
                    $this->scopeData['browser'],
                    $this->scopeData['script']
                );
                $this->foldertree->setLinkParameterProvider($linkParamProvider);
            }
        } else {
            $this->foldertree = GeneralUtility::makeInstance(FileListFolderTree::class);
            $this->foldertree->thisScript = BackendUtility::getModuleUrl('file_navframe');
        }
        // Only set ext_IconMode if we are not running an ajax request from the ElementBrowser,
        // which has this property hardcoded to "titlelink".
        if (!$this->foldertree instanceof ElementBrowserFolderTreeView) {
            $this->foldertree->ext_IconMode = $this->getBackendUser()->getTSConfigVal('options.folderTree.disableIconLinkToContextmenu');
        }
    }

    /**
     * initialization for the visual parts of the class
     * Use template rendering only if this is a non-AJAX call
     */
    public function initPage()
    {
        $this->moduleTemplate->setBodyTag('<body id="ext-backend-Modules-FileSystemNavigationFrame-index-php">');

        // Adding javascript code for drag&drop and the file tree as well as the click menu code
        $hlClass = $this->getBackendUser()->workspace === 0 ? 'active' : 'active active-ws wsver' . $GLOBALS['BE_USER']->workspace;
        $dragDropCode = '
		Tree.highlightClass = "' . $hlClass . '";
		Tree.highlightActiveItem("", top.fsMod.navFrameHighlightedID["file"]);
		';

        // Adding javascript for drag & drop activation and highlighting
        $pageRenderer = $this->moduleTemplate->getPageRenderer();
        $pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/ContextMenu');
        $pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/LegacyTree', 'function() {
            DragDrop.table = "folders";
			Tree.registerDragDropHandlers();
            ' . $dragDropCode . '
        }');

        // Setting JavaScript for menu.
        $inlineJs = ($this->currentSubScript ? 'top.currentSubScript=unescape("' . rawurlencode($this->currentSubScript) . '");' : '') . '
		// Function, loading the list frame from navigation tree:
		function jumpTo(id, linkObj, highlightID, bank) {
			var theUrl = top.currentSubScript;
			if (theUrl.indexOf("?") != -1) {
				theUrl += "&id=" + id
			} else {
				theUrl += "?id=" + id
			}
			top.fsMod.currentBank = bank;
			top.TYPO3.Backend.ContentContainer.setUrl(theUrl);

			Tree.highlightActiveItem("file", highlightID + "_" + bank);
			if (linkObj) { linkObj.blur(); }
			return false;
		}
		' . ($this->cMR ? ' jumpTo(top.fsMod.recentIds[\'file\'],\'\');' : '');

        $this->moduleTemplate->getPageRenderer()->addJsInlineCode(
            'FileSystemNavigationFrame',
            $inlineJs
        );
    }

    /**
     * Main function, rendering the folder tree
     */
    public function main()
    {
        // Produce browse-tree:
        $tree = $this->foldertree->getBrowsableTree();
        // Outputting page tree:
        $this->moduleTemplate->setContent($tree);
        // Setting up the buttons
        $this->getButtons();
        // Build the <body> for the module
        $this->moduleTemplate->setTitle('TYPO3 Folder Tree');
        $this->content = $this->moduleTemplate->renderContent();
    }

    /**
     * Register docHeader buttons
     */
    protected function getButtons()
    {
        /** @var ButtonBar $buttonBar */
        $buttonBar = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar();

        /** @var IconFactory $iconFactory */
        $iconFactory = $this->moduleTemplate->getIconFactory();

        // Refresh
        $refreshButton = $buttonBar->makeLinkButton()
            ->setHref(GeneralUtility::getIndpEnv('REQUEST_URI'))
            ->setTitle($this->getLanguageService()->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:labels.reload'))
            ->setIcon($iconFactory->getIcon('actions-refresh', Icon::SIZE_SMALL));
        $buttonBar->addButton($refreshButton, ButtonBar::BUTTON_POSITION_RIGHT);

        // CSH
        $cshButton = $buttonBar->makeHelpButton()
            ->setModuleName('xMOD_csh_corebe')
            ->setFieldName('filetree');
        $buttonBar->addButton($cshButton);
    }

    /**********************************
     * AJAX Calls
     **********************************/
    /**
     * Makes the AJAX call to expand or collapse the foldertree.
     * Called by an AJAX Route, see AjaxRequestHandler
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function ajaxExpandCollapse(ServerRequestInterface $request, ResponseInterface $response)
    {
        $this->init();
        $tree = $this->foldertree->getBrowsableTree();
        if ($this->foldertree->getAjaxStatus() === false) {
            $response = $response->withStatus(500);
        } else {
            $response->getBody()->write(json_encode($tree));
        }

        return $response;
    }

    /**
     * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
     */
    protected function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }

    /**
     * Returns an instance of LanguageService
     *
     * @return \TYPO3\CMS\Lang\LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }
}
