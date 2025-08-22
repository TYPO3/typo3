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

namespace TYPO3\CMS\RteCKEditor\Tests\Unit\Configuration;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\RteCKEditor\Configuration\CKEditor5Migrator;
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
                            'toolbar' => [
                                'items' => [],
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

            // Extra Plugins. Configured as array
            'Extra plugins mapping (array)' => [
                [
                    'editor' => [
                        'config' => [
                            'extraPlugins' => ['image', 'whitespace'],
                        ],
                    ],
                ],
                [
                    'editor' => [
                        'config' => [
                            'toolbar' => [
                                'items' => [],
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
                            'importModules' => [
                                [
                                    'module' => '@ckeditor/ckeditor5-image',
                                    'exports' => [ 'Image', 'ImageCaption', 'ImageStyle', 'ImageToolbar', 'ImageUpload', 'PictureEditing' ],
                                ],
                                [
                                    'module' => '@typo3/rte-ckeditor/plugin/whitespace.js',
                                    'exports' => [ 'Whitespace' ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],

            // Extra Plugins. Configured as string
            'Extra plugins mapping (string)' => [
                [
                    'editor' => [
                        'config' => [
                            'extraPlugins' => 'image,whitespace',
                        ],
                    ],
                ],
                [
                    'editor' => [
                        'config' => [
                            'toolbar' => [
                                'items' => [],
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
                            'importModules' => [
                                [
                                    'module' => '@ckeditor/ckeditor5-image',
                                    'exports' => [ 'Image', 'ImageCaption', 'ImageStyle', 'ImageToolbar', 'ImageUpload', 'PictureEditing' ],
                                ],
                                [
                                    'module' => '@typo3/rte-ckeditor/plugin/whitespace.js',
                                    'exports' => [ 'Whitespace' ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],

            // Remove Plugins. Configured as array
            'Remove plugins mapping (array)' => [
                [
                    'editor' => [
                        'config' => [
                            'removePlugins' => ['image', 'whitespace'],
                        ],
                    ],
                ],
                [
                    'editor' => [
                        'config' => [
                            'removeImportModules' => [
                                [
                                    'module' => '@ckeditor/ckeditor5-image',
                                    'exports' => [ 'Image', 'ImageCaption', 'ImageStyle', 'ImageToolbar', 'ImageUpload', 'PictureEditing' ],
                                ],
                                [
                                    'module' => '@typo3/rte-ckeditor/plugin/whitespace.js',
                                    'exports' => [ 'Whitespace' ],
                                ],
                            ],
                            'toolbar' => [
                                'items' => [],
                                'removeItems' => [ 'softhyphen' ],
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

            // Remove Plugins. Configured as string
            'Remove plugins mapping (string)' => [
                [
                    'editor' => [
                        'config' => [
                            'removePlugins' => 'image, whitespace',
                        ],
                    ],
                ],
                [
                    'editor' => [
                        'config' => [
                            'removeImportModules' => [
                                [
                                    'module' => '@ckeditor/ckeditor5-image',
                                    'exports' => [ 'Image', 'ImageCaption', 'ImageStyle', 'ImageToolbar', 'ImageUpload', 'PictureEditing' ],
                                ],
                                [
                                    'module' => '@typo3/rte-ckeditor/plugin/whitespace.js',
                                    'exports' => [ 'Whitespace' ],
                                ],
                            ],
                            'toolbar' => [
                                'items' => [],
                                'removeItems' => [ 'softhyphen' ],
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

            // Remove Plugins, preserving array-list structure
            'Remove plugins mapping preserves array-list structure' => [
                [
                    'editor' => [
                        'config' => [
                            'removePlugins' => [
                                'image',
                                'foobar',
                            ],
                        ],
                    ],
                ],
                [
                    'editor' => [
                        'config' => [
                            'removePlugins' => [
                                'foobar',
                            ],
                            'removeImportModules' => [
                                [
                                    'module' => '@ckeditor/ckeditor5-image',
                                    'exports' => [ 'Image', 'ImageCaption', 'ImageStyle', 'ImageToolbar', 'ImageUpload', 'PictureEditing' ],
                                ],
                            ],
                            'toolbar' => [
                                'items' => [],
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
            'CKEditor5 toolbar.removeItems and CKEditor4 removeButtons' => [
                [
                    'editor' => [
                        'config' => [
                            'toolbar' => [
                                'items' => [
                                    'bold',
                                ],
                                'removeItems' => [
                                    'subscript',
                                    'underline',
                                ],
                            ],
                            'removeButtons' => [
                                'Superscript',
                                'Strike',
                            ],
                        ],
                    ],
                ],
                [
                    'editor' => [
                        'config' => [
                            'toolbar' => [
                                'items' => [
                                    'bold',
                                ],
                                'removeItems' => [
                                    'subscript',
                                    'underline',
                                    'superscript',
                                    'strikethrough',
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
            'CKEditor5 shouldNotGroupWhenFull' => [
                [
                    'editor' => [
                        'config' => [
                            'toolbar' => [
                                'shouldNotGroupWhenFull' => false,
                            ],
                        ],
                    ],
                ],
                [
                    'editor' => [
                        'config' => [
                            'toolbar' => [
                                'items' => [],
                                'removeItems' => [],
                                'shouldNotGroupWhenFull' => false,
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
                            'toolbar' => [
                                'items' => [
                                    'style',
                                    'heading',
                                    'fontFamily',
                                    'fontSize',
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
                                    'fontFamily',
                                    'fontSize',
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
                            'toolbar' => [
                                'items' => [],
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
                            'toolbar' => [
                                'items' => [],
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
                            'toolbar' => [
                                'items' => [],
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

            // typo3link.additionalAttributes
            'Migrate typo3link.additionalAttributes to config.htmlSupport' => [
                [
                    'editor' => [
                        'config' => [
                            'typo3link' => [
                                'additionalAttributes' => [
                                    'foo',
                                    'bar',
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'editor' => [
                        'config' => [
                            'htmlSupport' => [
                                'allow' => [
                                    [
                                        'name' => 'a',
                                        'attributes' => [
                                            'foo',
                                            'bar',
                                        ],
                                    ],
                                ],
                            ],
                            'toolbar' => [
                                'items' => [],
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
            'Unset empty typo3link.additionalAttributes' => [
                [
                    'editor' => [
                        'config' => [
                            'typo3link' => [
                                'additionalAttributes' => [],
                            ],
                        ],
                    ],
                ],
                [
                    'editor' => [
                        'config' => [
                            'toolbar' => [
                                'items' => [],
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
            'Unset invalid values in typo3link.additionalAttributes' => [
                [
                    'editor' => [
                        'config' => [
                            'typo3link' => [
                                'additionalAttributes' => 'invalid string value',
                            ],
                        ],
                    ],
                ],
                [
                    'editor' => [
                        'config' => [
                            'toolbar' => [
                                'items' => [],
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
            // {extraA,disa,a}allowedContent
            'Migrate {extraA,disa,a}allowedContent from string representation to config.htmlSupport' => [
                [
                    'editor' => [
                        'config' => [
                            'extraAllowedContent' => 'div[aria-label,data-*];a(classname)',
                            'allowedContent' => '*[*](*){*}',
                            'disallowedContent' => 'strong underline;em[*](*){*}',
                        ],
                    ],
                ],
                [
                    'editor' => [
                        'config' => [
                            'htmlSupport' => [
                                'allow' => [
                                    [
                                        'name' => [
                                            'pattern' => '^[a-z]+$',
                                        ],
                                        'classes' => true,
                                        'attributes' => true,
                                        'styles' => true,
                                    ],
                                    [
                                        'name' => 'div',
                                        'attributes' => [
                                            'aria-label',
                                            [
                                                'pattern' => 'data-.+',
                                            ],
                                        ],
                                    ],
                                    [
                                        'name' => 'a',
                                        'classes' => [
                                            'classname',
                                        ],
                                    ],
                                ],
                                'disallow' => [
                                    [
                                        'name' => [
                                            'pattern' => 'strong|underline',
                                        ],
                                    ],
                                    [
                                        'name' => 'em',
                                        'classes' => true,
                                        'attributes' => true,
                                        'styles' => true,
                                    ],
                                ],
                            ],
                            'toolbar' => [
                                'items' => [],
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
            // {extraA,disa,a}allowedContent
            'Migrate {extraA,disa,a}allowedContent from object representation to config.htmlSupport' => [
                [
                    'editor' => [
                        'config' => [
                            'extraAllowedContent' => [
                                'div' => [
                                    'attributes' => 'aria-label,data-*',
                                ],
                                'a' => [
                                    'classes' => 'classname',
                                ],
                            ],
                            'allowedContent' => [
                                '*' => [
                                    'styles' => '*',
                                    'classes' => '*',
                                    'attributes' => '*',
                                ],
                            ],
                            'disallowedContent' => [
                                'strong underline' => true,
                                'em' => [
                                    'styles' => '*',
                                    'classes' => '*',
                                    'attributes' => '*',
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'editor' => [
                        'config' => [
                            'htmlSupport' => [
                                'allow' => [
                                    [
                                        'name' => [
                                            'pattern' => '^[a-z]+$',
                                        ],
                                        'classes' => true,
                                        'attributes' => true,
                                        'styles' => true,
                                    ],
                                    [
                                        'name' => 'div',
                                        'attributes' => [
                                            'aria-label',
                                            [
                                                'pattern' => 'data-.+',
                                            ],
                                        ],
                                    ],
                                    [
                                        'name' => 'a',
                                        'classes' => [
                                            'classname',
                                        ],
                                    ],
                                ],
                                'disallow' => [
                                    [
                                        'name' => [
                                            'pattern' => 'strong|underline',
                                        ],
                                    ],
                                    [
                                        'name' => 'em',
                                        'classes' => true,
                                        'attributes' => true,
                                        'styles' => true,
                                    ],
                                ],
                            ],
                            'toolbar' => [
                                'items' => [],
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
                            'removeImportModules' => [
                                [
                                    'module' => '@ckeditor/ckeditor5-alignment',
                                    'exports' => [ 'Alignment' ],
                                ],
                            ],
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
                            'toolbar' => [
                                'items' => [],
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
                            'toolbar' => [
                                'items' => [],
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
                                'Whitespace',
                            ],
                        ],
                    ],
                ],
                [
                    'editor' => [
                        'config' => [
                            'removeImportModules' => [
                                [
                                    'module' =>  '@typo3/rte-ckeditor/plugin/whitespace.js',
                                    'exports' => [ 'Whitespace' ],
                                ],
                            ],
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
                            'removeImportModules' => [
                                [
                                    'module' =>  '@typo3/rte-ckeditor/plugin/whitespace.js',
                                    'exports' => [ 'Whitespace' ],
                                ],
                            ],
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
                            'removeImportModules' => [
                                [
                                    'module' => '@ckeditor/ckeditor5-word-count',
                                    'exports' => [ 'WordCount' ],
                                ],
                            ],
                            'toolbar' => [
                                'items' => [],
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
                            'toolbar' => [
                                'items' => [],
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
                            'toolbar' => [
                                'items' => [],
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
                            'toolbar' => [
                                'items' => [],
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
                            'toolbar' => [
                                'items' => [],
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
                                    'allowedClasses' => 'link-arrow, btn btn-default, link-chevron, class-karl',
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
                                        'classes' => ['btn', 'btn-default'],
                                        'element' => 'a',
                                        'name' => 'btn btn-default',
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
                            'toolbar' => [
                                'items' => [],
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
                                    'allowedClasses' => 'link-arrow, btn btn-default, link-chevron, class-karl',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    #[DataProvider('migrationDataProvider')]
    #[Test]
    public function migrationTests(array $configuration, array $expectation): void
    {
        self::assertEquals($expectation, (new CKEditor5Migrator($configuration))->get());
    }
}
