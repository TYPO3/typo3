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

namespace TYPO3\CMS\Backend\Tests\Unit\Form\FormDataProvider\Fixtures;

final class EvaluateDisplayConditionsTestClass
{
    /**
     * Callback method of addDataEvaluatesUserCondition.
     * Test if a USER condition receives all parameters.
     *
     * @throws \RuntimeException if data is ok
     */
    public function addDataEvaluatesUserConditionCallback(array $parameter): void
    {
        $expected = [
            'record' => [],
            'flexContext' => [],
            'flexformValueKey' => 'vDEF',
            'conditionParameters' => [
                0 => 'more',
                1 => 'arguments',
            ],
        ];
        if ($expected === $parameter) {
            throw new \RuntimeException('testing', 1488130499);
        }
    }

    /**
     * Callback method of addDataEvaluatesUserCondition.
     * Throws an exception if data is correct.
     *
     * @throws \RuntimeException if FlexForm context is not as expected
     */
    public function addDataPassesFlexContextToUserConditionCallback(array $parameter): bool
    {
        $expected = [
            'context' => 'flexField',
            'sheetNameFieldNames' => [
                'sDEF.foo' => [
                    'sheetName' => 'sDEF',
                    'fieldName' => 'foo',
                ],
            ],
            'currentSheetName' => 'sDEF',
            'currentFieldName' => 'foo',
            'flexFormDataStructure' => [
                'sheets' => [
                    'sDEF' => [
                        'ROOT' => [
                            'type' => 'array',
                            'el' => [
                                'foo' => [
                                    'displayCond' => 'USER:' . self::class . '->addDataPassesFlexContextToUserConditionCallback:some:info',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'flexFormRowData' => null,
        ];
        if ($expected !== $parameter['flexContext']) {
            throw new \RuntimeException('testing', 1538057402);
        }
        return true;
    }
}
