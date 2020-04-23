<?php

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

namespace TYPO3\CMS\Backend\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Domain\Repository\Module\BackendModuleRepository;
use TYPO3\CMS\Backend\Module\ModuleLoader;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Toolbar\ToolbarItemInterface;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Localization\LanguageService;
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
    /**
     * @var string
     */
    protected $css = '';

    /**
     * @var array
     */
    protected $toolbarItems = [];

    /**
     * @var string
     */
    protected $templatePath = 'EXT:backend/Resources/Private/Templates/';

    /**
     * @var string
     */
    protected $partialPath = 'EXT:backend/Resources/Private/Partials/';

    /**
     * @var BackendModuleRepository
     */
    protected $backendModuleRepository;

    /**
     * @var PageRenderer
     */
    protected $pageRenderer;

    /**
     * @var IconFactory
     */
    protected $iconFactory;

    /**
     * @var Typo3Version
     */
    protected $typo3Version;

    /**
     * @var UriBuilder
     */
    protected $uriBuilder;

    /**
     * @var \SplObjectStorage
     */
    protected $moduleStorage;

    /**
     * @var ModuleLoader
     */
    protected $moduleLoader;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->getLanguageService()->includeLLFile('EXT:core/Resources/Private/Language/locallang_misc.xlf');
        $this->backendModuleRepository = GeneralUtility::makeInstance(BackendModuleRepository::class);
        $this->iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        $this->uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        $this->typo3Version = GeneralUtility::makeInstance(Typo3Version::class);
        $this->pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
        $this->moduleLoader = GeneralUtility::makeInstance(ModuleLoader::class);
        $this->moduleLoader->observeWorkspaces = true;
        $this->moduleLoader->load($GLOBALS['TBE_MODULES']);

        // Add default BE javascript
        $this->pageRenderer->addJsFile('EXT:backend/Resources/Public/JavaScript/md5.js');
        $this->pageRenderer->addJsFile('EXT:backend/Resources/Public/JavaScript/backend.js');
        $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/LoginRefresh', 'function(LoginRefresh) {
			LoginRefresh.setIntervalTime(' . MathUtility::forceIntegerInRange((int)$GLOBALS['TYPO3_CONF_VARS']['BE']['sessionTimeout'] - 60, 60) . ');
			LoginRefresh.setLoginFramesetUrl(' . GeneralUtility::quoteJSvalue((string)$this->uriBuilder->buildUriFromRoute('login_frameset')) . ');
			LoginRefresh.setLogoutUrl(' . GeneralUtility::quoteJSvalue((string)$this->uriBuilder->buildUriFromRoute('logout')) . ');
			LoginRefresh.initialize();
		}');

        $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/BroadcastService', 'function(service) { service.listen(); }');
        $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/ModuleMenu');
        $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/Toolbar');
        $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/Notification');
        $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/Modal');
        $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/InfoWindow');
        $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/ContextMenu');

        // load the storage API and fill the UC into the PersistentStorage, so no additional AJAX call is needed
        $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/Storage/Persistent', 'function(PersistentStorage) {
            PersistentStorage.load(' . json_encode($this->getBackendUser()->uc) . ');
        }');

        $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/DebugConsole');

        $this->pageRenderer->addInlineLanguageLabelFile('EXT:core/Resources/Private/Language/locallang_core.xlf');
        $this->pageRenderer->addInlineLanguageLabelFile('EXT:core/Resources/Private/Language/locallang_misc.xlf');
        $this->pageRenderer->addInlineLanguageLabelFile('EXT:backend/Resources/Private/Language/locallang_layout.xlf');
        $this->pageRenderer->addInlineLanguageLabelFile('EXT:core/Resources/Private/Language/locallang_mod_web_list.xlf');
        $this->pageRenderer->addInlineLanguageLabelFile('EXT:core/Resources/Private/Language/debugger.xlf');
        $this->pageRenderer->addInlineLanguageLabelFile('EXT:core/Resources/Private/Language/wizard.xlf');

        $this->pageRenderer->addInlineSetting('ContextHelp', 'moduleUrl', (string)$this->uriBuilder->buildUriFromRoute('help_cshmanual'));
        $this->pageRenderer->addInlineSetting('ShowItem', 'moduleUrl', (string)$this->uriBuilder->buildUriFromRoute('show_item'));
        $this->pageRenderer->addInlineSetting('RecordHistory', 'moduleUrl', (string)$this->uriBuilder->buildUriFromRoute('record_history'));
        $this->pageRenderer->addInlineSetting('NewRecord', 'moduleUrl', (string)$this->uriBuilder->buildUriFromRoute('db_new'));
        $this->pageRenderer->addInlineSetting('FormEngine', 'moduleUrl', (string)$this->uriBuilder->buildUriFromRoute('record_edit'));
        $this->pageRenderer->addInlineSetting('RecordCommit', 'moduleUrl', (string)$this->uriBuilder->buildUriFromRoute('tce_db'));
        $this->pageRenderer->addInlineSetting('FileCommit', 'moduleUrl', (string)$this->uriBuilder->buildUriFromRoute('tce_file'));
        $this->pageRenderer->addInlineSetting('WebLayout', 'moduleUrl', (string)$this->uriBuilder->buildUriFromRoute('web_layout'));

        $this->initializeToolbarItems();
        $this->executeHook('constructPostProcess');

        $this->moduleStorage = $this->backendModuleRepository->loadAllowedModules(['user', 'help']);
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
     * Main function generating the BE scaffolding
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface the response with the content
     */
    public function mainAction(ServerRequestInterface $request): ResponseInterface
    {
        $this->executeHook('renderPreProcess');

        // Prepare the scaffolding, at this point extension may still add javascript and css
        $moduleTemplate = GeneralUtility::makeInstance(ModuleTemplate::class);
        $view = $moduleTemplate->getView();
        $view->setPartialRootPaths([GeneralUtility::getFileAbsFileName('EXT:backend/Resources/Private/Partials')]);
        $view->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName($this->templatePath . 'Backend/Main.html'));
        $view->assign('moduleMenuCollapsed', $this->getCollapseStateOfMenu());
        $view->assign('moduleMenu', $this->generateModuleMenu());
        $view->assign('topbar', $this->renderTopbar());
        $view->assign('hasModules', count($this->moduleStorage) > 0);

        if (!empty($this->css)) {
            $this->pageRenderer->addCssInlineBlock('BackendInlineCSS', $this->css);
        }
        $this->generateJavascript($request);

        // Set document title
        $typo3Version = 'TYPO3 CMS ' . $this->typo3Version->getVersion();
        $title = $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'] ? $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'] . ' [' . $typo3Version . ']' : $typo3Version;
        $moduleTemplate->setTitle($title);

        // Renders the backend scaffolding
        $content = $moduleTemplate->renderContent();
        $this->executeHook('renderPostProcess', ['content' => &$content]);
        return new HtmlResponse($content);
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

        $view->assign('hasModules', count($this->moduleStorage) > 0);
        $view->assign('modulesHaveNavigationComponent', $this->backendModuleRepository->modulesHaveNavigationComponent());
        $view->assign('logoUrl', PathUtility::getAbsoluteWebPath($logoPath));
        $view->assign('logoWidth', $logoWidth);
        $view->assign('logoHeight', $logoHeight);
        $view->assign('applicationVersion', $this->typo3Version->getVersion());
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
            /** @var ToolbarItemInterface $toolbarItem */
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
     *
     * @param ServerRequestInterface $request
     */
    protected function generateJavascript(ServerRequestInterface $request)
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
            $pageModuleUrl = (string)$this->uriBuilder->buildUriFromRoute($pageModule);
        }
        $t3Configuration = [
            'username' => htmlspecialchars($beUser->user['username']),
            'pageModule' => $pageModule,
            'pageModuleUrl' => $pageModuleUrl,
            'inWorkspace' => $beUser->workspace !== 0,
            'showRefreshLoginPopup' => (bool)($GLOBALS['TYPO3_CONF_VARS']['BE']['showRefreshLoginPopup'] ?? false)
        ];

        $this->pageRenderer->addJsInlineCode(
            'BackendConfiguration',
            '
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
        ' . $this->setStartupModule($request)
          . $this->handlePageEditing($request),
            false
        );
    }

    /**
     * Checking if the "&edit" variable was sent so we can open it for editing the page.
     */
    protected function handlePageEditing(ServerRequestInterface $request): string
    {
        $beUser = $this->getBackendUser();
        $userTsConfig = $this->getBackendUser()->getTSConfig();
        // EDIT page
        $editId = preg_replace('/[^[:alnum:]_]/', '', $request->getQueryParams()['edit'] ?? '');
        if ($editId) {
            // Looking up the page to edit, checking permissions:
            $where = ' AND (' . $beUser->getPagePermsClause(Permission::PAGE_EDIT) . ' OR ' . $beUser->getPagePermsClause(Permission::CONTENT_EDIT) . ')';
            $editRecord = null;
            if (MathUtility::canBeInterpretedAsInteger($editId)) {
                $editRecord = BackendUtility::getRecordWSOL('pages', $editId, '*', $where);
            }
            // If the page was accessible, then let the user edit it.
            if (is_array($editRecord) && $beUser->isInWebMount($editRecord)) {
                // Checking page edit parameter:
                if (!($userTsConfig['options.']['bookmark_onEditId_dontSetPageTree'] ?? false)) {
                    $bookmarkKeepExpanded = (bool)($userTsConfig['options.']['bookmark_onEditId_keepExistingExpanded'] ?? false);
                    // Expanding page tree:
                    BackendUtility::openPageTree((int)$editRecord['pid'], !$bookmarkKeepExpanded);
                }
                // Setting JS code to open editing:
                return '
		// Load page to edit:
	window.setTimeout("top.loadEditId(' . (int)$editRecord['uid'] . ');", 500);
			';
            }
            return '
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
        return '';
    }

    /**
     * Sets the startup module from either GETvars module and modParams or user configuration.
     *
     * @param ServerRequestInterface $request
     * @return string the JavaScript code for the startup module
     */
    protected function setStartupModule(ServerRequestInterface $request)
    {
        $startModule = preg_replace('/[^[:alnum:]_]/', '', $request->getQueryParams()['module'] ?? '');
        $startModuleParameters = '';
        if (!$startModule) {
            $beUser = $this->getBackendUser();
            // start module on first login, will be removed once used the first time
            if (isset($beUser->uc['startModuleOnFirstLogin'])) {
                $startModule = $beUser->uc['startModuleOnFirstLogin'];
                unset($beUser->uc['startModuleOnFirstLogin']);
                $beUser->writeUC();
            } elseif ($this->moduleLoader->checkMod($beUser->uc['startModule']) !== 'notFound') {
                $startModule = $beUser->uc['startModule'];
            } else {
                $startModule = $this->determineFirstAvailableBackendModule();
            }

            // check if the start module has additional parameters, so a redirect to a specific
            // action is possible
            if (strpos($startModule, '->') !== false) {
                [$startModule, $startModuleParameters] = explode('->', $startModule, 2);
            }
        }

        $moduleParameters = $request->getQueryParams()['modParams'] ?? '';
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

    protected function determineFirstAvailableBackendModule(): string
    {
        foreach ($this->moduleLoader->modules as $mainMod => $modData) {
            $hasSubmodules = !empty($modData['sub']) && is_array($modData['sub']);
            $isStandalone = $modData['standalone'] ?? false;
            if ($isStandalone) {
                return $modData['name'];
            }

            if ($hasSubmodules) {
                $firstSubmodule = reset($modData['sub']);
                return $firstSubmodule['name'];
            }
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
        $view = $this->getFluidTemplateObject($this->templatePath . 'ModuleMenu/Main.html');
        $view->assign('modules', $this->moduleStorage);
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
     * @return LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }

    /**
     * Returns the current BE user.
     *
     * @return BackendUserAuthentication
     */
    protected function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }
}
