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

namespace TYPO3\CMS\Core\Configuration\Tca;

/**
 * Automatically "enrich" TCA. This mainly adds "columns" definitions
 * based on "ctrl" settings. This is *not* for migration or preparation.
 *
 * This class is executed when building final TCA *after* "base"
 * files in "Configuration/TCA" have been loaded, and *before*
 * files in "Configuration/TCA/Overrides" are loaded.
 *
 * @internal Class and API may change any time.
 */
final readonly class TcaEnrichment
{
    public function enrich(array $tca): array
    {
        $tca = $this->enrichDisabledField($tca);
        $tca = $this->enrichStarttimeField($tca);
        $tca = $this->enrichEndtimeField($tca);
        $tca = $this->enrichFeGroupField($tca);
        $tca = $this->enrichEditLockField($tca);
        $tca = $this->enrichDescriptionField($tca);
        $tca = $this->enrichLanguageField($tca);
        $tca = $this->setTransOrigPointerFieldInCtrl($tca);
        $tca = $this->enrichTransOrigPointerField($tca);
        $tca = $this->enrichTransOrigDiffSourceField($tca);
        $tca = $this->enrichTranslationSourceField($tca);
        return $tca;
    }

    private function enrichDisabledField(array $tca): array
    {
        foreach ($tca as $table => $tableDefinition) {
            $disabledFieldName = $tableDefinition['ctrl']['enablecolumns']['disabled'] ?? null;
            if ($disabledFieldName && !is_array($tableDefinition['columns'][$disabledFieldName] ?? null)) {
                $tca[$table]['columns'][$disabledFieldName] = [
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
            }
        }
        return $tca;
    }

    private function enrichStarttimeField(array $tca): array
    {
        foreach ($tca as $table => $tableDefinition) {
            $starttimeFieldName = $tableDefinition['ctrl']['enablecolumns']['starttime'] ?? null;
            if ($starttimeFieldName && !is_array($tableDefinition['columns'][$starttimeFieldName] ?? null)) {
                $tca[$table]['columns'][$starttimeFieldName] = [
                    'exclude' => true,
                    'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.starttime',
                    'config' => [
                        'type' => 'datetime',
                        'default' => 0,
                    ],
                ];
            }
        }
        return $tca;
    }

    private function enrichEndtimeField(array $tca): array
    {
        foreach ($tca as $table => $tableDefinition) {
            $endtimeFieldName = $tableDefinition['ctrl']['enablecolumns']['endtime'] ?? null;
            if ($endtimeFieldName && !is_array($tableDefinition['columns'][$endtimeFieldName] ?? null)) {
                $tca[$table]['columns'][$endtimeFieldName] = [
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
            }
        }
        return $tca;
    }

    private function enrichFeGroupField(array $tca): array
    {
        foreach ($tca as $table => $tableDefinition) {
            $feGroupFieldName = $tableDefinition['ctrl']['enablecolumns']['fe_group'] ?? null;
            if ($feGroupFieldName && !is_array($tableDefinition['columns'][$feGroupFieldName] ?? null)) {
                $tca[$table]['columns'][$feGroupFieldName] = [
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
            }
        }
        return $tca;
    }

    private function enrichEditLockField(array $tca): array
    {
        foreach ($tca as $table => $tableDefinition) {
            $editLockFieldName = $tableDefinition['ctrl']['editlock'] ?? null;
            if ($editLockFieldName && !is_array($tableDefinition['columns'][$editLockFieldName] ?? null)) {
                $tca[$table]['columns'][$editLockFieldName] = [
                    'displayCond' => 'HIDE_FOR_NON_ADMINS',
                    'label' => 'core.db.general:editlock',
                    'config' => [
                        'type' => 'check',
                        'renderType' => 'checkboxToggle',
                    ],
                ];
            }
        }
        return $tca;
    }

    private function enrichDescriptionField(array $tca): array
    {
        foreach ($tca as $table => $tableDefinition) {
            $descriptionFieldName = $tableDefinition['ctrl']['descriptionColumn'] ?? null;
            if ($descriptionFieldName && !is_array($tableDefinition['columns'][$descriptionFieldName] ?? null)) {
                $tca[$table]['columns'][$descriptionFieldName] = [
                    'exclude' => true,
                    'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.description',
                    'config' => [
                        'type' => 'text',
                        'rows' => 5,
                        'cols' => 30,
                        'max' => 2000,
                    ],
                ];
            }
        }
        return $tca;
    }

    private function enrichLanguageField(array $tca): array
    {
        foreach ($tca as $table => $tableDefinition) {
            $languageFieldName = $tableDefinition['ctrl']['languageField'] ?? null;
            if ($languageFieldName && !is_array($tableDefinition['columns'][$languageFieldName] ?? null)) {
                $tca[$table]['columns'][$languageFieldName] = [
                    'exclude' => true,
                    'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.language',
                    'config' => [
                        'type' => 'language',
                    ],
                ];
            }
        }
        return $tca;
    }

    /**
     * When 'languageField' is set, 'transOrigPointerField' must be set as well.
     * We silently add 'transOrigPointerField' if that iss not the case.
     *
     * @todo: This obviously needs a consolidation in ctrl. We should have a single, probably
     *        boolean ctrl toggle to make a table 'localization' aware, with core then handling
     *        all internals. This will require streamlining the field names along the way, and
     *        we can think about this as soon as sys_language_uid=-1 is gone.
     */
    private function setTransOrigPointerFieldInCtrl(array $tca): array
    {
        foreach ($tca as $table => $tableDefinition) {
            if (isset($tableDefinition['ctrl']['languageField']) && !isset($tableDefinition['ctrl']['transOrigPointerField'])) {
                $tca[$table]['ctrl']['transOrigPointerField'] = 'l10n_parent';
            }
        }
        return $tca;
    }

    private function enrichTransOrigPointerField(array $tca): array
    {
        foreach ($tca as $table => $tableDefinition) {
            $transOrigPointerFieldName = $tableDefinition['ctrl']['transOrigPointerField'] ?? null;
            if ($transOrigPointerFieldName && !is_array($tableDefinition['columns'][$transOrigPointerFieldName] ?? null)) {
                $languageFieldName = $tableDefinition['ctrl']['languageField'];
                $tca[$table]['columns'][$transOrigPointerFieldName] = [
                    'displayCond' => 'FIELD:' . $languageFieldName . ':>:0',
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
                        'foreign_table' => $table,
                        'foreign_table_where' => 'AND {#' . $table . '}.{#pid}=###CURRENT_PID### AND {#' . $table . '}.{#' . $languageFieldName . '} IN (-1,0)',
                        'default' => 0,
                    ],
                ];
            }
        }
        return $tca;
    }

    private function enrichTransOrigDiffSourceField(array $tca): array
    {
        foreach ($tca as $table => $tableDefinition) {
            $transOrigDiffSourceFieldName = $tableDefinition['ctrl']['transOrigDiffSourceField'] ?? null;
            if ($transOrigDiffSourceFieldName && !is_array($tableDefinition['columns'][$transOrigDiffSourceFieldName] ?? null)) {
                $tca[$table]['columns'][$transOrigDiffSourceFieldName] = [
                    'config' => [
                        'type' => 'passthrough',
                        'default' => '',
                    ],
                ];
            }
        }
        return $tca;
    }

    private function enrichTranslationSourceField(array $tca): array
    {
        foreach ($tca as $table => $tableDefinition) {
            $translationSourceFieldName = $tableDefinition['ctrl']['translationSource'] ?? null;
            if ($translationSourceFieldName && !is_array($tableDefinition['columns'][$translationSourceFieldName] ?? null)) {
                $tca[$table]['columns'][$translationSourceFieldName] = [
                    'config' => [
                        'type' => 'passthrough',
                    ],
                ];
            }
        }
        return $tca;
    }
}
