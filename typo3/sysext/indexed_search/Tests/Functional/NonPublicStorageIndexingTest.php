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

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\IndexedSearch\Indexer;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class NonPublicStorageIndexingTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = [
        'indexed_search',
    ];

    protected array $pathsToProvideInTestInstance = [
        'typo3/sysext/indexed_search/Tests/Functional/Fixtures/Files/nonpublic.txt' => 'fileadmin/user_upload/nonpublic.txt',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/Indexer/non_public_storage.csv');
        $site = new Site('website-local', 1, ['base' => 'https://example.com/', 'languages' => []]);
        $request = (new ServerRequest('https://example.com/', 'GET', null, [], ['HTTP_HOST' => 'example.com', 'SCRIPT_NAME' => '/index.php', 'HTTPS' => 'on']))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE)
            ->withAttribute('site', $site)
            ->withAttribute('language', $site->getDefaultLanguage());
        $GLOBALS['TYPO3_REQUEST'] = $request->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request));
    }

    #[Test]
    public function publicUrlOfNonPublicStorageIsResolvedToALocalPath(): void
    {
        $subject = $this->get(Indexer::class);
        $result = $subject->extractHyperLinks('<a href="' . htmlspecialchars($this->getFileDumpUrl()) . '">nonpublic.txt</a>');

        self::assertCount(1, $result);
        self::assertSame(
            $this->instancePath . '/fileadmin/user_upload/nonpublic.txt',
            $result[0]['localPath']
        );
    }

    #[Test]
    public function fileOfNonPublicStorageIsIndexed(): void
    {
        $this->indexPageWithLink(htmlspecialchars($this->getFileDumpUrl()));

        self::assertSame(['txt'], $this->getIndexedItemTypes());
        self::assertStringContainsString('xylographica', $this->getIndexedFullText());
    }

    #[Test]
    public function fileDumpUrlWithInvalidTokenIsNotIndexed(): void
    {
        $this->indexPageWithLink('index.php?eID=dumpFile&amp;t=f&amp;f=1&amp;token=invalid');

        self::assertSame([], $this->getIndexedItemTypes());
    }

    private function getFileDumpUrl(): string
    {
        $publicUrl = $this->get(ResourceFactory::class)->getFileObject(1)->getPublicUrl();
        self::assertStringContainsString('eID=dumpFile', (string)$publicUrl, 'Storage did not create a file dump URL');
        return (string)$publicUrl;
    }

    private function indexPageWithLink(string $href): void
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
            'index_externals' => true,
            'mtime' => time(),
            'crdate' => time(),
            'content' => '<html><head><title>Download</title></head><body><a href="' . $href . '">nonpublic.txt</a></body></html>',
            'indexedDocTitle' => '',
        ]);
        $indexer->indexTypo3PageContent();
    }

    /**
     * @return string[]
     */
    private function getIndexedItemTypes(): array
    {
        $queryBuilder = $this->get(ConnectionPool::class)->getQueryBuilderForTable('index_phash');
        $itemTypes = $queryBuilder
            ->select('item_type')
            ->from('index_phash')
            ->where($queryBuilder->expr()->neq('item_type', $queryBuilder->createNamedParameter('0')))
            ->executeQuery()
            ->fetchFirstColumn();
        return array_map(strval(...), $itemTypes);
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
