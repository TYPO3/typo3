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

namespace TYPO3\CMS\Backend\Tests\Functional\View\BackendLayout\Drawing;

use TYPO3\CMS\Backend\View\BackendLayout\BackendLayout;
use TYPO3\CMS\Backend\View\Drawing\BackendLayoutRenderer;
use TYPO3\CMS\Backend\View\PageLayoutContext;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\Framework\DataHandling\Scenario\DataHandlerFactory;
use TYPO3\TestingFramework\Core\Functional\Framework\DataHandling\Scenario\DataHandlerWriter;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Testing rendering of backend layouts.
 */
class BackendLayoutRendererTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    /**
     * @var string[]
     */
    protected $coreExtensionsToLoad = ['workspaces'];

    /**
     * @var BackendUserAuthentication
     */
    private $backendUser;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        static::initializeDatabaseSnapshot();
    }

    public static function tearDownAfterClass(): void
    {
        static::destroyDatabaseSnapshot();
        parent::tearDownAfterClass();
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->backendUser = $this->setUpBackendUserFromFixture(1);

        $this->withDatabaseSnapshot(function () {
            $this->setUpDatabase();
        });
    }

    protected function tearDown(): void
    {
        unset($this->backendUser);
        parent::tearDown();
    }

    protected function setUpDatabase()
    {
        Bootstrap::initializeLanguageObject();

        $scenarioFile = __DIR__ . '/../Fixtures/DefaultViewScenario.yaml';
        $factory = DataHandlerFactory::fromYamlFile($scenarioFile);
        $writer = DataHandlerWriter::withBackendUser($this->backendUser);
        $writer->invokeFactory($factory);
        static::failIfArrayIsNotEmpty(
            $writer->getErrors()
        );
    }

    /**
     * @param int   $pageId
     * @param array $configuration BackendLayout configuration
     * @return PageLayoutContext|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function getPageLayoutContext(int $pageId, array $configuration)
    {
        // Create a BackendLayout
        $backendLayout = new BackendLayout('layout1', 'Layout 1', $configuration);

        // Create a mock for the PageLayoutContext class
        return $this->createConfiguredMock(
            PageLayoutContext::class,
            [
                'getBackendLayout' => $backendLayout,
                'getPageId' => $pageId,
            ]
        );
    }

    /**
     * @param PageLayoutContext|\PHPUnit\Framework\MockObject\MockObject $context
     * @return BackendLayoutRenderer
     */
    protected function getSubject(PageLayoutContext $context)
    {
        return GeneralUtility::makeInstance(BackendLayoutRenderer::class, $context);
    }

    /**
     * @test
     */
    public function emptyBackendLayoutIsRendered()
    {
        $configuration['__config']['backend_layout.'] = [
            'rows.' => [],
        ];
        $pageLayoutContext = $this->getPageLayoutContext(1100, $configuration);
        $subject = $this->getSubject($pageLayoutContext);

        // Test the subject
        self::assertCount(0, $subject->getGridForPageLayoutContext($pageLayoutContext)->getRows());
    }

    /**
     * @test
     */
    public function oneRowBackendLayoutIsRendered()
    {
        $configuration['__config']['backend_layout.'] = [
            'rows.' => [
                0 => [
                    'columns.' => [],
                ],
            ],
        ];
        $pageLayoutContext = $this->getPageLayoutContext(1100, $configuration);
        $subject = $this->getSubject($pageLayoutContext);

        // Test the subject
        self::assertCount(1, $subject->getGridForPageLayoutContext($pageLayoutContext)->getRows());
        self::assertCount(0, $subject->getGridForPageLayoutContext($pageLayoutContext)->getColumns());
    }

    /**
     * @test
     */
    public function multipleRowsBackendLayoutIsRendered()
    {
        $configuration['__config']['backend_layout.'] = [
            'rows.' => [
                0 => [
                    'columns.' => [],
                ],
                1 => [
                    'columns.' => [],
                ],
            ],
        ];
        $pageLayoutContext = $this->getPageLayoutContext(1100, $configuration);
        $subject = $this->getSubject($pageLayoutContext);

        // Test the subject
        self::assertCount(2, $subject->getGridForPageLayoutContext($pageLayoutContext)->getRows());
        self::assertCount(0, $subject->getGridForPageLayoutContext($pageLayoutContext)->getColumns());
    }

    /**
     * @test
     */
    public function oneRowOneColBackendLayoutIsRendered()
    {
        $configuration['__config']['backend_layout.'] = [
            'rows.' => [
                0 => [
                    'columns.' => [
                        0 => [
                            'colPos' => 0
                        ],
                    ],
                ],
            ],
        ];
        $pageLayoutContext = $this->getPageLayoutContext(1100, $configuration);
        $subject = $this->getSubject($pageLayoutContext);

        // Test the subject
        self::assertCount(1, $subject->getGridForPageLayoutContext($pageLayoutContext)->getRows());
        self::assertCount(1, $subject->getGridForPageLayoutContext($pageLayoutContext)->getColumns());
        foreach ($subject->getGridForPageLayoutContext($pageLayoutContext)->getColumns() as $column) {
            self::assertCount(1, $column->getItems());
        }
    }

    /**
     * @test
     */
    public function oneRowMultipleColsBackendLayoutIsRendered()
    {
        $configuration['__config']['backend_layout.'] = [
            'rows.' => [
                0 => [
                    'columns.' => [
                        0 => [
                            'colPos' => 0
                        ],
                        1 => [
                            'colPos' => 1
                        ],
                    ],
                ],
            ],
        ];
        $pageLayoutContext = $this->getPageLayoutContext(1100, $configuration);
        $subject = $this->getSubject($pageLayoutContext);

        // Test the subject
        self::assertCount(1, $subject->getGridForPageLayoutContext($pageLayoutContext)->getRows());
        self::assertCount(2, $subject->getGridForPageLayoutContext($pageLayoutContext)->getColumns());
        foreach ($subject->getGridForPageLayoutContext($pageLayoutContext)->getColumns() as $column) {
            self::assertCount(1, $column->getItems());
        }
    }

    /**
     * @test
     */
    public function multipleRowsOneColBackendLayoutIsRendered()
    {
        $configuration['__config']['backend_layout.'] = [
            'rows.' => [
                0 => [
                    'columns.' => [
                        0 => [
                            'colPos' => 0
                        ],
                    ],
                ],
                1 => [
                    'columns.' => [
                        0 => [
                            'colPos' => 1
                        ],
                    ],
                ],
            ],
        ];
        $pageLayoutContext = $this->getPageLayoutContext(1100, $configuration);
        $subject = $this->getSubject($pageLayoutContext);

        // Test the subject
        self::assertCount(2, $subject->getGridForPageLayoutContext($pageLayoutContext)->getRows());
        self::assertCount(2, $subject->getGridForPageLayoutContext($pageLayoutContext)->getColumns());
        foreach ($subject->getGridForPageLayoutContext($pageLayoutContext)->getColumns() as $column) {
            self::assertCount(1, $column->getItems());
        }
    }

    /**
     * @test
     */
    public function multipleRowsMultipleColsBackendLayoutIsRendered()
    {
        $configuration['__config']['backend_layout.'] = [
            'rows.' => [
                0 => [
                    'columns.' => [
                        0 => [
                            'colPos' => 0
                        ],
                        1 => [
                            'colPos' => 1
                        ],
                    ],
                ],
                1 => [
                    'columns.' => [
                        0 => [
                            'colPos' => 2
                        ],
                        1 => [
                            'colPos' => 3
                        ],
                    ],
                ],
            ],
        ];
        $pageLayoutContext = $this->getPageLayoutContext(1100, $configuration);
        $subject = $this->getSubject($pageLayoutContext);

        // Test the subject
        self::assertCount(2, $subject->getGridForPageLayoutContext($pageLayoutContext)->getRows());
        self::assertCount(4, $subject->getGridForPageLayoutContext($pageLayoutContext)->getColumns());
        foreach ($subject->getGridForPageLayoutContext($pageLayoutContext)->getColumns() as $column) {
            self::assertCount(1, $column->getItems());
        }
    }

    /**
     * @test
     */
    public function noColPosBackendLayoutIsRendered()
    {
        $configuration['__config']['backend_layout.'] = [
            'rows.' => [
                0 => [
                    'columns.' => [
                        0 => [
                        ],
                        1 => [
                        ],
                    ],
                ],
            ],
        ];
        $pageLayoutContext = $this->getPageLayoutContext(1100, $configuration);
        $subject = $this->getSubject($pageLayoutContext);

        // Test the subject
        self::assertCount(1, $subject->getGridForPageLayoutContext($pageLayoutContext)->getRows());
        self::assertCount(1, $subject->getGridForPageLayoutContext($pageLayoutContext)->getColumns());
        foreach ($subject->getGridForPageLayoutContext($pageLayoutContext)->getColumns() as $column) {
            self::assertCount(0, $column->getItems());
        }
    }
}
