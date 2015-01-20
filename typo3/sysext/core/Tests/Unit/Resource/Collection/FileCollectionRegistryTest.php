<?php
namespace TYPO3\CMS\Core\Tests\Unit\Resource\Collection;

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
 * Test cases for FileCollectionRegistry
 */
class FileCollectionRegistryTest extends \TYPO3\CMS\Core\Tests\Unit\Resource\BaseTestCase {

	/**
	 * @var \TYPO3\CMS\Core\Resource\Collection\FileCollectionRegistry
	 */
	protected $testSubject;

	public function setUp() {
		$this->initializeTestSubject();
	}

	protected function initializeTestSubject() {
		$this->testSubject = new \TYPO3\CMS\Core\Resource\Collection\FileCollectionRegistry();
	}

	/**
	 * @test
	 */
	public function registeredFileCollectionClassesCanBeRetrieved() {
		$className = get_class($this->getMockForAbstractClass('TYPO3\\CMS\\Core\\Resource\\Collection\\AbstractFileCollection'));
		$this->testSubject->registerFileCollectionClass($className, 'foobar');
		$returnedClassName = $this->testSubject->getFileCollectionClass('foobar');
		$this->assertEquals($className, $returnedClassName);
	}

	/**
	 * @test
	 * @expectedException InvalidArgumentException
	 * @expectedExceptionCode 1391295613
	 */
	public function registerFileCollectionClassThrowsExceptionIfClassDoesNotExist() {
		$this->testSubject->registerFileCollectionClass($this->getUniqueId(), substr($this->getUniqueId(), 0, 30));
	}

	/**
	 * @test
	 * @expectedException InvalidArgumentException
	 * @expectedExceptionCode 1391295611
	 */
	public function registerFileCollectionClassThrowsExceptionIfTypeIsTooLong() {
		$className = get_class($this->getMockForAbstractClass('TYPO3\\CMS\\Core\\Resource\\Collection\\AbstractFileCollection'));
		$type = str_pad('', 40);
		$this->testSubject->registerFileCollectionClass($className, $type);
	}

	/**
	 * @test
	 * @expectedException InvalidArgumentException
	 * @expectedExceptionCode 1391295643
	 */
	public function registerFileCollectionClassThrowsExceptionIfTypeIsAlreadyRegistered() {
		$className = get_class($this->getMockForAbstractClass('TYPO3\\CMS\\Core\\Resource\\Collection\\AbstractFileCollection'));
		$className2 = get_class($this->getMockForAbstractClass('TYPO3\\CMS\\Core\\Resource\\Collection\\StaticFileCollection'));
		$this->testSubject->registerFileCollectionClass($className, 'foobar');
		$this->testSubject->registerFileCollectionClass($className2, 'foobar');
	}

	/**
	 * @test
	 */
	public function registerFileCollectionClassOverridesExistingRegisteredFileCollectionClass() {
		$className = get_class($this->getMockForAbstractClass('TYPO3\\CMS\\Core\\Resource\\Collection\\AbstractFileCollection'));
		$className2 = get_class($this->getMockForAbstractClass('TYPO3\\CMS\\Core\\Resource\\Collection\\StaticFileCollection'));
		$this->testSubject->registerFileCollectionClass($className, 'foobar');
		$this->testSubject->registerFileCollectionClass($className2, 'foobar', TRUE);
	}

	/**
	 * @test
	 * @expectedException InvalidArgumentException
	 * @expectedExceptionCode 1391295644
	 */
	public function getFileCollectionClassThrowsExceptionIfClassIsNotRegistered() {
		$this->testSubject->getFileCollectionClass($this->getUniqueId());
	}

	/**
	 * @test
	 */
	public function getFileCollectionClassAcceptsClassNameIfClassIsRegistered() {
		$className = get_class($this->getMockForAbstractClass('TYPO3\\CMS\\Core\\Resource\\Collection\\AbstractFileCollection'));
		$this->testSubject->registerFileCollectionClass($className, 'foobar');
		$this->assertEquals($className, $this->testSubject->getFileCollectionClass('foobar'));
	}

	/**
	 * @test
	 */
	public function fileCollectionRegistryIsInitializedWithPreconfiguredFileCollections() {
		$className = get_class($this->getMockForAbstractClass('TYPO3\\CMS\\Core\\Resource\\Collection\\AbstractFileCollection'));
		$type = substr($this->getUniqueId(), 0, 30);
		$GLOBALS['TYPO3_CONF_VARS']['SYS']['fal']['registeredCollections'] = array(
			$type => $className
		);
		$this->initializeTestSubject();
		$this->assertEquals($className, $this->testSubject->getFileCollectionClass($type));
	}

	/**
	 * @test
	 */
	public function fileCollectionExistsReturnsTrueForAllExistingFileCollections() {
		$className = get_class($this->getMockForAbstractClass('TYPO3\\CMS\\Core\\Resource\\Collection\\AbstractFileCollection'));
		$type = 'foo';
		$GLOBALS['TYPO3_CONF_VARS']['SYS']['fal']['registeredCollections'] = array(
			$type => $className
		);
		$this->initializeTestSubject();
		$this->assertTrue($this->testSubject->fileCollectionTypeExists($type));
		$this->assertFalse($this->testSubject->fileCollectionTypeExists('bar'));
	}

	/**
	 * @test
	 */
	public function fileCollectionExistsReturnsFalseIfFileCollectionDoesNotExist() {
		$GLOBALS['TYPO3_CONF_VARS']['SYS']['fal']['registeredFileCollections'] = array();
		$this->initializeTestSubject();
		$this->assertFalse($this->testSubject->fileCollectionTypeExists($this->getUniqueId()));
	}

	/**
	 * @test
	 */
	public function addNewTypeToTCA() {

		// Create a TCA fixture for sys_file_collection
		$GLOBALS['TCA']['sys_file_collection'] = array(
			'types' => array(
				'typeB' => array('showitem' => 'fieldA, fieldB, fieldC;labelC;paletteC;specialC, fieldD'),
			),
			'columns' => array(
				'type' => array(
					'config' => array(
						'items' => array('Type B', 'typeB')
					)
				)
			)
		);

		$type = 'my_type';
		$label = 'The Label';

		$this->testSubject->addTypeToTCA($type, $label, 'something');

		// check type
		$this->assertEquals('sys_language_uid;;;;1-1-1, l10n_parent, l10n_diffsource, title;;1, type, something', $GLOBALS['TCA']['sys_file_collection']['types']['my_type']['showitem']);

		$indexOfNewType = count($GLOBALS['TCA']['sys_file_collection']['columns']['type']['config']['items']) - 1;

		// check if columns.type.item exist
		$this->assertEquals($type, $GLOBALS['TCA']['sys_file_collection']['columns']['type']['config']['items'][$indexOfNewType][1]);
		$this->assertEquals($label, $GLOBALS['TCA']['sys_file_collection']['columns']['type']['config']['items'][$indexOfNewType][0]);
	}
}