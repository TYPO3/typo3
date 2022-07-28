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

namespace TYPO3\CMS\Form\Tests\Functional\RequestHandling;

use TYPO3\CMS\Form\Tests\Functional\Framework\FormHandling\FormDataFactory;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;

class RequestHandlingTest extends AbstractRequestHandlingTest
{
    /**
     * @var string $databaseScenarioFile
     */
    protected $databaseScenarioFile = __DIR__ . '/Fixtures/OnePageWithMultipleFormIntegrationsScenario.yaml';

    /**
     * @var FormDataFactory $formDataFactory
     */
    private $formDataFactory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->formDataFactory = new FormDataFactory();
    }

    public function theCachingBehavesTheSameForAllFormIntegrationVariantsDataProvider(): \Generator
    {
        yield 'Multistep form / ext:form content element' => [
            'formIdentifier' => 'multistep-test-form-1001',
            'formNamePrefix' => 'tx_form_formframework',
        ];

        yield 'Multistep form / custom extbase controller => RenderActionIsCached' => [
            'formIdentifier' => 'RenderActionIsCached-1002',
            'formNamePrefix' => 'tx_formcachingtests_renderactioniscached',
        ];

        yield 'Multistep form / custom extbase controller => AllActionsUncached' => [
            'formIdentifier' => 'AllActionsUncached-1003',
            'formNamePrefix' => 'tx_formcachingtests_allactionsuncached',
        ];

        // disabled until https://review.typo3.org/c/Packages/TYPO3.CMS/+/70460 is fixed
        // yield 'Multistep form / custom extbase controller => AllActionsCached' => [
        //     'formIdentifier' => 'AllActionsCached-1004',
        //     'formNamePrefix' => 'tx_formcachingtests_allactionscached',
        // ];

        // disabled until https://review.typo3.org/c/Packages/TYPO3.CMS/+/70460 is fixed
        // yield 'Multistep form / simple FLUIDTEMPLATE' => [
        //     'formIdentifier' => 'FormFromSimpleFluidtemplate',
        //     'formNamePrefix' => 'tx_form_formframework',
        // ];

        // disabled until https://review.typo3.org/c/Packages/TYPO3.CMS/+/70460 is fixed
        // yield 'Multistep form / COA FLUIDTEMPLATE through custom extbase controller => AllActionsCached' => [
        //     'formIdentifier' => 'FormFromCoaFluidtemplateThroughCustomExtbaseControllerAllActionsCached',
        //     'formNamePrefix' => 'tx_formcachingtests_allactionscached',
        // ];

        yield 'Multistep form / COA_INT FLUIDTEMPLATE through custom extbase controller => AllActionsCached' => [
            'formIdentifier' => 'FormFromCoaIntFluidtemplateThroughCustomExtbaseControllerAllActionsCached',
            'formNamePrefix' => 'tx_formcachingtests_allactionscached',
        ];

        // disabled until https://review.typo3.org/c/Packages/TYPO3.CMS/+/70460 is fixed
        // yield 'Multistep form / COA FLUIDTEMPLATE through custom extbase controller => RenderActionIsCached' => [
        //     'formIdentifier' => 'FormFromCoaFluidtemplateThroughCustomExtbaseControllerRenderActionIsCached',
        //     'formNamePrefix' => 'tx_formcachingtests_renderactioniscached',
        // ];

        yield 'Multistep form / COA_INT FLUIDTEMPLATE through custom extbase controller => RenderActionIsCached' => [
            'formIdentifier' => 'FormFromCoaIntFluidtemplateThroughCustomExtbaseControllerRenderActionIsCached',
            'formNamePrefix' => 'tx_formcachingtests_renderactioniscached',
        ];

        // disabled until https://review.typo3.org/c/Packages/TYPO3.CMS/+/70460 is fixed
        // yield 'Multistep form / COA FLUIDTEMPLATE through custom extbase controller => AllActionsUncached' => [
        //     'formIdentifier' => 'FormFromCoaFluidtemplateThroughCustomExtbaseControllerAllActionsUncached',
        //     'formNamePrefix' => 'tx_formcachingtests_allactionsuncached',
        // ];

        yield 'Multistep form / COA_INT FLUIDTEMPLATE through custom extbase controller => AllActionsUncached' => [
            'formIdentifier' => 'FormFromCoaIntFluidtemplateThroughCustomExtbaseControllerAllActionsUncached',
            'formNamePrefix' => 'tx_formcachingtests_allactionsuncached',
        ];

        // disabled until https://review.typo3.org/c/Packages/TYPO3.CMS/+/70460 is fixed
        // yield 'Multistep form / COA FLUIDTEMPLATE through ext:form controller' => [
        //     'formIdentifier' => 'FormFromCoaFluidtemplateThroughExtFormController',
        //     'formNamePrefix' => 'tx_form_formframework',
        // ];

        yield 'Multistep form / COA_INT FLUIDTEMPLATE through ext:form controller' => [
             'formIdentifier' => 'FormFromCoaIntFluidtemplateThroughExtFormController',
             'formNamePrefix' => 'tx_form_formframework',
         ];
    }

    /**
     * @test
     * @dataProvider theCachingBehavesTheSameForAllFormIntegrationVariantsDataProvider
     */
    public function theCachingBehavesTheSameForAllFormIntegrationVariants(string $formIdentifier, string $formNamePrefix): void
    {
        $uri = static::ROOT_PAGE_BASE_URI . '/form';

        // goto form page
        $pageMarkup = (string)$this->executeFrontendRequest(new InternalRequest($uri), null, true)->getBody();
        $formData = $this->formDataFactory->fromHtmlMarkupAndXpath($pageMarkup, '//form[@id="' . $formIdentifier . '"]');

        $honeypotIdFromStep1 = $formData->getHoneypotId();
        $sessionIdFromStep1 = $formData->getSessionId();

        self::assertEmpty($sessionIdFromStep1, 'session element is not rendered');
        self::assertEmpty($formData->toArray()['elementData'][$formNamePrefix . '[' . $formIdentifier . '][text-1]']['value'] ?? '_notempty_', 'form element "text-1" is empty');
        self::assertNotEmpty($honeypotIdFromStep1, 'honeypot element exists');

        // post data and go to summary page
        $formPostRequest = $formData->with('text-1', 'FOObarBAZ')->toPostRequest(new InternalRequest($uri));
        $pageMarkup = (string)$this->executeFrontendRequest($formPostRequest, null, true)->getBody();
        $formData = $this->formDataFactory->fromHtmlMarkupAndXpath($pageMarkup, '//form[@id="' . $formIdentifier . '"]');

        $honeypotIdFromStep2 = $formData->getHoneypotId();
        $sessionIdFromStep2 = $formData->getSessionId();
        $formMarkup = $formData->getFormMarkup();

        self::assertStringContainsString('Summary step', $formMarkup, 'the summary form step is shown');
        self::assertStringContainsString('FOObarBAZ', $formMarkup, 'data from "text-1" is shown');
        self::assertNotEmpty($sessionIdFromStep2, 'session element is rendered');
        self::assertEmpty($honeypotIdFromStep2, 'honeypot element does not exists on summary form step');

        // go back to first page
        $formPostRequest = $formData->with('__currentPage', '0')->toPostRequest(new InternalRequest($uri));
        $pageMarkup = (string)$this->executeFrontendRequest($formPostRequest, null, true)->getBody();
        $formData = $this->formDataFactory->fromHtmlMarkupAndXpath($pageMarkup, '//form[@id="' . $formIdentifier . '"]');

        $honeypotIdFromStep3 = $formData->getHoneypotId();
        $sessionIdFromStep3 = $formData->getSessionId();

        self::assertEquals('FOObarBAZ', $formData->toArray()['elementData'][$formNamePrefix . '[' . $formIdentifier . '][text-1]']['value'] ?? null, 'form element "text-1" contains submitted data');
        self::assertNotEquals($honeypotIdFromStep3, $honeypotIdFromStep1, 'honeypot differs from historical honeypot');
        self::assertEquals($sessionIdFromStep3, $sessionIdFromStep2, 'session is still available');

        // post data and go to summary page
        $formPostRequest = $formData->with('text-1', 'BAZbarFOO')->toPostRequest(new InternalRequest($uri));
        $pageMarkup = (string)$this->executeFrontendRequest($formPostRequest, null, true)->getBody();
        $formData = $this->formDataFactory->fromHtmlMarkupAndXpath($pageMarkup, '//form[@id="' . $formIdentifier . '"]');

        $honeypotIdFromStep4 = $formData->getHoneypotId();
        $sessionIdFromStep4 = $formData->getSessionId();
        $formMarkup = $formData->getFormMarkup();

        self::assertStringContainsString('Summary step', $formMarkup, 'the summary form step is shown');
        self::assertStringContainsString('BAZbarFOO', $formMarkup, 'data from "text-1" is shown');
        self::assertEmpty($honeypotIdFromStep4, 'honeypot element does not exists on summary form step');
        self::assertEquals($sessionIdFromStep4, $sessionIdFromStep3, 'session is still available');

        // submit and trigger finishers
        $formPostRequest = $formData->toPostRequest(new InternalRequest($uri));
        $pageMarkup = (string)$this->executeFrontendRequest($formPostRequest, null, true)->getBody();
        $formData = $this->formDataFactory->fromHtmlMarkupAndXpath($pageMarkup, '//*[@id="' . $formIdentifier . '"]');

        $formMarkup = $formData->getFormMarkup();
        $mails = $this->getMailSpoolMessages();

        self::assertStringContainsString('Form is submitted', $formMarkup, 'the finisher text is shown');
        self::assertCount(1, $this->getMailSpoolMessages(), 'a mail is sent');
        self::assertStringContainsString('Text: BAZbarFOO', $mails[0]['plaintext'] ?? '', 'Mail contains form data');
    }

    public function formRendersUncachedIfTheActionTargetIsCalledViaHttpGetDataProvider(): \Generator
    {
        yield 'Multistep form / ext:form content element' => [
            'formIdentifier' => 'multistep-test-form-1001',
        ];

        yield 'Multistep form / custom extbase controller => RenderActionIsCached' => [
            'formIdentifier' => 'RenderActionIsCached-1002',
        ];

        yield 'Multistep form / custom extbase controller => AllActionsUncached' => [
            'formIdentifier' => 'AllActionsUncached-1003',
        ];

        // disabled until https://review.typo3.org/c/Packages/TYPO3.CMS/+/70460 is fixed
        // yield 'Multistep form / custom extbase controller => AllActionsCached' => [
        //     'formIdentifier' => 'AllActionsCached-1004',
        // ];

        // disabled until https://review.typo3.org/c/Packages/TYPO3.CMS/+/70460 is fixed
        // yield 'Multistep form / simple FLUIDTEMPLATE' => [
        //     'formIdentifier' => 'FormFromSimpleFluidtemplate',
        // ];

        // disabled until https://review.typo3.org/c/Packages/TYPO3.CMS/+/70460 is fixed
        // yield 'Multistep form / COA FLUIDTEMPLATE through custom extbase controller => AllActionsCached' => [
        //     'formIdentifier' => 'FormFromCoaFluidtemplateThroughCustomExtbaseControllerAllActionsCached',
        // ];

        yield 'Multistep form / COA_INT FLUIDTEMPLATE through custom extbase controller => AllActionsCached' => [
            'formIdentifier' => 'FormFromCoaIntFluidtemplateThroughCustomExtbaseControllerAllActionsCached',
        ];

        // disabled until https://review.typo3.org/c/Packages/TYPO3.CMS/+/70460 is fixed
        // yield 'Multistep form / COA FLUIDTEMPLATE through custom extbase controller => RenderActionIsCached' => [
        //     'formIdentifier' => 'FormFromCoaFluidtemplateThroughCustomExtbaseControllerRenderActionIsCached',
        // ];

        yield 'Multistep form / COA_INT FLUIDTEMPLATE through custom extbase controller => RenderActionIsCached' => [
            'formIdentifier' => 'FormFromCoaIntFluidtemplateThroughCustomExtbaseControllerRenderActionIsCached',
        ];

        // disabled until https://review.typo3.org/c/Packages/TYPO3.CMS/+/70460 is fixed
        // yield 'Multistep form / COA FLUIDTEMPLATE through custom extbase controller => AllActionsUncached' => [
        //     'formIdentifier' => 'FormFromCoaFluidtemplateThroughCustomExtbaseControllerAllActionsUncached',
        // ];

        yield 'Multistep form / COA_INT FLUIDTEMPLATE through custom extbase controller => AllActionsUncached' => [
            'formIdentifier' => 'FormFromCoaIntFluidtemplateThroughCustomExtbaseControllerAllActionsUncached',
        ];

        // disabled until https://review.typo3.org/c/Packages/TYPO3.CMS/+/70460 is fixed
        // yield 'Multistep form / COA FLUIDTEMPLATE through ext:form controller' => [
        //     'formIdentifier' => 'FormFromCoaFluidtemplateThroughExtFormController',
        // ];

        yield 'Multistep form / COA_INT FLUIDTEMPLATE through ext:form controller' => [
             'formIdentifier' => 'FormFromCoaIntFluidtemplateThroughExtFormController',
         ];
    }

    /**
     * @test
     * @dataProvider formRendersUncachedIfTheActionTargetIsCalledViaHttpGetDataProvider
     */
    public function formRendersUncachedIfTheActionTargetIsCalledViaHttpGet(string $formIdentifier): void
    {
        $uri = static::ROOT_PAGE_BASE_URI . '/form';

        // goto form page
        $pageMarkup = (string)$this->executeFrontendRequest(new InternalRequest($uri), null, true)->getBody();
        $formData = $this->formDataFactory->fromHtmlMarkupAndXpath($pageMarkup, '//form[@id="' . $formIdentifier . '"]');

        // goto form target with HTTP GET
        $pageMarkup = (string)$this->executeFrontendRequest($formData->toGetRequest(new InternalRequest($uri), false), null, true)->getBody();

        // goto form page
        $pageMarkup = (string)$this->executeFrontendRequest(new InternalRequest($uri), null, true)->getBody();
        $formData = $this->formDataFactory->fromHtmlMarkupAndXpath($pageMarkup, '//form[@id="' . $formIdentifier . '"]');

        // post data and go to summary page
        $formPostRequest = $formData->with('text-1', 'FOObarBAZ')->toPostRequest(new InternalRequest($uri));
        $pageMarkup = (string)$this->executeFrontendRequest($formPostRequest, null, true)->getBody();
        $formData = $this->formDataFactory->fromHtmlMarkupAndXpath($pageMarkup, '//form[@id="' . $formIdentifier . '"]');

        $formMarkup = $formData->getFormMarkup();

        self::assertStringContainsString('Summary step', $formMarkup, 'the summary form step is shown');
        self::assertStringContainsString('FOObarBAZ', $formMarkup, 'data from "text-1" is shown');
    }

    public function theHoneypotElementChangesWithEveryCallOfTheFirstFormStepDataProvider(): \Generator
    {
        // disabled until https://review.typo3.org/c/Packages/TYPO3.CMS/+/67642/ is fixed
        // yield 'Multistep form / ext:form content element' => [
        //     'formIdentifier' => 'multistep-test-form-1001',
        // ];

        // disabled until https://review.typo3.org/c/Packages/TYPO3.CMS/+/67642/ is fixed
        // yield 'Multistep form / custom extbase controller => RenderActionIsCached' => [
        //     'formIdentifier' => 'RenderActionIsCached-1002',
        // ];

        yield 'Multistep form / custom extbase controller => AllActionsUncached' => [
            'formIdentifier' => 'AllActionsUncached-1003',
        ];

        // disabled until https://review.typo3.org/c/Packages/TYPO3.CMS/+/67642/ is fixed
        // yield 'Multistep form / custom extbase controller => AllActionsCached' => [
        //     'formIdentifier' => 'AllActionsCached-1004',
        // ];

        // disabled until https://review.typo3.org/c/Packages/TYPO3.CMS/+/67642/ is fixed
        // yield 'Multistep form / simple FLUIDTEMPLATE' => [
        //     'formIdentifier' => 'FormFromSimpleFluidtemplate',
        // ];

        // disabled until https://review.typo3.org/c/Packages/TYPO3.CMS/+/67642/ is fixed
        // yield 'Multistep form / COA FLUIDTEMPLATE through custom extbase controller => AllActionsCached' => [
        //     'formIdentifier' => 'FormFromCoaFluidtemplateThroughCustomExtbaseControllerAllActionsCached',
        // ];

        yield 'Multistep form / COA_INT FLUIDTEMPLATE through custom extbase controller => AllActionsCached' => [
            'formIdentifier' => 'FormFromCoaIntFluidtemplateThroughCustomExtbaseControllerAllActionsCached',
        ];

        // disabled until https://review.typo3.org/c/Packages/TYPO3.CMS/+/67642/ is fixed
        // yield 'Multistep form / COA FLUIDTEMPLATE through custom extbase controller => RenderActionIsCached' => [
        //     'formIdentifier' => 'FormFromCoaFluidtemplateThroughCustomExtbaseControllerRenderActionIsCached',
        // ];

        yield 'Multistep form / COA_INT FLUIDTEMPLATE through custom extbase controller => RenderActionIsCached' => [
            'formIdentifier' => 'FormFromCoaIntFluidtemplateThroughCustomExtbaseControllerRenderActionIsCached',
        ];

        // disabled until https://review.typo3.org/c/Packages/TYPO3.CMS/+/67642/ is fixed
        // yield 'Multistep form / COA FLUIDTEMPLATE through custom extbase controller => AllActionsUncached' => [
        //     'formIdentifier' => 'FormFromCoaFluidtemplateThroughCustomExtbaseControllerAllActionsUncached',
        // ];

        yield 'Multistep form / COA_INT FLUIDTEMPLATE through custom extbase controller => AllActionsUncached' => [
            'formIdentifier' => 'FormFromCoaIntFluidtemplateThroughCustomExtbaseControllerAllActionsUncached',
        ];

        // disabled until https://review.typo3.org/c/Packages/TYPO3.CMS/+/67642/ is fixed
        // yield 'Multistep form / COA FLUIDTEMPLATE through ext:form controller' => [
        //     'formIdentifier' => 'FormFromCoaFluidtemplateThroughExtFormController',
        // ];

        yield 'Multistep form / COA_INT FLUIDTEMPLATE through ext:form controller' => [
            'formIdentifier' => 'FormFromCoaIntFluidtemplateThroughExtFormController',
        ];
    }

    /**
     * @test
     * @dataProvider theHoneypotElementChangesWithEveryCallOfTheFirstFormStepDataProvider
     */
    public function theHoneypotElementChangesWithEveryCallOfTheFirstFormStep(string $formIdentifier): void
    {
        $uri = static::ROOT_PAGE_BASE_URI . '/form';

        // goto form page
        $pageMarkup = (string)$this->executeFrontendRequest(new InternalRequest($uri), null, true)->getBody();
        $formData = $this->formDataFactory->fromHtmlMarkupAndXpath($pageMarkup, '//form[@id="' . $formIdentifier . '"]');
        $honeypotId = $formData->getHoneypotId();

        self::assertNotEmpty($honeypotId, 'honeypot element exists');

        // revisit form page
        $pageMarkup = (string)$this->executeFrontendRequest(new InternalRequest($uri), null, true)->getBody();
        $formData = $this->formDataFactory->fromHtmlMarkupAndXpath($pageMarkup, '//form[@id="' . $formIdentifier . '"]');

        $honeypotIdFromRevisit = $formData->getHoneypotId();

        self::assertNotEquals($honeypotIdFromRevisit, $honeypotId, 'honeypot differs from historical honeypot');
    }
}
