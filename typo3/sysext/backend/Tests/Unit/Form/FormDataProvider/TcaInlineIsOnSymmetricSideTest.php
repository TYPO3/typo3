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

namespace TYPO3\CMS\Backend\Tests\Unit\Form\FormDataProvider;

use TYPO3\CMS\Backend\Form\FormDataProvider\TcaInlineIsOnSymmetricSide;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class TcaInlineIsOnSymmetricSideTest extends UnitTestCase
{
    protected TcaInlineIsOnSymmetricSide $subject;

    /**
     * Initializes the mock object.
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->subject = new TcaInlineIsOnSymmetricSide();
    }

    /**
     * @test
     */
    public function addDataSetsIsOnSymmetricSideToTrue(): void
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
        self::assertEquals($expected, $this->subject->addData($input));
    }
}
