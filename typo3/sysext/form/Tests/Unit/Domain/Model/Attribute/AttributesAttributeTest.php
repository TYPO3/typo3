<?php
namespace typo3\sysext\form\Tests\Unit\Domain;

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
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\CMS\Form\Domain\Model\Attribute\AbstractAttribute;
use TYPO3\CMS\Form\Domain\Model\Attribute\AttributesAttribute;
use TYPO3\CMS\Form\Localization;
use TYPO3\CMS\Form\Request;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

/**
 * Test case for class \TYPO3\CMS\Form\Domain\Model\Attribute\AttributesAttribute
 */
class AttributesAttributeTest extends UnitTestCase {

	/**
	 * @var AttributesAttribute
	 */
	protected $subject = NULL;

	/**
	 * @var array A backup of registered singleton instances
	 */
	protected $singletonInstances = array();

	/**
	 * @var string
	 */
	protected $elementId;

	/**
	 *
	 */
	public function setUp(){
		$contentObjectRendererProphecy = $this->prophesize(ContentObjectRenderer::class);
		GeneralUtility::addInstance(ContentObjectRenderer::class, $contentObjectRendererProphecy->reveal());
		$localisationProphecy = $this->prophesize(Localization::class);
		GeneralUtility::addInstance(Localization::class, $localisationProphecy->reveal());
		$requestProphecy = $this->prophesize(Request::class);
		$this->singletonInstances = GeneralUtility::getSingletonInstances();
		GeneralUtility::setSingletonInstance(Request::class, $requestProphecy->reveal());
		$this->elementId = StringUtility::getUniqueId('elementId_');
		$this->subject = new AttributesAttribute($this->elementId);
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
	public function hasAttributeReturnsFalseForMissingAttribute() {
		$this->assertFalse($this->subject->hasAttribute('anAttribute'));
	}

	/**
	 * @test
	 */
	public function getValueReturnsEmptyIfAttributeIsNotSet() {
		$this->assertEmpty($this->subject->getValue('anAttribute'));
	}

	/**
	 * @test
	 */
	public function getValueReturnsValueIfAttributeIsSet() {
		$attributeProphecy = $this->prophesize(AbstractAttribute::class);
		$attributeProphecy->getValue()->shouldBeCalled()->willReturn('aValue');

		$this->subject->setAttribute('anAttribute', $attributeProphecy->reveal());
		$this->assertSame('aValue', $this->subject->getValue('anAttribute'));
	}
}
