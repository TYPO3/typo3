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

namespace TYPO3\CMS\Fluid\Tests\Functional\ViewHelpers\Asset;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\DirectiveHashCollection;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\HashValue;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Fluid\Fluid\View\TemplateView;

final class StyleAttrViewHelperTest extends FunctionalTestCase
{
    protected bool $initializeDatabase = false;

    public static function renderReturnsStyleAndCollectsHashDataProvider(): iterable
    {
        yield 'regular' => [
            'template' => '<div style="{f:asset.styleAttr(value: \'color: green\')}">text</div>',
            'expectedResult' => '<div style="color: green">text</div>',
            'expectedHash' => HashValue::hash('color: green')->export(),
        ];
        yield 'regular csp enabled' => [
            'template' => '<div style="{f:asset.styleAttr(value: \'color: green\', csp: true)}">text</div>',
            'expectedResult' => '<div style="color: green">text</div>',
            'expectedHash' => HashValue::hash('color: green')->export(),
        ];
        yield 'regular csp disabled' => [
            'template' => '<div style="{f:asset.styleAttr(value: \'color: green\', csp: false)}">text</div>',
            'expectedResult' => '<div style="color: green">text</div>',
            'expectedHash' => null,
        ];
        yield 'unexpected markup' => [
            'template' => '<div style="{f:asset.styleAttr(value: \'<bad value=&quot;&quot;>\')}">text</div>',
            'expectedResult' => '<div style="&lt;bad value=&amp;quot;&amp;quot;&gt;">text</div>',
            'expectedHash' => HashValue::hash('<bad value=&quot;&quot;>')->export(),
        ];
    }

    #[Test]
    #[DataProvider('renderReturnsStyleAndCollectsHashDataProvider')]
    public function renderReturnsStyleAndCollectsHash(string $template, string $expectedResult, ?string $expectedHash): void
    {
        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource($template);

        $result = new TemplateView($context)->render();
        $collection = $this->get(DirectiveHashCollection::class);
        $hash = $collection->jsonSerialize()['inline']['style-src-attr'][0] ?? null;

        self::assertSame($expectedResult, $result);
        self::assertSame($expectedHash, $hash);
    }
}
