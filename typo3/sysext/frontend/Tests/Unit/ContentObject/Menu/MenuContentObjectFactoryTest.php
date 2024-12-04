<?php

declare(strict_types=1);

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

use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\CMS\Frontend\ContentObject\Menu\AbstractMenuContentObject;
use TYPO3\CMS\Frontend\ContentObject\Menu\Exception\NoSuchMenuTypeException;
use TYPO3\CMS\Frontend\ContentObject\Menu\MenuContentObjectFactory;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class MenuContentObjectFactoryTest extends UnitTestCase
{
    #[Test]
    public function getMenuObjectByTypeThrowsExceptionForUnknownType(): void
    {
        $this->expectException(NoSuchMenuTypeException::class);
        $this->expectExceptionCode(1363278130);
        $subject = new MenuContentObjectFactory();
        $subject->getMenuObjectByType(StringUtility::getUniqueId('foo_'));
    }

    #[Test]
    #[DoesNotPerformAssertions]
    public function getMenuObjectByTypeDoesNotThrowException(): void
    {
        $subject = new MenuContentObjectFactory();
        $subject->getMenuObjectByType('TMENU');
    }

    #[Test]
    #[DoesNotPerformAssertions]
    public function getMenuObjectByTypeDoesNotThrowExceptionWithLowercasedMenuType(): void
    {
        $subject = new MenuContentObjectFactory();
        $subject->getMenuObjectByType('tmenu');
    }

    #[Test]
    public function getMenuObjectByTypeReturnsInstanceOfOwnRegisteredTypeInsteadOfInternalType(): void
    {
        $subject = new MenuContentObjectFactory();
        $selfClassName = 'tx_menutest_' . uniqid();
        $selfClass = new class () extends AbstractMenuContentObject {};
        class_alias($selfClass::class, $selfClassName);
        $subject->registerMenuType('TMENU', $selfClassName);
        self::assertInstanceOf($selfClassName, $subject->getMenuObjectByType('TMENU'));
    }

    #[Test]
    public function getMenuObjectByTypeReturnsInstanceOfNewRegisteredType(): void
    {
        $subject = new MenuContentObjectFactory();
        $selfClassName = 'tx_menutest_' . uniqid();
        $selfClass = new class () extends AbstractMenuContentObject {};
        class_alias($selfClass::class, $selfClassName);
        $uniqueMenuType = StringUtility::getUniqueId('foo_');
        $subject->registerMenuType($uniqueMenuType, $selfClassName);
        self::assertInstanceOf($selfClassName, $subject->getMenuObjectByType($uniqueMenuType));
    }
}
