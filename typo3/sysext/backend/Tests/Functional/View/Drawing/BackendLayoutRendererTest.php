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

namespace TYPO3\CMS\Backend\Tests\Functional\View\Drawing;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Backend\View\BackendLayout\BackendLayout;
use TYPO3\CMS\Backend\View\BackendViewFactory;
use TYPO3\CMS\Backend\View\Drawing\BackendLayoutRenderer;
use TYPO3\CMS\Backend\View\PageLayoutContext;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Domain\RecordFactory;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextFactory;
use TYPO3\TestingFramework\Core\Functional\Framework\DataHandling\Scenario\DataHandlerFactory;
use TYPO3\TestingFramework\Core\Functional\Framework\DataHandling\Scenario\DataHandlerWriter;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class BackendLayoutRendererTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8'],
    ];

    protected array $coreExtensionsToLoad = ['workspaces'];

    private BackendUserAuthentication $backendUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withDatabaseSnapshot(
            function () {
                $this->importCSVDataSet(__DIR__ . '/../../Fixtures/be_users.csv');
                $this->backendUser = $this->setUpBackendUser(1);
                $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($this->backendUser);
                $scenarioFile = __DIR__ . '/../Fixtures/DefaultViewScenario.yaml';
                $factory = DataHandlerFactory::fromYamlFile($scenarioFile);
                $writer = DataHandlerWriter::withBackendUser($this->backendUser);
                $writer->invokeFactory($factory);
                static::failIfArrayIsNotEmpty($writer->getErrors());
            },
            function () {
                $this->backendUser = $this->setUpBackendUser(1);
            }
        );
    }

    protected function getPageLayoutContext(int $pageId, array $configuration): PageLayoutContext&MockObject
    {
        $backendLayout = new BackendLayout('layout1', 'Layout 1', $configuration);
        return $this->createConfiguredMock(
            PageLayoutContext::class,
            [
                'getBackendLayout' => $backendLayout,
                'getPageId' => $pageId,
            ]
        );
    }

    #[Test]
    public function emptyBackendLayoutIsRendered(): void
    {
        $configuration['__config']['backend_layout.'] = [
            'rows.' => [],
        ];
        $pageLayoutContext = $this->getPageLayoutContext(1100, $configuration);
        $subject = new BackendLayoutRenderer(
            new BackendViewFactory($this->get(RenderingContextFactory::class), $this->get(PackageManager::class)),
            $this->get(RecordFactory::class),
        );
        self::assertCount(0, $subject->getGridForPageLayoutContext($pageLayoutContext)->getRows());
    }

    #[Test]
    public function oneRowBackendLayoutIsRendered(): void
    {
        $configuration['__config']['backend_layout.'] = [
            'rows.' => [
                0 => [
                    'columns.' => [],
                ],
            ],
        ];
        $pageLayoutContext = $this->getPageLayoutContext(1100, $configuration);
        $subject = new BackendLayoutRenderer(
            new BackendViewFactory($this->get(RenderingContextFactory::class), $this->get(PackageManager::class)),
            $this->get(RecordFactory::class),
        );
        self::assertCount(1, $subject->getGridForPageLayoutContext($pageLayoutContext)->getRows());
        self::assertCount(0, $subject->getGridForPageLayoutContext($pageLayoutContext)->getColumns());
    }

    #[Test]
    public function invalidRowBackendLayoutDoesNotFail(): void
    {
        $configuration['__config']['backend_layout.'] = [
            'rows.' => [
                0 => [
                    // Note: No "columns." array key
                ],
                1 => [
                    'columns.' => [],
                ],
            ],
        ];
        $pageLayoutContext = $this->getPageLayoutContext(1100, $configuration);
        $subject = new BackendLayoutRenderer(
            new BackendViewFactory($this->get(RenderingContextFactory::class), $this->get(PackageManager::class)),
            $this->get(RecordFactory::class),
        );
        self::assertCount(2, $subject->getGridForPageLayoutContext($pageLayoutContext)->getRows());
        self::assertCount(0, $subject->getGridForPageLayoutContext($pageLayoutContext)->getColumns());
    }

    #[Test]
    public function multipleRowsBackendLayoutIsRendered(): void
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
        $subject = new BackendLayoutRenderer(
            new BackendViewFactory($this->get(RenderingContextFactory::class), $this->get(PackageManager::class)),
            $this->get(RecordFactory::class),
        );
        self::assertCount(2, $subject->getGridForPageLayoutContext($pageLayoutContext)->getRows());
        self::assertCount(0, $subject->getGridForPageLayoutContext($pageLayoutContext)->getColumns());
    }

    #[Test]
    public function oneRowOneColBackendLayoutIsRendered(): void
    {
        $configuration['__config']['backend_layout.'] = [
            'rows.' => [
                0 => [
                    'columns.' => [
                        0 => [
                            'colPos' => 0,
                        ],
                    ],
                ],
            ],
        ];
        $pageLayoutContext = $this->getPageLayoutContext(1100, $configuration);
        $subject = new BackendLayoutRenderer(
            new BackendViewFactory($this->get(RenderingContextFactory::class), $this->get(PackageManager::class)),
            $this->get(RecordFactory::class),
        );
        self::assertCount(1, $subject->getGridForPageLayoutContext($pageLayoutContext)->getRows());
        self::assertCount(1, $subject->getGridForPageLayoutContext($pageLayoutContext)->getColumns());
        foreach ($subject->getGridForPageLayoutContext($pageLayoutContext)->getColumns() as $column) {
            self::assertCount(1, $column->getItems());
        }
    }

    #[Test]
    public function oneRowMultipleColsBackendLayoutIsRendered(): void
    {
        $configuration['__config']['backend_layout.'] = [
            'rows.' => [
                0 => [
                    'columns.' => [
                        0 => [
                            'colPos' => 0,
                        ],
                        1 => [
                            'colPos' => 1,
                        ],
                    ],
                ],
            ],
        ];
        $pageLayoutContext = $this->getPageLayoutContext(1100, $configuration);
        $subject = new BackendLayoutRenderer(
            new BackendViewFactory($this->get(RenderingContextFactory::class), $this->get(PackageManager::class)),
            $this->get(RecordFactory::class),
        );
        self::assertCount(1, $subject->getGridForPageLayoutContext($pageLayoutContext)->getRows());
        self::assertCount(2, $subject->getGridForPageLayoutContext($pageLayoutContext)->getColumns());
        foreach ($subject->getGridForPageLayoutContext($pageLayoutContext)->getColumns() as $column) {
            self::assertCount(1, $column->getItems());
        }
    }

    #[Test]
    public function multipleRowsOneColBackendLayoutIsRendered(): void
    {
        $configuration['__config']['backend_layout.'] = [
            'rows.' => [
                0 => [
                    'columns.' => [
                        0 => [
                            'colPos' => 0,
                        ],
                    ],
                ],
                1 => [
                    'columns.' => [
                        0 => [
                            'colPos' => 1,
                        ],
                    ],
                ],
            ],
        ];
        $pageLayoutContext = $this->getPageLayoutContext(1100, $configuration);
        $subject = new BackendLayoutRenderer(
            new BackendViewFactory($this->get(RenderingContextFactory::class), $this->get(PackageManager::class)),
            $this->get(RecordFactory::class),
        );
        self::assertCount(2, $subject->getGridForPageLayoutContext($pageLayoutContext)->getRows());
        self::assertCount(2, $subject->getGridForPageLayoutContext($pageLayoutContext)->getColumns());
        foreach ($subject->getGridForPageLayoutContext($pageLayoutContext)->getColumns() as $column) {
            self::assertCount(1, $column->getItems());
        }
    }

    #[Test]
    public function multipleRowsMultipleColsBackendLayoutIsRendered(): void
    {
        $configuration['__config']['backend_layout.'] = [
            'rows.' => [
                0 => [
                    'columns.' => [
                        0 => [
                            'colPos' => 0,
                        ],
                        1 => [
                            'colPos' => 1,
                        ],
                    ],
                ],
                1 => [
                    'columns.' => [
                        0 => [
                            'colPos' => 2,
                        ],
                        1 => [
                            'colPos' => 3,
                        ],
                    ],
                ],
            ],
        ];
        $pageLayoutContext = $this->getPageLayoutContext(1100, $configuration);
        $subject = new BackendLayoutRenderer(
            new BackendViewFactory($this->get(RenderingContextFactory::class), $this->get(PackageManager::class)),
            $this->get(RecordFactory::class),
        );
        self::assertCount(2, $subject->getGridForPageLayoutContext($pageLayoutContext)->getRows());
        self::assertCount(4, $subject->getGridForPageLayoutContext($pageLayoutContext)->getColumns());
        foreach ($subject->getGridForPageLayoutContext($pageLayoutContext)->getColumns() as $column) {
            self::assertCount(1, $column->getItems());
        }
    }

    #[Test]
    public function noColPosBackendLayoutIsRendered(): void
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
        $subject = new BackendLayoutRenderer(
            new BackendViewFactory($this->get(RenderingContextFactory::class), $this->get(PackageManager::class)),
            $this->get(RecordFactory::class),
        );
        self::assertCount(1, $subject->getGridForPageLayoutContext($pageLayoutContext)->getRows());
        self::assertCount(1, $subject->getGridForPageLayoutContext($pageLayoutContext)->getColumns());
        foreach ($subject->getGridForPageLayoutContext($pageLayoutContext)->getColumns() as $column) {
            self::assertCount(0, $column->getItems());
        }
    }
}
