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

namespace TYPO3\CMS\Backend\Tests\Unit\View\BackendLayout;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\View\BackendLayout\BackendLayout;
use TYPO3\CMS\Backend\View\BackendLayout\BackendLayoutCollection;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Testing collection of backend layouts.
 */
final class BackendLayoutCollectionTest extends UnitTestCase
{
    #[Test]
    public function invalidIdentifierIsRecognizedOnCreation(): void
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionCode(1381597631);
        $identifier = StringUtility::getUniqueId('identifier__');
        new BackendLayoutCollection($identifier);
    }

    #[Test]
    public function objectIsCreated(): void
    {
        $identifier = StringUtility::getUniqueId('identifier');
        $backendLayoutCollection = new BackendLayoutCollection($identifier);

        self::assertEquals($identifier, $backendLayoutCollection->getIdentifier());
    }

    #[Test]
    public function invalidBackendLayoutIsRecognizedOnAdding(): void
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionCode(1381597628);
        $identifier = StringUtility::getUniqueId('identifier');
        $backendLayoutCollection = new BackendLayoutCollection($identifier);
        $backendLayoutIdentifier = StringUtility::getUniqueId('identifier__');
        $backendLayoutMock = $this->getMockBuilder(BackendLayout::class)
            ->onlyMethods(['getIdentifier'])
            ->disableOriginalConstructor()
            ->getMock();
        $backendLayoutMock->expects($this->once())->method('getIdentifier')->willReturn($backendLayoutIdentifier);

        $backendLayoutCollection->add($backendLayoutMock);
    }

    #[Test]
    public function duplicateBackendLayoutIsRecognizedOnAdding(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionCode(1381559376);
        $identifier = StringUtility::getUniqueId('identifier');
        $backendLayoutCollection = new BackendLayoutCollection($identifier);
        $backendLayoutIdentifier = StringUtility::getUniqueId('identifier');
        $firstBackendLayoutMock = $this->getMockBuilder(BackendLayout::class)
            ->onlyMethods(['getIdentifier'])
            ->disableOriginalConstructor()
            ->getMock();
        $firstBackendLayoutMock->expects($this->once())->method('getIdentifier')->willReturn($backendLayoutIdentifier);
        $secondBackendLayoutMock = $this->getMockBuilder(BackendLayout::class)
            ->onlyMethods(['getIdentifier'])
            ->disableOriginalConstructor()
            ->getMock();
        $secondBackendLayoutMock->expects($this->once())->method('getIdentifier')->willReturn($backendLayoutIdentifier);

        $backendLayoutCollection->add($firstBackendLayoutMock);
        $backendLayoutCollection->add($secondBackendLayoutMock);
    }

    #[Test]
    public function backendLayoutCanBeFetched(): void
    {
        $identifier = StringUtility::getUniqueId('identifier');
        $backendLayoutCollection = new BackendLayoutCollection($identifier);
        $backendLayoutIdentifier = StringUtility::getUniqueId('identifier');
        $backendLayoutMock = $this->getMockBuilder(BackendLayout::class)
            ->onlyMethods(['getIdentifier'])
            ->disableOriginalConstructor()
            ->getMock();
        $backendLayoutMock->expects($this->once())->method('getIdentifier')->willReturn($backendLayoutIdentifier);

        $backendLayoutCollection->add($backendLayoutMock);

        self::assertEquals($backendLayoutMock, $backendLayoutCollection->get($backendLayoutIdentifier));
    }
}
