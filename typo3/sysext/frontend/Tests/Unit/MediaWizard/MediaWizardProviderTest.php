<?php
namespace TYPO3\CMS\Frontend\Tests\Unit\MediaWizard;

/***************************************************************
 * Copyright notice
 *
 * (c) 2009-2013 Sebastian Fischer (typo3@marketing-factory.de)
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
/**
 * Testcase for TYPO3\CMS\Frontend\MediaWizard\MediaWizardProvider
 *
 * @author Sebastian Fischer <typo3@marketing-factory.de>
 */
class MediaWizardProviderTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var \PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\TYPO3\CMS\Frontend\MediaWizard\MediaWizardProvider
	 */
	protected $fixture;

	/**
	 * Setup
	 */
	public function setUp() {
		$this->fixture = $this->getAccessibleMock('\\TYPO3\\CMS\\Frontend\\MediaWizard\\MediaWizardProvider', array('dummy'), array(), '', FALSE);
	}

	/**
	 * @return array
	 */
	public function process_youtubeDataProvider() {
		return array(
			'http://youtu.be/2PMeCSQ--08' => array(
				'http://youtu.be/2PMeCSQ--08',
				'http://www.youtube.com/embed/2PMeCSQ--08?fs=1'
			),
			'http://www.youtube.com/embed/2PMeCSQ--08' => array(
				'http://www.youtube.com/embed/2PMeCSQ--08',
				'http://www.youtube.com/embed/2PMeCSQ--08?fs=1'
			),
			'http://www.youtube.com/watch?v=2PMeCSQ--08' => array(
				'http://www.youtube.com/watch?v=2PMeCSQ--08',
				'http://www.youtube.com/embed/2PMeCSQ--08?fs=1'
			),
			'http://www.youtube.com/?v=2PMeCSQ--08' => array(
				'http://www.youtube.com/?v=2PMeCSQ--08',
				'http://www.youtube.com/embed/2PMeCSQ--08?fs=1'
			),
			'http://www.youtube.com/v/2PMeCSQ--08' => array(
				'http://www.youtube.com/v/2PMeCSQ--08',
				'http://www.youtube.com/embed/2PMeCSQ--08?fs=1'
			),
			'http://www.youtube.com/e/2PMeCSQ--08' => array(
				'http://www.youtube.com/e/2PMeCSQ--08',
				'http://www.youtube.com/embed/2PMeCSQ--08?fs=1'
			),
			'http://www.youtube.com/watch?feature=player_embedded&v=2PMeCSQ--08' => array(
				'http://www.youtube.com/watch?feature=player_embedded&v=2PMeCSQ--08',
				'http://www.youtube.com/embed/2PMeCSQ--08?fs=1'
			),
			'http://www.youtube.com/watch?v=2PMeCSQ--08&list=PLGWGc5dfbzn_pvtJg7XskLva9XZpNTI88' => array(
				'http://www.youtube.com/watch?v=2PMeCSQ--08&list=PLGWGc5dfbzn_pvtJg7XskLva9XZpNTI88',
				'http://www.youtube.com/embed/2PMeCSQ--08?fs=1'
			),
		);
	}

	/**
	 * Test for process youtube
	 *
	 * @param string $url
	 * @param string $expected
	 * @return void
	 * @dataProvider process_youtubeDataProvider
	 * @test
	 */
	public function process_youtube($url, $expected) {
		$result = $this->fixture->_call('process_youtube', $url);
		$this->assertEquals($expected, $result);
	}
}
