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

use TYPO3\CMS\Backend\Form\FormDataProvider\TcaInlineIsOnSymmetricSide;
use TYPO3\CMS\Core\Tests\UnitTestCase;

/**
 * Test case
 */
class TcaInlineIsOnSymmetricSideTest extends UnitTestCase
{
    /**
     * @var TcaInlineIsOnSymmetricSide
     */
    protected $subject;

    /**
     * Initializes the mock object.
     */
    public function setUp()
    {
        $this->subject = new TcaInlineIsOnSymmetricSide();
    }

    /**
     * @test
     */
    public function addDataSetsIsOnSymmetricSideToTrue()
    {
        $input = [
            'databaseRow' => [
                'uid' => 5,
                'theSymmetricField' => [
                    23,
                ],
            ],
            'isInlineChild' => true,
            'inlineParentUid' => 23,
            'inlineParentConfig' => [
                'symmetric_field' => 'theSymmetricField',
            ],
        ];
        $expected = $input;
        $expected['isOnSymmetricSide'] = true;
        $this->assertEquals($expected, $this->subject->addData($input));
    }
}
