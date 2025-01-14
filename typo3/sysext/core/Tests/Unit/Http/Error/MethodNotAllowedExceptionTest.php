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

namespace TYPO3\CMS\Core\Tests\Unit\Http\Error;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Http\Error\MethodNotAllowedException;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class MethodNotAllowedExceptionTest extends UnitTestCase
{
    #[Test]
    public function constructorReturnsExceptionWithUppercasedHTTPMethods(): void
    {
        $actual = new MethodNotAllowedException(['foo', 'BAZ'], 1734024370);

        self::assertSame(['FOO', 'BAZ'], $actual->allowedMethods);
        self::assertSame('HTTP method is not allowed! Allowed method(s): FOO, BAZ', $actual->getMessage());
    }
}
