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
use TYPO3\CMS\Backend\Breadcrumb\BreadcrumbFactory;
use TYPO3\CMS\Backend\Configuration\TranslationConfigurationProvider;
use TYPO3\CMS\Backend\Controller\Event\AfterFormEnginePageInitializedEvent;
use TYPO3\CMS\Backend\Controller\Event\BeforeFormEnginePageInitializedEvent;
use TYPO3\CMS\Backend\Domain\Model\OpenDocument;
use TYPO3\CMS\Backend\Domain\Repository\OpenDocumentRepository;
use TYPO3\CMS\Backend\Dto\FormElementData;
use TYPO3\CMS\Backend\Form\Exception\AccessDeniedException;
use TYPO3\CMS\Backend\Form\Exception\DatabaseRecordException;
use TYPO3\CMS\Backend\Form\Exception\DatabaseRecordWorkspaceDeletePlaceholderException;
use TYPO3\CMS\Backend\Form\Exception\NoFieldsToRenderException;
use TYPO3\CMS\Backend\Form\FormAction;
use TYPO3\CMS\Backend\Form\FormDataCompiler;
use TYPO3\CMS\Backend\Form\FormDataGroup\TcaDatabaseRecord;
use TYPO3\CMS\Backend\Form\FormResultCompiler;
use TYPO3\CMS\Backend\Form\NodeFactory;
use TYPO3\CMS\Backend\Module\ModuleInterface;
use TYPO3\CMS\Backend\Module\ModuleProvider;
use TYPO3\CMS\Backend\Routing\Exception\ResourceNotFoundException;
use TYPO3\CMS\Backend\Routing\PreviewUriBuilder;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\Components\ComponentFactory;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\WorkspaceRestriction;
use TYPO3\CMS\Core\Database\ReferenceIndex;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\DataHandling\TableColumnType;
use TYPO3\CMS\Core\Domain\RecordFactory;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageQueue;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Resource\Exception\FileDoesNotExistException;
use TYPO3\CMS\Core\Resource\Exception\InsufficientUserPermissionsException;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Routing\BackendEntryPointResolver;
use TYPO3\CMS\Core\Schema\Capability\TcaSchemaCapability;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Core\Versioning\VersionState;

/**
 * Main backend controller almost always used if some database record is edited in the backend.
 *
 * Main job of this controller is to evaluate and sanitize $request parameters,
 * call the DataHandler if records should be created or updated and
 * execute FormEngine for record rendering.
 */
#[AsController]
class EditDocumentController
{
    /**
     * An array looking approx like [tablename][list-of-ids]=command, eg. "&edit[pages][123]=edit".
     *
     * @var array<string,mixed>
     */
    protected array $editconf = [];

    /**
     * Array of tables with a lists of field names to edit for those tables. If specified, only those fields
     * will be rendered. Otherwise, all (available) fields in the record are shown according to the TCA type.
     */
    protected array $columnsOnly = [];

    /**
     * Default values for fields
     *
     * @var array|null [table][field]
     */
    protected $defVals;

    /**
     * Array of values to force being set as hidden fields in FormEngine
     *
     * @var array|null [table][field]
     */
    protected $overrideVals;

    /**
     * If set, this value will be set in $this->retUrl as "returnUrl", if not,
     * $this->retUrl will link to dummy action
     *
     * @var string|null
     */
    protected $returnUrl;

    /**
     * Prepared return URL. Contains the URL that we should return to from FormEngine if
     * close button is clicked. Usually passed along as 'returnUrl', but falls back to
     * "dummy" action.
     *
     * @var string
     */
    protected $retUrl;

    /**
     * Boolean: If set, then the GET var "&id=" will be added to the
     * retUrl string so that the NEW id of something is returned to the script calling the form.
     */
    protected bool $returnNewPageId = false;

    /**
     * The preview page id.
     * ID for displaying the page in the frontend, "save and view"
     * Is set to the pid value of the last shown record from "viewId" - thus indicating which page to
     * show when clicking the SAVE/VIEW button and transferred via GET/POST parameter "popViewId"
     */
    protected int $popViewId = 0;

    /**
     * If true, $this->editconf array is added a redirect response, used by Wizard/AddController
     */
    protected bool $returnEditConf = false;

    /**
     * @var array
     */
    protected $pageinfo;

    /**
     * Array of the elements to create edit forms for.
     *
     * @var FormElementData[]
     */
    protected array $elementsData = [];

    /**
     * Pointer to the first element in $elementsData
     */
    protected ?FormElementData $firstEl = null;

    /**
     * Counter, used to count the number of errors (when users do not have edit permissions)
     */
    protected int $numberOfErrors = 0;

    protected FormResultCompiler $formResultCompiler;

    protected ?ModuleInterface $module = null;

    public function __construct(
        private readonly ComponentFactory $componentFactory,
        protected readonly EventDispatcherInterface $eventDispatcher,
        protected readonly IconFactory $iconFactory,
        protected readonly RecordFactory $recordFactory,
        protected readonly BreadcrumbFactory $breadcrumbFactory,
        protected readonly PageRenderer $pageRenderer,
        protected readonly UriBuilder $uriBuilder,
        protected readonly ModuleTemplateFactory $moduleTemplateFactory,
        protected readonly BackendEntryPointResolver $backendEntryPointResolver,
        protected readonly ModuleProvider $moduleProvider,
        private readonly FormDataCompiler $formDataCompiler,
        private readonly NodeFactory $nodeFactory,
        protected TcaSchemaFactory $tcaSchemaFactory,
        protected readonly OpenDocumentRepository $openDocumentRepository,
    ) {}

    /**
     * Main dispatcher entry method registered as "record_edit" end point.
     */
    public function mainAction(ServerRequestInterface $request): ResponseInterface
    {
        $view = $this->moduleTemplateFactory->create($request);
        $view->setUiBlock(true);
        $view->setTitle($this->getShortcutTitle($request));
        $body = '';

        // Unlock all locked records
        BackendUtility::lockRecords();
        $queryParams = $request->getQueryParams();
        $parsedBody = $request->getParsedBody();
        $this->module = $this->moduleProvider->getModule((string)($queryParams['module'] ?? ''), $this->getBackendUser());

        $this->editconf = $this->sanitizeEditConf($parsedBody['edit'] ?? $queryParams['edit'] ?? []);
        $this->defVals = $parsedBody['defVals'] ?? $queryParams['defVals'] ?? null;
        $this->overrideVals = $parsedBody['overrideVals'] ?? $queryParams['overrideVals'] ?? null;
        $this->returnUrl = GeneralUtility::sanitizeLocalUrl($parsedBody['returnUrl'] ?? $queryParams['returnUrl'] ?? '');
        $this->returnEditConf = (bool)($parsedBody['returnEditConf'] ?? $queryParams['returnEditConf'] ?? false);
        $this->columnsOnly = $this->prepareColumnsOnlyConfigurationFromRequest($request);
        $this->popViewId = (int)($parsedBody['popViewId'] ?? $queryParams['popViewId'] ?? 0);

        // Set overrideVals as default values if defVals does not exist.
        // @todo: Why?
        if (!is_array($this->defVals) && is_array($this->overrideVals)) {
            $this->defVals = $this->overrideVals;
        }

        // Set final return URL
        $this->retUrl = $this->returnUrl ?: $this->resolveDefaultReturnUrl();

        // Close document if a request for closing the document has been sent
        $requestAction = FormAction::createFromRequest($request);
        if ($requestAction->shouldHandleDocumentClosing()) {
            $this->markOpenDocumentsAsRecentInSesssion();
            if ($response = $this->closeAndPossiblyRedirectAction($requestAction)) {
                return $response;
            }
        }

        $event = new BeforeFormEnginePageInitializedEvent($this, $request);
        $this->eventDispatcher->dispatch($event);

        // Process incoming data via DataHandler?
        if ($requestAction->shouldProcessData()) {
            $this->processData($view, $request);
            // Redirect if element should be closed after save
            if ($requestAction->shouldCloseAfterSave()) {
                return $this->closeAndPossiblyRedirectAction($requestAction);
            }
        }

        // Prepare current request url parameters (which might have been changed already, especially "editconf")
        // Contains $request query parameters. This array is the foundation for creating
        // the $currentEditingUrl var which becomes the url to which forms are submitted.
        $queryParamsForGeneratingCurrentUrl = $queryParams;
        $queryParamsForGeneratingCurrentUrl['edit'] = $this->editconf;
        $queryParamsForGeneratingCurrentUrl['returnUrl'] = $this->retUrl;
        if ($requestAction->shouldProcessData() && $requestAction->doSave()) {
            // Unset default values since we don't need them anymore.
            unset($queryParamsForGeneratingCurrentUrl['defVals']);
        }

        // Preview code is implicit only generated for GET requests, having the query
        // parameters "popViewId" (the preview page id) and "showPreview" set.
        if ($this->popViewId && ($queryParams['showPreview'] ?? false)) {
            // Generate the preview code (markup), which is added to the module body later
            $body = $this->getPreviewUriBuilderForRecordPreview($this->popViewId)->buildImmediateActionElement([PreviewUriBuilder::OPTION_SWITCH_FOCUS => null]);
            // After generating the preview code, those params should no longer be applied to the form
            // action, as this would otherwise always refresh the preview window on saving the record.
            unset($queryParamsForGeneratingCurrentUrl['showPreview'], $queryParamsForGeneratingCurrentUrl['popViewId']);
        }

        $event = new AfterFormEnginePageInitializedEvent($this, $request);
        $this->eventDispatcher->dispatch($event);

        if ($requestAction->isPostRequest) {
            // In case save&view is requested, we have to add this information to the redirect
            // URL, since the ImmediateAction will be added to the module body afterward.
            if ($requestAction->savedokview()) {
                $queryParamsForGeneratingCurrentUrl['showPreview'] = true;
                $queryParamsForGeneratingCurrentUrl['popViewId'] = $this->popViewId;
            }
            $url = $this->uriBuilder->buildUriFromRoute('record_edit', $queryParamsForGeneratingCurrentUrl);
            return new RedirectResponse($url, 302);
        }

        // Begin to show the edit form
        $this->setModuleContext($view);
        $this->pageRenderer->addInlineLanguageLabelFile('EXT:backend/Resources/Private/Language/locallang_alt_doc.xlf');
        $this->pageRenderer->addInlineLanguageLabelFile('EXT:backend/Resources/Private/Language/Wizards/localization.xlf');
        $this->pageRenderer->addInlineSetting('ShowItem', 'moduleUrl', (string)$this->uriBuilder->buildUriFromRoute('show_item'));
        $this->formResultCompiler = GeneralUtility::makeInstance(FormResultCompiler::class);

        // Generate the URL to the current request with modified GET parameters
        // This is used in various places to "return to" in the form and for buttons etc.
        $currentEditingUrl = $this->uriBuilder->buildUriFromRoute('record_edit', $queryParamsForGeneratingCurrentUrl);

        // Creating the editing form, wrap it with buttons, document selector etc.
        $editForm = $this->makeEditForm($request, $view, $currentEditingUrl);
        if ($editForm) {
            $this->firstEl = $this->elementsData !== [] ? reset($this->elementsData) : null;
            $lastEl = end($this->elementsData);
            // Contains an array with key/value pairs of GET parameters needed to reach the
            // current document displayed - used in the 'open documents' toolbar.
            $storeArray = $this->compileStoreData($request, $queryParamsForGeneratingCurrentUrl);
            $this->storeCurrentDocumentInOpenDocuments($storeArray);
            $this->formResultCompiler->addCssFiles();
            // Put together the various elements (buttons, selectors, form) into a table
            $body .= '
            <form
                action="' . htmlspecialchars((string)$currentEditingUrl) . '"
                method="post"
                enctype="multipart/form-data"
                name="editform"
                id="EditDocumentController"
            >
            ' . $editForm . '
            <input type="hidden" name="returnUrl" value="' . htmlspecialchars($this->retUrl) . '" />
            <input type="hidden" name="popViewId" value="' . htmlspecialchars((string)$lastEl->viewId) . '" />
            <input type="hidden" name="closeDoc" value="0" />
            <input type="hidden" name="doSave" value="0" />
            <input type="hidden" name="returnNewPageId" value="' . ($this->returnNewPageId ? 1 : 0) . '" />';
            $body .= $this->formResultCompiler->printNeededJSFunctions();
            $body .= '</form>';
        }

        if ($this->firstEl === null) {
            // In case firstEl is null, no edit form could be created. Therefore, add an
            // info box and remove the spinner, since it will never be resolved by FormEngine.
            $view->setUiBlock(false);
            $body .= $this->getInfobox(
                $this->getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang_alt_doc.xlf:noEditForm.message'),
                $this->getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang_alt_doc.xlf:noEditForm'),
            );
        }

        // Access check...
        // The page will show only if there is a valid page and if this page may be viewed by the user
        $perms_clause = $this->getBackendUser()->getPagePermsClause(Permission::PAGE_SHOW);
        $this->pageinfo = BackendUtility::readPageAccess($this->firstEl->viewId ?? 0, $perms_clause) ?: [];

        // Setting up the buttons, markers for doc header and navigation component state
        $this->createBreadcrumb($view);
        $this->getButtons($view, $request, $this->firstEl, $currentEditingUrl);

        // Create language switch options if the record is already persisted, and it is a single record to edit
        if ($this->isSingleRecordView() && $this->firstEl->isSavedRecord()) {
            $this->languageSwitch($view, $this->firstEl);
        }

        $view->assign('bodyHtml', $body);

        return $view->renderResponse('Form/EditDocument');
    }

    protected function sanitizeEditConf(array $editConf): array
    {
        $newConfiguration = [];
        $beUser = $this->getBackendUser();
        // Traverse the GPvar edit array tables
        foreach ($editConf as $table => $conf) {
            if (!is_array($conf) || !$this->tcaSchemaFactory->has($table)) {
                // Skip for invalid config or in case no TCA exists
                continue;
            }
            if (!$beUser->check('tables_modify', $table)) {
                // Skip in case the user has insufficient permissions and increment the error counter
                $this->numberOfErrors++;
                continue;
            }
            // Traverse the keys/comments of each table (keys can be a comma list of uids)
            foreach ($conf as $cKey => $command) {
                if ($command !== 'edit' && $command !== 'new') {
                    // Skip if invalid command
                    continue;
                }
                $ids = GeneralUtility::trimExplode(',', (string)$cKey, true);
                foreach ($ids as $id) {
                    $newConfiguration[$table][$id] = $command;
                }
            }
        }
        // Change $this->editconf if versioning applies to any of the records
        return $this->fixWSversioningInEditConf($newConfiguration);
    }

    /**
     * Store all currently edited records as open documents.
     *
     * Creates one document entry per record being edited.
     */
    protected function storeCurrentDocumentInOpenDocuments(array $storeArray): void
    {
        if ($this->elementsData === []) {
            return;
        }
        foreach ($this->elementsData as $element) {
            $recordUid = (string)$element->uid;

            // Create OpenDocument object for this record
            $document = new OpenDocument(
                table: $element->table,
                // Ensure to only have one "new" entry in the list
                uid: str_starts_with($recordUid, 'NEW') ? 'NEW' : $recordUid,
                title: $element->title,
                parameters: $storeArray,
                pid: $element->pid,
                returnUrl: $this->returnUrl,
            );

            // Store to session (updates if already exists)
            $this->openDocumentRepository->addOrUpdateOpenDocument($document, $this->getBackendUser());
        }

        // Update signal for UI
        $openDocuments = $this->openDocumentRepository->findOpenDocumentsForUser($this->getBackendUser());
        BackendUtility::setUpdateSignal('OpendocsController::updateNumber', count($openDocuments));
    }

    protected function setModuleContext(ModuleTemplate $view): void
    {
        $view->assign('moduleContext', '');
        $view->assign('moduleContextId', '');
        if ($this->module === null) {
            return;
        }

        $view->setModuleName($this->module->getIdentifier());

        if ($this->module->isStandalone()) {
            $parent = $this->module;
        } else {
            $parent = $this->module->getParentModule();
            while ($parent?->getParentModule() !== null) {
                $parent = $parent->getParentModule();
            }
        }
        if ($parent === null) {
            return;
        }
        $moduleContext = $parent->getIdentifier();

        if ($moduleContext === 'file') {
            // Workaround for filelist using 'media' as ModuleStorage module context… :\
            $moduleContext = 'media';
        }

        $view->assign('moduleContext', $moduleContext);
    }

    protected function prepareColumnsOnlyConfigurationFromRequest(ServerRequestInterface $request): array
    {
        $columnsOnly = $request->getParsedBody()['columnsOnly'] ?? $request->getQueryParams()['columnsOnly'] ?? null;
        $usedTables = array_keys($request->getQueryParams()['edit'] ?? []);
        $finalColumnsOnly = [];
        if (is_array($columnsOnly) && $columnsOnly !== []) {
            $finalColumnsOnly = array_map(function ($fields) {
                return is_array($fields) ? $fields : GeneralUtility::trimExplode(',', $fields, true);
            }, $columnsOnly);
            $finalColumnsOnly = $this->addSlugFieldsToColumnsOnly($finalColumnsOnly, $usedTables);
        }
        return $finalColumnsOnly;
    }

    /**
     * Always add required fields of slug field
     */
    protected function addSlugFieldsToColumnsOnly(array $finalColumnsOnly, array $tables): array
    {
        foreach ($tables as $table) {
            if (!empty($finalColumnsOnly[$table]) && $this->tcaSchemaFactory->has($table)) {
                $schema = $this->tcaSchemaFactory->get($table);
                foreach ($finalColumnsOnly[$table] as $field) {
                    if (!$schema->hasField($field)) {
                        continue;
                    }
                    $field = $schema->getField($field);
                    $postModifiers = $field->getConfiguration()['generatorOptions']['postModifiers'] ?? [];
                    if ($field->isType(TableColumnType::SLUG)
                        && (!is_array($postModifiers) || $postModifiers === [])
                    ) {
                        $fieldGroups = $field->getConfiguration()['generatorOptions']['fields'] ?? [];
                        if (is_string($fieldGroups)) {
                            $fieldGroups = [$fieldGroups];
                        }
                        foreach ($fieldGroups as $fields) {
                            $finalColumnsOnly['__hiddenGeneratorFields'][$table] = array_merge(
                                $finalColumnsOnly['__hiddenGeneratorFields'][$table] ?? [],
                                (is_array($fields) ? $fields : GeneralUtility::trimExplode(',', $fields, true))
                            );
                        }
                    }
                }
                if (!empty($finalColumnsOnly['__hiddenGeneratorFields'][$table])) {
                    $finalColumnsOnly['__hiddenGeneratorFields'][$table] = array_diff(
                        array_unique($finalColumnsOnly['__hiddenGeneratorFields'][$table]),
                        $finalColumnsOnly[$table]
                    );
                }
            }
        }
        return $finalColumnsOnly;
    }

    /**
     * Do processing of data, submitting it to DataHandler.
     *
     * Also handles the "duplication" of a record.
     */
    protected function processData(ModuleTemplate $view, ServerRequestInterface $request): void
    {
        $requestAction = FormAction::createFromRequest($request);
        $parsedBody = $request->getParsedBody();

        $beUser = $this->getBackendUser();

        $dataMap = $parsedBody['data'] ?? [];
        $dataHandlerIncomingCommandMap = $parsedBody['cmd'] ?? [];
        $this->returnNewPageId = (bool)($parsedBody['returnNewPageId'] ?? false);

        /** @var DataHandler $dataHandler */
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        // Only options related to $dataMap submission are included here
        $dataHandler->setControl($parsedBody['control'] ?? []);

        // Set default values fetched previously from GET / POST vars
        if (is_array($dataMap)) {
            foreach ($dataMap as $tableName => $records) {
                if (is_array($this->defVals[$tableName] ?? null)) {
                    foreach ($records as $uid => $_) {
                        if (str_contains((string)$uid, 'NEW')) {
                            $dataMap[$tableName][$uid] = array_merge($this->defVals[$tableName], $dataMap[$tableName][$uid]);
                        }
                    }
                }
            }
        }

        // Load DataHandler with data
        $dataHandler->start($dataMap, $dataHandlerIncomingCommandMap);
        if (is_array($parsedBody['mirror'] ?? null)) {
            $dataHandler->setMirror($parsedBody['mirror']);
        }

        // Perform the saving operation with DataHandler:
        if ($requestAction->doSave()) {
            $dataHandler->process_datamap();
            $dataHandler->process_cmdmap();

            // Update the module menu for the current backend user, as they updated their UI language
            $currentUserId = $beUser->getUserId();
            if ($currentUserId
                && (string)($dataMap['be_users'][$currentUserId]['lang'] ?? '') !== ''
                && $dataMap['be_users'][$currentUserId]['lang'] !== $beUser->user['lang']
            ) {
                $newLanguageKey = $dataMap['be_users'][$currentUserId]['lang'];
                // Update the current backend user language as well
                $beUser->user['lang'] = $newLanguageKey;
                // Re-create LANG to have the current request updated the translated page as well
                $this->getLanguageService()->init($newLanguageKey);
                BackendUtility::setUpdateSignal('updateModuleMenu');
                BackendUtility::setUpdateSignal('updateTopbar');
            }

            // If pages are being edited, we set an instruction about updating the page tree after this operation.
            if ($dataHandler->pagetreeNeedsRefresh
                && (isset($dataMap['pages']) || $beUser->workspace !== 0 && !empty($dataMap))
            ) {
                BackendUtility::setUpdateSignal('updatePageTree');
            }

            // If there was saved any new items, load them:
            if (!empty($dataHandler->substNEWwithIDs)) {
                // Save the expanded/collapsed states for new inline records, if any
                $this->updateInlineView($request->getParsedBody()['uc'] ?? $request->getQueryParams()['uc'] ?? null, $dataHandler);
                $newEditConf = [];
                // Traverse all new records and forge the content of $this->editconf so we can continue to edit these records!
                // $this->editconf is now updated to replace NEW-Ids with the actual persisted IDs.
                foreach ($this->editconf as $tableName => $tableCmds) {
                    $keys = array_keys($dataHandler->substNEWwithIDs_table, $tableName);
                    if ($keys !== []) {
                        foreach ($keys as $key) {
                            $editId = $dataHandler->substNEWwithIDs[$key];
                            // Check if the $editId isn't a child record of an IRRE action
                            if (!(is_array($dataHandler->newRelatedIDs[$tableName] ?? null)
                                && in_array($editId, $dataHandler->newRelatedIDs[$tableName]))
                            ) {
                                // Translate new id to the workspace version
                                if ($versionRec = BackendUtility::getWorkspaceVersionOfRecord(
                                    $beUser->workspace,
                                    $tableName,
                                    $editId,
                                    'uid'
                                )) {
                                    $editId = $versionRec['uid'];
                                }
                                $newEditConf[$tableName][$editId] = 'edit';
                            }
                            if ($tableName === 'pages'
                                && !$this->shouldRedirectToEmptyPage()
                                && $this->retUrl !== $this->getCloseUrl($request)
                                && $this->returnNewPageId
                            ) {
                                $this->retUrl .= '&id=' . $dataHandler->substNEWwithIDs[$key];
                            }
                        }
                    } else {
                        $newEditConf[$tableName] = $tableCmds;
                    }
                }
                if ($newEditConf !== []) {
                    $this->editconf = $newEditConf;
                }
            }
            // See if any records was auto-created as new versions?
            if (!empty($dataHandler->autoVersionIdMap)) {
                $this->editconf = $this->fixWSversioningInEditConf($this->editconf, $dataHandler->autoVersionIdMap);
            }
        }

        // If a document is saved and a new one is created right after.
        if ($requestAction->savedoknew()) {
            $this->markOpenDocumentsAsRecentInSesssion();
            // Find the current table
            reset($this->editconf);
            $nTable = (string)key($this->editconf);
            // Determine insertion mode: 'top' is self-explaining,
            // otherwise new elements are inserted after one using a negative uid
            $insertRecordOnTop = ($this->getTsConfigOption($nTable, 'saveDocNew') === 'top');
            $ids = array_keys($this->editconf[$nTable]);
            // Depending on $insertRecordOnTop, retrieve either the first or last id to get the records' pid+uid
            if ($insertRecordOnTop) {
                $nUid = (int)reset($ids);
            } else {
                $nUid = (int)end($ids);
            }
            $nRec = BackendUtility::getRecord($nTable, $nUid);
            if ($insertRecordOnTop) {
                $relatedPageId = $nRec['pid'];
            } else {
                if ((int)($nRec['t3ver_oid'] ?? 0) === 0) {
                    $relatedPageId = -$nRec['uid'];
                } else {
                    // Use uid of live version of workspace version
                    $relatedPageId = -$nRec['t3ver_oid'];
                }
            }
            // Setting a blank editconf array for a new record:
            $this->editconf = [];
            $this->editconf[$nTable][$relatedPageId] = 'new';
        }

        // Explicitly require a save operation
        if ($requestAction->doSave()) {
            $erroneousRecords = $dataHandler->printLogErrorMessages();
            $messages = [];
            $table = (string)key($this->editconf);
            $uidList = array_keys($this->editconf[$table]);

            foreach ($uidList as $uid) {
                $uid = (int)abs($uid);
                if (!in_array($table . '.' . $uid, $erroneousRecords, true)) {
                    $realUidInPayload = ($tceSubstId = array_search($uid, $dataHandler->substNEWwithIDs, true)) !== false ? $tceSubstId : $uid;
                    $row = $dataMap[$table][$uid] ?? $dataMap[$table][$realUidInPayload] ?? null;
                    if ($row === null) {
                        continue;
                    }
                    // Ensure, uid is always available to make labels with foreign table lookups possible
                    $row['uid'] ??= $realUidInPayload;
                    // If the label column of the record is not available, fetch it from database.
                    // This is the case when EditDocumentController is booted in single field mode (e.g.
                    // Template module > 'info/modify' > edit 'setup' field) or in case the field is
                    // not in "showitem" or is set to readonly (e.g. "file" in sys_file_metadata).
                    $labelCapability = $this->tcaSchemaFactory->get($table)->getCapability(TcaSchemaCapability::Label);
                    $labelFields = $labelCapability->getAllLabelFieldNames();
                    foreach ($labelFields as $labelField) {
                        if (!isset($row[$labelField])) {
                            $tmpRecord = BackendUtility::getRecord($table, $uid, $labelFields);
                            if ($tmpRecord !== null) {
                                $row = array_merge($row, $tmpRecord);
                            }
                            break;
                        }
                    }
                    $recordTitle = GeneralUtility::fixed_lgd_cs(BackendUtility::getRecordTitle($table, $row), (int)$this->getBackendUser()->uc['titleLen']);
                    $messages[] = sprintf($this->getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang_alt_doc.xlf:notification.record_saved.message'), $recordTitle);
                }
            }

            // Add messages to the flash message container only if the request is a save action (excludes "duplicate")
            if ($messages !== []) {
                $label = $this->getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang_alt_doc.xlf:notification.record_saved.title.plural');
                if (count($messages) === 1) {
                    $label = $this->getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang_alt_doc.xlf:notification.record_saved.title.singular');
                }
                if (count($messages) > 10) {
                    $messages = [sprintf($this->getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang_alt_doc.xlf:notification.mass_saving.message'), count($messages))];
                }
                $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
                $defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier(FlashMessageQueue::NOTIFICATION_QUEUE);
                $flashMessage = GeneralUtility::makeInstance(
                    FlashMessage::class,
                    implode(LF, $messages),
                    $label,
                    ContextualFeedbackSeverity::OK,
                    true
                );
                $defaultFlashMessageQueue->enqueue($flashMessage);
            }
        }

        // If a document should be duplicated.
        if ($requestAction->duplicatedoc()) {
            $this->markOpenDocumentsAsRecentInSesssion();
            // Find current table
            reset($this->editconf);
            $nTable = (string)key($this->editconf);
            // Find the first id, getting the records pid+uid
            $nUid = array_keys($this->editconf[$nTable]);
            $nUid = reset($nUid);
            if (!MathUtility::canBeInterpretedAsInteger($nUid)) {
                $nUid = $dataHandler->substNEWwithIDs[$nUid];
            }

            $nRec = BackendUtility::getRecord($nTable, $nUid);

            // Setting a blank editconf array for a new record
            $this->editconf = [];

            if ((int)($nRec['t3ver_oid'] ?? 0) > 0) {
                $relatedPageId = -$nRec['t3ver_oid'];
            } else {
                $relatedPageId = -$nRec['uid'];
            }

            /** @var DataHandler $duplicateTce */
            $duplicateTce = GeneralUtility::makeInstance(DataHandler::class);
            $duplicateCmd = [
                $nTable => [
                    $nUid => [
                        'copy' => $relatedPageId,
                    ],
                ],
            ];

            $duplicateTce->start([], $duplicateCmd);
            $duplicateTce->process_cmdmap();
            $duplicateUid = $duplicateTce->copyMappingArray[$nTable][$nUid] ?? null;
            if ($duplicateUid !== null) {
                if ($nTable === 'pages') {
                    BackendUtility::setUpdateSignal('updatePageTree');
                }

                $this->editconf[$nTable][$duplicateUid] = 'edit';

                // Inform the user of the duplication
                $view->addFlashMessage($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.recordDuplicated'));
            } else {
                $this->numberOfErrors++;
                // Inform the user about the failed duplication
                $view->addFlashMessage(
                    $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.recordDuplicationFailed'),
                    '',
                    ContextualFeedbackSeverity::ERROR
                );
            }
        }
    }

    protected function getPreviewUriBuilderForRecordPreview($pageId): PreviewUriBuilder
    {
        $array_keys = array_keys($this->editconf);
        $table = reset($array_keys);
        $recordId = 0;
        if ($table) {
            $uids = array_keys($this->editconf[$table]);
            $recordId = (int)((reset($uids) ?: null) ?? '');
        }
        return PreviewUriBuilder::createForRecordPreview($table, $recordId, $pageId);
    }

    protected function createBreadcrumb(ModuleTemplate $view): void
    {
        // Handle file metadata records
        $file = null;
        if ($this->firstEl->table === 'sys_file_metadata' && $this->firstEl->uid > 0) {
            // Happens if it is a select/group
            $fileUid = $this->firstEl->record['file'] ?? 0;
            if (is_array($fileUid)) {
                $fileUid = reset($fileUid);
            }
            try {
                $file = GeneralUtility::makeInstance(ResourceFactory::class)->getFileObject((int)$fileUid);
            } catch (FileDoesNotExistException|InsufficientUserPermissionsException $e) {
                // do nothing when file is not accessible
            }
        }

        if ($file instanceof FileInterface) {
            $view->assign('moduleContextId', $file->getParentFolder()->getCombinedIdentifier());
            $view->getDocHeaderComponent()->setResourceBreadcrumb($file);
        } elseif ($this->pageinfo !== []) {
            $l10nParent = (int)($this->pageinfo['l10n_parent'] ?? 0);
            $pageUid =  $this->pageinfo['uid'] ?? '';
            $view->assign('moduleContextId', $l10nParent !== 0 ? $l10nParent : $pageUid);

            // Determine breadcrumb based on action (edit existing vs. create new)
            if ($this->firstEl->isSavedRecord()) {
                if ($this->isSingleRecordView()) {
                    // Edit single existing record
                    $breadcrumbContext = $this->breadcrumbFactory->forEditAction(
                        $this->firstEl->table,
                        (int)$this->firstEl->uid
                    );
                } else {
                    // Edit multiple records
                    $breadcrumbContext = $this->breadcrumbFactory->forEditMultipleAction(
                        $this->firstEl->table,
                        (int)($this->pageinfo['uid'] ?? 0)
                    );
                }
            } else {
                // Create new record
                $breadcrumbContext = $this->breadcrumbFactory->forNewAction(
                    $this->firstEl->table,
                    (int)($this->pageinfo['uid'] ?? 0),
                    $this->defVals[$this->firstEl->table] ?? []
                );
            }
            $view->getDocHeaderComponent()->setBreadcrumbContext($breadcrumbContext);
        }
    }

    /**
     * Creates the editing form with FormEngine, based on the input from GPvars.
     *
     * @return string HTML form elements wrapped in tables
     */
    protected function makeEditForm(ServerRequestInterface $request, ModuleTemplate $view, UriInterface $currentRequestUrl): string
    {
        // Initialize variables
        $editForm = '';
        $beUser = $this->getBackendUser();
        // Traverse the GPvar edit array tables
        foreach ($this->editconf as $table => $conf) {
            // Traverse the keys/comments of each table (keys can be a comma list of uids)
            foreach ($conf as $theUid => $command) {
                try {
                    $formDataCompilerInput = [
                        'request' => $request,
                        'tableName' => $table,
                        'vanillaUid' => (int)$theUid,
                        'command' => $command,
                        'returnUrl' => (string)$currentRequestUrl,
                    ];
                    if (is_array($this->overrideVals) && is_array($this->overrideVals[$table])) {
                        $formDataCompilerInput['overrideValues'] = $this->overrideVals[$table];
                    }
                    if (is_array($this->defVals) && $this->defVals !== []) {
                        $formDataCompilerInput['defaultValues'] = $this->defVals;
                    }

                    $formData = $this->formDataCompiler->compile($formDataCompilerInput, GeneralUtility::makeInstance(TcaDatabaseRecord::class));

                    $viewId = 0;
                    if ($table === 'pages') {
                        // Only set viewId in case it's not a new page - as this can not be viewed before being saved
                        if ($command !== 'new' && MathUtility::canBeInterpretedAsInteger($formData['databaseRow']['uid'])) {
                            $viewId = (int)$formData['databaseRow']['uid'];
                        }
                    } elseif (!empty($formData['parentPageRow']['uid'])) {
                        $viewId = $formData['parentPageRow']['uid'];
                    }

                    // Display "is-locked" message
                    if ($command === 'edit') {
                        $lockInfo = BackendUtility::isRecordLocked($table, $formData['databaseRow']['uid']);
                        if ($lockInfo) {
                            $view->addFlashMessage($lockInfo['msg'], '', ContextualFeedbackSeverity::WARNING);
                        }
                    }

                    $el = new FormElementData(
                        title: $formData['recordTitle'],
                        table: $table,
                        uid: $formData['databaseRow']['uid'],
                        pid: ($formData['databaseRow']['pid'] ?? $viewId),
                        record: $formData['databaseRow'],
                        viewId: (int)$viewId,
                        command: $command,
                        userPermissionOnPage: $formData['userPermissionOnPage'],
                    );

                    $this->elementsData[] = $el;

                    if ($command !== 'new') {
                        BackendUtility::lockRecords($table, $el->uid, $table === 'tt_content' ? $el->pid : 0);
                    }

                    // Set list if only specific fields should be rendered. This will trigger
                    // ListOfFieldsContainer instead of FullRecordContainer in OuterWrapContainer
                    if (!empty($this->columnsOnly[$table])) {
                        $formData['fieldListToRender'] = implode(',', $this->columnsOnly[$table]);
                        if (!empty($this->columnsOnly['__hiddenGeneratorFields'][$table])) {
                            $formData['hiddenFieldListToRender'] = implode(',', $this->columnsOnly['__hiddenGeneratorFields'][$table]);
                        }
                    }

                    $formData['renderType'] = 'outerWrapContainer';
                    $formResult = $this->nodeFactory->create($formData)->render();

                    $html = $formResult['html'];

                    $formResult['html'] = '';
                    $formResult['doSaveFieldName'] = 'doSave';

                    // @todo: Put all the stuff into FormEngine as final "compiler" class
                    // @todo: This is done here for now to not rewrite addCssFiles()
                    // @todo: and printNeededJSFunctions() now
                    $this->formResultCompiler->mergeResult($formResult);

                    // Seems the pid is set as hidden field (again) at end?!
                    if ($command === 'new') {
                        // @todo: looks ugly
                        $html .= LF
                            . '<input type="hidden"'
                            . ' name="data[' . htmlspecialchars($table) . '][' . htmlspecialchars($el->uid) . '][pid]"'
                            . ' value="' . $el->pid . '" />';
                    }

                    $editForm .= $html;
                } catch (NoFieldsToRenderException $e) {
                    $this->numberOfErrors++;
                    $editForm .= $this->getInfobox(
                        $this->getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang_alt_doc.xlf:noFieldsEditForm.message'),
                        $this->getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang_alt_doc.xlf:noFieldsEditForm'),
                    );
                } catch (AccessDeniedException $e) {
                    $this->numberOfErrors++;
                    // Try to fetch error message from "recordInternals" be user object
                    // @todo: This construct should be logged and localized and de-uglified
                    $message = (!empty($beUser->errorMsg)) ? $beUser->errorMsg : $e->getMessage() . ' ' . $e->getCode();
                    $title = $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.noEditPermission');
                    $editForm .= $this->getInfobox($message, $title);
                } catch (DatabaseRecordException | DatabaseRecordWorkspaceDeletePlaceholderException $e) {
                    $editForm .= $this->getInfobox($e->getMessage());
                }
            }
        }
        return $editForm;
    }

    /**
     * Helper function for rendering an Infobox
     */
    protected function getInfobox(string $message, ?string $title = null): string
    {
        return '<div class="callout callout-danger">' .
                '<div class="callout-icon">' .
                    '<span class="icon-emphasized">' .
                        $this->iconFactory->getIcon('actions-close', IconSize::SMALL)->render() .
                    '</span>' .
                '</div>' .
                '<div class="callout-content">' .
                    ($title ? '<div class="callout-title">' . htmlspecialchars($title) . '</div>' : '') .
                    '<div class="callout-body">' . htmlspecialchars($message) . '</div>' .
                '</div>' .
            '</div>';
    }

    /**
     * Create the panel of buttons for submitting the form or otherwise perform operations.
     */
    protected function getButtons(ModuleTemplate $view, ServerRequestInterface $request, ?FormElementData $mainFormElement, UriInterface $currentEditingUrl): void
    {
        if ($mainFormElement !== null) {
            $record = $mainFormElement->record;
            $schema = $this->tcaSchemaFactory->get($mainFormElement->table);
            $this->registerCloseButtonToButtonBar($view, ButtonBar::BUTTON_POSITION_LEFT, 1);

            // Show buttons (duplicate, new, view save) when table is not read-only
            if (!$this->numberOfErrors && !$schema->hasCapability(TcaSchemaCapability::AccessReadOnly)) {
                $view->addButtonToButtonBar($this->componentFactory->createSaveButton('EditDocumentController')->setDisabled(true), ButtonBar::BUTTON_POSITION_LEFT, 2);
                $this->registerViewButtonToButtonBar($view, ButtonBar::BUTTON_POSITION_LEFT, 3);

                if ($mainFormElement->command !== 'new') {
                    $languageCapability = $schema->isLanguageAware() ? $schema->getCapability(TcaSchemaCapability::Language) : null;
                    $languageId = 0;
                    if (
                        $mainFormElement->isSavedRecord()
                        && $schema->isLanguageAware()
                        && isset($record[($languageField = $languageCapability->getLanguageField()->getName())])
                    ) {
                        $languageId = (int)$record[$languageField];
                    } elseif (isset($this->defVals[$mainFormElement->table]['sys_language_uid'])) {
                        $languageId = (int)$this->defVals[$mainFormElement->table]['sys_language_uid'];
                    }
                    $l10nParent = 0;
                    $translationOriginPointerField = $languageCapability ? $languageCapability->getTranslationOriginPointerField()->getName() : null;
                    if ($translationOriginPointerField && isset($record[$translationOriginPointerField])) {
                        $value = $record[$translationOriginPointerField];
                        if (is_array($value)) {
                            $value = reset($value);
                        }
                        // Happens on group
                        if (is_array($value) && isset($value['uid'])) {
                            $value = $value['uid'];
                        }
                        $l10nParent = (int)$value;
                    }

                    if ($mainFormElement->table === 'tt_content') {
                        $canCreateNewOrDuplicate = $this->isInconsistentLanguageHandlingAllowed() || $this->isPageContentFreeTranslationMode($mainFormElement, $languageId);
                    } else {
                        $canCreateNewOrDuplicate = $languageId === 0 || $l10nParent === 0;
                    }
                    if ($canCreateNewOrDuplicate) {
                        $this->registerNewButtonToButtonBar($view, ButtonBar::BUTTON_POSITION_LEFT, 4);
                        $this->registerDuplicationButtonToButtonBar($view, ButtonBar::BUTTON_POSITION_LEFT, 5);
                    }
                }
                if ($mainFormElement->isSavedRecord()) {
                    $this->registerHistoryButtonToButtonBar($view, ButtonBar::BUTTON_POSITION_RIGHT, 1, $currentEditingUrl);
                    $this->registerDeleteButtonToButtonBar($view, ButtonBar::BUTTON_POSITION_LEFT, 6, $request);
                }
                $this->registerColumnsOnlyButtonToButtonBar($view, ButtonBar::BUTTON_POSITION_LEFT, 7, $currentEditingUrl);
            }
        }

        $this->registerInfoButtonToButtonBar($view, ButtonBar::BUTTON_POSITION_RIGHT, 2);
        $this->registerOpenInNewWindowButtonToButtonBar($view, ButtonBar::BUTTON_POSITION_RIGHT, 3, $request);
        $this->registerShortcutButtonToButtonBar($view, $request);
    }

    /**
     * Return true if inconsistent language handling is allowed
     */
    protected function isInconsistentLanguageHandlingAllowed(): bool
    {
        $allowInconsistentLanguageHandling = BackendUtility::getPagesTSconfig(
            $this->pageinfo['uid'] ?? 0
        )['mod']['web_layout']['allowInconsistentLanguageHandling'] ?? ['value' => '0'];

        return $allowInconsistentLanguageHandling['value'] === '1';
    }

    /**
     * Checks if the page is in free translation mode for tt_content
     */
    protected function isPageContentFreeTranslationMode(FormElementData $formElementData, int $languageId): bool
    {
        if ($formElementData->table !== 'tt_content') {
            return false;
        }
        if (!$formElementData->isSavedRecord()) {
            return $this->getFreeTranslationMode(
                (int)($this->pageinfo['uid'] ?? 0),
                (int)($this->defVals[$formElementData->table]['colPos'] ?? 0),
                $languageId
            );
        }
        return $this->getFreeTranslationMode(
            (int)($this->pageinfo['uid'] ?? 0),
            (int)($formElementData->record['colPos'] ?? 0),
            $languageId
        );
    }

    /**
     * True if the page is in free translation mode.
     */
    protected function getFreeTranslationMode(int $page, int $column, int $language): bool
    {
        $freeTranslationMode = false;
        if ($this->getConnectedContentElementTranslationsCount($page, $column, $language) === 0
            && $this->getStandAloneContentElementTranslationsCount($page, $column, $language) >= 0
        ) {
            $freeTranslationMode = true;
        }
        return $freeTranslationMode;
    }

    /**
     * Register the close button to the button bar
     */
    protected function registerCloseButtonToButtonBar(ModuleTemplate $view, string $position, int $group): void
    {
        $closeButton = $this->componentFactory->createLinkButton()
            ->setHref('#')
            ->setClasses('t3js-editform-close')
            ->setTitle($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:rm.closeDoc'))
            ->setShowLabelText(true)
            ->setIcon($this->iconFactory->getIcon('actions-close', IconSize::SMALL))
            ->setDisabled(true);
        $view->addButtonToButtonBar($closeButton, $position, $group);
    }

    /**
     * Register the view button to the button bar
     */
    protected function registerViewButtonToButtonBar(ModuleTemplate $view, string $position, int $group): void
    {
        // Pid to show the record
        if (!$this->firstEl->viewId) {
            return;
        }
        if ($this->firstEl->table === '') {
            return;
        }
        // @TODO: TsConfig option should change to viewDoc
        if (!$this->getTsConfigOption($this->firstEl->table, 'saveDocView')) {
            return;
        }

        $previewUriBuilderForCurrentPage = PreviewUriBuilder::create($this->pageinfo)->isPreviewable();
        $pageId = $this->popViewId ?: $this->firstEl->viewId;
        $previewUriBuilder = PreviewUriBuilder::createForRecordPreview($this->firstEl->table, $this->firstEl->record, $pageId);
        if ($previewUriBuilderForCurrentPage || $previewUriBuilder->isPreviewable()) {
            $previewUrl = $previewUriBuilder->buildUri();
            if ($previewUrl) {
                $viewButton = $this->componentFactory->createLinkButton()
                    ->setHref((string)$previewUrl)
                    ->setIcon($this->iconFactory->getIcon('actions-view', IconSize::SMALL))
                    ->setShowLabelText(true)
                    ->setTitle($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:rm.viewDoc'))
                    ->setClasses('t3js-editform-view')
                    ->setDisabled(true);
                if (!$this->firstEl->isSavedRecord() && $this->firstEl->table === 'pages') {
                    $viewButton->setDataAttributes(['is-new' => '']);
                }
                $view->addButtonToButtonBar($viewButton, $position, $group);
            }
        }
    }

    /**
     * Register the new button to the button bar
     */
    protected function registerNewButtonToButtonBar(ModuleTemplate $view, string $position, int $group): void
    {
        if ($this->firstEl->table === '') {
            return;
        }
        if ($this->firstEl->table === 'sys_file_metadata') {
            return;
        }
        if (!$this->getTsConfigOption($this->firstEl->table, 'saveDocNew')) {
            return;
        }
        $newButton = $this->componentFactory->createLinkButton()
            ->setHref('#')
            ->setIcon($this->iconFactory->getIcon('actions-plus', IconSize::SMALL))
            ->setShowLabelText(true)
            ->setTitle($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:rm.newDoc'))
            ->setClasses('t3js-editform-new')
            ->setDisabled(true);
        if (!$this->firstEl->isSavedRecord()) {
            $newButton->setDataAttributes(['is-new' => '']);
        }
        $view->addButtonToButtonBar($newButton, $position, $group);
    }

    /**
     * Register the duplication button to the button bar
     */
    protected function registerDuplicationButtonToButtonBar(ModuleTemplate $view, string $position, int $group): void
    {
        if (!$this->isSingleRecordView()) {
            return;
        }
        if ($this->firstEl->table === '') {
            return;
        }
        if ($this->firstEl->table === 'sys_file_metadata') {
            return;
        }
        if (!$this->getTsConfigOption($this->firstEl->table, 'showDuplicate')) {
            return;
        }
        $duplicateButton = $this->componentFactory->createLinkButton()
            ->setHref('#')
            ->setShowLabelText(true)
            ->setTitle($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:rm.duplicateDoc'))
            ->setIcon($this->iconFactory->getIcon('actions-document-duplicates-select', IconSize::SMALL))
            ->setClasses('t3js-editform-duplicate')
            ->setDisabled(true);
        if (!$this->firstEl->isSavedRecord()) {
            $duplicateButton->setDataAttributes(['is-new' => '']);
        }
        $view->addButtonToButtonBar($duplicateButton, $position, $group);
    }

    /**
     * Register the delete button to the button bar
     */
    protected function registerDeleteButtonToButtonBar(ModuleTemplate $view, string $position, int $group, ServerRequestInterface $request): void
    {
        if (!$this->isSingleRecordView()) {
            return;
        }
        if (!$this->firstEl->isSavedRecord()) {
            return;
        }
        if ($this->getDisableDelete()) {
            return;
        }
        if ($this->isRecordCurrentBackendUser()) {
            return;
        }
        if (!$this->firstEl->hasDeleteAccess()) {
            return;
        }
        $returnUrl = $this->retUrl;
        if ($this->firstEl->table === 'pages') {
            // The below is a hack to replace the return url with an url to the current module on id=0. Otherwise,
            // this might lead to empty views, since the current id is the page, which is about to be deleted.
            $parsedUrl = parse_url($returnUrl);
            // @todo consider using $this->module here
            $routePath = str_replace($this->backendEntryPointResolver->getPathFromRequest($request), '', $parsedUrl['path'] ?? '');
            parse_str($parsedUrl['query'] ?? '', $queryParams);
            if ($routePath
                && isset($queryParams['id'])
                && (string)$this->firstEl->uid === (string)$queryParams['id']
            ) {
                try {
                    // TODO: Use the page's pid instead of 0, this requires a clean API to manipulate the page
                    // tree from the outside to be able to mark the pid as active
                    $returnUrl = (string)$this->uriBuilder->buildUriFromRoutePath($routePath, ['id' => 0]);
                } catch (ResourceNotFoundException $e) {
                    // Resolved path can not be matched to a configured route
                }
            }
        }

        $referenceIndex = GeneralUtility::makeInstance(ReferenceIndex::class);
        $numberOfReferences = $referenceIndex->getNumberOfReferencedRecords(
            $this->firstEl->table,
            (int)$this->firstEl->uid
        );
        $referenceCountMessage = BackendUtility::referenceCount(
            $this->firstEl->table,
            (int)$this->firstEl->uid,
            $this->getLanguageService()->sL(
                'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.referencesToRecord'
            ),
            (string)$numberOfReferences
        );
        $translationCountMessage = BackendUtility::translationCount(
            $this->firstEl->table,
            (string)(int)$this->firstEl->uid,
            $this->getLanguageService()->sL(
                'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.translationsOfRecord'
            )
        );

        $deleteUrl = (string)$this->uriBuilder->buildUriFromRoute('tce_db', [
            'cmd' => [
                $this->firstEl->table => [
                    $this->firstEl->uid => [
                        'delete' => '1',
                    ],
                ],
            ],
            'redirect' => $returnUrl,
        ]);

        $recordInfo = $this->firstEl->title;
        if ($this->getBackendUser()->shallDisplayDebugInformation()) {
            $recordInfo .= ' [' . $this->firstEl->table . ':' . $this->firstEl->uid . ']';
        }

        $deleteButton = $this->componentFactory->createLinkButton()
            ->setClasses('t3js-editform-delete-record')
            ->setDataAttributes([
                'uid' => $this->firstEl->uid,
                'table' => $this->firstEl->table,
                'record-info' => trim($recordInfo),
                'reference-count-message' => $referenceCountMessage,
                'translation-count-message' => $translationCountMessage,
            ])
            ->setHref($deleteUrl)
            ->setIcon($this->iconFactory->getIcon('actions-edit-delete', IconSize::SMALL))
            ->setShowLabelText(true)
            ->setTitle($this->getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang_alt_doc.xlf:deleteItem'))
            ->setDisabled(true);
        $view->addButtonToButtonBar($deleteButton, $position, $group);
    }

    /**
     * Register the info button to the button bar
     */
    protected function registerInfoButtonToButtonBar(ModuleTemplate $view, string $position, int $group): void
    {
        if (!$this->isSingleRecordView()) {
            return;
        }
        if (!$this->firstEl->isSavedRecord()) {
            return;
        }
        $button = $this->componentFactory->createGenericButton();
        $button->setLabel($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_mod_web_list.xlf:showInfo'));
        $button->setAttributes([
            'type' => 'button',
            'data-dispatch-action' => 'TYPO3.InfoWindow.showItem',
            'data-dispatch-args-list' => $this->firstEl->table . ',' . $this->firstEl->uid,
            'disabled' => 'disabled',
        ]);
        $button->setIcon($this->iconFactory->getIcon('actions-document-info', IconSize::SMALL));
        $view->addButtonToButtonBar($button, $position, $group);
    }

    /**
     * Register the history button to the button bar
     */
    protected function registerHistoryButtonToButtonBar(ModuleTemplate $view, string $position, int $group, UriInterface $currentEditingUrl): void
    {
        if (!$this->isSingleRecordView()) {
            return;
        }
        if ($this->firstEl->table === '') {
            return;
        }
        if (!$this->getTsConfigOption($this->firstEl->table, 'showHistory', '1')) {
            return;
        }
        $historyUrl = (string)$this->uriBuilder->buildUriFromRoute('record_history', [
            'element' => $this->firstEl->table . ':' . $this->firstEl->uid,
            'returnUrl' => (string)$currentEditingUrl,
        ]);
        $historyButton = $this->componentFactory->createLinkButton()
            ->setHref($historyUrl)
            ->setTitle($this->getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang_alt_doc.xlf:recordHistory'))
            ->setIcon($this->iconFactory->getIcon('actions-document-history-open', IconSize::SMALL))
            ->setDisabled(true);
        $view->addButtonToButtonBar($historyButton, $position, $group);
    }

    /**
     * Register "Edit whole record" button to the button bar
     */
    protected function registerColumnsOnlyButtonToButtonBar(ModuleTemplate $view, string $position, int $group, UriInterface $currentEditingUrl): void
    {
        if (!$this->isSingleRecordView()) {
            return;
        }
        if ($this->columnsOnly === []) {
            return;
        }
        $query = $currentEditingUrl->getQuery();
        $query .= '&columnsOnly=';
        $url = $currentEditingUrl->withQuery($query);
        $columnsOnlyButton = $this->componentFactory->createLinkButton()
            ->setHref((string)$url)
            ->setTitle($this->getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang_alt_doc.xlf:editWholeRecord'))
            ->setShowLabelText(true)
            ->setIcon($this->iconFactory->getIcon('actions-open', IconSize::SMALL))
            ->setDisabled(true);

        $view->addButtonToButtonBar($columnsOnlyButton, $position, $group);
    }

    /**
     * Register the open in new window button to the button bar
     */
    protected function registerOpenInNewWindowButtonToButtonBar(ModuleTemplate $view, string $position, int $group, ServerRequestInterface $request): void
    {
        $closeUrl = $this->getCloseUrl($request);
        if ($this->returnUrl === $closeUrl) {
            return;
        }
        // Generate a URL to the current edit form
        $arguments = $this->getUrlQueryParamsForCurrentRequest($request);
        $arguments['returnUrl'] = $closeUrl;
        $requestUri = (string)$this->uriBuilder->buildUriFromRoute('record_edit', $arguments);
        $openInNewWindowButton = $this->componentFactory
            ->createLinkButton()
            ->setHref('#')
            ->setTitle($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.openInNewWindow'))
            ->setIcon($this->iconFactory->getIcon('actions-window-open', IconSize::SMALL))
            ->setDataAttributes([
                'dispatch-action' => 'TYPO3.WindowManager.localOpen',
                'dispatch-args' => GeneralUtility::jsonEncodeForHtmlAttribute([
                    $requestUri,
                    true, // switchFocus
                    md5($requestUri), // windowName,
                    'width=670,height=500,status=0,menubar=0,scrollbars=1,resizable=1', // windowFeatures
                ]),
            ])
            ->setDisabled(true);
        $view->addButtonToButtonBar($openInNewWindowButton, $position, $group);
    }

    /**
     * Register the shortcut button to the button bar
     */
    protected function registerShortcutButtonToButtonBar(ModuleTemplate $view, ServerRequestInterface $request): void
    {
        if ($this->returnUrl === $this->getCloseUrl($request)) {
            return;
        }
        $arguments = $this->getUrlQueryParamsForCurrentRequest($request);
        $view->getDocHeaderComponent()->setShortcutContext(
            routeIdentifier: 'record_edit',
            displayName: $this->getShortcutTitle($request),
            arguments: $arguments
        );
    }

    protected function getUrlQueryParamsForCurrentRequest(ServerRequestInterface $request): array
    {
        $queryParams = $request->getQueryParams();
        $potentialArguments = [
            'edit',
            'defVals',
            'overrideVals',
            'columnsOnly',
            'returnNewPageId',
            'module',
        ];
        $arguments = [];
        foreach ($potentialArguments as $argument) {
            if (!empty($queryParams[$argument])) {
                $arguments[$argument] = $queryParams[$argument];
            }
        }
        return $arguments;
    }

    /**
     * Get the count of connected translated content elements
     */
    protected function getConnectedContentElementTranslationsCount(int $page, int $column, int $language): int
    {
        $queryBuilder = $this->getQueryBuilderForTranslationMode($page, $column, $language);
        return (int)$queryBuilder
            ->andWhere(
                $queryBuilder->expr()->gt(
                    $this->tcaSchemaFactory->get('tt_content')->getCapability(TcaSchemaCapability::Language)->getTranslationOriginPointerField()->getName(),
                    $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)
                )
            )
            ->executeQuery()
            ->fetchOne();
    }

    /**
     * Get the count of standalone translated content elements
     */
    protected function getStandAloneContentElementTranslationsCount(int $page, int $column, int $language): int
    {
        $queryBuilder = $this->getQueryBuilderForTranslationMode($page, $column, $language);
        return (int)$queryBuilder
            ->andWhere(
                $queryBuilder->expr()->eq(
                    $this->tcaSchemaFactory->get('tt_content')->getCapability(TcaSchemaCapability::Language)->getTranslationOriginPointerField()->getName(),
                    $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)
                )
            )
            ->executeQuery()
            ->fetchOne();
    }

    /**
     * Get the query builder for the translation mode
     */
    protected function getQueryBuilderForTranslationMode(int $page, int $column, int $language): QueryBuilder
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tt_content');
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
            ->add(GeneralUtility::makeInstance(WorkspaceRestriction::class, $this->getBackendUser()->workspace));
        return $queryBuilder
            ->count('uid')
            ->from('tt_content')
            ->where(
                $queryBuilder->expr()->eq(
                    'pid',
                    $queryBuilder->createNamedParameter($page, Connection::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    $this->tcaSchemaFactory->get('tt_content')->getCapability(TcaSchemaCapability::Language)->getLanguageField()->getName(),
                    $queryBuilder->createNamedParameter($language, Connection::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    'colPos',
                    $queryBuilder->createNamedParameter($column, Connection::PARAM_INT)
                )
            );
    }

    /**
     * Update expanded/collapsed states on new inline records if any within backendUser->uc.
     *
     * @param array|null $uc The uc array to be processed and saved - uc[inlineView][...]
     * @param DataHandler $dataHandler Instance of DataHandler that saved data before
     */
    protected function updateInlineView(?array $uc, DataHandler $dataHandler): void
    {
        if (!is_array($uc['inlineView'] ?? null)) {
            return;
        }
        $backendUser = $this->getBackendUser();
        $inlineView = (array)json_decode(is_string($backendUser->uc['inlineView'] ?? false) ? $backendUser->uc['inlineView'] : '', true);
        foreach ($uc['inlineView'] as $topTable => $topRecords) {
            foreach ($topRecords as $topUid => $childElements) {
                foreach ($childElements as $childTable => $childRecords) {
                    $uids = array_keys($dataHandler->substNEWwithIDs_table, $childTable);
                    if (!empty($uids)) {
                        $newExpandedChildren = [];
                        foreach ($childRecords as $childUid => $state) {
                            if ($state && in_array($childUid, $uids)) {
                                $newChildUid = $dataHandler->substNEWwithIDs[$childUid];
                                $newExpandedChildren[] = $newChildUid;
                            }
                        }
                        // Add new expanded child records to UC (if any):
                        if (!empty($newExpandedChildren)) {
                            $inlineViewCurrent = &$inlineView[$topTable][$topUid][$childTable];
                            if (is_array($inlineViewCurrent)) {
                                $inlineViewCurrent = array_unique(array_merge($inlineViewCurrent, $newExpandedChildren));
                            } else {
                                $inlineViewCurrent = $newExpandedChildren;
                            }
                        }
                    }
                }
            }
        }
        $backendUser->uc['inlineView'] = json_encode($inlineView);
        $backendUser->writeUC();
    }

    /**
     * Returns if delete for the current table is disabled by configuration.
     * For sys_file_metadata in default language delete is always disabled.
     */
    protected function getDisableDelete(): bool
    {
        $disableDelete = false;
        if ($this->firstEl->table === 'sys_file_metadata') {
            $row = $this->firstEl->record;
            if ((int)($row['sys_language_uid'] ?? 0) === 0) {
                // Always disable for default language
                $disableDelete = true;
            }
        } else {
            $disableDelete = (bool)$this->getTsConfigOption($this->firstEl->table, 'disableDelete');
        }
        return $disableDelete;
    }

    /**
     * Return true in case the current record is the current backend user
     */
    protected function isRecordCurrentBackendUser(): bool
    {
        $backendUser = $this->getBackendUser();
        return $this->firstEl->table === 'be_users' && (int)($this->firstEl->uid ?? 0) === $backendUser->getUserId();
    }

    /**
     * Returns the URL (usually for the "returnUrl") which closes the current window.
     * Used when editing a record in a popup.
     */
    protected function getCloseUrl(ServerRequestInterface $request): string
    {
        return (string)PathUtility::getSystemResourceUri('EXT:backend/Resources/Public/Html/Close.html', $request);
    }

    /**
     * Make selector box for creating new translation for a record or switching to edit the record
     * in an existing language. Displays only languages which are available for the current page.
     */
    protected function languageSwitch(ModuleTemplate $view, FormElementData $formElement): void
    {
        $backendUser = $this->getBackendUser();
        if (!$this->tcaSchemaFactory->has($formElement->table)) {
            return;
        }
        $schema = $this->tcaSchemaFactory->get($formElement->table);
        if (!$schema->isLanguageAware()) {
            return;
        }
        $languageCapability = $schema->getCapability(TcaSchemaCapability::Language);
        $languageField = $languageCapability->getLanguageField()->getName();
        $transOrigPointerField = $languageCapability->getTranslationOriginPointerField()->getName();

        $table = $formElement->table;
        if (!$backendUser->check('tables_modify', $table)) {
            return;
        }
        $uid = $formElement->uid;

        // Get all available languages for the page
        // If editing a page, the translations of the current UID need to be fetched
        if ($table === 'pages') {
            if (is_array($formElement->record[$transOrigPointerField] ?? null)) {
                $l10nParent = $formElement->record[$transOrigPointerField];
                $l10nParent = reset($l10nParent);
            } else {
                $l10nParent = $formElement->record[$transOrigPointerField] ?? 0;
            }
            // Ensure the check is always done against the default language page
            $availableLanguages = $this->getLanguages(
                (int)($l10nParent ?: $uid),
                $table
            );
        } else {
            $availableLanguages = $this->getLanguages($formElement->pid, $table);
        }
        // Remove default language, if user does not have access. This is necessary, since
        // the default language is always added when fetching the system languages (#88504).
        if (isset($availableLanguages[0]) && !$this->getBackendUser()->checkLanguageAccess(0)) {
            unset($availableLanguages[0]);
        }
        // Page available in other languages than default language?
        if (count($availableLanguages) > 1) {
            $rowsByLang = [];
            $fetchFields = ['uid', $languageField, $transOrigPointerField];
            // Get record in current language
            $rowCurrent = BackendUtility::getLiveVersionOfRecord($table, $uid, $fetchFields);
            if (!is_array($rowCurrent)) {
                $rowCurrent = BackendUtility::getRecord($table, $uid, $fetchFields);
            }
            $currentLanguage = (int)$rowCurrent[$languageField];
            // Disabled for records with [all] language!
            if ($currentLanguage > -1) {
                // Get record in default language if needed
                if ($currentLanguage && $rowCurrent[$transOrigPointerField]) {
                    $rowsByLang[0] = BackendUtility::getLiveVersionOfRecord(
                        $table,
                        $rowCurrent[$transOrigPointerField],
                        $fetchFields
                    );
                    if (!is_array($rowsByLang[0])) {
                        $rowsByLang[0] = BackendUtility::getRecord(
                            $table,
                            $rowCurrent[$transOrigPointerField],
                            $fetchFields
                        );
                    }
                } else {
                    $rowsByLang[$rowCurrent[$languageField]] = $rowCurrent;
                }
                // List of language id's that should not be added to the selector
                $noAddOption = [];
                if ($rowCurrent[$transOrigPointerField] || $currentLanguage === 0) {
                    // Get record in other languages to see what's already available
                    $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
                    $queryBuilder->getRestrictions()
                        ->removeAll()
                        ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
                        ->add(GeneralUtility::makeInstance(WorkspaceRestriction::class, $backendUser->workspace));
                    $result = $queryBuilder->select(...$fetchFields)
                        ->from($table)
                        ->where(
                            $queryBuilder->expr()->eq(
                                'pid',
                                $queryBuilder->createNamedParameter($formElement->pid, Connection::PARAM_INT)
                            ),
                            $queryBuilder->expr()->gt(
                                $languageField,
                                $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)
                            ),
                            $queryBuilder->expr()->eq(
                                $transOrigPointerField,
                                $queryBuilder->createNamedParameter($rowsByLang[0]['uid'], Connection::PARAM_INT)
                            )
                        )
                        ->executeQuery();
                    while ($row = $result->fetchAssociative()) {
                        if ($backendUser->workspace !== 0 && $schema->isWorkspaceAware()) {
                            $workspaceVersion = BackendUtility::getWorkspaceVersionOfRecord($backendUser->workspace, $table, $row['uid'], 'uid,t3ver_state');
                            if (!empty($workspaceVersion)) {
                                $versionState = VersionState::tryFrom($workspaceVersion['t3ver_state'] ?? 0);
                                if ($versionState === VersionState::DELETE_PLACEHOLDER) {
                                    // If a workspace delete placeholder exists for this translation: Mark
                                    // this language as "don't add to selector" and continue with next row,
                                    // otherwise an edit link to a delete placeholder would be created, which
                                    // does not make sense.
                                    $noAddOption[] = (int)$row[$languageField];
                                    continue;
                                }
                            }
                        }
                        $rowsByLang[$row[$languageField]] = $row;
                    }
                }
                $languageDropDownButton = $this->componentFactory->createDropDownButton()
                    ->setLabel($this->getLanguageService()->sL('core.core:labels.language'))
                    ->setShowActiveLabelText(true)
                    ->setShowLabelText(true);

                $existingLanguageItems = [];
                $newLanguageItems = [];

                foreach ($availableLanguages as $languageId => $language) {
                    $selectorOptionLabel = $language['title'];
                    // Create url for creating a localized record
                    $addOption = true;
                    $createNewLanguageLink = '';

                    if (!isset($rowsByLang[$languageId])) {
                        // Translation in this language does not exist
                        if ($this->columnsOnly[$table] ?? false) {
                            // Don't add option since we are in a view with just a subset of fields, those views
                            // are specific editing fields only views and are not meant for translation handling.
                            $addOption = false;
                        } elseif (!isset($rowsByLang[0]['uid'])) {
                            // Don't add option since no default row to localize from exists
                            // TODO: Actually tt_content is able to localize from another l10n_source then L=0.
                            //       This however is currently only possible via the translation wizard.
                            $addOption = false;
                        }
                    } else {
                        $params = [
                            'edit[' . $table . '][' . $rowsByLang[$languageId]['uid'] . ']' => 'edit',
                            'module' => $this->module?->getIdentifier() ?? '',
                            'returnUrl' => $this->retUrl,
                        ];
                        if ($this->columnsOnly[$table] ?? false) {
                            $params['columnsOnly'] = [$table => $this->columnsOnly[$table]];
                        }
                        if ($table === 'pages') {
                            // Disallow manual adjustment of the language field for pages
                            $params['overrideVals'] = [
                                'pages' => [
                                    'sys_language_uid' => $languageId,
                                ],
                            ];
                        }
                        $createNewLanguageLink = (string)$this->uriBuilder->buildUriFromRoute('record_edit', $params);
                    }
                    if ($addOption && !in_array($languageId, $noAddOption, true)) {
                        if (!$createNewLanguageLink) {
                            $languageItem = $this->componentFactory->createDropDownItem()
                                ->setTag('typo3-backend-localization-button')
                                ->setAttribute('record-type', $table)
                                ->setAttribute('record-uid', (string)$rowsByLang[0]['uid'])
                                ->setAttribute('target-language', (string)$languageId)
                                ->setLabel($selectorOptionLabel);
                            if (!empty($language['flagIcon'])) {
                                $languageItem->setIcon($this->iconFactory->getIcon($language['flagIcon']));
                            }
                            $newLanguageItems[] = $languageItem;
                        } else {
                            $isActive = $languageId === $currentLanguage;
                            $languageItem = $this->componentFactory->createDropDownRadio()
                                ->setLabel($selectorOptionLabel)
                                ->setHref($createNewLanguageLink)
                                ->setActive($isActive);
                            if (!empty($language['flagIcon'])) {
                                $languageItem->setIcon($this->iconFactory->getIcon($language['flagIcon']));
                            }
                            $existingLanguageItems[] = $languageItem;
                        }
                    }
                }

                // Add existing languages first
                foreach ($existingLanguageItems as $item) {
                    $languageDropDownButton->addItem($item);
                }

                // Add separator and new languages if any
                if (!empty($newLanguageItems)) {
                    $languageDropDownButton->addItem($this->componentFactory->createDropDownDivider());
                    $languageDropDownButton->addItem(
                        $this->componentFactory->createDropDownHeader()
                            ->setLabel($this->getLanguageService()->sL('core.core:labels.new_page_translation'))
                    );
                    foreach ($newLanguageItems as $item) {
                        $languageDropDownButton->addItem($item);
                    }
                }

                $view->getDocHeaderComponent()->setLanguageSelector($languageDropDownButton);
            }
        }
    }

    /**
     * Returns languages available for record translations on given page.
     *
     * @param int $id Page id: If zero, all available system languages will be returned. If set to
     *                another value, only languages, a page translation exists for, will be returned.
     * @param string $table For pages we want all languages, for other records the languages of the page translations
     * @return array Array with languages (uid, title, ISOcode, flagIcon)
     */
    protected function getLanguages(int $id, string $table): array
    {
        // This usually happens when a non-pages record is added after another, so we are fetching the proper page ID
        if ($id < 0 && $table !== 'pages') {
            $pageId = $this->pageinfo['uid'] ?? null;
            if ($pageId !== null) {
                $pageId = (int)$pageId;
            } else {
                $fullRecord = BackendUtility::getRecord($table, abs($id));
                $pageId = (int)$fullRecord['pid'];
            }
        } else {
            if ($table === 'pages' && $id > 0) {
                $fullRecord = BackendUtility::getRecordWSOL('pages', $id);
                $id = (int)($fullRecord['t3ver_oid'] ?: $fullRecord['uid']);
            }
            $pageId = $id;
        }
        // Fetch the current translations of this page, to only show the ones where there is a page translation
        $allLanguages = array_filter(
            GeneralUtility::makeInstance(TranslationConfigurationProvider::class)->getSystemLanguages($pageId),
            static fn(array $language): bool => (int)$language['uid'] !== -1
        );
        if ($table !== 'pages' && $id > 0) {
            $schema = $this->tcaSchemaFactory->get('pages');
            $languageCapability = $schema->getCapability(TcaSchemaCapability::Language);
            $languageField = $languageCapability->getLanguageField()->getName();
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
            $queryBuilder->getRestrictions()->removeAll()
                ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
                ->add(GeneralUtility::makeInstance(WorkspaceRestriction::class, $this->getBackendUser()->workspace));
            $statement = $queryBuilder->select('uid', $languageField)
                ->from('pages')
                ->where(
                    $queryBuilder->expr()->eq(
                        $languageCapability->getTranslationOriginPointerField()->getName(),
                        $queryBuilder->createNamedParameter($pageId, Connection::PARAM_INT)
                    )
                )
                ->executeQuery();
            $availableLanguages = [];
            if ($allLanguages[0] ?? false) {
                $availableLanguages = [
                    0 => $allLanguages[0],
                ];
            }
            while ($row = $statement->fetchAssociative()) {
                $languageId = (int)$row[$languageField];
                if (isset($allLanguages[$languageId])) {
                    $availableLanguages[$languageId] = $allLanguages[$languageId];
                }
            }
            return $availableLanguages;
        }
        return $allLanguages;
    }

    /**
     * Fix $this->editconf if versioning applies to any of the records
     *
     * @param array|null $mapArray Mapping between old and new ids if auto-versioning has been performed.
     */
    protected function fixWSversioningInEditConf(array $editConf, ?array $mapArray = null): array
    {
        $finalConfiguration = [];
        foreach ($editConf as $table => $conf) {
            // Traverse the keys/comments of each table (keys can be a comma list of uids)
            $newConf = [];
            foreach ($conf as $theUid => $cmd) {
                if ($cmd === 'edit') {
                    if (is_array($mapArray)) {
                        if ($mapArray[$table][$theUid] ?? false) {
                            $theUid = $mapArray[$table][$theUid];
                        }
                    } else {
                        // Default, look for versions in workspace for record:
                        $calcPRec = $this->getRecordForEdit($table, (int)$theUid);
                        if (is_array($calcPRec)) {
                            // Setting UID again if it had changed, due to workspace versioning.
                            $theUid = (int)$calcPRec['uid'];
                        }
                    }
                    // Add the possibly manipulated IDs to the new-build newConf array:
                    $newConf[$theUid] = $cmd;
                } else {
                    $newConf[$theUid] = $cmd;
                }
            }
            $finalConfiguration[$table] = $newConf;
        }
        return $finalConfiguration;
    }

    /**
     * Get record for editing.
     *
     * @return array|false Returns record to edit, false if none
     */
    protected function getRecordForEdit(string $table, int $recordId): array|bool
    {
        $schema = $this->tcaSchemaFactory->get($table);
        // Fetch requested record:
        $reqRecord = BackendUtility::getRecord($table, $recordId, 'uid,pid' . ($schema->isWorkspaceAware() ? ',t3ver_oid' : ''));
        if (is_array($reqRecord)) {
            // If workspace is OFFLINE:
            if ($this->getBackendUser()->workspace !== 0) {
                // Check for versioning support of the table:
                if ($schema->isWorkspaceAware()) {
                    // If the record is already a version of "something" pass it by.
                    if ($reqRecord['t3ver_oid'] > 0 || VersionState::tryFrom($reqRecord['t3ver_state'] ?? 0) === VersionState::NEW_PLACEHOLDER) {
                        // (If it turns out not to be a version of the current workspace there will be trouble, but
                        // that is handled inside DataHandler then and in the interface it would clearly be an error of
                        // links if the user accesses such a scenario)
                        return $reqRecord;
                    }
                    // The input record was online and an offline version must be found or made:
                    // Look for version of this workspace:
                    $versionRec = BackendUtility::getWorkspaceVersionOfRecord(
                        $this->getBackendUser()->workspace,
                        $table,
                        $reqRecord['uid'],
                        'uid,pid,t3ver_oid'
                    );
                    return is_array($versionRec) ? $versionRec : $reqRecord;
                }
                // This means that editing cannot occur on this record because it was not supporting versioning
                // which is required inside an offline workspace.
                return false;
            }
            // In ONLINE workspace, just return the originally requested record:
            return $reqRecord;
        }
        // Return FALSE because the table/uid was not found anyway.
        return false;
    }

    /**
     * The return value is used for the variable $this->storeArray to prepare 'open documents' urls
     */
    protected function compileStoreData(ServerRequestInterface $request, array $overriddenValues): array
    {
        $queryParams = $request->getQueryParams();
        $parsedBody = $request->getParsedBody();

        $storeArray = [];
        foreach (['edit', 'defVals', 'overrideVals' , 'columnsOnly'] as $key) {
            $value = $overriddenValues[$key] ?? $parsedBody[$key] ?? $queryParams[$key] ?? null;
            if ($value !== null) {
                $storeArray[$key] = $value;
            }
        }
        return $storeArray;
    }

    /**
     * Get a TSConfig 'option.' array, possibly for a specific table.
     */
    protected function getTsConfigOption(string $table, string $key, string $defaultValue = ''): string
    {
        return trim((string)(
            $this->getBackendUser()->getTSConfig()['options.'][$key . '.'][$table]
            ?? $this->getBackendUser()->getTSConfig()['options.'][$key]
            ?? $defaultValue
        ));
    }

    /**
     * Called when someone is done finishing editing - either by just hitting "close" or "save + close",
     * but also for New/AddController when using returnEditConf.
     *
     * At this time, "closing" open documents in the session and unlocking should be done already.
     *
     * @return ResponseInterface|null Redirect response if needed
     */
    protected function closeAndPossiblyRedirectAction(FormAction $requestAction): ?ResponseInterface
    {
        if ($requestAction->shouldCloseWithARedirect()) {
            // If ->returnEditConf is set, then add the current content of editconf to the ->retUrl variable: used by
            // other scripts, like wizard_add, to know which records was created or so...
            if ($this->returnEditConf && !$this->shouldRedirectToEmptyPage()) {
                $this->retUrl .= '&returnEditConf=' . rawurlencode((string)json_encode($this->editconf));
            }
            return new RedirectResponse($this->retUrl, 303);
        }
        if ($this->retUrl === '') {
            return null;
        }
        return new RedirectResponse((string)$this->returnUrl, 303);
    }

    protected function shouldRedirectToEmptyPage(): bool
    {
        return $this->retUrl === (string)$this->uriBuilder->buildUriFromRoute('dummy');
    }

    /**
     * Close the current document(s).
     */
    protected function markOpenDocumentsAsRecentInSesssion(): void
    {
        foreach ($this->editconf as $table => $records) {
            foreach ($records as $uid => $action) {
                if ($action === 'new') {
                    $this->openDocumentRepository->closeDocument($table, 'NEW', $this->getBackendUser());
                } else {
                    $this->openDocumentRepository->closeDocument($table, (string)$uid, $this->getBackendUser());
                }
            }
        }

        // Update signal for UI
        $openDocuments = $this->openDocumentRepository->findOpenDocumentsForUser($this->getBackendUser());
        BackendUtility::setUpdateSignal('OpendocsController::updateNumber', count($openDocuments));
    }

    /**
     * Returns the shortcut title for the current element
     */
    protected function getShortcutTitle(ServerRequestInterface $request): string
    {
        $queryParameters = $request->getQueryParams();
        $languageService = $this->getLanguageService();
        $defaultTitle = $languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.edit');

        if (!is_array($queryParameters['edit'] ?? false)) {
            return $defaultTitle;
        }

        // @todo There may be a more efficient way in using FormEngine FormData.
        // @todo Therefore, the button initialization however has to take place at a later stage.

        $table = (string)key($queryParameters['edit']);
        $schema = $this->tcaSchemaFactory->has($table) ? $this->tcaSchemaFactory->get($table) : null;
        $tableTitle = $schema?->getTitle($languageService->sL(...)) ?: $table;
        $identifier = (string)key($queryParameters['edit'][$table]);
        $action = (string)($queryParameters['edit'][$table][$identifier] ?? '');

        if ($action === 'new') {
            if ($table === 'pages') {
                return sprintf(
                    $languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.createNewPage'),
                    $tableTitle
                );
            }

            $identifier = (int)$identifier;
            if ($identifier === 0) {
                return sprintf(
                    $languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.createNewRecordRootLevel'),
                    $tableTitle
                );
            }

            $pageRecord = null;
            if ($identifier < 0) {
                $parentRecord = BackendUtility::getRecord($table, abs($identifier));
                if ($parentRecord['pid'] ?? false) {
                    $pageRecord = BackendUtility::getRecord('pages', (int)($parentRecord['pid']), 'title');
                }
            } else {
                $pageRecord = BackendUtility::getRecord('pages', $identifier, 'title');
            }

            if ($pageRecord !== null) {
                $pageTitle = BackendUtility::getRecordTitle('pages', $pageRecord);
                if ($pageTitle !== '') {
                    return sprintf(
                        $languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.createNewRecord'),
                        $tableTitle,
                        $pageTitle
                    );
                }
            }

            return $languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.createNew') . ' ' . $tableTitle;
        }

        if ($action === 'edit') {
            if ($multiple = str_contains($identifier, ',')) {
                // Multiple records are given, use the first one for further evaluation of e.g. the parent page
                $recordId = (int)(GeneralUtility::trimExplode(',', $identifier, true)[0] ?? 0);
            } else {
                $recordId = (int)$identifier;
            }
            $record = BackendUtility::getRecord($table, $recordId) ?? [];
            $recordTitle = BackendUtility::getRecordTitle($table, $record);
            if ($table === 'pages') {
                return $multiple
                    ? $languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.editMultiplePages')
                    : sprintf($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.editPage'), $tableTitle, $recordTitle);
            }
            if (!isset($record['pid'])) {
                return $defaultTitle;
            }
            $pageId = (int)$record['pid'];
            if ($pageId === 0) {
                return $multiple
                    ? sprintf($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.editMultipleRecordsRootLevel'), $tableTitle)
                    : sprintf($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.editRecordRootLevel'), $tableTitle, $recordTitle);
            }
            $pageRow = BackendUtility::getRecord('pages', $pageId) ?? [];
            $pageTitle = BackendUtility::getRecordTitle('pages', $pageRow);
            if ($multiple) {
                return sprintf($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.editMultipleRecords'), $tableTitle, $pageTitle);
            }
            if ($recordTitle !== '') {
                return sprintf($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.editRecord'), $tableTitle, $recordTitle, $pageTitle);
            }
            return sprintf($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.editRecordNoTitle'), $tableTitle, $pageTitle);
        }

        return $defaultTitle;
    }

    protected function resolveDefaultReturnUrl(): string
    {
        $module = $this->moduleProvider->getFirstAccessibleModule($this->getBackendUser());
        $routeName = $module ? $module->getIdentifier() : 'dummy';
        return (string)$this->uriBuilder->buildUriFromRoute($routeName);
    }

    /**
     * Whether a single record view is requested. This
     * means, only one element exists in $elementsData.
     */
    protected function isSingleRecordView(): bool
    {
        return count($this->elementsData) === 1;
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
