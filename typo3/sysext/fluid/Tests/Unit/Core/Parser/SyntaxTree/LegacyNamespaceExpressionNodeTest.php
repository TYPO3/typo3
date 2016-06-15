<?php
namespace TYPO3\CMS\Fluid\Tests\Unit\Core\Parser\SyntaxTree;

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
use TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\Expression\LegacyNamespaceExpressionNode;
use TYPO3\CMS\Fluid\Tests\Unit\Core\Rendering\RenderingContextFixture;

/**
 * Class LegacyNamespaceExpressionNodeTest
 */
class LegacyNamespaceExpressionNodeTest extends UnitTestCase
{
    /**
     * @test
     * @dataProvider getEvaluateExpressionTestValues
     * @param array $matches
     * @param array $expected
     */
    public function evaluateExpressionExtractsNamespaces(array $matches, array $expected)
    {
        $resolver = $this->getMockBuilder('TYPO3Fluid\\Fluid\\Core\\ViewHelper\\ViewHelperResolver')
            ->setMethods(array('addNamespace'))
            ->getMock();
        $resolver->expects($this->once())->method('addNamespace')->with($expected[0], $expected[1]);
        $context = $this->getMockBuilder(RenderingContextFixture::class)
            ->setMethods(array('getViewHelperResolver'))
            ->getMock();
        $context->expects($this->once())->method('getViewHelperResolver')->willReturn($resolver);
        LegacyNamespaceExpressionNode::evaluateExpression($context, $matches[0], $matches);
    }

    /**
     * @return array
     */
    public function getEvaluateExpressionTestValues()
    {
        return array(
            array(
                array('foo', 'bar', 'baz'),
                array('bar', 'baz')
            ),
            array(
                array('test1', 'test2', 'test3'),
                array('test2', 'test3')
            )
        );
    }
}
