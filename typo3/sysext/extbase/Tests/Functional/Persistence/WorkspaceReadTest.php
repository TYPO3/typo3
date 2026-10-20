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
use TYPO3\CMS\Core\Context\VisibilityAspect;
use TYPO3\CMS\Core\Context\WorkspaceAspect;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Domain\Persistence\RecordIdentityMap;
use TYPO3\CMS\Core\Domain\RecordFactory;
use TYPO3\CMS\Core\Domain\RecordInterface;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\TypoScript\AST\Node\RootNode;
use TYPO3\CMS\Core\TypoScript\FrontendTypoScript;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Page\PageInformation;
use TYPO3\CMS\Frontend\Page\PageParts;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Tests\BlogExample\Domain\Model\Blog;
use TYPO3Tests\BlogExample\Domain\Model\Post;
use TYPO3Tests\BlogExample\Domain\Repository\BlogRepository;

/**
 * Reading in workspaces: which records a query returns when a workspace is previewed in the frontend, and how this
 * applies to changed, new, deleted, hidden and moved records, to their relations and translations, and to counting.
 *
 * Every scenario is tested twice against the same expectation: once with what the core shows in the frontend as oracle
 * (suffix "ByCore"), once with Extbase (suffix "ByExtbase"). Both sides run in a frontend request in the workspace 1,
 * which the workspace aspect of the context selects, with the same language aspect. The core side reads the rows with
 * ContentObjectRenderer::getRecords(), the method behind the TypoScript objects CONTENT and RECORDS and the
 * DatabaseQueryProcessor: it selects the live rows of the storage page, replaces each row by its version of the
 * workspace with PageRepository::versionOL() and then overlays the translation. Relations are read with the record API
 * (RecordFactory), as RecordCollector does: the blog is selected with getRecords(), turned into a resolved record with
 * RecordFactory::createResolvedRecordFromDatabaseRow() and its field posts is read with Record::get().
 * The RelationHandler finds the uids of the related records in the workspace of the context, for the uid of the
 * translation if the blog is overlaid, and the related rows pass the version overlay, the language overlay and the
 * RecordAccessVoter. A record found by its uid is read with the TypoScript object RECORDS. Both sides return uid, pid
 * and title ordered by uid and are compared by these values. Both tests of a pair read the same fixture
 * Fixtures/WorkspaceRead/Blogs.csv. An Extbase test that differs from core is skipped.
 *
 * The blog and the post table are workspace aware, language aware and have the enable field hidden. Every scenario
 * reads the blogs of its own storage page: the page 2 for a changed field, 3 for a new record, 4 for a deleted record,
 * 5 for a hidden version, 6 and 7 for a moved record, 9 for children, 10 for translations and 12 for counting. The blog
 * has the inline children posts. The scenario with a deleted translated child reads the storage page 14
 * (Fixtures/WorkspaceRead/TranslatedPostDeleted.csv): blog 37 with its German translation (38), an English post (9) and
 * a German post (10) that the workspace deletes with a delete placeholder (11). The scenario on translated blogs reads
 * the storage page 15 (Fixtures/WorkspaceRead/TranslatedBlogs.csv): blog 40 with its German translation (41) and the
 * version of that translation (42), a blog for all languages (43) and a blog that is new in the workspace in the
 * default language (44). The scenarios on the translation of a hidden post read the storage page 16
 * (Fixtures/WorkspaceRead/TranslatedPostOfHiddenDefault.csv): blog 45 with its German translation (46), a visible post
 * (12) and a hidden post (14), each with a German translation (13, 15) that has no version in the workspace. The
 * scenario on ignored enable fields reads the storage page 17 in the live workspace
 * (Fixtures/WorkspaceRead/WorkspaceOnlyBlog.csv): the live blog 50, the hidden live blog 51 and blog 52, which exists
 * only in workspace 1.
 */
final class WorkspaceReadTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3/sysext/extbase/Tests/Functional/Fixtures/Extensions/blog_example',
    ];

    private ServerRequest $request;

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/WorkspaceRead/Blogs.csv');
        $frontendTypoScript = new FrontendTypoScript(new RootNode(), [], [], []);
        $frontendTypoScript->setSetupArray([]);
        $frontendTypoScript->setConfigArray([]);
        $pageInformation = new PageInformation();
        $pageInformation->setId(1);
        $pageInformation->setContentFromPid(1);
        $this->request = new ServerRequest()
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE)
            ->withAttribute('frontend.typoscript', $frontendTypoScript)
            ->withAttribute('frontend.page.information', $pageInformation)
            ->withAttribute('frontend.cache.collector', new CacheDataCollector())
            ->withAttribute('frontend.page.parts', new PageParts());
        $GLOBALS['TYPO3_REQUEST'] = $this->request;
        $this->get(ConfigurationManager::class)->setConfiguration([
            'extensionName' => 'blog_example',
            'pluginName' => 'test',
        ]);
        $this->get(ConfigurationManagerInterface::class)->setRequest($this->request);
        $this->get(Context::class)->setAspect('workspace', new WorkspaceAspect(1));
    }

    /**
     * In the workspace the core replaces a live row by its version. Of the blogs on the storage page 2 it returns the
     * unchanged blog (1) and blog 2 with the title of its version, "Blog after change", under the uid of the live
     * record (2); the version (3) is not returned as a record of its own.
     *
     * @see aChangedFieldIsReadFromTheVersionByExtbase()
     */
    #[Test]
    public function aChangedFieldIsReadFromTheVersionByCore(): void
    {
        // When: the blogs of the page with a changed blog are read in the workspace
        $contentObjectRenderer = $this->get(ContentObjectRenderer::class);
        $contentObjectRenderer->setRequest($this->request);
        $rows = $contentObjectRenderer->getRecords('tx_blogexample_domain_model_blog', ['pidInList' => '2', 'orderBy' => 'uid']);
        $blogs = array_map(static fn(array $row): array => [(int)$row['uid'], (int)$row['pid'], $row['title']], $rows);

        // Then: the blog has the title of its version and the uid of the live record
        self::assertSame([[1, 2, 'Unchanged blog'], [2, 2, 'Blog after change']], $blogs);
    }

    /**
     * Extbase returns the unchanged blog (1) and blog 2 with the title "Blog after change" of its version under the uid
     * of the live record, like the core.
     *
     * @see aChangedFieldIsReadFromTheVersionByCore()
     */
    #[Test]
    public function aChangedFieldIsReadFromTheVersionByExtbase(): void
    {
        // When: the blogs of the page with a changed blog are read in the workspace
        $query = $this->get(BlogRepository::class)->createQuery();
        $query->getQuerySettings()->setStoragePageIds([2]);
        $query->setOrderings(['uid' => QueryInterface::ORDER_ASCENDING]);
        $blogs = array_map(static fn(Blog $blog): array => [$blog->getUid(), $blog->getPid(), $blog->getTitle()], $query->execute()->toArray());

        // Then: the blog has the title of its version and the uid of the live record
        self::assertSame([[1, 2, 'Unchanged blog'], [2, 2, 'Blog after change']], $blogs);
    }

    /**
     * A record that was created in the workspace is a row with `t3ver_state` 1 and no live counterpart. Of the blogs on
     * the storage page 3 the core returns the live blog (4) and the blog that is new in the workspace (5).
     *
     * @see aRecordNewInTheWorkspaceIsReadByExtbase()
     */
    #[Test]
    public function aRecordNewInTheWorkspaceIsReadByCore(): void
    {
        // When: the blogs of the page with a blog that is new in the workspace are read
        $contentObjectRenderer = $this->get(ContentObjectRenderer::class);
        $contentObjectRenderer->setRequest($this->request);
        $rows = $contentObjectRenderer->getRecords('tx_blogexample_domain_model_blog', ['pidInList' => '3', 'orderBy' => 'uid']);
        $blogs = array_map(static fn(array $row): array => [(int)$row['uid'], (int)$row['pid'], $row['title']], $rows);

        // Then: the new blog is returned
        self::assertSame([[4, 3, 'Live blog'], [5, 3, 'Blog new in workspace']], $blogs);
    }

    /**
     * Extbase returns the live blog (4) and the blog that is new in the workspace (5) of the storage page 3, like the
     * core.
     *
     * @see aRecordNewInTheWorkspaceIsReadByCore()
     */
    #[Test]
    public function aRecordNewInTheWorkspaceIsReadByExtbase(): void
    {
        // When: the blogs of the page with a blog that is new in the workspace are read
        $query = $this->get(BlogRepository::class)->createQuery();
        $query->getQuerySettings()->setStoragePageIds([3]);
        $query->setOrderings(['uid' => QueryInterface::ORDER_ASCENDING]);
        $blogs = array_map(static fn(Blog $blog): array => [$blog->getUid(), $blog->getPid(), $blog->getTitle()], $query->execute()->toArray());

        // Then: the new blog is returned
        self::assertSame([[4, 3, 'Live blog'], [5, 3, 'Blog new in workspace']], $blogs);
    }

    /**
     * A record that was deleted in the workspace has a version with `t3ver_state` 2. Of the blogs on the storage page 4
     * the core returns only the kept blog (6), not the deleted blog (7).
     *
     * @see aRecordDeletedInTheWorkspaceIsNotReadByExtbase()
     */
    #[Test]
    public function aRecordDeletedInTheWorkspaceIsNotReadByCore(): void
    {
        // When: the blogs of the page with a blog that is deleted in the workspace are read
        $contentObjectRenderer = $this->get(ContentObjectRenderer::class);
        $contentObjectRenderer->setRequest($this->request);
        $rows = $contentObjectRenderer->getRecords('tx_blogexample_domain_model_blog', ['pidInList' => '4', 'orderBy' => 'uid']);
        $blogs = array_map(static fn(array $row): array => [(int)$row['uid'], (int)$row['pid'], $row['title']], $rows);

        // Then: the deleted blog is not returned
        self::assertSame([[6, 4, 'Kept blog']], $blogs);
    }

    /**
     * Extbase returns only the kept blog (6) of the storage page 4 and not the blog that is deleted in the workspace
     * (7), like the core.
     *
     * @see aRecordDeletedInTheWorkspaceIsNotReadByCore()
     */
    #[Test]
    public function aRecordDeletedInTheWorkspaceIsNotReadByExtbase(): void
    {
        // When: the blogs of the page with a blog that is deleted in the workspace are read
        $query = $this->get(BlogRepository::class)->createQuery();
        $query->getQuerySettings()->setStoragePageIds([4]);
        $query->setOrderings(['uid' => QueryInterface::ORDER_ASCENDING]);
        $blogs = array_map(static fn(Blog $blog): array => [$blog->getUid(), $blog->getPid(), $blog->getTitle()], $query->execute()->toArray());

        // Then: the deleted blog is not returned
        self::assertSame([[6, 4, 'Kept blog']], $blogs);
    }

    /**
     * A record that was moved in the workspace has a version with `t3ver_state` 4 on the target page. On the target
     * page 7 the core returns the blog that is on the page (14) and the moved blog (13) under its live uid, with the
     * `pid` of the target page (7).
     *
     * @see aMovedRecordIsReadOnItsTargetPageByExtbase()
     */
    #[Test]
    public function aMovedRecordIsReadOnItsTargetPageByCore(): void
    {
        // When: the blogs of the page the blog was moved to are read
        $contentObjectRenderer = $this->get(ContentObjectRenderer::class);
        $contentObjectRenderer->setRequest($this->request);
        $rows = $contentObjectRenderer->getRecords('tx_blogexample_domain_model_blog', ['pidInList' => '7', 'orderBy' => 'uid']);
        $blogs = array_map(static fn(array $row): array => [(int)$row['uid'], (int)$row['pid'], $row['title']], $rows);

        // Then: the moved blog is returned with the uid of the live record and the pid of the target page
        self::assertSame([[14, 7, 'Blog on target'], [13, 7, 'Moved blog']], $blogs);
    }

    /**
     * Extbase returns only the blog that is on the target page 7 (14). The blog that was moved to this page in the
     * workspace (13) is not in the list: its version with `t3ver_state` 4 is not selected.
     *
     * @see aMovedRecordIsReadOnItsTargetPageByCore()
     */
    #[Test]
    public function aMovedRecordIsReadOnItsTargetPageByExtbase(): void
    {
        self::markTestSkipped('Extbase differs from core: it does not return the moved blog (13) on its target page, where the core returns it with the `pid` of the target page.');

        // When: the blogs of the page the blog was moved to are read
        // @phpstan-ignore deadCode.unreachable
        $query = $this->get(BlogRepository::class)->createQuery();
        $query->getQuerySettings()->setStoragePageIds([7]);
        $query->setOrderings(['uid' => QueryInterface::ORDER_ASCENDING]);
        $blogs = array_map(static fn(Blog $blog): array => [$blog->getUid(), $blog->getPid(), $blog->getTitle()], $query->execute()->toArray());

        // Then: the moved blog is returned with the uid of the live record and the pid of the target page
        self::assertSame([[14, 7, 'Blog on target'], [13, 7, 'Moved blog']], $blogs);
    }

    /**
     * The page a record was moved from does not show it in the workspace. Of the blogs on the page 6 the core returns
     * only the blog that stayed (12), not the blog that was moved to the page 7 (13).
     *
     * @see aMovedRecordIsNotReadOnItsSourcePageByExtbase()
     */
    #[Test]
    public function aMovedRecordIsNotReadOnItsSourcePageByCore(): void
    {
        // When: the blogs of the page the blog was moved from are read
        $contentObjectRenderer = $this->get(ContentObjectRenderer::class);
        $contentObjectRenderer->setRequest($this->request);
        $rows = $contentObjectRenderer->getRecords('tx_blogexample_domain_model_blog', ['pidInList' => '6', 'orderBy' => 'uid']);
        $blogs = array_map(static fn(array $row): array => [(int)$row['uid'], (int)$row['pid'], $row['title']], $rows);

        // Then: the moved blog is not returned
        self::assertSame([[12, 6, 'Staying blog']], $blogs);
    }

    /**
     * Extbase returns only the blog that stayed on the page 6 (12) and not the blog that was moved to the page 7 (13),
     * like the core.
     *
     * @see aMovedRecordIsNotReadOnItsSourcePageByCore()
     */
    #[Test]
    public function aMovedRecordIsNotReadOnItsSourcePageByExtbase(): void
    {
        // When: the blogs of the page the blog was moved from are read
        $query = $this->get(BlogRepository::class)->createQuery();
        $query->getQuerySettings()->setStoragePageIds([6]);
        $query->setOrderings(['uid' => QueryInterface::ORDER_ASCENDING]);
        $blogs = array_map(static fn(Blog $blog): array => [$blog->getUid(), $blog->getPid(), $blog->getTitle()], $query->execute()->toArray());

        // Then: the moved blog is not returned
        self::assertSame([[12, 6, 'Staying blog']], $blogs);
    }

    /**
     * The core reads a record by its uid with the TypoScript object `RECORDS` (`source`
     * "tx_blogexample_domain_model_blog_13"), which replaces the live row by its version. For blog 13, which was moved
     * to the page 7 in the workspace, it returns the live uid (13), the `pid` of the target page (7) and the title
     * "Moved blog".
     *
     * @see aMovedRecordFoundByUidIsReadOnItsTargetPageByExtbase()
     */
    #[Test]
    public function aMovedRecordFoundByUidIsReadOnItsTargetPageByCore(): void
    {
        // When: the moved blog is read by the uid of the live record
        $contentObjectRenderer = $this->get(ContentObjectRenderer::class);
        $contentObjectRenderer->setRequest($this->request);
        $output = $contentObjectRenderer->cObjGetSingle('RECORDS', [
            'source' => 'tx_blogexample_domain_model_blog_13',
            'tables' => 'tx_blogexample_domain_model_blog',
            'dontCheckPid' => 1,
            'conf.' => [
                'tx_blogexample_domain_model_blog' => 'TEXT',
                'tx_blogexample_domain_model_blog.' => ['value' => '{field:uid};{field:pid};{field:title}', 'insertData' => 1],
            ],
        ]);
        $parts = explode(';', $output);
        $blog = [(int)$parts[0], (int)$parts[1], $parts[2]];

        // Then: the blog is returned with the pid of the target page
        self::assertSame([13, 7, 'Moved blog'], $blog);
    }

    /**
     * Extbase finds the blog 13 by its uid with `BlogRepository::findByUid()` and returns it with the `pid` of the
     * target page (7) and the title "Moved blog", like the core.
     *
     * @see aMovedRecordFoundByUidIsReadOnItsTargetPageByCore()
     */
    #[Test]
    public function aMovedRecordFoundByUidIsReadOnItsTargetPageByExtbase(): void
    {
        // When: the moved blog is read by the uid of the live record
        $blog = $this->get(BlogRepository::class)->findByUid(13);
        $blog = [$blog->getUid(), $blog->getPid(), $blog->getTitle()];

        // Then: the blog is returned with the pid of the target page
        self::assertSame([13, 7, 'Moved blog'], $blog);
    }

    /**
     * In the workspace the core checks the hidden field on the version of a record. Blog 10 is visible in live, but its
     * version is hidden, so of the blogs on the storage page 5 the core returns only the visible blog (9).
     *
     * @see aHiddenVersionIsNotReadByExtbase()
     */
    #[Test]
    public function aHiddenVersionIsNotReadByCore(): void
    {
        // When: the blogs of the page with a blog that is hidden in the workspace are read
        $contentObjectRenderer = $this->get(ContentObjectRenderer::class);
        $contentObjectRenderer->setRequest($this->request);
        $rows = $contentObjectRenderer->getRecords('tx_blogexample_domain_model_blog', ['pidInList' => '5', 'orderBy' => 'uid']);
        $blogs = array_map(static fn(array $row): array => [(int)$row['uid'], (int)$row['pid'], $row['title']], $rows);

        // Then: the blog with the hidden version is not returned
        self::assertSame([[9, 5, 'Visible blog']], $blogs);
    }

    /**
     * Extbase returns only the visible blog (9) of the storage page 5 and not blog 10, whose version is hidden, like
     * the core.
     *
     * @see aHiddenVersionIsNotReadByCore()
     */
    #[Test]
    public function aHiddenVersionIsNotReadByExtbase(): void
    {
        // When: the blogs of the page with a blog that is hidden in the workspace are read
        $query = $this->get(BlogRepository::class)->createQuery();
        $query->getQuerySettings()->setStoragePageIds([5]);
        $query->setOrderings(['uid' => QueryInterface::ORDER_ASCENDING]);
        $blogs = array_map(static fn(Blog $blog): array => [$blog->getUid(), $blog->getPid(), $blog->getTitle()], $query->execute()->toArray());

        // Then: the blog with the hidden version is not returned
        self::assertSame([[9, 5, 'Visible blog']], $blogs);
    }

    /**
     * The core reads the field `posts` of blog 18 like the record API (RecordFactory) does: the RelationHandler finds
     * the posts of the blog in the workspace, and each post is replaced by its version. It returns the first post (1),
     * the second post with the title of its version, "Second post revised" (2), and the post that is new in the
     * workspace (6), ordered by `sorting`. The third post (3) is not returned, because the workspace deletes it.
     *
     * @see childrenAreReadThroughTheVersionOfTheParentByExtbase()
     */
    #[Test]
    public function childrenAreReadThroughTheVersionOfTheParentByCore(): void
    {
        // When: the posts of the blog with a version are read in the workspace
        $contentObjectRenderer = $this->get(ContentObjectRenderer::class);
        $contentObjectRenderer->setRequest($this->request);
        $rows = $contentObjectRenderer->getRecords('tx_blogexample_domain_model_blog', ['pidInList' => '9', 'orderBy' => 'uid']);
        $blog = $this->get(RecordFactory::class)->createResolvedRecordFromDatabaseRow(
            'tx_blogexample_domain_model_blog',
            $rows[0],
            $this->get(Context::class),
            new RecordIdentityMap()
        );
        $posts = array_map(static fn(RecordInterface $post): array => [$post->getUid(), $post->getPid(), $post->get('title')], iterator_to_array($blog->get('posts'), false));

        // Then: the posts are returned as the workspace has changed them
        self::assertSame([[1, 9, 'First post'], [2, 9, 'Second post revised'], [6, 9, 'Post new in workspace']], $posts);
    }

    /**
     * Extbase returns the same posts of blog 18 as the core: the first post (1), the second post as "Second post
     * revised" (2) and the post that is new in the workspace (6). The third post (3), which the workspace deletes, is
     * not returned.
     *
     * @see childrenAreReadThroughTheVersionOfTheParentByCore()
     */
    #[Test]
    public function childrenAreReadThroughTheVersionOfTheParentByExtbase(): void
    {
        // When: the posts of the blog with a version are read in the workspace
        $query = $this->get(BlogRepository::class)->createQuery();
        $query->getQuerySettings()->setStoragePageIds([9]);
        $query->setOrderings(['uid' => QueryInterface::ORDER_ASCENDING]);
        $blog = $query->execute()->getFirst();
        $posts = array_map(static fn(Post $post): array => [$post->getUid(), $post->getPid(), $post->getTitle()], $blog->getPosts()->toArray());

        // Then: the posts are returned as the workspace has changed them
        self::assertSame([[1, 9, 'First post'], [2, 9, 'Second post revised'], [6, 9, 'Post new in workspace']], $posts);
    }

    /**
     * Read with German and overlays on, the core replaces the translation of blog 20 by its version in the workspace.
     * It returns the uid of the default record (20) with the title of the version of the German translation, "Blog auf
     * Deutsch im Workspace", and not the title of the live translation, "Blog auf Deutsch".
     *
     * @see aTranslationIsReadThroughItsOwnVersionByExtbase()
     */
    #[Test]
    public function aTranslationIsReadThroughItsOwnVersionByCore(): void
    {
        // When: the blogs of the page with a translated blog are read with German and overlays on
        $this->get(Context::class)->setAspect('language', new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_ON));
        $contentObjectRenderer = $this->get(ContentObjectRenderer::class);
        $contentObjectRenderer->setRequest($this->request);
        $rows = $contentObjectRenderer->getRecords('tx_blogexample_domain_model_blog', ['pidInList' => '10', 'orderBy' => 'uid']);
        $blogs = array_map(static fn(array $row): array => [(int)$row['uid'], (int)$row['pid'], $row['title']], $rows);

        // Then: the blog has the title of the version of its translation
        self::assertSame([[20, 10, 'Blog auf Deutsch im Workspace']], $blogs);
    }

    /**
     * Read with German and overlays on, Extbase returns blog 20 with the title of the version of its German
     * translation, "Blog auf Deutsch im Workspace", like the core.
     *
     * @see aTranslationIsReadThroughItsOwnVersionByCore()
     */
    #[Test]
    public function aTranslationIsReadThroughItsOwnVersionByExtbase(): void
    {
        // When: the blogs of the page with a translated blog are read with German and overlays on
        $this->get(Context::class)->setAspect('language', new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_ON));
        $query = $this->get(BlogRepository::class)->createQuery();
        $query->getQuerySettings()->setStoragePageIds([10]);
        $query->setOrderings(['uid' => QueryInterface::ORDER_ASCENDING]);
        $blogs = array_map(static fn(Blog $blog): array => [$blog->getUid(), $blog->getPid(), $blog->getTitle()], $query->execute()->toArray());

        // Then: the blog has the title of the version of its translation
        self::assertSame([[20, 10, 'Blog auf Deutsch im Workspace']], $blogs);
    }

    /**
     * Read with German and overlays mixed in the workspace, the core replaces the German translation of blog 40 by its
     * version and returns it under the uid of the default record (40) as "Blog auf Deutsch im Workspace". The blog for
     * all languages (43) is returned unchanged, and so is the blog that is new in the workspace (44) in the default
     * language, because with overlays mixed a record without translation keeps its default language.
     *
     * @see readingTranslatedBlogsInAWorkspaceWithOverlaysMixedByExtbase()
     */
    #[Test]
    public function readingTranslatedBlogsInAWorkspaceWithOverlaysMixedByCore(): void
    {
        // Given: blog 40 with a German translation that has a version, a blog for all languages and a blog that is new
        // in the workspace
        $this->importCSVDataSet(__DIR__ . '/Fixtures/WorkspaceRead/TranslatedBlogs.csv');

        // When: the blogs of the page are read in the workspace with German and overlays mixed
        $this->get(Context::class)->setAspect('language', new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_MIXED));
        $contentObjectRenderer = $this->get(ContentObjectRenderer::class);
        $contentObjectRenderer->setRequest($this->request);
        $rows = $contentObjectRenderer->getRecords('tx_blogexample_domain_model_blog', ['pidInList' => '15', 'orderBy' => 'uid']);
        $blogs = array_map(static fn(array $row): array => [(int)$row['uid'], (int)$row['pid'], $row['title']], $rows);

        // Then: the translated version, the blog for all languages and the new blog in the default language are
        // returned
        self::assertSame([[40, 15, 'Blog auf Deutsch im Workspace'], [43, 15, 'Blog for all languages'], [44, 15, 'Blog new in workspace']], $blogs);
    }

    /**
     * Read with German and overlays mixed in the workspace, Extbase returns the same three blogs as the core: blog 40
     * with the title of the version of its German translation, "Blog auf Deutsch im Workspace", the blog for all
     * languages (43) and the blog that is new in the workspace (44) in the default language.
     *
     * @see readingTranslatedBlogsInAWorkspaceWithOverlaysMixedByCore()
     */
    #[Test]
    public function readingTranslatedBlogsInAWorkspaceWithOverlaysMixedByExtbase(): void
    {
        // Given: blog 40 with a German translation that has a version, a blog for all languages and a blog that is new
        // in the workspace
        $this->importCSVDataSet(__DIR__ . '/Fixtures/WorkspaceRead/TranslatedBlogs.csv');

        // When: the blogs of the page are read in the workspace with German and overlays mixed
        $this->get(Context::class)->setAspect('language', new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_MIXED));
        $query = $this->get(BlogRepository::class)->createQuery();
        $query->getQuerySettings()->setStoragePageIds([15]);
        $query->setOrderings(['uid' => QueryInterface::ORDER_ASCENDING]);
        $blogs = array_map(static fn(Blog $blog): array => [$blog->getUid(), $blog->getPid(), $blog->getTitle()], $query->execute()->toArray());

        // Then: the translated version, the blog for all languages and the new blog in the default language are
        // returned
        self::assertSame([[40, 15, 'Blog auf Deutsch im Workspace'], [43, 15, 'Blog for all languages'], [44, 15, 'Blog new in workspace']], $blogs);
    }

    /**
     * A condition on a field is evaluated on the live row, before the workspace is applied. Blog 2 is live as "Blog
     * before change" and has a version (3) titled "Blog after change". The core selects the live blogs of the storage
     * page 2 whose title is "Blog before change" and then replaces each by its version: it returns blog 2 with the
     * title of its version, "Blog after change", although the title of the version does not match the condition.
     *
     * @see aConditionOnTheLiveValueOfAChangedFieldByExtbase()
     */
    #[Test]
    public function aConditionOnTheLiveValueOfAChangedFieldByCore(): void
    {
        // When: the blogs with the live title "Blog before change" are read in the workspace
        $contentObjectRenderer = $this->get(ContentObjectRenderer::class);
        $contentObjectRenderer->setRequest($this->request);
        $rows = $contentObjectRenderer->getRecords('tx_blogexample_domain_model_blog', ['pidInList' => '2', 'orderBy' => 'uid', 'where' => "title = 'Blog before change'"]);
        $blogs = array_map(static fn(array $row): array => [(int)$row['uid'], (int)$row['pid'], $row['title']], $rows);

        // Then: the blog is returned with the title of its version
        self::assertSame([[2, 2, 'Blog after change']], $blogs);
    }

    /**
     * Extbase returns blog 2 with the title of its version, "Blog after change", for the condition that the title
     * equals the live title "Blog before change", like the core: the condition is evaluated on the live row and the
     * version replaces the row afterwards.
     *
     * @see aConditionOnTheLiveValueOfAChangedFieldByCore()
     */
    #[Test]
    public function aConditionOnTheLiveValueOfAChangedFieldByExtbase(): void
    {
        // When: the blogs with the live title "Blog before change" are read in the workspace
        $query = $this->get(BlogRepository::class)->createQuery();
        $query->getQuerySettings()->setStoragePageIds([2]);
        $query->setOrderings(['uid' => QueryInterface::ORDER_ASCENDING]);
        $query->matching($query->equals('title', 'Blog before change'));
        $blogs = array_map(static fn(Blog $blog): array => [$blog->getUid(), $blog->getPid(), $blog->getTitle()], $query->execute()->toArray());

        // Then: the blog is returned with the title of its version
        self::assertSame([[2, 2, 'Blog after change']], $blogs);
    }

    /**
     * Blog 2 is live as "Blog before change" and has a version (3) titled "Blog after change". The core selects the
     * live blogs of the storage page 2 whose title is "Blog after change", the title of the version, and finds none,
     * because the live row holds the old title: the result is empty although the blog shows that title in the
     * workspace.
     *
     * @see aConditionOnTheValueOfTheVersionOfAChangedFieldByExtbase()
     */
    #[Test]
    public function aConditionOnTheValueOfTheVersionOfAChangedFieldByCore(): void
    {
        // When: the blogs with the title "Blog after change" are read in the workspace
        $contentObjectRenderer = $this->get(ContentObjectRenderer::class);
        $contentObjectRenderer->setRequest($this->request);
        $rows = $contentObjectRenderer->getRecords('tx_blogexample_domain_model_blog', ['pidInList' => '2', 'orderBy' => 'uid', 'where' => "title = 'Blog after change'"]);
        $blogs = array_map(static fn(array $row): array => [(int)$row['uid'], (int)$row['pid'], $row['title']], $rows);

        // Then: no blog is returned
        self::assertSame([], $blogs);
    }

    /**
     * Extbase returns no blog for the condition that the title equals "Blog after change", the title of the version of
     * blog 2, like the core: the condition is evaluated on the live row, which still holds "Blog before change".
     *
     * @see aConditionOnTheValueOfTheVersionOfAChangedFieldByCore()
     */
    #[Test]
    public function aConditionOnTheValueOfTheVersionOfAChangedFieldByExtbase(): void
    {
        // When: the blogs with the title "Blog after change" are read in the workspace
        $query = $this->get(BlogRepository::class)->createQuery();
        $query->getQuerySettings()->setStoragePageIds([2]);
        $query->setOrderings(['uid' => QueryInterface::ORDER_ASCENDING]);
        $query->matching($query->equals('title', 'Blog after change'));
        $blogs = array_map(static fn(Blog $blog): array => [$blog->getUid(), $blog->getPid(), $blog->getTitle()], $query->execute()->toArray());

        // Then: no blog is returned
        self::assertSame([], $blogs);
    }

    /**
     * Read with German and overlays on, the core resolves blog 37 to its German translation (38) and reads the field
     * `posts` like the record API (RecordFactory) does: the RelationHandler finds the posts that point to the uid of
     * the translation, 38. The German post (10) has a delete placeholder in the workspace, so no post is returned. The
     * English post (9) points to blog 37 and is not found.
     *
     * @see aTranslatedChildDeletedInTheWorkspaceIsNotReadWithOverlaysOnByExtbase()
     */
    #[Test]
    public function aTranslatedChildDeletedInTheWorkspaceIsNotReadWithOverlaysOnByCore(): void
    {
        // Given: blog 37 with a German translation (38) and a German post (10) that the workspace deletes
        $this->importCSVDataSet(__DIR__ . '/Fixtures/WorkspaceRead/TranslatedPostDeleted.csv');

        // When: the posts of the blog are read with German and overlays on
        $this->get(Context::class)->setAspect('language', new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_ON));
        $contentObjectRenderer = $this->get(ContentObjectRenderer::class);
        $contentObjectRenderer->setRequest($this->request);
        $rows = $contentObjectRenderer->getRecords('tx_blogexample_domain_model_blog', ['pidInList' => '14', 'orderBy' => 'uid']);
        $blog = $this->get(RecordFactory::class)->createResolvedRecordFromDatabaseRow(
            'tx_blogexample_domain_model_blog',
            $rows[0],
            $this->get(Context::class),
            new RecordIdentityMap()
        );
        $posts = array_map(static fn(RecordInterface $post): array => [$post->getUid(), $post->getPid(), $post->get('title')], iterator_to_array($blog->get('posts'), false));

        // Then: no post is returned
        self::assertSame([], $posts);
    }

    /**
     * Read with German and overlays on, Extbase returns no post for blog 37 either: the German post (10) has a delete
     * placeholder in the workspace.
     *
     * @see aTranslatedChildDeletedInTheWorkspaceIsNotReadWithOverlaysOnByCore()
     */
    #[Test]
    public function aTranslatedChildDeletedInTheWorkspaceIsNotReadWithOverlaysOnByExtbase(): void
    {
        // Given: blog 37 with a German translation (38) and a German post (10) that the workspace deletes
        $this->importCSVDataSet(__DIR__ . '/Fixtures/WorkspaceRead/TranslatedPostDeleted.csv');

        // When: the posts of the blog are read with German and overlays on
        $this->get(Context::class)->setAspect('language', new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_ON));
        $query = $this->get(BlogRepository::class)->createQuery();
        $query->getQuerySettings()->setStoragePageIds([14]);
        $query->setOrderings(['uid' => QueryInterface::ORDER_ASCENDING]);
        $blog = $query->execute()->getFirst();
        $posts = array_map(static fn(Post $post): array => [$post->getUid(), $post->getPid(), $post->getTitle()], $blog->getPosts()->toArray());

        // Then: no post is returned
        self::assertSame([], $posts);
    }

    /**
     * Read with German and overlays mixed, the core returns no post either. It resolves blog 37 to its German
     * translation (38) and reads the posts that point to the uid of the translation, 38, and the German post (10) has a
     * delete placeholder in the workspace. The English post (9) points to blog 37 and is not found, so it is not kept
     * in English.
     *
     * @see aTranslatedChildDeletedInTheWorkspaceIsNotReadWithOverlaysMixedByExtbase()
     */
    #[Test]
    public function aTranslatedChildDeletedInTheWorkspaceIsNotReadWithOverlaysMixedByCore(): void
    {
        // Given: blog 37 with a German translation (38) and a German post (10) that the workspace deletes
        $this->importCSVDataSet(__DIR__ . '/Fixtures/WorkspaceRead/TranslatedPostDeleted.csv');

        // When: the posts of the blog are read with German and overlays mixed
        $this->get(Context::class)->setAspect('language', new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_MIXED));
        $contentObjectRenderer = $this->get(ContentObjectRenderer::class);
        $contentObjectRenderer->setRequest($this->request);
        $rows = $contentObjectRenderer->getRecords('tx_blogexample_domain_model_blog', ['pidInList' => '14', 'orderBy' => 'uid']);
        $blog = $this->get(RecordFactory::class)->createResolvedRecordFromDatabaseRow(
            'tx_blogexample_domain_model_blog',
            $rows[0],
            $this->get(Context::class),
            new RecordIdentityMap()
        );
        $posts = array_map(static fn(RecordInterface $post): array => [$post->getUid(), $post->getPid(), $post->get('title')], iterator_to_array($blog->get('posts'), false));

        // Then: no post is returned
        self::assertSame([], $posts);
    }

    /**
     * Read with German and overlays mixed, Extbase returns the English post (9) for blog 37, where the core returns no
     * post: the delete placeholder drops the German row and the overlay falls back to the English record.
     *
     * @see aTranslatedChildDeletedInTheWorkspaceIsNotReadWithOverlaysMixedByCore()
     */
    #[Test]
    public function aTranslatedChildDeletedInTheWorkspaceIsNotReadWithOverlaysMixedByExtbase(): void
    {
        self::markTestSkipped('Extbase differs from core: it returns the English post "English post of a deleted translation" (9), where the core returns no post.');

        // Given: blog 37 with a German translation (38) and a German post (10) that the workspace deletes
        // @phpstan-ignore deadCode.unreachable
        $this->importCSVDataSet(__DIR__ . '/Fixtures/WorkspaceRead/TranslatedPostDeleted.csv');

        // When: the posts of the blog are read with German and overlays mixed
        $this->get(Context::class)->setAspect('language', new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_MIXED));
        $query = $this->get(BlogRepository::class)->createQuery();
        $query->getQuerySettings()->setStoragePageIds([14]);
        $query->setOrderings(['uid' => QueryInterface::ORDER_ASCENDING]);
        $blog = $query->execute()->getFirst();
        $posts = array_map(static fn(Post $post): array => [$post->getUid(), $post->getPid(), $post->getTitle()], $blog->getPosts()->toArray());

        // Then: no post is returned
        self::assertSame([], $posts);
    }

    /**
     * Read in the workspace with German and overlays on, the core resolves blog 45 to its German translation (46) and
     * reads the field `posts` like the record API (RecordFactory) does: the RelationHandler finds the posts that point
     * to the uid of the translation, 46, and returns the German posts under their own uids, "Sichtbarer Beitrag" (13)
     * and "Versteckter Beitrag" (15). The record API does not look at the default record of a translation, so it
     * returns the translation of the hidden post (14) although its default record is hidden; the translation is not
     * hidden and has no version in the workspace.
     *
     * @see aTranslationOfAHiddenDefaultChildIsReadWithOverlaysOnByExtbase()
     */
    #[Test]
    public function aTranslationOfAHiddenDefaultChildIsReadWithOverlaysOnByCore(): void
    {
        // Given: blog 45 with a German translation (46), a visible post (12) and a hidden post (14), each with a German
        // translation
        $this->importCSVDataSet(__DIR__ . '/Fixtures/WorkspaceRead/TranslatedPostOfHiddenDefault.csv');

        // When: the posts of the blog are read in the workspace with German and overlays on
        $this->get(Context::class)->setAspect('language', new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_ON));
        $contentObjectRenderer = $this->get(ContentObjectRenderer::class);
        $contentObjectRenderer->setRequest($this->request);
        $rows = $contentObjectRenderer->getRecords('tx_blogexample_domain_model_blog', ['pidInList' => '16', 'orderBy' => 'uid']);
        $blog = $this->get(RecordFactory::class)->createResolvedRecordFromDatabaseRow(
            'tx_blogexample_domain_model_blog',
            $rows[0],
            $this->get(Context::class),
            new RecordIdentityMap()
        );
        $posts = array_map(static fn(RecordInterface $post): array => [$post->getUid(), $post->getPid(), $post->get('title')], iterator_to_array($blog->get('posts'), false));

        // Then: the translations of both posts are returned
        self::assertSame([[13, 16, 'Sichtbarer Beitrag'], [15, 16, 'Versteckter Beitrag']], $posts);
    }

    /**
     * Read in the workspace with German and overlays on, Extbase returns only "Sichtbarer Beitrag", under the uid of
     * the default post (12), where the core returns "Sichtbarer Beitrag" (13) and "Versteckter Beitrag" (15) under the
     * uids of the translations. Extbase drops the translation of the hidden post, because it overlays the translation
     * through its default record, which is hidden and has no version in the workspace.
     *
     * @see aTranslationOfAHiddenDefaultChildIsReadWithOverlaysOnByCore()
     */
    #[Test]
    public function aTranslationOfAHiddenDefaultChildIsReadWithOverlaysOnByExtbase(): void
    {
        self::markTestSkipped('Extbase differs from core: it returns only "Sichtbarer Beitrag" (12) and drops the translation of the hidden default post, "Versteckter Beitrag", where the core returns both translations under their own uids (13, 15).');

        // Given: blog 45 with a German translation (46), a visible post (12) and a hidden post (14), each with a German
        // translation
        // @phpstan-ignore deadCode.unreachable
        $this->importCSVDataSet(__DIR__ . '/Fixtures/WorkspaceRead/TranslatedPostOfHiddenDefault.csv');

        // When: the posts of the blog are read in the workspace with German and overlays on
        $this->get(Context::class)->setAspect('language', new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_ON));
        $query = $this->get(BlogRepository::class)->createQuery();
        $query->getQuerySettings()->setStoragePageIds([16]);
        $query->setOrderings(['uid' => QueryInterface::ORDER_ASCENDING]);
        $blog = $query->execute()->getFirst();
        $posts = array_map(static fn(Post $post): array => [$post->getUid(), $post->getPid(), $post->getTitle()], $blog->getPosts()->toArray());

        // Then: the translations of both posts are returned
        self::assertSame([[13, 16, 'Sichtbarer Beitrag'], [15, 16, 'Versteckter Beitrag']], $posts);
    }

    /**
     * Read in the workspace with German and overlays mixed, the core resolves blog 45 to its German translation (46)
     * and reads the field `posts` like the record API (RecordFactory) does: the RelationHandler finds the posts that
     * point to the uid of the translation, 46, and returns the German posts under their own uids, "Sichtbarer Beitrag"
     * (13) and "Versteckter Beitrag" (15). The record API does not look at the default record of a translation, so it
     * returns the translation of the hidden post (14) although its default record is hidden; the translation is not
     * hidden and has no version in the workspace.
     *
     * @see aTranslationOfAHiddenDefaultChildIsReadWithOverlaysMixedByExtbase()
     */
    #[Test]
    public function aTranslationOfAHiddenDefaultChildIsReadWithOverlaysMixedByCore(): void
    {
        // Given: blog 45 with a German translation (46), a visible post (12) and a hidden post (14), each with a German
        // translation
        $this->importCSVDataSet(__DIR__ . '/Fixtures/WorkspaceRead/TranslatedPostOfHiddenDefault.csv');

        // When: the posts of the blog are read in the workspace with German and overlays mixed
        $this->get(Context::class)->setAspect('language', new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_MIXED));
        $contentObjectRenderer = $this->get(ContentObjectRenderer::class);
        $contentObjectRenderer->setRequest($this->request);
        $rows = $contentObjectRenderer->getRecords('tx_blogexample_domain_model_blog', ['pidInList' => '16', 'orderBy' => 'uid']);
        $blog = $this->get(RecordFactory::class)->createResolvedRecordFromDatabaseRow(
            'tx_blogexample_domain_model_blog',
            $rows[0],
            $this->get(Context::class),
            new RecordIdentityMap()
        );
        $posts = array_map(static fn(RecordInterface $post): array => [$post->getUid(), $post->getPid(), $post->get('title')], iterator_to_array($blog->get('posts'), false));

        // Then: the translations of both posts are returned
        self::assertSame([[13, 16, 'Sichtbarer Beitrag'], [15, 16, 'Versteckter Beitrag']], $posts);
    }

    /**
     * Read in the workspace with German and overlays mixed, Extbase returns only "Sichtbarer Beitrag", under the uid of
     * the default post (12), where the core returns "Sichtbarer Beitrag" (13) and "Versteckter Beitrag" (15) under the
     * uids of the translations. Extbase drops the translation of the hidden post, because it overlays the translation
     * through its default record, which is hidden and has no version in the workspace.
     *
     * @see aTranslationOfAHiddenDefaultChildIsReadWithOverlaysMixedByCore()
     */
    #[Test]
    public function aTranslationOfAHiddenDefaultChildIsReadWithOverlaysMixedByExtbase(): void
    {
        self::markTestSkipped('Extbase differs from core: it returns only "Sichtbarer Beitrag" (12) and drops the translation of the hidden default post, "Versteckter Beitrag", where the core returns both translations under their own uids (13, 15).');

        // Given: blog 45 with a German translation (46), a visible post (12) and a hidden post (14), each with a German
        // translation
        // @phpstan-ignore deadCode.unreachable
        $this->importCSVDataSet(__DIR__ . '/Fixtures/WorkspaceRead/TranslatedPostOfHiddenDefault.csv');

        // When: the posts of the blog are read in the workspace with German and overlays mixed
        $this->get(Context::class)->setAspect('language', new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_MIXED));
        $query = $this->get(BlogRepository::class)->createQuery();
        $query->getQuerySettings()->setStoragePageIds([16]);
        $query->setOrderings(['uid' => QueryInterface::ORDER_ASCENDING]);
        $blog = $query->execute()->getFirst();
        $posts = array_map(static fn(Post $post): array => [$post->getUid(), $post->getPid(), $post->getTitle()], $blog->getPosts()->toArray());

        // Then: the translations of both posts are returned
        self::assertSame([[13, 16, 'Sichtbarer Beitrag'], [15, 16, 'Versteckter Beitrag']], $posts);
    }

    /**
     * The core counts the records it delivers. Of the blogs on the storage page 12 it delivers three: blog 25, the kept
     * blog (30) and the blog that is new in the workspace (31). The blog that the workspace deletes (26) and the blog
     * whose version is hidden (28) are dropped in the overlay and are not counted.
     *
     * @see countingInAWorkspaceCountsTheRecordsThatAreReadByExtbase()
     */
    #[Test]
    public function countingInAWorkspaceCountsTheRecordsThatAreReadByCore(): void
    {
        // When: the blogs of the page with changed blogs are read and counted
        $contentObjectRenderer = $this->get(ContentObjectRenderer::class);
        $contentObjectRenderer->setRequest($this->request);
        $rows = $contentObjectRenderer->getRecords('tx_blogexample_domain_model_blog', ['pidInList' => '12', 'orderBy' => 'uid']);
        $count = count($rows);

        // Then: the count is the number of the blogs that are returned
        self::assertSame(3, $count);
    }

    /**
     * Extbase counts 5 blogs on the storage page 12, although a query for them delivers 3 and the core counts 3:
     * `count()` counts the live rows, including the blog that the workspace deletes (26) and the blog whose version is
     * hidden (28), before the workspace is applied.
     *
     * @see countingInAWorkspaceCountsTheRecordsThatAreReadByCore()
     */
    #[Test]
    public function countingInAWorkspaceCountsTheRecordsThatAreReadByExtbase(): void
    {
        self::markTestSkipped('Extbase differs from core: it counts 5 blogs, where the core delivers 3, because it counts the live rows before the workspace is applied.');

        // When: the blogs of the page with changed blogs are read and counted
        // @phpstan-ignore deadCode.unreachable
        $query = $this->get(BlogRepository::class)->createQuery();
        $query->getQuerySettings()->setStoragePageIds([12]);
        $query->setOrderings(['uid' => QueryInterface::ORDER_ASCENDING]);
        $count = $query->count();

        // Then: the count is the number of the blogs that are returned
        self::assertSame(3, $count);
    }

    /**
     * The core keeps `t3ver_wsid = 0` in the live workspace even when every enable field is ignored: the constraint
     * comes from `PageRepository::getDefaultConstraints()`, which `ContentObjectRenderer::getRecords()` uses, and which
     * keeps `t3ver_state <= 0 AND t3ver_wsid = 0` in the live workspace. Here the visibility aspect includes hidden
     * records, hidden pages and scheduled records. Of the blogs on the storage page 17 the core returns the live blog
     * (50) and the hidden live blog (51), but not blog 52, which exists only in workspace 1 (`t3ver_state` 1).
     *
     * @see ignoringAllEnableFieldsDoesNotReturnAWorkspaceOnlyRecordInLiveByExtbase()
     */
    #[Test]
    public function ignoringAllEnableFieldsDoesNotReturnAWorkspaceOnlyRecordInLiveByCore(): void
    {
        // Given: blog 52 exists only in workspace 1, next to a live blog and a hidden live blog, and the live workspace
        // is selected
        $this->importCSVDataSet(__DIR__ . '/Fixtures/WorkspaceRead/WorkspaceOnlyBlog.csv');
        $this->get(Context::class)->setAspect('workspace', new WorkspaceAspect(0));

        // When: the blogs of the page are read with the enable fields of hidden records and scheduled records ignored
        $this->get(Context::class)->setAspect('visibility', new VisibilityAspect(true, true, false, true));
        $contentObjectRenderer = $this->get(ContentObjectRenderer::class);
        $contentObjectRenderer->setRequest($this->request);
        $rows = $contentObjectRenderer->getRecords('tx_blogexample_domain_model_blog', ['pidInList' => '17', 'orderBy' => 'uid']);
        $blogs = array_map(static fn(array $row): array => [(int)$row['uid'], (int)$row['pid'], $row['title']], $rows);

        // Then: both live blogs are returned and the blog of the workspace is not
        self::assertSame([[50, 17, 'Live blog'], [51, 17, 'Hidden live blog']], $blogs);
    }

    /**
     * With `setIgnoreEnableFields(true)` Extbase returns three blogs of the storage page 17: the live blog (50), the
     * hidden live blog (51) and blog 52, which exists only in workspace 1 (`t3ver_state` 1). The core returns only the
     * two live blogs: Extbase ignores all enable fields, including the constraint on `t3ver_wsid`, and keeps only
     * `deleted = 0` and `t3ver_oid = 0`.
     *
     * @see ignoringAllEnableFieldsDoesNotReturnAWorkspaceOnlyRecordInLiveByCore()
     */
    #[Test]
    public function ignoringAllEnableFieldsDoesNotReturnAWorkspaceOnlyRecordInLiveByExtbase(): void
    {
        self::markTestSkipped('Extbase differs from core: it returns blog 52, which exists only in workspace 1, where the core returns only the two live blogs.');

        // Given: blog 52 exists only in workspace 1, next to a live blog and a hidden live blog, and the live workspace
        // is selected
        // @phpstan-ignore deadCode.unreachable
        $this->importCSVDataSet(__DIR__ . '/Fixtures/WorkspaceRead/WorkspaceOnlyBlog.csv');
        $this->get(Context::class)->setAspect('workspace', new WorkspaceAspect(0));

        // When: the blogs of the page are read with the enable fields ignored
        $query = $this->get(BlogRepository::class)->createQuery();
        $query->getQuerySettings()->setStoragePageIds([17]);
        $query->getQuerySettings()->setIgnoreEnableFields(true);
        $query->setOrderings(['uid' => QueryInterface::ORDER_ASCENDING]);
        $blogs = array_map(static fn(Blog $blog): array => [$blog->getUid(), $blog->getPid(), $blog->getTitle()], $query->execute()->toArray());

        // Then: both live blogs are returned and the blog of the workspace is not
        self::assertSame([[50, 17, 'Live blog'], [51, 17, 'Hidden live blog']], $blogs);
    }
}
