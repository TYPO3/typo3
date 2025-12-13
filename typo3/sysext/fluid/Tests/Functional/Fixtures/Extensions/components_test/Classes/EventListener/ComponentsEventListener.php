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

namespace TYPO3Tests\ComponentsTest\EventListener;

use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Fluid\Event\ModifyComponentDefinitionEvent;
use TYPO3\CMS\Fluid\Event\ProvideStaticVariablesToComponentEvent;
use TYPO3\CMS\Fluid\Event\RenderComponentEvent;
use TYPO3Fluid\Fluid\Core\Component\ComponentDefinition;
use TYPO3Fluid\Fluid\Core\ViewHelper\ArgumentDefinition;

final readonly class ComponentsEventListener
{
    #[AsEventListener]
    public function modifyComponentDefinition(ModifyComponentDefinitionEvent $event): void
    {
        // Add required argument to component
        if (
            $event->getNamespace() === 'TYPO3Tests\\ComponentsTest\\Components' &&
            $event->getComponentDefinition()->getName() === 'modifiedComponent'
        ) {
            $originalDefinition = $event->getComponentDefinition();
            $event->setComponentDefinition(new ComponentDefinition(
                $originalDefinition->getName(),
                [
                    ...$originalDefinition->getArgumentDefinitions(),
                    'addedArgument' => new ArgumentDefinition('addedArgument', 'string', '', true),
                ],
                $originalDefinition->additionalArgumentsAllowed(),
                $originalDefinition->getAvailableSlots(),
            ));
        }
    }

    #[AsEventListener]
    public function provideStaticVariablesToComponent(ProvideStaticVariablesToComponentEvent $event): void
    {
        // Provide static variable to component template
        if (
            $event->getComponentCollection()->getNamespace() === 'TYPO3Tests\\ComponentsTest\\Components' &&
            $event->getViewHelperName() === 'staticVariables'
        ) {
            $event->setStaticVariables([
                ...$event->getStaticVariables(),
                'staticVariable' => 'foo',
            ]);
        }
    }

    #[AsEventListener]
    public function renderComponent(RenderComponentEvent $event): void
    {
        // Replace component rendering completely
        if (
            $event->getComponentCollection()->getNamespace() === 'TYPO3Tests\\ComponentsTest\\Components' &&
            $event->getViewHelperName() === 'alternativeRenderer'
        ) {
            $event->setRenderedComponent(json_encode([
                'arguments' => $event->getArguments(),
                'slots' => array_map(fn(\Closure $slot) => $slot($event->getParentRenderingContext()), $event->getSlots()),
                'requestAttribute' => $event->getRequest()?->getAttribute('exampleRequestAttribute'),
            ]));
        }

        // Provide additional data for default rendering
        if (
            $event->getComponentCollection()->getNamespace() === 'TYPO3Tests\\ComponentsTest\\Components' &&
            $event->getViewHelperName() === 'extendedRenderer'
        ) {
            $event->setArguments([
                ...$event->getArguments(),
                'argumentFromEvent' => 'foo',
            ]);
            $event->setSlots([
                ...$event->getSlots(),
                'slotFromEvent' => fn() => '<b>bar</b>',
            ]);
        }
    }
}
