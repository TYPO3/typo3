<?php
declare(strict_types=1);
namespace TYPO3\CMS\Core\Tests\Functional\DataHandling\DataHandler;

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

use TYPO3\CMS\Core\Tests\Functional\DataHandling\AbstractDataHandlerActionTestCase;
use TYPO3\CMS\Core\Tests\Functional\DataHandling\DataHandler\Fixtures\HookFixture;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;

/**
 * Tests triggering hook execution in DataHandler.
 */
class HookTest extends AbstractDataHandlerActionTestCase
{
    const VALUE_PageId = 89;
    const VALUE_ContentId = 297;
    const TABLE_Content = 'tt_content';
    const TABLE_Hotel = 'tx_irretutorial_1nff_hotel';
    const TABLE_Category = 'sys_category';
    const FIELD_ContentHotel = 'tx_irretutorial_1nff_hotels';
    const FIELD_Categories = 'categories';

    /**
     * @var HookFixture
     */
    protected $hookFixture;

    /**
     * @var string
     */
    protected $scenarioDataSetDirectory = 'typo3/sysext/core/Tests/Functional/DataHandling/DataHandler/DataSet/';

    protected function setUp()
    {
        parent::setUp();
        $this->importScenarioDataSet('LiveDefaultPages');
        $this->importScenarioDataSet('LiveDefaultElements');
        $this->backendUser->workspace = 0;

        $this->hookFixture = GeneralUtility::makeInstance(HookFixture::class);
        $this->hookFixture->purge();
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][__CLASS__] = HookFixture::class;
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass'][__CLASS__] = HookFixture::class;
    }

    protected function tearDown()
    {
        parent::tearDown();

        unset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][__CLASS__]);
        unset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass'][__CLASS__]);
        unset($this->hookFixture);
    }

    /**
     * @test
     */
    public function hooksAreExecutedForNewRecords()
    {
        $newTableIds = $this->actionService->createNewRecord(
            self::TABLE_Content,
            self::VALUE_PageId,
            ['header' => 'Testing #1']
        );
        $this->recordIds['newContentId'] = $newTableIds[self::TABLE_Content][0];

        $this->assertHookInvocationsCount([
                'processDatamap_beforeStart',
                'processDatamap_afterAllOperations'
        ], 1);

        $this->assertHookInvocationsPayload([
            'processDatamap_preProcessFieldArray',
            'processDatamap_postProcessFieldArray',
            'processDatamap_afterDatabaseOperations',
        ], [
            [
                'table' => self::TABLE_Content,
                'fieldArray' => [ 'header' => 'Testing #1', 'pid' => self::VALUE_PageId ]
            ]
        ]);
    }

    /**
     * @test
     */
    public function hooksAreExecutedForExistingRecords()
    {
        $this->actionService->modifyRecord(
            self::TABLE_Content,
            self::VALUE_ContentId,
            ['header' => 'Testing #1']
        );

        $this->assertHookInvocationsCount([
            'processDatamap_beforeStart',
            'processDatamap_afterAllOperations'
        ], 1);

        $this->assertHookInvocationsPayload([
            'processDatamap_preProcessFieldArray',
            'processDatamap_postProcessFieldArray',
            'processDatamap_afterDatabaseOperations',
        ], [
            [
                'table' => self::TABLE_Content,
                'fieldArray' => [ 'header' => 'Testing #1' ]
            ]
        ]);
    }

    /**
     * @test
     */
    public function hooksAreExecutedForNewRelations()
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
            'processDatamap_afterAllOperations'
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
    public function hooksAreExecutedForExistingRelations()
    {
        $this->actionService->modifyRecord(
            self::TABLE_Content,
            self::VALUE_ContentId,
            [
                'header' => 'Testing #1',
                self::FIELD_ContentHotel => '3,4,5',
                self::FIELD_Categories => '28,29,30',
            ]
        );

        $this->assertHookInvocationsCount([
            'processDatamap_beforeStart',
            'processDatamap_afterAllOperations'
        ], 1);

        $this->assertHookInvocationPayload(
            'processDatamap_preProcessFieldArray',
            [
                [
                    'table' => self::TABLE_Content,
                    'fieldArray' => [
                        'header' => 'Testing #1',
                        self::FIELD_ContentHotel => '3,4,5',
                        self::FIELD_Categories => '28,29,30',
                    ]
                ]
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
                    self::FIELD_ContentHotel => 3,
                    self::FIELD_Categories => 3,
                ]
            ]
        ]);
    }

    /**
     * @param string[] $methodNames
     * @param int $count
     */
    protected function assertHookInvocationsCount(array $methodNames, int $count)
    {
        $message = 'Unexpected invocations of method "%s"';
        foreach ($methodNames as $methodName) {
            $invocations = $this->hookFixture->findInvocationsByMethodName($methodName);
            $this->assertCount(
                $count,
                $invocations,
                sprintf($message, $methodName)
            );
        }
    }

    /**
     * @param string[] $methodNames
     * @param array $assertions
     */
    protected function assertHookInvocationsPayload(array $methodNames, array $assertions)
    {
        foreach ($methodNames as $methodName) {
            $this->assertHookInvocationPayload($methodName, $assertions);
        }
    }

    /**
     * @param string $methodName
     * @param array $assertions
     */
    protected function assertHookInvocationPayload(string $methodName, array $assertions)
    {
        $message = 'Unexpected hook payload amount found for method "%s"';
        $invocations = $this->hookFixture->findInvocationsByMethodName($methodName);
        $this->assertNotNull($invocations);

        foreach ($assertions as $assertion) {
            $indexes = $this->findAllArrayValuesInHaystack($invocations, $assertion);
            $this->assertCount(
                1,
                $indexes,
                sprintf($message, $methodName)
            );
            $index = $indexes[0];
            unset($invocations[$index]);
        }
    }

    /**
     * @param array $haystack
     * @param array $assertion
     * @return int[]
     */
    protected function findAllArrayValuesInHaystack(array $haystack, array $assertion)
    {
        $found = [];
        foreach ($haystack as $index => $item) {
            if ($this->equals($assertion, $item)) {
                $found[] = $index;
            }
        }
        return $found;
    }

    /**
     * @param array $left
     * @param array $right
     * @return bool
     */
    protected function equals(array $left, array $right)
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
