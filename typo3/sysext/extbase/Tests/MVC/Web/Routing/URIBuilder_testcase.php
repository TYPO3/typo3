<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Jochen Rau <jochen.rau@typoplanet.de>
*  All rights reserved
*
*  This class is a backport of the corresponding class of FLOW3.
*  All credits go to the v5 team.
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

class Tx_Extbase_MVC_Web_Routing_URIBuilder_testcase extends Tx_Extbase_Base_testcase {

	protected $tsfeBackup;

	public function setUp() {
		$this->tsfeBackup = $GLOBALS['TSFE'];
		$GLOBALS['TSFE'] = $this->getMock('tslib_fe', array(), array(), '', FALSE);
	}

	public function tearDown() {
		$GLOBALS['TSFE'] = $this->tsfeBackup;
	}

	/**
	 * @test
	 */
	public function uriForPrefixesArgumentsWithExtensionAndPluginNameAndSetsControllerArgument() {
		$URIBuilder = $this->getMock('Tx_Extbase_MVC_Web_Routing_URIBuilder', array('typolinkURI'));

		$expectedArguments = array('tx_someextension_someplugin' => array('foo' => 'bar', 'baz' => array('extbase' => 'fluid'), 'controller' => 'SomeController'));
		$URIBuilder->expects($this->once())->method('typolinkURI')->with(NULL, $expectedArguments, 0, FALSE, TRUE, '', FALSE);
		$URIBuilder->URIFor(NULL, NULL, array('foo' => 'bar', 'baz' => array('extbase' => 'fluid')), 'SomeController', 'SomeExtension', 'SomePlugin');
	}

	/**
	 * @test
	 */
	public function additionalArgumentsOverruleArguments() {
		$URIBuilder = $this->getMock('Tx_Extbase_MVC_Web_Routing_URIBuilder', array('typolinkURI'));

		$arguments = array('foo' => 'bar', 'baz' => array('extbase' => 'fluid'));
		$additionalArguments = array('tx_someextension_someplugin' => array('foo' => 'overruled'), 'additionalParam' => 'additionalValue');
		$expectedArguments = array('tx_someextension_someplugin' => array('foo' => 'overruled', 'baz' => array('extbase' => 'fluid'), 'controller' => 'SomeController'), 'additionalParam' => 'additionalValue');
		$URIBuilder->expects($this->once())->method('typolinkURI')->with(NULL, $expectedArguments, 0, FALSE, TRUE, '', FALSE);
		$URIBuilder->URIFor(NULL, NULL, $arguments, 'SomeController', 'SomeExtension', 'SomePlugin', 0, FALSE, TRUE, '', FALSE, $additionalArguments);
	}

	/**
	 * @test
	 */
	public function uriForForwardsAllParametersToTypolinkURI() {
		$URIBuilder = $this->getMock('Tx_Extbase_MVC_Web_Routing_URIBuilder', array('typolinkURI'));

		$expectedArguments = array('tx_someextension_someplugin' => array('action' => 'SomeAction', 'controller' => 'SomeController'));
		$URIBuilder->expects($this->once())->method('typolinkURI')->with(123, $expectedArguments, 2, TRUE, FALSE, 'SomeSection', TRUE);
		$URIBuilder->URIFor(123, 'SomeAction', array(), 'SomeController', 'SomeExtension', 'SomePlugin', 2, TRUE, FALSE, 'SomeSection', TRUE);
	}

	/**
	 * @test
	 */
	public function uriForSetsActionArgument() {
		$URIBuilder = $this->getMock('Tx_Extbase_MVC_Web_Routing_URIBuilder', array('typolinkURI'));

		$expectedArguments = array('tx_someextension_someplugin' => array('action' => 'SomeAction', 'controller' => 'SomeController'));
		$URIBuilder->expects($this->once())->method('typolinkURI')->with(NULL, $expectedArguments, 0, FALSE, TRUE, '', FALSE);
		$URIBuilder->URIFor(NULL, 'SomeAction', array(), 'SomeController', 'SomeExtension', 'SomePlugin');
	}

	/**
	 * @test
	 */
	public function uriForSetsControllerFromRequestIfControllerIsNotSet() {
		$mockRequest = $this->getMock('Tx_Extbase_MVC_Request');
		$mockRequest->expects($this->once())->method('getControllerName')->will($this->returnValue('SomeControllerFromRequest'));

		$URIBuilder = $this->getMock('Tx_Extbase_MVC_Web_Routing_URIBuilder', array('typolinkURI'));
		$URIBuilder->setRequest($mockRequest);

		$expectedArguments = array('tx_someextension_someplugin' => array('controller' => 'SomeControllerFromRequest'));
		$URIBuilder->expects($this->once())->method('typolinkURI')->with(NULL, $expectedArguments, 0, FALSE, TRUE, '', FALSE);
		$URIBuilder->URIFor(NULL, NULL, array(), NULL, 'SomeExtension', 'SomePlugin');
	}

	/**
	 * @test
	 */
	public function uriForSetsExtensionNameFromRequestIfExtensionNameIsNotSet() {
		$mockRequest = $this->getMock('Tx_Extbase_MVC_Request');
		$mockRequest->expects($this->once())->method('getControllerExtensionName')->will($this->returnValue('SomeExtensionNameFromRequest'));

		$URIBuilder = $this->getMock('Tx_Extbase_MVC_Web_Routing_URIBuilder', array('typolinkURI'));
		$URIBuilder->setRequest($mockRequest);

		$expectedArguments = array('tx_someextensionnamefromrequest_someplugin' => array('controller' => 'SomeController'));
		$URIBuilder->expects($this->once())->method('typolinkURI')->with(NULL, $expectedArguments, 0, FALSE, TRUE, '', FALSE);
		$URIBuilder->URIFor(NULL, NULL, array(), 'SomeController', NULL, 'SomePlugin');
	}

	/**
	 * @test
	 */
	public function uriForSetsPluginNameFromRequestIfPluginNameIsNotSet() {
		$mockRequest = $this->getMock('Tx_Extbase_MVC_Request');
		$mockRequest->expects($this->once())->method('getPluginName')->will($this->returnValue('SomePluginNameFromRequest'));

		$URIBuilder = $this->getMock('Tx_Extbase_MVC_Web_Routing_URIBuilder', array('typolinkURI'));
		$URIBuilder->setRequest($mockRequest);

		$expectedArguments = array('tx_someextension_somepluginnamefromrequest' => array('controller' => 'SomeController'));
		$URIBuilder->expects($this->once())->method('typolinkURI')->with(NULL, $expectedArguments, 0, FALSE, TRUE, '', FALSE);
		$URIBuilder->URIFor(NULL, NULL, array(), 'SomeController', 'SomeExtension');
	}

	/**
	 * @test
	 */
	public function uriForCallsConvertDomainObjectsToIdentityArraysAfterArgumentsHaveBeenMerged() {
		$URIBuilder = $this->getMock('Tx_Extbase_MVC_Web_Routing_URIBuilder', array('typolinkURI', 'convertDomainObjectsToIdentityArrays'));

		$arguments = array('foo' => 'bar', 'baz' => array('extbase' => 'fluid'));
		$additionalArguments = array('tx_someextension_someplugin' => array('foo' => 'overruled'), 'additionalParam' => 'additionalValue');
		$expectedArguments = array('tx_someextension_someplugin' => array('foo' => 'overruled', 'baz' => array('extbase' => 'fluid'), 'controller' => 'SomeController'), 'additionalParam' => 'additionalValue');
		$URIBuilder->expects($this->once())->method('convertDomainObjectsToIdentityArrays')->with($expectedArguments)->will($this->returnValue(array()));;
		$URIBuilder->URIFor(NULL, NULL, $arguments, 'SomeController', 'SomeExtension', 'SomePlugin', 0, FALSE, TRUE, '', FALSE, $additionalArguments);
	}

	/**
	 * @test
	 */
	public function uriForPassesAllDefaultArgumentsToTypolinkURI() {
		$mockRequest = $this->getMock('Tx_Extbase_MVC_Request');
		$mockRequest->expects($this->any())->method('getControllerName')->will($this->returnValue('SomeControllerName'));
		$mockRequest->expects($this->any())->method('getControllerExtensionName')->will($this->returnValue('SomeExtensionName'));
		$mockRequest->expects($this->any())->method('getPluginName')->will($this->returnValue('SomePluginName'));

		$mockContentObject = $this->getMock('tslib_cObj');
		$URIBuilder = $this->getMock('Tx_Extbase_MVC_Web_Routing_URIBuilder', array('typolinkURI'), array($mockContentObject), '', FALSE);
		$URIBuilder->setRequest($mockRequest);
		$URIBuilder->expects($this->once())->method('typolinkURI')->with(NULL, array('tx_someextensionname_somepluginname' => array('controller' => 'SomeControllerName')), 0, FALSE, TRUE, '', FALSE, FALSE);

		$URIBuilder->URIFor();
	}

	/**
	 * @test
	 */
	public function uriForPassesAllSpecifiedArgumentsToTypolinkURI() {
		$mockContentObject = $this->getMock('tslib_cObj');
		$URIBuilder = $this->getMock('Tx_Extbase_MVC_Web_Routing_URIBuilder', array('typolinkURI'), array($mockContentObject), '', FALSE);
		$URIBuilder->expects($this->once())->method('typolinkURI')->with(123, array('tx_extensionname_pluginname' => array('some' => 'Argument', 'action' => 'actionName', 'controller' => 'controllerName'), 'additional' => 'Parameter'), 1, TRUE, FALSE, 'section', TRUE, TRUE);

		$URIBuilder->URIFor(123, 'actionName', array('some' => 'Argument'), 'controllerName', 'extensionName', 'pluginName', 1, TRUE, FALSE, 'section', TRUE, array('additional' => 'Parameter'), TRUE);
	}

	/**
	 * @test
	 */
	public function convertDomainObjectsToIdentityArraysConvertsDomainObjects() {
		$mockDomainObject1 = $this->getMock($this->buildAccessibleProxy('Tx_Extbase_DomainObject_AbstractEntity'), array('dummy'));
		$mockDomainObject1->_set('uid', '123');

		$mockDomainObject2 = $this->getMock($this->buildAccessibleProxy('Tx_Extbase_DomainObject_AbstractEntity'), array('dummy'));
		$mockDomainObject2->_set('uid', '321');

		$URIBuilder = $this->getMock($this->buildAccessibleProxy('Tx_Extbase_MVC_Web_Routing_URIBuilder'), array('dummy'));

		$expectedResult = array('foo' => array('bar' => 'baz'), 'domainObject1' => array('uid' => '123'), 'second' => array('domainObject2' => array('uid' => '321')));
		$actualResult = $URIBuilder->_call('convertDomainObjectsToIdentityArrays', array('foo' => array('bar' => 'baz'), 'domainObject1' => $mockDomainObject1, 'second' => array('domainObject2' => $mockDomainObject2)));

		$this->assertEquals($expectedResult, $actualResult);
	}

	/**
	 * @test
	 */
	public function typolinkURILinksToCurrentPageIfPageUidIsNotSet() {
		$mockContentObject = $this->getMock('tslib_cObj');
		$URIBuilder = new Tx_Extbase_MVC_Web_Routing_URIBuilder($mockContentObject);

		$GLOBALS['TSFE']->id = 123;
		$expectedConfiguration = array('parameter' => 123, 'useCacheHash' => 1);
		$mockContentObject->expects($this->once())->method('typoLink_URL')->with($expectedConfiguration);

		$URIBuilder->typolinkURI();
	}

	/**
	 * @test
	 */
	public function typolinkURILinksToPageUidIfSet() {
		$mockContentObject = $this->getMock('tslib_cObj');
		$URIBuilder = new Tx_Extbase_MVC_Web_Routing_URIBuilder($mockContentObject);

		$expectedConfiguration = array('parameter' => 321, 'useCacheHash' => 1);
		$mockContentObject->expects($this->once())->method('typoLink_URL')->with($expectedConfiguration);

		$URIBuilder->typolinkURI(321);
	}

	/**
	 * @test
	 */
	public function typolinkURILinksProperlySetsAdditionalArguments() {
		$mockContentObject = $this->getMock('tslib_cObj');
		$URIBuilder = new Tx_Extbase_MVC_Web_Routing_URIBuilder($mockContentObject);

		$expectedConfiguration = array('parameter' => 123, 'useCacheHash' => 1, 'additionalParams' => '&foo=bar&baz%5Bextbase%5D=fluid');
		$mockContentObject->expects($this->once())->method('typoLink_URL')->with($expectedConfiguration);

		$URIBuilder->typolinkURI(123, array('foo' => 'bar', 'baz' => array('extbase' => 'fluid')));
	}

	/**
	 * @test
	 */
	public function typolinkURIConsidersPageType() {
		$mockContentObject = $this->getMock('tslib_cObj');
		$URIBuilder = new Tx_Extbase_MVC_Web_Routing_URIBuilder($mockContentObject);

		$expectedConfiguration = array('parameter' => '123,2', 'useCacheHash' => 1);
		$mockContentObject->expects($this->once())->method('typoLink_URL')->with($expectedConfiguration);

		$URIBuilder->typolinkURI(123, array(), 2);
	}

	/**
	 * @test
	 */
	public function typolinkURIDisablesCacheHashIfNoCacheIsSet() {
		$mockContentObject = $this->getMock('tslib_cObj');
		$URIBuilder = new Tx_Extbase_MVC_Web_Routing_URIBuilder($mockContentObject);

		$expectedConfiguration = array('parameter' => 123, 'no_cache' => 1);
		$mockContentObject->expects($this->once())->method('typoLink_URL')->with($expectedConfiguration);

		$URIBuilder->typolinkURI(123, array(), 0, TRUE, TRUE);
	}

	/**
	 * @test
	 */
	public function cacheHashCanBeDisabled() {
		$mockContentObject = $this->getMock('tslib_cObj');
		$URIBuilder = new Tx_Extbase_MVC_Web_Routing_URIBuilder($mockContentObject);

		$expectedConfiguration = array('parameter' => 123);
		$mockContentObject->expects($this->once())->method('typoLink_URL')->with($expectedConfiguration);

		$URIBuilder->typolinkURI(123, array(), 0, FALSE, FALSE);
	}

	/**
	 * @test
	 */
	public function typolinkURIConsidersSection() {
		$mockContentObject = $this->getMock('tslib_cObj');
		$URIBuilder = new Tx_Extbase_MVC_Web_Routing_URIBuilder($mockContentObject);

		$expectedConfiguration = array('parameter' => 123, 'section' => 'SomeSection');
		$mockContentObject->expects($this->once())->method('typoLink_URL')->with($expectedConfiguration);

		$URIBuilder->typolinkURI(123, array(), 0, FALSE, FALSE, 'SomeSection');
	}

	/**
	 * @test
	 */
	public function typolinkURIConsidersLinkAccessRestrictedPages() {
		$mockContentObject = $this->getMock('tslib_cObj');
		$URIBuilder = new Tx_Extbase_MVC_Web_Routing_URIBuilder($mockContentObject);

		$expectedConfiguration = array('parameter' => 123, 'linkAccessRestrictedPages' => 1);
		$mockContentObject->expects($this->once())->method('typoLink_URL')->with($expectedConfiguration);

		$URIBuilder->typolinkURI(123, array(), 0, FALSE, FALSE, '', TRUE);
	}

	/**
	 * @test
	 */
	public function typolinkURICreatesRelativeUrisByDefault() {
		$mockContentObject = $this->getMock('tslib_cObj');
		$URIBuilder = new Tx_Extbase_MVC_Web_Routing_URIBuilder($mockContentObject);

		$mockContentObject->expects($this->once())->method('typoLink_URL')->will($this->returnValue('relative/uri'));

		$expectedResult = 'relative/uri';
		$actualResult = $URIBuilder->typolinkURI();
		$this->assertSame('relative/uri', $actualResult);
	}

	/**
	 * @test
	 */
	public function typolinkURICreatesAbsoluteUrisIfSpecified() {
		$mockRequest = $this->getMock('Tx_Extbase_MVC_Web_Request');
		$mockRequest->expects($this->any())->method('getBaseURI')->will($this->returnValue('http://baseuri/'));

		$mockContentObject = $this->getMock('tslib_cObj');
		$URIBuilder = new Tx_Extbase_MVC_Web_Routing_URIBuilder($mockContentObject);
		$URIBuilder->setRequest($mockRequest);

		$mockContentObject->expects($this->once())->method('typoLink_URL')->will($this->returnValue('relative/uri'));

		$expectedResult = 'http://baseuri/relative/uri';
		$actualResult = $URIBuilder->typolinkURI(NULL, array(), 0, FALSE, TRUE, '', FALSE, TRUE);
		$this->assertSame($expectedResult, $actualResult);
	}

}
?>
