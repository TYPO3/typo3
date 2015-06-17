<?php
namespace TYPO3\CMS\Rtehtmlarea\Extension;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Rtehtmlarea\RteHtmlAreaApi;

/**
 * BlockElements extension for htmlArea RTE
 *
 * @author Stanislas Rolland <typo3(arobas)sjbr.ca>
 */
class BlockElements extends RteHtmlAreaApi {

	/**
	 * The name of the plugin registered by the extension
	 *
	 * @var string
	 */
	protected $pluginName = 'BlockElements';

	/**
	 * Path to this main locallang file of the extension relative to the extension directory
	 *
	 * @var string
	 */
	protected $relativePathToLocallangFile = 'extensions/BlockElements/locallang.xlf';

	/**
	 * The comma-separated list of button names that the registered plugin is adding to the htmlArea RTE toolbar
	 *
	 * @var string
	 */
	protected $pluginButtons = 'formatblock, indent, outdent, blockquote, insertparagraphbefore, insertparagraphafter, left, center, right, justifyfull, orderedlist, unorderedlist, line';

	/**
	 * The name-converting array, converting the button names used in the RTE PageTSConfing to the button id's used by the JS scripts
	 *
	 * @var array
	 */
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

	/**
	 * List of default block elements
	 *
	 * @var array
	 */
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

	/**
	 * Default order of block elements
	 *
	 * @var string
	 */
	protected $defaultBlockElementsOrder = 'none, p, h1, h2, h3, h4, h5, h6, pre, address, article, aside, blockquote, div, footer, header, nav, section';

	/**
	 * Return JS configuration of the htmlArea plugins registered by the extension
	 *
	 * @param string $rteNumberPlaceholder A dummy string for JS arrays
	 * @return string JS configuration for registered plugins, in this case, JS configuration of block elements
	 */
	public function buildJavascriptConfiguration($rteNumberPlaceholder) {
		$registerRTEinJavascriptString = '';
		if (in_array('formatblock', $this->toolbar)) {
			if (!is_array($this->thisConfig['buttons.']) || !is_array($this->thisConfig['buttons.']['formatblock.'])) {
				$registerRTEinJavascriptString .= '
			RTEarea[' . $rteNumberPlaceholder . '].buttons.formatblock = new Object();';
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
						$hideItems = GeneralUtility::trimExplode(',', $this->htmlAreaRTE->cleanList(GeneralUtility::strtolower($this->thisConfig['buttons.']['formatblock.']['removeItems'])), TRUE);
					}
				}
				// Adding elements
				if ($this->thisConfig['buttons.']['formatblock.']['addItems']) {
					$addItems = GeneralUtility::trimExplode(',', $this->htmlAreaRTE->cleanList(GeneralUtility::strtolower($this->thisConfig['buttons.']['formatblock.']['addItems'])), TRUE);
				}
				// Restriction clause
				if ($this->thisConfig['buttons.']['formatblock.']['restrictToItems']) {
					$restrictTo = GeneralUtility::trimExplode(',', $this->htmlAreaRTE->cleanList('none,' . GeneralUtility::strtolower($this->thisConfig['buttons.']['formatblock.']['restrictToItems'])), TRUE);
				}
				// Elements order
				if ($this->thisConfig['buttons.']['formatblock.']['orderItems']) {
					$blockElementsOrder = 'none,' . GeneralUtility::strtolower($this->thisConfig['buttons.']['formatblock.']['orderItems']);
				}
				$prefixLabelWithTag = $this->thisConfig['buttons.']['formatblock.']['prefixLabelWithTag'] ? TRUE : $prefixLabelWithTag;
				$postfixLabelWithTag = $this->thisConfig['buttons.']['formatblock.']['postfixLabelWithTag'] ? TRUE : $postfixLabelWithTag;
			}
			// Adding custom items
			$blockElementsOrder = array_merge(GeneralUtility::trimExplode(',', $this->htmlAreaRTE->cleanList($blockElementsOrder), TRUE), $addItems);
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
					$blockElementsOptions[$item] = $GLOBALS['TSFE']->getLLL($this->defaultBlockElements[$item], $this->LOCAL_LANG);
				} else {
					$blockElementsOptions[$item] = $GLOBALS['LANG']->getLL($this->defaultBlockElements[$item]);
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
			RTEarea[' . $rteNumberPlaceholder . '].buttons.formatblock.options = ' . json_encode($JSBlockElements) . ';';
		}
		return $registerRTEinJavascriptString;
	}

}
