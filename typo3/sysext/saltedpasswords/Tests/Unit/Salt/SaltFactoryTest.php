<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Saltedpasswords\Tests\Unit\Salt;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Saltedpasswords\Exception\InvalidSaltException;
use TYPO3\CMS\Saltedpasswords\Salt\Argon2iSalt;
use TYPO3\CMS\Saltedpasswords\Salt\PhpassSalt;
use TYPO3\CMS\Saltedpasswords\Salt\SaltFactory;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class SaltFactoryTest extends UnitTestCase
{
    /**
     * @test
     */
    public function getThrowsExceptionIfARegisteredHashDoesNotImplementSaltInterface(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/saltedpasswords']['saltMethods'] = [ \stdClass::class ];
        $this->expectException(\LogicException::class);
        $this->expectExceptionCode(1533818569);
        (new SaltFactory())->get('ThisIsNotAValidHash');
    }

    /**
     * @test
     */
    public function getThrowsExceptionIfNoClassIsFoundThatHandlesGivenHash(): void
    {
        $this->expectException(InvalidSaltException::class);
        $this->expectExceptionCode(1533818591);
        (new SaltFactory())->get('ThisIsNotAValidHash');
    }

    /**
     * @test
     */
    public function getThrowsExceptionIfClassThatHandlesAHashIsNotAvailable(): void
    {
        $phpassProphecy = $this->prophesize(PhpassSalt::class);
        GeneralUtility::addInstance(PhpassSalt::class, $phpassProphecy->reveal());
        $phpassProphecy->isAvailable()->shouldBeCalled()->willReturn(false);
        $this->expectException(InvalidSaltException::class);
        $this->expectExceptionCode(1533818591);
        (new SaltFactory())->get('$P$C7u7E10SBEie/Jbdz0jDtUcWhzgOPF.');
    }

    /**
     * @test
     */
    public function getThrowsExceptionIfClassThatHandlesAHashSaysNoToHash(): void
    {
        $phpassProphecy = $this->prophesize(PhpassSalt::class);
        GeneralUtility::addInstance(PhpassSalt::class, $phpassProphecy->reveal());
        $phpassProphecy->isAvailable()->shouldBeCalled()->willReturn(true);
        $hash = '$P$C7u7E10SBEie/Jbdz0jDtUcWhzgOPF.';
        $phpassProphecy->isValidSaltedPW($hash)->shouldBeCalled()->willReturn(false);
        $this->expectException(InvalidSaltException::class);
        $this->expectExceptionCode(1533818591);
        (new SaltFactory())->get($hash);
    }

    /**
     * @test
     */
    public function getReturnsInstanceOfHashClassThatHandlesHash(): void
    {
        $phpassProphecy = $this->prophesize(PhpassSalt::class);
        $phpassRevelation = $phpassProphecy->reveal();
        GeneralUtility::addInstance(PhpassSalt::class, $phpassRevelation);
        $phpassProphecy->isAvailable()->shouldBeCalled()->willReturn(true);
        $hash = '$P$C7u7E10SBEie/Jbdz0jDtUcWhzgOPF.';
        $phpassProphecy->isValidSaltedPW($hash)->shouldBeCalled()->willReturn(true);
        $this->assertSame($phpassRevelation, (new SaltFactory())->get($hash));
    }

    /**
     * @test
     */
    public function getDefaultHashInstanceThrowsExceptionIfModeIsNotBeOrFe(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1533820041);
        (new SaltFactory())->getDefaultHashInstance('foo');
    }

    /**
     * @test
     */
    public function getDefaultHashReturnsInstanceOfConfiguredDefaultFeMethod(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['saltedpasswords']['FE']['saltedPWHashingMethod'] = Argon2iSalt::class;
        $hashInstance = (new SaltFactory())->getDefaultHashInstance('FE');
        $this->assertInstanceOf(Argon2iSalt::class, $hashInstance);
    }

    /**
     * @test
     */
    public function getDefaultHashReturnsInstanceOfConfiguredDefaultBeMethod(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['saltedpasswords']['BE']['saltedPWHashingMethod'] = Argon2iSalt::class;
        $hashInstance = (new SaltFactory())->getDefaultHashInstance('BE');
        $this->assertInstanceOf(Argon2iSalt::class, $hashInstance);
    }

    /**
     * @test
     * @todo: have a test for exception 1533820194 after utility class is no longer used
     */
    public function getDefaultHashThrowsExceptionIfDefaultHashMethodDoesNotImplementSaltInterface(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['saltedpasswords']['BE']['saltedPWHashingMethod'] = \stdClass::class;
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/saltedpasswords']['saltMethods'] = [ \stdClass::class ];
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1533820281);
        (new SaltFactory())->getDefaultHashInstance('BE');
    }

    /**
     * @test
     */
    public function getDefaultHashThrowsExceptionIfDefaultHashMethodIsNotAvailable(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['saltedpasswords']['BE']['saltedPWHashingMethod'] = Argon2iSalt::class;
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/saltedpasswords']['saltMethods'] = [ \stdClass::class ];
        $argonProphecy = $this->prophesize(Argon2iSalt::class);
        GeneralUtility::addInstance(Argon2iSalt::class, $argonProphecy->reveal());
        $argonProphecy->isAvailable()->shouldBeCalled()->willReturn(false);
        $this->expectException(InvalidSaltException::class);
        $this->expectExceptionCode(1533822084);
        (new SaltFactory())->getDefaultHashInstance('BE');
    }
}
