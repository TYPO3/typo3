<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2007-2208 Stanislas Rolland <typo3(arobas)sjbr.ca>
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
 * BlockElements extension for htmlArea RTE
 *
 * @author Stanislas Rolland <typo3(arobas)sjbr.ca>
 *
 * TYPO3 SVN ID: $Id$
 *
 */

require_once(t3lib_extMgm::extPath('rtehtmlarea').'class.tx_rtehtmlareaapi.php');

class tx_rtehtmlarea_blockelements extends tx_rtehtmlareaapi {

	protected $extensionKey = 'rtehtmlarea';		// The key of the extension that is extending htmlArea RTE
	protected $pluginName = 'BlockElements';		// The name of the plugin registered by the extension
	protected $relativePathToLocallangFile = 'extensions/BlockElements/locallang.xml';	// Path to this main locallang file of the extension relative to the extension dir.
	protected $relativePathToSkin = 'extensions/BlockElements/skin/htmlarea.css';		// Path to the skin (css) file relative to the extension dir
	protected $htmlAreaRTE;					// Reference to the invoking object
	protected $thisConfig;					// Reference to RTE PageTSConfig
	protected $toolbar;					// Reference to RTE toolbar array
	protected $LOCAL_LANG; 					// Frontend language array

	protected $pluginButtons = 'formatblock, indent, outdent, blockquote, insertparagraphbefore, insertparagraphafter, left, center, right, justifyfull, orderedlist, unorderedlist';
	protected $convertToolbarForHtmlAreaArray = array (
		'formatblock'		=> 'FormatBlock',
		'indent'		=> 'Indent',
		'outdent'		=> 'Outdent',
		'blockquote'		=> 'Blockquote',
		'insertparagraphbefore'	=> 'InsertParagraphBefore',
		'insertparagraphafter'	=> 'InsertParagraphAfter',
		'left'			=> 'JustifyLeft',
		'center'		=> 'JustifyCenter',
		'right'			=> 'JustifyRight',
		'justifyfull'		=> 'JustifyFull',
		'orderedlist'		=> 'InsertOrderedList',
		'unorderedlist'		=> 'InsertUnorderedList',
		);

	protected $defaultBlockElements = array(
		'none'		=> 'No block',
		'p'		=> 'Paragraph',
		'h1'		=> 'Heading 1',
		'h2'		=> 'Heading 2',
		'h3'		=> 'Heading 3',
		'h4'		=> 'Heading 4',
		'h5'		=> 'Heading 5',
		'h6'		=> 'Heading 6',
		'pre'		=> 'Preformatted',
		'address'	=> 'Address',
		'blockquote'	=> 'Long quotation',
		'div'		=> 'Section',
	);

	protected $defaultBlockElementsOrder = 'none, p, h1, h2, h3, h4, h5, h6, pre, address, blockquote, div';

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
		global $TSFE, $LANG;

		$registerRTEinJavascriptString = '';
		if (in_array('formatblock', $this->toolbar)) {
			if (!is_array( $this->thisConfig['buttons.']) || !is_array( $this->thisConfig['buttons.']['formatblock.'])) {
				$registerRTEinJavascriptString .= '
			RTEarea['.$RTEcounter.'].buttons.formatblock = new Object();';
			}
				// Default block elements
			$hideItems = array();
			$restrictTo = array('*');
			$blockElementsOrder = $this->defaultBlockElementsOrder;
			$prefixLabelWithTag = false;
			$postfixLabelWithTag = false;

				// Processing PageTSConfig
			if (is_array($this->thisConfig['buttons.']) && is_array($this->thisConfig['buttons.']['formatblock.'])) {
					// Removing elements
				if ($this->thisConfig['buttons.']['formatblock.']['removeItems']) {
					$hideItems =  t3lib_div::trimExplode(',', $this->htmlAreaRTE->cleanList(t3lib_div::strtolower($this->thisConfig['buttons.']['formatblock.']['removeItems'])), 1);
				}
					// Restriction clause
				if ($this->thisConfig['buttons.']['formatblock.']['restrictToItems']) {
					$restrictTo =  t3lib_div::trimExplode(',', $this->htmlAreaRTE->cleanList('none,'.t3lib_div::strtolower($this->thisConfig['buttons.']['formatblock.']['restrictToItems'])), 1);
				}
					// Elements order
				if ($this->thisConfig['buttons.']['formatblock.']['orderItems']) {
					$blockElementsOrder = 'none,'.t3lib_div::strtolower($this->thisConfig['buttons.']['formatblock.']['orderItems']);
				}
				$prefixLabelWithTag = ($this->thisConfig['buttons.']['formatblock.']['prefixLabelWithTag']) ? true : $prefixLabelWithTag;
				$postfixLabelWithTag = ($this->thisConfig['buttons.']['formatblock.']['postfixLabelWithTag']) ? true : $postfixLabelWithTag;
			}
				// Processing old style configuration for hiding paragraphs
			if ($this->thisConfig['hidePStyleItems']) {
				$hideItems = array_merge($hideItems, t3lib_div::trimExplode(',', $this->htmlAreaRTE->cleanList(t3lib_div::strtolower($this->thisConfig['hidePStyleItems'])), 1));
			}
				// Applying User TSConfig restriction
			$blockElementsOrder = array_diff(t3lib_div::trimExplode(',', $this->htmlAreaRTE->cleanList($blockElementsOrder), 1), $hideItems);
			if (!in_array('*', $restrictTo)) {
				$blockElementsOrder = array_intersect($blockElementsOrder, $restrictTo);
			}
				// Localizing the options
			$blockElementsOptions = array();
			if ($this->htmlAreaRTE->cleanList($this->thisConfig['hidePStyleItems']) != '*') {
				$labels = array();
				if (is_array($this->thisConfig['buttons.'])
						&& is_array($this->thisConfig['buttons.']['formatblock.'])
						&& is_array($this->thisConfig['buttons.']['formatblock.']['items.'])) {
					$labels = $this->thisConfig['buttons.']['formatblock.']['items.'];
				}
				foreach ($blockElementsOrder as $item) {
					if ($this->htmlAreaRTE->is_FE()) {
						$blockElementsOptions[$item] = $TSFE->getLLL($this->defaultBlockElements[$item],$this->LOCAL_LANG);
					} else {
						$blockElementsOptions[$item] = $LANG->getLL($this->defaultBlockElements[$item]);
					}
					// Getting custom labels
					if (is_array($labels[$item.'.']) && $labels[$item.'.']['label']) {
						$blockElementsOptions[$item] = $this->htmlAreaRTE->getPageConfigLabel($labels[$item.'.']['label'], 0);
					}
					$blockElementsOptions[$item] = (($prefixLabelWithTag && $item != 'none')?($item . ' - '):'') . $blockElementsOptions[$item] . (($postfixLabelWithTag && $item != 'none')?(' - ' . $item):'');
				}
			}

			$first = array_shift($blockElementsOptions);
				// Sorting the options
			if (!is_array($this->thisConfig['buttons.']) || !is_array($this->thisConfig['buttons.']['formatblock.']) || !$this->thisConfig['buttons.']['formatblock.']['orderItems']) {
				asort($blockElementsOptions);
			}
				// utf8-encode labels if we are responding to an IRRE ajax call
			if (!$this->htmlAreaRTE->is_FE() && $this->htmlAreaRTE->TCEform->inline->isAjaxCall) {
				foreach ($blockElementsOptions as $item => $label) {
					$blockElementsOptions[$item] = $GLOBALS['LANG']->csConvObj->utf8_encode($label, $GLOBALS['LANG']->charSet);
				}
			}
				// Generating the javascript options
			$JSBlockElements = '{
			"'. $first.'" : "none"';
			foreach ($blockElementsOptions as $item => $label) {
				$JSBlockElements .= ',
			"' . $label . '" : "' . $item . '"';
			}
			$JSBlockElements .= '};';

			$registerRTEinJavascriptString .= '
			RTEarea['.$RTEcounter.'].buttons.formatblock.dropDownOptions = '. $JSBlockElements;
		}
		return $registerRTEinJavascriptString;
	}

} // end of class

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/rtehtmlarea/extensions/BlockElements/class.tx_rtehtmlarea_blockelements.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/rtehtmlarea/extensions/BlockElements/class.tx_rtehtmlarea_blockelements.php']);
}

?>