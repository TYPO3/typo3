<?php

declare(strict_types=1);

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

namespace TYPO3\CMS\Core\Tests\Unit\Core;

use Composer\Autoload\ClassLoader;
use TYPO3\CMS\Core\Core\ClassLoadingInformationGenerator;
use TYPO3\CMS\Core\Error\Exception;
use TYPO3\CMS\Core\Package\PackageInterface;
use TYPO3\CMS\Core\Tests\Unit\Core\Fixtures\test_extension\Resources\PHP\AnotherTestFile;
use TYPO3\CMS\Core\Tests\Unit\Core\Fixtures\test_extension\Resources\PHP\TestFile;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class ClassLoadingInformationGeneratorTest extends UnitTestCase
{
    /**
     * @test
     */
    public function buildClassAliasMapForPackageThrowsExceptionForWrongComposerManifestInformation(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionCode(1444142481);

        $packageMock = $this->createPackageMock([
            'extra' => [
                'typo3/class-alias-loader' => [
                    'class-alias-maps' => [
                        'foo' => TestFile::class,
                        'bar' => AnotherTestFile::class,
                    ],
                ],
            ],
        ]);
        $classLoaderMock = $this->createMock(ClassLoader::class);
        $generator = new ClassLoadingInformationGenerator($classLoaderMock, [], __DIR__);
        $generator->buildClassAliasMapForPackage($packageMock);
    }

    /**
     * @test
     */
    public function buildClassAliasMapForPackageThrowsExceptionForWrongClassAliasMapFile(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionCode(1422625075);

        $packageMock = $this->createPackageMock([
            'extra' => [
                'typo3/class-alias-loader' => [
                    'class-alias-maps' => [
                        'Migrations/Code/WrongClassAliasMap.php',
                    ],
                ],
            ],
        ]);
        $classLoaderMock = $this->createMock(ClassLoader::class);
        $generator = new ClassLoadingInformationGenerator($classLoaderMock, [], __DIR__);
        $generator->buildClassAliasMapForPackage($packageMock);
    }

    /**
     * @test
     */
    public function buildClassAliasMapForPackageReturnsClassAliasMapForClassAliasMapFile(): void
    {
        $expectedClassMap = [
            'aliasToClassNameMapping' => [
                'foo' => TestFile::class,
                'bar' => AnotherTestFile::class,
            ],
            'classNameToAliasMapping' => [
                TestFile::class => [
                    'foo' => 'foo',
                ],
                AnotherTestFile::class => [
                    'bar' => 'bar',
                ],
            ],
        ];
        $packageMock = $this->createPackageMock([]);
        $classLoaderMock = $this->createMock(ClassLoader::class);
        $generator = new ClassLoadingInformationGenerator($classLoaderMock, [], __DIR__);
        self::assertEquals($expectedClassMap, $generator->buildClassAliasMapForPackage($packageMock));
    }

    /**
     * @test
     */
    public function buildClassAliasMapForPackageReturnsClassAliasMapForComposerManifestInformation(): void
    {
        $expectedClassMap = [
            'aliasToClassNameMapping' => [
                'foo' => TestFile::class,
                'bar' => AnotherTestFile::class,
            ],
            'classNameToAliasMapping' => [
                TestFile::class => [
                    'foo' => 'foo',
                ],
                AnotherTestFile::class => [
                    'bar' => 'bar',
                ],
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
        $classLoaderMock = $this->createMock(ClassLoader::class);
        $generator = new ClassLoadingInformationGenerator($classLoaderMock, [], __DIR__);
        self::assertEquals($expectedClassMap, $generator->buildClassAliasMapForPackage($packageMock));
    }

    public static function autoloadFilesAreBuildCorrectlyDataProvider(): array
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
                    '$typo3InstallDir . \'/Fixtures/test_extension/Resources/PHP/TestFile.php\'',
                    '$typo3InstallDir . \'/Fixtures/test_extension/Resources/PHP/AnotherTestFile.php\'',
                    '$typo3InstallDir . \'/Fixtures/test_extension/Resources/PHP/Subdirectory/SubdirectoryTestFile.php\'',
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
                    '$typo3InstallDir . \'/Fixtures/test_extension/Resources/PHP/TestFile.php\'',
                    '$typo3InstallDir . \'/Fixtures/test_extension/Resources/PHP/AnotherTestFile.php\'',
                    '$typo3InstallDir . \'/Fixtures/test_extension/Resources/PHP/Subdirectory/SubdirectoryTestFile.php\'',
                ],
            ],
            'Classmap section pointing to a file' => [
                [
                    'autoload' => [
                        'classmap' => [
                            'Resources/PHP/TestFile.php',
                        ],
                    ],
                ],
                [],
                [
                    '$typo3InstallDir . \'/Fixtures/test_extension/Resources/PHP/TestFile.php\'',
                    '!$typo3InstallDir . \'/Fixtures/test_extension/Resources/PHP/AnotherTestFile.php\'',
                    '!$typo3InstallDir . \'/Fixtures/test_extension/Resources/PHP/Subdirectory/SubdirectoryTestFile.php\'',
                ],
            ],
            'No autoload section' => [
                [],
                [],
                [
                    '!$typo3InstallDir . \'/Fixtures/test_extension/Resources/PHP/TestFile.php\'',
                    '!$typo3InstallDir . \'/Fixtures/test_extension/Tests/TestClass.php\'',
                    '!$typo3InstallDir . \'/Fixtures/test_extension/Resources/PHP/AnotherTestFile.php\'',
                    '!$typo3InstallDir . \'/Fixtures/test_extension/Resources/PHP/Subdirectory/SubdirectoryTestFile.php\'',
                    '!$typo3InstallDir . \'/Fixtures/test_extension/class.ext_update.php\'',
                ],
            ],
            'Classmap section pointing to two files' => [
                [
                    'autoload' => [
                        'classmap' => [
                            'Resources/PHP/TestFile.php',
                            'Resources/PHP/AnotherTestFile.php',
                        ],
                    ],
                ],
                [],
                [
                    '$typo3InstallDir . \'/Fixtures/test_extension/Resources/PHP/TestFile.php\'',
                    '$typo3InstallDir . \'/Fixtures/test_extension/Resources/PHP/AnotherTestFile.php\'',
                    '!$typo3InstallDir . \'/Fixtures/test_extension/Resources/PHP/Subdirectory/SubdirectoryTestFile.php\'',
                ],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider autoloadFilesAreBuildCorrectlyDataProvider
     */
    public function autoloadFilesAreBuildCorrectly(array $packageManifest, array $expectedPsr4Files, array $expectedClassMapFiles): void
    {
        $classLoaderMock = $this->createMock(ClassLoader::class);
        $generator = new ClassLoadingInformationGenerator($classLoaderMock, [$this->createPackageMock($packageManifest)], __DIR__);
        $files = $generator->buildAutoloadInformationFiles();

        self::assertArrayHasKey('psr-4File', $files);
        self::assertArrayHasKey('classMapFile', $files);
        foreach ($expectedPsr4Files as $expectation) {
            if ($expectation[0] === '!') {
                $expectedCount = 0;
                $expectation = substr($expectation, 1);
                $message = sprintf('File "%s" is NOT expected to be in psr-4, but is.', $expectation);
            } else {
                $expectedCount = 1;
                $message = sprintf('File "%s" is expected to be in psr-4, but is not.', $expectation);
            }
            self::assertSame($expectedCount, substr_count($files['psr-4File'], $expectation), $message);
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
            self::assertSame($expectedCount, substr_count($files['classMapFile'], $expectation), $message);
        }
    }

    public static function autoloadDevFilesAreBuildCorrectlyDataProvider(): array
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
                            'Resources/PHP/TestFile.php',
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
                    '$typo3InstallDir . \'/Fixtures/test_extension/Resources/PHP/TestFile.php\'',
                    '$typo3InstallDir . \'/Fixtures/test_extension/Resources/PHP/AnotherTestFile.php\'',
                    '!$typo3InstallDir . \'/Fixtures/test_extension/Resources/PHP/Subdirectory/SubdirectoryTestFile.php\'',
                ],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider autoloadDevFilesAreBuildCorrectlyDataProvider
     */
    public function autoloadDevFilesAreBuildCorrectly(array $packageManifest, array $expectedPsr4Files, array $expectedClassMapFiles): void
    {
        $classLoaderMock = $this->createMock(ClassLoader::class);
        $generator = new ClassLoadingInformationGenerator($classLoaderMock, [$this->createPackageMock($packageManifest)], __DIR__, true);
        $files = $generator->buildAutoloadInformationFiles();

        self::assertArrayHasKey('psr-4File', $files);
        self::assertArrayHasKey('classMapFile', $files);
        foreach ($expectedPsr4Files as $expectation) {
            if ($expectation[0] === '!') {
                $expectedCount = 0;
            } else {
                $expectedCount = 1;
            }
            self::assertSame($expectedCount, substr_count($files['psr-4File'], $expectation), '' . $expectation);
        }
        foreach ($expectedClassMapFiles as $expectation) {
            if ($expectation[0] === '!') {
                $expectedCount = 0;
            } else {
                $expectedCount = 1;
            }
            self::assertSame($expectedCount, substr_count($files['classMapFile'], $expectation), '' . $expectation);
        }
    }

    private function createPackageMock(array $packageManifest): PackageInterface
    {
        $packageMock = $this->createMock(PackageInterface::class);
        $packageMock->method('getPackagePath')->willReturn(__DIR__ . '/Fixtures/test_extension/');
        $packageMock->method('getValueFromComposerManifest')->willReturn(
            json_decode(json_encode($packageManifest))
        );

        return $packageMock;
    }
}
