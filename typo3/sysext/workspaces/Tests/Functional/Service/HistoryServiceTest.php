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

namespace TYPO3\CMS\Workspaces\Tests\Functional\Service;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Workspaces\Service\HistoryService;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class HistoryServiceTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['workspaces'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/HistoryServiceUserTypes.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);
    }

    #[Test]
    public function backendUserIsResolvedForBackendEntries(): void
    {
        // Entries are returned in reverse order, the backend entry is the older one
        $entry = $this->get(HistoryService::class)->getHistory('pages', 1)[1];

        self::assertSame('admin', $entry['user']);
        self::assertSame(1, $entry['user_uid']);
        self::assertNotSame('', $entry['user_avatar']);
    }

    #[Test]
    public function frontendUserIsNotResolvedAsBackendUser(): void
    {
        // The frontend user uid 1 must not be attributed to backend user uid 1
        $entry = $this->get(HistoryService::class)->getHistory('pages', 1)[0];

        self::assertSame('unknown', $entry['user']);
        self::assertSame('', $entry['user_realName']);
        self::assertSame(0, $entry['user_uid']);
        self::assertSame('', $entry['user_avatar']);
    }
}
