<?php
namespace TYPO3\CMS\Tstemplate\Controller;

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
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Compatibility\PublicMethodDeprecationTrait;
use TYPO3\CMS\Core\Compatibility\PublicPropertyDeprecationTrait;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\TypoScript\ExtendedTemplateService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Versioning\VersionState;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Fluid\ViewHelpers\Be\InfoboxViewHelper;

/**
 * Module: TypoScript Tools
 * @internal This is a specific Backend Controller implementation and is not considered part of the Public TYPO3 API.
 */
class TypoScriptTemplateModuleController
{
    use PublicPropertyDeprecationTrait;
    use PublicMethodDeprecationTrait;

    /**
     * @var array
     */
    private $deprecatedPublicProperties = [
        'textExtensions' => 'Using TypoScriptTemplateModuleController::$textExtensions is deprecated and will not be possible anymore in TYPO3 v10.0.',
        'pageinfo' => 'Using TypoScriptTemplateModuleController::$pageinfo is deprecated and will not be possible anymore in TYPO3 v10.0.',
        'id' => 'Using TypoScriptTemplateModuleController::$id is deprecated and will not be possible anymore in TYPO3 v10.0.',
        'modTSconfig' => 'Using TypoScriptTemplateModuleController::$modTSconfig is deprecated and will not be possible anymore in TYPO3 v10.0.',
        'content' => 'Using TypoScriptTemplateModuleController::$content is deprecated and will not be possible anymore in TYPO3 v10.0.',
        'extObj' => 'Using TypoScriptTemplateModuleController::$extObj is deprecated and will not be possible anymore in TYPO3 v10.0.',
        'access' => 'Using TypoScriptTemplateModuleController::$access is deprecated and will not be possible anymore in TYPO3 v10.0.',
        'perms_clause' => 'Using TypoScriptTemplateModuleController::$perms_clause is deprecated and will not be possible anymore in TYPO3 v10.0.',
        'extClassConf' => 'Using TypoScriptTemplateModuleController::$extClassConf is deprecated and will not be possible anymore in TYPO3 v10.0.',
        'edit' => 'Using TypoScriptTemplateModuleController::$edit is deprecated, property will be removed in TYPO3 v10.0.',
        'modMenu_type' => 'Using TypoScriptTemplateModuleController::$modMenu_type is deprecated, property will be removed in TYPO3 v10.0.',
        'MCONF' => 'Using TypoScriptTemplateModuleController::$MCONF is deprecated, property will be removed in TYPO3 v10.0.',
        'CMD' => 'Using TypoScriptTemplateModuleController::$CMD is deprecated, property will be removed in TYPO3 v10.0.',
        'sObj' => 'Using TypoScriptTemplateModuleController::$sObj is deprecated, property will be removed in TYPO3 v10.0.',
    ];

    /**
     * @var array
     */
    private $deprecatedPublicMethods = [
        'getExternalItemConfig' => 'Using TypoScriptTemplateModuleController::getExternalItemConfig() is deprecated and will not be possible anymore in TYPO3 v10.0.',
        'init' => 'Using TypoScriptTemplateModuleController::init() is deprecated and will not be possible anymore in TYPO3 v10.0.',
        'clearCache' => 'Using TypoScriptTemplateModuleController::clearCache() is deprecated and will not be possible anymore in TYPO3 v10.0.',
        'main' => 'Using TypoScriptTemplateModuleController::main() is deprecated and will not be possible anymore in TYPO3 v10.0.',
        'setInPageArray' => 'Using TypoScriptTemplateModuleController::setInPageArray() is deprecated and will not be possible anymore in TYPO3 v10.0.',
        'menuConfig' => 'Using TypoScriptTemplateModuleController::menuConfig() is deprecated and will not be possible anymore in TYPO3 v10.0.',
        'mergeExternalItems' => 'Using TypoScriptTemplateModuleController::mergeExternalItems() is deprecated and will not be possible anymore in TYPO3 v10.0.',
        'handleExternalFunctionValue' => 'Using TypoScriptTemplateModuleController::handleExternalFunctionValue() is deprecated and will not be possible anymore in TYPO3 v10.0.',
        'checkExtObj' => 'Using TypoScriptTemplateModuleController::checkExtObj() is deprecated and will not be possible anymore in TYPO3 v10.0.',
        'extObjContent' => 'Using TypoScriptTemplateModuleController::extObjContent() is deprecated and will not be possible anymore in TYPO3 v10.0.',
        'getExtObjContent' => 'Using TypoScriptTemplateModuleController::getExtObjContent() is deprecated and will not be possible anymore in TYPO3 v10.0.',
        'checkSubExtObj' => 'Using TypoScriptTemplateModuleController::checkSubExtObj() is deprecated, method will be removed in TYPO3 v10.0.',
        'extObjHeader' => 'Using TypoScriptTemplateModuleController::extObjHeader() is deprecated, method will be removed in TYPO3 v10.0.',
    ];

    /**
     * @var string
     */
    protected $perms_clause;

    /**
     * @var string
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0.
     */
    protected $sObj;

    /**
     * @var string
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0.
     */
    protected $edit;

    /**
     * @var string
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0.
     */
    protected $textExtensions = 'html,htm,txt,css,tmpl,inc,js';

    /**
     * @var string Written by client classes.
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0. Remove last usages, too.
     */
    protected $modMenu_type = '';

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
     * Loaded with the global array $MCONF which holds some module configuration from the conf.php file of backend modules.
     *
     * @var array
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0.
     */
    protected $MCONF = [];

    /**
     * @var int GET/POST var 'id'
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
     * The module menu items array. Each key represents a key for which values can range between the items in the array of that key.
     * Written by client classes.
     *
     * @var array
     */
    public $MOD_MENU = [
        'function' => []
    ];

    /**
     * Current settings for the keys of the MOD_MENU array, used in client classes
     *
     * @see $MOD_MENU
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
     * @see handleExternalFunctionValue()
     * @var array
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
     */
    protected $extObj;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->moduleTemplate = GeneralUtility::makeInstance(ModuleTemplate::class);
        $this->getLanguageService()->includeLLFile('EXT:tstemplate/Resources/Private/Language/locallang.xlf');

        // @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0.
        $this->MCONF = [
            'name' => $this->moduleName
        ];
        $this->moduleTemplate->addJavaScriptCode(
            'jumpToUrl',
            '
            function jumpToUrl(URL) {
                window.location.href = URL;
                return false;
            }
            '
        );
    }

    /**
     * Init
     */
    protected function init()
    {
        // @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0.
        $this->CMD = GeneralUtility::_GP('CMD');
        $this->menuConfig();
        $this->handleExternalFunctionValue();
        $this->id = (int)GeneralUtility::_GP('id');
        // @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0.
        $this->sObj = GeneralUtility::_GP('sObj');
        // @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0.
        $this->edit = GeneralUtility::_GP('edit');
        $this->perms_clause = $this->getBackendUser()->getPagePermsClause(Permission::PAGE_SHOW);
    }

    /**
     * Clear cache
     */
    protected function clearCache()
    {
        if (GeneralUtility::_GP('clear_all_cache')) {
            $tce = GeneralUtility::makeInstance(DataHandler::class);
            $tce->start([], []);
            $tce->clear_cacheCmd('all');
        }
    }

    /**
     * Main
     */
    protected function main()
    {
        // Access check...
        // The page will show only if there is a valid page and if this page may be viewed by the user
        $this->pageinfo = BackendUtility::readPageAccess($this->id, $this->perms_clause);
        $this->access = is_array($this->pageinfo);
        $view = $this->getFluidTemplateObject('tstemplate');
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        if ($this->id && $this->access) {
            $urlParameters = [
                'id' => $this->id,
                'template' => 'all'
            ];
            $aHref = (string)$uriBuilder->buildUriFromRoute('web_ts', $urlParameters);

            // JavaScript
            $this->moduleTemplate->addJavaScriptCode(
                'TSTemplateInlineJS',
                'function uFormUrl(aname) {
                    document.forms[0].action = ' . GeneralUtility::quoteJSvalue($aHref . '#') . '+aname;
                }
                function brPoint(lnumber,t) {
                    window.location.href = '
                . GeneralUtility::quoteJSvalue(
                    $aHref . '&SET[function]=TYPO3\\CMS\\Tstemplate\\Controller\\'
                    . 'TypoScriptTemplateObjectBrowserModuleFunctionController&SET[ts_browser_type]='
                ) . '+(t?"setup":"const")+"&breakPointLN="+lnumber;
                    return false;
                }
                if (top.fsMod) top.fsMod.recentIds["web"] = ' . $this->id . ';'
            );
            $this->moduleTemplate->getPageRenderer()->addCssInlineBlock(
                'TSTemplateInlineStyle',
                'TABLE#typo3-objectBrowser { width: 100%; margin-bottom: 24px; }
                TABLE#typo3-objectBrowser A { text-decoration: none; }
                TABLE#typo3-objectBrowser .comment { color: maroon; font-weight: bold; }
                .ts-typoscript { width: 100%; }
                .tsob-search-submit {margin-left: 3px; margin-right: 3px;}
                .tst-analyzer-options { margin:5px 0; }'
            );
            // Setting up the context sensitive menu:
            $this->moduleTemplate->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Backend/ContextMenu');
            // Build the module content
            $view->assign('actionName', $aHref);
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
                    'sitetitle',
                    'root',
                    'hidden',
                    'starttime',
                    'endtime',
                    't3ver_oid',
                    't3ver_wsid',
                    't3ver_state',
                    't3ver_move_id'
                )
                ->from('sys_template')
                ->orderBy('sys_template.pid')
                ->addOrderBy('sys_template.sorting')
                ->execute();
            $pArray = [];
            while ($record = $result->fetch()) {
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
        $this->content = $view->render();
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
     * Injects the request object for the current request or subrequest
     * Then checks for module functions that have hooked in, and renders menu etc.
     *
     * @param ServerRequestInterface $request the current request
     * @return ResponseInterface the response with the content
     */
    public function mainAction(ServerRequestInterface $request): ResponseInterface
    {
        // @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0.
        $GLOBALS['SOBE'] = $this;

        $this->init();

        // Checking for first level external objects
        $this->checkExtObj();

        $this->clearCache();
        $this->main();

        $this->moduleTemplate->setContent($this->content);
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
            $viewButton = $buttonBar->makeLinkButton()
                ->setHref('#')
                ->setOnClick(BackendUtility::viewOnClick(
                    $this->pageinfo['uid'],
                    '',
                    BackendUtility::BEgetRootLine($this->pageinfo['uid'])
                ))
                ->setTitle($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.showPage'))
                ->setIcon($this->moduleTemplate->getIconFactory()->getIcon('actions-view-page', Icon::SIZE_SMALL));
            $buttonBar->addButton($viewButton, ButtonBar::BUTTON_POSITION_LEFT, 99);

            if ($this->extClassConf['name'] === TypoScriptTemplateInformationModuleFunctionController::class) {
                // NEW button
                $urlParameters = [
                    'id' => $this->id,
                    'template' => 'all',
                    'createExtension' => 'new'
                ];
                $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
                $newButton = $buttonBar->makeLinkButton()
                    ->setHref((string)$uriBuilder->buildUriFromRoute('web_ts', $urlParameters))
                    ->setTitle($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:db_new.php.pagetitle'))
                    ->setIcon($this->moduleTemplate->getIconFactory()->getIcon(
                        'actions-add',
                        Icon::SIZE_SMALL
                    ));
                $buttonBar->addButton($newButton);
            } elseif ($this->extClassConf['name'] === TypoScriptTemplateConstantEditorModuleFunctionController::class
                && !empty($this->MOD_MENU['constant_editor_cat'])) {
                // SAVE button
                $saveButton = $buttonBar->makeInputButton()
                    ->setName('_savedok')
                    ->setValue('1')
                    ->setForm('TypoScriptTemplateModuleController')
                    ->setTitle($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:rm.saveDoc'))
                    ->setIcon($this->moduleTemplate->getIconFactory()->getIcon(
                        'actions-document-save',
                        Icon::SIZE_SMALL
                    ))
                    ->setShowLabelText(true);
                $buttonBar->addButton($saveButton);
            } elseif ($this->extClassConf['name'] === TypoScriptTemplateObjectBrowserModuleFunctionController::class
                && !empty(GeneralUtility::_GP('sObj'))
            ) {
                // back button in edit mode of object browser. "sObj" is set by ExtendedTemplateService
                $urlParameters = [
                    'id' => $this->id
                ];
                $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
                $backButton = $buttonBar->makeLinkButton()
                    ->setHref((string)$uriBuilder->buildUriFromRoute('web_ts', $urlParameters))
                    ->setClasses('typo3-goBack')
                    ->setTitle($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.goBack'))
                    ->setIcon($this->moduleTemplate->getIconFactory()->getIcon(
                        'actions-view-go-back',
                        Icon::SIZE_SMALL
                    ));
                $buttonBar->addButton($backButton);
            }
        }
        // Shortcut
        $shortcutButton = $buttonBar->makeShortcutButton()
            ->setModuleName('web_ts')
            ->setGetVariables(['id', 'route']);
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
            'id' => $this->id
        ];
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        $aHref = (string)$uriBuilder->buildUriFromRoute('web_ts', $urlParameters);
        if ($onlyKey) {
            $title = '<a href="' . htmlspecialchars($aHref . '&e[' . $onlyKey . ']=1&SET[function]=TYPO3\\CMS\\Tstemplate\\Controller\\TypoScriptTemplateInformationModuleFunctionController') . '">' . htmlspecialchars($title) . '</a>';
        } else {
            $title = '<a href="' . htmlspecialchars($aHref . '&e[constants]=1&e[config]=1&SET[function]=TYPO3\\CMS\\Tstemplate\\Controller\\TypoScriptTemplateInformationModuleFunctionController') . '">' . htmlspecialchars($title) . '</a>';
        }
        return $title;
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
                    'staticsText' => &$staticsText
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
        // Go to previous Page with Template...
        $previousPage = $this->templateService->ext_prevPageWithTemplate($this->id, $this->perms_clause);
        if ($previousPage) {
            $urlParameters = [
                'id' => $previousPage['uid']
            ];
            $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
            $previousPage['aHref'] = (string)$uriBuilder->buildUriFromRoute('web_ts', $urlParameters);
            $moduleContent['previousPage'] = $previousPage;
        }
        $view = $this->getFluidTemplateObject('tstemplate', 'NoTemplate');
        $view->assign('content', $moduleContent);
        return $view->render();
    }

    /**
     * Render template menu, called from client classes.
     *
     * @return string
     */
    public function templateMenu()
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
            GeneralUtility::_GP('SET'),
            'web_ts',
            $this->modMenu_type,
            $this->modMenu_dontValidateList,
            $this->modMenu_setDefaultList
        );
        return BackendUtility::getFuncMenu(
            $this->id,
            'SET[templatesOnPage]',
            $this->MOD_SETTINGS['templatesOnPage'],
            $this->MOD_MENU['templatesOnPage']
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

        if (GeneralUtility::_GP('createExtension')) {
            $recData['sys_template']['NEW'] = [
                'pid' => $actTemplateId ? -1 * $actTemplateId : $id,
                'title' => '+ext'
            ];
            $tce->start($recData, []);
            $tce->process_datamap();
        } elseif (GeneralUtility::_GP('newWebsite')) {
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
'
                ];
            }
            $tce->start($recData, []);
            $tce->process_datamap();
            $tce->clear_cacheCmd('all');
        }
        return $tce->substNEWwithIDs['NEW'];
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
        uasort($pArray, function ($a, $b) {
            return $a['sorting'] - $b['sorting'];
        });
    }

    /**
     * Get the list
     *
     * @param array $pArray
     * @param array $lines
     * @param int $c
     * @return array
     * @deprecated since TYPO3 v9.4, will be removed in TYPO3 v10.0
     */
    public function renderList($pArray, $lines = [], $c = 0)
    {
        trigger_error(
            'The method `TYPO3\CMS\Tstemplate\Controller\TypoScriptTemplateModuleController::renderList` has been deprecated and should not be used any longer, this method will be removed in TYPO3 v10.0',
            E_USER_DEPRECATED
        );

        if (!is_array($pArray)) {
            return $lines;
        }

        $statusCheckedIcon = $this->moduleTemplate->getIconFactory()
            ->getIcon('status-status-checked', Icon::SIZE_SMALL)->render();
        foreach ($pArray as $k => $v) {
            if (MathUtility::canBeInterpretedAsInteger($k)) {
                $line = [];
                $key = $k . '_';
                $line['marginLeft'] = $c * 20;
                $line['pageTitle'] = GeneralUtility::fixed_lgd_cs($pArray[$k], 30);
                $line['icon'] = $this->moduleTemplate->getIconFactory()
                    ->getIconForRecord(
                        'pages',
                        BackendUtility::getRecordWSOL('pages', $k),
                        Icon::SIZE_SMALL
                    )->render();
                if (!empty($pArray[$key])) {
                    $line['href'] = GeneralUtility::linkThisScript(['id' => (int)$k]);
                    $line['title'] = 'ID: ' . (int)$k;
                    $line['count'] = $pArray[$k . '_']['count'];
                    $line['root_max_val'] = ($pArray[$key]['root_max_val'] > 0 ? $statusCheckedIcon : '&nbsp;');
                    $line['root_min_val'] = ($pArray[$key]['root_min_val'] === 0 ? $statusCheckedIcon : '&nbsp;');
                } else {
                    $line['href'] = '';
                    $line['title'] = '';
                    $line['count'] = '';
                    $line['root_max_val'] = '';
                    $line['root_min_val'] = '';
                }
                $lines[] = $line;
                $lines = $this->renderList($pArray[$k . '.'], $lines, $c + 1);
            }
        }
        return $lines;
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
     * @param QueryBuilder $queryBuilder
     * @param string $tableName
     * @param int $workspaceId
     */
    protected function applyWorkspaceConstraint(
        QueryBuilder $queryBuilder,
        string $tableName,
        int $workspaceId
    ) {
        if (empty($GLOBALS['TCA'][$tableName]['ctrl']['versioningWS'])) {
            return;
        }

        $workspaceIds = [0];
        if ($workspaceId > 0) {
            $workspaceIds[] = $workspaceId;
        }
        $queryBuilder->andWhere(
            $queryBuilder->expr()->in(
                't3ver_wsid',
                $queryBuilder->createNamedParameter($workspaceIds, Connection::PARAM_INT_ARRAY)
            ),
            $queryBuilder->expr()->neq(
                'pid',
                $queryBuilder->createNamedParameter(-1, \PDO::PARAM_INT)
            )
        );
    }

    /**
     * @param string $action
     * @return string
     */
    protected function getHookObjectForAction($action)
    {
        if (!empty($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][self::class][$action])) {
            return $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][self::class][$action];
        }
        return null;
    }

    /**
     * Initializes the internal MOD_MENU array setting and unsetting items based on various conditions. It also merges in external menu items from the global array TBE_MODULES_EXT (see mergeExternalItems())
     * Then MOD_SETTINGS array is cleaned up (see \TYPO3\CMS\Backend\Utility\BackendUtility::getModuleData()) so it contains only valid values. It's also updated with any SET[] values submitted.
     * Also loads the modTSconfig internal variable.
     *
     * @see init(), $MOD_MENU, $MOD_SETTINGS, \TYPO3\CMS\Backend\Utility\BackendUtility::getModuleData(), mergeExternalItems()
     */
    protected function menuConfig()
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
        $this->MOD_SETTINGS = BackendUtility::getModuleData($this->MOD_MENU, GeneralUtility::_GP('SET'), 'web_ts', $this->modMenu_type, $this->modMenu_dontValidateList, $this->modMenu_setDefaultList);
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
        $this->extClassConf = $this->getExternalItemConfig('web_ts', $MM_key, $MS_value);
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
     *
     * @see handleExternalFunctionValue(), \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::insertModuleFunction(), $extObj
     */
    protected function checkExtObj()
    {
        if (is_array($this->extClassConf) && $this->extClassConf['name']) {
            $this->extObj = GeneralUtility::makeInstance($this->extClassConf['name']);
            $this->extObj->init($this);
            // Re-write:
            $this->MOD_SETTINGS = BackendUtility::getModuleData($this->MOD_MENU, GeneralUtility::_GP('SET'), 'web_ts', $this->modMenu_type, $this->modMenu_dontValidateList, $this->modMenu_setDefaultList);
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
     * A header function might be needed to add JavaScript or other stuff in the head.
     * This can't be done in the main function because the head is already written.
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
            $flashMessage = GeneralUtility::makeInstance(
                FlashMessage::class,
                $this->getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang.xlf:no_modules_registered'),
                $this->getLanguageService()->getLL('title'),
                FlashMessage::ERROR
            );
            $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
            /** @var \TYPO3\CMS\Core\Messaging\FlashMessageQueue $defaultFlashMessageQueue */
            $defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
            $defaultFlashMessageQueue->enqueue($flashMessage);
        } else {
            if (is_callable([$this->extObj, 'main'])) {
                $this->content .= $this->extObj->main();
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

    /**
     * @return PageRenderer
     */
    protected function getPageRenderer(): PageRenderer
    {
        return GeneralUtility::makeInstance(PageRenderer::class);
    }
}
