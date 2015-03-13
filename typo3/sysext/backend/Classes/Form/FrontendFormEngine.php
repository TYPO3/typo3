<?php
namespace TYPO3\CMS\Backend\Form;

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

/**
 * Extension class for the rendering of TCEforms in the frontend
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
class FrontendFormEngine extends \TYPO3\CMS\Backend\Form\FormEngine {

	/**
	 * Constructs this object.
	 */
	public function __construct() {
		$this->initializeTemplateContainer();
		parent::__construct();
	}

	/**
	 * Prints the palette in the frontend editing (forms-on-page?)
	 *
	 * @param array $paletteArray The palette array to print
	 * @return string HTML output
	 * @deprecated since TYPO3 CMS 7, will be removed in TYPO3 CMS 8
	 */
	public function printPalette(array $paletteArray) {
		GeneralUtility::logDeprecatedFunction();
		$out = '';
		$bgColor = ' bgcolor="#D6DAD0"';
		foreach ($paletteArray as $content) {
			$hRow[] = '<td' . $bgColor . '><font face="verdana" size="1">&nbsp;</font></td><td nowrap="nowrap"' . $bgColor . '><font color="#666666" face="verdana" size="1">' . $content['NAME'] . '</font></td>';
			$iRow[] = '<td valign="top">' . '<img name="req_' . $content['TABLE'] . '_' . $content['ID'] . '_' . $content['FIELD'] . '" src="clear.gif" width="10" height="10" alt="" /></td><td nowrap="nowrap" valign="top">' . $content['ITEM'] . $content['HELP_ICON'] . '</td>';
		}
		$out = '<table border="0" cellpadding="0" cellspacing="0">
			<tr><td><img src="clear.gif" width="1" height="1" alt="" /></td>' . implode('', $hRow) . '</tr>
			<tr><td></td>' . implode('', $iRow) . '</tr>
		</table>';
		return $out;
	}

	/**
	 * Includes a javascript library that exists in the core /typo3/ directory. The
	 * backpath is automatically applied.
	 * This method adds the library to $GLOBALS['TSFE']->additionalHeaderData[$lib].
	 *
	 * @param string $lib Library name. Call it with the full path like "contrib/prototype/prototype.js" to load it
	 * @return void
	 */
	public function loadJavascriptLib($lib) {
		/** @var $pageRenderer \TYPO3\CMS\Core\Page\PageRenderer */
		$pageRenderer = $GLOBALS['TSFE']->getPageRenderer();
		$pageRenderer->addJsLibrary($lib, $this->prependBackPath($lib));
	}

	/**
	 * Initializes an anonymous template container.
	 * The created container can be compared to "record_edit" module in backend-only disposal.
	 *
	 * @return void
	 */
	public function initializeTemplateContainer() {
		$GLOBALS['TBE_TEMPLATE'] = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Template\FrontendDocumentTemplate::class);
		$GLOBALS['TBE_TEMPLATE']->getPageRenderer()->addInlineSetting('', 'PATH_typo3', GeneralUtility::dirname(GeneralUtility::getIndpEnv('SCRIPT_NAME')) . '/' . TYPO3_mainDir);
		$GLOBALS['SOBE'] = new \stdClass();
		$GLOBALS['SOBE']->doc = $GLOBALS['TBE_TEMPLATE'];
	}

	/**
	 * Prepends backPath to given URL if it's not an absolute URL
	 *
	 * @param string $url
	 * @return string
	 */
	private function prependBackPath($url) {
		return $url;
	}

}
