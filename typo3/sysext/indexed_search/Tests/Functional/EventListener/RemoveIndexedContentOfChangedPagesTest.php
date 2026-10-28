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

namespace TYPO3\CMS\IndexedSearch\Tests\Functional\EventListener;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Cache\Flush\CacheFlushDemand;
use TYPO3\CMS\Core\Cache\Flush\CacheFlusher;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class RemoveIndexedContentOfChangedPagesTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['indexed_search'];

    protected array $configurationToUseInTestInstance = [
        'EXTENSIONS' => [
            'indexed_search' => [
                'deleteFromIndexAfterEditing' => '1',
            ],
        ],
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/RemoveIndexedContent/Records.csv');
    }

    #[Test]
    public function indexedContentOfThePageOfAChangedRecordIsRemoved(): void
    {
        $this->get(CacheFlusher::class)->flush(CacheFlushDemand::forRecord('tt_content', 1));

        self::assertSame(['bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb'], $this->getIndexedPhashes());
    }

    #[Test]
    public function indexedContentIsKeptWhenOnlyPagesAreFlushed(): void
    {
        $this->get(CacheFlusher::class)->flush(CacheFlushDemand::forPageTagsByPageIds(2));

        self::assertSame(['aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa', 'bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb'], $this->getIndexedPhashes());
    }

    /**
     * @return list<string>
     */
    private function getIndexedPhashes(): array
    {
        $phashes = $this->getConnectionPool()->getConnectionForTable('index_phash')
            ->select(['phash'], 'index_phash')
            ->fetchFirstColumn();
        sort($phashes);
        return $phashes;
    }
}
