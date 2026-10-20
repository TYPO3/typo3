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
use TYPO3\CMS\Core\Cache\CacheDataCollector;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\LanguageAspect;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\DomainObject\AbstractDomainObject;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Page\PageInformation;
use TYPO3\CMS\Frontend\Page\PageParts;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Tests\BlogExample\Domain\Model\Post;
use TYPO3Tests\BlogExample\Domain\Repository\PostRepository;

/**
 * Reading translations: which posts a query returns for a language, how many it counts, how they are ordered and how a
 * query without language restriction and the lookup by uid behave.
 *
 * Every scenario is tested twice against the same expectation: once with the core API as oracle (suffix "ByCore"), once
 * with Extbase (suffix "ByExtbase"). The core side selects the records the way the `CONTENT` cObject does, with
 * `ContentObjectRenderer::getRecords()`, and reads a single record the way the `RECORDS` cObject does. Both sides read
 * with the same `LanguageAspect`, sort by `uid` unless the scenario says otherwise, and return the posts as "title
 * (uid, language)". Both tests of a pair read the same fixture in Fixtures/ReadingTranslations/Given/. An Extbase test
 * that differs from core is skipped.
 *
 * The storage page 2 holds the posts 1 to 8 (Posts.csv). Post 1 has no translation. Post 2 has a German translation (3)
 * and a French translation (4), post 5 has a German translation (6). Post 7 is a record for all languages (language
 * -1). Post 8 is a German record without default record, a free translation. The languages are English (0), German (1)
 * and French (2); French falls back to German and English.
 *
 * The scenarios on ordering by `sorting` read the posts of the storage page 3 (SortedPosts.csv): the posts Alpha, Beta
 * and Gamma with the German translations "Alpha DE", "Beta DE" and "Gamma DE" whose `sorting` values differ from those
 * of the default records, and the untranslated post Delta.
 */
final class ReadingTranslationsTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3/sysext/extbase/Tests/Functional/Fixtures/Extensions/blog_example',
    ];

    private ContentObjectRenderer $contentObjectRenderer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ReadingTranslations/Given/Posts.csv');
        $this->get(ConfigurationManager::class)->setConfiguration([
            'persistence' => ['storagePid' => 2],
            'extensionName' => 'blog_example',
            'pluginName' => 'test',
        ]);
        $this->get(ConfigurationManagerInterface::class)->setRequest(
            new ServerRequest()->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
        );
        $pageInformation = new PageInformation();
        $pageInformation->setId(1);
        $pageInformation->setContentFromPid(1);
        $this->contentObjectRenderer = $this->get(ContentObjectRenderer::class);
        $this->contentObjectRenderer->setRequest(
            new ServerRequest()
                ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE)
                ->withAttribute('frontend.page.information', $pageInformation)
                ->withAttribute('frontend.cache.collector', new CacheDataCollector())
                ->withAttribute('frontend.page.parts', new PageParts())
        );
    }

    /**
     * Read with German and overlays on, `ContentObjectRenderer::getRecords()` selects the default rows and the rows for
     * all languages of the storage page 2 and overlays each default row with its German translation. It returns post 2
     * as "Erster Beitrag" and post 5 as "Zweiter Beitrag", both under the uid of the default record, and post 7 for all
     * languages unchanged. Post 1 "Only in English" has no translation and is dropped. The German post 8 "Freie
     * Übersetzung" has no default record and is not selected.
     *
     * @see readingAllPostsWithOverlaysOnByExtbase()
     */
    #[Test]
    public function readingAllPostsWithOverlaysOnByCore(): void
    {
        // When: all posts are read with German and overlays on
        $this->get(Context::class)->setAspect('language', new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_ON));
        $posts = array_map(
            static fn(array $row): string => sprintf('%s (uid %d, language %d)', $row['title'], $row['uid'], $row['sys_language_uid']),
            $this->contentObjectRenderer->getRecords('tx_blogexample_domain_model_post', ['pidInList' => '2', 'orderBy' => 'uid']),
        );

        // Then: the translated posts and the post for all languages are returned
        self::assertSame([
            'Erster Beitrag (uid 2, language 1)',
            'Zweiter Beitrag (uid 5, language 1)',
            'All languages (uid 7, language -1)',
        ], $posts);
    }

    /**
     * Read with German and overlays on, Extbase returns the same three posts as the core: post 2 as "Erster Beitrag"
     * and post 5 as "Zweiter Beitrag" under the uids of their default records, and post 7 for all languages. Post 1
     * "Only in English" is dropped and the German post 8 "Freie Übersetzung" without default record is not returned.
     *
     * @see readingAllPostsWithOverlaysOnByCore()
     */
    #[Test]
    public function readingAllPostsWithOverlaysOnByExtbase(): void
    {
        // When: all posts are read with German and overlays on
        $this->get(Context::class)->setAspect('language', new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_ON));
        $query = $this->get(PostRepository::class)->createQuery();
        $query->setOrderings(['uid' => QueryInterface::ORDER_ASCENDING]);
        $posts = array_map(
            static fn(Post $post): string => sprintf('%s (uid %d, language %d)', $post->getTitle(), $post->getUid(), $post->_getProperty(AbstractDomainObject::PROPERTY_LANGUAGE_UID)),
            $query->execute()->toArray(),
        );

        // Then: the translated posts and the post for all languages are returned
        self::assertSame([
            'Erster Beitrag (uid 2, language 1)',
            'Zweiter Beitrag (uid 5, language 1)',
            'All languages (uid 7, language -1)',
        ], $posts);
    }

    /**
     * Read with German and overlays mixed, `ContentObjectRenderer::getRecords()` returns the translated posts with the
     * German title and keeps the untranslated post in English: post 1 as "Only in English", post 2 as "Erster Beitrag",
     * post 5 as "Zweiter Beitrag", post 7 for all languages and the German post 8 "Freie Übersetzung", which has no
     * default record. With overlays mixed the core selects such records of the requested language and returns them
     * unchanged.
     *
     * @see readingAllPostsWithOverlaysMixedByExtbase()
     */
    #[Test]
    public function readingAllPostsWithOverlaysMixedByCore(): void
    {
        // When: all posts are read with German and overlays mixed
        $this->get(Context::class)->setAspect('language', new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_MIXED));
        $posts = array_map(
            static fn(array $row): string => sprintf('%s (uid %d, language %d)', $row['title'], $row['uid'], $row['sys_language_uid']),
            $this->contentObjectRenderer->getRecords('tx_blogexample_domain_model_post', ['pidInList' => '2', 'orderBy' => 'uid']),
        );

        // Then: the untranslated post is kept in English and the German post without default record is returned
        self::assertSame([
            'Only in English (uid 1, language 0)',
            'Erster Beitrag (uid 2, language 1)',
            'Zweiter Beitrag (uid 5, language 1)',
            'All languages (uid 7, language -1)',
            'Freie Übersetzung (uid 8, language 1)',
        ], $posts);
    }

    /**
     * Read with German and overlays mixed, Extbase returns post 1 in English, posts 2 and 5 with the German title and
     * post 7 for all languages, but not the German post 8 "Freie Übersetzung" without default record that the core
     * returns. Extbase selects such records only with overlays on with floating, not with overlays mixed.
     *
     * @see readingAllPostsWithOverlaysMixedByCore()
     */
    #[Test]
    public function readingAllPostsWithOverlaysMixedByExtbase(): void
    {
        self::markTestSkipped('Extbase differs from core: with overlays mixed it does not return the German post without default record (8), see Forge #89131.');

        // When: all posts are read with German and overlays mixed
        // @phpstan-ignore deadCode.unreachable
        $this->get(Context::class)->setAspect('language', new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_MIXED));
        $query = $this->get(PostRepository::class)->createQuery();
        $query->setOrderings(['uid' => QueryInterface::ORDER_ASCENDING]);
        $posts = array_map(
            static fn(Post $post): string => sprintf('%s (uid %d, language %d)', $post->getTitle(), $post->getUid(), $post->_getProperty(AbstractDomainObject::PROPERTY_LANGUAGE_UID)),
            $query->execute()->toArray(),
        );

        // Then: the untranslated post is kept in English and the German post without default record is returned
        self::assertSame([
            'Only in English (uid 1, language 0)',
            'Erster Beitrag (uid 2, language 1)',
            'Zweiter Beitrag (uid 5, language 1)',
            'All languages (uid 7, language -1)',
            'Freie Übersetzung (uid 8, language 1)',
        ], $posts);
    }

    /**
     * Read with German and overlays on with floating, `ContentObjectRenderer::getRecords()` returns the posts that have
     * a German translation with the German title, the post for all languages and the German post without default
     * record: post 2 as "Erster Beitrag", post 5 as "Zweiter Beitrag", post 7 and post 8 as "Freie Übersetzung". Post 1
     * has no translation and is dropped. Unlike with overlays on, the core also selects records of the requested
     * language without a default record and returns them unchanged.
     *
     * @see readingAllPostsWithOverlaysOnWithFloatingByExtbase()
     */
    #[Test]
    public function readingAllPostsWithOverlaysOnWithFloatingByCore(): void
    {
        // When: all posts are read with German and overlays on with floating
        $this->get(Context::class)->setAspect('language', new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_ON_WITH_FLOATING));
        $posts = array_map(
            static fn(array $row): string => sprintf('%s (uid %d, language %d)', $row['title'], $row['uid'], $row['sys_language_uid']),
            $this->contentObjectRenderer->getRecords('tx_blogexample_domain_model_post', ['pidInList' => '2', 'orderBy' => 'uid']),
        );

        // Then: the translated posts, the post for all languages and the German post without default record are returned
        self::assertSame([
            'Erster Beitrag (uid 2, language 1)',
            'Zweiter Beitrag (uid 5, language 1)',
            'All languages (uid 7, language -1)',
            'Freie Übersetzung (uid 8, language 1)',
        ], $posts);
    }

    /**
     * Read with German and overlays on with floating, Extbase returns the same four posts as the core: post 2 as
     * "Erster Beitrag", post 5 as "Zweiter Beitrag", post 7 for all languages and the German post 8 "Freie Übersetzung"
     * without default record. Post 1 without translation is dropped.
     *
     * @see readingAllPostsWithOverlaysOnWithFloatingByCore()
     */
    #[Test]
    public function readingAllPostsWithOverlaysOnWithFloatingByExtbase(): void
    {
        // When: all posts are read with German and overlays on with floating
        $this->get(Context::class)->setAspect('language', new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_ON_WITH_FLOATING));
        $query = $this->get(PostRepository::class)->createQuery();
        $query->setOrderings(['uid' => QueryInterface::ORDER_ASCENDING]);
        $posts = array_map(
            static fn(Post $post): string => sprintf('%s (uid %d, language %d)', $post->getTitle(), $post->getUid(), $post->_getProperty(AbstractDomainObject::PROPERTY_LANGUAGE_UID)),
            $query->execute()->toArray(),
        );

        // Then: the translated posts, the post for all languages and the German post without default record are returned
        self::assertSame([
            'Erster Beitrag (uid 2, language 1)',
            'Zweiter Beitrag (uid 5, language 1)',
            'All languages (uid 7, language -1)',
            'Freie Übersetzung (uid 8, language 1)',
        ], $posts);
    }

    /**
     * Read with German and overlays off (strict mode), `ContentObjectRenderer::getRecords()` does no overlay and
     * returns the German rows as they are stored: the translations 3 "Erster Beitrag" and 6 "Zweiter Beitrag", the post
     * for all languages (7) and the German post without default record (8), each with its own uid.
     *
     * @see readingAllPostsInStrictModeByExtbase()
     */
    #[Test]
    public function readingAllPostsInStrictModeByCore(): void
    {
        // When: all posts are read with German and overlays off
        $this->get(Context::class)->setAspect('language', new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_OFF));
        $posts = array_map(
            static fn(array $row): string => sprintf('%s (uid %d, language %d)', $row['title'], $row['uid'], $row['sys_language_uid']),
            $this->contentObjectRenderer->getRecords('tx_blogexample_domain_model_post', ['pidInList' => '2', 'orderBy' => 'uid']),
        );

        // Then: the German rows are returned with their own uids
        self::assertSame([
            'Erster Beitrag (uid 3, language 1)',
            'Zweiter Beitrag (uid 6, language 1)',
            'All languages (uid 7, language -1)',
            'Freie Übersetzung (uid 8, language 1)',
        ], $posts);
    }

    /**
     * Read with German and overlays off, Extbase returns the same four rows as the core, but it identifies the
     * translations by the uid of their default record: "Erster Beitrag" is post 2 and "Zweiter Beitrag" is post 5,
     * where the core returns the rows with their own uids 3 and 6. The post for all languages keeps the uid 7 and the
     * German post without default record keeps the uid 8.
     *
     * @see readingAllPostsInStrictModeByCore()
     */
    #[Test]
    public function readingAllPostsInStrictModeByExtbase(): void
    {
        self::markTestSkipped('Extbase differs from core: it returns the German translations under the uids of their default records (2 and 5), where the core returns the rows with their own uids (3 and 6).');

        // When: all posts are read with German and overlays off
        // @phpstan-ignore deadCode.unreachable
        $this->get(Context::class)->setAspect('language', new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_OFF));
        $query = $this->get(PostRepository::class)->createQuery();
        $query->setOrderings(['uid' => QueryInterface::ORDER_ASCENDING]);
        $posts = array_map(
            static fn(Post $post): string => sprintf('%s (uid %d, language %d)', $post->getTitle(), $post->getUid(), $post->_getProperty(AbstractDomainObject::PROPERTY_LANGUAGE_UID)),
            $query->execute()->toArray(),
        );

        // Then: the German rows are returned with their own uids
        self::assertSame([
            'Erster Beitrag (uid 3, language 1)',
            'Zweiter Beitrag (uid 6, language 1)',
            'All languages (uid 7, language -1)',
            'Freie Übersetzung (uid 8, language 1)',
        ], $posts);
    }

    /**
     * French falls back to German and then to English. Read with French, this fallback chain and overlays mixed, the
     * core returns for every post the first language of the chain that has a translation: post 2 in French ("Premier
     * billet"), post 5 in German ("Zweiter Beitrag") because it has no French translation, post 1 in English ("Only in
     * English") and the post for all languages (7).
     *
     * @see readingWithAFallbackChainByExtbase()
     */
    #[Test]
    public function readingWithAFallbackChainByCore(): void
    {
        // When: all posts are read with French, the fallback chain German, English and overlays mixed
        $this->get(Context::class)->setAspect('language', new LanguageAspect(2, 2, LanguageAspect::OVERLAYS_MIXED, [1, 0]));
        $posts = array_map(
            static fn(array $row): string => sprintf('%s (uid %d, language %d)', $row['title'], $row['uid'], $row['sys_language_uid']),
            $this->contentObjectRenderer->getRecords('tx_blogexample_domain_model_post', ['pidInList' => '2', 'orderBy' => 'uid']),
        );

        // Then: every post has the title of the first language in the chain that has a translation
        self::assertSame([
            'Only in English (uid 1, language 0)',
            'Premier billet (uid 2, language 2)',
            'Zweiter Beitrag (uid 5, language 1)',
            'All languages (uid 7, language -1)',
        ], $posts);
    }

    /**
     * Read with French, the fallback chain German, English and overlays mixed, Extbase returns the same four posts as
     * the core: post 2 in French, post 5 in German through the fallback chain, post 1 in English and the post for all
     * languages (7).
     *
     * @see readingWithAFallbackChainByCore()
     */
    #[Test]
    public function readingWithAFallbackChainByExtbase(): void
    {
        // When: all posts are read with French, the fallback chain German, English and overlays mixed
        $this->get(Context::class)->setAspect('language', new LanguageAspect(2, 2, LanguageAspect::OVERLAYS_MIXED, [1, 0]));
        $query = $this->get(PostRepository::class)->createQuery();
        $query->setOrderings(['uid' => QueryInterface::ORDER_ASCENDING]);
        $posts = array_map(
            static fn(Post $post): string => sprintf('%s (uid %d, language %d)', $post->getTitle(), $post->getUid(), $post->_getProperty(AbstractDomainObject::PROPERTY_LANGUAGE_UID)),
            $query->execute()->toArray(),
        );

        // Then: every post has the title of the first language in the chain that has a translation
        self::assertSame([
            'Only in English (uid 1, language 0)',
            'Premier billet (uid 2, language 2)',
            'Zweiter Beitrag (uid 5, language 1)',
            'All languages (uid 7, language -1)',
        ], $posts);
    }

    /**
     * French falls back to German and then to English. Read with French, this fallback chain and overlays on, the core
     * returns the posts that have a translation in a language of the chain: post 2 in French ("Premier billet"), post 5
     * in German ("Zweiter Beitrag") and the post for all languages (7). Post 1 has no translation in the chain and is
     * dropped.
     *
     * @see readingWithAFallbackChainAndOverlaysOnByExtbase()
     */
    #[Test]
    public function readingWithAFallbackChainAndOverlaysOnByCore(): void
    {
        // When: all posts are read with French, the fallback chain German, English and overlays on
        $this->get(Context::class)->setAspect('language', new LanguageAspect(2, 2, LanguageAspect::OVERLAYS_ON, [1, 0]));
        $posts = array_map(
            static fn(array $row): string => sprintf('%s (uid %d, language %d)', $row['title'], $row['uid'], $row['sys_language_uid']),
            $this->contentObjectRenderer->getRecords('tx_blogexample_domain_model_post', ['pidInList' => '2', 'orderBy' => 'uid']),
        );

        // Then: the posts that have a translation in the chain are returned
        self::assertSame([
            'Premier billet (uid 2, language 2)',
            'Zweiter Beitrag (uid 5, language 1)',
            'All languages (uid 7, language -1)',
        ], $posts);
    }

    /**
     * Read with French, the fallback chain German, English and overlays on, Extbase selects only the French translation
     * of post 2 and the post for all languages and returns just these two: post 2 as "Premier billet" and post 7. Post
     * 5 is missing, although the core finds its German translation in the fallback chain.
     *
     * @see readingWithAFallbackChainAndOverlaysOnByCore()
     */
    #[Test]
    public function readingWithAFallbackChainAndOverlaysOnByExtbase(): void
    {
        self::markTestSkipped('Extbase differs from core: with a fallback chain and overlays on it selects only rows in the requested language, so post 5, which has only a German translation, is missing.');

        // When: all posts are read with French, the fallback chain German, English and overlays on
        // @phpstan-ignore deadCode.unreachable
        $this->get(Context::class)->setAspect('language', new LanguageAspect(2, 2, LanguageAspect::OVERLAYS_ON, [1, 0]));
        $query = $this->get(PostRepository::class)->createQuery();
        $query->setOrderings(['uid' => QueryInterface::ORDER_ASCENDING]);
        $posts = array_map(
            static fn(Post $post): string => sprintf('%s (uid %d, language %d)', $post->getTitle(), $post->getUid(), $post->_getProperty(AbstractDomainObject::PROPERTY_LANGUAGE_UID)),
            $query->execute()->toArray(),
        );

        // Then: the posts that have a translation in the chain are returned
        self::assertSame([
            'Premier billet (uid 2, language 2)',
            'Zweiter Beitrag (uid 5, language 1)',
            'All languages (uid 7, language -1)',
        ], $posts);
    }

    /**
     * With French, the fallback chain German, English and overlays on, the core has three posts to show: 2, 5 and 7.
     * The number of posts is the size of the overlaid result, so it is 3.
     *
     * @see countingPostsWithAFallbackChainAndOverlaysOnByExtbase()
     */
    #[Test]
    public function countingPostsWithAFallbackChainAndOverlaysOnByCore(): void
    {
        // When: the posts are counted with French, the fallback chain German, English and overlays on
        $this->get(Context::class)->setAspect('language', new LanguageAspect(2, 2, LanguageAspect::OVERLAYS_ON, [1, 0]));
        $count = count($this->contentObjectRenderer->getRecords('tx_blogexample_domain_model_post', ['pidInList' => '2']));

        // Then: the number of posts that are shown is returned
        self::assertSame(3, $count);
    }

    /**
     * With French, the fallback chain German, English and overlays on, Extbase counts the rows it selects from the
     * database: the French translation of post 2 and the post for all languages. The count is 2, post 5 with its German
     * translation from the fallback chain is not counted.
     *
     * @see countingPostsWithAFallbackChainAndOverlaysOnByCore()
     */
    #[Test]
    public function countingPostsWithAFallbackChainAndOverlaysOnByExtbase(): void
    {
        self::markTestSkipped('Extbase differs from core: it counts 2 where the core has 3 posts to show, because with a fallback chain and overlays on it selects only rows in the requested language.');

        // When: the posts are counted with French, the fallback chain German, English and overlays on
        // @phpstan-ignore deadCode.unreachable
        $this->get(Context::class)->setAspect('language', new LanguageAspect(2, 2, LanguageAspect::OVERLAYS_ON, [1, 0]));
        $query = $this->get(PostRepository::class)->createQuery();
        $count = $query->count();

        // Then: the number of posts that are shown is returned
        self::assertSame(3, $count);
    }

    /**
     * With German and overlays on, the core has three posts to show: 2, 5 and 7. The number of posts is the size of the
     * overlaid result, so it is 3.
     *
     * @see countingPostsWithOverlaysOnByExtbase()
     */
    #[Test]
    public function countingPostsWithOverlaysOnByCore(): void
    {
        // When: the posts are counted with German and overlays on
        $this->get(Context::class)->setAspect('language', new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_ON));
        $count = count($this->contentObjectRenderer->getRecords('tx_blogexample_domain_model_post', ['pidInList' => '2']));

        // Then: the number of posts that are shown is returned
        self::assertSame(3, $count);
    }

    /**
     * With German and overlays on, Extbase counts the three posts that it also returns when it reads them: the German
     * translations of the posts 2 and 5 and the post for all languages. The count is 3.
     *
     * @see countingPostsWithOverlaysOnByCore()
     */
    #[Test]
    public function countingPostsWithOverlaysOnByExtbase(): void
    {
        // When: the posts are counted with German and overlays on
        $this->get(Context::class)->setAspect('language', new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_ON));
        $query = $this->get(PostRepository::class)->createQuery();
        $count = $query->count();

        // Then: the number of posts that are shown is returned
        self::assertSame(3, $count);
    }

    /**
     * With German and overlays mixed, the core has five posts to show: 1, 2, 5, 7 and the German post without default
     * record (8). The number of posts is the size of the overlaid result, so it is 5.
     *
     * @see countingPostsWithOverlaysMixedByExtbase()
     */
    #[Test]
    public function countingPostsWithOverlaysMixedByCore(): void
    {
        // When: the posts are counted with German and overlays mixed
        $this->get(Context::class)->setAspect('language', new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_MIXED));
        $count = count($this->contentObjectRenderer->getRecords('tx_blogexample_domain_model_post', ['pidInList' => '2']));

        // Then: the number of posts that are shown is returned
        self::assertSame(5, $count);
    }

    /**
     * With German and overlays mixed, Extbase counts the four posts that it also returns when it reads them: 1, 2, 5
     * and 7. The German post without default record (8) is not counted, so the count is 4 where the core has 5 posts to
     * show.
     *
     * @see countingPostsWithOverlaysMixedByCore()
     */
    #[Test]
    public function countingPostsWithOverlaysMixedByExtbase(): void
    {
        self::markTestSkipped('Extbase differs from core: it counts 4 where the core has 5 posts to show, because with overlays mixed it does not select the German post without default record (8), see Forge #89131.');

        // When: the posts are counted with German and overlays mixed
        // @phpstan-ignore deadCode.unreachable
        $this->get(Context::class)->setAspect('language', new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_MIXED));
        $query = $this->get(PostRepository::class)->createQuery();
        $count = $query->count();

        // Then: the number of posts that are shown is returned
        self::assertSame(5, $count);
    }

    /**
     * The posts of the storage page 3 are sorted Alpha (256), Beta (512), Gamma (768); their German translations have
     * the `sorting` values 768, 256 and 512. Read with German and overlays on, ordered by `sorting`, the core sorts the
     * default rows and overlays them afterwards, so the order is the one of the default records: "Alpha DE", "Beta DE",
     * "Gamma DE". Delta, which has no translation, is dropped.
     *
     * @see orderingPostsBySortingWithOverlaysOnByExtbase()
     */
    #[Test]
    public function orderingPostsBySortingWithOverlaysOnByCore(): void
    {
        // Given: the posts of page 3 with diverging sorting values in the translations
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ReadingTranslations/Given/SortedPosts.csv');

        // When: the posts are read with German and overlays on, ordered by sorting
        $this->get(Context::class)->setAspect('language', new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_ON));
        $posts = array_map(
            static fn(array $row): string => sprintf('%s (uid %d, language %d)', $row['title'], $row['uid'], $row['sys_language_uid']),
            $this->contentObjectRenderer->getRecords('tx_blogexample_domain_model_post', ['pidInList' => '3', 'orderBy' => 'sorting']),
        );

        // Then: the order is the one of the default records
        self::assertSame([
            'Alpha DE (uid 11, language 1)',
            'Beta DE (uid 12, language 1)',
            'Gamma DE (uid 13, language 1)',
        ], $posts);
    }

    /**
     * Read with German and overlays on, ordered by `sorting`, Extbase sorts the German rows by their own `sorting`
     * values (256, 512, 768) and returns "Beta DE", "Gamma DE", "Alpha DE", where the core returns the order of the
     * default records.
     *
     * @see orderingPostsBySortingWithOverlaysOnByCore()
     */
    #[Test]
    public function orderingPostsBySortingWithOverlaysOnByExtbase(): void
    {
        self::markTestSkipped('Extbase differs from core: it orders by the `sorting` of the German rows, the core by the `sorting` of the default records, see Forge #70154.');

        // Given: the posts of page 3 with diverging sorting values in the translations
        // @phpstan-ignore deadCode.unreachable
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ReadingTranslations/Given/SortedPosts.csv');

        // When: the posts are read with German and overlays on, ordered by sorting
        $this->get(Context::class)->setAspect('language', new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_ON));
        $query = $this->get(PostRepository::class)->createQuery();
        $query->getQuerySettings()->setStoragePageIds([3]);
        $query->setOrderings(['sorting' => QueryInterface::ORDER_ASCENDING]);
        $posts = array_map(
            static fn(Post $post): string => sprintf('%s (uid %d, language %d)', $post->getTitle(), $post->getUid(), $post->_getProperty(AbstractDomainObject::PROPERTY_LANGUAGE_UID)),
            $query->execute()->toArray(),
        );

        // Then: the order is the one of the default records
        self::assertSame([
            'Alpha DE (uid 11, language 1)',
            'Beta DE (uid 12, language 1)',
            'Gamma DE (uid 13, language 1)',
        ], $posts);
    }

    /**
     * The posts of the storage page 3 are sorted Alpha (256), Beta (512), Delta (600), Gamma (768); their German
     * translations have the `sorting` values 768, 256 and 512, Delta has none. Read with German and overlays mixed,
     * ordered by `sorting`, the core sorts the default rows and overlays them afterwards, so the untranslated Delta
     * keeps its place: "Alpha DE", "Beta DE", "Delta", "Gamma DE".
     *
     * @see orderingPostsBySortingWithOverlaysMixedByExtbase()
     */
    #[Test]
    public function orderingPostsBySortingWithOverlaysMixedByCore(): void
    {
        // Given: the posts of page 3 with diverging sorting values in the translations
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ReadingTranslations/Given/SortedPosts.csv');

        // When: the posts are read with German and overlays mixed, ordered by sorting
        $this->get(Context::class)->setAspect('language', new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_MIXED));
        $posts = array_map(
            static fn(array $row): string => sprintf('%s (uid %d, language %d)', $row['title'], $row['uid'], $row['sys_language_uid']),
            $this->contentObjectRenderer->getRecords('tx_blogexample_domain_model_post', ['pidInList' => '3', 'orderBy' => 'sorting']),
        );

        // Then: the order is the one of the default records, Delta keeps its place
        self::assertSame([
            'Alpha DE (uid 11, language 1)',
            'Beta DE (uid 12, language 1)',
            'Delta (uid 17, language 0)',
            'Gamma DE (uid 13, language 1)',
        ], $posts);
    }

    /**
     * Read with German and overlays mixed, ordered by `sorting`, Extbase sorts the rows it selects by their own
     * `sorting` values and returns "Beta DE" (256), "Gamma DE" (512), "Delta" (600) and "Alpha DE" (768), where the
     * core returns the order of the default records.
     *
     * @see orderingPostsBySortingWithOverlaysMixedByCore()
     */
    #[Test]
    public function orderingPostsBySortingWithOverlaysMixedByExtbase(): void
    {
        self::markTestSkipped('Extbase differs from core: it orders by the `sorting` of the German rows, the core by the `sorting` of the default records, see Forge #70154.');

        // Given: the posts of page 3 with diverging sorting values in the translations
        // @phpstan-ignore deadCode.unreachable
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ReadingTranslations/Given/SortedPosts.csv');

        // When: the posts are read with German and overlays mixed, ordered by sorting
        $this->get(Context::class)->setAspect('language', new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_MIXED));
        $query = $this->get(PostRepository::class)->createQuery();
        $query->getQuerySettings()->setStoragePageIds([3]);
        $query->setOrderings(['sorting' => QueryInterface::ORDER_ASCENDING]);
        $posts = array_map(
            static fn(Post $post): string => sprintf('%s (uid %d, language %d)', $post->getTitle(), $post->getUid(), $post->_getProperty(AbstractDomainObject::PROPERTY_LANGUAGE_UID)),
            $query->execute()->toArray(),
        );

        // Then: the order is the one of the default records, Delta keeps its place
        self::assertSame([
            'Alpha DE (uid 11, language 1)',
            'Beta DE (uid 12, language 1)',
            'Delta (uid 17, language 0)',
            'Gamma DE (uid 13, language 1)',
        ], $posts);
    }

    /**
     * The core has no query without language restriction: it reads default rows and overlays them, so the result is the
     * one of reading with German and overlays on. Every post is returned once: posts 2, 5 and 7.
     *
     * @see readingWithoutLanguageRestrictionByExtbase()
     */
    #[Test]
    public function readingWithoutLanguageRestrictionByCore(): void
    {
        // When: all posts are read with German and overlays on
        $this->get(Context::class)->setAspect('language', new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_ON));
        $posts = array_map(
            static fn(array $row): string => sprintf('%s (uid %d, language %d)', $row['title'], $row['uid'], $row['sys_language_uid']),
            $this->contentObjectRenderer->getRecords('tx_blogexample_domain_model_post', ['pidInList' => '2', 'orderBy' => 'uid']),
        );

        // Then: every post is returned once
        self::assertSame([
            'Erster Beitrag (uid 2, language 1)',
            'Zweiter Beitrag (uid 5, language 1)',
            'All languages (uid 7, language -1)',
        ], $posts);
    }

    /**
     * With the language restriction of the query off, Extbase selects every row of the storage page and overlays each
     * row with its own language, so a record appears once per row: post 2 three times (as the default row with the
     * German title, as the German translation and as the French translation "Premier billet"), post 5 twice, plus post
     * 7 and the German post without default record (8). The result has seven records.
     *
     * @see readingWithoutLanguageRestrictionByCore()
     */
    #[Test]
    public function readingWithoutLanguageRestrictionByExtbase(): void
    {
        self::markTestSkipped('Extbase differs from core: it returns seven records with post 2 three times and post 5 twice, and includes the German post without default record (8), where the core returns each of the three posts once.');

        // When: all posts are read with German and overlays on
        // @phpstan-ignore deadCode.unreachable
        $this->get(Context::class)->setAspect('language', new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_ON));
        $query = $this->get(PostRepository::class)->createQuery();
        $query->getQuerySettings()->setRespectSysLanguage(false);
        $query->setOrderings(['uid' => QueryInterface::ORDER_ASCENDING]);
        $posts = array_map(
            static fn(Post $post): string => sprintf('%s (uid %d, language %d)', $post->getTitle(), $post->getUid(), $post->_getProperty(AbstractDomainObject::PROPERTY_LANGUAGE_UID)),
            $query->execute()->toArray(),
        );

        // Then: every post is returned once
        self::assertSame([
            'Erster Beitrag (uid 2, language 1)',
            'Zweiter Beitrag (uid 5, language 1)',
            'All languages (uid 7, language -1)',
        ], $posts);
    }

    /**
     * The core has no query without language restriction: it reads default rows and overlays them, and the number of
     * posts it has to show is the size of the overlaid result. With German and overlays on these are the posts 2, 5 and
     * 7, so the number is 3.
     *
     * @see countingPostsWithoutLanguageRestrictionByExtbase()
     */
    #[Test]
    public function countingPostsWithoutLanguageRestrictionByCore(): void
    {
        // When: the posts are counted with German and overlays on
        $this->get(Context::class)->setAspect('language', new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_ON));
        $count = count($this->contentObjectRenderer->getRecords('tx_blogexample_domain_model_post', ['pidInList' => '2']));

        // Then: the number of posts that are shown is returned
        self::assertSame(3, $count);
    }

    /**
     * With the language restriction of the query off, Extbase counts the rows of the storage page of every language in
     * the database: eight. That is not the 3 posts that the core has to show.
     *
     * @see countingPostsWithoutLanguageRestrictionByCore()
     */
    #[Test]
    public function countingPostsWithoutLanguageRestrictionByExtbase(): void
    {
        self::markTestSkipped('Extbase differs from core: `count()` returns 8, the rows of every language, where the core has 3 posts to show, see Forge #110798.');

        // When: the posts are counted with German and overlays on
        // @phpstan-ignore deadCode.unreachable
        $this->get(Context::class)->setAspect('language', new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_ON));
        $query = $this->get(PostRepository::class)->createQuery();
        $query->getQuerySettings()->setRespectSysLanguage(false);
        $count = $query->count();

        // Then: the number of posts that are shown is returned
        self::assertSame(3, $count);
    }

    /**
     * The German translations of the posts 2 and 5 are found through the uids of the default records. The core reads
     * the default rows with these uids and overlays them with German and overlays on: it returns "Erster Beitrag" for
     * post 2 and "Zweiter Beitrag" for post 5.
     *
     * @see findingATranslationByTheUidOfTheDefaultRecordByExtbase()
     */
    #[Test]
    public function findingATranslationByTheUidOfTheDefaultRecordByCore(): void
    {
        // When: the posts 2 and 5 are read with German and overlays on
        $this->get(Context::class)->setAspect('language', new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_ON));
        $posts = array_map(
            static fn(array $row): string => sprintf('%s (uid %d, language %d)', $row['title'], $row['uid'], $row['sys_language_uid']),
            $this->contentObjectRenderer->getRecords('tx_blogexample_domain_model_post', ['pidInList' => '2', 'uidInList' => '2,5', 'orderBy' => 'uid']),
        );

        // Then: the German translations are returned
        self::assertSame([
            'Erster Beitrag (uid 2, language 1)',
            'Zweiter Beitrag (uid 5, language 1)',
        ], $posts);
    }

    /**
     * Read with German and overlays on, Extbase selects the German rows, which have the uids 3 and 6, so a constraint
     * on the uids 2 and 5 of the default records matches none of them. The result is empty.
     *
     * @see findingATranslationByTheUidOfTheDefaultRecordByCore()
     */
    #[Test]
    public function findingATranslationByTheUidOfTheDefaultRecordByExtbase(): void
    {
        self::markTestSkipped('Extbase differs from core: it returns no record, because the constraint on the uids of the default records matches none of the selected German rows (3 and 6).');

        // When: the posts 2 and 5 are read with German and overlays on
        // @phpstan-ignore deadCode.unreachable
        $this->get(Context::class)->setAspect('language', new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_ON));
        $query = $this->get(PostRepository::class)->createQuery();
        $query->matching($query->in('uid', [2, 5]));
        $query->setOrderings(['uid' => QueryInterface::ORDER_ASCENDING]);
        $posts = array_map(
            static fn(Post $post): string => sprintf('%s (uid %d, language %d)', $post->getTitle(), $post->getUid(), $post->_getProperty(AbstractDomainObject::PROPERTY_LANGUAGE_UID)),
            $query->execute()->toArray(),
        );

        // Then: the German translations are returned
        self::assertSame([
            'Erster Beitrag (uid 2, language 1)',
            'Zweiter Beitrag (uid 5, language 1)',
        ], $posts);
    }

    /**
     * The core reads a single record by its uid like the TypoScript object `RECORDS` does (`source`
     * "tx_blogexample_domain_model_post_2"): it reads the row and overlays it with the language of the request. With
     * German and overlays on, post 2 is returned with the German title "Erster Beitrag" under the uid of the default
     * record (2).
     *
     * @see findingATranslatedPostByUidByExtbase()
     */
    #[Test]
    public function findingATranslatedPostByUidByCore(): void
    {
        // When: post 2 is read by its uid with German and overlays on
        $this->get(Context::class)->setAspect('language', new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_ON));
        $output = $this->contentObjectRenderer->cObjGetSingle('RECORDS', [
            'source' => 'tx_blogexample_domain_model_post_2',
            'tables' => 'tx_blogexample_domain_model_post',
            'dontCheckPid' => 1,
            'conf.' => [
                'tx_blogexample_domain_model_post' => 'TEXT',
                'tx_blogexample_domain_model_post.' => [
                    'value' => '{field:title} (uid {field:uid}, language {field:sys_language_uid})',
                    'insertData' => 1,
                ],
            ],
        ]);

        // Then: the post has the title, the uid and the language of the expected row
        self::assertSame('Erster Beitrag (uid 2, language 1)', $output);
    }

    /**
     * Extbase finds post 2 with `PostRepository::findByUid(2)` and returns it with the German title "Erster Beitrag"
     * under the uid of the default record (2), with German and overlays on, like the core.
     *
     * @see findingATranslatedPostByUidByCore()
     */
    #[Test]
    public function findingATranslatedPostByUidByExtbase(): void
    {
        // When: post 2 is read by its uid with German and overlays on
        $this->get(Context::class)->setAspect('language', new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_ON));
        $post = $this->get(PostRepository::class)->findByUid(2);
        $output = sprintf('%s (uid %d, language %d)', $post->getTitle(), $post->getUid(), $post->_getProperty(AbstractDomainObject::PROPERTY_LANGUAGE_UID));

        // Then: the post has the title, the uid and the language of the expected row
        self::assertSame('Erster Beitrag (uid 2, language 1)', $output);
    }

    /**
     * The core reads a single record by its uid like the TypoScript object `RECORDS` does (`source`
     * "tx_blogexample_domain_model_post_3"). Post 3 is the German translation of post 2. With the default language the
     * core does no overlay, because it overlays only for a language above 0, so it returns the row as it is stored: the
     * uid 3 with the title "Erster Beitrag".
     *
     * @see findingAPostByTheUidOfItsTranslationInTheDefaultLanguageByExtbase()
     */
    #[Test]
    public function findingAPostByTheUidOfItsTranslationInTheDefaultLanguageByCore(): void
    {
        // When: post 3 is read by its uid with the default language and overlays on
        $this->get(Context::class)->setAspect('language', new LanguageAspect(0, 0, LanguageAspect::OVERLAYS_ON));
        $output = $this->contentObjectRenderer->cObjGetSingle('RECORDS', [
            'source' => 'tx_blogexample_domain_model_post_3',
            'tables' => 'tx_blogexample_domain_model_post',
            'dontCheckPid' => 1,
            'conf.' => [
                'tx_blogexample_domain_model_post' => 'TEXT',
                'tx_blogexample_domain_model_post.' => [
                    'value' => '{field:title} (uid {field:uid}, language {field:sys_language_uid})',
                    'insertData' => 1,
                ],
            ],
        ]);

        // Then: the post has the title, the uid and the language of the expected row
        self::assertSame('Erster Beitrag (uid 3, language 1)', $output);
    }

    /**
     * Extbase finds the German translation (3) with `PostRepository::findByUid(3)` in the default language and returns
     * it with the German title "Erster Beitrag" under the uid of the default record (2), where the core returns the row
     * with its own uid (3).
     *
     * @see findingAPostByTheUidOfItsTranslationInTheDefaultLanguageByCore()
     */
    #[Test]
    public function findingAPostByTheUidOfItsTranslationInTheDefaultLanguageByExtbase(): void
    {
        self::markTestSkipped('Extbase differs from core: it returns the German translation under the uid of its default record (2), where the core returns the row with its own uid (3), see Forge #110798.');

        // When: post 3 is read by its uid with the default language and overlays on
        // @phpstan-ignore deadCode.unreachable
        $this->get(Context::class)->setAspect('language', new LanguageAspect(0, 0, LanguageAspect::OVERLAYS_ON));
        $post = $this->get(PostRepository::class)->findByUid(3);
        $output = sprintf('%s (uid %d, language %d)', $post->getTitle(), $post->getUid(), $post->_getProperty(AbstractDomainObject::PROPERTY_LANGUAGE_UID));

        // Then: the post has the title, the uid and the language of the expected row
        self::assertSame('Erster Beitrag (uid 3, language 1)', $output);
    }

    /**
     * Post 7 is a record for all languages (language -1). The core returns it unchanged and once, with German and with
     * French.
     *
     * @see aRecordForAllLanguagesIsReadOnceByExtbase()
     */
    #[Test]
    public function aRecordForAllLanguagesIsReadOnceByCore(): void
    {
        // When: all posts are read with German and with French, overlays on
        $this->get(Context::class)->setAspect('language', new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_ON));
        $german = array_map(
            static fn(array $row): string => sprintf('%s (uid %d, language %d)', $row['title'], $row['uid'], $row['sys_language_uid']),
            $this->contentObjectRenderer->getRecords('tx_blogexample_domain_model_post', ['pidInList' => '2', 'orderBy' => 'uid']),
        );
        $this->get(Context::class)->setAspect('language', new LanguageAspect(2, 2, LanguageAspect::OVERLAYS_ON));
        $french = array_map(
            static fn(array $row): string => sprintf('%s (uid %d, language %d)', $row['title'], $row['uid'], $row['sys_language_uid']),
            $this->contentObjectRenderer->getRecords('tx_blogexample_domain_model_post', ['pidInList' => '2', 'orderBy' => 'uid']),
        );

        // Then: post 7 appears once in each result
        self::assertSame(['All languages (uid 7, language -1)'], array_values(array_filter($german, static fn(string $post): bool => str_contains($post, 'uid 7,'))));
        self::assertSame(['All languages (uid 7, language -1)'], array_values(array_filter($french, static fn(string $post): bool => str_contains($post, 'uid 7,'))));
    }

    /**
     * Read with German and with French and overlays on, Extbase returns the record for all languages (post 7) once per
     * result and unchanged, as the core does.
     *
     * @see aRecordForAllLanguagesIsReadOnceByCore()
     */
    #[Test]
    public function aRecordForAllLanguagesIsReadOnceByExtbase(): void
    {
        // When: all posts are read with German and with French, overlays on
        $this->get(Context::class)->setAspect('language', new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_ON));
        $query = $this->get(PostRepository::class)->createQuery();
        $query->setOrderings(['uid' => QueryInterface::ORDER_ASCENDING]);
        $german = array_map(
            static fn(Post $post): string => sprintf('%s (uid %d, language %d)', $post->getTitle(), $post->getUid(), $post->_getProperty(AbstractDomainObject::PROPERTY_LANGUAGE_UID)),
            $query->execute()->toArray(),
        );
        $this->get(PersistenceManager::class)->clearState();
        $this->get(Context::class)->setAspect('language', new LanguageAspect(2, 2, LanguageAspect::OVERLAYS_ON));
        $query = $this->get(PostRepository::class)->createQuery();
        $query->setOrderings(['uid' => QueryInterface::ORDER_ASCENDING]);
        $french = array_map(
            static fn(Post $post): string => sprintf('%s (uid %d, language %d)', $post->getTitle(), $post->getUid(), $post->_getProperty(AbstractDomainObject::PROPERTY_LANGUAGE_UID)),
            $query->execute()->toArray(),
        );

        // Then: post 7 appears once in each result
        self::assertSame(['All languages (uid 7, language -1)'], array_values(array_filter($german, static fn(string $post): bool => str_contains($post, 'uid 7,'))));
        self::assertSame(['All languages (uid 7, language -1)'], array_values(array_filter($french, static fn(string $post): bool => str_contains($post, 'uid 7,'))));
    }
}
