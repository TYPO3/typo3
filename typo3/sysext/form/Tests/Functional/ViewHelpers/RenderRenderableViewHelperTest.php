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

namespace TYPO3\CMS\Form\Tests\Functional\ViewHelpers;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\EventDispatcher\ListenerProvider;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface as ExtbaseConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Mvc\ExtbaseRequestParameters;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextFactory;
use TYPO3\CMS\Form\Domain\Factory\ArrayFormFactory;
use TYPO3\CMS\Form\Domain\Model\FormDefinition;
use TYPO3\CMS\Form\Domain\Model\FormElements\GenericFormElement;
use TYPO3\CMS\Form\Event\BeforeRenderableIsRenderedEvent;
use TYPO3\CMS\Form\ViewHelpers\RenderRenderableViewHelper;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Fluid\Fluid\View\TemplateView;

final class RenderRenderableViewHelperTest extends FunctionalTestCase
{
    protected const BEFORE_RENDERABLE_IS_RENDERED_LISTENER_KEY = 'before-renderable-is-rendered-listener';

    protected array $coreExtensionsToLoad = ['form'];

    #[Test]
    public function beforeRenderableIsRenderedEventIsTriggered(): void
    {
        $expectedLabel = 'foo';
        $expectedValue = 'value';

        $container = $this->get('service_container');
        $state = [
            self::BEFORE_RENDERABLE_IS_RENDERED_LISTENER_KEY => null,
        ];
        $container->set(
            self::BEFORE_RENDERABLE_IS_RENDERED_LISTENER_KEY,
            static function (BeforeRenderableIsRenderedEvent $event) use (&$state, $expectedLabel, $expectedValue): void {
                $state[self::BEFORE_RENDERABLE_IS_RENDERED_LISTENER_KEY] = $event;
                if (!($event->renderable instanceof GenericFormElement)) {
                    return;
                }
                $event->renderable->setLabel($expectedLabel);
                $event->formRuntime[$event->renderable->getIdentifier()] = $expectedValue;
            }
        );
        $eventListener = $container->get(ListenerProvider::class);
        $eventListener->addListener(BeforeRenderableIsRenderedEvent::class, self::BEFORE_RENDERABLE_IS_RENDERED_LISTENER_KEY);

        // Init ConfigurationManagerInterface stateful singleton, usually done by extbase bootstrap
        $this->get(ExtbaseConfigurationManagerInterface::class)->setRequest(
            (new ServerRequest())->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
        );
        $definition = $this->buildFormDefinition();
        $runtime = $definition->bind($this->buildExtbaseRequest());

        $element = $definition->getElementByIdentifier('text-1');

        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getVariableProvider()->add('element', $element);
        $context->getViewHelperVariableContainer()
            ->add(RenderRenderableViewHelper::class, 'formRuntime', $runtime);
        $template = '<formvh:renderRenderable renderable="{element}">{element.label}</formvh:renderRenderable>';
        $context->getTemplatePaths()->setTemplateSource($template);

        self::assertStringContainsString($expectedLabel, (new TemplateView($context))->render());
        self::assertInstanceOf(BeforeRenderableIsRenderedEvent::class, $state[self::BEFORE_RENDERABLE_IS_RENDERED_LISTENER_KEY]);
        self::assertEquals($expectedLabel, $state[self::BEFORE_RENDERABLE_IS_RENDERED_LISTENER_KEY]->renderable->getLabel());
        self::assertEquals($expectedValue, $state[self::BEFORE_RENDERABLE_IS_RENDERED_LISTENER_KEY]->formRuntime[$state[self::BEFORE_RENDERABLE_IS_RENDERED_LISTENER_KEY]->renderable->getIdentifier()]);
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
        return $this->get(ArrayFormFactory::class)->build([
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
                            'defaultValue' => 'element value',
                        ],
                    ],
                ],
            ],
        ], null, new ServerRequest());
    }
}
