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

namespace TYPO3\CMS\Core\Tests\UnitDeprecated\Cache;

use TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\VariableFrontend;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class CacheManagerTest extends UnitTestCase
{
    /**
     * @test
     */
    public function setCacheConfigurationsMergesLegacyConfigCorrectly(): void
    {
        $rawConfiguration = [
            'pages' => [
                'frontend' => VariableFrontend::class,
                'backend' => Typo3DatabaseBackend::class,
                'options' => [
                    'compression' => true,
                ],
                'groups' => ['pages'],
            ],
            'cache_pages' => [
                'backend' => \TYPO3\CMS\Core\Cache\Backend\RedisBackend::class,
                'options' => [
                    'hostname' => 'redis',
                ],
                'groups' => ['pages'],
            ],
        ];
        $expectedConfiguration = [
            'pages' => [
                'frontend' => VariableFrontend::class,
                'backend' => \TYPO3\CMS\Core\Cache\Backend\RedisBackend::class,
                'options' => [
                    'compression' => true,
                    'hostname' => 'redis',
                ],
                'groups' => ['pages'],
            ],
        ];

        $manager = $this->getAccessibleMock(CacheManager::class, ['dummy']);
        $manager->setCacheConfigurations($rawConfiguration);
        self::assertEquals($expectedConfiguration, $manager->_get('cacheConfigurations'));
    }
}
