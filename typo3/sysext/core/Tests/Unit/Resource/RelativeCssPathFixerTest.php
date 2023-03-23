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

namespace TYPO3\CMS\Core\Tests\Unit\Resource;

use TYPO3\CMS\Core\Resource\RelativeCssPathFixer;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class RelativeCssPathFixerTest extends UnitTestCase
{
    public static function fixRelativeUrlPathsDataProvider(): array
    {
        return [
            '@import from fileadmin with relative' => [
                '@import url(../tests/test.css); body { background: #ffffff; }',
                '/fileadmin/css/',
                '@import url(\'/fileadmin/tests/test.css\'); body { background: #ffffff; }',
            ],
            '@import from fileadmin with no relative' => [
                '@import url(test.css); body { background: #ffffff; }',
                'fileadmin/css/',
                '@import url(\'fileadmin/css/test.css\'); body { background: #ffffff; }',
            ],
            '@import from sitepackage with no relative' => [
                '@import url(test.css); body { background: #ffffff; }',
                'typo3conf/ext/sitepackage/Resources/Public/Css/',
                '@import url(\'typo3conf/ext/sitepackage/Resources/Public/Css/test.css\'); body { background: #ffffff; }',
            ],
            'url() from sitepackage with relative' => [
                '@font-face {
                    font-family: "Testfont"
                    src: url("../fonts/testfont.woff2") format("woff2"),
                         url("../fonts/testfont.woff") format("woff");
                    }',
                '../../../typo3conf/ext/sitepackage/Resources/Public/Css/',
                '@font-face {
                    font-family: "Testfont"
                    src: url(\'../../../typo3conf/ext/sitepackage/Resources/Public/fonts/testfont.woff2\') format("woff2"),
                         url(\'../../../typo3conf/ext/sitepackage/Resources/Public/fonts/testfont.woff\') format("woff");
                    }',
            ],
            'url() from fileadmin with no relative' => [
                '@font-face {
                    font-family: "Testfont"
                    src: url("../fonts/testfont.woff2") format("woff2"),
                         url("../fonts/testfont.woff") format("woff");
                    }',
                'fileadmin/css/',
                '@font-face {
                    font-family: "Testfont"
                    src: url(\'fileadmin/fonts/testfont.woff2\') format("woff2"),
                         url(\'fileadmin/fonts/testfont.woff\') format("woff");
                    }',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider fixRelativeUrlPathsDataProvider
     */
    public function fixRelativeUrlPaths(string $css, string $newDir, string $expected): void
    {
        $subject = new RelativeCssPathFixer();
        $fixedCssPath = $subject->fixRelativeUrlPaths($css, $newDir);
        self::assertSame($expected, $fixedCssPath);
    }
}
