<?php
namespace TYPO3\CMS\Install;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012-2013 Christian Kuhn <lolli@schwarzbu.ch>
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
 *
 ***************************************************************/
/**
 * Encapsulate install tool specific bootstrap methods.
 *
 * This script is internal code and subject to change.
 * DO NOT use it in own code, or be prepared your code might
 * break in future core versions.
 *
 * @author Christian Kuhn <lolli@schwarzbu.ch>
 */
class InstallBootstrap {

	/**
	 * During first install, typo3conf/LocalConfiguration.php does not
	 * exist. It is created now based on factory configuration as a
	 * first action in the install process.
	 *
	 * @return void
	 * @internal This is not a public API method, do not use in own extensions
	 */
	static public function createLocalConfigurationIfNotExists() {
		/** @var $configurationManager \TYPO3\CMS\Core\Configuration\ConfigurationManager */
		$configurationManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Configuration\\ConfigurationManager');
		if (
			!file_exists($configurationManager->getLocalConfigurationFileLocation())
			&& !file_exists($configurationManager->getLocalconfFileLocation())
		) {
			$configurationManager->createLocalConfigurationFromFactoryConfiguration();
		}
	}

	/**
	 * Check ENABLE_INSTALL_TOOL and FIRST_INSTALL file in typo3conf
	 * or exit the script if conditions to access the install tool are not met.
	 *
	 * @return void
	 * @internal This is not a public API method, do not use in own extensions
	 */
	static public function checkEnabledInstallToolOrDie() {
		$quickstartFile = PATH_site . 'typo3conf/FIRST_INSTALL';
		$enableInstallToolFile = PATH_site . 'typo3conf/ENABLE_INSTALL_TOOL';
		// If typo3conf/FIRST_INSTALL is present and can be deleted, automatically create typo3conf/ENABLE_INSTALL_TOOL
		if (is_file($quickstartFile) && is_writeable($quickstartFile) && unlink($quickstartFile)) {
			touch($enableInstallToolFile);
		}
		// Additional security measure if ENABLE_INSTALL_TOOL file cannot, but
		// should be deleted (in case it is write-protected, for example).
		$removeInstallToolFileFailed = FALSE;
		// Only allow Install Tool access if the file "typo3conf/ENABLE_INSTALL_TOOL" is found
		if (is_file($enableInstallToolFile) && time() - filemtime($enableInstallToolFile) > 3600) {
			$content = file_get_contents($enableInstallToolFile);
			$verifyString = 'KEEP_FILE';
			if (trim($content) !== $verifyString) {
				// Delete the file if it is older than 3600s (1 hour)
				if (!@unlink($enableInstallToolFile)) {
					$removeInstallToolFileFailed = TRUE;
				}
			}
		}
		if (!is_file($enableInstallToolFile) || $removeInstallToolFileFailed) {
			self::dieWithLockedInstallToolMessage();
		}
	}

	/**
	 * Exit the script with a message that the install tool is locked.
	 *
	 * @return void
	 */
	static protected function dieWithLockedInstallToolMessage() {
		require_once PATH_site . 'typo3/sysext/core/Classes/Html/HtmlParser.php';
		// Define the stylesheet
		$stylesheet = '<link rel="stylesheet" type="text/css" href="' . '../stylesheets/install/install.css" />';
		$javascript = '<script type="text/javascript" src="' . '../contrib/prototype/prototype.js"></script>';
		$javascript .= '<script type="text/javascript" src="' . '../sysext/install/Resources/Public/Javascript/install.js"></script>';
		// Get the template file
		$template = @file_get_contents((PATH_site . 'typo3/templates/install.html'));
		// Define the markers content
		$markers = array(
			'styleSheet' => $stylesheet,
			'javascript' => $javascript,
			'title' => 'The Install Tool is locked',
			'content' => '
				<p>
					To enable the Install Tool, the file ENABLE_INSTALL_TOOL must be created.
				</p>
				<ul>
					<li>
						In the typo3conf/ folder, create a file named ENABLE_INSTALL_TOOL. The file name is
						case sensitive, but the file itself can simply be an empty file.
					</li>
					<li class="t3-install-locked-user-settings">
						Alternatively, in the Backend, go to <a href="javascript:top.goToModule(\'tools_install\',1);">Admin tools &gt; Install</a>
						and let TYPO3 create this file for you.<br />
						You are recommended to log out from the Install Tool after finishing your work.
						The file will then automatically be deleted.
					</li>
				</ul>
				<p>
					For security reasons, it is highly recommended that you either rename or delete the file after the operation is finished.
				</p>
				<p>
					As an additional security measure, if the file is older than one hour, TYPO3 will automatically delete it. The file must be writable by the web server user.
				</p>
			'
		);
		// Fill the markers
		$content = \TYPO3\CMS\Core\Html\HtmlParser::substituteMarkerArray($template, $markers, '###|###', 1, 1);
		// Output the warning message and exit
		header('Content-Type: text/html; charset=utf-8');
		header('Cache-Control: no-cache, must-revalidate');
		header('Pragma: no-cache');
		echo $content;
		die;
	}

}


?>