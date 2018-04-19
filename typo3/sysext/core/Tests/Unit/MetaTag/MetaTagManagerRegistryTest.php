<?php
namespace TYPO3\CMS\Core\Tests\Unit\MetaTag;

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

use TYPO3\CMS\Core\MetaTag\GenericMetaTagManager;
use TYPO3\CMS\Core\MetaTag\Html5MetaTagManager;
use TYPO3\CMS\Core\MetaTag\MetaTagManagerRegistry;
use TYPO3\CMS\Core\MetaTag\OpenGraphMetaTagManager;
use TYPO3\CMS\Core\MetaTag\TwitterCardMetaTagManager;

/**
 * Test case
 */
class MetaTagManagerRegistryTest extends \TYPO3\TestingFramework\Core\Unit\UnitTestCase
{
    /**
     * @test
     */
    public function checkGetInstanceReturnsMetaTagManagerRegistryInstance()
    {
        return $this->assertInstanceOf(MetaTagManagerRegistry::class, MetaTagManagerRegistry::getInstance());
    }

    /**
     * @test
     */
    public function checkRegisterNonExistingManagerDoesntThrowErrorWhenFetchingManagers()
    {
        $metaTagManagerRegistry = MetaTagManagerRegistry::getInstance();

        $metaTagManagerRegistry->registerManager('name', 'fake//class//name');
        $metaTagManagerRegistry->getAllManagers();
    }

    /**
     * @param array $managersToRegister
     * @param array $expected
     *
     * @dataProvider registerMetaTagManagersProvider
     * @test
     */
    public function checkRegisterExistingManagerDoRegister($managersToRegister, $expected)
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

        $this->assertEquals($expected, $managers);
    }

    /**
     * @test
     */
    public function checkConditionRaceResultsIntoException()
    {
        $input = [
            'name' => 'opengraph',
            'className' => OpenGraphMetaTagManager::class,
            'before' => ['opengraph'],
            'after' => []
        ];

        $this->expectException(\UnexpectedValueException::class);

        $metaTagManagerRegistry = new MetaTagManagerRegistry();
        $metaTagManagerRegistry->registerManager($input['name'], $input['className'], (array)$input['before'], (array)$input['after']);
        $metaTagManagerRegistry->getAllManagers();
    }
    /**
     * @return array
     */
    public function registerMetaTagManagersProvider()
    {
        return [
            [
                [
                    [
                        'name' => 'opengraph',
                        'className' => OpenGraphMetaTagManager::class,
                        'before' => [],
                        'after' => []
                    ]
                ],
                [
                    new OpenGraphMetaTagManager(),
                    new GenericMetaTagManager()
                ]
            ],
            [
                [
                    [
                        'name' => 'opengraph',
                        'className' => OpenGraphMetaTagManager::class,
                        'before' => [],
                        'after' => []
                    ],
                    [
                        'name' => 'opengraph',
                        'className' => OpenGraphMetaTagManager::class,
                        'before' => [],
                        'after' => []
                    ],
                ],
                [
                    new OpenGraphMetaTagManager(),
                    new GenericMetaTagManager()
                ]
            ],
            [
                [
                    [
                        'name' => 'opengraph',
                        'className' => OpenGraphMetaTagManager::class,
                        'before' => [],
                        'after' => []
                    ],
                    [
                        'name' => 'html5',
                        'className' => Html5MetaTagManager::class,
                        'before' => [],
                        'after' => []
                    ],
                ],
                [
                    new Html5MetaTagManager(),
                    new OpenGraphMetaTagManager(),
                    new GenericMetaTagManager()
                ]
            ],
            [
                [
                    [
                        'name' => 'opengraph',
                        'className' => OpenGraphMetaTagManager::class,
                        'before' => ['html5'],
                        'after' => []
                    ],
                    [
                        'name' => 'html5',
                        'className' => Html5MetaTagManager::class,
                        'before' => [],
                        'after' => []
                    ],
                ],
                [
                    new OpenGraphMetaTagManager(),
                    new Html5MetaTagManager(),
                    new GenericMetaTagManager()
                ]
            ],
            [
                [
                    [
                        'name' => 'opengraph',
                        'className' => OpenGraphMetaTagManager::class,
                        'before' => [],
                        'after' => []
                    ],
                    [
                        'name' => 'html5',
                        'className' => Html5MetaTagManager::class,
                        'before' => [],
                        'after' => ['opengraph']
                    ],
                ],
                [
                    new OpenGraphMetaTagManager(),
                    new Html5MetaTagManager(),
                    new GenericMetaTagManager()
                ]
            ],
            [
                [
                    [
                        'name' => 'opengraph',
                        'className' => OpenGraphMetaTagManager::class,
                        'before' => [],
                        'after' => []
                    ],
                    [
                        'name' => 'html5',
                        'className' => Html5MetaTagManager::class,
                        'before' => [],
                        'after' => ['twitter']
                    ],
                    [
                        'name' => 'twitter',
                        'className' => TwitterCardMetaTagManager::class,
                        'before' => [],
                        'after' => ['opengraph']
                    ],
                ],
                [
                    new OpenGraphMetaTagManager(),
                    new TwitterCardMetaTagManager(),
                    new Html5MetaTagManager(),
                    new GenericMetaTagManager()
                ]
            ],
            [
                [],
                [
                    new GenericMetaTagManager()
                ]
            ],
        ];
    }
}
