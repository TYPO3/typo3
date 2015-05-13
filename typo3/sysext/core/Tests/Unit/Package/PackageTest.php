<?php
namespace TYPO3\CMS\Core\Tests\Unit\Package;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\CMS\Core\Package\Package as Package;
use org\bovigo\vfs\vfsStream;

/**
 * Testcase for the package class
 *
 */
class PackageTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 */
	protected function setUp() {
		vfsStream::setup('Packages');
	}

	/**
	 * @test
	 * @expectedException \TYPO3\CMS\Core\Package\Exception\InvalidPackagePathException
	 */
	public function constructThrowsPackageDoesNotExistException() {
		$packageManagerMock = $this->getMock(\TYPO3\CMS\Core\Package\PackageManager::class);
		$packageManagerMock->expects($this->any())->method('isPackageKeyValid')->willReturn(TRUE);
		new Package($packageManagerMock, 'Vendor.TestPackage', './ThisPackageSurelyDoesNotExist');
	}

	/**
	 */
	public function validPackageKeys() {
		return array(
			array('Doctrine.DBAL'),
			array('TYPO3.CMS'),
			array('My.Own.TwitterPackage'),
			array('GoFor.IT'),
			array('Symfony.Symfony')
		);
	}

	/**
	 * @test
	 * @dataProvider validPackageKeys
	 */
	public function constructAcceptsValidPackageKeys($packageKey) {
		$packagePath = 'vfs://Packages/' . str_replace('\\', '/', $packageKey) . '/';
		mkdir($packagePath, 0777, TRUE);
		file_put_contents($packagePath . 'composer.json', '{"name": "' . $packageKey . '", "type": "flow-test"}');
		file_put_contents($packagePath . 'ext_emconf.php', '');

		$packageManagerMock = $this->getMock(\TYPO3\CMS\Core\Package\PackageManager::class);
		$packageManagerMock->expects($this->any())->method('isPackageKeyValid')->willReturn(TRUE);
		$package = new Package($packageManagerMock, $packageKey, $packagePath);
		$this->assertEquals($packageKey, $package->getPackageKey());
	}

	/**
	 */
	public function invalidPackageKeys() {
		return array(
			array('TYPO3..Flow'),
			array('RobertLemke.Flow. Twitter'),
			array('Schalke*4')
		);
	}

	/**
	 * @test
	 * @dataProvider invalidPackageKeys
	 * @expectedException \TYPO3\CMS\Core\Package\Exception\InvalidPackageKeyException
	 */
	public function constructRejectsInvalidPackageKeys($packageKey) {
		$packagePath = 'vfs://Packages/' . str_replace('\\', '/', $packageKey) . '/';
		mkdir($packagePath, 0777, TRUE);

		$packageManagerMock = $this->getMock(\TYPO3\CMS\Core\Package\PackageManager::class);
		new Package($packageManagerMock, $packageKey, $packagePath);
	}

	/**
	 * @test
	 */
	public function getNamespaceReturnsThePhpNamespaceCorrespondingToThePackageKey() {
		$packagePath = 'vfs://Packages/Application/Acme.MyPackage/';
		mkdir($packagePath, 0777, TRUE);
		file_put_contents($packagePath . 'composer.json', '{"name": "acme/mypackage", "type": "flow-test"}');
		file_put_contents($packagePath . 'ext_emconf.php', '');

		$packageManagerMock = $this->getMock(\TYPO3\CMS\Core\Package\PackageManager::class);
		$packageManagerMock->expects($this->any())->method('isPackageKeyValid')->willReturn(TRUE);
		$package = new Package($packageManagerMock, 'Acme.MyPackage', $packagePath);
		$this->assertEquals('Acme\\MyPackage', $package->getNamespace());
	}

	/**
	 * @test
	 */
	public function getClassesPathReturnsPathToClasses() {
		$packageManagerMock = $this->getMock(\TYPO3\CMS\Core\Package\PackageManager::class);
		$packageManagerMock->expects($this->any())->method('isPackageKeyValid')->willReturn(TRUE);
		$package = new Package($packageManagerMock, 'core', PATH_typo3 . 'sysext/core/', Package::DIRECTORY_CLASSES);
		$packageClassesPath = $package->getClassesPath();
		$expected = $package->getPackagePath() . Package::DIRECTORY_CLASSES;
		$this->assertEquals($expected, $packageClassesPath);
	}

	/**
	 * @test
	 */
	public function aPackageCanBeFlaggedAsProtected() {
		$packagePath = 'vfs://Packages/Application/Vendor/Dummy/';
		mkdir($packagePath, 0700, TRUE);
		file_put_contents($packagePath . 'composer.json', '{"name": "vendor/dummy", "type": "flow-test"}');
		file_put_contents($packagePath . 'ext_emconf.php', '');

		$packageManagerMock = $this->getMock(\TYPO3\CMS\Core\Package\PackageManager::class);
		$packageManagerMock->expects($this->any())->method('isPackageKeyValid')->willReturn(TRUE);
		$package = new Package($packageManagerMock, 'Vendor.Dummy', $packagePath);

		$this->assertFalse($package->isProtected());
		$package->setProtected(TRUE);
		$this->assertTrue($package->isProtected());
	}

}
