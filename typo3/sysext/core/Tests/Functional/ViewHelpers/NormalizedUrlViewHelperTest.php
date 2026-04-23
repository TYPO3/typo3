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

namespace TYPO3\CMS\Core\Tests\Functional\ViewHelpers;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Fluid\Fluid\View\TemplateView;

final class NormalizedUrlViewHelperTest extends FunctionalTestCase
{
    protected bool $initializeDatabase = false;

    public static function renderCorrectlyDataProvider(): \Generator
    {
        yield 'URI: prefixed absolute URL' => [
            'pathOrUrl' => 'URI:https://example.com/logo.png',
            'expected' => 'https://example.com/logo.png',
        ];
        yield 'bare absolute URL' => [
            'pathOrUrl' => 'https://example.com/logo.png',
            'expected' => 'https://example.com/logo.png',
        ];
        yield 'EXT path to public resource' => [
            'pathOrUrl' => 'EXT:core/Resources/Public/Images/typo3_orange.svg',
            'expected' => 'typo3_orange.svg',
        ];
    }

    #[DataProvider('renderCorrectlyDataProvider')]
    #[Test]
    public function renderCorrectly(string $pathOrUrl, string $expected): void
    {
        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource('<core:normalizedUrl pathOrUrl="' . $pathOrUrl . '" />');
        self::assertStringContainsString($expected, (new TemplateView($context))->render());
    }
}
