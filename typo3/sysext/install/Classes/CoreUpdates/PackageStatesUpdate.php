<?php
namespace TYPO3\CMS\Install\CoreUpdates;

/**
 * Move the list of available extensions from LocalConfiguration
 * to its own Flow supported PackageStates configuration
 *
 * @author Thomas Maroschik <tmaroschik@dfau.de>
 */

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Thomas Maroschik <tmaroschik@dfau.de>
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
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
class PackageStatesUpdate extends \TYPO3\CMS\Install\Updates\AbstractUpdate {

	/**
	 * @var string The title
	 */
	protected $title = 'Migrate Extension List to PackageStates.php';

	/**
	 * Checks if localconf.php is available. If so, the update should be done
	 *
	 * @param string &$description: The description for the update
	 * @return boolean TRUE if update should be done
	 */
	public function checkForUpdate(&$description) {
		$description = 'The package configuration stored in LocalConfiguration.php is obsolete and will be moved to a PackageStates.php';
		$description .= '<br /><strong>It is strongly recommended to run this wizard now.</strong><br />';
		$result = TRUE;
		if (@is_file((PATH_typo3conf . 'PackageStates.php'))) {
			$result = TRUE;
		}
		return $result;
	}

	/**
	 * Performs the update action.
	 *
	 * The methods reads localconf.php line by line and classifies every line
	 * to be either part of LocalConfiguration (everything that starts with TYPO3_CONF_VARS),
	 * belongs to the database settings (those will be merged to TYPO3_CONF_VARS),
	 * and everything else (those will be moved to the AdditionalConfiguration file.
	 *
	 * @param array &$dbQueries: Queries done in this update
	 * @param mixed &$customMessages: Custom messages
	 * @return boolean TRUE if everything went well
	 */
	public function performUpdate(array &$dbQueries, &$customMessages) {
		$result = FALSE;
		try {
			$bootstrap = \TYPO3\CMS\Core\Core\Bootstrap::getInstance();
			$packageManager = new PackageStatesUpdate\UpdatePackageManager($bootstrap->getEarlyInstance('TYPO3\\CMS\\Core\\Configuration\\ConfigurationManager'));
			$packageManager->createPackageStatesFile($bootstrap, PATH_site, PATH_typo3conf . 'PackageStates.php');
			$result = TRUE;
		} catch (\Exception $exception) {
			@unlink(PATH_typo3conf . 'PackageStates.php');
			throw $exception;
		}
		return $result;
	}

}


?>