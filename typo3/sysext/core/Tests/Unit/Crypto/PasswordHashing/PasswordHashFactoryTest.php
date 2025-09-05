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

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Crypto\PasswordHashing\Argon2idPasswordHash;
use TYPO3\CMS\Core\Crypto\PasswordHashing\InvalidPasswordHashException;
use TYPO3\CMS\Core\Crypto\PasswordHashing\PasswordHashFactory;
use TYPO3\CMS\Core\Crypto\PasswordHashing\PhpassPasswordHash;
use TYPO3\CMS\Core\Tests\Unit\Crypto\PasswordHashing\Fixtures\TestPasswordHash;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class PasswordHashFactoryTest extends UnitTestCase
{
    #[Test]
    public function getThrowsExceptionIfModeIsNotBeOrFe(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1533948312);
        (new PasswordHashFactory())->get('ThisIsNotAValidHash', 'foo');
    }

    #[Test]
    public function getThrowsExceptionWithBrokenClassNameModeConfiguration(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionCode(1533949053);
        $GLOBALS['TYPO3_CONF_VARS']['FE']['passwordHashing']['className'] = '';
        (new PasswordHashFactory())->get('ThisIsNotAValidHash', 'FE');
    }

    #[Test]
    public function getThrowsExceptionWithBrokenOptionsModeConfiguration(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionCode(1533949053);
        $GLOBALS['TYPO3_CONF_VARS']['FE']['passwordHashing']['options'] = '';
        (new PasswordHashFactory())->get('ThisIsNotAValidHash', 'FE');
    }

    #[Test]
    public function getThrowsExceptionIfARegisteredHashDoesNotImplementSaltInterface(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['availablePasswordHashAlgorithms'] = [ \stdClass::class ];
        $this->expectException(\LogicException::class);
        $this->expectExceptionCode(1533818569);
        (new PasswordHashFactory())->get('ThisIsNotAValidHash', 'BE');
    }

    #[Test]
    public function getThrowsExceptionIfNoClassIsFoundThatHandlesGivenHash(): void
    {
        $this->expectException(InvalidPasswordHashException::class);
        $this->expectExceptionCode(1533818591);
        (new PasswordHashFactory())->get('ThisIsNotAValidHash', 'BE');
    }

    #[Test]
    public function getThrowsExceptionIfClassThatHandlesAHashIsNotAvailable(): void
    {
        $phpassPasswordHashMock = $this->createMock(PhpassPasswordHash::class);
        $phpassPasswordHashMock->expects($this->atLeastOnce())->method('isAvailable')->willReturn(false);
        GeneralUtility::addInstance(PhpassPasswordHash::class, $phpassPasswordHashMock);
        $this->expectException(InvalidPasswordHashException::class);
        $this->expectExceptionCode(1533818591);
        (new PasswordHashFactory())->get('$P$C7u7E10SBEie/Jbdz0jDtUcWhzgOPF.', 'BE');
    }

    #[Test]
    public function getThrowsExceptionIfClassThatHandlesAHashSaysNoToHash(): void
    {
        GeneralUtility::addInstance(PhpassPasswordHash::class, new PhpassPasswordHash());
        $hash = 'FOO$P$C7u7E10SBEie/Jbdz0jDtUcWhzgOPF.';
        $this->expectException(InvalidPasswordHashException::class);
        $this->expectExceptionCode(1533818591);
        (new PasswordHashFactory())->get($hash, 'BE');
    }

    #[Test]
    public function getHandsConfiguredOptionsToHashClassIfMethodIsConfiguredDefaultForMode(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['availablePasswordHashAlgorithms'] = [ TestPasswordHash::class ];
        $GLOBALS['TYPO3_CONF_VARS']['FE']['passwordHashing'] = [
            'className' => TestPasswordHash::class,
            'options' => [
                'foo' => 'bar',
            ],
        ];
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1533950385);
        (new PasswordHashFactory())->get('someHash', 'FE');
    }

    #[Test]
    public function getReturnsInstanceOfHashClassThatHandlesHash(): void
    {
        $phpassPasswordHash = new PhpassPasswordHash();
        GeneralUtility::addInstance(PhpassPasswordHash::class, $phpassPasswordHash);
        $hash = '$P$C7u7E10SBEie/Jbdz0jDtUcWhzgOPF.';
        self::assertSame($phpassPasswordHash, (new PasswordHashFactory())->get($hash, 'BE'));
    }

    #[Test]
    public function getDefaultHashInstanceThrowsExceptionIfModeIsNotBeOrFe(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1533820041);
        (new PasswordHashFactory())->getDefaultHashInstance('foo');
    }

    #[Test]
    public function getDefaultHashInstanceThrowsExceptionWithBrokenClassNameModeConfiguration(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionCode(1533950622);
        $GLOBALS['TYPO3_CONF_VARS']['FE']['passwordHashing']['className'] = '';
        (new PasswordHashFactory())->getDefaultHashInstance('FE');
    }

    #[Test]
    public function getDefaultHashInstanceThrowsExceptionWithBrokenOptionsModeConfiguration(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionCode(1533950622);
        $GLOBALS['TYPO3_CONF_VARS']['FE']['passwordHashing']['options'] = '';
        (new PasswordHashFactory())->getDefaultHashInstance('FE');
    }

    #[Test]
    public function getDefaultHashReturnsInstanceOfConfiguredDefaultFeMethod(): void
    {
        $hashInstance = (new PasswordHashFactory())->getDefaultHashInstance('FE');
        self::assertInstanceOf(Argon2idPasswordHash::class, $hashInstance);
    }

    #[Test]
    public function getDefaultHashReturnsInstanceOfConfiguredDefaultBeMethod(): void
    {
        $hashInstance = (new PasswordHashFactory())->getDefaultHashInstance('BE');
        self::assertInstanceOf(Argon2idPasswordHash::class, $hashInstance);
    }

    #[Test]
    public function getDefaultHashThrowsExceptionIfDefaultHashMethodDoesNotImplementSaltInterface(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['BE']['passwordHashing']['className'] = \stdClass::class;
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['availablePasswordHashAlgorithms'] = [ \stdClass::class ];
        $this->expectException(\LogicException::class);
        $this->expectExceptionCode(1533820281);
        (new PasswordHashFactory())->getDefaultHashInstance('BE');
    }

    #[Test]
    public function getDefaultHashThrowsExceptionIfDefaultHashMethodIsNotRegistered(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['BE']['passwordHashing']['className'] = \stdClass::class;
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['availablePasswordHashAlgorithms'] = [ Argon2idPasswordHash::class ];
        $this->expectException(InvalidPasswordHashException::class);
        $this->expectExceptionCode(1533820194);
        (new PasswordHashFactory())->getDefaultHashInstance('BE');
    }

    #[Test]
    public function getDefaultHashThrowsExceptionIfDefaultHashMethodIsNotAvailable(): void
    {
        $argon2idPasswordHashMock = $this->createMock(Argon2idPasswordHash::class);
        $argon2idPasswordHashMock->expects($this->atLeastOnce())->method('isAvailable')->willReturn(false);
        GeneralUtility::addInstance(Argon2idPasswordHash::class, $argon2idPasswordHashMock);
        $this->expectException(InvalidPasswordHashException::class);
        $this->expectExceptionCode(1533822084);
        (new PasswordHashFactory())->getDefaultHashInstance('BE');
    }

    #[Test]
    public function getDefaultHashHandsConfiguredOptionsToHashClass(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['availablePasswordHashAlgorithms'] = [ TestPasswordHash::class ];
        $GLOBALS['TYPO3_CONF_VARS']['FE']['passwordHashing'] = [
            'className' => TestPasswordHash::class,
            'options' => [
                'foo' => 'bar',
            ],
        ];
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1533950385);
        (new PasswordHashFactory())->getDefaultHashInstance('FE');
    }

    #[Test]
    public function getRegisteredSaltedHashingMethodsReturnsRegisteredMethods(): void
    {
        $methods = [
            'foo',
            'bar',
        ];
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['availablePasswordHashAlgorithms'] = $methods;
        self::assertSame($methods, PasswordHashFactory::getRegisteredSaltedHashingMethods());
    }

    #[Test]
    public function getRegisteredSaltedHashingMethodsThrowsExceptionIfNoMethodIsConfigured(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1533948733);
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['availablePasswordHashAlgorithms'] = [];
        PasswordHashFactory::getRegisteredSaltedHashingMethods();
    }
}
