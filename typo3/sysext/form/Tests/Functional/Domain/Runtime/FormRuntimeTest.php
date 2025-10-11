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

namespace TYPO3\CMS\Form\Tests\Functional\Domain\Runtime;

use PHPUnit\Framework\Attributes\Test;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Crypto\HashService;
use TYPO3\CMS\Core\EventDispatcher\ListenerProvider;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface as ExtbaseConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Mvc\ExtbaseRequestParameters;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Extbase\Validation\ValidatorResolver;
use TYPO3\CMS\Form\Domain\Exception\RenderingException;
use TYPO3\CMS\Form\Domain\Factory\ArrayFormFactory;
use TYPO3\CMS\Form\Domain\Model\FormDefinition;
use TYPO3\CMS\Form\Domain\Model\FormElements\GenericFormElement;
use TYPO3\CMS\Form\Domain\Model\FormElements\Page;
use TYPO3\CMS\Form\Domain\Runtime\FormRuntime;
use TYPO3\CMS\Form\Event\AfterCurrentPageIsResolvedEvent;
use TYPO3\CMS\Form\Event\BeforeRenderableIsValidatedEvent;
use TYPO3\CMS\Form\Mvc\Configuration\ConfigurationManagerInterface as ExtFormConfigurationManagerInterface;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class FormRuntimeTest extends FunctionalTestCase
{
    public const AFTER_CURRENT_PAGE_IS_RESOLVED_LISTENER_KEY = 'after-current-page-is-resolved-listener';
    public const BEFORE_RENDERABLE_IS_VALIDATED_LISTENER_KEY = 'before-renderable-is-validated-listener';

    protected array $coreExtensionsToLoad = [
        'form',
    ];

    protected ArrayFormFactory $formFactory;
    protected Request $request;

    protected function setUp(): void
    {
        parent::setUp();
        $this->loadDefaultYamlConfigurations();
        $this->formFactory = $this->get(ArrayFormFactory::class);
        $this->request = $this->buildExtbaseRequest();
    }

    #[Test]
    public function renderThrowsExceptionIfFormDefinitionReturnsNoRendererClassName(): void
    {
        $formDefinition = $this->buildFormDefinition();
        $formDefinition->setOptions(['rendererClassName' => '']);
        $formRuntime = $formDefinition->bind($this->request);

        $this->expectException(RenderingException::class);
        $this->expectExceptionCode(1326095912);

        $formRuntime->render();
    }

    #[Test]
    public function renderThrowsExceptionIfRendererClassNameInstanceDoesNotImplementRendererInterface(): void
    {
        $formDefinition = $this->buildFormDefinition();
        // This must be a class available in the container without implementing RendererInterface
        $formDefinition->setOptions(['rendererClassName' => ExtFormConfigurationManagerInterface::class]);
        $formRuntime = $formDefinition->bind($this->request);

        $this->expectException(RenderingException::class);
        $this->expectExceptionCode(1326096024);

        $formRuntime->render();
    }

    #[Test]
    public function afterCurrentPageIsResolvedEventIsTriggered(): void
    {
        $container = $this->get('service_container');
        $state = [
            self::AFTER_CURRENT_PAGE_IS_RESOLVED_LISTENER_KEY => null,
        ];
        $container->set(
            self::AFTER_CURRENT_PAGE_IS_RESOLVED_LISTENER_KEY,
            static function (AfterCurrentPageIsResolvedEvent $event) use (&$state): void {
                $state[self::AFTER_CURRENT_PAGE_IS_RESOLVED_LISTENER_KEY] = $event;
                $event->currentPage = null;
            }
        );

        $eventListener = $container->get(ListenerProvider::class);
        $eventListener->addListener(AfterCurrentPageIsResolvedEvent::class, self::AFTER_CURRENT_PAGE_IS_RESOLVED_LISTENER_KEY);

        $formDefinition = $this->buildFormDefinition();
        $formRuntime = $formDefinition->bind($this->request);
        $formRuntime->render();

        self::assertInstanceOf(AfterCurrentPageIsResolvedEvent::class, $state[self::AFTER_CURRENT_PAGE_IS_RESOLVED_LISTENER_KEY]);
        self::assertNull($formRuntime->getCurrentPage());
    }

    #[Test]
    public function beforeRenderableIsValidatedEventIsTriggered(): void
    {
        $container = $this->get('service_container');
        $state = [
            self::BEFORE_RENDERABLE_IS_VALIDATED_LISTENER_KEY => null,
        ];
        $expectedValue = 'foo';
        $container->set(
            self::BEFORE_RENDERABLE_IS_VALIDATED_LISTENER_KEY,
            static function (BeforeRenderableIsValidatedEvent $event) use (&$state, $expectedValue): void {
                $state[self::BEFORE_RENDERABLE_IS_VALIDATED_LISTENER_KEY] = $event;
                if ($event->renderable->getIdentifier() !== 'text-1') {
                    return;
                }
                $event->value = $expectedValue;
            }
        );

        $eventListener = $container->get(ListenerProvider::class);
        $eventListener->addListener(BeforeRenderableIsValidatedEvent::class, self::BEFORE_RENDERABLE_IS_VALIDATED_LISTENER_KEY);

        $subject = $this->getAccessibleMock(FormRuntime::class, null, [
            $container,
            $this->createMock(ExtbaseConfigurationManagerInterface::class),
            new HashService(),
            $this->createMock(ValidatorResolver::class),
            $this->createMock(Context::class),
            $this->get(EventDispatcherInterface::class),
        ]);
        $page = new Page('page-1');
        $page->addElement(new GenericFormElement('text-1', 'Text'));

        $subject->setRequest($this->request);
        $subject->setFormDefinition($this->buildFormDefinition());

        $subject->_call(
            'initializeFormStateFromRequest',
        );
        $subject->_call(
            'mapAndValidatePage',
            $page,
        );

        self::assertInstanceOf(BeforeRenderableIsValidatedEvent::class, $state[self::BEFORE_RENDERABLE_IS_VALIDATED_LISTENER_KEY]);
        self::assertEquals($expectedValue, $subject->getElementValue('text-1'));
    }

    private function buildExtbaseRequest(): Request
    {
        $frontendUser = new FrontendUserAuthentication();
        $frontendUser->initializeUserSessionManager();
        $serverRequest = (new ServerRequest())
            ->withAttribute('extbase', new ExtbaseRequestParameters())
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE)
            ->withAttribute('frontend.user', $frontendUser);

        $GLOBALS['TYPO3_REQUEST'] = $serverRequest;

        return (new Request($serverRequest))->withPluginName('Formframework');
    }

    private function buildFormDefinition(): FormDefinition
    {
        return $this->formFactory->build([
            'type' => 'Form',
            'identifier' => 'test',
            'label' => 'test',
            'prototypeName' => 'standard',
            'renderables' => [
                [
                    'type' => 'Page',
                    'identifier' => 'page-1',
                    'label' => 'Page',
                    'renderables' => [
                        [
                            'type' => 'Text',
                            'identifier' => 'text-1',
                            'label' => 'Text',
                        ],
                    ],
                ],
            ],
        ], null, new ServerRequest());
    }

    private function loadDefaultYamlConfigurations(): void
    {
        $configurationManager = $this->get(ExtbaseConfigurationManagerInterface::class);
        $configurationManager->setRequest(
            (new ServerRequest())->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
        );
        $configurationManager->setConfiguration([
            'plugin.' => [
                'tx_form.' => [
                    'settings.' => [
                        'yamlConfigurations.' => [
                            '10' => 'EXT:form/Configuration/Yaml/FormSetup.yaml',
                        ],
                    ],
                ],
            ],
        ]);
    }
}
