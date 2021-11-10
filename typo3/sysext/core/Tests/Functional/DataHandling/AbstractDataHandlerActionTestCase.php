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
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\WorkspaceAspect;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ReferenceIndex;
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
    const VALUE_BackendUserId = 1;

    /**
     * @var bool True if assertCleanReferenceIndex() should be called in tearDown(). Set to false only with care.
     */
    protected $assertCleanReferenceIndex = true;

    /**
     * @var string
     */
    protected $scenarioDataSetDirectory;

    /**
     * @var string
     */
    protected $assertionDataSetDirectory;

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
    protected $pathsToLinkInTestInstance = [
        'typo3/sysext/core/Tests/Functional/Fixtures/Frontend/AdditionalConfiguration.php' => 'typo3conf/AdditionalConfiguration.php',
    ];

    /**
     * @var array
     */
    protected $recordIds = [];

    /**
     * @var ActionService
     */
    protected $actionService;

    /**
     * @var \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
     */
    protected $backendUser;

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
            'typo3Language' => 'dk',
            'locale' => 'da_DK.UTF-8',
            'iso-639-1' => 'da',
            'flag' => 'dk',
            'fallbackType' => 'fallback',
            'fallbacks' => '0',
        ],
        2 => [
            'title' => 'Deutsch',
            'enabled' => true,
            'languageId' => 2,
            'base' => '/de/',
            'typo3Language' => 'de',
            'locale' => 'de_DE.UTF-8',
            'iso-639-1' => 'de',
            'flag' => 'de',
            'fallbackType' => 'fallback',
            'fallbacks' => '1,0',
        ],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->backendUser = $this->setUpBackendUserFromFixture(self::VALUE_BackendUserId);
        // By default make tests on live workspace
        $this->setWorkspaceId(0);

        $this->actionService = $this->getActionService();
        Bootstrap::initializeLanguageObject();
    }

    protected function tearDown(): void
    {
        $this->assertErrorLogEntries();
        if ($this->assertCleanReferenceIndex) {
            $this->assertCleanReferenceIndex();
        }
        unset($this->actionService);
        unset($this->recordIds);
        parent::tearDown();
    }

    /**
     * Create a simple site config for the tests that
     * call a frontend page.
     *
     * @param int $pageId
     * @param array $additionalLanguages
     */
    protected function setUpFrontendSite(int $pageId, array $additionalLanguages = []): void
    {
        $languages = [
            0 => [
                'title' => 'English',
                'enabled' => true,
                'languageId' => 0,
                'base' => '/',
                'typo3Language' => 'default',
                'locale' => 'en_US.UTF-8',
                'iso-639-1' => 'en',
                'navigationTitle' => '',
                'hreflang' => '',
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

    /**
     * @param int $workspaceId
     */
    protected function setWorkspaceId(int $workspaceId): void
    {
        $this->backendUser->workspace = $workspaceId;
        GeneralUtility::makeInstance(Context::class)->setAspect('workspace', new WorkspaceAspect($workspaceId));
    }

    /**
     * @return ActionService
     */
    protected function getActionService(): ActionService
    {
        return GeneralUtility::makeInstance(
            ActionService::class
        );
    }

    /**
     * @param string $dataSetName
     */
    protected function importScenarioDataSet($dataSetName): void
    {
        $fileName = rtrim($this->scenarioDataSetDirectory, '/') . '/' . $dataSetName . '.csv';
        $fileName = GeneralUtility::getFileAbsFileName($fileName);
        $this->importCSVDataSet($fileName);
    }

    protected function assertAssertionDataSet($dataSetName): void
    {
        $fileName = rtrim($this->assertionDataSetDirectory, '/') . '/' . $dataSetName . '.csv';
        $fileName = GeneralUtility::getFileAbsFileName($fileName);
        $this->assertCSVDataSet($fileName);
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
            ->execute();

        $actualErrorLogEntries = (int)$queryBuilder
            ->count('uid')
            ->execute()
            ->fetchOne();

        $entryMessages = array_map(
            static function (array $entry) {
                $entryData = (array)unserialize($entry['log_data'], ['allowed_classes' => false]);
                return vsprintf($entry['details'], $entryData);
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

    /**
     * @return HasRecordConstraint
     */
    protected function getRequestSectionHasRecordConstraint(): HasRecordConstraint
    {
        return new HasRecordConstraint();
    }

    /**
     * @return DoesNotHaveRecordConstraint
     */
    protected function getRequestSectionDoesNotHaveRecordConstraint(): DoesNotHaveRecordConstraint
    {
        return new DoesNotHaveRecordConstraint();
    }

    /**
     * @return StructureHasRecordConstraint
     */
    protected function getRequestSectionStructureHasRecordConstraint(): StructureHasRecordConstraint
    {
        return new StructureHasRecordConstraint();
    }

    /**
     * @return StructureDoesNotHaveRecordConstraint
     */
    protected function getRequestSectionStructureDoesNotHaveRecordConstraint(): StructureDoesNotHaveRecordConstraint
    {
        return new StructureDoesNotHaveRecordConstraint();
    }
}
