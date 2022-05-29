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
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Clipboard\Clipboard;
use TYPO3\CMS\Backend\Domain\Model\Element\ImmediateActionElement;
use TYPO3\CMS\Backend\Routing\PreviewUriBuilder;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\WorkspaceRestriction;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\TypoScript\TypoScriptService;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Recordlist\Event\RenderAdditionalContentToRecordListEvent;
use TYPO3\CMS\Recordlist\RecordList\DatabaseRecordList;
use TYPO3\CMS\Recordlist\View\RecordSearchBoxComponent;

/**
 * Script Class for the Web > List module; rendering the listing of records on a page
 * @internal This class is a specific Backend controller implementation and is not part of the TYPO3's Core API.
 */
class RecordListController
{
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
     * @var Permission
     */
    protected $pagePermissions;

    protected int $id = 0;
    protected array $pageInfo = [];
    protected string $returnUrl = '';
    protected array $modTSconfig = [];

    protected IconFactory $iconFactory;
    protected PageRenderer $pageRenderer;
    protected EventDispatcherInterface $eventDispatcher;
    protected UriBuilder $uriBuilder;
    protected ModuleTemplateFactory $moduleTemplateFactory;
    protected ResponseFactoryInterface $responseFactory;

    public function __construct(
        IconFactory $iconFactory,
        PageRenderer $pageRenderer,
        EventDispatcherInterface $eventDispatcher,
        UriBuilder $uriBuilder,
        ModuleTemplateFactory $moduleTemplateFactory,
        ResponseFactoryInterface $responseFactory
    ) {
        $this->iconFactory = $iconFactory;
        $this->pageRenderer = $pageRenderer;
        $this->eventDispatcher = $eventDispatcher;
        $this->uriBuilder = $uriBuilder;
        $this->moduleTemplateFactory = $moduleTemplateFactory;
        $this->responseFactory = $responseFactory;
    }

    /**
     * Injects the request object for the current request or subrequest
     *
     * @param ServerRequestInterface $request the current request
     * @return ResponseInterface the response with the content
     */
    public function mainAction(ServerRequestInterface $request): ResponseInterface
    {
        $this->moduleTemplate = $this->moduleTemplateFactory->create($request);
        $this->getLanguageService()->includeLLFile('EXT:core/Resources/Private/Language/locallang_mod_web_list.xlf');
        $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Recordlist/Recordlist');
        $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Recordlist/RecordDownloadButton');
        $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Recordlist/ClearCache');
        $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Recordlist/RecordSearch');
        $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/AjaxDataHandler');
        $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/ColumnSelectorButton');
        $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/MultiRecordSelection');
        $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/ClipboardPanel');
        $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/NewContentElementWizardButton');
        $this->pageRenderer->addInlineLanguageLabelFile('EXT:core/Resources/Private/Language/locallang_mod_web_list.xlf');

        BackendUtility::lockRecords();
        $parsedBody = $request->getParsedBody();
        $queryParams = $request->getQueryParams();
        $backendUser = $this->getBackendUserAuthentication();
        $perms_clause = $backendUser->getPagePermsClause(Permission::PAGE_SHOW);
        // GPvars:
        $this->id = (int)($parsedBody['id'] ?? $queryParams['id'] ?? 0);
        $pointer = max(0, (int)($parsedBody['pointer'] ?? $queryParams['pointer'] ?? 0));
        $table = (string)($parsedBody['table'] ?? $queryParams['table'] ?? '');
        $search_field = (string)($parsedBody['search_field'] ?? $queryParams['search_field'] ?? '');
        $search_levels = (int)($parsedBody['search_levels'] ?? $queryParams['search_levels'] ?? 0);
        $this->returnUrl = GeneralUtility::sanitizeLocalUrl((string)($parsedBody['returnUrl'] ?? $queryParams['returnUrl'] ?? ''));
        $cmd = (string)($parsedBody['cmd'] ?? $queryParams['cmd'] ?? '');
        // Set site languages
        $site = $request->getAttribute('site');
        $this->siteLanguages = $site->getAvailableLanguages($this->getBackendUserAuthentication(), false, $this->id);
        // Loading module configuration:
        $this->modTSconfig = BackendUtility::getPagesTSconfig($this->id)['mod.']['web_list.'] ?? [];
        // Clean up settings:
        $MOD_SETTINGS = BackendUtility::getModuleData(['clipBoard' => ''], (array)($parsedBody['SET'] ?? $queryParams['SET'] ?? []), 'web_list');
        // main
        $lang = $this->getLanguageService();
        // Loading current page record and checking access:
        $pageinfo = BackendUtility::readPageAccess($this->id, $perms_clause);
        $access = is_array($pageinfo);
        $this->pageInfo = is_array($pageinfo) ? $pageinfo : [];

        $this->pagePermissions = new Permission($backendUser->calcPerms($pageinfo));
        $userCanEditPage = $this->pagePermissions->editPagePermissionIsGranted() && !empty($this->id) && ($backendUser->isAdmin() || (int)$pageinfo['editlock'] === 0);
        $pageActionsInstruction = JavaScriptModuleInstruction::forRequireJS('TYPO3/CMS/Backend/PageActions');
        if ($userCanEditPage) {
            $pageActionsInstruction->invoke('setPageId', $this->id);
        }
        $this->pageRenderer->getJavaScriptRenderer()->addJavaScriptModuleInstruction($pageActionsInstruction);
        // Apply predefined values for hidden checkboxes
        // Set predefined value for Clipboard:
        if (isset($this->modTSconfig['enableClipBoard'])) {
            if ($this->modTSconfig['enableClipBoard'] === 'activated') {
                $MOD_SETTINGS['clipBoard'] = true;
            } elseif ($this->modTSconfig['enableClipBoard'] === 'deactivated') {
                $MOD_SETTINGS['clipBoard'] = false;
            }
        }
        if (!isset($MOD_SETTINGS['clipBoard'])) {
            $MOD_SETTINGS['clipBoard'] = true;
        }
        $clipboard = $this->initializeClipboard($request, (bool)$MOD_SETTINGS['clipBoard']);
        $enableListing = $access || ($this->id === 0 && $search_levels !== 0 && $search_field !== '');

        // Initialize the dblist object:
        $dblist = GeneralUtility::makeInstance(DatabaseRecordList::class);
        $dblist->setModuleData($MOD_SETTINGS ?? []);
        $dblist->calcPerms = $this->pagePermissions;
        $dblist->returnUrl = $this->returnUrl;
        $dblist->showClipboardActions = true;
        $dblist->disableSingleTableView = $this->modTSconfig['disableSingleTableView'] ?? false;
        $dblist->listOnlyInSingleTableMode = $this->modTSconfig['listOnlyInSingleTableView'] ?? false;
        $dblist->hideTables = $this->modTSconfig['hideTables'] ?? false;
        $dblist->hideTranslations = $this->modTSconfig['hideTranslations'] ?? false;
        $dblist->tableTSconfigOverTCA = $this->modTSconfig['table.'] ?? false;
        $dblist->allowedNewTables = GeneralUtility::trimExplode(',', $this->modTSconfig['allowedNewTables'] ?? '', true);
        $dblist->deniedNewTables = GeneralUtility::trimExplode(',', $this->modTSconfig['deniedNewTables'] ?? '', true);
        $dblist->pageRow = $this->pageInfo;
        $dblist->modTSconfig = $this->modTSconfig;
        $dblist->setLanguagesAllowedForUser($this->siteLanguages);
        $clickTitleMode = trim($this->modTSconfig['clickTitleMode'] ?? '');
        $dblist->clickTitleMode = $clickTitleMode === '' ? 'edit' : $clickTitleMode;
        if (isset($this->modTSconfig['tableDisplayOrder.'])) {
            $typoScriptService = GeneralUtility::makeInstance(TypoScriptService::class);
            $dblist->setTableDisplayOrder($typoScriptService->convertTypoScriptArrayToPlainArray($this->modTSconfig['tableDisplayOrder.']));
        }

        $dblist->clipObj = $clipboard;
        // If there is access to the page or root page is used for searching, then render the list contents and set up the document template object:
        $tableOutput = '';
        if ($enableListing) {
            // Deleting records...:
            // Has not to do with the clipboard but is simply the delete action. The clipboard object is used to clean up the submitted entries to only the selected table.
            if ($cmd === 'delete' && $request->getMethod() === 'POST') {
                $items = $clipboard->cleanUpCBC((array)($parsedBody['CBC'] ?? []), (string)($parsedBody['cmd_table'] ?? ''), true);
                if (!empty($items)) {
                    // Create data handler command array
                    $dataHandlerCmd = [];
                    foreach ($items as $iK => $value) {
                        $iKParts = explode('|', (string)$iK);
                        $dataHandlerCmd[$iKParts[0]][$iKParts[1]]['delete'] = 1;
                    }
                    $tce = GeneralUtility::makeInstance(DataHandler::class);
                    $tce->start([], $dataHandlerCmd);
                    $tce->process_cmdmap();
                    if (isset($dataHandlerCmd['pages'])) {
                        BackendUtility::setUpdateSignal('updatePageTree');
                    }
                    $tce->printLogErrorMessages();
                }
            }
            // Initialize the listing object, dblist, for rendering the list:
            $dblist->start($this->id, $table, $pointer, $search_field, $search_levels);
            $dblist->setDispFields();
            // Render the list of tables:
            $tableOutput = $dblist->generateList();

            // Add JavaScript functions to the page:
            $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/Element/ImmediateActionElement');

            // Setting up the context sensitive menu:
            $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/ContextMenu');
        }
        // access
        // Begin to compile the whole page, starting out with page header:
        if (!$this->id) {
            $title = $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'];
        } else {
            $title = $pageinfo['title'] ?? '';
        }
        $body = ImmediateActionElement::moduleStateUpdate('web', (int)$this->id);
        $body .= $this->moduleTemplate->header($title, $this->isPageEditable());

        // Additional header content
        /** @var RenderAdditionalContentToRecordListEvent $additionalRecordListEvent */
        $additionalRecordListEvent = $this->eventDispatcher->dispatch(new RenderAdditionalContentToRecordListEvent($request));
        $body .= $additionalRecordListEvent->getAdditionalContentAbove();
        $this->moduleTemplate->setTitle(
            $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_mod_web_list.xlf:mlang_tabs_tab'),
            $title
        );

        $beforeOutput = '';
        $output = '';
        // Show the selector to add page translations, but only when in "default" mode.
        // If not disabled via module TSconfig and the user is allowed, also show the page translations table.
        if ($this->id && !$search_field && !$cmd && !$table) {
            $beforeOutput .= $this->languageSelector($request->getAttribute('normalizedParams')->getRequestUri());
            if ($this->showPageTranslations()) {
                $pageTranslationsDatabaseRecordList = clone $dblist;
                $pageTranslationsDatabaseRecordList->listOnlyInSingleTableMode = false;
                $pageTranslationsDatabaseRecordList->disableSingleTableView = true;
                $pageTranslationsDatabaseRecordList->deniedNewTables = ['pages'];
                $pageTranslationsDatabaseRecordList->hideTranslations = '';
                $pageTranslationsDatabaseRecordList->setLanguagesAllowedForUser($this->siteLanguages);
                $pageTranslationsDatabaseRecordList->showOnlyTranslatedRecords(true);
                $output .= $pageTranslationsDatabaseRecordList->getTable('pages', $this->id);
            }
        }

        // search box toolbar
        if (!($this->modTSconfig['disableSearchBox'] ?? false) && ($tableOutput || !empty($search_field))) {
            $beforeOutput .= $this->renderSearchBox($dblist, $search_field, $search_levels);
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
            $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
            $defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
            $defaultFlashMessageQueue->enqueue($flashMessage);
        }

        if ($beforeOutput) {
            $body .= '<div class="row">' . $beforeOutput . '</div>';
        }
        $body .= $output;
        // If a listing was produced, create the page footer
        if ($tableOutput) {
            // Adding checkbox option for clipboard display
            $body .= '
					<div class="mb-3">
						<form action="" method="post">';

            // Add "clipboard" checkbox:
            if (($this->modTSconfig['enableClipBoard'] ?? '') === 'selectable') {
                $body .= '<div class="form-check form-switch">' .
                    BackendUtility::getFuncCheck($this->id, 'SET[clipBoard]', ($MOD_SETTINGS['clipBoard'] ?? ''), '', $table ? '&table=' . $table : '', 'id="checkShowClipBoard"') .
                    '<label class="form-check-label" for="checkShowClipBoard">' .
                    BackendUtility::wrapInHelp('xMOD_csh_corebe', 'list_options', htmlspecialchars($lang->getLL('showClipBoard'))) .
                    '</label>' .
                    '</div>';
            }

            $body .= '
						</form>
					</div>';
        }
        // Printing clipboard if enabled
        if ($MOD_SETTINGS['clipBoard'] && ($tableOutput || $clipboard->hasElements())) {
            $body .= '<typo3-backend-clipboard-panel return-url="' . htmlspecialchars($dblist->listURL()) . '"></typo3-backend-clipboard-panel>';
        }
        // Additional footer content
        $body .= $additionalRecordListEvent->getAdditionalContentBelow();
        // Setting up the buttons for docheader
        $this->getDocHeaderButtons(
            $clipboard,
            $queryParams,
            $table,
            $dblist->listURL(),
            $MOD_SETTINGS
        );

        if ($pageinfo) {
            $this->moduleTemplate->getDocHeaderComponent()->setMetaInformation($pageinfo);
        }

        $this->moduleTemplate->setContent($body);
        return $this->htmlResponse($this->moduleTemplate->renderContent());
    }

    /**
     * Processing incoming data and configures the clipboard.
     *
     * @param ServerRequestInterface $request
     * @param bool $isClipboardShown
     * @return Clipboard
     */
    protected function initializeClipboard(ServerRequestInterface $request, bool $isClipboardShown): Clipboard
    {
        $clipboard = GeneralUtility::makeInstance(Clipboard::class);
        $cmd = (string)($request->getParsedBody()['cmd'] ?? $request->getQueryParams()['cmd'] ?? '');
        // Initialize - reads the clipboard content from the user session
        $clipboard->initializeClipboard($request);
        // Clipboard actions are handled:
        // CB is the clipboard command array
        $CB = array_replace_recursive($request->getQueryParams()['CB'] ?? [], $request->getParsedBody()['CB'] ?? []);
        if ($cmd === 'copyMarked' || $cmd === 'removeMarked') {
            // Get CBC from request, and map the element values (true => copy, false => remove)
            $CBC = array_map(static fn () => ($cmd === 'copyMarked'), (array)($request->getParsedBody()['CBC'] ?? []));
            $cmd_table = (string)($request->getParsedBody()['cmd_table'] ?? $request->getQueryParams()['cmd_table'] ?? '');
            // Cleanup CBC
            $CB['el'] = $clipboard->cleanUpCBC($CBC, $cmd_table);
        }
        if (!$isClipboardShown) {
            // If the clipboard is NOT shown, set the pad to 'normal'.
            $CB['setP'] = 'normal';
        }
        // Execute commands.
        $clipboard->setCmd($CB);
        // Clean up pad
        $clipboard->cleanCurrent();
        // Save the clipboard content
        $clipboard->endClipboard();
        return $clipboard;
    }

    protected function renderSearchBox(DatabaseRecordList $dblist, string $searchWord, int $searchLevels): string
    {
        $searchBoxVisible = !empty($dblist->searchString);
        $searchBox = GeneralUtility::makeInstance(RecordSearchBoxComponent::class)
            ->setAllowedSearchLevels((array)($this->modTSconfig['searchLevel.']['items.'] ?? []))
            ->setSearchWord($searchWord)
            ->setSearchLevel($searchLevels)
            ->render($dblist->listURL('', '-1', 'pointer,search_field'));

        $buttonBar = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar();
        $searchButton = $buttonBar->makeLinkButton();
        $searchButton
            ->setHref('#')
            ->setClasses('t3js-toggle-search-toolbox')
            ->setTitle($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.title.searchIcon'))
            ->setIcon($this->iconFactory->getIcon('actions-search', Icon::SIZE_SMALL));
        $buttonBar->addButton(
            $searchButton,
            ButtonBar::BUTTON_POSITION_LEFT,
            90
        );
        return '<div class="col-6" style="' . ($searchBoxVisible ?: 'display: none') . '" id="db_list-searchbox-toolbar">' . $searchBox . '</div>';
    }

    /**
     * Create the panel of buttons for submitting the form or otherwise perform
     * operations.
     *
     * @param Clipboard $clipboard
     * @param array $queryParams
     * @param string $table
     * @param string $listUrl
     * @param array $moduleSettings
     */
    protected function getDocHeaderButtons(Clipboard $clipboard, array $queryParams, string $table, string $listUrl, array $moduleSettings): void
    {
        $buttonBar = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar();
        $backendUser = $this->getBackendUserAuthentication();
        $lang = $this->getLanguageService();
        // CSH
        if (!$this->id) {
            $fieldName = 'list_module_root';
        } else {
            $fieldName = 'list_module';
        }
        $cshButton = $buttonBar->makeHelpButton()
            ->setModuleName('xMOD_csh_corebe')
            ->setFieldName($fieldName);
        $buttonBar->addButton($cshButton);
        // New record on pages that are not locked by editlock
        if (!($this->modTSconfig['noCreateRecordsLink'] ?? false) && $this->editLockPermissions()) {
            $newRecordButton = $buttonBar->makeLinkButton()
                ->setHref((string)$this->uriBuilder->buildUriFromRoute('db_new', ['id' => $this->id, 'returnUrl' => $listUrl]))
                ->setTitle($lang->getLL('newRecordGeneral'))
                ->setIcon($this->iconFactory->getIcon('actions-add', Icon::SIZE_SMALL));
            $buttonBar->addButton($newRecordButton, ButtonBar::BUTTON_POSITION_LEFT, 10);
        }

        if ($this->id !== 0) {
            if ($this->canCreatePreviewLink()) {
                $previewDataAttributes = PreviewUriBuilder::create((int)$this->id)
                    ->withRootLine(BackendUtility::BEgetRootLine($this->id))
                    ->buildDispatcherDataAttributes();
                $viewButton = $buttonBar->makeLinkButton()
                    ->setHref('#')
                    ->setDataAttributes($previewDataAttributes ?? [])
                    ->setTitle($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.showPage'))
                    ->setIcon($this->iconFactory->getIcon('actions-view-page', Icon::SIZE_SMALL));
                $buttonBar->addButton($viewButton, ButtonBar::BUTTON_POSITION_LEFT, 20);
            }
            // If edit permissions are set, see
            // \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
            if ($this->isPageEditable()) {
                // Edit
                $editLink = $this->uriBuilder->buildUriFromRoute('record_edit', [
                    'edit' => [
                        'pages' => [
                            $this->id => 'edit',
                        ],
                    ],
                    'returnUrl' => $listUrl,
                ]);
                $editButton = $buttonBar->makeLinkButton()
                    ->setHref($editLink)
                    ->setTitle($lang->getLL('editPage'))
                    ->setIcon($this->iconFactory->getIcon('actions-page-open', Icon::SIZE_SMALL));
                $buttonBar->addButton($editButton, ButtonBar::BUTTON_POSITION_LEFT, 20);
            }
        }

        // Paste
        if (($this->pagePermissions->createPagePermissionIsGranted() || $this->pagePermissions->editContentPermissionIsGranted()) && $this->editLockPermissions()) {
            $elFromTable = $clipboard->elFromTable();
            if (!empty($elFromTable)) {
                $confirmMessage = $clipboard->confirmMsgText('pages', $this->pageInfo, 'into', $elFromTable);
                $pasteButton = $buttonBar->makeLinkButton()
                    ->setHref($clipboard->pasteUrl('', $this->id))
                    ->setTitle($lang->getLL('clip_paste'))
                    ->setClasses('t3js-modal-trigger')
                    ->setDataAttributes([
                        'severity' => 'warning',
                        'bs-content' => $confirmMessage,
                        'title' => $lang->getLL('clip_paste'),
                    ])
                    ->setIcon($this->iconFactory->getIcon('actions-document-paste-into', Icon::SIZE_SMALL));
                $buttonBar->addButton($pasteButton, ButtonBar::BUTTON_POSITION_LEFT, 40);
            }
        }
        // Cache
        if ($this->id !== 0) {
            $clearCacheButton = $buttonBar->makeLinkButton()
                ->setHref('#')
                ->setDataAttributes(['id' => $this->id])
                ->setClasses('t3js-clear-page-cache')
                ->setTitle($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.clear_cache'))
                ->setIcon($this->iconFactory->getIcon('actions-system-cache-clear', Icon::SIZE_SMALL));
            $buttonBar->addButton($clearCacheButton, ButtonBar::BUTTON_POSITION_RIGHT);
        }
        if ($table && (!isset($this->modTSconfig['noExportRecordsLinks'])
                || (isset($this->modTSconfig['noExportRecordsLinks'])
                    && !$this->modTSconfig['noExportRecordsLinks']))
        ) {
            // Export
            if (ExtensionManagementUtility::isLoaded('impexp')) {
                $url = (string)$this->uriBuilder->buildUriFromRoute('tx_impexp_export', ['tx_impexp' => ['list' => [$table . ':' . $this->id]]]);
                $exportButton = $buttonBar->makeLinkButton()
                    ->setHref($url)
                    ->setTitle($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:rm.export'))
                    ->setIcon($this->iconFactory->getIcon('actions-document-export-t3d', Icon::SIZE_SMALL))
                    ->setShowLabelText(true);
                $buttonBar->addButton($exportButton, ButtonBar::BUTTON_POSITION_LEFT, 40);
            }
        }
        // Reload
        $reloadButton = $buttonBar->makeLinkButton()
            ->setHref($listUrl)
            ->setTitle($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.reload'))
            ->setIcon($this->iconFactory->getIcon('actions-refresh', Icon::SIZE_SMALL));
        $buttonBar->addButton($reloadButton, ButtonBar::BUTTON_POSITION_RIGHT);

        // Shortcut
        $shortCutButton = $buttonBar->makeShortcutButton()->setRouteIdentifier('web_list');
        $arguments = [
            'id' => $this->id,
        ];
        $potentialArguments = [
            'pointer',
            'table',
            'search_field',
            'search_levels',
            'sortField',
            'sortRev',
        ];
        foreach ($potentialArguments as $argument) {
            if (!empty($queryParams[$argument])) {
                $arguments[$argument] = $queryParams[$argument];
            }
        }
        foreach ($moduleSettings as $moduleSettingKey => $moduleSettingValue) {
            $arguments['GET'][$moduleSettingKey] = $moduleSettingValue;
        }
        $shortCutButton->setArguments($arguments);
        $shortCutButton->setDisplayName($this->getShortcutTitle($arguments));
        $buttonBar->addButton($shortCutButton, ButtonBar::BUTTON_POSITION_RIGHT);

        // Back
        if ($this->returnUrl) {
            $backButton = $buttonBar->makeLinkButton()
                ->setHref($this->returnUrl)
                ->setTitle($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.goBack'))
                ->setIcon($this->iconFactory->getIcon('actions-view-go-back', Icon::SIZE_SMALL));
            $buttonBar->addButton($backButton, ButtonBar::BUTTON_POSITION_LEFT);
        }
    }

    /**
     * Make selector box for creating new translation in a language
     * Displays only languages which are not yet present for the current page and
     * that are not disabled with page TS.
     *
     * @param string $requestUri
     * @return string HTML <select> element (if there were items for the box anyways...)
     * @throws \TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException
     */
    protected function languageSelector(string $requestUri): string
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
                    $queryBuilder->createNamedParameter($this->id, \PDO::PARAM_INT)
                )
            )
            ->executeQuery();
        while ($pageTranslation = $statement->fetchAssociative()) {
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
                    'justLocalized' => 'pages:' . $this->id . ':' . $languageUid,
                    'returnUrl' => $requestUri,
                ];
                $redirectUrl = (string)$this->uriBuilder->buildUriFromRoute('record_edit', $parameters);
                $params = [];
                $params['redirect'] = $redirectUrl;
                $params['cmd']['pages'][$this->id]['localize'] = $languageUid;
                $targetUrl = (string)$this->uriBuilder->buildUriFromRoute('tce_db', $params);
                $output .= '<option value="' . htmlspecialchars($targetUrl) . '">' . htmlspecialchars($languageTitle) . '</option>';
            }

            return '<div class="col-auto">'
                . '<select class="form-select" name="createNewLanguage" data-global-event="change" data-action-navigate="$value">'
                . $output
                . '</select></div>';
        }
        return '';
    }

    /**
     * Returns the configuration of mod.web_list.noViewWithDokTypes or the
     * default value 254 (Sys Folders) and 255 (Recycler), if not set.
     */
    protected function canCreatePreviewLink(): bool
    {
        if (isset($this->modTSconfig['noViewWithDokTypes'])) {
            $noViewDokTypes = GeneralUtility::trimExplode(',', $this->modTSconfig['noViewWithDokTypes'], true);
        } else {
            $noViewDokTypes = [
                PageRepository::DOKTYPE_SYSFOLDER,
                PageRepository::DOKTYPE_RECYCLER,
            ];
        }

        return !in_array($this->pageInfo['doktype'] ?? 0, $noViewDokTypes);
    }

    /**
     * Check whether or not the current backend user is an admin or the current page is
     * locked by editlock.
     *
     * @return bool
     */
    protected function editLockPermissions(): bool
    {
        return $this->getBackendUserAuthentication()->isAdmin() || !($this->pageInfo['editlock'] ?? false);
    }

    /**
     * Returns the shortcut title for the current page
     *
     * @param array $arguments
     * @return string
     */
    protected function getShortcutTitle(array $arguments): string
    {
        $pageTitle = '';
        $tableTitle = '';
        $languageService = $this->getLanguageService();

        if (isset($arguments['table'])) {
            $tableTitle = ': ' . (isset($GLOBALS['TCA'][$arguments['table']]['ctrl']['title']) ? $languageService->sL($GLOBALS['TCA'][$arguments['table']]['ctrl']['title']) : $arguments['table']);
        }

        if ($this->pageInfo !== []) {
            $pageTitle = BackendUtility::getRecordTitle('pages', $this->pageInfo);
        }

        return trim(sprintf(
            $languageService->sL('LLL:EXT:recordlist/Resources/Private/Language/locallang.xlf:shortcut.title'),
            $languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_mod_web_list.xlf:mlang_tabs_tab'),
            $tableTitle,
            $pageTitle,
            $this->id
        ));
    }

    protected function showPageTranslations(): bool
    {
        if (!$this->getBackendUserAuthentication()->check('tables_select', 'pages')) {
            return false;
        }

        if (isset($this->modTSconfig['table.']['pages.']['hideTable'])) {
            return !$this->modTSconfig['table.']['pages.']['hideTable'];
        }

        $hideTables = $this->modTSconfig['hideTables'] ?? '';
        return !($GLOBALS['TCA']['pages']['ctrl']['hideTable'] ?? false)
            && $hideTables !== '*'
            && !in_array('pages', GeneralUtility::trimExplode(',', $hideTables), true);
    }

    protected function htmlResponse(string $html): ResponseInterface
    {
        $response = $this->responseFactory->createResponse()
            ->withHeader('Content-Type', 'text/html; charset=utf-8');
        $response->getBody()->write($html);
        return $response;
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
     * Check if page can be edited by current user
     *
     * @return bool
     */
    protected function isPageEditable(): bool
    {
        if ($GLOBALS['TCA']['pages']['ctrl']['readOnly'] ?? false) {
            return false;
        }
        $backendUser = $this->getBackendUserAuthentication();
        if ($backendUser->isAdmin()) {
            return true;
        }
        if ($GLOBALS['TCA']['pages']['ctrl']['adminOnly'] ?? false) {
            return false;
        }

        return $this->pageInfo !== []
            && $this->editLockPermissions()
            && $this->pagePermissions->editPagePermissionIsGranted()
            && $backendUser->checkLanguageAccess(0)
            && $backendUser->check('tables_modify', 'pages');
    }
}
