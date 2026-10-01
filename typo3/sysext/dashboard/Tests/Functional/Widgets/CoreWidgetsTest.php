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

namespace TYPO3\CMS\Dashboard\Tests\Functional\Widgets;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Routing\Route;
use TYPO3\CMS\Backend\View\BackendViewFactory;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Settings\Settings;
use TYPO3\CMS\Dashboard\Widgets\BarChartWidget;
use TYPO3\CMS\Dashboard\Widgets\ChartDataProviderInterface;
use TYPO3\CMS\Dashboard\Widgets\CtaWidget;
use TYPO3\CMS\Dashboard\Widgets\DoughnutChartWidget;
use TYPO3\CMS\Dashboard\Widgets\ListDataProviderInterface;
use TYPO3\CMS\Dashboard\Widgets\ListWidget;
use TYPO3\CMS\Dashboard\Widgets\NumberWithIconDataProviderInterface;
use TYPO3\CMS\Dashboard\Widgets\NumberWithIconWidget;
use TYPO3\CMS\Dashboard\Widgets\T3GeneralInformationWidget;
use TYPO3\CMS\Dashboard\Widgets\WidgetConfiguration;
use TYPO3\CMS\Dashboard\Widgets\WidgetContext;
use TYPO3\CMS\Dashboard\Widgets\WidgetRendererInterface;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class CoreWidgetsTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['dashboard'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);
    }

    public static function widgetsRenderTheirContentDataProvider(): \Generator
    {
        yield 'list' => [
            static fn(BackendViewFactory $viewFactory) => new ListWidget(
                self::createConfiguration(),
                new class implements ListDataProviderInterface {
                    public function getItems(): array
                    {
                        return ['list item'];
                    }
                },
                $viewFactory,
            ),
            'list item',
        ];
        yield 'cta' => [
            static fn(BackendViewFactory $viewFactory) => new CtaWidget(self::createConfiguration(), $viewFactory, null, ['text' => 'cta text']),
            'cta text',
        ];
        yield 'number with icon' => [
            static fn(BackendViewFactory $viewFactory) => new NumberWithIconWidget(
                self::createConfiguration(),
                new class implements NumberWithIconDataProviderInterface {
                    public function getNumber(): int
                    {
                        return 4711;
                    }
                },
                $viewFactory,
                ['icon' => 'content-widget-number', 'title' => 'number title'],
            ),
            '4711',
        ];
        yield 'general information' => [
            static fn(BackendViewFactory $viewFactory) => new T3GeneralInformationWidget(self::createConfiguration(), $viewFactory),
            'TYPO3 CMS',
        ];
        yield 'bar chart' => [
            static fn(BackendViewFactory $viewFactory) => new BarChartWidget(self::createConfiguration(), self::createChartDataProvider(), $viewFactory),
            'widget-chart',
        ];
        yield 'doughnut chart' => [
            static fn(BackendViewFactory $viewFactory) => new DoughnutChartWidget(self::createConfiguration(), self::createChartDataProvider(), $viewFactory),
            'widget-chart',
        ];
    }

    #[Test]
    #[DataProvider('widgetsRenderTheirContentDataProvider')]
    public function widgetsRenderTheirContent(\Closure $widgetFactory, string $expectedContent): void
    {
        $subject = $widgetFactory($this->get(BackendViewFactory::class));

        self::assertInstanceOf(WidgetRendererInterface::class, $subject);
        $result = $subject->renderWidget($this->createContext());
        self::assertStringContainsString($expectedContent, $result->content);
        self::assertFalse($result->refreshable);
        self::assertSame([], $subject->getSettingsDefinitions());
    }

    #[Test]
    public function widgetIsRefreshableIfOptionIsSet(): void
    {
        $subject = new CtaWidget(self::createConfiguration(), $this->get(BackendViewFactory::class), null, ['refreshAvailable' => true]);

        self::assertTrue($subject->renderWidget($this->createContext())->refreshable);
    }

    private function createContext(): WidgetContext
    {
        $normalizedParams = self::createStub(NormalizedParams::class);
        $normalizedParams->method('getSitePath')->willReturn('/');
        $request = new ServerRequest('https://example.com/typo3/')
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withAttribute('normalizedParams', $normalizedParams)
            ->withAttribute('route', new Route('path', ['packageName' => 'typo3/cms-dashboard']));
        return new WidgetContext('test', [], self::createConfiguration(), new Settings([]), $request);
    }

    private static function createConfiguration(): WidgetConfiguration
    {
        return new WidgetConfiguration('test', 'test', [], 'Test', 'Test', 'content-widget-list', 'small', 'small');
    }

    private static function createChartDataProvider(): ChartDataProviderInterface
    {
        return new class implements ChartDataProviderInterface {
            public function getChartData(): array
            {
                return ['labels' => [], 'datasets' => []];
            }
        };
    }
}
