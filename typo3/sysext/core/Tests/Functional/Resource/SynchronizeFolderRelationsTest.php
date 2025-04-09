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

namespace TYPO3\CMS\Core\Tests\Functional\Resource;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Resource\Driver\LocalDriver;
use TYPO3\CMS\Core\Resource\Event\AfterFolderRenamedEvent;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Resource\SynchronizeFolderRelations;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class SynchronizeFolderRelationsTest extends FunctionalTestCase
{
    protected SynchronizeFolderRelations $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);

        $this->subject = new SynchronizeFolderRelations(
            $this->get(ConnectionPool::class),
            $this->get(FlashMessageService::class),
            $this->get(TcaSchemaFactory::class)
        );
    }

    #[Test]
    public function synchronizeFilemountsAfterRenameTest(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/FilemountsBase.csv');

        // Nothing should change if the renamed identifier is the same as the current one
        $this->subject->synchronizeFilemountsAfterRename($this->getAfterFolderRenamedEvent('/foo/bar/'));
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/FilemountsBase.csv');

        // Relations should be changed if the renamed identifier differs from the current one
        $this->subject->synchronizeFilemountsAfterRename($this->getAfterFolderRenamedEvent('/foo/renamed/'));
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/FilemountsResult.csv');

        // Check for generated flash messages
        $flashMessages = $this->get(FlashMessageService::class)->getMessageQueueByIdentifier()->getAllMessages();
        self::assertNotEmpty($flashMessages);

        // Check flash message content
        $flashMessage = array_shift($flashMessages);
        self::assertStringContainsString('6 File mount records', $flashMessage->getMessage());
    }

    #[Test]
    public function synchronizeFileCollectionsAfterRenameTest(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/FileCollectionBase.csv');

        // Nothing should change if the renamed identifier is the same as the current one
        $this->subject->synchronizeFileCollectionsAfterRename($this->getAfterFolderRenamedEvent('/foo/bar/'));
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/FileCollectionBase.csv');

        // Relations should be changed if the renamed identifier differs from the current one
        $this->subject->synchronizeFileCollectionsAfterRename($this->getAfterFolderRenamedEvent('/foo/renamed/'));
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/FileCollectionResult.csv');

        // Check for generated flash messages
        $flashMessages = $this->get(FlashMessageService::class)->getMessageQueueByIdentifier()->getAllMessages();
        self::assertNotEmpty($flashMessages);

        // Check flash message content
        $flashMessage = array_shift($flashMessages);
        self::assertStringContainsString('5 File collection records', $flashMessage->getMessage());
    }

    protected function getAfterFolderRenamedEvent(string $targetIdentifier): AfterFolderRenamedEvent
    {
        $sourceDriver = $this->createMock(LocalDriver::class);
        $storage = new ResourceStorage($sourceDriver, ['uid' => 1]);
        $targetFolder = new Folder($storage, $targetIdentifier, 'renamed folder');
        $sourceFolder = new Folder($storage, '/foo/bar/', 'some folder');
        return new AfterFolderRenamedEvent($targetFolder, $sourceFolder);
    }
}
