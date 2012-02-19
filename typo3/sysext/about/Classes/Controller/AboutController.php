<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012 Christian Kuhn <lolli@schwarzbu.ch>
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
 * Module 'about' shows some standard information for TYPO3 CMS: About-text, version number and so on.
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 * @author Steffen Kamper <steffen@typo3.org>
 * @author Christian Kuhn <lolli@schwarzbu.ch>
 * @package TYPO3
 * @subpackage about
 */
class Tx_About_Controller_AboutController extends Tx_Extbase_MVC_Controller_ActionController {

	/**
	 * Main action: Show standard information
	 *
	 * @return void
	 */
	public function indexAction() {
		$this->view
			->assign('TYPO3Version', TYPO3_version)
			->assign('TYPO3CopyrightYear', TYPO3_copyright_year)
			->assign('TYPO3UrlDonate', TYPO3_URL_DONATE)
			->assign('loadedExtensions', $this->getLoadedExtensionInformation())
			->assign('customContents', $this->getCustomContent())
		;
	}

	protected function getLoadedExtensionInformation() {
		$loadedExtensions = $GLOBALS['TYPO3_LOADED_EXT'];
		$extensionsInformation = array();
		foreach ($loadedExtensions as $extensionKey => $extension) {
			if (is_array($extension) && $extension['type'] != 'S') {
				$emconfPath = PATH_site . $extension['siteRelPath'] . 'ext_emconf.php';
				include($emconfPath);
				$extensionInformation = array();
				$extensionInformation['key'] = $extensionKey;
				$extensionInformation['title'] = $EM_CONF['']['title'];
				$extensionInformation['author'] = $EM_CONF['']['author'];
				$extensionInformation['authorEmail'] = $EM_CONF['']['author_email'];
				$extensionsInformation[] = $extensionInformation;
			}
		}
		return $extensionsInformation;
	}

	/**
	 * Hook to add custom content
	 *
	 * @return array with additional content sections
	 * @deprecated Since 4.7; will be removed together with the call in indexAction and the fluid partial in 4.9
	 */
	protected function getCustomContent() {
		$sections = array();
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['about/index.php']['addSection'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['about/index.php']['addSection'] as $classRef) {
				t3lib_div::deprecationLog(
					'Hook about/index.php addSection is deprecated and will be removed in TYPO3 4.9, use fluid overrides instead.'
				);

					/** @var $hookObject tx_about_customSections */
				$hookObject = t3lib_div::getUserObj($classRef);
				if (!($hookObject instanceof tx_about_customSections)) {
					throw new UnexpectedValueException(
						'$hookObject must implement interface tx_about_customSections',
						1298121573
					);
				}
				$hookObject->addSection($sections);
			}
		}
		return $sections;
	}
}
?>