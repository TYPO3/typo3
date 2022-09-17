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

namespace TYPO3\CMS\Fluid\Tests\Unit\Core\Rendering;

use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContext;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;
use TYPO3Fluid\Fluid\Core\Variables\StandardVariableProvider;
use TYPO3Fluid\Fluid\Core\ViewHelper\ViewHelperVariableContainer;

/**
 * Test case
 */
class RenderingContextTest extends UnitTestCase
{
    /**
     * Parsing state
     *
     * @var RenderingContext
     */
    protected $renderingContext;

    protected function setUp(): void
    {
        parent::setUp();
        $this->renderingContext = $this->getMockBuilder(RenderingContext::class)
            ->addMethods(['dummy'])
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @test
     */
    public function templateVariableContainerCanBeReadCorrectly(): void
    {
        $templateVariableContainer = $this->createMock(StandardVariableProvider::class);
        $this->renderingContext->setVariableProvider($templateVariableContainer);
        self::assertSame($this->renderingContext->getVariableProvider(), $templateVariableContainer, 'Template Variable Container could not be read out again.');
    }

    /**
     * @test
     */
    public function viewHelperVariableContainerCanBeReadCorrectly(): void
    {
        $viewHelperVariableContainer = $this->createMock(ViewHelperVariableContainer::class);
        $this->renderingContext->setViewHelperVariableContainer($viewHelperVariableContainer);
        self::assertSame($viewHelperVariableContainer, $this->renderingContext->getViewHelperVariableContainer());
    }

    /**
     * @test
     * @dataProvider getControllerActionTestValues
     * @param string $input
     * @param string $expected
     */
    public function setControllerActionProcessesInputCorrectly($input, $expected): void
    {
        $subject = $this->getMockBuilder(RenderingContext::class)
            ->addMethods(['dummy'])
            ->disableOriginalConstructor()
            ->getMock();
        $request = new Request();
        $subject->setRequest($request);
        $subject->setControllerAction($input);
        self::assertSame(lcfirst($expected), $subject->getControllerAction());
    }

    /**
     * @return array
     */
    public function getControllerActionTestValues(): array
    {
        return [
            ['default', 'default'],
            ['default.html', 'default'],
            ['default.sub.html', 'default'],
            ['Sub/Default', 'Sub/Default'],
            ['Sub/Default.html', 'Sub/Default'],
            ['Sub/Default.sub.html', 'Sub/Default'],
        ];
    }
}
