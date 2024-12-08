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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\TypoScript\AST\Node\RootNode;
use TYPO3\CMS\Core\TypoScript\FrontendTypoScript;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Mvc\ExtbaseRequestParameters;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextFactory;
use TYPO3\CMS\Fluid\Tests\Functional\Fixtures\ViewHelpers\ExtendsAbstractEntity;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Fluid\Fluid\View\TemplateView;

final class FormViewHelperTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8'],
    ];

    public static function isRenderedDataProvider(): array
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
                '<input type="hidden" name="fieldNamePrefix&lt;&gt;&amp;&quot;&#039;[__referrer][@extension]" value="" >',
            ],
            '#2' => [
                '{f:form(action:action, method:method, fieldNamePrefix:fieldNamePrefix)}',
                [
                    'action' => 'fieldNamePrefix<>&"\'',
                    'method' => 'fieldNamePrefix<>&"\'',
                    'fieldNamePrefix' => 'fieldNamePrefix<>&"\'',
                ],
                // first element having "@extension" in name attribute
                '<input type="hidden" name="fieldNamePrefix&lt;&gt;&amp;&quot;&#039;[__referrer][@extension]" value="" >',
            ],
        ];
    }

    #[DataProvider('isRenderedDataProvider')]
    #[Test]
    public function isRendered(string $source, array $variables, string $expectation): void
    {
        $request = $this->createRequest();
        $context = $this->get(RenderingContextFactory::class)->create([], $request);
        $context->getTemplatePaths()->setTemplateSource($source);
        $this->get(ConfigurationManagerInterface::class)->setRequest($request);
        $view = new TemplateView($context);
        $view->assignMultiple($variables);
        $body = $view->render();
        $actual = null;
        if (preg_match('#<input[^>]+name=".+\[@extension\]"[^>]+>#m', $body, $matches)) {
            $actual = $matches[0];
        }
        self::assertSame($expectation, $actual);
    }

    #[Test]
    public function renderHiddenIdentityFieldReturnsAHiddenInputFieldContainingTheObjectsUID(): void
    {
        $extendsAbstractEntity = new ExtendsAbstractEntity();
        $extendsAbstractEntity->_setProperty('uid', 123);
        $request = $this->createRequest();
        $context = $this->get(RenderingContextFactory::class)->create([], $request);
        $context->getTemplatePaths()->setTemplateSource('<f:form fieldNamePrefix="prefix" objectName="myObjectName" object="{object}" />');
        $this->get(ConfigurationManagerInterface::class)->setRequest($request);
        $view = new TemplateView($context);
        $view->assign('object', $extendsAbstractEntity);
        $expected = '<input type="hidden" name="prefix[myObjectName][__identity]" value="123" >';
        self::assertStringContainsString($expected, $view->render());
    }

    #[Test]
    public function setFormActionUriRespectsOverriddenArgument(): void
    {
        $request = $this->createRequest();
        $context = $this->get(RenderingContextFactory::class)->create([], $request);
        $context->getTemplatePaths()->setTemplateSource('<f:form actionUri="foobar" />');
        $this->get(ConfigurationManagerInterface::class)->setRequest($request);
        $expected = '<form action="foobar" method="post">';
        self::assertStringContainsString($expected, (new TemplateView($context))->render());
    }

    #[Test]
    public function nameAttributeIsSetIfGiven(): void
    {
        $request = $this->createRequest();
        $context = $this->get(RenderingContextFactory::class)->create([], $request);
        $context->getTemplatePaths()->setTemplateSource('<f:form name="myForm" />');
        $this->get(ConfigurationManagerInterface::class)->setRequest($request);
        $expected = '<form action="" method="post" name="myForm">';
        self::assertStringContainsString($expected, (new TemplateView($context))->render());
    }

    #[Test]
    public function emptyNameAttributeIsNotSet(): void
    {
        $request = $this->createRequest();
        $context = $this->get(RenderingContextFactory::class)->create([], $request);
        $context->getTemplatePaths()->setTemplateSource('<f:form name="" />');
        $this->get(ConfigurationManagerInterface::class)->setRequest($request);
        $expected = '<form action="" method="post">';
        self::assertStringContainsString($expected, (new TemplateView($context))->render());
    }

    #[Test]
    public function nameArgumentIsUsedFormHiddenIdentityName(): void
    {
        $extendsAbstractEntity = new ExtendsAbstractEntity();
        $extendsAbstractEntity->_setProperty('uid', 123);
        $request = $this->createRequest();
        $context = $this->get(RenderingContextFactory::class)->create([], $request);
        $context->getTemplatePaths()->setTemplateSource('<f:form name="formName" fieldNamePrefix="prefix" object="{object}" />');
        $this->get(ConfigurationManagerInterface::class)->setRequest($request);
        $view = new TemplateView($context);
        $view->assign('object', $extendsAbstractEntity);
        $expected = '<input type="hidden" name="prefix[formName][__identity]" value="123" >';
        self::assertStringContainsString($expected, $view->render());
    }

    #[Test]
    public function objectNameArgumentOverrulesNameArgument(): void
    {
        $extendsAbstractEntity = new ExtendsAbstractEntity();
        $extendsAbstractEntity->_setProperty('uid', 123);
        $request = $this->createRequest();
        $context = $this->get(RenderingContextFactory::class)->create([], $request);
        $context->getTemplatePaths()->setTemplateSource('<f:form name="formName" fieldNamePrefix="prefix" objectName="myObjectName" object="{object}" />');
        $this->get(ConfigurationManagerInterface::class)->setRequest($request);
        $view = new TemplateView($context);
        $view->assign('object', $extendsAbstractEntity);
        $expected = '<input type="hidden" name="prefix[myObjectName][__identity]" value="123" >';
        self::assertStringContainsString($expected, $view->render());
    }

    #[Test]
    public function renderWrapsHiddenFieldsWithDivForXhtmlCompatibilityWithRewrittenPropertyMapper(): void
    {
        $extendsAbstractEntity = new ExtendsAbstractEntity();
        $extendsAbstractEntity->_setProperty('uid', 123);
        $request = $this->createRequest();
        $context = $this->get(RenderingContextFactory::class)->create([], $request);
        $context->getTemplatePaths()->setTemplateSource('<f:form fieldNamePrefix="prefix" objectName="myObjectName" object="{object}" />');
        $this->get(ConfigurationManagerInterface::class)->setRequest($request);
        $view = new TemplateView($context);
        $view->assign('object', $extendsAbstractEntity);
        $expected = '<form action="" method="post">' . chr(10) . '<div>';
        self::assertStringContainsString($expected, $view->render());
    }

    #[Test]
    public function renderWrapsHiddenFieldsWithDivAndAnAdditionalClassForXhtmlCompatibilityWithRewrittenPropertyMapper(): void
    {
        $extendsAbstractEntity = new ExtendsAbstractEntity();
        $extendsAbstractEntity->_setProperty('uid', 123);
        $request = $this->createRequest();
        $context = $this->get(RenderingContextFactory::class)->create([], $request);
        $context->getTemplatePaths()->setTemplateSource('<f:form hiddenFieldClassName="hidden" fieldNamePrefix="prefix" objectName="myObjectName" object="{object}" />');
        $this->get(ConfigurationManagerInterface::class)->setRequest($request);
        $view = new TemplateView($context);
        $view->assign('object', $extendsAbstractEntity);
        $expected = '<form action="" method="post">' . chr(10) . '<div class="hidden">';
        self::assertStringContainsString($expected, $view->render());
    }

    #[Test]
    public function renderHiddenReferrerFieldsAddCurrentControllerAndActionAsHiddenFields1(): void
    {
        $extbaseRequestParameters = new ExtbaseRequestParameters();
        $extbaseRequestParameters->setControllerActionName('controllerActionName');
        $extbaseRequestParameters->setControllerName('controllerName');
        $extbaseRequestParameters->setControllerExtensionName('extensionName');
        $psr7Request = $this->createRequest()->withAttribute('extbase', $extbaseRequestParameters);
        $this->get(ConfigurationManagerInterface::class)->setRequest($psr7Request);
        $extbaseRequest = new Request($psr7Request);
        $extendsAbstractEntity = new ExtendsAbstractEntity();
        $extendsAbstractEntity->_setProperty('uid', 123);
        $context = $this->get(RenderingContextFactory::class)->create([], $extbaseRequest);
        $context->getTemplatePaths()->setTemplateSource('<f:form fieldNamePrefix="prefix" objectName="myObjectName" object="{object}" />');
        $view = new TemplateView($context);
        $view->assign('object', $extendsAbstractEntity);
        $expected = '<form action="" method="post">
<div>
<input type="hidden" name="prefix[myObjectName][__identity]" value="123" >

<input type="hidden" name="prefix[__referrer][@extension]" value="extensionName" >
<input type="hidden" name="prefix[__referrer][@controller]" value="controllerName" >
<input type="hidden" name="prefix[__referrer][@action]" value="controllerActionName" >
<input type="hidden" name="prefix[__referrer][arguments]" value="YTowOnt99e84bd507db45be875f9238be42d954813180d05" >
<input type="hidden" name="prefix[__referrer][@request]" value="{&quot;@extension&quot;:&quot;extensionName&quot;,&quot;@controller&quot;:&quot;controllerName&quot;,&quot;@action&quot;:&quot;controllerActionName&quot;}d5e7bc06c14881c8fe6f373c2236c4b62d13465c" >
<input type="hidden" name="prefix[__trustedProperties]" value="{&quot;myObjectName&quot;:{&quot;__identity&quot;:1}}9e6686e8fe21e9b4d3f5a89a66fed4193f4758b4" >
</div>
</form>';
        self::assertSame($expected, $view->render());
    }

    private function createRequest(): ServerRequestInterface
    {
        $frontendTypoScript = new FrontendTypoScript(new RootNode(), [], [], []);
        $frontendTypoScript->setSetupTree(new RootNode());
        $frontendTypoScript->setSetupArray([]);
        $frontendTypoScript->setConfigArray([]);
        $contentObject = $this->get(ContentObjectRenderer::class);
        $serverRequest = (new ServerRequest())
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE)
            ->withAttribute('frontend.typoscript', $frontendTypoScript)
            ->withAttribute('extbase', new ExtbaseRequestParameters())
            ->withAttribute('currentContentObject', $contentObject);
        return new Request($serverRequest);
    }
}
