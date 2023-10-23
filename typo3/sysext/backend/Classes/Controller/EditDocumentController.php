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
use TYPO3\CMS\Backend\Attribute\Controller;
use TYPO3\CMS\Backend\Configuration\TranslationConfigurationProvider;
use TYPO3\CMS\Backend\Controller\Event\AfterFormEnginePageInitializedEvent;
use TYPO3\CMS\Backend\Controller\Event\BeforeFormEnginePageInitializedEvent;
use TYPO3\CMS\Backend\Form\Exception\AccessDeniedException;
use TYPO3\CMS\Backend\Form\Exception\DatabaseRecordException;
use TYPO3\CMS\Backend\Form\Exception\DatabaseRecordWorkspaceDeletePlaceholderException;
use TYPO3\CMS\Backend\Form\FormDataCompiler;
use TYPO3\CMS\Backend\Form\FormDataGroup\TcaDatabaseRecord;
use TYPO3\CMS\Backend\Form\FormResultCompiler;
use TYPO3\CMS\Backend\Form\NodeFactory;
use TYPO3\CMS\Backend\Routing\Exception\ResourceNotFoundException;
use TYPO3\CMS\Backend\Routing\PreviewUriBuilder;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
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
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
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
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\HttpUtility;
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
#[Controller]
class EditDocumentController
{
    protected const DOCUMENT_CLOSE_MODE_DEFAULT = 0;
    // works like DOCUMENT_CLOSE_MODE_DEFAULT
    protected const DOCUMENT_CLOSE_MODE_REDIRECT = 1;
    protected const DOCUMENT_CLOSE_MODE_CLEAR_ALL = 3;
    protected const DOCUMENT_CLOSE_MODE_NO_REDIRECT = 4;

    /**
     * An array looking approx like [tablename][list-of-ids]=command, eg. "&edit[pages][123]=edit".
     *
     * @var array<string,array>
     */
    protected $editconf = [];

    /**
     * Comma list of field names to edit. If specified, only those fields will be rendered.
     * Otherwise all (available) fields in the record are shown according to the TCA type.
     *
     * @var string|null
     */
    protected $columnsOnly;

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
     * $this->retUrl will link to dummy controller
     *
     * @var string|null
     */
    protected $returnUrl;

    /**
     * Prepared return URL. Contains the URL that we should return to from FormEngine if
     * close button is clicked. Usually passed along as 'returnUrl', but falls back to
     * "dummy" controller.
     *
     * @var string
     */
    protected $retUrl;

    /**
     * Close document command. One of the DOCUMENT_CLOSE_MODE_* constants above
     */
    protected int $closeDoc;

    /**
     * If true, the processing of incoming data will be performed as if a save-button is pressed.
     * Used in the forms as a hidden field which can be set through
     * JavaScript if the form is somehow submitted by JavaScript.
     *
     * @var bool
     */
    protected $doSave;

    /**
     * Main DataHandler datamap array
     *
     * @var array
     */
    protected $data;

    /**
     * Main DataHandler cmdmap array
     *
     * @var array
     */
    protected $cmd;

    /**
     * DataHandler 'mirror' input
     *
     * @var array
     */
    protected $mirror;

    /**
     * Boolean: If set, then the GET var "&id=" will be added to the
     * retUrl string so that the NEW id of something is returned to the script calling the form.
     *
     * @var bool
     */
    protected $returnNewPageId = false;

    /**
     * ID for displaying the page in the frontend, "save and view"
     *
     * @var int
     */
    protected $popViewId;

    /**
     * @var string|null
     */
    protected $previewCode;

    /**
     * Alternative title for the document handler.
     *
     * @var string
     */
    protected $recTitle;

    /**
     * If set, then no save & view button is printed
     *
     * @var bool
     */
    protected $noView;

    /**
     * @var string
     */
    protected $perms_clause;

    /**
     * If true, $this->editconf array is added a redirect response, used by Wizard/AddController
     *
     * @var bool
     */
    protected $returnEditConf;

    /**
     * parse_url() of current requested URI, contains ['path'] and ['query'] parts.
     *
     * @var array
     */
    protected $R_URL_parts;

    /**
     * Contains $request query parameters. This array is the foundation for creating
     * the R_URI internal var which becomes the url to which forms are submitted
     *
     * @var array
     */
    protected $R_URL_getvars;

    /**
     * Set to the URL of this script including variables which is needed to re-display the form.
     *
     * @var string
     */
    protected $R_URI;

    /**
     * @var array
     */
    protected $pageinfo;

    /**
     * Is loaded with the "title" of the currently "open document"
     * used for the open document toolbar
     *
     * @var string
     */
    protected $storeTitle = '';

    /**
     * Contains an array with key/value pairs of GET parameters needed to reach the
     * current document displayed - used in the 'open documents' toolbar.
     *
     * @var array
     */
    protected $storeArray;

    /**
     * $this->storeArray imploded to url
     *
     * @var string
     */
    protected $storeUrl;

    /**
     * md5 hash of storeURL, used to identify a single open document in backend user uc
     *
     * @var string
     */
    protected $storeUrlMd5;

    /**
     * Backend user session data of this module
     *
     * @var array
     */
    protected $docDat;

    /**
     * An array of the "open documents" - keys are md5 hashes (see $storeUrlMd5) identifying
     * the various documents on the GET parameter list needed to open it. The values are
     * arrays with 0,1,2 keys with information about the document (see compileStoreData()).
     * The docHandler variable is stored in the $docDat session data, key "0".
     *
     * @var array
     */
    protected $docHandler;

    /**
     * Array of the elements to create edit forms for.
     *
     * @var array
     */
    protected $elementsData;

    /**
     * Pointer to the first element in $elementsData
     *
     * @var array
     */
    protected $firstEl;

    /**
     * Counter, used to count the number of errors (when users do not have edit permissions)
     *
     * @var int
     */
    protected $errorC;

    /**
     * Is set to the pid value of the last shown record - thus indicating which page to
     * show when clicking the SAVE/VIEW button
     *
     * @var int
     */
    protected $viewId;

    /**
     * @var FormResultCompiler
     */
    protected $formResultCompiler;

    /**
     * Used internally to disable the storage of the document reference (eg. new records)
     *
     * @var int
     */
    protected $dontStoreDocumentRef = 0;

    /**
     * Stores information needed to preview the currently saved record
     *
     * @var array
     */
    protected $previewData = [];

    /**
     * True if a record has been saved
     */
    protected bool $isSavedRecord = false;

    protected bool $isPageInFreeTranslationMode = false;

    public function __construct(
        protected readonly EventDispatcherInterface $eventDispatcher,
        protected readonly IconFactory $iconFactory,
        protected readonly PageRenderer $pageRenderer,
        protected readonly UriBuilder $uriBuilder,
        protected readonly ModuleTemplateFactory $moduleTemplateFactory,
        protected readonly BackendEntryPointResolver $backendEntryPointResolver
    ) {}

    /**
     * Main dispatcher entry method registered as "record_edit" end point.
     */
    public function mainAction(ServerRequestInterface $request): ResponseInterface
    {
        $view = $this->moduleTemplateFactory->create($request);
        $view->setUiBlock(true);
        $view->setTitle($this->getShortcutTitle($request));

        // Unlock all locked records
        BackendUtility::lockRecords();
        if ($response = $this->preInit($request)) {
            return $response;
        }

        // Process incoming data via DataHandler?
        $parsedBody = $request->getParsedBody();
        if ((
            $this->doSave
                || isset($parsedBody['_savedok'])
                || isset($parsedBody['_saveandclosedok'])
                || isset($parsedBody['_savedokview'])
                || isset($parsedBody['_savedoknew'])
                || isset($parsedBody['_duplicatedoc'])
        )
            && $request->getMethod() === 'POST'
            && $response = $this->processData($view, $request)
        ) {
            return $response;
        }

        $this->init($request);

        if ($request->getMethod() === 'POST') {
            // In case save&view is requested, we have to add this information to the redirect
            // URL, since the ImmediateAction will be added to the module body afterwards.
            if (isset($parsedBody['_savedokview'])) {
                $this->R_URI = rtrim($this->R_URI, '&') .
                    HttpUtility::buildQueryString([
                        'showPreview' => true,
                        'popViewId' => $parsedBody['popViewId'] ?? $this->getPreviewPageId(),
                    ], (empty($this->R_URL_getvars) ? '?' : '&'));
            }
            return new RedirectResponse($this->R_URI, 302);
        }

        $view->assign('bodyHtml', $this->main($view, $request));
        return $view->renderResponse('Form/EditDocument');
    }

    /**
     * First initialization, always called, even before processData() executes DataHandler processing.
     */
    protected function preInit(ServerRequestInterface $request): ?ResponseInterface
    {
        if ($response = $this->localizationRedirect($request)) {
            return $response;
        }

        $parsedBody = $request->getParsedBody();
        $queryParams = $request->getQueryParams();

        $this->editconf = $parsedBody['edit'] ?? $queryParams['edit'] ?? [];
        $this->defVals = $parsedBody['defVals'] ?? $queryParams['defVals'] ?? null;
        $this->overrideVals = $parsedBody['overrideVals'] ?? $queryParams['overrideVals'] ?? null;
        $this->columnsOnly = $parsedBody['columnsOnly'] ?? $queryParams['columnsOnly'] ?? null;
        $this->returnUrl = GeneralUtility::sanitizeLocalUrl($parsedBody['returnUrl'] ?? $queryParams['returnUrl'] ?? '');
        $this->closeDoc = (int)($parsedBody['closeDoc'] ?? $queryParams['closeDoc'] ?? self::DOCUMENT_CLOSE_MODE_DEFAULT);
        $this->doSave = ($parsedBody['doSave'] ?? false) && $request->getMethod() === 'POST';
        $this->returnEditConf = (bool)($parsedBody['returnEditConf'] ?? $queryParams['returnEditConf'] ?? false);

        // Set overrideVals as default values if defVals does not exist.
        // @todo: Why?
        if (!is_array($this->defVals) && is_array($this->overrideVals)) {
            $this->defVals = $this->overrideVals;
        }
        $this->addSlugFieldsToColumnsOnly($queryParams);

        // Set final return URL
        $this->retUrl = $this->returnUrl ?: (string)$this->uriBuilder->buildUriFromRoute('dummy');

        // Change $this->editconf if versioning applies to any of the records
        $this->fixWSversioningInEditConf();

        // Prepare R_URL (request url)
        $this->R_URL_parts = parse_url($request->getAttribute('normalizedParams')->getRequestUri()) ?: [];
        $this->R_URL_getvars = $queryParams;
        $this->R_URL_getvars['edit'] = $this->editconf;

        // Prepare 'open documents' url, this is later modified again various times
        $this->compileStoreData($request);
        // Backend user session data of this module
        $this->docDat = $this->getBackendUser()->getModuleData('FormEngine', 'ses');
        $this->docHandler = $this->docDat[0] ?? [];

        // Close document if a request for closing the document has been sent
        if ($this->closeDoc > self::DOCUMENT_CLOSE_MODE_DEFAULT) {
            if ($response = $this->closeDocument($this->closeDoc, $request)) {
                return $response;
            }
        }

        $event = new BeforeFormEnginePageInitializedEvent($this, $request);
        $this->eventDispatcher->dispatch($event);
        return null;
    }

    /**
     * Always add required fields of slug field
     */
    protected function addSlugFieldsToColumnsOnly(array $queryParams): void
    {
        $data = $queryParams['edit'] ?? [];
        $data = array_keys($data);
        $table = reset($data);
        if ($this->columnsOnly && $table !== false && isset($GLOBALS['TCA'][$table])) {
            $fields = GeneralUtility::trimExplode(',', $this->columnsOnly, true);
            foreach ($fields as $field) {
                $postModifiers = $GLOBALS['TCA'][$table]['columns'][$field]['config']['generatorOptions']['postModifiers'] ?? [];
                if (isset($GLOBALS['TCA'][$table]['columns'][$field])
                    && $GLOBALS['TCA'][$table]['columns'][$field]['config']['type'] === 'slug'
                    && (!is_array($postModifiers) || $postModifiers === [])
                ) {
                    foreach ($GLOBALS['TCA'][$table]['columns'][$field]['config']['generatorOptions']['fields'] ?? [] as $fields) {
                        $this->columnsOnly .= ',' . (is_array($fields) ? implode(',', $fields) : $fields);
                    }
                }
            }
        }
    }

    /**
     * Do processing of data, submitting it to DataHandler. May return a RedirectResponse.
     */
    protected function processData(ModuleTemplate $view, ServerRequestInterface $request): ?ResponseInterface
    {
        $parsedBody = $request->getParsedBody();

        $beUser = $this->getBackendUser();

        // Processing related GET / POST vars
        $this->data = $parsedBody['data'] ?? [];
        $this->cmd = $parsedBody['cmd'] ?? [];
        $this->mirror = $parsedBody['mirror']  ?? [];
        $this->returnNewPageId = (bool)($parsedBody['returnNewPageId'] ?? false);

        // Only options related to $this->data submission are included here
        $tce = GeneralUtility::makeInstance(DataHandler::class);

        $tce->setControl($parsedBody['control'] ?? []);

        // Set internal vars
        if (isset($beUser->uc['neverHideAtCopy']) && $beUser->uc['neverHideAtCopy']) {
            $tce->neverHideAtCopy = true;
        }

        // Set default values fetched previously from GET / POST vars
        if (is_array($this->defVals) && $this->defVals !== [] && is_array($tce->defaultValues)) {
            $tce->defaultValues = array_merge_recursive($this->defVals, $tce->defaultValues);
        }

        // Load DataHandler with data
        $tce->start($this->data, $this->cmd);
        if (is_array($this->mirror)) {
            $tce->setMirror($this->mirror);
        }

        // Perform the saving operation with DataHandler:
        if ($this->doSave === true) {
            $tce->process_datamap();
            $tce->process_cmdmap();

            // Update the module menu for the current backend user, as they updated their UI language
            $currentUserId = (int)($beUser->user[$beUser->userid_column] ?? 0);
            if ($currentUserId
                && (string)($this->data['be_users'][$currentUserId]['lang'] ?? '') !== ''
                && $this->data['be_users'][$currentUserId]['lang'] !== $beUser->user['lang']
            ) {
                $newLanguageKey = $this->data['be_users'][$currentUserId]['lang'];
                // Update the current backend user language as well
                $beUser->user['lang'] = $newLanguageKey;
                // Re-create LANG to have the current request updated the translated page as well
                $this->getLanguageService()->init($newLanguageKey);
                BackendUtility::setUpdateSignal('updateModuleMenu');
                BackendUtility::setUpdateSignal('updateTopbar');
            }
        }
        // If pages are being edited, we set an instruction about updating the page tree after this operation.
        if ($tce->pagetreeNeedsRefresh
            && (isset($this->data['pages']) || $beUser->workspace !== 0 && !empty($this->data))
        ) {
            BackendUtility::setUpdateSignal('updatePageTree');
        }
        // If there was saved any new items, load them:
        if (!empty($tce->substNEWwithIDs_table)) {
            // Save the expanded/collapsed states for new inline records, if any
            $this->updateInlineView($request->getParsedBody()['uc'] ?? $request->getQueryParams()['uc'] ?? null, $tce);
            $newEditConf = [];
            foreach ($this->editconf as $tableName => $tableCmds) {
                $keys = array_keys($tce->substNEWwithIDs_table, $tableName);
                if (!empty($keys)) {
                    foreach ($keys as $key) {
                        $editId = $tce->substNEWwithIDs[$key];
                        // Check if the $editId isn't a child record of an IRRE action
                        if (!(is_array($tce->newRelatedIDs[$tableName] ?? null)
                            && in_array($editId, $tce->newRelatedIDs[$tableName]))
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
                        // Traverse all new records and forge the content of ->editconf so we can continue to edit these records!
                        if ($tableName === 'pages'
                            && $this->retUrl !== (string)$this->uriBuilder->buildUriFromRoute('dummy')
                            && $this->retUrl !== $this->getCloseUrl()
                            && $this->returnNewPageId
                        ) {
                            $this->retUrl .= '&id=' . $tce->substNEWwithIDs[$key];
                        }
                    }
                } else {
                    $newEditConf[$tableName] = $tableCmds;
                }
            }
            // Reset editconf if newEditConf has values
            if (!empty($newEditConf)) {
                $this->editconf = $newEditConf;
            }
            // Finally, set the editconf array in the "getvars" so they will be passed along in URLs as needed.
            $this->R_URL_getvars['edit'] = $this->editconf;
            // Unset default values since we don't need them anymore.
            unset($this->R_URL_getvars['defVals']);
            // Recompile the store* values since editconf changed
            $this->compileStoreData($request);
        }
        // See if any records was auto-created as new versions?
        if (!empty($tce->autoVersionIdMap)) {
            $this->fixWSversioningInEditConf($tce->autoVersionIdMap);
        }
        // If a document is saved and a new one is created right after.
        if (isset($parsedBody['_savedoknew']) && is_array($this->editconf)) {
            if ($redirect = $this->closeDocument(self::DOCUMENT_CLOSE_MODE_NO_REDIRECT, $request)) {
                return $redirect;
            }
            // Find the current table
            reset($this->editconf);
            $nTable = (string)key($this->editconf);
            // Finding the first id, getting the records pid+uid
            reset($this->editconf[$nTable]);
            $nUid = (int)key($this->editconf[$nTable]);
            $recordFields = 'pid,uid';
            if (BackendUtility::isTableWorkspaceEnabled($nTable)) {
                $recordFields .= ',t3ver_oid';
            }
            $nRec = BackendUtility::getRecord($nTable, $nUid, $recordFields);
            // Determine insertion mode: 'top' is self-explaining,
            // otherwise new elements are inserted after one using a negative uid
            $insertRecordOnTop = ($this->getTsConfigOption($nTable, 'saveDocNew') === 'top');
            // Setting a blank editconf array for a new record:
            $this->editconf = [];
            // Determine related page ID for regular live context
            if ((int)($nRec['t3ver_oid'] ?? 0) === 0) {
                if ($insertRecordOnTop) {
                    $relatedPageId = $nRec['pid'];
                } else {
                    $relatedPageId = -$nRec['uid'];
                }
            } else {
                // Determine related page ID for workspace context
                if ($insertRecordOnTop) {
                    // Fetch live version of workspace version since the pid value is always -1 in workspaces
                    $liveRecord = BackendUtility::getRecord($nTable, $nRec['t3ver_oid'], $recordFields);
                    $relatedPageId = $liveRecord['pid'];
                } else {
                    // Use uid of live version of workspace version
                    $relatedPageId = -$nRec['t3ver_oid'];
                }
            }
            $this->editconf[$nTable][$relatedPageId] = 'new';
            // Finally, set the editconf array in the "getvars" so they will be passed along in URLs as needed.
            $this->R_URL_getvars['edit'] = $this->editconf;
            // Recompile the store* values since editconf changed...
            $this->compileStoreData($request);
        }

        // Explicitly require a save operation
        if ($this->doSave) {
            $erroneousRecords = $tce->printLogErrorMessages();
            $messages = [];
            $table = (string)key($this->editconf);
            $uidList = GeneralUtility::intExplode(',', (string)key($this->editconf[$table]));

            foreach ($uidList as $uid) {
                $uid = (int)abs($uid);
                if (!in_array($table . '.' . $uid, $erroneousRecords, true)) {
                    $realUidInPayload = ($tceSubstId = array_search($uid, $tce->substNEWwithIDs, true)) !== false ? $tceSubstId : $uid;
                    $row = $this->data[$table][$uid] ?? $this->data[$table][$realUidInPayload] ?? null;
                    if ($row === null) {
                        continue;
                    }
                    // Ensure, uid is always available to make labels with foreign table lookups possible
                    $row['uid'] ??= $realUidInPayload;
                    // If the label column of the record is not available, fetch it from database.
                    // This is the when EditDocumentController is booted in single field mode (e.g.
                    // Template module > 'info/modify' > edit 'setup' field) or in case the field is
                    // not in "showitem" or is set to readonly (e.g. "file" in sys_file_metadata).
                    $labelArray = [$GLOBALS['TCA'][$table]['ctrl']['label'] ?? null];
                    $labelAltArray = GeneralUtility::trimExplode(',', $GLOBALS['TCA'][$table]['ctrl']['label_alt'] ?? '', true);
                    $labelFields = array_unique(array_filter(array_merge($labelArray, $labelAltArray)));
                    foreach ($labelFields as $labelField) {
                        if (!isset($row[$labelField])) {
                            $tmpRecord = BackendUtility::getRecord($table, $uid, implode(',', $labelFields));
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
        if (isset($parsedBody['_duplicatedoc']) && is_array($this->editconf)) {
            $this->closeDocument(self::DOCUMENT_CLOSE_MODE_NO_REDIRECT, $request);
            // Find current table
            reset($this->editconf);
            $nTable = (string)key($this->editconf);
            // Find the first id, getting the records pid+uid
            reset($this->editconf[$nTable]);
            $nUid = key($this->editconf[$nTable]);
            if (!MathUtility::canBeInterpretedAsInteger($nUid)) {
                $nUid = $tce->substNEWwithIDs[$nUid];
            }

            $recordFields = 'pid,uid';
            if (BackendUtility::isTableWorkspaceEnabled($nTable)) {
                $recordFields .= ',t3ver_oid';
            }
            $nRec = BackendUtility::getRecord($nTable, $nUid, $recordFields);

            // Setting a blank editconf array for a new record:
            $this->editconf = [];

            if ((int)($nRec['t3ver_oid'] ?? 0) === 0) {
                $relatedPageId = -$nRec['uid'];
            } else {
                $relatedPageId = -$nRec['t3ver_oid'];
            }

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

            $duplicateMappingArray = $duplicateTce->copyMappingArray;
            $duplicateUid = $duplicateMappingArray[$nTable][$nUid];

            if ($nTable === 'pages') {
                BackendUtility::setUpdateSignal('updatePageTree');
            }

            $this->editconf[$nTable][$duplicateUid] = 'edit';
            // Finally, set the editconf array in the "getvars" so they will be passed along in URLs as needed.
            $this->R_URL_getvars['edit'] = $this->editconf;
            // Recompile the store* values since editconf changed...
            $this->compileStoreData($request);

            // Inform the user of the duplication
            $view->addFlashMessage($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.recordDuplicated'));
        }

        if ($this->closeDoc < self::DOCUMENT_CLOSE_MODE_DEFAULT
            || isset($parsedBody['_saveandclosedok'])
        ) {
            // Redirect if element should be closed after save
            return $this->closeDocument((int)abs($this->closeDoc), $request);
        }
        return null;
    }

    /**
     * Initialize the view part of the controller logic.
     */
    protected function init(ServerRequestInterface $request): void
    {
        $parsedBody = $request->getParsedBody();
        $queryParams = $request->getQueryParams();

        $beUser = $this->getBackendUser();

        $this->popViewId = (int)($parsedBody['popViewId'] ?? $queryParams['popViewId'] ?? 0);
        $this->recTitle = (string)($parsedBody['recTitle'] ?? $queryParams['recTitle'] ?? '');
        $this->noView = (bool)($parsedBody['noView'] ?? $queryParams['noView'] ?? false);
        $this->perms_clause = $beUser->getPagePermsClause(Permission::PAGE_SHOW);

        // Preview code is implicit only generated for GET requests, having the query
        // parameters "popViewId" (the preview page id) and "showPreview" set.
        if ($this->popViewId && ($queryParams['showPreview'] ?? false)) {
            // Generate the preview code (markup), which is added to the module body later
            $this->previewCode = $this->generatePreviewCode();
            // After generating the preview code, those params should not longer be applied to the form
            // action, as this would otherwise always refresh the preview window on saving the record.
            unset($this->R_URL_getvars['showPreview'], $this->R_URL_getvars['popViewId']);
        }

        // Set other internal variables:
        $this->R_URL_getvars['returnUrl'] = $this->retUrl;
        $this->R_URI = $this->R_URL_parts['path'] . HttpUtility::buildQueryString($this->R_URL_getvars, '?');

        $this->pageRenderer->getJavaScriptRenderer()->includeTaggedImports('backend.form');
        $this->pageRenderer->addInlineLanguageLabelFile('EXT:backend/Resources/Private/Language/locallang_alt_doc.xlf');

        $event = new AfterFormEnginePageInitializedEvent($this, $request);
        $this->eventDispatcher->dispatch($event);
    }

    /**
     * Generates markup for immediate action dispatching.
     */
    protected function generatePreviewCode(): ?string
    {
        $array_keys = array_keys($this->editconf);
        $this->previewData['table'] = reset($array_keys) ?: null;
        $array_keys = array_keys($this->editconf[$this->previewData['table']]);
        $this->previewData['id'] = reset($array_keys) ?: null;

        $previewPageId = $this->getPreviewPageId();
        $anchorSection = $this->getPreviewUrlAnchorSection();
        $previewPageRootLine = BackendUtility::BEgetRootLine($previewPageId);
        $previewUrlParameters = $this->getPreviewUrlParameters($previewPageId);

        return PreviewUriBuilder::create($previewPageId)
            ->withRootLine($previewPageRootLine)
            ->withSection($anchorSection)
            ->withAdditionalQueryParameters($previewUrlParameters)
            ->buildImmediateActionElement([PreviewUriBuilder::OPTION_SWITCH_FOCUS => null]);
    }

    /**
     * Returns the parameters for the preview URL
     */
    protected function getPreviewUrlParameters(int $previewPageId): string
    {
        $linkParameters = [];
        $table = ($this->previewData['table'] ?? '') ?: ($this->firstEl['table'] ?? '');
        $recordId = ($this->previewData['id'] ?? '') ?: ($this->firstEl['uid'] ?? '');
        $previewConfiguration = BackendUtility::getPagesTSconfig($previewPageId)['TCEMAIN.']['preview.'][$table . '.'] ?? [];
        $recordArray = BackendUtility::getRecord($table, $recordId);

        // language handling
        $languageField = $GLOBALS['TCA'][$table]['ctrl']['languageField'] ?? '';
        if ($languageField && !empty($recordArray[$languageField])) {
            $recordId = $this->resolvePreviewRecordId($table, $recordArray, $previewConfiguration);
            $language = $recordArray[$languageField];
            if ($language > 0) {
                $linkParameters['_language'] = $language;
            }
        }

        // Always use live workspace record uid for the preview
        if (BackendUtility::isTableWorkspaceEnabled($table) && ($recordArray['t3ver_oid'] ?? 0) > 0) {
            $recordId = $recordArray['t3ver_oid'];
        }

        // map record data to GET parameters
        if (isset($previewConfiguration['fieldToParameterMap.'])) {
            foreach ($previewConfiguration['fieldToParameterMap.'] as $field => $parameterName) {
                $value = $recordArray[$field] ?? '';
                if ($field === 'uid') {
                    $value = $recordId;
                }
                $linkParameters[$parameterName] = $value;
            }
        }

        // add/override parameters by configuration
        if (isset($previewConfiguration['additionalGetParameters.'])) {
            $linkParameters = array_replace(
                $linkParameters,
                GeneralUtility::removeDotsFromTS($previewConfiguration['additionalGetParameters.'])
            );
        }

        return HttpUtility::buildQueryString($linkParameters, '&');
    }

    protected function resolvePreviewRecordId(string $table, array $recordArray, array $previewConfiguration): int
    {
        $l10nPointer = $GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField'] ?? '';
        if ($l10nPointer
            && !empty($recordArray[$l10nPointer])
            && (
                // not set -> default to true
                !isset($previewConfiguration['useDefaultLanguageRecord'])
                // or set -> use value
                || $previewConfiguration['useDefaultLanguageRecord']
            )
        ) {
            return (int)$recordArray[$l10nPointer];
        }
        return (int)$recordArray['uid'];
    }

    /**
     * Returns the anchor section for the preview url
     */
    protected function getPreviewUrlAnchorSection(): string
    {
        $table = ($this->previewData['table'] ?? '') ?: ($this->firstEl['table'] ?? '');
        $recordId = ($this->previewData['id'] ?? '') ?: ($this->firstEl['uid'] ?? '');

        return $table === 'tt_content' ? '#c' . (int)$recordId : '';
    }

    /**
     * Returns the preview page id
     */
    protected function getPreviewPageId(): int
    {
        $previewPageId = 0;
        $table = ($this->previewData['table'] ?? '') ?: ($this->firstEl['table'] ?? '');
        $recordId = ($this->previewData['id'] ?? '') ?: ($this->firstEl['uid'] ?? '');
        $pageId = $this->popViewId ?: $this->viewId;

        if ($table === 'pages') {
            $currentPageId = (int)$recordId;
        } else {
            $currentPageId = MathUtility::convertToPositiveInteger($pageId);
        }

        $previewConfiguration = BackendUtility::getPagesTSconfig($currentPageId)['TCEMAIN.']['preview.'][$table . '.'] ?? [];

        if (isset($previewConfiguration['previewPageId'])) {
            $previewPageId = (int)$previewConfiguration['previewPageId'];
        }
        // if no preview page was configured
        if (!$previewPageId) {
            $rootPageData = null;
            $rootLine = BackendUtility::BEgetRootLine($currentPageId);
            $currentPage = (array)(reset($rootLine) ?: []);
            if ($this->canViewDoktype($currentPage)) {
                // try the current page
                $previewPageId = $currentPageId;
            } else {
                // or search for the root page
                foreach ($rootLine as $page) {
                    if ($page['is_siteroot']) {
                        $rootPageData = $page;
                        break;
                    }
                }
                $previewPageId = isset($rootPageData)
                    ? (int)$rootPageData['uid']
                    : $currentPageId;
            }
        }

        $this->popViewId = $previewPageId;

        return $previewPageId;
    }

    /**
     * Check whether the current page has a "no view doktype" assigned
     */
    protected function canViewDoktype(array $currentPage): bool
    {
        if (!isset($currentPage['uid']) || !($currentPage['doktype'] ?? false)) {
            // In case the current page record is invalid, the element can not be viewed
            return false;
        }

        return !in_array((int)$currentPage['doktype'], [
            PageRepository::DOKTYPE_SPACER,
            PageRepository::DOKTYPE_SYSFOLDER,
            PageRepository::DOKTYPE_RECYCLER,
        ], true);
    }

    /**
     * Main module operation
     */
    protected function main(ModuleTemplate $view, ServerRequestInterface $request): string
    {
        $body = $this->previewCode ?? '';
        // Begin edit
        if (is_array($this->editconf)) {
            $this->formResultCompiler = GeneralUtility::makeInstance(FormResultCompiler::class);

            // Creating the editing form, wrap it with buttons, document selector etc.
            $editForm = $this->makeEditForm($request, $view);
            if ($editForm) {
                $this->firstEl = $this->elementsData ? reset($this->elementsData) : null;
                // Checking if the currently open document is stored in the list of "open documents" - if not, add it:
                if ((($this->docDat[1] ?? null) !== $this->storeUrlMd5 || !isset($this->docHandler[$this->storeUrlMd5]))
                    && !$this->dontStoreDocumentRef
                ) {
                    $this->docHandler[$this->storeUrlMd5] = [
                        $this->storeTitle,
                        $this->storeArray,
                        $this->storeUrl,
                        $this->firstEl,
                        $this->returnUrl,
                    ];
                    $this->getBackendUser()->pushModuleData('FormEngine', [$this->docHandler, $this->storeUrlMd5]);
                    BackendUtility::setUpdateSignal('OpendocsController::updateNumber', count($this->docHandler));
                }
                $body .= $this->formResultCompiler->addCssFiles();
                $body .= $this->compileForm($editForm);
                $body .= $this->formResultCompiler->printNeededJSFunctions();
                $body .= '</form>';
            }
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
        $this->pageinfo = BackendUtility::readPageAccess($this->viewId, $this->perms_clause) ?: [];
        // Setting up the buttons and markers for doc header
        $this->resolveMetaInformation($view);
        $this->getButtons($view, $request);

        // Create language switch options if the record is already persisted, and it is a single record to edit
        if ($this->isSavedRecord && $this->isSingleRecordView()) {
            $this->languageSwitch(
                $view,
                (string)($this->firstEl['table'] ?? ''),
                (int)($this->firstEl['uid'] ?? 0),
                isset($this->firstEl['pid']) ? (int)$this->firstEl['pid'] : null
            );
        }

        return $body;
    }

    protected function resolveMetaInformation(ModuleTemplate $view): void
    {
        $file = null;
        if (($this->firstEl['table'] ?? '') === 'sys_file_metadata' && (int)($this->firstEl['uid'] ?? 0) > 0) {
            $fileUid = (int)(BackendUtility::getRecord('sys_file_metadata', (int)$this->firstEl['uid'], 'file')['file'] ?? 0);
            try {
                $file = GeneralUtility::makeInstance(ResourceFactory::class)->getFileObject($fileUid);
            } catch (FileDoesNotExistException|InsufficientUserPermissionsException $e) {
                // do nothing when file is not accessible
            }
        }
        if ($file instanceof FileInterface) {
            $view->getDocHeaderComponent()->setMetaInformationForResource($file);
        } elseif ($this->pageinfo !== []) {
            $view->getDocHeaderComponent()->setMetaInformation($this->pageinfo);
        }
    }

    /**
     * Creates the editing form with FormEngine, based on the input from GPvars.
     *
     * @return string HTML form elements wrapped in tables
     */
    protected function makeEditForm(ServerRequestInterface $request, ModuleTemplate $view): string
    {
        // Initialize variables
        $this->elementsData = [];
        $this->errorC = 0;
        $editForm = '';
        $beUser = $this->getBackendUser();
        // Traverse the GPvar edit array tables
        foreach ($this->editconf as $table => $conf) {
            if (!is_array($conf) || !($GLOBALS['TCA'][$table] ?? false)) {
                // Skip for invalid config or in case no TCA exists
                continue;
            }
            if (!$beUser->check('tables_modify', $table)) {
                // Skip in case the user has insufficient permissions and increment the error counter
                $this->errorC++;
                continue;
            }
            // Traverse the keys/comments of each table (keys can be a comma list of uids)
            foreach ($conf as $cKey => $command) {
                if ($command !== 'edit' && $command !== 'new') {
                    // Skip if invalid command
                    continue;
                }
                // Get the ids:
                $ids = GeneralUtility::trimExplode(',', (string)$cKey, true);
                // Traverse the ids:
                foreach ($ids as $theUid) {
                    // Don't save this document title in the document selector if the document is new.
                    if ($command === 'new') {
                        $this->dontStoreDocumentRef = 1;
                    }

                    try {
                        $formDataCompiler = GeneralUtility::makeInstance(FormDataCompiler::class);
                        $nodeFactory = GeneralUtility::makeInstance(NodeFactory::class);

                        // Reset viewId - it should hold data of last entry only
                        $this->viewId = 0;

                        $formDataCompilerInput = [
                            'request' => $request,
                            'tableName' => $table,
                            'vanillaUid' => (int)$theUid,
                            'command' => $command,
                            'returnUrl' => $this->R_URI,
                        ];
                        if (is_array($this->overrideVals) && is_array($this->overrideVals[$table])) {
                            $formDataCompilerInput['overrideValues'] = $this->overrideVals[$table];
                        }
                        if (!empty($this->defVals) && is_array($this->defVals)) {
                            $formDataCompilerInput['defaultValues'] = $this->defVals;
                        }

                        $formData = $formDataCompiler->compile($formDataCompilerInput, GeneralUtility::makeInstance(TcaDatabaseRecord::class));

                        // Set this->viewId if possible
                        if ($command === 'new'
                            && $table !== 'pages'
                            && !empty($formData['parentPageRow']['uid'])
                        ) {
                            $this->viewId = $formData['parentPageRow']['uid'];
                        } else {
                            if ($table === 'pages') {
                                $this->viewId = $formData['databaseRow']['uid'];
                            } elseif (!empty($formData['parentPageRow']['uid'])) {
                                $this->viewId = $formData['parentPageRow']['uid'];
                            }
                        }

                        // Determine if delete button can be shown
                        $deleteAccess = false;
                        $permission = new Permission($formData['userPermissionOnPage']);
                        if ($formData['tableName'] === 'pages') {
                            $deleteAccess = $permission->get(Permission::PAGE_DELETE);
                        } else {
                            $deleteAccess = $permission->get(Permission::CONTENT_EDIT);
                        }

                        // Display "is-locked" message
                        if ($command === 'edit') {
                            $lockInfo = BackendUtility::isRecordLocked($table, $formData['databaseRow']['uid']);
                            if ($lockInfo) {
                                $view->addFlashMessage($lockInfo['msg'], '', ContextualFeedbackSeverity::WARNING);
                            }
                        }

                        // Record title
                        if (!$this->storeTitle) {
                            $this->storeTitle = htmlspecialchars($this->recTitle ?: ($formData['recordTitle'] ?? ''));
                        }

                        $this->elementsData[] = [
                            'table' => $table,
                            'uid' => $formData['databaseRow']['uid'],
                            'pid' => $formData['databaseRow']['pid'],
                            'cmd' => $command,
                            'deleteAccess' => $deleteAccess,
                        ];

                        if ($command !== 'new') {
                            BackendUtility::lockRecords($table, $formData['databaseRow']['uid'], $table === 'tt_content' ? $formData['databaseRow']['pid'] : 0);
                        }

                        // Set list if only specific fields should be rendered. This will trigger
                        // ListOfFieldsContainer instead of FullRecordContainer in OuterWrapContainer
                        if ($this->columnsOnly) {
                            if (is_array($this->columnsOnly)) {
                                $formData['fieldListToRender'] = $this->columnsOnly[$table];
                            } else {
                                $formData['fieldListToRender'] = $this->columnsOnly;
                            }
                        }

                        $formData['renderType'] = 'outerWrapContainer';
                        $formResult = $nodeFactory->create($formData)->render();

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
                                . ' name="data[' . htmlspecialchars($table) . '][' . htmlspecialchars($formData['databaseRow']['uid']) . '][pid]"'
                                . ' value="' . (int)$formData['databaseRow']['pid'] . '" />';
                        }

                        $editForm .= $html;
                    } catch (AccessDeniedException $e) {
                        $this->errorC++;
                        // Try to fetch error message from "recordInternals" be user object
                        // @todo: This construct should be logged and localized and de-uglified
                        $message = (!empty($beUser->errorMsg)) ? $beUser->errorMsg : $e->getMessage() . ' ' . $e->getCode();
                        $title = $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.noEditPermission');
                        $editForm .= $this->getInfobox($message, $title);
                    } catch (DatabaseRecordException | DatabaseRecordWorkspaceDeletePlaceholderException $e) {
                        $editForm .= $this->getInfobox($e->getMessage());
                    }
                } // End of for each uid
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
                '<div class="media">' .
                    '<div class="media-left">' .
                        '<span class="icon-emphasized">' .
                            $this->iconFactory->getIcon('actions-close', Icon::SIZE_SMALL)->render() .
                        '</span>' .
                    '</div>' .
                    '<div class="media-body">' .
                        ($title ? '<h4 class="callout-title">' . htmlspecialchars($title) . '</h4>' : '') .
                        '<div class="callout-body">' . htmlspecialchars($message) . '</div>' .
                    '</div>' .
                '</div>' .
            '</div>';
    }

    /**
     * Create the panel of buttons for submitting the form or otherwise perform operations.
     */
    protected function getButtons(ModuleTemplate $view, ServerRequestInterface $request): void
    {
        $buttonBar = $view->getDocHeaderComponent()->getButtonBar();
        if (!empty($this->firstEl)) {
            $record = BackendUtility::getRecord($this->firstEl['table'], $this->firstEl['uid']);
            $TCActrl = $GLOBALS['TCA'][$this->firstEl['table']]['ctrl'];

            $this->setIsSavedRecord();

            $sysLanguageUid = 0;
            if (
                $this->isSavedRecord
                && isset($TCActrl['languageField'], $record[$TCActrl['languageField']])
            ) {
                $sysLanguageUid = (int)$record[$TCActrl['languageField']];
            } elseif (isset($this->defVals['sys_language_uid'])) {
                $sysLanguageUid = (int)$this->defVals['sys_language_uid'];
            }

            $l18nParent = isset($TCActrl['transOrigPointerField'], $record[$TCActrl['transOrigPointerField']])
                ? (int)$record[$TCActrl['transOrigPointerField']]
                : 0;

            $this->setIsPageInFreeTranslationMode($record, $sysLanguageUid);

            $this->registerCloseButtonToButtonBar($buttonBar, ButtonBar::BUTTON_POSITION_LEFT, 1);

            // Show buttons when table is not read-only
            if (
                !$this->errorC
                && !($GLOBALS['TCA'][$this->firstEl['table']]['ctrl']['readOnly'] ?? false)
            ) {
                $this->registerSaveButtonToButtonBar($buttonBar, ButtonBar::BUTTON_POSITION_LEFT, 2);
                $this->registerViewButtonToButtonBar($buttonBar, ButtonBar::BUTTON_POSITION_LEFT, 3);
                if ($this->firstEl['cmd'] !== 'new') {
                    $this->registerNewButtonToButtonBar(
                        $buttonBar,
                        ButtonBar::BUTTON_POSITION_LEFT,
                        4,
                        $sysLanguageUid,
                        $l18nParent
                    );
                    $this->registerDuplicationButtonToButtonBar(
                        $buttonBar,
                        ButtonBar::BUTTON_POSITION_LEFT,
                        5,
                        $sysLanguageUid,
                        $l18nParent
                    );
                }
                $this->registerDeleteButtonToButtonBar($buttonBar, ButtonBar::BUTTON_POSITION_LEFT, 6, $request);
                $this->registerColumnsOnlyButtonToButtonBar($buttonBar, ButtonBar::BUTTON_POSITION_LEFT, 7);
                $this->registerHistoryButtonToButtonBar($buttonBar, ButtonBar::BUTTON_POSITION_RIGHT, 1);
            }
        }

        $this->registerOpenInNewWindowButtonToButtonBar($buttonBar, ButtonBar::BUTTON_POSITION_RIGHT, 2, $request);
        $this->registerShortcutButtonToButtonBar($buttonBar, ButtonBar::BUTTON_POSITION_RIGHT, 3, $request);
    }

    /**
     * Set the boolean to check if the record is saved
     */
    protected function setIsSavedRecord(): void
    {
        $this->isSavedRecord = (
            $this->firstEl['cmd'] !== 'new'
            && MathUtility::canBeInterpretedAsInteger($this->firstEl['uid'])
        );
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
     * Set the boolean to check if the page is in free translation mode
     */
    protected function setIsPageInFreeTranslationMode(?array $record, int $sysLanguageUid): void
    {
        if ($this->firstEl['table'] === 'tt_content') {
            if (!$this->isSavedRecord) {
                $this->isPageInFreeTranslationMode = $this->getFreeTranslationMode(
                    (int)($this->pageinfo['uid'] ?? 0),
                    (int)($this->defVals['colPos'] ?? 0),
                    $sysLanguageUid
                );
            } else {
                $this->isPageInFreeTranslationMode = $this->getFreeTranslationMode(
                    (int)($this->pageinfo['uid'] ?? 0),
                    (int)($record['colPos'] ?? 0),
                    $sysLanguageUid
                );
            }
        }
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
    protected function registerCloseButtonToButtonBar(ButtonBar $buttonBar, string $position, int $group): void
    {
        $closeButton = $buttonBar->makeLinkButton()
            ->setHref('#')
            ->setClasses('t3js-editform-close')
            ->setTitle($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:rm.closeDoc'))
            ->setShowLabelText(true)
            ->setIcon($this->iconFactory->getIcon('actions-close', Icon::SIZE_SMALL));
        $buttonBar->addButton($closeButton, $position, $group);
    }

    /**
     * Register the save button to the button bar
     */
    protected function registerSaveButtonToButtonBar(ButtonBar $buttonBar, string $position, int $group): void
    {
        $saveButton = $buttonBar->makeInputButton()
            ->setForm('EditDocumentController')
            ->setIcon($this->iconFactory->getIcon('actions-document-save', Icon::SIZE_SMALL))
            ->setName('_savedok')
            ->setShowLabelText(true)
            ->setTitle($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:rm.saveDoc'))
            ->setValue('1');
        $buttonBar->addButton($saveButton, $position, $group);
    }

    /**
     * Register the view button to the button bar
     */
    protected function registerViewButtonToButtonBar(ButtonBar $buttonBar, string $position, int $group): void
    {
        if ($this->viewId // Pid to show the record
            && !$this->noView // Passed parameter
            && !empty($this->firstEl['table']) // No table
            // @TODO: TsConfig option should change to viewDoc
            && $this->getTsConfigOption($this->firstEl['table'], 'saveDocView')
        ) {
            $pagesTSconfig = BackendUtility::getPagesTSconfig($this->pageinfo['uid'] ?? 0);
            if (isset($pagesTSconfig['TCEMAIN.']['preview.']['disableButtonForDokType'])) {
                $excludeDokTypes = GeneralUtility::intExplode(',', (string)$pagesTSconfig['TCEMAIN.']['preview.']['disableButtonForDokType'], true);
            } else {
                // exclude sys-folders, spacers and recycler by default
                $excludeDokTypes = [
                    PageRepository::DOKTYPE_RECYCLER,
                    PageRepository::DOKTYPE_SYSFOLDER,
                    PageRepository::DOKTYPE_SPACER,
                ];
            }
            if (
                !in_array((int)($this->pageinfo['doktype'] ?? 0), $excludeDokTypes, true)
                || isset($pagesTSconfig['TCEMAIN.']['preview.'][$this->firstEl['table'] . '.']['previewPageId'])
            ) {
                $previewPageId = $this->getPreviewPageId();
                $previewUrl = (string)PreviewUriBuilder::create($previewPageId)
                    ->withSection($this->getPreviewUrlAnchorSection())
                    ->withAdditionalQueryParameters($this->getPreviewUrlParameters($previewPageId))
                    ->buildUri();
                if ($previewUrl !== '') {
                    $viewButton = $buttonBar->makeLinkButton()
                        ->setHref($previewUrl)
                        ->setIcon($this->iconFactory->getIcon('actions-view', Icon::SIZE_SMALL))
                        ->setShowLabelText(true)
                        ->setTitle($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:rm.viewDoc'))
                        ->setClasses('t3js-editform-view');
                    if (!$this->isSavedRecord && $this->firstEl['table'] === 'pages') {
                        $viewButton->setDataAttributes(['is-new' => '']);
                    }
                    $buttonBar->addButton($viewButton, $position, $group);
                }
            }
        }
    }

    /**
     * Register the new button to the button bar
     */
    protected function registerNewButtonToButtonBar(ButtonBar $buttonBar, string $position, int $group, int $sysLanguageUid, int $l18nParent): void
    {
        if ($this->firstEl['table'] !== 'sys_file_metadata'
            && !empty($this->firstEl['table'])
            && (
                (
                    (
                        $this->isInconsistentLanguageHandlingAllowed()
                        || $this->isPageInFreeTranslationMode
                    )
                    && $this->firstEl['table'] === 'tt_content'
                )
                || (
                    $this->firstEl['table'] !== 'tt_content'
                    && (
                        $sysLanguageUid === 0
                        || $l18nParent === 0
                    )
                )
            )
            && $this->getTsConfigOption($this->firstEl['table'], 'saveDocNew')
        ) {
            $newButton = $buttonBar->makeLinkButton()
                ->setHref('#')
                ->setIcon($this->iconFactory->getIcon('actions-plus', Icon::SIZE_SMALL))
                ->setShowLabelText(true)
                ->setTitle($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:rm.newDoc'))
                ->setClasses('t3js-editform-new');
            if (!$this->isSavedRecord) {
                $newButton->setDataAttributes(['is-new' => '']);
            }
            $buttonBar->addButton($newButton, $position, $group);
        }
    }

    /**
     * Register the duplication button to the button bar
     */
    protected function registerDuplicationButtonToButtonBar(ButtonBar $buttonBar, string $position, int $group, int $sysLanguageUid, int $l18nParent): void
    {
        if ($this->firstEl['table'] !== 'sys_file_metadata'
            && !empty($this->firstEl['table'])
            && (
                (
                    (
                        $this->isInconsistentLanguageHandlingAllowed()
                        || $this->isPageInFreeTranslationMode
                    )
                    && $this->firstEl['table'] === 'tt_content'
                )
                || (
                    $this->firstEl['table'] !== 'tt_content'
                    && (
                        $sysLanguageUid === 0
                        || $l18nParent === 0
                    )
                )
            )
            && $this->getTsConfigOption($this->firstEl['table'], 'showDuplicate')
        ) {
            $duplicateButton = $buttonBar->makeLinkButton()
                ->setHref('#')
                ->setShowLabelText(true)
                ->setTitle($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:rm.duplicateDoc'))
                ->setIcon($this->iconFactory->getIcon('actions-document-duplicates-select', Icon::SIZE_SMALL))
                ->setClasses('t3js-editform-duplicate');
            if (!$this->isSavedRecord) {
                $duplicateButton->setDataAttributes(['is-new' => '']);
            }
            $buttonBar->addButton($duplicateButton, $position, $group);
        }
    }

    /**
     * Register the delete button to the button bar
     */
    protected function registerDeleteButtonToButtonBar(ButtonBar $buttonBar, string $position, int $group, ServerRequestInterface $request): void
    {
        if ($this->firstEl['deleteAccess']
            && !$this->getDisableDelete()
            && !$this->isRecordCurrentBackendUser()
            && $this->isSavedRecord
            && $this->isSingleRecordView()
        ) {
            $returnUrl = $this->retUrl;
            if ($this->firstEl['table'] === 'pages') {
                // The below is a hack to replace the return url with an url to the current module on id=0. Otherwise,
                // this might lead to empty views, since the current id is the page, which is about to be deleted.
                $parsedUrl = parse_url($returnUrl);
                $routePath = str_replace($this->backendEntryPointResolver->getPathFromRequest($request), '', $parsedUrl['path'] ?? '');
                parse_str($parsedUrl['query'] ?? '', $queryParams);
                if ($routePath
                    && isset($queryParams['id'])
                    && (string)$this->firstEl['uid'] === (string)$queryParams['id']
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
                $this->firstEl['table'],
                (int)$this->firstEl['uid']
            );
            $referenceCountMessage = BackendUtility::referenceCount(
                $this->firstEl['table'],
                (string)(int)$this->firstEl['uid'],
                $this->getLanguageService()->sL(
                    'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.referencesToRecord'
                ),
                (string)$numberOfReferences
            );
            $translationCountMessage = BackendUtility::translationCount(
                $this->firstEl['table'],
                (string)(int)$this->firstEl['uid'],
                $this->getLanguageService()->sL(
                    'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.translationsOfRecord'
                )
            );

            $deleteUrl = (string)$this->uriBuilder->buildUriFromRoute('tce_db', [
                'cmd' => [
                    $this->firstEl['table'] => [
                        $this->firstEl['uid'] => [
                            'delete' => '1',
                        ],
                    ],
                ],
                'redirect' => $returnUrl,
            ]);

            $recordInfo = $this->storeTitle;
            if ($this->getBackendUser()->shallDisplayDebugInformation()) {
                $recordInfo .= ' [' . $this->firstEl['table'] . ':' . $this->firstEl['uid'] . ']';
            }

            $deleteButton = $buttonBar->makeLinkButton()
                ->setClasses('t3js-editform-delete-record')
                ->setDataAttributes([
                    'uid' => $this->firstEl['uid'],
                    'table' => $this->firstEl['table'],
                    'record-info' => trim($recordInfo),
                    'reference-count-message' => $referenceCountMessage,
                    'translation-count-message' => $translationCountMessage,
                ])
                ->setHref($deleteUrl)
                ->setIcon($this->iconFactory->getIcon('actions-edit-delete', Icon::SIZE_SMALL))
                ->setShowLabelText(true)
                ->setTitle($this->getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang_alt_doc.xlf:deleteItem'));
            $buttonBar->addButton($deleteButton, $position, $group);
        }
    }

    /**
     * Register the history button to the button bar
     */
    protected function registerHistoryButtonToButtonBar(ButtonBar $buttonBar, string $position, int $group): void
    {
        if ($this->isSingleRecordView()
            && !empty($this->firstEl['table'])
            && $this->getTsConfigOption($this->firstEl['table'], 'showHistory')
        ) {
            $historyUrl = (string)$this->uriBuilder->buildUriFromRoute('record_history', [
                'element' => $this->firstEl['table'] . ':' . $this->firstEl['uid'],
                'returnUrl' => $this->R_URI,
            ]);
            $historyButton = $buttonBar->makeLinkButton()
                ->setHref($historyUrl)
                ->setTitle($this->getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang_alt_doc.xlf:recordHistory'))
                ->setIcon($this->iconFactory->getIcon('actions-document-history-open', Icon::SIZE_SMALL));
            $buttonBar->addButton($historyButton, $position, $group);
        }
    }

    /**
     * Register the columns only button to the button bar
     */
    protected function registerColumnsOnlyButtonToButtonBar(ButtonBar $buttonBar, string $position, int $group): void
    {
        if ($this->columnsOnly
            && $this->isSingleRecordView()
        ) {
            $columnsOnlyButton = $buttonBar->makeLinkButton()
                ->setHref($this->R_URI . '&columnsOnly=')
                ->setTitle($this->getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang_alt_doc.xlf:editWholeRecord'))
                ->setShowLabelText(true)
                ->setIcon($this->iconFactory->getIcon('actions-open', Icon::SIZE_SMALL));

            $buttonBar->addButton($columnsOnlyButton, $position, $group);
        }
    }

    /**
     * Register the open in new window button to the button bar
     */
    protected function registerOpenInNewWindowButtonToButtonBar(ButtonBar $buttonBar, string $position, int $group, ServerRequestInterface $request): void
    {
        $closeUrl = $this->getCloseUrl();
        if ($this->returnUrl !== $closeUrl) {
            // Generate a URL to the current edit form
            $arguments = $this->getUrlQueryParamsForCurrentRequest($request);
            $arguments['returnUrl'] = $closeUrl;
            $requestUri = (string)$this->uriBuilder->buildUriFromRoute('record_edit', $arguments);
            $openInNewWindowButton = $buttonBar
                ->makeLinkButton()
                ->setHref('#')
                ->setTitle($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.openInNewWindow'))
                ->setIcon($this->iconFactory->getIcon('actions-window-open', Icon::SIZE_SMALL))
                ->setDataAttributes([
                    'dispatch-action' => 'TYPO3.WindowManager.localOpen',
                    'dispatch-args' => GeneralUtility::jsonEncodeForHtmlAttribute([
                        $requestUri,
                        true, // switchFocus
                        md5($this->R_URI), // windowName,
                        'width=670,height=500,status=0,menubar=0,scrollbars=1,resizable=1', // windowFeatures
                    ]),
                ]);
            $buttonBar->addButton($openInNewWindowButton, $position, $group);
        }
    }

    /**
     * Register the shortcut button to the button bar
     */
    protected function registerShortcutButtonToButtonBar(ButtonBar $buttonBar, string $position, int $group, ServerRequestInterface $request): void
    {
        if ($this->returnUrl !== $this->getCloseUrl()) {
            $arguments = $this->getUrlQueryParamsForCurrentRequest($request);
            $shortCutButton = $buttonBar->makeShortcutButton()
                ->setRouteIdentifier('record_edit')
                ->setDisplayName($this->getShortcutTitle($request))
                ->setArguments($arguments);
            $buttonBar->addButton($shortCutButton, $position, $group);
        }
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
            'noView',
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
                    $GLOBALS['TCA']['tt_content']['ctrl']['transOrigPointerField'],
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
                    $GLOBALS['TCA']['tt_content']['ctrl']['transOrigPointerField'],
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
        $languageField = $GLOBALS['TCA']['tt_content']['ctrl']['languageField'];
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
                    $languageField,
                    $queryBuilder->createNamedParameter($language, Connection::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    'colPos',
                    $queryBuilder->createNamedParameter($column, Connection::PARAM_INT)
                )
            );
    }

    /**
     * Put together the various elements (buttons, selectors, form) into a table
     *
     * @param string $editForm HTML form.
     * @return string Composite HTML
     */
    protected function compileForm(string $editForm): string
    {
        $formContent = '
            <form
                action="' . htmlspecialchars($this->R_URI) . '"
                method="post"
                enctype="multipart/form-data"
                name="editform"
                id="EditDocumentController"
            >
            ' . $editForm . '
            <input type="hidden" name="returnUrl" value="' . htmlspecialchars($this->retUrl) . '" />
            <input type="hidden" name="popViewId" value="' . htmlspecialchars((string)$this->viewId) . '" />
            <input type="hidden" name="closeDoc" value="0" />
            <input type="hidden" name="doSave" value="0" />';
        if ($this->returnNewPageId) {
            $formContent .= '<input type="hidden" name="returnNewPageId" value="1" />';
        }
        return $formContent;
    }

    /**
     * Update expanded/collapsed states on new inline records if any within backendUser->uc.
     *
     * @param array|null $uc The uc array to be processed and saved - uc[inlineView][...]
     * @param DataHandler $dataHandler Instance of DataHandler that saved data before
     */
    protected function updateInlineView(?array $uc, DataHandler $dataHandler): void
    {
        $backendUser = $this->getBackendUser();
        if (!isset($uc['inlineView']) || !is_array($uc['inlineView'])) {
            return;
        }
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
        if ($this->firstEl['table'] === 'sys_file_metadata') {
            $row = BackendUtility::getRecord('sys_file_metadata', $this->firstEl['uid'], 'sys_language_uid');
            $languageUid = $row['sys_language_uid'];
            if ($languageUid === 0) {
                $disableDelete = true;
            }
        } else {
            $disableDelete = (bool)$this->getTsConfigOption($this->firstEl['table'] ?? '', 'disableDelete');
        }
        return $disableDelete;
    }

    /**
     * Return true in case the current record is the current backend user
     */
    protected function isRecordCurrentBackendUser(): bool
    {
        $backendUser = $this->getBackendUser();
        return $this->firstEl['table'] === 'be_users'
            && (int)($this->firstEl['uid'] ?? 0) === (int)$backendUser->user[$backendUser->userid_column];
    }

    /**
     * Returns the URL (usually for the "returnUrl") which closes the current window.
     * Used when editing a record in a popup.
     */
    protected function getCloseUrl(): string
    {
        return PathUtility::getPublicResourceWebPath('EXT:backend/Resources/Public/Html/Close.html');
    }

    /**
     * Make selector box for creating new translation for a record or switching to edit the record
     * in an existing language. Displays only languages which are available for the current page.
     *
     * @param string $table Table name
     * @param int $uid Uid for which to create a new language
     * @param int|null $pid Pid of the record
     */
    protected function languageSwitch(ModuleTemplate $view, string $table, int $uid, ?int $pid = null)
    {
        $backendUser = $this->getBackendUser();
        $languageField = $GLOBALS['TCA'][$table]['ctrl']['languageField'] ?? '';
        $transOrigPointerField = $GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField'] ?? '';
        // Table editable and activated for languages?
        if ($backendUser->check('tables_modify', $table)
            && $languageField
            && $transOrigPointerField
        ) {
            if ($pid === null) {
                $row = BackendUtility::getRecord($table, $uid, 'pid');
                $pid = $row['pid'];
            }
            // Get all available languages for the page
            // If editing a page, the translations of the current UID need to be fetched
            if ($table === 'pages') {
                $row = BackendUtility::getRecord($table, $uid, $GLOBALS['TCA']['pages']['ctrl']['transOrigPointerField']);
                // Ensure the check is always done against the default language page
                $availableLanguages = $this->getLanguages(
                    (int)$row[$GLOBALS['TCA']['pages']['ctrl']['transOrigPointerField']] ?: $uid,
                    $table
                );
            } else {
                $availableLanguages = $this->getLanguages((int)$pid, $table);
            }
            // Remove default language, if user does not have access. This is necessary, since
            // the default language is always added when fetching the system languages (#88504).
            if (isset($availableLanguages[0]) && !$this->getBackendUser()->checkLanguageAccess(0)) {
                unset($availableLanguages[0]);
            }
            // Page available in other languages than default language?
            if (count($availableLanguages) > 1) {
                $rowsByLang = [];
                $fetchFields = 'uid,' . $languageField . ',' . $transOrigPointerField;
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
                        $result = $queryBuilder->select(...GeneralUtility::trimExplode(',', $fetchFields, true))
                            ->from($table)
                            ->where(
                                $queryBuilder->expr()->eq(
                                    'pid',
                                    $queryBuilder->createNamedParameter($pid, Connection::PARAM_INT)
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
                            if ($backendUser->workspace !== 0 && BackendUtility::isTableWorkspaceEnabled($table)) {
                                $workspaceVersion = BackendUtility::getWorkspaceVersionOfRecord($backendUser->workspace, $table, $row['uid'], 'uid,t3ver_state');
                                if (!empty($workspaceVersion)) {
                                    $versionState = VersionState::cast($workspaceVersion['t3ver_state']);
                                    if ($versionState->equals(VersionState::DELETE_PLACEHOLDER)) {
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
                    $languageMenu = $view->getDocHeaderComponent()->getMenuRegistry()->makeMenu();
                    $languageMenu->setIdentifier('_langSelector');
                    foreach ($availableLanguages as $languageId => $language) {
                        $selectorOptionLabel = $language['title'];
                        // Create url for creating a localized record
                        $addOption = true;
                        $href = '';
                        if (!isset($rowsByLang[$languageId])) {
                            // Translation in this language does not exist
                            if (!isset($rowsByLang[0]['uid'])) {
                                // Don't add option since no default row to localize from exists
                                // TODO: Actually tt_content is able to localize from another l10n_source then L=0.
                                //       This however is currently only possible via the translation wizard.
                                $addOption = false;
                            } else {
                                // Build the link to add the localization
                                $selectorOptionLabel .= ' [' . htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.new')) . ']';
                                $href = (string)$this->uriBuilder->buildUriFromRoute(
                                    'tce_db',
                                    [
                                        'cmd' => [
                                            $table => [
                                                $rowsByLang[0]['uid'] => [
                                                    'localize' => $languageId,
                                                ],
                                            ],
                                        ],
                                        'redirect' => (string)$this->uriBuilder->buildUriFromRoute(
                                            'record_edit',
                                            [
                                                'justLocalized' => $table . ':' . $rowsByLang[0]['uid'] . ':' . $languageId,
                                                'returnUrl' => $this->retUrl,
                                            ]
                                        ),
                                    ]
                                );
                            }
                        } else {
                            $params = [
                                'edit[' . $table . '][' . $rowsByLang[$languageId]['uid'] . ']' => 'edit',
                                'returnUrl' => $this->retUrl,
                            ];
                            if ($table === 'pages') {
                                // Disallow manual adjustment of the language field for pages
                                $params['overrideVals'] = [
                                    'pages' => [
                                        'sys_language_uid' => $languageId,
                                    ],
                                ];
                            }
                            $href = (string)$this->uriBuilder->buildUriFromRoute('record_edit', $params);
                        }
                        if ($addOption && !in_array($languageId, $noAddOption, true)) {
                            $menuItem = $languageMenu->makeMenuItem()
                                ->setTitle($selectorOptionLabel)
                                ->setHref($href);
                            if ($languageId === $currentLanguage) {
                                $menuItem->setActive(true);
                            }
                            $languageMenu->addMenuItem($menuItem);
                        }
                    }
                    $view->getDocHeaderComponent()->getMenuRegistry()->addMenu($languageMenu);
                }
            }
        }
    }

    /**
     * Redirects to FormEngine with new parameters to edit a just created localized record.
     */
    protected function localizationRedirect(ServerRequestInterface $request): ?ResponseInterface
    {
        $justLocalized = $request->getQueryParams()['justLocalized'] ?? null;
        if (empty($justLocalized)) {
            return null;
        }

        [$table, $origUid, $language] = explode(':', $justLocalized);

        if ($GLOBALS['TCA'][$table]
            && $GLOBALS['TCA'][$table]['ctrl']['languageField']
            && $GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField']
        ) {
            $parsedBody = $request->getParsedBody();
            $queryParams = $request->getQueryParams();
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
            $queryBuilder->getRestrictions()
                ->removeAll()
                ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
                ->add(GeneralUtility::makeInstance(WorkspaceRestriction::class, $this->getBackendUser()->workspace));
            $localizedRecord = $queryBuilder->select('uid')
                ->from($table)
                ->where(
                    $queryBuilder->expr()->eq(
                        $GLOBALS['TCA'][$table]['ctrl']['languageField'],
                        $queryBuilder->createNamedParameter($language, Connection::PARAM_INT)
                    ),
                    $queryBuilder->expr()->eq(
                        $GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField'],
                        $queryBuilder->createNamedParameter($origUid, Connection::PARAM_INT)
                    )
                )
                ->executeQuery()
                ->fetchAssociative();
            $returnUrl = $parsedBody['returnUrl'] ?? $queryParams['returnUrl'] ?? '';
            if (is_array($localizedRecord)) {
                // Create redirect response to self to edit just created record
                return new RedirectResponse(
                    (string)$this->uriBuilder->buildUriFromRoute(
                        'record_edit',
                        [
                            'edit[' . $table . '][' . $localizedRecord['uid'] . ']' => 'edit',
                            'returnUrl' => GeneralUtility::sanitizeLocalUrl($returnUrl),
                        ]
                    ),
                    303
                );
            }
        }
        return null;
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
            static fn($language) => (int)$language['uid'] !== -1
        );
        if ($table !== 'pages' && $id > 0) {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
            $queryBuilder->getRestrictions()->removeAll()
                ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
                ->add(GeneralUtility::makeInstance(WorkspaceRestriction::class, $this->getBackendUser()->workspace));
            $statement = $queryBuilder->select('uid', $GLOBALS['TCA']['pages']['ctrl']['languageField'])
                ->from('pages')
                ->where(
                    $queryBuilder->expr()->eq(
                        $GLOBALS['TCA']['pages']['ctrl']['transOrigPointerField'],
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
                $languageId = (int)$row[$GLOBALS['TCA']['pages']['ctrl']['languageField']];
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
    protected function fixWSversioningInEditConf(?array $mapArray = null): void
    {
        if (!is_array($this->editconf)) {
            return;
        }
        foreach ($this->editconf as $table => $conf) {
            if (is_array($conf) && $GLOBALS['TCA'][$table]) {
                // Traverse the keys/comments of each table (keys can be a comma list of uids)
                $newConf = [];
                foreach ($conf as $cKey => $cmd) {
                    if ($cmd === 'edit') {
                        // Traverse the ids:
                        $ids = GeneralUtility::trimExplode(',', (string)$cKey, true);
                        foreach ($ids as $idKey => $theUid) {
                            if (is_array($mapArray)) {
                                if ($mapArray[$table][$theUid] ?? false) {
                                    $ids[$idKey] = $mapArray[$table][$theUid];
                                }
                            } else {
                                // Default, look for versions in workspace for record:
                                $calcPRec = $this->getRecordForEdit((string)$table, (int)$theUid);
                                if (is_array($calcPRec)) {
                                    // Setting UID again if it had changed, eg. due to workspace versioning.
                                    $ids[$idKey] = $calcPRec['uid'];
                                }
                            }
                        }
                        // Add the possibly manipulated IDs to the new-build newConf array:
                        $newConf[implode(',', $ids)] = $cmd;
                    } else {
                        $newConf[$cKey] = $cmd;
                    }
                }
                // Store the new conf array:
                $this->editconf[$table] = $newConf;
            }
        }
    }

    /**
     * Get record for editing.
     *
     * @param string $table Table name
     * @param int $theUid Record UID
     * @return array|false Returns record to edit, false if none
     */
    protected function getRecordForEdit(string $table, int $theUid): array|bool
    {
        $tableSupportsVersioning = BackendUtility::isTableWorkspaceEnabled($table);
        // Fetch requested record:
        $reqRecord = BackendUtility::getRecord($table, $theUid, 'uid,pid' . ($tableSupportsVersioning ? ',t3ver_oid' : ''));
        if (is_array($reqRecord)) {
            // If workspace is OFFLINE:
            if ($this->getBackendUser()->workspace !== 0) {
                // Check for versioning support of the table:
                if ($tableSupportsVersioning) {
                    // If the record is already a version of "something" pass it by.
                    if ($reqRecord['t3ver_oid'] > 0 || (int)($reqRecord['t3ver_state'] ?? 0) === VersionState::NEW_PLACEHOLDER) {
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
     * Populates the variables $this->storeArray, $this->storeUrl, $this->storeUrlMd5
     * to prepare 'open documents' urls
     */
    protected function compileStoreData(ServerRequestInterface $request): void
    {
        $queryParams = $request->getQueryParams();
        $parsedBody = $request->getParsedBody();

        foreach (['edit', 'defVals', 'overrideVals' , 'columnsOnly' , 'noView'] as $key) {
            if (isset($this->R_URL_getvars[$key])) {
                $this->storeArray[$key] = $this->R_URL_getvars[$key];
            } else {
                $this->storeArray[$key] = $parsedBody[$key] ?? $queryParams[$key] ?? null;
            }
        }

        $this->storeUrl = HttpUtility::buildQueryString($this->storeArray, '&');
        $this->storeUrlMd5 = md5($this->storeUrl);
    }

    /**
     * Get a TSConfig 'option.' array, possibly for a specific table.
     */
    protected function getTsConfigOption(string $table, string $key): string
    {
        return trim((string)(
            $this->getBackendUser()->getTSConfig()['options.'][$key . '.'][$table]
            ?? $this->getBackendUser()->getTSConfig()['options.'][$key]
            ?? ''
        ));
    }

    /**
     * Handling the closing of a document
     * The argument $mode can be one of this values:
     * - 0/1 will redirect to $this->retUrl [self::DOCUMENT_CLOSE_MODE_DEFAULT || self::DOCUMENT_CLOSE_MODE_REDIRECT]
     * - 3 will clear the docHandler (thus closing all documents) [self::DOCUMENT_CLOSE_MODE_CLEAR_ALL]
     * - 4 will do no redirect [self::DOCUMENT_CLOSE_MODE_NO_REDIRECT]
     * - other values will call setDocument with ->retUrl
     *
     * @param int $mode the close mode: one of self::DOCUMENT_CLOSE_MODE_*
     * @param ServerRequestInterface $request Incoming request
     * @return ResponseInterface|null Redirect response if needed
     */
    protected function closeDocument(int $mode, ServerRequestInterface $request): ?ResponseInterface
    {
        $setupArr = [];
        // If current document is found in docHandler,
        // then unset it, possibly unset it ALL and finally, write it to the session data
        if (isset($this->docHandler[$this->storeUrlMd5])) {
            // add the closing document to the recent documents
            $recentDocs = $this->getBackendUser()->getModuleData('opendocs::recent');
            if (!is_array($recentDocs)) {
                $recentDocs = [];
            }
            $closedDoc = $this->docHandler[$this->storeUrlMd5];
            $recentDocs = array_merge([$this->storeUrlMd5 => $closedDoc], $recentDocs);
            if (count($recentDocs) > 8) {
                $recentDocs = array_slice($recentDocs, 0, 8);
            }
            // remove it from the list of the open documents
            unset($this->docHandler[$this->storeUrlMd5]);
            if ($mode === self::DOCUMENT_CLOSE_MODE_CLEAR_ALL) {
                $recentDocs = array_merge($this->docHandler, $recentDocs);
                $this->docHandler = [];
            }
            $this->getBackendUser()->pushModuleData('opendocs::recent', $recentDocs);
            $this->getBackendUser()->pushModuleData('FormEngine', [$this->docHandler, $this->docDat[1]]);
            BackendUtility::setUpdateSignal('OpendocsController::updateNumber', count($this->docHandler));
        }
        if ($mode === self::DOCUMENT_CLOSE_MODE_NO_REDIRECT) {
            return null;
        }
        // If ->returnEditConf is set, then add the current content of editconf to the ->retUrl variable: used by
        // other scripts, like wizard_add, to know which records was created or so...
        if ($this->returnEditConf && $this->retUrl != (string)$this->uriBuilder->buildUriFromRoute('dummy')) {
            $this->retUrl .= '&returnEditConf=' . rawurlencode((string)json_encode($this->editconf));
        }
        // If mode is NOT set (means 0) OR set to 1, then make a header location redirect to $this->retUrl
        if ($mode === self::DOCUMENT_CLOSE_MODE_DEFAULT || $mode === self::DOCUMENT_CLOSE_MODE_REDIRECT) {
            return new RedirectResponse($this->retUrl, 303);
        }
        if ($this->retUrl === '') {
            return null;
        }
        $retUrl = (string)$this->returnUrl;
        if (is_array($this->docHandler) && !empty($this->docHandler)) {
            if (!empty($setupArr[2])) {
                $sParts = parse_url($request->getAttribute('normalizedParams')->getRequestUri());
                $retUrl = $sParts['path'] . '?' . $setupArr[2] . '&returnUrl=' . rawurlencode($retUrl);
            }
        }
        return new RedirectResponse($retUrl, 303);
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
        $tableTitle = $languageService->sL($GLOBALS['TCA'][$table]['ctrl']['title'] ?? '') ?: $table;
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
