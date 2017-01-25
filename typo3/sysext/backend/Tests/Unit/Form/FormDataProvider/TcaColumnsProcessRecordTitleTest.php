<?php
namespace TYPO3\CMS\Backend\Tests\Unit\Form\FormDataProvider;

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

use TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsProcessRecordTitle;
use TYPO3\CMS\Core\Tests\UnitTestCase;

/**
 * Test case
 */
class TcaColumnsProcessRecordTitleTest extends UnitTestCase
{
    /**
     * @var TcaColumnsProcessRecordTitle
     */
    protected $subject;

    protected function setUp()
    {
        $this->subject = new TcaColumnsProcessRecordTitle();
    }

    /**
     * @test
     */
    public function addDataRegistersLabelColumn()
    {
        $input = [
            'columnsToProcess' => [],
            'processedTca' => [
                'ctrl' => [
                    'label' => 'uid'
                ],
                'columns' => [],
            ]
        ];

        $expected = $input;
        $expected['columnsToProcess'] = ['uid'];
        $this->assertSame($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataRegistersAlternativeLabelColumnn()
    {
        $input = [
            'columnsToProcess' => [],
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
        $this->assertSame($expected, $this->subject->addData($input));
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
        $this->assertSame($expected, $this->subject->addData($input));
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
        $this->assertSame($expected, $this->subject->addData($input));
    }
}
