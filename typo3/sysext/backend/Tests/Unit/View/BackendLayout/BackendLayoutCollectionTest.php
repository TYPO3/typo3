<?php
namespace TYPO3\CMS\Backend\Tests\Unit\View\BackendLayout;

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

/**
 * Testing collection of backend layouts.
 */
class BackendLayoutCollectionTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @test
     */
    public function invalidIdentifierIsRecognizedOnCreation()
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionCode(1381597631);
        $identifier = $this->getUniqueId('identifier__');
        new \TYPO3\CMS\Backend\View\BackendLayout\BackendLayoutCollection($identifier);
    }

    /**
     * @test
     */
    public function objectIsCreated()
    {
        $identifier = $this->getUniqueId('identifier');
        $backendLayoutCollection = new \TYPO3\CMS\Backend\View\BackendLayout\BackendLayoutCollection($identifier);

        $this->assertEquals($identifier, $backendLayoutCollection->getIdentifier());
    }

    /**
     * @test
     */
    public function invalidBackendLayoutIsRecognizedOnAdding()
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionCode(1381597628);
        $identifier = $this->getUniqueId('identifier');
        $backendLayoutCollection = new \TYPO3\CMS\Backend\View\BackendLayout\BackendLayoutCollection($identifier);
        $backendLayoutIdentifier = $this->getUniqueId('identifier__');
        $backendLayoutMock = $this->getMockBuilder(\TYPO3\CMS\Backend\View\BackendLayout\BackendLayout::class)
            ->setMethods(array('getIdentifier'))
            ->disableOriginalConstructor()
            ->getMock();
        $backendLayoutMock->expects($this->once())->method('getIdentifier')->will($this->returnValue($backendLayoutIdentifier));

        $backendLayoutCollection->add($backendLayoutMock);
    }

    /**
     * @test
     */
    public function duplicateBackendLayoutIsRecognizedOnAdding()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionCode(1381559376);
        $identifier = $this->getUniqueId('identifier');
        $backendLayoutCollection = new \TYPO3\CMS\Backend\View\BackendLayout\BackendLayoutCollection($identifier);
        $backendLayoutIdentifier = $this->getUniqueId('identifier');
        $firstBackendLayoutMock = $this->getMockBuilder(\TYPO3\CMS\Backend\View\BackendLayout\BackendLayout::class)
            ->setMethods(array('getIdentifier'))
            ->disableOriginalConstructor()
            ->getMock();
        $firstBackendLayoutMock->expects($this->once())->method('getIdentifier')->will($this->returnValue($backendLayoutIdentifier));
        $secondBackendLayoutMock = $this->getMockBuilder(\TYPO3\CMS\Backend\View\BackendLayout\BackendLayout::class)
            ->setMethods(array('getIdentifier'))
            ->disableOriginalConstructor()
            ->getMock();
        $secondBackendLayoutMock->expects($this->once())->method('getIdentifier')->will($this->returnValue($backendLayoutIdentifier));

        $backendLayoutCollection->add($firstBackendLayoutMock);
        $backendLayoutCollection->add($secondBackendLayoutMock);
    }

    /**
     * @test
     */
    public function backendLayoutCanBeFetched()
    {
        $identifier = $this->getUniqueId('identifier');
        $backendLayoutCollection = new \TYPO3\CMS\Backend\View\BackendLayout\BackendLayoutCollection($identifier);
        $backendLayoutIdentifier = $this->getUniqueId('identifier');
        $backendLayoutMock = $this->getMockBuilder(\TYPO3\CMS\Backend\View\BackendLayout\BackendLayout::class)
            ->setMethods(array('getIdentifier'))
            ->disableOriginalConstructor()
            ->getMock();
        $backendLayoutMock->expects($this->once())->method('getIdentifier')->will($this->returnValue($backendLayoutIdentifier));

        $backendLayoutCollection->add($backendLayoutMock);

        $this->assertEquals($backendLayoutMock, $backendLayoutCollection->get($backendLayoutIdentifier));
    }
}
