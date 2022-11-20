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

namespace TYPO3\CMS\Core\Tests\Functional\DataHandling\DataHandler;

use TYPO3\CMS\Core\Tests\Functional\DataHandling\AbstractDataHandlerActionTestCase;
use TYPO3\CMS\Core\Tests\Functional\DataHandling\DataHandler\Fixtures\HookFixture;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;

/**
 * Tests triggering hook execution in DataHandler.
 */
class HookTest extends AbstractDataHandlerActionTestCase
{
    protected const VALUE_PageId = 89;
    protected const VALUE_ContentId = 297;
    protected const TABLE_Content = 'tt_content';
    protected const TABLE_Hotel = 'tx_testirreforeignfield_hotel';
    protected const TABLE_Category = 'sys_category';
    protected const FIELD_ContentHotel = 'tx_testirreforeignfield_hotels';
    protected const FIELD_Categories = 'categories';

    /**
     * @var HookFixture
     */
    protected $hookFixture;

    protected array $testExtensionsToLoad = [
        'typo3/sysext/core/Tests/Functional/Fixtures/Extensions/test_irre_foreignfield',
        'typo3/sysext/core/Tests/Functional/Fixtures/Extensions/irre_tutorial',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/DataSet/LiveDefaultPages.csv');
        $this->importCSVDataSet(__DIR__ . '/DataSet/LiveDefaultElements.csv');
        $this->backendUser->workspace = 0;

        $this->hookFixture = GeneralUtility::makeInstance(HookFixture::class);
        $this->hookFixture->purge();
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][__CLASS__] = HookFixture::class;
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass'][__CLASS__] = HookFixture::class;
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][__CLASS__]);
        unset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass'][__CLASS__]);
        unset($this->hookFixture);
    }

    /**
     * @test
     */
    public function verifyCleanReferenceIndex(): void
    {
        // The test verifies the imported data set has a clean reference index by the check in tearDown()
        self::assertTrue(true);
    }

    /**
     * @test
     */
    public function hooksAreExecutedForNewRecords(): void
    {
        $newTableIds = $this->actionService->createNewRecord(
            self::TABLE_Content,
            self::VALUE_PageId,
            ['header' => 'Testing #1']
        );
        $this->recordIds['newContentId'] = $newTableIds[self::TABLE_Content][0];

        $this->assertHookInvocationsCount([
                'processDatamap_beforeStart',
                'processDatamap_afterAllOperations',
        ], 1);

        $this->assertHookInvocationsPayload([
            'processDatamap_preProcessFieldArray',
            'processDatamap_postProcessFieldArray',
            'processDatamap_afterDatabaseOperations',
        ], [
            [
                'table' => self::TABLE_Content,
                'fieldArray' => [ 'header' => 'Testing #1', 'pid' => self::VALUE_PageId ],
            ],
        ]);
    }

    /**
     * @test
     */
    public function hooksAreExecutedForExistingRecords(): void
    {
        $this->actionService->modifyRecord(
            self::TABLE_Content,
            self::VALUE_ContentId,
            ['header' => 'Testing #1']
        );

        $this->assertHookInvocationsCount([
            'processDatamap_beforeStart',
            'processDatamap_afterAllOperations',
        ], 1);

        $this->assertHookInvocationsPayload([
            'processDatamap_preProcessFieldArray',
            'processDatamap_postProcessFieldArray',
            'processDatamap_afterDatabaseOperations',
        ], [
            [
                'table' => self::TABLE_Content,
                'fieldArray' => [ 'header' => 'Testing #1' ],
            ],
        ]);
    }

    /**
     * @test
     */
    public function hooksAreExecutedForNewRelations(): void
    {
        $contentNewId = StringUtility::getUniqueId('NEW');
        $hotelNewId = StringUtility::getUniqueId('NEW');
        $categoryNewId = StringUtility::getUniqueId('NEW');

        $this->actionService->modifyRecords(
            self::VALUE_PageId,
            [
                self::TABLE_Content => [
                    'uid' => $contentNewId,
                    'header' => 'Testing #1',
                    self::FIELD_ContentHotel => $hotelNewId,
                    self::FIELD_Categories => $categoryNewId,
                ],
                self::TABLE_Hotel => [
                    'uid' => $hotelNewId,
                    'title' => 'Hotel #1',
                ],
                self::TABLE_Category => [
                    'uid' => $categoryNewId,
                    'title' => 'Category #1',
                ],
            ]
        );

        $this->assertHookInvocationsCount([
            'processDatamap_beforeStart',
            'processDatamap_afterAllOperations',
        ], 1);

        $this->assertHookInvocationPayload(
            'processDatamap_preProcessFieldArray',
            [
                [
                    'table' => self::TABLE_Content,
                    'fieldArray' => [
                        'header' => 'Testing #1',
                        self::FIELD_ContentHotel => $hotelNewId,
                        self::FIELD_Categories => $categoryNewId,
                    ],
                ],
                [
                    'table' => self::TABLE_Hotel,
                    'fieldArray' => [ 'title' => 'Hotel #1' ],
                ],
                [
                    'table' => self::TABLE_Category,
                    'fieldArray' => [ 'title' => 'Category #1' ],
                ],
            ]
        );

        $this->assertHookInvocationPayload(
            'processDatamap_postProcessFieldArray',
            [
                [
                    'table' => self::TABLE_Content,
                    'fieldArray' => [ 'header' => 'Testing #1' ],
                ],
                [
                    'table' => self::TABLE_Hotel,
                    'fieldArray' => [ 'title' => 'Hotel #1' ],
                ],
                [
                    'table' => self::TABLE_Category,
                    'fieldArray' => [ 'title' => 'Category #1' ],
                ],
            ]
        );

        $this->assertHookInvocationPayload(
            'processDatamap_afterDatabaseOperations',
            [
                [
                    'table' => self::TABLE_Content,
                    'fieldArray' => [
                        'header' => 'Testing #1',
                        self::FIELD_ContentHotel => 1,
                        self::FIELD_Categories => 1,
                    ],
                ],
                [
                    'table' => self::TABLE_Hotel,
                    'fieldArray' => [ 'title' => 'Hotel #1' ],
                ],
                [
                    'table' => self::TABLE_Category,
                    'fieldArray' => [ 'title' => 'Category #1' ],
                ],
            ]
        );
    }

    /**
     * @test
     */
    public function hooksAreExecutedForExistingRelations(): void
    {
        $this->actionService->modifyRecord(
            self::TABLE_Content,
            self::VALUE_ContentId,
            [
                'header' => 'Testing #1',
                self::FIELD_ContentHotel => '3,4',
                self::FIELD_Categories => '28,29,30',
            ]
        );

        $this->assertHookInvocationsCount([
            'processDatamap_beforeStart',
            'processDatamap_afterAllOperations',
        ], 1);

        $this->assertHookInvocationPayload(
            'processDatamap_preProcessFieldArray',
            [
                [
                    'table' => self::TABLE_Content,
                    'fieldArray' => [
                        'header' => 'Testing #1',
                        self::FIELD_ContentHotel => '3,4',
                        self::FIELD_Categories => '28,29,30',
                    ],
                ],
            ]
        );

        $this->assertHookInvocationsPayload([
            'processDatamap_postProcessFieldArray',
            'processDatamap_afterDatabaseOperations',
        ], [
            [
                'table' => self::TABLE_Content,
                'fieldArray' => [
                    'header' => 'Testing #1',
                    self::FIELD_ContentHotel => 2,
                    self::FIELD_Categories => 3,
                ],
            ],
        ]);
    }

    /**
     * @param string[] $methodNames
     */
    protected function assertHookInvocationsCount(array $methodNames, int $count): void
    {
        $message = 'Unexpected invocations of method "%s"';
        foreach ($methodNames as $methodName) {
            $invocations = $this->hookFixture->findInvocationsByMethodName($methodName);
            self::assertCount(
                $count,
                $invocations,
                sprintf($message, $methodName)
            );
        }
    }

    /**
     * @param string[] $methodNames
     */
    protected function assertHookInvocationsPayload(array $methodNames, array $assertions): void
    {
        foreach ($methodNames as $methodName) {
            $this->assertHookInvocationPayload($methodName, $assertions);
        }
    }

    protected function assertHookInvocationPayload(string $methodName, array $assertions): void
    {
        $message = 'Unexpected hook payload amount found for method "%s"';
        $invocations = $this->hookFixture->findInvocationsByMethodName($methodName);
        self::assertNotNull($invocations);

        foreach ($assertions as $assertion) {
            $indexes = $this->findAllArrayValuesInHaystack($invocations, $assertion);
            self::assertCount(
                1,
                $indexes,
                sprintf($message, $methodName)
            );
            $index = $indexes[0];
            unset($invocations[$index]);
        }
    }

    /**
     * @return int[]
     */
    protected function findAllArrayValuesInHaystack(array $haystack, array $assertion): array
    {
        $found = [];
        foreach ($haystack as $index => $item) {
            if ($this->equals($assertion, $item)) {
                $found[] = $index;
            }
        }
        return $found;
    }

    protected function equals(array $left, array $right): bool
    {
        foreach ($left as $key => $leftValue) {
            $rightValue = $right[$key] ?? null;
            if (!is_array($leftValue) && (string)$leftValue !== (string)$rightValue) {
                return false;
            }
            if (is_array($leftValue)) {
                if (!$this->equals($leftValue, $rightValue)) {
                    return false;
                }
            }
        }
        return true;
    }
}
