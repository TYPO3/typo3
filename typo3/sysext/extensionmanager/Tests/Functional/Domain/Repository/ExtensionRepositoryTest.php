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

namespace TYPO3\CMS\Extensionmanager\Tests\Functional\Domain\Repository;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Extensionmanager\Domain\ExtensionCatalogueInterface;
use TYPO3\CMS\Extensionmanager\Domain\Model\Extension;
use TYPO3\CMS\Extensionmanager\Domain\Model\PackageIdentifier;
use TYPO3\CMS\Extensionmanager\Domain\Repository\ExtensionRepository;
use TYPO3\CMS\Extensionmanager\Exception\ExtensionNotFoundException;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class ExtensionRepositoryTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = [
        'extensionmanager',
    ];

    /**
     * The list views depend on the contract, not on the repository, so the
     * container has to hand out an implementation for it. Without the alias in
     * Services.yaml the "Get Extensions" module cannot be instantiated at all.
     */
    #[Test]
    public function theExtensionCatalogueContractIsServedByTheRepository(): void
    {
        $catalogue = $this->get(ExtensionCatalogueInterface::class);

        self::assertInstanceOf(ExtensionRepository::class, $catalogue);
        self::assertSame(0, $catalogue->countAll());
    }

    #[Test]
    public function getByPackageIdentifierReturnsExtensionForExistingRecord(): void
    {
        $this->createRemoteExtensionRecord('ext_dependency', '1.0.0');

        $extension = $this->get(ExtensionRepository::class)
            ->getByPackageIdentifier(new PackageIdentifier('ext_dependency', '1.0.0', 'ter'));

        self::assertSame('ext_dependency', $extension->extensionKey);
        self::assertSame('1.0.0', $extension->version);
        self::assertSame('ter', $extension->remote);
    }

    #[Test]
    public function getByPackageIdentifierThrowsExceptionForUnknownRecord(): void
    {
        $this->expectException(ExtensionNotFoundException::class);
        $this->expectExceptionCode(1784926081);

        $this->get(ExtensionRepository::class)
            ->getByPackageIdentifier(new PackageIdentifier('ext_dependency', '1.0.0', 'ter'));
    }

    #[Test]
    public function findOneByPackageIdentifierAppliesAllThreeConstraints(): void
    {
        $this->createRemoteExtensionRecord('ext_dependency', '1.0.0');
        $repository = $this->get(ExtensionRepository::class);

        self::assertNotNull($repository->findOneByPackageIdentifier(new PackageIdentifier('ext_dependency', '1.0.0', 'ter')));
        self::assertNull($repository->findOneByPackageIdentifier(new PackageIdentifier('other_ext', '1.0.0', 'ter')));
        self::assertNull($repository->findOneByPackageIdentifier(new PackageIdentifier('ext_dependency', '2.0.0', 'ter')));
        self::assertNull($repository->findOneByPackageIdentifier(new PackageIdentifier('ext_dependency', '1.0.0', 'unknown-remote')));
    }

    /**
     * The catalogue is ordered by "last_updated", which is not unique. Without a
     * tie breaker the database is free to return rows in any order within one
     * timestamp, and offset based paging would then repeat or skip extensions.
     */
    #[Test]
    public function findAllReturnsTheRequestedPageInAStableOrder(): void
    {
        foreach (['epsilon', 'delta', 'charlie', 'bravo', 'alpha'] as $extensionKey) {
            $this->createRemoteExtensionRecord($extensionKey, '1.0.0', ['last_updated' => 1757260800]);
        }
        $repository = $this->get(ExtensionRepository::class);

        self::assertSame(['alpha', 'bravo'], $this->extensionKeysOf($repository->findAll(0, 2)));
        self::assertSame(['charlie', 'delta'], $this->extensionKeysOf($repository->findAll(2, 2)));
        self::assertSame(['epsilon'], $this->extensionKeysOf($repository->findAll(4, 2)));
    }

    #[Test]
    public function findAllOrdersTheNewestExtensionFirst(): void
    {
        $this->createRemoteExtensionRecord('older', '1.0.0', ['last_updated' => 1600000000]);
        $this->createRemoteExtensionRecord('newer', '1.0.0', ['last_updated' => 1700000000]);

        self::assertSame(
            ['newer', 'older'],
            $this->extensionKeysOf($this->get(ExtensionRepository::class)->findAll(0, 10))
        );
    }

    #[Test]
    public function countAllCountsEveryRecordFindAllPagesThrough(): void
    {
        $this->createRemoteExtensionRecord('alpha', '1.0.0');
        $this->createRemoteExtensionRecord('bravo', '1.0.0');
        $this->createRemoteExtensionRecord('charlie', '1.0.0', ['current_version' => 0]);

        self::assertSame(2, $this->get(ExtensionRepository::class)->countAll());
    }

    #[Test]
    public function findByTitleOrAuthorNameOrExtensionKeyPagesThroughRankedResults(): void
    {
        $this->createRemoteExtensionRecord('news', '1.0.0', ['title' => 'Anything']);
        $this->createRemoteExtensionRecord('news_extra', '1.0.0', ['title' => 'Anything']);
        $this->createRemoteExtensionRecord('by_title', '1.0.0', ['title' => 'all the news']);
        $this->createRemoteExtensionRecord('by_author', '1.0.0', ['title' => 'Anything', 'author_name' => 'news reporter']);
        $this->createRemoteExtensionRecord('unrelated', '1.0.0', ['title' => 'Anything']);
        $repository = $this->get(ExtensionRepository::class);

        self::assertSame(
            ['news', 'news_extra'],
            $this->extensionKeysOf($repository->findByTitleOrAuthorNameOrExtensionKey('news', 0, 2))
        );
        self::assertSame(
            ['by_title', 'by_author'],
            $this->extensionKeysOf($repository->findByTitleOrAuthorNameOrExtensionKey('news', 2, 2))
        );
        self::assertSame([], $this->extensionKeysOf($repository->findByTitleOrAuthorNameOrExtensionKey('news', 4, 2)));
    }

    #[Test]
    public function countByTitleOrAuthorNameOrExtensionKeyCountsEveryMatch(): void
    {
        $this->createRemoteExtensionRecord('news', '1.0.0', ['title' => 'Anything']);
        $this->createRemoteExtensionRecord('news_extra', '1.0.0', ['title' => 'Anything']);
        $this->createRemoteExtensionRecord('by_title', '1.0.0', ['title' => 'all the news']);
        $this->createRemoteExtensionRecord('by_author', '1.0.0', ['title' => 'Anything', 'author_name' => 'news reporter']);
        $this->createRemoteExtensionRecord('unrelated', '1.0.0', ['title' => 'Anything']);

        self::assertSame(4, $this->get(ExtensionRepository::class)->countByTitleOrAuthorNameOrExtensionKey('news'));
    }

    /**
     * @param Extension[] $extensions
     * @return string[]
     */
    private function extensionKeysOf(array $extensions): array
    {
        return array_map(static fn(Extension $extension): string => $extension->extensionKey, $extensions);
    }

    /**
     * @param array<string, mixed> $additionalFields
     */
    private function createRemoteExtensionRecord(string $extensionKey, string $version, array $additionalFields = []): void
    {
        $this->getConnectionPool()
            ->getConnectionForTable('tx_extensionmanager_domain_model_extension')
            ->insert(
                'tx_extensionmanager_domain_model_extension',
                array_merge(
                    [
                        'extension_key' => $extensionKey,
                        'remote' => 'ter',
                        'version' => $version,
                        'title' => $extensionKey,
                        'description' => '',
                        'author_name' => '',
                        'last_updated' => 0,
                        'current_version' => 1,
                        'review_state' => 0,
                    ],
                    $additionalFields
                )
            );
    }
}
