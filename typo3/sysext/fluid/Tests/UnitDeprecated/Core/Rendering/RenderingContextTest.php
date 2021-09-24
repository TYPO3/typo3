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

namespace TYPO3\CMS\Fluid\Tests\UnitDeprecated\Core\Rendering;

use TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContext;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

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
     * @deprecated since v11, will be removed with v12.
     */
    public function controllerContextCanBeReadCorrectly(): void
    {
        $controllerContext = $this->getMockBuilder(ControllerContext::class)
            ->disableOriginalConstructor()
            ->getMock();
        $controllerContext->expects(self::atLeastOnce())->method('getRequest')->willReturn($this->createMock(Request::class));
        $this->renderingContext->setControllerContext($controllerContext);
        self::assertSame($this->renderingContext->getControllerContext(), $controllerContext);
    }
}
