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

namespace TYPO3\CMS\Core\Tests\Functional\DataScenarios\IrreForeignField\Nesting;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Tests\Functional\DataScenarios\Fixtures\NestedRunErrorHook;
use TYPO3\CMS\Core\Tests\Functional\DataScenarios\Fixtures\RunRecordingHook;
use TYPO3\CMS\Core\Tests\Functional\DataScenarios\IrreForeignField\AbstractActionTestCase;

/**
 * Pins how many nested DataHandler runs an action starts today, how often the
 * run-level hooks fire, and what the outer instance collects from the nested
 * runs. The numbers are expected to go down when nesting is removed.
 */
final class ActionTest extends AbstractActionTestCase
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
                'runs' => 1,
                'nested runs' => 0,
                'hook calls' => [
                    'processDatamap_beforeStart' => 1,
                    'processDatamap_afterAllOperations' => 1,
                    'processCmdmap_beforeStart' => 0,
                    'processCmdmap_afterFinish' => 0,
                ],
            ],
            $this->describeRuns()
        );
    }

    #[Test]
    public function deleteParentContent(): void
    {
        parent::deleteParentContent();

        self::assertSame(
            [
                'runs' => 2,
                'nested runs' => 1,
                'hook calls' => [
                    'processDatamap_beforeStart' => 1,
                    'processDatamap_afterAllOperations' => 1,
                    'processCmdmap_beforeStart' => 1,
                    'processCmdmap_afterFinish' => 1,
                ],
            ],
            $this->describeRuns()
        );
    }

    #[Test]
    public function copyParentContent(): void
    {
        parent::copyParentContent();

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
            $this->describeCopyMapping()
        );
    }

    #[Test]
    public function copyPage(): void
    {
        parent::copyPage();

        self::assertSame(
            [
                'runs' => 5,
                'nested runs' => 4,
                'hook calls' => [
                    'processDatamap_beforeStart' => 4,
                    'processDatamap_afterAllOperations' => 4,
                    'processCmdmap_beforeStart' => 1,
                    'processCmdmap_afterFinish' => 1,
                ],
            ],
            $this->describeRuns()
        );
        self::assertSame(
            [
                self::TABLE_Page => [self::VALUE_PageId],
                self::TABLE_Content => [self::VALUE_ContentIdFirst, self::VALUE_ContentIdLast],
                self::TABLE_Hotel => [2, 3, 4, 5],
                self::TABLE_Offer => [5, 6, 7, 8],
                self::TABLE_Price => [7, 8, 9, 10, 11, 12, 13],
            ],
            $this->describeCopyMapping()
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
                self::TABLE_Content => [self::VALUE_ContentIdLast],
                self::TABLE_Hotel => [5],
                self::TABLE_Offer => [8],
                self::TABLE_Price => [13],
            ],
            $this->describeCopyMapping()
        );
    }

    #[Test]
    public function copyParentContentToLanguageWithAllChildren(): void
    {
        parent::copyParentContentToLanguageWithAllChildren();

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
                self::TABLE_Content => [self::VALUE_ContentIdLast],
                self::TABLE_Hotel => [5],
                self::TABLE_Offer => [8],
                self::TABLE_Price => [13],
            ],
            $this->describeCopyMapping()
        );
    }

    #[Test]
    public function errorsOfNestedRunsAfterDeleteParentContent(): void
    {
        $this->expectedErrorLogEntries = 1;
        $this->logErrorsInNestedRuns();
        parent::deleteParentContent();

        self::assertSame(['[1.5]: Error of a nested datamap run'], $this->actionService->getDataHandler()->errorLog);
    }

    #[Test]
    public function errorsOfNestedRunsAfterCopyParentContent(): void
    {
        $this->expectedErrorLogEntries = 2;
        $this->logErrorsInNestedRuns();
        parent::copyParentContent();

        self::assertSame(array_fill(0, 2, '[1.5]: Error of a nested datamap run'), $this->actionService->getDataHandler()->errorLog);
    }

    #[Test]
    public function errorsOfNestedRunsAfterCopyPage(): void
    {
        $this->expectedErrorLogEntries = 4;
        $this->logErrorsInNestedRuns();
        parent::copyPage();

        self::assertSame(array_fill(0, 4, '[1.5]: Error of a nested datamap run'), $this->actionService->getDataHandler()->errorLog);
    }

    #[Test]
    public function errorsOfNestedRunsAfterLocalizeParentContentWithAllChildren(): void
    {
        $this->expectedErrorLogEntries = 5;
        $this->logErrorsInNestedRuns();
        parent::localizeParentContentWithAllChildren();

        self::assertSame(array_fill(0, 5, '[1.5]: Error of a nested datamap run'), $this->actionService->getDataHandler()->errorLog);
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
     * @return array<string, list<int>> table => uids of the copied records, sorted
     */
    private function describeCopyMapping(): array
    {
        $copyMapping = [];
        foreach ($this->actionService->getDataHandler()->copyMappingArray_merged as $table => $records) {
            $uids = array_map(intval(...), array_keys($records));
            sort($uids);
            $copyMapping[$table] = $uids;
        }
        ksort($copyMapping);
        return $copyMapping;
    }
}
