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
use TYPO3\CMS\Extensionmanager\Domain\Model\Extension;
use TYPO3\CMS\Extensionmanager\Domain\Model\PackageIdentifier;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class PackageIdentifierTest extends UnitTestCase
{
    #[Test]
    public function identifierExposesPackageKeyVersionAndRemote(): void
    {
        $identifier = new PackageIdentifier('my_package', '1.2.3', 'ter');

        self::assertSame('my_package', $identifier->packageKey);
        self::assertSame('1.2.3', $identifier->version);
        self::assertSame('ter', $identifier->remote);
    }

    #[Test]
    public function extensionDerivesPackageIdentifierFromItsProperties(): void
    {
        $extension = new Extension();
        $extension->extensionKey = 'my_package';
        $extension->version = '1.2.3';
        $extension->remote = 'ter';

        self::assertSame('my_package', $extension->getPackageKey());

        $identifier = $extension->getPackageIdentifier();
        self::assertSame('my_package', $identifier->packageKey);
        self::assertSame('1.2.3', $identifier->version);
        self::assertSame('ter', $identifier->remote);
    }
}
