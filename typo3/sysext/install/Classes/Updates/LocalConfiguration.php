<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010-2011  Steffen Ritter (info@rs-websystems.de)
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
 * Contains the update class for not installed t3skin. Used by the update wizard in the install tool.
 *
 * @author	Steffen Ritter <info@rs-websystems.de>
 */
class Tx_Install_Updates_LocalConfiguration extends Tx_Install_Updates_Base {
	protected $title = 'Update LocalConfiguration';


	/**
	 * Checks if t3skin is not installed.
	 *
	 * @param	string		&$description: The description for the update
	 * @return	boolean		whether an update is needed (TRUE) or not (FALSE)
	 */
	public function checkForUpdate(&$description) {
		$result = FALSE;
		if (@is_file(PATH_typo3conf . 'localconf.php')) {
			$result = TRUE;
		}
		return $result;
	}

	/**
	 * performs the action of the UpdateManager
	 *
	 * @param	array		&$dbQueries: queries done in this update
	 * @param	mixed		&$customMessages: custom messages
	 * @return	bool		whether everything went smoothly or not
	 */
	public function performUpdate(array &$dbQueries, &$customMessages) {
		$result = FALSE;

		try {

			$localConfigurationContent = file(PATH_typo3conf . 'localconf.php');
			$typo3ConfigurationVariables = array();
			$typoDbVariables = array();
			$additionalConfiguration = array();

			foreach ($localConfigurationContent as $line) {
				$line = trim($line);
				$matches = array();

				if (preg_match('/^\$TYPO3_CONF_VARS\[\'EXT\'\]\[\'extList\'\] *={1} *\'(.+)\';{1}/', $line, $matches) === 1) {
					debug($matches, 'enet_hf_$matches', __LINE__, __FILE__);

					$extListAsArray = t3lib_div::trimExplode(',', $matches[1], TRUE);
					$typo3ConfigurationVariables[] = '$TYPO3_CONF_VARS[\'EXT\'][\'extList\'] = ' . var_export($extListAsArray, TRUE) . ';';

				} elseif (preg_match('/^\$TYPO3_CONF_VARS.+;{1}/', $line, $matches) === 1) {
					$typo3ConfigurationVariables[] = $matches[0];
				} elseif (preg_match('/^\$typo_db.+;{1}/', $line, $matches) === 1){
					$typoDbVariables[] = $matches[0];
				} elseif (mb_strlen($line) > 0 && preg_match('/^\/\/.+|^#.+|^<\?php$|^<\?$|^\?>$/', $line, $matches) === 0) {
					$additionalConfiguration[] = $line;
				}
			}

			asort($typoDbVariables);
			file_put_contents(
				PATH_typo3conf . 'DatabaseConfiguration.php',
				"<?php\n" . implode(LF, $typoDbVariables) . "\n ?>"
			);

			asort($typo3ConfigurationVariables);
			$TYPO3_CONF_VARS = NULL;
			eval(implode(LF, $typo3ConfigurationVariables));

			file_put_contents(
				PATH_typo3conf . 'LocalConfiguration.php',
				"<?php\nreturn " . var_export($TYPO3_CONF_VARS, TRUE) . ";\n ?>"
			);

			if (sizeof($additionalConfiguration) > 0) {
				file_put_contents(
					PATH_typo3conf . 'AdditionalConfiguration.php',
					"<?php\n" . implode(LF, $additionalConfiguration) . "\n ?>"
				);
			} else {
				@unlink(PATH_typo3conf . 'AdditionalConfiguration.php');
			}

			//rename(PATH_typo3conf . 'localconf.php', PATH_typo3conf . 'obsolete.localconf.php');

			$result = TRUE;

		} catch (Exception $e) {

		}

		return $result;
	}

}
?>