.. include:: /Includes.rst.txt

.. _feature-94117:

=============================================================
Feature: #94117 - Improve Extbase type converter registration
=============================================================

See :issue:`94117`

Description
===========

Extbase type converters are an important part of the Extbase data and property
mapping mechanism. Those converters usually convert data from simple types
to objects or other simple types.

Extension authors can add their own type converters. This was previously done
by registering the type converter class in the :file:`ext_localconf.php`
file and adding the configuration, such as the `sourceType`, the `targetType`
or the `priority` as class properties, accessible via public methods.

This has now been improved. Type converters are now registered as container
services in the extension's :file:`Services.yaml` file by tagging the service
with :yaml:`extbase.type_converter` and adding the configuration as tag
attributes.

This means, the registration via php:`\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerTypeConverter()`
can be removed together with the configuration related class properties and
methods. See :doc:`changelog <../12.0/Breaking-94117-RegisterExtbaseTypeConvertersAsServices>`
for more information.

Impact
======

Registration is now done in your :file:`Services.yaml` like the following:

..  code-block:: yaml

    services:
      Vendor\Extension\Property\TypeConverter\MyBooleanConverter:
        tags:
          - name: extbase.type_converter
            priority: 10
            target: boolean
            sources: boolean,string

.. tip::

    Tag arguments (priority, target, sources, etc.) have to be simple types.
    Don't register the sources as array but as comma separated list as shown
    in the example.

.. note::

    Since the configuration (priority, target and sources) are now done at
    this place, respective type converter properties are now superfluous and
    will also no longer be evaluated. See the :doc:`deprecation changelog <../12.0/Deprecation-94117-RegisterExtbaseTypeConvertersAsServices>`
    for more information.

.. index:: PHP-API, ext:extbase
