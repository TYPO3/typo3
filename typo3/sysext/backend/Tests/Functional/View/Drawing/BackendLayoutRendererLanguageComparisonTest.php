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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Context\PageContextFactory;
use TYPO3\CMS\Backend\Routing\Route;
use TYPO3\CMS\Backend\View\BackendLayout\BackendLayout;
use TYPO3\CMS\Backend\View\BackendViewFactory;
use TYPO3\CMS\Backend\View\Drawing\BackendLayoutRenderer;
use TYPO3\CMS\Backend\View\Drawing\DrawingConfiguration;
use TYPO3\CMS\Backend\View\PageLayoutContext;
use TYPO3\CMS\Backend\View\PageViewMode;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Domain\RecordFactory;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextFactory;
use TYPO3\TestingFramework\Core\Functional\Framework\DataHandling\Scenario\DataHandlerFactory;
use TYPO3\TestingFramework\Core\Functional\Framework\DataHandling\Scenario\DataHandlerWriter;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class BackendLayoutRendererLanguageComparisonTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8'],
        'FR' => ['id' => 1, 'title' => 'French', 'locale' => 'fr_FR.UTF8'],
        'DE' => ['id' => 2, 'title' => 'German', 'locale' => 'de_DE.UTF8'],
    ];

    /** colPos 0 */
    private const DEFAULT_LANGUAGE_ELEMENTS = [
        'EN content default #1',
        'EN content default #2',
        'EN content default #3',
    ];

    /** colPos 0 */
    private const CONNECTED_TRANSLATIONS = [
        'FR connected translation #1',
        'FR connected translation #2',
    ];

    /** colPos 0 */
    private const FREE_MODE_ELEMENTS = [
        'DE free content #1',
        'DE free content #2',
        'DE free content #3',
        'DE free content #5',
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
                $scenarioFile = __DIR__ . '/../Fixtures/LanguageComparisonScenario.yaml';
                $factory = DataHandlerFactory::fromYamlFile($scenarioFile);
                $writer = DataHandlerWriter::withBackendUser($this->backendUser);
                $writer->invokeFactory($factory);
                static::failIfArrayIsNotEmpty($writer->getErrors());
            },
            function () {
                $this->backendUser = $this->setUpBackendUser(1);
                $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($this->backendUser);
            }
        );
        $this->writeSiteConfiguration(
            'test-site',
            $this->buildSiteConfiguration(1000, '/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/'),
                $this->buildLanguageConfiguration('FR', '/fr'),
                $this->buildLanguageConfiguration('DE', '/de'),
            ]
        );
    }
    private function renderLanguageComparisonView(array $selectedLanguageIds = [0, 1, 2]): string
    {
        $configuration = [];
        $configuration['__colPosList'] = [0, 1];
        $configuration['__config']['backend_layout.'] = [
            'rows.' => [
                0 => [
                    'columns.' => [
                        0 => [
                            'colPos' => 0,
                            'name' => 'Normal',
                        ],
                        1 => [
                            'colPos' => 1,
                            'name' => 'Right',
                        ],
                    ],
                ],
            ],
        ];
        $backendLayout = new BackendLayout('layout1', 'Layout 1', $configuration);
        $drawingConfiguration = DrawingConfiguration::create($backendLayout, [], PageViewMode::LanguageComparisonView);
        $drawingConfiguration->setSelectedLanguageIds($selectedLanguageIds);

        $site = $this->get(SiteFinder::class)->getSiteByIdentifier('test-site');
        $request = (new ServerRequest('https://example.com/'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withAttribute('site', $site)
            ->withAttribute('route', new Route('path', ['packageName' => 'typo3/cms-backend']));
        $request = $request->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request));

        $pageContext = $this->get(PageContextFactory::class)->createWithLanguages($request, 1100, $selectedLanguageIds, $this->backendUser);
        $pageLayoutContext = new PageLayoutContext($pageContext, $backendLayout, $drawingConfiguration, $request);

        $subject = new BackendLayoutRenderer(
            new BackendViewFactory($this->get(RenderingContextFactory::class), $this->get(PackageManager::class)),
            $this->get(RecordFactory::class)
        );
        return $subject->drawContent($request, $pageLayoutContext, false);
    }

    #[Test]
    public function allContentElementsAreRendered(): void
    {
        $html = $this->renderLanguageComparisonView();

        self::assertStringContainsString('EN content default #1', $html);
        self::assertStringContainsString('EN content default #2', $html);
        self::assertStringContainsString('EN content default #3', $html);
        self::assertStringContainsString('FR connected translation #1', $html);
        self::assertStringContainsString('FR connected translation #2', $html);
        self::assertStringContainsString('DE free content #1', $html);
        self::assertStringContainsString('DE free content #2', $html);
        self::assertStringContainsString('DE free content #3', $html);
        self::assertStringContainsString('DE free content #4', $html);
        self::assertStringContainsString('DE free content #5', $html);
    }

    #[Test]
    public function connectedTranslationIsRenderedInSameRowAsDefaultLanguageParent(): void
    {
        $html = $this->renderLanguageComparisonView();

        $firstParentRow = $this->tableRowContaining('EN content default #1', $html);
        self::assertNotSame('', $firstParentRow);
        self::assertStringContainsString('FR connected translation #1', $firstParentRow);
        self::assertStringNotContainsString('FR connected translation #2', $firstParentRow);
        self::assertStringNotContainsString('EN content default #2', $firstParentRow);

        $secondParentRow = $this->tableRowContaining('EN content default #2', $html);
        self::assertNotSame('', $secondParentRow);
        self::assertStringContainsString('FR connected translation #2', $secondParentRow);
        self::assertStringNotContainsString('FR connected translation #1', $secondParentRow);
        self::assertStringNotContainsString('EN content default #3', $secondParentRow);
    }

    #[Test]
    public function defaultLanguageElementWithoutConnectedTranslationRendersEmptyTranslationCell(): void
    {
        $html = $this->renderLanguageComparisonView();

        $orphanRow = $this->tableRowContaining('EN content default #3', $html);
        self::assertNotSame('', $orphanRow);
        self::assertStringNotContainsString('FR connected translation', $orphanRow);
        self::assertSame(2, substr_count($orphanRow, '<td'));
    }

    #[Test]
    public function freeModeContentIsRenderedInSingleCellSpanningAllDefaultLanguageRows(): void
    {
        $html = $this->renderLanguageComparisonView();

        $freeModeCell = $this->tableCellContaining('DE free content #1', $html);
        self::assertNotSame('', $freeModeCell);
        self::assertStringContainsString('DE free content #2', $freeModeCell);
        self::assertStringContainsString('DE free content #3', $freeModeCell);
        self::assertStringContainsString('DE free content #5', $freeModeCell);
        self::assertNotNull($this->rowSpanOf($freeModeCell));

        $firstRow = $this->tableRowContaining('EN content default #1', $html);
        self::assertStringContainsString('DE free content #1', $firstRow);
    }

    #[Test]
    public function rowSpanIsDerivedFromDefaultLanguageColumnOnly(): void
    {
        $html = $this->renderLanguageComparisonView();

        $defaultLanguageRowCount = $this->countTableRowsContainingAnyOf(self::DEFAULT_LANGUAGE_ELEMENTS, $html);
        self::assertSame(count(self::DEFAULT_LANGUAGE_ELEMENTS), $defaultLanguageRowCount);

        $connectedElementCount = $this->countElementsPresentIn(self::CONNECTED_TRANSLATIONS, $html);
        self::assertSame(count(self::CONNECTED_TRANSLATIONS), $connectedElementCount);
        self::assertLessThan($defaultLanguageRowCount, $connectedElementCount);

        $freeModeCell = $this->tableCellContaining('DE free content #1', $html);
        self::assertNotSame('', $freeModeCell);
        $freeModeElementCount = $this->countElementsPresentIn(self::FREE_MODE_ELEMENTS, $freeModeCell);
        self::assertSame(count(self::FREE_MODE_ELEMENTS), $freeModeElementCount);
        self::assertGreaterThan($defaultLanguageRowCount, $freeModeElementCount);

        self::assertSame($defaultLanguageRowCount, $this->rowSpanOf($freeModeCell));
    }

    #[Test]
    public function freeModeContentIsRenderedWhenDefaultLanguageColumnIsEmpty(): void
    {
        $html = $this->renderLanguageComparisonView();

        self::assertStringContainsString('DE free content #4', $html);
    }

    #[Test]
    public function rowSpanIsCalculatedPerColumnPosition(): void
    {
        $html = $this->renderLanguageComparisonView();

        $freeModeCell = $this->tableCellContaining('DE free content #4', $html);
        self::assertNotSame('', $freeModeCell);
        self::assertStringContainsString('data-colpos="1"', $freeModeCell);
        self::assertNull($this->rowSpanOf($freeModeCell));
    }

    /**
     * @return array<string, array{0: int[], 1: bool}>
     */
    public static function defaultLanguageGroupingDataProvider(): array
    {
        return [
            'connected mode language only' => [[0, 1], false],
            'connected and free mode language' => [[0, 1, 2], false],
            'free mode language only' => [[0, 2], true],
        ];
    }

    /**
     * @param int[] $selectedLanguageIds
     */
    #[DataProvider('defaultLanguageGroupingDataProvider')]
    #[Test]
    public function defaultLanguageElementsShareOneRowOnlyWithoutConnectedLanguage(array $selectedLanguageIds, bool $expectedSharedRow): void
    {
        $html = $this->renderLanguageComparisonView($selectedLanguageIds);
        $contentRow = $this->tableRowContaining('EN content default #1', $html);

        self::assertNotSame('', $contentRow);
        self::assertSame($expectedSharedRow, str_contains($contentRow, 'EN content default #2'));
        self::assertSame($expectedSharedRow, str_contains($contentRow, 'EN content default #3'));
        self::assertSame(
            $expectedSharedRow ? 1 : count(self::DEFAULT_LANGUAGE_ELEMENTS),
            $this->countTableRowsContainingAnyOf(self::DEFAULT_LANGUAGE_ELEMENTS, $html)
        );
    }

    /**
     * @return array<string, array{0: int[], 1: int}>
     */
    public static function contentRowCellCountDataProvider(): array
    {
        return [
            'connected mode language only' => [[0, 1], 2],
            'connected and free mode language' => [[0, 1, 2], 3],
            'free mode language only' => [[0, 2], 2],
        ];
    }

    /**
     * @param int[] $selectedLanguageIds
     */
    #[DataProvider('contentRowCellCountDataProvider')]
    #[Test]
    public function firstContentRowHasOneCellPerLanguageColumn(array $selectedLanguageIds, int $expectedCellCount): void
    {
        $contentRow = $this->tableRowContaining('EN content default #1', $this->renderLanguageComparisonView($selectedLanguageIds));

        self::assertNotSame('', $contentRow);
        self::assertSame($expectedCellCount, substr_count($contentRow, '<td'));
    }

    #[Test]
    public function allContentElementsAreRenderedWithFreeModeLanguagesOnly(): void
    {
        $html = $this->renderLanguageComparisonView([0, 2]);

        self::assertStringContainsString('EN content default #1', $html);
        self::assertStringContainsString('EN content default #2', $html);
        self::assertStringContainsString('EN content default #3', $html);
        self::assertStringContainsString('DE free content #1', $html);
        self::assertStringContainsString('DE free content #2', $html);
        self::assertStringContainsString('DE free content #3', $html);
        self::assertStringContainsString('DE free content #4', $html);
        self::assertStringContainsString('DE free content #5', $html);
        self::assertStringNotContainsString('FR connected translation', $html);
    }

    #[Test]
    public function defaultLanguageElementsAreRenderedInSingleCellWithFreeModeLanguagesOnly(): void
    {
        $html = $this->renderLanguageComparisonView([0, 2]);

        $defaultLanguageCell = $this->tableCellContaining('EN content default #1', $html);
        self::assertNotSame('', $defaultLanguageCell);
        self::assertStringContainsString('EN content default #2', $defaultLanguageCell);
        self::assertStringContainsString('EN content default #3', $defaultLanguageCell);
        self::assertStringContainsString('data-colpos="0"', $defaultLanguageCell);
        self::assertNull($this->rowSpanOf($defaultLanguageCell));
    }

    #[Test]
    public function freeModeContentIsRenderedWithoutRowSpanWithFreeModeLanguagesOnly(): void
    {
        $html = $this->renderLanguageComparisonView([0, 2]);

        $freeModeCell = $this->tableCellContaining('DE free content #1', $html);
        self::assertNotSame('', $freeModeCell);
        self::assertStringContainsString('DE free content #2', $freeModeCell);
        self::assertStringContainsString('DE free content #3', $freeModeCell);
        self::assertStringContainsString('DE free content #5', $freeModeCell);
        self::assertNull($this->rowSpanOf($freeModeCell));

        $contentRow = $this->tableRowContaining('EN content default #1', $html);
        self::assertStringContainsString('DE free content #1', $contentRow);
    }

    #[Test]
    public function freeModeContentIsRenderedWhenDefaultLanguageColumnIsEmptyWithFreeModeLanguagesOnly(): void
    {
        $html = $this->renderLanguageComparisonView([0, 2]);

        $freeModeCell = $this->tableCellContaining('DE free content #4', $html);
        self::assertNotSame('', $freeModeCell);
        self::assertStringContainsString('data-colpos="1"', $freeModeCell);
        self::assertNull($this->rowSpanOf($freeModeCell));

        $contentRow = $this->tableRowContaining('DE free content #4', $html);
        self::assertSame(2, substr_count($contentRow, '<td'));
        self::assertStringNotContainsString('EN content default #1', $contentRow);
    }

    #[Test]
    public function contentIsRenderedWithoutComparisonGridForSingleSelectedLanguage(): void
    {
        $html = $this->renderLanguageComparisonView([0]);

        self::assertStringContainsString('EN content default #1', $html);
        self::assertStringContainsString('EN content default #2', $html);
        self::assertStringContainsString('EN content default #3', $html);
        self::assertStringNotContainsString('FR connected translation', $html);
        self::assertStringNotContainsString('DE free content #1', $html);
    }

    private function tableRowContaining(string $needle, string $html): string
    {
        preg_match_all('#<tr\b[^>]*>.*?</tr>#s', $html, $matches);
        foreach ($matches[0] as $row) {
            if (str_contains($row, $needle)) {
                return $row;
            }
        }
        return '';
    }

    private function tableCellContaining(string $needle, string $html): string
    {
        preg_match_all('#<td\b[^>]*>.*?</td>#s', $html, $matches);
        foreach ($matches[0] as $cell) {
            if (str_contains($cell, $needle)) {
                return $cell;
            }
        }
        return '';
    }

    private function countTableRowsContainingAnyOf(array $needles, string $html): int
    {
        preg_match_all('#<tr\b[^>]*>.*?</tr>#s', $html, $matches);
        $rowCount = 0;
        foreach ($matches[0] as $row) {
            foreach ($needles as $needle) {
                if (str_contains($row, $needle)) {
                    $rowCount++;
                    break;
                }
            }
        }
        return $rowCount;
    }

    private function countElementsPresentIn(array $needles, string $haystack): int
    {
        $elementCount = 0;
        foreach ($needles as $needle) {
            if (str_contains($haystack, $needle)) {
                $elementCount++;
            }
        }
        return $elementCount;
    }

    private function rowSpanOf(string $cell): ?int
    {
        if (preg_match('#^<td\b[^>]*\srowspan="(\d+)"#', $cell, $matches) === 1) {
            return (int)$matches[1];
        }
        return null;
    }
}
