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

namespace TYPO3\CMS\Core\Tests\Unit\MetaTag;

use TYPO3\CMS\Core\MetaTag\GenericMetaTagManager;
use TYPO3\CMS\Core\MetaTag\Html5MetaTagManager;
use TYPO3\CMS\Core\MetaTag\MetaTagManagerRegistry;
use TYPO3\CMS\Seo\MetaTag\OpenGraphMetaTagManager;
use TYPO3\CMS\Seo\MetaTag\TwitterCardMetaTagManager;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class MetaTagManagerRegistryTest extends UnitTestCase
{
    protected bool $resetSingletonInstances = true;

    /**
     * @test
     */
    public function checkRegisterNonExistingManagerDoesntThrowErrorWhenFetchingManagers(): void
    {
        $metaTagManagerRegistry = new MetaTagManagerRegistry();

        $metaTagManagerRegistry->registerManager('name', 'fake//class//name');
        $metaTagManagerRegistry->getAllManagers();
    }

    /**
     * @dataProvider registerMetaTagManagersProvider
     * @test
     */
    public function checkRegisterExistingManagerDoRegister(array $managersToRegister, array $expected): void
    {
        $metaTagManagerRegistry = new MetaTagManagerRegistry();

        foreach ($managersToRegister as $managerToRegister) {
            $metaTagManagerRegistry->registerManager(
                $managerToRegister['name'],
                $managerToRegister['className'],
                (array)$managerToRegister['before'],
                (array)$managerToRegister['after']
            );
        }

        // Remove all properties from the manager if it was set by a previous unittest
        foreach ($metaTagManagerRegistry->getAllManagers() as $manager) {
            $manager->removeAllProperties();
        }

        $managers = $metaTagManagerRegistry->getAllManagers();

        self::assertEquals($expected, $managers);
    }

    /**
     * @test
     */
    public function checkConditionRaceResultsIntoException(): void
    {
        $input = [
            'name' => 'opengraph',
            'className' => OpenGraphMetaTagManager::class,
            'before' => ['opengraph'],
            'after' => [],
        ];

        $this->expectException(\UnexpectedValueException::class);

        $metaTagManagerRegistry = new MetaTagManagerRegistry();
        $metaTagManagerRegistry->registerManager($input['name'], $input['className'], (array)$input['before'], (array)$input['after']);
        $metaTagManagerRegistry->getAllManagers();
    }
    public static function registerMetaTagManagersProvider(): array
    {
        return [
            [
                [
                    [
                        'name' => 'opengraph',
                        'className' => OpenGraphMetaTagManager::class,
                        'before' => [],
                        'after' => [],
                    ],
                ],
                [
                    'opengraph' => new OpenGraphMetaTagManager(),
                    'generic' => new GenericMetaTagManager(),
                ],
            ],
            [
                [
                    [
                        'name' => 'opengraph',
                        'className' => OpenGraphMetaTagManager::class,
                        'before' => [],
                        'after' => [],
                    ],
                    [
                        'name' => 'opengraph',
                        'className' => OpenGraphMetaTagManager::class,
                        'before' => [],
                        'after' => [],
                    ],
                ],
                [
                    'opengraph' => new OpenGraphMetaTagManager(),
                    'generic' => new GenericMetaTagManager(),
                ],
            ],
            [
                [
                    [
                        'name' => 'opengraph',
                        'className' => OpenGraphMetaTagManager::class,
                        'before' => [],
                        'after' => [],
                    ],
                    [
                        'name' => 'html5',
                        'className' => Html5MetaTagManager::class,
                        'before' => [],
                        'after' => [],
                    ],
                ],
                [
                    'html5' => new Html5MetaTagManager(),
                    'opengraph' => new OpenGraphMetaTagManager(),
                    'generic' => new GenericMetaTagManager(),
                ],
            ],
            [
                [
                    [
                        'name' => 'opengraph',
                        'className' => OpenGraphMetaTagManager::class,
                        'before' => ['html5'],
                        'after' => [],
                    ],
                    [
                        'name' => 'html5',
                        'className' => Html5MetaTagManager::class,
                        'before' => [],
                        'after' => [],
                    ],
                ],
                [
                    'opengraph' => new OpenGraphMetaTagManager(),
                    'html5' => new Html5MetaTagManager(),
                    'generic' => new GenericMetaTagManager(),
                ],
            ],
            [
                [
                    [
                        'name' => 'opengraph',
                        'className' => OpenGraphMetaTagManager::class,
                        'before' => [],
                        'after' => [],
                    ],
                    [
                        'name' => 'html5',
                        'className' => Html5MetaTagManager::class,
                        'before' => [],
                        'after' => ['opengraph'],
                    ],
                ],
                [
                    'opengraph' => new OpenGraphMetaTagManager(),
                    'html5' => new Html5MetaTagManager(),
                    'generic' => new GenericMetaTagManager(),
                ],
            ],
            [
                [
                    [
                        'name' => 'opengraph',
                        'className' => OpenGraphMetaTagManager::class,
                        'before' => [],
                        'after' => [],
                    ],
                    [
                        'name' => 'html5',
                        'className' => Html5MetaTagManager::class,
                        'before' => [],
                        'after' => ['twitter'],
                    ],
                    [
                        'name' => 'twitter',
                        'className' => TwitterCardMetaTagManager::class,
                        'before' => [],
                        'after' => ['opengraph'],
                    ],
                ],
                [
                    'opengraph' => new OpenGraphMetaTagManager(),
                    'twitter' => new TwitterCardMetaTagManager(),
                    'html5' => new Html5MetaTagManager(),
                    'generic' => new GenericMetaTagManager(),
                ],
            ],
            [
                [],
                [
                    'generic' => new GenericMetaTagManager(),
                ],
            ],
        ];
    }
}
