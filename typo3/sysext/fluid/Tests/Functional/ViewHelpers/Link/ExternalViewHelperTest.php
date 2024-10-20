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

namespace TYPO3\CMS\Fluid\Tests\Functional\ViewHelpers\Link;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Fluid\Fluid\View\TemplateView;

final class ExternalViewHelperTest extends FunctionalTestCase
{
    protected bool $initializeDatabase = false;

    public static function renderDataProvider(): array
    {
        return [
            'renderAddsHttpsPrefixIfSpecifiedUriDoesNotContainScheme' => [
                '<f:link.external uri="www.some-domain.tld">some content</f:link.external>',
                '<a href="https://www.some-domain.tld">some content</a>',
            ],
            'renderAddsSpecifiedSchemeIfUriDoesNotContainScheme' => [
                '<f:link.external uri="www.some-domain.tld" defaultScheme="ftp">some content</f:link.external>',
                '<a href="ftp://www.some-domain.tld">some content</a>',
            ],
            'renderDoesNotAddEmptyScheme' => [
                '<f:link.external uri="www.some-domain.tld" defaultScheme="">some content</f:link.external>',
                '<a href="www.some-domain.tld">some content</a>',
            ],
            'renderCanUseIntegerAsTagContent' => [
                '<f:for each="{4711:\'4712\'}" as="i" iteration="iterator" key="k"><f:link.external uri="www.some-domain.tld" defaultScheme="">{k}</f:link.external></f:for>',
                '<a href="www.some-domain.tld">4711</a>',
            ],
        ];
    }

    #[DataProvider('renderDataProvider')]
    #[Test]
    public function render(string $template, string $expected): void
    {
        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource($template);
        self::assertSame($expected, (new TemplateView($context))->render());
    }
}
