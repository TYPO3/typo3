.. include:: /Includes.rst.txt

.. _deprecation-94117:

==================================================================
Deprecation: #94117 - Register extbase type converters as services
==================================================================

See :issue:`94117`

Description
===========

Because Extbase type converters are no longer registered via
:php:`\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerTypeConverter()` but
as container services, also the configuration, such as `sourceType` or
`targetType` is now defined in the :file:`Services.yaml`.

Therefore, the following configuration related properties and methods
of :php:`\TYPO3\CMS\Extbase\Property\TypeConverter\AbstractTypeConverter` have
been deprecated:

- :php:`\TYPO3\CMS\Extbase\Property\TypeConverter\AbstractTypeConverter::$sourceTypes`
- :php:`\TYPO3\CMS\Extbase\Property\TypeConverter\AbstractTypeConverter::$targetType`
- :php:`\TYPO3\CMS\Extbase\Property\TypeConverter\AbstractTypeConverter::$priority`
- :php:`\TYPO3\CMS\Extbase\Property\TypeConverter\AbstractTypeConverter::getSupportedSourceTypes()`
- :php:`\TYPO3\CMS\Extbase\Property\TypeConverter\AbstractTypeConverter::getSupportedTargetType()`
- :php:`\TYPO3\CMS\Extbase\Property\TypeConverter\AbstractTypeConverter::getPriority()`
- :php:`\TYPO3\CMS\Extbase\Property\TypeConverter\AbstractTypeConverter::canConvertFrom()`

The methods have also been removed from the :php:`TypeConverterInterface`, see
:doc:`changelog <../12.0/Breaking-94117-RegisterExtbaseTypeConvertersAsServices>`.

Impact
======

Since those properties and methods were important for registering and
configuring type converters but are replaced with type converter registrations
in :file:`Services.yaml`, they are now obsolete and without functionality.

If defined in an own type converter, those properties and methods can be
removed there as well.

Affected Installations
======================

All installations with custom type converters, extending :php:`AbstractTypeConverter`
and relying on those properties and methods.

Migration
=========

In custom type converters, drop mentioned properties and methods and don't access
said properties and methods of :php:`AbstractTypeConverter` from outside.

.. index:: PHP-API, NotScanned, ext:extbase
