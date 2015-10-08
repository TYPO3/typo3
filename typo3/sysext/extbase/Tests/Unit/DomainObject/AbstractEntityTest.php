<?php
namespace TYPO3\CMS\Extbase\Tests\Unit\DomainObject;

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
class AbstractEntityTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @test
     */
    public function objectIsNotDirtyAfterCallingMemorizeCleanStateWithSimpleProperties()
    {
        $domainObjectName = $this->getUniqueId('DomainObject_');
        $domainObjectNameWithNS = __NAMESPACE__ . '\\' . $domainObjectName;
        eval('namespace ' . __NAMESPACE__ . '; class ' . $domainObjectName . ' extends \\' . \TYPO3\CMS\Extbase\DomainObject\AbstractEntity::class . ' {
			public $foo;
			public $bar;
		}');
        $domainObject = new $domainObjectNameWithNS();
        $domainObject->foo = 'Test';
        $domainObject->bar = 'It is raining outside';
        $domainObject->_memorizeCleanState();
        $this->assertFalse($domainObject->_isDirty());
    }

    /**
     * @test
     */
    public function objectIsDirtyAfterCallingMemorizeCleanStateWithSimplePropertiesAndModifyingThePropertiesAfterwards()
    {
        $domainObjectName = $this->getUniqueId('DomainObject_');
        $domainObjectNameWithNS = __NAMESPACE__ . '\\' . $domainObjectName;
        eval('namespace ' . __NAMESPACE__ . '; class ' . $domainObjectName . ' extends \\' . \TYPO3\CMS\Extbase\DomainObject\AbstractEntity::class . ' {
			public $foo;
			public $bar;
		}');
        $domainObject = new $domainObjectNameWithNS();
        $domainObject->foo = 'Test';
        $domainObject->bar = 'It is raining outside';
        $domainObject->_memorizeCleanState();
        $domainObject->bar = 'Now it is sunny.';
        $this->assertTrue($domainObject->_isDirty());
    }

    /**
     * @test
     */
    public function objectIsNotDirtyAfterCallingMemorizeCleanStateWithObjectProperties()
    {
        $domainObjectName = $this->getUniqueId('DomainObject_');
        $domainObjectNameWithNS = __NAMESPACE__ . '\\' . $domainObjectName;
        eval('namespace ' . __NAMESPACE__ . '; class ' . $domainObjectName . ' extends \\' . \TYPO3\CMS\Extbase\DomainObject\AbstractEntity::class . ' {
			public $foo;
			public $bar;
		}');
        $domainObject = new $domainObjectNameWithNS();
        $domainObject->foo = new \DateTime();
        $domainObject->bar = 'It is raining outside';
        $domainObject->_memorizeCleanState();
        $this->assertFalse($domainObject->_isDirty());
    }

    /**
     * @test
     */
    public function objectIsNotDirtyAfterCallingMemorizeCleanStateWithOtherDomainObjectsAsProperties()
    {
        $domainObjectName = $this->getUniqueId('DomainObject_');
        $domainObjectNameWithNS = __NAMESPACE__ . '\\' . $domainObjectName;
        eval('namespace ' . __NAMESPACE__ . '; class ' . $domainObjectName . ' extends \\' . \TYPO3\CMS\Extbase\DomainObject\AbstractEntity::class . ' {
			public $foo;
			public $bar;
		}');
        $secondDomainObjectName = $this->getUniqueId('DomainObject_');
        $secondDomainObjectNameWithNS = __NAMESPACE__ . '\\' . $secondDomainObjectName;
        eval('namespace ' . __NAMESPACE__ . '; class ' . $secondDomainObjectName . ' extends \\' . \TYPO3\CMS\Extbase\DomainObject\AbstractEntity::class . ' {
			public $foo;
			public $bar;
		}');
        $secondDomainObject = new $secondDomainObjectNameWithNS();
        $secondDomainObject->_memorizeCleanState();
        $domainObject = new $domainObjectNameWithNS();
        $domainObject->foo = $secondDomainObject;
        $domainObject->bar = 'It is raining outside';
        $domainObject->_memorizeCleanState();
        $this->assertFalse($domainObject->_isDirty());
    }
}
