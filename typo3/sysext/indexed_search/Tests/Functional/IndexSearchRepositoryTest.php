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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\UserAspect;
use TYPO3\CMS\IndexedSearch\Domain\Repository\IndexSearchRepository;
use TYPO3\CMS\IndexedSearch\Indexer;
use TYPO3\CMS\IndexedSearch\Type\MediaType;
use TYPO3\CMS\IndexedSearch\Type\SearchType;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class IndexSearchRepositoryTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = [
        'indexed_search',
    ];

    /**
     * Indexes a "Lorem Ipsum"-page
     * and adds a UserAspect to the context providing a "grlist" used in
     * @see IndexSearchRepository::$frontendUserGroupList.
     */
    protected function setUp(): void
    {
        parent::setUp();
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
        $this->get(Context::class)->setAspect('frontend.user', new UserAspect(null, [0, -1]));
    }

    #[Test]
    public function doSearchReturnsLoremIpsumResults(): void
    {
        $searchRepository = $this->getSearchRepository();
        $searchResults = $searchRepository->doSearch([['sword' => 'lorem']], -1);
        self::assertIsArray($searchResults['resultRows'] ?? false);
        self::assertCount(1, $searchResults['resultRows']);
        self::assertStringContainsStringIgnoringCase('lorem', $searchResults['resultRows'][0]['item_description']);
    }

    #[Test]
    public function doSearchProperlyQuotesSearchWord(): void
    {
        $searchRepository = $this->getSearchRepository();
        $searchResults = $searchRepository->doSearch([['sword' => 'l%rem']], -1);
        self::assertIsNotArray($searchResults['resultRows'] ?? false);
    }

    public static function searchByMediaTypeSetsAppropriateQuerybuilderWhereConditionDataProvider(): \Generator
    {
        yield 'mediaType is "ALL_MEDIA"' => [
            'postInput' => '-1',
            'expected' => MediaType::ALL_MEDIA,
            'expectedSql' => '',
        ];

        yield 'mediaType is "ALL_EXTERNAL"' => [
            'postInput' => '-2',
            'expected' => MediaType::ALL_EXTERNAL,
            'expectedSql' => ' AND IP.item_type <> 0',
        ];

        yield 'mediaType is "INTERNAL_PAGES"' => [
            'postInput' => '0',
            'expected' => MediaType::INTERNAL_PAGES,
            'expectedSql' => ' AND IP.item_type = 0',
        ];

        yield 'empty mediaType is "INTERNAL_PAGES"' => [
            'postInput' => '',
            'expected' => MediaType::INTERNAL_PAGES,
            'expectedSql' => ' AND IP.item_type = 0',
        ];

        yield 'null mediaType is "INTERNAL_PAGES"' => [
            'postInput' => null,
            'expected' => MediaType::INTERNAL_PAGES,
            'expectedSql' => ' AND IP.item_type = 0',
        ];

        yield 'invalid mediaType is "INTERNAL_PAGES"' => [
            'postInput' => '189',
            'expected' => MediaType::INTERNAL_PAGES,
            'expectedSql' => ' AND IP.item_type = 0',
        ];

        yield 'negative invalid mediaType is "INTERNAL_PAGES"' => [
            'postInput' => '-189',
            'expected' => MediaType::INTERNAL_PAGES,
            'expectedSql' => ' AND IP.item_type = 0',
        ];

        yield 'string ppt mediaType is string' => [
            'postInput' => 'ppt',
            'expected' => 'ppt',
            'expectedSql' => ' AND IP.item_type = ppt',
        ];

        yield 'invalid string mediaType is still string' => [
            'postInput' => 'php',
            'expected' => 'php',
            'expectedSql' => ' AND IP.item_type = php',
        ];
    }

    #[DataProvider('searchByMediaTypeSetsAppropriateQuerybuilderWhereConditionDataProvider')]
    #[Test]
    public function searchByMediaTypeSetsAppropriateQuerybuilderWhereCondition(?string $postInput, string|MediaType $expected, string $expectedSql): void
    {
        $searchRepository = $this->get(IndexSearchRepository::class);
        $mediaTypeWhere = new \ReflectionMethod($searchRepository, 'mediaTypeWhere');
        $getMediaType = new \ReflectionProperty($searchRepository, 'mediaType');
        $searchRepositoryDefaultOptions = [
            'defaultOperand' => 0,
            'sections' => 0,
            'mediaType' => $postInput,
            'sortOrder' => 'rank_flag',
            'languageUid' => 'current',
            'sortDesc' => 1,
            'searchType' => SearchType::PART_OF_WORD->value,
            'extResume' => 1,
        ];
        $searchRepository->initialize([], $searchRepositoryDefaultOptions, [], -1);
        self::assertSame($expected, $getMediaType->getValue($searchRepository));
        self::assertSame($expectedSql, preg_replace('@["\'`]@imsU', '', $mediaTypeWhere->invoke($searchRepository)));
    }

    private function getSearchRepository(SearchType $searchType = SearchType::PART_OF_WORD): IndexSearchRepository
    {
        $searchRepository = $this->get(IndexSearchRepository::class);
        $searchRepositoryDefaultOptions = [
            'defaultOperand' => 0,
            'sections' => 0,
            'mediaType' => -1,
            'sortOrder' => 'rank_flag',
            'languageUid' => 'current',
            'sortDesc' => 1,
            'searchType' => $searchType->value,
            'extResume' => 1,
        ];
        $searchRepository->initialize([], $searchRepositoryDefaultOptions, [], -1);
        return $searchRepository;
    }
}
