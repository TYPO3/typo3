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

namespace TYPO3\CMS\Core\Tests\Functional\DataScenarios\IrreForeignField\WorkspacesNesting;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Tests\Functional\DataScenarios\Fixtures\NestedRunErrorHook;
use TYPO3\CMS\Core\Tests\Functional\DataScenarios\Fixtures\RunRecordingHook;
use TYPO3\CMS\Core\Tests\Functional\DataScenarios\IrreForeignField\AbstractActionWorkspacesTestCase;

/**
 * Pins how many nested DataHandler runs an action in a workspace starts today,
 * how often the run-level hooks fire, and what the outer instance collects
 * from the nested runs. The numbers are expected to go down when nesting is
 * removed.
 */
final class ActionTest extends AbstractActionWorkspacesTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass']['nesting-test'] = RunRecordingHook::class;
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass']['nesting-test'] = RunRecordingHook::class;
        RunRecordingHook::reset();
    }

    #[Test]
    public function modifyParentContent(): void
    {
        parent::modifyParentContent();

        self::assertSame(
            [
                'runs' => 3,
                'nested runs' => 2,
                'hook calls' => [
                    'processDatamap_beforeStart' => 2,
                    'processDatamap_afterAllOperations' => 2,
                    'processCmdmap_beforeStart' => 1,
                    'processCmdmap_afterFinish' => 1,
                ],
            ],
            $this->describeRuns()
        );
        self::assertSame(
            [
                self::TABLE_Content => [self::VALUE_ContentIdLast],
                self::TABLE_Hotel => [5],
                self::TABLE_Offer => [8],
                self::TABLE_Price => [13],
            ],
            $this->describeAutoVersionIdMap()
        );
    }

    #[Test]
    public function copyParentContent(): void
    {
        parent::copyParentContent();

        self::assertSame(
            [
                'runs' => 6,
                'nested runs' => 5,
                'hook calls' => [
                    'processDatamap_beforeStart' => 5,
                    'processDatamap_afterAllOperations' => 5,
                    'processCmdmap_beforeStart' => 1,
                    'processCmdmap_afterFinish' => 1,
                ],
            ],
            $this->describeRuns()
        );
        self::assertSame(
            [
                self::TABLE_Content => [299],
                self::TABLE_Hotel => [6],
                self::TABLE_Offer => [9],
                self::TABLE_Price => [14],
            ],
            $this->describeAutoVersionIdMap()
        );
    }

    #[Test]
    public function copyPage(): void
    {
        parent::copyPage();

        self::assertSame(
            [
                'runs' => 20,
                'nested runs' => 19,
                'hook calls' => [
                    'processDatamap_beforeStart' => 19,
                    'processDatamap_afterAllOperations' => 19,
                    'processCmdmap_beforeStart' => 1,
                    'processCmdmap_afterFinish' => 1,
                ],
            ],
            $this->describeRuns()
        );
        self::assertSame(
            [
                self::TABLE_Page => [91],
                self::TABLE_Content => [299, 300],
                self::TABLE_Hotel => [6, 7, 8, 9],
                self::TABLE_Offer => [9, 10, 11, 12],
                self::TABLE_Price => [14, 15, 16, 17, 18, 19, 20],
            ],
            $this->describeAutoVersionIdMap()
        );
    }

    #[Test]
    public function localizeParentContentWithAllChildren(): void
    {
        parent::localizeParentContentWithAllChildren();

        self::assertSame(
            [
                'runs' => 6,
                'nested runs' => 5,
                'hook calls' => [
                    'processDatamap_beforeStart' => 5,
                    'processDatamap_afterAllOperations' => 5,
                    'processCmdmap_beforeStart' => 1,
                    'processCmdmap_afterFinish' => 1,
                ],
            ],
            $this->describeRuns()
        );
        self::assertSame(
            [
                self::TABLE_Content => [299],
                self::TABLE_Hotel => [6],
                self::TABLE_Offer => [9],
                self::TABLE_Price => [14],
            ],
            $this->describeAutoVersionIdMap()
        );
    }

    #[Test]
    public function errorsOfNestedRunsAfterModifyParentContent(): void
    {
        $this->expectedErrorLogEntries = 2;
        $this->logErrorsInNestedRuns();
        parent::modifyParentContent();

        self::assertSame(['[1.5]: Error of a nested datamap run', '[1.5]: Error of a nested cmdmap run'], $this->actionService->getDataHandler()->errorLog);
    }

    #[Test]
    public function errorsOfNestedRunsAfterCopyParentContent(): void
    {
        $this->expectedErrorLogEntries = 5;
        $this->logErrorsInNestedRuns();
        parent::copyParentContent();

        self::assertSame(array_fill(0, 5, '[1.5]: Error of a nested datamap run'), $this->actionService->getDataHandler()->errorLog);
    }

    #[Test]
    public function errorsOfNestedRunsAfterCopyPage(): void
    {
        $this->expectedErrorLogEntries = 19;
        $this->logErrorsInNestedRuns();
        parent::copyPage();

        self::assertSame(array_fill(0, 19, '[1.5]: Error of a nested datamap run'), $this->actionService->getDataHandler()->errorLog);
    }

    private function logErrorsInNestedRuns(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass']['nesting-test-error'] = NestedRunErrorHook::class;
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass']['nesting-test-error'] = NestedRunErrorHook::class;
    }

    private function describeRuns(): array
    {
        return [
            'runs' => RunRecordingHook::countRuns(),
            'nested runs' => RunRecordingHook::countNestedRuns(),
            'hook calls' => RunRecordingHook::countCallsPerHook(),
        ];
    }

    /**
     * @return array<string, list<int>> table => uids of the records that got a workspace version, sorted
     */
    private function describeAutoVersionIdMap(): array
    {
        $autoVersionIds = [];
        foreach ($this->actionService->getDataHandler()->autoVersionIdMap as $table => $records) {
            $uids = array_map(intval(...), array_keys($records));
            sort($uids);
            $autoVersionIds[$table] = $uids;
        }
        ksort($autoVersionIds);
        return $autoVersionIds;
    }
}
