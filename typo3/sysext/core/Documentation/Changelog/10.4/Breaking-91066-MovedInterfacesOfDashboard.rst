.. include:: /Includes.rst.txt

===============================================
Breaking: #91066 - Move interfaces of Dashboard
===============================================

See :issue:`91066`

Description
===========

The interfaces of the dashboard have been moved out of the
interfaces folder to be consistent with the overall TYPO3 structure.


Impact
======

New widget types that have implemented one or more of the interfaces of EXT:dashboard.
If the namespace of those interfaces is not changed, you will get errors saying
that the interfaces are not found anymore.


Affected Installations
======================

All 3rd party extensions that created own widget types and implement one of the
interfaces of EXT:dashboard should update their paths. The accepted interfaces
are:

- :php:`AdditionalCssInterface`
- :php:`AdditionalJavascriptInterface`
- :php:`ButtonProviderInterface`
- :php:`ChartDataProviderInterface`
- :php:`EventDataProviderInterface`
- :php:`ListDataProviderInterface`
- :php:`NumberWithIconDataProviderInterface`
- :php:`RequireJsModuleInterface`
- :php:`WidgetConfigurationInterface`
- :php:`WidgetInterface`


Migration
=========

The interfaces listed above have been moved from :php:`TYPO3\CMS\Dashboard\Widgets\Interfaces`
to :php:`TYPO3\CMS\Dashboard\Widgets`. You need to adapt the namespaces of those
interfaces in your own widgets.

.. index:: Backend, ext:dashboard, FullyScanned
