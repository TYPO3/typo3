<?php
namespace TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers;

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

use Prophecy\Argument;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Fluid\ViewHelpers\CObjectViewHelper;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\TestingFramework\Fluid\Unit\ViewHelpers\ViewHelperBaseTestcase;

/**
 * Class CObjectViewHelperTest
 */
class CObjectViewHelperTest extends ViewHelperBaseTestcase
{
    /**
     * @var CObjectViewHelper
     */
    protected $viewHelper;

    /**
     * @var ConfigurationManagerInterface
     */
    protected $configurationManager;

    /**
     * @var ContentObjectRenderer
     */
    protected $contentObjectRenderer;

    /**
     * Set up the fixture
     */
    protected function setUp()
    {
        parent::setUp();
        $this->viewHelper = new CObjectViewHelper();
        $this->injectDependenciesIntoViewHelper($this->viewHelper);

        $this->configurationManager = $this->prophesize(ConfigurationManagerInterface::class);
        $this->contentObjectRenderer = $this->prophesize(ContentObjectRenderer::class);
    }

    /**
     * @test
     */
    public function viewHelperAcceptsDataParameterAsInput()
    {
        $this->stubBaseDependencies();

        $this->setArgumentsUnderTest(
            $this->viewHelper,
            [
                'data' => 'foo',
            ]
        );
        $this->contentObjectRenderer->start(['foo'], '')->willReturn();
        $actualResult = $this->viewHelper->initializeArgumentsAndRender();
        $this->assertSame('', $actualResult);
    }

    /**
     * @test
     */
    public function viewHelperAcceptsChildrenClosureAsInput()
    {
        $this->stubBaseDependencies();

        $this->viewHelper->setRenderChildrenClosure(
            function () {
                return 'foo';
            }
        );
        $this->setArgumentsUnderTest(
            $this->viewHelper,
            []
        );
        $this->contentObjectRenderer->start(['foo'], '')->willReturn();
        $actualResult = $this->viewHelper->initializeArgumentsAndRender();
        $this->assertSame('', $actualResult);
    }

    /**
     * @test
     */
    public function renderThrowsExceptionIfTyposcriptObjectPathDoesNotExist()
    {
        $this->stubBaseDependencies();
        $this->contentObjectRenderer->start(Argument::cetera())->willReturn();

        $this->setArgumentsUnderTest(
            $this->viewHelper,
            [
                'data' => 'foo',
                'typoscriptObjectPath' => 'test.path',
            ]
        );
        $this->expectException(\TYPO3\CMS\Fluid\Core\ViewHelper\Exception::class);
        $this->expectExceptionCode(1253191023);
        $this->viewHelper->initializeArgumentsAndRender();
    }

    /**
     * @test
     */
    public function renderReturnsSimpleTyposcriptValue()
    {
        $this->setArgumentsUnderTest(
            $this->viewHelper,
            [
                'typoscriptObjectPath' => 'test',
                'data' => 'foo',
                'table' => 'table',
            ]
        );

        $subConfigArray = [
            'value' => 'Hello World',
            'wrap' => 'ab | cd',
        ];

        $configArray = [
            'test' => 'TEXT',
            'test.' => $subConfigArray,
        ];

        $this->configurationManager->getConfiguration(Argument::any())->willReturn($configArray);
        $this->viewHelper->injectConfigurationManager($this->configurationManager->reveal());

        $this->contentObjectRenderer->start(['foo'], 'table')->willReturn();
        $this->contentObjectRenderer->setCurrentVal('foo')->willReturn();
        $this->contentObjectRenderer->cObjGetSingle('TEXT', $subConfigArray)->willReturn('Hello World');
        $this->viewHelper->injectContentObjectRenderer($this->contentObjectRenderer->reveal());

        $actualResult = $this->viewHelper->initializeArgumentsAndRender();
        $expectedResult = 'Hello World';
        $this->assertSame($expectedResult, $actualResult);
    }

    /**
     * Stubs base dependencies
     */
    protected function stubBaseDependencies()
    {
        $this->configurationManager->getConfiguration(Argument::any())->willReturn([]);
        $this->viewHelper->injectConfigurationManager($this->configurationManager->reveal());

        $this->contentObjectRenderer->setCurrentVal(Argument::cetera())->willReturn();
        $this->contentObjectRenderer->cObjGetSingle(Argument::cetera())->willReturn('');
        $this->viewHelper->injectContentObjectRenderer($this->contentObjectRenderer->reveal());
    }
}
