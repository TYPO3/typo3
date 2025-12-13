..  include:: /Includes.rst.txt

..  _feature-108508-1765987901:

===============================================
Feature: #108508 - Fluid components integration
===============================================

See :issue:`108508`

Description
===========

Fluid 4.3 introduced the concept of components to Fluid (see
`Components <https://docs.typo3.org/permalink/fluid:components>`_). Since then, it
was already possible to use components in TYPO3 projects by creating a custom
:php:`ComponentCollection` class that essentially connects a folder of template files
to a Fluid ViewHelper namespace. Using that class it was also possible to use an
alternative folder structure for a component collection and to allow passing
arbitrary arguments to components within that collection.

Now it is possible to define component collections purely with configuration.
For the most common use cases, it is no longer necessary to create a custom
PHP class, which makes it much easier for integrators to setup components in
TYPO3 projects.

Registering component collections
---------------------------------

The new extension-level configuration file
:file:`Configuration/Fluid/ComponentCollections.php` is introduced, which allows
extensions to register one or multiple new component collections. It is also possible
to extend existing collections registered by other extensions (such as adding template
paths to override components defined by another extension).

Basic example:

..  code-block:: php
    :caption: EXT:my_extension/Configuration/Fluid/ComponentCollections.php

    <?php

    return [
        'MyVendor\\MyExtension\\Components' => [
            'templatePaths' => [
                10 => 'EXT:my_extension/Resources/Private/Components',
            ],
        ],
    ];

Components in that collection can then be used in any Fluid template:

..  code-block:: html

    <html
        xmlns:my="http://typo3.org/ns/MyVendor/MyExtension/Components"
        data-namespace-typo3-fluid="true"
    >

    <my:organism.header.navigation />

By default, component collections use a folder structure that requires a
separate folder per component. This is handy if you want to put other
files right next to your component template, such as the matching CSS
or JS file, or even a custom language file. Using the example above,
:html:`<my:organism.header.navigation />` would point to
:file:`EXT:my_extension/Resources/Private/Components/Organism/Header/Navigation/Navigation.fluid.html`.

If not otherwise specified, components use a strict API, meaning that all
arguments that are passed to a component need to be defined with
:html:`<f:argument>` in the component template.

Both defaults can be adjusted per collection by providing configuration options:

* `templateNamePattern` allows you to use a different folder structure, available
  variables are `{path}` and `{name}`. For :html:`<my:organism.header.navigation>`,
  `{path}` would be `Organism/Header` and `{name}` would be `Navigation`.
* setting `additionalArgumentsAllowed` to `true` allows passing undefined arguments
  to components.

Advanced example:

..  code-block:: php
    :caption: EXT:my_extension/Configuration/Fluid/ComponentCollections.php

    <?php

    return [
        'MyVendor\\MyExtension\\Components' => [
            'templatePaths' => [
                10 => 'EXT:my_extension/Resources/Private/Components',
            ],
            'templateNamePattern' => '{path}/{name}',
            'additionalArgumentsAllowed' => true,
        ],
    ];

Using this example :html:`<my:organism.header.navigation />` would point to
:file:`EXT:my_extension/Resources/Private/Components/Organism/Header/Navigation.fluid.html`
(note the missing :file:`Navigation` folder).

It is possible to influence certain aspects of Fluid components using PSR-14 events,
see :ref:`PSR-14 events for Fluid components <feature-108508-1765987847>`

Creating components
-------------------

A typical component looks just like a normal Fluid template, except that it defines
all of its arguments with the
`Argument ViewHelper <f:argument> <https://docs.typo3.org/permalink/t3viewhelper:typo3fluid-fluid-argument>`_.
Also, the `Slot ViewHelper <f:slot> <https://docs.typo3.org/permalink/t3viewhelper:typo3fluid-fluid-slot>`_
can be used to receive HTML content.

Example:

..  code-block:: html
    :caption: EXT:my_extension/Resources/Private/Components/Molecule/TeaserCard/TeaserCard.html

    <html
        xmlns:my="http://typo3.org/ns/MyVendor/MyExtension/Components"
        data-namespace-typo3-fluid="true"
    >

    <f:argument name="title" type="string" />
    <f:argument name="link" type="string" />
    <f:argument name="icon" type="string" optional="{true}" />

    <a href="{link}" class="teaserCard">
        <f:if condition="{icon}">
            <my:atom.icon identifier="{icon}">
        </f:if>
        <div class="teaserCard__title">{title}</div>
        <div class="teaserCard__content"><f:slot /></div>
    </a>

The example also demonstrates that components can (and should) use other components, in this
case :html:`<my:atom.icon>`.
Depending on the use case, it might also make sense to pass the output of one component
to another component via a slot:

..  code-block:: html

    <html
        xmlns:my="http://typo3.org/ns/MyVendor/MyExtension/Components"
        data-namespace-typo3-fluid="true"
    >

    <my:molecule.teaserCard
        title="TYPO3"
        link="https://typo3.org/"
        icon="typo3"
    >
        <my:atom.text>{content}</my:atom.text>
    </my:molecule.teaserCard>

You can learn more about components in
`Defining Components <https://docs.typo3.org/permalink/fluid:components-definition>`_. Note
that this is part of the documentation of Fluid Standalone, which means that it doesn't mention
TYPO3 specifics.

Migration and co-existence with class-based collections
-------------------------------------------------------

Configuration-based and class-based component collections can be used side by side.
For more advanced use cases, it might still be best to ship a custom class to define
a component collection. However, most use cases can easily be migrated to the
configuration-based approach, since they usually just consist of boilerplate code
around the configuration options.

Since the new approach is not available in TYPO3 13, it is possible to ship both
variants to provide backwards-compatibility: If a specific component collection is
defined both via class and via configuration, in TYPO3 13 the class will be used,
while in TYPO3 14 the configuration will be used and the class will be ignored completely.

Extending component collections from other extensions
-----------------------------------------------------

It is possible to extend the configuration of other extensions using the
introduced configuration file. This allows integrators to merge their own set of
components into an existing component collection:

..  code-block:: php
    :caption: EXT:vendor_extension/Configuration/Fluid/ComponentCollections.php

    <?php

    return [
        'SomeVendor\\VendorExtension\\Components' => [
            'templatePaths' => [
                10 => 'EXT:vendor_extension/Resources/Private/Components',
            ],
        ],
    ];

..  code-block:: php
    :caption: EXT:my_extension/Configuration/Fluid/ComponentCollections.php

    <?php

    return [
        'SomeVendor\\VendorExtension\\Components' => [
            'templatePaths' => [
                1765990741 => 'EXT:my_extension/Resources/Private/Extensions/VendorExtension/Components',
            ],
        ],
    ];

For template paths, the familiar rule applies: They will be sorted by their
keys and will be processed in reverse order. In this example, if `my_extension`
defines a component that already exists in `vendor_extension`, it will override
the original component in `vendor_extension`.

Impact
======

Fluid component collections no longer need to be defined by creating a custom
class, but can now be registered purely by configuration. Existing class-based
collections will continue to work. If a collection namespace is registered both
by a class and by configuration, the configuration overrules the class and any
custom code in the class is ignored.

..  index:: Fluid, ext:fluid
