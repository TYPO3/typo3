..  include:: /Includes.rst.txt


..  _apireference-frontendrendering:

============================
Building and rendering forms
============================

This chapter explains how EXT:form renders forms in the frontend and how
developers can build forms programmatically or customize the rendering
pipeline.

For the complete PHP API of every class mentioned here, see
`EXT:form API on api.typo3.org <https://api.typo3.org/main/namespaces/typo3-cms-form.html>`__.

..  contents::
    :local:
    :depth: 2


..  _apireference-frontendrendering-fluidformrenderer:

Template resolution (FluidFormRenderer)
========================================

The :php-short:`\TYPO3\CMS\Form\Domain\Renderer\FluidFormRenderer` resolves
Fluid templates, layouts and partials through rendering options defined in the
prototype configuration. All options are read from
:php-short:`\TYPO3\CMS\Form\Domain\Model\FormDefinition::getRenderingOptions()`.

..  _apireference-frontendrendering-fluidformrenderer-options:

..  _apireference-frontendrendering-fluidformrenderer-options-templaterootpaths:

templateRootPaths
-----------------

Defines one or more paths to Fluid **templates**.
Paths are searched in reverse order (bottom to top); the first match wins.

Only the root form element (type :yaml:`Form`) must be a **template** file.
All child elements are resolved as **partials**.

..  literalinclude:: _templateRootPaths.yaml
    :caption: EXT:my_sitepackage/Configuration/Form/CustomPrototype.yaml
    :language: yaml

With the default type :yaml:`Form` the renderer expects a file named
:file:`Form.html` inside the first matching path.


..  _apireference-frontendrendering-fluidformrenderer-options-layoutrootpaths:

layoutRootPaths
---------------

Defines one or more paths to Fluid **layouts**, searched in reverse order.

..  literalinclude:: _layoutRootPaths.yaml
    :caption: EXT:my_sitepackage/Configuration/Form/CustomPrototype.yaml
    :language: yaml


..  _apireference-frontendrendering-fluidformrenderer-options-partialrootpaths:

partialRootPaths
----------------

Defines one or more paths to Fluid **partials**, searched in reverse order.

Within these paths the renderer looks for a file named after the
form element type (e.g. :file:`Text.html` for a :yaml:`Text` element).
Use :ref:`templateName <apireference-frontendrendering-fluidformrenderer-options-templatename>`
to override this convention.

..  literalinclude:: _partialRootPaths.yaml
    :caption: EXT:my_sitepackage/Configuration/Form/CustomPrototype.yaml
    :language: yaml


..  _apireference-frontendrendering-fluidformrenderer-options-templatename:

templateName
------------

By default the element type is used as the partial file name
(e.g. type :yaml:`Text` → :file:`Text.html`).
Set :yaml:`templateName` to use a different file instead:

..  literalinclude:: _templateName.yaml
    :caption: EXT:my_sitepackage/Configuration/Form/CustomPrototype.yaml
    :language: yaml

The element of type :yaml:`Foo` now renders using :file:`Text.html`.


..  _apireference-frontendrendering-renderviewHelper:

The render ViewHelper
=====================

..  _apireference-frontendrendering-renderviewHelper-arguments:

Use :html:`<formvh:render>` in a Fluid template to render a form.
The ViewHelper accepts the following arguments:


..  _apireference-frontendrendering-renderviewHelper-persistenceidentifier:

persistenceIdentifier
---------------------

Path to a YAML form definition. This is the most common way to render a
form:

..  literalinclude:: _renderPersistenceIdentifier.html
    :caption: EXT:my_sitepackage/Resources/Private/Templates/ContactPage.html
    :language: html


..  _apireference-frontendrendering-renderviewHelper-overrideconfiguration:

overrideConfiguration
---------------------

A configuration array that is merged **on top** of the loaded form
definition (or passed directly to the factory when no
:yaml:`persistenceIdentifier` is given).
This allows adjusting a form per usage without duplicating the YAML file.


..  _apireference-frontendrendering-renderviewHelper-factoryclass:

factoryClass
------------

A fully qualified class name implementing
:php-short:`\TYPO3\CMS\Form\Domain\Factory\FormFactoryInterface`.
Defaults to :php-short:`\TYPO3\CMS\Form\Domain\Factory\ArrayFormFactory`.
Set a custom factory to :ref:`build forms programmatically <apireference-frontendrendering-programmatically>`.

..  literalinclude:: _renderFactoryClass.html
    :caption: EXT:my_sitepackage/Resources/Private/Templates/ContactPage.html
    :language: html


..  _apireference-frontendrendering-renderviewHelper-prototypename:

prototypeName
-------------

Name of the prototype the factory should use (e.g. :yaml:`standard`).
If omitted the framework looks for the prototype name inside the form
definition; if none is found, :yaml:`standard` is used.


..  _apireference-frontendrendering-programmatically:

Building forms programmatically
===============================

Instead of writing YAML, you can create a form entirely in PHP by
implementing a custom :php:`FormFactory`.

..  rst-class:: bignums-xxl

1.  Create a FormFactory

    Extend :php:`AbstractFormFactory` and implement :php:`build()`.
    Use :php:`FormDefinition::createPage()` to add pages,
    :php:`Page::createElement()` to add elements, and
    :php:`FormDefinition::createFinisher()` to attach finishers.

    ..  literalinclude:: _CustomFormFactory.php
        :caption: EXT:my_sitepackage/Classes/Domain/Factory/CustomFormFactory.php
        :language: php

2.  Render the form

    Reference your factory in a Fluid template:

    ..  literalinclude:: _renderFactoryClass.html
        :caption: EXT:my_sitepackage/Resources/Private/Templates/ContactPage.html
        :language: html


..  _apireference-frontendrendering-programmatically-key-concepts:

Key classes and their responsibilities
--------------------------------------

..  _apireference-frontendrendering-programmatically-apimethods-formruntime:

The following table lists the most important classes you work with when
building or manipulating forms programmatically. Use your IDE's
autocompletion or the
`API documentation <https://api.typo3.org/main/namespaces/typo3-cms-form.html>`__
for the full method reference.

..  list-table::
    :header-rows: 1
    :widths: 30 70

    *   -   Class
        -   Purpose

    *   -   :php-short:`\TYPO3\CMS\Form\Domain\Model\FormDefinition`
        -   The complete form model. Create pages (:php:`createPage()`),
            attach finishers (:php:`createFinisher()`), look up elements
            (:php:`getElementByIdentifier()`), and bind to a request
            (:php:`bind()`).

    *   -   :php-short:`\TYPO3\CMS\Form\Domain\Runtime\FormRuntime`
        -   A *bound* form instance (created by :php:`FormDefinition::bind()`).
            Provides access to the current page, submitted values
            (:php:`getElementValue()`), and the request/response objects.
            This is the object available inside finishers and event listeners.

    *   -   :php-short:`\TYPO3\CMS\Form\Domain\Model\FormElements\Page`
        -   One page of a multi-step form. Add elements with
            :php:`createElement()`, reorder them with :php:`moveElementBefore()`
            / :php:`moveElementAfter()`.

    *   -   :php-short:`\TYPO3\CMS\Form\Domain\Model\FormElements\Section`
        -   A grouping element inside a page. Same API as :php:`Page` for
            managing child elements.

    *   -   :php-short:`\TYPO3\CMS\Form\Domain\Model\FormElements\AbstractFormElement`
        -   Base class of all concrete elements. Most element types use
            :php-short:`\TYPO3\CMS\Form\Domain\Model\FormElements\GenericFormElement`;
            specialized subclasses include
            :php-short:`\TYPO3\CMS\Form\Domain\Model\FormElements\DatePicker` and
            :php-short:`\TYPO3\CMS\Form\Domain\Model\FormElements\FileUpload`.
            Set properties (:php:`setProperty()`), add validators
            (:php:`createValidator()`), define default values
            (:php:`setDefaultValue()`).

    *   -   :php-short:`\TYPO3\CMS\Form\Domain\Configuration\ConfigurationService`
        -   Reads the merged prototype configuration.
            Call :php:`getPrototypeConfiguration('standard')` to obtain the
            full array for a prototype.


..  _apireference-frontendrendering-programmatically-initializeformelement:

Initializing elements at runtime
---------------------------------

Override :php:`initializeFormElement()` in a custom form element class to
populate data (e.g. from a database) when the element is added to the form.
At that point the prototype defaults have already been applied; properties
from the YAML definition are applied **afterwards**.

..  tip::
    If you only need to initialize an element without writing a full custom
    class, listen to the :php:`BeforeRenderableIsAddedToFormEvent` PSR-14
    event instead. See :ref:`apireference-events`.


..  _apireference-frontendrendering-finishers:

Working with finishers
======================

Custom finishers extend :php-short:`\TYPO3\CMS\Form\Domain\Finishers\AbstractFinisher`
and place their logic in :php:`executeInternal()`. The base class provides:

:php:`parseOption(string $optionName)`
    Resolves a finisher option, applying form-element variable replacements
    and TypoScript-style option overrides. Always prefer this over direct
    array access.

The :php-short:`\TYPO3\CMS\Form\Domain\Finishers\FinisherContext` passed to
:php:`execute()` gives access to:

:php:`getFormRuntime()`
    The :php-short:`\TYPO3\CMS\Form\Domain\Runtime\FormRuntime` for the current
    submission.

:php:`getFormValues()`
    All submitted values (after validation and property mapping).

:php:`getFinisherVariableProvider()`
    A key/value store to **share data between finishers** within the same
    request. The returned
    :php-short:`\TYPO3\CMS\Form\Domain\Finishers\FinisherVariableProvider` offers:

    :php:`add(string $finisherIdentifier, string $key, mixed $value)`
        Store a value under a finisher-specific namespace.

    :php:`get(string $finisherIdentifier, string $key, mixed $default = null)`
        Retrieve a previously stored value (returns :php:`$default` if not set).

    :php:`exists(string $finisherIdentifier, string $key)`
        Check whether a value has been stored.

    :php:`remove(string $finisherIdentifier, string $key)`
        Remove a stored value.

:php:`cancel()`
    Stops execution of any remaining finishers.

..  seealso::
    :ref:`Accessing finisher options <concepts-finishers-customfinisherimplementations-accessingoptions>` |
    :ref:`Sharing data between finishers <concepts-finishers-customfinisherimplementations-finishercontext-sharedatabetweenfinishers>`


..  _apireference-frontendrendering-runtimemanipulation:

Runtime manipulation
====================

..  _apireference-frontendrendering-runtimemanipulation-events:

EXT:form dispatches PSR-14 events at every important step of the rendering
lifecycle. Use them to modify the form, redirect page flow, or adjust
submitted values – without subclassing framework internals.

..  seealso::
    :ref:`PSR-14 events overview for EXT:form <apireference-events>`
