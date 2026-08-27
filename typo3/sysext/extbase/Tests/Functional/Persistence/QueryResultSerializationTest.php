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

namespace TYPO3\CMS\Extbase\Tests\Functional\Persistence;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Cache\Backend\SimpleFileBackend;
use TYPO3\CMS\Core\Cache\Frontend\VariableFrontend;
use TYPO3\CMS\Core\Context\LanguageAspect;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Extbase\Persistence\Generic\QueryResult;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Tests\BlogExample\Domain\Repository\PostRepository;

final class QueryResultSerializationTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = ['typo3/sysext/extbase/Tests/Functional/Fixtures/Extensions/blog_example'];

    private PostRepository $postRepository;
    private PersistenceManager $persistenceManager;
    private VariableFrontend $cache;

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/QueryLocalizedDataTestImport.csv');
        $this->get(ConfigurationManager::class)->setConfiguration([
            'persistence' => [
                'storagePid' => 20,
            ],
            'extensionName' => 'blog_example',
            'pluginName' => 'test',
        ]);
        $request = (new ServerRequest())->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $this->get(ConfigurationManagerInterface::class)->setRequest($request);
        $this->postRepository = $this->get(PostRepository::class);
        $this->persistenceManager = $this->get(PersistenceManager::class);
        $this->cache = new VariableFrontend('extbase_queryresult_test', new SimpleFileBackend('Testing'));
    }

    #[Test]
    public function queryResultCanBeSerialized(): void
    {
        $query = $this->postRepository->createQuery();
        $query->setLimit(3);
        $query->setOrderings(['uid' => QueryInterface::ORDER_DESCENDING]);

        self::assertNotEmpty(serialize($query->execute()));
    }

    #[Test]
    public function cachedQueryResultReturnsSameRecords(): void
    {
        $query = $this->postRepository->createQuery();
        $query->setLimit(3);
        $query->setOrderings(['uid' => QueryInterface::ORDER_DESCENDING]);
        $queryResult = $query->execute();
        $expectedUids = $this->uidsOf($queryResult);

        $this->persistenceManager->clearState();
        $cachedQueryResult = $this->cacheRoundTrip($queryResult);

        self::assertSame($expectedUids, $this->uidsOf($cachedQueryResult));
    }

    #[Test]
    public function queryOfCachedQueryResultCanBeExecutedAgain(): void
    {
        $query = $this->postRepository->createQuery();
        $query->setLimit(3);
        $query->setOrderings(['uid' => QueryInterface::ORDER_DESCENDING]);
        $expectedUids = $this->uidsOf($query->execute());

        $this->persistenceManager->clearState();
        $cachedQueryResult = $this->cacheRoundTrip($query->execute());

        self::assertSame($expectedUids, $this->uidsOf($cachedQueryResult->getQuery()->execute()));
    }

    #[Test]
    public function cachedQueryResultTranslatesRecordsOfTheQueryLanguage(): void
    {
        $query = $this->postRepository->createQuery();
        $query->getQuerySettings()->setLanguageAspect(new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_ON));
        $query->matching($query->equals('uid', 11));

        // Cached before the result is initialized, so the mapping happens after restoring it
        $cachedQueryResult = $this->cacheRoundTrip($query->execute());

        $post = $cachedQueryResult->getFirst();
        self::assertNotNull($post);
        self::assertSame('Post 2 - DA', $post->getTitle());
        self::assertSame('Blog 1 DA', $post->getBlog()->getTitle());
    }

    /**
     * setQuery() hands the query to the data mapper as well, which needs it to determine the
     * effective language aspect (DataMapper::getEffectiveLanguageAspect()) and the parent query
     * of relations (DataMapper::getPreparedQuery()). __wakeup() has to restore that link.
     */
    #[Test]
    public function cachedQueryResultReconnectsItsDataMapperToTheQuery(): void
    {
        $query = $this->postRepository->createQuery();
        $query->setLimit(1);

        $cachedQueryResult = $this->cacheRoundTrip($query->execute());

        $dataMapper = (new \ReflectionProperty(QueryResult::class, 'dataMapper'))->getValue($cachedQueryResult);
        $queryOfDataMapper = (new \ReflectionProperty(DataMapper::class, 'query'))->getValue($dataMapper);
        self::assertSame($cachedQueryResult->getQuery()->getType(), $queryOfDataMapper?->getType());
    }

    #[Test]
    public function cachedQuerySettingsKeepTheirState(): void
    {
        $query = $this->postRepository->createQuery();
        $query->getQuerySettings()
            ->setRespectStoragePage(false)
            ->setIgnoreEnableFields(true)
            ->setEnableFieldsToBeIgnored(['disabled'])
            ->setIncludeDeleted(true)
            ->setRespectSysLanguage(false)
            ->setLanguageAspect(new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_ON));

        $querySettings = $this->cacheRoundTrip($query->execute())->getQuery()->getQuerySettings();

        self::assertFalse($querySettings->getRespectStoragePage());
        self::assertTrue($querySettings->getIgnoreEnableFields());
        self::assertSame(['disabled'], $querySettings->getEnableFieldsToBeIgnored());
        self::assertTrue($querySettings->getIncludeDeleted());
        self::assertFalse($querySettings->getRespectSysLanguage());
        self::assertSame(1, $querySettings->getLanguageAspect()->getContentId());
    }

    private function cacheRoundTrip(QueryResultInterface $queryResult): QueryResultInterface
    {
        $this->cache->set('queryResult', $queryResult);
        return $this->cache->get('queryResult');
    }

    /**
     * @return list<int>
     */
    private function uidsOf(QueryResultInterface $queryResult): array
    {
        $uids = [];
        foreach ($queryResult as $object) {
            $uids[] = $object->getUid();
        }
        return $uids;
    }
}
