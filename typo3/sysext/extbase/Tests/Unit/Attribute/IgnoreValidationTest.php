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

namespace TYPO3\CMS\Extbase\Tests\Unit\Attribute;

use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Extbase\Attribute\IgnoreValidation;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class IgnoreValidationTest extends UnitTestCase
{
    #[Test]
    #[IgnoreDeprecations]
    public function constructorAcceptsConfigurationOptionsAsArray(): void
    {
        $this->expectUserDeprecationMessage(
            'Passing an array of configuration values to Extbase attributes will be removed in TYPO3 v15.0. ' .
            'Use explicit constructor parameters instead.',
        );

        $actual = new IgnoreValidation([
            'value' => 'foo',
        ]);

        self::assertSame('foo', $actual->argumentName);
    }

    #[Test]
    #[IgnoreDeprecations]
    public function constructorTriggersDeprecationErrorWhenArgumentNameIsPassed(): void
    {
        $this->expectUserDeprecationMessage(
            'Passing an argument name to an #[IgnoreValidation] attribute is deprecated and will be removed in ' .
            'TYPO3 v15.0. Place the attribute on the method parameter instead.',
        );

        new IgnoreValidation(argumentName: 'foo');
    }
}
