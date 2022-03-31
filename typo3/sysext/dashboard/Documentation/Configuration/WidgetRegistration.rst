.. include:: /Includes.rst.txt

Widgets need to be provided by an extension, e.g. by ext:dashboard.
They are provided as a PHP class with specific feature sets.
Each of the widgets can be registered with different configurations as documented below.

.. include:: /Shared/DifferenceRegistrationAndImplementation.rst.txt

The below example will use the RSS Widget as a concrete example.

.. _register-new-widget:

===================
Register new Widget
===================

Registration happens through :ref:`Dependency Injection <t3coreapi:DependencyInjection>`
either in :file:`Services.yaml` or :file:`Services.php`.
Both files can exist and will be merged.

:file:`Services.yaml` is recommended and easier to write,
while :file:`Services.php` provide way more flexibility.

Naming widgets
--------------

Widgets receive a name in form of ``dashboard.widget.vendor.ext_key.widgetName``.

``vendor``
   Should be a snaked version of composer vendor.

``ext_key``
   Should be the extension key.

This prevents naming conflicts if multiple 3rd Party extensions are installed.

Services.yaml File
------------------

In order to turn the PHP class :php:`\TYPO3\CMS\Dashboard\Widgets\RssWidget` into an actual widget,
the following service registration can be used inside of :file:`Configuration/Services.yaml`::

   services:
     _defaults:
       autowire: true
       autoconfigure: true
       public: false

     TYPO3\CMS\Dashboard\:
       resource: '../Classes/*'

     dashboard.widget.t3news:
       class: 'TYPO3\CMS\Dashboard\Widgets\RssWidget'
       arguments:
         $view: '@dashboard.views.widget'
         $buttonProvider: '@dashboard.buttons.t3news'
         $cache: '@cache.dashboard.rss'
         $options:
           feedUrl: 'https://www.typo3.org/rss'
       tags:
         - name: dashboard.widget
           identifier: 't3news'
           groupNames: 'news'
           title: 'LLL:EXT:dashboard/Resources/Private/Language/locallang.xlf:widgets.t3news.title'
           description: 'LLL:EXT:dashboard/Resources/Private/Language/locallang.xlf:widgets.t3news.description'
           iconIdentifier: 'content-widget-rss'
           height: 'large'
           width: 'medium'

The beginning of the file is not related to the widget itself, but dependency injection in general,
see: :ref:`t3coreapi:configure-dependency-injection-in-extensions`.

Service configuration
"""""""""""""""""""""

The last block configured a service called :yaml:`dashboard.widget.t3news`.

This service is configured to use the existing PHP class :php:`TYPO3\CMS\Dashboard\Widgets\RssWidget`.
When creating the instance of this class, an array is provided for the constructor argument :php:`$options`.
This way the same PHP class can be used with different configuration to create new widgets.

The following keys are defined for the service:

.. option:: class

   In example: :php:`TYPO3\CMS\Dashboard\Widgets\RssWidget`.

   Defines concrete PHP class to use as implementation.

.. option:: arguments

   Highly depending on the used widget.
   Each widget can define custom arguments, which should be documented.

   Documentation for provided widgets is available at :ref:`widgets`.

.. option:: tags

   Registers the service as an actual widget for ext:dashboard.
   See :ref:`register-new-widget-tags-section`.

.. _register-new-widget-tags-section:

Tags Section
""""""""""""

In order to turn the instance into a widget, the tag ``dashboard.widget`` is configured in `tags` section.
The following options are mandatory and need to be provided:

.. option:: name

   Always has to be ``dashboard.widget``.
   Defines that this tag configured the service to be registered as a widget for
   ext:dashboard.

.. option:: identifier

   In example: ``t3news``.

   Used to store which widgets are currently assigned to dashboards.
   Furthermore used to allow access control, see :ref:`permission-handling-of-widgets`.

.. option:: groupNames

   In example: ``news``.

   Defines which groups should contain the widget.
   Used when adding widgets to a dashboard to group related widgets in tabs.
   Multiple names can be defined as comma separated string, e.g.: ``typo3, general``.

   See :ref:`create-widget-group` regarding how to create new widget groups.
   There is no difference between custom groups and existing groups.
   Widgets are registered to all groups by their name.

.. option:: title

   In example: ``LLL:EXT:dashboard/Resources/Private/Language/locallang.xlf:widgets.t3news.title``.

   Defines the title of the widget. Language references are resolved.

.. option:: description

   In example: ``LLL:EXT:dashboard/Resources/Private/Language/locallang.xlf:widgets.t3news.description``.

   Defines the description of the widget. Language references are resolved.

.. option:: iconIdentifier

   In example: ``content-widget-rss``.

   One of registered icons.
   Icons can be registered through :ref:`t3coreapi:icon`.

The following options are optional and have default values which will be used if not defined:

.. option:: height

   In example: ``large``.

   Has to be an string value ``large``, ``medium`` or ``small``.

.. option:: width

   In example ``medium``.

   Has to be an string value ``large``, ``medium`` or ``small``.

.. option:: additionalCssClasses

   Will be added to the surrounding rendered markup.
   Usually used when working with Graphs.

Splitting up Services.yaml
--------------------------

In case the :file:`Services.yaml` is getting to large, it can be split up.
The official documentation can be found at `symfony.com <https://symfony.com/doc/current/service_container/import.html>`__.
An example to split up all Widget related configuration would look like:

:file:`Configuration/Services.yaml`::

   imports:
     - { resource: Backend/DashboardWidgets.yaml }

.. note::

   Note that you have to repeat all necessary information, e.g. :yaml:`services:` section with :yaml:`_defaults:` again.

:file:`Configuration/Backend/DashboardWidgets.yaml`::

   services:
     _defaults:
       autowire: true
       autoconfigure: true
       public: false

     TYPO3\CMS\Dashboard\Widgets\:
       resource: '../Classes/Widgets/*'

     dashboard.buttons.t3news:
       class: 'TYPO3\CMS\Dashboard\Widgets\Provider\ButtonProvider'
       arguments:
         $title: 'LLL:EXT:dashboard/Resources/Private/Language/locallang.xlf:widgets.t3news.moreItems'
         $link: 'https://typo3.org/project/news'
         $target: '_blank'

     dashboard.widget.t3news:
       class: 'TYPO3\CMS\Dashboard\Widgets\RssWidget'
       arguments:
         $view: '@dashboard.views.widget'
         $buttonProvider: '@dashboard.buttons.t3news'
         $cache: '@cache.dashboard.rss'
         $options:
           feedUrl: 'https://www.typo3.org/rss'
       tags:
         - name: dashboard.widget
           identifier: 't3news'
           groupNames: 'news'
           title: 'LLL:EXT:dashboard/Resources/Private/Language/locallang.xlf:widgets.t3news.title'
           description: 'LLL:EXT:dashboard/Resources/Private/Language/locallang.xlf:widgets.t3news.description'
           iconIdentifier: 'content-widget-rss'
           height: 'large'
           width: 'medium'


Services.php File
-----------------

This is not intended for integrators but developers only, as this involves PHP experience.

The typical use case should be solved via :file:`Services.yaml`.
But for more complex situations, it is possible to register widgets via :file:`Services.php`.
Even if :file:`Services.php` contains PHP, it is only executed during compilation of the dependency injection container.
Therefore, it is not possible to check for runtime information like URLs, users, configuration or packages.

Instead, this approach can be used to register widgets only if their service dependencies are available.
The :php:`ContainerBuilder` instance provides a method :php:`hasDefinition()`
that may be used to check for optional dependencies.
Make sure to declare the optional dependencies in :file:`composer.json` and :php:`ext_emconf.php` as
suggested extensions to ensure packages are ordered correctly in order for
services to be registered with deterministic ordering.

The following example demonstrates how a widget can be registered via :file:`Services.php`:

.. code-block:: php


   <?php

   declare(strict_types=1);
   namespace Vendor\ExtName;

   use Vendor\ExtName\Widgets\ExampleWidget;
   use Vendor\ExtName\Widgets\Provider\ExampleProvider;
   use Symfony\Component\DependencyInjection\ContainerBuilder;
   use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
   use Symfony\Component\DependencyInjection\Reference;
   use TYPO3\CMS\Report\Status;

   return function (ContainerConfigurator $configurator, ContainerBuilder $containerBuilder) {
       $services = $configurator->services();

       if ($containerBuilder->hasDefinition(Status::class)) {
           $services->set('widgets.dashboard.widget.exampleWidget')
               ->class(ExampleWidget::class)
               ->arg('$view', new Reference('dashboard.views.widget'))
               ->arg('$buttonProvider', new Reference(ExampleProvider::class))
               ->arg('$options', ['template' => 'Widget/ExampleWidget'])
               ->tag('dashboard.widget', [
                  'identifier' => 'widgets-exampleWidget',
                  'groupNames' => 'systemInfo',
                  'title' => 'LLL:EXT:ext_key/Resources/Private/Language/locallang.xlf:widgets.dashboard.widget.exampleWidget.title',
                  'description' => 'LLL:EXT:ext_key/Resources/Private/Language/locallang.xlf:widgets.dashboard.widget.exampleWidget.description',
                  'iconIdentifier' => 'content-widget-list',
                  'height' => 'medium',
                  'width' => 'medium'
               ])
           ;
       }
   };

Above example will register a new widget called ``widgets.dashboard.widget.exampleWidget``.
The widget is only registered, in case the extension "reports" is enabled, which
results in the availablity of the :php:`TYPO3\CMS\Report\Status` during container compile time.

Configuration is done in the same way as with :file:`Services.yaml`, except a PHP API is used.
The :php:`new Reference` equals to :yaml:`@` inside the YAML, to reference another service.
:yaml:`arguments:` are registered via :php:`->arg()` method call.
And :yaml:`tags:` are added via :php:`->tag()` method call.

Using this approach, it is possible to provide widgets that depend on 3rd party code,
without requiring this 3rd party code.
Instead the 3rd party code can be suggested and is supported if its installed.

Further information regarding how :file:`Services.php` works in general, can be found
at `symfony.com <https://symfony.com/doc/current/components/dependency_injection.html>`_.
Make sure to switch code examples from YAML to PHP.
