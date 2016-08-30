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
 * Testing collection of backend layout data providers.
 */
class DataProviderCollectionTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @var \TYPO3\CMS\Backend\View\BackendLayout\DataProviderCollection
     */
    protected $dataProviderCollection;

    /**
     * Sets up this test case.
     */
    protected function setUp()
    {
        $this->dataProviderCollection = new \TYPO3\CMS\Backend\View\BackendLayout\DataProviderCollection();
    }

    /**
     * @test
     * @expectedException \UnexpectedValueException
     */
    public function invalidIdentifierIsRecognizedOnAdding()
    {
        $identifier = $this->getUniqueId('identifier__');
        $dataProviderMock = $this->getMock('stdClass');

        $this->dataProviderCollection->add($identifier, get_class($dataProviderMock));
    }

    /**
     * @test
     * @expectedException \LogicException
     */
    public function invalidInterfaceIsRecognizedOnAdding()
    {
        $identifier = $this->getUniqueId('identifier');
        $dataProviderMock = $this->getMock('stdClass');

        $this->dataProviderCollection->add($identifier, get_class($dataProviderMock));
    }

    /**
     * @test
     */
    public function defaultBackendLayoutIsFound()
    {
        $backendLayoutIdentifier = $this->getUniqueId('identifier');

        $dataProviderMock = $this->getMock(\TYPO3\CMS\Backend\View\BackendLayout\DefaultDataProvider::class, ['getBackendLayout'], [], '', false);
        $backendLayoutMock = $this->getMock(\TYPO3\CMS\Backend\View\BackendLayout\BackendLayout::class, ['getIdentifier'], [], '', false);
        $backendLayoutMock->expects($this->any())->method('getIdentifier')->will($this->returnValue($backendLayoutIdentifier));
        $dataProviderMock->expects($this->once())->method('getBackendLayout')->will($this->returnValue($backendLayoutMock));

        $this->dataProviderCollection->add('default', $dataProviderMock);
        $providedBackendLayout = $this->dataProviderCollection->getBackendLayout($backendLayoutIdentifier, 123);

        $this->assertNotNull($providedBackendLayout);
        $this->assertEquals($backendLayoutIdentifier, $providedBackendLayout->getIdentifier());
    }

    /**
     * @test
     */
    public function providedBackendLayoutIsFound()
    {
        $dataProviderIdentifier = $this->getUniqueId('custom');
        $backendLayoutIdentifier = $this->getUniqueId('identifier');

        $dataProviderMock = $this->getMock(\TYPO3\CMS\Backend\View\BackendLayout\DefaultDataProvider::class, ['getBackendLayout'], [], '', false);
        $backendLayoutMock = $this->getMock(\TYPO3\CMS\Backend\View\BackendLayout\BackendLayout::class, ['getIdentifier'], [], '', false);
        $backendLayoutMock->expects($this->any())->method('getIdentifier')->will($this->returnValue($backendLayoutIdentifier));
        $dataProviderMock->expects($this->once())->method('getBackendLayout')->will($this->returnValue($backendLayoutMock));

        $this->dataProviderCollection->add($dataProviderIdentifier, $dataProviderMock);
        $providedBackendLayout = $this->dataProviderCollection->getBackendLayout($dataProviderIdentifier . '__' . $backendLayoutIdentifier, 123);

        $this->assertNotNull($providedBackendLayout);
        $this->assertEquals($backendLayoutIdentifier, $providedBackendLayout->getIdentifier());
    }
}
