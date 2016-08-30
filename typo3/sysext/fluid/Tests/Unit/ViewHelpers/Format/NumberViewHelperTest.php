<?php
namespace TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\Format;

/*                                                                        *
 * This script is backported from the TYPO3 Flow package "TYPO3.Fluid".   *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */
use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContext;
use TYPO3\CMS\Fluid\ViewHelpers\Format\NumberViewHelper;

/**
 * Test case
 */
class NumberViewHelperTest extends UnitTestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|NumberViewHelper
     */
    protected $fixture;

    protected function setUp()
    {
        $this->fixture = $this->getMock(NumberViewHelper::class, ['renderChildren']);
        $this->fixture->expects($this->once())->method('renderChildren')->will($this->returnValue(10000.0 / 3.0));
        $renderingContext = $this->getMock(RenderingContext::class);
        $this->fixture->setRenderingContext($renderingContext);
    }

    /**
     * @test
     */
    public function formatNumberDefaultsToEnglishNotationWithTwoDecimals()
    {
        $this->assertEquals('3,333.33', $this->fixture->render());
    }

    /**
     * @test
     */
    public function formatNumberWithDecimalsDecimalPointAndSeparator()
    {
        $this->assertEquals('3.333,333', $this->fixture->render(3, ',', '.'));
    }
}
