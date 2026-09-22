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

use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\DependencyInjection\Container;
use TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools;
use TYPO3\CMS\Core\EventDispatcher\ListenerProvider;
use TYPO3\CMS\Core\Resource\Event\BeforeResourceStorageInitializationEvent;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class LegacyStorageRecordTest extends FunctionalTestCase
{
    protected bool $initializeDatabase = false;

    #[Test]
    #[IgnoreDeprecations]
    public function getStorageRecordReturnsTheConfigurationAsFlexForm(): void
    {
        $record = $this->get(StorageRepository::class)->findByUid(1)->getStorageRecord();
        self::assertSame(1, $record['uid']);
        self::assertSame(1, $record['is_default']);
        self::assertIsString($record['configuration']);
        self::assertSame(
            'fileadmin/',
            $this->get(FlexFormTools::class)->convertFlexFormContentToArray($record['configuration'])['basePath']
        );
    }

    #[Test]
    #[IgnoreDeprecations]
    public function legacyKeysSetByEventListenersAreMapped(): void
    {
        /** @var Container $container */
        $container = $this->getContainer();
        $container->set(
            'legacy-storage-record-listener',
            static function (BeforeResourceStorageInitializationEvent $event): void {
                $record = $event->getRecord();
                $record['is_public'] = 0;
                $record['is_writable'] = 0;
                $record['processingfolder'] = '1:/legacy-processed/';
                $event->setRecord($record);
            }
        );
        $container->get(ListenerProvider::class)->addListener(BeforeResourceStorageInitializationEvent::class, 'legacy-storage-record-listener');

        $storage = $this->get(StorageRepository::class)->findByUid(1);

        self::assertFalse($storage->isPublic());
        self::assertFalse($storage->isWritable());
        self::assertSame('1:/legacy-processed/', $storage->getStorageRecord()['processingfolder']);
    }
}
