<?php
namespace TYPO3\CMS\Form\Tests\Unit\Domain;

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
use TYPO3\CMS\Form\Domain\Model\Configuration;
use TYPO3\CMS\Form\Domain\Repository\TypoScriptRepository;

/**
 * Test case for class \TYPO3\CMS\Form\Domain\Model\Configuration
 */
class ConfigurationTest extends UnitTestCase {

	/**
	 * @var Configuration
	 */
	protected $subject = NULL;

	/*
	 * @var TypoScriptRepository|\Prophecy\Prophecy\ObjectProphecy
	 */
	protected $typoScriptRepositoryProphecy;

	/**
	 * Sets up this test case.
	 */
	protected function setUp() {
		parent::setUp();
		$this->typoScriptRepositoryProphecy = $this->prophesize(TypoScriptRepository::class);
		$this->subject = $this->getAccessibleMock(Configuration::class, array('__none'));
		$this->subject->_set('typoScriptRepository', $this->typoScriptRepositoryProphecy->reveal());
	}

	/**
	 * Tears down this test case.
	 */
	protected function tearDown() {
		parent::tearDown();
		unset($this->typoScriptRepositoryProphecy);
		unset($this->subject);
	}

	/**
	 * @param array $typoScript
	 * @param bool $globalCompatibilityMode
	 *
	 * @test
	 * @dataProvider propertiesAreUpdatedFromTypoScriptDataProvider
	 */
	public function propertiesAreUpdatedFromTypoScript(array $typoScript, $globalCompatibilityMode) {
		$this->typoScriptRepositoryProphecy
			->getModelConfigurationByScope('FORM', 'compatibilityMode')
			->willReturn($globalCompatibilityMode);

		$expected = array(
			'prefix' => $typoScript['prefix'] ?: 'form',
			'compatibility' =>  ($typoScript['compatibilityMode'] || $globalCompatibilityMode),
			'contentElementRendering' => !$typoScript['disableContentElement'],
		);

		$this->subject->setTypoScript($typoScript);
		$this->assertEquals($expected['prefix'], $this->subject->getPrefix());
		$this->assertEquals($expected['compatibility'], $this->subject->getCompatibility());
		$this->assertEquals($expected['contentElementRendering'], $this->subject->getContentElementRendering());
	}

	/**
	 * @return array
	 */
	public function propertiesAreUpdatedFromTypoScriptDataProvider() {
		return array(
			'#1' => array(
				array(
					'prefix' => '',
					'compatibilityMode' => FALSE,
					'disableContentElement' => FALSE,
				),
				FALSE
			),
			'#2' => array(
				array(
					'prefix' => '',
					'compatibilityMode' => FALSE,
					'disableContentElement' => FALSE,
				),
				TRUE
			),
			'#3' => array(
				array(
					'prefix' => 'somePrefix',
					'compatibilityMode' => TRUE,
					'disableContentElement' => TRUE,
				),
				TRUE
			),
			'#4' => array(
				array(
					'prefix' => 'somePrefix',
					'compatibilityMode' => TRUE,
					'disableContentElement' => TRUE,
				),
				FALSE
			),
		);
	}

}
