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

namespace TYPO3\CMS\Core\Tests\Unit\Crypto\PasswordHashing;

use TYPO3\CMS\Core\Crypto\PasswordHashing\Argon2iPasswordHash;
use TYPO3\CMS\Core\Crypto\PasswordHashing\InvalidPasswordHashException;
use TYPO3\CMS\Core\Crypto\PasswordHashing\PasswordHashFactory;
use TYPO3\CMS\Core\Crypto\PasswordHashing\PhpassPasswordHash;
use TYPO3\CMS\Core\Tests\Unit\Crypto\PasswordHashing\Fixtures\TestPasswordHash;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class PasswordHashFactoryTest extends UnitTestCase
{
    /**
     * @test
     */
    public function getThrowsExceptionIfModeIsNotBeOrFe(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1533948312);
        (new PasswordHashFactory())->get('ThisIsNotAValidHash', 'foo');
    }

    /**
     * @test
     */
    public function getThrowsExceptionWithBrokenClassNameModeConfiguration(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionCode(1533949053);
        $GLOBALS['TYPO3_CONF_VARS']['FE']['passwordHashing']['className'] = '';
        (new PasswordHashFactory())->get('ThisIsNotAValidHash', 'FE');
    }

    /**
     * @test
     */
    public function getThrowsExceptionWithBrokenOptionsModeConfiguration(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionCode(1533949053);
        $GLOBALS['TYPO3_CONF_VARS']['FE']['passwordHashing']['options'] = '';
        (new PasswordHashFactory())->get('ThisIsNotAValidHash', 'FE');
    }

    /**
     * @test
     */
    public function getThrowsExceptionIfARegisteredHashDoesNotImplementSaltInterface(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['availablePasswordHashAlgorithms'] = [ \stdClass::class ];
        $this->expectException(\LogicException::class);
        $this->expectExceptionCode(1533818569);
        (new PasswordHashFactory())->get('ThisIsNotAValidHash', 'BE');
    }

    /**
     * @test
     */
    public function getThrowsExceptionIfNoClassIsFoundThatHandlesGivenHash(): void
    {
        $this->expectException(InvalidPasswordHashException::class);
        $this->expectExceptionCode(1533818591);
        (new PasswordHashFactory())->get('ThisIsNotAValidHash', 'BE');
    }

    /**
     * @test
     */
    public function getThrowsExceptionIfClassThatHandlesAHashIsNotAvailable(): void
    {
        $phpassProphecy = $this->prophesize(PhpassPasswordHash::class);
        GeneralUtility::addInstance(PhpassPasswordHash::class, $phpassProphecy->reveal());
        $phpassProphecy->isAvailable()->shouldBeCalled()->willReturn(false);
        $this->expectException(InvalidPasswordHashException::class);
        $this->expectExceptionCode(1533818591);
        (new PasswordHashFactory())->get('$P$C7u7E10SBEie/Jbdz0jDtUcWhzgOPF.', 'BE');
    }

    /**
     * @test
     */
    public function getThrowsExceptionIfClassThatHandlesAHashSaysNoToHash(): void
    {
        $phpassProphecy = $this->prophesize(PhpassPasswordHash::class);
        GeneralUtility::addInstance(PhpassPasswordHash::class, $phpassProphecy->reveal());
        $phpassProphecy->isAvailable()->shouldBeCalled()->willReturn(true);
        $hash = '$P$C7u7E10SBEie/Jbdz0jDtUcWhzgOPF.';
        $phpassProphecy->isValidSaltedPW($hash)->shouldBeCalled()->willReturn(false);
        $this->expectException(InvalidPasswordHashException::class);
        $this->expectExceptionCode(1533818591);
        (new PasswordHashFactory())->get($hash, 'BE');
    }

    /**
     * @test
     */
    public function getHandsConfiguredOptionsToHashClassIfMethodIsConfiguredDefaultForMode(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['availablePasswordHashAlgorithms'] = [ TestPasswordHash::class ];
        $GLOBALS['TYPO3_CONF_VARS']['FE']['passwordHashing'] = [
            'className' => TestPasswordHash::class,
            'options' => [
                'foo' => 'bar'
            ],
        ];
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1533950385);
        (new PasswordHashFactory())->get('someHash', 'FE');
    }

    /**
     * @test
     */
    public function getReturnsInstanceOfHashClassThatHandlesHash(): void
    {
        $phpassProphecy = $this->prophesize(PhpassPasswordHash::class);
        $phpassRevelation = $phpassProphecy->reveal();
        GeneralUtility::addInstance(PhpassPasswordHash::class, $phpassRevelation);
        $phpassProphecy->isAvailable()->shouldBeCalled()->willReturn(true);
        $hash = '$P$C7u7E10SBEie/Jbdz0jDtUcWhzgOPF.';
        $phpassProphecy->isValidSaltedPW($hash)->shouldBeCalled()->willReturn(true);
        self::assertSame($phpassRevelation, (new PasswordHashFactory())->get($hash, 'BE'));
    }

    /**
     * @test
     */
    public function getDefaultHashInstanceThrowsExceptionIfModeIsNotBeOrFe(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1533820041);
        (new PasswordHashFactory())->getDefaultHashInstance('foo');
    }

    /**
     * @test
     */
    public function getDefaultHashInstanceThrowsExceptionWithBrokenClassNameModeConfiguration(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionCode(1533950622);
        $GLOBALS['TYPO3_CONF_VARS']['FE']['passwordHashing']['className'] = '';
        (new PasswordHashFactory())->getDefaultHashInstance('FE');
    }

    /**
     * @test
     */
    public function getDefaultHashInstanceThrowsExceptionWithBrokenOptionsModeConfiguration(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionCode(1533950622);
        $GLOBALS['TYPO3_CONF_VARS']['FE']['passwordHashing']['options'] = '';
        (new PasswordHashFactory())->getDefaultHashInstance('FE');
    }

    /**
     * @test
     */
    public function getDefaultHashReturnsInstanceOfConfiguredDefaultFeMethod(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['saltedpasswords']['FE']['saltedPWHashingMethod'] = Argon2iPasswordHash::class;
        $hashInstance = (new PasswordHashFactory())->getDefaultHashInstance('FE');
        self::assertInstanceOf(Argon2iPasswordHash::class, $hashInstance);
    }

    /**
     * @test
     */
    public function getDefaultHashReturnsInstanceOfConfiguredDefaultBeMethod(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['saltedpasswords']['BE']['saltedPWHashingMethod'] = Argon2iPasswordHash::class;
        $hashInstance = (new PasswordHashFactory())->getDefaultHashInstance('BE');
        self::assertInstanceOf(Argon2iPasswordHash::class, $hashInstance);
    }

    /**
     * @test
     */
    public function getDefaultHashThrowsExceptionIfDefaultHashMethodDoesNotImplementSaltInterface(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['BE']['passwordHashing']['className'] = \stdClass::class;
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['availablePasswordHashAlgorithms'] = [ \stdClass::class ];
        $this->expectException(\LogicException::class);
        $this->expectExceptionCode(1533820281);
        (new PasswordHashFactory())->getDefaultHashInstance('BE');
    }

    /**
     * @test
     */
    public function getDefaultHashThrowsExceptionIfDefaultHashMethodIsNotRegistered(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['BE']['passwordHashing']['className'] = \stdClass::class;
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['availablePasswordHashAlgorithms'] = [ Argon2iPasswordHash::class ];
        $this->expectException(InvalidPasswordHashException::class);
        $this->expectExceptionCode(1533820194);
        (new PasswordHashFactory())->getDefaultHashInstance('BE');
    }

    /**
     * @test
     */
    public function getDefaultHashThrowsExceptionIfDefaultHashMethodIsNotAvailable(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['saltedpasswords']['BE']['saltedPWHashingMethod'] = Argon2iPasswordHash::class;
        $argonProphecy = $this->prophesize(Argon2iPasswordHash::class);
        GeneralUtility::addInstance(Argon2iPasswordHash::class, $argonProphecy->reveal());
        $argonProphecy->isAvailable()->shouldBeCalled()->willReturn(false);
        $this->expectException(InvalidPasswordHashException::class);
        $this->expectExceptionCode(1533822084);
        (new PasswordHashFactory())->getDefaultHashInstance('BE');
    }

    /**
     * @test
     */
    public function getDefaultHoshHandsConfiguredOptionsToHashClass(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['availablePasswordHashAlgorithms'] = [ TestPasswordHash::class ];
        $GLOBALS['TYPO3_CONF_VARS']['FE']['passwordHashing'] = [
            'className' => TestPasswordHash::class,
            'options' => [
                'foo' => 'bar'
            ],
        ];
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1533950385);
        (new PasswordHashFactory())->getDefaultHashInstance('FE');
    }

    /**
     * @test
     */
    public function getRegisteredSaltedHashingMethodsReturnsRegisteredMethods(): void
    {
        $methods = [
            'foo',
            'bar'
        ];
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['availablePasswordHashAlgorithms'] = $methods;
        self::assertSame($methods, PasswordHashFactory::getRegisteredSaltedHashingMethods());
    }

    /**
     * @test
     */
    public function getRegisteredSaltedHashingMethodsThrowsExceptionIfNoMethodIsConfigured(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1533948733);
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['availablePasswordHashAlgorithms'] = [];
        PasswordHashFactory::getRegisteredSaltedHashingMethods();
    }
}
