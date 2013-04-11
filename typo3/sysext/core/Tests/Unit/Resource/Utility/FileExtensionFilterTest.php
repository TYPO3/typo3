<?php
namespace TYPO3\CMS\Core\Tests\Unit\Resource\Utility;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012-2013 Oliver Hader <oliver.hader@typo3.org>
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
 *  A copy is found in the textfile GPL.txt and important notices to the license
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
 * Test suite for filtering files by their extensions.
 *
 * @author Oliver Hader <oliver.hader@typo3.org>
 */
class FileExtensionFilterTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var array A backup of registered singleton instances
	 */
	protected $singletonInstances = array();

	/**
	 * @var \TYPO3\CMS\Core\Resource\Filter\FileExtensionFilter
	 */
	protected $filter;

	/**
	 * @var array
	 */
	protected $parameters;

	/**
	 * @var \TYPO3\CMS\Core\DataHandling\DataHandler|PHPUnit_Framework_MockObject_MockObject
	 */
	protected $tceMainMock;

	/**
	 * @var \TYPO3\CMS\Core\Resource\ResourceFactory|PHPUnit_Framework_MockObject_MockObject
	 */
	protected $fileFactoryMock;

	/**
	 * Sets up this test suite.
	 */
	protected function setUp() {
		$this->filter = new \TYPO3\CMS\Core\Resource\Filter\FileExtensionFilter();
		$this->tceMainMock = $this->getMock('TYPO3\\CMS\\Core\\DataHandling\\DataHandler', array('deleteAction'), array());
		$this->fileFactoryMock = $this->getMock('TYPO3\\CMS\\Core\\Resource\\ResourceFactory', array('getFileReferenceObject'), array());
		$this->singletonInstances = \TYPO3\CMS\Core\Utility\GeneralUtility::getSingletonInstances();
		\TYPO3\CMS\Core\Utility\GeneralUtility::setSingletonInstance('TYPO3\\CMS\\Core\\Resource\\ResourceFactory', $this->fileFactoryMock);
	}

	/**
	 * Cleans up this test suite.
	 */
	protected function tearDown() {
		unset($this->fileFactoryMock);
		unset($this->tceMainMock);
		unset($this->parameters);
		unset($this->filter);
		\TYPO3\CMS\Core\Utility\GeneralUtility::resetSingletonInstances($this->singletonInstances);
	}

	/**
	 * @param array|string $allowed
	 * @param array|string $disallowed
	 * @param array|string $values
	 * @test
	 * @dataProvider invalidInlineChildrenFilterParametersDataProvider
	 */
	public function areInlineChildrenFilteredWithInvalidParameters($allowed, $disallowed, $values) {
		$this->parameters = array(
			'allowedFileExtensions' => $allowed,
			'disallowedFileExtensions' => $disallowed,
			'values' => $values
		);
		$this->tceMainMock->expects($this->never())->method('deleteAction');
		$this->fileFactoryMock->expects($this->never())->method('getFileReferenceObject');
		$this->filter->filterInlineChildren($this->parameters, $this->tceMainMock);
	}

	/**
	 * @return array
	 */
	public function invalidInlineChildrenFilterParametersDataProvider() {
		return array(
			array(NULL, NULL, NULL),
			array('', '', array(0, '', NULL, FALSE)),
			array(NULL, NULL, array(0, '', NULL, FALSE))
		);
	}

}

?>