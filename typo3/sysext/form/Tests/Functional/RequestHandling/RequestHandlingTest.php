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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Mailer\SentMessage;
use TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend;
use TYPO3\CMS\Core\Cache\Frontend\VariableFrontend;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Mail\FluidEmail;
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Form\Tests\Functional\Framework\FormHandling\FormDataFactory;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\TestingFramework\Core\Functional\Framework\DataHandling\Scenario\DataHandlerFactory;
use TYPO3\TestingFramework\Core\Functional\Framework\DataHandling\Scenario\DataHandlerWriter;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class RequestHandlingTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    protected array $coreExtensionsToLoad = ['form', 'fluid_styled_content'];
    protected array $testExtensionsToLoad = [
        'typo3/sysext/form/Tests/Functional/Fixtures/Extensions/form_caching_tests',
    ];
    protected array $configurationToUseInTestInstance = [
        'MAIL' => [
            'defaultMailFromAddress' => 'hello@typo3.org',
            'defaultMailFromName' => 'TYPO3',
            'transport' => 'mbox',
            'transport_spool_type' => 'file',
            'transport_spool_filepath' => self::MAIL_SPOOL_FOLDER,
        ],
        'SYS' => [
            'caching' => [
                'cacheConfigurations' => [
                    'hash' => [
                        'backend' => Typo3DatabaseBackend::class,
                        'frontend' => VariableFrontend::class,
                    ],
                    'pages' => [
                        'backend' => Typo3DatabaseBackend::class,
                        'frontend' => VariableFrontend::class,
                    ],
                    'rootline' => [
                        'backend' => Typo3DatabaseBackend::class,
                        'frontend' => VariableFrontend::class,
                    ],
                ],
            ],
            'encryptionKey' => '4408d27a916d51e624b69af3554f516dbab61037a9f7b9fd6f81b4d3bedeccb6',
        ],
    ];

    private string $databaseScenarioFile = __DIR__ . '/Fixtures/OnePageWithMultipleFormIntegrationsScenario.yaml';
    private const ROOT_PAGE_BASE_URI = 'http://localhost';
    private const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_GB.UTF8'],
    ];
    private const MAIL_SPOOL_FOLDER = 'typo3temp/var/transient/spool/';

    protected function setUp(): void
    {
        parent::setUp();
        $this->writeSiteConfiguration(
            'site1',
            $this->buildSiteConfiguration(1000, self::ROOT_PAGE_BASE_URI . '/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/'),
            ]
        );
        $this->withDatabaseSnapshot(function () {
            $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
            $backendUser = $this->setUpBackendUser(1);
            $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);
            $factory = DataHandlerFactory::fromYamlFile($this->databaseScenarioFile);
            $writer = DataHandlerWriter::withBackendUser($backendUser);
            $writer->invokeFactory($factory);
            self::failIfArrayIsNotEmpty($writer->getErrors());
        });
    }

    protected function tearDown(): void
    {
        $this->purgeMailSpool();
        parent::tearDown();
    }

    private function getMailSpoolMessages(): array
    {
        $messages = [];
        foreach (array_filter(glob($this->instancePath . '/' . self::MAIL_SPOOL_FOLDER . '*'), 'is_file') as $path) {
            $serializedMessage = file_get_contents($path);
            $sentMessage = unserialize($serializedMessage);
            if (!$sentMessage instanceof SentMessage) {
                continue;
            }
            $fluidEmail = $sentMessage->getOriginalMessage();
            if (!$fluidEmail instanceof FluidEmail) {
                continue;
            }
            $parsedHeaders = $this->parseRawHeaders($sentMessage->toString());
            $item = [
                'plaintext' => $fluidEmail->getTextBody(),
                'html' => $fluidEmail->getHtmlBody(),
                'subject' => $fluidEmail->getSubject(),
                'date' => $fluidEmail->getDate() ?? $parsedHeaders['Date'] ?? null,
                'to' => $fluidEmail->getTo(),
            ];
            if (is_string($item['date'])) {
                // @todo `@timezone` is not handled here - probably tests don't need date at all
                $item['date'] = new \DateTimeImmutable($item['date']);
            }
            $messages[] = $item;
        }
        return $messages;
    }

    /**
     * @return array<string, string>
     */
    private function parseRawHeaders(string $rawMessage): array
    {
        $rawParts = explode("\r\n\r\n", $rawMessage, 2);
        $rawLines = explode("\r\n", $rawParts[0]);
        $rawHeaders = array_map(
            fn(string $rawLine) => array_map(
                'trim',
                explode(':', $rawLine, 2)
            ),
            $rawLines
        );
        return array_combine(
            array_column($rawHeaders, 0),
            array_column($rawHeaders, 1)
        );
    }

    private function purgeMailSpool(): void
    {
        foreach (glob($this->instancePath . '/' . self::MAIL_SPOOL_FOLDER . '*') as $path) {
            unlink($path);
        }
    }

    public static function theCachingBehavesTheSameForAllFormIntegrationVariantsDataProvider(): \Generator
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

    #[DataProvider('theCachingBehavesTheSameForAllFormIntegrationVariantsDataProvider')]
    #[Test]
    public function theCachingBehavesTheSameForAllFormIntegrationVariants(string $formIdentifier, string $formNamePrefix): void
    {
        $uri = static::ROOT_PAGE_BASE_URI . '/form';

        $subject = new FormDataFactory();

        // goto form page
        $internalRequest = (new InternalRequest($uri))->withAttribute('currentContentObject', $this->get(ContentObjectRenderer::class));
        $pageMarkup = (string)$this->executeFrontendSubRequest($internalRequest, null, true)->getBody();
        $formData = $subject->fromHtmlMarkupAndXpath($pageMarkup, '//form[@id="' . $formIdentifier . '"]');

        $honeypotIdFromStep1 = $formData->getHoneypotId();
        $sessionIdFromStep1 = $formData->getSessionId();

        self::assertEmpty($sessionIdFromStep1, 'session element is not rendered');
        self::assertEmpty($formData->toArray()['elementData'][$formNamePrefix . '[' . $formIdentifier . '][text-1]']['value'] ?? '_notempty_', 'form element "text-1" is empty');
        self::assertNotEmpty($honeypotIdFromStep1, 'honeypot element exists');

        // post data and go to summary page
        $internalRequest = (new InternalRequest($uri))->withAttribute('currentContentObject', $this->get(ContentObjectRenderer::class));
        $formPostRequest = $formData->with('text-1', 'FOObarBAZ')->toPostRequest($internalRequest);
        $pageMarkup = (string)$this->executeFrontendSubRequest($formPostRequest, null, true)->getBody();
        $formData = $subject->fromHtmlMarkupAndXpath($pageMarkup, '//form[@id="' . $formIdentifier . '"]');

        $honeypotIdFromStep2 = $formData->getHoneypotId();
        $sessionIdFromStep2 = $formData->getSessionId();
        $formMarkup = $formData->getFormMarkup();

        self::assertStringContainsString('Summary step', $formMarkup, 'the summary form step is shown');
        self::assertStringContainsString('FOObarBAZ', $formMarkup, 'data from "text-1" is shown');
        self::assertNotEmpty($sessionIdFromStep2, 'session element is rendered');
        self::assertEmpty($honeypotIdFromStep2, 'honeypot element does not exists on summary form step');

        // go back to first page
        $internalRequest = (new InternalRequest($uri))->withAttribute('currentContentObject', $this->get(ContentObjectRenderer::class));
        $formPostRequest = $formData->with('__currentPage', '0')->toPostRequest($internalRequest);
        $pageMarkup = (string)$this->executeFrontendSubRequest($formPostRequest, null, true)->getBody();
        $formData = $subject->fromHtmlMarkupAndXpath($pageMarkup, '//form[@id="' . $formIdentifier . '"]');

        $honeypotIdFromStep3 = $formData->getHoneypotId();
        $sessionIdFromStep3 = $formData->getSessionId();

        self::assertEquals('FOObarBAZ', $formData->toArray()['elementData'][$formNamePrefix . '[' . $formIdentifier . '][text-1]']['value'] ?? null, 'form element "text-1" contains submitted data');
        self::assertNotEquals($honeypotIdFromStep3, $honeypotIdFromStep1, 'honeypot differs from historical honeypot');
        self::assertEquals($sessionIdFromStep3, $sessionIdFromStep2, 'session is still available');

        // post data and go to summary page
        $internalRequest = (new InternalRequest($uri))->withAttribute('currentContentObject', $this->get(ContentObjectRenderer::class));
        $formPostRequest = $formData->with('text-1', 'BAZbarFOO')->toPostRequest($internalRequest);
        $pageMarkup = (string)$this->executeFrontendSubRequest($formPostRequest, null, true)->getBody();
        $formData = $subject->fromHtmlMarkupAndXpath($pageMarkup, '//form[@id="' . $formIdentifier . '"]');

        $honeypotIdFromStep4 = $formData->getHoneypotId();
        $sessionIdFromStep4 = $formData->getSessionId();
        $formMarkup = $formData->getFormMarkup();

        self::assertStringContainsString('Summary step', $formMarkup, 'the summary form step is shown');
        self::assertStringContainsString('BAZbarFOO', $formMarkup, 'data from "text-1" is shown');
        self::assertEmpty($honeypotIdFromStep4, 'honeypot element does not exists on summary form step');
        self::assertEquals($sessionIdFromStep4, $sessionIdFromStep3, 'session is still available');

        // submit and trigger finishers
        $internalRequest = (new InternalRequest($uri))->withAttribute('currentContentObject', $this->get(ContentObjectRenderer::class));
        $formPostRequest = $formData->toPostRequest($internalRequest);
        $pageMarkup = (string)$this->executeFrontendSubRequest($formPostRequest, null, true)->getBody();
        $formData = $subject->fromHtmlMarkupAndXpath($pageMarkup, '//*[@id="' . $formIdentifier . '"]');

        $formMarkup = $formData->getFormMarkup();
        $mails = $this->getMailSpoolMessages();

        self::assertStringContainsString('Form is submitted', $formMarkup, 'the finisher text is shown');
        self::assertCount(1, $this->getMailSpoolMessages(), 'a mail is sent');
        self::assertStringContainsString('Text: BAZbarFOO', $mails[0]['plaintext'] ?? '', 'Mail contains form data');
    }

    public static function formRendersUncachedIfTheActionTargetIsCalledViaHttpGetDataProvider(): \Generator
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

    #[DataProvider('formRendersUncachedIfTheActionTargetIsCalledViaHttpGetDataProvider')]
    #[Test]
    public function formRendersUncachedIfTheActionTargetIsCalledViaHttpGet(string $formIdentifier): void
    {
        $uri = static::ROOT_PAGE_BASE_URI . '/form';

        $subject = new FormDataFactory();

        // goto form page
        $internalRequest = (new InternalRequest($uri))->withAttribute('currentContentObject', $this->get(ContentObjectRenderer::class));
        $pageMarkup = (string)$this->executeFrontendSubRequest($internalRequest, null, true)->getBody();
        $formData = $subject->fromHtmlMarkupAndXpath($pageMarkup, '//form[@id="' . $formIdentifier . '"]');

        // goto form target with HTTP GET
        $internalRequest = (new InternalRequest($uri))->withAttribute('currentContentObject', $this->get(ContentObjectRenderer::class));
        (string)$this->executeFrontendSubRequest($formData->toGetRequest($internalRequest, false), null, true)->getBody();

        // goto form page
        $internalRequest = (new InternalRequest($uri))->withAttribute('currentContentObject', $this->get(ContentObjectRenderer::class));
        $pageMarkup = (string)$this->executeFrontendSubRequest($internalRequest, null, true)->getBody();
        $formData = $subject->fromHtmlMarkupAndXpath($pageMarkup, '//form[@id="' . $formIdentifier . '"]');

        // post data and go to summary page
        $internalRequest = (new InternalRequest($uri))->withAttribute('currentContentObject', $this->get(ContentObjectRenderer::class));
        $formPostRequest = $formData->with('text-1', 'FOObarBAZ')->toPostRequest($internalRequest);
        $pageMarkup = (string)$this->executeFrontendSubRequest($formPostRequest, null, true)->getBody();
        $formData = $subject->fromHtmlMarkupAndXpath($pageMarkup, '//form[@id="' . $formIdentifier . '"]');

        $formMarkup = $formData->getFormMarkup();

        self::assertStringContainsString('Summary step', $formMarkup, 'the summary form step is shown');
        self::assertStringContainsString('FOObarBAZ', $formMarkup, 'data from "text-1" is shown');
    }

    public static function theHoneypotElementChangesWithEveryCallOfTheFirstFormStepDataProvider(): \Generator
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

    #[DataProvider('theHoneypotElementChangesWithEveryCallOfTheFirstFormStepDataProvider')]
    #[Test]
    public function theHoneypotElementChangesWithEveryCallOfTheFirstFormStep(string $formIdentifier): void
    {
        $uri = static::ROOT_PAGE_BASE_URI . '/form';

        $subject = new FormDataFactory();

        // goto form page
        $internalRequest = (new InternalRequest($uri))->withAttribute('currentContentObject', $this->get(ContentObjectRenderer::class));
        $pageMarkup = (string)$this->executeFrontendSubRequest($internalRequest, null, true)->getBody();
        $formData = $subject->fromHtmlMarkupAndXpath($pageMarkup, '//form[@id="' . $formIdentifier . '"]');
        $honeypotId = $formData->getHoneypotId();

        self::assertNotEmpty($honeypotId, 'honeypot element exists');

        // revisit form page
        $internalRequest = (new InternalRequest($uri))->withAttribute('currentContentObject', $this->get(ContentObjectRenderer::class));
        $pageMarkup = (string)$this->executeFrontendSubRequest($internalRequest, null, true)->getBody();
        $formData = $subject->fromHtmlMarkupAndXpath($pageMarkup, '//form[@id="' . $formIdentifier . '"]');

        $honeypotIdFromRevisit = $formData->getHoneypotId();

        self::assertNotEquals($honeypotIdFromRevisit, $honeypotId, 'honeypot differs from historical honeypot');
    }
}
