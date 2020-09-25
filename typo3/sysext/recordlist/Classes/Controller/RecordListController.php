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

namespace TYPO3\CMS\Recordlist\Controller;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Clipboard\Clipboard;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\WorkspaceRestriction;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\TypoScript\TypoScriptService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Recordlist\Event\RenderAdditionalContentToRecordListEvent;
use TYPO3\CMS\Recordlist\RecordList\DatabaseRecordList;

/**
 * Script Class for the Web > List module; rendering the listing of records on a page
 * @internal This class is a specific Backend controller implementation and is not part of the TYPO3's Core API.
 */
class RecordListController
{
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
     * @var SiteLanguage[]
     */
    protected $siteLanguages = [];

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @var UriBuilder
     */
    protected $uriBuilder;

    /**
     * Constructor
     */
    public function __construct(IconFactory $iconFactory, ModuleTemplate $moduleTemplate, EventDispatcherInterface $eventDispatcher, UriBuilder $uriBuilder)
    {
        $this->moduleTemplate = $moduleTemplate;
        $this->getLanguageService()->includeLLFile('EXT:core/Resources/Private/Language/locallang_mod_web_list.xlf');
        $this->moduleTemplate->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Recordlist/FieldSelectBox');
        $this->moduleTemplate->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Recordlist/Recordlist');
        $this->moduleTemplate->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Recordlist/ClearCache');
        $this->moduleTemplate->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Backend/AjaxDataHandler');
        $this->moduleTemplate->getPageRenderer()->addInlineLanguageLabelFile('EXT:core/Resources/Private/Language/locallang_mod_web_list.xlf');
        $this->moduleTemplate->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Recordlist/Tooltip');

        $this->iconFactory = $iconFactory;
        $this->eventDispatcher = $eventDispatcher;
        $this->uriBuilder = $uriBuilder;
    }

    /**
     * Injects the request object for the current request or subrequest
     *
     * @param ServerRequestInterface $request the current request
     * @return ResponseInterface the response with the content
     */
    public function mainAction(ServerRequestInterface $request): ResponseInterface
    {
        // init
        BackendUtility::lockRecords();
        $parsedBody = $request->getParsedBody();
        $queryParams = $request->getQueryParams();
        $backendUser = $this->getBackendUserAuthentication();
        $perms_clause = $backendUser->getPagePermsClause(Permission::PAGE_SHOW);
        // GPvars:
        $id = (int)($parsedBody['id'] ?? $queryParams['id'] ?? 0);
        $pointer =  max(0, (int)($parsedBody['pointer'] ?? $queryParams['pointer'] ?? 0));
        $table = (string)($parsedBody['table'] ?? $queryParams['table'] ?? '');
        $search_field = (string)($parsedBody['search_field'] ?? $queryParams['search_field'] ?? '');
        $search_levels = (int)($parsedBody['search_levels'] ?? $queryParams['search_levels'] ?? 0);
        $showLimit = (int)($parsedBody['showLimit'] ?? $queryParams['showLimit'] ?? 0);
        $returnUrl = GeneralUtility::sanitizeLocalUrl((string)($parsedBody['returnUrl'] ?? $queryParams['returnUrl'] ?? ''));
        $cmd = (string)($parsedBody['cmd'] ?? $queryParams['cmd'] ?? '');
        $cmd_table = (string)($parsedBody['cmd_table'] ?? $queryParams['cmd_table'] ?? '');
        // Set site languages
        $site = $request->getAttribute('site');
        $this->siteLanguages = $site->getAvailableLanguages($this->getBackendUserAuthentication(), false, $id);
        // Loading module configuration:
        $modTSconfig['properties'] = BackendUtility::getPagesTSconfig($id)['mod.']['web_list.'] ?? [];
        // Clean up settings:
        $MOD_SETTINGS = BackendUtility::getModuleData(['bigControlPanel' => '', 'clipBoard' => ''], (array)($parsedBody['SET'] ?? $queryParams['SET'] ?? []), 'web_list');
        // main
        $backendUser = $this->getBackendUserAuthentication();
        $lang = $this->getLanguageService();
        // Loading current page record and checking access:
        $pageinfo = BackendUtility::readPageAccess($id, $perms_clause);
        $access = is_array($pageinfo);

        $calcPerms = new Permission($backendUser->calcPerms($pageinfo));
        $userCanEditPage = $calcPerms->editPagePermissionIsGranted() && !empty($id) && ($backendUser->isAdmin() || (int)$pageinfo['editlock'] === 0);
        $pageActionsCallback = null;
        if ($userCanEditPage) {
            $pageActionsCallback = 'function(PageActions) {
                PageActions.setPageId(' . (int)$id . ');
            }';
        }
        $this->moduleTemplate->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Backend/PageActions', $pageActionsCallback);
        // Apply predefined values for hidden checkboxes
        // Set predefined value for DisplayBigControlPanel:
        if ($modTSconfig['properties']['enableDisplayBigControlPanel'] === 'activated') {
            $MOD_SETTINGS['bigControlPanel'] = true;
        } elseif ($modTSconfig['properties']['enableDisplayBigControlPanel'] === 'deactivated') {
            $MOD_SETTINGS['bigControlPanel'] = false;
        }
        // Set predefined value for Clipboard:
        if ($modTSconfig['properties']['enableClipBoard'] === 'activated') {
            $MOD_SETTINGS['clipBoard'] = true;
        } elseif ($modTSconfig['properties']['enableClipBoard'] === 'deactivated') {
            $MOD_SETTINGS['clipBoard'] = false;
        } else {
            if ($MOD_SETTINGS['clipBoard'] === null) {
                $MOD_SETTINGS['clipBoard'] = true;
            }
        }

        // Initialize the dblist object:
        $dblist = GeneralUtility::makeInstance(DatabaseRecordList::class);
        $dblist->setModuleData($MOD_SETTINGS ?? []);
        $dblist->calcPerms = $calcPerms;
        $dblist->returnUrl = $returnUrl;
        $dblist->allFields = $MOD_SETTINGS['bigControlPanel'] || $table ? 1 : 0;
        $dblist->showClipboard = true;
        $dblist->disableSingleTableView = $modTSconfig['properties']['disableSingleTableView'];
        $dblist->listOnlyInSingleTableMode = $modTSconfig['properties']['listOnlyInSingleTableView'];
        $dblist->hideTables = $modTSconfig['properties']['hideTables'];
        $dblist->hideTranslations = $modTSconfig['properties']['hideTranslations'];
        $dblist->tableTSconfigOverTCA = $modTSconfig['properties']['table.'];
        $dblist->allowedNewTables = GeneralUtility::trimExplode(',', $modTSconfig['properties']['allowedNewTables'], true);
        $dblist->deniedNewTables = GeneralUtility::trimExplode(',', $modTSconfig['properties']['deniedNewTables'], true);
        $dblist->pageRow = $pageinfo;
        $dblist->MOD_MENU = ['bigControlPanel' => '', 'clipBoard' => ''];
        $dblist->modTSconfig = $modTSconfig;
        $dblist->setLanguagesAllowedForUser($this->siteLanguages);
        $clickTitleMode = trim($modTSconfig['properties']['clickTitleMode']);
        $dblist->clickTitleMode = $clickTitleMode === '' ? 'edit' : $clickTitleMode;
        if (isset($modTSconfig['properties']['tableDisplayOrder.'])) {
            $typoScriptService = GeneralUtility::makeInstance(TypoScriptService::class);
            $dblist->setTableDisplayOrder($typoScriptService->convertTypoScriptArrayToPlainArray($modTSconfig['properties']['tableDisplayOrder.']));
        }
        // Clipboard is initialized:
        // Start clipboard
        $dblist->clipObj = GeneralUtility::makeInstance(Clipboard::class);
        // Initialize - reads the clipboard content from the user session
        $dblist->clipObj->initializeClipboard();
        // Clipboard actions are handled:
        // CB is the clipboard command array
        $CB = $queryParams['CB'] ?? [];
        if ($cmd === 'setCB') {
            // CBH is all the fields selected for the clipboard, CBC is the checkbox fields which were checked.
            // By merging we get a full array of checked/unchecked elements
            // This is set to the 'el' array of the CB after being parsed so only the table in question is registered.
            $CB['el'] = $dblist->clipObj->cleanUpCBC(array_merge($parsedBody['CBH'] ?? [], (array)($parsedBody['CBC'] ?? [])), $cmd_table);
        }
        if (!$MOD_SETTINGS['clipBoard']) {
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
        $dblist->dontShowClipControlPanels = ($dblist->clipObj->current === 'normal' && !$modTSconfig['properties']['showClipControlPanelsDespiteOfCMlayers']);
        // If there is access to the page or root page is used for searching, then render the list contents and set up the document template object:
        $tableOutput = '';
        if ($access || ($id === 0 && $search_levels !== 0 && $search_field !== '')) {
            // Deleting records...:
            // Has not to do with the clipboard but is simply the delete action. The clipboard object is used to clean up the submitted entries to only the selected table.
            if ($cmd === 'delete') {
                $items = $dblist->clipObj->cleanUpCBC($parsedBody['CBC'] ?? [], $cmd_table, 1);
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
            $dblist->start($id, $table, $pointer, $search_field, $search_levels, $showLimit);
            $dblist->setDispFields();
            // Render the list of tables:
            $tableOutput = $dblist->generateList();

            // Add JavaScript functions to the page:
            $this->moduleTemplate->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Backend/ClipboardComponent');

            $this->moduleTemplate->addJavaScriptCode(
                'RecordListInlineJS',
                '
				function setHighlight(id) {
					top.fsMod.recentIds["web"] = id;
					top.fsMod.navFrameHighlightedID["web"] = top.fsMod.currentBank + "_" + id; // For highlighting

					if (top.nav_frame && top.nav_frame.refresh_nav) {
						top.nav_frame.refresh_nav();
					}
				}
				function editRecords(table,idList,addParams,CBflag) {
					window.location.href="' . (string)$this->uriBuilder->buildUriFromRoute('record_edit', ['returnUrl' => GeneralUtility::getIndpEnv('REQUEST_URI')]) . '&edit["+table+"]["+idList+"]=edit"+addParams;
				}

				if (top.fsMod) top.fsMod.recentIds["web"] = ' . (int)$id . ';
			'
            );

            // Setting up the context sensitive menu:
            $this->moduleTemplate->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Backend/ContextMenu');
        }
        // access
        // Begin to compile the whole page, starting out with page header:
        if (!$id) {
            $title = $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'];
        } else {
            $title = $pageinfo['title'];
        }
        $body = $this->moduleTemplate->header($title);

        // Additional header content
        /** @var RenderAdditionalContentToRecordListEvent $additionalRecordListEvent */
        $additionalRecordListEvent = $this->eventDispatcher->dispatch(new RenderAdditionalContentToRecordListEvent($request));
        $body .= $additionalRecordListEvent->getAdditionalContentAbove();
        $this->moduleTemplate->setTitle($title);

        $output = '';
        // Show the selector to add page translations and the list of translations of the current page
        // but only when in "default" mode
        if ($id && !$dblist->csvOutput && !$search_field && !$cmd && !$table) {
            $output .= $this->languageSelector($id);
            $pageTranslationsDatabaseRecordList = clone $dblist;
            $pageTranslationsDatabaseRecordList->listOnlyInSingleTableMode = false;
            $pageTranslationsDatabaseRecordList->disableSingleTableView = true;
            $pageTranslationsDatabaseRecordList->deniedNewTables = ['pages'];
            $pageTranslationsDatabaseRecordList->hideTranslations = '';
            $pageTranslationsDatabaseRecordList->setLanguagesAllowedForUser($this->siteLanguages);
            $pageTranslationsDatabaseRecordList->showOnlyTranslatedRecords(true);
            $output .= $pageTranslationsDatabaseRecordList->getTable('pages', $id);
        }

        if (!empty($tableOutput)) {
            $output .= $tableOutput;
        } else {
            if (isset($GLOBALS['TCA'][$table]['ctrl']['title'])) {
                if (strpos($GLOBALS['TCA'][$table]['ctrl']['title'], 'LLL:') === 0) {
                    $ll = sprintf($lang->getLL('noRecordsOfTypeOnThisPage'), $lang->sL($GLOBALS['TCA'][$table]['ctrl']['title']));
                } else {
                    $ll = sprintf($lang->getLL('noRecordsOfTypeOnThisPage'), $GLOBALS['TCA'][$table]['ctrl']['title']);
                }
            } else {
                $ll = $lang->getLL('noRecordsOnThisPage');
            }
            $flashMessage = GeneralUtility::makeInstance(
                FlashMessage::class,
                $ll,
                '',
                FlashMessage::INFO
            );
            unset($ll);
            /** @var \TYPO3\CMS\Core\Messaging\FlashMessageService $flashMessageService */
            $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
            /** @var \TYPO3\CMS\Core\Messaging\FlashMessageQueue $defaultFlashMessageQueue */
            $defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
            $defaultFlashMessageQueue->enqueue($flashMessage);
        }

        $body .= '<form action="' . htmlspecialchars($dblist->listURL()) . '" method="post" name="dblistForm">';
        $body .= $output;
        $body .= '<input type="hidden" name="cmd_table" /><input type="hidden" name="cmd" /></form>';
        // If a listing was produced, create the page footer with search form etc:
        if ($tableOutput) {
            // Making field select box (when extended view for a single table is enabled):
            if ($dblist->table) {
                $body .= $dblist->fieldSelectBox($dblist->table);
            }
            // Adding checkbox options for extended listing and clipboard display:
            $body .= '

					<!--
						Listing options for extended view and clipboard view
					-->
					<div class="typo3-listOptions">
						<form action="" method="post">';

            // Add "display bigControlPanel" checkbox:
            if ($modTSconfig['properties']['enableDisplayBigControlPanel'] === 'selectable') {
                $body .= '<div class="checkbox">' .
                    '<label for="checkLargeControl">' .
                    BackendUtility::getFuncCheck($id, 'SET[bigControlPanel]', $MOD_SETTINGS['bigControlPanel'], '', $table ? '&table=' . $table : '', 'id="checkLargeControl"') .
                    BackendUtility::wrapInHelp('xMOD_csh_corebe', 'list_options', htmlspecialchars($lang->getLL('largeControl'))) .
                    '</label>' .
                    '</div>';
            }

            // Add "clipboard" checkbox:
            if ($modTSconfig['properties']['enableClipBoard'] === 'selectable') {
                if ($dblist->showClipboard) {
                    $body .= '<div class="checkbox">' .
                        '<label for="checkShowClipBoard">' .
                        BackendUtility::getFuncCheck($id, 'SET[clipBoard]', $MOD_SETTINGS['clipBoard'], '', $table ? '&table=' . $table : '', 'id="checkShowClipBoard"') .
                        BackendUtility::wrapInHelp('xMOD_csh_corebe', 'list_options', htmlspecialchars($lang->getLL('showClipBoard'))) .
                        '</label>' .
                        '</div>';
                }
            }

            $body .= '
						</form>
					</div>';
        }
        // Printing clipboard if enabled
        if ($MOD_SETTINGS['clipBoard'] && $dblist->showClipboard && ($tableOutput || $dblist->clipObj->hasElements())) {
            $body .= '<div class="db_list-dashboard">' . $dblist->clipObj->printClipboard() . '</div>';
        }
        // Additional footer content
        $body .= $additionalRecordListEvent->getAdditionalContentBelow();
        // Setting up the buttons for docheader
        $dblist->getDocHeaderButtons($this->moduleTemplate, $request);
        // search box toolbar
        $content = '';
        if (!$modTSconfig['properties']['disableSearchBox'] && ($tableOutput || !empty($dblist->searchString))) {
            $content .= $dblist->getSearchBox();
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

        if ($pageinfo) {
            $this->moduleTemplate->getDocHeaderComponent()->setMetaInformation($pageinfo);
        }

        // Build the <body> for the module
        $content .= $body;
        $this->moduleTemplate->setContent($content);
        return new HtmlResponse($this->moduleTemplate->renderContent());
    }

    /**
     * Make selector box for creating new translation in a language
     * Displays only languages which are not yet present for the current page and
     * that are not disabled with page TS.
     *
     * @param int $id Page id for which to create a new translation record of pages
     * @return string HTML <select> element (if there were items for the box anyways...)
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
            ->add(GeneralUtility::makeInstance(WorkspaceRestriction::class, (int)$this->getBackendUserAuthentication()->workspace));
        $statement = $queryBuilder->select('uid', $languageField)
            ->from('pages')
            ->where(
                $queryBuilder->expr()->eq(
                    $localizationParentField,
                    $queryBuilder->createNamedParameter($id, \PDO::PARAM_INT)
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
                $redirectUrl = (string)$this->uriBuilder->buildUriFromRoute('record_edit', $parameters);
                $params = [];
                $params['redirect'] = $redirectUrl;
                $params['cmd']['pages'][$id]['localize'] = $languageUid;
                $targetUrl = (string)$this->uriBuilder->buildUriFromRoute('tce_db', $params);
                $output .= '<option value="' . htmlspecialchars($targetUrl) . '">' . htmlspecialchars($languageTitle) . '</option>';
            }

            return '<div class="form-inline form-inline-spaced">'
                . '<div class="form-group">'
                . '<select class="form-control input-sm" name="createNewLanguage" data-global-event="change" data-action-navigate="$value">'
                . $output
                . '</select></div></div>';
        }
        return '';
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
}
