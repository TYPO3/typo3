<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012 Tolleiv Nietsch <info@tolleiv.de>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
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
 * Upgrade wizard which creates all sys_file* tables. Required to ensure that all
 * other FAL migration wizards can run properly.
 *
 * @package     TYPO3
 * @author      Tolleiv Nietsch <info@tolleiv.de>
 * @license     http://www.gnu.org/copyleft/gpl.html
 */
class Tx_Install_Updates_File_InitUpdateWizard extends Tx_Install_Updates_Base {
	/**
	 * @var string
	 */
	protected $title = 'Initialize FAL tables';

	/**
	 * @var t3lib_install_Sql
	 */
	protected $installerSql;

	/**
	 * Creates this object.
	 */
	public function __construct() {
		$this->installerSql = t3lib_div::makeInstance('t3lib_install_Sql');
	}

	/**
	 * Checks if an update is needed
	 *
	 * @param	string		&$description: The description for the update
	 * @return	boolean		TRUE if an update is needed, FALSE otherwise
	 */
	public function checkForUpdate(&$description) {
		$description = 'Create the tables which are required for the file abstraction layer.';
		return count($this->getRequiredUpdates()) > 0;
	}

	/**
	 * Performs the database update.
	 *
	 * @param	array		&$dbQueries: queries done in this update
	 * @param	mixed		&$customMessages: custom messages
	 * @return	boolean		TRUE on success, FALSE on error
	 */
	public function performUpdate(&$dbQueries, &$customMessages) {
		$updates = $this->getRequiredUpdates();
		foreach ($updates as $update) {
			$GLOBALS['TYPO3_DB']->admin_query($update);
			$dbQueries[] = $update;
		}
		return TRUE;
	}


	/**
	 * Determine all create table statements which create the sys_file* tables
	 *
	 * @return array
	 */
	protected function getRequiredUpdates() {
		$requiredUpdates = array();

		$fileContent = t3lib_div::getUrl(PATH_t3lib . 'stddb/tables.sql');
		$FDfile = $this->installerSql->getFieldDefinitions_fileContent($fileContent);
		$FDdb = $this->installerSql->getFieldDefinitions_database(TYPO3_db);
		$diff = $this->installerSql->getDatabaseExtra($FDfile, $FDdb);
		$update_statements = $this->installerSql->getUpdateSuggestions($diff);

		foreach ((array) $update_statements['create_table'] as $string) {
			if (preg_match('/^CREATE TABLE sys_file($|_)/', $string)) {
				$requiredUpdates[] = $string;
			}
		}

		return $requiredUpdates;
	}
}

?>