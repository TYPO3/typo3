<?php
namespace TYPO3\CMS\Core\Tests\Unit\Resource;

/***************************************************************
 * Copyright notice
 *
 * (c) 2013 Helmut Hummel <helmut.hummel@typo3.org>
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/


/**
 * Testcase for the file class of the TYPO3 FAL
 *
 */
class FileReferenceTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var array A backup of registered singleton instances
	 */
	protected $singletonInstances = array();

	public function setUp() {
		$this->singletonInstances = \TYPO3\CMS\Core\Utility\GeneralUtility::getSingletonInstances();
		\TYPO3\CMS\Core\Utility\GeneralUtility::purgeInstances();
	}

	public function tearDown() {
		\TYPO3\CMS\Core\Utility\GeneralUtility::resetSingletonInstances($this->singletonInstances);
	}

	/**
	 * @param array $fileReferenceProperties
	 * @param array $originalFileProperties
	 * @return \TYPO3\CMS\Core\Resource\FileReference|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface
	 */
	protected function prepareFixture(array $fileReferenceProperties, array $originalFileProperties) {
		$fixture = $this->getAccessibleMock('TYPO3\\CMS\\Core\\Resource\\FileReference', array('dummy'), array(), '', FALSE);
		$originalFileMock = $this->getAccessibleMock('TYPO3\\CMS\\Core\\Resource\\File', array(), array(), '', FALSE);
		$originalFileMock->expects($this->any())
			->method('getProperties')
			->will($this->returnValue($originalFileProperties)
		);
		$fixture->_set('originalFile', $originalFileMock);
		$fixture->_set('propertiesOfFileReference', $fileReferenceProperties);

		return $fixture;
	}

	/**
	 * @return array
	 */
	public function propertiesDataProvider() {
		return array(
			'File properties correctly override file reference properties' => array(
				array(
					'title' => NULL,
					'description' => 'fileReferenceDescription',
					'alternative' => '',
				),
				array(
					'title' => 'fileTitle',
					'description' => 'fileDescription',
					'alternative' => 'fileAlternative',
					'file_only_property' => 'fileOnlyPropertyValue',
				),
				array(
					'title' => 'fileTitle',
					'description' => 'fileReferenceDescription',
					'alternative' => '',
					'file_only_property' => 'fileOnlyPropertyValue',
				),
			)
		);
	}

	/**
	 * @param array $fileReferenceProperties
	 * @param array $originalFileProperties
	 * @param array $expectedMergedProperties
	 * @test
	 * @dataProvider propertiesDataProvider
	 */
	public function getPropertiesReturnsMergedPropertiesAndRespectsNullValues(array $fileReferenceProperties, array $originalFileProperties, array $expectedMergedProperties) {
		$fixture = $this->prepareFixture($fileReferenceProperties, $originalFileProperties);
		$actual = $fixture->getProperties();
		$this->assertSame($expectedMergedProperties, $actual);
	}

	/**
	 * @param array $fileReferenceProperties
	 * @param array $originalFileProperties
	 * @param array $expectedMergedProperties
	 * @test
	 * @dataProvider propertiesDataProvider
	 */
	public function hasPropertyReturnsTrueForAllMergedPropertyKeys($fileReferenceProperties, $originalFileProperties, $expectedMergedProperties) {
		$fixture = $this->prepareFixture($fileReferenceProperties, $originalFileProperties);
		foreach (array_keys($expectedMergedProperties) as $key) {
			$this->assertTrue($fixture->hasProperty($key));
		}
	}

	/**
	 * @param array $fileReferenceProperties
	 * @param array $originalFileProperties
	 * @param array $expectedMergedProperties
	 * @test
	 * @dataProvider propertiesDataProvider
	 */
	public function getPropertyReturnsAllMergedPropertyKeys($fileReferenceProperties, $originalFileProperties, $expectedMergedProperties) {
		$fixture = $this->prepareFixture($fileReferenceProperties, $originalFileProperties);
		foreach ($expectedMergedProperties as $key => $expectedValue) {
			$this->assertSame($expectedValue, $fixture->getProperty($key));
		}
	}

	/**
	 * @param array $fileReferenceProperties
	 * @param array $originalFileProperties
	 * @param array $expectedMergedProperties
	 * @test
	 * @dataProvider propertiesDataProvider
	 * @expectedException \InvalidArgumentException
	 */
	public function getPropertyThrowsExceptionForNotAvailableProperty($fileReferenceProperties, $originalFileProperties) {
		$fixture = $this->prepareFixture($fileReferenceProperties, $originalFileProperties);
		$fixture->getProperty(uniqid('nothingHere'));
	}

	/**
	 * @param array $fileReferenceProperties
	 * @param array $originalFileProperties
	 * @param array $expectedMergedProperties
	 * @test
	 * @dataProvider propertiesDataProvider
	 */
	public function getPropertyDoesNotThrowExceptionForPropertyOnlyAvailableInOriginalFile($fileReferenceProperties, $originalFileProperties) {
		$fixture = $this->prepareFixture($fileReferenceProperties, $originalFileProperties);
		$this->assertSame($originalFileProperties['file_only_property'], $fixture->getProperty('file_only_property'));
	}

	/**
	 * @param array $fileReferenceProperties
	 * @param array $originalFileProperties
	 * @param array $expectedMergedProperties
	 * @test
	 * @dataProvider propertiesDataProvider
	 * @expectedException \InvalidArgumentException
	 */
	public function getReferencePropertyThrowsExceptionForPropertyOnlyAvailableInOriginalFile($fileReferenceProperties, $originalFileProperties) {
		$fixture = $this->prepareFixture($fileReferenceProperties, $originalFileProperties);
		$fixture->getReferenceProperty('file_only_property');
	}
}

?>