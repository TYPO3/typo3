.. include:: /Includes.rst.txt

.. _breaking-101291-1688740732:

==================================================
Breaking: #101291 - Introduce capabilities bit set
==================================================

See :issue:`101291`

Description
===========

The capabilities property of the :php:`ResourceStorage` and drivers
(:php:`LocalDriver`/:php:`AbstractDriver`) have been converted from an integer
(holding a bit value) to an instance of a new :php:`BitSet` class
:php:`\TYPO3\CMS\Core\Resource\Capabilities`.

This affects the public API of the following interface methods:

- :php:`\TYPO3\CMS\Core\Resource\Driver\DriverInterface::getCapabilities()`
- :php:`\TYPO3\CMS\Core\Resource\Driver\DriverInterface::mergeConfigurationCapabilities()`

In consequence, all mentioned methods of implementations are affected as well,
those of:

- :php:`\TYPO3\CMS\Core\Resource\Driver\AbstractDriver::getCapabilities()`
- :php:`\TYPO3\CMS\Core\Resource\Driver\LocalDriver::mergeConfigurationCapabilities()`

Also the following constants have been removed:

- :php:`\TYPO3\CMS\Core\Resource\ResourceStorageInterface::CAPABILITY_BROWSABLE`
- :php:`\TYPO3\CMS\Core\Resource\ResourceStorageInterface::CAPABILITY_PUBLIC`
- :php:`\TYPO3\CMS\Core\Resource\ResourceStorageInterface::CAPABILITY_WRITABLE`
- :php:`\TYPO3\CMS\Core\Resource\ResourceStorageInterface::CAPABILITY_HIERARCHICAL_IDENTIFIERS`


Impact
======

The return type of the following methods, respective their implementations have
changed from :php:`int` to :php:`\TYPO3\CMS\Core\Resource\Capabilities`:

- :php:`\TYPO3\CMS\Core\Resource\Driver\DriverInterface::getCapabilities()`
- :php:`\TYPO3\CMS\Core\Resource\Driver\DriverInterface::mergeConfigurationCapabilities()`

The type of the parameter :php:`$capabilities` of the method
:php:`mergeConfigurationCapabilities()` has been changed from :php:`int` to
:php:`\TYPO3\CMS\Core\Resource\Capabilities`.

The usage of the mentioned, removed constants of
:php:`\TYPO3\CMS\Core\Resource\ResourceStorageInterface` will lead to errors.


Affected installations
======================

Installations that implement custom drivers and therefore directly implement
:php:`\TYPO3\CMS\Core\Resource\Driver\DriverInterface` or extend
:php:`\TYPO3\CMS\Core\Resource\Driver\AbstractDriver`.

Also, installations that use the removed constants of
:php:`\TYPO3\CMS\Core\Resource\ResourceStorageInterface`.


Migration
=========

When using mentioned methods that formerly returned the bit value as integer or
expected the bit value as integer parameter need to use the :php:`Capabilities`
class instead. It behaves exactly the same as the plain integer. If the plain
integer value needs to be retrieved, :php:`__toInt()` can be called on
:php:`Capabilities` instances.

The following removed constants

- :php:`\TYPO3\CMS\Core\Resource\ResourceStorageInterface::CAPABILITY_BROWSABLE`
- :php:`\TYPO3\CMS\Core\Resource\ResourceStorageInterface::CAPABILITY_PUBLIC`
- :php:`\TYPO3\CMS\Core\Resource\ResourceStorageInterface::CAPABILITY_WRITABLE`
- :php:`\TYPO3\CMS\Core\Resource\ResourceStorageInterface::CAPABILITY_HIERARCHICAL_IDENTIFIERS`

can be replaced with public constants of the new :php:`Capabilities` class:

- :php:`\TYPO3\CMS\Core\Resource\Capabilities::CAPABILITY_BROWSABLE`
- :php:`\TYPO3\CMS\Core\Resource\Capabilities::CAPABILITY_PUBLIC`
- :php:`\TYPO3\CMS\Core\Resource\Capabilities::CAPABILITY_WRITABLE`
- :php:`\TYPO3\CMS\Core\Resource\Capabilities::CAPABILITY_HIERARCHICAL_IDENTIFIERS`

.. index:: FAL, PHP-API, NotScanned, ext:core
