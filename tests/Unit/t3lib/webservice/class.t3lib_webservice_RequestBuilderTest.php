<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2011 Thomas Maroschik <tmaroschik@dfau.de>
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
*  A copy is found in the textfile GPL.txt and important notices to the license
*  from the author is found in LICENSE.txt distributed with these scripts.
*
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

/**
 * Enter descriptions here
 *
 * @package $PACKAGE$
 * @subpackage $SUBPACKAGE$
 * @scope prototype
 * @entity
 * @api
 */
class t3lib_webservice_RequestBuilderTest extends Tx_Phpunit_TestCase {

	/**
	 * @var t3lib_webservice_RequestBuilder
	 */
	protected $builder;

	/**
	 * Sets up a request builder for testing
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function setUpRequestBuilder() {
		$_SERVER['HTTP_HOST'] = 'localhost';
		$_SERVER['REQUEST_URI'] = '/foo?someArgument=GETArgument';
		$this->builder = t3lib_div::makeInstance('t3lib_webservice_RequestBuilder');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function buildReturnsAWebRequestObject() {
		$this->setUpRequestBuilder();
		$request = $this->builder->build();
		$this->assertInstanceOf('t3lib_webservice_Request', $request);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function buildDetectsTheRequestMethodAndSetsItInTheRequestObject() {
		$this->setUpRequestBuilder();
		$_SERVER['REQUEST_METHOD'] = 'GET';
		$request = $this->builder->build();
		$this->assertEquals('GET', $request->getMethod());
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function buildSetsGetArgumentsFromRequest() {
		$this->setUpRequestBuilder();
		$_SERVER['REQUEST_METHOD'] = 'GET';
		$_GET = array('someArgument' => 'GETArgument');
		$request = $this->builder->build();
		$arguments = $request->getArguments();
		$this->assertEquals(array('someArgument' => 'GETArgument'), $arguments);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function buildSetsPostArgumentsFromRequest() {
		$this->setUpRequestBuilder();
		$_SERVER['REQUEST_METHOD'] = 'POST';
		$_POST = array('someArgument' => 'POSTArgument');
		$request = $this->builder->build();
		$arguments = $request->getArguments();
		$this->assertEquals(array('someArgument' => 'POSTArgument'), $arguments);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function setArgumentsFromRawRequestDataRecursivelyMergesGetAndPostArgumentsFromRequest() {
		$this->setUpRequestBuilder();
		$_GET = array(
			'getArgument1' => 'getArgument1Value',
			'getArgument2' => array(
				'getArgument2a' => 'getArgument2aValue',
				'getArgument2b' => 'getArgument2bValue'
			),
			'argument3' => 'argument3Value',
			'argument4' => array(
				'argument4a' => 'argument4aValue',
				'argument4b' => array(
					'argument4ba' => 'argument4baValue',
				)
			),
			'argument5' => 'argument5Value',
		);

		$_POST = array(
			'postArgument1' => 'postArgument1Value',
			'postArgument2' => array(
				'postArgument2a' => 'postArgument2aValue',
				'postArgument2b' => 'postArgument2bValue'
			),
			'argument3' => 'overriddenArgument3Value',
			'argument4' => array(
				'argument4a' => 'overriddenArgument4aValue',
				'argument4b' => array(
					'argument4bb' => 'argument4bbValue',
				),
				'argument4c' => 'argument4cValue',
			),
			'argument6' => 'argument6Value',
		);
		$expectedArguments = array(
			'getArgument1' => 'getArgument1Value',
			'getArgument2' => array(
				'getArgument2a' => 'getArgument2aValue',
				'getArgument2b' => 'getArgument2bValue'
			),
			'argument3' => 'overriddenArgument3Value',
			'argument4' => array(
				'argument4a' => 'overriddenArgument4aValue',
				'argument4b' => array(
					'argument4ba' => 'argument4baValue',
					'argument4bb' => 'argument4bbValue',
				),
				'argument4c' => 'argument4cValue',
			),
			'argument5' => 'argument5Value',
			'postArgument1' => 'postArgument1Value',
			'postArgument2' => array(
				'postArgument2a' => 'postArgument2aValue',
				'postArgument2b' => 'postArgument2bValue'
			),
			'argument6' => 'argument6Value',
		);
		$request = $this->builder->build();
		$this->assertSame($expectedArguments, $request->getArguments());
	}

}

?>