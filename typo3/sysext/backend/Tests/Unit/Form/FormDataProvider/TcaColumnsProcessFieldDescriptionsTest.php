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

use TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsProcessFieldDescriptions;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class TcaColumnsProcessFieldDescriptionsTest extends UnitTestCase
{
    protected TcaColumnsProcessFieldDescriptions $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new TcaColumnsProcessFieldDescriptions();
    }

    /**
     * @test
     */
    public function addDataKeepsDescriptionAsIsIfNoOverrideIsGiven(): void
    {
        $input = [
            'tableName' => 'aTable',
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'description' => 'foo',
                    ],
                ],
            ],
        ];
        $languageServiceProphecy = $this->prophesize(LanguageService::class);
        $languageServiceProphecy->sL('foo')->shouldBeCalled()->willReturnArgument(0);
        $GLOBALS['LANG'] = $languageServiceProphecy->reveal();

        $expected = $input;
        self::assertSame($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataAddsDescriptionToExistingField(): void
    {
        $input = [
            'tableName' => 'aTable',
            'processedTca' => [
                'columns' => [
                    'aField' => []
                ],
            ],
            'pageTsConfig' => [
                'TCEFORM.' => [
                    'aTable.' => [
                        'aField.' => [
                            'description' => 'aNewDescription',
                        ],
                    ],
                ],
            ],
        ];
        $languageServiceProphecy = $this->prophesize(LanguageService::class);
        $languageServiceProphecy->sL('aNewDescription')->shouldBeCalled()->willReturnArgument(0);
        $GLOBALS['LANG'] = $languageServiceProphecy->reveal();

        $expected = $input;
        $expected['processedTca']['columns']['aField']['description'] = 'aNewDescription';
        self::assertSame($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataSetsDescriptionFromPageTsConfig(): void
    {
        $input = [
            'tableName' => 'aTable',
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'description' => 'origDescription',
                    ],
                ],
            ],
            'pageTsConfig' => [
                'TCEFORM.' => [
                    'aTable.' => [
                        'aField.' => [
                            'description' => 'aDescriptionOverride',
                        ],
                    ],
                ],
            ],
        ];
        $languageServiceProphecy = $this->prophesize(LanguageService::class);
        $languageServiceProphecy->sL('aDescriptionOverride')->shouldBeCalled()->willReturnArgument(0);
        $GLOBALS['LANG'] = $languageServiceProphecy->reveal();

        $expected = $input;
        $expected['processedTca']['columns']['aField']['description'] = 'aDescriptionOverride';
        self::assertSame($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataSetsDescriptionFromPageTsConfigForSpecificLanguage(): void
    {
        $input = [
            'tableName' => 'aTable',
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'description' => 'origDescription',
                    ],
                ],
            ],
            'pageTsConfig' => [
                'TCEFORM.' => [
                    'aTable.' => [
                        'aField.' => [
                            'description.' => [
                                'fr' => 'aDescriptionOverride',
                            ],
                        ],
                    ],
                ],
            ],
        ];
        $languageServiceProphecy = $this->prophesize(LanguageService::class);
        $languageServiceProphecy->lang = 'fr';
        $languageServiceProphecy->sL('aDescriptionOverride')->shouldBeCalled()->willReturnArgument(0);
        $GLOBALS['LANG'] = $languageServiceProphecy->reveal();

        $expected = $input;
        $expected['processedTca']['columns']['aField']['description'] = 'aDescriptionOverride';
        self::assertSame($expected, $this->subject->addData($input));
    }
}
