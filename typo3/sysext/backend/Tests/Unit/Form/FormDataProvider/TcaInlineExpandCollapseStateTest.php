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

use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\Backend\Form\FormDataProvider\TcaInlineExpandCollapseState;

/**
 * Test case
 */
class TcaInlineExpandCollapseStateTest extends UnitTestCase
{
    /**
     * @var TcaInlineExpandCollapseState
     */
    protected $subject;

    protected function setUp()
    {
        $this->subject = new TcaInlineExpandCollapseState();
    }

    /**
     * @test
     */
    public function addDataAddsInlineStatusForTableUid()
    {
        $input = [
            'command' => 'edit',
            'tableName' => 'aParentTable',
            'databaseRow' => [
                'uid' => 5,
            ],
        ];
        $inlineState = [
            'aParentTable' => [
                5 => [
                    'aChildTable' => [
                        // Records 23 and 42 are expanded
                        23,
                        42,
                    ],
                ],
            ],
        ];
        $GLOBALS['BE_USER'] = new \stdClass();
        $GLOBALS['BE_USER']->uc = [
            'inlineView' => serialize($inlineState),
        ];
        $expected = $input;
        $expected['inlineExpandCollapseStateArray'] = $inlineState['aParentTable'][5];
        $this->assertSame($expected, $this->subject->addData($input));
    }
}
