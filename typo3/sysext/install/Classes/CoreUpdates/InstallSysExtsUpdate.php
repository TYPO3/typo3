<?php
namespace TYPO3\CMS\Install\CoreUpdates;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2008-2013 Benjamin Mack <benni@typo3.org>
 *  (c) 2008-2013 Steffen Kamper <info@sk-typo3.de>
 *  (c) 2012-2013 Kai Vogel <kai.vogel@speedprogs.de>
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
 * Contains the update class for adding new system extensions.
 *
 * @author Benjamin Mack <benni@typo3.org>
 * @author Steffen Kamper <info@sk-typo3.de>
 * @author  Kai Vogel <kai.vogel@speedprogs.de>
 */
class InstallSysExtsUpdate extends \TYPO3\CMS\Install\Updates\AbstractUpdate {

	/**
	 * @var string
	 */
	protected $title = 'Install System Extensions';

	/**
	 * @var array
	 */
	protected $systemExtensions = array(
		'info',
		'perm',
		'func',
		'filelist',
		'about',
		'cshmanual',
		'feedit',
		'opendocs',
		'recycler',
		't3editor',
		'reports',
		'scheduler',
		'simulatestatic',
	);

	/**
	 * @var array
	 */
	protected $extensionDetails = array(
		'simulatestatic' => array(
			'title' => 'Simulate Static URLs',
			'description' => 'Adds the possibility to have Speaking URLs in the TYPO3 Frontend pages.',
			'versionString' => '2.0.0',
		),
	);

	/**
	 * @var string
	 */
	protected $extEmConfPath = 'typo3/sysext/@extensionKey/ext_emconf.php';

	/**
	 * @var string
	 */
	protected $repositoryUrl = 'http://typo3.org/fileadmin/ter/@filename';

	/**
	 * @var string
	 */
	protected $informationUrl = 'http://typo3.org/index.php?type=95832&tx_terfe2_pi1%5Baction%5D=show&tx_terfe2_pi1%5Bformat%5D=json&tx_terfe2_pi1%5BextensionKey%5D=@extensionKey';

	/**
	 * @var bool
	 */
	protected $updateSuccessful = TRUE;

	/**
	 * Checks if an update is needed
	 *
	 * @param string &$description: The description for the update
	 * @return boolean whether an update is needed (TRUE) or not (FALSE)
	 */
	public function checkForUpdate(&$description) {
		$description = '
			<br />
			Uninstalled system extensions have been found.
			It is now possible to install them automatically by this upgrade wizard.
		';

		if ($this->isWizardDone()) {
			return FALSE;
		}

		foreach ($this->systemExtensions as $extensionKey) {
			if (!\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded($extensionKey)) {
				return TRUE;
			}
		}

		return FALSE;
	}

	/**
	 * Second step: Get user input for installing system extensions
	 *
	 * @param string $inputPrefix input prefix, all names of form fields have to start with this. Append custom name in [ ... ]
	 * @return string HTML output
	 */
	public function getUserInput($inputPrefix) {
		$list = '
			<p>
				Install the following system extensions:
			</p>
			<fieldset>
				<ol class="t3-install-form-label-after">%s</ol>
			</fieldset>';
		$item = '
			<li class="labelAfter">
				<input type="checkbox" id="%1$s" name="%2$s[sysext][%1$s]" value="1" checked="checked" />
				<label for="%1$s"><strong>%3$s [%1$s]</strong><br />%4$s</label>
			</li>';
		$items = array();

		foreach ($this->systemExtensions as $extensionKey) {
			if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded($extensionKey)) {
				continue;
			}
			$extension = $this->getExtensionDetails($extensionKey);
			$items[] = sprintf(
				$item,
				$extensionKey,
				$inputPrefix,
				htmlspecialchars($extension['title']),
				htmlspecialchars($extension['description'])
			);
		}

		return sprintf($list, implode('', $items));
	}

	/**
	 * Adds the outsourced extensions to the extList in TYPO3_CONF_VARS
	 *
	 * @param array &$dbQueries: queries done in this update
	 * @param mixed &$customMessages: custom messages
	 * @return boolean whether it worked (TRUE) or not (FALSE)
	 */
	public function performUpdate(array &$dbQueries, &$customMessages) {
			// Get extension keys that were submitted by the user to be installed and that are valid for this update wizard
		if (is_array($this->pObj->INSTALL['update']['installSystemExtensions']['sysext'])) {
			$extArray = array_intersect(
				$this->systemExtensions,
				array_keys($this->pObj->INSTALL['update']['installSystemExtensions']['sysext'])
			);
			$this->installExtensions($extArray, $customMessages);
		}

			// Show this wizard again only if new extension keys have been found
		$this->markWizardAsDone();

		return $this->updateSuccessful;
	}

	/**
	 * This method can be called to install extensions following all proper processes
	 * (e.g. installing in extList, respecting priority, etc.)
	 *
	 * @param array $extensionKeys List of keys of extensions to install
	 * @param mixed $customMessages
	 * @return void
	 */
	protected function installExtensions($extensionKeys, &$customMessages) {
		/** @var $extensionListUtility \TYPO3\CMS\Extensionmanager\Utility\ListUtility */
		$extensionListUtility = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
			'TYPO3\\CMS\\Extensionmanager\\Utility\\ListUtility'
		);

		/** @var $extensionFileHandlingUtility \TYPO3\CMS\Extensionmanager\Utility\FileHandlingUtility */
		$extensionFileHandlingUtility = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
			'TYPO3\\CMS\\Extensionmanager\\Utility\\FileHandlingUtility'
		);
		/** @var $objectManager \TYPO3\CMS\Extbase\Object\ObjectManager */
		$objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');

		/** @var $extensionInstallUtility \TYPO3\CMS\Extensionmanager\Utility\InstallUtility */
		$extensionInstallUtility = $objectManager->get('TYPO3\\CMS\\Extensionmanager\\Utility\\InstallUtility');

		/** @var $extensionTerUtility \TYPO3\CMS\Extensionmanager\Utility\Connection\TerUtility */
		$extensionTerUtility = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
			'TYPO3\\CMS\\Extensionmanager\\Utility\\Connection\\TerUtility'
		);
		$availableExtensions = $extensionListUtility->getAvailableExtensions();
		$availableAndInstalledExtensions = $extensionListUtility->getAvailableAndInstalledExtensions($availableExtensions);
		foreach ($extensionKeys as $extensionKey) {
			if (!is_array($availableAndInstalledExtensions[$extensionKey])) {
				$extensionDetails = $this->getExtensionDetails($extensionKey);
				if (empty($extensionDetails)) {
					$this->updateSuccessful = FALSE;
					$customMessages .= 'No version information for extension ' . $extensionKey . '. Cannot install it.';
					continue;
				}
				$t3xContent = $this->fetchExtension($extensionKey, $extensionDetails['versionString']);
				if (empty($t3xContent)) {
					$this->updateSuccessful = FALSE;
					$customMessages .= 'The extension ' . $extensionKey . ' could not be downloaded.';
					continue;
				}
				$t3xExtracted = $extensionTerUtility->decodeExchangeData($t3xContent);
				if (empty($t3xExtracted) || !is_array($t3xExtracted) || empty($t3xExtracted['extKey'])) {
					$this->updateSuccessful = FALSE;
					$customMessages .= 'The extension ' . $extensionKey . ' could not be extracted.';
					continue;
				}
				$extensionFileHandlingUtility->unpackExtensionFromExtensionDataArray($t3xExtracted);
			}
			$extensionInstallUtility->install($extensionKey);
		}
	}

	/**
	 * Returns the details of a local or external extension
	 *
	 * @param string $extensionKey Key of the extension to check
	 * @return array Extension details
	 */
	protected function getExtensionDetails($extensionKey) {
			// Local extension
		$extEmConf = PATH_site . str_replace('@extensionKey', $extensionKey, $this->extEmConfPath);
		if (file_exists($extEmConf)) {
			$EM_CONF = FALSE;
			require_once($extEmConf);
			return reset($EM_CONF);
		}

		if (array_key_exists($extensionKey, $this->extensionDetails)) {
			return $this->extensionDetails[$extensionKey];
		}

		return array();
	}

	/**
	 * Fetch extension from repository
	 *
	 * @param string $extensionKey The extension key to fetch
	 * @param string $version The version to fetch
	 * @return string T3X file content
	 */
	protected function fetchExtension($extensionKey, $version) {
		if (empty($extensionKey) || empty($version)) {
			return '';
		}

		$filename = $extensionKey[0] . '/' . $extensionKey[1] . '/' . $extensionKey . '_' . $version . '.t3x';
		$url = str_replace('@filename', $filename, $this->repositoryUrl);
		return $this->fetchUrl($url);
	}

	/**
	 * Open an URL and return the response
	 *
	 * This wrapper method is required to try several download methods if
	 * the configuration is not valid or initially written by the installer.
	 *
	 * @param string $url The URL to file
	 * @throws \Exception
	 * @return string File content
	 */
	protected function fetchUrl($url) {
		if (empty($url)) {
			return NULL;
		}

		$fileContent = \TYPO3\CMS\Core\Utility\GeneralUtility::getUrl($url, 0, array(TYPO3_user_agent));

			// No content, force cURL if disabled in configuration but found in system
		if ($fileContent === FALSE && function_exists('curl_init') && empty($GLOBALS['TYPO3_CONF_VARS']['SYS']['curlUse'])) {
			$GLOBALS['TYPO3_CONF_VARS']['SYS']['curlUse'] = TRUE;
			$fileContent = \TYPO3\CMS\Core\Utility\GeneralUtility::getUrl($url, 0, array(TYPO3_user_agent));
			$GLOBALS['TYPO3_CONF_VARS']['SYS']['curlUse'] = FALSE;
		}

			// Still no content, try file_get_contents if allow_url_fopen is enabled
		if ($fileContent === FALSE && function_exists('file_get_contents') && ini_get('allow_url_fopen')) {
			$fileContent = file_get_contents($url);
		}

			// Can not fetch url, throw an exception
		if ($fileContent === FALSE) {
			throw new \Exception(
				'Can not fetch URL "' . $url . '". Possibile reasons are network problems or misconfiguration.',
				1344685036
			);
		}

		return $fileContent;
	}

	/**
	 * Marks the wizard as being "seen" so that it not shown again until
	 * no new extension keys have been found.
	 *
	 * Writes the info in LocalConfiguration.php
	 *
	 * @return void
	 */
	protected function markWizardAsDone() {
		\TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Configuration\\ConfigurationManager')->setLocalConfigurationValueByPath(
			'INSTALL/wizardDone/' . get_class($this),
			json_encode($this->systemExtensions)
		);
	}

	/**
	 * Checks if all extensions have been "seen" before
	 *
	 * @return boolean TRUE if wizard has been done before, FALSE otherwise
	 */
	protected function isWizardDone() {
		$wizardClassName = get_class($this);
		if (!empty($GLOBALS['TYPO3_CONF_VARS']['INSTALL']['wizardDone'][$wizardClassName])) {
			$seenExtensions = json_decode($GLOBALS['TYPO3_CONF_VARS']['INSTALL']['wizardDone'][$wizardClassName], TRUE);
			return (bool) array_diff($this->systemExtensions, $seenExtensions);
		}
		return FALSE;
	}
}

?>