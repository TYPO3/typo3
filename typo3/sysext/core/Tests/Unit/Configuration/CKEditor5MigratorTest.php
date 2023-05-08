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

final class CKEditor5MigratorTest extends UnitTestCase
{
    public static function migrationDataProvider(): array
    {
        return [
            'empty' => [
                [
                    'editor' => [
                        'config' => [
                        ],
                    ],
                ],
                [
                    'editor' => [
                        'config' => [
                            'removePlugins' => [],
                            'toolbar' => [
                                'items' => [
                                    'softhyphen',
                                ],
                                'removeItems' => [],
                                'shouldNotGroupWhenFull' => true,
                            ],
                            'alignment' => [
                                'options' => [
                                    ['name' => 'left', 'className' => 'text-start'],
                                    ['name' => 'center', 'className' => 'text-center'],
                                    ['name' => 'right', 'className' => 'text-end'],
                                    ['name' => 'justify', 'className' => 'text-justify'],
                                ],
                            ],
                            'wordCount' => [
                                'displayCharacters' => true,
                                'displayWords' => true,
                            ],
                        ],
                    ],
                ],
            ],

            // Remove Plugins
            'Remove plugins mapping' => [
                [
                    'editor' => [
                        'config' => [
                            'removePlugins' => ['image'],
                        ],
                    ],
                ],
                [
                    'editor' => [
                        'config' => [
                            'removePlugins' => ['Image'],
                            'toolbar' => [
                                'items' => [
                                    'softhyphen',
                                ],
                                'removeItems' => [],
                                'shouldNotGroupWhenFull' => true,
                            ],
                            'alignment' => [
                                'options' => [
                                    ['name' => 'left', 'className' => 'text-start'],
                                    ['name' => 'center', 'className' => 'text-center'],
                                    ['name' => 'right', 'className' => 'text-end'],
                                    ['name' => 'justify', 'className' => 'text-justify'],
                                ],
                            ],
                            'wordCount' => [
                                'displayCharacters' => true,
                                'displayWords' => true,
                            ],
                        ],
                    ],
                ],
            ],

            // Remove Buttons. Configured as array
            'Remove buttons mapping (array)' => [
                [
                    'editor' => [
                        'config' => [
                            'removeButtons' => [
                                'Anchor',
                                'Superscript',
                                'Subscript',
                                'Underline',
                                'Strike',
                                'Styles',
                            ],
                        ],
                    ],
                ],
                [
                    'editor' => [
                        'config' => [
                            'removePlugins' => [],
                            'toolbar' => [
                                'items' => [
                                    'softhyphen',
                                ],
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
                                    ['name' => 'left', 'className' => 'text-start'],
                                    ['name' => 'center', 'className' => 'text-center'],
                                    ['name' => 'right', 'className' => 'text-end'],
                                    ['name' => 'justify', 'className' => 'text-justify'],
                                ],
                            ],
                            'wordCount' => [
                                'displayCharacters' => true,
                                'displayWords' => true,
                            ],
                        ],
                    ],
                ],
            ],

            // Remove Buttons. Configured as string
            'Remove buttons mapping (string)' => [
                [
                    'editor' => [
                        'config' => [
                            'removeButtons' => 'Anchor,Superscript,Subscript,Underline,Strike,Styles',
                        ],
                    ],
                ],
                [
                    'editor' => [
                        'config' => [
                            'removePlugins' => [],
                            'toolbar' => [
                                'items' => [
                                    'softhyphen',
                                ],
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
                                    ['name' => 'left', 'className' => 'text-start'],
                                    ['name' => 'center', 'className' => 'text-center'],
                                    ['name' => 'right', 'className' => 'text-end'],
                                    ['name' => 'justify', 'className' => 'text-justify'],
                                ],
                            ],
                            'wordCount' => [
                                'displayCharacters' => true,
                                'displayWords' => true,
                            ],
                        ],
                    ],
                ],
            ],

            // Toolbar migration
            'CKEditor4 Toolbar Basic' => [
                [
                    'editor' => [
                        'config' => [
                            'toolbar' => [
                                ['Bold', 'Italic', '-', 'Underline', 'Strike'],
                                ['Subscript', 'Superscript', 'SoftHyphen'],
                                '/',
                                ['NumberedList', 'BulletedList'],
                            ],
                        ],
                    ],
                ],
                [
                    'editor' => [
                        'config' => [
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
                                    ['name' => 'left', 'className' => 'text-start'],
                                    ['name' => 'center', 'className' => 'text-center'],
                                    ['name' => 'right', 'className' => 'text-end'],
                                    ['name' => 'justify', 'className' => 'text-justify'],
                                ],
                            ],
                            'wordCount' => [
                                'displayCharacters' => true,
                                'displayWords' => true,
                            ],
                        ],
                    ],
                ],
            ],
            'CKEditor4 Toolbar Advanced' => [
                [
                    'editor' => [
                        'config' => [
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
                    ],
                ],
                [
                    'editor' => [
                        'config' => [
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
                                    ['name' => 'left', 'className' => 'text-start'],
                                    ['name' => 'center', 'className' => 'text-center'],
                                    ['name' => 'right', 'className' => 'text-end'],
                                    ['name' => 'justify', 'className' => 'text-justify'],
                                ],
                            ],
                            'wordCount' => [
                                'displayCharacters' => true,
                                'displayWords' => true,
                            ],
                        ],
                    ],
                ],
            ],
            'CKEditor4 ToolbarGroups' => [
                [
                    'editor' => [
                        'config' => [
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
                    ],
                ],
                [
                    'editor' => [
                        'config' => [
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
                                    ['name' => 'left', 'className' => 'text-start'],
                                    ['name' => 'center', 'className' => 'text-center'],
                                    ['name' => 'right', 'className' => 'text-end'],
                                    ['name' => 'justify', 'className' => 'text-justify'],
                                ],
                            ],
                            'wordCount' => [
                                'displayCharacters' => true,
                                'displayWords' => true,
                            ],
                        ],
                    ],
                ],
            ],
            'CKEditor4 ToolbarReference' => [
                [
                    'editor' => [
                        'config' => [
                            'toolbar' => 'Basic',
                            'toolbar_Basic' => [
                                ['Bold', 'Italic', '-', 'Underline', 'Strike'],
                                ['Subscript', 'Superscript', 'SoftHyphen'],
                                '/',
                                ['NumberedList', 'BulletedList'],
                            ],
                        ],
                    ],
                ],
                [
                    'editor' => [
                        'config' => [
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
                                    ['name' => 'left', 'className' => 'text-start'],
                                    ['name' => 'center', 'className' => 'text-center'],
                                    ['name' => 'right', 'className' => 'text-end'],
                                    ['name' => 'justify', 'className' => 'text-justify'],
                                ],
                            ],
                            'wordCount' => [
                                'displayCharacters' => true,
                                'displayWords' => true,
                            ],
                        ],
                    ],
                ],
            ],
            'CKEditor5 Flat' => [
                [
                    'editor' => [
                        'config' => [
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
                    ],
                ],
                [
                    'editor' => [
                        'config' => [
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
                                    ['name' => 'left', 'className' => 'text-start'],
                                    ['name' => 'center', 'className' => 'text-center'],
                                    ['name' => 'right', 'className' => 'text-end'],
                                    ['name' => 'justify', 'className' => 'text-justify'],
                                ],
                            ],
                            'wordCount' => [
                                'displayCharacters' => true,
                                'displayWords' => true,
                            ],
                        ],
                    ],
                ],
            ],
            'CKEditor5 Nested' => [
                [
                    'editor' => [
                        'config' => [
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
                    ],
                ],
                [
                    'editor' => [
                        'config' => [
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
                                    ['name' => 'left', 'className' => 'text-start'],
                                    ['name' => 'center', 'className' => 'text-center'],
                                    ['name' => 'right', 'className' => 'text-end'],
                                    ['name' => 'justify', 'className' => 'text-justify'],
                                ],
                            ],
                            'wordCount' => [
                                'displayCharacters' => true,
                                'displayWords' => true,
                            ],
                        ],
                    ],
                ],
            ],
            'CKEditor5 Grouped' => [
                [
                    'editor' => [
                        'config' => [
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
                    ],
                ],
                [
                    'editor' => [
                        'config' => [
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
                                    'softhyphen',
                                ],
                                'removeItems' => [],
                                'shouldNotGroupWhenFull' => true,
                            ],
                            'alignment' => [
                                'options' => [
                                    ['name' => 'left', 'className' => 'text-start'],
                                    ['name' => 'center', 'className' => 'text-center'],
                                    ['name' => 'right', 'className' => 'text-end'],
                                    ['name' => 'justify', 'className' => 'text-justify'],
                                ],
                            ],
                            'wordCount' => [
                                'displayCharacters' => true,
                                'displayWords' => true,
                            ],
                        ],
                    ],
                ],
            ],

            // TYPO3 11.5 Toolbar Presets
            'CKEditor4 Toolbar Default' => [
                [
                    'editor' => [
                        'config' => [
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
                    ],
                ],
                [
                    'editor' => [
                        'config' => [
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
                                    ['name' => 'left', 'className' => 'text-start'],
                                    ['name' => 'center', 'className' => 'text-center'],
                                    ['name' => 'right', 'className' => 'text-end'],
                                    ['name' => 'justify', 'className' => 'text-justify'],
                                ],
                            ],
                            'wordCount' => [
                                'displayCharacters' => true,
                                'displayWords' => true,
                            ],
                        ],
                    ],
                ],
            ],
            'CKEditor4 Toolbar Full' => [
                [
                    'editor' => [
                        'config' => [
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
                    ],
                ],
                [
                    'editor' => [
                        'config' => [
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
                                    ['name' => 'left', 'className' => 'text-start'],
                                    ['name' => 'center', 'className' => 'text-center'],
                                    ['name' => 'right', 'className' => 'text-end'],
                                    ['name' => 'justify', 'className' => 'text-justify'],
                                ],
                            ],
                            'wordCount' => [
                                'displayCharacters' => true,
                                'displayWords' => true,
                            ],
                        ],
                    ],
                ],
            ],
            'CKEditor4 Toolbar Minimal' => [
                [
                    'editor' => [
                        'config' => [
                            'toolbarGroups' => [
                                ['name' => 'basicstyles', 'groups' => ['basicstyles']],
                                ['name' => 'clipboard', 'groups' => ['clipboard', 'undo']],
                            ],
                        ],
                    ],
                ],
                [
                    'editor' => [
                        'config' => [
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
                                    ['name' => 'left', 'className' => 'text-start'],
                                    ['name' => 'center', 'className' => 'text-center'],
                                    ['name' => 'right', 'className' => 'text-end'],
                                    ['name' => 'justify', 'className' => 'text-justify'],
                                ],
                            ],
                            'wordCount' => [
                                'displayCharacters' => true,
                                'displayWords' => true,
                            ],
                        ],
                    ],
                ],
            ],
            'Migrate contentsCss to array' => [
                [
                    'editor' => [
                        'config' => [
                            'contentsCss' => 'EXT:example/Resources/Public/Css/ckeditor.css',
                        ],
                    ],
                ],
                [
                    'editor' => [
                        'config' => [
                            'contentsCss' => [
                                'EXT:example/Resources/Public/Css/ckeditor.css',
                            ],
                            'removePlugins' => [],
                            'toolbar' => [
                                'items' => [
                                    'softhyphen',
                                ],
                                'removeItems' => [],
                                'shouldNotGroupWhenFull' => true,
                            ],
                            'alignment' => [
                                'options' => [
                                    ['name' => 'left', 'className' => 'text-start'],
                                    ['name' => 'center', 'className' => 'text-center'],
                                    ['name' => 'right', 'className' => 'text-end'],
                                    ['name' => 'justify', 'className' => 'text-justify'],
                                ],
                            ],
                            'wordCount' => [
                                'displayCharacters' => true,
                                'displayWords' => true,
                            ],
                        ],
                    ],
                ],
            ],
            'Remove contentsCss if empty' => [
                [
                    'editor' => [
                        'config' => [
                            'contentsCss' => '',
                        ],
                    ],
                ],
                [
                    'editor' => [
                        'config' => [
                            'removePlugins' => [],
                            'toolbar' => [
                                'items' => [
                                    'softhyphen',
                                ],
                                'removeItems' => [],
                                'shouldNotGroupWhenFull' => true,
                            ],
                            'alignment' => [
                                'options' => [
                                    ['name' => 'left', 'className' => 'text-start'],
                                    ['name' => 'center', 'className' => 'text-center'],
                                    ['name' => 'right', 'className' => 'text-end'],
                                    ['name' => 'justify', 'className' => 'text-justify'],
                                ],
                            ],
                            'wordCount' => [
                                'displayCharacters' => true,
                                'displayWords' => true,
                            ],
                        ],
                    ],
                ],
            ],
            'Migrate contentsCss to a clean array with mixed values' => [
                [
                    'editor' => [
                        'config' => [
                            'contentsCss' => [
                                'EXT:example/Resources/Public/Css/ckeditor.css  ', // trailing whitespaces are on purpose
                                '',
                                'EXT:example/Resources/Public/Css/ckeditor2.css',
                                42,
                            ],
                        ],
                    ],
                ],
                [
                    'editor' => [
                        'config' => [
                            'contentsCss' => [
                                'EXT:example/Resources/Public/Css/ckeditor.css',
                                'EXT:example/Resources/Public/Css/ckeditor2.css',
                            ],
                            'removePlugins' => [],
                            'toolbar' => [
                                'items' => [
                                    'softhyphen',
                                ],
                                'removeItems' => [],
                                'shouldNotGroupWhenFull' => true,
                            ],
                            'alignment' => [
                                'options' => [
                                    ['name' => 'left', 'className' => 'text-start'],
                                    ['name' => 'center', 'className' => 'text-center'],
                                    ['name' => 'right', 'className' => 'text-end'],
                                    ['name' => 'justify', 'className' => 'text-justify'],
                                ],
                            ],
                            'wordCount' => [
                                'displayCharacters' => true,
                                'displayWords' => true,
                            ],
                        ],
                    ],
                ],
            ],

            // Plugin Alignment Handling
            'Remove Alignment Plugin' => [
                [
                    'editor' => [
                        'config' => [
                            'removePlugins' => ['Alignment'],
                        ],
                    ],
                ],
                [
                    'editor' => [
                        'config' => [
                            'removePlugins' => ['Alignment'],
                            'toolbar' => [
                                'items' => [
                                    'softhyphen',
                                ],
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
                ],
            ],
            'Configure Alignment like CKEditor4' => [
                [
                    'editor' => [
                        'config' => [
                            'justifyClasses' => [
                                'left',
                                'center',
                                'right',
                                'justify',
                            ],
                        ],
                    ],
                ],
                [
                    'editor' => [
                        'config' => [
                            'removePlugins' => [],
                            'toolbar' => [
                                'items' => [
                                    'softhyphen',
                                ],
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
                ],
            ],
            'Configure Alignment like CKEditor4 Partially' => [
                [
                    'editor' => [
                        'config' => [
                            'justifyClasses' => [
                                'left',
                                'center',
                            ],
                        ],
                    ],
                ],
                [
                    'editor' => [
                        'config' => [
                            'removePlugins' => [],
                            'toolbar' => [
                                'items' => [
                                    'softhyphen',
                                ],
                                'removeItems' => [],
                                'shouldNotGroupWhenFull' => true,
                            ],
                            'alignment' => [
                                'options' => [
                                    ['name' => 'left', 'className' => 'left'],
                                    ['name' => 'center', 'className' => 'center'],
                                    ['name' => 'right', 'className' => 'text-end'],
                                    ['name' => 'justify', 'className' => 'text-justify'],
                                ],
                            ],
                            'wordCount' => [
                                'displayCharacters' => true,
                                'displayWords' => true,
                            ],
                        ],
                    ],
                ],
            ],
            'Configure Alignment like CKEditor5' => [
                [
                    'editor' => [
                        'config' => [
                            'alignment' => [
                                'options' => [
                                    ['name' => 'left', 'className' => 'left'],
                                    ['name' => 'center', 'className' => 'center'],
                                    ['name' => 'right', 'className' => 'right'],
                                    ['name' => 'justify', 'className' => 'justify'],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'editor' => [
                        'config' => [
                            'removePlugins' => [],
                            'toolbar' => [
                                'items' => [
                                    'softhyphen',
                                ],
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
                ],
            ],
            'Configure Alignment like CKEditor5 Partially' => [
                [
                    'editor' => [
                        'config' => [
                            'alignment' => [
                                'options' => [
                                    ['name' => 'center', 'className' => 'center'],
                                    ['name' => 'justify', 'className' => 'justify'],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'editor' => [
                        'config' => [
                            'removePlugins' => [],
                            'toolbar' => [
                                'items' => [
                                    'softhyphen',
                                ],
                                'removeItems' => [],
                                'shouldNotGroupWhenFull' => true,
                            ],
                            'alignment' => [
                                'options' => [
                                    ['name' => 'left', 'className' => 'text-start'],
                                    ['name' => 'center', 'className' => 'center'],
                                    ['name' => 'right', 'className' => 'text-end'],
                                    ['name' => 'justify', 'className' => 'justify'],
                                ],
                            ],
                            'wordCount' => [
                                'displayCharacters' => true,
                                'displayWords' => true,
                            ],
                        ],
                    ],
                ],
            ],

            // Plugin Whitespace Handling
            'Remove Whitespace Plugin' => [
                [
                    'editor' => [
                        'config' => [
                            'removePlugins' => [
                                'whitespace',
                            ],
                        ],
                    ],
                ],
                [
                    'editor' => [
                        'config' => [
                            'removePlugins' => ['Whitespace'],
                            'toolbar' => [
                                'items' => [],
                                'removeItems' => [
                                    'softhyphen',
                                ],
                                'shouldNotGroupWhenFull' => true,
                            ],
                            'alignment' => [
                                'options' => [
                                    ['name' => 'left', 'className' => 'text-start'],
                                    ['name' => 'center', 'className' => 'text-center'],
                                    ['name' => 'right', 'className' => 'text-end'],
                                    ['name' => 'justify', 'className' => 'text-justify'],
                                ],
                            ],
                            'wordCount' => [
                                'displayCharacters' => true,
                                'displayWords' => true,
                            ],
                        ],
                    ],
                ],
            ],
            'Remove Whitespace Plugin (Legacy Name)' => [
                [
                    'editor' => [
                        'config' => [
                            'removePlugins' => [
                                'softhyphen',
                            ],
                        ],
                    ],
                ],
                [
                    'editor' => [
                        'config' => [
                            'removePlugins' => ['Whitespace'],
                            'toolbar' => [
                                'items' => [],
                                'removeItems' => [
                                    'softhyphen',
                                ],
                                'shouldNotGroupWhenFull' => true,
                            ],
                            'alignment' => [
                                'options' => [
                                    ['name' => 'left', 'className' => 'text-start'],
                                    ['name' => 'center', 'className' => 'text-center'],
                                    ['name' => 'right', 'className' => 'text-end'],
                                    ['name' => 'justify', 'className' => 'text-justify'],
                                ],
                            ],
                            'wordCount' => [
                                'displayCharacters' => true,
                                'displayWords' => true,
                            ],
                        ],
                    ],
                ],
            ],
            'Toolbaritem softhyphen not added when exists in toolbar' => [
                [
                    'editor' => [
                        'config' => [
                            'toolbar' => [
                                'items' => [
                                    'softhyphen',
                                    'bold',
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'editor' => [
                        'config' => [
                            'removePlugins' => [],
                            'toolbar' => [
                                'items' => [
                                    'softhyphen',
                                    'bold',
                                ],
                                'removeItems' => [],
                                'shouldNotGroupWhenFull' => true,
                            ],
                            'alignment' => [
                                'options' => [
                                    ['name' => 'left', 'className' => 'text-start'],
                                    ['name' => 'center', 'className' => 'text-center'],
                                    ['name' => 'right', 'className' => 'text-end'],
                                    ['name' => 'justify', 'className' => 'text-justify'],
                                ],
                            ],
                            'wordCount' => [
                                'displayCharacters' => true,
                                'displayWords' => true,
                            ],
                        ],
                    ],
                ],
            ],

            // Plugin WordCount Handling
            'Remove WordCount Plugin' => [
                [
                    'editor' => [
                        'config' => [
                            'removePlugins' => ['WordCount'],
                        ],
                    ],
                ],
                [
                    'editor' => [
                        'config' => [
                            'removePlugins' => ['WordCount'],
                            'toolbar' => [
                                'items' => [
                                    'softhyphen',
                                ],
                                'removeItems' => [],
                                'shouldNotGroupWhenFull' => true,
                            ],
                            'alignment' => [
                                'options' => [
                                    ['name' => 'left', 'className' => 'text-start'],
                                    ['name' => 'center', 'className' => 'text-center'],
                                    ['name' => 'right', 'className' => 'text-end'],
                                    ['name' => 'justify', 'className' => 'text-justify'],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'Configure WordCount like CKEditor4' => [
                [
                    'editor' => [
                        'config' => [
                            'wordcount' => [
                                'showCharCount' => false,
                                'showWordCount' => false,
                            ],
                        ],
                    ],
                ],
                [
                    'editor' => [
                        'config' => [
                            'removePlugins' => [],
                            'toolbar' => [
                                'items' => [
                                    'softhyphen',
                                ],
                                'removeItems' => [],
                                'shouldNotGroupWhenFull' => true,
                            ],
                            'alignment' => [
                                'options' => [
                                    ['name' => 'left', 'className' => 'text-start'],
                                    ['name' => 'center', 'className' => 'text-center'],
                                    ['name' => 'right', 'className' => 'text-end'],
                                    ['name' => 'justify', 'className' => 'text-justify'],
                                ],
                            ],
                            'wordCount' => [
                                'displayCharacters' => false,
                                'displayWords' => false,
                            ],
                        ],
                    ],
                ],
            ],
            'Configure WordCount like CKEditor4 Partially' => [
                [
                    'editor' => [
                        'config' => [
                            'wordcount' => [
                                'showCharCount' => false,
                            ],
                        ],
                    ],

                ],
                [
                    'editor' => [
                        'config' => [
                            'removePlugins' => [],
                            'toolbar' => [
                                'items' => [
                                    'softhyphen',
                                ],
                                'removeItems' => [],
                                'shouldNotGroupWhenFull' => true,
                            ],
                            'alignment' => [
                                'options' => [
                                    ['name' => 'left', 'className' => 'text-start'],
                                    ['name' => 'center', 'className' => 'text-center'],
                                    ['name' => 'right', 'className' => 'text-end'],
                                    ['name' => 'justify', 'className' => 'text-justify'],
                                ],
                            ],
                            'wordCount' => [
                                'displayCharacters' => false,
                                'displayWords' => true,
                            ],
                        ],
                    ],
                ],
            ],
            'Configure WordCount like CKEditor5' => [
                [
                    'editor' => [
                        'config' => [
                            'wordCount' => [
                                'displayCharacters' => false,
                                'displayWords' => false,
                            ],
                        ],
                    ],
                ],
                [
                    'editor' => [
                        'config' => [
                            'removePlugins' => [],
                            'toolbar' => [
                                'items' => [
                                    'softhyphen',
                                ],
                                'removeItems' => [],
                                'shouldNotGroupWhenFull' => true,
                            ],
                            'alignment' => [
                                'options' => [
                                    ['name' => 'left', 'className' => 'text-start'],
                                    ['name' => 'center', 'className' => 'text-center'],
                                    ['name' => 'right', 'className' => 'text-end'],
                                    ['name' => 'justify', 'className' => 'text-justify'],
                                ],
                            ],
                            'wordCount' => [
                                'displayCharacters' => false,
                                'displayWords' => false,
                            ],
                        ],
                    ],
                ],
            ],
            'Configure WordCount like CKEditor5 Partially' => [
                [
                    'editor' => [
                        'config' => [
                            'wordCount' => [
                                'displayCharacters' => false,
                            ],
                        ],
                    ],
                ],
                [
                    'editor' => [
                        'config' => [
                            'removePlugins' => [],
                            'toolbar' => [
                                'items' => [
                                    'softhyphen',
                                ],
                                'removeItems' => [],
                                'shouldNotGroupWhenFull' => true,
                            ],
                            'alignment' => [
                                'options' => [
                                    ['name' => 'left', 'className' => 'text-start'],
                                    ['name' => 'center', 'className' => 'text-center'],
                                    ['name' => 'right', 'className' => 'text-end'],
                                    ['name' => 'justify', 'className' => 'text-justify'],
                                ],
                            ],
                            'wordCount' => [
                                'displayCharacters' => false,
                                'displayWords' => true,
                            ],
                        ],
                    ],
                ],
            ],

            // Add link classes to ckeditor styles
            'Add missing link classes to ckeditor styles' => [
                [
                    'editor' => [
                        'config' => [
                            'style' => [
                                'definitions' => [
                                    [
                                        'classes' => ['link-arrow'],
                                        'element' => 'a',
                                        'name' => 'Link Arrow',
                                    ],
                                    [
                                        'classes' => ['class-karl'],
                                        'element' => 'p',
                                        'name' => 'Class Karl',
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'buttons' => [
                        'link' => [
                            'properties' => [
                                'class' => [
                                    'allowedClasses' => 'link-arrow, link-chevron, class-karl',
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'editor' => [
                        'config' => [
                            'style' => [
                                'definitions' => [
                                    [
                                        'classes' => ['link-arrow'],
                                        'element' => 'a',
                                        'name' => 'Link Arrow',
                                    ],
                                    [
                                        'classes' => ['link-chevron'],
                                        'element' => 'a',
                                        'name' => 'link-chevron',
                                    ],
                                    [
                                        'classes' => ['class-karl'],
                                        'element' => 'a',
                                        'name' => 'class-karl',
                                    ],
                                    [
                                        'classes' => ['class-karl'],
                                        'element' => 'p',
                                        'name' => 'Class Karl',
                                    ],
                                ],
                            ],
                            'removePlugins' => [],
                            'toolbar' => [
                                'items' => [
                                    'softhyphen',
                                ],
                                'removeItems' => [],
                                'shouldNotGroupWhenFull' => true,
                            ],
                            'alignment' => [
                                'options' => [
                                    ['name' => 'left', 'className' => 'text-start'],
                                    ['name' => 'center', 'className' => 'text-center'],
                                    ['name' => 'right', 'className' => 'text-end'],
                                    ['name' => 'justify', 'className' => 'text-justify'],
                                ],
                            ],
                            'wordCount' => [
                                'displayCharacters' => true,
                                'displayWords' => true,
                            ],
                        ],
                    ],
                    'buttons' => [
                        'link' => [
                            'properties' => [
                                'class' => [
                                    'allowedClasses' => 'link-arrow, link-chevron, class-karl',
                                ],
                            ],
                        ],
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
