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

use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class CropViewHelperTest extends FunctionalTestCase
{
    /**
     * @var bool Speed up this test case, it needs no database
     */
    protected $initializeDatabase = false;

    public function renderConvertsAValueDataProvider(): array
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
        ];
    }

    /**
     * @test
     * @dataProvider renderConvertsAValueDataProvider
     */
    public function renderConvertsAValue(string $src, string $expected): void
    {
        $view = new StandaloneView();
        $view->setTemplateSource($src);
        self::assertSame($expected, $view->render());
    }
}
