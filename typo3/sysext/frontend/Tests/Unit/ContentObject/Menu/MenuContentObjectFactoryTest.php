<?php
namespace TYPO3\CMS\Frontend\Tests\Unit\ContentObject\Menu;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Christian Kuhn <lolli@schwarzbu.ch>
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
 * Testcase
 *
 * @author Christian Kuhn <lolli@schwarzbu.ch
 */
class MenuContentObjectFactoryTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @test
	 * @expectedException \TYPO3\CMS\Frontend\ContentObject\Menu\Exception\NoSuchMenuTypeException
	 */
	public function getMenuObjectByTypeThrowsExceptionForUnknownType() {
		$factory = new \TYPO3\CMS\Frontend\ContentObject\Menu\MenuContentObjectFactory;
		$factory->getMenuObjectByType(uniqid('foo_'));
	}

	/**
	 * @test
	 */
	public function getMenuObjectByTypeReturnsObjectForRegisteredMenuType() {
		$factory = new \TYPO3\CMS\Frontend\ContentObject\Menu\MenuContentObjectFactory;
		$this->assertInternalType('object', $factory->getMenuObjectByType('GMENU'));
	}

	/**
	 * @test
	 */
	public function getMenuObjectByTypeReturnsObjectWithLowercasedMenuType() {
		$factory = new \TYPO3\CMS\Frontend\ContentObject\Menu\MenuContentObjectFactory;
		$this->assertInternalType('object', $factory->getMenuObjectByType('gmenu'));
	}

	/**
	 * @test
	 */
	public function getMenuObjectByTypeReturnsInstanceOfOwnRegisteredTypeInsteadOfInternalType() {
		$factory = new \TYPO3\CMS\Frontend\ContentObject\Menu\MenuContentObjectFactory;
		$selfClassName = get_class($this);
		$factory->registerMenuType('GMENU', $selfClassName);
		$this->assertInstanceOf($selfClassName, $factory->getMenuObjectByType('GMENU'));
	}

	/**
	 * @test
	 */
	public function getMenuObjectByTypeReturnsInstanceOfNewRegisteredType() {
		$factory = new \TYPO3\CMS\Frontend\ContentObject\Menu\MenuContentObjectFactory;
		$selfClassName = get_class($this);
		$uniqueMenuType = uniqid('foo_');
		$factory->registerMenuType($uniqueMenuType, $selfClassName);
		$this->assertInstanceOf($selfClassName, $factory->getMenuObjectByType($uniqueMenuType));
	}

	/**
	 * @test
	 * @expectedException \InvalidArgumentException
	 */
	public function registerMenuTypeThrowsExceptionIfTypeIsNotOfTypeString() {
		$factory = new \TYPO3\CMS\Frontend\ContentObject\Menu\MenuContentObjectFactory;
		$factory->registerMenuType(array(), 'foo');
	}

	/**
	 * @test
	 * @expectedException \InvalidArgumentException
	 */
	public function registerMenuTypeThrowsExceptionIfClassNameIsNotOfTypeString() {
		$factory = new \TYPO3\CMS\Frontend\ContentObject\Menu\MenuContentObjectFactory;
		$factory->registerMenuType('foo', array());
	}
}

?>