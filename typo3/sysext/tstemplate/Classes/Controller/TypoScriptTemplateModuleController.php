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

namespace TYPO3\CMS\Tstemplate\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Routing\PreviewUriBuilder;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\WorkspaceRestriction;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\TypoScript\ExtendedTemplateService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Versioning\VersionState;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Fluid\ViewHelpers\Be\InfoboxViewHelper;

/**
 * Module: TypoScript Tools
 * @internal This is a specific Backend Controller implementation and is not considered part of the Public TYPO3 API.
 */
class TypoScriptTemplateModuleController
{

    /**
     * @var string
     */
    protected $perms_clause;

    /**
     * @var string
     */
    public $modMenu_dontValidateList = '';

    /**
     * @var string Written by client classes
     */
    public $modMenu_setDefaultList = '';

    /**
     * @var array
     */
    protected $pageinfo = [];

    /**
     * @var bool
     */
    protected $access = false;

    /**
     * The name of the module
     *
     * @var string
     */
    protected $moduleName = 'web_ts';

    /**
     * ModuleTemplate Container
     *
     * @var ModuleTemplate
     */
    protected $moduleTemplate;

    /**
     * @var ExtendedTemplateService
     */
    protected $templateService;

    /**
     * @var int GET/POST var 'id'
     */
    protected $id;

    /**
     * The module menu items array. Each key represents a key for which values can range between the items in the array of that key.
     * Written by client classes.
     *
     * @var array
     */
    public $MOD_MENU = [
        'function' => [],
    ];

    /**
     * Current settings for the keys of the MOD_MENU array, used in client classes
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
     * Contains module configuration parts from TBE_MODULES_EXT if found
     *
     * @var array
     */
    protected $extClassConf;

    /**
     * May contain an instance of a 'Function menu module' which connects to this backend module.
     *
     * @see checkExtObj()
     * @var object
     */
    protected $extObj;

    /**
     * @var ServerRequestInterface
     */
    protected $request;

    protected IconFactory $iconFactory;
    protected PageRenderer $pageRenderer;
    protected UriBuilder $uriBuilder;
    protected ModuleTemplateFactory $moduleTemplateFactory;

    public function __construct(
        IconFactory $iconFactory,
        PageRenderer $pageRenderer,
        UriBuilder $uriBuilder,
        ModuleTemplateFactory $moduleTemplateFactory
    ) {
        $this->iconFactory = $iconFactory;
        $this->pageRenderer = $pageRenderer;
        $this->uriBuilder = $uriBuilder;
        $this->moduleTemplateFactory = $moduleTemplateFactory;
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
        foreach ($this->MOD_MENU['function'] as $controller => $title) {
            $item = $menu
                ->makeMenuItem()
                ->setHref(
                    (string)$this->uriBuilder->buildUriFromRoute(
                        $this->moduleName,
                        [
                            'id' => $this->id,
                            'SET' => [
                                'function' => $controller,
                            ],
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
     * Injects the request object for the current request or subrequest
     * Then checks for module functions that have hooked in, and renders menu etc.
     *
     * @param ServerRequestInterface $request the current request
     * @return ResponseInterface the response with the content
     */
    public function mainAction(ServerRequestInterface $request): ResponseInterface
    {
        $this->moduleTemplate = $this->moduleTemplateFactory->create($request);
        $this->getLanguageService()->includeLLFile('EXT:tstemplate/Resources/Private/Language/locallang.xlf');
        $this->request = $request;
        $this->id = (int)($request->getParsedBody()['id'] ?? $request->getQueryParams()['id'] ?? 0);
        $changedMenuSettings = $request->getParsedBody()['SET'] ?? $request->getQueryParams()['SET'] ?? [];
        $changedMenuSettings = is_array($changedMenuSettings) ? $changedMenuSettings : [];
        $this->menuConfig($changedMenuSettings);
        // Loads $this->extClassConf with the configuration for the CURRENT function of the menu.
        $this->extClassConf = $this->getExternalItemConfig('web_ts', 'function', $this->MOD_SETTINGS['function']);
        $this->perms_clause = $this->getBackendUser()->getPagePermsClause(Permission::PAGE_SHOW);

        // Checking for first level external objects
        $this->checkExtObj($changedMenuSettings, $request);

        // Access check...
        // The page will show only if there is a valid page and if this page may be viewed by the user
        $this->pageinfo = BackendUtility::readPageAccess($this->id, $this->perms_clause) ?: [];
        $this->access = $this->pageinfo !== [];
        $view = $this->getFluidTemplateObject('tstemplate');
        if ($this->id && $this->access) {
            // Setting up the context sensitive menu
            $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/ContextMenu');
            $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/Element/ImmediateActionElement');
            // Build the module content
            $view->assign('pageId', $this->id);
            $view->assign('typoscriptTemplateModuleContent', $this->getExtObjContent());
            // Setting up the buttons and markers for docheader
            $this->getButtons();
            $this->generateMenu();
        } else {
            $workspaceId = $this->getBackendUser()->workspace;
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable('sys_template');
            $queryBuilder->getRestrictions()
                ->removeAll()
                ->add(GeneralUtility::makeInstance(DeletedRestriction::class));
            $this->applyWorkspaceConstraint(
                $queryBuilder,
                'sys_template',
                $workspaceId
            );
            $result = $queryBuilder
                ->select(
                    'uid',
                    'pid',
                    'title',
                    'root',
                    'hidden',
                    'starttime',
                    'endtime',
                    't3ver_oid',
                    't3ver_wsid',
                    't3ver_state'
                )
                ->from('sys_template')
                ->orderBy('sys_template.pid')
                ->addOrderBy('sys_template.sorting')
                ->executeQuery();
            $pArray = [];
            while ($record = $result->fetchAssociative()) {
                BackendUtility::workspaceOL('sys_template', $record, $workspaceId, true);
                if (empty($record) || VersionState::cast($record['t3ver_state'])->equals(VersionState::DELETE_PLACEHOLDER)) {
                    continue;
                }
                $additionalFieldsForRootline = ['sorting', 'shortcut'];
                $rootline = BackendUtility::BEgetRootLine($record['pid'], '', true, $additionalFieldsForRootline);
                $this->setInPageArray($pArray, $rootline, $record);
            }

            $view->getRenderingContext()->setControllerAction('PageZero');
            $view->assign('pageTree', $pArray);

            // RENDER LIST of pages with templates, END
            // Setting up the buttons and markers for docheader
            $this->getButtons();
        }
        $this->moduleTemplate->setContent($view->render());

        $this->moduleTemplate->setTitle(
            $this->getLanguageService()->sL($this->extClassConf['title']),
            $this->pageinfo['title'] ?? ''
        );

        return new HtmlResponse($this->moduleTemplate->renderContent());
    }

    /**
     * Create the panel of buttons for submitting the form or otherwise perform operations.
     */
    protected function getButtons()
    {
        $buttonBar = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar();
        $lang = $this->getLanguageService();

        if ($this->id && $this->access) {
            // View page
            $previewDataAttributes = PreviewUriBuilder::create((int)$this->pageinfo['uid'])
                ->withRootLine(BackendUtility::BEgetRootLine($this->pageinfo['uid']))
                ->buildDispatcherDataAttributes();
            $viewButton = $buttonBar->makeLinkButton()
                ->setHref('#')
                ->setDataAttributes($previewDataAttributes ?? [])
                ->setTitle($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.showPage'))
                ->setIcon($this->iconFactory->getIcon('actions-view-page', Icon::SIZE_SMALL));
            $buttonBar->addButton($viewButton, ButtonBar::BUTTON_POSITION_LEFT, 99);

            $sObj = $this->request->getParsedBody()['sObj'] ?? $this->request->getQueryParams()['sObj'] ?? null;
            if ($this->extClassConf['name'] === TypoScriptTemplateInformationModuleFunctionController::class) {
                // NEW button
                $urlParameters = [
                    'id' => $this->id,
                    'template' => 'all',
                    'createExtension' => 'new',
                ];
                $newButton = $buttonBar->makeLinkButton()
                    ->setHref((string)$this->uriBuilder->buildUriFromRoute('web_ts', $urlParameters))
                    ->setTitle($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:db_new.php.pagetitle'))
                    ->setIcon($this->iconFactory->getIcon('actions-add', Icon::SIZE_SMALL));
                $buttonBar->addButton($newButton);
            } elseif ($this->extClassConf['name'] === TypoScriptTemplateConstantEditorModuleFunctionController::class
                && !empty($this->MOD_MENU['constant_editor_cat'])) {
                // SAVE button
                $saveButton = $buttonBar->makeInputButton()
                    ->setName('_savedok')
                    ->setValue('1')
                    ->setForm('TypoScriptTemplateModuleController')
                    ->setTitle($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:rm.saveDoc'))
                    ->setIcon($this->iconFactory->getIcon('actions-document-save', Icon::SIZE_SMALL))
                    ->setShowLabelText(true);
                $buttonBar->addButton($saveButton);
            } elseif ($this->extClassConf['name'] === TypoScriptTemplateObjectBrowserModuleFunctionController::class
                && !empty($sObj)
            ) {
                // back button in edit mode of object browser. "sObj" is set by ExtendedTemplateService
                $urlParameters = [
                    'id' => $this->id,
                ];
                $backButton = $buttonBar->makeLinkButton()
                    ->setHref((string)$this->uriBuilder->buildUriFromRoute('web_ts', $urlParameters))
                    ->setTitle($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.goBack'))
                    ->setIcon($this->iconFactory->getIcon('actions-view-go-back', Icon::SIZE_SMALL));
                $buttonBar->addButton($backButton);
            }
        }
        // Shortcut
        $shortcutButton = $buttonBar->makeShortcutButton()
            ->setRouteIdentifier('web_ts')
            ->setDisplayName($this->getShortcutTitle())
            ->setArguments(['id' => (int)$this->id]);
        $buttonBar->addButton($shortcutButton);
    }

    /**
     * Wrap title for link in template, called from client classes.
     *
     * @param string $title
     * @param string $onlyKey
     * @return string
     */
    public function linkWrapTemplateTitle($title, $onlyKey = '')
    {
        $urlParameters = [
            'id' => $this->id,
        ];
        if ($onlyKey) {
            $urlParameters['e'] = [$onlyKey => 1];
        } else {
            $urlParameters['e'] = ['constants' => 1];
        }
        $urlParameters['SET'] = ['function' => TypoScriptTemplateInformationModuleFunctionController::class];
        $url = (string)$this->uriBuilder->buildUriFromRoute('web_ts', $urlParameters);
        return '<a href="' . htmlspecialchars($url) . '">' . htmlspecialchars($title) . '</a>';
    }

    /**
     * No template, called from client classes.
     *
     * @param int $newStandardTemplate
     * @return string
     */
    public function noTemplate($newStandardTemplate = 0)
    {
        $this->templateService = GeneralUtility::makeInstance(ExtendedTemplateService::class);

        $moduleContent = [];
        $moduleContent['state'] = InfoboxViewHelper::STATE_INFO;

        // New standard?
        if ($newStandardTemplate) {
            $selector = '';
            $staticsText = '';
            // Hook to change output, implemented for statictemplates
            $hookObject = $this->getHookObjectForAction('newStandardTemplateView');
            if (!empty($hookObject)) {
                $reference = [
                    'selectorHtml' => &$selector,
                    'staticsText' => &$staticsText,
                ];
                GeneralUtility::callUserFunction(
                    $hookObject,
                    $reference,
                    $this
                );
                $selector = $reference['selectorHtml'];
                $staticsText = $reference['staticsText'];
            }
            // Extension?
            $moduleContent['staticsText'] = $staticsText;
            $moduleContent['selector'] = $selector;
        }
        $view = $this->getFluidTemplateObject('tstemplate', 'NoTemplate');
        // Go to previous Page with a template
        $view->assign('previousPage', $this->templateService->ext_prevPageWithTemplate($this->id, $this->perms_clause));
        $view->assign('content', $moduleContent);
        return $view->render();
    }

    /**
     * Render template menu, called from client classes.
     *
     * @param ServerRequestInterface $request
     * @return string
     */
    public function templateMenu(ServerRequestInterface $request)
    {
        $this->templateService = GeneralUtility::makeInstance(ExtendedTemplateService::class);

        $all = $this->templateService->ext_getAllTemplates($this->id);
        if (count($all) > 1) {
            $this->MOD_MENU['templatesOnPage'] = [];
            foreach ($all as $d) {
                $this->MOD_MENU['templatesOnPage'][$d['uid']] = $d['title'];
            }
        }
        $this->MOD_SETTINGS = BackendUtility::getModuleData(
            $this->MOD_MENU,
            $request->getParsedBody()['SET'] ?? $request->getQueryParams()['SET'] ?? [],
            'web_ts',
            '',
            $this->modMenu_dontValidateList,
            $this->modMenu_setDefaultList
        );
        return BackendUtility::getFuncMenu(
            $this->id,
            'SET[templatesOnPage]',
            $this->MOD_SETTINGS['templatesOnPage'] ?? '',
            $this->MOD_MENU['templatesOnPage'] ?? []
        );
    }

    /**
     * Create template, called from client classes.
     *
     * @param int $id
     * @param int $actTemplateId
     * @return string
     */
    public function createTemplate($id, $actTemplateId = 0)
    {
        $recData = [];
        $tce = GeneralUtility::makeInstance(DataHandler::class);

        if ($this->request->getParsedBody()['createExtension'] ?? $this->request->getQueryParams()['createExtension'] ?? false) {
            $recData['sys_template']['NEW'] = [
                'pid' => $actTemplateId ? -1 * $actTemplateId : $id,
                'title' => '+ext',
            ];
            $tce->start($recData, []);
            $tce->process_datamap();
        } elseif ($this->request->getParsedBody()['newWebsite'] ?? $this->request->getQueryParams()['newWebsite'] ?? false) {
            // Hook to handle row data, implemented for statictemplates
            $hookObject = $this->getHookObjectForAction('newStandardTemplateHandler');
            if (!empty($hookObject)) {
                $reference = [
                    'recData' => &$recData,
                    'id' => $id,
                ];
                GeneralUtility::callUserFunction(
                    $hookObject,
                    $reference,
                    $this
                );
                $recData = $reference['recData'];
            } else {
                $recData['sys_template']['NEW'] = [
                    'pid' => $id,
                    'title' => $this->getLanguageService()->getLL('titleNewSite'),
                    'sorting' => 0,
                    'root' => 1,
                    'clear' => 3,
                    'config' => '
# Default PAGE object:
page = PAGE
page.10 = TEXT
page.10.value = HELLO WORLD!
',
                ];
            }
            $tce->start($recData, []);
            $tce->process_datamap();
        }
        return $tce->substNEWwithIDs['NEW'] ?? '';
    }

    /**
     * Set page in array
     * To render list of page tree with templates
     *
     * @param array $pArray Multidimensional array of page tree with template records
     * @param array $rlArr Rootline array
     * @param array $row Record of sys_template
     */
    protected function setInPageArray(&$pArray, $rlArr, $row)
    {
        ksort($rlArr);
        reset($rlArr);
        if (!$rlArr[0]['uid']) {
            array_shift($rlArr);
        }
        $cEl = current($rlArr);
        if (empty($pArray[$cEl['uid']])) {
            $pArray[$cEl['uid']] = $cEl;
        }
        array_shift($rlArr);
        if (!empty($rlArr)) {
            if (empty($pArray[$cEl['uid']]['_nodes'])) {
                $pArray[$cEl['uid']]['_nodes'] = [];
            }
            $this->setInPageArray($pArray[$cEl['uid']]['_nodes'], $rlArr, $row);
        } else {
            $pArray[$cEl['uid']]['_templates'][] = $row;
        }
        uasort($pArray, static function ($a, $b) {
            return $a['sorting'] - $b['sorting'];
        });
    }

    /**
     * Returns a new standalone view, shorthand function
     *
     * @param string $extensionName
     * @param string $templateName
     * @return StandaloneView
     */
    protected function getFluidTemplateObject($extensionName, $templateName = 'Main')
    {
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->getRenderingContext()->getTemplatePaths()->fillDefaultsByPackageName($extensionName);
        $view->getRenderingContext()->setControllerAction($templateName);
        $view->getRequest()->setControllerExtensionName('tstemplate');
        return $view;
    }

    /**
     * Fetching all live records, and versioned records that do not have a "online ID" counterpart,
     * as this is then handled via the BackendUtility::workspaceOL().
     *
     * @param QueryBuilder $queryBuilder
     * @param string $tableName
     * @param int $workspaceId
     */
    protected function applyWorkspaceConstraint(
        QueryBuilder $queryBuilder,
        string $tableName,
        int $workspaceId
    ) {
        if (!BackendUtility::isTableWorkspaceEnabled($tableName)) {
            return;
        }

        $queryBuilder->getRestrictions()->add(
            GeneralUtility::makeInstance(WorkspaceRestriction::class, $workspaceId)
        );
    }

    /**
     * @param string $action
     * @return string
     */
    protected function getHookObjectForAction(string $action): string
    {
        return (string)($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][self::class][$action] ?? '');
    }

    /**
     * Initializes the internal MOD_MENU array setting and unsetting items based on various conditions. It also merges in external menu items from the global array TBE_MODULES_EXT (see mergeExternalItems())
     * Then MOD_SETTINGS array is cleaned up (see \TYPO3\CMS\Backend\Utility\BackendUtility::getModuleData()) so it contains only valid values. It's also updated with any SET[] values submitted.
     * Also loads the modTSconfig internal variable.
     *
     * @param array $changedSettings can be anything
     * @see mainAction()
     * @see \TYPO3\CMS\Backend\Utility\BackendUtility::getModuleData()
     * @see mergeExternalItems()
     */
    protected function menuConfig($changedSettings)
    {
        // Page / user TSconfig settings and blinding of menu-items
        $this->modTSconfig['properties'] = BackendUtility::getPagesTSconfig($this->id)['mod.']['web_ts.'] ?? [];
        $this->MOD_MENU['function'] = $this->mergeExternalItems('web_ts', 'function', $this->MOD_MENU['function']);
        $blindActions = $this->modTSconfig['properties']['menu.']['function.'] ?? [];
        foreach ($blindActions as $key => $value) {
            if (!$value && array_key_exists($key, $this->MOD_MENU['function'])) {
                unset($this->MOD_MENU['function'][$key]);
            }
        }
        $this->MOD_SETTINGS = BackendUtility::getModuleData($this->MOD_MENU, $changedSettings, 'web_ts', '', $this->modMenu_dontValidateList, $this->modMenu_setDefaultList);
    }

    /**
     * Merges menu items from global array $TBE_MODULES_EXT
     *
     * @param string $modName Module name for which to find value
     * @param string $menuKey Menu key, eg. 'function' for the function menu.
     * @param array $menuArr The part of a MOD_MENU array to work on.
     * @return array Modified array part.
     * @internal
     * @see \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::insertModuleFunction()
     * @see menuConfig()
     */
    protected function mergeExternalItems($modName, $menuKey, $menuArr)
    {
        $mergeArray = $GLOBALS['TBE_MODULES_EXT'][$modName]['MOD_MENU'][$menuKey];
        if (is_array($mergeArray)) {
            foreach ($mergeArray as $k => $v) {
                if (((string)$v['ws'] === '' || ($this->getBackendUser()->workspace === 0 && GeneralUtility::inList($v['ws'], 'online')))
                    || ($this->getBackendUser()->workspace > 0 && GeneralUtility::inList($v['ws'], 'custom'))
                ) {
                    $menuArr[$k] = $this->getLanguageService()->sL($v['title']);
                }
            }
        }
        return $menuArr;
    }

    /**
     * Returns configuration values from the global variable $TBE_MODULES_EXT for the module given.
     * For example if the module is named "web_info" and the "function" key ($menuKey) of MOD_SETTINGS is "stat" ($value) then you will have the values of $TBE_MODULES_EXT['webinfo']['MOD_MENU']['function']['stat'] returned.
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
     * The array $this->extClassConf is set based on the value of MOD_SETTINGS[function]
     * If an instance is created it is initiated with $this passed as value and $this->extClassConf as second argument. Further the $this->MOD_SETTING is cleaned up again after calling the init function.
     *
     * @see \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::insertModuleFunction()
     * @param array $changedSettings
     * @param ServerRequestInterface $request
     */
    protected function checkExtObj($changedSettings, ServerRequestInterface $request)
    {
        if (is_array($this->extClassConf) && $this->extClassConf['name']) {
            $this->extObj = GeneralUtility::makeInstance($this->extClassConf['name']);
            $this->extObj->init($this, $request);
            // Re-write:
            $this->MOD_SETTINGS = BackendUtility::getModuleData($this->MOD_MENU, $changedSettings, 'web_ts', '', $this->modMenu_dontValidateList, $this->modMenu_setDefaultList);
        }
    }

    /**
     * Return the content of the 'main' function inside the "Function menu module" if present
     *
     * @return string|null
     */
    protected function getExtObjContent()
    {
        // Calls the 'main' function inside the "Function menu module" if present
        if ($this->extObj === null) {
            $flashMessage = GeneralUtility::makeInstance(
                FlashMessage::class,
                $this->getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang.xlf:no_modules_registered'),
                $this->getLanguageService()->getLL('title'),
                FlashMessage::ERROR
            );
            $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
            $defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
            $defaultFlashMessageQueue->enqueue($flashMessage);
        } elseif (is_callable([$this->extObj, 'main'])) {
            return $this->extObj->main();
        }

        return null;
    }

    /**
     * Returns the shortcut title for the current page
     *
     * @return string
     */
    protected function getShortcutTitle(): string
    {
        return sprintf(
            '%s: %s [%d]',
            $this->getLanguageService()->sL('LLL:EXT:tstemplate/Resources/Private/Language/locallang_mod.xlf:mlang_labels_tablabel'),
            BackendUtility::getRecordTitle('pages', $this->pageinfo),
            $this->id
        );
    }

    /**
     * Returns the Language Service
     * @return LanguageService
     */
    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }

    /**
     * Returns the Backend User
     * @return BackendUserAuthentication
     */
    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
