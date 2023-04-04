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

namespace TYPO3\CMS\Redirects\Tests\Unit\ViewHelpers;

use TYPO3\CMS\Fluid\Core\Rendering\RenderingContext;
use TYPO3\CMS\Redirects\ViewHelpers\TargetPageIdViewHelper;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class TargetPageIdViewHelperTest extends UnitTestCase
{
    /**
     * @test
     */
    public function nonTypo3TargetInRenderStaticReturnsEmptyString(): void
    {
        $renderingContext = new class () extends RenderingContext {
            public function __construct()
            {
            }
        };
        $args = ['target' => 'nope'];
        self::assertSame('', TargetPageIdViewHelper::renderStatic($args, static fn () => '', $renderingContext));
    }

    /**
     * @test
     */
    public function emptyTargetInRenderStaticReturnsEmptyString(): void
    {
        $renderingContext = new class () extends RenderingContext {
            public function __construct()
            {
            }
        };
        $args = [];
        self::assertSame('', TargetPageIdViewHelper::renderStatic($args, static fn () => '', $renderingContext));
    }
}
