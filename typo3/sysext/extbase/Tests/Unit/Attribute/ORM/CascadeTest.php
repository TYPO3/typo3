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

namespace TYPO3\CMS\Extbase\Tests\Unit\Attribute\ORM;

use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Extbase\Attribute\ORM\Cascade;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class CascadeTest extends UnitTestCase
{
    #[Test]
    #[IgnoreDeprecations]
    public function constructorAcceptsConfigurationOptionsAsArray(): void
    {
        $this->expectUserDeprecationMessage(
            'Passing an array of configuration values to Extbase attributes will be removed in TYPO3 v15.0. ' .
            'Use explicit constructor parameters instead.',
        );

        $actual = new Cascade([
            'value' => 'remove',
        ]);

        self::assertSame('remove', $actual->value);
    }
}
