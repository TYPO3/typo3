<?php
namespace TYPO3\CMS\Frontend\Tests\Unit\ContentObject\Menu;

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

use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\Frontend\ContentObject\Menu\Exception\NoSuchMenuTypeException;
use TYPO3\CMS\Frontend\ContentObject\Menu\MenuContentObjectFactory;

/**
 * Testcase
 */
class MenuContentObjectFactoryTest extends UnitTestCase
{
    /**
     * @test
     */
    public function getMenuObjectByTypeThrowsExceptionForUnknownType()
    {
        $this->expectException(NoSuchMenuTypeException::class);
        $this->expectExceptionCode(1363278130);
        $factory = new MenuContentObjectFactory;
        $factory->getMenuObjectByType($this->getUniqueId('foo_'));
    }

    /**
     * @test
     */
    public function getMenuObjectByTypeReturnsObjectForRegisteredMenuType()
    {
        $factory = new MenuContentObjectFactory;
        $this->assertInternalType('object', $factory->getMenuObjectByType('GMENU'));
    }

    /**
     * @test
     */
    public function getMenuObjectByTypeReturnsObjectWithLowercasedMenuType()
    {
        $factory = new MenuContentObjectFactory;
        $this->assertInternalType('object', $factory->getMenuObjectByType('gmenu'));
    }

    /**
     * @test
     */
    public function getMenuObjectByTypeReturnsInstanceOfOwnRegisteredTypeInsteadOfInternalType()
    {
        $factory = new MenuContentObjectFactory;
        $selfClassName = get_class($this);
        $factory->registerMenuType('GMENU', $selfClassName);
        $this->assertInstanceOf($selfClassName, $factory->getMenuObjectByType('GMENU'));
    }

    /**
     * @test
     */
    public function getMenuObjectByTypeReturnsInstanceOfNewRegisteredType()
    {
        $factory = new MenuContentObjectFactory;
        $selfClassName = get_class($this);
        $uniqueMenuType = $this->getUniqueId('foo_');
        $factory->registerMenuType($uniqueMenuType, $selfClassName);
        $this->assertInstanceOf($selfClassName, $factory->getMenuObjectByType($uniqueMenuType));
    }

    /**
     * @test
     */
    public function registerMenuTypeThrowsExceptionIfTypeIsNotOfTypeString()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1363429303);
        $factory = new MenuContentObjectFactory;
        $factory->registerMenuType(array(), 'foo');
    }

    /**
     * @test
     */
    public function registerMenuTypeThrowsExceptionIfClassNameIsNotOfTypeString()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1363429303);
        $factory = new MenuContentObjectFactory;
        $factory->registerMenuType('foo', array());
    }
}
