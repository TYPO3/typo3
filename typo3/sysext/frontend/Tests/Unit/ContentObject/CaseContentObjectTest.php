<?php
namespace TYPO3\CMS\Frontend\Tests\Unit\ContentObject;

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
use TYPO3\CMS\Frontend\ContentObject\CaseContentObject;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\CMS\Core\TypoScript\TemplateService;
use TYPO3\CMS\Frontend\Page\PageRepository;
use TYPO3\CMS\Core\Charset\CharsetConverter;

/**
 * Testcase for TYPO3\CMS\Frontend\ContentObject\CaseContentObject
 *
 * @author Philipp Gampe <philipp.gampe@typo3.org>
 */
class CaseContentObjectTest extends UnitTestCase {

	/**
	 * @var CaseContentObject|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected $subject = NULL;

	/**
	 * Set up
	 */
	public function setUp() {
		$this->singletonInstances = \TYPO3\CMS\Core\Utility\GeneralUtility::getSingletonInstances();
		$this->contentObjectRenderer = $this->getMock(
			'TYPO3\\CMS\\Frontend\\ContentObject\\ContentObjectRenderer',
			array('dummy')
		);
		$this->subject = $this->getAccessibleMock(
			'TYPO3\\CMS\\Frontend\\ContentObject\\CaseContentObject',
			array('dummy'),
			array($this->contentObjectRenderer)
		);
		/** @var $tsfe \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController */
		$tsfe = $this->getMock('TYPO3\\CMS\\Frontend\\Controller\\TypoScriptFrontendController', array(), array(), '', FALSE);
		$tsfe->tmpl = $this->getMock('TYPO3\\CMS\\Core\\TypoScript\\TemplateService');
		$GLOBALS['TSFE'] = $tsfe;
	}


	/**
	 * @test
	 */
	public function renderReturnsEmptyStringIfNoKeyMatchesAndIfNoDefaultObjectIsSet() {
		$conf = array(
			'key' => 'not existing'
		);
		$this->assertSame('', $this->subject->render($conf));
	}

	/**
	 * @test
	 */
	public function renderReturnsContentFromDefaultObjectIfKeyDoesNotExist() {
		$conf = array(
			'key' => 'not existing',
			'default' => 'TEXT',
			'default.' => array(
				'value' => 'expected value'
			),
		);
		$this->assertSame('expected value', $this->subject->render($conf));
	}
}
