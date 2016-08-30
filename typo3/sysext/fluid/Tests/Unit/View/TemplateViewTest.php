<?php
namespace TYPO3\CMS\Fluid\Tests\Unit\View;

/*                                                                        *
 * This script is backported from the TYPO3 Flow package "TYPO3.Fluid".   *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

include_once(__DIR__ . '/Fixtures/TransparentSyntaxTreeNode.php');
include_once(__DIR__ . '/Fixtures/TemplateViewFixture.php');

use org\bovigo\vfs\vfsStreamWrapper;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\PhpFrontend;
use TYPO3\CMS\Core\Tests\AccessibleObjectInterface;
use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext;
use TYPO3\CMS\Extbase\Mvc\Web\Request as WebRequest;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Fluid\Core\Compiler\TemplateCompiler;
use TYPO3\CMS\Fluid\Core\Parser\ParsedTemplateInterface;
use TYPO3\CMS\Fluid\Core\Parser\TemplateParser;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContext;
use TYPO3\CMS\Fluid\Core\ViewHelper\ViewHelperVariableContainer;
use TYPO3\CMS\Fluid\View\TemplateView;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

/**
 * Test case
 */
class TemplateViewTest extends UnitTestCase
{
    /**
     * Test for #42123
     * "Widgets with underscores in class names do not work because the subpackage key is not handled correctly."
     * @test
     */
    public function expandGenericPathPatternWorksWithOldNamingSchemeOfSubPackage()
    {
        $mockControllerContext = $this->setupMockControllerContextForPathResolving('MyPackage', 'ViewHelpers_Widget', 'Paginate', 'html');
        $templateView = $this->getAccessibleMock(TemplateView::class, ['dummy'], [], '', false);
        $templateView->_set('controllerContext', $mockControllerContext);
        $expected = [ExtensionManagementUtility::extPath('frontend') . 'Resources/Private/Templates/ViewHelpers/Widget/Paginate/@action.html'];
        $actual = $templateView->_call('expandGenericPathPattern', '@templateRoot/@subpackage/@controller/@action.@format', false, false);
        $this->assertEquals($expected, $actual);
    }

    /**
     * Test for #42123
     * "Widgets with underscores in class names do not work because the subpackage key is not handled correctly."
     * @test
     */
    public function expandGenericPathPatternWorksWithNewNamingSchemeOfSubPackage()
    {
        $mockControllerContext = $this->setupMockControllerContextForPathResolving('MyPackage', 'ViewHelpers\\Widget', 'Paginate', 'html');
        $templateView = $this->getAccessibleMock(TemplateView::class, ['dummy'], [], '', false);
        $templateView->_set('controllerContext', $mockControllerContext);
        $expected = [ExtensionManagementUtility::extPath('frontend') . 'Resources/Private/Templates/ViewHelpers/Widget/Paginate/@action.html'];
        $actual = $templateView->_call('expandGenericPathPattern', '@templateRoot/@subpackage/@controller/@action.@format', false, false);
        $this->assertEquals($expected, $actual);
    }

    /**
     * Helper to build mock controller context needed to test expandGenericPathPattern.
     *
     * @param string $packageKey
     * @param string $subPackageKey
     * @param string $controllerName
     * @param string $format
     * @return ControllerContext
     */
    protected function setupMockControllerContextForPathResolving($packageKey, $subPackageKey, $controllerName, $format)
    {
        $controllerObjectName = "TYPO3\\$packageKey\\" . ($subPackageKey != $subPackageKey . '\\' ? : '') . 'Controller\\' . $controllerName . 'Controller';
        $mockRequest = $this->getMock(WebRequest::class);
        $mockRequest->expects($this->any())->method('getControllerExtensionKey')->will($this->returnValue('frontend'));
        $mockRequest->expects($this->any())->method('getControllerPackageKey')->will($this->returnValue($packageKey));
        $mockRequest->expects($this->any())->method('getControllerSubPackageKey')->will($this->returnValue($subPackageKey));
        $mockRequest->expects($this->any())->method('getControllerName')->will($this->returnValue($controllerName));
        $mockRequest->expects($this->any())->method('getControllerObjectName')->will($this->returnValue($controllerObjectName));
        $mockRequest->expects($this->any())->method('getFormat')->will($this->returnValue($format));

        $mockControllerContext = $this->getMock(ControllerContext::class, ['getRequest'], [], '', false);
        $mockControllerContext->expects($this->any())->method('getRequest')->will($this->returnValue($mockRequest));

        return $mockControllerContext;
    }

    /**
     * @return array
     */
    public function expandGenericPathPatternDataProvider()
    {
        return [
            // bubbling controller & subpackage parts and optional format
            [
                'package' => 'Some.Package',
                'subPackage' => 'Some\\Sub\\Package',
                'controller' => 'SomeController',
                'format' => 'html',
                'templateRootPath' => 'Resources/Private/Templates',
                'templateRootPaths' => null,
                'partialRootPath' => 'Resources/Private/Partials',
                'partialRootPaths' => null,
                'layoutRootPath' => 'Resources/Private/Layouts',
                'layoutRootPaths' => null,
                'bubbleControllerAndSubpackage' => true,
                'formatIsOptional' => true,
                'pattern' => '@templateRoot/@subpackage/@controller/@action.@format',
                'expectedResult' => [
                    'Resources/Private/Templates/Some/Sub/Package/SomeController/@action.html',
                    'Resources/Private/Templates/Some/Sub/Package/SomeController/@action',
                    'Resources/Private/Templates/Some/Sub/Package/@action.html',
                    'Resources/Private/Templates/Some/Sub/Package/@action',
                    'Resources/Private/Templates/Sub/Package/@action.html',
                    'Resources/Private/Templates/Sub/Package/@action',
                    'Resources/Private/Templates/Package/@action.html',
                    'Resources/Private/Templates/Package/@action',
                    'Resources/Private/Templates/@action.html',
                    'Resources/Private/Templates/@action',
                ]
            ],
            // just optional format
            [
                'package' => 'Some.Package',
                'subPackage' => 'Some\\Sub\\Package',
                'controller' => 'SomeController',
                'format' => 'html',
                'templateRootPath' => 'Resources/Private/Templates/',
                'templateRootPaths' => null,
                'partialRootPath' => 'Resources/Private/Partials',
                'partialRootPaths' => null,
                'layoutRootPath' => 'Resources/Private/Layouts',
                'layoutRootPaths' => null,
                'bubbleControllerAndSubpackage' => false,
                'formatIsOptional' => true,
                'pattern' => '@templateRoot/@subpackage/@controller/@action.@format',
                'expectedResult' => [
                    'Resources/Private/Templates/Some/Sub/Package/SomeController/@action.html',
                    'Resources/Private/Templates/Some/Sub/Package/SomeController/@action',
                ]
            ],
            // just bubbling controller & subpackage parts
            [
                'package' => 'Some.Package',
                'subPackage' => 'Some\\Sub\\Package',
                'controller' => 'SomeController',
                'format' => 'json',
                'templateRootPath' => 'Resources/Private/Templates',
                'templateRootPaths' => null,
                'partialRootPath' => 'Resources/Private/Partials',
                'partialRootPaths' => null,
                'layoutRootPath' => 'Resources/Private/Layouts',
                'layoutRootPaths' => null,
                'bubbleControllerAndSubpackage' => true,
                'formatIsOptional' => false,
                'pattern' => '@partialRoot/@subpackage/@controller/@action.@format',
                'expectedResult' => [
                    'Resources/Private/Partials/Some/Sub/Package/SomeController/@action.json',
                    'Resources/Private/Partials/Some/Sub/Package/@action.json',
                    'Resources/Private/Partials/Sub/Package/@action.json',
                    'Resources/Private/Partials/Package/@action.json',
                    'Resources/Private/Partials/@action.json',
                ]
            ],
            // layoutRootPath
            [
                'package' => 'Some.Package',
                'subPackage' => null,
                'controller' => null,
                'format' => 'xml',
                'templateRootPath' => 'Resources/Private/Templates',
                'templateRootPaths' => null,
                'partialRootPath' => 'Resources/Private/Partials',
                'partialRootPaths' => null,
                'layoutRootPath' => 'Resources/Private/Layouts',
                'layoutRootPaths' => null,
                'bubbleControllerAndSubpackage' => true,
                'formatIsOptional' => true,
                'pattern' => '@layoutRoot/@subpackage/@controller/@action.@format',
                'expectedResult' => [
                    'Resources/Private/Layouts/@action.xml',
                    'Resources/Private/Layouts/@action',
                ]
            ],
            // partialRootPath
            [
                'package' => 'Some.Package',
                'subPackage' => 'Some\\Sub\\Package',
                'controller' => null,
                'format' => 'html',
                'templateRootPath' => 'Resources/Private/Templates',
                'templateRootPaths' => null,
                'partialRootPath' => 'Resources/Private/Partials',
                'partialRootPaths' => null,
                'layoutRootPath' => 'Resources/Private/Layouts',
                'layoutRootPaths' => null,
                'bubbleControllerAndSubpackage' => true,
                'formatIsOptional' => true,
                'pattern' => '@templateRoot/@subpackage/@controller/@action.@format',
                'expectedResult' => [
                    'Resources/Private/Templates/Some/Sub/Package/@action.html',
                    'Resources/Private/Templates/Some/Sub/Package/@action',
                    'Resources/Private/Templates/Sub/Package/@action.html',
                    'Resources/Private/Templates/Sub/Package/@action',
                    'Resources/Private/Templates/Package/@action.html',
                    'Resources/Private/Templates/Package/@action',
                    'Resources/Private/Templates/@action.html',
                    'Resources/Private/Templates/@action',
                ]
            ],
            // optional format as directory name
            [
                'package' => 'Some.Package',
                'subPackage' => 'Some\\Sub\\Package',
                'controller' => 'SomeController',
                'format' => 'xml',
                'templateRootPath' => 'Resources/Private/Templates_@format',
                'templateRootPaths' => null,
                'partialRootPath' => 'Resources/Private/Partials',
                'partialRootPaths' => null,
                'layoutRootPath' => 'Resources/Private/Layouts',
                'layoutRootPaths' => null,
                'bubbleControllerAndSubpackage' => false,
                'formatIsOptional' => true,
                'pattern' => '@templateRoot/@subpackage/@controller/@action',
                'expectedResult' => [
                    'Resources/Private/Templates_xml/Some/Sub/Package/SomeController/@action',
                    'Resources/Private/Templates_/Some/Sub/Package/SomeController/@action',
                ]
            ],
            // mandatory format as directory name
            [
                'package' => 'Some.Package',
                'subPackage' => 'Some\\Sub\\Package',
                'controller' => 'SomeController',
                'format' => 'json',
                'templateRootPath' => 'Resources/Private/Templates_@format',
                'templateRootPaths' => null,
                'partialRootPath' => 'Resources/Private/Partials',
                'partialRootPaths' => null,
                'layoutRootPath' => 'Resources/Private/Layouts',
                'layoutRootPaths' => null,
                'bubbleControllerAndSubpackage' => false,
                'formatIsOptional' => false,
                'pattern' => '@templateRoot/@subpackage/@controller/@action',
                'expectedResult' => [
                    'Resources/Private/Templates_json/Some/Sub/Package/SomeController/@action',
                ]
            ],
            // paths must not contain double slashes
            [
                'package' => 'Some.Package',
                'subPackage' => null,
                'controller' => 'SomeController',
                'format' => 'html',
                'templateRootPath' => 'Resources/Private/Templates',
                'templateRootPaths' => null,
                'partialRootPath' => 'Resources/Private/Partials',
                'partialRootPaths' => null,
                'layoutRootPath' => 'Some/Root/Path/',
                'layoutRootPaths' => null,
                'bubbleControllerAndSubpackage' => true,
                'formatIsOptional' => true,
                'pattern' => '@layoutRoot/@subpackage/@controller/@action.@format',
                'expectedResult' => [
                    'Some/Root/Path/SomeController/@action.html',
                    'Some/Root/Path/SomeController/@action',
                    'Some/Root/Path/@action.html',
                    'Some/Root/Path/@action',
                ]
            ],
            // paths must be unique
            [
                'package' => 'Some.Package',
                'subPackage' => 'Some\\Sub\\Package',
                'controller' => 'SomeController',
                'format' => 'json',
                'templateRootPath' => 'Resources/Private/Templates',
                'templateRootPaths' => null,
                'partialRootPath' => 'Resources/Private/Partials',
                'partialRootPaths' => null,
                'layoutRootPath' => 'Resources/Private/Layouts',
                'layoutRootPaths' => null,
                'bubbleControllerAndSubpackage' => true,
                'formatIsOptional' => false,
                'pattern' => 'foo',
                'expectedResult' => [
                    'foo',
                ]
            ],
            // template fallback paths
            [
                'package' => 'Some.Package',
                'subPackage' => 'Some\\Sub\\Package',
                'controller' => 'SomeController',
                'format' => 'html',
                'templateRootPath' => 'Resources/Private/Templates',
                'templateRootPaths' => ['Resources/Private/Templates', 'Some/Fallback/Path'],
                'partialRootPath' => 'Resources/Private/Partials',
                'partialRootPaths' => null,
                'layoutRootPath' => 'Resources/Private/Layouts',
                'layoutRootPaths' => null,
                'bubbleControllerAndSubpackage' => false,
                'formatIsOptional' => true,
                'pattern' => '@templateRoot/@subpackage/@controller/@action.@format',
                'expectedResult' => [
                    'Resources/Private/Templates/Some/Sub/Package/SomeController/@action.html',
                    'Resources/Private/Templates/Some/Sub/Package/SomeController/@action',
                    'Some/Fallback/Path/Some/Sub/Package/SomeController/@action.html',
                    'Some/Fallback/Path/Some/Sub/Package/SomeController/@action',
                ]
            ],
            // template fallback paths with bubbleControllerAndSubpackage
            [
                'package' => 'Some.Package',
                'subPackage' => 'Some\\Sub\\Package',
                'controller' => 'SomeController',
                'format' => 'html',
                'templateRootPath' => 'Resources/Private/Templates',
                'templateRootPaths' => ['Resources/Private/Templates', 'Some/Fallback/Path'],
                'partialRootPath' => 'Resources/Private/Partials',
                'partialRootPaths' => null,
                'layoutRootPath' => 'Resources/Private/Layouts',
                'layoutRootPaths' => null,
                'bubbleControllerAndSubpackage' => true,
                'formatIsOptional' => false,
                'pattern' => '@templateRoot/@subpackage/@controller/@action.@format',
                'expectedResult' => [
                    'Resources/Private/Templates/Some/Sub/Package/SomeController/@action.html',
                    'Resources/Private/Templates/Some/Sub/Package/@action.html',
                    'Resources/Private/Templates/Sub/Package/@action.html',
                    'Resources/Private/Templates/Package/@action.html',
                    'Resources/Private/Templates/@action.html',
                    'Some/Fallback/Path/Some/Sub/Package/SomeController/@action.html',
                    'Some/Fallback/Path/Some/Sub/Package/@action.html',
                    'Some/Fallback/Path/Sub/Package/@action.html',
                    'Some/Fallback/Path/Package/@action.html',
                    'Some/Fallback/Path/@action.html',
                ]
            ],
            // partial fallback paths
            [
                'package' => 'Some.Package',
                'subPackage' => 'Some\\Sub\\Package',
                'controller' => 'SomeController',
                'format' => 'html',
                'templateRootPath' => 'Resources/Private/Templates',
                'templateRootPaths' => null,
                'partialRootPath' => 'Resources/Private/Partials',
                'partialRootPaths' => ['Default/Resources/Path', 'Fallback/'],
                'layoutRootPath' => 'Resources/Private/Layouts',
                'layoutRootPaths' => null,
                'bubbleControllerAndSubpackage' => false,
                'formatIsOptional' => true,
                'pattern' => '@partialRoot/@subpackage/@controller/@partial.@format',
                'expectedResult' => [
                    'Default/Resources/Path/Some/Sub/Package/SomeController/@partial.html',
                    'Default/Resources/Path/Some/Sub/Package/SomeController/@partial',
                    'Fallback/Some/Sub/Package/SomeController/@partial.html',
                    'Fallback/Some/Sub/Package/SomeController/@partial',
                ]
            ],
            // partial fallback paths with bubbleControllerAndSubpackage
            [
                'package' => 'Some.Package',
                'subPackage' => 'Some\\Sub\\Package',
                'controller' => 'SomeController',
                'format' => 'html',
                'templateRootPath' => 'Resources/Private/Templates',
                'templateRootPaths' => ['Resources/Private/Templates', 'Some/Fallback/Path'],
                'partialRootPath' => 'Resources/Private/Partials',
                'partialRootPaths' => ['Default/Resources/Path', 'Fallback1/', 'Fallback2'],
                'layoutRootPath' => 'Resources/Private/Layouts',
                'layoutRootPaths' => null,
                'bubbleControllerAndSubpackage' => true,
                'formatIsOptional' => true,
                'pattern' => '@partialRoot/@controller/@subpackage/@partial',
                'expectedResult' => [
                    'Default/Resources/Path/SomeController/Some/Sub/Package/@partial',
                    'Default/Resources/Path/Some/Sub/Package/@partial',
                    'Default/Resources/Path/Sub/Package/@partial',
                    'Default/Resources/Path/Package/@partial',
                    'Default/Resources/Path/@partial',
                    'Fallback1/SomeController/Some/Sub/Package/@partial',
                    'Fallback1/Some/Sub/Package/@partial',
                    'Fallback1/Sub/Package/@partial',
                    'Fallback1/Package/@partial',
                    'Fallback1/@partial',
                    'Fallback2/SomeController/Some/Sub/Package/@partial',
                    'Fallback2/Some/Sub/Package/@partial',
                    'Fallback2/Sub/Package/@partial',
                    'Fallback2/Package/@partial',
                    'Fallback2/@partial',
                ]
            ],
            // layout fallback paths
            [
                'package' => 'Some.Package',
                'subPackage' => 'Some\\Sub\\Package',
                'controller' => 'SomeController',
                'format' => 'html',
                'templateRootPath' => 'Resources/Private/Templates',
                'templateRootPaths' => ['Resources/Private/Templates', 'Some/Fallback/Path'],
                'partialRootPath' => 'Resources/Private/Partials',
                'partialRootPaths' => ['foo', 'bar'],
                'layoutRootPath' => 'Resources/Private/Layouts',
                'layoutRootPaths' => ['Default/Layout/Path', 'Fallback/Path'],
                'bubbleControllerAndSubpackage' => false,
                'formatIsOptional' => false,
                'pattern' => '@layoutRoot/@subpackage/@controller/@layout.@format',
                'expectedResult' => [
                    'Default/Layout/Path/Some/Sub/Package/SomeController/@layout.html',
                    'Fallback/Path/Some/Sub/Package/SomeController/@layout.html',
                ]
            ],
            // layout fallback paths with bubbleControllerAndSubpackage
            [
                'package' => 'Some.Package',
                'subPackage' => 'Some\\Sub\\Package',
                'controller' => 'SomeController',
                'format' => 'html',
                'templateRootPath' => 'Resources/Private/Templates',
                'templateRootPaths' => null,
                'partialRootPath' => 'Resources/Private/Partials',
                'partialRootPaths' => null,
                'layoutRootPath' => 'Resources/Private/Layouts',
                'layoutRootPaths' => ['Resources/Layouts', 'Some/Fallback/Path'],
                'bubbleControllerAndSubpackage' => true,
                'formatIsOptional' => true,
                'pattern' => 'Static/@layoutRoot/@subpackage/@controller/@layout.@format',
                'expectedResult' => [
                    'Static/Resources/Layouts/Some/Sub/Package/SomeController/@layout.html',
                    'Static/Resources/Layouts/Some/Sub/Package/SomeController/@layout',
                    'Static/Resources/Layouts/Some/Sub/Package/@layout.html',
                    'Static/Resources/Layouts/Some/Sub/Package/@layout',
                    'Static/Resources/Layouts/Sub/Package/@layout.html',
                    'Static/Resources/Layouts/Sub/Package/@layout',
                    'Static/Resources/Layouts/Package/@layout.html',
                    'Static/Resources/Layouts/Package/@layout',
                    'Static/Resources/Layouts/@layout.html',
                    'Static/Resources/Layouts/@layout',
                    'Static/Some/Fallback/Path/Some/Sub/Package/SomeController/@layout.html',
                    'Static/Some/Fallback/Path/Some/Sub/Package/SomeController/@layout',
                    'Static/Some/Fallback/Path/Some/Sub/Package/@layout.html',
                    'Static/Some/Fallback/Path/Some/Sub/Package/@layout',
                    'Static/Some/Fallback/Path/Sub/Package/@layout.html',
                    'Static/Some/Fallback/Path/Sub/Package/@layout',
                    'Static/Some/Fallback/Path/Package/@layout.html',
                    'Static/Some/Fallback/Path/Package/@layout',
                    'Static/Some/Fallback/Path/@layout.html',
                    'Static/Some/Fallback/Path/@layout',
                ]
            ],
            // combined fallback paths
            [
                'package' => 'Some.Package',
                'subPackage' => 'Some\\Sub\\Package',
                'controller' => 'SomeController',
                'format' => 'html',
                'templateRootPath' => 'Resources/Private/Templates',
                'templateRootPaths' => ['Resources/Templates', 'Templates/Fallback1', 'Templates/Fallback2'],
                'partialRootPath' => 'Resources/Private/Partials',
                'partialRootPaths' => ['Resources/Partials'],
                'layoutRootPath' => 'Resources/Private/Layouts',
                'layoutRootPaths' => ['Resources/Layouts', 'Layouts/Fallback1'],
                'bubbleControllerAndSubpackage' => false,
                'formatIsOptional' => true,
                'pattern' => '@layoutRoot/@templateRoot/@partialRoot/@subpackage/@controller/foo',
                'expectedResult' => [
                    'Resources/Layouts/Resources/Templates/Resources/Partials/Some/Sub/Package/SomeController/foo',
                    'Layouts/Fallback1/Resources/Templates/Resources/Partials/Some/Sub/Package/SomeController/foo',
                    'Resources/Layouts/Templates/Fallback1/Resources/Partials/Some/Sub/Package/SomeController/foo',
                    'Layouts/Fallback1/Templates/Fallback1/Resources/Partials/Some/Sub/Package/SomeController/foo',
                    'Resources/Layouts/Templates/Fallback2/Resources/Partials/Some/Sub/Package/SomeController/foo',
                    'Layouts/Fallback1/Templates/Fallback2/Resources/Partials/Some/Sub/Package/SomeController/foo',
                ]
            ],
        ];
    }

    /**
     * @test
     * @dataProvider expandGenericPathPatternDataProvider()
     *
     * @param string $package
     * @param string $subPackage
     * @param string $controller
     * @param string $format
     * @param string $templateRootPath
     * @param array $templateRootPaths
     * @param string $partialRootPath
     * @param array $partialRootPaths
     * @param string $layoutRootPath
     * @param array $layoutRootPaths
     * @param bool $bubbleControllerAndSubpackage
     * @param bool $formatIsOptional
     * @param string $pattern
     * @param string $expectedResult
     */
    public function expandGenericPathPatternTests($package, $subPackage, $controller, $format, $templateRootPath, array $templateRootPaths = null, $partialRootPath, array $partialRootPaths = null, $layoutRootPath, array $layoutRootPaths = null, $bubbleControllerAndSubpackage, $formatIsOptional, $pattern, $expectedResult)
    {
        $mockControllerContext = $this->setupMockControllerContextForPathResolving($package, $subPackage, $controller, $format);

        /** @var TemplateView|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface $templateView */
        $templateView = $this->getAccessibleMock(TemplateView::class, ['dummy'], [], '', false);
        $templateView->setControllerContext($mockControllerContext);
        if ($templateRootPath !== null) {
            $templateView->setTemplateRootPath($templateRootPath);
        }
        if ($templateRootPaths !== null) {
            $templateView->setTemplateRootPaths($templateRootPaths);
        }

        if ($partialRootPath !== null) {
            $templateView->setPartialRootPath($partialRootPath);
        }
        if ($partialRootPaths !== null) {
            $templateView->setPartialRootPaths($partialRootPaths);
        }

        if ($layoutRootPath !== null) {
            $templateView->setLayoutRootPath($layoutRootPath);
        }
        if ($layoutRootPaths !== null) {
            $templateView->setLayoutRootPaths($layoutRootPaths);
        }

        $actualResult = $templateView->_call('expandGenericPathPattern', $pattern, $bubbleControllerAndSubpackage, $formatIsOptional);
        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function expandGenericPathPatternWorksWithBubblingDisabledAndFormatNotOptional()
    {
        $mockControllerContext = $this->setupMockControllerContextForPathResolving('MyPackage', null, 'My', 'html');

        $templateView = $this->getAccessibleMock(TemplateView::class, ['getTemplateRootPaths'], [], '', false);
        $templateView->_set('controllerContext', $mockControllerContext);
        $templateView->expects($this->any())->method('getTemplateRootPaths')->will($this->returnValue(['Resources/Private/']));

        $expected = ['Resources/Private/Templates/My/@action.html'];
        $actual = $templateView->_call('expandGenericPathPattern', '@templateRoot/Templates/@subpackage/@controller/@action.@format', false, false);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function expandGenericPathPatternWorksWithSubpackageAndBubblingDisabledAndFormatNotOptional()
    {
        $mockControllerContext = $this->setupMockControllerContextForPathResolving('MyPackage', 'MySubPackage', 'My', 'html');

        $templateView = $this->getAccessibleMock(TemplateView::class, ['getTemplateRootPaths'], [], '', false);
        $templateView->_set('controllerContext', $mockControllerContext);
        $templateView->expects($this->any())->method('getTemplateRootPaths')->will($this->returnValue(['Resources/Private/']));
        $actual = $templateView->_call('expandGenericPathPattern', '@templateRoot/Templates/@subpackage/@controller/@action.@format', false, false);

        $expected = [
            'Resources/Private/Templates/MySubPackage/My/@action.html'
        ];
        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function expandGenericPathPatternWorksWithSubpackageAndBubblingDisabledAndFormatOptional()
    {
        $mockControllerContext = $this->setupMockControllerContextForPathResolving('MyPackage', 'MySubPackage', 'My', 'html');

        $templateView = $this->getAccessibleMock(TemplateView::class, ['getTemplateRootPaths'], [], '', false);
        $templateView->_set('controllerContext', $mockControllerContext);
        $templateView->expects($this->any())->method('getTemplateRootPaths')->will($this->returnValue(['Resources/Private/']));
        $actual = $templateView->_call('expandGenericPathPattern', '@templateRoot/Templates/@subpackage/@controller/@action.@format', false, true);

        $expected = [
            'Resources/Private/Templates/MySubPackage/My/@action.html',
            'Resources/Private/Templates/MySubPackage/My/@action'
        ];
        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function expandGenericPathPatternWorksWithSubpackageAndBubblingEnabledAndFormatOptional()
    {
        $mockControllerContext = $this->setupMockControllerContextForPathResolving('MyPackage', 'MySubPackage', 'My', 'html');

        $templateView = $this->getAccessibleMock(TemplateView::class, ['getTemplateRootPaths'], [], '', false);
        $templateView->_set('controllerContext', $mockControllerContext);
        $templateView->expects($this->any())->method('getTemplateRootPaths')->will($this->returnValue(['Resources/Private/']));
        $actual = $templateView->_call('expandGenericPathPattern', '@templateRoot/Templates/@subpackage/@controller/@action.@format', true, true);

        $expected = [
            'Resources/Private/Templates/MySubPackage/My/@action.html',
            'Resources/Private/Templates/MySubPackage/My/@action',
            'Resources/Private/Templates/MySubPackage/@action.html',
            'Resources/Private/Templates/MySubPackage/@action',
            'Resources/Private/Templates/@action.html',
            'Resources/Private/Templates/@action'
        ];
        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function getTemplateRootPathsReturnsUserSpecifiedTemplatePaths()
    {
        /** @var TemplateView|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface $templateView */
        $templateView = $this->getAccessibleMock(TemplateView::class, ['dummy'], [], '', false);
        $templateView->setTemplateRootPath('/foo/bar');
        $expected = ['/foo/bar'];
        $actual = $templateView->_call('getTemplateRootPaths');
        $this->assertEquals($expected, $actual, 'A set template root path was not returned correctly.');
    }

    /**
     * @test
     */
    public function setTemplateRootPathOverrulesSetTemplateRootPaths()
    {
        /** @var TemplateView|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface $templateView */
        $templateView = $this->getAccessibleMock(TemplateView::class, ['dummy'], [], '', false);
        $templateView->setTemplateRootPath('/foo/bar');
        $templateView->setTemplateRootPaths(['/overruled/path']);
        $expected = ['/overruled/path'];
        $actual = $templateView->_call('getTemplateRootPaths');
        $this->assertEquals($expected, $actual, 'A set template root path was not returned correctly.');
    }

    /**
     * @test
     */
    public function getPartialRootPathsReturnsUserSpecifiedPartialPath()
    {
        /** @var TemplateView|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface $templateView */
        $templateView = $this->getAccessibleMock(TemplateView::class, ['dummy'], [], '', false);
        $templateView->setPartialRootPath('/foo/bar');
        $expected = ['/foo/bar'];
        $actual = $templateView->_call('getPartialRootPaths');
        $this->assertEquals($expected, $actual, 'A set partial root path was not returned correctly.');
    }

    /**
     * @test
     */
    public function getLayoutRootPathsReturnsUserSpecifiedPartialPath()
    {
        /** @var TemplateView|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface $templateView */
        $templateView = $this->getAccessibleMock(TemplateView::class, ['dummy'], [], '', false);
        $templateView->setLayoutRootPath('/foo/bar');
        $expected = ['/foo/bar'];
        $actual = $templateView->_call('getLayoutRootPaths');
        $this->assertEquals($expected, $actual, 'A set partial root path was not returned correctly.');
    }

    /**
     * @test
     */
    public function pathToPartialIsResolvedCorrectly()
    {
        vfsStreamWrapper::register();
        mkdir('vfs://MyPartials');
        \file_put_contents('vfs://MyPartials/SomePartial', 'contentsOfSomePartial');

        $paths = [
            'vfs://NonExistentDir/UnknowFile.html',
            'vfs://MyPartials/SomePartial.html',
            'vfs://MyPartials/SomePartial'
        ];

        /** @var TemplateView|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface $templateView */
        $templateView = $this->getAccessibleMock(TemplateView::class, ['expandGenericPathPattern', 'resolveFileNamePath'], [], '', false);
        $templateView->expects($this->once())->method('expandGenericPathPattern')->with('@partialRoot/@subpackage/@partial.@format', true, true)->will($this->returnValue($paths));
        $templateView->expects($this->any())->method('resolveFileNamePath')->will($this->onConsecutiveCalls(
            $paths[0],
            $paths[1],
            $paths[2]
        ));

        $templateView->setTemplateRootPath('MyTemplates');
        $templateView->setPartialRootPath('MyPartials');
        $templateView->setLayoutRootPath('MyLayouts');

        $this->assertSame('contentsOfSomePartial', $templateView->_call('getPartialSource', 'SomePartial'));
    }

    /**
     * @test
     */
    public function resolveTemplatePathAndFilenameChecksDifferentPathPatternsAndReturnsTheFirstPathWhichExists()
    {
        vfsStreamWrapper::register();
        mkdir('vfs://MyTemplates');
        \file_put_contents('vfs://MyTemplates/MyCoolAction.html', 'contentsOfMyCoolAction');

        $paths = [
            'vfs://NonExistentDir/UnknownFile.html',
            'vfs://MyTemplates/@action.html'
        ];

        /** @var TemplateView|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface $templateView */
        $templateView = $this->getAccessibleMock(TemplateView::class, ['expandGenericPathPattern', 'resolveFileNamePath'], [], '', false);
        $templateView->expects($this->once())->method('expandGenericPathPattern')->with('@templateRoot/@subpackage/@controller/@action.@format', false, false)->will($this->returnValue($paths));
        $templateView->expects($this->any())->method('resolveFileNamePath')->will($this->onConsecutiveCalls(
            $paths[0],
            'vfs://MyTemplates/MyCoolAction.html'
        ));

        $templateView->setTemplateRootPath('MyTemplates');
        $templateView->setPartialRootPath('MyPartials');
        $templateView->setLayoutRootPath('MyLayouts');

        $this->assertSame('contentsOfMyCoolAction', $templateView->_call('getTemplateSource', 'myCoolAction'));
    }

    /**
     * @test
     */
    public function resolveTemplatePathAndFilenameReturnsTheExplicitlyConfiguredTemplatePathAndFilename()
    {
        vfsStreamWrapper::register();
        mkdir('vfs://MyTemplates');
        \file_put_contents('vfs://MyTemplates/MyCoolAction.html', 'contentsOfMyCoolAction');

        $templateView = $this->getAccessibleMock(TemplateView::class, ['resolveFileNamePath'], [], '', false);
        $templateView->expects($this->any())->method('resolveFileNamePath')->willReturnArgument(0);
        $templateView->_set('templatePathAndFilename', 'vfs://MyTemplates/MyCoolAction.html');

        $this->assertSame('contentsOfMyCoolAction', $templateView->_call('getTemplateSource'));
    }

    /**
     * @test
     */
    public function getLayoutPathAndFilenameRespectsCasingOfLayoutName()
    {
        $singletonInstances = GeneralUtility::getSingletonInstances();

        $mockParsedTemplate = $this->getMock(ParsedTemplateInterface::class);
        $mockTemplateParser = $this->getMock(TemplateParser::class);
        $mockTemplateParser->expects($this->any())->method('parse')->will($this->returnValue($mockParsedTemplate));

        /** @var ObjectManager|\PHPUnit_Framework_MockObject_MockObject $mockObjectManager */
        $mockObjectManager = $this->getMock(ObjectManager::class);
        $mockObjectManager->expects($this->any())->method('get')->will($this->returnCallback([$this, 'objectManagerCallback']));

        $mockRequest = $this->getMock(WebRequest::class);
        $mockControllerContext = $this->getMock(ControllerContext::class);
        $mockControllerContext->expects($this->any())->method('getRequest')->will($this->returnValue($mockRequest));

        $mockViewHelperVariableContainer = $this->getMock(ViewHelperVariableContainer::class);
        /** @var RenderingContext|\PHPUnit_Framework_MockObject_MockObject $mockRenderingContext */
        $mockRenderingContext = $this->getMock(RenderingContext::class);
        $mockRenderingContext->expects($this->any())->method('getControllerContext')->will($this->returnValue($mockControllerContext));
        $mockRenderingContext->expects($this->any())->method('getViewHelperVariableContainer')->will($this->returnValue($mockViewHelperVariableContainer));

        /** @var TemplateView|\PHPUnit_Framework_MockObject_MockObject|AccessibleObjectInterface $view */
        $view = $this->getAccessibleMock(TemplateView::class, ['testFileExistence', 'buildParserConfiguration'], [], '', false);
        $view->_set('templateParser', $mockTemplateParser);
        $view->_set('objectManager', $mockObjectManager);
        $view->setRenderingContext($mockRenderingContext);

        $mockTemplateCompiler = $this->getMock(TemplateCompiler::class);
        $view->_set('templateCompiler', $mockTemplateCompiler);
        GeneralUtility::setSingletonInstance(ObjectManager::class, $mockObjectManager);
        $mockContentObject = $this->getMock(ContentObjectRenderer::class);
        GeneralUtility::addInstance(ContentObjectRenderer::class, $mockContentObject);

        /** @var CacheManager|\PHPUnit_Framework_MockObject_MockObject $mockCacheManager */
        $mockCacheManager = $this->getMock(CacheManager::class, [], [], '', false);
        $mockCache = $this->getMock(PhpFrontend::class, [], [], '', false);
        $mockCacheManager->expects($this->any())->method('getCache')->will($this->returnValue($mockCache));
        GeneralUtility::setSingletonInstance(CacheManager::class, $mockCacheManager);

        $mockRequest->expects($this->any())->method('getFormat')->will($this->returnValue('html'));
        $view->setLayoutRootPaths(['some/Default/Directory']);
        $view->setTemplateRootPaths(['some/Default/Directory']);
        $view->setPartialRootPaths(['some/Default/Directory']);
        $view->expects($this->at(0))->method('testFileExistence')->with(PATH_site . 'some/Default/Directory/LayoutName.html')->willReturn(false);
        $view->expects($this->at(1))->method('testFileExistence')->with(PATH_site . 'some/Default/Directory/layoutName.html')->willReturn(true);
        $this->assertSame(PATH_site . 'some/Default/Directory/layoutName.html', $view->_call('getLayoutPathAndFilename', 'layoutName'));

        GeneralUtility::purgeInstances();
        GeneralUtility::resetSingletonInstances($singletonInstances);
    }
}
