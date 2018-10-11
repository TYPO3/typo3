<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Backend\Controller;

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
use TYPO3\CMS\Backend\Form\Exception\AccessDeniedException;
use TYPO3\CMS\Backend\Form\Exception\DatabaseRecordException;
use TYPO3\CMS\Backend\Form\FormDataCompiler;
use TYPO3\CMS\Backend\Form\FormDataGroup\TcaDatabaseRecord;
use TYPO3\CMS\Backend\Form\FormResultCompiler;
use TYPO3\CMS\Backend\Form\NodeFactory;
use TYPO3\CMS\Backend\Form\Utility\FormEngineUtility;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Compatibility\PublicMethodDeprecationTrait;
use TYPO3\CMS\Core\Compatibility\PublicPropertyDeprecationTrait;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\BackendWorkspaceRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\ReferenceIndex;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Routing\SiteMatcher;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\HttpUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Extbase\SignalSlot\Dispatcher;
use TYPO3\CMS\Frontend\Page\CacheHashCalculator;
use TYPO3\CMS\Frontend\Page\PageRepository;

/**
 * Main backend controller almost always used if some database record is edited in the backend.
 *
 * Main job of this controller is to evaluate and sanitize $request parameters,
 * call the DataHandler if records should be created or updated and
 * execute FormEngine for record rendering.
 */
class EditDocumentController
{
    use PublicMethodDeprecationTrait;
    use PublicPropertyDeprecationTrait;

    /**
     * @deprecated since TYPO3 v9. These constants will be set to protected in TYPO3 v10.0
     */
    public const DOCUMENT_CLOSE_MODE_DEFAULT = 0;
    public const DOCUMENT_CLOSE_MODE_REDIRECT = 1; // works like DOCUMENT_CLOSE_MODE_DEFAULT
    public const DOCUMENT_CLOSE_MODE_CLEAR_ALL = 3;
    public const DOCUMENT_CLOSE_MODE_NO_REDIRECT = 4;

    /**
     * @var array
     */
    private $deprecatedPublicMethods = [
        'makeEditForm' => 'Using EditDocumentController::makeEditForm() is deprecated and will not be possible anymore in TYPO3 v10.0.',
        'compileForm' => 'Using EditDocumentController::compileForm() is deprecated and will not be possible anymore in TYPO3 v10.0.',
        'languageSwitch' => 'Using EditDocumentController::languageSwitch() is deprecated and will not be possible anymore in TYPO3 v10.0.',
        'getLanguages' => 'Using EditDocumentController::getLanguages() is deprecated and will not be possible anymore in TYPO3 v10.0.',
        'fixWSversioningInEditConf' => 'Using EditDocumentController::fixWSversioningInEditConf() is deprecated and will not be possible anymore in TYPO3 v10.0.',
        'getRecordForEdit' => 'Using EditDocumentController::getRecordForEdit() is deprecated and will not be possible anymore in TYPO3 v10.0.',
    ];

    /**
     * Properties which have been moved to protected status from public
     *
     * @var array
     */
    private $deprecatedPublicProperties = [
        'editconf' => 'Using $editconf of class EditDocumentTemplate from the outside is discouraged, as this variable is only used for internal storage.',
        'defVals' => 'Using $defVals of class EditDocumentTemplate from the outside is discouraged, as this variable is only used for internal storage.',
        'overrideVals' => 'Using $overrideVals of class EditDocumentTemplate from the outside is discouraged, as this variable is only used for internal storage.',
        'columnsOnly' => 'Using $columnsOnly of class EditDocumentTemplate from the outside is discouraged, as this variable is only used for internal storage.',
        'returnUrl' => 'Using $returnUrl of class EditDocumentTemplate from the outside is discouraged, as this variable is only used for internal storage.',
        'closeDoc' => 'Using $closeDoc of class EditDocumentTemplate from the outside is discouraged, as this variable is only used for internal storage.',
        'doSave' => 'Using $doSave of class EditDocumentTemplate from the outside is discouraged, as this variable is only used for internal storage.',
        'returnEditConf' => 'Using $returnEditConf of class EditDocumentTemplate from the outside is discouraged, as this variable is only used for internal storage.',
        'uc' => 'Using $uc of class EditDocumentTemplate from the outside is discouraged, as this variable is only used for internal storage.',
        'retUrl' => 'Using $retUrl of class EditDocumentTemplate from the outside is discouraged, as this variable is only used for internal storage.',
        'R_URL_parts' => 'Using $R_URL_parts of class EditDocumentTemplate from the outside is discouraged, as this variable is only used for internal storage.',
        'R_URL_getvars' => 'Using $R_URL_getvars of class EditDocumentTemplate from the outside is discouraged, as this variable is only used for internal storage.',
        'storeArray' => 'Using $storeArray of class EditDocumentTemplate from the outside is discouraged, as this variable is only used for internal storage.',
        'storeUrl' => 'Using $storeUrl of class EditDocumentTemplate from the outside is discouraged, as this variable is only used for internal storage.',
        'storeUrlMd5' => 'Using $storeUrlMd5 of class EditDocumentTemplate from the outside is discouraged, as this variable is only used for internal storage.',
        'docDat' => 'Using $docDat of class EditDocumentTemplate from the outside is discouraged, as this variable is only used for internal storage.',
        'docHandler' => 'Using $docHandler of class EditDocumentTemplate from the outside is discouraged, as this variable is only used for internal storage.',
        'cmd' => 'Using $cmd of class EditDocumentTemplate from the outside is discouraged, as this variable is only used for internal storage.',
        'mirror' => 'Using $mirror of class EditDocumentTemplate from the outside is discouraged, as this variable is only used for internal storage.',
        'cacheCmd' => 'Using $cacheCmd of class EditDocumentTemplate from the outside is discouraged, the variable will be removed.',
        'redirect' => 'Using $redirect of class EditDocumentTemplate from the outside is discouraged, the variable will be removed.',
        'returnNewPageId' => 'Using $returnNewPageId of class EditDocumentTemplate from the outside is discouraged, as this variable is only used for internal storage.',
        'popViewId' => 'Using $popViewId of class EditDocumentTemplate from the outside is discouraged, as this variable is only used for internal storage.',
        'popViewId_addParams' => 'Using $popViewId_addParams of class EditDocumentTemplate from the outside is discouraged, the variable will be removed.',
        'viewUrl' => 'Using $viewUrl of class EditDocumentTemplate from the outside is discouraged, as this variable is only used for internal storage.',
        'recTitle' => 'Using $recTitle of class EditDocumentTemplate from the outside is discouraged, as this variable is only used for internal storage.',
        'noView' => 'Using $noView of class EditDocumentTemplate from the outside is discouraged, as this variable is only used for internal storage.',
        'MCONF' => 'Using $MCONF of class EditDocumentTemplate from the outside is discouraged, the variable will be removed.',
        'doc' => 'Using $doc of class EditDocumentTemplate from the outside is discouraged, the variable will be removed.',
        'perms_clause' => 'Using $perms_clause of class EditDocumentTemplate from the outside is discouraged, as this variable is only used for internal storage.',
        'template' => 'Using $template of class EditDocumentTemplate from the outside is discouraged, the variable will be removed.',
        'content' => 'Using $content of class EditDocumentTemplate from the outside is discouraged, the variable will be removed.',
        'R_URI' => 'Using $R_URI of class EditDocumentTemplate from the outside is discouraged, as this variable is only used for internal storage.',
        'pageinfo' => 'Using $pageinfo of class EditDocumentTemplate from the outside is discouraged, as this variable is only used for internal storage.',
        'storeTitle' => 'Using $storeTitle of class EditDocumentTemplate from the outside is discouraged, as this variable is only used for internal storage.',
        'firstEl' => 'Using $firstEl of class EditDocumentTemplate from the outside is discouraged, as this variable is only used for internal storage.',
        'errorC' => 'Using $errorC of class EditDocumentTemplate from the outside is discouraged, as this variable is only used for internal storage.',
        'newC' => 'Using $newC of class EditDocumentTemplate from the outside is discouraged, as this variable is only used for internal storage.',
        'viewId' => 'Using $viewId of class EditDocumentTemplate from the outside is discouraged, as this variable is only used for internal storage.',
        'viewId_addParams' => 'Using $viewId_addParams of class EditDocumentTemplate from the outside is discouraged, as this variable is only used for internal storage.',
        'modTSconfig' => 'Using $modTSconfig of class EditDocumentTemplate from the outside is discouraged, the variable will be removed.',
        'dontStoreDocumentRef' => 'Using $dontStoreDocumentRef of class EditDocumentTemplate from the outside is discouraged, as this variable is only used for internal storage.',
    ];

    /**
     * An array looking approx like [tablename][list-of-ids]=command, eg. "&edit[pages][123]=edit".
     *
     * @see \TYPO3\CMS\Backend\Utility\BackendUtility::editOnClick()
     * @var array
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
     *
     * @var int
     */
    protected $closeDoc;

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
     * @todo: Will be set protected later, still used by ConditionMatcher
     * @internal Will be removed / protected in TYPO3 v10.0 without further notice
     */
    public $data;

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
     * @var string
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0, unused
     */
    protected $cacheCmd;

    /**
     * @var string
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0, unused
     */
    protected $redirect;

    /**
     * Boolean: If set, then the GET var "&id=" will be added to the
     * retUrl string so that the NEW id of something is returned to the script calling the form.
     *
     * @var bool
     */
    protected $returnNewPageId = false;

    /**
     * Updated values for backendUser->uc. Used for new inline records to mark them
     * as expanded: uc[inlineView][...]
     *
     * @var array|null
     */
    protected $uc;

    /**
     * ID for displaying the page in the frontend, "save and view"
     *
     * @var int
     */
    protected $popViewId;

    /**
     * @var string
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0, unused
     */
    protected $popViewId_addParams;

    /**
     * Alternative URL for viewing the frontend pages.
     *
     * @var string
     */
    protected $viewUrl;

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
     * Workspace used for the editing action.
     *
     * @var string|null
     */
    protected $workspace;

    /**
     * @var \TYPO3\CMS\Backend\Template\DocumentTemplate
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0, unused
     */
    protected $doc;

    /**
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0, unused
     */
    protected $template;

    /**
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0, unused
     */
    protected $content;

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
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0, unused
     */
    protected $MCONF;

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
     * @todo: Will be set protected later, still used by ConditionMatcher
     * @internal Will be removed / protected in TYPO3 v10.0 without further notice
     */
    public $elementsData;

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
     * Counter, used to count the number of new record forms displayed
     *
     * @var int
     */
    protected $newC;

    /**
     * Is set to the pid value of the last shown record - thus indicating which page to
     * show when clicking the SAVE/VIEW button
     *
     * @var int
     */
    protected $viewId;

    /**
     * Is set to additional parameters (like "&L=xxx") if the record supports it.
     *
     * @var string
     */
    protected $viewId_addParams;

    /**
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0, unused
     */
    protected $modTSconfig = [];

    /**
     * @var FormResultCompiler
     */
    protected $formResultCompiler;

    /**
     * Used internally to disable the storage of the document reference (eg. new records)
     *
     * @var bool
     */
    protected $dontStoreDocumentRef = 0;

    /**
     * @var \TYPO3\CMS\Extbase\SignalSlot\Dispatcher
     */
    protected $signalSlotDispatcher;

    /**
     * Stores information needed to preview the currently saved record
     *
     * @var array
     */
    protected $previewData = [];

    /**
     * ModuleTemplate object
     *
     * @var ModuleTemplate
     */
    protected $moduleTemplate;

    /**
     * Check if a record has been saved
     *
     * @var bool
     */
    protected $isSavedRecord;

    /**
     * Check if a page in free translation mode
     *
     * @var bool
     */
    protected $isPageInFreeTranslationMode = false;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->moduleTemplate = GeneralUtility::makeInstance(ModuleTemplate::class);
        $this->moduleTemplate->setUiBlock(true);
        // @todo Used by TYPO3\CMS\Backend\Configuration\TypoScript\ConditionMatching
        $GLOBALS['SOBE'] = $this;
        $this->getLanguageService()->includeLLFile('EXT:backend/Resources/Private/Language/locallang_alt_doc.xlf');
    }

    /**
     * Main dispatcher entry method registered as "record_edit" end point
     *
     * @param ServerRequestInterface $request the current request
     * @return ResponseInterface the response with the content
     */
    public function mainAction(ServerRequestInterface $request): ResponseInterface
    {
        // Unlock all locked records
        BackendUtility::lockRecords();
        if ($response = $this->preInit($request)) {
            return $response;
        }

        // Process incoming data via DataHandler?
        $parsedBody = $request->getParsedBody();
        if ($this->doSave
            || isset($parsedBody['_savedok'])
            || isset($parsedBody['_saveandclosedok'])
            || isset($parsedBody['_savedokview'])
            || isset($parsedBody['_savedoknew'])
            || isset($parsedBody['_duplicatedoc'])
        ) {
            if ($response = $this->processData($request)) {
                return $response;
            }
        }

        $this->init($request);
        $this->main($request);

        return new HtmlResponse($this->moduleTemplate->renderContent());
    }

    /**
     * First initialization, always called, even before processData() executes DataHandler processing.
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface|null Possible redirect response
     */
    public function preInit(ServerRequestInterface $request = null): ?ResponseInterface
    {
        if ($request === null) {
            // Missing argument? This method must have been called from outside.
            // Method will be protected and $request mandatory in TYPO3 v10.0, giving core freedom to move stuff around
            // New v10 signature: "protected function preInit(ServerRequestInterface $request): ?ResponseInterface"
            // @deprecated since TYPO3 v9, method argument $request will be set to mandatory
            trigger_error('EditDocumentController->preInit() will be set to protected in TYPO3 v10.0. Do not call from other extension.', E_USER_DEPRECATED);
            $request = $GLOBALS['TYPO3_REQUEST'];
        }

        if ($response = $this->localizationRedirect($request)) {
            return $response;
        }

        $parsedBody = $request->getParsedBody();
        $queryParams = $request->getQueryParams();

        $this->editconf = $parsedBody['edit'] ?? $queryParams['edit'] ?? [];
        $this->defVals = $parsedBody['defVals'] ?? $queryParams['defVals'] ?? null;
        $this->overrideVals = $parsedBody['overrideVals'] ?? $queryParams['overrideVals'] ?? null;
        $this->columnsOnly = $parsedBody['columnsOnly'] ?? $queryParams['columnsOnly'] ?? null;
        $this->returnUrl = GeneralUtility::sanitizeLocalUrl($parsedBody['returnUrl'] ?? $queryParams['returnUrl'] ?? null);
        $this->closeDoc = (int)($parsedBody['closeDoc'] ?? $queryParams['closeDoc'] ?? self::DOCUMENT_CLOSE_MODE_DEFAULT);
        $this->doSave = (bool)($parsedBody['doSave'] ?? $queryParams['doSave'] ?? false);
        $this->returnEditConf = (bool)($parsedBody['returnEditConf'] ?? $queryParams['returnEditConf'] ?? false);
        $this->workspace = $parsedBody['workspace'] ?? $queryParams['workspace'] ?? null;
        $this->uc = $parsedBody['uc'] ?? $queryParams['uc'] ?? null;

        // Set overrideVals as default values if defVals does not exist.
        // @todo: Why?
        if (!is_array($this->defVals) && is_array($this->overrideVals)) {
            $this->defVals = $this->overrideVals;
        }

        // Set final return URL
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        $this->retUrl = $this->returnUrl ?: (string)$uriBuilder->buildUriFromRoute('dummy');

        // Change $this->editconf if versioning applies to any of the records
        $this->fixWSversioningInEditConf();

        // Prepare R_URL (request url)
        $this->R_URL_parts = parse_url($request->getAttribute('normalizedParams')->getRequestUri());
        $this->R_URL_getvars = $queryParams;
        $this->R_URL_getvars['edit'] = $this->editconf;

        // Prepare 'open documents' url, this is later modified again various times
        $this->compileStoreData();
        // Backend user session data of this module
        $this->docDat = $this->getBackendUser()->getModuleData('FormEngine', 'ses');
        $this->docHandler = $this->docDat[0];

        // Close document if a request for closing the document has been sent
        if ((int)$this->closeDoc > self::DOCUMENT_CLOSE_MODE_DEFAULT) {
            if ($response = $this->closeDocument($this->closeDoc, $request)) {
                return $response;
            }
        }

        // Sets a temporary workspace, this request is based on
        if ($this->workspace !== null) {
            $this->getBackendUser()->setTemporaryWorkspace($this->workspace);
        }

        $this->emitFunctionAfterSignal('preInit', $request);

        return null;
    }

    /**
     * Detects, if a save command has been triggered.
     *
     * @return bool TRUE, then save the document (data submitted)
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0
     */
    public function doProcessData()
    {
        trigger_error('EditDocumentController->doProcessData() will be removed in TYPO3 v10.0.', E_USER_DEPRECATED);

        $out = $this->doSave
            || isset($_POST['_savedok'])
            || isset($_POST['_saveandclosedok'])
            || isset($_POST['_savedokview'])
            || isset($_POST['_savedoknew'])
            || isset($_POST['_duplicatedoc']);
        return $out;
    }

    /**
     * Do processing of data, submitting it to DataHandler. May return a RedirectResponse
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface|null
     */
    public function processData(ServerRequestInterface $request = null): ?ResponseInterface
    {
        // @deprecated Variable can be removed in TYPO3 v10.0
        $deprecatedCaller = false;
        if ($request === null) {
            // Missing argument? This method must have been called from outside.
            // Method will be protected and $request mandatory in TYPO3 v10.0, giving core freedom to move stuff around
            // New v10 signature: "protected function processData(ServerRequestInterface $request): ?ResponseInterface"
            // @deprecated since TYPO3 v9, method argument $request will be set to mandatory
            trigger_error('EditDocumentController->processData() will be set to protected in TYPO3 v10.0. Do not call from other extension.', E_USER_DEPRECATED);
            $request = $GLOBALS['TYPO3_REQUEST'];
            $deprecatedCaller = true;
        }

        $parsedBody = $request->getParsedBody();
        $queryParams = $request->getQueryParams();

        $beUser = $this->getBackendUser();

        // Processing related GET / POST vars
        $this->data = $parsedBody['data'] ?? $queryParams['data'] ?? [];
        $this->cmd = $parsedBody['cmd'] ?? $queryParams['cmd'] ?? [];
        $this->mirror = $parsedBody['mirror'] ?? $queryParams['mirror'] ?? [];
        // @deprecated property cacheCmd is unused and can be removed in TYPO3 v10.0
        $this->cacheCmd = $parsedBody['cacheCmd'] ?? $queryParams['cacheCmd'] ?? null;
        // @deprecated property redirect is unused and can be removed in TYPO3 v10.0
        $this->redirect = $parsedBody['redirect'] ?? $queryParams['redirect'] ?? null;
        $this->returnNewPageId = (bool)($parsedBody['returnNewPageId'] ?? $queryParams['returnNewPageId'] ?? false);

        // Only options related to $this->data submission are included here
        $tce = GeneralUtility::makeInstance(DataHandler::class);

        $tce->setControl($parsedBody['control'] ?? $queryParams['control'] ?? []);

        // Set default values specific for the user
        $TCAdefaultOverride = $beUser->getTSConfig()['TCAdefaults.'] ?? null;
        if (is_array($TCAdefaultOverride)) {
            $tce->setDefaultsFromUserTS($TCAdefaultOverride);
        }
        // Set internal vars
        if (isset($beUser->uc['neverHideAtCopy']) && $beUser->uc['neverHideAtCopy']) {
            $tce->neverHideAtCopy = 1;
        }
        // Load DataHandler with data
        $tce->start($this->data, $this->cmd);
        if (is_array($this->mirror)) {
            $tce->setMirror($this->mirror);
        }

        // Perform the saving operation with DataHandler:
        if ($this->doSave === true) {
            // @todo: Make DataHandler understand UploadedFileInterface and submit $request->getUploadedFiles() instead of $_FILES here
            $tce->process_uploads($_FILES);
            $tce->process_datamap();
            $tce->process_cmdmap();
        }
        // If pages are being edited, we set an instruction about updating the page tree after this operation.
        if ($tce->pagetreeNeedsRefresh
            && (isset($this->data['pages']) || $beUser->workspace != 0 && !empty($this->data))
        ) {
            BackendUtility::setUpdateSignal('updatePageTree');
        }
        // If there was saved any new items, load them:
        if (!empty($tce->substNEWwithIDs_table)) {
            // Save the expanded/collapsed states for new inline records, if any
            FormEngineUtility::updateInlineView($this->uc, $tce);
            $newEditConf = [];
            foreach ($this->editconf as $tableName => $tableCmds) {
                $keys = array_keys($tce->substNEWwithIDs_table, $tableName);
                if (!empty($keys)) {
                    foreach ($keys as $key) {
                        $editId = $tce->substNEWwithIDs[$key];
                        // Check if the $editId isn't a child record of an IRRE action
                        if (!(is_array($tce->newRelatedIDs[$tableName])
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
                        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
                        // Traverse all new records and forge the content of ->editconf so we can continue to edit these records!
                        if ($tableName === 'pages'
                            && $this->retUrl != (string)$uriBuilder->buildUriFromRoute('dummy')
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
            $this->compileStoreData();
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
            $nTable = key($this->editconf);
            // Finding the first id, getting the records pid+uid
            reset($this->editconf[$nTable]);
            $nUid = key($this->editconf[$nTable]);
            $recordFields = 'pid,uid';
            if (!empty($GLOBALS['TCA'][$nTable]['ctrl']['versioningWS'])) {
                $recordFields .= ',t3ver_oid';
            }
            $nRec = BackendUtility::getRecord($nTable, $nUid, $recordFields);
            // Determine insertion mode: 'top' is self-explaining,
            // otherwise new elements are inserted after one using a negative uid
            $insertRecordOnTop = ($this->getTsConfigOption($nTable, 'saveDocNew') === 'top');
            // Setting a blank editconf array for a new record:
            $this->editconf = [];
            // Determine related page ID for regular live context
            if ($nRec['pid'] != -1) {
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
            $this->compileStoreData();
        }
        // If a document should be duplicated.
        if (isset($parsedBody['_duplicatedoc']) && is_array($this->editconf)) {
            $this->closeDocument(self::DOCUMENT_CLOSE_MODE_NO_REDIRECT, $request);
            // Find current table
            reset($this->editconf);
            $nTable = key($this->editconf);
            // Find the first id, getting the records pid+uid
            reset($this->editconf[$nTable]);
            $nUid = key($this->editconf[$nTable]);
            if (!MathUtility::canBeInterpretedAsInteger($nUid)) {
                $nUid = $tce->substNEWwithIDs[$nUid];
            }

            $recordFields = 'pid,uid';
            if (!empty($GLOBALS['TCA'][$nTable]['ctrl']['versioningWS'])) {
                $recordFields .= ',t3ver_oid';
            }
            $nRec = BackendUtility::getRecord($nTable, $nUid, $recordFields);

            // Setting a blank editconf array for a new record:
            $this->editconf = [];

            if ($nRec['pid'] != -1) {
                $relatedPageId = -$nRec['uid'];
            } else {
                $relatedPageId = -$nRec['t3ver_oid'];
            }

            /** @var \TYPO3\CMS\Core\DataHandling\DataHandler $duplicateTce */
            $duplicateTce = GeneralUtility::makeInstance(DataHandler::class);

            $duplicateCmd = [
                $nTable => [
                    $nUid => [
                        'copy' => $relatedPageId
                    ]
                ]
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
            $this->compileStoreData();

            // Inform the user of the duplication
            $flashMessage = GeneralUtility::makeInstance(
                FlashMessage::class,
                $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.recordDuplicated'),
                '',
                FlashMessage::OK
            );
            $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
            $defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
            $defaultFlashMessageQueue->enqueue($flashMessage);
        }
        // If a preview is requested
        if (isset($parsedBody['_savedokview'])) {
            // Get the first table and id of the data array from DataHandler
            $table = reset(array_keys($this->data));
            $id = reset(array_keys($this->data[$table]));
            if (!MathUtility::canBeInterpretedAsInteger($id)) {
                $id = $tce->substNEWwithIDs[$id];
            }
            // Store this information for later use
            $this->previewData['table'] = $table;
            $this->previewData['id'] = $id;
        }
        $tce->printLogErrorMessages();

        if ((int)$this->closeDoc < self::DOCUMENT_CLOSE_MODE_DEFAULT
            || isset($parsedBody['_saveandclosedok'])
        ) {
            // Redirect if element should be closed after save
            $possibleRedirect = $this->closeDocument(abs($this->closeDoc), $request);
            if ($deprecatedCaller && $possibleRedirect) {
                // @deprecated fall back if method has been called from outside. This if can be removed in TYPO3 v10.0
                HttpUtility::redirect($possibleRedirect->getHeaders()['location'][0]);
            }
            return $possibleRedirect;
        }
        return null;
    }

    /**
     * Initialize the view part of the controller logic.
     *
     * @param ServerRequestInterface $request
     */
    public function init(ServerRequestInterface $request = null): void
    {
        if ($request === null) {
            // Missing argument? This method must have been called from outside.
            // Method will be protected and $request mandatory in TYPO3 v10.0, giving core freedom to move stuff around
            // New v10 signature: "protected function init(ServerRequestInterface $request): void
            // @deprecated since TYPO3 v9, method argument $request will be set to mandatory
            trigger_error('EditDocumentController->init() will be set to protected in TYPO3 v10.0. Do not call from other extension.', E_USER_DEPRECATED);
            $request = $GLOBALS['TYPO3_REQUEST'];
        }

        $parsedBody = $request->getParsedBody();
        $queryParams = $request->getQueryParams();

        $beUser = $this->getBackendUser();

        // @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0, unused, remove call in TYPO3 v10.0
        $this->popViewId_addParams = $parsedBody['popViewId_addParams'] ?? $queryParams['popViewId_addParams'] ?? '';

        $this->popViewId = (int)($parsedBody['popViewId'] ?? $queryParams['popViewId'] ?? 0);
        $this->viewUrl = (string)($parsedBody['viewUrl'] ?? $queryParams['viewUrl'] ?? '');
        $this->recTitle = (string)($parsedBody['recTitle'] ?? $queryParams['recTitle'] ?? '');
        $this->noView = (bool)($parsedBody['noView'] ?? $queryParams['noView'] ?? false);
        $this->perms_clause = $beUser->getPagePermsClause(Permission::PAGE_SHOW);
        // Set other internal variables:
        $this->R_URL_getvars['returnUrl'] = $this->retUrl;
        $this->R_URI = $this->R_URL_parts['path'] . HttpUtility::buildQueryString($this->R_URL_getvars, '?');

        // @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0, unused
        $this->MCONF['name'] = 'xMOD_alt_doc.php';
        // @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0, unused
        $this->doc = $GLOBALS['TBE_TEMPLATE'];

        $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
        $pageRenderer->addInlineLanguageLabelFile('EXT:backend/Resources/Private/Language/locallang_alt_doc.xlf');

        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        // override the default jumpToUrl
        $this->moduleTemplate->addJavaScriptCode(
            'jumpToUrl',
            '
            // Info view:
            function launchView(table,uid) {
                console.warn(\'Calling launchView() has been deprecated in TYPO3 v9 and will be removed in TYPO3 v10.0\');
                var thePreviewWindow = window.open(
                    ' . GeneralUtility::quoteJSvalue((string)$uriBuilder->buildUriFromRoute('show_item') . '&table=') . ' + encodeURIComponent(table) + "&uid=" + encodeURIComponent(uid),
                    "ShowItem" + Math.random().toString(16).slice(2),
                    "height=300,width=410,status=0,menubar=0,resizable=0,location=0,directories=0,scrollbars=1,toolbar=0"
                );
                if (thePreviewWindow && thePreviewWindow.focus) {
                    thePreviewWindow.focus();
                }
            }
            function deleteRecord(table,id,url) {
                window.location.href = ' . GeneralUtility::quoteJSvalue((string)$uriBuilder->buildUriFromRoute('tce_db') . '&cmd[') . '+table+"]["+id+"][delete]=1&redirect="+escape(url);
            }
        ' . (isset($parsedBody['_savedokview']) && $this->popViewId ? $this->generatePreviewCode() : '')
        );
        // Set context sensitive menu
        $this->moduleTemplate->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Backend/ContextMenu');

        $this->emitFunctionAfterSignal('init', $request);
    }

    /**
     * Generate the Javascript for opening the preview window
     *
     * @return string
     */
    protected function generatePreviewCode(): string
    {
        $previewPageId = $this->getPreviewPageId();
        $previewPageRootLine = BackendUtility::BEgetRootLine($previewPageId);
        $anchorSection = $this->getPreviewUrlAnchorSection();
        $previewUrlParameters = $this->getPreviewUrlParameters($previewPageId);

        return '
            if (window.opener) {
                '
                . BackendUtility::viewOnClick(
                    $previewPageId,
                    '',
                    $previewPageRootLine,
                    $anchorSection,
                    $this->viewUrl,
                    $previewUrlParameters,
                    false
                )
            . '
            } else {
            '
                . BackendUtility::viewOnClick(
                    $previewPageId,
                    '',
                    $previewPageRootLine,
                    $anchorSection,
                    $this->viewUrl,
                    $previewUrlParameters
                )
            . '
            }';
    }

    /**
     * Returns the parameters for the preview URL
     *
     * @param int $previewPageId
     * @return string
     */
    protected function getPreviewUrlParameters(int $previewPageId): string
    {
        $linkParameters = [];
        $table = $this->previewData['table'] ?: $this->firstEl['table'];
        $recordId = $this->previewData['id'] ?: $this->firstEl['uid'];
        $previewConfiguration = BackendUtility::getPagesTSconfig($previewPageId)['TCEMAIN.']['preview.'][$table . '.'] ?? [];
        $recordArray = BackendUtility::getRecord($table, $recordId);

        // language handling
        $languageField = $GLOBALS['TCA'][$table]['ctrl']['languageField'] ?? '';
        if ($languageField && !empty($recordArray[$languageField])) {
            $l18nPointer = $GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField'] ?? '';
            if ($l18nPointer && !empty($recordArray[$l18nPointer])
                && isset($previewConfiguration['useDefaultLanguageRecord'])
                && !$previewConfiguration['useDefaultLanguageRecord']
            ) {
                // use parent record
                $recordId = $recordArray[$l18nPointer];
            }
            $language = $recordArray[$languageField];
            if ($language > 0) {
                $linkParameters['L'] = $language;
            }
        }

        // map record data to GET parameters
        if (isset($previewConfiguration['fieldToParameterMap.'])) {
            foreach ($previewConfiguration['fieldToParameterMap.'] as $field => $parameterName) {
                $value = $recordArray[$field];
                if ($field === 'uid') {
                    $value = $recordId;
                }
                $linkParameters[$parameterName] = $value;
            }
        }

        // add/override parameters by configuration
        if (isset($previewConfiguration['additionalGetParameters.'])) {
            $additionalGetParameters = [];
            $this->parseAdditionalGetParameters(
                $additionalGetParameters,
                $previewConfiguration['additionalGetParameters.']
            );
            $linkParameters = array_replace($linkParameters, $additionalGetParameters);
        }

        if (!empty($previewConfiguration['useCacheHash'])) {
            $cacheHashCalculator = GeneralUtility::makeInstance(CacheHashCalculator::class);
            $fullLinkParameters = HttpUtility::buildQueryString(array_merge($linkParameters, ['id' => $previewPageId]), '&');
            $cacheHashParameters = $cacheHashCalculator->getRelevantParameters($fullLinkParameters);
            $linkParameters['cHash'] = $cacheHashCalculator->calculateCacheHash($cacheHashParameters);
        } else {
            $linkParameters['no_cache'] = 1;
        }

        return HttpUtility::buildQueryString($linkParameters, '&');
    }

    /**
     * Returns the anchor section for the preview url
     *
     * @return string
     */
    protected function getPreviewUrlAnchorSection(): string
    {
        $table = $this->previewData['table'] ?: $this->firstEl['table'];
        $recordId = $this->previewData['id'] ?: $this->firstEl['uid'];

        return $table === 'tt_content' ? '#c' . (int)$recordId : '';
    }

    /**
     * Returns the preview page id
     *
     * @return int
     */
    protected function getPreviewPageId(): int
    {
        $previewPageId = 0;
        $table = $this->previewData['table'] ?: $this->firstEl['table'];
        $recordId = $this->previewData['id'] ?: $this->firstEl['uid'];
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
            $currentPage = reset($rootLine);
            // Allow all doktypes below 200
            // This makes custom doktype work as well with opening a frontend page.
            if ((int)$currentPage['doktype'] <= PageRepository::DOKTYPE_SPACER) {
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
     * Migrates a set of (possibly nested) GET parameters in TypoScript syntax to a plain array
     *
     * This basically removes the trailing dots of sub-array keys in TypoScript.
     * The result can be used to create a query string with GeneralUtility::implodeArrayForUrl().
     *
     * @param array $parameters Should be an empty array by default
     * @param array $typoScript The TypoScript configuration
     */
    protected function parseAdditionalGetParameters(array &$parameters, array $typoScript)
    {
        foreach ($typoScript as $key => $value) {
            if (is_array($value)) {
                $key = rtrim($key, '.');
                $parameters[$key] = [];
                $this->parseAdditionalGetParameters($parameters[$key], $value);
            } else {
                $parameters[$key] = $value;
            }
        }
    }

    /**
     * Main module operation
     *
     * @param ServerRequestInterface $request
     */
    public function main(ServerRequestInterface $request = null): void
    {
        if ($request === null) {
            // Set method signature in TYPO3 v10.0 to: "protected function main(ServerRequestInterface $request): void"
            trigger_error('EditDocumentController->main() will be set to protected in TYPO3 v10.0.', E_USER_DEPRECATED);
            $request = $GLOBALS['TYPO3_REQUEST'];
        }

        $body = '';
        // Begin edit
        if (is_array($this->editconf)) {
            $this->formResultCompiler = GeneralUtility::makeInstance(FormResultCompiler::class);

            // Creating the editing form, wrap it with buttons, document selector etc.
            $editForm = $this->makeEditForm();
            if ($editForm) {
                $this->firstEl = reset($this->elementsData);
                // Checking if the currently open document is stored in the list of "open documents" - if not, add it:
                if (($this->docDat[1] !== $this->storeUrlMd5 || !isset($this->docHandler[$this->storeUrlMd5]))
                    && !$this->dontStoreDocumentRef
                ) {
                    $this->docHandler[$this->storeUrlMd5] = [
                        $this->storeTitle,
                        $this->storeArray,
                        $this->storeUrl,
                        $this->firstEl
                    ];
                    $this->getBackendUser()->pushModuleData('FormEngine', [$this->docHandler, $this->storeUrlMd5]);
                    BackendUtility::setUpdateSignal('OpendocsController::updateNumber', count($this->docHandler));
                }
                $body = $this->formResultCompiler->addCssFiles();
                $body .= $this->compileForm($editForm);
                $body .= $this->formResultCompiler->printNeededJSFunctions();
                $body .= '</form>';
            }
        }
        // Access check...
        // The page will show only if there is a valid page and if this page may be viewed by the user
        $this->pageinfo = BackendUtility::readPageAccess($this->viewId, $this->perms_clause);
        if ($this->pageinfo) {
            $this->moduleTemplate->getDocHeaderComponent()->setMetaInformation($this->pageinfo);
        }
        // Setting up the buttons and markers for doc header
        $this->getButtons($request);
        $this->languageSwitch(
            (string)($this->firstEl['table'] ?? ''),
            (int)($this->firstEl['uid'] ?? 0),
            isset($this->firstEl['pid']) ? (int)$this->firstEl['pid'] : null
        );
        $this->moduleTemplate->setContent($body);
    }

    /**
     * Creates the editing form with FormEngine, based on the input from GPvars.
     *
     * @return string HTML form elements wrapped in tables
     */
    protected function makeEditForm(): string
    {
        // Initialize variables
        $this->elementsData = [];
        $this->errorC = 0;
        $this->newC = 0;
        $editForm = '';
        $beUser = $this->getBackendUser();
        // Traverse the GPvar edit array tables
        foreach ($this->editconf as $table => $conf) {
            if (is_array($conf) && $GLOBALS['TCA'][$table] && $beUser->check('tables_modify', $table)) {
                // Traverse the keys/comments of each table (keys can be a comma list of uids)
                foreach ($conf as $cKey => $command) {
                    if ($command === 'edit' || $command === 'new') {
                        // Get the ids:
                        $ids = GeneralUtility::trimExplode(',', $cKey, true);
                        // Traverse the ids:
                        foreach ($ids as $theUid) {
                            // Don't save this document title in the document selector if the document is new.
                            if ($command === 'new') {
                                $this->dontStoreDocumentRef = 1;
                            }

                            try {
                                $formDataGroup = GeneralUtility::makeInstance(TcaDatabaseRecord::class);
                                $formDataCompiler = GeneralUtility::makeInstance(FormDataCompiler::class, $formDataGroup);
                                $nodeFactory = GeneralUtility::makeInstance(NodeFactory::class);

                                // Reset viewId - it should hold data of last entry only
                                $this->viewId = 0;
                                $this->viewId_addParams = '';

                                $formDataCompilerInput = [
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

                                $formData = $formDataCompiler->compile($formDataCompilerInput);

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
                                        // Adding "&L=xx" if the record being edited has a languageField with a value larger than zero!
                                        if (!empty($formData['processedTca']['ctrl']['languageField'])
                                            && is_array($formData['databaseRow'][$formData['processedTca']['ctrl']['languageField']])
                                            && $formData['databaseRow'][$formData['processedTca']['ctrl']['languageField']][0] > 0
                                        ) {
                                            $this->viewId_addParams = '&L=' . $formData['databaseRow'][$formData['processedTca']['ctrl']['languageField']][0];
                                        }
                                    }
                                }

                                // Determine if delete button can be shown
                                $deleteAccess = false;
                                if (
                                    $command === 'edit'
                                    || $command === 'new'
                                ) {
                                    $permission = $formData['userPermissionOnPage'];
                                    if ($formData['tableName'] === 'pages') {
                                        $deleteAccess = $permission & Permission::PAGE_DELETE ? true : false;
                                    } else {
                                        $deleteAccess = $permission & Permission::CONTENT_EDIT ? true : false;
                                    }
                                }

                                // Display "is-locked" message
                                if ($command === 'edit') {
                                    $lockInfo = BackendUtility::isRecordLocked($table, $formData['databaseRow']['uid']);
                                    if ($lockInfo) {
                                        $flashMessage = GeneralUtility::makeInstance(
                                            FlashMessage::class,
                                            $lockInfo['msg'],
                                            '',
                                            FlashMessage::WARNING
                                        );
                                        $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
                                        $defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
                                        $defaultFlashMessageQueue->enqueue($flashMessage);
                                    }
                                }

                                // Record title
                                if (!$this->storeTitle) {
                                    $this->storeTitle = $this->recTitle
                                        ? htmlspecialchars($this->recTitle)
                                        : BackendUtility::getRecordTitle($table, FormEngineUtility::databaseRowCompatibility($formData['databaseRow']), true);
                                }

                                $this->elementsData[] = [
                                    'table' => $table,
                                    'uid' => $formData['databaseRow']['uid'],
                                    'pid' => $formData['databaseRow']['pid'],
                                    'cmd' => $command,
                                    'deleteAccess' => $deleteAccess
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
                                    $this->newC++;
                                }

                                $editForm .= $html;
                            } catch (AccessDeniedException $e) {
                                $this->errorC++;
                                // Try to fetch error message from "recordInternals" be user object
                                // @todo: This construct should be logged and localized and de-uglified
                                $message = $beUser->errorMsg;
                                if (empty($message)) {
                                    // Create message from exception.
                                    $message = $e->getMessage() . ' ' . $e->getCode();
                                }
                                $editForm .= htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.noEditPermission'))
                                    . '<br /><br />' . htmlspecialchars($message) . '<br /><br />';
                            } catch (DatabaseRecordException $e) {
                                $editForm = '<div class="alert alert-warning">' . htmlspecialchars($e->getMessage()) . '</div>';
                            }
                        } // End of for each uid
                    }
                }
            }
        }
        return $editForm;
    }

    /**
     * Create the panel of buttons for submitting the form or otherwise perform operations.
     *
     * @param ServerRequestInterface $request
     */
    protected function getButtons(ServerRequestInterface $request): void
    {
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

        $buttonBar = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar();

        $this->registerCloseButtonToButtonBar($buttonBar, ButtonBar::BUTTON_POSITION_LEFT, 1);

        // Show buttons when table is not read-only
        if (
            !$this->errorC
            && !$GLOBALS['TCA'][$this->firstEl['table']]['ctrl']['readOnly']
        ) {
            $this->registerSaveButtonToButtonBar($buttonBar, ButtonBar::BUTTON_POSITION_LEFT, 2);
            $this->registerViewButtonToButtonBar($buttonBar, ButtonBar::BUTTON_POSITION_LEFT, 3);
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
            $this->registerDeleteButtonToButtonBar($buttonBar, ButtonBar::BUTTON_POSITION_LEFT, 6);
            $this->registerColumnsOnlyButtonToButtonBar($buttonBar, ButtonBar::BUTTON_POSITION_LEFT, 7);
            $this->registerHistoryButtonToButtonBar($buttonBar, ButtonBar::BUTTON_POSITION_RIGHT, 1);
        }

        $this->registerOpenInNewWindowButtonToButtonBar($buttonBar, ButtonBar::BUTTON_POSITION_RIGHT, 2);
        $this->registerShortcutButtonToButtonBar($buttonBar, ButtonBar::BUTTON_POSITION_RIGHT, 3);
        $this->registerCshButtonToButtonBar($buttonBar, ButtonBar::BUTTON_POSITION_RIGHT, 4);
    }

    /**
     * Set the boolean to check if the record is saved
     */
    protected function setIsSavedRecord()
    {
        if (!is_bool($this->isSavedRecord)) {
            $this->isSavedRecord = (
                $this->firstEl['cmd'] !== 'new'
                && MathUtility::canBeInterpretedAsInteger($this->firstEl['uid'])
            );
        }
    }

    /**
     * Returns if inconsistent language handling is allowed
     *
     * @return bool
     */
    protected function isInconsistentLanguageHandlingAllowed(): bool
    {
        $allowInconsistentLanguageHandling = BackendUtility::getPagesTSconfig(
            $this->pageinfo['uid']
        )['mod']['web_layout']['allowInconsistentLanguageHandling'];

        return $allowInconsistentLanguageHandling['value'] === '1';
    }

    /**
     * Set the boolean to check if the page is in free translation mode
     *
     * @param array|null $record
     * @param int $sysLanguageUid
     */
    protected function setIsPageInFreeTranslationMode($record, int $sysLanguageUid)
    {
        if ($this->firstEl['table'] === 'tt_content') {
            if (!$this->isSavedRecord) {
                $this->isPageInFreeTranslationMode = $this->getFreeTranslationMode(
                    (int)$this->pageinfo['uid'],
                    (int)$this->defVals['colPos'],
                    $sysLanguageUid
                );
            } else {
                $this->isPageInFreeTranslationMode = $this->getFreeTranslationMode(
                    (int)$this->pageinfo['uid'],
                    (int)$record['colPos'],
                    $sysLanguageUid
                );
            }
        }
    }

    /**
     * Check if the page is in free translation mode
     *
     * @param int $page
     * @param int $column
     * @param int $language
     * @return bool
     */
    protected function getFreeTranslationMode(int $page, int $column, int $language): bool
    {
        $freeTranslationMode = false;

        if (
            $this->getConnectedContentElementTranslationsCount($page, $column, $language) === 0
            && $this->getStandAloneContentElementTranslationsCount($page, $column, $language) >= 0
        ) {
            $freeTranslationMode = true;
        }

        return $freeTranslationMode;
    }

    /**
     * Register the close button to the button bar
     *
     * @param ButtonBar $buttonBar
     * @param string $position
     * @param int $group
     */
    protected function registerCloseButtonToButtonBar(ButtonBar $buttonBar, string $position, int $group)
    {
        $closeButton = $buttonBar->makeLinkButton()
            ->setHref('#')
            ->setClasses('t3js-editform-close')
            ->setTitle($this->getLanguageService()->sL(
                'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:rm.closeDoc'
            ))
            ->setShowLabelText(true)
            ->setIcon($this->moduleTemplate->getIconFactory()->getIcon(
                'actions-close',
                Icon::SIZE_SMALL
            ));

        $buttonBar->addButton($closeButton, $position, $group);
    }

    /**
     * Register the save button to the button bar
     *
     * @param ButtonBar $buttonBar
     * @param string $position
     * @param int $group
     */
    protected function registerSaveButtonToButtonBar(ButtonBar $buttonBar, string $position, int $group)
    {
        $saveButton = $buttonBar->makeInputButton()
            ->setForm('EditDocumentController')
            ->setIcon($this->moduleTemplate->getIconFactory()->getIcon('actions-document-save', Icon::SIZE_SMALL))
            ->setName('_savedok')
            ->setShowLabelText(true)
            ->setTitle($this->getLanguageService()->sL(
                'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:rm.saveDoc'
            ))
            ->setValue('1');

        $buttonBar->addButton($saveButton, $position, $group);
    }

    /**
     * Register the view button to the button bar
     *
     * @param ButtonBar $buttonBar
     * @param string $position
     * @param int $group
     */
    protected function registerViewButtonToButtonBar(ButtonBar $buttonBar, string $position, int $group)
    {
        if (
            $this->viewId // Pid to show the record
            && !$this->noView // Passed parameter
            && !empty($this->firstEl['table']) // No table

            // @TODO: TsConfig option should change to viewDoc
            && $this->getTsConfigOption($this->firstEl['table'], 'saveDocView')
        ) {
            $classNames = 't3js-editform-view';

            $pagesTSconfig = BackendUtility::getPagesTSconfig($this->pageinfo['uid']);

            if (isset($pagesTSconfig['TCEMAIN.']['preview.']['disableButtonForDokType'])) {
                $excludeDokTypes = GeneralUtility::intExplode(
                    ',',
                    $pagesTSconfig['TCEMAIN.']['preview.']['disableButtonForDokType'],
                    true
                );
            } else {
                // exclude sysfolders, spacers and recycler by default
                $excludeDokTypes = [
                    PageRepository::DOKTYPE_RECYCLER,
                    PageRepository::DOKTYPE_SYSFOLDER,
                    PageRepository::DOKTYPE_SPACER
                ];
            }

            if (
                !in_array((int)$this->pageinfo['doktype'], $excludeDokTypes, true)
                || isset($pagesTSconfig['TCEMAIN.']['preview.'][$this->firstEl['table'] . '.']['previewPageId'])
            ) {
                $previewPageId = $this->getPreviewPageId();
                $previewUrl = BackendUtility::getPreviewUrl(
                    $previewPageId,
                    '',
                    BackendUtility::BEgetRootLine($previewPageId),
                    $this->getPreviewUrlAnchorSection(),
                    $this->viewUrl,
                    $this->getPreviewUrlParameters($previewPageId)
                );

                $viewButton = $buttonBar->makeLinkButton()
                    ->setHref($previewUrl)
                    ->setIcon($this->moduleTemplate->getIconFactory()->getIcon(
                        'actions-view',
                        Icon::SIZE_SMALL
                    ))
                    ->setShowLabelText(true)
                    ->setTitle($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:rm.viewDoc'));

                if (!$this->isSavedRecord) {
                    if ($this->firstEl['table'] === 'pages') {
                        $viewButton->setDataAttributes(['is-new' => '']);
                    }
                }

                if ($classNames !== '') {
                    $viewButton->setClasses($classNames);
                }

                $buttonBar->addButton($viewButton, $position, $group);
            }
        }
    }

    /**
     * Register the new button to the button bar
     *
     * @param ButtonBar $buttonBar
     * @param string $position
     * @param int $group
     * @param int $sysLanguageUid
     * @param int $l18nParent
     */
    protected function registerNewButtonToButtonBar(
        ButtonBar $buttonBar,
        string $position,
        int $group,
        int $sysLanguageUid,
        int $l18nParent
    ) {
        if (
            $this->firstEl['table'] !== 'sys_file_metadata'
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
        ) {
            $classNames = 't3js-editform-new';

            $newButton = $buttonBar->makeLinkButton()
                ->setHref('#')
                ->setIcon($this->moduleTemplate->getIconFactory()->getIcon(
                    'actions-add',
                    Icon::SIZE_SMALL
                ))
                ->setShowLabelText(true)
                ->setTitle($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:rm.newDoc'));

            if (!$this->isSavedRecord) {
                $newButton->setDataAttributes(['is-new' => '']);
            }

            if ($classNames !== '') {
                $newButton->setClasses($classNames);
            }

            $buttonBar->addButton($newButton, $position, $group);
        }
    }

    /**
     * Register the duplication button to the button bar
     *
     * @param ButtonBar $buttonBar
     * @param string $position
     * @param int $group
     * @param int $sysLanguageUid
     * @param int $l18nParent
     */
    protected function registerDuplicationButtonToButtonBar(
        ButtonBar $buttonBar,
        string $position,
        int $group,
        int $sysLanguageUid,
        int $l18nParent
    ) {
        if (
            $this->firstEl['table'] !== 'sys_file_metadata'
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
            $classNames = 't3js-editform-duplicate';

            $duplicateButton = $buttonBar->makeLinkButton()
                ->setHref('#')
                ->setShowLabelText(true)
                ->setTitle($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:rm.duplicateDoc'))
                ->setIcon($this->moduleTemplate->getIconFactory()->getIcon(
                    'actions-document-duplicates-select',
                    Icon::SIZE_SMALL
                ));

            if (!$this->isSavedRecord) {
                $duplicateButton->setDataAttributes(['is-new' => '']);
            }

            if ($classNames !== '') {
                $duplicateButton->setClasses($classNames);
            }

            $buttonBar->addButton($duplicateButton, $position, $group);
        }
    }

    /**
     * Register the delete button to the button bar
     *
     * @param ButtonBar $buttonBar
     * @param string $position
     * @param int $group
     */
    protected function registerDeleteButtonToButtonBar(ButtonBar $buttonBar, string $position, int $group)
    {
        if (
            $this->firstEl['deleteAccess']
            && !$this->getDisableDelete()
            && $this->isSavedRecord
            && count($this->elementsData) === 1
        ) {
            $classNames = 't3js-editform-delete-record';

            $returnUrl = $this->retUrl;
            if ($this->firstEl['table'] === 'pages') {
                parse_str((string)parse_url($returnUrl, PHP_URL_QUERY), $queryParams);
                if (
                    isset($queryParams['route'], $queryParams['id'])
                    && (string)$this->firstEl['uid'] === (string)$queryParams['id']
                ) {

                    /** @var UriBuilder $uriBuilder */
                    $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);

                    // TODO: Use the page's pid instead of 0, this requires a clean API to manipulate the page
                    // tree from the outside to be able to mark the pid as active
                    $returnUrl = (string)$uriBuilder->buildUriFromRoutePath($queryParams['route'], ['id' => 0]);
                }
            }

            /** @var ReferenceIndex $referenceIndex */
            $referenceIndex = GeneralUtility::makeInstance(ReferenceIndex::class);
            $numberOfReferences = $referenceIndex->getNumberOfReferencedRecords(
                $this->firstEl['table'],
                (int)$this->firstEl['uid']
            );

            $referenceCountMessage = BackendUtility::referenceCount(
                $this->firstEl['table'],
                (int)$this->firstEl['uid'],
                $this->getLanguageService()->sL(
                    'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.referencesToRecord'
                ),
                $numberOfReferences
            );
            $translationCountMessage = BackendUtility::translationCount(
                $this->firstEl['table'],
                (int)$this->firstEl['uid'],
                $this->getLanguageService()->sL(
                    'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.translationsOfRecord'
                )
            );

            $deleteButton = $buttonBar->makeLinkButton()
                ->setClasses($classNames)
                ->setDataAttributes([
                    'return-url' => $returnUrl,
                    'uid' => $this->firstEl['uid'],
                    'table' => $this->firstEl['table'],
                    'reference-count-message' => $referenceCountMessage,
                    'translation-count-message' => $translationCountMessage
                ])
                ->setHref('#')
                ->setIcon($this->moduleTemplate->getIconFactory()->getIcon(
                   'actions-edit-delete',
                   Icon::SIZE_SMALL
                ))
                ->setShowLabelText(true)
                ->setTitle($this->getLanguageService()->getLL('deleteItem'));

            $buttonBar->addButton($deleteButton, $position, $group);
        }
    }

    /**
     * Register the history button to the button bar
     *
     * @param ButtonBar $buttonBar
     * @param string $position
     * @param int $group
     */
    protected function registerHistoryButtonToButtonBar(ButtonBar $buttonBar, string $position, int $group)
    {
        if (
            count($this->elementsData) === 1
            && !empty($this->firstEl['table'])
            && $this->getTsConfigOption($this->firstEl['table'], 'showHistory')
        ) {
            /** @var UriBuilder $uriBuilder */
            $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);

            $historyButtonOnClick = 'window.location.href=' .
                GeneralUtility::quoteJSvalue(
                    (string)$uriBuilder->buildUriFromRoute(
                        'record_history',
                        [
                            'element' => $this->firstEl['table'] . ':' . $this->firstEl['uid'],
                            'returnUrl' => $this->R_URI,
                        ]
                    )
                ) . '; return false;';

            $historyButton = $buttonBar->makeLinkButton()
                ->setHref('#')
                ->setIcon($this->moduleTemplate->getIconFactory()->getIcon(
                    'actions-document-history-open',
                    Icon::SIZE_SMALL
                ))
                ->setOnClick($historyButtonOnClick)
                ->setTitle('Open history of this record')
                ;

            $buttonBar->addButton($historyButton, $position, $group);
        }
    }

    /**
     * Register the columns only button to the button bar
     *
     * @param ButtonBar $buttonBar
     * @param string $position
     * @param int $group
     */
    protected function registerColumnsOnlyButtonToButtonBar(ButtonBar $buttonBar, string $position, int $group)
    {
        if (
            $this->columnsOnly
            && count($this->elementsData) === 1
        ) {
            $columnsOnlyButton = $buttonBar->makeLinkButton()
                ->setHref($this->R_URI . '&columnsOnly=')
                ->setTitle($this->getLanguageService()->getLL('editWholeRecord'))
                ->setIcon($this->moduleTemplate->getIconFactory()->getIcon(
                    'actions-open',
                    Icon::SIZE_SMALL
                ));

            $buttonBar->addButton($columnsOnlyButton, $position, $group);
        }
    }

    /**
     * Register the open in new window button to the button bar
     *
     * @param ButtonBar $buttonBar
     * @param string $position
     * @param int $group
     */
    protected function registerOpenInNewWindowButtonToButtonBar(ButtonBar $buttonBar, string $position, int $group)
    {
        $closeUrl = $this->getCloseUrl();
        if ($this->returnUrl !== $closeUrl) {
            $requestUri = GeneralUtility::linkThisScript([
                'returnUrl' => $closeUrl,
            ]);
            $aOnClick = 'vHWin=window.open('
                . GeneralUtility::quoteJSvalue($requestUri) . ','
                . GeneralUtility::quoteJSvalue(md5($this->R_URI))
                . ',\'width=670,height=500,status=0,menubar=0,scrollbars=1,resizable=1\');vHWin.focus();return false;';

            $openInNewWindowButton = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar()
                ->makeLinkButton()
                ->setHref('#')
                ->setTitle($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.openInNewWindow'))
                ->setIcon($this->moduleTemplate->getIconFactory()->getIcon('actions-window-open', Icon::SIZE_SMALL))
                ->setOnClick($aOnClick);

            $buttonBar->addButton($openInNewWindowButton, $position, $group);
        }
    }

    /**
     * Register the shortcut button to the button bar
     *
     * @param ButtonBar $buttonBar
     * @param string $position
     * @param int $group
     */
    protected function registerShortcutButtonToButtonBar(ButtonBar $buttonBar, string $position, int $group)
    {
        if ($this->returnUrl !== $this->getCloseUrl()) {
            $shortCutButton = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar()->makeShortcutButton();
            $shortCutButton->setModuleName('xMOD_alt_doc.php')
                ->setGetVariables([
                    'returnUrl',
                    'edit',
                    'defVals',
                    'overrideVals',
                    'columnsOnly',
                    'returnNewPageId',
                    'noView']);

            $buttonBar->addButton($shortCutButton, $position, $group);
        }
    }

    /**
     * Register the CSH button to the button bar
     *
     * @param ButtonBar $buttonBar
     * @param string $position
     * @param int $group
     */
    protected function registerCshButtonToButtonBar(ButtonBar $buttonBar, string $position, int $group)
    {
        $cshButton = $buttonBar->makeHelpButton()->setModuleName('xMOD_csh_corebe')->setFieldName('TCEforms');

        $buttonBar->addButton($cshButton, $position, $group);
    }

    /**
     * Get the count of connected translated content elements
     *
     * @param int $page
     * @param int $column
     * @param int $language
     * @return int
     */
    protected function getConnectedContentElementTranslationsCount(int $page, int $column, int $language): int
    {
        $queryBuilder = $this->getQueryBuilderForTranslationMode($page, $column, $language);

        return (int)$queryBuilder
            ->andWhere(
                $queryBuilder->expr()->gt(
                    $GLOBALS['TCA']['tt_content']['ctrl']['transOrigPointerField'],
                    $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)
                )
            )
            ->execute()
            ->fetchColumn(0);
    }

    /**
     * Get the count of standalone translated content elements
     *
     * @param int $page
     * @param int $column
     * @param int $language
     * @return int
     */
    protected function getStandAloneContentElementTranslationsCount(int $page, int $column, int $language): int
    {
        $queryBuilder = $this->getQueryBuilderForTranslationMode($page, $column, $language);

        return (int)$queryBuilder
            ->andWhere(
                $queryBuilder->expr()->eq(
                    $GLOBALS['TCA']['tt_content']['ctrl']['transOrigPointerField'],
                    $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)
                )
            )
            ->execute()
            ->fetchColumn(0);
    }

    /**
     * Get the query builder for the translation mode
     *
     * @param int $page
     * @param int $column
     * @param int $language
     * @return QueryBuilder
     */
    protected function getQueryBuilderForTranslationMode(int $page, int $column, int $language): QueryBuilder
    {
        $languageField = $GLOBALS['TCA']['tt_content']['ctrl']['languageField'];

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tt_content');

        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
            ->add(GeneralUtility::makeInstance(BackendWorkspaceRestriction::class));

        return $queryBuilder
            ->count('uid')
            ->from('tt_content')
            ->where(
                $queryBuilder->expr()->eq(
                    'pid',
                    $queryBuilder->createNamedParameter($page, \PDO::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    $languageField,
                    $queryBuilder->createNamedParameter($language, \PDO::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    'colPos',
                    $queryBuilder->createNamedParameter($column, \PDO::PARAM_INT)
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
                onsubmit="TBE_EDITOR.checkAndDoSubmit(1); return false;"
            >
            ' . $editForm . '
            <input type="hidden" name="returnUrl" value="' . htmlspecialchars($this->retUrl) . '" />
            <input type="hidden" name="viewUrl" value="' . htmlspecialchars($this->viewUrl) . '" />
            <input type="hidden" name="popViewId" value="' . htmlspecialchars((string)$this->viewId) . '" />
            <input type="hidden" name="closeDoc" value="0" />
            <input type="hidden" name="doSave" value="0" />
            <input type="hidden" name="_serialNumber" value="' . md5(microtime()) . '" />
            <input type="hidden" name="_scrollPosition" value="" />';
        if ($this->returnNewPageId) {
            $formContent .= '<input type="hidden" name="returnNewPageId" value="1" />';
        }
        if ($this->viewId_addParams) {
            $formContent .= '<input type="hidden" name="popViewId_addParams" value="' . htmlspecialchars($this->viewId_addParams) . '" />';
        }
        return $formContent;
    }

    /**
     * Create shortcut icon
     *
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0
     */
    public function shortCutLink()
    {
        trigger_error('EditDocumentController->shortCutLink() will be removed in TYPO3 v10.0.', E_USER_DEPRECATED);

        if ($this->returnUrl !== $this->getCloseUrl()) {
            $shortCutButton = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar()->makeShortcutButton();
            $shortCutButton->setModuleName('xMOD_alt_doc.php')
                ->setGetVariables([
                    'returnUrl',
                    'edit',
                    'defVals',
                    'overrideVals',
                    'columnsOnly',
                    'returnNewPageId',
                    'noView']);
            $this->moduleTemplate->getDocHeaderComponent()->getButtonBar()->addButton($shortCutButton);
        }
    }

    /**
     * Creates open-in-window link
     *
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0
     */
    public function openInNewWindowLink()
    {
        trigger_error('EditDocumentController->openInNewWindowLink() will be removed in TYPO3 v10.0.', E_USER_DEPRECATED);

        $closeUrl = $this->getCloseUrl();
        if ($this->returnUrl !== $closeUrl) {
            $aOnClick = 'vHWin=window.open(' . GeneralUtility::quoteJSvalue(GeneralUtility::linkThisScript(
                ['returnUrl' => $closeUrl]
            ))
                . ','
                . GeneralUtility::quoteJSvalue(md5($this->R_URI))
                . ',\'width=670,height=500,status=0,menubar=0,scrollbars=1,resizable=1\');vHWin.focus();return false;';
            $openInNewWindowButton = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar()
                ->makeLinkButton()
                ->setHref('#')
                ->setTitle($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.openInNewWindow'))
                ->setIcon($this->moduleTemplate->getIconFactory()->getIcon('actions-window-open', Icon::SIZE_SMALL))
                ->setOnClick($aOnClick);
            $this->moduleTemplate->getDocHeaderComponent()->getButtonBar()->addButton(
                $openInNewWindowButton,
                ButtonBar::BUTTON_POSITION_RIGHT
            );
        }
    }

    /**
     * Returns if delete for the current table is disabled by configuration.
     * For sys_file_metadata in default language delete is always disabled.
     *
     * @return bool
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
     * Returns the URL (usually for the "returnUrl") which closes the current window.
     * Used when editing a record in a popup.
     *
     * @return string
     */
    protected function getCloseUrl(): string
    {
        $closeUrl = GeneralUtility::getFileAbsFileName('EXT:backend/Resources/Public/Html/Close.html');
        return PathUtility::getAbsoluteWebPath($closeUrl);
    }

    /***************************
     *
     * Localization stuff
     *
     ***************************/
    /**
     * Make selector box for creating new translation for a record or switching to edit the record in an existing
     * language.
     * Displays only languages which are available for the current page.
     *
     * @param string $table Table name
     * @param int $uid Uid for which to create a new language
     * @param int $pid|null Pid of the record
     */
    protected function languageSwitch(string $table, int $uid, $pid = null)
    {
        $languageField = $GLOBALS['TCA'][$table]['ctrl']['languageField'];
        $transOrigPointerField = $GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField'];
        /** @var UriBuilder $uriBuilder */
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);

        // Table editable and activated for languages?
        if ($this->getBackendUser()->check('tables_modify', $table)
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
                    if ($rowCurrent[$transOrigPointerField] || $currentLanguage === 0) {
                        // Get record in other languages to see what's already available

                        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                            ->getQueryBuilderForTable($table);

                        $queryBuilder->getRestrictions()
                            ->removeAll()
                            ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
                            ->add(GeneralUtility::makeInstance(BackendWorkspaceRestriction::class));

                        $result = $queryBuilder->select(...GeneralUtility::trimExplode(',', $fetchFields, true))
                            ->from($table)
                            ->where(
                                $queryBuilder->expr()->eq(
                                    'pid',
                                    $queryBuilder->createNamedParameter($pid, \PDO::PARAM_INT)
                                ),
                                $queryBuilder->expr()->gt(
                                    $languageField,
                                    $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)
                                ),
                                $queryBuilder->expr()->eq(
                                    $transOrigPointerField,
                                    $queryBuilder->createNamedParameter($rowsByLang[0]['uid'], \PDO::PARAM_INT)
                                )
                            )
                            ->execute();

                        while ($row = $result->fetch()) {
                            $rowsByLang[$row[$languageField]] = $row;
                        }
                    }
                    $languageMenu = $this->moduleTemplate->getDocHeaderComponent()->getMenuRegistry()->makeMenu();
                    $languageMenu->setIdentifier('_langSelector');
                    foreach ($availableLanguages as $language) {
                        $languageId = $language->getLanguageId();
                        $selectorOptionLabel = $language->getTitle();
                        // Create url for creating a localized record
                        $addOption = true;
                        $href = '';
                        if (!isset($rowsByLang[$languageId])) {
                            // Translation in this language does not exist
                            $selectorOptionLabel .= ' [' . htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.new')) . ']';
                            $redirectUrl = (string)$uriBuilder->buildUriFromRoute('record_edit', [
                                'justLocalized' => $table . ':' . $rowsByLang[0]['uid'] . ':' . $languageId,
                                'returnUrl' => $this->retUrl
                            ]);

                            if (array_key_exists(0, $rowsByLang)) {
                                $href = BackendUtility::getLinkToDataHandlerAction(
                                    '&cmd[' . $table . '][' . $rowsByLang[0]['uid'] . '][localize]=' . $languageId,
                                    $redirectUrl
                                );
                            } else {
                                $addOption = false;
                            }
                        } else {
                            $params = [
                                'edit[' . $table . '][' . $rowsByLang[$languageId]['uid'] . ']' => 'edit',
                                'returnUrl' => $this->retUrl
                            ];
                            if ($table === 'pages') {
                                // Disallow manual adjustment of the language field for pages
                                $params['overrideVals'] = [
                                    'pages' => [
                                        'sys_language_uid' => $languageId
                                    ]
                                ];
                            }
                            $href = (string)$uriBuilder->buildUriFromRoute('record_edit', $params);
                        }
                        if ($addOption) {
                            $menuItem = $languageMenu->makeMenuItem()
                                ->setTitle($selectorOptionLabel)
                                ->setHref($href);
                            if ($languageId === $currentLanguage) {
                                $menuItem->setActive(true);
                            }
                            $languageMenu->addMenuItem($menuItem);
                        }
                    }
                    $this->moduleTemplate->getDocHeaderComponent()->getMenuRegistry()->addMenu($languageMenu);
                }
            }
        }
    }

    /**
     * Redirects to FormEngine with new parameters to edit a just created localized record
     *
     * @param ServerRequestInterface $request Incoming request object
     * @return ResponseInterface|null Possible redirect response
     */
    public function localizationRedirect(ServerRequestInterface $request = null): ?ResponseInterface
    {
        $deprecatedCaller = false;
        if (!$request instanceof ServerRequestInterface) {
            // @deprecated since TYPO3 v9
            // Method signature in TYPO3 v10.0: protected function localizationRedirect(ServerRequestInterface $request): ?ResponseInterface
            trigger_error('EditDocumentController->localizationRedirect() will be set to protected in TYPO3 v10.0. Do not call from other extension.', E_USER_DEPRECATED);
            $justLocalized = $request;
            $request = $GLOBALS['TYPO3_REQUEST'];
            $deprecatedCaller = true;
        } else {
            $justLocalized = $request->getQueryParams()['justLocalized'];
        }

        if (empty($justLocalized)) {
            return null;
        }

        list($table, $origUid, $language) = explode(':', $justLocalized);

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
                ->add(GeneralUtility::makeInstance(BackendWorkspaceRestriction::class));
            $localizedRecord = $queryBuilder->select('uid')
                ->from($table)
                ->where(
                    $queryBuilder->expr()->eq(
                        $GLOBALS['TCA'][$table]['ctrl']['languageField'],
                        $queryBuilder->createNamedParameter($language, \PDO::PARAM_INT)
                    ),
                    $queryBuilder->expr()->eq(
                        $GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField'],
                        $queryBuilder->createNamedParameter($origUid, \PDO::PARAM_INT)
                    )
                )
                ->execute()
                ->fetch();
            $returnUrl = $parsedBody['returnUrl'] ?? $queryParams['returnUrl'] ?? '';
            if (is_array($localizedRecord)) {
                if ($deprecatedCaller) {
                    // @deprecated fall back if method has been called from outside. This if can be removed in TYPO3 v10.0
                    $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
                    $location = (string)$uriBuilder->buildUriFromRoute('record_edit', [
                        'edit[' . $table . '][' . $localizedRecord['uid'] . ']' => 'edit',
                        'returnUrl' => GeneralUtility::sanitizeLocalUrl($returnUrl)
                    ]);
                    HttpUtility::redirect($location);
                }
                // Create redirect response to self to edit just created record
                $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
                return new RedirectResponse(
                    (string)$uriBuilder->buildUriFromRoute(
                        'record_edit',
                        [
                            'edit[' . $table . '][' . $localizedRecord['uid'] . ']' => 'edit',
                            'returnUrl' => GeneralUtility::sanitizeLocalUrl($returnUrl)
                        ]
                    ),
                    303
                );
            }
        }
        return null;
    }

    /**
     * Returns languages  available for record translations on given page.
     *
     * @param int $id Page id: If zero, the query will select all sys_language records from root level which are NOT
     *                hidden. If set to another value, the query will select all sys_language records that has a
     *                translation record on that page (and is not hidden, unless you are admin user)
     * @param string $table For pages we want all languages, for other records the languages of the page translations
     * @return SiteLanguage[] Language
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
            $pageId = $id;
        }
        $site = GeneralUtility::makeInstance(SiteMatcher::class)->matchByPageId($pageId);

        // Fetch the current translations of this page, to only show the ones where there is a page translation
        $allLanguages = $site->getAvailableLanguages($this->getBackendUser(), false, $pageId);
        if ($table !== 'pages' && $id > 0) {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
            $queryBuilder->getRestrictions()->removeAll()
                ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
                ->add(GeneralUtility::makeInstance(BackendWorkspaceRestriction::class));
            $statement = $queryBuilder->select('uid', $GLOBALS['TCA']['pages']['ctrl']['languageField'])
                ->from('pages')
                ->where(
                    $queryBuilder->expr()->eq(
                        $GLOBALS['TCA']['pages']['ctrl']['transOrigPointerField'],
                        $queryBuilder->createNamedParameter($pageId, \PDO::PARAM_INT)
                    )
                )
                ->execute();

            $availableLanguages = [];

            if ($allLanguages[0] ?? false) {
                $availableLanguages = [
                    0 => $allLanguages[0]
                ];
            }

            while ($row = $statement->fetch()) {
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
     * @param array|bool $mapArray Mapping between old and new ids if auto-versioning has been performed.
     */
    protected function fixWSversioningInEditConf($mapArray = false): void
    {
        // Traverse the editConf array
        if (is_array($this->editconf)) {
            // Tables:
            foreach ($this->editconf as $table => $conf) {
                if (is_array($conf) && $GLOBALS['TCA'][$table]) {
                    // Traverse the keys/comments of each table (keys can be a comma list of uids)
                    $newConf = [];
                    foreach ($conf as $cKey => $cmd) {
                        if ($cmd === 'edit') {
                            // Traverse the ids:
                            $ids = GeneralUtility::trimExplode(',', $cKey, true);
                            foreach ($ids as $idKey => $theUid) {
                                if (is_array($mapArray)) {
                                    if ($mapArray[$table][$theUid]) {
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
    }

    /**
     * Get record for editing.
     *
     * @param string $table Table name
     * @param int $theUid Record UID
     * @return array|false Returns record to edit, false if none
     */
    protected function getRecordForEdit(string $table, int $theUid)
    {
        // Fetch requested record:
        $reqRecord = BackendUtility::getRecord($table, $theUid, 'uid,pid');
        if (is_array($reqRecord)) {
            // If workspace is OFFLINE:
            if ($this->getBackendUser()->workspace != 0) {
                // Check for versioning support of the table:
                if ($GLOBALS['TCA'][$table] && $GLOBALS['TCA'][$table]['ctrl']['versioningWS']) {
                    // If the record is already a version of "something" pass it by.
                    if ($reqRecord['pid'] == -1) {
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
     *
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0
     */
    public function compileStoreDat()
    {
        trigger_error('EditDocumentController->compileStoreDat() will be removed in TYPO3 v10.0.', E_USER_DEPRECATED);
        $this->compileStoreData();
    }

    /**
     * Populates the variables $this->storeArray, $this->storeUrl, $this->storeUrlMd5
     * to prepare 'open documents' urls
     */
    protected function compileStoreData(): void
    {
        // @todo: Refactor in TYPO3 v10.0: This GeneralUtility method fiddles with _GP()
        $this->storeArray = GeneralUtility::compileSelectedGetVarsFromArray(
            'edit,defVals,overrideVals,columnsOnly,noView,workspace',
            $this->R_URL_getvars
        );
        $this->storeUrl = HttpUtility::buildQueryString($this->storeArray, '&');
        $this->storeUrlMd5 = md5($this->storeUrl);
    }

    /**
     * Function used to look for configuration of buttons in the form: Fx. disabling buttons or showing them at various
     * positions.
     *
     * @param string $table The table for which the configuration may be specific
     * @param string $key The option for look for. Default is checking if the saveDocNew button should be displayed.
     * @return string Return value fetched from USER TSconfig
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0
     */
    public function getNewIconMode($table, $key = 'saveDocNew')
    {
        trigger_error('EditDocumentController->getNewIconMode() will be removed in TYPO3 v10.0.', E_USER_DEPRECATED);
        return $this->getTsConfigOption($table, $key);
    }

    /**
     * Get a TSConfig 'option.' array, possibly for a specific table.
     *
     * @param string $table Table name
     * @param string $key Options key
     * @return string
     */
    protected function getTsConfigOption(string $table, string $key): string
    {
        return \trim((string)(
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
    public function closeDocument($mode = self::DOCUMENT_CLOSE_MODE_DEFAULT, ServerRequestInterface $request = null): ?ResponseInterface
    {
        // Foreign class call or missing argument? Method will be protected and $request mandatory in TYPO3 v10.0, giving core freedom to move stuff around
        $deprecatedCaller = false;
        if ($request === null) {
            // Set method signature in TYPO3 v10.0 to: "protected function closeDocument($mode, ServerRequestInterface $request): ?ResponseInterface"
            trigger_error('EditDocumentController->closeDocument will be set to protected in TYPO3 v10.0.', E_USER_DEPRECATED);
            $request = $GLOBALS['TYPO3_REQUEST'];
            $deprecatedCaller = true;
        }

        $mode = (int)$mode;
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
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        // If ->returnEditConf is set, then add the current content of editconf to the ->retUrl variable: used by
        // other scripts, like wizard_add, to know which records was created or so...
        if ($this->returnEditConf && $this->retUrl != (string)$uriBuilder->buildUriFromRoute('dummy')) {
            $this->retUrl .= '&returnEditConf=' . rawurlencode(json_encode($this->editconf));
        }
        // If mode is NOT set (means 0) OR set to 1, then make a header location redirect to $this->retUrl
        if ($mode === self::DOCUMENT_CLOSE_MODE_DEFAULT || $mode === self::DOCUMENT_CLOSE_MODE_REDIRECT) {
            if ($deprecatedCaller) {
                // @deprecated fall back if method has been called from outside. This if can be removed in TYPO3 v10.0
                HttpUtility::redirect($this->retUrl);
            }
            return new RedirectResponse($this->retUrl, 303);
        }
        if ($this->retUrl === '') {
            return null;
        }
        $retUrl = $this->returnUrl;
        if (is_array($this->docHandler) && !empty($this->docHandler)) {
            if (!empty($setupArr[2])) {
                $sParts = parse_url($request->getAttribute('normalizedParams')->getRequestUri());
                $retUrl = $sParts['path'] . '?' . $setupArr[2] . '&returnUrl=' . rawurlencode($retUrl);
            }
        }
        if ($deprecatedCaller) {
            // @deprecated fall back if method has been called from outside. This if can be removed in TYPO3 v10.0
            HttpUtility::redirect($retUrl);
        }
        return new RedirectResponse($retUrl, 303);
    }

    /**
     * Redirects to the document pointed to by $currentDocFromHandlerMD5 OR $retUrl,
     * depending on some internal calculations.
     *
     * @param string $currentDocFromHandlerMD5 Pointer to the document in the docHandler array
     * @param string $retUrl Alternative/Default retUrl
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0
     */
    public function setDocument($currentDocFromHandlerMD5 = '', $retUrl = '')
    {
        trigger_error('EditDocumentController->setDocument() will be removed in TYPO3 v10.0.', E_USER_DEPRECATED);
        if ($retUrl === '') {
            return;
        }
        if (is_array($this->docHandler) && !empty($this->docHandler)) {
            if (isset($this->docHandler[$currentDocFromHandlerMD5])) {
                $setupArr = $this->docHandler[$currentDocFromHandlerMD5];
            } else {
                $setupArr = reset($this->docHandler);
            }
            if ($setupArr[2]) {
                $sParts = parse_url(GeneralUtility::getIndpEnv('REQUEST_URI'));
                $retUrl = $sParts['path'] . '?' . $setupArr[2] . '&returnUrl=' . rawurlencode($retUrl);
            }
        }
        HttpUtility::redirect($retUrl);
    }

    /**
     * Emits a signal after a function was executed
     *
     * @param string $signalName
     * @param ServerRequestInterface $request
     */
    protected function emitFunctionAfterSignal($signalName, ServerRequestInterface $request): void
    {
        $this->getSignalSlotDispatcher()->dispatch(__CLASS__, $signalName . 'After', [$this, 'request' => $request]);
    }

    /**
     * Get the SignalSlot dispatcher
     *
     * @return \TYPO3\CMS\Extbase\SignalSlot\Dispatcher
     */
    protected function getSignalSlotDispatcher()
    {
        if (!isset($this->signalSlotDispatcher)) {
            $this->signalSlotDispatcher = GeneralUtility::makeInstance(Dispatcher::class);
        }
        return $this->signalSlotDispatcher;
    }

    /**
     * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
     */
    protected function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }

    /**
     * Returns LanguageService
     *
     * @return \TYPO3\CMS\Core\Localization\LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }
}
