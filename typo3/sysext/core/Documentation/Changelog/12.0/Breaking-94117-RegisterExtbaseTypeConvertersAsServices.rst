.. include:: /Includes.rst.txt

.. _breaking-94117:

===============================================================
Breaking: #94117 - Register Extbase type converters as services
===============================================================

See :issue:`94117`

Description
===========

Extbase type converters are used to convert from a simple type to an
object or another simple type. The registration of those type converters
is no longer done via :php:`\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerTypeConverter()`,
but via container services in the extension's :file:`Services.yaml` file.

As a side effect, the type converter configuration such as `sourceType` or
`targetType` has been moved from the :php:`TypeConverterInterface` to the
service container configuration.

Impact
======

Type converters registered via :php:`\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerTypeConverter()`
are no longer evaluated.

The :php:`TypeConverterInterface` does no longer define the configuration
related methods:

- :php:`getSupportedSourceTypes()`
- :php:`getSupportedTargetType()`
- :php:`getPriority()`
- :php:`canConvertFrom()`

Affected Installations
======================

All installations that do not register type converters via :php:`Services.yaml`.

All installations, which rely on the configuration related methods, being
defined in the :php:`TypeConverterInterface`.

Migration
=========

Remove registration via :php:`\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerTypeConverter()`
from your :file:`ext_localconf.php` file and register the type converters
in your :php:`Services.yaml` instead. See :doc:`changelog <../12.0/Feature-94117-ImproveExtbaseTypeConverterRegistration>`
for an example.

Remove any call to the configuration related methods, see the
:doc:`deprecation changelog <../12.0/Deprecation-94117-RegisterExtbaseTypeConvertersAsServices>`
for more information.

.. index:: PHP-API, NotScanned, ext:extbase
