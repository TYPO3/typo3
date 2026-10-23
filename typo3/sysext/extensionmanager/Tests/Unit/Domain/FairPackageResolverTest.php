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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Extensionmanager\Domain\FairPackageResolver;
use TYPO3\CMS\Extensionmanager\Domain\Model\PackageIdentifier;
use TYPO3\CMS\Extensionmanager\Exception\ExtensionNotFoundException;
use TYPO3\CMS\Extensionmanager\Remote\Dto\FairPackage;
use TYPO3\CMS\Extensionmanager\Remote\Dto\FairRelease;
use TYPO3\CMS\Extensionmanager\Remote\FairPackageClient;
use TYPO3\CMS\Extensionmanager\Remote\FairPackageMapper;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

#[AllowMockObjectsWithoutExpectations]
final class FairPackageResolverTest extends UnitTestCase
{
    /**
     * A client knowing one package with the versions 2.0.0, 1.1.0 and 1.0.0.
     */
    private function createSubject(): FairPackageResolver
    {
        $package = new FairPackage('did:web:extensions.typo3.org:news', 'news', 'georgringer/news', 'News', '', '', '', '', [
            new FairRelease('2.0.0', 0, [], [], '', 'sha256:aaa'),
            new FairRelease('1.1.0', 0, [], [], '', 'sha256:bbb'),
            new FairRelease('1.0.0', 0, [], [], '', 'sha256:ccc'),
        ]);
        $client = $this->createMock(FairPackageClient::class);
        $client->method('getPackage')->willReturnCallback(static fn(string $key): ?FairPackage => $key === 'news' ? $package : null);
        return new FairPackageResolver($client, new FairPackageMapper());
    }

    #[Test]
    public function theIdentifiedVersionIsResolvedWithItsChecksum(): void
    {
        $extension = $this->createSubject()->getByPackageIdentifier(new PackageIdentifier('news', '1.1.0', 'ter'));

        self::assertSame('1.1.0', $extension->version);
        self::assertSame('ter', $extension->remote);
        self::assertSame('bbb', $extension->artifactSha256);
    }

    public static function unknownIdentifierProvider(): \Generator
    {
        yield 'unknown version' => [new PackageIdentifier('news', '3.0.0', 'ter')];
        yield 'unknown extension' => [new PackageIdentifier('nope', '1.0.0', 'ter')];
        yield 'another remote' => [new PackageIdentifier('news', '1.1.0', 'other')];
    }

    #[Test]
    #[DataProvider('unknownIdentifierProvider')]
    public function anUnknownIdentifierIsReported(PackageIdentifier $identifier): void
    {
        $this->expectException(ExtensionNotFoundException::class);
        $this->expectExceptionCode(1792929601);

        $this->createSubject()->getByPackageIdentifier($identifier);
    }

    #[Test]
    public function aVersionTheDocumentDoesNotListIsNotResolved(): void
    {
        $subject = $this->createSubject();

        self::assertSame('1.1.0', $subject->findOneByExtensionKeyAndVersion('news', '1.1.0')?->version);
        self::assertNull($subject->findOneByExtensionKeyAndVersion('news', '3.0.0'));
        self::assertNull($subject->findOneByExtensionKeyAndVersion('nope', '1.0.0'));
    }

    public static function unverifiableChecksumProvider(): \Generator
    {
        yield 'no package artifact' => [''];
        yield 'another algorithm' => ['sha512:aaa'];
        yield 'an empty sha256' => ['sha256:'];
    }

    /**
     * Downloading verifies the archive against the sha256 of the document, and
     * would skip that check without one. Such a release is left to the imported
     * list, which carries a checksum of its own.
     */
    #[Test]
    #[DataProvider('unverifiableChecksumProvider')]
    public function aReleaseWithoutSha256ChecksumIsNotResolved(string $checksum): void
    {
        $package = new FairPackage('did:web:extensions.typo3.org:news', 'news', '', '', '', '', '', '', [
            new FairRelease('1.0.0', 0, [], [], 'https://example.org/news/1.0.0/zip', $checksum),
        ]);
        $client = $this->createMock(FairPackageClient::class);
        $client->method('getPackage')->willReturn($package);
        $subject = new FairPackageResolver($client, new FairPackageMapper());

        self::assertNull($subject->findOneByExtensionKeyAndVersion('news', '1.0.0'));
        $this->expectException(ExtensionNotFoundException::class);
        $subject->getByPackageIdentifier(new PackageIdentifier('news', '1.0.0', 'ter'));
    }

    /**
     * A FAIR document names neither the review state of a release nor its
     * upload comment, so it cannot tell which versions may be offered for an
     * update, and leaves these questions to the fallback without a request.
     */
    #[Test]
    public function whichVersionsAreOfferedIsNotAnsweredFromTheDocument(): void
    {
        $client = $this->createMock(FairPackageClient::class);
        $client->expects($this->never())->method('getPackage');
        $subject = new FairPackageResolver($client, new FairPackageMapper());

        self::assertNull($subject->findHighestAvailableVersion('news'));
        self::assertSame([], $subject->findByVersionRangeAndExtensionKeyOrderedByVersion('news', 1000000, 2000000, false));
    }

    #[Test]
    public function aComposerPackageNameIsNotLookedUp(): void
    {
        $client = $this->createMock(FairPackageClient::class);
        $client->expects($this->never())->method('getPackage');
        $subject = new FairPackageResolver($client, new FairPackageMapper());

        self::assertNull($subject->findOneByExtensionKeyAndVersion('georgringer/news', '1.0.0'));
    }
}
