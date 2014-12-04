<?php
namespace TYPO3\CMS\Frontend\Tests\Unit\ContentObject;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Extbase\Service\TypoScriptService;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\ContentObject\FluidTemplateContentObject;

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
	 * @var FluidTemplateContentObject|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface
	 */
	protected $subject = NULL;

	/**
	 * @var ContentObjectRenderer|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected $contentObjectRenderer = NULL;

	/**
	 * @var StandaloneView|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected $standaloneView = NULL;

	/**
	 * @var Request|\PHPUnit_Framework_MockObject_MockObject
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
		$this->subject = $this->getAccessibleMock(
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
		parent::tearDown();
	}

	/**
	 * Add a mock standalone view to subject
	 */
	protected function addMockViewToSubject() {
		$this->standaloneView = $this->getMock('TYPO3\\CMS\\Fluid\\View\\StandaloneView', array(), array(), '', FALSE);
		$this->request = $this->getMock('TYPO3\\CMS\\Extbase\\Mvc\\Request');
		$this->standaloneView
			->expects($this->any())
			->method('getRequest')
			->will($this->returnValue($this->request));
		$this->subject->_set('view', $this->standaloneView);
	}

	/**
	 * @test
	 */
	public function constructSetsContentObjectRenderer() {
		$this->assertSame($this->contentObjectRenderer, $this->subject->getContentObject());
	}

	/**
	 * @test
	 */
	public function renderCallsInitializeStandaloneViewInstance() {
		$this->addMockViewToSubject();
		$this->subject
			->expects($this->once())
			->method('initializeStandaloneViewInstance');
		$this->subject->render(array());
	}

	/**
	 * @test
	 */
	public function renderCallsTemplateServiceGetFileNameForGivenTemplateFile() {
		$this->addMockViewToSubject();
		/** @var $templateService \PHPUnit_Framework_MockObject_MockObject */
		$templateService = $GLOBALS['TSFE']->tmpl;
		$templateService
			->expects($this->any())
			->method('getFileName')
			->with('foo');
		$this->subject->render(array('file' => 'foo'));
	}

	/**
	 * @test
	 */
	public function renderCallsStandardWrapForGivenTemplateFileWithStandardWrap() {
		$this->addMockViewToSubject();
		$this->contentObjectRenderer
			->expects($this->any())
			->method('stdWrap')
			->with('foo', array('bar' => 'baz'));
		$this->subject->render(array('file' => 'foo', 'file.' => array('bar' => 'baz')));
	}

	/**
	 * @test
	 */
	public function renderSetsTemplateFileInView() {
		$this->addMockViewToSubject();
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
			->with(PATH_site . 'bar');
		$this->subject->render(array('file' => 'foo'));
	}

	/**
	 * @test
	 */
	public function renderSetsTemplateFileByTemplateInView() {
		$this->addMockViewToSubject();

		$this->contentObjectRenderer
			->expects($this->any())
			->method('cObjGetSingle')
			->with('FILE', array('file' => PATH_site . 'foo/bar.html'))
			->will($this->returnValue('baz'));

		$this->standaloneView
			->expects($this->any())
			->method('setTemplateSource')
			->with('baz');

		$this->subject->render(array(
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
		$this->addMockViewToSubject();
		$this->standaloneView
			->expects($this->once())
			->method('setLayoutRootPaths')
			->with(array(PATH_site . 'foo/bar.html'));
		$this->subject->render(array('layoutRootPath' => 'foo/bar.html'));
	}

	/**
	 * @test
	 */
	public function renderCallsStandardWrapForLayoutRootPath() {
		$this->addMockViewToSubject();
		$this->contentObjectRenderer
			->expects($this->once())
			->method('stdWrap')
			->with('foo', array('bar' => 'baz'));
		$this->subject->render(array('layoutRootPath' => 'foo', 'layoutRootPath.' => array('bar' => 'baz')));
	}

	/**
	 * @test
	 */
	public function fallbacksForLayoutRootPathAreSet() {
		$this->addMockViewToSubject();
		$this->standaloneView
			->expects($this->once())
			->method('setLayoutRootPaths')
			->with(array(10 => PATH_site . 'foo/bar.html', 20 => PATH_site . 'foo/bar2.html'));
		$this->subject->render(array('layoutRootPaths.' => array(10 => 'foo/bar.html', 20 => 'foo/bar2.html')));
	}

	/**
	 * @test
	 */
	public function fallbacksForLayoutRootPathAreAppendedToLayoutRootPath() {
		$this->addMockViewToSubject();
		$this->standaloneView
			->expects($this->once())
			->method('setLayoutRootPaths')
			->with(array(0 => PATH_site . 'foo/main.html', 10 => PATH_site . 'foo/bar.html', 20 => PATH_site . 'foo/bar2.html'));
		$this->subject->render(array('layoutRootPath' => 'foo/main.html', 'layoutRootPaths.' => array(10 => 'foo/bar.html', 20 => 'foo/bar2.html')));
	}

	/**
	 * @test
	 */
	public function renderSetsPartialRootPathInView() {
		$this->addMockViewToSubject();
		$this->standaloneView
			->expects($this->once())
			->method('setPartialRootPaths')
			->with(array(PATH_site . 'foo/bar.html'));
		$this->subject->render(array('partialRootPath' => 'foo/bar.html'));
	}

	/**
	 * @test
	 */
	public function renderCallsStandardWrapForPartialRootPath() {
		$this->addMockViewToSubject();
		$this->contentObjectRenderer
			->expects($this->once())
			->method('stdWrap')
			->with('foo', array('bar' => 'baz'));
		$this->subject->render(array('partialRootPath' => 'foo', 'partialRootPath.' => array('bar' => 'baz')));
	}


	/**
	 * @test
	 */
	public function fallbacksForPartialRootPathAreSet() {
		$this->addMockViewToSubject();
		$this->standaloneView
			->expects($this->once())
			->method('setPartialRootPaths')
			->with(array(10 => PATH_site . 'foo', 20 => PATH_site . 'bar'));
		$this->subject->render(array('partialRootPaths.' => array(10 => 'foo', 20 => 'bar')));
	}

	/**
	 * @test
	 */
	public function fallbacksForPartialRootPathAreAppendedToPartialRootPath() {
		$this->addMockViewToSubject();
		$this->standaloneView
			->expects($this->once())
			->method('setPartialRootPaths')
			->with(array(0 => PATH_site . 'main', 10 => PATH_site . 'foo', 20 => PATH_site . 'bar'));
		$this->subject->render(array('partialRootPath' => 'main', 'partialRootPaths.' => array(10 => 'foo', 20 => 'bar')));
	}

	/**
	 * @test
	 */
	public function renderSetsFormatInView() {
		$this->addMockViewToSubject();
		$this->standaloneView
			->expects($this->once())
			->method('setFormat')
			->with('xml');
		$this->subject->render(array('format' => 'xml'));
	}

	/**
	 * @test
	 */
	public function renderCallsStandardWrapForFormat() {
		$this->addMockViewToSubject();
		$this->contentObjectRenderer
			->expects($this->once())
			->method('stdWrap')
			->with('foo', array('bar' => 'baz'));
		$this->subject->render(array('format' => 'foo', 'format.' => array('bar' => 'baz')));
	}

	/**
	 * @test
	 */
	public function renderSetsExtbasePluginNameInRequest() {
		$this->addMockViewToSubject();
		$this->request
			->expects($this->once())
			->method('setPluginName')
			->with('foo');
		$configuration = array(
			'extbase.' => array(
				'pluginName' => 'foo',
			),
		);
		$this->subject->render($configuration);
	}

	/**
	 * @test
	 */
	public function renderCallsStandardWrapForExtbasePluginName() {
		$this->addMockViewToSubject();
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
		$this->subject->render($configuration);
	}

	/**
	 * @test
	 */
	public function renderSetsExtbaseControllerExtensionNameInRequest() {
		$this->addMockViewToSubject();
		$this->request
			->expects($this->once())
			->method('setControllerExtensionName')
			->with('foo');
		$configuration = array(
			'extbase.' => array(
				'controllerExtensionName' => 'foo',
			),
		);
		$this->subject->render($configuration);
	}

	/**
	 * @test
	 */
	public function renderCallsStandardWrapForExtbaseControllerExtensionName() {
		$this->addMockViewToSubject();
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
		$this->subject->render($configuration);
	}

	/**
	 * @test
	 */
	public function renderSetsExtbaseControllerNameInRequest() {
		$this->addMockViewToSubject();
		$this->request
			->expects($this->once())
			->method('setControllerName')
			->with('foo');
		$configuration = array(
			'extbase.' => array(
				'controllerName' => 'foo',
			),
		);
		$this->subject->render($configuration);
	}

	/**
	 * @test
	 */
	public function renderCallsStandardWrapForExtbaseControllerName() {
		$this->addMockViewToSubject();
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
		$this->subject->render($configuration);
	}

	/**
	 * @test
	 */
	public function renderSetsExtbaseControllerActionNameInRequest() {
		$this->addMockViewToSubject();
		$this->request
			->expects($this->once())
			->method('setControllerActionName')
			->with('foo');
		$configuration = array(
			'extbase.' => array(
				'controllerActionName' => 'foo',
			),
		);
		$this->subject->render($configuration);
	}

	/**
	 * @test
	 */
	public function renderCallsStandardWrapForExtbaseControllerActionName() {
		$this->addMockViewToSubject();
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
		$this->subject->render($configuration);
	}

	/**
	 * @test
	 */
	public function renderAssignsSettingsArrayToView() {
		$this->addMockViewToSubject();

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

		$this->subject->render($configuration);
	}

	/**
	 * @test
	 * @expectedException \InvalidArgumentException
	 */
	public function renderThrowsExceptionForNotAllowedVariableData() {
		$this->addMockViewToSubject();
		$configuration = array(
			'variables.' => array(
				'data' => 'foo',
				'data.' => array(
					'bar' => 'baz',
				),
			),
		);
		$this->subject->render($configuration);
	}

	/**
	 * @test
	 * @expectedException \InvalidArgumentException
	 */
	public function renderThrowsExceptionForNotAllowedVariableCurrent() {
		$this->addMockViewToSubject();
		$configuration = array(
			'variables.' => array(
				'current' => 'foo',
				'current.' => array(
					'bar' => 'baz',
				),
			),
		);
		$this->subject->render($configuration);
	}

	/**
	 * @test
	 */
	public function renderCallsCObjGetSingleForAllowedVariable() {
		$this->addMockViewToSubject();
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
		$this->subject->render($configuration);
	}

	/**
	 * @test
	 */
	public function renderAssignsRenderedContentObjectVariableToView() {
		$this->addMockViewToSubject();
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
		$this->subject->render($configuration);
	}

	/**
	 * @test
	 */
	public function renderAssignsContentObjectRendererDataToView() {
		$this->addMockViewToSubject();
		$this->contentObjectRenderer->data = array('foo');
		$this->standaloneView
			->expects($this->at(1))
			->method('assign')
			->with('data', array('foo'));
		$this->subject->render(array());
	}

	/**
	 * @test
	 */
	public function renderAssignsContentObjectRendererCurrentValueToView() {
		$this->addMockViewToSubject();
		$this->contentObjectRenderer->data = array('currentKey' => 'currentValue');
		$this->contentObjectRenderer->currentValKey= 'currentKey';
		$this->standaloneView
			->expects($this->at(2))
			->method('assign')
			->with('current', 'currentValue');
		$this->subject->render(array());
	}

	/**
	 * @test
	 */
	public function renderCallsRenderOnStandaloneViewie() {
		$this->addMockViewToSubject();
		$this->standaloneView
			->expects($this->once())
			->method('render');
		$this->subject->render(array());
	}

	/**
	 * @test
	 */
	public function renderCallsStandardWrapOnResultStringIfGivenInConfiguration() {
		$this->addMockViewToSubject();
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
		$this->subject->render($configuration);
	}
}
