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

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Form\FormDataProvider\TcaText;
use TYPO3\CMS\Core\Configuration\Richtext;
use TYPO3\CMS\Core\Html\RteHtmlParser;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class TcaTextTest extends UnitTestCase
{
    #[Test]
    public function addDataSetsRichtextConfigurationAndTransformsContent(): void
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

        $richtextConfigurationMock = $this->createMock(Richtext::class);
        $rteHtmlParserMock = $this->createMock(RteHtmlParser::class);

        $richtextConfigurationMock
            ->method('getConfiguration')
            ->with(
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
        $rteHtmlParserMock
            ->method('transformTextForRichTextEditor')
            ->with(
                'notProcessedContent',
                []
            )
            ->willReturn('processedContent');

        self::assertSame($expected, (new TcaText($richtextConfigurationMock, $rteHtmlParserMock))->addData($input));
    }

    #[Test]
    public function addDataDoesNotTransformsContentWhenRichtextIsNotSet(): void
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
                        ],
                    ],
                ],
            ],
        ];

        // No processing should be performed
        $expected = $input;
        self::assertSame($expected, (new TcaText($this->createMock(Richtext::class), $this->createMock(RteHtmlParser::class)))->addData($input));
    }

    #[Test]
    public function addDataDoesNotTransformsContentWhenRichtextIsDisabledInConfiguration(): void
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

        $richtextConfigurationMock = $this->createMock(Richtext::class);
        $richtextConfigurationMock
            ->method('getConfiguration')
            ->with(
                'aTable',
                'aField',
                42,
                23,
                [
                    'type' => 'text',
                    'enableRichtext' => true,
                ]
            )
            ->willReturn(['disabled' => '1']);

        // No processing should be performed
        $expected = $input;
        self::assertSame($expected, (new TcaText($richtextConfigurationMock, $this->createMock(RteHtmlParser::class)))->addData($input));
    }
}
