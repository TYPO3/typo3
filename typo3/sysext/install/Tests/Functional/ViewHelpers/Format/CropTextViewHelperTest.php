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

namespace TYPO3\CMS\Install\Tests\Functional\ViewHelpers\Format;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Fluid\Fluid\View\TemplateView;

final class CropTextViewHelperTest extends FunctionalTestCase
{
    protected bool $initializeDatabase = false;

    public static function renderCorrectlyDataProvider(): array
    {
        return [
            'not cropped' => [
                'input' => 'i am not cropped',
                'maxCharacters' => 20,
                'expected' => 'i am not cropped',
            ],
            'crop 20' => [
                'input' => 'i am too long so i get cropped somewhere',
                'maxCharacters' => 20,
                'expected' => 'i am too long so i g…',
            ],
            'crop 30' => [
                'input' => 'i am too long so i get cropped somewhere',
                'maxCharacters' => 30,
                'expected' => 'i am too long so i get cropped…',
            ],
        ];
    }

    #[DataProvider('renderCorrectlyDataProvider')]
    #[Test]
    public function renderCorrectly(string $input, int $maxCharacters, string $expected): void
    {
        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getViewHelperResolver()->addNamespace('install', 'TYPO3\\CMS\\Install\\ViewHelpers');
        $context->getTemplatePaths()->setTemplateSource('<install:format.cropText maxCharacters="' . $maxCharacters . '">' . $input . '</install:format.cropText>');
        self::assertSame($expected, (new TemplateView($context))->render());
    }
}
