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

namespace TYPO3\CMS\Extbase\Tests\Unit\Hook\DataHandler;

use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Extbase\Hook\DataHandler\CheckFlexFormValue;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class CheckFlexFormValueTest extends UnitTestCase
{
    /**
     * @test
     */
    public function checkFlexFormValueBeforeMergeRemovesSwitchableControllerActions(): void
    {
        $currentFlexFormDataArray = [
            'foo' => [
                'bar' => 'baz',
                'qux' => [
                    'quux' => 'quuux',
                    'switchableControllerActions' => [],
                ],
                'switchableControllerActions' => [],
            ],
            'switchableControllerActions' => [],
        ];

        $expectedFlexFormDataArray = [
            'foo' => [
                'bar' => 'baz',
                'qux' => [
                    'quux' => 'quuux',
                ],
            ],
        ];

        /** @var DataHandler $dataHandler */
        $dataHandler = $this->createMock(DataHandler::class);

        $newFlexFormDataArray = [];
        /** @var CheckFlexFormValue $checkFlexFormValue */
        $checkFlexFormValue = $this->getMockBuilder(CheckFlexFormValue::class)
            ->addMethods(['dummy'])
            ->getMock();
        $checkFlexFormValue->checkFlexFormValue_beforeMerge($dataHandler, $currentFlexFormDataArray, $newFlexFormDataArray);

        self::assertSame($expectedFlexFormDataArray, $currentFlexFormDataArray);
    }
}
