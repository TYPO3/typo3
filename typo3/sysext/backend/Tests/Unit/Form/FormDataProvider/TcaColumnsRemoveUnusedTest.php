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

use TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsRemoveUnused;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class TcaColumnsRemoveUnusedTest extends UnitTestCase
{
    protected TcaColumnsRemoveUnused $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new TcaColumnsRemoveUnused();
    }

    /**
     * @test
     */
    public function addDataKeepsColumnsToProcess(): void
    {
        $input = [
            'columnsToProcess' => ['keepMe', 'keepMeToo'],
            'processedTca' => [
                'columns' => [
                    'keepMe' => [
                        'config' => [
                            'type' => 'input',
                        ]
                    ],
                    'keepMeToo' => [
                        'config' => [
                            'type' => 'input',
                        ]
                    ],
                    'aField' => [
                        'config' => [
                            'type' => 'input',
                        ]
                    ]
                ]
            ]
        ];

        $expected = $input;
        unset($expected['processedTca']['columns']['aField']);

        self::assertSame($expected, $this->subject->addData($input));
    }
}
