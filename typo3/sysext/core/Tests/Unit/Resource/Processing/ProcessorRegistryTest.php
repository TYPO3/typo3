<?php
namespace TYPO3\CMS\Core\Tests\Unit\Resource\Processing;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2014 Frans Saris <franssaris@gmail.com>
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
 * Test cases for ProcessorRegistry
 */
class ProcessorRegistryTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	public function setUp() {
		parent::setUp();
		unset($GLOBALS['TYPO3_CONF_VARS']['SYS']['fal']['fileProcessors']);
	}

	/**
	 * Initialize an FileProcessorRegistry and mock createFileProcessorInstance()
	 *
	 * @param array $createdFileProcessorInstances
	 * @return \PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Resource\Processing\FileProcessorRegistry
	 */
	protected function getTestFileProcessorRegistry(array $createdFileProcessorInstances = array()) {
		$fileProcessorRegistry = $this->getMockBuilder('TYPO3\\CMS\\Core\\Resource\\Processing\\FileProcessorRegistry')
			->setMethods(array('createFileProcessorInstance'))
			->getMock();

		if (count($createdFileProcessorInstances)) {
			$fileProcessorRegistry->expects($this->any())
				->method('createFileProcessorInstance')
				->will($this->returnValueMap($createdFileProcessorInstances));
		}

		return $fileProcessorRegistry;
	}

	/**
	 * @test
	 */
	public function registeredFileProcessorClassCanBeRetrieved() {
		$processorClass = uniqid('myProcessor');
		$processorObject = $this->getMock('TYPO3\\CMS\\Core\\Resource\\Processing\\FileProcessorInterface', array(), array(), $processorClass);

		$fileProcessorRegistry = $this->getTestFileProcessorRegistry(array(array($processorClass, $processorObject)));

		$fileProcessorRegistry->registerFileProcessorClass($processorClass);
		$this->assertContains($processorObject, $fileProcessorRegistry->getFileProcessors(), '', FALSE, FALSE);
	}

	/**
	 * @test
	 */
	public function registeredLocalProcessorClassCanBeRetrieved() {
		$processorClass = uniqid('myProcessor');
		$driverType = 'Local';
		$processorObject = $this->getMock('TYPO3\\CMS\\Core\\Resource\\Processing\\FileProcessorInterface', array(), array(), $processorClass);
		$processorObject->expects($this->any())->method('getDriverRestrictions')->will($this->returnValue(array($driverType)));

		$fileProcessorRegistry = $this->getTestFileProcessorRegistry(array(array($processorClass, $processorObject)));

		$fileProcessorRegistry->registerFileProcessorClass($processorClass);
		$this->assertContains($processorObject, $fileProcessorRegistry->getFileProcessorsWithDriverSupport($driverType), '', FALSE, FALSE);
	}

	/**
	 * @test
	 */
	public function registeredLocalProcessorClassDoesNotGetRetrievedWhenAskingForOtherDriverType() {
		$processorClass = uniqid('myRemoteProcessor');
		$processorObject = $this->getMock('TYPO3\\CMS\\Core\\Resource\\Processing\\FileProcessorInterface', array(), array(), $processorClass);
		$processorObject->expects($this->any())->method('getDriverRestrictions')->will($this->returnValue(array('Remote')));

		$fileProcessorRegistry = $this->getTestFileProcessorRegistry(array(array($processorClass, $processorObject)));

		$fileProcessorRegistry->registerFileProcessorClass($processorClass);
		$this->assertNotContains($processorObject, $fileProcessorRegistry->getFileProcessorsWithDriverSupport('Local'), '', FALSE, FALSE);
	}

	/**
	 * @test
	 * @expectedException \InvalidArgumentException
	 * @expectedExceptionCode 1393614709
	 */
	public function registerFileProcessorClassThrowsExceptionIfClassDoesNotExist() {
		$fileProcessorRegistry = $this->getTestFileProcessorRegistry();
		$fileProcessorRegistry->registerFileProcessorClass(uniqid());
	}

	/**
	 * @test
	 * @expectedException \InvalidArgumentException
	 * @expectedExceptionCode 1393614710
	 */
	public function registerFileProcessorClassThrowsExceptionIfClassDoesNotImplementRightInterface() {
		$className = __CLASS__;
		$fileProcessorRegistry = $this->getTestFileProcessorRegistry();
		$fileProcessorRegistry->registerFileProcessorClass($className);
	}

	/**
	 * @test
	 */
	public function processorRegistryIsInitializedWithPreconfiguredFileProcessors() {
		$processorClass = uniqid('myRemoteProcessor');
		$processorObject = $this->getMock('TYPO3\\CMS\\Core\\Resource\\Processing\\FileProcessorInterface', array(), array(), $processorClass);

			// set TYPO3_CONF_VARS
		$GLOBALS['TYPO3_CONF_VARS']['SYS']['fal']['fileProcessors'] = array(
			$processorClass
		);

		$fileProcessorRegistry = $this->getTestFileProcessorRegistry(array(array($processorClass, $processorObject)));
		$this->assertContains($processorObject, $fileProcessorRegistry->getFileProcessors(), '', FALSE, FALSE);
	}

	/**
	 * @test
	 */
	public function registerFileProcessorClassWithHighestPriorityIsFirstInResult() {
		$processorClass1 = uniqid('myProcessor1');
		$processorObject1 = $this->getMock('TYPO3\\CMS\\Core\\Resource\\Processing\\FileProcessorInterface', array(), array(), $processorClass1);
		$processorObject1->expects($this->any())->method('getPriority')->will($this->returnValue(1));

		$processorClass2 = uniqid('myProcessor2');
		$processorObject2 = $this->getMock('TYPO3\\CMS\\Core\\Resource\\Processing\\FileProcessorInterface', array(), array(), $processorClass2);
		$processorObject2->expects($this->any())->method('getPriority')->will($this->returnValue(10));

		$processorClass3 = uniqid('myProcessor3');
		$processorObject3 = $this->getMock('TYPO3\\CMS\\Core\\Resource\\Processing\\FileProcessorInterface', array(), array(), $processorClass3);
		$processorObject3->expects($this->any())->method('getPriority')->will($this->returnValue(2));

		$createdFileProcessorInstances = array(
			array($processorClass1, $processorObject1),
			array($processorClass2, $processorObject2),
			array($processorClass3, $processorObject3),
		);

		$fileProcessorRegistry = $this->getTestFileProcessorRegistry($createdFileProcessorInstances);
		$fileProcessorRegistry->registerFileProcessorClass($processorClass1);
		$fileProcessorRegistry->registerFileProcessorClass($processorClass2);
		$fileProcessorRegistry->registerFileProcessorClass($processorClass3);

		$processors = $fileProcessorRegistry->getFileProcessors();
		$this->assertTrue($processors[0] instanceof $processorClass2);
		$this->assertTrue($processors[1] instanceof $processorClass3);
		$this->assertTrue($processors[2] instanceof $processorClass1);
	}

	/**
	 * @test
	 */
	public function registeredFileProcessorClassWithSamePriorityAreReturnedInSameOrderAsTheyWereAdded() {
		$processorClass1 = uniqid('myProcessor1');
		$processorObject1 = $this->getMock('TYPO3\\CMS\\Core\\Resource\\Processing\\FileProcessorInterface', array(), array(), $processorClass1);
		$processorObject1->expects($this->any())->method('getPriority')->will($this->returnValue(1));

		$processorClass2 = uniqid('myProcessor2');
		$processorObject2 = $this->getMock('TYPO3\\CMS\\Core\\Resource\\Processing\\FileProcessorInterface', array(), array(), $processorClass2);
		$processorObject2->expects($this->any())->method('getPriority')->will($this->returnValue(1));

		$createdFileProcessorInstances = array(
			array($processorClass1, $processorObject1),
			array($processorClass2, $processorObject2),
		);

		$fileProcessorRegistry = $this->getTestFileProcessorRegistry($createdFileProcessorInstances);
		$fileProcessorRegistry->registerFileProcessorClass($processorClass1);
		$fileProcessorRegistry->registerFileProcessorClass($processorClass2);

		$processors = $fileProcessorRegistry->getFileProcessors();
		$this->assertTrue($processors[0] instanceof $processorClass1);
		$this->assertTrue($processors[1] instanceof $processorClass2);
	}
}

