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
use TYPO3\CMS\Core\Tests\UnitTestCase;
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
        $this->fixture = $this->getMockBuilder(NumberViewHelper::class)
            ->setMethods(array('renderChildren'))
            ->getMock();
        $this->fixture->expects($this->once())->method('renderChildren')->will($this->returnValue(10000.0 / 3.0));
        $renderingContext = $this->createMock(\TYPO3\CMS\Fluid\Tests\Unit\Core\Rendering\RenderingContextFixture::class);
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
