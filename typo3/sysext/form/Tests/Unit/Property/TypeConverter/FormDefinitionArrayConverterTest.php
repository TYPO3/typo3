<?php
namespace TYPO3\CMS\Form\Tests\Unit\Property\TypeConverter;

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

use TYPO3\CMS\Form\Property\TypeConverter\FormDefinitionArrayConverter;
use TYPO3\CMS\Form\Type\FormDefinitionArray;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case for TYPO3\CMS\Form\Property\TypeConverter\FormDefinitionArrayConverter
 */
class FormDefinitionArrayConverterTest extends UnitTestCase
{
    /**
     * @test
     */
    public function convertsJsonStringToFormDefinitionArray()
    {
        $typeConverter = new FormDefinitionArrayConverter();
        $source = '{"francine":"stan","enabled":false,"properties":{"options":[{"_label":"label","_value":"value"}]}}';
        $expected = [
            'francine' => 'stan',
            'enabled' => false,
            'properties' => [
                'options' => [
                    'value' => 'label',
                ],
            ],
        ];
        $result = $typeConverter->convertFrom($source, FormDefinitionArray::class);

        $this->assertInstanceOf(FormDefinitionArray::class, $result);
        $this->assertSame($expected, $result->getArrayCopy());
    }
}
