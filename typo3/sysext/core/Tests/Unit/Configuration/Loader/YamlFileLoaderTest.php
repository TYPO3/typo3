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

namespace TYPO3\CMS\Core\Tests\Unit\Configuration\Loader;

use TYPO3\CMS\Core\Configuration\Loader\YamlFileLoader;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case for the YAML file loader class
 */
class YamlFileLoaderTest extends UnitTestCase
{
    /**
     * Generic method to check if the load method returns an array from a YAML file
     * @test
     */
    public function load()
    {
        $fileName = 'Berta.yml';
        $fileContents = '
options:
    - option1
    - option2
betterthanbefore: 1
';

        $expected = [
            'options' => [
                'option1',
                'option2'
            ],
            'betterthanbefore' => 1
        ];

        // Accessible mock to $subject since getFileContents calls GeneralUtility methods
        $subject = $this->getAccessibleMock(YamlFileLoader::class, ['getFileContents', 'getStreamlinedFileName']);
        $subject->expects(self::once())->method('getStreamlinedFileName')->with($fileName)->willReturn($fileName);
        $subject->expects(self::once())->method('getFileContents')->with($fileName)->willReturn($fileContents);
        $output = $subject->load($fileName);
        self::assertSame($expected, $output);
    }

    /**
     * Method checking for imports that they have been processed properly
     * @test
     */
    public function loadWithAnImport()
    {
        $fileName = 'Berta.yml';
        $fileContents = '
imports:
    - { resource: Secondfile.yml }

options:
    - option1
    - option2
betterthanbefore: 1
';

        $importFileName = 'Secondfile.yml';
        $importFileContents = '
options:
    - optionBefore
betterthanbefore: 2
';

        $expected = [
            'options' => [
                'optionBefore',
                'option1',
                'option2'
            ],
            'betterthanbefore' => 1
        ];

        // Accessible mock to $subject since getFileContents calls GeneralUtility methods
        $subject = $this->getAccessibleMock(YamlFileLoader::class, ['getFileContents', 'getStreamlinedFileName']);
        $subject->expects(self::at(0))->method('getStreamlinedFileName')->with($fileName)->willReturn($fileName);
        $subject->expects(self::at(1))->method('getFileContents')->with($fileName)->willReturn($fileContents);
        $subject->expects(self::at(2))->method('getStreamlinedFileName')->with($importFileName, $fileName)->willReturn($importFileName);
        $subject->expects(self::at(3))->method('getFileContents')->with($importFileName)->willReturn($importFileContents);
        $output = $subject->load($fileName);
        self::assertSame($expected, $output);
    }

    /**
     * Method checking for imports that they have been processed properly
     * @test
     */
    public function loadWithImportAndRelativePaths()
    {
        $subject = new YamlFileLoader();
        $result = $subject->load(__DIR__ . '/Fixtures/Berta.yaml');
        self::assertSame([
            'enable' => [
                'frontend' => false,
                'json.api' => true,
                'backend' => true,
                'rest.api' => true,
            ]
        ], $result);
    }

    /**
     * Method checking for placeholders
     * @test
     */
    public function loadWithPlaceholders(): void
    {
        $fileName = 'Berta.yml';
        $fileContents = '

firstset:
  myinitialversion: 13
options:
    - option1
    - option2
betterthanbefore: \'%firstset.myinitialversion%\'
muchbetterthanbefore: \'some::%options.0%::option\'
';

        $expected = [
            'firstset' => [
                'myinitialversion' => 13
            ],
            'options' => [
                'option1',
                'option2'
            ],
            'betterthanbefore' => 13,
            'muchbetterthanbefore' => 'some::option1::option'
        ];

        // Accessible mock to $subject since getFileContents calls GeneralUtility methods
        $subject = $this->getAccessibleMock(YamlFileLoader::class, ['getFileContents', 'getStreamlinedFileName']);
        $subject->expects(self::once())->method('getStreamlinedFileName')->with($fileName)->willReturn($fileName);
        $subject->expects(self::once())->method('getFileContents')->with($fileName)->willReturn($fileContents);
        $output = $subject->load($fileName);
        self::assertSame($expected, $output);
    }

    /**
     * Method checking for nested placeholders
     * @test
     */
    public function loadWithNestedPlaceholders(): void
    {
        $fileName = 'Berta.yml';
        $fileContents = '

firstset:
  myinitialversion: 13
options:
    - option1
    - option2
betterthanbefore: \'%env(foo)%\'
';

        $expected = [
            'firstset' => [
                'myinitialversion' => 13
            ],
            'options' => [
                'option1',
                'option2'
            ],
            'betterthanbefore' => 13
        ];

        // Accessible mock to $subject since getFileContents calls GeneralUtility methods
        $subject = $this->getAccessibleMock(YamlFileLoader::class, ['getFileContents', 'getStreamlinedFileName']);
        $subject->expects(self::once())->method('getStreamlinedFileName')->with($fileName)->willReturn($fileName);
        $subject->expects(self::once())->method('getFileContents')->with($fileName)->willReturn($fileContents);

        putenv('foo=%firstset.myinitialversion%');
        $output = $subject->load($fileName);
        putenv('foo=');
        self::assertSame($expected, $output);
    }

    /**
     * Method checking for imports with env vars that they have been processed properly
     * @test
     */
    public function loadWithImportAndEnvVars(): void
    {
        $loader = new YamlFileLoader();

        putenv('foo=barbaz');
        $output = $loader->load(__DIR__ . '/Fixtures/Env/Berta.yml');
        putenv('foo=');

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

    public function loadWithEnvVarDataProvider(): array
    {
        return [
            'plain' => [
                ['foo=heinz'],
                'carl: \'%env(foo)%\'',
                ['carl' => 'heinz']
            ],
            'quoted var' => [
                ['foo=heinz'],
                "carl: '%env(''foo'')%'",
                ['carl' => 'heinz']
            ],
            'double quoted var' => [
                ['foo=heinz'],
                "carl: '%env(\"foo\")%'",
                ['carl' => 'heinz']
            ],
            'var in the middle' => [
                ['foo=heinz'],
                "carl: 'https://%env(foo)%/foo'",
                ['carl' => 'https://heinz/foo']
            ],
            'quoted var in the middle' => [
                ['foo=heinz'],
                "carl: 'https://%env(''foo'')%/foo'",
                ['carl' => 'https://heinz/foo']
            ],
            'double quoted var in the middle' => [
                ['foo=heinz'],
                "carl: 'https://%env(\"foo\")%/foo'",
                ['carl' => 'https://heinz/foo']
            ],
            'two env vars' => [
                ['foo=karl', 'bar=heinz'],
                'carl: \'%env(foo)%::%env(bar)%\'',
                ['carl' => 'karl::heinz']
            ],
            'three env vars' => [
                ['foo=karl', 'bar=heinz', 'baz=bencer'],
                'carl: \'%env(foo)%::%env(bar)%::%env(baz)%\'',
                ['carl' => 'karl::heinz::bencer']
            ],
            'three env vars with baz being undefined' => [
                ['foo=karl', 'bar=heinz'],
                'carl: \'%env(foo)%::%env(bar)%::%env(baz)%\'',
                ['carl' => 'karl::heinz::%env(baz)%']
            ],
            'three undefined env vars' => [
                [],
                'carl: \'%env(foo)%::%env(bar)%::%env(baz)%\'',
                ['carl' => '%env(foo)%::%env(bar)%::%env(baz)%']
            ],
            'nested env variables' => [
                ['foo=bar', 'bar=heinz'],
                'carl: \'%env(%env(foo)%)%\'',
                ['carl' => 'heinz']
            ],
        ];
    }

    /**
     * Method checking for env placeholders
     *
     * @dataProvider loadWithEnvVarDataProvider
     * @test
     * @param array $envs
     * @param string $yamlContent
     * @param array $expected
     */
    public function loadWithEnvVarPlaceholders(array $envs, string $yamlContent, array $expected): void
    {
        foreach ($envs as $env) {
            putenv($env);
        }
        $fileName = 'Berta.yml';
        $fileContents = $yamlContent;

        // Accessible mock to $subject since getFileContents calls GeneralUtility methods
        $subject = $this->getAccessibleMock(YamlFileLoader::class, ['getFileContents', 'getStreamlinedFileName']);
        $subject->expects(self::once())->method('getStreamlinedFileName')->with($fileName)->willReturn($fileName);
        $subject->expects(self::once())->method('getFileContents')->with($fileName)->willReturn($fileContents);
        $output = $subject->load($fileName);
        self::assertSame($expected, $output);
        putenv('foo=');
        putenv('bar=');
        putenv('baz=');
    }

    /**
     * Method checking for env placeholders
     *
     * @test
     */
    public function loadWithEnvVarPlaceholdersDoesNotReplaceWithNonExistingValues(): void
    {
        $fileName = 'Berta.yml';
        $fileContents = '

firstset:
  myinitialversion: 13
options:
    - option1
    - option2
betterthanbefore: \'%env(mynonexistingenv)%\'
';

        $expected = [
            'firstset' => [
                'myinitialversion' => 13
            ],
            'options' => [
                'option1',
                'option2'
            ],
            'betterthanbefore' => '%env(mynonexistingenv)%'
        ];

        // Accessible mock to $subject since getFileContents calls GeneralUtility methods
        $subject = $this->getAccessibleMock(YamlFileLoader::class, ['getFileContents', 'getStreamlinedFileName']);
        $subject->expects(self::once())->method('getStreamlinedFileName')->with($fileName)->willReturn($fileName);
        $subject->expects(self::once())->method('getFileContents')->with($fileName)->willReturn($fileContents);
        $output = $subject->load($fileName);
        self::assertSame($expected, $output);
    }

    /**
     * dataprovider for tests isPlaceholderTest
     * @return array
     */
    public function isPlaceholderDataProvider()
    {
        return [
            'regular string' => [
                'berta13',
                false
            ],
            'regular array' => [
                ['berta13'],
                false
            ],
            'regular float' => [
                13.131313,
                false
            ],
            'regular int' => [
                13,
                false
            ],
            'invalid placeholder with only % at the beginning' => [
                '%cool',
                false
            ],
            'invalid placeholder with only % at the end' => [
                'cool%',
                false
            ],
            'invalid placeholder with two % but not at the end' => [
                '%cool%again',
                true
            ],
            'invalid placeholder with two % but not at the beginning nor end' => [
                'did%you%know',
                true
            ],
            'valid placeholder with just numbers' => [
                '%13%',
                true
            ],
            'valid placeholder' => [
                '%foo%baracks%',
                true
            ],
        ];
    }

    /**
     * @dataProvider isPlaceholderDataProvider
     * @test
     * @param mixed $placeholderValue
     * @param bool $expected
     * @skip
     */
    public function containsPlaceholderTest($placeholderValue, bool $expected)
    {
        $subject = $this->getAccessibleMock(YamlFileLoader::class, ['dummy']);
        $output = $subject->_call('containsPlaceholder', $placeholderValue);
        self::assertSame($expected, $output);
    }
}
