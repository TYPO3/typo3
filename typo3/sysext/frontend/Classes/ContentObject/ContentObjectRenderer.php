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

namespace TYPO3\CMS\Frontend\ContentObject;

use Doctrine\DBAL\Exception as DBALException;
use Doctrine\DBAL\Result;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LogLevel;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\LanguageAspect;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\QueryHelper;
use TYPO3\CMS\Core\Database\Query\Restriction\DocumentTypeExclusionRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\FrontendRestrictionContainer;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Html\HtmlCropper;
use TYPO3\CMS\Core\Html\HtmlParser;
use TYPO3\CMS\Core\Html\SanitizerBuilderFactory;
use TYPO3\CMS\Core\Html\SanitizerInitiator;
use TYPO3\CMS\Core\Imaging\ImageManipulation\Area;
use TYPO3\CMS\Core\Imaging\ImageManipulation\CropVariantCollection;
use TYPO3\CMS\Core\Localization\DateFormatter;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Localization\Locales;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Page\DefaultJavaScriptAssetTrait;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Resource\Exception;
use TYPO3\CMS\Core\Resource\Exception\ResourceDoesNotExistException;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Service\FlexFormService;
use TYPO3\CMS\Core\Text\TextCropper;
use TYPO3\CMS\Core\TimeTracker\TimeTracker;
use TYPO3\CMS\Core\Type\BitSet;
use TYPO3\CMS\Core\TypoScript\TypoScriptService;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\DebugUtility;
use TYPO3\CMS\Core\Utility\Exception\MissingArrayPathException;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\HttpUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\CMS\Core\Versioning\VersionState;
use TYPO3\CMS\Frontend\ContentObject\Exception\ContentRenderingException;
use TYPO3\CMS\Frontend\ContentObject\Exception\ExceptionHandlerInterface;
use TYPO3\CMS\Frontend\ContentObject\Exception\ProductionExceptionHandler;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\CMS\Frontend\Imaging\GifBuilder;
use TYPO3\CMS\Frontend\Page\PageLayoutResolver;
use TYPO3\CMS\Frontend\Resource\FilePathSanitizer;
use TYPO3\CMS\Frontend\Typolink\EmailLinkBuilder;
use TYPO3\CMS\Frontend\Typolink\LinkFactory;
use TYPO3\CMS\Frontend\Typolink\LinkResult;
use TYPO3\CMS\Frontend\Typolink\LinkResultInterface;
use TYPO3\CMS\Frontend\Typolink\UnableToLinkException;
use TYPO3\HtmlSanitizer\Builder\BuilderInterface;

/**
 * This class contains all main TypoScript features, is the backbone of TypoScript
 * rendering, include stdWrap and TypoScript content objects, usually referred to as "cObj".
 *
 * When you call your own PHP-code typically through a USER or USER_INT cObject then it is this
 * class that instantiates the object and calls the main method.
 */
class ContentObjectRenderer implements LoggerAwareInterface
{
    use LoggerAwareTrait;
    use DefaultJavaScriptAssetTrait;

    /**
     * @var ContainerInterface|null
     */
    protected $container;

    /**
     * stdWrap functions in their correct order
     *
     * @see stdWrap()
     * @var string[]
     */
    public array $stdWrapOrder = [
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
        'formattedDate' => 'formattedDateconf',
        'formattedDate.' => 'array',
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
        'debugData.' => 'array',
    ];

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
     * @var string|int
     */
    public $checkPid_badDoktypeList = PageRepository::DOKTYPE_RECYCLER;

    /**
     * This will be set by typoLink() to the url of the most recent link created.
     *
     * @var string
     * @deprecated will be removed in TYPO3 v13.0. Use $this->lastTypoLinkResult or call LinkFactory directly
     */
    public $lastTypoLinkUrl = '';

    /**
     * DO. link target.
     *
     * @var string
     * @deprecated will be removed in TYPO3 v13.0. Use $this->lastTypoLinkResult or call LinkFactory directly
     */
    public $lastTypoLinkTarget = '';

    /**
     * @var array
     * @deprecated will be removed in TYPO3 v13.0. Use $this->lastTypoLinkResult or call LinkFactory directly
     */
    public $lastTypoLinkLD = [];

    public ?LinkResultInterface $lastTypoLinkResult = null;

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
     * @var File|FileReference|Folder|string|null Current file objects (during iterations over files)
     */
    protected $currentFile;

    /**
     * Set to TRUE by doConvertToUserIntObject() if USER object wants to become USER_INT
     * @var bool
     */
    public $doConvertToUserIntObject = false;

    /**
     * Indicates current object type. Can hold one of OBJECTTYPE_ constants or FALSE.
     * The value is set and reset inside USER() function. Any time outside of
     * USER() it is FALSE.
     * @var int|bool
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
     * @var TypoScriptFrontendController|null
     */
    protected $typoScriptFrontendController;

    /**
     * Request pointer, if injected. Use getRequest() instead of reading this property directly.
     */
    private ?ServerRequestInterface $request = null;

    /**
     * Indicates that object type is USER.
     *
     * @see ContentObjectRender::$userObjectType
     */
    public const OBJECTTYPE_USER_INT = 1;
    /**
     * Indicates that object type is USER.
     *
     * @see ContentObjectRender::$userObjectType
     */
    public const OBJECTTYPE_USER = 2;

    public function __construct(TypoScriptFrontendController $typoScriptFrontendController = null, ContainerInterface $container = null)
    {
        $this->typoScriptFrontendController = $typoScriptFrontendController;
        $this->container = $container;
    }

    public function setRequest(?ServerRequestInterface $request): void
    {
        $this->request = $request;
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
        unset($vars['typoScriptFrontendController'], $vars['logger'], $vars['container'], $vars['request']);
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
        if (is_string($this->currentFile)) {
            [$objectType, $identifier] = explode(':', $this->currentFile, 2);
            try {
                if ($objectType === 'File') {
                    $this->currentFile = GeneralUtility::makeInstance(ResourceFactory::class)->retrieveFileOrFolderObject($identifier);
                } elseif ($objectType === 'FileReference') {
                    $this->currentFile = GeneralUtility::makeInstance(ResourceFactory::class)->getFileReferenceObject($identifier);
                }
            } catch (ResourceDoesNotExistException $e) {
                $this->currentFile = null;
            }
        }
        $this->logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(__CLASS__);
        $this->container = GeneralUtility::getContainer();

        // We do not derive $this->request from globals here. The request is expected to be injected
        // using setRequest(), a fallback to $GLOBALS['TYPO3_REQUEST'] is available in getRequest() for BC.
    }

    /**
     * Class constructor.
     * Well, it has to be called manually since it is not a real constructor function.
     * So after making an instance of the class, call this function and pass to it a database record and the tablename from where the record is from. That will then become the "current" record loaded into memory and accessed by the .fields property found in eg. stdWrap.
     *
     * @param array $data The record data that is rendered.
     * @param string $table The table that the data record is from.
     */
    public function start($data, $table = '', ?ServerRequestInterface $request = null)
    {
        if ($request !== null) {
            // @deprecated since TYPO3 v12, will be removed with v13: Remove third method argument and remove if() with body.
            trigger_error(
                'Do not provide $request as third argument to start(). Call setRequest() before, instead.',
                E_USER_DEPRECATED
            );
            $this->request = $request;
        }
        $this->data = $data;
        $this->table = $table;
        $this->currentRecord = $table !== ''
            ? $table . ':' . ($this->data['uid'] ?? '')
            : '';
        $this->parameters = [];
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
            'currentRecord' => $currentRecord,
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
        return $this->data[$this->currentValKey] ?? null;
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
        return implode('', $this->cObjGetSeparated($setup, $addKey));
    }

    /**
     * Rendering of a "numerical array" of cObjects from TypoScript
     * Will call ->cObjGetSingle() for each cObject found.
     *
     * @return list<string>
     */
    public function cObjGetSeparated(?array $setup, string $addKey = ''): array
    {
        if ($setup === null || $setup === []) {
            return [];
        }
        $sKeyArray = ArrayUtility::filterAndSortByNumericKeys($setup);
        $contentObjects = [];
        foreach ($sKeyArray as $theKey) {
            $theValue = $setup[$theKey];
            if ((int)$theKey && !str_contains($theKey, '.')) {
                $conf = $setup[$theKey . '.'] ?? [];
                $contentObjects[] = $this->cObjGetSingle($theValue, $conf, $addKey . $theKey);
            }
        }
        return $contentObjects;
    }

    /**
     * Renders a content object
     *
     * @param string $name The content object name, eg. "TEXT" or "USER" or "IMAGE"
     * @param array $conf The array with TypoScript properties for the content object
     * @param string $TSkey A string label used for the internal debugging tracking.
     * @return string cObject output
     * @throws \UnexpectedValueException
     */
    public function cObjGetSingle(string $name, $conf, $TSkey = '__')
    {
        $timeTracker = $this->getTimeTracker();
        $name = trim($name);
        if ($timeTracker->LR) {
            $timeTracker->push($TSkey, $name);
        }
        $fullConfigArray = [
            'tempKey' => $name,
            'tempKey.' => is_array($conf) ? $conf : [],
        ];
        // Resolve '=<' operator if needed
        $fullConfigArray = $this->mergeTSRef($fullConfigArray, 'tempKey');
        $contentObject = $this->getContentObject($fullConfigArray['tempKey']);
        $content = '';
        if ($contentObject) {
            $content = $this->render($contentObject, $fullConfigArray['tempKey.']);
        }
        if ($timeTracker->LR) {
            $timeTracker->pull($content);
        }
        return $content;
    }

    /**
     * Returns a new content object of type $name.
     *
     * @param string $name
     * @throws ContentRenderingException
     */
    public function getContentObject($name): ?AbstractContentObject
    {
        $contentObjectFactory = $this->container
            ? $this->container->get(ContentObjectFactory::class)
            : GeneralUtility::makeInstance(ContentObjectFactory::class);
        return $contentObjectFactory->getContentObject($name, $this->getRequest(), $this);
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
     */
    public function render(AbstractContentObject $contentObject, $configuration = []): string
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
                $cacheFrontend = GeneralUtility::makeInstance(CacheManager::class)->getCache('hash');
                $tags = $this->calculateCacheTags($cacheConfiguration);
                $lifetime = $this->calculateCacheLifetime($cacheConfiguration);
                $cachedData = [
                    'content' => $content,
                    'cacheTags' => $tags,
                ];
                $cacheFrontend->set($key, $cachedData, $tags, $lifetime);
                $this->getTypoScriptFrontendController()->addCacheTags($tags);
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
            $exceptionHandler = GeneralUtility::makeInstance($exceptionHandlerClassName);
            if (!$exceptionHandler instanceof ExceptionHandlerInterface) {
                throw new ContentRenderingException('An exception handler was configured but the class does not exist or does not implement the ExceptionHandlerInterface', 1403653369);
            }
            $exceptionHandler->setConfiguration($this->mergeExceptionHandlerConfiguration($configuration));
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
            if (Environment::getContext()->isProduction()) {
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
            $this->getTimeTracker()->setTSlogMessage(self::class . '::convertToUserIntObject() is called in the wrong context or for the wrong object type', LogLevel::WARNING);
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
        // todo: phpstan states that $pidConf always exists and is not nullable. At the moment, this is a false positive
        //       as null can be passed into this method via $pidConf. As soon as more strict types are used, this isset
        //       check must be replaced with a more appropriate check like empty or count.
        $pidList = isset($pidConf) ? trim((string)$this->stdWrap($pidList, $pidConf)) : trim($pidList);
        if ($pidList === '') {
            $pidList = 'this';
        }
        $tsfe = $this->getTypoScriptFrontendController();
        $listArr = null;
        if (trim($pidList)) {
            $listArr = GeneralUtility::intExplode(',', str_replace('this', (string)$tsfe->contentPid, $pidList));
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
     * Wraps the input string in link-tags that opens the image in a new window.
     *
     * @param string $string String to wrap, probably an <img> tag
     * @param string|File|FileReference $imageFile The original image file
     * @param array $conf TypoScript properties for the "imageLinkWrap" function
     * @return string The input string, $string, wrapped as configured.
     * @internal This method should be used within TYPO3 Core only
     */
    public function imageLinkWrap($string, $imageFile, $conf)
    {
        $string = (string)$string;
        $enable = $this->stdWrapValue('enable', $conf ?? []);
        if (!$enable) {
            return $string;
        }
        $content = (string)$this->typoLink($string, $conf['typolink.'] ?? []);
        if (isset($conf['file.']) && is_scalar($imageFile)) {
            $imageFile = $this->stdWrap((string)$imageFile, $conf['file.']);
        }

        if ($imageFile instanceof File) {
            $file = $imageFile;
        } elseif ($imageFile instanceof FileReference) {
            $file = $imageFile->getOriginalFile();
        } else {
            if (MathUtility::canBeInterpretedAsInteger($imageFile)) {
                $file = GeneralUtility::makeInstance(ResourceFactory::class)->getFileObject((int)$imageFile);
            } else {
                $file = GeneralUtility::makeInstance(ResourceFactory::class)->getFileObjectFromCombinedIdentifier($imageFile);
            }
        }

        // Create imageFileLink if not created with typolink
        if ($content === $string && $file !== null) {
            $parameterNames = ['width', 'height', 'effects', 'bodyTag', 'title', 'wrap', 'crop'];
            $parameters = [];
            $sample = $this->stdWrapValue('sample', $conf ?? []);
            if ($sample) {
                $parameters['sample'] = 1;
            }
            foreach ($parameterNames as $parameterName) {
                if (isset($conf[$parameterName . '.'])) {
                    $conf[$parameterName] = $this->stdWrap($conf[$parameterName] ?? '', $conf[$parameterName . '.'] ?? []);
                }
                if (isset($conf[$parameterName]) && $conf[$parameterName]) {
                    $parameters[$parameterName] = $conf[$parameterName];
                }
            }
            $parametersEncoded = base64_encode((string)json_encode($parameters));
            $hmac = GeneralUtility::hmac(implode('|', [$file->getUid(), $parametersEncoded]));
            $params = '&md5=' . $hmac;
            foreach (str_split($parametersEncoded, 64) as $index => $chunk) {
                $params .= '&parameters' . rawurlencode('[') . $index . rawurlencode(']') . '=' . rawurlencode($chunk);
            }
            $url = $this->getTypoScriptFrontendController()->absRefPrefix . 'index.php?eID=tx_cms_showpic&file=' . $file->getUid() . $params;
            $directImageLink = $this->stdWrapValue('directImageLink', $conf ?? []);
            if ($directImageLink) {
                $imgResourceConf = [
                    'file' => $imageFile,
                    'file.' => $conf,
                ];
                $url = $this->cObjGetSingle('IMG_RESOURCE', $imgResourceConf);
                if (!$url) {
                    // If no imagemagick / gm is available
                    $url = $imageFile;
                }
            }
            $target = (string)$this->stdWrapValue('target', $conf ?? []);
            if ($target === '') {
                $target = 'thePicture';
            }
            $a1 = '';
            $a2 = '';
            $conf['JSwindow'] = $this->stdWrapValue('JSwindow', $conf ?? []);
            if ($conf['JSwindow']) {
                $altUrl = $this->stdWrapValue('altUrl', $conf['JSwindow.'] ?? []);
                if ($altUrl) {
                    $url = $altUrl . (($conf['JSwindow.']['altUrl_noDefaultParams'] ?? false) ? '' : '?file=' . rawurlencode((string)$imageFile) . $params);
                }

                $processedFile = $file->process(ProcessedFile::CONTEXT_IMAGECROPSCALEMASK, $conf);
                $JSwindowExpand = $this->stdWrapValue('expand', $conf['JSwindow.'] ?? []);
                $offset = GeneralUtility::intExplode(',', $JSwindowExpand . ',');
                $newWindow = $this->stdWrapValue('newWindow', $conf['JSwindow.'] ?? []);
                $params = [
                    'width' => ($processedFile->getProperty('width') + $offset[0]),
                    'height' => ($processedFile->getProperty('height') + $offset[1]),
                    'status' => '0',
                    'menubar' => '0',
                ];
                // params override existing parameters from above, or add more
                $windowParams = (string)$this->stdWrapValue('params', $conf['JSwindow.'] ?? []);
                $windowParams = explode(',', $windowParams);
                foreach ($windowParams as $windowParam) {
                    $windowParamParts = explode('=', $windowParam);
                    $paramKey = $windowParamParts[0];
                    $paramValue = $windowParamParts[1] ?? null;

                    if ($paramKey === '') {
                        continue;
                    }

                    if ($paramValue !== '') {
                        $params[$paramKey] = $paramValue;
                    } else {
                        unset($params[$paramKey]);
                    }
                }
                $paramString = '';
                foreach ($params as $paramKey => $paramValue) {
                    $paramString .= htmlspecialchars((string)$paramKey) . '=' . htmlspecialchars((string)$paramValue) . ',';
                }

                $attrs = [
                    'href' => (string)$url,
                    'data-window-url' => $this->getTypoScriptFrontendController()->baseUrlWrap($url, true),
                    'data-window-target' => $newWindow ? md5((string)$url) : 'thePicture',
                    'data-window-features' => rtrim($paramString, ','),
                ];
                if ($target !== '') {
                    $attrs['target'] = $target;
                }

                $a1 = sprintf(
                    '<a %s%s>',
                    GeneralUtility::implodeAttributes($attrs, true),
                    trim($this->getTypoScriptFrontendController()->config['config']['ATagParams'] ?? '') ? ' ' . trim($this->getTypoScriptFrontendController()->config['config']['ATagParams']) : ''
                );
                $a2 = '</a>';
                $this->addDefaultFrontendJavaScript();
            } else {
                $conf['linkParams.']['directImageLink'] = (bool)($conf['directImageLink'] ?? false);
                $conf['linkParams.']['parameter'] = $url;
                $string = (string)$this->typoLink($string, $conf['linkParams.']);
            }
            if (isset($conf['stdWrap.'])) {
                $string = (string)$this->stdWrap($string, $conf['stdWrap.']);
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
        if ($tstamp > (int)($tsfe->register['SYS_LASTCHANGED'] ?? 0)) {
            $tsfe->register['SYS_LASTCHANGED'] = $tstamp;
        }
    }

    /**
     * An abstraction method to add parameters to an A tag.
     * Uses the ATagParams property, also includes the global TypoScript config.ATagParams
     *
     * @param array $conf TypoScript configuration properties
     * @return string String containing the parameters to the A tag (if non empty, with a leading space)
     * @see typoLink()
     * @deprecated will be removed in TYPO3 v13.0. Use LinkFactory functionality directly, available since TYPO3 v12.0.
     */
    public function getATagParams($conf)
    {
        trigger_error('$cObj->getATagParams is deprecated in favor of the unified LinkFactory API for generating links. This method will be removed in TYPO3 v13.0.', E_USER_DEPRECATED);
        $aTagParams = $this->stdWrapValue('ATagParams', $conf ?? []);
        // Add the global config.ATagParams
        $globalParams = $this->getTypoScriptFrontendController() ? trim($this->getTypoScriptFrontendController()->config['config']['ATagParams'] ?? '') : '';
        $aTagParams = ' ' . trim($globalParams . ' ' . $aTagParams);
        // Extend params
        $aTagParams = trim($aTagParams);
        if (!empty($aTagParams)) {
            $aTagParams = ' ' . $aTagParams;
        }

        return $aTagParams;
    }

    /***********************************************
     *
     * HTML template processing functions
     *
     ***********************************************/

    /**
     * Sets the current file object during iterations over files.
     *
     * @param File|FileReference|Folder|string|null $fileObject The file object.
     */
    public function setCurrentFile($fileObject)
    {
        $this->currentFile = $fileObject;
    }

    /**
     * Gets the current file object during iterations over files.
     *
     * @return File|FileReference|Folder|string|null The current file object.
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
     * @param string $content Input value undergoing processing in this function. Possibly substituted by other values fetched from another source.
     * @param array $conf TypoScript "stdWrap properties".
     * @return string|null The processed input value
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
            $conf['cache.']['key'] = $this->stdWrapValue('key', $conf['cache.'] ?? []);
            $conf['cache.']['tags'] = $this->stdWrapValue('tags', $conf['cache.'] ?? []);
            $conf['cache.']['lifetime'] = $this->stdWrapValue('lifetime', $conf['cache.'] ?? []);
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
                $functionType = $this->stdWrapOrder[$functionName] ?? '';
                // If there is any code on the next level, check if it contains "official" stdWrap functions
                // if yes, execute them first - will make each function stdWrap aware
                // so additional stdWrap calls within the functions can be removed, since the result will be the same
                if (!empty($conf[$functionProperties]) && !GeneralUtility::inList($stdWrapDisabledFunctionTypes, $functionType)) {
                    if (array_intersect_key($this->stdWrapOrder, $conf[$functionProperties])) {
                        // Check if there's already content available before processing
                        // any ifEmpty or ifBlank stdWrap properties
                        if (($functionName === 'ifBlank' && $content !== '') ||
                            ($functionName === 'ifEmpty' && !empty(trim((string)$content)))) {
                            continue;
                        }

                        $conf[$functionName] = $this->stdWrap($conf[$functionName] ?? '', $conf[$functionProperties]);
                    }
                }
                // Check if key is still containing something, since it might have been changed by next level stdWrap before
                if ((isset($conf[$functionName]) || ($conf[$functionProperties] ?? null))
                    && ($functionType !== 'boolean' || ($conf[$functionName] ?? null))
                ) {
                    // Get just that part of $conf that is needed for the particular function
                    $singleConf = [
                        $functionName => $conf[$functionName] ?? null,
                        $functionProperties => $conf[$functionProperties] ?? null,
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
                } elseif ($functionType === 'boolean' && !($conf[$functionName] ?? null)) {
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
     * @param string|int|bool|null $defaultValue Optional default value.
     * @return string|int|bool|null Value of the config variable
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
        // The string "0" should be returned.
        return $stdWrapped !== '' ? $stdWrapped : $defaultValue;
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
        $tags = (string)$this->stdWrapValue('addPageCacheTags', $conf ?? []);
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
        $siteLanguage = $this->getTypoScriptFrontendController()->getLanguage();
        $currentLanguageCode = $siteLanguage->getTypo3Language();
        if (!$currentLanguageCode) {
            return $content;
        }
        if (isset($conf['lang.'][$currentLanguageCode])) {
            $content = $conf['lang.'][$currentLanguageCode];
        } else {
            // @todo: use the Locale object and its dependencies in TYPO3 v13
            // Check language dependencies
            $locales = GeneralUtility::makeInstance(Locales::class);
            foreach ($locales->getLocaleDependencies($currentLanguageCode) as $languageCode) {
                if (isset($conf['lang.'][$languageCode])) {
                    $content = $conf['lang.'][$languageCode];
                    break;
                }
            }
        }
        return $content;
    }

    /**
     * Gets content from different sources based on getText functions.
     *
     * @param string $_ Unused
     * @param array $conf stdWrap properties for data.
     * @return string The processed input value
     */
    public function stdWrap_data($_ = '', $conf = [])
    {
        return $this->getData($conf['data'], $this->data);
    }

    /**
     * field
     * Gets content from a DB field
     *
     * @param string $content Input value undergoing processing in this function.
     * @param array $conf stdWrap properties for field.
     * @return string|null The processed input value
     */
    public function stdWrap_field($content = '', $conf = [])
    {
        return $this->getFieldVal($conf['field']);
    }

    /**
     * current
     * Gets content that has been previously set as 'current'
     * Can be set via setContentToCurrent or setCurrent or will be set automatically i.e. inside the split function
     *
     * @param string $content Input value undergoing processing in this function.
     * @param array $conf stdWrap properties for current.
     * @return string The processed input value
     */
    public function stdWrap_current($content = '', $conf = [])
    {
        return $this->getCurrentVal();
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
     * preUserFunc
     * Will execute a user public function before the content will be modified by any other stdWrap function
     *
     * @param string $content Input value undergoing processing in this function.
     * @param array $conf stdWrap properties for preUserFunc.
     * @return string The processed input value
     */
    public function stdWrap_preUserFunc($content = '', $conf = [])
    {
        return $this->callUserFunction($conf['preUserFunc'], $conf['preUserFunc.'] ?? [], $content);
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
        return $this->listNum($content, $conf['preIfEmptyListNum'] ?? '0', $conf['preIfEmptyListNum.']['splitChar'] ?? ',');
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
        if (empty(trim((string)$content))) {
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
        if (trim((string)$content) === '') {
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
        return $this->listNum($content, $conf['listNum'] ?? '0', $conf['listNum.']['splitChar'] ?? ',');
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
        return trim((string)$content);
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
        $length = (int)$this->stdWrapValue('length', $conf['strPad.'] ?? [], 0);
        // Padding with space is PHP-default
        $padWith = (string)$this->stdWrapValue('padWith', $conf['strPad.'] ?? [], ' ');
        // Padding on the right side is PHP-default
        $padType = STR_PAD_RIGHT;

        if (!empty($conf['strPad.']['type'])) {
            $type = (string)$this->stdWrapValue('type', $conf['strPad.'] ?? []);
            if (strtolower($type) === 'left') {
                $padType = STR_PAD_LEFT;
            } elseif (strtolower($type) === 'both') {
                $padType = STR_PAD_BOTH;
            }
        }
        return StringUtility::multibyteStringPad($content, $length, $padWith, $padType);
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
     * @see https://php.net/manual/en/function.chr.php
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
     * @link https://php.net/manual/de/function.hash-algos.php for a list of supported hash algorithms
     */
    public function stdWrap_hash($content = '', array $conf = [])
    {
        $algorithm = (string)$this->stdWrapValue('hash', $conf ?? []);
        if (in_array($algorithm, hash_algos())) {
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
        return $this->numberFormat((float)$content, $conf['numberFormat.'] ?? []);
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
            ? (new DateFormatter())->strftime($conf['strftime'] ?? '', $content, null, true)
            : (new DateFormatter())->strftime($conf['strftime'] ?? '', $content);
        if (!empty($conf['strftime.']['charset'])) {
            $output = mb_convert_encoding((string)$content, 'utf-8', trim(strtolower($conf['strftime.']['charset'])));
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
     * php-intl dateformatted
     * Will return a timestamp based on configuration given according to PHP-intl DateFormatter->format()
     * see https://unicode-org.github.io/icu/userguide/format_parse/datetime/#datetime-format-syntax
     *
     * @param string $content Input value undergoing processing in this function.
     * @param array $conf stdWrap properties for formattedDate.
     * @return string The processed input value
     */
    public function stdWrap_formattedDate(string $content, array $conf): string
    {
        $pattern = $conf['formattedDate'] ?? 'LONG';
        $locale = $conf['formattedDate.']['locale'] ?? $this->getTypoScriptFrontendController()->getLanguage()->getLocale();

        if ($content === '' || $content === '0') {
            $content = $this->getTypoScriptFrontendController()->getContext()->getAspect('date')->getDateTime();
        } else {
            // format this to a timestamp now
            $content = strtotime((MathUtility::canBeInterpretedAsInteger($content) ? '@' : '') . $content);
            if ($content === false) {
                $content = $this->getTypoScriptFrontendController()->getContext()->getAspect('date')->getDateTime();
            }
        }
        return (new DateFormatter())->format($content, $pattern, $locale);
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
        return GeneralUtility::formatSize((int)$content, $conf['bytes.']['labels'] ?? '', $conf['bytes.']['base'] ?? 0);
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
     * Completely removes HTML tags from content
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
     * @param array $conf stdWrap properties for htmlSpecialChars.
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
        return preg_replace('/\R{1,2}[\t\x20]*\R{1,2}/', $conf['doubleBrTag'] ?? '', $content);
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
        $docType = GeneralUtility::makeInstance(PageRenderer::class)->getDocType();
        return nl2br($content, $docType->isXmlCompliant());
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
        return str_replace(LF, (string)($conf['brTag'] ?? ''), $content);
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
        return $this->typoLink((string)$content, $conf['typolink.'] ?? []);
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
            $content = (string)$this->stdWrap($content, $conf['orderedStdWrap.'][$key . '.'] ?? null);
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
        return $this->callUserFunction($conf['postUserFunc'], $conf['postUserFunc.'] ?? [], $content);
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
            'cObj' => serialize($this),
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
        $cacheFrontend = GeneralUtility::makeInstance(CacheManager::class)->getCache('hash');
        $tags = $this->calculateCacheTags($conf['cache.']);
        $lifetime = $this->calculateCacheLifetime($conf['cache.']);
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_content.php']['stdWrap_cacheStore'] ?? [] as $_funcRef) {
            $params = [
                'key' => $key,
                'content' => $content,
                'lifetime' => $lifetime,
                'tags' => $tags,
            ];
            $ref = $this; // introduced for phpstan to not lose type information when passing $this into callUserFunction
            GeneralUtility::callUserFunction($_funcRef, $params, $ref);
        }
        $cachedData = [
            'content' => $content,
            'cacheTags' => $tags,
        ];
        $cacheFrontend->set($key, $cachedData, $tags, $lifetime);
        $this->getTypoScriptFrontendController()->addCacheTags($tags);
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

        return (int)$statement->fetchOne();
    }

    /**
     * Explode a string by the $delimeter value and return the value of index $listNum
     *
     * @param string $content String to explode
     * @param string $listNum Index-number | 'last' | 'rand' | arithmetic expression. You can place the word "last" in it and it will be substituted with the pointer to the last value. You can use math operators like "+-/*" (passed to calc())
     * @param string $delimeter Either a string used to explode the content string or an integer value (as string) which will then be changed into a character, eg. "10" for a linebreak char.
     * @return string
     */
    public function listNum($content, $listNum, $delimeter = ',')
    {
        $delimeter = $delimeter ?: ',';
        if (MathUtility::canBeInterpretedAsInteger($delimeter)) {
            $delimeter = chr((int)$delimeter);
        }
        $temp = explode($delimeter, $content);
        if (empty($temp)) {
            return '';
        }
        $last = '' . (count($temp) - 1);
        // Take a random item if requested
        if ($listNum === 'rand') {
            $listNum = (string)random_int(0, count($temp) - 1);
        }
        $index = $this->calc(str_ireplace('last', $last, $listNum));
        return $temp[$index] ?? '';
    }

    /**
     * Compares values together based on the settings in the input TypoScript array and returns the comparison result.
     * Implements the "if" function in TYPO3 TypoScript
     *
     * @param array $conf TypoScript properties defining what to compare
     */
    public function checkIf($conf): bool
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
            $isTrue = trim((string)$this->stdWrapValue('isTrue', $conf));
            if (!$isTrue) {
                $flag = false;
            }
        }
        if (isset($conf['isFalse']) || isset($conf['isFalse.'])) {
            $isFalse = trim((string)$this->stdWrapValue('isFalse', $conf));
            if ($isFalse) {
                $flag = false;
            }
        }
        if (isset($conf['isPositive']) || isset($conf['isPositive.'])) {
            $number = $this->calc((string)$this->stdWrapValue('isPositive', $conf));
            if ($number < 1) {
                $flag = false;
            }
        }
        if ($flag) {
            $comparisonValue = trim((string)$this->stdWrapValue('value', $conf));
            if (isset($conf['isGreaterThan']) || isset($conf['isGreaterThan.'])) {
                $number = trim((string)$this->stdWrapValue('isGreaterThan', $conf));
                if ($number <= $comparisonValue) {
                    $flag = false;
                }
            }
            if (isset($conf['isLessThan']) || isset($conf['isLessThan.'])) {
                $number = trim((string)$this->stdWrapValue('isLessThan', $conf));
                if ($number >= $comparisonValue) {
                    $flag = false;
                }
            }
            if (isset($conf['equals']) || isset($conf['equals.'])) {
                $number = trim((string)$this->stdWrapValue('equals', $conf));
                if ($number != $comparisonValue) {
                    $flag = false;
                }
            }
            if (isset($conf['contains']) || isset($conf['contains.'])) {
                $needle = trim((string)$this->stdWrapValue('contains', $conf));
                if (!str_contains($comparisonValue, $needle)) {
                    $flag = false;
                }
            }
            if (isset($conf['startsWith']) || isset($conf['startsWith.'])) {
                $needle = trim((string)$this->stdWrapValue('startsWith', $conf));
                if (!str_starts_with($comparisonValue, $needle)) {
                    $flag = false;
                }
            }
            if (isset($conf['endsWith']) || isset($conf['endsWith.'])) {
                $needle = trim((string)$this->stdWrapValue('endsWith', $conf));
                if (!str_ends_with($comparisonValue, $needle)) {
                    $flag = false;
                }
            }
            if (isset($conf['isInList']) || isset($conf['isInList.'])) {
                $singleValueWhichNeedsToBeInList = trim((string)$this->stdWrapValue('isInList', $conf));
                if (!GeneralUtility::inList($comparisonValue, $singleValueWhichNeedsToBeInList)) {
                    $flag = false;
                }
            }
            if (isset($conf['bitAnd']) || isset($conf['bitAnd.'])) {
                $number = (int)trim((string)$this->stdWrapValue('bitAnd', $conf));
                if ((new BitSet($number))->get($comparisonValue) === false) {
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
     * Passes the input value, $theValue, to an instance of "\TYPO3\CMS\Core\Html\HtmlParser"
     * together with the TypoScript options which are first converted from a TS style array
     * to a set of arrays with options for the \TYPO3\CMS\Core\Html\HtmlParser class.
     *
     * @param string $theValue The value to parse by the class \TYPO3\CMS\Core\Html\HtmlParser
     * @param array $conf TypoScript properties for the parser. See link.
     * @return string Return value.
     * @see stdWrap()
     * @see \TYPO3\CMS\Core\Html\HtmlParser::HTMLparserConfig()
     * @see \TYPO3\CMS\Core\Html\HtmlParser::HTMLcleaner()
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
     * @see insertData()
     * @see stdWrap()
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
     * @see getData()
     * @see stdWrap()
     * @see dataWrap()
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
     * @see stdWrap()
     * @internal
     */
    public function crop($content, $options)
    {
        $options = explode('|', $options);
        $numberOfChars = (int)$options[0];
        $replacementForEllipsis = trim($options[1] ?? '');
        $cropToSpace = trim($options[2] ?? '') === '1';
        return GeneralUtility::makeInstance(TextCropper::class)
            ->crop(
                content: $content,
                numberOfChars: $numberOfChars,
                replacementForEllipsis: $replacementForEllipsis,
                cropToSpace: $cropToSpace
            );
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
     * @see stdWrap()
     * @return string The processed input value.
     * @internal
     */
    public function cropHTML(string $content, string $options): string
    {
        $options = explode('|', $options);
        $numberOfChars = (int)$options[0];
        $replacementForEllipsis = trim($options[1] ?? '');
        $cropToSpace = trim($options[2] ?? '') === '1';
        return GeneralUtility::makeInstance(HtmlCropper::class)
            ->crop(
                content: $content,
                numberOfChars: $numberOfChars,
                replacementForEllipsis: $replacementForEllipsis,
                cropToSpace: $cropToSpace
            );
    }

    /**
     * Performs basic mathematical evaluation of the input string. Does NOT take parenthesis and operator precedence into account! (for that, see \TYPO3\CMS\Core\Utility\MathUtility::calculateWithPriorityToAdditionAndSubtraction())
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
     * Implements the "split" property of stdWrap; Splits a string based on a token (given in TypoScript properties), sets the "current" value to each part and then renders a content object pointer to by a number.
     * In classic TypoScript (like 'content (default)'/'styles.content (default)') this is used to render tables, splitting rows and cells by tokens and putting them together again wrapped in <td> tags etc.
     * Implements the "optionSplit" processing of the TypoScript options for each splitted value to parse.
     *
     * @param string $value The string value to explode by $conf[token] and process each part
     * @param array $conf TypoScript properties for "split
     * @return string Compiled result
     * @internal
     * @see stdWrap()
     * @see \TYPO3\CMS\Frontend\ContentObject\Menu\AbstractMenuContentObject::processItemStates()
     */
    public function splitObj($value, $conf)
    {
        $conf['token'] = isset($conf['token.']) ? $this->stdWrap($conf['token'] ?? '', $conf['token.']) : $conf['token'] ?? '';
        if ($conf['token'] === '') {
            return $value;
        }
        $valArr = explode($conf['token'], $value);

        // return value directly by returnKey. No further processing
        if (!empty($valArr) && (MathUtility::canBeInterpretedAsInteger($conf['returnKey'] ?? null) || ($conf['returnKey.'] ?? false))) {
            $key = (int)$this->stdWrapValue('returnKey', $conf ?? []);
            return $valArr[$key] ?? '';
        }

        // return the amount of elements. No further processing
        if (!empty($valArr) && (($conf['returnCount'] ?? false) || ($conf['returnCount.'] ?? false))) {
            $returnCount = (bool)$this->stdWrapValue('returnCount', $conf ?? []);
            return $returnCount ? count($valArr) : 0;
        }

        // calculate splitCount
        $splitCount = count($valArr);
        $max = (int)$this->stdWrapValue('max', $conf ?? []);
        if ($max && $splitCount > $max) {
            $splitCount = $max;
        }
        $min = (int)$this->stdWrapValue('min', $conf ?? []);
        if ($min && $splitCount < $min) {
            $splitCount = $min;
        }
        $wrap = (string)$this->stdWrapValue('wrap', $conf ?? []);
        $cObjNumSplitConf = isset($conf['cObjNum.']) ? $this->stdWrap($conf['cObjNum'] ?? '', $conf['cObjNum.'] ?? []) : (string)($conf['cObjNum'] ?? '');
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
            if ($splitArr[$a]['cObjNum'] ?? false) {
                $objName = (int)$splitArr[$a]['cObjNum'];
                $value = (string)(isset($conf[$objName . '.'])
                    ? $this->stdWrap($this->cObjGet($conf[$objName . '.'], $objName . '.'), $conf[$objName . '.'])
                    : '');
            }
            $wrap = (string)$this->stdWrapValue('wrap', $splitArr[$a] ?? []);
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
            // Checks whether we have a valid action and a numeric key ending with a dot ("10.")
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
            $search = (string)$this->stdWrapValue('search', $configuration ?? []);
            $replace = (string)$this->stdWrapValue('replace', $configuration, null);

            // Determines whether regular expression shall be used
            $useRegularExpression = (bool)$this->stdWrapValue('useRegExp', $configuration, false);

            // Determines whether replace-pattern uses option-split
            $useOptionSplitReplace = (bool)$this->stdWrapValue('useOptionSplitReplace', $configuration, false);

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

                    $replaceCallback = static function ($match) use ($replaceArray, $search, &$replaceCount) {
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

                $replaceCallback = static function () use ($replaceArray, &$replaceCount) {
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
        $decimals = (int)$this->stdWrapValue('decimals', $conf, 0);
        $type = $this->stdWrapValue('roundType', $conf ?? []);
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
                $content = round($floatVal, $decimals);
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
        $decimals = (int)$this->stdWrapValue('decimals', $conf, 0);
        $dec_point = (string)$this->stdWrapValue('dec_point', $conf, '.');
        $thousands_sep = (string)$this->stdWrapValue('thousands_sep', $conf, ',');
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
     * This means that before the input string is actually "parsed" it will be split into the parts configured to BE parsed
     * (while other parts/blocks should NOT be parsed).
     * Therefore, the actual processing of the parseFunc properties goes on in ->parseFuncInternal()
     *
     * @param string $theValue The value to process.
     * @param non-empty-array<string, mixed>|null $conf TypoScript configuration for parseFunc
     * @param non-empty-string|null $ref Reference to get configuration from. Eg. "< lib.parseFunc" which means that the configuration of the object path "lib.parseFunc" will be retrieved and MERGED with what is in $conf!
     * @return string The processed value
     */
    public function parseFunc($theValue, ?array $conf, ?string $ref = null)
    {
        // Fetch / merge reference, if any
        if (!empty($ref)) {
            $temp_conf = [
                'parseFunc' => $ref,
                'parseFunc.' => $conf ?? [],
            ];
            $temp_conf = $this->mergeTSRef($temp_conf, 'parseFunc');
            $conf = $temp_conf['parseFunc.'];
        }
        if (empty($conf)) {
            // `parseFunc` relies on configuration, either given in `$conf` or resolved from `$ref`
            throw new \LogicException('Invoked ContentObjectRenderer::parseFunc without any configuration', 1641989097);
        }
        // Handle HTML sanitizer invocation
        $conf['htmlSanitize'] = (bool)($conf['htmlSanitize'] ?? true);
        // Process:
        if ((string)($conf['externalBlocks'] ?? '') === '') {
            $result = $this->parseFuncInternal($theValue, $conf);
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
                $cfg = $conf['externalBlocks.'][$tagName . '.'] ?? [];
                if ($cfg === []) {
                    continue;
                }
                if (($cfg['stripNLprev'] ?? false) || ($cfg['stripNL'] ?? false)) {
                    $parts[$k - 1] = preg_replace('/' . CR . '?' . LF . '[ ]*$/', '', $parts[$k - 1]);
                }
                if (($cfg['stripNLnext'] ?? false) || ($cfg['stripNL'] ?? false)) {
                    if (!isset($parts[$k + 1])) {
                        $parts[$k + 1] = '';
                    }
                    $parts[$k + 1] = preg_replace('/^[ ]*' . CR . '?' . LF . '/', '', $parts[$k + 1]);
                }
            }
        }
        foreach ($parts as $k => $v) {
            if ($k % 2) {
                $tag = $htmlParser->getFirstTag($v);
                $tagName = strtolower($htmlParser->getFirstTagName($v));
                $cfg = $conf['externalBlocks.'][$tagName . '.'] ?? [];
                if ($cfg === []) {
                    continue;
                }
                if ($cfg['callRecursive'] ?? false) {
                    $parts[$k] = $this->parseFunc($htmlParser->removeFirstAndLastTag($v), $conf);
                    if (!($cfg['callRecursive.']['dontWrapSelf'] ?? false)) {
                        if ($cfg['callRecursive.']['alternativeWrap'] ?? false) {
                            $parts[$k] = $this->wrap($parts[$k], $cfg['callRecursive.']['alternativeWrap']);
                        } else {
                            if (is_array($cfg['callRecursive.']['tagStdWrap.'] ?? false)) {
                                $tag = $this->stdWrap($tag, $cfg['callRecursive.']['tagStdWrap.']);
                            }
                            $parts[$k] = $tag . $parts[$k] . '</' . $tagName . '>';
                        }
                    }
                } elseif ($cfg['HTMLtableCells'] ?? false) {
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
                                    if (($cfg['HTMLtableCells.'][$cc . '.']['callRecursive'] ?? false)
                                        || (!isset($cfg['HTMLtableCells.'][$cc . '.']['callRecursive']) && ($cfg['HTMLtableCells.']['default.']['callRecursive'] ?? false))) {
                                        if ($cfg['HTMLtableCells.']['addChr10BetweenParagraphs'] ?? false) {
                                            $colParts[$kkk] = str_replace(
                                                '</p><p>',
                                                '</p>' . LF . '<p>',
                                                $colParts[$kkk]
                                            );
                                        }
                                        $colParts[$kkk] = $this->parseFunc($colParts[$kkk], $conf);
                                    }
                                    $tagStdWrap = is_array($cfg['HTMLtableCells.'][$cc . '.']['tagStdWrap.'] ?? false)
                                        ? $cfg['HTMLtableCells.'][$cc . '.']['tagStdWrap.']
                                        : ($cfg['HTMLtableCells.']['default.']['tagStdWrap.'] ?? null);
                                    if (is_array($tagStdWrap)) {
                                        $tag = $this->stdWrap($tag, $tagStdWrap);
                                    }
                                    $stdWrap = is_array($cfg['HTMLtableCells.'][$cc . '.']['stdWrap.'] ?? false)
                                        ? $cfg['HTMLtableCells.'][$cc . '.']['stdWrap.']
                                        : ($cfg['HTMLtableCells.']['default.']['stdWrap.'] ?? null);
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
                if (is_array($cfg['stdWrap.'] ?? false)) {
                    $parts[$k] = $this->stdWrap($parts[$k], $cfg['stdWrap.']);
                }
            } else {
                $parts[$k] = $this->parseFuncInternal($parts[$k], $conf);
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
     */
    protected function parseFuncInternal($theValue, $conf)
    {
        if (!empty($conf['if.']) && !$this->checkIf($conf['if.'])) {
            return $theValue;
        }
        // Indicates that the data is from within a tag.
        $inside = false;
        // Pointer to the total string position
        $pointer = 0;
        // Loaded with the current typo-tag if any.
        $currentTag = null;
        $stripNL = 0;
        $contentAccum = [];
        $contentAccumP = 0;
        $allowTags = strtolower(str_replace(' ', '', $conf['allowTags'] ?? ''));
        $denyTags = strtolower(str_replace(' ', '', $conf['denyTags'] ?? ''));
        $totalLen = strlen($theValue);
        do {
            if (!$inside) {
                if ($currentTag === null) {
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
                    $len = $this->getContentLengthOfCurrentTag($theValue, $pointer, (string)$currentTag[0]);
                }
                // $data is the content until the next <tag-start or end is detected.
                // In case of a currentTag set, this would mean all data between the start- and end-tags
                $data = substr($theValue, $pointer, $len);
                if ($data !== false) {
                    if ($stripNL) {
                        // If the previous tag was set to strip NewLines in the beginning of the next data-chunk.
                        $data = preg_replace('/^[ ]*' . CR . '?' . LF . '/', '', $data);
                        if ($data === null) {
                            $this->logger->debug('Stripping new lines failed for "{data}"', ['data' => $data]);
                            $data = '';
                        }
                    }
                    // These operations should only be performed on code outside the tags...
                    if (!is_array($currentTag)) {
                        // Constants
                        $typoScriptSetupArray = [];
                        $frontendTypoScript = $this->getRequest()->getAttribute('frontend.typoscript');
                        if ($frontendTypoScript && $frontendTypoScript->hasSetup()) {
                            $typoScriptSetupArray = $frontendTypoScript->getSetupArray();
                        }
                        $tmpConstants = $typoScriptSetupArray['constants.'] ?? null;
                        if (!empty($conf['constants']) && is_array($tmpConstants)) {
                            // @deprecated since v12, remove with v13: Entire if plus init code above
                            trigger_error(
                                'The TypoScript setup "constants" top-level-object and the parseFunc property "constants" have'
                                . ' been deprecated in TYPO3 v12 and will be removed in v12. Use TypoScript constants / settings'
                                . ' and access them in setup using "{$myConstant}" instead.',
                                E_USER_DEPRECATED
                            );
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
                            $data = $this->callUserFunction($conf['userFunc'], $conf['userFunc.'] ?? [], $data);
                        }
                    }
                    // Search for tags to process in current data and
                    // call this method recursively if found
                    if (str_contains($data, '<') && isset($conf['tags.']) && is_array($conf['tags.'])) {
                        // @todo probably use a DOM tree traversal for the whole stuff
                        // This iterations basically re-processes the markup string, as
                        // long as there are `<$tag ` or `<$tag>` "tags" found...
                        foreach (array_keys($conf['tags.']) as $tag) {
                            // only match tag `a` in `<a href"...">` but not in `<abbr>`
                            if (preg_match('#<' . $tag . '[\s/>]#', $data)) {
                                $data = $this->parseFuncInternal($data, $conf);
                                break;
                            }
                        }
                    }
                    if (!is_array($currentTag) && ($conf['makelinks'] ?? false)) {
                        $data = $this->http_makelinks($data, $conf['makelinks.']['http.'] ?? []);
                        $data = $this->mailto_makelinks($data, $conf['makelinks.']['mailto.'] ?? []);
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
                if (str_ends_with($data, '/>') && !str_starts_with($data, '<link ')) {
                    $tagContent = substr($data, 1, -2);
                } else {
                    $tagContent = substr($data, 1, -1);
                }
                $tag = explode(' ', trim($tagContent), 2);
                $tag[0] = strtolower($tag[0]);
                // end tag like </li>
                if (str_starts_with($tag[0], '/')) {
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
                        if (preg_match('/^(area|base|br|col|hr|img|input|meta|param)$/i', (string)$tag[0])) {
                            $tag['out'] = 1;
                        }
                    }
                    // out-tag
                    if (isset($currentTag[0], $tag['out']) && $currentTag[0] === $tag[0] && $tag['out']) {
                        $theName = $conf['tags.'][$tag[0]];
                        $theConf = $conf['tags.'][$tag[0] . '.'];
                        // This flag indicates, that NL- (13-10-chars) should be stripped first and last.
                        $stripNL = (bool)($theConf['stripNL'] ?? false);
                        // This flag indicates, that this TypoTag section should NOT be included in the nonTypoTag content.
                        $breakOut = (bool)($theConf['breakoutTypoTagContent'] ?? false);
                        $this->parameters = [];
                        if (isset($currentTag[1])) {
                            // decode HTML entities in attributes, since they're processed
                            $params = GeneralUtility::get_tag_attributes((string)$currentTag[1], true);
                            if (is_array($params)) {
                                foreach ($params as $option => $val) {
                                    // contains non-encoded values
                                    $this->parameters[strtolower($option)] = $val;
                                }
                            }
                            $this->parameters['allParams'] = trim((string)$currentTag[1]);
                        }
                        // Removes NL in the beginning and end of the tag-content AND at the end of the currentTagBuffer.
                        // $stripNL depends on the configuration of the current tag
                        if ($stripNL) {
                            $contentAccum[$contentAccumP - 1] = preg_replace('/' . CR . '?' . LF . '[ ]*$/', '', $contentAccum[$contentAccumP - 1] ?? '');
                            $contentAccum[$contentAccumP] = preg_replace('/^[ ]*' . CR . '?' . LF . '/', '', $contentAccum[$contentAccumP] ?? '');
                            $contentAccum[$contentAccumP] = preg_replace('/' . CR . '?' . LF . '[ ]*$/', '', $contentAccum[$contentAccumP] ?? '');
                        }
                        $this->data[$this->currentValKey] = $contentAccum[$contentAccumP] ?? null;
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
                        $currentTag = null;
                        $treated = true;
                    }
                    // other tags
                    if (!$treated) {
                        $contentAccum[$contentAccumP] .= $data;
                    }
                } else {
                    // If a tag was not a typo tag, then it is just added to the content
                    $stripNL = false;
                    if (GeneralUtility::inList($allowTags, (string)$tag[0]) ||
                        ($denyTags !== '*' && !GeneralUtility::inList($denyTags, (string)$tag[0]))) {
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
                    $contentAccum[$a] = $this->stdWrap((string)($contentAccum[$a] ?? ''), $conf['nonTypoTagStdWrap.']);
                }
                // userFunc
                if (!empty($conf['nonTypoTagUserFunc'])) {
                    $contentAccum[$a] = $this->callUserFunction($conf['nonTypoTagUserFunc'], $conf['nonTypoTagUserFunc.'] ?? [], (string)($contentAccum[$a] ?? ''));
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

        $encapTags = GeneralUtility::trimExplode(',', strtolower($conf['encapsTagList'] ?? ''), true);
        $defaultAlign = trim((string)$this->stdWrapValue('defaultAlign', $conf ?? []));

        $str_content = '';
        foreach ($lParts as $k => $l) {
            $sameBeginEnd = false;
            $emptyTag = false;
            $l = trim($l);
            $attrib = [];
            $nonWrapped = false;
            $tagName = '';
            if (isset($l[0]) && $l[0] === '<' && substr($l, -1) === '>') {
                $fwParts = explode('>', substr($l, 1), 2);
                [$tagName] = explode(' ', $fwParts[0], 2);
                if (!$fwParts[1]) {
                    if (substr($tagName, -1) === '/') {
                        $tagName = substr($tagName, 0, -1);
                    }
                    if (substr($fwParts[0], -1) === '/') {
                        $sameBeginEnd = true;
                        $emptyTag = true;
                        // decode HTML entities, they're encoded later again
                        $attrib = GeneralUtility::get_tag_attributes('<' . substr($fwParts[0], 0, -1) . '>', true);
                    }
                } else {
                    $backParts = GeneralUtility::revExplode('<', substr($fwParts[1], 0, -1), 2);
                    // decode HTML entities, they're encoded later again
                    $attrib = GeneralUtility::get_tag_attributes('<' . $fwParts[0] . '>', true);
                    $str_content = $backParts[0];
                    // Ensure that $backParts could be exploded into 2 items
                    if (isset($backParts[1])) {
                        $sameBeginEnd = strtolower(substr($backParts[1], 1, strlen($tagName))) === strtolower($tagName);
                    }
                }
            }
            if ($sameBeginEnd && in_array(strtolower($tagName), $encapTags)) {
                $uTagName = strtoupper($tagName);
                $uTagName = strtoupper($conf['remapTag.'][$uTagName] ?? $uTagName);
            } else {
                $uTagName = strtoupper($conf['nonWrappedTag'] ?? '');
                // The line will be wrapped: $uTagName should not be an empty tag
                $emptyTag = false;
                $str_content = $lParts[$k];
                $nonWrapped = true;
                $attrib = [];
            }
            // Wrapping all inner-content:
            if (is_array($conf['innerStdWrap_all.'] ?? null)) {
                $str_content = (string)$this->stdWrap($str_content, $conf['innerStdWrap_all.']);
            }
            if ($uTagName) {
                // Setting common attributes
                if (isset($conf['addAttributes.'][$uTagName . '.']) && is_array($conf['addAttributes.'][$uTagName . '.'])) {
                    foreach ($conf['addAttributes.'][$uTagName . '.'] as $kk => $vv) {
                        if (!is_array($vv)) {
                            if ((string)($conf['addAttributes.'][$uTagName . '.'][$kk . '.']['setOnly'] ?? '') === 'blank') {
                                if ((string)($attrib[$kk] ?? '') === '') {
                                    $attrib[$kk] = $vv;
                                }
                            } elseif ((string)($conf['addAttributes.'][$uTagName . '.'][$kk . '.']['setOnly'] ?? '') === 'exists') {
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
                    $str_content = (string)$this->stdWrap($str_content, $conf['encapsLinesStdWrap.'][$uTagName . '.']);
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
     * Finds URLs in text and makes it to a real link.
     * Will find all strings prefixed with "http://" and "https://" in the $data string and make them into a link,
     * linking to the URL we should have found.
     *
     * Helper method of parseFuncInternal().
     *
     * @param string $data The string in which to search for "http://
     * @param array $conf Configuration for makeLinks, see link
     * @return string The processed input string, being returned.
     * @internal
     */
    protected function http_makelinks(string $data, array $conf): string
    {
        $parts = [];
        foreach (['http://', 'https://'] as $scheme) {
            $textpieces = explode($scheme, $data);
            $pieces = count($textpieces);
            $textstr = $textpieces[0];
            for ($i = 1; $i < $pieces; $i++) {
                $len = strcspn($textpieces[$i], chr(32) . "\t" . CRLF);
                if (!(trim(substr($textstr, -1)) === '' && $len)) {
                    $textstr .= $scheme . $textpieces[$i];
                    continue;
                }
                $lastChar = substr($textpieces[$i], $len - 1, 1);
                if (!preg_match('/[A-Za-z0-9\\/#_-]/', $lastChar)) {
                    $len--;
                }
                // Included '\/' 3/12
                $parts[0] = substr($textpieces[$i], 0, $len);
                $parts[1] = substr($textpieces[$i], $len);
                $keep = $conf['keep'] ?? '';
                $linkParts = parse_url($scheme . $parts[0]);
                // Check if link couldn't be parsed properly
                if (!is_array($linkParts)) {
                    $textstr .= $scheme . $textpieces[$i];
                    continue;
                }
                $linktxt = '';
                if (str_contains($keep, 'scheme')) {
                    $linktxt = $scheme;
                }
                $linktxt .= $linkParts['host'] ?? '';
                if (str_contains($keep, 'path')) {
                    $linktxt .= ($linkParts['path'] ?? '');
                    // Added $linkParts['query'] 3/12
                    if (str_contains($keep, 'query') && $linkParts['query']) {
                        $linktxt .= '?' . $linkParts['query'];
                    } elseif (($linkParts['path'] ?? '') === '/') {
                        $linktxt = substr($linktxt, 0, -1);
                    }
                }
                $typolinkConfiguration = $conf;
                $typolinkConfiguration['parameter'] = $scheme . $parts[0];
                $textstr .= $this->typoLink($linktxt, $typolinkConfiguration) . $parts[1];
            }
            $data = $textstr;
        }
        return $textstr;
    }

    /**
     * Will find all strings prefixed with "mailto:" in the $data string and make them into a link,
     * linking to the email address they point to.
     *
     * Helper method of parseFuncInternal().
     *
     * @param string $data The string in which to search for "mailto:
     * @param array $conf Configuration for makeLinks, see link
     * @return string The processed input string, being returned.
     * @internal
     */
    protected function mailto_makelinks(string $data, array $conf): string
    {
        $conf = (array)$conf;
        $parts = [];
        // split by mailto logic
        $textpieces = explode('mailto:', $data);
        $pieces = count($textpieces);
        $textstr = $textpieces[0] ?? '';
        for ($i = 1; $i < $pieces; $i++) {
            $len = strcspn($textpieces[$i], chr(32) . "\t" . CRLF);
            if (trim(substr($textstr, -1)) === '' && $len) {
                $lastChar = substr($textpieces[$i], $len - 1, 1);
                if (!preg_match('/[A-Za-z0-9]/', $lastChar)) {
                    $len--;
                }
                $parts[0] = substr($textpieces[$i], 0, $len);
                $parts[1] = substr($textpieces[$i], $len);
                $linktxt = (string)preg_replace('/\\?.*/', '', $parts[0]);
                $typolinkConfiguration = $conf;
                $typolinkConfiguration['parameter'] = 'mailto:' . $parts[0];
                $textstr .= (string)$this->typoLink($linktxt, $typolinkConfiguration) . $parts[1];
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
     * @see cImage()
     * @see \TYPO3\CMS\Frontend\Imaging\GifBuilder
     */
    public function getImgResource($file, $fileArray)
    {
        $importedFile = null;
        $fileReference = null;
        if (empty($file) && empty($fileArray)) {
            return null;
        }
        if (!is_array($fileArray)) {
            $fileArray = (array)$fileArray;
        }
        $imageResource = null;
        if ($file === 'GIFBUILDER') {
            $gifCreator = GeneralUtility::makeInstance(GifBuilder::class);
            if ($GLOBALS['TYPO3_CONF_VARS']['GFX']['gdlib']) {
                $gifCreator->start($fileArray, $this->data);
                $theImage = $gifCreator->gifBuild();
                if ($theImage !== '') {
                    $imageResource = $gifCreator->getImageDimensions(Environment::getPublicPath() . '/' . $theImage);
                    $imageResource['origFile'] = $theImage;
                }
            }
        } else {
            if ($file instanceof File) {
                $fileObject = $file;
            } elseif ($file instanceof FileReference) {
                $fileReference = $file;
                $fileObject = $file->getOriginalFile();
            } else {
                try {
                    if (isset($fileArray['import.']) && $fileArray['import.']) {
                        $importedFile = trim((string)$this->stdWrap('', $fileArray['import.']));
                        if (!empty($importedFile)) {
                            $file = $importedFile;
                        }
                    }

                    if (MathUtility::canBeInterpretedAsInteger($file)) {
                        $treatIdAsReference = $this->stdWrapValue('treatIdAsReference', $fileArray);
                        if (!empty($treatIdAsReference)) {
                            $fileReference = $this->getResourceFactory()->getFileReferenceObject($file);
                            $fileObject = $fileReference->getOriginalFile();
                        } else {
                            $fileObject = $this->getResourceFactory()->getFileObject($file);
                        }
                    } elseif (preg_match('/^(0|[1-9][0-9]*):/', $file)) { // combined identifier
                        $fileObject = $this->getResourceFactory()->retrieveFileOrFolderObject($file);
                    } else {
                        if ($importedFile && !empty($fileArray['import'])) {
                            $file = $fileArray['import'] . $file;
                        }
                        $fileObject = $this->getResourceFactory()->retrieveFileOrFolderObject($file);
                    }
                } catch (Exception $exception) {
                    $this->logger->warning('The image "{file}" could not be found and won\'t be included in frontend output', [
                        'file' => $file,
                        'exception' => $exception,
                    ]);
                    return null;
                }
            }
            if ($fileObject instanceof File) {
                $processingConfiguration['width'] = $this->stdWrapValue('width', $fileArray);
                $processingConfiguration['height'] = $this->stdWrapValue('height', $fileArray);
                $processingConfiguration['fileExtension'] = $this->stdWrapValue('ext', $fileArray);
                $processingConfiguration['maxWidth'] = (int)$this->stdWrapValue('maxW', $fileArray);
                $processingConfiguration['maxHeight'] = (int)$this->stdWrapValue('maxH', $fileArray);
                $processingConfiguration['minWidth'] = (int)$this->stdWrapValue('minW', $fileArray);
                $processingConfiguration['minHeight'] = (int)$this->stdWrapValue('minH', $fileArray);
                $processingConfiguration['noScale'] = $this->stdWrapValue('noScale', $fileArray);
                $processingConfiguration['sample'] = (bool)$this->stdWrapValue('sample', $fileArray);
                $processingConfiguration['additionalParameters'] = $this->stdWrapValue('params', $fileArray);
                $processingConfiguration['frame'] = (int)$this->stdWrapValue('frame', $fileArray);
                if ($fileReference instanceof FileReference) {
                    $processingConfiguration['crop'] = $this->getCropAreaFromFileReference($fileReference, $fileArray);
                } else {
                    $processingConfiguration['crop'] = $this->getCropAreaFromFromTypoScriptSettings($fileObject, $fileArray);
                }

                // Possibility to cancel/force profile extraction
                // see $GLOBALS['TYPO3_CONF_VARS']['GFX']['processor_stripColorProfileCommand']
                if (isset($fileArray['stripProfile'])) {
                    $processingConfiguration['stripProfile'] = $fileArray['stripProfile'];
                }
                // Check if we can handle this type of file for editing
                if ($fileObject->isImage()) {
                    $maskArray = $fileArray['m.'] ?? false;
                    // Must render mask images and include in hash-calculating
                    // - otherwise we cannot be sure the filename is unique for the setup!
                    if (is_array($maskArray)) {
                        $processingConfiguration['maskImages']['maskImage'] = $this->getImgResource($maskArray['mask'] ?? '', $maskArray['mask.'] ?? [])['processedFile'] ?? null;
                        $processingConfiguration['maskImages']['backgroundImage'] = $this->getImgResource($maskArray['bgImg'] ?? '', $maskArray['bgImg.'] ?? [])['processedFile'] ?? null;
                        $processingConfiguration['maskImages']['maskBottomImage'] = $this->getImgResource($maskArray['bottomImg'] ?? '', $maskArray['bottomImg.'] ?? [])['processedFile'] ?? null;
                        $processingConfiguration['maskImages']['maskBottomImageMask'] = $this->getImgResource($maskArray['bottomImg_mask'] ?? '', $maskArray['bottomImg_mask.'] ?? [])['processedFile'] ?? null;
                    }
                    $processedFileObject = $fileObject->process(ProcessedFile::CONTEXT_IMAGECROPSCALEMASK, $processingConfiguration);
                    if ($processedFileObject->isProcessed()) {
                        $imageResource = [
                            0 => (int)$processedFileObject->getProperty('width'),
                            1 => (int)$processedFileObject->getProperty('height'),
                            2 => $processedFileObject->getExtension(),
                            3 => Environment::getPublicPath() . '/' . $processedFileObject->getPublicUrl(),
                            'origFile' => $fileObject->getPublicUrl(),
                            'origFile_mtime' => $fileObject->getModificationTime(),
                            // This is needed by \TYPO3\CMS\Frontend\Imaging\GifBuilder,
                            // in order for the setup-array to create a unique filename hash.
                            'originalFile' => $fileObject,
                            'processedFile' => $processedFileObject,
                        ];
                    }
                }
            }
        }
        // Triggered when the resolved file object isn't considered as image, processing failed and likely other scenarios
        // This code path dates back to pre FAL times and should be deprecated and removed eventually
        if (!isset($imageResource) && is_string($file)) {
            try {
                $theImage = GeneralUtility::makeInstance(FilePathSanitizer::class)->sanitize($file);
                $info = GeneralUtility::makeInstance(GifBuilder::class)->imageMagickConvert($theImage, 'WEB');
                if ($info !== null) {
                    $info['origFile'] = $theImage;
                    // This is needed by \TYPO3\CMS\Frontend\Imaging\GifBuilder, ln 100ff in order for the setup-array to create a unique filename hash.
                    $info['origFile_mtime'] = @filemtime(Environment::getPublicPath() . '/' . $theImage);
                    $imageResource = $info;
                }
            } catch (Exception $e) {
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
     * @return Area|null
     */
    protected function getCropAreaFromFromTypoScriptSettings(FileInterface $file, array $fileArray)
    {
        /** @var Area $cropArea */
        $cropArea = null;
        // Resolve TypoScript configured cropping.
        $cropSettings = isset($fileArray['crop.'])
            ? $this->stdWrap($fileArray['crop'] ?? '', $fileArray['crop.'])
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
                    $cropSettings = array_map(floatval(...), $cropSettings);
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
        if (!str_contains($field, '//')) {
            return $this->data[trim($field)] ?? null;
        }
        $sections = GeneralUtility::trimExplode('//', $field, true);
        foreach ($sections as $k) {
            if ((string)($this->data[$k] ?? '') !== '') {
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
        // @todo: getData should not be called with non-string as $string. example trigger:
        //        SecureHtmlRenderingTest htmlViewHelperAvoidsCrossSiteScripting set #07 PHP 8
        $sections = is_string($string) ? explode('//', $string) : [];
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
                        $requestParameters = $this->getRequest()->getQueryParams();
                        $requestParameters = array_replace_recursive($requestParameters, (array)$this->getRequest()->getParsedBody());
                        $retVal = $this->getGlobal($key, $requestParameters);
                        break;
                    case 'request':
                        $retVal = $this->getValueFromRecursiveData(GeneralUtility::trimExplode('|', $key), $this->getRequest());
                        break;
                    case 'tsfe':
                        $retVal = $this->getValueFromRecursiveData(GeneralUtility::trimExplode('|', $key), $this->getTypoScriptFrontendController());
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
                        $retVal = $this->parameters[$key] ?? null;
                        break;
                    case 'register':
                        $retVal = $tsfe->register[$key] ?? null;
                        break;
                    case 'global':
                        $retVal = $this->getGlobal($key);
                        break;
                    case 'level':
                        $retVal = count($tsfe->config['rootLine'] ?? []) - 1;
                        break;
                    case 'leveltitle':
                        $keyParts = GeneralUtility::trimExplode(',', $key);
                        $pointer = (int)($keyParts[0] ?? 0);
                        $slide = (string)($keyParts[1] ?? '');

                        $numericKey = $this->getKey($pointer, $tsfe->config['rootLine'] ?? []);
                        $retVal = $this->rootLineValue($numericKey, 'title', strtolower($slide) === 'slide');
                        break;
                    case 'levelmedia':
                        $keyParts = GeneralUtility::trimExplode(',', $key);
                        $pointer = (int)($keyParts[0] ?? 0);
                        $slide = (string)($keyParts[1] ?? '');

                        $numericKey = $this->getKey($pointer, $tsfe->config['rootLine'] ?? []);
                        $retVal = $this->rootLineValue($numericKey, 'media', strtolower($slide) === 'slide');
                        break;
                    case 'leveluid':
                        $numericKey = $this->getKey((int)$key, $tsfe->config['rootLine'] ?? []);
                        $retVal = $this->rootLineValue($numericKey, 'uid');
                        break;
                    case 'levelfield':
                        $keyParts = GeneralUtility::trimExplode(',', $key);
                        $pointer = (int)($keyParts[0] ?? 0);
                        $field = (string)($keyParts[1] ?? '');
                        $slide = (string)($keyParts[2] ?? '');

                        $numericKey = $this->getKey($pointer, $tsfe->config['rootLine'] ?? []);
                        $retVal = $this->rootLineValue($numericKey, $field, strtolower($slide) === 'slide');
                        break;
                    case 'fullrootline':
                        $keyParts = GeneralUtility::trimExplode(',', $key);
                        $pointer = (int)($keyParts[0] ?? 0);
                        $field = (string)($keyParts[1] ?? '');
                        $slide = (string)($keyParts[2] ?? '');

                        $fullKey = (int)($pointer - count($tsfe->config['rootLine'] ?? []) + count($tsfe->rootLine));
                        if ($fullKey >= 0) {
                            $retVal = $this->rootLineValue($fullKey, $field, stristr($slide, 'slide') !== false, $tsfe->rootLine);
                        }
                        break;
                    case 'date':
                        if (!$key) {
                            $key = 'd/m Y';
                        }
                        $retVal = date($key, $GLOBALS['EXEC_TIME']);
                        break;
                    case 'page':
                        $retVal = $tsfe->page[$key] ?? '';
                        break;
                    case 'pagelayout':
                        $retVal = GeneralUtility::makeInstance(PageLayoutResolver::class)
                            ->getLayoutForPage($tsfe->page, $tsfe->rootLine);
                        break;
                    case 'current':
                        $retVal = $this->data[$this->currentValKey] ?? null;
                        break;
                    case 'db':
                        $selectParts = GeneralUtility::trimExplode(':', $key, true);
                        if (!isset($selectParts[1])) {
                            break;
                        }
                        $dbRecord = $tsfe->sys_page->getRawRecord($selectParts[0], $selectParts[1]);
                        if (is_array($dbRecord) && isset($selectParts[2])) {
                            $retVal = $dbRecord[$selectParts[2]] ?? '';
                        }
                        break;
                    case 'lll':
                        $languageService = GeneralUtility::makeInstance(LanguageServiceFactory::class)->createFromSiteLanguage($this->getTypoScriptFrontendController()->getLanguage());
                        $retVal = $languageService->sL('LLL:' . $key);
                        break;
                    case 'path':
                        try {
                            $retVal = GeneralUtility::makeInstance(FilePathSanitizer::class)->sanitize($key);
                        } catch (Exception $e) {
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
                                $retVal = DebugUtility::viewArray($tsfe->config['rootLine'] ?? []);
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
                        [$aspectName, $propertyName] = GeneralUtility::trimExplode(':', $key, true, 2);
                        $retVal = $context->getPropertyFromAspect($aspectName, $propertyName, '');
                        if (is_array($retVal)) {
                            $retVal = implode(',', $retVal);
                        }
                        if (!is_scalar($retVal)) {
                            $retVal = '';
                        }
                        break;
                    case 'site':
                        $site = $this->getTypoScriptFrontendController()->getSite();
                        if ($key === 'identifier') {
                            $retVal = $site->getIdentifier();
                        } elseif ($key === 'base') {
                            $retVal = $site->getBase();
                        } else {
                            try {
                                $retVal = ArrayUtility::getValueByPath($site->getConfiguration(), $key, '.');
                            } catch (MissingArrayPathException $exception) {
                                $this->logger->notice('Configuration "{key}" is not defined for site "{site}"', ['key' => $key, 'site' => $site->getIdentifier(), 'exception' => $exception]);
                            }
                        }
                        break;
                    case 'sitelanguage':
                        $siteLanguage = $this->getTypoScriptFrontendController()->getLanguage();
                        if ($key === 'twoLetterIsoCode') {
                            $key = 'locale:languageCode';
                        }
                        if ($key === 'hreflang') {
                            $key = 'locale:full';
                        }
                        // Harmonizing the namings from the site configuration value with the TypoScript setting
                        if ($key === 'flag') {
                            $key = 'flagIdentifier';
                        }
                        // Special handling for the locale object
                        if (str_starts_with($key, 'locale')) {
                            $localeObject = $siteLanguage->getLocale();
                            if ($key === 'locale') {
                                // backwards-compatibility
                                $retVal = $localeObject->posixFormatted();
                            } else {
                                $keyParts = explode(':', $key, 2);
                                switch ($keyParts[1] ?? '') {
                                    case 'languageCode':
                                        $retVal = $localeObject->getLanguageCode();
                                        break;
                                    case 'countryCode':
                                        $retVal = $localeObject->getCountryCode();
                                        break;
                                    case 'full':
                                    default:
                                        $retVal = $localeObject->getName();
                                }
                            }
                        } else {
                            $config = $siteLanguage->toArray();
                            if (isset($config[$key])) {
                                $retVal = $config[$key] ?? '';
                            }
                        }
                        break;
                    case 'sitesettings':
                        $siteSettings = $this->getTypoScriptFrontendController()->getSite()->getSettings();
                        $retVal = $siteSettings->get($key, '');
                        break;
                }
            }

            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_content.php']['getData'] ?? [] as $className) {
                $hookObject = GeneralUtility::makeInstance($className);
                if (!$hookObject instanceof ContentObjectGetDataHookInterface) {
                    throw new \UnexpectedValueException('$hookObject must implement interface ' . ContentObjectGetDataHookInterface::class, 1195044480);
                }
                $ref = $this; // introduced for phpstan to not lose type information when passing $this into callUserFunction
                $retVal = $hookObject->getDataExtension($string, $fieldArray, $secVal, $retVal, $ref);
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
        [$fileUidOrCurrentKeyword, $requestedFileInformationKey] = GeneralUtility::trimExplode(':', $key, false, 3);
        try {
            if ($fileUidOrCurrentKeyword === 'current') {
                $fileObject = $this->getCurrentFile();
            } elseif (MathUtility::canBeInterpretedAsInteger($fileUidOrCurrentKeyword)) {
                $fileFactory = GeneralUtility::makeInstance(ResourceFactory::class);
                $fileObject = $fileFactory->getFileObject($fileUidOrCurrentKeyword);
            } else {
                $fileObject = null;
            }
        } catch (Exception $exception) {
            $this->logger->warning('The file "{uid}" could not be found and won\'t be included in frontend output', ['uid' => $fileUidOrCurrentKeyword, 'exception' => $exception]);
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
     * Returns a value from the current rootline (site) from $GLOBALS['TSFE']->config['rootLine'];
     *
     * @param int $key Which level in the root line
     * @param string $field The field in the rootline record to return (a field from the pages table)
     * @param bool $slideBack If set, then we will traverse through the rootline from outer level towards the root level until the value found is TRUE
     * @param mixed $altRootLine If you supply an array for this it will be used as an alternative root line array
     * @return string The value from the field of the rootline.
     * @internal
     * @see getData()
     */
    public function rootLineValue($key, $field, $slideBack = false, $altRootLine = '')
    {
        $rootLine = is_array($altRootLine) ? $altRootLine : ($this->getTypoScriptFrontendController()->config['rootLine'] ?? []);
        if (!$slideBack) {
            return $rootLine[$key][$field] ?? '';
        }
        for ($a = $key; $a >= 0; $a--) {
            $val = $rootLine[$a][$field] ?? '';
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
        $keys = GeneralUtility::trimExplode('|', $keyString);
        // remove the first key, as this is only used for finding the original value
        $rootKey = array_shift($keys);
        $value = isset($source) ? ($source[$rootKey] ?? '') : ($GLOBALS[$rootKey] ?? '');
        return $this->getValueFromRecursiveData($keys, $value);
    }

    /**
     * This method recursively checks for values in methods, arrays, objects, but
     * does not fall back to $GLOBALS object instead of getGlobal().
     *
     * see getGlobal()
     */
    protected function getValueFromRecursiveData(array $keys, mixed $startValue): int|float|string
    {
        $value = $startValue;
        $numberOfLevels = count($keys);
        for ($i = 0; $i < $numberOfLevels && isset($value); $i++) {
            $currentKey = $keys[$i];
            if (is_object($value)) {
                // getter method
                if (method_exists($value, 'get' . ucfirst($currentKey))) {
                    $getterMethod = 'get' . ucfirst($currentKey);
                    $value = $value->$getterMethod(...)();
                    // server request attribute, such as "routing"
                } elseif ($value instanceof ServerRequestInterface) {
                    $value = $value->getAttribute($currentKey);
                } else {
                    // Public property
                    $value = $value->{$currentKey};
                }
            } elseif (is_array($value)) {
                $value = $value[$currentKey] ?? '';
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
     * Implements the "typolink" property of stdWrap (and others)
     * Basically the input string, $linkText, is (typically) wrapped in a <a>-tag linking to some page, email address,
     * file or URL based on a parameter defined by the configuration array $conf.
     * This function is best used from internal functions as is. There are some API functions defined after this
     * function which is more suited for general usage in external applications.
     *
     * Generally the concept "typolink" should be used in your own applications as an API for making links to pages with
     * parameters and more. The reason for this is that you will then automatically make links compatible with all the
     * centralized functions for URL simulation and manipulation of parameters into hashes and more.
     *
     * For many more details on the parameters and how they are interpreted, please see the link to TSref below.
     *
     * @param string $linkText The string (text) to link
     * @param array $conf TypoScript configuration (see link below)
     * @return string|LinkResult A link-wrapped string.
     * @see stdWrap()
     */
    public function typoLink(string $linkText, array $conf)
    {
        try {
            $linkResult = $this->createLink($linkText, $conf);
        } catch (UnableToLinkException $e) {
            return $e->getLinkText();
        }

        // If flag "returnLast" set, then just return the latest URL / url / target that was built.
        // This returns the information without being wrapped in a "LinkResult" object.
        switch ($conf['returnLast'] ?? null) {
            case 'url':
                return $linkResult->getUrl();
            case 'target':
                return $linkResult->getTarget();
            case 'result':
                // kept for backwards-compatibility, as this was added in TYPO3 v11
                return LinkResult::adapt($linkResult, LinkResult::STRING_CAST_JSON);
        }

        $wrap = (string)$this->stdWrapValue('wrap', $conf ?? []);
        if ($conf['ATagBeforeWrap'] ?? false) {
            $linkResult = $linkResult->withLinkText($this->wrap((string)$linkResult->getLinkText(), $wrap));
            return LinkResult::adapt($linkResult)->getHtml();
        }
        $result = LinkResult::adapt($linkResult)->getHtml();
        return $this->wrap($result, $wrap);
    }

    /**
     * Similar to ->typoLink(), however it does not evaluate the .wrap and .ATagBeforeWrap
     * functionality.
     *
     * For this reason, it also does not consider the LinkResult functionality,
     * and "returnLast" logic, as the whole LinkResult object is available.
     *
     * It is recommended to use this method when working with PHP and wanting to create
     * a typolink, but be aware that you need to escape the Link yourself as PHP developer depending
     * on the needs.
     *
     * @param string $linkText the text to be wrapped in a link
     * @param array $conf the typolink configuration
     * @throws UnableToLinkException
     * @see typoLink()
     * @see createUrl()
     */
    public function createLink(string $linkText, array $conf): LinkResultInterface
    {
        $this->lastTypoLinkUrl = '';
        $this->lastTypoLinkTarget = '';
        $this->lastTypoLinkResult = null;
        try {
            $linkResult = GeneralUtility::makeInstance(LinkFactory::class)->create($linkText, $conf, $this);
        } catch (UnableToLinkException $e) {
            // URL could not be generated
            throw $e;
        }

        $this->lastTypoLinkResult = $linkResult;
        // Now populate all legacy values
        // @deprecated can be removed in TYPO3 13.0.
        $this->lastTypoLinkTarget = $linkResult->getTarget();
        $this->lastTypoLinkUrl = $linkResult->getUrl();
        $this->lastTypoLinkLD['target'] = htmlspecialchars($linkResult->getTarget());
        $this->lastTypoLinkLD['totalUrl'] = $linkResult->getUrl();
        $this->lastTypoLinkLD['type'] = $linkResult->getType();
        return $linkResult;
    }

    /**
     * This method creates a typoLink() and just returns the information of the "href" attribute
     * of the link (most of the time, this is the URL).
     *
     * @param array $conf the typolink configuration.
     * @return string The URL
     * @see typoLink()
     * @see createLink()
     */
    public function createUrl(array $conf): string
    {
        try {
            return $this->createLink('', $conf)->getUrl();
        } catch (UnableToLinkException $e) {
            // URL could not be generated
            return '';
        }
    }

    /**
     * Based on the input "TypoLink" TypoScript configuration this will return the generated URL
     *
     * @param array $conf TypoScript properties for "typolink"
     * @return string The URL of the link-tag that typoLink() would by itself return
     * @see typoLink()
     */
    public function typoLink_URL($conf)
    {
        return $this->createUrl($conf ?? []);
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
     * @deprecated since TYPO3 v12.0. will be removed in TYPO3 v13.0, use LinkFactory or cObj->typoLink() instead.
     */
    public function getTypoLink($label, $params, $urlParameters = [], $target = '')
    {
        trigger_error('$cObj->getTypoLink() is deprecated in favor of the unified LinkFactory API for generating links. This method will be removed in TYPO3 v13.0.', E_USER_DEPRECATED);
        $conf = [];
        $conf['parameter'] = $params;
        if ($target) {
            $conf['target'] = $target;
            $conf['extTarget'] = $target;
            $conf['fileTarget'] = $target;
        }
        if (is_array($urlParameters)) {
            if (!empty($urlParameters)) {
                $conf['additionalParams'] = ($conf['additionalParams'] ?? '') . HttpUtility::buildQueryString($urlParameters, '&');
            }
        } else {
            $conf['additionalParams'] = ($conf['additionalParams'] ?? '') . $urlParameters;
        }
        $out = $this->typoLink((string)$label, $conf);
        return $out;
    }

    /**
     * Returns the canonical URL to the current "location", which include the current page ID and type
     * and optionally the query string
     *
     * @param bool $addQueryString Whether additional GET arguments in the query string should be included or not
     * @return string
     * @deprecated since TYPO3 v12.0. will be removed in TYPO3 v13.0, use LinkFactory or cObj->typoLink() instead.
     */
    public function getUrlToCurrentLocation($addQueryString = true)
    {
        trigger_error('$cObj->getUrlToCurrentLocation() is deprecated in favor of the unified LinkFactory API for generating links. This method will be removed in TYPO3 v13.0.', E_USER_DEPRECATED);
        $conf = [];
        $conf['parameter'] = $this->getTypoScriptFrontendController()->id . ',' . $this->getTypoScriptFrontendController()->getPageArguments()->getPageType();
        if ($addQueryString) {
            $conf['addQueryString'] = '1';
            $linkVars = implode(',', array_keys(GeneralUtility::explodeUrl2Array($this->getTypoScriptFrontendController()->linkVars)));
            $conf['addQueryString.'] = [
                'exclude' => 'id,type,cHash' . ($linkVars ? ',' . $linkVars : ''),
            ];
        }

        return $this->createUrl($conf);
    }

    /**
     * Returns the URL of a "typolink" create from the input parameter string, url-parameters and target
     *
     * @param string $params Link parameter; eg. "123" for page id, "kasperYYYY@typo3.com" for email address, "http://...." for URL, "fileadmin/example.txt" for file.
     * @param array|string $urlParameters As an array key/value pairs represent URL parameters to set. Values NOT URL-encoded yet, keys should be URL-encoded if needed. As a string the parameter is expected to be URL-encoded already.
     * @param string $target Specific target set, if any. (Default is using the current)
     * @return string The URL
     * @see getTypoLink()
     * @deprecated since TYPO3 v12.0, will be removed in TYPO3 v13.0. Use LinkFactory API directly.
     */
    public function getTypoLink_URL($params, $urlParameters = [], $target = '')
    {
        trigger_error('$cObj->getTypoLink_URL() is deprecated in favor of the unified LinkFactory API for generating links. This method will be removed in TYPO3 v13.0.', E_USER_DEPRECATED);
        $conf = [
            'parameter' => $params,
        ];
        if ($target) {
            $conf['target'] = $target;
            $conf['extTarget'] = $target;
            $conf['fileTarget'] = $target;
        }
        if (is_array($urlParameters)) {
            if (!empty($urlParameters)) {
                $conf['additionalParams'] = HttpUtility::buildQueryString($urlParameters, '&');
            }
        } else {
            $conf['additionalParams'] = $urlParameters;
        }
        return $this->createUrl($conf);
    }

    /**
     * @param string $mailAddress Email address
     * @param string $linktxt Link text, default will be the email address.
     * @return array{0: string, 1: string, 2: array<string, string>} A numerical array with three items
     * @deprecated will be removed in TYPO3 v13.0. Use EmailLinkBuilder->processEmailLink() instead.
     */
    public function getMailTo($mailAddress, $linktxt)
    {
        trigger_error('ContentObjectRenderer->getMailTo() will be removed in TYPO3 v13.0, Use EmailLinkBuilder->processEmailLink() instead.', E_USER_DEPRECATED);
        $linkBuilder = GeneralUtility::makeInstance(EmailLinkBuilder::class, $this, $this->getTypoScriptFrontendController());
        return $linkBuilder->processEmailLink((string)$mailAddress, (string)$linktxt);
    }

    /**
     * Gets the query arguments and assembles them for URLs.
     * Arguments may be removed or set, depending on configuration.
     *
     * @param array $conf Configuration
     * @return string The URL query part (starting with a &)
     * @deprecated will be removed in TYPO3 v13.0
     */
    public function getQueryArguments($conf)
    {
        trigger_error('Calling ContentObjectRenderer->getQueryArguments() will be removed in TYPO3 v13.0. Use LinkFactory directly to create links', E_USER_DEPRECATED);
        $currentQueryArray = $this->getRequest()->getQueryParams();
        if ($conf['exclude'] ?? false) {
            $excludeString = str_replace(',', '&', $conf['exclude']);
            $excludedQueryParts = [];
            parse_str($excludeString, $excludedQueryParts);
            $newQueryArray = ArrayUtility::arrayDiffKeyRecursive($currentQueryArray, $excludedQueryParts);
        } else {
            $newQueryArray = $currentQueryArray;
        }
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
            $content = ($wrapArr[1] ?? '') . $content . ($wrapArr[2] ?? '');
        }
        return $content;
    }

    /**
     * Calling a user function/class-method
     * Notice: For classes the instantiated object will have the internal variable, $cObj, set to be a *reference* to $this (the parent/calling object).
     *
     * @param string $funcName The functionname, eg "user_myfunction" or "user_myclass->main". Notice that there are rules for the names of functions/classes you can instantiate. If a function cannot be called for some reason it will be seen in the TypoScript log in the AdminPanel.
     * @param array $conf The TypoScript configuration to pass the function
     * @param mixed $content The content payload to pass the function
     * @return mixed The return content from the function call. Should probably be a string.
     */
    public function callUserFunction($funcName, $conf, $content)
    {
        // Split parts
        $parts = explode('->', $funcName);
        if (count($parts) === 2) {
            // Check whether PHP class is available
            if (class_exists($parts[0])) {
                if ($this->container && $this->container->has($parts[0])) {
                    $classObj = $this->container->get($parts[0]);
                } else {
                    $classObj = GeneralUtility::makeInstance($parts[0]);
                }
                $methodName = (string)$parts[1];
                $callable = [$classObj, $methodName];

                if (is_object($classObj) && method_exists($classObj, $parts[1]) && is_callable($callable)) {
                    if (method_exists($classObj, 'setContentObjectRenderer') && is_callable([$classObj, 'setContentObjectRenderer'])) {
                        $classObj->setContentObjectRenderer($this);
                    }
                    $content = $callable($content, $conf, $this->getRequest()->withAttribute('currentContentObject', $this));
                } else {
                    $this->getTimeTracker()->setTSlogMessage('Method "' . $parts[1] . '" did not exist in class "' . $parts[0] . '"', LogLevel::ERROR);
                }
            } else {
                $this->getTimeTracker()->setTSlogMessage('Class "' . $parts[0] . '" did not exist', LogLevel::ERROR);
            }
        } elseif (function_exists($funcName)) {
            $content = $funcName($content, $conf, $this->getRequest()->withAttribute('currentContentObject', $this));
        } else {
            $this->getTimeTracker()->setTSlogMessage('Function "' . $funcName . '" did not exist', LogLevel::ERROR);
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
        if ($listArr === false) {
            return '';
        }
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
     * @param string $case The direction; either "upper" or "lower"
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
     * Resolve a TypoScript reference value to the full set of properties BUT overridden with any local properties set.
     * So the reference is resolved but overlaid with local TypoScript properties of the reference value.
     *
     * In short: This parses the "=<" operator for a couple of special properties like "parseFunc" and "tt_content.*".
     *
     * Note the "=<" operator is not a general TypoScript language construct, but applied here for a couple
     * of special objects only.
     *
     * @param array $typoScriptArray The TypoScript array: ['someProperty' => 'somePropertyValue', 'someProperty.' => [ 'someSubProperty' => 'someSubValue', ... ]
     * @param string $propertyName The property name: If this value in $typoScriptArray[$prop] is a reference (eg. "< lib.contentElement"), then
     *                             the reference will be retrieved and inserted at that position and overlaid with given local properties if any.
     * @return array The modified TypoScript array with resolved "=<" reference operator
     * @internal
     * @todo: It would be better if this method would get the setup object tree to resolve a
     *        ReferenceChildNode only once per node. This would however mean the object tree
     *        is moved around in the entire rendering chain, which is quite hard to achieve.
     */
    public function mergeTSRef(array $typoScriptArray, string $propertyName): array
    {
        if (!isset($typoScriptArray[$propertyName]) || !str_starts_with($typoScriptArray[$propertyName], '<')) {
            return $typoScriptArray;
        }
        $frontendTypoScript = $this->getRequest()->getAttribute('frontend.typoscript');
        if (!$frontendTypoScript || !$frontendTypoScript->hasSetup()) {
            return $typoScriptArray;
        }
        $fullTypoScriptArray = $frontendTypoScript->getSetupArray();
        $dottedSourceIdentifier = trim(substr($typoScriptArray[$propertyName], 1));
        $dottedSourceIdentifierArray = StringUtility::explodeEscaped('.', $dottedSourceIdentifier);
        $overrideConfig = $typoScriptArray[$propertyName . '.'] ?? [];
        $resolvedValue = $dottedSourceIdentifier;
        $resolvedConfig = $fullTypoScriptArray;
        foreach ($dottedSourceIdentifierArray as $identifierPart) {
            if (!isset($resolvedConfig[$identifierPart . '.'])) {
                $resolvedValue = $dottedSourceIdentifier;
                $resolvedConfig = $overrideConfig;
                break;
            }
            $resolvedValue = $resolvedConfig[$identifierPart] ?? $resolvedValue;
            $resolvedConfig = $resolvedConfig[$identifierPart . '.'];
        }
        $resolvedConfig = array_replace_recursive($resolvedConfig, $overrideConfig);
        $typoScriptArray[$propertyName] = $resolvedValue;
        $typoScriptArray[$propertyName . '.'] = $resolvedConfig;
        if (!isset($typoScriptArray[$propertyName]) || !str_starts_with($typoScriptArray[$propertyName], '<')) {
            return $typoScriptArray;
        }
        // Call recursive to resolve a nested =< operator
        return $this->mergeTSRef($typoScriptArray, $propertyName);
    }

    /***********************************************
     *
     * Database functions, making of queries
     *
     ***********************************************/
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
     * @param int $depth The number of levels to descend. If you want to descend infinitely, just set this to 100 or so. Should be at least "1" since zero will just make the function return (no descend...)
     * @param int $begin Is an optional integer that determines at which level in the tree to start collecting uid's. Zero means 'start right away', 1 = 'next level and out'
     * @param bool $dontCheckEnableFields See function description
     * @param string $addSelectFields Additional fields to select. Syntax: ",[fieldname],[fieldname],...
     * @param string $moreWhereClauses Additional where clauses. Syntax: " AND [fieldname]=[value] AND ...
     * @param array $prevId_array array of IDs from previous recursions. In order to prevent infinite loops with mount pages.
     * @param int $recursionLevel Internal: Zero for the first recursion, incremented for each recursive call.
     * @return string Returns the list of ids as a comma separated string
     * @deprecated since TYPO3 v12.0, will be removed in TYPO3 v13.0. Use PageRepository->getDescendantPageIdsRecursive() or PageRepository->getPageIdsRecursive() instead.
     */
    public function getTreeList($id, $depth, $begin = 0, $dontCheckEnableFields = false, $addSelectFields = '', $moreWhereClauses = '', array $prevId_array = [], $recursionLevel = 0)
    {
        trigger_error('ContentObjectRenderer->getTreeList() will be removed in TYPO3 v13.0. Use PageRepository->getDescendantPageIdsRecursive() or PageRepository->getPageIdsRecursive() instead.', E_USER_DEPRECATED);
        $addCurrentPageId = false;
        $id = (int)$id;
        if ($id < 0) {
            $id = abs($id);
            $addCurrentPageId = true;
        }
        $pageRepository = $this->getTypoScriptFrontendController()->sys_page;
        if ($dontCheckEnableFields) {
            $backupEnableFields = $pageRepository->where_hid_del;
            $pageRepository->where_hid_del = '';
        }
        $result = $pageRepository->getDescendantPageIdsRecursive($id, (int)$depth, (int)$begin, [], (bool)$dontCheckEnableFields);
        if ($dontCheckEnableFields) {
            $pageRepository->where_hid_del = $backupEnableFields;
        }
        if ($addCurrentPageId) {
            $result = array_merge([$id], $result);
        }
        return implode(',', $result);
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

        $where = $queryBuilder->expr()->and();
        $searchFields = explode(',', $searchFieldList);
        $searchWords = preg_split('/[ ,]/', $searchWords);
        foreach ($searchWords as $searchWord) {
            $searchWord = trim($searchWord);
            if (strlen($searchWord) < 3) {
                continue;
            }
            $searchWordConstraint = $queryBuilder->expr()->or();
            $searchWord = $queryBuilder->escapeLikeWildcards($searchWord);
            foreach ($searchFields as $field) {
                $searchWordConstraint = $searchWordConstraint->with(
                    $queryBuilder->expr()->like($prefixTableName . $field, $queryBuilder->quote('%' . $searchWord . '%'))
                );
            }

            if ($searchWordConstraint->count()) {
                $where = $where->with($searchWordConstraint);
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
     * @return Result
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
        while ($row = $statement->fetchAssociative()) {
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
     * @see numRows()
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
            'where',
        ];
        foreach ($properties as $property) {
            $conf[$property] = trim(
                isset($conf[$property . '.'])
                    ? (string)$this->stdWrap($conf[$property] ?? '', $conf[$property . '.'] ?? [])
                    : (string)($conf[$property] ?? '')
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
                'rightjoin',
            ];
            foreach ($properties as $property) {
                if ($conf[$property] ?? false) {
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
                });
                $expandedPidList = $this->getTypoScriptFrontendController()->sys_page->getPageIdsRecursive($pidList, $conf['recursive']);
                $conf['pidInList'] = implode(',', $expandedPidList);
            }
        }
        if ((string)($conf['pidInList'] ?? '') === '') {
            $conf['pidInList'] = 'this';
        }

        $queryParts = $this->getQueryConstraints($table, $conf);

        $queryBuilder = $connection->createQueryBuilder();
        // @todo Check against getQueryConstraints, can probably use FrontendRestrictions
        // @todo here and remove enableFields there.
        $queryBuilder->getRestrictions()->removeAll();
        $queryBuilder->select('*')->from($table);

        if ($queryParts['where'] ?? false) {
            $queryBuilder->where($queryParts['where']);
        }

        if ($queryParts['groupBy'] ?? false) {
            $queryBuilder->groupBy(...$queryParts['groupBy']);
        }

        if (is_array($queryParts['orderBy'] ?? false)) {
            foreach ($queryParts['orderBy'] as $orderBy) {
                $queryBuilder->addOrderBy(...$orderBy);
            }
        }

        // Fields:
        if ($conf['selectFields'] ?? false) {
            $queryBuilder->selectLiteral($this->sanitizeSelectPart($conf['selectFields'], $table));
        }

        // Setting LIMIT:
        $error = false;
        if (($conf['max'] ?? false) || ($conf['begin'] ?? false)) {
            // Finding the total number of records, if used:
            if (str_contains(strtolower(($conf['begin'] ?? '') . ($conf['max'] ?? '')), 'total')) {
                $countQueryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
                $countQueryBuilder->getRestrictions()->removeAll();
                $countQueryBuilder->count('*')
                    ->from($table)
                    ->where($queryParts['where']);

                if ($queryParts['groupBy']) {
                    $countQueryBuilder->groupBy(...$queryParts['groupBy']);
                }

                try {
                    $count = $countQueryBuilder->executeQuery()->fetchOne();
                    if (isset($conf['max'])) {
                        $conf['max'] = str_ireplace('total', $count, (string)$conf['max']);
                    }
                    if (isset($conf['begin'])) {
                        $conf['begin'] = str_ireplace('total', $count, (string)$conf['begin']);
                    }
                } catch (DBALException $e) {
                    $this->getTimeTracker()->setTSlogMessage($e->getPrevious()->getMessage());
                    $error = true;
                }
            }

            if (!$error) {
                if (isset($conf['begin']) && $conf['begin'] > 0) {
                    $conf['begin'] = MathUtility::forceIntegerInRange((int)ceil($this->calc($conf['begin'])), 0);
                    $queryBuilder->setFirstResult($conf['begin']);
                }
                if (isset($conf['max'])) {
                    $conf['max'] = MathUtility::forceIntegerInRange((int)ceil($this->calc($conf['max'])), 0);
                    $queryBuilder->setMaxResults($conf['max'] ?: 100000);
                }
            }
        }

        if (!$error) {
            // Setting up tablejoins:
            if ($conf['join'] ?? false) {
                $joinParts = QueryHelper::parseJoin($conf['join']);
                $queryBuilder->join(
                    $table,
                    $joinParts['tableName'],
                    $joinParts['tableAlias'],
                    $joinParts['joinCondition']
                );
            } elseif ($conf['leftjoin'] ?? false) {
                $joinParts = QueryHelper::parseJoin($conf['leftjoin']);
                $queryBuilder->leftJoin(
                    $table,
                    $joinParts['tableName'],
                    $joinParts['tableAlias'],
                    $joinParts['joinCondition']
                );
            } elseif ($conf['rightjoin'] ?? false) {
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
            }

            return $returnQueryArray ? $this->getQueryArray($queryBuilder) : $query;
        }

        return '';
    }

    /**
     * Helper to transform a QueryBuilder object into a queryParts array that can be used
     * with exec_SELECT_queryArray
     *
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
        $considerMovePointers = (
            $isInWorkspace && $table !== 'pages'
            && !empty($GLOBALS['TCA'][$table]['ctrl']['versioningWS'])
        );

        if (trim($conf['uidInList'] ?? '')) {
            $listArr = GeneralUtility::intExplode(',', str_replace('this', (string)$tsfe->contentPid, $conf['uidInList']));

            // If moved records shall be considered, select via t3ver_oid
            if ($considerMovePointers) {
                $constraints[] = (string)$expressionBuilder->or(
                    $expressionBuilder->in($table . '.uid', $listArr),
                    $expressionBuilder->and(
                        $expressionBuilder->eq(
                            $table . '.t3ver_state',
                            (int)(string)VersionState::cast(VersionState::MOVE_POINTER)
                        ),
                        $expressionBuilder->in($table . '.t3ver_oid', $listArr)
                    )
                );
            } else {
                $constraints[] = (string)$expressionBuilder->in($table . '.uid', $listArr);
            }
            $pid_uid_flag++;
        }

        // Static_* tables are allowed to be fetched from root page
        if (str_starts_with($table, 'static_')) {
            $pid_uid_flag++;
        }

        if (trim($conf['pidInList'])) {
            $listArr = GeneralUtility::intExplode(',', str_replace('this', (string)$tsfe->contentPid, $conf['pidInList']));
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

        $where = trim((string)$this->stdWrapValue('where', $conf ?? []));
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
        $constraints[] = QueryHelper::stripLogicalOperatorPrefix($tsfe->sys_page->enableFields($table, -1, $enableFieldsIgnore));
        // For pages, recyclers are also always excluded, as this is the default for fetching records in PageRepository
        if ($table === 'pages') {
            $constraints[] = GeneralUtility::makeInstance(DocumentTypeExclusionRestriction::class, [PageRepository::DOKTYPE_RECYCLER])->buildExpression([$table => $table], $expressionBuilder);
        }

        // MAKE WHERE:
        if ($constraints !== []) {
            $queryParts['where'] = $expressionBuilder->and(...$constraints);
        }
        // GROUP BY
        $groupBy = trim((string)$this->stdWrapValue('groupBy', $conf ?? []));
        if ($groupBy) {
            $queryParts['groupBy'] = QueryHelper::parseGroupBy($groupBy);
        }

        // ORDER BY
        $orderByString = trim((string)$this->stdWrapValue('orderBy', $conf ?? []));
        if ($orderByString) {
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
            if (isset($conf['includeRecordsWithoutDefaultTranslation']) || !empty($conf['includeRecordsWithoutDefaultTranslation.'])) {
                $includeRecordsWithoutDefaultTranslation = isset($conf['includeRecordsWithoutDefaultTranslation.'])
                    ? $this->stdWrap($conf['includeRecordsWithoutDefaultTranslation'], $conf['includeRecordsWithoutDefaultTranslation.'])
                    : $conf['includeRecordsWithoutDefaultTranslation'];
                $includeRecordsWithoutDefaultTranslation = trim($includeRecordsWithoutDefaultTranslation) !== '';
            } else {
                // Option was not explicitly set, check what's in for the language overlay type.
                $includeRecordsWithoutDefaultTranslation = $languageAspect->getOverlayType() === $languageAspect::OVERLAYS_ON_WITH_FLOATING;
            }
            if ($includeRecordsWithoutDefaultTranslation) {
                $languageQuery = $expressionBuilder->or(
                    $languageQuery,
                    $expressionBuilder->and(
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
        $languageField = $GLOBALS['TCA'][$table]['ctrl']['languageField'] ?? false;
        if (isset($GLOBALS['TCA'][$table]) && !preg_match($matchStart . '\\*' . $matchEnd, $selectPart) && !preg_match('/(count|max|min|avg|sum)\\([^\\)]+\\)|distinct/i', $selectPart)) {
            foreach ($necessaryFields as $field) {
                $match = $matchStart . $field . $matchEnd;
                if (!preg_match($match, $selectPart)) {
                    $selectPart .= ', ' . $connection->quoteIdentifier($table . '.' . $field) . ' AS ' . $connection->quoteIdentifier($field);
                }
            }
            if (is_string($languageField)) {
                $match = $matchStart . $languageField . $matchEnd;
                if (!preg_match($match, $selectPart)) {
                    $selectPart .= ', ' . $connection->quoteIdentifier($table . '.' . $languageField) . ' AS ' . $connection->quoteIdentifier($languageField);
                }
            }
            if ($GLOBALS['TCA'][$table]['ctrl']['versioningWS'] ?? false) {
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
     * @param int[] $pageIds Array of Page UID numbers for select and for which pages with enablefields and bad doktypes should be removed.
     * @return array Returns the array of remaining page UID numbers
     * @internal
     */
    public function checkPidArray($pageIds)
    {
        if (!is_array($pageIds) || empty($pageIds)) {
            return [];
        }

        $tsfe = $this->getTypoScriptFrontendController();
        if ($pageIds === [$tsfe->id]) {
            // The TypoScriptFrontendController already granted access to the current page (see getPageAndRootline())
            // and made sure the current doktype is a doktype whose content should be rendered, so there is no need
            // to check that again.
            return $pageIds;
        }

        $restrictionContainer = GeneralUtility::makeInstance(FrontendRestrictionContainer::class);
        $restrictionContainer->add(GeneralUtility::makeInstance(
            DocumentTypeExclusionRestriction::class,
            GeneralUtility::intExplode(',', (string)$this->checkPid_badDoktypeList, true)
        ));
        return $tsfe->sys_page->filterAccessiblePageIds($pageIds, $restrictionContainer);
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
        if (!isset($conf['markers.']) || !is_array($conf['markers.'])) {
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
            // todo else value is always null
            $tempValue = isset($conf['markers.'][$dottedMarker])
                ? $this->stdWrap($conf['markers.'][$dottedMarker]['value'] ?? '', $conf['markers.'][$dottedMarker])
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

    /**
     * Get instance of FAL resource factory
     *
     * @return ResourceFactory
     */
    protected function getResourceFactory()
    {
        return GeneralUtility::makeInstance(ResourceFactory::class);
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
        if ($key === 'REQUEST_URI') {
            return $this->getRequest()->getAttribute('normalizedParams')->getRequestUri();
        }
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
        if ($this->getTypoScriptFrontendController()->no_cache) {
            return false;
        }
        $cacheKey = $this->calculateCacheKey($configuration);
        if (empty($cacheKey)) {
            return false;
        }

        $cacheFrontend = GeneralUtility::makeInstance(CacheManager::class)->getCache('hash');
        $cachedData = $cacheFrontend->get($cacheKey);
        if ($cachedData === false) {
            return false;
        }
        if (is_string($cachedData)) {
            // Legacy (from a previous patch release) cache data found.
            // Handle gracefully as content without associated cache tags.
            return $cachedData;
        }
        $this->getTypoScriptFrontendController()->addCacheTags($cachedData['cacheTags'] ?? []);
        return $cachedData['content'] ?? false;
    }

    /**
     * Calculates the lifetime of a cache entry based on the given configuration
     *
     * @return int|null
     */
    protected function calculateCacheLifetime(array $configuration)
    {
        $configuration['lifetime'] = $configuration['lifetime'] ?? '';
        $lifetimeConfiguration = (string)$this->stdWrapValue('lifetime', $configuration);

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
     * @return array
     */
    protected function calculateCacheTags(array $configuration)
    {
        $configuration['tags'] = $configuration['tags'] ?? '';
        $tags = (string)$this->stdWrapValue('tags', $configuration);
        return empty($tags) ? [] : GeneralUtility::trimExplode(',', $tags);
    }

    /**
     * Applies stdWrap to the cache key
     *
     * @return string
     */
    protected function calculateCacheKey(array $configuration)
    {
        $configuration['key'] = $configuration['key'] ?? '';
        return $this->stdWrapValue('key', $configuration);
    }

    /**
     * @return TimeTracker
     */
    protected function getTimeTracker()
    {
        return GeneralUtility::makeInstance(TimeTracker::class);
    }

    /**
     * @return TypoScriptFrontendController|null
     * @internal this is set to public so extensions such as EXT:solr can use the method in tests.
     */
    public function getTypoScriptFrontendController()
    {
        return $this->typoScriptFrontendController ?: $GLOBALS['TSFE'] ?? null;
    }

    /**
     * Get content length of the current tag that could also contain nested tag contents
     *
     * Helper method of parseFuncInternal().
     *
     * @internal
     */
    protected function getContentLengthOfCurrentTag(string $theValue, int $pointer, string $currentTag): int
    {
        $tempContent = strtolower(substr($theValue, $pointer));
        $startTag = '<' . $currentTag;
        $endTag = '</' . $currentTag . '>';
        $offsetCount = 0;

        // Take care for nested tags
        do {
            $nextMatchingEndTagPosition = strpos($tempContent, $endTag);
            // only match tag `a` in `<a href"...">` but not in `<abbr>`
            $nextSameTypeTagPosition = preg_match(
                '#' . $startTag . '[\s/>]#',
                $tempContent,
                $nextSameStartTagMatches,
                PREG_OFFSET_CAPTURE
            ) ? $nextSameStartTagMatches[0][1] : false;

            // filter out nested tag contents to help getting the correct closing tag
            if ($nextMatchingEndTagPosition !== false && $nextSameTypeTagPosition !== false && $nextSameTypeTagPosition < $nextMatchingEndTagPosition) {
                $lastOpeningTagStartPosition = (int)strrpos(substr($tempContent, 0, $nextMatchingEndTagPosition), $startTag);
                $closingTagEndPosition = $nextMatchingEndTagPosition + strlen($endTag);
                $offsetCount += $closingTagEndPosition - $lastOpeningTagStartPosition;

                // replace content from latest tag start to latest tag end
                $tempContent = substr($tempContent, 0, $lastOpeningTagStartPosition) . substr($tempContent, $closingTagEndPosition);
            }
        } while (
            ($nextMatchingEndTagPosition !== false && $nextSameTypeTagPosition !== false) &&
            $nextSameTypeTagPosition < $nextMatchingEndTagPosition
        );

        // if no closing tag is found we use length of the whole content
        $endingOffset = strlen($tempContent);
        if ($nextMatchingEndTagPosition !== false) {
            $endingOffset = $nextMatchingEndTagPosition + $offsetCount;
        }

        return $endingOffset;
    }

    protected function shallDebug(): bool
    {
        $tsfe = $this->getTypoScriptFrontendController();
        if ($tsfe !== null && isset($tsfe->config['config']['debug'])) {
            return (bool)($tsfe->config['config']['debug']);
        }
        return !empty($GLOBALS['TYPO3_CONF_VARS']['FE']['debug']);
    }

    /**
     * @todo: This getRequest() is still a bit messy.
     *        Underling code depends on both, a ContentObjectRenderer instance and a request,
     *        but the API currently only passes one or the other. For instance Extbase and Fluid
     *        only pass the Request, DataProcessors only a ContentObjectRenderer.
     *        This is why getRequest() is currently public here.
     *        A potential refactoring could:
     *        * Create interfaces to pass both where needed (or pass a combined context object)
     *        * Deprecate access to getRequest() here afterwards
     *        A circular dependency that the instance of ContentObjectRenderer holds a
     *        request with the instance of itself as attribute must be avoided.
     *        This is currently achieved by adding a new request with
     *        $this->request->withAttribute('currentContentObject', $cObj) in code that needs
     *        it, but this new request is NOT passed back into the ContentObjectRenderer instance.
     *
     * @internal This method might be deprecated with TYPO3 v13.
     */
    public function getRequest(): ServerRequestInterface
    {
        if ($this->request instanceof ServerRequestInterface) {
            return $this->request;
        }
        if (($GLOBALS['TYPO3_REQUEST'] ?? null) instanceof ServerRequestInterface) {
            // @todo: We may want to deprecate this fallback and force consumers
            //        to setRequest() after object instantiation / unserialization instead.
            return $GLOBALS['TYPO3_REQUEST'];
        }
        throw new ContentRenderingException(
            'PSR-7 request is missing in ContentObjectRenderer. Inject with start(), setRequest() or provide via $GLOBALS[\'TYPO3_REQUEST\'].',
            1607172972
        );
    }
}
