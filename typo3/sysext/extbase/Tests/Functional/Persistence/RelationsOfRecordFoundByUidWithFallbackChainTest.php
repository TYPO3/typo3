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
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\LanguageAspect;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Domain\RecordFactory;
use TYPO3\CMS\Core\Domain\RecordInterface;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Tests\BlogExample\Domain\Model\Tag;
use TYPO3Tests\BlogExample\Domain\Repository\PostRepository;

/**
 * Relations of a record that has no translation in the requested language but one in a language
 * of the fallback chain, read with French, the fallback chain German, English and overlays mixed or on.
 *
 * Fixture Posts.csv: post 1 "First post" has a German translation (2) and no French one. Both
 * posts relate through the field tags (MM) to the tags 1, 2 and 3. Tag 1 "First tag" has a German
 * (4, "Erstes Tag") and a French translation (5, "Premier tag"), tag 2 "Second tag" has only a
 * German translation (6, "Zweites Tag"), tag 3 "Third tag" has no translation. The record API
 * of core is the reference: it reads every tag in the first language of the chain that has a
 * translation.
 */
final class RelationsOfRecordFoundByUidWithFallbackChainTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    private const array LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en-US'],
        'DE' => ['id' => 1, 'title' => 'German', 'locale' => 'de-DE'],
        'FR' => ['id' => 2, 'title' => 'French', 'locale' => 'fr-FR'],
    ];

    protected array $testExtensionsToLoad = [
        'typo3/sysext/extbase/Tests/Functional/Fixtures/Extensions/blog_example',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RelationsOfRecordFoundByUidWithFallbackChain/Posts.csv');
        $this->writeSiteConfiguration(
            'blog',
            $this->buildSiteConfiguration(1, '/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/'),
                $this->buildLanguageConfiguration('DE', '/de/'),
                $this->buildLanguageConfiguration('FR', '/fr/', ['DE', 'EN']),
            ]
        );
        $this->get(ConfigurationManager::class)->setConfiguration([
            'persistence' => ['storagePid' => 2],
            'extensionName' => 'blog_example',
            'pluginName' => 'test',
        ]);
        $this->get(ConfigurationManagerInterface::class)->setRequest(
            new ServerRequest()->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
        );
    }

    /**
     * Read with French, the fallback chain German, English and overlays mixed, post 1 has no French
     * translation and is read as its German translation. The core reads the tags like the record API
     * does: every tag in the first language of the chain that has a translation, "Premier tag" (tag 1
     * in French), "Zweites Tag" (tag 2 in German through the chain) and "Third tag" (tag 3, kept in
     * the default language).
     *
     * @see readingTheChildrenOfAParentFoundByUidWithAFallbackChainAndOverlaysMixedByExtbase()
     */
    #[Test]
    public function readingTheChildrenOfAParentFoundByUidWithAFallbackChainAndOverlaysMixedByCore(): void
    {
        // Given: post 1 with a German translation and without a French one
        $languageAspect = new LanguageAspect(2, 2, LanguageAspect::OVERLAYS_MIXED, [1, 0]);
        $this->get(Context::class)->setAspect('language', $languageAspect);

        // When: post 1 is read with French, the fallback chain German, English and overlays mixed and its tags are followed
        $row = $this->get(ConnectionPool::class)->getConnectionForTable('tx_blogexample_domain_model_post')
            ->select(['*'], 'tx_blogexample_domain_model_post', ['uid' => 1])->fetchAssociative();
        $row = $this->get(PageRepository::class)->getLanguageOverlay('tx_blogexample_domain_model_post', $row, $languageAspect);
        $post = $this->get(RecordFactory::class)->createResolvedRecordFromDatabaseRow('tx_blogexample_domain_model_post', $row);
        $titles = array_map(
            static fn(RecordInterface $tag): string => $tag->get('name'),
            iterator_to_array($post->get('tags'), false)
        );

        // Then: every tag is read in the first language of the chain that has a translation
        self::assertSame(['Premier tag', 'Zweites Tag', 'Third tag'], $titles);
    }

    /**
     * Read with French, the fallback chain German, English and overlays mixed, post 1 is found by
     * findByUid() and read as its German translation. Extbase must return the same tags as the core:
     * "Premier tag", "Zweites Tag" and "Third tag". It returns the German "Erstes Tag" for tag 1,
     * because the relation is read in the language of the parent record, German, instead of the
     * requested language, French.
     *
     * @see readingTheChildrenOfAParentFoundByUidWithAFallbackChainAndOverlaysMixedByCore()
     */
    #[Test]
    public function readingTheChildrenOfAParentFoundByUidWithAFallbackChainAndOverlaysMixedByExtbase(): void
    {
        // Given: post 1 with a German translation and without a French one
        $this->get(Context::class)->setAspect('language', new LanguageAspect(2, 2, LanguageAspect::OVERLAYS_MIXED, [1, 0]));
        $this->get(PersistenceManager::class)->clearState();

        // When: post 1 is found by findByUid() with French, the fallback chain German, English and overlays mixed and its tags are followed
        $post = $this->get(PostRepository::class)->findByUid(1);
        $titles = array_map(
            static fn(Tag $tag): string => $tag->getName(),
            $post->getTags()->toArray()
        );

        // Then: every tag is read in the first language of the chain that has a translation
        self::assertSame(['Premier tag', 'Zweites Tag', 'Third tag'], $titles);
    }

    /**
     * Read with French, the fallback chain German, English and overlays on, post 1 is read as its
     * German translation. The core reads the tags like the record API does: "Premier tag" (tag 1 in
     * French) and "Zweites Tag" (tag 2 in German through the chain). Tag 3 has no translation in the
     * chain and is dropped.
     *
     * @see readingTheChildrenOfAParentFoundByUidWithAFallbackChainAndOverlaysOnByExtbase()
     */
    #[Test]
    public function readingTheChildrenOfAParentFoundByUidWithAFallbackChainAndOverlaysOnByCore(): void
    {
        // Given: post 1 with a German translation and without a French one
        $languageAspect = new LanguageAspect(2, 2, LanguageAspect::OVERLAYS_ON, [1, 0]);
        $this->get(Context::class)->setAspect('language', $languageAspect);

        // When: post 1 is read with French, the fallback chain German, English and overlays on and its tags are followed
        $row = $this->get(ConnectionPool::class)->getConnectionForTable('tx_blogexample_domain_model_post')
            ->select(['*'], 'tx_blogexample_domain_model_post', ['uid' => 1])->fetchAssociative();
        $row = $this->get(PageRepository::class)->getLanguageOverlay('tx_blogexample_domain_model_post', $row, $languageAspect);
        $post = $this->get(RecordFactory::class)->createResolvedRecordFromDatabaseRow('tx_blogexample_domain_model_post', $row);
        $titles = array_map(
            static fn(RecordInterface $tag): string => $tag->get('name'),
            iterator_to_array($post->get('tags'), false)
        );

        // Then: every tag with a translation in the chain is read in the first language that has one
        self::assertSame(['Premier tag', 'Zweites Tag'], $titles);
    }

    /**
     * Read with French, the fallback chain German, English and overlays on, post 1 is found by
     * findByUid() and read as its German translation. Extbase must return the same tags as the
     * core: "Premier tag" and "Zweites Tag". It returns the German "Erstes Tag" for tag 1, because
     * the relation is read in the language of the parent record, German, instead of the requested
     * language, French.
     *
     * @see readingTheChildrenOfAParentFoundByUidWithAFallbackChainAndOverlaysOnByCore()
     */
    #[Test]
    public function readingTheChildrenOfAParentFoundByUidWithAFallbackChainAndOverlaysOnByExtbase(): void
    {
        // Given: post 1 with a German translation and without a French one
        $this->get(Context::class)->setAspect('language', new LanguageAspect(2, 2, LanguageAspect::OVERLAYS_ON, [1, 0]));
        $this->get(PersistenceManager::class)->clearState();

        // When: post 1 is found by findByUid() with French, the fallback chain German, English and overlays on and its tags are followed
        $post = $this->get(PostRepository::class)->findByUid(1);
        $titles = array_map(
            static fn(Tag $tag): string => $tag->getName(),
            $post->getTags()->toArray()
        );

        // Then: every tag with a translation in the chain is read in the first language that has one
        self::assertSame(['Premier tag', 'Zweites Tag'], $titles);
    }

    /**
     * Post 1 is found by a repository query (equals('uid', 1) on the storage page 2, respectSysLanguage
     * left at its default true) instead of findByUid(), and read with French, the fallback chain German,
     * English and overlays mixed. Extbase returns the same as the core: "Premier tag", "Zweites Tag" and
     * "Third tag". Unlike the findByUid() pair above, the query reads the relation in the requested
     * language, French.
     *
     * @see readingTheChildrenOfAParentFoundByUidWithAFallbackChainAndOverlaysMixedByCore()
     */
    #[Test]
    public function readingTheChildrenOfAParentFoundByAQueryWithAFallbackChainAndOverlaysMixedByExtbase(): void
    {
        // Given: post 1 with a German translation and without a French one
        $this->get(Context::class)->setAspect('language', new LanguageAspect(2, 2, LanguageAspect::OVERLAYS_MIXED, [1, 0]));
        $this->get(PersistenceManager::class)->clearState();

        // When: post 1 is found by a query with French, the fallback chain German, English and overlays mixed and its tags are followed
        $query = $this->get(PostRepository::class)->createQuery();
        $query->getQuerySettings()->setStoragePageIds([2]);
        $query->getQuerySettings()->setRespectSysLanguage(true);
        $query->matching($query->equals('uid', 1));
        $post = $query->execute()->getFirst();
        $titles = array_map(
            static fn(Tag $tag): string => $tag->getName(),
            $post->getTags()->toArray()
        );

        // Then: every tag is read in the first language of the chain that has a translation
        self::assertSame(['Premier tag', 'Zweites Tag', 'Third tag'], $titles);
    }
}
