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

namespace TYPO3\CMS\Form\Tests\Functional\Domain\Finishers;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\DependencyInjection\Container;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\EventDispatcher\ListenerProvider;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Mail\FluidEmail;
use TYPO3\CMS\Core\Mail\MailerInterface;
use TYPO3\CMS\Core\Mail\TemplatedEmailFactory;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\TypoScript\AST\Node\RootNode;
use TYPO3\CMS\Core\TypoScript\FrontendTypoScript;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface as ExtbaseConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Mvc\ExtbaseRequestParameters;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Form\Domain\Factory\ArrayFormFactory;
use TYPO3\CMS\Form\Domain\Finishers\EmailFinisher;
use TYPO3\CMS\Form\Domain\Finishers\FinisherContext;
use TYPO3\CMS\Form\Domain\Model\FormDefinition;
use TYPO3\CMS\Form\Domain\Runtime\FormRuntime;
use TYPO3\CMS\Form\Event\BeforeEmailFinisherInitializedEvent;
use TYPO3\CMS\Form\Service\FormValueResolver;
use TYPO3\CMS\Form\Service\TranslationService;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class EmailFinisherTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['form'];

    protected bool $initializeDatabase = false;

    private ArrayFormFactory $formFactory;
    private Request $request;

    protected function setUp(): void
    {
        parent::setUp();
        $frontendTypoScript = new FrontendTypoScript(new RootNode(), [], [], []);
        $frontendTypoScript->setSetupArray([]);

        $feRequest = new ServerRequest()
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE)
            ->withAttribute('frontend.typoscript', $frontendTypoScript)
            ->withAttribute('language', new SiteLanguage(0, 'en_US.UTF-8', new Uri('/'), []));

        $this->get(ExtbaseConfigurationManagerInterface::class)->setRequest($feRequest);
        $this->formFactory = $this->get(ArrayFormFactory::class);
        $this->request = $this->buildExtbaseRequest();
    }

    #[Test]
    public function beforeEmailFinisherInitializedEventIsCalled(): void
    {
        /** @var Container $container */
        $container = $this->get('service_container');

        // Define a TranslationService mock which skips all the translation but simply returns the $optionValue
        // without any further processing.
        $translationServiceStub = self::createStub(TranslationService::class);
        $translationServiceStub->method('translateFinisherOption')->willReturnCallback(static function () {
            return func_get_arg(3);
        });
        $container->set(TranslationService::class, $translationServiceStub);

        // Define the MailerInterface implementation to be able to test the correct subject in the final mail.
        // The method ->send() must be called exactly once and the subject of the passed FluidMail object must match
        // our defined string.
        $mailerMock = $this->createMock(MailerInterface::class);
        $mailerMock->expects($this->once())->method('send')->willReturnCallback(static function (FluidEmail $mail) {
            self::assertEquals('dynamic event subject', $mail->getSubject());
        });
        $container->set(MailerInterface::class, $mailerMock);

        // define a custom event to set "subject" to the defined string
        $beforeEmailFinisherInitializedEvent = null;
        $container->set(
            'before-email-finisher-initialized-event-listener',
            static function (BeforeEmailFinisherInitializedEvent $event) use (&$beforeEmailFinisherInitializedEvent) {
                $beforeEmailFinisherInitializedEvent = $event;
                $options = $event->getOptions();
                $options['subject'] = 'dynamic event subject';
                $event->setOptions($options);
            }
        );
        $eventListener = $this->get(ListenerProvider::class);
        $eventListener->addListener(BeforeEmailFinisherInitializedEvent::class, 'before-email-finisher-initialized-event-listener');

        $subject = new EmailFinisher(
            $this->get(EventDispatcher::class),
            $this->get(TemplatedEmailFactory::class),
            $this->get(MailerInterface::class),
        );
        $subject->injectTranslationService($translationServiceStub);
        $subject->injectFormValueResolver(new FormValueResolver($translationServiceStub));
        $subject->setOptions([
            'senderAddress' => 'sender@example.org',
            'templateName' => 'template',
            'recipients' => ['user@example.org' => 'John Doe'],
            'subject' => 'default subject',
        ]);
        $formRuntimeStub = self::createStub(FormRuntime::class);
        $formRuntimeStub->method('getFormDefinition')->willReturn(new FormDefinition('form'));
        $subject->execute(new FinisherContext($formRuntimeStub, self::createStub(Request::class)));

        self::assertInstanceOf(BeforeEmailFinisherInitializedEvent::class, $beforeEmailFinisherInitializedEvent);
        self::assertEquals([
            'senderAddress' => 'sender@example.org',
            'templateName' => 'template',
            'recipients' => ['user@example.org' => 'John Doe'],
            'subject' => 'dynamic event subject',
        ], $beforeEmailFinisherInitializedEvent->getOptions());
    }

    public static function subjectPlaceholderDataProvider(): array
    {
        return [
            'single select label replacement' => ['Choice: {single-select}', 'Choice: Mister'],
            'multi select label replacement in text' => ['Choices: {multi-select}', 'Choices: Mister, Missis'],
            'multi select placeholder only' => ['{multi-select}', 'Mister, Missis'],
        ];
    }

    #[DataProvider('subjectPlaceholderDataProvider')]
    #[Test]
    public function subjectUsesDisplayValuesFromFormElements(string $subjectTemplate, string $expectedSubject): void
    {
        $this->executeFinisherWithOptions(
            [
                'senderAddress' => 'sender@example.org',
                'templateName' => 'template',
                'recipients' => ['user@example.org' => 'John Doe'],
                'subject' => $subjectTemplate,
            ],
            static function (FluidEmail $mail) use ($expectedSubject): void {
                self::assertSame($expectedSubject, $mail->getSubject());
            }
        );
    }

    #[Test]
    public function emailAddressesKeepTheSubmittedValue(): void
    {
        $this->executeFinisherWithOptions(
            [
                'senderAddress' => '{department}',
                'templateName' => 'template',
                'recipients' => ['{department}' => 'Team'],
                'replyToRecipients' => ['{department}' => 'Team'],
                'subject' => 'Request for {department}',
            ],
            static function (FluidEmail $mail): void {
                self::assertSame('sales@example.org', $mail->getTo()[0]->getAddress());
                self::assertSame('sales@example.org', $mail->getReplyTo()[0]->getAddress());
                self::assertSame('sales@example.org', $mail->getFrom()[0]->getAddress());
                // The very same element reads as its label where a human sees it
                self::assertSame('Request for Sales', $mail->getSubject());
            }
        );
    }

    #[Test]
    public function senderNameUsesTheTranslatedOptionLabel(): void
    {
        $this->executeFinisherWithOptions(
            [
                'senderAddress' => 'sender@example.org',
                'senderName' => '{single-select} Doe',
                'templateName' => 'template',
                'recipients' => ['user@example.org' => 'John Doe'],
                'subject' => 'Subject',
            ],
            static function (FluidEmail $mail): void {
                self::assertSame('Mister Doe', $mail->getFrom()[0]->getName());
            }
        );
    }

    private function executeFinisherWithOptions(array $options, \Closure $assertMail): void
    {
        /** @var Container $container */
        $container = $this->get('service_container');

        $mailerMock = $this->createMock(MailerInterface::class);
        $mailerMock->expects($this->once())->method('send')->willReturnCallback($assertMail);
        $container->set(MailerInterface::class, $mailerMock);

        $translationServiceStub = self::createStub(TranslationService::class);
        $translationServiceStub->method('translateFinisherOption')->willReturnArgument(3);

        $subject = new EmailFinisher(
            $this->get(EventDispatcher::class),
            $this->get(TemplatedEmailFactory::class),
            $this->get(MailerInterface::class),
        );
        $subject->injectTranslationService($translationServiceStub);
        $subject->injectFormValueResolver(new FormValueResolver($translationServiceStub));
        $subject->setOptions($options);
        $subject->execute(new FinisherContext($this->buildFormRuntime(), $this->request));
    }

    private function buildExtbaseRequest(): Request
    {
        $frontendUser = new FrontendUserAuthentication();
        $frontendUser->initializeUserSessionManager();
        $serverRequest = new ServerRequest()
            ->withAttribute('extbase', new ExtbaseRequestParameters())
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE)
            ->withAttribute('frontend.user', $frontendUser)
            ->withAttribute('language', new SiteLanguage(0, 'en_US.UTF-8', new Uri('/'), []));

        $GLOBALS['TYPO3_REQUEST'] = $serverRequest;

        return new Request($serverRequest)->withPluginName('Formframework');
    }

    private function buildFormRuntime(): FormRuntime
    {
        $formDefinition = $this->buildFormDefinition();
        $formRuntime = $formDefinition->bind($this->request);
        $formRuntime->getFormState()->setFormValue('single-select', 'mr');
        $formRuntime->getFormState()->setFormValue('multi-select', ['mr', 'mrs']);
        $formRuntime->getFormState()->setFormValue('department', 'sales@example.org');
        return $formRuntime;
    }

    private function buildFormDefinition(): FormDefinition
    {
        return $this->formFactory->build([
            'type' => 'Form',
            'identifier' => 'test-form',
            'label' => 'Test form',
            'prototypeName' => 'standard',
            'renderables' => [
                [
                    'type' => 'Page',
                    'identifier' => 'page-1',
                    'label' => 'Page 1',
                    'renderables' => [
                        [
                            'type' => 'SingleSelect',
                            'identifier' => 'single-select',
                            'label' => 'Single',
                            'properties' => [
                                'options' => [
                                    'mr' => 'Mister',
                                    'mrs' => 'Missis',
                                ],
                            ],
                        ],
                        [
                            'type' => 'SingleSelect',
                            'identifier' => 'department',
                            'label' => 'Department',
                            'properties' => [
                                'options' => [
                                    'sales@example.org' => 'Sales',
                                    'support@example.org' => 'Support',
                                ],
                            ],
                        ],
                        [
                            'type' => 'MultiSelect',
                            'identifier' => 'multi-select',
                            'label' => 'Multi',
                            'properties' => [
                                'options' => [
                                    'mr' => 'Mister',
                                    'mrs' => 'Missis',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ], null, new ServerRequest());
    }
}
