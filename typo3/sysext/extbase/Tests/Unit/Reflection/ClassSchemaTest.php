<?php
namespace TYPO3\CMS\Extbase\Tests\Unit\Reflection;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Anja Leichsenring <anja.leichsenring@typo3.org>
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
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Test case
 */
class ClassSchemaTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function classSchemaForModelIsSetAggregateRootIfRepositoryClassIsFoundForNamespacedClasses() {
		$className = uniqid('BazFixture');
		eval ('
			namespace Foo\\Bar\\Domain\\Model;
			class ' . $className . ' extends \\TYPO3\\CMS\\Extbase\\DomainObject\\AbstractEntity {}
		');
		eval ('
			namespace Foo\\Bar\\Domain\\Repository;
			class ' . $className . 'Repository {}
		');

		/** @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface|\PHPUnit_Framework_MockObject_MockObject $objectManager */
		$objectManager = $this->getMock('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
		$mockClassSchema = $this->getAccessibleMock('TYPO3\\CMS\\Extbase\\Reflection\\ClassSchema', array('dummy'), array('Foo\\Bar\\Domain\\Model\\' . $className));
		$mockClassSchema->_set('typeHandlingService', $this->getMock('TYPO3\\CMS\\Extbase\\Service\\TypeHandlingService'));
		$objectManager->expects($this->once())->method('get')->will($this->returnValue($mockClassSchema));

		/** @var \TYPO3\CMS\Extbase\Reflection\ReflectionService $service */
		$service = $this->getAccessibleMock('TYPO3\\CMS\\Extbase\\Reflection\\ReflectionService', array('dummy'));
		$service->_set('objectManager', $objectManager);
		$classSchema = $service->getClassSchema('Foo\\Bar\\Domain\\Model\\' . $className);
		$this->assertTrue($classSchema->isAggregateRoot());
	}

	/**
	 * @test
	 */
	public function classSchemaForModelIsSetAggregateRootIfRepositoryClassIsFoundForNotNamespacedClasses() {
		$className = uniqid('BazFixture');
		eval ('
			class Foo_Bar_Domain_Model_' . $className . ' extends \\TYPO3\\CMS\\Extbase\\DomainObject\\AbstractEntity {}
		');
		eval ('
			class Foo_Bar_Domain_Repository_' . $className . 'Repository {}
		');

		/** @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface|\PHPUnit_Framework_MockObject_MockObject $objectManager */
		$objectManager = $this->getMock('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
		$mockClassSchema = $this->getAccessibleMock('TYPO3\\CMS\\Extbase\\Reflection\\ClassSchema', array('dummy'), array('Foo_Bar_Domain_Model_' . $className));
		$mockClassSchema->_set('typeHandlingService', $this->getMock('TYPO3\\CMS\\Extbase\\Service\\TypeHandlingService'));
		$objectManager->expects($this->once())->method('get')->will($this->returnValue($mockClassSchema));

		$service = $this->getAccessibleMock('TYPO3\CMS\Extbase\Reflection\ReflectionService', array('dummy'));
		$service->_set('objectManager', $objectManager);
		$classSchema = $service->getClassSchema('Foo_Bar_Domain_Model_' . $className);
		$this->assertTrue($classSchema->isAggregateRoot());
	}

}
