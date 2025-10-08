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

namespace TYPO3\CMS\Form\Tests\Functional\Domain\Renderer;

use PHPUnit\Framework\Attributes\Test;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\EventDispatcher\ListenerProvider;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\View\ViewFactoryInterface;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Mvc\ExtbaseRequestParameters;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Form\Domain\Factory\ArrayFormFactory;
use TYPO3\CMS\Form\Domain\Model\FormDefinition;
use TYPO3\CMS\Form\Domain\Renderer\FluidFormRenderer;
use TYPO3\CMS\Form\Event\BeforeRenderableIsRenderedEvent;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class FluidFormRendererTest extends FunctionalTestCase
{
    protected const BEFORE_RENDERABLE_IS_RENDERED_LISTENER_KEY = 'before-renderable-is-rendered-listener';

    protected array $coreExtensionsToLoad = ['form'];

    #[Test]
    public function beforeRenderableIsRenderedEventIsTriggered(): void
    {
        $expectedLabel = 'foo';

        $container = $this->get('service_container');
        $state = [
            self::BEFORE_RENDERABLE_IS_RENDERED_LISTENER_KEY => null,
        ];
        $container->set(
            self::BEFORE_RENDERABLE_IS_RENDERED_LISTENER_KEY,
            static function (BeforeRenderableIsRenderedEvent $event) use (&$state, $expectedLabel): void {
                if (!($event->renderable instanceof FormDefinition)) {
                    return;
                }
                $state[self::BEFORE_RENDERABLE_IS_RENDERED_LISTENER_KEY] = $event;
                $event->renderable->setLabel($expectedLabel);
            }
        );
        $eventListener = $container->get(ListenerProvider::class);
        $eventListener->addListener(BeforeRenderableIsRenderedEvent::class, self::BEFORE_RENDERABLE_IS_RENDERED_LISTENER_KEY);

        // Init ConfigurationManagerInterface stateful singleton, usually done by extbase bootstrap
        $this->get(ConfigurationManagerInterface::class)->setRequest(
            (new ServerRequest())->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
        );
        $definition = $this->buildFormDefinition();
        $runtime = $definition->bind($this->buildExtbaseRequest());

        $subject = new FluidFormRenderer($this->createMock(ViewFactoryInterface::class), $this->get(EventDispatcherInterface::class));
        $subject->setFormRuntime($runtime);
        $subject->render();

        self::assertInstanceOf(BeforeRenderableIsRenderedEvent::class, $state[self::BEFORE_RENDERABLE_IS_RENDERED_LISTENER_KEY]);
        if (!($state[self::BEFORE_RENDERABLE_IS_RENDERED_LISTENER_KEY]->renderable instanceof FormDefinition)) {
            self::fail('Renderable is not an instance of FormDefinition');
        }
        self::assertEquals($expectedLabel, $state[self::BEFORE_RENDERABLE_IS_RENDERED_LISTENER_KEY]->renderable->getLabel());
        self::assertEquals($expectedLabel, $subject->getFormRuntime()->getFormDefinition()->getLabel());
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
