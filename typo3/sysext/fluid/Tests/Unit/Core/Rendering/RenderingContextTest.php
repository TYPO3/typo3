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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContext;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;
use TYPO3Fluid\Fluid\Core\Variables\StandardVariableProvider;
use TYPO3Fluid\Fluid\Core\ViewHelper\ViewHelperVariableContainer;

final class RenderingContextTest extends UnitTestCase
{
    #[Test]
    public function templateVariableContainerCanBeReadCorrectly(): void
    {
        $templateVariableContainer = $this->createMock(StandardVariableProvider::class);
        $subject = $this->getMockBuilder(RenderingContext::class)->onlyMethods([])->disableOriginalConstructor()->getMock();
        $subject->setVariableProvider($templateVariableContainer);
        self::assertSame($subject->getVariableProvider(), $templateVariableContainer, 'Template Variable Container could not be read out again.');
    }

    #[Test]
    public function viewHelperVariableContainerCanBeReadCorrectly(): void
    {
        $viewHelperVariableContainer = $this->createMock(ViewHelperVariableContainer::class);
        $subject = $this->getMockBuilder(RenderingContext::class)->onlyMethods([])->disableOriginalConstructor()->getMock();
        $subject->setViewHelperVariableContainer($viewHelperVariableContainer);
        self::assertSame($viewHelperVariableContainer, $subject->getViewHelperVariableContainer());
    }

    public static function setControllerActionProcessesInputCorrectlyDataProvider(): array
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

    #[DataProvider('setControllerActionProcessesInputCorrectlyDataProvider')]
    #[Test]
    public function setControllerActionProcessesInputCorrectly(string $input, string $expected): void
    {
        $subject = $this->getMockBuilder(RenderingContext::class)->onlyMethods([])->disableOriginalConstructor()->getMock();
        $subject->setControllerAction($input);
        self::assertSame($expected, $subject->getControllerAction());
    }
}
