<?php
namespace TYPO3\CMS\Extbase\Tests\Unit\Hook\DataHandler;

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

/**
 * Test case
 */
class CheckFlexFormValueTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @test
     */
    public function checkFlexFormValueBeforeMergeRemovesSwitchableControllerActions()
    {
        $currentFlexFormDataArray = [
            'foo' => [
                'bar' => 'baz',
                'qux' => [
                    'quux' => 'quuux',
                    'switchableControllerActions' => []
                ],
                'switchableControllerActions' => []
            ],
            'switchableControllerActions' => []
        ];

        $expectedFlexFormDataArray = [
            'foo' => [
                'bar' => 'baz',
                'qux' => [
                    'quux' => 'quuux',
                ],
            ],
        ];

        /** @var \TYPO3\CMS\Core\DataHandling\DataHandler $dataHandler */
        $dataHandler = $this->getMock(\TYPO3\CMS\Core\DataHandling\DataHandler::class);

        $newFlexFormDataArray = [];
        /** @var \TYPO3\CMS\Extbase\Hook\DataHandler\CheckFlexFormValue $checkFlexFormValue */
        $checkFlexFormValue = $this->getMock(\TYPO3\CMS\Extbase\Hook\DataHandler\CheckFlexFormValue::class, ['dummy']);
        $checkFlexFormValue->checkFlexFormValue_beforeMerge($dataHandler, $currentFlexFormDataArray, $newFlexFormDataArray);

        $this->assertSame($expectedFlexFormDataArray, $currentFlexFormDataArray);
    }
}
