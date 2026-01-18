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
use TYPO3\CMS\Backend\Attribute\AsController;
use TYPO3\CMS\Backend\Clipboard\Clipboard;
use TYPO3\CMS\Backend\Clipboard\Type\CountMode;
use TYPO3\CMS\Backend\Context\PageContext;
use TYPO3\CMS\Backend\Context\PageContextFactory;
use TYPO3\CMS\Backend\Controller\Event\RenderAdditionalContentToRecordListEvent;
use TYPO3\CMS\Backend\Module\ModuleData;
use TYPO3\CMS\Backend\RecordList\DatabaseRecordList;
use TYPO3\CMS\Backend\Routing\PreviewUriBuilder;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Security\SudoMode\Exception\VerificationRequiredException;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\Components\Buttons\LanguageSelectorBuilder;
use TYPO3\CMS\Backend\Template\Components\Buttons\LanguageSelectorMode;
use TYPO3\CMS\Backend\Template\Components\ComponentFactory;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\View\RecordSearchBoxComponent;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
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
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * The Content > Records module: Rendering the listing of records on a page.
 *
 * @internal This class is a specific Backend controller implementation and is not part of the TYPO3's Core API.
 */
#[AsController]
class RecordListController
{
    protected PageContext $pageContext;

    protected string $table = '';
    protected string $searchTerm = '';
    protected string $returnUrl = '';
    protected array $modTSconfig = [];
    protected ?ModuleData $moduleData = null;
    protected bool $allowClipboard = true;
    protected bool $allowSearch = true;

    public function __construct(
        private readonly ComponentFactory $componentFactory,
        protected readonly IconFactory $iconFactory,
        protected readonly PageRenderer $pageRenderer,
        protected readonly EventDispatcherInterface $eventDispatcher,
        protected readonly UriBuilder $uriBuilder,
        protected readonly ModuleTemplateFactory $moduleTemplateFactory,
        protected readonly TcaSchemaFactory $tcaSchemaFactory,
        protected readonly FlashMessageService $flashMessageService,
        protected readonly PageContextFactory $pageContextFactory,
        protected readonly LanguageSelectorBuilder $languageSelectorBuilder,
    ) {}

    public function mainAction(ServerRequestInterface $request): ResponseInterface
    {

        $pageContext = $request->getAttribute('pageContext');
        if (!$pageContext instanceof PageContext) {
            throw new \RuntimeException(
                'PageContext not initialized by middleware.',
                1731415238
            );
        }
        $this->pageContext = $pageContext;
        $this->moduleData = $request->getAttribute('moduleData');

        $languageService = $this->getLanguageService();
        $backendUser = $this->getBackendUserAuthentication();
        $parsedBody = $request->getParsedBody();
        $queryParams = $request->getQueryParams();

        $this->pageRenderer->addInlineLanguageLabelFile('EXT:core/Resources/Private/Language/locallang_mod_web_list.xlf');
        $this->pageRenderer->loadJavaScriptModule('@typo3/backend/element/dispatch-modal-button.js');

        BackendUtility::lockRecords();
        $pointer = max(0, (int)($parsedBody['pointer'] ?? $queryParams['pointer'] ?? 0));
        $this->table = (string)($parsedBody['table'] ?? $queryParams['table'] ?? '');
        $this->searchTerm = trim((string)($parsedBody['searchTerm'] ?? $queryParams['searchTerm'] ?? ''));
        $this->returnUrl = GeneralUtility::sanitizeLocalUrl((string)($parsedBody['returnUrl'] ?? $queryParams['returnUrl'] ?? ''));
        $cmd = (string)($parsedBody['cmd'] ?? $queryParams['cmd'] ?? '');

        // RecordList always requires default language (0) for proper record display
        // Similar to PageLayoutController's comparison mode behavior
        $languagesToDisplay = $this->pageContext->selectedLanguageIds;
        if (!in_array(0, $languagesToDisplay, true)) {
            $languagesToDisplay = array_merge([0], $languagesToDisplay);
            // Create updated PageContext with modified languages and update request
            $this->pageContext = $this->pageContextFactory->createWithLanguages(
                $request,
                $this->pageContext->pageId,
                $languagesToDisplay,
                $backendUser
            );
            $request = $request->withAttribute('pageContext', $this->pageContext);
        }
        $this->moduleData->set('languages', $languagesToDisplay);

        $siteLanguages = $this->pageContext->site->getAvailableLanguages($backendUser, false, $this->pageContext->pageId);
        $backendUser->pushModuleData($this->moduleData->getModuleIdentifier(), $this->moduleData->toArray());

        // Loading module configuration, clean up settings, current page and page access
        $this->modTSconfig = $this->pageContext->getModuleTsConfig('web_list');

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
        $search_levels = (int)($parsedBody['search_levels'] ?? $queryParams['search_levels'] ?? $this->modTSconfig['searchLevel']['default'] ?? 0);

        $dbList = GeneralUtility::makeInstance(DatabaseRecordList::class);
        $dbList->setRequest($request);
        $dbList->setModuleData($this->moduleData);
        $dbList->calcPerms = $this->pageContext->pagePermissions;
        $dbList->returnUrl = $this->returnUrl;
        $dbList->showClipboardActions = true;
        $dbList->disableSingleTableView = $this->modTSconfig['disableSingleTableView'] ?? false;
        $dbList->listOnlyInSingleTableMode = $this->modTSconfig['listOnlyInSingleTableView'] ?? false;
        $dbList->hideTables = $this->modTSconfig['hideTables'] ?? '';
        $dbList->hideTranslations = (string)($this->modTSconfig['hideTranslations'] ?? '');
        $dbList->tableTSconfigOverTCA = $this->modTSconfig['table'] ?? [];
        $dbList->allowedNewTables = GeneralUtility::trimExplode(',', $this->modTSconfig['allowedNewTables'] ?? '', true);
        $dbList->deniedNewTables = GeneralUtility::trimExplode(',', $this->modTSconfig['deniedNewTables'] ?? '', true);
        $dbList->pageRow = $this->pageContext->pageRecord ?? [];
        $dbList->modTSconfig = $this->modTSconfig;
        $dbList->setLanguagesAllowedForUser($siteLanguages);
        $clickTitleMode = trim($this->modTSconfig['clickTitleMode'] ?? '');
        $dbList->clickTitleMode = $clickTitleMode === '' ? 'edit' : $clickTitleMode;
        if (isset($this->modTSconfig['tableDisplayOrder'])) {
            $dbList->setTableDisplayOrder($this->modTSconfig['tableDisplayOrder']);
        }
        $clipboard = $this->initializeClipboard($request, (bool)$this->moduleData->get('clipBoard'));
        $dbList->clipObj = $clipboard;
        $additionalRecordListEvent = $this->eventDispatcher->dispatch(new RenderAdditionalContentToRecordListEvent($request));

        $view = $this->moduleTemplateFactory->create($request);

        $tableListHtml = '';
        if ($this->pageContext->isAccessible() || ($this->pageContext->pageId === 0 && $search_levels !== 0 && $this->searchTerm !== '')) {
            // If there is access to the page or root page is used for searching, then perform actions and render table list.
            if ($cmd === 'delete' && $request->getMethod() === 'POST') {
                $this->deleteRecords($request, $clipboard);
            }
            $dbList->start($this->pageContext->pageId, $this->table, $pointer, $this->searchTerm, $search_levels);
            $tableListHtml = $dbList->generateList();
        }

        if (!$this->pageContext->pageId) {
            $title = $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'];
        } else {
            $title = $this->pageContext->getPageTitle();
        }
        $pageTranslationsHtml = '';
        if ($this->pageContext->pageId && !$this->searchTerm && !$cmd && !$this->table && $this->showPageTranslations()) {
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

        $view->setTitle($languageService->translate('title', 'backend.modules.list'), $title);
        if (empty($tableListHtml)) {
            $this->addNoRecordsFlashMessage($view, $this->table);
        }
        if ($this->pageContext->pageRecord) {
            $view->getDocHeaderComponent()->setPageBreadcrumb($this->pageContext->pageRecord);
        }
        $this->getDocHeaderButtons($view, $clipboard, $request, $dbList);
        $view->assignMultiple([
            'pageId' => $this->pageContext->pageId,
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
                $selectFields[] = 'is_siteroot';
            }

            $row = BackendUtility::getRecord($table, $uid, $selectFields);
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
            ->setAllowedSearchLevels((array)($this->modTSconfig['searchLevel']['items'] ?? []))
            ->setSearchWord($searchWord)
            ->setSearchLevel($searchLevels)
            ->render($request, $dbList->listURL('', null, 'pointer,searchTerm'));
        return $searchBox;
    }

    /**
     * Create the panel of buttons for submitting the form or otherwise perform operations.
     */
    protected function getDocHeaderButtons(ModuleTemplate $view, Clipboard $clipboard, ServerRequestInterface $request, DatabaseRecordList $dbList): void
    {
        $queryParams = $request->getQueryParams();
        $lang = $this->getLanguageService();

        // Language selector (top right area)
        $this->createLanguageSelector($view, $request);

        if (!($this->modTSconfig['noCreateRecordsLink'] ?? false) && $this->editLockPermissions()) {
            if ($this->table === '') {
                // "General" new record button if: not in single table view, not disabled via TSconfig and page is not 'edit locked'
                $newRecordButton = $this->componentFactory->createLinkButton()
                    ->setHref((string)$this->uriBuilder->buildUriFromRoute('db_new', ['id' => $this->pageContext->pageId, 'returnUrl' => $dbList->listURL()]))
                    ->setTitle($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_mod_web_list.xlf:newRecordGeneral'))
                    ->setShowLabelText(true)
                    ->setIcon($this->iconFactory->getIcon('actions-plus', IconSize::SMALL));
                $view->addButtonToButtonBar($newRecordButton, ButtonBar::BUTTON_POSITION_LEFT, 10);
            } elseif (($createNewRecordButton = $dbList->createActionButtonNewRecord($this->table)) !== null) {
                // In single table view, render the specific create new button
                $view->addButtonToButtonBar($createNewRecordButton);
            }
        }

        if ($this->pageContext->isAccessible() && $this->pageContext->pageId > 0) {
            $uriBuilder = PreviewUriBuilder::create($this->pageContext->pageRecord);
            if ($uriBuilder->isPreviewable()) {
                $view->addButtonToButtonBar(
                    $this->componentFactory->createViewButton(PreviewUriBuilder::create($this->pageContext->pageRecord)
                        ->withRootLine($this->pageContext->rootLine)
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
                            $this->pageContext->pageId => 'edit',
                        ],
                    ],
                    'module' => 'records',
                    'returnUrl' => $dbList->listURL(),
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
        if (($this->pageContext->pagePermissions->createPagePermissionIsGranted() || $this->pageContext->pagePermissions->editContentPermissionIsGranted()) && $this->editLockPermissions()) {
            $elFromTable = $clipboard->elFromTable();
            if (!empty($elFromTable)) {
                $confirmMessage = $clipboard->confirmMsgText('pages', $this->pageContext->pageRecord, 'into', CountMode::ALL);
                $pasteButton = $this->componentFactory->createLinkButton()
                    ->setHref($clipboard->pasteUrl('', $this->pageContext->pageId))
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
        if ($this->pageContext->pageId) {
            $clearCacheButton = $this->componentFactory->createLinkButton()
                ->setHref('#')
                ->setDataAttributes(['id' => $this->pageContext->pageId])
                ->setClasses('t3js-clear-page-cache')
                ->setTitle($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.clear_cache'))
                ->setIcon($this->iconFactory->getIcon('actions-system-cache-clear', IconSize::SMALL));
            $view->addButtonToButtonBar($clearCacheButton, ButtonBar::BUTTON_POSITION_RIGHT);
        }
        if ($this->table
            && !($this->modTSconfig['noExportRecordsLinks'] ?? false)
            && $this->getBackendUserAuthentication()->isExportEnabled()
        ) {
            // Export
            if (ExtensionManagementUtility::isLoaded('impexp')) {
                $url = (string)$this->uriBuilder->buildUriFromRoute('tx_impexp_export', ['tx_impexp' => ['list' => [$this->table . ':' . $this->pageContext->pageId]]]);
                $exportButton = $this->componentFactory->createLinkButton()
                    ->setHref($url)
                    ->setTitle($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:rm.export'))
                    ->setIcon($this->iconFactory->getIcon('actions-document-export-t3d', IconSize::SMALL))
                    ->setShowLabelText(true);
                $view->addButtonToButtonBar($exportButton, ButtonBar::BUTTON_POSITION_LEFT, 50);
            }
        }
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
            $view->addButtonToButtonBar($viewModeButton, ButtonBar::BUTTON_POSITION_RIGHT, 0);
        }

        // Shortcut
        $arguments = [
            'id' => $this->pageContext->pageId,
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
        $view->getDocHeaderComponent()->setShortcutContext(
            routeIdentifier: 'records',
            displayName: $this->getShortcutTitle($arguments),
            arguments: $arguments
        );

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
            || !($this->pageContext->pageRecord[$schema->getCapability(TcaSchemaCapability::EditLock)->getFieldName()] ?? false);
    }

    /**
     * Returns the shortcut title for the current page.
     */
    protected function getShortcutTitle(array $arguments): string
    {
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
        return trim(sprintf(
            $languageService->sL('LLL:EXT:backend/Resources/Private/Language/locallang.xlf:shortcut.title'),
            $languageService->translate('title', 'backend.modules.list'),
            $tableTitle,
            $this->pageContext->getPageTitle(),
            $this->pageContext->pageId
        ));
    }

    protected function showPageTranslations(): bool
    {
        if (!$this->getBackendUserAuthentication()->check('tables_select', 'pages')) {
            return false;
        }
        if (isset($this->modTSconfig['table']['pages']['hideTable'])) {
            return !$this->modTSconfig['table']['pages']['hideTable'];
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
        $pageTranslationsDatabaseRecordList->id = $this->pageContext->pageId;
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
            'id' => $this->pageContext->pageId,
            'table' => $this->table,
            'searchTerm' => $this->searchTerm,
        ], $params);

        $params = array_filter($params, static function (mixed $value): bool {
            return $value !== null && trim((string)$value) !== '';
        });

        return (string)$this->uriBuilder->buildUriFromRequest($request, $params);
    }

    /**
     * Creates the language selector dropdown in the module toolbar.
     */
    protected function createLanguageSelector(ModuleTemplate $view, ServerRequestInterface $request): void
    {
        if (count($this->pageContext->languageInformation->availableLanguages) <= 1) {
            return;
        }

        $languageSelector = $this->languageSelectorBuilder->build(
            $this->pageContext,
            LanguageSelectorMode::MULTI_SELECT,
            fn(array $languageIds): string => $this->buildListUrl($request, ['languages' => $languageIds]),
            !empty($this->pageContext->languageInformation->existingTranslations)
        );

        $view->getDocHeaderComponent()->setLanguageSelector($languageSelector);
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

        return !empty($this->pageContext->pageRecord)
            && $this->editLockPermissions()
            && $this->pageContext->pagePermissions->editPagePermissionIsGranted()
            && $backendUser->checkLanguageAccess(0)
            && $backendUser->check('tables_modify', 'pages');
    }

    /**
     * Build list URL preserving relevant parameters (search, table, sort).
     * Does NOT preserve pagination (pointer) to allow resetting to first page.
     *
     * @param array $additionalParams Additional parameters to add/override (e.g., ['languages' => [0, 1]])
     */
    protected function buildListUrl(ServerRequestInterface $request, array $additionalParams = []): string
    {
        $queryParams = $request->getQueryParams();
        $parsedBody = $request->getParsedBody();

        $urlParams = [
            'id' => $this->pageContext->pageId,
        ];

        if ($this->table !== '') {
            $urlParams['table'] = $this->table;
        }
        if ($this->searchTerm !== '') {
            $urlParams['searchTerm'] = $this->searchTerm;
        }
        $searchLevels = (int)($parsedBody['search_levels'] ?? $queryParams['search_levels'] ?? 0);
        if ($searchLevels > 0) {
            $urlParams['search_levels'] = $searchLevels;
        }
        $sortField = (string)($parsedBody['sortField'] ?? $queryParams['sortField'] ?? '');
        if ($sortField !== '') {
            $urlParams['sortField'] = $sortField;
        }
        $sortRev = $parsedBody['sortRev'] ?? $queryParams['sortRev'] ?? null;
        if ($sortRev !== null) {
            $urlParams['sortRev'] = $sortRev;
        }

        // Merge with additional parameters (which can override preserved ones)
        $urlParams = array_merge($urlParams, $additionalParams);

        return (string)$this->uriBuilder->buildUriFromRoute('records', $urlParams);
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
