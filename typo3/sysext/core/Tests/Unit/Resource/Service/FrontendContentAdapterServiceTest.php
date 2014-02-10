<?php
namespace TYPO3\CMS\Core\Tests\Unit\Resource\Service;

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
	 * @var string
	 */
	protected $accessibleFixtureName;

	/**
	 * Saving the singletons
	 */
	public function setUp() {
		$this->accessibleFixtureName = $this->buildAccessibleProxy('TYPO3\\CMS\\Core\\Resource\\Service\\FrontendContentAdapterService');
		$this->singletonInstances = \TYPO3\CMS\Core\Utility\GeneralUtility::getSingletonInstances();
		$this->fileRepositoryMock = $this->getMock('TYPO3\\CMS\\Core\\Resource\\FileRepository');
		\TYPO3\CMS\Core\Utility\GeneralUtility::setSingletonInstance('TYPO3\\CMS\\Core\\Resource\\FileRepository', $this->fileRepositoryMock);
	}

	/**
	 * Restoring the singletons
	 */
	public function tearDown() {
		call_user_func_array($this->accessibleFixtureName . '::_setStatic', array('migrationCache', array()));
		\TYPO3\CMS\Core\Utility\GeneralUtility::resetSingletonInstances($this->singletonInstances);
		parent::tearDown();
	}

	/**
	 * @test
	 */
	public function customTableIsNotConsidered() {
		$dbRow = array(
			'uid' => uniqid(),
		);
		/* @see \TYPO3\CMS\Core\Resource\Service\FrontendContentAdapterService::modifyDBRow */
		$result = call_user_func_array($this->accessibleFixtureName . '::modifyDBRow', array(&$dbRow, uniqid('tx_testtable')));
		$this->assertNull($result);
	}

	/**
	 * @test
	 */
	public function recordWithoutUidIsNotConsidered() {
		$dbRow = array();
		/* @see \TYPO3\CMS\Core\Resource\Service\FrontendContentAdapterService::modifyDBRow */
		$result = call_user_func_array($this->accessibleFixtureName . '::modifyDBRow', array(&$dbRow, 'tt_content'));
		$this->assertNull($result);
	}

	/**
	 * @test
	 */
	public function emptyRelationResetsLegacyFields() {
		$this->fileRepositoryMock->expects($this->any())
			->method('findByRelation')
			->will($this->returnValue(array()));
		$dbRow = array(
			'uid' => uniqid(),
			'CType' => 'image',
			'image' => '1'
		);

		/* @see \TYPO3\CMS\Core\Resource\Service\FrontendContentAdapterService::modifyDBRow */
		call_user_func_array($this->accessibleFixtureName . '::modifyDBRow', array(&$dbRow, 'tt_content'));
		$this->assertEmpty($dbRow['image']);
	}

	/**
	 * @test
	 */
	public function processedRecordsAreCached() {
		// Asserting that this is only called once,
		// since second call shall be delivered from cache
		$this->fileRepositoryMock->expects($this->once())
			->method('findByRelation')
			->will($this->returnValue(array()));

		$testUid = uniqid();

		$dbRow = array(
			'uid' => $testUid,
			'CType' => 'image',
			'image' => '1',
		);

		/* @see \TYPO3\CMS\Core\Resource\Service\FrontendContentAdapterService::modifyDBRow */
		call_user_func_array($this->accessibleFixtureName . '::modifyDBRow', array(&$dbRow, 'tt_content'));

		$dbRow = array(
			'uid' => $testUid,
			'CType' => 'image',
			'image' => '1',
		);

		/* @see \TYPO3\CMS\Core\Resource\Service\FrontendContentAdapterService::modifyDBRow */
		call_user_func_array($this->accessibleFixtureName . '::modifyDBRow', array(&$dbRow, 'tt_content'));
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
			'uid' => uniqid(),
			'CType' => 'image',
			'image' => '1'
		);

		/* @see \TYPO3\CMS\Core\Resource\Service\FrontendContentAdapterService::modifyDBRow */
		call_user_func_array($this->accessibleFixtureName . '::modifyDBRow', array(&$dbRow, 'tt_content'));
		$this->assertSame('../../path/to/file', $dbRow['image']);
	}

	public function contentRowsOfDifferentTypesDataProvider() {
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
					'uid' => uniqid(),
					'CType' => 'image',
					'image' => '',
					'media' => '',
				),
				'IMAGE DESCRIPTION',
				$filePropertiesImage
			),
			'Textpic Element' => array(
				array(
					'uid' => uniqid(),
					'CType' => 'textpic',
					'image' => '',
					'media' => '',
				),
				'IMAGE DESCRIPTION',
				$filePropertiesImage
			),
			'Uploads Element' => array(
				array(
					'uid' => uniqid(),
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
	 * @dataProvider contentRowsOfDifferentTypesDataProvider
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

		/* @see \TYPO3\CMS\Core\Resource\Service\FrontendContentAdapterService::modifyDBRow */
		call_user_func_array($this->accessibleFixtureName . '::modifyDBRow', array(&$dbRow, 'tt_content'));
		$this->assertSame($expectedCaption, $dbRow['imagecaption']);
	}

}
