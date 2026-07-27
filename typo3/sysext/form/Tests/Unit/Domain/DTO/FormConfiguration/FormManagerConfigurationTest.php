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

namespace TYPO3\CMS\Form\Tests\Unit\Domain\DTO\FormConfiguration;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Form\Domain\DTO\FormConfiguration\FormManagerConfiguration;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class FormManagerConfigurationTest extends UnitTestCase
{
    #[Test]
    public function getSelectablePrototypeIdentifiersReturnsAllConfiguredIdentifiers(): void
    {
        $configuration = FormManagerConfiguration::fromArray([
            'selectablePrototypesConfiguration' => [
                100 => ['identifier' => 'standard'],
                200 => ['identifier' => 'custom'],
            ],
        ]);

        self::assertSame(['standard', 'custom'], $configuration->getSelectablePrototypeIdentifiers());
    }

    #[Test]
    public function getSelectablePrototypeIdentifiersSkipsEntriesWithoutIdentifier(): void
    {
        $configuration = FormManagerConfiguration::fromArray([
            'selectablePrototypesConfiguration' => [
                100 => ['identifier' => 'standard'],
                200 => ['label' => 'without identifier'],
                300 => ['identifier' => 'custom'],
            ],
        ]);

        self::assertSame(['standard', 'custom'], $configuration->getSelectablePrototypeIdentifiers());
    }

    #[Test]
    public function getSelectablePrototypeIdentifiersReturnsEmptyArrayWhenNoneConfigured(): void
    {
        $configuration = FormManagerConfiguration::fromArray([]);

        self::assertSame([], $configuration->getSelectablePrototypeIdentifiers());
    }

    #[Test]
    public function getSelectablePrototypeReturnsMatchingConfiguration(): void
    {
        $configuration = FormManagerConfiguration::fromArray([
            'selectablePrototypesConfiguration' => [
                100 => ['identifier' => 'standard'],
                200 => ['identifier' => 'custom'],
            ],
        ]);

        self::assertSame('custom', $configuration->getSelectablePrototype('custom')?->identifier);
        self::assertNull($configuration->getSelectablePrototype('unknown'));
    }
}
