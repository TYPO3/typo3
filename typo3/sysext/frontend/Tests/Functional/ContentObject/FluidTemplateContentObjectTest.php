<?php
namespace TYPO3\CMS\Frontend\Tests\Functional\ContentObject;

/**
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
