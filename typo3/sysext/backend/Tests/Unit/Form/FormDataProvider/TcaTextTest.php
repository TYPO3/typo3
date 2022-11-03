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
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Configuration\Richtext;
use TYPO3\CMS\Core\Html\RteHtmlParser;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class TcaTextTest extends UnitTestCase
{
    /**
     * @test
     */
    public function addDataSetsRichtextConfigurationAndTransformsContent(): void
    {
        $beUserMock = $this->createMock(BackendUserAuthentication::class);
        $beUserMock->method('isRTE')->willReturn(true);
        $GLOBALS['BE_USER'] = $beUserMock;

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
        GeneralUtility::addInstance(Richtext::class, $richtextConfigurationMock);
        $rteHtmlParserMock = $this->createMock(RteHtmlParser::class);
        GeneralUtility::addInstance(RteHtmlParser::class, $rteHtmlParserMock);

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

        self::assertSame($expected, (new TcaText())->addData($input));
    }

    /**
     * @test
     */
    public function addDataDoesNotTransformsContentWhenRichtextIsNotSet(): void
    {
        $beUserMock = $this->createMock(BackendUserAuthentication::class);
        $beUserMock->method('isRTE')->willReturn(true);
        $GLOBALS['BE_USER'] = $beUserMock;

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
        self::assertSame($expected, (new TcaText())->addData($input));
    }

    /**
     * @test
     */
    public function addDataDoesNotTransformsContentWhenRichtextIsDisabledInConfiguration(): void
    {
        $beUserMock = $this->createMock(BackendUserAuthentication::class);
        $beUserMock->method('isRTE')->willReturn(true);
        $GLOBALS['BE_USER'] = $beUserMock;

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
        GeneralUtility::addInstance(Richtext::class, $richtextConfigurationMock);

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
        self::assertSame($expected, (new TcaText())->addData($input));
    }

    /**
     * @test
     */
    public function addDataDoesNotTransformsContentWhenRichtextIsDisabledForUser(): void
    {
        $beUserMock = $this->createMock(BackendUserAuthentication::class);
        $beUserMock->method('isRTE')->willReturn(false);
        $GLOBALS['BE_USER'] = $beUserMock;

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

        // No processing should be performed
        $expected = $input;
        self::assertSame($expected, (new TcaText())->addData($input));
    }
}
