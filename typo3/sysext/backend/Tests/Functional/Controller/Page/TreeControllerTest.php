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

namespace TYPO3\CMS\Backend\Tests\Functional\Controller\Page;

use TYPO3\CMS\Backend\Controller\Page\TreeController;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\UserAspect;
use TYPO3\CMS\Core\Context\WorkspaceAspect;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\AccessibleObjectInterface;
use TYPO3\TestingFramework\Core\Functional\Framework\DataHandling\Scenario\DataHandlerFactory;
use TYPO3\TestingFramework\Core\Functional\Framework\DataHandling\Scenario\DataHandlerWriter;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Test case for TYPO3\CMS\Backend\Controller\Page\TreeController
 */
class TreeControllerTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    /**
     * @var string[]
     */
    protected $coreExtensionsToLoad = ['workspaces'];

    /**
     * @var TreeController|AccessibleObjectInterface
     */
    private $subject;

    /**
     * @var BackendUserAuthentication
     */
    private $backendUser;

    /**
     * @var BackendUserAuthentication
     */
    private $regularBackendUser;

    /**
     * @var Context
     */
    private $context;

    /**
     * The fixture which is used when initializing a backend user
     *
     * @var string
     */
    protected $backendUserFixture = 'EXT:core/Tests/Functional/Fixtures/be_users.xml';

    protected function setUp(): void
    {
        parent::setUp();
        //admin user for importing dataset
        $this->backendUser = $this->setUpBackendUserFromFixture(1);
        $this->setUpDatabase();

        //regular editor, non admin
        $this->backendUser = $this->setUpBackendUser(9);
        $this->context = GeneralUtility::makeInstance(Context::class);
        $this->context->setAspect('backend.user', GeneralUtility::makeInstance(UserAspect::class, $this->backendUser));

        $this->subject = $this->getAccessibleMock(TreeController::class, ['dummy']);
    }

    protected function tearDown(): void
    {
        unset($this->subject, $this->backendUser, $this->context);
        parent::tearDown();
    }

    protected function setUpDatabase()
    {
        Bootstrap::initializeLanguageObject();
        $scenarioFile = __DIR__ . '/Fixtures/PagesWithBEPermissions.yaml';
        $factory = DataHandlerFactory::fromYamlFile($scenarioFile);
        $writer = DataHandlerWriter::withBackendUser($this->backendUser);
        $writer->invokeFactory($factory);
        static::failIfArrayIsNotEmpty(
            $writer->getErrors()
        );
    }

    /**
     * @test
     */
    public function getAllEntryPointPageTrees()
    {
        $actual = $this->subject->_call('getAllEntryPointPageTrees');
        $keepProperties = array_flip(['uid', 'title', '_children']);
        $actual = $this->sortTreeArray($actual);
        $actual = $this->normalizeTreeArray($actual, $keepProperties);

        $expected = [
            [
                'uid' => 1000,
                'title' => 'ACME Inc',
                '_children' => [
                    [
                        'uid' => 1100,
                        'title' => 'EN: Welcome',
                        '_children' => [
                        ],
                    ],
                    [
                        'uid' => 1200,
                        'title' => 'EN: Features',
                        '_children' => [
                            [
                                'uid' => 1210,
                                'title' => 'EN: Frontend Editing',
                                '_children' => [
                                ],
                            ],
                            [
                                'uid' => 1230,
                                'title' => 'EN: Managing content',
                                '_children' => [
                                ],
                            ],
                        ],
                    ],
                    [
                        'uid' => 1400,
                        'title' => 'EN: ACME in your Region',
                        '_children' => [
                            [
                                'uid' => 1410,
                                'title' => 'EN: Groups',
                                '_children' => [
                                ],
                            ],
                        ],
                    ],
                    [
                        'uid' => 1500,
                        'title' => 'Internal',
                        '_children' => [
                            [
                                'uid' => 1520,
                                'title' => 'Forecasts',
                                '_children' => [
                                    [
                                        'uid' => 1521,
                                        'title' => 'Current Year',
                                        '_children' => [
                                        ],
                                    ],
                                    [
                                        'uid' => 1522,
                                        'title' => 'Next Year',
                                        '_children' => [
                                        ],
                                    ],
                                    [
                                        'uid' => 1523,
                                        'title' => 'Five Years',
                                        '_children' => [
                                        ],
                                    ],
                                ],
                            ],
                            [
                                'uid' => 1530,
                                'title' => 'Reports',
                                '_children' => [
                                ],
                            ],
                        ],
                    ],
                    [
                        'uid' => 1700,
                        'title' => 'Announcements & News',
                        '_children' => [
                        ],
                    ],
                    [
                        'uid' => 404,
                        'title' => 'Page not found',
                        '_children' => [
                        ],
                    ],
                    [
                        'uid' => 1930,
                        'title' => 'Our Blog',
                        '_children' => [
                        ],
                    ],
                    [
                        'uid' => 1990,
                        'title' => 'Storage',
                        '_children' => [
                        ],
                    ],
                ],
            ],
            [
                'uid' => 8110,
                'title' => 'Europe',
                '_children' => [
                    [
                        'uid' => 811000,
                        'title' => 'France',
                        '_children' => [],
                    ],
                ],
            ],
        ];
        self::assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function getAllEntryPointPageTreesInWorkspace()
    {
        $this->setWorkspace(1);
        $actual = $this->subject->_call('getAllEntryPointPageTrees');
        $keepProperties = array_flip(['uid', 'title', '_children']);
        $actual = $this->sortTreeArray($actual);
        $actual = $this->normalizeTreeArray($actual, $keepProperties);

        $expected = [
            [
                'uid' => 1000,
                'title' => 'ACME Inc',
                '_children' => [
                    [
                        'uid' => 1950,
                        'title' => 'EN: Goodbye',
                        '_children' => [
                            [
                                'uid' => 10007,
                                'title' => 'EN: Really Goodbye',
                                '_children' => [
                                ],
                            ],
                        ],
                    ],
                    [
                        'uid' => 1100,
                        'title' => 'EN: Welcome',
                        '_children' => [
                        ],
                    ],
                    [
                        'uid' => 1200,
                        'title' => 'EN: Features',
                        '_children' => [
                            [
                                'uid' => 1240,
                                'title' => 'EN: Managing data',
                                '_children' => [
                                    [
                                        'uid' => 124010,
                                        'title' => 'EN: Managing complex data',
                                        '_children' => [
                                        ],
                                    ],
                                ],
                            ],
                            [
                                'uid' => 1210,
                                'title' => 'EN: Frontend Editing',
                                '_children' => [
                                ],
                            ],
                            [
                                'uid' => 1230,
                                'title' => 'EN: Managing content',
                                '_children' => [
                                ],
                            ],
                        ],
                    ],
                    [
                        'uid' => 1400,
                        'title' => 'EN: ACME in your Region',
                        '_children' => [
                            [
                                'uid' => 1410,
                                'title' => 'EN: Groups',
                                '_children' => [
                                ],
                            ],
                        ],
                    ],
                    [
                        'uid' => 1500,
                        'title' => 'Internal',
                        '_children' => [
                            [
                                'uid' => 1520,
                                'title' => 'Forecasts',
                                '_children' => [
                                    [
                                        'uid' => 1521,
                                        'title' => 'Current Year',
                                        '_children' => [
                                        ],
                                    ],
                                    [
                                        'uid' => 1522,
                                        'title' => 'Next Year',
                                        '_children' => [
                                        ],
                                    ],
                                    [
                                        'uid' => 1523,
                                        'title' => 'Five Years',
                                        '_children' => [
                                        ],
                                    ],
                                ],
                            ],
                            [
                                'uid' => 1530,
                                'title' => 'Reports',
                                '_children' => [
                                ],
                            ],
                        ],
                    ],
                    [
                        'uid' => 1700,
                        'title' => 'Announcements & News',
                        '_children' => [
                            [
                                // page with sub-pages moved in workspace 1
                                // from pid 1510 (missing permissions) to pid 1700 (visible now)
                                'uid' => 1511,
                                'title' => 'Products',
                                '_children' => [
                                    [
                                        'uid' => 151110,
                                        'title' => 'Product 1',
                                        '_children' => [],
                                    ]
                                ],
                            ],
                        ],
                    ],
                    [
                        'uid' => 404,
                        'title' => 'Page not found',
                        '_children' => [
                        ],
                    ],
                    [
                        'uid' => 1930,
                        'title' => 'Our Blog',
                        '_children' => [
                        ],
                    ],
                    [
                        'uid' => 1990,
                        'title' => 'Storage',
                        '_children' => [
                        ],
                    ],
                ],
            ],
            [
                'uid' => 8110,
                'title' => 'Europe',
                '_children' => [
                    [
                        'uid' => 811000,
                        'title' => 'France',
                        '_children' => [],
                    ],
                ],
            ],
        ];
        self::assertEquals($expected, $actual);
    }

    /**
     * @param int $workspaceId
     */
    private function setWorkspace(int $workspaceId)
    {
        $this->backendUser->workspace = $workspaceId;
        $this->context->setAspect('workspace', new WorkspaceAspect($workspaceId));
    }

    /**
     * Sorts tree array by index of each section item recursively.
     *
     * @param array $tree
     * @return array
     */
    private function sortTreeArray(array $tree): array
    {
        ksort($tree);
        return array_map(
            function (array $item) {
                foreach ($item as $propertyName => $propertyValue) {
                    if (!is_array($propertyValue)) {
                        continue;
                    }
                    $item[$propertyName] = $this->sortTreeArray($propertyValue);
                }
                return $item;
            },
            $tree
        );
    }

    /**
     * Normalizes a tree array, re-indexes numeric indexes, only keep given properties.
     *
     * @param array $tree Whole tree array
     * @param array $keepProperties (property names to be used as indexes for array_intersect_key())
     * @return array Normalized tree array
     */
    private function normalizeTreeArray(array $tree, array $keepProperties): array
    {
        return array_map(
            function (array $item) use ($keepProperties) {
                // only keep these property names
                $item = array_intersect_key($item, $keepProperties);
                foreach ($item as $propertyName => $propertyValue) {
                    if (!is_array($propertyValue)) {
                        continue;
                    }
                    // process recursively for nested array items (e.g. `_children`)
                    $item[$propertyName] = $this->normalizeTreeArray($propertyValue, $keepProperties);
                }
                return $item;
            },
            // normalize numeric indexes (remove sorting markers)
            array_values($tree)
        );
    }
}
