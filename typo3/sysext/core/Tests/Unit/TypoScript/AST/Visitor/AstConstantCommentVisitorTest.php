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

namespace TYPO3\CMS\Core\Tests\Unit\TypoScript\AST\Visitor;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\EventDispatcher\NoopEventDispatcher;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\TypoScript\AST\CommentAwareAstBuilder;
use TYPO3\CMS\Core\TypoScript\AST\Node\RootNode;
use TYPO3\CMS\Core\TypoScript\AST\Traverser\AstTraverser;
use TYPO3\CMS\Core\TypoScript\AST\Visitor\AstConstantCommentVisitor;
use TYPO3\CMS\Core\TypoScript\Tokenizer\LosslessTokenizer;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class AstConstantCommentVisitorTest extends UnitTestCase
{
    /**
     * This is an unfortunate setup - we cannot easily build a distinct generated
     * data provider here, because the structure depends on the whole context, and
     * we cannot easily only create a singular structure. Plus, managing these
     * arrays manually is cumbersome. Also, we don't wand to parse the whole AST
     * tree for every single expectation due to performance reasons.
     * TL;DR: This needs to suffice, for now.
     */
    private function getAssertionStructure(): array
    {
        return [
            'string_1' =>
            [
                'cat'                   => 'text',
                'subcat_name'           => '200',
                'subcat_label'          => '',
                'subcat_sorting_first'  => 7,
                'subcat_sorting_second' => '10z',
                'type'                  => 'string',
                'label'                 => '',
                'description'           => '',
                'name'                  => 'string_1',
                'idName'                => 'string_1',
                'value'                 => 'value',
                'default_value'         => 'value',
                'isInCurrentTemplate'   => false,
            ],
            'string_2' =>
            [
                'cat'                   => 'text',
                'subcat_name'           => '200',
                'subcat_label'          => '',
                'subcat_sorting_first'  => 7,
                'subcat_sorting_second' => '11z',
                'type'                  => 'string',
                'label'                 => '',
                'description'           => '',
                'name'                  => 'string_2',
                'idName'                => 'string_2',
                'value'                 => '',
                'default_value'         => '',
                'isInCurrentTemplate'   => false,
            ],
            'small_1' =>
            [
                'cat'                   => 'text',
                'subcat_name'           => '201',
                'subcat_label'          => '',
                'subcat_sorting_first'  => 8,
                'subcat_sorting_second' => '10z',
                'type'                  => 'string',
                'label'                 => '',
                'description'           => '',
                'name'                  => 'small_1',
                'idName'                => 'small_1',
                'value'                 => 'value',
                'default_value'         => 'value',
                'isInCurrentTemplate'   => false,
            ],
            'small_2' =>
            [
                'cat'                   => 'text',
                'subcat_name'           => '201',
                'subcat_label'          => '',
                'subcat_sorting_first'  => 8,
                'subcat_sorting_second' => '11z',
                'type'                  => 'string',
                'label'                 => '',
                'description'           => '',
                'name'                  => 'small_2',
                'idName'                => 'small_2',
                'value'                 => '',
                'default_value'         => '',
                'isInCurrentTemplate'   => false,
            ],
            'wrap_1' =>
            [
                'cat'                   => 'text',
                'subcat_name'           => '202',
                'subcat_label'          => '',
                'subcat_sorting_first'  => 9,
                'subcat_sorting_second' => '10z',
                'type'                  => 'wrap',
                'wrapStart'             => 'value',
                'wrapEnd'               => '',
                'label'                 => '',
                'description'           => '',
                'name'                  => 'wrap_1',
                'idName'                => 'wrap_1',
                'value'                 => 'value',
                'default_value'         => 'value',
                'isInCurrentTemplate'   => false,
            ],
            'wrap_2' =>
            [
                'cat'                   => 'text',
                'subcat_name'           => '202',
                'subcat_label'          => '',
                'subcat_sorting_first'  => 9,
                'subcat_sorting_second' => '11z',
                'type'                  => 'wrap',
                'wrapStart'             => '',
                'wrapEnd'               => '',
                'label'                 => '',
                'description'           => '',
                'name'                  => 'wrap_2',
                'idName'                => 'wrap_2',
                'value'                 => '',
                'default_value'         => '',
                'isInCurrentTemplate'   => false,
            ],
            'color_1' =>
            [
                'cat'                   => 'color',
                'subcat_name'           => '400',
                'subcat_label'          => '',
                'subcat_sorting_first'  => 11,
                'subcat_sorting_second' => '10z',
                'type'                  => 'color',
                'label'                 => '',
                'description'           => '',
                'name'                  => 'color_1',
                'idName'                => 'color_1',
                'value'                 => 'black',
                'default_value'         => 'black',
                'isInCurrentTemplate'   => false,
            ],
            'color_2' =>
            [
                'cat'                   => 'color',
                'subcat_name'           => '400',
                'subcat_label'          => '',
                'subcat_sorting_first'  => 11,
                'subcat_sorting_second' => '11z',
                'type'                  => 'color',
                'label'                 => '',
                'description'           => '',
                'name'                  => 'color_2',
                'idName'                => 'color_2',
                'value'                 => '#000000',
                'default_value'         => '#000000',
                'isInCurrentTemplate'   => false,
            ],
            'color_3' =>
            [
                'cat'                   => 'color',
                'subcat_name'           => '400',
                'subcat_label'          => '',
                'subcat_sorting_first'  => 11,
                'subcat_sorting_second' => '12z',
                'type'                  => 'color',
                'label'                 => '',
                'description'           => '',
                'name'                  => 'color_3',
                'idName'                => 'color_3',
                'value'                 => '000000',
                'default_value'         => '000000',
                'isInCurrentTemplate'   => false,
            ],
            'color_4' =>
            [
                'cat'                   => 'color',
                'subcat_name'           => '400',
                'subcat_label'          => '',
                'subcat_sorting_first'  => 11,
                'subcat_sorting_second' => '13z',
                'type'                  => 'color',
                'label'                 => '',
                'description'           => '',
                'name'                  => 'color_4',
                'idName'                => 'color_4',
                'value'                 => '',
                'default_value'         => '',
                'isInCurrentTemplate'   => false,
            ],
            'offset_1' =>
            [
                'cat'                   => 'offset',
                'subcat_name'           => '300',
                'subcat_label'          => '',
                'subcat_sorting_first'  => 10,
                'subcat_sorting_second' => '10z',
                'type'                  => 'offset',
                'labelValueArray'       => [
                    0 => [
                        'label' => 'x',
                        'value' => 'x',
                    ],
                    1 => [
                        'label' => 'y',
                        'value' => 'y',
                    ],
                ],
                'label'                 => '',
                'description'           => '',
                'name'                  => 'offset_1',
                'idName'                => 'offset_1',
                'value'                 => 'x,y',
                'default_value'         => 'x,y',
                'isInCurrentTemplate'   => false,
            ],
            'offset_2' =>
            [
                'cat'                   => 'offset',
                'subcat_name'           => '300',
                'subcat_label'          => '',
                'subcat_sorting_first'  => 10,
                'subcat_sorting_second' => '11z',
                'type'                  => 'offset',
                'labelValueArray'       => [
                    0 => [
                        'label' => 'x',
                        'value' => 'x',
                    ],
                    1 => [
                        'label' => 'y',
                        'value' => '',
                    ],
                ],
                'label'                 => '',
                'description'           => '',
                'name'                  => 'offset_2',
                'idName'                => 'offset_2',
                'value'                 => 'x',
                'default_value'         => 'x',
                'isInCurrentTemplate'   => false,
            ],
            'offset_3' =>
            [
                'cat'                   => 'offset',
                'subcat_name'           => '300',
                'subcat_label'          => '',
                'subcat_sorting_first'  => 10,
                'subcat_sorting_second' => '12z',
                'type'                  => 'offset',
                'labelValueArray'       => [
                    0 => [
                        'label' => 'x',
                        'value' => '',
                    ],
                    1 => [
                        'label' => 'y',
                        'value' => 'y',
                    ],
                ],
                'label'                 => '',
                'description'           => '',
                'name'                  => 'offset_3',
                'idName'                => 'offset_3',
                'value'                 => ',y',
                'default_value'         => ',y',
                'isInCurrentTemplate'   => false,
            ],
            'offset_4' =>
            [
                'cat'                   => 'offset',
                'subcat_name'           => '300',
                'subcat_label'          => '',
                'subcat_sorting_first'  => 10,
                'subcat_sorting_second' => '13z',
                'type'                  => 'offset',
                'labelValueArray'       => [
                    0 => [
                        'label' => 'x',
                        'value' => '',
                    ],
                    1 => [
                        'label' => 'y',
                        'value' => '',
                    ],
                ],
                'label'                 => '',
                'description'           => '',
                'name'                  => 'offset_4',
                'idName'                => 'offset_4',
                'value'                 => '',
                'default_value'         => '',
                'isInCurrentTemplate'   => false,
            ],
            'user_1' =>
            [
                'cat'                   => 'user',
                'subcat_name'           => '500',
                'subcat_label'          => '',
                'subcat_sorting_first'  => 12,
                'subcat_sorting_second' => '10z',
                'type'                  => 'user',
                'html'                  => '<pre>array(2) {
  ["fieldName"]=>
  string(6) "user_1"
  ["fieldValue"]=>
  string(1) "0"
}
</pre>',
                'label'                 => '',
                'description'           => '',
                'name'                  => 'user_1',
                'idName'                => 'user_1',
                'value'                 => '0',
                'default_value'         => '0',
                'isInCurrentTemplate'   => false,
            ],
            'options_1' =>
            [
                'cat'                   => 'options',
                'subcat_name'           => '600',
                'subcat_label'          => '',
                'subcat_sorting_first'  => 13,
                'subcat_sorting_second' => '10z',
                'type'                  => 'options',
                'labelValueArray'       => [
                    0 => [
                        'label'    => '',
                        'value'    => 'default',
                        'selected' => true,
                    ],
                    1 => [
                        'label'    => '',
                        'value'    => 'option_1',
                        'selected' => false,
                    ],
                    2 => [
                        'label'    => '',
                        'value'    => 'option_2',
                        'selected' => false,
                    ],
                    3 => [
                        'label'    => '',
                        'value'    => 'option_3',
                        'selected' => false,
                    ],
                ],
                'label'                 => '',
                'description'           => '',
                'name'                  => 'options_1',
                'idName'                => 'options_1',
                'value'                 => 'default',
                'default_value'         => 'default',
                'isInCurrentTemplate'   => false,
            ],
            'options_2' =>
            [
                'cat'                   => 'options',
                'subcat_name'           => '600',
                'subcat_label'          => '',
                'subcat_sorting_first'  => 13,
                'subcat_sorting_second' => '11z',
                'type'                  => 'options',
                'labelValueArray'       => [
                    0 => [
                        'label'    => '',
                        'value'    => 'default',
                        'selected' => false,
                    ],
                    1 => [
                        'label'    => '',
                        'value'    => 'option_1',
                        'selected' => false,
                    ],
                    2 => [
                        'label'    => '',
                        'value'    => 'option_2',
                        'selected' => true,
                    ],
                    3 => [
                        'label'    => '',
                        'value'    => 'option_3',
                        'selected' => false,
                    ],
                ],
                'label'                 => '',
                'description'           => '',
                'name'                  => 'options_2',
                'idName'                => 'options_2',
                'value'                 => 'option_2',
                'default_value'         => 'option_2',
                'isInCurrentTemplate'   => false,
            ],
            'options_3' =>
            [
                'cat'                   => 'options',
                'subcat_name'           => '600',
                'subcat_label'          => '',
                'subcat_sorting_first'  => 13,
                'subcat_sorting_second' => '11z',
                'type'                  => 'options',
                'labelValueArray'       => [
                    0 => [
                        'label'    => '',
                        'value'    => 'default',
                        'selected' => false,
                    ],
                    1 => [
                        'label'    => '',
                        'value'    => 'option_1',
                        'selected' => false,
                    ],
                    2 => [
                        'label'    => '',
                        'value'    => 'option_2',
                        'selected' => false,
                    ],
                    3 => [
                        'label'    => '',
                        'value'    => 'option_3',
                        'selected' => false,
                    ],
                ],
                'label'                 => '',
                'description'           => '',
                'name'                  => 'options_3',
                'idName'                => 'options_3',
                'value'                 => '',
                'default_value'         => '',
                'isInCurrentTemplate'   => false,
            ],
            'boolean_1' =>
            [
                'cat'                   => 'boolean',
                'subcat_name'           => '100',
                'subcat_label'          => '',
                'subcat_sorting_first'  => 4,
                'subcat_sorting_second' => '10z',
                'type'                  => 'boolean',
                'trueValue'             => '1',
                'label'                 => '',
                'description'           => '',
                'name'                  => 'boolean_1',
                'idName'                => 'boolean_1',
                'value'                 => '0',
                'default_value'         => '0',
                'isInCurrentTemplate'   => false,
            ],
            'boolean_2' =>
            [
                'cat'                   => 'boolean',
                'subcat_name'           => '100',
                'subcat_label'          => '',
                'subcat_sorting_first'  => 4,
                'subcat_sorting_second' => '11z',
                'type'                  => 'boolean',
                'trueValue'             => '1',
                'label'                 => '',
                'description'           => '',
                'name'                  => 'boolean_2',
                'idName'                => 'boolean_2',
                'value'                 => '1',
                'default_value'         => '1',
                'isInCurrentTemplate'   => false,
            ],
            'boolean_3' =>
            [
                'cat'                   => 'boolean',
                'subcat_name'           => '100',
                'subcat_label'          => '',
                'subcat_sorting_first'  => 4,
                'subcat_sorting_second' => '12z',
                'type'                  => 'boolean',
                'trueValue'             => '1',
                'label'                 => '',
                'description'           => '',
                'name'                  => 'boolean_3',
                'idName'                => 'boolean_3',
                'value'                 => '',
                'default_value'         => '',
                'isInCurrentTemplate'   => false,
            ],
            'boolean_4' =>
            [
                'cat'                   => 'boolean',
                'subcat_name'           => '100',
                'subcat_label'          => '',
                'subcat_sorting_first'  => 4,
                'subcat_sorting_second' => '13z',
                'type'                  => 'boolean',
                'trueValue'             => 'myTrueValue',
                'label'                 => '',
                'description'           => '',
                'name'                  => 'boolean_4',
                'idName'                => 'boolean_4',
                'value'                 => '0',
                'default_value'         => '0',
                'isInCurrentTemplate'   => false,
            ],
            'int_1' =>
            [
                'cat'                   => 'integer',
                'subcat_name'           => '101',
                'subcat_label'          => '',
                'subcat_sorting_first'  => 5,
                'subcat_sorting_second' => '10z',
                'type'                  => 'int',
                'label'                 => '',
                'description'           => '',
                'name'                  => 'int_1',
                'idName'                => 'int_1',
                'value'                 => '1',
                'default_value'         => '1',
                'isInCurrentTemplate'   => false,
            ],
            'int_2' =>
            [
                'cat'                   => 'integer',
                'subcat_name'           => '101',
                'subcat_label'          => '',
                'subcat_sorting_first'  => 5,
                'subcat_sorting_second' => '11z',
                'type'                  => 'int',
                'label'                 => '',
                'description'           => '',
                'name'                  => 'int_2',
                'idName'                => 'int_2',
                'value'                 => '',
                'default_value'         => '',
                'isInCurrentTemplate'   => false,
            ],
            'int_3' =>
            [
                'cat'                   => 'integer',
                'subcat_name'           => '101',
                'subcat_label'          => '',
                'subcat_sorting_first'  => 5,
                'subcat_sorting_second' => '12z',
                'type'                  => 'int',
                'label'                 => '',
                'description'           => '',
                'name'                  => 'int_3',
                'idName'                => 'int_3',
                'value'                 => '-100',
                'default_value'         => '-100',
                'isInCurrentTemplate'   => false,
            ],
            'int_4' =>
            [
                'cat'                   => 'integer',
                'subcat_name'           => '101',
                'subcat_label'          => '',
                'subcat_sorting_first'  => 5,
                'subcat_sorting_second' => '13z',
                'type'                  => 'int',
                'typeIntMin'            => '1',
                'typeHint'              => 'Range 1 - 5',
                'typeIntMax'            => '5',
                'label'                 => '',
                'description'           => '',
                'name'                  => 'int_4',
                'idName'                => 'int_4',
                'value'                 => '2',
                'default_value'         => '2',
                'isInCurrentTemplate'   => false,
            ],
            'int_5' =>
            [
                'cat'                   => 'integer',
                'subcat_name'           => '101',
                'subcat_label'          => '',
                'subcat_sorting_first'  => 5,
                'subcat_sorting_second' => '14z',
                'type'                  => 'int',
                'typeIntMin'            => '-5',
                'typeHint'              => 'Range -5 - 5',
                'typeIntMax'            => '5',
                'label'                 => '',
                'description'           => '',
                'name'                  => 'int_5',
                'idName'                => 'int_5',
                'value'                 => '2',
                'default_value'         => '2',
                'isInCurrentTemplate'   => false,
            ],
            'int_6' =>
            [
                'cat'                   => 'integer',
                'subcat_name'           => '101',
                'subcat_label'          => '',
                'subcat_sorting_first'  => 5,
                'subcat_sorting_second' => '15z',
                'type'                  => 'int',
                'typeIntMin'            => '-5',
                'typeHint'              => 'Range -5 - -1',
                'typeIntMax'            => '-1',
                'label'                 => '',
                'description'           => '',
                'name'                  => 'int_6',
                'idName'                => 'int_6',
                'value'                 => '2',
                'default_value'         => '2',
                'isInCurrentTemplate'   => false,
            ],
            'int_7' =>
            [
                'cat'                   => 'integer',
                'subcat_name'           => '101',
                'subcat_label'          => '',
                'subcat_sorting_first'  => 5,
                'subcat_sorting_second' => '16z',
                'type'                  => 'int',
                'typeIntMin'            => '1',
                'typeHint'              => 'Range 1 - -5',
                'typeIntMax'            => '-5',
                'label'                 => '',
                'description'           => '',
                'name'                  => 'int_7',
                'idName'                => 'int_7',
                'value'                 => '2',
                'default_value'         => '2',
                'isInCurrentTemplate'   => false,
            ],
            'int_8' =>
            [
                'cat'                   => 'integer',
                'subcat_name'           => '101',
                'subcat_label'          => '',
                'subcat_sorting_first'  => 5,
                'subcat_sorting_second' => '17z',
                'type'                  => 'int',
                'typeIntMax'            => '5',
                'typeHint'              => 'Range  - 5',
                'label'                 => '',
                'description'           => '',
                'name'                  => 'int_8',
                'idName'                => 'int_8',
                'value'                 => '2',
                'default_value'         => '2',
                'isInCurrentTemplate'   => false,
            ],
            'intplus_1' =>
            [
                'cat'                   => 'integer',
                'subcat_name'           => '102',
                'subcat_label'          => '',
                'subcat_sorting_first'  => 6,
                'subcat_sorting_second' => '10z',
                'type'                  => 'int+',
                'typeIntPlusMin'        => 0,
                'label'                 => '',
                'description'           => '',
                'name'                  => 'intplus_1',
                'idName'                => 'intplus_1',
                'value'                 => '1',
                'default_value'         => '1',
                'isInCurrentTemplate'   => false,
            ],
            'intplus_2' =>
            [
                'cat'                   => 'integer',
                'subcat_name'           => '102',
                'subcat_label'          => '',
                'subcat_sorting_first'  => 6,
                'subcat_sorting_second' => '11z',
                'type'                  => 'int+',
                'typeIntPlusMin'        => 0,
                'label'                 => '',
                'description'           => '',
                'name'                  => 'intplus_2',
                'idName'                => 'intplus_2',
                'value'                 => '',
                'default_value'         => '',
                'isInCurrentTemplate'   => false,
            ],
            'intplus_3' =>
            [
                'cat'                   => 'integer',
                'subcat_name'           => '101',
                'subcat_label'          => '',
                'subcat_sorting_first'  => 5,
                'subcat_sorting_second' => '12z',
                'type'                  => 'int+',
                'typeIntPlusMin'        => 1,
                'typeHint'              => 'Range 1 - 5',
                'typeIntPlusMax'        => 5,
                'label'                 => '',
                'description'           => '',
                'name'                  => 'intplus_3',
                'idName'                => 'intplus_3',
                'value'                 => '2',
                'default_value'         => '2',
                'isInCurrentTemplate'   => false,
            ],
            'compat_input_1' =>
            [
                'cat'                   => 'compatibility',
                'subcat_name'           => '900',
                'subcat_label'          => '',
                'subcat_sorting_first'  => 14,
                'subcat_sorting_second' => '10z',
                'type'                  => 'string',
                'label'                 => '',
                'description'           => '',
                'name'                  => 'compat_input_1',
                'idName'                => 'compat_input_1',
                'value'                 => 'value',
                'default_value'         => 'value',
                'isInCurrentTemplate'   => false,
            ],
            'compat_input_2' =>
            [
                'cat'                   => 'compatibility',
                'subcat_name'           => '900',
                'subcat_label'          => '',
                'subcat_sorting_first'  => 14,
                'subcat_sorting_second' => '11z',
                'type'                  => 'string',
                'label'                 => '',
                'description'           => '',
                'name'                  => 'compat_input_2',
                'idName'                => 'compat_input_2',
                'value'                 => '',
                'default_value'         => '',
                'isInCurrentTemplate'   => false,
            ],
            'compat_default_1' =>
            [
                'cat'                   => 'compatibility',
                'subcat_name'           => '901',
                'subcat_label'          => '',
                'subcat_sorting_first'  => 15,
                'subcat_sorting_second' => '10z',
                'type'                  => 'string',
                'label'                 => '',
                'description'           => '',
                'name'                  => 'compat_default_1',
                'idName'                => 'compat_default_1',
                'value'                 => 'value',
                'default_value'         => 'value',
                'isInCurrentTemplate'   => false,
            ],
            'compat_default_2' =>
            [
                'cat'                   => 'compatibility',
                'subcat_name'           => '901',
                'subcat_label'          => '',
                'subcat_sorting_first'  => 15,
                'subcat_sorting_second' => '11z',
                'type'                  => 'string',
                'label'                 => '',
                'description'           => '',
                'name'                  => 'compat_default_2',
                'idName'                => 'compat_default_2',
                'value'                 => '',
                'default_value'         => '',
                'isInCurrentTemplate'   => false,
            ],
            'zeroorder_input_1' =>
            [
                'cat'                   => 'zeroindex',
                'subcat_name'           => 'other',
                'subcat_label'          => 'Other',
                'subcat_sorting_first'  => 'o',
                'subcat_sorting_second' => '0z',
                'type'                  => 'string',
                'label'                 => '',
                'description'           => '',
                'name'                  => 'zeroorder_input_1',
                'idName'                => 'zeroorder_input_1',
                'value'                 => 'value',
                'default_value'         => 'value',
                'isInCurrentTemplate'   => false,
            ],
            'zeroorder_input_3' =>
            [
                'cat'                   => 'zeroindex',
                'subcat_name'           => 'other',
                'subcat_label'          => 'Other',
                'subcat_sorting_first'  => 'o',
                'subcat_sorting_second' => '2z',
                'type'                  => 'string',
                'label'                 => '',
                'description'           => '',
                'name'                  => 'zeroorder_input_3',
                'idName'                => 'zeroorder_input_3',
                'value'                 => '',
                'default_value'         => '',
                'isInCurrentTemplate'   => false,
            ],
            'zeroorder_input_2' =>
            [
                'cat'                   => 'zeroindex',
                'subcat_name'           => 'other',
                'subcat_label'          => 'Other',
                'subcat_sorting_first'  => 'o',
                'subcat_sorting_second' => '1z',
                'type'                  => 'string',
                'label'                 => '',
                'description'           => '',
                'name'                  => 'zeroorder_input_2',
                'idName'                => 'zeroorder_input_2',
                'value'                 => '',
                'default_value'         => '',
                'isInCurrentTemplate'   => false,
            ],
            'nested.input_1' =>
            [
                'cat'                   => 'nested',
                'subcat_name'           => 'other',
                'subcat_label'          => 'Other',
                'subcat_sorting_first'  => 'o',
                'subcat_sorting_second' => '89z',
                'type'                  => 'string',
                'label'                 => '',
                'description'           => '',
                'name'                  => 'nested.input_1',
                'idName'                => 'nested-input_1',
                'value'                 => 'aDefault',
                'default_value'         => 'aDefault',
                'isInCurrentTemplate'   => false,
            ],
            'nested.input_2' =>
            [
                'cat'                   => 'nested',
                'subcat_name'           => 'other',
                'subcat_label'          => 'Other',
                'subcat_sorting_first'  => 'o',
                'subcat_sorting_second' => '90z',
                'type'                  => 'string',
                'label'                 => '',
                'description'           => '',
                'name'                  => 'nested.input_2',
                'idName'                => 'nested-input_2',
                'value'                 => '',
                'default_value'         => '',
                'isInCurrentTemplate'   => false,
            ],
            'predefined.int_1' =>
            [
                'cat'                   => 'pre defined',
                'subcat_name'           => 'dims',
                'subcat_label'          => '',
                'subcat_sorting_first'  => 'b',
                'subcat_sorting_second' => '10z',
                'type'                  => 'int',
                'label'                 => '',
                'description'           => '',
                'name'                  => 'predefined.int_1',
                'idName'                => 'predefined-int_1',
                'value'                 => '42',
                'default_value'         => '42',
                'isInCurrentTemplate'   => false,
            ],
            'predefined.boolean_1' =>
            [
                'cat'                   => 'pre defined',
                'subcat_name'           => 'enable',
                'subcat_label'          => '',
                'subcat_sorting_first'  => 'a',
                'subcat_sorting_second' => '10z',
                'type'                  => 'boolean',
                'trueValue'             => '1',
                'label'                 => '',
                'description'           => '',
                'name'                  => 'predefined.boolean_1',
                'idName'                => 'predefined-boolean_1',
                'value'                 => '1',
                'default_value'         => '1',
                'isInCurrentTemplate'   => false,
            ],
        ];
    }

    #[Test]
    public function AstConstantCommentVisitorCanParseExtConfTemplateComments(): void
    {
        $GLOBALS['LANG'] = $this->getMockBuilder(LanguageService::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['sL'])
            ->getMock();

        $noopEventDispatcher = new NoopEventDispatcher();
        $astTraverser = new AstTraverser();
        $tokens = (new LosslessTokenizer())->tokenize(file_get_contents(__DIR__ . '/../../../Fixtures/ext_conf_template.txt'));
        $ast = (new CommentAwareAstBuilder($noopEventDispatcher))->build($tokens, new RootNode());
        $astConstantCommentVisitor = new (AstConstantCommentVisitor::class);
        $astTraverser->traverse($ast, [$astConstantCommentVisitor]);
        $subject = $astConstantCommentVisitor->getConstants();

        $assertionStructure = $this->getAssertionStructure();
        foreach ($assertionStructure as $checkedConstantName => $expected) {
            self::assertSame($expected, $subject[$checkedConstantName] ?? []);
        }
    }
}
