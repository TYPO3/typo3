<?php

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

use TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsProcessRecordTitle;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class TcaColumnsProcessRecordTitleTest extends UnitTestCase
{
    /**
     * @test
     */
    public function addDataRegistersLabelColumn()
    {
        $input = [
            'columnsToProcess' => [],
            'isInlineChild' => false,
            'processedTca' => [
                'ctrl' => [
                    'label' => 'uid'
                ],
                'columns' => [],
            ]
        ];

        $expected = $input;
        $expected['columnsToProcess'] = ['uid'];
        self::assertSame($expected, (new TcaColumnsProcessRecordTitle())->addData($input));
    }

    /**
     * @test
     */
    public function addDataRegistersAlternativeLabelColumn()
    {
        $input = [
            'columnsToProcess' => [],
            'isInlineChild' => false,
            'processedTca' => [
                'ctrl' => [
                    'label' => 'uid',
                    'label_alt' => 'aField,anotherField',
                ],
                'columns' => [],
            ]
        ];

        $expected = $input;
        $expected['columnsToProcess'] = ['uid', 'aField', 'anotherField'];
        self::assertSame($expected, (new TcaColumnsProcessRecordTitle())->addData($input));
    }

    /**
     * @test
     */
    public function addDataRegistersForeignLabelInInlineContext()
    {
        $input = [
            'columnsToProcess' => [],
            'inlineParentConfig' => [
                'foreign_label' => 'aForeignLabelField',
            ],
            'isInlineChild' => true,
        ];

        $expected = $input;
        $expected['columnsToProcess'] = [ 'aForeignLabelField' ];
        self::assertSame($expected, (new TcaColumnsProcessRecordTitle())->addData($input));
    }

    /**
     * @test
     */
    public function addDataRegistersSymmetricLabelInInlineContext()
    {
        $input = [
            'columnsToProcess' => [],
            'inlineParentConfig' => [
                'symmetric_label' => 'aSymmetricLabelField',
            ],
            'isInlineChild' => true,
        ];

        $expected = $input;
        $expected['columnsToProcess'] = [ 'aSymmetricLabelField' ];
        self::assertSame($expected, (new TcaColumnsProcessRecordTitle())->addData($input));
    }
}
