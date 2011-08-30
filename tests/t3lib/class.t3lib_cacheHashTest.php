<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2012 Tolleiv Nietsch <typo3@tolleiv.de>
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
 * Testcase for class t3lib_cacheHeash
 *
 * @author 2012 Tolleiv Nietsch <typo3@tolleiv.de>
 *
 * @package TYPO3
 * @subpackage t3lib
 */
class t3lib_cacheHashTest extends tx_phpunit_testcase {

	/**
	 * @var t3lib_cacheHash
	 */
	protected $cacheHash;

	protected $confCache = array();

	/**
	 * @dataProvider cacheHashCalculationDataprovider
	 * @test
	 */
	public function cacheHashCalculationWorks($params, $expected, $message) {
		$this->assertEquals($expected, $this->cacheHash->calculateCacheHash($params), $message);
	}

	/**
	 * @return array
	 */
	public function cacheHashCalculationDataprovider() {
		return array(
			array(array(), '', 'Empty parameters should not return an hash'),
			array(
				array('encryptionKey' => 't3lib_cacheHashTest', 'key' => 'value'),
				'5cfdcf826275558b3613dd51714a0a17',
				'Trivial key value combination should generate hash'
			),
			array(
				array('a' => 'v', 'b' => 'v', 'encryptionKey' => 't3lib_cacheHashTest'),
				'0f40b089cdad149aea99e9bf4badaa93',
				'Multiple parameters should generate hash'
			)
		);
	}

	/**
	 * @dataProvider getRelevantParametersDataprovider
	 * @test
	 */
	public function getRelevantParametersWorks($params, $expected, $message) {
		$actual = $this->cacheHash->getRelevantParameters($params);
		$this->assertEquals($expected, array_keys($actual), $message);
	}

	/**
	 * @return array
	 */
	public function getRelevantParametersDataprovider() {
		return array(
			array('', array(), 'Empty list should be passed through'),
			array(
				'key=v',
				array('encryptionKey', 'key'),
				'Simple parameter should be passed through and the encryptionKey should be added'),
			array(
				'key1=v&key2=v',
				array('encryptionKey', 'key1', 'key2'),
				'Simple parameter should be passed through'
			),
			array(
				'id=1&type=3&exclude1=x&no_cache=1',
				array(),
				'System and exclude paramters should be omitted'
			),
			array(
				'id=1&type=3&key=x&no_cache=1',
				array('encryptionKey', 'key'),
				'System and exclude paramters should be omitted'
			)
		);
	}

	/**
	 * @dataProvider canGenerateForParametersDataprovider
	 * @test
	 */
	public function canGenerateForParameters($params, $expected, $message) {
		$this->assertEquals($expected, $this->cacheHash->generateForParameters($params), $message);
	}

	/**
	 * @return array
	 */
	public function canGenerateForParametersDataprovider() {
		$knowHash = '5cfdcf826275558b3613dd51714a0a17';
		return array(
			array('', 	'',	'Empty parameters should not return an hash'),
			array('&exclude1=val', '', 'Querystring has no relevant parameters so we should not have a cacheHash'),
			array('id=1&type=val', '', 'Querystring has only system parameters so we should not have a cacheHash'),
			array('&key=value', $knowHash,	'Trivial key value combination should generate hash'),
			array('&key=value&exclude1=val', $knowHash,	'Only the relevant parts should be taken into account'),
			array('&exclude2=val&key=value', $knowHash,	'Only the relevant parts should be taken into account'),
			array('&id=1&key=value', $knowHash,	'System parameters should not be taken into account'),
			array('&TSFE_ADMIN_PANEL[display]=7&key=value', $knowHash,	'Admin panel parameters should not be taken into account'),
			array('a=v&b=v', '0f40b089cdad149aea99e9bf4badaa93', 'Trivial hash for sorted parameters should be right'),
			array('b=v&a=v', '0f40b089cdad149aea99e9bf4badaa93', 'Parameters should be sorted before  is created')
		);
	}

	/**
	 * @dataProvider parametersRequireCacheHashDataprovider
	 * @test
	 */
	public function parametersRequireCacheHashWorks($params, $expected, $message) {
		$this->assertEquals($expected, $this->cacheHash->doParametersRequireCacheHash($params), $message);
	}

	/**
	 * @return array
	 */
	public function parametersRequireCacheHashDataprovider() {
		return array(
			array('', FALSE, 'Empty parameter strings should not require anything.'),
			array('key=value', FALSE, 'Normal parameters aren\'t required.'),
			array('req1=value', TRUE, 'Configured "req1" to be required.'),
			array('&key=value&req1=value', TRUE, 'Configured "req1" to be requiredm, should also work in combined context'),
			array('req1=value&key=value', TRUE, 'Configured "req1" to be requiredm, should also work in combined context')
		);
	}

	/**
	 * In case the cHashOnlyForParameters is set, other parameters should not
	 * incluence the cHash (except the encryption key of course)
	 *
	 * @dataProvider canWhitelistParametersDataprovider
	 * @test
	 */
	public function canWhitelistParameters($params, $expected, $message) {
		$GLOBALS['TYPO3_CONF_VARS']['FE']['cHashOnlyForParameters'] = 'whitep1,whitep2';

			// Configuration has changed - so we can't use the prepared object
		unset($this->cacheHash);
		$this->cacheHash = new t3lib_cacheHash();

		$this->assertEquals($expected, $this->cacheHash->generateForParameters($params), $message);
	}

	/**
	 * @return array
	 */
	public function canWhitelistParametersDataprovider() {
		$oneParamHash = 'e2c0f2edf08be18bcff2f4272e11f66b';
		$twoParamHash = 'f6f08c2e10a97d91b6ec61a6e2ddd0e7';
		return array(
			array('', 	'',	'Even with the whitelist enabled, empty parameters should not return an hash.'),
			array('whitep1=value', $oneParamHash,	'Whitelisted parameters should have a hash.'),
			array('whitep1=value&black=value', $oneParamHash,	'Blacklisted parameter should not influence hash.'),
			array('&whitep1=value&whitep2=value', $twoParamHash,	'Multiple whitelisted parameters should work'),
			array('whitep2=value&black=value&whitep1=value', $twoParamHash,	'The order should not influce the hash.'),
		);
	}

	/**
	 * @dataProvider canSkipParametersWithEmptyValuesDataprovider
	 * @test
	 */
	public function canSkipParametersWithEmptyValues($params, $setting, $expected, $message) {
		$GLOBALS['TYPO3_CONF_VARS']['FE']['cHashExcludedParametersIfEmpty'] = $setting;

			// Configuration has changed - so we can't use the prepared object
		unset($this->cacheHash);
		$this->cacheHash = new t3lib_cacheHash();

		$actual = $this->cacheHash->getRelevantParameters($params);
		$this->assertEquals($expected, array_keys($actual), $message);
	}

	/**
	 * @return array
	 */
	public function canSkipParametersWithEmptyValuesDataprovider() {
		return array(
			array(
				'key1=v&key2=&key3=',
				'',
				array('encryptionKey', 'key1', 'key2', 'key3'),
				'The default configuration does not allow to skip an empty key.'
			),
			array(
				'key1=v&key2=&key3=',
				'key2',
				array('encryptionKey', 'key1', 'key3'),
				'Due to the empty value, "key2" should be skipped'
			),
			array(
				'key1=v&key2&key3',
				'key2',
				array('encryptionKey', 'key1', 'key3'),
				'Due to the empty value, "key2" should be skipped'
			),
			array(
				'key1=v&key2=&key3=',
				'*',
				array('encryptionKey', 'key1'),
				'Due to the empty value, "key2" and "key3" should be skipped'
			)
		);

	}

	public function setUp() {
		$this->confCache = array(
			'encryptionKey' => $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'],
			'cHashExcludedParameters' => $GLOBALS['TYPO3_CONF_VARS']['FE']['cHashExcludedParameters'],
			'cHashOnlyForParameters' => $GLOBALS['TYPO3_CONF_VARS']['FE']['cHashOnlyForParameters'],
			'cHashRequiredParameters' => $GLOBALS['TYPO3_CONF_VARS']['FE']['cHashRequiredParameters'],
			'cHashExcludedParametersIfEmpty' => $GLOBALS['TYPO3_CONF_VARS']['FE']['cHashExcludedParametersIfEmpty']
		);

		$GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] = 't3lib_cacheHashTest';
		$GLOBALS['TYPO3_CONF_VARS']['FE']['cHashExcludedParameters'] = 'exclude1,exclude2';
		$GLOBALS['TYPO3_CONF_VARS']['FE']['cHashOnlyForParameters'] = '';
		$GLOBALS['TYPO3_CONF_VARS']['FE']['cHashRequiredParameters'] = 'req1,req2';
		$GLOBALS['TYPO3_CONF_VARS']['FE']['cHashExcludedParameterIfEmpty'] = '';

			// t3lib_div::makeInstance won't work here - the singleton might introduce cross influences
		$this->cacheHash = new t3lib_cacheHash();
	}

	public function tearDown() {
		$GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] = $this->confCache['encryptionKey'];
		$GLOBALS['TYPO3_CONF_VARS']['FE']['cHashExcludedParameters'] = $this->confCache['cHashExcludedParameters'];
		$GLOBALS['TYPO3_CONF_VARS']['FE']['cHashOnlyForParameters'] = $this->confCache['cHashOnlyForParameters'];
		$GLOBALS['TYPO3_CONF_VARS']['FE']['cHashRequiredParameters'] = $this->confCache['cHashRequiredParameters'];
		$GLOBALS['TYPO3_CONF_VARS']['FE']['cHashExcludedParameterIfEmpty'] = $this->confCache['cHashExcludedParameterIfEmpty'];
	}
}
?>