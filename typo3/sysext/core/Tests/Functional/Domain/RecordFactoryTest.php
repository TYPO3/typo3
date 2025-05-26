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

namespace TYPO3\CMS\Core\Tests\Functional\Domain;

use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\DependencyInjection\Container;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\LanguageAspect;
use TYPO3\CMS\Core\Domain\Event\RecordCreationEvent;
use TYPO3\CMS\Core\Domain\Exception\IncompleteRecordException;
use TYPO3\CMS\Core\Domain\Persistence\RecordIdentityMap;
use TYPO3\CMS\Core\Domain\RawRecord;
use TYPO3\CMS\Core\Domain\Record;
use TYPO3\CMS\Core\Domain\RecordFactory;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\EventDispatcher\ListenerProvider;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class RecordFactoryTest extends FunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_groups.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/pages.csv');
    }

    #[Test]
    public function uidAndPidPropertiesAreAccessible(): void
    {
        $dbRow = BackendUtility::getRecord('pages', 1);
        $subject = $this->get(RecordFactory::class);
        $result = $subject->createFromDatabaseRow('pages', $dbRow);
        self::assertSame(1, $result->getUid());
        self::assertSame(1, $result->get('uid'));
        self::assertSame(0, $result->getPid());
        self::assertSame(0, $result->get('pid'));
    }

    #[Test]
    public function typesAreResolvedProperlyForPageRecord(): void
    {
        $dbRow = BackendUtility::getRecord('pages', 1);
        $subject = $this->get(RecordFactory::class);
        /** @var Record $result */
        $result = $subject->createFromDatabaseRow('pages', $dbRow);
        self::assertSame($dbRow, $result->getRawRecord()->toArray());
        self::assertArrayNotHasKey('mount_pid', $result->toArray());
        self::assertArrayNotHasKey('shortcut_mode', $result->toArray());
        self::assertFalse($result->getSystemProperties()->isDeleted());
        self::assertFalse($result->getSystemProperties()->isDisabled());
        self::assertSame([], $result->getSystemProperties()->getUserGroupRestriction());
        self::assertInstanceOf(\DateTimeImmutable::class, $result->getSystemProperties()->getCreatedAt());
        self::assertInstanceOf(\DateTimeImmutable::class, $result->getSystemProperties()->getLastUpdatedAt());
        self::assertInstanceOf(\DateTimeImmutable::class, $result->getSystemProperties()->getPublishAt());
        self::assertInstanceOf(\DateTimeImmutable::class, $result->getSystemProperties()->getPublishUntil());
    }

    #[Test]
    public function typesAreResolvedProperlyForContent(): void
    {
        $dbRow = BackendUtility::getRecord('tt_content', 1);
        $subject = $this->get(RecordFactory::class);
        /** @var Record $result */
        $result = $subject->createFromDatabaseRow('tt_content', $dbRow);
        self::assertSame($dbRow, $result->getRawRecord()->toArray());
        self::assertArrayNotHasKey('pi_flexform', $result->toArray());
        self::assertSame('tt_content.text', $result->getFullType());
        self::assertNull($result->getSystemProperties()->getDescription());
        self::assertSame(0, $result->getSystemProperties()->getSorting());
        self::assertFalse($result->getSystemProperties()->isLockedForEditing());
    }

    #[Test]
    public function recordWithoutTypeIsResolvedProperly(): void
    {
        $dbRow = BackendUtility::getRecord('be_groups', 9);
        $subject = $this->get(RecordFactory::class);
        /** @var Record $result */
        $result = $subject->createFromDatabaseRow('be_groups', $dbRow);
        self::assertNull($result->getRecordType());
        self::assertSame('be_groups', $result->getFullType());
        self::assertSame($dbRow, $result->getRawRecord()->toArray());
        self::assertSame('readFolder,writeFolder,addFolder,renameFolder,moveFolder,deleteFolder,readFile,writeFile,addFile,renameFile,replaceFile,moveFile,copyFile,deleteFile', $result->get('file_permissions'));
    }

    #[Test]
    public function overlaidRecordContainsRelevantInformation(): void
    {
        $context = clone $this->get(Context::class);
        $context->setAspect('language', new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_ON_WITH_FLOATING, [0]));
        $pageRepository = GeneralUtility::makeInstance(PageRepository::class, $context);
        $dbRow = $pageRepository->getPage(3);
        $subject = $this->get(RecordFactory::class);
        /** @var Record $result */
        $result = $subject->createFromDatabaseRow('pages', $dbRow);
        self::assertSame(903, $result->getOverlaidUid());
        self::assertSame(903, $result->getComputedProperties()->getLocalizedUid());
        self::assertSame(3, $result->getUid());
        // Uses the Page object
        self::assertSame('Dummy 1-3', $result->getComputedProperties()->getTranslationSource()['title']);
    }

    #[Test]
    public function overlaidRecordContainsVersionStateAndLanguageState(): void
    {
        $context = clone $this->get(Context::class);
        $context->setAspect('language', new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_ON_WITH_FLOATING, [0]));
        $pageRepository = GeneralUtility::makeInstance(PageRepository::class, $context);
        $dbRow = $pageRepository->getPage(3);
        $subject = $this->get(RecordFactory::class);
        /** @var Record $result */
        $result = $subject->createFromDatabaseRow('pages', $dbRow);
        self::assertSame(0, $result->getVersionInfo()->getWorkspaceId());
        self::assertSame(3, $result->getLanguageInfo()->getTranslationParent());
        self::assertSame(1, $result->getLanguageInfo()->getLanguageId());
        self::assertSame(1, $result->getLanguageId());
        self::assertArrayHasKey('categories', $result->toArray());
        self::assertArrayNotHasKey('shortcut', $result->toArray());
        self::assertSame(1, $result->getComputedProperties()->getRequestedOverlayLanguageId());
        self::assertNull($result->getComputedProperties()->getVersionedUid());
    }

    #[Test]
    public function recordIdentityMapIsRespected(): void
    {
        $context = clone $this->get(Context::class);
        $context->setAspect('language', new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_ON_WITH_FLOATING, [0]));
        $pageRepository = GeneralUtility::makeInstance(PageRepository::class, $context);
        $dbRow = $pageRepository->getPage(3);
        $testRecord = $this->get(RecordFactory::class)
            ->createFromDatabaseRow('pages', array_replace_recursive($dbRow, ['title' => 'Testing #1']))
            ->getRawRecord();
        $recordIdentityMap = GeneralUtility::makeInstance(RecordIdentityMap::class);
        $recordIdentityMap->add($testRecord);
        $subject = $this->get(RecordFactory::class);
        /** @var Record $result */
        $result = $subject->createResolvedRecordFromDatabaseRow('pages', $dbRow, null, $recordIdentityMap);
        self::assertSame(3, $result->getUid());
        self::assertSame('Testing #1', $result->get('title'));
    }

    #[Test]
    public function throwsIncompleteRecordExceptionForMissingLangauegField(): void
    {
        $dbRow = BackendUtility::getRecord('pages', 1);
        unset($dbRow['sys_language_uid']);

        $this->expectException(IncompleteRecordException::class);
        $this->expectExceptionCode(1726046917);

        $this->get(RecordFactory::class)->createFromDatabaseRow('pages', $dbRow);
    }

    #[Test]
    public function throwsIncompleteRecordExceptionForMissingWorkspaceField(): void
    {
        $dbRow = BackendUtility::getRecord('pages', 1);
        unset($dbRow['t3ver_oid']);

        $this->expectException(IncompleteRecordException::class);
        $this->expectExceptionCode(1726046918);

        $this->get(RecordFactory::class)->createFromDatabaseRow('pages', $dbRow);
    }

    #[Test]
    public function throwsIncompleteRecordExceptionForMissingSystemPropertyField(): void
    {
        $dbRow = BackendUtility::getRecord('pages', 1);
        unset($dbRow['deleted']);

        $this->expectException(IncompleteRecordException::class);
        $this->expectExceptionCode(1726046919);

        $this->get(RecordFactory::class)->createFromDatabaseRow('pages', $dbRow);
    }

    #[Test]
    public function recordCreationEventIsCalled(): void
    {
        $recordCreationEvent = null;
        /** @var Container $container */
        $container = $this->get('service_container');
        $container->set(
            'record-creation-listener',
            static function (RecordCreationEvent $event) use (&$recordCreationEvent) {
                $recordCreationEvent = $event;
                $recordCreationEvent->setProperty('title', 'custom title');
                $recordCreationEvent->setProperty('customProperty', 'somePropertyValue');
            }
        );
        $eventListener = $container->get(ListenerProvider::class);
        $eventListener->addListener(RecordCreationEvent::class, 'record-creation-listener');

        $pageRepository = GeneralUtility::makeInstance(PageRepository::class);
        $dbRow = $pageRepository->getPage(1);
        self::assertNull($dbRow['customProperty'] ?? null);

        $subject = $this->get(RecordFactory::class);
        $result = $subject->createFromDatabaseRow('pages', $dbRow);

        self::assertEquals('Root 1', $dbRow['title']);
        self::assertEquals('custom title', $result->get('title'));
        self::assertEquals('somePropertyValue', $result->get('customProperty'));
        self::assertInstanceOf(RecordCreationEvent::class, $recordCreationEvent);
        self::assertEquals('pages.1', $recordCreationEvent->getSchema()->getName());
        self::assertEquals('somePropertyValue', $recordCreationEvent->getProperty('customProperty'));
    }

    #[Test]
    public function recordCreationEventIsStopped(): void
    {
        /** @var Container $container */
        $container = $this->get('service_container');
        $container->set(
            'record-creation-listener-stop-propagation',
            static function (RecordCreationEvent $event) {
                $rawRecord = $event->getRawRecord();
                $event->setRecord(
                    new Record(
                        new RawRecord(123, 456, array_replace_recursive($rawRecord->toArray(), ['uid' => 123, 'pid' => 456]), $rawRecord->getComputedProperties(), $rawRecord->getFullType()),
                        ['customProperty' => 'somePropertyValue'],
                        $event->getSystemProperties()
                    )
                );
            }
        );
        $container->set(
            'record-creation-listener-not-executed',
            static function (RecordCreationEvent $event) {
                $event->setProperty('anotherProperty', 'anotherPropertyValuer');
            }
        );
        $eventListener = $container->get(ListenerProvider::class);
        $eventListener->addListener(RecordCreationEvent::class, 'record-creation-listener-stop-propagation');
        $eventListener->addListener(RecordCreationEvent::class, 'record-creation-listener-not-executed');

        $pageRepository = GeneralUtility::makeInstance(PageRepository::class);
        $dbRow = $pageRepository->getPage(3);
        $subject = $this->get(RecordFactory::class);
        $result = $subject->createFromDatabaseRow('pages', $dbRow);

        self::assertEquals('somePropertyValue', $result->get('customProperty'));
        self::assertEquals(123, $result->getUid());
        self::assertEquals(456, $result->getPid());
        self::assertFalse($result->has('anotherProperty'));
    }
}
