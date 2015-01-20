<?php
namespace TYPO3\CMS\Frontend\Tests\Unit\ContentObject\Menu;

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
		$factory->getMenuObjectByType($this->getUniqueId('foo_'));
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
		$uniqueMenuType = $this->getUniqueId('foo_');
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
