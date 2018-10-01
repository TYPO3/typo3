<?php
namespace TYPO3\CMS\Recordlist\Controller;

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
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\DocumentTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Compatibility\PublicMethodDeprecationTrait;
use TYPO3\CMS\Core\Compatibility\PublicPropertyDeprecationTrait;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\BackendWorkspaceRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Site\Entity\SiteInterface;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\TypoScript\TypoScriptService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Recordlist\RecordList\DatabaseRecordList;

/**
 * Script Class for the Web > List module; rendering the listing of records on a page
 * @internal This class is a specific Backend controller implementation and is not part of the TYPO3's Core API.
 */
class RecordListController
{
    use PublicPropertyDeprecationTrait;
    use PublicMethodDeprecationTrait;

    /**
     * @var array
     */
    private $deprecatedPublicProperties = [
        'id' => 'Using RecordListController::$id is deprecated and will not be possible anymore in TYPO3 v10.0.',
        'pointer' => 'Using RecordListController::$pointer is deprecated and will not be possible anymore in TYPO3 v10.0.',
        'table' => 'Using RecordListController::$table is deprecated and will not be possible anymore in TYPO3 v10.0.',
        'search_field' => 'Using RecordListController::$search_field is deprecated and will not be possible anymore in TYPO3 v10.0.',
        'search_levels' => 'Using RecordListController::$search_levels is deprecated and will not be possible anymore in TYPO3 v10.0.',
        'showLimit' => 'Using RecordListController::$showLimit is deprecated and will not be possible anymore in TYPO3 v10.0.',
        'returnUrl' => 'Using RecordListController::$returnUrl is deprecated and will not be possible anymore in TYPO3 v10.0.',
        'clear_cache' => 'Using RecordListController::$clear_cache is deprecated and will not be possible anymore in TYPO3 v10.0.',
        'cmd' => 'Using RecordListController::$cmd is deprecated and will not be possible anymore in TYPO3 v10.0.',
        'cmd_table' => 'Using RecordListController::$cmd_table is deprecated and will not be possible anymore in TYPO3 v10.0.',
        'perms_clause' => 'Using RecordListController::$perms_clause is deprecated and will not be possible anymore in TYPO3 v10.0.',
        'pageinfo' => 'Using RecordListController::$pageinfo is deprecated and will not be possible anymore in TYPO3 v10.0.',
        'MOD_MENU' => 'Using RecordListController::$MOD_MENU is deprecated and will not be possible anymore in TYPO3 v10.0.',
        'content' => 'Using RecordListController::$content is deprecated and will not be possible anymore in TYPO3 v10.0.',
        'body' => 'Using RecordListController::$body is deprecated and will not be possible anymore in TYPO3 v10.0.',
        'imagemode' => 'Using RecordListController::$imagemode is deprecated, property will be removed in TYPO3 v10.0.',
        'doc' => 'Using RecordListController::$doc is deprecated, property will be removed in TYPO3 v10.0.',
    ];

    /**
     * @var array
     */
    private $deprecatedPublicMethods = [
        'init' => 'Using RecordListController::init() is deprecated and will not be possible anymore in TYPO3 v10.0.',
        'menuConfig' => 'Using RecordListController::menuConfig() is deprecated and will not be possible anymore in TYPO3 v10.0.',
        'clearCache' => 'Using RecordListController::clearCache() is deprecated and will not be possible anymore in TYPO3 v10.0.',
        'main' => 'Using RecordListController::main() is deprecated and will not be possible anymore in TYPO3 v10.0.',
        'getModuleTemplate' => 'Using RecordListController::getModuleTemplate() is deprecated and will not be possible anymore in TYPO3 v10.0.',
    ];

    /**
     * Page Id for which to make the listing
     *
     * @var int
     */
    protected $id;

    /**
     * Pointer - for browsing list of records.
     *
     * @var int
     */
    protected $pointer;

    /**
     * Thumbnails or not
     *
     * @var string
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0.
     */
    protected $imagemode;

    /**
     * Which table to make extended listing for
     *
     * @var string
     */
    protected $table;

    /**
     * Search-fields
     *
     * @var string
     */
    protected $search_field;

    /**
     * Search-levels
     *
     * @var int
     */
    protected $search_levels;

    /**
     * Show-limit
     *
     * @var int
     */
    protected $showLimit;

    /**
     * Return URL
     *
     * @var string
     */
    protected $returnUrl;

    /**
     * Clear-cache flag - if set, clears page cache for current id.
     *
     * @var bool
     */
    protected $clear_cache;

    /**
     * Command: Eg. "delete" or "setCB" (for DataHandler / clipboard operations)
     *
     * @var string
     */
    protected $cmd;

    /**
     * Table on which the cmd-action is performed.
     *
     * @var string
     */
    protected $cmd_table;

    /**
     * Page select perms clause
     *
     * @var int
     */
    protected $perms_clause;

    /**
     * Module TSconfig
     *
     * @var array
     * @internal Still used by DatabaseRecordList via $GLOBALS['SOBE']
     */
    public $modTSconfig;

    /**
     * Current ids page record
     *
     * @var mixed[]|bool
     */
    protected $pageinfo;

    /**
     * Document template object
     *
     * @var DocumentTemplate
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0.
     */
    protected $doc;

    /**
     * Menu configuration
     *
     * @var string[]
     */
    protected $MOD_MENU = [];

    /**
     * Module settings (session variable)
     *
     * @var string[]
     * @internal Still used by DatabaseRecordList via $GLOBALS['SOBE']
     */
    public $MOD_SETTINGS = [];

    /**
     * Module output accumulation
     *
     * @var string
     */
    protected $content;

    /**
     * @var string
     */
    protected $body = '';

    /**
     * @var PageRenderer
     */
    protected $pageRenderer;

    /**
     * @var IconFactory
     */
    protected $iconFactory;

    /**
     * ModuleTemplate object
     *
     * @var ModuleTemplate
     */
    protected $moduleTemplate;

    /**
     * @var SiteInterface
     */
    protected $site;

    /**
     * @var SiteLanguage[]
     */
    protected $siteLanguages = [];

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->moduleTemplate = GeneralUtility::makeInstance(ModuleTemplate::class);
        $this->getLanguageService()->includeLLFile('EXT:core/Resources/Private/Language/locallang_mod_web_list.xlf');
        $this->moduleTemplate->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Recordlist/FieldSelectBox');
        $this->moduleTemplate->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Recordlist/Recordlist');
    }

    /**
     * Initializing the module
     */
    protected function init()
    {
        $this->iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        $backendUser = $this->getBackendUserAuthentication();
        $this->perms_clause = $backendUser->getPagePermsClause(Permission::PAGE_SHOW);
        // Get session data
        $sessionData = $backendUser->getSessionData(__CLASS__);
        $this->search_field = !empty($sessionData['search_field']) ? $sessionData['search_field'] : '';
        // GPvars:
        $this->id = (int)GeneralUtility::_GP('id');
        $this->pointer = GeneralUtility::_GP('pointer');
        // @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0.
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
        $this->getPageRenderer()->addInlineLanguageLabelFile('EXT:core/Resources/Private/Language/locallang_mod_web_list.xlf');
    }

    /**
     * Initialize function menu array
     */
    protected function menuConfig()
    {
        // MENU-ITEMS:
        $this->MOD_MENU = [
            'bigControlPanel' => '',
            'clipBoard' => '',
        ];
        // Loading module configuration:
        $this->modTSconfig['properties'] = BackendUtility::getPagesTSconfig($this->id)['mod.']['web_list.'] ?? [];
        // Clean up settings:
        $this->MOD_SETTINGS = BackendUtility::getModuleData($this->MOD_MENU, GeneralUtility::_GP('SET'), 'web_list');
    }

    /**
     * Clears page cache for the current id, $this->id
     */
    protected function clearCache()
    {
        if ($this->clear_cache) {
            $tce = GeneralUtility::makeInstance(DataHandler::class);
            $tce->start([], []);
            $tce->clear_cacheCmd($this->id);
        }
    }

    /**
     * Main function, starting the rendering of the list.
     *
     * @param ServerRequestInterface $request
     */
    protected function main(ServerRequestInterface $request = null)
    {
        if ($request === null) {
            // Missing argument? This method must have been called from outside.
            // Method will be protected and $request mandatory in TYPO3 v10.0, giving core freedom to move stuff around
            // New v10 signature: "protected function main(ServerRequestInterface $request)"
            // @deprecated since TYPO3 v9, method argument $request will be set to mandatory
            $request = $GLOBALS['TYPO3_REQUEST'];
        }

        $backendUser = $this->getBackendUserAuthentication();
        $lang = $this->getLanguageService();
        // Loading current page record and checking access:
        $this->pageinfo = BackendUtility::readPageAccess($this->id, $this->perms_clause);
        $access = is_array($this->pageinfo);

        // Start document template object
        // @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0. Instantiation will be removed.
        $this->doc = GeneralUtility::makeInstance(DocumentTemplate::class);

        $this->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Backend/AjaxDataHandler');
        $calcPerms = $backendUser->calcPerms($this->pageinfo);
        $userCanEditPage = $calcPerms & Permission::PAGE_EDIT && !empty($this->id) && ($backendUser->isAdmin() || (int)$this->pageinfo['editlock'] === 0);
        if ($userCanEditPage) {
            $this->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Backend/PageActions', 'function(PageActions) {
                PageActions.setPageId(' . (int)$this->id . ');
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

        // Initialize the dblist object:
        $dblist = GeneralUtility::makeInstance(DatabaseRecordList::class);
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        $dblist->script = (string)$uriBuilder->buildUriFromRoute('web_list');
        $dblist->calcPerms = $calcPerms;
        $dblist->thumbs = $backendUser->uc['thumbnailsByDefault'];
        $dblist->returnUrl = $this->returnUrl;
        $dblist->allFields = $this->MOD_SETTINGS['bigControlPanel'] || $this->table ? 1 : 0;
        $dblist->showClipboard = 1;
        $dblist->disableSingleTableView = $this->modTSconfig['properties']['disableSingleTableView'];
        $dblist->listOnlyInSingleTableMode = $this->modTSconfig['properties']['listOnlyInSingleTableView'];
        $dblist->hideTables = $this->modTSconfig['properties']['hideTables'];
        $dblist->hideTranslations = $this->modTSconfig['properties']['hideTranslations'];
        $dblist->tableTSconfigOverTCA = $this->modTSconfig['properties']['table.'];
        $dblist->allowedNewTables = GeneralUtility::trimExplode(',', $this->modTSconfig['properties']['allowedNewTables'], true);
        $dblist->deniedNewTables = GeneralUtility::trimExplode(',', $this->modTSconfig['properties']['deniedNewTables'], true);
        $dblist->pageRow = $this->pageinfo;
        $dblist->counter++;
        $dblist->MOD_MENU = ['bigControlPanel' => '', 'clipBoard' => ''];
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
            // Render the list of tables:
            $dblist->generateList();
            $listUrl = $dblist->listURL();
            // Add JavaScript functions to the page:

            $this->moduleTemplate->addJavaScriptCode(
                'RecordListInlineJS',
                '
				function jumpExt(URL,anchor) {
					var anc = anchor?anchor:"";
					window.location.href = URL+(T3_THIS_LOCATION?"&returnUrl="+T3_THIS_LOCATION:"")+anc;
					return false;
				}
				function jumpSelf(URL) {
					window.location.href = URL+(T3_RETURN_URL?"&returnUrl="+T3_RETURN_URL:"");
					return false;
				}
				function jumpToUrl(URL) {
					window.location.href = URL;
					return false;
				}

				function setHighlight(id) {
					top.fsMod.recentIds["web"] = id;
					top.fsMod.navFrameHighlightedID["web"] = top.fsMod.currentBank + "_" + id; // For highlighting

					if (top.nav_frame && top.nav_frame.refresh_nav) {
						top.nav_frame.refresh_nav();
					}
				}
				' . $this->moduleTemplate->redirectUrls($listUrl) . '
				' . $dblist->CBfunctions() . '
				function editRecords(table,idList,addParams,CBflag) {
					window.location.href="' . (string)$uriBuilder->buildUriFromRoute('record_edit', ['returnUrl' => GeneralUtility::getIndpEnv('REQUEST_URI')]) . '&edit["+table+"]["+idList+"]=edit"+addParams;
				}
				function editList(table,idList) {
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

        // Additional header content
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['recordlist/Modules/Recordlist/index.php']['drawHeaderHook'] ?? [] as $hook) {
            $params = [
                'request' => $request,
            ];
            // @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0: Handing over $this as second constructor argument will be changed to $null = null;
            $this->body .= GeneralUtility::callUserFunction($hook, $params, $this);
        }

        $this->moduleTemplate->setTitle($title);

        $output = '';
        // Show the selector to add page translations and the list of translations of the current page
        // but only when in "default" mode
        if ($this->id && !$dblist->csvOutput && !$this->search_field && !$this->cmd && !$this->table) {
            $output .= $this->languageSelector($this->id);
            $pageTranslationsDatabaseRecordList = clone $dblist;
            $pageTranslationsDatabaseRecordList->listOnlyInSingleTableMode = false;
            $pageTranslationsDatabaseRecordList->disableSingleTableView = true;
            $pageTranslationsDatabaseRecordList->deniedNewTables = ['pages'];
            $pageTranslationsDatabaseRecordList->hideTranslations = '';
            $pageTranslationsDatabaseRecordList->iLimit = $pageTranslationsDatabaseRecordList->itemsLimitPerTable;
            $pageTranslationsDatabaseRecordList->showOnlyTranslatedRecords(true);
            $output .= $pageTranslationsDatabaseRecordList->getTable('pages', $this->id);
        }

        if (!empty($dblist->HTMLcode)) {
            $output .= $dblist->HTMLcode;
        } else {
            $flashMessage = GeneralUtility::makeInstance(
                FlashMessage::class,
                $lang->getLL('noRecordsOnThisPage'),
                '',
                FlashMessage::INFO
            );
            /** @var \TYPO3\CMS\Core\Messaging\FlashMessageService $flashMessageService */
            $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
            /** @var \TYPO3\CMS\Core\Messaging\FlashMessageQueue $defaultFlashMessageQueue */
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
						Listing options for extended view and clipboard view
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

            $this->body .= '
						</form>
					</div>';
        }
        // Printing clipboard if enabled
        if ($this->MOD_SETTINGS['clipBoard'] && $dblist->showClipboard && ($dblist->HTMLcode || $dblist->clipObj->hasElements())) {
            $this->body .= '<div class="db_list-dashboard">' . $dblist->clipObj->printClipboard() . '</div>';
        }
        // Additional footer content
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['recordlist/Modules/Recordlist/index.php']['drawFooterHook'] ?? [] as $hook) {
            $params = [
                'request' => $request,
            ];
            // @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0: Handing over $this as second constructor argument will be changed to $null = null;
            $this->body .= GeneralUtility::callUserFunction($hook, $params, $this);
        }
        // Setting up the buttons for docheader
        $dblist->getDocHeaderButtons($this->moduleTemplate);
        // search box toolbar
        if (!$this->modTSconfig['properties']['disableSearchBox'] && ($dblist->HTMLcode || !empty($dblist->searchString))) {
            $this->content = $dblist->getSearchBox();
            $this->moduleTemplate->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Backend/ToggleSearchToolbox');

            $searchButton = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar()->makeLinkButton();
            $searchButton
                ->setHref('#')
                ->setClasses('t3js-toggle-search-toolbox')
                ->setTitle($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.title.searchIcon'))
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
     * @return ResponseInterface the response with the content
     */
    public function mainAction(ServerRequestInterface $request): ResponseInterface
    {
        $this->site = $request->getAttribute('site');
        $this->siteLanguages = $this->site->getAvailableLanguages($this->getBackendUserAuthentication(), false, (int)$this->id);
        BackendUtility::lockRecords();
        // @deprecated  since TYPO3 v9, will be removed in TYPO3 v10.0. Can be removed along with $this->doc.
        $GLOBALS['SOBE'] = $this;
        $this->init();
        $this->clearCache();
        $this->main($request);
        $this->moduleTemplate->setContent($this->content);
        return new HtmlResponse($this->moduleTemplate->renderContent());
    }

    /**
     * Make selector box for creating new translation in a language
     * Displays only languages which are not yet present for the current page and
     * that are not disabled with page TS.
     *
     * @param int $id Page id for which to create a new translation record of pages
     * @return string <select> HTML element (if there were items for the box anyways...)
     */
    protected function languageSelector(int $id): string
    {
        if (!$this->getBackendUserAuthentication()->check('tables_modify', 'pages')) {
            return '';
        }
        $availableTranslations = [];
        foreach ($this->siteLanguages as $siteLanguage) {
            if ($siteLanguage->getLanguageId() === 0) {
                continue;
            }
            $availableTranslations[$siteLanguage->getLanguageId()] = $siteLanguage->getTitle();
        }
        // Then, subtract the languages which are already on the page:
        $localizationParentField = $GLOBALS['TCA']['pages']['ctrl']['transOrigPointerField'];
        $languageField = $GLOBALS['TCA']['pages']['ctrl']['languageField'];
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
            ->add(GeneralUtility::makeInstance(BackendWorkspaceRestriction::class));
        $statement = $queryBuilder->select('uid', $languageField)
            ->from('pages')
            ->where(
                $queryBuilder->expr()->eq(
                    $localizationParentField,
                    $queryBuilder->createNamedParameter($this->id, \PDO::PARAM_INT)
                )
            )
            ->execute();
        while ($pageTranslation = $statement->fetch()) {
            unset($availableTranslations[(int)$pageTranslation[$languageField]]);
        }
        // If any languages are left, make selector:
        if (!empty($availableTranslations)) {
            $output = '<option value="">' . htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang_layout.xlf:new_language')) . '</option>';
            foreach ($availableTranslations as $languageUid => $languageTitle) {
                // Build localize command URL to DataHandler (tce_db)
                // which redirects to FormEngine (record_edit)
                // which, when finished editing should return back to the current page (returnUrl)
                $parameters = [
                    'justLocalized' => 'pages:' . $id . ':' . $languageUid,
                    'returnUrl' => GeneralUtility::getIndpEnv('REQUEST_URI')
                ];
                $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
                $redirectUrl = (string)$uriBuilder->buildUriFromRoute('record_edit', $parameters);
                $targetUrl = BackendUtility::getLinkToDataHandlerAction(
                    '&cmd[pages][' . $id . '][localize]=' . $languageUid,
                    $redirectUrl
                );

                $output .= '<option value="' . htmlspecialchars($targetUrl) . '">' . htmlspecialchars($languageTitle) . '</option>';
            }

            return '<div class="form-inline form-inline-spaced">'
                . '<div class="form-group">'
                . '<select class="form-control input-sm" name="createNewLanguage" onchange="window.location.href=this.options[this.selectedIndex].value">'
                . $output
                . '</select></div></div>';
        }
        return '';
    }

    /**
     * @return ModuleTemplate
     */
    protected function getModuleTemplate(): ModuleTemplate
    {
        return $this->moduleTemplate;
    }

    /**
     * @return BackendUserAuthentication
     */
    protected function getBackendUserAuthentication(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    /**
     * @return LanguageService
     */
    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }

    /**
     * @return PageRenderer
     */
    protected function getPageRenderer(): PageRenderer
    {
        if ($this->pageRenderer === null) {
            $this->pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
        }
        return $this->pageRenderer;
    }
}
