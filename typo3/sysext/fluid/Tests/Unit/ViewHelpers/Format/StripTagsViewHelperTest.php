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
use TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\ViewHelperBaseTestcase;

/**
 * Test case
 */
class StripTagsViewHelperTest extends ViewHelperBaseTestcase
{
    /**
     * @var \TYPO3\CMS\Fluid\ViewHelpers\Format\StripTagsViewHelper
     */
    protected $viewHelper;

    protected function setUp()
    {
        parent::setUp();
        $this->viewHelper = $this->getMockBuilder(\TYPO3\CMS\Fluid\ViewHelpers\Format\StripTagsViewHelper::class)
            ->setMethods(array('renderChildren'))
            ->getMock();
        $this->injectDependenciesIntoViewHelper($this->viewHelper);
        $this->viewHelper->initializeArguments();
    }

    /**
     * @test
     */
    public function renderUsesValueAsSourceIfSpecified()
    {
        $this->viewHelper->expects($this->never())->method('renderChildren');
        $actualResult = $this->viewHelper->render('Some string');
        $this->assertEquals('Some string', $actualResult);
    }

    /**
     * @test
     */
    public function renderUsesChildnodesAsSourceIfSpecified()
    {
        $this->viewHelper->expects($this->atLeastOnce())->method('renderChildren')->will($this->returnValue('Some string'));
        $actualResult = $this->viewHelper->render();
        $this->assertEquals('Some string', $actualResult);
    }

    /**
     * Data Provider for the render tests
     *
     * @return array
     */
    public function stringsTestDataProvider()
    {
        return array(
            array('This is a sample text without special characters.', '', 'This is a sample text without special characters.'),
            array('This is a sample text <b>with <i>some</i> tags</b>.', '', 'This is a sample text with some tags.'),
            array('This text contains some &quot;&Uuml;mlaut&quot;.', '', 'This text contains some &quot;&Uuml;mlaut&quot;.'),
            array('This text <i>contains</i> some <strong>allowed</strong> tags.', '<strong>', 'This text contains some <strong>allowed</strong> tags.'),
        );
    }

    /**
     * @test
     * @dataProvider stringsTestDataProvider
     */
    public function renderCorrectlyConvertsIntoPlaintext($source, $allowed, $expectedResult)
    {
        $actualResult = $this->viewHelper->render($source, $allowed);
        $this->assertSame($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function renderReturnsUnmodifiedSourceIfItIsNoString()
    {
        $source = new \stdClass();
        $actualResult = $this->viewHelper->render($source);
        $this->assertSame($source, $actualResult);
    }
}
