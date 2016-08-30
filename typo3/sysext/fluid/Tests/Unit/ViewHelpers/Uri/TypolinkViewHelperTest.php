<?php
namespace TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\Uri;

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        */

use TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\ViewHelperBaseTestcase;
use TYPO3\CMS\Fluid\ViewHelpers\Uri\TypolinkViewHelper;

/**
 * Class TypolinkViewHelperTest
 */
class TypolinkViewHelperTest extends ViewHelperBaseTestcase
{
    /**
     * @return array
     */
    public function typoScriptConfigurationData()
    {
        return [
            'empty input' => [
                '', // input from link field
                '', // additional parameters from fluid
                '', //expected typolink
            ],
            'simple id input' => [
                19,
                '',
                '19',
            ],
            'external url with target' => [
                'www.web.de _blank',
                '',
                'www.web.de _blank',
            ],
            'page with class' => [
                '42 - css-class',
                '',
                '42 - css-class',
            ],
            'page with title' => [
                '42 - - "a link title"',
                '',
                '42 - - "a link title"',
            ],
            'page with title and parameters' => [
                '42 - - "a link title" &x=y',
                '',
                '42 - - "a link title" &x=y',
            ],
            'page with title and extended parameters' => [
                '42 - - "a link title" &x=y',
                '&a=b',
                '42 - - "a link title" &x=y&a=b',
            ],
            'only page id and overwrite' => [
                '42',
                '&a=b',
                '42 - - - &a=b',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider typoScriptConfigurationData
     * @param string $input
     * @param string $additionalParametersFromFluid
     * @param string $expected
     * @throws \InvalidArgumentException
     */
    public function createTypolinkParameterArrayFromArgumentsReturnsExpectedArray($input, $additionalParametersFromFluid, $expected)
    {
        /** @var \TYPO3\CMS\Fluid\ViewHelpers\Uri\TypolinkViewHelper|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface $subject */
        $subject = $this->getAccessibleMock(TypolinkViewHelper::class, ['dummy']);
        $result = $subject->_call('createTypolinkParameterArrayFromArguments', $input, $additionalParametersFromFluid);
        $this->assertSame($expected, $result);
    }
}
