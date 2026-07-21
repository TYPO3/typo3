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

namespace TYPO3\CMS\Filelist\Tests\Unit\Event;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Filelist\Event\AfterFileListRowPreparedEvent;
use TYPO3\CMS\Filelist\FileList;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class AfterFileListRowPreparedEventTest extends UnitTestCase
{
    #[Test]
    public function gettersReturnInitializedObjects(): void
    {
        $storageMock = $this->createMock(ResourceStorage::class);
        $resource = new File(['identifier' => '/foo.jpg', 'name' => 'foo.jpg'], $storageMock);
        $data = ['name' => 'foo.jpg', 'alternative' => 'Alt text'];
        $fileList = $this->createMock(FileList::class);
        $attributes = ['class' => 'my-class', 'data-filelist-identifier' => '1:/foo.jpg'];

        $event = new AfterFileListRowPreparedEvent($resource, $data, $fileList, $attributes);

        self::assertSame($resource, $event->getResource());
        self::assertSame($data, $event->getData());
        self::assertSame($fileList, $event->getFileList());
        self::assertSame($attributes, $event->getAttributes());
    }

    #[Test]
    public function setDataModifiesData(): void
    {
        $storageMock = $this->createMock(ResourceStorage::class);
        $resource = new File(['identifier' => '/foo.jpg', 'name' => 'foo.jpg'], $storageMock);
        $fileList = $this->createMock(FileList::class);

        $event = new AfterFileListRowPreparedEvent($resource, ['alternative' => 'Old text'], $fileList, []);

        $modifiedData = ['alternative' => 'New text'];
        $event->setData($modifiedData);

        self::assertSame($modifiedData, $event->getData());
    }

    #[Test]
    public function setAttributesModifiesAttributes(): void
    {
        $storageMock = $this->createMock(ResourceStorage::class);
        $resource = new File(['identifier' => '/foo.jpg', 'name' => 'foo.jpg'], $storageMock);
        $fileList = $this->createMock(FileList::class);

        $event = new AfterFileListRowPreparedEvent($resource, [], $fileList, ['class' => 'original']);

        $modifiedAttributes = ['class' => 'modified', 'data-custom' => 'value'];
        $event->setAttributes($modifiedAttributes);

        self::assertSame($modifiedAttributes, $event->getAttributes());
    }
}
