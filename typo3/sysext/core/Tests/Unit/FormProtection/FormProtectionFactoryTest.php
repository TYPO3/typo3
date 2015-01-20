<?php
namespace TYPO3\CMS\Core\Tests\Unit\FormProtection;

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
 * Testcase
 *
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 * @author Ernesto Baschny <ernst@cron-it.de>
 */
class FormProtectionFactoryTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	public function setUp() {

	}

	public function tearDown() {
		\TYPO3\CMS\Core\FormProtection\FormProtectionFactory::purgeInstances();
		parent::tearDown();
	}

	/////////////////////////
	// Tests concerning get
	/////////////////////////
	/**
	 * @test
	 * @expectedException \InvalidArgumentException
	 */
	public function getForInexistentClassThrowsException() {
		\TYPO3\CMS\Core\FormProtection\FormProtectionFactory::get('noSuchClass');
	}

	/**
	 * @test
	 * @expectedException \InvalidArgumentException
	 */
	public function getForClassThatIsNoFormProtectionSubclassThrowsException() {
		\TYPO3\CMS\Core\FormProtection\FormProtectionFactory::get('TYPO3\\CMS\\Core\\FormProtection\\FormProtectionFactoryTest');
	}

	/**
	 * @test
	 */
	public function getForTypeBackEndWithExistingBackEndReturnsBackEndFormProtection() {
		$GLOBALS['BE_USER'] = $this->getMock('TYPO3\CMS\Core\Authentication\BackendUserAuthentication', array(), array(), '', FALSE);
		$GLOBALS['BE_USER']->user = array('uid' => $this->getUniqueId());
		$this->assertTrue(\TYPO3\CMS\Core\FormProtection\FormProtectionFactory::get('TYPO3\\CMS\\Core\\FormProtection\\BackendFormProtection') instanceof \TYPO3\CMS\Core\FormProtection\BackendFormProtection);
	}

	/**
	 * @test
	 */
	public function getForTypeBackEndCalledTwoTimesReturnsTheSameInstance() {
		$GLOBALS['BE_USER'] = $this->getMock('TYPO3\CMS\Core\Authentication\BackendUserAuthentication', array(), array(), '', FALSE);
		$GLOBALS['BE_USER']->user = array('uid' => $this->getUniqueId());
		$this->assertSame(\TYPO3\CMS\Core\FormProtection\FormProtectionFactory::get('TYPO3\\CMS\\Core\\FormProtection\\BackendFormProtection'), \TYPO3\CMS\Core\FormProtection\FormProtectionFactory::get('TYPO3\\CMS\\Core\\FormProtection\\BackendFormProtection'));
	}

	/**
	 * @test
	 */
	public function getForTypeInstallToolReturnsInstallToolFormProtection() {
		$this->assertTrue(\TYPO3\CMS\Core\FormProtection\FormProtectionFactory::get('TYPO3\\CMS\\Core\\FormProtection\\InstallToolFormProtection') instanceof \TYPO3\CMS\Core\FormProtection\InstallToolFormProtection);
	}

	/**
	 * @test
	 */
	public function getForTypeInstallToolCalledTwoTimesReturnsTheSameInstance() {
		$this->assertSame(\TYPO3\CMS\Core\FormProtection\FormProtectionFactory::get('TYPO3\\CMS\\Core\\FormProtection\\InstallToolFormProtection'), \TYPO3\CMS\Core\FormProtection\FormProtectionFactory::get('TYPO3\\CMS\\Core\\FormProtection\\InstallToolFormProtection'));
	}

	/**
	 * @test
	 */
	public function getForTypesInstallToolAndBackEndReturnsDifferentInstances() {
		$GLOBALS['BE_USER'] = $this->getMock('TYPO3\CMS\Core\Authentication\BackendUserAuthentication', array(), array(), '', FALSE);
		$GLOBALS['BE_USER']->user = array('uid' => $this->getUniqueId());
		$this->assertNotSame(\TYPO3\CMS\Core\FormProtection\FormProtectionFactory::get('TYPO3\\CMS\\Core\\FormProtection\\InstallToolFormProtection'), \TYPO3\CMS\Core\FormProtection\FormProtectionFactory::get('TYPO3\\CMS\\Core\\FormProtection\\BackendFormProtection'));
	}

	/////////////////////////
	// Tests concerning set
	/////////////////////////
	/**
	 * @test
	 */
	public function setSetsInstanceForType() {
		$instance = new \TYPO3\CMS\Core\Tests\Unit\FormProtection\Fixtures\FormProtectionTesting();
		\TYPO3\CMS\Core\FormProtection\FormProtectionFactory::set('TYPO3\\CMS\\Core\\FormProtection\\BackendFormProtection', $instance);
		$this->assertSame($instance, \TYPO3\CMS\Core\FormProtection\FormProtectionFactory::get('TYPO3\\CMS\\Core\\FormProtection\\BackendFormProtection'));
	}

	/**
	 * @test
	 */
	public function setNotSetsInstanceForOtherType() {
		$instance = new \TYPO3\CMS\Core\Tests\Unit\FormProtection\Fixtures\FormProtectionTesting();
		\TYPO3\CMS\Core\FormProtection\FormProtectionFactory::set('TYPO3\\CMS\\Core\\FormProtection\\BackendFormProtection', $instance);
		$this->assertNotSame($instance, \TYPO3\CMS\Core\FormProtection\FormProtectionFactory::get('TYPO3\\CMS\\Core\\FormProtection\\InstallToolFormProtection'));
	}

}
