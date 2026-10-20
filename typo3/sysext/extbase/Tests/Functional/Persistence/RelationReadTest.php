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
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Domain\Record;
use TYPO3\CMS\Core\Domain\RecordFactory;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Tests\BlogExample\Domain\Model\Comment;
use TYPO3Tests\BlogExample\Domain\Model\Tag;
use TYPO3Tests\BlogExample\Domain\Repository\PostWithRelationsRepository;

/**
 * Reading relation types that have no relation table or no foreign field: select and group fields that store a list
 * of uids, inline children without `foreign_field`, two inline fields that share one child table and a relation to a
 * deleted or missing record.
 *
 * Every scenario is tested twice against the same expectation: once with the core API as oracle (RecordFactory,
 * suffix "ByCore"), once with Extbase (suffix "ByExtbase"). Both tests of a pair read the fixture
 * Fixtures/RelationRead/Given.csv and compare the readable values of the related records in the order that the
 * relation returns them. An Extbase test that differs from core is skipped.
 *
 * Post 1 "First post" stores the tags PHP (2) and TYPO3 (1) in this order in its fields `picked_tags` (select) and
 * `referenced_tags` (group), and the comments of Max (1) and Moritz (2) in this order in `additional_comments`
 * (inline without `foreign_field`). Its fields `pinned_comments` (Clara, Dieter) and `highlighted_comments` (Emil)
 * share the comment table: the comments point to post 1 with `post`, to the post table with `post_table` and are told
 * apart by `post_field`. The reviewer of post 1 is the deleted person Ben (2), the reviewer of post 2 "Second post" is
 * the person 99, which does not exist.
 */
final class RelationReadTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3/sysext/extbase/Tests/Functional/Fixtures/Extensions/blog_example',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RelationRead/Given.csv');
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
     * Post 1 "First post" stores the uids "2,1" in its field `picked_tags`, a select field with a foreign table and
     * without relation table, so the tags PHP (2) and TYPO3 (1). The core reads the field `picked_tags` of the record
     * of post 1 like the record API (RecordFactory) does and returns the tags in the stored order: "PHP" and "TYPO3".
     *
     * @see readingASelectWithoutRelationTableInTheStoredOrderByExtbase()
     */
    #[Test]
    public function readingASelectWithoutRelationTableInTheStoredOrderByCore(): void
    {
        // Given: the record of post 1 "First post"
        $row = $this->get(ConnectionPool::class)->getConnectionForTable('tx_blogexample_domain_model_post')
            ->select(['*'], 'tx_blogexample_domain_model_post', ['uid' => 1])->fetchAssociative();
        $record = $this->get(RecordFactory::class)->createResolvedRecordFromDatabaseRow('tx_blogexample_domain_model_post', $row);

        // When: the picked tags of post 1 are read
        $names = array_map(
            static fn(Record $tag): string => $tag->get('name'),
            iterator_to_array($record->get('picked_tags'), false)
        );

        // Then: the tags come in the stored order
        self::assertSame(['PHP', 'TYPO3'], $names);
    }

    /**
     * Extbase reads post 1 "First post" and returns the property `pickedTags` ordered by uid, "TYPO3" and "PHP", not in
     * the stored order "2,1" of the field `picked_tags`.
     *
     * @see readingASelectWithoutRelationTableInTheStoredOrderByCore()
     */
    #[Test]
    public function readingASelectWithoutRelationTableInTheStoredOrderByExtbase(): void
    {
        self::markTestSkipped('Extbase differs from core: it returns the tags of a select field without relation table ordered by uid, "TYPO3" and "PHP", not in the stored order of the field, "PHP" and "TYPO3".');

        // Given: post 1 "First post"
        // @phpstan-ignore deadCode.unreachable
        $post = $this->get(PostWithRelationsRepository::class)->findByUid(1);

        // When: the picked tags of post 1 are read
        $names = array_map(static fn(Tag $tag): string => $tag->getName(), $post->getPickedTags()->toArray());

        // Then: the tags come in the stored order
        self::assertSame(['PHP', 'TYPO3'], $names);
    }

    /**
     * Post 1 "First post" stores the uids "2,1" in its field `referenced_tags`, a group field that allows the tag
     * table and has no relation table, so the tags PHP (2) and TYPO3 (1). The core reads the field `referenced_tags`
     * of the record of post 1 like the record API (RecordFactory) does and returns the tags in the stored order: "PHP"
     * and "TYPO3".
     *
     * @see readingAGroupFieldInTheStoredOrderByExtbase()
     */
    #[Test]
    public function readingAGroupFieldInTheStoredOrderByCore(): void
    {
        // Given: the record of post 1 "First post"
        $row = $this->get(ConnectionPool::class)->getConnectionForTable('tx_blogexample_domain_model_post')
            ->select(['*'], 'tx_blogexample_domain_model_post', ['uid' => 1])->fetchAssociative();
        $record = $this->get(RecordFactory::class)->createResolvedRecordFromDatabaseRow('tx_blogexample_domain_model_post', $row);

        // When: the referenced tags of post 1 are read
        $names = array_map(
            static fn(Record $tag): string => $tag->get('name'),
            iterator_to_array($record->get('referenced_tags'), false)
        );

        // Then: the tags come in the stored order
        self::assertSame(['PHP', 'TYPO3'], $names);
    }

    /**
     * Extbase reads post 1 "First post" and returns the property `referencedTags` ordered by uid, "TYPO3" and "PHP",
     * not in the stored order "2,1" of the field `referenced_tags`.
     *
     * @see readingAGroupFieldInTheStoredOrderByCore()
     */
    #[Test]
    public function readingAGroupFieldInTheStoredOrderByExtbase(): void
    {
        self::markTestSkipped('Extbase differs from core: it returns the tags of a group field without relation table ordered by uid, "TYPO3" and "PHP", not in the stored order of the field, "PHP" and "TYPO3".');

        // Given: post 1 "First post"
        // @phpstan-ignore deadCode.unreachable
        $post = $this->get(PostWithRelationsRepository::class)->findByUid(1);

        // When: the referenced tags of post 1 are read
        $names = array_map(static fn(Tag $tag): string => $tag->getName(), $post->getReferencedTags()->toArray());

        // Then: the tags come in the stored order
        self::assertSame(['PHP', 'TYPO3'], $names);
    }

    /**
     * Post 1 "First post" stores the uids "2,1" in its field `additional_comments`, an inline field without
     * `foreign_field`, so the comments of Moritz (2) and Max (1). The core reads the field `additional_comments` of
     * the record of post 1 like the record API (RecordFactory) does and returns the comments in the stored order:
     * "Moritz" and "Max".
     *
     * @see readingInlineChildrenWithoutForeignFieldInTheStoredOrderByExtbase()
     */
    #[Test]
    public function readingInlineChildrenWithoutForeignFieldInTheStoredOrderByCore(): void
    {
        // Given: the record of post 1 "First post"
        $row = $this->get(ConnectionPool::class)->getConnectionForTable('tx_blogexample_domain_model_post')
            ->select(['*'], 'tx_blogexample_domain_model_post', ['uid' => 1])->fetchAssociative();
        $record = $this->get(RecordFactory::class)->createResolvedRecordFromDatabaseRow('tx_blogexample_domain_model_post', $row);

        // When: the additional comments of post 1 are read
        $authors = array_map(
            static fn(Record $comment): string => $comment->get('author'),
            iterator_to_array($record->get('additional_comments'), false)
        );

        // Then: the comments come in the stored order
        self::assertSame(['Moritz', 'Max'], $authors);
    }

    /**
     * Extbase reads post 1 "First post" and returns the property `additionalComments` ordered by uid, "Max" and
     * "Moritz", not in the stored order "2,1" of the field `additional_comments`.
     *
     * @see readingInlineChildrenWithoutForeignFieldInTheStoredOrderByCore()
     */
    #[Test]
    public function readingInlineChildrenWithoutForeignFieldInTheStoredOrderByExtbase(): void
    {
        self::markTestSkipped('Extbase differs from core: it returns the children of an inline field without foreign field ordered by uid, "Max" and "Moritz", not in the stored order of the field, "Moritz" and "Max".');

        // Given: post 1 "First post"
        // @phpstan-ignore deadCode.unreachable
        $post = $this->get(PostWithRelationsRepository::class)->findByUid(1);

        // When: the additional comments of post 1 are read
        $authors = array_map(static fn(Comment $comment): string => $comment->getAuthor(), $post->getAdditionalComments()->toArray());

        // Then: the comments come in the stored order
        self::assertSame(['Moritz', 'Max'], $authors);
    }

    /**
     * The comments 3 (Clara) and 4 (Dieter) belong to the field `pinned_comments` of post 1 "First post", comment 5
     * (Emil) to its field `highlighted_comments`; all three point to post 1 with the same foreign field `post` and the
     * table name of the posts. The core reads each field of the record of post 1 like the record API (RecordFactory)
     * does and the match field `post_field` tells the fields apart: `pinned_comments` returns "Clara" and "Dieter",
     * `highlighted_comments` returns "Emil".
     *
     * @see readingTwoInlineFieldsOfOneChildTableByExtbase()
     */
    #[Test]
    public function readingTwoInlineFieldsOfOneChildTableByCore(): void
    {
        // Given: the record of post 1 "First post"
        $row = $this->get(ConnectionPool::class)->getConnectionForTable('tx_blogexample_domain_model_post')
            ->select(['*'], 'tx_blogexample_domain_model_post', ['uid' => 1])->fetchAssociative();
        $record = $this->get(RecordFactory::class)->createResolvedRecordFromDatabaseRow('tx_blogexample_domain_model_post', $row);

        // When: both comment fields of post 1 are read
        $authors = [
            'pinned' => array_map(
                static fn(Record $comment): string => $comment->get('author'),
                iterator_to_array($record->get('pinned_comments'), false)
            ),
            'highlighted' => array_map(
                static fn(Record $comment): string => $comment->get('author'),
                iterator_to_array($record->get('highlighted_comments'), false)
            ),
        ];

        // Then: each field returns only its own comments
        self::assertSame(['pinned' => ['Clara', 'Dieter'], 'highlighted' => ['Emil']], $authors);
    }

    /**
     * Extbase reads post 1 "First post" and returns the same comments per property as the core: `pinnedComments`
     * returns "Clara" and "Dieter", `highlightedComments` returns "Emil".
     *
     * @see readingTwoInlineFieldsOfOneChildTableByCore()
     */
    #[Test]
    public function readingTwoInlineFieldsOfOneChildTableByExtbase(): void
    {
        // Given: post 1 "First post"
        $post = $this->get(PostWithRelationsRepository::class)->findByUid(1);

        // When: both comment fields of post 1 are read
        $authors = [
            'pinned' => array_map(static fn(Comment $comment): string => $comment->getAuthor(), $post->getPinnedComments()->toArray()),
            'highlighted' => array_map(static fn(Comment $comment): string => $comment->getAuthor(), $post->getHighlightedComments()->toArray()),
        ];

        // Then: each field returns only its own comments
        self::assertSame(['pinned' => ['Clara', 'Dieter'], 'highlighted' => ['Emil']], $authors);
    }

    /**
     * Post 1 "First post" points to the person Ben (2) in its field `reviewer`, a select field for one person, and
     * person 2 is marked deleted. The core reads the field `reviewer` of the record of post 1 like the record API
     * (RecordFactory) does and returns null.
     *
     * @see readingAToOneRelationToADeletedRecordByExtbase()
     */
    #[Test]
    public function readingAToOneRelationToADeletedRecordByCore(): void
    {
        // Given: the record of post 1 "First post"
        $row = $this->get(ConnectionPool::class)->getConnectionForTable('tx_blogexample_domain_model_post')
            ->select(['*'], 'tx_blogexample_domain_model_post', ['uid' => 1])->fetchAssociative();
        $record = $this->get(RecordFactory::class)->createResolvedRecordFromDatabaseRow('tx_blogexample_domain_model_post', $row);

        // When: the reviewer of post 1 is read
        $reviewer = $record->get('reviewer');

        // Then: there is no reviewer
        self::assertNull($reviewer);
    }

    /**
     * Extbase reads post 1 "First post" and returns null for the property `reviewer`, like the core.
     *
     * @see readingAToOneRelationToADeletedRecordByCore()
     */
    #[Test]
    public function readingAToOneRelationToADeletedRecordByExtbase(): void
    {
        // Given: post 1 "First post"
        $post = $this->get(PostWithRelationsRepository::class)->findByUid(1);

        // When: the reviewer of post 1 is read
        $reviewer = $post->getReviewer();

        // Then: there is no reviewer
        self::assertNull($reviewer);
    }

    /**
     * Post 2 "Second post" points to the uid 99 in its field `reviewer`, a select field for one person, and no person
     * 99 exists. The core reads the field `reviewer` of the record of post 2 like the record API (RecordFactory) does
     * and returns null.
     *
     * @see readingAToOneRelationToAMissingRecordByExtbase()
     */
    #[Test]
    public function readingAToOneRelationToAMissingRecordByCore(): void
    {
        // Given: the record of post 2 "Second post"
        $row = $this->get(ConnectionPool::class)->getConnectionForTable('tx_blogexample_domain_model_post')
            ->select(['*'], 'tx_blogexample_domain_model_post', ['uid' => 2])->fetchAssociative();
        $record = $this->get(RecordFactory::class)->createResolvedRecordFromDatabaseRow('tx_blogexample_domain_model_post', $row);

        // When: the reviewer of post 2 is read
        $reviewer = $record->get('reviewer');

        // Then: there is no reviewer
        self::assertNull($reviewer);
    }

    /**
     * Extbase reads post 2 "Second post" and returns null for the property `reviewer`, like the core.
     *
     * @see readingAToOneRelationToAMissingRecordByCore()
     */
    #[Test]
    public function readingAToOneRelationToAMissingRecordByExtbase(): void
    {
        // Given: post 2 "Second post"
        $post = $this->get(PostWithRelationsRepository::class)->findByUid(2);

        // When: the reviewer of post 2 is read
        $reviewer = $post->getReviewer();

        // Then: there is no reviewer
        self::assertNull($reviewer);
    }
}
