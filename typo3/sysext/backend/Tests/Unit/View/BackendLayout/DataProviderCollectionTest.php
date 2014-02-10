<?php
namespace TYPO3\CMS\Backend\Tests\Unit\View\BackendLayout;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Oliver Hader <oliver.hader@typo3.org>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the text file GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Testing collection of backend layout data providers.
 *
 * @author Oliver Hader <oliver.hader@typo3.org>
 */
class DataProviderCollectionTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\CMS\Backend\View\BackendLayout\DataProviderCollection
	 */
	protected $dataProviderCollection;

	/**
	 * Sets up this test case.
	 */
	protected function setUp() {
		$this->dataProviderCollection = new \TYPO3\CMS\Backend\View\BackendLayout\DataProviderCollection();
	}

	/**
	 * @test
	 * @expectedException \UnexpectedValueException
	 */
	public function invalidIdentifierIsRecognizedOnAdding() {
		$identifier = uniqid('identifier__');
		$dataProviderMock = $this->getMock('stdClass');

		$this->dataProviderCollection->add($identifier, get_class($dataProviderMock));
	}

	/**
	 * @test
	 * @expectedException \LogicException
	 */
	public function invalidInterfaceIsRecognizedOnAdding() {
		$identifier = uniqid('identifier');
		$dataProviderMock = $this->getMock('stdClass');

		$this->dataProviderCollection->add($identifier, get_class($dataProviderMock));
	}

	/**
	 * @test
	 */
	public function defaultBackendLayoutIsFound() {
		$backendLayoutIdentifier = uniqid('identifier');

		$dataProviderMock = $this->getMock('TYPO3\\CMS\\Backend\\View\\BackendLayout\\DefaultDataProvider', array('getBackendLayout'), array(), '', FALSE);
		$backendLayoutMock = $this->getMock('TYPO3\\CMS\\Backend\\View\\BackendLayout\\BackendLayout', array('getIdentifier'), array(), '', FALSE);
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
	public function providedBackendLayoutIsFound() {
		$dataProviderIdentifier = uniqid('custom');
		$backendLayoutIdentifier = uniqid('identifier');

		$dataProviderMock = $this->getMock('TYPO3\\CMS\\Backend\\View\\BackendLayout\\DefaultDataProvider', array('getBackendLayout'), array(), '', FALSE);
		$backendLayoutMock = $this->getMock('TYPO3\\CMS\\Backend\\View\\BackendLayout\\BackendLayout', array('getIdentifier'), array(), '', FALSE);
		$backendLayoutMock->expects($this->any())->method('getIdentifier')->will($this->returnValue($backendLayoutIdentifier));
		$dataProviderMock->expects($this->once())->method('getBackendLayout')->will($this->returnValue($backendLayoutMock));

		$this->dataProviderCollection->add($dataProviderIdentifier, $dataProviderMock);
		$providedBackendLayout = $this->dataProviderCollection->getBackendLayout($dataProviderIdentifier . '__' . $backendLayoutIdentifier, 123);

		$this->assertNotNull($providedBackendLayout);
		$this->assertEquals($backendLayoutIdentifier, $providedBackendLayout->getIdentifier());
	}

}