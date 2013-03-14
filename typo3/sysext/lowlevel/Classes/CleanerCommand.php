<?php
namespace TYPO3\CMS\Lowlevel;

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
 * Core functions for cleaning and analysing
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
/**
 * Core functions for cleaning and analysing
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
class CleanerCommand extends \TYPO3\CMS\Core\Controller\CommandLineController {

	/**
	 * @todo Define visibility
	 */
	public $genTree_traverseDeleted = TRUE;

	/**
	 * @todo Define visibility
	 */
	public $genTree_traverseVersions = TRUE;

	/**
	 * @todo Define visibility
	 */
	public $label_infoString = 'The list of records is organized as [table]:[uid]:[field]:[flexpointer]:[softref_key]';

	/**
	 * @todo Define visibility
	 */
	public $pagetreePlugins = array();

	/**
	 * @todo Define visibility
	 */
	public $cleanerModules = array();

	/**
	 * @todo Define visibility
	 */
	public $performanceStatistics = array();

	protected $workspaceIndex = array();

	/**
	 * Constructor
	 *
	 * @todo Define visibility
	 */
	public function __construct() {
		// Running parent class constructor
		parent::__construct();
		$this->cleanerModules = (array) $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['lowlevel']['cleanerModules'];
		// Adding options to help archive:
		$this->cli_options[] = array('-r', 'Execute this tool, otherwise help is shown');
		$this->cli_options[] = array('-v level', 'Verbosity level 0-3', 'The value of level can be:
  0 = all output
  1 = info and greater (default)
  2 = warnings and greater
  3 = errors');
		$this->cli_options[] = array('--refindex mode', 'Mode for reference index handling for operations that require a clean reference index ("update"/"ignore")', 'Options are "check" (default), "update" and "ignore". By default, the reference index is checked before running analysis that require a clean index. If the check fails, the analysis is not run. You can choose to bypass this completely (using value "ignore") or ask to have the index updated right away before the analysis (using value "update")');
		$this->cli_options[] = array('--AUTOFIX [testName]', 'Repairs errors that can be automatically fixed.', 'Only add this option after having run the test without it so you know what will happen when you add this option! The optional parameter "[testName]" works for some tool keys to limit the fixing to a particular test.');
		$this->cli_options[] = array('--dryrun', 'With --AUTOFIX it will only simulate a repair process', 'You may like to use this to see what the --AUTOFIX option will be doing. It will output the whole process like if a fix really occurred but nothing is in fact happening');
		$this->cli_options[] = array('--YES', 'Implicit YES to all questions', 'Use this with EXTREME care. The option "-i" is not affected by this option.');
		$this->cli_options[] = array('-i', 'Interactive', 'Will ask you before running the AUTOFIX on each element.');
		$this->cli_options[] = array('--filterRegex expr', 'Define an expression for preg_match() that must match the element ID in order to auto repair it', 'The element ID is the string in quotation marks when the text \'Cleaning ... in "ELEMENT ID"\'. "expr" is the expression for preg_match(). To match for example "Nature3.JPG" and "Holiday3.JPG" you can use "/.*3.JPG/". To match for example "Image.jpg" and "Image.JPG" you can use "/.*.jpg/i". Try a --dryrun first to see what the matches are!');
		$this->cli_options[] = array('--showhowto', 'Displays HOWTO file for cleaner script.');
		// Setting help texts:
		$this->cli_help['name'] = 'lowlevel_cleaner -- Analysis and clean-up tools for TYPO3 installations';
		$this->cli_help['synopsis'] = 'toolkey ###OPTIONS###';
		$this->cli_help['description'] = 'Dispatches to various analysis and clean-up tools which can plug into the API of this script. Typically you can run tests that will take longer than the usual max execution time of PHP. Such tasks could be checking for orphan records in the page tree or flushing all published versions in the system. For the complete list of options, please explore each of the \'toolkey\' keywords below:

  ' . implode('
  ', array_keys($this->cleanerModules));
		$this->cli_help['examples'] = '/.../cli_dispatch.phpsh lowlevel_cleaner missing_files -s -r
This will show you missing files in the TYPO3 system and only report back if errors were found.';
		$this->cli_help['author'] = 'Kasper Skaarhoej, (c) 2006';
	}

	/**************************
	 *
	 * CLI functionality
	 *
	 *************************/
	/**
	 * CLI engine
	 *
	 * @param array $argv Command line arguments
	 * @return string
	 * @todo Define visibility
	 */
	public function cli_main($argv) {

		$this->cli_setArguments($argv);

		// Force user to admin state and set workspace to "Live":
		$GLOBALS['BE_USER']->user['admin'] = 1;
		$GLOBALS['BE_USER']->setWorkspace(0);
		// Print Howto:
		if ($this->cli_isArg('--showhowto')) {
			$howto = \TYPO3\CMS\Core\Utility\GeneralUtility::getUrl(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('lowlevel') . 'HOWTO_clean_up_TYPO3_installations.txt');
			echo wordwrap($howto, 120) . LF;
			die;
		}
		// Print help
		$analysisType = (string) $this->cli_args['_DEFAULT'][1];
		if (!$analysisType) {
			$this->cli_validateArgs();
			$this->cli_help();
			die;
		}
		// Analysis type:
		switch ((string) $analysisType) {
		default:
			if (is_array($this->cleanerModules[$analysisType])) {
				$cleanerMode = \TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($this->cleanerModules[$analysisType][0]);
				$cleanerMode->cli_validateArgs();
				// Run it...
				if ($this->cli_isArg('-r')) {
					if (!$cleanerMode->checkRefIndex || $this->cli_referenceIndexCheck()) {
						$res = $cleanerMode->main();
						$this->cli_printInfo($analysisType, $res);
						// Autofix...
						if ($this->cli_isArg('--AUTOFIX')) {
							if ($this->cli_isArg('--YES') || $this->cli_keyboardInput_yes('

NOW Running --AUTOFIX on result. OK?' . ($this->cli_isArg('--dryrun') ? ' (--dryrun simulation)' : ''))) {
								$cleanerMode->main_autofix($res);
							} else {
								$this->cli_echo('ABORTING AutoFix...
', 1);
							}
						}
					}
				} else {
					// Help only...
					$cleanerMode->cli_help();
					die;
				}
			} else {
				$this->cli_echo('ERROR: Analysis Type \'' . $analysisType . '\' is unknown.
', 1);
				die;
			}
			break;
		}
	}

	/**
	 * Checks reference index
	 *
	 * @return boolean TRUE if reference index was OK (either OK, updated or ignored)
	 * @todo Define visibility
	 */
	public function cli_referenceIndexCheck() {
		// Reference index option:
		$refIndexMode = isset($this->cli_args['--refindex']) ? $this->cli_args['--refindex'][0] : 'check';
		if (!\TYPO3\CMS\Core\Utility\GeneralUtility::inList('update,ignore,check', $refIndexMode)) {
			$this->cli_echo('ERROR: Wrong value for --refindex argument.
', 1);
			die;
		}
		switch ($refIndexMode) {
		case 'check':

		case 'update':
			$refIndexObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Database\\ReferenceIndex');
			list($headerContent, $bodyContent, $errorCount) = $refIndexObj->updateIndex($refIndexMode == 'check', $this->cli_echo());
			if ($errorCount && $refIndexMode == 'check') {
				$ok = FALSE;
				$this->cli_echo('ERROR: Reference Index Check failed! (run with \'--refindex update\' to fix)
', 1);
			} else {
				$ok = TRUE;
			}
			break;
		case 'ignore':
			$this->cli_echo('Reference Index Check: Bypassing reference index check...
');
			$ok = TRUE;
			break;
		}
		return $ok;
	}

	/**
	 * @param string $matchString
	 * @return string If string, it's the reason for not executing. Returning FALSE means it should execute.
	 * @todo Define visibility
	 */
	public function cli_noExecutionCheck($matchString) {
		// Check for filter:
		if ($this->cli_isArg('--filterRegex') && ($regex = $this->cli_argValue('--filterRegex', 0))) {
			if (!preg_match($regex, $matchString)) {
				return 'BYPASS: Filter Regex "' . $regex . '" did not match string "' . $matchString . '"';
			}
		}
		// Check for interactive mode
		if ($this->cli_isArg('-i')) {
			if (!$this->cli_keyboardInput_yes(' EXECUTE?')) {
				return 'BYPASS...';
			}
		}
		// Check for
		if ($this->cli_isArg('--dryrun')) {
			return 'BYPASS: --dryrun set';
		}
	}

	/**
	 * Formats a result array from a test so it fits output in the shell
	 *
	 * @param string $header Name of the test (eg. function name)
	 * @param array $res Result array from an analyze function
	 * @return void Outputs with echo - capture content with output buffer if needed.
	 * @todo Define visibility
	 */
	public function cli_printInfo($header, $res) {
		$detailLevel = \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange($this->cli_isArg('-v') ? $this->cli_argValue('-v') : 1, 0, 3);
		$silent = !$this->cli_echo();
		$severity = array(
			0 => 'MESSAGE',
			1 => 'INFO',
			2 => 'WARNING',
			3 => 'ERROR'
		);
		// Header output:
		if ($detailLevel <= 1) {
			$this->cli_echo('*********************************************
' . $header . LF . '*********************************************
');
			$this->cli_echo(wordwrap(trim($res['message'])) . LF . LF);
		}
		// Traverse headers for output:
		if (is_array($res['headers'])) {
			foreach ($res['headers'] as $key => $value) {
				if ($detailLevel <= intval($value[2])) {
					if (is_array($res[$key]) && (count($res[$key]) || !$silent)) {
						// Header and explanaion:
						$this->cli_echo('---------------------------------------------' . LF, 1);
						$this->cli_echo('[' . $header . ']' . LF, 1);
						$this->cli_echo($value[0] . ' [' . $severity[$value[2]] . ']' . LF, 1);
						$this->cli_echo('---------------------------------------------' . LF, 1);
						if (trim($value[1])) {
							$this->cli_echo('Explanation: ' . wordwrap(trim($value[1])) . LF . LF, 1);
						}
					}
					// Content:
					if (is_array($res[$key])) {
						if (count($res[$key])) {
							if ($this->cli_echo('', 1)) {
								print_r($res[$key]);
							}
						} else {
							$this->cli_echo('(None)' . LF . LF);
						}
					} else {
						$this->cli_echo($res[$key] . LF . LF);
					}
				}
			}
		}
	}

	/**************************
	 *
	 * Page tree traversal
	 *
	 *************************/
	/**
	 * Traverses the FULL/part of page tree, mainly to register ALL validly connected records (to find orphans) but also to register deleted records, versions etc.
	 * Output (in $this->recStats) can be useful for multiple purposes.
	 *
	 * @param integer $rootID Root page id from where to start traversal. Use "0" (zero) to have full page tree (necessary when spotting orphans, otherwise you can run it on parts only)
	 * @param integer $depth Depth to traverse. zero is do not traverse at all. 1 = 1 sublevel, 1000= 1000 sublevels (all...)
	 * @param boolean $echoLevel If >0, will echo information about the traversal process.
	 * @param string $callBack Call back function (from this class or subclass)
	 * @return void
	 * @todo Define visibility
	 */
	public function genTree($rootID, $depth = 1000, $echoLevel = 0, $callBack = '') {
		$pt = \TYPO3\CMS\Core\Utility\GeneralUtility::milliseconds();
		$this->performanceStatistics['genTree()'] = '';
		// Initialize:
		if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('workspaces')) {
			$this->workspaceIndex = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('uid,title', 'sys_workspace', '1=1' . \TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause('sys_workspace'), '', '', '', 'uid');
		}
		$this->workspaceIndex[-1] = TRUE;
		$this->workspaceIndex[0] = TRUE;
		$this->recStats = array(
			'all' => array(),
			// All records connected in tree including versions (the reverse are orphans). All Info and Warning categories below are included here (and therefore safe if you delete the reverse of the list)
			'deleted' => array(),
			// Subset of "alL" that are deleted-flagged [Info]
			'versions' => array(),
			// Subset of "all" which are offline versions (pid=-1). [Info]
			'versions_published' => array(),
			// Subset of "versions" that is a count of 1 or more (has been published) [Info]
			'versions_liveWS' => array(),
			// Subset of "versions" that exists in live workspace [Info]
			'versions_lost_workspace' => array(),
			// Subset of "versions" that doesn't belong to an existing workspace [Warning: Fix by move to live workspace]
			'versions_inside_versioned_page' => array(),
			// Subset of "versions" This is versions of elements found inside an already versioned branch / page. In real life this can work out, but is confusing and the backend should prevent this from happening to people. [Warning: Fix by deleting those versions (or publishing them)]
			'illegal_record_under_versioned_page' => array(),
			// If a page is "element" or "page" version and records are found attached to it, they might be illegally attached, so this will tell you. [Error: Fix by deleting orphans since they are not registered in "all" category]
			'misplaced_at_rootlevel' => array(),
			// Subset of "all": Those that should not be at root level but are. [Warning: Fix by moving record into page tree]
			'misplaced_inside_tree' => array()
		);
		// Start traversal:
		$pt2 = \TYPO3\CMS\Core\Utility\GeneralUtility::milliseconds();
		$this->performanceStatistics['genTree_traverse()'] = '';
		$this->performanceStatistics['genTree_traverse():TraverseTables'] = '';
		$this->genTree_traverse($rootID, $depth, $echoLevel, $callBack);
		$this->performanceStatistics['genTree_traverse()'] = \TYPO3\CMS\Core\Utility\GeneralUtility::milliseconds() - $pt2;
		// Sort recStats (for diff'able displays)
		foreach ($this->recStats as $kk => $vv) {
			foreach ($this->recStats[$kk] as $tables => $recArrays) {
				ksort($this->recStats[$kk][$tables]);
			}
			ksort($this->recStats[$kk]);
		}
		if ($echoLevel > 0) {
			echo LF . LF;
		}
		// Processing performance statistics:
		$this->performanceStatistics['genTree()'] = \TYPO3\CMS\Core\Utility\GeneralUtility::milliseconds() - $pt;
		// Count records:
		foreach ($GLOBALS['TCA'] as $tableName => $cfg) {
			// Select all records belonging to page:
			$resSub = $GLOBALS['TYPO3_DB']->exec_SELECTquery('count(*)', $tableName, '');
			$countRow = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($resSub);
			$this->performanceStatistics['MySQL_count'][$tableName] = $countRow['count(*)'];
			$this->performanceStatistics['CSV'] .= LF . $tableName . ',' . $this->performanceStatistics['genTree_traverse():TraverseTables:']['MySQL'][$tableName] . ',' . $this->performanceStatistics['genTree_traverse():TraverseTables:']['Proc'][$tableName] . ',' . $this->performanceStatistics['MySQL_count'][$tableName];
		}
		$this->performanceStatistics['recStats_size']['(ALL)'] = strlen(serialize($this->recStats));
		foreach ($this->recStats as $key => $arrcontent) {
			$this->performanceStatistics['recStats_size'][$key] = strlen(serialize($arrcontent));
		}
	}

	/**
	 * Recursive traversal of page tree:
	 *
	 * @param integer $rootID Page root id (must be online, valid page record - or zero for page tree root)
	 * @param integer $depth Depth
	 * @param integer $echoLevel Echo Level
	 * @param string $callBack Call back function (from this class or subclass)
	 * @param string $versionSwapmode DON'T set from outside, internal. (indicates we are inside a version of a page) - will be "SWAPMODE:-1" or empty
	 * @param integer $rootIsVersion DON'T set from outside, internal. (1: Indicates that rootID is a version of a page, 2: ...that it is even a version of a version (which triggers a warning!)
	 * @param string $accumulatedPath Internal string that accumulates the path
	 * @return void
	 * @access private
	 * @todo $versionSwapmode needs to be cleaned up, since page and branch version (0, 1) does not exist anymore
	 * @todo Define visibility
	 */
	public function genTree_traverse($rootID, $depth, $echoLevel = 0, $callBack = '', $versionSwapmode = '', $rootIsVersion = 0, $accumulatedPath = '') {
		// Register page:
		$this->recStats['all']['pages'][$rootID] = $rootID;
		$pageRecord = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecordRaw('pages', 'uid=' . intval($rootID), 'deleted,title,t3ver_count,t3ver_wsid');
		$accumulatedPath .= '/' . $pageRecord['title'];
		// Register if page is deleted:
		if ($pageRecord['deleted']) {
			$this->recStats['deleted']['pages'][$rootID] = $rootID;
		}
		// If rootIsVersion is set it means that the input rootID is that of a version of a page. See below where the recursive call is made.
		if ($rootIsVersion) {
			$this->recStats['versions']['pages'][$rootID] = $rootID;
			// If it has been published and is in archive now...
			if ($pageRecord['t3ver_count'] >= 1 && $pageRecord['t3ver_wsid'] == 0) {
				$this->recStats['versions_published']['pages'][$rootID] = $rootID;
			}
			// If it has been published and is in archive now...
			if ($pageRecord['t3ver_wsid'] == 0) {
				$this->recStats['versions_liveWS']['pages'][$rootID] = $rootID;
			}
			// If it doesn't belong to a workspace...
			if (!isset($this->workspaceIndex[$pageRecord['t3ver_wsid']])) {
				$this->recStats['versions_lost_workspace']['pages'][$rootID] = $rootID;
			}
			// In case the rootID is a version inside a versioned page
			if ($rootIsVersion == 2) {
				$this->recStats['versions_inside_versioned_page']['pages'][$rootID] = $rootID;
			}
		}
		if ($echoLevel > 0) {
			echo LF . $accumulatedPath . ' [' . $rootID . ']' . ($pageRecord['deleted'] ? ' (DELETED)' : '') . ($this->recStats['versions_published']['pages'][$rootID] ? ' (PUBLISHED)' : '');
		}
		if ($echoLevel > 1 && $this->recStats['versions_lost_workspace']['pages'][$rootID]) {
			echo LF . '	ERROR! This version belongs to non-existing workspace (' . $pageRecord['t3ver_wsid'] . ')!';
		}
		if ($echoLevel > 1 && $this->recStats['versions_inside_versioned_page']['pages'][$rootID]) {
			echo LF . '	WARNING! This version is inside an already versioned page or branch!';
		}
		// Call back:
		if ($callBack) {
			$this->{$callBack}('pages', $rootID, $echoLevel, $versionSwapmode, $rootIsVersion);
		}
		$pt3 = \TYPO3\CMS\Core\Utility\GeneralUtility::milliseconds();
		// Traverse tables of records that belongs to page:
		foreach ($GLOBALS['TCA'] as $tableName => $cfg) {
			if ($tableName != 'pages') {
				// Select all records belonging to page:
				$pt4 = \TYPO3\CMS\Core\Utility\GeneralUtility::milliseconds();
				$resSub = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid' . ($GLOBALS['TCA'][$tableName]['ctrl']['delete'] ? ',' . $GLOBALS['TCA'][$tableName]['ctrl']['delete'] : ''), $tableName, 'pid=' . intval($rootID) . ($this->genTree_traverseDeleted ? '' : \TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause($tableName)));
				$this->performanceStatistics['genTree_traverse():TraverseTables:']['MySQL']['(ALL)'] += \TYPO3\CMS\Core\Utility\GeneralUtility::milliseconds() - $pt4;
				$this->performanceStatistics['genTree_traverse():TraverseTables:']['MySQL'][$tableName] += \TYPO3\CMS\Core\Utility\GeneralUtility::milliseconds() - $pt4;
				$pt5 = \TYPO3\CMS\Core\Utility\GeneralUtility::milliseconds();
				$count = $GLOBALS['TYPO3_DB']->sql_num_rows($resSub);
				if ($count) {
					if ($echoLevel == 2) {
						echo LF . '	\\-' . $tableName . ' (' . $count . ')';
					}
				}
				while ($rowSub = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($resSub)) {
					if ($echoLevel == 3) {
						echo LF . '	\\-' . $tableName . ':' . $rowSub['uid'];
					}
					// If the rootID represents an "element" or "page" version type, we must check if the record from this table is allowed to belong to this:
					if ($versionSwapmode == 'SWAPMODE:-1' || $versionSwapmode == 'SWAPMODE:0' && !$GLOBALS['TCA'][$tableName]['ctrl']['versioning_followPages']) {
						// This is illegal records under a versioned page - therefore not registered in $this->recStats['all'] so they should be orphaned:
						$this->recStats['illegal_record_under_versioned_page'][$tableName][$rowSub['uid']] = $rowSub['uid'];
						if ($echoLevel > 1) {
							echo LF . '		ERROR! Illegal record (' . $tableName . ':' . $rowSub['uid'] . ') under versioned page!';
						}
					} else {
						$this->recStats['all'][$tableName][$rowSub['uid']] = $rowSub['uid'];
						// Register deleted:
						if ($GLOBALS['TCA'][$tableName]['ctrl']['delete'] && $rowSub[$GLOBALS['TCA'][$tableName]['ctrl']['delete']]) {
							$this->recStats['deleted'][$tableName][$rowSub['uid']] = $rowSub['uid'];
							if ($echoLevel == 3) {
								echo ' (DELETED)';
							}
						}
						// Check location of records regarding tree root:
						if (!$GLOBALS['TCA'][$tableName]['ctrl']['rootLevel'] && $rootID == 0) {
							$this->recStats['misplaced_at_rootlevel'][$tableName][$rowSub['uid']] = $rowSub['uid'];
							if ($echoLevel > 1) {
								echo LF . '		ERROR! Misplaced record (' . $tableName . ':' . $rowSub['uid'] . ') on rootlevel!';
							}
						}
						if ($GLOBALS['TCA'][$tableName]['ctrl']['rootLevel'] == 1 && $rootID > 0) {
							$this->recStats['misplaced_inside_tree'][$tableName][$rowSub['uid']] = $rowSub['uid'];
							if ($echoLevel > 1) {
								echo LF . '		ERROR! Misplaced record (' . $tableName . ':' . $rowSub['uid'] . ') inside page tree!';
							}
						}
						// Traverse plugins:
						if ($callBack) {
							$this->{$callBack}($tableName, $rowSub['uid'], $echoLevel, $versionSwapmode, $rootIsVersion);
						}
						// Add any versions of those records:
						if ($this->genTree_traverseVersions) {
							$versions = \TYPO3\CMS\Backend\Utility\BackendUtility::selectVersionsOfRecord($tableName, $rowSub['uid'], 'uid,t3ver_wsid,t3ver_count' . ($GLOBALS['TCA'][$tableName]['ctrl']['delete'] ? ',' . $GLOBALS['TCA'][$tableName]['ctrl']['delete'] : ''), 0, TRUE);
							if (is_array($versions)) {
								foreach ($versions as $verRec) {
									if (!$verRec['_CURRENT_VERSION']) {
										if ($echoLevel == 3) {
											echo LF . '		\\-[#OFFLINE VERSION: WS#' . $verRec['t3ver_wsid'] . '/Cnt:' . $verRec['t3ver_count'] . '] ' . $tableName . ':' . $verRec['uid'] . ')';
										}
										$this->recStats['all'][$tableName][$verRec['uid']] = $verRec['uid'];
										// Register deleted:
										if ($GLOBALS['TCA'][$tableName]['ctrl']['delete'] && $verRec[$GLOBALS['TCA'][$tableName]['ctrl']['delete']]) {
											$this->recStats['deleted'][$tableName][$verRec['uid']] = $verRec['uid'];
											if ($echoLevel == 3) {
												echo ' (DELETED)';
											}
										}
										// Register version:
										$this->recStats['versions'][$tableName][$verRec['uid']] = $verRec['uid'];
										if ($verRec['t3ver_count'] >= 1 && $verRec['t3ver_wsid'] == 0) {
											// Only register published versions in LIVE workspace (published versions in draft workspaces are allowed)
											$this->recStats['versions_published'][$tableName][$verRec['uid']] = $verRec['uid'];
											if ($echoLevel == 3) {
												echo ' (PUBLISHED)';
											}
										}
										if ($verRec['t3ver_wsid'] == 0) {
											$this->recStats['versions_liveWS'][$tableName][$verRec['uid']] = $verRec['uid'];
										}
										if (!isset($this->workspaceIndex[$verRec['t3ver_wsid']])) {
											$this->recStats['versions_lost_workspace'][$tableName][$verRec['uid']] = $verRec['uid'];
											if ($echoLevel > 1) {
												echo LF . '		ERROR! Version (' . $tableName . ':' . $verRec['uid'] . ') belongs to non-existing workspace (' . $verRec['t3ver_wsid'] . ')!';
											}
										}
										// In case we are inside a versioned branch, there should not exists versions inside that "branch".
										if ($versionSwapmode) {
											$this->recStats['versions_inside_versioned_page'][$tableName][$verRec['uid']] = $verRec['uid'];
											if ($echoLevel > 1) {
												echo LF . '		ERROR! This version (' . $tableName . ':' . $verRec['uid'] . ') is inside an already versioned page or branch!';
											}
										}
										// Traverse plugins:
										if ($callBack) {
											$this->{$callBack}($tableName, $verRec['uid'], $echoLevel, $versionSwapmode, $rootIsVersion);
										}
									}
								}
							}
							unset($versions);
						}
					}
				}
				$this->performanceStatistics['genTree_traverse():TraverseTables:']['Proc']['(ALL)'] += \TYPO3\CMS\Core\Utility\GeneralUtility::milliseconds() - $pt5;
				$this->performanceStatistics['genTree_traverse():TraverseTables:']['Proc'][$tableName] += \TYPO3\CMS\Core\Utility\GeneralUtility::milliseconds() - $pt5;
			}
		}
		unset($resSub);
		unset($rowSub);
		$this->performanceStatistics['genTree_traverse():TraverseTables'] += \TYPO3\CMS\Core\Utility\GeneralUtility::milliseconds() - $pt3;
		// Find subpages to root ID and traverse (only when rootID is not a version or is a branch-version):
		if (!$versionSwapmode || $versionSwapmode == 'SWAPMODE:1') {
			if ($depth > 0) {
				$depth--;
				$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid', 'pages', 'pid=' . intval($rootID) . ($this->genTree_traverseDeleted ? '' : \TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause('pages')), '', 'sorting');
				while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
					$this->genTree_traverse($row['uid'], $depth, $echoLevel, $callBack, $versionSwapmode, 0, $accumulatedPath);
				}
			}
			// Add any versions of pages
			if ($rootID > 0 && $this->genTree_traverseVersions) {
				$versions = \TYPO3\CMS\Backend\Utility\BackendUtility::selectVersionsOfRecord('pages', $rootID, 'uid,t3ver_oid,t3ver_wsid,t3ver_count', 0, TRUE);
				if (is_array($versions)) {
					foreach ($versions as $verRec) {
						if (!$verRec['_CURRENT_VERSION']) {
							$this->genTree_traverse($verRec['uid'], $depth, $echoLevel, $callBack, 'SWAPMODE:-1', $versionSwapmode ? 2 : 1, $accumulatedPath . ' [#OFFLINE VERSION: WS#' . $verRec['t3ver_wsid'] . '/Cnt:' . $verRec['t3ver_count'] . ']');
						}
					}
				}
			}
		}
	}

	/**************************
	 *
	 * Helper functions
	 *
	 *************************/
	/**
	 * Compile info-string
	 *
	 * @param array $rec Input record from sys_refindex
	 * @return string String identifying the main record of the reference
	 * @todo Define visibility
	 */
	public function infoStr($rec) {
		return $rec['tablename'] . ':' . $rec['recuid'] . ':' . $rec['field'] . ':' . $rec['flexpointer'] . ':' . $rec['softref_key'] . ($rec['deleted'] ? ' (DELETED)' : '');
	}

}


?>
