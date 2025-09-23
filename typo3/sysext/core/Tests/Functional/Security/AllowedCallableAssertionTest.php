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

namespace TYPO3\CMS\Core\Tests\Functional\Security;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Security\AllowedCallableAssertion;
use TYPO3\CMS\Core\Security\AllowedCallableException;
use TYPO3\CMS\Core\Tests\Functional\Security\Fixtures\UserFunctionClass;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class AllowedCallableAssertionTest extends FunctionalTestCase
{
    public static function allowedDataProvider(): \Generator
    {
        include_once __DIR__ . '/Fixtures/GlobalUserFunction.php';

        yield 'allowed InvokableClass->__invoke' => [
            [UserFunctionClass::class, '__invoke'],
            true,
        ];
        yield 'allowed InvokableClass->instanceMethod' => [
            [UserFunctionClass::class, 'instanceMethod'],
            true,
        ];
        yield 'allowed InvokableClass::staticMethod' => [
            [UserFunctionClass::class, 'staticMethod'],
            true,
        ];
        yield 'allowed InvokableClass::staticMethod as closure' => [
            [UserFunctionClass::staticMethod(...)],
            true,
        ];
        yield 'allowed global function' => [
            ['\globalUserFunction'],
            true,
        ];
        yield 'allowed global function as closure' => [
            [\globalUserFunction(...)],
            true,
        ];
    }

    public static function disallowedDataProvider(): \Generator
    {
        yield 'disallowed anonymous closure' => [
            [static fn(): true => true],
            false,
        ];
        yield 'disallowed DataHandler->start' => [
            [DataHandler::class, 'start'],
            false,
        ];
        yield 'disallowed non-existing function' => [
            'nonExistingFunction',
            null,
        ];
        yield 'disallowed existing function' => [
            'hash',
            false,
        ];
        yield 'incorrect callable format' => [
            ['a', 'b', 'c'],
            null,
        ];
    }

    #[Test]
    #[DataProvider('allowedDataProvider')]
    #[DataProvider('disallowedDataProvider')]
    public function isTrustedReturnsExpectedValue(mixed $callable, ?bool $expectation): void
    {
        /** @var AllowedCallableAssertion $subject */
        $subject = $this->get(AllowedCallableAssertion::class);
        self::assertSame($expectation, $subject->isTrusted($callable));
    }

    #[Test]
    #[DataProvider('disallowedDataProvider')]
    public function assertCallableThrowsException(mixed $callable, ?bool $expectation): void
    {
        $this->expectException(AllowedCallableException::class);
        $this->expectExceptionCode($expectation === null ? 1758626231 : 1758626232);
        /** @var AllowedCallableAssertion $subject */
        $subject = $this->get(AllowedCallableAssertion::class);
        $subject->assertCallable($callable);
    }
}
