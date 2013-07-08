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

use TYPO3\Flow\Package\Package as FlowPackage;
use org\bovigo\vfs\vfsStream;

/**
 * Testcase for the package class
 *
 */
class PackageTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 */
	public function setUp() {
		vfsStream::setup('Packages');
	}

	/**
	 * @test
	 * @expectedException \TYPO3\Flow\Package\Exception\InvalidPackagePathException
	 */
	public function constructThrowsPackageDoesNotExistException() {
		new FlowPackage($this->getMock('TYPO3\CMS\Core\Package\PackageManager'), 'Vendor.TestPackage', './ThisPackageSurelyDoesNotExist');
	}

	/**
	 */
	public function validPackageKeys() {
		return array(
			array('Doctrine.DBAL'),
			array('TYPO3.Flow'),
			array('RobertLemke.Flow.Twitter'),
			array('Sumphonos.Stem'),
			array('Schalke04.Soccer.MagicTrainer')
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

		$package = new FlowPackage($this->getMock('TYPO3\CMS\Core\Package\PackageManager'), $packageKey, $packagePath);
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
	 * @expectedException \TYPO3\Flow\Package\Exception\InvalidPackageKeyException
	 */
	public function constructRejectsInvalidPackageKeys($packageKey) {
		$packagePath = 'vfs://Packages/' . str_replace('\\', '/', $packageKey) . '/';
		mkdir($packagePath, 0777, TRUE);
		new FlowPackage($this->getMock('TYPO3\CMS\Core\Package\PackageManager'), $packageKey, $packagePath);
	}

	/**
	 * @test
	 */
	public function getNamespaceReturnsThePhpNamespaceCorrespondingToThePackageKey() {
		$packagePath = 'vfs://Packages/Application/Acme.MyPackage/';
		mkdir($packagePath, 0777, TRUE);
		file_put_contents($packagePath . 'composer.json', '{"name": "acme/mypackage", "type": "flow-test"}');
		$package = new FlowPackage($this->getMock('TYPO3\CMS\Core\Package\PackageManager'), 'Acme.MyPackage', $packagePath);
		$this->assertEquals('Acme\\MyPackage', $package->getNamespace());
	}

	/**
	 * @test
	 */
	public function getMetaPathReturnsPathToMetaDirectory() {
		$package = new FlowPackage($this->getMock('TYPO3\CMS\Core\Package\PackageManager'), 'TYPO3.CMS.Core', PATH_typo3 . 'sysext/core/');
		$packageMetaDataPath = $package->getMetaPath();
		$this->assertSame($package->getPackagePath() . FlowPackage::DIRECTORY_METADATA, $packageMetaDataPath);
	}

	/**
	 * @test
	 */
	public function getDocumentationPathReturnsPathToDocumentationDirectory() {
		$package = new FlowPackage($this->getMock('TYPO3\CMS\Core\Package\PackageManager'), 'TYPO3.CMS.Core', PATH_typo3 . 'sysext/core/');
		$packageDocumentationPath = $package->getDocumentationPath();

		$this->assertEquals($package->getPackagePath() . FlowPackage::DIRECTORY_DOCUMENTATION, $packageDocumentationPath);
	}

	/**
	 * @test
	 */
	public function getClassesPathReturnsPathToClasses() {
		$package = new FlowPackage($this->getMock('TYPO3\CMS\Core\Package\PackageManager'), 'TYPO3.CMS.Core', PATH_typo3 . 'sysext/core/', FlowPackage::DIRECTORY_CLASSES);
		$packageClassesPath = $package->getClassesPath();
		$expected = $package->getPackagePath() . FlowPackage::DIRECTORY_CLASSES;
		$this->assertEquals($expected, $packageClassesPath);
	}

	/**
	 * @test
	 */
	public function getClassesPathReturnsNormalizedPathToClasses() {
		$packagePath = 'vfs://Packages/Application/Acme/MyPackage/';
		mkdir($packagePath, 0777, TRUE);
		file_put_contents($packagePath . 'composer.json', '{"name": "acme/mypackage", "type": "flow-test"}');

		$package = new FlowPackage($this->getMock('TYPO3\CMS\Core\Package\PackageManager'), 'Acme.MyPackage', $packagePath, 'no/trailing/slash');

		$packageClassesPath = $package->getClassesPath();
		$expected = $package->getPackagePath() . 'no/trailing/slash/';

		$this->assertEquals($expected, $packageClassesPath);
	}

	/**
	 * @test
	 */
	public function getPackageDocumentationsReturnsEmptyArrayIfDocumentationDirectoryDoesntExist() {
		vfsStream::setup('testDirectory');

		$packagePath = vfsStream::url('testDirectory') . '/';
		file_put_contents($packagePath . 'composer.json', '{"name": "typo3/flow", "type": "flow-test"}');

		$package = new FlowPackage($this->getMock('TYPO3\CMS\Core\Package\PackageManager'), 'TYPO3.Flow', $packagePath);
		$documentations = $package->getPackageDocumentations();

		$this->assertEquals(array(), $documentations);
	}

	/**
	 * @test
	 */
	public function aPackageCanBeFlaggedAsProtected() {
		$packagePath = 'vfs://Packages/Application/Vendor/Dummy/';
		mkdir($packagePath, 0700, TRUE);
		file_put_contents($packagePath . 'composer.json', '{"name": "vendor/dummy", "type": "flow-test"}');
		$package = new FlowPackage($this->getMock('TYPO3\CMS\Core\Package\PackageManager'), 'Vendor.Dummy', $packagePath);

		$this->assertFalse($package->isProtected());
		$package->setProtected(TRUE);
		$this->assertTrue($package->isProtected());
	}

	/**
	 * @test
	 */
	public function isObjectManagementEnabledTellsIfObjectManagementShouldBeEnabledForThePackage() {
		$packagePath = 'vfs://Packages/Application/Vendor/Dummy/';
		mkdir($packagePath, 0700, TRUE);
		file_put_contents($packagePath . 'composer.json', '{"name": "vendor/dummy", "type": "flow-test"}');
		$package = new FlowPackage($this->getMock('TYPO3\CMS\Core\Package\PackageManager'), 'Vendor.Dummy', $packagePath);

		$this->assertTrue($package->isObjectManagementEnabled());
	}

	/**
	 * @test
	 */
	public function getClassFilesReturnsAListOfClassFilesOfThePackage() {
		$packagePath = 'vfs://Packages/Application/Acme.MyPackage/';
		mkdir($packagePath, 0777, TRUE);
		file_put_contents($packagePath . 'composer.json', '{"name": "acme/mypackage", "type": "flow-test"}');

		mkdir($packagePath . 'Classes/Acme/MyPackage/Controller', 0770, TRUE);
		mkdir($packagePath . 'Classes/Acme/MyPackage/Domain/Model', 0770, TRUE);

		file_put_contents($packagePath . 'Classes/Acme/MyPackage/Controller/FooController.php', '');
		file_put_contents($packagePath . 'Classes/Acme/MyPackage/Domain/Model/Foo.php', '');
		file_put_contents($packagePath . 'Classes/Acme/MyPackage/Domain/Model/Bar.php', '');

		$expectedClassFilesArray = array(
			'Acme\MyPackage\Controller\FooController' => 'Classes/Acme/MyPackage/Controller/FooController.php',
			'Acme\MyPackage\Domain\Model\Foo' => 'Classes/Acme/MyPackage/Domain/Model/Foo.php',
			'Acme\MyPackage\Domain\Model\Bar' => 'Classes/Acme/MyPackage/Domain/Model/Bar.php',
		);

		$package = new FlowPackage($this->getMock('TYPO3\CMS\Core\Package\PackageManager'), 'Acme.MyPackage', $packagePath, 'Classes');
		$actualClassFilesArray = $package->getClassFiles();

		$this->assertEquals($expectedClassFilesArray, $actualClassFilesArray);
	}

}
