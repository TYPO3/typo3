<?php
namespace TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\Link;

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

use TYPO3\CMS\Fluid\Core\Rendering\RenderingContext;
use TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\ViewHelperBaseTestcase;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\ViewHelpers\Link\TypolinkViewHelper;

/**
 * Class TypolinkViewHelperTest
 */
class TypolinkViewHelperTest extends ViewHelperBaseTestcase {

	/**
	 * @var TypolinkViewHelper|\PHPUnit_Framework_MockObject_MockObject $subject
	 */
	protected $subject;

	public function setUp() {
		$this->subject = $this->getAccessibleMock(TypolinkViewHelper::class, array('renderChildren'));
		/** @var RenderingContext  $renderingContext */
		$renderingContext = $this->getMock(RenderingContext::class);
		$this->subject->setRenderingContext($renderingContext);
	}

	/**
	 * @test
	 */
	public function renderReturnsResultOfContentObjectRenderer() {
		$this->subject->expects($this->any())->method('renderChildren')->will($this->returnValue('innerContent'));
		$contentObjectRendererMock = $this->getMock(\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer::class, array(), array(), '', FALSE);
		$contentObjectRendererMock->expects($this->once())->method('stdWrap')->will($this->returnValue('foo'));
		GeneralUtility::addInstance(\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer::class, $contentObjectRendererMock);
		$this->assertEquals('foo', $this->subject->render('42'));
	}

	/**
	 * @return array
	 */
	public function typoScriptConfigurationData() {
		return array(
			'empty input' => array(
				'', // input from link field
				'', // target from fluid
				'', // class from fluid
				'', // title from fluid
				'', // additional parameters from fluid
				array(),
			),
			'simple id input' => array(
				19,
				'',
				'',
				'',
				'',
				array(
					0 => '"19"',
				),
			),
			'external url with target' => array(
				'www.web.de _blank',
				'',
				'',
				'',
				'',
				array(
					0 => '"www.web.de"',
					1 => '"_blank"',
				),
			),
			'page with class' => array(
				'42 - css-class',
				'',
				'',
				'',
				'',
				array(
					0 => '"42"',
					1 => '-',
					2 => '"css-class"',
				),
			),
			'page with extended class' => array(
				'42 - css-class',
				'',
				'fluid_class',
				'',
				'',
				array(
					0 => '"42"',
					1 => '-',
					2 => '"css-class fluid_class"',
				),
			),
			'page with title' => array(
				'42 - - "a link title"',
				'',
				'',
				'',
				'',
				array(
					0 => '"42"',
					1 => '-',
					2 => '-',
					3 => '"a link title"'
				)
			),
			'page with overridden title' => array(
				'42 - - "a link title"',
				'',
				'',
				'another link title',
				'',
				array(
					0 => '"42"',
					1 => '-',
					2 => '-',
					3 => '"another link title"',
				),
			),
			'page with title and parameters' => array(
				'42 - - "a link title" &x=y',
				'',
				'',
				'',
				'',
				array(
					0 => '"42"',
					1 => '-',
					2 => '-',
					3 => '"a link title"',
					4 => '"&x=y"',
				),
			),
			'page with title and extended parameters' => array(
				'42 - - "a link title" &x=y',
				'',
				'',
				'',
				'&a=b',
				array(
					0 => '"42"',
					1 => '-',
					2 => '-',
					3 => '"a link title"',
					4 => '"&x=y&a=b"',
				),
			),
			'full parameter usage' => array(
				'19 _blank css-class "testtitle with whitespace" &X=y',
				'-',
				'fluid_class',
				'a new title',
				'&a=b',
				array(
					0 => '"19"',
					1 => '-',
					2 => '"css-class fluid_class"',
					3 => '"a new title"',
					4 => '"&X=y&a=b"',
				),
			),
			'only page id and overwrite' => array(
				'42',
				'',
				'',
				'',
				'&a=b',
				array(
					0 => '"42"',
					1 => '-',
					2 => '-',
					3 => '-',
					4 => '"&a=b"',
				),
			),
			'email' => array(
				'a@b.tld',
				'',
				'',
				'',
				'',
				array(
					'"a@b.tld"',
				),
			),
		);
	}

	/**
	 * @test
	 * @dataProvider typoScriptConfigurationData
	 */
	public function createTypolinkParameterArrayFromArgumentsReturnsExpectedArray($input, $targetFromFluid, $classFromFluid, $titleFromFluid, $additionalParametersFromFluid, $expected) {
		$result = $this->subject->_call('createTypolinkParameterArrayFromArguments', $input, $targetFromFluid, $classFromFluid, $titleFromFluid, $additionalParametersFromFluid);
		$this->assertSame($expected, $result);
	}

}
