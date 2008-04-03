<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2008 Kasper Skaarhoj (kasperYYYY@typo3.com)
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
 * Module: About
 * This document shows some standard-information for TYPO3 CMS: About-text, version number and so on.
 *
 * $Id$
 * Revised for TYPO3 3.6 November/2003 by Kasper Skaarhoj
 * XHTML compliant
 *
 * @todo	This module could use a major overhaul in general.
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   73: class SC_mod_help_about_index
 *   91:     function main()
 *  125:     function printContent()
 *
 * TOTAL FUNCTIONS: 2
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */

unset($MCONF);
require ('conf.php');
require ($BACK_PATH.'init.php');
require ($BACK_PATH.'template.php');
$LANG->includeLLFile('EXT:lang/locallang_mod_help_about.xml');
$BE_USER->modAccess($MCONF,1);








/**
 * Script Class for the Help > About module
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage core
 */
class SC_mod_help_about_index {

		// Internal, dynamic:
	var $MCONF=array();
	var $MOD_MENU=array();
	var $MOD_SETTINGS=array();
	var $content;




	/**
	 * Main function, producing the module output.
	 * In this case, the module output is a very simple screen telling the version of TYPO3 and that's basically it...
	 * The content is set in the internal variable $this->content
	 *
	 * @return	void
	 */
	function main()	{
		global $TBE_TEMPLATE,$LANG,$BACK_PATH;

		$this->MCONF = $GLOBALS['MCONF'];

		// **************************
		// Main
		// **************************
		#$TBE_TEMPLATE->bgColor = '#cccccc';
		$TBE_TEMPLATE->backPath = $GLOBALS['BACK_PATH'];
		$TBE_TEMPLATE->docType = 'xhtml_trans';
		$this->content.= $TBE_TEMPLATE->startPage('About');

		$minorText = sprintf($LANG->getLL('minor'), 'TYPO3 Ver. '.htmlspecialchars(TYPO3_version).', Copyright &copy; '.htmlspecialchars(TYPO3_copyright_year), 'Kasper Sk&aring;rh&oslash;j');

		$content='
			<div id="typo3-mod-help-about-index-php-outer">
				<img src="'.$BACK_PATH.'gfx/typo3logo.gif" width="123" height="34" vspace="10" alt="TYPO3 logo" />
				<div class="typo3-mod-help-about-index-php-inner">
					<h2>TYPO3 Information</h2>
					<h3>'.$LANG->getLL('welcome',1).'</h3>
					<p>'.$minorText.'</p>
				</div>

				<div class="typo3-mod-help-about-index-php-inner">
					<h2>Community Credits</h2>
					<p>Visit <a href="http://typo3.org/community/" target="_blank">typo3.org/community</a> if you want to know why TYPO3 rocks.</p>
				</div>

				<div class="typo3-mod-help-about-index-php-inner">
					<h2>Extension Authors</h2>
					<p>This is a list of the people who contributed to the extensions you\'re using in your installation.</p>
					<p><br />'.$this->getExtensionAuthors().'</p>
				</div>
			</div>
		';
		$this->content.= $content;
		$this->content.= $TBE_TEMPLATE->endPage();
	}

	/**
	 * Outputting the accumulated content to screen
	 *
	 * @return	void
	 */
	function printContent()	{
		echo $this->content;
	}

	/**
	 * gets the author names from the installed extensions
	 *
	 * @return	string	list of extensions authors and their e-mail
	 */
	function getExtensionAuthors() {
		$content = '<table border="0" cellspacing="2" cellpadding="1"><tr><th>Extension</th><th>Author</th></tr>';

		$loadedExtensions = $GLOBALS['TYPO3_LOADED_EXT'];
		foreach ($loadedExtensions as $extensionKey => $extension) {
			if (is_array($extension) && $extension['type'] != 'S') {
				$emconfPath = PATH_site.$extension['siteRelPath'].'ext_emconf.php';
				include($emconfPath);

				$emconf = $EM_CONF['']; // ext key is not set when loading the ext_emconf.php directly

				$content.= '<tr><td>'.$emconf['title'].' ('.$extensionKey.')</td>'.
						 		'<td><a href="mailto:'.$emconf['author_email'].'?subject='.rawurlencode('Thanks for your '.$emconf['title'].' extension').'">'.$emconf['author'].'</a></td></tr>';
			}
		}

		$content.= '</table>';

		return $content;
	}
}

// Include extension?
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['typo3/mod/help/about/index.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['typo3/mod/help/about/index.php']);
}












// Make instance:
$SOBE = t3lib_div::makeInstance('SC_mod_help_about_index');
$SOBE->main();
$SOBE->printContent();
?>
