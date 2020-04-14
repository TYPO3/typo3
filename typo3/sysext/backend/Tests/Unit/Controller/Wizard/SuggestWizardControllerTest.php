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

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Controller\Wizard\SuggestWizardController;
use TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class SuggestWizardControllerTest extends UnitTestCase
{
    /**
     * @test
     */
    public function getFlexFieldConfigurationThrowsExceptionIfSimpleFlexFieldIsNotFound(): void
    {
        $dataStructureIdentifier = '{"type":"tca","tableName":"tt_content","fieldName":"pi_flexform","dataStructureKey":"blog_example,list"}';
        $serverRequestProphecy = $this->prophesize(ServerRequestInterface::class);
        $serverRequestProphecy->getParsedBody()->willReturn([
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
                                'TCEforms' => [
                                    'config' => [
                                        'Sublevel field configuration',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
        $flexFormToolsProphecy = $this->prophesize(FlexFormTools::class);
        GeneralUtility::addInstance(FlexFormTools::class, $flexFormToolsProphecy->reveal());
        $flexFormToolsProphecy->parseDataStructureByIdentifier($dataStructureIdentifier)->willReturn($dataStructure);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1480609491);
        (new SuggestWizardController())->searchAction($serverRequestProphecy->reveal());
    }

    /**
     * @test
     */
    public function getFlexFieldConfigurationThrowsExceptionIfSectionContainerFlexFieldIsNotFound(): void
    {
        $dataStructureIdentifier = '{"type":"tca","tableName":"tt_content","fieldName":"pi_flexform","dataStructureKey":"blog_example,list"}';
        $serverRequestProphecy = $this->prophesize(ServerRequestInterface::class);
        $serverRequestProphecy->getParsedBody()->willReturn([
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
                                'TCEforms' => [
                                    'config' => [
                                        'Sublevel field configuration',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
        $flexFormToolsProphecy = $this->prophesize(FlexFormTools::class);
        GeneralUtility::addInstance(FlexFormTools::class, $flexFormToolsProphecy->reveal());
        $flexFormToolsProphecy->parseDataStructureByIdentifier($dataStructureIdentifier)->willReturn($dataStructure);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1480611208);
        (new SuggestWizardController())->searchAction($serverRequestProphecy->reveal());
    }

    /**
     * @test
     * @dataProvider isTableHiddenIsProperlyRetrievedDataProvider
     * @param bool $expected
     * @param array $array
     */
    public function isTableHiddenIsProperlyRetrieved(bool $expected, array $array): void
    {
        $subject = $this->getAccessibleMock(SuggestWizardController::class, ['dummy'], [], '', false);
        self::assertEquals($expected, $subject->_call('isTableHidden', $array));
    }

    /**
     * @return array
     */
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
}
