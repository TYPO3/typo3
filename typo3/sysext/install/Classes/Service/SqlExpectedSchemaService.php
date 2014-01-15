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

		// Add caching framework sql definition
		$sqlString[] = $this->getCachingFrameworkRequiredDatabaseSchema();

		// Add category registry sql definition
		$sqlString[] = \TYPO3\CMS\Core\Category\CategoryRegistry::getInstance()->getDatabaseTableDefinitions();

		return implode(LF . LF . LF . LF, $sqlString);
	}

	/**
	 * Get schema SQL of required cache framework tables.
	 *
	 * This method needs ext_localconf and ext_tables loaded!
	 *
	 * This is a hack, but there was no smarter solution with current cache configuration setup:
	 * ToolController sets the extbase caches to NullBackend to ensure the install tool does not
	 * cache anything. The CacheManager gets the required SQL from database backends only, so we need to
	 * temporarily 'fake' the standard db backends for extbase caches so they are respected.
	 *
	 * Additionally, the extbase_object cache is already in use and instantiated, and the CacheManager singleton
	 * does not allow overriding this definition. The only option at the moment is to 'fake' another cache with
	 * a different name, and then substitute this name in the sql content with the real one.
	 *
	 * @TODO: This construct needs to be improved. It does not recognise if some custom ext overwrote the extbase cache config
	 * @TODO: Solve this as soon as cache configuration is separated from ext_localconf / ext_tables
	 * @TODO: It might be possible to reduce this ugly construct by circumventing the 'singleton' of CacheManager by using 'new'
	 *
	 * @return string Cache framework SQL
	 */
	public function getCachingFrameworkRequiredDatabaseSchema() {
		$cacheConfigurationBackup = $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'];
		$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['extbase_datamapfactory_datamap'] = array(
			'groups' => array('system')
		);
		$extbaseObjectFakeName = uniqid('extbase_object');
		$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'][$extbaseObjectFakeName] = array(
			'groups' => array('system')
		);
		$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['extbase_reflection'] = array(
			'groups' => array('system')
		);
		$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['extbase_typo3dbbackend_tablecolumns'] = array(
			'groups' => array('system')
		);
		/** @var \TYPO3\CMS\Core\Cache\CacheManager $cacheManager */
		$cacheManager = $GLOBALS['typo3CacheManager'];
		$cacheManager->setCacheConfigurations($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']);
		$cacheSqlString = \TYPO3\CMS\Core\Cache\Cache::getDatabaseTableDefinitions();
		$sqlString = str_replace($extbaseObjectFakeName, 'extbase_object', $cacheSqlString);
		$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'] = $cacheConfigurationBackup;
		$cacheManager->setCacheConfigurations($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']);

		return $sqlString;
	}
}
