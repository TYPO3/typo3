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
     */
    public function invalidIdentifierIsRecognizedOnAdding()
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionCode(1381597629);
        $identifier = $this->getUniqueId('identifier__');
        $dataProviderMock = $this->getMockBuilder('stdClass')->getMock();

        $this->dataProviderCollection->add($identifier, get_class($dataProviderMock));
    }

    /**
     * @test
     */
    public function invalidInterfaceIsRecognizedOnAdding()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionCode(1381269811);
        $identifier = $this->getUniqueId('identifier');
        $dataProviderMock = $this->getMockBuilder('stdClass')->getMock();

        $this->dataProviderCollection->add($identifier, get_class($dataProviderMock));
    }

    /**
     * @test
     */
    public function defaultBackendLayoutIsFound()
    {
        $backendLayoutIdentifier = $this->getUniqueId('identifier');

        $dataProviderMock = $this->getMockBuilder(\TYPO3\CMS\Backend\View\BackendLayout\DefaultDataProvider::class)
            ->setMethods(array('getBackendLayout'))
            ->disableOriginalConstructor()
            ->getMock();
        $backendLayoutMock = $this->getMockBuilder(\TYPO3\CMS\Backend\View\BackendLayout\BackendLayout::class)
            ->setMethods(array('getIdentifier'))
            ->disableOriginalConstructor()
            ->getMock();
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

        $dataProviderMock = $this->getMockBuilder(\TYPO3\CMS\Backend\View\BackendLayout\DefaultDataProvider::class)
            ->setMethods(array('getBackendLayout'))
            ->disableOriginalConstructor()
            ->getMock();
        $backendLayoutMock = $this->getMockBuilder(\TYPO3\CMS\Backend\View\BackendLayout\BackendLayout::class)
            ->setMethods(array('getIdentifier'))
            ->disableOriginalConstructor()
            ->getMock();
        $backendLayoutMock->expects($this->any())->method('getIdentifier')->will($this->returnValue($backendLayoutIdentifier));
        $dataProviderMock->expects($this->once())->method('getBackendLayout')->will($this->returnValue($backendLayoutMock));

        $this->dataProviderCollection->add($dataProviderIdentifier, $dataProviderMock);
        $providedBackendLayout = $this->dataProviderCollection->getBackendLayout($dataProviderIdentifier . '__' . $backendLayoutIdentifier, 123);

        $this->assertNotNull($providedBackendLayout);
        $this->assertEquals($backendLayoutIdentifier, $providedBackendLayout->getIdentifier());
    }
}
