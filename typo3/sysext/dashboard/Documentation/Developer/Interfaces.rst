.. include:: /Includes.rst.txt

.. highlight:: php

.. _interfaces:

==========
Interfaces
==========

The following list provides information for all necessary interfaces that are used inside of this documentation.
For up to date information, please check the source code.

.. php:namespace:: TYPO3\CMS\Dashboard\Widgets

.. php:class:: WidgetInterface

   Has to be implemented by all widgets.
   This interface defines public API used by ext:dashboard to interact with widgets.

   .. php:method:: renderWidgetContent()

      :returntype: string
      :returns: The rendered HTML to display.

.. php:class:: WidgetConfigurationInterface

   Used internally in ext:dashboard.
   Used to separate internal configuration from widgets.
   Can be required in widget classes and passed to view.

   .. php:method:: getIdentifier()

      :returntype: string
      :returns: Unique identifer of a widget.

   .. php:method:: getServiceName()

      :returntype: string
      :returns: Service name providing the widget implementation.

   .. php:method:: getGroupNames()

      :returntype: array
      :returns: Group names associated to this widget.

   .. php:method:: getTitle()

      :returntype: string
      :returns: Title of a widget, this is used for the widget selector.

   .. php:method:: getDescription()

      :returntype: string
      :returns: Description of a widget, this is used for the widget selector.

   .. php:method:: getIconIdentifier()

      :returntype: string
      :returns: Icon identifier of a widget, this is used for the widget selector.

   .. php:method:: getHeight()

      :returntype: int
      :returns: Height of a widget in rows (1-6).

   .. php:method:: getWidth()

      :returntype: int
      :returns: Width of a widget in columns (1-4).

   .. php:method:: getAdditionalCssClasses()

      :returntype: array
      :returns: Additional CSS classes which should be added to the rendered widget.

.. php:class:: RequireJsModuleInterface

   Widgets implementing this interface will add the provided RequireJS modules.
   Those modules will be loaded in dashboard view if the widget is added at least once.

   .. php:method:: getRequireJsModules()

      Returns a list of RequireJS modules that should be loaded, e.g.::

         return [
             'TYPO3/CMS/MyExtension/ModuleName',
             'TYPO3/CMS/MyExtension/Module2Name',
         ];

      See also :ref:`t3coreapi:requirejs` for further information regarding RequireJS
      in TYPO3 Backend.

      :returntype: array
      :returns: List of modules to require.

.. php:class:: AdditionalJavaScriptInterface

   Widgets implementing this interface will add the provided JavaScript files.
   Those files will be loaded in dashboard view if the widget is added at least once.

   .. php:method:: getJsFiles()

      Returns a list of JavaScript file names that should be included, e.g.::

         return [
             'EXT:my_extension/Resources/Public/JavaScript/file.js',
             'EXT:my_extension/Resources/Public/JavaScript/file2.js',
         ];

      :returntype: array
      :returns: List of JS files to load.

.. php:class:: AdditionalCssInterface

   Widgets implementing this interface will add the provided Css files.
   Those files will be loaded in dashboard view if the widget is added at least once.

   .. php:method:: getCssFiles()

      Returns a list of Css file names that should be included, e.g.::

         return [
             'EXT:my_extension/Resources/Public/Css/widgets.css',
             'EXT:my_extension/Resources/Public/Css/list-widget.css',
         ];

      :returntype: array
      :returns: List of Css files to load.

.. php:class:: ButtonProviderInterface

   .. php:method:: getTitle()

      :returntype: string
      :returns: The title used for the button. E.g. an ``LLL:EXT:`` reference like
                ``LLL:EXT:dashboard/Resources/Private/Language/locallang.xlf:widgets.t3news.moreItems``.

   .. php:method:: getLink()

      :returntype: string
      :returns: The link to use for the button. Clicking the button will open the link.

   .. php:method:: getTarget()

      :returntype: string
      :returns: The target of the link, e.g. ``_blank``.
                ``LLL:EXT:dashboard/Resources/Private/Language/locallang.xlf:widgets.t3news.moreItems``.

.. php:class:: NumberWithIconDataProviderInterface

   .. php:method:: getNumber()

      :returntype: integer
      :returns: The number to display for an number widget.

.. php:class:: EventDataInterface

   .. php:method:: getEventData()

      :returntype: array
      :returns: Returns data which should be send to the widget as JSON encoded value.

.. php:class:: ChartDataProviderInterface

   .. php:method:: getChartData()

      :returntype: array
      :returns: Provide the data for a graph.
         The data and options you have depend on the type of chart.
         More information can be found in the documentation of the specific type:

         Bar
            https://www.chartjs.org/docs/latest/charts/bar.html#data-structure

         Doughnut
            https://www.chartjs.org/docs/latest/charts/doughnut.html#data-structure

.. php:class:: ListDataProviderInterface

   .. php:method:: getItems()

      :returntype: array
      :returns: Provide the array if items.
                Each entry should be a single string.
