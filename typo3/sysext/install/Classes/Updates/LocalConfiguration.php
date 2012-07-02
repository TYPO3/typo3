<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012 Helge Funk <helge.funk@e-net.info>
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

/**
 *
 * @package TYPO3
 * @author Helge Funk <helge.funk@e-net.info>
 */
class Tx_Install_Updates_LocalConfiguration extends Tx_Install_Updates_Base {

	/**
	 * @var string
	 */
	protected $title = 'Update LocalConfiguration';

	/**
	 * Checks if localconf.php is available.
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
			$typoDatabaseVariables = array();
			$additionalConfiguration = array();

			foreach ($localConfigurationContent as $line) {
				$line = trim($line);
				$matches = array();

				if (preg_match('/^\$TYPO3_CONF_VARS\[\'EXT\'\]\[\'extList\'\] *={1} *\'(.+)\';{1}/', $line, $matches) === 1) {

					$extListAsArray = t3lib_div::trimExplode(',', $matches[1], TRUE);
					$typo3ConfigurationVariables[] = '$TYPO3_CONF_VARS[\'EXT\'][\'extList\'] = ' . var_export($extListAsArray, TRUE) . ';';

				} elseif (preg_match('/^\$TYPO3_CONF_VARS.+;{1}/', $line, $matches) === 1) {
					$typo3ConfigurationVariables[] = $matches[0];
				} elseif (preg_match('/^\$typo_db.+;{1}/', $line, $matches) === 1) {
					$typoDatabaseVariables[] = $matches[0];
				} elseif (mb_strlen($line) > 0 && preg_match('/^\/\/.+|^#.+|^<\?php$|^<\?$|^\?>$/', $line, $matches) === 0) {
					$additionalConfiguration[] = $line;
				}
			}

			asort($typoDatabaseVariables);
			t3lib_div::writeFile(
				PATH_typo3conf . t3lib_Configuration::DATABASE_CONFIGURATION_FILE,
				"<?php\n" . implode(LF, $typoDatabaseVariables) . "\n ?>"
			);

			$TYPO3_CONF_VARS = NULL;
			eval(implode(LF, $typo3ConfigurationVariables));

			t3lib_utility_Array::sortByKeyRecursive($TYPO3_CONF_VARS);
			t3lib_div::writeFile(
				PATH_typo3conf . t3lib_Configuration::LOCAL_CONFIGURATION_FILE,
				"<?php\nreturn " . var_export($TYPO3_CONF_VARS, TRUE) . ";\n ?>"
			);

			if (sizeof($additionalConfiguration) > 0) {
				t3lib_div::writeFile(
					PATH_typo3conf . t3lib_Configuration::ADDITIONAL_CONFIGURATION_FILE,
					"<?php\n" . implode(LF, $additionalConfiguration) . "\n ?>"
				);
			} else {
				@unlink(PATH_typo3conf . t3lib_Configuration::ADDITIONAL_CONFIGURATION_FILE);
			}

			rename(PATH_typo3conf . 'localconf.php', PATH_typo3conf . 'obsolete.localconf.php');

			$result = TRUE;

		} catch (Exception $e) {

		}

		return $result;
	}

}
?>