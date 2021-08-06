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

use TYPO3\CMS\Extensionmanager\Domain\Model\DownloadQueue;
use TYPO3\CMS\Extensionmanager\Domain\Model\Extension;
use TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Download queue test
 */
class DownloadQueueTest extends UnitTestCase
{
    /**
     * @var \TYPO3\CMS\Extensionmanager\Domain\Model\DownloadQueue
     */
    protected $downloadQueue;

    /**
     * @var \TYPO3\CMS\Extensionmanager\Domain\Model\Extension
     */
    protected $extension;

    protected function setUp(): void
    {
        parent::setUp();
        $this->downloadQueue = new DownloadQueue();
        $this->extension = new Extension();
        $this->extension->setExtensionKey('foobar');
        $this->extension->setVersion('1.0.0');
    }

    /**
     * @test
     */
    public function addExtensionToQueueAddsExtensionToDownloadStorageArray(): void
    {
        $this->downloadQueue->addExtensionToQueue($this->extension);
        $extensionStorage = $this->downloadQueue->getExtensionQueue();

        self::assertArrayHasKey('foobar', $extensionStorage['download']);
    }

    /**
     * @test
     */
    public function addExtensionToQueueAddsExtensionToUpdateStorageArray(): void
    {
        $this->downloadQueue->addExtensionToQueue($this->extension, 'update');
        $extensionStorage = $this->downloadQueue->getExtensionQueue();

        self::assertArrayHasKey('foobar', $extensionStorage['update']);
    }

    /**
     * @test
     */
    public function addExtensionToQueueThrowsExceptionIfUnknownStackIsGiven(): void
    {
        $this->expectException(ExtensionManagerException::class);
        $this->expectExceptionCode(1342432103);
        $this->downloadQueue->addExtensionToQueue($this->extension, 'unknownStack');
    }

    /**
     * @test
     */
    public function addExtensionToQueueThrowsExceptionIfExtensionWithSameKeyAndDifferentValuesAlreadyExists(): void
    {
        $extension = new Extension();
        $extension->setExtensionKey('foobar');
        $extension->setVersion('1.0.3');

        $this->expectException(ExtensionManagerException::class);
        $this->expectExceptionCode(1342432101);
        $this->downloadQueue->addExtensionToQueue($extension);
        $this->downloadQueue->addExtensionToQueue($this->extension);
    }

    /**
     * @test
     */
    public function removeExtensionFromQueueRemovesExtension(): void
    {
        $extension = new Extension();
        $extension->setExtensionKey('foobarbaz');
        $extension->setVersion('1.0.3');
        $this->downloadQueue->addExtensionToQueue($this->extension);
        $this->downloadQueue->addExtensionToQueue($extension);
        $extensionStorageBefore = $this->downloadQueue->getExtensionQueue();

        self::assertTrue(array_key_exists('foobar', $extensionStorageBefore['download']));

        $this->downloadQueue->removeExtensionFromQueue($this->extension);
        $extensionStorageAfter = $this->downloadQueue->getExtensionQueue();

        self::assertFalse(array_key_exists('foobar', $extensionStorageAfter['download']));
    }
}
