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

namespace TYPO3\CMS\Backend\Tests\Unit\Form\FormDataProvider;

use TYPO3\CMS\Backend\Form\FormDataProvider\TcaText;
use TYPO3\CMS\Core\Configuration\Richtext;
use TYPO3\CMS\Core\Html\RteHtmlParser;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class TcaTextTest extends UnitTestCase
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
                            'richtextConfigurationName' => '',
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
        $rteHtmlParserProphecy = $this->prophesize(RteHtmlParser::class);
        GeneralUtility::addInstance(RteHtmlParser::class, $rteHtmlParserProphecy->reveal());

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
            ->willReturn([ 'aConfig' => 'option']);
        $rteHtmlParserProphecy
            ->transformTextForRichTextEditor(
                'notProcessedContent',
                []
            )
            ->willReturn('processedContent');

        self::assertSame($expected, (new TcaText())->addData($input));
    }
}
