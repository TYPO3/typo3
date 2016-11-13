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
use TYPO3\CMS\Backend\Module\BaseScriptClass;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\BackendWorkspaceRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\TypoScript\ExtendedTemplateService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Fluid\ViewHelpers\Be\InfoboxViewHelper;

/**
 * Module: TypoScript Tools
 */
class TypoScriptTemplateModuleController extends BaseScriptClass
{
    /**
     * @var string
     */
    public $perms_clause;

    /**
     * @var string
     */
    public $e;

    /**
     * @var string
     */
    public $sObj;

    /**
     * @var string
     */
    public $edit;

    /**
     * @var string
     */
    public $textExtensions = 'html,htm,txt,css,tmpl,inc,js';

    /**
     * @var string
     */
    public $modMenu_type = '';

    /**
     * @var string
     */
    public $modMenu_dontValidateList = '';

    /**
     * @var string
     */
    public $modMenu_setDefaultList = '';

    /**
     * @var array
     */
    public $pageinfo = [];

    /**
     * @var bool
     */
    public $access = false;

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
     * Constructor
     */
    public function __construct()
    {
        $this->moduleTemplate = GeneralUtility::makeInstance(ModuleTemplate::class);
        $this->getLanguageService()->includeLLFile('EXT:tstemplate/Resources/Private/Language/locallang.xlf');

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
     *
     * @return void
     */
    public function init()
    {
        parent::init();
        $this->id = (int)GeneralUtility::_GP('id');
        $this->e = GeneralUtility::_GP('e');
        $this->sObj = GeneralUtility::_GP('sObj');
        $this->edit = GeneralUtility::_GP('edit');
        $this->perms_clause = $this->getBackendUser()->getPagePermsClause(1);
    }

    /**
     * Clear cache
     *
     * @return void
     */
    public function clearCache()
    {
        if (GeneralUtility::_GP('clear_all_cache')) {
            /** @var DataHandler $tce */
            $tce = GeneralUtility::makeInstance(DataHandler::class);
            $tce->start([], []);
            $tce->clear_cacheCmd('all');
        }
    }

    /**
     * Main
     *
     * @return void
     */
    public function main()
    {
        // Access check...
        // The page will show only if there is a valid page and if this page may be viewed by the user
        $this->pageinfo = BackendUtility::readPageAccess($this->id, $this->perms_clause);
        $this->access = is_array($this->pageinfo);
        $view = $this->getFluidTemplateObject('tstemplate');

        if ($this->id && $this->access) {
            $urlParameters = [
                'id' => $this->id,
                'template' => 'all'
            ];
            $aHref = BackendUtility::getModuleUrl('web_ts', $urlParameters);

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
            $this->moduleTemplate->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Backend/ClickMenu');
            // Build the module content
            $view->assign('actionName', $aHref);
            $view->assign('typoscriptTemplateModuleContent', $this->getExtObjContent());
            // Setting up the buttons and markers for docheader
            $this->getButtons();
            $this->generateMenu();
        } else {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
            $queryBuilder->getRestrictions()
                ->removeAll()
                ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
                ->add(GeneralUtility::makeInstance(BackendWorkspaceRestriction::class));

            $result = $queryBuilder->select('pages.uid')
                ->addSelectLiteral(
                    $queryBuilder->expr()->count('*', 'count'),
                    $queryBuilder->expr()->max('sys_template.root', 'root_max_val'),
                    $queryBuilder->expr()->min('sys_template.root', 'root_min_val')
                )
                ->from('pages')
                ->from('sys_template')
                ->where($queryBuilder->expr()->eq('pages.uid', $queryBuilder->quoteIdentifier('sys_template.pid')))
                ->groupBy('pages.uid')
                ->orderBy('pages.pid')
                ->addOrderBy('pages.sorting')
                ->execute();

            $pArray = [];
            while ($record = $result->fetch()) {
                $this->setInPageArray($pArray, BackendUtility::BEgetRootLine($record['uid'], 'AND 1=1'), $record);
            }

            $view->getRenderingContext()->setControllerAction('PageZero');
            $view->assign('templateList', $this->renderList($pArray));

            // RENDER LIST of pages with templates, END
            // Setting up the buttons and markers for docheader
            $this->getButtons();
        }
        $this->content = $view->render();
    }

    /**
     * Generates the menu based on $this->MOD_MENU
     *
     * @return void
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
                    BackendUtility::getModuleUrl(
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
     * @param ResponseInterface $response
     * @return ResponseInterface the response with the content
     */
    public function mainAction(ServerRequestInterface $request, ResponseInterface $response)
    {
        $GLOBALS['SOBE'] = $this;
        $this->init();

        // Checking for first level external objects
        $this->checkExtObj();

        $this->clearCache();
        $this->main();

        $this->moduleTemplate->setContent($this->content);
        $response->getBody()->write($this->moduleTemplate->renderContent());
        return $response;
    }

    /**
     * Create the panel of buttons for submitting the form or otherwise perform operations.
     *
     * @return array All available buttons as an assoc. array
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
                ->setTitle($lang->sL('LLL:EXT:lang/locallang_core.xlf:labels.showPage'))
                ->setIcon($this->moduleTemplate->getIconFactory()->getIcon('actions-document-view', Icon::SIZE_SMALL));
            $buttonBar->addButton($viewButton, ButtonBar::BUTTON_POSITION_LEFT, 99);
            if ($this->extClassConf['name'] === TypoScriptTemplateInformationModuleFunctionController::class) {
                // NEW button
                $urlParameters = [
                    'id' => $this->id,
                    'template' => 'all',
                    'createExtension' => 'new'
                ];

                if (!empty($this->e) && !GeneralUtility::_POST('_saveandclosedok')) {
                    $saveButton = $buttonBar->makeInputButton()
                        ->setName('_savedok')
                        ->setValue('1')
                        ->setForm('TypoScriptTemplateModuleController')
                        ->setIcon($this->moduleTemplate->getIconFactory()->getIcon(
                            'actions-document-save',
                            Icon::SIZE_SMALL
                        ))
                        ->setTitle($lang->sL('LLL:EXT:lang/locallang_core.xlf:rm.saveDoc'));

                    $saveAndCloseButton = $buttonBar->makeInputButton()
                        ->setName('_saveandclosedok')
                        ->setValue('1')
                        ->setForm('TypoScriptTemplateModuleController')
                        ->setTitle($lang->sL('LLL:EXT:lang/locallang_core.xlf:rm.saveCloseDoc'))
                        ->setIcon($this->moduleTemplate->getIconFactory()->getIcon(
                            'actions-document-save-close',
                            Icon::SIZE_SMALL
                        ));

                    $splitButtonElement = $buttonBar->makeSplitButton()
                        ->addItem($saveButton)
                        ->addItem($saveAndCloseButton);

                    $buttonBar->addButton($splitButtonElement, ButtonBar::BUTTON_POSITION_LEFT, 3);

                    // CLOSE button
                    $closeButton = $buttonBar->makeLinkButton()
                        ->setHref(BackendUtility::getModuleUrl('web_ts', ['id' => $this->id]))
                        ->setTitle($lang->sL('LLL:EXT:lang/locallang_core.xlf:rm.closeDoc'))
                        ->setIcon($this->moduleTemplate->getIconFactory()->getIcon(
                            'actions-document-close',
                            Icon::SIZE_SMALL
                        ));
                    $buttonBar->addButton($closeButton);
                } else {
                    $newButton = $buttonBar->makeLinkButton()
                        ->setHref(BackendUtility::getModuleUrl('web_ts', $urlParameters))
                        ->setTitle($lang->sL('LLL:EXT:lang/locallang_core.xlf:db_new.php.pagetitle'))
                        ->setIcon($this->moduleTemplate->getIconFactory()->getIcon(
                            'actions-document-new',
                            Icon::SIZE_SMALL
                        ));
                    $buttonBar->addButton($newButton);
                }
            } elseif ($this->extClassConf['name'] === TypoScriptTemplateConstantEditorModuleFunctionController::class
                && !empty($this->MOD_MENU['constant_editor_cat'])) {
                // SAVE button
                $saveButton = $buttonBar->makeInputButton()
                    ->setName('_savedok')
                    ->setValue('1')
                    ->setForm('TypoScriptTemplateModuleController')
                    ->setTitle($lang->sL('LLL:EXT:lang/locallang_core.xlf:rm.saveDoc'))
                    ->setIcon($this->moduleTemplate->getIconFactory()->getIcon(
                        'actions-document-save',
                        Icon::SIZE_SMALL
                    ))
                    ->setShowLabelText(true);
                $buttonBar->addButton($saveButton);
            } elseif ($this->extClassConf['name'] === TypoScriptTemplateObjectBrowserModuleFunctionController::class) {
                if (!empty($this->sObj)) {
                    // BACK
                    $urlParameters = [
                        'id' => $this->id
                    ];
                    $backButton = $buttonBar->makeLinkButton()
                        ->setHref(BackendUtility::getModuleUrl('web_ts', $urlParameters))
                        ->setClasses('typo3-goBack')
                        ->setTitle($lang->sL('LLL:EXT:lang/locallang_core.xlf:labels.goBack'))
                        ->setIcon($this->moduleTemplate->getIconFactory()->getIcon(
                            'actions-view-go-back',
                            Icon::SIZE_SMALL
                        ));
                    $buttonBar->addButton($backButton);
                }
            }
        }
        // Shortcut
        $shortcutButton = $buttonBar->makeShortcutButton()
            ->setModuleName($this->MCONF['name'])
            ->setGetVariables(['id', 'M']);
        $buttonBar->addButton($shortcutButton);
    }

    // OTHER FUNCTIONS:
    /**
     * Wrap title for link in template
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
        $aHref = BackendUtility::getModuleUrl('web_ts', $urlParameters);
        if ($onlyKey) {
            $title = '<a href="' . htmlspecialchars(($aHref . '&e[' . $onlyKey . ']=1&SET[function]=TYPO3\\CMS\\Tstemplate\\Controller\\TypoScriptTemplateInformationModuleFunctionController')) . '">' . htmlspecialchars($title) . '</a>';
        } else {
            $title = '<a href="' . htmlspecialchars(($aHref . '&e[constants]=1&e[config]=1&SET[function]=TYPO3\\CMS\\Tstemplate\\Controller\\TypoScriptTemplateInformationModuleFunctionController')) . '">' . htmlspecialchars($title) . '</a>';
        }
        return $title;
    }

    /**
     * No template
     *
     * @param int $newStandardTemplate
     * @return string
     */
    public function noTemplate($newStandardTemplate = 0)
    {
        $this->templateService = GeneralUtility::makeInstance(ExtendedTemplateService::class);
        $this->templateService->init();

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
            $previousPage['aHref'] = BackendUtility::getModuleUrl('web_ts', $urlParameters);
            $moduleContent['previousPage'] = $previousPage;
        }
        $view = $this->getFluidTemplateObject('tstemplate', 'NoTemplate');
        $view->assign('content', $moduleContent);
        return $view->render();
    }

    /**
     * Render template menu
     *
     * @return string
     */
    public function templateMenu()
    {
        $this->templateService = GeneralUtility::makeInstance(ExtendedTemplateService::class);
        $this->templateService->init();

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
            $this->MCONF['name'],
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
     * Create template
     *
     * @param int $id
     * @param int $actTemplateId
     * @return string
     */
    public function createTemplate($id, $actTemplateId = 0)
    {
        $recData = [];
        /** @var DataHandler $tce */
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

    // RENDER LIST of pages with templates, BEGIN
    /**
     * Set page in array
     *
     * @param array $pArray
     * @param array $rlArr
     * @param array $row
     * @return void
     */
    public function setInPageArray(&$pArray, $rlArr, $row)
    {
        ksort($rlArr);
        reset($rlArr);
        if (!$rlArr[0]['uid']) {
            array_shift($rlArr);
        }
        $cEl = current($rlArr);
        $pArray[$cEl['uid']] = htmlspecialchars($cEl['title']);
        array_shift($rlArr);
        if (!empty($rlArr)) {
            $key = $cEl['uid'] . '.';
            if (empty($pArray[$key])) {
                $pArray[$key] = [];
            }
            $this->setInPageArray($pArray[$key], $rlArr, $row);
        } else {
            $key = $cEl['uid'] . '_';
            $pArray[$key] = $row;
        }
    }

    /**
     * Get the list
     *
     * @param array $pArray
     * @param array $lines
     * @param int $c
     * @return array
     */
    public function renderList($pArray, $lines = [], $c = 0)
    {
        if (!is_array($pArray)) {
            return $lines;
        }

        $statusCheckedIcon = $this->moduleTemplate->getIconFactory()
            ->getIcon('status-status-checked', Icon::SIZE_SMALL)->render();
        $i = 0;
        foreach ($pArray as $k => $v) {
            if (MathUtility::canBeInterpretedAsInteger($k)) {
                $line = [];
                $key = $k . '_';
                $line['marginLeft'] = $c * 20;
                $line['class'] = ($i++ % 2 === 0 ? 'bgColor4' : 'bgColor6');
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
        /** @var StandaloneView $view */
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->getRenderingContext()->getTemplatePaths()->fillDefaultsByPackageName($extensionName);
        $view->getRenderingContext()->setControllerAction($templateName);
        $view->getRequest()->setControllerExtensionName('tstemplate');
        return $view;
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
}
