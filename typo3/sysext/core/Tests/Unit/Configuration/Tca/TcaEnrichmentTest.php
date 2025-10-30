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

namespace TYPO3\CMS\Core\Tests\Unit\Configuration\Tca;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Configuration\Tca\TcaEnrichment;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class TcaEnrichmentTest extends UnitTestCase
{
    #[Test]
    public function disabledFieldIsAddedToColumns(): void
    {
        $tca = [
            'aTable' => [
                'ctrl' => [
                    'enablecolumns' => [
                        'disabled' => 'myDisableField',
                    ],
                ],
            ],
        ];
        $expected = $tca;
        $expected['aTable']['columns']['myDisableField'] = [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.enabled',
            'exclude' => true,
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
                'default' => 0,
                'items' => [
                    [
                        'label' => '',
                        'invertStateDisplay' => true,
                    ],
                ],
            ],
        ];
        self::assertSame($expected, (new TcaEnrichment())->enrich($tca));
    }

    #[Test]
    public function disabledFieldIsNotAddedToColumnsIfExists(): void
    {
        $tca = [
            'aTable' => [
                'ctrl' => [
                    'enablecolumns' => [
                        'disabled' => 'myDisableField',
                    ],
                ],
                'columns' => [
                    'myDisableField' => [
                        'foo' => 'iAmBrokenButStillKept',
                    ],
                ],
            ],
        ];
        $expected = $tca;
        self::assertSame($expected, (new TcaEnrichment())->enrich($tca));
    }

    #[Test]
    public function starttimeFieldIsAddedToColumns(): void
    {
        $tca = [
            'aTable' => [
                'ctrl' => [
                    'enablecolumns' => [
                        'starttime' => 'myStarttimeField',
                    ],
                ],
            ],
        ];
        $expected = $tca;
        $expected['aTable']['columns']['myStarttimeField'] = [
            'exclude' => true,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.starttime',
            'config' => [
                'type' => 'datetime',
                'default' => 0,
            ],
        ];
        self::assertSame($expected, (new TcaEnrichment())->enrich($tca));
    }

    #[Test]
    public function starttimeFieldIsNotAddedToColumnsIfExists(): void
    {
        $tca = [
            'aTable' => [
                'ctrl' => [
                    'enablecolumns' => [
                        'starttime' => 'myStarttimeField',
                    ],
                ],
                'columns' => [
                    'myStarttimeField' => [
                        'foo' => 'iAmBrokenButStillKept',
                    ],
                ],
            ],
        ];
        $expected = $tca;
        self::assertSame($expected, (new TcaEnrichment())->enrich($tca));
    }

    #[Test]
    public function endtimeFieldIsAddedToColumns(): void
    {
        $tca = [
            'aTable' => [
                'ctrl' => [
                    'enablecolumns' => [
                        'endtime' => 'myEndtimeField',
                    ],
                ],
            ],
        ];
        $expected = $tca;
        $expected['aTable']['columns']['myEndtimeField'] = [
            'exclude' => true,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.endtime',
            'config' => [
                'type' => 'datetime',
                'default' => 0,
                'range' => [
                    'upper' => mktime(0, 0, 0, 1, 1, 2106),
                ],
            ],
        ];
        self::assertSame($expected, (new TcaEnrichment())->enrich($tca));
    }

    #[Test]
    public function endtimeFieldIsNotAddedToColumnsIfExists(): void
    {
        $tca = [
            'aTable' => [
                'ctrl' => [
                    'enablecolumns' => [
                        'endtime' => 'myEndtimeField',
                    ],
                ],
                'columns' => [
                    'myEndtimeField' => [
                        'foo' => 'iAmBrokenButStillKept',
                    ],
                ],
            ],
        ];
        $expected = $tca;
        self::assertSame($expected, (new TcaEnrichment())->enrich($tca));
    }

    #[Test]
    public function feGroupFieldIsAddedToColumns(): void
    {
        $tca = [
            'aTable' => [
                'ctrl' => [
                    'enablecolumns' => [
                        'fe_group' => 'myFeGroupField',
                    ],
                ],
            ],
        ];
        $expected = $tca;
        $expected['aTable']['columns']['myFeGroupField'] = [
            'exclude' => true,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.fe_group',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'size' => 5,
                'maxitems' => 20,
                'items' => [
                    [
                        'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.hide_at_login',
                        'value' => -1,
                    ],
                    [
                        'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.any_login',
                        'value' => -2,
                    ],
                    [
                        'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.usergroups',
                        'value' => '--div--',
                    ],
                ],
                'exclusiveKeys' => '-1,-2',
                'foreign_table' => 'fe_groups',
            ],
        ];
        self::assertSame($expected, (new TcaEnrichment())->enrich($tca));
    }

    #[Test]
    public function feGroupFieldIsNotAddedToColumnsIfExists(): void
    {
        $tca = [
            'aTable' => [
                'ctrl' => [
                    'enablecolumns' => [
                        'fe_group' => 'myFeGroupField',
                    ],
                ],
                'columns' => [
                    'myFeGroupField' => [
                        'foo' => 'iAmBrokenButStillKept',
                    ],
                ],
            ],
        ];
        $expected = $tca;
        self::assertSame($expected, (new TcaEnrichment())->enrich($tca));
    }

    #[Test]
    public function editLockFieldIsAddedToColumns(): void
    {
        $tca = [
            'aTable' => [
                'ctrl' => [
                    'editlock' => 'myEditLockField',
                ],
            ],
        ];
        $expected = $tca;
        $expected['aTable']['columns']['myEditLockField'] = [
            'displayCond' => 'HIDE_FOR_NON_ADMINS',
            'label' => 'core.db.general:editlock',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
            ],
        ];
        self::assertSame($expected, (new TcaEnrichment())->enrich($tca));
    }

    #[Test]
    public function editLockFieldIsNotAddedToColumnsIfExists(): void
    {
        $tca = [
            'aTable' => [
                'ctrl' => [
                    'editlock' => 'myEditLockField',
                ],
                'columns' => [
                    'myEditLockField' => [
                        'foo' => 'iAmBrokenButStillKept',
                    ],
                ],
            ],
        ];
        $expected = $tca;
        self::assertSame($expected, (new TcaEnrichment())->enrich($tca));
    }

    #[Test]
    public function descriptionFieldIsAddedToColumns(): void
    {
        $tca = [
            'aTable' => [
                'ctrl' => [
                    'descriptionColumn' => 'myDescriptionField',
                ],
            ],
        ];
        $expected = $tca;
        $expected['aTable']['columns']['myDescriptionField'] = [
            'exclude' => true,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.description',
            'config' => [
                'type' => 'text',
                'rows' => 5,
                'cols' => 30,
                'max' => 2000,
            ],
        ];
        self::assertSame($expected, (new TcaEnrichment())->enrich($tca));
    }

    #[Test]
    public function descriptionFieldIsNotAddedToColumnsIfExists(): void
    {
        $tca = [
            'aTable' => [
                'ctrl' => [
                    'descriptionColumn' => 'myDescriptionField',
                ],
                'columns' => [
                    'myDescriptionField' => [
                        'foo' => 'iAmBrokenButStillKept',
                    ],
                ],
            ],
        ];
        $expected = $tca;
        self::assertSame($expected, (new TcaEnrichment())->enrich($tca));
    }

    #[Test]
    public function languageFieldIsAddedToColumns(): void
    {
        $tca = [
            'aTable' => [
                'ctrl' => [
                    'languageField' => 'myLanguageField',
                ],
            ],
        ];
        $expected = [
            'exclude' => true,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.language',
            'config' => [
                'type' => 'language',
            ],
        ];
        self::assertSame($expected, (new TcaEnrichment())->enrich($tca)['aTable']['columns']['myLanguageField']);
    }

    #[Test]
    public function languageFieldIsNotAddedToColumnsIfExists(): void
    {
        $tca = [
            'aTable' => [
                'ctrl' => [
                    'languageField' => 'myLanguageField',
                ],
                'columns' => [
                    'myLanguageField' => [
                        'foo' => 'iAmBrokenButStillKept',
                    ],
                ],
            ],
        ];
        $expected = [
            'foo' => 'iAmBrokenButStillKept',
        ];
        self::assertSame($expected, (new TcaEnrichment())->enrich($tca)['aTable']['columns']['myLanguageField']);
    }

    #[Test]
    public function transOrigPointerFieldIsSetWhenLanguageFieldIsGiven(): void
    {
        $tca = [
            'aTable' => [
                'ctrl' => [
                    'languageField' => 'myLanguageField',
                ],
            ],
        ];
        self::assertSame('l10n_parent', (new TcaEnrichment())->enrich($tca)['aTable']['ctrl']['transOrigPointerField']);
    }

    #[Test]
    public function transOrigPointerFieldIsNotSetWhenConfigured(): void
    {
        $tca = [
            'aTable' => [
                'ctrl' => [
                    'languageField' => 'myLanguageField',
                    'transOrigPointerField' => 'myTransOrigPointerField',
                ],
            ],
        ];
        self::assertSame('myTransOrigPointerField', (new TcaEnrichment())->enrich($tca)['aTable']['ctrl']['transOrigPointerField']);
    }

    #[Test]
    public function transOrigPointerFieldIsAddedToColumns(): void
    {
        $tca = [
            'aTable' => [
                'ctrl' => [
                    'languageField' => 'myLanguageField',
                    'transOrigPointerField' => 'myTransOrigPointerField',
                ],
            ],
        ];
        $expected = [
            'displayCond' => 'FIELD:myLanguageField:>:0',
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.l18n_parent',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [
                        'label' => '',
                        'value' => 0,
                    ],
                ],
                'foreign_table' => 'aTable',
                'foreign_table_where' => 'AND {#aTable}.{#pid}=###CURRENT_PID### AND {#aTable}.{#myLanguageField} IN (-1,0)',
                'default' => 0,
            ],
        ];
        self::assertSame($expected, (new TcaEnrichment())->enrich($tca)['aTable']['columns']['myTransOrigPointerField']);
    }

    #[Test]
    public function transOrigPointerFieldIsNotAddedToColumnsIfExists(): void
    {
        $tca = [
            'aTable' => [
                'ctrl' => [
                    'languageField' => 'myLanguageField',
                    'transOrigPointerField' => 'myTransOrigPointerField',
                ],
                'columns' => [
                    'myTransOrigPointerField' => [
                        'foo' => 'iAmBrokenButStillKept',
                    ],
                ],
            ],
        ];
        $expected = [
            'foo' => 'iAmBrokenButStillKept',
        ];
        self::assertSame($expected, (new TcaEnrichment())->enrich($tca)['aTable']['columns']['myTransOrigPointerField']);
    }

    #[Test]
    public function transOrigDiffSourceFieldIsAddedToColumns(): void
    {
        $tca = [
            'aTable' => [
                'ctrl' => [
                    'transOrigDiffSourceField' => 'myTransOrigDiffSourceField',
                ],
            ],
        ];
        $expected = $tca;
        $expected['aTable']['columns']['myTransOrigDiffSourceField'] = [
            'config' => [
                'type' => 'passthrough',
                'default' => '',
            ],
        ];
        self::assertSame($expected, (new TcaEnrichment())->enrich($tca));
    }

    #[Test]
    public function transOrigDiffSourceFieldIsNotAddedToColumnsIfExists(): void
    {
        $tca = [
            'aTable' => [
                'ctrl' => [
                    'transOrigDiffSourceField' => 'myTransOrigDiffSourceField',
                ],
                'columns' => [
                    'myTransOrigDiffSourceField' => [
                        'foo' => 'iAmBrokenButStillKept',
                    ],
                ],
            ],
        ];
        $expected = $tca;
        self::assertSame($expected, (new TcaEnrichment())->enrich($tca));
    }

    #[Test]
    public function translationSourceFieldIsAddedToColumns(): void
    {
        $tca = [
            'aTable' => [
                'ctrl' => [
                    'translationSource' => 'myTranslationSourceField',
                ],
            ],
        ];
        $expected = $tca;
        $expected['aTable']['columns']['myTranslationSourceField'] = [
            'config' => [
                'type' => 'passthrough',
            ],
        ];
        self::assertSame($expected, (new TcaEnrichment())->enrich($tca));
    }

    #[Test]
    public function translationSourceFieldIsNotAddedToColumnsIfExists(): void
    {
        $tca = [
            'aTable' => [
                'ctrl' => [
                    'translationSource' => 'myTranslationSourceField',
                ],
                'columns' => [
                    'myTranslationSourceField' => [
                        'foo' => 'iAmBrokenButStillKept',
                    ],
                ],
            ],
        ];
        $expected = $tca;
        self::assertSame($expected, (new TcaEnrichment())->enrich($tca));
    }
}
