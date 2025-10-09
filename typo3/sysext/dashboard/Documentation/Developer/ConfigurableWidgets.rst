:navigation-title: Configurable widgets

.. include:: /Includes.rst.txt

..  _configurable-widget-implementation:

============================================
Configurable dashboard widget implementation
============================================

..  versionadded:: 14.0
    `Configurable Dashboard Widgets <https://docs.typo3.org/permalink/changelog:feature-107036-1738837673>`_
    have been introduced with TYPO3 14.0.

Widget authors can implement configurable widgets by using to the
renderer interface :php:`TYPO3\CMS\Dashboard\Widgets\WidgetRendererInterface`
which allows to defining settings in their widget renderer.

Settings are automatically validated and processed using the Settings API.
All types that are available for site settings definition are available:
`Definition types <https://docs.typo3.org/permalink/t3coreapi:definition-types>`_.

..  seealso::
    :php:`TYPO3\CMS\Dashboard\Widgets\RssWidget` is a configurable widget
    implementation.

..  _configurable-widget-implementation-example:

Example: A configurable widget implementation
=============================================

..  literalinclude:: _codesnippets/_ConfigurableWidget.php.inc
    :language: php
    :caption: EXT:my_extension/Classes/Widgets/ConfigurableWidget.php
