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

namespace TYPO3\CMS\Extensionmanager\Tests\Unit\Remote;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Extensionmanager\Domain\Model\Dependency;
use TYPO3\CMS\Extensionmanager\Remote\Dto\FairPackage;
use TYPO3\CMS\Extensionmanager\Remote\Dto\FairRelease;
use TYPO3\CMS\Extensionmanager\Remote\FairPackageMapper;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class FairPackageMapperTest extends UnitTestCase
{
    private function package(FairRelease ...$releases): FairPackage
    {
        return new FairPackage(
            'did:web:extensions.typo3.org:news',
            'news',
            'georgringer/news',
            'News system',
            'Versatile news system',
            'https://docs.typo3.org/p/georgringer/news/main/en-us/',
            'Georg Ringer',
            'mail@example.org',
            $releases
        );
    }

    #[Test]
    public function aReleaseBecomesAnExtensionOfTheRemoteWithItsChecksum(): void
    {
        $newest = new FairRelease('14.1.2', 1791000000, [], [], 'https://example.org/news/14.1.2/zip', 'sha256:ad91339b0bbf');
        $older = new FairRelease('14.1.1', 1790000000, [], [], 'https://example.org/news/14.1.1/zip', 'sha256:cc9e0298ca43');
        $package = $this->package($newest, $older);

        $extension = (new FairPackageMapper())->map($package, $older);

        self::assertSame('news', $extension->extensionKey);
        self::assertSame('ter', $extension->remote);
        self::assertTrue($extension->resolvedFromRemote);
        self::assertSame('14.1.1', $extension->version);
        self::assertSame(14001001, $extension->integerVersion);
        self::assertSame('News system', $extension->title);
        self::assertSame('georgringer/news', $extension->composerName);
        self::assertSame('Georg Ringer', $extension->authorName);
        self::assertSame(1790000000, $extension->lastUpdated);
        self::assertSame('cc9e0298ca43', $extension->artifactSha256);
        self::assertFalse($extension->currentVersion);
        self::assertTrue((new FairPackageMapper())->map($package, $newest)->currentVersion);
    }

    #[Test]
    public function requirementsAreTurnedIntoDependenciesByExtensionKey(): void
    {
        $release = new FairRelease('14.1.2', 0, [
            'env:php' => '>=8.2.0 <=8.5.99',
            'env:typo3' => '>=13.4.29 <=14.99.99',
            'env:typo3:backend' => '>=13.4.29 <=14.99.99',
            'env:php-intl' => '*',
        ], [
            'did:web:extensions.typo3.org:rx_shariff' => '*',
            'env:typo3:seo' => '*',
        ]);

        $extension = (new FairPackageMapper())->map($this->package($release), $release);

        $dependencies = [];
        foreach ($extension->getDependencies() as $dependency) {
            /** @var Dependency $dependency */
            $dependencies[$dependency->getIdentifier()] = [$dependency->getType(), $dependency->getLowestVersion(), $dependency->getHighestVersion()];
        }
        ksort($dependencies);
        self::assertSame([
            'backend' => ['depends', '13.4.29', '14.99.99'],
            'php' => ['depends', '8.2.0', '8.5.99'],
            'rx_shariff' => ['suggests', '', ''],
            'seo' => ['suggests', '', ''],
            'typo3' => ['depends', '13.4.29', '14.99.99'],
        ], $dependencies);
        self::assertSame('13.4.29', $extension->getTypo3Dependency()->getLowestVersion());
    }
}
