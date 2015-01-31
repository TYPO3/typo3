<?php
namespace TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\Uri;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is backported from the TYPO3 Flow package "TYPO3.Fluid".
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
 * Testcase for the email uri view helper
 */
class EmailViewHelperTest extends \TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\ViewHelperBaseTestcase {

	/**
	 * @var \TYPO3\CMS\Fluid\ViewHelpers\Uri\EmailViewHelper
	 */
	protected $viewHelper;

	protected function setUp() {
		parent::setUp();
		$GLOBALS['TSFE'] = new \stdClass();
		$GLOBALS['TSFE']->cObj = $this->getMock(\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer::class, array(), array(), '', FALSE);
		$this->viewHelper = new \TYPO3\CMS\Fluid\ViewHelpers\Uri\EmailViewHelper();
		$this->injectDependenciesIntoViewHelper($this->viewHelper);
		$this->viewHelper->initializeArguments();
	}

	/**
	 * @test
	 */
	public function renderReturnsFirstResultOfGetMailTo() {
		$this->viewHelper->initialize();
		$actualResult = $this->viewHelper->render('some@email.tld');
		$this->assertEquals('mailto:some@email.tld', $actualResult);
	}

}
