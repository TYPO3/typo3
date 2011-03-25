<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 1999-2011 Kasper Skårhøj (kasperYYYY@typo3.com)
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
 * Revised for TYPO3 3.6 November/2003 by Kasper Skårhøj
 * XHTML compliant
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @author	Steffen Kamper <steffen@typo3.org>
 * @package TYPO3
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   73: class SC_mod_help_about_index
 *   91:	 function main()
 *  125:	 function printContent()
 *
 * TOTAL FUNCTIONS: 2
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */

unset($MCONF);
require ('conf.php');
require ($BACK_PATH . 'init.php');
require ($BACK_PATH . 'template.php');
$LANG->includeLLFile('EXT:lang/locallang_mod_help_about.xml');
$BE_USER->modAccess($MCONF, 1);


/**
 * Script Class for the Help > About module
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage core
 */
class SC_mod_help_about_index {

	// Internal, dynamic:
	/**
	 * @var array
	 */
	public $MCONF = array();

	/**
	 * @var array
	 */
	public $MOD_MENU = array();

	/**
	 * @var array
	 */
	public $MOD_SETTINGS = array();

	/**
	 * @var string
	 */
	protected $content = '';

	/**
	 * @var array
	 */
	protected $sections = array();


	/**
	 * Main function, producing the module output.
	 * In this case, the module output is a very simple screen telling the version of TYPO3 and that's basically it...
	 * The content is set in the internal variable $this->content
	 *
	 * @return	void
	 */
	function main() {

		$this->MCONF = $GLOBALS['MCONF'];

		// **************************
		// Main
		// **************************
		#$TBE_TEMPLATE->bgColor = '#cccccc';
		$content = $this->renderSections();

		// Renders the module page
		$this->content = $GLOBALS['TBE_TEMPLATE']->render(
			$GLOBALS['LANG']->getLL('title', TRUE),
			$content
		);
	}

	/**
	 * Renders main sections
	 *
	 * @return string $content
	 */
	public function renderSections() {
		$this->sections = array();

		$this->renderAboutTypo3();
		$this->renderDonation();
		$this->renderCommunityCredits();
		$this->renderCoreteamCredits();
		$this->render3rdPartyCredits();
		$this->renderExtensionAuthors();
		$this->renderCustomSections();

		// compile content
		$content = '<div id="typo3-mod-help-about-index-php-outer">'
				. implode('', $this->sections) . '</div>';

		return $content;
	}

	/**
	 * Outputs the accumulated content to screen
	 *
	 * @return	void
	 */
	public function printContent() {
		echo $this->content;
	}


	protected function renderAboutTypo3() {
		$minorText = sprintf($GLOBALS['LANG']->getLL('minor'), 'TYPO3 Ver. ' . htmlspecialchars(TYPO3_version) . ', Copyright &copy; ' . htmlspecialchars(TYPO3_copyright_year), 'Kasper Sk&aring;rh&oslash;j');
		$content = '
			<img' . t3lib_iconWorks::skinImg($GLOBALS['BACK_PATH'], 'gfx/typo3logo.gif', 'width="123" height="34"') . ' alt="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_mod_help_about.xml:typo3_logo', true) . '" />
			<div class="typo3-mod-help-about-index-php-inner">
				<h2>' . $GLOBALS['LANG']->getLL('welcome', TRUE) . '</h2>
					<p>' . $minorText . '</p>
			</div>';

		$this->sections['about'] = $content;
	}

	/**
	 * Renders TYPO3 donation
	 *
	 * @return void
	 */
	protected function renderDonation() {
		$content =
				'<div class="typo3-mod-help-about-index-php-inner">
				<h2>' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_mod_help_about.xml:donation_header', TRUE) . '</h2>
				<p id="donation-description">' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_mod_help_about.xml:donation_message') . '</p>
				<div class="donation-button">
					<input type="button" id="donation-button" value="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_mod_help_about.xml:donation_button') . '"
					onclick="window.open(\'' . TYPO3_URL_DONATE . '\');" />
				</div>
			</div>';

		$this->sections['donation'] = $content;
	}

	/**
	 * Renders community credits
	 *
	 * @return void
	 */
	protected function renderCommunityCredits() {
		$content = '
			<div class="typo3-mod-help-about-index-php-inner">
				<h2>' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_mod_help_about.xml:community_credits', TRUE) . '</h2>
				<p>' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_mod_help_about.xml:information_detail') . '</p>
			</div>';

		$this->sections['community'] = $content;
	}

	/**
	 * Renders community credits
	 *
	 * @return void
	 */
	protected function render3rdPartyCredits() {
		$content = '
			<div class="typo3-mod-help-about-index-php-inner">
				<h2>' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_mod_help_about.xml:external_libraries', true) . '</h2>
				<p>' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_mod_help_about.xml:external_thanks', true) . '</p>
				<br />
				<table border="0" cellspacing="2" cellpadding="1">
				<tr><td width="280">Audio player Plugin</td><td><a href="http://www.1pixelout.net/code/audio-player-wordpress-plugin/" target="_blank">www.1pixelout.net</a></td></tr>
				<tr><td>CodeMirror</td><td><a href="http://codemirror.net/" target="_blank">codemirror.net</a></td></tr>
				<tr><td>ExtJS</td><td><a href="http://www.sencha.com/" target="_blank">www.sencha.com</a></td></tr>
				<tr><td>JSMin</td><td><a href="http://www.crockford.com" target="_blank">www.crockford.com</a></td></tr>
				<tr><td>Modernizr</td><td><a href="http://www.modernizr.com" target="_blank">www.modernizr.com</a></td></tr>
				<tr><td>Prototype JavaScript framework</td><td><a href="http://www.prototypejs.org/" target="_blank">www.prototypejs.org</a></td></tr>
				<tr><td>RemoveXSS</td><td><a href="http://quickwired.com/smallprojects/php_xss_filter_function.php" target="_blank">quickwired.com</a></td></tr>
				<tr><td>script.aculo.us</td><td><a href="http://script.aculo.us" target="_blank">script.aculo.us</a></td></tr>
				<tr><td>SWFUpload</td><td><a href="http://www.swfupload.org" target="_blank">www.swfupload.org</a></td></tr>
				<tr><td>Swift Mailer</td><td><a href="http://swiftmailer.org" target="_blank">swiftmailer.org</a></td></tr>
				</table>
			</div>';

		$this->sections['3rdparty'] = $content;
	}

	/**
	 * Renders core team credits
	 *
	 * @return void
	 */
	protected function renderCoreteamCredits() {
		$content = '
			<div class="typo3-mod-help-about-index-php-inner">
				<h2>' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_mod_help_about.xml:coredevs', TRUE) . '</h2>
				<p>' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_mod_help_about.xml:coredevs_detail') . '</p>
			</div>';

		$this->sections['coreteam'] = $content;
	}

	/**
	 * Renders custom sections
	 *
	 * @return void
	 */
	protected function renderCustomSections() {
		//hook for custom sections
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['about/index.php']['addSection'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['about/index.php']['addSection'] as $classRef) {
				$hookObject = t3lib_div::getUserObj($classRef);
				if (!($hookObject instanceof tx_about_customSections)) {
					throw new UnexpectedValueException('$hookObject must implement interface tx_about_customSections', 1298121573);
				}
				$hookObject->addSection($this->sections);
			}
		}
	}

	/**
	 * Renders extension authors credits
	 *
	 * @return void
	 */
	protected function renderExtensionAuthors() {
		$content = '<table border="0" cellspacing="2" cellpadding="1"><tr><th>' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_mod_help_about.xml:extension', true) . '</th><th>' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_mod_help_about.xml:extension_author', true) . '</th></tr>';

		$loadedExtensions = $GLOBALS['TYPO3_LOADED_EXT'];
		foreach ($loadedExtensions as $extensionKey => $extension) {
			if (is_array($extension) && $extension['type'] != 'S') {
				$emconfPath = PATH_site . $extension['siteRelPath'] . 'ext_emconf.php';
				include($emconfPath);

				$emconf = $EM_CONF['']; // ext key is not set when loading the ext_emconf.php directly

				$content .= '<tr><td width="280">' . $emconf['title'] . ' (' . $extensionKey . ')</td>' .
						'<td><a href="mailto:' . $emconf['author_email'] . '?subject=' . rawurlencode('Thanks for your ' . $emconf['title'] . ' extension') . '">' . $emconf['author'] . '</a></td></tr>';
			}
		}
	}

}

// Include extension?
if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['typo3/mod/help/about/index.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['typo3/mod/help/about/index.php']);
}


// Make instance:
$SOBE = t3lib_div::makeInstance('SC_mod_help_about_index');
$SOBE->main();
$SOBE->printContent();
?>