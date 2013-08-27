<?php
namespace TYPO3\CMS\Core\Tests\Unit\Resource\Utility;


use TYPO3\CMS\Core\Resource\Utility\StorageUtility;

class StorageUtilityTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function isLocalStorageContainedInOtherLocalStorageReturnsFalseIfStoragesHaveDifferentPathtypes() {
		$containingStorageConfiguration = array(
			'pathType' => 'relative'
		);
		$containedStorageConfiguration = array(
			'pathType' => 'absolute'
		);

		$this->assertFalse(StorageUtility::isLocalStorageContainedInOtherLocalStorage($containingStorageConfiguration, $containedStorageConfiguration));
	}

	/**
	 * @test
	 */
	public function isLocalStorageContainedInOtherLocalStorageReturnsFalseIfContainedContainsContainer() {
		$containingStorageConfiguration = array(
			'pathType' => 'relative',
			'basePath' => '/foo/bar/'
		);
		$containedStorageConfiguration = array(
			'pathType' => 'relative',
			'basePath' => '/foo/'
		);

		$this->assertFalse(StorageUtility::isLocalStorageContainedInOtherLocalStorage($containingStorageConfiguration, $containedStorageConfiguration));
	}

	/**
	 * @test
	 */
	public function isLocalStorageContainedInOtherLocalStorageReturnsTrueIfContainerContainsStorage() {
		$containingStorageConfiguration = array(
			'pathType' => 'relative',
			'basePath' => '/foo/'
		);
		$containedStorageConfiguration = array(
			'pathType' => 'relative',
			'basePath' => '/foo/bar/'
		);

		$this->assertTrue(StorageUtility::isLocalStorageContainedInOtherLocalStorage($containingStorageConfiguration, $containedStorageConfiguration));
	}
}
