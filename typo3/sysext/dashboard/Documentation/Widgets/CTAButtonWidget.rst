.. include:: /Includes.rst.txt

.. _cta-button-widget:

=================
CTA Button Widget
=================

.. php:namespace:: TYPO3\CMS\Dashboard\Widgets
.. program:: TYPO3\CMS\Dashboard\Widgets\CtaWidget

Widgets using this class will show a CTA (=Call to action) button to easily go to
a specific page or do a specific action. You can add a button to the widget by
defining a button provider.

You can use this kind of widget to link to for example a manual or to an important
website that is used a lot by the users.

Example
-------

:file:`Configuration/Services.yaml`::

   services:

      dashboard.widget.docGettingStarted:
       class: 'TYPO3\CMS\Dashboard\Widgets\CtaWidget'
       arguments:
         $view: '@dashboard.views.widget'
         $buttonProvider: '@dashboard.buttons.docGettingStarted'
         $options:
           text: 'LLL:EXT:dashboard/Resources/Private/Language/locallang.xlf:widgets.documentation.gettingStarted.text'
       tags:
         - name: dashboard.widget
           identifier: 'docGettingStarted'
           groupNames: 'documentation'
           title: 'LLL:EXT:dashboard/Resources/Private/Language/locallang.xlf:widgets.documentation.gettingStarted.title'
           description: 'LLL:EXT:dashboard/Resources/Private/Language/locallang.xlf:widgets.documentation.gettingStarted.description'
           iconIdentifier: 'content-widget-text'
           height: 'small'

Options
-------

.. include:: Options/RefreshAvailable.rst.txt

.. option:: text

   Adds an optional text to the widget to give some more background information
   about what a user can expect when clicking the button.
   You can either enter a normal string or a translation string
   e.g. ``LLL:EXT:dashboard/Resources/Private/Language/locallang.xlf:widgets.documentation.gettingStarted.text``.

Dependencies
------------

.. option:: $buttonProvider

   Provides the actual button to show within the widget.
   This button should be provided by a ButtonProvider that implements the interface :php:class:`ButtonProviderInterface`.

   See :ref:`adding-buttons` for further info and configuration options.

.. option:: $view

   Used to render a Fluidtemplate.
   This should not be changed.
   The default is to use the pre configured Fluid StandaloneView for EXT:dashboard.

   See :ref:`implement-new-widget-fluid` for further information.
