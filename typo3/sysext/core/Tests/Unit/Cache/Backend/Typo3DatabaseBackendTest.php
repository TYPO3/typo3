<?php
namespace TYPO3\CMS\Core\Tests\Unit\Cache\Backend;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2009-2013 Ingo Renner <ingo@typo3.org>
 *  (c) 2014 Christian Kuhn <lolli@schwarzbu.ch>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Test case
 *
 */
class Typo3DatabaseBackendTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * Helper method to inject a mock frontend to backend instance
	 *
	 * @param \TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend $backend Current backend instance
	 * @return \TYPO3\CMS\Core\Cache\Frontend\FrontendInterface Mock frontend
	 */
	protected function setUpMockFrontendOfBackend(\TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend $backend) {
		$mockCache = $this->getMock('TYPO3\\CMS\\Core\\Cache\\Frontend\\AbstractFrontend', array(), array(), '', FALSE);
		$mockCache->expects($this->any())->method('getIdentifier')->will($this->returnValue('Testing'));
		$backend->setCache($mockCache);
		return $mockCache;
	}

	/**
	 * @test
	 */
	public function setCacheCalculatesCacheTableName() {
		/** @var \TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend|\PHPUnit_Framework_MockObject_MockObject $backend */
		$backend = $this->getMock('TYPO3\\CMS\\Core\\Cache\\Backend\\Typo3DatabaseBackend', array('dummy'), array('Testing'));
		$this->setUpMockFrontendOfBackend($backend);
		$this->assertEquals('cf_Testing', $backend->getCacheTable());
	}

	/**
	 * @test
	 */
	public function setCacheCalculatesTagsTableName() {
		/** @var \TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend|\PHPUnit_Framework_MockObject_MockObject $backend */
		$backend = $this->getMock('TYPO3\\CMS\\Core\\Cache\\Backend\\Typo3DatabaseBackend', array('dummy'), array('Testing'));
		$this->setUpMockFrontendOfBackend($backend);
		$this->assertEquals('cf_Testing_tags', $backend->getTagsTable());
	}

	/**
	 * @test
	 * @expectedException \TYPO3\CMS\Core\Cache\Exception
	 */
	public function setThrowsExceptionIfFrontendWasNotSet() {
		/** @var \TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend|\PHPUnit_Framework_MockObject_MockObject $backend */
		$backend = $this->getMock('TYPO3\\CMS\\Core\\Cache\\Backend\\Typo3DatabaseBackend', array('dummy'), array('Testing'));
		$backend->set('identifier', 'data');
	}

	/**
	 * @test
	 * @expectedException \TYPO3\CMS\Core\Cache\Exception\InvalidDataException
	 */
	public function setThrowsExceptionIfDataIsNotAString() {
		/** @var \TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend|\PHPUnit_Framework_MockObject_MockObject $backend */
		$backend = $this->getMock('TYPO3\\CMS\\Core\\Cache\\Backend\\Typo3DatabaseBackend', array('dummy'), array('Testing'));
		$this->setUpMockFrontendOfBackend($backend);
		$data = array('Some data');
		$entryIdentifier = 'BackendDbTest';
		$backend->set($entryIdentifier, $data);
	}

	/**
	 * @test
	 */
	public function setInsertsEntryInTable() {
		/** @var \TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend|\PHPUnit_Framework_MockObject_MockObject $backend */
		$backend = $this->getMock('TYPO3\\CMS\\Core\\Cache\\Backend\\Typo3DatabaseBackend', array('dummy'), array('Testing'));
		$this->setUpMockFrontendOfBackend($backend);
		$GLOBALS['TYPO3_DB'] = $this->getMock('TYPO3\\CMS\\Core\\Database\\DatabaseConnection', array(), array(), '', FALSE);
		$GLOBALS['TYPO3_DB']
			->expects($this->once())
			->method('exec_INSERTquery')
			->with('cf_Testing', $this->callback(function (array $data) {
				if ($data['content'] !== 'someData') {
					return FALSE;
				}
				if ($data['identifier'] !== 'anIdentifier') {
					return FALSE;
				}
				return TRUE;
			}));
		$backend->set('anIdentifier', 'someData');
	}

	/**
	 * @test
	 */
	public function setRemovesAnAlreadyExistingCacheEntryForTheSameIdentifier() {
		/** @var \TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend|\PHPUnit_Framework_MockObject_MockObject $backend */
		$backend = $this->getMock('TYPO3\\CMS\\Core\\Cache\\Backend\\Typo3DatabaseBackend', array('remove'), array('Testing'));
		$this->setUpMockFrontendOfBackend($backend);
		$GLOBALS['TYPO3_DB'] = $this->getMock('TYPO3\\CMS\\Core\\Database\\DatabaseConnection', array(), array(), '', FALSE);

		$backend->expects($this->once())->method('remove');
		$data = uniqid('someData');
		$entryIdentifier = 'anIdentifier';
		$backend->set($entryIdentifier, $data, array(), 500);
	}

	/**
	 * @test
	 */
	public function setReallySavesSpecifiedTags() {
		/** @var \TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend|\PHPUnit_Framework_MockObject_MockObject $backend */
		$backend = $this->getMock('TYPO3\\CMS\\Core\\Cache\\Backend\\Typo3DatabaseBackend', array('dummy'), array('Testing'));
		$this->setUpMockFrontendOfBackend($backend);
		$GLOBALS['TYPO3_DB'] = $this->getMock('TYPO3\\CMS\\Core\\Database\\DatabaseConnection', array(), array(), '', FALSE);
		$GLOBALS['TYPO3_DB']
			->expects($this->once())
			->method('exec_INSERTmultipleRows')
			->with(
				'cf_Testing_tags',
				$this->callback(function (array $data) {
					if ($data[0] === 'identifier' && $data[1] === 'tag') {
						return TRUE;
					}
					return FALSE;
				}),
				$this->callback(function (array $data) {
					if ($data[0][0] !== 'anIdentifier' || $data[0][1] !== 'UnitTestTag%tag1') {
						return FALSE;
					}
					if ($data[1][0] !== 'anIdentifier' || $data[1][1] !== 'UnitTestTag%tag2') {
						return FALSE;
					}
					return TRUE;
				})
			);
		$backend->set('anIdentifier', 'someData', array('UnitTestTag%tag1', 'UnitTestTag%tag2'));
	}

	/**
	 * @test
	 */
	public function setSavesCompressedDataWithEnabledCompression() {
		$backendOptions = array(
			'compression' => TRUE
		);
		/** @var \TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend|\PHPUnit_Framework_MockObject_MockObject $backend */
		$backend = $this->getMock(
			'TYPO3\\CMS\\Core\\Cache\\Backend\\Typo3DatabaseBackend',
			array('dummy'),
			array('Testing', $backendOptions)
		);
		$this->setUpMockFrontendOfBackend($backend);

		$GLOBALS['TYPO3_DB'] = $this->getMock('TYPO3\\CMS\\Core\\Database\\DatabaseConnection', array(), array(), '', FALSE);
		$GLOBALS['TYPO3_DB']
			->expects($this->once())
			->method('exec_INSERTquery')
			->with(
				'cf_Testing',
				$this->callback(function (array $data) {
					if (@gzuncompress($data['content']) === 'someData') {
						return TRUE;
					}
					return FALSE;
				}
			));

		$backend->set('anIdentifier', 'someData');
	}

	/**
	 * @test
	 */
	public function setWithUnlimitedLifetimeWritesCorrectEntry() {
		/** @var \TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend|\PHPUnit_Framework_MockObject_MockObject $backend */
		$backend = $this->getMock('TYPO3\\CMS\\Core\\Cache\\Backend\\Typo3DatabaseBackend', array('dummy'), array('Testing'));
		$this->setUpMockFrontendOfBackend($backend);

		$GLOBALS['TYPO3_DB'] = $this->getMock('TYPO3\\CMS\\Core\\Database\\DatabaseConnection', array(), array(), '', FALSE);
		$GLOBALS['TYPO3_DB']
			->expects($this->once())
			->method('exec_INSERTquery')
			->with(
				'cf_Testing',
				$this->callback(function (array $data) {
					$lifetime = $data['expires'];
					if ($lifetime > 2000000000) {
						return TRUE;
					}
					return FALSE;
				}
			));

		$backend->set('aIdentifier', 'someData', array(), 0);
	}

	/**
	 * @test
	 * @expectedException \TYPO3\CMS\Core\Cache\Exception
	 */
	public function getThrowsExceptionIfFrontendWasNotSet() {
		/** @var \TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend|\PHPUnit_Framework_MockObject_MockObject $backend */
		$backend = $this->getMock('TYPO3\\CMS\\Core\\Cache\\Backend\\Typo3DatabaseBackend', array('dummy'), array('Testing'));
		$backend->get('identifier');
	}

	/**
	 * @test
	 */
	public function getReturnsContentOfTheCorrectCacheEntry() {
		/** @var \TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend|\PHPUnit_Framework_MockObject_MockObject $backend */
		$backend = $this->getMock('TYPO3\\CMS\\Core\\Cache\\Backend\\Typo3DatabaseBackend', array('dummy'), array('Testing'));
		$this->setUpMockFrontendOfBackend($backend);

		$GLOBALS['TYPO3_DB'] = $this->getMock('TYPO3\\CMS\\Core\\Database\\DatabaseConnection', array(), array(), '', FALSE);
		$GLOBALS['TYPO3_DB']
			->expects($this->once())
			->method('exec_SELECTgetSingleRow')
			->with('content', 'cf_Testing', $this->anything())
			->will($this->returnValue(array('content' => 'someData')));

		$loadedData = $backend->get('aIdentifier');
		$this->assertEquals('someData', $loadedData);
	}

	/**
	 * @test
	 */
	public function getSetsExceededLifetimeQueryPart() {
		/** @var \TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend|\PHPUnit_Framework_MockObject_MockObject $backend */
		$backend = $this->getMock('TYPO3\\CMS\\Core\\Cache\\Backend\\Typo3DatabaseBackend', array('dummy'), array('Testing'));
		$this->setUpMockFrontendOfBackend($backend);

		$GLOBALS['TYPO3_DB'] = $this->getMock('TYPO3\\CMS\\Core\\Database\\DatabaseConnection', array(), array(), '', FALSE);
		$GLOBALS['TYPO3_DB']
			->expects($this->once())
			->method('exec_SELECTgetSingleRow')
			->with(
				'content',
				'cf_Testing',
				$this->stringContains('identifier =  AND cf_Testing.expires >=')
			);

		$backend->get('aIdentifier');
	}

	/**
	 * @test
	 * @expectedException \TYPO3\CMS\Core\Cache\Exception
	 */
	public function hasThrowsExceptionIfFrontendWasNotSet() {
		/** @var \TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend|\PHPUnit_Framework_MockObject_MockObject $backend */
		$backend = $this->getMock('TYPO3\\CMS\\Core\\Cache\\Backend\\Typo3DatabaseBackend', array('dummy'), array('Testing'));
		$backend->has('identifier');
	}

	/**
	 * @test
	 */
	public function hasReturnsTrueForExistingEntry() {
		/** @var \TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend|\PHPUnit_Framework_MockObject_MockObject $backend */
		$backend = $this->getMock('TYPO3\\CMS\\Core\\Cache\\Backend\\Typo3DatabaseBackend', array('dummy'), array('Testing'));
		$this->setUpMockFrontendOfBackend($backend);

		$GLOBALS['TYPO3_DB'] = $this->getMock('TYPO3\\CMS\\Core\\Database\\DatabaseConnection', array(), array(), '', FALSE);
		$GLOBALS['TYPO3_DB']
			->expects($this->once())
			->method('exec_SELECTcountRows')
			->with('*', 'cf_Testing', $this->anything())
			->will($this->returnValue(1));

		$this->assertTrue($backend->has('aIdentifier'));
	}

	/**
	 * @test
	 */
	public function hasSetsExceededLifetimeQueryPart() {
		/** @var \TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend|\PHPUnit_Framework_MockObject_MockObject $backend */
		$backend = $this->getMock('TYPO3\\CMS\\Core\\Cache\\Backend\\Typo3DatabaseBackend', array('dummy'), array('Testing'));
		$this->setUpMockFrontendOfBackend($backend);

		$GLOBALS['TYPO3_DB'] = $this->getMock('TYPO3\\CMS\\Core\\Database\\DatabaseConnection', array(), array(), '', FALSE);
		$GLOBALS['TYPO3_DB']
			->expects($this->once())
			->method('exec_SELECTcountRows')
			->with(
				'*',
				'cf_Testing',
				$this->stringContains('identifier =  AND cf_Testing.expires >='))
			->will($this->returnValue(1));

		$this->assertTrue($backend->has('aIdentifier'));
	}

	/**
	 * @test
	 * @expectedException \TYPO3\CMS\Core\Cache\Exception
	 */
	public function removeThrowsExceptionIfFrontendWasNotSet() {
		/** @var \TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend|\PHPUnit_Framework_MockObject_MockObject $backend */
		$backend = $this->getMock('TYPO3\\CMS\\Core\\Cache\\Backend\\Typo3DatabaseBackend', array('dummy'), array('Testing'));
		$backend->remove('identifier');
	}

	/**
	 * @test
	 */
	public function removeReallyRemovesACacheEntry() {
		/** @var \TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend|\PHPUnit_Framework_MockObject_MockObject $backend */
		$backend = $this->getMock('TYPO3\\CMS\\Core\\Cache\\Backend\\Typo3DatabaseBackend', array('dummy'), array('Testing'));
		$this->setUpMockFrontendOfBackend($backend);

		$GLOBALS['TYPO3_DB'] = $this->getMock('TYPO3\\CMS\\Core\\Database\\DatabaseConnection', array(), array(), '', FALSE);
		$GLOBALS['TYPO3_DB']
			->expects($this->at(0))
			->method('fullQuoteStr')
			->will($this->returnValue('aIdentifier'));
		$GLOBALS['TYPO3_DB']
			->expects($this->at(1))
			->method('exec_DELETEquery')
			->with('cf_Testing', "identifier = aIdentifier");
		$GLOBALS['TYPO3_DB']
			->expects($this->at(2))
			->method('fullQuoteStr')
			->will($this->returnValue('aIdentifier'));
		$GLOBALS['TYPO3_DB']
			->expects($this->at(3))
			->method('exec_DELETEquery')
			->with('cf_Testing_tags', "identifier = aIdentifier");

		$backend->remove('aIdentifier');
	}

	/**
	 * @test
	 * @expectedException \TYPO3\CMS\Core\Cache\Exception
	 */
	public function collectGarbageThrowsExceptionIfFrontendWasNotSet() {
		/** @var \TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend|\PHPUnit_Framework_MockObject_MockObject $backend */
		$backend = $this->getMock('TYPO3\\CMS\\Core\\Cache\\Backend\\Typo3DatabaseBackend', array('dummy'), array('Testing'));
		$backend->collectGarbage();
	}

	/**
	 * @test
	 */
	public function collectGarbageSelectsExpiredCacheEntries() {
		/** @var \TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend|\PHPUnit_Framework_MockObject_MockObject $backend */
		$backend = $this->getMock('TYPO3\\CMS\\Core\\Cache\\Backend\\Typo3DatabaseBackend', array('dummy'), array('Testing'));
		$this->setUpMockFrontendOfBackend($backend);

		$GLOBALS['TYPO3_DB'] = $this->getMock('TYPO3\\CMS\\Core\\Database\\DatabaseConnection', array(), array(), '', FALSE);
		$GLOBALS['TYPO3_DB']
			->expects($this->once())
			->method('exec_SELECTquery')
			->with('identifier', 'cf_Testing');
		$backend->collectGarbage();
	}

	/**
	 * @test
	 */
	public function collectGarbageDeletesTagsFromExpiredEntries() {
		/** @var \TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend|\PHPUnit_Framework_MockObject_MockObject $backend */
		$backend = $this->getMock('TYPO3\\CMS\\Core\\Cache\\Backend\\Typo3DatabaseBackend', array('dummy'), array('Testing'));
		$this->setUpMockFrontendOfBackend($backend);

		$GLOBALS['TYPO3_DB'] = $this->getMock('TYPO3\\CMS\\Core\\Database\\DatabaseConnection', array(), array(), '', FALSE);
		$GLOBALS['TYPO3_DB']
			->expects($this->at(1))
			->method('sql_fetch_assoc')
			->will($this->returnValue(array('identifier' => 'aIdentifier')));
		$GLOBALS['TYPO3_DB']
			->expects($this->at(2))
			->method('fullQuoteStr')
			->will($this->returnValue('aIdentifier'));
		$GLOBALS['TYPO3_DB']
			->expects($this->at(3))
			->method('sql_fetch_assoc')
			->will($this->returnValue(FALSE));
		$GLOBALS['TYPO3_DB']
			->expects($this->at(5))
			->method('exec_DELETEquery')
			->with('cf_Testing_tags', 'identifier IN (aIdentifier)');

		$backend->collectGarbage();
	}

	/**
	 * @test
	 */
	public function collectGarbageDeletesExpiredEntry() {
		/** @var \TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend|\PHPUnit_Framework_MockObject_MockObject $backend */
		$backend = $this->getMock('TYPO3\\CMS\\Core\\Cache\\Backend\\Typo3DatabaseBackend', array('dummy'), array('Testing'));
		$this->setUpMockFrontendOfBackend($backend);

		$GLOBALS['TYPO3_DB'] = $this->getMock('TYPO3\\CMS\\Core\\Database\\DatabaseConnection', array(), array(), '', FALSE);
		$GLOBALS['TYPO3_DB']
			->expects($this->at(1))
			->method('sql_fetch_assoc')
			->will($this->returnValue(FALSE));
		$GLOBALS['TYPO3_DB']
			->expects($this->at(3))
			->method('exec_DELETEquery')
			->with('cf_Testing', $this->stringContains('cf_Testing.expires < '));

		$backend->collectGarbage();
	}

	/**
	 * @test
	 * @expectedException \TYPO3\CMS\Core\Cache\Exception
	 */
	public function findIdentifiersByTagThrowsExceptionIfFrontendWasNotSet() {
		/** @var \TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend|\PHPUnit_Framework_MockObject_MockObject $backend */
		$backend = $this->getMock('TYPO3\\CMS\\Core\\Cache\\Backend\\Typo3DatabaseBackend', array('dummy'), array('Testing'));
		$backend->findIdentifiersByTag('identifier');
	}

	/**
	 * @test
	 */
	public function findIdentifiersByTagFindsCacheEntriesWithSpecifiedTag() {
		/** @var \TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend|\PHPUnit_Framework_MockObject_MockObject $backend */
		$backend = $this->getMock('TYPO3\\CMS\\Core\\Cache\\Backend\\Typo3DatabaseBackend', array('dummy'), array('Testing'));
		$this->setUpMockFrontendOfBackend($backend);

		$GLOBALS['TYPO3_DB'] = $this->getMock('TYPO3\\CMS\\Core\\Database\\DatabaseConnection', array(), array(), '', FALSE);
		$GLOBALS['TYPO3_DB']
			->expects($this->at(0))
			->method('fullQuoteStr')
			->will($this->returnValue('cf_Testing_tags'));
		$GLOBALS['TYPO3_DB']
			->expects($this->at(1))
			->method('exec_SELECTgetRows')
			->with(
				'cf_Testing.identifier',
				'cf_Testing, cf_Testing_tags',
				$this->stringContains('cf_Testing_tags.tag = cf_Testing_tags AND cf_Testing.identifier = cf_Testing_tags.identifier AND cf_Testing.expires >= '),
				'cf_Testing.identifier'
			)
			->will($this->returnValue(array(array('identifier' => 'aIdentifier'))));
		$this->assertSame(array('aIdentifier' => 'aIdentifier'), $backend->findIdentifiersByTag('aTag'));
	}

	/**
	 * @test
	 * @expectedException \TYPO3\CMS\Core\Cache\Exception
	 */
	public function flushThrowsExceptionIfFrontendWasNotSet() {
		/** @var \TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend|\PHPUnit_Framework_MockObject_MockObject $backend */
		$backend = $this->getMock('TYPO3\\CMS\\Core\\Cache\\Backend\\Typo3DatabaseBackend', array('dummy'), array('Testing'));
		$backend->flush();
	}

	/**
	 * @test
	 */
	public function flushRemovesAllCacheEntries() {
		/** @var \TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend|\PHPUnit_Framework_MockObject_MockObject $backend */
		$backend = $this->getMock('TYPO3\\CMS\\Core\\Cache\\Backend\\Typo3DatabaseBackend', array('dummy'), array('Testing'));
		$this->setUpMockFrontendOfBackend($backend);

		$GLOBALS['TYPO3_DB'] = $this->getMock('TYPO3\\CMS\\Core\\Database\\DatabaseConnection', array(), array(), '', FALSE);
		$GLOBALS['TYPO3_DB']
			->expects($this->at(0))
			->method('exec_TRUNCATEquery')
			->with('cf_Testing');
		$GLOBALS['TYPO3_DB']
			->expects($this->at(1))
			->method('exec_TRUNCATEquery')
			->with('cf_Testing_tags');

		$backend->flush();
	}

	/**
	 * @test
	 * @expectedException \TYPO3\CMS\Core\Cache\Exception
	 */
	public function flushByTagThrowsExceptionIfFrontendWasNotSet() {
		/** @var \TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend|\PHPUnit_Framework_MockObject_MockObject $backend */
		$backend = $this->getMock('TYPO3\\CMS\\Core\\Cache\\Backend\\Typo3DatabaseBackend', array('dummy'), array('Testing'));
		$backend->flushByTag(array());
	}

	/**
	 * @test
	 */
	public function flushByTagRemovesCacheEntriesWithSpecifiedTag() {
		/** @var \TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend|\PHPUnit_Framework_MockObject_MockObject $backend */
		$backend = $this->getMock('TYPO3\\CMS\\Core\\Cache\\Backend\\Typo3DatabaseBackend', array('dummy'), array('Testing'));
		$this->setUpMockFrontendOfBackend($backend);

		$GLOBALS['TYPO3_DB'] = $this->getMock('TYPO3\\CMS\\Core\\Database\\DatabaseConnection', array(), array(), '', FALSE);
		$GLOBALS['TYPO3_DB']
			->expects($this->at(0))
			->method('fullQuoteStr')
			->will($this->returnValue('UnitTestTag%special'));
		$GLOBALS['TYPO3_DB']
			->expects($this->at(1))
			->method('exec_SELECTquery')
			->with(
				'DISTINCT identifier',
				'cf_Testing_tags',
				'cf_Testing_tags.tag = UnitTestTag%special'
			);
		$GLOBALS['TYPO3_DB']
			->expects($this->at(2))
			->method('sql_fetch_assoc')
			->will($this->returnValue(array('identifier' => 'BackendDbTest1')));
		$GLOBALS['TYPO3_DB']
			->expects($this->at(3))
			->method('fullQuoteStr')
			->with('BackendDbTest1', 'cf_Testing')
			->will($this->returnValue('BackendDbTest1'));
		$GLOBALS['TYPO3_DB']
			->expects($this->at(4))
			->method('sql_fetch_assoc')
			->will($this->returnValue(array('identifier' => 'BackendDbTest2')));
		$GLOBALS['TYPO3_DB']
			->expects($this->at(5))
			->method('fullQuoteStr')
			->with('BackendDbTest2', 'cf_Testing')
			->will($this->returnValue('BackendDbTest2'));
		$GLOBALS['TYPO3_DB']
			->expects($this->at(6))
			->method('sql_fetch_assoc')
			->will($this->returnValue(FALSE));
		$GLOBALS['TYPO3_DB']
			->expects($this->at(7))
			->method('sql_free_result')
			->will($this->returnValue(TRUE));
		$GLOBALS['TYPO3_DB']
			->expects($this->at(8))
			->method('exec_DELETEquery')
			->with('cf_Testing', 'identifier IN (BackendDbTest1, BackendDbTest2)');
		$GLOBALS['TYPO3_DB']
			->expects($this->at(9))
			->method('exec_DELETEquery')
			->with('cf_Testing_tags', 'identifier IN (BackendDbTest1, BackendDbTest2)');

		$backend->flushByTag('UnitTestTag%special');
	}
}