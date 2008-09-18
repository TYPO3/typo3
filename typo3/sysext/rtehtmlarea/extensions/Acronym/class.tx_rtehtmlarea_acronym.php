<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2008 Stanislas Rolland <typo3(arobas)sjbr.ca>
*  All rights reserved
*
*  This script is part of the Typo3 project. The Typo3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/**
 * Acronym extension for htmlArea RTE
 *
 * @author Stanislas Rolland <typo3(arobas)sjbr.ca>
 *
 * TYPO3 SVN ID: $Id$
 *
 */

require_once(t3lib_extMgm::extPath('rtehtmlarea').'class.tx_rtehtmlareaapi.php');

class tx_rtehtmlarea_acronym extends tx_rtehtmlareaapi {

	protected $extensionKey = 'rtehtmlarea';		// The key of the extension that is extending htmlArea RTE
	protected $pluginName = 'Acronym';			// The name of the plugin registered by the extension
	protected $relativePathToLocallangFile = '';		// Path to this main locallang file of the extension relative to the extension dir.
	protected $relativePathToSkin = 'extensions/Acronym/skin/htmlarea.css';		// Path to the skin (css) file relative to the extension dir
	protected $htmlAreaRTE;					// Reference to the invoking object
	protected $thisConfig;					// Reference to RTE PageTSConfig
	protected $toolbar;					// Reference to RTE toolbar array
	protected $LOCAL_LANG; 					// Frontend language array

	protected $pluginButtons = 'acronym';
	protected $convertToolbarForHtmlAreaArray = array (
		'acronym'	=> 'Acronym',
		);
	protected $acronymIndex = 0;
	protected $abbraviationIndex = 0;

	/**
	 * Return tranformed content
	 *
	 * @param	string		$content: The content that is about to be sent to the RTE
	 *
	 * @return 	string		the transformed content
	 */
	public function transformContent($content) {

			// <abbr> was not supported by IE before verison 7
		if ($this->htmlAreaRTE->client['BROWSER'] == 'msie' && $this->htmlAreaRTE->client['VERSION'] < 7) {
				// change <abbr> to <acronym>
			$content = preg_replace('/<(\/?)abbr/i', "<$1acronym", $content);
		}

		return $content;
	}

	/**
	 * Return JS configuration of the htmlArea plugins registered by the extension
	 *
	 * @param	integer		Relative id of the RTE editing area in the form
	 *
	 * @return 	string		JS configuration for registered plugins, in this case, JS configuration of block elements
	 *
	 * The returned string will be a set of JS instructions defining the configuration that will be provided to the plugin(s)
	 * Each of the instructions should be of the form:
	 * 	RTEarea['.$RTEcounter.']["buttons"]["button-id"]["property"] = "value";
	 */
	public function buildJavascriptConfiguration($RTEcounter) {

		$registerRTEinJavascriptString = '';
		$button = 'acronym';
		if (in_array($button, $this->toolbar)) {
			if (!is_array($this->thisConfig['buttons.']) || !is_array($this->thisConfig['buttons.'][$button.'.'])) {
					$registerRTEinJavascriptString .= '
			RTEarea['.$RTEcounter.']["buttons"]["'. $button .'"] = new Object();';
			}
			$registerRTEinJavascriptString .= '
			RTEarea['.$RTEcounter.'].buttons.'. $button .'.pathAcronymModule = "../../mod2/acronym.php";
			RTEarea['.$RTEcounter.'].buttons.'. $button .'.acronymUrl = "' . $this->htmlAreaRTE->writeTemporaryFile('', 'acronym_'.$this->htmlAreaRTE->contentLanguageUid, 'js', $this->buildJSAcronymArray($this->htmlAreaRTE->contentLanguageUid)) . '";';

				// <abbr> was not supported by IE before version 7
			if ($this->htmlAreaRTE->client['BROWSER'] == 'msie' && $this->htmlAreaRTE->client['VERSION'] < 7) {
				$this->AbbreviationIndex = 0;
			}
			$registerRTEinJavascriptString .= '
			RTEarea['.$RTEcounter.'].buttons.'. $button .'.noAcronym = ' . ($this->acronymIndex ? 'false' : 'true') . ';
			RTEarea['.$RTEcounter.'].buttons.'. $button .'.noAbbr =  ' . ($this->AbbreviationIndex ? 'false' : 'true') . ';';
		}

		return $registerRTEinJavascriptString;
	}

	/**
	 * Return an acronym array for the Acronym plugin
	 *
	 * @return	string		acronym Javascript array
	 */
	function buildJSAcronymArray($languageUid) {
		global $TYPO3_CONF_VARS, $TYPO3_DB;

		$button = 'acronym';
		$PIDList = 0;
		if (is_array($this->thisConfig['buttons.']) && is_array($this->thisConfig['buttons.'][$button.'.']) && trim($this->thisConfig['buttons.'][$button.'.']['PIDList'])) {
			$PIDList = implode(',', t3lib_div::trimExplode(',', $this->thisConfig['buttons.'][$button.'.']['PIDList']));
		}
		$linebreak = $TYPO3_CONF_VARS['EXTCONF'][$this->htmlAreaRTE->ID]['enableCompressedScripts'] ? '' : chr(10);
		$JSAcronymArray .= 'acronyms = { ' . $linebreak;
		$JSAbbreviationArray .= 'abbreviations = { ' . $linebreak;
		$table = 'tx_rtehtmlarea_acronym';
		if ($languageUid > -1) {
			$whereClause = '(sys_language_uid=' . $languageUid . ' OR sys_language_uid=-1) ';
		} else {
			$whereClause = '1 = 1 ';
		}
		$whereClause .= ($PIDList ? ' AND '. $table . '.pid IN (' . $TYPO3_DB->fullQuoteStr($PIDList, $table) . ') ' : '');
		$whereClause .= t3lib_BEfunc::BEenableFields($table);
		$whereClause .= t3lib_BEfunc::deleteClause($table);
		$res = $TYPO3_DB->exec_SELECTquery('type,term,acronym', $table, $whereClause);
		while($acronymRow = $TYPO3_DB->sql_fetch_assoc($res))    {
			if( $acronymRow['type'] == 1) $JSAcronymArray .= (($this->acronymIndex++)?',':'') . '"' . $acronymRow['term'] . '":"' . $acronymRow['acronym'] . '"' . $linebreak;
			if ($acronymRow['type'] == 2) $JSAbbreviationArray .= (($this->AbbreviationIndex++)?',':'') . '"' . $acronymRow['term'] . '":"' . $acronymRow['acronym'] . '"' . $linebreak;
		}
		$JSAcronymArray .= '};' . $linebreak;
		$JSAbbreviationArray .= '};' . $linebreak;

		return $JSAcronymArray . $JSAbbreviationArray;
	}

} // end of class

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/rtehtmlarea/extensions/Acronym/class.tx_rtehtmlarea_acronym.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/rtehtmlarea/extensions/Acronym/class.tx_rtehtmlarea_acronym.php']);
}

?>
