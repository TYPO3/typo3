<?php
namespace TYPO3\CMS\Rtehtmlarea\Extension;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2007-2013 Stanislas Rolland <typo3(arobas)sjbr.ca>
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
 */
class BlockElements extends \TYPO3\CMS\Rtehtmlarea\RteHtmlAreaApi {

	protected $extensionKey = 'rtehtmlarea';

	// The key of the extension that is extending htmlArea RTE
	protected $pluginName = 'BlockElements';

	// The name of the plugin registered by the extension
	protected $relativePathToLocallangFile = 'extensions/BlockElements/locallang.xml';

	// Path to this main locallang file of the extension relative to the extension dir.
	protected $relativePathToSkin = 'extensions/BlockElements/skin/htmlarea.css';

	// Path to the skin (css) file relative to the extension dir
	protected $htmlAreaRTE;

	// Reference to the invoking object
	protected $thisConfig;

	// Reference to RTE PageTSConfig
	protected $toolbar;

	// Reference to RTE toolbar array
	protected $LOCAL_LANG;

	// Frontend language array
	protected $pluginButtons = 'formatblock, indent, outdent, blockquote, insertparagraphbefore, insertparagraphafter, left, center, right, justifyfull, orderedlist, unorderedlist, line';

	protected $convertToolbarForHtmlAreaArray = array(
		'formatblock' => 'FormatBlock',
		'indent' => 'Indent',
		'outdent' => 'Outdent',
		'blockquote' => 'Blockquote',
		'insertparagraphbefore' => 'InsertParagraphBefore',
		'insertparagraphafter' => 'InsertParagraphAfter',
		'left' => 'JustifyLeft',
		'center' => 'JustifyCenter',
		'right' => 'JustifyRight',
		'justifyfull' => 'JustifyFull',
		'orderedlist' => 'InsertOrderedList',
		'unorderedlist' => 'InsertUnorderedList',
		'line' => 'InsertHorizontalRule'
	);

	protected $defaultBlockElements = array(
		'none' => 'No block',
		'p' => 'Paragraph',
		'h1' => 'Heading 1',
		'h2' => 'Heading 2',
		'h3' => 'Heading 3',
		'h4' => 'Heading 4',
		'h5' => 'Heading 5',
		'h6' => 'Heading 6',
		'pre' => 'Preformatted',
		'address' => 'Address',
		'article' => 'Article',
		'aside' => 'Aside',
		'blockquote' => 'Long quotation',
		'div' => 'Container',
		'footer' => 'Footer',
		'header' => 'Header',
		'nav' => 'Navigation',
		'section' => 'Section'
	);

	protected $defaultBlockElementsOrder = 'none, p, h1, h2, h3, h4, h5, h6, pre, address, article, aside, blockquote, div, footer, header, nav, section';

	/**
	 * Return JS configuration of the htmlArea plugins registered by the extension
	 *
	 * @param 	integer		Relative id of the RTE editing area in the form
	 * @return 	string		JS configuration for registered plugins, in this case, JS configuration of block elements
	 */
	public function buildJavascriptConfiguration($RTEcounter) {
		global $TSFE, $LANG;
		$registerRTEinJavascriptString = '';
		if (in_array('formatblock', $this->toolbar)) {
			if (!is_array($this->thisConfig['buttons.']) || !is_array($this->thisConfig['buttons.']['formatblock.'])) {
				$registerRTEinJavascriptString .= '
			RTEarea[' . $RTEcounter . '].buttons.formatblock = new Object();';
			}
			// Default block elements
			$hideItems = array();
			$addItems = array();
			$restrictTo = array('*');
			$blockElementsOrder = $this->defaultBlockElementsOrder;
			$prefixLabelWithTag = FALSE;
			$postfixLabelWithTag = FALSE;
			// Processing PageTSConfig
			if (is_array($this->thisConfig['buttons.']) && is_array($this->thisConfig['buttons.']['formatblock.'])) {
				// Removing elements
				if ($this->thisConfig['buttons.']['formatblock.']['removeItems']) {
					if ($this->htmlAreaRTE->cleanList($this->thisConfig['buttons.']['formatblock.']['removeItems']) == '*') {
						$hideItems = array_diff(array_keys($defaultBlockElements), array('none'));
					} else {
						$hideItems = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $this->htmlAreaRTE->cleanList(\TYPO3\CMS\Core\Utility\GeneralUtility::strtolower($this->thisConfig['buttons.']['formatblock.']['removeItems'])), 1);
					}
				}
				// Adding elements
				if ($this->thisConfig['buttons.']['formatblock.']['addItems']) {
					$addItems = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $this->htmlAreaRTE->cleanList(\TYPO3\CMS\Core\Utility\GeneralUtility::strtolower($this->thisConfig['buttons.']['formatblock.']['addItems'])), 1);
				}
				// Restriction clause
				if ($this->thisConfig['buttons.']['formatblock.']['restrictToItems']) {
					$restrictTo = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $this->htmlAreaRTE->cleanList('none,' . \TYPO3\CMS\Core\Utility\GeneralUtility::strtolower($this->thisConfig['buttons.']['formatblock.']['restrictToItems'])), 1);
				}
				// Elements order
				if ($this->thisConfig['buttons.']['formatblock.']['orderItems']) {
					$blockElementsOrder = 'none,' . \TYPO3\CMS\Core\Utility\GeneralUtility::strtolower($this->thisConfig['buttons.']['formatblock.']['orderItems']);
				}
				$prefixLabelWithTag = $this->thisConfig['buttons.']['formatblock.']['prefixLabelWithTag'] ? TRUE : $prefixLabelWithTag;
				$postfixLabelWithTag = $this->thisConfig['buttons.']['formatblock.']['postfixLabelWithTag'] ? TRUE : $postfixLabelWithTag;
			}
			// Adding custom items
			$blockElementsOrder = array_merge(\TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $this->htmlAreaRTE->cleanList($blockElementsOrder), 1), $addItems);
			// Add div element if indent is configured in the toolbar
			if (in_array('indent', $this->toolbar) || in_array('outdent', $this->toolbar)) {
				$blockElementsOrder = array_merge($blockElementsOrder, array('div'));
			}
			// Add blockquote element if blockquote is configured in the toolbar
			if (in_array('blockquote', $this->toolbar)) {
				$blockElementsOrder = array_merge($blockElementsOrder, array('blockquote'));
			}
			// Remove items
			$blockElementsOrder = array_diff($blockElementsOrder, $hideItems);
			// Applying User TSConfig restriction
			if (!in_array('*', $restrictTo)) {
				$blockElementsOrder = array_intersect($blockElementsOrder, $restrictTo);
			}
			// Localizing the options
			$blockElementsOptions = array();
			$labels = array();
			if (is_array($this->thisConfig['buttons.']) && is_array($this->thisConfig['buttons.']['formatblock.']) && is_array($this->thisConfig['buttons.']['formatblock.']['items.'])) {
				$labels = $this->thisConfig['buttons.']['formatblock.']['items.'];
			}
			foreach ($blockElementsOrder as $item) {
				if ($this->htmlAreaRTE->is_FE()) {
					$blockElementsOptions[$item] = $TSFE->getLLL($this->defaultBlockElements[$item], $this->LOCAL_LANG);
				} else {
					$blockElementsOptions[$item] = $LANG->getLL($this->defaultBlockElements[$item]);
				}
				// Getting custom labels
				if (is_array($labels[$item . '.']) && $labels[$item . '.']['label']) {
					$blockElementsOptions[$item] = $this->htmlAreaRTE->getPageConfigLabel($labels[$item . '.']['label'], 0);
				}
				$blockElementsOptions[$item] = ($prefixLabelWithTag && $item != 'none' ? $item . ' - ' : '') . $blockElementsOptions[$item] . ($postfixLabelWithTag && $item != 'none' ? ' - ' . $item : '');
			}
			$first = array_shift($blockElementsOptions);
			// Sorting the options
			if (!is_array($this->thisConfig['buttons.']) || !is_array($this->thisConfig['buttons.']['formatblock.']) || !$this->thisConfig['buttons.']['formatblock.']['orderItems']) {
				asort($blockElementsOptions);
			}
			// Generating the javascript options
			$JSBlockElements = array();
			$JSBlockElements[] = array($first, 'none');
			foreach ($blockElementsOptions as $item => $label) {
				$JSBlockElements[] = array($label, $item);
			}
			if ($this->htmlAreaRTE->is_FE()) {
				$GLOBALS['TSFE']->csConvObj->convArray($JSBlockElements, $this->htmlAreaRTE->OutputCharset, 'utf-8');
			}
			$registerRTEinJavascriptString .= '
			RTEarea[' . $RTEcounter . '].buttons.formatblock.options = ' . json_encode($JSBlockElements) . ';';
		}
		return $registerRTEinJavascriptString;
	}

}


?>