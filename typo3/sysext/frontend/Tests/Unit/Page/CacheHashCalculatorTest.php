<?php
namespace TYPO3\CMS\Frontend\Tests\Unit\Page;

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
 * @author 2012 Tolleiv Nietsch <typo3@tolleiv.de>
 */
class CacheHashCalculatorTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\CMS\Frontend\Page\CacheHashCalculator
	 */
	protected $fixture;

	/**
	 * @var array
	 */
	protected $confCache = array();

	public function setUp() {
		$this->confCache = array(
			'encryptionKey' => $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey']
		);
		$GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] = 't3lib_cacheHashTest';
		$this->fixture = $this->getMock('TYPO3\\CMS\\Frontend\\Page\\CacheHashCalculator', array('foo'));
		$this->fixture->setConfiguration(array(
			'excludedParameters' => array('exclude1', 'exclude2'),
			'cachedParametersWhiteList' => array(),
			'requireCacheHashPresenceParameters' => array('req1', 'req2'),
			'excludedParametersIfEmpty' => array(),
			'includePageId' => TRUE,
			'excludeAllEmptyParameters' => FALSE
		));
	}

	/**
	 * @dataProvider cacheHashCalculationDataProvider
	 * @test
	 */
	public function cacheHashCalculationWorks($params, $expected) {
		$this->assertEquals($expected, $this->fixture->calculateCacheHash($params));
	}

	/**
	 * @return array
	 */
	public function cacheHashCalculationDataProvider() {
		return array(
			'Empty parameters should not return a hash' => array(
				array(),
				''
			),
			'Trivial key value combination should generate hash' => array(
				array(
					'encryptionKey' => 't3lib_cacheHashTest',
					'key' => 'value'
				),
				'5cfdcf826275558b3613dd51714a0a17'
			),
			'Multiple parameters should generate hash' => array(
				array(
					'a' => 'v',
					'b' => 'v',
					'encryptionKey' => 't3lib_cacheHashTest'
				),
				'0f40b089cdad149aea99e9bf4badaa93'
			)
		);
	}

	/**
	 * @dataProvider getRelevantParametersDataprovider
	 * @test
	 */
	public function getRelevantParametersWorks($params, $expected) {
		$actual = $this->fixture->getRelevantParameters($params);
		$this->assertEquals($expected, array_keys($actual));
	}

	/**
	 * @return array
	 */
	public function getRelevantParametersDataprovider() {
		return array(
			'Empty list should be passed through' => array('', array()),
			'Simple parameter should be passed through and the encryptionKey should be added' => array(
				'key=v&id=42',
				array('encryptionKey', 'id', 'key')
			),
			'Simple parameter should be passed through' => array(
				'key1=v&key2=v&id=42',
				array('encryptionKey', 'id', 'key1', 'key2')
			),
			'System and exclude parameters should be omitted' => array(
				'id=1&type=3&exclude1=x&no_cache=1',
				array()
			),
			'System and exclude parameters (except id) should be omitted, others should stay' => array(
				'id=1&type=3&key=x&no_cache=1',
				array('encryptionKey', 'id', 'key')
			),
			'System and exclude parameters should be omitted and id is not required to be specified' => array(
				'&type=3&no_cache=1',
				array()
			)
		);
	}

	/**
	 * @dataProvider canGenerateForParametersDataProvider
	 * @test
	 */
	public function canGenerateForParameters($params, $expected) {
		$this->assertEquals($expected, $this->fixture->generateForParameters($params));
	}

	/**
	 * @test
	 * @expectedException \RuntimeException
	 * @expectedExceptionCode 1467983513
	 */
	public function generateForParametersThrowsExceptionWhenIdIsNotSpecified() {
		$this->fixture->generateForParameters('&key=x');
	}

	/**
	 * @return array
	 */
	public function canGenerateForParametersDataProvider() {
		$knowHash = 'fac112f7e662c83c19b57142c3a921f5';
		return array(
			'Empty parameters should not return an hash' => array('&id=42', ''),
			'Querystring has no relevant parameters so we should not have a cacheHash' => array('&exclude1=val', ''),
			'Querystring has only system parameters so we should not have a cacheHash' => array('&id=42&type=val', ''),
			'Trivial key value combination should generate hash' => array('&id=42&key=value', $knowHash),
			'Only the relevant parts should be taken into account' => array('&id=42&key=value&exclude1=val', $knowHash),
			'Only the relevant parts should be taken into account(exclude2 before key)' => array('&id=42&exclude2=val&key=value', $knowHash),
			'System parameters should not be taken into account (except id)' => array('&id=42&type=23&key=value', $knowHash),
			'Admin panel parameters should not be taken into account' => array('&id=42&TSFE_ADMIN_PANEL[display]=7&key=value', $knowHash),
			'Trivial hash for sorted parameters should be right' => array('&id=42&a=v&b=v', '52c8a1299e20324f90377c43153c4987'),
			'Parameters should be sorted before cHash is created' => array('&id=42&b=v&a=v', '52c8a1299e20324f90377c43153c4987'),
			'Empty argument names are filtered out before cHash calculation' => array('&id=42&b=v&a=v&=dummy', '52c8a1299e20324f90377c43153c4987')
		);
	}

	/**
	 * @dataProvider parametersRequireCacheHashDataprovider
	 * @test
	 */
	public function parametersRequireCacheHashWorks($params, $expected) {
		$this->assertEquals($expected, $this->fixture->doParametersRequireCacheHash($params));
	}

	/**
	 * @return array
	 */
	public function parametersRequireCacheHashDataProvider() {
		return array(
			'Empty parameter strings should not require anything.' => array('', FALSE),
			'Normal parameters aren\'t required.' => array('key=value', FALSE),
			'Configured "req1" to be required.' => array('req1=value', TRUE),
			'Configured "req1" to be required, should also work in combined context' => array('&key=value&req1=value', TRUE),
			'Configured "req1" to be required, should also work in combined context (key at the end)' => array('req1=value&key=value', TRUE)
		);
	}

	/**
	 * In case the cHashOnlyForParameters is set, other parameters should not
	 * incluence the cHash (except the encryption key of course)
	 *
	 * @dataProvider canWhitelistParametersDataProvider
	 * @test
	 */
	public function canWhitelistParameters($params, $expected) {
		$method = new \ReflectionMethod('TYPO3\\CMS\\Frontend\\Page\\CacheHashCalculator', 'setCachedParametersWhiteList');
		$method->setAccessible(TRUE);
		$method->invoke($this->fixture, array('whitep1', 'whitep2'));
		$this->assertEquals($expected, $this->fixture->generateForParameters($params));
	}

	/**
	 * @return array
	 */
	public function canWhitelistParametersDataProvider() {
		$oneParamHash = 'eae50a13101afd53a9d2c543230eb5bb';
		$twoParamHash = '701e2d2f1becc9d1b71d327e5cb1c3ed';
		return array(
			'Even with the whitelist enabled, empty parameters should not return an hash.' => array('', ''),
			'Whitelisted parameters should have a hash.' => array('&id=42&whitep1=value', $oneParamHash),
			'Blacklisted parameter should not influence hash.' => array('&id=42&whitep1=value&black=value', $oneParamHash),
			'Multiple whitelisted parameters should work' => array('&id=42&whitep1=value&whitep2=value', $twoParamHash),
			'The order should not influce the hash.' => array('&id=42&whitep2=value&black=value&whitep1=value', $twoParamHash)
		);
	}

	/**
	 * @dataProvider canSkipParametersWithEmptyValuesDataProvider
	 * @test
	 */
	public function canSkipParametersWithEmptyValues($params, $settings, $expected) {
		$this->fixture->setConfiguration($settings);
		$actual = $this->fixture->getRelevantParameters($params);
		$this->assertEquals($expected, array_keys($actual));
	}

	/**
	 * @return array
	 */
	public function canSkipParametersWithEmptyValuesDataProvider() {
		return array(
			'The default configuration does not allow to skip an empty key.' => array(
				'&id=42&key1=v&key2=&key3=',
				array('excludedParametersIfEmpty' => array(), 'excludeAllEmptyParameters' => FALSE),
				array('encryptionKey', 'id', 'key1', 'key2', 'key3')
			),
			'Due to the empty value, "key2" should be skipped(with equals sign' => array(
				'&id=42&key1=v&key2=&key3=',
				array('excludedParametersIfEmpty' => array('key2'), 'excludeAllEmptyParameters' => FALSE),
				array('encryptionKey', 'id', 'key1', 'key3')
			),
			'Due to the empty value, "key2" should be skipped(without equals sign)' => array(
				'&id=42&key1=v&key2&key3',
				array('excludedParametersIfEmpty' => array('key2'), 'excludeAllEmptyParameters' => FALSE),
				array('encryptionKey', 'id', 'key1', 'key3')
			),
			'Due to the empty value, "key2" and "key3" should be skipped' => array(
				'&id=42&key1=v&key2=&key3=',
				array('excludedParametersIfEmpty' => array(), 'excludeAllEmptyParameters' => TRUE),
				array('encryptionKey', 'id', 'key1')
			)
		);
	}

}