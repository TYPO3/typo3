<?php
namespace TYPO3\CMS\Form\Tests\Unit\PostProcess;

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
use Prophecy\Argument;
use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\Core\TypoScript\TemplateService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Form\Domain\Factory\TypoScriptFactory;
use TYPO3\CMS\Form\Domain\Model\Form;
use TYPO3\CMS\Form\Layout;
use TYPO3\CMS\Form\PostProcess\PostProcessor;
use TYPO3\CMS\Form\Tests\Unit\Fixtures\PostProcessorWithFormPrefixFixture;
use TYPO3\CMS\Form\Tests\Unit\Fixtures\PostProcessorWithoutFormPrefixFixture;
use TYPO3\CMS\Form\Tests\Unit\Fixtures\PostProcessorWithoutInterfaceFixture;

/**
 * Testcase for PostProcessor
 */
class PostProcessorTest extends UnitTestCase {

	/**
	 * @var array A backup of registered singleton instances
	 */
	protected $singletonInstances = array();

	/**
	 * @var Form
	 */
	protected $formProphecy;

	/**
	 * @var Layout
	 */
	protected $typoScriptLayoutProphecy;

	/**
	 * @var TypoScriptFactory
	 */
	protected $typoScriptFactoryProphecy;

	/**
	 * Set up
	 */
	protected function setUp() {
		$this->singletonInstances = GeneralUtility::getSingletonInstances();

		$this->formProphecy = $this->prophesize(Form::class);

		$this->typoScriptFactoryProphecy = $this->prophesize(TypoScriptFactory::class);
		$this->typoScriptFactoryProphecy->getLayoutFromTypoScript(Argument::any())->willReturn(array());
		GeneralUtility::setSingletonInstance(TypoScriptFactory::class, $this->typoScriptFactoryProphecy->reveal());

		$this->typoScriptLayoutProphecy = $this->prophesize(Layout::class);

		$templateServiceProphecy = $this->prophesize(TemplateService::class);
		$templateServiceProphecy->sortedKeyList(Argument::any())->willReturn(array(10, 20));
		GeneralUtility::addInstance(TemplateService::class, $templateServiceProphecy->reveal());
	}

	/**
	 * Tear down the tests
	 */
	protected function tearDown() {
		GeneralUtility::resetSingletonInstances($this->singletonInstances);
		parent::tearDown();
	}

	/**
	 * @test
	 */
	public function processFindsClassSpecifiedByTypoScriptWithoutFormPrefix() {

		$typoScript = array(
			10 => $this->getUniqueId('postprocess'),
			20 => PostProcessorWithoutFormPrefixFixture::class
		);

		$subject = new PostProcessor($this->formProphecy->reveal(), $typoScript);
		$this->typoScriptFactoryProphecy->setLayoutHandler($typoScript)->willReturn($this->typoScriptLayoutProphecy->reveal());

		$this->assertEquals('processedWithoutPrefix', $subject->process());
	}

	/**
	 * @test
	 */
	public function processFindsClassSpecifiedByTypoScriptWithFormPrefix() {
		$typoScript = array(
			10 => $this->getUniqueId('postprocess'),
			20 => PostProcessorWithFormPrefixFixture::class
		);

		$subject = new PostProcessor($this->formProphecy->reveal(), $typoScript);
		$this->typoScriptFactoryProphecy->setLayoutHandler($typoScript)->willReturn($this->typoScriptLayoutProphecy->reveal());

		$this->assertEquals('processedWithPrefix', $subject->process());
	}

	/**
	 * @test
	 */
	public function processReturnsEmptyStringIfSpecifiedPostProcessorDoesNotImplementTheInterface() {
		$typoScript = array(
			10 => $this->getUniqueId('postprocess'),
			20 => PostProcessorWithoutInterfaceFixture::class
		);

		$subject = new PostProcessor($this->formProphecy->reveal(), $typoScript);
		$this->typoScriptFactoryProphecy->setLayoutHandler($typoScript)->willReturn($this->typoScriptLayoutProphecy->reveal());

		$this->assertEquals('', $subject->process());
	}

	/**
	 * @test
	 */
	public function processUsesGlobalLayoutIfNoneIsSet() {
		$processorConfig = array(
			'recipientEmail' => 'your@email.com',
			'senderEmail' => 'your@email.com',
		);
		$typoScript = array(
			'layout.' => array(
					'label' => '<div class="global"><labelvalue /></div>',
			),
			'1' => 'foo', // something senseless on purpose, otherwise dependencies need to be resolved, that come in by static call -> ugly
			'1.' => $processorConfig
		);

		$subject = new PostProcessor($this->formProphecy->reveal(), $typoScript);
		$this->typoScriptFactoryProphecy->setLayoutHandler($typoScript)->willReturn($this->typoScriptLayoutProphecy->reveal());

		$this->assertEquals('', $subject->process());
	}

	/**
	 * @test
	 */
	public function processUsesLocalLayoutIfSet() {
		$processorConfig = array(
			'layout.' => array(
					'label' => '<div class="local"><labelvalue /></div>',
			),
			'recipientEmail' => 'your@email.com',
			'senderEmail' => 'your@email.com',
		);
		$typoScript = array(
			'layout.' => array(
					'label' => '<div class="global"><labelvalue /></div>',
			),
			'1' => 'foo',
			'1.' => $processorConfig
		);

		$subject = new PostProcessor($this->formProphecy->reveal(), $typoScript);
		$this->typoScriptFactoryProphecy->setLayoutHandler($processorConfig)->willReturn($this->typoScriptLayoutProphecy->reveal());

		$this->assertEquals('', $subject->process());
	}
}