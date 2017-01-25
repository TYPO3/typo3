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

use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use TYPO3\CMS\Backend\Form\FormDataProvider\DatabasePageLanguageOverlayRows;
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\Tests\UnitTestCase;

/**
 * Test case
 */
class DatabasePageLanguageOverlayRowsTest extends UnitTestCase
{
    /**
     * @var DatabasePageLanguageOverlayRows
     */
    protected $subject;

    /**
     * @var DatabaseConnection | ObjectProphecy
     */
    protected $dbProphecy;

    protected function setUp()
    {
        $this->dbProphecy = $this->prophesize(DatabaseConnection::class);
        $GLOBALS['TYPO3_DB'] = $this->dbProphecy->reveal();
        $GLOBALS['TCA']['pages_language_overlay'] = [];

        $this->subject = new DatabasePageLanguageOverlayRows();
    }

    /**
     * @test
     */
    public function addDataThrowsExceptionOnDatabaseError()
    {
        $this->dbProphecy->exec_SELECTgetRows(Argument::cetera())->willReturn(null);
        $this->dbProphecy->sql_error(Argument::cetera())->willReturn(null);
        $this->setExpectedException(\UnexpectedValueException::class, $this->anything(), 1440777705);
        $this->subject->addData(['effectivePid' => 1]);
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
        $this->dbProphecy->exec_SELECTgetRows('*', 'pages_language_overlay', 'pid=23')
            ->shouldBeCalled()
            ->willReturn($expected['pageLanguageOverlayRows']);
        $this->assertSame($expected, $this->subject->addData($input));
    }
}
