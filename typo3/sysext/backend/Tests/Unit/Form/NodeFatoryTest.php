<?php
namespace TYPO3\CMS\Backend\Tests\Unit\Form;

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

use TYPO3\CMS\Backend\Form\Element;
use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\Backend\Form\NodeFactory;
use TYPO3\CMS\Backend\Form\NodeInterface;

/**
 * Test case
 */
class NodeFactoryTest extends UnitTestCase {

	/**
	 * @test
	 * @expectedException \TYPO3\CMS\Backend\Form\Exception
	 */
	public function createThrowsExceptionIfTypeIsNotGiven() {
		$subject = new NodeFactory();
		$subject->create(array());
	}

	/**
	 * @test
	 * @expectedException \TYPO3\CMS\Backend\Form\Exception
	 */
	public function createThrowsExceptionIfNodeDoesNotImplementNodeInterface() {
		$mockNode = new \stdClass();
		/** @var NodeInterface|\PHPUnit_Framework_MockObject_MockObject $mockSubject */
		$mockSubject = $this->getMock(NodeFactory::class, array('instantiate'), array(), '', FALSE);
		$mockSubject->expects($this->once())->method('instantiate')->will($this->returnValue($mockNode));
		$mockSubject->create(array('type' => 'foo'));
	}

	/**
	 * @test
	 */
	public function createSetsGlobalOptionsInInstantiatedObject() {
		$globalOptions = array('type' => 'foo');
		$mockNode = $this->getMock(NodeInterface::class, array(), array(), '', FALSE);
		$mockNode->expects($this->once())->method('setGlobalOptions')->with($globalOptions);
		/** @var NodeInterface|\PHPUnit_Framework_MockObject_MockObject $mockSubject */
		$mockSubject = $this->getMock(NodeFactory::class, array('instantiate'), array(), '', FALSE);
		$mockSubject->expects($this->once())->method('instantiate')->will($this->returnValue($mockNode));
		$mockSubject->create($globalOptions);
	}

	/**
	 * @test
	 */
	public function createReturnsInstanceOfUnknownElementIfTypeIsNotRegistered() {
		$subject = new NodeFactory();
		$this->assertInstanceOf(Element\UnknownElement::class, $subject->create(array('type' => 'foo')));
	}

	/**
	 * @test
	 */
	public function createReturnsInstanceOfSelectTreeElementIfNeeded() {
		$globalOptions = array(
			'type' => 'select',
			'parameterArray' => array(
				'fieldConf' => array(
					'config' => array(
						'renderMode' => 'tree',
					),
				),
			),
		);
		$subject = new NodeFactory();
		$this->assertInstanceOf(Element\SelectTreeElement::class, $subject->create($globalOptions));
	}

	/**
	 * @test
	 */
	public function createReturnsInstanceOfSelectSingleElementIfNeeded() {
		$globalOptions = array(
			'type' => 'select',
			'parameterArray' => array(
				'fieldConf' => array(
					'config' => array(
						'maxitems' => 1,
					),
				),
			),
		);
		$subject = new NodeFactory();
		$this->assertInstanceOf(Element\SelectSingleElement::class, $subject->create($globalOptions));
	}

	/**
	 * @test
	 */
	public function createReturnsInstanceOfSelectSingleElementIfSelectboxIsConfiguredButMaxitemsIsOne() {
		$globalOptions = array(
			'type' => 'select',
			'parameterArray' => array(
				'fieldConf' => array(
					'config' => array(
						'renderMode' => 'singlebox',
						'maxitems' => 1,
					),
				),
			),
		);
		$subject = new NodeFactory();
		$this->assertInstanceOf(Element\SelectSingleElement::class, $subject->create($globalOptions));
	}

	/**
	 * @test
	 */
	public function createReturnsInstanceOfSelectSingleElementIfCheckboxIsConfiguredButMaxitemsIsOne() {
		$globalOptions = array(
			'type' => 'select',
			'parameterArray' => array(
				'fieldConf' => array(
					'config' => array(
						'renderMode' => 'checkbox',
						'maxitems' => 1,
					),
				),
			),
		);
		$subject = new NodeFactory();
		$this->assertInstanceOf(Element\SelectSingleElement::class, $subject->create($globalOptions));
	}

	/**
	 * @test
	 */
	public function createReturnsInstanceOfSelectSingleBoxElementIfNeeded() {
		$globalOptions = array(
			'type' => 'select',
			'parameterArray' => array(
				'fieldConf' => array(
					'config' => array(
						'renderMode' => 'singlebox',
						'maxitems' => 2,
					),
				),
			),
		);
		$subject = new NodeFactory();
		$this->assertInstanceOf(Element\SelectSingleBoxElement::class, $subject->create($globalOptions));
	}

	/**
	 * @test
	 */
	public function createReturnsInstanceOfSelectCheckBoxElementIfNeeded() {
		$globalOptions = array(
			'type' => 'select',
			'parameterArray' => array(
				'fieldConf' => array(
					'config' => array(
						'renderMode' => 'checkbox',
						'maxitems' => 2,
					),
				),
			),
		);
		$subject = new NodeFactory();
		$this->assertInstanceOf(Element\SelectCheckBoxElement::class, $subject->create($globalOptions));
	}

	/**
	 * @test
	 */
	public function createReturnsInstanceOfSelectMultipleSideBySideElementIfNeeded() {
		$globalOptions = array(
			'type' => 'select',
			'parameterArray' => array(
				'fieldConf' => array(
					'config' => array(
						'maxitems' => 2,
					),
				),
			),
		);
		$subject = new NodeFactory();
		$this->assertInstanceOf(Element\SelectMultipleSideBySideElement::class, $subject->create($globalOptions));
	}

}
