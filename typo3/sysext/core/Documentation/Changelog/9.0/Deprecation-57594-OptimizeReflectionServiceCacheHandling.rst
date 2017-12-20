.. include:: ../../Includes.txt

=======================================================================
Deprecation: #57594 - Optimize extbase ReflectionService Cache handling
=======================================================================

See :issue:`57594`

Description
===========

In the process of streamlining the internal reflection / docparser cache handling, the following
methods of the PHP class :php:`ClassSchema` have been deprecated:

* :php:`addProperty()`
* :php:`setModelType()`
* :php:`getModelType()`
* :php:`setUuidPropertyName()`
* :php:`getUuidPropertyName()`
* :php:`markAsIdentityProperty()`
* :php:`getIdentityProperties()`


Impact
======

Installations using the above methods will trigger a :php:`E_USER_DEPRECATED` warning.


Affected Installations
======================

Installations using one of the mentioned methods instead of the ReflectionService API.


Migration
=========

Use the class :php:`ReflectionService` as API which will be automatically initialized on
nstantiation.

.. index:: PHP-API, FullyScanned, ext:extbase
