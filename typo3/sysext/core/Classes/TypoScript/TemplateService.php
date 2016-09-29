<?php
namespace TYPO3\CMS\Core\TypoScript;

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

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\TimeTracker\TimeTracker;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\CMS\Frontend\Configuration\TypoScript\ConditionMatching\ConditionMatcher;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\CMS\Frontend\Page\PageRepository;

/**
 * Template object that is responsible for generating the TypoScript template based on template records.
 * @see \TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser
 * @see \TYPO3\CMS\Core\Configuration\TypoScript\ConditionMatching\AbstractConditionMatcher
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
     * If set, the template is always rendered. Used from Admin Panel.
     *
     * @var bool
     */
    public $forceTemplateParsing = false;

    /**
     * This array is passed on to matchObj by generateConfig().
     * If it holds elements, they are used for matching instead. See commment at the match-class.
     * Used for backend modules only. Never frontend!
     *
     * @var array
     */
    public $matchAlternative = [];

    /**
     * If set, the match-class matches everything! Used for backend modules only. Never frontend!
     *
     * @var bool
     */
    public $matchAll = false;

    /**
     * @var bool
     */
    public $backend_info = false;

    /**
     * Externally set breakpoints (used by Backend Modules)
     *
     * @var int
     */
    public $ext_constants_BRP = 0;

    /**
     * @var int
     */
    public $ext_config_BRP = 0;

    /**
     * @var bool
     */
    public $ext_regLinenumbers = false;

    /**
     * @var bool
     */
    public $ext_regComments = false;

    /**
     * @deprecated since TYPO3 CMS 7, will be removed with CMS 8.
     * @var string
     */
    public $tempPath = 'typo3temp/';

    /**
     * This MUST be initialized by the init() function
     *
     * @var string
     */
    public $whereClause = '';

    /**
     * @var bool
     */
    public $debug = false;

    /**
     * This is the only paths (relative!!) that are allowed for resources in TypoScript.
     * Should all be appended with '/'. You can extend these by the global array TYPO3_CONF_VARS. See init() function.
     *
     * @var array
     */
    public $allowedPaths = [];

    /**
     * See init(); Set if preview of some kind is enabled.
     *
     * @var int
     */
    public $simulationHiddenOrTime = 0;

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
     * For Template Analyser in backend
     *
     * @var array
     */
    public $hierarchyInfo = [];

    /**
     * For Template Analyser in backend (setup content only)
     *
     * @var array
     */
    public $hierarchyInfoToRoot = [];

    /**
     * Next-level flag (see runThroughTemplates())
     *
     * @var int
     */
    public $nextLevel = 0;

    /**
     * The Page UID of the root page
     *
     * @var int
     */
    public $rootId;

    /**
     * The rootline from current page to the root page
     *
     * @var array
     */
    public $rootLine;

    /**
     * Rootline all the way to the root. Set but runThroughTemplates
     *
     * @var array
     */
    public $absoluteRootLine;

    /**
     * A pointer to the last entry in the rootline where a template was found.
     *
     * @var int
     */
    public $outermostRootlineIndexWithTemplate = 0;

    /**
     * Array of arrays with title/uid of templates in hierarchy
     *
     * @var array
     */
    public $rowSum;

    /**
     * The current site title field.
     *
     * @var string
     */
    public $sitetitle = '';

    /**
     * Tracking all conditions found during parsing of TypoScript. Used for the "all" key in currentPageData
     *
     * @var string
     */
    public $sections;

    /**
     * Tracking all matching conditions found
     *
     * @var array
     */
    public $sectionsMatch;

    /**
     * Used by Backend only (Typoscript Template Analyzer)
     *
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
     * Used by getFileName for caching of references to file resources
     *
     * @var array
     */
    public $fileCache = [];

    /**
     * Keys are frame names and values are type-values, which must be used to refer correctly to the content of the frames.
     *
     * @var array
     */
    public $frames = [];

    /**
     * Contains mapping of Page id numbers to MP variables.
     *
     * @var string
     */
    public $MPmap = '';

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
     * @return bool
     */
    public function getProcessExtensionStatics()
    {
        return $this->processExtensionStatics;
    }

    /**
     * @param bool $processExtensionStatics
     */
    public function setProcessExtensionStatics($processExtensionStatics)
    {
        $this->processExtensionStatics = (bool)$processExtensionStatics;
    }

    /**
     * sets the verbose parameter
     * @param bool $verbose
     */
    public function setVerbose($verbose)
    {
        $this->verbose = (bool)$verbose;
    }

    /**
     * Initialize
     * MUST be called directly after creating a new template-object
     *
     * @return void
     * @see \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController::initTemplate()
     */
    public function init()
    {
        // $this->whereClause is used only to select templates from sys_template.
        // $GLOBALS['SIM_ACCESS_TIME'] is used so that we're able to simulate a later time as a test...
        $this->whereClause = 'AND deleted=0 ';
        if (!$this->getTypoScriptFrontendController()->showHiddenRecords) {
            $this->whereClause .= 'AND hidden=0 ';
        }
        if ($this->getTypoScriptFrontendController()->showHiddenRecords || $GLOBALS['SIM_ACCESS_TIME'] != $GLOBALS['ACCESS_TIME']) {
            // Set the simulation flag, if simulation is detected!
            $this->simulationHiddenOrTime = 1;
        }
        $this->whereClause .= 'AND (starttime<=' . $GLOBALS['SIM_ACCESS_TIME'] . ') AND (endtime=0 OR endtime>' . $GLOBALS['SIM_ACCESS_TIME'] . ')';
        // Sets the paths from where TypoScript resources are allowed to be used:
        $this->allowedPaths = [
            $GLOBALS['TYPO3_CONF_VARS']['BE']['fileadminDir'],
            // fileadmin/ path
            'uploads/',
            'typo3temp/',
            TYPO3_mainDir . 'ext/',
            TYPO3_mainDir . 'sysext/',
            'typo3conf/ext/'
        ];
        if ($GLOBALS['TYPO3_CONF_VARS']['FE']['addAllowedPaths']) {
            $pathArr = GeneralUtility::trimExplode(',', $GLOBALS['TYPO3_CONF_VARS']['FE']['addAllowedPaths'], true);
            foreach ($pathArr as $p) {
                // Once checked for path, but as this may run from typo3/mod/web/ts/ dir, that'll not work!! So the paths ar uncritically included here.
                $this->allowedPaths[] = $p;
            }
        }
    }

    /**
     * Fetches the "currentPageData" array from cache
     *
     * NOTE about currentPageData:
     * It holds information about the TypoScript conditions along with the list
     * of template uid's which is used on the page. In the getFromCache() function
     * in TSFE, currentPageData is used to evaluate if there is a template and
     * if the matching conditions are alright. Unfortunately this does not take
     * into account if the templates in the rowSum of currentPageData has
     * changed composition, eg. due to hidden fields or start/end time. So if a
     * template is hidden or times out, it'll not be discovered unless the page
     * is regenerated - at least the this->start function must be called,
     * because this will make a new portion of data in currentPageData string.
     *
     * @return array Returns the unmatched array $currentPageData if found cached in "cache_pagesection". Otherwise FALSE is returned which means that the array must be generated and stored in the cache
     */
    public function getCurrentPageData()
    {
        return GeneralUtility::makeInstance(CacheManager::class)->getCache('cache_pagesection')->get((int)$this->getTypoScriptFrontendController()->id . '_' . GeneralUtility::md5int($this->getTypoScriptFrontendController()->MP));
    }

    /**
     * Fetches data about which TypoScript-matches there are at this page. Then it performs a matchingtest.
     *
     * @param array $cc An array with three keys, "all", "rowSum" and "rootLine" - all coming from the "currentPageData" array
     * @return array The input array but with a new key added, "match" which contains the items from the "all" key which when passed to tslib_matchCondition returned TRUE.
     */
    public function matching($cc)
    {
        if (is_array($cc['all'])) {
            /** @var $matchObj ConditionMatcher */
            $matchObj = GeneralUtility::makeInstance(ConditionMatcher::class);
            $matchObj->setRootline((array)$cc['rootLine']);
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
     * @return void
     * @see \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController::getConfigArray()
     */
    public function start($theRootLine)
    {
        if (is_array($theRootLine)) {
            $setupData = '';
            $hash = '';
            // Flag that indicates that the existing data in cache_pagesection
            // could be used (this is the case if $TSFE->all is set, and the
            // rowSum still matches). Based on this we decide if cache_pagesection
            // needs to be updated...
            $isCached = false;
            $this->runThroughTemplates($theRootLine);
            if ($this->getTypoScriptFrontendController()->all) {
                $cc = $this->getTypoScriptFrontendController()->all;
                // The two rowSums must NOT be different from each other - which they will be if start/endtime or hidden has changed!
                if (serialize($this->rowSum) !== serialize($cc['rowSum'])) {
                    unset($cc);
                } else {
                    // If $TSFE->all contains valid data, we don't need to update cache_pagesection (because this data was fetched from there already)
                    if (serialize($this->rootLine) === serialize($cc['rootLine'])) {
                        $isCached = true;
                    }
                    // When the data is serialized below (ROWSUM hash), it must not contain the rootline by concept. So this must be removed (and added again later)...
                    unset($cc['rootLine']);
                }
            }
            // This is about getting the hash string which is used to fetch the cached TypoScript template.
            // If there was some cached currentPageData ($cc) then that's good (it gives us the hash).
            if (isset($cc) && is_array($cc)) {
                // If currentPageData was actually there, we match the result (if this wasn't done already in $TSFE->getFromCache()...)
                if (!$cc['match']) {
                    // @todo check if this can ever be the case - otherwise remove
                    $cc = $this->matching($cc);
                    ksort($cc);
                }
                $hash = md5(serialize($cc));
            } else {
                // If currentPageData was not there, we first find $rowSum (freshly generated). After that we try to see, if it is stored with a list of all conditions. If so we match the result.
                $rowSumHash = md5('ROWSUM:' . serialize($this->rowSum));
                $result = PageRepository::getHash($rowSumHash);
                if (is_array($result)) {
                    $cc = [];
                    $cc['all'] = $result;
                    $cc['rowSum'] = $this->rowSum;
                    $cc = $this->matching($cc);
                    ksort($cc);
                    $hash = md5(serialize($cc));
                }
            }
            if ($hash) {
                // Get TypoScript setup array
                $setupData = PageRepository::getHash($hash);
            }
            if (is_array($setupData) && !$this->forceTemplateParsing) {
                // If TypoScript setup structure was cached we unserialize it here:
                $this->setup = $setupData;
                if ($this->tt_track) {
                    $this->getTimeTracker()->setTSLogMessage('Using cached TS template data');
                }
            } else {
                if ($this->tt_track) {
                    $this->getTimeTracker()->setTSLogMessage('Not using any cached TS data');
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
                $hash = md5(serialize($cc));
                // This stores the data.
                PageRepository::storeHash($hash, $this->setup, 'TS_TEMPLATE');
                if ($this->tt_track) {
                    $this->getTimeTracker()->setTSlogMessage('TS template size, serialized: ' . strlen(serialize($this->setup)) . ' bytes');
                }
                $rowSumHash = md5('ROWSUM:' . serialize($this->rowSum));
                PageRepository::storeHash($rowSumHash, $cc['all'], 'TMPL_CONDITIONS_ALL');
            }
            // Add rootLine
            $cc['rootLine'] = $this->rootLine;
            ksort($cc);
            // Make global and save
            $this->getTypoScriptFrontendController()->all = $cc;
            // Matching must be executed for every request, so this must never be part of the pagesection cache!
            unset($cc['match']);
            if (!$isCached && !$this->simulationHiddenOrTime && !$this->getTypoScriptFrontendController()->no_cache) {
                // Only save the data if we're not simulating by hidden/starttime/endtime
                $mpvarHash = GeneralUtility::md5int($this->getTypoScriptFrontendController()->MP);
                /** @var $pageSectionCache \TYPO3\CMS\Core\Cache\Frontend\FrontendInterface */
                $pageSectionCache = GeneralUtility::makeInstance(CacheManager::class)->getCache('cache_pagesection');
                $pageSectionCache->set((int)$this->getTypoScriptFrontendController()->id . '_' . $mpvarHash, $cc, [
                    'pageId_' . (int)$this->getTypoScriptFrontendController()->id,
                    'mpvarHash_' . $mpvarHash
                ]);
            }
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
     * @return void
     * @see start()
     */
    public function runThroughTemplates($theRootLine, $start_template_uid = 0)
    {
        $this->constants = [];
        $this->config = [];
        $this->rowSum = [];
        $this->hierarchyInfoToRoot = [];
        $this->absoluteRootLine = $theRootLine;
        $this->isDefaultTypoScriptAdded = false;

        reset($this->absoluteRootLine);
        $c = count($this->absoluteRootLine);
        for ($a = 0; $a < $c; $a++) {
            // If some template loaded before has set a template-id for the next level, then load this template first!
            if ($this->nextLevel) {
                $res = $this->getDatabaseConnection()->exec_SELECTquery('*', 'sys_template', 'uid=' . (int)$this->nextLevel . ' ' . $this->whereClause);
                $this->nextLevel = 0;
                if ($row = $this->getDatabaseConnection()->sql_fetch_assoc($res)) {
                    $this->versionOL($row);
                    if (is_array($row)) {
                        $this->processTemplate($row, 'sys_' . $row['uid'], $this->absoluteRootLine[$a]['uid'], 'sys_' . $row['uid']);
                        $this->outermostRootlineIndexWithTemplate = $a;
                    }
                }
                $this->getDatabaseConnection()->sql_free_result($res);
            }
            $addC = '';
            // If first loop AND there is set an alternative template uid, use that
            if ($a == $c - 1 && $start_template_uid) {
                $addC = ' AND uid=' . (int)$start_template_uid;
            }
            $res = $this->getDatabaseConnection()->exec_SELECTquery('*', 'sys_template', 'pid=' . (int)$this->absoluteRootLine[$a]['uid'] . $addC . ' ' . $this->whereClause, '', 'root DESC, sorting', 1);
            if ($row = $this->getDatabaseConnection()->sql_fetch_assoc($res)) {
                $this->versionOL($row);
                if (is_array($row)) {
                    $this->processTemplate($row, 'sys_' . $row['uid'], $this->absoluteRootLine[$a]['uid'], 'sys_' . $row['uid']);
                    $this->outermostRootlineIndexWithTemplate = $a;
                }
            }
            $this->getDatabaseConnection()->sql_free_result($res);
            $this->rootLine[] = $this->absoluteRootLine[$a];
        }

        // Process extension static files if not done yet, but explicitly requested
        if (!$this->extensionStaticsProcessed && $this->processExtensionStatics) {
            $this->addExtensionStatics('sys_0', 'sys_0', 0, []);
        }

        // Add the global default TypoScript from the TYPO3_CONF_VARS
        $this->addDefaultTypoScript();

        $this->processIncludes();
    }

    /**
     * Checks if the template ($row) has some included templates and after including them it fills the arrays with the setup
     * Builds up $this->rowSum
     *
     * @param array $row A full TypoScript template record (sys_template/static_template/forged "dummy" record made from static template file)
     * @param string $idList A list of already processed template ids including the current; The list is on the form "[prefix]_[uid]" where [prefix] is "sys" for "sys_template" records, "static" for "static_template" records and "ext_" for static include files (from extensions). The list is used to check that the recursive inclusion of templates does not go into circles: Simply it is used to NOT include a template record/file which has already BEEN included somewhere in the recursion.
     * @param int $pid The PID of the input template record
     * @param string $templateID The id of the current template. Same syntax as $idList ids, eg. "sys_123
     * @param string $templateParent Parent template id (during recursive call); Same syntax as $idList ids, eg. "sys_123
     * @param string $includePath Specifies the path from which the template was included (used with static_includes)
     * @return void
     * @see runThroughTemplates()
     */
    public function processTemplate($row, $idList, $pid, $templateID = '', $templateParent = '', $includePath = '')
    {
        // Adding basic template record information to rowSum array
        $this->rowSum[] = [$row['uid'], $row['title'], $row['tstamp']];
        // Processing "Clear"-flags
        $clConst = 0;
        $clConf = 0;
        if ($row['clear']) {
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
        // Include static records (static_template) or files (from extensions) (#1/2)
        // NORMAL inclusion, The EXACT same code is found below the basedOn inclusion!!!
        if (!$row['includeStaticAfterBasedOn']) {
            $this->includeStaticTypoScriptSources($idList, $templateID, $pid, $row);
        }
        // Include "Based On" sys_templates:
        // 'basedOn' is a list of templates to include
        if (trim($row['basedOn'])) {
            // Normal Operation, which is to include the "based-on" sys_templates,
            // if they are not already included, and maintaining the sorting of the templates
            $basedOnIds = GeneralUtility::intExplode(',', $row['basedOn'], true);
            // skip template if it's already included
            foreach ($basedOnIds as $key => $basedOnId) {
                if (GeneralUtility::inList($idList, 'sys_' . $basedOnId)) {
                    unset($basedOnIds[$key]);
                }
            }
            if (!empty($basedOnIds)) {
                $subTemplates = $this->getDatabaseConnection()->exec_SELECTgetRows('*', 'sys_template', 'uid IN (' . implode(',', $basedOnIds) . ') ' . $this->whereClause, '', '', '', 'uid');
                // Traversing list again to ensure the sorting of the templates
                foreach ($basedOnIds as $id) {
                    if (is_array($subTemplates[$id])) {
                        $this->versionOL($subTemplates[$id]);
                        $this->processTemplate($subTemplates[$id], $idList . ',sys_' . $id, $pid, 'sys_' . $id, $templateID);
                    }
                }
            }
        }
        // Include static records (static_template) or files (from extensions) (#2/2)
        if ($row['includeStaticAfterBasedOn']) {
            $this->includeStaticTypoScriptSources($idList, $templateID, $pid, $row);
        }
        // Creating hierarchy information; Used by backend analysis tools
        $this->hierarchyInfo[] = ($this->hierarchyInfoToRoot[] = [
            'root' => trim($row['root']),
            'next' => $row['nextLevel'],
            'clConst' => $clConst,
            'clConf' => $clConf,
            'templateID' => $templateID,
            'templateParent' => $templateParent,
            'title' => $row['title'],
            'uid' => $row['uid'],
            'pid' => $row['pid'],
            'configLines' => substr_count($row['config'], LF) + 1
        ]);
        // Adding the content of the fields constants (Constants) and config (Setup)
        $this->constants[] = $row['constants'];
        $this->config[] = $row['config'];
        $this->templateIncludePaths[] = $includePath;
        // For backend analysis (Template Analyser) provide the order of added constants/config template IDs
        $this->clearList_const[] = $templateID;
        $this->clearList_setup[] = $templateID;
        if (trim($row['sitetitle'])) {
            $this->sitetitle = $row['sitetitle'];
        }
        // If the template record is a Rootlevel record, set the flag and clear the template rootLine (so it starts over from this point)
        if (trim($row['root'])) {
            $this->rootId = $pid;
            $this->rootLine = [];
        }
        // If a template is set to be active on the next level set this internal value to point to this UID. (See runThroughTemplates())
        if ($row['nextLevel']) {
            $this->nextLevel = $row['nextLevel'];
        } else {
            $this->nextLevel = 0;
        }
    }

    /**
     * This function can be used to update the data of the current rootLine
     * e.g. when a different language is used.
     *
     * This function must not be used if there are different pages in the
     * rootline as before!
     *
     * @param array $fullRootLine Array containing the FULL rootline (up to the TYPO3 root)
     * @return void
     * @throws \RuntimeException If the given $fullRootLine does not contain all pages that are in the current template rootline
     */
    public function updateRootlineData($fullRootLine)
    {
        if (!is_array($this->rootLine) || empty($this->rootLine)) {
            return;
        }

        $fullRootLineByUid = [];
        foreach ($fullRootLine as $rootLineData) {
            $fullRootLineByUid[$rootLineData['uid']] = $rootLineData;
        }

        foreach ($this->rootLine as $level => $dataArray) {
            $currentUid = $dataArray['uid'];

            if (!array_key_exists($currentUid, $fullRootLineByUid)) {
                throw new \RuntimeException(sprintf('The full rootLine does not contain data for the page with the uid %d that is contained in the template rootline.', $currentUid), 1370419654);
            }

            $this->rootLine[$level] = $fullRootLineByUid[$currentUid];
        }
    }

    /**
     * Includes static template records (from static_template table, loaded through a hook) and static template files (from extensions) for the input template record row.
     *
     * @param string $idList A list of already processed template ids including the current; The list is on the form "[prefix]_[uid]" where [prefix] is "sys" for "sys_template" records, "static" for "static_template" records and "ext_" for static include files (from extensions). The list is used to check that the recursive inclusion of templates does not go into circles: Simply it is used to NOT include a template record/file which has already BEEN included somewhere in the recursion.
     * @param string $templateID The id of the current template. Same syntax as $idList ids, eg. "sys_123
     * @param int $pid The PID of the input template record
     * @param array $row A full TypoScript template record
     * @return void
     * @see processTemplate()
     */
    public function includeStaticTypoScriptSources($idList, $templateID, $pid, $row)
    {
        // Static Template Records (static_template): include_static is a list of static templates to include
        // Call function for link rendering:
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tstemplate.php']['includeStaticTypoScriptSources'])) {
            $_params = [
                'idList' => &$idList,
                'templateId' => &$templateID,
                'pid' => &$pid,
                'row' => &$row
            ];
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tstemplate.php']['includeStaticTypoScriptSources'] as $_funcRef) {
                GeneralUtility::callUserFunction($_funcRef, $_params, $this);
            }
        }
        // If "Include before all static templates if root-flag is set" is set:
        if ($row['static_file_mode'] == 3 && substr($templateID, 0, 4) == 'sys_' && $row['root']) {
            $this->addExtensionStatics($idList, $templateID, $pid, $row);
        }
        // Static Template Files (Text files from extensions): include_static_file is a list of static files to include (from extensions)
        if (trim($row['include_static_file'])) {
            $include_static_fileArr = GeneralUtility::trimExplode(',', $row['include_static_file'], true);
            // Traversing list
            foreach ($include_static_fileArr as $ISF_file) {
                if (substr($ISF_file, 0, 4) == 'EXT:') {
                    list($ISF_extKey, $ISF_localPath) = explode('/', substr($ISF_file, 4), 2);
                    if ((string)$ISF_extKey !== '' && ExtensionManagementUtility::isLoaded($ISF_extKey) && (string)$ISF_localPath !== '') {
                        $ISF_localPath = rtrim($ISF_localPath, '/') . '/';
                        $ISF_filePath = ExtensionManagementUtility::extPath($ISF_extKey) . $ISF_localPath;
                        if (@is_dir($ISF_filePath)) {
                            $mExtKey = str_replace('_', '', $ISF_extKey . '/' . $ISF_localPath);
                            $subrow = [
                                'constants' => $this->getTypoScriptSourceFileContent($ISF_filePath, 'constants'),
                                'config' => $this->getTypoScriptSourceFileContent($ISF_filePath, 'setup'),
                                'include_static' => @file_exists(($ISF_filePath . 'include_static.txt')) ? implode(',', array_unique(GeneralUtility::intExplode(',', GeneralUtility::getUrl($ISF_filePath . 'include_static.txt')))) : '',
                                'include_static_file' => @file_exists(($ISF_filePath . 'include_static_file.txt')) ? implode(',', array_unique(explode(',', GeneralUtility::getUrl($ISF_filePath . 'include_static_file.txt')))) : '',
                                'title' => $ISF_file,
                                'uid' => $mExtKey
                            ];
                            $subrow = $this->prependStaticExtra($subrow);
                            $this->processTemplate($subrow, $idList . ',ext_' . $mExtKey, $pid, 'ext_' . $mExtKey, $templateID, $ISF_filePath);
                        }
                    }
                }
            }
        }
        // If "Default (include before if root flag is set)" is set OR
        // "Always include before this template record" AND root-flag are set
        if ($row['static_file_mode'] == 1 || $row['static_file_mode'] == 0 && substr($templateID, 0, 4) == 'sys_' && $row['root']) {
            $this->addExtensionStatics($idList, $templateID, $pid, $row);
        }
        // Include Static Template Records after all other TypoScript has been included.
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tstemplate.php']['includeStaticTypoScriptSourcesAtEnd'])) {
            $_params = [
                'idList' => &$idList,
                'templateId' => &$templateID,
                'pid' => &$pid,
                'row' => &$row
            ];
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tstemplate.php']['includeStaticTypoScriptSourcesAtEnd'] as $_funcRef) {
                GeneralUtility::callUserFunction($_funcRef, $_params, $this);
            }
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
        $extensions = ['.ts', '.txt'];
        foreach ($extensions as $extension) {
            $fileName = $filePath . $baseName . $extension;
            if (@file_exists($fileName)) {
                return GeneralUtility::getUrl($fileName);
            }
        }
        return '';
    }

    /**
     * Adds the default TypoScript files for extensions if any.
     *
     * @param string $idList A list of already processed template ids including the current; The list is on the form "[prefix]_[uid]" where [prefix] is "sys" for "sys_template" records, "static" for "static_template" records and "ext_" for static include files (from extensions). The list is used to check that the recursive inclusion of templates does not go into circles: Simply it is used to NOT include a template record/file which has already BEEN included somewhere in the recursion.
     * @param string $templateID The id of the current template. Same syntax as $idList ids, eg. "sys_123
     * @param int $pid The PID of the input template record
     * @param array $row A full TypoScript template record
     * @return void
     * @access private
     * @see includeStaticTypoScriptSources()
     */
    public function addExtensionStatics($idList, $templateID, $pid, $row)
    {
        $this->extensionStaticsProcessed = true;

        // @todo Change to use new API
        foreach ($GLOBALS['TYPO3_LOADED_EXT'] as $extKey => $files) {
            if ((is_array($files) || $files instanceof \ArrayAccess) && ($files['ext_typoscript_constants.txt'] || $files['ext_typoscript_setup.txt'])) {
                $mExtKey = str_replace('_', '', $extKey);
                $subrow = [
                    'constants' => $files['ext_typoscript_constants.txt'] ? GeneralUtility::getUrl($files['ext_typoscript_constants.txt']) : '',
                    'config' => $files['ext_typoscript_setup.txt'] ? GeneralUtility::getUrl($files['ext_typoscript_setup.txt']) : '',
                    'title' => $extKey,
                    'uid' => $mExtKey
                ];
                $subrow = $this->prependStaticExtra($subrow);
                $extPath = ExtensionManagementUtility::extPath($extKey);
                $this->processTemplate($subrow, $idList . ',ext_' . $mExtKey, $pid, 'ext_' . $mExtKey, $templateID, $extPath);
            }
        }
    }

    /**
     * Appends (not prepends) additional TypoScript code to static template records/files as set in TYPO3_CONF_VARS
     * For DB records the "uid" value is the integer of the "static_template" record.
     * For files the "uid" value is the extension key but with any underscores removed. Possibly with a path if its a static file selected in the template record
     *
     * @param array $subrow Static template record/file
     * @return array Returns the input array where the values for keys "config" and "constants" may have been modified with prepended code.
     * @access private
     * @see addExtensionStatics(), includeStaticTypoScriptSources()
     */
    public function prependStaticExtra($subrow)
    {
        // the identifier can be "43" if coming from "static template" extension or a path like "cssstyledcontent/static/"
        $identifier = $subrow['uid'];
        $subrow['config'] .= $GLOBALS['TYPO3_CONF_VARS']['FE']['defaultTypoScript_setup.'][$identifier];
        $subrow['constants'] .= $GLOBALS['TYPO3_CONF_VARS']['FE']['defaultTypoScript_constants.'][$identifier];
        // if this is a template of type "default content rendering", also see if other extensions have added their TypoScript that should be included after the content definitions
        if (in_array($identifier, $GLOBALS['TYPO3_CONF_VARS']['FE']['contentRenderingTemplates'], true)) {
            $subrow['config'] .= $GLOBALS['TYPO3_CONF_VARS']['FE']['defaultTypoScript_setup.']['defaultContentRendering'];
            $subrow['constants'] .= $GLOBALS['TYPO3_CONF_VARS']['FE']['defaultTypoScript_constants.']['defaultContentRendering'];
        }
        return $subrow;
    }

    /**
     * Creating versioning overlay of a sys_template record.
     * This will use either frontend or backend overlay functionality depending on environment.
     *
     * @param array $row Row to overlay (passed by reference)
     * @return void
     */
    public function versionOL(&$row)
    {
        // Distinguish frontend and backend call:
        // To do the fronted call a full frontend is required, just checking for
        // TYPO3_MODE === 'FE' is not enough. This could otherwise lead to fatals in
        // eId scripts that run in frontend scope, but do not have a full blown frontend.
        if (is_object($this->getTypoScriptFrontendController()) && property_exists($this->getTypoScriptFrontendController(), 'sys_page') && method_exists($this->getTypoScriptFrontendController()->sys_page, 'versionOL')) {
            // Frontend
            $this->getTypoScriptFrontendController()->sys_page->versionOL('sys_template', $row);
        } else {
            // Backend
            BackendUtility::workspaceOL('sys_template', $row);
        }
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
     * @return void
     * @see \TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser, start()
     */
    public function generateConfig()
    {
        // Add default TS for all code types
        $this->addDefaultTypoScript();

        // Parse the TypoScript code text for include-instructions!
        $this->processIncludes();
        // These vars are also set lateron...
        $this->setup['sitetitle'] = $this->sitetitle;
        // ****************************
        // Parse TypoScript Constants
        // ****************************
        // Initialize parser and match-condition classes:
        /** @var $constants Parser\TypoScriptParser */
        $constants = GeneralUtility::makeInstance(Parser\TypoScriptParser::class);
        $constants->breakPointLN = (int)$this->ext_constants_BRP;
        $constants->setup = $this->mergeConstantsFromPageTSconfig([]);
        /** @var $matchObj ConditionMatcher */
        $matchObj = GeneralUtility::makeInstance(ConditionMatcher::class);
        $matchObj->setSimulateMatchConditions($this->matchAlternative);
        $matchObj->setSimulateMatchResult((bool)$this->matchAll);
        // Traverse constants text fields and parse them
        foreach ($this->constants as $str) {
            $constants->parse($str, $matchObj);
        }
        // Read out parse errors if any
        $this->parserErrors['constants'] = $constants->errors;
        // Then flatten the structure from a multi-dim array to a single dim array with all constants listed as key/value pairs (ready for substitution)
        $this->flatSetup = [];
        $this->flattenSetup($constants->setup, '', '');
        // ***********************************************
        // Parse TypoScript Setup (here called "config")
        // ***********************************************
        // Initialize parser and match-condition classes:
        /** @var $config Parser\TypoScriptParser */
        $config = GeneralUtility::makeInstance(Parser\TypoScriptParser::class);
        $config->breakPointLN = (int)$this->ext_config_BRP;
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
                    $this->getTimeTracker()->setTSlogMessage(implode(', ', $constantList[0]) . ': Constants may remain un-substituted!!', 2);
                }
            }
        }

        // Logging the textual size of the TypoScript Setup field text with all constants substituted:
        if ($this->tt_track) {
            $this->getTimeTracker()->setTSlogMessage('TypoScript template size as textfile: ' . strlen($all) . ' bytes');
        }
        // Finally parse the Setup field TypoScript code (where constants are now substituted)
        $config->parse($all, $matchObj);
        // Read out parse errors if any
        $this->parserErrors['config'] = $config->errors;
        // Transfer the TypoScript array from the parser object to the internal $this->setup array:
        $this->setup = $config->setup;
        if ($this->backend_info) {
            // Used for backend purposes only
            $this->setup_constants = $constants->setup;
        }
        // ****************************************************************
        // Final processing of the $this->setup TypoScript Template array
        // Basically: This is unsetting/setting of certain reserved keys.
        // ****************************************************************
        // These vars are allready set after 'processTemplate', but because $config->setup overrides them (in the line above!), we set them again. They are not changed compared to the value they had in the top of the page!
        unset($this->setup['sitetitle']);
        unset($this->setup['sitetitle.']);
        $this->setup['sitetitle'] = $this->sitetitle;
        // Unsetting some vars...
        unset($this->setup['types.']);
        unset($this->setup['types']);
        if (is_array($this->setup)) {
            foreach ($this->setup as $key => $value) {
                if ($value == 'PAGE') {
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
     * @return void
     * @see \TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser, generateConfig()
     */
    public function processIncludes()
    {
        if ($this->processIncludesHasBeenRun) {
            return;
        }

        $paths = $this->templateIncludePaths;
        $files = [];
        foreach ($this->constants as &$value) {
            $includeData = Parser\TypoScriptParser::checkIncludeLines($value, 1, true, array_shift($paths));
            $files = array_merge($files, $includeData['files']);
            $value = $includeData['typoscript'];
        }
        unset($value);
        $paths = $this->templateIncludePaths;
        foreach ($this->config as &$value) {
            $includeData = Parser\TypoScriptParser::checkIncludeLines($value, 1, true, array_shift($paths));
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
     * Loads Page TSconfig until the outermost template record and parses the configuration - if TSFE.constants object path is found it is merged with the default data in here!
     *
     * @param array $constArray Constants array, default input.
     * @return array Constants array, modified
     * @todo Apply caching to the parsed Page TSconfig. This is done in the other similar functions for both frontend and backend. However, since this functions works for BOTH frontend and backend we will have to either write our own local caching function or (more likely) detect if we are in FE or BE and use caching functions accordingly. Not having caching affects mostly the backend modules inside the "Template" module since the overhead in the frontend is only seen when TypoScript templates are parsed anyways (after which point they are cached anyways...)
     */
    public function mergeConstantsFromPageTSconfig($constArray)
    {
        $TSdataArray = [];
        // Setting default configuration:
        $TSdataArray[] = $GLOBALS['TYPO3_CONF_VARS']['BE']['defaultPageTSconfig'];
        for ($a = 0; $a <= $this->outermostRootlineIndexWithTemplate; $a++) {
            if (trim($this->absoluteRootLine[$a]['tsconfig_includes'])) {
                $includeTsConfigFileList = GeneralUtility::trimExplode(',',
                    $this->absoluteRootLine[$a]['tsconfig_includes'], true);

                $TSdataArray = $this->mergeConstantsFromIncludedTsConfigFiles($includeTsConfigFileList, $TSdataArray);
            }
            $TSdataArray[] = $this->absoluteRootLine[$a]['TSconfig'];
        }
        // Parsing the user TS (or getting from cache)
        $TSdataArray = Parser\TypoScriptParser::checkIncludeLines_array($TSdataArray);
        $userTS = implode(LF . '[GLOBAL]' . LF, $TSdataArray);
        /** @var $parseObj Parser\TypoScriptParser */
        $parseObj = GeneralUtility::makeInstance(Parser\TypoScriptParser::class);
        $parseObj->parse($userTS);
        if (is_array($parseObj->setup['TSFE.']['constants.'])) {
            ArrayUtility::mergeRecursiveWithOverrule($constArray, $parseObj->setup['TSFE.']['constants.']);
        }

        return $constArray;
    }

    /**
     * Reads TSconfig defined in external files and appends it to the given TSconfig array (in this case only constants)
     *
     * @param array $filesToInclude The files to read constants from
     * @param array $TSdataArray The TSconfig array the constants should be appended to
     * @return array The TSconfig with the included constants appended
     */
    protected function mergeConstantsFromIncludedTsConfigFiles($filesToInclude, $TSdataArray)
    {
        foreach ($filesToInclude as $key => $file) {
            if (!StringUtility::beginsWith($file, 'EXT:')) {
                continue;
            }

            list($extensionKey, $filePath) = explode('/', substr($file, 4), 2);

            if ((string)$extensionKey === '' || !ExtensionManagementUtility::isLoaded($extensionKey)) {
                continue;
            }
            if ((string)$filePath == '') {
                continue;
            }

            $tsConfigFile = ExtensionManagementUtility::extPath($extensionKey) . $filePath;
            if (file_exists($tsConfigFile)) {
                $TSdataArray[] = GeneralUtility::getUrl($tsConfigFile);
            }
        }

        return $TSdataArray;
    }

    /**
     * This flattens a hierarchical TypoScript array to $this->flatSetup
     *
     * @param array $setupArray TypoScript array
     * @param string $prefix Prefix to the object path. Used for recursive calls to this function.
     * @return void
     * @see generateConfig()
     */
    public function flattenSetup($setupArray, $prefix)
    {
        if (is_array($setupArray)) {
            foreach ($setupArray as $key => $val) {
                if ($prefix || !StringUtility::beginsWith($key, 'TSConstantEditor')) {
                    // We don't want 'TSConstantEditor' in the flattend setup on the first level (190201)
                    if (is_array($val)) {
                        $this->flattenSetup($val, $prefix . $key);
                    } else {
                        $this->flatSetup[$prefix . $key] = $val;
                    }
                }
            }
        }
    }

    /**
     * Substitutes the constants from $this->flatSetup in the text string $all
     *
     * @param string $all TypoScript code text string
     * @return string The processed string with all constants found in $this->flatSetup as key/value pairs substituted.
     * @see generateConfig(), flattenSetup()
     */
    public function substituteConstants($all)
    {
        if ($this->tt_track) {
            $this->getTimeTracker()->setTSlogMessage('Constants to substitute: ' . count($this->flatSetup));
        }
        $noChange = false;
        // Recursive substitution of constants (up to 10 nested levels)
        for ($i = 0; $i < 10 && !$noChange; $i++) {
            $old_all = $all;
            $all = preg_replace_callback('/\\{\\$(.[^}]*)\\}/', [$this, 'substituteConstantsCallBack'], $all);
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
     * @see substituteConstants()
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
     * Implementation of the "optionSplit" feature in TypoScript (used eg. for MENU objects)
     * What it does is to split the incoming TypoScript array so that the values are exploded by certain strings ("||" and "|*|") and each part distributed into individual TypoScript arrays with a similar structure, but individualized values.
     * The concept is known as "optionSplit" and is rather advanced to handle but quite powerful, in particular for creating menus in TYPO3.
     *
     * @param array $conf A TypoScript array
     * @param int $splitCount The number of items for which to generated individual TypoScript arrays
     * @return array The individualized TypoScript array.
     * @see \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer::IMGTEXT(), \TYPO3\CMS\Frontend\ContentObject\Menu\AbstractMenuContentObject::procesItemStates()
     */
    public function splitConfArray($conf, $splitCount)
    {
        // Initialize variables:
        $splitCount = (int)$splitCount;
        $conf2 = [];
        if ($splitCount && is_array($conf)) {
            // Initialize output to carry at least the keys:
            for ($aKey = 0; $aKey < $splitCount; $aKey++) {
                $conf2[$aKey] = [];
            }
            // Recursive processing of array keys:
            foreach ($conf as $cKey => $val) {
                if (is_array($val)) {
                    $tempConf = $this->splitConfArray($val, $splitCount);
                    foreach ($tempConf as $aKey => $val2) {
                        $conf2[$aKey][$cKey] = $val2;
                    }
                } else {
                    // Splitting of all values on this level of the TypoScript object tree:
                    if ($cKey === 'noTrimWrap' || (!strstr($val, '|*|') && !strstr($val, '||'))) {
                        for ($aKey = 0; $aKey < $splitCount; $aKey++) {
                            $conf2[$aKey][$cKey] = $val;
                        }
                    } else {
                        $main = explode('|*|', $val);
                        $lastC = 0;
                        $middleC = 0;
                        $firstC = 0;
                        if ($main[0]) {
                            $first = explode('||', $main[0]);
                            $firstC = count($first);
                        }
                        $middle = [];
                        if ($main[1]) {
                            $middle = explode('||', $main[1]);
                            $middleC = count($middle);
                        }
                        $last = [];
                        $value = '';
                        if ($main[2]) {
                            $last = explode('||', $main[2]);
                            $lastC = count($last);
                            $value = $last[0];
                        }
                        for ($aKey = 0; $aKey < $splitCount; $aKey++) {
                            if ($firstC && isset($first[$aKey])) {
                                $value = $first[$aKey];
                            } elseif ($middleC) {
                                $value = $middle[($aKey - $firstC) % $middleC];
                            }
                            if ($lastC && $lastC >= $splitCount - $aKey) {
                                $value = $last[$lastC - ($splitCount - $aKey)];
                            }
                            $conf2[$aKey][$cKey] = trim($value);
                        }
                    }
                }
            }
        }
        return $conf2;
    }

    /**
     * Returns the reference used for the frontend inclusion, checks against allowed paths for inclusion.
     *
     * @param string $fileFromSetup TypoScript "resource" data type value.
     * @return string|NULL Resulting filename, is either a full absolute URL or a relative path. Returns NULL if invalid filename or a directory is given
     */
    public function getFileName($fileFromSetup)
    {
        $file = trim($fileFromSetup);
        if (!$file) {
            return null;
        } elseif (strpos($file, '../') !== false) {
            if ($this->tt_track) {
                $this->getTimeTracker()->setTSlogMessage('File path "' . $file . '" contained illegal string "../"!', 3);
            }
            return null;
        }
        // Cache
        $hash = md5($file);
        if (isset($this->fileCache[$hash])) {
            return $this->fileCache[$hash];
        }

        // if this is an URL, it can be returned directly
        $urlScheme = parse_url($file, PHP_URL_SCHEME);
        if ($urlScheme === 'https' || $urlScheme === 'http' || is_file(PATH_site . $file)) {
            return $file;
        }

        // this call also resolves EXT:myext/ files
        $file = GeneralUtility::getFileAbsFileName($file);
        if (!$file) {
            if ($this->tt_track) {
                $this->getTimeTracker()->setTSlogMessage('File "' . $fileFromSetup . '" was not found!', 3);
            }
            return null;
        }

        $file = PathUtility::stripPathSitePrefix($file);

        // Check if the found file is in the allowed paths
        foreach ($this->allowedPaths as $val) {
            if (GeneralUtility::isFirstPartOfStr($file, $val)) {
                $this->fileCache[$hash] = $file;
                return $file;
            }
        }

        if ($this->tt_track) {
            $this->getTimeTracker()->setTSlogMessage('"' . $file . '" was not located in the allowed paths: (' . implode(',', $this->allowedPaths) . ')', 3);
        }
        return null;
    }

    /**
     * Compiles the content for the page <title> tag.
     *
     * @param string $pageTitle The input title string, typically the "title" field of a page's record.
     * @param bool $noTitle If set, then only the site title is outputted (from $this->setup['sitetitle'])
     * @param bool $showTitleFirst If set, then "sitetitle" and $title is swapped
     * @param string $pageTitleSeparator an alternative to the ": " as the separator between site title and page title
     * @return string The page title on the form "[sitetitle]: [input-title]". Not htmlspecialchar()'ed.
     * @see \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController::tempPageCacheContent(), \TYPO3\CMS\Frontend\Page\PageGenerator::renderContentWithHeader()
     */
    public function printTitle($pageTitle, $noTitle = false, $showTitleFirst = false, $pageTitleSeparator = '')
    {
        $siteTitle = trim($this->setup['sitetitle']);
        $pageTitle = $noTitle ? '' : $pageTitle;
        if ($showTitleFirst) {
            $temp = $siteTitle;
            $siteTitle = $pageTitle;
            $pageTitle = $temp;
        }
        // only show a separator if there are both site title and page title
        if ($pageTitle === '' || $siteTitle === '') {
            $pageTitleSeparator = '';
        // use the default separator if non given
        } elseif (empty($pageTitleSeparator)) {
            $pageTitleSeparator = ': ';
        }
        return $siteTitle . $pageTitleSeparator . $pageTitle;
    }

    /**
     * Reads the fileContent of $fileName and returns it.
     * Similar to GeneralUtility::getUrl() but with an additional check if the path is allowed
     *
     * @param string $fileName Absolute filepath to record
     * @return NULL|string The content returned
     * @see \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer::fileResource(), \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer::MULTIMEDIA(), GeneralUtility::getUrl()
     */
    public function fileContent($fileName)
    {
        $fileName = $this->getFileName($fileName);
        if ($fileName) {
            return GeneralUtility::getUrl($fileName);
        }
        return null;
    }

    /**
     * Ordinary "wrapping" function. Used in the AbstractMenuContentObject class and extension classes instead of the similar function in ContentObjectRenderer
     *
     * @param string $content The content to wrap
     * @param string $wrap The wrap value, eg. "<strong> | </strong>
     * @return string Wrapped input string
     * @deprecated since TYPO3 CMS 7, will be removed in TYPO3 CMS 8, use \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer::wrap() instead
     * @see \TYPO3\CMS\Frontend\ContentObject\Menu\AbstractMenuContentObject, \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer::wrap()
     */
    public function wrap($content, $wrap)
    {
        GeneralUtility::logDeprecatedFunction();
        if ($wrap) {
            $wrapArr = explode('|', $wrap);
            return trim($wrapArr[0]) . $content . trim($wrapArr[1]);
        } else {
            return $content;
        }
    }

    /**
     * Removes the "?" of input string IF the "?" is the last character.
     *
     * @param string $url Input string
     * @return string Output string, free of "?" in the end, if any such character.
     * @see linkData(), \TYPO3\CMS\Frontend\Page\FramesetRenderer::frameParams()
     */
    public function removeQueryString($url)
    {
        if (substr($url, -1) == '?') {
            return substr($url, 0, -1);
        } else {
            return $url;
        }
    }

    /**
     * Takes a TypoScript array as input and returns an array which contains all integer properties found which had a value (not only properties). The output array will be sorted numerically.
     * Call it like \TYPO3\CMS\Core\TypoScript\TemplateService::sortedKeyList()
     *
     * @param array $setupArr TypoScript array with numerical array in
     * @param bool $acceptOnlyProperties If set, then a value is not required - the properties alone will be enough.
     * @return array An array with all integer properties listed in numeric order.
     * @see \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer::cObjGet(), \TYPO3\CMS\Frontend\Imaging\GifBuilder, \TYPO3\CMS\Frontend\ContentObject\Menu\ImageMenuContentObject::makeImageMap()
     */
    public static function sortedKeyList($setupArr, $acceptOnlyProperties = false)
    {
        $keyArr = [];
        $setupArrKeys = array_keys($setupArr);
        foreach ($setupArrKeys as $key) {
            if ($acceptOnlyProperties || MathUtility::canBeInterpretedAsInteger($key)) {
                $keyArr[] = (int)$key;
            }
        }
        $keyArr = array_unique($keyArr);
        sort($keyArr);
        return $keyArr;
    }

    /**
     * Returns the level of the given page in the rootline - Multiple pages can be given by separating the UIDs by comma.
     *
     * @param string $list A list of UIDs for which the rootline-level should get returned
     * @return int The level in the rootline. If more than one page was given the lowest level will get returned.
     */
    public function getRootlineLevel($list)
    {
        $idx = 0;
        foreach ($this->rootLine as $page) {
            if (GeneralUtility::inList($list, $page['uid'])) {
                return $idx;
            }
            $idx++;
        }
        return false;
    }

    /*******************************************************************
     *
     * Functions for creating links
     *
     *******************************************************************/
    /**
     * The mother of all functions creating links/URLs etc in a TypoScript environment.
     * See the references below.
     * Basically this function takes care of issues such as type,id,alias and Mount Points, URL rewriting (through hooks), M5/B6 encoded parameters etc.
     * It is important to pass all links created through this function since this is the guarantee that globally configured settings for link creating are observed and that your applications will conform to the various/many configuration options in TypoScript Templates regarding this.
     *
     * @param array $page The page record of the page to which we are creating a link. Needed due to fields like uid, alias, target, no_cache, title and sectionIndex_uid.
     * @param string $oTarget Default target string to use IF not $page['target'] is set.
     * @param bool $no_cache If set, then the "&no_cache=1" parameter is included in the URL.
     * @param string $script Alternative script name if you don't want to use $this->getTypoScriptFrontendController()->config['mainScript'] (normally set to "index.php")
     * @param array $overrideArray Array with overriding values for the $page array.
     * @param string $addParams Additional URL parameters to set in the URL. Syntax is "&foo=bar&foo2=bar2" etc. Also used internally to add parameters if needed.
     * @param string $typeOverride If you set this value to something else than a blank string, then the typeNumber used in the link will be forced to this value. Normally the typeNum is based on the target set OR on $this->getTypoScriptFrontendController()->config['config']['forceTypeValue'] if found.
     * @param string $targetDomain The target Doamin, if any was detected in typolink
     * @return array Contains keys like "totalURL", "url", "sectionIndex", "linkVars", "no_cache", "type", "target" of which "totalURL" is normally the value you would use while the other keys contains various parts that was used to construct "totalURL
     * @see \TYPO3\CMS\Frontend\Page\FramesetRenderer::frameParams(), \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer::typoLink(), \TYPO3\CMS\Frontend\Page\PageGenerator::pagegenInit(), \TYPO3\CMS\Frontend\ContentObject\Menu\AbstractMenuContentObject::link()
     */
    public function linkData($page, $oTarget, $no_cache, $script, $overrideArray = null, $addParams = '', $typeOverride = '', $targetDomain = '')
    {
        $LD = [];
        // Overriding some fields in the page record and still preserves the values by adding them as parameters. Little strange function.
        if (is_array($overrideArray)) {
            foreach ($overrideArray as $theKey => $theNewVal) {
                $addParams .= '&real_' . $theKey . '=' . rawurlencode($page[$theKey]);
                $page[$theKey] = $theNewVal;
            }
        }
        // Adding Mount Points, "&MP=", parameter for the current page if any is set:
        if (!strstr($addParams, '&MP=')) {
            // Looking for hardcoded defaults:
            if (trim($this->getTypoScriptFrontendController()->MP_defaults[$page['uid']])) {
                $addParams .= '&MP=' . rawurlencode(trim($this->getTypoScriptFrontendController()->MP_defaults[$page['uid']]));
            } elseif ($this->getTypoScriptFrontendController()->config['config']['MP_mapRootPoints']) {
                // Else look in automatically created map:
                $m = $this->getFromMPmap($page['uid']);
                if ($m) {
                    $addParams .= '&MP=' . rawurlencode($m);
                }
            }
        }
        // Setting ID/alias:
        if (!$script) {
            $script = $this->getTypoScriptFrontendController()->config['mainScript'];
        }
        if ($page['alias']) {
            $LD['url'] = $script . '?id=' . rawurlencode($page['alias']);
        } else {
            $LD['url'] = $script . '?id=' . $page['uid'];
        }
        // Setting target
        $LD['target'] = trim($page['target']) ?: $oTarget;
        // typeNum
        $typeNum = $this->setup[$LD['target'] . '.']['typeNum'];
        if (!MathUtility::canBeInterpretedAsInteger($typeOverride) && (int)$this->getTypoScriptFrontendController()->config['config']['forceTypeValue']) {
            $typeOverride = (int)$this->getTypoScriptFrontendController()->config['config']['forceTypeValue'];
        }
        if ((string)$typeOverride !== '') {
            $typeNum = $typeOverride;
        }
        // Override...
        if ($typeNum) {
            $LD['type'] = '&type=' . (int)$typeNum;
        } else {
            $LD['type'] = '';
        }
        // Preserving the type number.
        $LD['orig_type'] = $LD['type'];
        // noCache
        $LD['no_cache'] = trim($page['no_cache']) || $no_cache ? '&no_cache=1' : '';
        // linkVars
        if ($addParams) {
            $LD['linkVars'] = GeneralUtility::implodeArrayForUrl('', GeneralUtility::explodeUrl2Array($this->getTypoScriptFrontendController()->linkVars . $addParams), '', false, true);
        } else {
            $LD['linkVars'] = $this->getTypoScriptFrontendController()->linkVars;
        }
        // Add absRefPrefix if exists.
        $LD['url'] = $this->getTypoScriptFrontendController()->absRefPrefix . $LD['url'];
        // If the special key 'sectionIndex_uid' (added 'manually' in tslib/menu.php to the page-record) is set, then the link jumps directly to a section on the page.
        $LD['sectionIndex'] = $page['sectionIndex_uid'] ? '#c' . $page['sectionIndex_uid'] : '';
        // Compile the normal total url
        $LD['totalURL'] = $this->removeQueryString(($LD['url'] . $LD['type'] . $LD['no_cache'] . $LD['linkVars'] . $this->getTypoScriptFrontendController()->getMethodUrlIdToken)) . $LD['sectionIndex'];
        // Call post processing function for link rendering:
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tstemplate.php']['linkData-PostProc'])) {
            $_params = [
                'LD' => &$LD,
                'args' => ['page' => $page, 'oTarget' => $oTarget, 'no_cache' => $no_cache, 'script' => $script, 'overrideArray' => $overrideArray, 'addParams' => $addParams, 'typeOverride' => $typeOverride, 'targetDomain' => $targetDomain],
                'typeNum' => $typeNum
            ];
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tstemplate.php']['linkData-PostProc'] as $_funcRef) {
                GeneralUtility::callUserFunction($_funcRef, $_params, $this);
            }
        }
        // Return the LD-array
        return $LD;
    }

    /**
     * Initializes the automatically created MPmap coming from the "config.MP_mapRootPoints" setting
     * Can be called many times with overhead only the first time since then the map is generated and cached in memory.
     *
     * @param int $pageId Page id to return MPvar value for.
     * @return string
     * @see initMPmap_create()
     * @todo Implement some caching of the result between hits. (more than just the memory caching used here)
     */
    public function getFromMPmap($pageId = 0)
    {
        // Create map if not found already:
        if (!is_array($this->MPmap)) {
            $this->MPmap = [];
            $rootPoints = GeneralUtility::trimExplode(',', strtolower($this->getTypoScriptFrontendController()->config['config']['MP_mapRootPoints']), true);
            // Traverse rootpoints:
            foreach ($rootPoints as $p) {
                $initMParray = [];
                if ($p == 'root') {
                    $p = $this->rootLine[0]['uid'];
                    if ($this->rootLine[0]['_MOUNT_OL'] && $this->rootLine[0]['_MP_PARAM']) {
                        $initMParray[] = $this->rootLine[0]['_MP_PARAM'];
                    }
                }
                $this->initMPmap_create($p, $initMParray);
            }
        }
        // Finding MP var for Page ID:
        if ($pageId) {
            if (is_array($this->MPmap[$pageId]) && !empty($this->MPmap[$pageId])) {
                return implode(',', $this->MPmap[$pageId]);
            }
        }
        return '';
    }

    /**
     * Creating MPmap for a certain ID root point.
     *
     * @param int $id Root id from which to start map creation.
     * @param array $MP_array MP_array passed from root page.
     * @param int $level Recursion brake. Incremented for each recursive call. 20 is the limit.
     * @return void
     * @see getFromMPvar()
     */
    public function initMPmap_create($id, $MP_array = [], $level = 0)
    {
        $id = (int)$id;
        if ($id <= 0) {
            return;
        }
        // First level, check id
        if (!$level) {
            // Find mount point if any:
            $mount_info = $this->getTypoScriptFrontendController()->sys_page->getMountPointInfo($id);
            // Overlay mode:
            if (is_array($mount_info) && $mount_info['overlay']) {
                $MP_array[] = $mount_info['MPvar'];
                $id = $mount_info['mount_pid'];
            }
            // Set mapping information for this level:
            $this->MPmap[$id] = $MP_array;
            // Normal mode:
            if (is_array($mount_info) && !$mount_info['overlay']) {
                $MP_array[] = $mount_info['MPvar'];
                $id = $mount_info['mount_pid'];
            }
        }
        if ($id && $level < 20) {
            $nextLevelAcc = [];
            // Select and traverse current level pages:
            $res = $this->getDatabaseConnection()->exec_SELECTquery('uid,pid,doktype,mount_pid,mount_pid_ol', 'pages', 'pid=' . (int)$id . ' AND deleted=0 AND doktype<>' . PageRepository::DOKTYPE_RECYCLER . ' AND doktype<>' . PageRepository::DOKTYPE_BE_USER_SECTION);
            while ($row = $this->getDatabaseConnection()->sql_fetch_assoc($res)) {
                // Find mount point if any:
                $next_id = $row['uid'];
                $next_MP_array = $MP_array;
                $mount_info = $this->getTypoScriptFrontendController()->sys_page->getMountPointInfo($next_id, $row);
                // Overlay mode:
                if (is_array($mount_info) && $mount_info['overlay']) {
                    $next_MP_array[] = $mount_info['MPvar'];
                    $next_id = $mount_info['mount_pid'];
                }
                if (!isset($this->MPmap[$next_id])) {
                    // Set mapping information for this level:
                    $this->MPmap[$next_id] = $next_MP_array;
                    // Normal mode:
                    if (is_array($mount_info) && !$mount_info['overlay']) {
                        $next_MP_array[] = $mount_info['MPvar'];
                        $next_id = $mount_info['mount_pid'];
                    }
                    // Register recursive call
                    // (have to do it this way since ALL of the current level should be registered BEFORE the sublevel at any time)
                    $nextLevelAcc[] = [$next_id, $next_MP_array];
                }
            }
            $this->getDatabaseConnection()->sql_free_result($res);
            // Call recursively, if any:
            foreach ($nextLevelAcc as $pSet) {
                $this->initMPmap_create($pSet[0], $pSet[1], $level + 1);
            }
        }
    }

    /**
     * Adds the TypoScript from the global array.
     * The class property isDefaultTypoScriptAdded ensures
     * that the adding only happens once.
     *
     * @return void
     * @see isDefaultTypoScriptAdded
     */
    protected function addDefaultTypoScript()
    {
        // Add default TS for all code types, if not done already
        if (!$this->isDefaultTypoScriptAdded) {
            // adding default setup and constants
            // defaultTypoScript_setup is *very* unlikely to be empty
            // the count of elements in ->constants, ->config and ->templateIncludePaths have to be in sync
            array_unshift($this->constants, (string)$GLOBALS['TYPO3_CONF_VARS']['FE']['defaultTypoScript_constants']);
            array_unshift($this->config, (string)$GLOBALS['TYPO3_CONF_VARS']['FE']['defaultTypoScript_setup']);
            array_unshift($this->templateIncludePaths, '');
            // prepare a proper entry to hierachyInfo (used by TemplateAnalyzer in BE)
            $rootTemplateId = $this->hierarchyInfo[count($this->hierarchyInfo)-1]['templateID'];
            $defaultTemplateInfo = [
                'root' => '',
                'next' => '',
                'clConst' => '',
                'clConf' => '',
                'templateID' => '_defaultTypoScript_',
                'templateParent' => $rootTemplateId,
                'title' => 'SYS:TYPO3_CONF_VARS:FE:defaultTypoScript',
                'uid' => '_defaultTypoScript_',
                'pid' => '',
                'configLines' => substr_count((string)$GLOBALS['TYPO3_CONF_VARS']['FE']['defaultTypoScript_setup'], LF) + 1
            ];
            // push info to information arrays used in BE by TemplateTools (Analyzer)
            array_unshift($this->clearList_const, $defaultTemplateInfo['uid']);
            array_unshift($this->clearList_setup, $defaultTemplateInfo['uid']);
            array_unshift($this->hierarchyInfo, $defaultTemplateInfo);
            $this->isDefaultTypoScriptAdded = true;
        }
    }

    /**
     * @return DatabaseConnection
     */
    protected function getDatabaseConnection()
    {
        return $GLOBALS['TYPO3_DB'];
    }

    /**
     * @return TypoScriptFrontendController
     */
    protected function getTypoScriptFrontendController()
    {
        return $GLOBALS['TSFE'];
    }

    /**
     * @return TimeTracker
     */
    protected function getTimeTracker()
    {
        return $GLOBALS['TT'];
    }
}
