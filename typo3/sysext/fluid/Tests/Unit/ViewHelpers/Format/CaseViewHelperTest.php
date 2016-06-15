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
use TYPO3\CMS\Fluid\ViewHelpers\Format\CaseViewHelper;

/**
 * Test case
 */
class CaseViewHelperTest extends ViewHelperBaseTestcase
{
    /**
     * @var CaseViewHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $subject;

    protected function setUp()
    {
        parent::setUp();
        $this->subject = $this->getMockBuilder(CaseViewHelper::class)
            ->setMethods(array('renderChildren'))
            ->getMock();
        $this->injectDependenciesIntoViewHelper($this->subject);
    }

    /**
     * @test
     */
    public function viewHelperRendersChildrenIfGivenValueIsNull()
    {
        $this->subject->expects($this->once())->method('renderChildren');
        $this->subject->render();
    }

    /**
     * @test
     */
    public function viewHelperDoesNotRenderChildrenIfGivenValueIsNotNull()
    {
        $this->subject->expects($this->never())->method('renderChildren');
        $this->subject->render('');
        $this->subject->render(0);
        $this->subject->render('foo');
    }

    /**
     * @test
     */
    public function viewHelperThrowsExceptionIfIncorrectModeIsGiven()
    {
        $this->expectException(\TYPO3\CMS\Fluid\Core\ViewHelper\Exception\InvalidVariableException::class);
        $this->expectExceptionCode(1358349150);

        $this->subject->render('Foo', 'incorrectMode');
    }

    /**
     * @test
     */
    public function viewHelperConvertsUppercasePerDefault()
    {
        $this->assertSame('FOOB4R', $this->subject->render('FooB4r'));
    }

    /**
     * Signature: $input, $mode, $expected
     */
    public function conversionTestingDataProvider()
    {
        return array(
            array('FooB4r', CaseViewHelper::CASE_LOWER, 'foob4r'),
            array('FooB4r', CaseViewHelper::CASE_UPPER, 'FOOB4R'),
            array('foo bar', CaseViewHelper::CASE_CAPITAL, 'Foo bar'),
            array('FOO Bar', CaseViewHelper::CASE_UNCAPITAL, 'fOO Bar'),
            array('smørrebrød', CaseViewHelper::CASE_UPPER, 'SMØRREBRØD'),
            array('smørrebrød', CaseViewHelper::CASE_CAPITAL, 'Smørrebrød'),
            array('römtömtömtöm', CaseViewHelper::CASE_UPPER, 'RÖMTÖMTÖMTÖM'),
            array('Ἕλλάς α ω', CaseViewHelper::CASE_UPPER, 'ἝΛΛΆΣ Α Ω'),
        );
    }

    /**
     * @test
     * @dataProvider conversionTestingDataProvider
     */
    public function viewHelperConvertsCorrectly($input, $mode, $expected)
    {
        $this->assertSame($expected, $this->subject->render($input, $mode), sprintf('The conversion with mode "%s" did not perform as expected.', $mode));
    }
}
