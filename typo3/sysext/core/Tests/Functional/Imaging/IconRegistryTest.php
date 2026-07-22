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

namespace TYPO3\CMS\Core\Tests\Functional\Imaging;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider;
use TYPO3\CMS\Core\Imaging\IconRegistry;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class IconRegistryTest extends FunctionalTestCase
{
    protected bool $initializeDatabase = false;
    protected array $testExtensionsToLoad = [
        'typo3/sysext/core/Tests/Functional/Fixtures/Extensions/test_irre_mm',
    ];

    #[Test]
    public function tcaTableWithIconfileRegistersTcaRecordsDefaultIcon(): void
    {
        $iconRegistry = $this->get(IconRegistry::class);
        self::assertTrue($iconRegistry->isRegistered('tcarecords-tx_testirremm_hotel-default'));

        $configuration = $iconRegistry->getIconConfigurationByIdentifier('tcarecords-tx_testirremm_hotel-default');
        self::assertSame(BitmapIconProvider::class, $configuration['provider']);
        self::assertSame('EXT:test_irre_mm/Resources/Public/Icons/icon_hotel.gif', $configuration['options']['source']);
    }

    #[Test]
    public function tcaTableWithoutIconfileDoesNotRegisterTcaRecordsDefaultIcon(): void
    {
        // pages relies on typeicon_classes and has no iconfile, so no default record icon is derived
        self::assertFalse($this->get(IconRegistry::class)->isRegistered('tcarecords-pages-default'));
    }
}
