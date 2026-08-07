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

namespace TYPO3\CMS\Redirects\Tests\Functional\Hooks;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class DataHandlerPermissionGuardHookTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['redirects'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/be_groups.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/be_users.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/sys_redirect.csv');
        $this->setUpBackendUser(1);
    }

    #[Test]
    public function nonAdminUserCanTogglePartialFieldOfRedirectWithAllowedSourceHost(): void
    {
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        // The record list "toggle visibility" action only ever sends the
        // changed field, not the full record.
        $dataHandler->start(['sys_redirect' => [1 => ['disabled' => 1]]], []);
        $dataHandler->process_datamap();

        self::assertSame([], $dataHandler->errorLog);
        $record = BackendUtility::getRecord('sys_redirect', 1, 'disabled', '', false);
        self::assertSame(1, (int)$record['disabled']);
    }

    #[Test]
    public function nonAdminUserCannotTogglePartialFieldOfRedirectWithDisallowedSourceHost(): void
    {
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start(['sys_redirect' => [2 => ['disabled' => 1]]], []);
        $dataHandler->process_datamap();

        self::assertNotSame([], $dataHandler->errorLog);
        $record = BackendUtility::getRecord('sys_redirect', 2, 'disabled', '', false);
        self::assertSame(0, (int)$record['disabled']);
    }

    #[Test]
    public function nonAdminUserCanEditShortUrlRedirectWithAllowedSourceHost(): void
    {
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        // The "short_url" record type's edit form does not contain a
        // source_host field, so it is never part of the submitted data.
        $dataHandler->start([
            'sys_redirect' => [3 => ['redirect_type' => 'short_url', 'target' => 'https://example.com/updated']],
        ], []);
        $dataHandler->process_datamap();

        self::assertSame([], $dataHandler->errorLog);
        $record = BackendUtility::getRecord('sys_redirect', 3, 'target', '', false);
        self::assertSame('https://example.com/updated', $record['target']);
    }
}
