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

namespace TYPO3\CMS\Fluid\Tests\Unit\View;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Fluid\View\FluidViewAdapter;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;
use TYPO3Fluid\Fluid\View\TemplateAwareViewInterface as FluidStandaloneTemplateAwareViewInterface;
use TYPO3Fluid\Fluid\View\ViewInterface as FluidStandaloneViewInterface;

final class FluidViewAdapterTest extends UnitTestCase
{
    public static function renderCastsToStringDataProvider(): iterable
    {
        return [
            [null, ''],
            [123, '123'],
            [123.456, '123.456'],
            [
                new class () {
                    public function __toString(): string
                    {
                        return 'Stringable';
                    }
                },
                'Stringable',
            ],
        ];
    }

    #[Test]
    #[DataProvider('renderCastsToStringDataProvider')]
    public function renderCastsToString(mixed $viewReturnValue, string $expectedResult): void
    {
        $view = new class ($viewReturnValue) implements FluidStandaloneViewInterface, FluidStandaloneTemplateAwareViewInterface {
            public function __construct(private mixed $viewReturnValue) {}
            public function render(string $templateFileName = '')
            {
                return $this->viewReturnValue;
            }
            public function assign($name, $value)
            {
                return $this;
            }
            public function assignMultiple($variables)
            {
                return $this;
            }
            public function renderPartial($partialName, $sectionName, array $variables, $ignoreUnknown = false)
            {
                return '';
            }
            public function renderSection($sectionName, array $variables = [], $ignoreUnknown = false)
            {
                return '';
            }
        };

        $subject = new FluidViewAdapter($view);
        self::assertSame($expectedResult, $subject->render());
    }

    public static function renderThrowsExceptionDataProvider(): iterable
    {
        return [
            [[]],
            [[1, 2, 3]],
            [new \stdClass()],
            [new class () {}],
        ];
    }

    #[Test]
    #[DataProvider('renderThrowsExceptionDataProvider')]
    public function renderThrowsException(mixed $viewReturnValue): void
    {
        $view = new class ($viewReturnValue) implements FluidStandaloneViewInterface, FluidStandaloneTemplateAwareViewInterface {
            public function __construct(private mixed $viewReturnValue) {}
            public function render(string $templateFileName = '')
            {
                return $this->viewReturnValue;
            }
            public function assign($name, $value)
            {
                return $this;
            }
            public function assignMultiple($variables)
            {
                return $this;
            }
            public function renderPartial($partialName, $sectionName, array $variables, $ignoreUnknown = false)
            {
                return '';
            }
            public function renderSection($sectionName, array $variables = [], $ignoreUnknown = false)
            {
                return '';
            }
        };

        $this->expectException(\RuntimeException::class);
        $subject = new FluidViewAdapter($view);
        $subject->render();
    }
}
