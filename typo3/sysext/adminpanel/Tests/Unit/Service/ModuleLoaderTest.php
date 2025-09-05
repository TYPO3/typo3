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

namespace TYPO3\CMS\Adminpanel\Tests\Unit\Service;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Adminpanel\Service\ModuleLoader;
use TYPO3\CMS\Adminpanel\Tests\Unit\Fixtures\DisabledMainModuleFixture;
use TYPO3\CMS\Adminpanel\Tests\Unit\Fixtures\MainModuleFixture;
use TYPO3\CMS\Core\Service\DependencyOrderingService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class ModuleLoaderTest extends UnitTestCase
{
    #[Test]
    public function validateSortAndInitializeModulesReturnsEmptyArrayIfNoModulesAreConfigured(): void
    {
        $moduleLoader = new ModuleLoader();
        $result = $moduleLoader->validateSortAndInitializeModules([]);

        self::assertSame([], $result);
    }

    public static function missingConfigurationDataProvider(): array
    {
        return [
            'empty' => [['modulename' => []]],
            'no array' => [['modulename' => '']],
        ];
    }

    #[DataProvider('missingConfigurationDataProvider')]
    #[Test]
    public function validateSortAndInitializeModulesThrowsExceptionIfModuleHasMissingConfiguration(array $configuration): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1519490105);

        $moduleLoader = new ModuleLoader();
        $moduleLoader->validateSortAndInitializeModules($configuration);
    }

    public static function invalidConfigurationDataProvider(): array
    {
        return [
            'module class name is no string' => [
                [
                    'modulename' => ['module' => []],
                ],
            ],
            'module class name is empty' => [
                [
                    'modulename' => ['module' => ''],
                ],
            ],
            'module class name is no valid class' => [
                [
                    'modulename' => ['module' => 'nonExistingClassName'],
                ],
            ],
            'module class name does not implement AdminPanelModuleInterface' => [
                [
                    'modulename' => ['module' => \stdClass::class],
                ],
            ],
        ];
    }

    #[DataProvider('invalidConfigurationDataProvider')]
    #[Test]
    public function validateSortAndInitializeModulesThrowsExceptionIfModuleHasInvalidConfiguration(array $configuration): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1519490112);

        $moduleLoader = new ModuleLoader();
        $moduleLoader->validateSortAndInitializeModules($configuration);
    }

    #[Test]
    public function validateSortAndInitializeModulesOrdersModulesWithDependencyOrderingService(): void
    {
        $config = [
            'module1' => [
                'module' => MainModuleFixture::class,
            ],
        ];

        $dependencyOrderingServiceMock = $this->getMockBuilder(DependencyOrderingService::class)->getMock();
        GeneralUtility::addInstance(DependencyOrderingService::class, $dependencyOrderingServiceMock);
        $dependencyOrderingServiceMock->expects($this->atLeastOnce())->method('orderByDependencies')
            ->with($config)->willReturn($config);

        $moduleLoader = new ModuleLoader();
        $moduleLoader->validateSortAndInitializeModules($config);
    }

    #[Test]
    public function validateSortAndInitializeModulesInstantiatesMainModulesOnlyIfEnabled(): void
    {
        $config = [
            'module1' => [
                'module' => MainModuleFixture::class,
            ],
            'module2' => [
                'module' => DisabledMainModuleFixture::class,
            ],
        ];

        $dependencyOrderingServiceMock = $this->getMockBuilder(DependencyOrderingService::class)->getMock();
        GeneralUtility::addInstance(DependencyOrderingService::class, $dependencyOrderingServiceMock);
        $dependencyOrderingServiceMock->expects($this->atLeastOnce())->method('orderByDependencies')
            ->with($config)->willReturn($config);

        $moduleLoader = new ModuleLoader();
        $result = $moduleLoader->validateSortAndInitializeModules($config);

        self::assertCount(1, $result);
        self::assertInstanceOf(MainModuleFixture::class, $result['example']);
        self::assertArrayNotHasKey('example-disabled', $result);
    }
}
