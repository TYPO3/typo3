<?php
namespace TYPO3\CMS\Recordlist;

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
use TYPO3\CMS\Backend\Clipboard\Clipboard;
use TYPO3\CMS\Backend\Module\AbstractModule;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\DocumentTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\TypoScript\TypoScriptService;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Lang\LanguageService;

/**
 * Script Class for the Web > List module; rendering the listing of records on a page
 */
class RecordList extends AbstractModule
{
    /**
     * Page Id for which to make the listing
     *
     * @var int
     */
    public $id;

    /**
     * Pointer - for browsing list of records.
     *
     * @var int
     */
    public $pointer;

    /**
     * Thumbnails or not
     *
     * @var string
     */
    public $imagemode;

    /**
     * Which table to make extended listing for
     *
     * @var string
     */
    public $table;

    /**
     * Search-fields
     *
     * @var string
     */
    public $search_field;

    /**
     * Search-levels
     *
     * @var int
     */
    public $search_levels;

    /**
     * Show-limit
     *
     * @var int
     */
    public $showLimit;

    /**
     * Return URL
     *
     * @var string
     */
    public $returnUrl;

    /**
     * Clear-cache flag - if set, clears page cache for current id.
     *
     * @var bool
     */
    public $clear_cache;

    /**
     * Command: Eg. "delete" or "setCB" (for DataHandler / clipboard operations)
     *
     * @var string
     */
    public $cmd;

    /**
     * Table on which the cmd-action is performed.
     *
     * @var string
     */
    public $cmd_table;

    /**
     * Page select perms clause
     *
     * @var int
     */
    public $perms_clause;

    /**
     * Module TSconfig
     *
     * @var array
     */
    public $modTSconfig;

    /**
     * Current ids page record
     *
     * @var mixed[]|bool
     */
    public $pageinfo;

    /**
     * Document template object
     *
     * @var DocumentTemplate
     */
    public $doc;

    /**
     * Menu configuration
     *
     * @var string[]
     */
    public $MOD_MENU = [];

    /**
     * Module settings (session variable)
     *
     * @var string[]
     */
    public $MOD_SETTINGS = [];

    /**
     * Module output accumulation
     *
     * @var string
     */
    public $content;

    /**
     * The name of the module
     *
     * @var string
     */
    protected $moduleName = 'web_list';

    /**
     * @var string
     */
    public $body = '';

    /**
     * @var PageRenderer
     */
    protected $pageRenderer = null;

    /**
     * @var IconFactory
     */
    protected $iconFactory;

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->getLanguageService()->includeLLFile('EXT:lang/Resources/Private/Language/locallang_mod_web_list.xlf');
        $this->moduleTemplate->getPageRenderer()->loadJquery();
        $this->moduleTemplate->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Recordlist/FieldSelectBox');
        $this->moduleTemplate->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Recordlist/Recordlist');
    }

    /**
     * Initializing the module
     */
    public function init()
    {
        $this->iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        $backendUser = $this->getBackendUserAuthentication();
        $this->perms_clause = $backendUser->getPagePermsClause(1);
        // Get session data
        $sessionData = $backendUser->getSessionData(__CLASS__);
        $this->search_field = !empty($sessionData['search_field']) ? $sessionData['search_field'] : '';
        // GPvars:
        $this->id = (int)GeneralUtility::_GP('id');
        $this->pointer = GeneralUtility::_GP('pointer');
        $this->imagemode = GeneralUtility::_GP('imagemode');
        $this->table = GeneralUtility::_GP('table');
        $this->search_field = GeneralUtility::_GP('search_field');
        $this->search_levels = (int)GeneralUtility::_GP('search_levels');
        $this->showLimit = GeneralUtility::_GP('showLimit');
        $this->returnUrl = GeneralUtility::sanitizeLocalUrl(GeneralUtility::_GP('returnUrl'));
        $this->clear_cache = GeneralUtility::_GP('clear_cache');
        $this->cmd = GeneralUtility::_GP('cmd');
        $this->cmd_table = GeneralUtility::_GP('cmd_table');
        $sessionData['search_field'] = $this->search_field;
        // Initialize menu
        $this->menuConfig();
        // Store session data
        $backendUser->setAndSaveSessionData(self::class, $sessionData);
        $this->getPageRenderer()->addInlineLanguageLabelFile('EXT:lang/Resources/Private/Language/locallang_mod_web_list.xlf');
    }

    /**
     * Initialize function menu array
     */
    public function menuConfig()
    {
        // MENU-ITEMS:
        $this->MOD_MENU = [
            'bigControlPanel' => '',
            'clipBoard' => '',
            'localization' => ''
        ];
        // Loading module configuration:
        $this->modTSconfig = BackendUtility::getModTSconfig($this->id, 'mod.' . $this->moduleName);
        // Clean up settings:
        $this->MOD_SETTINGS = BackendUtility::getModuleData($this->MOD_MENU, GeneralUtility::_GP('SET'), $this->moduleName);
    }

    /**
     * Clears page cache for the current id, $this->id
     */
    public function clearCache()
    {
        if ($this->clear_cache) {
            $tce = GeneralUtility::makeInstance(DataHandler::class);
            $tce->start([], []);
            $tce->clear_cacheCmd($this->id);
        }
    }

    /**
     * Main function, starting the rendering of the list.
     */
    public function main()
    {
        $backendUser = $this->getBackendUserAuthentication();
        $lang = $this->getLanguageService();
        // Loading current page record and checking access:
        $this->pageinfo = BackendUtility::readPageAccess($this->id, $this->perms_clause);
        $access = is_array($this->pageinfo) ? 1 : 0;
        // Start document template object:
        // We need to keep this due to deeply nested dependencies
        $this->doc = GeneralUtility::makeInstance(DocumentTemplate::class);

        $this->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Backend/AjaxDataHandler');
        $calcPerms = $backendUser->calcPerms($this->pageinfo);
        $userCanEditPage = $calcPerms & Permission::PAGE_EDIT && !empty($this->id) && ($backendUser->isAdmin() || (int)$this->pageinfo['editlock'] === 0);
        if ($userCanEditPage) {
            $this->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Backend/PageActions', 'function(PageActions) {
                PageActions.setPageId(' . (int)$this->id . ');
                PageActions.initializePageTitleRenaming();
            }');
        }
        $this->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Recordlist/Tooltip');
        // Apply predefined values for hidden checkboxes
        // Set predefined value for DisplayBigControlPanel:
        if ($this->modTSconfig['properties']['enableDisplayBigControlPanel'] === 'activated') {
            $this->MOD_SETTINGS['bigControlPanel'] = true;
        } elseif ($this->modTSconfig['properties']['enableDisplayBigControlPanel'] === 'deactivated') {
            $this->MOD_SETTINGS['bigControlPanel'] = false;
        }
        // Set predefined value for Clipboard:
        if ($this->modTSconfig['properties']['enableClipBoard'] === 'activated') {
            $this->MOD_SETTINGS['clipBoard'] = true;
        } elseif ($this->modTSconfig['properties']['enableClipBoard'] === 'deactivated') {
            $this->MOD_SETTINGS['clipBoard'] = false;
        } else {
            if ($this->MOD_SETTINGS['clipBoard'] === null) {
                $this->MOD_SETTINGS['clipBoard'] = true;
            }
        }
        // Set predefined value for LocalizationView:
        if ($this->modTSconfig['properties']['enableLocalizationView'] === 'activated') {
            $this->MOD_SETTINGS['localization'] = true;
        } elseif ($this->modTSconfig['properties']['enableLocalizationView'] === 'deactivated') {
            $this->MOD_SETTINGS['localization'] = false;
        }

        // Initialize the dblist object:
        /** @var $dblist RecordList\DatabaseRecordList */
        $dblist = GeneralUtility::makeInstance(RecordList\DatabaseRecordList::class);
        $dblist->script = BackendUtility::getModuleUrl('web_list');
        $dblist->calcPerms = $calcPerms;
        $dblist->thumbs = $backendUser->uc['thumbnailsByDefault'];
        $dblist->returnUrl = $this->returnUrl;
        $dblist->allFields = $this->MOD_SETTINGS['bigControlPanel'] || $this->table ? 1 : 0;
        $dblist->localizationView = $this->MOD_SETTINGS['localization'];
        $dblist->showClipboard = 1;
        $dblist->disableSingleTableView = $this->modTSconfig['properties']['disableSingleTableView'];
        $dblist->listOnlyInSingleTableMode = $this->modTSconfig['properties']['listOnlyInSingleTableView'];
        $dblist->hideTables = $this->modTSconfig['properties']['hideTables'];
        $dblist->hideTranslations = $this->modTSconfig['properties']['hideTranslations'];
        $dblist->tableTSconfigOverTCA = $this->modTSconfig['properties']['table.'];
        $dblist->allowedNewTables = GeneralUtility::trimExplode(',', $this->modTSconfig['properties']['allowedNewTables'], true);
        $dblist->deniedNewTables = GeneralUtility::trimExplode(',', $this->modTSconfig['properties']['deniedNewTables'], true);
        $dblist->newWizards = $this->modTSconfig['properties']['newWizards'] ? 1 : 0;
        $dblist->pageRow = $this->pageinfo;
        $dblist->counter++;
        $dblist->MOD_MENU = ['bigControlPanel' => '', 'clipBoard' => '', 'localization' => ''];
        $dblist->modTSconfig = $this->modTSconfig;
        $clickTitleMode = trim($this->modTSconfig['properties']['clickTitleMode']);
        $dblist->clickTitleMode = $clickTitleMode === '' ? 'edit' : $clickTitleMode;
        if (isset($this->modTSconfig['properties']['tableDisplayOrder.'])) {
            $typoScriptService = GeneralUtility::makeInstance(TypoScriptService::class);
            $dblist->setTableDisplayOrder($typoScriptService->convertTypoScriptArrayToPlainArray($this->modTSconfig['properties']['tableDisplayOrder.']));
        }
        // Clipboard is initialized:
        // Start clipboard
        $dblist->clipObj = GeneralUtility::makeInstance(Clipboard::class);
        // Initialize - reads the clipboard content from the user session
        $dblist->clipObj->initializeClipboard();
        // Clipboard actions are handled:
        // CB is the clipboard command array
        $CB = GeneralUtility::_GET('CB');
        if ($this->cmd === 'setCB') {
            // CBH is all the fields selected for the clipboard, CBC is the checkbox fields which were checked.
            // By merging we get a full array of checked/unchecked elements
            // This is set to the 'el' array of the CB after being parsed so only the table in question is registered.
            $CB['el'] = $dblist->clipObj->cleanUpCBC(array_merge(GeneralUtility::_POST('CBH'), (array)GeneralUtility::_POST('CBC')), $this->cmd_table);
        }
        if (!$this->MOD_SETTINGS['clipBoard']) {
            // If the clipboard is NOT shown, set the pad to 'normal'.
            $CB['setP'] = 'normal';
        }
        // Execute commands.
        $dblist->clipObj->setCmd($CB);
        // Clean up pad
        $dblist->clipObj->cleanCurrent();
        // Save the clipboard content
        $dblist->clipObj->endClipboard();
        // This flag will prevent the clipboard panel in being shown.
        // It is set, if the clickmenu-layer is active AND the extended view is not enabled.
        $dblist->dontShowClipControlPanels = ($dblist->clipObj->current === 'normal' && !$this->modTSconfig['properties']['showClipControlPanelsDespiteOfCMlayers']);
        // If there is access to the page or root page is used for searching, then render the list contents and set up the document template object:
        if ($access || ($this->id === 0 && $this->search_levels !== 0 && $this->search_field !== '')) {
            // Deleting records...:
            // Has not to do with the clipboard but is simply the delete action. The clipboard object is used to clean up the submitted entries to only the selected table.
            if ($this->cmd === 'delete') {
                $items = $dblist->clipObj->cleanUpCBC(GeneralUtility::_POST('CBC'), $this->cmd_table, 1);
                if (!empty($items)) {
                    $cmd = [];
                    foreach ($items as $iK => $value) {
                        $iKParts = explode('|', $iK);
                        $cmd[$iKParts[0]][$iKParts[1]]['delete'] = 1;
                    }
                    $tce = GeneralUtility::makeInstance(DataHandler::class);
                    $tce->start([], $cmd);
                    $tce->process_cmdmap();
                    if (isset($cmd['pages'])) {
                        BackendUtility::setUpdateSignal('updatePageTree');
                    }
                    $tce->printLogErrorMessages();
                }
            }
            // Initialize the listing object, dblist, for rendering the list:
            $this->pointer = max(0, (int)$this->pointer);
            $dblist->start($this->id, $this->table, $this->pointer, $this->search_field, $this->search_levels, $this->showLimit);
            $dblist->setDispFields();
            // Render versioning selector:
            if (ExtensionManagementUtility::isLoaded('version')) {
                $dblist->HTMLcode .= $this->moduleTemplate->getVersionSelector($this->id);
            }
            // Render the list of tables:
            $dblist->generateList();
            $listUrl = $dblist->listURL();
            // Add JavaScript functions to the page:

            $this->moduleTemplate->addJavaScriptCode(
                'RecordListInlineJS',
                '
				function jumpExt(URL,anchor) {	//
					var anc = anchor?anchor:"";
					window.location.href = URL+(T3_THIS_LOCATION?"&returnUrl="+T3_THIS_LOCATION:"")+anc;
					return false;
				}
				function jumpSelf(URL) {	//
					window.location.href = URL+(T3_RETURN_URL?"&returnUrl="+T3_RETURN_URL:"");
					return false;
				}
				function jumpToUrl(URL) {
					window.location.href = URL;
					return false;
				}

				function setHighlight(id) {	//
					top.fsMod.recentIds["web"]=id;
					top.fsMod.navFrameHighlightedID["web"]="pages"+id+"_"+top.fsMod.currentBank;	// For highlighting

					if (top.nav_frame && top.nav_frame.refresh_nav) {
						top.nav_frame.refresh_nav();
					}
				}
				' . $this->moduleTemplate->redirectUrls($listUrl) . '
				' . $dblist->CBfunctions() . '
				function editRecords(table,idList,addParams,CBflag) {	//
					window.location.href="' . BackendUtility::getModuleUrl('record_edit', ['returnUrl' => GeneralUtility::getIndpEnv('REQUEST_URI')]) . '&edit["+table+"]["+idList+"]=edit"+addParams;
				}
				function editList(table,idList) {	//
					var list="";

						// Checking how many is checked, how many is not
					var pointer=0;
					var pos = idList.indexOf(",");
					while (pos!=-1) {
						if (cbValue(table+"|"+idList.substr(pointer,pos-pointer))) {
							list+=idList.substr(pointer,pos-pointer)+",";
						}
						pointer=pos+1;
						pos = idList.indexOf(",",pointer);
					}
					if (cbValue(table+"|"+idList.substr(pointer))) {
						list+=idList.substr(pointer)+",";
					}

					return list ? list : idList;
				}

				if (top.fsMod) top.fsMod.recentIds["web"] = ' . (int)$this->id . ';
			'
            );

            // Setting up the context sensitive menu:
            $this->moduleTemplate->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Backend/ContextMenu');
        }
        // access
        // Begin to compile the whole page, starting out with page header:
        if (!$this->id) {
            $title = $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'];
        } else {
            $title = $this->pageinfo['title'];
        }
        $this->body = $this->moduleTemplate->header($title);
        $this->moduleTemplate->setTitle($title);

        if (!empty($dblist->HTMLcode)) {
            $output = $dblist->HTMLcode;
        } else {
            $output = '';
            $flashMessage = GeneralUtility::makeInstance(
                FlashMessage::class,
                $lang->getLL('noRecordsOnThisPage'),
                '',
                FlashMessage::INFO
            );
            /** @var $flashMessageService \TYPO3\CMS\Core\Messaging\FlashMessageService */
            $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
            /** @var $defaultFlashMessageQueue \TYPO3\CMS\Core\Messaging\FlashMessageQueue */
            $defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
            $defaultFlashMessageQueue->enqueue($flashMessage);
        }

        $this->body .= '<form action="' . htmlspecialchars($dblist->listURL()) . '" method="post" name="dblistForm">';
        $this->body .= $output;
        $this->body .= '<input type="hidden" name="cmd_table" /><input type="hidden" name="cmd" /></form>';
        // If a listing was produced, create the page footer with search form etc:
        if ($dblist->HTMLcode) {
            // Making field select box (when extended view for a single table is enabled):
            if ($dblist->table) {
                $this->body .= $dblist->fieldSelectBox($dblist->table);
            }
            // Adding checkbox options for extended listing and clipboard display:
            $this->body .= '

					<!--
						Listing options for extended view, clipboard and localization view
					-->
					<div class="typo3-listOptions">
						<form action="" method="post">';

            // Add "display bigControlPanel" checkbox:
            if ($this->modTSconfig['properties']['enableDisplayBigControlPanel'] === 'selectable') {
                $this->body .= '<div class="checkbox">' .
                    '<label for="checkLargeControl">' .
                    BackendUtility::getFuncCheck($this->id, 'SET[bigControlPanel]', $this->MOD_SETTINGS['bigControlPanel'], '', $this->table ? '&table=' . $this->table : '', 'id="checkLargeControl"') .
                    BackendUtility::wrapInHelp('xMOD_csh_corebe', 'list_options', htmlspecialchars($lang->getLL('largeControl'))) .
                    '</label>' .
                    '</div>';
            }

            // Add "clipboard" checkbox:
            if ($this->modTSconfig['properties']['enableClipBoard'] === 'selectable') {
                if ($dblist->showClipboard) {
                    $this->body .= '<div class="checkbox">' .
                        '<label for="checkShowClipBoard">' .
                        BackendUtility::getFuncCheck($this->id, 'SET[clipBoard]', $this->MOD_SETTINGS['clipBoard'], '', $this->table ? '&table=' . $this->table : '', 'id="checkShowClipBoard"') .
                        BackendUtility::wrapInHelp('xMOD_csh_corebe', 'list_options', htmlspecialchars($lang->getLL('showClipBoard'))) .
                        '</label>' .
                        '</div>';
                }
            }

            // Add "localization view" checkbox:
            if ($this->modTSconfig['properties']['enableLocalizationView'] === 'selectable') {
                $this->body .= '<div class="checkbox">' .
                    '<label for="checkLocalization">' .
                    BackendUtility::getFuncCheck($this->id, 'SET[localization]', $this->MOD_SETTINGS['localization'], '', $this->table ? '&table=' . $this->table : '', 'id="checkLocalization"') .
                    BackendUtility::wrapInHelp('xMOD_csh_corebe', 'list_options', htmlspecialchars($lang->getLL('localization'))) .
                    '</label>' .
                    '</div>';
            }

            $this->body .= '
						</form>
					</div>';
        }
        // Printing clipboard if enabled
        if ($this->MOD_SETTINGS['clipBoard'] && $dblist->showClipboard && ($dblist->HTMLcode || $dblist->clipObj->hasElements())) {
            $this->body .= '<div class="db_list-dashboard">' . $dblist->clipObj->printClipboard() . '</div>';
        }
        // Additional footer content
        $footerContentHook = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['recordlist/Modules/Recordlist/index.php']['drawFooterHook'];
        if (is_array($footerContentHook)) {
            foreach ($footerContentHook as $hook) {
                $params = [];
                $this->body .= GeneralUtility::callUserFunction($hook, $params, $this);
            }
        }
        // Setting up the buttons for docheader
        $dblist->getDocHeaderButtons($this->moduleTemplate);
        // searchbox toolbar
        if (!$this->modTSconfig['properties']['disableSearchBox'] && ($dblist->HTMLcode || !empty($dblist->searchString))) {
            $this->content = $dblist->getSearchBox();
            $this->moduleTemplate->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Backend/ToggleSearchToolbox');

            $searchButton = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar()->makeLinkButton();
            $searchButton
                ->setHref('#')
                ->setClasses('t3js-toggle-search-toolbox')
                ->setTitle($lang->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:labels.title.searchIcon'))
                ->setIcon($this->iconFactory->getIcon('actions-search', Icon::SIZE_SMALL));
            $this->moduleTemplate->getDocHeaderComponent()->getButtonBar()->addButton(
                $searchButton,
                ButtonBar::BUTTON_POSITION_LEFT,
                90
            );
        }

        if ($this->pageinfo) {
            $this->moduleTemplate->getDocHeaderComponent()->setMetaInformation($this->pageinfo);
        }

        // Build the <body> for the module
        $this->content .= $this->body;
    }

    /**
     * Injects the request object for the current request or subrequest
     * Simply calls main() and init() and outputs the content
     *
     * @param ServerRequestInterface $request the current request
     * @param ResponseInterface $response
     * @return ResponseInterface the response with the content
     */
    public function mainAction(ServerRequestInterface $request, ResponseInterface $response)
    {
        BackendUtility::lockRecords();
        $GLOBALS['SOBE'] = $this;
        $this->init();
        $this->clearCache();
        $this->main();
        $this->moduleTemplate->setContent($this->content);
        $response->getBody()->write($this->moduleTemplate->renderContent());
        return $response;
    }

    /**
     * @return ModuleTemplate
     */
    public function getModuleTemplate()
    {
        return $this->moduleTemplate;
    }

    /**
     * @return BackendUserAuthentication
     */
    protected function getBackendUserAuthentication()
    {
        return $GLOBALS['BE_USER'];
    }

    /**
     * @return LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }

    /**
     * @return PageRenderer
     */
    protected function getPageRenderer()
    {
        if ($this->pageRenderer === null) {
            $this->pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
        }

        return $this->pageRenderer;
    }
}
