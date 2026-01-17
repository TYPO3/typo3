..  include:: /Includes.rst.txt

..  _feature-108508-1765987847:

=====================================================
Feature: #108508 - PSR-14 events for Fluid components
=====================================================

See :issue:`108508`

Description
===========

Three PSR-14 events have been added to influence the processing and rendering
of Fluid components that are registered using the new configuration file
(see :ref:`Fluid components integration <feature-108508-1765987901>`).

ModifyComponentDefinitionEvent
------------------------------

The :php-short:`\TYPO3\CMS\Fluid\Event\ModifyComponentDefinitionEvent` can be
used to modify the definition of a component before it's written to the cache.
Component definitions must not have any dependencies on runtime information, as
they might be used for static analysis or IDE auto-completion. Due
to the component definitions cache, this is already enforced, as the registered
events are only executed once and not on every request.

Example:

..  code-block:: php
    :caption: EXT:my_extension/Classes/EventListener/ModifyComponentDefinitionListener.php

    <?php

    declare(strict_types=1);

    namespace MyVendor\MyExtension\EventListener;

    use TYPO3\CMS\Core\Attribute\AsEventListener;
    use TYPO3\CMS\Fluid\Event\ModifyComponentDefinitionEvent;
    use TYPO3Fluid\Fluid\Core\Component\ComponentDefinition;
    use TYPO3Fluid\Fluid\Core\ViewHelper\ArgumentDefinition;

    #[AsEventListener]
    final readonly class ModifyComponentDefinitionListener
    {
        public function __invoke(ModifyComponentDefinitionEvent $event): void
        {
            // Add required argument to one specific component
            if (
                $event->getNamespace() === 'MyVendor\\MyExtension\\Components' &&
                $event->getComponentDefinition()->getName() === 'myComponent'
            ) {
                $originalDefinition = $event->getComponentDefinition();
                $event->setComponentDefinition(new ComponentDefinition(
                    $originalDefinition->getName(),
                    [
                        ...$originalDefinition->getArgumentDefinitions(),
                        'myArgument' => new ArgumentDefinition('myArgument', 'string', '', true),
                    ],
                    $originalDefinition->additionalArgumentsAllowed(),
                    $originalDefinition->getAvailableSlots(),
                ));
            }
        }
    }


ProvideStaticVariablesToComponentEvent
--------------------------------------

The :php-short:`\TYPO3\CMS\Fluid\Event\ProvideStaticVariablesToComponentEvent` can
be used to inject additional static variables into component templates. As with the
:php-short:`\TYPO3\CMS\Fluid\Event\ModifyComponentDefinitionEvent`, these variables
must not have any dependencies on runtime information, as they might be used for
static analysis or IDE auto-completion. The
:php-short:`\TYPO3\CMS\Fluid\Event\RenderComponentEvent` can be used to add variables
with runtime dependencies.

Valid use cases for this event might be:

*   providing static (!) design tokens (colors, icons, ...) to all components in a collection
*   generating prefix strings based on the component's name

Example:

..  code-block:: php
    :caption: EXT:my_extension/Classes/EventListener/ProvideStaticVariablesToComponentListener.php

    <?php

    declare(strict_types=1);

    namespace MyVendor\MyExtension\EventListener;

    use TYPO3\CMS\Core\Attribute\AsEventListener;
    use TYPO3\CMS\Fluid\Event\ProvideStaticVariablesToComponentEvent;

    #[AsEventListener]
    final readonly class ProvideStaticVariablesToComponentListener
    {
        public function __invoke(ProvideStaticVariablesToComponentEvent $event): void
        {
            // Provide design tokens to all components in a collection
            if ($event->getComponentCollection()->getNamespace() === 'MyVendor\\MyExtension\\Components') {
                $event->setStaticVariables([
                    ...$event->getStaticVariables(),
                    'designTokens' => [
                        'color1' => '#abcdef',
                        'color2' => '#123456',
                    ],
                ]);
            }
        }
    }


RenderComponentEvent
--------------------

The :php-short:`\TYPO3\CMS\Fluid\Event\RenderComponentEvent` can be used to alter or
replace the rendering of Fluid components. There are three possible use cases:

1.  fully take over the rendering of components by filling the :php:`$renderedContent` with
    :php:`$event->setRenderedContent()`. The first event that does this skips all following
    event listeners.
2.  provide additional arguments (= variables in the component template) or slots to
    the component with :php:`$event->setArguments()`/:php:`$event->setSlots()`.
3.  execute additional code that doesn't influence the component rendering directly, e. g.
    adding certain frontend assets to the page automatically.

Example:

..  code-block:: php
    :caption: EXT:my_extension/Classes/EventListener/RenderComponentListener.php

    <?php

    declare(strict_types=1);

    namespace MyVendor\MyExtension\EventListener;

    use TYPO3\CMS\Core\Attribute\AsEventListener;
    use TYPO3\CMS\Core\Page\AssetCollector;
    use TYPO3\CMS\Fluid\Event\RenderComponentEvent;

    #[AsEventListener]
    final readonly class RenderComponentListener
    {
        public function __construct(private AssetCollector $assetCollector) {}

        public function __invoke(RenderComponentEvent $event): void
        {
            // Add bundled components CSS if a component is used on the page
            if ($event->getComponentCollection()->getNamespace() === 'MyVendor\\MyExtension\\Components') {
                $this->assetCollector->addStyleSheet(
                    'componentsBundle',
                    'EXT:my_extension/Resources/Public/ComponentsBundle.css'
                );
            }
        }
    }


Impact
======

Three new PSR-14 events can be used to influence the processing and rendering
of Fluid components.

..  index:: Fluid, ext:fluid
