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

use TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseEffectivePid;
use TYPO3\CMS\Core\Tests\UnitTestCase;

/**
 * Test case
 */
class DatabaseEffectivePidTest extends UnitTestCase
{
    /**
     * @var DatabaseEffectivePid
     */
    protected $subject;

    protected function setUp()
    {
        $this->subject = new DatabaseEffectivePid();
    }

    /**
     * @test
     */
    public function addDataSetsUidOfRecordIsPageIsEdited()
    {
        $input = [
            'command' => 'edit',
            'tableName' => 'pages',
            'databaseRow' => [
                'uid' => 123,
            ],
        ];
        $expected = $input;
        $expected['effectivePid'] = 123;
        $this->assertSame($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataSetsPidOfRecordIfNoPageIsEdited()
    {
        $input = [
            'command' => 'edit',
            'tableName' => 'tt_content',
            'databaseRow' => [
                'pid' => 123,
            ],
        ];
        $expected = $input;
        $expected['effectivePid'] = 123;
        $this->assertSame($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataSetsUidOfParentPageRowIfParentPageRowExistsAndCommandIsNew()
    {
        $input = [
            'command' => 'new',
            'tableName' => 'tt_content',
            'parentPageRow' => [
                'uid' => 123
            ],
        ];
        $expected = $input;
        $expected['effectivePid'] = 123;
        $this->assertSame($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataSetsZeroWithMissingParentPageRowAndCommandIsNew()
    {
        $input = [
            'command' => 'new',
            'tableName' => 'pages',
            'parentPageRow' => null,
        ];
        $expected = $input;
        $expected['effectivePid'] = 0;
        $this->assertSame($expected, $this->subject->addData($input));
    }
}
