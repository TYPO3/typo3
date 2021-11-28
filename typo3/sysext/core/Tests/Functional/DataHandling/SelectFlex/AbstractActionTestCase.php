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

namespace TYPO3\CMS\Core\Tests\Functional\DataHandling\SelectFlex;

use TYPO3\CMS\Core\Tests\Functional\DataHandling\AbstractDataHandlerActionTestCase;

abstract class AbstractActionTestCase extends AbstractDataHandlerActionTestCase
{
    protected const VALUE_PageId = 89;
    protected const VALUE_LanguageId = 1;
    protected const VALUE_ElementIdFirst = 1;
    protected const VALUE_ElementIdSecond = 2;
    protected const VALUE_ElementIdThird = 3;
    protected const VALUE_ForeignIdFirst = 1;
    protected const VALUE_ForeignIdSecond = 2;
    protected const VALUE_ForeignIdThird = 3;

    protected const TABLE_Element = 'tx_testselectflexmm_local';
    protected const FIELD_Flex = 'flex_1';

    protected const SCENARIO_DataSet = __DIR__ . '/DataSet/ImportDefault.csv';

    protected array $testExtensionsToLoad = [
        'typo3/sysext/core/Tests/Functional/Fixtures/Extensions/test_select_flex_mm',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(static::SCENARIO_DataSet);
    }

    public function addElementRelation()
    {
        $this->actionService->modifyRecord(
            self::TABLE_Element,
            self::VALUE_ElementIdSecond,
            [
                'flex_1' => [
                    'data' => [
                        'sMultiplesidebyside' => [
                            'lDEF' => [
                                'select_multiplesidebyside_1' => [
                                    'vDEF' => implode(',', [self::VALUE_ForeignIdSecond, self::VALUE_ElementIdFirst]),
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        );
    }
}
