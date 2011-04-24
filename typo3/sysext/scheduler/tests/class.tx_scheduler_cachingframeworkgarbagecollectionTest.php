<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010-2011 Christian Kuhn <lolli@schwarzbu.ch>
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
 * Testcase for class "tx_scheduler_CachingFrameworkGarbageCollection"
 *
 * @package TYPO3
 * @subpackage tx_scheduler
 *
 * @author Christian Kuhn <lolli@schwarzbu.ch>
 */
class tx_scheduler_CachingFrameworkGarbageCollectionTest extends tx_phpunit_testcase {

	/**
	 * Enable backup of global and system variables
	 *
	 * @var boolean
	 */
	protected $backupGlobals = TRUE;

	/**
	 * Exclude TYPO3_DB from backup/ restore of $GLOBALS
	 * because resource types cannot be handled during serialization
	 *
	 * @var array
	 */
	protected $backupGlobalsBlacklist = array('TYPO3_DB');

	/**
	 * @test
	 */
	public function executeCallsCollectGarbageOfConfiguredBackend() {
		$cache = $this->getMock('t3lib_cache_frontend_StringFrontend', array(), array(), '', FALSE);
		$cache->expects($this->any())->method('getIdentifier')->will($this->returnValue('cache'));

		$cache->expects($this->atLeastOnce())->method('collectGarbage');

		$GLOBALS['typo3CacheManager'] = new t3lib_cache_Manager();
		$GLOBALS['typo3CacheManager']->registerCache($cache);
		$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'] = array(
			'cache' => array(
				'frontend' => 't3lib_cache_frontend_StringFrontend',
				'backend' => 't3lib_cache_backend_AbstractBackend',
			),
		);

		$task = new tx_scheduler_CachingFrameworkGarbageCollection();
		$task->selectedBackends = array('t3lib_cache_backend_AbstractBackend');
		$task->execute();
	}

	/**
	 * @test
	 */
	public function executeDoesNotCallCollectGarbageOfNotConfiguredBackend() {
		$cache = $this->getMock('t3lib_cache_frontend_StringFrontend', array(), array(), '', FALSE);
		$cache->expects($this->any())->method('getIdentifier')->will($this->returnValue('cache'));

		$cache->expects($this->never())->method('collectGarbage');

		$GLOBALS['typo3CacheManager'] = new t3lib_cache_Manager();
		$GLOBALS['typo3CacheManager']->registerCache($cache);
		$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'] = array(
			'cache' => array(
				'frontend' => 't3lib_cache_frontend_StringFrontend',
				'backend' => 't3lib_cache_backend_AbstractBackend',
			),
		);

		$task = new tx_scheduler_CachingFrameworkGarbageCollection();
		$task->selectedBackends = array('t3lib_cache_backend_NullBackend');
		$task->execute();
	}
}
?>