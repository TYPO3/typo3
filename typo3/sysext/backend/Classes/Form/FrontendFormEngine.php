<?php
namespace TYPO3\CMS\Backend\Form;

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
 * Contains a frontend version of the TYPO3 Core Form generator - AKA "TCEforms"
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
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
	 * Function for wrapping labels.
	 *
	 * @param string $str The string to wrap
	 * @return string
	 */
	public function wrapLabels($str) {
		return '<font face="verdana" size="1" color="black">' . $str . '</font>';
	}

	/**
	 * Prints the palette in the frontend editing (forms-on-page?)
	 *
	 * @param array $paletteArray The palette array to print
	 * @return string HTML output
	 */
	public function printPalette(array $paletteArray) {
		$out = '';
		$bgColor = ' bgcolor="#D6DAD0"';
		foreach ($paletteArray as $content) {
			$hRow[] = '<td' . $bgColor . '><font face="verdana" size="1">&nbsp;</font></td><td nowrap="nowrap"' . $bgColor . '><font color="#666666" face="verdana" size="1">' . $content['NAME'] . '</font></td>';
			$iRow[] = '<td valign="top">' . '<img name="req_' . $content['TABLE'] . '_' . $content['ID'] . '_' . $content['FIELD'] . '" src="clear.gif" width="10" height="10" alt="" />' . '<img name="cm_' . $content['TABLE'] . '_' . $content['ID'] . '_' . $content['FIELD'] . '" src="clear.gif" width="7" height="10" alt="" />' . '</td><td nowrap="nowrap" valign="top">' . $content['ITEM'] . $content['HELP_ICON'] . '</td>';
		}
		$out = '<table border="0" cellpadding="0" cellspacing="0">
			<tr><td><img src="clear.gif" width="' . intval($this->paletteMargin) . '" height="1" alt="" /></td>' . implode('', $hRow) . '</tr>
			<tr><td></td>' . implode('', $iRow) . '</tr>
		</table>';
		return $out;
	}

	/**
	 * Sets the fancy front-end design of the editor.
	 * Frontend
	 *
	 * @return void
	 */
	public function setFancyDesign() {
		$this->fieldTemplate = '
	<tr>
		<td nowrap="nowrap" bgcolor="#F6F2E6">###FIELD_HELP_ICON###<font face="verdana" size="1" color="black"><strong>###FIELD_NAME###</strong></font>###FIELD_HELP_TEXT###</td>
	</tr>
	<tr>
		<td nowrap="nowrap" bgcolor="#ABBBB4"><img name="req_###FIELD_TABLE###_###FIELD_ID###_###FIELD_FIELD###" src="clear.gif" width="10" height="10" alt="" /><img name="cm_###FIELD_TABLE###_###FIELD_ID###_###FIELD_FIELD###" src="clear.gif" width="7" height="10" alt="" /><font face="verdana" size="1" color="black">###FIELD_ITEM###</font>###FIELD_PAL_LINK_ICON###</td>
	</tr>	';
		$this->totalWrap = '<table border="0" cellpadding="1" cellspacing="0" bgcolor="black"><tr><td><table border="0" cellpadding="2" cellspacing="0">|</table></td></tr></table>';
		$this->palFieldTemplate = '
	<tr>
		<td nowrap="nowrap" bgcolor="#ABBBB4"><font face="verdana" size="1" color="black">###FIELD_PALETTE###</font></td>
	</tr>	';
		$this->palFieldTemplateHeader = '
	<tr>
		<td nowrap="nowrap" bgcolor="#F6F2E6"><font face="verdana" size="1" color="black"><strong>###FIELD_HEADER###</strong></font></td>
	</tr>	';
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
	 * Insert additional style sheet link
	 *
	 * @param string $key Some key identifying the style sheet
	 * @param string $href Uri to the style sheet file
	 * @param string $title Value for the title attribute of the link element
	 * @param string $relation Value for the rel attribute of the link element
	 * @return void
	 */
	public function addStyleSheet($key, $href, $title = '', $relation = 'stylesheet') {
		/** @var $pageRenderer \TYPO3\CMS\Core\Page\PageRenderer */
		$pageRenderer = $GLOBALS['TSFE']->getPageRenderer();
		$pageRenderer->addCssFile($this->prependBackPath($href), $relation, 'screen', $title);
	}

	/**
	 * Initializes an anonymous template container.
	 * The created container can be compared to alt_doc.php in backend-only disposal.
	 *
	 * @return void
	 */
	public function initializeTemplateContainer() {
		$GLOBALS['TBE_TEMPLATE'] = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Template\\FrontendDocumentTemplate');
		$GLOBALS['TBE_TEMPLATE']->getPageRenderer()->addInlineSetting('', 'PATH_typo3', \TYPO3\CMS\Core\Utility\GeneralUtility::dirname(\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('SCRIPT_NAME')) . '/' . TYPO3_mainDir);
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
		if (strpos($url, '://') !== FALSE || substr($url, 0, 1) === '/') {
			return $url;
		} else {
			return $this->backPath . $url;
		}
	}

}


?>