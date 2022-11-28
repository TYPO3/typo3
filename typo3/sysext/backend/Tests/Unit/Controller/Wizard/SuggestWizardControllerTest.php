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

namespace TYPO3\CMS\Backend\Tests\Unit\Controller\Wizard;

use TYPO3\CMS\Backend\Controller\Wizard\SuggestWizardController;
use TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class SuggestWizardControllerTest extends UnitTestCase
{
    /**
     * @test
     */
    public function getFlexFieldConfigurationThrowsExceptionIfSimpleFlexFieldIsNotFound(): void
    {
        $dataStructureIdentifier = '{"type":"tca","tableName":"tt_content","fieldName":"pi_flexform","dataStructureKey":"blog_example,list"}';
        $request = (new ServerRequest())->withParsedBody([
            'value' => 'theSearchValue',
            'table' => 'aTable',
            'field' => 'aField',
            'uid' => 'aUid',
            'pid' => 'aPid',
            'dataStructureIdentifier' => $dataStructureIdentifier,
            'flexFormSheetName' => 'sDb',
            'flexFormFieldName' => 'aField',
            'flexFormContainerName' => '',
            'flexFormContainerFieldName' => '',
        ]);

        $dataStructure = [
            'sheets' => [
                'sDb' => [
                    'ROOT' => [
                        'el' => [
                            'differentField' => [
                                'config' => [
                                    'Sublevel field configuration',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
        $flexFormToolsMock = $this->createMock(FlexFormTools::class);
        GeneralUtility::addInstance(FlexFormTools::class, $flexFormToolsMock);
        $flexFormToolsMock->method('parseDataStructureByIdentifier')->with($dataStructureIdentifier)->willReturn($dataStructure);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1480609491);
        (new SuggestWizardController())->searchAction($request);
    }

    /**
     * @test
     */
    public function getFlexFieldConfigurationThrowsExceptionIfSectionContainerFlexFieldIsNotFound(): void
    {
        $dataStructureIdentifier = '{"type":"tca","tableName":"tt_content","fieldName":"pi_flexform","dataStructureKey":"blog_example,list"}';
        $request = (new ServerRequest())->withParsedBody([
            'value' => 'theSearchValue',
            'table' => 'aTable',
            'field' => 'aField',
            'uid' => 'aUid',
            'pid' => 'aPid',
            'dataStructureIdentifier' => $dataStructureIdentifier,
            'flexFormSheetName' => 'sDb',
            'flexFormFieldName' => 'aField',
            'flexFormContainerName' => 'aContainer',
            'flexFormContainerFieldName' => 'aContainerFieldName',
        ]);

        $dataStructure = [
            'sheets' => [
                'sDb' => [
                    'ROOT' => [
                        'el' => [
                            'notTheFieldYouAreLookingFor' => [
                                'config' => [
                                    'Sublevel field configuration',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
        $flexFormToolsMock = $this->createMock(FlexFormTools::class);
        GeneralUtility::addInstance(FlexFormTools::class, $flexFormToolsMock);
        $flexFormToolsMock->method('parseDataStructureByIdentifier')->with($dataStructureIdentifier)->willReturn($dataStructure);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1480611208);
        (new SuggestWizardController())->searchAction($request);
    }

    /**
     * @test
     * @dataProvider isTableHiddenIsProperlyRetrievedDataProvider
     */
    public function isTableHiddenIsProperlyRetrieved(bool $expected, array $array): void
    {
        $subject = $this->getAccessibleMock(SuggestWizardController::class, null, [], '', false);
        self::assertEquals($expected, $subject->_call('isTableHidden', $array));
    }

    public function isTableHiddenIsProperlyRetrievedDataProvider(): array
    {
        return [
          'notSetValue' => [false, ['ctrl' => ['hideTable' => null]]],
          'true' => [true, ['ctrl' => ['hideTable' => true]]],
          'false' => [false, ['ctrl' => ['hideTable' => false]]],
          'string with true' => [true, ['ctrl' => ['hideTable' => '1']]],
          'string with false' => [false, ['ctrl' => ['hideTable' => '0']]],
        ];
    }

    /**
     * @test
     * @dataProvider whereClauseIsProperlyRetrievedDataProvider
     */
    public function whereClauseIsProperlyRetrieved(string $expected, array $array): void
    {
        $subject = $this->getAccessibleMock(SuggestWizardController::class, null, [], '', false);
        self::assertEquals($expected, $subject->_call('getWhereClause', $array));
    }

    public function whereClauseIsProperlyRetrievedDataProvider(): array
    {
        return [
            'no foreign_table' => [
                '',
                [],
            ],
            'no foreign_table_where' => [
                '',
                [
                    'foreign_table' => 'aTable',
                ],
            ],
            'empty where clause' => [
                '',
                [
                    'foreign_table' => 'aTable',
                    'foreign_table_where' => '',
                ],
            ],
            'where clause' => [
                'aTable.pid = 123',
                [
                    'foreign_table' => 'aTable',
                    'foreign_table_where' => ' aTable.pid = 123 ORDER BY aTable.uid',
                ],
            ],
        ];
    }
}
