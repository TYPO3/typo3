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

namespace TYPO3\CMS\Core\TypoScript;

use Psr\Log\LogLevel;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\AbstractRestrictionContainer;
use TYPO3\CMS\Core\Database\Query\Restriction\DefaultRestrictionContainer;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\TimeTracker\TimeTracker;
use TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Frontend\Configuration\TypoScript\ConditionMatching\ConditionMatcher;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Template object that is responsible for generating the TypoScript template based on template records.
 *
 * @deprecated This class should not be used anymore, last core usages will be removed during v12.
 *             Using methods or properties of this class will start logging deprecation messages.
 */
class TemplateService
{
    /**
     * option to enable logging, time-tracking (FE-only)
     * usually, this is only done when
     *  - in FE a BE_USER is logged-in
     *  - in BE when the BE_USER needs information about the template (TypoScript module)
     * @var bool
     */
    protected $verbose = false;

    /**
     * If set, the global tt-timeobject is used to log the performance.
     *
     * @var bool
     */
    public $tt_track = true;

    /**
     * This array is passed on to matchObj by generateConfig().
     * If it holds elements, they are used for matching instead. See comment at the match-class.
     * Used for backend modules only. Never frontend!
     *
     * @var array
     * @internal
     */
    public $matchAlternative = [];

    /**
     * If set, the match-class matches everything! Used for backend modules only. Never frontend!
     *
     * @var bool
     */
    protected $matchAll = false;

    /**
     * @var bool
     */
    public $ext_regLinenumbers = false;

    /**
     * @var bool
     */
    public $ext_regComments = false;

    /**
     * Set if preview of some kind is enabled.
     *
     * @var bool
     */
    protected $simulationHiddenOrTime = false;

    /**
     * Set, if the TypoScript template structure is loaded and OK, see ->start()
     *
     * @var bool
     */
    public $loaded = false;

    /**
     * @var array Contains TypoScript setup part after parsing
     */
    public $setup = [];

    /**
     * @var array
     */
    public $flatSetup = [];

    /**
     * For fetching TypoScript code from template hierarchy before parsing it.
     * Each array contains code field values from template records/files:
     * Setup field
     *
     * @var array
     */
    public $config = [];

    /**
     * Constant field
     *
     * @var array
     */
    public $constants = [];

    /**
     * Holds the include paths of the templates (empty if from database)
     *
     * @var array
     */
    protected $templateIncludePaths = [];

    /**
     * For Template Analyzer in backend
     *
     * @var array
     */
    public $hierarchyInfo = [];

    /**
     * For Template Analyzer in backend (setup content only)
     *
     * @var array
     */
    protected $hierarchyInfoToRoot = [];

    /**
     * The Page UID of the root page
     *
     * @var int
     */
    protected $rootId;

    /**
     * This is the "local" rootline of a deep page that stops at the first parent
     * sys_template record that has "root" flag set, in natural parent-child order.
     *
     * In frontend context, this is also set as TSFE TypoScriptFrontendController->config['rootLine'].
     *
     * Both language and version overlays are applied to these page records:
     * All "data" fields are set to language / version overlay values, *except* uid and
     * pid, which are the default-language and live-version ids.
     *
     * When page uid 5 is called in this example:
     * [0] Project name
     * |- [2] An organizational page, probably with is_siteroot=1 and a site config
     *    |- [3] Site root with a sys_template having "root" flag set
     *       |- [5] Here you are
     *
     * This $rootLine is:
     * [0] => [uid = 3, pid = 2, title = Site root with a sys_template having "root" flag set, ...]
     * [1] => [uid = 5, pid = 3, title = Here you are, ...]
     *
     * @var array<int, array<string, mixed>>
     */
    public $rootLine;

    /**
     * Rootline of page records all the way to the root.
     *
     * In frontend context, this is also set as TSFE TypoScriptFrontendController->rootLine.
     *
     * Both language and version overlays are applied to these page records:
     * All "data" fields are set to language / version overlay values, *except* uid and
     * pid, which are the default-language and live-version ids.
     *
     * First array row with the highest key is the deepest page (the requested page),
     * then parent pages with descending keys until (but not including) the
     * project root pseudo page 0.
     *
     * When page uid 5 is called in this example:
     * [0] Project name
     * |- [2] An organizational page, probably with is_siteroot=1 and a site config
     *    |- [3] Site root with a sys_template having "root" flag set
     *       |- [5] Here you are
     *
     * This $absoluteRootLine is:
     * [3] => [uid = 5, pid = 3, title = Here you are, ...]
     * [2] => [uid = 3, pid = 2, title = Site root with a sys_template having "root" flag set, ...]
     * [1] => [uid = 2, pid = 0, title = An organizational page, probably with is_siteroot=1 and a site config, ...]
     *
     * @var array<int, array<string, mixed>>
     */
    protected array $absoluteRootLine = [];

    /**
     * Array of arrays with title/uid of templates in hierarchy
     *
     * @var array
     */
    protected $rowSum;

    /**
     * Tracking all conditions found during parsing of TypoScript. Used for the "all" key in currentPageData
     *
     * @var array|null
     */
    public $sections;

    /**
     * Tracking all matching conditions found
     *
     * @var array
     */
    protected $sectionsMatch;

    /**
     * Used by Backend only (Typoscript Template Analyzer)
     * @var string[]
     */
    public $clearList_const = [];

    /**
     * Used by Backend only (Typoscript Template Analyzer)
     *
     * @var array
     */
    public $clearList_setup = [];

    /**
     * @var array
     */
    public $parserErrors = [];

    /**
     * @var array
     */
    public $setup_constants = [];

    /**
     * Indicator that extension statics are processed.
     *
     * These files are considered if either a root template
     * has been processed or the $processExtensionStatics
     * property has been set to TRUE.
     *
     * @var bool
     */
    protected $extensionStaticsProcessed = false;

    /**
     * Trigger value, to ensure that extension statics are processed.
     *
     * @var bool
     */
    protected $processExtensionStatics = false;

    /**
     * Set to TRUE after the default TypoScript was added during parsing.
     * This prevents double inclusion of the same TypoScript code.
     *
     * @see addDefaultTypoScript()
     * @var bool
     */
    protected $isDefaultTypoScriptAdded = false;

    /**
     * Set to TRUE after $this->config and $this->constants have processed all <INCLUDE_TYPOSCRIPT:> instructions.
     *
     * This prevents double processing of INCLUDES.
     *
     * @see processIncludes()
     * @var bool
     */
    protected $processIncludesHasBeenRun = false;

    /**
     * Contains the restrictions about deleted, and some frontend related topics
     * @var AbstractRestrictionContainer
     */
    protected $queryBuilderRestrictions;

    /**
     * @var Context
     */
    protected $context;

    /**
     * @var PackageManager
     */
    protected $packageManager;

    /**
     * @var TypoScriptFrontendController|null
     */
    protected $frontendController;

    private static bool $deprecationLogged = false;

    /**
     * @param Context|null $context
     * @param PackageManager|null $packageManager
     * @param TypoScriptFrontendController|null $frontendController
     */
    public function __construct(Context $context = null, PackageManager $packageManager = null, TypoScriptFrontendController $frontendController = null)
    {
        $this->context = $context ?? GeneralUtility::makeInstance(Context::class);
        $this->packageManager = $packageManager ?? GeneralUtility::makeInstance(PackageManager::class);
        $this->frontendController = $frontendController;
        $this->initializeDatabaseQueryRestrictions();
        if ($this->context->getPropertyFromAspect('visibility', 'includeHiddenContent', false) || $GLOBALS['SIM_ACCESS_TIME'] !== $GLOBALS['ACCESS_TIME']) {
            // Set the simulation flag, if simulation is detected!
            $this->simulationHiddenOrTime = true;
        }
        $this->tt_track = $this->verbose = (bool)$this->context->getPropertyFromAspect('backend.user', 'isLoggedIn', false);
    }

    /**
     * @return bool
     */
    public function getProcessExtensionStatics()
    {
        if (!self::$deprecationLogged) {
            trigger_error(__CLASS__ . ' has been marked as deprecated in TYPO3 v12 and will be removed in v13.', E_USER_DEPRECATED);
            self::$deprecationLogged = true;
        }
        return $this->processExtensionStatics;
    }

    /**
     * @param bool $processExtensionStatics
     */
    public function setProcessExtensionStatics($processExtensionStatics)
    {
        if (!self::$deprecationLogged) {
            trigger_error(__CLASS__ . ' has been marked as deprecated in TYPO3 v12 and will be removed in v13.', E_USER_DEPRECATED);
            self::$deprecationLogged = true;
        }
        $this->processExtensionStatics = (bool)$processExtensionStatics;
    }

    /**
     * sets the verbose parameter
     * @param bool $verbose
     */
    public function setVerbose($verbose)
    {
        if (!self::$deprecationLogged) {
            trigger_error(__CLASS__ . ' has been marked as deprecated in TYPO3 v12 and will be removed in v13.', E_USER_DEPRECATED);
            self::$deprecationLogged = true;
        }
        $this->verbose = (bool)$verbose;
    }

    /**
     * Set up the query builder restrictions, optionally include hidden records
     */
    protected function initializeDatabaseQueryRestrictions()
    {
        $this->queryBuilderRestrictions = GeneralUtility::makeInstance(DefaultRestrictionContainer::class);

        if ($this->context->getPropertyFromAspect('visibility', 'includeHiddenContent', false)) {
            $this->queryBuilderRestrictions->removeByType(HiddenRestriction::class);
        }
    }

    /**
     * Fetches data about which TypoScript-matches there are at this page. Then it performs a matchingtest.
     *
     * @param array $cc An array with three keys, "all", "rowSum" and "rootLine" - all coming from the "currentPageData" array
     * @return array The input array but with a new key added, "match" which contains the items from the "all" key which when passed to tslib_matchCondition returned TRUE.
     */
    public function matching($cc)
    {
        if (!self::$deprecationLogged) {
            trigger_error(__CLASS__ . ' has been marked as deprecated in TYPO3 v12 and will be removed in v13.', E_USER_DEPRECATED);
            self::$deprecationLogged = true;
        }
        if (is_array($cc['all'])) {
            $matchObj = GeneralUtility::makeInstance(ConditionMatcher::class, null, null, null, $this->absoluteRootLine);
            $matchObj->setRootline((array)($cc['rootLine'] ?? []));
            $sectionsMatch = [];
            foreach ($cc['all'] as $key => $pre) {
                if ($matchObj->match($pre)) {
                    $sectionsMatch[$key] = $pre;
                }
            }
            $cc['match'] = $sectionsMatch;
        }
        return $cc;
    }

    /**
     * This is all about fetching the right TypoScript template structure. If it's not cached then it must be generated and cached!
     * The method traverses the rootline structure from out to in, fetches the hierarchy of template records and based on this either finds the cached TypoScript template structure or parses the template and caches it for next time.
     * Sets $this->setup to the parsed TypoScript template array
     *
     * @param array $theRootLine The rootline of the current page (going ALL the way to tree root)
     */
    public function start($theRootLine)
    {
        if (!self::$deprecationLogged) {
            trigger_error(__CLASS__ . ' has been marked as deprecated in TYPO3 v12 and will be removed in v13.', E_USER_DEPRECATED);
            self::$deprecationLogged = true;
        }
        $cc = [];
        if (is_array($theRootLine)) {
            $constantsData = [];
            $setupData = [];
            $cacheIdentifier = '';
            $this->runThroughTemplates($theRootLine);
            // This is about getting the hash string which is used to fetch the cached TypoScript template.
            // If there was some cached currentPageData ($cc) then that's good (it gives us the hash).
            // If currentPageData was not there, we first find $rowSum (freshly generated). After that we try to see, if it is stored with a list of all conditions. If so we match the result.
            $rowSumHash = md5('ROWSUM:' . serialize($this->rowSum));
            $result = $this->getCacheEntry($rowSumHash);
            if (is_array($result)) {
                $cc['all'] = $result;
                $cc['rowSum'] = $this->rowSum;
                $cc = $this->matching($cc);
                ksort($cc);
                $cacheIdentifier = md5(serialize($cc));
            }
            if ($cacheIdentifier) {
                // Get TypoScript setup array
                $cachedData = $this->getCacheEntry($cacheIdentifier);
                if (is_array($cachedData)) {
                    $constantsData = $cachedData['constants'];
                    $setupData = $cachedData['setup'];
                }
            }
            if (!empty($setupData) && !$this->context->getPropertyFromAspect('typoscript', 'forcedTemplateParsing')) {
                // TypoScript constants + setup are found in the cache
                $this->setup_constants = $constantsData;
                $this->setup = $setupData;
                if ($this->tt_track) {
                    $this->getTimeTracker()->setTSlogMessage('Using cached TS template data', LogLevel::INFO);
                }
            } else {
                if ($this->tt_track) {
                    $this->getTimeTracker()->setTSlogMessage('Not using any cached TS data', LogLevel::INFO);
                }

                // Make configuration
                $this->generateConfig();
                // This stores the template hash thing
                $cc = [];
                // All sections in the template at this point is found
                $cc['all'] = $this->sections;
                // The line of templates is collected
                $cc['rowSum'] = $this->rowSum;
                $cc = $this->matching($cc);
                ksort($cc);
                $cacheIdentifier = md5(serialize($cc));
                // This stores the data.
                $this->setCacheEntry($cacheIdentifier, ['constants' => $this->setup_constants, 'setup' => $this->setup], 'TS_TEMPLATE');
                if ($this->tt_track) {
                    $this->getTimeTracker()->setTSlogMessage('TS template size, serialized: ' . strlen(serialize($this->setup)) . ' bytes', LogLevel::INFO);
                }
                $rowSumHash = md5('ROWSUM:' . serialize($this->rowSum));
                $this->setCacheEntry($rowSumHash, $cc['all'], 'TMPL_CONDITIONS_ALL');
            }
            // Add rootLine
            $cc['rootLine'] = $this->rootLine;
            ksort($cc);
            // If everything OK.
            if ($this->rootId && $this->rootLine && $this->setup) {
                $this->loaded = true;
            }
        }
    }

    /*******************************************************************
     *
     * Fetching TypoScript code text for the Template Hierarchy
     *
     *******************************************************************/
    /**
     * Traverses the rootLine from the root and out. For each page it checks if there is a template record. If there is a template record, $this->processTemplate() is called.
     * Resets and affects internal variables like $this->constants, $this->config and $this->rowSum
     * Also creates $this->rootLine which is a root line stopping at the root template (contrary to $this->getTypoScriptFrontendController()->rootLine which goes all the way to the root of the tree
     *
     * @param array $theRootLine The rootline of the current page (going ALL the way to tree root)
     * @param int $start_template_uid Set specific template record UID to select; this is only for debugging/development/analysis use in backend modules like "Web > Template". For parsing TypoScript templates in the frontend it should be 0 (zero)
     * @see start()
     */
    public function runThroughTemplates($theRootLine, $start_template_uid = 0)
    {
        if (!self::$deprecationLogged) {
            trigger_error(__CLASS__ . ' has been marked as deprecated in TYPO3 v12 and will be removed in v13.', E_USER_DEPRECATED);
            self::$deprecationLogged = true;
        }
        $this->constants = [];
        $this->config = [];
        $this->rowSum = [];
        $this->hierarchyInfoToRoot = [];
        $this->absoluteRootLine = $theRootLine;
        $this->isDefaultTypoScriptAdded = false;

        reset($this->absoluteRootLine);
        $c = count($this->absoluteRootLine);
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_template');
        for ($a = 0; $a < $c; $a++) {
            $where = [
                $queryBuilder->expr()->eq(
                    'pid',
                    $queryBuilder->createNamedParameter($this->absoluteRootLine[$a]['uid'], Connection::PARAM_INT)
                ),
            ];
            // If first loop AND there is set an alternative template uid, use that
            if ($a === $c - 1 && $start_template_uid) {
                $where[] = $queryBuilder->expr()->eq(
                    'uid',
                    $queryBuilder->createNamedParameter($start_template_uid, Connection::PARAM_INT)
                );
            }
            $queryBuilder->setRestrictions($this->queryBuilderRestrictions);
            $queryResult = $queryBuilder
                ->select('*')
                ->from('sys_template')
                ->where(...$where)
                ->orderBy('root', 'DESC')
                ->addOrderBy('sorting')
                ->setMaxResults(1)
                ->executeQuery();
            if ($row = $queryResult->fetchAssociative()) {
                $this->processTemplate($row, 'sys_' . $row['uid'], $this->absoluteRootLine[$a]['uid'], 'sys_' . $row['uid']);
            }
            $this->rootLine[] = $this->absoluteRootLine[$a];
        }

        // Hook into the default TypoScript to add custom typoscript logic
        $hookParameters = [
            'extensionStaticsProcessed' => &$this->extensionStaticsProcessed,
            'isDefaultTypoScriptAdded'  => &$this->isDefaultTypoScriptAdded,
            'absoluteRootLine' => &$this->absoluteRootLine,
            'rootLine'         => &$this->rootLine,
            'startTemplateUid' => $start_template_uid,
            'rowSum'           => &$this->rowSum,
        ];
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['Core/TypoScript/TemplateService']['runThroughTemplatesPostProcessing'] ?? [] as $listener) {
            GeneralUtility::callUserFunction($listener, $hookParameters, $this);
        }

        // Process extension static files if not done yet, but explicitly requested
        if (!$this->extensionStaticsProcessed && $this->processExtensionStatics) {
            $this->addExtensionStatics('sys_0', 'sys_0', 0);
        }

        // Add the global default TypoScript from the TYPO3_CONF_VARS
        $this->addDefaultTypoScript();

        $this->processIncludes();
    }

    /**
     * Checks if the template ($row) has some included templates and after including them it fills the arrays with the setup
     * Builds up $this->rowSum
     *
     * @param array $row A full TypoScript template record (sys_template/forged "dummy" record made from static template file)
     * @param string $idList A list of already processed template ids including the current; The list is on the form "[prefix]_[uid]" where [prefix] is "sys" for "sys_template" records, records and "ext_" for static include files (from extensions). The list is used to check that the recursive inclusion of templates does not go into circles: Simply it is used to NOT include a template record/file which has already BEEN included somewhere in the recursion.
     * @param int $pid The PID of the input template record
     * @param string $templateID The id of the current template. Same syntax as $idList ids, eg. "sys_123
     * @param string $templateParent Parent template id (during recursive call); Same syntax as $idList ids, eg. "sys_123
     * @param string $includePath Specifies the path from which the template was included (used with static_includes)
     * @see runThroughTemplates()
     */
    public function processTemplate($row, $idList, $pid, $templateID = '', $templateParent = '', $includePath = '')
    {
        if (!self::$deprecationLogged) {
            trigger_error(__CLASS__ . ' has been marked as deprecated in TYPO3 v12 and will be removed in v13.', E_USER_DEPRECATED);
            self::$deprecationLogged = true;
        }
        // Adding basic template record information to rowSum array
        $this->rowSum[] = [$row['uid'] ?? null, $row['title'] ?? null, $row['tstamp'] ?? null];
        // Processing "Clear"-flags
        $clConst = 0;
        $clConf = 0;
        if (!empty($row['clear'])) {
            $clConst = $row['clear'] & 1;
            $clConf = $row['clear'] & 2;
            if ($clConst) {
                // Keep amount of items to stay in sync with $this->templateIncludePaths so processIncludes() does not break
                foreach ($this->constants as &$constantConfiguration) {
                    $constantConfiguration = '';
                }
                unset($constantConfiguration);
                $this->clearList_const = [];
            }
            if ($clConf) {
                // Keep amount of items to stay in sync with $this->templateIncludePaths so processIncludes() does not break
                foreach ($this->config as &$configConfiguration) {
                    $configConfiguration = '';
                }
                unset($configConfiguration);
                $this->hierarchyInfoToRoot = [];
                $this->clearList_setup = [];
            }
        }
        // Include files (from extensions) (#1/2)
        // NORMAL inclusion, The EXACT same code is found below the basedOn inclusion!!!
        if (!isset($row['includeStaticAfterBasedOn']) || !$row['includeStaticAfterBasedOn']) {
            $this->includeStaticTypoScriptSources($idList, $templateID, $pid, $row);
        }
        // Include "Based On" sys_templates:
        // 'basedOn' is a list of templates to include
        $basedOn = trim($row['basedOn'] ?? '');
        if ($basedOn !== '') {
            // Normal Operation, which is to include the "based-on" sys_templates,
            // if they are not already included, and maintaining the sorting of the templates
            $basedOnIds = GeneralUtility::intExplode(',', $basedOn, true);
            // skip template if it's already included
            foreach ($basedOnIds as $key => $basedOnId) {
                if (GeneralUtility::inList($idList, 'sys_' . $basedOnId)) {
                    unset($basedOnIds[$key]);
                }
            }
            if (!empty($basedOnIds)) {
                $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_template');
                $queryBuilder->setRestrictions($this->queryBuilderRestrictions);
                $queryResult = $queryBuilder
                    ->select('*')
                    ->from('sys_template')
                    ->where(
                        $queryBuilder->expr()->in(
                            'uid',
                            $queryBuilder->createNamedParameter($basedOnIds, Connection::PARAM_INT_ARRAY)
                        )
                    )
                    ->executeQuery();
                // make it an associative array with the UID as key
                $subTemplates = [];
                while ($rowItem = $queryResult->fetchAssociative()) {
                    $subTemplates[(int)$rowItem['uid']] = $rowItem;
                }
                // Traversing list again to ensure the sorting of the templates
                foreach ($basedOnIds as $id) {
                    if (is_array($subTemplates[$id] ?? false)) {
                        $this->processTemplate($subTemplates[$id], $idList . ',sys_' . $id, $pid, 'sys_' . $id, $templateID);
                    }
                }
            }
        }
        // Include files (from extensions) (#2/2)
        if (!empty($row['includeStaticAfterBasedOn'])) {
            $this->includeStaticTypoScriptSources($idList, $templateID, $pid, $row);
        }
        // Creating hierarchy information; Used by backend analysis tools
        $this->hierarchyInfo[] = ($this->hierarchyInfoToRoot[] = [
            'root' => trim($row['root'] ?? ''),
            'clConst' => $clConst,
            'clConf' => $clConf,
            'templateID' => $templateID,
            'templateParent' => $templateParent,
            'title' => $row['title'],
            'uid' => $row['uid'],
            'pid' => $row['pid'] ?? null,
            'configLines' => substr_count((string)$row['config'], LF) + 1,
        ]);
        // Adding the content of the fields constants (Constants) and config (Setup)
        $this->constants[] = $row['constants'];
        $this->config[] = $row['config'];
        $this->templateIncludePaths[] = $includePath;
        // For backend analysis (Template Analyzer) provide the order of added constants/config template IDs
        $this->clearList_const[] = $templateID;
        $this->clearList_setup[] = $templateID;
        // If the template record is a Rootlevel record, set the flag and clear the template rootLine (so it starts over from this point)
        if (trim($row['root'] ?? '')) {
            $this->rootId = $pid;
            $this->rootLine = [];
        }
    }

    /**
     * Includes static template files (from extensions) for the input template record row.
     *
     * @param string $idList A list of already processed template ids including the current; The list is on the form "[prefix]_[uid]" where [prefix] is "sys" for "sys_template" records and "ext_" for static include files (from extensions). The list is used to check that the recursive inclusion of templates does not go into circles: Simply it is used to NOT include a template record/file which has already BEEN included somewhere in the recursion.
     * @param string $templateID The id of the current template. Same syntax as $idList ids, eg. "sys_123
     * @param int $pid The PID of the input template record
     * @param array $row A full TypoScript template record
     * @see processTemplate()
     * @internal
     */
    public function includeStaticTypoScriptSources($idList, $templateID, $pid, $row)
    {
        // Call function for link rendering:
        $_params = [
            'idList' => &$idList,
            'templateId' => &$templateID,
            'pid' => &$pid,
            'row' => &$row,
        ];
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tstemplate.php']['includeStaticTypoScriptSources'] ?? [] as $_funcRef) {
            GeneralUtility::callUserFunction($_funcRef, $_params, $this);
        }
        // If "Include before all static templates if root-flag is set" is set:
        $staticFileMode = (int)($row['static_file_mode'] ?? null);
        if ($staticFileMode === 3 && str_starts_with($templateID, 'sys_') && $row['root']) {
            $this->addExtensionStatics($idList, $templateID, $pid);
        }
        // Static Template Files (Text files from extensions): include_static_file is a list of static files to include (from extensions)
        if (trim($row['include_static_file'] ?? '')) {
            $include_static_fileArr = GeneralUtility::trimExplode(',', $row['include_static_file'], true);
            // Traversing list
            foreach ($include_static_fileArr as $ISF_file) {
                if (PathUtility::isExtensionPath($ISF_file)) {
                    [$ISF_extKey, $ISF_localPath] = explode('/', substr($ISF_file, 4), 2);
                    if ($ISF_extKey !== '' && $ISF_localPath !== '' && ExtensionManagementUtility::isLoaded($ISF_extKey)) {
                        $ISF_localPath = rtrim($ISF_localPath, '/') . '/';
                        $ISF_filePath = ExtensionManagementUtility::extPath($ISF_extKey) . $ISF_localPath;
                        if (@is_dir($ISF_filePath)) {
                            $mExtKey = str_replace('_', '', $ISF_extKey . '/' . $ISF_localPath);
                            $includeStaticFileTxtPath = $ISF_filePath . 'include_static_file.txt';
                            $includeStaticFileTxtContents = '';
                            if (@file_exists($includeStaticFileTxtPath)) {
                                $includeStaticFileTxtContents = (string)file_get_contents($includeStaticFileTxtPath);
                                $includeStaticFileTxtContents = implode(',', array_unique(GeneralUtility::trimExplode(',', $includeStaticFileTxtContents)));
                            }
                            $subrow = [
                                'constants' => $this->getTypoScriptSourceFileContent($ISF_filePath, 'constants'),
                                'config' => $this->getTypoScriptSourceFileContent($ISF_filePath, 'setup'),
                                'include_static_file' => $includeStaticFileTxtContents,
                                'title' => $ISF_file,
                                'uid' => $mExtKey,
                            ];
                            $subrow = $this->prependStaticExtra($subrow);
                            $this->processTemplate($subrow, $idList . ',ext_' . $mExtKey, $pid, 'ext_' . $mExtKey, $templateID, $ISF_filePath);
                        }
                    }
                }
            }
        }
        // If "Default (include before if root flag is set)" is set OR
        // "Always include before this typoscript record" AND root-flag are set
        if ($staticFileMode === 1 || ($staticFileMode === 0 && str_starts_with($templateID, 'sys_') && $row['root'])) {
            $this->addExtensionStatics($idList, $templateID, $pid);
        }
        // Include Static Template Records after all other TypoScript has been included.
        $_params = [
            'idList' => &$idList,
            'templateId' => &$templateID,
            'pid' => &$pid,
            'row' => &$row,
        ];
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tstemplate.php']['includeStaticTypoScriptSourcesAtEnd'] ?? [] as $_funcRef) {
            GeneralUtility::callUserFunction($_funcRef, $_params, $this);
        }
    }

    /**
     * Retrieves the content of the first existing file by extension order.
     * Returns the empty string if no file is found.
     *
     * @param string $filePath The location of the file.
     * @param string $baseName The base file name. "constants" or "setup".
     * @return string
     */
    protected function getTypoScriptSourceFileContent($filePath, $baseName)
    {
        $extensions = ['.typoscript', '.ts', '.txt'];
        foreach ($extensions as $extension) {
            $fileName = $filePath . $baseName . $extension;
            if (@file_exists($fileName)) {
                return file_get_contents($fileName);
            }
        }
        return '';
    }

    /**
     * Adds the default TypoScript files for extensions if any.
     *
     * @param string $idList A list of already processed template ids including the current; The list is on the form "[prefix]_[uid]" where [prefix] is "sys" for "sys_template" records and "ext_" for static include files (from extensions). The list is used to check that the recursive inclusion of templates does not go into circles: Simply it is used to NOT include a template record/file which has already BEEN included somewhere in the recursion.
     * @param string $templateID The id of the current template. Same syntax as $idList ids, eg. "sys_123
     * @param int $pid The PID of the input template record
     * @internal
     * @see includeStaticTypoScriptSources()
     */
    protected function addExtensionStatics($idList, $templateID, $pid)
    {
        $this->extensionStaticsProcessed = true;

        foreach ($this->packageManager->getActivePackages() as $package) {
            $extKey = $package->getPackageKey();
            $packagePath = $package->getPackagePath();
            $filesToCheck = [
                'ext_typoscript_constants.typoscript',
                'ext_typoscript_setup.typoscript',
            ];
            $files = [];
            $hasExtensionStatics = false;
            foreach ($filesToCheck as $file) {
                $path = $packagePath . $file;
                if (@file_exists($path)) {
                    $files[$file] = $path;
                    $hasExtensionStatics = true;
                } else {
                    $files[$file] = null;
                }
            }

            if ($hasExtensionStatics) {
                $mExtKey = str_replace('_', '', $extKey);
                $constants = '';
                $config = '';

                if (!empty($files['ext_typoscript_constants.typoscript'])) {
                    $constants = @file_get_contents($files['ext_typoscript_constants.typoscript']);
                }

                if (!empty($files['ext_typoscript_setup.typoscript'])) {
                    $config = @file_get_contents($files['ext_typoscript_setup.typoscript']);
                }

                $this->processTemplate(
                    $this->prependStaticExtra([
                        'constants' => $constants,
                        'config' => $config,
                        'title' => $extKey,
                        'uid' => $mExtKey,
                    ]),
                    $idList . ',ext_' . $mExtKey,
                    $pid,
                    'ext_' . $mExtKey,
                    $templateID,
                    $packagePath
                );
            }
        }
    }

    /**
     * Appends (not prepends) additional TypoScript code to static template records/files as set in TYPO3_CONF_VARS
     * For files the "uid" value is the extension key but with any underscores removed. Possibly with a path if its a static file selected in the template record
     *
     * @param array $subrow Static template record/file
     * @return array Returns the input array where the values for keys "config" and "constants" may have been modified with prepended code.
     * @see addExtensionStatics()
     * @see includeStaticTypoScriptSources()
     */
    protected function prependStaticExtra($subrow)
    {
        // the identifier can be "43" if coming from "static template" extension or a path like "cssstyledcontent/static/"
        $identifier = $subrow['uid'];
        $subrow['config'] .= $GLOBALS['TYPO3_CONF_VARS']['FE']['defaultTypoScript_setup.'][$identifier] ?? '';
        $subrow['constants'] .= $GLOBALS['TYPO3_CONF_VARS']['FE']['defaultTypoScript_constants.'][$identifier] ?? '';
        // if this is a template of type "default content rendering", also see if other extensions have added their TypoScript that should be included after the content definitions
        if (in_array($identifier, $GLOBALS['TYPO3_CONF_VARS']['FE']['contentRenderingTemplates'], true)) {
            $subrow['config'] .= $GLOBALS['TYPO3_CONF_VARS']['FE']['defaultTypoScript_setup.']['defaultContentRendering'] ?? '';
            $subrow['constants'] .= $GLOBALS['TYPO3_CONF_VARS']['FE']['defaultTypoScript_constants.']['defaultContentRendering'] ?? '';
        }
        return $subrow;
    }

    /*******************************************************************
     *
     * Parsing TypoScript code text from Template Records into PHP array
     *
     *******************************************************************/
    /**
     * Generates the configuration array by replacing constants and parsing the whole thing.
     * Depends on $this->config and $this->constants to be set prior to this! (done by processTemplate/runThroughTemplates)
     *
     * @see \TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser
     * @see start()
     */
    public function generateConfig()
    {
        if (!self::$deprecationLogged) {
            trigger_error(__CLASS__ . ' has been marked as deprecated in TYPO3 v12 and will be removed in v13.', E_USER_DEPRECATED);
            self::$deprecationLogged = true;
        }
        // Add default TS for all code types
        $this->addDefaultTypoScript();

        // Parse the TypoScript code text for include-instructions!
        $this->processIncludes();
        // ****************************
        // Parse TypoScript Constants
        // ****************************
        // Initialize parser and match-condition classes:
        $constants = GeneralUtility::makeInstance(TypoScriptParser::class);
        $matchObj = GeneralUtility::makeInstance(ConditionMatcher::class, null, null, $this->rootLine, $this->absoluteRootLine);
        $matchObj->setSimulateMatchConditions($this->matchAlternative);
        $matchObj->setSimulateMatchResult((bool)$this->matchAll);
        // Traverse constants text fields and parse them
        foreach ($this->constants as $str) {
            $constants->parse($str, $matchObj);
        }
        // Read out parse errors if any
        $this->parserErrors['constants'] = $constants->errors;
        // Then flatten the structure from a multi-dim array to a single dim array with all constants listed as key/value pairs (ready for substitution)
        $this->flatSetup = ArrayUtility::flatten($constants->setup, '', true);
        // ***********************************************
        // Parse TypoScript Setup (here called "config")
        // ***********************************************
        // Initialize parser and match-condition classes:
        $config = GeneralUtility::makeInstance(TypoScriptParser::class);
        $config->regLinenumbers = $this->ext_regLinenumbers;
        $config->regComments = $this->ext_regComments;
        $config->setup = $this->setup;
        // Transfer information about conditions found in "Constants" and which of them returned TRUE.
        $config->sections = $constants->sections;
        $config->sectionsMatch = $constants->sectionsMatch;
        // Traverse setup text fields and concatenate them into one, single string separated by a [GLOBAL] condition
        $all = '';
        foreach ($this->config as $str) {
            $all .= '
[GLOBAL]
' . $str;
        }
        // Substitute constants in the Setup code:
        if ($this->tt_track) {
            $this->getTimeTracker()->push('Substitute Constants (' . count($this->flatSetup) . ')');
        }
        $all = $this->substituteConstants($all);
        if ($this->tt_track) {
            $this->getTimeTracker()->pull();
        }

        // Searching for possible unsubstituted constants left (only for information)
        if ($this->verbose) {
            if (preg_match_all('/\\{\\$.[^}]*\\}/', $all, $constantList) > 0) {
                if ($this->tt_track) {
                    $this->getTimeTracker()->setTSlogMessage(implode(', ', $constantList[0]) . ': Constants may remain un-substituted!!', LogLevel::WARNING);
                }
            }
        }

        // Logging the textual size of the TypoScript Setup field text with all constants substituted:
        if ($this->tt_track) {
            $this->getTimeTracker()->setTSlogMessage('TypoScript template size as textfile: ' . strlen($all) . ' bytes', LogLevel::INFO);
        }
        // Finally parse the Setup field TypoScript code (where constants are now substituted)
        $config->parse($all, $matchObj);
        // Read out parse errors if any
        $this->parserErrors['config'] = $config->errors;
        // Transfer the TypoScript array from the parser object to the internal $this->setup array:
        $this->setup = $config->setup;
        // Do the same for the constants
        $this->setup_constants = $constants->setup;
        // ****************************************************************
        // Final processing of the $this->setup TypoScript Template array
        // Basically: This is unsetting/setting of certain reserved keys.
        // ****************************************************************
        // These vars are already set after 'processTemplate', but because $config->setup overrides them (in the line above!), we set them again. They are not changed compared to the value they had in the top of the page!
        unset($this->setup['types.']);
        unset($this->setup['types']);
        if (is_array($this->setup)) {
            foreach ($this->setup as $key => $value) {
                if ($value === 'PAGE') {
                    // Set the typeNum of the current page object:
                    if (isset($this->setup[$key . '.']['typeNum'])) {
                        $typeNum = $this->setup[$key . '.']['typeNum'];
                        $this->setup['types.'][$typeNum] = $key;
                    } elseif (!isset($this->setup['types.'][0]) || !$this->setup['types.'][0]) {
                        $this->setup['types.'][0] = $key;
                    }
                }
            }
        }
        unset($this->setup['temp.']);
        unset($constants);
        // Storing the conditions found/matched information:
        $this->sections = $config->sections;
        $this->sectionsMatch = $config->sectionsMatch;
    }

    /**
     * Searching TypoScript code text (for constants and config (Setup))
     * for include instructions and does the inclusion of external TypoScript files
     * if needed.
     *
     * @see \TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser
     * @see generateConfig()
     */
    protected function processIncludes()
    {
        if ($this->processIncludesHasBeenRun) {
            return;
        }

        $paths = $this->templateIncludePaths;
        $files = [];
        foreach ($this->constants as &$value) {
            $includeData = TypoScriptParser::checkIncludeLines($value, 1, true, array_shift($paths));
            $files = array_merge($files, $includeData['files']);
            $value = $includeData['typoscript'];
        }
        unset($value);
        $paths = $this->templateIncludePaths;
        foreach ($this->config as &$value) {
            $includeData = TypoScriptParser::checkIncludeLines($value, 1, true, array_shift($paths));
            $files = array_merge($files, $includeData['files']);
            $value = $includeData['typoscript'];
        }
        unset($value);

        if (!empty($files)) {
            $files = array_unique($files);
            foreach ($files as $file) {
                $this->rowSum[] = [$file, filemtime($file)];
            }
        }

        $this->processIncludesHasBeenRun = true;
    }

    /**
     * Substitutes the constants from $this->flatSetup in the text string $all
     *
     * @param string $all TypoScript code text string
     * @return string The processed string with all constants found in $this->flatSetup as key/value pairs substituted.
     * @see generateConfig()
     */
    protected function substituteConstants($all)
    {
        if ($this->tt_track) {
            $this->getTimeTracker()->setTSlogMessage('Constants to substitute: ' . count($this->flatSetup), LogLevel::INFO);
        }
        $noChange = false;
        // Recursive substitution of constants (up to 10 nested levels)
        for ($i = 0; $i < 10 && !$noChange; $i++) {
            $old_all = $all;
            $all = preg_replace_callback('/\\{\\$(.[^}]*)\\}/', [$this, 'substituteConstantsCallBack'], $all) ?? '';
            if ($old_all == $all) {
                $noChange = true;
            }
        }
        return $all;
    }

    /**
     * Call back method for preg_replace_callback in substituteConstants
     *
     * @param array $matches Regular expression matches
     * @return string Replacement
     * @internal
     */
    public function substituteConstantsCallBack($matches)
    {
        // Replace {$CONST} if found in $this->flatSetup, else leave unchanged
        return isset($this->flatSetup[$matches[1]]) && !is_array($this->flatSetup[$matches[1]]) ? $this->flatSetup[$matches[1]] : $matches[0];
    }

    /*******************************************************************
     *
     * Various API functions, used from elsewhere in the frontend classes
     *
     *******************************************************************/

    /**
     * Returns the level of the given page in the rootline - Multiple pages can be given by separating the UIDs by comma.
     *
     * @param string $list A list of UIDs for which the rootline-level should get returned
     * @return int The level in the rootline. If more than one page was given the lowest level will get returned.
     */
    public function getRootlineLevel($list)
    {
        if (!self::$deprecationLogged) {
            trigger_error(__CLASS__ . ' has been marked as deprecated in TYPO3 v12 and will be removed in v13.', E_USER_DEPRECATED);
            self::$deprecationLogged = true;
        }
        $idx = 0;
        foreach ($this->rootLine as $page) {
            if (GeneralUtility::inList($list, $page['uid'])) {
                return $idx;
            }
            $idx++;
        }
        return false;
    }

    /**
     * Returns the page ID of the rootlevel
     */
    public function getRootId(): int
    {
        if (!self::$deprecationLogged) {
            trigger_error(__CLASS__ . ' has been marked as deprecated in TYPO3 v12 and will be removed in v13.', E_USER_DEPRECATED);
            self::$deprecationLogged = true;
        }
        return (int)$this->rootId;
    }

    /*******************************************************************
     *
     * Functions for creating links
     *
     *******************************************************************/
    /**
     * Adds the TypoScript from the global array.
     * The class property isDefaultTypoScriptAdded ensures
     * that the adding only happens once.
     *
     * @see isDefaultTypoScriptAdded
     */
    protected function addDefaultTypoScript()
    {
        // Add default TS for all code types, if not done already
        if (!$this->isDefaultTypoScriptAdded) {
            $rootTemplateId = $this->hierarchyInfo[count($this->hierarchyInfo) - 1]['templateID'] ?? null;

            // adding constants from site settings
            $siteConstants = '';
            if ($this->getTypoScriptFrontendController() instanceof TypoScriptFrontendController) {
                $site = $this->getTypoScriptFrontendController()->getSite();
            } else {
                $possibleRoots = array_filter($this->absoluteRootLine, static function (array $page) {
                    return $page['is_siteroot'] === 1;
                });
                $possibleRoots[] = end($this->absoluteRootLine);
                $site = null;
                foreach ($possibleRoots as $possibleRoot) {
                    try {
                        $site = GeneralUtility::makeInstance(SiteFinder::class)->getSiteByPageId((int)($possibleRoot['uid'] ?? 0));
                        break;
                    } catch (SiteNotFoundException $exception) {
                        // continue
                    }
                }
            }
            if ($site instanceof Site) {
                $siteSettings = $site->getSettings();
                if (!$siteSettings->isEmpty()) {
                    $siteSettings = $siteSettings->getAllFlat();
                    foreach ($siteSettings as $k => $v) {
                        $siteConstants .= $k . ' = ' . $v . LF;
                    }
                }
            }

            if ($siteConstants !== '') {
                // the count of elements in ->constants, ->config and ->templateIncludePaths have to be in sync
                array_unshift($this->constants, $siteConstants);
                array_unshift($this->config, '');
                array_unshift($this->templateIncludePaths, '');
                // prepare a proper entry to hierachyInfo (used by TemplateAnalyzer in BE)
                $defaultTemplateInfo = [
                    'root' => '',
                    'clConst' => '',
                    'clConf' => '',
                    'templateID' => '_siteConstants_',
                    'templateParent' => $rootTemplateId,
                    'title' => 'Site settings',
                    'uid' => '_siteConstants_',
                    'pid' => '',
                    'configLines' => 0,
                ];
                // push info to information arrays used in BE by TemplateTools (Analyzer)
                array_unshift($this->clearList_const, $defaultTemplateInfo['uid']);
                array_unshift($this->clearList_setup, $defaultTemplateInfo['uid']);
                array_unshift($this->hierarchyInfo, $defaultTemplateInfo);
            }

            // adding default setup and constants
            // defaultTypoScript_setup is *very* unlikely to be empty
            // the count of elements in ->constants, ->config and ->templateIncludePaths have to be in sync
            array_unshift($this->constants, (string)$GLOBALS['TYPO3_CONF_VARS']['FE']['defaultTypoScript_constants']);
            array_unshift($this->config, (string)$GLOBALS['TYPO3_CONF_VARS']['FE']['defaultTypoScript_setup']);
            array_unshift($this->templateIncludePaths, '');
            // prepare a proper entry to hierachyInfo (used by TemplateAnalyzer in BE)
            $defaultTemplateInfo = [
                'root' => '',
                'clConst' => '',
                'clConf' => '',
                'templateID' => '_defaultTypoScript_',
                'templateParent' => $rootTemplateId,
                'title' => 'SYS:TYPO3_CONF_VARS:FE:defaultTypoScript',
                'uid' => '_defaultTypoScript_',
                'pid' => '',
                'configLines' => substr_count((string)$GLOBALS['TYPO3_CONF_VARS']['FE']['defaultTypoScript_setup'], LF) + 1,
            ];
            // push info to information arrays used in BE by TemplateTools (Analyzer)
            array_unshift($this->clearList_const, $defaultTemplateInfo['uid']);
            array_unshift($this->clearList_setup, $defaultTemplateInfo['uid']);
            array_unshift($this->hierarchyInfo, $defaultTemplateInfo);

            $this->isDefaultTypoScriptAdded = true;
        }
    }

    /**
     * @return TypoScriptFrontendController|null
     */
    protected function getTypoScriptFrontendController()
    {
        return $this->frontendController ?? $GLOBALS['TSFE'] ?? null;
    }

    /**
     * @return TimeTracker
     */
    protected function getTimeTracker()
    {
        return GeneralUtility::makeInstance(TimeTracker::class);
    }

    /**
     * Returns data stored for the hash string in the cache "cache_hash"
     * used to store the parsed TypoScript template structures.
     *
     * @param string $identifier The hash-string which was used to store the data value
     * @return mixed The data from the cache
     */
    protected function getCacheEntry($identifier)
    {
        return GeneralUtility::makeInstance(CacheManager::class)->getCache('hash')->get($identifier);
    }

    /**
     * Stores $data in the 'hash' cache with the hash key $identifier
     *
     * @param string $identifier 32 bit hash string (eg. a md5 hash of a serialized array identifying the data being stored)
     * @param mixed $data The data to store
     * @param string $tag Is just a textual identification in order to inform about the content
     */
    protected function setCacheEntry($identifier, $data, $tag)
    {
        GeneralUtility::makeInstance(CacheManager::class)->getCache('hash')->set($identifier, $data, ['ident_' . $tag], 0);
    }
}
