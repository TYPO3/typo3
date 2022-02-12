<?php

declare(strict_types=1);

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
use TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException;
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
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\TypoScript\TypoScriptService;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Recordlist\Event\RenderAdditionalContentToRecordListEvent;
use TYPO3\CMS\Recordlist\RecordList\DatabaseRecordList;
use TYPO3\CMS\Recordlist\View\RecordSearchBoxComponent;

/**
 * The Web > List module: Rendering the listing of records on a page.
 *
 * @internal This class is a specific Backend controller implementation and is not part of the TYPO3's Core API.
 */
class RecordListController
{
    /**
     * @var Permission
     */
    protected $pagePermissions;

    protected int $id = 0;
    protected array $pageInfo = [];
    protected string $returnUrl = '';
    protected array $modTSconfig = [];

    public function __construct(
        protected readonly IconFactory $iconFactory,
        protected readonly PageRenderer $pageRenderer,
        protected readonly EventDispatcherInterface $eventDispatcher,
        protected readonly UriBuilder $uriBuilder,
        protected readonly ModuleTemplateFactory $moduleTemplateFactory,
    ) {
    }

    public function mainAction(ServerRequestInterface $request): ResponseInterface
    {
        $languageService = $this->getLanguageService();
        $backendUser = $this->getBackendUserAuthentication();
        $parsedBody = $request->getParsedBody();
        $queryParams = $request->getQueryParams();

        $languageService->includeLLFile('EXT:core/Resources/Private/Language/locallang_mod_web_list.xlf');
        $this->pageRenderer->addInlineLanguageLabelFile('EXT:core/Resources/Private/Language/locallang_mod_web_list.xlf');

        BackendUtility::lockRecords();
        $perms_clause = $backendUser->getPagePermsClause(Permission::PAGE_SHOW);
        $this->id = (int)($parsedBody['id'] ?? $queryParams['id'] ?? 0);
        $pointer = max(0, (int)($parsedBody['pointer'] ?? $queryParams['pointer'] ?? 0));
        $singleTable = (string)($parsedBody['table'] ?? $queryParams['table'] ?? '');
        $search_field = (string)($parsedBody['search_field'] ?? $queryParams['search_field'] ?? '');
        $search_levels = (int)($parsedBody['search_levels'] ?? $queryParams['search_levels'] ?? 0);
        $this->returnUrl = GeneralUtility::sanitizeLocalUrl((string)($parsedBody['returnUrl'] ?? $queryParams['returnUrl'] ?? ''));
        $cmd = (string)($parsedBody['cmd'] ?? $queryParams['cmd'] ?? '');
        $siteLanguages = $request->getAttribute('site')->getAvailableLanguages($this->getBackendUserAuthentication(), false, $this->id);

        // Loading module configuration, clean up settings, current page and page access
        $this->modTSconfig = BackendUtility::getPagesTSconfig($this->id)['mod.']['web_list.'] ?? [];
        $MOD_SETTINGS = BackendUtility::getModuleData(['clipBoard' => ''], (array)($parsedBody['SET'] ?? $queryParams['SET'] ?? []), 'web_list');
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

        $MOD_SETTINGS['clipBoard'] = true;
        if (isset($this->modTSconfig['enableClipBoard'])) {
            if ($this->modTSconfig['enableClipBoard'] === 'activated') {
                $MOD_SETTINGS['clipBoard'] = true;
            } elseif ($this->modTSconfig['enableClipBoard'] === 'deactivated') {
                $MOD_SETTINGS['clipBoard'] = false;
            }
        }

        $dbList = GeneralUtility::makeInstance(DatabaseRecordList::class);
        $dbList->setModuleData($MOD_SETTINGS ?? []);
        $dbList->calcPerms = $this->pagePermissions;
        $dbList->returnUrl = $this->returnUrl;
        $dbList->showClipboardActions = true;
        $dbList->disableSingleTableView = $this->modTSconfig['disableSingleTableView'] ?? false;
        $dbList->listOnlyInSingleTableMode = $this->modTSconfig['listOnlyInSingleTableView'] ?? false;
        $dbList->hideTables = $this->modTSconfig['hideTables'] ?? false;
        $dbList->hideTranslations = $this->modTSconfig['hideTranslations'] ?? false;
        $dbList->tableTSconfigOverTCA = $this->modTSconfig['table.'] ?? false;
        $dbList->allowedNewTables = GeneralUtility::trimExplode(',', $this->modTSconfig['allowedNewTables'] ?? '', true);
        $dbList->deniedNewTables = GeneralUtility::trimExplode(',', $this->modTSconfig['deniedNewTables'] ?? '', true);
        $dbList->pageRow = $this->pageInfo;
        $dbList->modTSconfig = $this->modTSconfig;
        $dbList->setLanguagesAllowedForUser($siteLanguages);
        $clickTitleMode = trim($this->modTSconfig['clickTitleMode'] ?? '');
        $dbList->clickTitleMode = $clickTitleMode === '' ? 'edit' : $clickTitleMode;
        if (isset($this->modTSconfig['tableDisplayOrder.'])) {
            $typoScriptService = GeneralUtility::makeInstance(TypoScriptService::class);
            $dbList->setTableDisplayOrder($typoScriptService->convertTypoScriptArrayToPlainArray($this->modTSconfig['tableDisplayOrder.']));
        }
        $clipboard = $this->initializeClipboard($request, (bool)$MOD_SETTINGS['clipBoard']);
        $dbList->clipObj = $clipboard;
        /** @var RenderAdditionalContentToRecordListEvent $additionalRecordListEvent */
        $additionalRecordListEvent = $this->eventDispatcher->dispatch(new RenderAdditionalContentToRecordListEvent($request));

        $view = $this->moduleTemplateFactory->create($request, 'typo3/cms-recordlist');

        $tableListHtml = '';
        if ($access || ($this->id === 0 && $search_levels !== 0 && $search_field !== '')) {
            // If there is access to the page or root page is used for searching, then perform actions and render table list.
            if ($cmd === 'delete' && $request->getMethod() === 'POST') {
                $this->deleteRecords($request, $clipboard);
            }
            $dbList->start($this->id, $singleTable, $pointer, $search_field, $search_levels);
            $dbList->setDispFields();
            $tableListHtml = $dbList->generateList();
        }

        if (!$this->id) {
            $title = $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'];
        } else {
            $title = $pageinfo['title'] ?? '';
        }
        $languageSelectorHtml = '';
        if ($this->id && !$search_field && !$cmd && !$singleTable) {
            // Show the selector to add page translations, but only when in "default" mode.
            $languageSelectorHtml = $this->languageSelector($siteLanguages, $request->getAttribute('normalizedParams')->getRequestUri());
        }
        $pageTranslationsHtml = '';
        if ($this->id && !$search_field && !$cmd && !$singleTable && $this->showPageTranslations()) {
            // Show page translation table if there are any and display is allowed.
            $pageTranslationsHtml = $this->renderPageTranslations($dbList, $siteLanguages, $this->id);
        }
        $searchBoxHtml = '';
        if (!($this->modTSconfig['disableSearchBox'] ?? false) && ($tableListHtml || !empty($search_field))) {
            $searchBoxHtml = $this->renderSearchBox($view, $dbList, $search_field, $search_levels);
        }
        $toggleClipboardHtml = '';
        if ($tableListHtml && ($this->modTSconfig['enableClipBoard'] ?? '') === 'selectable') {
            $toggleClipboardHtml = $this->renderToggleClipboardHtml($this->id, $singleTable, $MOD_SETTINGS['clipBoard'] ?? false);
        }
        $clipboardHtml = '';
        if ($MOD_SETTINGS['clipBoard'] && ($tableListHtml || $clipboard->hasElements())) {
            $clipboardHtml = '<typo3-backend-clipboard-panel return-url="' . htmlspecialchars($dbList->listURL()) . '"></typo3-backend-clipboard-panel>';
        }

        $view->setTitle($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_mod_web_list.xlf:mlang_tabs_tab'), $title);
        if (empty($tableListHtml)) {
            $this->addNoRecordsFlashMessage($view, $singleTable);
        }
        if ($pageinfo) {
            $view->getDocHeaderComponent()->setMetaInformation($pageinfo);
        }
        $this->getDocHeaderButtons($view, $clipboard, $queryParams, $singleTable, $dbList->listURL(), $MOD_SETTINGS);
        $view->assignMultiple([
            'pageId' => $this->id,
            'pageTitle' => $title,
            'isPageEditable' => $this->isPageEditable(),
            'singleTable' => $singleTable,
            'additionalContentTop' => $additionalRecordListEvent->getAdditionalContentAbove(),
            'languageSelectorHtml' => $languageSelectorHtml,
            'pageTranslationsHtml' => $pageTranslationsHtml,
            'searchBoxHtml' => $searchBoxHtml,
            'tableListHtml' => $tableListHtml,
            'toggleClipboardHtml' => $toggleClipboardHtml,
            'clipboardHtml' => $clipboardHtml,
            'additionalContentBottom' => $additionalRecordListEvent->getAdditionalContentBelow(),
        ]);
        return $view->renderResponse('RecordList');
    }

    /**
     * Process incoming data and configure the clipboard.
     */
    protected function initializeClipboard(ServerRequestInterface $request, bool $isClipboardShown): Clipboard
    {
        $clipboard = GeneralUtility::makeInstance(Clipboard::class);
        $cmd = (string)($request->getParsedBody()['cmd'] ?? $request->getQueryParams()['cmd'] ?? '');
        // Initialize - reads the clipboard content from the user session
        $clipboard->initializeClipboard($request);
        // Clipboard actions are handled:
        $clipboardCommandArray = array_replace_recursive($request->getQueryParams()['CB'] ?? [], $request->getParsedBody()['CB'] ?? []);
        if ($cmd === 'copyMarked' || $cmd === 'removeMarked') {
            // Get CBC from request, and map the element values (true => copy, false => remove)
            $CBC = array_map(static fn () => ($cmd === 'copyMarked'), (array)($request->getParsedBody()['CBC'] ?? []));
            $cmd_table = (string)($request->getParsedBody()['cmd_table'] ?? $request->getQueryParams()['cmd_table'] ?? '');
            // Cleanup CBC
            $clipboardCommandArray['el'] = $clipboard->cleanUpCBC($CBC, $cmd_table);
        }
        if (!$isClipboardShown) {
            // If the clipboard is NOT shown, set the pad to 'normal'.
            $clipboardCommandArray['setP'] = 'normal';
        }
        // Execute commands.
        $clipboard->setCmd($clipboardCommandArray);
        // Clean up pad
        $clipboard->cleanCurrent();
        // Save the clipboard content
        $clipboard->endClipboard();
        return $clipboard;
    }

    protected function deleteRecords(ServerRequestInterface $request, Clipboard $clipboard): void
    {
        // This is the 'delete' button in table header with multi record selection.
        // The clipboard object is used to clean up the submitted entries to only the selected table.
        $parsedBody = $request->getParsedBody();
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

    protected function renderSearchBox(ModuleTemplate $view, DatabaseRecordList $dbList, string $searchWord, int $searchLevels): string
    {
        $searchBoxVisible = !empty($dbList->searchString);
        $searchBox = GeneralUtility::makeInstance(RecordSearchBoxComponent::class)
            ->setAllowedSearchLevels((array)($this->modTSconfig['searchLevel.']['items.'] ?? []))
            ->setSearchWord($searchWord)
            ->setSearchLevel($searchLevels)
            ->render($dbList->listURL('', '-1', 'pointer,search_field'));

        $buttonBar = $view->getDocHeaderComponent()->getButtonBar();
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
     * Create the panel of buttons for submitting the form or otherwise perform operations.
     */
    protected function getDocHeaderButtons(ModuleTemplate $view, Clipboard $clipboard, array $queryParams, string $table, string $listUrl, array $moduleSettings): void
    {
        $buttonBar = $view->getDocHeaderComponent()->getButtonBar();
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
                ->setTitle($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_mod_web_list.xlf:newRecordGeneral'))
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
            // If edit permissions are set, see BackendUserAuthentication
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
                    ->setHref((string)$editLink)
                    ->setTitle($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_mod_web_list.xlf:editPage'))
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
                    ->setTitle($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_mod_web_list.xlf:clip_paste'))
                    ->setClasses('t3js-modal-trigger')
                    ->setDataAttributes([
                        'severity' => 'warning',
                        'bs-content' => $confirmMessage,
                        'title' => $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_mod_web_list.xlf:clip_paste'),
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

    protected function addNoRecordsFlashMessage(ModuleTemplate $view, string $singleTable)
    {
        $languageService = $this->getLanguageService();
        if ($singleTable && isset($GLOBALS['TCA'][$singleTable]['ctrl']['title'])) {
            if (str_starts_with($GLOBALS['TCA'][$singleTable]['ctrl']['title'], 'LLL:')) {
                $message = sprintf($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_mod_web_list.xlf:noRecordsOfTypeOnThisPage'), $languageService->sL($GLOBALS['TCA'][$singleTable]['ctrl']['title']));
            } else {
                $message = sprintf($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_mod_web_list.xlf:noRecordsOfTypeOnThisPage'), $GLOBALS['TCA'][$singleTable]['ctrl']['title']);
            }
        } else {
            $message = $languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_mod_web_list.xlf:noRecordsOnThisPage');
        }
        $view->addFlashMessage($message, '', AbstractMessage::INFO);
    }

    /**
     * Make selector box for creating new translation in a language
     * Displays only languages which are not yet present for the current page and
     * that are not disabled with page TS.
     *
     * @param string $requestUri
     * @return string HTML <select> element (if there were items for the box anyways...)
     * @throws RouteNotFoundException
     */
    protected function languageSelector(array $siteLanguages, string $requestUri): string
    {
        if (!$this->getBackendUserAuthentication()->check('tables_modify', 'pages')) {
            return '';
        }
        $availableTranslations = [];
        foreach ($siteLanguages as $siteLanguage) {
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
     * Check whether the current backend user is an admin or the current page is locked by edit lock.
     */
    protected function editLockPermissions(): bool
    {
        return $this->getBackendUserAuthentication()->isAdmin() || !($this->pageInfo['editlock'] ?? false);
    }

    /**
     * Returns the shortcut title for the current page.
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

    protected function renderPageTranslations(DatabaseRecordList $dbList, array $siteLanguages, int $pageId): string
    {
        $pageTranslationsDatabaseRecordList = clone $dbList;
        $pageTranslationsDatabaseRecordList->listOnlyInSingleTableMode = false;
        $pageTranslationsDatabaseRecordList->disableSingleTableView = true;
        $pageTranslationsDatabaseRecordList->deniedNewTables = ['pages'];
        $pageTranslationsDatabaseRecordList->hideTranslations = '';
        $pageTranslationsDatabaseRecordList->setLanguagesAllowedForUser($siteLanguages);
        $pageTranslationsDatabaseRecordList->showOnlyTranslatedRecords(true);
        return $pageTranslationsDatabaseRecordList->getTable('pages', $pageId);
    }

    public function renderToggleClipboardHtml(int $pageId, string $singleTable, bool $checked): string
    {
        $languageService = $this->getLanguageService();
        $html = [];
        $html[] = '<div class="mb-3">';
        $html[] =   '<div class="form-check form-switch">';
        $html[] =       BackendUtility::getFuncCheck($pageId, 'SET[clipBoard]', $checked, '', $singleTable ? '&table=' . $singleTable : '', 'id="checkShowClipBoard"');
        $html[] =       '<label class="form-check-label" for="checkShowClipBoard">';
        $html[] =           BackendUtility::wrapInHelp('xMOD_csh_corebe', 'list_options', htmlspecialchars($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_mod_web_list.xlf:showClipBoard')));
        $html[] =       '</label>';
        $html[] =   '</div>';
        $html[] = '</div>';
        return implode(LF, $html);
    }

    /**
     * Check if page can be edited by current user
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

    protected function getBackendUserAuthentication(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
