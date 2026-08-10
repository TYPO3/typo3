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

namespace TYPO3\CMS\Fluid\Tests\Functional\ViewHelpers\Uri;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\TypoScript\AST\Node\RootNode;
use TYPO3\CMS\Core\TypoScript\FrontendTypoScript;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Extbase\Mvc\ExtbaseRequestParameters;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Fluid\Fluid\View\TemplateView;

final class ResourceViewHelperTest extends FunctionalTestCase
{
    protected bool $initializeDatabase = false;

    #[Test]
    public function renderingFailsWithNonExtSyntaxWithoutExtensionNameWithPsr7Request()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1639672666);
        $context = $this->get(RenderingContextFactory::class)->create([], new ServerRequest());
        $context->getTemplatePaths()->setTemplateSource('<f:uri.resource path="Icons/Extension.svg" />');
        new TemplateView($context)->render();
    }

    #[Test]
    public function renderingFailsWhenExtensionNameNotSetInExtbaseRequest(): void
    {
        $serverRequest = new ServerRequest()->withAttribute('extbase', new ExtbaseRequestParameters());
        $context = $this->get(RenderingContextFactory::class)->create([], new Request($serverRequest));
        $context->getTemplatePaths()->setTemplateSource('<f:uri.resource path="Icons/Extension.svg" />');
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1640097205);
        new TemplateView($context)->render();
    }

    public static function renderWithExtbaseRequestDataProvider(): \Generator
    {
        yield 'render returns URI using extensionName from Extbase Request' => [
            '<f:uri.resource path="Icons/Extension.svg" />',
            '{{EXT:core/Resources/Public/Icons/Extension.svg}}',
        ];
        yield 'render gracefully trims leading slashes from path' => [
            '<f:uri.resource path="/Icons/Extension.svg" />',
            '{{EXT:core/Resources/Public/Icons/Extension.svg}}',
        ];
        yield 'render returns URI using UpperCamelCase extensionName' => [
            '<f:uri.resource path="Icons/Extension.svg" extensionName="Core" />',
            '{{EXT:core/Resources/Public/Icons/Extension.svg}}',
        ];
        yield 'render returns URI using extension key as extensionName' => [
            '<f:uri.resource path="Icons/Extension.svg" extensionName="core" />',
            '{{EXT:core/Resources/Public/Icons/Extension.svg}}',
        ];
        yield 'render returns URI using EXT: syntax' => [
            '<f:uri.resource path="EXT:core/Resources/Public/Icons/Extension.svg" />',
            '{{EXT:core/Resources/Public/Icons/Extension.svg}}',
        ];
    }

    #[DataProvider('renderWithExtbaseRequestDataProvider')]
    #[Test]
    public function renderWithExtbaseRequest(string $template, string $expected): void
    {
        $extbaseRequestParameters = new ExtbaseRequestParameters();
        $extbaseRequestParameters->setControllerExtensionName('Core');
        $normalizedParams = self::createStub(NormalizedParams::class);
        $normalizedParams->method('getSitePath')->willReturn('/');
        $serverRequest = new ServerRequest()->withAttribute('extbase', $extbaseRequestParameters)
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withAttribute('normalizedParams', $normalizedParams);
        $extbaseRequest = (new Request($serverRequest));
        $context = $this->get(RenderingContextFactory::class)->create([], $extbaseRequest);
        $context->getTemplatePaths()->setTemplateSource($template);
        self::assertEquals($this->resolveResourcePlaceholders($expected), new TemplateView($context)->render());
    }

    public static function renderWithAndWithoutRequestDataProvider(): \Generator
    {
        yield 'render gracefully trims leading slashes from path' => [
            '<f:uri.resource path="/Icons/Extension.svg" extensionName="Core" />',
            '{{EXT-RELATIVE:core/Resources/Public/Icons/Extension.svg}}',
        ];
        yield 'render returns URI using UpperCamelCase extensionName' => [
            '<f:uri.resource path="Icons/Extension.svg" extensionName="Core" />',
            '{{EXT-RELATIVE:core/Resources/Public/Icons/Extension.svg}}',
        ];
        yield 'render returns URI using extension key as extensionName' => [
            '<f:uri.resource path="Icons/Extension.svg" extensionName="core" />',
            '{{EXT-RELATIVE:core/Resources/Public/Icons/Extension.svg}}',
        ];
        yield 'render returns URI using EXT: syntax' => [
            '<f:uri.resource path="EXT:core/Resources/Public/Icons/Extension.svg" />',
            '{{EXT-RELATIVE:core/Resources/Public/Icons/Extension.svg}}',
        ];
    }

    #[DataProvider('renderWithAndWithoutRequestDataProvider')]
    #[Test]
    public function renderWithBackendRequest(string $template, string $expected): void
    {
        $urlPrefix = '/prefix/';
        $normalizedParams = self::createStub(NormalizedParams::class);
        $normalizedParams->method('getSitePath')->willReturn($urlPrefix);
        $serverRequest = new ServerRequest()
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withAttribute('normalizedParams', $normalizedParams);
        $context = $this->get(RenderingContextFactory::class)->create([], $serverRequest);
        $context->getTemplatePaths()->setTemplateSource($template);
        self::assertEquals($urlPrefix . $this->resolveResourcePlaceholders($expected), new TemplateView($context)->render());
    }

    #[DataProvider('renderWithAndWithoutRequestDataProvider')]
    #[Test]
    public function renderWithFrontendRequest(string $template, string $expected): void
    {
        $urlPrefix = '/absRefPrefix/';
        $frontendTypoScript = new FrontendTypoScript(new RootNode(), [], [], []);
        $frontendTypoScript->setConfigArray([
            'absRefPrefix' => $urlPrefix,
        ]);
        $normalizedParams = self::createStub(NormalizedParams::class);
        $normalizedParams->method('getSitePath')->willReturn('/');
        $serverRequest = new ServerRequest()
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE)
            ->withAttribute('normalizedParams', $normalizedParams)
            ->withAttribute('frontend.typoscript', $frontendTypoScript);
        $context = $this->get(RenderingContextFactory::class)->create([], $serverRequest);
        $context->getTemplatePaths()->setTemplateSource($template);
        self::assertEquals($urlPrefix . $this->resolveResourcePlaceholders($expected), new TemplateView($context)->render());
    }

    #[DataProvider('renderWithAndWithoutRequestDataProvider')]
    #[Test]
    public function renderWithoutRequest(string $template, string $expected): void
    {
        // If no request is given, the default prefix "/" kicks in
        $urlPrefix = '/';
        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource($template);
        self::assertEquals($urlPrefix . $this->resolveResourcePlaceholders($expected), new TemplateView($context)->render());
    }

    #[Test]
    public function renderWithGivenResourceObject(): void
    {
        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource('{resourceString -> f:resource() -> f:uri.resource()}');
        $template = new TemplateView($context);
        $template->assign('resourceString', 'EXT:core/Resources/Public/Icons/Extension.svg');
        self::assertEquals($this->resolveResourcePlaceholders('{{EXT:core/Resources/Public/Icons/Extension.svg}}'), $template->render());
    }

    /**
     * Replaces {{EXT:…}} and {{EXT-RELATIVE:…}} placeholders with the web path the
     * resource actually gets, absolute or without its leading slash respectively.
     *
     * A public extension resource is served from the extension directory in classic
     * mode and from the published _assets directory in composer mode. Data providers
     * cannot resolve that - they run before the instance is bootstrapped.
     */
    private function resolveResourcePlaceholders(string $expected): string
    {
        return preg_replace_callback(
            '/{{(EXT-RELATIVE|EXT):([^}]+)}}/',
            static function (array $matches): string {
                $uri = (string)PathUtility::getSystemResourceUri('EXT:' . $matches[2]);
                return $matches[1] === 'EXT-RELATIVE' ? ltrim($uri, '/') : $uri;
            },
            $expected
        );
    }
}
