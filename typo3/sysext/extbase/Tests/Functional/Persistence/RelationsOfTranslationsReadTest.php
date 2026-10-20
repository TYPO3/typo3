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
use TYPO3\CMS\Core\Domain\Persistence\RecordIdentityMap;
use TYPO3\CMS\Core\Domain\RecordFactory;
use TYPO3\CMS\Core\Domain\RecordInterface;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Page\PageInformation;
use TYPO3\CMS\Frontend\Page\PageParts;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Tests\BlogExample\Domain\Model\Folder;
use TYPO3Tests\BlogExample\Domain\Model\Post;
use TYPO3Tests\BlogExample\Domain\Repository\BlogRepository;
use TYPO3Tests\BlogExample\Domain\Repository\FolderRepository;
use TYPO3Tests\BlogExample\Domain\Repository\PostRepository;

/**
 * Reading the relations of translated records: the posts of a blog (field `posts`), a tree of folders (field
 * `children`), the blog and the related posts of a post (fields `blog` and `related_posts`), and queries with
 * `equals()`, `in()` and `contains()` on a relation to a translated record.
 *
 * Every scenario is tested twice against the same expectation: once with the core API as oracle (suffix "ByCore"), once
 * with Extbase (suffix "ByExtbase"). The core side reads a record in a frontend request as `RecordCollector` does: the
 * record is selected with `ContentObjectRenderer::getRecords()` (language restriction, enable fields, version overlay
 * and language overlay of the context), turned into a resolved record with
 * `RecordFactory::createResolvedRecordFromDatabaseRow()`, and its relation field is read with `Record::get()`. The
 * `RelationHandler` finds the related uids for the uid of the translation if the record is overlaid, and the related
 * rows pass the version overlay, the language overlay and the `RecordAccessVoter` of the same context. The Extbase side
 * reads the record with a repository and follows the property. A test of Extbase that differs from core is skipped.
 *
 * The site has the languages English (0), German (1) and French (2), French falls back to German and English. All
 * records are on the storage page 2.
 */
final class RelationsOfTranslationsReadTest extends FunctionalTestCase
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
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RelationsOfTranslationsRead/Pages.csv');
        $this->writeSiteConfiguration(
            'test',
            $this->buildSiteConfiguration(1, '/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/'),
                $this->buildLanguageConfiguration('DE', '/de/'),
                $this->buildLanguageConfiguration('FR', '/fr/', ['DE', 'EN']),
            ],
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
     * Blog 1 has the German translation, blog 2. "Post DE translation" (12) is the translation of "Post EN with
     * translation" (11), "Post DE free mode" (13) has no default post and a hidden free mode post (14) too, and all
     * three point to blog 2. The core selects blog 1 with `CONTENT`, which overlays it with the German blog 2, and
     * reads the field `posts` of that record like the record API (`RecordFactory`) does: the `RelationHandler` finds
     * the posts that point to the uid of the translation, 2. With overlays on, English returns "Post EN" and "Post EN
     * with translation", German returns "Post DE translation" and "Post DE free mode", and French, which has no
     * translation of the blog, returns no post, because the overlay drops the blog. The hidden post (14) is not
     * returned.
     *
     * @see readingThePostsOfATranslatedBlogWithOverlaysOnByExtbase()
     */
    #[Test]
    public function readingThePostsOfATranslatedBlogWithOverlaysOnByCore(): void
    {
        // Given: blog 1 with its German translation (2) and posts that point to either blog
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RelationsOfTranslationsRead/Posts.csv');

        // When: blog 1 and its posts are read in English, German and French with overlays on
        $pageInformation = new PageInformation();
        $pageInformation->setId(1);
        $pageInformation->setContentFromPid(1);
        $request = new ServerRequest()
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE)
            ->withAttribute('frontend.page.information', $pageInformation)
            ->withAttribute('frontend.cache.collector', new CacheDataCollector())
            ->withAttribute('frontend.page.parts', new PageParts());
        $reads = [
            'en' => [1, new LanguageAspect(0, 0, LanguageAspect::OVERLAYS_ON)],
            'de' => [1, new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_ON)],
            'fr' => [1, new LanguageAspect(2, 2, LanguageAspect::OVERLAYS_ON)],
        ];
        $result = [];
        foreach ($reads as $key => [$blogUid, $languageAspect]) {
            $this->get(Context::class)->setAspect('language', $languageAspect);
            $contentObjectRenderer = $this->get(ContentObjectRenderer::class);
            $contentObjectRenderer->setRequest($request);
            $blogRow = $contentObjectRenderer->getRecords('tx_blogexample_domain_model_blog', ['uidInList' => (string)$blogUid, 'pidInList' => '2'])[0] ?? null;
            $titles = [];
            if ($blogRow !== null) {
                $blog = $this->get(RecordFactory::class)->createResolvedRecordFromDatabaseRow('tx_blogexample_domain_model_blog', $blogRow, $this->get(Context::class), new RecordIdentityMap());
                foreach ($blog->get('posts') as $post) {
                    $titles[] = $post->get('title');
                }
            }
            $result[$key] = $titles;
        }

        // Then: the posts of the German blog are returned in German
        self::assertSame([
            'en' => ['Post EN', 'Post EN with translation'],
            'de' => ['Post DE translation', 'Post DE free mode'],
            'fr' => [],
        ], $result);
    }

    /**
     * Extbase loads the posts of the German blog 2 and returns the same posts as the core: "Post EN" and "Post EN with
     * translation" for English, "Post DE translation" and "Post DE free mode" for German, and no post for French.
     *
     * @see readingThePostsOfATranslatedBlogWithOverlaysOnByCore()
     */
    #[Test]
    public function readingThePostsOfATranslatedBlogWithOverlaysOnByExtbase(): void
    {
        // Given: blog 1 with its German translation (2) and posts that point to either blog
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RelationsOfTranslationsRead/Posts.csv');

        // When: blog 1 and its posts are read in English, German and French with overlays on
        $reads = [
            'en' => [1, new LanguageAspect(0, 0, LanguageAspect::OVERLAYS_ON)],
            'de' => [1, new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_ON)],
            'fr' => [1, new LanguageAspect(2, 2, LanguageAspect::OVERLAYS_ON)],
        ];
        $result = [];
        foreach ($reads as $key => [$blogUid, $languageAspect]) {
            $this->get(Context::class)->setAspect('language', $languageAspect);
            $this->get(PersistenceManager::class)->clearState();
            $blog = $this->get(BlogRepository::class)->findByUid($blogUid);
            $result[$key] = $blog === null ? [] : array_map(static fn(Post $post): string => $post->getTitle(), $blog->getPosts()->toArray());
        }

        // Then: the posts of the German blog are returned in German
        self::assertSame([
            'en' => ['Post EN', 'Post EN with translation'],
            'de' => ['Post DE translation', 'Post DE free mode'],
            'fr' => [],
        ], $result);
    }

    /**
     * With overlays mixed, the core reads the field `posts` of the record of blog 1 as with overlays on. English
     * returns "Post EN" and "Post EN with translation". German returns "Post DE translation" and "Post DE free mode",
     * the posts that point to the German blog 2; "Post EN" is not part of the result, because no row points from it to
     * blog 2. French, which has no translation of the blog, keeps the English blog and returns "Post EN" and "Post EN
     * with translation".
     *
     * @see readingThePostsOfATranslatedBlogWithOverlaysMixedByExtbase()
     */
    #[Test]
    public function readingThePostsOfATranslatedBlogWithOverlaysMixedByCore(): void
    {
        // Given: blog 1 with its German translation (2) and posts that point to either blog
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RelationsOfTranslationsRead/Posts.csv');

        // When: blog 1 and its posts are read in English, German and French with overlays mixed
        $pageInformation = new PageInformation();
        $pageInformation->setId(1);
        $pageInformation->setContentFromPid(1);
        $request = new ServerRequest()
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE)
            ->withAttribute('frontend.page.information', $pageInformation)
            ->withAttribute('frontend.cache.collector', new CacheDataCollector())
            ->withAttribute('frontend.page.parts', new PageParts());
        $reads = [
            'en' => [1, new LanguageAspect(0, 0, LanguageAspect::OVERLAYS_MIXED)],
            'de' => [1, new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_MIXED)],
            'fr' => [1, new LanguageAspect(2, 2, LanguageAspect::OVERLAYS_MIXED)],
        ];
        $result = [];
        foreach ($reads as $key => [$blogUid, $languageAspect]) {
            $this->get(Context::class)->setAspect('language', $languageAspect);
            $contentObjectRenderer = $this->get(ContentObjectRenderer::class);
            $contentObjectRenderer->setRequest($request);
            $blogRow = $contentObjectRenderer->getRecords('tx_blogexample_domain_model_blog', ['uidInList' => (string)$blogUid, 'pidInList' => '2'])[0] ?? null;
            $titles = [];
            if ($blogRow !== null) {
                $blog = $this->get(RecordFactory::class)->createResolvedRecordFromDatabaseRow('tx_blogexample_domain_model_blog', $blogRow, $this->get(Context::class), new RecordIdentityMap());
                foreach ($blog->get('posts') as $post) {
                    $titles[] = $post->get('title');
                }
            }
            $result[$key] = $titles;
        }

        // Then: the posts of the German blog are returned in German, French keeps the English blog
        self::assertSame([
            'en' => ['Post EN', 'Post EN with translation'],
            'de' => ['Post DE translation', 'Post DE free mode'],
            'fr' => ['Post EN', 'Post EN with translation'],
        ], $result);
    }

    /**
     * Extbase loads the posts of the German blog 2 and returns the same posts as the core: "Post EN" and "Post EN with
     * translation" for English, "Post DE translation" and "Post DE free mode" for German, and "Post EN" and "Post EN
     * with translation" for French.
     *
     * @see readingThePostsOfATranslatedBlogWithOverlaysMixedByCore()
     */
    #[Test]
    public function readingThePostsOfATranslatedBlogWithOverlaysMixedByExtbase(): void
    {
        // Given: blog 1 with its German translation (2) and posts that point to either blog
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RelationsOfTranslationsRead/Posts.csv');

        // When: blog 1 and its posts are read in English, German and French with overlays mixed
        $reads = [
            'en' => [1, new LanguageAspect(0, 0, LanguageAspect::OVERLAYS_MIXED)],
            'de' => [1, new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_MIXED)],
            'fr' => [1, new LanguageAspect(2, 2, LanguageAspect::OVERLAYS_MIXED)],
        ];
        $result = [];
        foreach ($reads as $key => [$blogUid, $languageAspect]) {
            $this->get(Context::class)->setAspect('language', $languageAspect);
            $this->get(PersistenceManager::class)->clearState();
            $blog = $this->get(BlogRepository::class)->findByUid($blogUid);
            $result[$key] = $blog === null ? [] : array_map(static fn(Post $post): string => $post->getTitle(), $blog->getPosts()->toArray());
        }

        // Then: the posts of the German blog are returned in German, French keeps the English blog
        self::assertSame([
            'en' => ['Post EN', 'Post EN with translation'],
            'de' => ['Post DE translation', 'Post DE free mode'],
            'fr' => ['Post EN', 'Post EN with translation'],
        ], $result);
    }

    /**
     * With overlays off the core does no overlay: blog 1 is the English blog and blog 2 the German one, and each
     * returns the posts of its language that point to it. Blog 1 returns "Post EN" and "Post EN with translation", blog
     * 2 returns "Post DE translation" and "Post DE free mode". The hidden free mode post (14) is not returned.
     *
     * @see readingThePostsOfATranslatedBlogWithOverlaysOffByExtbase()
     */
    #[Test]
    public function readingThePostsOfATranslatedBlogWithOverlaysOffByCore(): void
    {
        // Given: blog 1 with its German translation (2) and posts that point to either blog
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RelationsOfTranslationsRead/Posts.csv');

        // When: the posts of blog 1 in English and of blog 2 in German are read with overlays off
        $pageInformation = new PageInformation();
        $pageInformation->setId(1);
        $pageInformation->setContentFromPid(1);
        $request = new ServerRequest()
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE)
            ->withAttribute('frontend.page.information', $pageInformation)
            ->withAttribute('frontend.cache.collector', new CacheDataCollector())
            ->withAttribute('frontend.page.parts', new PageParts());
        $reads = [
            'en' => [1, new LanguageAspect(0, 0, LanguageAspect::OVERLAYS_OFF)],
            'de' => [2, new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_OFF)],
        ];
        $result = [];
        foreach ($reads as $key => [$blogUid, $languageAspect]) {
            $this->get(Context::class)->setAspect('language', $languageAspect);
            $contentObjectRenderer = $this->get(ContentObjectRenderer::class);
            $contentObjectRenderer->setRequest($request);
            $blogRow = $contentObjectRenderer->getRecords('tx_blogexample_domain_model_blog', ['uidInList' => (string)$blogUid, 'pidInList' => '2'])[0] ?? null;
            $titles = [];
            if ($blogRow !== null) {
                $blog = $this->get(RecordFactory::class)->createResolvedRecordFromDatabaseRow('tx_blogexample_domain_model_blog', $blogRow, $this->get(Context::class), new RecordIdentityMap());
                foreach ($blog->get('posts') as $post) {
                    $titles[] = $post->get('title');
                }
            }
            $result[$key] = $titles;
        }

        // Then: every blog returns the posts that point to it
        self::assertSame([
            'en' => ['Post EN', 'Post EN with translation'],
            'de' => ['Post DE translation', 'Post DE free mode'],
        ], $result);
    }

    /**
     * Read with overlays off, Extbase returns the same posts as the core: "Post EN" and "Post EN with translation" for
     * blog 1, "Post DE translation" and "Post DE free mode" for blog 2.
     *
     * @see readingThePostsOfATranslatedBlogWithOverlaysOffByCore()
     */
    #[Test]
    public function readingThePostsOfATranslatedBlogWithOverlaysOffByExtbase(): void
    {
        // Given: blog 1 with its German translation (2) and posts that point to either blog
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RelationsOfTranslationsRead/Posts.csv');

        // When: the posts of blog 1 in English and of blog 2 in German are read with overlays off
        $reads = [
            'en' => [1, new LanguageAspect(0, 0, LanguageAspect::OVERLAYS_OFF)],
            'de' => [2, new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_OFF)],
        ];
        $result = [];
        foreach ($reads as $key => [$blogUid, $languageAspect]) {
            $this->get(Context::class)->setAspect('language', $languageAspect);
            $this->get(PersistenceManager::class)->clearState();
            $blog = $this->get(BlogRepository::class)->findByUid($blogUid);
            $result[$key] = $blog === null ? [] : array_map(static fn(Post $post): string => $post->getTitle(), $blog->getPosts()->toArray());
        }

        // Then: every blog returns the posts that point to it
        self::assertSame([
            'en' => ['Post EN', 'Post EN with translation'],
            'de' => ['Post DE translation', 'Post DE free mode'],
        ], $result);
    }

    /**
     * Folder 1, "Root EN", has the children "Child only EN" (10), "Child EN and DE" (11) with the child "Grandchild EN"
     * (20), and "Child all languages" (13, language -1). Its German translation, "Root DE" (2), owns "Child DE" (12),
     * the translation of "Child EN and DE", which has the child "Grandchild DE" (21). "Root EN only" (3) has the child
     * "Only child EN" (30) and no translation. The core selects the root with `CONTENT` and reads the field `children`
     * level by level like the record API (`RecordFactory`) does. With overlays on, English returns the whole English
     * tree. German returns "Root DE > Child DE > Grandchild DE": the `RelationHandler` finds the children that point to
     * the uid of the translation, 2, so "Child only EN" and "Child all languages", which point to the default folder 1,
     * are not part of it. "Root EN only" and the trees in French are not returned, because the overlay drops roots
     * without translation.
     *
     * @see readingATreeOfFoldersWithOverlaysOnByExtbase()
     */
    #[Test]
    public function readingATreeOfFoldersWithOverlaysOnByCore(): void
    {
        // Given: two trees of folders, one of them with a German translation of the root
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RelationsOfTranslationsRead/Folders.csv');

        // When: both roots are read with their tree in English, German and French with overlays on
        $pageInformation = new PageInformation();
        $pageInformation->setId(1);
        $pageInformation->setContentFromPid(1);
        $request = new ServerRequest()
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE)
            ->withAttribute('frontend.page.information', $pageInformation)
            ->withAttribute('frontend.cache.collector', new CacheDataCollector())
            ->withAttribute('frontend.page.parts', new PageParts());
        $reads = [
            'en:1' => [1, new LanguageAspect(0, 0, LanguageAspect::OVERLAYS_ON)],
            'en:3' => [3, new LanguageAspect(0, 0, LanguageAspect::OVERLAYS_ON)],
            'de:1' => [1, new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_ON)],
            'de:3' => [3, new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_ON)],
            'fr:1' => [1, new LanguageAspect(2, 2, LanguageAspect::OVERLAYS_ON)],
            'fr:3' => [3, new LanguageAspect(2, 2, LanguageAspect::OVERLAYS_ON)],
        ];
        $collectPaths = static function (RecordInterface $folder, string $parentPath) use (&$collectPaths): array {
            $path = $parentPath === '' ? $folder->get('title') : $parentPath . ' > ' . $folder->get('title');
            $paths = [$path];
            foreach ($folder->get('children') as $child) {
                array_push($paths, ...$collectPaths($child, $path));
            }
            return $paths;
        };
        $result = [];
        foreach ($reads as $key => [$folderUid, $languageAspect]) {
            $this->get(Context::class)->setAspect('language', $languageAspect);
            $contentObjectRenderer = $this->get(ContentObjectRenderer::class);
            $contentObjectRenderer->setRequest($request);
            $folderRow = $contentObjectRenderer->getRecords('tx_blogexample_domain_model_folder', ['uidInList' => (string)$folderUid, 'pidInList' => '2'])[0] ?? null;
            $paths = [];
            if ($folderRow !== null) {
                $folder = $this->get(RecordFactory::class)->createResolvedRecordFromDatabaseRow('tx_blogexample_domain_model_folder', $folderRow, $this->get(Context::class), new RecordIdentityMap());
                $paths = $collectPaths($folder, '');
            }
            $result[$key] = $paths;
        }

        // Then: the English tree in English, the tree of the German folders in German, and nothing for the roots
        // without translation
        self::assertSame([
            'en:1' => [
                'Root EN',
                'Root EN > Child only EN',
                'Root EN > Child EN and DE',
                'Root EN > Child EN and DE > Grandchild EN',
                'Root EN > Child all languages',
            ],
            'en:3' => ['Root EN only', 'Root EN only > Only child EN'],
            'de:1' => ['Root DE', 'Root DE > Child DE', 'Root DE > Child DE > Grandchild DE'],
            'de:3' => [],
            'fr:1' => [],
            'fr:3' => [],
        ], $result);
    }

    /**
     * Extbase returns the same trees as the core: the whole English tree for "Root EN", "Root DE > Child DE >
     * Grandchild DE" for the German tree of "Root DE", and no tree for the roots without translation.
     *
     * @see readingATreeOfFoldersWithOverlaysOnByCore()
     */
    #[Test]
    public function readingATreeOfFoldersWithOverlaysOnByExtbase(): void
    {
        // Given: two trees of folders, one of them with a German translation of the root
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RelationsOfTranslationsRead/Folders.csv');

        // When: both roots are read with their tree in English, German and French with overlays on
        $reads = [
            'en:1' => [1, new LanguageAspect(0, 0, LanguageAspect::OVERLAYS_ON)],
            'en:3' => [3, new LanguageAspect(0, 0, LanguageAspect::OVERLAYS_ON)],
            'de:1' => [1, new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_ON)],
            'de:3' => [3, new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_ON)],
            'fr:1' => [1, new LanguageAspect(2, 2, LanguageAspect::OVERLAYS_ON)],
            'fr:3' => [3, new LanguageAspect(2, 2, LanguageAspect::OVERLAYS_ON)],
        ];
        $collectPaths = static function (Folder $folder, string $parentPath) use (&$collectPaths): array {
            $path = $parentPath === '' ? $folder->getTitle() : $parentPath . ' > ' . $folder->getTitle();
            $paths = [$path];
            foreach ($folder->getChildren() as $child) {
                array_push($paths, ...$collectPaths($child, $path));
            }
            return $paths;
        };
        $result = [];
        foreach ($reads as $key => [$folderUid, $languageAspect]) {
            $this->get(Context::class)->setAspect('language', $languageAspect);
            $this->get(PersistenceManager::class)->clearState();
            $folder = $this->get(FolderRepository::class)->findByUid($folderUid);
            $result[$key] = $folder === null ? [] : $collectPaths($folder, '');
        }

        // Then: the English tree in English, the tree of the German folders in German, and nothing for the roots
        // without translation
        self::assertSame([
            'en:1' => [
                'Root EN',
                'Root EN > Child only EN',
                'Root EN > Child EN and DE',
                'Root EN > Child EN and DE > Grandchild EN',
                'Root EN > Child all languages',
            ],
            'en:3' => ['Root EN only', 'Root EN only > Only child EN'],
            'de:1' => ['Root DE', 'Root DE > Child DE', 'Root DE > Child DE > Grandchild DE'],
            'de:3' => [],
            'fr:1' => [],
            'fr:3' => [],
        ], $result);
    }

    /**
     * With overlays mixed, German returns the same tree of "Root DE" as with overlays on, "Root DE > Child DE >
     * Grandchild DE", because its children point to the German folder 2; "Child only EN" and "Child all languages"
     * point to the default folder 1 and are not read. "Root EN only" with "Only child EN" stays in English in German,
     * and French, which has no translations, returns the English trees.
     *
     * @see readingATreeOfFoldersWithOverlaysMixedByExtbase()
     */
    #[Test]
    public function readingATreeOfFoldersWithOverlaysMixedByCore(): void
    {
        // Given: two trees of folders, one of them with a German translation of the root
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RelationsOfTranslationsRead/Folders.csv');

        // When: both roots are read with their tree in English, German and French with overlays mixed
        $pageInformation = new PageInformation();
        $pageInformation->setId(1);
        $pageInformation->setContentFromPid(1);
        $request = new ServerRequest()
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE)
            ->withAttribute('frontend.page.information', $pageInformation)
            ->withAttribute('frontend.cache.collector', new CacheDataCollector())
            ->withAttribute('frontend.page.parts', new PageParts());
        $reads = [
            'en:1' => [1, new LanguageAspect(0, 0, LanguageAspect::OVERLAYS_MIXED)],
            'en:3' => [3, new LanguageAspect(0, 0, LanguageAspect::OVERLAYS_MIXED)],
            'de:1' => [1, new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_MIXED)],
            'de:3' => [3, new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_MIXED)],
            'fr:1' => [1, new LanguageAspect(2, 2, LanguageAspect::OVERLAYS_MIXED)],
            'fr:3' => [3, new LanguageAspect(2, 2, LanguageAspect::OVERLAYS_MIXED)],
        ];
        $collectPaths = static function (RecordInterface $folder, string $parentPath) use (&$collectPaths): array {
            $path = $parentPath === '' ? $folder->get('title') : $parentPath . ' > ' . $folder->get('title');
            $paths = [$path];
            foreach ($folder->get('children') as $child) {
                array_push($paths, ...$collectPaths($child, $path));
            }
            return $paths;
        };
        $result = [];
        foreach ($reads as $key => [$folderUid, $languageAspect]) {
            $this->get(Context::class)->setAspect('language', $languageAspect);
            $contentObjectRenderer = $this->get(ContentObjectRenderer::class);
            $contentObjectRenderer->setRequest($request);
            $folderRow = $contentObjectRenderer->getRecords('tx_blogexample_domain_model_folder', ['uidInList' => (string)$folderUid, 'pidInList' => '2'])[0] ?? null;
            $paths = [];
            if ($folderRow !== null) {
                $folder = $this->get(RecordFactory::class)->createResolvedRecordFromDatabaseRow('tx_blogexample_domain_model_folder', $folderRow, $this->get(Context::class), new RecordIdentityMap());
                $paths = $collectPaths($folder, '');
            }
            $result[$key] = $paths;
        }

        // Then: the tree of the German folders, and the English trees where there is no translation
        self::assertSame([
            'en:1' => [
                'Root EN',
                'Root EN > Child only EN',
                'Root EN > Child EN and DE',
                'Root EN > Child EN and DE > Grandchild EN',
                'Root EN > Child all languages',
            ],
            'en:3' => ['Root EN only', 'Root EN only > Only child EN'],
            'de:1' => ['Root DE', 'Root DE > Child DE', 'Root DE > Child DE > Grandchild DE'],
            'de:3' => ['Root EN only', 'Root EN only > Only child EN'],
            'fr:1' => [
                'Root EN',
                'Root EN > Child only EN',
                'Root EN > Child EN and DE',
                'Root EN > Child EN and DE > Grandchild EN',
                'Root EN > Child all languages',
            ],
            'fr:3' => ['Root EN only', 'Root EN only > Only child EN'],
        ], $result);
    }

    /**
     * Extbase returns the same trees as the core: "Root DE > Child DE > Grandchild DE" for "Root DE", the English trees
     * for "Root EN only" in German and for both roots in French.
     *
     * @see readingATreeOfFoldersWithOverlaysMixedByCore()
     */
    #[Test]
    public function readingATreeOfFoldersWithOverlaysMixedByExtbase(): void
    {
        // Given: two trees of folders, one of them with a German translation of the root
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RelationsOfTranslationsRead/Folders.csv');

        // When: both roots are read with their tree in English, German and French with overlays mixed
        $reads = [
            'en:1' => [1, new LanguageAspect(0, 0, LanguageAspect::OVERLAYS_MIXED)],
            'en:3' => [3, new LanguageAspect(0, 0, LanguageAspect::OVERLAYS_MIXED)],
            'de:1' => [1, new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_MIXED)],
            'de:3' => [3, new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_MIXED)],
            'fr:1' => [1, new LanguageAspect(2, 2, LanguageAspect::OVERLAYS_MIXED)],
            'fr:3' => [3, new LanguageAspect(2, 2, LanguageAspect::OVERLAYS_MIXED)],
        ];
        $collectPaths = static function (Folder $folder, string $parentPath) use (&$collectPaths): array {
            $path = $parentPath === '' ? $folder->getTitle() : $parentPath . ' > ' . $folder->getTitle();
            $paths = [$path];
            foreach ($folder->getChildren() as $child) {
                array_push($paths, ...$collectPaths($child, $path));
            }
            return $paths;
        };
        $result = [];
        foreach ($reads as $key => [$folderUid, $languageAspect]) {
            $this->get(Context::class)->setAspect('language', $languageAspect);
            $this->get(PersistenceManager::class)->clearState();
            $folder = $this->get(FolderRepository::class)->findByUid($folderUid);
            $result[$key] = $folder === null ? [] : $collectPaths($folder, '');
        }

        // Then: the tree of the German folders, and the English trees where there is no translation
        self::assertSame([
            'en:1' => [
                'Root EN',
                'Root EN > Child only EN',
                'Root EN > Child EN and DE',
                'Root EN > Child EN and DE > Grandchild EN',
                'Root EN > Child all languages',
            ],
            'en:3' => ['Root EN only', 'Root EN only > Only child EN'],
            'de:1' => ['Root DE', 'Root DE > Child DE', 'Root DE > Child DE > Grandchild DE'],
            'de:3' => ['Root EN only', 'Root EN only > Only child EN'],
            'fr:1' => [
                'Root EN',
                'Root EN > Child only EN',
                'Root EN > Child EN and DE',
                'Root EN > Child EN and DE > Grandchild EN',
                'Root EN > Child all languages',
            ],
            'fr:3' => ['Root EN only', 'Root EN only > Only child EN'],
        ], $result);
    }

    /**
     * With overlays off the core does no overlay and reads the folders of the language as they are: the English "Root
     * EN" (1) returns its whole English tree, and the German "Root DE" (2) returns "Root DE > Child DE > Grandchild
     * DE", the folders that point to the German folders.
     *
     * @see readingATreeOfFoldersWithOverlaysOffByExtbase()
     */
    #[Test]
    public function readingATreeOfFoldersWithOverlaysOffByCore(): void
    {
        // Given: two trees of folders, one of them with a German translation of the root
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RelationsOfTranslationsRead/Folders.csv');

        // When: the English tree of folder 1 and the German tree of folder 2 are read with overlays off
        $pageInformation = new PageInformation();
        $pageInformation->setId(1);
        $pageInformation->setContentFromPid(1);
        $request = new ServerRequest()
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE)
            ->withAttribute('frontend.page.information', $pageInformation)
            ->withAttribute('frontend.cache.collector', new CacheDataCollector())
            ->withAttribute('frontend.page.parts', new PageParts());
        $reads = [
            'en:1' => [1, new LanguageAspect(0, 0, LanguageAspect::OVERLAYS_OFF)],
            'de:2' => [2, new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_OFF)],
        ];
        $collectPaths = static function (RecordInterface $folder, string $parentPath) use (&$collectPaths): array {
            $path = $parentPath === '' ? $folder->get('title') : $parentPath . ' > ' . $folder->get('title');
            $paths = [$path];
            foreach ($folder->get('children') as $child) {
                array_push($paths, ...$collectPaths($child, $path));
            }
            return $paths;
        };
        $result = [];
        foreach ($reads as $key => [$folderUid, $languageAspect]) {
            $this->get(Context::class)->setAspect('language', $languageAspect);
            $contentObjectRenderer = $this->get(ContentObjectRenderer::class);
            $contentObjectRenderer->setRequest($request);
            $folderRow = $contentObjectRenderer->getRecords('tx_blogexample_domain_model_folder', ['uidInList' => (string)$folderUid, 'pidInList' => '2'])[0] ?? null;
            $paths = [];
            if ($folderRow !== null) {
                $folder = $this->get(RecordFactory::class)->createResolvedRecordFromDatabaseRow('tx_blogexample_domain_model_folder', $folderRow, $this->get(Context::class), new RecordIdentityMap());
                $paths = $collectPaths($folder, '');
            }
            $result[$key] = $paths;
        }

        // Then: every tree consists of the folders of its language and of all languages
        self::assertSame([
            'en:1' => [
                'Root EN',
                'Root EN > Child only EN',
                'Root EN > Child EN and DE',
                'Root EN > Child EN and DE > Grandchild EN',
                'Root EN > Child all languages',
            ],
            'de:2' => ['Root DE', 'Root DE > Child DE', 'Root DE > Child DE > Grandchild DE'],
        ], $result);
    }

    /**
     * Read with overlays off, Extbase returns the same trees as the core: the whole English tree for "Root EN" and
     * "Root DE > Child DE > Grandchild DE" for "Root DE".
     *
     * @see readingATreeOfFoldersWithOverlaysOffByCore()
     */
    #[Test]
    public function readingATreeOfFoldersWithOverlaysOffByExtbase(): void
    {
        // Given: two trees of folders, one of them with a German translation of the root
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RelationsOfTranslationsRead/Folders.csv');

        // When: the English tree of folder 1 and the German tree of folder 2 are read with overlays off
        $reads = [
            'en:1' => [1, new LanguageAspect(0, 0, LanguageAspect::OVERLAYS_OFF)],
            'de:2' => [2, new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_OFF)],
        ];
        $collectPaths = static function (Folder $folder, string $parentPath) use (&$collectPaths): array {
            $path = $parentPath === '' ? $folder->getTitle() : $parentPath . ' > ' . $folder->getTitle();
            $paths = [$path];
            foreach ($folder->getChildren() as $child) {
                array_push($paths, ...$collectPaths($child, $path));
            }
            return $paths;
        };
        $result = [];
        foreach ($reads as $key => [$folderUid, $languageAspect]) {
            $this->get(Context::class)->setAspect('language', $languageAspect);
            $this->get(PersistenceManager::class)->clearState();
            $folder = $this->get(FolderRepository::class)->findByUid($folderUid);
            $result[$key] = $folder === null ? [] : $collectPaths($folder, '');
        }

        // Then: every tree consists of the folders of its language and of all languages
        self::assertSame([
            'en:1' => [
                'Root EN',
                'Root EN > Child only EN',
                'Root EN > Child EN and DE',
                'Root EN > Child EN and DE > Grandchild EN',
                'Root EN > Child all languages',
            ],
            'de:2' => ['Root DE', 'Root DE > Child DE', 'Root DE > Child DE > Grandchild DE'],
        ], $result);
    }

    /**
     * Review 14 has the German translation, review 15, and both point to the posts 2, 5 and 1: review 15 through the
     * field `blog` to blog 5 and through the relation table to the posts 2, 5 and 1. Read with German and overlays off,
     * the review is the German review 15 itself. The core reads its fields like the record API (`RecordFactory`) does,
     * and with overlays off the related records are not overlaid: the field `blog` returns blog 5 as "Second blog", and
     * the field `related_posts` returns "First article" (2), "Second article" (5) and "Only in English" (1).
     *
     * @see readingTheRelationsOfATranslatedReviewWithOverlaysOffByExtbase()
     */
    #[Test]
    public function readingTheRelationsOfATranslatedReviewWithOverlaysOffByCore(): void
    {
        // Given: review 14 with its German translation (15), both pointing to the posts 2, 5 and 1
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RelationsOfTranslationsRead/Reviews.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RelationsOfTranslationsRead/TranslatedReviews.csv');

        // When: the German review 15 is read with overlays off and its relations are followed
        $pageInformation = new PageInformation();
        $pageInformation->setId(1);
        $pageInformation->setContentFromPid(1);
        $request = new ServerRequest()
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE)
            ->withAttribute('frontend.page.information', $pageInformation)
            ->withAttribute('frontend.cache.collector', new CacheDataCollector())
            ->withAttribute('frontend.page.parts', new PageParts());
        $this->get(Context::class)->setAspect('language', new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_OFF));
        $contentObjectRenderer = $this->get(ContentObjectRenderer::class);
        $contentObjectRenderer->setRequest($request);
        $reviewRow = $contentObjectRenderer->getRecords('tx_blogexample_domain_model_post', ['uidInList' => '15', 'pidInList' => '2'])[0] ?? null;
        $result = ['blog' => [], 'relatedPosts' => []];
        if ($reviewRow !== null) {
            $review = $this->get(RecordFactory::class)->createResolvedRecordFromDatabaseRow('tx_blogexample_domain_model_post', $reviewRow, $this->get(Context::class), new RecordIdentityMap());
            $blog = $review->get('blog');
            if ($blog !== null) {
                $result['blog'][] = [$blog->getUid(), $blog->get('title')];
            }
            foreach ($review->get('related_posts') as $relatedPost) {
                $result['relatedPosts'][] = [$relatedPost->getUid(), $relatedPost->get('title')];
            }
        }

        // Then: both relations return the related records in the default language
        self::assertSame([
            'blog' => [[5, 'Second blog']],
            'relatedPosts' => [[2, 'First article'], [5, 'Second article'], [1, 'Only in English']],
        ], $result);
    }

    /**
     * Extbase loads the relations of the German review 15 as with overlays mixed: the field `blog` returns "Zweiter
     * Blog" (5) and the relation table returns "Erster Artikel" (2), "Zweiter Artikel" (5) and, without translation,
     * "Only in English" (1), where the core returns the records without overlay.
     *
     * @see readingTheRelationsOfATranslatedReviewWithOverlaysOffByCore()
     */
    #[Test]
    public function readingTheRelationsOfATranslatedReviewWithOverlaysOffByExtbase(): void
    {
        self::markTestSkipped('Extbase differs from core: it loads the relations of a record read with overlays off as with overlays mixed, so it returns the German records, where the core returns the records in the default language, see Forge #110798.');

        // Given: review 14 with its German translation (15), both pointing to the posts 2, 5 and 1
        // @phpstan-ignore deadCode.unreachable
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RelationsOfTranslationsRead/Reviews.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RelationsOfTranslationsRead/TranslatedReviews.csv');

        // When: the German review 15 is read with overlays off and its relations are followed
        $this->get(Context::class)->setAspect('language', new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_OFF));
        $this->get(PersistenceManager::class)->clearState();
        $review = $this->get(PostRepository::class)->findByUid(15);
        $result = ['blog' => [], 'relatedPosts' => []];
        if ($review !== null) {
            if ($review->getBlog() !== null) {
                $result['blog'][] = [$review->getBlog()->getUid(), $review->getBlog()->getTitle()];
            }
            foreach ($review->getRelatedPosts() as $relatedPost) {
                $result['relatedPosts'][] = [$relatedPost->getUid(), $relatedPost->getTitle()];
            }
        }

        // Then: both relations return the related records in the default language
        self::assertSame([
            'blog' => [[5, 'Second blog']],
            'relatedPosts' => [[2, 'First article'], [5, 'Second article'], [1, 'Only in English']],
        ], $result);
    }

    /**
     * Read with French, the fallback chain German, English and overlays mixed, review 14 has no French translation and
     * is read as its German translation (15). The core reads its fields like the record API (`RecordFactory`) does and
     * follows the chain for the related records: the field `blog` returns "Zweiter Blog", the German translation of
     * blog 5, and the field `related_posts` returns, in its order, "Premier article" (post 2 in French), "Zweiter
     * Artikel" (post 5 in German through the chain) and "Only in English" (post 1, kept in the default language).
     *
     * @see readingTheRelationsOfAReviewWithAFallbackChainAndOverlaysMixedByExtbase()
     */
    #[Test]
    public function readingTheRelationsOfAReviewWithAFallbackChainAndOverlaysMixedByCore(): void
    {
        // Given: review 14 with its German translation (15), both pointing to the posts 2, 5 and 1
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RelationsOfTranslationsRead/Reviews.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RelationsOfTranslationsRead/TranslatedReviews.csv');

        // When: review 14 is read with French, the fallback chain German, English and overlays mixed and its relations
        // are followed
        $pageInformation = new PageInformation();
        $pageInformation->setId(1);
        $pageInformation->setContentFromPid(1);
        $request = new ServerRequest()
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE)
            ->withAttribute('frontend.page.information', $pageInformation)
            ->withAttribute('frontend.cache.collector', new CacheDataCollector())
            ->withAttribute('frontend.page.parts', new PageParts());
        $this->get(Context::class)->setAspect('language', new LanguageAspect(2, 2, LanguageAspect::OVERLAYS_MIXED, [1, 0]));
        $contentObjectRenderer = $this->get(ContentObjectRenderer::class);
        $contentObjectRenderer->setRequest($request);
        $reviewRow = $contentObjectRenderer->getRecords('tx_blogexample_domain_model_post', ['uidInList' => '14', 'pidInList' => '2'])[0] ?? null;
        $result = ['blog' => [], 'relatedPosts' => []];
        if ($reviewRow !== null) {
            $review = $this->get(RecordFactory::class)->createResolvedRecordFromDatabaseRow('tx_blogexample_domain_model_post', $reviewRow, $this->get(Context::class), new RecordIdentityMap());
            $blog = $review->get('blog');
            if ($blog !== null) {
                $result['blog'][] = [$blog->getUid(), $blog->get('title')];
            }
            foreach ($review->get('related_posts') as $relatedPost) {
                $result['relatedPosts'][] = [$relatedPost->getUid(), $relatedPost->get('title')];
            }
        }

        // Then: every related record is read in the first language of the chain that has a translation
        self::assertSame([
            'blog' => [[5, 'Zweiter Blog']],
            'relatedPosts' => [[2, 'Premier article'], [5, 'Zweiter Artikel'], [1, 'Only in English']],
        ], $result);
    }

    /**
     * Extbase returns "Zweiter Blog" for the field `blog` like the core, and "Zweiter Artikel" and "Only in English"
     * from the relation table, but "Erster Artikel", the German translation of post 2, where the core returns the
     * French "Premier article".
     *
     * @see readingTheRelationsOfAReviewWithAFallbackChainAndOverlaysMixedByCore()
     */
    #[Test]
    public function readingTheRelationsOfAReviewWithAFallbackChainAndOverlaysMixedByExtbase(): void
    {
        self::markTestSkipped('Extbase differs from core: the relation table returns the German "Erster Artikel" for post 2, where the core follows the fallback chain French, German, English and returns the French "Premier article", see Forge #111129.');

        // Given: review 14 with its German translation (15), both pointing to the posts 2, 5 and 1
        // @phpstan-ignore deadCode.unreachable
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RelationsOfTranslationsRead/Reviews.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RelationsOfTranslationsRead/TranslatedReviews.csv');

        // When: review 14 is read with French, the fallback chain German, English and overlays mixed and its relations
        // are followed
        $this->get(Context::class)->setAspect('language', new LanguageAspect(2, 2, LanguageAspect::OVERLAYS_MIXED, [1, 0]));
        $this->get(PersistenceManager::class)->clearState();
        $review = $this->get(PostRepository::class)->findByUid(14);
        $result = ['blog' => [], 'relatedPosts' => []];
        if ($review !== null) {
            if ($review->getBlog() !== null) {
                $result['blog'][] = [$review->getBlog()->getUid(), $review->getBlog()->getTitle()];
            }
            foreach ($review->getRelatedPosts() as $relatedPost) {
                $result['relatedPosts'][] = [$relatedPost->getUid(), $relatedPost->getTitle()];
            }
        }

        // Then: every related record is read in the first language of the chain that has a translation
        self::assertSame([
            'blog' => [[5, 'Zweiter Blog']],
            'relatedPosts' => [[2, 'Premier article'], [5, 'Zweiter Artikel'], [1, 'Only in English']],
        ], $result);
    }

    /**
     * The core side is the same as when review 14 is found by its uid: read with French, the fallback chain German,
     * English and overlays mixed, review 14 is read as its German translation (15) and the field `related_posts`
     * returns "Premier article", "Zweiter Artikel" and "Only in English".
     *
     * @see readingTheRelationsOfAReviewFoundByAQueryWithAFallbackChainAndOverlaysMixedByExtbase()
     */
    #[Test]
    public function readingTheRelationsOfAReviewFoundByAQueryWithAFallbackChainAndOverlaysMixedByCore(): void
    {
        // Given: review 14 with its German translation (15), both pointing to the posts 2, 5 and 1
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RelationsOfTranslationsRead/Reviews.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RelationsOfTranslationsRead/TranslatedReviews.csv');

        // When: review 14 is found by a query with French, the fallback chain German, English and overlays mixed and
        // its relations are followed
        $pageInformation = new PageInformation();
        $pageInformation->setId(1);
        $pageInformation->setContentFromPid(1);
        $request = new ServerRequest()
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE)
            ->withAttribute('frontend.page.information', $pageInformation)
            ->withAttribute('frontend.cache.collector', new CacheDataCollector())
            ->withAttribute('frontend.page.parts', new PageParts());
        $this->get(Context::class)->setAspect('language', new LanguageAspect(2, 2, LanguageAspect::OVERLAYS_MIXED, [1, 0]));
        $contentObjectRenderer = $this->get(ContentObjectRenderer::class);
        $contentObjectRenderer->setRequest($request);
        $reviewRow = $contentObjectRenderer->getRecords('tx_blogexample_domain_model_post', ['uidInList' => '14', 'pidInList' => '2'])[0] ?? null;
        $result = ['blog' => [], 'relatedPosts' => []];
        if ($reviewRow !== null) {
            $review = $this->get(RecordFactory::class)->createResolvedRecordFromDatabaseRow('tx_blogexample_domain_model_post', $reviewRow, $this->get(Context::class), new RecordIdentityMap());
            $blog = $review->get('blog');
            if ($blog !== null) {
                $result['blog'][] = [$blog->getUid(), $blog->get('title')];
            }
            foreach ($review->get('related_posts') as $relatedPost) {
                $result['relatedPosts'][] = [$relatedPost->getUid(), $relatedPost->get('title')];
            }
        }

        // Then: every related record is read in the first language of the chain that has a translation
        self::assertSame([
            'blog' => [[5, 'Zweiter Blog']],
            'relatedPosts' => [[2, 'Premier article'], [5, 'Zweiter Artikel'], [1, 'Only in English']],
        ], $result);
    }

    /**
     * Review 14 is found by a repository query (`equals('uid', 14)`, `respectSysLanguage` left at its default `true`)
     * on the storage page 2 instead of `findByUid()`, and read with French, the fallback chain German, English and
     * overlays mixed. Extbase returns the same as the core: "Zweiter Blog" for the field `blog`, and "Premier article"
     * (post 2 in French), "Zweiter Artikel" and "Only in English" from the relation table. Unlike `findByUid()`, which
     * returns the German "Erster Artikel" for post 2, the query reads the relation in the requested language, French.
     *
     * @see readingTheRelationsOfAReviewFoundByAQueryWithAFallbackChainAndOverlaysMixedByCore()
     */
    #[Test]
    public function readingTheRelationsOfAReviewFoundByAQueryWithAFallbackChainAndOverlaysMixedByExtbase(): void
    {
        // Given: review 14 with its German translation (15), both pointing to the posts 2, 5 and 1
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RelationsOfTranslationsRead/Reviews.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RelationsOfTranslationsRead/TranslatedReviews.csv');

        // When: review 14 is found by a query with French, the fallback chain German, English and overlays mixed and
        // its relations are followed
        $this->get(Context::class)->setAspect('language', new LanguageAspect(2, 2, LanguageAspect::OVERLAYS_MIXED, [1, 0]));
        $this->get(PersistenceManager::class)->clearState();
        $query = $this->get(PostRepository::class)->createQuery();
        $query->getQuerySettings()->setStoragePageIds([2]);
        $query->matching($query->equals('uid', 14));
        $review = $query->execute()->getFirst();
        $result = ['blog' => [], 'relatedPosts' => []];
        if ($review !== null) {
            if ($review->getBlog() !== null) {
                $result['blog'][] = [$review->getBlog()->getUid(), $review->getBlog()->getTitle()];
            }
            foreach ($review->getRelatedPosts() as $relatedPost) {
                $result['relatedPosts'][] = [$relatedPost->getUid(), $relatedPost->getTitle()];
            }
        }

        // Then: every related record is read in the first language of the chain that has a translation
        self::assertSame([
            'blog' => [[5, 'Zweiter Blog']],
            'relatedPosts' => [[2, 'Premier article'], [5, 'Zweiter Artikel'], [1, 'Only in English']],
        ], $result);
    }

    /**
     * Read with French, the fallback chain German, English and overlays on, review 14 is read as its German translation
     * (15). The core reads its fields like the record API (`RecordFactory`) does and returns "Zweiter Blog" for the
     * field `blog`, and from the field `related_posts` "Premier article" (post 2 in French) and "Zweiter Artikel" (post
     * 5 in German through the chain). Post 1 has no translation in the chain and is dropped.
     *
     * @see readingTheRelationsOfAReviewWithAFallbackChainAndOverlaysOnByExtbase()
     */
    #[Test]
    public function readingTheRelationsOfAReviewWithAFallbackChainAndOverlaysOnByCore(): void
    {
        // Given: review 14 with its German translation (15), both pointing to the posts 2, 5 and 1
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RelationsOfTranslationsRead/Reviews.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RelationsOfTranslationsRead/TranslatedReviews.csv');

        // When: review 14 is read with French, the fallback chain German, English and overlays on and its relations are
        // followed
        $pageInformation = new PageInformation();
        $pageInformation->setId(1);
        $pageInformation->setContentFromPid(1);
        $request = new ServerRequest()
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE)
            ->withAttribute('frontend.page.information', $pageInformation)
            ->withAttribute('frontend.cache.collector', new CacheDataCollector())
            ->withAttribute('frontend.page.parts', new PageParts());
        $this->get(Context::class)->setAspect('language', new LanguageAspect(2, 2, LanguageAspect::OVERLAYS_ON, [1, 0]));
        $contentObjectRenderer = $this->get(ContentObjectRenderer::class);
        $contentObjectRenderer->setRequest($request);
        $reviewRow = $contentObjectRenderer->getRecords('tx_blogexample_domain_model_post', ['uidInList' => '14', 'pidInList' => '2'])[0] ?? null;
        $result = ['blog' => [], 'relatedPosts' => []];
        if ($reviewRow !== null) {
            $review = $this->get(RecordFactory::class)->createResolvedRecordFromDatabaseRow('tx_blogexample_domain_model_post', $reviewRow, $this->get(Context::class), new RecordIdentityMap());
            $blog = $review->get('blog');
            if ($blog !== null) {
                $result['blog'][] = [$blog->getUid(), $blog->get('title')];
            }
            foreach ($review->get('related_posts') as $relatedPost) {
                $result['relatedPosts'][] = [$relatedPost->getUid(), $relatedPost->get('title')];
            }
        }

        // Then: every related record that has a translation in the chain is read in the first language of the chain
        // that has one
        self::assertSame([
            'blog' => [[5, 'Zweiter Blog']],
            'relatedPosts' => [[2, 'Premier article'], [5, 'Zweiter Artikel']],
        ], $result);
    }

    /**
     * Extbase returns "Zweiter Blog" for the field `blog` and drops post 1 like the core, but returns "Erster Artikel",
     * the German translation of post 2, from the relation table, where the core returns the French "Premier article".
     *
     * @see readingTheRelationsOfAReviewWithAFallbackChainAndOverlaysOnByCore()
     */
    #[Test]
    public function readingTheRelationsOfAReviewWithAFallbackChainAndOverlaysOnByExtbase(): void
    {
        self::markTestSkipped('Extbase differs from core: the relation table returns the German "Erster Artikel" for post 2, where the core follows the fallback chain French, German, English and returns the French "Premier article", see Forge #111129.');

        // Given: review 14 with its German translation (15), both pointing to the posts 2, 5 and 1
        // @phpstan-ignore deadCode.unreachable
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RelationsOfTranslationsRead/Reviews.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RelationsOfTranslationsRead/TranslatedReviews.csv');

        // When: review 14 is read with French, the fallback chain German, English and overlays on and its relations are
        // followed
        $this->get(Context::class)->setAspect('language', new LanguageAspect(2, 2, LanguageAspect::OVERLAYS_ON, [1, 0]));
        $this->get(PersistenceManager::class)->clearState();
        $review = $this->get(PostRepository::class)->findByUid(14);
        $result = ['blog' => [], 'relatedPosts' => []];
        if ($review !== null) {
            if ($review->getBlog() !== null) {
                $result['blog'][] = [$review->getBlog()->getUid(), $review->getBlog()->getTitle()];
            }
            foreach ($review->getRelatedPosts() as $relatedPost) {
                $result['relatedPosts'][] = [$relatedPost->getUid(), $relatedPost->getTitle()];
            }
        }

        // Then: every related record that has a translation in the chain is read in the first language of the chain
        // that has one
        self::assertSame([
            'blog' => [[5, 'Zweiter Blog']],
            'relatedPosts' => [[2, 'Premier article'], [5, 'Zweiter Artikel']],
        ], $result);
    }

    /**
     * Review 16 is a record for all languages and points to blog 2 through the field `blog` and to the posts 2 and 5
     * through the relation table. Read with German and overlays on, `CONTENT` returns the review as it is stored,
     * without overlay, and the record API (`RecordFactory`) reads its relations in the language of the context: the
     * field `blog` returns "Erster Blog", the German translation of blog 2, and the field `related_posts` returns
     * "Erster Artikel" and "Zweiter Artikel".
     *
     * @see readingTheRelationsOfAReviewForAllLanguagesByExtbase()
     */
    #[Test]
    public function readingTheRelationsOfAReviewForAllLanguagesByCore(): void
    {
        // Given: review 16 for all languages, pointing to the posts 2 and 5
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RelationsOfTranslationsRead/Reviews.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RelationsOfTranslationsRead/TranslatedReviews.csv');

        // When: review 16 is read with German and overlays on and its relations are followed
        $pageInformation = new PageInformation();
        $pageInformation->setId(1);
        $pageInformation->setContentFromPid(1);
        $request = new ServerRequest()
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE)
            ->withAttribute('frontend.page.information', $pageInformation)
            ->withAttribute('frontend.cache.collector', new CacheDataCollector())
            ->withAttribute('frontend.page.parts', new PageParts());
        $this->get(Context::class)->setAspect('language', new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_ON));
        $contentObjectRenderer = $this->get(ContentObjectRenderer::class);
        $contentObjectRenderer->setRequest($request);
        $reviewRow = $contentObjectRenderer->getRecords('tx_blogexample_domain_model_post', ['uidInList' => '16', 'pidInList' => '2'])[0] ?? null;
        $result = ['blog' => [], 'relatedPosts' => []];
        if ($reviewRow !== null) {
            $review = $this->get(RecordFactory::class)->createResolvedRecordFromDatabaseRow('tx_blogexample_domain_model_post', $reviewRow, $this->get(Context::class), new RecordIdentityMap());
            $blog = $review->get('blog');
            if ($blog !== null) {
                $result['blog'][] = [$blog->getUid(), $blog->get('title')];
            }
            foreach ($review->get('related_posts') as $relatedPost) {
                $result['relatedPosts'][] = [$relatedPost->getUid(), $relatedPost->get('title')];
            }
        }

        // Then: the relations are read in German
        self::assertSame([
            'blog' => [[2, 'Erster Blog']],
            'relatedPosts' => [[2, 'Erster Artikel'], [5, 'Zweiter Artikel']],
        ], $result);
    }

    /**
     * Read with German and overlays on, Extbase returns the review for all languages (16) without overlay and its
     * relations in German like the core: "Erster Blog" for the field `blog`, "Erster Artikel" and "Zweiter Artikel"
     * from the relation table.
     *
     * @see readingTheRelationsOfAReviewForAllLanguagesByCore()
     */
    #[Test]
    public function readingTheRelationsOfAReviewForAllLanguagesByExtbase(): void
    {
        // Given: review 16 for all languages, pointing to the posts 2 and 5
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RelationsOfTranslationsRead/Reviews.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RelationsOfTranslationsRead/TranslatedReviews.csv');

        // When: review 16 is read with German and overlays on and its relations are followed
        $this->get(Context::class)->setAspect('language', new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_ON));
        $this->get(PersistenceManager::class)->clearState();
        $review = $this->get(PostRepository::class)->findByUid(16);
        $result = ['blog' => [], 'relatedPosts' => []];
        if ($review !== null) {
            if ($review->getBlog() !== null) {
                $result['blog'][] = [$review->getBlog()->getUid(), $review->getBlog()->getTitle()];
            }
            foreach ($review->getRelatedPosts() as $relatedPost) {
                $result['relatedPosts'][] = [$relatedPost->getUid(), $relatedPost->getTitle()];
            }
        }

        // Then: the relations are read in German
        self::assertSame([
            'blog' => [[2, 'Erster Blog']],
            'relatedPosts' => [[2, 'Erster Artikel'], [5, 'Zweiter Artikel']],
        ], $result);
    }

    /**
     * Blog 6 has the German translation, blog 7. The post "Visible post" (30) has the German translation "Sichtbarer
     * Beitrag" (31). The post "Hidden post" (32) is hidden, but its German translation "Versteckter Beitrag" (33) is
     * visible. "Beitrag im freien Modus" (34) is a German post without default record. Read with German and overlays
     * on, the core selects blog 6 with `CONTENT`, which overlays it with the German blog 7, and reads the field `posts`
     * like the record API (`RecordFactory`) does: the `RelationHandler` finds the posts that point to blog 7 and
     * returns "Sichtbarer Beitrag", "Versteckter Beitrag" and "Beitrag im freien Modus". The record API does not look
     * at the default record of a translation, so it returns the translation of the hidden post although its default
     * record is hidden.
     *
     * @see readingTheTranslationOfAHiddenDefaultChildWithOverlaysOnByExtbase()
     */
    #[Test]
    public function readingTheTranslationOfAHiddenDefaultChildWithOverlaysOnByCore(): void
    {
        // Given: blog 6 with its German translation (7), a visible post (30) and a hidden post (32), each with a German
        // translation, and a free mode post of the German blog (34)
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RelationsOfTranslationsRead/TranslatedChildOfHiddenDefault.csv');

        // When: the posts of the blog are read with German and overlays on
        $pageInformation = new PageInformation();
        $pageInformation->setId(1);
        $pageInformation->setContentFromPid(1);
        $request = new ServerRequest()
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE)
            ->withAttribute('frontend.page.information', $pageInformation)
            ->withAttribute('frontend.cache.collector', new CacheDataCollector())
            ->withAttribute('frontend.page.parts', new PageParts());
        $reads = [
            'de' => [6, new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_ON)],
        ];
        $result = [];
        foreach ($reads as $key => [$blogUid, $languageAspect]) {
            $this->get(Context::class)->setAspect('language', $languageAspect);
            $contentObjectRenderer = $this->get(ContentObjectRenderer::class);
            $contentObjectRenderer->setRequest($request);
            $blogRow = $contentObjectRenderer->getRecords('tx_blogexample_domain_model_blog', ['uidInList' => (string)$blogUid, 'pidInList' => '2'])[0] ?? null;
            $titles = [];
            if ($blogRow !== null) {
                $blog = $this->get(RecordFactory::class)->createResolvedRecordFromDatabaseRow('tx_blogexample_domain_model_blog', $blogRow, $this->get(Context::class), new RecordIdentityMap());
                foreach ($blog->get('posts') as $post) {
                    $titles[] = $post->get('title');
                }
            }
            $result[$key] = $titles;
        }

        // Then: the translations of both posts are returned, and the free mode post
        self::assertSame([
            'de' => ['Sichtbarer Beitrag', 'Versteckter Beitrag', 'Beitrag im freien Modus'],
        ], $result);
    }

    /**
     * Read with German and overlays on, Extbase returns the same posts as the core: "Sichtbarer Beitrag", "Versteckter
     * Beitrag", the translation of the hidden default post, and "Beitrag im freien Modus".
     *
     * @see readingTheTranslationOfAHiddenDefaultChildWithOverlaysOnByCore()
     */
    #[Test]
    public function readingTheTranslationOfAHiddenDefaultChildWithOverlaysOnByExtbase(): void
    {
        // Given: blog 6 with its German translation (7), a visible post (30) and a hidden post (32), each with a German
        // translation, and a free mode post of the German blog (34)
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RelationsOfTranslationsRead/TranslatedChildOfHiddenDefault.csv');

        // When: the posts of the blog are read with German and overlays on
        $reads = [
            'de' => [6, new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_ON)],
        ];
        $result = [];
        foreach ($reads as $key => [$blogUid, $languageAspect]) {
            $this->get(Context::class)->setAspect('language', $languageAspect);
            $this->get(PersistenceManager::class)->clearState();
            $blog = $this->get(BlogRepository::class)->findByUid($blogUid);
            $result[$key] = $blog === null ? [] : array_map(static fn(Post $post): string => $post->getTitle(), $blog->getPosts()->toArray());
        }

        // Then: the translations of both posts are returned, and the free mode post
        self::assertSame([
            'de' => ['Sichtbarer Beitrag', 'Versteckter Beitrag', 'Beitrag im freien Modus'],
        ], $result);
    }

    /**
     * Blog 6 has the German translation, blog 7. The post "Visible post" (30) has the German translation "Sichtbarer
     * Beitrag" (31). The post "Hidden post" (32) is hidden, but its German translation "Versteckter Beitrag" (33) is
     * visible. "Beitrag im freien Modus" (34) is a German post without default record. Read with German and overlays
     * mixed, the core selects blog 6 with `CONTENT`, which overlays it with the German blog 7, and reads the field
     * `posts` like the record API (`RecordFactory`) does: the `RelationHandler` finds the posts that point to blog 7
     * and returns "Sichtbarer Beitrag", "Versteckter Beitrag" and "Beitrag im freien Modus". The record API does not
     * look at the default record of a translation, so it returns the translation of the hidden post although its
     * default record is hidden.
     *
     * @see readingTheTranslationOfAHiddenDefaultChildWithOverlaysMixedByExtbase()
     */
    #[Test]
    public function readingTheTranslationOfAHiddenDefaultChildWithOverlaysMixedByCore(): void
    {
        // Given: blog 6 with its German translation (7), a visible post (30) and a hidden post (32), each with a German
        // translation, and a free mode post of the German blog (34)
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RelationsOfTranslationsRead/TranslatedChildOfHiddenDefault.csv');

        // When: the posts of the blog are read with German and overlays mixed
        $pageInformation = new PageInformation();
        $pageInformation->setId(1);
        $pageInformation->setContentFromPid(1);
        $request = new ServerRequest()
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE)
            ->withAttribute('frontend.page.information', $pageInformation)
            ->withAttribute('frontend.cache.collector', new CacheDataCollector())
            ->withAttribute('frontend.page.parts', new PageParts());
        $reads = [
            'de' => [6, new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_MIXED)],
        ];
        $result = [];
        foreach ($reads as $key => [$blogUid, $languageAspect]) {
            $this->get(Context::class)->setAspect('language', $languageAspect);
            $contentObjectRenderer = $this->get(ContentObjectRenderer::class);
            $contentObjectRenderer->setRequest($request);
            $blogRow = $contentObjectRenderer->getRecords('tx_blogexample_domain_model_blog', ['uidInList' => (string)$blogUid, 'pidInList' => '2'])[0] ?? null;
            $titles = [];
            if ($blogRow !== null) {
                $blog = $this->get(RecordFactory::class)->createResolvedRecordFromDatabaseRow('tx_blogexample_domain_model_blog', $blogRow, $this->get(Context::class), new RecordIdentityMap());
                foreach ($blog->get('posts') as $post) {
                    $titles[] = $post->get('title');
                }
            }
            $result[$key] = $titles;
        }

        // Then: the translations of both posts are returned, and the free mode post
        self::assertSame([
            'de' => ['Sichtbarer Beitrag', 'Versteckter Beitrag', 'Beitrag im freien Modus'],
        ], $result);
    }

    /**
     * Read with German and overlays mixed, Extbase returns the same posts as the core: "Sichtbarer Beitrag",
     * "Versteckter Beitrag", the translation of the hidden default post, and "Beitrag im freien Modus".
     *
     * @see readingTheTranslationOfAHiddenDefaultChildWithOverlaysMixedByCore()
     */
    #[Test]
    public function readingTheTranslationOfAHiddenDefaultChildWithOverlaysMixedByExtbase(): void
    {
        // Given: blog 6 with its German translation (7), a visible post (30) and a hidden post (32), each with a German
        // translation, and a free mode post of the German blog (34)
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RelationsOfTranslationsRead/TranslatedChildOfHiddenDefault.csv');

        // When: the posts of the blog are read with German and overlays mixed
        $reads = [
            'de' => [6, new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_MIXED)],
        ];
        $result = [];
        foreach ($reads as $key => [$blogUid, $languageAspect]) {
            $this->get(Context::class)->setAspect('language', $languageAspect);
            $this->get(PersistenceManager::class)->clearState();
            $blog = $this->get(BlogRepository::class)->findByUid($blogUid);
            $result[$key] = $blog === null ? [] : array_map(static fn(Post $post): string => $post->getTitle(), $blog->getPosts()->toArray());
        }

        // Then: the translations of both posts are returned, and the free mode post
        self::assertSame([
            'de' => ['Sichtbarer Beitrag', 'Versteckter Beitrag', 'Beitrag im freien Modus'],
        ], $result);
    }

    /**
     * The review rows store the uid of the default blog, 2, in the field `blog`, also the German review 12, as
     * DataHandler writes it when it translates a record. Read in German with overlays on, the core selects the default
     * posts whose field `blog` is 2 with `CONTENT` and overlays them: it returns review 11 as "Erste Rezension". Review
     * 13, which points to blog 5, is not returned.
     *
     * @see equalsOnARelationToATranslatedBlogByExtbase()
     */
    #[Test]
    public function equalsOnARelationToATranslatedBlogByCore(): void
    {
        // Given: blog 2 and post 2 with their German translations and reviews that point to them and to the blog 5
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RelationsOfTranslationsRead/Reviews.csv');

        // When: the reviews of blog 2 are selected by the stored value of the field blog and read in German with
        // overlays on
        $pageInformation = new PageInformation();
        $pageInformation->setId(1);
        $pageInformation->setContentFromPid(1);
        $request = new ServerRequest()
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE)
            ->withAttribute('frontend.page.information', $pageInformation)
            ->withAttribute('frontend.cache.collector', new CacheDataCollector())
            ->withAttribute('frontend.page.parts', new PageParts());
        $this->get(Context::class)->setAspect('language', new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_ON));
        $contentObjectRenderer = $this->get(ContentObjectRenderer::class);
        $contentObjectRenderer->setRequest($request);
        $reviews = [];
        foreach ($contentObjectRenderer->getRecords('tx_blogexample_domain_model_post', ['pidInList' => '2', 'where' => 'blog=2', 'orderBy' => 'uid']) as $row) {
            $reviews[] = $row['title'];
        }

        // Then: the German review of blog 2 is returned
        self::assertSame(['Erste Rezension'], $reviews);
    }

    /**
     * Read in German with overlays on, Extbase loads blog 2 as its translation "Erster Blog" and matches the field
     * `blog` with `equals()` against that entity. It binds the uid of the translation, 3, while the review stores the
     * uid of the default blog, 2, so the result is empty and "Erste Rezension" is not found. Matching the plain uid 2
     * would find it.
     *
     * @see equalsOnARelationToATranslatedBlogByCore()
     */
    #[Test]
    public function equalsOnARelationToATranslatedBlogByExtbase(): void
    {
        self::markTestSkipped('Extbase differs from core: it binds the uid of the translation (3) of the entity, while the relation holds the uid of the default record (2), so no review is found, see Forge #91487.');

        // Given: blog 2 and post 2 with their German translations and reviews that point to them and to the blog 5
        // @phpstan-ignore deadCode.unreachable
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RelationsOfTranslationsRead/Reviews.csv');

        // When: the reviews of blog 2 are selected by the stored value of the field blog and read in German with
        // overlays on
        $this->get(Context::class)->setAspect('language', new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_ON));
        $this->get(PersistenceManager::class)->clearState();
        $constraintObject = $this->get(BlogRepository::class)->findByUid(2);
        $query = $this->get(PostRepository::class)->createQuery();
        $query->getQuerySettings()->setStoragePageIds([2]);
        $query->setOrderings(['uid' => QueryInterface::ORDER_ASCENDING]);
        $query->matching($query->equals('blog', $constraintObject));
        $reviews = array_map(static fn(Post $review): string => $review->getTitle(), $query->execute()->toArray());

        // Then: the German review of blog 2 is returned
        self::assertSame(['Erste Rezension'], $reviews);
    }

    /**
     * The review rows store the uid of the default blog, 2, in the field `blog`. Read in German with overlays on, the
     * core selects the default posts whose field `blog` is 2 with `CONTENT` and overlays them: it returns review 11 as
     * "Erste Rezension", the review of blog 5 is not returned.
     *
     * @see inOnARelationToATranslatedBlogByExtbase()
     */
    #[Test]
    public function inOnARelationToATranslatedBlogByCore(): void
    {
        // Given: blog 2 and post 2 with their German translations and reviews that point to them and to the blog 5
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RelationsOfTranslationsRead/Reviews.csv');

        // When: the reviews of blog 2 are selected by the stored value of the field blog and read in German with
        // overlays on
        $pageInformation = new PageInformation();
        $pageInformation->setId(1);
        $pageInformation->setContentFromPid(1);
        $request = new ServerRequest()
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE)
            ->withAttribute('frontend.page.information', $pageInformation)
            ->withAttribute('frontend.cache.collector', new CacheDataCollector())
            ->withAttribute('frontend.page.parts', new PageParts());
        $this->get(Context::class)->setAspect('language', new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_ON));
        $contentObjectRenderer = $this->get(ContentObjectRenderer::class);
        $contentObjectRenderer->setRequest($request);
        $reviews = [];
        foreach ($contentObjectRenderer->getRecords('tx_blogexample_domain_model_post', ['pidInList' => '2', 'where' => 'blog=2', 'orderBy' => 'uid']) as $row) {
            $reviews[] = $row['title'];
        }

        // Then: the German review of blog 2 is returned
        self::assertSame(['Erste Rezension'], $reviews);
    }

    /**
     * Read in German with overlays on, Extbase loads blog 2 as its translation "Erster Blog" and matches the field
     * `blog` with `in()` against a list holding that entity. It binds the uid of the translation, 3, like `equals()`,
     * while the review stores the uid 2, so the result is empty and "Erste Rezension" is not found.
     *
     * @see inOnARelationToATranslatedBlogByCore()
     */
    #[Test]
    public function inOnARelationToATranslatedBlogByExtbase(): void
    {
        self::markTestSkipped('Extbase differs from core: it binds the uid of the translation (3) of the entity, while the relation holds the uid of the default record (2), so no review is found, see Forge #91487.');

        // Given: blog 2 and post 2 with their German translations and reviews that point to them and to the blog 5
        // @phpstan-ignore deadCode.unreachable
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RelationsOfTranslationsRead/Reviews.csv');

        // When: the reviews of blog 2 are selected by the stored value of the field blog and read in German with
        // overlays on
        $this->get(Context::class)->setAspect('language', new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_ON));
        $this->get(PersistenceManager::class)->clearState();
        $constraintObject = $this->get(BlogRepository::class)->findByUid(2);
        $query = $this->get(PostRepository::class)->createQuery();
        $query->getQuerySettings()->setStoragePageIds([2]);
        $query->setOrderings(['uid' => QueryInterface::ORDER_ASCENDING]);
        $query->matching($query->in('blog', [$constraintObject]));
        $reviews = array_map(static fn(Post $review): string => $review->getTitle(), $query->execute()->toArray());

        // Then: the German review of blog 2 is returned
        self::assertSame(['Erste Rezension'], $reviews);
    }

    /**
     * The relation table holds the uid of the default post, 2, for review 11 and for the German review 12. Read in
     * German with overlays on, the core selects the default posts that have a relation row to post 2 with `CONTENT` and
     * overlays them: it returns review 11 as "Erste Rezension", the review of post 5 is not returned.
     *
     * @see equalsOnTheUidOfAnMmRelationToATranslatedPostByExtbase()
     */
    #[Test]
    public function equalsOnTheUidOfAnMmRelationToATranslatedPostByCore(): void
    {
        // Given: blog 2 and post 2 with their German translations and reviews that point to them and to the blog 5
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RelationsOfTranslationsRead/Reviews.csv');

        // When: the reviews that have a relation row to post 2 are selected and read in German with overlays on
        $pageInformation = new PageInformation();
        $pageInformation->setId(1);
        $pageInformation->setContentFromPid(1);
        $request = new ServerRequest()
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE)
            ->withAttribute('frontend.page.information', $pageInformation)
            ->withAttribute('frontend.cache.collector', new CacheDataCollector())
            ->withAttribute('frontend.page.parts', new PageParts());
        $this->get(Context::class)->setAspect('language', new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_ON));
        $contentObjectRenderer = $this->get(ContentObjectRenderer::class);
        $contentObjectRenderer->setRequest($request);
        $reviews = [];
        foreach ($contentObjectRenderer->getRecords('tx_blogexample_domain_model_post', ['pidInList' => '2', 'selectFields' => 'tx_blogexample_domain_model_post.*', 'join' => 'tx_blogexample_post_post_mm ON tx_blogexample_post_post_mm.uid_local = tx_blogexample_domain_model_post.uid', 'where' => 'tx_blogexample_post_post_mm.uid_foreign=2', 'orderBy' => 'tx_blogexample_domain_model_post.uid']) as $row) {
            $reviews[] = $row['title'];
        }

        // Then: the German review of post 2 is returned
        self::assertSame(['Erste Rezension'], $reviews);
    }

    /**
     * Read in German with overlays on, Extbase loads post 2 as its translation "Erster Artikel" and matches the uid of
     * the relation `related_posts` with `equals()` on `"relatedPosts.uid"` against that entity. It binds the uid of the
     * translation, 3, while the relation table holds 2, so the result is empty and "Erste Rezension" is not found.
     *
     * @see equalsOnTheUidOfAnMmRelationToATranslatedPostByCore()
     */
    #[Test]
    public function equalsOnTheUidOfAnMmRelationToATranslatedPostByExtbase(): void
    {
        self::markTestSkipped('Extbase differs from core: it binds the uid of the translation (3) of the entity, while the relation table holds the uid of the default record (2), so no review is found, see Forge #91487.');

        // Given: blog 2 and post 2 with their German translations and reviews that point to them and to the blog 5
        // @phpstan-ignore deadCode.unreachable
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RelationsOfTranslationsRead/Reviews.csv');

        // When: the reviews that have a relation row to post 2 are selected and read in German with overlays on
        $this->get(Context::class)->setAspect('language', new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_ON));
        $this->get(PersistenceManager::class)->clearState();
        $constraintObject = $this->get(PostRepository::class)->findByUid(2);
        $query = $this->get(PostRepository::class)->createQuery();
        $query->getQuerySettings()->setStoragePageIds([2]);
        $query->setOrderings(['uid' => QueryInterface::ORDER_ASCENDING]);
        $query->matching($query->equals('relatedPosts.uid', $constraintObject));
        $reviews = array_map(static fn(Post $review): string => $review->getTitle(), $query->execute()->toArray());

        // Then: the German review of post 2 is returned
        self::assertSame(['Erste Rezension'], $reviews);
    }

    /**
     * The relation table holds the uid of the default post, 2, for review 11 and for the German review 12. Read in
     * German with overlays on, the core selects the default posts that have a relation row to post 2 with `CONTENT` and
     * overlays them: it returns review 11 as "Erste Rezension", the review of post 5 is not returned.
     *
     * @see inOnTheUidOfAnMmRelationToATranslatedPostByExtbase()
     */
    #[Test]
    public function inOnTheUidOfAnMmRelationToATranslatedPostByCore(): void
    {
        // Given: blog 2 and post 2 with their German translations and reviews that point to them and to the blog 5
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RelationsOfTranslationsRead/Reviews.csv');

        // When: the reviews that have a relation row to post 2 are selected and read in German with overlays on
        $pageInformation = new PageInformation();
        $pageInformation->setId(1);
        $pageInformation->setContentFromPid(1);
        $request = new ServerRequest()
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE)
            ->withAttribute('frontend.page.information', $pageInformation)
            ->withAttribute('frontend.cache.collector', new CacheDataCollector())
            ->withAttribute('frontend.page.parts', new PageParts());
        $this->get(Context::class)->setAspect('language', new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_ON));
        $contentObjectRenderer = $this->get(ContentObjectRenderer::class);
        $contentObjectRenderer->setRequest($request);
        $reviews = [];
        foreach ($contentObjectRenderer->getRecords('tx_blogexample_domain_model_post', ['pidInList' => '2', 'selectFields' => 'tx_blogexample_domain_model_post.*', 'join' => 'tx_blogexample_post_post_mm ON tx_blogexample_post_post_mm.uid_local = tx_blogexample_domain_model_post.uid', 'where' => 'tx_blogexample_post_post_mm.uid_foreign=2', 'orderBy' => 'tx_blogexample_domain_model_post.uid']) as $row) {
            $reviews[] = $row['title'];
        }

        // Then: the German review of post 2 is returned
        self::assertSame(['Erste Rezension'], $reviews);
    }

    /**
     * Read in German with overlays on, Extbase loads post 2 as its translation "Erster Artikel" and matches
     * `"relatedPosts.uid"` with `in()` against a list holding that entity. It binds the uid of the translation, 3,
     * while the relation table holds 2, so the result is empty and "Erste Rezension" is not found.
     *
     * @see inOnTheUidOfAnMmRelationToATranslatedPostByCore()
     */
    #[Test]
    public function inOnTheUidOfAnMmRelationToATranslatedPostByExtbase(): void
    {
        self::markTestSkipped('Extbase differs from core: it binds the uid of the translation (3) of the entity, while the relation table holds the uid of the default record (2), so no review is found, see Forge #91487.');

        // Given: blog 2 and post 2 with their German translations and reviews that point to them and to the blog 5
        // @phpstan-ignore deadCode.unreachable
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RelationsOfTranslationsRead/Reviews.csv');

        // When: the reviews that have a relation row to post 2 are selected and read in German with overlays on
        $this->get(Context::class)->setAspect('language', new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_ON));
        $this->get(PersistenceManager::class)->clearState();
        $constraintObject = $this->get(PostRepository::class)->findByUid(2);
        $query = $this->get(PostRepository::class)->createQuery();
        $query->getQuerySettings()->setStoragePageIds([2]);
        $query->setOrderings(['uid' => QueryInterface::ORDER_ASCENDING]);
        $query->matching($query->in('relatedPosts.uid', [$constraintObject]));
        $reviews = array_map(static fn(Post $review): string => $review->getTitle(), $query->execute()->toArray());

        // Then: the German review of post 2 is returned
        self::assertSame(['Erste Rezension'], $reviews);
    }

    /**
     * The relation table holds the uid of the default post, 2, for review 11 and for the German review 12. Read in
     * German with overlays on, the core selects the default posts that have a relation row to post 2 with `CONTENT` and
     * overlays them: it returns review 11 as "Erste Rezension", the review of post 5 is not returned.
     *
     * @see containingATranslatedPostInAnMmRelationByExtbase()
     */
    #[Test]
    public function containingATranslatedPostInAnMmRelationByCore(): void
    {
        // Given: blog 2 and post 2 with their German translations and reviews that point to them and to the blog 5
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RelationsOfTranslationsRead/Reviews.csv');

        // When: the reviews that have a relation row to post 2 are selected and read in German with overlays on
        $pageInformation = new PageInformation();
        $pageInformation->setId(1);
        $pageInformation->setContentFromPid(1);
        $request = new ServerRequest()
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE)
            ->withAttribute('frontend.page.information', $pageInformation)
            ->withAttribute('frontend.cache.collector', new CacheDataCollector())
            ->withAttribute('frontend.page.parts', new PageParts());
        $this->get(Context::class)->setAspect('language', new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_ON));
        $contentObjectRenderer = $this->get(ContentObjectRenderer::class);
        $contentObjectRenderer->setRequest($request);
        $reviews = [];
        foreach ($contentObjectRenderer->getRecords('tx_blogexample_domain_model_post', ['pidInList' => '2', 'selectFields' => 'tx_blogexample_domain_model_post.*', 'join' => 'tx_blogexample_post_post_mm ON tx_blogexample_post_post_mm.uid_local = tx_blogexample_domain_model_post.uid', 'where' => 'tx_blogexample_post_post_mm.uid_foreign=2', 'orderBy' => 'tx_blogexample_domain_model_post.uid']) as $row) {
            $reviews[] = $row['title'];
        }

        // Then: the German review of post 2 is returned
        self::assertSame(['Erste Rezension'], $reviews);
    }

    /**
     * Read in German with overlays on, Extbase loads post 2 as its translation "Erster Artikel" and matches the
     * relation `related_posts` with `contains()` against that entity. `contains()` resolves the relation table with the
     * uid of the default post, 2, so it finds "Erste Rezension" like the core.
     *
     * @see containingATranslatedPostInAnMmRelationByCore()
     */
    #[Test]
    public function containingATranslatedPostInAnMmRelationByExtbase(): void
    {
        // Given: blog 2 and post 2 with their German translations and reviews that point to them and to the blog 5
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RelationsOfTranslationsRead/Reviews.csv');

        // When: the reviews that have a relation row to post 2 are selected and read in German with overlays on
        $this->get(Context::class)->setAspect('language', new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_ON));
        $this->get(PersistenceManager::class)->clearState();
        $constraintObject = $this->get(PostRepository::class)->findByUid(2);
        $query = $this->get(PostRepository::class)->createQuery();
        $query->getQuerySettings()->setStoragePageIds([2]);
        $query->setOrderings(['uid' => QueryInterface::ORDER_ASCENDING]);
        $query->matching($query->contains('relatedPosts', $constraintObject));
        $reviews = array_map(static fn(Post $review): string => $review->getTitle(), $query->execute()->toArray());

        // Then: the German review of post 2 is returned
        self::assertSame(['Erste Rezension'], $reviews);
    }

}
