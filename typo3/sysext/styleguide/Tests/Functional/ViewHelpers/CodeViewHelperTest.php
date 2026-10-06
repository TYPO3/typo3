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

namespace TYPO3\CMS\Styleguide\Tests\Functional\ViewHelpers;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Fluid\Fluid\View\TemplateView;

final class CodeViewHelperTest extends FunctionalTestCase
{
    protected bool $initializeDatabase = false;

    protected array $coreExtensionsToLoad = ['styleguide'];

    public static function renderAddsCopyToClipboardButtonWithCodeDataProvider(): array
    {
        return [
            'encoded code' => [
                '<sg:code language="html" decodeEntities="true">&lt;b data-id=&quot;###UNIQUEID###&quot;&gt;bold&lt;/b&gt;</sg:code>',
            ],
            'plain code' => [
                '<sg:code language="html"><b data-id="###UNIQUEID###">bold</b></sg:code>',
            ],
        ];
    }

    #[DataProvider('renderAddsCopyToClipboardButtonWithCodeDataProvider')]
    #[Test]
    public function renderAddsCopyToClipboardButtonWithCode(string $template): void
    {
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->create('default');
        $request = new ServerRequest('https://www.example.com/')
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $context = $this->get(RenderingContextFactory::class)->create([], $request);
        $context->getViewHelperResolver()->addNamespace('sg', 'TYPO3\\CMS\\Styleguide\\ViewHelpers');
        $context->getTemplatePaths()->setTemplateSource($template);
        $result = new TemplateView($context)->render();

        self::assertMatchesRegularExpression(
            '#<typo3-copy-to-clipboard class="btn btn-sm btn-default styleguide-example-code-copy" title="Copy code to clipboard" text="&lt;b data-id=&quot;code[0-9a-f]+&quot;&gt;bold&lt;/b&gt;">#',
            $result
        );
    }
}
