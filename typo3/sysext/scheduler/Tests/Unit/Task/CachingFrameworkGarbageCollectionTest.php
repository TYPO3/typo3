<?php
namespace TYPO3\CMS\Scheduler\Tests\Unit\Task;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2013 Christian Kuhn <lolli@schwarzbu.ch>
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
 * @author Christian Kuhn <lolli@schwarzbu.ch>
 */
class CachingFrameworkGarbageCollectionTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function executeCallsCollectGarbageOfConfiguredBackend() {
		$cache = $this->getMock('TYPO3\\CMS\\Core\\Cache\\Frontend\\StringFrontend', array(), array(), '', FALSE);
		$cache->expects($this->any())->method('getIdentifier')->will($this->returnValue('cache'));
		$cache->expects($this->atLeastOnce())->method('collectGarbage');
		$GLOBALS['typo3CacheManager'] = new \TYPO3\CMS\Core\Cache\CacheManager();
		$GLOBALS['typo3CacheManager']->registerCache($cache);
		$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'] = array(
			'cache' => array(
				'frontend' => 'TYPO3\\CMS\\Core\\Cache\\Frontend\\StringFrontend',
				'backend' => 'TYPO3\\CMS\\Core\\Cache\\Backend\\AbstractBackend',
			)
		);
		$task = new \TYPO3\CMS\Scheduler\Task\CachingFrameworkGarbageCollectionTask();
		$task->selectedBackends = array('TYPO3\\CMS\\Core\\Cache\\Backend\\AbstractBackend');
		$task->execute();
	}

	/**
	 * @test
	 */
	public function executeDoesNotCallCollectGarbageOfNotConfiguredBackend() {
		$cache = $this->getMock('TYPO3\\CMS\\Core\\Cache\\Frontend\\StringFrontend', array(), array(), '', FALSE);
		$cache->expects($this->any())->method('getIdentifier')->will($this->returnValue('cache'));
		$cache->expects($this->never())->method('collectGarbage');
		$GLOBALS['typo3CacheManager'] = new \TYPO3\CMS\Core\Cache\CacheManager();
		$GLOBALS['typo3CacheManager']->registerCache($cache);
		$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'] = array(
			'cache' => array(
				'frontend' => 'TYPO3\\CMS\\Core\\Cache\\Frontend\\StringFrontend',
				'backend' => 'TYPO3\\CMS\\Core\\Cache\\Backend\\AbstractBackend',
			)
		);
		$task = new \TYPO3\CMS\Scheduler\Task\CachingFrameworkGarbageCollectionTask();
		$task->selectedBackends = array('TYPO3\\CMS\\Core\\Cache\\Backend\\NullBackend');
		$task->execute();
	}

}


?>