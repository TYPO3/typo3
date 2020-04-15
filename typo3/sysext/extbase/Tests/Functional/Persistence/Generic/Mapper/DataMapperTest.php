<?php

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
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class DataMapperTest extends FunctionalTestCase
{
    /**
     * @var \TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager
     */
    protected $persistenceManager;

    /**
     * @var array
     */
    protected $testExtensionsToLoad = ['typo3/sysext/extbase/Tests/Functional/Fixtures/Extensions/blog_example'];

    /**
     * @var array
     */
    protected $coreExtensionsToLoad = ['extbase', 'fluid'];

    /**
     * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface The object manager
     */
    protected $objectManager;

    /**
     * Sets up this test suite.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $this->persistenceManager = $this->objectManager->get(PersistenceManager::class);

        $GLOBALS['BE_USER'] = new BackendUserAuthentication();
    }

    /**
     * @test
     */
    public function datetimeObjectsCanBePersistedToDatetimeDatabaseFields()
    {
        $date = new \DateTime('2016-03-06T12:40:00+01:00');
        $comment = new Comment();
        $comment->setDate($date);

        $this->persistenceManager->add($comment);
        $this->persistenceManager->persistAll();
        $uid = $this->persistenceManager->getIdentifierByObject($comment);
        $this->persistenceManager->clearState();

        /** @var Comment $existingComment */
        $existingComment = $this->persistenceManager->getObjectByIdentifier($uid, Comment::class);

        self::assertEquals($date->getTimestamp(), $existingComment->getDate()->getTimestamp());
    }

    /**
     * @test
     */
    public function dateValuesAreStoredInUtcInIntegerDatabaseFields()
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
    public function dateValuesAreStoredInUtcInTextDatabaseFields()
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
    public function dateValuesAreStoredInUtcInDatetimeDatabaseFields()
    {
        $example = new DateExample();
        $date = new \DateTime('2016-03-06T12:40:00+01:00');
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
    public function dateTimeImmutableIntIsHandledAsDateTime()
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
    public function dateTimeImmutableTextIsHandledAsDateTime()
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
    public function dateTimeImmutableDateTimeIsHandledAsDateTime()
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
}
