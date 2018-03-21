.. include:: ../../Includes.txt

============================================================
Breaking: #57594 - Optimize ReflectionService Cache handling
============================================================

See :issue:`57594`

Description
===========

The `extbase_object` cache has been removed completely and all necessary information about objects,
mainly @inject information, is now fetched from the ReflectionService as well.

The ReflectionService does still create `ClassSchema` instances but these were improved a lot. All
necessary information is now gathered during the instantiation of `ClassSchema` instances. That means
that all necessary data is fetched once and then it can be used everywhere making any further
reflection superfluous.

As runtime reflection has been removed completely, along with it several reflection classes, that
analyzed doc blocks, have been removed as well. These are no longer necessary.

The `extbase_reflection` cache is no longer plugin based and will no longer be stored in the database
in the first place. Serialized ClassSchema instances will be stored in `typo3temp/var/cache` or `var/cache` for
composer-based installations.

The following classes for internal use only and have been removed:

* :php:`ClassInfo`
* :php:`ClassInfoCache`
* :php:`ClassInfoFactory`
* :php:`ClassReflection`
* :php:`MethodReflection`
* :php:`ParameterReflection`
* :php:`PropertyReflection`

The following methods of the PHP class :php:`ReflectionService` have been removed:

* :php:`injectConfigurationManager`
* :php:`setDataCache`
* :php:`initialize`
* :php:`isInitialized`
* :php:`shutdown`


Impact
======

Installations using the above classes or methods will throw a fatal error.


Affected Installations
======================

Installations using one of the mentioned classes or methods instead of the ReflectionService API.


Migration
=========

Use the class :php:`ReflectionService` as API which will be automatically initialized on
instantiation.

.. index:: PHP-API, FullyScanned, ext:extbase
