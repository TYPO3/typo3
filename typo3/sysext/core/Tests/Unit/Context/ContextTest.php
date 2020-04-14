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
use TYPO3\CMS\Core\Registry;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class ContextTest extends UnitTestCase
{
    /**
     * Date provider for hasAspectReturnsTrueOnExistingAspect
     *
     * @return array
     */
    public function validAspectKeysDataProvider(): array
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
     * @param string $aspectName
     */
    public function hasAspectReturnsTrueOnExistingAspect(string $aspectName)
    {
        $subject = new Context([
            'myfirst' => new UserAspect(),
            'mysecond' => new UserAspect(),
        ]);
        self::assertTrue($subject->hasAspect($aspectName));
    }

    /**
     * Date provider for hasAspectReturnsFalseOnNonExistingAspect
     *
     * @return array
     */
    public function invalidAspectKeysDataProvider(): array
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
     * @param string $aspectName
     */
    public function hasAspectReturnsFalseOnNonExistingAspect(string $aspectName)
    {
        $subject = new Context([
            'myfirst' => new UserAspect(),
            'mysecond' => new UserAspect(),
        ]);
        self::assertFalse($subject->hasAspect($aspectName));
    }

    /**
     * @test
     */
    public function constructorAddsValidAspect()
    {
        $subject = new Context([
            'coolio' => new UserAspect(),
            'uncoolio' => new Registry()
        ]);
        self::assertTrue($subject->hasAspect('coolio'));
        self::assertFalse($subject->hasAspect('uncoolio'));
    }

    /**
     * @test
     */
    public function getAspectThrowsExceptionOnInvalidAspect()
    {
        $aspect = new UserAspect();
        $subject = new Context([
            'coolio' => $aspect
        ]);

        $this->expectException(AspectNotFoundException::class);
        $this->expectExceptionCode(1527777641);
        $subject->getAspect('uncoolio');
    }

    /**
     * @test
     */
    public function getAspectReturnsValidAspect()
    {
        $aspect = new UserAspect();
        $subject = new Context([
            'coolio' => $aspect
        ]);

        self::assertSame($aspect, $subject->getAspect('coolio'));
    }

    /**
     * @test
     */
    public function invalidAspectFromgetPropertyFromAspectThrowsException()
    {
        $aspect = new UserAspect();
        $subject = new Context([
            'coolio' => $aspect
        ]);

        $this->expectException(AspectNotFoundException::class);
        $this->expectExceptionCode(1527777868);
        $subject->getPropertyFromAspect('uncoolio', 'does not matter');
    }

    /**
     * @test
     */
    public function invalidPropertyFromgetPropertyFromAspectReturnsDefault()
    {
        $defaultValue = 'default value';
        $aspect = new UserAspect();
        $subject = new Context([
            'coolio' => $aspect
        ]);

        $result = $subject->getPropertyFromAspect('coolio', 'unknownproperty', $defaultValue);
        self::assertEquals($defaultValue, $result);
    }

    /**
     * @test
     */
    public function validPropertyFromgetPropertyFromAspectReturnsValue()
    {
        $aspect = new WorkspaceAspect(13);
        $subject = new Context([
            'coolio' => $aspect
        ]);

        $result = $subject->getPropertyFromAspect('coolio', 'id');
        self::assertEquals(13, $result);
    }

    /**
     * @test
     */
    public function setAspectSetsAnAspectAndCanReturnIt()
    {
        $aspect = new UserAspect();
        $subject = new Context();

        $subject->setAspect('coolio', $aspect);
        self::assertSame($aspect, $subject->getAspect('coolio'));
    }

    /**
     * @test
     */
    public function setAspectOverridesAnExisting()
    {
        $initialAspect = new UserAspect();
        $aspectOverride = new UserAspect();
        $subject = new Context([
            'coolio' => $initialAspect
        ]);

        $subject->setAspect('coolio', $aspectOverride);
        self::assertNotSame($initialAspect, $subject->getAspect('coolio'));
        self::assertSame($aspectOverride, $subject->getAspect('coolio'));
    }
}
