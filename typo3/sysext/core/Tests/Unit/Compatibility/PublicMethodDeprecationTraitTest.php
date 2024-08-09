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

namespace TYPO3\CMS\Core\Tests\Unit\Compatibility;

use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Tests\Unit\Compatibility\Fixtures\PublicMethodDeprecationTraitTextFixture;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class PublicMethodDeprecationTraitTest extends UnitTestCase
{
    #[Test]
    public function publicMethodCanBeCalled(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1528822131);
        (new PublicMethodDeprecationTraitTextFixture())->standardPublicMethod();
    }

    #[Test]
    public function protectedMethodNotHandledByTraitThrowsError(): void
    {
        $this->expectException(\Error::class);
        /** @phpstan-ignore-next-line */
        (new PublicMethodDeprecationTraitTextFixture())->standardProtectedMethod();
    }

    #[Test]
    public function notExistingMethodThrowsError(): void
    {
        $this->expectException(\Error::class);
        /** @phpstan-ignore-next-line */
        (new PublicMethodDeprecationTraitTextFixture())->doesNotExist();
    }

    /**
     * Do NOT remove this test, even though it has IgnoreDeprecations attribute,
     * we're testing the core's deprecation strategy here.
     */
    #[Test]
    #[IgnoreDeprecations]
    public function methodMadeProtectedCanBeCalled(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1528822485);
        /** @phpstan-ignore-next-line */
        (new PublicMethodDeprecationTraitTextFixture())->methodMadeProtected();
    }

    /**
     * Do NOT remove this test, even though it has IgnoreDeprecations attribute,
     * we're testing the core's deprecation strategy here.
     */
    #[Test]
    #[IgnoreDeprecations]
    public function methodMadeProtectedReturnsValue(): void
    {
        /** @phpstan-ignore-next-line */
        self::assertEquals('foo', (new PublicMethodDeprecationTraitTextFixture())->methodMadeProtectedWithReturn());
    }

    /**
     * Do NOT remove this test, even though it has IgnoreDeprecations attribute,
     * we're testing the core's deprecation strategy here.
     */
    #[Test]
    #[IgnoreDeprecations]
    public function methodMadeProtectedCanBeCalledWithArguments(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1528822486);
        /** @phpstan-ignore-next-line */
        (new PublicMethodDeprecationTraitTextFixture())->methodMadeProtectedWithArguments('foo', 'bar');
    }
}
