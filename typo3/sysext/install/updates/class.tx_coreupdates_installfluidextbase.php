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
 * Contains the update class for installing extbase and fluid
 * for installed extensions that depend on it now.
 *
 * @author Christian Kuhn <lolli@schwarzbu.ch>
 */
class tx_coreupdates_installFluidExtbase extends Tx_Install_Updates_Base {

	/**
	 * @var string Title of this wizard
	 */
	protected $title = 'Install fluid and extbase to satisfy dependencies of system extensions';

	/**
	 * @var array Extensions keys that depend on fluid / extbase
	 */
	protected $extensionsDependingOnFluidExtbase = array(
		'about',
		'aboutmodules',
		'workspaces'
	);

	/**
	 * Checks if an update is needed
	 *
	 * @param string &$description: The description for the update
	 * @return boolean whether an update is needed (TRUE) or not (FALSE)
	 */
	public function checkForUpdate(&$description) {
		$result = FALSE;

		$description = 'These loaded extensions depend on fluid and extbase:';
		$description .= '<ul>';
		if (!t3lib_extMgm::isLoaded('extbase') || !t3lib_extMgm::isLoaded('fluid')) {
			foreach($this->extensionsDependingOnFluidExtbase as $extension) {
				if (t3lib_extMgm::isLoaded($extension)) {
					$result = TRUE;
					$description .= '<li>' . $extension . '</li>';
				}
			}
		}
		$description .= '</ul>';

		return $result;
	}

	/**
	 * Adds fluid and extbase to the extList in TYPO3_CONF_VARS
	 *
	 * @param array &$dbQueries: Queries done in this update
	 * @param mixed &$customMessages: Custom messages
	 * @return boolean Whether it worked (TRUE) or not (FALSE)
	 */
	public function performUpdate(array &$dbQueries, &$customMessages) {
		$this->installExtensions(array('extbase', 'fluid'));
		return TRUE;
	}
}
?>