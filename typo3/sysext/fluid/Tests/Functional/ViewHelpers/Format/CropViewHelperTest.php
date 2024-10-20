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

namespace TYPO3\CMS\Fluid\Tests\Functional\ViewHelpers\Format;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Fluid\Fluid\View\TemplateView;

final class CropViewHelperTest extends FunctionalTestCase
{
    protected bool $initializeDatabase = false;

    public static function renderConvertsAValueDataProvider(): array
    {
        return [
            'simple html crop' => [
                '<f:format.crop maxCharacters="10">Crop this content</f:format.crop>',
                'Crop this&hellip;',
            ],
            'simple html crop with html' => [
                '<f:format.crop maxCharacters="10"><p>Crop</p> this content</f:format.crop>',
                '<p>Crop</p> this&hellip;',
            ],
            'custom suffix' => [
                '<f:format.crop maxCharacters="10" append="custom suffix">Crop this content</f:format.crop>',
                'Crop thiscustom suffix',
            ],
            'disabled respectWordBoundaries' => [
                '<f:format.crop maxCharacters="7" respectWordBoundaries="false">Crop this content</f:format.crop>',
                'Crop th&hellip;',
            ],
            'do not respect html' => [
                '<f:format.crop maxCharacters="7" respectHtml="false" append="..."><p>Crop</p> this content</f:format.crop>',
                '<p>Crop...',
            ],
            'crop integer tag content' => [
                '<f:for each="{471147124713:\'4712\'}" as="i" iteration="iterator" key="k"><f:format.crop maxCharacters="4" append="custom suffix">{k}</f:format.crop></f:for>',
                '4711custom suffix',
            ],
        ];
    }

    #[DataProvider('renderConvertsAValueDataProvider')]
    #[Test]
    public function renderConvertsAValue(string $src, string $expected): void
    {
        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource($src);
        self::assertSame($expected, (new TemplateView($context))->render());
    }
}
