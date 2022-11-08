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

use TYPO3\CMS\Core\Configuration\Loader\YamlFileLoader;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class YamlFileLoaderTest extends FunctionalTestCase
{
    protected bool $resetSingletonInstances = true;

    /**
     * Generic method to check if the load method returns an array from a YAML file
     *
     * @test
     */
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
        $output = (new YamlFileLoader())->load($fileName);
        self::assertSame($expected, $output);
    }

    /**
     * Method checking for imports that they have been processed properly
     *
     * @test
     */
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

        $output = (new YamlFileLoader())->load($fileName);
        self::assertSame($expected, $output);
    }

    /**
     * Method checking for multiple imports that they have been loaded in the right order
     *
     * @test
     */
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

        $output = (new YamlFileLoader())->load($fileName);
        self::assertSame($expected, $output);
    }

    /**
     * Method checking for imports that they have been processed properly
     *
     * @test
     */
    public function loadWithImportAndRelativePaths(): void
    {
        $fileName = 'EXT:core/Tests/Functional/Configuration/Loader/Fixtures/LoadWithImportAndRelativeFiles.yaml';
        $output = (new YamlFileLoader())->load($fileName);
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
     *
     * @test
     */
    public function loadWithPlaceholders(): void
    {
        $fileName = 'EXT:core/Tests/Functional/Configuration/Loader/Fixtures/LoadWithPlaceholders.yaml';
        $output = (new YamlFileLoader())->load($fileName);

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
     * Method checking for nested placeholders
     *
     * @test
     */
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
        $output = (new YamlFileLoader())->load($fileName);
        putenv('foo=');
        self::assertSame($expected, $output);
    }

    /**
     * Method checking for imports with env vars that they have been processed properly
     *
     * @test
     */
    public function loadWithImportAndEnvVars(): void
    {
        $loader = new YamlFileLoader();

        putenv('foo=barbaz');
        $output = $loader->load('EXT:core/Tests/Functional/Configuration/Loader/Fixtures/Env/Berta.yaml');
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

    /**
     * Method checking for imports with placeholder values in the file name
     *
     * @test
     */
    public function loadWithImportAndPlaceholderInFileName(): void
    {
        $output = (new YamlFileLoader())->load('EXT:core/Tests/Functional/Configuration/Loader/Fixtures/Placeholder/Berta.yaml');

        $expected = [
            'loadedWithPlaceholder' => 1,
            'settings' => [
                'dynamicOption' => 'Foo',
            ],
        ];

        self::assertSame($expected, $output);
    }
}
