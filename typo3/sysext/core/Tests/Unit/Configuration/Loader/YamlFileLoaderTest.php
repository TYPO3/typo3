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

class YamlFileLoaderTest extends UnitTestCase
{
    public function loadWithEnvVarDataProvider(): array
    {
        return [
            'plain' => [
                ['foo=heinz'],
                'carl: \'%env(foo)%\'',
                ['carl' => 'heinz'],
            ],
            'quoted var' => [
                ['foo=heinz'],
                "carl: '%env(''foo'')%'",
                ['carl' => 'heinz'],
            ],
            'double quoted var' => [
                ['foo=heinz'],
                "carl: '%env(\"foo\")%'",
                ['carl' => 'heinz'],
            ],
            'var in the middle' => [
                ['foo=heinz'],
                "carl: 'https://%env(foo)%/foo'",
                ['carl' => 'https://heinz/foo'],
            ],
            'quoted var in the middle' => [
                ['foo=heinz'],
                "carl: 'https://%env(''foo'')%/foo'",
                ['carl' => 'https://heinz/foo'],
            ],
            'double quoted var in the middle' => [
                ['foo=heinz'],
                "carl: 'https://%env(\"foo\")%/foo'",
                ['carl' => 'https://heinz/foo'],
            ],
            'two env vars' => [
                ['foo=karl', 'bar=heinz'],
                'carl: \'%env(foo)%::%env(bar)%\'',
                ['carl' => 'karl::heinz'],
            ],
            'three env vars' => [
                ['foo=karl', 'bar=heinz', 'baz=bencer'],
                'carl: \'%env(foo)%::%env(bar)%::%env(baz)%\'',
                ['carl' => 'karl::heinz::bencer'],
            ],
            'three env vars with baz being undefined' => [
                ['foo=karl', 'bar=heinz'],
                'carl: \'%env(foo)%::%env(bar)%::%env(baz)%\'',
                ['carl' => 'karl::heinz::%env(baz)%'],
            ],
            'three undefined env vars' => [
                [],
                'carl: \'%env(foo)%::%env(bar)%::%env(baz)%\'',
                ['carl' => '%env(foo)%::%env(bar)%::%env(baz)%'],
            ],
            'nested env variables' => [
                ['foo=bar', 'bar=heinz'],
                'carl: \'%env(%env(foo)%)%\'',
                ['carl' => 'heinz'],
            ],
        ];
    }

    /**
     * Method checking for env placeholders
     *
     * @dataProvider loadWithEnvVarDataProvider
     * @test
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
                'myinitialversion' => 13,
            ],
            'options' => [
                'option1',
                'option2',
            ],
            'betterthanbefore' => '%env(mynonexistingenv)%',
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
    public function isPlaceholderDataProvider(): array
    {
        return [
            'regular string' => [
                'berta13',
                false,
            ],
            'regular array' => [
                ['berta13'],
                false,
            ],
            'regular float' => [
                13.131313,
                false,
            ],
            'regular int' => [
                13,
                false,
            ],
            'invalid placeholder with only % at the beginning' => [
                '%cool',
                false,
            ],
            'invalid placeholder with only % at the end' => [
                'cool%',
                false,
            ],
            'invalid placeholder with two % but not at the end' => [
                '%cool%again',
                true,
            ],
            'invalid placeholder with two % but not at the beginning nor end' => [
                'did%you%know',
                true,
            ],
            'valid placeholder with just numbers' => [
                '%13%',
                true,
            ],
            'valid placeholder' => [
                '%foo%baracks%',
                true,
            ],
        ];
    }

    /**
     * @dataProvider isPlaceholderDataProvider
     * @test
     */
    public function containsPlaceholderTest(mixed $placeholderValue, bool $expected): void
    {
        $subject = $this->getAccessibleMock(YamlFileLoader::class, ['dummy']);
        $output = $subject->_call('containsPlaceholder', $placeholderValue);
        self::assertSame($expected, $output);
    }
}
