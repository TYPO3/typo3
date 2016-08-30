<?php
namespace TYPO3\CMS\Extbase\Tests\Unit\Reflection;

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
 * Test case
 */
class ClassSchemaTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @test
     */
    public function classSchemaForModelIsSetAggregateRootIfRepositoryClassIsFoundForNamespacedClasses()
    {
        $className = $this->getUniqueId('BazFixture');
        eval('
			namespace Foo\\Bar\\Domain\\Model;
			class ' . $className . ' extends \\' . \TYPO3\CMS\Extbase\DomainObject\AbstractEntity::class . ' {}
		');
        eval('
			namespace Foo\\Bar\\Domain\\Repository;
			class ' . $className . 'Repository {}
		');

        /** @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface|\PHPUnit_Framework_MockObject_MockObject $objectManager */
        $objectManager = $this->getMock(\TYPO3\CMS\Extbase\Object\ObjectManager::class);
        $mockClassSchema = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Reflection\ClassSchema::class, ['dummy'], ['Foo\\Bar\\Domain\\Model\\' . $className]);
        $objectManager->expects($this->once())->method('get')->will($this->returnValue($mockClassSchema));

        /** @var \TYPO3\CMS\Extbase\Reflection\ReflectionService $service */
        $service = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Reflection\ReflectionService::class, ['dummy']);
        $service->_set('objectManager', $objectManager);
        $classSchema = $service->getClassSchema('Foo\\Bar\\Domain\\Model\\' . $className);
        $this->assertTrue($classSchema->isAggregateRoot());
    }

    /**
     * @test
     */
    public function classSchemaForModelIsSetAggregateRootIfRepositoryClassIsFoundForNotNamespacedClasses()
    {
        $className = $this->getUniqueId('BazFixture');
        eval('
			class Foo_Bar_Domain_Model_' . $className . ' extends \\' . \TYPO3\CMS\Extbase\DomainObject\AbstractEntity::class . ' {}
		');
        eval('
			class Foo_Bar_Domain_Repository_' . $className . 'Repository {}
		');

        /** @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface|\PHPUnit_Framework_MockObject_MockObject $objectManager */
        $objectManager = $this->getMock(\TYPO3\CMS\Extbase\Object\ObjectManager::class);
        $mockClassSchema = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Reflection\ClassSchema::class, ['dummy'], ['Foo_Bar_Domain_Model_' . $className]);
        $objectManager->expects($this->once())->method('get')->will($this->returnValue($mockClassSchema));

        $service = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Reflection\ReflectionService::class, ['dummy']);
        $service->_set('objectManager', $objectManager);
        $classSchema = $service->getClassSchema('Foo_Bar_Domain_Model_' . $className);
        $this->assertTrue($classSchema->isAggregateRoot());
    }
}
