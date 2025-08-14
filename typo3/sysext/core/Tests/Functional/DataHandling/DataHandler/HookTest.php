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

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Tests\Functional\DataHandling\DataHandler\Fixtures\HookFixture;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\TestingFramework\Core\Functional\Framework\DataHandling\ActionService;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Tests triggering hook execution in DataHandler.
 */
final class HookTest extends FunctionalTestCase
{
    private const VALUE_PageId = 89;
    private const VALUE_ContentId = 297;
    private const TABLE_Content = 'tt_content';
    private const TABLE_Hotel = 'tx_testirreforeignfield_hotel';
    private const TABLE_Category = 'sys_category';
    private const FIELD_ContentHotel = 'tx_testirreforeignfield_hotels';
    private const FIELD_Categories = 'categories';
    protected array $testExtensionsToLoad = [
        'typo3/sysext/core/Tests/Functional/Fixtures/Extensions/test_irre_foreignfield',
    ];

    protected array $configurationToUseInTestInstance = [
        'SC_OPTIONS' => [
            't3lib/class.t3lib_tcemain.php' => [
                'processDatamapClass' => [
                    __CLASS__ => HookFixture::class,
                ],
                'processCmdmapClass' => [
                    __CLASS__ => HookFixture::class,
                ],
            ],
        ],
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/DataSet/LiveDefaultPages.csv');
        $this->importCSVDataSet(__DIR__ . '/DataSet/LiveDefaultElements.csv');
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/be_users_admin.csv');
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);
    }

    /**
     * @param string[] $methodNames
     */
    private function assertHookInvocationsCount(HookFixture $hookFixture, array $methodNames, int $count): void
    {
        $message = 'Unexpected invocations of method "%s"';
        foreach ($methodNames as $methodName) {
            $invocations = $hookFixture->findInvocationsByMethodName($methodName);
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
    private function assertHookInvocationsPayload(HookFixture $hookFixture, array $methodNames, array $assertions): void
    {
        foreach ($methodNames as $methodName) {
            $this->assertHookInvocationPayload($hookFixture, $methodName, $assertions);
        }
    }

    private function assertHookInvocationPayload(HookFixture $hookFixture, string $methodName, array $assertions): void
    {
        $invocations = $hookFixture->findInvocationsByMethodName($methodName);
        self::assertNotNull($invocations);
        foreach ($assertions as $assertion) {
            $indexes = [];
            foreach ($invocations as $index => $item) {
                if ($this->equals($assertion, $item)) {
                    $indexes[] = $index;
                }
            }
            self::assertCount(
                1,
                $indexes,
                sprintf('Unexpected hook payload amount found for method "%s"', $methodName)
            );
            $index = $indexes[0];
            unset($invocations[$index]);
        }
    }

    private function equals(array $left, array $right): bool
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

    #[Test]
    public function hooksAreExecutedForNewRecords(): void
    {
        $hookFixture = GeneralUtility::makeInstance(HookFixture::class);
        $actionService = new ActionService();
        $actionService->createNewRecord(
            self::TABLE_Content,
            self::VALUE_PageId,
            ['header' => 'Testing #1']
        );

        $this->assertHookInvocationsCount($hookFixture, [
            'processDatamap_beforeStart',
            'processDatamap_afterAllOperations',
        ], 1);

        $this->assertHookInvocationsPayload($hookFixture, [
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

    #[Test]
    public function hooksAreExecutedForExistingRecords(): void
    {
        $hookFixture = GeneralUtility::makeInstance(HookFixture::class);
        $actionService = new ActionService();
        $actionService->modifyRecord(
            self::TABLE_Content,
            self::VALUE_ContentId,
            ['header' => 'Testing #1']
        );

        $this->assertHookInvocationsCount($hookFixture, [
            'processDatamap_beforeStart',
            'processDatamap_afterAllOperations',
        ], 1);

        $this->assertHookInvocationsPayload($hookFixture, [
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

    #[Test]
    public function hooksAreExecutedForNewRelations(): void
    {
        $contentNewId = StringUtility::getUniqueId('NEW');
        $hotelNewId = StringUtility::getUniqueId('NEW');
        $categoryNewId = StringUtility::getUniqueId('NEW');

        $hookFixture = GeneralUtility::makeInstance(HookFixture::class);
        $actionService = new ActionService();
        $actionService->modifyRecords(
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

        $this->assertHookInvocationsCount($hookFixture, [
            'processDatamap_beforeStart',
            'processDatamap_afterAllOperations',
        ], 1);

        $this->assertHookInvocationPayload(
            $hookFixture,
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
            $hookFixture,
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
            $hookFixture,
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

    #[Test]
    public function hooksAreExecutedForExistingRelations(): void
    {
        $hookFixture = GeneralUtility::makeInstance(HookFixture::class);
        $actionService = new ActionService();
        $actionService->modifyRecord(
            self::TABLE_Content,
            self::VALUE_ContentId,
            [
                'header' => 'Testing #1',
                self::FIELD_ContentHotel => '3,4',
                self::FIELD_Categories => '28,29,30',
            ]
        );

        $this->assertHookInvocationsCount($hookFixture, [
            'processDatamap_beforeStart',
            'processDatamap_afterAllOperations',
        ], 1);

        $this->assertHookInvocationPayload(
            $hookFixture,
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

        $this->assertHookInvocationsPayload($hookFixture, [
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
}
