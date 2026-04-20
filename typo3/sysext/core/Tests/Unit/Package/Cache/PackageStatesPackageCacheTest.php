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

namespace TYPO3\CMS\Core\Tests\Unit\Package\Cache;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Cache\Frontend\PhpFrontend;
use TYPO3\CMS\Core\Package\Cache\PackageStatesPackageCache;
use TYPO3\CMS\Core\Package\Exception\PackageManagerCacheUnavailableException;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class PackageStatesPackageCacheTest extends UnitTestCase
{
    private string $packageStatesFile;

    protected function setUp(): void
    {
        parent::setUp();
        $this->packageStatesFile = sys_get_temp_dir() . '/PackageStates_' . uniqid('', true) . '.php';
    }

    protected function tearDown(): void
    {
        if (file_exists($this->packageStatesFile)) {
            @unlink($this->packageStatesFile);
        }
        parent::tearDown();
    }

    #[Test]
    public function getIdentifierThrowsWhenPackageStatesFileIsMissing(): void
    {
        $subject = new PackageStatesPackageCache($this->packageStatesFile, $this->createMock(PhpFrontend::class));

        $this->expectException(PackageManagerCacheUnavailableException::class);
        $this->expectExceptionCode(1629817141);

        $subject->getIdentifier();
    }

    #[Test]
    public function getIdentifierDiffersWhenFileSizeChangesWithIdenticalMtime(): void
    {
        file_put_contents($this->packageStatesFile, '<?php return ["packages" => ["core" => 1]];');
        $pinnedMtime = filemtime($this->packageStatesFile);
        $subject = new PackageStatesPackageCache($this->packageStatesFile, $this->createMock(PhpFrontend::class));

        $beforeIdentifier = $subject->getIdentifier();

        $subject->invalidate();
        file_put_contents($this->packageStatesFile, '<?php return ["packages" => ["core" => 1, "container" => 1]];');

        self::assertSame($pinnedMtime, filemtime($this->packageStatesFile));
        self::assertNotSame($beforeIdentifier, $subject->getIdentifier());
    }
}
