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

final class IconViewHelperTest extends FunctionalTestCase
{
    protected bool $initializeDatabase = false;

    #[Test]
    #[DataProvider('iconRenderingDataProvider')]
    public function renderIconViewHelperWithVariousConfigurations(string $template, array $expectedStrings): void
    {
        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource($template);
        $result = (new TemplateView($context))->render();
        $fileMtimeActions = filemtime(__DIR__ . '/../../../Resources/Public/Icons/T3Icons/sprites/actions.svg');
        foreach ($expectedStrings as $expectedString) {
            self::assertStringContainsString(
                str_replace(
                    'actions.svg#',
                    'actions.svg?' . $fileMtimeActions . '#',
                    $expectedString,
                ),
                $result,
            );
        }
    }

    public static function iconRenderingDataProvider(): array
    {
        return [
            'default size and state' => [
                '<core:icon identifier="actions-search" size="small" state="default" />',
                [
                    '<span class="t3js-icon icon icon-size-small icon-state-default icon-actions-search" data-identifier="actions-search" aria-hidden="true">',
                    '<svg class="icon-color"><use xlink:href="/typo3/sysext/core/Resources/Public/Icons/T3Icons/sprites/actions.svg#actions-search" /></svg>',
                ],
            ],
            'given size' => [
                '<core:icon identifier="actions-search" size="large" state="default" />',
                [
                    '<span class="t3js-icon icon icon-size-large icon-state-default icon-actions-search" data-identifier="actions-search" aria-hidden="true">',
                    '<svg class="icon-color"><use xlink:href="/typo3/sysext/core/Resources/Public/Icons/T3Icons/sprites/actions.svg#actions-search" /></svg>',
                ],
            ],
            'given state' => [
                '<core:icon identifier="actions-search" size="small" state="disabled" />',
                [
                    '<span class="t3js-icon icon icon-size-small icon-state-disabled icon-actions-search" data-identifier="actions-search" aria-hidden="true">',
                    '<svg class="icon-color"><use xlink:href="/typo3/sysext/core/Resources/Public/Icons/T3Icons/sprites/actions.svg#actions-search" /></svg>',
                ],
            ],
            'given overlay' => [
                '<core:icon identifier="actions-search" size="large" state="default" overlay="actions-plus" />',
                [
                    '<span class="t3js-icon icon icon-size-large icon-state-default icon-actions-search" data-identifier="actions-search" aria-hidden="true">',
                    '<svg class="icon-color"><use xlink:href="/typo3/sysext/core/Resources/Public/Icons/T3Icons/sprites/actions.svg#actions-search" /></svg>',
                    '<span class="icon-overlay icon-actions-plus"><svg class="icon-color"><use xlink:href="/typo3/sysext/core/Resources/Public/Icons/T3Icons/sprites/actions.svg#actions-plus" /></svg></span>',
                ],
            ],
            'title is passed' => [
                '<core:icon identifier="actions-search" size="large" state="default" title="myTitle" />',
                [
                    '<span title="myTitle" class="t3js-icon icon icon-size-large icon-state-default icon-actions-search" data-identifier="actions-search" aria-hidden="true">',
                    '<svg class="icon-color"><use xlink:href="/typo3/sysext/core/Resources/Public/Icons/T3Icons/sprites/actions.svg#actions-search" /></svg>',
                ],
            ],
        ];
    }
}
