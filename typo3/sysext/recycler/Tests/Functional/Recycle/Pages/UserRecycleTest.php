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

namespace TYPO3\CMS\Recycler\Tests\Functional\Recycle\Pages;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Recycler\Tests\Functional\Recycle\AbstractRecycleTestCase;

final class UserRecycleTest extends AbstractRecycleTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/Database/pages.csv');
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/Database/be_groups.csv');
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/Database/be_users.csv');
        // Set up "editor" user
        $backendUser = $this->setUpBackendUser(2);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);
    }

    #[Test]
    public function retrieveDeletedPagesNoRecursion(): void
    {
        $deletedPages = $this->getDeletedPages(1, 0);
        self::assertCount(1, $deletedPages);
        self::assertArrayHasKey('pages', $deletedPages);
        self::assertCount(2, $deletedPages['pages']);
        self::assertGreaterThan(0, (int)($deletedPages['pages'][0]['uid'] ?? 0));
        self::assertSame(3, (int)$deletedPages['pages'][0]['uid']);
    }

    #[Test]
    public function retrieveDeletedPagesOneLevelRecursion(): void
    {
        $deletedPages = $this->getDeletedPages(1, 1);
        self::assertCount(1, $deletedPages);
        self::assertArrayHasKey('pages', $deletedPages);
        self::assertCount(3, $deletedPages['pages']);
        self::assertGreaterThan(0, (int)($deletedPages['pages'][0]['uid'] ?? 0));
        self::assertSame(3, (int)$deletedPages['pages'][0]['uid']);
    }

    #[Test]
    public function canNotRetrieveDeletedPagesOutsideWebmount(): void
    {
        $deletedPages = $this->getDeletedPages(6, 0);
        self::assertCount(0, $deletedPages);
    }

    #[Test]
    public function canNotRetrieveDeletedWithNoAccess(): void
    {
        $deletedPages = $this->getDeletedPages(7, 0);
        self::assertCount(0, $deletedPages);
    }
}
