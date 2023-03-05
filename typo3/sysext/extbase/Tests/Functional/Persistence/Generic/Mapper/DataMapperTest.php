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

namespace TYPO3\CMS\Extbase\Tests\Functional\Persistence\Generic\Mapper;

use ExtbaseTeam\BlogExample\Domain\Model\Comment;
use ExtbaseTeam\BlogExample\Domain\Model\DateExample;
use ExtbaseTeam\BlogExample\Domain\Model\DateTimeImmutableExample;
use ExtbaseTeam\BlogExample\Domain\Model\Post;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class DataMapperTest extends FunctionalTestCase
{
    /**
     * @var PersistenceManager
     */
    protected $persistenceManager;

    protected $testExtensionsToLoad = ['typo3/sysext/extbase/Tests/Functional/Fixtures/Extensions/blog_example'];

    /**
     * Sets up this test suite.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->persistenceManager = $this->get(PersistenceManager::class);
        $GLOBALS['BE_USER'] = new BackendUserAuthentication();
    }

    /**
     * @test
     */
    public function dateValuesAreStoredInUtcInIntegerDatabaseFields(): void
    {
        $example = new DateExample();
        $date = new \DateTime('2016-03-06T12:40:00+01:00');
        $example->setDatetimeInt($date);

        $this->persistenceManager->add($example);
        $this->persistenceManager->persistAll();
        $uid = $this->persistenceManager->getIdentifierByObject($example);
        $this->persistenceManager->clearState();

        /** @var DateExample $example */
        $example = $this->persistenceManager->getObjectByIdentifier($uid, DateExample::class);

        self::assertEquals($example->getDatetimeInt()->getTimestamp(), $date->getTimestamp());
    }

    /**
     * @test
     */
    public function dateValuesAreStoredInUtcInTextDatabaseFields(): void
    {
        $example = new DateExample();
        $date = new \DateTime('2016-03-06T12:40:00+01:00');
        $example->setDatetimeText($date);

        $this->persistenceManager->add($example);
        $this->persistenceManager->persistAll();
        $uid = $this->persistenceManager->getIdentifierByObject($example);
        $this->persistenceManager->clearState();

        /** @var DateExample $example */
        $example = $this->persistenceManager->getObjectByIdentifier($uid, DateExample::class);

        self::assertEquals($example->getDatetimeText()->getTimestamp(), $date->getTimestamp());
    }

    /**
     * @test
     */
    public function dateValuesAreStoredInLocalTimeInDatetimeDatabaseFields(): void
    {
        $example = new DateExample();
        $date = new \DateTime('2016-03-06T12:40:00');
        $example->setDatetimeDatetime($date);

        $this->persistenceManager->add($example);
        $this->persistenceManager->persistAll();
        $uid = $this->persistenceManager->getIdentifierByObject($example);
        $this->persistenceManager->clearState();

        /** @var DateExample $example */
        $example = $this->persistenceManager->getObjectByIdentifier($uid, DateExample::class);

        self::assertEquals($example->getDatetimeDatetime()->getTimestamp(), $date->getTimestamp());
    }

    /**
     * @test
     */
    public function dateTimeImmutableIntIsHandledAsDateTime(): void
    {
        $subject = new DateTimeImmutableExample();
        $date = new \DateTimeImmutable('2018-07-24T20:40:00');
        $subject->setDatetimeImmutableInt($date);

        $this->persistenceManager->add($subject);
        $this->persistenceManager->persistAll();
        $uid = $this->persistenceManager->getIdentifierByObject($subject);
        $this->persistenceManager->clearState();

        /** @var DateTimeImmutableExample $subject */
        $subject = $this->persistenceManager->getObjectByIdentifier($uid, DateTimeImmutableExample::class);

        self::assertEquals($date, $subject->getDatetimeImmutableInt());
    }

    /**
     * @test
     */
    public function dateTimeImmutableTextIsHandledAsDateTime(): void
    {
        $subject = new DateTimeImmutableExample();
        $date = new \DateTimeImmutable('2018-07-24T20:40:00');
        $subject->setDatetimeImmutableText($date);

        $this->persistenceManager->add($subject);
        $this->persistenceManager->persistAll();
        $uid = $this->persistenceManager->getIdentifierByObject($subject);
        $this->persistenceManager->clearState();

        /** @var DateTimeImmutableExample $subject */
        $subject = $this->persistenceManager->getObjectByIdentifier($uid, DateTimeImmutableExample::class);

        self::assertEquals($date, $subject->getDatetimeImmutableText());
    }

    /**
     * @test
     */
    public function dateTimeImmutableDateTimeIsHandledAsDateTime(): void
    {
        $subject = new DateTimeImmutableExample();
        $date = new \DateTimeImmutable('2018-07-24T20:40:00');
        $subject->setDatetimeImmutableDatetime($date);

        $this->persistenceManager->add($subject);
        $this->persistenceManager->persistAll();
        $uid = $this->persistenceManager->getIdentifierByObject($subject);
        $this->persistenceManager->clearState();

        /** @var DateTimeImmutableExample $subject */
        $subject = $this->persistenceManager->getObjectByIdentifier($uid, DateTimeImmutableExample::class);

        self::assertSame($date->getTimestamp(), $subject->getDatetimeImmutableDatetime()->getTimestamp());
    }

    /**
     * @test
     */
    public function fetchRelatedRespectsForeignDefaultSortByTCAConfiguration(): void
    {
        // Arrange
        $this->importCSVDataSet('typo3/sysext/extbase/Tests/Functional/Persistence/Fixtures/posts.csv');
        $this->importCSVDataSet('typo3/sysext/extbase/Tests/Functional/Persistence/Fixtures/comments.csv');

        $dataMapper = $this->get(DataMapper::class);

        $post = new Post();
        $post->_setProperty('uid', 1);

        // Act
        $comments = $dataMapper->fetchRelated($post, 'comments', '5', false)->toArray();

        // Assert
        self::assertSame(
            [5, 4, 3, 2, 1], // foreign_default_sortby is set to uid desc, see
            array_map(fn (Comment $comment) => $comment->getUid(), $comments)
        );
    }
}
