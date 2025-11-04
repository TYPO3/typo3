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

namespace TYPO3\CMS\Core\Tests\Functional\Configuration\Loader;

use PHPUnit\Framework\Attributes\Test;
use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use TYPO3\CMS\Core\Configuration\Loader\Exception\YamlParseException;
use TYPO3\CMS\Core\Configuration\Loader\YamlFileLoader;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class YamlFileLoaderTest extends FunctionalTestCase
{
    /**
     * Generic method to check if the load method returns an array from a YAML file
     */
    #[Test]
    public function load(): void
    {
        $fileName = 'EXT:core/Tests/Functional/Configuration/Loader/Fixtures/Berta.yaml';
        $expected = [
            'options' => [
                'option1',
                'option2',
            ],
            'betterthanbefore' => 1,
        ];
        $output = (new YamlFileLoader($this->createMock(LoggerInterface::class)))->load($fileName);
        self::assertSame($expected, $output);
    }

    /**
     * Check that an invalid YAML files triggers a YamlParseException
     */
    #[Test]
    public function loadEmptyYaml(): void
    {
        $this->expectException(YamlParseException::class);
        $this->expectExceptionCode(1497332874);
        $fileName = 'EXT:core/Tests/Functional/Configuration/Loader/Fixtures/InvalidYamlFiles/LoadEmptyYaml.yaml';
        (new YamlFileLoader($this->createMock(LoggerInterface::class)))->load($fileName);
    }

    /**
     * Check that an invalid YAML files triggers a YamlParseException
     */
    #[Test]
    public function loadInvalidYaml(): void
    {
        $this->expectException(YamlParseException::class);
        $this->expectExceptionCode(1740817000);
        $fileName = 'EXT:core/Tests/Functional/Configuration/Loader/Fixtures/InvalidYamlFiles/LoadInvalidYaml.yaml';
        (new YamlFileLoader($this->createMock(LoggerInterface::class)))->load($fileName);
    }

    /**
     * Method checking for imports that they have been processed properly
     */
    #[Test]
    public function loadWithAnImport(): void
    {
        $fileName = 'EXT:core/Tests/Functional/Configuration/Loader/Fixtures/LoadWithAnImport.yaml';
        $expected = [
            'options' => [
                'optionBefore',
                'option1',
                'option2',
            ],
            'betterthanbefore' => 1,
        ];
        $output = (new YamlFileLoader($this->createMock(LoggerInterface::class)))->load($fileName);
        self::assertSame($expected, $output);
    }

    /**
     * Method checking for multiple imports that they have been loaded in the right order
     */
    #[Test]
    public function loadWithMultipleImports(): void
    {
        $fileName = 'EXT:core/Tests/Functional/Configuration/Loader/Fixtures/LoadWithMultipleImports.yaml';
        $expected = [
            'options' => [
                'optionBefore',
                'optionAfterBefore',
                'option1',
                'option2',
            ],
            'betterthanbefore' => 1,
        ];
        $output = (new YamlFileLoader($this->createMock(LoggerInterface::class)))->load($fileName);
        self::assertSame($expected, $output);
    }

    /**
     * Method checking for imports that they have been processed properly
     */
    #[Test]
    public function loadWithImportAndRelativePaths(): void
    {
        $fileName = 'EXT:core/Tests/Functional/Configuration/Loader/Fixtures/LoadWithImportAndRelativeFiles.yaml';
        $output = (new YamlFileLoader($this->createMock(LoggerInterface::class)))->load($fileName);
        self::assertSame(
            [
                'enable' => [
                    'frontend' => false,
                    'json.api' => true,
                    'backend' => true,
                    'rest.api' => true,
                ],
            ],
            $output
        );
    }

    /**
     * Method checking for placeholders
     */
    #[Test]
    public function loadWithPlaceholders(): void
    {
        $fileName = 'EXT:core/Tests/Functional/Configuration/Loader/Fixtures/LoadWithPlaceholders.yaml';
        $output = (new YamlFileLoader($this->createMock(LoggerInterface::class)))->load($fileName);
        $expected = [
            'firstset' => [
                'myinitialversion' => 13,
            ],
            'options' => [
                'option1',
                'option2',
            ],
            'betterthanbefore' => 13,
            'muchbetterthanbefore' => 'some::option1::option',
        ];
        self::assertSame($expected, $output);
    }

    /**
     * Method checking for placeholders in keys
     */
    #[Test]
    public function loadWithPlaceholdersInKeys(): void
    {
        $fileName = 'EXT:core/Tests/Functional/Configuration/Loader/Fixtures/LoadWithPlaceholdersInKeys.yaml';
        $expected = [
            'firstset' => [
                'myinitialversion' => 13,
                'mysecondversion' => 12,
            ],
            'options' => [
                'option1',
                'option2',
            ],
            'mysteriousKey' => 'mysterious13',
            'betterthanbefore' => 13,
            'muchbetterthanbefore' => 'some::option1::option',
            'mysterious13' => 12,
            'bestVersion' => 13,
            'foo' => 'bar value',
        ];
        putenv('env=bestVersion');
        putenv('bar=foo');
        $output = (new YamlFileLoader($this->createMock(LoggerInterface::class)))->load($fileName);
        putenv('env');
        putenv('bar');
        self::assertSame($expected, $output);
    }

    /**
     * Method checking for placeholders in keys
     */
    #[Test]
    public function loadWihPlaceholdersInKeysResolvedKeyAlreadyExistingThrowsException(): void
    {
        $this->expectExceptionCode(1719316250);
        $this->expectExceptionMessage('Placeholder key "%env("bar")%" can not be substituted with "foo" because key already exists');
        $this->expectException(\UnexpectedValueException::class);
        $fileName = 'EXT:core/Tests/Functional/Configuration/Loader/Fixtures/LoadWihPlaceholdersInKeysResolvedKeyAlreadyExisting.yaml';
        putenv('bar=foo');
        (new YamlFileLoader($this->createMock(LoggerInterface::class)))->load($fileName);
        putenv('bar');
    }

    /**
     * Method checking for placeholders in keys
     */
    #[Test]
    public function loadWithUnresolvablePlaceholdersInKeysThrowsException(): void
    {
        $this->expectExceptionCode(1719672440);
        $this->expectExceptionMessage('Unresolvable placeholder key "%env("notset1")%" could not be substituted.');
        $this->expectException(\UnexpectedValueException::class);
        $fileName = 'EXT:core/Tests/Functional/Configuration/Loader/Fixtures/LoadWithUnresolvablePlaceholdersInKeys.yaml';
        putenv('env=bestVersion');
        putenv('bar=foo');
        (new YamlFileLoader($this->createMock(LoggerInterface::class)))->load($fileName);
        putenv('env');
        putenv('bar');
    }

    /**
     * Method checking for nested placeholders
     */
    #[Test]
    public function loadWithNestedPlaceholders(): void
    {
        $fileName = 'EXT:core/Tests/Functional/Configuration/Loader/Fixtures/LoadWithNestedPlaceholders.yaml';
        $expected = [
            'firstset' => [
                'myinitialversion' => 13,
            ],
            'options' => [
                'option1',
                'option2',
            ],
            'betterthanbefore' => 13,
        ];
        putenv('foo=%firstset.myinitialversion%');
        $output = (new YamlFileLoader($this->createMock(LoggerInterface::class)))->load($fileName);
        putenv('foo');
        self::assertSame($expected, $output);
    }

    /**
     * Method checking for imports with env vars that they have been processed properly
     */
    #[Test]
    public function loadWithImportAndEnvVars(): void
    {
        $loader = new YamlFileLoader($this->createMock(LoggerInterface::class));
        putenv('foo=barbaz');
        $output = $loader->load('EXT:core/Tests/Functional/Configuration/Loader/Fixtures/Env/Berta.yaml');
        putenv('foo');
        $expected = [
            'loadedWithEnvVars' => 1,
            'options' => [
                'optionBefore',
                'option1',
                'option2',
            ],
        ];
        self::assertSame($expected, $output);
    }

    /**
     * Method checking for imports with env vars that they have been processed properly
     */
    #[Test]
    public function loadWithEnvVarsSetToFalsyValuesReturnsTheseValues(): void
    {
        $loader = new YamlFileLoader($this->createMock(LoggerInterface::class));
        putenv('optionFalse=false');
        putenv('optionNull1=0');
        putenv('optionNull2="0"');
        putenv('optionNull3=');
        putenv('optionFilled=barbaz');
        putenv('optionEmptyString1=""');
        putenv('optionEmptyString2=\'\'');
        $output = $loader->load('EXT:core/Tests/Functional/Configuration/Loader/Fixtures/Env/NullAndFalse.yaml');
        putenv('optionFalse');
        putenv('optionNull1');
        putenv('optionNull2');
        putenv('optionNull3');
        putenv('optionFilled');
        putenv('optionEmptyString1');
        putenv('optionEmptyString2');
        $expected = [
            'options' => [
                'optionFalse' => 'false',
                'optionNull1' => '0',
                'optionNull2' => '"0"',
                'optionNull3' => '',
                'optionFilled' => 'barbaz',
                'optionEmptyString1' => '""',
                'optionEmptyString2' => '\'\'',
                'optionInvalidReference' => '%env("doesNotExist")%',
            ],
        ];
        self::assertSame($expected, $output);
    }

    /**
     * Method checking for imports with placeholder values in the file name
     */
    #[Test]
    public function loadWithImportAndPlaceholderInFileName(): void
    {
        $output = (new YamlFileLoader($this->createMock(LoggerInterface::class)))
            ->load('EXT:core/Tests/Functional/Configuration/Loader/Fixtures/Placeholder/Berta.yaml');
        $expected = [
            'loadedWithPlaceholder' => 1,
            'settings' => [
                'dynamicOption' => 'Foo',
            ],
        ];
        self::assertSame($expected, $output);
    }

    /**
     * Method checking for multiple imports via glob() call
     */
    #[Test]
    public function loadWithGlobbedImports(): void
    {
        $output = (new YamlFileLoader($this->createMock(LoggerInterface::class)))
            ->load('EXT:core/Tests/Functional/Configuration/Loader/Fixtures/LoadWithGlobbedImports.yaml');
        $expected = [
            'options' => [
                'optionBefore',
                'optionAfterBefore',
                'option1',
                'option2',
            ],
            'betterthanbefore' => 1,
        ];
        self::assertSame($expected, $output);
    }

    /**
     * Method checking for multiple imports with numeric keys
     */
    #[Test]
    public function loadImportsWithNumericKeys(): void
    {
        $output = (new YamlFileLoader($this->createMock(LoggerInterface::class)))
            ->load('EXT:core/Tests/Functional/Configuration/Loader/Fixtures/NumericKeys/Base.yaml');
        $expected = [
            'TYPO3' => [
                'CMS' => [
                    'Form' => [
                        'prototypes' => [
                            'standard' => [
                                'formElementsDefinition' => [
                                    'Form' => [
                                        'formEditor' => [
                                            'editors' => [
                                                900 => [
                                                    'selectOptions' => [
                                                        35 => [
                                                            'value' => 'FirstValue',
                                                            'label' => 'First option',
                                                        ],
                                                        45 => [
                                                            'value' => 'SecondValue',
                                                            'label' => 'Second option',
                                                        ],
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
        self::assertSame($expected, $output);
    }

    /**
     * Method checking for path traversal imports via glob() call
     */
    #[Test]
    public function loadWithGlobbedImportsWithPathTraversalShouldFail(): void
    {
        $logger = new class () extends AbstractLogger {
            public array $logEntries = [];

            public function log($level, \Stringable|string $message, array $context = []): void
            {
                $this->logEntries[$level][0] = $message;
            }
        };
        $loader = new YamlFileLoader($logger);
        $output = $loader->load('EXT:core/Tests/Functional/Configuration/Loader/Fixtures/LoadWithGlobbedImportsWithPathTraversal.yaml');
        self::assertEmpty($output);
        self::assertSame('Referencing a file which is outside of TYPO3s main folder', $logger->logEntries[LogLevel::ERROR][0] ?? '');
    }
}
