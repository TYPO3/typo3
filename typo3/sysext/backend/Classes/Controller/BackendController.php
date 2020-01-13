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
use TYPO3\CMS\Backend\Domain\Repository\Module\BackendModuleRepository;
use TYPO3\CMS\Backend\Module\ModuleLoader;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Toolbar\ToolbarItemInterface;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Compatibility\PublicMethodDeprecationTrait;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\BackendWorkspaceRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Type\File\ImageInfo;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * Class for rendering the TYPO3 backend
 */
class BackendController
{
    use PublicMethodDeprecationTrait;

    /**
     * @var array
     */
    private $deprecatedPublicMethods = [
        'render' => 'Using BackendController::render() is deprecated and will not be possible anymore in TYPO3 v10.0.',
    ];

    /**
     * @var string
     */
    protected $content = '';

    /**
     * @var string
     */
    protected $css = '';

    /**
     * @var array
     */
    protected $cssFiles = [];

    /**
     * @var string
     */
    protected $js = '';

    /**
     * @var array
     */
    protected $jsFiles = [];

    /**
     * @var array
     */
    protected $toolbarItems = [];

    /**
     * @var bool
     */
    protected $debug;

    /**
     * @var string
     */
    protected $templatePath = 'EXT:backend/Resources/Private/Templates/';

    /**
     * @var string
     */
    protected $partialPath = 'EXT:backend/Resources/Private/Partials/';

    /**
     * @var \TYPO3\CMS\Backend\Domain\Repository\Module\BackendModuleRepository
     */
    protected $backendModuleRepository;

    /**
     * @var \TYPO3\CMS\Backend\Module\ModuleLoader Object for loading backend modules
     */
    protected $moduleLoader;

    /**
     * @var PageRenderer
     */
    protected $pageRenderer;

    /**
     * @var IconFactory
     */
    protected $iconFactory;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->getLanguageService()->includeLLFile('EXT:core/Resources/Private/Language/locallang_misc.xlf');

        $this->backendModuleRepository = GeneralUtility::makeInstance(BackendModuleRepository::class);
        $this->iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        // Set debug flag for BE development only
        $this->debug = (int)$GLOBALS['TYPO3_CONF_VARS']['BE']['debug'] === 1;
        // Initializes the backend modules structure for use later.
        $this->moduleLoader = GeneralUtility::makeInstance(ModuleLoader::class);
        $this->moduleLoader->load($GLOBALS['TBE_MODULES']);
        $this->pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
        // Add default BE javascript
        $this->jsFiles = [
            'md5' => 'EXT:backend/Resources/Public/JavaScript/md5.js',
            'evalfield' => 'EXT:backend/Resources/Public/JavaScript/jsfunc.evalfield.js',
            'backend' => 'EXT:backend/Resources/Public/JavaScript/backend.js',
        ];
        $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/LoginRefresh', 'function(LoginRefresh) {
			LoginRefresh.setIntervalTime(' . MathUtility::forceIntegerInRange((int)$GLOBALS['TYPO3_CONF_VARS']['BE']['sessionTimeout'] - 60, 60) . ');
			LoginRefresh.setLoginFramesetUrl(' . GeneralUtility::quoteJSvalue((string)$uriBuilder->buildUriFromRoute('login_frameset')) . ');
			LoginRefresh.setLogoutUrl(' . GeneralUtility::quoteJSvalue((string)$uriBuilder->buildUriFromRoute('logout')) . ');
			LoginRefresh.initialize();
		}');

        // load module menu
        $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/ModuleMenu');

        // load Toolbar class
        $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/Toolbar');

        // load Utility class
        $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/Utility');

        // load Notification functionality
        $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/Notification');

        // load Modals
        $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/Modal');

        // load InfoWindow
        $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/InfoWindow');

        // load ContextMenu
        $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/ContextMenu');

        // load the storage API and fill the UC into the PersistentStorage, so no additional AJAX call is needed
        $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/Storage');
        $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/Storage/Persistent', 'function(PersistentStorage) {
            PersistentStorage.load(' . json_encode($this->getBackendUser()->uc) . ');
        }');

        // load debug console
        $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/DebugConsole');

        $this->pageRenderer->addInlineLanguageLabelFile('EXT:core/Resources/Private/Language/locallang_core.xlf');
        $this->pageRenderer->addInlineLanguageLabelFile('EXT:core/Resources/Private/Language/locallang_misc.xlf');
        $this->pageRenderer->addInlineLanguageLabelFile('EXT:backend/Resources/Private/Language/locallang_layout.xlf');
        $this->pageRenderer->addInlineLanguageLabelFile('EXT:core/Resources/Private/Language/locallang_mod_web_list.xlf');

        $this->pageRenderer->addInlineLanguageLabelFile('EXT:core/Resources/Private/Language/debugger.xlf');
        $this->pageRenderer->addInlineLanguageLabelFile('EXT:core/Resources/Private/Language/wizard.xlf');

        $this->pageRenderer->addInlineSetting('ShowItem', 'moduleUrl', (string)$uriBuilder->buildUriFromRoute('show_item'));
        $this->pageRenderer->addInlineSetting('RecordHistory', 'moduleUrl', (string)$uriBuilder->buildUriFromRoute('record_history'));
        $this->pageRenderer->addInlineSetting('NewRecord', 'moduleUrl', (string)$uriBuilder->buildUriFromRoute('db_new'));
        $this->pageRenderer->addInlineSetting('FormEngine', 'moduleUrl', (string)$uriBuilder->buildUriFromRoute('record_edit'));
        $this->pageRenderer->addInlineSetting('RecordCommit', 'moduleUrl', (string)$uriBuilder->buildUriFromRoute('tce_db'));
        $this->pageRenderer->addInlineSetting('WebLayout', 'moduleUrl', (string)$uriBuilder->buildUriFromRoute('web_layout'));

        $this->css = '';

        $this->initializeToolbarItems();
        $this->executeHook('constructPostProcess');
    }

    /**
     * Initialize toolbar item objects
     *
     * @throws \RuntimeException
     */
    protected function initializeToolbarItems()
    {
        $toolbarItemInstances = [];
        foreach ($GLOBALS['TYPO3_CONF_VARS']['BE']['toolbarItems'] ?? [] as $className) {
            $toolbarItemInstance = GeneralUtility::makeInstance($className);
            if (!$toolbarItemInstance instanceof ToolbarItemInterface) {
                throw new \RuntimeException(
                    'class ' . $className . ' is registered as toolbar item but does not implement'
                        . ToolbarItemInterface::class,
                    1415958218
                );
            }
            $index = (int)$toolbarItemInstance->getIndex();
            if ($index < 0 || $index > 100) {
                throw new \RuntimeException(
                    'getIndex() must return an integer between 0 and 100',
                    1415968498
                );
            }
            // Find next free position in array
            while (array_key_exists($index, $toolbarItemInstances)) {
                $index++;
            }
            $toolbarItemInstances[$index] = $toolbarItemInstance;
        }
        ksort($toolbarItemInstances);
        $this->toolbarItems = $toolbarItemInstances;
    }

    /**
     * Injects the request object for the current request or subrequest
     * As this controller goes only through the render() method, it is rather simple for now
     *
     * @return ResponseInterface the response with the content
     */
    public function mainAction(): ResponseInterface
    {
        $this->render();
        return new HtmlResponse($this->content);
    }

    /**
     * Main function generating the BE scaffolding
     */
    protected function render()
    {
        $this->executeHook('renderPreProcess');

        // Prepare the scaffolding, at this point extension may still add javascript and css
        $view = $this->getFluidTemplateObject($this->templatePath . 'Backend/Main.html');

        $view->assign('moduleMenuCollapsed', $this->getCollapseStateOfMenu());
        $view->assign('moduleMenu', $this->generateModuleMenu());
        $view->assign('topbar', $this->renderTopbar());

        /******************************************************
         * Now put the complete backend document together
         ******************************************************/
        foreach ($this->cssFiles as $cssFileName => $cssFile) {
            $this->pageRenderer->addCssFile($cssFile);
            // Load additional css files to overwrite existing core styles
            if (!empty($GLOBALS['TBE_STYLES']['stylesheets'][$cssFileName])) {
                $this->pageRenderer->addCssFile($GLOBALS['TBE_STYLES']['stylesheets'][$cssFileName]);
            }
        }
        if (!empty($this->css)) {
            $this->pageRenderer->addCssInlineBlock('BackendInlineCSS', $this->css);
        }
        foreach ($this->jsFiles as $jsFile) {
            $this->pageRenderer->addJsFile($jsFile);
        }
        $this->generateJavascript();
        $this->pageRenderer->addJsInlineCode('BackendInlineJavascript', $this->js, false);

        // Set document title:
        $title = $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'] ? $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'] . ' [TYPO3 CMS ' . TYPO3_version . ']' : 'TYPO3 CMS ' . TYPO3_version;
        // Renders the module page
        $this->content = $this->getDocumentTemplate()->render($title, $view->render());
        $hookConfiguration = ['content' => &$this->content];
        $this->executeHook('renderPostProcess', $hookConfiguration);
    }

    /**
     * Renders the topbar, containing the backend logo, sitename etc.
     *
     * @return string
     */
    protected function renderTopbar()
    {
        $view = $this->getFluidTemplateObject($this->partialPath . 'Backend/Topbar.html');

        // Extension Configuration to find the TYPO3 logo in the left corner
        $extConf = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('backend');
        $logoPath = '';
        if (!empty($extConf['backendLogo'])) {
            $customBackendLogo = GeneralUtility::getFileAbsFileName(ltrim($extConf['backendLogo'], '/'));
            if (!empty($customBackendLogo)) {
                $logoPath = $customBackendLogo;
            }
        }
        // if no custom logo was set or the path is invalid, use the original one
        if (empty($logoPath) || !file_exists($logoPath)) {
            $logoPath = GeneralUtility::getFileAbsFileName('EXT:backend/Resources/Public/Images/typo3_logo_orange.svg');
            $logoWidth = 22;
            $logoHeight = 22;
        } else {
            // set width/height for custom logo
            $imageInfo = GeneralUtility::makeInstance(ImageInfo::class, $logoPath);
            $logoWidth = $imageInfo->getWidth() ?? '22';
            $logoHeight = $imageInfo->getHeight() ?? '22';

            // High-resolution?
            if (strpos($logoPath, '@2x.') !== false) {
                $logoWidth /= 2;
                $logoHeight /= 2;
            }
        }

        $view->assign('logoUrl', PathUtility::getAbsoluteWebPath($logoPath));
        $view->assign('logoWidth', $logoWidth);
        $view->assign('logoHeight', $logoHeight);
        $view->assign('applicationVersion', TYPO3_version);
        $view->assign('siteName', $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename']);
        $view->assign('toolbar', $this->renderToolbar());

        return $view->render();
    }

    /**
     * Renders the items in the top toolbar
     *
     * @return string top toolbar elements as HTML
     */
    protected function renderToolbar()
    {
        $toolbar = [];
        foreach ($this->toolbarItems as $toolbarItem) {
            /** @var \TYPO3\CMS\Backend\Toolbar\ToolbarItemInterface $toolbarItem */
            if ($toolbarItem->checkAccess()) {
                $hasDropDown = (bool)$toolbarItem->hasDropDown();
                $additionalAttributes = (array)$toolbarItem->getAdditionalAttributes();

                $liAttributes = [];

                // Merge class: Add dropdown class if hasDropDown, add classes from additional attributes
                $classes = [];
                $classes[] = 'toolbar-item';
                $classes[] = 't3js-toolbar-item';
                if (isset($additionalAttributes['class'])) {
                    $classes[] = $additionalAttributes['class'];
                    unset($additionalAttributes['class']);
                }
                $liAttributes['class'] = implode(' ', $classes);

                // Add further attributes
                foreach ($additionalAttributes as $name => $value) {
                    $liAttributes[$name] = $value;
                }

                // Create a unique id from class name
                $fullyQualifiedClassName = \get_class($toolbarItem);
                $className = GeneralUtility::underscoredToLowerCamelCase($fullyQualifiedClassName);
                $className = GeneralUtility::camelCaseToLowerCaseUnderscored($className);
                $className = str_replace(['_', '\\'], '-', $className);
                $liAttributes['id'] = $className;

                // Create data attribute identifier
                $shortName = substr($fullyQualifiedClassName, strrpos($fullyQualifiedClassName, '\\') + 1);
                $dataToolbarIdentifier = GeneralUtility::camelCaseToLowerCaseUnderscored($shortName);
                $dataToolbarIdentifier = str_replace('_', '-', $dataToolbarIdentifier);
                $liAttributes['data-toolbar-identifier'] = $dataToolbarIdentifier;

                $toolbar[] = '<li ' . GeneralUtility::implodeAttributes($liAttributes, true) . '>';

                if ($hasDropDown) {
                    $toolbar[] = '<a href="#" class="toolbar-item-link dropdown-toggle" data-toggle="dropdown">';
                    $toolbar[] = $toolbarItem->getItem();
                    $toolbar[] = '</a>';
                    $toolbar[] = '<div class="dropdown-menu" role="menu">';
                    $toolbar[] = $toolbarItem->getDropDown();
                    $toolbar[] = '</div>';
                } else {
                    $toolbar[] = $toolbarItem->getItem();
                }
                $toolbar[] = '</li>';
            }
        }
        return implode(LF, $toolbar);
    }

    /**
     * Generates the JavaScript code for the backend.
     */
    protected function generateJavascript()
    {
        $beUser = $this->getBackendUser();
        // Needed for FormEngine manipulation (date picker)
        $dateFormat = ($GLOBALS['TYPO3_CONF_VARS']['SYS']['USdateFormat'] ? ['MM-DD-YYYY', 'HH:mm MM-DD-YYYY'] : ['DD-MM-YYYY', 'HH:mm DD-MM-YYYY']);
        $this->pageRenderer->addInlineSetting('DateTimePicker', 'DateFormat', $dateFormat);

        // If another page module was specified, replace the default Page module with the new one
        $newPageModule = trim($beUser->getTSConfig()['options.']['overridePageModule'] ?? '');
        $pageModule = BackendUtility::isModuleSetInTBE_MODULES($newPageModule) ? $newPageModule : 'web_layout';
        $pageModuleUrl = '';
        if (!$beUser->check('modules', $pageModule)) {
            $pageModule = '';
        } else {
            $pageModuleUrl = (string)GeneralUtility::makeInstance(UriBuilder::class)->buildUriFromRoute($pageModule);
        }
        $t3Configuration = [
            'username' => htmlspecialchars($beUser->user['username']),
            'pageModule' => $pageModule,
            'pageModuleUrl' => $pageModuleUrl,
            'inWorkspace' => $beUser->workspace !== 0,
            'showRefreshLoginPopup' => (bool)($GLOBALS['TYPO3_CONF_VARS']['BE']['showRefreshLoginPopup'] ?? false)
        ];
        $this->js .= '
	TYPO3.configuration = ' . json_encode($t3Configuration) . ';
	/**
	 * Frameset Module object
	 *
	 * Used in main modules with a frameset for submodules to keep the ID between modules
	 * Typically that is set by something like this in a Web>* sub module:
	 *		if (top.fsMod) top.fsMod.recentIds["web"] = "\'.(int)$this->id.\'";
	 * 		if (top.fsMod) top.fsMod.recentIds["file"] = "...(file reference/string)...";
	 */
	var fsMod = {
		recentIds: [],					// used by frameset modules to track the most recent used id for list frame.
		navFrameHighlightedID: [],		// used by navigation frames to track which row id was highlighted last time
		currentBank: "0"
	};

	top.goToModule = function(modName, cMR_flag, addGetVars) {
		TYPO3.ModuleMenu.App.showModule(modName, addGetVars);
	}
	' . $this->setStartupModule();
        // Check editing of page:
        $this->handlePageEditing();
    }

    /**
     * Checking if the "&edit" variable was sent so we can open it for editing the page.
     */
    protected function handlePageEditing()
    {
        $beUser = $this->getBackendUser();
        $userTsConfig = $this->getBackendUser()->getTSConfig();
        // EDIT page:
        $editId = preg_replace('/[^[:alnum:]_]/', '', GeneralUtility::_GET('edit'));
        if ($editId) {
            // Looking up the page to edit, checking permissions:
            $where = ' AND (' . $beUser->getPagePermsClause(Permission::PAGE_EDIT) . ' OR ' . $beUser->getPagePermsClause(Permission::CONTENT_EDIT) . ')';
            if (MathUtility::canBeInterpretedAsInteger($editId)) {
                $editRecord = BackendUtility::getRecordWSOL('pages', $editId, '*', $where);
            } else {
                $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
                $queryBuilder->getRestrictions()
                    ->removeAll()
                    ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
                    ->add(GeneralUtility::makeInstance(BackendWorkspaceRestriction::class));

                $editRecord = $queryBuilder->select('*')
                    ->from('pages')
                    ->where(
                        $queryBuilder->expr()->eq(
                            'alias',
                            $queryBuilder->createNamedParameter($editId, \PDO::PARAM_STR)
                        ),
                        $queryBuilder->expr()->orX(
                            $beUser->getPagePermsClause(Permission::PAGE_EDIT),
                            $beUser->getPagePermsClause(Permission::CONTENT_EDIT)
                        )
                    )
                    ->setMaxResults(1)
                    ->execute()
                    ->fetch();

                if ($editRecord !== false) {
                    BackendUtility::workspaceOL('pages', $editRecord);
                }
            }
            // If the page was accessible, then let the user edit it.
            if (is_array($editRecord) && $beUser->isInWebMount($editRecord)) {
                // Setting JS code to open editing:
                $this->js .= '
		// Load page to edit:
	window.setTimeout("top.loadEditId(' . (int)$editRecord['uid'] . ');", 500);
			';
                // Checking page edit parameter:
                if (!($userTsConfig['options.']['bookmark_onEditId_dontSetPageTree'] ?? false)) {
                    $bookmarkKeepExpanded = (bool)($userTsConfig['options.']['bookmark_onEditId_keepExistingExpanded'] ?? false);
                    // Expanding page tree:
                    BackendUtility::openPageTree((int)$editRecord['pid'], !$bookmarkKeepExpanded);
                }
            } else {
                $this->js .= '
            // Warning about page editing:
            require(["TYPO3/CMS/Backend/Modal", "TYPO3/CMS/Backend/Severity"], function(Modal, Severity) {
                Modal.show("", ' . GeneralUtility::quoteJSvalue(sprintf($this->getLanguageService()->getLL('noEditPage'), $editId)) . ', Severity.notice, [{
                    text: ' . GeneralUtility::quoteJSvalue($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_common.xlf:close')) . ',
                    active: true,
                    btnClass: "btn-info",
                    name: "cancel",
                    trigger: function () {
                        Modal.currentModal.trigger("modal-dismiss");
                    }
                }])
            });';
            }
        }
    }

    /**
     * Sets the startup module from either GETvars module and modParams or user configuration.
     *
     * @return string the JavaScript code for the startup module
     */
    protected function setStartupModule()
    {
        $startModule = preg_replace('/[^[:alnum:]_]/', '', GeneralUtility::_GET('module'));
        $startModuleParameters = '';
        if (!$startModule) {
            $beUser = $this->getBackendUser();
            // start module on first login, will be removed once used the first time
            if (isset($beUser->uc['startModuleOnFirstLogin'])) {
                $startModule = $beUser->uc['startModuleOnFirstLogin'];
                unset($beUser->uc['startModuleOnFirstLogin']);
                $beUser->writeUC();
            } elseif ($beUser->uc['startModule']) {
                $startModule = $beUser->uc['startModule'];
            }

            // check if the start module has additional parameters, so a redirect to a specific
            // action is possible
            if (strpos($startModule, '->') !== false) {
                list($startModule, $startModuleParameters) = explode('->', $startModule, 2);
            }
        }

        $moduleParameters = GeneralUtility::_GET('modParams');
        // if no GET parameters are set, check if there are parameters given from the UC
        if (!$moduleParameters && $startModuleParameters) {
            $moduleParameters = $startModuleParameters;
        }

        if ($startModule) {
            return '
					// start in module:
				top.startInModule = [' . GeneralUtility::quoteJSvalue($startModule) . ', ' . GeneralUtility::quoteJSvalue($moduleParameters) . '];
			';
        }
        return '';
    }

    /**
     * Adds a css snippet to the backend
     *
     * @param string $css Css snippet
     * @throws \InvalidArgumentException
     */
    public function addCss($css)
    {
        if (!is_string($css)) {
            throw new \InvalidArgumentException('parameter $css must be of type string', 1195129642);
        }
        $this->css .= $css;
    }

    /**
     * Executes defined hooks functions for the given identifier.
     *
     * These hook identifiers are valid:
     * + constructPostProcess
     * + renderPreProcess
     * + renderPostProcess
     *
     * @param string $identifier Specific hook identifier
     * @param array $hookConfiguration Additional configuration passed to hook functions
     */
    protected function executeHook($identifier, array $hookConfiguration = [])
    {
        $options = &$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/backend.php'];
        foreach ($options[$identifier] ?? [] as $hookFunction) {
            GeneralUtility::callUserFunction($hookFunction, $hookConfiguration, $this);
        }
    }

    /**
     * loads all modules from the repository
     * and renders it with a template
     *
     * @return string
     */
    protected function generateModuleMenu()
    {
        // get all modules except the user modules for the side menu
        $moduleStorage = $this->backendModuleRepository->loadAllowedModules(['user', 'help']);

        $view = $this->getFluidTemplateObject($this->templatePath . 'ModuleMenu/Main.html');
        $view->assign('modules', $moduleStorage);
        return $view->render();
    }

    protected function getCollapseStateOfMenu(): bool
    {
        $uc = json_decode(json_encode($this->getBackendUser()->uc), true);
        $collapseState = $uc['BackendComponents']['States']['typo3-module-menu']['collapsed'] ?? false;

        return $collapseState === true || $collapseState === 'true';
    }

    /**
     * Returns the Module menu for the AJAX request
     *
     * @return ResponseInterface
     */
    public function getModuleMenu(): ResponseInterface
    {
        return new JsonResponse(['menu' => $this->generateModuleMenu()]);
    }

    /**
     * Returns the toolbar for the AJAX request
     *
     * @return ResponseInterface
     */
    public function getTopbar(): ResponseInterface
    {
        return new JsonResponse(['topbar' => $this->renderTopbar()]);
    }

    /**
     * returns a new standalone view, shorthand function
     *
     * @param string $templatePathAndFileName optional the path to set the template path and filename
     * @return \TYPO3\CMS\Fluid\View\StandaloneView
     */
    protected function getFluidTemplateObject($templatePathAndFileName = null)
    {
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setPartialRootPaths([GeneralUtility::getFileAbsFileName('EXT:backend/Resources/Private/Partials')]);
        if ($templatePathAndFileName) {
            $view->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName($templatePathAndFileName));
        }
        return $view;
    }

    /**
     * Returns LanguageService
     *
     * @return \TYPO3\CMS\Core\Localization\LanguageService
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

    /**
     * Returns an instance of DocumentTemplate
     *
     * @return \TYPO3\CMS\Backend\Template\DocumentTemplate
     */
    protected function getDocumentTemplate()
    {
        return $GLOBALS['TBE_TEMPLATE'];
    }
}
