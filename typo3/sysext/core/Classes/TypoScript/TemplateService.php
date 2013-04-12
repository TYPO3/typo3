<?php
namespace TYPO3\CMS\Core\TypoScript;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 1999-2013 Kasper Skårhøj (kasperYYYY@typo3.com)
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
/**
 * Class with template object that is responsible for generating the template
 *
 * Revised for TYPO3 3.6 July/2003 by Kasper Skårhøj
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
/**
 * Template object that is responsible for generating the TypoScript template based on template records.
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 * @see \TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser
 * @see \TYPO3\CMS\Core\Configuration\TypoScript\ConditionMatching\AbstractConditionMatcher
 */
class TemplateService {

	// Debugging, analysis:
	// If set, the global tt-timeobject is used to log the performance.
	/**
	 * @todo Define visibility
	 */
	public $tt_track = 1;

	// If set, the template is always rendered. Used from Admin Panel.
	/**
	 * @todo Define visibility
	 */
	public $forceTemplateParsing = 0;

	// Backend Analysis modules settings:
	// This array is passed on to matchObj by generateConfig(). If it holds elements, they are used for matching instead. See commment at the match-class. Used for backend modules only. Never frontend!
	/**
	 * @todo Define visibility
	 */
	public $matchAlternative = array();

	// If set, the match-class matches everything! Used for backend modules only. Never frontend!
	/**
	 * @todo Define visibility
	 */
	public $matchAll = 0;

	/**
	 * @todo Define visibility
	 */
	public $backend_info = 0;

	// Set from the backend - used to set an absolute path (PATH_site) so that relative resources are properly found with getFileName()
	/**
	 * @todo Define visibility
	 */
	public $getFileName_backPath = '';

	// Externally set breakpoints (used by Backend Modules)
	/**
	 * @todo Define visibility
	 */
	public $ext_constants_BRP = 0;

	/**
	 * @todo Define visibility
	 */
	public $ext_config_BRP = 0;

	/**
	 * @todo Define visibility
	 */
	public $ext_regLinenumbers = FALSE;

	/**
	 * @todo Define visibility
	 */
	public $ext_regComments = FALSE;

	// Constants:
	/**
	 * @todo Define visibility
	 */
	public $tempPath = 'typo3temp/';

	// Set Internally:
	// This MUST be initialized by the init() function
	/**
	 * @todo Define visibility
	 */
	public $whereClause = '';

	/**
	 * @todo Define visibility
	 */
	public $debug = 0;

	// This is the only paths (relative!!) that are allowed for resources in TypoScript. Should all be appended with '/'. You can extend these by the global array TYPO3_CONF_VARS. See init() function.
	/**
	 * @todo Define visibility
	 */
	public $allowedPaths = array();

	// See init(); Set if preview of some kind is enabled.
	/**
	 * @todo Define visibility
	 */
	public $simulationHiddenOrTime = 0;

	// Set, if the TypoScript template structure is loaded and OK, see ->start()
	/**
	 * @todo Define visibility
	 */
	public $loaded = 0;

	// Default TypoScript Setup code
	/**
	 * @todo Define visibility
	 */
	public $setup = array(
		'styles.' => array(
			'insertContent' => 'CONTENT',
			'insertContent.' => array(
				'table' => 'tt_content',
				'select.' => array(
					'orderBy' => 'sorting',
					'where' => 'colPos=0',
					'languageField' => 'sys_language_uid'
				)
			)
		),
		'config.' => array(
			'extTarget' => '_top',
			'uniqueLinkVars' => 1
		)
	);

	/**
	 * @todo Define visibility
	 */
	public $flatSetup = array();

	// Default TypoScript Constants code:
	/**
	 * @todo Define visibility
	 */
	public $const = array(
		'_clear' => '<img src="clear.gif" width="1" height="1" alt="" />',
		'_blackBorderWrap' => '<table border="0" bgcolor="black" cellspacing="0" cellpadding="1"><tr><td> | </td></tr></table>',
		'_tableWrap' => '<table border="0" cellspacing="0" cellpadding="0"> | </table>',
		'_tableWrap_DEBUG' => '<table border="1" cellspacing="0" cellpadding="0"> | </table>',
		'_stdFrameParams' => 'frameborder="no" marginheight="0" marginwidth="0" noresize="noresize"',
		'_stdFramesetParams' => 'border="0" framespacing="0" frameborder="no"'
	);

	// For fetching TypoScript code from template hierarchy before parsing it. Each array contains code field values from template records/files:
	// Setup field
	/**
	 * @todo Define visibility
	 */
	public $config = array();

	// Constant field
	/**
	 * @todo Define visibility
	 */
	public $constants = array();

	// For Template Analyser in backend
	/**
	 * @todo Define visibility
	 */
	public $hierarchyInfo = array();

	// For Template Analyser in backend (setup content only)
	/**
	 * @todo Define visibility
	 */
	public $hierarchyInfoToRoot = array();

	// Next-level flag (see runThroughTemplates())
	/**
	 * @todo Define visibility
	 */
	public $nextLevel = 0;

	// The Page UID of the root page
	/**
	 * @todo Define visibility
	 */
	public $rootId;

	// The rootline from current page to the root page
	/**
	 * @todo Define visibility
	 */
	public $rootLine;

	// Rootline all the way to the root. Set but runThroughTemplates
	/**
	 * @todo Define visibility
	 */
	public $absoluteRootLine;

	// A pointer to the last entry in the rootline where a template was found.
	/**
	 * @todo Define visibility
	 */
	public $outermostRootlineIndexWithTemplate = 0;

	// Array of arrays with title/uid of templates in hierarchy
	/**
	 * @todo Define visibility
	 */
	public $rowSum;

	// The current site title field.
	/**
	 * @todo Define visibility
	 */
	public $sitetitle = '';

	// Tracking all conditions found during parsing of TypoScript. Used for the "all" key in currentPageData
	/**
	 * @todo Define visibility
	 */
	public $sections;

	// Tracking all matching conditions found
	/**
	 * @todo Define visibility
	 */
	public $sectionsMatch;

	// Backend: ts_analyzer
	/**
	 * @todo Define visibility
	 */
	public $clearList_const = array();

	/**
	 * @todo Define visibility
	 */
	public $clearList_setup = array();

	/**
	 * @todo Define visibility
	 */
	public $parserErrors = array();

	/**
	 * @todo Define visibility
	 */
	public $setup_constants = array();

	// Other:
	// Used by getFileName for caching of references to file resources
	/**
	 * @todo Define visibility
	 */
	public $fileCache = array();

	// Keys are frame names and values are type-values, which must be used to refer correctly to the content of the frames.
	/**
	 * @todo Define visibility
	 */
	public $frames = array();

	// Contains mapping of Page id numbers to MP variables.
	/**
	 * @todo Define visibility
	 */
	public $MPmap = '';

	/**
	 * Indicator that extension statics are processed.
	 *
	 * These files are considered if either a root template
	 * has been processed or the $processExtensionStatics
	 * property has been set to TRUE.
	 *
	 * @var boolean
	 */
	protected $extensionStaticsProcessed = FALSE;

	/**
	 * Trigger value, to ensure that extension statics are processed.
	 *
	 * @var boolean
	 */
	protected $processExtensionStatics = FALSE;

	/**
	 * @return boolean
	 */
	public function getProcessExtensionStatics() {
		return $this->processExtensionStatics;
	}

	/**
	 * @param boolean $processExtensionStatics
	 */
	public function setProcessExtensionStatics($processExtensionStatics) {
		$this->processExtensionStatics = (bool) $processExtensionStatics;
	}

	/**
	 * Initialize
	 * MUST be called directly after creating a new template-object
	 *
	 * @return void
	 * @see tslib_fe::initTemplate()
	 * @todo Define visibility
	 */
	public function init() {
		// $this->whereClause is used only to select templates from sys_template.
		// $GLOBALS['SIM_ACCESS_TIME'] is used so that we're able to simulate a later time as a test...
		$this->whereClause = 'AND deleted=0 ';
		if (!$GLOBALS['TSFE']->showHiddenRecords) {
			$this->whereClause .= 'AND hidden=0 ';
		}
		if ($GLOBALS['TSFE']->showHiddenRecords || $GLOBALS['SIM_ACCESS_TIME'] != $GLOBALS['ACCESS_TIME']) {
			// Set the simulation flag, if simulation is detected!
			$this->simulationHiddenOrTime = 1;
		}
		$this->whereClause .= 'AND (starttime<=' . $GLOBALS['SIM_ACCESS_TIME'] . ') AND (endtime=0 OR endtime>' . $GLOBALS['SIM_ACCESS_TIME'] . ')';
		// Sets the paths from where TypoScript resources are allowed to be used:
		$this->allowedPaths = array(
			'media/',
			$GLOBALS['TYPO3_CONF_VARS']['BE']['fileadminDir'],
			// fileadmin/ path
			'uploads/',
			'typo3temp/',
			't3lib/fonts/',
			TYPO3_mainDir . 'ext/',
			TYPO3_mainDir . 'sysext/',
			TYPO3_mainDir . 'contrib/',
			'typo3conf/ext/'
		);
		if ($GLOBALS['TYPO3_CONF_VARS']['FE']['addAllowedPaths']) {
			$pathArr = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $GLOBALS['TYPO3_CONF_VARS']['FE']['addAllowedPaths'], TRUE);
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
	 * of template uid's which is used on the page. In the getFromCache function
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
	public function getCurrentPageData() {
		return $GLOBALS['typo3CacheManager']->getCache('cache_pagesection')->get(intval($GLOBALS['TSFE']->id) . '_' . \TYPO3\CMS\Core\Utility\GeneralUtility::md5int($GLOBALS['TSFE']->MP));
	}

	/**
	 * Fetches data about which TypoScript-matches there are at this page. Then it performs a matchingtest.
	 *
	 * @param array $cc An array with three keys, "all", "rowSum" and "rootLine" - all coming from the "currentPageData" array
	 * @return array The input array but with a new key added, "match" which contains the items from the "all" key which when passed to tslib_matchCondition returned TRUE.
	 * @todo Define visibility
	 */
	public function matching($cc) {
		if (is_array($cc['all'])) {
			/** @var $matchObj \TYPO3\CMS\Frontend\Configuration\TypoScript\ConditionMatching\ConditionMatcher */
			$matchObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Frontend\\Configuration\\TypoScript\\ConditionMatching\\ConditionMatcher');
			$matchObj->setRootline((array) $cc['rootLine']);
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
	 * @see tslib_fe::getConfigArray()
	 * @todo Define visibility
	 */
	public function start($theRootLine) {
		if (is_array($theRootLine)) {
			$setupData = '';
			$hash = '';
			// Flag that indicates that the existing data in cache_pagesection
			// could be used (this is the case if $TSFE->all is set, and the
			// rowSum still matches). Based on this we decide if cache_pagesection
			// needs to be updated...
			$isCached = FALSE;
			$this->runThroughTemplates($theRootLine);
			if ($GLOBALS['TSFE']->all) {
				$cc = $GLOBALS['TSFE']->all;
				// The two rowSums must NOT be different from each other - which they will be if start/endtime or hidden has changed!
				if (strcmp(serialize($this->rowSum), serialize($cc['rowSum']))) {
					unset($cc);
				} else {
					// If $TSFE->all contains valid data, we don't need to update cache_pagesection (because this data was fetched from there already)
					if (!strcmp(serialize($this->rootLine), serialize($cc['rootLine']))) {
						$isCached = TRUE;
					}
					// When the data is serialized below (ROWSUM hash), it must not contain the rootline by concept. So this must be removed (and added again later)...
					unset($cc['rootLine']);
				}
			}
			// This is about getting the hash string which is used to fetch the cached TypoScript template.
			// If there was some cached currentPageData ($cc) then that's good (it gives us the hash).
			if (is_array($cc)) {
				// If currentPageData was actually there, we match the result (if this wasn't done already in $TSFE->getFromCache()...)
				if (!$cc['match']) {
					// TODO: check if this can ever be the case - otherwise remove
					$cc = $this->matching($cc);
					ksort($cc);
				}
				$hash = md5(serialize($cc));
			} else {
				// If currentPageData was not there, we first find $rowSum (freshly generated). After that we try to see, if it is stored with a list of all conditions. If so we match the result.
				$rowSumHash = md5('ROWSUM:' . serialize($this->rowSum));
				$result = \TYPO3\CMS\Frontend\Page\PageRepository::getHash($rowSumHash);
				if ($result) {
					$cc = array();
					$cc['all'] = unserialize($result);
					$cc['rowSum'] = $this->rowSum;
					$cc = $this->matching($cc);
					ksort($cc);
					$hash = md5(serialize($cc));
				}
			}
			if ($hash) {
				// Get TypoScript setup array
				$setupData = \TYPO3\CMS\Frontend\Page\PageRepository::getHash($hash);
			}
			if ($setupData && !$this->forceTemplateParsing) {
				// If TypoScript setup structure was cached we unserialize it here:
				$this->setup = unserialize($setupData);
			} else {
				// Make configuration
				$this->generateConfig();
				// This stores the template hash thing
				$cc = array();
				// All sections in the template at this point is found
				$cc['all'] = $this->sections;
				// The line of templates is collected
				$cc['rowSum'] = $this->rowSum;
				$cc = $this->matching($cc);
				ksort($cc);
				$hash = md5(serialize($cc));
				// This stores the data.
				\TYPO3\CMS\Frontend\Page\PageRepository::storeHash($hash, serialize($this->setup), 'TS_TEMPLATE');
				if ($this->tt_track) {
					$GLOBALS['TT']->setTSlogMessage('TS template size, serialized: ' . strlen(serialize($this->setup)) . ' bytes');
				}
				$rowSumHash = md5('ROWSUM:' . serialize($this->rowSum));
				\TYPO3\CMS\Frontend\Page\PageRepository::storeHash($rowSumHash, serialize($cc['all']), 'TMPL_CONDITIONS_ALL');
			}
			// Add rootLine
			$cc['rootLine'] = $this->rootLine;
			ksort($cc);
			// Make global and save
			$GLOBALS['TSFE']->all = $cc;
			// Matching must be executed for every request, so this must never be part of the pagesection cache!
			unset($cc['match']);
			if (!$isCached && !$this->simulationHiddenOrTime && !$GLOBALS['TSFE']->no_cache) {
				// Only save the data if we're not simulating by hidden/starttime/endtime
				$mpvarHash = \TYPO3\CMS\Core\Utility\GeneralUtility::md5int($GLOBALS['TSFE']->MP);
				/** @var $pageSectionCache \TYPO3\CMS\Core\Cache\Frontend\FrontendInterface */
				$pageSectionCache = $GLOBALS['typo3CacheManager']->getCache('cache_pagesection');
				$pageSectionCache->set(intval($GLOBALS['TSFE']->id) . '_' . $mpvarHash, $cc, array(
					'pageId_' . intval($GLOBALS['TSFE']->id),
					'mpvarHash_' . $mpvarHash
				));
			}
			// If everything OK.
			if ($this->rootId && $this->rootLine && $this->setup) {
				$this->loaded = 1;
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
	 * Also creates $this->rootLine which is a root line stopping at the root template (contrary to $GLOBALS['TSFE']->rootLine which goes all the way to the root of the tree
	 *
	 * @param array $theRootLine The rootline of the current page (going ALL the way to tree root)
	 * @param integer $start_template_uid Set specific template record UID to select; this is only for debugging/development/analysis use in backend modules like "Web > Template". For parsing TypoScript templates in the frontend it should be 0 (zero)
	 * @return void
	 * @see start()
	 * @todo Define visibility
	 */
	public function runThroughTemplates($theRootLine, $start_template_uid = 0) {
		$this->constants = array();
		$this->config = array();
		$this->rowSum = array();
		$this->hierarchyInfoToRoot = array();
		// Is the TOTAL rootline
		$this->absoluteRootLine = $theRootLine;
		reset($this->absoluteRootLine);
		$c = count($this->absoluteRootLine);
		for ($a = 0; $a < $c; $a++) {
			// If some template loaded before has set a template-id for the next level, then load this template first!
			if ($this->nextLevel) {
				$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'sys_template', 'uid=' . intval($this->nextLevel) . ' ' . $this->whereClause);
				$this->nextLevel = 0;
				if ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
					$this->versionOL($row);
					if (is_array($row)) {
						$this->processTemplate($row, 'sys_' . $row['uid'], $this->absoluteRootLine[$a]['uid'], 'sys_' . $row['uid']);
						$this->outermostRootlineIndexWithTemplate = $a;
					}
				}
				$GLOBALS['TYPO3_DB']->sql_free_result($res);
			}
			$addC = '';
			// If first loop AND there is set an alternative template uid, use that
			if ($a == $c - 1 && $start_template_uid) {
				$addC = ' AND uid=' . intval($start_template_uid);
			}
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'sys_template', 'pid=' . intval($this->absoluteRootLine[$a]['uid']) . $addC . ' ' . $this->whereClause, '', 'sorting', 1);
			if ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				$this->versionOL($row);
				if (is_array($row)) {
					$this->processTemplate($row, 'sys_' . $row['uid'], $this->absoluteRootLine[$a]['uid'], 'sys_' . $row['uid']);
					$this->outermostRootlineIndexWithTemplate = $a;
				}
			}
			$GLOBALS['TYPO3_DB']->sql_free_result($res);
			$this->rootLine[] = $this->absoluteRootLine[$a];
		}
		// Process extension static files if not done yet, but explicitly requested
		if (!$this->extensionStaticsProcessed && $this->processExtensionStatics) {
			$this->addExtensionStatics('sys_0', 'sys_0', 0, array());
		}
		$this->processIncludes();
	}

	/**
	 * Checks if the template ($row) has some included templates and after including them it fills the arrays with the setup
	 * Builds up $this->rowSum
	 *
	 * @param array $row A full TypoScript template record (sys_template/static_template/forged "dummy" record made from static template file)
	 * @param string $idList A list of already processed template ids including the current; The list is on the form "[prefix]_[uid]" where [prefix] is "sys" for "sys_template" records, "static" for "static_template" records and "ext_" for static include files (from extensions). The list is used to check that the recursive inclusion of templates does not go into circles: Simply it is used to NOT include a template record/file which has already BEEN included somewhere in the recursion.
	 * @param array $pid The PID of the input template record
	 * @param string $templateID The id of the current template. Same syntax as $idList ids, eg. "sys_123
	 * @param string $templateParent Parent template id (during recursive call); Same syntax as $idList ids, eg. "sys_123
	 * @return void
	 * @see runThroughTemplates()
	 * @todo Define visibility
	 */
	public function processTemplate($row, $idList, $pid, $templateID = '', $templateParent = '') {
		// Adding basic template record information to rowSum array
		$this->rowSum[] = array($row['uid'], $row['title'], $row['tstamp']);
		// Processing "Clear"-flags
		if ($row['clear']) {
			$clConst = $row['clear'] & 1;
			$clConf = $row['clear'] & 2;
			if ($clConst) {
				$this->constants = array();
				$this->clearList_const = array();
			}
			if ($clConf) {
				$this->config = array();
				$this->hierarchyInfoToRoot = array();
				$this->clearList_setup = array();
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
			// Manually you can put this value in the field and then the based_on ID will be taken from the $_GET var defined by '=....'.
			// Example: If $row['basedOn'] is 'EXTERNAL_BASED_ON_TEMPLATE_ID=based_on_uid', then the global var, based_on_uid - given by the URL like '&based_on_uid=999' - is included instead!
			// This feature allows us a hack to test/demonstrate various included templates on the same set of content bearing pages. Used by the "freesite" extension.
			$basedOn_hackFeature = explode('=', $row['basedOn']);
			if ($basedOn_hackFeature[0] == 'EXTERNAL_BASED_ON_TEMPLATE_ID' && $basedOn_hackFeature[1]) {
				$id = intval(\TYPO3\CMS\Core\Utility\GeneralUtility::_GET($basedOn_hackFeature[1]));
				// If $id is not allready included ...
				if ($id && !\TYPO3\CMS\Core\Utility\GeneralUtility::inList($idList, ('sys_' . $id))) {
					$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'sys_template', 'uid=' . $id . ' ' . $this->whereClause);
					// there was a template, then we fetch that
					if ($subrow = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
						$this->versionOL($subrow);
						if (is_array($subrow)) {
							$this->processTemplate($subrow, $idList . ',sys_' . $id, $pid, 'sys_' . $id, $templateID);
						}
					}
					$GLOBALS['TYPO3_DB']->sql_free_result($res);
				}
			} else {
				// Normal Operation, which is to include the "based-on" sys_templates,
				// if they are not already included, and maintaining the sorting of the templates
				$basedOnIds = \TYPO3\CMS\Core\Utility\GeneralUtility::intExplode(',', $row['basedOn']);
				// skip template if it's already included
				foreach ($basedOnIds as $key => $basedOnId) {
					if (\TYPO3\CMS\Core\Utility\GeneralUtility::inList($idList, 'sys_' . $basedOnId)) {
						unset($basedOnIds[$key]);
					}
				}
				$subTemplates = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('*', 'sys_template', 'uid IN (' . implode(',', $basedOnIds) . ') ' . $this->whereClause, '', '', '', 'uid');
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
		$this->hierarchyInfo[] = ($this->hierarchyInfoToRoot[] = array(
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
		));
		// Adding the content of the fields constants (Constants) and config (Setup)
		$this->constants[] = $row['constants'];
		$this->config[] = $row['config'];
		// For backend analysis (Template Analyser) provide the order of added constants/config template IDs
		$this->clearList_const[] = $templateID;
		$this->clearList_setup[] = $templateID;
		if (trim($row['sitetitle'])) {
			$this->sitetitle = $row['sitetitle'];
		}
		// If the template record is a Rootlevel record, set the flag and clear the template rootLine (so it starts over from this point)
		if (trim($row['root'])) {
			$this->rootId = $pid;
			$this->rootLine = array();
		}
		// If a template is set to be active on the next level set this internal value to point to this UID. (See runThroughTemplates())
		if ($row['nextLevel']) {
			$this->nextLevel = $row['nextLevel'];
		} else {
			$this->nextLevel = 0;
		}
	}

	/**
	 * Includes static template records (from static_template table, loaded through a hook) and static template files (from extensions) for the input template record row.
	 *
	 * @param string $idList A list of already processed template ids including the current; The list is on the form "[prefix]_[uid]" where [prefix] is "sys" for "sys_template" records, "static" for "static_template" records and "ext_" for static include files (from extensions). The list is used to check that the recursive inclusion of templates does not go into circles: Simply it is used to NOT include a template record/file which has already BEEN included somewhere in the recursion.
	 * @param string $templateID The id of the current template. Same syntax as $idList ids, eg. "sys_123
	 * @param array $pid The PID of the input template record
	 * @param array $row A full TypoScript template record
	 * @return void
	 * @see processTemplate()
	 * @todo Define visibility
	 */
	public function includeStaticTypoScriptSources($idList, $templateID, $pid, $row) {
		// Static Template Records (static_template): include_static is a list of static templates to include
		// Call function for link rendering:
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tstemplate.php']['includeStaticTypoScriptSources'])) {
			$_params = array(
				'idList' => &$idList,
				'templateId' => &$templateID,
				'pid' => &$pid,
				'row' => &$row
			);
			foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tstemplate.php']['includeStaticTypoScriptSources'] as $_funcRef) {
				\TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($_funcRef, $_params, $this);
			}
		}
		// If "Include before all static templates if root-flag is set" is set:
		if ($row['static_file_mode'] == 3 && substr($templateID, 0, 4) == 'sys_' && $row['root']) {
			$this->addExtensionStatics($idList, $templateID, $pid, $row);
		}
		// Static Template Files (Text files from extensions): include_static_file is a list of static files to include (from extensions)
		if (trim($row['include_static_file'])) {
			$include_static_fileArr = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $row['include_static_file'], TRUE);
			// Traversing list
			foreach ($include_static_fileArr as $ISF_file) {
				if (substr($ISF_file, 0, 4) == 'EXT:') {
					list($ISF_extKey, $ISF_localPath) = explode('/', substr($ISF_file, 4), 2);
					if (strcmp($ISF_extKey, '') && \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded($ISF_extKey) && strcmp($ISF_localPath, '')) {
						$ISF_localPath = rtrim($ISF_localPath, '/') . '/';
						$ISF_filePath = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($ISF_extKey) . $ISF_localPath;
						if (@is_dir($ISF_filePath)) {
							$mExtKey = str_replace('_', '', $ISF_extKey . '/' . $ISF_localPath);
							$subrow = array(
								'constants' => @is_file(($ISF_filePath . 'constants.txt')) ? \TYPO3\CMS\Core\Utility\GeneralUtility::getUrl($ISF_filePath . 'constants.txt') : '',
								'config' => @is_file(($ISF_filePath . 'setup.txt')) ? \TYPO3\CMS\Core\Utility\GeneralUtility::getUrl($ISF_filePath . 'setup.txt') : '',
								'include_static' => @is_file(($ISF_filePath . 'include_static.txt')) ? implode(',', array_unique(\TYPO3\CMS\Core\Utility\GeneralUtility::intExplode(',', \TYPO3\CMS\Core\Utility\GeneralUtility::getUrl($ISF_filePath . 'include_static.txt')))) : '',
								'include_static_file' => @is_file(($ISF_filePath . 'include_static_file.txt')) ? implode(',', array_unique(explode(',', \TYPO3\CMS\Core\Utility\GeneralUtility::getUrl($ISF_filePath . 'include_static_file.txt')))) : '',
								'title' => $ISF_file,
								'uid' => $mExtKey
							);
							$subrow = $this->prependStaticExtra($subrow);
							$this->processTemplate($subrow, $idList . ',ext_' . $mExtKey, $pid, 'ext_' . $mExtKey, $templateID);
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
			$_params = array(
				'idList' => &$idList,
				'templateId' => &$templateID,
				'pid' => &$pid,
				'row' => &$row
			);
			foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tstemplate.php']['includeStaticTypoScriptSourcesAtEnd'] as $_funcRef) {
				\TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($_funcRef, $_params, $this);
			}
		}
	}

	/**
	 * Adds the default TypoScript files for extensions if any.
	 *
	 * @param string $idList A list of already processed template ids including the current; The list is on the form "[prefix]_[uid]" where [prefix] is "sys" for "sys_template" records, "static" for "static_template" records and "ext_" for static include files (from extensions). The list is used to check that the recursive inclusion of templates does not go into circles: Simply it is used to NOT include a template record/file which has already BEEN included somewhere in the recursion.
	 * @param string $templateID The id of the current template. Same syntax as $idList ids, eg. "sys_123
	 * @param array $pid The PID of the input template record
	 * @param array $row A full TypoScript template record
	 * @return void
	 * @access private
	 * @see includeStaticTypoScriptSources()
	 * @todo Define visibility
	 */
	public function addExtensionStatics($idList, $templateID, $pid, $row) {
		$this->extensionStaticsProcessed = TRUE;

		foreach ($GLOBALS['TYPO3_LOADED_EXT'] as $extKey => $files) {
			if (is_array($files) && ($files['ext_typoscript_constants.txt'] || $files['ext_typoscript_setup.txt'])) {
				$mExtKey = str_replace('_', '', $extKey);
				$subrow = array(
					'constants' => $files['ext_typoscript_constants.txt'] ? \TYPO3\CMS\Core\Utility\GeneralUtility::getUrl($files['ext_typoscript_constants.txt']) : '',
					'config' => $files['ext_typoscript_setup.txt'] ? \TYPO3\CMS\Core\Utility\GeneralUtility::getUrl($files['ext_typoscript_setup.txt']) : '',
					'title' => $extKey,
					'uid' => $mExtKey
				);
				$subrow = $this->prependStaticExtra($subrow);
				$this->processTemplate($subrow, $idList . ',ext_' . $mExtKey, $pid, 'ext_' . $mExtKey, $templateID);
			}
		}
	}

	/**
	 * Appends (not prepends) additional TypoScript code to static template records/files as set in TYPO3_CONF_VARS
	 * For records the "uid" value is the integer of the "static_template" record
	 * For files the "uid" value is the extension key but with any underscores removed. Possibly with a path if its a static file selected in the template record
	 *
	 * @param array $subrow Static template record/file
	 * @return array Returns the input array where the values for keys "config" and "constants" may have been modified with prepended code.
	 * @access private
	 * @see addExtensionStatics(), includeStaticTypoScriptSources()
	 * @todo Define visibility
	 */
	public function prependStaticExtra($subrow) {
		$subrow['config'] .= $GLOBALS['TYPO3_CONF_VARS']['FE']['defaultTypoScript_setup.'][$subrow['uid']];
		$subrow['constants'] .= $GLOBALS['TYPO3_CONF_VARS']['FE']['defaultTypoScript_constants.'][$subrow['uid']];
		return $subrow;
	}

	/**
	 * Creating versioning overlay of a sys_template record.
	 * This will use either frontend or backend overlay functionality depending on environment.
	 *
	 * @param array $row Row to overlay (passed by reference)
	 * @return void
	 * @todo Define visibility
	 */
	public function versionOL(&$row) {
		// Distinguish frontend and backend call:
		// To do the fronted call a full frontend is required, just checking for
		// TYPO3_MODE === 'FE' is not enough. This could otherwise lead to fatals in
		// eId scripts that run in frontend scope, but do not have a full blown frontend.
		if (is_object($GLOBALS['TSFE']) === TRUE && property_exists($GLOBALS['TSFE'], 'sys_page') === TRUE && method_exists($GLOBALS['TSFE']->sys_page, 'versionOL') === TRUE) {
			// Frontend
			$GLOBALS['TSFE']->sys_page->versionOL('sys_template', $row);
		} else {
			// Backend
			\TYPO3\CMS\Backend\Utility\BackendUtility::workspaceOL('sys_template', $row);
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
	 * @todo Define visibility
	 */
	public function generateConfig() {
		// Add default TS for all three code types:
		array_unshift($this->constants, '' . $GLOBALS['TYPO3_CONF_VARS']['FE']['defaultTypoScript_constants']);
		// Adding default TS/constants
		array_unshift($this->config, '' . $GLOBALS['TYPO3_CONF_VARS']['FE']['defaultTypoScript_setup']);
		// Adding default TS/setup
		// Parse the TypoScript code text for include-instructions!
		$this->processIncludes();
		// These vars are also set lateron...
		$this->setup['sitetitle'] = $this->sitetitle;
		// ****************************
		// Parse TypoScript Constants
		// ****************************
		// Initialize parser and match-condition classes:
		/** @var $constants \TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser */
		$constants = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\TypoScript\\Parser\\TypoScriptParser');
		$constants->breakPointLN = intval($this->ext_constants_BRP);
		$constants->setup = $this->const;
		$constants->setup = $this->mergeConstantsFromPageTSconfig($constants->setup);
		/** @var $matchObj \TYPO3\CMS\Frontend\Configuration\TypoScript\ConditionMatching\ConditionMatcher */
		$matchObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Frontend\\Configuration\\TypoScript\\ConditionMatching\\ConditionMatcher');
		$matchObj->setSimulateMatchConditions($this->matchAlternative);
		$matchObj->setSimulateMatchResult((bool) $this->matchAll);
		// Traverse constants text fields and parse them
		foreach ($this->constants as $str) {
			$constants->parse($str, $matchObj);
		}
		// Read out parse errors if any
		$this->parserErrors['constants'] = $constants->errors;
		// Then flatten the structure from a multi-dim array to a single dim array with all constants listed as key/value pairs (ready for substitution)
		$this->flatSetup = array();
		$this->flattenSetup($constants->setup, '', '');
		// ***********************************************
		// Parse TypoScript Setup (here called "config")
		// ***********************************************
		// Initialize parser and match-condition classes:
		/** @var $config \TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser */
		$config = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\TypoScript\\Parser\\TypoScriptParser');
		$config->breakPointLN = intval($this->ext_config_BRP);
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
			$GLOBALS['TT']->push('Substitute Constants (' . count($this->flatSetup) . ')');
		}
		$all = $this->substituteConstants($all);
		if ($this->tt_track) {
			$GLOBALS['TT']->pull();
		}
		// Searching for possible unsubstituted constants left (only for information)
		if (strstr($all, '{$')) {
			$theConstList = array();
			$findConst = explode('{$', $all);
			array_shift($findConst);
			foreach ($findConst as $constVal) {
				$constLen = \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange(strcspn($constVal, '}'), 0, 50);
				$theConstList[] = '{$' . substr($constVal, 0, ($constLen + 1));
			}
			if ($this->tt_track) {
				$GLOBALS['TT']->setTSlogMessage(implode(', ', $theConstList) . ': Constants may remain un-substituted!!', 2);
			}
		}
		// Logging the textual size of the TypoScript Setup field text with all constants substituted:
		if ($this->tt_track) {
			$GLOBALS['TT']->setTSlogMessage('TypoScript template size as textfile: ' . strlen($all) . ' bytes');
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
		unset($this->setup['styles.']);
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
	public function processIncludes() {
		$files = array();
		foreach ($this->constants as &$value) {
			$includeData = \TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser::checkIncludeLines($value, 1, TRUE);
			$files = array_merge($files, $includeData['files']);
			$value = $includeData['typoscript'];
		}
		unset($value);
		foreach ($this->config as &$value) {
			$includeData = \TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser::checkIncludeLines($value, 1, TRUE);
			$files = array_merge($files, $includeData['files']);
			$value = $includeData['typoscript'];
		}
		unset($value);
		if (count($files)) {
			$files = array_unique($files);
			foreach ($files as $file) {
				$this->rowSum[] = array($file, filemtime($file));
			}
		}
	}

	/**
	 * Loads Page TSconfig until the outermost template record and parses the configuration - if TSFE.constants object path is found it is merged with the default data in here!
	 *
	 * @param array $constArray Constants array, default input.
	 * @return array Constants array, modified
	 * @todo Apply caching to the parsed Page TSconfig. This is done in the other similar functions for both frontend and backend. However, since this functions works for BOTH frontend and backend we will have to either write our own local caching function or (more likely) detect if we are in FE or BE and use caching functions accordingly. Not having caching affects mostly the backend modules inside the "Template" module since the overhead in the frontend is only seen when TypoScript templates are parsed anyways (after which point they are cached anyways...)
	 * @todo Define visibility
	 */
	public function mergeConstantsFromPageTSconfig($constArray) {
		$TSdataArray = array();
		// Setting default configuration:
		$TSdataArray[] = $GLOBALS['TYPO3_CONF_VARS']['BE']['defaultPageTSconfig'];
		for ($a = 0; $a <= $this->outermostRootlineIndexWithTemplate; $a++) {
			$TSdataArray[] = $this->absoluteRootLine[$a]['TSconfig'];
		}
		// Parsing the user TS (or getting from cache)
		$TSdataArray = \TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser::checkIncludeLines_array($TSdataArray);
		$userTS = implode(LF . '[GLOBAL]' . LF, $TSdataArray);
		/** @var $parseObj \TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser */
		$parseObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\TypoScript\\Parser\\TypoScriptParser');
		$parseObj->parse($userTS);
		if (is_array($parseObj->setup['TSFE.']['constants.'])) {
			$constArray = \TYPO3\CMS\Core\Utility\GeneralUtility::array_merge_recursive_overrule($constArray, $parseObj->setup['TSFE.']['constants.']);
		}
		return $constArray;
	}

	/**
	 * This flattens a hierarchical TypoScript array to $this->flatSetup
	 *
	 * @param array $setupArray TypoScript array
	 * @param string $prefix Prefix to the object path. Used for recursive calls to this function.
	 * @param boolean $resourceFlag If set, then the constant value will be resolved as a TypoScript "resource" data type. Also used internally during recursive calls so that all subproperties for properties named "file." will be resolved as resources.
	 * @return void
	 * @see generateConfig()
	 * @todo Define visibility
	 */
	public function flattenSetup($setupArray, $prefix, $resourceFlag) {
		if (is_array($setupArray)) {
			foreach ($setupArray as $key => $val) {
				if ($prefix || substr($key, 0, 16) != 'TSConstantEditor') {
					// We don't want 'TSConstantEditor' in the flattend setup on the first level (190201)
					if (is_array($val)) {
						$this->flattenSetup($val, $prefix . $key, $key == 'file.');
					} elseif ($resourceFlag) {
						$this->flatSetup[$prefix . $key] = $this->getFileName($val);
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
	 * @todo Define visibility
	 */
	public function substituteConstants($all) {
		if ($this->tt_track) {
			$GLOBALS['TT']->setTSlogMessage('Constants to substitute: ' . count($this->flatSetup));
		}
		$noChange = FALSE;
		// Recursive substitution of constants (up to 10 nested levels)
		for ($i = 0; $i < 10 && !$noChange; $i++) {
			$old_all = $all;
			$all = preg_replace_callback('/\\{\\$(.[^}]*)\\}/', array($this, 'substituteConstantsCallBack'), $all);
			if ($old_all == $all) {
				$noChange = TRUE;
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
	 * @todo Define visibility
	 */
	public function substituteConstantsCallBack($matches) {
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
	 * @param integer $splitCount The number of items for which to generated individual TypoScript arrays
	 * @return array The individualized TypoScript array.
	 * @see tslib_cObj::IMGTEXT(), tslib_menu::procesItemStates()
	 * @todo Define visibility
	 */
	public function splitConfArray($conf, $splitCount) {
		// Initialize variables:
		$splitCount = intval($splitCount);
		$conf2 = array();
		if ($splitCount && is_array($conf)) {
			// Initialize output to carry at least the keys:
			for ($aKey = 0; $aKey < $splitCount; $aKey++) {
				$conf2[$aKey] = array();
			}
			// Recursive processing of array keys:
			foreach ($conf as $cKey => $val) {
				if (is_array($val)) {
					$tempConf = $this->splitConfArray($val, $splitCount);
					foreach ($tempConf as $aKey => $val) {
						$conf2[$aKey][$cKey] = $val;
					}
				} else {
					// Splitting of all values on this level of the TypoScript object tree:
					if (!strstr($val, '|*|') && !strstr($val, '||')) {
						for ($aKey = 0; $aKey < $splitCount; $aKey++) {
							$conf2[$aKey][$cKey] = $val;
						}
					} else {
						$main = explode('|*|', $val);
						$mainCount = count($main);
						$lastC = 0;
						$middleC = 0;
						$firstC = 0;
						if ($main[0]) {
							$first = explode('||', $main[0]);
							$firstC = count($first);
						}
						if ($main[1]) {
							$middle = explode('||', $main[1]);
							$middleC = count($middle);
						}
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
	 * Returns the reference to a 'resource' in TypoScript.
	 * This could be from the filesystem if '/' is found in the value $fileFromSetup, else from the resource-list
	 *
	 * @param string $fileFromSetup TypoScript "resource" data type value.
	 * @return string Resulting filename, if any.
	 * @todo Define visibility
	 */
	public function getFileName($fileFromSetup) {
		$file = trim($fileFromSetup);
		if (!$file) {
			return;
		} elseif (strstr($file, '../')) {
			if ($this->tt_track) {
				$GLOBALS['TT']->setTSlogMessage('File path "' . $file . '" contained illegal string "../"!', 3);
			}
			return;
		}
		// Cache
		$hash = md5($file);
		if (isset($this->fileCache[$hash])) {
			return $this->fileCache[$hash];
		}
		if (!strcmp(substr($file, 0, 4), 'EXT:')) {
			$newFile = '';
			list($extKey, $script) = explode('/', substr($file, 4), 2);
			if ($extKey && \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded($extKey)) {
				$extPath = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($extKey);
				$newFile = substr($extPath, strlen(PATH_site)) . $script;
			}
			if (!@is_file((PATH_site . $newFile))) {
				if ($this->tt_track) {
					$GLOBALS['TT']->setTSlogMessage('Extension media file "' . $newFile . '" was not found!', 3);
				}
				return;
			} else {
				$file = $newFile;
			}
		}
		if (parse_url($file) !== FALSE) {
			return $file;
		}
		// Find
		if (strpos($file, '/') !== FALSE) {
			// If the file is in the media/ folder but it doesn't exist,
			// it is assumed that it's in the tslib folder
			if (\TYPO3\CMS\Core\Utility\GeneralUtility::isFirstPartOfStr($file, 'media/') && !is_file(($this->getFileName_backPath . $file))) {
				$file = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::siteRelPath('cms') . 'tslib/' . $file;
			}
			if (is_file($this->getFileName_backPath . $file)) {
				$outFile = $file;
				$fileInfo = \TYPO3\CMS\Core\Utility\GeneralUtility::split_fileref($outFile);
				$OK = 0;
				foreach ($this->allowedPaths as $val) {
					if (substr($fileInfo['path'], 0, strlen($val)) == $val) {
						$OK = 1;
						break;
					}
				}
				if ($OK) {
					$this->fileCache[$hash] = $outFile;
					return $outFile;
				} elseif ($this->tt_track) {
					$GLOBALS['TT']->setTSlogMessage('"' . $file . '" was not located in the allowed paths: (' . implode(',', $this->allowedPaths) . ')', 3);
				}
			} elseif ($this->tt_track) {
				$GLOBALS['TT']->setTSlogMessage('"' . $this->getFileName_backPath . $file . '" is not a file (non-uploads/.. resource, did not exist).', 3);
			}
		}
	}

	/**
	 * Compiles the content for the page <title> tag.
	 *
	 * @param string $pageTitle The input title string, typically the "title" field of a page's record.
	 * @param boolean $noTitle If set, then only the site title is outputted (from $this->setup['sitetitle'])
	 * @param boolean $showTitleFirst If set, then "sitetitle" and $title is swapped
	 * @return string The page title on the form "[sitetitle]: [input-title]". Not htmlspecialchar()'ed.
	 * @see tslib_fe::tempPageCacheContent(), TSpagegen::renderContentWithHeader()
	 * @todo Define visibility
	 */
	public function printTitle($pageTitle, $noTitle = FALSE, $showTitleFirst = FALSE) {
		$siteTitle = trim($this->setup['sitetitle']) ? $this->setup['sitetitle'] : '';
		$pageTitle = $noTitle ? '' : $pageTitle;
		$pageTitleSeparator = '';
		if ($showTitleFirst) {
			$temp = $siteTitle;
			$siteTitle = $pageTitle;
			$pageTitle = $temp;
		}
		if ($pageTitle != '' && $siteTitle != '') {
			$pageTitleSeparator = ': ';
			if (isset($this->setup['config.']['pageTitleSeparator']) && $this->setup['config.']['pageTitleSeparator']) {
				$pageTitleSeparator = $this->setup['config.']['pageTitleSeparator'];

				if (is_object($GLOBALS['TSFE']->cObj) && isset($this->setup['config.']['pageTitleSeparator.']) && is_array($this->setup['config.']['pageTitleSeparator.'])) {
					$pageTitleSeparator = $GLOBALS['TSFE']->cObj->stdWrap($pageTitleSeparator, $this->setup['config.']['pageTitleSeparator.']);
				} else {
					$pageTitleSeparator .= ' ';
				}
			}
		}
		return $siteTitle . $pageTitleSeparator . $pageTitle;
	}

	/**
	 * Reads the fileContent of $fName and returns it.
	 * Similar to \TYPO3\CMS\Core\Utility\GeneralUtility::getUrl()
	 *
	 * @param string $fName Absolute filepath to record
	 * @return string The content returned
	 * @see tslib_cObj::fileResource(), tslib_cObj::MULTIMEDIA(), \TYPO3\CMS\Core\Utility\GeneralUtility::getUrl()
	 * @todo Define visibility
	 */
	public function fileContent($fName) {
		$incFile = $this->getFileName($fName);
		if ($incFile) {
			return @file_get_contents($incFile);
		}
	}

	/**
	 * Ordinary "wrapping" function. Used in the tslib_menu class and extension classes instead of the similar function in tslib_cObj
	 *
	 * @param string $content The content to wrap
	 * @param string $wrap The wrap value, eg. "<strong> | </strong>
	 * @return string Wrapped input string
	 * @see tslib_menu, tslib_cObj::wrap()
	 * @todo Define visibility
	 */
	public function wrap($content, $wrap) {
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
	 * @see linkData(), tslib_frameset::frameParams()
	 * @todo Define visibility
	 */
	public function removeQueryString($url) {
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
	 * @param boolean $acceptOnlyProperties If set, then a value is not required - the properties alone will be enough.
	 * @return array An array with all integer properties listed in numeric order.
	 * @see tslib_cObj::cObjGet(), \TYPO3\CMS\Frontend\Imaging\GifBuilder, tslib_imgmenu::makeImageMap()
	 */
	static public function sortedKeyList($setupArr, $acceptOnlyProperties = FALSE) {
		$keyArr = array();
		$setupArrKeys = array_keys($setupArr);
		foreach ($setupArrKeys as $key) {
			if ($acceptOnlyProperties || \TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($key)) {
				$keyArr[] = intval($key);
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
	 * @return integer The level in the rootline. If more than one page was given the lowest level will get returned.
	 * @todo Define visibility
	 */
	public function getRootlineLevel($list) {
		$idx = 0;
		foreach ($this->rootLine as $page) {
			if (\TYPO3\CMS\Core\Utility\GeneralUtility::inList($list, $page['uid'])) {
				return $idx;
			}
			$idx++;
		}
		return FALSE;
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
	 * @param boolean $no_cache If set, then the "&no_cache=1" parameter is included in the URL.
	 * @param string $script Alternative script name if you don't want to use $GLOBALS['TSFE']->config['mainScript'] (normally set to "index.php")
	 * @param array $overrideArray Array with overriding values for the $page array.
	 * @param string $addParams Additional URL parameters to set in the URL. Syntax is "&foo=bar&foo2=bar2" etc. Also used internally to add parameters if needed.
	 * @param string $typeOverride If you set this value to something else than a blank string, then the typeNumber used in the link will be forced to this value. Normally the typeNum is based on the target set OR on $GLOBALS['TSFE']->config['config']['forceTypeValue'] if found.
	 * @param string $targetDomain The target Doamin, if any was detected in typolink
	 * @return array Contains keys like "totalURL", "url", "sectionIndex", "linkVars", "no_cache", "type", "target" of which "totalURL" is normally the value you would use while the other keys contains various parts that was used to construct "totalURL
	 * @see tslib_frameset::frameParams(), tslib_cObj::typoLink(), tslib_cObj::SEARCHRESULT(), TSpagegen::pagegenInit(), tslib_menu::link()
	 * @todo Define visibility
	 */
	public function linkData($page, $oTarget, $no_cache, $script, $overrideArray = NULL, $addParams = '', $typeOverride = '', $targetDomain = '') {
		$LD = array();
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
			if (trim($GLOBALS['TSFE']->MP_defaults[$page['uid']])) {
				$addParams .= '&MP=' . rawurlencode(trim($GLOBALS['TSFE']->MP_defaults[$page['uid']]));
			} elseif ($GLOBALS['TSFE']->config['config']['MP_mapRootPoints']) {
				// Else look in automatically created map:
				$m = $this->getFromMPmap($page['uid']);
				if ($m) {
					$addParams .= '&MP=' . rawurlencode($m);
				}
			}
		}
		// Setting ID/alias:
		if (!$script) {
			$script = $GLOBALS['TSFE']->config['mainScript'];
		}
		if ($page['alias']) {
			$LD['url'] = $script . '?id=' . rawurlencode($page['alias']);
		} else {
			$LD['url'] = $script . '?id=' . $page['uid'];
		}
		// Setting target
		$LD['target'] = trim($page['target']) ? trim($page['target']) : $oTarget;
		// typeNum
		$typeNum = $this->setup[$LD['target'] . '.']['typeNum'];
		if (!\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($typeOverride) && intval($GLOBALS['TSFE']->config['config']['forceTypeValue'])) {
			$typeOverride = intval($GLOBALS['TSFE']->config['config']['forceTypeValue']);
		}
		if (strcmp($typeOverride, '')) {
			$typeNum = $typeOverride;
		}
		// Override...
		if ($typeNum) {
			$LD['type'] = '&type=' . intval($typeNum);
		} else {
			$LD['type'] = '';
		}
		// Preserving the type number.
		$LD['orig_type'] = $LD['type'];
		// noCache
		$LD['no_cache'] = trim($page['no_cache']) || $no_cache ? '&no_cache=1' : '';
		// linkVars
		if ($GLOBALS['TSFE']->config['config']['uniqueLinkVars']) {
			if ($addParams) {
				$LD['linkVars'] = \TYPO3\CMS\Core\Utility\GeneralUtility::implodeArrayForUrl('', \TYPO3\CMS\Core\Utility\GeneralUtility::explodeUrl2Array($GLOBALS['TSFE']->linkVars . $addParams), '', FALSE, TRUE);
			} else {
				$LD['linkVars'] = $GLOBALS['TSFE']->linkVars;
			}
		} else {
			$LD['linkVars'] = $GLOBALS['TSFE']->linkVars . $addParams;
		}
		// Add absRefPrefix if exists.
		$LD['url'] = $GLOBALS['TSFE']->absRefPrefix . $LD['url'];
		// If the special key 'sectionIndex_uid' (added 'manually' in tslib/menu.php to the page-record) is set, then the link jumps directly to a section on the page.
		$LD['sectionIndex'] = $page['sectionIndex_uid'] ? '#c' . $page['sectionIndex_uid'] : '';
		// Compile the normal total url
		$LD['totalURL'] = $this->removeQueryString(($LD['url'] . $LD['type'] . $LD['no_cache'] . $LD['linkVars'] . $GLOBALS['TSFE']->getMethodUrlIdToken)) . $LD['sectionIndex'];
		// Call post processing function for link rendering:
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tstemplate.php']['linkData-PostProc'])) {
			$_params = array(
				'LD' => &$LD,
				'args' => array('page' => $page, 'oTarget' => $oTarget, 'no_cache' => $no_cache, 'script' => $script, 'overrideArray' => $overrideArray, 'addParams' => $addParams, 'typeOverride' => $typeOverride, 'targetDomain' => $targetDomain),
				'typeNum' => $typeNum
			);
			foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tstemplate.php']['linkData-PostProc'] as $_funcRef) {
				\TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($_funcRef, $_params, $this);
			}
		}
		// Return the LD-array
		return $LD;
	}

	/**
	 * Initializes the automatically created MPmap coming from the "config.MP_mapRootPoints" setting
	 * Can be called many times with overhead only the first time since then the map is generated and cached in memory.
	 *
	 * @param integer $pageId Page id to return MPvar value for.
	 * @return string
	 * @see initMPmap_create()
	 * @todo Implement some caching of the result between hits. (more than just the memory caching used here)
	 * @todo Define visibility
	 */
	public function getFromMPmap($pageId = 0) {
		// Create map if not found already:
		if (!is_array($this->MPmap)) {
			$this->MPmap = array();
			$rootPoints = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', strtolower($GLOBALS['TSFE']->config['config']['MP_mapRootPoints']), 1);
			// Traverse rootpoints:
			foreach ($rootPoints as $p) {
				if ($p == 'root') {
					$p = $this->rootLine[0]['uid'];
					$initMParray = array();
					if ($this->rootLine[0]['_MOUNT_OL'] && $this->rootLine[0]['_MP_PARAM']) {
						$initMParray[] = $this->rootLine[0]['_MP_PARAM'];
					}
				}
				$this->initMPmap_create($p, $initMParray);
			}
		}
		// Finding MP var for Page ID:
		if ($pageId) {
			if (is_array($this->MPmap[$pageId]) && count($this->MPmap[$pageId])) {
				return implode(',', $this->MPmap[$pageId]);
			}
		}
	}

	/**
	 * Creating MPmap for a certain ID root point.
	 *
	 * @param integer $id Root id from which to start map creation.
	 * @param array $MP_array MP_array passed from root page.
	 * @param integer $level Recursion brake. Incremented for each recursive call. 20 is the limit.
	 * @return void
	 * @see getFromMPvar()
	 * @todo Define visibility
	 */
	public function initMPmap_create($id, $MP_array = array(), $level = 0) {
		$id = intval($id);
		if ($id <= 0) {
			return;
		}
		// First level, check id
		if (!$level) {
			// Find mount point if any:
			$mount_info = $GLOBALS['TSFE']->sys_page->getMountPointInfo($id);
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
			$nextLevelAcc = array();
			// Select and traverse current level pages:
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid,pid,doktype,mount_pid,mount_pid_ol', 'pages', 'pid=' . intval($id) . ' AND deleted=0 AND doktype<>' . \TYPO3\CMS\Frontend\Page\PageRepository::DOKTYPE_RECYCLER . ' AND doktype<>' . \TYPO3\CMS\Frontend\Page\PageRepository::DOKTYPE_BE_USER_SECTION);
			while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				// Find mount point if any:
				$next_id = $row['uid'];
				$next_MP_array = $MP_array;
				$mount_info = $GLOBALS['TSFE']->sys_page->getMountPointInfo($next_id, $row);
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
					$nextLevelAcc[] = array($next_id, $next_MP_array);
				}
			}
			$GLOBALS['TYPO3_DB']->sql_free_result($res);
			// Call recursively, if any:
			foreach ($nextLevelAcc as $pSet) {
				$this->initMPmap_create($pSet[0], $pSet[1], $level + 1);
			}
		}
	}

}


?>
