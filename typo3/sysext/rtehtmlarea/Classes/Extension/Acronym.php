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
 * Acronym extension for htmlArea RTE
 *
 * @author Stanislas Rolland <typo3(arobas)sjbr.ca>
 */
class Acronym extends \TYPO3\CMS\Rtehtmlarea\RteHtmlAreaApi {

	protected $extensionKey = 'rtehtmlarea';

	// The key of the extension that is extending htmlArea RTE
	protected $pluginName = 'Acronym';

	// The name of the plugin registered by the extension
	protected $relativePathToLocallangFile = '';

	// Path to this main locallang file of the extension relative to the extension dir.
	protected $relativePathToSkin = 'extensions/Acronym/skin/htmlarea.css';

	// Path to the skin (css) file relative to the extension dir
	protected $htmlAreaRTE;

	// Reference to the invoking object
	protected $thisConfig;

	// Reference to RTE PageTSConfig
	protected $toolbar;

	// Reference to RTE toolbar array
	protected $LOCAL_LANG;

	// Frontend language array
	protected $pluginButtons = 'acronym';

	protected $convertToolbarForHtmlAreaArray = array(
		'acronym' => 'Acronym'
	);

	protected $acronymIndex = 0;

	protected $abbreviationIndex = 0;

	public function main($parentObject) {
		return parent::main($parentObject) && \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('static_info_tables');
	}

	/**
	 * Return tranformed content
	 *
	 * @param 	string		$content: The content that is about to be sent to the RTE
	 * @return 	string		the transformed content
	 */
	public function transformContent($content) {
		// <abbr> was not supported by IE before verison 7
		if ($this->htmlAreaRTE->client['browser'] == 'msie' && $this->htmlAreaRTE->client['version'] < 7) {
			// change <abbr> to <acronym>
			$content = preg_replace('/<(\\/?)abbr/i', '<$1acronym', $content);
		}
		return $content;
	}

	/**
	 * Return JS configuration of the htmlArea plugins registered by the extension
	 *
	 * @param 	integer		Relative id of the RTE editing area in the form
	 * @return 	string		JS configuration for registered plugins, in this case, JS configuration of block elements
	 */
	public function buildJavascriptConfiguration($RTEcounter) {
		$registerRTEinJavascriptString = '';
		$button = 'acronym';
		if (in_array($button, $this->toolbar)) {
			if (!is_array($this->thisConfig['buttons.']) || !is_array($this->thisConfig['buttons.'][($button . '.')])) {
				$registerRTEinJavascriptString .= '
			RTEarea[' . $RTEcounter . ']["buttons"]["' . $button . '"] = new Object();';
			}
			$registerRTEinJavascriptString .= '
			RTEarea[' . $RTEcounter . '].buttons.' . $button . '.acronymUrl = "' . $this->htmlAreaRTE->writeTemporaryFile('', ('acronym_' . $this->htmlAreaRTE->contentLanguageUid), 'js', $this->buildJSAcronymArray($this->htmlAreaRTE->contentLanguageUid)) . '";';
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
	 * Return an acronym array for the Acronym plugin
	 *
	 * @return 	string		acronym Javascript array
	 * @todo Define visibility
	 */
	public function buildJSAcronymArray($languageUid) {
		$button = 'acronym';
		$acronymArray = array();
		$abbrArray = array();
		$tableA = 'tx_rtehtmlarea_acronym';
		$tableB = 'static_languages';
		$fields = $tableA . '.type,' . $tableA . '.term,' . $tableA . '.acronym,' . $tableB . '.lg_iso_2,' . $tableB . '.lg_country_iso_2';
		$tableAB = $tableA . ' LEFT JOIN ' . $tableB . ' ON ' . $tableA . '.static_lang_isocode=' . $tableB . '.uid';
		$whereClause = '1=1';
		$loadRecordsFromDatabase = TRUE;
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
			$pageTree = '';
			$pageTreePrefix = '';
			foreach ($pids as $key => $val) {
				if ($pageTree) {
					$pageTreePrefix = ',';
				}
				$pageTree .= $pageTreePrefix . $queryGenerator->getTreeList($val, $recursive, ($begin = 0), $perms_clause);
			}

			if ($pageTree !== '') {
				$whereClause .= ' AND ' . $tableA . '.pid IN (' . $pageTree . ')';
			} else {
				// If page tree is empty the user does not have access to any pages / acronyms.
				// This is why we do not try do read any records from the database.
				$loadRecordsFromDatabase = FALSE;
			}
		}

		if ($loadRecordsFromDatabase) {
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
		}

		$this->acronymIndex = count($acronymArray);
		$this->abbreviationIndex = count($abbrArray);
		return json_encode(array('abbr' => $abbrArray, 'acronym' => $acronymArray));
	}

}
