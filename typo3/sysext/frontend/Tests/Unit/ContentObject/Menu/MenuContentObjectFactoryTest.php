<?php

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

namespace TYPO3\CMS\Frontend\Tests\Unit\ContentObject\Menu;

use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\CMS\Frontend\ContentObject\Menu\Exception\NoSuchMenuTypeException;
use TYPO3\CMS\Frontend\ContentObject\Menu\MenuContentObjectFactory;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

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
        $factory = new MenuContentObjectFactory();
        $factory->getMenuObjectByType(StringUtility::getUniqueId('foo_'));
    }

    /**
     * @test
     */
    public function getMenuObjectByTypeReturnsObjectForRegisteredMenuType()
    {
        $factory = new MenuContentObjectFactory();
        self::assertIsObject($factory->getMenuObjectByType('TMENU'));
    }

    /**
     * @test
     */
    public function getMenuObjectByTypeReturnsObjectWithLowercasedMenuType()
    {
        $factory = new MenuContentObjectFactory();
        self::assertIsObject($factory->getMenuObjectByType('tmenu'));
    }

    /**
     * @test
     */
    public function getMenuObjectByTypeReturnsInstanceOfOwnRegisteredTypeInsteadOfInternalType()
    {
        $factory = new MenuContentObjectFactory();
        $selfClassName = static::class;
        $factory->registerMenuType('TMENU', $selfClassName);
        self::assertInstanceOf($selfClassName, $factory->getMenuObjectByType('TMENU'));
    }

    /**
     * @test
     */
    public function getMenuObjectByTypeReturnsInstanceOfNewRegisteredType()
    {
        $factory = new MenuContentObjectFactory();
        $selfClassName = static::class;
        $uniqueMenuType = StringUtility::getUniqueId('foo_');
        $factory->registerMenuType($uniqueMenuType, $selfClassName);
        self::assertInstanceOf($selfClassName, $factory->getMenuObjectByType($uniqueMenuType));
    }

    /**
     * @test
     */
    public function registerMenuTypeThrowsExceptionIfTypeIsNotOfTypeString()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1363429303);
        $factory = new MenuContentObjectFactory();
        $factory->registerMenuType([], 'foo');
    }

    /**
     * @test
     */
    public function registerMenuTypeThrowsExceptionIfClassNameIsNotOfTypeString()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1363429303);
        $factory = new MenuContentObjectFactory();
        $factory->registerMenuType('foo', []);
    }
}
