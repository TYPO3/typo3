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
use TYPO3\CMS\Extbase\Mvc\ExtbaseRequestParameters;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Fluid\Fluid\View\TemplateView;

final class ResourceViewHelperTest extends FunctionalTestCase
{
    private const pathToCoreIcon = __DIR__ . '/../../../../../core/Resources/Public/Icons/Extension.svg';
    protected bool $initializeDatabase = false;

    #[Test]
    public function renderingFailsWithNonExtSyntaxWithoutExtensionNameWithPsr7Request()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1639672666);
        $context = $this->get(RenderingContextFactory::class)->create([], new ServerRequest());
        $context->getTemplatePaths()->setTemplateSource('<f:uri.resource path="Icons/Extension.svg" />');
        (new TemplateView($context))->render();
    }

    #[Test]
    public function renderingFailsWhenExtensionNameNotSetInExtbaseRequest(): void
    {
        $serverRequest = (new ServerRequest())->withAttribute('extbase', new ExtbaseRequestParameters());
        $context = $this->get(RenderingContextFactory::class)->create([], new Request($serverRequest));
        $context->getTemplatePaths()->setTemplateSource('<f:uri.resource path="Icons/Extension.svg" />');
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1640097205);
        (new TemplateView($context))->render();
    }

    public static function renderWithExtbaseRequestDataProvider(): \Generator
    {
        $iconFileMtime = filemtime(self::pathToCoreIcon);
        yield 'render returns URI using extensionName from Extbase Request' => [
            '<f:uri.resource path="Icons/Extension.svg" />',
            '/typo3/sysext/core/Resources/Public/Icons/Extension.svg?' . $iconFileMtime,
        ];
        yield 'render gracefully trims leading slashes from path' => [
            '<f:uri.resource path="/Icons/Extension.svg" />',
            '/typo3/sysext/core/Resources/Public/Icons/Extension.svg?' . $iconFileMtime,
        ];
        yield 'render returns URI using UpperCamelCase extensionName' => [
            '<f:uri.resource path="Icons/Extension.svg" extensionName="Core" />',
            '/typo3/sysext/core/Resources/Public/Icons/Extension.svg?' . $iconFileMtime,
        ];
        yield 'render returns URI using extension key as extensionName' => [
            '<f:uri.resource path="Icons/Extension.svg" extensionName="core" />',
            '/typo3/sysext/core/Resources/Public/Icons/Extension.svg?' . $iconFileMtime,
        ];
        yield 'render returns URI using EXT: syntax' => [
            '<f:uri.resource path="EXT:core/Resources/Public/Icons/Extension.svg" />',
            '/typo3/sysext/core/Resources/Public/Icons/Extension.svg?' . $iconFileMtime,
        ];
    }

    #[DataProvider('renderWithExtbaseRequestDataProvider')]
    #[Test]
    public function renderWithExtbaseRequest(string $template, string $expected): void
    {
        $extbaseRequestParameters = new ExtbaseRequestParameters();
        $extbaseRequestParameters->setControllerExtensionName('Core');
        $normalizedParams = $this->createMock(NormalizedParams::class);
        $normalizedParams->method('getSitePath')->willReturn('/');
        $serverRequest = (new ServerRequest())->withAttribute('extbase', $extbaseRequestParameters)
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withAttribute('normalizedParams', $normalizedParams);
        $extbaseRequest = (new Request($serverRequest));
        $context = $this->get(RenderingContextFactory::class)->create([], $extbaseRequest);
        $context->getTemplatePaths()->setTemplateSource($template);
        self::assertEquals($expected, (new TemplateView($context))->render());
    }

    public static function renderWithAndWithoutRequestDataProvider(): \Generator
    {
        $iconFileMtime = filemtime(self::pathToCoreIcon);
        yield 'render gracefully trims leading slashes from path' => [
            '<f:uri.resource path="/Icons/Extension.svg" extensionName="Core" />',
            'typo3/sysext/core/Resources/Public/Icons/Extension.svg?' . $iconFileMtime,
        ];
        yield 'render returns URI using UpperCamelCase extensionName' => [
            '<f:uri.resource path="Icons/Extension.svg" extensionName="Core" />',
            'typo3/sysext/core/Resources/Public/Icons/Extension.svg?' . $iconFileMtime,
        ];
        yield 'render returns URI using extension key as extensionName' => [
            '<f:uri.resource path="Icons/Extension.svg" extensionName="core" />',
            'typo3/sysext/core/Resources/Public/Icons/Extension.svg?' . $iconFileMtime,
        ];
        yield 'render returns URI using EXT: syntax' => [
            '<f:uri.resource path="EXT:core/Resources/Public/Icons/Extension.svg" />',
            'typo3/sysext/core/Resources/Public/Icons/Extension.svg?' . $iconFileMtime,
        ];
    }

    #[DataProvider('renderWithAndWithoutRequestDataProvider')]
    #[Test]
    public function renderWithBackendRequest(string $template, string $expected): void
    {
        $urlPrefix = '/prefix/';
        $normalizedParams = $this->createMock(NormalizedParams::class);
        $normalizedParams->method('getSitePath')->willReturn($urlPrefix);
        $serverRequest = (new ServerRequest())
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withAttribute('normalizedParams', $normalizedParams);
        $context = $this->get(RenderingContextFactory::class)->create([], $serverRequest);
        $context->getTemplatePaths()->setTemplateSource($template);
        self::assertEquals($urlPrefix . $expected, (new TemplateView($context))->render());
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
        $normalizedParams = $this->createMock(NormalizedParams::class);
        $normalizedParams->method('getSitePath')->willReturn('/');
        $serverRequest = (new ServerRequest())
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE)
            ->withAttribute('normalizedParams', $normalizedParams)
            ->withAttribute('frontend.typoscript', $frontendTypoScript);
        $context = $this->get(RenderingContextFactory::class)->create([], $serverRequest);
        $context->getTemplatePaths()->setTemplateSource($template);
        self::assertEquals($urlPrefix . $expected, (new TemplateView($context))->render());
    }

    #[DataProvider('renderWithAndWithoutRequestDataProvider')]
    #[Test]
    public function renderWithoutRequest(string $template, string $expected): void
    {
        // If no request is given, the default prefix "/" kicks in
        $urlPrefix = '/';
        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource($template);
        self::assertEquals($urlPrefix . $expected, (new TemplateView($context))->render());
    }

    #[Test]
    public function renderWithGivenResourceObject(): void
    {
        $iconFileMtime = filemtime(self::pathToCoreIcon);
        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource('{resourceString -> f:resource() -> f:uri.resource()}');
        $template = new TemplateView($context);
        $template->assign('resourceString', 'EXT:core/Resources/Public/Icons/Extension.svg');
        self::assertEquals('/typo3/sysext/core/Resources/Public/Icons/Extension.svg?' . $iconFileMtime, $template->render());
    }
}
