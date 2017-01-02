<?php
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
use TYPO3\CMS\Backend\Form\FormDataCompiler;
use TYPO3\CMS\Backend\Form\FormDataGroup\TcaDatabaseRecord;
use TYPO3\CMS\Backend\Form\FormResultCompiler;
use TYPO3\CMS\Backend\Form\NodeFactory;
use TYPO3\CMS\Backend\Form\Utility\FormEngineUtility;
use TYPO3\CMS\Backend\Module\AbstractModule;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageQueue;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\HttpUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Extbase\SignalSlot\Dispatcher;
use TYPO3\CMS\Frontend\Page\PageRepository;

/**
 * Script Class: Drawing the editing form for editing records in TYPO3.
 * Notice: It does NOT use tce_db.php to submit data to, rather it handles submissions itself
 */
class EditDocumentController extends AbstractModule
{
    const DOCUMENT_CLOSE_MODE_DEFAULT = 0;
    const DOCUMENT_CLOSE_MODE_REDIRECT = 1; // works like DOCUMENT_CLOSE_MODE_DEFAULT
    const DOCUMENT_CLOSE_MODE_CLEAR_ALL = 3;
    const DOCUMENT_CLOSE_MODE_NO_REDIRECT = 4;

    /**
     * GPvar "edit": Is an array looking approx like [tablename][list-of-ids]=command, eg.
     * "&edit[pages][123]=edit". See \TYPO3\CMS\Backend\Utility\BackendUtility::editOnClick(). Value can be seen
     * modified internally (converting NEW keyword to id, workspace/versioning etc).
     *
     * @var array
     */
    public $editconf;

    /**
     * Commalist of fieldnames to edit. The point is IF you specify this list, only those
     * fields will be rendered in the form. Otherwise all (available) fields in the record
     * is shown according to the types configuration in $GLOBALS['TCA']
     *
     * @var bool
     */
    public $columnsOnly;

    /**
     * Default values for fields (array with tablenames, fields etc. as keys).
     * Can be seen modified internally.
     *
     * @var array
     */
    public $defVals;

    /**
     * Array of values to force being set (as hidden fields). Will be set as $this->defVals
     * IF defVals does not exist.
     *
     * @var array
     */
    public $overrideVals;

    /**
     * If set, this value will be set in $this->retUrl (which is used quite many places
     * as the return URL). If not set, "dummy.php" will be set in $this->retUrl
     *
     * @var string
     */
    public $returnUrl;

    /**
     * Close-document command. Not really sure of all options...
     *
     * @var int
     */
    public $closeDoc;

    /**
     * Quite simply, if this variable is set, then the processing of incoming data will be performed
     * as if a save-button is pressed. Used in the forms as a hidden field which can be set through
     * JavaScript if the form is somehow submitted by JavaScript).
     *
     * @var bool
     */
    public $doSave;

    /**
     * The data array from which the data comes...
     *
     * @var array
     */
    public $data;

    /**
     * @var string
     */
    public $cmd;

    /**
     * @var array
     */
    public $mirror;

    /**
     * Clear-cache cmd.
     *
     * @var string
     */
    public $cacheCmd;

    /**
     * Redirect (not used???)
     *
     * @var string
     */
    public $redirect;

    /**
     * Boolean: If set, then the GET var "&id=" will be added to the
     * retUrl string so that the NEW id of something is returned to the script calling the form.
     *
     * @var bool
     */
    public $returnNewPageId;

    /**
     * @var string
     */
    public $vC;

    /**
     * update BE_USER->uc
     *
     * @var array
     */
    public $uc;

    /**
     * ID for displaying the page in the frontend (used for SAVE/VIEW operations)
     *
     * @var int
     */
    public $popViewId;

    /**
     * Additional GET vars for the link, eg. "&L=xxx"
     *
     * @var string
     */
    public $popViewId_addParams;

    /**
     * Alternative URL for viewing the frontend pages.
     *
     * @var string
     */
    public $viewUrl;

    /**
     * If this is pointing to a page id it will automatically load all content elements
     * (NORMAL column/default language) from that page into the form!
     *
     * @var int
     * @deprecated since TYPO3 CMS 7, will be removed with TYPO3 CMS 8
     */
    public $editRegularContentFromId;

    /**
     * Alternative title for the document handler.
     *
     * @var string
     */
    public $recTitle;

    /**
     * If set, then no SAVE/VIEW button is printed
     *
     * @var bool
     */
    public $noView;

    /**
     * @var string
     */
    public $perms_clause;

    /**
     * If set, the $this->editconf array is returned to the calling script
     * (used by wizard_add.php for instance)
     *
     * @var bool
     */
    public $returnEditConf;

    /**
     * Workspace used for the editing action.
     *
     * @var NULL|int
     */
    protected $workspace;

    /**
     * document template object
     *
     * @var \TYPO3\CMS\Backend\Template\DocumentTemplate
     */
    public $doc;

    /**
     * a static HTML template, usually in templates/alt_doc.html
     *
     * @var string
     */
    public $template;

    /**
     * Content accumulation
     *
     * @var string
     */
    public $content;

    /**
     * Return URL script, processed. This contains the script (if any) that we should
     * RETURN TO from the FormEngine script IF we press the close button. Thus this
     * variable is normally passed along from the calling script so we can properly return if needed.
     *
     * @var string
     */
    public $retUrl;

    /**
     * Contains the parts of the REQUEST_URI (current url). By parts we mean the result of resolving
     * REQUEST_URI (current url) by the parse_url() function. The result is an array where eg. "path"
     * is the script path and "query" is the parameters...
     *
     * @var array
     */
    public $R_URL_parts;

    /**
     * Contains the current GET vars array; More specifically this array is the foundation for creating
     * the R_URI internal var (which becomes the "url of this script" to which we submit the forms etc.)
     *
     * @var array
     */
    public $R_URL_getvars;

    /**
     * Set to the URL of this script including variables which is needed to re-display the form. See main()
     *
     * @var string
     */
    public $R_URI;

    /**
     * @var array
     */
    public $MCONF;

    /**
     * @var array
     */
    public $pageinfo;

    /**
     * Is loaded with the "title" of the currently "open document" - this is used in the
     * Document Selector box. (see makeDocSel())
     *
     * @var string
     */
    public $storeTitle = '';

    /**
     * Contains an array with key/value pairs of GET parameters needed to reach the
     * current document displayed - used in the Document Selector box. (see compileStoreDat())
     *
     * @var array
     */
    public $storeArray;

    /**
     * Contains storeArray, but imploded into a GET parameter string (see compileStoreDat())
     *
     * @var string
     */
    public $storeUrl;

    /**
     * Hashed value of storeURL (see compileStoreDat())
     *
     * @var string
     */
    public $storeUrlMd5;

    /**
     * Module session data
     *
     * @var array
     */
    public $docDat;

    /**
     * An array of the "open documents" - keys are md5 hashes (see $storeUrlMd5) identifying
     * the various documents on the GET parameter list needed to open it. The values are
     * arrays with 0,1,2 keys with information about the document (see compileStoreDat()).
     * The docHandler variable is stored in the $docDat session data, key "0".
     *
     * @var array
     */
    public $docHandler;

    /**
     * Array of the elements to create edit forms for.
     *
     * @var array
     */
    public $elementsData;

    /**
     * Pointer to the first element in $elementsData
     *
     * @var array
     */
    public $firstEl;

    /**
     * Counter, used to count the number of errors (when users do not have edit permissions)
     *
     * @var int
     */
    public $errorC;

    /**
     * Counter, used to count the number of new record forms displayed
     *
     * @var int
     */
    public $newC;

    /**
     * Is set to the pid value of the last shown record - thus indicating which page to
     * show when clicking the SAVE/VIEW button
     *
     * @var int
     */
    public $viewId;

    /**
     * Is set to additional parameters (like "&L=xxx") if the record supports it.
     *
     * @var string
     */
    public $viewId_addParams;

    /**
     * Module TSconfig, loaded from main() based on the page id value of viewId
     *
     * @var array
     */
    public $modTSconfig;

    /**
     * @var FormResultCompiler
     */
    protected $formResultCompiler;

    /**
     * Used internally to disable the storage of the document reference (eg. new records)
     *
     * @var bool
     */
    public $dontStoreDocumentRef = 0;

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
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $GLOBALS['SOBE'] = $this;
        $this->getLanguageService()->includeLLFile('EXT:lang/locallang_alt_doc.xlf');
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
     * Emits a signal after a function was executed
     *
     * @param string $signalName
     */
    protected function emitFunctionAfterSignal($signalName)
    {
        $this->getSignalSlotDispatcher()->dispatch(__CLASS__, $signalName . 'After', [$this]);
    }

    /**
     * First initialization.
     *
     * @return void
     */
    public function preInit()
    {
        if (GeneralUtility::_GP('justLocalized')) {
            $this->localizationRedirect(GeneralUtility::_GP('justLocalized'));
        }
        // Setting GPvars:
        $this->editconf = GeneralUtility::_GP('edit');
        $this->defVals = GeneralUtility::_GP('defVals');
        $this->overrideVals = GeneralUtility::_GP('overrideVals');
        $this->columnsOnly = GeneralUtility::_GP('columnsOnly');
        $this->returnUrl = GeneralUtility::sanitizeLocalUrl(GeneralUtility::_GP('returnUrl'));
        $this->closeDoc = (int)GeneralUtility::_GP('closeDoc');
        $this->doSave = GeneralUtility::_GP('doSave');
        $this->returnEditConf = GeneralUtility::_GP('returnEditConf');
        $this->localizationMode = GeneralUtility::_GP('localizationMode');
        $this->workspace = GeneralUtility::_GP('workspace');
        $this->uc = GeneralUtility::_GP('uc');
        // Setting override values as default if defVals does not exist.
        if (!is_array($this->defVals) && is_array($this->overrideVals)) {
            $this->defVals = $this->overrideVals;
        }
        // Setting return URL
        $this->retUrl = $this->returnUrl ?: BackendUtility::getModuleUrl('dummy');
        // Fix $this->editconf if versioning applies to any of the records
        $this->fixWSversioningInEditConf();
        // Make R_URL (request url) based on input GETvars:
        $this->R_URL_parts = parse_url(GeneralUtility::getIndpEnv('REQUEST_URI'));
        $this->R_URL_getvars = GeneralUtility::_GET();
        $this->R_URL_getvars['edit'] = $this->editconf;
        // MAKE url for storing
        $this->compileStoreDat();
        // Get session data for the module:
        $this->docDat = $this->getBackendUser()->getModuleData('FormEngine', 'ses');
        $this->docHandler = $this->docDat[0];
        // If a request for closing the document has been sent, act accordingly:
        if ((int)$this->closeDoc > self::DOCUMENT_CLOSE_MODE_DEFAULT) {
            $this->closeDocument($this->closeDoc);
        }
        // If NO vars are sent to the script, try to read first document:
        // Added !is_array($this->editconf) because editConf must not be set either.
        // Anyways I can't figure out when this situation here will apply...
        if (is_array($this->R_URL_getvars) && count($this->R_URL_getvars) < 2 && !is_array($this->editconf)) {
            $this->setDocument($this->docDat[1]);
        }

        // Sets a temporary workspace, this request is based on
        if ($this->workspace !== null) {
            $this->getBackendUser()->setTemporaryWorkspace($this->workspace);
        }

        $this->emitFunctionAfterSignal(__FUNCTION__);
    }

    /**
     * Detects, if a save command has been triggered.
     *
     * @return bool TRUE, then save the document (data submitted)
     */
    public function doProcessData()
    {
        $out = $this->doSave
            || isset($_POST['_savedok'])
            || isset($_POST['_saveandclosedok'])
            || isset($_POST['_savedokview'])
            || isset($_POST['_savedoknew'])
            || isset($_POST['_translation_savedok_x'])
            || isset($_POST['_translation_savedokclear_x']);
        return $out;
    }

    /**
     * Do processing of data, submitting it to TCEmain.
     *
     * @return void
     */
    public function processData()
    {
        $beUser = $this->getBackendUser();
        // GPvars specifically for processing:
        $control = GeneralUtility::_GP('control');
        $this->data = GeneralUtility::_GP('data');
        $this->cmd = GeneralUtility::_GP('cmd');
        $this->mirror = GeneralUtility::_GP('mirror');
        $this->cacheCmd = GeneralUtility::_GP('cacheCmd');
        $this->redirect = GeneralUtility::_GP('redirect');
        $this->returnNewPageId = GeneralUtility::_GP('returnNewPageId');
        $this->vC = GeneralUtility::_GP('vC');
        // See tce_db.php for relevate options here:
        // Only options related to $this->data submission are included here.
        /** @var $tce \TYPO3\CMS\Core\DataHandling\DataHandler */
        $tce = GeneralUtility::makeInstance(DataHandler::class);
        $tce->stripslashes_values = false;

        if (!empty($control)) {
            $tce->setControl($control);
        }
        if (isset($_POST['_translation_savedok_x'])) {
            $tce->updateModeL10NdiffData = 'FORCE_FFUPD';
        }
        if (isset($_POST['_translation_savedokclear_x'])) {
            $tce->updateModeL10NdiffData = 'FORCE_FFUPD';
            $tce->updateModeL10NdiffDataClear = true;
        }
        // Setting default values specific for the user:
        $TCAdefaultOverride = $beUser->getTSConfigProp('TCAdefaults');
        if (is_array($TCAdefaultOverride)) {
            $tce->setDefaultsFromUserTS($TCAdefaultOverride);
        }
        // Setting internal vars:
        if ($beUser->uc['neverHideAtCopy']) {
            $tce->neverHideAtCopy = 1;
        }
        // Loading TCEmain with data:
        $tce->start($this->data, $this->cmd);
        if (is_array($this->mirror)) {
            $tce->setMirror($this->mirror);
        }
        // Checking referer / executing
        $refInfo = parse_url(GeneralUtility::getIndpEnv('HTTP_REFERER'));
        $httpHost = GeneralUtility::getIndpEnv('TYPO3_HOST_ONLY');
        if ($httpHost != $refInfo['host']
            && $this->vC != $beUser->veriCode()
            && !$GLOBALS['TYPO3_CONF_VARS']['SYS']['doNotCheckReferer']
        ) {
            $tce->log(
                '',
                0,
                0,
                0,
                1,
                'Referer host \'%s\' and server host \'%s\' did not match and veriCode was not valid either!',
                1,
                [$refInfo['host'], $httpHost]
            );
            debug('Error: Referer host did not match with server host.');
        } else {
            // Perform the saving operation with TCEmain:
            $tce->process_uploads($_FILES);
            $tce->process_datamap();
            $tce->process_cmdmap();
            // If pages are being edited, we set an instruction about updating the page tree after this operation.
            if ($tce->pagetreeNeedsRefresh
                && (isset($this->data['pages']) || $beUser->workspace != 0 && !empty($this->data))
            ) {
                BackendUtility::setUpdateSignal('updatePageTree');
            }
            // If there was saved any new items, load them:
            if (!empty($tce->substNEWwithIDs_table)) {
                // save the expanded/collapsed states for new inline records, if any
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
                                // Translate new id to the workspace version:
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
                            // Traverse all new records and forge the content of ->editconf so we can continue to EDIT
                            // these records!
                            if ($tableName == 'pages'
                                && $this->retUrl != BackendUtility::getModuleUrl('dummy')
                                && $this->returnNewPageId
                            ) {
                                $this->retUrl .= '&id=' . $tce->substNEWwithIDs[$key];
                            }
                        }
                    } else {
                        $newEditConf[$tableName] = $tableCmds;
                    }
                }
                // Resetting editconf if newEditConf has values:
                if (!empty($newEditConf)) {
                    $this->editconf = $newEditConf;
                }
                // Finally, set the editconf array in the "getvars" so they will be passed along in URLs as needed.
                $this->R_URL_getvars['edit'] = $this->editconf;
                // Unsetting default values since we don't need them anymore.
                unset($this->R_URL_getvars['defVals']);
                // Re-compile the store* values since editconf changed...
                $this->compileStoreDat();
            }
            // See if any records was auto-created as new versions?
            if (!empty($tce->autoVersionIdMap)) {
                $this->fixWSversioningInEditConf($tce->autoVersionIdMap);
            }
            // If a document is saved and a new one is created right after.
            if (isset($_POST['_savedoknew']) && is_array($this->editconf)) {
                $this->closeDocument(self::DOCUMENT_CLOSE_MODE_NO_REDIRECT);
                // Finding the current table:
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
                // Determine insertion mode ('top' is self-explaining,
                // otherwise new elements are inserted after one using a negative uid)
                $insertRecordOnTop = ($this->getNewIconMode($nTable) == 'top');
                // Setting a blank editconf array for a new record:
                $this->editconf = [];
                // Determine related page ID for regular live context
                if ($nRec['pid'] != -1) {
                    if ($insertRecordOnTop) {
                        $relatedPageId = $nRec['pid'];
                    } else {
                        $relatedPageId = -$nRec['uid'];
                    }
                // Determine related page ID for workspace context
                } else {
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
                // Re-compile the store* values since editconf changed...
                $this->compileStoreDat();
            }
            // If a preview is requested
            if (isset($_POST['_savedokview'])) {
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
            $tce->printLogErrorMessages(isset($_POST['_saveandclosedok']) || isset($_POST['_translation_savedok_x']) ? $this->retUrl : $this->R_URL_parts['path'] . '?' . GeneralUtility::implodeArrayForUrl('', $this->R_URL_getvars));
        }
        //  || count($tce->substNEWwithIDs)... If any new items has been save, the document is CLOSED
        // because if not, we just get that element re-listed as new. And we don't want that!
        if ((int)$this->closeDoc < self::DOCUMENT_CLOSE_MODE_DEFAULT
            || isset($_POST['_saveandclosedok'])
            || isset($_POST['_translation_savedok_x'])
        ) {
            $this->closeDocument(abs($this->closeDoc));
        }
    }

    /**
     * Initialize the normal module operation
     *
     * @return void
     */
    public function init()
    {
        $beUser = $this->getBackendUser();
        // Setting more GPvars:
        $this->popViewId = GeneralUtility::_GP('popViewId');
        $this->popViewId_addParams = GeneralUtility::_GP('popViewId_addParams');
        $this->viewUrl = GeneralUtility::_GP('viewUrl');
        $this->editRegularContentFromId = GeneralUtility::_GP('editRegularContentFromId');
        $this->recTitle = GeneralUtility::_GP('recTitle');
        $this->noView = GeneralUtility::_GP('noView');
        $this->perms_clause = $beUser->getPagePermsClause(1);
        // Set other internal variables:
        $this->R_URL_getvars['returnUrl'] = $this->retUrl;
        $this->R_URI = $this->R_URL_parts['path'] . '?' . ltrim(GeneralUtility::implodeArrayForUrl(
            '',
            $this->R_URL_getvars
        ), '&');
        // Setting virtual document name
        $this->MCONF['name'] = 'xMOD_alt_doc.php';

        // Create an instance of the document template object
        $this->doc = $GLOBALS['TBE_TEMPLATE'];
        $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
        $pageRenderer->addInlineLanguageLabelFile('EXT:lang/locallang_alt_doc.xlf');
        // override the default jumpToUrl
        $this->moduleTemplate->addJavaScriptCode(
            'jumpToUrl',
            '
			function jumpToUrl(URL,formEl) {
				if (!TBE_EDITOR.isFormChanged()) {
					window.location.href = URL;
				} else if (formEl && formEl.type=="checkbox") {
					formEl.checked = formEl.checked ? 0 : 1;
				}
			}
'
        );
        // define the window size of the element browser
        $popupWindowWidth  = 700;
        $popupWindowHeight = 750;
        $popupWindowSize = trim($beUser->getTSConfigVal('options.popupWindowSize'));
        if (!empty($popupWindowSize)) {
            list($popupWindowWidth, $popupWindowHeight) = GeneralUtility::intExplode('x', $popupWindowSize);
        }
        $t3Configuration = [
            'PopupWindow' => [
                'width' => $popupWindowWidth,
                'height' => $popupWindowHeight
            ]
        ];

        if (ExtensionManagementUtility::isLoaded('feedit') && (int)GeneralUtility::_GP('feEdit') === 1) {
            // We have to load some locallang strings and push them into TYPO3.LLL if this request was
            // triggered by feedit. Originally, this object is fed by BackendController which is not
            // called here. This block of code is intended to be removed at a later point again.
            $lang = $this->getLanguageService();
            $coreLabels = [
                'csh_tooltip_loading' => $lang->sL('LLL:EXT:lang/locallang_core.xlf:csh_tooltip_loading')
            ];
            $generatedLabels = [];
            $generatedLabels['core'] = $coreLabels;
            $code = 'TYPO3.LLL = ' . json_encode($generatedLabels) . ';';
            $filePath = 'typo3temp/Language/Backend-' . sha1($code) . '.js';
            if (!file_exists(PATH_site . $filePath)) {
                // writeFileToTypo3tempDir() returns NULL on success (please double-read!)
                $error = GeneralUtility::writeFileToTypo3tempDir(PATH_site . $filePath, $code);
                if ($error !== null) {
                    throw new \RuntimeException('Locallang JS file could not be written to ' . $filePath . '. Reason: ' . $error, 1446118286);
                }
            }
            $pageRenderer->addJsFile('../' . $filePath);

            // define the window size of the popups within the RTE
            $rtePopupWindowSize = trim($beUser->getTSConfigVal('options.rte.popupWindowSize'));
            if (!empty($rtePopupWindowSize)) {
                list($rtePopupWindowWidth, $rtePopupWindowHeight) = GeneralUtility::trimExplode('x', $rtePopupWindowSize);
            }
            $rtePopupWindowWidth  = !empty($rtePopupWindowWidth) ? (int)$rtePopupWindowWidth : ($popupWindowWidth-200);
            $rtePopupWindowHeight = !empty($rtePopupWindowHeight) ? (int)$rtePopupWindowHeight : ($popupWindowHeight-250);
            $t3Configuration['RTEPopupWindow'] = [
                'width' => $rtePopupWindowWidth,
                'height' => $rtePopupWindowHeight
            ];
        }

        $javascript = '
			TYPO3.configuration = ' . json_encode($t3Configuration) . ';
			// Object: TS:
			// passwordDummy and decimalSign are used by tbe_editor.js and have to be declared here as
			// TS object overwrites the object declared in tbe_editor.js
			function typoSetup() {	//
				this.uniqueID = "";
				this.passwordDummy = "********";
				this.PATH_typo3 = "";
				this.decimalSign = ".";
			}
			var TS = new typoSetup();

				// Info view:
			function launchView(table,uid,bP) {	//
				var backPath= bP ? bP : "";
				var thePreviewWindow = window.open(
					backPath+' . GeneralUtility::quoteJSvalue(BackendUtility::getModuleUrl('show_item') . '&table=') . ' + encodeURIComponent(table) + "&uid=" + encodeURIComponent(uid),
					"ShowItem" + TS.uniqueID,
					"height=300,width=410,status=0,menubar=0,resizable=0,location=0,directories=0,scrollbars=1,toolbar=0"
				);
				if (thePreviewWindow && thePreviewWindow.focus) {
					thePreviewWindow.focus();
				}
			}
			function deleteRecord(table,id,url) {	//
				window.location.href = ' . GeneralUtility::quoteJSvalue(BackendUtility::getModuleUrl('tce_db') . '&cmd[') . '+table+"]["+id+"][delete]=1&redirect="+escape(url)+"&vC=' . $beUser->veriCode() . '&prErr=1&uPT=1";
			}
		';

        $previewCode = isset($_POST['_savedokview']) && $this->popViewId ? $this->generatePreviewCode() : '';
        $this->moduleTemplate->addJavaScriptCode(
            'PreviewCode',
            $javascript . $previewCode
        );
        // Setting up the context sensitive menu:
        $this->moduleTemplate->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Backend/ClickMenu');

        $this->emitFunctionAfterSignal(__FUNCTION__);
    }

    /**
     * @return string
     */
    protected function generatePreviewCode()
    {
        $table = $this->previewData['table'];
        $recordId = $this->previewData['id'];

        if ($table === 'pages') {
            $currentPageId = $recordId;
        } else {
            $currentPageId = MathUtility::convertToPositiveInteger($this->popViewId);
        }

        $pageTsConfig = BackendUtility::getPagesTSconfig($currentPageId);
        $previewConfiguration = isset($pageTsConfig['TCEMAIN.']['preview.'][$table . '.'])
            ? $pageTsConfig['TCEMAIN.']['preview.'][$table . '.']
            : [];

        $recordArray = BackendUtility::getRecord($table, $recordId);

        // find the right preview page id
        $previewPageId = 0;
        if (isset($previewConfiguration['previewPageId'])) {
            $previewPageId = $previewConfiguration['previewPageId'];
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

        $linkParameters = [
            'no_cache' => 1,
        ];

        // language handling
        $languageField = isset($GLOBALS['TCA'][$table]['ctrl']['languageField'])
            ? $GLOBALS['TCA'][$table]['ctrl']['languageField']
            : '';
        if ($languageField && !empty($recordArray[$languageField])) {
            $l18nPointer = isset($GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField'])
                ? $GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField']
                : '';
            if ($l18nPointer && !empty($recordArray[$l18nPointer])
                && isset($previewConfiguration['useDefaultLanguageRecord'])
                && !$previewConfiguration['useDefaultLanguageRecord']
            ) {
                // use parent record
                $recordId = $recordArray[$l18nPointer];
            }
            $linkParameters['L'] = $recordArray[$languageField];
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

        $this->popViewId = $previewPageId;
        $this->popViewId_addParams = GeneralUtility::implodeArrayForUrl('', $linkParameters, '', false, true);

        $previewPageRootline = BackendUtility::BEgetRootLine($this->popViewId);
        return '
				if (window.opener) {
				'
            . BackendUtility::viewOnClick(
                $this->popViewId,
                '',
                $previewPageRootline,
                '',
                $this->viewUrl,
                $this->popViewId_addParams,
                false
            )
            . '
				} else {
				'
            . BackendUtility::viewOnClick(
                $this->popViewId,
                '',
                $previewPageRootline,
                '',
                $this->viewUrl,
                $this->popViewId_addParams
            )
            . '
				}';
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
     * @return void
     */
    public function main()
    {
        $body = '';
        // Begin edit:
        if (is_array($this->editconf)) {
            /** @var FormResultCompiler formResultCompiler */
            $this->formResultCompiler = GeneralUtility::makeInstance(FormResultCompiler::class);

            if ($this->editRegularContentFromId) {
                $this->editRegularContentFromId();
            }
            // Creating the editing form, wrap it with buttons, document selector etc.
            $editForm = $this->makeEditForm();
            if ($editForm) {
                $this->firstEl = reset($this->elementsData);
                // Checking if the currently open document is stored in the list of "open documents" - if not, add it:
                if (($this->docDat[1] !== $this->storeUrlMd5
                        || !isset($this->docHandler[$this->storeUrlMd5]))
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
                // Module configuration
                $this->modTSconfig = $this->viewId ? BackendUtility::getModTSconfig(
                    $this->viewId,
                    'mod.xMOD_alt_doc'
                ) : [];
                $body = $this->formResultCompiler->JStop();
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
        // Setting up the buttons and markers for docheader
        $this->getButtons();
        $this->languageSwitch($this->firstEl['table'], $this->firstEl['uid'], $this->firstEl['pid']);
        $this->moduleTemplate->setContent($body);
    }

    /**
     * Outputting the accumulated content to screen
     *
     * @return void
     * @deprecated since TYPO3 CMS 7, will be removed in TYPO3 CMS 8
     */
    public function printContent()
    {
        GeneralUtility::logDeprecatedFunction();
        echo $this->content;
    }

    /***************************
     *
     * Sub-content functions, rendering specific parts of the module content.
     *
     ***************************/
    /**
     * Creates the editing form with FormEnigne, based on the input from GPvars.
     *
     * @return string HTML form elements wrapped in tables
     */
    public function makeEditForm()
    {
        // Initialize variables:
        $this->elementsData = [];
        $this->errorC = 0;
        $this->newC = 0;
        $editForm = '';
        $trData = null;
        $beUser = $this->getBackendUser();
        // Traverse the GPvar edit array
        // Tables:
        foreach ($this->editconf as $table => $conf) {
            if (is_array($conf) && $GLOBALS['TCA'][$table] && $beUser->check('tables_modify', $table)) {
                // Traverse the keys/comments of each table (keys can be a commalist of uids)
                foreach ($conf as $cKey => $command) {
                    if ($command == 'edit' || $command == 'new') {
                        // Get the ids:
                        $ids = GeneralUtility::trimExplode(',', $cKey, true);
                        // Traverse the ids:
                        foreach ($ids as $theUid) {
                            // Don't save this document title in the document selector if the document is new.
                            if ($command === 'new') {
                                $this->dontStoreDocumentRef = 1;
                            }

                            /** @var TcaDatabaseRecord $formDataGroup */
                            $formDataGroup = GeneralUtility::makeInstance(TcaDatabaseRecord::class);
                            /** @var FormDataCompiler $formDataCompiler */
                            $formDataCompiler = GeneralUtility::makeInstance(FormDataCompiler::class, $formDataGroup);
                            /** @var NodeFactory $nodeFactory */
                            $nodeFactory = GeneralUtility::makeInstance(NodeFactory::class);

                            try {
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

                                $formData = $formDataCompiler->compile($formDataCompilerInput);

                                // Set this->viewId if possible
                                if ($command === 'new'
                                    && $table !== 'pages'
                                    && !empty($formData['parentPageRow']['uid'])
                                ) {
                                    $this->viewId = $formData['parentPageRow']['uid'];
                                } else {
                                    if ($table == 'pages') {
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
                                if ($command === 'edit') {
                                    $permission = $formData['userPermissionOnPage'];
                                    if ($formData['tableName'] === 'pages') {
                                        $deleteAccess = $permission & Permission::PAGE_DELETE ? true : false;
                                    } else {
                                        $deleteAccess = $permission & Permission::CONTENT_EDIT ? true : false;
                                    }
                                }

                                // Display "is-locked" message:
                                if ($command === 'edit') {
                                    $lockInfo = BackendUtility::isRecordLocked($table, $formData['databaseRow']['uid']);
                                    if ($lockInfo) {
                                        /** @var $flashMessage \TYPO3\CMS\Core\Messaging\FlashMessage */
                                        $flashMessage = GeneralUtility::makeInstance(
                                            FlashMessage::class,
                                            $lockInfo['msg'],
                                            '',
                                            FlashMessage::WARNING
                                        );
                                        /** @var $flashMessageService \TYPO3\CMS\Core\Messaging\FlashMessageService */
                                        $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
                                        /** @var $defaultFlashMessageQueue FlashMessageQueue */
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
                                // @todo: This is done here for now to not rewrite JStop()
                                // @todo: and printNeededJSFunctions() now
                                $this->formResultCompiler->mergeResult($formResult);

                                // Seems the pid is set as hidden field (again) at end?!
                                if ($command == 'new') {
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
                                $editForm .= $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:labels.noEditPermission', true)
                                    . '<br /><br />' . htmlspecialchars($message) . '<br /><br />';
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
     * @return array All available buttons as an assoc. array
     */
    protected function getButtons()
    {
        $lang = $this->getLanguageService();
        // Render SAVE type buttons:
        // The action of each button is decided by its name attribute. (See doProcessData())
        $buttonBar = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar();
        if (!$this->errorC && !$GLOBALS['TCA'][$this->firstEl['table']]['ctrl']['readOnly']) {
            $saveSplitButton = $buttonBar->makeSplitButton();
            // SAVE button:
            $saveButton = $buttonBar->makeInputButton()
                ->setTitle($lang->sL('LLL:EXT:lang/locallang_core.xlf:rm.saveDoc'))
                ->setName('_savedok')
                ->setValue('1')
                ->setForm('EditDocumentController')
                ->setIcon($this->moduleTemplate->getIconFactory()->getIcon('actions-document-save', Icon::SIZE_SMALL));
            $saveSplitButton->addItem($saveButton, true);

            // SAVE / VIEW button:
            if ($this->viewId && !$this->noView && $this->getNewIconMode($this->firstEl['table'], 'saveDocView')) {
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
                if (!in_array((int)$this->pageinfo['doktype'], $excludeDokTypes, true)
                    || isset($pagesTSconfig['TCEMAIN.']['preview.'][$this->firstEl['table'] . '.']['previewPageId'])
                ) {
                    $saveAndOpenButton = $buttonBar->makeInputButton()
                        ->setTitle($lang->sL('LLL:EXT:lang/locallang_core.xlf:rm.saveDocShow'))
                        ->setName('_savedokview')
                        ->setValue('1')
                        ->setForm('EditDocumentController')
                        ->setOnClick("window.open('', 'newTYPO3frontendWindow');")
                        ->setIcon($this->moduleTemplate->getIconFactory()->getIcon(
                            'actions-document-save-view',
                            Icon::SIZE_SMALL
                        ));
                    $saveSplitButton->addItem($saveAndOpenButton);
                }
            }
            // SAVE / NEW button:
            if (count($this->elementsData) === 1 && $this->getNewIconMode($this->firstEl['table'])) {
                $saveAndNewButton = $buttonBar->makeInputButton()
                    ->setName('_savedoknew')
                    ->setClasses('t3js-editform-submitButton')
                    ->setValue('1')
                    ->setForm('EditDocumentController')
                    ->setTitle($lang->sL('LLL:EXT:lang/locallang_core.xlf:rm.saveNewDoc'))
                    ->setIcon($this->moduleTemplate->getIconFactory()->getIcon(
                        'actions-document-save-new',
                        Icon::SIZE_SMALL
                    ));
                $saveSplitButton->addItem($saveAndNewButton);
            }
            // SAVE / CLOSE
            $saveAndCloseButton = $buttonBar->makeInputButton()
                ->setName('_saveandclosedok')
                ->setClasses('t3js-editform-submitButton')
                ->setValue('1')
                ->setForm('EditDocumentController')
                ->setTitle($lang->sL('LLL:EXT:lang/locallang_core.xlf:rm.saveCloseDoc'))
                ->setIcon($this->moduleTemplate->getIconFactory()->getIcon(
                    'actions-document-save-close',
                    Icon::SIZE_SMALL
                ));
            $saveSplitButton->addItem($saveAndCloseButton);
            // FINISH TRANSLATION / SAVE / CLOSE
            if ($GLOBALS['TYPO3_CONF_VARS']['BE']['explicitConfirmationOfTranslation']) {
                $saveTranslationButton = $buttonBar->makeInputButton()
                    ->setName('_translation_savedok')
                    ->setValue('1')
                    ->setForm('EditDocumentController')
                    ->setTitle($lang->sL('LLL:EXT:lang/locallang_core.xlf:rm.translationSaveDoc'))
                    ->setIcon($this->moduleTemplate->getIconFactory()->getIcon(
                        'actions-document-save-cleartranslationcache',
                        Icon::SIZE_SMALL
                    ));
                $saveSplitButton->addItem($saveTranslationButton);
                $saveAndClearTranslationButton = $buttonBar->makeInputButton()
                    ->setName('_translation_savedokclear')
                    ->setValue('1')
                    ->setForm('EditDocumentController')
                    ->setTitle($lang->sL('LLL:EXT:lang/locallang_core.xlf:rm.translationSaveDocClear'))
                    ->setIcon($this->moduleTemplate->getIconFactory()->getIcon(
                        'actions-document-save-cleartranslationcache',
                        Icon::SIZE_SMALL
                    ));
                $saveSplitButton->addItem($saveAndClearTranslationButton);
            }
            $buttonBar->addButton($saveSplitButton, ButtonBar::BUTTON_POSITION_LEFT, 2);
        }
        // CLOSE button:
        $closeButton = $buttonBar->makeLinkButton()
            ->setHref('#')
            ->setClasses('t3js-editform-close')
            ->setTitle($lang->sL('LLL:EXT:lang/locallang_core.xlf:rm.closeDoc'))
            ->setIcon($this->moduleTemplate->getIconFactory()->getIcon(
                'actions-document-close',
                Icon::SIZE_SMALL
            ));
        $buttonBar->addButton($closeButton);
        // DELETE + UNDO buttons:
        if (!$this->errorC
            && !$GLOBALS['TCA'][$this->firstEl['table']]['ctrl']['readOnly']
            && count($this->elementsData) === 1
        ) {
            if ($this->firstEl['cmd'] !== 'new' && MathUtility::canBeInterpretedAsInteger($this->firstEl['uid'])) {
                // Delete:
                if ($this->firstEl['deleteAccess']
                    && !$GLOBALS['TCA'][$this->firstEl['table']]['ctrl']['readOnly']
                    && !$this->getNewIconMode($this->firstEl['table'], 'disableDelete')
                ) {
                    $returnUrl = $this->retUrl;
                    if ($this->firstEl['table'] === 'pages') {
                        parse_str((string)parse_url($returnUrl, PHP_URL_QUERY), $queryParams);
                        if (isset($queryParams['M'])
                            && isset($queryParams['id'])
                            && (string)$this->firstEl['uid'] === (string)$queryParams['id']
                        ) {
                            // TODO: Use the page's pid instead of 0, this requires a clean API to manipulate the page
                            // tree from the outside to be able to mark the pid as active
                            $returnUrl = BackendUtility::getModuleUrl($queryParams['M'], ['id' => 0]);
                        }
                    }
                    $deleteButton = $buttonBar->makeLinkButton()
                        ->setHref('#')
                        ->setClasses('t3js-editform-delete-record')
                        ->setTitle($lang->getLL('deleteItem'))
                        ->setIcon($this->moduleTemplate->getIconFactory()->getIcon(
                            'actions-edit-delete',
                            Icon::SIZE_SMALL
                        ))
                        ->setDataAttributes([
                            'return-url' => $returnUrl,
                            'uid' => $this->firstEl['uid'],
                            'table' => $this->firstEl['table']
                        ]);
                    $buttonBar->addButton($deleteButton, ButtonBar::BUTTON_POSITION_LEFT, 3);
                }
                // Undo:
                $undoRes = $this->getDatabaseConnection()->exec_SELECTquery(
                    'tstamp',
                    'sys_history',
                    'tablename='
                    . $this->getDatabaseConnection()->fullQuoteStr($this->firstEl['table'], 'sys_history')
                    . ' AND recuid='
                    . (int)$this->firstEl['uid'],
                    '',
                    'tstamp DESC',
                    '1'
                );
                if ($undoButtonR = $this->getDatabaseConnection()->sql_fetch_assoc($undoRes)) {
                    $aOnClick = 'window.location.href=' .
                        GeneralUtility::quoteJSvalue(
                            BackendUtility::getModuleUrl(
                                'record_history',
                                [
                                    'element' => $this->firstEl['table'] . ':' . $this->firstEl['uid'],
                                    'revert' => 'ALL_FIELDS',
                                    'sumUp' => -1,
                                    'returnUrl' => $this->R_URI,
                                ]
                            )
                        ) . '; return false;';

                    $undoButton = $buttonBar->makeLinkButton()
                        ->setHref('#')
                        ->setOnClick($aOnClick)
                        ->setTitle(
                            sprintf(
                                $lang->getLL('undoLastChange'),
                                BackendUtility::calcAge(
                                    ($GLOBALS['EXEC_TIME'] - $undoButtonR['tstamp']),
                                    $lang->sL('LLL:EXT:lang/locallang_core.xlf:labels.minutesHoursDaysYears')
                                )
                            )
                        )
                        ->setIcon($this->moduleTemplate->getIconFactory()->getIcon(
                            'actions-document-history-open',
                            Icon::SIZE_SMALL
                        ));
                    $buttonBar->addButton($undoButton, ButtonBar::BUTTON_POSITION_LEFT, 3);
                }
                if ($this->getNewIconMode($this->firstEl['table'], 'showHistory')) {
                    $aOnClick = 'window.location.href=' .
                        GeneralUtility::quoteJSvalue(
                            BackendUtility::getModuleUrl(
                                'record_history',
                                [
                                    'element' => $this->firstEl['table'] . ':' . $this->firstEl['uid'],
                                    'returnUrl' => $this->R_URI,
                                ]
                            )
                        ) . '; return false;';

                    $historyButton = $buttonBar->makeLinkButton()
                        ->setHref('#')
                        ->setOnClick($aOnClick)
                        ->setTitle('Open history of this record')
                        ->setIcon($this->moduleTemplate->getIconFactory()->getIcon(
                            'actions-document-history-open',
                            Icon::SIZE_SMALL
                        ));
                    $buttonBar->addButton($historyButton, ButtonBar::BUTTON_POSITION_LEFT, 3);
                }
                // If only SOME fields are shown in the form, this will link the user to the FULL form:
                if ($this->columnsOnly) {
                    $columnsOnlyButton = $buttonBar->makeLinkButton()
                        ->setHref($this->R_URI . '&columnsOnly=')
                        ->setTitle($lang->getLL('editWholeRecord'))
                        ->setIcon($this->moduleTemplate->getIconFactory()->getIcon(
                            'actions-document-open',
                            Icon::SIZE_SMALL
                        ));
                    $buttonBar->addButton($columnsOnlyButton, ButtonBar::BUTTON_POSITION_LEFT, 3);
                }
            }
        }
        $cshButton = $buttonBar->makeHelpButton()->setModuleName('xMOD_csh_corebe')->setFieldName('TCEforms');
        $buttonBar->addButton($cshButton);
        $this->shortCutLink();
        $this->openInNewWindowLink();
    }

    /**
     * Put together the various elements (buttons, selectors, form) into a table
     *
     * @param string $editForm HTML form.
     * @return string Composite HTML
     */
    public function compileForm($editForm)
    {
        $formContent = '
			<!-- EDITING FORM -->
			<form
            action="' . htmlspecialchars($this->R_URI) . '"
            method="post"
            enctype="multipart/form-data"
            name="editform"
            id="EditDocumentController"
            onsubmit="TBE_EDITOR.checkAndDoSubmit(1); return false;">
			' . $editForm . '

			<input type="hidden" name="returnUrl" value="' . htmlspecialchars($this->retUrl) . '" />
			<input type="hidden" name="viewUrl" value="' . htmlspecialchars($this->viewUrl) . '" />';
        if ($this->returnNewPageId) {
            $formContent .= '<input type="hidden" name="returnNewPageId" value="1" />';
        }
        $formContent .= '<input type="hidden" name="popViewId" value="' . htmlspecialchars($this->viewId) . '" />';
        if ($this->viewId_addParams) {
            $formContent .= '<input type="hidden" name="popViewId_addParams" value="' . htmlspecialchars($this->viewId_addParams) . '" />';
        }
        $formContent .= '
			<input type="hidden" name="closeDoc" value="0" />
			<input type="hidden" name="doSave" value="0" />
			<input type="hidden" name="_serialNumber" value="' . md5(microtime()) . '" />
			<input type="hidden" name="_scrollPosition" value="" />';
        return $formContent;
    }

    /**
     * Create shortcut icon
     */
    public function shortCutLink()
    {
        if ($this->returnUrl !== ExtensionManagementUtility::extRelPath('backend') . 'Resources/Private/Templates/Close.html') {
            $shortCutButton = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar()->makeShortcutButton();
            $shortCutButton->setModuleName($this->MCONF['name'])
                ->setGetVariables([
                    'returnUrl',
                    'edit',
                    'defVals',
                    'overrideVals',
                    'columnsOnly',
                    'returnNewPageId',
                    'editRegularContentFromId',
                    'noView']);
            $this->moduleTemplate->getDocHeaderComponent()->getButtonBar()->addButton($shortCutButton);
        }
    }

    /**
     * Creates open-in-window link
     */
    public function openInNewWindowLink()
    {
        $backendRelPath = ExtensionManagementUtility::extRelPath('backend');
        if ($this->returnUrl !== $backendRelPath . 'Resources/Private/Templates/Close.html') {
            $aOnClick = 'vHWin=window.open(' . GeneralUtility::quoteJSvalue(GeneralUtility::linkThisScript(
                ['returnUrl' => $backendRelPath . 'Resources/Private/Templates/Close.html']
            ))
                . ','
                . GeneralUtility::quoteJSvalue(md5($this->R_URI))
                . ',\'width=670,height=500,status=0,menubar=0,scrollbars=1,resizable=1\');vHWin.focus();return false;';
            $openInNewWindowButton = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar()
                ->makeLinkButton()
                ->setHref('#')
                ->setTitle($this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:labels.openInNewWindow'))
                ->setIcon($this->moduleTemplate->getIconFactory()->getIcon('actions-window-open', Icon::SIZE_SMALL))
                ->setOnClick($aOnClick);
            $this->moduleTemplate->getDocHeaderComponent()->getButtonBar()->addButton(
                $openInNewWindowButton,
                ButtonBar::BUTTON_POSITION_RIGHT
            );
        }
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
     * @param int $pid Pid of the record
     */
    public function languageSwitch($table, $uid, $pid = null)
    {
        $languageField = $GLOBALS['TCA'][$table]['ctrl']['languageField'];
        $transOrigPointerField = $GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField'];
        // Table editable and activated for languages?
        if ($this->getBackendUser()->check('tables_modify', $table)
            && $languageField
            && $transOrigPointerField && !$GLOBALS['TCA'][$table]['ctrl']['transOrigPointerTable']
        ) {
            if (is_null($pid)) {
                $row = BackendUtility::getRecord($table, $uid, 'pid');
                $pid = $row['pid'];
            }
            // Get all available languages for the page
            $langRows = $this->getLanguages($pid);
            // Page available in other languages than default language?
            if (is_array($langRows) && count($langRows) > 1) {
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
                        $translations = $this->getDatabaseConnection()->exec_SELECTgetRows(
                            $fetchFields,
                            $table,
                            'pid=' . (int)$pid . ' AND ' . $languageField . '>0' . ' AND ' . $transOrigPointerField . '=' . (int)$rowsByLang[0]['uid'] . BackendUtility::deleteClause($table) . BackendUtility::versioningPlaceholderClause($table)
                        );
                        foreach ($translations as $row) {
                            $rowsByLang[$row[$languageField]] = $row;
                        }
                    }
                    $languageMenu = $this->moduleTemplate->getDocHeaderComponent()->getMenuRegistry()->makeMenu();
                    $languageMenu->setIdentifier('_langSelector');
                    $languageMenu->setLabel($this->getLanguageService()->sL(
                        'LLL:EXT:lang/locallang_general.xlf:LGL.language',
                        true
                    ));
                    foreach ($langRows as $lang) {
                        if ($this->getBackendUser()->checkLanguageAccess($lang['uid'])) {
                            $newTranslation = isset($rowsByLang[$lang['uid']]) ? '' : ' [' . $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:labels.new', true) . ']';
                            // Create url for creating a localized record
                            $addOption = true;
                            if ($newTranslation) {
                                $redirectUrl = BackendUtility::getModuleUrl('record_edit', [
                                    'justLocalized' => $table . ':' . $rowsByLang[0]['uid'] . ':' . $lang['uid'],
                                    'returnUrl' => $this->retUrl
                                ]);

                                if (array_key_exists(0, $rowsByLang)) {
                                    $href = BackendUtility::getLinkToDataHandlerAction(
                                        '&cmd[' . $table . '][' . $rowsByLang[0]['uid'] . '][localize]=' . $lang['uid'],
                                        $redirectUrl
                                    );
                                } else {
                                    $addOption = false;
                                }
                            } else {
                                $href = BackendUtility::getModuleUrl('record_edit', [
                                    'edit[' . $table . '][' . $rowsByLang[$lang['uid']]['uid'] . ']' => 'edit',
                                    'returnUrl' => $this->retUrl
                                ]);
                            }
                            if ($addOption) {
                                $menuItem = $languageMenu->makeMenuItem()
                                                         ->setTitle($lang['title'] . $newTranslation)
                                                         ->setHref($href);
                                if ((int)$lang['uid'] === $currentLanguage) {
                                    $menuItem->setActive(true);
                                }
                                $languageMenu->addMenuItem($menuItem);
                            }
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
     * @param string $justLocalized String passed by GET &justLocalized=
     * @return void
     */
    public function localizationRedirect($justLocalized)
    {
        list($table, $orig_uid, $language) = explode(':', $justLocalized);
        if ($GLOBALS['TCA'][$table]
            && $GLOBALS['TCA'][$table]['ctrl']['languageField']
            && $GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField']
        ) {
            $localizedRecord = $this->getDatabaseConnection()->exec_SELECTgetSingleRow(
                'uid',
                $table,
                $GLOBALS['TCA'][$table]['ctrl']['languageField'] . '=' . (int)$language . ' AND '
                . $GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField'] . '=' . (int)$orig_uid
                . BackendUtility::deleteClause($table) . BackendUtility::versioningPlaceholderClause($table)
            );
            if (is_array($localizedRecord)) {
                // Create parameters and finally run the classic page module for creating a new page translation
                $location = BackendUtility::getModuleUrl('record_edit', [
                    'edit[' . $table . '][' . $localizedRecord['uid'] . ']' => 'edit',
                    'returnUrl' => GeneralUtility::sanitizeLocalUrl(GeneralUtility::_GP('returnUrl'))
                ]);
                HttpUtility::redirect($location);
            }
        }
    }

    /**
     * Returns sys_language records available for record translations on given page.
     *
     * @param int $id Page id: If zero, the query will select all sys_language records from root level which are NOT
     *                hidden. If set to another value, the query will select all sys_language records that has a
     *                pages_language_overlay record on that page (and is not hidden, unless you are admin user)
     * @return array Language records including faked record for default language
     */
    public function getLanguages($id)
    {
        $modSharedTSconfig = BackendUtility::getModTSconfig($id, 'mod.SHARED');
        // Fallback non sprite-configuration
        if (preg_match('/\\.gif$/', $modSharedTSconfig['properties']['defaultLanguageFlag'])) {
            $modSharedTSconfig['properties']['defaultLanguageFlag'] = str_replace(
                '.gif',
                '',
                $modSharedTSconfig['properties']['defaultLanguageFlag']
            );
        }
        $languages = [
            0 => [
                'uid' => 0,
                'pid' => 0,
                'hidden' => 0,
                'title' => $modSharedTSconfig['properties']['defaultLanguageLabel'] !== ''
                        ? $modSharedTSconfig['properties']['defaultLanguageLabel'] . ' (' . $this->getLanguageService()->sL('LLL:EXT:lang/locallang_mod_web_list.xlf:defaultLanguage') . ')'
                        : $this->getLanguageService()->sL('LLL:EXT:lang/locallang_mod_web_list.xlf:defaultLanguage'),
                'flag' => $modSharedTSconfig['properties']['defaultLanguageFlag']
            ]
        ];
        $exQ = $this->getBackendUser()->isAdmin() ? '' : ' AND sys_language.hidden=0';
        if ($id) {
            $rows = $this->getDatabaseConnection()->exec_SELECTgetRows(
                'sys_language.*',
                'pages_language_overlay,sys_language',
                'pages_language_overlay.sys_language_uid=sys_language.uid
                    AND pages_language_overlay.pid=' . (int)$id . BackendUtility::deleteClause('pages_language_overlay')
                . $exQ,
                'pages_language_overlay.sys_language_uid,
                sys_language.uid,
                sys_language.pid,
                sys_language.tstamp,
                sys_language.hidden,
                sys_language.title,
                sys_language.language_isocode,
                sys_language.static_lang_isocode,
                sys_language.flag',
                'sys_language.title'
            );
        } else {
            $rows = $this->getDatabaseConnection()->exec_SELECTgetRows(
                'sys_language.*',
                'sys_language',
                'sys_language.hidden=0',
                '',
                'sys_language.title'
            );
        }
        if ($rows) {
            foreach ($rows as $row) {
                $languages[$row['uid']] = $row;
            }
        }
        return $languages;
    }

    /***************************
     *
     * Other functions
     *
     ***************************/
    /**
     * Fix $this->editconf if versioning applies to any of the records
     *
     * @param array|bool $mapArray Mapping between old and new ids if auto-versioning has been performed.
     * @return void
     */
    public function fixWSversioningInEditConf($mapArray = false)
    {
        // Traverse the editConf array
        if (is_array($this->editconf)) {
            // Tables:
            foreach ($this->editconf as $table => $conf) {
                if (is_array($conf) && $GLOBALS['TCA'][$table]) {
                    // Traverse the keys/comments of each table (keys can be a commalist of uids)
                    $newConf = [];
                    foreach ($conf as $cKey => $cmd) {
                        if ($cmd == 'edit') {
                            // Traverse the ids:
                            $ids = GeneralUtility::trimExplode(',', $cKey, true);
                            foreach ($ids as $idKey => $theUid) {
                                if (is_array($mapArray)) {
                                    if ($mapArray[$table][$theUid]) {
                                        $ids[$idKey] = $mapArray[$table][$theUid];
                                    }
                                } else {
                                    // Default, look for versions in workspace for record:
                                    $calcPRec = $this->getRecordForEdit($table, $theUid);
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
     * @return array Returns record to edit, FALSE if none
     */
    public function getRecordForEdit($table, $theUid)
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
                        // that is handled inside TCEmain then and in the interface it would clearly be an error of
                        // links if the user accesses such a scenario)
                        return $reqRecord;
                    } else {
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
                } else {
                    // This means that editing cannot occur on this record because it was not supporting versioning
                    // which is required inside an offline workspace.
                    return false;
                }
            } else {
                // In ONLINE workspace, just return the originally requested record:
                return $reqRecord;
            }
        } else {
            // Return FALSE because the table/uid was not found anyway.
            return false;
        }
    }

    /**
     * Function, which populates the internal editconf array with editing commands for all tt_content elements from
     * the normal column in normal language from the page pointed to by $this->editRegularContentFromId
     *
     * @return void
     * @deprecated since TYPO3 CMS 7, will be removed with TYPO3 CMS 8
     */
    public function editRegularContentFromId()
    {
        GeneralUtility::logDeprecatedFunction();
        $dbConnection = $this->getDatabaseConnection();
        $res = $dbConnection->exec_SELECTquery(
            'uid',
            'tt_content',
            'pid=' . (int)$this->editRegularContentFromId . BackendUtility::deleteClause('tt_content')
            . BackendUtility::versioningPlaceholderClause('tt_content') . ' AND colPos=0 AND sys_language_uid=0',
            '',
            'sorting'
        );
        if ($dbConnection->sql_num_rows($res)) {
            $ecUids = [];
            while ($ecRec = $dbConnection->sql_fetch_assoc($res)) {
                $ecUids[] = $ecRec['uid'];
            }
            $this->editconf['tt_content'][implode(',', $ecUids)] = 'edit';
        }
        $dbConnection->sql_free_result($res);
    }

    /**
     * Populates the variables $this->storeArray, $this->storeUrl, $this->storeUrlMd5
     *
     * @return void
     * @see makeDocSel()
     */
    public function compileStoreDat()
    {
        $this->storeArray = GeneralUtility::compileSelectedGetVarsFromArray(
            'edit,defVals,overrideVals,columnsOnly,noView,editRegularContentFromId,workspace',
            $this->R_URL_getvars
        );
        $this->storeUrl = GeneralUtility::implodeArrayForUrl('', $this->storeArray);
        $this->storeUrlMd5 = md5($this->storeUrl);
    }

    /**
     * Function used to look for configuration of buttons in the form: Fx. disabling buttons or showing them at various
     * positions.
     *
     * @param string $table The table for which the configuration may be specific
     * @param string $key The option for look for. Default is checking if the saveDocNew button should be displayed.
     * @return string Return value fetched from USER TSconfig
     */
    public function getNewIconMode($table, $key = 'saveDocNew')
    {
        $TSconfig = $this->getBackendUser()->getTSConfig('options.' . $key);
        $output = trim(isset($TSconfig['properties'][$table]) ? $TSconfig['properties'][$table] : $TSconfig['value']);
        return $output;
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
     * @return void
     */
    public function closeDocument($mode = self::DOCUMENT_CLOSE_MODE_DEFAULT)
    {
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
        if ($mode !== self::DOCUMENT_CLOSE_MODE_NO_REDIRECT) {
            // If ->returnEditConf is set, then add the current content of editconf to the ->retUrl variable: (used by
            // other scripts, like wizard_add, to know which records was created or so...)
            if ($this->returnEditConf && $this->retUrl != BackendUtility::getModuleUrl('dummy')) {
                $this->retUrl .= '&returnEditConf=' . rawurlencode(json_encode($this->editconf));
            }

            // If mode is NOT set (means 0) OR set to 1, then make a header location redirect to $this->retUrl
            if ($mode === self::DOCUMENT_CLOSE_MODE_DEFAULT || $mode === self::DOCUMENT_CLOSE_MODE_REDIRECT) {
                HttpUtility::redirect($this->retUrl);
            } else {
                $this->setDocument('', $this->retUrl);
            }
        }
    }

    /**
     * Redirects to the document pointed to by $currentDocFromHandlerMD5 OR $retUrl (depending on some internal
     * calculations).
     * Most likely you will get a header-location redirect from this function.
     *
     * @param string $currentDocFromHandlerMD5 Pointer to the document in the docHandler array
     * @param string $retUrl Alternative/Default retUrl
     * @return void
     */
    public function setDocument($currentDocFromHandlerMD5 = '', $retUrl = '')
    {
        if ($retUrl === '') {
            return;
        }
        if (!$this->modTSconfig['properties']['disableDocSelector']
            && is_array($this->docHandler)
            && !empty($this->docHandler)
        ) {
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
     * Injects the request object for the current request or subrequest
     *
     * @param ServerRequestInterface $request the current request
     * @param ResponseInterface $response
     * @return ResponseInterface the response with the content
     */
    public function mainAction(ServerRequestInterface $request, ResponseInterface $response)
    {
        BackendUtility::lockRecords();

        // Preprocessing, storing data if submitted to
        $this->preInit();

        // Checks, if a save button has been clicked (or the doSave variable is sent)
        if ($this->doProcessData()) {
            $this->processData();
        }

        $this->init();
        $this->main();

        $response->getBody()->write($this->moduleTemplate->renderContent());
        return $response;
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
     * @return \TYPO3\CMS\Lang\LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }

    /**
     * Returns the database connection
     *
     * @return \TYPO3\CMS\Core\Database\DatabaseConnection
     */
    protected function getDatabaseConnection()
    {
        return $GLOBALS['TYPO3_DB'];
    }
}
