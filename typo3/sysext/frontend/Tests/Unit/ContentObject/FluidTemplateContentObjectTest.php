<?php
namespace TYPO3\CMS\Frontend\Tests\Unit\ContentObject;

/*
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
 */
class FluidTemplateContentObjectTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @var array A backup of registered singleton instances
     */
    protected $singletonInstances = [];

    /**
     * @var FluidTemplateContentObject|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface
     */
    protected $subject = null;

    /**
     * @var ContentObjectRenderer|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $contentObjectRenderer = null;

    /**
     * @var StandaloneView|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $standaloneView = null;

    /**
     * @var Request|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $request = null;

    /**
     * Set up
     */
    protected function setUp()
    {
        $this->singletonInstances = GeneralUtility::getSingletonInstances();
        $this->contentObjectRenderer = $this->getMock(
            \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer::class
        );
        $this->subject = $this->getAccessibleMock(
            \TYPO3\CMS\Frontend\ContentObject\FluidTemplateContentObject::class,
            ['initializeStandaloneViewInstance'],
            [$this->contentObjectRenderer]
        );
        /** @var $tsfe \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController */
        $tsfe = $this->getMock(\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController::class, [], [], '', false);
        $tsfe->tmpl = $this->getMock(\TYPO3\CMS\Core\TypoScript\TemplateService::class);
        $GLOBALS['TSFE'] = $tsfe;
    }

    /**
     * Tear down
     */
    protected function tearDown()
    {
        GeneralUtility::resetSingletonInstances($this->singletonInstances);
        parent::tearDown();
    }

    /**
     * Add a mock standalone view to subject
     */
    protected function addMockViewToSubject()
    {
        $this->standaloneView = $this->getMock(\TYPO3\CMS\Fluid\View\StandaloneView::class, [], [], '', false);
        $this->request = $this->getMock(\TYPO3\CMS\Extbase\Mvc\Request::class);
        $this->standaloneView
            ->expects($this->any())
            ->method('getRequest')
            ->will($this->returnValue($this->request));
        $this->subject->_set('view', $this->standaloneView);
    }

    /**
     * @test
     */
    public function constructSetsContentObjectRenderer()
    {
        $this->assertSame($this->contentObjectRenderer, $this->subject->getContentObject());
    }

    /**
     * @test
     */
    public function renderCallsInitializeStandaloneViewInstance()
    {
        $this->addMockViewToSubject();
        $this->subject
            ->expects($this->once())
            ->method('initializeStandaloneViewInstance');
        $this->subject->render([]);
    }

    /**
     * @test
     */
    public function renderCallsTemplateServiceGetFileNameForGivenTemplateFile()
    {
        $this->addMockViewToSubject();
        /** @var $templateService \PHPUnit_Framework_MockObject_MockObject */
        $templateService = $GLOBALS['TSFE']->tmpl;
        $templateService
            ->expects($this->any())
            ->method('getFileName')
            ->with('foo');
        $this->subject->render(['file' => 'foo']);
    }

    /**
     * @test
     */
    public function renderCallsStandardWrapForGivenTemplateFileWithStandardWrap()
    {
        $this->addMockViewToSubject();
        $this->contentObjectRenderer
            ->expects($this->any())
            ->method('stdWrap')
            ->with('foo', ['bar' => 'baz']);
        $this->subject->render(['file' => 'foo', 'file.' => ['bar' => 'baz']]);
    }

    /**
     * @test
     */
    public function renderCallsStandardWrapForGivenTemplateRootPathsWithStandardWrap()
    {
        $this->addMockViewToSubject();
        $this->contentObjectRenderer
            ->expects($this->at(0))
            ->method('stdWrap')
            ->with('dummyPath', ['wrap' => '|5/']);
        $this->contentObjectRenderer
            ->expects($this->at(1))
            ->method('stdWrap')
            ->with('', ['field' => 'someField']);
        $this->subject->render([
                'templateName' => 'foobar',
                'templateRootPaths.' => [
                    10 => 'dummyPath',
                    '10.' => [
                        'wrap' => '|5/',
                    ],
                    15 => 'dummyPath6/',
                    '25.' => [
                        'field' => 'someField',
                    ],
                ]
            ]
        );
    }

    /**
     * @test
     */
    public function renderSetsTemplateFileInView()
    {
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
        $this->subject->render(['file' => 'foo']);
    }

    /**
     * @test
     */
    public function renderSetsTemplateFileByTemplateInView()
    {
        $this->addMockViewToSubject();

        $this->contentObjectRenderer
            ->expects($this->any())
            ->method('cObjGetSingle')
            ->with('FILE', ['file' => PATH_site . 'foo/bar.html'])
            ->will($this->returnValue('baz'));

        $this->standaloneView
            ->expects($this->any())
            ->method('setTemplateSource')
            ->with('baz');

        $this->subject->render([
            'template' => 'FILE',
            'template.' => [
                'file' => PATH_site . 'foo/bar.html'
            ]
        ]);
    }

    /**
     * @test
     */
    public function renderSetsTemplateFileByTemplateNameInView()
    {
        $this->addMockViewToSubject();

        $this->standaloneView
            ->expects($this->any())
            ->method('getFormat')
            ->will($this->returnValue('html'));
        $this->standaloneView
            ->expects($this->once())
            ->method('setTemplate')
            ->with('foo');

        $this->subject->render([
            'templateName' => 'foo',
            'templateRootPaths.' => [
                0 => 'dummyPath1/',
                1 => 'dummyPath2/']
            ]
        );
    }

    /**
     * @test
     */
    public function renderSetsTemplateFileByTemplateNameStdWrapInView()
    {
        $this->addMockViewToSubject();

        $this->contentObjectRenderer
            ->expects($this->once())
            ->method('stdWrap')
            ->with('TEXT', ['value' => 'bar'])
            ->will($this->returnValue('bar'));
        $this->standaloneView
            ->expects($this->any())
            ->method('getFormat')
            ->will($this->returnValue('html'));
        $this->standaloneView
            ->expects($this->once())
            ->method('setTemplate')
            ->with('bar');

        $this->subject->render([
            'templateName' => 'TEXT',
            'templateName.' => ['value' => 'bar'],
            'templateRootPaths.' => [
                0 => 'dummyPath1/',
                1 => 'dummyPath2/']
            ]
        );
    }

    /**
     * @test
     */
    public function renderSetsLayoutRootPathInView()
    {
        $this->addMockViewToSubject();
        $this->standaloneView
            ->expects($this->once())
            ->method('setLayoutRootPaths')
            ->with([PATH_site . 'foo/bar.html']);
        $this->subject->render(['layoutRootPath' => 'foo/bar.html']);
    }

    /**
     * @test
     */
    public function renderCallsStandardWrapForLayoutRootPath()
    {
        $this->addMockViewToSubject();
        $this->contentObjectRenderer
            ->expects($this->once())
            ->method('stdWrap')
            ->with('foo', ['bar' => 'baz']);
        $this->subject->render(['layoutRootPath' => 'foo', 'layoutRootPath.' => ['bar' => 'baz']]);
    }

    /**
     * @test
     */
    public function layoutRootPathsHasStdWrapSupport()
    {
        $this->addMockViewToSubject();
        $this->contentObjectRenderer
            ->expects($this->at(0))
            ->method('stdWrap')
            ->with('FILE', ['file' => 'foo/bar.html']);
        $this->subject->render(
            [
                'layoutRootPaths.' => [
                    10 => 'FILE',
                    '10.' => [
                        'file' => 'foo/bar.html',
                    ],
                    20 => 'foo/bar2.html',
                ]
            ]
        );
    }

    /**
     * @test
     */
    public function fallbacksForLayoutRootPathAreSet()
    {
        $this->addMockViewToSubject();
        $this->standaloneView
            ->expects($this->once())
            ->method('setLayoutRootPaths')
            ->with([10 => PATH_site . 'foo/bar.html', 20 => PATH_site . 'foo/bar2.html']);
        $this->subject->render(['layoutRootPaths.' => [10 => 'foo/bar.html', 20 => 'foo/bar2.html']]);
    }

    /**
     * @test
     */
    public function fallbacksForLayoutRootPathAreAppendedToLayoutRootPath()
    {
        $this->addMockViewToSubject();
        $this->standaloneView
            ->expects($this->once())
            ->method('setLayoutRootPaths')
            ->with([0 => PATH_site . 'foo/main.html', 10 => PATH_site . 'foo/bar.html', 20 => PATH_site . 'foo/bar2.html']);
        $this->subject->render(['layoutRootPath' => 'foo/main.html', 'layoutRootPaths.' => [10 => 'foo/bar.html', 20 => 'foo/bar2.html']]);
    }

    /**
     * @test
     */
    public function renderSetsPartialRootPathInView()
    {
        $this->addMockViewToSubject();
        $this->standaloneView
            ->expects($this->once())
            ->method('setPartialRootPaths')
            ->with([PATH_site . 'foo/bar.html']);
        $this->subject->render(['partialRootPath' => 'foo/bar.html']);
    }

    /**
     * @test
     */
    public function partialRootPathsHasStdWrapSupport()
    {
        $this->addMockViewToSubject();
        $this->contentObjectRenderer
            ->expects($this->at(0))
            ->method('stdWrap')
            ->with('FILE', ['file' => 'foo/bar.html']);
        $this->subject->render(
            [
                'partialRootPaths.' => [
                    10 => 'FILE',
                    '10.' => [
                        'file' => 'foo/bar.html',
                    ],
                    20 => 'foo/bar2.html',
                ]
            ]
        );
    }

    /**
     * @test
     */
    public function renderCallsStandardWrapForPartialRootPath()
    {
        $this->addMockViewToSubject();
        $this->contentObjectRenderer
            ->expects($this->once())
            ->method('stdWrap')
            ->with('foo', ['bar' => 'baz']);
        $this->subject->render(['partialRootPath' => 'foo', 'partialRootPath.' => ['bar' => 'baz']]);
    }

    /**
     * @test
     */
    public function fallbacksForPartialRootPathAreSet()
    {
        $this->addMockViewToSubject();
        $this->standaloneView
            ->expects($this->once())
            ->method('setPartialRootPaths')
            ->with([10 => PATH_site . 'foo', 20 => PATH_site . 'bar']);
        $this->subject->render(['partialRootPaths.' => [10 => 'foo', 20 => 'bar']]);
    }

    /**
     * @test
     */
    public function fallbacksForPartialRootPathAreAppendedToPartialRootPath()
    {
        $this->addMockViewToSubject();
        $this->standaloneView
            ->expects($this->once())
            ->method('setPartialRootPaths')
            ->with([0 => PATH_site . 'main', 10 => PATH_site . 'foo', 20 => PATH_site . 'bar']);
        $this->subject->render(['partialRootPath' => 'main', 'partialRootPaths.' => [10 => 'foo', 20 => 'bar']]);
    }

    /**
     * @test
     */
    public function renderSetsFormatInView()
    {
        $this->addMockViewToSubject();
        $this->standaloneView
            ->expects($this->once())
            ->method('setFormat')
            ->with('xml');
        $this->subject->render(['format' => 'xml']);
    }

    /**
     * @test
     */
    public function renderCallsStandardWrapForFormat()
    {
        $this->addMockViewToSubject();
        $this->contentObjectRenderer
            ->expects($this->once())
            ->method('stdWrap')
            ->with('foo', ['bar' => 'baz']);
        $this->subject->render(['format' => 'foo', 'format.' => ['bar' => 'baz']]);
    }

    /**
     * @test
     */
    public function renderSetsExtbasePluginNameInRequest()
    {
        $this->addMockViewToSubject();
        $this->request
            ->expects($this->once())
            ->method('setPluginName')
            ->with('foo');
        $configuration = [
            'extbase.' => [
                'pluginName' => 'foo',
            ],
        ];
        $this->subject->render($configuration);
    }

    /**
     * @test
     */
    public function renderCallsStandardWrapForExtbasePluginName()
    {
        $this->addMockViewToSubject();
        $this->contentObjectRenderer
            ->expects($this->once())
            ->method('stdWrap')
            ->with('foo', ['bar' => 'baz']);
        $configuration = [
            'extbase.' => [
                'pluginName' => 'foo',
                'pluginName.' => [
                    'bar' => 'baz',
                ],
            ],
        ];
        $this->subject->render($configuration);
    }

    /**
     * @test
     */
    public function renderSetsExtbaseControllerExtensionNameInRequest()
    {
        $this->addMockViewToSubject();
        $this->request
            ->expects($this->once())
            ->method('setControllerExtensionName')
            ->with('foo');
        $configuration = [
            'extbase.' => [
                'controllerExtensionName' => 'foo',
            ],
        ];
        $this->subject->render($configuration);
    }

    /**
     * @test
     */
    public function renderCallsStandardWrapForExtbaseControllerExtensionName()
    {
        $this->addMockViewToSubject();
        $this->contentObjectRenderer
            ->expects($this->once())
            ->method('stdWrap')
            ->with('foo', ['bar' => 'baz']);
        $configuration = [
            'extbase.' => [
                'controllerExtensionName' => 'foo',
                'controllerExtensionName.' => [
                    'bar' => 'baz',
                ],
            ],
        ];
        $this->subject->render($configuration);
    }

    /**
     * @test
     */
    public function renderSetsExtbaseControllerNameInRequest()
    {
        $this->addMockViewToSubject();
        $this->request
            ->expects($this->once())
            ->method('setControllerName')
            ->with('foo');
        $configuration = [
            'extbase.' => [
                'controllerName' => 'foo',
            ],
        ];
        $this->subject->render($configuration);
    }

    /**
     * @test
     */
    public function renderCallsStandardWrapForExtbaseControllerName()
    {
        $this->addMockViewToSubject();
        $this->contentObjectRenderer
            ->expects($this->once())
            ->method('stdWrap')
            ->with('foo', ['bar' => 'baz']);
        $configuration = [
            'extbase.' => [
                'controllerName' => 'foo',
                'controllerName.' => [
                    'bar' => 'baz',
                ],
            ],
        ];
        $this->subject->render($configuration);
    }

    /**
     * @test
     */
    public function renderSetsExtbaseControllerActionNameInRequest()
    {
        $this->addMockViewToSubject();
        $this->request
            ->expects($this->once())
            ->method('setControllerActionName')
            ->with('foo');
        $configuration = [
            'extbase.' => [
                'controllerActionName' => 'foo',
            ],
        ];
        $this->subject->render($configuration);
    }

    /**
     * @test
     */
    public function renderCallsStandardWrapForExtbaseControllerActionName()
    {
        $this->addMockViewToSubject();
        $this->contentObjectRenderer
            ->expects($this->once())
            ->method('stdWrap')
            ->with('foo', ['bar' => 'baz']);
        $configuration = [
            'extbase.' => [
                'controllerActionName' => 'foo',
                'controllerActionName.' => [
                    'bar' => 'baz',
                ],
            ],
        ];
        $this->subject->render($configuration);
    }

    /**
     * @test
     */
    public function renderAssignsSettingsArrayToView()
    {
        $this->addMockViewToSubject();

        $configuration = [
            'settings.' => [
                'foo' => 'value',
                'bar.' => [
                    'baz' => 'value2',
                ],
            ],
        ];

        $expectedSettingsToBeSet = [
            'foo' => 'value',
            'bar' => [
                'baz' => 'value2',
            ],
        ];

        /** @var TypoScriptService|\PHPUnit_Framework_MockObject_MockObject $typoScriptServiceMock */
        $typoScriptServiceMock = $this->getMock(\TYPO3\CMS\Extbase\Service\TypoScriptService::class);
        $typoScriptServiceMock
            ->expects($this->once())
            ->method('convertTypoScriptArrayToPlainArray')
            ->with($configuration['settings.'])
            ->will($this->returnValue($expectedSettingsToBeSet));
        GeneralUtility::setSingletonInstance(\TYPO3\CMS\Extbase\Service\TypoScriptService::class, $typoScriptServiceMock);

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
    public function renderThrowsExceptionForNotAllowedVariableData()
    {
        $this->addMockViewToSubject();
        $configuration = [
            'variables.' => [
                'data' => 'foo',
                'data.' => [
                    'bar' => 'baz',
                ],
            ],
        ];
        $this->subject->render($configuration);
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function renderThrowsExceptionForNotAllowedVariableCurrent()
    {
        $this->addMockViewToSubject();
        $configuration = [
            'variables.' => [
                'current' => 'foo',
                'current.' => [
                    'bar' => 'baz',
                ],
            ],
        ];
        $this->subject->render($configuration);
    }

    /**
     * @test
     */
    public function renderCallsCObjGetSingleForAllowedVariable()
    {
        $this->addMockViewToSubject();
        $configuration = [
            'variables.' => [
                'aVar' => 'TEXT',
                'aVar.' => [
                    'value' => 'foo',
                ],
            ],
        ];
        $this->contentObjectRenderer
            ->expects($this->once())
            ->method('cObjGetSingle')
            ->with('TEXT', ['value' => 'foo']);
        $this->subject->render($configuration);
    }

    /**
     * @test
     */
    public function renderAssignsRenderedContentObjectVariableToView()
    {
        $this->addMockViewToSubject();
        $configuration = [
            'variables.' => [
                'aVar' => 'TEXT',
                'aVar.' => [
                    'value' => 'foo',
                ],
            ],
        ];
        $this->contentObjectRenderer
            ->expects($this->once())
            ->method('cObjGetSingle')
            ->will($this->returnValue('foo'));
        $this->standaloneView
            ->expects($this->once())
            ->method('assignMultiple')
            ->with(['aVar' => 'foo', 'data' => [], 'current' => null]);
        $this->subject->render($configuration);
    }

    /**
     * @test
     */
    public function renderAssignsContentObjectRendererDataToView()
    {
        $this->addMockViewToSubject();
        $this->contentObjectRenderer->data = ['foo'];
        $this->standaloneView
            ->expects($this->once())
            ->method('assignMultiple')
            ->with(['data' => ['foo'], 'current' => null]);
        $this->subject->render([]);
    }

    /**
     * @test
     */
    public function renderAssignsContentObjectRendererCurrentValueToView()
    {
        $this->addMockViewToSubject();
        $this->contentObjectRenderer->data = ['currentKey' => 'currentValue'];
        $this->contentObjectRenderer->currentValKey = 'currentKey';
        $this->standaloneView
            ->expects($this->once())
            ->method('assignMultiple')
            ->with(['data' => ['currentKey' => 'currentValue'], 'current' => 'currentValue']);
        $this->subject->render([]);
    }

    /**
     * @test
     */
    public function renderCallsRenderOnStandaloneViewie()
    {
        $this->addMockViewToSubject();
        $this->standaloneView
            ->expects($this->once())
            ->method('render');
        $this->subject->render([]);
    }

    /**
     * @test
     */
    public function renderCallsStandardWrapOnResultStringIfGivenInConfiguration()
    {
        $this->addMockViewToSubject();
        $configuration = [
            'stdWrap.' => [
                'foo' => 'bar',
            ],
        ];
        $this->standaloneView
            ->expects($this->any())
            ->method('render')
            ->will($this->returnValue('baz'));
        $this->contentObjectRenderer
            ->expects($this->once())
            ->method('stdWrap')
            ->with('baz', ['foo' => 'bar']);
        $this->subject->render($configuration);
    }
}
