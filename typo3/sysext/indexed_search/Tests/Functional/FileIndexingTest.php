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
use Symfony\Component\DependencyInjection\Container;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\UserAspect;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Resource\TextExtraction\ExtractedText;
use TYPO3\CMS\Core\Resource\TextExtraction\PlainTextExtractor;
use TYPO3\CMS\Core\Resource\TextExtraction\StructuredTextExtractorInterface;
use TYPO3\CMS\Core\Resource\TextExtraction\TextExtractorRegistry;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\SystemResource\Type\SystemResourceInterface;
use TYPO3\CMS\IndexedSearch\Domain\Repository\IndexSearchRepository;
use TYPO3\CMS\IndexedSearch\Indexer;
use TYPO3\CMS\IndexedSearch\Type\SearchType;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class FileIndexingTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = [
        'indexed_search',
    ];

    protected array $pathsToProvideInTestInstance = [
        'typo3/sysext/indexed_search/Tests/Functional/Fixtures/Files/public.txt' => 'fileadmin/user_upload/public.txt',
        'typo3/sysext/indexed_search/Tests/Functional/Fixtures/Files/document.docx' => 'fileadmin/user_upload/document.docx',
        'typo3/sysext/indexed_search/Tests/Functional/Fixtures/Files/outside.txt' => 'outside.txt',
        'typo3/sysext/indexed_search/Tests/Functional/Fixtures/Files/nonpublic.txt' => 'fileadmin/user_upload/notes.unsupported',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $site = new Site('website-local', 1, ['base' => 'https://example.com/', 'languages' => []]);
        $request = new ServerRequest('https://example.com/', 'GET', null, [], ['HTTP_HOST' => 'example.com', 'SCRIPT_NAME' => '/index.php', 'HTTPS' => 'on'])
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE)
            ->withAttribute('site', $site)
            ->withAttribute('language', $site->getDefaultLanguage());
        $GLOBALS['TYPO3_REQUEST'] = $request->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request));
    }

    public static function linkToFileOfStorageDataProvider(): \Generator
    {
        yield 'relative path' => ['fileadmin/user_upload/public.txt'];
        yield 'absolute path' => ['/fileadmin/user_upload/public.txt'];
        yield 'absolute URL of the site' => ['https://example.com/fileadmin/user_upload/public.txt'];
    }

    #[DataProvider('linkToFileOfStorageDataProvider')]
    #[Test]
    public function textFileOfStorageLinkedFromPageIsIndexedAndFound(string $href): void
    {
        $this->indexPageWithLink($href);

        $rows = $this->getIndexedFileRows();
        self::assertCount(1, $rows);
        self::assertSame('txt', $rows[0]['item_type']);
        self::assertSame($href, $rows[0]['data_filename']);
        self::assertSame('public.txt', $rows[0]['item_title']);
        self::assertSame(0, (int)$rows[0]['externalUrl']);
        self::assertStringContainsString('marmalade', $this->getIndexedFullText());

        $this->get(Context::class)->setAspect('frontend.user', new UserAspect(null, [0, -1]));
        $searchRepository = $this->get(IndexSearchRepository::class);
        $searchRepository->initialize([], [
            'defaultOperand' => 0,
            'sections' => 0,
            'mediaType' => 'txt',
            'sortOrder' => 'rank_flag',
            'languageUid' => 'current',
            'sortDesc' => 1,
            'searchType' => SearchType::PART_OF_WORD->value,
            'extResume' => 1,
        ], -1);
        $searchResults = $searchRepository->doSearch([['sword' => 'quokkas']], -1);
        self::assertIsArray($searchResults['resultRows'] ?? false);
        self::assertCount(1, $searchResults['resultRows']);
        self::assertSame('txt', $searchResults['resultRows'][0]['item_type']);
    }

    #[Test]
    public function documentStoredWithContainerMimeTypeIsIndexed(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/Indexer/docx_with_container_mime_type.csv');

        $this->indexPageWithLink('fileadmin/user_upload/document.docx');

        $rows = $this->getIndexedFileRows();
        self::assertCount(1, $rows);
        self::assertSame('docx', $rows[0]['item_type']);
        self::assertStringContainsString('Wombats', $this->getIndexedFullText());
    }

    #[Test]
    public function fileOutsideOfAnyStorageIsNotIndexed(): void
    {
        self::assertFileExists($this->instancePath . '/outside.txt');

        $this->indexPageWithLink('outside.txt');

        self::assertSame([], $this->getIndexedFileRows());
    }

    #[Test]
    public function linkToNonExistingFileIsNotIndexed(): void
    {
        $this->indexPageWithLink('fileadmin/user_upload/missing.txt');

        self::assertSame([], $this->getIndexedFileRows());
    }

    #[Test]
    public function fileWithIgnoredExtensionIsNotIndexed(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['indexed_search']['ignoreExtensions'] = 'csv, TXT';

        $this->indexPageWithLink('fileadmin/user_upload/public.txt');

        self::assertSame([], $this->getIndexedFileRows());
        self::assertSame(0, $this->countFileIndexEntries('public.txt'));
    }

    #[Test]
    public function linkToFileOfUnsupportedTypeIsNotResolvedThroughFal(): void
    {
        $this->indexPageWithLink('fileadmin/user_upload/notes.unsupported', 'fileadmin/user_upload/public.txt');

        self::assertCount(1, $this->getIndexedFileRows());
        self::assertSame(1, $this->countFileIndexEntries('public.txt'));
        self::assertSame(0, $this->countFileIndexEntries('notes.unsupported'));
    }

    #[Test]
    public function externalUrlIsNotIndexed(): void
    {
        $this->indexPageWithLink('https://example.org/fileadmin/user_upload/public.txt');

        self::assertSame([], $this->getIndexedFileRows());
    }

    #[Test]
    public function unchangedFileIsNotIndexedAgain(): void
    {
        $this->indexPageWithLink('fileadmin/user_upload/public.txt');
        $firstRows = $this->getIndexedFileRows();
        $this->indexPageWithLink('fileadmin/user_upload/public.txt');

        self::assertSame($firstRows, $this->getIndexedFileRows());
    }

    #[Test]
    public function failingTextExtractorDoesNotPreventIndexingOfPageAndOtherFiles(): void
    {
        $failingTextExtractor = new class implements StructuredTextExtractorInterface {
            public function getSupportedMimeTypes(): array
            {
                return ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
            }

            public function extractStructuredText(SystemResourceInterface $resource): ExtractedText
            {
                throw new \RuntimeException('The document is broken', 1793350211);
            }
        };
        /** @var Container $container */
        $container = $this->get('service_container');
        $container->set(TextExtractorRegistry::class, new TextExtractorRegistry([$failingTextExtractor, new PlainTextExtractor()]));

        $this->indexPageWithLink('fileadmin/user_upload/document.docx', 'fileadmin/user_upload/public.txt');

        $rows = $this->getIndexedFileRows();
        self::assertCount(1, $rows);
        self::assertSame('txt', $rows[0]['item_type']);
        self::assertStringContainsString('marmalade', $this->getIndexedFullText());
        self::assertSame(1, $this->get(ConnectionPool::class)->getConnectionForTable('index_phash')->count('*', 'index_phash', ['item_type' => '0']));
    }

    private function indexPageWithLink(string ...$hrefs): void
    {
        $links = '';
        foreach ($hrefs as $href) {
            $links .= '<a href="' . htmlspecialchars($href) . '">Download</a>';
        }
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
            'index_externals' => true,
            'mtime' => time(),
            'crdate' => time(),
            'content' => '<html><head><title>Download</title></head><body>' . $links . '</body></html>',
            'indexedDocTitle' => '',
        ]);
        $indexer->indexTypo3PageContent();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function getIndexedFileRows(): array
    {
        $queryBuilder = $this->get(ConnectionPool::class)->getQueryBuilderForTable('index_phash');
        return $queryBuilder
            ->select('item_type', 'data_filename', 'item_title', 'externalUrl')
            ->from('index_phash')
            ->where($queryBuilder->expr()->neq('item_type', $queryBuilder->createNamedParameter('0')))
            ->orderBy('phash')
            ->executeQuery()
            ->fetchAllAssociative();
    }

    private function countFileIndexEntries(string $fileName): int
    {
        return $this->get(ConnectionPool::class)->getConnectionForTable('sys_file')->count('*', 'sys_file', ['name' => $fileName]);
    }

    private function getIndexedFullText(): string
    {
        $queryBuilder = $this->get(ConnectionPool::class)->getQueryBuilderForTable('index_fulltext');
        return implode(' ', $queryBuilder
            ->select('fulltextdata')
            ->from('index_fulltext')
            ->executeQuery()
            ->fetchFirstColumn());
    }
}
