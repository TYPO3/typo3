<?php
namespace TYPO3\CMS\Frontend\ContentObject;

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

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Driver\Statement;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Authentication\AbstractUserAuthentication;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Configuration\Features;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\LanguageAspect;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\QueryHelper;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\FrontendRestrictionContainer;
use TYPO3\CMS\Core\Html\HtmlParser;
use TYPO3\CMS\Core\Html\SanitizerBuilderFactory;
use TYPO3\CMS\Core\Html\SanitizerInitiator;
use TYPO3\CMS\Core\Imaging\ImageManipulation\Area;
use TYPO3\CMS\Core\Imaging\ImageManipulation\CropVariantCollection;
use TYPO3\CMS\Core\LinkHandling\LinkService;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Mail\MailMessage;
use TYPO3\CMS\Core\Resource\Exception;
use TYPO3\CMS\Core\Resource\Exception\ResourceDoesNotExistException;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Service\DependencyOrderingService;
use TYPO3\CMS\Core\Service\FlexFormService;
use TYPO3\CMS\Core\Service\MarkerBasedTemplateService;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\TimeTracker\TimeTracker;
use TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser;
use TYPO3\CMS\Core\TypoScript\TypoScriptService;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\DebugUtility;
use TYPO3\CMS\Core\Utility\Exception\MissingArrayPathException;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\HttpUtility;
use TYPO3\CMS\Core\Utility\MailUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\CMS\Core\Versioning\VersionState;
use TYPO3\CMS\Frontend\ContentObject\Exception\ContentRenderingException;
use TYPO3\CMS\Frontend\ContentObject\Exception\ExceptionHandlerInterface;
use TYPO3\CMS\Frontend\ContentObject\Exception\ProductionExceptionHandler;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\CMS\Frontend\Http\UrlProcessorInterface;
use TYPO3\CMS\Frontend\Imaging\GifBuilder;
use TYPO3\CMS\Frontend\Page\PageRepository;
use TYPO3\CMS\Frontend\Resource\FilePathSanitizer;
use TYPO3\CMS\Frontend\Service\TypoLinkCodecService;
use TYPO3\CMS\Frontend\Typolink\AbstractTypolinkBuilder;
use TYPO3\CMS\Frontend\Typolink\UnableToLinkException;
use TYPO3\HtmlSanitizer\Builder\BuilderInterface;

/**
 * This class contains all main TypoScript features.
 * This includes the rendering of TypoScript content objects (cObjects).
 * Is the backbone of TypoScript Template rendering.
 *
 * There are lots of functions you can use from your include-scripts.
 * The class is normally instantiated and referred to as "cObj".
 * When you call your own PHP-code typically through a USER or USER_INT cObject then it is this class that instantiates the object and calls the main method. Before it does so it will set (if you are using classes) a reference to itself in the internal variable "cObj" of the object. Thus you can access all functions and data from this class by $this->cObj->... from within you classes written to be USER or USER_INT content objects.
 */
class ContentObjectRenderer implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var array
     */
    public $align = [
        'center',
        'right',
        'left'
    ];

    /**
     * stdWrap functions in their correct order
     *
     * @see stdWrap()
     */
    public $stdWrapOrder = [
        'stdWrapPreProcess' => 'hook',
        // this is a placeholder for the first Hook
        'cacheRead' => 'hook',
        // this is a placeholder for checking if the content is available in cache
        'setContentToCurrent' => 'boolean',
        'setContentToCurrent.' => 'array',
        'addPageCacheTags' => 'string',
        'addPageCacheTags.' => 'array',
        'setCurrent' => 'string',
        'setCurrent.' => 'array',
        'lang.' => 'array',
        'data' => 'getText',
        'data.' => 'array',
        'field' => 'fieldName',
        'field.' => 'array',
        'current' => 'boolean',
        'current.' => 'array',
        'cObject' => 'cObject',
        'cObject.' => 'array',
        'numRows.' => 'array',
        // @deprecated - will be removed in TYPO3 v10.0.
        'filelist' => 'dir',
        // @deprecated - will be removed in TYPO3 v10.0.
        'filelist.' => 'array',
        'preUserFunc' => 'functionName',
        'stdWrapOverride' => 'hook',
        // this is a placeholder for the second Hook
        'override' => 'string',
        'override.' => 'array',
        'preIfEmptyListNum' => 'listNum',
        'preIfEmptyListNum.' => 'array',
        'ifNull' => 'string',
        'ifNull.' => 'array',
        'ifEmpty' => 'string',
        'ifEmpty.' => 'array',
        'ifBlank' => 'string',
        'ifBlank.' => 'array',
        'listNum' => 'listNum',
        'listNum.' => 'array',
        'trim' => 'boolean',
        'trim.' => 'array',
        'strPad.' => 'array',
        'stdWrap' => 'stdWrap',
        'stdWrap.' => 'array',
        'stdWrapProcess' => 'hook',
        // this is a placeholder for the third Hook
        'required' => 'boolean',
        'required.' => 'array',
        'if.' => 'array',
        'fieldRequired' => 'fieldName',
        'fieldRequired.' => 'array',
        'csConv' => 'string',
        'csConv.' => 'array',
        'parseFunc' => 'objectpath',
        'parseFunc.' => 'array',
        'HTMLparser' => 'boolean',
        'HTMLparser.' => 'array',
        'split.' => 'array',
        'replacement.' => 'array',
        'prioriCalc' => 'boolean',
        'prioriCalc.' => 'array',
        'char' => 'integer',
        'char.' => 'array',
        'intval' => 'boolean',
        'intval.' => 'array',
        'hash' => 'string',
        'hash.' => 'array',
        'round' => 'boolean',
        'round.' => 'array',
        'numberFormat.' => 'array',
        'expandList' => 'boolean',
        'expandList.' => 'array',
        'date' => 'dateconf',
        'date.' => 'array',
        'strtotime' => 'strtotimeconf',
        'strtotime.' => 'array',
        'strftime' => 'strftimeconf',
        'strftime.' => 'array',
        'age' => 'boolean',
        'age.' => 'array',
        'case' => 'case',
        'case.' => 'array',
        'bytes' => 'boolean',
        'bytes.' => 'array',
        'substring' => 'parameters',
        'substring.' => 'array',
        'cropHTML' => 'crop',
        'cropHTML.' => 'array',
        'stripHtml' => 'boolean',
        'stripHtml.' => 'array',
        'crop' => 'crop',
        'crop.' => 'array',
        'rawUrlEncode' => 'boolean',
        'rawUrlEncode.' => 'array',
        'htmlSpecialChars' => 'boolean',
        'htmlSpecialChars.' => 'array',
        'encodeForJavaScriptValue' => 'boolean',
        'encodeForJavaScriptValue.' => 'array',
        'doubleBrTag' => 'string',
        'doubleBrTag.' => 'array',
        'br' => 'boolean',
        'br.' => 'array',
        'brTag' => 'string',
        'brTag.' => 'array',
        'encapsLines.' => 'array',
        'keywords' => 'boolean',
        'keywords.' => 'array',
        'innerWrap' => 'wrap',
        'innerWrap.' => 'array',
        'innerWrap2' => 'wrap',
        'innerWrap2.' => 'array',
        // @deprecated - will be removed in TYPO3 v10.0.
        'addParams.' => 'array',
        // @deprecated - will be removed in TYPO3 v10.0.
        'filelink.' => 'array',
        'preCObject' => 'cObject',
        'preCObject.' => 'array',
        'postCObject' => 'cObject',
        'postCObject.' => 'array',
        'wrapAlign' => 'align',
        'wrapAlign.' => 'array',
        'typolink.' => 'array',
        'wrap' => 'wrap',
        'wrap.' => 'array',
        'noTrimWrap' => 'wrap',
        'noTrimWrap.' => 'array',
        'wrap2' => 'wrap',
        'wrap2.' => 'array',
        'dataWrap' => 'dataWrap',
        'dataWrap.' => 'array',
        'prepend' => 'cObject',
        'prepend.' => 'array',
        'append' => 'cObject',
        'append.' => 'array',
        'wrap3' => 'wrap',
        'wrap3.' => 'array',
        'orderedStdWrap' => 'stdWrap',
        'orderedStdWrap.' => 'array',
        'outerWrap' => 'wrap',
        'outerWrap.' => 'array',
        'insertData' => 'boolean',
        'insertData.' => 'array',
        'postUserFunc' => 'functionName',
        'postUserFuncInt' => 'functionName',
        'prefixComment' => 'string',
        'prefixComment.' => 'array',
        'editIcons' => 'string',
        'editIcons.' => 'array',
        'editPanel' => 'boolean',
        'editPanel.' => 'array',
        'htmlSanitize' => 'boolean',
        'htmlSanitize.' => 'array',
        'cacheStore' => 'hook',
        // this is a placeholder for storing the content in cache
        'stdWrapPostProcess' => 'hook',
        // this is a placeholder for the last Hook
        'debug' => 'boolean',
        'debug.' => 'array',
        'debugFunc' => 'boolean',
        'debugFunc.' => 'array',
        'debugData' => 'boolean',
        'debugData.' => 'array'
    ];

    /**
     * Class names for accordant content object names
     *
     * @var array
     */
    protected $contentObjectClassMap = [];

    /**
     * Loaded with the current data-record.
     *
     * If the instance of this class is used to render records from the database those records are found in this array.
     * The function stdWrap has TypoScript properties that fetch field-data from this array.
     *
     * @var array
     * @see start()
     */
    public $data = [];

    /**
     * @var string
     */
    protected $table = '';

    /**
     * Used for backup
     *
     * @var array
     */
    public $oldData = [];

    /**
     * If this is set with an array before stdWrap, it's used instead of $this->data in the data-property in stdWrap
     *
     * @var string
     */
    public $alternativeData = '';

    /**
     * Used by the parseFunc function and is loaded with tag-parameters when parsing tags.
     *
     * @var array
     */
    public $parameters = [];

    /**
     * @var string
     */
    public $currentValKey = 'currentValue_kidjls9dksoje';

    /**
     * This is set to the [table]:[uid] of the record delivered in the $data-array, if the cObjects CONTENT or RECORD is in operation.
     * Note that $GLOBALS['TSFE']->currentRecord is set to an equal value but always indicating the latest record rendered.
     *
     * @var string
     */
    public $currentRecord = '';

    /**
     * Set in RecordsContentObject and ContentContentObject to the current number of records selected in a query.
     *
     * @var int
     */
    public $currentRecordTotal = 0;

    /**
     * Incremented in RecordsContentObject and ContentContentObject before each record rendering.
     *
     * @var int
     */
    public $currentRecordNumber = 0;

    /**
     * Incremented in RecordsContentObject and ContentContentObject before each record rendering.
     *
     * @var int
     */
    public $parentRecordNumber = 0;

    /**
     * If the ContentObjectRender was started from ContentContentObject, RecordsContentObject or SearchResultContentObject this array has two keys, 'data' and 'currentRecord' which indicates the record and data for the parent cObj.
     *
     * @var array
     */
    public $parentRecord = [];

    /**
     * This is used by checkPid, that checks if pages are accessible. The $checkPid_cache['page_uid'] is set TRUE or FALSE upon this check featuring a caching function for the next request.
     *
     * @var array
     */
    public $checkPid_cache = [];

    /**
     * @var string
     */
    public $checkPid_badDoktypeList = '255';

    /**
     * This will be set by typoLink() to the url of the most recent link created.
     *
     * @var string
     */
    public $lastTypoLinkUrl = '';

    /**
     * DO. link target.
     *
     * @var string
     */
    public $lastTypoLinkTarget = '';

    /**
     * @var array
     */
    public $lastTypoLinkLD = [];

    /**
     * array that registers rendered content elements (or any table) to make sure they are not rendered recursively!
     *
     * @var array
     */
    public $recordRegister = [];

    /**
     * Additionally registered content object types and class names
     *
     * @var array
     */
    protected $cObjHookObjectsRegistry = [];

    /**
     * @var array
     */
    public $cObjHookObjectsArr = [];

    /**
     * Containing hook objects for stdWrap
     *
     * @var array
     */
    protected $stdWrapHookObjects = [];

    /**
     * Containing hook objects for getImgResource
     *
     * @var array
     */
    protected $getImgResourceHookObjects;

    /**
     * @var File Current file objects (during iterations over files)
     */
    protected $currentFile;

    /**
     * Set to TRUE by doConvertToUserIntObject() if USER object wants to become USER_INT
     */
    public $doConvertToUserIntObject = false;

    /**
     * Indicates current object type. Can hold one of OBJECTTYPE_ constants or FALSE.
     * The value is set and reset inside USER() function. Any time outside of
     * USER() it is FALSE.
     */
    protected $userObjectType = false;

    /**
     * @var array
     */
    protected $stopRendering = [];

    /**
     * @var int
     */
    protected $stdWrapRecursionLevel = 0;

    /**
     * @var TypoScriptFrontendController
     */
    protected $typoScriptFrontendController;

    /**
     * Indicates that object type is USER.
     *
     * @see ContentObjectRender::$userObjectType
     */
    const OBJECTTYPE_USER_INT = 1;
    /**
     * Indicates that object type is USER.
     *
     * @see ContentObjectRender::$userObjectType
     */
    const OBJECTTYPE_USER = 2;

    /**
     * @param TypoScriptFrontendController $typoScriptFrontendController
     */
    public function __construct(TypoScriptFrontendController $typoScriptFrontendController = null)
    {
        $this->typoScriptFrontendController = $typoScriptFrontendController;
        $this->contentObjectClassMap = $GLOBALS['TYPO3_CONF_VARS']['FE']['ContentObjects'];
    }

    /**
     * Prevent several objects from being serialized.
     * If currentFile is set, it is either a File or a FileReference object. As the object itself can't be serialized,
     * we have store a hash and restore the object in __wakeup()
     *
     * @return array
     */
    public function __sleep()
    {
        $vars = get_object_vars($this);
        unset($vars['typoScriptFrontendController'], $vars['logger']);
        if ($this->currentFile instanceof FileReference) {
            $this->currentFile = 'FileReference:' . $this->currentFile->getUid();
        } elseif ($this->currentFile instanceof File) {
            $this->currentFile = 'File:' . $this->currentFile->getIdentifier();
        } else {
            unset($vars['currentFile']);
        }
        return array_keys($vars);
    }

    /**
     * Restore currentFile from hash.
     * If currentFile references a File, the identifier equals file identifier.
     * If it references a FileReference the identifier equals the uid of the reference.
     */
    public function __wakeup()
    {
        if (isset($GLOBALS['TSFE'])) {
            $this->typoScriptFrontendController = $GLOBALS['TSFE'];
        }
        if ($this->currentFile !== null && is_string($this->currentFile)) {
            list($objectType, $identifier) = explode(':', $this->currentFile, 2);
            try {
                if ($objectType === 'File') {
                    $this->currentFile = ResourceFactory::getInstance()->retrieveFileOrFolderObject($identifier);
                } elseif ($objectType === 'FileReference') {
                    $this->currentFile = ResourceFactory::getInstance()->getFileReferenceObject($identifier);
                }
            } catch (ResourceDoesNotExistException $e) {
                $this->currentFile = null;
            }
        }
        $this->logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(__CLASS__);
    }

    /**
     * Allow injecting content object class map.
     *
     * This method is private API, please use configuration
     * $GLOBALS['TYPO3_CONF_VARS']['FE']['ContentObjects'] to add new content objects
     *
     * @internal
     * @param array $contentObjectClassMap
     */
    public function setContentObjectClassMap(array $contentObjectClassMap)
    {
        $this->contentObjectClassMap = $contentObjectClassMap;
    }

    /**
     * Register a single content object name to class name
     *
     * This method is private API, please use configuration
     * $GLOBALS['TYPO3_CONF_VARS']['FE']['ContentObjects'] to add new content objects
     *
     * @param string $className
     * @param string $contentObjectName
     * @internal
     */
    public function registerContentObjectClass($className, $contentObjectName)
    {
        $this->contentObjectClassMap[$contentObjectName] = $className;
    }

    /**
     * Class constructor.
     * Well, it has to be called manually since it is not a real constructor function.
     * So after making an instance of the class, call this function and pass to it a database record and the tablename from where the record is from. That will then become the "current" record loaded into memory and accessed by the .fields property found in eg. stdWrap.
     *
     * @param array $data The record data that is rendered.
     * @param string $table The table that the data record is from.
     */
    public function start($data, $table = '')
    {
        $this->data = $data;
        $this->table = $table;
        $this->currentRecord = $table !== ''
            ? $table . ':' . ($this->data['uid'] ?? '')
            : '';
        $this->parameters = [];
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_content.php']['cObjTypeAndClass'] ?? [] as $classArr) {
            $this->cObjHookObjectsRegistry[$classArr[0]] = $classArr[1];
        }
        $this->stdWrapHookObjects = [];
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_content.php']['stdWrap'] ?? [] as $className) {
            $hookObject = GeneralUtility::makeInstance($className);
            if (!$hookObject instanceof ContentObjectStdWrapHookInterface) {
                throw new \UnexpectedValueException($className . ' must implement interface ' . ContentObjectStdWrapHookInterface::class, 1195043965);
            }
            $this->stdWrapHookObjects[] = $hookObject;
        }
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_content.php']['postInit'] ?? [] as $className) {
            $postInitializationProcessor = GeneralUtility::makeInstance($className);
            if (!$postInitializationProcessor instanceof ContentObjectPostInitHookInterface) {
                throw new \UnexpectedValueException($className . ' must implement interface ' . ContentObjectPostInitHookInterface::class, 1274563549);
            }
            $postInitializationProcessor->postProcessContentObjectInitialization($this);
        }
    }

    /**
     * Returns the current table
     *
     * @return string
     */
    public function getCurrentTable()
    {
        return $this->table;
    }

    /**
     * Gets the 'getImgResource' hook objects.
     * The first call initializes the accordant objects.
     *
     * @return array The 'getImgResource' hook objects (if any)
     */
    protected function getGetImgResourceHookObjects()
    {
        if (!isset($this->getImgResourceHookObjects)) {
            $this->getImgResourceHookObjects = [];
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_content.php']['getImgResource'] ?? [] as $className) {
                $hookObject = GeneralUtility::makeInstance($className);
                if (!$hookObject instanceof ContentObjectGetImageResourceHookInterface) {
                    throw new \UnexpectedValueException('$hookObject must implement interface ' . ContentObjectGetImageResourceHookInterface::class, 1218636383);
                }
                $this->getImgResourceHookObjects[] = $hookObject;
            }
        }
        return $this->getImgResourceHookObjects;
    }

    /**
     * Sets the internal variable parentRecord with information about current record.
     * If the ContentObjectRender was started from CONTENT, RECORD or SEARCHRESULT cObject's this array has two keys, 'data' and 'currentRecord' which indicates the record and data for the parent cObj.
     *
     * @param array $data The record array
     * @param string $currentRecord This is set to the [table]:[uid] of the record delivered in the $data-array, if the cObjects CONTENT or RECORD is in operation. Note that $GLOBALS['TSFE']->currentRecord is set to an equal value but always indicating the latest record rendered.
     * @internal
     */
    public function setParent($data, $currentRecord)
    {
        $this->parentRecord = [
            'data' => $data,
            'currentRecord' => $currentRecord
        ];
    }

    /***********************************************
     *
     * CONTENT_OBJ:
     *
     ***********************************************/
    /**
     * Returns the "current" value.
     * The "current" value is just an internal variable that can be used by functions to pass a single value on to another function later in the TypoScript processing.
     * It's like "load accumulator" in the good old C64 days... basically a "register" you can use as you like.
     * The TSref will tell if functions are setting this value before calling some other object so that you know if it holds any special information.
     *
     * @return mixed The "current" value
     */
    public function getCurrentVal()
    {
        return $this->data[$this->currentValKey];
    }

    /**
     * Sets the "current" value.
     *
     * @param mixed $value The variable that you want to set as "current
     * @see getCurrentVal()
     */
    public function setCurrentVal($value)
    {
        $this->data[$this->currentValKey] = $value;
    }

    /**
     * Rendering of a "numerical array" of cObjects from TypoScript
     * Will call ->cObjGetSingle() for each cObject found and accumulate the output.
     *
     * @param array $setup array with cObjects as values.
     * @param string $addKey A prefix for the debugging information
     * @return string Rendered output from the cObjects in the array.
     * @see cObjGetSingle()
     */
    public function cObjGet($setup, $addKey = '')
    {
        if (!is_array($setup)) {
            return '';
        }
        $sKeyArray = ArrayUtility::filterAndSortByNumericKeys($setup);
        $content = '';
        foreach ($sKeyArray as $theKey) {
            $theValue = $setup[$theKey];
            if ((int)$theKey && strpos($theKey, '.') === false) {
                $conf = $setup[$theKey . '.'];
                $content .= $this->cObjGetSingle($theValue, $conf, $addKey . $theKey);
            }
        }
        return $content;
    }

    /**
     * Renders a content object
     *
     * @param string $name The content object name, eg. "TEXT" or "USER" or "IMAGE
     * @param array $conf The array with TypoScript properties for the content object
     * @param string $TSkey A string label used for the internal debugging tracking.
     * @return string cObject output
     * @throws \UnexpectedValueException
     */
    public function cObjGetSingle($name, $conf, $TSkey = '__')
    {
        $content = '';
        // Checking that the function is not called eternally. This is done by interrupting at a depth of 100
        $this->getTypoScriptFrontendController()->cObjectDepthCounter--;
        if ($this->getTypoScriptFrontendController()->cObjectDepthCounter > 0) {
            $timeTracker = $this->getTimeTracker();
            $name = trim($name);
            if ($timeTracker->LR) {
                $timeTracker->push($TSkey, $name);
            }
            // Checking if the COBJ is a reference to another object. (eg. name of 'some.object =< styles.something')
            if (isset($name[0]) && $name[0] === '<') {
                $key = trim(substr($name, 1));
                $cF = GeneralUtility::makeInstance(TypoScriptParser::class);
                // $name and $conf is loaded with the referenced values.
                $confOverride = is_array($conf) ? $conf : [];
                list($name, $conf) = $cF->getVal($key, $this->getTypoScriptFrontendController()->tmpl->setup);
                $conf = array_replace_recursive(is_array($conf) ? $conf : [], $confOverride);
                // Getting the cObject
                $timeTracker->incStackPointer();
                $content .= $this->cObjGetSingle($name, $conf, $key);
                $timeTracker->decStackPointer();
            } else {
                $hooked = false;
                // Application defined cObjects
                if (!empty($this->cObjHookObjectsRegistry[$name])) {
                    if (empty($this->cObjHookObjectsArr[$name])) {
                        $this->cObjHookObjectsArr[$name] = GeneralUtility::makeInstance($this->cObjHookObjectsRegistry[$name]);
                    }
                    $hookObj = $this->cObjHookObjectsArr[$name];
                    if (method_exists($hookObj, 'cObjGetSingleExt')) {
                        $content .= $hookObj->cObjGetSingleExt($name, $conf, $TSkey, $this);
                        $hooked = true;
                    }
                }
                if (!$hooked) {
                    $contentObject = $this->getContentObject($name);
                    if ($contentObject) {
                        $content .= $this->render($contentObject, $conf);
                    } else {
                        // Call hook functions for extra processing
                        if ($name) {
                            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_content.php']['cObjTypeAndClassDefault'] ?? [] as $className) {
                                $hookObject = GeneralUtility::makeInstance($className);
                                if (!$hookObject instanceof ContentObjectGetSingleHookInterface) {
                                    throw new \UnexpectedValueException('$hookObject must implement interface ' . ContentObjectGetSingleHookInterface::class, 1195043731);
                                }
                                /** @var ContentObjectGetSingleHookInterface $hookObject */
                                $content .= $hookObject->getSingleContentObject($name, (array)$conf, $TSkey, $this);
                            }
                        } else {
                            // Log error in AdminPanel
                            $warning = sprintf('Content Object "%s" does not exist', $name);
                            $timeTracker->setTSlogMessage($warning, 2);
                        }
                    }
                }
            }
            if ($timeTracker->LR) {
                $timeTracker->pull($content);
            }
        }
        // Increasing on exit...
        $this->getTypoScriptFrontendController()->cObjectDepthCounter++;
        return $content;
    }

    /**
     * Returns a new content object of type $name.
     * This content object needs to be registered as content object
     * in $this->contentObjectClassMap
     *
     * @param string $name
     * @return AbstractContentObject|null
     * @throws ContentRenderingException
     */
    public function getContentObject($name)
    {
        if (!isset($this->contentObjectClassMap[$name])) {
            return null;
        }
        $fullyQualifiedClassName = $this->contentObjectClassMap[$name];
        $contentObject = GeneralUtility::makeInstance($fullyQualifiedClassName, $this);
        if (!($contentObject instanceof AbstractContentObject)) {
            throw new ContentRenderingException(sprintf('Registered content object class name "%s" must be an instance of AbstractContentObject, but is not!', $fullyQualifiedClassName), 1422564295);
        }
        return $contentObject;
    }

    /********************************************
     *
     * Functions rendering content objects (cObjects)
     *
     ********************************************/

    /**
     * Renders a content object by taking exception and cache handling
     * into consideration
     *
     * @param AbstractContentObject $contentObject Content object instance
     * @param array $configuration Array of TypoScript properties
     *
     * @throws ContentRenderingException
     * @throws \Exception
     * @return string
     */
    public function render(AbstractContentObject $contentObject, $configuration = [])
    {
        $content = '';

        // Evaluate possible cache and return
        $cacheConfiguration = $configuration['cache.'] ?? null;
        if ($cacheConfiguration !== null) {
            unset($configuration['cache.']);
            $cache = $this->getFromCache($cacheConfiguration);
            if ($cache !== false) {
                return $cache;
            }
        }

        // Render content
        try {
            $content .= $contentObject->render($configuration);
        } catch (ContentRenderingException $exception) {
            // Content rendering Exceptions indicate a critical problem which should not be
            // caught e.g. when something went wrong with Exception handling itself
            throw $exception;
        } catch (\Exception $exception) {
            $exceptionHandler = $this->createExceptionHandler($configuration);
            if ($exceptionHandler === null) {
                throw $exception;
            }
            $content = $exceptionHandler->handle($exception, $contentObject, $configuration);
        }

        // Store cache
        if ($cacheConfiguration !== null && !$this->getTypoScriptFrontendController()->no_cache) {
            $key = $this->calculateCacheKey($cacheConfiguration);
            if (!empty($key)) {
                /** @var \TYPO3\CMS\Core\Cache\Frontend\FrontendInterface $cacheFrontend */
                $cacheFrontend = GeneralUtility::makeInstance(CacheManager::class)->getCache('cache_hash');
                $tags = $this->calculateCacheTags($cacheConfiguration);
                $lifetime = $this->calculateCacheLifetime($cacheConfiguration);
                $cacheFrontend->set($key, $content, $tags, $lifetime);
            }
        }

        return $content;
    }

    /**
     * Creates the content object exception handler from local content object configuration
     * or, from global configuration if not explicitly disabled in local configuration
     *
     * @param array $configuration
     * @return ExceptionHandlerInterface|null
     * @throws ContentRenderingException
     */
    protected function createExceptionHandler($configuration = [])
    {
        $exceptionHandler = null;
        $exceptionHandlerClassName = $this->determineExceptionHandlerClassName($configuration);
        if (!empty($exceptionHandlerClassName)) {
            $exceptionHandler = GeneralUtility::makeInstance($exceptionHandlerClassName, $this->mergeExceptionHandlerConfiguration($configuration));
            if (!$exceptionHandler instanceof ExceptionHandlerInterface) {
                throw new ContentRenderingException('An exception handler was configured but the class does not exist or does not implement the ExceptionHandlerInterface', 1403653369);
            }
        }

        return $exceptionHandler;
    }

    /**
     * Determine exception handler class name from global and content object configuration
     *
     * @param array $configuration
     * @return string|null
     */
    protected function determineExceptionHandlerClassName($configuration)
    {
        $exceptionHandlerClassName = null;
        $tsfe = $this->getTypoScriptFrontendController();
        if (!isset($tsfe->config['config']['contentObjectExceptionHandler'])) {
            if (GeneralUtility::getApplicationContext()->isProduction()) {
                $exceptionHandlerClassName = '1';
            }
        } else {
            $exceptionHandlerClassName = $tsfe->config['config']['contentObjectExceptionHandler'];
        }

        if (isset($configuration['exceptionHandler'])) {
            $exceptionHandlerClassName = $configuration['exceptionHandler'];
        }

        if ($exceptionHandlerClassName === '1') {
            $exceptionHandlerClassName = ProductionExceptionHandler::class;
        }

        return $exceptionHandlerClassName;
    }

    /**
     * Merges global exception handler configuration with the one from the content object
     * and returns the merged exception handler configuration
     *
     * @param array $configuration
     * @return array
     */
    protected function mergeExceptionHandlerConfiguration($configuration)
    {
        $exceptionHandlerConfiguration = [];
        $tsfe = $this->getTypoScriptFrontendController();
        if (!empty($tsfe->config['config']['contentObjectExceptionHandler.'])) {
            $exceptionHandlerConfiguration = $tsfe->config['config']['contentObjectExceptionHandler.'];
        }
        if (!empty($configuration['exceptionHandler.'])) {
            $exceptionHandlerConfiguration = array_replace_recursive($exceptionHandlerConfiguration, $configuration['exceptionHandler.']);
        }

        return $exceptionHandlerConfiguration;
    }

    /**
     * Retrieves a type of object called as USER or USER_INT. Object can detect their
     * type by using this call. It returns OBJECTTYPE_USER_INT or OBJECTTYPE_USER depending on the
     * current object execution. In all other cases it will return FALSE to indicate
     * a call out of context.
     *
     * @return mixed One of OBJECTTYPE_ class constants or FALSE
     */
    public function getUserObjectType()
    {
        return $this->userObjectType;
    }

    /**
     * Sets the user object type
     *
     * @param mixed $userObjectType
     */
    public function setUserObjectType($userObjectType)
    {
        $this->userObjectType = $userObjectType;
    }

    /**
     * Requests the current USER object to be converted to USER_INT.
     */
    public function convertToUserIntObject()
    {
        if ($this->userObjectType !== self::OBJECTTYPE_USER) {
            $this->getTimeTracker()->setTSlogMessage(self::class . '::convertToUserIntObject() is called in the wrong context or for the wrong object type', 2);
        } else {
            $this->doConvertToUserIntObject = true;
        }
    }

    /************************************
     *
     * Various helper functions for content objects:
     *
     ************************************/
    /**
     * Converts a given config in Flexform to a conf-array
     *
     * @param string|array $flexData Flexform data
     * @param array $conf Array to write the data into, by reference
     * @param bool $recursive Is set if called recursive. Don't call function with this parameter, it's used inside the function only
     */
    public function readFlexformIntoConf($flexData, &$conf, $recursive = false)
    {
        if ($recursive === false && is_string($flexData)) {
            $flexData = GeneralUtility::xml2array($flexData, 'T3');
        }
        if (is_array($flexData) && isset($flexData['data']['sDEF']['lDEF'])) {
            $flexData = $flexData['data']['sDEF']['lDEF'];
        }
        if (!is_array($flexData)) {
            return;
        }
        foreach ($flexData as $key => $value) {
            if (!is_array($value)) {
                continue;
            }
            if (isset($value['el'])) {
                if (is_array($value['el']) && !empty($value['el'])) {
                    foreach ($value['el'] as $ekey => $element) {
                        if (isset($element['vDEF'])) {
                            $conf[$ekey] = $element['vDEF'];
                        } else {
                            if (is_array($element)) {
                                $this->readFlexformIntoConf($element, $conf[$key][key($element)][$ekey], true);
                            } else {
                                $this->readFlexformIntoConf($element, $conf[$key][$ekey], true);
                            }
                        }
                    }
                } else {
                    $this->readFlexformIntoConf($value['el'], $conf[$key], true);
                }
            }
            if (isset($value['vDEF'])) {
                $conf[$key] = $value['vDEF'];
            }
        }
    }

    /**
     * Returns all parents of the given PID (Page UID) list
     *
     * @param string $pidList A list of page Content-Element PIDs (Page UIDs) / stdWrap
     * @param array $pidConf stdWrap array for the list
     * @return string A list of PIDs
     * @internal
     */
    public function getSlidePids($pidList, $pidConf)
    {
        $pidList = isset($pidConf) ? trim($this->stdWrap($pidList, $pidConf)) : trim($pidList);
        if ($pidList === '') {
            $pidList = 'this';
        }
        $tsfe = $this->getTypoScriptFrontendController();
        $listArr = null;
        if (trim($pidList)) {
            $listArr = GeneralUtility::intExplode(',', str_replace('this', $tsfe->contentPid, $pidList));
            $listArr = $this->checkPidArray($listArr);
        }
        $pidList = [];
        if (is_array($listArr) && !empty($listArr)) {
            foreach ($listArr as $uid) {
                $page = $tsfe->sys_page->getPage($uid);
                if (!$page['is_siteroot']) {
                    $pidList[] = $page['pid'];
                }
            }
        }
        return implode(',', $pidList);
    }

    /**
     * Returns a <img> tag with the image file defined by $file and processed according to the properties in the TypoScript array.
     * Mostly this function is a sub-function to the IMAGE function which renders the IMAGE cObject in TypoScript.
     * This function is called by "$this->cImage($conf['file'], $conf);" from IMAGE().
     *
     * @param string $file File TypoScript resource
     * @param array $conf TypoScript configuration properties
     * @return string <img> tag, (possibly wrapped in links and other HTML) if any image found.
     * @internal
     * @see IMAGE()
     */
    public function cImage($file, $conf)
    {
        $tsfe = $this->getTypoScriptFrontendController();
        $info = $this->getImgResource($file, $conf['file.']);
        $tsfe->lastImageInfo = $info;
        if (!is_array($info)) {
            return '';
        }
        if (is_file(Environment::getPublicPath() . '/' . $info['3'])) {
            $source = $tsfe->absRefPrefix . str_replace('%2F', '/', rawurlencode($info['3']));
        } else {
            $source = $info[3];
        }

        $layoutKey = $this->stdWrap($conf['layoutKey'], $conf['layoutKey.']);
        $imageTagTemplate = $this->getImageTagTemplate($layoutKey, $conf);
        $sourceCollection = $this->getImageSourceCollection($layoutKey, $conf, $file);

        // This array is used to collect the image-refs on the page...
        $tsfe->imagesOnPage[] = $source;
        $altParam = $this->getAltParam($conf);
        $params = $this->stdWrapValue('params', $conf);
        if ($params !== '' && $params[0] !== ' ') {
            $params = ' ' . $params;
        }

        $imageTagValues = [
            'width' =>  (int)$info[0],
            'height' => (int)$info[1],
            'src' => htmlspecialchars($source),
            'params' => $params,
            'altParams' => $altParam,
            'border' =>  $this->getBorderAttr(' border="' . (int)$conf['border'] . '"'),
            'sourceCollection' => $sourceCollection,
            'selfClosingTagSlash' => !empty($tsfe->xhtmlDoctype) ? ' /' : '',
        ];

        $markerTemplateEngine = GeneralUtility::makeInstance(MarkerBasedTemplateService::class);
        $theValue = $markerTemplateEngine->substituteMarkerArray($imageTagTemplate, $imageTagValues, '###|###', true, true);

        $linkWrap = isset($conf['linkWrap.']) ? $this->stdWrap($conf['linkWrap'], $conf['linkWrap.']) : $conf['linkWrap'];
        if ($linkWrap) {
            $theValue = $this->linkWrap($theValue, $linkWrap);
        } elseif ($conf['imageLinkWrap']) {
            $originalFile = !empty($info['originalFile']) ? $info['originalFile'] : $info['origFile'];
            $theValue = $this->imageLinkWrap($theValue, $originalFile, $conf['imageLinkWrap.']);
        }
        $wrap = isset($conf['wrap.']) ? $this->stdWrap($conf['wrap'], $conf['wrap.']) : $conf['wrap'];
        if ((string)$wrap !== '') {
            $theValue = $this->wrap($theValue, $conf['wrap']);
        }
        return $theValue;
    }

    /**
     * Returns the 'border' attribute for an <img> tag only if the doctype is not xhtml_strict, xhtml_11 or html5
     * or if the config parameter 'disableImgBorderAttr' is not set.
     *
     * @param string $borderAttr The border attribute
     * @return string The border attribute
     */
    public function getBorderAttr($borderAttr)
    {
        $tsfe = $this->getTypoScriptFrontendController();
        $docType = $tsfe->xhtmlDoctype;
        if (
            $docType !== 'xhtml_strict' && $docType !== 'xhtml_11'
            && $tsfe->config['config']['doctype'] !== 'html5'
            && !$tsfe->config['config']['disableImgBorderAttr']
        ) {
            return $borderAttr;
        }
        return '';
    }

    /**
     * Returns the html-template for rendering the image-Tag if no template is defined via typoscript the
     * default <img> tag template is returned
     *
     * @param string $layoutKey rendering key
     * @param array $conf TypoScript configuration properties
     * @return string
     */
    public function getImageTagTemplate($layoutKey, $conf)
    {
        if ($layoutKey && isset($conf['layout.']) && isset($conf['layout.'][$layoutKey . '.'])) {
            $imageTagLayout = $this->stdWrap(
                $conf['layout.'][$layoutKey . '.']['element'] ?? '',
                $conf['layout.'][$layoutKey . '.']['element.'] ?? []
            );
        } else {
            $imageTagLayout = '<img src="###SRC###" width="###WIDTH###" height="###HEIGHT###" ###PARAMS### ###ALTPARAMS### ###BORDER######SELFCLOSINGTAGSLASH###>';
        }
        return $imageTagLayout;
    }

    /**
     * Render alternate sources for the image tag. If no source collection is given an empty string is returned.
     *
     * @param string $layoutKey rendering key
     * @param array $conf TypoScript configuration properties
     * @param string $file
     * @throws \UnexpectedValueException
     * @return string
     */
    public function getImageSourceCollection($layoutKey, $conf, $file)
    {
        $sourceCollection = '';
        if ($layoutKey
            && isset($conf['sourceCollection.']) && $conf['sourceCollection.']
            && (
                isset($conf['layout.'][$layoutKey . '.']['source']) && $conf['layout.'][$layoutKey . '.']['source']
                || isset($conf['layout.'][$layoutKey . '.']['source.']) && $conf['layout.'][$layoutKey . '.']['source.']
            )
        ) {

            // find active sourceCollection
            $activeSourceCollections = [];
            foreach ($conf['sourceCollection.'] as $sourceCollectionKey => $sourceCollectionConfiguration) {
                if (substr($sourceCollectionKey, -1) === '.') {
                    if (empty($sourceCollectionConfiguration['if.']) || $this->checkIf($sourceCollectionConfiguration['if.'])) {
                        $activeSourceCollections[] = $sourceCollectionConfiguration;
                    }
                }
            }

            // apply option split to configurations
            $tsfe = $this->getTypoScriptFrontendController();
            $typoScriptService = GeneralUtility::makeInstance(TypoScriptService::class);
            $srcLayoutOptionSplitted = $typoScriptService->explodeConfigurationForOptionSplit((array)$conf['layout.'][$layoutKey . '.'], count($activeSourceCollections));

            // render sources
            foreach ($activeSourceCollections as $key => $sourceConfiguration) {
                $sourceLayout = $this->stdWrap(
                    $srcLayoutOptionSplitted[$key]['source'] ?? '',
                    $srcLayoutOptionSplitted[$key]['source.'] ?? []
                );

                $sourceRenderConfiguration = [
                    'file' => $file,
                    'file.' => $conf['file.'] ?? null
                ];

                if (isset($sourceConfiguration['quality']) || isset($sourceConfiguration['quality.'])) {
                    $imageQuality = $sourceConfiguration['quality'] ?? '';
                    if (isset($sourceConfiguration['quality.'])) {
                        $imageQuality = $this->stdWrap($sourceConfiguration['quality'], $sourceConfiguration['quality.']);
                    }
                    if ($imageQuality) {
                        $sourceRenderConfiguration['file.']['params'] = '-quality ' . (int)$imageQuality;
                    }
                }

                if (isset($sourceConfiguration['pixelDensity'])) {
                    $pixelDensity = (int)$this->stdWrap(
                        $sourceConfiguration['pixelDensity'] ?? '',
                        $sourceConfiguration['pixelDensity.'] ?? []
                    );
                } else {
                    $pixelDensity = 1;
                }
                $dimensionKeys = ['width', 'height', 'maxW', 'minW', 'maxH', 'minH', 'maxWidth', 'maxHeight', 'XY'];
                foreach ($dimensionKeys as $dimensionKey) {
                    $dimension = $this->stdWrap(
                        $sourceConfiguration[$dimensionKey] ?? '',
                        $sourceConfiguration[$dimensionKey . '.'] ?? []
                    );
                    if (!$dimension) {
                        $dimension = $this->stdWrap(
                            $conf['file.'][$dimensionKey] ?? '',
                            $conf['file.'][$dimensionKey . '.'] ?? []
                        );
                    }
                    if ($dimension) {
                        if (strstr($dimension, 'c') !== false && ($dimensionKey === 'width' || $dimensionKey === 'height')) {
                            $dimensionParts = explode('c', $dimension, 2);
                            $dimension = ((int)$dimensionParts[0] * $pixelDensity) . 'c';
                            if ($dimensionParts[1]) {
                                $dimension .= $dimensionParts[1];
                            }
                        } elseif ($dimensionKey === 'XY') {
                            $dimensionParts = GeneralUtility::intExplode(',', $dimension, false, 2);
                            $dimension = $dimensionParts[0] * $pixelDensity;
                            if ($dimensionParts[1]) {
                                $dimension .= ',' . $dimensionParts[1] * $pixelDensity;
                            }
                        } else {
                            $dimension = (int)$dimension * $pixelDensity;
                        }
                        $sourceRenderConfiguration['file.'][$dimensionKey] = $dimension;
                        // Remove the stdWrap properties for dimension as they have been processed already above.
                        unset($sourceRenderConfiguration['file.'][$dimensionKey . '.']);
                    }
                }
                $sourceInfo = $this->getImgResource($sourceRenderConfiguration['file'], $sourceRenderConfiguration['file.']);
                if ($sourceInfo) {
                    $sourceConfiguration['width'] = $sourceInfo[0];
                    $sourceConfiguration['height'] = $sourceInfo[1];
                    $urlPrefix = '';
                    if (parse_url($sourceInfo[3], PHP_URL_HOST) === null) {
                        $urlPrefix = $tsfe->absRefPrefix;
                    }
                    $sourceConfiguration['src'] = htmlspecialchars($urlPrefix . $sourceInfo[3]);
                    $sourceConfiguration['selfClosingTagSlash'] = !empty($tsfe->xhtmlDoctype) ? ' /' : '';

                    $markerTemplateEngine = GeneralUtility::makeInstance(MarkerBasedTemplateService::class);
                    $oneSourceCollection = $markerTemplateEngine->substituteMarkerArray($sourceLayout, $sourceConfiguration, '###|###', true, true);

                    foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_content.php']['getImageSourceCollection'] ?? [] as $className) {
                        $hookObject = GeneralUtility::makeInstance($className);
                        if (!$hookObject instanceof ContentObjectOneSourceCollectionHookInterface) {
                            throw new \UnexpectedValueException(
                                '$hookObject must implement interface ' . ContentObjectOneSourceCollectionHookInterface::class,
                                1380007853
                            );
                        }
                        $oneSourceCollection = $hookObject->getOneSourceCollection((array)$sourceRenderConfiguration, (array)$sourceConfiguration, $oneSourceCollection, $this);
                    }

                    $sourceCollection .= $oneSourceCollection;
                }
            }
        }
        return $sourceCollection;
    }

    /**
     * Wraps the input string in link-tags that opens the image in a new window.
     *
     * @param string $string String to wrap, probably an <img> tag
     * @param string|File|FileReference $imageFile The original image file
     * @param array $conf TypoScript properties for the "imageLinkWrap" function
     * @return string The input string, $string, wrapped as configured.
     * @see cImage()
     */
    public function imageLinkWrap($string, $imageFile, $conf)
    {
        $string = (string)$string;
        $enable = isset($conf['enable.']) ? $this->stdWrap($conf['enable'], $conf['enable.']) : $conf['enable'];
        if (!$enable) {
            return $string;
        }
        $content = (string)$this->typoLink($string, $conf['typolink.']);
        if (isset($conf['file.'])) {
            $imageFile = $this->stdWrap($imageFile, $conf['file.']);
        }

        if ($imageFile instanceof File) {
            $file = $imageFile;
        } elseif ($imageFile instanceof FileReference) {
            $file = $imageFile->getOriginalFile();
        } else {
            if (MathUtility::canBeInterpretedAsInteger($imageFile)) {
                $file = ResourceFactory::getInstance()->getFileObject((int)$imageFile);
            } else {
                $file = ResourceFactory::getInstance()->getFileObjectFromCombinedIdentifier($imageFile);
            }
        }

        // Create imageFileLink if not created with typolink
        if ($content === $string && $file !== null) {
            $parameterNames = ['width', 'height', 'effects', 'bodyTag', 'title', 'wrap', 'crop'];
            $parameters = [];
            $sample = isset($conf['sample.']) ? $this->stdWrap($conf['sample'], $conf['sample.']) : $conf['sample'];
            if ($sample) {
                $parameters['sample'] = 1;
            }
            foreach ($parameterNames as $parameterName) {
                if (isset($conf[$parameterName . '.'])) {
                    $conf[$parameterName] = $this->stdWrap($conf[$parameterName], $conf[$parameterName . '.']);
                }
                if (isset($conf[$parameterName]) && $conf[$parameterName]) {
                    $parameters[$parameterName] = $conf[$parameterName];
                }
            }
            $parametersEncoded = base64_encode(json_encode($parameters));
            $hmac = GeneralUtility::hmac(implode('|', [$file->getUid(), $parametersEncoded]));
            $params = '&md5=' . $hmac;
            foreach (str_split($parametersEncoded, 64) as $index => $chunk) {
                $params .= '&parameters' . rawurlencode('[') . $index . rawurlencode(']') . '=' . rawurlencode($chunk);
            }
            $url = $this->getTypoScriptFrontendController()->absRefPrefix . 'index.php?eID=tx_cms_showpic&file=' . $file->getUid() . $params;
            $directImageLink = isset($conf['directImageLink.']) ? $this->stdWrap($conf['directImageLink'], $conf['directImageLink.']) : $conf['directImageLink'];
            if ($directImageLink) {
                $imgResourceConf = [
                    'file' => $imageFile,
                    'file.' => $conf
                ];
                $url = $this->cObjGetSingle('IMG_RESOURCE', $imgResourceConf);
                if (!$url) {
                    // If no imagemagick / gm is available
                    $url = $imageFile;
                }
            }
            // Create TARGET-attribute only if the right doctype is used
            $target = '';
            $xhtmlDocType = $this->getTypoScriptFrontendController()->xhtmlDoctype;
            if ($xhtmlDocType !== 'xhtml_strict' && $xhtmlDocType !== 'xhtml_11') {
                $target = isset($conf['target.'])
                    ? (string)$this->stdWrap($conf['target'], $conf['target.'])
                    : (string)$conf['target'];
                if ($target === '') {
                    $target = 'thePicture';
                }
            }
            $a1 = '';
            $a2 = '';
            $conf['JSwindow'] = isset($conf['JSwindow.']) ? $this->stdWrap($conf['JSwindow'], $conf['JSwindow.']) : $conf['JSwindow'];
            if ($conf['JSwindow']) {
                if ($conf['JSwindow.']['altUrl'] || $conf['JSwindow.']['altUrl.']) {
                    $altUrl = isset($conf['JSwindow.']['altUrl.']) ? $this->stdWrap($conf['JSwindow.']['altUrl'], $conf['JSwindow.']['altUrl.']) : $conf['JSwindow.']['altUrl'];
                    if ($altUrl) {
                        $url = $altUrl . ($conf['JSwindow.']['altUrl_noDefaultParams'] ? '' : '?file=' . rawurlencode($imageFile) . $params);
                    }
                }

                $processedFile = $file->process(ProcessedFile::CONTEXT_IMAGECROPSCALEMASK, $conf);
                $JSwindowExpand = isset($conf['JSwindow.']['expand.']) ? $this->stdWrap($conf['JSwindow.']['expand'], $conf['JSwindow.']['expand.']) : $conf['JSwindow.']['expand'];
                $offset = GeneralUtility::intExplode(',', $JSwindowExpand . ',');
                $newWindow = isset($conf['JSwindow.']['newWindow.']) ? $this->stdWrap($conf['JSwindow.']['newWindow'], $conf['JSwindow.']['newWindow.']) : $conf['JSwindow.']['newWindow'];
                $onClick = 'openPic('
                    . GeneralUtility::quoteJSvalue($this->getTypoScriptFrontendController()->baseUrlWrap($url)) . ','
                    . '\'' . ($newWindow ? md5($url) : 'thePicture') . '\','
                    . GeneralUtility::quoteJSvalue('width=' . ($processedFile->getProperty('width') + $offset[0])
                        . ',height=' . ($processedFile->getProperty('height') + $offset[1]) . ',status=0,menubar=0')
                    . '); return false;';
                $a1 = '<a href="' . htmlspecialchars($url) . '"'
                    . ' onclick="' . htmlspecialchars($onClick) . '"'
                    . ($target !== '' ? ' target="' . htmlspecialchars($target) . '"' : '')
                    . $this->getTypoScriptFrontendController()->ATagParams . '>';
                $a2 = '</a>';
                $this->getTypoScriptFrontendController()->setJS('openPic');
            } else {
                $conf['linkParams.']['directImageLink'] = (bool)$conf['directImageLink'];
                $conf['linkParams.']['parameter'] = $url;
                $string = $this->typoLink($string, $conf['linkParams.']);
            }
            if (isset($conf['stdWrap.'])) {
                $string = $this->stdWrap($string, $conf['stdWrap.']);
            }
            $content = $a1 . $string . $a2;
        }
        return $content;
    }

    /**
     * Sets the SYS_LASTCHANGED timestamp if input timestamp is larger than current value.
     * The SYS_LASTCHANGED timestamp can be used by various caching/indexing applications to determine if the page has new content.
     * Therefore you should call this function with the last-changed timestamp of any element you display.
     *
     * @param int $tstamp Unix timestamp (number of seconds since 1970)
     * @see TypoScriptFrontendController::setSysLastChanged()
     */
    public function lastChanged($tstamp)
    {
        $tstamp = (int)$tstamp;
        $tsfe = $this->getTypoScriptFrontendController();
        if ($tstamp > (int)$tsfe->register['SYS_LASTCHANGED']) {
            $tsfe->register['SYS_LASTCHANGED'] = $tstamp;
        }
    }

    /**
     * Wraps the input string by the $wrap value and implements the "linkWrap" data type as well.
     * The "linkWrap" data type means that this function will find any integer encapsulated in {} (curly braces) in the first wrap part and substitute it with the corresponding page uid from the rootline where the found integer is pointing to the key in the rootline. See link below.
     *
     * @param string $content Input string
     * @param string $wrap A string where the first two parts separated by "|" (vertical line) will be wrapped around the input string
     * @return string Wrapped output string
     * @see wrap(), cImage(), FILE()
     */
    public function linkWrap($content, $wrap)
    {
        $wrapArr = explode('|', $wrap);
        if (preg_match('/\\{([0-9]*)\\}/', $wrapArr[0], $reg)) {
            $uid = $this->getTypoScriptFrontendController()->tmpl->rootLine[$reg[1]]['uid'] ?? null;
            if ($uid) {
                $wrapArr[0] = str_replace($reg[0], $uid, $wrapArr[0]);
            }
        }
        return trim($wrapArr[0] ?? '') . $content . trim($wrapArr[1] ?? '');
    }

    /**
     * An abstraction method which creates an alt or title parameter for an HTML img, applet, area or input element and the FILE content element.
     * From the $conf array it implements the properties "altText", "titleText" and "longdescURL"
     *
     * @param array $conf TypoScript configuration properties
     * @param bool $longDesc If set, the longdesc attribute will be generated - must only be used for img elements!
     * @return string Parameter string containing alt and title parameters (if any)
     * @see IMGTEXT(), FILE(), FORM(), cImage(), filelink()
     */
    public function getAltParam($conf, $longDesc = true)
    {
        $altText = isset($conf['altText.']) ? trim($this->stdWrap($conf['altText'], $conf['altText.'])) : trim($conf['altText']);
        $titleText = isset($conf['titleText.']) ? trim($this->stdWrap($conf['titleText'], $conf['titleText.'])) : trim($conf['titleText']);
        if (isset($conf['longdescURL.']) && $this->getTypoScriptFrontendController()->config['config']['doctype'] !== 'html5') {
            $longDescUrl = $this->typoLink_URL($conf['longdescURL.']);
        } else {
            $longDescUrl = trim($conf['longdescURL']);
        }
        $longDescUrl = strip_tags($longDescUrl);

        // "alt":
        $altParam = ' alt="' . htmlspecialchars($altText) . '"';
        // "title":
        $emptyTitleHandling = isset($conf['emptyTitleHandling.']) ? $this->stdWrap($conf['emptyTitleHandling'], $conf['emptyTitleHandling.']) : $conf['emptyTitleHandling'];
        // Choices: 'keepEmpty' | 'useAlt' | 'removeAttr'
        if ($titleText || $emptyTitleHandling === 'keepEmpty') {
            $altParam .= ' title="' . htmlspecialchars($titleText) . '"';
        } elseif (!$titleText && $emptyTitleHandling === 'useAlt') {
            $altParam .= ' title="' . htmlspecialchars($altText) . '"';
        }
        // "longDesc" URL
        if ($longDesc && !empty($longDescUrl)) {
            $altParam .= ' longdesc="' . htmlspecialchars($longDescUrl) . '"';
        }
        return $altParam;
    }

    /**
     * An abstraction method to add parameters to an A tag.
     * Uses the ATagParams property.
     *
     * @param array $conf TypoScript configuration properties
     * @param bool|int $addGlobal If set, will add the global config.ATagParams to the link
     * @return string String containing the parameters to the A tag (if non empty, with a leading space)
     * @see IMGTEXT(), filelink(), makelinks(), typolink()
     */
    public function getATagParams($conf, $addGlobal = 1)
    {
        $aTagParams = '';
        if ($conf['ATagParams.'] ?? false) {
            $aTagParams = ' ' . $this->stdWrap($conf['ATagParams'], $conf['ATagParams.']);
        } elseif ($conf['ATagParams'] ?? false) {
            $aTagParams = ' ' . $conf['ATagParams'];
        }
        if ($addGlobal) {
            $aTagParams = ' ' . trim($this->getTypoScriptFrontendController()->ATagParams . $aTagParams);
        }
        // Extend params
        $_params = [
            'conf' => &$conf,
            'aTagParams' => &$aTagParams
        ];
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_content.php']['getATagParamsPostProc'] ?? [] as $className) {
            $processor = GeneralUtility::makeInstance($className);
            $aTagParams = $processor->process($_params, $this);
        }

        $aTagParams = trim($aTagParams);
        if (!empty($aTagParams)) {
            $aTagParams = ' ' . $aTagParams;
        }

        return $aTagParams;
    }

    /**
     * All extension links should ask this function for additional properties to their tags.
     * Designed to add for instance an "onclick" property for site tracking systems.
     *
     * @param string $URL URL of the website
     * @param string $TYPE
     * @return string The additional tag properties
     */
    public function extLinkATagParams($URL, $TYPE)
    {
        $out = '';
        if (!empty($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_content.php']['extLinkATagParamsHandler'])) {
            $extLinkATagParamsHandler = GeneralUtility::makeInstance($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_content.php']['extLinkATagParamsHandler']);
            if (method_exists($extLinkATagParamsHandler, 'main')) {
                $out .= trim($extLinkATagParamsHandler->main($URL, $TYPE, $this));
            }
        }
        return trim($out) ? ' ' . trim($out) : '';
    }

    /***********************************************
     *
     * HTML template processing functions
     *
     ***********************************************/

    /**
     * Sets the current file object during iterations over files.
     *
     * @param File $fileObject The file object.
     */
    public function setCurrentFile($fileObject)
    {
        $this->currentFile = $fileObject;
    }

    /**
     * Gets the current file object during iterations over files.
     *
     * @return File The current file object.
     */
    public function getCurrentFile()
    {
        return $this->currentFile;
    }

    /***********************************************
     *
     * "stdWrap" + sub functions
     *
     ***********************************************/
    /**
     * The "stdWrap" function. This is the implementation of what is known as "stdWrap properties" in TypoScript.
     * Basically "stdWrap" performs some processing of a value based on properties in the input $conf array(holding the TypoScript "stdWrap properties")
     * See the link below for a complete list of properties and what they do. The order of the table with properties found in TSref (the link) follows the actual order of implementation in this function.
     *
     * If $this->alternativeData is an array it's used instead of the $this->data array in ->getData
     *
     * @param string $content Input value undergoing processing in this function. Possibly substituted by other values fetched from another source.
     * @param array $conf TypoScript "stdWrap properties".
     * @return string The processed input value
     */
    public function stdWrap($content = '', $conf = [])
    {
        $content = (string)$content;
        // If there is any hook object, activate all of the process and override functions.
        // The hook interface ContentObjectStdWrapHookInterface takes care that all 4 methods exist.
        if ($this->stdWrapHookObjects) {
            $conf['stdWrapPreProcess'] = 1;
            $conf['stdWrapOverride'] = 1;
            $conf['stdWrapProcess'] = 1;
            $conf['stdWrapPostProcess'] = 1;
        }

        if (!is_array($conf) || !$conf) {
            return $content;
        }

        // Cache handling
        if (isset($conf['cache.']) && is_array($conf['cache.'])) {
            $conf['cache.']['key'] = $this->stdWrap($conf['cache.']['key'], $conf['cache.']['key.']);
            $conf['cache.']['tags'] = $this->stdWrap($conf['cache.']['tags'], $conf['cache.']['tags.']);
            $conf['cache.']['lifetime'] = $this->stdWrap($conf['cache.']['lifetime'], $conf['cache.']['lifetime.']);
            $conf['cacheRead'] = 1;
            $conf['cacheStore'] = 1;
        }
        // The configuration is sorted and filtered by intersection with the defined stdWrapOrder.
        $sortedConf = array_keys(array_intersect_key($this->stdWrapOrder, $conf));
        // Functions types that should not make use of nested stdWrap function calls to avoid conflicts with internal TypoScript used by these functions
        $stdWrapDisabledFunctionTypes = 'cObject,functionName,stdWrap';
        // Additional Array to check whether a function has already been executed
        $isExecuted = [];
        // Additional switch to make sure 'required', 'if' and 'fieldRequired'
        // will still stop rendering immediately in case they return FALSE
        $this->stdWrapRecursionLevel++;
        $this->stopRendering[$this->stdWrapRecursionLevel] = false;
        // execute each function in the predefined order
        foreach ($sortedConf as $stdWrapName) {
            // eliminate the second key of a pair 'key'|'key.' to make sure functions get called only once and check if rendering has been stopped
            if ((!isset($isExecuted[$stdWrapName]) || !$isExecuted[$stdWrapName]) && !$this->stopRendering[$this->stdWrapRecursionLevel]) {
                $functionName = rtrim($stdWrapName, '.');
                $functionProperties = $functionName . '.';
                $functionType = $this->stdWrapOrder[$functionName] ?? null;
                // If there is any code on the next level, check if it contains "official" stdWrap functions
                // if yes, execute them first - will make each function stdWrap aware
                // so additional stdWrap calls within the functions can be removed, since the result will be the same
                if (!empty($conf[$functionProperties]) && !GeneralUtility::inList($stdWrapDisabledFunctionTypes, $functionType)) {
                    if (array_intersect_key($this->stdWrapOrder, $conf[$functionProperties])) {
                        // Check if there's already content available before processing
                        // any ifEmpty or ifBlank stdWrap properties
                        if (($functionName === 'ifBlank' && $content !== '') ||
                            ($functionName === 'ifEmpty' && trim($content) !== '')) {
                            continue;
                        }

                        $conf[$functionName] = $this->stdWrap($conf[$functionName] ?? '', $conf[$functionProperties] ?? []);
                    }
                }
                // Check if key is still containing something, since it might have been changed by next level stdWrap before
                if ((isset($conf[$functionName]) || $conf[$functionProperties])
                    && ($functionType !== 'boolean' || $conf[$functionName])
                ) {
                    // Get just that part of $conf that is needed for the particular function
                    $singleConf = [
                        $functionName => $conf[$functionName] ?? null,
                        $functionProperties => $conf[$functionProperties] ?? null
                    ];
                    // Hand over the whole $conf array to the stdWrapHookObjects
                    if ($functionType === 'hook') {
                        $singleConf = $conf;
                    }
                    // Add both keys - with and without the dot - to the set of executed functions
                    $isExecuted[$functionName] = true;
                    $isExecuted[$functionProperties] = true;
                    // Call the function with the prefix stdWrap_ to make sure nobody can execute functions just by adding their name to the TS Array
                    $functionName = 'stdWrap_' . $functionName;
                    $content = $this->{$functionName}($content, $singleConf);
                } elseif ($functionType === 'boolean' && !$conf[$functionName]) {
                    $isExecuted[$functionName] = true;
                    $isExecuted[$functionProperties] = true;
                }
            }
        }
        unset($this->stopRendering[$this->stdWrapRecursionLevel]);
        $this->stdWrapRecursionLevel--;

        return $content;
    }

    /**
     * Gets a configuration value by passing them through stdWrap first and taking a default value if stdWrap doesn't yield a result.
     *
     * @param string $key The config variable key (from TS array).
     * @param array $config The TypoScript array.
     * @param string $defaultValue Optional default value.
     * @return string Value of the config variable
     */
    public function stdWrapValue($key, array $config, $defaultValue = '')
    {
        if (isset($config[$key])) {
            if (!isset($config[$key . '.'])) {
                return $config[$key];
            }
        } elseif (isset($config[$key . '.'])) {
            $config[$key] = '';
        } else {
            return $defaultValue;
        }
        $stdWrapped = $this->stdWrap($config[$key], $config[$key . '.']);
        return $stdWrapped ?: $defaultValue;
    }

    /**
     * stdWrap pre process hook
     * can be used by extensions authors to modify the behaviour of stdWrap functions to their needs
     * this hook will execute functions before any other stdWrap function can modify anything
     *
     * @param string $content Input value undergoing processing in these functions.
     * @param array $conf All stdWrap properties, not just the ones for a particular function.
     * @return string The processed input value
     */
    public function stdWrap_stdWrapPreProcess($content = '', $conf = [])
    {
        foreach ($this->stdWrapHookObjects as $hookObject) {
            /** @var ContentObjectStdWrapHookInterface $hookObject */
            $content = $hookObject->stdWrapPreProcess($content, $conf, $this);
        }
        return $content;
    }

    /**
     * Check if content was cached before (depending on the given cache key)
     *
     * @param string $content Input value undergoing processing in these functions.
     * @param array $conf All stdWrap properties, not just the ones for a particular function.
     * @return string The processed input value
     */
    public function stdWrap_cacheRead($content = '', $conf = [])
    {
        if (!isset($conf['cache.'])) {
            return $content;
        }
        $result = $this->getFromCache($conf['cache.']);
        return $result === false ? $content : $result;
    }

    /**
     * Add tags to page cache (comma-separated list)
     *
     * @param string $content Input value undergoing processing in these functions.
     * @param array $conf All stdWrap properties, not just the ones for a particular function.
     * @return string The processed input value
     */
    public function stdWrap_addPageCacheTags($content = '', $conf = [])
    {
        $tags = isset($conf['addPageCacheTags.'])
            ? $this->stdWrap($conf['addPageCacheTags'], $conf['addPageCacheTags.'])
            : $conf['addPageCacheTags'];
        if (!empty($tags)) {
            $cacheTags = GeneralUtility::trimExplode(',', $tags, true);
            $this->getTypoScriptFrontendController()->addCacheTags($cacheTags);
        }
        return $content;
    }

    /**
     * setContentToCurrent
     * actually it just does the contrary: Sets the value of 'current' based on current content
     *
     * @param string $content Input value undergoing processing in this function.
     * @return string The processed input value
     */
    public function stdWrap_setContentToCurrent($content = '')
    {
        $this->data[$this->currentValKey] = $content;
        return $content;
    }

    /**
     * setCurrent
     * Sets the value of 'current' based on the outcome of stdWrap operations
     *
     * @param string $content Input value undergoing processing in this function.
     * @param array $conf stdWrap properties for setCurrent.
     * @return string The processed input value
     */
    public function stdWrap_setCurrent($content = '', $conf = [])
    {
        $this->data[$this->currentValKey] = $conf['setCurrent'] ?? null;
        return $content;
    }

    /**
     * lang
     * Translates content based on the language currently used by the FE
     *
     * @param string $content Input value undergoing processing in this function.
     * @param array $conf stdWrap properties for lang.
     * @return string The processed input value
     */
    public function stdWrap_lang($content = '', $conf = [])
    {
        $request = $GLOBALS['TYPO3_REQUEST'] ?? null;
        $siteLanguage = $request ? $request->getAttribute('language') : null;
        if ($siteLanguage instanceof SiteLanguage) {
            $currentLanguageCode = $siteLanguage->getTypo3Language();
        } else {
            $tsfe = $this->getTypoScriptFrontendController();
            $currentLanguageCode = $tsfe->config['config']['language'] ?? null;
        }
        if ($currentLanguageCode && isset($conf['lang.'][$currentLanguageCode])) {
            $content = $conf['lang.'][$currentLanguageCode];
        }
        return $content;
    }

    /**
     * data
     * Gets content from different sources based on getText functions, makes use of alternativeData, when set
     *
     * @param string $content Input value undergoing processing in this function.
     * @param array $conf stdWrap properties for data.
     * @return string The processed input value
     */
    public function stdWrap_data($content = '', $conf = [])
    {
        $content = $this->getData($conf['data'], is_array($this->alternativeData) ? $this->alternativeData : $this->data);
        // This must be unset directly after
        $this->alternativeData = '';
        return $content;
    }

    /**
     * field
     * Gets content from a DB field
     *
     * @param string $content Input value undergoing processing in this function.
     * @param array $conf stdWrap properties for field.
     * @return string The processed input value
     */
    public function stdWrap_field($content = '', $conf = [])
    {
        return $this->getFieldVal($conf['field']);
    }

    /**
     * current
     * Gets content that has been perviously set as 'current'
     * Can be set via setContentToCurrent or setCurrent or will be set automatically i.e. inside the split function
     *
     * @param string $content Input value undergoing processing in this function.
     * @param array $conf stdWrap properties for current.
     * @return string The processed input value
     */
    public function stdWrap_current($content = '', $conf = [])
    {
        return $this->data[$this->currentValKey];
    }

    /**
     * cObject
     * Will replace the content with the value of an official TypoScript cObject
     * like TEXT, COA, HMENU
     *
     * @param string $content Input value undergoing processing in this function.
     * @param array $conf stdWrap properties for cObject.
     * @return string The processed input value
     */
    public function stdWrap_cObject($content = '', $conf = [])
    {
        return $this->cObjGetSingle($conf['cObject'] ?? '', $conf['cObject.'] ?? [], '/stdWrap/.cObject');
    }

    /**
     * numRows
     * Counts the number of returned records of a DB operation
     * makes use of select internally
     *
     * @param string $content Input value undergoing processing in this function.
     * @param array $conf stdWrap properties for numRows.
     * @return string The processed input value
     */
    public function stdWrap_numRows($content = '', $conf = [])
    {
        return $this->numRows($conf['numRows.']);
    }

    /**
     * filelist
     * Will create a list of files based on some additional parameters
     *
     * @param string $content Input value undergoing processing in this function.
     * @param array $conf stdWrap properties for filelist.
     * @return string The processed input value
     * @deprecated since TYPO3 v9.5, will be removed in TYPO3 v10.0. Use cObject FILES instead.
     */
    public function stdWrap_filelist($content = '', $conf = [])
    {
        return $this->filelist($conf['filelist'], true);
    }

    /**
     * preUserFunc
     * Will execute a user public function before the content will be modified by any other stdWrap function
     *
     * @param string $content Input value undergoing processing in this function.
     * @param array $conf stdWrap properties for preUserFunc.
     * @return string The processed input value
     */
    public function stdWrap_preUserFunc($content = '', $conf = [])
    {
        return $this->callUserFunction($conf['preUserFunc'], $conf['preUserFunc.'], $content);
    }

    /**
     * stdWrap override hook
     * can be used by extensions authors to modify the behaviour of stdWrap functions to their needs
     * this hook will execute functions on existing content but still before the content gets modified or replaced
     *
     * @param string $content Input value undergoing processing in these functions.
     * @param array $conf All stdWrap properties, not just the ones for a particular function.
     * @return string The processed input value
     */
    public function stdWrap_stdWrapOverride($content = '', $conf = [])
    {
        foreach ($this->stdWrapHookObjects as $hookObject) {
            /** @var ContentObjectStdWrapHookInterface $hookObject */
            $content = $hookObject->stdWrapOverride($content, $conf, $this);
        }
        return $content;
    }

    /**
     * override
     * Will override the current value of content with its own value'
     *
     * @param string $content Input value undergoing processing in this function.
     * @param array $conf stdWrap properties for override.
     * @return string The processed input value
     */
    public function stdWrap_override($content = '', $conf = [])
    {
        if (trim($conf['override'] ?? false)) {
            $content = $conf['override'];
        }
        return $content;
    }

    /**
     * preIfEmptyListNum
     * Gets a value off a CSV list before the following ifEmpty check
     * Makes sure that the result of ifEmpty will be TRUE in case the CSV does not contain a value at the position given by preIfEmptyListNum
     *
     * @param string $content Input value undergoing processing in this function.
     * @param array $conf stdWrap properties for preIfEmptyListNum.
     * @return string The processed input value
     */
    public function stdWrap_preIfEmptyListNum($content = '', $conf = [])
    {
        return $this->listNum($content, $conf['preIfEmptyListNum'] ?? null, $conf['preIfEmptyListNum.']['splitChar'] ?? null);
    }

    /**
     * ifNull
     * Will set content to a replacement value in case the value of content is NULL
     *
     * @param string|null $content Input value undergoing processing in this function.
     * @param array $conf stdWrap properties for ifNull.
     * @return string The processed input value
     */
    public function stdWrap_ifNull($content = '', $conf = [])
    {
        return $content ?? $conf['ifNull'];
    }

    /**
     * ifEmpty
     * Will set content to a replacement value in case the trimmed value of content returns FALSE
     * 0 (zero) will be replaced as well
     *
     * @param string $content Input value undergoing processing in this function.
     * @param array $conf stdWrap properties for ifEmpty.
     * @return string The processed input value
     */
    public function stdWrap_ifEmpty($content = '', $conf = [])
    {
        if (!trim($content)) {
            $content = $conf['ifEmpty'];
        }
        return $content;
    }

    /**
     * ifBlank
     * Will set content to a replacement value in case the trimmed value of content has no length
     * 0 (zero) will not be replaced
     *
     * @param string $content Input value undergoing processing in this function.
     * @param array $conf stdWrap properties for ifBlank.
     * @return string The processed input value
     */
    public function stdWrap_ifBlank($content = '', $conf = [])
    {
        if (trim($content) === '') {
            $content = $conf['ifBlank'];
        }
        return $content;
    }

    /**
     * listNum
     * Gets a value off a CSV list after ifEmpty check
     * Might return an empty value in case the CSV does not contain a value at the position given by listNum
     * Use preIfEmptyListNum to avoid that behaviour
     *
     * @param string $content Input value undergoing processing in this function.
     * @param array $conf stdWrap properties for listNum.
     * @return string The processed input value
     */
    public function stdWrap_listNum($content = '', $conf = [])
    {
        return $this->listNum($content, $conf['listNum'] ?? null, $conf['listNum.']['splitChar'] ?? null);
    }

    /**
     * trim
     * Cuts off any whitespace at the beginning and the end of the content
     *
     * @param string $content Input value undergoing processing in this function.
     * @return string The processed input value
     */
    public function stdWrap_trim($content = '')
    {
        return trim($content);
    }

    /**
     * strPad
     * Will return a string padded left/right/on both sides, based on configuration given as stdWrap properties
     *
     * @param string $content Input value undergoing processing in this function.
     * @param array $conf stdWrap properties for strPad.
     * @return string The processed input value
     */
    public function stdWrap_strPad($content = '', $conf = [])
    {
        // Must specify a length in conf for this to make sense
        $length = 0;
        // Padding with space is PHP-default
        $padWith = ' ';
        // Padding on the right side is PHP-default
        $padType = STR_PAD_RIGHT;
        if (!empty($conf['strPad.']['length'])) {
            $length = isset($conf['strPad.']['length.']) ? $this->stdWrap($conf['strPad.']['length'], $conf['strPad.']['length.']) : $conf['strPad.']['length'];
            $length = (int)$length;
        }
        if (isset($conf['strPad.']['padWith']) && (string)$conf['strPad.']['padWith'] !== '') {
            $padWith = isset($conf['strPad.']['padWith.']) ? $this->stdWrap($conf['strPad.']['padWith'], $conf['strPad.']['padWith.']) : $conf['strPad.']['padWith'];
        }
        if (!empty($conf['strPad.']['type'])) {
            $type = isset($conf['strPad.']['type.']) ? $this->stdWrap($conf['strPad.']['type'], $conf['strPad.']['type.']) : $conf['strPad.']['type'];
            if (strtolower($type) === 'left') {
                $padType = STR_PAD_LEFT;
            } elseif (strtolower($type) === 'both') {
                $padType = STR_PAD_BOTH;
            }
        }
        return str_pad($content, $length, $padWith, $padType);
    }

    /**
     * stdWrap
     * A recursive call of the stdWrap function set
     * This enables the user to execute stdWrap functions in another than the predefined order
     * It modifies the content, not the property
     * while the new feature of chained stdWrap functions modifies the property and not the content
     *
     * @param string $content Input value undergoing processing in this function.
     * @param array $conf stdWrap properties for stdWrap.
     * @return string The processed input value
     */
    public function stdWrap_stdWrap($content = '', $conf = [])
    {
        return $this->stdWrap($content, $conf['stdWrap.']);
    }

    /**
     * stdWrap process hook
     * can be used by extensions authors to modify the behaviour of stdWrap functions to their needs
     * this hook executes functions directly after the recursive stdWrap function call but still before the content gets modified
     *
     * @param string $content Input value undergoing processing in these functions.
     * @param array $conf All stdWrap properties, not just the ones for a particular function.
     * @return string The processed input value
     */
    public function stdWrap_stdWrapProcess($content = '', $conf = [])
    {
        foreach ($this->stdWrapHookObjects as $hookObject) {
            /** @var ContentObjectStdWrapHookInterface $hookObject */
            $content = $hookObject->stdWrapProcess($content, $conf, $this);
        }
        return $content;
    }

    /**
     * required
     * Will immediately stop rendering and return an empty value
     * when there is no content at this point
     *
     * @param string $content Input value undergoing processing in this function.
     * @return string The processed input value
     */
    public function stdWrap_required($content = '')
    {
        if ((string)$content === '') {
            $content = '';
            $this->stopRendering[$this->stdWrapRecursionLevel] = true;
        }
        return $content;
    }

    /**
     * if
     * Will immediately stop rendering and return an empty value
     * when the result of the checks returns FALSE
     *
     * @param string $content Input value undergoing processing in this function.
     * @param array $conf stdWrap properties for if.
     * @return string The processed input value
     */
    public function stdWrap_if($content = '', $conf = [])
    {
        if (empty($conf['if.']) || $this->checkIf($conf['if.'])) {
            return $content;
        }
        $this->stopRendering[$this->stdWrapRecursionLevel] = true;
        return '';
    }

    /**
     * fieldRequired
     * Will immediately stop rendering and return an empty value
     * when there is no content in the field given by fieldRequired
     *
     * @param string $content Input value undergoing processing in this function.
     * @param array $conf stdWrap properties for fieldRequired.
     * @return string The processed input value
     */
    public function stdWrap_fieldRequired($content = '', $conf = [])
    {
        if (!trim($this->data[$conf['fieldRequired'] ?? null] ?? '')) {
            $content = '';
            $this->stopRendering[$this->stdWrapRecursionLevel] = true;
        }
        return $content;
    }

    /**
     * stdWrap csConv: Converts the input to UTF-8
     *
     * The character set of the input must be specified. Returns the input if
     * matters go wrong, for example if an invalid character set is given.
     *
     * @param string $content The string to convert.
     * @param array $conf stdWrap properties for csConv.
     * @return string The processed input.
     */
    public function stdWrap_csConv($content = '', $conf = [])
    {
        if (!empty($conf['csConv'])) {
            $output = mb_convert_encoding($content, 'utf-8', trim(strtolower($conf['csConv'])));
            return $output !== false && $output !== '' ? $output : $content;
        }
        return $content;
    }

    /**
     * parseFunc
     * Will parse the content based on functions given as stdWrap properties
     * Heavily used together with RTE based content
     *
     * @param string $content Input value undergoing processing in this function.
     * @param array $conf stdWrap properties for parseFunc.
     * @return string The processed input value
     */
    public function stdWrap_parseFunc($content = '', $conf = [])
    {
        return $this->parseFunc($content, $conf['parseFunc.'], $conf['parseFunc']);
    }

    /**
     * HTMLparser
     * Will parse HTML content based on functions given as stdWrap properties
     * Heavily used together with RTE based content
     *
     * @param string $content Input value undergoing processing in this function.
     * @param array $conf stdWrap properties for HTMLparser.
     * @return string The processed input value
     */
    public function stdWrap_HTMLparser($content = '', $conf = [])
    {
        if (isset($conf['HTMLparser.']) && is_array($conf['HTMLparser.'])) {
            $content = $this->HTMLparser_TSbridge($content, $conf['HTMLparser.']);
        }
        return $content;
    }

    /**
     * split
     * Will split the content by a given token and treat the results separately
     * Automatically fills 'current' with a single result
     *
     * @param string $content Input value undergoing processing in this function.
     * @param array $conf stdWrap properties for split.
     * @return string The processed input value
     */
    public function stdWrap_split($content = '', $conf = [])
    {
        return $this->splitObj($content, $conf['split.']);
    }

    /**
     * replacement
     * Will execute replacements on the content (optionally with preg-regex)
     *
     * @param string $content Input value undergoing processing in this function.
     * @param array $conf stdWrap properties for replacement.
     * @return string The processed input value
     */
    public function stdWrap_replacement($content = '', $conf = [])
    {
        return $this->replacement($content, $conf['replacement.']);
    }

    /**
     * prioriCalc
     * Will use the content as a mathematical term and calculate the result
     * Can be set to 1 to just get a calculated value or 'intval' to get the integer of the result
     *
     * @param string $content Input value undergoing processing in this function.
     * @param array $conf stdWrap properties for prioriCalc.
     * @return string The processed input value
     */
    public function stdWrap_prioriCalc($content = '', $conf = [])
    {
        $content = MathUtility::calculateWithParentheses($content);
        if (!empty($conf['prioriCalc']) && $conf['prioriCalc'] === 'intval') {
            $content = (int)$content;
        }
        return $content;
    }

    /**
     * char
     * Returns a one-character string containing the character specified by ascii code.
     *
     * Reliable results only for character codes in the integer range 0 - 127.
     *
     * @see http://php.net/manual/en/function.chr.php
     * @param string $content Input value undergoing processing in this function.
     * @param array $conf stdWrap properties for char.
     * @return string The processed input value
     */
    public function stdWrap_char($content = '', $conf = [])
    {
        return chr((int)$conf['char']);
    }

    /**
     * intval
     * Will return an integer value of the current content
     *
     * @param string $content Input value undergoing processing in this function.
     * @return string The processed input value
     */
    public function stdWrap_intval($content = '')
    {
        return (int)$content;
    }

    /**
     * Will return a hashed value of the current content
     *
     * @param string $content Input value undergoing processing in this function.
     * @param array $conf stdWrap properties for hash.
     * @return string The processed input value
     * @link http://php.net/manual/de/function.hash-algos.php for a list of supported hash algorithms
     */
    public function stdWrap_hash($content = '', array $conf = [])
    {
        $algorithm = isset($conf['hash.']) ? $this->stdWrap($conf['hash'], $conf['hash.']) : $conf['hash'];
        if (function_exists('hash') && in_array($algorithm, hash_algos())) {
            return hash($algorithm, $content);
        }
        // Non-existing hashing algorithm
        return '';
    }

    /**
     * stdWrap_round will return a rounded number with ceil(), floor() or round(), defaults to round()
     * Only the english number format is supported . (dot) as decimal point
     *
     * @param string $content Input value undergoing processing in this function.
     * @param array $conf stdWrap properties for round.
     * @return string The processed input value
     */
    public function stdWrap_round($content = '', $conf = [])
    {
        return $this->round($content, $conf['round.']);
    }

    /**
     * numberFormat
     * Will return a formatted number based on configuration given as stdWrap properties
     *
     * @param string $content Input value undergoing processing in this function.
     * @param array $conf stdWrap properties for numberFormat.
     * @return string The processed input value
     */
    public function stdWrap_numberFormat($content = '', $conf = [])
    {
        return $this->numberFormat($content, $conf['numberFormat.'] ?? []);
    }

    /**
     * expandList
     * Will return a formatted number based on configuration given as stdWrap properties
     *
     * @param string $content Input value undergoing processing in this function.
     * @return string The processed input value
     */
    public function stdWrap_expandList($content = '')
    {
        return GeneralUtility::expandList($content);
    }

    /**
     * date
     * Will return a formatted date based on configuration given according to PHP date/gmdate properties
     * Will return gmdate when the property GMT returns TRUE
     *
     * @param string $content Input value undergoing processing in this function.
     * @param array $conf stdWrap properties for date.
     * @return string The processed input value
     */
    public function stdWrap_date($content = '', $conf = [])
    {
        // Check for zero length string to mimic default case of date/gmdate.
        $content = (string)$content === '' ? $GLOBALS['EXEC_TIME'] : (int)$content;
        $content = !empty($conf['date.']['GMT']) ? gmdate($conf['date'] ?? null, $content) : date($conf['date'] ?? null, $content);
        return $content;
    }

    /**
     * strftime
     * Will return a formatted date based on configuration given according to PHP strftime/gmstrftime properties
     * Will return gmstrftime when the property GMT returns TRUE
     *
     * @param string $content Input value undergoing processing in this function.
     * @param array $conf stdWrap properties for strftime.
     * @return string The processed input value
     */
    public function stdWrap_strftime($content = '', $conf = [])
    {
        // Check for zero length string to mimic default case of strtime/gmstrftime
        $content = (string)$content === '' ? $GLOBALS['EXEC_TIME'] : (int)$content;
        $content = (isset($conf['strftime.']['GMT']) && $conf['strftime.']['GMT'])
            ? gmstrftime($conf['strftime'] ?? null, $content)
            : strftime($conf['strftime'] ?? null, $content);
        if (!empty($conf['strftime.']['charset'])) {
            $output = mb_convert_encoding($content, 'utf-8', trim(strtolower($conf['strftime.']['charset'])));
            return $output ?: $content;
        }
        return $content;
    }

    /**
     * strtotime
     * Will return a timestamp based on configuration given according to PHP strtotime
     *
     * @param string $content Input value undergoing processing in this function.
     * @param array $conf stdWrap properties for strtotime.
     * @return string The processed input value
     */
    public function stdWrap_strtotime($content = '', $conf = [])
    {
        if ($conf['strtotime'] !== '1') {
            $content .= ' ' . $conf['strtotime'];
        }
        return strtotime($content, $GLOBALS['EXEC_TIME']);
    }

    /**
     * age
     * Will return the age of a given timestamp based on configuration given by stdWrap properties
     *
     * @param string $content Input value undergoing processing in this function.
     * @param array $conf stdWrap properties for age.
     * @return string The processed input value
     */
    public function stdWrap_age($content = '', $conf = [])
    {
        return $this->calcAge((int)($GLOBALS['EXEC_TIME'] ?? 0) - (int)$content, $conf['age'] ?? null);
    }

    /**
     * case
     * Will transform the content to be upper or lower case only
     * Leaves HTML tags untouched
     *
     * @param string $content Input value undergoing processing in this function.
     * @param array $conf stdWrap properties for case.
     * @return string The processed input value
     */
    public function stdWrap_case($content = '', $conf = [])
    {
        return $this->HTMLcaseshift($content, $conf['case']);
    }

    /**
     * bytes
     * Will return the size of a given number in Bytes	 *
     *
     * @param string $content Input value undergoing processing in this function.
     * @param array $conf stdWrap properties for bytes.
     * @return string The processed input value
     */
    public function stdWrap_bytes($content = '', $conf = [])
    {
        return GeneralUtility::formatSize($content, $conf['bytes.']['labels'], $conf['bytes.']['base']);
    }

    /**
     * substring
     * Will return a substring based on position information given by stdWrap properties
     *
     * @param string $content Input value undergoing processing in this function.
     * @param array $conf stdWrap properties for substring.
     * @return string The processed input value
     */
    public function stdWrap_substring($content = '', $conf = [])
    {
        return $this->substring($content, $conf['substring']);
    }

    /**
     * cropHTML
     * Crops content to a given size while leaving HTML tags untouched
     *
     * @param string $content Input value undergoing processing in this function.
     * @param array $conf stdWrap properties for cropHTML.
     * @return string The processed input value
     */
    public function stdWrap_cropHTML($content = '', $conf = [])
    {
        return $this->cropHTML($content, $conf['cropHTML'] ?? '');
    }

    /**
     * stripHtml
     * Copmletely removes HTML tags from content
     *
     * @param string $content Input value undergoing processing in this function.
     * @return string The processed input value
     */
    public function stdWrap_stripHtml($content = '')
    {
        return strip_tags($content);
    }

    /**
     * crop
     * Crops content to a given size without caring about HTML tags
     *
     * @param string $content Input value undergoing processing in this function.
     * @param array $conf stdWrap properties for crop.
     * @return string The processed input value
     */
    public function stdWrap_crop($content = '', $conf = [])
    {
        return $this->crop($content, $conf['crop']);
    }

    /**
     * rawUrlEncode
     * Encodes content to be used within URLs
     *
     * @param string $content Input value undergoing processing in this function.
     * @return string The processed input value
     */
    public function stdWrap_rawUrlEncode($content = '')
    {
        return rawurlencode($content);
    }

    /**
     * htmlSpecialChars
     * Transforms HTML tags to readable text by replacing special characters with their HTML entity
     * When preserveEntities returns TRUE, existing entities will be left untouched
     *
     * @param string $content Input value undergoing processing in this function.
     * @param array $conf stdWrap properties for htmlSpecalChars.
     * @return string The processed input value
     */
    public function stdWrap_htmlSpecialChars($content = '', $conf = [])
    {
        if (!empty($conf['htmlSpecialChars.']['preserveEntities'])) {
            $content = htmlspecialchars($content, ENT_COMPAT, 'UTF-8', false);
        } else {
            $content = htmlspecialchars($content);
        }
        return $content;
    }

    /**
     * encodeForJavaScriptValue
     * Escapes content to be used inside JavaScript strings. Single quotes are added around the value.
     *
     * @param string $content Input value undergoing processing in this function
     * @return string The processed input value
     */
    public function stdWrap_encodeForJavaScriptValue($content = '')
    {
        return GeneralUtility::quoteJSvalue($content);
    }

    /**
     * doubleBrTag
     * Searches for double line breaks and replaces them with the given value
     *
     * @param string $content Input value undergoing processing in this function.
     * @param array $conf stdWrap properties for doubleBrTag.
     * @return string The processed input value
     */
    public function stdWrap_doubleBrTag($content = '', $conf = [])
    {
        return preg_replace('/\R{1,2}[\t\x20]*\R{1,2}/', $conf['doubleBrTag'] ?? null, $content);
    }

    /**
     * br
     * Searches for single line breaks and replaces them with a <br />/<br> tag
     * according to the doctype
     *
     * @param string $content Input value undergoing processing in this function.
     * @return string The processed input value
     */
    public function stdWrap_br($content = '')
    {
        return nl2br($content, !empty($this->getTypoScriptFrontendController()->xhtmlDoctype));
    }

    /**
     * brTag
     * Searches for single line feeds and replaces them with the given value
     *
     * @param string $content Input value undergoing processing in this function.
     * @param array $conf stdWrap properties for brTag.
     * @return string The processed input value
     */
    public function stdWrap_brTag($content = '', $conf = [])
    {
        return str_replace(LF, $conf['brTag'] ?? null, $content);
    }

    /**
     * encapsLines
     * Modifies text blocks by searching for lines which are not surrounded by HTML tags yet
     * and wrapping them with values given by stdWrap properties
     *
     * @param string $content Input value undergoing processing in this function.
     * @param array $conf stdWrap properties for erncapsLines.
     * @return string The processed input value
     */
    public function stdWrap_encapsLines($content = '', $conf = [])
    {
        return $this->encaps_lineSplit($content, $conf['encapsLines.']);
    }

    /**
     * keywords
     * Transforms content into a CSV list to be used i.e. as keywords within a meta tag
     *
     * @param string $content Input value undergoing processing in this function.
     * @return string The processed input value
     */
    public function stdWrap_keywords($content = '')
    {
        return $this->keywords($content);
    }

    /**
     * innerWrap
     * First of a set of different wraps which will be applied in a certain order before or after other functions that modify the content
     * See wrap
     *
     * @param string $content Input value undergoing processing in this function.
     * @param array $conf stdWrap properties for innerWrap.
     * @return string The processed input value
     */
    public function stdWrap_innerWrap($content = '', $conf = [])
    {
        return $this->wrap($content, $conf['innerWrap'] ?? null);
    }

    /**
     * innerWrap2
     * Second of a set of different wraps which will be applied in a certain order before or after other functions that modify the content
     * See wrap
     *
     * @param string $content Input value undergoing processing in this function.
     * @param array $conf stdWrap properties for innerWrap2.
     * @return string The processed input value
     */
    public function stdWrap_innerWrap2($content = '', $conf = [])
    {
        return $this->wrap($content, $conf['innerWrap2'] ?? null);
    }

    /**
     * addParams
     * Adds tag attributes to any content that is a tag
     *
     * @param string $content Input value undergoing processing in this function.
     * @param array $conf stdWrap properties for addParams.
     * @return string The processed input value
     * @deprecated since TYPO3 v9.5, will be removed in TYPO3 v10.0.
     */
    public function stdWrap_addParams($content = '', $conf = [])
    {
        return $this->addParams($content, $conf['addParams.'] ?? [], true);
    }

    /**
     * filelink
     * Used to make lists of links to files
     * See wrap
     *
     * @param string $content Input value undergoing processing in this function.
     * @param array $conf stdWrap properties for filelink.
     * @return string The processed input value
     * @deprecated since TYPO3 v9.5, will be removed in TYPO3 v10.0. Use cObject FILES instead.
     */
    public function stdWrap_filelink($content = '', $conf = [])
    {
        return $this->filelink($content, $conf['filelink.'] ?? [], true);
    }

    /**
     * preCObject
     * A content object that is prepended to the current content but between the innerWraps and the rest of the wraps
     *
     * @param string $content Input value undergoing processing in this function.
     * @param array $conf stdWrap properties for preCObject.
     * @return string The processed input value
     */
    public function stdWrap_preCObject($content = '', $conf = [])
    {
        return $this->cObjGetSingle($conf['preCObject'], $conf['preCObject.'], '/stdWrap/.preCObject') . $content;
    }

    /**
     * postCObject
     * A content object that is appended to the current content but between the innerWraps and the rest of the wraps
     *
     * @param string $content Input value undergoing processing in this function.
     * @param array $conf stdWrap properties for postCObject.
     * @return string The processed input value
     */
    public function stdWrap_postCObject($content = '', $conf = [])
    {
        return $content . $this->cObjGetSingle($conf['postCObject'], $conf['postCObject.'], '/stdWrap/.postCObject');
    }

    /**
     * wrapAlign
     * Wraps content with a div container having the style attribute text-align set to the given value
     * See wrap
     *
     * @param string $content Input value undergoing processing in this function.
     * @param array $conf stdWrap properties for wrapAlign.
     * @return string The processed input value
     */
    public function stdWrap_wrapAlign($content = '', $conf = [])
    {
        $wrapAlign = trim($conf['wrapAlign'] ?? '');
        if ($wrapAlign) {
            $content = $this->wrap($content, '<div style="text-align:' . htmlspecialchars($wrapAlign) . ';">|</div>');
        }
        return $content;
    }

    /**
     * typolink
     * Wraps the content with a link tag
     * URLs and other attributes are created automatically by the values given in the stdWrap properties
     * See wrap
     *
     * @param string $content Input value undergoing processing in this function.
     * @param array $conf stdWrap properties for typolink.
     * @return string The processed input value
     */
    public function stdWrap_typolink($content = '', $conf = [])
    {
        return $this->typoLink($content, $conf['typolink.']);
    }

    /**
     * wrap
     * This is the "mother" of all wraps
     * Third of a set of different wraps which will be applied in a certain order before or after other functions that modify the content
     * Basically it will put additional content before and after the current content using a split character as a placeholder for the current content
     * The default split character is | but it can be replaced with other characters by the property splitChar
     * Any other wrap that does not have own splitChar settings will be using the default split char though
     *
     * @param string $content Input value undergoing processing in this function.
     * @param array $conf stdWrap properties for wrap.
     * @return string The processed input value
     */
    public function stdWrap_wrap($content = '', $conf = [])
    {
        return $this->wrap(
            $content,
            $conf['wrap'] ?? null,
            $conf['wrap.']['splitChar'] ?? '|'
        );
    }

    /**
     * noTrimWrap
     * Fourth of a set of different wraps which will be applied in a certain order before or after other functions that modify the content
     * The major difference to any other wrap is, that this one can make use of whitespace without trimming	 *
     *
     * @param string $content Input value undergoing processing in this function.
     * @param array $conf stdWrap properties for noTrimWrap.
     * @return string The processed input value
     */
    public function stdWrap_noTrimWrap($content = '', $conf = [])
    {
        $splitChar = isset($conf['noTrimWrap.']['splitChar.'])
            ? $this->stdWrap($conf['noTrimWrap.']['splitChar'] ?? '', $conf['noTrimWrap.']['splitChar.'])
            : $conf['noTrimWrap.']['splitChar'] ?? '';
        if ($splitChar === null || $splitChar === '') {
            $splitChar = '|';
        }
        $content = $this->noTrimWrap(
            $content,
            $conf['noTrimWrap'],
            $splitChar
        );
        return $content;
    }

    /**
     * wrap2
     * Fifth of a set of different wraps which will be applied in a certain order before or after other functions that modify the content
     * The default split character is | but it can be replaced with other characters by the property splitChar
     *
     * @param string $content Input value undergoing processing in this function.
     * @param array $conf stdWrap properties for wrap2.
     * @return string The processed input value
     */
    public function stdWrap_wrap2($content = '', $conf = [])
    {
        return $this->wrap(
            $content,
            $conf['wrap2'] ?? null,
            $conf['wrap2.']['splitChar'] ?? '|'
        );
    }

    /**
     * dataWrap
     * Sixth of a set of different wraps which will be applied in a certain order before or after other functions that modify the content
     * Can fetch additional content the same way data does (i.e. {field:whatever}) and apply it to the wrap before that is applied to the content
     *
     * @param string $content Input value undergoing processing in this function.
     * @param array $conf stdWrap properties for dataWrap.
     * @return string The processed input value
     */
    public function stdWrap_dataWrap($content = '', $conf = [])
    {
        return $this->dataWrap($content, $conf['dataWrap']);
    }

    /**
     * prepend
     * A content object that will be prepended to the current content after most of the wraps have already been applied
     *
     * @param string $content Input value undergoing processing in this function.
     * @param array $conf stdWrap properties for prepend.
     * @return string The processed input value
     */
    public function stdWrap_prepend($content = '', $conf = [])
    {
        return $this->cObjGetSingle($conf['prepend'], $conf['prepend.'], '/stdWrap/.prepend') . $content;
    }

    /**
     * append
     * A content object that will be appended to the current content after most of the wraps have already been applied
     *
     * @param string $content Input value undergoing processing in this function.
     * @param array $conf stdWrap properties for append.
     * @return string The processed input value
     */
    public function stdWrap_append($content = '', $conf = [])
    {
        return $content . $this->cObjGetSingle($conf['append'], $conf['append.'], '/stdWrap/.append');
    }

    /**
     * wrap3
     * Seventh of a set of different wraps which will be applied in a certain order before or after other functions that modify the content
     * The default split character is | but it can be replaced with other characters by the property splitChar
     *
     * @param string $content Input value undergoing processing in this function.
     * @param array $conf stdWrap properties for wrap3.
     * @return string The processed input value
     */
    public function stdWrap_wrap3($content = '', $conf = [])
    {
        return $this->wrap(
            $content,
            $conf['wrap3'] ?? null,
            $conf['wrap3.']['splitChar'] ?? '|'
        );
    }

    /**
     * orderedStdWrap
     * Calls stdWrap for each entry in the provided array
     *
     * @param string $content Input value undergoing processing in this function.
     * @param array $conf stdWrap properties for orderedStdWrap.
     * @return string The processed input value
     */
    public function stdWrap_orderedStdWrap($content = '', $conf = [])
    {
        $sortedKeysArray = ArrayUtility::filterAndSortByNumericKeys($conf['orderedStdWrap.'], true);
        foreach ($sortedKeysArray as $key) {
            $content = $this->stdWrap($content, $conf['orderedStdWrap.'][$key . '.'] ?? null);
        }
        return $content;
    }

    /**
     * outerWrap
     * Eighth of a set of different wraps which will be applied in a certain order before or after other functions that modify the content
     *
     * @param string $content Input value undergoing processing in this function.
     * @param array $conf stdWrap properties for outerWrap.
     * @return string The processed input value
     */
    public function stdWrap_outerWrap($content = '', $conf = [])
    {
        return $this->wrap($content, $conf['outerWrap'] ?? null);
    }

    /**
     * insertData
     * Can fetch additional content the same way data does and replaces any occurrence of {field:whatever} with this content
     *
     * @param string $content Input value undergoing processing in this function.
     * @return string The processed input value
     */
    public function stdWrap_insertData($content = '')
    {
        return $this->insertData($content);
    }

    /**
     * postUserFunc
     * Will execute a user function after the content has been modified by any other stdWrap function
     *
     * @param string $content Input value undergoing processing in this function.
     * @param array $conf stdWrap properties for postUserFunc.
     * @return string The processed input value
     */
    public function stdWrap_postUserFunc($content = '', $conf = [])
    {
        return $this->callUserFunction($conf['postUserFunc'], $conf['postUserFunc.'], $content);
    }

    /**
     * postUserFuncInt
     * Will execute a user function after the content has been created and each time it is fetched from Cache
     * The result of this function itself will not be cached
     *
     * @param string $content Input value undergoing processing in this function.
     * @param array $conf stdWrap properties for postUserFuncInt.
     * @return string The processed input value
     */
    public function stdWrap_postUserFuncInt($content = '', $conf = [])
    {
        $substKey = 'INT_SCRIPT.' . $this->getTypoScriptFrontendController()->uniqueHash();
        $this->getTypoScriptFrontendController()->config['INTincScript'][$substKey] = [
            'content' => $content,
            'postUserFunc' => $conf['postUserFuncInt'],
            'conf' => $conf['postUserFuncInt.'],
            'type' => 'POSTUSERFUNC',
            'cObj' => serialize($this)
        ];
        $content = '<!--' . $substKey . '-->';
        return $content;
    }

    /**
     * prefixComment
     * Will add HTML comments to the content to make it easier to identify certain content elements within the HTML output later on
     *
     * @param string $content Input value undergoing processing in this function.
     * @param array $conf stdWrap properties for prefixComment.
     * @return string The processed input value
     */
    public function stdWrap_prefixComment($content = '', $conf = [])
    {
        if (
            (!isset($this->getTypoScriptFrontendController()->config['config']['disablePrefixComment']) || !$this->getTypoScriptFrontendController()->config['config']['disablePrefixComment'])
            && !empty($conf['prefixComment'])
        ) {
            $content = $this->prefixComment($conf['prefixComment'], [], $content);
        }
        return $content;
    }

    /**
     * editIcons
     * Will render icons for frontend editing as long as there is a BE user logged in
     *
     * @param string $content Input value undergoing processing in this function.
     * @param array $conf stdWrap properties for editIcons.
     * @return string The processed input value
     */
    public function stdWrap_editIcons($content = '', $conf = [])
    {
        if ($this->getTypoScriptFrontendController()->isBackendUserLoggedIn() && $conf['editIcons']) {
            if (!isset($conf['editIcons.']) || !is_array($conf['editIcons.'])) {
                $conf['editIcons.'] = [];
            }
            $content = $this->editIcons($content, $conf['editIcons'], $conf['editIcons.']);
        }
        return $content;
    }

    /**
     * editPanel
     * Will render the edit panel for frontend editing as long as there is a BE user logged in
     *
     * @param string $content Input value undergoing processing in this function.
     * @param array $conf stdWrap properties for editPanel.
     * @return string The processed input value
     */
    public function stdWrap_editPanel($content = '', $conf = [])
    {
        if ($this->getTypoScriptFrontendController()->isBackendUserLoggedIn()) {
            $content = $this->editPanel($content, $conf['editPanel.']);
        }
        return $content;
    }

    public function stdWrap_htmlSanitize(string $content = '', array $conf = []): string
    {
        $build = $conf['build'] ?? 'default';
        if (class_exists($build) && is_a($build, BuilderInterface::class, true)) {
            $builder = GeneralUtility::makeInstance($build);
        } else {
            $factory = GeneralUtility::makeInstance(SanitizerBuilderFactory::class);
            $builder = $factory->build($build);
        }
        $sanitizer = $builder->build();
        $initiator = $this->shallDebug()
            ? GeneralUtility::makeInstance(SanitizerInitiator::class, DebugUtility::debugTrail())
            : null;
        return $sanitizer->sanitize($content, $initiator);
    }

    /**
     * Store content into cache
     *
     * @param string $content Input value undergoing processing in these functions.
     * @param array $conf All stdWrap properties, not just the ones for a particular function.
     * @return string The processed input value
     */
    public function stdWrap_cacheStore($content = '', $conf = [])
    {
        if (!isset($conf['cache.'])) {
            return $content;
        }
        $key = $this->calculateCacheKey($conf['cache.']);
        if (empty($key)) {
            return $content;
        }
        /** @var \TYPO3\CMS\Core\Cache\Frontend\FrontendInterface $cacheFrontend */
        $cacheFrontend = GeneralUtility::makeInstance(CacheManager::class)->getCache('cache_hash');
        $tags = $this->calculateCacheTags($conf['cache.']);
        $lifetime = $this->calculateCacheLifetime($conf['cache.']);
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_content.php']['stdWrap_cacheStore'] ?? [] as $_funcRef) {
            $params = [
                'key' => $key,
                'content' => $content,
                'lifetime' => $lifetime,
                'tags' => $tags
            ];
            GeneralUtility::callUserFunction($_funcRef, $params, $this);
        }
        $cacheFrontend->set($key, $content, $tags, $lifetime);
        return $content;
    }

    /**
     * stdWrap post process hook
     * can be used by extensions authors to modify the behaviour of stdWrap functions to their needs
     * this hook executes functions at after the content has been modified by the rest of the stdWrap functions but still before debugging
     *
     * @param string $content Input value undergoing processing in these functions.
     * @param array $conf All stdWrap properties, not just the ones for a particular function.
     * @return string The processed input value
     */
    public function stdWrap_stdWrapPostProcess($content = '', $conf = [])
    {
        foreach ($this->stdWrapHookObjects as $hookObject) {
            /** @var ContentObjectStdWrapHookInterface $hookObject */
            $content = $hookObject->stdWrapPostProcess($content, $conf, $this);
        }
        return $content;
    }

    /**
     * debug
     * Will output the content as readable HTML code
     *
     * @param string $content Input value undergoing processing in this function.
     * @return string The processed input value
     */
    public function stdWrap_debug($content = '')
    {
        return '<pre>' . htmlspecialchars($content) . '</pre>';
    }

    /**
     * debugFunc
     * Will output the content in a debug table
     *
     * @param string $content Input value undergoing processing in this function.
     * @param array $conf stdWrap properties for debugFunc.
     * @return string The processed input value
     */
    public function stdWrap_debugFunc($content = '', $conf = [])
    {
        debug((int)$conf['debugFunc'] === 2 ? [$content] : $content);
        return $content;
    }

    /**
     * debugData
     * Will output the data used by the current record in a debug table
     *
     * @param string $content Input value undergoing processing in this function.
     * @return string The processed input value
     */
    public function stdWrap_debugData($content = '')
    {
        debug($this->data, '$cObj->data:');
        if (is_array($this->alternativeData)) {
            debug($this->alternativeData, '$this->alternativeData');
        }
        return $content;
    }

    /**
     * Returns number of rows selected by the query made by the properties set.
     * Implements the stdWrap "numRows" property
     *
     * @param array $conf TypoScript properties for the property (see link to "numRows")
     * @return int The number of rows found by the select
     * @internal
     * @see stdWrap()
     */
    public function numRows($conf)
    {
        $conf['select.']['selectFields'] = 'count(*)';
        $statement = $this->exec_getQuery($conf['table'], $conf['select.']);

        return (int)$statement->fetchColumn(0);
    }

    /**
     * Exploding a string by the $char value (if integer its an ASCII value) and returning index $listNum
     *
     * @param string $content String to explode
     * @param string $listNum Index-number. You can place the word "last" in it and it will be substituted with the pointer to the last value. You can use math operators like "+-/*" (passed to calc())
     * @param string $char Either a string used to explode the content string or an integer value which will then be changed into a character, eg. "10" for a linebreak char.
     * @return string
     */
    public function listNum($content, $listNum, $char)
    {
        $char = $char ?: ',';
        if (MathUtility::canBeInterpretedAsInteger($char)) {
            $char = chr($char);
        }
        $temp = explode($char, $content);
        $last = '' . (count($temp) - 1);
        // Take a random item if requested
        if ($listNum === 'rand') {
            $listNum = rand(0, count($temp) - 1);
        }
        $index = $this->calc(str_ireplace('last', $last, $listNum));
        return $temp[$index];
    }

    /**
     * Compares values together based on the settings in the input TypoScript array and returns the comparison result.
     * Implements the "if" function in TYPO3 TypoScript
     *
     * @param array $conf TypoScript properties defining what to compare
     * @return bool
     * @see stdWrap(), _parseFunc()
     */
    public function checkIf($conf)
    {
        if (!is_array($conf)) {
            return true;
        }
        if (isset($conf['directReturn'])) {
            return (bool)$conf['directReturn'];
        }
        $flag = true;
        if (isset($conf['isNull.'])) {
            $isNull = $this->stdWrap('', $conf['isNull.']);
            if ($isNull !== null) {
                $flag = false;
            }
        }
        if (isset($conf['isTrue']) || isset($conf['isTrue.'])) {
            $isTrue = isset($conf['isTrue.']) ? trim($this->stdWrap($conf['isTrue'], $conf['isTrue.'])) : trim($conf['isTrue']);
            if (!$isTrue) {
                $flag = false;
            }
        }
        if (isset($conf['isFalse']) || isset($conf['isFalse.'])) {
            $isFalse = isset($conf['isFalse.']) ? trim($this->stdWrap($conf['isFalse'], $conf['isFalse.'])) : trim($conf['isFalse']);
            if ($isFalse) {
                $flag = false;
            }
        }
        if (isset($conf['isPositive']) || isset($conf['isPositive.'])) {
            $number = isset($conf['isPositive.']) ? $this->calc($this->stdWrap($conf['isPositive'], $conf['isPositive.'])) : $this->calc($conf['isPositive']);
            if ($number < 1) {
                $flag = false;
            }
        }
        if ($flag) {
            $value = isset($conf['value.'])
                ? trim($this->stdWrap($conf['value'] ?? '', $conf['value.']))
                : trim($conf['value'] ?? '');
            if (isset($conf['isGreaterThan']) || isset($conf['isGreaterThan.'])) {
                $number = isset($conf['isGreaterThan.']) ? trim($this->stdWrap($conf['isGreaterThan'], $conf['isGreaterThan.'])) : trim($conf['isGreaterThan']);
                if ($number <= $value) {
                    $flag = false;
                }
            }
            if (isset($conf['isLessThan']) || isset($conf['isLessThan.'])) {
                $number = isset($conf['isLessThan.']) ? trim($this->stdWrap($conf['isLessThan'], $conf['isLessThan.'])) : trim($conf['isLessThan']);
                if ($number >= $value) {
                    $flag = false;
                }
            }
            if (isset($conf['equals']) || isset($conf['equals.'])) {
                $number = isset($conf['equals.']) ? trim($this->stdWrap($conf['equals'], $conf['equals.'])) : trim($conf['equals']);
                if ($number != $value) {
                    $flag = false;
                }
            }
            if (isset($conf['isInList']) || isset($conf['isInList.'])) {
                $number = isset($conf['isInList.']) ? trim($this->stdWrap($conf['isInList'], $conf['isInList.'])) : trim($conf['isInList']);
                if (!GeneralUtility::inList($value, $number)) {
                    $flag = false;
                }
            }
        }
        if ($conf['negate'] ?? false) {
            $flag = !$flag;
        }
        return $flag;
    }

    /**
     * Reads a directory for files and returns the filepaths in a string list separated by comma.
     * Implements the stdWrap property "filelist"
     *
     * @param string $data The command which contains information about what files/directory listing to return. See the "filelist" property of stdWrap for details.
     * @param bool $isCoreCall if set, the deprecation message is suppressed
     * @return string Comma list of files.
     * @internal
     * @see stdWrap()
     * @deprecated since TYPO3 v9.5, will be removed in TYPO3 v10.0. Use cObject FILES instead.
     */
    public function filelist($data, bool $isCoreCall = false)
    {
        if (!$isCoreCall) {
            trigger_error('ContentObjectRenderer->filelist() will be removed in TYPO3 v10.0. Use cObject FILES instead.', E_USER_DEPRECATED);
        }
        $data = trim($data);
        if ($data === '') {
            return '';
        }
        list($possiblePath, $ext_list, $sorting, $reverse, $useFullPath) = GeneralUtility::trimExplode('|', $data);
        // read directory:
        // MUST exist!
        $path = '';
        // proceeds if no '//', '..' or '\' is in the $theFile
        if (GeneralUtility::validPathStr($possiblePath)) {
            // Removes all dots, slashes and spaces after a path.
            $possiblePath = preg_replace('/[\\/\\. ]*$/', '', $possiblePath);
            if (!GeneralUtility::isAbsPath($possiblePath) && @is_dir($possiblePath)) {
                // Now check if it matches one of the FAL storages
                $storageRepository = GeneralUtility::makeInstance(StorageRepository::class);
                $storages = $storageRepository->findAll();
                foreach ($storages as $storage) {
                    if ($storage->getDriverType() === 'Local' && $storage->isPublic() && $storage->isOnline()) {
                        $folder = $storage->getPublicUrl($storage->getRootLevelFolder(), true);
                        if (GeneralUtility::isFirstPartOfStr($possiblePath . '/', $folder)) {
                            $path = $possiblePath;
                            break;
                        }
                    }
                }
            }
        }
        if (!$path) {
            return '';
        }
        $items = [
            'files' => [],
            'sorting' => []
        ];
        $ext_list = strtolower(GeneralUtility::uniqueList($ext_list));
        // Read dir:
        $d = @dir($path);
        if (is_object($d)) {
            $count = 0;
            while ($entry = $d->read()) {
                if ($entry !== '.' && $entry !== '..') {
                    // Because of odd PHP-error where <br />-tag is sometimes placed after a filename!!
                    $wholePath = $path . '/' . $entry;
                    if (file_exists($wholePath) && filetype($wholePath) === 'file') {
                        $info = GeneralUtility::split_fileref($wholePath);
                        if (!$ext_list || GeneralUtility::inList($ext_list, $info['fileext'])) {
                            $items['files'][] = $info['file'];
                            switch ($sorting) {
                                case 'name':
                                    $items['sorting'][] = strtolower($info['file']);
                                    break;
                                case 'size':
                                    $items['sorting'][] = filesize($wholePath);
                                    break;
                                case 'ext':
                                    $items['sorting'][] = $info['fileext'];
                                    break;
                                case 'date':
                                    $items['sorting'][] = filectime($wholePath);
                                    break;
                                case 'mdate':
                                    $items['sorting'][] = filemtime($wholePath);
                                    break;
                                default:
                                    $items['sorting'][] = $count;
                            }
                            $count++;
                        }
                    }
                }
            }
            $d->close();
        }
        // Sort if required
        if (!empty($items['sorting'])) {
            if (strtolower($reverse) !== 'r') {
                asort($items['sorting']);
            } else {
                arsort($items['sorting']);
            }
        }
        if (!empty($items['files'])) {
            // Make list
            reset($items['sorting']);
            $list_arr = [];
            foreach ($items['sorting'] as $key => $v) {
                $list_arr[] = $useFullPath ? $path . '/' . $items['files'][$key] : $items['files'][$key];
            }
            return implode(',', $list_arr);
        }
        return '';
    }

    /**
     * Passes the input value, $theValue, to an instance of "\TYPO3\CMS\Core\Html\HtmlParser"
     * together with the TypoScript options which are first converted from a TS style array
     * to a set of arrays with options for the \TYPO3\CMS\Core\Html\HtmlParser class.
     *
     * @param string $theValue The value to parse by the class \TYPO3\CMS\Core\Html\HtmlParser
     * @param array $conf TypoScript properties for the parser. See link.
     * @return string Return value.
     * @see stdWrap(), \TYPO3\CMS\Core\Html\HtmlParser::HTMLparserConfig(), \TYPO3\CMS\Core\Html\HtmlParser::HTMLcleaner()
     */
    public function HTMLparser_TSbridge($theValue, $conf)
    {
        $htmlParser = GeneralUtility::makeInstance(HtmlParser::class);
        $htmlParserCfg = $htmlParser->HTMLparserConfig($conf);
        return $htmlParser->HTMLcleaner($theValue, $htmlParserCfg[0], $htmlParserCfg[1], $htmlParserCfg[2], $htmlParserCfg[3]);
    }

    /**
     * Wrapping input value in a regular "wrap" but parses the wrapping value first for "insertData" codes.
     *
     * @param string $content Input string being wrapped
     * @param string $wrap The wrap string, eg. "<strong></strong>" or more likely here '<a href="index.php?id={TSFE:id}"> | </a>' which will wrap the input string in a <a> tag linking to the current page.
     * @return string Output string wrapped in the wrapping value.
     * @see insertData(), stdWrap()
     */
    public function dataWrap($content, $wrap)
    {
        return $this->wrap($content, $this->insertData($wrap));
    }

    /**
     * Implements the "insertData" property of stdWrap meaning that if strings matching {...} is found in the input string they
     * will be substituted with the return value from getData (datatype) which is passed the content of the curly braces.
     * If the content inside the curly braces starts with a hash sign {#...} it is a field name that must be quoted by Doctrine
     * DBAL and is skipped here for later processing.
     *
     * Example: If input string is "This is the page title: {page:title}" then the part, '{page:title}', will be substituted with
     * the current pages title field value.
     *
     * @param string $str Input value
     * @return string Processed input value
     * @see getData(), stdWrap(), dataWrap()
     */
    public function insertData($str)
    {
        $inside = 0;
        $newVal = '';
        $pointer = 0;
        $totalLen = strlen($str);
        do {
            if (!$inside) {
                $len = strcspn(substr($str, $pointer), '{');
                $newVal .= substr($str, $pointer, $len);
                $inside = true;
                if (substr($str, $pointer + $len + 1, 1) === '#') {
                    $len2 = strcspn(substr($str, $pointer + $len), '}');
                    $newVal .= substr($str, $pointer + $len, $len2);
                    $len += $len2;
                    $inside = false;
                }
            } else {
                $len = strcspn(substr($str, $pointer), '}') + 1;
                $newVal .= $this->getData(substr($str, $pointer + 1, $len - 2), $this->data);
                $inside = false;
            }
            $pointer += $len;
        } while ($pointer < $totalLen);
        return $newVal;
    }

    /**
     * Returns a HTML comment with the second part of input string (divided by "|") where first part is an integer telling how many trailing tabs to put before the comment on a new line.
     * Notice; this function (used by stdWrap) can be disabled by a "config.disablePrefixComment" setting in TypoScript.
     *
     * @param string $str Input value
     * @param array $conf TypoScript Configuration (not used at this point.)
     * @param string $content The content to wrap the comment around.
     * @return string Processed input value
     * @see stdWrap()
     */
    public function prefixComment($str, $conf, $content)
    {
        if (empty($str)) {
            return $content;
        }
        $parts = explode('|', $str);
        $indent = (int)$parts[0];
        $comment = htmlspecialchars($this->insertData($parts[1]));
        $output = LF
            . str_pad('', $indent, "\t") . '<!-- ' . $comment . ' [begin] -->' . LF
            . str_pad('', $indent + 1, "\t") . $content . LF
            . str_pad('', $indent, "\t") . '<!-- ' . $comment . ' [end] -->' . LF
            . str_pad('', $indent + 1, "\t");
        return $output;
    }

    /**
     * Implements the stdWrap property "substring" which is basically a TypoScript implementation of the PHP function, substr()
     *
     * @param string $content The string to perform the operation on
     * @param string $options The parameters to substring, given as a comma list of integers where the first and second number is passed as arg 1 and 2 to substr().
     * @return string The processed input value.
     * @internal
     * @see stdWrap()
     */
    public function substring($content, $options)
    {
        $options = GeneralUtility::intExplode(',', $options . ',');
        if ($options[1]) {
            return mb_substr($content, $options[0], $options[1], 'utf-8');
        }
        return mb_substr($content, $options[0], null, 'utf-8');
    }

    /**
     * Implements the stdWrap property "crop" which is a modified "substr" function allowing to limit a string length to a certain number of chars (from either start or end of string) and having a pre/postfix applied if the string really was cropped.
     *
     * @param string $content The string to perform the operation on
     * @param string $options The parameters splitted by "|": First parameter is the max number of chars of the string. Negative value means cropping from end of string. Second parameter is the pre/postfix string to apply if cropping occurs. Third parameter is a boolean value. If set then crop will be applied at nearest space.
     * @return string The processed input value.
     * @internal
     * @see stdWrap()
     */
    public function crop($content, $options)
    {
        $options = explode('|', $options);
        $chars = (int)$options[0];
        $afterstring = trim($options[1] ?? '');
        $crop2space = trim($options[2] ?? '');
        if ($chars) {
            if (mb_strlen($content, 'utf-8') > abs($chars)) {
                $truncatePosition = false;
                if ($chars < 0) {
                    $content = mb_substr($content, $chars, null, 'utf-8');
                    if ($crop2space) {
                        $truncatePosition = strpos($content, ' ');
                    }
                    $content = $truncatePosition ? $afterstring . substr($content, $truncatePosition) : $afterstring . $content;
                } else {
                    $content = mb_substr($content, 0, $chars, 'utf-8');
                    if ($crop2space) {
                        $truncatePosition = strrpos($content, ' ');
                    }
                    $content = $truncatePosition ? substr($content, 0, $truncatePosition) . $afterstring : $content . $afterstring;
                }
            }
        }
        return $content;
    }

    /**
     * Implements the stdWrap property "cropHTML" which is a modified "substr" function allowing to limit a string length
     * to a certain number of chars (from either start or end of string) and having a pre/postfix applied if the string
     * really was cropped.
     *
     * Compared to stdWrap.crop it respects HTML tags and entities.
     *
     * @param string $content The string to perform the operation on
     * @param string $options The parameters splitted by "|": First parameter is the max number of chars of the string. Negative value means cropping from end of string. Second parameter is the pre/postfix string to apply if cropping occurs. Third parameter is a boolean value. If set then crop will be applied at nearest space.
     * @return string The processed input value.
     * @internal
     * @see stdWrap()
     */
    public function cropHTML($content, $options)
    {
        $options = explode('|', $options);
        $chars = (int)$options[0];
        $absChars = abs($chars);
        $replacementForEllipsis = trim($options[1] ?? '');
        $crop2space = trim($options[2] ?? '') === '1';
        // Split $content into an array(even items in the array are outside the tags, odd numbers are tag-blocks).
        $tags = 'a|abbr|address|area|article|aside|audio|b|bdi|bdo|blockquote|body|br|button|caption|cite|code|col|colgroup|data|datalist|dd|del|dfn|div|dl|dt|em|embed|fieldset|figcaption|figure|font|footer|form|h1|h2|h3|h4|h5|h6|header|hr|i|iframe|img|input|ins|kbd|keygen|label|legend|li|link|main|map|mark|meter|nav|object|ol|optgroup|option|output|p|param|pre|progress|q|rb|rp|rt|rtc|ruby|s|samp|section|select|small|source|span|strong|sub|sup|table|tbody|td|textarea|tfoot|th|thead|time|tr|track|u|ul|ut|var|video|wbr';
        $tagsRegEx = '
			(
				(?:
					<!--.*?-->					# a comment
					|
					<canvas[^>]*>.*?</canvas>   # a canvas tag
					|
					<script[^>]*>.*?</script>   # a script tag
					|
					<noscript[^>]*>.*?</noscript> # a noscript tag
					|
					<template[^>]*>.*?</template> # a template tag
				)
				|
				</?(?:' . $tags . ')+			# opening tag (\'<tag\') or closing tag (\'</tag\')
				(?:
					(?:
						(?:
							\\s+\\w[\\w-]*		# EITHER spaces, followed by attribute names
							(?:
								\\s*=?\\s*		# equals
								(?>
									".*?"		# attribute values in double-quotes
									|
									\'.*?\'		# attribute values in single-quotes
									|
									[^\'">\\s]+	# plain attribute values
								)
							)?
						)
						|						# OR a single dash (for TYPO3 link tag)
						(?:
							\\s+-
						)
					)+\\s*
					|							# OR only spaces
					\\s*
				)
				/?>								# closing the tag with \'>\' or \'/>\'
			)';
        $splittedContent = preg_split('%' . $tagsRegEx . '%xs', $content, -1, PREG_SPLIT_DELIM_CAPTURE);
        // Reverse array if we are cropping from right.
        if ($chars < 0) {
            $splittedContent = array_reverse($splittedContent);
        }
        // Crop the text (chars of tag-blocks are not counted).
        $strLen = 0;
        // This is the offset of the content item which was cropped.
        $croppedOffset = null;
        $countSplittedContent = count($splittedContent);
        for ($offset = 0; $offset < $countSplittedContent; $offset++) {
            if ($offset % 2 === 0) {
                $tempContent = $splittedContent[$offset];
                $thisStrLen = mb_strlen(html_entity_decode($tempContent, ENT_COMPAT, 'UTF-8'), 'utf-8');
                if ($strLen + $thisStrLen > $absChars) {
                    $croppedOffset = $offset;
                    $cropPosition = $absChars - $strLen;
                    // The snippet "&[^&\s;]{2,8};" in the RegEx below represents entities.
                    $patternMatchEntityAsSingleChar = '(&[^&\\s;]{2,8};|.)';
                    $cropRegEx = $chars < 0 ? '#' . $patternMatchEntityAsSingleChar . '{0,' . ($cropPosition + 1) . '}$#uis' : '#^' . $patternMatchEntityAsSingleChar . '{0,' . ($cropPosition + 1) . '}#uis';
                    if (preg_match($cropRegEx, $tempContent, $croppedMatch)) {
                        $tempContentPlusOneCharacter = $croppedMatch[0];
                    } else {
                        $tempContentPlusOneCharacter = false;
                    }
                    $cropRegEx = $chars < 0 ? '#' . $patternMatchEntityAsSingleChar . '{0,' . $cropPosition . '}$#uis' : '#^' . $patternMatchEntityAsSingleChar . '{0,' . $cropPosition . '}#uis';
                    if (preg_match($cropRegEx, $tempContent, $croppedMatch)) {
                        $tempContent = $croppedMatch[0];
                        if ($crop2space && $tempContentPlusOneCharacter !== false) {
                            $cropRegEx = $chars < 0 ? '#(?<=\\s)' . $patternMatchEntityAsSingleChar . '{0,' . $cropPosition . '}$#uis' : '#^' . $patternMatchEntityAsSingleChar . '{0,' . $cropPosition . '}(?=\\s)#uis';
                            if (preg_match($cropRegEx, $tempContentPlusOneCharacter, $croppedMatch)) {
                                $tempContent = $croppedMatch[0];
                            }
                        }
                    }
                    $splittedContent[$offset] = $tempContent;
                    break;
                }
                $strLen += $thisStrLen;
            }
        }
        // Close cropped tags.
        $closingTags = [];
        if ($croppedOffset !== null) {
            $openingTagRegEx = '#^<(\\w+)(?:\\s|>)#';
            $closingTagRegEx = '#^</(\\w+)(?:\\s|>)#';
            for ($offset = $croppedOffset - 1; $offset >= 0; $offset = $offset - 2) {
                if (substr($splittedContent[$offset], -2) === '/>') {
                    // Ignore empty element tags (e.g. <br />).
                    continue;
                }
                preg_match($chars < 0 ? $closingTagRegEx : $openingTagRegEx, $splittedContent[$offset], $matches);
                $tagName = $matches[1] ?? null;
                if ($tagName !== null) {
                    // Seek for the closing (or opening) tag.
                    $countSplittedContent = count($splittedContent);
                    for ($seekingOffset = $offset + 2; $seekingOffset < $countSplittedContent; $seekingOffset = $seekingOffset + 2) {
                        preg_match($chars < 0 ? $openingTagRegEx : $closingTagRegEx, $splittedContent[$seekingOffset], $matches);
                        $seekingTagName = $matches[1] ?? null;
                        if ($tagName === $seekingTagName) {
                            // We found a matching tag.
                            // Add closing tag only if it occurs after the cropped content item.
                            if ($seekingOffset > $croppedOffset) {
                                $closingTags[] = $splittedContent[$seekingOffset];
                            }
                            break;
                        }
                    }
                }
            }
            // Drop the cropped items of the content array. The $closingTags will be added later on again.
            array_splice($splittedContent, $croppedOffset + 1);
        }
        $splittedContent = array_merge($splittedContent, [
            $croppedOffset !== null ? $replacementForEllipsis : ''
        ], $closingTags);
        // Reverse array once again if we are cropping from the end.
        if ($chars < 0) {
            $splittedContent = array_reverse($splittedContent);
        }
        return implode('', $splittedContent);
    }

    /**
     * Implements the TypoScript function "addParams"
     *
     * @param string $content The string with the HTML tag.
     * @param array $conf The TypoScript configuration properties
     * @param bool $isCoreCall if set, the deprecation message is suppressed
     * @return string The modified string
     * @todo Make it XHTML compatible. Will not present "/>" endings of tags right now. Further getting the tagname might fail if it is not separated by a normal space from the attributes.
     * @deprecated since TYPO3 v9.5, will be removed in TYPO3 v10.0.
     */
    public function addParams($content, $conf, $isCoreCall = false)
    {
        if (!$isCoreCall) {
            trigger_error('ContentObjectRenderer->addParams() will be removed in TYPO3 v10.0.', E_USER_DEPRECATED);
        }
        // For XHTML compliance.
        $lowerCaseAttributes = true;
        if (!is_array($conf)) {
            return $content;
        }
        $key = 1;
        $parts = explode('<', $content);
        if (isset($conf['_offset']) && (int)$conf['_offset']) {
            $key = (int)$conf['_offset'] < 0 ? count($parts) + (int)$conf['_offset'] : (int)$conf['_offset'];
        }
        $subparts = explode('>', $parts[$key] ?? '');
        if (trim($subparts[0])) {
            // Get attributes and name
            $attribs = GeneralUtility::get_tag_attributes('<' . $subparts[0] . '>');
            list($tagName) = explode(' ', $subparts[0], 2);
            // adds/overrides attributes
            foreach ($conf as $pkey => $val) {
                if (substr($pkey, -1) !== '.' && $pkey[0] !== '_') {
                    $tmpVal = isset($conf[$pkey . '.']) ? $this->stdWrap($conf[$pkey], $conf[$pkey . '.']) : (string)$val;
                    if ($lowerCaseAttributes) {
                        $pkey = strtolower($pkey);
                    }
                    if ($tmpVal !== '') {
                        $attribs[$pkey] = $tmpVal;
                    }
                }
            }
            // Re-assembles the tag and content
            $subparts[0] = trim($tagName . ' ' . GeneralUtility::implodeAttributes($attribs));
            $parts[$key] = implode('>', $subparts);
            $content = implode('<', $parts);
        }
        return $content;
    }

    /**
     * Creates a list of links to files.
     * Implements the stdWrap property "filelink"
     *
     * @param string $theValue The filename to link to, possibly prefixed with $conf[path]
     * @param array $conf TypoScript parameters for the TypoScript function ->filelink
     * @param bool $isCoreCall if set, the deprecation message is suppressed
     * @return string The link to the file possibly with icons, thumbnails, size in bytes shown etc.
     * @internal
     * @see stdWrap()
     * @deprecated since TYPO3 v9.5, will be removed in TYPO3 v10.0. Use cObject FILES instead.
     */
    public function filelink($theValue, $conf, $isCoreCall = false)
    {
        if (!$isCoreCall) {
            trigger_error('ContentObjectRenderer->filelink() will be removed in TYPO3 v10.0. Use cObject FILES instead.', E_USER_DEPRECATED);
        }
        $conf['path'] = isset($conf['path.'])
            ? $this->stdWrap($conf['path'] ?? '', $conf['path.'])
            : ($conf['path'] ?? '');
        $theFile = trim($conf['path']) . $theValue;
        if (!@is_file($theFile)) {
            return '';
        }
        $theFileEnc = str_replace('%2F', '/', rawurlencode($theFile));
        $title = $conf['title'] ?? '';
        if (isset($conf['title.'])) {
            $title = $this->stdWrap($title, $conf['title.']);
        }
        $target = $conf['target'] ?? '';
        if (isset($conf['target.'])) {
            $target = $this->stdWrap($target, $conf['target.']);
        }
        $tsfe = $this->getTypoScriptFrontendController();

        $typoLinkConf = [
            'parameter' => $theFileEnc,
            'fileTarget' => $target,
            'title' => $title,
            'ATagParams' => $this->getATagParams($conf)
        ];

        if (isset($conf['typolinkConfiguration.'])) {
            $additionalTypoLinkConfiguration = $conf['typolinkConfiguration.'];
            // We only allow additional configuration. This is why the generated conf overwrites the additional conf.
            ArrayUtility::mergeRecursiveWithOverrule($additionalTypoLinkConfiguration, $typoLinkConf);
            $typoLinkConf = $additionalTypoLinkConfiguration;
        }

        $theLinkWrap = $this->typoLink('|', $typoLinkConf);
        $theSize = filesize($theFile);
        $fI = GeneralUtility::split_fileref($theFile);
        $icon = '';
        if ($conf['icon'] ?? false) {
            $conf['icon.']['path'] = isset($conf['icon.']['path.'])
                ? $this->stdWrap($conf['icon.']['path'], $conf['icon.']['path.'])
                : $conf['icon.']['path'];
            $iconPath = !empty($conf['icon.']['path'])
                ? $conf['icon.']['path']
                : GeneralUtility::getFileAbsFileName('EXT:frontend/Resources/Public/Icons/FileIcons/');
            $conf['icon.']['ext'] = isset($conf['icon.']['ext.'])
                ? $this->stdWrap($conf['icon.']['ext'], $conf['icon.']['ext.'])
                : $conf['icon.']['ext'];
            $iconExt = !empty($conf['icon.']['ext']) ? '.' . $conf['icon.']['ext'] : '.gif';
            $icon = @is_file($iconPath . $fI['fileext'] . $iconExt)
                ? $iconPath . $fI['fileext'] . $iconExt
                : $iconPath . 'default' . $iconExt;
            $icon = PathUtility::stripPathSitePrefix($icon);
            // Checking for images: If image, then return link to thumbnail.
            $IEList = isset($conf['icon_image_ext_list.']) ? $this->stdWrap($conf['icon_image_ext_list'], $conf['icon_image_ext_list.']) : $conf['icon_image_ext_list'];
            $image_ext_list = str_replace(' ', '', strtolower($IEList));
            if ($fI['fileext'] && GeneralUtility::inList($image_ext_list, $fI['fileext'])) {
                if ($conf['iconCObject']) {
                    $icon = $this->cObjGetSingle($conf['iconCObject'], $conf['iconCObject.'], 'iconCObject');
                } else {
                    $notFoundThumb = GeneralUtility::getFileAbsFileName('EXT:core/Resources/Public/Images/NotFound.gif');
                    $notFoundThumb = PathUtility::stripPathSitePrefix($notFoundThumb);
                    $sizeParts = [64, 64];
                    if ($GLOBALS['TYPO3_CONF_VARS']['GFX']['thumbnails']) {
                        // using the File Abstraction Layer to generate a preview image
                        try {
                            /** @var File $fileObject */
                            $fileObject = ResourceFactory::getInstance()->retrieveFileOrFolderObject($theFile);
                            if ($fileObject->isMissing()) {
                                $icon = $notFoundThumb;
                            } else {
                                $fileExtension = $fileObject->getExtension();
                                if ($fileExtension === 'ttf' || GeneralUtility::inList($GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'], $fileExtension)) {
                                    if ($conf['icon_thumbSize'] || $conf['icon_thumbSize.']) {
                                        $thumbSize = isset($conf['icon_thumbSize.']) ? $this->stdWrap($conf['icon_thumbSize'], $conf['icon_thumbSize.']) : $conf['icon_thumbSize'];
                                        $sizeParts = explode('x', $thumbSize);
                                    }
                                    $icon = $fileObject->process(ProcessedFile::CONTEXT_IMAGEPREVIEW, [
                                        'width' => $sizeParts[0],
                                        'height' => $sizeParts[1]
                                    ])->getPublicUrl(true);
                                }
                            }
                        } catch (ResourceDoesNotExistException $exception) {
                            $icon = $notFoundThumb;
                        }
                    } else {
                        $icon = $notFoundThumb;
                    }
                    $urlPrefix = '';
                    if (parse_url($icon, PHP_URL_HOST) === null) {
                        $urlPrefix = $tsfe->absRefPrefix;
                    }
                    $icon = '<img src="' . htmlspecialchars($urlPrefix . $icon) . '"' .
                        ' width="' . (int)$sizeParts[0] . '" height="' . (int)$sizeParts[1] . '" ' .
                        $this->getBorderAttr(' border="0"') . '' . $this->getAltParam($conf) . ' />';
                }
            } else {
                $conf['icon.']['widthAttribute'] = isset($conf['icon.']['widthAttribute.'])
                    ? $this->stdWrap($conf['icon.']['widthAttribute'], $conf['icon.']['widthAttribute.'])
                    : $conf['icon.']['widthAttribute'];
                $iconWidth = !empty($conf['icon.']['widthAttribute']) ? $conf['icon.']['widthAttribute'] : 18;
                $conf['icon.']['heightAttribute'] = isset($conf['icon.']['heightAttribute.'])
                    ? $this->stdWrap($conf['icon.']['heightAttribute'], $conf['icon.']['heightAttribute.'])
                    : $conf['icon.']['heightAttribute'];
                $iconHeight = !empty($conf['icon.']['heightAttribute']) ? (int)$conf['icon.']['heightAttribute'] : 16;
                $icon = '<img src="' . htmlspecialchars($tsfe->absRefPrefix . $icon) . '" width="' . (int)$iconWidth . '" height="' . (int)$iconHeight . '"'
                    . $this->getBorderAttr(' border="0"') . $this->getAltParam($conf) . ' />';
            }
            if ($conf['icon_link'] && !$conf['combinedLink']) {
                $icon = $this->wrap($icon, $theLinkWrap);
            }
            $icon = isset($conf['icon.']) ? $this->stdWrap($icon, $conf['icon.']) : $icon;
        }
        $size = '';
        if ($conf['size'] ?? false) {
            $size = isset($conf['size.']) ? $this->stdWrap($theSize, $conf['size.']) : $theSize;
        }
        // Wrapping file label
        if ($conf['removePrependedNumbers'] ?? false) {
            $theValue = preg_replace('/_[0-9][0-9](\\.[[:alnum:]]*)$/', '\\1', $theValue);
        }
        if (isset($conf['labelStdWrap.'])) {
            $theValue = $this->stdWrap($theValue, $conf['labelStdWrap.']);
        }
        // Wrapping file
        $wrap = isset($conf['wrap.'])
            ? $this->stdWrap($conf['wrap'] ?? '', $conf['wrap.'])
            : ($conf['wrap'] ?? '');
        if ($conf['combinedLink'] ?? false) {
            $theValue = $icon . $theValue;
            if ($conf['ATagBeforeWrap']) {
                $theValue = $this->wrap($this->wrap($theValue, $wrap), $theLinkWrap);
            } else {
                $theValue = $this->wrap($this->wrap($theValue, $theLinkWrap), $wrap);
            }
            $file = isset($conf['file.']) ? $this->stdWrap($theValue, $conf['file.']) : $theValue;
            // output
            $output = $file . $size;
        } else {
            if ($conf['ATagBeforeWrap'] ?? false) {
                $theValue = $this->wrap($this->wrap($theValue, $wrap), $theLinkWrap);
            } else {
                $theValue = $this->wrap($this->wrap($theValue, $theLinkWrap), $wrap);
            }
            $file = isset($conf['file.']) ? $this->stdWrap($theValue, $conf['file.']) : $theValue;
            // output
            $output = $icon . $file . $size;
        }
        if (isset($conf['stdWrap.'])) {
            $output = $this->stdWrap($output, $conf['stdWrap.']);
        }
        return $output;
    }

    /**
     * Performs basic mathematical evaluation of the input string. Does NOT take parathesis and operator precedence into account! (for that, see \TYPO3\CMS\Core\Utility\MathUtility::calculateWithPriorityToAdditionAndSubtraction())
     *
     * @param string $val The string to evaluate. Example: "3+4*10/5" will generate "35". Only integer numbers can be used.
     * @return int The result (might be a float if you did a division of the numbers).
     * @see \TYPO3\CMS\Core\Utility\MathUtility::calculateWithPriorityToAdditionAndSubtraction()
     */
    public function calc($val)
    {
        $parts = GeneralUtility::splitCalc($val, '+-*/');
        $value = 0;
        foreach ($parts as $part) {
            $theVal = $part[1];
            $sign = $part[0];
            if ((string)(int)$theVal === (string)$theVal) {
                $theVal = (int)$theVal;
            } else {
                $theVal = 0;
            }
            if ($sign === '-') {
                $value -= $theVal;
            }
            if ($sign === '+') {
                $value += $theVal;
            }
            if ($sign === '/') {
                if ((int)$theVal) {
                    $value /= (int)$theVal;
                }
            }
            if ($sign === '*') {
                $value *= $theVal;
            }
        }
        return $value;
    }

    /**
     * This explodes a comma-list into an array where the values are parsed through ContentObjectRender::calc() and cast to (int)(so you are sure to have integers in the output array)
     * Used to split and calculate min and max values for GMENUs.
     *
     * @param string $delim Delimited to explode by
     * @param string $string The string with parts in (where each part is evaluated by ->calc())
     * @return array And array with evaluated values.
     * @see calc(), \TYPO3\CMS\Frontend\ContentObject\Menu\GraphicalMenuContentObject::makeGifs()
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0. It is solely used in GMENU, which can be handled there directly.
     */
    public function calcIntExplode($delim, $string)
    {
        trigger_error('calcIntExplode will be removed in TYPO3 v10.0.', E_USER_DEPRECATED);
        $temp = explode($delim, $string);
        foreach ($temp as $key => $val) {
            $temp[$key] = (int)$this->calc($val);
        }
        return $temp;
    }

    /**
     * Implements the "split" property of stdWrap; Splits a string based on a token (given in TypoScript properties), sets the "current" value to each part and then renders a content object pointer to by a number.
     * In classic TypoScript (like 'content (default)'/'styles.content (default)') this is used to render tables, splitting rows and cells by tokens and putting them together again wrapped in <td> tags etc.
     * Implements the "optionSplit" processing of the TypoScript options for each splitted value to parse.
     *
     * @param string $value The string value to explode by $conf[token] and process each part
     * @param array $conf TypoScript properties for "split
     * @return string Compiled result
     * @internal
     * @see stdWrap(), \TYPO3\CMS\Frontend\ContentObject\Menu\AbstractMenuContentObject::procesItemStates()
     */
    public function splitObj($value, $conf)
    {
        $conf['token'] = isset($conf['token.']) ? $this->stdWrap($conf['token'], $conf['token.']) : $conf['token'];
        if ($conf['token'] === '') {
            return $value;
        }
        $valArr = explode($conf['token'], $value);

        // return value directly by returnKey. No further processing
        if (!empty($valArr) && (MathUtility::canBeInterpretedAsInteger($conf['returnKey'] ?? null) || ($conf['returnKey.'] ?? false))
        ) {
            $key = isset($conf['returnKey.']) ? (int)$this->stdWrap($conf['returnKey'], $conf['returnKey.']) : (int)$conf['returnKey'];
            return $valArr[$key] ?? '';
        }

        // return the amount of elements. No further processing
        if (!empty($valArr) && ($conf['returnCount'] || $conf['returnCount.'])) {
            $returnCount = isset($conf['returnCount.']) ? (bool)$this->stdWrap($conf['returnCount'], $conf['returnCount.']) : (bool)$conf['returnCount'];
            return $returnCount ? count($valArr) : 0;
        }

        // calculate splitCount
        $splitCount = count($valArr);
        $max = isset($conf['max.']) ? (int)$this->stdWrap($conf['max'], $conf['max.']) : (int)$conf['max'];
        if ($max && $splitCount > $max) {
            $splitCount = $max;
        }
        $min = isset($conf['min.']) ? (int)$this->stdWrap($conf['min'], $conf['min.']) : (int)$conf['min'];
        if ($min && $splitCount < $min) {
            $splitCount = $min;
        }
        $wrap = isset($conf['wrap.']) ? (string)$this->stdWrap($conf['wrap'], $conf['wrap.']) : (string)$conf['wrap'];
        $cObjNumSplitConf = isset($conf['cObjNum.']) ? (string)$this->stdWrap($conf['cObjNum'], $conf['cObjNum.']) : (string)$conf['cObjNum'];
        $splitArr = [];
        if ($wrap !== '' || $cObjNumSplitConf !== '') {
            $splitArr['wrap'] = $wrap;
            $splitArr['cObjNum'] = $cObjNumSplitConf;
            $splitArr = GeneralUtility::makeInstance(TypoScriptService::class)
                ->explodeConfigurationForOptionSplit($splitArr, $splitCount);
        }
        $content = '';
        for ($a = 0; $a < $splitCount; $a++) {
            $this->getTypoScriptFrontendController()->register['SPLIT_COUNT'] = $a;
            $value = '' . $valArr[$a];
            $this->data[$this->currentValKey] = $value;
            if ($splitArr[$a]['cObjNum']) {
                $objName = (int)$splitArr[$a]['cObjNum'];
                $value = isset($conf[$objName . '.'])
                    ? $this->stdWrap($this->cObjGet($conf[$objName . '.'], $objName . '.'), $conf[$objName . '.'])
                    : $this->cObjGet($conf[$objName . '.'], $objName . '.');
            }
            $wrap = isset($splitArr[$a]['wrap.']) ? $this->stdWrap($splitArr[$a]['wrap'], $splitArr[$a]['wrap.']) : $splitArr[$a]['wrap'];
            if ($wrap) {
                $value = $this->wrap($value, $wrap);
            }
            $content .= $value;
        }
        return $content;
    }

    /**
     * Processes ordered replacements on content data.
     *
     * @param string $content The content to be processed
     * @param array $configuration The TypoScript configuration for stdWrap.replacement
     * @return string The processed content data
     */
    protected function replacement($content, array $configuration)
    {
        // Sorts actions in configuration by numeric index
        ksort($configuration, SORT_NUMERIC);
        foreach ($configuration as $index => $action) {
            // Checks whether we have an valid action and a numeric key ending with a dot ("10.")
            if (is_array($action) && substr($index, -1) === '.' && MathUtility::canBeInterpretedAsInteger(substr($index, 0, -1))) {
                $content = $this->replacementSingle($content, $action);
            }
        }
        return $content;
    }

    /**
     * Processes a single search/replace on content data.
     *
     * @param string $content The content to be processed
     * @param array $configuration The TypoScript of the search/replace action to be processed
     * @return string The processed content data
     */
    protected function replacementSingle($content, array $configuration)
    {
        if ((isset($configuration['search']) || isset($configuration['search.'])) && (isset($configuration['replace']) || isset($configuration['replace.']))) {
            // Gets the strings
            $search = isset($configuration['search.']) ? $this->stdWrap($configuration['search'], $configuration['search.']) : $configuration['search'];
            $replace = isset($configuration['replace.'])
                ? $this->stdWrap($configuration['replace'] ?? null, $configuration['replace.'])
                : $configuration['replace'] ?? null;
            $useRegularExpression = false;
            // Determines whether regular expression shall be used
            if (isset($configuration['useRegExp'])
                || (isset($configuration['useRegExp.']) && $configuration['useRegExp.'])
            ) {
                $useRegularExpression = isset($configuration['useRegExp.']) ? (bool)$this->stdWrap($configuration['useRegExp'], $configuration['useRegExp.']) : (bool)$configuration['useRegExp'];
            }
            $useOptionSplitReplace = false;
            // Determines whether replace-pattern uses option-split
            if (isset($configuration['useOptionSplitReplace']) || isset($configuration['useOptionSplitReplace.'])) {
                $useOptionSplitReplace = isset($configuration['useOptionSplitReplace.']) ? (bool)$this->stdWrap($configuration['useOptionSplitReplace'], $configuration['useOptionSplitReplace.']) : (bool)$configuration['useOptionSplitReplace'];
            }

            // Performs a replacement by preg_replace()
            if ($useRegularExpression) {
                // Get separator-character which precedes the string and separates search-string from the modifiers
                $separator = $search[0];
                $startModifiers = strrpos($search, $separator);
                if ($separator !== false && $startModifiers > 0) {
                    $modifiers = substr($search, $startModifiers + 1);
                    // remove "e" (eval-modifier), which would otherwise allow to run arbitrary PHP-code
                    $modifiers = str_replace('e', '', $modifiers);
                    $search = substr($search, 0, $startModifiers + 1) . $modifiers;
                }
                if ($useOptionSplitReplace) {
                    // init for replacement
                    $splitCount = preg_match_all($search, $content, $matches);
                    $typoScriptService = GeneralUtility::makeInstance(TypoScriptService::class);
                    $replaceArray = $typoScriptService->explodeConfigurationForOptionSplit([$replace], $splitCount);
                    $replaceCount = 0;

                    $replaceCallback = function ($match) use ($replaceArray, $search, &$replaceCount) {
                        $replaceCount++;
                        return preg_replace($search, $replaceArray[$replaceCount - 1][0], $match[0]);
                    };
                    $content = preg_replace_callback($search, $replaceCallback, $content);
                } else {
                    $content = preg_replace($search, $replace, $content);
                }
            } elseif ($useOptionSplitReplace) {
                // turn search-string into a preg-pattern
                $searchPreg = '#' . preg_quote($search, '#') . '#';

                // init for replacement
                $splitCount = preg_match_all($searchPreg, $content, $matches);
                $typoScriptService = GeneralUtility::makeInstance(TypoScriptService::class);
                $replaceArray = $typoScriptService->explodeConfigurationForOptionSplit([$replace], $splitCount);
                $replaceCount = 0;

                $replaceCallback = function () use ($replaceArray, $search, &$replaceCount) {
                    $replaceCount++;
                    return $replaceArray[$replaceCount - 1][0];
                };
                $content = preg_replace_callback($searchPreg, $replaceCallback, $content);
            } else {
                $content = str_replace($search, $replace, $content);
            }
        }
        return $content;
    }

    /**
     * Implements the "round" property of stdWrap
     * This is a Wrapper function for PHP's rounding functions (round,ceil,floor), defaults to round()
     *
     * @param string $content Value to process
     * @param array $conf TypoScript configuration for round
     * @return string The formatted number
     */
    protected function round($content, array $conf = [])
    {
        $decimals = isset($conf['decimals.'])
            ? $this->stdWrap($conf['decimals'] ?? '', $conf['decimals.'])
            : ($conf['decimals'] ?? null);
        $type = isset($conf['roundType.'])
            ? $this->stdWrap($conf['roundType'] ?? '', $conf['roundType.'])
            : ($conf['roundType'] ?? null);
        $floatVal = (float)$content;
        switch ($type) {
            case 'ceil':
                $content = ceil($floatVal);
                break;
            case 'floor':
                $content = floor($floatVal);
                break;
            case 'round':

            default:
                $content = round($floatVal, (int)$decimals);
        }
        return $content;
    }

    /**
     * Implements the stdWrap property "numberFormat"
     * This is a Wrapper function for php's number_format()
     *
     * @param float $content Value to process
     * @param array $conf TypoScript Configuration for numberFormat
     * @return string The formatted number
     */
    public function numberFormat($content, $conf)
    {
        $decimals = isset($conf['decimals.'])
            ? (int)$this->stdWrap($conf['decimals'] ?? '', $conf['decimals.'])
            : (int)($conf['decimals'] ?? 0);
        $dec_point = isset($conf['dec_point.'])
            ? $this->stdWrap($conf['dec_point'] ?? '', $conf['dec_point.'])
            : ($conf['dec_point'] ?? null);
        $thousands_sep = isset($conf['thousands_sep.'])
            ? $this->stdWrap($conf['thousands_sep'] ?? '', $conf['thousands_sep.'])
            : ($conf['thousands_sep'] ?? null);
        return number_format((float)$content, $decimals, $dec_point, $thousands_sep);
    }

    /**
     * Implements the stdWrap property, "parseFunc".
     * This is a function with a lot of interesting uses. In classic TypoScript this is used to process text
     * from the bodytext field; This included highlighting of search words, changing http:// and mailto: prefixed strings into etc.
     * It is still a very important function for processing of bodytext which is normally stored in the database
     * in a format which is not fully ready to be outputted.
     * This situation has not become better by having a RTE around...
     *
     * This function is actually just splitting the input content according to the configuration of "external blocks".
     * This means that before the input string is actually "parsed" it will be splitted into the parts configured to BE parsed
     * (while other parts/blocks should NOT be parsed).
     * Therefore the actual processing of the parseFunc properties goes on in ->_parseFunc()
     *
     * @param string $theValue The value to process.
     * @param array $conf TypoScript configuration for parseFunc
     * @param string $ref Reference to get configuration from. Eg. "< lib.parseFunc" which means that the configuration of the object path "lib.parseFunc" will be retrieved and MERGED with what is in $conf!
     * @return string The processed value
     * @see _parseFunc()
     */
    public function parseFunc($theValue, $conf, $ref = '')
    {
        // Fetch / merge reference, if any
        if ($ref) {
            $temp_conf = [
                'parseFunc' => $ref,
                'parseFunc.' => $conf
            ];
            $temp_conf = $this->mergeTSRef($temp_conf, 'parseFunc');
            $conf = $temp_conf['parseFunc.'];
        }
        // early return, no processing in case no configuration is given
        if (empty($conf)) {
            // @deprecated Invoking ContentObjectRenderer::parseFunc without any configuration will trigger an exception in TYPO3 v12.0
            trigger_error('Invoking ContentObjectRenderer::parseFunc without any configuration will trigger an exception in TYPO3 v12.0', E_USER_DEPRECATED);
            return $theValue;
        }
        // Handle HTML sanitizer invocation
        if (!isset($conf['htmlSanitize'])) {
            // @deprecated Property htmlSanitize was not defined, but will be mandatory in TYPO3 v12.0
            trigger_error('Property htmlSanitize was not defined, but will be mandatory in TYPO3 v12.0', E_USER_DEPRECATED);
            $features = GeneralUtility::makeInstance(Features::class);
            $conf['htmlSanitize'] = $features->isFeatureEnabled('security.frontend.htmlSanitizeParseFuncDefault');
        }
        $conf['htmlSanitize'] = (bool)$conf['htmlSanitize'];

        // Process:
        if ((string)($conf['externalBlocks'] ?? '') === '') {
            $result = $this->_parseFunc($theValue, $conf);
            if ($conf['htmlSanitize']) {
                $result = $this->stdWrap_htmlSanitize($result, $conf['htmlSanitize.'] ?? []);
            }
            return $result;
        }
        $tags = strtolower(implode(',', GeneralUtility::trimExplode(',', $conf['externalBlocks'])));
        $htmlParser = GeneralUtility::makeInstance(HtmlParser::class);
        $parts = $htmlParser->splitIntoBlock($tags, $theValue);
        foreach ($parts as $k => $v) {
            if ($k % 2) {
                // font:
                $tagName = strtolower($htmlParser->getFirstTagName($v));
                $cfg = $conf['externalBlocks.'][$tagName . '.'];
                if ($cfg['stripNLprev'] || $cfg['stripNL']) {
                    $parts[$k - 1] = preg_replace('/' . CR . '?' . LF . '[ ]*$/', '', $parts[$k - 1]);
                }
                if ($cfg['stripNLnext'] || $cfg['stripNL']) {
                    $parts[$k + 1] = preg_replace('/^[ ]*' . CR . '?' . LF . '/', '', $parts[$k + 1]);
                }
            }
        }
        foreach ($parts as $k => $v) {
            if ($k % 2) {
                $tag = $htmlParser->getFirstTag($v);
                $tagName = strtolower($htmlParser->getFirstTagName($v));
                $cfg = $conf['externalBlocks.'][$tagName . '.'];
                if ($cfg['callRecursive']) {
                    $parts[$k] = $this->parseFunc($htmlParser->removeFirstAndLastTag($v), $conf);
                    if (!$cfg['callRecursive.']['dontWrapSelf']) {
                        if ($cfg['callRecursive.']['alternativeWrap']) {
                            $parts[$k] = $this->wrap($parts[$k], $cfg['callRecursive.']['alternativeWrap']);
                        } else {
                            if (is_array($cfg['callRecursive.']['tagStdWrap.'])) {
                                $tag = $this->stdWrap($tag, $cfg['callRecursive.']['tagStdWrap.']);
                            }
                            $parts[$k] = $tag . $parts[$k] . '</' . $tagName . '>';
                        }
                    }
                } elseif ($cfg['HTMLtableCells']) {
                    $rowParts = $htmlParser->splitIntoBlock('tr', $parts[$k]);
                    foreach ($rowParts as $kk => $vv) {
                        if ($kk % 2) {
                            $colParts = $htmlParser->splitIntoBlock('td,th', $vv);
                            $cc = 0;
                            foreach ($colParts as $kkk => $vvv) {
                                if ($kkk % 2) {
                                    $cc++;
                                    $tag = $htmlParser->getFirstTag($vvv);
                                    $tagName = strtolower($htmlParser->getFirstTagName($vvv));
                                    $colParts[$kkk] = $htmlParser->removeFirstAndLastTag($vvv);
                                    if ($cfg['HTMLtableCells.'][$cc . '.']['callRecursive'] || !isset($cfg['HTMLtableCells.'][$cc . '.']['callRecursive']) && $cfg['HTMLtableCells.']['default.']['callRecursive']) {
                                        if ($cfg['HTMLtableCells.']['addChr10BetweenParagraphs']) {
                                            $colParts[$kkk] = str_replace('</p><p>', '</p>' . LF . '<p>', $colParts[$kkk]);
                                        }
                                        $colParts[$kkk] = $this->parseFunc($colParts[$kkk], $conf);
                                    }
                                    $tagStdWrap = is_array($cfg['HTMLtableCells.'][$cc . '.']['tagStdWrap.'])
                                        ? $cfg['HTMLtableCells.'][$cc . '.']['tagStdWrap.']
                                        : $cfg['HTMLtableCells.']['default.']['tagStdWrap.'];
                                    if (is_array($tagStdWrap)) {
                                        $tag = $this->stdWrap($tag, $tagStdWrap);
                                    }
                                    $stdWrap = is_array($cfg['HTMLtableCells.'][$cc . '.']['stdWrap.'])
                                        ? $cfg['HTMLtableCells.'][$cc . '.']['stdWrap.']
                                        : $cfg['HTMLtableCells.']['default.']['stdWrap.'];
                                    if (is_array($stdWrap)) {
                                        $colParts[$kkk] = $this->stdWrap($colParts[$kkk], $stdWrap);
                                    }
                                    $colParts[$kkk] = $tag . $colParts[$kkk] . '</' . $tagName . '>';
                                }
                            }
                            $rowParts[$kk] = implode('', $colParts);
                        }
                    }
                    $parts[$k] = implode('', $rowParts);
                }
                if (is_array($cfg['stdWrap.'])) {
                    $parts[$k] = $this->stdWrap($parts[$k], $cfg['stdWrap.']);
                }
            } else {
                $parts[$k] = $this->_parseFunc($parts[$k], $conf);
            }
        }
        $result = implode('', $parts);
        if ($conf['htmlSanitize']) {
            $result = $this->stdWrap_htmlSanitize($result, $conf['htmlSanitize.'] ?? []);
        }
        return $result;
    }

    /**
     * Helper function for parseFunc()
     *
     * @param string $theValue The value to process.
     * @param array $conf TypoScript configuration for parseFunc
     * @return string The processed value
     * @internal
     * @see parseFunc()
     */
    public function _parseFunc($theValue, $conf)
    {
        if (!empty($conf['if.']) && !$this->checkIf($conf['if.'])) {
            return $theValue;
        }
        // Indicates that the data is from within a tag.
        $inside = false;
        // Pointer to the total string position
        $pointer = 0;
        // Loaded with the current typo-tag if any.
        $currentTag = '';
        $stripNL = 0;
        $contentAccum = [];
        $contentAccumP = 0;
        $allowTags = strtolower(str_replace(' ', '', $conf['allowTags'] ?? ''));
        $denyTags = strtolower(str_replace(' ', '', $conf['denyTags'] ?? ''));
        $totalLen = strlen($theValue);
        do {
            if (!$inside) {
                if (!is_array($currentTag)) {
                    // These operations should only be performed on code outside the typotags...
                    // data: this checks that we enter tags ONLY if the first char in the tag is alphanumeric OR '/'
                    $len_p = 0;
                    $c = 100;
                    do {
                        $len = strcspn(substr($theValue, $pointer + $len_p), '<');
                        $len_p += $len + 1;
                        $endChar = ord(strtolower(substr($theValue, $pointer + $len_p, 1)));
                        $c--;
                    } while ($c > 0 && $endChar && ($endChar < 97 || $endChar > 122) && $endChar != 47);
                    $len = $len_p - 1;
                } else {
                    // If we're inside a currentTag, just take it to the end of that tag!
                    $tempContent = strtolower(substr($theValue, $pointer));
                    $len = strpos($tempContent, '</' . $currentTag[0]);
                    if (is_string($len) && !$len) {
                        $len = strlen($tempContent);
                    }
                }
                // $data is the content until the next <tag-start or end is detected.
                // In case of a currentTag set, this would mean all data between the start- and end-tags
                $data = substr($theValue, $pointer, $len);
                if ($data != '') {
                    if ($stripNL) {
                        // If the previous tag was set to strip NewLines in the beginning of the next data-chunk.
                        $data = preg_replace('/^[ ]*' . CR . '?' . LF . '/', '', $data);
                    }
                    // These operations should only be performed on code outside the tags...
                    if (!is_array($currentTag)) {
                        // Constants
                        $tsfe = $this->getTypoScriptFrontendController();
                        $tmpConstants = $tsfe->tmpl->setup['constants.'] ?? null;
                        if ($conf['constants'] && is_array($tmpConstants)) {
                            foreach ($tmpConstants as $key => $val) {
                                if (is_string($val)) {
                                    $data = str_replace('###' . $key . '###', $val, $data);
                                }
                            }
                        }
                        // Short
                        if (isset($conf['short.']) && is_array($conf['short.'])) {
                            $shortWords = $conf['short.'];
                            krsort($shortWords);
                            foreach ($shortWords as $key => $val) {
                                if (is_string($val)) {
                                    $data = str_replace($key, $val, $data);
                                }
                            }
                        }
                        // stdWrap
                        if (isset($conf['plainTextStdWrap.']) && is_array($conf['plainTextStdWrap.'])) {
                            $data = $this->stdWrap($data, $conf['plainTextStdWrap.']);
                        }
                        // userFunc
                        if ($conf['userFunc'] ?? false) {
                            $data = $this->callUserFunction($conf['userFunc'], $conf['userFunc.'], $data);
                        }
                        // Makelinks: (Before search-words as we need the links to be generated when searchwords go on...!)
                        if ($conf['makelinks'] ?? false) {
                            $data = $this->http_makelinks($data, $conf['makelinks.']['http.']);
                            $data = $this->mailto_makelinks($data, $conf['makelinks.']['mailto.'] ?? []);
                        }
                        // Search Words:
                        if ($tsfe->no_cache && $conf['sword'] && is_array($tsfe->sWordList) && $tsfe->sWordRegEx) {
                            $newstring = '';
                            do {
                                $pregSplitMode = 'i';
                                if (isset($tsfe->config['config']['sword_noMixedCase']) && !empty($tsfe->config['config']['sword_noMixedCase'])) {
                                    $pregSplitMode = '';
                                }
                                $pieces = preg_split('/' . $tsfe->sWordRegEx . '/' . $pregSplitMode, $data, 2);
                                $newstring .= $pieces[0];
                                $match_len = strlen($data) - (strlen($pieces[0]) + strlen($pieces[1]));
                                $inTag = false;
                                if (strstr($pieces[0], '<') || strstr($pieces[0], '>')) {
                                    // Returns TRUE, if a '<' is closer to the string-end than '>'.
                                    // This is the case if we're INSIDE a tag (that could have been
                                    // made by makelinks...) and we must secure, that the inside of a tag is
                                    // not marked up.
                                    $inTag = strrpos($pieces[0], '<') > strrpos($pieces[0], '>');
                                }
                                // The searchword:
                                $match = substr($data, strlen($pieces[0]), $match_len);
                                if (trim($match) && strlen($match) > 1 && !$inTag) {
                                    $match = $this->wrap($match, $conf['sword']);
                                }
                                // Concatenate the Search Word again.
                                $newstring .= $match;
                                $data = $pieces[1];
                            } while ($pieces[1]);
                            $data = $newstring;
                        }
                    }
                    $contentAccum[$contentAccumP] = isset($contentAccum[$contentAccumP])
                        ? $contentAccum[$contentAccumP] . $data
                        : $data;
                }
                $inside = true;
            } else {
                // tags
                $len = strcspn(substr($theValue, $pointer), '>') + 1;
                $data = substr($theValue, $pointer, $len);
                if (StringUtility::endsWith($data, '/>') && strpos($data, '<link ') !== 0) {
                    $tagContent = substr($data, 1, -2);
                } else {
                    $tagContent = substr($data, 1, -1);
                }
                $tag = explode(' ', trim($tagContent), 2);
                $tag[0] = strtolower($tag[0]);
                if ($tag[0][0] === '/') {
                    $tag[0] = substr($tag[0], 1);
                    $tag['out'] = 1;
                }
                if ($conf['tags.'][$tag[0]] ?? false) {
                    $treated = false;
                    $stripNL = false;
                    // in-tag
                    if (!$currentTag && (!isset($tag['out']) || !$tag['out'])) {
                        // $currentTag (array!) is the tag we are currently processing
                        $currentTag = $tag;
                        $contentAccumP++;
                        $treated = true;
                        // in-out-tag: img and other empty tags
                        if (preg_match('/^(area|base|br|col|hr|img|input|meta|param)$/i', $tag[0])) {
                            $tag['out'] = 1;
                        }
                    }
                    // out-tag
                    if ($currentTag[0] === $tag[0] && isset($tag['out']) && $tag['out']) {
                        $theName = $conf['tags.'][$tag[0]];
                        $theConf = $conf['tags.'][$tag[0] . '.'];
                        // This flag indicates, that NL- (13-10-chars) should be stripped first and last.
                        $stripNL = (bool)($theConf['stripNL'] ?? false);
                        // This flag indicates, that this TypoTag section should NOT be included in the nonTypoTag content.
                        $breakOut = (bool)($theConf['breakoutTypoTagContent'] ?? false);
                        $this->parameters = [];
                        if ($currentTag[1]) {
                            // decode HTML entities in attributes, since they're processed
                            $params = GeneralUtility::get_tag_attributes($currentTag[1], true);
                            if (is_array($params)) {
                                foreach ($params as $option => $val) {
                                    // contains non-encoded values
                                    $this->parameters[strtolower($option)] = $val;
                                }
                            }
                        }
                        $this->parameters['allParams'] = trim($currentTag[1]);
                        // Removes NL in the beginning and end of the tag-content AND at the end of the currentTagBuffer.
                        // $stripNL depends on the configuration of the current tag
                        if ($stripNL) {
                            $contentAccum[$contentAccumP - 1] = preg_replace('/' . CR . '?' . LF . '[ ]*$/', '', $contentAccum[$contentAccumP - 1]);
                            $contentAccum[$contentAccumP] = preg_replace('/^[ ]*' . CR . '?' . LF . '/', '', $contentAccum[$contentAccumP]);
                            $contentAccum[$contentAccumP] = preg_replace('/' . CR . '?' . LF . '[ ]*$/', '', $contentAccum[$contentAccumP]);
                        }
                        $this->data[$this->currentValKey] = $contentAccum[$contentAccumP];
                        $newInput = $this->cObjGetSingle($theName, $theConf, '/parseFunc/.tags.' . $tag[0]);
                        // fetch the content object
                        $contentAccum[$contentAccumP] = $newInput;
                        $contentAccumP++;
                        // If the TypoTag section
                        if (!$breakOut) {
                            if (!isset($contentAccum[$contentAccumP - 2])) {
                                $contentAccum[$contentAccumP - 2] = '';
                            }
                            $contentAccum[$contentAccumP - 2] .= ($contentAccum[$contentAccumP - 1] ?? '') . ($contentAccum[$contentAccumP] ?? '');
                            unset($contentAccum[$contentAccumP]);
                            unset($contentAccum[$contentAccumP - 1]);
                            $contentAccumP -= 2;
                        }
                        unset($currentTag);
                        $treated = true;
                    }
                    // other tags
                    if (!$treated) {
                        $contentAccum[$contentAccumP] .= $data;
                    }
                } else {
                    // If a tag was not a typo tag, then it is just added to the content
                    $stripNL = false;
                    if (GeneralUtility::inList($allowTags, $tag[0]) || $denyTags !== '*' && !GeneralUtility::inList($denyTags, $tag[0])) {
                        $contentAccum[$contentAccumP] = isset($contentAccum[$contentAccumP])
                            ? $contentAccum[$contentAccumP] . $data
                            : $data;
                    } else {
                        $contentAccum[$contentAccumP] = isset($contentAccum[$contentAccumP])
                            ? $contentAccum[$contentAccumP] . htmlspecialchars($data)
                            : htmlspecialchars($data);
                    }
                }
                $inside = false;
            }
            $pointer += $len;
        } while ($pointer < $totalLen);
        // Parsing nonTypoTag content (all even keys):
        reset($contentAccum);
        $contentAccumCount = count($contentAccum);
        for ($a = 0; $a < $contentAccumCount; $a++) {
            if ($a % 2 != 1) {
                // stdWrap
                if (isset($conf['nonTypoTagStdWrap.']) && is_array($conf['nonTypoTagStdWrap.'])) {
                    $contentAccum[$a] = $this->stdWrap($contentAccum[$a], $conf['nonTypoTagStdWrap.']);
                }
                // userFunc
                if (!empty($conf['nonTypoTagUserFunc'])) {
                    $contentAccum[$a] = $this->callUserFunction($conf['nonTypoTagUserFunc'], $conf['nonTypoTagUserFunc.'], $contentAccum[$a]);
                }
            }
        }
        return implode('', $contentAccum);
    }

    /**
     * Lets you split the content by LF and process each line independently. Used to format content made with the RTE.
     *
     * @param string $theValue The input value
     * @param array $conf TypoScript options
     * @return string The processed input value being returned; Splitted lines imploded by LF again.
     * @internal
     */
    public function encaps_lineSplit($theValue, $conf)
    {
        if ((string)$theValue === '') {
            return '';
        }
        $lParts = explode(LF, $theValue);

        // When the last element is an empty linebreak we need to remove it, otherwise we will have a duplicate empty line.
        $lastPartIndex = count($lParts) - 1;
        if ($lParts[$lastPartIndex] === '' && trim($lParts[$lastPartIndex - 1], CR) === '') {
            array_pop($lParts);
        }

        $encapTags = GeneralUtility::trimExplode(',', strtolower($conf['encapsTagList']), true);
        $nonWrappedTag = $conf['nonWrappedTag'];
        $defaultAlign = isset($conf['defaultAlign.'])
            ? trim($this->stdWrap($conf['defaultAlign'] ?? '', $conf['defaultAlign.']))
            : trim($conf['defaultAlign'] ?? '');

        $str_content = '';
        foreach ($lParts as $k => $l) {
            $sameBeginEnd = 0;
            $emptyTag = false;
            $l = trim($l);
            $attrib = [];
            $nonWrapped = false;
            $tagName = '';
            if (isset($l[0]) && $l[0] === '<' && substr($l, -1) === '>') {
                $fwParts = explode('>', substr($l, 1), 2);
                list($tagName) = explode(' ', $fwParts[0], 2);
                if (!$fwParts[1]) {
                    if (substr($tagName, -1) === '/') {
                        $tagName = substr($tagName, 0, -1);
                    }
                    if (substr($fwParts[0], -1) === '/') {
                        $sameBeginEnd = 1;
                        $emptyTag = true;
                        // decode HTML entities, they're encoded later again
                        $attrib = GeneralUtility::get_tag_attributes('<' . substr($fwParts[0], 0, -1) . '>', true);
                    }
                } else {
                    $backParts = GeneralUtility::revExplode('<', substr($fwParts[1], 0, -1), 2);
                    // decode HTML entities, they're encoded later again
                    $attrib = GeneralUtility::get_tag_attributes('<' . $fwParts[0] . '>', true);
                    $str_content = $backParts[0];
                    $sameBeginEnd = substr(strtolower($backParts[1]), 1, strlen($tagName)) === strtolower($tagName);
                }
            }
            if ($sameBeginEnd && in_array(strtolower($tagName), $encapTags)) {
                $uTagName = strtoupper($tagName);
                $uTagName = strtoupper($conf['remapTag.'][$uTagName] ?? $uTagName);
            } else {
                $uTagName = strtoupper($nonWrappedTag);
                // The line will be wrapped: $uTagName should not be an empty tag
                $emptyTag = false;
                $str_content = $lParts[$k];
                $nonWrapped = true;
                $attrib = [];
            }
            // Wrapping all inner-content:
            if (is_array($conf['innerStdWrap_all.'])) {
                $str_content = $this->stdWrap($str_content, $conf['innerStdWrap_all.']);
            }
            if ($uTagName) {
                // Setting common attributes
                if (isset($conf['addAttributes.'][$uTagName . '.']) && is_array($conf['addAttributes.'][$uTagName . '.'])) {
                    foreach ($conf['addAttributes.'][$uTagName . '.'] as $kk => $vv) {
                        if (!is_array($vv)) {
                            if ((string)$conf['addAttributes.'][$uTagName . '.'][$kk . '.']['setOnly'] === 'blank') {
                                if ((string)($attrib[$kk] ?? '') === '') {
                                    $attrib[$kk] = $vv;
                                }
                            } elseif ((string)$conf['addAttributes.'][$uTagName . '.'][$kk . '.']['setOnly'] === 'exists') {
                                if (!isset($attrib[$kk])) {
                                    $attrib[$kk] = $vv;
                                }
                            } else {
                                $attrib[$kk] = $vv;
                            }
                        }
                    }
                }
                // Wrapping all inner-content:
                if (isset($conf['encapsLinesStdWrap.'][$uTagName . '.']) && is_array($conf['encapsLinesStdWrap.'][$uTagName . '.'])) {
                    $str_content = $this->stdWrap($str_content, $conf['encapsLinesStdWrap.'][$uTagName . '.']);
                }
                // Default align
                if ((!isset($attrib['align']) || !$attrib['align']) && $defaultAlign) {
                    $attrib['align'] = $defaultAlign;
                }
                // implode (insecure) attributes, that's why `htmlspecialchars` is used here
                $params = GeneralUtility::implodeAttributes($attrib, true);
                if (!isset($conf['removeWrapping']) || !$conf['removeWrapping'] || ($emptyTag && $conf['removeWrapping.']['keepSingleTag'])) {
                    $selfClosingTagList = ['area', 'base', 'br', 'col', 'embed', 'hr', 'img', 'input', 'keygen', 'link', 'meta', 'param', 'source', 'track', 'wbr'];
                    if ($emptyTag && in_array(strtolower($uTagName), $selfClosingTagList, true)) {
                        $str_content = '<' . strtolower($uTagName) . (trim($params) ? ' ' . trim($params) : '') . ' />';
                    } else {
                        $str_content = '<' . strtolower($uTagName) . (trim($params) ? ' ' . trim($params) : '') . '>' . $str_content . '</' . strtolower($uTagName) . '>';
                    }
                }
            }
            if ($nonWrapped && isset($conf['wrapNonWrappedLines']) && $conf['wrapNonWrappedLines']) {
                $str_content = $this->wrap($str_content, $conf['wrapNonWrappedLines']);
            }
            $lParts[$k] = $str_content;
        }
        return implode(LF, $lParts);
    }

    /**
     * Finds URLS in text and makes it to a real link.
     * Will find all strings prefixed with "http://" and "https://" in the $data string and make them into a link,
     * linking to the URL we should have found.
     *
     * @param string $data The string in which to search for "http://
     * @param array $conf Configuration for makeLinks, see link
     * @return string The processed input string, being returned.
     * @see _parseFunc()
     */
    public function http_makelinks($data, $conf)
    {
        $aTagParams = $this->getATagParams($conf);
        $textstr = '';
        foreach (['http://', 'https://'] as $scheme) {
            $textpieces = explode($scheme, $data);
            $pieces = count($textpieces);
            $textstr = $textpieces[0];
            for ($i = 1; $i < $pieces; $i++) {
                $len = strcspn($textpieces[$i], chr(32) . "\t" . CRLF);
                if (trim(substr($textstr, -1)) === '' && $len) {
                    $lastChar = substr($textpieces[$i], $len - 1, 1);
                    if (!preg_match('/[A-Za-z0-9\\/#_-]/', $lastChar)) {
                        $len--;
                    }
                    // Included '\/' 3/12
                    $parts[0] = substr($textpieces[$i], 0, $len);
                    $parts[1] = substr($textpieces[$i], $len);
                    $keep = $conf['keep'];
                    $linkParts = parse_url($scheme . $parts[0]);
                    $linktxt = '';
                    if (strstr($keep, 'scheme')) {
                        $linktxt = $scheme;
                    }
                    $linktxt .= $linkParts['host'];
                    if (strstr($keep, 'path')) {
                        $linktxt .= $linkParts['path'];
                        // Added $linkParts['query'] 3/12
                        if (strstr($keep, 'query') && $linkParts['query']) {
                            $linktxt .= '?' . $linkParts['query'];
                        } elseif ($linkParts['path'] === '/') {
                            $linktxt = substr($linktxt, 0, -1);
                        }
                    }
                    if (isset($conf['extTarget'])) {
                        if (isset($conf['extTarget.'])) {
                            $target = $this->stdWrap($conf['extTarget'], $conf['extTarget.']);
                        } else {
                            $target = $conf['extTarget'];
                        }
                    } else {
                        $target = $this->getTypoScriptFrontendController()->extTarget;
                    }

                    // check for jump URLs or similar
                    $linkUrl = $this->processUrl(UrlProcessorInterface::CONTEXT_COMMON, $scheme . $parts[0], $conf);

                    $res = '<a href="' . htmlspecialchars($linkUrl) . '"'
                        . ($target !== '' ? ' target="' . htmlspecialchars($target) . '"' : '')
                        . $aTagParams . $this->extLinkATagParams('http://' . $parts[0], 'url') . '>';

                    $wrap = isset($conf['wrap.']) ? $this->stdWrap($conf['wrap'], $conf['wrap.']) : $conf['wrap'];
                    if ((string)$conf['ATagBeforeWrap'] !== '') {
                        $res = $res . $this->wrap($linktxt, $wrap) . '</a>';
                    } else {
                        $res = $this->wrap($res . $linktxt . '</a>', $wrap);
                    }
                    $textstr .= $res . $parts[1];
                } else {
                    $textstr .= $scheme . $textpieces[$i];
                }
            }
            $data = $textstr;
        }
        return $textstr;
    }

    /**
     * Will find all strings prefixed with "mailto:" in the $data string and make them into a link,
     * linking to the email address they point to.
     *
     * @param string $data The string in which to search for "mailto:
     * @param array $conf Configuration for makeLinks, see link
     * @return string The processed input string, being returned.
     * @see _parseFunc()
     */
    public function mailto_makelinks($data, $conf)
    {
        // http-split
        $aTagParams = $this->getATagParams($conf);
        $textpieces = explode('mailto:', $data);
        $pieces = count($textpieces);
        $textstr = $textpieces[0];
        $tsfe = $this->getTypoScriptFrontendController();
        for ($i = 1; $i < $pieces; $i++) {
            $len = strcspn($textpieces[$i], chr(32) . "\t" . CRLF);
            if (trim(substr($textstr, -1)) === '' && $len) {
                $lastChar = substr($textpieces[$i], $len - 1, 1);
                if (!preg_match('/[A-Za-z0-9]/', $lastChar)) {
                    $len--;
                }
                $parts[0] = substr($textpieces[$i], 0, $len);
                $parts[1] = substr($textpieces[$i], $len);
                $linktxt = preg_replace('/\\?.*/', '', $parts[0]);
                list($mailToUrl, $linktxt) = $this->getMailTo($parts[0], $linktxt);
                $mailToUrl = $tsfe->spamProtectEmailAddresses === 'ascii' ? $mailToUrl : htmlspecialchars($mailToUrl);
                $res = '<a href="' . $mailToUrl . '"' . $aTagParams . '>';
                $wrap = isset($conf['wrap.']) ? $this->stdWrap($conf['wrap'], $conf['wrap.']) : $conf['wrap'];
                if ((string)$conf['ATagBeforeWrap'] !== '') {
                    $res = $res . $this->wrap($linktxt, $wrap) . '</a>';
                } else {
                    $res = $this->wrap($res . $linktxt . '</a>', $wrap);
                }
                $textstr .= $res . $parts[1];
            } else {
                $textstr .= 'mailto:' . $textpieces[$i];
            }
        }
        return $textstr;
    }

    /**
     * Creates and returns a TypoScript "imgResource".
     * The value ($file) can either be a file reference (TypoScript resource) or the string "GIFBUILDER".
     * In the first case a current image is returned, possibly scaled down or otherwise processed.
     * In the latter case a GIFBUILDER image is returned; This means an image is made by TYPO3 from layers of elements as GIFBUILDER defines.
     * In the function IMG_RESOURCE() this function is called like $this->getImgResource($conf['file'], $conf['file.']);
     *
     * Structure of the returned info array:
     *  0 => width
     *  1 => height
     *  2 => file extension
     *  3 => file name
     *  origFile => original file name
     *  origFile_mtime => original file mtime
     *  -- only available if processed via FAL: --
     *  originalFile => original file object
     *  processedFile => processed file object
     *  fileCacheHash => checksum of processed file
     *
     * @param string|File|FileReference $file A "imgResource" TypoScript data type. Either a TypoScript file resource, a file or a file reference object or the string GIFBUILDER. See description above.
     * @param array $fileArray TypoScript properties for the imgResource type
     * @return array|null Returns info-array
     * @see IMG_RESOURCE(), cImage(), \TYPO3\CMS\Frontend\Imaging\GifBuilder
     */
    public function getImgResource($file, $fileArray)
    {
        if (empty($file) && empty($fileArray)) {
            return null;
        }
        if (!is_array($fileArray)) {
            $fileArray = (array)$fileArray;
        }
        $imageResource = null;
        if ($file === 'GIFBUILDER') {
            $gifCreator = GeneralUtility::makeInstance(GifBuilder::class);
            $theImage = '';
            if ($GLOBALS['TYPO3_CONF_VARS']['GFX']['gdlib']) {
                $gifCreator->start($fileArray, $this->data);
                $theImage = $gifCreator->gifBuild();
            }
            $imageResource = $gifCreator->getImageDimensions($theImage);
            $imageResource['origFile'] = $theImage;
        } else {
            if ($file instanceof File) {
                $fileObject = $file;
            } elseif ($file instanceof FileReference) {
                $fileObject = $file->getOriginalFile();
            } else {
                try {
                    if (isset($fileArray['import.']) && $fileArray['import.']) {
                        $importedFile = trim($this->stdWrap('', $fileArray['import.']));
                        if (!empty($importedFile)) {
                            $file = $importedFile;
                        }
                    }

                    if (MathUtility::canBeInterpretedAsInteger($file)) {
                        $treatIdAsReference = isset($fileArray['treatIdAsReference.']) ? $this->stdWrap($fileArray['treatIdAsReference'], $fileArray['treatIdAsReference.']) : $fileArray['treatIdAsReference'];
                        if (!empty($treatIdAsReference)) {
                            $file = $this->getResourceFactory()->getFileReferenceObject($file);
                            $fileObject = $file->getOriginalFile();
                        } else {
                            $fileObject = $this->getResourceFactory()->getFileObject($file);
                        }
                    } elseif (preg_match('/^(0|[1-9][0-9]*):/', $file)) { // combined identifier
                        $fileObject = $this->getResourceFactory()->retrieveFileOrFolderObject($file);
                    } else {
                        if (isset($importedFile) && !empty($importedFile) && !empty($fileArray['import'])) {
                            $file = $fileArray['import'] . $file;
                        }
                        $fileObject = $this->getResourceFactory()->retrieveFileOrFolderObject($file);
                    }
                } catch (Exception $exception) {
                    $this->logger->warning('The image "' . $file . '" could not be found and won\'t be included in frontend output', ['exception' => $exception]);
                    return null;
                }
            }
            if ($fileObject instanceof File) {
                $processingConfiguration = [];
                $processingConfiguration['width'] = isset($fileArray['width.']) ? $this->stdWrap($fileArray['width'], $fileArray['width.']) : $fileArray['width'];
                $processingConfiguration['height'] = isset($fileArray['height.']) ? $this->stdWrap($fileArray['height'], $fileArray['height.']) : $fileArray['height'];
                $processingConfiguration['fileExtension'] = isset($fileArray['ext.']) ? $this->stdWrap($fileArray['ext'], $fileArray['ext.']) : $fileArray['ext'];
                $processingConfiguration['maxWidth'] = isset($fileArray['maxW.']) ? (int)$this->stdWrap($fileArray['maxW'], $fileArray['maxW.']) : (int)$fileArray['maxW'];
                $processingConfiguration['maxHeight'] = isset($fileArray['maxH.']) ? (int)$this->stdWrap($fileArray['maxH'], $fileArray['maxH.']) : (int)$fileArray['maxH'];
                $processingConfiguration['minWidth'] = isset($fileArray['minW.']) ? (int)$this->stdWrap($fileArray['minW'], $fileArray['minW.']) : (int)$fileArray['minW'];
                $processingConfiguration['minHeight'] = isset($fileArray['minH.']) ? (int)$this->stdWrap($fileArray['minH'], $fileArray['minH.']) : (int)$fileArray['minH'];
                $processingConfiguration['noScale'] = isset($fileArray['noScale.']) ? $this->stdWrap($fileArray['noScale'], $fileArray['noScale.']) : $fileArray['noScale'];
                $processingConfiguration['additionalParameters'] = isset($fileArray['params.']) ? $this->stdWrap($fileArray['params'], $fileArray['params.']) : $fileArray['params'];
                $processingConfiguration['frame'] = isset($fileArray['frame.']) ? (int)$this->stdWrap($fileArray['frame'], $fileArray['frame.']) : (int)$fileArray['frame'];
                if ($file instanceof FileReference) {
                    $processingConfiguration['crop'] = $this->getCropAreaFromFileReference($file, $fileArray);
                } else {
                    $processingConfiguration['crop'] = $this->getCropAreaFromFromTypoScriptSettings($fileObject, $fileArray);
                }

                // Possibility to cancel/force profile extraction
                // see $GLOBALS['TYPO3_CONF_VARS']['GFX']['processor_stripColorProfileCommand']
                if (isset($fileArray['stripProfile'])) {
                    $processingConfiguration['stripProfile'] = $fileArray['stripProfile'];
                }
                // Check if we can handle this type of file for editing
                if (GeneralUtility::inList($GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'], $fileObject->getExtension())) {
                    $maskArray = $fileArray['m.'];
                    // Must render mask images and include in hash-calculating
                    // - otherwise we cannot be sure the filename is unique for the setup!
                    if (is_array($maskArray)) {
                        $mask = $this->getImgResource($maskArray['mask'], $maskArray['mask.']);
                        $bgImg = $this->getImgResource($maskArray['bgImg'], $maskArray['bgImg.']);
                        $bottomImg = $this->getImgResource($maskArray['bottomImg'], $maskArray['bottomImg.']);
                        $bottomImg_mask = $this->getImgResource($maskArray['bottomImg_mask'], $maskArray['bottomImg_mask.']);

                        $processingConfiguration['maskImages']['maskImage'] = $mask['processedFile'];
                        $processingConfiguration['maskImages']['backgroundImage'] = $bgImg['processedFile'];
                        $processingConfiguration['maskImages']['maskBottomImage'] = $bottomImg['processedFile'];
                        $processingConfiguration['maskImages']['maskBottomImageMask'] = $bottomImg_mask['processedFile'];
                    }
                    $processedFileObject = $fileObject->process(ProcessedFile::CONTEXT_IMAGECROPSCALEMASK, $processingConfiguration);
                    if ($processedFileObject->isProcessed()) {
                        $imageResource = [
                            0 => (int)$processedFileObject->getProperty('width'),
                            1 => (int)$processedFileObject->getProperty('height'),
                            2 => $processedFileObject->getExtension(),
                            3 => $processedFileObject->getPublicUrl(),
                            'origFile' => $fileObject->getPublicUrl(),
                            'origFile_mtime' => $fileObject->getModificationTime(),
                            // This is needed by \TYPO3\CMS\Frontend\Imaging\GifBuilder,
                            // in order for the setup-array to create a unique filename hash.
                            'originalFile' => $fileObject,
                            'processedFile' => $processedFileObject
                        ];
                    }
                }
            }
        }
        // If image was processed by GIFBUILDER:
        // ($imageResource indicates that it was processed the regular way)
        if (!isset($imageResource)) {
            try {
                $theImage = GeneralUtility::makeInstance(FilePathSanitizer::class)->sanitize((string)$file);
                $info = GeneralUtility::makeInstance(GifBuilder::class)->imageMagickConvert($theImage, 'WEB');
                $info['origFile'] = $theImage;
                // This is needed by \TYPO3\CMS\Frontend\Imaging\GifBuilder, ln 100ff in order for the setup-array to create a unique filename hash.
                $info['origFile_mtime'] = @filemtime($theImage);
                $imageResource = $info;
            } catch (\TYPO3\CMS\Core\Resource\Exception $e) {
                // do nothing in case the file path is invalid
            }
        }
        // Hook 'getImgResource': Post-processing of image resources
        if (isset($imageResource)) {
            /** @var ContentObjectGetImageResourceHookInterface $hookObject */
            foreach ($this->getGetImgResourceHookObjects() as $hookObject) {
                $imageResource = $hookObject->getImgResourcePostProcess($file, (array)$fileArray, $imageResource, $this);
            }
        }
        return $imageResource;
    }

    /**
     * Returns an ImageManipulation\Area object for the given cropVariant (or 'default')
     * or null if the crop settings or crop area is empty.
     *
     * The cropArea from file reference is used, if not set in TypoScript.
     *
     * Example TypoScript settings:
     * file.crop =
     * OR
     * file.crop = 50,50,100,100
     * OR
     * file.crop.data = file:current:crop
     *
     * @param FileReference $fileReference
     * @param array $fileArray TypoScript properties for the imgResource type
     * @return Area|null
     */
    protected function getCropAreaFromFileReference(FileReference $fileReference, array $fileArray)
    {
        // Use cropping area from file reference if nothing is configured in TypoScript.
        if (!isset($fileArray['crop']) && !isset($fileArray['crop.'])) {
            // Set crop variant from TypoScript settings. If not set, use default.
            $cropVariant = $fileArray['cropVariant'] ?? 'default';
            $fileCropArea = $this->createCropAreaFromJsonString((string)$fileReference->getProperty('crop'), $cropVariant);
            return $fileCropArea->isEmpty() ? null : $fileCropArea->makeAbsoluteBasedOnFile($fileReference);
        }

        return $this->getCropAreaFromFromTypoScriptSettings($fileReference, $fileArray);
    }

    /**
     * Returns an ImageManipulation\Area object for the given cropVariant (or 'default')
     * or null if the crop settings or crop area is empty.
     *
     * @param FileInterface $file
     * @param array $fileArray
     * @return Area|null
     */
    protected function getCropAreaFromFromTypoScriptSettings(FileInterface $file, array $fileArray)
    {
        /** @var Area $cropArea */
        $cropArea = null;
        // Resolve TypoScript configured cropping.
        $cropSettings = isset($fileArray['crop.'])
            ? $this->stdWrap($fileArray['crop'], $fileArray['crop.'])
            : ($fileArray['crop'] ?? null);

        if (is_string($cropSettings)) {
            // Set crop variant from TypoScript settings. If not set, use default.
            $cropVariant = $fileArray['cropVariant'] ?? 'default';
            // Get cropArea from CropVariantCollection, if cropSettings is a valid json.
            // CropVariantCollection::create does json_decode.
            $jsonCropArea = $this->createCropAreaFromJsonString($cropSettings, $cropVariant);
            $cropArea = $jsonCropArea->isEmpty() ? null : $jsonCropArea->makeAbsoluteBasedOnFile($file);

            // Cropping is configured in TypoScript in the following way: file.crop = 50,50,100,100
            if ($jsonCropArea->isEmpty() && preg_match('/^[0-9]+,[0-9]+,[0-9]+,[0-9]+$/', $cropSettings)) {
                $cropSettings = explode(',', $cropSettings);
                if (count($cropSettings) === 4) {
                    $stringCropArea = GeneralUtility::makeInstance(
                        Area::class,
                        ...$cropSettings
                    );
                    $cropArea = $stringCropArea->isEmpty() ? null : $stringCropArea;
                }
            }
        }

        return $cropArea;
    }

    /**
     * Takes a JSON string and creates CropVariantCollection and fetches the corresponding
     * CropArea for that.
     *
     * @param string $cropSettings
     * @param string $cropVariant
     * @return Area
     */
    protected function createCropAreaFromJsonString(string $cropSettings, string $cropVariant): Area
    {
        return CropVariantCollection::create($cropSettings)->getCropArea($cropVariant);
    }

    /***********************************************
     *
     * Data retrieval etc.
     *
     ***********************************************/
    /**
     * Returns the value for the field from $this->data. If "//" is found in the $field value that token will split the field values apart and the first field having a non-blank value will be returned.
     *
     * @param string $field The fieldname, eg. "title" or "navtitle // title" (in the latter case the value of $this->data[navtitle] is returned if not blank, otherwise $this->data[title] will be)
     * @return string|null
     */
    public function getFieldVal($field)
    {
        if (!strstr($field, '//')) {
            return $this->data[trim($field)] ?? null;
        }
        $sections = GeneralUtility::trimExplode('//', $field, true);
        foreach ($sections as $k) {
            if ((string)$this->data[$k] !== '') {
                return $this->data[$k];
            }
        }

        return '';
    }

    /**
     * Implements the TypoScript data type "getText". This takes a string with parameters and based on those a value from somewhere in the system is returned.
     *
     * @param string $string The parameter string, eg. "field : title" or "field : navtitle // field : title" (in the latter case and example of how the value is FIRST splitted by "//" is shown)
     * @param array|null $fieldArray Alternative field array; If you set this to an array this variable will be used to look up values for the "field" key. Otherwise the current page record in $GLOBALS['TSFE']->page is used.
     * @return string The value fetched
     * @see getFieldVal()
     */
    public function getData($string, $fieldArray = null)
    {
        $tsfe = $this->getTypoScriptFrontendController();
        if (!is_array($fieldArray)) {
            $fieldArray = $tsfe->page;
        }
        $retVal = '';
        $sections = explode('//', $string);
        foreach ($sections as $secKey => $secVal) {
            if ($retVal) {
                break;
            }
            $parts = explode(':', $secVal, 2);
            $type = strtolower(trim($parts[0]));
            $typesWithOutParameters = ['level', 'date', 'current', 'pagelayout'];
            $key = trim($parts[1] ?? '');
            if (($key != '') || in_array($type, $typesWithOutParameters)) {
                switch ($type) {
                    case 'gp':
                        // Merge GET and POST and get $key out of the merged array
                        $getPostArray = GeneralUtility::_GET();
                        ArrayUtility::mergeRecursiveWithOverrule($getPostArray, GeneralUtility::_POST());
                        $retVal = $this->getGlobal($key, $getPostArray);
                        break;
                    case 'tsfe':
                        $retVal = $this->getGlobal('TSFE|' . $key);
                        break;
                    case 'getenv':
                        $retVal = getenv($key);
                        break;
                    case 'getindpenv':
                        $retVal = $this->getEnvironmentVariable($key);
                        break;
                    case 'field':
                        $retVal = $this->getGlobal($key, $fieldArray);
                        break;
                    case 'file':
                        $retVal = $this->getFileDataKey($key);
                        break;
                    case 'parameters':
                        $retVal = $this->parameters[$key];
                        break;
                    case 'register':
                        $retVal = $tsfe->register[$key] ?? null;
                        break;
                    case 'global':
                        $retVal = $this->getGlobal($key);
                        break;
                    case 'level':
                        $retVal = count($tsfe->tmpl->rootLine) - 1;
                        break;
                    case 'leveltitle':
                        $keyParts = GeneralUtility::trimExplode(',', $key);
                        $numericKey = $this->getKey($keyParts[0], $tsfe->tmpl->rootLine);
                        $retVal = $this->rootLineValue($numericKey, 'title', strtolower($keyParts[1] ?? '') === 'slide');
                        break;
                    case 'levelmedia':
                        $keyParts = GeneralUtility::trimExplode(',', $key);
                        $numericKey = $this->getKey($keyParts[0], $tsfe->tmpl->rootLine);
                        $retVal = $this->rootLineValue($numericKey, 'media', strtolower($keyParts[1] ?? '') === 'slide');
                        break;
                    case 'leveluid':
                        $numericKey = $this->getKey($key, $tsfe->tmpl->rootLine);
                        $retVal = $this->rootLineValue($numericKey, 'uid');
                        break;
                    case 'levelfield':
                        $keyParts = GeneralUtility::trimExplode(',', $key);
                        $numericKey = $this->getKey($keyParts[0], $tsfe->tmpl->rootLine);
                        $retVal = $this->rootLineValue($numericKey, $keyParts[1], strtolower($keyParts[2] ?? '') === 'slide');
                        break;
                    case 'fullrootline':
                        $keyParts = GeneralUtility::trimExplode(',', $key);
                        $fullKey = (int)$keyParts[0] - count($tsfe->tmpl->rootLine) + count($tsfe->rootLine);
                        if ($fullKey >= 0) {
                            $retVal = $this->rootLineValue($fullKey, $keyParts[1], stristr($keyParts[2] ?? '', 'slide'), $tsfe->rootLine);
                        }
                        break;
                    case 'date':
                        if (!$key) {
                            $key = 'd/m Y';
                        }
                        $retVal = date($key, $GLOBALS['EXEC_TIME']);
                        break;
                    case 'page':
                        $retVal = $tsfe->page[$key];
                        break;
                    case 'pagelayout':
                        // Check if the current page has a value in the DB field "backend_layout"
                        // if empty, check the root line for "backend_layout_next_level"
                        // same as
                        //   field = backend_layout
                        //   ifEmpty.data = levelfield:-2, backend_layout_next_level, slide
                        //   ifEmpty.ifEmpty = default
                        $retVal = $tsfe->page['backend_layout'];

                        // If it is set to "none" - don't use any
                        if ($retVal === '-1') {
                            $retVal = 'none';
                        } elseif ($retVal === '' || $retVal === '0') {
                            // If it not set check the root-line for a layout on next level and use this
                            // Remove first element, which is the current page
                            // See also \TYPO3\CMS\Backend\View\BackendLayoutView::getSelectedCombinedIdentifier()
                            $rootLine = $tsfe->rootLine;
                            array_shift($rootLine);
                            foreach ($rootLine as $rootLinePage) {
                                $retVal = (string)$rootLinePage['backend_layout_next_level'];
                                // If layout for "next level" is set to "none" - don't use any and stop searching
                                if ($retVal === '-1') {
                                    $retVal = 'none';
                                    break;
                                }
                                if ($retVal !== '' && $retVal !== '0') {
                                    // Stop searching if a layout for "next level" is set
                                    break;
                                }
                            }
                        }
                        if ($retVal === '0' || $retVal === '') {
                            $retVal = 'default';
                        }
                        break;
                    case 'current':
                        $retVal = $this->data[$this->currentValKey] ?? null;
                        break;
                    case 'db':
                        $selectParts = GeneralUtility::trimExplode(':', $key);
                        $db_rec = $tsfe->sys_page->getRawRecord($selectParts[0], $selectParts[1]);
                        if (is_array($db_rec) && $selectParts[2]) {
                            $retVal = $db_rec[$selectParts[2]];
                        }
                        break;
                    case 'lll':
                        $retVal = $tsfe->sL('LLL:' . $key);
                        break;
                    case 'path':
                        try {
                            $retVal = GeneralUtility::makeInstance(FilePathSanitizer::class)->sanitize($key);
                        } catch (\TYPO3\CMS\Core\Resource\Exception $e) {
                            // do nothing in case the file path is invalid
                            $retVal = null;
                        }
                        break;
                    case 'cobj':
                        switch ($key) {
                            case 'parentRecordNumber':
                                $retVal = $this->parentRecordNumber;
                                break;
                        }
                        break;
                    case 'debug':
                        switch ($key) {
                            case 'rootLine':
                                $retVal = DebugUtility::viewArray($tsfe->tmpl->rootLine);
                                break;
                            case 'fullRootLine':
                                $retVal = DebugUtility::viewArray($tsfe->rootLine);
                                break;
                            case 'data':
                                $retVal = DebugUtility::viewArray($this->data);
                                break;
                            case 'register':
                                $retVal = DebugUtility::viewArray($tsfe->register);
                                break;
                            case 'page':
                                $retVal = DebugUtility::viewArray($tsfe->page);
                                break;
                        }
                        break;
                    case 'flexform':
                        $keyParts = GeneralUtility::trimExplode(':', $key, true);
                        if (count($keyParts) === 2 && isset($this->data[$keyParts[0]])) {
                            $flexFormContent = $this->data[$keyParts[0]];
                            if (!empty($flexFormContent)) {
                                $flexFormService = GeneralUtility::makeInstance(FlexFormService::class);
                                $flexFormKey = str_replace('.', '|', $keyParts[1]);
                                $settings = $flexFormService->convertFlexFormContentToArray($flexFormContent);
                                $retVal = $this->getGlobal($flexFormKey, $settings);
                            }
                        }
                        break;
                    case 'session':
                        $keyParts = GeneralUtility::trimExplode('|', $key, true);
                        $sessionKey = array_shift($keyParts);
                        $retVal = $this->getTypoScriptFrontendController()->fe_user->getSessionData($sessionKey);
                        foreach ($keyParts as $keyPart) {
                            if (is_object($retVal)) {
                                $retVal = $retVal->{$keyPart};
                            } elseif (is_array($retVal)) {
                                $retVal = $retVal[$keyPart];
                            } else {
                                $retVal = '';
                                break;
                            }
                        }
                        if (!is_scalar($retVal)) {
                            $retVal = '';
                        }
                        break;
                    case 'context':
                        $context = GeneralUtility::makeInstance(Context::class);
                        list($aspectName, $propertyName) = GeneralUtility::trimExplode(':', $key, true, 2);
                        $retVal = $context->getPropertyFromAspect($aspectName, $propertyName, '');
                        if (is_array($retVal)) {
                            $retVal = implode(',', $retVal);
                        }
                        if (!is_scalar($retVal)) {
                            $retVal = '';
                        }
                        break;
                    case 'site':
                        $request = $GLOBALS['TYPO3_REQUEST'] ?? null;
                        $site = $request ? $request->getAttribute('site') : null;
                        if ($site instanceof Site) {
                            if ($key === 'identifier') {
                                $retVal = $site->getIdentifier();
                            } elseif ($key === 'base') {
                                $retVal = $site->getBase();
                            } else {
                                try {
                                    $retVal = ArrayUtility::getValueByPath($site->getConfiguration(), $key, '.');
                                } catch (MissingArrayPathException $exception) {
                                    $this->logger->warning(sprintf('getData() with "%s" failed', $key), ['exception' => $exception]);
                                }
                            }
                        }
                        break;
                    case 'sitelanguage':
                        $request = $GLOBALS['TYPO3_REQUEST'] ?? null;
                        $siteLanguage = $request ? $request->getAttribute('language') : null;
                        if ($siteLanguage instanceof SiteLanguage) {
                            $config = $siteLanguage->toArray();
                            if (isset($config[$key])) {
                                $retVal = $config[$key];
                            }
                        }
                        break;
                }
            }

            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_content.php']['getData'] ?? [] as $className) {
                $hookObject = GeneralUtility::makeInstance($className);
                if (!$hookObject instanceof ContentObjectGetDataHookInterface) {
                    throw new \UnexpectedValueException('$hookObject must implement interface ' . ContentObjectGetDataHookInterface::class, 1195044480);
                }
                $retVal = $hookObject->getDataExtension($string, $fieldArray, $secVal, $retVal, $this);
            }
        }
        return $retVal;
    }

    /**
     * Gets file information. This is a helper function for the getData() method above, which resolves e.g.
     * page.10.data = file:current:title
     * or
     * page.10.data = file:17:title
     *
     * @param string $key A colon-separated key, e.g. 17:name or current:sha1, with the first part being a sys_file uid or the keyword "current" and the second part being the key of information to get from file (e.g. "title", "size", "description", etc.)
     * @return string|int The value as retrieved from the file object.
     */
    protected function getFileDataKey($key)
    {
        list($fileUidOrCurrentKeyword, $requestedFileInformationKey) = explode(':', $key, 3);
        try {
            if ($fileUidOrCurrentKeyword === 'current') {
                $fileObject = $this->getCurrentFile();
            } elseif (MathUtility::canBeInterpretedAsInteger($fileUidOrCurrentKeyword)) {
                /** @var ResourceFactory $fileFactory */
                $fileFactory = GeneralUtility::makeInstance(ResourceFactory::class);
                $fileObject = $fileFactory->getFileObject($fileUidOrCurrentKeyword);
            } else {
                $fileObject = null;
            }
        } catch (Exception $exception) {
            $this->logger->warning('The file "' . $fileUidOrCurrentKeyword . '" could not be found and won\'t be included in frontend output', ['exception' => $exception]);
            $fileObject = null;
        }

        if ($fileObject instanceof FileInterface) {
            // All properties of the \TYPO3\CMS\Core\Resource\FileInterface are available here:
            switch ($requestedFileInformationKey) {
                case 'name':
                    return $fileObject->getName();
                case 'uid':
                    if (method_exists($fileObject, 'getUid')) {
                        return $fileObject->getUid();
                    }
                    return 0;
                case 'originalUid':
                    if ($fileObject instanceof FileReference) {
                        return $fileObject->getOriginalFile()->getUid();
                    }
                    return null;
                case 'size':
                    return $fileObject->getSize();
                case 'sha1':
                    return $fileObject->getSha1();
                case 'extension':
                    return $fileObject->getExtension();
                case 'mimetype':
                    return $fileObject->getMimeType();
                case 'contents':
                    return $fileObject->getContents();
                case 'publicUrl':
                    return $fileObject->getPublicUrl();
                default:
                    // Generic alternative here
                    return $fileObject->getProperty($requestedFileInformationKey);
            }
        } else {
            // @todo fail silently as is common in tslib_content
            return 'Error: no file object';
        }
    }

    /**
     * Returns a value from the current rootline (site) from $GLOBALS['TSFE']->tmpl->rootLine;
     *
     * @param string $key Which level in the root line
     * @param string $field The field in the rootline record to return (a field from the pages table)
     * @param bool $slideBack If set, then we will traverse through the rootline from outer level towards the root level until the value found is TRUE
     * @param mixed $altRootLine If you supply an array for this it will be used as an alternative root line array
     * @return string The value from the field of the rootline.
     * @internal
     * @see getData()
     */
    public function rootLineValue($key, $field, $slideBack = false, $altRootLine = '')
    {
        $rootLine = is_array($altRootLine) ? $altRootLine : $this->getTypoScriptFrontendController()->tmpl->rootLine;
        if (!$slideBack) {
            return $rootLine[$key][$field];
        }
        for ($a = $key; $a >= 0; $a--) {
            $val = $rootLine[$a][$field];
            if ($val) {
                return $val;
            }
        }

        return '';
    }

    /**
     * Return global variable where the input string $var defines array keys separated by "|"
     * Example: $var = "HTTP_SERVER_VARS | something" will return the value $GLOBALS['HTTP_SERVER_VARS']['something'] value
     *
     * @param string $keyString Global var key, eg. "HTTP_GET_VAR" or "HTTP_GET_VARS|id" to get the GET parameter "id" back.
     * @param array $source Alternative array than $GLOBAL to get variables from.
     * @return mixed Whatever value. If none, then blank string.
     * @see getData()
     */
    public function getGlobal($keyString, $source = null)
    {
        $keys = explode('|', $keyString);
        $numberOfLevels = count($keys);
        $rootKey = trim($keys[0]);
        $value = isset($source) ? $source[$rootKey] : $GLOBALS[$rootKey];
        for ($i = 1; $i < $numberOfLevels && isset($value); $i++) {
            $currentKey = trim($keys[$i]);
            if (is_object($value)) {
                $value = $value->{$currentKey};
            } elseif (is_array($value)) {
                $value = $value[$currentKey];
            } else {
                $value = '';
                break;
            }
        }
        if (!is_scalar($value)) {
            $value = '';
        }
        return $value;
    }

    /**
     * Processing of key values pointing to entries in $arr; Here negative values are converted to positive keys pointer to an entry in the array but from behind (based on the negative value).
     * Example: entrylevel = -1 means that entryLevel ends up pointing at the outermost-level, -2 means the level before the outermost...
     *
     * @param int $key The integer to transform
     * @param array $arr array in which the key should be found.
     * @return int The processed integer key value.
     * @internal
     * @see getData()
     */
    public function getKey($key, $arr)
    {
        $key = (int)$key;
        if (is_array($arr)) {
            if ($key < 0) {
                $key = count($arr) + $key;
            }
            if ($key < 0) {
                $key = 0;
            }
        }
        return $key;
    }

    /***********************************************
     *
     * Link functions (typolink)
     *
     ***********************************************/

    /**
     * called from the typoLink() function
     *
     * does the magic to split the full "typolink" string like "15,13 _blank myclass &more=1"
     * into separate parts
     *
     * @param string $linkText The string (text) to link
     * @param string $mixedLinkParameter destination data like "15,13 _blank myclass &more=1" used to create the link
     * @param array $configuration TypoScript configuration
     * @return array|string
     * @see typoLink()
     *
     * @todo the functionality of the "file:" syntax + the hook should be marked as deprecated, an upgrade wizard should handle existing links
     */
    protected function resolveMixedLinkParameter($linkText, $mixedLinkParameter, &$configuration = [])
    {
        $linkParameter = null;

        // Link parameter value = first part
        $linkParameterParts = GeneralUtility::makeInstance(TypoLinkCodecService::class)->decode($mixedLinkParameter);

        // Check for link-handler keyword
        $linkHandlerExploded = explode(':', $linkParameterParts['url'], 2);
        $linkHandlerKeyword = $linkHandlerExploded[0] ?? null;
        $linkHandlerValue = $linkHandlerExploded[1] ?? null;
        if (!empty($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_content.php']['typolinkLinkHandler'][$linkHandlerKeyword])
            && (string)$linkHandlerValue !== ''
        ) {
            $linkHandlerObj = GeneralUtility::makeInstance($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_content.php']['typolinkLinkHandler'][$linkHandlerKeyword]);
            if (method_exists($linkHandlerObj, 'main')) {
                return $linkHandlerObj->main($linkText, $configuration, $linkHandlerKeyword, $linkHandlerValue, $mixedLinkParameter, $this);
            }
        }

        if (in_array(strtolower(preg_replace('#\s|[[:cntrl:]]#', '', $linkHandlerKeyword)), ['javascript', 'data'], true)) {
            // Disallow insecure scheme's like javascript: or data:
            return $linkText;
        }
        $linkParameter = $linkParameterParts['url'];

        // additional parameters that need to be set
        if ($linkParameterParts['additionalParams'] !== '') {
            $forceParams = $linkParameterParts['additionalParams'];
            // params value
            $configuration['additionalParams'] .= $forceParams[0] === '&' ? $forceParams : '&' . $forceParams;
        }

        return [
            'href'   => $linkParameter,
            'target' => $linkParameterParts['target'],
            'class'  => $linkParameterParts['class'],
            'title'  => $linkParameterParts['title']
        ];
    }

    /**
     * Implements the "typolink" property of stdWrap (and others)
     * Basically the input string, $linktext, is (typically) wrapped in a <a>-tag linking to some page, email address, file or URL based on a parameter defined by the configuration array $conf.
     * This function is best used from internal functions as is. There are some API functions defined after this function which is more suited for general usage in external applications.
     * Generally the concept "typolink" should be used in your own applications as an API for making links to pages with parameters and more. The reason for this is that you will then automatically make links compatible with all the centralized functions for URL simulation and manipulation of parameters into hashes and more.
     * For many more details on the parameters and how they are interpreted, please see the link to TSref below.
     *
     * the FAL API is handled with the namespace/prefix "file:..."
     *
     * @param string $linkText The string (text) to link
     * @param array $conf TypoScript configuration (see link below)
     * @return string A link-wrapped string.
     * @see stdWrap(), \TYPO3\CMS\Frontend\Plugin\AbstractPlugin::pi_linkTP()
     */
    public function typoLink($linkText, $conf)
    {
        $linkText = (string)$linkText;
        $tsfe = $this->getTypoScriptFrontendController();

        $linkParameter = trim(
            (isset($conf['parameter.']) ?? '')
            ? $this->stdWrap($conf['parameter'] ?? '', $conf['parameter.'])
            : ($conf['parameter'] ?? '')
        );
        $this->lastTypoLinkUrl = '';
        $this->lastTypoLinkTarget = '';

        $resolvedLinkParameters = $this->resolveMixedLinkParameter($linkText, $linkParameter, $conf);
        // check if the link handler hook has resolved the link completely already
        if (!is_array($resolvedLinkParameters)) {
            return $resolvedLinkParameters;
        }
        $linkParameter = $resolvedLinkParameters['href'];
        $target = $resolvedLinkParameters['target'];
        $title = $resolvedLinkParameters['title'];

        if (!$linkParameter) {
            return $this->resolveAnchorLink($linkText, $conf ?? []);
        }

        // Detecting kind of link and resolve all necessary parameters
        $linkService = GeneralUtility::makeInstance(LinkService::class);
        try {
            $linkDetails = $linkService->resolve($linkParameter);
        } catch (Exception\InvalidPathException $exception) {
            $this->logger->warning('The link could not be generated', ['exception' => $exception]);
            return $linkText;
        }

        $linkDetails['typoLinkParameter'] = $linkParameter;
        if (isset($linkDetails['type']) && isset($GLOBALS['TYPO3_CONF_VARS']['FE']['typolinkBuilder'][$linkDetails['type']])) {
            /** @var AbstractTypolinkBuilder $linkBuilder */
            $linkBuilder = GeneralUtility::makeInstance(
                $GLOBALS['TYPO3_CONF_VARS']['FE']['typolinkBuilder'][$linkDetails['type']],
                $this,
                $tsfe
            );
            try {
                list($this->lastTypoLinkUrl, $linkText, $target) = $linkBuilder->build($linkDetails, $linkText, $target, $conf);
                $this->lastTypoLinkTarget = htmlspecialchars($target);
                $this->lastTypoLinkLD['target'] = htmlspecialchars($target);
                $this->lastTypoLinkLD['totalUrl'] = $this->lastTypoLinkUrl;
            } catch (UnableToLinkException $e) {
                $this->logger->debug(sprintf('Unable to link "%s": %s', $e->getLinkText(), $e->getMessage()), ['exception' => $e]);

                // Only return the link text directly
                return $e->getLinkText();
            }
        } elseif (isset($linkDetails['url'])) {
            $this->lastTypoLinkUrl = $linkDetails['url'];
            $this->lastTypoLinkTarget = htmlspecialchars($target);
            $this->lastTypoLinkLD['target'] = htmlspecialchars($target);
            $this->lastTypoLinkLD['totalUrl'] = $this->lastTypoLinkUrl;
        } else {
            return $linkText;
        }

        // We need to backup the URL because ATagParams might call typolink again and change the last URL.
        $url = $this->lastTypoLinkUrl;
        $finalTagParts = [
            'aTagParams' => $this->getATagParams($conf) . $this->extLinkATagParams($this->lastTypoLinkUrl, $linkDetails['type']),
            'url'        => $url,
            'TYPE'       => $linkDetails['type']
        ];

        // Ensure "href" is not in the list of aTagParams to avoid double tags, usually happens within buggy parseFunc settings
        if (!empty($finalTagParts['aTagParams'])) {
            $aTagParams = GeneralUtility::get_tag_attributes($finalTagParts['aTagParams'], true);
            if (isset($aTagParams['href'])) {
                unset($aTagParams['href']);
                $finalTagParts['aTagParams'] = GeneralUtility::implodeAttributes($aTagParams, true);
            }
        }

        // Building the final <a href=".."> tag
        $tagAttributes = [];

        // Title attribute
        if (empty($title)) {
            $title = $conf['title'] ?? '';
            if (isset($conf['title.']) && is_array($conf['title.'])) {
                $title = $this->stdWrap($title, $conf['title.']);
            }
        }

        // Check, if the target is coded as a JS open window link:
        $JSwindowParts = [];
        $JSwindowParams = '';
        if ($target && preg_match('/^([0-9]+)x([0-9]+)(:(.*)|.*)$/', $target, $JSwindowParts)) {
            // Take all pre-configured and inserted parameters and compile parameter list, including width+height:
            $JSwindow_tempParamsArr = GeneralUtility::trimExplode(',', strtolower($conf['JSwindow_params'] . ',' . $JSwindowParts[4]), true);
            $JSwindow_paramsArr = [];
            foreach ($JSwindow_tempParamsArr as $JSv) {
                list($JSp, $JSv) = explode('=', $JSv, 2);
                $JSwindow_paramsArr[$JSp] = $JSp . '=' . $JSv;
            }
            // Add width/height:
            $JSwindow_paramsArr['width'] = 'width=' . $JSwindowParts[1];
            $JSwindow_paramsArr['height'] = 'height=' . $JSwindowParts[2];
            // Imploding into string:
            $JSwindowParams = implode(',', $JSwindow_paramsArr);
        }

        if (!$JSwindowParams && $linkDetails['type'] === LinkService::TYPE_EMAIL && $tsfe->spamProtectEmailAddresses === 'ascii') {
            $tagAttributes['href'] = $finalTagParts['url'];
        } else {
            $tagAttributes['href'] = htmlspecialchars($finalTagParts['url']);
        }
        if (!empty($title)) {
            $tagAttributes['title'] = htmlspecialchars($title);
        }

        // Target attribute
        if (!empty($target)) {
            $tagAttributes['target'] = htmlspecialchars($target);
        } elseif ($JSwindowParams && !in_array($tsfe->xhtmlDoctype, ['xhtml_strict', 'xhtml_11'], true)) {
            // Create TARGET-attribute only if the right doctype is used
            $tagAttributes['target'] = 'FEopenLink';
        }

        if ($JSwindowParams) {
            $onClick = 'openPic(' . GeneralUtility::quoteJSvalue($tsfe->baseUrlWrap($finalTagParts['url'])) . ',\'FEopenLink\',' . GeneralUtility::quoteJSvalue($JSwindowParams) . ');return false;';
            $tagAttributes['onclick'] = htmlspecialchars($onClick);
            $this->getTypoScriptFrontendController()->setJS('openPic');
        }

        if (!empty($resolvedLinkParameters['class'])) {
            $tagAttributes['class'] = htmlspecialchars($resolvedLinkParameters['class']);
        }

        // Prevent trouble with double and missing spaces between attributes and merge params before implode
        // (skip decoding HTML entities, since `$tagAttributes` are expected to be encoded already)
        $finalTagAttributes = array_merge($tagAttributes, GeneralUtility::get_tag_attributes($finalTagParts['aTagParams']));
        $finalAnchorTag = '<a ' . GeneralUtility::implodeAttributes($finalTagAttributes) . '>';

        // kept for backwards-compatibility in hooks
        $finalTagParts['targetParams'] = !empty($tagAttributes['target']) ? ' target="' . $tagAttributes['target'] . '"' : '';
        $this->lastTypoLinkTarget = $target;

        // Call user function:
        if ($conf['userFunc'] ?? false) {
            $finalTagParts['TAG'] = $finalAnchorTag;
            $finalAnchorTag = $this->callUserFunction($conf['userFunc'], $conf['userFunc.'], $finalTagParts);
        }

        // Hook: Call post processing function for link rendering:
        $_params = [
            'conf' => &$conf,
            'linktxt' => &$linkText,
            'finalTag' => &$finalAnchorTag,
            'finalTagParts' => &$finalTagParts,
            'linkDetails' => &$linkDetails,
            'tagAttributes' => &$finalTagAttributes
        ];
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_content.php']['typoLink_PostProc'] ?? [] as $_funcRef) {
            GeneralUtility::callUserFunction($_funcRef, $_params, $this);
        }

        // If flag "returnLastTypoLinkUrl" set, then just return the latest URL made:
        if ($conf['returnLast'] ?? false) {
            switch ($conf['returnLast']) {
                case 'url':
                    return $this->lastTypoLinkUrl;
                case 'target':
                    return $this->lastTypoLinkTarget;
            }
        }

        $wrap = isset($conf['wrap.'])
            ? $this->stdWrap($conf['wrap'] ?? '', $conf['wrap.'])
            : $conf['wrap'] ?? '';

        if ($conf['ATagBeforeWrap'] ?? false) {
            return $finalAnchorTag . $this->wrap($linkText, $wrap) . '</a>';
        }
        return $this->wrap($finalAnchorTag . $linkText . '</a>', $wrap);
    }

    /**
     * Based on the input "TypoLink" TypoScript configuration this will return the generated URL
     *
     * @param array $conf TypoScript properties for "typolink
     * @return string The URL of the link-tag that typolink() would by itself return
     * @see typoLink()
     */
    public function typoLink_URL($conf)
    {
        $this->typoLink('|', $conf);
        return $this->lastTypoLinkUrl;
    }

    /**
     * Returns a linked string made from typoLink parameters.
     *
     * This function takes $label as a string, wraps it in a link-tag based on the $params string, which should contain data like that you would normally pass to the popular <LINK>-tag in the TSFE.
     * Optionally you can supply $urlParameters which is an array with key/value pairs that are rawurlencoded and appended to the resulting url.
     *
     * @param string $label Text string being wrapped by the link.
     * @param string $params Link parameter; eg. "123" for page id, "kasperYYYY@typo3.com" for email address, "http://...." for URL, "fileadmin/example.txt" for file.
     * @param array|string $urlParameters As an array key/value pairs represent URL parameters to set. Values NOT URL-encoded yet, keys should be URL-encoded if needed. As a string the parameter is expected to be URL-encoded already.
     * @param string $target Specific target set, if any. (Default is using the current)
     * @return string The wrapped $label-text string
     * @see getTypoLink_URL()
     */
    public function getTypoLink($label, $params, $urlParameters = [], $target = '')
    {
        $conf = [];
        $conf['parameter'] = $params;
        if ($target) {
            $conf['target'] = $target;
            $conf['extTarget'] = $target;
            $conf['fileTarget'] = $target;
        }
        if (is_array($urlParameters)) {
            if (!empty($urlParameters)) {
                $conf['additionalParams'] .= HttpUtility::buildQueryString($urlParameters, '&');
            }
        } else {
            $conf['additionalParams'] .= $urlParameters;
        }
        $out = $this->typoLink($label, $conf);
        return $out;
    }

    /**
     * Returns the canonical URL to the current "location", which include the current page ID and type
     * and optionally the query string
     *
     * @param bool $addQueryString Whether additional GET arguments in the query string should be included or not
     * @return string
     */
    public function getUrlToCurrentLocation($addQueryString = true)
    {
        $conf = [];
        $conf['parameter'] = $this->getTypoScriptFrontendController()->id . ',' . $this->getTypoScriptFrontendController()->type;
        if ($addQueryString) {
            $conf['addQueryString'] = '1';
            $linkVars = implode(',', array_keys(GeneralUtility::explodeUrl2Array($this->getTypoScriptFrontendController()->linkVars)));
            $conf['addQueryString.'] = [
                'method' => 'GET',
                'exclude' => 'id,type,cHash' . ($linkVars ? ',' . $linkVars : '')
            ];
            $conf['useCacheHash'] = GeneralUtility::_GET('cHash') ? '1' : '0';
        }

        return $this->typoLink_URL($conf);
    }

    /**
     * Returns the URL of a "typolink" create from the input parameter string, url-parameters and target
     *
     * @param string $params Link parameter; eg. "123" for page id, "kasperYYYY@typo3.com" for email address, "http://...." for URL, "fileadmin/example.txt" for file.
     * @param array|string $urlParameters As an array key/value pairs represent URL parameters to set. Values NOT URL-encoded yet, keys should be URL-encoded if needed. As a string the parameter is expected to be URL-encoded already.
     * @param string $target Specific target set, if any. (Default is using the current)
     * @return string The URL
     * @see getTypoLink()
     */
    public function getTypoLink_URL($params, $urlParameters = [], $target = '')
    {
        $this->getTypoLink('', $params, $urlParameters, $target);
        return $this->lastTypoLinkUrl;
    }

    /**
     * Generates a typolink and returns the two link tags - start and stop - in an array
     *
     * @param array $conf "typolink" TypoScript properties
     * @return array An array with two values in key 0+1, each value being the start and close <a>-tag of the typolink properties being inputted in $conf
     * @see typolink()
     * @deprecated since TYPO3 v9.5, will be removed in TYPO3 v10.0. Use typoLink() instead.
     */
    public function typolinkWrap($conf)
    {
        trigger_error('ContentObjectRenderer->typolinkWrap() will be removed in TYPO3 v10.0. Use $cObj->typoLink() instead.', E_USER_DEPRECATED);
        $k = md5(microtime());
        return explode($k, $this->typoLink($k, $conf));
    }

    /**
     * Returns the current page URL
     *
     * @param array|string $urlParameters As an array key/value pairs represent URL parameters to set. Values NOT URL-encoded yet, keys should be URL-encoded if needed. As a string the parameter is expected to be URL-encoded already.
     * @param int $id An alternative ID to the current id ($GLOBALS['TSFE']->id)
     * @return string The URL
     * @see getTypoLink_URL()
     * @deprecated since TYPO3 v9.5, will be removed in TYPO3 v10.0. Use getTypoLink_URL() instead.
     */
    public function currentPageUrl($urlParameters = [], $id = 0)
    {
        trigger_error('ContentObjectRenderer->currentPageUrl() will be removed in TYPO3 v10.0. Use $cObj->getTypoLink_URL() instead.', E_USER_DEPRECATED);
        $tsfe = $this->getTypoScriptFrontendController();
        return $this->getTypoLink_URL($id ?: $tsfe->id, $urlParameters, $tsfe->sPre);
    }

    /**
     * Loops over all configured URL modifier hooks (if available) and returns the generated URL or NULL if no URL was generated.
     *
     * @param string $context The context in which the method is called (e.g. typoLink).
     * @param string $url The URL that should be processed.
     * @param array $typolinkConfiguration The current link configuration array.
     * @return string|null Returns NULL if URL was not processed or the processed URL as a string.
     * @throws \RuntimeException if a hook was registered but did not fulfill the correct parameters.
     */
    protected function processUrl($context, $url, $typolinkConfiguration = [])
    {
        $urlProcessors = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['urlProcessing']['urlProcessors'] ?? [];
        if (empty($urlProcessors)) {
            return $url;
        }

        foreach ($urlProcessors as $identifier => $configuration) {
            if (empty($configuration) || !is_array($configuration)) {
                throw new \RuntimeException('Missing configuration for URI processor "' . $identifier . '".', 1442050529);
            }
            if (!is_string($configuration['processor']) || empty($configuration['processor']) || !class_exists($configuration['processor']) || !is_subclass_of($configuration['processor'], UrlProcessorInterface::class)) {
                throw new \RuntimeException('The URI processor "' . $identifier . '" defines an invalid provider. Ensure the class exists and implements the "' . UrlProcessorInterface::class . '".', 1442050579);
            }
        }

        $orderedProcessors = GeneralUtility::makeInstance(DependencyOrderingService::class)->orderByDependencies($urlProcessors);
        $keepProcessing = true;

        foreach ($orderedProcessors as $configuration) {
            /** @var UrlProcessorInterface $urlProcessor */
            $urlProcessor = GeneralUtility::makeInstance($configuration['processor']);
            $url = $urlProcessor->process($context, $url, $typolinkConfiguration, $this, $keepProcessing);
            if (!$keepProcessing) {
                break;
            }
        }

        return $url;
    }

    /**
     * Creates a href attibute for given $mailAddress.
     * The function uses spamProtectEmailAddresses for encoding the mailto statement.
     * If spamProtectEmailAddresses is disabled, it'll just return a string like "mailto:user@example.tld".
     *
     * @param string $mailAddress Email address
     * @param string $linktxt Link text, default will be the email address.
     * @return array A numerical array with two elements: 1) $mailToUrl, string ready to be inserted into the href attribute of the <a> tag, b) $linktxt: The string between starting and ending <a> tag.
     */
    public function getMailTo($mailAddress, $linktxt)
    {
        $mailAddress = (string)$mailAddress;
        if ((string)$linktxt === '') {
            $linktxt = htmlspecialchars($mailAddress);
        }

        $originalMailToUrl = 'mailto:' . $mailAddress;
        $mailToUrl = $this->processUrl(UrlProcessorInterface::CONTEXT_MAIL, $originalMailToUrl);

        // no processing happened, therefore, the default processing kicks in
        if ($mailToUrl === $originalMailToUrl) {
            $tsfe = $this->getTypoScriptFrontendController();
            if ($tsfe->spamProtectEmailAddresses) {
                $mailToUrl = $this->encryptEmail($mailToUrl, $tsfe->spamProtectEmailAddresses);
                if ($tsfe->spamProtectEmailAddresses !== 'ascii') {
                    $encodedForJsAndHref = rawurlencode(GeneralUtility::quoteJSvalue($mailToUrl));
                    $mailToUrl = 'javascript:linkTo_UnCryptMailto(' . $encodedForJsAndHref . ');';
                }
                $atLabel = trim($tsfe->config['config']['spamProtectEmailAddresses_atSubst']) ?: '(at)';
                $spamProtectedMailAddress = str_replace('@', $atLabel, htmlspecialchars($mailAddress));
                if ($tsfe->config['config']['spamProtectEmailAddresses_lastDotSubst']) {
                    $lastDotLabel = trim($tsfe->config['config']['spamProtectEmailAddresses_lastDotSubst']);
                    $lastDotLabel = $lastDotLabel ? $lastDotLabel : '(dot)';
                    $spamProtectedMailAddress = preg_replace('/\\.([^\\.]+)$/', $lastDotLabel . '$1', $spamProtectedMailAddress);
                }
                $linktxt = str_ireplace($mailAddress, $spamProtectedMailAddress, $linktxt);
            }
        }

        return [$mailToUrl, $linktxt];
    }

    /**
     * Encryption of email addresses for <A>-tags See the spam protection setup in TS 'config.'
     *
     * @param string $string Input string to en/decode: "mailto:some@example.com
     * @param mixed  $type - either "ascii" or a number between -10 and 10, taken from config.spamProtectEmailAddresses
     * @return string encoded version of $string
     */
    protected function encryptEmail(string $string, $type): string
    {
        $out = '';
        // obfuscates using the decimal HTML entity references for each character
        if ($type === 'ascii') {
            foreach (preg_split('//u', $string, -1, PREG_SPLIT_NO_EMPTY) as $char) {
                $out .= '&#' . mb_ord($char) . ';';
            }
        } else {
            // like str_rot13() but with a variable offset and a wider character range
            $len = strlen($string);
            $offset = (int)$type;
            for ($i = 0; $i < $len; $i++) {
                $charValue = ord($string[$i]);
                // 0-9 . , - + / :
                if ($charValue >= 43 && $charValue <= 58) {
                    $out .= $this->encryptCharcode($charValue, 43, 58, $offset);
                } elseif ($charValue >= 64 && $charValue <= 90) {
                    // A-Z @
                    $out .= $this->encryptCharcode($charValue, 64, 90, $offset);
                } elseif ($charValue >= 97 && $charValue <= 122) {
                    // a-z
                    $out .= $this->encryptCharcode($charValue, 97, 122, $offset);
                } else {
                    $out .= $string[$i];
                }
            }
        }
        return $out;
    }

    /**
     * Decryption of email addresses for <A>-tags See the spam protection setup in TS 'config.'
     *
     * @param string $string Input string to en/decode: "mailto:some@example.com
     * @param mixed  $type - either "ascii" or a number between -10 and 10 taken from config.spamProtectEmailAddresses
     * @return string decoded version of $string
     */
    protected function decryptEmail(string $string, $type): string
    {
        $out = '';
        // obfuscates using the decimal HTML entity references for each character
        if ($type === 'ascii') {
            foreach (preg_split('//u', $string, -1, PREG_SPLIT_NO_EMPTY) as $char) {
                $out .= '&#' . mb_ord($char) . ';';
            }
        } else {
            // like str_rot13() but with a variable offset and a wider character range
            $len = strlen($string);
            $offset = (int)$type * -1;
            for ($i = 0; $i < $len; $i++) {
                $charValue = ord($string[$i]);
                // 0-9 . , - + / :
                if ($charValue >= 43 && $charValue <= 58) {
                    $out .= $this->encryptCharcode($charValue, 43, 58, $offset);
                } elseif ($charValue >= 64 && $charValue <= 90) {
                    // A-Z @
                    $out .= $this->encryptCharcode($charValue, 64, 90, $offset);
                } elseif ($charValue >= 97 && $charValue <= 122) {
                    // a-z
                    $out .= $this->encryptCharcode($charValue, 97, 122, $offset);
                } else {
                    $out .= $string[$i];
                }
            }
        }
        return $out;
    }

    /**
     * Encryption (or decryption) of a single character.
     * Within the given range the character is shifted with the supplied offset.
     *
     * @param int $n Ordinal of input character
     * @param int $start Start of range
     * @param int $end End of range
     * @param int $offset Offset
     * @return string encoded/decoded version of character
     */
    protected function encryptCharcode($n, $start, $end, $offset)
    {
        $n = $n + $offset;
        if ($offset > 0 && $n > $end) {
            $n = $start + ($n - $end - 1);
        } elseif ($offset < 0 && $n < $start) {
            $n = $end - ($start - $n - 1);
        }
        return chr($n);
    }

    /**
     * Gets the query arguments and assembles them for URLs.
     * Arguments may be removed or set, depending on configuration.
     *
     * @param array $conf Configuration
     * @param array $overruleQueryArguments Multidimensional key/value pairs that overrule incoming query arguments
     * @param bool $forceOverruleArguments If set, key/value pairs not in the query but the overrule array will be set
     * @return string The URL query part (starting with a &)
     */
    public function getQueryArguments($conf, $overruleQueryArguments = [], $forceOverruleArguments = false)
    {
        $method = (string)($conf['method'] ?? '');
        switch ($method) {
            case 'GET':
                $currentQueryArray = GeneralUtility::_GET();
                break;
            case 'POST':
                $currentQueryArray = GeneralUtility::_POST();
                break;
            case 'GET,POST':
                $currentQueryArray = GeneralUtility::_GET();
                ArrayUtility::mergeRecursiveWithOverrule($currentQueryArray, GeneralUtility::_POST());
                break;
            case 'POST,GET':
                $currentQueryArray = GeneralUtility::_POST();
                ArrayUtility::mergeRecursiveWithOverrule($currentQueryArray, GeneralUtility::_GET());
                break;
            default:
                $currentQueryArray = [];
                parse_str($this->getEnvironmentVariable('QUERY_STRING'), $currentQueryArray);
        }
        if ($conf['exclude'] ?? false) {
            $excludeString = str_replace(',', '&', $conf['exclude']);
            $excludedQueryParts = [];
            parse_str($excludeString, $excludedQueryParts);
            // never repeat id
            $exclude['id'] = 0;
            $newQueryArray = ArrayUtility::arrayDiffAssocRecursive($currentQueryArray, $excludedQueryParts);
        } else {
            $newQueryArray = $currentQueryArray;
        }
        ArrayUtility::mergeRecursiveWithOverrule($newQueryArray, $overruleQueryArguments, $forceOverruleArguments);
        return HttpUtility::buildQueryString($newQueryArray, '&');
    }

    /***********************************************
     *
     * Miscellaneous functions, stand alone
     *
     ***********************************************/
    /**
     * Wrapping a string.
     * Implements the TypoScript "wrap" property.
     * Example: $content = "HELLO WORLD" and $wrap = "<strong> | </strong>", result: "<strong>HELLO WORLD</strong>"
     *
     * @param string $content The content to wrap
     * @param string $wrap The wrap value, eg. "<strong> | </strong>
     * @param string $char The char used to split the wrapping value, default is "|
     * @return string Wrapped input string
     * @see noTrimWrap()
     */
    public function wrap($content, $wrap, $char = '|')
    {
        if ($wrap) {
            $wrapArr = explode($char, $wrap);
            $content = trim($wrapArr[0] ?? '') . $content . trim($wrapArr[1] ?? '');
        }
        return $content;
    }

    /**
     * Wrapping a string, preserving whitespace in wrap value.
     * Notice that the wrap value uses part 1/2 to wrap (and not 0/1 which wrap() does)
     *
     * @param string $content The content to wrap, eg. "HELLO WORLD
     * @param string $wrap The wrap value, eg. " | <strong> | </strong>
     * @param string $char The char used to split the wrapping value, default is "|"
     * @return string Wrapped input string, eg. " <strong> HELLO WORD </strong>
     * @see wrap()
     */
    public function noTrimWrap($content, $wrap, $char = '|')
    {
        if ($wrap) {
            // expects to be wrapped with (at least) 3 characters (before, middle, after)
            // anything else is not taken into account
            $wrapArr = explode($char, $wrap, 4);
            $content = $wrapArr[1] . $content . $wrapArr[2];
        }
        return $content;
    }

    /**
     * Calling a user function/class-method
     * Notice: For classes the instantiated object will have the internal variable, $cObj, set to be a *reference* to $this (the parent/calling object).
     *
     * @param string $funcName The functionname, eg "user_myfunction" or "user_myclass->main". Notice that there are rules for the names of functions/classes you can instantiate. If a function cannot be called for some reason it will be seen in the TypoScript log in the AdminPanel.
     * @param array $conf The TypoScript configuration to pass the function
     * @param string $content The content string to pass the function
     * @return string The return content from the function call. Should probably be a string.
     * @see USER(), stdWrap(), typoLink(), _parseFunc()
     */
    public function callUserFunction($funcName, $conf, $content)
    {
        // Split parts
        $parts = explode('->', $funcName);
        if (count($parts) === 2) {
            // Check whether PHP class is available
            if (class_exists($parts[0])) {
                $classObj = GeneralUtility::makeInstance($parts[0]);
                if (is_object($classObj) && method_exists($classObj, $parts[1])) {
                    $classObj->cObj = $this;
                    $content = call_user_func_array([
                        $classObj,
                        $parts[1]
                    ], [
                        $content,
                        $conf
                    ]);
                } else {
                    $this->getTimeTracker()->setTSlogMessage('Method "' . $parts[1] . '" did not exist in class "' . $parts[0] . '"', 3);
                }
            } else {
                $this->getTimeTracker()->setTSlogMessage('Class "' . $parts[0] . '" did not exist', 3);
            }
        } elseif (function_exists($funcName)) {
            $content = call_user_func($funcName, $content, $conf);
        } else {
            $this->getTimeTracker()->setTSlogMessage('Function "' . $funcName . '" did not exist', 3);
        }
        return $content;
    }

    /**
     * Cleans up a string of keywords. Keywords at splitted by "," (comma)  ";" (semi colon) and linebreak
     *
     * @param string $content String of keywords
     * @return string Cleaned up string, keywords will be separated by a comma only.
     */
    public function keywords($content)
    {
        $listArr = preg_split('/[,;' . LF . ']/', $content);
        foreach ($listArr as $k => $v) {
            $listArr[$k] = trim($v);
        }
        return implode(',', $listArr);
    }

    /**
     * Changing character case of a string, converting typically used western charset characters as well.
     *
     * @param string $theValue The string to change case for.
     * @param string $case The direction; either "upper" or "lower
     * @return string
     * @see HTMLcaseshift()
     */
    public function caseshift($theValue, $case)
    {
        switch (strtolower($case)) {
            case 'upper':
                $theValue = mb_strtoupper($theValue, 'utf-8');
                break;
            case 'lower':
                $theValue = mb_strtolower($theValue, 'utf-8');
                break;
            case 'capitalize':
                $theValue = mb_convert_case($theValue, MB_CASE_TITLE, 'utf-8');
                break;
            case 'ucfirst':
                $firstChar = mb_substr($theValue, 0, 1, 'utf-8');
                $firstChar = mb_strtoupper($firstChar, 'utf-8');
                $remainder = mb_substr($theValue, 1, null, 'utf-8');
                $theValue = $firstChar . $remainder;
                break;
            case 'lcfirst':
                $firstChar = mb_substr($theValue, 0, 1, 'utf-8');
                $firstChar = mb_strtolower($firstChar, 'utf-8');
                $remainder = mb_substr($theValue, 1, null, 'utf-8');
                $theValue = $firstChar . $remainder;
                break;
            case 'uppercamelcase':
                $theValue = GeneralUtility::underscoredToUpperCamelCase($theValue);
                break;
            case 'lowercamelcase':
                $theValue = GeneralUtility::underscoredToLowerCamelCase($theValue);
                break;
        }
        return $theValue;
    }

    /**
     * Shifts the case of characters outside of HTML tags in the input string
     *
     * @param string $theValue The string to change case for.
     * @param string $case The direction; either "upper" or "lower
     * @return string
     * @see caseshift()
     */
    public function HTMLcaseshift($theValue, $case)
    {
        $inside = 0;
        $newVal = '';
        $pointer = 0;
        $totalLen = strlen($theValue);
        do {
            if (!$inside) {
                $len = strcspn(substr($theValue, $pointer), '<');
                $newVal .= $this->caseshift(substr($theValue, $pointer, $len), $case);
                $inside = 1;
            } else {
                $len = strcspn(substr($theValue, $pointer), '>') + 1;
                $newVal .= substr($theValue, $pointer, $len);
                $inside = 0;
            }
            $pointer += $len;
        } while ($pointer < $totalLen);
        return $newVal;
    }

    /**
     * Returns the 'age' of the tstamp $seconds
     *
     * @param int $seconds Seconds to return age for. Example: "70" => "1 min", "3601" => "1 hrs
     * @param string $labels The labels of the individual units. Defaults to : ' min| hrs| days| yrs'
     * @return string The formatted string
     */
    public function calcAge($seconds, $labels)
    {
        if (MathUtility::canBeInterpretedAsInteger($labels)) {
            $labels = ' min| hrs| days| yrs| min| hour| day| year';
        } else {
            $labels = str_replace('"', '', $labels);
        }
        $labelArr = explode('|', $labels);
        if (count($labelArr) === 4) {
            $labelArr = array_merge($labelArr, $labelArr);
        }
        $absSeconds = abs($seconds);
        $sign = $seconds > 0 ? 1 : -1;
        if ($absSeconds < 3600) {
            $val = round($absSeconds / 60);
            $seconds = $sign * $val . ($val == 1 ? $labelArr[4] : $labelArr[0]);
        } elseif ($absSeconds < 24 * 3600) {
            $val = round($absSeconds / 3600);
            $seconds = $sign * $val . ($val == 1 ? $labelArr[5] : $labelArr[1]);
        } elseif ($absSeconds < 365 * 24 * 3600) {
            $val = round($absSeconds / (24 * 3600));
            $seconds = $sign * $val . ($val == 1 ? $labelArr[6] : $labelArr[2]);
        } else {
            $val = round($absSeconds / (365 * 24 * 3600));
            $seconds = $sign * $val . ($val == 1 ? ($labelArr[7] ?? null) : ($labelArr[3] ?? null));
        }
        return $seconds;
    }

    /**
     * Sends a notification email
     *
     * @param string $message The message content. If blank, no email is sent.
     * @param string $recipients Comma list of recipient email addresses
     * @param string $cc Email address of recipient of an extra mail. The same mail will be sent ONCE more; not using a CC header but sending twice.
     * @param string $senderAddress "From" email address
     * @param string $senderName Optional "From" name
     * @param string $replyTo Optional "Reply-To" header email address.
     * @return bool Returns TRUE if sent
     */
    public function sendNotifyEmail($message, $recipients, $cc, $senderAddress, $senderName = '', $replyTo = '')
    {
        /** @var MailMessage $mail */
        $mail = GeneralUtility::makeInstance(MailMessage::class);
        $senderName = trim($senderName);
        $senderAddress = trim($senderAddress);
        if ($senderName !== '' && $senderAddress !== '') {
            $mail->setFrom([$senderAddress => $senderName]);
        } elseif ($senderAddress !== '') {
            $mail->setFrom([$senderAddress]);
        }
        $parsedReplyTo = MailUtility::parseAddresses($replyTo);
        if (!empty($parsedReplyTo)) {
            $mail->setReplyTo($parsedReplyTo);
        }
        $message = trim($message);
        if ($message !== '') {
            // First line is subject
            $messageParts = explode(LF, $message, 2);
            $subject = trim($messageParts[0]);
            $plainMessage = trim($messageParts[1]);
            $parsedRecipients = MailUtility::parseAddresses($recipients);
            if (!empty($parsedRecipients)) {
                $mail->setTo($parsedRecipients)
                    ->setSubject($subject)
                    ->setBody($plainMessage);
                $mail->send();
            }
            $parsedCc = MailUtility::parseAddresses($cc);
            if (!empty($parsedCc)) {
                $from = $mail->getFrom();
                /** @var MailMessage $mail */
                $mail = GeneralUtility::makeInstance(MailMessage::class);
                if (!empty($parsedReplyTo)) {
                    $mail->setReplyTo($parsedReplyTo);
                }
                $mail->setFrom($from)
                    ->setTo($parsedCc)
                    ->setSubject($subject)
                    ->setBody($plainMessage);
                $mail->send();
            }
            return true;
        }
        return false;
    }

    /**
     * Resolves a TypoScript reference value to the full set of properties BUT overridden with any local properties set.
     * So the reference is resolved but overlaid with local TypoScript properties of the reference value.
     *
     * @param array $confArr The TypoScript array
     * @param string $prop The property name: If this value is a reference (eg. " < plugins.tx_something") then the reference will be retrieved and inserted at that position (into the properties only, not the value...) AND overlaid with the old properties if any.
     * @return array The modified TypoScript array
     */
    public function mergeTSRef($confArr, $prop)
    {
        if ($confArr[$prop][0] === '<') {
            $key = trim(substr($confArr[$prop], 1));
            $cF = GeneralUtility::makeInstance(TypoScriptParser::class);
            // $name and $conf is loaded with the referenced values.
            $old_conf = $confArr[$prop . '.'];
            list(, $conf) = $cF->getVal($key, $this->getTypoScriptFrontendController()->tmpl->setup);
            if (is_array($old_conf) && !empty($old_conf)) {
                $conf = is_array($conf) ? array_replace_recursive($conf, $old_conf) : $old_conf;
            }
            $confArr[$prop . '.'] = $conf;
        }
        return $confArr;
    }

    /***********************************************
     *
     * Database functions, making of queries
     *
     ***********************************************/

    /**
     * Returns a part of a WHERE clause which will filter out records with start/end times or hidden/fe_groups fields
     * set to values that should de-select them according to the current time, preview settings or user login.
     * Definitely a frontend function.
     * THIS IS A VERY IMPORTANT FUNCTION: Basically you must add the output from this function for EVERY select query you create
     * for selecting records of tables in your own applications - thus they will always be filtered according to the "enablefields"
     * configured in TCA
     * Simply calls \TYPO3\CMS\Frontend\Page\PageRepository::enableFields() BUT will send the show_hidden flag along!
     * This means this function will work in conjunction with the preview facilities of the frontend engine/Admin Panel.
     *
     * @param string $table The table for which to get the where clause
     * @param bool $show_hidden If set, then you want NOT to filter out hidden records. Otherwise hidden record are filtered based on the current preview settings.
     * @param array $ignore_array Array you can pass where keys can be "disabled", "starttime", "endtime", "fe_group" (keys from "enablefields" in TCA) and if set they will make sure that part of the clause is not added. Thus disables the specific part of the clause. For previewing etc.
     * @return string The part of the where clause on the form " AND [fieldname]=0 AND ...". Eg. " AND hidden=0 AND starttime < 123345567
     * @deprecated since TYPO3 v9.4, will be removed in TYPO3 v10.0.
     */
    public function enableFields($table, $show_hidden = false, array $ignore_array = [])
    {
        trigger_error('cObj->enableFields() will be removed in TYPO3 v10.0. should be used from the PageRepository->enableFields() functionality directly.', E_USER_DEPRECATED);
        return $this->getTypoScriptFrontendController()->sys_page->enableFields($table, $show_hidden ? true : -1, $ignore_array);
    }

    /**
     * Generates a list of Page-uid's from $id. List does not include $id itself
     * (unless the id specified is negative in which case it does!)
     * The only pages WHICH PREVENTS DECENDING in a branch are
     * - deleted pages,
     * - pages in a recycler (doktype = 255) or of the Backend User Section (doktpe = 6) type
     * - pages that has the extendToSubpages set, WHERE start/endtime, hidden
     * and fe_users would hide the records.
     * Apart from that, pages with enable-fields excluding them, will also be
     * removed. HOWEVER $dontCheckEnableFields set will allow
     * enableFields-excluded pages to be included anyway - including
     * extendToSubpages sections!
     * Mount Pages are also descended but notice that these ID numbers are not
     * useful for links unless the correct MPvar is set.
     *
     * @param int $id The id of the start page from which point in the page tree to descend. IF NEGATIVE the id itself is included in the end of the list (only if $begin is 0) AND the output does NOT contain a last comma. Recommended since it will resolve the input ID for mount pages correctly and also check if the start ID actually exists!
     * @param int $depth The number of levels to descend. If you want to descend infinitely, just set this to 100 or so. Should be at least "1" since zero will just make the function return (no decend...)
     * @param int $begin Is an optional integer that determines at which level in the tree to start collecting uid's. Zero means 'start right away', 1 = 'next level and out'
     * @param bool $dontCheckEnableFields See function description
     * @param string $addSelectFields Additional fields to select. Syntax: ",[fieldname],[fieldname],...
     * @param string $moreWhereClauses Additional where clauses. Syntax: " AND [fieldname]=[value] AND ...
     * @param array $prevId_array array of IDs from previous recursions. In order to prevent infinite loops with mount pages.
     * @param int $recursionLevel Internal: Zero for the first recursion, incremented for each recursive call.
     * @return string Returns the list of ids as a comma separated string
     * @see TypoScriptFrontendController::checkEnableFields(), TypoScriptFrontendController::checkPagerecordForIncludeSection()
     */
    public function getTreeList($id, $depth, $begin = 0, $dontCheckEnableFields = false, $addSelectFields = '', $moreWhereClauses = '', array $prevId_array = [], $recursionLevel = 0)
    {
        $id = (int)$id;
        if (!$id) {
            return '';
        }

        // Init vars:
        $allFields = 'uid,hidden,starttime,endtime,fe_group,extendToSubpages,doktype,php_tree_stop,mount_pid,mount_pid_ol,t3ver_state' . $addSelectFields;
        $depth = (int)$depth;
        $begin = (int)$begin;
        $theList = [];
        $addId = 0;
        $requestHash = '';

        // First level, check id (second level, this is done BEFORE the recursive call)
        $tsfe = $this->getTypoScriptFrontendController();
        if (!$recursionLevel) {
            // Check tree list cache
            // First, create the hash for this request - not sure yet whether we need all these parameters though
            $parameters = [
                $id,
                $depth,
                $begin,
                $dontCheckEnableFields,
                $addSelectFields,
                $moreWhereClauses,
                $prevId_array,
                GeneralUtility::makeInstance(Context::class)->getPropertyFromAspect('frontend.user', 'groupIds', [0, -1])
            ];
            $requestHash = md5(serialize($parameters));
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable('cache_treelist');
            $cacheEntry = $queryBuilder->select('treelist')
                ->from('cache_treelist')
                ->where(
                    $queryBuilder->expr()->eq(
                        'md5hash',
                        $queryBuilder->createNamedParameter($requestHash, \PDO::PARAM_STR)
                    ),
                    $queryBuilder->expr()->orX(
                        $queryBuilder->expr()->gt(
                            'expires',
                            $queryBuilder->createNamedParameter($GLOBALS['EXEC_TIME'], \PDO::PARAM_INT)
                        ),
                        $queryBuilder->expr()->eq('expires', $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT))
                    )
                )
                ->setMaxResults(1)
                ->execute()
                ->fetch();

            if (is_array($cacheEntry)) {
                // Cache hit
                return $cacheEntry['treelist'];
            }
            // If Id less than zero it means we should add the real id to list:
            if ($id < 0) {
                $addId = $id = abs($id);
            }
            // Check start page:
            if ($tsfe->sys_page->getRawRecord('pages', $id, 'uid')) {
                // Find mount point if any:
                $mount_info = $tsfe->sys_page->getMountPointInfo($id);
                if (is_array($mount_info)) {
                    $id = $mount_info['mount_pid'];
                    // In Overlay mode, use the mounted page uid as added ID!:
                    if ($addId && $mount_info['overlay']) {
                        $addId = $id;
                    }
                }
            } else {
                // Return blank if the start page was NOT found at all!
                return '';
            }
        }
        // Add this ID to the array of IDs
        if ($begin <= 0) {
            $prevId_array[] = $id;
        }
        // Select sublevel:
        if ($depth > 0) {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
            $queryBuilder->getRestrictions()
                ->removeAll()
                ->add(GeneralUtility::makeInstance(DeletedRestriction::class));
            $queryBuilder->select(...GeneralUtility::trimExplode(',', $allFields, true))
                ->from('pages')
                ->where(
                    $queryBuilder->expr()->eq(
                        'pid',
                        $queryBuilder->createNamedParameter($id, \PDO::PARAM_INT)
                    ),
                    // tree is only built by language=0 pages
                    $queryBuilder->expr()->eq('sys_language_uid', 0)
                )
                ->orderBy('sorting');

            if (!empty($moreWhereClauses)) {
                $queryBuilder->andWhere(QueryHelper::stripLogicalOperatorPrefix($moreWhereClauses));
            }

            $result = $queryBuilder->execute();
            while ($row = $result->fetch()) {
                /** @var VersionState $versionState */
                $versionState = VersionState::cast($row['t3ver_state']);
                $tsfe->sys_page->versionOL('pages', $row);
                if ((int)$row['doktype'] === PageRepository::DOKTYPE_RECYCLER
                    || (int)$row['doktype'] === PageRepository::DOKTYPE_BE_USER_SECTION
                    || $versionState->indicatesPlaceholder()
                ) {
                    // Doing this after the overlay to make sure changes
                    // in the overlay are respected.
                    // However, we do not process pages below of and
                    // including of type recycler and BE user section
                    continue;
                }
                // Find mount point if any:
                $next_id = $row['uid'];
                $mount_info = $tsfe->sys_page->getMountPointInfo($next_id, $row);
                // Overlay mode:
                if (is_array($mount_info) && $mount_info['overlay']) {
                    $next_id = $mount_info['mount_pid'];
                    $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                        ->getQueryBuilderForTable('pages');
                    $queryBuilder->getRestrictions()
                        ->removeAll()
                        ->add(GeneralUtility::makeInstance(DeletedRestriction::class));
                    $queryBuilder->select(...GeneralUtility::trimExplode(',', $allFields, true))
                        ->from('pages')
                        ->where(
                            $queryBuilder->expr()->eq(
                                'uid',
                                $queryBuilder->createNamedParameter($next_id, \PDO::PARAM_INT)
                            )
                        )
                        ->orderBy('sorting')
                        ->setMaxResults(1);

                    if (!empty($moreWhereClauses)) {
                        $queryBuilder->andWhere(QueryHelper::stripLogicalOperatorPrefix($moreWhereClauses));
                    }

                    $row = $queryBuilder->execute()->fetch();
                    $tsfe->sys_page->versionOL('pages', $row);
                    if ((int)$row['doktype'] === PageRepository::DOKTYPE_RECYCLER
                        || (int)$row['doktype'] === PageRepository::DOKTYPE_BE_USER_SECTION
                        || $versionState->indicatesPlaceholder()
                    ) {
                        // Doing this after the overlay to make sure
                        // changes in the overlay are respected.
                        // see above
                        continue;
                    }
                }
                // Add record:
                if ($dontCheckEnableFields || $tsfe->checkPagerecordForIncludeSection($row)) {
                    // Add ID to list:
                    if ($begin <= 0) {
                        if ($dontCheckEnableFields || $tsfe->checkEnableFields($row)) {
                            $theList[] = $next_id;
                        }
                    }
                    // Next level:
                    if ($depth > 1 && !$row['php_tree_stop']) {
                        // Normal mode:
                        if (is_array($mount_info) && !$mount_info['overlay']) {
                            $next_id = $mount_info['mount_pid'];
                        }
                        // Call recursively, if the id is not in prevID_array:
                        if (!in_array($next_id, $prevId_array)) {
                            $theList = array_merge(
                                GeneralUtility::intExplode(
                                    ',',
                                    $this->getTreeList(
                                        $next_id,
                                        $depth - 1,
                                        $begin - 1,
                                        $dontCheckEnableFields,
                                        $addSelectFields,
                                        $moreWhereClauses,
                                        $prevId_array,
                                        $recursionLevel + 1
                                    ),
                                    true
                                ),
                                $theList
                            );
                        }
                    }
                }
            }
        }
        // If first run, check if the ID should be returned:
        if (!$recursionLevel) {
            if ($addId) {
                if ($begin > 0) {
                    $theList[] = 0;
                } else {
                    $theList[] = $addId;
                }
            }

            $cacheEntry = [
                'md5hash' => $requestHash,
                'pid' => $id,
                'treelist' => implode(',', $theList),
                'tstamp' => $GLOBALS['EXEC_TIME'],
            ];

            // Only add to cache if not logged into TYPO3 Backend
            if (!$this->getFrontendBackendUser() instanceof AbstractUserAuthentication) {
                $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('cache_treelist');
                try {
                    $connection->transactional(function ($connection) use ($cacheEntry) {
                        $connection->insert('cache_treelist', $cacheEntry);
                    });
                } catch (\Throwable $e) {
                }
            }
        }

        return implode(',', $theList);
    }

    /**
     * Generates a search where clause based on the input search words (AND operation - all search words must be found in record.)
     * Example: The $sw is "content management, system" (from an input form) and the $searchFieldList is "bodytext,header" then the output will be ' AND (bodytext LIKE "%content%" OR header LIKE "%content%") AND (bodytext LIKE "%management%" OR header LIKE "%management%") AND (bodytext LIKE "%system%" OR header LIKE "%system%")'
     *
     * @param string $searchWords The search words. These will be separated by space and comma.
     * @param string $searchFieldList The fields to search in
     * @param string $searchTable The table name you search in (recommended for DBAL compliance. Will be prepended field names as well)
     * @return string The WHERE clause.
     */
    public function searchWhere($searchWords, $searchFieldList, $searchTable)
    {
        if (!$searchWords) {
            return '';
        }

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable($searchTable);

        $prefixTableName = $searchTable ? $searchTable . '.' : '';

        $where = $queryBuilder->expr()->andX();
        $searchFields = explode(',', $searchFieldList);
        $searchWords = preg_split('/[ ,]/', $searchWords);
        foreach ($searchWords as $searchWord) {
            $searchWord = trim($searchWord);
            if (strlen($searchWord) < 3) {
                continue;
            }
            $searchWordConstraint = $queryBuilder->expr()->orX();
            $searchWord = $queryBuilder->escapeLikeWildcards($searchWord);
            foreach ($searchFields as $field) {
                $searchWordConstraint->add(
                    $queryBuilder->expr()->like($prefixTableName . $field, $queryBuilder->quote('%' . $searchWord . '%'))
                );
            }

            if ($searchWordConstraint->count()) {
                $where->add($searchWordConstraint);
            }
        }

        if ((string)$where === '') {
            return '';
        }

        return ' AND (' . (string)$where . ')';
    }

    /**
     * Executes a SELECT query for records from $table and with conditions based on the configuration in the $conf array
     * This function is preferred over ->getQuery() if you just need to create and then execute a query.
     *
     * @param string $table The table name
     * @param array $conf The TypoScript configuration properties
     * @return Statement
     * @see getQuery()
     */
    public function exec_getQuery($table, $conf)
    {
        $statement = $this->getQuery($table, $conf);
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($table);

        return $connection->executeQuery($statement);
    }

    /**
     * Executes a SELECT query for records from $table and with conditions based on the configuration in the $conf array
     * and overlays with translation and version if available
     *
     * @param string $tableName the name of the TCA database table
     * @param array $queryConfiguration The TypoScript configuration properties, see .select in TypoScript reference
     * @return array The records
     * @throws \UnexpectedValueException
     */
    public function getRecords($tableName, array $queryConfiguration)
    {
        $records = [];

        $statement = $this->exec_getQuery($tableName, $queryConfiguration);

        $tsfe = $this->getTypoScriptFrontendController();
        while ($row = $statement->fetch()) {
            // Versioning preview:
            $tsfe->sys_page->versionOL($tableName, $row, true);

            // Language overlay:
            if (is_array($row)) {
                $row = $tsfe->sys_page->getLanguageOverlay($tableName, $row);
            }

            // Might be unset in the language overlay
            if (is_array($row)) {
                $records[] = $row;
            }
        }

        return $records;
    }

    /**
     * Creates and returns a SELECT query for records from $table and with conditions based on the configuration in the $conf array
     * Implements the "select" function in TypoScript
     *
     * @param string $table See ->exec_getQuery()
     * @param array $conf See ->exec_getQuery()
     * @param bool $returnQueryArray If set, the function will return the query not as a string but array with the various parts. RECOMMENDED!
     * @return mixed A SELECT query if $returnQueryArray is FALSE, otherwise the SELECT query in an array as parts.
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     * @internal
     * @see CONTENT(), numRows()
     */
    public function getQuery($table, $conf, $returnQueryArray = false)
    {
        // Resolve stdWrap in these properties first
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($table);
        $properties = [
            'pidInList',
            'uidInList',
            'languageField',
            'selectFields',
            'max',
            'begin',
            'groupBy',
            'orderBy',
            'join',
            'leftjoin',
            'rightjoin',
            'recursive',
            'where'
        ];
        foreach ($properties as $property) {
            $conf[$property] = trim(
                isset($conf[$property . '.'])
                    ? $this->stdWrap($conf[$property], $conf[$property . '.'])
                    : $conf[$property]
            );
            if ($conf[$property] === '') {
                unset($conf[$property]);
            } elseif (in_array($property, ['languageField', 'selectFields', 'join', 'leftjoin', 'rightjoin', 'where'], true)) {
                $conf[$property] = QueryHelper::quoteDatabaseIdentifiers($connection, $conf[$property]);
            }
            if (isset($conf[$property . '.'])) {
                // stdWrapping already done, so remove the sub-array
                unset($conf[$property . '.']);
            }
        }
        // Handle PDO-style named parameter markers first
        $queryMarkers = $this->getQueryMarkers($table, $conf);
        // Replace the markers in the non-stdWrap properties
        foreach ($queryMarkers as $marker => $markerValue) {
            $properties = [
                'uidInList',
                'selectFields',
                'where',
                'max',
                'begin',
                'groupBy',
                'orderBy',
                'join',
                'leftjoin',
                'rightjoin'
            ];
            foreach ($properties as $property) {
                if ($conf[$property]) {
                    $conf[$property] = str_replace('###' . $marker . '###', $markerValue, $conf[$property]);
                }
            }
        }

        // Construct WHERE clause:
        // Handle recursive function for the pidInList
        if (isset($conf['recursive'])) {
            $conf['recursive'] = (int)$conf['recursive'];
            if ($conf['recursive'] > 0) {
                $pidList = GeneralUtility::trimExplode(',', $conf['pidInList'], true);
                array_walk($pidList, function (&$storagePid) {
                    if ($storagePid === 'this') {
                        $storagePid = $this->getTypoScriptFrontendController()->id;
                    }
                    if ($storagePid > 0) {
                        $storagePid = -$storagePid;
                    }
                });
                $expandedPidList = [];
                foreach ($pidList as $value) {
                    // Implementation of getTreeList allows to pass the id negative to include
                    // it into the result otherwise only childpages are returned
                    $expandedPidList = array_merge(
                        GeneralUtility::intExplode(',', $this->getTreeList($value, $conf['recursive'])),
                        $expandedPidList
                    );
                }
                $conf['pidInList'] = implode(',', $expandedPidList);
            }
        }
        if ((string)$conf['pidInList'] === '') {
            $conf['pidInList'] = 'this';
        }

        $queryParts = $this->getQueryConstraints($table, $conf);

        $queryBuilder = $connection->createQueryBuilder();
        // @todo Check against getQueryConstraints, can probably use FrontendRestrictions
        // @todo here and remove enableFields there.
        $queryBuilder->getRestrictions()->removeAll();
        $queryBuilder->select('*')->from($table);

        if ($queryParts['where']) {
            $queryBuilder->where($queryParts['where']);
        }

        if ($queryParts['groupBy']) {
            $queryBuilder->groupBy(...$queryParts['groupBy']);
        }

        if (is_array($queryParts['orderBy'])) {
            foreach ($queryParts['orderBy'] as $orderBy) {
                $queryBuilder->addOrderBy(...$orderBy);
            }
        }

        // Fields:
        if ($conf['selectFields']) {
            $queryBuilder->selectLiteral($this->sanitizeSelectPart($conf['selectFields'], $table));
        }

        // Setting LIMIT:
        $error = false;
        if ($conf['max'] || $conf['begin']) {
            // Finding the total number of records, if used:
            if (strpos(strtolower($conf['begin'] . $conf['max']), 'total') !== false) {
                $countQueryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
                $countQueryBuilder->getRestrictions()->removeAll();
                $countQueryBuilder->count('*')
                    ->from($table)
                    ->where($queryParts['where']);

                if ($queryParts['groupBy']) {
                    $countQueryBuilder->groupBy(...$queryParts['groupBy']);
                }

                try {
                    $count = $countQueryBuilder->execute()->fetchColumn(0);
                    $conf['max'] = str_ireplace('total', $count, $conf['max']);
                    $conf['begin'] = str_ireplace('total', $count, $conf['begin']);
                } catch (DBALException $e) {
                    $this->getTimeTracker()->setTSlogMessage($e->getPrevious()->getMessage());
                    $error = true;
                }
            }

            if (!$error) {
                $conf['begin'] = MathUtility::forceIntegerInRange(ceil($this->calc($conf['begin'])), 0);
                $conf['max'] = MathUtility::forceIntegerInRange(ceil($this->calc($conf['max'])), 0);
                if ($conf['begin'] > 0) {
                    $queryBuilder->setFirstResult($conf['begin']);
                }
                $queryBuilder->setMaxResults($conf['max'] ?: 100000);
            }
        }

        if (!$error) {
            // Setting up tablejoins:
            if ($conf['join']) {
                $joinParts = QueryHelper::parseJoin($conf['join']);
                $queryBuilder->join(
                    $table,
                    $joinParts['tableName'],
                    $joinParts['tableAlias'],
                    $joinParts['joinCondition']
                );
            } elseif ($conf['leftjoin']) {
                $joinParts = QueryHelper::parseJoin($conf['leftjoin']);
                $queryBuilder->leftJoin(
                    $table,
                    $joinParts['tableName'],
                    $joinParts['tableAlias'],
                    $joinParts['joinCondition']
                );
            } elseif ($conf['rightjoin']) {
                $joinParts = QueryHelper::parseJoin($conf['rightjoin']);
                $queryBuilder->rightJoin(
                    $table,
                    $joinParts['tableName'],
                    $joinParts['tableAlias'],
                    $joinParts['joinCondition']
                );
            }

            // Convert the QueryBuilder object into a SQL statement.
            $query = $queryBuilder->getSQL();

            // Replace the markers in the queryParts to handle stdWrap enabled properties
            foreach ($queryMarkers as $marker => $markerValue) {
                // @todo Ugly hack that needs to be cleaned up, with the current architecture
                // @todo for exec_Query / getQuery it's the best we can do.
                $query = str_replace('###' . $marker . '###', $markerValue, $query);
                foreach ($queryParts as $queryPartKey => &$queryPartValue) {
                    $queryPartValue = str_replace('###' . $marker . '###', $markerValue, $queryPartValue);
                }
                unset($queryPartValue);
            }

            return $returnQueryArray ? $this->getQueryArray($queryBuilder) : $query;
        }

        return '';
    }

    /**
     * Helper to transform a QueryBuilder object into a queryParts array that can be used
     * with exec_SELECT_queryArray
     *
     * @param \TYPO3\CMS\Core\Database\Query\QueryBuilder $queryBuilder
     * @return array
     * @throws \RuntimeException
     */
    protected function getQueryArray(QueryBuilder $queryBuilder)
    {
        $fromClauses = [];
        $knownAliases = [];
        $queryParts = [];

        // Loop through all FROM clauses
        foreach ($queryBuilder->getQueryPart('from') as $from) {
            if ($from['alias'] === null) {
                $tableSql = $from['table'];
                $tableReference = $from['table'];
            } else {
                $tableSql = $from['table'] . ' ' . $from['alias'];
                $tableReference = $from['alias'];
            }

            $knownAliases[$tableReference] = true;

            $fromClauses[$tableReference] = $tableSql . $this->getQueryArrayJoinHelper(
                $tableReference,
                $queryBuilder->getQueryPart('join'),
                $knownAliases
            );
        }

        $queryParts['SELECT'] = implode(', ', $queryBuilder->getQueryPart('select'));
        $queryParts['FROM'] = implode(', ', $fromClauses);
        $queryParts['WHERE'] = (string)$queryBuilder->getQueryPart('where') ?: '';
        $queryParts['GROUPBY'] = implode(', ', $queryBuilder->getQueryPart('groupBy'));
        $queryParts['ORDERBY'] = implode(', ', $queryBuilder->getQueryPart('orderBy'));
        if ($queryBuilder->getFirstResult() > 0) {
            $queryParts['LIMIT'] = $queryBuilder->getFirstResult() . ',' . $queryBuilder->getMaxResults();
        } elseif ($queryBuilder->getMaxResults() > 0) {
            $queryParts['LIMIT'] = $queryBuilder->getMaxResults();
        }

        return $queryParts;
    }

    /**
     * Helper to transform the QueryBuilder join part into a SQL fragment.
     *
     * @param string $fromAlias
     * @param array $joinParts
     * @param array $knownAliases
     * @return string
     * @throws \RuntimeException
     */
    protected function getQueryArrayJoinHelper(string $fromAlias, array $joinParts, array &$knownAliases): string
    {
        $sql = '';

        if (isset($joinParts['join'][$fromAlias])) {
            foreach ($joinParts['join'][$fromAlias] as $join) {
                if (array_key_exists($join['joinAlias'], $knownAliases)) {
                    throw new \RuntimeException(
                        'Non unique join alias: "' . $join['joinAlias'] . '" found.',
                        1472748872
                    );
                }
                $sql .= ' ' . strtoupper($join['joinType'])
                    . ' JOIN ' . $join['joinTable'] . ' ' . $join['joinAlias']
                    . ' ON ' . ((string)$join['joinCondition']);
                $knownAliases[$join['joinAlias']] = true;
            }

            foreach ($joinParts['join'][$fromAlias] as $join) {
                $sql .= $this->getQueryArrayJoinHelper($join['joinAlias'], $joinParts, $knownAliases);
            }
        }

        return $sql;
    }
    /**
     * Helper function for getQuery(), creating the WHERE clause of the SELECT query
     *
     * @param string $table The table name
     * @param array $conf The TypoScript configuration properties
     * @return array Associative array containing the prepared data for WHERE, ORDER BY and GROUP BY fragments
     * @throws \InvalidArgumentException
     * @see getQuery()
     */
    protected function getQueryConstraints(string $table, array $conf): array
    {
        // Init:
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
        $expressionBuilder = $queryBuilder->expr();
        $tsfe = $this->getTypoScriptFrontendController();
        $constraints = [];
        $pid_uid_flag = 0;
        $enableFieldsIgnore = [];
        $queryParts = [
            'where' => null,
            'groupBy' => null,
            'orderBy' => null,
        ];

        $isInWorkspace = GeneralUtility::makeInstance(Context::class)->getPropertyFromAspect('workspace', 'isOffline');
        $considerMovePlaceholders = (
            $isInWorkspace && $table !== 'pages'
            && !empty($GLOBALS['TCA'][$table]['ctrl']['versioningWS'])
        );

        if (trim($conf['uidInList'])) {
            $listArr = GeneralUtility::intExplode(',', str_replace('this', $tsfe->contentPid, $conf['uidInList']));

            // If move placeholder shall be considered, select via t3ver_move_id
            if ($considerMovePlaceholders) {
                $constraints[] = (string)$expressionBuilder->orX(
                    $expressionBuilder->in($table . '.uid', $listArr),
                    $expressionBuilder->andX(
                        $expressionBuilder->eq(
                            $table . '.t3ver_state',
                            (int)(string)VersionState::cast(VersionState::MOVE_PLACEHOLDER)
                        ),
                        $expressionBuilder->in($table . '.t3ver_move_id', $listArr)
                    )
                );
            } else {
                $constraints[] = (string)$expressionBuilder->in($table . '.uid', $listArr);
            }
            $pid_uid_flag++;
        }

        // Static_* tables are allowed to be fetched from root page
        if (strpos($table, 'static_') === 0) {
            $pid_uid_flag++;
        }

        if (trim($conf['pidInList'])) {
            $listArr = GeneralUtility::intExplode(',', str_replace('this', $tsfe->contentPid, $conf['pidInList']));
            // Removes all pages which are not visible for the user!
            $listArr = $this->checkPidArray($listArr);
            if (GeneralUtility::inList($conf['pidInList'], 'root')) {
                $listArr[] = 0;
            }
            if (GeneralUtility::inList($conf['pidInList'], '-1')) {
                $listArr[] = -1;
                $enableFieldsIgnore['pid'] = true;
            }
            if (!empty($listArr)) {
                $constraints[] = $expressionBuilder->in($table . '.pid', array_map('intval', $listArr));
                $pid_uid_flag++;
            } else {
                // If not uid and not pid then uid is set to 0 - which results in nothing!!
                $pid_uid_flag = 0;
            }
        }

        // If not uid and not pid then uid is set to 0 - which results in nothing!!
        if (!$pid_uid_flag) {
            $constraints[] = $expressionBuilder->eq($table . '.uid', 0);
        }

        $where = isset($conf['where.']) ? trim($this->stdWrap($conf['where'], $conf['where.'])) : trim($conf['where']);
        if ($where) {
            $constraints[] = QueryHelper::stripLogicalOperatorPrefix($where);
        }

        // Check if the default language should be fetched (= doing overlays), or if only the records of a language should be fetched
        // but only do this for TCA tables that have languages enabled
        $languageConstraint = $this->getLanguageRestriction($expressionBuilder, $table, $conf, GeneralUtility::makeInstance(Context::class));
        if ($languageConstraint !== null) {
            $constraints[] = $languageConstraint;
        }

        // Enablefields
        if ($table === 'pages') {
            $constraints[] = QueryHelper::stripLogicalOperatorPrefix($tsfe->sys_page->where_hid_del);
            $constraints[] = QueryHelper::stripLogicalOperatorPrefix($tsfe->sys_page->where_groupAccess);
        } else {
            $constraints[] = QueryHelper::stripLogicalOperatorPrefix($tsfe->sys_page->enableFields($table, -1, $enableFieldsIgnore));
        }

        // MAKE WHERE:
        if (count($constraints) !== 0) {
            $queryParts['where'] = $expressionBuilder->andX(...$constraints);
        }
        // GROUP BY
        if (trim($conf['groupBy'])) {
            $groupBy = isset($conf['groupBy.'])
                ? trim($this->stdWrap($conf['groupBy'], $conf['groupBy.']))
                : trim($conf['groupBy']);
            $queryParts['groupBy'] = QueryHelper::parseGroupBy($groupBy);
        }

        // ORDER BY
        if (trim($conf['orderBy'])) {
            $orderByString = isset($conf['orderBy.'])
                ? trim($this->stdWrap($conf['orderBy'], $conf['orderBy.']))
                : trim($conf['orderBy']);

            $queryParts['orderBy'] = QueryHelper::parseOrderBy($orderByString);
        }

        // Return result:
        return $queryParts;
    }

    /**
     * Adds parts to the WHERE clause that are related to language.
     * This only works on TCA tables which have the [ctrl][languageField] field set or if they
     * have select.languageField = my_language_field set explicitly.
     *
     * It is also possible to disable the language restriction for a query by using select.languageField = 0,
     * if select.languageField is not explicitly set, the TCA default values are taken.
     *
     * If the table is "localizeable" (= any of the criteria above is met), then the DB query is restricted:
     *
     * If the current language aspect has overlays enabled, then the only records with language "0" or "-1" are
     * fetched (the overlays are taken care of later-on).
     * if the current language has overlays but also records without localization-parent (free mode) available,
     * then these are fetched as well. This can explicitly set via select.includeRecordsWithoutDefaultTranslation = 1
     * which overrules the overlayType within the language aspect.
     *
     * If the language aspect has NO overlays enabled, it behaves as in "free mode" (= only fetch the records
     * for the current language.
     *
     * @param ExpressionBuilder $expressionBuilder
     * @param string $table
     * @param array $conf
     * @param Context $context
     * @return string|\TYPO3\CMS\Core\Database\Query\Expression\CompositeExpression|null
     * @throws \TYPO3\CMS\Core\Context\Exception\AspectNotFoundException
     */
    protected function getLanguageRestriction(ExpressionBuilder $expressionBuilder, string $table, array $conf, Context $context)
    {
        $languageField = '';
        $localizationParentField = $GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField'] ?? null;
        // Check if the table is translatable, and set the language field by default from the TCA information
        if (!empty($conf['languageField']) || !isset($conf['languageField'])) {
            if (isset($conf['languageField']) && !empty($GLOBALS['TCA'][$table]['columns'][$conf['languageField']])) {
                $languageField = $conf['languageField'];
            } elseif (!empty($GLOBALS['TCA'][$table]['ctrl']['languageField']) && !empty($localizationParentField)) {
                $languageField = $table . '.' . $GLOBALS['TCA'][$table]['ctrl']['languageField'];
            }
        }

        // No language restriction enabled explicitly or available via TCA
        if (empty($languageField)) {
            return null;
        }

        /** @var LanguageAspect $languageAspect */
        $languageAspect = $context->getAspect('language');
        if ($languageAspect->doOverlays() && !empty($localizationParentField)) {
            // Sys language content is set to zero/-1 - and it is expected that whatever routine processes the output will
            // OVERLAY the records with localized versions!
            $languageQuery = $expressionBuilder->in($languageField, [0, -1]);
            // Use this option to include records that don't have a default language counterpart ("free mode")
            // (originalpointerfield is 0 and the language field contains the requested language)
            if (isset($conf['includeRecordsWithoutDefaultTranslation']) || $conf['includeRecordsWithoutDefaultTranslation.']) {
                $includeRecordsWithoutDefaultTranslation = isset($conf['includeRecordsWithoutDefaultTranslation.']) ?
                    $this->stdWrap($conf['includeRecordsWithoutDefaultTranslation'], $conf['includeRecordsWithoutDefaultTranslation.']) : $conf['includeRecordsWithoutDefaultTranslation'];
                $includeRecordsWithoutDefaultTranslation = trim($includeRecordsWithoutDefaultTranslation) !== '';
            } else {
                // Option was not explicitly set, check what's in for the language overlay type.
                $includeRecordsWithoutDefaultTranslation = $languageAspect->getOverlayType() === $languageAspect::OVERLAYS_ON_WITH_FLOATING;
            }
            if ($includeRecordsWithoutDefaultTranslation) {
                $languageQuery = $expressionBuilder->orX(
                    $languageQuery,
                    $expressionBuilder->andX(
                        $expressionBuilder->eq($table . '.' . $localizationParentField, 0),
                        $expressionBuilder->eq($languageField, $languageAspect->getContentId())
                    )
                );
            }
            return $languageQuery;
        }
        // No overlays = only fetch records given for the requested language and "all languages"
        return $expressionBuilder->in($languageField, [$languageAspect->getContentId(), -1]);
    }

    /**
     * Helper function for getQuery, sanitizing the select part
     *
     * This functions checks if the necessary fields are part of the select
     * and adds them if necessary.
     *
     * @param string $selectPart Select part
     * @param string $table Table to select from
     * @return string Sanitized select part
     * @internal
     * @see getQuery
     */
    protected function sanitizeSelectPart($selectPart, $table)
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($table);

        // Pattern matching parts
        $matchStart = '/(^\\s*|,\\s*|' . $table . '\\.)';
        $matchEnd = '(\\s*,|\\s*$)/';
        $necessaryFields = ['uid', 'pid'];
        $wsFields = ['t3ver_state'];
        if (isset($GLOBALS['TCA'][$table]) && !preg_match($matchStart . '\\*' . $matchEnd, $selectPart) && !preg_match('/(count|max|min|avg|sum)\\([^\\)]+\\)|distinct/i', $selectPart)) {
            foreach ($necessaryFields as $field) {
                $match = $matchStart . $field . $matchEnd;
                if (!preg_match($match, $selectPart)) {
                    $selectPart .= ', ' . $connection->quoteIdentifier($table . '.' . $field) . ' AS ' . $connection->quoteIdentifier($field);
                }
            }
            if ($GLOBALS['TCA'][$table]['ctrl']['versioningWS']) {
                foreach ($wsFields as $field) {
                    $match = $matchStart . $field . $matchEnd;
                    if (!preg_match($match, $selectPart)) {
                        $selectPart .= ', ' . $connection->quoteIdentifier($table . '.' . $field) . ' AS ' . $connection->quoteIdentifier($field);
                    }
                }
            }
        }
        return $selectPart;
    }

    /**
     * Removes Page UID numbers from the input array which are not available due to enableFields() or the list of bad doktype numbers ($this->checkPid_badDoktypeList)
     *
     * @param array $listArr Array of Page UID numbers for select and for which pages with enablefields and bad doktypes should be removed.
     * @return array Returns the array of remaining page UID numbers
     * @internal
     * @see checkPid()
     */
    public function checkPidArray($listArr)
    {
        if (!is_array($listArr) || empty($listArr)) {
            return [];
        }
        $outArr = [];
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
        $queryBuilder->setRestrictions(GeneralUtility::makeInstance(FrontendRestrictionContainer::class));
        $queryBuilder->select('uid')
            ->from('pages')
            ->where(
                $queryBuilder->expr()->in(
                    'uid',
                    $queryBuilder->createNamedParameter($listArr, Connection::PARAM_INT_ARRAY)
                ),
                $queryBuilder->expr()->notIn(
                    'doktype',
                    $queryBuilder->createNamedParameter(
                        GeneralUtility::intExplode(',', $this->checkPid_badDoktypeList, true),
                        Connection::PARAM_INT_ARRAY
                    )
                )
            );
        try {
            $result = $queryBuilder->execute();
            while ($row = $result->fetch()) {
                $outArr[] = $row['uid'];
            }
        } catch (DBALException $e) {
            $this->getTimeTracker()->setTSlogMessage($e->getMessage() . ': ' . $queryBuilder->getSQL(), 3);
        }

        return $outArr;
    }

    /**
     * Checks if a page UID is available due to enableFields() AND the list of bad doktype numbers ($this->checkPid_badDoktypeList)
     *
     * @param int $uid Page UID to test
     * @return bool TRUE if OK
     * @internal
     * @see checkPidArray()
     */
    public function checkPid($uid)
    {
        $uid = (int)$uid;
        if (!isset($this->checkPid_cache[$uid])) {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
            $queryBuilder->setRestrictions(GeneralUtility::makeInstance(FrontendRestrictionContainer::class));
            $count = $queryBuilder->count('*')
                ->from('pages')
                ->where(
                    $queryBuilder->expr()->eq(
                        'uid',
                        $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT)
                    ),
                    $queryBuilder->expr()->notIn(
                        'doktype',
                        $queryBuilder->createNamedParameter(
                            GeneralUtility::intExplode(',', $this->checkPid_badDoktypeList, true),
                            Connection::PARAM_INT_ARRAY
                        )
                    )
                )
                ->execute()
                ->fetchColumn(0);

            $this->checkPid_cache[$uid] = (bool)$count;
        }
        return $this->checkPid_cache[$uid];
    }

    /**
     * Builds list of marker values for handling PDO-like parameter markers in select parts.
     * Marker values support stdWrap functionality thus allowing a way to use stdWrap functionality in various properties of 'select' AND prevents SQL-injection problems by quoting and escaping of numeric values, strings, NULL values and comma separated lists.
     *
     * @param string $table Table to select records from
     * @param array $conf Select part of CONTENT definition
     * @return array List of values to replace markers with
     * @internal
     * @see getQuery()
     */
    public function getQueryMarkers($table, $conf)
    {
        if (!is_array($conf['markers.'])) {
            return [];
        }
        // Parse markers and prepare their values
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($table);
        $markerValues = [];
        foreach ($conf['markers.'] as $dottedMarker => $dummy) {
            $marker = rtrim($dottedMarker, '.');
            if ($dottedMarker != $marker . '.') {
                continue;
            }
            // Parse definition
            $tempValue = isset($conf['markers.'][$dottedMarker])
                ? $this->stdWrap($conf['markers.'][$dottedMarker]['value'], $conf['markers.'][$dottedMarker])
                : $conf['markers.'][$dottedMarker]['value'];
            // Quote/escape if needed
            if (is_numeric($tempValue)) {
                if ((int)$tempValue == $tempValue) {
                    // Handle integer
                    $markerValues[$marker] = (int)$tempValue;
                } else {
                    // Handle float
                    $markerValues[$marker] = (float)$tempValue;
                }
            } elseif ($tempValue === null) {
                // It represents NULL
                $markerValues[$marker] = 'NULL';
            } elseif (!empty($conf['markers.'][$dottedMarker]['commaSeparatedList'])) {
                // See if it is really a comma separated list of values
                $explodeValues = GeneralUtility::trimExplode(',', $tempValue);
                if (count($explodeValues) > 1) {
                    // Handle each element of list separately
                    $tempArray = [];
                    foreach ($explodeValues as $listValue) {
                        if (is_numeric($listValue)) {
                            if ((int)$listValue == $listValue) {
                                $tempArray[] = (int)$listValue;
                            } else {
                                $tempArray[] = (float)$listValue;
                            }
                        } else {
                            // If quoted, remove quotes before
                            // escaping.
                            if (preg_match('/^\'([^\']*)\'$/', $listValue, $matches)) {
                                $listValue = $matches[1];
                            } elseif (preg_match('/^\\"([^\\"]*)\\"$/', $listValue, $matches)) {
                                $listValue = $matches[1];
                            }
                            $tempArray[] = $connection->quote($listValue);
                        }
                    }
                    $markerValues[$marker] = implode(',', $tempArray);
                } else {
                    // Handle remaining values as string
                    $markerValues[$marker] = $connection->quote($tempValue);
                }
            } else {
                // Handle remaining values as string
                $markerValues[$marker] = $connection->quote($tempValue);
            }
        }
        return $markerValues;
    }

    /***********************************************
     *
     * Frontend editing functions
     *
     ***********************************************/
    /**
     * Generates the "edit panels" which can be shown for a page or records on a page when the Admin Panel is enabled for a backend users surfing the frontend.
     * With the "edit panel" the user will see buttons with links to editing, moving, hiding, deleting the element
     * This function is used for the cObject EDITPANEL and the stdWrap property ".editPanel"
     *
     * @param string $content A content string containing the content related to the edit panel. For cObject "EDITPANEL" this is empty but not so for the stdWrap property. The edit panel is appended to this string and returned.
     * @param array $conf TypoScript configuration properties for the editPanel
     * @param string $currentRecord The "table:uid" of the record being shown. If empty string then $this->currentRecord is used. For new records (set by $conf['newRecordFromTable']) it's auto-generated to "[tablename]:NEW
     * @param array $dataArray Alternative data array to use. Default is $this->data
     * @return string The input content string with the editPanel appended. This function returns only an edit panel appended to the content string if a backend user is logged in (and has the correct permissions). Otherwise the content string is directly returned.
     */
    public function editPanel($content, $conf, $currentRecord = '', $dataArray = [])
    {
        if (!$this->getTypoScriptFrontendController()->isBackendUserLoggedIn()) {
            return $content;
        }
        if (!$this->getTypoScriptFrontendController()->displayEditIcons) {
            return $content;
        }

        if (!$currentRecord) {
            $currentRecord = $this->currentRecord;
        }
        if (empty($dataArray)) {
            $dataArray = $this->data;
        }

        if ($conf['newRecordFromTable']) {
            $currentRecord = $conf['newRecordFromTable'] . ':NEW';
            $conf['allow'] = 'new';
            $checkEditAccessInternals = false;
        } else {
            $checkEditAccessInternals = true;
        }
        list($table, $uid) = explode(':', $currentRecord);
        // Page ID for new records, 0 if not specified
        $newRecordPid = (int)$conf['newRecordInPid'];
        $newUid = null;
        if (!$conf['onlyCurrentPid'] || $dataArray['pid'] == $this->getTypoScriptFrontendController()->id) {
            if ($table === 'pages') {
                $newUid = $uid;
            } else {
                if ($conf['newRecordFromTable']) {
                    $newUid = $this->getTypoScriptFrontendController()->id;
                    if ($newRecordPid) {
                        $newUid = $newRecordPid;
                    }
                } else {
                    $newUid = -1 * $uid;
                }
            }
        }
        if ($table && $this->getFrontendBackendUser()->allowedToEdit($table, $dataArray, $conf, $checkEditAccessInternals) && $this->getFrontendBackendUser()->allowedToEditLanguage($table, $dataArray)) {
            $editClass = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/classes/class.frontendedit.php']['edit'];
            if ($editClass) {
                $edit = GeneralUtility::makeInstance($editClass);
                $allowedActions = $this->getFrontendBackendUser()->getAllowedEditActions($table, $conf, $dataArray['pid']);
                $content = $edit->editPanel($content, $conf, $currentRecord, $dataArray, $table, $allowedActions, $newUid, []);
            }
        }
        return $content;
    }

    /**
     * Adds an edit icon to the content string. The edit icon links to FormEngine with proper parameters for editing the table/fields of the context.
     * This implements TYPO3 context sensitive editing facilities. Only backend users will have access (if properly configured as well).
     *
     * @param string $content The content to which the edit icons should be appended
     * @param string $params The parameters defining which table and fields to edit. Syntax is [tablename]:[fieldname],[fieldname],[fieldname],... OR [fieldname],[fieldname],[fieldname],... (basically "[tablename]:" is optional, default table is the one of the "current record" used in the function). The fieldlist is sent as "&columnsOnly=" parameter to FormEngine
     * @param array $conf TypoScript properties for configuring the edit icons.
     * @param string $currentRecord The "table:uid" of the record being shown. If empty string then $this->currentRecord is used. For new records (set by $conf['newRecordFromTable']) it's auto-generated to "[tablename]:NEW
     * @param array $dataArray Alternative data array to use. Default is $this->data
     * @param string $addUrlParamStr Additional URL parameters for the link pointing to FormEngine
     * @return string The input content string, possibly with edit icons added (not necessarily in the end but just after the last string of normal content.
     */
    public function editIcons($content, $params, array $conf = [], $currentRecord = '', $dataArray = [], $addUrlParamStr = '')
    {
        if (!$this->getTypoScriptFrontendController()->isBackendUserLoggedIn()) {
            return $content;
        }
        if (!$this->getTypoScriptFrontendController()->displayFieldEditIcons) {
            return $content;
        }
        if (!$currentRecord) {
            $currentRecord = $this->currentRecord;
        }
        if (empty($dataArray)) {
            $dataArray = $this->data;
        }
        // Check incoming params:
        list($currentRecordTable, $currentRecordUID) = explode(':', $currentRecord);
        list($fieldList, $table) = array_reverse(GeneralUtility::trimExplode(':', $params, true));
        // Reverse the array because table is optional
        if (!$table) {
            $table = $currentRecordTable;
        } elseif ($table != $currentRecordTable) {
            // If the table is set as the first parameter, and does not match the table of the current record, then just return.
            return $content;
        }

        $editUid = $dataArray['_LOCALIZED_UID'] ?: $currentRecordUID;
        // Edit icons imply that the editing action is generally allowed, assuming page and content element permissions permit it.
        if (!array_key_exists('allow', $conf)) {
            $conf['allow'] = 'edit';
        }
        if ($table && $this->getFrontendBackendUser()->allowedToEdit($table, $dataArray, $conf, true) && $fieldList && $this->getFrontendBackendUser()->allowedToEditLanguage($table, $dataArray)) {
            $editClass = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/classes/class.frontendedit.php']['edit'];
            if ($editClass) {
                $edit = GeneralUtility::makeInstance($editClass);
                $content = $edit->editIcons($content, $params, $conf, $currentRecord, $dataArray, $addUrlParamStr, $table, $editUid, $fieldList);
            }
        }
        return $content;
    }

    /**
     * Returns TRUE if the input table/row would be hidden in the frontend (according nto the current time and simulate user group)
     *
     * @param string $table The table name
     * @param array $row The data record
     * @return bool
     * @internal
     * @see editPanelPreviewBorder()
     */
    public function isDisabled($table, $row)
    {
        $tsfe = $this->getTypoScriptFrontendController();
        $enablecolumns = $GLOBALS['TCA'][$table]['ctrl']['enablecolumns'];
        return $enablecolumns['disabled'] && $row[$enablecolumns['disabled']]
            || $enablecolumns['fe_group'] && $tsfe->simUserGroup && (int)$row[$enablecolumns['fe_group']] === (int)$tsfe->simUserGroup
            || $enablecolumns['starttime'] && $row[$enablecolumns['starttime']] > $GLOBALS['EXEC_TIME']
            || $enablecolumns['endtime'] && $row[$enablecolumns['endtime']] && $row[$enablecolumns['endtime']] < $GLOBALS['EXEC_TIME'];
    }

    /**
     * Get instance of FAL resource factory
     *
     * @return ResourceFactory
     */
    protected function getResourceFactory()
    {
        return ResourceFactory::getInstance();
    }

    /**
     * Wrapper function for GeneralUtility::getIndpEnv()
     *
     * @see GeneralUtility::getIndpEnv
     * @param string $key Name of the "environment variable"/"server variable" you wish to get.
     * @return string
     */
    protected function getEnvironmentVariable($key)
    {
        return GeneralUtility::getIndpEnv($key);
    }

    /**
     * Fetches content from cache
     *
     * @param array $configuration Array
     * @return string|bool FALSE on cache miss
     * @throws \TYPO3\CMS\Core\Cache\Exception\NoSuchCacheException
     */
    protected function getFromCache(array $configuration)
    {
        $content = false;

        if ($this->getTypoScriptFrontendController()->no_cache) {
            return $content;
        }
        $cacheKey = $this->calculateCacheKey($configuration);
        if (!empty($cacheKey)) {
            /** @var \TYPO3\CMS\Core\Cache\Frontend\FrontendInterface $cacheFrontend */
            $cacheFrontend = GeneralUtility::makeInstance(CacheManager::class)
                ->getCache('cache_hash');
            $content = $cacheFrontend->get($cacheKey);
        }
        return $content;
    }

    /**
     * Calculates the lifetime of a cache entry based on the given configuration
     *
     * @param array $configuration
     * @return int|null
     */
    protected function calculateCacheLifetime(array $configuration)
    {
        $lifetimeConfiguration = $configuration['lifetime'] ?? '';
        $lifetimeConfiguration = isset($configuration['lifetime.'])
            ? $this->stdWrap($lifetimeConfiguration, $configuration['lifetime.'])
            : $lifetimeConfiguration;

        $lifetime = null; // default lifetime
        if (strtolower($lifetimeConfiguration) === 'unlimited') {
            $lifetime = 0; // unlimited
        } elseif ($lifetimeConfiguration > 0) {
            $lifetime = (int)$lifetimeConfiguration; // lifetime in seconds
        }
        return $lifetime;
    }

    /**
     * Calculates the tags for a cache entry bases on the given configuration
     *
     * @param array $configuration
     * @return array
     */
    protected function calculateCacheTags(array $configuration)
    {
        $tags = $configuration['tags'] ?? '';
        $tags = isset($configuration['tags.'])
            ? $this->stdWrap($tags, $configuration['tags.'])
            : $tags;
        return empty($tags) ? [] : GeneralUtility::trimExplode(',', $tags);
    }

    /**
     * Applies stdWrap to the cache key
     *
     * @param array $configuration
     * @return string
     */
    protected function calculateCacheKey(array $configuration)
    {
        $key = $configuration['key'] ?? '';
        return isset($configuration['key.'])
            ? $this->stdWrap($key, $configuration['key.'])
            : $key;
    }

    /**
     * Returns the current BE user.
     *
     * @return \TYPO3\CMS\Backend\FrontendBackendUserAuthentication
     */
    protected function getFrontendBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }

    /**
     * @return TimeTracker
     */
    protected function getTimeTracker()
    {
        return GeneralUtility::makeInstance(TimeTracker::class);
    }

    /**
     * @return \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController
     */
    protected function getTypoScriptFrontendController()
    {
        return $this->typoScriptFrontendController ?: $GLOBALS['TSFE'];
    }

    /**
     * Support anchors without href value
     * Changes ContentObjectRenderer::typolink to render a tag without href,
     * if id or name attribute is present.
     *
     * @param string $linkText
     * @param array $conf Typolink configuration decoded as array
     * @return string Full a-Tag or just the linktext if id or name are not set.
     */
    protected function resolveAnchorLink(string $linkText, array $conf): string
    {
        $anchorTag = '<a ' . $this->getATagParams($conf) . '>';
        $aTagParams = GeneralUtility::get_tag_attributes($anchorTag);
        // If it looks like a anchor tag, render it anyway
        if (isset($aTagParams['id']) || isset($aTagParams['name'])) {
            return $anchorTag . $linkText . '</a>';
        }
        // Otherwise just return the link text
        return $linkText;
    }

    protected function shallDebug(): bool
    {
        $tsfe = $this->getTypoScriptFrontendController();
        if ($tsfe !== null && isset($tsfe->config['config']['debug'])) {
            return (bool)($tsfe->config['config']['debug']);
        }
        return !empty($GLOBALS['TYPO3_CONF_VARS']['FE']['debug']);
    }
}
