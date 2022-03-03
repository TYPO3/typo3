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

namespace TYPO3\CMS\Fluid\Tests\Functional\ViewHelpers;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Extbase\Mvc\ExtbaseRequestParameters;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextFactory;
use TYPO3\CMS\Fluid\Tests\Functional\Fixtures\ViewHelpers\ExtendsAbstractEntity;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Fluid\Fluid\View\TemplateView;

class FormViewHelperTest extends FunctionalTestCase
{
    public function isRenderedDataProvider(): array
    {
        return [
            '#1' => [
                '<f:form action="{action}" method="{method}" fieldNamePrefix="{fieldNamePrefix}" />',
                [
                    'action' => 'fieldNamePrefix<>&"\'',
                    'method' => 'fieldNamePrefix<>&"\'',
                    'fieldNamePrefix' => 'fieldNamePrefix<>&"\'',
                ],
                // first element having "@extension" in name attribute
                '<input type="hidden" name="fieldNamePrefix&lt;&gt;&amp;&quot;&#039;[__referrer][@extension]" value="" />',
            ],
            '#2' => [
                '{f:form(action:action, method:method, fieldNamePrefix:fieldNamePrefix)}',
                [
                    'action' => 'fieldNamePrefix<>&"\'',
                    'method' => 'fieldNamePrefix<>&"\'',
                    'fieldNamePrefix' => 'fieldNamePrefix<>&"\'',
                ],
                // first element having "@extension" in name attribute
               '<input type="hidden" name="fieldNamePrefix&lt;&gt;&amp;&quot;&#039;[__referrer][@extension]" value="" />',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider isRenderedDataProvider
     */
    public function isRendered(string $source, array $variables, string $expectation): void
    {
        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource($source);
        $context->setRequest($this->createRequest());
        $view = new TemplateView($context);
        $view->assignMultiple($variables);
        $body = $view->render();
        $actual = null;
        if (preg_match('#<input[^>]+name=".+\[@extension\]"[^>]+>#m', $body, $matches)) {
            $actual = $matches[0];
        }
        self::assertSame($expectation, $actual);
    }

    /**
     * @test
     */
    public function renderHiddenIdentityFieldReturnsAHiddenInputFieldContainingTheObjectsUID(): void
    {
        $extendsAbstractEntity = new ExtendsAbstractEntity();
        $extendsAbstractEntity->_setProperty('uid', 123);
        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource('<f:form fieldNamePrefix="prefix" objectName="myObjectName" object="{object}" />');
        $context->setRequest($this->createRequest());
        $view = new TemplateView($context);
        $view->assign('object', $extendsAbstractEntity);
        $expected = '<input type="hidden" name="prefix[myObjectName][__identity]" value="123" />';
        self::assertStringContainsString($expected, $view->render());
    }

    /**
     * @test
     */
    public function setFormActionUriRespectsOverriddenArgument(): void
    {
        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource('<f:form actionUri="foobar" />');
        $context->setRequest($this->createRequest());
        $expected = '<form action="foobar" method="post">';
        self::assertStringContainsString($expected, (new TemplateView($context))->render());
    }

    /**
     * @test
     */
    public function nameArgumentIsUsedFormHiddenIdentityName(): void
    {
        $extendsAbstractEntity = new ExtendsAbstractEntity();
        $extendsAbstractEntity->_setProperty('uid', 123);
        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource('<f:form name="formName" fieldNamePrefix="prefix" object="{object}" />');
        $context->setRequest($this->createRequest());
        $view = new TemplateView($context);
        $view->assign('object', $extendsAbstractEntity);
        $expected = '<input type="hidden" name="prefix[formName][__identity]" value="123" />';
        self::assertStringContainsString($expected, $view->render());
    }

    /**
     * @test
     */
    public function objectNameArgumentOverrulesNameArgument(): void
    {
        $extendsAbstractEntity = new ExtendsAbstractEntity();
        $extendsAbstractEntity->_setProperty('uid', 123);
        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource('<f:form name="formName" fieldNamePrefix="prefix" objectName="myObjectName" object="{object}" />');
        $context->setRequest($this->createRequest());
        $view = new TemplateView($context);
        $view->assign('object', $extendsAbstractEntity);
        $expected = '<input type="hidden" name="prefix[myObjectName][__identity]" value="123" />';
        self::assertStringContainsString($expected, $view->render());
    }

    /**
     * @test
     */
    public function renderWrapsHiddenFieldsWithDivForXhtmlCompatibilityWithRewrittenPropertyMapper(): void
    {
        $extendsAbstractEntity = new ExtendsAbstractEntity();
        $extendsAbstractEntity->_setProperty('uid', 123);
        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource('<f:form fieldNamePrefix="prefix" objectName="myObjectName" object="{object}" />');
        $context->setRequest($this->createRequest());
        $view = new TemplateView($context);
        $view->assign('object', $extendsAbstractEntity);
        $expected = '<form action="" method="post">' . chr(10) . '<div>';
        self::assertStringContainsString($expected, $view->render());
    }

    /**
     * @test
     */
    public function renderWrapsHiddenFieldsWithDivAndAnAdditionalClassForXhtmlCompatibilityWithRewrittenPropertyMapper(): void
    {
        $extendsAbstractEntity = new ExtendsAbstractEntity();
        $extendsAbstractEntity->_setProperty('uid', 123);
        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource('<f:form hiddenFieldClassName="hidden" fieldNamePrefix="prefix" objectName="myObjectName" object="{object}" />');
        $context->setRequest($this->createRequest());
        $view = new TemplateView($context);
        $view->assign('object', $extendsAbstractEntity);
        $expected = '<form action="" method="post">' . chr(10) . '<div class="hidden">';
        self::assertStringContainsString($expected, $view->render());
    }

    /**
     * @test
     */
    public function renderHiddenReferrerFieldsAddCurrentControllerAndActionAsHiddenFields(): void
    {
        $extbaseRequestParameters = new ExtbaseRequestParameters();
        $extbaseRequestParameters->setControllerActionName('controllerActionName');
        $extbaseRequestParameters->setControllerName('controllerName');
        $extbaseRequestParameters->setControllerExtensionName('extensionName');
        $psr7Request = (new ServerRequest())->withAttribute('extbase', $extbaseRequestParameters);
        $psr7Request = $psr7Request->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE);
        $extbaseRequest = new Request($psr7Request);
        $extbaseRequest = $extbaseRequest->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE);

        $extendsAbstractEntity = new ExtendsAbstractEntity();
        $extendsAbstractEntity->_setProperty('uid', 123);
        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource('<f:form fieldNamePrefix="prefix" objectName="myObjectName" object="{object}" />');
        $context->setRequest($extbaseRequest);
        $view = new TemplateView($context);
        $view->assign('object', $extendsAbstractEntity);
        $expected = '<form action="" method="post">
<div>
<input type="hidden" name="prefix[myObjectName][__identity]" value="123" />

<input type="hidden" name="prefix[__referrer][@extension]" value="extensionName" />
<input type="hidden" name="prefix[__referrer][@controller]" value="controllerName" />
<input type="hidden" name="prefix[__referrer][@action]" value="controllerActionName" />
<input type="hidden" name="prefix[__referrer][arguments]" value="YTowOnt97e22094095b617b0604f3fe5b48446b0dfa46c8c" />
<input type="hidden" name="prefix[__referrer][@request]" value="{&quot;@extension&quot;:&quot;extensionName&quot;,&quot;@controller&quot;:&quot;controllerName&quot;,&quot;@action&quot;:&quot;controllerActionName&quot;}a85f8e01ed64daa6bd0910d3c3fafe3519eed791" />
<input type="hidden" name="prefix[__trustedProperties]" value="{&quot;myObjectName&quot;:{&quot;__identity&quot;:1}}c5603abb8f2ebaef799efd6ba9f46ea7edc650ea" />
</div>
</form>';
        self::assertSame($expected, $view->render());
    }

    protected function createRequest(): ServerRequestInterface
    {
        return (new Request())->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE);
    }
}
