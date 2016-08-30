<?php
namespace TYPO3\CMS\Core\Tests\Unit\Core;

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

use Composer\Autoload\ClassLoader;
use TYPO3\CMS\Core\Core\ClassLoadingInformationGenerator;
use TYPO3\CMS\Core\Package\PackageInterface;
use TYPO3\CMS\Core\Tests\UnitTestCase;

/**
 * Testcase for the ClassLoadingInformationGenerator class
 */
class ClassLoadingInformationGeneratorTest extends UnitTestCase
{
    /**
     * @test
     * @expectedException \TYPO3\CMS\Core\Error\Exception
     * @expectedExceptionCode 1444142481
     */
    public function buildClassAliasMapForPackageThrowsExceptionForWrongComposerManifestInformation()
    {
        $packageMock = $this->createPackageMock([
            'extra' => [
                'typo3/class-alias-loader' => [
                    'class-alias-maps' => [
                        'foo' => Fixtures\test_extension\Resources\PHP\Test::class,
                        'bar' => Fixtures\test_extension\Resources\PHP\AnotherTestFile::class,
                    ],
                ],
            ],
        ]);
        /** @var ClassLoader|\PHPUnit_Framework_MockObject_MockObject $classLoaderMock */
        $classLoaderMock = $this->getMock(ClassLoader::class);
        $generator = new ClassLoadingInformationGenerator($classLoaderMock, [], __DIR__);
        $generator->buildClassAliasMapForPackage($packageMock);
    }

    /**
     * @test
     * @expectedException \TYPO3\CMS\Core\Error\Exception
     * @expectedExceptionCode 1422625075
     */
    public function buildClassAliasMapForPackageThrowsExceptionForWrongClassAliasMapFile()
    {
        $packageMock = $this->createPackageMock([
            'extra' => [
                'typo3/class-alias-loader' => [
                    'class-alias-maps' => [
                        'Migrations/Code/WrongClassAliasMap.php',
                    ],
                ],
            ],
        ]);
        /** @var ClassLoader|\PHPUnit_Framework_MockObject_MockObject $classLoaderMock */
        $classLoaderMock = $this->getMock(ClassLoader::class);
        $generator = new ClassLoadingInformationGenerator($classLoaderMock, [], __DIR__);
        $generator->buildClassAliasMapForPackage($packageMock);
    }

    /**
     * @test
     */
    public function buildClassAliasMapForPackageReturnsClassAliasMapForClassAliasMapFile()
    {
        $expectedClassMap = [
            'aliasToClassNameMapping' => [
                'foo' => Fixtures\test_extension\Resources\PHP\Test::class,
                'bar' => Fixtures\test_extension\Resources\PHP\AnotherTestFile::class,
            ],
            'classNameToAliasMapping' => [
                Fixtures\test_extension\Resources\PHP\Test::class => [
                    'foo' => 'foo',
                ],
                Fixtures\test_extension\Resources\PHP\AnotherTestFile::class => [
                    'bar' => 'bar',
                ]
            ],
        ];
        $packageMock = $this->createPackageMock([]);
        /** @var ClassLoader|\PHPUnit_Framework_MockObject_MockObject $classLoaderMock */
        $classLoaderMock = $this->getMock(ClassLoader::class);
        $generator = new ClassLoadingInformationGenerator($classLoaderMock, [], __DIR__);
        $this->assertEquals($expectedClassMap, $generator->buildClassAliasMapForPackage($packageMock));
    }

    /**
     * @test
     */
    public function buildClassAliasMapForPackageReturnsClassAliasMapForComposerManifestInformation()
    {
        $expectedClassMap = [
            'aliasToClassNameMapping' => [
                'foo' => Fixtures\test_extension\Resources\PHP\Test::class,
                'bar' => Fixtures\test_extension\Resources\PHP\AnotherTestFile::class,
            ],
            'classNameToAliasMapping' => [
                Fixtures\test_extension\Resources\PHP\Test::class => [
                    'foo' => 'foo',
                ],
                Fixtures\test_extension\Resources\PHP\AnotherTestFile::class => [
                    'bar' => 'bar',
                ]
            ],
        ];
        $packageMock = $this->createPackageMock([
            'extra' => [
                'typo3/class-alias-loader' => [
                    'class-alias-maps' => [
                        'Resources/PHP/ClassAliasMap.php',
                    ],
                ],
            ],
        ]);
        /** @var ClassLoader|\PHPUnit_Framework_MockObject_MockObject $classLoaderMock */
        $classLoaderMock = $this->getMock(ClassLoader::class);
        $generator = new ClassLoadingInformationGenerator($classLoaderMock, [], __DIR__);
        $this->assertEquals($expectedClassMap, $generator->buildClassAliasMapForPackage($packageMock));
    }

    /**
     * Data provider for different autoload information
     *
     * @return array
     */
    public function autoloadFilesAreBuildCorrectlyDataProvider()
    {
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
            'Psr-4 section with array' => [
                [
                    'autoload' => [
                        'psr-4' => [
                            'TYPO3\\CMS\\TestExtension\\' => ['Classes/', 'Resources/PHP/'],
                        ],
                    ],
                ],
                [
                    '\'TYPO3\\\\CMS\\\\TestExtension\\\\\' => array($typo3InstallDir . \'/Fixtures/test_extension/Classes\',$typo3InstallDir . \'/Fixtures/test_extension/Resources/PHP\')',
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
            'No autoload section' => [
                [],
                [],
                [
                    '!$typo3InstallDir . \'/Fixtures/test_extension/Resources/PHP/Test.php\'',
                    '!$typo3InstallDir . \'/Fixtures/test_extension/Tests/TestClass.php\'',
                    '!$typo3InstallDir . \'/Fixtures/test_extension/Resources/PHP/AnotherTestFile.php\'',
                    '!$typo3InstallDir . \'/Fixtures/test_extension/Resources/PHP/Subdirectory/SubdirectoryTest.php\'',
                    '!$typo3InstallDir . \'/Fixtures/test_extension/class.ext_update.php\'',
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
    public function autoloadFilesAreBuildCorrectly($packageManifest, $expectedPsr4Files, $expectedClassMapFiles)
    {
        /** @var ClassLoader|\PHPUnit_Framework_MockObject_MockObject $classLoaderMock */
        $classLoaderMock = $this->getMock(ClassLoader::class);
        $generator = new ClassLoadingInformationGenerator($classLoaderMock, [$this->createPackageMock($packageManifest)], __DIR__);
        $files = $generator->buildAutoloadInformationFiles();

        $this->assertArrayHasKey('psr-4File', $files);
        $this->assertArrayHasKey('classMapFile', $files);
        foreach ($expectedPsr4Files as $expectation) {
            if ($expectation[0] === '!') {
                $expectedCount = 0;
                $expectation = substr($expectation, 1);
                $message = sprintf('File "%s" is NOT expected to be in psr-4, but is.', $expectation);
            } else {
                $expectedCount = 1;
                $message = sprintf('File "%s" is expected to be in psr-4, but is not.', $expectation);
            }
            $this->assertSame($expectedCount, substr_count($files['psr-4File'], $expectation), $message);
        }
        foreach ($expectedClassMapFiles as $expectation) {
            if ($expectation[0] === '!') {
                $expectedCount = 0;
                $expectation = substr($expectation, 1);
                $message = sprintf('File "%s" is NOT expected to be in class map, but is.', $expectation);
            } else {
                $expectedCount = 1;
                $message = sprintf('File "%s" is expected to be in class map, but is not.', $expectation);
            }
            $this->assertSame($expectedCount, substr_count($files['classMapFile'], $expectation), $message);
        }
    }

    /**
     * Data provider for different autoload information
     *
     * @return array
     */
    public function autoloadDevFilesAreBuildCorrectlyDataProvider()
    {
        return [
            'Psr-4 sections' => [
                [
                    'autoload' => [
                        'psr-4' => [
                            'TYPO3\\CMS\\TestExtension\\' => 'Classes',
                        ],
                    ],
                    'autoload-dev' => [
                        'psr-4' => [
                            'TYPO3\\CMS\\TestExtension\\Tests\\' => 'Tests',
                        ],
                    ],
                ],
                [
                    '\'TYPO3\\\\CMS\\\\TestExtension\\\\\' => array($typo3InstallDir . \'/Fixtures/test_extension/Classes\')',
                    '\'TYPO3\\\\CMS\\\\TestExtension\\\\Tests\\\\\' => array($typo3InstallDir . \'/Fixtures/test_extension/Tests\')',
                ],
                [],
            ],
            'Psr-4 sections with override' => [
                [
                    'autoload' => [
                        'psr-4' => [
                            'TYPO3\\CMS\\TestExtension\\' => 'Classes',
                        ],
                    ],
                    'autoload-dev' => [
                        'psr-4' => [
                            'TYPO3\\CMS\\TestExtension\\' => 'Tests',
                        ],
                    ],
                ],
                [
                    '!\'TYPO3\\\\CMS\\\\TestExtension\\\\\' => array($typo3InstallDir . \'/Fixtures/test_extension/Classes\')',
                    '\'TYPO3\\\\CMS\\\\TestExtension\\\\\' => array($typo3InstallDir . \'/Fixtures/test_extension/Tests\')',
                ],
                [],
            ],
            'Classmap section pointing to two files, one in dev and one not' => [
                [
                    'autoload' => [
                        'classmap' => [
                            'Resources/PHP/Test.php',
                        ],
                    ],
                    'autoload-dev' => [
                        'classmap' => [
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
     * @dataProvider autoloadDevFilesAreBuildCorrectlyDataProvider
     *
     * @param array $packageManifest
     * @param array $expectedPsr4Files
     * @param array $expectedClassMapFiles
     */
    public function autoloadDevFilesAreBuildCorrectly($packageManifest, $expectedPsr4Files, $expectedClassMapFiles)
    {
        /** @var ClassLoader|\PHPUnit_Framework_MockObject_MockObject $classLoaderMock */
        $classLoaderMock = $this->getMock(ClassLoader::class);
        $generator = new ClassLoadingInformationGenerator($classLoaderMock, [$this->createPackageMock($packageManifest)], __DIR__, true);
        $files = $generator->buildAutoloadInformationFiles();

        $this->assertArrayHasKey('psr-4File', $files);
        $this->assertArrayHasKey('classMapFile', $files);
        foreach ($expectedPsr4Files as $expectation) {
            if ($expectation[0] === '!') {
                $expectedCount = 0;
            } else {
                $expectedCount = 1;
            }
            $this->assertSame($expectedCount, substr_count($files['psr-4File'], $expectation), '' . $expectation);
        }
        foreach ($expectedClassMapFiles as $expectation) {
            if ($expectation[0] === '!') {
                $expectedCount = 0;
            } else {
                $expectedCount = 1;
            }
            $this->assertSame($expectedCount, substr_count($files['classMapFile'], $expectation), '' . $expectation);
        }
    }

    /**
     * @param array Array which should be returned as composer manifest
     * @return PackageInterface
     */
    protected function createPackageMock($packageManifest)
    {
        $packageMock = $this->getMock(PackageInterface::class);
        $packageMock->expects($this->any())->method('getPackagePath')->willReturn(__DIR__ . '/Fixtures/test_extension/');
        $packageMock->expects($this->any())->method('getValueFromComposerManifest')->willReturn(
            json_decode(json_encode($packageManifest))
        );

        return $packageMock;
    }
}
