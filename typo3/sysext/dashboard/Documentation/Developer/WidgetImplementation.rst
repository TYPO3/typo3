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

    class RssWidget implements WidgetInterface, RequestAwareWidgetInterface
    {
        private ServerRequestInterface $request;

        public function __construct(
            private readonly WidgetConfigurationInterface $configuration,
            private readonly Cache $cache,
            private readonly BackendViewFactory $backendViewFactory,
            private readonly ButtonProviderInterface $buttonProvider = null,
            private readonly array $options = []
        ) {
        }

        public function setRequest(ServerRequestInterface $request): void
        {
            $this->request = $request;
        }

        public function renderWidgetContent(): string
        {
            $view = $this->backendViewFactory->create($this->request);
            $this->view->assignMultiple([
                'items' => $this->getRssItems(),
                'options' => $this->options,
                'button' => $this->getButton(),
                'configuration' => $this->configuration,
            ]);
            return $this->view->render('Widget/RssWidget');
        }

        protected function getRssItems(): array
        {
            $items = [];
            // Logic to populate $items array
            return $items;
        }

        public function getOptions(): array
        {
            return $this->options;
        }
   }

The class should always provide documentation how to use in :file:`Services.yaml`.
The above class is documented at :ref:`rss-widget`.
The documentation should provide all possible options and an concrete example.
It should make it possible for integrators to register new widgets using the implementation.

The difference between :php:`$options` and :php:`$configuration` in above example is the following:
:php:`$options` are the options for this implementation which can be provided through :file:`Services.yaml`.
:php:`$configuration` is an instance of :php:class:`WidgetConfigurationInterface`
holding all internal configuration, like icon identifier.

.. _implement-new-widget-fluid:

Using Fluid
-----------

Most widgets will need a template.
Therefore each widget can define :php:`BackendViewFactory` as requirement for DI in
constructor, like done in RSS example.


.. _implement-new-widget-custom-js:

Providing custom JS
-------------------

There are two ways to add JavaScript for an widget:

JavaScript module
    Implement :php:class:`\TYPO3\CMS\Dashboard\Widgets\JavaScriptInterface`:

    ..  code-block:: php

        class ExampleChartWidget implements JavaScriptInterface
        {
            // ...
            public function getJavaScriptModuleInstructions(): array
            {
                return [
                    JavaScriptModuleInstruction::create(
                        '@myvendor/my-extension/module-name.js'
                    )->invoke('initialize'),
                    JavaScriptModuleInstruction::create(
                        '@myvendor/my-extension/module-name2.js'
                    )->invoke('initialize'),
                ];
            }
        }

    ..  seealso::

        :ref:`t3coreapi:backend-javascript-es6` for more info about JavaScript in TYPO3 Backend.

Plain JS files
    Implement :php:class:`AdditionalJavaScriptInterface`:

    .. code-block:: php

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

JavaScript
    Implement :php:class:`\TYPO3\CMS\Dashboard\Widgets\JavaScriptInterface`:

    .. code-block:: php

        class ExampleChartWidget implements JavaScriptInterface
        {
            // ...
            public function getJavaScriptModuleInstructions(): array
            {
                return [
                    JavaScriptModuleInstruction::create(
                        '@typo3/dashboard/chart-initializer.js'
                    )->invoke('initialize'),
                ];
            }
        }

All ways can be combined.

Migration from RequireJsModuleInterface
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. versionchanged:: 12.0
    The :php:`RequireJsModuleInterface` has been deprecated, use
    :php:`JavaScriptInterface` instead.

Affected widgets have to implement :php:`\TYPO3\CMS\Dashboard\Widgets\JavaScriptInterface`
instead of deprecated :php:`\TYPO3\CMS\Dashboard\Widgets\RequireJsModuleInterface`.
Instead of using inline JavaScript for initializing RequireJS modules,
:php:`\TYPO3\CMS\Core\Page\JavaScriptModuleInstruction` have to be declared.

.. code-block:: php

    class ExampleChartWidget implements RequireJsModuleInterface
    {
        // ...
        public function getJavaScriptModuleInstructions(): array
        {
            return [
                'TYPO3/CMS/Dashboard/ChartInitializer' =>
                    'function(ChartInitializer) { ChartInitializer.initialize(); }',
            ];
        }
    }

Deprecated example widget above would look like the following when using
`JavaScriptInterface` and `JavaScriptModuleInstruction`:

.. code-block:: php

    class ExampleChartWidget implements JavaScriptInterface
    {
        // ...
        public function getJavaScriptModuleInstructions(): array
        {
            return [
                JavaScriptModuleInstruction::forRequireJS(
                    'TYPO3/CMS/Dashboard/ChartInitializer'
                )->invoke('initialize'),
            ];
        }
    }



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
