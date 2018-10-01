<?php
namespace TYPO3\CMS\Info\Controller;

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
use TYPO3\CMS\Backend\Template\DocumentTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Compatibility\PublicMethodDeprecationTrait;
use TYPO3\CMS\Core\Compatibility\PublicPropertyDeprecationTrait;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * Script Class for the Web > Info module
 * This class creates the framework to which other extensions can connect their sub-modules
 * @internal This class is a specific Backend controller implementation and is not part of the TYPO3's Core API.
 */
class InfoModuleController
{
    use PublicPropertyDeprecationTrait;
    use PublicMethodDeprecationTrait;

    /**
     * @var array
     */
    private $deprecatedPublicProperties = [
        'perms_clause' => 'Using InfoModuleController::$perms_clause is deprecated and will not be possible anymore in TYPO3 v10.0.',
        'modTSconfig' => 'Using InfoModuleController::$modTSconfig is deprecated and will not be possible anymore in TYPO3 v10.0.',
        'modMenu_setDefaultList' => 'Using InfoModuleController::$modMenu_setDefaultList is deprecated and will not be possible anymore in TYPO3 v10.0.',
        'modMenu_dontValidateList' => 'Using InfoModuleController::$modMenu_dontValidateList is deprecated and will not be possible anymore in TYPO3 v10.0.',
        'modMenu_type' => 'Using InfoModuleController::$modMenu_type$ is deprecated and will not be possible anymore in TYPO3 v10.0.',
        'extClassConf' => 'Using InfoModuleController::extClassConf$ is deprecated and will not be possible anymore in TYPO3 v10.0.',
        'extObj' => 'Using InfoModuleController::$extObj is deprecated and will not be possible anymore in TYPO3 v10.0.',
        'content' => 'Using InfoModuleController::$content is deprecated and will not be possible anymore in TYPO3 v10.0.',
        'pObj' => 'Using InfoModuleController::$pObj is deprecated and will not be possible anymore in TYPO3 v10.0.',
        'id' => 'Using InfoModuleController::id$ is deprecated and will not be possible anymore in TYPO3 v10.0.',
        'CMD' => 'Using InfoModuleController::$CMD is deprecated, property will be removed in TYPO3 v10.0.',
        'doc' => 'Using InfoModuleController::$doc is deprecated, property will be removed in TYPO3 v10.0.',
        'MCONF' => 'Using InfoModuleController::$MCONF is deprecated, property will be removed in TYPO3 v10.0.',
    ];

    /**
     * @var array
     */
    private $deprecatedPublicMethods = [
        'main' => 'Using InfoModuleController::main() is deprecated and will not be possible anymore in TYPO3 v10.0.',
        'init' => 'Using InfoModuleController::init() is deprecated and will not be possible anymore in TYPO3 v10.0.',
        'getModuleTemplate' => 'Using InfoModuleController::getModuleTemplate() is deprecated and will not be possible anymore in TYPO3 v10.0.',
        'menuConfig' => 'Using InfoModuleController::menuConfig() is deprecated and will not be possible anymore in TYPO3 v10.0.',
        'handleExternalFunctionValue' => 'Using InfoModuleController::handleExternalFunctionValue() is deprecated and will not be possible anymore in TYPO3 v10.0.',
        'mergeExternalItems' => 'Using InfoModuleController::mergeExternalItems() is deprecated and will not be possible anymore in TYPO3 v10.0.',
        'getExternalItemConfig' => 'Using InfoModuleController::getExternalItemConfig() is deprecated and will not be possible anymore in TYPO3 v10.0.',
        'extObjContent' => 'Using InfoModuleController::extObjContent() is deprecated and will not be possible anymore in TYPO3 v10.0.',
        'getExtObjContent' => 'Using InfoModuleController::getExtObjContent() is deprecated and will not be possible anymore in TYPO3 v10.0.',
        'checkExtObj' => 'Using InfoModuleController::checkExtObj() is deprecated and will not be possible anymore in TYPO3 v10.0.',
        'extObjHeader' => 'Using InfoModuleController::extObjHeader() is deprecated, method will be removed in TYPO3 v10.0.',
        'checkSubExtObj' => 'Using InfoModuleController::checkSubExtObj() is deprecated, method will be removed in TYPO3 v10.0.',
    ];

    /**
     * @var array Used by client classes.
     */
    public $pageinfo;

    /**
     * The name of the module
     *
     * @var string
     */
    protected $moduleName = 'web_info';

    /**
     * ModuleTemplate Container
     *
     * @var ModuleTemplate
     */
    protected $moduleTemplate;

    /**
     * @var StandaloneView
     */
    protected $view;

    /**
     * Loaded with the global array $MCONF which holds some module configuration from the conf.php file of backend modules.
     *
     * @var array
     */
    protected $MCONF = [];

    /**
     * @var int Value of the GET/POST var 'id'
     */
    protected $id;

    /**
     * The value of GET/POST var, 'CMD'
     *
     * @var mixed
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0.
     */
    protected $CMD;

    /**
     * A WHERE clause for selection records from the pages table based on read-permissions of the current backend user.
     *
     * @var string
     */
    protected $perms_clause;

    /**
     * The module menu items array. Each key represents a key for which values can range between the items in the array of that key.
     * Written by client classes.
     *
     * @var array
     */
    public $MOD_MENU = [
        'function' => []
    ];

    /**
     * Current settings for the keys of the MOD_MENU array
     * Written by client classes.
     *
     * @var array
     */
    public $MOD_SETTINGS = [];

    /**
     * Module TSconfig based on PAGE TSconfig / USER TSconfig
     *
     * @var array
     */
    protected $modTSconfig;

    /**
     * If type is 'ses' then the data is stored as session-lasting data. This means that it'll be wiped out the next time the user logs in.
     * Can be set from extension classes of this class before the init() function is called.
     *
     * @var string
     */
    protected $modMenu_type = '';

    /**
     * dontValidateList can be used to list variables that should not be checked if their value is found in the MOD_MENU array. Used for dynamically generated menus.
     * Can be set from extension classes of this class before the init() function is called.
     *
     * @var string
     */
    protected $modMenu_dontValidateList = '';

    /**
     * List of default values from $MOD_MENU to set in the output array (only if the values from MOD_MENU are not arrays)
     * Can be set from extension classes of this class before the init() function is called.
     *
     * @var string
     */
    protected $modMenu_setDefaultList = '';

    /**
     * @var array Contains module configuration parts from TBE_MODULES_EXT if found
     */
    protected $extClassConf;

    /**
     * Generally used for accumulating the output content of backend modules
     *
     * @var string
     */
    protected $content = '';

    /**
     * @var DocumentTemplate
     */
    protected $doc;

    /**
     * May contain an instance of a 'Function menu module' which connects to this backend module.
     *
     * @var \object
     */
    protected $extObj;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->moduleTemplate = GeneralUtility::makeInstance(ModuleTemplate::class);
        $languageService = $this->getLanguageService();
        $languageService->includeLLFile('EXT:info/Resources/Private/Language/locallang_mod_web_info.xlf');

        // @deprecated and will be removed in TYPO3 v10.0.
        $this->MCONF = [
            'name' => $this->moduleName,
        ];
    }

    /**
     * Initializes the backend module by setting internal variables, initializing the menu.
     */
    protected function init()
    {
        $this->id = (int)GeneralUtility::_GP('id');
        // @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0.
        $this->CMD = GeneralUtility::_GP('CMD');
        $this->perms_clause = $this->getBackendUser()->getPagePermsClause(Permission::PAGE_SHOW);
        $this->menuConfig();
        $this->handleExternalFunctionValue();
    }

    /**
     * Initialize module header etc and call extObjContent function
     */
    protected function main()
    {
        // since TYPO3 v9, will be removed in TYPO3 v10.0.
        $this->doc = GeneralUtility::makeInstance(DocumentTemplate::class);

        $languageService = $this->getLanguageService();
        $backendUser = $this->getBackendUser();

        // The page will show only if there is a valid page and if this page
        // may be viewed by the user
        $this->pageinfo = BackendUtility::readPageAccess($this->id, $this->perms_clause);
        if ($this->pageinfo) {
            $this->moduleTemplate->getDocHeaderComponent()->setMetaInformation($this->pageinfo);
        }
        $access = is_array($this->pageinfo);
        if ($this->id && $access || $backendUser->isAdmin() && !$this->id) {
            if ($backendUser->isAdmin() && !$this->id) {
                $this->pageinfo = ['title' => '[root-level]', 'uid' => 0, 'pid' => 0];
            }
            // JavaScript
            $this->moduleTemplate->addJavaScriptCode(
                'WebFuncInLineJS',
                'if (top.fsMod) top.fsMod.recentIds["web"] = ' . (int)$this->id . ';
				function jumpToUrl(URL) {
					window.location.href = URL;
					return false;
				}
				'
            );
            // Setting up the context sensitive menu:
            $this->moduleTemplate->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Backend/ContextMenu');

            $this->view = $this->getFluidTemplateObject();
            $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
            $this->view->assign('moduleName', (string)$uriBuilder->buildUriFromRoute($this->moduleName));
            $this->view->assign('functionMenuModuleContent', $this->getExtObjContent());
            // Setting up the buttons and markers for doc header
            $this->getButtons();
            $this->generateMenu();
            $this->content .= $this->view->render();
        } else {
            // If no access or if ID == zero
            $this->content = $this->moduleTemplate->header($languageService->getLL('title'));
        }
    }

    /**
     * Injects the request object for the current request or subrequest
     * Then checks for module functions that have hooked in, and renders menu etc.
     *
     * @param ServerRequestInterface $request the current request
     * @return ResponseInterface the response with the content
     */
    public function mainAction(ServerRequestInterface $request): ResponseInterface
    {
        // @deprecated and will be removed in TYPO3 v10.0.
        $GLOBALS['SOBE'] = $this;

        $this->init();

        // Checking for first level external objects
        $this->checkExtObj();

        // Checking second level external objects
        // @deprecated and will be removed in TYPO3 v10.0.
        $this->checkSubExtObj();
        $this->main();

        $this->moduleTemplate->setContent($this->content);
        return new HtmlResponse($this->moduleTemplate->renderContent());
    }

    /**
     * Create the panel of buttons for submitting the form or otherwise perform operations.
     */
    protected function getButtons()
    {
        $languageService = $this->getLanguageService();
        $buttonBar = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar();
        // View page
        $viewButton = $buttonBar->makeLinkButton()
            ->setHref('#')
            ->setOnClick(BackendUtility::viewOnClick(
                $this->pageinfo['uid'],
                '',
                BackendUtility::BEgetRootLine($this->pageinfo['uid'])
            ))
            ->setTitle($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.showPage'))
            ->setIcon($this->moduleTemplate->getIconFactory()->getIcon('actions-view-page', Icon::SIZE_SMALL));
        $buttonBar->addButton($viewButton, ButtonBar::BUTTON_POSITION_LEFT, 1);
        // Shortcut
        $shortCutButton = $buttonBar->makeShortcutButton()
            ->setModuleName($this->moduleName)
            ->setDisplayName($this->MOD_MENU['function'][$this->MOD_SETTINGS['function']])
            ->setGetVariables([
                'route',
                'id',
                'edit_record',
                'pointer',
                'new_unique_uid',
                'search_field',
                'search_levels',
                'showLimit'
            ])
            ->setSetVariables(array_keys($this->MOD_MENU));
        $buttonBar->addButton($shortCutButton, ButtonBar::BUTTON_POSITION_RIGHT);

        // CSH
        $cshButton = $buttonBar->makeHelpButton()
            ->setModuleName('xMOD_csh_corebe')
            ->setFieldName('pagetree_overview');
        $buttonBar->addButton($cshButton);
    }

    /**
     * Generate the ModuleMenu
     */
    protected function generateMenu()
    {
        $menu = $this->moduleTemplate->getDocHeaderComponent()->getMenuRegistry()->makeMenu();
        $menu->setIdentifier('WebInfoJumpMenu');
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        foreach ($this->MOD_MENU['function'] as $controller => $title) {
            $item = $menu
                ->makeMenuItem()
                ->setHref(
                    (string)$uriBuilder->buildUriFromRoute(
                        $this->moduleName,
                        [
                            'id' => $this->id,
                            'SET' => [
                                'function' => $controller
                            ]
                        ]
                    )
                )
                ->setTitle($title);
            if ($controller === $this->MOD_SETTINGS['function']) {
                $item->setActive(true);
            }
            $menu->addMenuItem($item);
        }
        $this->moduleTemplate->getDocHeaderComponent()->getMenuRegistry()->addMenu($menu);
    }

    /**
     * Returns the ModuleTemplate container
     * This is used by PageLayoutView.php
     *
     * @return ModuleTemplate
     */
    protected function getModuleTemplate()
    {
        return $this->moduleTemplate;
    }

    /**
     * returns a new standalone view, shorthand function
     *
     * @return StandaloneView
     */
    protected function getFluidTemplateObject()
    {
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setLayoutRootPaths([GeneralUtility::getFileAbsFileName('EXT:info/Resources/Private/Layouts')]);
        $view->setPartialRootPaths([GeneralUtility::getFileAbsFileName('EXT:info/Resources/Private/Partials')]);
        $view->setTemplateRootPaths([GeneralUtility::getFileAbsFileName('EXT:info/Resources/Private/Templates')]);

        $view->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName('EXT:info/Resources/Private/Templates/Main.html'));

        $view->getRequest()->setControllerExtensionName('info');
        return $view;
    }

    /**
     * Initializes the internal MOD_MENU array setting and unsetting items based on various conditions. It also merges in external menu items from the global array TBE_MODULES_EXT (see mergeExternalItems())
     * Then MOD_SETTINGS array is cleaned up (see \TYPO3\CMS\Backend\Utility\BackendUtility::getModuleData()) so it contains only valid values. It's also updated with any SET[] values submitted.
     * Also loads the modTSconfig internal variable.
     */
    protected function menuConfig()
    {
        // Page / user TSconfig settings and blinding of menu-items
        $this->modTSconfig['properties'] = BackendUtility::getPagesTSconfig($this->id)['mod.']['web_info.'] ?? [];
        $this->MOD_MENU['function'] = $this->mergeExternalItems('web_info', 'function', $this->MOD_MENU['function']);
        $blindActions = $this->modTSconfig['properties']['menu.']['function.'] ?? [];
        foreach ($blindActions as $key => $value) {
            if (!$value && array_key_exists($key, $this->MOD_MENU['function'])) {
                unset($this->MOD_MENU['function'][$key]);
            }
        }
        $this->MOD_SETTINGS = BackendUtility::getModuleData($this->MOD_MENU, GeneralUtility::_GP('SET'), 'web_info', $this->modMenu_type, $this->modMenu_dontValidateList, $this->modMenu_setDefaultList);
    }

    /**
     * Merges menu items from global array $TBE_MODULES_EXT
     *
     * @param string $modName Module name for which to find value
     * @param string $menuKey Menu key, eg. 'function' for the function menu.
     * @param array $menuArr The part of a MOD_MENU array to work on.
     * @return array Modified array part.
     * @internal
     */
    protected function mergeExternalItems($modName, $menuKey, $menuArr)
    {
        $mergeArray = $GLOBALS['TBE_MODULES_EXT'][$modName]['MOD_MENU'][$menuKey];
        if (is_array($mergeArray)) {
            $backendUser = $this->getBackendUser();
            foreach ($mergeArray as $k => $v) {
                if (((string)$v['ws'] === '' || $backendUser->workspace === 0 && GeneralUtility::inList($v['ws'], 'online'))
                    || $backendUser->workspace === -1 && GeneralUtility::inList($v['ws'], 'offline')
                    || $backendUser->workspace > 0 && GeneralUtility::inList($v['ws'], 'custom')
                ) {
                    $menuArr[$k] = $this->getLanguageService()->sL($v['title']);
                }
            }
        }
        return $menuArr;
    }

    /**
     * Loads $this->extClassConf with the configuration for the CURRENT function of the menu.
     *
     * @param string $MM_key The key to MOD_MENU for which to fetch configuration. 'function' is default since it is first and foremost used to get information per "extension object" (I think that is what its called)
     * @param string $MS_value The value-key to fetch from the config array. If NULL (default) MOD_SETTINGS[$MM_key] will be used. This is useful if you want to force another function than the one defined in MOD_SETTINGS[function]. Call this in init() function of your Script Class: handleExternalFunctionValue('function', $forcedSubModKey)
     */
    protected function handleExternalFunctionValue($MM_key = 'function', $MS_value = null)
    {
        if ($MS_value === null) {
            $MS_value = $this->MOD_SETTINGS[$MM_key];
        }
        $this->extClassConf = $this->getExternalItemConfig('web_info', $MM_key, $MS_value);
    }

    /**
     * Returns configuration values from the global variable $TBE_MODULES_EXT for the module given.
     * For example if the module is named "web_info" and the "function" key ($menuKey) of MOD_SETTINGS is "stat" ($value) then you will have the values
     * of $TBE_MODULES_EXT['webinfo']['MOD_MENU']['function']['stat'] returned.
     *
     * @param string $modName Module name
     * @param string $menuKey Menu key, eg. "function" for the function menu. See $this->MOD_MENU
     * @param string $value Optionally the value-key to fetch from the array that would otherwise have been returned if this value was not set. Look source...
     * @return mixed The value from the TBE_MODULES_EXT array.
     */
    protected function getExternalItemConfig($modName, $menuKey, $value = '')
    {
        if (isset($GLOBALS['TBE_MODULES_EXT'][$modName])) {
            return (string)$value !== ''
                ? $GLOBALS['TBE_MODULES_EXT'][$modName]['MOD_MENU'][$menuKey][$value]
                : $GLOBALS['TBE_MODULES_EXT'][$modName]['MOD_MENU'][$menuKey];
        }
        return null;
    }

    /**
     * Creates an instance of the class found in $this->extClassConf['name'] in $this->extObj if any (this should hold three keys, "name", "path" and "title" if a "Function menu module" tries to connect...)
     * This value in extClassConf might be set by an extension (in an ext_tables/ext_localconf file) which thus "connects" to a module.
     * The array $this->extClassConf is set in handleExternalFunctionValue() based on the value of MOD_SETTINGS[function]
     * If an instance is created it is initiated with $this passed as value and $this->extClassConf as second argument. Further the $this->MOD_SETTING is cleaned up again after calling the init function.
     */
    protected function checkExtObj()
    {
        if (is_array($this->extClassConf) && $this->extClassConf['name']) {
            $this->extObj = GeneralUtility::makeInstance($this->extClassConf['name']);
            if (is_callable([$this->extObj, 'init'])) {
                $this->extObj->init($this);
            }
            // Re-write:
            $this->MOD_SETTINGS = BackendUtility::getModuleData($this->MOD_MENU, GeneralUtility::_GP('SET'), 'web_info', $this->modMenu_type, $this->modMenu_dontValidateList, $this->modMenu_setDefaultList);
        }
    }

    /**
     * Calls the checkExtObj function in sub module if present.
     *
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0.
     */
    protected function checkSubExtObj()
    {
        if (is_object($this->extObj) && is_callable([$this->extObj, 'checkExtObj'])) {
            $this->extObj->checkExtObj();
        }
    }

    /**
     * Calls the 'header' function inside the "Function menu module" if present.
     * A header function might be needed to add JavaScript or other stuff in the head. This can't be done in the main function because the head is already written.
     *
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0.
     */
    protected function extObjHeader()
    {
        if (is_callable([$this->extObj, 'head'])) {
            $this->extObj->head();
        }
    }

    /**
     * Calls the 'main' function inside the "Function menu module" if present
     */
    protected function extObjContent()
    {
        if ($this->extObj === null) {
            $languageService = $this->getLanguageService();
            $flashMessage = GeneralUtility::makeInstance(
                FlashMessage::class,
                $languageService->sL('LLL:EXT:backend/Resources/Private/Language/locallang.xlf:no_modules_registered'),
                $languageService->getLL('title'),
                FlashMessage::ERROR
            );
            $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
            /** @var \TYPO3\CMS\Core\Messaging\FlashMessageQueue $defaultFlashMessageQueue */
            $defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
            $defaultFlashMessageQueue->enqueue($flashMessage);
        } else {
            if (is_callable([$this->extObj, 'main'])) {
                $main = $this->extObj->main();
                if ($main instanceof ResponseInterface) {
                    $stream = $main->getBody();
                    $stream->rewind();
                    $main = $stream->getContents();
                }
                $this->content .= $main;
            }
        }
    }

    /**
     * Return the content of the 'main' function inside the "Function menu module" if present
     *
     * @return string
     */
    protected function getExtObjContent()
    {
        $savedContent = $this->content;
        $this->content = '';
        $this->extObjContent();
        $newContent = $this->content;
        $this->content = $savedContent;
        return $newContent;
    }

    /**
     * @return LanguageService
     */
    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }

    /**
     * @return BackendUserAuthentication
     */
    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
