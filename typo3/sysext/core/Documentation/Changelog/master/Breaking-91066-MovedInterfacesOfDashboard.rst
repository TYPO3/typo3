.. include:: ../../Includes.txt

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
New widget types have implemented one or more of the interfaces of EXT:dashboard.
If the namespace of those interfaces is not changed, you will get errors saying
that the old interfaces are not found anymore.

Affected Installations
======================
All 3rd party extensions that created own widget types and implements one of the
interfaces of EXT:dashboard should update their paths. The accected interfaces
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
The interfaces above has been moved from :php:`TYPO3\CMS\Dashboard\Widgets\Interfaces`
to :php:`TYPO3\CMS\Dashboard\Widgets`. You need to alter the namespaces of those
interfaces in your own widgets.

.. index:: Backend, ext:dashboard, FullyScanned
