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

namespace TYPO3\CMS\Core\Tests\Functional\Upgrades\PageDoktypeLinkMigration;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Upgrades\PageDoktypeLinkMigration;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class DroppedUrlFieldTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['install'];

    #[Test]
    public function linkFieldUpdated(): void
    {
        // Tests not to crash when `pages.url` has been already renamed or removed.
        $subject = $this->get(PageDoktypeLinkMigration::class);
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DroppedUrlField/dataset_import.csv');
        self::assertFalse($subject->updateNecessary());
        self::assertTrue($subject->executeUpdate());
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/DroppedUrlField/dataset_migrated.csv');
    }
}
