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

namespace TYPO3\CMS\IndexedSearch\Tests\Functional;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\IndexedSearch\Dto\IndexingDataAsString;
use TYPO3\CMS\IndexedSearch\Indexer;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class IndexerTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = [
        'indexed_search',
    ];

    #[Test]
    public function indexerIndexesLoremIpsumContent(): void
    {
        $indexer = $this->get(Indexer::class);
        $indexer->init([
            'id' => 1,
            'type' => 0,
            'MP' => '',
            'staticPageArguments' => null,
            'sys_language_uid' => 0,
            'gr_list' => '0,-1',
            'recordUid' => null,
            'freeIndexUid' => null,
            'freeIndexSetId' => null,
            'index_descrLgd' => 200,
            'index_metatags' => true,
            'index_externals' => false,
            'mtime' => time(),
            'crdate' => time(),
            'content' =>
                '<html>
                <head>
                    <title>Lorem Ipsum</title>
                </head>
                <body>
                    Lorem ipsum dolor sit amet, consectetur adipiscing elit. Ut luctus fringilla tortor sit amet feugiat. Sed mattis semper sapien, in eleifend quam condimentum vel. Aliquam pellentesque feugiat ipsum sed posuere. Morbi pulvinar, eros at fermentum ullamcorper, mauris sem viverra eros, aliquet consequat nulla augue eu sem. Ut placerat, leo sed sagittis suscipit, magna lacus venenatis neque, quis venenatis neque lorem non leo. Sed ullamcorper lorem id ullamcorper commodo. Cras a hendrerit neque. Proin vehicula pretium neque, vitae feugiat justo consequat aliquam. Donec fringilla dolor ac fringilla scelerisque. Suspendisse condimentum egestas odio, vel rutrum neque aliquet sed. Phasellus in sapien quam. Nullam luctus hendrerit dignissim.
                </body>
            </html>',
            'indexedDocTitle' => '',
        ]);
        $indexer->indexTypo3PageContent();
        self::assertCSVDataSet(__DIR__ . '/Fixtures/Indexer/index_dataset.csv');
    }

    #[Test]
    public function indexerDoesNotFailForWordsWithPhashCollision(): void
    {
        $indexer = $this->get(Indexer::class);
        $indexer->init([
            'id' => 1,
            'type' => 0,
            'MP' => '',
            'staticPageArguments' => null,
            'sys_language_uid' => 0,
            'gr_list' => '0,-1',
            'recordUid' => null,
            'freeIndexUid' => null,
            'freeIndexSetId' => null,
            'index_descrLgd' => 200,
            'index_metatags' => true,
            'index_externals' => false,
            'mtime' => time(),
            'crdate' => time(),
            'content' =>
                '<html>
                <head>
                    <title>Test</title>
                </head>
                <body>
                    graf gettogethers abfluss erworbener
                </body>
            </html>',
            'indexedDocTitle' => '',
        ]);

        try {
            $indexer->indexTypo3PageContent();
        } catch (UniqueConstraintViolationException) {
            self::fail('Indexer failed to index words with phash collision');
        }
        self::assertCSVDataSet(__DIR__ . '/Fixtures/Indexer/phash_collision.csv');
    }

    #[Test]
    public function indexerBuildsCorrectWordIndexWhenIndexingWordsTwice(): void
    {
        $indexerConfig = [
            'id' => 1,
            'type' => 0,
            'MP' => '',
            'staticPageArguments' => null,
            'sys_language_uid' => 0,
            'gr_list' => '0,-1',
            'recordUid' => null,
            'freeIndexUid' => null,
            'freeIndexSetId' => null,
            'index_descrLgd' => 200,
            'index_metatags' => true,
            'index_externals' => false,
            'mtime' => time(),
            'crdate' => time(),
            'content' =>
                '<html>
                <head>
                    <title>Test</title>
                </head>
                <body>
                    graf gettogethers abfluss erworbener
                </body>
            </html>',
            'indexedDocTitle' => '',
        ];

        $indexer = $this->get(Indexer::class);
        $indexer->init($indexerConfig);
        $indexer->indexTypo3PageContent();
        self::assertCSVDataSet(__DIR__ . '/Fixtures/Indexer/indexing_words_twice_first.csv');

        $indexer = $this->get(Indexer::class);
        $indexer->init($indexerConfig);
        $indexer->forceIndexing = true;
        $indexer->indexTypo3PageContent();
        self::assertCSVDataSet(__DIR__ . '/Fixtures/Indexer/indexing_words_twice_second.csv');
    }

    #[Test]
    public function bodyDescriptionSubstitutesMultipleSpaceCharactersWithSingleSpace(): void
    {
        $indexingDataDto = new IndexingDataAsString(body: "This is a test body with multiple   spaces and\n\nnewlines that should be normalized.");
        $expected = 'This is a test body with multiple spaces and newlines that should be normalized.';
        $subject = $this->get(Indexer::class);
        $subject->conf = ['index_descrLgd' => 200];
        self::assertSame($expected, $subject->bodyDescription($indexingDataDto));
    }

    #[Test]
    public function bodyDescriptionHandlesPregReplaceFailureGracefully(): void
    {
        // Have a string with invalid UTF-8 that will trigger PREG_BAD_UTF8_ERROR
        // using a byte sequences that cause PCRE to fail with /u modifier.
        $invalidUtf8 = "Valid start \x80\x81\x82 invalid UTF-8 sequence";
        $indexingDataDto = new IndexingDataAsString(body: $invalidUtf8);
        $subject = $this->get(Indexer::class);
        $subject->conf = ['index_descrLgd' => 200];
        self::assertSame($invalidUtf8, $subject->bodyDescription($indexingDataDto));
    }
}
