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
use TYPO3\CMS\Extensionmanager\Domain\Model\PackageIdentifier;
use TYPO3\CMS\Extensionmanager\Domain\Repository\ExtensionRepository;
use TYPO3\CMS\Extensionmanager\Exception\ExtensionNotFoundException;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class ExtensionRepositoryTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = [
        'extensionmanager',
    ];

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

    private function createRemoteExtensionRecord(string $extensionKey, string $version): void
    {
        $this->getConnectionPool()
            ->getConnectionForTable('tx_extensionmanager_domain_model_extension')
            ->insert(
                'tx_extensionmanager_domain_model_extension',
                [
                    'extension_key' => $extensionKey,
                    'remote' => 'ter',
                    'version' => $version,
                    'title' => $extensionKey,
                    'current_version' => 1,
                    'review_state' => 0,
                ]
            );
    }
}
