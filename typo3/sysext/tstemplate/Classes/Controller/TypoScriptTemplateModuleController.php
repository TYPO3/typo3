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
use TYPO3\CMS\Backend\Template\DocumentTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\TypoScript\ExtendedTemplateService;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Fluid\ViewHelpers\Be\InfoboxViewHelper;

/**
 * Module: TypoScript Tools
 *
 * $TYPO3_CONF_VARS["MODS"]["web_ts"]["onlineResourceDir"]  = Directory of default resources. Eg. "fileadmin/res/" or so.
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
            $tce->stripslashes_values = false;
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
        // Template markers
        $markers = [
            'CSH' => '',
            'FUNC_MENU' => '',
            'CONTENT' => ''
        ];

        // Access check...
        // The page will show only if there is a valid page and if this page may be viewed by the user
        $this->pageinfo = BackendUtility::readPageAccess($this->id, $this->perms_clause);
        $this->access = is_array($this->pageinfo);

        /** @var DocumentTemplate doc */
        $this->doc = GeneralUtility::makeInstance(DocumentTemplate::class);
        $this->moduleTemplate->getPageRenderer()->addCssFile(ExtensionManagementUtility::extRelPath('tstemplate') . 'Resources/Public/Css/styles.css');

        $lang = $this->getLanguageService();

        if ($this->id && $this->access) {
            $urlParameters = [
                'id' => $this->id,
                'template' => 'all'
            ];
            $aHref = BackendUtility::getModuleUrl('web_ts', $urlParameters);

            // JavaScript
            $this->moduleTemplate->addJavaScriptCode(
                'TSTemplateInlineJS', '
                function uFormUrl(aname) {
                    document.forms[0].action = ' . GeneralUtility::quoteJSvalue(($aHref . '#')) . '+aname;
                }
                function brPoint(lnumber,t) {
                    window.location.href = ' . GeneralUtility::quoteJSvalue(($aHref . '&SET[function]=TYPO3\\CMS\\Tstemplate\\Controller\\TypoScriptTemplateObjectBrowserModuleFunctionController&SET[ts_browser_type]=')) . '+(t?"setup":"const")+"&breakPointLN="+lnumber;
                    return false;
                }
                if (top.fsMod) top.fsMod.recentIds["web"] = ' . $this->id . ';
            ');
            $this->moduleTemplate->getPageRenderer()->addCssInlineBlock(
                'TSTemplateInlineStyle', '
                TABLE#typo3-objectBrowser { width: 100%; margin-bottom: 24px; }
                TABLE#typo3-objectBrowser A { text-decoration: none; }
                TABLE#typo3-objectBrowser .comment { color: maroon; font-weight: bold; }
                .ts-typoscript { width: 100%; }
                .tsob-search-submit {margin-left: 3px; margin-right: 3px;}
                .tst-analyzer-options { margin:5px 0; }
            ');
            // Setting up the context sensitive menu:
            $this->moduleTemplate->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Backend/ClickMenu');
            // Build the module content
            $this->content = '<form action="' . htmlspecialchars($aHref) . '" method="post" enctype="multipart/form-data" id="TypoScriptTemplateModuleController" name="editForm" class="form">';
            $this->content .= $this->doc->header($lang->getLL('moduleTitle'));
            $this->extObjContent();
            // Setting up the buttons and markers for docheader
            $this->getButtons();
            $this->generateMenu();
            $this->content .= '</form>';
        } else {
            // Template pages:
            $records = $this->getDatabaseConnection()->exec_SELECTgetRows(
                'pages.uid, count(*) AS count, max(sys_template.root) AS root_max_val, min(sys_template.root) AS root_min_val',
                'pages,sys_template',
                'pages.uid=sys_template.pid'
                    . BackendUtility::deleteClause('pages')
                    . BackendUtility::versioningPlaceholderClause('pages')
                    . BackendUtility::deleteClause('sys_template')
                    . BackendUtility::versioningPlaceholderClause('sys_template'),
                'pages.uid',
                'pages.pid, pages.sorting'
            );
            $pArray = [];
            foreach ($records as $record) {
                $this->setInPageArray($pArray, BackendUtility::BEgetRootLine($record['uid'], 'AND 1=1'), $record);
            }

            $table = '<div class="table-fit"><table class="table table-striped table-hover" id="ts-overview">' .
                    '<thead>' .
                    '<tr>' .
                    '<th>' . $lang->getLL('pageName') . '</th>' .
                    '<th>' . $lang->getLL('templates') . '</th>' .
                    '<th>' . $lang->getLL('isRoot') . '</th>' .
                    '<th>' . $lang->getLL('isExt') . '</th>' .
                    '</tr>' .
                    '</thead>' .
                    '<tbody>' . implode('', $this->renderList($pArray)) . '</tbody>' .
                    '</table></div>';

            $this->content = $this->doc->header($lang->getLL('moduleTitle'));
            $this->content .= '<div><p class="lead">' . $lang->getLL('overview') . '</p>' . $table . '</div>';

            // RENDER LIST of pages with templates, END
            // Setting up the buttons and markers for docheader
            $this->getButtons();
        }
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
     * Print content
     *
     * @return void
     * @deprecated since TYPO3 CMS 7, will be removed in TYPO3 CMS 8
     */
    public function printContent()
    {
        GeneralUtility::logDeprecatedFunction();
        echo $this->content;
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
                ->setOnClick(BackendUtility::viewOnClick($this->pageinfo['uid'], '', BackendUtility::BEgetRootLine($this->pageinfo['uid'])))
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
                        ->setIcon($this->moduleTemplate->getIconFactory()->getIcon('actions-document-save', Icon::SIZE_SMALL))
                        ->setTitle($lang->sL('LLL:EXT:lang/locallang_core.xlf:rm.saveDoc'));

                    $saveAndCloseButton = $buttonBar->makeInputButton()
                        ->setName('_saveandclosedok')
                        ->setValue('1')
                        ->setForm('TypoScriptTemplateModuleController')
                        ->setTitle($lang->sL('LLL:EXT:lang/locallang_core.xlf:rm.saveCloseDoc'))
                        ->setIcon($this->moduleTemplate->getIconFactory()->getIcon('actions-document-save-close', Icon::SIZE_SMALL));

                    $splitButtonElement = $buttonBar->makeSplitButton()
                        ->addItem($saveButton)
                        ->addItem($saveAndCloseButton);

                    $buttonBar->addButton($splitButtonElement, ButtonBar::BUTTON_POSITION_LEFT, 3);

                    // CLOSE button
                    $closeButton = $buttonBar->makeLinkButton()
                        ->setHref(BackendUtility::getModuleUrl('web_ts', ['id' => $this->id]))
                        ->setTitle($lang->sL('LLL:EXT:lang/locallang_core.xlf:rm.closeDoc'))
                        ->setIcon($this->moduleTemplate->getIconFactory()->getIcon('actions-document-close', Icon::SIZE_SMALL));
                    $buttonBar->addButton($closeButton);
                } else {
                    $newButton = $buttonBar->makeLinkButton()
                        ->setHref(BackendUtility::getModuleUrl('web_ts', $urlParameters))
                        ->setTitle($lang->sL('LLL:EXT:lang/locallang_core.xlf:db_new.php.pagetitle'))
                        ->setIcon($this->moduleTemplate->getIconFactory()->getIcon('actions-document-new', Icon::SIZE_SMALL));
                    $buttonBar->addButton($newButton);
                }
            } elseif ($this->extClassConf['name'] === TypoScriptTemplateConstantEditorModuleFunctionController::class && !empty($this->MOD_MENU['constant_editor_cat'])) {
                // SAVE button
                $saveButton = $buttonBar->makeInputButton()
                    ->setName('_savedok')
                    ->setValue('1')
                    ->setForm('TypoScriptTemplateModuleController')
                    ->setTitle($lang->sL('LLL:EXT:lang/locallang_core.xlf:rm.saveDoc'))
                    ->setIcon($this->moduleTemplate->getIconFactory()->getIcon('actions-document-save', Icon::SIZE_SMALL))
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
                        ->setIcon($this->moduleTemplate->getIconFactory()->getIcon('actions-view-go-back', Icon::SIZE_SMALL));
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
        // Defined global here!
        /** @var ExtendedTemplateService $tmpl */
        $tmpl = GeneralUtility::makeInstance(ExtendedTemplateService::class);
        $GLOBALS['tmpl'] = $tmpl;

        // Do not log time-performance information
        $tmpl->tt_track = false;
        $tmpl->init();

        $lang = $this->getLanguageService();

        $title = $lang->getLL('noTemplate');
        $message = '<p>' . $lang->getLL('noTemplateDescription') . '<br />' . $lang->getLL('createTemplateToEditConfiguration') . '</p>';

        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName('EXT:tstemplate/Resources/Private/Templates/InfoBox.html'));
        $view->assignMultiple([
            'title' => $title,
            'message' => $message,
            'state' => InfoboxViewHelper::STATE_INFO
        ]);
        $theOutput = $view->render();

        // New standard?
        if ($newStandardTemplate) {
            // Hook to change output, implemented for statictemplates
            if (isset(
                $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][self::class]['newStandardTemplateView']
            )) {
                $selector = '';
                $staticsText = '';
                $reference = [
                    'selectorHtml' => &$selector,
                    'staticsText' => &$staticsText
                ];
                GeneralUtility::callUserFunction(
                    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][self::class]['newStandardTemplateView'],
                    $reference,
                    $this
                );
                $selector = $reference['selectorHtml'];
                $staticsText = $reference['staticsText'];
            } else {
                $selector = '<input type="hidden" name="createStandard" value="" />';
                $staticsText = '';
            }
            // Extension?
            $theOutput .= '<h2>' . $lang->getLL('newWebsite', true) . $staticsText . '</h2>';
            $theOutput .= '<div><p>' . $lang->getLL('newWebsiteDescription') . '</p>' . $selector
                . '<input class="btn btn-primary" type="submit" form="TypoScriptTemplateModuleController" name="newWebsite" value="'
                . $lang->getLL('newWebsiteAction') . '" /></div>';
        }
        // Extension?
        $theOutput .= '<h2>' . $lang->getLL('extTemplate') . '</h2>';
        $theOutput .= '<div><p>' . $lang->getLL('extTemplateDescription') . '</p>' . '<input class="btn btn-default" type="submit" form="TypoScriptTemplateModuleController" name="createExtension" value="' . $lang->getLL('extTemplateAction') . '" /></div>';

        // Go to first appearing...
        $first = $tmpl->ext_prevPageWithTemplate($this->id, $this->perms_clause);
        if ($first) {
            $urlParameters = [
                'id' => $first['uid']
            ];
            $aHref = BackendUtility::getModuleUrl('web_ts', $urlParameters);
            $theOutput .= '<h3>' . $lang->getLL('goToClosest') . '</h3>';
            $theOutput .= '<div>' . sprintf('<p>' . $lang->getLL('goToClosestDescription') . '</p>%s' . $lang->getLL('goToClosestAction') . '%s', htmlspecialchars($first['title']), $first['uid'], '<a class="btn btn-default" href="' . htmlspecialchars($aHref) . '">', '</a>') . '</div>';
        }
        return $theOutput;
    }

    /**
     * Render template menu
     *
     * @return string
     */
    public function templateMenu()
    {
        /** @var ExtendedTemplateService $tmpl */
        $tmpl = GeneralUtility::makeInstance(ExtendedTemplateService::class);
        $GLOBALS['tmpl'] = $tmpl;

        // Do not log time-performance information
        $tmpl->tt_track = false;
        $tmpl->init();

        $all = $tmpl->ext_getAllTemplates($this->id, $this->perms_clause);
        if (count($all) > 1) {
            $this->MOD_MENU['templatesOnPage'] = [];
            foreach ($all as $d) {
                $this->MOD_MENU['templatesOnPage'][$d['uid']] = $d['title'];
            }
        }
        $this->MOD_SETTINGS = BackendUtility::getModuleData($this->MOD_MENU, GeneralUtility::_GP('SET'), $this->MCONF['name'], $this->modMenu_type, $this->modMenu_dontValidateList, $this->modMenu_setDefaultList);
        return BackendUtility::getFuncMenu($this->id, 'SET[templatesOnPage]', $this->MOD_SETTINGS['templatesOnPage'], $this->MOD_MENU['templatesOnPage']);
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
        $tce->stripslashes_values = false;

        if (GeneralUtility::_GP('createExtension')) {
            $recData['sys_template']['NEW'] = [
                'pid' => $actTemplateId ? -1 * $actTemplateId : $id,
                'title' => '+ext'
            ];
            $tce->start($recData, []);
            $tce->process_datamap();
        } elseif (GeneralUtility::_GP('newWebsite')) {
            // Hook to handle row data, implemented for statictemplates
            if (isset(
                $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][self::class]['newStandardTemplateHandler']
            )) {
                $reference = [
                    'recData' => &$recData,
                    'id' => $id,
                ];
                GeneralUtility::callUserFunction(
                    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][self::class]['newStandardTemplateHandler'],
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
            if (!isset($pArray[$cEl['uid'] . '.'])) {
                $pArray[$cEl['uid'] . '.'] = [];
            }
            $this->setInPageArray($pArray[$cEl['uid'] . '.'], $rlArr, $row);
        } else {
            $pArray[$cEl['uid'] . '_'] = $row;
        }
    }

    /**
     * Render the list
     *
     * @param array $pArray
     * @param array $lines
     * @param int $c
     * @return array
     */
    public function renderList($pArray, $lines = [], $c = 0)
    {
        static $i;

        if (!is_array($pArray)) {
            return $lines;
        }

        $statusCheckedIcon = $this->moduleTemplate->getIconFactory()->getIcon('status-status-checked', Icon::SIZE_SMALL)->render();
        foreach ($pArray as $k => $v) {
            if (MathUtility::canBeInterpretedAsInteger($k)) {
                if (isset($pArray[$k . '_'])) {
                    $lines[] = '<tr class="' . ($i++ % 2 == 0 ? 'bgColor4' : 'bgColor6') . '">
						<td nowrap><span style="width: 1px; height: 1px; display:inline-block; margin-left: ' . $c * 20 . 'px"></span>' . '<a href="' . htmlspecialchars(GeneralUtility::linkThisScript(['id' => $k])) . '" title="' . htmlspecialchars('ID: ' . $k) . '">' . $this->moduleTemplate->getIconFactory()->getIconForRecord('pages', BackendUtility::getRecordWSOL('pages', $k), Icon::SIZE_SMALL)->render() . GeneralUtility::fixed_lgd_cs($pArray[$k], 30) . '</a></td>
						<td>' . $pArray[$k . '_']['count'] . '</td>
						<td>' . ($pArray[$k . '_']['root_max_val'] > 0 ? $statusCheckedIcon : '&nbsp;') . '</td>
						<td>' . ($pArray[$k . '_']['root_min_val'] == 0 ? $statusCheckedIcon : '&nbsp;') . '</td>
						</tr>';
                } else {
                    $lines[] = '<tr class="' . ($i++ % 2 == 0 ? 'bgColor4' : 'bgColor6') . '">
						<td nowrap><span style="width: 1px; height: 1px; display:inline-block; margin-left: ' . $c * 20 . 'px"></span>' . $this->moduleTemplate->getIconFactory()->getIconForRecord('pages', BackendUtility::getRecordWSOL('pages', $k), Icon::SIZE_SMALL)->render() . GeneralUtility::fixed_lgd_cs($pArray[$k], 30) . '</td>
						<td></td>
						<td></td>
						<td></td>
						</tr>';
                }
                $lines = $this->renderList($pArray[$k . '.'], $lines, $c + 1);
            }
        }
        return $lines;
    }
}
