<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2008-2011 Benjamin Mack <benni@typo3.org>
*  (c) 2008-2011 Steffen Kamper <info@sk-typo3.de>
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
 * Contains the update class for adding outsourced system extensions.
 *
 * @author  Benjamin Mack <benni@typo3.org>
 * @author  Steffen Kamper <info@sk-typo3.de>
 */
class tx_coreupdates_installsysexts extends Tx_Install_Updates_Base {
	protected $title = 'Install Outsourced System Extensions';
	protected $outsourcedSystemExtensions = array('info', 'perm', 'func', 'filelist', 'about', 'cshmanual', 'feedit', 'opendocs', 'simulatestatic');

	/**
	 * Checks if an update is needed
	 *
	 * @param string &$description: The description for the update
	 * @return boolean whether an update is needed (TRUE) or not (FALSE)
	 */
	public function checkForUpdate(&$description) {
		$result = FALSE;

        $description = '
			<ul>
		';

		foreach($this->outsourcedSystemExtensions as $_EXTKEY) {
			if (!t3lib_extMgm::isLoaded($_EXTKEY)) {
				$EM_CONF = FALSE;
					// extension may not been loaded at this point, so we can't use an API function from t3lib_extmgm
				require_once(PATH_site . 'typo3/sysext/' . $_EXTKEY . '/ext_emconf.php');
				$description .= '
					<li>
						<strong>
							' . htmlspecialchars($EM_CONF[$_EXTKEY]['title']) . ' [' . $_EXTKEY . ']
						</strong>
						<br />
						' . htmlspecialchars($EM_CONF[$_EXTKEY]['description']) . '
					</li>
				';

				$result = TRUE;
			}
		}

		$description .= '
			</ul>
		';
		if ($this->isWizardDone()) {
			$result = FALSE;
		}

		return $result;
	}

	/**
	 * second step: get user input for installing sysextensions
	 *
	 * @param string $inputPrefix input prefix, all names of form fields have to start with this. Append custom name in [ ... ]
	 * @return string HTML output
	 */
	public function getUserInput($inputPrefix) {
        $content = '
			<p>
				<strong>
					Install the following SystemExtensions:
				</strong>
			</p>
		';

		$content .= '
			<fieldset>
				<ol>
		';

		foreach($this->outsourcedSystemExtensions as $_EXTKEY) {
			if (!t3lib_extMgm::isLoaded($_EXTKEY)) {
				$EM_CONF = FALSE;
					// extension may not been loaded at this point, so we can't use an API function from t3lib_extmgm
				require_once(PATH_site . 'typo3/sysext/' . $_EXTKEY . '/ext_emconf.php');
				$content .= '
					<li class="labelAfter">
						<input type="checkbox" id="' . $_EXTKEY . '" name="' . $inputPrefix . '[sysext][' . $_EXTKEY . ']" value="1" checked="checked" />
						<label for="' . $_EXTKEY . '">' . $EM_CONF[$_EXTKEY]['title'] . ' [' . $_EXTKEY . ']</label>
					</li>
				';
			}
		}

		$content .= '
				</ol>
			</fieldset>
		';

		return $content;
	}

	/**
	 * Adds the outsourced extensions to the extList in TYPO3_CONF_VARS
	 *
	 * @param	array		&$dbQueries: queries done in this update
	 * @param	mixed		&$customMessages: custom messages
	 * @return	boolean		whether it worked (TRUE) or not (FALSE)
	 */
	public function performUpdate(array &$dbQueries, &$customMessages) {

			// Get extension keys that were submitted by the user to be installed and that are valid for this update wizard
		if (is_array($this->pObj->INSTALL['update']['installSystemExtensions']['sysext'])) {
			$extArray = array_intersect(
				$this->outsourcedSystemExtensions,
				array_keys($this->pObj->INSTALL['update']['installSystemExtensions']['sysext'])
			);
			$this->installExtensions($extArray);
		}

			// Never show this wizard again
		$this->markWizardAsDone();

		return TRUE;
	}
}
?>