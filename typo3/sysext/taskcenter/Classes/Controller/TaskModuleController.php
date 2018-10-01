<?php
namespace TYPO3\CMS\Taskcenter\Controller;

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
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Compatibility\PublicMethodDeprecationTrait;
use TYPO3\CMS\Core\Compatibility\PublicPropertyDeprecationTrait;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Taskcenter\TaskInterface;

/**
 * This class provides a task center for BE users
 * @internal This is a specific Backend Controller implementation and is not considered part of the Public TYPO3 API.
 */
class TaskModuleController
{
    use PublicPropertyDeprecationTrait;
    use PublicMethodDeprecationTrait;

    /**
     * @var array
     */
    private $deprecatedPublicProperties = [
        'MCONF' => 'Using TaskModuleController::$MCONF is deprecated and will not be possible anymore in TYPO3 v10.0.',
        'id' => 'Using TaskModuleController::$id is deprecated and will not be possible anymore in TYPO3 v10.0.',
        'MOD_MENU' => 'Using TaskModuleController::$MOD_MENU is deprecated and will not be possible anymore in TYPO3 v10.0.',
        'modMenu_type' => 'Using TaskModuleController::$modMenu_type is deprecated and will not be possible anymore in TYPO3 v10.0.',
        'modMenu_setDefaultList' => 'Using TaskModuleController::$$modMenu_setDefaultList is deprecated and will not be possible anymore in TYPO3 v10.0.',
        'modMenu_dontValidateList' => 'Using TaskModuleController::$modMenu_dontValidateList is deprecated and will not be possible anymore in TYPO3 v10.0.',
        'content' => 'Using TaskModuleController::$content is deprecated and will not be possible anymore in TYPO3 v10.0.',
        'perms_clause' => 'Using TaskModuleController::$perms_clause is deprecated, the property will be removed in TYPO3 v10.0.',
        'CMD' => 'Using TaskModuleController::$CMD is deprecated, the property will be removed in TYPO3 v10.0.',
        'extClassConf' => 'Using TaskModuleController::$extClassConf is deprecated, the property will be removed in TYPO3 v10.0.',
        'extObj' => 'Using TaskModuleController::$extObj is deprecated, the property will be removed in TYPO3 v10.0.',
    ];

    /**
     * @var array
     */
    private $deprecatedPublicMethods = [
        'menuConfig' => 'Using TaskModuleController::menuConfig() is deprecated and will not be possible anymore in TYPO3 v10.0.',
        'mergeExternalItems' => 'Using TaskModuleController::mergeExternalItems() is deprecated and will not be possible anymore in TYPO3 v10.0.',
        'handleExternalFunctionValue' => 'Using TaskModuleController::handleExternalFunctionValue() is deprecated and will not be possible anymore in TYPO3 v10.0.',
        'getExternalItemConfig' => 'Using TaskModuleController::getExternalItemConfig() is deprecated and will not be possible anymore in TYPO3 v10.0.',
        'main' => 'Using TaskModuleController::main() is deprecated and will not be possible anymore in TYPO3 v10.0.',
        'urlInIframe' => 'Using TaskModuleController::urlInIframe() is deprecated. The method will be removed in TYPO3 v10.0.',
        'extObjHeader' => 'Using TaskModuleController::extObjHeader() is deprecated. The method will be removed in TYPO3 v10.0.',
        'checkSubExtObj' => 'Using TaskModuleController::checkSubExtObj() is deprecated. The method will be removed in TYPO3 v10.0.',
        'checkExtObj' => 'Using TaskModuleController::checkExtObj() is deprecated. The method will be removed in TYPO3 v10.0.',
        'extObjContent' => 'Using TaskModuleController::extObjContent() is deprecated. The method will be removed in TYPO3 v10.0.',
        'getExtObjContent' => 'Using TaskModuleController::getExtObjContent() is deprecated. The method will be removed in TYPO3 v10.0.',
    ];

    /**
     * Loaded with the global array $MCONF which holds some module configuration from the conf.php file of backend modules.
     *
     * @see init()
     * @var array
     */
    protected $MCONF = [];

    /**
     * The integer value of the GET/POST var, 'id'. Used for submodules to the 'Web' module (page id)
     *
     * @see init()
     * @var int
     */
    protected $id;

    /**
     * The value of GET/POST var, 'CMD'
     *
     * @see init()
     * @var mixed
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0.
     */
    protected $CMD;

    /**
     * A WHERE clause for selection records from the pages table based on read-permissions of the current backend user.
     *
     * @see init()
     * @var string
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0.
     */
    protected $perms_clause;

    /**
     * The module menu items array. Each key represents a key for which values can range between the items in the array of that key.
     *
     * @see init()
     * @var array
     */
    protected $MOD_MENU = [
        'function' => []
    ];

    /**
     * Current settings for the keys of the MOD_MENU array
     * Public since task objects use this.
     *
     * @see $MOD_MENU
     * @var array
     */
    public $MOD_SETTINGS = [];

    /**
     * Module TSconfig based on PAGE TSconfig / USER TSconfig
     * Public since task objects use this.
     *
     * @see menuConfig()
     * @var array
     */
    public $modTSconfig;

    /**
     * If type is 'ses' then the data is stored as session-lasting data. This means that it'll be wiped out the next time the user logs in.
     * Can be set from extension classes of this class before the init() function is called.
     *
     * @see menuConfig(), \TYPO3\CMS\Backend\Utility\BackendUtility::getModuleData()
     * @var string
     */
    protected $modMenu_type = '';

    /**
     * dontValidateList can be used to list variables that should not be checked if their value is found in the MOD_MENU array. Used for dynamically generated menus.
     * Can be set from extension classes of this class before the init() function is called.
     *
     * @see menuConfig(), \TYPO3\CMS\Backend\Utility\BackendUtility::getModuleData()
     * @var string
     */
    protected $modMenu_dontValidateList = '';

    /**
     * List of default values from $MOD_MENU to set in the output array (only if the values from MOD_MENU are not arrays)
     * Can be set from extension classes of this class before the init() function is called.
     *
     * @see menuConfig(), \TYPO3\CMS\Backend\Utility\BackendUtility::getModuleData()
     * @var string
     */
    protected $modMenu_setDefaultList = '';

    /**
     * Contains module configuration parts from TBE_MODULES_EXT if found
     *
     * @see handleExternalFunctionValue()
     * @var array
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0.
     */
    protected $extClassConf;

    /**
     * Generally used for accumulating the output content of backend modules
     *
     * @var string
     */
    protected $content = '';

    /**
     * May contain an instance of a 'Function menu module' which connects to this backend module.
     *
     * @see checkExtObj()
     * @var \object
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0.
     */
    protected $extObj;

    /**
     * @var array
     */
    protected $pageinfo;

    /**
     * ModuleTemplate Container
     *
     * @var ModuleTemplate
     */
    protected $moduleTemplate;

    /**
     * The name of the module
     *
     * @var string
     */
    protected $moduleName = 'user_task';

    /**
     * Initializes the Module
     */
    public function __construct()
    {
        $this->moduleTemplate = GeneralUtility::makeInstance(ModuleTemplate::class);
        $this->getLanguageService()->includeLLFile('EXT:taskcenter/Resources/Private/Language/locallang_task.xlf');
        $this->MCONF = [
            'name' => $this->moduleName
        ];
        // Name might be set from outside
        if (!$this->MCONF['name']) {
            $this->MCONF = $GLOBALS['MCONF'];
        }
        $this->id = (int)GeneralUtility::_GP('id');
        // @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0.
        $this->CMD = GeneralUtility::_GP('CMD');
        // @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0.
        $this->perms_clause = $this->getBackendUser()->getPagePermsClause(Permission::PAGE_SHOW);
        $this->menuConfig();
        $this->handleExternalFunctionValue();
    }

    /**
     * Adds items to the ->MOD_MENU array. Used for the function menu selector.
     */
    protected function menuConfig()
    {
        $this->MOD_MENU = ['mode' => []];
        $languageService = $this->getLanguageService();
        $this->MOD_MENU['mode']['information'] = $languageService->sL('LLL:EXT:taskcenter/Resources/Private/Language/locallang.xlf:task_overview');
        $this->MOD_MENU['mode']['tasks'] = $languageService->sL('LLL:EXT:taskcenter/Resources/Private/Language/locallang.xlf:task_tasks');
        // Copied from parent::menuConfig, because parent is hardcoded to menu.function,
        // however menu.function is already used for the individual tasks. Therefore we use menu.mode here.
        // Page/be_user TSconfig settings and blinding of menu-items
        $this->modTSconfig['properties'] = BackendUtility::getPagesTSconfig($this->id)['mod.'][$this->moduleName . '.'] ?? [];
        $this->MOD_MENU['mode'] = $this->mergeExternalItems($this->MCONF['name'], 'mode', $this->MOD_MENU['mode']);
        $blindActions = $this->modTSconfig['properties']['menu.']['mode.'] ?? [];
        foreach ($blindActions as $key => $value) {
            if (!$value && array_key_exists($key, $this->MOD_MENU['mode'])) {
                unset($this->MOD_MENU['mode'][$key]);
            }
        }
        // Page / user TSconfig settings and blinding of menu-items
        // Now overwrite the stuff again for unknown reasons
        $this->modTSconfig['properties'] = BackendUtility::getPagesTSconfig($this->id)['mod.'][$this->MCONF['name'] . '.'] ?? [];
        $this->MOD_MENU['function'] = $this->mergeExternalItems($this->MCONF['name'], 'function', $this->MOD_MENU['function']);
        $blindActions = $this->modTSconfig['properties']['menu.']['function.'] ?? [];
        foreach ($blindActions as $key => $value) {
            if (!$value && array_key_exists($key, $this->MOD_MENU['function'])) {
                unset($this->MOD_MENU['function'][$key]);
            }
        }
        $this->MOD_SETTINGS = BackendUtility::getModuleData($this->MOD_MENU, GeneralUtility::_GP('SET'), $this->MCONF['name'], $this->modMenu_type, $this->modMenu_dontValidateList, $this->modMenu_setDefaultList);
    }

    /**
     * Generates the menu based on $this->MOD_MENU
     *
     * @throws \InvalidArgumentException
     */
    protected function generateMenu()
    {
        $menu = $this->moduleTemplate->getDocHeaderComponent()->getMenuRegistry()->makeMenu();
        $menu->setIdentifier('WebFuncJumpMenu');
        /** @var \TYPO3\CMS\Backend\Routing\UriBuilder $uriBuilder */
        $uriBuilder = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Routing\UriBuilder::class);
        foreach ($this->MOD_MENU['mode'] as $controller => $title) {
            $item = $menu
                ->makeMenuItem()
                ->setHref(
                    (string)$uriBuilder->buildUriFromRoute(
                        $this->moduleName,
                        [
                            'id' => $this->id,
                            'SET' => [
                                'mode' => $controller
                            ]
                        ]
                    )
                )
                ->setTitle($title);
            if ($controller === $this->MOD_SETTINGS['mode']) {
                $item->setActive(true);
            }
            $menu->addMenuItem($item);
        }
        $this->moduleTemplate->getDocHeaderComponent()->getMenuRegistry()->addMenu($menu);
    }

    /**
     * Injects the request object for the current request or subrequest
     * Simply calls main() and writes the content to the response
     *
     * @param ServerRequestInterface $request the current request
     * @return ResponseInterface the response with the content
     */
    public function mainAction(ServerRequestInterface $request): ResponseInterface
    {
        // @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0.
        $GLOBALS['SOBE'] = $this;

        $this->main();
        $this->moduleTemplate->setContent($this->content);
        return new HtmlResponse($this->moduleTemplate->renderContent());
    }

    /**
     * Creates the module's content. In this case it rather acts as a kind of #
     * dispatcher redirecting requests to specific tasks.
     */
    protected function main()
    {
        $this->getButtons();
        $this->generateMenu();
        $this->moduleTemplate->addJavaScriptCode(
            'TaskCenterInlineJavascript',
            'if (top.fsMod) { top.fsMod.recentIds["web"] = 0; }'
        );

        // Render content depending on the mode
        $mode = (string)$this->MOD_SETTINGS['mode'];
        if ($mode === 'information') {
            $this->renderInformationContent();
        } else {
            $this->renderModuleContent();
        }
        // Renders the module page
        $this->moduleTemplate->setTitle($this->getLanguageService()->getLL('title'));
    }

    /**
     * Generates the module content by calling the selected task
     */
    protected function renderModuleContent()
    {
        $languageService = $this->getLanguageService();
        $chosenTask = (string)$this->MOD_SETTINGS['function'];
        // Render the taskcenter task as default
        if (empty($chosenTask) || $chosenTask === 'index') {
            $chosenTask = 'taskcenter.tasks';
        }
        // Render the task
        $actionContent = '';
        $flashMessage = null;
        list($extKey, $taskClass) = explode('.', $chosenTask, 2);
        if (class_exists($taskClass)) {
            $taskInstance = GeneralUtility::makeInstance($taskClass, $this);
            if ($taskInstance instanceof TaskInterface) {
                // Check if the task is restricted to admins only
                if ($this->checkAccess($extKey, $taskClass)) {
                    $actionContent .= $taskInstance->getTask();
                } else {
                    $flashMessage = GeneralUtility::makeInstance(
                        FlashMessage::class,
                        $languageService->getLL('error-access'),
                        $languageService->getLL('error_header'),
                        FlashMessage::ERROR
                    );
                }
            } else {
                // Error if the task is not an instance of \TYPO3\CMS\Taskcenter\TaskInterface
                $flashMessage = GeneralUtility::makeInstance(
                    FlashMessage::class,
                    sprintf($languageService->getLL('error_no-instance'), $taskClass, TaskInterface::class),
                    $languageService->getLL('error_header'),
                    FlashMessage::ERROR
                );
            }
        } else {
            $flashMessage = GeneralUtility::makeInstance(
                FlashMessage::class,
                $languageService->sL('LLL:EXT:taskcenter/Resources/Private/Language/locallang_mod.xlf:mlang_labels_tabdescr'),
                $languageService->sL('LLL:EXT:taskcenter/Resources/Private/Language/locallang_mod.xlf:mlang_tabs_tab'),
                FlashMessage::INFO
            );
        }

        if ($flashMessage) {
            /** @var \TYPO3\CMS\Core\Messaging\FlashMessageService $flashMessageService */
            $flashMessageService = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Messaging\FlashMessageService::class);
            /** @var \TYPO3\CMS\Core\Messaging\FlashMessageQueue $defaultFlashMessageQueue */
            $defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
            $defaultFlashMessageQueue->enqueue($flashMessage);
        }

        $assigns = [];
        $assigns['reports'] = $this->indexAction();
        $assigns['taskClass'] = strtolower(str_replace('\\', '-', htmlspecialchars($extKey . '-' . $taskClass)));
        $assigns['actionContent'] = $actionContent;

        // Rendering of the output via fluid
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName(
            'EXT:taskcenter/Resources/Private/Templates/ModuleContent.html'
        ));
        $view->assignMultiple($assigns);
        $this->content .= $view->render();
    }

    /**
     * Generates the information content
     */
    protected function renderInformationContent()
    {
        $assigns = [];
        $assigns['LLPrefix'] = 'LLL:EXT:taskcenter/Resources/Private/Language/locallang.xlf:';
        $assigns['LLPrefixMod'] = 'LLL:EXT:taskcenter/Resources/Private/Language/locallang_mod.xlf:';
        $assigns['LLPrefixTask'] = 'LLL:EXT:taskcenter/Resources/Private/Language/locallang_task.xlf:';
        $assigns['admin'] = $this->getBackendUser()->isAdmin();

        // Rendering of the output via fluid
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setTemplateRootPaths([GeneralUtility::getFileAbsFileName('EXT:taskcenter/Resources/Private/Templates')]);
        $view->setPartialRootPaths([GeneralUtility::getFileAbsFileName('EXT:taskcenter/Resources/Private/Partials')]);
        $view->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName(
            'EXT:taskcenter/Resources/Private/Templates/InformationContent.html'
        ));
        $view->assignMultiple($assigns);
        $this->content .= $view->render();
    }

    /**
     * Render the headline of a task including a title and an optional description.
     * Public since task objects use this.
     *
     * @param string $title Title
     * @param string $description Description
     * @return string formatted title and description
     */
    public function description($title, $description = '')
    {
        $descriptionView = GeneralUtility::makeInstance(StandaloneView::class);
        $descriptionView->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName(
            'EXT:taskcenter/Resources/Private/Partials/Description.html'
        ));
        $descriptionView->assign('title', $title);
        $descriptionView->assign('description', $description);
        return $descriptionView->render();
    }

    /**
     * Render a list of items as a nicely formatted definition list including a link, icon, title and description.
     * The keys of a single item are:
     * - title:             Title of the item
     * - link:              Link to the task
     * - icon:              Path to the icon or Icon as HTML if it begins with <img
     * - description:       Description of the task, using htmlspecialchars()
     * - descriptionHtml:   Description allowing HTML tags which will override the description
     * Public since task objects use this.
     *
     * @param array $items List of items to be displayed in the definition list.
     * @param bool $mainMenu Set it to TRUE to render the main menu
     * @return string Formatted definition list
     */
    public function renderListMenu($items, $mainMenu = false)
    {
        $assigns = [];
        $assigns['mainMenu'] = $mainMenu;

        // Change the sorting of items to the user's one
        if ($mainMenu) {
            $userSorting = unserialize($this->getBackendUser()->uc['taskcenter']['sorting']);
            if (is_array($userSorting)) {
                $newSorting = [];
                foreach ($userSorting as $item) {
                    if (isset($items[$item])) {
                        $newSorting[] = $items[$item];
                        unset($items[$item]);
                    }
                }
                $items = $newSorting + $items;
            }
        }
        if (is_array($items) && !empty($items)) {
            foreach ($items as $itemKey => &$item) {
                // Check for custom icon
                if (!empty($item['icon'])) {
                    if (strpos($item['icon'], '<img ') === false) {
                        $iconFile = GeneralUtility::getFileAbsFileName($item['icon']);
                        if (@is_file($iconFile)) {
                            $item['iconFile'] = PathUtility::getAbsoluteWebPath($iconFile);
                        }
                    }
                }
                $id = $this->getUniqueKey($item['uid']);
                $contentId = strtolower(str_replace('\\', '-', $id));
                $item['uniqueKey'] = $id;
                $item['contentId'] = $contentId;
                // Collapsed & expanded menu items
                if (isset($this->getBackendUser()->uc['taskcenter']['states'][$id]) && $this->getBackendUser()->uc['taskcenter']['states'][$id]) {
                    $item['ariaExpanded'] = 'true';
                    $item['collapseIcon'] = 'actions-view-list-expand';
                    $item['collapsed'] = '';
                } else {
                    $item['ariaExpanded'] = 'false';
                    $item['collapseIcon'] = 'actions-view-list-collapse';
                    $item['collapsed'] = 'in';
                }
                // Active menu item
                $panelState = (string)$this->MOD_SETTINGS['function'] == $item['uid'] ? 'panel-active' : 'panel-default';
                $item['panelState'] = $panelState;
            }
        }
        $assigns['items'] = $items;

        // Rendering of the output via fluid
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName(
            'EXT:taskcenter/Resources/Private/Templates/ListMenu.html'
        ));
        $view->assignMultiple($assigns);
        return $view->render();
    }

    /**
     * Shows an overview list of available reports.
     *
     * @return string List of available reports
     */
    protected function indexAction()
    {
        $languageService = $this->getLanguageService();
        $content = '';
        $tasks = [];
        $defaultIcon = 'EXT:taskcenter/Resources/Public/Icons/module-taskcenter.svg';
        /** @var \TYPO3\CMS\Backend\Routing\UriBuilder $uriBuilder */
        $uriBuilder = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Routing\UriBuilder::class);
        // Render the tasks only if there are any available
        if (count($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['taskcenter'] ?? [])) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['taskcenter'] as $extKey => $extensionReports) {
                foreach ($extensionReports as $taskClass => $task) {
                    if (!$this->checkAccess($extKey, $taskClass)) {
                        continue;
                    }
                    $link = (string)$uriBuilder->buildUriFromRoute('user_task') . '&SET[function]=' . $extKey . '.' . $taskClass;
                    $taskTitle = $languageService->sL($task['title']);
                    $taskDescriptionHtml = '';

                    if (class_exists($taskClass)) {
                        $taskInstance = GeneralUtility::makeInstance($taskClass, $this);
                        if ($taskInstance instanceof TaskInterface) {
                            $taskDescriptionHtml = $taskInstance->getOverview();
                        }
                    }
                    // Generate an array of all tasks
                    $uniqueKey = $this->getUniqueKey($extKey . '.' . $taskClass);
                    $tasks[$uniqueKey] = [
                        'title' => $taskTitle,
                        'descriptionHtml' => $taskDescriptionHtml,
                        'description' => $languageService->sL($task['description']),
                        'icon' => !empty($task['icon']) ? $task['icon'] : $defaultIcon,
                        'link' => $link,
                        'uid' => $extKey . '.' . $taskClass
                    ];
                }
            }
            $content .= $this->renderListMenu($tasks, true);
        } else {
            $flashMessage = GeneralUtility::makeInstance(
                FlashMessage::class,
                $languageService->getLL('no-tasks'),
                '',
                FlashMessage::INFO
            );
            /** @var \TYPO3\CMS\Core\Messaging\FlashMessageService $flashMessageService */
            $flashMessageService = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Messaging\FlashMessageService::class);
            /** @var \TYPO3\CMS\Core\Messaging\FlashMessageQueue $defaultFlashMessageQueue */
            $defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
            $defaultFlashMessageQueue->enqueue($flashMessage);
        }
        return $content;
    }

    /**
     * Create the panel of buttons for submitting the form or otherwise
     * perform operations.
     */
    protected function getButtons()
    {
        $buttonBar = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar();

        // Shortcut
        $shortcutButton = $buttonBar->makeShortcutButton()
            ->setModuleName($this->moduleName)
            ->setSetVariables(['function']);
        $buttonBar->addButton($shortcutButton);
    }

    /**
     * Check the access to a task. Considered are:
     * - Admins are always allowed
     * - Tasks can be restriced to admins only
     * - Tasks can be blinded for Users with TsConfig taskcenter.<extensionkey>.<taskName> = 0
     *
     * @param string $extKey Extension key
     * @param string $taskClass Name of the task
     * @return bool Access to the task allowed or not
     */
    protected function checkAccess($extKey, $taskClass): bool
    {
        $backendUser = $this->getBackendUser();
        // Admins are always allowed
        if ($backendUser->isAdmin()) {
            return true;
        }
        // Check if task is restricted to admins
        if ((int)$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['taskcenter'][$extKey][$taskClass]['admin'] === 1) {
            return false;
        }
        // Check if task is blinded with TsConfig (taskcenter.<extkey>.<taskName>
        return (bool)($backendUser->getTSConfig()['taskcenter.'][$extKey . '.'][$taskClass] ?? true);
    }

    /**
     * Returns HTML code to dislay an url in an iframe at the right side of the taskcenter
     *
     * @param string $url Url to display
     * @return string Code that inserts the iframe (HTML)
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0. Remember to remove the fluid template, too.
     */
    protected function urlInIframe($url)
    {
        $urlView = GeneralUtility::makeInstance(StandaloneView::class);
        $urlView->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName(
            'EXT:taskcenter/Resources/Private/Partials/UrlInIframe.html'
        ));
        $urlView->assign('url', $url);
        return $urlView->render();
    }

    /**
     * Create a unique key from a string which can be used in JS for sorting
     * Therefore '_' are replaced
     *
     * @param string $string string which is used to generate the identifier
     * @return string Modified string
     */
    protected function getUniqueKey($string)
    {
        $search = ['.', '_'];
        $replace = ['-', ''];
        return str_replace($search, $replace, $string);
    }

    /**
     * Returns the current BE user.
     *
     * @return BackendUserAuthentication
     */
    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    /**
     * Returns LanguageService
     *
     * @return LanguageService
     */
    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }

    /**
     * Public since task objects use this.
     *
     * @return ModuleTemplate
     */
    public function getModuleTemplate(): ModuleTemplate
    {
        return $this->moduleTemplate;
    }

    /**
     * Merges menu items from global array $TBE_MODULES_EXT
     *
     * @param string $modName Module name for which to find value
     * @param string $menuKey Menu key, eg. 'function' for the function menu.
     * @param array $menuArr The part of a MOD_MENU array to work on.
     * @return array Modified array part.
     * @internal
     * @see \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::insertModuleFunction(), menuConfig()
     */
    protected function mergeExternalItems($modName, $menuKey, $menuArr)
    {
        $mergeArray = $GLOBALS['TBE_MODULES_EXT'][$modName]['MOD_MENU'][$menuKey];
        if (is_array($mergeArray)) {
            foreach ($mergeArray as $k => $v) {
                if (((string)$v['ws'] === '' || $this->getBackendUser()->workspace === 0 && GeneralUtility::inList($v['ws'], 'online')) || $this->getBackendUser()->workspace === -1 && GeneralUtility::inList($v['ws'], 'offline') || $this->getBackendUser()->workspace > 0 && GeneralUtility::inList($v['ws'], 'custom')) {
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
     * @see getExternalItemConfig(), init()
     */
    protected function handleExternalFunctionValue($MM_key = 'function', $MS_value = null)
    {
        if ($MS_value === null) {
            $MS_value = $this->MOD_SETTINGS[$MM_key];
        }
        $this->extClassConf = $this->getExternalItemConfig($this->MCONF['name'], $MM_key, $MS_value);
    }

    /**
     * Returns configuration values from the global variable $TBE_MODULES_EXT for the module given.
     * For example if the module is named "web_info" and the "function" key ($menuKey) of MOD_SETTINGS is "stat" ($value) then you will have the values of $TBE_MODULES_EXT['webinfo']['MOD_MENU']['function']['stat'] returned.
     *
     * @param string $modName Module name
     * @param string $menuKey Menu key, eg. "function" for the function menu. See $this->MOD_MENU
     * @param string $value Optionally the value-key to fetch from the array that would otherwise have been returned if this value was not set. Look source...
     * @return mixed The value from the TBE_MODULES_EXT array.
     * @see handleExternalFunctionValue()
     */
    protected function getExternalItemConfig($modName, $menuKey, $value = '')
    {
        if (isset($GLOBALS['TBE_MODULES_EXT'][$modName])) {
            return (string)$value !== '' ? $GLOBALS['TBE_MODULES_EXT'][$modName]['MOD_MENU'][$menuKey][$value] : $GLOBALS['TBE_MODULES_EXT'][$modName]['MOD_MENU'][$menuKey];
        }
        return null;
    }

    /**
     * Creates an instance of the class found in $this->extClassConf['name'] in $this->extObj if any (this should hold three keys, "name", "path" and "title" if a "Function menu module" tries to connect...)
     * This value in extClassConf might be set by an extension (in an ext_tables/ext_localconf file) which thus "connects" to a module.
     * The array $this->extClassConf is set in handleExternalFunctionValue() based on the value of MOD_SETTINGS[function]
     * If an instance is created it is initiated with $this passed as value and $this->extClassConf as second argument. Further the $this->MOD_SETTING is cleaned up again after calling the init function.
     *
     * @see handleExternalFunctionValue(), \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::insertModuleFunction(), $extObj
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0.
     */
    protected function checkExtObj()
    {
        if (is_array($this->extClassConf) && $this->extClassConf['name']) {
            $this->extObj = GeneralUtility::makeInstance($this->extClassConf['name']);
            $this->extObj->init($this, $this->extClassConf);
            // Re-write:
            $this->MOD_SETTINGS = BackendUtility::getModuleData($this->MOD_MENU, GeneralUtility::_GP('SET'), $this->MCONF['name'], $this->modMenu_type, $this->modMenu_dontValidateList, $this->modMenu_setDefaultList);
        }
    }

    /**
     * Calls the checkExtObj function in sub module if present.
     *
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0.
     */
    protected function checkSubExtObj()
    {
        if (is_object($this->extObj)) {
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
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0.
     */
    protected function extObjContent()
    {
        if ($this->extObj === null) {
            $flashMessage = GeneralUtility::makeInstance(
                FlashMessage::class,
                $this->getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang.xlf:no_modules_registered'),
                $this->getLanguageService()->getLL('title'),
                FlashMessage::ERROR
            );
            /** @var \TYPO3\CMS\Core\Messaging\FlashMessageService $flashMessageService */
            $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
            /** @var \TYPO3\CMS\Core\Messaging\FlashMessageQueue $defaultFlashMessageQueue */
            $defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
            $defaultFlashMessageQueue->enqueue($flashMessage);
        } else {
            $this->extObj->pObj = $this;
            if (is_callable([$this->extObj, 'main'])) {
                $this->content .= $this->extObj->main();
            }
        }
    }

    /**
     * Return the content of the 'main' function inside the "Function menu module" if present
     *
     * @return string
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0.
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
     * @return PageRenderer
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0.
     */
    protected function getPageRenderer(): PageRenderer
    {
        return GeneralUtility::makeInstance(PageRenderer::class);
    }
}
