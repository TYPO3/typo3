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
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class TypolinkViewHelperTest
 */
class TypolinkViewHelperTest extends ViewHelperBaseTestcase {

	/**
	 * @return array
	 */
	public function typoScriptConfigurationData() {
		return array(
			'empty input' => array(
				'', // input from link field
				'', // additional parameters from fluid
				array(), //expected typolink Array
                '' // expected URI
			),
			'simple id input' => array(
				19,
				'',
				array(
					0 => '"19"',
				),
                'index.php?id=19'
			),
			'external url with target' => array(
				'www.web.de _blank',
				'',
				array(
					0 => '"www.web.de"',
					1 => '"_blank"',
				),
                'http://www.web.de'
			),
			'page with class' => array(
				'42 - css-class',
				'',
				array(
					0 => '"42"',
					1 => '-',
					2 => '"css-class"',
				),
                "index.php?id=42"
			),
			'page with title' => array(
				'42 - - "a link title"',
				'',
				array(
					0 => '"42"',
					1 => '-',
					2 => '-',
					3 => '"a link title"'
				),
                "index.php?id=42"
			),
			'page with title and parameters' => array(
				'42 - - "a link title" &x=y',
				'',
				array(
					0 => '"42"',
					1 => '-',
					2 => '-',
					3 => '"a link title"',
					4 => '"&x=y"',
				),
                "index.php?id=42&x=y"
			),
			'page with title and extended parameters' => array(
				'42 - - "a link title" &x=y',
				'&a=b',
				array(
					0 => '"42"',
					1 => '-',
					2 => '-',
					3 => '"a link title"',
					4 => '"&x=y&a=b"',
				),
                "index.php?id=42&x=y&a=b"
			),
			'full parameter usage' => array(
				'19 _blank css-class "testtitle with whitespace" &X=y',
				'&a=b',
				array(
					0 => '"19"',
					1 => '"_blank"',
					2 => '"css-class"',
					3 => '"testtitle with whitespace"',
					4 => '"&X=y&a=b"',
				),
                "index.php?id=19&X=y&a=b"
			),
			'only page id and overwrite' => array(
				'42',
				'&a=b',
				array(
					0 => '"42"',
					1 => '-',
					2 => '-',
					3 => '-',
					4 => '"&a=b"',
				),
                "index.php?id=42&a=b"
			),
			'email' => array(
				'a@b.tld',
				'',
				array(
					'"a@b.tld"',
				),
                'mailto:a@b.tld'
			),
		);
	}

	/**
	 * @test
	 * @dataProvider typoScriptConfigurationData
	 */
	public function createTypolinkParameterArrayFromArgumentsReturnsExpectedArray($input, $additionalParametersFromFluid, $expected) {
		/** @var \TYPO3\CMS\Fluid\ViewHelpers\Uri\TypolinkViewHelper|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface $subject */
		$subject = $this->getAccessibleMock('TYPO3\\CMS\\Fluid\\ViewHelpers\\Uri\\TypolinkViewHelper', array('dummy'));
		$result = $subject->_call('createTypolinkParameterArrayFromArguments', $input, $additionalParametersFromFluid);
		$this->assertSame($expected, $result);
	}
}
