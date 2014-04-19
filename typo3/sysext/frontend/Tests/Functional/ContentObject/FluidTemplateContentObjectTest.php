<?php
namespace TYPO3\CMS\Frontend\Tests\Functional\ContentObject;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2014 Christian Kuhn <lolli@schwarzbu.ch>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Test case
 */
class FluidTemplateContentObjectTest extends \TYPO3\CMS\Core\Tests\FunctionalTestCase {

	protected $coreExtensionsToLoad = array('fluid');

	/**
	 * @test
	 */
	public function renderWorksWithNestedFluidtemplate() {
		/** @var $tsfe \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController */
		$tsfe = $this->getMock('TYPO3\\CMS\\Frontend\\Controller\\TypoScriptFrontendController', array(), array(), '', FALSE);
		$GLOBALS['TSFE'] = $tsfe;

		$configuration = array(
			'10' => 'FLUIDTEMPLATE',
			'10.' => array(
				'template' => 'TEXT',
				'template.' => array(
					'value' => 'A{anotherFluidTemplate}C'
				),
				'variables.' => array(
					'anotherFluidTemplate' => 'FLUIDTEMPLATE',
					'anotherFluidTemplate.' => array(
						'template' => 'TEXT',
						'template.' => array(
							'value' => 'B',
						),
					),
				),
			),
		);
		$expectedResult = 'ABC';

		$contentObjectRenderer = new \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
		$fluidTemplateContentObject = new \TYPO3\CMS\Frontend\ContentObject\ContentObjectArrayContentObject(
			$contentObjectRenderer
		);
		$result = $fluidTemplateContentObject->render($configuration);

		$this->assertEquals($expectedResult, $result);
	}
}
