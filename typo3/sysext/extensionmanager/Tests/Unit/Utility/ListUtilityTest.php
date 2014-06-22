<?php
namespace TYPO3\CMS\Extensionmanager\Tests\Unit\Utility;

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
use TYPO3\CMS\Cms\Package;

/**
 * List utility test
 *
 */
class ListUtilityTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\CMS\Extensionmanager\Utility\ListUtility
	 */
	protected $subject;

	/**
	 * @return void
	 */
	public function setUp() {
		$this->subject = $this->getMock('TYPO3\\CMS\\Extensionmanager\\Utility\\ListUtility', array('emitPackagesMayHaveChangedSignal'));
		$packageManagerMock = $this->getMock('TYPO3\\CMS\\Core\\Package\\PackageManager');
		$packageManagerMock
				->expects($this->any())
				->method('getActivePackages')
				->will($this->returnValue(array(
					'cms' => $this->getMock('TYPO3\\CMS\\Cms\\Package', array(), array(), '', FALSE),
					'lang' => $this->getMock('TYPO3\\CMS\\Cms\\Package', array(), array(), '', FALSE),
					'news' => $this->getMock('TYPO3\\CMS\\Cms\\Package', array(), array(), '', FALSE),
					'saltedpasswords' => $this->getMock('TYPO3\\CMS\\Cms\\Package', array(), array(), '', FALSE),
					'rsaauth' => $this->getMock('TYPO3\\CMS\\Cms\\Package', array(), array(), '', FALSE),
				)));
		$this->inject($this->subject, 'packageManager', $packageManagerMock);
	}

	/**
	 * @return array
	 */
	public function getAvailableAndInstalledExtensionsDataProvider() {
		return array(
			'same extension lists' => array(
				array(
					'cms' => array(),
					'lang' => array(),
					'news' => array(),
					'saltedpasswords' => array(),
					'rsaauth' => array()
				),
				array(
					'cms' => array('installed' => TRUE),
					'lang' => array('installed' => TRUE),
					'news' => array('installed' => TRUE),
					'saltedpasswords' => array('installed' => TRUE),
					'rsaauth' => array('installed' => TRUE)
				)
			),
			'different extension lists' => array(
				array(
					'cms' => array(),
					'lang' => array(),
					'news' => array(),
					'saltedpasswords' => array(),
					'rsaauth' => array()
				),
				array(
					'cms' => array('installed' => TRUE),
					'lang' => array('installed' => TRUE),
					'news' => array('installed' => TRUE),
					'saltedpasswords' => array('installed' => TRUE),
					'rsaauth' => array('installed' => TRUE)
				)
			),
			'different extension lists - set2' => array(
				array(
					'cms' => array(),
					'lang' => array(),
					'news' => array(),
					'saltedpasswords' => array(),
					'rsaauth' => array(),
					'em' => array()
				),
				array(
					'cms' => array('installed' => TRUE),
					'lang' => array('installed' => TRUE),
					'news' => array('installed' => TRUE),
					'saltedpasswords' => array('installed' => TRUE),
					'rsaauth' => array('installed' => TRUE),
					'em' => array()
				)
			),
			'different extension lists - set3' => array(
				array(
					'cms' => array(),
					'lang' => array(),
					'fluid' => array(),
					'news' => array(),
					'saltedpasswords' => array(),
					'rsaauth' => array(),
					'em' => array()
				),
				array(
					'cms' => array('installed' => TRUE),
					'lang' => array('installed' => TRUE),
					'fluid' => array(),
					'news' => array('installed' => TRUE),
					'saltedpasswords' => array('installed' => TRUE),
					'rsaauth' => array('installed' => TRUE),
					'em' => array()
				)
			)
		);
	}

	/**
	 * @test
	 * @dataProvider getAvailableAndInstalledExtensionsDataProvider
	 * @param $availableExtensions
	 * @param $expectedResult
	 * @return void
	 */
	public function getAvailableAndInstalledExtensionsTest($availableExtensions, $expectedResult) {
		$this->assertEquals($expectedResult, $this->subject->getAvailableAndInstalledExtensions($availableExtensions));
	}

	/**
	 * @return array
	 */
	public function enrichExtensionsWithEmConfInformationDataProvider() {
		return array(
			'simple key value array emconf' => array(
				array(
					'cms' => array('test' => 'test2'),
					'lang' => array('property1' => 'oldvalue'),
					'news' => array(),
					'saltedpasswords' => array(),
					'rsaauth' => array()
				),
				array(
					'property1' => 'property value1'
				),
				array(
					'cms' => array('test' => 'test2', 'property1' => 'property value1'),
					'lang' => array('property1' => 'oldvalue'),
					'news' => array('property1' => 'property value1'),
					'saltedpasswords' => array('property1' => 'property value1'),
					'rsaauth' => array('property1' => 'property value1')
				)
			)
		);
	}

	/**
	 * @test
	 * @dataProvider enrichExtensionsWithEmConfInformationDataProvider
	 * @param $extensions
	 * @param $emConf
	 * @param $expectedResult
	 * @return void
	 */
	public function enrichExtensionsWithEmConfInformation($extensions, $emConf, $expectedResult) {
		$this->inject($this->subject, 'extensionRepository', $this->getAccessibleMock('TYPO3\\CMS\\Extensionmanager\\Domain\\Repository\\ExtensionRepository', array('findOneByExtensionKeyAndVersion'), array(), '', FALSE));
		$emConfUtilityMock = $this->getMock('TYPO3\\CMS\\Extensionmanager\\Utility\\EmConfUtility');
		$emConfUtilityMock->expects($this->any())->method('includeEmConf')->will($this->returnValue($emConf));
		$this->inject($this->subject, 'emConfUtility', $emConfUtilityMock);
		$this->assertEquals($expectedResult, $this->subject->enrichExtensionsWithEmConfAndTerInformation($extensions));
	}
}
