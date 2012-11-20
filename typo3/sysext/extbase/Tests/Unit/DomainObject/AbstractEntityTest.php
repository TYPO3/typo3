<?php
namespace TYPO3\CMS\Extbase\Tests\Unit\DomainObject;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2009 Sebastian Kurfürst <sebastian@typo3.org>
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
class AbstractEntityTest extends \TYPO3\CMS\Extbase\Tests\Unit\BaseTestCase {

	/**
	 * @test
	 */
	public function objectIsNotDirtyAfterCallingMemorizeCleanStateWithSimpleProperties() {
		$domainObjectName = uniqid('DomainObject_');
		eval('class ' . $domainObjectName . ' extends TYPO3\\CMS\\Extbase\\DomainObject\\AbstractEntity {
			public $foo;
			public $bar;
		}');
		$domainObject = new $domainObjectName();
		$domainObject->foo = 'Test';
		$domainObject->bar = 'It is raining outside';
		$domainObject->_memorizeCleanState();
		$this->assertFalse($domainObject->_isDirty());
	}

	/**
	 * @test
	 */
	public function objectIsDirtyAfterCallingMemorizeCleanStateWithSimplePropertiesAndModifyingThePropertiesAfterwards() {
		$domainObjectName = uniqid('DomainObject_');
		eval('class ' . $domainObjectName . ' extends TYPO3\\CMS\\Extbase\\DomainObject\\AbstractEntity {
			public $foo;
			public $bar;
		}');
		$domainObject = new $domainObjectName();
		$domainObject->foo = 'Test';
		$domainObject->bar = 'It is raining outside';
		$domainObject->_memorizeCleanState();
		$domainObject->bar = 'Now it is sunny.';
		$this->assertTrue($domainObject->_isDirty());
	}

	/**
	 * @test
	 */
	public function objectIsNotDirtyAfterCallingMemorizeCleanStateWithObjectProperties() {
		$domainObjectName = uniqid('DomainObject_');
		eval('class ' . $domainObjectName . ' extends TYPO3\\CMS\\Extbase\\DomainObject\\AbstractEntity {
			public $foo;
			public $bar;
		}');
		$domainObject = new $domainObjectName();
		$domainObject->foo = new \DateTime();
		$domainObject->bar = 'It is raining outside';
		$domainObject->_memorizeCleanState();
		$this->assertFalse($domainObject->_isDirty());
	}

	/**
	 * @test
	 */
	public function objectIsNotDirtyAfterCallingMemorizeCleanStateWithOtherDomainObjectsAsProperties() {
		$domainObjectName = uniqid('DomainObject_');
		eval('class ' . $domainObjectName . ' extends TYPO3\\CMS\\Extbase\\DomainObject\\AbstractEntity {
			public $foo;
			public $bar;
		}');
		$secondDomainObjectName = uniqid('DomainObject_');
		eval('class ' . $secondDomainObjectName . ' extends TYPO3\\CMS\\Extbase\\DomainObject\\AbstractEntity {
			public $foo;
			public $bar;
		}');
		$secondDomainObject = new $secondDomainObjectName();
		$secondDomainObject->_memorizeCleanState();
		$domainObject = new $domainObjectName();
		$domainObject->foo = $secondDomainObject;
		$domainObject->bar = 'It is raining outside';
		$domainObject->_memorizeCleanState();
		$this->assertFalse($domainObject->_isDirty());
	}
}

?>