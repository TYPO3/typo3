<?php
namespace TYPO3\CMS\Install\Service;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011-2013 Christian Kuhn <lolli@schwarzbu.ch>
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
 *  A copy is found in the text file GPL.txt and important notices to the license
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

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Expected schema service
 *
 * @internal use in install tool only!
 */
class SqlExpectedSchemaService {

	/**
	 * @var \TYPO3\CMS\Extbase\Object\ObjectManager
	 * @inject
	 */
	protected $objectManager = NULL;

	/**
	 * @var \TYPO3\CMS\Extbase\SignalSlot\Dispatcher
	 * @inject
	 */
	protected $signalSlotDispatcher;

	/**
	 * Get expected schema array
	 *
	 * @return array Expected schema
	 */
	public function getExpectedDatabaseSchema() {
		/** @var \TYPO3\CMS\Install\Service\SqlSchemaMigrationService $schemaMigrationService */
		$schemaMigrationService = $this->objectManager->get('TYPO3\\CMS\\Install\\Service\\SqlSchemaMigrationService');
		// Raw concatenated ext_tables.sql and friends string
		$expectedSchemaString = $this->getTablesDefinitionString();
		// Remove comments
		$cleanedExpectedSchemaString = implode(LF, $schemaMigrationService->getStatementArray($expectedSchemaString, TRUE, '^CREATE TABLE '));
		$expectedSchema = $schemaMigrationService->getFieldDefinitions_fileContent($cleanedExpectedSchemaString);
		return $expectedSchema;
	}

	/**
	 * Cycle through all loaded extensions and get full table definitions as concatenated string
	 *
	 * @param boolean $withStatic TRUE if sql from ext_tables_static+adt.sql should be loaded, too.
	 * @return string Concatenated SQL of loaded extensions ext_tables.sql
	 */
	public function getTablesDefinitionString($withStatic = FALSE) {
		$sqlString = array();

		// Find all ext_tables.sql of loaded extensions
		$loadedExtensionInformation = $GLOBALS['TYPO3_LOADED_EXT'];
		foreach ($loadedExtensionInformation as $extensionConfiguration) {
			if ((is_array($extensionConfiguration) || $extensionConfiguration instanceof \ArrayAccess) && $extensionConfiguration['ext_tables.sql']) {
				$sqlString[] = GeneralUtility::getUrl($extensionConfiguration['ext_tables.sql']);
			}
			if ($withStatic
				&& (is_array($extensionConfiguration) || $extensionConfiguration instanceof \ArrayAccess)
				&& $extensionConfiguration['ext_tables_static+adt.sql']
			) {
				$sqlString[] = GeneralUtility::getUrl($extensionConfiguration['ext_tables_static+adt.sql']);
			}
		}

		$sqlString = $this->emitTablesDefinitionIsBeingBuiltSignal($sqlString);

		return implode(LF . LF . LF . LF, $sqlString);
	}

	/**
	 * Emits a signal to manipulate the tables definitions
	 *
	 * @param array $sqlString
	 * @return mixed
	 */
	protected function emitTablesDefinitionIsBeingBuiltSignal(array $sqlString) {
		$signalReturn = $this->signalSlotDispatcher->dispatch(__CLASS__, 'tablesDefinitionIsBeingBuilt', array('sqlString' => $sqlString));
		$sqlString = $signalReturn['sqlString'];
		if (!is_array($sqlString)) {
			throw new Exception\UnexpectedSignalReturnValueTypeException(
				sprintf(
					'The signal %s of class %s returned a value of type %s, but array was expected.',
					'tablesDefinitionIsBeingBuilt',
					__CLASS__,
					gettype($sqlString)
				),
				1382351456
			);
		}
		return $sqlString;
	}
}
