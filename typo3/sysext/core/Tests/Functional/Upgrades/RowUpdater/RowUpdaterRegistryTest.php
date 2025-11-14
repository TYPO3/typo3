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

namespace TYPO3\CMS\Core\Tests\Functional\Upgrades\RowUpdater;

use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Upgrades\DatabaseRowsUpdateWizard;
use TYPO3\CMS\Core\Upgrades\RowUpdater\RowUpdaterRegistry;
use TYPO3\CMS\Core\Upgrades\UpgradeWizardRegistry;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Tests\TestRowUpdater\Service\InjectionTestService;
use TYPO3Tests\TestRowUpdater\Upgrades\RowUpdater\AttributeIdentifier;
use TYPO3Tests\TestRowUpdater\Upgrades\RowUpdater\AttributeNoIdentifier;
use TYPO3Tests\TestRowUpdater\Upgrades\RowUpdater\ManualIdentifier;
use TYPO3Tests\TestRowUpdater\Upgrades\RowUpdater\ManualNoIdentifier;

final class RowUpdaterRegistryTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3/sysext/core/Tests/Functional/Fixtures/Extensions/test_rowupdater',
    ];

    #[DoesNotPerformAssertions]
    #[Test]
    public function rowUpdaterRegistryIsInjectedIntoTestService(): void
    {
        $this->get(InjectionTestService::class);
    }

    #[Test]
    public function rowUpdaterWithPhpAttributeAndCustomIdentiferIsAvailableInRegistry(): void
    {
        $identifier = 'attributeRegisteredRowUpdaterWithCustomIdentifier';
        $rowUpdaterRegistry = $this->get(RowUpdaterRegistry::class);
        self::assertContains($identifier, $rowUpdaterRegistry->getRowUpdaterIdentifiers());
        self::assertArrayHasKey($identifier, $rowUpdaterRegistry->getRowUpdaters());
        self::assertSame(AttributeIdentifier::class, $rowUpdaterRegistry->getRowUpdaters()[$identifier]);
        self::assertTrue($rowUpdaterRegistry->hasRowUpdater($identifier));
        $rowUpdaterRegistry->getRowUpdater($identifier);
    }

    #[Test]
    public function rowUpdaterWithPhpAttributeAndClassNameIdentifierFallbackIsAvailableInRegistry(): void
    {
        $identifier = AttributeNoIdentifier::class;
        $rowUpdaterRegistry = $this->get(RowUpdaterRegistry::class);
        self::assertContains($identifier, $rowUpdaterRegistry->getRowUpdaterIdentifiers());
        self::assertArrayHasKey($identifier, $rowUpdaterRegistry->getRowUpdaters());
        self::assertSame(AttributeNoIdentifier::class, $rowUpdaterRegistry->getRowUpdaters()[$identifier]);
        self::assertTrue($rowUpdaterRegistry->hasRowUpdater($identifier));
        $rowUpdaterRegistry->getRowUpdater($identifier);
    }

    #[Test]
    public function rowUpdaterWithManualRegistrationAndCustomIdentifierIsAvailableInRegistry(): void
    {
        $identifier = 'manualRegisteredRowUpdaterWithCustomIdentifier';
        $rowUpdaterRegistry = $this->get(RowUpdaterRegistry::class);
        self::assertContains($identifier, $rowUpdaterRegistry->getRowUpdaterIdentifiers());
        self::assertArrayHasKey($identifier, $rowUpdaterRegistry->getRowUpdaters());
        self::assertSame(ManualIdentifier::class, $rowUpdaterRegistry->getRowUpdaters()[$identifier]);
        self::assertTrue($rowUpdaterRegistry->hasRowUpdater($identifier));
        $rowUpdaterRegistry->getRowUpdater($identifier);
    }

    #[Test]
    public function rowUpdaterWithManualRegistrationAndClassNameIdentifierFallbackIsAvailableInRegistry(): void
    {
        $identifier = ManualNoIdentifier::class;
        $rowUpdaterRegistry = $this->get(RowUpdaterRegistry::class);
        self::assertContains($identifier, $rowUpdaterRegistry->getRowUpdaterIdentifiers());
        self::assertArrayHasKey($identifier, $rowUpdaterRegistry->getRowUpdaters());
        self::assertSame(ManualNoIdentifier::class, $rowUpdaterRegistry->getRowUpdaters()[$identifier]);
        self::assertTrue($rowUpdaterRegistry->hasRowUpdater($identifier));
        $rowUpdaterRegistry->getRowUpdater($identifier);
    }

    #[Test]
    public function registeredRowUpdatersAreAnnouncedByDatabaseRowsUpdateWizard(): void
    {
        $wizard = $this->get(UpgradeWizardRegistry::class)->getUpgradeWizard('databaseRowsUpdateWizard');
        self::assertInstanceOf(DatabaseRowsUpdateWizard::class, $wizard);
        self::assertTrue($wizard->updateNecessary());
        $description = $wizard->getDescription();
        self::assertStringContainsString('RowUpdater test implementation using php attribute registration with identifier.', $description);
        self::assertStringContainsString('RowUpdater test implementation using php attribute registration without identifier', $description);
        self::assertStringContainsString('RowUpdater test implementation using manual registration with identifier.', $description);
        self::assertStringContainsString('RowUpdater test implementation using manual registration without identifier.', $description);
    }
}
