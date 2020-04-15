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

namespace TYPO3\CMS\Backend\Tests\Unit\View\BackendLayout;

use TYPO3\CMS\Backend\View\BackendLayout\BackendLayout;
use TYPO3\CMS\Backend\View\BackendLayout\BackendLayoutCollection;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Testing collection of backend layouts.
 */
class BackendLayoutCollectionTest extends UnitTestCase
{
    /**
     * @test
     */
    public function invalidIdentifierIsRecognizedOnCreation()
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionCode(1381597631);
        $identifier = StringUtility::getUniqueId('identifier__');
        new BackendLayoutCollection($identifier);
    }

    /**
     * @test
     */
    public function objectIsCreated()
    {
        $identifier = StringUtility::getUniqueId('identifier');
        $backendLayoutCollection = new BackendLayoutCollection($identifier);

        self::assertEquals($identifier, $backendLayoutCollection->getIdentifier());
    }

    /**
     * @test
     */
    public function invalidBackendLayoutIsRecognizedOnAdding()
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionCode(1381597628);
        $identifier = StringUtility::getUniqueId('identifier');
        $backendLayoutCollection = new BackendLayoutCollection($identifier);
        $backendLayoutIdentifier = StringUtility::getUniqueId('identifier__');
        $backendLayoutMock = $this->getMockBuilder(BackendLayout::class)
            ->setMethods(['getIdentifier'])
            ->disableOriginalConstructor()
            ->getMock();
        $backendLayoutMock->expects(self::once())->method('getIdentifier')->willReturn($backendLayoutIdentifier);

        $backendLayoutCollection->add($backendLayoutMock);
    }

    /**
     * @test
     */
    public function duplicateBackendLayoutIsRecognizedOnAdding()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionCode(1381559376);
        $identifier = StringUtility::getUniqueId('identifier');
        $backendLayoutCollection = new BackendLayoutCollection($identifier);
        $backendLayoutIdentifier = StringUtility::getUniqueId('identifier');
        $firstBackendLayoutMock = $this->getMockBuilder(BackendLayout::class)
            ->setMethods(['getIdentifier'])
            ->disableOriginalConstructor()
            ->getMock();
        $firstBackendLayoutMock->expects(self::once())->method('getIdentifier')->willReturn($backendLayoutIdentifier);
        $secondBackendLayoutMock = $this->getMockBuilder(BackendLayout::class)
            ->setMethods(['getIdentifier'])
            ->disableOriginalConstructor()
            ->getMock();
        $secondBackendLayoutMock->expects(self::once())->method('getIdentifier')->willReturn($backendLayoutIdentifier);

        $backendLayoutCollection->add($firstBackendLayoutMock);
        $backendLayoutCollection->add($secondBackendLayoutMock);
    }

    /**
     * @test
     */
    public function backendLayoutCanBeFetched()
    {
        $identifier = StringUtility::getUniqueId('identifier');
        $backendLayoutCollection = new BackendLayoutCollection($identifier);
        $backendLayoutIdentifier = StringUtility::getUniqueId('identifier');
        $backendLayoutMock = $this->getMockBuilder(BackendLayout::class)
            ->setMethods(['getIdentifier'])
            ->disableOriginalConstructor()
            ->getMock();
        $backendLayoutMock->expects(self::once())->method('getIdentifier')->willReturn($backendLayoutIdentifier);

        $backendLayoutCollection->add($backendLayoutMock);

        self::assertEquals($backendLayoutMock, $backendLayoutCollection->get($backendLayoutIdentifier));
    }
}
