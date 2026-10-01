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

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Routing\Route;
use TYPO3\CMS\Backend\View\BackendViewFactory;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Pagination\ArrayPaginator;
use TYPO3\CMS\Core\Pagination\PaginatorInterface;
use TYPO3\CMS\Core\Settings\Settings;
use TYPO3\CMS\Dashboard\Widgets\ListDataProviderInterface;
use TYPO3\CMS\Dashboard\Widgets\ListWidget;
use TYPO3\CMS\Dashboard\Widgets\PaginatedListDataProviderInterface;
use TYPO3\CMS\Dashboard\Widgets\WidgetConfiguration;
use TYPO3\CMS\Dashboard\Widgets\WidgetContext;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class ListWidgetTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['dashboard'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);
    }

    #[Test]
    public function widgetWithoutPaginatedProviderRendersAllItemsWithoutPagination(): void
    {
        $result = $this->render(new class implements ListDataProviderInterface {
            public function getItems(): array
            {
                return ['item 1', 'item 2', 'item 3', 'item 4'];
            }
        });

        self::assertStringContainsString('item 1', $result);
        self::assertStringContainsString('item 4', $result);
        self::assertStringNotContainsString('widget-pagination', $result);
    }

    #[Test]
    public function widgetWithoutPaginatedProviderHasNoSettings(): void
    {
        $subject = $this->createWidget(new class implements ListDataProviderInterface {
            public function getItems(): array
            {
                return [];
            }
        });

        self::assertSame([], $subject->getSettingsDefinitions());
    }

    #[Test]
    public function firstPageIsRenderedWithoutPageArgument(): void
    {
        $result = $this->render($this->createPaginatedProvider());

        self::assertStringContainsString('item 1<', $result);
        self::assertStringContainsString('item 3<', $result);
        self::assertStringNotContainsString('item 4<', $result);
        self::assertStringContainsString('widget-pagination', $result);
        self::assertMatchesRegularExpression('/data-widget-pagination-direction="previous"[^>]*disabled/s', $result);
        self::assertDoesNotMatchRegularExpression('/data-widget-pagination-direction="next"[^>]*disabled/s', $result);
        self::assertStringContainsString('data-widget-page="2"', $result);
    }

    #[Test]
    public function requestedPageIsRendered(): void
    {
        $result = $this->render($this->createPaginatedProvider(), ['page' => '2']);

        self::assertStringNotContainsString('item 3<', $result);
        self::assertStringContainsString('item 4<', $result);
        self::assertStringContainsString('item 6<', $result);
        self::assertStringNotContainsString('item 7<', $result);
        self::assertStringContainsString('data-widget-page="1"', $result);
        self::assertStringContainsString('data-widget-page="3"', $result);
    }

    #[Test]
    public function lastPageDisablesNextButton(): void
    {
        $result = $this->render($this->createPaginatedProvider(), ['page' => '3']);

        self::assertStringContainsString('item 7<', $result);
        self::assertMatchesRegularExpression('/data-widget-pagination-direction="next"[^>]*disabled/s', $result);
        self::assertDoesNotMatchRegularExpression('/data-widget-pagination-direction="previous"[^>]*disabled/s', $result);
    }

    #[Test]
    public function pageBeyondLastPageFallsBackToLastPage(): void
    {
        $result = $this->render($this->createPaginatedProvider(), ['page' => '99']);

        self::assertStringContainsString('item 7<', $result);
        self::assertStringNotContainsString('item 6<', $result);
    }

    #[Test]
    public function invalidPageFallsBackToFirstPage(): void
    {
        $result = $this->render($this->createPaginatedProvider(), ['page' => '-5']);

        self::assertStringContainsString('item 1<', $result);
        self::assertStringNotContainsString('item 4<', $result);
    }

    #[Test]
    public function paginationIsOmittedIfAllItemsFitOnOnePage(): void
    {
        $result = $this->render($this->createPaginatedProvider(), [], ['itemsPerPage' => 10]);

        self::assertStringContainsString('item 7<', $result);
        self::assertStringNotContainsString('widget-pagination', $result);
    }

    #[Test]
    public function itemsPerPageSettingDefinesPageSize(): void
    {
        $result = $this->render($this->createPaginatedProvider(), [], ['itemsPerPage' => 2]);

        self::assertStringContainsString('item 2<', $result);
        self::assertStringNotContainsString('item 3<', $result);
        self::assertStringContainsString('Page 1 of 4', $result);
    }

    #[Test]
    public function itemsPerPageOptionIsUsedIfSettingIsMissing(): void
    {
        $result = $this->render($this->createPaginatedProvider(), [], [], ['itemsPerPage' => 5]);

        self::assertStringContainsString('item 5<', $result);
        self::assertStringNotContainsString('item 6<', $result);
        self::assertStringContainsString('Page 1 of 2', $result);
    }

    #[Test]
    public function itemsPerPageSettingIsDefinedForPaginatedProvider(): void
    {
        $definitions = $this->createWidget($this->createPaginatedProvider())->getSettingsDefinitions();

        self::assertCount(1, $definitions);
        self::assertSame('itemsPerPage', $definitions[0]->key);
        self::assertSame(10, $definitions[0]->default);
        self::assertFalse($definitions[0]->readonly);
    }

    #[Test]
    public function itemsPerPageOptionIsDefaultAndMakesSettingReadonly(): void
    {
        $definitions = $this->createWidget($this->createPaginatedProvider(), ['itemsPerPage' => 7])->getSettingsDefinitions();

        self::assertSame(7, $definitions[0]->default);
        self::assertTrue($definitions[0]->readonly);
    }

    private function createPaginatedProvider(): PaginatedListDataProviderInterface
    {
        return new class implements PaginatedListDataProviderInterface {
            public function getPaginator(int $currentPage, int $itemsPerPage): PaginatorInterface
            {
                $items = [];
                for ($i = 1; $i <= 7; $i++) {
                    $items[] = 'item ' . $i;
                }
                return new ArrayPaginator($items, $currentPage, $itemsPerPage);
            }
        };
    }

    private function render(
        ListDataProviderInterface|PaginatedListDataProviderInterface $provider,
        array $queryParams = [],
        array $settings = ['itemsPerPage' => 3],
        array $options = [],
    ): string {
        $normalizedParams = self::createStub(NormalizedParams::class);
        $normalizedParams->method('getSitePath')->willReturn('/');
        $request = new ServerRequest('https://example.com/typo3/')
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withAttribute('normalizedParams', $normalizedParams)
            ->withAttribute('route', new Route('path', ['packageName' => 'typo3/cms-dashboard']))
            ->withQueryParams($queryParams);
        $context = new WidgetContext('test', [], $this->createConfiguration(), new Settings($settings), $request);
        return $this->createWidget($provider, $options)->renderWidget($context)->content;
    }

    private function createWidget(ListDataProviderInterface|PaginatedListDataProviderInterface $provider, array $options = []): ListWidget
    {
        return new ListWidget(
            $this->createConfiguration(),
            $provider,
            $this->get(BackendViewFactory::class),
            null,
            $options,
        );
    }

    private function createConfiguration(): WidgetConfiguration
    {
        return new WidgetConfiguration('test', 'test', [], 'Test', 'Test', 'content-widget-list', 'small', 'small');
    }
}
