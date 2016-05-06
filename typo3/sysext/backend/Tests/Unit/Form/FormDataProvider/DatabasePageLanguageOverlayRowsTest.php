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

use TYPO3\CMS\Backend\Form\FormDataProvider\DatabasePageLanguageOverlayRows;
use TYPO3\CMS\Core\Tests\UnitTestCase;

/**
 * Test case
 */
class DatabasePageLanguageOverlayRowsTest extends UnitTestCase
{
    /**
     * @var DatabasePageLanguageOverlayRows|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $subject;

    protected function setUp()
    {
        $this->subject = $this->getMockBuilder(DatabasePageLanguageOverlayRows::class)
            ->setMethods(['getDatabaseRows'])
            ->getMock();
    }

    /**
     * @test
     */
    public function addDataSetsPageLanguageOverlayRows()
    {
        $input = [
            'effectivePid' => '23',
        ];
        $expected = $input;
        $expected['pageLanguageOverlayRows'] = [
            0 => [
                'uid' => '1',
                'pid' => '42',
                'sys_language_uid' => '2',
            ],
        ];
        $this->subject->expects($this->once())
            ->method('getDatabaseRows')
            ->willReturn($expected['pageLanguageOverlayRows']);

        $this->assertSame($expected, $this->subject->addData($input));
    }
}
