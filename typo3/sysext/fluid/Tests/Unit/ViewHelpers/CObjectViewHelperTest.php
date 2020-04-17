<?php

declare(strict_types=1);

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

namespace TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers;

use Prophecy\Argument;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Fluid\ViewHelpers\CObjectViewHelper;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\TestingFramework\Fluid\Unit\ViewHelpers\ViewHelperBaseTestcase;
use TYPO3Fluid\Fluid\Core\ViewHelper\Exception;

/**
 * Test case
 */
class CObjectViewHelperTest extends ViewHelperBaseTestcase
{
    /**
     * @var bool Reset singletons created by subject
     */
    protected $resetSingletonInstances = true;

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
    protected function setUp(): void
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
                'typoscriptObjectPath' => 'test',
                'data' => 'foo',
            ]
        );
        $configArray = [
            'test' => 'TEXT',
            'test.' => [],
        ];
        $this->configurationManager->getConfiguration(Argument::any())->willReturn($configArray);

        $this->contentObjectRenderer->start(['foo'], '')->willReturn();
        $this->viewHelper->initializeArgumentsAndRender();
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
            [
                'typoscriptObjectPath' => 'test',
            ]
        );
        $configArray = [
            'test' => 'TEXT',
            'test.' => [],
        ];
        $this->configurationManager->getConfiguration(Argument::any())->willReturn($configArray);

        $this->contentObjectRenderer->start(['foo'], '')->willReturn();
        $this->viewHelper->initializeArgumentsAndRender();
    }

    public function renderThrowsExceptionIfTypoScriptObjectPathDoesNotExistDataProvider(): array
    {
        return [
            'Single path' => [
                'test',
                1540246570
            ],
            'Multi path' => [
                'test.path',
                1253191023
            ],
        ];
    }

    /**
     * @test
     * @dataProvider renderThrowsExceptionIfTypoScriptObjectPathDoesNotExistDataProvider
     */
    public function renderThrowsExceptionIfTypoScriptObjectPathDoesNotExist(string $objectPath, int $exceptionCode)
    {
        $this->stubBaseDependencies();
        $this->contentObjectRenderer->start(Argument::cetera())->willReturn();

        $this->setArgumentsUnderTest(
            $this->viewHelper,
            [
                'data' => 'foo',
                'typoscriptObjectPath' => $objectPath,
            ]
        );

        $this->expectException(Exception::class);
        $this->expectExceptionCode($exceptionCode);
        $this->viewHelper->initializeArgumentsAndRender();
    }

    public function renderReturnsSimpleTypoScriptValueDataProvider(): array
    {
        $subConfigArray = [
            'value' => 'Hello World',
            'wrap' => 'ab | cd',
        ];
        return [
            'Single path' => [
                'test',
                [
                    'test' => 'TEXT',
                    'test.' => $subConfigArray,
                ],
                $subConfigArray
            ],
            'Single path no config' => [
                'test',
                [
                    'test' => 'TEXT',
                ],
                []
            ],
            'Multi path' => [
                'plugin.test',
                [
                    'plugin.' => [
                        'test' => 'TEXT',
                        'test.' => $subConfigArray,
                    ]
                ],
                $subConfigArray
            ],
            'Multi path no config' => [
                'plugin.test',
                [
                    'plugin.' => [
                        'test' => 'TEXT',
                    ]
                ],
                []
            ],
        ];
    }

    /**
     * @test
     * @dataProvider renderReturnsSimpleTypoScriptValueDataProvider
     * @param string $objectPath
     * @param array $configArray
     * @param array $subConfigArray
     */
    public function renderReturnsSimpleTypoScriptValue(string $objectPath, array $configArray, array $subConfigArray)
    {
        $this->stubBaseDependencies();
        $this->setArgumentsUnderTest(
            $this->viewHelper,
            [
                'typoscriptObjectPath' => $objectPath,
                'data' => 'foo',
                'table' => 'table',
            ]
        );

        $this->configurationManager->getConfiguration(Argument::any())->willReturn($configArray);

        $this->contentObjectRenderer->start(['foo'], 'table')->willReturn();
        $this->contentObjectRenderer->setCurrentVal('foo')->willReturn();
        $this->contentObjectRenderer->cObjGetSingle('TEXT', $subConfigArray, Argument::any())->willReturn('Hello World');

        $objectManager = $this->prophesize(ObjectManager::class);
        $objectManager->get(ConfigurationManagerInterface::class)->willReturn($this->configurationManager->reveal());
        GeneralUtility::addInstance(ContentObjectRenderer::class, $this->contentObjectRenderer->reveal());
        GeneralUtility::setSingletonInstance(ObjectManager::class, $objectManager->reveal());

        $actualResult = $this->viewHelper->initializeArgumentsAndRender();
        $expectedResult = 'Hello World';
        self::assertSame($expectedResult, $actualResult);
    }

    /**
     * Stubs base dependencies
     */
    protected function stubBaseDependencies()
    {
        $this->configurationManager->getConfiguration(Argument::any())->willReturn([]);
        $this->contentObjectRenderer->setCurrentVal(Argument::cetera())->willReturn();
        $this->contentObjectRenderer->cObjGetSingle(Argument::cetera())->willReturn('');
        $objectManager = $this->prophesize(ObjectManager::class);
        $objectManager->get(ConfigurationManagerInterface::class)->willReturn($this->configurationManager->reveal());
        GeneralUtility::setSingletonInstance(ObjectManager::class, $objectManager->reveal());
        $GLOBALS['TSFE'] = $this->getAccessibleMock(TypoScriptFrontendController::class, ['initCaches'], [], '', false);
    }
}
