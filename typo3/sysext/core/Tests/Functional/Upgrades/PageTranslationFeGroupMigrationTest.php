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

namespace TYPO3\CMS\Core\Tests\Functional\Upgrades;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Upgrades\PageTranslationFeGroupMigration;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class PageTranslationFeGroupMigrationTest extends FunctionalTestCase
{
    #[Test]
    public function feGroupOfLiveTranslationsIsSynchronizedFromDefaultLanguagePage(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/PageTranslationFeGroupBase.csv');
        $subject = $this->get(PageTranslationFeGroupMigration::class);
        self::assertTrue($subject->updateNecessary());
        self::assertTrue($subject->executeUpdate());
        self::assertFalse($subject->updateNecessary());
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/PageTranslationFeGroupMigrated.csv');
    }

    #[Test]
    public function updateIsNotNecessaryIfTranslationsAreSynchronized(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/PageTranslationFeGroupMigrated.csv');
        self::assertFalse($this->get(PageTranslationFeGroupMigration::class)->updateNecessary());
    }
}
