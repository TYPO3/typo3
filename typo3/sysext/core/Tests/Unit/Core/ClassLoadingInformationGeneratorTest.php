<?php
namespace TYPO3\CMS\Core\Tests\Unit\Core;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use Composer\Autoload\ClassLoader;
use TYPO3\CMS\Core\Core\ClassLoadingInformationGenerator;
use TYPO3\CMS\Core\Package\PackageInterface;
use TYPO3\CMS\Core\Tests\UnitTestCase;


/**
 * Testcase for the ClassLoadingInformationGenerator class
 */
class ClassLoadingInformationGeneratorTest extends UnitTestCase {

	/**
	 * Data provider with different class names.
	 *
	 * @return array
	 */
	public function isIgnoredClassNameIgnoresTestClassesDataProvider() {
		return array(
			'FoTest' => array('FoTest', TRUE),
			'FoLowercasetest' => array('FoLowercasetest', FALSE),
			'DifferentClassTes' => array('DifferentClassTes', FALSE),
			'Test' => array('Test', TRUE),
			'FoFixture' => array('FoFixture', TRUE),
			'FoLowercasefixture' => array('FoLowercasefixture', FALSE),
			'DifferentClassFixtur' => array('DifferentClassFixtur', FALSE),
			'Fixture' => array('Fixture', TRUE),
			'Latest' => array('Latest', FALSE),
			'LaTest' => array('LaTest', TRUE),
			'Tx_RedirectTest_Domain_Model_Test' => array('Tx_RedirectTest_Domain_Model_Test', FALSE),
		);
	}

	/**
	 * @test
	 * @dataProvider isIgnoredClassNameIgnoresTestClassesDataProvider
	 *
	 * @param string $className
	 * @param bool $expectedResult
	 */
	public function isIgnoredClassNameIgnoresTestClasses($className, $expectedResult) {
		$generator = $this->getAccessibleMock(
			ClassLoadingInformationGenerator::class,
			['dummy'],
			[$this->getMock(ClassLoader::class), $this->createPackagesMock(array()), __DIR__]
		);

		$this->assertEquals($expectedResult, $generator->_call('isIgnoredClassName', $className));
	}

	/**
	 * Data provider for different autoload information
	 *
	 * @return array
	 */
	public function autoloadFilesAreBuildCorrectlyDataProvider() {
		return [
			'Psr-4 section' => [
				[
					'autoload' => [
						'psr-4' => [
							'TYPO3\\CMS\\TestExtension\\' => 'Classes/',
						],
					],
				],
				[
					'\'TYPO3\\\\CMS\\\\TestExtension\\\\\' => array($typo3InstallDir . \'/Fixtures/test_extension/Classes\')',
				],
				[],
			],
			'Psr-4 section without trailing slash' => [
				[
					'autoload' => [
						'psr-4' => [
							'TYPO3\\CMS\\TestExtension\\' => 'Classes',
						],
					],
				],
				[
					'\'TYPO3\\\\CMS\\\\TestExtension\\\\\' => array($typo3InstallDir . \'/Fixtures/test_extension/Classes\')',
				],
				[],
			],
			'Classmap section' => [
				[
					'autoload' => [
						'classmap' => [
							'Resources/PHP/',
						],
					],
				],
				[],
				[
					'$typo3InstallDir . \'/Fixtures/test_extension/Resources/PHP/Test.php\'',
					'$typo3InstallDir . \'/Fixtures/test_extension/Resources/PHP/AnotherTestFile.php\'',
					'$typo3InstallDir . \'/Fixtures/test_extension/Resources/PHP/Subdirectory/SubdirectoryTest.php\'',
				],
			],
			'Classmap section without trailing slash' => [
				[
					'autoload' => [
						'classmap' => [
							'Resources/PHP',
						],
					],
				],
				[],
				[
					'$typo3InstallDir . \'/Fixtures/test_extension/Resources/PHP/Test.php\'',
					'$typo3InstallDir . \'/Fixtures/test_extension/Resources/PHP/AnotherTestFile.php\'',
					'$typo3InstallDir . \'/Fixtures/test_extension/Resources/PHP/Subdirectory/SubdirectoryTest.php\'',
				],
			],
			'Classmap section pointing to a file' => [
				[
					'autoload' => [
						'classmap' => [
							'Resources/PHP/Test.php',
						],
					],
				],
				[],
				[
					'$typo3InstallDir . \'/Fixtures/test_extension/Resources/PHP/Test.php\'',
					'!$typo3InstallDir . \'/Fixtures/test_extension/Resources/PHP/AnotherTestFile.php\'',
					'!$typo3InstallDir . \'/Fixtures/test_extension/Resources/PHP/Subdirectory/SubdirectoryTest.php\'',
				],
			],
			'Classmap section pointing to two files' => [
				[
					'autoload' => [
						'classmap' => [
							'Resources/PHP/Test.php',
							'Resources/PHP/AnotherTestFile.php',
						],
					],
				],
				[],
				[
					'$typo3InstallDir . \'/Fixtures/test_extension/Resources/PHP/Test.php\'',
					'$typo3InstallDir . \'/Fixtures/test_extension/Resources/PHP/AnotherTestFile.php\'',
					'!$typo3InstallDir . \'/Fixtures/test_extension/Resources/PHP/Subdirectory/SubdirectoryTest.php\'',
				],
			],
		];
	}

	/**
	 * @test
	 * @dataProvider autoloadFilesAreBuildCorrectlyDataProvider
	 *
	 * @param string $packageManifest
	 * @param array $expectedPsr4Files
	 * @param array $expectedClassMapFiles
	 */
	public function autoloadFilesAreBuildCorrectly($packageManifest, $expectedPsr4Files, $expectedClassMapFiles) {
		/** @var ClassLoader|\PHPUnit_Framework_MockObject_MockObject $classLoaderMock */
		$classLoaderMock = $this->getMock(ClassLoader::class);
		$generator = new ClassLoadingInformationGenerator($classLoaderMock, $this->createPackagesMock($packageManifest), __DIR__);
		$files = $generator->buildAutoloadInformationFiles();

		$this->assertArrayHasKey('psr-4File', $files);
		$this->assertArrayHasKey('classMapFile', $files);
		foreach ($expectedPsr4Files as $expectation) {
			if ($expectation[0] === '!') {
				$expectedCount = 0;
			} else {
				$expectedCount = 1;
			}
			$this->assertSame($expectedCount, substr_count($files['psr-4File'], $expectation));
		}
		foreach ($expectedClassMapFiles as $expectation) {
			if ($expectation[0] === '!') {
				$expectedCount = 0;
			} else {
				$expectedCount = 1;
			}
			$this->assertSame($expectedCount, substr_count($files['classMapFile'], $expectation));
		}
	}

	/**
	 * @param array Array which should be returned as composer manifest
	 * @return PackageInterface[]
	 */
	protected function createPackagesMock($packageManifest) {
		$packageStub = $this->getMock(PackageInterface::class);
		$packageStub->expects($this->any())->method('getPackagePath')->willReturn(__DIR__ . '/Fixtures/test_extension/');
		$packageStub->expects($this->any())->method('getValueFromComposerManifest')->willReturn(
			json_decode(json_encode($packageManifest))
		);

		return [$packageStub];
	}

}
