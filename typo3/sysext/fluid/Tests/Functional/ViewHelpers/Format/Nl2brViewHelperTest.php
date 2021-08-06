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

class Nl2brViewHelperTest extends FunctionalTestCase
{
    /**
     * @var bool Speed up this test case, it needs no database
     */
    protected $initializeDatabase = false;

    public function renderDataProvider(): array
    {
        return [
            'viewHelperDoesNotModifyTextWithoutLineBreaks' => [
                '<f:format.nl2br><p class="bodytext">Some Text without line breaks</p></f:format.nl2br>',
                '<p class="bodytext">Some Text without line breaks</p>'
            ],
            'viewHelperConvertsLineBreaksToBRTags' => [
                '<f:format.nl2br>' . 'Line 1' . chr(10) . 'Line 2' . '</f:format.nl2br>',
                'Line 1<br />' . chr(10) . 'Line 2'
            ],
            'viewHelperConvertsWindowsLineBreaksToBRTags' => [
                '<f:format.nl2br>' . 'Line 1' . chr(13) . chr(10) . 'Line 2' . '</f:format.nl2br>',
                'Line 1<br />' . chr(13) . chr(10) . 'Line 2'
            ]
        ];
    }

    /**
     * @test
     * @dataProvider renderDataProvider
     */
    public function render(string $template, string $expected): void
    {
        $view = new StandaloneView();
        $view->setTemplateSource($template);
        self::assertEquals($expected, $view->render());
    }
}
