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

namespace TYPO3\CMS\Extensionmanager\Tests\Unit\Domain\Model;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Extensionmanager\Domain\Model\DownloadQueue;
use TYPO3\CMS\Extensionmanager\Domain\Model\Extension;
use TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Download queue test
 */
final class DownloadQueueTest extends UnitTestCase
{
    private DownloadQueue $downloadQueue;

    private Extension $extension;

    protected function setUp(): void
    {
        parent::setUp();
        $this->downloadQueue = new DownloadQueue();
        $this->extension = new Extension();
        $this->extension->extensionKey = 'foobar';
        $this->extension->version = '1.0.0';
    }

    #[Test]
    public function addExtensionToQueueAddsExtensionToDownloadStorageArray(): void
    {
        $this->downloadQueue->addExtensionToQueue($this->extension);
        $extensionStorage = $this->downloadQueue->getExtensionQueue();

        self::assertArrayHasKey('foobar', $extensionStorage['download']);
    }

    #[Test]
    public function addExtensionToQueueAddsExtensionToUpdateStorageArray(): void
    {
        $this->downloadQueue->addExtensionToQueue($this->extension, 'update');
        $extensionStorage = $this->downloadQueue->getExtensionQueue();

        self::assertArrayHasKey('foobar', $extensionStorage['update']);
    }

    #[Test]
    public function addExtensionToQueueThrowsExceptionIfUnknownStackIsGiven(): void
    {
        $this->expectException(ExtensionManagerException::class);
        $this->expectExceptionCode(1342432103);
        $this->downloadQueue->addExtensionToQueue($this->extension, 'unknownStack');
    }

    #[Test]
    public function addExtensionToQueueThrowsExceptionIfExtensionWithSameKeyAndDifferentValuesAlreadyExists(): void
    {
        $extension = new Extension();
        $extension->extensionKey = 'foobar';
        $extension->version = '1.0.3';

        $this->expectException(ExtensionManagerException::class);
        $this->expectExceptionCode(1342432101);
        $this->downloadQueue->addExtensionToQueue($extension);
        $this->downloadQueue->addExtensionToQueue($this->extension);
    }

    #[Test]
    public function removeExtensionFromQueueRemovesExtension(): void
    {
        $extension = new Extension();
        $extension->extensionKey = 'foobarbaz';
        $extension->version = '1.0.3';
        $this->downloadQueue->addExtensionToQueue($this->extension);
        $this->downloadQueue->addExtensionToQueue($extension);
        $extensionStorageBefore = $this->downloadQueue->getExtensionQueue();

        self::assertArrayHasKey('foobar', $extensionStorageBefore['download']);

        $this->downloadQueue->removeExtensionFromQueue($this->extension);
        $extensionStorageAfter = $this->downloadQueue->getExtensionQueue();

        self::assertArrayNotHasKey('foobar', $extensionStorageAfter['download']);
    }
}
