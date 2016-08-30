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
     * @expectedException \UnexpectedValueException
     */
    public function invalidIdentifierIsRecognizedOnCreation()
    {
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
     * @expectedException \UnexpectedValueException
     */
    public function invalidBackendLayoutIsRecognizedOnAdding()
    {
        $identifier = $this->getUniqueId('identifier');
        $backendLayoutCollection = new \TYPO3\CMS\Backend\View\BackendLayout\BackendLayoutCollection($identifier);
        $backendLayoutIdentifier = $this->getUniqueId('identifier__');
        $backendLayoutMock = $this->getMock(\TYPO3\CMS\Backend\View\BackendLayout\BackendLayout::class, ['getIdentifier'], [], '', false);
        $backendLayoutMock->expects($this->once())->method('getIdentifier')->will($this->returnValue($backendLayoutIdentifier));

        $backendLayoutCollection->add($backendLayoutMock);
    }

    /**
     * @test
     * @expectedException \LogicException
     */
    public function duplicateBackendLayoutIsRecognizedOnAdding()
    {
        $identifier = $this->getUniqueId('identifier');
        $backendLayoutCollection = new \TYPO3\CMS\Backend\View\BackendLayout\BackendLayoutCollection($identifier);
        $backendLayoutIdentifier = $this->getUniqueId('identifier');
        $firstBackendLayoutMock = $this->getMock(\TYPO3\CMS\Backend\View\BackendLayout\BackendLayout::class, ['getIdentifier'], [], '', false);
        $firstBackendLayoutMock->expects($this->once())->method('getIdentifier')->will($this->returnValue($backendLayoutIdentifier));
        $secondBackendLayoutMock = $this->getMock(\TYPO3\CMS\Backend\View\BackendLayout\BackendLayout::class, ['getIdentifier'], [], '', false);
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
        $backendLayoutMock = $this->getMock(\TYPO3\CMS\Backend\View\BackendLayout\BackendLayout::class, ['getIdentifier'], [], '', false);
        $backendLayoutMock->expects($this->once())->method('getIdentifier')->will($this->returnValue($backendLayoutIdentifier));

        $backendLayoutCollection->add($backendLayoutMock);

        $this->assertEquals($backendLayoutMock, $backendLayoutCollection->get($backendLayoutIdentifier));
    }
}
