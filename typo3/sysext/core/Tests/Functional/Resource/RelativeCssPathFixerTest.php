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

namespace TYPO3\CMS\Core\Tests\Functional\Resource;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Resource\RelativeCssPathFixer;
use TYPO3\CMS\Core\SystemResource\Publishing\SystemResourcePublisherInterface;
use TYPO3\CMS\Core\SystemResource\SystemResourceFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class RelativeCssPathFixerTest extends FunctionalTestCase
{
    public static function fixRelativeUrlPathsDataProvider(): array
    {
        return [
            '@import from URI with relative' => [
                '@import url(../tests/test.css); body { background: #ffffff; }',
                'https://example.com/css/',
                '@import url(\'https://example.com/tests/test.css\'); body { background: #ffffff; }',
            ],
            '@import from URI with no relative' => [
                '@import url(test.css); body { background: #ffffff; }',
                'https://example.com/css/',
                '@import url(\'https://example.com/css/test.css\'); body { background: #ffffff; }',
            ],
            '@import from package with no relative' => [
                '@import url(test.css); body { background: #ffffff; }',
                'EXT:backend/Resources/Public/Css/',
                '@import url(\'/typo3/sysext/backend/Resources/Public/Css/test.css\'); body { background: #ffffff; }',
            ],
            'url() from package with relative' => [
                '@font-face {
                    font-family: "Testfont"
                    src: url("../fonts/testfont.woff2") format("woff2"),
                         url("../fonts/testfont.woff") format("woff");
                    }',
                'EXT:backend/Resources/Public/Css/',
                '@font-face {
                    font-family: "Testfont"
                    src: url(\'/typo3/sysext/backend/Resources/Public/fonts/testfont.woff2\') format("woff2"),
                         url(\'/typo3/sysext/backend/Resources/Public/fonts/testfont.woff\') format("woff");
                    }',
            ],
            'url() from URI with no relative' => [
                '@font-face {
                    font-family: "Testfont"
                    src: url("../fonts/testfont.woff2") format("woff2"),
                         url("../fonts/testfont.woff") format("woff");
                    }',
                'https://example.com/css/',
                '@font-face {
                    font-family: "Testfont"
                    src: url(\'https://example.com/fonts/testfont.woff2\') format("woff2"),
                         url(\'https://example.com/fonts/testfont.woff\') format("woff");
                    }',
            ],
            'url() containing an id only' => [
                'clip-path: url(#example-clip-path)',
                'https://example.com/css/',
                'clip-path: url(#example-clip-path)',
            ],
        ];
    }

    #[DataProvider('fixRelativeUrlPathsDataProvider')]
    #[Test]
    public function fixRelativeUrlPaths(string $css, string $newDir, string $expected): void
    {
        $subject = new RelativeCssPathFixer($this->get(SystemResourceFactory::class), $this->get(SystemResourcePublisherInterface::class));
        $normalizedParams = $this->createMock(NormalizedParams::class);
        $normalizedParams->method('getSitePath')->willReturn('/');
        $request = (new ServerRequest('https://www.example.com/'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE)
            ->withAttribute('normalizedParams', $normalizedParams);
        $fixedCssPath = $subject->fixRelativeUrlPaths($css, $newDir, $request);
        self::assertSame($expected, $fixedCssPath);
    }
}
