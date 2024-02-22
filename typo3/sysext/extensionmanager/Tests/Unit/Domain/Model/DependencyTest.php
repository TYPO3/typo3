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

namespace TYPO3\CMS\Extensionmanager\Tests\Unit\Domain\Model;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Extensionmanager\Domain\Model\Dependency;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class DependencyTest extends UnitTestCase
{
    #[Test]
    public function getLowestAndHighestIntegerVersionsReturnsArrayWithVersions(): void
    {
        $subject = Dependency::createFromEmConf('ter', '1.0.0-2.0.0');
        self::assertSame(1000000, $subject->getLowestVersionAsInteger());
        self::assertSame(2000000, $subject->getHighestVersionAsInteger());
    }

    #[Test]
    public function isVersionCompatibleReturnsCorrectResult(): void
    {
        $dependency = Dependency::createFromEmConf('typo3', '9.5.0-10.4.99');
        self::assertTrue($dependency->isVersionCompatible('10.4.9'));
        self::assertFalse($dependency->isVersionCompatible('11.0.0'));
    }
}
