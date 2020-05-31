<?php
declare(strict_types = 1);
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
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Tree\View\ElementBrowserFolderTreeView;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Compatibility\PublicPropertyDeprecationTrait;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Filelist\FileListFolderTree;
use TYPO3\CMS\Recordlist\Tree\View\DummyLinkParameterProvider;

/**
 * Main script class for rendering of the folder tree
 * @internal This class is a specific Backend controller implementation and is not considered part of the Public TYPO3 API.
 */
class FileSystemNavigationFrameController
{
    use PublicPropertyDeprecationTrait;

    /**
     * Properties which have been moved to protected status from public
     *
     * @var array
     */
    protected $deprecatedPublicProperties = [
        'content' => 'Using $content of class FileSystemNavigationFrameController from the outside is discouraged, as this variable is only used for internal storage.',
        'foldertree' => 'Using $foldertree of class FileSystemNavigationFrameController from the outside is discouraged, as this variable is only used for internal storage.',
        'currentSubScript' => 'Using $currentSubScript of class FileSystemNavigationFrameController from the outside is discouraged, as this variable is only used for internal storage.',
        'cMR' => 'Using $cMR of class FileSystemNavigationFrameController from the outside is discouraged, as this variable is only used for internal storage.',
    ];

    /**
     * Content accumulates in this variable.
     *
     * @var string
     */
    protected $content;

    /**
     * @var \TYPO3\CMS\Backend\Tree\View\FolderTreeView
     */
    protected $foldertree;

    /**
     * @var string
     */
    protected $currentSubScript;

    /**
     * @var bool
     */
    protected $cMR;

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
        // @deprecated since TYPO3 v9, will be obsolete in TYPO3 v10.0 with removal of init()
        $request = $GLOBALS['TYPO3_REQUEST'];
        $GLOBALS['SOBE'] = $this;
        // @deprecated since TYPO3 v9, will be moved out of __construct() in TYPO3 v10.0
        $this->init($request);
    }

    /**
     * @param ServerRequestInterface $request the current request
     * @return ResponseInterface the response with the content
     */
    public function mainAction(ServerRequestInterface $request): ResponseInterface
    {
        $this->initializePageTemplate();
        $this->renderFolderTree($request);
        return new HtmlResponse($this->content);
    }

    /**
     * Makes the AJAX call to expand or collapse the foldertree.
     * Called by an AJAX Route, see AjaxRequestHandler
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function ajaxExpandCollapse(ServerRequestInterface $request): ResponseInterface
    {
        $tree = $this->foldertree->getBrowsableTree();
        if ($this->foldertree->getAjaxStatus() === false) {
            return new JsonResponse(null, 500);
        }
        return new JsonResponse([$tree]);
    }

    /**
     * Initialization of the script class
     *
     * @param ServerRequestInterface $request the current request
     */
    protected function init(ServerRequestInterface $request = null)
    {
        if ($request === null) {
            // Method signature in TYPO3 v10.0: protected function init(ServerRequestInterface $request)
            trigger_error('FileSystemNavigationFrameController->init() will be set to protected in TYPO3 v10.0. Do not call from other extension.', E_USER_DEPRECATED);
            $request = $GLOBALS['TYPO3_REQUEST'];
        }

        $this->moduleTemplate = GeneralUtility::makeInstance(ModuleTemplate::class);

        $parsedBody = $request->getParsedBody();
        $queryParams = $request->getQueryParams();

        $this->currentSubScript = $parsedBody['currentSubScript'] ?? $queryParams['currentSubScript'] ?? null;
        $this->cMR = (bool)($parsedBody['cMR'] ?? $queryParams['cMR'] ?? false);
        $scopeData = $parsedBody['scopeData'] ?? $queryParams['scopeData'] ?? '';
        $scopeHash = $parsedBody['scopeHash'] ?? $queryParams['scopeHash'] ?? '';

        if (!empty($scopeData) && hash_equals(GeneralUtility::hmac($scopeData), $scopeHash)) {
            $this->scopeData = json_decode($scopeData, true);
        }

        // Create folder tree object:
        if (!empty($this->scopeData)) {
            $this->foldertree = GeneralUtility::makeInstance($this->scopeData['class']);
            $this->foldertree->thisScript = $this->scopeData['script'];
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
            $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
            $this->foldertree = GeneralUtility::makeInstance(FileListFolderTree::class);
            $this->foldertree->thisScript = (string)$uriBuilder->buildUriFromRoute('file_navframe');
        }
    }

    /**
     * initialization for the visual parts of the class
     * Use template rendering only if this is a non-AJAX call
     *
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0
     */
    public function initPage()
    {
        trigger_error('FileSystemNavigationFrameController->initPage() will be replaced by protected method initializePageTemplate() in TYPO3 v10.0. Do not call from other extension.', E_USER_DEPRECATED);
        $this->initializePageTemplate();
    }

    /**
     * Initialization for the visual parts of the class
     * Use template rendering only if this is a non-AJAX call
     */
    protected function initializePageTemplate(): void
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
     *
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0
     */
    public function main()
    {
        trigger_error('FileSystemNavigationFrameController->main() will be replaced by protected method renderFolderTree() in TYPO3 v10.0. Do not call from other extension.', E_USER_DEPRECATED);
        $this->renderFolderTree($GLOBALS['TYPO3_REQUEST']);
    }

    /**
     * Main function, rendering the folder tree
     *
     * @param ServerRequestInterface $request
     */
    protected function renderFolderTree(ServerRequestInterface $request): void
    {
        // Produce browse-tree:
        $tree = $this->foldertree->getBrowsableTree();
        // Outputting page tree:
        $this->moduleTemplate->setContent($tree);
        // Setting up the buttons
        $this->getButtons($request);
        // Build the <body> for the module
        $this->moduleTemplate->setTitle('TYPO3 Folder Tree');
        $this->content = $this->moduleTemplate->renderContent();
    }

    /**
     * Register docHeader buttons
     *
     * @param ServerRequestInterface $request
     */
    protected function getButtons(ServerRequestInterface $request): void
    {
        /** @var ButtonBar $buttonBar */
        $buttonBar = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar();
        /** @var IconFactory $iconFactory */
        $iconFactory = $this->moduleTemplate->getIconFactory();
        /** @var \TYPO3\CMS\Core\Http\NormalizedParams */
        $normalizedParams = $request->getAttribute('normalizedParams');

        // Refresh
        $refreshButton = $buttonBar->makeLinkButton()
            ->setHref($normalizedParams->getRequestUri())
            ->setTitle($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.reload'))
            ->setIcon($iconFactory->getIcon('actions-refresh', Icon::SIZE_SMALL));
        $buttonBar->addButton($refreshButton, ButtonBar::BUTTON_POSITION_RIGHT);

        // CSH
        $cshButton = $buttonBar->makeHelpButton()
            ->setModuleName('xMOD_csh_corebe')
            ->setFieldName('filetree');
        $buttonBar->addButton($cshButton);
    }

    /**
     * @return BackendUserAuthentication
     */
    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    /**
     * Returns an instance of LanguageService
     *
     * @return LanguageService
     */
    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
