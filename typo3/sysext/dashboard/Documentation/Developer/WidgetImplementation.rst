.. include:: /Includes.rst.txt

.. highlight:: php

.. _implement-new-widget:

====================
Implement new widget
====================

.. php:namespace:: TYPO3\CMS\Dashboard\Widgets

.. seealso::

   For information regarding registration of widgets, see: :ref:`register-new-widget`.
   This section describes the implementation of new widgets for developers.

Each extension can provide multiple Widgets.
ext:dashboard already ships with some widget implementations.

Each widget has to be implemented as a PHP class.
The PHP class defines the concrete implementation and features of a widget,
while registration adds necessary options for a concrete instance of a widget.

For example a TYPO3.org RSS Widget would consist of an :php:`RssWidget` PHP class.
This class would provide the implementation to fetch rss news and display them.
The concrete registration will provide the URL to RSS feed.

PHP class
---------

Each Widget has to be a PHP class.
This class has to implement the :php:class:`WidgetInterface` and could look like this::

   class RssWidget implements WidgetInterface
   {
       /**
        * @var WidgetConfigurationInterface
        */
       private $configuration;

       /**
        * @var StandaloneView
        */
       private $view;

       /**
        * @var Cache
        */
       private $cache;

       /**
        * @var array
        */
       private $options;

       /**
        * @var ButtonProviderInterface|null
        */
       private $buttonProvider;

       public function __construct(
           WidgetConfigurationInterface $configuration,
           Cache $cache,
           StandaloneView $view,
           ButtonProviderInterface $buttonProvider = null,
           array $options = []
       ) {
           $this->configuration = $configuration;
           $this->view = $view;
           $this->cache = $cache;
           $this->options = [
               'limit' => 5,
           ] + $options;
           $this->buttonProvider = $buttonProvider;
       }

       public function renderWidgetContent(): string
       {
           $this->view->setTemplate('Widget/RssWidget');
           $this->view->assignMultiple([
               'items' => $this->getRssItems(),
               'options' => $this->options,
               'button' => $this->getButton(),
               'configuration' => $this->configuration,
           ]);
           return $this->view->render();
       }

       protected function getRssItems(): array
       {
           $items = [];

           // Logic to populate $items array

           return $items;
       }
   }

The class should always provide documentation how to use in :file:`Services.yaml`.
The above class is documented at :ref:`rss-widget`.
The documentation should provide all possible options and an concrete example.
It should make it possible for integrators to register new widgets using the implementation.

The difference between ``$options`` and ``$configuration`` in above example is the following:
``$options`` are the options for this implementation which can be provided through :file:`Services.yaml`.
``$configuration`` is an instance of :php:class:`WidgetConfigurationInterface` holding all internal configuration, like icon identifier.

.. _implement-new-widget-fluid:

Using Fluid
-----------

Most widgets will need a template.
Therefore each widget can define :php:`StandaloneView` as requirement for DI in constructor, like done in RSS example.
In order to provide a common configured instance to all widgets,
the following service can be used in :file:`Services.yaml` to provide the instance:

.. code-block:: yaml

   dashboard.widget.t3news:
     class: 'TYPO3\CMS\Dashboard\Widgets\RssWidget'
     arguments:
       $view: '@dashboard.views.widget'

The instance will be pre configured with paths, see :ref:`adjust-template-of-widget`,
and can be used as shown in RSS widget example above.

.. _implement-new-widget-custom-js:

Providing custom JS
-------------------

There are two ways to add JavaScript for an widget:

RequireJS AMD module
   Implement :php:class:`RequireJsModuleInterface`::

      class RssWidget implements WidgetInterface, RequireJsModuleInterface
      {
          public function getRequireJsModules(): array
          {
              return [
                  'TYPO3/CMS/MyExtension/ModuleName',
                  'TYPO3/CMS/MyExtension/Module2Name',
              ];
          }
      }

   .. seealso::

      :ref:`t3coreapi:requirejs` for more info about RequireJS in TYPO3 Backend.

Plain JS files
   Implement :php:class:`AdditionalJavaScriptInterface`::

      class RssWidget implements WidgetInterface, AdditionalJavaScriptInterface
      {
          public function getJsFiles(): array
          {
              return [
                  'EXT:my_extension/Resources/Public/JavaScript/file.js',
                  'EXT:my_extension/Resources/Public/JavaScript/file2.js',
              ];
          }
      }

Both ways can also be combined.

Providing custom CSS
--------------------

It is possible to add custom Css to style widgets.

Implement :php:class:`AdditionalCssInterface`::

   class RssWidget implements WidgetInterface, AdditionalCssInterface
   {
         public function getCssFiles(): array
         {
            return [
                'EXT:my_extension/Resources/Public/Css/widgets.css',
                'EXT:my_extension/Resources/Public/Css/list-widget.css',
            ];
         }
   }
