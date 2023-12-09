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

namespace TYPO3\CMS\Core\Tests\Unit\Context;

use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\Exception\AspectNotFoundException;
use TYPO3\CMS\Core\Context\UserAspect;
use TYPO3\CMS\Core\Context\WorkspaceAspect;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class ContextTest extends UnitTestCase
{
    public static function validAspectKeysDataProvider(): array
    {
        return [
            ['myfirst'],
            ['mysecond'],
            ['date'],
            ['visibility'],
            ['backend.user'],
            ['frontend.user'],
            ['workspace'],
        ];
    }

    /**
     * @test
     * @dataProvider validAspectKeysDataProvider
     */
    public function hasAspectReturnsTrueOnExistingAspect(string $aspectName): void
    {
        $subject = new Context();
        $subject->setAspect('myfirst', new UserAspect());
        $subject->setAspect('mysecond', new UserAspect());
        self::assertTrue($subject->hasAspect($aspectName));
    }

    public static function invalidAspectKeysDataProvider(): array
    {
        return [
            ['visible'],
            ['frontenduser'],
            ['compatibility'],
        ];
    }

    /**
     * @test
     * @dataProvider invalidAspectKeysDataProvider
     */
    public function hasAspectReturnsFalseOnNonExistingAspect(string $aspectName): void
    {
        $subject = new Context();
        self::assertFalse($subject->hasAspect($aspectName));
    }

    /**
     * @test
     */
    public function getAspectThrowsExceptionOnInvalidAspect(): void
    {
        $subject = new Context();
        $this->expectException(AspectNotFoundException::class);
        $this->expectExceptionCode(1527777641);
        $subject->getAspect('uncoolio');
    }

    /**
     * @test
     */
    public function getAspectReturnsValidAspect(): void
    {
        $aspect = new UserAspect();
        $subject = new Context();
        $subject->setAspect('coolio', $aspect);
        self::assertSame($aspect, $subject->getAspect('coolio'));
    }

    /**
     * @test
     */
    public function invalidAspectFromGetPropertyFromAspectThrowsException(): void
    {
        $subject = new Context();
        $this->expectException(AspectNotFoundException::class);
        $this->expectExceptionCode(1527777868);
        $subject->getPropertyFromAspect('uncoolio', 'does not matter');
    }

    /**
     * @test
     */
    public function invalidPropertyFromgetPropertyFromAspectReturnsDefault(): void
    {
        $defaultValue = 'default value';
        $subject = new Context();
        $subject->setAspect('coolio', new UserAspect());
        $result = $subject->getPropertyFromAspect('coolio', 'unknownproperty', $defaultValue);
        self::assertEquals($defaultValue, $result);
    }

    /**
     * @test
     */
    public function validPropertyFromgetPropertyFromAspectReturnsValue(): void
    {
        $aspect = new WorkspaceAspect(13);
        $subject = new Context();
        $subject->setAspect('coolio', $aspect);
        $result = $subject->getPropertyFromAspect('coolio', 'id');
        self::assertEquals(13, $result);
    }

    /**
     * @test
     */
    public function setAspectSetsAnAspectAndCanReturnIt(): void
    {
        $aspect = new UserAspect();
        $subject = new Context();
        $subject->setAspect('coolio', $aspect);
        self::assertSame($aspect, $subject->getAspect('coolio'));
    }

    /**
     * @test
     */
    public function setAspectOverridesAnExisting(): void
    {
        $initialAspect = new UserAspect();
        $aspectOverride = new UserAspect();
        $subject = new Context();
        $subject->setAspect('coolio', $initialAspect);
        $subject->setAspect('coolio', $aspectOverride);
        self::assertNotSame($initialAspect, $subject->getAspect('coolio'));
        self::assertSame($aspectOverride, $subject->getAspect('coolio'));
    }
}
