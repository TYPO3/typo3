<?php
declare(strict_types=1);
namespace TYPO3\CMS\Backend\Tests\Unit\Form\FormDataProvider;

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

use TYPO3\CMS\Backend\Form\FormDataProvider\TcaText;
use TYPO3\CMS\Core\Configuration\Richtext;
use TYPO3\CMS\Core\Html\RteHtmlParser;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Test case
 */
class TcaTextTest extends \TYPO3\TestingFramework\Core\Unit\UnitTestCase
{
    /**
     * @test
     */
    public function addDataSetsRichtextConfigurationAndTransformsContent()
    {
        $input = [
            'tableName' => 'aTable',
            'effectivePid' => 42,
            'recordTypeValue' => 23,
            'databaseRow' => [
                'aField' => 'notProcessedContent',
            ],
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'text',
                            'enableRichtext' => true,
                        ],
                    ],
                ],
            ],
        ];
        $expected = [
            'tableName' => 'aTable',
            'effectivePid' => 42,
            'recordTypeValue' => 23,
            'databaseRow' => [
                'aField' => 'processedContent',
            ],
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'text',
                            'enableRichtext' => true,
                            'richtextConfiguration' => [
                                'aConfig' => 'option',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $richtextConfigurationProphecy = $this->prophesize(Richtext::class);
        GeneralUtility::addInstance(Richtext::class, $richtextConfigurationProphecy->reveal());
        $rteHtmlParserPropehy = $this->prophesize(RteHtmlParser::class);
        GeneralUtility::addInstance(RteHtmlParser::class, $rteHtmlParserPropehy->reveal());

        $richtextConfigurationProphecy
            ->getConfiguration(
                'aTable',
                'aField',
                42,
                23,
                [
                    'type' => 'text',
                    'enableRichtext' => true,
                ]
            )
            ->willReturn([ 'aConfig' => 'option' ]);
        $rteHtmlParserPropehy->init('aTable:aField', 42)->shouldBeCalled();
        $rteHtmlParserPropehy
            ->RTE_transform(
                'notProcessedContent',
                [],
                'rte',
                [ 'aConfig' => 'option']
            )
            ->willReturn('processedContent');

        $this->assertSame($expected, (new TcaText())->addData($input));
    }
}
