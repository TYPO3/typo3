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

use TYPO3\CMS\Backend\Form\Utility\FormEngineUtility;
use TYPO3\CMS\Core\Tests\UnitTestCase;

/**
 * Test case
 */
class FormEngineUtilityTest extends UnitTestCase
{
    /**
     * @test
     */
    public function databaseRowCompatibilityKeepsSimpleValue()
    {
        $input = [
            'uid' => 42,
            'title' => 'aTitle',
        ];
        $expected = $input;
        $this->assertEquals($expected, FormEngineUtility::databaseRowCompatibility($input));
    }

    /**
     * @test
     */
    public function databaseRowCompatibilityImplodesSimpleArray()
    {
        $input = [
            'uid' => 42,
            'simpleArray' => [
                0 => 1,
                1 => 2,
            ],
        ];
        $expected = $input;
        $expected['simpleArray'] = '1,2';
        $this->assertEquals($expected, FormEngineUtility::databaseRowCompatibility($input));
    }

    /**
     * @test
     */
    public function databaseRowCompatibilityImplodesSelectArrayWithValuesAtSecondPosition()
    {
        $input = [
            'uid' => 42,
            'simpleArray' => [
                0 => [
                    0 => 'aLabel',
                    1 => 'aValue',
                ],
                1 => [
                    0 => 'anotherLabel',
                    1 => 'anotherValue',
                ],
            ],
        ];
        $expected = $input;
        $expected['simpleArray'] = 'aValue,anotherValue';
        $this->assertEquals($expected, FormEngineUtility::databaseRowCompatibility($input));
    }
}
