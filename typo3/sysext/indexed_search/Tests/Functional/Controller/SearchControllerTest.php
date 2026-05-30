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

namespace TYPO3\CMS\IndexedSearch\Tests\Functional\Controller;

use PHPUnit\Framework\Attributes\Test;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\DependencyInjection\Container;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\EventDispatcher\ListenerProvider;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Pagination\PaginationInterface;
use TYPO3\CMS\Core\Pagination\SimplePagination;
use TYPO3\CMS\Core\Pagination\SlidingWindowPagination;
use TYPO3\CMS\Core\TypoScript\TypoScriptService;
use TYPO3\CMS\Core\View\ViewInterface;
use TYPO3\CMS\Extbase\Mvc\ExtbaseRequestParameters;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Frontend\Typolink\LinkFactory;
use TYPO3\CMS\IndexedSearch\Controller\SearchController;
use TYPO3\CMS\IndexedSearch\Domain\Repository\IndexSearchRepository;
use TYPO3\CMS\IndexedSearch\Event\AfterSearchResultSetsAreGeneratedEvent;
use TYPO3\CMS\IndexedSearch\Lexer;
use TYPO3\CMS\IndexedSearch\Pagination\SlicePaginator;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class SearchControllerTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = [
        'indexed_search',
    ];

    #[Test]
    public function afterSearchResultSetsAreGeneratedEventIsDispatchedAndAllowsReplacingPaginationInAllResultSets(): void
    {
        /** @var Container $container */
        $container = $this->get('service_container');

        /** @var AfterSearchResultSetsAreGeneratedEvent|null $receivedEvent */
        $receivedEvent = null;
        $listenerInvocations = 0;

        $container->set(
            'modify-search-result-sets-listener',
            static function (AfterSearchResultSetsAreGeneratedEvent $event) use (&$receivedEvent, &$listenerInvocations): void {
                $listenerInvocations++;
                $receivedEvent = $event;

                $modifiedResultSets = $event->getResultSets();
                foreach ($modifiedResultSets as $key => $resultSet) {
                    if (($resultSet['pagination'] ?? null) instanceof SimplePagination) {
                        /** @var SimplePagination $pagination */
                        $pagination = $resultSet['pagination'];
                        $modifiedResultSets[$key]['pagination'] = new SlidingWindowPagination($pagination->getPaginator(), 7);
                    }
                }
                $event->setResultSets($modifiedResultSets);
            }
        );

        $listenerProvider = $container->get(ListenerProvider::class);
        $listenerProvider->addListener(
            AfterSearchResultSetsAreGeneratedEvent::class,
            'modify-search-result-sets-listener'
        );

        $view = $this->createMock(ViewInterface::class);
        $serverRequest = (new ServerRequest('https://example.com/', 'GET'))
            ->withAttribute('extbase', new ExtbaseRequestParameters());
        $extbaseRequest = new Request($serverRequest);

        $controller = new class (
            $this->createMock(Context::class),
            $this->createMock(IndexSearchRepository::class),
            $this->createMock(TypoScriptService::class),
            $this->createMock(Lexer::class),
            $this->createMock(LinkFactory::class),
            $this->createMock(PageRepository::class),
        ) extends SearchController {
            public function initializeForTest(ViewInterface $view, Request $request): void
            {
                $this->view = $view;
                $this->request = $request;
            }

            public function callDispatchAfterSearchResultSetsAreGeneratedEvent(array $resultSets, array $searchData, array $searchWords): array
            {
                return $this->dispatchAfterSearchResultSetsAreGeneratedEvent($resultSets, $searchData, $searchWords);
            }
        };

        $controller->injectEventDispatcher($this->get(EventDispatcherInterface::class));
        $controller->initializeForTest($view, $extbaseRequest);

        $searchData = [
            'pointer' => 0,
            'numberOfResults' => 10,
            'sections' => '',
            'group' => '',
            'extResume' => 0,
        ];
        $searchWords = [
            ['sword' => 'typo3'],
        ];
        $resultSets = [
            -1 => [
                'pagination' => new SimplePagination(new SlicePaginator([], 1, 20, 10)),
                'count' => 1,
            ],
            13 => [
                'pagination' => new SimplePagination(new SlicePaginator([], 2, 20, 10)),
                'count' => 2,
            ],
        ];

        $result = $controller->callDispatchAfterSearchResultSetsAreGeneratedEvent($resultSets, $searchData, $searchWords);

        self::assertInstanceOf(AfterSearchResultSetsAreGeneratedEvent::class, $receivedEvent);
        self::assertSame(1, $listenerInvocations);
        self::assertSame($searchData, $receivedEvent->getSearchData());
        self::assertSame($searchWords, $receivedEvent->getSearchWords());
        self::assertSame($view, $receivedEvent->getView());
        self::assertSame($extbaseRequest, $receivedEvent->getRequest());
        self::assertInstanceOf(SlidingWindowPagination::class, $result[-1]['pagination']);
        self::assertInstanceOf(SlidingWindowPagination::class, $result[13]['pagination']);
    }

    #[Test]
    public function paginationSettingSwitchesBetweenSimpleAndSlidingWindowPagination(): void
    {
        $controller = new class (
            $this->createMock(Context::class),
            $this->createMock(IndexSearchRepository::class),
            $this->createMock(TypoScriptService::class),
            $this->createMock(Lexer::class),
            $this->createMock(LinkFactory::class),
            $this->createMock(PageRepository::class),
        ) extends SearchController {
            public function callBuildPagination(array $searchData, array $rows, int $count): PaginationInterface
            {
                return $this->buildPagination($searchData, $rows, $count);
            }

            public function setSettingsForTest(array $settings): void
            {
                $this->settings = $settings;
            }
        };

        $searchData = [
            'pointer' => 10,
            'numberOfResults' => 10,
        ];
        $resultRows = array_fill(0, 10, ['item_title' => 'Result']);
        $resultCount = 200;

        $controller->setSettingsForTest([
            'pagination_type' => 'simple',
            'page_links' => 5,
        ]);
        $simplePagination = $controller->callBuildPagination($searchData, $resultRows, $resultCount);
        self::assertInstanceOf(SimplePagination::class, $simplePagination);
        self::assertCount(20, $simplePagination->getAllPageNumbers());

        $controller->setSettingsForTest([
            'pagination_type' => 'slidingWindow',
            'page_links' => 5,
        ]);
        $slidingWindowPagination = $controller->callBuildPagination($searchData, $resultRows, $resultCount);
        self::assertInstanceOf(SlidingWindowPagination::class, $slidingWindowPagination);
        self::assertSame([9, 10, 11, 12, 13], $slidingWindowPagination->getAllPageNumbers());

        $controller->setSettingsForTest([
            'pagination_type' => 'invalid',
        ]);
        $fallbackPagination = $controller->callBuildPagination($searchData, $resultRows, $resultCount);
        self::assertInstanceOf(SimplePagination::class, $fallbackPagination);
        self::assertCount(20, $fallbackPagination->getAllPageNumbers());
    }
}
