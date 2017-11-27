<?php
namespace TYPO3\CMS\Core\Tests\Functional\DataHandling;

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

use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Database\Connection;
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
    protected $testExtensionsToLoad = [
        'typo3/sysext/core/Tests/Functional/Fixtures/Extensions/irre_tutorial',
    ];

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

    protected function setUp()
    {
        parent::setUp();

        $this->backendUser = $this->setUpBackendUserFromFixture(self::VALUE_BackendUserId);
        // By default make tests on live workspace
        $this->backendUser->workspace = 0;

        $this->actionService = $this->getActionService();
        Bootstrap::getInstance()->initializeLanguageObject();
    }

    protected function tearDown()
    {
        $this->assertErrorLogEntries();
        unset($this->actionService);
        unset($this->recordIds);
        parent::tearDown();
    }

    /**
     * @return ActionService
     */
    protected function getActionService()
    {
        return GeneralUtility::makeInstance(
            ActionService::class
        );
    }

    /**
     * @param string $dataSetName
     */
    protected function importScenarioDataSet($dataSetName)
    {
        $fileName = rtrim($this->scenarioDataSetDirectory, '/') . '/' . $dataSetName . '.csv';
        $fileName = GeneralUtility::getFileAbsFileName($fileName);
        $this->importCSVDataSet($fileName);
    }

    protected function assertAssertionDataSet($dataSetName)
    {
        $fileName = rtrim($this->assertionDataSetDirectory, '/') . '/' . $dataSetName . '.csv';
        $fileName = GeneralUtility::getFileAbsFileName($fileName);
        $this->assertCSVDataSet($fileName);
    }

    /**
     * Asserts correct number of warning and error log entries.
     */
    protected function assertErrorLogEntries()
    {
        if ($this->expectedErrorLogEntries === null) {
            return;
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

        $actualErrorLogEntries = $statement->rowCount();
        if ($actualErrorLogEntries === $this->expectedErrorLogEntries) {
            $this->assertSame($this->expectedErrorLogEntries, $actualErrorLogEntries);
        } else {
            $failureMessage = 'Expected ' . $this->expectedErrorLogEntries . ' entries in sys_log, but got ' . $actualErrorLogEntries . LF;
            while ($entry = $statement->fetch()) {
                $entryData = unserialize($entry['log_data']);
                $entryMessage = vsprintf($entry['details'], $entryData);
                $failureMessage .= '* ' . $entryMessage . LF;
            }
            $this->fail($failureMessage);
        }
    }

    /**
     * @return HasRecordConstraint
     */
    protected function getRequestSectionHasRecordConstraint()
    {
        return new HasRecordConstraint();
    }

    /**
     * @return DoesNotHaveRecordConstraint
     */
    protected function getRequestSectionDoesNotHaveRecordConstraint()
    {
        return new DoesNotHaveRecordConstraint();
    }

    /**
     * @return StructureHasRecordConstraint
     */
    protected function getRequestSectionStructureHasRecordConstraint()
    {
        return new StructureHasRecordConstraint();
    }

    /**
     * @return StructureDoesNotHaveRecordConstraint
     */
    protected function getRequestSectionStructureDoesNotHaveRecordConstraint()
    {
        return new StructureDoesNotHaveRecordConstraint();
    }
}
