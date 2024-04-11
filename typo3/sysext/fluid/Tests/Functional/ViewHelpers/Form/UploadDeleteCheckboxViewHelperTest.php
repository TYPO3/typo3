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

namespace TYPO3\CMS\Fluid\Tests\Functional\ViewHelpers\Form;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use TYPO3\CMS\Extbase\Mvc\ExtbaseRequestParameters;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Fluid\Fluid\View\TemplateView;

final class UploadDeleteCheckboxViewHelperTest extends FunctionalTestCase
{
    #[Test]
    public function exceptionIsThrownWhenViewHelperNotUsedInFluidFormContext(): void
    {
        $extbaseRequestParameters = new ExtbaseRequestParameters();
        $psr7Request = (new ServerRequest())->withAttribute('extbase', $extbaseRequestParameters)
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $this->get(ConfigurationManagerInterface::class)->setRequest($psr7Request);
        $extbaseRequest = (new Request($psr7Request))
            ->withPluginName('MyPlugin')
            ->withControllerObjectName('VENDOR\\MyExtension\\Controller\\UploadController');

        $fileReferenceMock = $this->createMock(FileReference::class);

        $context = $this->get(RenderingContextFactory::class)->create([], $extbaseRequest);
        $context->getTemplatePaths()->setTemplateSource('<f:form.uploadDeleteCheckbox id="file" property="file" fileReference="{fileReference}" />');
        $view = new TemplateView($context);
        $view->assign('fileReference', $fileReferenceMock);

        $this->expectExceptionCode(1719655880);

        $view->render();
    }

    #[Test]
    public function exceptionIsThrownWhenExtensionNameNotDefinedInExtbaseRequest(): void
    {
        $extbaseRequestParameters = new ExtbaseRequestParameters();
        $psr7Request = (new ServerRequest())->withAttribute('extbase', $extbaseRequestParameters)
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $extbaseRequest = new Request($psr7Request);

        $fileReferenceMock = $this->createMock(FileReference::class);

        $context = $this->get(RenderingContextFactory::class)->create([], $extbaseRequest);
        $context->getTemplatePaths()->setTemplateSource('<f:form.uploadDeleteCheckbox id="file" property="file" fileReference="{fileReference}" />');
        $view = new TemplateView($context);
        $view->assign('fileReference', $fileReferenceMock);

        $this->expectExceptionCode(1719660837);

        $view->render();
    }

    #[Test]
    public function exceptionIsThrownWhenPluginNameNotDefinedInExtbaseRequest(): void
    {
        $extbaseRequestParameters = new ExtbaseRequestParameters();
        $psr7Request = (new ServerRequest())->withAttribute('extbase', $extbaseRequestParameters)
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $extbaseRequest = (new Request($psr7Request))->withControllerExtensionName('MyExtension');

        $fileReferenceMock = $this->createMock(FileReference::class);

        $context = $this->get(RenderingContextFactory::class)->create([], $extbaseRequest);
        $context->getTemplatePaths()->setTemplateSource('<f:form.uploadDeleteCheckbox id="file" property="file" fileReference="{fileReference}" />');
        $view = new TemplateView($context);
        $view->assign('fileReference', $fileReferenceMock);

        $this->expectExceptionCode(1719660837);

        $view->render();
    }

    #[Test]
    public function renderCorrectlySetsTagNameAndAttributes(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] = 'bar';

        $extbaseRequestParameters = new ExtbaseRequestParameters();
        $psr7Request = (new ServerRequest())->withAttribute('extbase', $extbaseRequestParameters)
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $this->get(ConfigurationManagerInterface::class)->setRequest($psr7Request);
        $extbaseRequest = (new Request($psr7Request))
            ->withPluginName('MyPlugin')
            ->withControllerExtensionName('MyExtension');

        $fileReferenceMock = $this->createMock(FileReference::class);
        $fileReferenceMock->method('getUid')->willReturn(1);

        $context = $this->get(RenderingContextFactory::class)->create([], $extbaseRequest);
        $context->getTemplatePaths()->setTemplateSource(
            '<f:form name="myForm"><f:form.uploadDeleteCheckbox id="file" property="file" fileReference="{fileReference}" /></f:form>'
        );
        $view = new TemplateView($context);
        $view->assign('fileReference', $fileReferenceMock);

        $renderResult = $view->render();
        self::assertStringContainsString('input type="checkbox" id="file" name="tx_myextension_myplugin[@delete][myForm][c7671aeb4c76fb2359285ea74057b6c22a0a6842]"', $renderResult);
        self::assertStringContainsString('{&quot;property&quot;:&quot;file&quot;,&quot;fileReference&quot;:1}4ea2b5149110f138f17f8ad991e09f6b4002c89b"', $renderResult);
    }

    #[Test]
    public function renderCorrectlySetsCheckedStateOnValidationErrors(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] = 'bar';

        $originalRequestExtbaseRequestParameters = new ExtbaseRequestParameters();
        $originalPsr7Rquest = (new ServerRequest())->withAttribute('extbase', $originalRequestExtbaseRequestParameters)
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $originalExtbaseRequest = (new Request($originalPsr7Rquest));

        $extbaseRequestParameters = new ExtbaseRequestParameters();
        $extbaseRequestParameters->setOriginalRequest($originalExtbaseRequest);

        $psr7Request = (new ServerRequest('/foo/bar', 'POST'))->withParsedBody(
            [
                'tx_myextension_myplugin' => [
                    '@delete' => [
                        'myForm' => [
                            'c7671aeb4c76fb2359285ea74057b6c22a0a6842' => 'some-value',
                        ],
                    ],
                ],
            ]
        );
        $psr7Request = $psr7Request->withAttribute('extbase', $extbaseRequestParameters)
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $this->get(ConfigurationManagerInterface::class)->setRequest($psr7Request);

        $extbaseRequest = (new Request($psr7Request))
            ->withPluginName('MyPlugin')
            ->withControllerExtensionName('MyExtension');

        $fileReferenceMock = $this->createMock(FileReference::class);
        $fileReferenceMock->method('getUid')->willReturn(1);

        $context = $this->get(RenderingContextFactory::class)->create([], $extbaseRequest);
        $context->getTemplatePaths()->setTemplateSource(
            '<f:form name="myForm"><f:form.uploadDeleteCheckbox id="file" property="file" fileReference="{fileReference}" /></f:form>'
        );
        $view = new TemplateView($context);
        $view->assign('fileReference', $fileReferenceMock);

        $renderResult = $view->render();
        self::assertStringContainsString('checked="checked"', $renderResult);
    }
}
