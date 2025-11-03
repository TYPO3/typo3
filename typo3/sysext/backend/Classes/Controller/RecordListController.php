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

namespace TYPO3\CMS\Backend\Controller;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use TYPO3\CMS\Backend\Attribute\AsController;
use TYPO3\CMS\Backend\Clipboard\Clipboard;
use TYPO3\CMS\Backend\Clipboard\Type\CountMode;
use TYPO3\CMS\Backend\Controller\Event\RenderAdditionalContentToRecordListEvent;
use TYPO3\CMS\Backend\Module\ModuleData;
use TYPO3\CMS\Backend\RecordList\DatabaseRecordList;
use TYPO3\CMS\Backend\Routing\PreviewUriBuilder;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Security\SudoMode\Exception\VerificationRequiredException;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\Components\ComponentFactory;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\View\RecordSearchBoxComponent;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\WorkspaceRestriction;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Error\Http\BadRequestException;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Schema\Capability\TcaSchemaCapability;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\TypoScript\TypoScriptService;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Versioning\VersionState;

/**
 * The Web > List module: Rendering the listing of records on a page.
 *
 * @internal This class is a specific Backend controller implementation and is not part of the TYPO3's Core API.
 */
#[AsController]
class RecordListController
{
    /**
     * @var Permission
     */
    protected $pagePermissions;

    protected int $id = 0;
    protected string $table = '';
    protected string $searchTerm = '';
    protected array $pageInfo = [];
    protected string $returnUrl = '';
    protected array $modTSconfig = [];
    protected ?ModuleData $moduleData = null;
    protected bool $allowClipboard = true;
    protected bool $allowSearch = true;
    protected int $currentSelectedLanguage;

    public function __construct(
        private readonly ComponentFactory $componentFactory,
        protected readonly IconFactory $iconFactory,
        protected readonly PageRenderer $pageRenderer,
        protected readonly EventDispatcherInterface $eventDispatcher,
        protected readonly UriBuilder $uriBuilder,
        protected readonly ModuleTemplateFactory $moduleTemplateFactory,
        protected readonly TcaSchemaFactory $tcaSchemaFactory,
        protected readonly FlashMessageService $flashMessageService,
    ) {}

    public function mainAction(ServerRequestInterface $request): ResponseInterface
    {
        $this->moduleData = $request->getAttribute('moduleData');

        $languageService = $this->getLanguageService();
        $backendUser = $this->getBackendUserAuthentication();
        $parsedBody = $request->getParsedBody();
        $queryParams = $request->getQueryParams();

        $this->pageRenderer->addInlineLanguageLabelFile('EXT:core/Resources/Private/Language/locallang_mod_web_list.xlf');
        $this->pageRenderer->loadJavaScriptModule('@typo3/backend/element/dispatch-modal-button.js');

        BackendUtility::lockRecords();
        $perms_clause = $backendUser->getPagePermsClause(Permission::PAGE_SHOW);
        $this->id = (int)($parsedBody['id'] ?? $queryParams['id'] ?? 0);
        $pointer = max(0, (int)($parsedBody['pointer'] ?? $queryParams['pointer'] ?? 0));
        $this->table = (string)($parsedBody['table'] ?? $queryParams['table'] ?? '');
        $this->searchTerm = trim((string)($parsedBody['searchTerm'] ?? $queryParams['searchTerm'] ?? ''));
        $this->returnUrl = GeneralUtility::sanitizeLocalUrl((string)($parsedBody['returnUrl'] ?? $queryParams['returnUrl'] ?? ''));
        $cmd = (string)($parsedBody['cmd'] ?? $queryParams['cmd'] ?? '');
        $siteLanguages = $request->getAttribute('site')->getAvailableLanguages($backendUser, false, $this->id);
        $this->currentSelectedLanguage = (int)$this->moduleData->get('language');

        // Loading module configuration, clean up settings, current page and page access
        $this->modTSconfig = BackendUtility::getPagesTSconfig($this->id)['mod.']['web_list.'] ?? [];
        $pageinfo = BackendUtility::readPageAccess($this->id, $perms_clause);
        $access = is_array($pageinfo);
        $this->pageInfo = is_array($pageinfo) ? $pageinfo : [];
        $this->pagePermissions = new Permission($backendUser->calcPerms($pageinfo));

        // Check if Clipboard is allowed to be shown:
        if (($this->modTSconfig['enableClipBoard'] ?? '') === 'activated') {
            $this->moduleData->set('clipBoard', true);
            $this->allowClipboard = false;
        } elseif (($this->modTSconfig['enableClipBoard'] ?? '') === 'selectable') {
            $this->allowClipboard = true;
        } elseif (($this->modTSconfig['enableClipBoard'] ?? '') === 'deactivated') {
            $this->moduleData->set('clipBoard', false);
            $this->allowClipboard = false;
        }

        // Check if SearchBox is allowed to be shown:
        $this->allowSearch = !($this->modTSconfig['disableSearchBox'] ?? false);

        // Overwrite to show search on search request
        if (!empty($this->searchTerm)) {
            $this->allowSearch = true;
            $this->moduleData->set('searchBox', true);
        }

        // Get search levels from request or fall back to default, set in TSconifg
        $search_levels = (int)($parsedBody['search_levels'] ?? $queryParams['search_levels'] ?? $this->modTSconfig['searchLevel.']['default'] ?? 0);

        $dbList = GeneralUtility::makeInstance(DatabaseRecordList::class);
        $dbList->setRequest($request);
        $dbList->setModuleData($this->moduleData);
        $dbList->calcPerms = $this->pagePermissions;
        $dbList->returnUrl = $this->returnUrl;
        $dbList->showClipboardActions = true;
        $dbList->disableSingleTableView = $this->modTSconfig['disableSingleTableView'] ?? false;
        $dbList->listOnlyInSingleTableMode = $this->modTSconfig['listOnlyInSingleTableView'] ?? false;
        $dbList->hideTables = $this->modTSconfig['hideTables'] ?? '';
        $dbList->hideTranslations = (string)($this->modTSconfig['hideTranslations'] ?? '');
        $dbList->tableTSconfigOverTCA = $this->modTSconfig['table.'] ?? [];
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
        $clipboard = $this->initializeClipboard($request, (bool)$this->moduleData->get('clipBoard'));
        $dbList->clipObj = $clipboard;
        $additionalRecordListEvent = $this->eventDispatcher->dispatch(new RenderAdditionalContentToRecordListEvent($request));

        $view = $this->moduleTemplateFactory->create($request);

        $tableListHtml = '';
        if ($access || ($this->id === 0 && $search_levels !== 0 && $this->searchTerm !== '')) {
            // If there is access to the page or root page is used for searching, then perform actions and render table list.
            if ($cmd === 'delete' && $request->getMethod() === 'POST') {
                $this->deleteRecords($request, $clipboard);
            }
            $dbList->start($this->id, $this->table, $pointer, $this->searchTerm, $search_levels);
            $tableListHtml = $dbList->generateList();
        }

        if (!$this->id) {
            $title = $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'];
        } else {
            $title = $pageinfo['title'] ?? '';
        }
        $pageTranslationsHtml = '';
        if ($this->id && !$this->searchTerm && !$cmd && !$this->table && $this->showPageTranslations()) {
            // Show page translation table if there are any and display is allowed.
            $pageTranslationsHtml = $this->renderPageTranslations($dbList, $siteLanguages);
        }
        $searchBoxHtml = '';
        if ($this->allowSearch && $this->moduleData->get('searchBox')) {
            $searchBoxHtml = $this->renderSearchBox($request, $dbList, $this->searchTerm, $search_levels);
        }
        $clipboardHtml = '';
        if ($this->moduleData->get('clipBoard') && ($tableListHtml || $clipboard->hasElements())) {
            $clipboardHtml = '<hr class="spacer"><typo3-backend-clipboard-panel return-url="' . htmlspecialchars((string)$dbList->listURL()) . '"></typo3-backend-clipboard-panel>';
        }

        $view->setTitle($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_mod_web_list.xlf:mlang_tabs_tab'), $title);
        if (empty($tableListHtml)) {
            $this->addNoRecordsFlashMessage($view, $this->table);
        }
        if ($pageinfo) {
            $view->getDocHeaderComponent()->setPageBreadcrumb($pageinfo);
        }
        $this->getDocHeaderButtons($view, $clipboard, $request, $this->table, $dbList->listURL(), [], $siteLanguages);
        $view->assignMultiple([
            'pageId' => $this->id,
            'pageTitle' => $title,
            'isPageEditable' => $this->isPageEditable(),
            'additionalContentTop' => $additionalRecordListEvent->getAdditionalContentAbove(),
            'pageTranslationsHtml' => $pageTranslationsHtml,
            'searchBoxHtml' => $searchBoxHtml,
            'tableListHtml' => $tableListHtml,
            'clipboardHtml' => $clipboardHtml,
            'additionalContentBottom' => $additionalRecordListEvent->getAdditionalContentBelow(),
        ]);
        return $view->renderResponse('RecordList');
    }

    public function toggleRecordVisibilityAction(ServerRequestInterface $request): ResponseInterface
    {
        $table = $request->getParsedBody()['table'] ?? null;
        $uid = $request->getParsedBody()['uid'] ?? null;
        $action = $request->getParsedBody()['action'] ?? null;

        try {
            if (!isset($action, $table, $uid)) {
                throw new BadRequestException('Any of the mandatory argument "table", "uid", "action" is missing', 1729161415);
            }

            if ($action !== 'show' && $action !== 'hide') {
                throw new BadRequestException(sprintf('Passed "action" value must be either "show" or "hide", "%s" given', $action), 1729161479);
            }

            if (!$this->tcaSchemaFactory->has($table)) {
                throw new BadRequestException(sprintf('Cannot execute action for non-existent table "%s"', $table), 1738593519);
            }

            $schema = $this->tcaSchemaFactory->get($table);
            if (!$schema->hasCapability(TcaSchemaCapability::RestrictionDisabledField)) {
                throw new \InvalidArgumentException(sprintf('TCA table "%s" does not support record visibility', $table), 1729166628);
            }

            if (BackendUtility::getRecord($table, $uid, 'uid') === null) {
                throw new BadRequestException(sprintf('A record with uid %d was not found', $uid), 1739376253);
            }

            $hiddenField = $schema->getCapability(TcaSchemaCapability::RestrictionDisabledField)->getFieldName();

            $dataHandlerDataMap = [
                $table => [
                    $uid => [
                        $hiddenField => $action === 'show' ? 0 : 1,
                    ],
                ],
            ];

            /** @var DataHandler $dataHandler */
            $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
            $dataHandler->start($dataHandlerDataMap, []);
            $dataHandler->process_datamap();

            // Prints errors (= write them to the message queue)
            $dataHandler->printLogErrorMessages();

            $response = [
                'messages' => [],
                'hasErrors' => false,
            ];

            // Basically the same as in \TYPO3\CMS\Backend\RecordList\DatabaseRecordList->getFieldsToSelect()
            $selectFields = [];
            $selectFields[] = 'uid';
            $selectFields[] = 'pid';
            $selectFields[] = $schema->getCapability(TcaSchemaCapability::RestrictionDisabledField)->getFieldName();

            if ($table === 'pages') {
                $selectFields[] = 'module';
                $selectFields[] = 'extendToSubpages';
                $selectFields[] = 'nav_hide';
                $selectFields[] = 'doktype';
                $selectFields[] = 'shortcut';
                $selectFields[] = 'shortcut_mode';
                $selectFields[] = 'mount_pid';
            }

            $row = BackendUtility::getRecord($table, $uid, implode(',', $selectFields));
            if ($row !== null) {
                // Get new record icon
                $recordIcon = $this->iconFactory->getIconForRecord($table, $row, IconSize::SMALL);

                $response['icon'] = $recordIcon->render();
                $response['isVisible'] = (int)$row[$hiddenField] === 0;
            }

            $messages = $this->flashMessageService->getMessageQueueByIdentifier()->getAllMessagesAndFlush();
            foreach ($messages as $message) {
                $response['messages'][] = [
                    'title'    => $message->getTitle(),
                    'message'  => $message->getMessage(),
                    'severity' => $message->getSeverity(),
                ];
                if ($message->getSeverity() === ContextualFeedbackSeverity::ERROR) {
                    $response['hasErrors'] = true;
                }
            }
        } catch (VerificationRequiredException $e) {
            // Handled by Middleware/SudoModeInterceptor
            throw $e;
        } catch (\Throwable $e) {
            // @todo: having this explicit handling here sucks
            $response = [
                'messages' => [
                    [
                        'title' => 'An exception occurred',
                        'message' => $e->getMessage(),
                        'severity' => ContextualFeedbackSeverity::ERROR,
                    ],
                ],
                'hasErrors' => true,
            ];
        }

        return new JsonResponse($response, $response['hasErrors'] ? 400 : 200);
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
            $CBC = array_map(static fn(): bool => ($cmd === 'copyMarked'), (array)($request->getParsedBody()['CBC'] ?? []));
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

    protected function renderSearchBox(ServerRequestInterface $request, DatabaseRecordList $dbList, string $searchWord, int $searchLevels): string
    {
        $searchBox = GeneralUtility::makeInstance(RecordSearchBoxComponent::class)
            ->setAllowedSearchLevels((array)($this->modTSconfig['searchLevel.']['items.'] ?? []))
            ->setSearchWord($searchWord)
            ->setSearchLevel($searchLevels)
            ->render($request, $dbList->listURL('', '-1', 'pointer,searchTerm'));
        return $searchBox;
    }

    /**
     * Create the panel of buttons for submitting the form or otherwise perform operations.
     *
     * @param SiteLanguage[] $availableLanguages
     */
    protected function getDocHeaderButtons(ModuleTemplate $view, Clipboard $clipboard, ServerRequestInterface $request, string $table, UriInterface $listUrl, array $moduleSettings, array $availableLanguages): void
    {
        $queryParams = $request->getQueryParams();
        $lang = $this->getLanguageService();

        // Language selector (top right area)
        $this->createLanguageSelector($view, $availableLanguages);

        if ($table !== 'tt_content' && !($this->modTSconfig['noCreateRecordsLink'] ?? false) && $this->editLockPermissions()) {
            // New record button if: table is not tt_content - tt_content should be managed in page module, link is
            // not disabled via TSconfig, page is not 'edit locked'
            $newRecordButton = $this->componentFactory->createLinkButton()
                ->setHref((string)$this->uriBuilder->buildUriFromRoute('db_new', ['id' => $this->id, 'returnUrl' => $listUrl]))
                ->setTitle($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_mod_web_list.xlf:newRecordGeneral'))
                ->setShowLabelText(true)
                ->setIcon($this->iconFactory->getIcon('actions-plus', IconSize::SMALL));
            $view->addButtonToButtonBar($newRecordButton, ButtonBar::BUTTON_POSITION_LEFT, 10);
        }

        if ($this->id !== 0) {
            $uriBuilder = PreviewUriBuilder::create($this->pageInfo);
            if ($uriBuilder->isPreviewable()) {
                $view->addButtonToButtonBar(
                    $this->componentFactory->createViewButton(PreviewUriBuilder::create($this->pageInfo)
                        ->withRootLine(BackendUtility::BEgetRootLine($this->id))
                        ->buildDispatcherDataAttributes() ?? []),
                    ButtonBar::BUTTON_POSITION_LEFT,
                    15
                );
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
                    'module' => 'web_list',
                    'returnUrl' => $listUrl,
                ]);
                $editButton = $this->componentFactory->createLinkButton()
                    ->setHref((string)$editLink)
                    ->setTitle($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_mod_web_list.xlf:editPage'))
                    ->setShowLabelText(true)
                    ->setIcon($this->iconFactory->getIcon('actions-page-open', IconSize::SMALL));
                $view->addButtonToButtonBar($editButton, ButtonBar::BUTTON_POSITION_LEFT, 20);
            }
        }

        // Paste
        if (($this->pagePermissions->createPagePermissionIsGranted() || $this->pagePermissions->editContentPermissionIsGranted()) && $this->editLockPermissions()) {
            $elFromTable = $clipboard->elFromTable();
            if (!empty($elFromTable)) {
                $confirmMessage = $clipboard->confirmMsgText('pages', $this->pageInfo, 'into', CountMode::ALL);
                $pasteButton = $this->componentFactory->createLinkButton()
                    ->setHref($clipboard->pasteUrl('', $this->id))
                    ->setTitle($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_mod_web_list.xlf:clip_paste'))
                    ->setClasses('t3js-modal-trigger')
                    ->setDataAttributes([
                        'severity' => 'warning',
                        'bs-content' => $confirmMessage,
                        'title' => $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_mod_web_list.xlf:clip_paste'),
                    ])
                    ->setIcon($this->iconFactory->getIcon('actions-document-paste-into', IconSize::SMALL))
                    ->setShowLabelText(true);
                $view->addButtonToButtonBar($pasteButton, ButtonBar::BUTTON_POSITION_LEFT, 40);
            }
        }
        // Cache
        if ($this->id !== 0) {
            $clearCacheButton = $this->componentFactory->createLinkButton()
                ->setHref('#')
                ->setDataAttributes(['id' => $this->id])
                ->setClasses('t3js-clear-page-cache')
                ->setTitle($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.clear_cache'))
                ->setIcon($this->iconFactory->getIcon('actions-system-cache-clear', IconSize::SMALL));
            $view->addButtonToButtonBar($clearCacheButton, ButtonBar::BUTTON_POSITION_RIGHT);
        }
        if ($table
            && !($this->modTSconfig['noExportRecordsLinks'] ?? false)
            && $this->getBackendUserAuthentication()->isExportEnabled()
        ) {
            // Export
            if (ExtensionManagementUtility::isLoaded('impexp')) {
                $url = (string)$this->uriBuilder->buildUriFromRoute('tx_impexp_export', ['tx_impexp' => ['list' => [$table . ':' . $this->id]]]);
                $exportButton = $this->componentFactory->createLinkButton()
                    ->setHref($url)
                    ->setTitle($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:rm.export'))
                    ->setIcon($this->iconFactory->getIcon('actions-document-export-t3d', IconSize::SMALL))
                    ->setShowLabelText(true);
                $view->addButtonToButtonBar($exportButton, ButtonBar::BUTTON_POSITION_LEFT, 50);
            }
        }
        // Reload
        $view->addButtonToButtonBar($this->componentFactory->createReloadButton($listUrl), ButtonBar::BUTTON_POSITION_RIGHT);

        // ViewMode
        $viewModeItems = [];
        if ($this->allowSearch) {
            $viewModeItems[] = $this->componentFactory->createDropDownToggle()
                ->setActive((bool)$this->moduleData->get('searchBox'))
                ->setHref($this->createModuleUri($request, ['searchBox' => $this->moduleData->get('searchBox') ? 0 : 1, 'searchTerm' => '']))
                ->setLabel($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.view.showSearch'))
                ->setIcon($this->iconFactory->getIcon('actions-search'));
        }
        if ($this->allowClipboard) {
            $viewModeItems[] = $this->componentFactory->createDropDownToggle()
                ->setActive((bool)$this->moduleData->get('clipBoard'))
                ->setHref($this->createModuleUri($request, ['clipBoard' => $this->moduleData->get('clipBoard') ? 0 : 1]))
                ->setLabel($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.view.showClipboard'))
                ->setIcon($this->iconFactory->getIcon('actions-clipboard'));
        }
        if (!empty($viewModeItems)) {
            $viewModeButton = $this->componentFactory->createDropDownButton()
                ->setLabel($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.view'))
                ->setShowLabelText(true);
            foreach ($viewModeItems as $viewModeItem) {
                $viewModeButton->addItem($viewModeItem);
            }
            $view->addButtonToButtonBar($viewModeButton, ButtonBar::BUTTON_POSITION_RIGHT, 3);
        }

        // Shortcut
        $shortCutButton = $this->componentFactory->createShortcutButton()->setRouteIdentifier('web_list');
        $arguments = [
            'id' => $this->id,
        ];
        $potentialArguments = [
            'pointer',
            'table',
            'searchTerm',
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
        $view->addButtonToButtonBar($shortCutButton, ButtonBar::BUTTON_POSITION_RIGHT);

        // Back
        if ($this->returnUrl) {
            $view->addButtonToButtonBar($this->componentFactory->createBackButton($this->returnUrl));
        }
    }

    protected function addNoRecordsFlashMessage(ModuleTemplate $view, string $table)
    {
        $languageService = $this->getLanguageService();
        if ($table && $this->tcaSchemaFactory->has($table) && $this->tcaSchemaFactory->get($table)->getTitle() !== '') {
            $message = sprintf(
                $languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_mod_web_list.xlf:noRecordsOfTypeOnThisPage'),
                $this->tcaSchemaFactory->get($table)->getTitle($languageService->sL(...))
            );
        } else {
            $message = $languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_mod_web_list.xlf:noRecordsOnThisPage');
        }
        $view->addFlashMessage($message, '', ContextualFeedbackSeverity::INFO);
    }

    /**
     * Check whether the current backend user is an admin or the current page is locked by edit lock.
     */
    protected function editLockPermissions(): bool
    {
        return $this->getBackendUserAuthentication()->isAdmin()
            || !($schema = $this->tcaSchemaFactory->get('pages'))->hasCapability(TcaSchemaCapability::EditLock)
            || !($this->pageInfo[$schema->getCapability(TcaSchemaCapability::EditLock)->getFieldName()] ?? false);
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
            $tableName = $arguments['table'];
            if ($this->tcaSchemaFactory->has($tableName)) {
                $schema = $this->tcaSchemaFactory->get($tableName);
                $tableTitle = $schema->getTitle($languageService->sL(...));
            }
            $tableTitle = ': ' . ($tableTitle ?: $tableName);
        }
        if ($this->pageInfo !== []) {
            $pageTitle = BackendUtility::getRecordTitle('pages', $this->pageInfo);
        }
        return trim(sprintf(
            $languageService->sL('LLL:EXT:backend/Resources/Private/Language/locallang.xlf:shortcut.title'),
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
        $schema = $this->tcaSchemaFactory->get('pages');
        $hideTables = $this->modTSconfig['hideTables'] ?? '';
        return !$schema->hasCapability(TcaSchemaCapability::HideInUi)
            && $hideTables !== '*'
            && !in_array('pages', GeneralUtility::trimExplode(',', $hideTables), true);
    }

    protected function renderPageTranslations(DatabaseRecordList $dbList, array $siteLanguages): string
    {
        $pageTranslationsDatabaseRecordList = clone $dbList;
        $pageTranslationsDatabaseRecordList->id = $this->id;
        $pageTranslationsDatabaseRecordList->listOnlyInSingleTableMode = false;
        $pageTranslationsDatabaseRecordList->disableSingleTableView = true;
        $pageTranslationsDatabaseRecordList->deniedNewTables = ['pages'];
        $pageTranslationsDatabaseRecordList->hideTranslations = '';
        $pageTranslationsDatabaseRecordList->setLanguagesAllowedForUser($siteLanguages);
        $pageTranslationsDatabaseRecordList->showOnlyTranslatedRecords(true);
        return $pageTranslationsDatabaseRecordList->getTable('pages');
    }

    protected function createModuleUri(ServerRequestInterface $request, array $params = []): string
    {
        $params = array_replace_recursive([
            'id' => $this->id,
            'table' => $this->table,
            'searchTerm' => $this->searchTerm,
        ], $params);

        $params = array_filter($params, static function (mixed $value): bool {
            return $value !== null && trim((string)$value) !== '';
        });

        return (string)$this->uriBuilder->buildUriFromRequest($request, $params);
    }

    /**
     * @param SiteLanguage[] $availableLanguages
     */
    protected function createLanguageSelector(ModuleTemplate $view, array $availableLanguages): void
    {
        // Early return if less than 2 languages are available
        if (count($availableLanguages) <= 1) {
            return;
        }

        $languageService = $this->getLanguageService();
        $backendUser = $this->getBackendUserAuthentication();

        // Check if user can create new page translations
        $canCreateTranslations = $backendUser->check('tables_modify', 'pages');

        // Get existing page translations with workspace handling
        $existingTranslations = [];
        if ($this->id) {
            $schema = $this->tcaSchemaFactory->get('pages');
            $languageCapability = $schema->getCapability(TcaSchemaCapability::Language);
            $languageField = $languageCapability->getLanguageField()->getName();
            $translationOriginField = $languageCapability->getTranslationOriginPointerField()->getName();

            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
            $queryBuilder->getRestrictions()->removeAll()
                ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
                ->add(GeneralUtility::makeInstance(WorkspaceRestriction::class, $backendUser->workspace));
            $queryBuilder->select('*')
                ->from('pages')
                ->where(
                    $queryBuilder->expr()->eq(
                        $translationOriginField,
                        $queryBuilder->createNamedParameter($this->id, Connection::PARAM_INT)
                    )
                );
            $statement = $queryBuilder->executeQuery();
            while ($row = $statement->fetchAssociative()) {
                BackendUtility::workspaceOL('pages', $row, $backendUser->workspace);
                if ($row && VersionState::tryFrom($row['t3ver_state']) !== VersionState::DELETE_PLACEHOLDER) {
                    $existingTranslations[(int)$row[$languageField]] = $row;
                }
            }
        }

        // Check if current selected language exists, if not we fall back to default (0)
        // Note: -1 (all languages) is only valid when there are actual translations
        $currentLanguageExists = $this->currentSelectedLanguage === 0
            || ($this->currentSelectedLanguage === -1 && !empty($existingTranslations))
            || isset($existingTranslations[$this->currentSelectedLanguage]);

        $languageDropDownButton = $this->componentFactory->createDropDownButton()
            ->setLabel($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.language'))
            ->setShowActiveLabelText(true)
            ->setShowLabelText(true);

        $existingLanguageItems = [];
        $newLanguageItems = [];

        foreach ($availableLanguages as $siteLanguage) {

            $languageId = $siteLanguage->getLanguageId();
            $languageTitle = $siteLanguage->getTitle();

            // Skip languages that don't exist and user can't create
            if ($languageId > 0 && !isset($existingTranslations[$languageId]) && !$canCreateTranslations) {
                continue;
            }

            if ($languageId > 0 && !isset($existingTranslations[$languageId])) {
                // Translation doesn't exist - build URL to create it via DataHandler
                $returnUrl = (string)$this->uriBuilder->buildUriFromRoute(
                    'web_list',
                    [
                        'id' => $this->id,
                        'language' => $languageId,
                    ]
                );
                $href = (string)$this->uriBuilder->buildUriFromRoute(
                    'tce_db',
                    [
                        'cmd' => [
                            'pages' => [
                                $this->id => [
                                    'localize' => $languageId,
                                ],
                            ],
                        ],
                        'redirect' => (string)$this->uriBuilder->buildUriFromRoute(
                            'record_edit',
                            [
                                'justLocalized' => 'pages:' . $this->id . ':' . $languageId,
                                'returnUrl' => $returnUrl,
                            ]
                        ),
                    ]
                );

                $languageItem = $this->componentFactory->createDropDownItem()
                    ->setActive(false)
                    ->setIcon($this->iconFactory->getIcon($siteLanguage->getFlagIdentifier()))
                    ->setHref($href)
                    ->setLabel($languageTitle);
                $newLanguageItems[] = $languageItem;
            } else {
                // Translation exists or is default language - just switch view
                $href = (string)$this->uriBuilder->buildUriFromRoute('web_list', [
                    'id' => $this->id,
                    'language' => $languageId,
                ]);
                // If selected language doesn't exist, fall back to marking default (0) as active
                if ($currentLanguageExists) {
                    $isActive = $this->currentSelectedLanguage === $languageId;
                } else {
                    $isActive = $languageId === 0;
                }

                $languageItem = $this->componentFactory->createDropDownRadio()
                    ->setActive($isActive)
                    ->setIcon($this->iconFactory->getIcon($siteLanguage->getFlagIdentifier()))
                    ->setHref($href)
                    ->setLabel($languageTitle);
                $existingLanguageItems[] = $languageItem;
            }
        }

        // Add existing languages first
        foreach ($existingLanguageItems as $item) {
            $languageDropDownButton->addItem($item);
        }

        // Add "All languages" option if there are translations
        if (!empty($existingTranslations)) {
            $allLanguagesLabel = $languageService->sL('core.mod_web_list:multipleLanguages');
            $isAllLanguagesActive = $this->currentSelectedLanguage === -1;
            $allLanguagesItem = $this->componentFactory->createDropDownRadio()
                ->setActive($isAllLanguagesActive)
                ->setIcon($this->iconFactory->getIcon('flags-multiple'))
                ->setHref(
                    (string)$this->uriBuilder->buildUriFromRoute('web_list', [
                        'id' => $this->id,
                        'language' => -1,
                    ])
                )
                ->setLabel($allLanguagesLabel);
            $languageDropDownButton
                ->addItem($this->componentFactory->createDropDownDivider())
                ->addItem($allLanguagesItem);
        }

        // Add separator and new languages if any
        if (!empty($newLanguageItems)) {
            $languageDropDownButton->addItem($this->componentFactory->createDropDownDivider());
            $languageDropDownButton->addItem(
                $this->componentFactory->createDropDownHeader()
                    ->setLabel($languageService->sL('core.core:labels.new_page_translation'))
            );
            foreach ($newLanguageItems as $item) {
                $languageDropDownButton->addItem($item);
            }
        }

        $view->getDocHeaderComponent()->setLanguageSelector($languageDropDownButton);
    }

    /**
     * Creates the module menu configuration.
     *
     * @param SiteLanguage[] $availableLanguages
     * @return array{language: array<int, string>}
     */
    protected function getLanguageMenuConfiguration(array $availableLanguages): array
    {
        $backendUser = $this->getBackendUserAuthentication();
        $languageService = $this->getLanguageService();
        $translations = [];

        // MENU-ITEMS:
        $languageMenuConfiguration = [
            'language' => [
                0 => isset($availableLanguages[0]) ? $availableLanguages[0]->getTitle() : $languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_mod_web_list.xlf:defaultLanguage'),
            ],
        ];

        // First, select all localized page records on the current page.
        // Each represents a possibility for a language on the page. Add these to language selector.
        if ($this->id) {
            // Add all possible languages first for the cleanup to make sure we keep the selected language
            // when the user switches between pages with/without translations
            foreach ($availableLanguages as $language) {
                $languageMenuConfiguration['language'][$language->getLanguageId()] = $language->getTitle();
            }

            // Compile language data for pid != 0 only. The language drop-down is not shown on pid 0
            // since pid 0 can't be localized.
            $availableLanguageIds = array_map(static function ($siteLanguage) {
                return $siteLanguage->getLanguageId();
            }, $availableLanguages);

            $pageTranslations = BackendUtility::getExistingPageTranslations($this->id, $availableLanguageIds);
            $schema = $this->tcaSchemaFactory->get('pages');
            $languageCapability = $schema->getCapability(TcaSchemaCapability::Language);
            $languageField = $languageCapability->getLanguageField()->getName();
            foreach ($pageTranslations as $pageTranslation) {
                $languageId = $pageTranslation[$languageField];
                $translations[] = $languageId;
            }

            // Add special "-1" in case translations of the current page exist
            if (count($languageMenuConfiguration['language']) > 1) {
                // We need to add -1 (all) here so a possible -1 value will be allowed when calling
                // moduleData->cleanUp(). Actually, this is only relevant if we are dealing with the
                // "languages" mode, which however can only be safely determined, after the moduleData
                // have been cleaned up => chicken and egg problem. We therefore remove the -1 item from
                // the menu again, as soon as we are able to determine the requested mode.
                // @todo Replace the whole "mode" handling with some more robust solution
                $languageMenuConfiguration['language'][-1] = $languageService->sL('core.mod_web_list:multipleLanguages');
            }
        }
        // Clean up settings
        if ($this->moduleData->cleanUp($languageMenuConfiguration)) {
            $backendUser->pushModuleData($this->moduleData->getModuleIdentifier(), $this->moduleData->toArray());
        }

        // Remove all languages from $languageMenuConfiguration, which have no page translations after cleanup
        foreach ($languageMenuConfiguration['language'] as $languageId => $language) {
            if ($languageId > 0 && !in_array($languageId, $translations, true)) {
                unset($languageMenuConfiguration['language'][$languageId]);
            }
        }

        if ($translations === []) {
            // Remove -1 if we have no translations
            unset($languageMenuConfiguration['language'][-1]);

            // No translations -> set module data for the current request to default language
            $this->moduleData->set('language', 0);
        } elseif (!isset($languageMenuConfiguration['language'][$this->moduleData->get('language')])) {
            // If the currently selected language is not available on this page (no translation),
            // fall back to "all languages" (-1) temporarily for this page only (not persisted).
            // When navigating to a page with the selected language translation, it will be used again.
            $this->moduleData->set('language', -1);
        }

        return $languageMenuConfiguration;
    }

    /**
     * Check if page can be edited by current user
     */
    protected function isPageEditable(): bool
    {
        $schema = $this->tcaSchemaFactory->get('pages');

        if ($schema->hasCapability(TcaSchemaCapability::AccessReadOnly)) {
            return false;
        }
        $backendUser = $this->getBackendUserAuthentication();
        if ($backendUser->isAdmin()) {
            return true;
        }
        if ($schema->hasCapability(TcaSchemaCapability::AccessAdminOnly)) {
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
