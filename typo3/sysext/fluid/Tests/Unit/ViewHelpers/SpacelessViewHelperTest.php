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
use TYPO3\CMS\Fluid\ViewHelpers\SpacelessViewHelper;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextInterface;

/**
 * Testcase for SpacelessViewHelper
 */
class SpacelessViewHelperTest extends \TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\ViewHelperBaseTestcase {

	/**
	 * @param string $input
	 * @param string $expected
	 * @dataProvider getRenderStaticData
	 * @test
	 */
	public function testRender($input, $expected) {
		$instance = new SpacelessViewHelper();
		$instance->setRenderChildrenClosure(function() use ($input) { return $input; });
		$instance->setRenderingContext($this->getMock(RenderingContextInterface::class));
		$instance->setArguments(array());
		$this->assertEquals($expected, $instance->render());
	}

	/**
	 * @param string $input
	 * @param string $expected
	 * @dataProvider getRenderStaticData
	 * @test
	 */
	public function testRenderStatic($input, $expected) {
		$context = $this->getMock(RenderingContextInterface::class);
		$this->assertEquals($expected, SpacelessViewHelper::renderStatic(array(), function() use ($input) { return $input; }, $context));
	}

	/**
	 * @return array
	 */
	public function getRenderStaticData() {
		return array(
			'extra whitespace between tags' => array('<div>foo</div>  <div>bar</div>', '<div>foo</div><div>bar</div>'),
			'whitespace preserved in text node' => array(PHP_EOL . '<div>' . PHP_EOL . 'foo</div>', '<div>' . PHP_EOL . 'foo</div>'),
			'whitespace removed from non-text node' => array(PHP_EOL . '<div>' . PHP_EOL . '<div>foo</div></div>', '<div><div>foo</div></div>')
		);
	}

}
