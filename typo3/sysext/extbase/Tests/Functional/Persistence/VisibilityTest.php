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
use TYPO3\CMS\Core\Context\DateTimeAspect;
use TYPO3\CMS\Core\Context\LanguageAspect;
use TYPO3\CMS\Core\Context\UserAspect;
use TYPO3\CMS\Core\Context\VisibilityAspect;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\EndTimeRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\FrontendGroupRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\FrontendRestrictionContainer;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\StartTimeRestriction;
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
use TYPO3Tests\BlogExample\Domain\Model\Post;
use TYPO3Tests\BlogExample\Domain\Repository\PostRepository;

/**
 * Visibility: which records a query returns when they are hidden, not yet started, already ended, restricted to a user
 * group or deleted, and how this applies to the records of a relation and to translations.
 *
 * Every scenario is tested twice against the same expectation: once with what the core shows in the frontend as oracle
 * (suffix "ByCore"), once with Extbase (suffix "ByExtbase"). Both sides run in a frontend request with the same
 * context: the language aspect, the date aspect fixed at the time 1700000000 and the frontend user aspect with the
 * groups of the visitor. The core side is the `CONTENT` cObject, called through ContentObjectRenderer::getRecords()
 * with `pidInList`, which applies the FrontendRestrictionContainer and overlays the rows with the language aspect.
 * Where `CONTENT` has no counterpart (joins over a relation table, a query without the enable field restrictions or
 * with soft-deleted records) it reads with a QueryBuilder that has the FrontendRestrictionContainer, from which the
 * restrictions in question are removed. Both sides return the titles of the posts ordered by uid. A test of Extbase
 * that differs from core is skipped.
 *
 * All scenarios read the posts of one storage page, which Fixtures/Visibility/Posts.csv fills: the page 2 for hidden
 * posts, 3 for the `starttime`, 4 for the `endtime`, 5 for user groups, 6 for translations, 7 for relations, 8 and 12
 * for ignored enable fields and 13 for deleted posts. The posts of `tx_blogexample_domain_model_post` have the enable
 * fields `hidden`, `starttime`, `endtime` and `fe_group`, a relation to tags (MM) and the inline children comments.
 * Tags and comments have the enable field `hidden`.
 *
 * The scenarios on hidden storage pages read the posts of the pages 9 to 11
 * (Fixtures/Visibility/HiddenStoragePages.csv): page 9 is hidden, page 10 is hidden and extends to its subpages, page
 * 11 is a visible subpage of page 10. Their Extbase side takes the storage pages from the plugin configuration
 * (`storagePid` and `recursive`).
 */
final class VisibilityTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3/sysext/extbase/Tests/Functional/Fixtures/Extensions/blog_example',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/Visibility/Posts.csv');
        $frontendTypoScript = new FrontendTypoScript(new RootNode(), [], [], []);
        $frontendTypoScript->setSetupArray([]);
        $pageInformation = new PageInformation();
        $pageInformation->setId(1);
        $pageInformation->setContentFromPid(1);
        $request = new ServerRequest()
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE)
            ->withAttribute('frontend.typoscript', $frontendTypoScript)
            ->withAttribute('frontend.page.information', $pageInformation)
            ->withAttribute('frontend.cache.collector', new CacheDataCollector())
            ->withAttribute('frontend.page.parts', new PageParts());
        $GLOBALS['TYPO3_REQUEST'] = $request;
        $this->get(ConfigurationManager::class)->setConfiguration([
            'persistence' => ['storagePid' => 2],
            'extensionName' => 'blog_example',
            'pluginName' => 'test',
        ]);
        $this->get(ConfigurationManagerInterface::class)->setRequest($request);
        $this->get(Context::class)->setAspect('date', new DateTimeAspect(new \DateTimeImmutable('@1700000000')));
    }

    /**
     * The core does not show hidden records in the frontend. Of the posts on the storage page 2 it returns "Visible
     * post" (1) and not "Hidden post" (2).
     *
     * @see hiddenPostsAreNotReadByExtbase()
     */
    #[Test]
    public function hiddenPostsAreNotReadByCore(): void
    {
        // When: the posts of the page with hidden posts are read
        $contentObjectRenderer = $this->get(ContentObjectRenderer::class);
        $contentObjectRenderer->setRequest($GLOBALS['TYPO3_REQUEST']);
        $rows = $contentObjectRenderer->getRecords('tx_blogexample_domain_model_post', ['pidInList' => '2', 'orderBy' => 'uid']);

        // Then: the hidden post is not returned
        self::assertSame(['Visible post'], array_column($rows, 'title'));
    }

    /**
     * Extbase returns "Visible post" (1) of the storage page 2 and not "Hidden post" (2), like the core.
     *
     * @see hiddenPostsAreNotReadByCore()
     */
    #[Test]
    public function hiddenPostsAreNotReadByExtbase(): void
    {
        // When: the posts of the page with hidden posts are read
        $query = $this->get(PostRepository::class)->createQuery();
        $query->getQuerySettings()->setStoragePageIds([2]);
        $query->setOrderings(['uid' => QueryInterface::ORDER_ASCENDING]);
        $posts = array_map(static fn(Post $post): string => $post->getTitle(), $query->execute()->toArray());

        // Then: the hidden post is not returned
        self::assertSame(['Visible post'], $posts);
    }

    /**
     * At the time 1700000000 the core does not show records whose `starttime` lies in the future. Of the posts on the
     * storage page 3 it returns "Started post" (3), whose `starttime` is in the past, and "Post without starttime" (5),
     * but not "Scheduled post" (4), which starts later.
     *
     * @see postsBeforeTheirStarttimeAreNotReadByExtbase()
     */
    #[Test]
    public function postsBeforeTheirStarttimeAreNotReadByCore(): void
    {
        // When: the posts of the page with starttimes are read at the time 1700000000
        $contentObjectRenderer = $this->get(ContentObjectRenderer::class);
        $contentObjectRenderer->setRequest($GLOBALS['TYPO3_REQUEST']);
        $rows = $contentObjectRenderer->getRecords('tx_blogexample_domain_model_post', ['pidInList' => '3', 'orderBy' => 'uid']);

        // Then: the post that starts later is not returned
        self::assertSame(['Started post', 'Post without starttime'], array_column($rows, 'title'));
    }

    /**
     * Extbase returns "Started post" (3) and "Post without starttime" (5) and not "Scheduled post" (4), which starts
     * later, like the core.
     *
     * @see postsBeforeTheirStarttimeAreNotReadByCore()
     */
    #[Test]
    public function postsBeforeTheirStarttimeAreNotReadByExtbase(): void
    {
        // When: the posts of the page with starttimes are read at the time 1700000000
        $query = $this->get(PostRepository::class)->createQuery();
        $query->getQuerySettings()->setStoragePageIds([3]);
        $query->setOrderings(['uid' => QueryInterface::ORDER_ASCENDING]);
        $posts = array_map(static fn(Post $post): string => $post->getTitle(), $query->execute()->toArray());

        // Then: the post that starts later is not returned
        self::assertSame(['Started post', 'Post without starttime'], $posts);
    }

    /**
     * At the time 1700000000 the core does not show records whose `endtime` lies in the past. Of the posts on the
     * storage page 4 it returns "Running post" (6), which ends later, and "Post without endtime" (8), but not "Ended
     * post" (7).
     *
     * @see postsAfterTheirEndtimeAreNotReadByExtbase()
     */
    #[Test]
    public function postsAfterTheirEndtimeAreNotReadByCore(): void
    {
        // When: the posts of the page with endtimes are read at the time 1700000000
        $contentObjectRenderer = $this->get(ContentObjectRenderer::class);
        $contentObjectRenderer->setRequest($GLOBALS['TYPO3_REQUEST']);
        $rows = $contentObjectRenderer->getRecords('tx_blogexample_domain_model_post', ['pidInList' => '4', 'orderBy' => 'uid']);

        // Then: the post that has ended is not returned
        self::assertSame(['Running post', 'Post without endtime'], array_column($rows, 'title'));
    }

    /**
     * Extbase returns "Running post" (6), which ends later, and "Post without endtime" (8) and not "Ended post" (7),
     * like the core.
     *
     * @see postsAfterTheirEndtimeAreNotReadByCore()
     */
    #[Test]
    public function postsAfterTheirEndtimeAreNotReadByExtbase(): void
    {
        // When: the posts of the page with endtimes are read at the time 1700000000
        $query = $this->get(PostRepository::class)->createQuery();
        $query->getQuerySettings()->setStoragePageIds([4]);
        $query->setOrderings(['uid' => QueryInterface::ORDER_ASCENDING]);
        $posts = array_map(static fn(Post $post): string => $post->getTitle(), $query->execute()->toArray());

        // Then: the post that has ended is not returned
        self::assertSame(['Running post', 'Post without endtime'], $posts);
    }

    /**
     * A visitor who is a member of the user group 1 gets the records without restriction and the records for group 1
     * from the core. Of the posts on the storage page 5 it returns "Public post" (9), "Group one post" (10) and "Group
     * one and two post" (12), but not "Group two post" (11), which is restricted to group 2.
     *
     * @see postsForAnotherUserGroupAreNotReadByExtbase()
     */
    #[Test]
    public function postsForAnotherUserGroupAreNotReadByCore(): void
    {
        // Given: the visitor is a member of the user group 1
        $this->get(Context::class)->setAspect('frontend.user', new UserAspect(null, [0, -2, 1]));

        // When: the posts of the page with user groups are read
        $contentObjectRenderer = $this->get(ContentObjectRenderer::class);
        $contentObjectRenderer->setRequest($GLOBALS['TYPO3_REQUEST']);
        $rows = $contentObjectRenderer->getRecords('tx_blogexample_domain_model_post', ['pidInList' => '5', 'orderBy' => 'uid']);

        // Then: the post for group 2 only is not returned
        self::assertSame(['Public post', 'Group one post', 'Group one and two post'], array_column($rows, 'title'));
    }

    /**
     * Extbase returns "Public post" (9), "Group one post" (10) and "Group one and two post" (12) to a member of group
     * 1, and not "Group two post" (11), like the core.
     *
     * @see postsForAnotherUserGroupAreNotReadByCore()
     */
    #[Test]
    public function postsForAnotherUserGroupAreNotReadByExtbase(): void
    {
        // Given: the visitor is a member of the user group 1
        $this->get(Context::class)->setAspect('frontend.user', new UserAspect(null, [0, -2, 1]));

        // When: the posts of the page with user groups are read
        $query = $this->get(PostRepository::class)->createQuery();
        $query->getQuerySettings()->setStoragePageIds([5]);
        $query->setOrderings(['uid' => QueryInterface::ORDER_ASCENDING]);
        $posts = array_map(static fn(Post $post): string => $post->getTitle(), $query->execute()->toArray());

        // Then: the post for group 2 only is not returned
        self::assertSame(['Public post', 'Group one post', 'Group one and two post'], $posts);
    }

    /**
     * Read with German and overlays on, the core shows a record only if its translation is visible. Post 13 has a
     * hidden German translation (14), so it is dropped from the result: the overlay finds no translation. Post 15 has a
     * visible German translation (16) and is returned with its German title "Sichtbare Übersetzung".
     *
     * @see aHiddenTranslationHidesThePostWithOverlaysOnByExtbase()
     */
    #[Test]
    public function aHiddenTranslationHidesThePostWithOverlaysOnByCore(): void
    {
        // Given: the language is German and overlays are on
        $this->get(Context::class)->setAspect('language', new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_ON));

        // When: the posts of the page with translations are read
        $contentObjectRenderer = $this->get(ContentObjectRenderer::class);
        $contentObjectRenderer->setRequest($GLOBALS['TYPO3_REQUEST']);
        $rows = $contentObjectRenderer->getRecords('tx_blogexample_domain_model_post', ['pidInList' => '6', 'orderBy' => 'uid']);

        // Then: only the post with the visible translation is returned
        self::assertSame(['Sichtbare Übersetzung'], array_column($rows, 'title'));
    }

    /**
     * Read with German and overlays on, Extbase returns only post 15 with its German title "Sichtbare Übersetzung",
     * like the core. Post 13 is dropped because its German translation (14) is hidden.
     *
     * @see aHiddenTranslationHidesThePostWithOverlaysOnByCore()
     */
    #[Test]
    public function aHiddenTranslationHidesThePostWithOverlaysOnByExtbase(): void
    {
        // Given: the language is German and overlays are on
        $this->get(Context::class)->setAspect('language', new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_ON));

        // When: the posts of the page with translations are read
        $query = $this->get(PostRepository::class)->createQuery();
        $query->getQuerySettings()->setStoragePageIds([6]);
        $query->setOrderings(['uid' => QueryInterface::ORDER_ASCENDING]);
        $posts = array_map(static fn(Post $post): string => $post->getTitle(), $query->execute()->toArray());

        // Then: only the post with the visible translation is returned
        self::assertSame(['Sichtbare Übersetzung'], $posts);
    }

    /**
     * Read with German and overlays mixed, the core keeps a record in the default language if its translation is not
     * visible. Post 13, whose German translation (14) is hidden, is returned with its English title "Post with hidden
     * translation"; post 15 is returned with its German title "Sichtbare Übersetzung".
     *
     * @see aHiddenTranslationKeepsTheDefaultTitleWithOverlaysMixedByExtbase()
     */
    #[Test]
    public function aHiddenTranslationKeepsTheDefaultTitleWithOverlaysMixedByCore(): void
    {
        // Given: the language is German and overlays are mixed
        $this->get(Context::class)->setAspect('language', new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_MIXED));

        // When: the posts of the page with translations are read
        $contentObjectRenderer = $this->get(ContentObjectRenderer::class);
        $contentObjectRenderer->setRequest($GLOBALS['TYPO3_REQUEST']);
        $rows = $contentObjectRenderer->getRecords('tx_blogexample_domain_model_post', ['pidInList' => '6', 'orderBy' => 'uid']);

        // Then: the post with the hidden translation keeps its English title
        self::assertSame(['Post with hidden translation', 'Sichtbare Übersetzung'], array_column($rows, 'title'));
    }

    /**
     * Read with German and overlays mixed, Extbase returns post 13 with its English title "Post with hidden
     * translation" and post 15 with its German title "Sichtbare Übersetzung", like the core.
     *
     * @see aHiddenTranslationKeepsTheDefaultTitleWithOverlaysMixedByCore()
     */
    #[Test]
    public function aHiddenTranslationKeepsTheDefaultTitleWithOverlaysMixedByExtbase(): void
    {
        // Given: the language is German and overlays are mixed
        $this->get(Context::class)->setAspect('language', new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_MIXED));

        // When: the posts of the page with translations are read
        $query = $this->get(PostRepository::class)->createQuery();
        $query->getQuerySettings()->setStoragePageIds([6]);
        $query->setOrderings(['uid' => QueryInterface::ORDER_ASCENDING]);
        $posts = array_map(static fn(Post $post): string => $post->getTitle(), $query->execute()->toArray());

        // Then: the post with the hidden translation keeps its English title
        self::assertSame(['Post with hidden translation', 'Sichtbare Übersetzung'], $posts);
    }

    /**
     * The core shows hidden records when the visibility aspect of the context includes hidden content, as in the
     * preview of the frontend. Both the select and the overlay read this aspect, so the hidden German translation is
     * found too. Read with German and overlays on, the core returns post 13 with its hidden German translation (14) as
     * "Versteckte Übersetzung" and post 15 as "Sichtbare Übersetzung".
     *
     * @see ignoringTheHiddenFlagAlsoAppliesToTheTranslationByExtbase()
     */
    #[Test]
    public function ignoringTheHiddenFlagAlsoAppliesToTheTranslationByCore(): void
    {
        // Given: the visibility aspect includes hidden content, the language is German and overlays are on
        $this->get(Context::class)->setAspect('visibility', new VisibilityAspect(false, true));
        $this->get(Context::class)->setAspect('language', new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_ON));

        // When: the posts of the page with translations are read
        $contentObjectRenderer = $this->get(ContentObjectRenderer::class);
        $contentObjectRenderer->setRequest($GLOBALS['TYPO3_REQUEST']);
        $rows = $contentObjectRenderer->getRecords('tx_blogexample_domain_model_post', ['pidInList' => '6', 'orderBy' => 'uid']);

        // Then: the hidden translation is used for the post
        self::assertSame(['Versteckte Übersetzung', 'Sichtbare Übersetzung'], array_column($rows, 'title'));
    }

    /**
     * Read with German and overlays on and ignoring the hidden flag with `setIgnoreEnableFields()` and the enable field
     * `"disabled"`, Extbase returns post 13 with its hidden German translation (14) as "Versteckte Übersetzung" and
     * post 15 as "Sichtbare Übersetzung", like the core: the ignored enable field also applies to the lookup of the
     * translation.
     *
     * @see ignoringTheHiddenFlagAlsoAppliesToTheTranslationByCore()
     */
    #[Test]
    public function ignoringTheHiddenFlagAlsoAppliesToTheTranslationByExtbase(): void
    {
        // Given: the language is German and overlays are on
        $this->get(Context::class)->setAspect('language', new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_ON));

        // When: the posts of the page with translations are read, ignoring the hidden flag
        $query = $this->get(PostRepository::class)->createQuery();
        $query->getQuerySettings()->setStoragePageIds([6]);
        $query->getQuerySettings()->setIgnoreEnableFields(true)->setEnableFieldsToBeIgnored(['disabled']);
        $query->setOrderings(['uid' => QueryInterface::ORDER_ASCENDING]);
        $posts = array_map(static fn(Post $post): string => $post->getTitle(), $query->execute()->toArray());

        // Then: the hidden translation is used for the post
        self::assertSame(['Versteckte Übersetzung', 'Sichtbare Übersetzung'], $posts);
    }

    /**
     * Hidden records of an MM relation are not shown in the frontend. The core joins the relation table to the tag
     * table with the FrontendRestrictionContainer, which restricts the tag table, and reads the tags of post 17 in the
     * order of the relation: it returns "Visible tag" (1) and not "Hidden tag" (2).
     *
     * @see hiddenTagsOfAPostAreNotReadByExtbase()
     */
    #[Test]
    public function hiddenTagsOfAPostAreNotReadByCore(): void
    {
        // When: the tags of post 17 are read
        $queryBuilder = $this->get(ConnectionPool::class)->getQueryBuilderForTable('tx_blogexample_domain_model_tag');
        $queryBuilder->setRestrictions(new FrontendRestrictionContainer());
        $rows = $queryBuilder
            ->select('tag.name')
            ->from('tx_blogexample_domain_model_tag_mm', 'mm')
            ->join('mm', 'tx_blogexample_domain_model_tag', 'tag', $queryBuilder->expr()->eq('tag.uid', $queryBuilder->quoteIdentifier('mm.uid_local')))
            ->where(
                $queryBuilder->expr()->eq('mm.uid_foreign', $queryBuilder->createNamedParameter(17, Connection::PARAM_INT)),
                $queryBuilder->expr()->eq('mm.fieldname', $queryBuilder->createNamedParameter('tags')),
            )
            ->orderBy('mm.sorting_foreign')
            ->executeQuery()
            ->fetchAllAssociative();

        // Then: the hidden tag is not returned
        self::assertSame(['Visible tag'], array_column($rows, 'name'));
    }

    /**
     * Extbase returns "Visible tag" (1) of post 17 and not "Hidden tag" (2), like the core.
     *
     * @see hiddenTagsOfAPostAreNotReadByCore()
     */
    #[Test]
    public function hiddenTagsOfAPostAreNotReadByExtbase(): void
    {
        // When: the tags of post 17 are read
        $post = $this->get(PostRepository::class)->findByUid(17);
        $tags = array_map(static fn($tag): string => $tag->getName(), $post->getTags()->toArray());

        // Then: the hidden tag is not returned
        self::assertSame(['Visible tag'], $tags);
    }

    /**
     * Hidden inline children are not shown in the frontend. The core reads the comments of post 17 from the comment
     * table by the field `"post"` that points to the parent, with the FrontendRestrictionContainer: it returns "Visible
     * comment" (1) and not "Hidden comment" (2).
     *
     * @see hiddenCommentsOfAPostAreNotReadByExtbase()
     */
    #[Test]
    public function hiddenCommentsOfAPostAreNotReadByCore(): void
    {
        // When: the comments of post 17 are read
        $queryBuilder = $this->get(ConnectionPool::class)->getQueryBuilderForTable('tx_blogexample_domain_model_comment');
        $queryBuilder->setRestrictions(new FrontendRestrictionContainer());
        $rows = $queryBuilder
            ->select('content')
            ->from('tx_blogexample_domain_model_comment')
            ->where($queryBuilder->expr()->eq('post', $queryBuilder->createNamedParameter(17, Connection::PARAM_INT)))
            ->orderBy('uid')
            ->executeQuery()
            ->fetchAllAssociative();

        // Then: the hidden comment is not returned
        self::assertSame(['Visible comment'], array_column($rows, 'content'));
    }

    /**
     * Extbase returns "Visible comment" (1) of post 17 and not "Hidden comment" (2), like the core.
     *
     * @see hiddenCommentsOfAPostAreNotReadByCore()
     */
    #[Test]
    public function hiddenCommentsOfAPostAreNotReadByExtbase(): void
    {
        // When: the comments of post 17 are read
        $post = $this->get(PostRepository::class)->findByUid(17);
        $comments = array_map(static fn($comment): string => $comment->getContent(), $post->getComments()->toArray());

        // Then: the hidden comment is not returned
        self::assertSame(['Visible comment'], $comments);
    }

    /**
     * Without the `hidden` restriction the core still applies the restriction for user groups. With the visibility
     * aspect including hidden content it returns "Public post" (18) and "Hidden public post" (19) from the storage page
     * 8 to a visitor without user group, but not "Post for group one" (21) and not "Hidden post for group one" (20).
     *
     * @see ignoringTheHiddenFlagKeepsTheUserGroupRestrictionByExtbase()
     */
    #[Test]
    public function ignoringTheHiddenFlagKeepsTheUserGroupRestrictionByCore(): void
    {
        // Given: the visibility aspect includes hidden content
        $this->get(Context::class)->setAspect('visibility', new VisibilityAspect(false, true));

        // When: the posts of page 8 are read as a visitor without group
        $contentObjectRenderer = $this->get(ContentObjectRenderer::class);
        $contentObjectRenderer->setRequest($GLOBALS['TYPO3_REQUEST']);
        $rows = $contentObjectRenderer->getRecords('tx_blogexample_domain_model_post', ['pidInList' => '8', 'orderBy' => 'uid']);

        // Then: hidden posts are returned, posts for a user group are not
        self::assertSame(['Public post', 'Hidden public post'], array_column($rows, 'title'));
    }

    /**
     * With `ignoreEnableFields` and only the enable field `"disabled"` to be ignored, Extbase returns "Public post"
     * (18) and "Hidden public post" (19) and still drops the posts for group 1 (20, 21), like the core.
     *
     * @see ignoringTheHiddenFlagKeepsTheUserGroupRestrictionByCore()
     */
    #[Test]
    public function ignoringTheHiddenFlagKeepsTheUserGroupRestrictionByExtbase(): void
    {
        // When: the posts of page 8 are read ignoring the hidden flag as a visitor without group
        $query = $this->get(PostRepository::class)->createQuery();
        $query->getQuerySettings()->setStoragePageIds([8]);
        $query->getQuerySettings()->setIgnoreEnableFields(true)->setEnableFieldsToBeIgnored(['disabled']);
        $query->setOrderings(['uid' => QueryInterface::ORDER_ASCENDING]);
        $posts = array_map(static fn(Post $post): string => $post->getTitle(), $query->execute()->toArray());

        // Then: hidden posts are returned, posts for a user group are not
        self::assertSame(['Public post', 'Hidden public post'], $posts);
    }

    /**
     * Posts are found by the name of a tag of the MM relation. The core joins the post table over the relation table to
     * the tag table with the FrontendRestrictionContainer, which restricts both the post and the tag table, and reads
     * the posts of the storage page 7 whose tag is named "Hidden tag". The tag is hidden, so the join finds nothing and
     * the result is empty.
     *
     * @see aConstraintOnAHiddenTagFindsNoPostByExtbase()
     */
    #[Test]
    public function aConstraintOnAHiddenTagFindsNoPostByCore(): void
    {
        // When: the posts with the tag "Hidden tag" are read
        $queryBuilder = $this->get(ConnectionPool::class)->getQueryBuilderForTable('tx_blogexample_domain_model_post');
        $queryBuilder->setRestrictions(new FrontendRestrictionContainer());
        $rows = $queryBuilder
            ->select('post.title')
            ->distinct()
            ->from('tx_blogexample_domain_model_post', 'post')
            ->join('post', 'tx_blogexample_domain_model_tag_mm', 'mm', $queryBuilder->expr()->eq('mm.uid_foreign', $queryBuilder->quoteIdentifier('post.uid')))
            ->join('mm', 'tx_blogexample_domain_model_tag', 'tag', $queryBuilder->expr()->eq('tag.uid', $queryBuilder->quoteIdentifier('mm.uid_local')))
            ->where(
                $queryBuilder->expr()->eq('post.pid', $queryBuilder->createNamedParameter(7, Connection::PARAM_INT)),
                $queryBuilder->expr()->eq('tag.name', $queryBuilder->createNamedParameter('Hidden tag')),
            )
            ->orderBy('post.title')
            ->executeQuery()
            ->fetchAllAssociative();

        // Then: no post is found
        self::assertSame([], array_column($rows, 'title'));
    }

    /**
     * Extbase finds no post for a constraint on the name "Hidden tag" of the tags, like the core: the join to the tag
     * table is restricted by the enable fields and the tag is hidden.
     *
     * @see aConstraintOnAHiddenTagFindsNoPostByCore()
     */
    #[Test]
    public function aConstraintOnAHiddenTagFindsNoPostByExtbase(): void
    {
        // When: the posts with the tag "Hidden tag" are read
        $query = $this->get(PostRepository::class)->createQuery();
        $query->getQuerySettings()->setStoragePageIds([7]);
        $query->setOrderings(['uid' => QueryInterface::ORDER_ASCENDING]);
        $query->matching($query->equals('tags.name', 'Hidden tag'));
        $posts = array_map(static fn(Post $post): string => $post->getTitle(), $query->execute()->toArray());

        // Then: no post is found
        self::assertSame([], $posts);
    }

    /**
     * With the hidden restriction removed from the FrontendRestrictionContainer, which removes it for the post table
     * and the tag table, the core finds "Post with tags and comments" (17) for the tag named "Hidden tag": the tag is
     * no longer excluded from the join.
     *
     * @see aConstraintOnAHiddenTagFindsThePostWhenTheHiddenFlagIsIgnoredByExtbase()
     */
    #[Test]
    public function aConstraintOnAHiddenTagFindsThePostWhenTheHiddenFlagIsIgnoredByCore(): void
    {
        // When: the posts with the tag "Hidden tag" are read without the hidden restriction
        $queryBuilder = $this->get(ConnectionPool::class)->getQueryBuilderForTable('tx_blogexample_domain_model_post');
        $queryBuilder->setRestrictions(new FrontendRestrictionContainer());
        $queryBuilder->getRestrictions()->removeByType(HiddenRestriction::class);
        $rows = $queryBuilder
            ->select('post.title')
            ->distinct()
            ->from('tx_blogexample_domain_model_post', 'post')
            ->join('post', 'tx_blogexample_domain_model_tag_mm', 'mm', $queryBuilder->expr()->eq('mm.uid_foreign', $queryBuilder->quoteIdentifier('post.uid')))
            ->join('mm', 'tx_blogexample_domain_model_tag', 'tag', $queryBuilder->expr()->eq('tag.uid', $queryBuilder->quoteIdentifier('mm.uid_local')))
            ->where(
                $queryBuilder->expr()->eq('post.pid', $queryBuilder->createNamedParameter(7, Connection::PARAM_INT)),
                $queryBuilder->expr()->eq('tag.name', $queryBuilder->createNamedParameter('Hidden tag')),
            )
            ->orderBy('post.title')
            ->executeQuery()
            ->fetchAllAssociative();

        // Then: post 17 is found
        self::assertSame(['Post with tags and comments'], array_column($rows, 'title'));
    }

    /**
     * With `ignoreEnableFields` and only the enable field `"disabled"` to be ignored, Extbase finds "Post with tags and
     * comments" (17) for a constraint on the name "Hidden tag" of the tags, like the core: the hidden tag is not
     * excluded from the join.
     *
     * @see aConstraintOnAHiddenTagFindsThePostWhenTheHiddenFlagIsIgnoredByCore()
     */
    #[Test]
    public function aConstraintOnAHiddenTagFindsThePostWhenTheHiddenFlagIsIgnoredByExtbase(): void
    {
        // When: the posts with the tag "Hidden tag" are read ignoring the hidden flag
        $query = $this->get(PostRepository::class)->createQuery();
        $query->getQuerySettings()->setStoragePageIds([7]);
        $query->getQuerySettings()->setIgnoreEnableFields(true)->setEnableFieldsToBeIgnored(['disabled']);
        $query->setOrderings(['uid' => QueryInterface::ORDER_ASCENDING]);
        $query->matching($query->equals('tags.name', 'Hidden tag'));
        $posts = array_map(static fn(Post $post): string => $post->getTitle(), $query->execute()->toArray());

        // Then: post 17 is found
        self::assertSame(['Post with tags and comments'], $posts);
    }

    /**
     * Without the `hidden`, `starttime`, `endtime` and user group restriction, only the `deleted` restriction is left.
     * The core returns all four posts of the storage page 8 to a visitor without user group: "Public post" (18),
     * "Hidden public post" (19), "Hidden post for group one" (20) and "Post for group one" (21).
     *
     * @see ignoringAllEnableFieldsAlsoIgnoresTheUserGroupRestrictionByExtbase()
     */
    #[Test]
    public function ignoringAllEnableFieldsAlsoIgnoresTheUserGroupRestrictionByCore(): void
    {
        // When: the posts of page 8 are read without the enable field restrictions as a visitor without group
        $queryBuilder = $this->get(ConnectionPool::class)->getQueryBuilderForTable('tx_blogexample_domain_model_post');
        $queryBuilder->setRestrictions(new FrontendRestrictionContainer());
        $queryBuilder->getRestrictions()
            ->removeByType(HiddenRestriction::class)
            ->removeByType(StartTimeRestriction::class)
            ->removeByType(EndTimeRestriction::class)
            ->removeByType(FrontendGroupRestriction::class);
        $rows = $queryBuilder
            ->select('title')
            ->from('tx_blogexample_domain_model_post')
            ->where($queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter(8, Connection::PARAM_INT)))
            ->orderBy('uid')
            ->executeQuery()
            ->fetchAllAssociative();

        // Then: the posts for a user group are returned too
        self::assertSame(['Public post', 'Hidden public post', 'Hidden post for group one', 'Post for group one'], array_column($rows, 'title'));
    }

    /**
     * With `ignoreEnableFields` and an empty list of enable fields to be ignored, Extbase returns the same four posts
     * of the storage page 8 as the core to a visitor without user group: 18, 19, 20 and 21. The user group restriction
     * is dropped together with the others.
     *
     * @see ignoringAllEnableFieldsAlsoIgnoresTheUserGroupRestrictionByCore()
     */
    #[Test]
    public function ignoringAllEnableFieldsAlsoIgnoresTheUserGroupRestrictionByExtbase(): void
    {
        // When: the posts of page 8 are read ignoring all enable fields as a visitor without group
        $query = $this->get(PostRepository::class)->createQuery();
        $query->getQuerySettings()->setStoragePageIds([8]);
        $query->getQuerySettings()->setIgnoreEnableFields(true)->setEnableFieldsToBeIgnored([]);
        $query->setOrderings(['uid' => QueryInterface::ORDER_ASCENDING]);
        $posts = array_map(static fn(Post $post): string => $post->getTitle(), $query->execute()->toArray());

        // Then: the posts for a user group are returned too
        self::assertSame(['Public post', 'Hidden public post', 'Hidden post for group one', 'Post for group one'], $posts);
    }

    /**
     * The core does not show records that are restricted to a user group the visitor is not a member of. It does show
     * them to a visitor whose group list contains every group involved. Of the posts on the storage page 12 it returns
     * "Public post" (25), "Post for group one" (27), "Post for group two" (28), "Post hidden at login" (33, `fe_group`
     * -1) and "Post shown at any login" (34, `fe_group` -2) to a visitor with the groups 0, -1, -2, 1 and 2. It still
     * drops the hidden posts "Hidden public post" (26) and "Hidden post for group two" (29). The core has no setting
     * that lifts only the user group restriction. No real visitor is in -1 and -2 at once, so the `UserAspect` is
     * given that group list directly, as the closest counterpart of ignoring the restriction.
     *
     * @see ignoringOnlyTheUserGroupRestrictionStillHidesHiddenPostsByExtbase()
     */
    #[Test]
    public function ignoringOnlyTheUserGroupRestrictionStillHidesHiddenPostsByCore(): void
    {
        // Given: the visitor is a member of the user groups 1 and 2 and of the special groups -1 and -2
        $this->get(Context::class)->setAspect('frontend.user', new UserAspect(null, [0, -1, -2, 1, 2]));

        // When: the posts of the page 12 are read
        $contentObjectRenderer = $this->get(ContentObjectRenderer::class);
        $contentObjectRenderer->setRequest($GLOBALS['TYPO3_REQUEST']);
        $rows = $contentObjectRenderer->getRecords('tx_blogexample_domain_model_post', ['pidInList' => '12', 'orderBy' => 'uid']);

        // Then: the posts for a user group, including those for the groups -1 and -2, are returned, the hidden posts are not
        self::assertSame(['Public post', 'Post for group one', 'Post for group two', 'Post hidden at login', 'Post shown at any login'], array_column($rows, 'title'));
    }

    /**
     * With `ignoreEnableFields` and only the enable field `"fe_group"` to be ignored, Extbase returns "Public post"
     * (25), "Post for group one" (27), "Post for group two" (28), "Post hidden at login" (33, `fe_group` -1) and "Post
     * shown at any login" (34, `fe_group` -2) to a visitor without user group and still drops the hidden posts "Hidden
     * public post" (26) and "Hidden post for group two" (29), like the core does for a visitor with the groups 0, -1,
     * -2, 1 and 2.
     *
     * @see ignoringOnlyTheUserGroupRestrictionStillHidesHiddenPostsByCore()
     */
    #[Test]
    public function ignoringOnlyTheUserGroupRestrictionStillHidesHiddenPostsByExtbase(): void
    {
        // When: the posts of page 12 are read ignoring the user group restriction as a visitor without group
        $query = $this->get(PostRepository::class)->createQuery();
        $query->getQuerySettings()->setStoragePageIds([12]);
        $query->getQuerySettings()->setIgnoreEnableFields(true)->setEnableFieldsToBeIgnored(['fe_group']);
        $query->setOrderings(['uid' => QueryInterface::ORDER_ASCENDING]);
        $posts = array_map(static fn(Post $post): string => $post->getTitle(), $query->execute()->toArray());

        // Then: the posts for a user group, including those for the groups -1 and -2, are returned, the hidden posts are not
        self::assertSame(['Public post', 'Post for group one', 'Post for group two', 'Post hidden at login', 'Post shown at any login'], $posts);
    }

    /**
     * Without the `deleted` restriction the core returns soft-deleted records and still applies the enable fields. Of
     * the posts on the storage page 13 it returns "Visible post" (30) and "Deleted post" (31), but not "Hidden deleted
     * post" (32). The select of `CONTENT` has no option for deleted records, so the core side is a QueryBuilder with
     * the FrontendRestrictionContainer without the `DeletedRestriction`.
     *
     * @see includingDeletedPostsKeepsTheEnableFieldRestrictionsByExtbase()
     */
    #[Test]
    public function includingDeletedPostsKeepsTheEnableFieldRestrictionsByCore(): void
    {
        // When: the posts of page 13 are read without the deleted restriction
        $queryBuilder = $this->get(ConnectionPool::class)->getQueryBuilderForTable('tx_blogexample_domain_model_post');
        $queryBuilder->setRestrictions(new FrontendRestrictionContainer());
        $queryBuilder->getRestrictions()->removeByType(DeletedRestriction::class);
        $rows = $queryBuilder
            ->select('title')
            ->from('tx_blogexample_domain_model_post')
            ->where($queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter(13, Connection::PARAM_INT)))
            ->orderBy('uid')
            ->executeQuery()
            ->fetchAllAssociative();

        // Then: the deleted post is returned, the hidden deleted post is not
        self::assertSame(['Visible post', 'Deleted post'], array_column($rows, 'title'));
    }

    /**
     * Extbase does not accept `setIncludeDeleted(true)` without `setIgnoreEnableFields(true)` in a frontend request: it
     * throws an InconsistentQuerySettingsException, where the core returns "Visible post" (30) and "Deleted post" (31)
     * of the storage page 13 and not "Hidden deleted post" (32).
     *
     * @see includingDeletedPostsKeepsTheEnableFieldRestrictionsByCore()
     */
    #[Test]
    public function includingDeletedPostsKeepsTheEnableFieldRestrictionsByExtbase(): void
    {
        self::markTestSkipped('Extbase differs from core: it throws an InconsistentQuerySettingsException for includeDeleted without ignoreEnableFields in the frontend, the core returns the deleted post.');

        // When: the posts of page 13 are read including deleted posts
        // @phpstan-ignore deadCode.unreachable
        $query = $this->get(PostRepository::class)->createQuery();
        $query->getQuerySettings()->setStoragePageIds([13]);
        $query->getQuerySettings()->setIncludeDeleted(true);
        $query->setOrderings(['uid' => QueryInterface::ORDER_ASCENDING]);
        $posts = array_map(static fn(Post $post): string => $post->getTitle(), $query->execute()->toArray());

        // Then: the deleted post is returned, the hidden deleted post is not
        self::assertSame(['Visible post', 'Deleted post'], $posts);
    }

    /**
     * Without the `deleted`, `hidden`, `starttime`, `endtime` and user group restriction the core returns every record
     * of a page. Of the posts on the storage page 13 it returns "Visible post" (30), "Deleted post" (31) and "Hidden
     * deleted post" (32). The select of `CONTENT` has no option for deleted records, so the core side is a QueryBuilder
     * with the FrontendRestrictionContainer from which these restrictions are removed.
     *
     * @see includingDeletedPostsWithIgnoredEnableFieldsReturnsHiddenDeletedPostsByExtbase()
     */
    #[Test]
    public function includingDeletedPostsWithIgnoredEnableFieldsReturnsHiddenDeletedPostsByCore(): void
    {
        // When: the posts of page 13 are read without the deleted restriction and the enable field restrictions
        $queryBuilder = $this->get(ConnectionPool::class)->getQueryBuilderForTable('tx_blogexample_domain_model_post');
        $queryBuilder->setRestrictions(new FrontendRestrictionContainer());
        $queryBuilder->getRestrictions()
            ->removeByType(DeletedRestriction::class)
            ->removeByType(HiddenRestriction::class)
            ->removeByType(StartTimeRestriction::class)
            ->removeByType(EndTimeRestriction::class)
            ->removeByType(FrontendGroupRestriction::class);
        $rows = $queryBuilder
            ->select('title')
            ->from('tx_blogexample_domain_model_post')
            ->where($queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter(13, Connection::PARAM_INT)))
            ->orderBy('uid')
            ->executeQuery()
            ->fetchAllAssociative();

        // Then: the deleted posts are returned, hidden or not
        self::assertSame(['Visible post', 'Deleted post', 'Hidden deleted post'], array_column($rows, 'title'));
    }

    /**
     * With `setIncludeDeleted(true)` and `setIgnoreEnableFields(true)` Extbase returns "Visible post" (30), "Deleted
     * post" (31) and "Hidden deleted post" (32) of the storage page 13, like the core.
     *
     * @see includingDeletedPostsWithIgnoredEnableFieldsReturnsHiddenDeletedPostsByCore()
     */
    #[Test]
    public function includingDeletedPostsWithIgnoredEnableFieldsReturnsHiddenDeletedPostsByExtbase(): void
    {
        // When: the posts of page 13 are read including deleted posts and ignoring the enable fields
        $query = $this->get(PostRepository::class)->createQuery();
        $query->getQuerySettings()->setStoragePageIds([13]);
        $query->getQuerySettings()->setIncludeDeleted(true)->setIgnoreEnableFields(true);
        $query->setOrderings(['uid' => QueryInterface::ORDER_ASCENDING]);
        $posts = array_map(static fn(Post $post): string => $post->getTitle(), $query->execute()->toArray());

        // Then: the deleted posts are returned, hidden or not
        self::assertSame(['Visible post', 'Deleted post', 'Hidden deleted post'], $posts);
    }

    /**
     * The core does not show records of a page that is hidden: the select of the `CONTENT` object removes hidden pages
     * from the pages it reads from. Post 22 is on the hidden storage page 9, so the core returns no post.
     *
     * @see postsOnAHiddenStoragePageAreNotReadByExtbase()
     */
    #[Test]
    public function postsOnAHiddenStoragePageAreNotReadByCore(): void
    {
        // Given: the hidden page 9 with post 22
        $this->importCSVDataSet(__DIR__ . '/Fixtures/Visibility/HiddenStoragePages.csv');

        // When: the posts of the hidden page are read
        $contentObjectRenderer = $this->get(ContentObjectRenderer::class);
        $contentObjectRenderer->setRequest($GLOBALS['TYPO3_REQUEST']);
        $rows = $contentObjectRenderer->getRecords('tx_blogexample_domain_model_post', ['pidInList' => '9', 'recursive' => 0, 'orderBy' => 'uid']);

        // Then: no post is returned
        self::assertSame([], array_column($rows, 'title'));
    }

    /**
     * Extbase does not check the storage page: it returns post 22 "Post on hidden page" of the hidden storage page 9,
     * where the core returns no post.
     *
     * @see postsOnAHiddenStoragePageAreNotReadByCore()
     */
    #[Test]
    public function postsOnAHiddenStoragePageAreNotReadByExtbase(): void
    {
        self::markTestSkipped('Extbase differs from core: it returns the post of the hidden storage page, the core returns none, see Forge #97323.');

        // Given: the hidden page 9 with post 22
        // @phpstan-ignore deadCode.unreachable
        $this->importCSVDataSet(__DIR__ . '/Fixtures/Visibility/HiddenStoragePages.csv');

        // When: the posts of the hidden page are read
        $this->get(ConfigurationManager::class)->setConfiguration([
            'persistence' => ['storagePid' => '9', 'recursive' => 0],
            'extensionName' => 'blog_example',
            'pluginName' => 'test',
        ]);
        $query = $this->get(PostRepository::class)->createQuery();
        $query->setOrderings(['uid' => QueryInterface::ORDER_ASCENDING]);
        $posts = array_map(static fn(Post $post): string => $post->getTitle(), $query->execute()->toArray());

        // Then: no post is returned
        self::assertSame([], $posts);
    }

    /**
     * Page 10 is hidden, its subpage 11 is not. Read from page 10 with one level of subpages, the core keeps only the
     * pages that are not hidden, so it returns post 24 "Post below hidden parent" of the visible subpage and not post
     * 23 of the hidden page 10.
     *
     * @see postsBelowAHiddenPageAreReadByExtbase()
     */
    #[Test]
    public function postsBelowAHiddenPageAreReadByCore(): void
    {
        // Given: the hidden page 10 with post 23 and its visible subpage 11 with post 24
        $this->importCSVDataSet(__DIR__ . '/Fixtures/Visibility/HiddenStoragePages.csv');

        // When: the posts are read from page 10 and its subpages
        $contentObjectRenderer = $this->get(ContentObjectRenderer::class);
        $contentObjectRenderer->setRequest($GLOBALS['TYPO3_REQUEST']);
        $rows = $contentObjectRenderer->getRecords('tx_blogexample_domain_model_post', ['pidInList' => '10', 'recursive' => 1, 'orderBy' => 'uid']);

        // Then: only the post of the visible subpage is returned
        self::assertSame(['Post below hidden parent'], array_column($rows, 'title'));
    }

    /**
     * Read with the storage page 10 and one level of subpages, Extbase returns post 23 "Post on hidden parent" of the
     * hidden page 10 and post 24 "Post below hidden parent" of the visible subpage 11. It adds the subpages to the
     * storage pages but does not remove the hidden page 10 itself, where the core returns only post 24.
     *
     * @see postsBelowAHiddenPageAreReadByCore()
     */
    #[Test]
    public function postsBelowAHiddenPageAreReadByExtbase(): void
    {
        self::markTestSkipped('Extbase differs from core: it also returns the post of the hidden storage page 10 (23), the core returns only the post of its visible subpage (24), see Forge #97323.');

        // Given: the hidden page 10 with post 23 and its visible subpage 11 with post 24
        // @phpstan-ignore deadCode.unreachable
        $this->importCSVDataSet(__DIR__ . '/Fixtures/Visibility/HiddenStoragePages.csv');

        // When: the posts are read from page 10 and its subpages
        $this->get(ConfigurationManager::class)->setConfiguration([
            'persistence' => ['storagePid' => '10', 'recursive' => 1],
            'extensionName' => 'blog_example',
            'pluginName' => 'test',
        ]);
        $query = $this->get(PostRepository::class)->createQuery();
        $query->setOrderings(['uid' => QueryInterface::ORDER_ASCENDING]);
        $posts = array_map(static fn(Post $post): string => $post->getTitle(), $query->execute()->toArray());

        // Then: only the post of the visible subpage is returned
        self::assertSame(['Post below hidden parent'], $posts);
    }
}
