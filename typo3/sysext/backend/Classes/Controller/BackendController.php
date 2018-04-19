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
use TYPO3\CMS\Backend\Domain\Repository\Module\BackendModuleRepository;
use TYPO3\CMS\Backend\Module\ModuleLoader;
use TYPO3\CMS\Backend\Toolbar\ToolbarItemInterface;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\BackendWorkspaceRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Type\File\ImageInfo;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
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
        $this->getLanguageService()->includeLLFile('EXT:lang/Resources/Private/Language/locallang_misc.xlf');
        $this->backendModuleRepository = GeneralUtility::makeInstance(BackendModuleRepository::class);
        $this->iconFactory = GeneralUtility::makeInstance(IconFactory::class);

        // Set debug flag for BE development only
        $this->debug = (int)$GLOBALS['TYPO3_CONF_VARS']['BE']['debug'] === 1;
        // Initializes the backend modules structure for use later.
        $this->moduleLoader = GeneralUtility::makeInstance(ModuleLoader::class);
        $this->moduleLoader->load($GLOBALS['TBE_MODULES']);
        $this->pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
        $this->pageRenderer->loadExtJS();
        // included for the module menu JavaScript, please note that this is subject to change
        $this->pageRenderer->loadJquery();
        $this->pageRenderer->addExtDirectCode();
        // Add default BE javascript
        $this->jsFiles = [
            'locallang' => $this->getLocalLangFileName(),
            'md5' => 'EXT:backend/Resources/Public/JavaScript/md5.js',
            'evalfield' => 'EXT:backend/Resources/Public/JavaScript/jsfunc.evalfield.js',
            'backend' => 'EXT:backend/Resources/Public/JavaScript/backend.js',
        ];
        $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/LoginRefresh', 'function(LoginRefresh) {
			LoginRefresh.setIntervalTime(' . MathUtility::forceIntegerInRange((int)$GLOBALS['TYPO3_CONF_VARS']['BE']['sessionTimeout'] - 60, 60) . ');
			LoginRefresh.setLoginFramesetUrl(' . GeneralUtility::quoteJSvalue(BackendUtility::getModuleUrl('login_frameset')) . ');
			LoginRefresh.setLogoutUrl(' . GeneralUtility::quoteJSvalue(BackendUtility::getModuleUrl('logout')) . ');
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

        // load ContextMenu
        $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/ContextMenu');

        // load the storage API and fill the UC into the PersistentStorage, so no additional AJAX call is needed
        $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/Storage', 'function(Storage) {
			Storage.Persistent.load(' . json_encode($this->getBackendUser()->uc) . ');
		}');

        // load debug console
        $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/DebugConsole');

        $this->pageRenderer->addInlineSetting('ShowItem', 'moduleUrl', BackendUtility::getModuleUrl('show_item'));

        $this->css = '';

        $this->initializeToolbarItems();
        $this->executeHook('constructPostProcess');
        $this->includeLegacyBackendItems();
    }

    /**
     * Add hooks from the additional backend items to load certain things for the main backend.
     * This was previously called from the global scope from backend.php.
     *
     * Please note that this method will be removed in TYPO3 v9. it does not throw a deprecation warning as it is protected and still called on every main backend request.
     */
    protected function includeLegacyBackendItems()
    {
        $TYPO3backend = $this;
        // Include extensions which may add css, javascript or toolbar items
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['typo3/backend.php']['additionalBackendItems'])) {
            GeneralUtility::deprecationLog('The hook $TYPO3_CONF_VARS["typo3/backend.php"]["additionalBackendItems"] is deprecated in TYPO3 v8, and will be removed in TYPO3 v9. Use the "constructPostProcess" hook within BackendController instead.');
            foreach ($GLOBALS['TYPO3_CONF_VARS']['typo3/backend.php']['additionalBackendItems'] as $additionalBackendItem) {
                include_once $additionalBackendItem;
            }
        }

        // Process ExtJS module js and css
        if (is_array($GLOBALS['TBE_MODULES']['_configuration'])) {
            foreach ($GLOBALS['TBE_MODULES']['_configuration'] as $moduleName => $moduleConfig) {
                if (is_array($moduleConfig['cssFiles'])) {
                    foreach ($moduleConfig['cssFiles'] as $cssFileName => $cssFile) {
                        $cssFile = GeneralUtility::getFileAbsFileName($cssFile);
                        $cssFile = PathUtility::getAbsoluteWebPath($cssFile);
                        $TYPO3backend->addCssFile($cssFileName, $cssFile);
                    }
                }
                if (is_array($moduleConfig['jsFiles'])) {
                    foreach ($moduleConfig['jsFiles'] as $jsFile) {
                        $jsFile = GeneralUtility::getFileAbsFileName($jsFile);
                        $jsFile = PathUtility::getAbsoluteWebPath($jsFile);
                        $TYPO3backend->addJavascriptFile($jsFile);
                    }
                }
            }
        }
    }

    /**
     * Initialize toolbar item objects
     *
     * @throws \RuntimeException
     */
    protected function initializeToolbarItems()
    {
        $toolbarItemInstances = [];
        $classNameRegistry = $GLOBALS['TYPO3_CONF_VARS']['BE']['toolbarItems'];
        foreach ($classNameRegistry as $className) {
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
     * @param ServerRequestInterface $request the current request
     * @param ResponseInterface $response
     * @return ResponseInterface the response with the content
     */
    public function mainAction(ServerRequestInterface $request, ResponseInterface $response)
    {
        $this->render();
        $response->getBody()->write($this->content);
        return $response;
    }

    /**
     * Main function generating the BE scaffolding
     */
    public function render()
    {
        $this->executeHook('renderPreProcess');

        // Prepare the scaffolding, at this point extension may still add javascript and css
        $view = $this->getFluidTemplateObject($this->templatePath . 'Backend/Main.html');

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
        $this->loadResourcesForRegisteredNavigationComponents();
        // @todo: remove this when ExtJS is removed
        $states = $this->getBackendUser()->uc['BackendComponents']['States'];
        $this->pageRenderer->addExtOnReadyCode('
            var TYPO3ExtJSStateProviderBridge = function() {};
            Ext.extend(TYPO3ExtJSStateProviderBridge, Ext.state.Provider, {
                state: {},
                queue: [],
                dirty: false,
                prefix: "BackendComponents.States.",
                initState: function(state) {
                    if (Ext.isArray(state)) {
                        Ext.each(state, function(item) {
                            this.state[item.name] = item.value;
                        }, this);
                    } else if (Ext.isObject(state)) {
                        Ext.iterate(state, function(key, value) {
                            this.state[key] = value;
                        }, this);
                    } else {
                        this.state = {};
                    }
                    var me = this;
                    window.setInterval(function() {
                        me.submitState(me)
                    }, 750);
                },
                get: function(name, defaultValue) {
                    return TYPO3.Storage.Persistent.isset(this.prefix + name) ? TYPO3.Storage.Persistent.get(this.prefix + name) : defaultValue;
                },
                clear: function(name) {
                    TYPO3.Storage.Persistent.unset(this.prefix + name);
                },
                set: function(name, value) {
                    if (!name) {
                        return;
                    }
                    this.queueChange(name, value);
                },
                queueChange: function(name, value) {
                    var o = {};
                    var i;
                    var found = false;

                    var lastValue = this.state[name];
                    for (i = 0; i < this.queue.length; i++) {
                        if (this.queue[i].name === name) {
                            lastValue = this.queue[i].value;
                        }
                    }
                    var changed = undefined === lastValue || lastValue !== value;

                    if (changed) {
                        o.name = name;
                        o.value = value;
                        for (i = 0; i < this.queue.length; i++) {
                            if (this.queue[i].name === o.name) {
                                this.queue[i] = o;
                                found = true;
                            }
                        }
                        if (false === found) {
                            this.queue.push(o);
                        }
                        this.dirty = true;
                    }
                },
                submitState: function(context) {
                    if (!context.dirty) {
                        return;
                    }
                    for (var i = 0; i < context.queue.length; ++i) {
                        TYPO3.Storage.Persistent.set(context.prefix + context.queue[i].name, context.queue[i].value).done(function() {
                            if (!context.dirty) {
                                context.queue = [];
                            }
                        });
                    }
                    context.dirty = false;
                }
            });
            Ext.state.Manager.setProvider(new TYPO3ExtJSStateProviderBridge());
            Ext.state.Manager.getProvider().initState(' . (!empty($states) ? json_encode($states) : []) . ');
            ');
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
        $extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['backend'], ['allowed_classes' => false]);
        $logoPath = '';
        if (!empty($extConf['backendLogo'])) {
            $customBackendLogo = GeneralUtility::getFileAbsFileName($extConf['backendLogo']);
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
     * Loads the css and javascript files of all registered navigation widgets
     */
    protected function loadResourcesForRegisteredNavigationComponents()
    {
        if (!is_array($GLOBALS['TBE_MODULES']['_navigationComponents'])) {
            return;
        }
        $loadedComponents = [];
        foreach ($GLOBALS['TBE_MODULES']['_navigationComponents'] as $module => $info) {
            if (in_array($info['componentId'], $loadedComponents)) {
                continue;
            }
            $loadedComponents[] = $info['componentId'];
            $component = strtolower(substr($info['componentId'], strrpos($info['componentId'], '-') + 1));
            $componentDirectory = 'components/' . $component . '/';
            if ($info['isCoreComponent']) {
                $componentDirectory = 'Resources/Public/JavaScript/extjs/' . $componentDirectory;
                $info['extKey'] = 'backend';
            }
            $absoluteComponentPath = ExtensionManagementUtility::extPath($info['extKey']) . $componentDirectory;
            $relativeComponentPath = PathUtility::getRelativePath(PATH_site . TYPO3_mainDir, $absoluteComponentPath);
            $cssFiles = GeneralUtility::getFilesInDir($absoluteComponentPath . 'css/', 'css');
            if (file_exists($absoluteComponentPath . 'css/loadorder.txt')) {
                // Don't allow inclusion outside directory
                $loadOrder = str_replace('../', '', file_get_contents($absoluteComponentPath . 'css/loadorder.txt'));
                $cssFilesOrdered = GeneralUtility::trimExplode(LF, $loadOrder, true);
                $cssFiles = array_merge($cssFilesOrdered, $cssFiles);
            }
            foreach ($cssFiles as $cssFile) {
                $this->pageRenderer->addCssFile($relativeComponentPath . 'css/' . $cssFile);
            }
            $jsFiles = GeneralUtility::getFilesInDir($absoluteComponentPath . 'javascript/', 'js');
            if (file_exists($absoluteComponentPath . 'javascript/loadorder.txt')) {
                // Don't allow inclusion outside directory
                $loadOrder = str_replace('../', '', file_get_contents($absoluteComponentPath . 'javascript/loadorder.txt'));
                $jsFilesOrdered = GeneralUtility::trimExplode(LF, $loadOrder, true);
                $jsFiles = array_merge($jsFilesOrdered, $jsFiles);
            }
            foreach ($jsFiles as $jsFile) {
                $this->pageRenderer->addJsFile($relativeComponentPath . 'javascript/' . $jsFile);
            }
            $this->pageRenderer->addInlineSetting('RecordHistory', 'moduleUrl', BackendUtility::getModuleUrl('record_history'));
            $this->pageRenderer->addInlineSetting('NewRecord', 'moduleUrl', BackendUtility::getModuleUrl('db_new'));
            $this->pageRenderer->addInlineSetting('FormEngine', 'moduleUrl', BackendUtility::getModuleUrl('record_edit'));
            $this->pageRenderer->addInlineSetting('RecordCommit', 'moduleUrl', BackendUtility::getModuleUrl('tce_db'));
            $this->pageRenderer->addInlineSetting('WebLayout', 'moduleUrl', BackendUtility::getModuleUrl('web_layout'));
        }
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
     * Returns the file name to the LLL JavaScript, containing the localized labels,
     * which can be used in JavaScript code.
     *
     * @return string File name of the JS file, relative to TYPO3_mainDir
     * @throws \RuntimeException
     */
    protected function getLocalLangFileName()
    {
        $code = $this->generateLocalLang();
        $filePath = 'typo3temp/assets/js/backend-' . sha1($code) . '.js';
        if (!file_exists(PATH_site . $filePath)) {
            // writeFileToTypo3tempDir() returns NULL on success (please double-read!)
            $error = GeneralUtility::writeFileToTypo3tempDir(PATH_site . $filePath, $code);
            if ($error !== null) {
                throw new \RuntimeException('Locallang JS file could not be written to ' . $filePath . '. Reason: ' . $error, 1295193026);
            }
        }
        return '../' . $filePath;
    }

    /**
     * Reads labels required in JavaScript code from the localization system and returns them as JSON
     * array in TYPO3.LLL.
     *
     * @return string JavaScript code containing the LLL labels in TYPO3.LLL
     */
    protected function generateLocalLang()
    {
        $lang = $this->getLanguageService();
        $coreLabels = [
            'waitTitle' => $lang->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:mess.refresh_login_logging_in'),
            'refresh_login_failed' => $lang->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:mess.refresh_login_failed'),
            'refresh_login_failed_message' => $lang->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:mess.refresh_login_failed_message'),
            'refresh_login_title' => $lang->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:mess.refresh_login_title'),
            'login_expired' => $lang->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:mess.login_expired'),
            'refresh_login_username' => $lang->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:mess.refresh_login_username'),
            'refresh_login_password' => $lang->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:mess.refresh_login_password'),
            'refresh_login_emptyPassword' => $lang->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:mess.refresh_login_emptyPassword'),
            'refresh_login_button' => $lang->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:mess.refresh_login_button'),
            'refresh_exit_button' => $lang->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:mess.refresh_exit_button'),
            'please_wait' => $lang->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:mess.please_wait'),
            'be_locked' => $lang->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:mess.be_locked'),
            'login_about_to_expire' => $lang->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:mess.login_about_to_expire'),
            'login_about_to_expire_title' => $lang->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:mess.login_about_to_expire_title'),
            'refresh_login_logout_button' => $lang->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:mess.refresh_login_logout_button'),
            'refresh_login_refresh_button' => $lang->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:mess.refresh_login_refresh_button'),
            'csh_tooltip_loading' => $lang->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:csh_tooltip_loading')
        ];
        $labels = [
            'fileUpload' => [
                'windowTitle',
                'buttonSelectFiles',
                'buttonCancelAll',
                'infoComponentMaxFileSize',
                'infoComponentFileUploadLimit',
                'infoComponentFileTypeLimit',
                'infoComponentOverrideFiles',
                'processRunning',
                'uploadWait',
                'uploadStarting',
                'uploadProgress',
                'uploadSuccess',
                'errorQueueLimitExceeded',
                'errorQueueFileSizeLimit',
                'errorQueueZeroByteFile',
                'errorQueueInvalidFiletype',
                'errorUploadHttp',
                'errorUploadMissingUrl',
                'errorUploadIO',
                'errorUploadSecurityError',
                'errorUploadLimit',
                'errorUploadFailed',
                'errorUploadFileIDNotFound',
                'errorUploadFileValidation',
                'errorUploadFileCancelled',
                'errorUploadStopped',
                'allErrorMessageTitle',
                'allErrorMessageText',
                'allError401',
                'allError2038'
            ],
            'liveSearch' => [
                'title',
                'helpTitle',
                'emptyText',
                'loadingText',
                'listEmptyText',
                'showAllResults',
                'helpDescription',
                'helpDescriptionPages',
                'helpDescriptionContent'
            ],
            'viewPort' => [
                'tooltipModuleMenuSplit',
                'tooltipNavigationContainerSplitDrag',
                'tooltipNavigationContainerSplitClick',
                'tooltipDebugPanelSplitDrag'
            ]
        ];
        $generatedLabels = [];
        $generatedLabels['core'] = $coreLabels;
        // First loop over all categories (fileUpload, liveSearch, ..)
        foreach ($labels as $categoryName => $categoryLabels) {
            // Then loop over every single label
            foreach ($categoryLabels as $label) {
                // LLL identifier must be called $categoryName_$label, e.g. liveSearch_loadingText
                $generatedLabels[$categoryName][$label] = $this->getLanguageService()->getLL($categoryName . '_' . $label);
            }
        }
        return 'TYPO3.LLL = ' . json_encode($generatedLabels) . ';';
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
        $newPageModule = trim($beUser->getTSConfigVal('options.overridePageModule'));
        $pageModule = BackendUtility::isModuleSetInTBE_MODULES($newPageModule) ? $newPageModule : 'web_layout';
        if (!$beUser->check('modules', $pageModule)) {
            $pageModule = '';
        }
        $t3Configuration = [
            'username' => htmlspecialchars($beUser->user['username']),
            'uniqueID' => GeneralUtility::shortMD5(uniqid('', true)),
            'pageModule' => $pageModule,
            'inWorkspace' => $beUser->workspace !== 0,
            'showRefreshLoginPopup' => isset($GLOBALS['TYPO3_CONF_VARS']['BE']['showRefreshLoginPopup']) ? (int)$GLOBALS['TYPO3_CONF_VARS']['BE']['showRefreshLoginPopup'] : false
        ];
        $this->js .= '
	TYPO3.configuration = ' . json_encode($t3Configuration) . ';

	/**
	 * TypoSetup object.
	 */
	function typoSetup() {	//
		this.username = TYPO3.configuration.username;
		this.uniqueID = TYPO3.configuration.uniqueID;
	}
	var TS = new typoSetup();
		//backwards compatibility
	/**
	 * Frameset Module object
	 *
	 * Used in main modules with a frameset for submodules to keep the ID between modules
	 * Typically that is set by something like this in a Web>* sub module:
	 *		if (top.fsMod) top.fsMod.recentIds["web"] = "\'.(int)$this->id.\'";
	 * 		if (top.fsMod) top.fsMod.recentIds["file"] = "...(file reference/string)...";
	 */
	function fsModules() {	//
		this.recentIds=new Array();					// used by frameset modules to track the most recent used id for list frame.
		this.navFrameHighlightedID=new Array();		// used by navigation frames to track which row id was highlighted last time
		this.currentBank="0";
	}
	var fsMod = new fsModules();

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
        // EDIT page:
        $editId = preg_replace('/[^[:alnum:]_]/', '', GeneralUtility::_GET('edit'));
        if ($editId) {
            // Looking up the page to edit, checking permissions:
            $where = ' AND (' . $beUser->getPagePermsClause(2) . ' OR ' . $beUser->getPagePermsClause(16) . ')';
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
            if (is_array($editRecord) && $beUser->isInWebMount($editRecord['uid'])) {
                // Setting JS code to open editing:
                $this->js .= '
		// Load page to edit:
	window.setTimeout("top.loadEditId(' . (int)$editRecord['uid'] . ');", 500);
			';
                // Checking page edit parameter:
                if (!$beUser->getTSConfigVal('options.bookmark_onEditId_dontSetPageTree')) {
                    $bookmarkKeepExpanded = $beUser->getTSConfigVal('options.bookmark_onEditId_keepExistingExpanded');
                    // Expanding page tree:
                    BackendUtility::openPageTree((int)$editRecord['pid'], !$bookmarkKeepExpanded);
                }
            } else {
                $this->js .= '
            // Warning about page editing:
            require(["TYPO3/CMS/Backend/Modal", "TYPO3/CMS/Backend/Severity"], function(Modal, Severity) {
                Modal.show("", ' . GeneralUtility::quoteJSvalue(sprintf($this->getLanguageService()->getLL('noEditPage'), $editId)) . ', Severity.notice, [{
                    text: ' . GeneralUtility::quoteJSvalue($this->getLanguageService()->sL('LLL:EXT:lang/Resources/Private/Language/locallang_common.xlf:close')) . ',
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
     * Adds a javascript snippet to the backend
     *
     * @param string $javascript Javascript snippet
     * @throws \InvalidArgumentException
     * @deprecated since TYPO3 v8, will be removed in TYPO3 v9. Use the "constructPostProcess" hook within BackendController instead.
     */
    public function addJavascript($javascript)
    {
        GeneralUtility::logDeprecatedFunction();
        // @todo do we need more checks?
        if (!is_string($javascript)) {
            throw new \InvalidArgumentException('parameter $javascript must be of type string', 1195129553);
        }
        $this->js .= $javascript;
    }

    /**
     * Adds a javscript file to the backend after it has been checked that it exists
     *
     * @param string $javascriptFile Javascript file reference
     * @return bool TRUE if the javascript file was successfully added, FALSE otherwise
     * @deprecated since TYPO3 v8, will be removed in TYPO3 v9. Use the "constructPostProcess" hook within BackendController instead.
     */
    public function addJavascriptFile($javascriptFile)
    {
        GeneralUtility::logDeprecatedFunction();
        $jsFileAdded = false;
        // @todo add more checks if necessary
        if (file_exists(GeneralUtility::resolveBackPath(PATH_typo3 . $javascriptFile))) {
            $this->jsFiles[] = $javascriptFile;
            $jsFileAdded = true;
        }
        return $jsFileAdded;
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
     * Adds a css file to the backend after it has been checked that it exists
     *
     * @param string $cssFileName The css file's name with out the .css ending
     * @param string $cssFile Css file reference
     * @return bool TRUE if the css file was added, FALSE otherwise
     * @deprecated since TYPO3 v8, will be removed in TYPO3 v9, use the according PageRenderer methods directly
     */
    public function addCssFile($cssFileName, $cssFile)
    {
        GeneralUtility::logDeprecatedFunction();
        $cssFileAdded = false;
        if (empty($this->cssFiles[$cssFileName])) {
            $this->cssFiles[$cssFileName] = $cssFile;
            $cssFileAdded = true;
        }
        return $cssFileAdded;
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
        if (isset($options[$identifier]) && is_array($options[$identifier])) {
            foreach ($options[$identifier] as $hookFunction) {
                GeneralUtility::callUserFunction($hookFunction, $hookConfiguration, $this);
            }
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

    /**
     * Returns the Module menu for the AJAX request
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function getModuleMenu(ServerRequestInterface $request, ResponseInterface $response)
    {
        $content = $this->generateModuleMenu();

        $response->getBody()->write(json_encode(['menu' => $content]));
        return $response;
    }

    /**
     * Returns the toolbar for the AJAX request
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function getTopbar(ServerRequestInterface $request, ResponseInterface $response)
    {
        $response->getBody()->write(json_encode(['topbar' => $this->renderTopbar()]));
        return $response;
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
