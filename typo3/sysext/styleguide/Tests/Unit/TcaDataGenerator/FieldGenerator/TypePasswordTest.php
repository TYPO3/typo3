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

namespace TYPO3\CMS\Styleguide\Tests\Unit\TcaDataGenerator\FieldGenerator;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Crypto\PasswordHashing\PasswordHashFactory;
use TYPO3\CMS\Core\Crypto\PasswordHashing\PasswordHashInterface;
use TYPO3\CMS\Styleguide\Service\KauderwelschService;
use TYPO3\CMS\Styleguide\TcaDataGenerator\FieldGenerator\TypePassword;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class TypePasswordTest extends UnitTestCase
{
    #[Test]
    public function generateHashesEachPlainTextPasswordOnlyOnce(): void
    {
        $passwordHash = $this->createMock(PasswordHashInterface::class);
        $passwordHash->expects($this->exactly(2))->method('getHashedPassword')->willReturnCallback(
            static fn(string $plainText): string => 'hashed-' . $plainText
        );
        $passwordHashFactory = self::createStub(PasswordHashFactory::class);
        $passwordHashFactory->method('getDefaultHashInstance')->willReturn($passwordHash);
        $subject = new TypePassword(new KauderwelschService(), $passwordHashFactory);

        $generatedPassword = ['fieldConfig' => ['config' => ['type' => 'password']]];
        $defaultPassword = ['fieldConfig' => ['config' => ['type' => 'password', 'default' => 'aDefault']]];

        self::assertSame('hashed-somePassword1!', $subject->generate($generatedPassword));
        self::assertSame('hashed-somePassword1!', $subject->generate($generatedPassword));
        self::assertSame('hashed-aDefault', $subject->generate($defaultPassword));
        self::assertSame('hashed-aDefault', $subject->generate($defaultPassword));
    }
}
