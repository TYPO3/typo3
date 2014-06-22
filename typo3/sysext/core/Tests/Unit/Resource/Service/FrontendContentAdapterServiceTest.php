<?php
namespace TYPO3\CMS\Core\Tests\Unit\Resource\Service;

/**
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
 * Tests for the Frontend Content Adapter
 */
class FrontendContentAdapterServiceTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {
	/**
	/**
	 * @var array A backup of registered singleton instances
	 */
	protected $singletonInstances = array();

	/**
	 * @var \TYPO3\CMS\Frontend\Page\PageRepository|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected $pageRepositoryMock;

	/**
	 * Saving the singletons
	 */
	public function setUp() {
		$this->pageRepositoryMock = $this->getMock('TYPO3\\CMS\\Frontend\\Page\\PageRepository');
		$GLOBALS['TSFE'] = new \stdClass;
		$GLOBALS['TSFE']->sys_page = $this->pageRepositoryMock;
	}

	/**
	 * @test
	 */
	public function emptyRelationResetsLegacyFields() {
		$this->pageRepositoryMock->expects($this->any())
			->method('getFileReferences')
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
		$this->pageRepositoryMock->expects($this->any())
			->method('getFileReferences')
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
		$this->pageRepositoryMock->expects($this->any())
			->method('getFileReferences')
			->will($this->returnValue(array($fileReference)));

		\TYPO3\CMS\Core\Resource\Service\FrontendContentAdapterService::modifyDBRow($dbRow, 'tt_content');
		$this->assertSame($expectedCaption, $dbRow['imagecaption']);
	}

	/**
	 * @test
	 */
	public function registerAdditionalTypeForMigrationAddsTypeToArray() {

		$migrateFields = array(
			'testtable' => array(
				'testfield' => array(
					'paths' => 'oldfield',
					'__typeMatch' => array(
						'typeField' => 'mytypefield',
						'types' => array('mytype'),
					)
				)
			),
		);

		$expectedResult = array(
			'testtable' => array(
				'testfield' => array(
					'paths' => 'oldfield',
					'__typeMatch' => array(
						'typeField' => 'mytypefield',
						'types' => array('mytype', 'mytype2'),
					)
				)
			),
		);

		$frontendContentAdapterService = $this->getAccessibleFrontendContentAdapterServiceWithEmptyConfiguration();
		$frontendContentAdapterService->_setStatic('migrateFields', $migrateFields);
		$frontendContentAdapterService->registerAdditionalTypeForMigration('testtable', 'testfield', 'mytype2');
		$this->assertEquals($expectedResult, $frontendContentAdapterService->_getStatic('migrateFields'));
	}

	/**
	 * @test
	 * @expectedException \RuntimeException
	 */
	public function registerAdditionalTypeForMigrationThrowsExceptionIfNoConfigurationAvailable() {
		$frontendContentAdapterService = $this->getAccessibleFrontendContentAdapterServiceWithEmptyConfiguration();
		$frontendContentAdapterService->registerAdditionalTypeForMigration('testtable', 'testfield', 'mytype2');
	}

	/**
	 * @test
	 * @expectedException \RuntimeException
	 */
	public function registerAdditionalTypeForMigrationThrowsExceptionIfNoTypeConfigurationAvailable() {

		$migrateFields = array(
			'testtable' => array(
				'testfield' => array(
					'paths' => 'oldfield',
				)
			),
		);

		$frontendContentAdapterService = $this->getAccessibleFrontendContentAdapterServiceWithEmptyConfiguration();
		$frontendContentAdapterService->_setStatic('migrateFields', $migrateFields);
		$frontendContentAdapterService->registerAdditionalTypeForMigration('testtable', 'testfield', 'mytype2');
	}

	/**
	 * @test
	 * @dataProvider registerFieldForMigrationAddsCorrectConfigurationDataProvider
	 */
	public function registerFieldForMigrationAddsCorrectConfiguration($expectedResult, $table, $field, $migrationFields, $oldFieldName, $typeField, $types) {
		$frontendContentAdapterService = $this->getAccessibleFrontendContentAdapterServiceWithEmptyConfiguration();
		$frontendContentAdapterService::registerFieldForMigration($table, $field, $migrationFields, $oldFieldName, $typeField, $types);
		$newConfiguration = $frontendContentAdapterService->_getStatic('migrateFields');
		$this->assertEquals($expectedResult, $newConfiguration);
	}

	/**
	 * Data provider for registerFieldForMigrationAddsCorrectConfiguration
	 *
	 * @return array
	 */
	public function registerFieldForMigrationAddsCorrectConfigurationDataProvider() {
		return array(
			'table without type column' => array(
				array(
					'tablename' => array(
						'newfield' => array(
							'paths' => 'oldfield',
						),
					),
				),
				'tablename',
				'newfield',
				'paths',
				'oldfield',
				NULL,
				array()
			),
			'table with type column' => array(
				array(
					'tablename' => array(
						'newfield' => array(
							'paths' => 'oldfield',
							'__typeMatch' => array(
								'typeField' => 'typecolumn',
								'types' => array('firsttype'),
							)
						),
					),
				),
				'tablename',
				'newfield',
				'paths',
				'oldfield',
				'typecolumn',
				array('firsttype'),
			),
		);
	}

	/**
	 * @test
	 * @expectedException \InvalidArgumentException
	 */
	public function registerFieldForMigrationThrowsExceptionForInvalidMigrationField() {
		$frontendContentAdapterService = $this->getAccessibleFrontendContentAdapterServiceWithEmptyConfiguration();
		$frontendContentAdapterService::registerFieldForMigration('table', 'field', 'invalidfield', 'oldfield');
	}

	/**
	 * Creates an accessible mock of the FrontendContentAdapterService class
	 * and sets the migrateFields property to an empty array.
	 *
	 * @return \TYPO3\CMS\Core\Resource\Service\FrontendContentAdapterService|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface
	 */
	protected function getAccessibleFrontendContentAdapterServiceWithEmptyConfiguration() {
		$frontendContentAdapterService = $this->getAccessibleMock('TYPO3\\CMS\\Core\\Resource\\Service\\FrontendContentAdapterService', array('dummy'));
		$frontendContentAdapterService->_setStatic('migrateFields', array());
		return $frontendContentAdapterService;
	}
}
