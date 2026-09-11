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
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Extensionmanager\Domain\TerExtensionCatalogue;
use TYPO3\CMS\Extensionmanager\Enum\ExtensionCategory;
use TYPO3\CMS\Extensionmanager\Enum\ExtensionState;
use TYPO3\CMS\Extensionmanager\Remote\Dto\TerExtension;
use TYPO3\CMS\Extensionmanager\Remote\Dto\TerExtensionListResult;
use TYPO3\CMS\Extensionmanager\Remote\Dto\TerExtensionVersion;
use TYPO3\CMS\Extensionmanager\Remote\TerApiClient;
use TYPO3\CMS\Extensionmanager\Remote\TerExtensionMapper;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

#[AllowMockObjectsWithoutExpectations]
final class TerExtensionCatalogueTest extends UnitTestCase
{
    /**
     * The listing queries the catalogue was asked, without the probes for how
     * many extensions a TYPO3 version has.
     *
     * @var list<array{string, int, int, int, ?string, ?string}>
     */
    private array $queries = [];

    /**
     * The paginator counts in offsets, the API in pages.
     */
    #[Test]
    public function anOffsetIsTranslatedIntoAPageNumber(): void
    {
        $this->createSubject($this->createClient(new TerExtensionListResult(2870)))->findAll(20, 10);

        self::assertSame([['', 13, 3, 10, null, null]], $this->queries);
    }

    #[Test]
    public function aSearchTermIsPassedOnWithTheSamePaging(): void
    {
        $this->createSubject($this->createClient(new TerExtensionListResult(80)))
            ->findByTitleOrAuthorNameOrExtensionKey('news', 0, 10);

        self::assertSame([['news', 13, 1, 10, null, null]], $this->queries);
    }

    #[Test]
    public function countingAsksForTheSmallestPossiblePage(): void
    {
        $client = $this->createMock(TerApiClient::class);
        $client->expects($this->atLeastOnce())
            ->method('query')
            ->with('', 13, 1, 1)
            ->willReturn(new TerExtensionListResult(2870));

        self::assertSame(2870, $this->createSubject($client)->countAll());
    }

    /**
     * Early in a major version's life, and on its development branch, TER has
     * nothing for it yet. What fits the previous one beats an empty module.
     */
    #[Test]
    public function thePreviousMajorVersionIsListedWhenNothingIsPublishedForTheRunningOne(): void
    {
        $subject = $this->createSubject($this->createClient(new TerExtensionListResult(500), [13 => 0, 12 => 500]));

        self::assertSame(500, $subject->countAll());
        $subject->findAll(0, 10);
        $subject->findByTitleOrAuthorNameOrExtensionKey('news', 0, 10);
        self::assertSame(500, $subject->countByTitleOrAuthorNameOrExtensionKey('news'));
        $subject->findAllCommunityDistributions();

        self::assertSame([
            ['', 12, 1, 10, null, null],
            ['news', 12, 1, 10, null, null],
            ['news', 12, 1, 1, null, null],
            ['', 12, 1, 50, 'distribution', null],
        ], $this->queries);
    }

    /**
     * A search without a hit for the running version is an answer, and must not
     * send the search to the previous version.
     */
    #[Test]
    public function anEmptySearchResultStaysWithTheRunningMajorVersion(): void
    {
        $subject = $this->createSubject($this->createClient(new TerExtensionListResult(0)));

        self::assertSame(0, $subject->countByTitleOrAuthorNameOrExtensionKey('no_such_thing'));
        self::assertSame([['no_such_thing', 13, 1, 1, null, null]], $this->queries);
    }

    #[Test]
    public function anExtensionIsMappedOntoTheModelTheViewsExpect(): void
    {
        $client = $this->createClient(new TerExtensionListResult(1, [
            $this->remoteExtension('news', 'georg', 'plugin', 'stable'),
        ]));

        $extension = $this->createSubject($client)->findAll(0, 10)[0];

        self::assertSame('news', $extension->extensionKey);
        self::assertSame('14.1.1', $extension->version);
        self::assertSame('georg', $extension->ownerusername);
        self::assertSame(ExtensionCategory::Plugin->value, $extension->category);
        self::assertSame(ExtensionState::stable->value, $extension->state);
        self::assertSame(str_repeat('a', 64), $extension->artifactSha256);
        self::assertSame('georgringer/news', $extension->composerName);
        // Downloading resolves the registered remote through this identifier.
        self::assertSame('ter', $extension->remote);
    }

    /**
     * The API filters by owner, so the community list has to drop the official
     * ones itself - otherwise they show up twice.
     */
    #[Test]
    public function officialDistributionsAreAskedForByOwnerAndExcludedFromTheCommunityOnes(): void
    {
        $client = $this->createClient(new TerExtensionListResult(1, [
            $this->remoteExtension('introduction', 'typo3v4', 'distribution', 'stable'),
        ]));

        $official = $this->createSubject($client)->findAllOfficialDistributions();

        self::assertSame(['introduction'], array_map(static fn($e) => $e->extensionKey, $official));
        self::assertSame([['', 13, 1, 50, 'distribution', 'typo3v4']], $this->queries);
    }

    #[Test]
    public function theCommunityDistributionsLeaveOutTheOfficialOnes(): void
    {
        $client = $this->createClient(new TerExtensionListResult(2, [
            $this->remoteExtension('introduction', 'typo3v4', 'distribution', 'stable'),
            $this->remoteExtension('aimeos_dist', 'aimeos', 'distribution', 'stable'),
        ]));

        $community = $this->createSubject($client)->findAllCommunityDistributions();

        self::assertSame(['aimeos_dist'], array_map(static fn($e) => $e->extensionKey, $community));
        self::assertSame([['', 13, 1, 50, 'distribution', null]], $this->queries);
    }

    /**
     * "Show unsuitable" means: do not restrict by the running TYPO3 version.
     */
    #[Test]
    public function unsuitableDistributionsDropTheVersionFilter(): void
    {
        $this->createSubject($this->createClient(new TerExtensionListResult(0)))->findAllCommunityDistributions(true);

        self::assertSame([['', 0, 1, 50, 'distribution', null]], $this->queries);
    }

    /**
     * The imported list never offers a version TER flagged as insecure, and
     * reading TER live must not start to.
     */
    #[Test]
    public function insecureVersionsAreNotOffered(): void
    {
        $client = $this->createMock(TerApiClient::class);
        $client->method('getExtension')->willReturn($this->remoteExtension('news', 'georg', 'plugin', 'stable'));
        $client->method('getVersions')->willReturn([
            TerExtensionVersion::fromArray(['number' => '14.1.1', 'review_state' => 0]),
            TerExtensionVersion::fromArray(['number' => '14.1.0', 'review_state' => -1]),
            TerExtensionVersion::fromArray(['number' => '14.0.0', 'review_state' => 0]),
        ]);

        $versions = $this->createSubject($client)->findByExtensionKeyOrderedByVersion('news');

        self::assertSame(['14.1.1', '14.0.0'], array_map(static fn($e) => $e->version, $versions));
    }

    #[Test]
    public function anInsecureCurrentVersionIsNotOffered(): void
    {
        $client = $this->createMock(TerApiClient::class);
        $client->method('getExtension')->willReturn(TerExtension::fromArray([
            'key' => 'news',
            'current_version' => ['number' => '14.1.1', 'review_state' => -1],
        ]));

        self::assertNull($this->createSubject($client)->findOneByCurrentVersionByExtensionKey('news'));
    }

    #[Test]
    public function anExtensionWhoseCurrentVersionIsInsecureIsNotListed(): void
    {
        $insecure = TerExtension::fromArray([
            'key' => 'insecure_ext',
            'current_version' => ['number' => '1.0.0', 'category' => 'distribution', 'review_state' => -1],
        ]);
        $client = $this->createClient(new TerExtensionListResult(2, [
            $insecure,
            $this->remoteExtension('news', 'georg', 'distribution', 'stable'),
        ]));
        $subject = $this->createSubject($client);

        $keys = static fn(array $extensions): array => array_map(static fn($e) => $e->extensionKey, $extensions);
        self::assertSame(['news'], $keys($subject->findAll(0, 10)));
        self::assertSame(['news'], $keys($subject->findByTitleOrAuthorNameOrExtensionKey('ext', 0, 10)));
        self::assertSame(['news'], $keys($subject->findAllCommunityDistributions()));
    }

    /**
     * The API hands out at most 50 per page, and TER has more distributions
     * than that.
     */
    #[Test]
    public function distributionsAreReadBeyondTheFirstPage(): void
    {
        $client = $this->createClient(fn(int $page): TerExtensionListResult => new TerExtensionListResult(
            51,
            array_map(
                fn(int $i): TerExtension => $this->remoteExtension('dist_' . $i, 'someone', 'distribution', 'stable'),
                $page === 1 ? range(1, 50) : [51]
            )
        ));

        self::assertCount(51, $this->createSubject($client)->findAllCommunityDistributions());
        self::assertCount(2, $this->queries);
    }

    /**
     * Answers the probe for how many extensions a TYPO3 version has from
     * $catalogueSizes, and every other query with $answer, which is either the
     * result or a closure that receives the requested page.
     *
     * @param array<int, int> $catalogueSizes
     */
    private function createClient(TerExtensionListResult|\Closure $answer, array $catalogueSizes = [13 => 2870]): TerApiClient
    {
        $client = $this->createMock(TerApiClient::class);
        $client->method('query')->willReturnCallback(
            function (string $search, int $version, int $page = 1, int $perPage = 20, ?string $category = null, ?string $username = null) use ($answer, $catalogueSizes): TerExtensionListResult {
                if ([$search, $page, $perPage, $category, $username] === ['', 1, 1, null, null]) {
                    return new TerExtensionListResult($catalogueSizes[$version] ?? 0);
                }
                $this->queries[] = [$search, $version, $page, $perPage, $category, $username];
                return $answer instanceof \Closure ? $answer($page) : $answer;
            }
        );
        return $client;
    }

    private function createSubject(TerApiClient $client): TerExtensionCatalogue
    {
        $typo3Version = $this->createMock(Typo3Version::class);
        $typo3Version->method('getMajorVersion')->willReturn(13);
        return new TerExtensionCatalogue($client, new TerExtensionMapper(), $typo3Version);
    }

    private function remoteExtension(string $key, string $owner, string $category, string $state): TerExtension
    {
        return TerExtension::fromArray([
            'key' => $key,
            'owner' => $owner,
            'meta' => ['composer_name' => 'georgringer/news'],
            'current_version' => [
                'number' => '14.1.1',
                'title' => 'A title',
                'category' => $category,
                'state' => $state,
                'artifact_sha256' => str_repeat('a', 64),
            ],
        ]);
    }
}
