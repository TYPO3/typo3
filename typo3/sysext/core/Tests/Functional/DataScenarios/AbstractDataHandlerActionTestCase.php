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

namespace TYPO3\CMS\Core\Tests\Functional\DataScenarios;

use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\WorkspaceAspect;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ReferenceIndex;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Log\LogDataTrait;
use TYPO3\TestingFramework\Core\Functional\Framework\DataHandling\ActionService;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Functional test for the DataHandler.
 *
 * DO NOT (ab)use this abstract in other classes than ext:core Functional/DataHandling/DataScenarios!
 */
abstract class AbstractDataHandlerActionTestCase extends FunctionalTestCase
{
    use LogDataTrait;

    protected const VALUE_BackendUserId = 1;
    protected const VALUE_WorkspaceId = 0;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8'],
        'DA' => ['id' => 1, 'title' => 'Dansk', 'locale' => 'da_DK.UTF8'],
        'DE' => ['id' => 2, 'title' => 'Deutsch', 'locale' => 'de_DE.UTF-8'],
    ];

    /** The number of log entries is asserted. This should usually be 0. */
    protected int $expectedErrorLogEntries = 0;
    protected array $recordIds = [];

    protected ActionService $actionService;
    protected BackendUserAuthentication $backendUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users_admin.csv');
        $this->backendUser = $this->setUpBackendUser(self::VALUE_BackendUserId);
        // Note late static binding - Workspace related tests override the constant
        $this->setWorkspaceId(static::VALUE_WorkspaceId);
        $this->actionService = new ActionService();
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($this->backendUser);
    }

    protected function tearDown(): void
    {
        $this->assertErrorLogEntries();
        $this->assertCleanReferenceIndex();
        parent::tearDown();
    }

    protected function setWorkspaceId(int $workspaceId): void
    {
        $this->backendUser->workspace = $workspaceId;
        $this->get(Context::class)->setAspect('workspace', new WorkspaceAspect($workspaceId));
    }

    /**
     * Asserts correct number of warning and error log entries.
     */
    private function assertErrorLogEntries(): void
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('sys_log');
        $queryBuilder->getRestrictions()->removeAll();
        $statement = $queryBuilder
            ->select('*')
            ->from('sys_log')
            ->where(
                $queryBuilder->expr()->in(
                    'error',
                    $queryBuilder->createNamedParameter([1, 2], Connection::PARAM_INT_ARRAY)
                )
            )
            ->executeQuery();
        $actualErrorLogEntries = (int)$queryBuilder
            ->count('uid')
            ->executeQuery()
            ->fetchOne();
        $entryMessages = array_map(
            function (array $entry) {
                return $this->formatLogDetails($entry['details'] ?? '', $entry['log_data'] ?? '');
            },
            $statement->fetchAllAssociative()
        );
        if ($actualErrorLogEntries !== $this->expectedErrorLogEntries) {
            $failureMessage = sprintf('Expected %d entries in sys_log, but got %d' . LF, $this->expectedErrorLogEntries, $actualErrorLogEntries);
            $failureMessage .= '* ' . implode(LF . '* ', $entryMessages) . LF;
            self::fail($failureMessage);
        }
    }

    /**
     * Similar to log entries, verify DataHandler tests end up with a clean reference index.
     */
    private function assertCleanReferenceIndex(): void
    {
        $referenceIndex = $this->get(ReferenceIndex::class);
        $referenceIndexFixResult = $referenceIndex->updateIndex(true);
        if (count($referenceIndexFixResult['errors']) > 0) {
            self::fail('Reference index not clean. ' . LF . implode(LF, $referenceIndexFixResult['errors']));
        }
    }
}
