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

namespace TYPO3\CMS\Core\Tests\UnitDeprecated\Compatibility;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Tests\UnitDeprecated\Compatibility\Fixtures\PublicMethodDeprecationTraitTextFixture;
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
        (new PublicMethodDeprecationTraitTextFixture())->standardProtectedMethod();
    }

    #[Test]
    public function notExistingMethodThrowsError(): void
    {
        $this->expectException(\Error::class);
        (new PublicMethodDeprecationTraitTextFixture())->doesNotExist();
    }

    #[Test]
    public function methodMadeProtectedCanBeCalled(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1528822485);
        (new PublicMethodDeprecationTraitTextFixture())->methodMadeProtected();
    }

    #[Test]
    public function methodMadeProtectedReturnsValue(): void
    {
        self::assertEquals('foo', (new PublicMethodDeprecationTraitTextFixture())->methodMadeProtectedWithReturn());
    }

    #[Test]
    public function methodMadeProtectedCanBeCalledWithArguments(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1528822486);
        (new PublicMethodDeprecationTraitTextFixture())->methodMadeProtectedWithArguments('foo', 'bar');
    }
}
