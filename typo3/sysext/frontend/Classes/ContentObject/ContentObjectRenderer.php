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
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LogLevel;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\CacheTag;
use TYPO3\CMS\Core\Configuration\Features;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\LanguageAspect;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Crypto\HashService;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;
use TYPO3\CMS\Core\Database\Query\QueryHelper;
use TYPO3\CMS\Core\Database\Query\Restriction\DocumentTypeExclusionRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\FrontendRestrictionContainer;
use TYPO3\CMS\Core\Domain\DateTimeFactory;
use TYPO3\CMS\Core\Domain\Record;
use TYPO3\CMS\Core\Domain\RecordInterface;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Html\HtmlCropper;
use TYPO3\CMS\Core\Html\HtmlParser;
use TYPO3\CMS\Core\Html\SanitizerBuilderFactory;
use TYPO3\CMS\Core\Html\SanitizerInitiator;
use TYPO3\CMS\Core\Imaging\ImageManipulation\Area;
use TYPO3\CMS\Core\Imaging\ImageManipulation\CropVariantCollection;
use TYPO3\CMS\Core\Imaging\ImageResource;
use TYPO3\CMS\Core\Localization\DateFormatter;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Localization\Locales;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Page\DefaultJavaScriptAssetTrait;
use TYPO3\CMS\Core\Page\PageLayoutResolver;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Resource\Exception;
use TYPO3\CMS\Core\Resource\Exception\ResourceDoesNotExistException;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\FolderInterface;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Schema\Capability\TcaSchemaCapability;
use TYPO3\CMS\Core\Schema\TcaSchema;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Service\FlexFormService;
use TYPO3\CMS\Core\Text\TextCropper;
use TYPO3\CMS\Core\TimeTracker\TimeTracker;
use TYPO3\CMS\Core\Type\BitSet;
use TYPO3\CMS\Core\TypoScript\TypoScriptService;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\DebugUtility;
use TYPO3\CMS\Core\Utility\Exception\MissingArrayPathException;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\CMS\Core\Versioning\VersionState;
use TYPO3\CMS\Frontend\Cache\CacheLifetimeCalculator;
use TYPO3\CMS\Frontend\ContentObject\Event\AfterContentObjectRendererInitializedEvent;
use TYPO3\CMS\Frontend\ContentObject\Event\AfterGetDataResolvedEvent;
use TYPO3\CMS\Frontend\ContentObject\Event\AfterImageResourceResolvedEvent;
use TYPO3\CMS\Frontend\ContentObject\Event\AfterStdWrapFunctionsExecutedEvent;
use TYPO3\CMS\Frontend\ContentObject\Event\AfterStdWrapFunctionsInitializedEvent;
use TYPO3\CMS\Frontend\ContentObject\Event\BeforeStdWrapContentStoredInCacheEvent;
use TYPO3\CMS\Frontend\ContentObject\Event\BeforeStdWrapFunctionsExecutedEvent;
use TYPO3\CMS\Frontend\ContentObject\Event\BeforeStdWrapFunctionsInitializedEvent;
use TYPO3\CMS\Frontend\ContentObject\Exception\ContentRenderingException;
use TYPO3\CMS\Frontend\ContentObject\Exception\ExceptionHandlerInterface;
use TYPO3\CMS\Frontend\ContentObject\Exception\ProductionExceptionHandler;
use TYPO3\CMS\Frontend\Imaging\GifBuilder;
use TYPO3\CMS\Frontend\Page\FrontendUrlPrefix;
use TYPO3\CMS\Frontend\Resource\FilePathSanitizer;
use TYPO3\CMS\Frontend\Typolink\LinkFactory;
use TYPO3\CMS\Frontend\Typolink\LinkResult;
use TYPO3\CMS\Frontend\Typolink\LinkResultInterface;
use TYPO3\CMS\Frontend\Typolink\LinkVarsCalculator;
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

    /**
     * List of stdWrap functions in their correct order
     */
    protected const STD_WRAP_ORDER = [
        BeforeStdWrapFunctionsInitializedEvent::class => 'event',
        'cacheRead' => 'hook', // this is a placeholder for checking if the content is available in cache
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
        AfterStdWrapFunctionsInitializedEvent::class => 'event',
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
        BeforeStdWrapFunctionsExecutedEvent::class => 'event',
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
        'cacheStore' => 'hook', // this is a placeholder for storing the content in cache
        AfterStdWrapFunctionsExecutedEvent::class => 'event',
        'debug' => 'boolean',
        'debug.' => 'array',
        'debugFunc' => 'boolean',
        'debugFunc.' => 'array',
        'debugData' => 'boolean',
        'debugData.' => 'array',
    ];

    /**
     * @var ContainerInterface|null
     */
    protected $container;

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
     * @var string|int|null
     * @internal this property might change and is not part of TYPO3 Core API anymore since TYPO3 v13.0. Use at your own risk
     */
    public $checkPid_badDoktypeList;

    public ?LinkResultInterface $lastTypoLinkResult = null;

    /**
     * @var File|FileReference|Folder|FolderInterface|FileInterface|string|null Current file objects (during iterations over files)
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
     * Request pointer, if injected. Use getRequest() instead of reading this property directly.
     */
    private ?ServerRequestInterface $request = null;

    public function __construct(?ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    public function setRequest(ServerRequestInterface $request): void
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
        unset($vars['logger'], $vars['container'], $vars['request']);
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
        if (is_string($this->currentFile)) {
            [$objectType, $identifier] = explode(':', $this->currentFile, 2);
            try {
                if ($objectType === 'File') {
                    $this->currentFile = GeneralUtility::makeInstance(ResourceFactory::class)->retrieveFileOrFolderObject($identifier);
                } elseif ($objectType === 'FileReference') {
                    $this->currentFile = GeneralUtility::makeInstance(ResourceFactory::class)->getFileReferenceObject((int)$identifier);
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
     * @param array|int|string $data The record data that is rendered.
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

        GeneralUtility::makeInstance(EventDispatcherInterface::class)->dispatch(
            new AfterContentObjectRendererInitializedEvent($this)
        );

        $autoTagging = GeneralUtility::makeInstance(Features::class)->isFeatureEnabled('frontend.cache.autoTagging');
        if (is_array($this->data) && $this->currentRecord !== '' && $autoTagging) {
            $cacheLifetimeCalculator = GeneralUtility::makeInstance(CacheLifetimeCalculator::class);
            $lifetime = $cacheLifetimeCalculator->calculateLifetimeForRow($this->table, $this->data);
            $cacheTags = [
                sprintf('%s_%s', $this->table, ($this->data['uid'] ?? 0)),
            ];
            if ((int)($this->data['_LOCALIZED_UID'] ?? 0) > 0) {
                $cacheTags[] = sprintf('%s_%s', $this->table, (int)$this->data['_LOCALIZED_UID']);
            }
            $this->request?->getAttribute('frontend.cache.collector')?->addCacheTags(
                ...array_map(
                    static fn(string $cacheTag) => new CacheTag($cacheTag, $lifetime),
                    $cacheTags,
                ),
            );
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
     * Sets the internal variable parentRecord with information about current record.
     * If the ContentObjectRender was started from CONTENT, RECORD or SEARCHRESULT cObject's this array has two keys, 'data' and 'currentRecord' which indicates the record and data for the parent cObj.
     *
     * @param array $data The record array
     * @param string $currentRecord This is set to the [table]:[uid] of the record delivered in the $data-array, if the cObjects CONTENT or RECORD is in operation.
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
        } catch (\Throwable $exception) {
            $exceptionHandler = $this->createExceptionHandler($configuration);
            if ($exceptionHandler === null) {
                throw $exception;
            }
            // Ensure that the exception handler receives an \Exception instance,
            // which is required by the \ExceptionHandlerInterface.
            if (!$exception instanceof \Exception) {
                $exception = new \Exception($exception->getMessage(), 1698347363, $exception);
            }
            $content = $exceptionHandler->handle($exception, $contentObject, $configuration);
        }

        // Store cache
        if ($cacheConfiguration !== null && $this->getRequest()->getAttribute('frontend.cache.instruction')->isCachingAllowed()) {
            $key = $this->calculateCacheKey($cacheConfiguration);
            if (!empty($key)) {
                $cacheFrontend = GeneralUtility::makeInstance(CacheManager::class)->getCache('hash');
                $tags = $this->calculateCacheTags($cacheConfiguration);
                $cacheLifetime = $this->calculateCacheLifetime($cacheConfiguration);
                $cachedData = [
                    'content' => $content,
                    'cacheTags' => $tags,
                ];
                $cacheFrontend->set($key, $cachedData, $tags, $cacheLifetime);

                // If no tags are given, we restrict the maximum lifetime of the cache to the lifetime of the cache entry.
                if ($tags === []) {
                    $this->getRequest()->getAttribute('frontend.cache.collector')->restrictMaximumLifetime($cacheLifetime);
                }

                $this->getRequest()->getAttribute('frontend.cache.collector')->addCacheTags(
                    ...array_map(fn(string $tag) => new CacheTag($tag, $cacheLifetime), $tags)
                );
            }
        }

        return $content;
    }

    /**
     * Creates the content object exception handler from local content object configuration
     * or, from global configuration if not explicitly disabled in local configuration
     *
     * @throws ContentRenderingException
     */
    protected function createExceptionHandler(array $configuration): ?ExceptionHandlerInterface
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
     */
    protected function determineExceptionHandlerClassName(array $configuration): ?string
    {
        $typoScriptConfigArray = $this->getRequest()->getAttribute('frontend.typoscript')->getConfigArray();
        $exceptionHandlerClassName = null;
        if (!isset($typoScriptConfigArray['contentObjectExceptionHandler'])) {
            if (Environment::getContext()->isProduction()) {
                $exceptionHandlerClassName = '1';
            }
        } else {
            $exceptionHandlerClassName = $typoScriptConfigArray['contentObjectExceptionHandler'];
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
     */
    protected function mergeExceptionHandlerConfiguration(array $configuration): array
    {
        $exceptionHandlerConfiguration = [];
        $typoScriptConfigArray = $this->getRequest()->getAttribute('frontend.typoscript')->getConfigArray();
        if (!empty($typoScriptConfigArray['contentObjectExceptionHandler.'])) {
            $exceptionHandlerConfiguration = $typoScriptConfigArray['contentObjectExceptionHandler.'];
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
    public function getSlidePids($pidList, $pidConf): string
    {
        // todo: phpstan states that $pidConf always exists and is not nullable. At the moment, this is a false positive
        //       as null can be passed into this method via $pidConf. As soon as more strict types are used, this isset
        //       check must be replaced with a more appropriate check like empty or count.
        $pidList = isset($pidConf) ? trim((string)$this->stdWrap($pidList, $pidConf)) : trim($pidList);
        if ($pidList === '') {
            $pidList = 'this';
        }
        $pageRepository = $this->getPageRepository();
        $listArr = null;
        if (trim($pidList)) {
            $contentPid = $this->getRequest()->getAttribute('frontend.page.information')->getContentFromPid();
            $listArr = GeneralUtility::intExplode(',', str_replace('this', (string)$contentPid, $pidList));
            $listArr = $this->checkPidArray($listArr);
        }
        $pidList = [];
        if (is_array($listArr) && !empty($listArr)) {
            foreach ($listArr as $uid) {
                $page = $pageRepository->getPage((int)$uid);
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
            $hashService = GeneralUtility::makeInstance(HashService::class);
            $hmac = $hashService->hmac(implode('|', [$file->getUid(), $parametersEncoded]), 'tx_cms_showpic');
            $params = '&md5=' . $hmac;
            foreach (str_split($parametersEncoded, 64) as $index => $chunk) {
                $params .= '&parameters' . rawurlencode('[') . $index . rawurlencode(']') . '=' . rawurlencode($chunk);
            }
            $absRefPrefix = GeneralUtility::makeInstance(FrontendUrlPrefix::class)->getUrlPrefix($this->getRequest());
            $url = $absRefPrefix . 'index.php?eID=tx_cms_showpic&file=' . $file->getUid() . $params;
            $directImageLink = $this->stdWrapValue('directImageLink', $conf ?? []);
            if ($directImageLink) {
                $imgResourceConf = [
                    'file' => $imageFile,
                    'file.' => $conf,
                ];
                $url = $this->cObjGetSingle('IMG_RESOURCE', $imgResourceConf);
                if (!$url) {
                    // Either imagemagick/gm is not available or image URL could not be resolved due to invalid image file
                    if ($imageFile instanceof File || $imageFile instanceof FileReference) {
                        $url = $imageFile->getPublicUrl();
                    } else {
                        $url = $imageFile;
                    }
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

                if ($file instanceof ProcessedFile) {
                    // TypoScript record delivered like 'file = fileadmin/something.jpg' which can result
                    // in an already processed file. Process the original file with the proper config now.
                    $file = $file->getOriginalFile();
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
                    $paramString .= htmlspecialchars($paramKey) . '=' . htmlspecialchars((string)$paramValue) . ',';
                }

                $attrs = [
                    'href' => (string)$url,
                    'data-window-url' => $url,
                    'data-window-target' => $newWindow ? md5((string)$url) : 'thePicture',
                    'data-window-features' => rtrim($paramString, ','),
                ];
                if ($target !== '') {
                    $attrs['target'] = $target;
                }

                $typoScriptConfigArray = $this->getRequest()->getAttribute('frontend.typoscript')->getConfigArray();
                $a1 = sprintf(
                    '<a %s%s>',
                    GeneralUtility::implodeAttributes($attrs, true),
                    trim($typoScriptConfigArray['ATagParams'] ?? '') ? ' ' . trim($typoScriptConfigArray['ATagParams']) : ''
                );
                $a2 = '</a>';
                $this->addDefaultFrontendJavaScript($this->getRequest());
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
     * @param RecordInterface|int|string|float|null $item a record objet or a Unix timestamp (number of seconds since 1970)
     */
    public function lastChanged(RecordInterface|int|string|float|null $item): void
    {
        if (MathUtility::canBeInterpretedAsInteger($item)) {
            $item = (int)$item;
        } elseif ($item instanceof Record) {
            $item = $item->getSystemProperties()->getLastUpdatedAt()->getTimestamp();
        } else {
            return;
        }
        $pageParts = $this->getRequest()->getAttribute('frontend.page.parts');
        if ($item > $pageParts->getLastChanged()) {
            $pageParts->setLastChanged($item);
        }
    }

    /***********************************************
     *
     * HTML template processing functions
     *
     ***********************************************/

    /**
     * Sets the current file object during iterations over files.
     *
     * @param File|FileReference|Folder|FileInterface|FolderInterface|string|null $fileObject The file object.
     */
    public function setCurrentFile($fileObject)
    {
        $this->currentFile = $fileObject;
    }

    /**
     * Gets the current file object during iterations over files.
     *
     * @return File|FileReference|Folder|FileInterface|FolderInterface|string|null The current file object.
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
        if (!is_array($conf) || !$conf) {
            return $content;
        }

        // Activate the stdWrap PSR-14 Events - They will be executed
        // as stdWrap functions, based on the STD_WRAP_ORDER constant.
        $conf[BeforeStdWrapFunctionsInitializedEvent::class] = 1;
        $conf[AfterStdWrapFunctionsInitializedEvent::class] = 1;
        $conf[BeforeStdWrapFunctionsExecutedEvent::class] = 1;
        $conf[AfterStdWrapFunctionsExecutedEvent::class] = 1;
        $eventDispatcher = GeneralUtility::makeInstance(EventDispatcherInterface::class);

        // Cache handling
        if (is_array($conf['cache.'] ?? null)) {
            $conf['cache.']['key'] = $this->stdWrapValue('key', $conf['cache.']);
            $conf['cache.']['tags'] = $this->stdWrapValue('tags', $conf['cache.']);
            $conf['cache.']['lifetime'] = $this->stdWrapValue('lifetime', $conf['cache.']);
            $conf['cacheRead'] = 1;
            $conf['cacheStore'] = 1;
        }
        // The configuration is sorted and filtered by intersection with the defined STD_WRAP_ORDER.
        $sortedConf = array_keys(array_intersect_key(self::STD_WRAP_ORDER, $conf));
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
                $functionType = self::STD_WRAP_ORDER[$functionName] ?? '';
                // If there is any code on the next level, check if it contains "official" stdWrap functions
                // if yes, execute them first - will make each function stdWrap aware
                // so additional stdWrap calls within the functions can be removed, since the result will be the same
                if (!empty($conf[$functionProperties]) && !GeneralUtility::inList($stdWrapDisabledFunctionTypes, $functionType)) {
                    if (array_intersect_key(self::STD_WRAP_ORDER, $conf[$functionProperties])) {
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
                    // Hand over the whole $conf array to the hooks
                    if ($functionType === 'hook') {
                        $singleConf = $conf;
                    }
                    // Add both keys - with and without the dot - to the set of executed functions
                    $isExecuted[$functionName] = true;
                    $isExecuted[$functionProperties] = true;
                    if ($functionType === 'event') {
                        // @phpstan-ignore-next-line phpstan does not understand $functionName is only called on 'event' types
                        $content = $eventDispatcher->dispatch(new $functionName($content, $conf, $this))->getContent();
                    } else {
                        // Call the function with the prefix stdWrap_ to make sure nobody can execute functions just by adding their name to the TS Array
                        $functionName = 'stdWrap_' . $functionName;
                        $content = $this->{$functionName}($content, $singleConf);
                    }
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
            $this->getRequest()->getAttribute('frontend.cache.collector')->addCacheTags(
                ...array_map(fn(string $tag) => new CacheTag($tag), $cacheTags)
            );
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
        // @todo: Check when/if there are scenarios where attribute 'language' is not yet set in $request.
        $siteLanguage = $this->getRequest()->getAttribute('language') ?? $this->getRequest()->getAttribute('site')->getDefaultLanguage();
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
            $type = (string)$this->stdWrapValue('type', $conf['strPad.']);
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
        // @todo: Check when/if there are scenarios where attribute 'language' is not yet set in $request.
        $language = $this->getRequest()->getAttribute('language') ?? $this->getRequest()->getAttribute('site')->getDefaultLanguage();
        $locale = $conf['formattedDate.']['locale'] ?? $language->getLocale();

        if ($content === '' || $content === '0') {
            $content = GeneralUtility::makeInstance(Context::class)->getAspect('date')->getDateTime();
        } else {
            // format this to a timestamp now
            $content = strtotime((MathUtility::canBeInterpretedAsInteger($content) ? '@' : '') . $content);
            if ($content === false) {
                $content = GeneralUtility::makeInstance(Context::class)->getAspect('date')->getDateTime();
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
        return strip_tags((string)$content);
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
            $content = htmlspecialchars((string)$content, ENT_COMPAT, 'UTF-8', false);
        } else {
            $content = htmlspecialchars((string)$content);
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
        $substKey = 'INT_SCRIPT.' . md5(StringUtility::getUniqueId());
        $pageParts = $this->getRequest()->getAttribute('frontend.page.parts');
        $pageParts->addNotCachedContentElement([
            'substKey' => $substKey,
            'content' => $content,
            'postUserFunc' => $conf['postUserFuncInt'],
            'conf' => $conf['postUserFuncInt.'],
            'type' => 'POSTUSERFUNC',
            'cObj' => serialize($this),
        ]);
        return '<!--' . $substKey . '-->';
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
        $typoScriptConfigArray = $this->getRequest()->getAttribute('frontend.typoscript')->getConfigArray();
        if (
            (!isset($typoScriptConfigArray['disablePrefixComment']) || !$typoScriptConfigArray['disablePrefixComment'])
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
     * @param string|null $content Input value undergoing processing in these functions.
     * @param array $conf All stdWrap properties, not just the ones for a particular function.
     * @return string|null The processed input value
     */
    public function stdWrap_cacheStore($content = '', $conf = []): ?string
    {
        if (!isset($conf['cache.'])) {
            return $content;
        }
        $key = $this->calculateCacheKey($conf['cache.']);
        if (empty($key)) {
            return $content;
        }

        $event = GeneralUtility::makeInstance(EventDispatcherInterface::class)->dispatch(
            new BeforeStdWrapContentStoredInCacheEvent(
                content: $content,
                tags: $this->calculateCacheTags($conf['cache.']),
                key: (string)$key,
                lifetime: $this->calculateCacheLifetime($conf['cache.']),
                configuration: $conf,
                contentObjectRenderer: $this
            )
        );

        GeneralUtility::makeInstance(CacheManager::class)
            ->getCache('hash')
            ->set(
                $event->getKey(),
                ['content' => $event->getContent(), 'cacheTags' => $event->getTags()],
                $event->getTags(),
                $event->getLifetime()
            );

        // If no tags are given, we restrict the maximum lifetime of the cache to the lifetime of the cache entry.
        if ($event->getTags() === []) {
            $this->getRequest()->getAttribute('frontend.cache.collector')->restrictMaximumLifetime($event->getLifetime());
        }

        $this->getRequest()->getAttribute('frontend.cache.collector')->addCacheTags(
            ...array_map(fn(string $tag) => new CacheTag($tag, $event->getLifetime()), $event->getTags())
        );
        return $event->getContent();
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
        if ($temp === ['']) {
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
        if ($valArr !== [''] && (MathUtility::canBeInterpretedAsInteger($conf['returnKey'] ?? null) || ($conf['returnKey.'] ?? false))) {
            $key = (int)$this->stdWrapValue('returnKey', $conf ?? []);
            return $valArr[$key] ?? '';
        }

        // return the amount of elements. No further processing
        if ($valArr !== [''] && (($conf['returnCount'] ?? false) || ($conf['returnCount.'] ?? false))) {
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
        $cObjNumSplitConf = isset($conf['cObjNum.']) ? $this->stdWrap($conf['cObjNum'] ?? '', $conf['cObjNum.']) : (string)($conf['cObjNum'] ?? '');
        $splitArr = [];
        if ($wrap !== '' || $cObjNumSplitConf !== '') {
            $splitArr['wrap'] = $wrap;
            $splitArr['cObjNum'] = $cObjNumSplitConf;
            $splitArr = GeneralUtility::makeInstance(TypoScriptService::class)
                ->explodeConfigurationForOptionSplit($splitArr, $splitCount);
        }
        $content = '';
        for ($a = 0; $a < $splitCount; $a++) {
            $this->getRequest()->getAttribute('frontend.register.stack')->current()->set('SPLIT_COUNT', $a);
            $value = $valArr[$a];
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
                    $splitCount = preg_match_all($search, $content);
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
                $splitCount = preg_match_all($searchPreg, $content);
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
        $type = $this->stdWrapValue('roundType', $conf);
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

        $allowTags = GeneralUtility::trimExplode(',', strtolower($conf['allowTags'] ?? ''), true);
        if (in_array('*', $allowTags, true)) {
            $allowTags = ['*'];
        }
        $denyTags = GeneralUtility::trimExplode(',', strtolower($conf['denyTags'] ?? ''), true);
        if (in_array('*', $denyTags, true)) {
            $denyTags = ['*'];
        }
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
                        $ordValue = strtolower(substr($theValue, $pointer + $len_p, 1));
                        $endChar = empty($ordValue) ? 0 : ord($ordValue);
                        unset($ordValue);
                        $c--;
                    } while ($c > 0 && $endChar && ($endChar < 97 || $endChar > 122) && $endChar != 47);
                    $len = $len_p - 1;
                } else {
                    $len = $this->getContentLengthOfCurrentTag($theValue, $pointer, (string)$currentTag[0]);
                }
                // $data is the content until the next <tag-start or end is detected.
                // In case of a currentTag set, this would mean all data between the start- and end-tags
                $data = substr($theValue, $pointer, $len);
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
                $contentAccum[$contentAccumP] = ($contentAccum[$contentAccumP] ?? '') . $data;
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
                $tag = preg_split('/[\t\n\f ]/', trim($tagContent), 2);
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
                            foreach ($params as $option => $val) {
                                // contains non-encoded values
                                $this->parameters[strtolower($option)] = $val;
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
                    $contentAccum[$contentAccumP] = $contentAccum[$contentAccumP] ?? '';
                    // If a tag was not a typo tag, then it is just added to the content
                    $stripNL = false;
                    if (
                        // Neither allowTags or denyTags set, thus everything is allowed
                        ($denyTags === [] && $allowTags === [])
                        // Explicitly allowed
                        || ($allowTags !== [] && in_array((string)$tag[0], $allowTags, true))
                        // Explicitly denied or everything "denied" (except for the explicitly allowed)
                        || ($denyTags !== [] && $denyTags !== ['*'] && !in_array((string)$tag[0], $denyTags))
                        // All tags are allowed, but not in the denied list above, so this is OK
                        || ($allowTags === ['*'] && !in_array((string)$tag[0], $denyTags))
                    ) {
                        $contentAccum[$contentAccumP] .= $data;
                    } else {
                        $contentAccum[$contentAccumP] .= htmlspecialchars($data);
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
            if (isset($l[0]) && $l[0] === '<' && str_ends_with($l, '>')) {
                $fwParts = explode('>', substr($l, 1), 2);
                [$tagName] = explode(' ', $fwParts[0], 2);
                if (!$fwParts[1]) {
                    if (str_ends_with($tagName, '/')) {
                        $tagName = substr($tagName, 0, -1);
                    }
                    if (str_ends_with($fwParts[0], '/')) {
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
        $textstr = $textpieces[0];
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
     * @return ImageResource|null
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
            $gifBuilder = GeneralUtility::makeInstance(GifBuilder::class);
            $gifBuilder->start($fileArray, $this->data);
            $imageResource = $gifBuilder->gifBuild();
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
                            $fileReference = $this->getResourceFactory()->getFileReferenceObject((int)$file);
                            $fileObject = $fileReference->getOriginalFile();
                        } else {
                            $fileObject = $this->getResourceFactory()->getFileObject((int)$file);
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
                if ($fileReference === null) {
                    $processingConfiguration['crop'] = $this->getCropAreaFromFromTypoScriptSettings($fileObject, $fileArray);
                } else {
                    $processingConfiguration['crop'] = $this->getCropAreaFromFileReference($fileReference, $fileArray);
                }

                // Possibility to cancel/force profile extraction
                // see $GLOBALS['TYPO3_CONF_VARS']['GFX']['processor_stripColorProfileParameters']
                if (isset($fileArray['stripProfile'])) {
                    $processingConfiguration['stripProfile'] = $fileArray['stripProfile'];
                }
                // Check if we can handle this type of file for editing
                if ($fileObject->isImage()) {
                    $maskArray = $fileArray['m.'] ?? false;
                    // Must render mask images and include in hash-calculating
                    // - otherwise we cannot be sure the filename is unique for the setup!
                    if (is_array($maskArray)) {
                        $processingConfiguration['maskImages']['maskImage'] = $this->getImgResource($maskArray['mask'] ?? '', $maskArray['mask.'] ?? [])?->getProcessedFile();
                        $processingConfiguration['maskImages']['backgroundImage'] = $this->getImgResource($maskArray['bgImg'] ?? '', $maskArray['bgImg.'] ?? [])?->getProcessedFile();
                        $processingConfiguration['maskImages']['maskBottomImage'] = $this->getImgResource($maskArray['bottomImg'] ?? '', $maskArray['bottomImg.'] ?? [])?->getProcessedFile();
                        $processingConfiguration['maskImages']['maskBottomImageMask'] = $this->getImgResource($maskArray['bottomImg_mask'] ?? '', $maskArray['bottomImg_mask.'] ?? [])?->getProcessedFile();
                    }
                    $processedFileObject = $fileObject->process(ProcessedFile::CONTEXT_IMAGECROPSCALEMASK, $processingConfiguration);
                    if ($processedFileObject->isProcessed()) {
                        $imageResource = ImageResource::createFromProcessedFile($processedFileObject);
                    }
                }
            } elseif ($fileObject instanceof ProcessedFile) {
                $imageResource = ImageResource::createFromProcessedFile($fileObject);
            }
        }

        return GeneralUtility::makeInstance(EventDispatcherInterface::class)->dispatch(
            new AfterImageResourceResolvedEvent($file, $fileArray, $imageResource)
        )->getImageResource();
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
     * Implements the TypoScript data type "getText". This takes a string with parameters
     * and based on those a value from somewhere in the system is returned.
     *
     * @param string $string The parameter string, eg. "field : title" or "field : navtitle // field : title"
     *                       In the latter case and example of how the value is FIRST split by "//" is shown
     * @param array|null $fieldArray Alternative field array; If you set this to an array this variable will be used to
     *                               look up values for the "field" key. Otherwise, the current page record is used.
     * @return string The value fetched
     * @see getFieldVal()
     */
    public function getData($string, $fieldArray = null)
    {
        if (!is_array($fieldArray)) {
            $fieldArray = $this->getRequest()->getAttribute('frontend.page.information')->getPageRecord();
        }
        $retVal = '';
        // @todo: getData should not be called with non-string as $string. example trigger:
        //        SecureHtmlRenderingTest htmlViewHelperAvoidsCrossSiteScripting set #07 PHP 8
        $sections = is_string($string) ? explode('//', $string) : [];
        foreach ($sections as $secVal) {
            if ($retVal) {
                break;
            }
            $parts = explode(':', $secVal, 2);
            $type = strtolower(trim($parts[0]));
            $typesWithOutParameters = ['level', 'date', 'current', 'pagelayout', 'applicationcontext'];
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
                        $valueParts = GeneralUtility::trimExplode('|', $key);
                        if (($valueParts[0] ?? '') === 'fe_user') {
                            $frontendUser = $this->getRequest()->getAttribute('frontend.user');
                            array_shift($valueParts);
                            $retVal = $this->getValueFromRecursiveData($valueParts, $frontendUser);
                        } elseif (($valueParts[0] ?? '') === 'linkVars') {
                            $typoScriptConfigArray = $this->getRequest()->getAttribute('frontend.typoscript')->getConfigArray();
                            $typoScriptConfigLinkVars = (string)($typoScriptConfigArray['linkVars'] ?? '');
                            $retVal = GeneralUtility::makeInstance(LinkVarsCalculator::class)
                                ->getAllowedLinkVarsFromRequest(
                                    $typoScriptConfigLinkVars,
                                    $this->getRequest()->getQueryParams(),
                                    GeneralUtility::makeInstance(Context::class)
                                );
                        } elseif (($valueParts[0] ?? '') === 'id') {
                            $retVal = $this->getRequest()->getAttribute('frontend.page.information')->getId();
                        } elseif (($valueParts[0] ?? '') === 'contentPid') {
                            $retVal = $this->getRequest()->getAttribute('frontend.page.information')->getContentFromPid();
                        } elseif (($valueParts[0] ?? '') === 'rootLine') {
                            array_shift($valueParts);
                            $retVal = $this->getValueFromRecursiveData($valueParts, $this->getRequest()->getAttribute('frontend.page.information')->getRootLine());
                        } elseif (($valueParts[0] ?? '') === 'page') {
                            array_shift($valueParts);
                            $retVal = $this->getValueFromRecursiveData($valueParts, $this->getRequest()->getAttribute('frontend.page.information')->getPageRecord());
                        } elseif (($valueParts[0] ?? '') === 'config' && ($valueParts[1] ?? '') === 'rootLine') {
                            array_shift($valueParts);
                            array_shift($valueParts);
                            $retVal = $this->getValueFromRecursiveData($valueParts, $this->getRequest()->getAttribute('frontend.page.information')->getLocalRootLine());
                        }
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
                    case 'asset':
                        $absoluteFilePath = GeneralUtility::getFileAbsFileName($key);
                        if ($absoluteFilePath === '') {
                            throw new \RuntimeException('Asset "' . $key . '" not found', 1670713983);
                        }
                        $retVal = PathUtility::getAbsoluteWebPath(GeneralUtility::createVersionNumberedFilename($absoluteFilePath));
                        break;
                    case 'parameters':
                        $retVal = $this->parameters[$key] ?? null;
                        break;
                    case 'register':
                        if ($key === 'SYS_LASTCHANGED') {
                            // b/w compat layer: SYS_LASTCHANGED has been a register entry until TYPO3 v14. It is now part
                            // of a request attribute. The register access via TS should continue to work, though.
                            $retVal = $this->getRequest()->getAttribute('frontend.page.parts')->getLastChanged();
                        } else {
                            $retVal = $this->getRequest()->getAttribute('frontend.register.stack')->current()->get($key);
                        }
                        break;
                    case 'global':
                        $retVal = $this->getGlobal($key);
                        break;
                    case 'level':
                        $localRootLine = $this->getRequest()->getAttribute('frontend.page.information')->getLocalRootLine();
                        $retVal = count($localRootLine) - 1;
                        break;
                    case 'leveltitle':
                        $keyParts = GeneralUtility::trimExplode(',', $key);
                        $pointer = (int)($keyParts[0] ?? 0);
                        $slide = $keyParts[1] ?? '';
                        $localRootLine = $this->getRequest()->getAttribute('frontend.page.information')->getLocalRootLine();
                        $numericKey = $this->getKey($pointer, $localRootLine);
                        $retVal = $this->rootLineValue($numericKey, 'title', strtolower($slide) === 'slide');
                        break;
                    case 'levelmedia':
                        $keyParts = GeneralUtility::trimExplode(',', $key);
                        $pointer = (int)($keyParts[0] ?? 0);
                        $slide = $keyParts[1] ?? '';
                        $localRootLine = $this->getRequest()->getAttribute('frontend.page.information')->getLocalRootLine();
                        $numericKey = $this->getKey($pointer, $localRootLine);
                        $retVal = $this->rootLineValue($numericKey, 'media', strtolower($slide) === 'slide');
                        break;
                    case 'leveluid':
                        $localRootLine = $this->getRequest()->getAttribute('frontend.page.information')->getLocalRootLine();
                        $numericKey = $this->getKey((int)$key, $localRootLine);
                        $retVal = $this->rootLineValue($numericKey, 'uid');
                        break;
                    case 'levelfield':
                        $keyParts = GeneralUtility::trimExplode(',', $key);
                        $pointer = (int)($keyParts[0] ?? 0);
                        $field = $keyParts[1] ?? '';
                        $slide = $keyParts[2] ?? '';
                        $localRootLine = $this->getRequest()->getAttribute('frontend.page.information')->getLocalRootLine();
                        $numericKey = $this->getKey($pointer, $localRootLine);
                        $retVal = $this->rootLineValue($numericKey, $field, strtolower($slide) === 'slide');
                        break;
                    case 'fullrootline':
                        $keyParts = GeneralUtility::trimExplode(',', $key);
                        $pointer = (int)($keyParts[0] ?? 0);
                        $field = $keyParts[1] ?? '';
                        $slide = $keyParts[2] ?? '';
                        $rootLine = $this->getRequest()->getAttribute('frontend.page.information')->getRootLine();
                        $localRootLine = $this->getRequest()->getAttribute('frontend.page.information')->getLocalRootLine();
                        $fullKey = $pointer - count($localRootLine) + count($rootLine);
                        if ($fullKey >= 0) {
                            $retVal = $this->rootLineValue($fullKey, $field, stristr($slide, 'slide') !== false, $rootLine);
                        }
                        break;
                    case 'date':
                        if (!$key) {
                            $key = 'd/m Y';
                        }
                        $retVal = date($key, $GLOBALS['EXEC_TIME']);
                        break;
                    case 'page':
                        $pageRecord = $this->getRequest()->getAttribute('frontend.page.information')->getPageRecord();
                        $retVal = $pageRecord[$key] ?? '';
                        break;
                    case 'pagelayout':
                        $pageInformation = $this->getRequest()->getAttribute('frontend.page.information');
                        $pageLayoutResolver = GeneralUtility::makeInstance(PageLayoutResolver::class);
                        $retVal = $pageLayoutResolver->getLayoutIdentifierForPage($pageInformation->getPageRecord(), $pageInformation->getRootLine());
                        break;
                    case 'current':
                        $retVal = $this->data[$this->currentValKey] ?? null;
                        break;
                    case 'db':
                        $selectParts = GeneralUtility::trimExplode(':', $key, true);
                        if (!isset($selectParts[1])) {
                            break;
                        }
                        $pageRepository = $this->getPageRepository();
                        $dbRecord = $pageRepository->getRawRecord($selectParts[0], (int)$selectParts[1]);
                        if (is_array($dbRecord) && isset($selectParts[2])) {
                            $retVal = $dbRecord[$selectParts[2]] ?? '';
                        }
                        break;
                    case 'lll':
                        // @todo: Check when/if there are scenarios where attribute 'language' is not yet set in $request.
                        $language = $this->getRequest()->getAttribute('language') ?? $this->getRequest()->getAttribute('site')->getDefaultLanguage();
                        $languageService = GeneralUtility::makeInstance(LanguageServiceFactory::class)->createFromSiteLanguage($language);
                        $retVal = $languageService->sL('LLL:' . $key);
                        break;
                    case 'path':
                        try {
                            $retVal = GeneralUtility::makeInstance(FilePathSanitizer::class)->sanitize($key);
                        } catch (Exception) {
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
                                $retVal = DebugUtility::viewArray($this->getRequest()->getAttribute('frontend.page.information')->getLocalRootLine());
                                break;
                            case 'fullRootLine':
                                $retVal = DebugUtility::viewArray($this->getRequest()->getAttribute('frontend.page.information')->getRootLine());
                                break;
                            case 'data':
                                $retVal = DebugUtility::viewArray($this->data);
                                break;
                            case 'register':
                                $retVal = DebugUtility::viewArray($this->getRequest()->getAttribute('frontend.register.stack')->current());
                                break;
                            case 'page':
                                $retVal = DebugUtility::viewArray($this->getRequest()->getAttribute('frontend.page.information')->getPageRecord());
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
                        $retVal = $this->getRequest()->getAttribute('frontend.user')->getSessionData($sessionKey);
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
                        $site = $this->getRequest()->getAttribute('site');
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
                        // @todo: Check when/if there are scenarios where attribute 'language' is not yet set in $request.
                        $siteLanguage = $this->getRequest()->getAttribute('language') ?? $this->getRequest()->getAttribute('site')->getDefaultLanguage();
                        if ($key === 'twoLetterIsoCode') {
                            $key = 'locale:languageCode';
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
                                $retVal = $config[$key];
                            }
                        }
                        break;
                    case 'sitesettings':
                        $siteSettings = $this->getRequest()->getAttribute('site')->getSettings();
                        $retVal = $siteSettings->get($key, '');
                        break;
                    case 'applicationcontext':
                        $retVal = Environment::getContext()->__toString();
                        break;
                }
            }
        }

        return GeneralUtility::makeInstance(EventDispatcherInterface::class)->dispatch(
            new AfterGetDataResolvedEvent($string, $fieldArray, $retVal, $this)
        )->getResult();
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
                $fileObject = $fileFactory->getFileObject((int)$fileUidOrCurrentKeyword);
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
     * Returns a value from the current rootline.
     *
     * @param int $key Which level in the root line
     * @param string $field The field in the rootline record to return (a field from the pages table)
     * @param bool $slideBack If set, then we will traverse through the rootline from outer level towards the root level until the value found is TRUE
     * @param mixed $altRootLine If you supply an array for this it will be used as an alternative root line array
     * @return string The value from the field of the rootline.
     * @internal
     * @see getData()
     */
    protected function rootLineValue($key, $field, $slideBack = false, $altRootLine = ''): string
    {
        if (is_array($altRootLine)) {
            $rootLine = $altRootLine;
        } else {
            $rootLine = $this->getRequest()->getAttribute('frontend.page.information')->getLocalRootLine();
        }
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

        $wrap = (string)$this->stdWrapValue('wrap', $conf);
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
        $this->lastTypoLinkResult = null;
        try {
            $linkResult = GeneralUtility::makeInstance(LinkFactory::class)->create($linkText, $conf, $this);
        } catch (UnableToLinkException $e) {
            // URL could not be generated
            throw $e;
        }

        $this->lastTypoLinkResult = $linkResult;
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
            $content = trim($wrapArr[0]) . $content . trim($wrapArr[1] ?? '');
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
     * @param string|int|null $labels The labels of the individual units. Defaults to : ' min| hrs| days| yrs'
     * @return string The formatted string
     */
    public function calcAge($seconds, $labels = null)
    {
        $now = DateTimeFactory::createFromTimestamp($GLOBALS['EXEC_TIME']);
        $then = DateTimeFactory::createFromTimestamp($GLOBALS['EXEC_TIME'] - $seconds);
        // Show past dates without a leading sign, but future dates with.
        // This does not make sense, but is kept for legacy reasons.
        $sign = $then > $now ? '-' : '';
        // Take an absolute diff, since we don't want formatDateInterval to output the (correct) sign
        $diff = $now->diff($then, true);
        $labels = ($labels === null || MathUtility::canBeInterpretedAsInteger($labels)) ? 'min|hrs|days|yrs|min|hour|day|year' : str_replace('"', '', $labels);
        return $sign . (new DateFormatter())->formatDateInterval($diff, $labels);
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
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($table);
        $statement = $this->getQuery($connection, $table, $conf);

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

        $pageRepository = $this->getPageRepository();
        while ($row = $statement->fetchAssociative()) {
            // Versioning preview:
            $pageRepository->versionOL($tableName, $row, true);

            // Language overlay:
            if (is_array($row)) {
                $row = $pageRepository->getLanguageOverlay($tableName, $row);
            }

            // Might be unset in the language overlay
            if (is_array($row)) {
                $records[] = $row;
            }
        }

        if (GeneralUtility::makeInstance(Features::class)->isFeatureEnabled('frontend.cache.autoTagging')) {
            $cacheLifetimeCalculator = GeneralUtility::makeInstance(CacheLifetimeCalculator::class);
            $cacheTags = array_map(fn(array $record) => new CacheTag(
                name: sprintf('%s_%s', $tableName, ($record['uid'] ?? 0)),
                lifetime: $cacheLifetimeCalculator->calculateLifetimeForRow($tableName, $record)
            ), $records);
            $this->getRequest()->getAttribute('frontend.cache.collector')?->addCacheTags(...$cacheTags);
        }

        return $records;
    }

    /**
     * Creates and returns a SELECT query for records from $table and with conditions
     * based on the configuration in the $conf array.
     * Implements the "select" function in TypoScript.
     *
     * @param string $table See ->exec_getQuery()
     * @param array $conf See ->exec_getQuery()
     * @return string A SELECT query
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     * @internal
     * @see numRows()
     */
    public function getQuery(Connection $connection, string $table, array $conf): string
    {
        // Resolve stdWrap in these properties first
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
        $queryMarkers = $this->getQueryMarkers($connection, $conf);
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
                        $storagePid = $this->getRequest()->getAttribute('frontend.page.information')->getId();
                    }
                });
                $pageRepository = $this->getPageRepository();
                $expandedPidList = $pageRepository->getPageIdsRecursive($pidList, $conf['recursive']);
                $conf['pidInList'] = implode(',', $expandedPidList);
            }
        }
        if ((string)($conf['pidInList'] ?? '') === '') {
            $conf['pidInList'] = 'this';
        }

        $queryParts = $this->getQueryConstraints($connection, $table, $conf);

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
            $queryBuilder->selectLiteral($this->sanitizeSelectPart($connection, $conf['selectFields'], $table));
        }

        // Setting LIMIT:
        if (($conf['max'] ?? false) || ($conf['begin'] ?? false)) {
            // Finding the total number of records, if used:
            if (str_contains(strtolower(($conf['begin'] ?? '') . ($conf['max'] ?? '')), 'total')) {
                $countQueryBuilder = $connection->createQueryBuilder();
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
                    $this->getTimeTracker()->setTSlogMessage($e->getMessage());
                    return '';
                }
            }

            if (isset($conf['begin']) && $conf['begin'] > 0) {
                $conf['begin'] = MathUtility::forceIntegerInRange((int)ceil($this->calc($conf['begin'])), 0);
                $queryBuilder->setFirstResult($conf['begin']);
            }
            if (isset($conf['max'])) {
                $conf['max'] = MathUtility::forceIntegerInRange((int)ceil($this->calc($conf['max'])), 0);
                $queryBuilder->setMaxResults($conf['max'] ?: 100000);
            }
        }

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
        return $query;
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
    protected function getQueryConstraints(Connection $connection, string $table, array $conf): array
    {
        $queryBuilder = $connection->createQueryBuilder();
        $expressionBuilder = $queryBuilder->expr();
        $request = $this->getRequest();
        $contentPid = $request->getAttribute('frontend.page.information')->getContentFromPid();
        $constraints = [];
        $pid_uid_flag = 0;
        $enableFieldsIgnore = [];
        $queryParts = [
            'where' => null,
            'groupBy' => null,
            'orderBy' => null,
        ];

        $context = GeneralUtility::makeInstance(Context::class);
        $isInWorkspace = $context->getPropertyFromAspect('workspace', 'isOffline');

        if (trim($conf['uidInList'] ?? '')) {
            $listArr = GeneralUtility::intExplode(',', str_replace('this', (string)$contentPid, $conf['uidInList']));

            // If moved records shall be considered, select via t3ver_oid
            $considerMovePointers = $isInWorkspace && $table !== 'pages' && $this->getTcaSchema($table)?->isWorkspaceAware();
            if ($considerMovePointers) {
                $constraints[] = (string)$expressionBuilder->or(
                    $expressionBuilder->in($table . '.uid', $listArr),
                    $expressionBuilder->and(
                        $expressionBuilder->eq(
                            $table . '.t3ver_state',
                            VersionState::MOVE_POINTER->value
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
            $listArr = GeneralUtility::intExplode(',', str_replace('this', (string)$contentPid, $conf['pidInList']));
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

        $where = trim((string)$this->stdWrapValue('where', $conf));
        if ($where) {
            $constraints[] = QueryHelper::stripLogicalOperatorPrefix($where);
        }

        // Check if the default language should be fetched (= doing overlays), or if only the records of a language should be fetched
        // but only do this for TCA tables that have languages enabled
        $languageConstraint = $this->getLanguageRestriction($expressionBuilder, $table, $conf, $context);
        if ($languageConstraint !== null) {
            $constraints[] = $languageConstraint;
        }

        // default constraints from TCA
        $pageRepository = $this->getPageRepository();
        $constraints = array_merge(
            $constraints,
            array_values($pageRepository->getDefaultConstraints($table, $enableFieldsIgnore))
        );

        // MAKE WHERE:
        if ($constraints !== []) {
            $queryParts['where'] = $expressionBuilder->and(...$constraints);
        }
        // GROUP BY
        $groupBy = trim((string)$this->stdWrapValue('groupBy', $conf));
        if ($groupBy) {
            $queryParts['groupBy'] = QueryHelper::parseGroupBy($groupBy);
        }

        // ORDER BY
        $orderByString = trim((string)$this->stdWrapValue('orderBy', $conf));
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
     * @return string|\TYPO3\CMS\Core\Database\Query\Expression\CompositeExpression|null
     * @throws \TYPO3\CMS\Core\Context\Exception\AspectNotFoundException
     */
    protected function getLanguageRestriction(ExpressionBuilder $expressionBuilder, string $table, array $conf, Context $context)
    {
        $languageField = '';
        $localizationParentField = '';
        $languageCapability = null;
        $schema = $this->getTcaSchema($table);
        if ($schema?->isLanguageAware()) {
            $languageCapability = $schema->getCapability(TcaSchemaCapability::Language);
            $localizationParentField = $languageCapability->getTranslationOriginPointerField()->getName();
        }
        // Check if the table is translatable, and set the language field by default from the TCA information
        if (!empty($conf['languageField']) || !isset($conf['languageField'])) {
            if (isset($conf['languageField']) && $schema?->hasField($conf['languageField'])) {
                $languageField = $conf['languageField'];
            } elseif ($languageCapability) {
                $languageField = $table . '.' . $languageCapability->getLanguageField()->getName();
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
                $includeRecordsWithoutDefaultTranslation = trim((string)$includeRecordsWithoutDefaultTranslation);
                $includeRecordsWithoutDefaultTranslation = $includeRecordsWithoutDefaultTranslation !== '' && $includeRecordsWithoutDefaultTranslation !== '0';
            } else {
                // Option was not explicitly set, check what's in for the language overlay type.
                // OVERLAYS_ON means that we do not include the "floating" records (records without default translation)
                $includeRecordsWithoutDefaultTranslation = $languageAspect->getOverlayType() !== $languageAspect::OVERLAYS_ON;
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
     * @return string Sanitized select part
     * @internal
     * @see getQuery
     */
    protected function sanitizeSelectPart(Connection $connection, string $selectPart, string $table)
    {
        // Pattern matching parts
        $matchStart = '/(^\\s*|,\\s*|' . $table . '\\.)';
        $matchEnd = '(\\s*,|\\s*$)/';
        $necessaryFields = ['uid', 'pid'];
        $wsFields = ['t3ver_state'];
        $schema = $this->getTcaSchema($table);
        if ($schema === null) {
            return $selectPart;
        }

        if (!preg_match($matchStart . '\\*' . $matchEnd, $selectPart) && !preg_match('/(count|max|min|avg|sum)\\([^\\)]+\\)|distinct/i', $selectPart)) {
            foreach ($necessaryFields as $field) {
                $match = $matchStart . $field . $matchEnd;
                if (!preg_match($match, $selectPart)) {
                    $selectPart .= ', ' . $connection->quoteIdentifier($table . '.' . $field) . ' AS ' . $connection->quoteIdentifier($field);
                }
            }
            if ($schema->isLanguageAware()) {
                $languageCapability = $schema->getCapability(TcaSchemaCapability::Language);
                $languageField = $languageCapability->getLanguageField()->getName();
                $match = $matchStart . $languageField . $matchEnd;
                if (!preg_match($match, $selectPart)) {
                    $selectPart .= ', ' . $connection->quoteIdentifier($table . '.' . $languageField) . ' AS ' . $connection->quoteIdentifier($languageField);
                }
            }
            if ($schema->isWorkspaceAware()) {
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
    public function checkPidArray($pageIds): array
    {
        if (!is_array($pageIds) || empty($pageIds)) {
            return [];
        }

        if ($pageIds === [$this->getRequest()->getAttribute('frontend.page.information')->getId()]) {
            // Middlewares already checked access to the current page and made sure the current doktype
            // is a doktype whose content should be rendered, so there is no need to check that again.
            return $pageIds;
        }
        $pageRepository = $this->getPageRepository();
        $restrictionContainer = GeneralUtility::makeInstance(FrontendRestrictionContainer::class);
        if ($this->checkPid_badDoktypeList) {
            $restrictionContainer->add(GeneralUtility::makeInstance(
                DocumentTypeExclusionRestriction::class,
                // @todo this functionality should be streamlined with a default FrontendRestriction or a "LinkRestrictionContainer"
                GeneralUtility::intExplode(',', (string)$this->checkPid_badDoktypeList, true)
            ));
        }
        return $pageRepository->filterAccessiblePageIds($pageIds, $restrictionContainer);
    }

    /**
     * Builds list of marker values for handling PDO-like parameter markers in select parts.
     * Marker values support stdWrap functionality thus allowing a way to use stdWrap functionality in various properties of 'select' AND prevents SQL-injection problems by quoting and escaping of numeric values, strings, NULL values and comma separated lists.
     *
     * @param array $conf Select part of CONTENT definition
     * @return array List of values to replace markers with
     * @internal
     * @see getQuery()
     */
    public function getQueryMarkers(Connection $connection, $conf)
    {
        if (!isset($conf['markers.']) || !is_array($conf['markers.'])) {
            return [];
        }
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

    protected function getResourceFactory(): ResourceFactory
    {
        return GeneralUtility::makeInstance(ResourceFactory::class);
    }

    protected function getPageRepository(): PageRepository
    {
        return GeneralUtility::makeInstance(PageRepository::class);
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
        if (!$this->getRequest()->getAttribute('frontend.cache.instruction')->isCachingAllowed()) {
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
        $this->getRequest()->getAttribute('frontend.cache.collector')->addCacheTags(
            ...array_map(fn(string $tag) => new CacheTag($tag), $cachedData['cacheTags'])
        );
        return $cachedData['content'] ?? false;
    }

    /**
     * Calculates the lifetime of a cache entry based on the given configuration
     */
    protected function calculateCacheLifetime(array $configuration): int
    {
        $configuration['lifetime'] = $configuration['lifetime'] ?? '';
        $lifetimeConfiguration = (string)$this->stdWrapValue('lifetime', $configuration);

        if (strtolower($lifetimeConfiguration) === 'unlimited') {
            $lifetime = 31536000; // unlimited lifetime - 1 year.
        } elseif (strtolower($lifetimeConfiguration) === 'default') {
            $lifetime = $this->getDefaultCachePeriod(); // default lifetime of config.cache_period or 86400 seconds
        } elseif ($lifetimeConfiguration > 0) {
            $lifetime = (int)$lifetimeConfiguration;
        } else {
            // If no lifetime is specified, we use the default cache period.
            $lifetime = $this->getDefaultCachePeriod();
        }
        return $lifetime;
    }

    /**
     * Returns the default cache period in seconds
     */
    protected function getDefaultCachePeriod(): int
    {
        $frontendTyposcript = $this->getRequest()->getAttribute('frontend.typoscript');
        return (int)($frontendTyposcript->getConfigArray()['cache_period'] ?? 86400);
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

    protected function getTimeTracker(): TimeTracker
    {
        return GeneralUtility::makeInstance(TimeTracker::class);
    }

    protected function getTcaSchema(string $table): ?TcaSchema
    {
        $schemaFactory = GeneralUtility::makeInstance(TcaSchemaFactory::class);
        if ($schemaFactory->has($table)) {
            return $schemaFactory->get($table);
        }
        return null;
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
        $typoScriptConfigArray = $this->getRequest()->getAttribute('frontend.typoscript')->getConfigArray();
        if (isset($typoScriptConfigArray['debug'])) {
            return (bool)($typoScriptConfigArray['debug']);
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
