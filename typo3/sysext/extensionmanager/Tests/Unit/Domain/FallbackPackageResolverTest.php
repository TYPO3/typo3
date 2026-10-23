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

namespace TYPO3\CMS\Extensionmanager\Tests\Unit\Domain;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\NullLogger;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Extensionmanager\Domain\FallbackPackageResolver;
use TYPO3\CMS\Extensionmanager\Domain\Model\Extension;
use TYPO3\CMS\Extensionmanager\Domain\Model\PackageIdentifier;
use TYPO3\CMS\Extensionmanager\Domain\PackageResolverInterface;
use TYPO3\CMS\Extensionmanager\Exception\ExtensionNotFoundException;
use TYPO3\CMS\Extensionmanager\Remote\ApiRequestFailedException;
use TYPO3\CMS\Extensionmanager\Remote\UnsupportedVersionConstraintException;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

#[AllowMockObjectsWithoutExpectations]
final class FallbackPackageResolverTest extends UnitTestCase
{
    private function extension(string $key): Extension
    {
        $extension = new Extension();
        $extension->extensionKey = $key;
        return $extension;
    }

    private function configuration(bool $offlineMode): ExtensionConfiguration
    {
        $configuration = $this->createMock(ExtensionConfiguration::class);
        $configuration->method('get')->willReturn($offlineMode ? '1' : '0');
        return $configuration;
    }

    #[Test]
    public function inOfflineModeTheRemoteIsNotAsked(): void
    {
        $identifier = new PackageIdentifier('news', '1.0.0', 'ter');
        $remote = $this->createMock(PackageResolverInterface::class);
        $remote->expects($this->never())->method('getByPackageIdentifier');
        $remote->expects($this->never())->method('findHighestAvailableVersion');
        $local = $this->createMock(PackageResolverInterface::class);
        $local->method('getByPackageIdentifier')->willReturn($this->extension('from_import'));
        $local->method('findHighestAvailableVersion')->willReturn($this->extension('from_import'));

        $subject = new FallbackPackageResolver($remote, $local, new NullLogger(), $this->configuration(true));

        self::assertSame('from_import', $subject->getByPackageIdentifier($identifier)->extensionKey);
        self::assertSame('from_import', $subject->findHighestAvailableVersion('news')->extensionKey);
    }

    #[Test]
    public function theRemoteAnswersWhileItCan(): void
    {
        $identifier = new PackageIdentifier('news', '1.0.0', 'ter');
        $remote = $this->createMock(PackageResolverInterface::class);
        $remote->expects($this->once())->method('getByPackageIdentifier')->with($identifier)->willReturn($this->extension('from_remote'));
        $local = $this->createMock(PackageResolverInterface::class);
        $local->expects($this->never())->method('getByPackageIdentifier');

        $subject = new FallbackPackageResolver($remote, $local, new NullLogger(), $this->configuration(false));

        self::assertSame('from_remote', $subject->getByPackageIdentifier($identifier)->extensionKey);
    }

    #[Test]
    public function theImportedListAnswersWhenTheRemoteCannot(): void
    {
        $remote = $this->createMock(PackageResolverInterface::class);
        $remote->method('findHighestAvailableVersion')->willThrowException(new ApiRequestFailedException('offline', 1789035174));
        $local = $this->createMock(PackageResolverInterface::class);
        $local->expects($this->once())->method('findHighestAvailableVersion')->with('news')->willReturn($this->extension('from_import'));

        $subject = new FallbackPackageResolver($remote, $local, new NullLogger(), $this->configuration(false));

        self::assertSame('from_import', $subject->findHighestAvailableVersion('news')->extensionKey);
    }

    /**
     * A release whose requirements cannot be converted would be checked against
     * a wrong range, so the imported list answers instead.
     */
    #[Test]
    public function theImportedListAnswersWhenTheRemoteSendsAnUnsupportedConstraint(): void
    {
        $identifier = new PackageIdentifier('news', '1.0.0', 'ter');
        $remote = $this->createMock(PackageResolverInterface::class);
        $remote->method('getByPackageIdentifier')->willThrowException(new UnsupportedVersionConstraintException('^13.4', 1794248547));
        $remote->method('findOneByExtensionKeyAndVersion')->willThrowException(new UnsupportedVersionConstraintException('^13.4', 1794248547));
        $local = $this->createMock(PackageResolverInterface::class);
        $local->method('getByPackageIdentifier')->willReturn($this->extension('from_import'));
        $local->method('findOneByExtensionKeyAndVersion')->willReturn($this->extension('from_import'));

        $subject = new FallbackPackageResolver($remote, $local, new NullLogger(), $this->configuration(false));

        self::assertSame('from_import', $subject->getByPackageIdentifier($identifier)->extensionKey);
        self::assertSame('from_import', $subject->findOneByExtensionKeyAndVersion('news', '1.0.0')->extensionKey);
    }

    /**
     * Unlike a listing, a package the remote does not know may still sit in the
     * imported list - a release older than the remote's document reaches back, say.
     */
    #[Test]
    public function theImportedListAnswersWhenTheRemoteDoesNotKnowThePackage(): void
    {
        $identifier = new PackageIdentifier('news', '1.0.0', 'ter');
        $remote = $this->createMock(PackageResolverInterface::class);
        $remote->method('getByPackageIdentifier')->willThrowException(new ExtensionNotFoundException('unknown', 1792929601));
        $remote->method('findOneByExtensionKeyAndVersion')->willReturn(null);
        $remote->method('findByVersionRangeAndExtensionKeyOrderedByVersion')->willReturn([]);
        $local = $this->createMock(PackageResolverInterface::class);
        $local->method('getByPackageIdentifier')->willReturn($this->extension('from_import'));
        $local->method('findOneByExtensionKeyAndVersion')->willReturn($this->extension('from_import'));
        $local->method('findByVersionRangeAndExtensionKeyOrderedByVersion')->willReturn([$this->extension('from_import')]);

        $subject = new FallbackPackageResolver($remote, $local, new NullLogger(), $this->configuration(false));

        self::assertSame('from_import', $subject->getByPackageIdentifier($identifier)->extensionKey);
        self::assertSame('from_import', $subject->findOneByExtensionKeyAndVersion('news', '1.0.0')->extensionKey);
        self::assertSame('from_import', $subject->findByVersionRangeAndExtensionKeyOrderedByVersion('news', 1, 2, false)[0]->extensionKey);
    }

    #[Test]
    public function aPackageUnknownToBothIsReportedByTheImportedList(): void
    {
        $identifier = new PackageIdentifier('nope', '1.0.0', 'ter');
        $remote = $this->createMock(PackageResolverInterface::class);
        $remote->method('getByPackageIdentifier')->willThrowException(new ExtensionNotFoundException('unknown remotely', 1792929601));
        $local = $this->createMock(PackageResolverInterface::class);
        $local->method('getByPackageIdentifier')->willThrowException(new ExtensionNotFoundException('unknown locally', 1784926081));

        $this->expectException(ExtensionNotFoundException::class);
        $this->expectExceptionMessage('unknown locally');

        new FallbackPackageResolver($remote, $local, new NullLogger(), $this->configuration(false))->getByPackageIdentifier($identifier);
    }

    #[Test]
    public function anythingButAFailedRequestOrAnUnknownPackagePropagates(): void
    {
        $remote = $this->createMock(PackageResolverInterface::class);
        $remote->method('findHighestAvailableVersion')->willThrowException(new \RuntimeException('bug', 1792929602));
        $local = $this->createMock(PackageResolverInterface::class);
        $local->expects($this->never())->method('findHighestAvailableVersion');

        $this->expectException(\RuntimeException::class);

        new FallbackPackageResolver($remote, $local, new NullLogger(), $this->configuration(false))->findHighestAvailableVersion('news');
    }
}
