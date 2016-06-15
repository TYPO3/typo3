<?php
namespace TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\Format;

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
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\ViewHelperBaseTestcase;

/**
 * Test case
 */
class BytesViewHelperTest extends ViewHelperBaseTestcase
{
    /**
     * @var \TYPO3\CMS\Fluid\ViewHelpers\Format\BytesViewHelper
     */
    protected $viewHelper;

    protected function setUp()
    {
        parent::setUp();

        // XXX: This is bad from a testing POV but the only option right now
        $reflectionClass = new \ReflectionClass(\TYPO3\CMS\Extbase\Utility\LocalizationUtility::class);
        $property = $reflectionClass->getProperty('configurationManager');
        $property->setAccessible(true);
        $property->setValue($this->createMock(ConfigurationManagerInterface::class));

        $this->viewHelper = $this->getMockBuilder(\TYPO3\CMS\Fluid\ViewHelpers\Format\BytesViewHelper::class)
            ->setMethods(array('renderChildren'))
            ->getMock();
        $this->injectDependenciesIntoViewHelper($this->viewHelper);
        $this->viewHelper->initializeArguments();
    }

    /**
     * @return array
     */
    public function valueDataProvider()
    {
        return array(

                // invalid values
            array(
                'value' => 'invalid',
                'decimals' => null,
                'decimalSeparator' => null,
                'thousandsSeparator' => null,
                'expected' => '0 B'
            ),
            array(
                'value' => '',
                'decimals' => 2,
                'decimalSeparator' => null,
                'thousandsSeparator' => null,
                'expected' => '0.00 B'
            ),
            array(
                'value' => array(),
                'decimals' => 2,
                'decimalSeparator' => ',',
                'thousandsSeparator' => null,
                'expected' => '0,00 B'
            ),
                // valid values
            array(
                'value' => 123,
                'decimals' => null,
                'decimalSeparator' => null,
                'thousandsSeparator' => null,
                'expected' => '123 B'
            ),
            array(
                'value' => '43008',
                'decimals' => 1,
                'decimalSeparator' => null,
                'thousandsSeparator' => null,
                'expected' => '42.0 KB'
            ),
            array(
                'value' => 1024,
                'decimals' => 1,
                'decimalSeparator' => null,
                'thousandsSeparator' => null,
                'expected' => '1.0 KB'
            ),
            array(
                'value' => 1023,
                'decimals' => 2,
                'decimalSeparator' => null,
                'thousandsSeparator' => null,
                'expected' => '1,023.00 B'
            ),
            array(
                'value' => 1073741823,
                'decimals' => 1,
                'decimalSeparator' => null,
                'thousandsSeparator' => '.',
                'expected' => '1.024.0 MB'
            ),
            array(
                'value' => pow(1024, 5),
                'decimals' => 1,
                'decimalSeparator' => null,
                'thousandsSeparator' => null,
                'expected' => '1.0 PB'
            ),
            array(
                'value' => pow(1024, 8),
                'decimals' => 1,
                'decimalSeparator' => null,
                'thousandsSeparator' => null,
                'expected' => '1.0 YB'
            )
        );
    }

    /**
     * @param $value
     * @param $decimals
     * @param $decimalSeparator
     * @param $thousandsSeparator
     * @param $expected
     * @test
     * @dataProvider valueDataProvider
     */
    public function renderCorrectlyConvertsAValue($value, $decimals, $decimalSeparator, $thousandsSeparator, $expected)
    {
        $actualResult = $this->viewHelper->render($value, $decimals, $decimalSeparator, $thousandsSeparator);
        $this->assertEquals($expected, $actualResult);
    }

    /**
     * @test
     */
    public function renderUsesChildNodesIfValueArgumentIsOmitted()
    {
        $this->viewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue(12345));
        $actualResult = $this->viewHelper->render();
        $this->assertEquals('12 KB', $actualResult);
    }
}
