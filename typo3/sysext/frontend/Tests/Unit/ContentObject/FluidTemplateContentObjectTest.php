<?php
namespace TYPO3\CMS\Frontend\Tests\Unit\ContentObject;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012-2013 Christian Kuhn <lolli@schwarzbu.ch>
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
 * Testcase
 *
 * @author Christian Kuhn <lolli@schwarzbu.ch>
 */
class FluidTemplateContentObjectTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var array A backup of registered singleton instances
	 */
	protected $singletonInstances = array();

	/**
	 * @var \TYPO3\CMS\Frontend\ContentObject\FluidTemplateContentObject|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface
	 */
	protected $fixture = NULL;

	/**
	 * @var \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected $contentObjectRenderer = NULL;

	/**
	 * @var \TYPO3\CMS\Fluid\View\StandaloneView|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected $standaloneView = NULL;

	/**
	 * @var \TYPO3\CMS\Extbase\Mvc\Request|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected $request = NULL;

	/**
	 * Set up
	 */
	public function setUp() {
		$this->singletonInstances = \TYPO3\CMS\Core\Utility\GeneralUtility::getSingletonInstances();
		$this->contentObjectRenderer = $this->getMock(
			'TYPO3\\CMS\\Frontend\\ContentObject\\ContentObjectRenderer'
		);
		$this->fixture = $this->getAccessibleMock(
			'TYPO3\\CMS\\Frontend\\ContentObject\\FluidTemplateContentObject',
			array('dummy', 'initializeStandaloneViewInstance'),
			array($this->contentObjectRenderer)
		);
		/** @var $tsfe \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController */
		$tsfe = $this->getMock('TYPO3\\CMS\\Frontend\\Controller\\TypoScriptFrontendController', array(), array(), '', FALSE);
		$tsfe->tmpl = $this->getMock('TYPO3\\CMS\\Core\\TypoScript\\TemplateService');
		$GLOBALS['TSFE'] = $tsfe;
	}

	/**
	 * Tear down
	 */
	public function tearDown() {
		\TYPO3\CMS\Core\Utility\GeneralUtility::resetSingletonInstances($this->singletonInstances);
	}

	/**
	 * Add a mock standalone view to fixture
	 */
	protected function addMockViewToFixture() {
		$this->standaloneView = $this->getMock('TYPO3\\CMS\\Fluid\\View\\StandaloneView');
		$this->request = $this->getMock('TYPO3\\CMS\\Extbase\\Mvc\\Request');
		$this->standaloneView
			->expects($this->any())
			->method('getRequest')
			->will($this->returnValue($this->request));
		$this->fixture->_set('view', $this->standaloneView);
	}

	/**
	 * @test
	 */
	public function constructSetsContentObjectRenderer() {
		$this->assertSame($this->contentObjectRenderer, $this->fixture->getContentObject());
	}

	/**
	 * @test
	 */
	public function renderCallsinitializeStandaloneViewInstance() {
		$this->addMockViewToFixture();
		$this->fixture
			->expects($this->once())
			->method('initializeStandaloneViewInstance');
		$this->fixture->render(array());
	}

	/**
	 * @test
	 */
	public function renderCallsTemplateServiceGetFileNameForGivenTemplateFile() {
		$this->addMockViewToFixture();
		/** @var $templateService \PHPUnit_Framework_MockObject_MockObject */
		$templateService = $GLOBALS['TSFE']->tmpl;
		$templateService
			->expects($this->any())
			->method('getFileName')
			->with('foo');
		$this->fixture->render(array('file' => 'foo'));
	}

	/**
	 * @test
	 */
	public function renderCallsStandardWrapForGivenTemplateFileWithStandardWrap() {
		$this->addMockViewToFixture();
		$this->contentObjectRenderer
			->expects($this->any())
			->method('stdWrap')
			->with('foo', array('bar' => 'baz'));
		$this->fixture->render(array('file' => 'foo', 'file.' => array('bar' => 'baz')));
	}

	/**
	 * @test
	 */
	public function renderSetsTemplateFileInView() {
		$this->addMockViewToFixture();
		/** @var $templateService \PHPUnit_Framework_MockObject_MockObject */
		$templateService = $GLOBALS['TSFE']->tmpl;
		$templateService
			->expects($this->any())
			->method('getFileName')
			->with('foo')
			->will($this->returnValue('bar'));
		$this->standaloneView
			->expects($this->any())
			->method('setTemplatePathAndFilename')
			->with('bar');
		$this->fixture->render(array('file' => 'foo'));
	}

	/**
	 * @test
	 */
	public function renderSetsTemplateFileByTemplateInView() {
		$this->addMockViewToFixture();

		$this->contentObjectRenderer
			->expects($this->any())
			->method('cObjGetSingle')
			->with('FILE', array('file' => PATH_site . 'foo/bar.html'))
			->will($this->returnValue('baz'));

		$this->standaloneView
			->expects($this->any())
			->method('setTemplateSource')
			->with('baz');

		$this->fixture->render(array(
			'template' => 'FILE',
			'template.' => array(
				'file' => PATH_site . 'foo/bar.html'
			)
		));
	}

	/**
	 * @test
	 */
	public function renderSetsLayoutRootPathInView() {
		$this->addMockViewToFixture();
		$this->standaloneView
			->expects($this->once())
			->method('setLayoutRootPath')
			->with(PATH_site . 'foo/bar.html');
		$this->fixture->render(array('layoutRootPath' => 'foo/bar.html'));
	}

	/**
	 * @test
	 */
	public function renderCallsStandardWrapForLayoutRootPath() {
		$this->addMockViewToFixture();
		$this->contentObjectRenderer
			->expects($this->once())
			->method('stdWrap')
			->with('foo', array('bar' => 'baz'));
		$this->fixture->render(array('layoutRootPath' => 'foo', 'layoutRootPath.' => array('bar' => 'baz')));
	}

	/**
	 * @test
	 */
	public function renderSetsPartialRootPathInView() {
		$this->addMockViewToFixture();
		$this->standaloneView
			->expects($this->once())
			->method('setPartialRootPath')
			->with(PATH_site . 'foo/bar.html');
		$this->fixture->render(array('partialRootPath' => 'foo/bar.html'));
	}

	/**
	 * @test
	 */
	public function renderCallsStandardWrapForPartialRootPath() {
		$this->addMockViewToFixture();
		$this->contentObjectRenderer
			->expects($this->once())
			->method('stdWrap')
			->with('foo', array('bar' => 'baz'));
		$this->fixture->render(array('partialRootPath' => 'foo', 'partialRootPath.' => array('bar' => 'baz')));
	}

	/**
	 * @test
	 */
	public function renderSetsFormatInView() {
		$this->addMockViewToFixture();
		$this->standaloneView
			->expects($this->once())
			->method('setFormat')
			->with('xml');
		$this->fixture->render(array('format' => 'xml'));
	}

	/**
	 * @test
	 */
	public function renderCallsStandardWrapForFormat() {
		$this->addMockViewToFixture();
		$this->contentObjectRenderer
			->expects($this->once())
			->method('stdWrap')
			->with('foo', array('bar' => 'baz'));
		$this->fixture->render(array('format' => 'foo', 'format.' => array('bar' => 'baz')));
	}

	/**
	 * @test
	 */
	public function renderSetsExtbasePluginNameInRequest() {
		$this->addMockViewToFixture();
		$this->request
			->expects($this->once())
			->method('setPluginName')
			->with('foo');
		$configuration = array(
			'extbase.' => array(
				'pluginName' => 'foo',
			),
		);
		$this->fixture->render($configuration);
	}

	/**
	 * @test
	 */
	public function renderCallsStandardWrapForExtbasePluginName() {
		$this->addMockViewToFixture();
		$this->contentObjectRenderer
			->expects($this->once())
			->method('stdWrap')
			->with('foo', array('bar' => 'baz'));
		$configuration = array(
			'extbase.' => array(
				'pluginName' => 'foo',
				'pluginName.' => array(
					'bar' => 'baz',
				),
			),
		);
		$this->fixture->render($configuration);
	}

	/**
	 * @test
	 */
	public function renderSetsExtbaseControllerExtensionNameInRequest() {
		$this->addMockViewToFixture();
		$this->request
			->expects($this->once())
			->method('setControllerExtensionName')
			->with('foo');
		$configuration = array(
			'extbase.' => array(
				'controllerExtensionName' => 'foo',
			),
		);
		$this->fixture->render($configuration);
	}

	/**
	 * @test
	 */
	public function renderCallsStandardWrapForExtbaseControllerExtensionName() {
		$this->addMockViewToFixture();
		$this->contentObjectRenderer
			->expects($this->once())
			->method('stdWrap')
			->with('foo', array('bar' => 'baz'));
		$configuration = array(
			'extbase.' => array(
				'controllerExtensionName' => 'foo',
				'controllerExtensionName.' => array(
					'bar' => 'baz',
				),
			),
		);
		$this->fixture->render($configuration);
	}

	/**
	 * @test
	 */
	public function renderSetsExtbaseControllerNameInRequest() {
		$this->addMockViewToFixture();
		$this->request
			->expects($this->once())
			->method('setControllerName')
			->with('foo');
		$configuration = array(
			'extbase.' => array(
				'controllerName' => 'foo',
			),
		);
		$this->fixture->render($configuration);
	}

	/**
	 * @test
	 */
	public function renderCallsStandardWrapForExtbaseControllerName() {
		$this->addMockViewToFixture();
		$this->contentObjectRenderer
			->expects($this->once())
			->method('stdWrap')
			->with('foo', array('bar' => 'baz'));
		$configuration = array(
			'extbase.' => array(
				'controllerName' => 'foo',
				'controllerName.' => array(
					'bar' => 'baz',
				),
			),
		);
		$this->fixture->render($configuration);
	}

	/**
	 * @test
	 */
	public function renderSetsExtbaseControllerActionNameInRequest() {
		$this->addMockViewToFixture();
		$this->request
			->expects($this->once())
			->method('setControllerActionName')
			->with('foo');
		$configuration = array(
			'extbase.' => array(
				'controllerActionName' => 'foo',
			),
		);
		$this->fixture->render($configuration);
	}

	/**
	 * @test
	 */
	public function renderCallsStandardWrapForExtbaseControllerActionName() {
		$this->addMockViewToFixture();
		$this->contentObjectRenderer
			->expects($this->once())
			->method('stdWrap')
			->with('foo', array('bar' => 'baz'));
		$configuration = array(
			'extbase.' => array(
				'controllerActionName' => 'foo',
				'controllerActionName.' => array(
					'bar' => 'baz',
				),
			),
		);
		$this->fixture->render($configuration);
	}

	/**
	 * @test
	 */
	public function renderAssignsSettingsArrayToView() {
		$this->addMockViewToFixture();

		$configuration = array(
			'settings.' => array(
				'foo' => 'value',
				'bar.' => array(
					'baz' => 'value2',
				),
			),
		);

		$expectedSettingsToBeSet = array(
			'foo' => 'value',
			'bar' => array(
				'baz' => 'value2',
			),
		);

		$typoScriptServiceMock = $this->getMock('TYPO3\\CMS\\Extbase\\Service\\TypoScriptService');
		$typoScriptServiceMock
			->expects($this->once())
			->method('convertTypoScriptArrayToPlainArray')
			->with($configuration['settings.'])
			->will($this->returnValue($expectedSettingsToBeSet));
		\TYPO3\CMS\Core\Utility\GeneralUtility::setSingletonInstance('TYPO3\\CMS\\Extbase\\Service\\TypoScriptService', $typoScriptServiceMock);

		$this->standaloneView
			->expects($this->at(1))
			->method('assign')
			->with('settings', $expectedSettingsToBeSet);

		$this->fixture->render($configuration);
	}

	/**
	 * @test
	 * @expectedException \InvalidArgumentException
	 */
	public function renderThrowsExceptionForNotAllowedVariableData() {
		$this->addMockViewToFixture();
		$configuration = array(
			'variables.' => array(
				'data' => 'foo',
				'data.' => array(
					'bar' => 'baz',
				),
			),
		);
		$this->fixture->render($configuration);
	}

	/**
	 * @test
	 * @expectedException \InvalidArgumentException
	 */
	public function renderThrowsExceptionForNotAllowedVariableCurrent() {
		$this->addMockViewToFixture();
		$configuration = array(
			'variables.' => array(
				'current' => 'foo',
				'current.' => array(
					'bar' => 'baz',
				),
			),
		);
		$this->fixture->render($configuration);
	}

	/**
	 * @test
	 */
	public function renderCallsCObjGetSingleForAllowedVariable() {
		$this->addMockViewToFixture();
		$configuration = array(
			'variables.' => array(
				'aVar' => 'TEXT',
				'aVar.' => array(
					'value' => 'foo',
				),
			),
		);
		$this->contentObjectRenderer
			->expects($this->once())
			->method('cObjGetSingle')
			->with('TEXT', array('value' => 'foo'));
		$this->fixture->render($configuration);
	}

	/**
	 * @test
	 */
	public function renderAssignsRenderedContentObjectVariableToView() {
		$this->addMockViewToFixture();
		$configuration = array(
			'variables.' => array(
				'aVar' => 'TEXT',
				'aVar.' => array(
					'value' => 'foo',
				),
			),
		);
		$this->contentObjectRenderer
			->expects($this->once())
			->method('cObjGetSingle')
			->will($this->returnValue('foo'));
		$this->standaloneView
			->expects($this->at(1))
			->method('assign')
			->with('aVar', 'foo');
		$this->fixture->render($configuration);
	}

	/**
	 * @test
	 */
	public function renderAssignsContentObjectRendererDataToView() {
		$this->addMockViewToFixture();
		$this->contentObjectRenderer->data = array('foo');
		$this->standaloneView
			->expects($this->at(1))
			->method('assign')
			->with('data', array('foo'));
		$this->fixture->render(array());
	}

	/**
	 * @test
	 */
	public function renderAssignsContentObjectRendererCurrentValueToView() {
		$this->addMockViewToFixture();
		$this->contentObjectRenderer->data = array('currentKey' => 'currentValue');
		$this->contentObjectRenderer->currentValKey= 'currentKey';
		$this->standaloneView
			->expects($this->at(2))
			->method('assign')
			->with('current', 'currentValue');
		$this->fixture->render(array());
	}

	/**
	 * @test
	 */
	public function renderCallsRenderOnStandaloneViewie() {
		$this->addMockViewToFixture();
		$this->standaloneView
			->expects($this->once())
			->method('render');
		$this->fixture->render(array());
	}

	/**
	 * @test
	 */
	public function renderCallsStandardWrapOnResultStringIfGivenInConfiguration() {
		$this->addMockViewToFixture();
		$configuration = array(
			'stdWrap.' => array(
				'foo' => 'bar',
			),
		);
		$this->standaloneView
			->expects($this->any())
			->method('render')
			->will($this->returnValue('baz'));
		$this->contentObjectRenderer
			->expects($this->once())
			->method('stdWrap')
			->with('baz', array('foo' => 'bar'));
		$this->fixture->render($configuration);
	}
}
?>