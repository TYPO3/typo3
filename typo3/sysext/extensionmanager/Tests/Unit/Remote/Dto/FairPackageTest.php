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

namespace TYPO3\CMS\Extensionmanager\Tests\Unit\Remote\Dto;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Extensionmanager\Remote\Dto\FairPackage;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class FairPackageTest extends UnitTestCase
{
    /**
     * The shape TER serves at /fair/v1/packages/news, reduced to two releases.
     */
    private static function document(): array
    {
        return [
            '@context' => 'https://fair.pm/ns/metadata/v1',
            'id' => 'did:web:extensions.typo3.org:news',
            'type' => 'typo3-extension',
            'license' => 'GPL-2.0-or-later',
            'authors' => [['name' => 'Georg Ringer', 'email' => 'mail@example.org']],
            'security' => [['email' => 'security@typo3.org']],
            'slug' => 'news',
            'extension_key' => 'news',
            'package_name' => 'georgringer/news',
            'name' => 'News system',
            'description' => 'Versatile news system',
            'documentation' => 'https://docs.typo3.org/p/georgringer/news/main/en-us/',
            'releases' => [
                [
                    'version' => '14.1.1',
                    'date' => '2026-08-11T18:27:26+02:00',
                    'requires' => ['env:typo3' => '>=13.4.29 <=14.3.99'],
                    'suggests' => [],
                    'artifacts' => ['package' => [[
                        'url' => 'https://extensions.typo3.org/extension/download/news/14.1.1/zip',
                        'content-type' => 'application/zip',
                        'checksum' => 'sha256:cc9e0298ca43',
                    ]]],
                ],
                [
                    'version' => '14.1.2',
                    'date' => '2026-10-12T10:29:37+02:00',
                    'requires' => ['env:typo3' => '>=13.4.29 <=14.99.99'],
                    'suggests' => ['did:web:extensions.typo3.org:rx_shariff' => '*'],
                    'artifacts' => ['package' => [[
                        'url' => 'https://extensions.typo3.org/extension/download/news/14.1.2/zip',
                        'content-type' => 'application/zip',
                        'checksum' => 'sha256:ad91339b0bbf',
                    ]]],
                ],
            ],
        ];
    }

    #[Test]
    public function fromArrayParsesTheMetadataDocumentNewestReleaseFirst(): void
    {
        $package = FairPackage::fromArray(self::document());

        self::assertSame('did:web:extensions.typo3.org:news', $package->id);
        self::assertSame('news', $package->extensionKey);
        self::assertSame('georgringer/news', $package->composerName);
        self::assertSame('News system', $package->name);
        self::assertSame('Georg Ringer', $package->authorName);
        self::assertSame('mail@example.org', $package->authorEmail);
        self::assertSame(['14.1.2', '14.1.1'], array_map(static fn($release) => $release->version, $package->releases));
        self::assertSame('14.1.2', $package->getNewestRelease()->version);
        self::assertSame('14.1.1', $package->getRelease('14.1.1')->version);
        self::assertNull($package->getRelease('1.0.0'));
    }

    #[Test]
    public function aReleaseCarriesItsDateRequirementsAndArtifactChecksum(): void
    {
        $release = FairPackage::fromArray(self::document())->getRelease('14.1.2');

        self::assertSame(strtotime('2026-10-12T10:29:37+02:00'), $release->date);
        self::assertSame(['env:typo3' => '>=13.4.29 <=14.99.99'], $release->requires);
        self::assertSame(['did:web:extensions.typo3.org:rx_shariff' => '*'], $release->suggests);
        self::assertSame('https://extensions.typo3.org/extension/download/news/14.1.2/zip', $release->artifactUrl);
        self::assertSame('ad91339b0bbf', $release->getArtifactSha256());
    }

    #[Test]
    public function aChecksumOfAnotherAlgorithmIsNoSha256(): void
    {
        $document = self::document();
        $document['releases'][0]['artifacts']['package'][0]['checksum'] = 'sha384:abc';

        self::assertSame('', FairPackage::fromArray($document)->getRelease('14.1.1')->getArtifactSha256());
    }

    #[Test]
    public function theExtensionKeyFallsBackToTheSlugAndThenToTheIdentifier(): void
    {
        $document = self::document();
        unset($document['extension_key']);
        self::assertSame('news', FairPackage::fromArray($document)->extensionKey);

        unset($document['slug']);
        self::assertSame('news', FairPackage::fromArray($document)->extensionKey);
    }

    #[Test]
    public function aDocumentWithoutAnIdentifierIsNoPackage(): void
    {
        self::assertNull(FairPackage::fromArray(null));
        self::assertNull(FairPackage::fromArray(['status' => 404]));
    }

    #[Test]
    public function releasesWithoutAVersionAreLeftOut(): void
    {
        $document = self::document();
        $document['releases'][] = ['date' => '2026-01-01T00:00:00+00:00'];
        $document['releases'][] = 'not a release';

        self::assertCount(2, FairPackage::fromArray($document)->releases);
    }
}
