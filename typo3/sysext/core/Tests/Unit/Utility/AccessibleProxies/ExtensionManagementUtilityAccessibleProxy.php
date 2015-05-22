<?php
namespace TYPO3\CMS\Core\Tests\Unit\Utility\AccessibleProxies;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

/**
 * Accessible proxy with protected methods made public
 */
class ExtensionManagementUtilityAccessibleProxy extends ExtensionManagementUtility {

	static public function setCacheManager(CacheManager $cacheManager = NULL) {
		static::$cacheManager = $cacheManager;
	}

	static public function getPackageManager() {
		return static::$packageManager;
	}

	static public function getExtLocalconfCacheIdentifier() {
		return parent::getExtLocalconfCacheIdentifier();
	}

	static public function loadSingleExtLocalconfFiles() {
		parent::loadSingleExtLocalconfFiles();
	}

	static public function getBaseTcaCacheIdentifier() {
		return parent::getBaseTcaCacheIdentifier();
	}

	static public function resetExtTablesWasReadFromCacheOnceBoolean() {
		self::$extTablesWasReadFromCacheOnce = FALSE;
	}

	static public function createExtLocalconfCacheEntry() {
		parent::createExtLocalconfCacheEntry();
	}

	static public function createExtTablesCacheEntry() {
		parent::createExtTablesCacheEntry();
	}

	static public function getExtTablesCacheIdentifier() {
		return parent::getExtTablesCacheIdentifier();
	}

	static public function buildBaseTcaFromSingleFiles() {
		$GLOBALS['TCA'] = array();
	}

	static public function emitTcaIsBeingBuiltSignal(array $tca) {
	}

	static public function removeDuplicatesForInsertion($insertionList, $list = '') {
		return parent::removeDuplicatesForInsertion($insertionList, $list);
	}
}
