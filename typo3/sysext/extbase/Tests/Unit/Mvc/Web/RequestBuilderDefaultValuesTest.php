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

namespace TYPO3\CMS\Extbase\Tests\Unit\Mvc\Web;

use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Mvc\Web\RequestBuilderDefaultValues;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class RequestBuilderDefaultValuesTest extends UnitTestCase
{
    private const MINIMAL_WORKING_CONFIGURATION = [
        'extensionName' => 'news',
        'pluginName' => 'list',
        'controllerConfiguration' => [
            ActionController::class => [
                'actions' => [
                    'list',
                    'show',
                ],
                'className' => ActionController::class,
                'alias' => 'ActionController',
            ],
        ],
    ];

    /**
     * @test
     */
    public function fromConfigurationThrowsExceptionIfConfigurationMissesExtensionName(): void
    {
        self::expectExceptionCode(1289843275);
        RequestBuilderDefaultValues::fromConfiguration([]);
    }

    /**
     * @test
     */
    public function fromConfigurationThrowsExceptionIfConfigurationMissesPluginName(): void
    {
        self::expectExceptionCode(1289843277);
        RequestBuilderDefaultValues::fromConfiguration([
            'extensionName' => 'news',
        ]);
    }

    /**
     * @test
     */
    public function fromConfigurationThrowsExceptionIfConfigurationMissesControllerConfigurations(): void
    {
        self::expectExceptionCode(1316104317);
        RequestBuilderDefaultValues::fromConfiguration([
            'extensionName' => 'news',
            'pluginName' => 'list',
        ]);
    }

    /**
     * @test
     */
    public function fromConfigurationSetsExtensionName(): void
    {
        $defaultValues = RequestBuilderDefaultValues::fromConfiguration(self::MINIMAL_WORKING_CONFIGURATION);

        self::assertSame('news', $defaultValues->getExtensionName());
    }

    /**
     * @test
     */
    public function fromConfigurationSetsPluginName(): void
    {
        $defaultValues = RequestBuilderDefaultValues::fromConfiguration(self::MINIMAL_WORKING_CONFIGURATION);

        self::assertSame('list', $defaultValues->getPluginName());
    }

    /**
     * @test
     */
    public function fromConfigurationFallsBackToDefaultFormat(): void
    {
        $defaultValues = RequestBuilderDefaultValues::fromConfiguration(self::MINIMAL_WORKING_CONFIGURATION);

        self::assertSame('html', $defaultValues->getDefaultFormat());
    }

    /**
     * @test
     */
    public function fromConfigurationSetsFormat(): void
    {
        $defaultValues = RequestBuilderDefaultValues::fromConfiguration(
            self::MINIMAL_WORKING_CONFIGURATION + [
                'format' => 'json',
            ]
        );

        self::assertSame('json', $defaultValues->getDefaultFormat());
    }

    /**
     * @test
     */
    public function fromConfigurationSetsDefaultControllerClassName(): void
    {
        $defaultValues = RequestBuilderDefaultValues::fromConfiguration(self::MINIMAL_WORKING_CONFIGURATION);

        self::assertSame(ActionController::class, $defaultValues->getDefaultControllerClassName());
    }

    /**
     * @test
     */
    public function fromConfigurationSetsDefaultControllerAlias(): void
    {
        $defaultValues = RequestBuilderDefaultValues::fromConfiguration(self::MINIMAL_WORKING_CONFIGURATION);

        self::assertSame('ActionController', $defaultValues->getDefaultControllerAlias());
    }

    /**
     * @test
     */
    public function getControllerAliasForControllerClassName(): void
    {
        $defaultValues = RequestBuilderDefaultValues::fromConfiguration(self::MINIMAL_WORKING_CONFIGURATION);

        self::assertSame('ActionController', $defaultValues->getControllerAliasForControllerClassName(ActionController::class));
    }

    /**
     * @test
     */
    public function getControllerClassNameForAlias(): void
    {
        $defaultValues = RequestBuilderDefaultValues::fromConfiguration(self::MINIMAL_WORKING_CONFIGURATION);

        self::assertSame(ActionController::class, $defaultValues->getControllerClassNameForAlias('ActionController'));
    }

    /**
     * @test
     */
    public function getAllowedControllerActions(): void
    {
        $defaultValues = RequestBuilderDefaultValues::fromConfiguration(self::MINIMAL_WORKING_CONFIGURATION);

        self::assertSame([ActionController::class => ['list', 'show']], $defaultValues->getAllowedControllerActions());
    }

    /**
     * @test
     */
    public function getControllerAliasToClassMapping(): void
    {
        $defaultValues = RequestBuilderDefaultValues::fromConfiguration(self::MINIMAL_WORKING_CONFIGURATION);

        self::assertSame(['ActionController' => ActionController::class], $defaultValues->getControllerAliasToClassMapping());
    }

    /**
     * @test
     */
    public function getControllerClassToAliasMapping(): void
    {
        $defaultValues = RequestBuilderDefaultValues::fromConfiguration(self::MINIMAL_WORKING_CONFIGURATION);

        self::assertSame([ActionController::class => 'ActionController'], $defaultValues->getControllerClassToAliasMapping());
    }
}
