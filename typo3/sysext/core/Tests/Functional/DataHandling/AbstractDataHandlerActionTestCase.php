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

namespace TYPO3\CMS\Core\Tests\Functional\DataHandling;

use Symfony\Component\Yaml\Yaml;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\WorkspaceAspect;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ReferenceIndex;
use TYPO3\CMS\Core\Log\LogDataTrait;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\Framework\Constraint\RequestSection\DoesNotHaveRecordConstraint;
use TYPO3\TestingFramework\Core\Functional\Framework\Constraint\RequestSection\HasRecordConstraint;
use TYPO3\TestingFramework\Core\Functional\Framework\Constraint\RequestSection\StructureDoesNotHaveRecordConstraint;
use TYPO3\TestingFramework\Core\Functional\Framework\Constraint\RequestSection\StructureHasRecordConstraint;
use TYPO3\TestingFramework\Core\Functional\Framework\DataHandling\ActionService;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Functional test for the DataHandler
 */
abstract class AbstractDataHandlerActionTestCase extends FunctionalTestCase
{
    use LogDataTrait;
    protected const VALUE_BackendUserId = 1;
    protected const VALUE_WorkspaceId = 0;

    /**
     * If this value is NULL, log entries are not considered.
     * If it's an integer value, the number of log entries is asserted.
     *
     * @var int|null
     */
    protected $expectedErrorLogEntries = 0;

    /**
     * @var array
     */
    protected $recordIds = [];

    protected ActionService $actionService;
    protected BackendUserAuthentication $backendUser;

    /**
     * Default Site Configuration
     * @var array
     */
    protected $siteLanguageConfiguration = [
        1 => [
            'title' => 'Dansk',
            'enabled' => true,
            'languageId' => 1,
            'base' => '/dk/',
            'locale' => 'da_DK.UTF-8',
            'flag' => 'dk',
            'fallbackType' => 'fallback',
            'fallbacks' => '0',
        ],
        2 => [
            'title' => 'Deutsch',
            'enabled' => true,
            'languageId' => 2,
            'base' => '/de/',
            'locale' => 'de_DE.UTF-8',
            'flag' => 'de',
            'fallbackType' => 'fallback',
            'fallbacks' => '1,0',
        ],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users_admin.csv');
        $this->backendUser = $this->setUpBackendUser(self::VALUE_BackendUserId);
        // Note late static binding - Workspace related tests override the constant
        $this->setWorkspaceId(static::VALUE_WorkspaceId);

        $this->actionService = new ActionService();
        Bootstrap::initializeLanguageObject();
    }

    protected function tearDown(): void
    {
        $this->assertErrorLogEntries();
        $this->assertCleanReferenceIndex();
        unset($this->actionService);
        unset($this->recordIds);
        parent::tearDown();
    }

    /**
     * Create a simple site config for the tests that
     * call a frontend page.
     */
    protected function setUpFrontendSite(int $pageId, array $additionalLanguages = []): void
    {
        $languages = [
            0 => [
                'title' => 'English',
                'enabled' => true,
                'languageId' => 0,
                'base' => '/',
                'locale' => 'en_US.UTF-8',
                'navigationTitle' => '',
                'direction' => '',
                'flag' => 'us',
            ],
        ];
        $languages = array_merge($languages, $additionalLanguages);
        $configuration = [
            'rootPageId' => $pageId,
            'base' => '/',
            'languages' => $languages,
            'errorHandling' => [],
            'routes' => [],
        ];
        GeneralUtility::mkdir_deep($this->instancePath . '/typo3conf/sites/testing/');
        $yamlFileContents = Yaml::dump($configuration, 99, 2);
        $fileName = $this->instancePath . '/typo3conf/sites/testing/config.yaml';
        GeneralUtility::writeFile($fileName, $yamlFileContents);
        // Ensure that no other site configuration was cached before
        $cache = GeneralUtility::makeInstance(CacheManager::class)->getCache('core');
        if ($cache->has('sites-configuration')) {
            $cache->remove('sites-configuration');
        }
    }

    protected function setWorkspaceId(int $workspaceId): void
    {
        $this->backendUser->workspace = $workspaceId;
        GeneralUtility::makeInstance(Context::class)->setAspect('workspace', new WorkspaceAspect($workspaceId));
    }

    /**
     * Asserts correct number of warning and error log entries.
     *
     * @param string[]|null $expectedMessages
     */
    protected function assertErrorLogEntries(array $expectedMessages = null): void
    {
        if ($this->expectedErrorLogEntries === null && $expectedMessages === null) {
            return;
        }

        if ($expectedMessages !== null) {
            $expectedErrorLogEntries = count($expectedMessages);
        } else {
            $expectedErrorLogEntries = (int)$this->expectedErrorLogEntries;
        }

        $queryBuilder = $this->getConnectionPool()
            ->getQueryBuilderForTable('sys_log');
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
            static function (array $entry) {
                return self::formatLogDetails($entry['details'] ?? '', $entry['log_data'] ?? '');
            },
            $statement->fetchAllAssociative()
        );

        if ($expectedMessages !== null) {
            self::assertEqualsCanonicalizing($expectedMessages, $entryMessages);
        } elseif ($actualErrorLogEntries === $expectedErrorLogEntries) {
            self::assertSame($expectedErrorLogEntries, $actualErrorLogEntries);
        } else {
            $failureMessage = sprintf(
                'Expected %d entries in sys_log, but got %d' . LF,
                $expectedMessages,
                $actualErrorLogEntries
            );
            $failureMessage .= '* ' . implode(LF . '* ', $entryMessages) . LF;
            self::fail($failureMessage);
        }
    }

    /**
     * Similar to log entries, verify DataHandler tests end up with a clean reference index.
     */
    protected function assertCleanReferenceIndex(): void
    {
        $referenceIndex = GeneralUtility::makeInstance(ReferenceIndex::class);
        $referenceIndexFixResult = $referenceIndex->updateIndex(true);
        if (count($referenceIndexFixResult['errors']) > 0) {
            self::fail('Reference index not clean. ' . LF . implode(LF, $referenceIndexFixResult['errors']));
        }
    }

    protected function getRequestSectionHasRecordConstraint(): HasRecordConstraint
    {
        return new HasRecordConstraint();
    }

    protected function getRequestSectionDoesNotHaveRecordConstraint(): DoesNotHaveRecordConstraint
    {
        return new DoesNotHaveRecordConstraint();
    }

    protected function getRequestSectionStructureHasRecordConstraint(): StructureHasRecordConstraint
    {
        return new StructureHasRecordConstraint();
    }

    protected function getRequestSectionStructureDoesNotHaveRecordConstraint(): StructureDoesNotHaveRecordConstraint
    {
        return new StructureDoesNotHaveRecordConstraint();
    }
}
