<?php
namespace TYPO3\CMS\Install\Updates;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012-2013 Tolleiv Nietsch <info@tolleiv.de>
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
 * @author Tolleiv Nietsch <info@tolleiv.de>
 * @license http://www.gnu.org/copyleft/gpl.html
 */
class InitUpdateWizard extends \TYPO3\CMS\Install\Updates\AbstractUpdate {

	/**
	 * @var string
	 */
	protected $title = 'Initialize database tables for the File Abstraction Layer (FAL)';

	/**
	 * @var \TYPO3\CMS\Install\Sql\SchemaMigrator
	 */
	protected $installerSql;

	/**
	 * Creates this object.
	 */
	public function __construct() {
		$this->installerSql = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Install\\Sql\\SchemaMigrator');
	}

	/**
	 * Checks if an update is needed
	 *
	 * @param 	string		&$description: The description for the update
	 * @return 	boolean		TRUE if an update is needed, FALSE otherwise
	 */
	public function checkForUpdate(&$description) {
		$description = 'Create the database tables which are required for the File Abstraction Layer in order to work. Do this as the first step for all further wizards related to FAL.';
		return count($this->getRequiredUpdates()) > 0;
	}

	/**
	 * Performs the database update.
	 *
	 * @param 	array		&$dbQueries: queries done in this update
	 * @param 	mixed		&$customMessages: custom messages
	 * @return 	boolean		TRUE on success, FALSE on error
	 */
	public function performUpdate(array &$dbQueries, &$customMessages) {
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
		$fileContent = \TYPO3\CMS\Core\Utility\GeneralUtility::getUrl(
			\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('core'). 'ext_tables.sql'
		);
		$FDfile = $this->installerSql->getFieldDefinitions_fileContent($fileContent);
		$FDdb = $this->installerSql->getFieldDefinitions_database(TYPO3_db);
		$diff = $this->installerSql->getDatabaseExtra($FDfile, $FDdb);
		$update_statements = $this->installerSql->getUpdateSuggestions($diff);
		foreach ((array) $update_statements['create_table'] as $string) {
			if (preg_match('/^CREATE TABLE sys_file($|_)?/', $string)) {
				$requiredUpdates[] = $string;
			}
		}
		return $requiredUpdates;
	}

}


?>