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
    public function getIdentifierDiffersAfterInvalidateWhenPackageStatesFileWasModified(): void
    {
        file_put_contents($this->packageStatesFile, '<?php return ["packages" => ["core" => 1]];');
        // This fakes the last file modification to have happened in the past.
        touch($this->packageStatesFile, time() - 3600);

        $subject = new PackageStatesPackageCache($this->packageStatesFile, $this->createMock(PhpFrontend::class));
        $beforeIdentifier = $subject->getIdentifier();
        $subject->invalidate();
        file_put_contents($this->packageStatesFile, '<?php return ["packages" => ["core" => 1, "container" => 1]];');

        self::assertNotSame($beforeIdentifier, $subject->getIdentifier());
    }

    #[Test]
    public function getIdentifierDiffersWhenPackageStatesFileModifiedWithinOneSecondResolution(): void
    {
        file_put_contents($this->packageStatesFile, '<?php return ["packages" => ["core" => 1]];');
        $subject = new PackageStatesPackageCache($this->packageStatesFile, $this->createMock(PhpFrontend::class));
        // This test assumes, that it will never take more than one second
        // to get from this execution point in this test...
        $beforeIdentifier = $subject->getIdentifier();

        $subject->invalidate();

        file_put_contents($this->packageStatesFile, '<?php return ["packages" => ["core" => 1, "container" => 1]];');

        // ...to this point. Therefore this will never get a new identifier, if the
        // identifier is based on mtime alone. In practice this case never
        // really happens in extension manager extension activation, because bulk activations
        // happen with only one PackageStates.php activation, but it is still
        // a good thing, that we have file size as second meta value to be included
        // in the cache identifier to also cover this theoretical case
        self::assertNotSame($beforeIdentifier, $subject->getIdentifier());
    }
}
