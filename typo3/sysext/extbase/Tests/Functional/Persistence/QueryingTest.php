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

use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Cache\CacheDataCollector;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\LanguageAspect;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\TypoScript\AST\Node\RootNode;
use TYPO3\CMS\Core\TypoScript\FrontendTypoScript;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Page\PageInformation;
use TYPO3\CMS\Frontend\Page\PageParts;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Tests\BlogExample\Domain\Model\Comment;
use TYPO3Tests\BlogExample\Domain\Model\Person;
use TYPO3Tests\BlogExample\Domain\Model\Post;
use TYPO3Tests\BlogExample\Domain\Repository\PersonRepository;
use TYPO3Tests\BlogExample\Domain\Repository\PostRepository;

/**
 * Querying: how the query API of Extbase selects, orders, counts and limits records compared with the TypoScript
 * object CONTENT of the core, for conditions and orderings that use a column of another table, for operators and for
 * storage pages.
 *
 * Every scenario is tested twice against the same expectation: once with the core as oracle (suffix "ByCore"), once
 * with Extbase (suffix "ByExtbase"). The core side is the configuration an integrator writes for the same question:
 * `ContentObjectRenderer::getRecords()` with the properties of the CONTENT property "select", which are given in the
 * docblock of each test as TypoScript. Both sides read the default language in a frontend request and return the
 * titles of the posts, or the last names of the persons. A test of Extbase that differs from core is skipped.
 *
 * All scenarios read the records on the storage page 2 (Extbase: `persistence.storagePid`). The post has the columns
 * `title`, `date` (stored as a timestamp), the relation `author` to one person (select), the relation `tags` to several
 * tags (select with a relation table, MM) and the inline children `comments`. The person has the nullable column `age`.
 * Each scenario has its own fixture in Fixtures/Querying/Given/.
 *
 * CONTENT joins only one table. A condition on the tags therefore selects the posts by a subquery over the relation
 * table, which returns every post once, however many of the given tags it has.
 */
final class QueryingTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3/sysext/extbase/Tests/Functional/Fixtures/Extensions/blog_example',
    ];

    private ContentObjectRenderer $contentObjectRenderer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/Querying/Given/Base.csv');
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
        $this->get(Context::class)->setAspect('language', new LanguageAspect(0));
        $this->contentObjectRenderer = $this->get(ContentObjectRenderer::class);
        $this->contentObjectRenderer->setRequest($request);
    }

    /**
     * The core orders posts by a column of a related table when the `CONTENT` cObject joins that table: `select.join =
     * tx_blogexample_domain_model_person ON tx_blogexample_domain_model_person.uid =
     * tx_blogexample_domain_model_post.author` and `select.orderBy = tx_blogexample_domain_model_person.lastname`. The
     * posts First (author Zimmermann), Second (author Albrecht) and Third (author Meier) come in the order of the last
     * names, ascending: Second, Third, First.
     *
     * @see orderingPostsByTheLastNameOfTheirAuthorByExtbase()
     */
    #[Test]
    public function orderingPostsByTheLastNameOfTheirAuthorByCore(): void
    {
        // Given: three posts with the authors Zimmermann, Albrecht and Meier
        $this->importCSVDataSet(__DIR__ . '/Fixtures/Querying/Given/Authors.csv');

        // When: the posts are read ordered by the last name of the author
        $rows = $this->contentObjectRenderer->getRecords('tx_blogexample_domain_model_post', [
            'pidInList' => '2',
            'selectFields' => 'tx_blogexample_domain_model_post.*',
            'join' => 'tx_blogexample_domain_model_person ON tx_blogexample_domain_model_person.uid = tx_blogexample_domain_model_post.author',
            'orderBy' => 'tx_blogexample_domain_model_person.lastname',
        ]);
        $titles = array_column($rows, 'title');

        // Then: the posts are in the order of the last names
        self::assertSame(['Second', 'Third', 'First'], $titles);
    }

    /**
     * Extbase orders posts by a column of the related author with the ordering `"author.lastname"`, ascending. It
     * returns Second (author Albrecht), Third (author Meier) and First (author Zimmermann), like the core.
     *
     * @see orderingPostsByTheLastNameOfTheirAuthorByCore()
     */
    #[Test]
    public function orderingPostsByTheLastNameOfTheirAuthorByExtbase(): void
    {
        // Given: three posts with the authors Zimmermann, Albrecht and Meier
        $this->importCSVDataSet(__DIR__ . '/Fixtures/Querying/Given/Authors.csv');

        // When: the posts are read ordered by the last name of the author
        $query = $this->get(PostRepository::class)->createQuery();
        $query->setOrderings(['author.lastname' => QueryInterface::ORDER_ASCENDING]);
        $titles = array_map(static fn(Post $post): string => $post->getTitle(), $query->execute()->toArray());

        // Then: the posts are in the order of the last names
        self::assertSame(['Second', 'Third', 'First'], $titles);
    }

    /**
     * The core selects the posts that have a tag named red or blue, ordered by the last name of the author, with the
     * `CONTENT` cObject: `select.where` is a subquery over the relation table `tx_blogexample_domain_model_tag_mm` that
     * selects every post once, `select.join` joins the person table and `select.orderBy =
     * tx_blogexample_domain_model_person.lastname, tx_blogexample_domain_model_post.uid`. Alpha (author Becker) has
     * both tags and Epsilon (author Albrecht) has both tags too, but each is returned once. The result is Beta and
     * Epsilon (Albrecht), then Alpha and Gamma (Becker). Delta has the tag green and is not selected.
     *
     * @see findingPostsByTagNamesAndOrderingThemByTheirAuthorByExtbase()
     */
    #[Test]
    public function findingPostsByTagNamesAndOrderingThemByTheirAuthorByCore(): void
    {
        // Given: five posts, two of them with the tags red and blue, one with the tag green
        $this->importCSVDataSet(__DIR__ . '/Fixtures/Querying/Given/Tags.csv');

        // When: the posts with the tags red or blue are read ordered by the last name of the author
        $rows = $this->contentObjectRenderer->getRecords('tx_blogexample_domain_model_post', [
            'pidInList' => '2',
            'selectFields' => 'tx_blogexample_domain_model_post.*',
            'where' => 'tx_blogexample_domain_model_post.uid IN (SELECT tx_blogexample_domain_model_tag_mm.uid_foreign FROM tx_blogexample_domain_model_tag_mm'
                . ' INNER JOIN tx_blogexample_domain_model_tag ON tx_blogexample_domain_model_tag.uid = tx_blogexample_domain_model_tag_mm.uid_local'
                . " WHERE tx_blogexample_domain_model_tag_mm.tablenames = 'tx_blogexample_domain_model_post' AND tx_blogexample_domain_model_tag_mm.fieldname = 'tags'"
                . " AND tx_blogexample_domain_model_tag.name IN ('red','blue'))",
            'join' => 'tx_blogexample_domain_model_person ON tx_blogexample_domain_model_person.uid = tx_blogexample_domain_model_post.author',
            'orderBy' => 'tx_blogexample_domain_model_person.lastname, tx_blogexample_domain_model_post.uid',
        ]);
        $titles = array_column($rows, 'title');

        // Then: every post is returned once, ordered by author
        self::assertSame(['Beta', 'Epsilon', 'Alpha', 'Gamma'], $titles);
    }

    /**
     * Extbase selects the posts with the constraint `in("tags.name", [red, blue])` and the orderings
     * `"author.lastname"` and `"uid"`, ascending. It returns Beta, Epsilon, Alpha and Gamma, each post once, like the
     * core. On PostgreSQL and MySQL, Extbase throws because the ORDER BY column is not in the select list of the
     * SELECT DISTINCT, see Forge #92514.
     *
     * @see findingPostsByTagNamesAndOrderingThemByTheirAuthorByCore()
     */
    #[Test]
    public function findingPostsByTagNamesAndOrderingThemByTheirAuthorByExtbase(): void
    {
        $platform = $this->get(ConnectionPool::class)->getConnectionForTable('tx_blogexample_domain_model_post')->getDatabasePlatform();
        if ($platform instanceof PostgreSQLPlatform || $platform instanceof MySQLPlatform) {
            self::markTestSkipped('Extbase differs from core: on PostgreSQL and MySQL it throws "ORDER BY expressions must appear in select list" for SELECT DISTINCT.');
        }

        // Given: five posts, two of them with the tags red and blue, one with the tag green
        $this->importCSVDataSet(__DIR__ . '/Fixtures/Querying/Given/Tags.csv');

        // When: the posts with the tags red or blue are read ordered by the last name of the author
        $query = $this->get(PostRepository::class)->createQuery();
        $query->matching($query->in('tags.name', ['red', 'blue']));
        $query->setOrderings(['author.lastname' => QueryInterface::ORDER_ASCENDING, 'uid' => QueryInterface::ORDER_ASCENDING]);
        $titles = array_map(static fn(Post $post): string => $post->getTitle(), $query->execute()->toArray());

        // Then: every post is returned once, ordered by author
        self::assertSame(['Beta', 'Epsilon', 'Alpha', 'Gamma'], $titles);
    }

    /**
     * The core shows four posts for the tags red and blue: Alpha, Beta, Gamma and Epsilon. The `CONTENT` cObject
     * selects them with a subquery over the relation table `tx_blogexample_domain_model_tag_mm` in `select.where`, so
     * that Alpha and Epsilon, which have both tags, are not counted twice. The number of records is 4.
     *
     * @see countingPostsByTagNamesByExtbase()
     */
    #[Test]
    public function countingPostsByTagNamesByCore(): void
    {
        // Given: five posts, Alpha and Epsilon with the tags red and blue, Beta with red, Gamma with blue, Delta with green
        $this->importCSVDataSet(__DIR__ . '/Fixtures/Querying/Given/Tags.csv');

        // When: the posts with the tags red or blue are counted
        $rows = $this->contentObjectRenderer->getRecords('tx_blogexample_domain_model_post', [
            'pidInList' => '2',
            'selectFields' => 'tx_blogexample_domain_model_post.*',
            'where' => 'tx_blogexample_domain_model_post.uid IN (SELECT tx_blogexample_domain_model_tag_mm.uid_foreign FROM tx_blogexample_domain_model_tag_mm'
                . ' INNER JOIN tx_blogexample_domain_model_tag ON tx_blogexample_domain_model_tag.uid = tx_blogexample_domain_model_tag_mm.uid_local'
                . " WHERE tx_blogexample_domain_model_tag_mm.tablenames = 'tx_blogexample_domain_model_post' AND tx_blogexample_domain_model_tag_mm.fieldname = 'tags'"
                . " AND tx_blogexample_domain_model_tag.name IN ('red','blue'))",
        ]);
        $count = count($rows);

        // Then: every post is counted once
        self::assertSame(4, $count);
    }

    /**
     * Extbase counts the posts that match `in("tags.name", [red, blue])` with `count()` of the query. The result is 4,
     * like the number of posts that the core shows: Alpha and Epsilon, which have both tags, are counted once.
     *
     * @see countingPostsByTagNamesByCore()
     */
    #[Test]
    public function countingPostsByTagNamesByExtbase(): void
    {
        // Given: five posts, Alpha and Epsilon with the tags red and blue, Beta with red, Gamma with blue, Delta with green
        $this->importCSVDataSet(__DIR__ . '/Fixtures/Querying/Given/Tags.csv');

        // When: the posts with the tags red or blue are counted
        $query = $this->get(PostRepository::class)->createQuery();
        $query->matching($query->in('tags.name', ['red', 'blue']));
        $count = $query->count();

        // Then: every post is counted once
        self::assertSame(4, $count);
    }

    /**
     * The core applies the limit to posts, not to join rows, because the `CONTENT` cObject selects them with a subquery
     * in `select.where`. For the tags red and blue, ordered by uid, with `select.max = 2` and `select.begin = 1` it
     * returns the second and third matching post: Beta and Gamma.
     *
     * @see limitingPostsByTagNamesByExtbase()
     */
    #[Test]
    public function limitingPostsByTagNamesByCore(): void
    {
        // Given: five posts, Alpha and Epsilon with the tags red and blue, Beta with red, Gamma with blue, Delta with green
        $this->importCSVDataSet(__DIR__ . '/Fixtures/Querying/Given/Tags.csv');

        // When: the second and third post with the tags red or blue are read
        $rows = $this->contentObjectRenderer->getRecords('tx_blogexample_domain_model_post', [
            'pidInList' => '2',
            'selectFields' => 'tx_blogexample_domain_model_post.*',
            'where' => 'tx_blogexample_domain_model_post.uid IN (SELECT tx_blogexample_domain_model_tag_mm.uid_foreign FROM tx_blogexample_domain_model_tag_mm'
                . ' INNER JOIN tx_blogexample_domain_model_tag ON tx_blogexample_domain_model_tag.uid = tx_blogexample_domain_model_tag_mm.uid_local'
                . " WHERE tx_blogexample_domain_model_tag_mm.tablenames = 'tx_blogexample_domain_model_post' AND tx_blogexample_domain_model_tag_mm.fieldname = 'tags'"
                . " AND tx_blogexample_domain_model_tag.name IN ('red','blue'))",
            'orderBy' => 'tx_blogexample_domain_model_post.uid',
            'max' => 2,
            'begin' => 1,
        ]);
        $titles = array_column($rows, 'title');

        // Then: the posts after Alpha are returned
        self::assertSame(['Beta', 'Gamma'], $titles);
    }

    /**
     * Extbase reads the posts that match `in("tags.name", [red, blue])`, ordered by uid, with `setLimit(2)` and
     * `setOffset(1)`. It returns Beta and Gamma, like the core: Alpha, which has both tags, is one post for the offset.
     *
     * @see limitingPostsByTagNamesByCore()
     */
    #[Test]
    public function limitingPostsByTagNamesByExtbase(): void
    {
        // Given: five posts, Alpha and Epsilon with the tags red and blue, Beta with red, Gamma with blue, Delta with green
        $this->importCSVDataSet(__DIR__ . '/Fixtures/Querying/Given/Tags.csv');

        // When: the second and third post with the tags red or blue are read
        $query = $this->get(PostRepository::class)->createQuery();
        $query->matching($query->in('tags.name', ['red', 'blue']));
        $query->setOrderings(['uid' => QueryInterface::ORDER_ASCENDING]);
        $query->setLimit(2);
        $query->setOffset(1);
        $titles = array_map(static fn(Post $post): string => $post->getTitle(), $query->execute()->toArray());

        // Then: the posts after Alpha are returned
        self::assertSame(['Beta', 'Gamma'], $titles);
    }

    /**
     * With the where condition `title LIKE '100% pure'` the `CONTENT` cObject treats the percent sign inside the value
     * as a wildcard, as every SQL `LIKE` does. It finds the posts "100% pure", "100 pure" and "1000 pure": the wildcard
     * matches a percent sign, nothing and a zero. "Pure" is not found.
     *
     * @see likeTreatsAPercentSignInsideTheValueAsAWildcardByExtbase()
     */
    #[Test]
    public function likeTreatsAPercentSignInsideTheValueAsAWildcardByCore(): void
    {
        // Given: posts with the titles "100% pure", "100 pure", "1000 pure" and "Pure"
        $this->importCSVDataSet(__DIR__ . '/Fixtures/Querying/Given/Titles.csv');

        // When: the posts with a title like "100% pure" are read
        $rows = $this->contentObjectRenderer->getRecords('tx_blogexample_domain_model_post', [
            'pidInList' => '2',
            'where' => "title LIKE '100% pure'",
            'orderBy' => 'uid',
        ]);
        $titles = array_column($rows, 'title');

        // Then: the percent sign is a wildcard
        self::assertSame(['100% pure', '100 pure', '1000 pure'], $titles);
    }

    /**
     * Extbase reads the posts with the constraint `like("title", "100% pure")`. It returns "100% pure", "100 pure" and
     * "1000 pure", like the core: the percent sign inside the value is a wildcard.
     *
     * @see likeTreatsAPercentSignInsideTheValueAsAWildcardByCore()
     */
    #[Test]
    public function likeTreatsAPercentSignInsideTheValueAsAWildcardByExtbase(): void
    {
        // Given: posts with the titles "100% pure", "100 pure", "1000 pure" and "Pure"
        $this->importCSVDataSet(__DIR__ . '/Fixtures/Querying/Given/Titles.csv');

        // When: the posts with a title like "100% pure" are read
        $query = $this->get(PostRepository::class)->createQuery();
        $query->matching($query->like('title', '100% pure'));
        $query->setOrderings(['uid' => QueryInterface::ORDER_ASCENDING]);
        $titles = array_map(static fn(Post $post): string => $post->getTitle(), $query->execute()->toArray());

        // Then: the percent sign is a wildcard
        self::assertSame(['100% pure', '100 pure', '1000 pure'], $titles);
    }

    /**
     * With the where condition `age <> 20` the `CONTENT` cObject returns the persons whose age differs from 20 and is
     * not NULL: Baker (30) and Davis (0). Clark, whose age is NULL, is not returned, because the comparison with NULL
     * is not true in SQL.
     *
     * @see excludingAValueFromANullableColumnByExtbase()
     */
    #[Test]
    public function excludingAValueFromANullableColumnByCore(): void
    {
        // Given: persons with the ages 20, 30, NULL and 0
        $this->importCSVDataSet(__DIR__ . '/Fixtures/Querying/Given/Ages.csv');

        // When: the persons whose age is not 20 are read
        $rows = $this->contentObjectRenderer->getRecords('tx_blogexample_domain_model_person', [
            'pidInList' => '2',
            'where' => 'age <> 20',
            'orderBy' => 'uid',
        ]);
        $lastNames = array_column($rows, 'lastname');

        // Then: the person with the NULL age is not returned
        self::assertSame(['Baker', 'Davis'], $lastNames);
    }

    /**
     * Extbase reads the persons with the constraint `logicalNot(equals("age", 20))`. It returns Baker and Davis, like
     * the core: Clark, whose age is NULL, is not returned.
     *
     * @see excludingAValueFromANullableColumnByCore()
     */
    #[Test]
    public function excludingAValueFromANullableColumnByExtbase(): void
    {
        // Given: persons with the ages 20, 30, NULL and 0
        $this->importCSVDataSet(__DIR__ . '/Fixtures/Querying/Given/Ages.csv');

        // When: the persons whose age is not 20 are read
        $query = $this->get(PersonRepository::class)->createQuery();
        $query->matching($query->logicalNot($query->equals('age', 20)));
        $query->setOrderings(['uid' => QueryInterface::ORDER_ASCENDING]);
        $lastNames = array_map(static fn(Person $person): string => $person->getLastname(), $query->execute()->toArray());

        // Then: the person with the NULL age is not returned
        self::assertSame(['Baker', 'Davis'], $lastNames);
    }

    /**
     * The date of a post is stored as a timestamp in the column `date`. With the where condition `date >= 1700000000`
     * the `CONTENT` cObject returns the posts dated at that second or later: Boundary (1700000000) and Late
     * (1800000000). Early (1600000000) and Unset (0) are not returned.
     *
     * @see comparingTheDateOfAPostWithADateTimeByExtbase()
     */
    #[Test]
    public function comparingTheDateOfAPostWithADateTimeByCore(): void
    {
        // Given: posts dated 1600000000, 1700000000, 1800000000 and not at all (0)
        $this->importCSVDataSet(__DIR__ . '/Fixtures/Querying/Given/Published.csv');

        // When: the posts dated 1700000000 or later are read
        $rows = $this->contentObjectRenderer->getRecords('tx_blogexample_domain_model_post', [
            'pidInList' => '2',
            'where' => 'date >= 1700000000',
            'orderBy' => 'uid',
        ]);
        $titles = array_column($rows, 'title');

        // Then: the post at the boundary is included
        self::assertSame(['Boundary', 'Late'], $titles);
    }

    /**
     * Extbase reads the posts with the constraint `greaterThanOrEqual("date", new \DateTime('@1700000000'))`. It
     * returns Boundary and Late, like the core: the DateTime is turned into the timestamp 1700000000 and the post dated
     * at that second is included.
     *
     * @see comparingTheDateOfAPostWithADateTimeByCore()
     */
    #[Test]
    public function comparingTheDateOfAPostWithADateTimeByExtbase(): void
    {
        // Given: posts dated 1600000000, 1700000000, 1800000000 and not at all (0)
        $this->importCSVDataSet(__DIR__ . '/Fixtures/Querying/Given/Published.csv');

        // When: the posts dated 1700000000 or later are read
        $query = $this->get(PostRepository::class)->createQuery();
        $query->matching($query->greaterThanOrEqual('date', new \DateTime('@1700000000')));
        $query->setOrderings(['uid' => QueryInterface::ORDER_ASCENDING]);
        $titles = array_map(static fn(Post $post): string => $post->getTitle(), $query->execute()->toArray());

        // Then: the post at the boundary is included
        self::assertSame(['Boundary', 'Late'], $titles);
    }

    /**
     * The inline children of a post are the comments whose field `post` holds the uid of the post. The `CONTENT`
     * cObject finds the post that has the comment Objection (3) with `select.join = tx_blogexample_domain_model_comment
     * ON tx_blogexample_domain_model_comment.post = tx_blogexample_domain_model_post.uid` and the where condition
     * `tx_blogexample_domain_model_comment.uid = 3`. The result is Beta.
     *
     * @see findingTheParentOfAnInlineChildByExtbase()
     */
    #[Test]
    public function findingTheParentOfAnInlineChildByCore(): void
    {
        // Given: Alpha with the comments Praise (1) and Question (2), Beta with Objection (3), Gamma without comments
        $this->importCSVDataSet(__DIR__ . '/Fixtures/Querying/Given/Comments.csv');

        // When: the posts with the comment Objection are read
        $rows = $this->contentObjectRenderer->getRecords('tx_blogexample_domain_model_post', [
            'pidInList' => '2',
            'selectFields' => 'tx_blogexample_domain_model_post.*',
            'join' => 'tx_blogexample_domain_model_comment ON tx_blogexample_domain_model_comment.post = tx_blogexample_domain_model_post.uid',
            'where' => 'tx_blogexample_domain_model_comment.uid = 3',
            'orderBy' => 'tx_blogexample_domain_model_post.uid',
        ]);
        $titles = array_column($rows, 'title');

        // Then: the parent of the comment is returned
        self::assertSame(['Beta'], $titles);
    }

    /**
     * Extbase reads the posts with the constraint `contains("comments", $comment)` for the comment Objection (3), an
     * inline child of the post. It returns Beta, like the core.
     *
     * @see findingTheParentOfAnInlineChildByCore()
     */
    #[Test]
    public function findingTheParentOfAnInlineChildByExtbase(): void
    {
        // Given: Alpha with the comments Praise (1) and Question (2), Beta with Objection (3), Gamma without comments
        $this->importCSVDataSet(__DIR__ . '/Fixtures/Querying/Given/Comments.csv');

        // When: the posts with the comment Objection are read
        $comment = $this->get(PersistenceManager::class)->getObjectByIdentifier(3, Comment::class);
        $query = $this->get(PostRepository::class)->createQuery();
        $query->matching($query->contains('comments', $comment));
        $query->setOrderings(['uid' => QueryInterface::ORDER_ASCENDING]);
        $titles = array_map(static fn(Post $post): string => $post->getTitle(), $query->execute()->toArray());

        // Then: the parent of the comment is returned
        self::assertSame(['Beta'], $titles);
    }

    /**
     * The `CONTENT` cObject with `select.pidInList = 2` and `select.recursive = 1` reads the records of the page 2 and
     * of its subpages down to one level: the post on the storage page 2 and the post on the subpage 3, not the post on
     * the sub-subpage 4.
     *
     * @see readingTheSubpagesOfTheStoragePageByExtbase()
     */
    #[Test]
    public function readingTheSubpagesOfTheStoragePageByCore(): void
    {
        // Given: one post each on the storage page 2, its subpage 3 and the subpage 4 of that subpage
        $this->importCSVDataSet(__DIR__ . '/Fixtures/Querying/Given/Subpages.csv');

        // When: the posts of page 2 and one level of subpages are read
        $rows = $this->contentObjectRenderer->getRecords('tx_blogexample_domain_model_post', [
            'pidInList' => '2',
            'recursive' => 1,
            'orderBy' => 'uid',
        ]);
        $titles = array_column($rows, 'title');

        // Then: the post of the second level of subpages is not returned
        self::assertSame(['On the storage page', 'On the subpage'], $titles);
    }

    /**
     * Extbase takes the storage pages from the plugin configuration: `persistence.storagePid = 2` with
     * `persistence.recursive = 1` selects the posts of the page 2 and of its subpages down to one level, like the core.
     * It returns the post on the storage page and the post on the subpage, not the post on the sub-subpage.
     *
     * @see readingTheSubpagesOfTheStoragePageByCore()
     */
    #[Test]
    public function readingTheSubpagesOfTheStoragePageByExtbase(): void
    {
        // Given: one post each on the storage page 2, its subpage 3 and the subpage 4 of that subpage
        $this->importCSVDataSet(__DIR__ . '/Fixtures/Querying/Given/Subpages.csv');

        // When: the posts of page 2 and one level of subpages are read
        $this->get(ConfigurationManager::class)->setConfiguration([
            'persistence' => ['storagePid' => '2', 'recursive' => 1],
            'extensionName' => 'blog_example',
            'pluginName' => 'test',
        ]);
        $query = $this->get(PostRepository::class)->createQuery();
        $query->setOrderings(['uid' => QueryInterface::ORDER_ASCENDING]);
        $titles = array_map(static fn(Post $post): string => $post->getTitle(), $query->execute()->toArray());

        // Then: the post of the second level of subpages is not returned
        self::assertSame(['On the storage page', 'On the subpage'], $titles);
    }
}
