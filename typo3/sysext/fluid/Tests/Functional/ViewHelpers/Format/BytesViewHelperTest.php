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

class BytesViewHelperTest extends FunctionalTestCase
{
    public function renderConvertsAValueDataProvider(): array
    {
        return [
            // invalid values
            [
                '<f:format.bytes value="invalid" units="B,KB,MB,GB,TB,PB,EB,ZB,YB" />',
                '0 B'
            ],
            [
                '<f:format.bytes value="" decimals="2" units="B,KB,MB,GB,TB,PB,EB,ZB,YB" />',
                '0.00 B'
            ],
            [
                '<f:format.bytes value="{}" decimals="2" units="B,KB,MB,GB,TB,PB,EB,ZB,YB" />',
                '0.00 B'
            ],
            // valid values
            [
                '<f:format.bytes value="123" units="B,KB,MB,GB,TB,PB,EB,ZB,YB" />',
                '123 B'
            ],
            [
                '<f:format.bytes value="43008" decimals="1" units="B,KB,MB,GB,TB,PB,EB,ZB,YB" />',
                '42.0 KB'
            ],
            [
                '<f:format.bytes value="1024" decimals="1" units="B,KB,MB,GB,TB,PB,EB,ZB,YB" />',
                '1.0 KB'
            ],
            [
                '<f:format.bytes value="1023" decimals="2" units="B,KB,MB,GB,TB,PB,EB,ZB,YB" />',
                '1,023.00 B'
            ],
            [
                '<f:format.bytes value="1073741823" decimals="1" thousandsSeparator="." units="B,KB,MB,GB,TB,PB,EB,ZB,YB" />',
                '1.024.0 MB'
            ],
            [
                '<f:format.bytes value="{1024 ^ 5}" decimals="1" units="B,KB,MB,GB,TB,PB,EB,ZB,YB" />',
                '1.0 PB'
            ],
            [
                '<f:format.bytes value="{1024 ^ 8}" decimals="1" units="B,KB,MB,GB,TB,PB,EB,ZB,YB" />',
                '1.0 YB'
            ],
            [
                '<f:format.bytes units="B,KB,MB,GB,TB,PB,EB,ZB,YB">12345</f:format.bytes>',
                '12 KB'
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
