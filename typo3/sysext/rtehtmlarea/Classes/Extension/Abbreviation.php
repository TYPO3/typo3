<?php
namespace TYPO3\CMS\Rtehtmlarea\Extension;

/**
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

/**
 * Abbreviation extension for htmlArea RTE
 *
 * @author Stanislas Rolland <typo3(arobas)sjbr.ca>
 */
class Abbreviation extends \TYPO3\CMS\Rtehtmlarea\RteHtmlAreaApi {

	/**
	 * The key of the extension that is extending htmlArea RTE
	 *
	 * @var string
	 */
	protected $extensionKey = 'rtehtmlarea';

	/**
	 * The name of the plugin registered by the extension
	 *
	 * @var string
	 */
	protected $pluginName = 'Abbreviation';

	/**
	 * Path to this main locallang file of the extension relative to the extension directory
	 *
	 * @var string
	 */
	protected $relativePathToLocallangFile = '';

	/**
	 * Path to the skin file relative to the extension directory
	 *
	 * @var string
	 */
	protected $relativePathToSkin = 'Resources/Public/Css/Skin/Plugins/abbreviation.css';

	/**
	 * Reference to the invoking object
	 *
	 * @var \TYPO3\CMS\Rtehtmlarea\RteHtmlAreaBase
	 */
	protected $htmlAreaRTE;

	protected $thisConfig;

	// Reference to RTE PageTSConfig
	protected $toolbar;

	// Reference to RTE toolbar array
	protected $LOCAL_LANG;

	// Frontend language array

	/**
	 * Comma-separated list of button names that the registered plugin is adding to the htmlArea RTE toolbar
	 *
	 * @var string
	 */
	protected $pluginButtons = 'abbreviation';

	/**
	 * Name-converting array, converting the button names used in the RTE PageTSConfing to the button id's used by the JS scripts
	 *
	 * @var array
	 */
	protected $convertToolbarForHtmlAreaArray = array(
		'abbreviation' => 'Abbreviation'
	);

	protected $acronymIndex = 0;

	protected $abbreviationIndex = 0;

	/**
	 * Returns TRUE if the plugin is available and correctly initialized
	 *
	 * @param \TYPO3\CMS\Rtehtmlarea\RteHtmlAreaBase Reference to parent object
	 * @return bool TRUE if this plugin should be made available in the current environment and is correctly initialized
	 */
	public function main(\TYPO3\CMS\Rtehtmlarea\RteHtmlAreaBase $parentObject) {
		// acronym button is deprecated as of TYPO3 CMS 7.0, use abbreviation instead
		// Convert the acronym button configuration
		if (isset($this->thisConfig['buttons.']['acronym.']) && is_array($this->thisConfig['buttons.']['acronym.'])) {
			if (!isset($this->thisConfig['buttons.']['abbreviation.']) || !is_array($this->thisConfig['buttons.']['abbreviation.'])) {
				$this->thisConfig['buttons.']['abbreviation.'] = $this->thisConfig['buttons.']['acronym.'];
			}
			unset($this->thisConfig['buttons.']['acronym.']);
		}
		// Convert any other reference to acronym two levels down in Page TSconfig, except in processing options
		foreach ($parentObject->thisConfig as $key => $config) {
			if ($key !== 'proc.') {
				if (is_array($config)) {
					foreach ($config as $subKey => $subConfig) {
						$parentObject->thisConfig[$key][$subKey] = str_replace('acronym', 'abbreviation', $subConfig);
					}
				} else {
					$parentObject->thisConfig[$key] = str_replace('acronym', 'abbreviation', $config);
				}
			}
		}
		// Convert any reference to acronym in special configuration options
		if (is_array($parentObject->specConf['richtext']['parameters'])) {
			foreach ($parentObject->specConf['richtext']['parameters'] as $key => $config) {
				$parentObject->specConf['richtext']['parameters'][$key] = str_replace('acronym', 'abbreviation', $config);
			}
		}
		// Convert any reference to acronym in user TSconfig
		if (is_object($GLOBALS['BE_USER']) && isset($GLOBALS['BE_USER']->userTS['options.']['RTEkeyList'])) {
			$GLOBALS['BE_USER']->userTS['options.']['RTEkeyList'] = str_replace('acronym', 'abbreviation', $GLOBALS['BE_USER']->userTS['options.']['RTEkeyList']);
		}
		return parent::main($parentObject) && \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('static_info_tables');
	}

	/**
	 * Return tranformed content
	 *
	 * @param string $content: The content that is about to be sent to the RTE
	 * @return string the transformed content
	 */
	public function transformContent($content) {
		// <abbr> was not supported by IE before version 7
		if ($this->htmlAreaRTE->client['browser'] == 'msie' && $this->htmlAreaRTE->client['version'] < 7) {
			// change <abbr> to <acronym>
			$content = preg_replace('/<(\\/?)abbr/i', '<$1acronym', $content);
		}
		return $content;
	}

	/**
	 * Return JS configuration of the htmlArea plugins registered by the extension
	 *
	 * @param int Relative id of the RTE editing area in the form
	 * @return string JS configuration for registered plugins, in this case, JS configuration of block elements
	 */
	public function buildJavascriptConfiguration($RTEcounter) {
		$registerRTEinJavascriptString = '';
		$button = 'abbreviation';
		if (in_array($button, $this->toolbar)) {
			if (!is_array($this->thisConfig['buttons.']) || !is_array($this->thisConfig['buttons.'][($button . '.')])) {
				$registerRTEinJavascriptString .= '
			RTEarea[' . $RTEcounter . '].buttons.' . $button . ' = new Object();';
			}
			$registerRTEinJavascriptString .= '
			RTEarea[' . $RTEcounter . '].buttons.' . $button . '.abbreviationUrl = "' . $this->htmlAreaRTE->writeTemporaryFile('', ('abbreviation_' . $this->htmlAreaRTE->contentLanguageUid), 'js', $this->buildJSAbbreviationArray($this->htmlAreaRTE->contentLanguageUid)) . '";';
			// <abbr> was not supported by IE before version 7
			if ($this->htmlAreaRTE->client['browser'] == 'msie' && $this->htmlAreaRTE->client['version'] < 7) {
				$this->abbreviationIndex = 0;
			}
			$registerRTEinJavascriptString .= '
			RTEarea[' . $RTEcounter . '].buttons.' . $button . '.noAcronym = ' . ($this->acronymIndex ? 'false' : 'true') . ';
			RTEarea[' . $RTEcounter . '].buttons.' . $button . '.noAbbr =  ' . ($this->abbreviationIndex ? 'false' : 'true') . ';';
		}
		return $registerRTEinJavascriptString;
	}

	/**
	 * Return an abbreviation array for the Abbreviation plugin
	 *
	 * @return string abbreviation Javascript array
	 */
	protected function buildJSAbbreviationArray($languageUid) {
		$button = 'abbreviation';
		$acronymArray = array();
		$abbrArray = array();
		$tableA = 'tx_rtehtmlarea_acronym';
		$tableB = 'static_languages';
		$fields = $tableA . '.type,' . $tableA . '.term,' . $tableA . '.acronym,' . $tableB . '.lg_iso_2,' . $tableB . '.lg_country_iso_2';
		$tableAB = $tableA . ' LEFT JOIN ' . $tableB . ' ON ' . $tableA . '.static_lang_isocode=' . $tableB . '.uid';
		$whereClause = '1=1';
		// Get all abbreviations on pages to which the user has access
		$lockBeUserToDBmounts = isset($this->thisConfig['buttons.'][$button . '.']['lockBeUserToDBmounts']) ? $this->thisConfig['buttons.'][$button . '.']['lockBeUserToDBmounts'] : $GLOBALS['TYPO3_CONF_VARS']['BE']['lockBeUserToDBmounts'];
		if (!$GLOBALS['BE_USER']->isAdmin() && $GLOBALS['TYPO3_CONF_VARS']['BE']['lockBeUserToDBmounts'] && $lockBeUserToDBmounts) {
			// Temporarily setting alternative web browsing mounts
			$altMountPoints = trim($GLOBALS['BE_USER']->getTSConfigVal('options.pageTree.altElementBrowserMountPoints'));
			if ($altMountPoints) {
				$savedGroupDataWebmounts = $GLOBALS['BE_USER']->groupData['webmounts'];
				$GLOBALS['BE_USER']->groupData['webmounts'] = implode(',', array_unique(\TYPO3\CMS\Core\Utility\GeneralUtility::intExplode(',', $altMountPoints)));
				$GLOBALS['WEBMOUNTS'] = $GLOBALS['BE_USER']->returnWebmounts();
			}
			$webMounts = $GLOBALS['BE_USER']->returnWebmounts();
			$perms_clause = $GLOBALS['BE_USER']->getPagePermsClause(1);
			$recursive = isset($this->thisConfig['buttons.'][$button . '.']['recursive']) ? (int)$this->thisConfig['buttons.'][$button . '.']['recursive'] : 0;
			if (trim($this->thisConfig['buttons.'][$button . '.']['pages'])) {
				$pids = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $this->thisConfig['buttons.'][$button . '.']['pages'], TRUE);
				foreach ($pids as $key => $val) {
					if (!$GLOBALS['BE_USER']->isInWebMount($val, $perms_clause)) {
						unset($pids[$key]);
					}
				}
			} else {
				$pids = $webMounts;
			}
			// Restoring webmounts
			if ($altMountPoints) {
				$GLOBALS['BE_USER']->groupData['webmounts'] = $savedGroupDataWebmounts;
				$GLOBALS['WEBMOUNTS'] = $GLOBALS['BE_USER']->returnWebmounts();
			}
			$queryGenerator = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Database\\QueryGenerator');
			foreach ($pids as $key => $val) {
				if ($pageTree) {
					$pageTreePrefix = ',';
				}
				$pageTree .= $pageTreePrefix . $queryGenerator->getTreeList($val, $recursive, ($begin = 0), $perms_clause);
			}
			$whereClause .= ' AND ' . $tableA . '.pid IN (' . $GLOBALS['TYPO3_DB']->fullQuoteStr(($pageTree ?: ''), $tableA) . ')';
		}
		// Restrict to abbreviations applicable to the language of current content element
		if ($this->htmlAreaRTE->contentLanguageUid > -1) {
			$whereClause .= ' AND (' . $tableA . '.sys_language_uid=' . $this->htmlAreaRTE->contentLanguageUid . ' OR ' . $tableA . '.sys_language_uid=-1) ';
		}
		// Restrict to abbreviations in certain languages
		if (is_array($this->thisConfig['buttons.']) && is_array($this->thisConfig['buttons.']['language.']) && isset($this->thisConfig['buttons.']['language.']['restrictToItems'])) {
			$languageList = implode('\',\'', \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $GLOBALS['TYPO3_DB']->fullQuoteStr(strtoupper($this->thisConfig['buttons.']['language.']['restrictToItems']), $tableB)));
			$whereClause .= ' AND ' . $tableB . '.lg_iso_2 IN (' . $languageList . ') ';
		}
		$whereClause .= BackendUtility::BEenableFields($tableA);
		$whereClause .= BackendUtility::deleteClause($tableA);
		$whereClause .= BackendUtility::BEenableFields($tableB);
		$whereClause .= BackendUtility::deleteClause($tableB);
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($fields, $tableAB, $whereClause);
		while ($abbreviationRow = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			$item = array('term' => $abbreviationRow['term'], 'abbr' => $abbreviationRow['acronym'], 'language' => strtolower($abbreviationRow['lg_iso_2']) . ($abbreviationRow['lg_country_iso_2'] ? '-' . $abbreviationRow['lg_country_iso_2'] : ''));
			if ($abbreviationRow['type'] == 1) {
				$acronymArray[] = $item;
			} elseif ($abbreviationRow['type'] == 2) {
				$abbrArray[] = $item;
			}
		}
		$this->acronymIndex = count($acronymArray);
		$this->abbreviationIndex = count($abbrArray);
		return json_encode(array('abbr' => $abbrArray, 'acronym' => $acronymArray));
	}

}
