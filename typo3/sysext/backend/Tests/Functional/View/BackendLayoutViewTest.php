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

namespace TYPO3\CMS\Backend\Tests\Functional\View;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Backend\View\BackendLayout\DataProviderCollection;
use TYPO3\CMS\Backend\View\BackendLayoutView;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Domain\Page;
use TYPO3\CMS\Core\Domain\PageAncestry;
use TYPO3\CMS\Core\Domain\PageAncestryResolver;
use TYPO3\CMS\Core\Page\PageLayoutResolver;
use TYPO3\CMS\Core\TypoScript\TypoScriptStringFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class BackendLayoutViewTest extends FunctionalTestCase
{
    protected bool $initializeDatabase = false;

    private function createSubject(int $pageId, array $rootLine): BackendLayoutView
    {
        $records = array_values(array_filter($rootLine, static fn(array $record): bool => $record['uid'] > 0));
        $pageAncestryResolver = self::createStub(PageAncestryResolver::class);
        $pageAncestryResolver->method('resolve')->willReturn(
            new PageAncestry($pageId, $records, [], static fn(array $record): Page => new Page($record))
        );
        return new BackendLayoutView(
            $this->get(EventDispatcherInterface::class),
            $this->get(CacheManager::class)->getCache('runtime'),
            $this->get(DataProviderCollection::class),
            $this->get(TypoScriptStringFactory::class),
            $this->get(PageLayoutResolver::class),
            $this->get(ConnectionPool::class),
            $pageAncestryResolver,
            $this->get(Context::class),
        );
    }

    public static function selectedCombinedIdentifierIsDeterminedDataProvider(): array
    {
        return [
            'first level w/o layout' => [
                '0',
                ['uid' => 1, 'pid' => 0, 'backend_layout' => '0', 'backend_layout_next_level' => '0'],
                [
                    ['uid' => 1, 'pid' => 0, 'backend_layout' => '0', 'backend_layout_next_level' => '0'],
                    ['uid' => 0, 'pid' => null],
                ],
            ],
            'first level with layout' => [
                '1',
                ['uid' => 1, 'pid' => 0, 'backend_layout' => '1', 'backend_layout_next_level' => '0'],
                [
                    ['uid' => 1, 'pid' => 0, 'backend_layout' => '1', 'backend_layout_next_level' => '0'],
                    ['uid' => 0, 'pid' => null],
                ],
            ],
            'first level with provided layout' => [
                'mine_current',
                ['uid' => 1, 'pid' => 0, 'backend_layout' => 'mine_current', 'backend_layout_next_level' => '0'],
                [
                    ['uid' => 1, 'pid' => 0, 'backend_layout' => 'mine_current', 'backend_layout_next_level' => '0'],
                    ['uid' => 0, 'pid' => null],
                ],
            ],
            'first level with next layout' => [
                '0',
                ['uid' => 1, 'pid' => 0, 'backend_layout' => '0', 'backend_layout_next_level' => '1'],
                [
                    ['uid' => 1, 'pid' => 0, 'backend_layout' => '0', 'backend_layout_next_level' => '1'],
                    ['uid' => 0, 'pid' => null],
                ],
            ],
            'first level with provided next layout' => [
                '0',
                ['uid' => 1, 'pid' => 0, 'backend_layout' => '0', 'backend_layout_next_level' => 'mine_next'],
                [
                    ['uid' => 1, 'pid' => 0, 'backend_layout' => '0', 'backend_layout_next_level' => 'mine_next'],
                    ['uid' => 0, 'pid' => null],
                ],
            ],
            'second level w/o layout, first level with layout' => [
                '0',
                ['uid' => 2, 'pid' => 1, 'backend_layout' => '0', 'backend_layout_next_level' => '0'],
                [
                    ['uid' => 2, 'pid' => 1, 'backend_layout' => '0', 'backend_layout_next_level' => '0'],
                    ['uid' => 1, 'pid' => 0, 'backend_layout' => '1', 'backend_layout_next_level' => '0'],
                    ['uid' => 0, 'pid' => null],
                ],
            ],
            'second level w/o layout, first level with next layout' => [
                '1',
                ['uid' => 2, 'pid' => 1, 'backend_layout' => '0', 'backend_layout_next_level' => '0'],
                [
                    ['uid' => 2, 'pid' => 1, 'backend_layout' => '0', 'backend_layout_next_level' => '0'],
                    ['uid' => 1, 'pid' => 0, 'backend_layout' => '0', 'backend_layout_next_level' => '1'],
                    ['uid' => 0, 'pid' => null],
                ],
            ],
            'second level with layout, first level with next layout' => [
                '2',
                ['uid' => 2, 'pid' => 1, 'backend_layout' => '2', 'backend_layout_next_level' => '0'],
                [
                    ['uid' => 2, 'pid' => 1, 'backend_layout' => '2', 'backend_layout_next_level' => '0'],
                    ['uid' => 1, 'pid' => 0, 'backend_layout' => '0', 'backend_layout_next_level' => '1'],
                    ['uid' => 0, 'pid' => null],
                ],
            ],
            'second level with layouts, first level resetting all layouts' => [
                '1',
                ['uid' => 2, 'pid' => 1, 'backend_layout' => '1', 'backend_layout_next_level' => '1'],
                [
                    ['uid' => 2, 'pid' => 1, 'backend_layout' => '1', 'backend_layout_next_level' => '1'],
                    ['uid' => 1, 'pid' => 0, 'backend_layout' => '-1', 'backend_layout_next_level' => '-1'],
                    ['uid' => 0, 'pid' => null],
                ],
            ],
            'second level with provided layouts, first level resetting all layouts' => [
                'mine_current',
                ['uid' => 2, 'pid' => 1, 'backend_layout' => 'mine_current', 'backend_layout_next_level' => 'mine_next'],
                [
                    ['uid' => 2, 'pid' => 1, 'backend_layout' => 'mine_current', 'backend_layout_next_level' => 'mine_next'],
                    ['uid' => 1, 'pid' => 0, 'backend_layout' => '-1', 'backend_layout_next_level' => '-1'],
                    ['uid' => 0, 'pid' => null],
                ],
            ],
            'second level resetting layout, first level with next layout' => [
                false,
                ['uid' => 2, 'pid' => 1, 'backend_layout' => '-1', 'backend_layout_next_level' => '0'],
                [
                    ['uid' => 2, 'pid' => 1, 'backend_layout' => '-1', 'backend_layout_next_level' => '0'],
                    ['uid' => 1, 'pid' => 0, 'backend_layout' => '0', 'backend_layout_next_level' => '1'],
                    ['uid' => 0, 'pid' => null],
                ],
            ],
            'second level resetting next layout, first level with next layout' => [
                '1',
                ['uid' => 2, 'pid' => 1, 'backend_layout' => '0', 'backend_layout_next_level' => '-1'],
                [
                    ['uid' => 2, 'pid' => 1, 'backend_layout' => '0', 'backend_layout_next_level' => '-1'],
                    ['uid' => 1, 'pid' => 0, 'backend_layout' => '0', 'backend_layout_next_level' => '1'],
                    ['uid' => 0, 'pid' => null],
                ],
            ],
            'third level w/o layout, second level resetting layout, first level with next layout' => [
                '1',
                ['uid' => 3, 'pid' => 2, 'backend_layout' => '0', 'backend_layout_next_level' => '0'],
                [
                    ['uid' => 3, 'pid' => 2, 'backend_layout' => '0', 'backend_layout_next_level' => '0'],
                    ['uid' => 2, 'pid' => 1, 'backend_layout' => '-1', 'backend_layout_next_level' => '0'],
                    ['uid' => 1, 'pid' => 0, 'backend_layout' => '0', 'backend_layout_next_level' => '1'],
                    ['uid' => 0, 'pid' => null],
                ],
            ],
            'third level w/o layout, second level resetting next layout, first level with next layout' => [
                false,
                ['uid' => 3, 'pid' => 2, 'backend_layout' => '0', 'backend_layout_next_level' => '0'],
                [
                    ['uid' => 3, 'pid' => 2, 'backend_layout' => '0', 'backend_layout_next_level' => '0'],
                    ['uid' => 2, 'pid' => 1, 'backend_layout' => '0', 'backend_layout_next_level' => '-1'],
                    ['uid' => 1, 'pid' => 0, 'backend_layout' => '0', 'backend_layout_next_level' => '1'],
                    ['uid' => 0, 'pid' => null],
                ],
            ],
            'third level with provided layouts, second level w/o layout, first level resetting layouts' => [
                'mine_current',
                ['uid' => 3, 'pid' => 2, 'backend_layout' => 'mine_current', 'backend_layout_next_level' => 'mine_next'],
                [
                    ['uid' => 3, 'pid' => 2, 'backend_layout' => 'mine_current', 'backend_layout_next_level' => 'mine_next'],
                    ['uid' => 2, 'pid' => 1, 'backend_layout' => '0', 'backend_layout_next_level' => '0'],
                    ['uid' => 1, 'pid' => 0, 'backend_layout' => '-1', 'backend_layout_next_level' => '-1'],
                    ['uid' => 0, 'pid' => null],
                ],
            ],
        ];
    }

    #[DataProvider('selectedCombinedIdentifierIsDeterminedDataProvider')]
    #[Test]
    public function selectedCombinedIdentifierIsDetermined(false|string $expected, array $page, array $rootLine): void
    {
        $pageId = $page['uid'];
        $subject = $this->createSubject((int)$pageId, $rootLine);
        $getSelectedCombinedIdentifierMethod = (new \ReflectionMethod($subject, 'getSelectedCombinedIdentifier'));
        $result = $getSelectedCombinedIdentifierMethod->invoke($subject, $pageId);
        self::assertEquals($expected, $result);
    }
}
