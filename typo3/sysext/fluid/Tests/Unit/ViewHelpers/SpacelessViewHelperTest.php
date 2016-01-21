<?php
namespace TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers;

/*                                                                        *
 * This script is backported from the TYPO3 Flow package "TYPO3.Fluid".   *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 *  of the License, or (at your option) any later version.                *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\ViewHelperNode;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3\CMS\Fluid\ViewHelpers\SpacelessViewHelper;

/**
 * Testcase for SpacelessViewHelper
 */
class SpacelessViewHelperTest extends \TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\ViewHelperBaseTestcase
{
    /**
     * @test
     */
    public function renderWithEmptyChildNodesReturnsNoOutput()
    {
        $instance = new SpacelessViewHelper();
        $viewHelperNodeProphecy = $this->prophesize(ViewHelperNode::class);
        $instance->setViewHelperNode($viewHelperNodeProphecy->reveal());
        $renderingContextInterfaceProphecy = $this->prophesize(RenderingContextInterface::class);
        $instance->setRenderingContext($renderingContextInterfaceProphecy->reveal());
        $instance->setArguments([]);
        // the render method would not return an empty string in a real usage. The render method just
        // calls renderStatic, which does the actual work. The tests for this are done in the
        // renderStatic test directly. The render method test only makes sure the method call
        // raises no fatal error.
        $this->assertEquals('', $instance->render());
    }

    /**
     * @param string $input
     * @param string $expected
     *
     * @dataProvider testRenderStaticDataProvider
     * @test
     */
    public function testRenderStatic($input, $expected)
    {
        $context = $this->getMock(RenderingContextInterface::class);

        $this->assertEquals($expected,
            SpacelessViewHelper::renderStatic([], function () use ($input) {
                return $input;
            }, $context));
    }

    /**
     * @return array
     */
    public function testRenderStaticDataProvider()
    {
        return [
            'extra whitespace between tags' => [
                '<div>foo</div>  <div>bar</div>',
                '<div>foo</div><div>bar</div>',
            ],
            'whitespace preserved in text node' => [
                PHP_EOL . '<div>' . PHP_EOL . 'foo</div>',
                '<div>' . PHP_EOL . 'foo</div>',
            ],
            'whitespace removed from non-text node' => [
                PHP_EOL . '<div>' . PHP_EOL . '<div>foo</div></div>',
                '<div><div>foo</div></div>',
            ],
        ];
    }
}
