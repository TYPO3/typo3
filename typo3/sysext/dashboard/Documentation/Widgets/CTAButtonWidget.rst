.. include:: /Includes.rst.txt

..  _cta-button-widget:

=================
CTA Button Widget
=================

.. php:namespace:: TYPO3\CMS\Dashboard\Widgets
.. php:class:: TYPO3\CMS\Dashboard\Widgets\CtaWidget

Widgets using this class will show a CTA (=Call to action) button to easily go to
a specific page or do a specific action. You can add a button to the widget by
defining a button provider.

You can use this kind of widget to link to for example a manual or to an important
website that is used a lot by the users.

..  _cta-button-widget-example:

Example
-------

..  code-block:: yaml
    :caption: Excerpt from EXT:dashboard/Configuration/Services.yaml

    services:
      dashboard.widget.docGettingStarted:
       class: 'TYPO3\CMS\Dashboard\Widgets\CtaWidget'
       arguments:
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

..  _cta-button-widget-options:

Options
-------

.. include:: Options/RefreshAvailable.rst.txt

..  confval:: text
    :name: cta-button-text
    :type: string
    :Example: `LLL:EXT:dashboard/Resources/Private/Language/locallang.xlf:widgets.documentation.gettingStarted.text`

    Adds an optional text to the widget to give some more background information
    about what a user can expect when clicking the button.
    You can either enter a normal string or a translation string.

..  _cta-button-widget-dependencies:

Dependencies
------------

..  confval:: $buttonProvider
    :type: :php:`\TYPO3\CMS\Dashboard\Widgets\ButtonProviderInterface`
    :name: cta-button-buttonProvider

    Provides the actual button to show within the widget.
    This button should be provided by a ButtonProvider that implements the interface
    :php-short:`\TYPO3\CMS\Dashboard\Widgets\ButtonProviderInterface`.

    See :ref:`adding-buttons` for further info and configuration options.
