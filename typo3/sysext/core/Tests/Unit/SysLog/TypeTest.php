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

namespace TYPO3\CMS\Core\Tests\Unit\SysLog;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\LogLevel;
use TYPO3\CMS\Core\SysLog\Error;
use TYPO3\CMS\Core\SysLog\Type;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class TypeTest extends UnitTestCase
{
    public static function toLevelDataProvider(): \Generator
    {
        yield 'message keeps type level' => [Type::DB, Error::MESSAGE, LogLevel::INFO];
        yield 'user error raises to warning' => [Type::DB, Error::USER_ERROR, LogLevel::WARNING];
        yield 'system error raises to error' => [Type::DB, Error::SYSTEM_ERROR, LogLevel::ERROR];
        yield 'security notice raises to notice' => [Type::LOGIN, Error::SECURITY_NOTICE, LogLevel::NOTICE];
        yield 'warning raises to warning' => [Type::FILE, Error::WARNING, LogLevel::WARNING];
        yield 'unknown error keeps type level' => [Type::DB, 42, LogLevel::INFO];
        yield 'less severe error does not lower type level' => [Type::ERROR, Error::USER_ERROR, LogLevel::ERROR];
    }

    #[DataProvider('toLevelDataProvider')]
    #[Test]
    public function toLevelConsidersErrorSeverity(int $type, int $error, string $expected): void
    {
        self::assertSame($expected, Type::toLevel($type, $error));
    }

    #[Test]
    public function toLevelWithoutErrorReturnsTypeLevel(): void
    {
        self::assertSame(LogLevel::ERROR, Type::toLevel(Type::ERROR));
        self::assertSame(LogLevel::INFO, Type::toLevel(Type::LOGIN));
    }
}
