<?php
namespace TYPO3\CMS\Core\Tests\Resource\Service;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Helmut Hummel <helmut.hummel@typo3.org>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
/**
 * Tests for the Frontend Content Adapter
 */
class FrontendContentAdapterServiceTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {
	/**
	 * @var \PHPUnit_Framework_MockObject_MockObject
	 */
	protected $fileRepositoryMock;

	/**
	 * @var array A backup of registered singleton instances
	 */
	protected $singletonInstances = array();

	/**
	 * Saving the singletons
	 */
	public function setUp() {
		$this->singletonInstances = \TYPO3\CMS\Core\Utility\GeneralUtility::getSingletonInstances();
		$this->fileRepositoryMock = $this->getMock('TYPO3\\CMS\\Core\\Resource\\FileRepository');
		\TYPO3\CMS\Core\Utility\GeneralUtility::setSingletonInstance('TYPO3\\CMS\\Core\\Resource\\FileRepository', $this->fileRepositoryMock);
	}

	/**
	 * Restoring the singletons
	 */
	public function tearDown() {
		\TYPO3\CMS\Core\Utility\GeneralUtility::resetSingletonInstances($this->singletonInstances);
		unset($this->fileRepositoryMock);
	}

	/**
	 * @test
	 */
	public function emptyRelationResetsLegacyFields() {
		$this->fileRepositoryMock->expects($this->any())
			->method('findByRelation')
			->will($this->returnValue(array()));
		$dbRow = array(
			'CType' => 'image',
			'image' => '1'
		);

		\TYPO3\CMS\Core\Resource\Service\FrontendContentAdapterService::modifyDBRow($dbRow, 'tt_content');
		$this->assertEmpty($dbRow['image']);
	}

	/**
	 * @test
	 */
	public function imageFieldIsFilledWithPathOfImage() {
		$fileReference = $this->getMock('TYPO3\\CMS\\Core\\Resource\\FileReference', array(), array(), '', FALSE);
		$fileReference->expects($this->any())
			->method('getOriginalFile')
			->will($this->returnValue($this->getMock('TYPO3\\CMS\\Core\\Resource\\File', array(), array(), '', FALSE)));
		$fileReference->expects($this->any())
			->method('getPublicUrl')
			->will($this->returnValue('path/to/file'));
		$this->fileRepositoryMock->expects($this->any())
			->method('findByRelation')
			->will($this->returnValue(array($fileReference)));
		$dbRow = array(
			'CType' => 'image',
			'image' => '1'
		);

		\TYPO3\CMS\Core\Resource\Service\FrontendContentAdapterService::modifyDBRow($dbRow, 'tt_content');
		$this->assertSame('../../path/to/file', $dbRow['image']);
	}

	public function conteRowsOfDifferentTypesDataProvider() {
		$filePropertiesImage = array(
			'title' => 'Image',
			'description' => 'IMAGE DESCRIPTION',
		);

		$filePropertiesMedia = array(
			'title' => 'Media',
			'description' => 'MEDIA DESCRIPTION',
		);

		return array(
			'Image Element' => array(
				array(
					'CType' => 'image',
					'image' => '',
					'media' => '',
				),
				'IMAGE DESCRIPTION',
				$filePropertiesImage
			),
			'Textpic Element' => array(
				array(
					'CType' => 'textpic',
					'image' => '',
					'media' => '',
				),
				'IMAGE DESCRIPTION',
				$filePropertiesImage
			),
			'Uploads Element' => array(
				array(
					'CType' => 'uploads',
					'image' => '',
					'media' => '',
				),
				'MEDIA DESCRIPTION',
				$filePropertiesMedia
			),
		);
	}

	/**
	 * @test
	 * @dataProvider conteRowsOfDifferentTypesDataProvider
	 */
	public function migrationOfLegacyFieldsIsOnlyDoneWhenRelationFieldIsVisibleInType($dbRow, $expectedCaption, $fileProperties) {
		$fileReference = $this->getMock('TYPO3\\CMS\\Core\\Resource\\FileReference', array(), array(), '', FALSE);
		$fileReference->expects($this->once())
			->method('getProperties')
			->will($this->returnValue($fileProperties));
		$fileReference->expects($this->any())
			->method('getOriginalFile')
			->will($this->returnValue($this->getMock('TYPO3\\CMS\\Core\\Resource\\File', array(), array(), '', FALSE)));
		$fileReference->expects($this->any())
			->method('getPublicUrl')
			->will($this->returnValue('path/to/file'));
		$this->fileRepositoryMock->expects($this->any())
			->method('findByRelation')
			->will($this->returnValue(array($fileReference)));

		\TYPO3\CMS\Core\Resource\Service\FrontendContentAdapterService::modifyDBRow($dbRow, 'tt_content');
		$this->assertSame($expectedCaption, $dbRow['imagecaption']);
	}

}


?>