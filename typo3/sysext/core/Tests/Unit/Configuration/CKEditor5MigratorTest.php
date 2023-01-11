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

namespace TYPO3\CMS\Core\Tests\Unit\Configuration;

use TYPO3\CMS\Core\Configuration\CKEditor5Migrator;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class CKEditor5MigratorTest extends UnitTestCase
{
    public function migrationDataProvider(): array
    {
        return [
            'empty' => [
                [],
                [
                    'removePlugins' => [],
                    'toolbar' => [
                        'items' => [],
                        'removeItems' => [],
                        'shouldNotGroupWhenFull' => true,
                    ],
                    'alignment' => [
                        'options' => [
                            ['name' => 'left', 'className' => 'text-left'],
                            ['name' => 'center', 'className' => 'text-center'],
                            ['name' => 'right', 'className' => 'text-right'],
                            ['name' => 'justify', 'className' => 'text-justify'],
                        ],
                    ],
                    'wordCount' => [
                        'displayCharacters' => true,
                        'displayWords' => true,
                    ],
                ],
            ],

            // Remove Plugins
            'Remove plugins mapping' => [
                [
                    'removePlugins' => ['image'],
                ],
                [
                    'removePlugins' => ['Image'],
                    'toolbar' => [
                        'items' => [],
                        'removeItems' => [],
                        'shouldNotGroupWhenFull' => true,
                    ],
                    'alignment' => [
                        'options' => [
                            ['name' => 'left', 'className' => 'text-left'],
                            ['name' => 'center', 'className' => 'text-center'],
                            ['name' => 'right', 'className' => 'text-right'],
                            ['name' => 'justify', 'className' => 'text-justify'],
                        ],
                    ],
                    'wordCount' => [
                        'displayCharacters' => true,
                        'displayWords' => true,
                    ],
                ],
            ],

            // Remove Buttons
            'Remove buttons mapping' => [
                [
                    'removeButtons' => [
                        'Anchor',
                        'Superscript',
                        'Subscript',
                        'Underline',
                        'Strike',
                        'Styles',
                    ],
                ],
                [
                    'removePlugins' => [],
                    'toolbar' => [
                        'items' => [],
                        'removeItems' => [
                            'superscript',
                            'subscript',
                            'underline',
                            'strikethrough',
                            'style',
                        ],
                        'shouldNotGroupWhenFull' => true,
                    ],
                    'alignment' => [
                        'options' => [
                            ['name' => 'left', 'className' => 'text-left'],
                            ['name' => 'center', 'className' => 'text-center'],
                            ['name' => 'right', 'className' => 'text-right'],
                            ['name' => 'justify', 'className' => 'text-justify'],
                        ],
                    ],
                    'wordCount' => [
                        'displayCharacters' => true,
                        'displayWords' => true,
                    ],
                ],
            ],

            // Toolbar migration
            'CKEditor4 Toolbar Basic' => [
                [
                    'toolbar' => [
                        ['Bold', 'Italic', '-', 'Underline', 'Strike'],
                        ['Subscript', 'Superscript', 'SoftHyphen'],
                        '/',
                        ['NumberedList', 'BulletedList'],
                    ],
                ],
                [
                    'removePlugins' => [],
                    'toolbar' => [
                        'items' => [
                            'bold',
                            'italic',
                            '|',
                            'underline',
                            'strikethrough',
                            '|',
                            'subscript',
                            'superscript',
                            'softhyphen',
                            '-',
                            'numberedList',
                            'bulletedList',
                        ],
                        'removeItems' => [],
                        'shouldNotGroupWhenFull' => true,
                    ],
                    'alignment' => [
                        'options' => [
                            ['name' => 'left', 'className' => 'text-left'],
                            ['name' => 'center', 'className' => 'text-center'],
                            ['name' => 'right', 'className' => 'text-right'],
                            ['name' => 'justify', 'className' => 'text-justify'],
                        ],
                    ],
                    'wordCount' => [
                        'displayCharacters' => true,
                        'displayWords' => true,
                    ],
                ],
            ],
            'CKEditor4 Toolbar Advanced' => [
                [
                    'toolbar' => [
                        [
                            'name' => 'basicstyles',
                            'items' => [
                                'Bold',
                                'Italic',
                                '-',
                                'Underline',
                                'Strike',
                                '-',
                                'Subscript',
                                'Superscript',
                                'SoftHyphen',
                            ],
                        ],
                        '/',
                        [
                            'name' => 'list',
                            'items' => [
                                'NumberedList', 'BulletedList',
                            ],
                        ],
                    ],
                ],
                [
                    'removePlugins' => [],
                    'toolbar' => [
                        'items' => [
                            'bold',
                            'italic',
                            '|',
                            'underline',
                            'strikethrough',
                            '|',
                            'subscript',
                            'superscript',
                            'softhyphen',
                            '-',
                            'numberedList',
                            'bulletedList',
                        ],
                        'removeItems' => [],
                        'shouldNotGroupWhenFull' => true,
                    ],
                    'alignment' => [
                        'options' => [
                            ['name' => 'left', 'className' => 'text-left'],
                            ['name' => 'center', 'className' => 'text-center'],
                            ['name' => 'right', 'className' => 'text-right'],
                            ['name' => 'justify', 'className' => 'text-justify'],
                        ],
                    ],
                    'wordCount' => [
                        'displayCharacters' => true,
                        'displayWords' => true,
                    ],
                ],
            ],
            'CKEditor4 ToolbarGroups' => [
                [
                    'toolbarGroups' => [
                        [
                            'name' => 'basicstyles',
                            'groups' => ['basicstyles'],
                        ],
                        '/',
                        [
                            'name' => 'paragraph',
                            'groups' => ['list', '-', 'indent', 'blocks', '-', 'align'],
                        ],
                    ],
                ],
                [
                    'removePlugins' => [],
                    'toolbar' => [
                        'items' => [
                            'bold',
                            'italic',
                            'underline',
                            'strikethrough',
                            'subscript',
                            'superscript',
                            'softhyphen',
                            '-',
                            'numberedList',
                            'bulletedList',
                            '|',
                            'indent',
                            'outdent',
                            '|',
                            'blockQuote',
                            '|',
                            'alignment:left',
                            'alignment:center',
                            'alignment:right',
                            'alignment:justify',
                        ],
                        'removeItems' => [],
                        'shouldNotGroupWhenFull' => true,
                    ],
                    'alignment' => [
                        'options' => [
                            ['name' => 'left', 'className' => 'text-left'],
                            ['name' => 'center', 'className' => 'text-center'],
                            ['name' => 'right', 'className' => 'text-right'],
                            ['name' => 'justify', 'className' => 'text-justify'],
                        ],
                    ],
                    'wordCount' => [
                        'displayCharacters' => true,
                        'displayWords' => true,
                    ],
                ],
            ],
            'CKEditor4 ToolbarReference' => [
                [
                    'toolbar' => 'Basic',
                    'toolbar_Basic' => [
                        ['Bold', 'Italic', '-', 'Underline', 'Strike'],
                        ['Subscript', 'Superscript', 'SoftHyphen'],
                        '/',
                        ['NumberedList', 'BulletedList'],
                    ],
                ],
                [
                    'removePlugins' => [],
                    'toolbar' => [
                        'items' => [
                            'bold',
                            'italic',
                            '|',
                            'underline',
                            'strikethrough',
                            '|',
                            'subscript',
                            'superscript',
                            'softhyphen',
                            '-',
                            'numberedList',
                            'bulletedList',
                        ],
                        'removeItems' => [],
                        'shouldNotGroupWhenFull' => true,
                    ],
                    'alignment' => [
                        'options' => [
                            ['name' => 'left', 'className' => 'text-left'],
                            ['name' => 'center', 'className' => 'text-center'],
                            ['name' => 'right', 'className' => 'text-right'],
                            ['name' => 'justify', 'className' => 'text-justify'],
                        ],
                    ],
                    'wordCount' => [
                        'displayCharacters' => true,
                        'displayWords' => true,
                    ],
                ],
            ],
            'CKEditor5 Flat' => [
                [
                    'toolbar' => [
                        'bold',
                        'italic',
                        '|',
                        'underline',
                        'strikethrough',
                        '|',
                        'subscript',
                        'superscript',
                        'softhyphen',
                        '-',
                        'numberedList',
                        'bulletedList',
                    ],
                ],
                [
                    'removePlugins' => [],
                    'toolbar' => [
                        'items' => [
                            'bold',
                            'italic',
                            '|',
                            'underline',
                            'strikethrough',
                            '|',
                            'subscript',
                            'superscript',
                            'softhyphen',
                            '-',
                            'numberedList',
                            'bulletedList',
                        ],
                        'removeItems' => [],
                        'shouldNotGroupWhenFull' => true,
                    ],
                    'alignment' => [
                        'options' => [
                            ['name' => 'left', 'className' => 'text-left'],
                            ['name' => 'center', 'className' => 'text-center'],
                            ['name' => 'right', 'className' => 'text-right'],
                            ['name' => 'justify', 'className' => 'text-justify'],
                        ],
                    ],
                    'wordCount' => [
                        'displayCharacters' => true,
                        'displayWords' => true,
                    ],
                ],
            ],
            'CKEditor5 Nested' => [
                [
                    'toolbar' => [
                        'items' => [
                            'bold',
                            'italic',
                            '|',
                            'underline',
                            'strikethrough',
                            '|',
                            'subscript',
                            'superscript',
                            'softhyphen',
                            '-',
                            'numberedList',
                            'bulletedList',
                        ],
                    ],
                ],
                [
                    'removePlugins' => [],
                    'toolbar' => [
                        'items' => [
                            'bold',
                            'italic',
                            '|',
                            'underline',
                            'strikethrough',
                            '|',
                            'subscript',
                            'superscript',
                            'softhyphen',
                            '-',
                            'numberedList',
                            'bulletedList',
                        ],
                        'removeItems' => [],
                        'shouldNotGroupWhenFull' => true,
                    ],
                    'alignment' => [
                        'options' => [
                            ['name' => 'left', 'className' => 'text-left'],
                            ['name' => 'center', 'className' => 'text-center'],
                            ['name' => 'right', 'className' => 'text-right'],
                            ['name' => 'justify', 'className' => 'text-justify'],
                        ],
                    ],
                    'wordCount' => [
                        'displayCharacters' => true,
                        'displayWords' => true,
                    ],
                ],
            ],
            'CKEditor5 Grouped' => [
                [
                    'toolbar' => [
                        'items' => [
                            'bold',
                            'italic',
                            [
                                'label' => 'More basic styles',
                                'icon' => 'threeVerticalDots',
                                'items' => ['strikethrough', 'superscript', 'subscript'],
                            ],
                        ],
                    ],
                ],
                [
                    'removePlugins' => [],
                    'toolbar' => [
                        'items' => [
                            'bold',
                            'italic',
                            [
                                'label' => 'More basic styles',
                                'icon' => 'threeVerticalDots',
                                'items' => ['strikethrough', 'superscript', 'subscript'],
                            ],
                        ],
                        'removeItems' => [],
                        'shouldNotGroupWhenFull' => true,
                    ],
                    'alignment' => [
                        'options' => [
                            ['name' => 'left', 'className' => 'text-left'],
                            ['name' => 'center', 'className' => 'text-center'],
                            ['name' => 'right', 'className' => 'text-right'],
                            ['name' => 'justify', 'className' => 'text-justify'],
                        ],
                    ],
                    'wordCount' => [
                        'displayCharacters' => true,
                        'displayWords' => true,
                    ],
                ],
            ],

            // TYPO3 11.5 Toolbar Presets
            'CKEditor4 Toolbar Default' => [
                [
                    'toolbarGroups' => [
                        ['name' => 'styles', 'groups' => ['styles', 'format']],
                        ['name' => 'basicstyles', 'groups' => ['basicstyles']],
                        ['name' => 'paragraph', 'groups' => ['list', 'indent', 'blocks', 'align']],
                        ['name' => 'links', 'groups' => ['links']],
                        ['name' => 'clipboard', 'groups' => ['clipboard', 'cleanup', 'undo']],
                        ['name' => 'editing', 'groups' => ['spellchecker']],
                        ['name' => 'insert', 'groups' => ['insert']],
                        ['name' => 'tools', 'groups' => ['table', 'specialchar', 'insertcharacters']],
                        ['name' => 'document', 'groups' => ['mode']],
                    ],
                ],
                [
                    'removePlugins' => [],
                    'toolbar' => [
                        'items' => [
                            'style',
                            'heading',
                            '|',
                            'bold',
                            'italic',
                            'underline',
                            'strikethrough',
                            'subscript',
                            'superscript',
                            'softhyphen',
                            '|',
                            'numberedList',
                            'bulletedList',
                            '|',
                            'indent',
                            'outdent',
                            '|',
                            'blockQuote',
                            '|',
                            'alignment:left',
                            'alignment:center',
                            'alignment:right',
                            'alignment:justify',
                            '|',
                            'link',
                            '|',
                            'removeFormat',
                            '|',
                            'undo',
                            'redo',
                            '|',
                            'insertImage',
                            'insertTable',
                            'horizontalLine',
                            'specialCharacters',
                            'pageBreak',
                            '|',
                            'insertcharacters',
                            '|',
                            'sourceEditing',
                        ],
                        'removeItems' => [],
                        'shouldNotGroupWhenFull' => true,
                    ],
                    'alignment' => [
                        'options' => [
                            ['name' => 'left', 'className' => 'text-left'],
                            ['name' => 'center', 'className' => 'text-center'],
                            ['name' => 'right', 'className' => 'text-right'],
                            ['name' => 'justify', 'className' => 'text-justify'],
                        ],
                    ],
                    'wordCount' => [
                        'displayCharacters' => true,
                        'displayWords' => true,
                    ],
                ],
            ],
            'CKEditor4 Toolbar Full' => [
                [
                    'toolbarGroups' => [
                        ['name' => 'clipboard', 'groups' => ['clipboard', 'undo']],
                        ['name' => 'editing', 'groups' => ['find', 'selection', 'spellchecker']],
                        ['name' => 'links'],
                        ['name' => 'insert'],
                        ['name' => 'tools'],
                        ['name' => 'table'],
                        ['name' => 'tabletools'],
                        ['name' => 'document', 'groups' => ['mode', 'document', 'doctools']],
                        ['name' => 'others'],
                        '/',
                        ['name' => 'basicstyles', 'groups' => ['basicstyles', 'align', 'cleanup']],
                        ['name' => 'document', 'groups' => ['list', 'indent', 'blocks', 'align', 'bidi']],
                        ['name' => 'specialcharacters', 'groups' => ['insertcharacters']],
                        '/',
                        ['name' => 'styles'],
                    ],
                ],
                [
                    'removePlugins' => [],
                    'toolbar' => [
                        'items' => [
                            'undo',
                            'redo',
                            '|',
                            'findAndReplace',
                            '|',
                            'selectAll',
                            '|',
                            'link',
                            '|',
                            'insertImage',
                            'insertTable',
                            'horizontalLine',
                            'specialCharacters',
                            'pageBreak',
                            '|',
                            'sourceEditing',
                            '-',
                            'bold',
                            'italic',
                            'underline',
                            'strikethrough',
                            'subscript',
                            'superscript',
                            'softhyphen',
                            '|',
                            'alignment:left',
                            'alignment:center',
                            'alignment:right',
                            'alignment:justify',
                            '|',
                            'removeFormat',
                            '|',
                            'numberedList',
                            'bulletedList',
                            '|',
                            'indent',
                            'outdent',
                            '|',
                            'blockQuote',
                            '|',
                            'textPartLanguage',
                            '|',
                            'insertcharacters',
                            '-',
                            'style',
                            'heading',
                        ],
                        'removeItems' => [],
                        'shouldNotGroupWhenFull' => true,
                    ],
                    'alignment' => [
                        'options' => [
                            ['name' => 'left', 'className' => 'text-left'],
                            ['name' => 'center', 'className' => 'text-center'],
                            ['name' => 'right', 'className' => 'text-right'],
                            ['name' => 'justify', 'className' => 'text-justify'],
                        ],
                    ],
                    'wordCount' => [
                        'displayCharacters' => true,
                        'displayWords' => true,
                    ],
                ],
            ],
            'CKEditor4 Toolbar Minimal' => [
                [
                    'toolbarGroups' => [
                        ['name' => 'basicstyles', 'groups' => ['basicstyles']],
                        ['name' => 'clipboard', 'groups' => ['clipboard', 'undo']],
                    ],
                ],
                [
                    'removePlugins' => [],
                    'toolbar' => [
                        'items' => [
                            'bold',
                            'italic',
                            'underline',
                            'strikethrough',
                            'subscript',
                            'superscript',
                            'softhyphen',
                            '|',
                            'undo',
                            'redo',
                        ],
                        'removeItems' => [],
                        'shouldNotGroupWhenFull' => true,
                    ],
                    'alignment' => [
                        'options' => [
                            ['name' => 'left', 'className' => 'text-left'],
                            ['name' => 'center', 'className' => 'text-center'],
                            ['name' => 'right', 'className' => 'text-right'],
                            ['name' => 'justify', 'className' => 'text-justify'],
                        ],
                    ],
                    'wordCount' => [
                        'displayCharacters' => true,
                        'displayWords' => true,
                    ],
                ],
            ],

            // Plugin Alignment Handling
            'Remove Alignment Plugin' => [
                [
                    'removePlugins' => ['Alignment'],
                ],
                [
                    'removePlugins' => ['Alignment'],
                    'toolbar' => [
                        'items' => [],
                        'removeItems' => [
                            'alignment',
                            'alignment:left',
                            'alignment:right',
                            'alignment:center',
                            'alignment:justify',
                        ],
                        'shouldNotGroupWhenFull' => true,
                    ],
                    'wordCount' => [
                        'displayCharacters' => true,
                        'displayWords' => true,
                    ],
                ],
            ],
            'Configure Alignment like CKEditor4' => [
                [
                    'justifyClasses' => [
                        'left',
                        'center',
                        'right',
                        'justify',
                    ],
                ],
                [
                    'removePlugins' => [],
                    'toolbar' => [
                        'items' => [],
                        'removeItems' => [],
                        'shouldNotGroupWhenFull' => true,
                    ],
                    'alignment' => [
                        'options' => [
                            ['name' => 'left', 'className' => 'left'],
                            ['name' => 'center', 'className' => 'center'],
                            ['name' => 'right', 'className' => 'right'],
                            ['name' => 'justify', 'className' => 'justify'],
                        ],
                    ],
                    'wordCount' => [
                        'displayCharacters' => true,
                        'displayWords' => true,
                    ],
                ],
            ],
            'Configure Alignment like CKEditor4 Partially' => [
                [
                    'justifyClasses' => [
                        'left',
                        'center',
                    ],
                ],
                [
                    'removePlugins' => [],
                    'toolbar' => [
                        'items' => [],
                        'removeItems' => [],
                        'shouldNotGroupWhenFull' => true,
                    ],
                    'alignment' => [
                        'options' => [
                            ['name' => 'left', 'className' => 'left'],
                            ['name' => 'center', 'className' => 'center'],
                            ['name' => 'right', 'className' => 'text-right'],
                            ['name' => 'justify', 'className' => 'text-justify'],
                        ],
                    ],
                    'wordCount' => [
                        'displayCharacters' => true,
                        'displayWords' => true,
                    ],
                ],
            ],
            'Configure Alignment like CKEditor5' => [
                [
                    'alignment' => [
                        'options' => [
                            ['name' => 'left', 'className' => 'left'],
                            ['name' => 'center', 'className' => 'center'],
                            ['name' => 'right', 'className' => 'right'],
                            ['name' => 'justify', 'className' => 'justify'],
                        ],
                    ],
                ],
                [
                    'removePlugins' => [],
                    'toolbar' => [
                        'items' => [],
                        'removeItems' => [],
                        'shouldNotGroupWhenFull' => true,
                    ],
                    'alignment' => [
                        'options' => [
                            ['name' => 'left', 'className' => 'left'],
                            ['name' => 'center', 'className' => 'center'],
                            ['name' => 'right', 'className' => 'right'],
                            ['name' => 'justify', 'className' => 'justify'],
                        ],
                    ],
                    'wordCount' => [
                        'displayCharacters' => true,
                        'displayWords' => true,
                    ],
                ],
            ],
            'Configure Alignment like CKEditor5 Partially' => [
                [
                    'alignment' => [
                        'options' => [
                            ['name' => 'center', 'className' => 'center'],
                            ['name' => 'justify', 'className' => 'justify'],
                        ],
                    ],
                ],
                [
                    'removePlugins' => [],
                    'toolbar' => [
                        'items' => [],
                        'removeItems' => [],
                        'shouldNotGroupWhenFull' => true,
                    ],
                    'alignment' => [
                        'options' => [
                            ['name' => 'left', 'className' => 'text-left'],
                            ['name' => 'center', 'className' => 'center'],
                            ['name' => 'right', 'className' => 'text-right'],
                            ['name' => 'justify', 'className' => 'justify'],
                        ],
                    ],
                    'wordCount' => [
                        'displayCharacters' => true,
                        'displayWords' => true,
                    ],
                ],
            ],

            // Plugin WordCount Handling
            'Remove WordCount Plugin' => [
                [
                    'removePlugins' => ['WordCount'],
                ],
                [
                    'removePlugins' => ['WordCount'],
                    'toolbar' => [
                        'items' => [],
                        'removeItems' => [],
                        'shouldNotGroupWhenFull' => true,
                    ],
                    'alignment' => [
                        'options' => [
                            ['name' => 'left', 'className' => 'text-left'],
                            ['name' => 'center', 'className' => 'text-center'],
                            ['name' => 'right', 'className' => 'text-right'],
                            ['name' => 'justify', 'className' => 'text-justify'],
                        ],
                    ],
                ],
            ],
            'Configure WordCount like CKEditor4' => [
                [
                    'wordcount' => [
                        'showCharCount' => false,
                        'showWordCount' => false,
                    ],
                ],
                [
                    'removePlugins' => [],
                    'toolbar' => [
                        'items' => [],
                        'removeItems' => [],
                        'shouldNotGroupWhenFull' => true,
                    ],
                    'alignment' => [
                        'options' => [
                            ['name' => 'left', 'className' => 'text-left'],
                            ['name' => 'center', 'className' => 'text-center'],
                            ['name' => 'right', 'className' => 'text-right'],
                            ['name' => 'justify', 'className' => 'text-justify'],
                        ],
                    ],
                    'wordCount' => [
                        'displayCharacters' => false,
                        'displayWords' => false,
                    ],
                ],
            ],
            'Configure WordCount like CKEditor4 Partially' => [
                [
                    'wordcount' => [
                        'showCharCount' => false,
                    ],
                ],
                [
                    'removePlugins' => [],
                    'toolbar' => [
                        'items' => [],
                        'removeItems' => [],
                        'shouldNotGroupWhenFull' => true,
                    ],
                    'alignment' => [
                        'options' => [
                            ['name' => 'left', 'className' => 'text-left'],
                            ['name' => 'center', 'className' => 'text-center'],
                            ['name' => 'right', 'className' => 'text-right'],
                            ['name' => 'justify', 'className' => 'text-justify'],
                        ],
                    ],
                    'wordCount' => [
                        'displayCharacters' => false,
                        'displayWords' => true,
                    ],
                ],
            ],
            'Configure WordCount like CKEditor5' => [
                [
                    'wordCount' => [
                        'displayCharacters' => false,
                        'displayWords' => false,
                    ],
                ],
                [
                    'removePlugins' => [],
                    'toolbar' => [
                        'items' => [],
                        'removeItems' => [],
                        'shouldNotGroupWhenFull' => true,
                    ],
                    'alignment' => [
                        'options' => [
                            ['name' => 'left', 'className' => 'text-left'],
                            ['name' => 'center', 'className' => 'text-center'],
                            ['name' => 'right', 'className' => 'text-right'],
                            ['name' => 'justify', 'className' => 'text-justify'],
                        ],
                    ],
                    'wordCount' => [
                        'displayCharacters' => false,
                        'displayWords' => false,
                    ],
                ],
            ],
            'Configure WordCount like CKEditor5 Partially' => [
                [
                    'wordCount' => [
                        'displayCharacters' => false,
                    ],
                ],
                [
                    'removePlugins' => [],
                    'toolbar' => [
                        'items' => [],
                        'removeItems' => [],
                        'shouldNotGroupWhenFull' => true,
                    ],
                    'alignment' => [
                        'options' => [
                            ['name' => 'left', 'className' => 'text-left'],
                            ['name' => 'center', 'className' => 'text-center'],
                            ['name' => 'right', 'className' => 'text-right'],
                            ['name' => 'justify', 'className' => 'text-justify'],
                        ],
                    ],
                    'wordCount' => [
                        'displayCharacters' => false,
                        'displayWords' => true,
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider migrationDataProvider
     * @test
     */
    public function migrationTests(array $configuration, array $expectation): void
    {
        $finalConfiguration = GeneralUtility::makeInstance(
            CKEditor5Migrator::class,
            $configuration
        )->get();
        self::assertEquals($expectation, $finalConfiguration);
    }
}
