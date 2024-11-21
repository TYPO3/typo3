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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Controller\Wizard\SuggestWizardController;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Schema\Field\FieldCollection;
use TYPO3\CMS\Core\Schema\TcaSchema;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class SuggestWizardControllerTest extends UnitTestCase
{
    #[Test]
    public function getFlexFieldConfigurationThrowsExceptionIfSimpleFlexFieldIsNotFound(): void
    {
        $dataStructureIdentifier = '{"type":"tca","tableName":"tt_content","fieldName":"pi_flexform","dataStructureKey":"blog_example,list"}';
        $request = (new ServerRequest())->withParsedBody([
            'value' => 'theSearchValue',
            'tableName' => 'aTable',
            'fieldName' => 'aField',
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

        $schema = new TcaSchema('aTable', new FieldCollection(), []);
        $flexFormToolsMock = $this->createMock(FlexFormTools::class);
        $flexFormToolsMock->method('parseDataStructureByIdentifier')->with($dataStructureIdentifier, $schema)->willReturn($dataStructure);
        $tcaSchemaFactoryMock = $this->createMock(TcaSchemaFactory::class);
        $tcaSchemaFactoryMock->method('get')->with('aTable')->willReturn($schema);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1480609491);
        (new SuggestWizardController($flexFormToolsMock, $tcaSchemaFactoryMock))->searchAction($request);
    }

    #[Test]
    public function getFlexFieldConfigurationThrowsExceptionIfSectionContainerFlexFieldIsNotFound(): void
    {
        $dataStructureIdentifier = '{"type":"tca","tableName":"tt_content","fieldName":"pi_flexform","dataStructureKey":"blog_example,list"}';
        $request = (new ServerRequest())->withParsedBody([
            'value' => 'theSearchValue',
            'tableName' => 'aTable',
            'fieldName' => 'aField',
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

        $schema = new TcaSchema('aTable', new FieldCollection(), []);
        $flexFormToolsMock = $this->createMock(FlexFormTools::class);
        $flexFormToolsMock->method('parseDataStructureByIdentifier')->with($dataStructureIdentifier, $schema)->willReturn($dataStructure);
        $tcaSchemaFactoryMock = $this->createMock(TcaSchemaFactory::class);
        $tcaSchemaFactoryMock->method('get')->with('aTable')->willReturn($schema);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1480611208);
        (new SuggestWizardController($flexFormToolsMock, $tcaSchemaFactoryMock))->searchAction($request);
    }

    #[DataProvider('currentBackendUserMayAccessTableIsEvaluatedCorrectlyDataProvider')]
    #[Test]
    public function currentBackendUserMayAccessTableIsEvaluatedCorrectly(
        bool $expected,
        array $tableConfig,
        bool $isAdmin
    ): void {
        $backendUser = $this->createMock(BackendUserAuthentication::class);
        $backendUser->method('isAdmin')->willReturn($isAdmin);
        $schema = new TcaSchema('irrelevant', new FieldCollection(), $tableConfig);

        $subject = $this->getAccessibleMock(SuggestWizardController::class, ['getBackendUser'], [], '', false);
        $subject->method('getBackendUser')->willReturn($backendUser);

        self::assertEquals($expected, $subject->_call('currentBackendUserMayAccessTable', $schema));
    }

    public static function currentBackendUserMayAccessTableIsEvaluatedCorrectlyDataProvider(): array
    {
        return [
            'isAdmin' => [
                true,
                [],
                true,
            ],
            'adminOnly set' => [
                false,
                [
                    'adminOnly' => true,
                ],
                false,
            ],
            'adminOnly not set, rootlevel not set and security.ignoreRootLevelRestriction not set' => [
                true,
                [],
                false,
            ],
            'adminOnly not set, rootlevel is true and security.ignoreRootLevelRestriction not set' => [
                false,
                [
                    'rootLevel' => true,
                ],
                false,
            ],
            'adminOnly not set, rootlevel is false and security.ignoreRootLevelRestriction not set' => [
                true,
                [
                    'rootLevel' => false,
                ],
                false,
            ],
            'adminOnly not set, rootlevel is true and security.ignoreRootLevelRestriction is false' => [
                false,
                [
                    'rootLevel' => true,
                    'security' => [
                        'ignoreRootLevelRestriction' => false,
                    ],
                ],
                false,
            ],
            'adminOnly not set, rootlevel is true and security.ignoreRootLevelRestriction is true' => [
                true,
                [
                    'rootLevel' => true,
                    'security' => [
                        'ignoreRootLevelRestriction' => true,
                    ],
                ],
                false,
            ],
        ];
    }

    #[DataProvider('whereClauseIsProperlyRetrievedDataProvider')]
    #[Test]
    public function whereClauseIsProperlyRetrieved(string $expected, array $array): void
    {
        $subject = $this->getAccessibleMock(SuggestWizardController::class, null, [], '', false);
        self::assertEquals($expected, $subject->_call('getWhereClause', $array));
    }

    public static function whereClauseIsProperlyRetrievedDataProvider(): array
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
